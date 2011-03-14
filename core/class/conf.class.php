<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin      	<regis@dolibarr.fr>
 * Copyright (C) 2006 	   Jean Heimburger    	<jean@tiaris.info>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       	htdocs/core/class/conf.class.php
 *	\ingroup		core
 *  \brief      	Fichier de la classe de stockage de la config courante
 *  \remarks		La config est stockee dans le fichier conf/conf.php
 *  \version    	$Id: conf.class.php,v 1.33 2011/01/16 18:47:33 eldy Exp $
 */


/**
 *  \class      Conf
 *  \brief      Classe de stockage de la config courante
 */
class Conf
{
	/** \public */
	//! Object with database handler
	var $db;
	//! To store properties found in conf file
	var $file;
	//! To store if javascript/ajax is enabked
	var $use_javascript_ajax;

	//! Used to store current currency
	var $monnaie;
	//! Used to store current css (from theme)
	var $theme;        // Contains current theme ("eldy", "auguria", ...)
	var $css;          // Contains full path of css page ("/theme/eldy/style.css.php", ...)
    //! Used to store current menu handlers
	var $top_menu;
	var $left_menu;
	var $smart_menu;

	//! Used to store entity for multi-company (default 1)
	var $entity=1;

	var $css_modules			= array();
	var $tabs_modules			= array();
	var $triggers_modules		= array();
	var $hooks_modules			= array();
	var $login_method_modules	= array();
	var $need_smarty			= array();
	var $modules				= array();

	var $logbuffer				= array();

	var $filesystem_forbidden_chars = array('<','>',':','/','\\','?','*','|','"');


	/**
	 * Constructor
	 *
	 * @return Conf
	 */
	function Conf()
	{
		//! Charset for HTML output and for storing data in memory
		$this->file->character_set_client='UTF-8';	// UTF-8, ISO-8859-1
	}


	/**
	 *      Load setup values into conf object (read llx_const)
	 *      @param      $db			    Handler d'acces base
	 *      @return     int         	< 0 if KO, >= 0 if OK
	 */
	function setValues($db)
	{
		dol_syslog("Conf::setValues");

		// Directory of core triggers
		$this->triggers_modules[] = "/includes/triggers";	// Relative path

		// Avoid warning if not defined
		if (empty($this->db->dolibarr_main_db_encryption)) $this->db->dolibarr_main_db_encryption=0;
		if (empty($this->db->dolibarr_main_db_cryptkey))   $this->db->dolibarr_main_db_cryptkey='';

		/*
		 * Definition de toutes les constantes globales d'environnement
		 * - En constante php (TODO a virer)
		 * - En $this->global->key=value
		 */
		$sql = "SELECT ".$db->decrypt('name')." as name";
		$sql.= ",".$db->decrypt('value')." as value, entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE entity IN (0,".$this->entity.")";
		$sql.= " ORDER BY entity";	// This is to have entity 0 first, then entity 1 that overwrite.

		$result = $db->query($sql);
		if ($result)
		{
			$numr = $db->num_rows($result);
			$i = 0;

			while ($i < $numr)
			{
				$objp = $db->fetch_object($result);
				$key=$objp->name;
				$value=$objp->value;
				if ($key)
				{
					if (! defined("$key")) define ("$key", $value);	// In some cases, the constant might be already forced (Example: SYSLOG_FILE during install)
					$this->global->$key=$value;

					if ($value)
					{
						// If this is constant for a css file activated by a module
						if (preg_match('/^MAIN_MODULE_([A-Z_]+)_CSS$/i',$key))
						{
							$this->css_modules[]=$value;
						}
						// If this is constant for a new tab page activated by a module
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)_TABS_/i',$key))
						{
							$params=explode(':',$value,2);
							$this->tabs_modules[$params[0]][]=$value;
							//print 'xxx'.$params[0].'-'.$value;
						}
						// If this is constant for triggers activated by a module
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)_TRIGGERS$/i',$key,$reg))
						{
							$modulename = strtolower($reg[1]);
							$this->triggers_modules[] = '/'.$modulename.'/inc/triggers/';
						}
						// If this is constant for login method activated by a module
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)_LOGIN_METHOD$/i',$key,$reg))
						{
							$modulename = strtolower($reg[1]);
							$this->login_method_modules[] = DOL_DOCUMENT_ROOT.'/'.$modulename.'/inc/login/';
						}
						// If this is constant for hook activated by a module
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)_HOOKS$/i',$key,$reg))
						{
							$modulename = strtolower($reg[1]);
							$params=explode(':',$value);
							foreach($params as $value)
							{
								$this->hooks_modules[$modulename][]=$value;
							}
						}
						// If this is constant to force a module directories (used to manage some exceptions)
						// Should not be used by modules
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)_DIR_/i',$key,$reg))
						{
							$module=strtolower($reg[1]);
							// If with submodule name
							if (preg_match('/_DIR_([A-Z_]+)?_([A-Z]+)$/i',$key,$reg))
							{
								$dir_name  = "dir_".strtolower($reg[2]);
								$submodule = strtolower($reg[1]);
								$this->$module->$submodule->$dir_name = $value;		// We put only dir name. We will add DOL_DATA_ROOT later
								//print '->'.$module.'->'.$submodule.'->'.$dir_name.' = '.$this->$module->$submodule->$dir_name.'<br>';
							}
							elseif (preg_match('/_DIR_([A-Z]+)$/i',$key,$reg))
							{
								$dir_name  = "dir_".strtolower($reg[1]);
								$this->$module->$dir_name = $value;		// We put only dir name. We will add DOL_DATA_ROOT later
								//print '->'.$module.'->'.$dir_name.' = '.$this->$module->$dir_name.'<br>';
							}
						}
						// If this is constant for a smarty need by a module
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)_NEEDSMARTY$/i',$key,$reg))
						{
							$module=strtolower($reg[1]);
							// Add this module in list of modules that need smarty
							$this->need_smarty[]=$module;
						}
						// If this is a module constant
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)$/i',$key,$reg))
						{
							$module=strtolower($reg[1]);
							//print "Module ".$module." is enabled<br>\n";
							$this->$module->enabled=true;
							// Add this module in list of enabled modules
							$this->modules[]=$module;
						}
					}
				}
				$i++;
			}
		}
		$db->free($result);
		//var_dump($this->modules);

		// Clean some variables
		if (empty($this->global->MAIN_MENU_BARRETOP)) $this->global->MAIN_MENU_BARRETOP="eldy_backoffice.php";
		if (empty($this->global->MAIN_MENUFRONT_BARRETOP)) $this->global->MAIN_MENUFRONT_BARRETOP="eldy_frontoffice.php";
		if (empty($this->global->MAIN_MENU_SMARTPHONE)) $this->global->MAIN_MENU_SMARTPHONE="iphone_backoffice.php";
		if (empty($this->global->MAIN_MENUFRONT_SMARTPHONE)) $this->global->MAIN_MENUFRONT_SMARTPHONE="iphone_frontoffice.php";

		// Variable globales LDAP
		if (empty($this->global->LDAP_FIELD_FULLNAME)) $this->global->LDAP_FIELD_FULLNAME='';
		if (! isset($this->global->LDAP_KEY_USERS)) $this->global->LDAP_KEY_USERS=$this->global->LDAP_FIELD_FULLNAME;
		if (! isset($this->global->LDAP_KEY_GROUPS)) $this->global->LDAP_KEY_GROUPS=$this->global->LDAP_FIELD_FULLNAME;
		if (! isset($this->global->LDAP_KEY_CONTACTS)) $this->global->LDAP_KEY_CONTACTS=$this->global->LDAP_FIELD_FULLNAME;
		if (! isset($this->global->LDAP_KEY_MEMBERS)) $this->global->LDAP_KEY_MEMBERS=$this->global->LDAP_FIELD_FULLNAME;

		// Load translation object with current language
		if (empty($this->global->MAIN_LANG_DEFAULT)) $this->global->MAIN_LANG_DEFAULT="en_US";

        // By default, we repeat info on all tabs
		if (! isset($this->global->MAIN_REPEATCONTACTONEACHTAB)) $this->global->MAIN_REPEATCONTACTONEACHTAB=1;
        //if (! isset($this->global->MAIN_REPEATTASKONEACHTAB)) $this->global->MAIN_REPEATTASKONEACHTAB=1; No more required as we have now an agenda tab

		$rootfordata = DOL_DATA_ROOT;
		$rootforuser = DOL_DATA_ROOT;
		// If multicompany module is enabled, we redefine the root of data
		if (! empty($this->global->MAIN_MODULE_MULTICOMPANY) && ! empty($this->entity) && $this->entity > 1) $rootfordata.='/'.$this->entity;

		// For backward compatibility
		// TODO Replace this->xxx->enabled by this->modulename->enabled to remove this code
		$this->compta->enabled=defined("MAIN_MODULE_COMPTABILITE")?MAIN_MODULE_COMPTABILITE:0;
		$this->webcal->enabled=defined('MAIN_MODULE_WEBCALENDAR')?MAIN_MODULE_WEBCALENDAR:0;
		$this->propal->enabled=defined("MAIN_MODULE_PROPALE")?MAIN_MODULE_PROPALE:0;

		// Define default dir_output and dir_temp for directories of modules
		foreach($this->modules as $module)
		{
			if (empty($this->$module->dir_output)) $this->$module->dir_output=$rootfordata."/".$module;
			else $this->$module->dir_output=$rootfordata.$this->$module->dir_output;
			//print 'this->'.$module.'->dir_output='.$this->$module->dir_output.'<br>';
			if (empty($this->$module->dir_temp)) $this->$module->dir_temp=$rootfordata."/".$module."/temp";
			else $this->$module->dir_temp=$rootfordata.$this->$module->dir_temp;
			//print 'this->'.$module.'->dir_temp='.$this->$module->dir_temp.'<br>';
		}

		// For mycompany setup
		$this->mycompany->dir_output=$rootfordata."/mycompany";
		$this->mycompany->dir_temp=$rootfordata."/mycompany/temp";

		// For admin features
		$this->admin->dir_output=$rootfordata.'/admin';
		$this->admin->dir_temp=$rootfordata.'/admin/temp';

		// Module user
		$this->user->dir_output=$rootforuser."/users";
		$this->user->dir_temp=$rootforuser."/users/temp";

		// Exception: Some dir are not the name of module. So we keep exception here
		// for backward compatibility.

		// Module RSS
		$this->externalrss->dir_output=$rootfordata."/rss";
		$this->externalrss->dir_temp=$rootfordata."/rss/temp";

		// Sous module bons d'expedition
		$this->expedition_bon->enabled=defined("MAIN_SUBMODULE_EXPEDITION")?MAIN_SUBMODULE_EXPEDITION:0;
		// Sous module bons de livraison
		$this->livraison_bon->enabled=defined("MAIN_SUBMODULE_LIVRAISON")?MAIN_SUBMODULE_LIVRAISON:0;

		// Module fournisseur
		$this->fournisseur->commande->dir_output=$rootfordata."/fournisseur/commande";
		$this->fournisseur->commande->dir_temp  =$rootfordata."/fournisseur/commande/temp";
		$this->fournisseur->facture->dir_output =$rootfordata."/fournisseur/facture";
		$this->fournisseur->facture->dir_temp   =$rootfordata."/fournisseur/facture/temp";
		// Module product/service
		$this->product->dir_output=$rootfordata."/produit";
		$this->product->dir_temp  =$rootfordata."/produit/temp";
		$this->service->dir_output=$rootfordata."/produit";
		$this->service->dir_temp  =$rootfordata."/produit/temp";
		// Module contrat
		$this->contrat->dir_output=$rootfordata."/contracts";
		$this->contrat->dir_temp=$rootfordata."/contracts/temp";
		// Module webcal
		$this->webcal->db->type=defined('PHPWEBCALENDAR_TYPE')?PHPWEBCALENDAR_TYPE:'__dolibarr_main_db_type__';
		$this->webcal->db->host=defined('PHPWEBCALENDAR_HOST')?PHPWEBCALENDAR_HOST:'';
		$this->webcal->db->port=defined('PHPWEBCALENDAR_PORT')?PHPWEBCALENDAR_PORT:'';
		$this->webcal->db->user=defined('PHPWEBCALENDAR_USER')?PHPWEBCALENDAR_USER:'';
		$this->webcal->db->pass=defined('PHPWEBCALENDAR_PASS')?PHPWEBCALENDAR_PASS:'';
		$this->webcal->db->name=defined('PHPWEBCALENDAR_DBNAME')?PHPWEBCALENDAR_DBNAME:'';
		// Module phenix
		$this->phenix->db->type=defined('PHPPHENIX_TYPE')?PHPPHENIX_TYPE:'__dolibarr_main_db_type__';
		$this->phenix->db->host=defined('PHPPHENIX_HOST')?PHPPHENIX_HOST:'';
		$this->phenix->db->port=defined('PHPPHENIX_PORT')?PHPPHENIX_PORT:'';
		$this->phenix->db->user=defined('PHPPHENIX_USER')?PHPPHENIX_USER:'';
		$this->phenix->db->pass=defined('PHPPHENIX_PASS')?PHPPHENIX_PASS:'';
		$this->phenix->db->name=defined('PHPPHENIX_DBNAME')?PHPPHENIX_DBNAME:'';
		$this->phenix->cookie=defined('PHPPHENIX_COOKIE')?PHPPHENIX_COOKIE:'';
		// Module mantis
		$this->mantis->db->type=defined('PHPMANTIS_TYPE')?PHPMANTIS_TYPE:'__dolibarr_main_db_type__';
		$this->mantis->db->host=defined('PHPMANTIS_HOST')?PHPMANTIS_HOST:'';
		$this->mantis->db->port=defined('PHPMANTIS_PORT')?PHPMANTIS_PORT:'';
		$this->mantis->db->user=defined('PHPMANTIS_USER')?PHPMANTIS_USER:'';
		$this->mantis->db->pass=defined('PHPMANTIS_PASS')?PHPMANTIS_PASS:'';
		$this->mantis->db->name=defined('PHPMANTIS_DBNAME')?PHPMANTIS_DBNAME:'';
		// Module oscommerce 1
		$this->boutique->livre->enabled=defined("BOUTIQUE_LIVRE")?BOUTIQUE_LIVRE:0;
		$this->boutique->album->enabled=defined("BOUTIQUE_ALBUM")?BOUTIQUE_ALBUM:0;


		/*
		 * Set some default values
		 */

		// societe
		if (empty($this->global->SOCIETE_CODECLIENT_ADDON))      $this->global->SOCIETE_CODECLIENT_ADDON="mod_codeclient_leopard";
		if (empty($this->global->SOCIETE_CODEFOURNISSEUR_ADDON)) $this->global->SOCIETE_CODEFOURNISSEUR_ADDON=$this->global->SOCIETE_CODECLIENT_ADDON;
		if (empty($this->global->SOCIETE_CODECOMPTA_ADDON))      $this->global->SOCIETE_CODECOMPTA_ADDON="mod_codecompta_panicum";

		// securite
		if (empty($this->global->USER_PASSWORD_GENERATED)) $this->global->USER_PASSWORD_GENERATED='standard';

		// conf->box_max_lines
		$this->box_max_lines=5;
		if (isset($this->global->MAIN_BOXES_MAXLINES)) $this->box_max_lines=$this->global->MAIN_BOXES_MAXLINES;

		// conf->use_preview_tabs
		$this->use_preview_tabs=0;
		if (isset($this->global->MAIN_USE_PREVIEW_TABS)) $this->use_preview_tabs=$this->global->MAIN_USE_PREVIEW_TABS;

		// conf->use_javascript_ajax
		$this->use_javascript_ajax=1;
		if (isset($this->global->MAIN_DISABLE_JAVASCRIPT)) $this->use_javascript_ajax=! $this->global->MAIN_DISABLE_JAVASCRIPT;
		// If no javascript_ajax, Ajax features are disabled.
		if (! $this->use_javascript_ajax)
		{
			$this->global->PRODUIT_USE_SEARCH_TO_SELECT=0;
			$this->global->MAIN_CONFIRM_AJAX=0;
		}

		// conf->use_popup_calendar
		$this->use_popup_calendar="";	// Pas de date popup par defaut
		if (isset($this->global->MAIN_POPUP_CALENDAR)) $this->use_popup_calendar=$this->global->MAIN_POPUP_CALENDAR;

		// conf->monnaie
		if (empty($this->global->MAIN_MONNAIE)) $this->global->MAIN_MONNAIE='EUR';
		$this->monnaie=$this->global->MAIN_MONNAIE;	// TODO deprecated
		$this->currency=$this->global->MAIN_MONNAIE;

		// $this->compta->mode = Option du module Comptabilite (simple ou expert):
		// Defini le mode de calcul des etats comptables (CA,...)
		$this->compta->mode = 'RECETTES-DEPENSES';  // By default
		if (isset($this->global->COMPTA_MODE)) {
			// Peut etre 'RECETTES-DEPENSES' ou 'CREANCES-DETTES'
			$this->compta->mode = $this->global->COMPTA_MODE;
		}

		// $this->defaulttx
		if (isset($this->global->FACTURE_TVAOPTION) && $this->global->FACTURE_TVAOPTION == 'franchise')
		{
			$this->defaulttx='0';		// Taux par defaut des factures clients
		}
		else {
			$this->defaulttx='';		// Pas de taux par defaut des factures clients, le plus élevé sera pris
		}

		// $this->liste_limit = constante de taille maximale des listes
		if (empty($this->global->MAIN_SIZE_LISTE_LIMIT)) $this->global->MAIN_SIZE_LISTE_LIMIT=25;
		$this->liste_limit=$this->global->MAIN_SIZE_LISTE_LIMIT;

		// $this->product->limit_size = constante de taille maximale des select de produit
		if (! isset($this->global->PRODUIT_LIMIT_SIZE)) $this->global->PRODUIT_LIMIT_SIZE=100;
		$this->product->limit_size=$this->global->PRODUIT_LIMIT_SIZE;

		// $this->theme et $this->css
		if (empty($this->global->MAIN_THEME)) $this->global->MAIN_THEME="eldy";
		$this->theme=$this->global->MAIN_THEME;
		$this->css  = "/theme/".$this->theme."/style.css.php";

		// $this->email_from = email pour envoi par dolibarr des mails automatiques
		$this->email_from = "dolibarr-robot@domain.com";
		if (! empty($this->global->MAIN_MAIL_EMAIL_FROM))
		{
			$this->email_from = $this->global->MAIN_MAIL_EMAIL_FROM;
		}
		// $this->notification->email_from = email pour envoi par Dolibarr des notifications
		$this->notification->email_from=$this->email_from;
		if (! empty($this->global->NOTIFICATION_EMAIL_FROM))
		{
			$this->notification->email_from=$this->global->NOTIFICATION_EMAIL_FROM;
		}

		// $this->mailing->email_from = email pour envoi par Dolibarr des mailings
		$this->mailing->email_from=$this->email_from;;
		if (! empty($this->global->MAILING_EMAIL_FROM))
		{
			$this->mailing->email_from=$this->global->MAILING_EMAIL_FROM;
		}

		// Defini MAIN_GRAPH_LIBRARY
		if (empty($this->global->MAIN_GRAPH_LIBRARY))
		{
			$this->global->MAIN_GRAPH_LIBRARY = 'artichow';
		}

        // Format for date (used by default when not found or searched in lang)
        $this->format_date_short="%d/%m/%Y";            # Format of day with PHP/C tags (strftime functions)
        $this->format_date_short_java="dd/MM/yyyy";     # Format of day with Java tags
        $this->format_hour_short="%H:%M";
        $this->format_hour_short_duration="%H:%M";
        $this->format_date_text_short="%d %b %Y";
        $this->format_date_text="%d %B %Y";
        $this->format_date_hour_short="%d/%m/%Y %H:%M";
        $this->format_date_hour_text_short="%d %b %Y %H:%M";
        $this->format_date_hour_text="%d %B %Y %H:%M";

		// Limites decimales si non definie (peuvent etre egale a 0)
		if (! isset($this->global->MAIN_MAX_DECIMALS_UNIT))  $this->global->MAIN_MAX_DECIMALS_UNIT=5;
		if (! isset($this->global->MAIN_MAX_DECIMALS_TOT))   $this->global->MAIN_MAX_DECIMALS_TOT=2;
		if (! isset($this->global->MAIN_MAX_DECIMALS_SHOWN)) $this->global->MAIN_MAX_DECIMALS_SHOWN=8;

		// Define umask
		if (empty($this->global->MAIN_UMASK)) $this->global->MAIN_UMASK='0664';

		// Set default variable to calculate VAT as if option tax_mode was 0 (standard)
        if (empty($this->global->TAX_MODE_SELL_PRODUCT)) $this->global->TAX_MODE_SELL_PRODUCT='invoice';
        if (empty($this->global->TAX_MODE_BUY_PRODUCT))  $this->global->TAX_MODE_BUY_PRODUCT='invoice';
        if (empty($this->global->TAX_MODE_SELL_SERVICE)) $this->global->TAX_MODE_SELL_SERVICE='payment';
        if (empty($this->global->TAX_MODE_BUY_SERVICE))  $this->global->TAX_MODE_BUY_SERVICE='payment';

		/* We always show vat menus if module tax is enabled.
		 * Because even when vat option is 'franchise' and vat rate is 0, we have to pay vat.
		 */
		$this->compta->tva=1; // This option means "Show vat menus"

		// Delay before warnings
		$this->actions->warning_delay=(isset($this->global->MAIN_DELAY_ACTIONS_TODO)?$this->global->MAIN_DELAY_ACTIONS_TODO:7)*24*60*60;
		$this->commande->client->warning_delay=(isset($this->global->MAIN_DELAY_ORDERS_TO_PROCESS)?$this->global->MAIN_DELAY_ORDERS_TO_PROCESS:2)*24*60*60;
        $this->commande->fournisseur->warning_delay=(isset($this->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS)?$this->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS:7)*24*60*60;
		$this->propal->cloture->warning_delay=(isset($this->global->MAIN_DELAY_PROPALS_TO_CLOSE)?$this->global->MAIN_DELAY_PROPALS_TO_CLOSE:0)*24*60*60;
		$this->propal->facturation->warning_delay=(isset($this->global->MAIN_DELAY_PROPALS_TO_BILL)?$this->global->MAIN_DELAY_PROPALS_TO_BILL:0)*24*60*60;
		$this->facture->client->warning_delay=(isset($this->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED)?$this->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED:0)*24*60*60;
        $this->facture->fournisseur->warning_delay=(isset($this->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY)?$this->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY:0)*24*60*60;
		$this->contrat->services->inactifs->warning_delay=(isset($this->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES)?$this->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES:0)*24*60*60;
		$this->contrat->services->expires->warning_delay=(isset($this->global->MAIN_DELAY_RUNNING_SERVICES)?$this->global->MAIN_DELAY_RUNNING_SERVICES:0)*24*60*60;
		$this->adherent->cotisation->warning_delay=(isset($this->global->MAIN_DELAY_MEMBERS)?$this->global->MAIN_DELAY_MEMBERS:0)*24*60*60;
		$this->bank->rappro->warning_delay=(isset($this->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE)?$this->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE:0)*24*60*60;
		$this->bank->cheque->warning_delay=(isset($this->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT)?$this->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT:0)*24*60*60;

		// For backward compatibility
		$this->produit=$this->product;
	}

}

?>
