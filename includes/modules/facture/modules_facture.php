<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/includes/modules/facture/modules_facture.php
 *	\ingroup    facture
 *	\brief      Fichier contenant la classe mere de generation des factures en PDF
 * 				et la classe mere de numerotation des factures
 *	\version    $Id: modules_facture.php,v 1.82 2010/12/22 20:17:14 eldy Exp $
 */

require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");   // Requis car utilise dans les classes qui heritent
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');
//require_once(DOL_DOCUMENT_ROOT.'/includes/tcpdf/tcpdf.php');


/**
 *	\class      ModelePDFFactures
 *	\brief      Classe mere des modeles de facture
 */
class ModelePDFFactures
{
	var $error='';

	/**
	 *      \brief      Return list of active generation modules
	 * 		\param		$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='invoice';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,'');

		return $liste;
	}
}

/**
 *	\class      ModeleNumRefFactures
 *	\brief      Classe mere des modeles de numerotation des references de facture
 */
class ModeleNumRefFactures
{
	var $error='';

	/**     \brief     	Return if a module can be used or not
	 *      	\return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**		\brief		Renvoi la description par defaut du modele de numerotation
	 *      	\return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans("NoDescription");
	}

	/**     \brief     	Renvoi un exemple de numerotation
	 *		\return		string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans("NoExample");
	}

	/**     \brief     	Test si les numeros deja en vigueur dans la base ne provoquent pas
	 *                  	de conflits qui empecheraient cette numerotation de fonctionner.
	 *      	\return		boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**     \brief      Renvoi prochaine valeur attribuee
	 *      	\param      objsoc		Objet societe
	 *      	\param      facture		Objet facture
	 *      	\return     string      Valeur
	 */
	function getNextValue($objsoc,$facture)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**     \brief      Renvoi version du module numerotation
	 *      	\return     string      Valeur
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		return $langs->trans("NotAvailable");
	}
}


/**
 *	Cree un facture sur disque en fonction du modele de FACTURE_ADDON_PDF
 *	@param   	db  			objet base de donnee
 *	@param   	object			Object invoice
 *	@param	    message			message
 *	@param	    modele			force le modele a utiliser ('' to not force)
 *	@param		outputlangs		objet lang a utiliser pour traduction
 *	@return  	int        		<0 if KO, >0 if OK
 */
function facture_pdf_create($db, $object, $message, $modele, $outputlangs)
{
	global $conf,$langs;
	$langs->load("bills");

	// Increase limit for PDF build
    $err=error_reporting();
    error_reporting(0);
    @set_time_limit(120);
    error_reporting($err);

	$dir = DOL_DOCUMENT_ROOT . "/includes/modules/facture/";

	// Positionne modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
		if (! empty($conf->global->FACTURE_ADDON_PDF))
		{
			$modele = $conf->global->FACTURE_ADDON_PDF;
		}
		else
		{
			//print $langs->trans("Error")." ".$langs->trans("Error_FACTURE_ADDON_PDF_NotDefined");
			//return 0;
			$modele = 'crabe';
		}
	}

	// Charge le modele
	$file = "pdf_".$modele.".modules.php";
	if (file_exists($dir.$file))
	{
		$classname = "pdf_".$modele;
		require_once($dir.$file);

		$obj = new $classname($db);
		$obj->message = $message;

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object, $outputlangs) > 0)
		{
			// Success in building document. We build meta file.
			facture_meta_create($db, $object->id);
			// et on supprime l'image correspondant au preview
			facture_delete_preview($db, $object->id);

			$outputlangs->charset_output=$sav_charset_output;
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"facture_pdf_create Error: ".$obj->error);
			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file));
		return -1;
	}
}

/**
 *	Create a meta file with document file into same directory.
 *  This should allow rgrep search.
 *	@param	    db  		Objet base de donnee
 *	@param	    facid		Id de la facture a creer
 *	@param      message     Message
 */
function facture_meta_create($db, $facid, $message="")
{
	global $langs,$conf;

	$fac = new Facture($db,"",$facid);
	$fac->fetch($facid);
	$fac->fetch_thirdparty();

	if ($conf->facture->dir_output)
	{
		$facref = dol_sanitizeFileName($fac->ref);
		$dir = $conf->facture->dir_output . "/" . $facref ;
		$file = $dir . "/" . $facref . ".meta";

		if (! is_dir($dir))
		{
			create_exdir($dir);
		}

		if (is_dir($dir))
		{
			$nblignes = sizeof($fac->lines);
			$client = $fac->client->nom . " " . $fac->client->address . " " . $fac->client->cp . " " . $fac->client->ville;
			$meta = "REFERENCE=\"" . $fac->ref . "\"
			DATE=\"" . dol_print_date($fac->date,'') . "\"
			NB_ITEMS=\"" . $nblignes . "\"
			CLIENT=\"" . $client . "\"
			TOTAL_HT=\"" . $fac->total_ht . "\"
			TOTAL_TTC=\"" . $fac->total_ttc . "\"\n";

			for ($i = 0 ; $i < $nblignes ; $i++)
			{
				//Pour les articles
				$meta .= "ITEM_" . $i . "_QUANTITY=\"" . $fac->lines[$i]->qty . "\"
				ITEM_" . $i . "_UNIT_PRICE=\"" . $fac->lines[$i]->price . "\"
				ITEM_" . $i . "_TVA=\"" .$fac->lines[$i]->tva_tx . "\"
				ITEM_" . $i . "_DESCRIPTION=\"" . str_replace("\r\n","",nl2br($fac->lines[$i]->desc)) . "\"
				";
			}
		}
		$fp = fopen ($file,"w");
		fputs($fp,$meta);
		fclose($fp);
		if (! empty($conf->global->MAIN_UMASK))
		@chmod($file, octdec($conf->global->MAIN_UMASK));
	}
}


/**
 *	\brief      Supprime l'image de previsualitation, pour le cas de regeneration de facture
 *	\param	    db  		objet base de donnee
 *	\param	    facid		id de la facture a creer
 */
function facture_delete_preview($db, $facid)
{
	global $langs,$conf;

	$fac = new Facture($db,"",$facid);
	$fac->fetch($facid);

	if ($conf->facture->dir_output)
	{
		$facref = dol_sanitizeFileName($fac->ref);
		$dir = $conf->facture->dir_output . "/" . $facref ;
		$file = $dir . "/" . $facref . ".pdf.png";

		if ( file_exists( $file ) && is_writable( $file ) )
		{
			if ( ! dol_delete_file($file,1) )
			{
				return 0;
			}
		}
	}

	return 1;
}

?>