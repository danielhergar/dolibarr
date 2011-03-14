<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2011 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010      Juanjo Menent         <jmenent@2byte.es>
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
 *	\file       	htdocs/comm/propal.php
 *	\ingroup    	propale
 *	\brief      	Page of commercial proposals card and list
 *	\version		$Id: propal.php,v 1.563.2.1 2011/01/29 16:30:41 hregis Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
require_once(DOL_DOCUMENT_ROOT."/includes/modules/propale/modules_propale.php");
require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/propal.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');

$langs->load('companies');
$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');

$sall=isset($_GET["sall"])?$_GET["sall"]:$_POST["sall"];
if (isset($_GET["msg"])) { $mesg=$_GET["mesg"]; }
$year=isset($_GET["year"])?$_GET["year"]:"";
$month=isset($_GET["month"])?$_GET["month"]:"";
$socid=isset($_GET['socid'])?$_GET['socid']:$_POST['socid'];
$mesg=isset($_GET['mesg'])?$_GET['mesg']:'';

// Security check
$module='propale';
if (isset($_GET["socid"]))
{
	$objectid=$_GET["socid"];
	$module='societe';
	$dbtable='';
}
else if (isset($_GET["id"]) &&  $_GET["id"] > 0)
{
	$objectid=$_GET["id"];
	$module='propale';
	$dbtable='propal';
}
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, $module, $objectid, $dbtable);

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES=4;

$object = new Propal($db);

// Instantiate hooks of thirdparty module
if (is_array($conf->hooks_modules) && !empty($conf->hooks_modules))
{
	$object->callHooks('objectcard');
}


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

// Hook of thirdparty module
if (! empty($object->hooks))
{
	foreach($object->hooks as $module)
	{
		$module->doActions($object);
		$mesg = $module->error;
	}
}

// Action clone object
if ($_REQUEST["action"] == 'confirm_clone' && $_REQUEST['confirm'] == 'yes')
{
	if (1==0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
	{
		$mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
	}
	else
	{
		$result=$object->createFromClone($_REQUEST["id"]);
		if ($result > 0)
		{
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
			exit;
		}
		else
		{
			$mesg=$object->error;
			$_GET['action']='';
			$_GET['id']=$_REQUEST['id'];
		}
	}
}

// Suppression de la propale
if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes')
{
	if ($user->rights->propale->supprimer)
	{
		$object->fetch($_GET["id"]);
		$result=$object->delete($user);
		$id = 0;
		$brouillon = 1;

		if ($result > 0)
		{
			Header('Location: '.$_SERVER["PHP_SELF"]);
			exit;
		}
		else
		{
			$langs->load("errors");
			if ($object->error == 'ErrorFailToDeleteDir') $mesg='<div class="error">'.$langs->trans('ErrorFailedToDeleteJoinedFiles').'</div>';
			else $mesg='<div class="error">'.$object->error.'</div>';
		}
	}
}

// Remove line
if ($_REQUEST['action'] == 'confirm_deleteline' && $_REQUEST['confirm'] == 'yes')
{
	if ($user->rights->propale->creer)
	{
		$object->fetch($_GET["id"]);
		$object->fetch_thirdparty();
		$result = $object->deleteline($_GET['lineid']);
		// reorder lines
		if ($result) $object->line_order(true);

		// Define output language
		$outputlangs = $langs;
		$newlang='';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
		if (! empty($newlang))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($newlang);
		}
		propale_pdf_create($db, $object, $object->modelpdf, $outputlangs);

		Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}

// Validation
if ($_REQUEST['action'] == 'confirm_validate' && $_REQUEST['confirm'] == 'yes' && $user->rights->propale->valider)
{
	$object->fetch($_GET["id"]);
	$object->fetch_thirdparty();

	$result=$object->valid($user);
	if ($result >= 0)
	{
		// Define output language
		$outputlangs = $langs;
		$newlang='';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
		if (! empty($newlang))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($newlang);
		}
		propale_pdf_create($db, $object, $object->modelpdf, $outputlangs);
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}

if ($_POST['action'] == 'setdate')
{
	$object->fetch($_GET["id"]);
	$result=$object->set_date($user,dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']));
	if ($result < 0) dol_print_error($db,$object->error);
}
if ($_POST['action'] == 'setecheance')
{
	$object->fetch($_GET["id"]);
	$result=$object->set_echeance($user,dol_mktime(12, 0, 0, $_POST['echmonth'], $_POST['echday'], $_POST['echyear']));
	if ($result < 0) dol_print_error($db,$object->error);
}
if ($_POST['action'] == 'setdate_livraison')
{
	$object->fetch($_GET["id"]);
	$result=$object->set_date_livraison($user,dol_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']));
	if ($result < 0) dol_print_error($db,$object->error);
}

if ($_POST['action'] == 'setaddress' && $user->rights->propale->creer)
{
	$object->fetch($_GET["id"]);
	$result=$object->set_adresse_livraison($user,$_POST['fk_address']);
	if ($result < 0) dol_print_error($db,$object->error);
}

// Positionne ref client
if ($_POST['action'] == 'set_ref_client' && $user->rights->propale->creer)
{
	$object->fetch($_GET["id"]);
	$object->set_ref_client($user, $_POST['ref_client']);
}

/*
 * Creation propale
 */
if ($_POST['action'] == 'add' && $user->rights->propale->creer)
{
	$object->socid=$_POST['socid'];
	$object->fetch_thirdparty();

	$db->begin();

	// Si on a selectionne une propal a copier, on realise la copie
	if($_POST['createmode']=='copy' && $_POST['copie_propal'])
	{
		if ($object->fetch($_POST['copie_propal']) > 0)
		{
			$object->ref       				= $_POST['ref'];
			$object->datep 					= dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			$object->date_livraison 		= dol_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);
			$object->fk_delivery_address 	= $_POST['fk_address'];
			$object->duree_validite			= $_POST['duree_validite'];
			$object->cond_reglement_id 		= $_POST['cond_reglement_id'];
			$object->mode_reglement_id 		= $_POST['mode_reglement_id'];
			$object->remise_percent 		= $_POST['remise_percent'];
			$object->remise_absolue 		= $_POST['remise_absolue'];
			$object->socid    				= $_POST['socid'];
			$object->contactid 				= $_POST['contactidp'];
			$object->fk_project				= $_POST['projectid'];
			$object->modelpdf  				= $_POST['model'];
			$object->author    				= $user->id;			// deprecated
			$object->note      				= $_POST['note'];
			$object->statut    				= 0;

			$id = $object->create_from($user);
		}
		else
		{
			$mesg = '<div class="error">'.$langs->trans("ErrorFailedToCopyProposal",$_POST['copie_propal']).'</div>';
		}
	}
	else
	{
		$object->ref					= $_POST['ref'];
		$object->ref_client 			= $_POST['ref_client'];
		$object->datep 					= dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		$object->date_livraison 		= dol_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);
		$object->fk_delivery_address 	= $_POST['fk_address'];
		$object->duree_validite 		= $_POST['duree_validite'];
		$object->cond_reglement_id 		= $_POST['cond_reglement_id'];
		$object->mode_reglement_id 		= $_POST['mode_reglement_id'];

		$object->contactid  = $_POST['contactidp'];
		$object->fk_project = $_POST['projectid'];
		$object->modelpdf   = $_POST['model'];
		$object->author     = $user->id;		// deprecated
		$object->note       = $_POST['note'];

		$object->origin		= $_POST['origin'];
		$object->origin_id	= $_POST['originid'];

		for ($i = 1 ; $i <= $conf->global->PRODUCT_SHOW_WHEN_CREATE; $i++)
		{
			if ($_POST['idprod'.$i])
			{
				$xid = 'idprod'.$i;
				$xqty = 'qty'.$i;
				$xremise = 'remise'.$i;
				$object->add_product($_POST[$xid],$_POST[$xqty],$_POST[$xremise]);
			}
		}

		$id = $object->create($user);
	}

	if ($id > 0)
	{
		$error=0;

		// Insertion contact par defaut si defini
		if ($_POST["contactidp"])
		{
			$result=$object->add_contact($_POST["contactidp"],'CUSTOMER','external');

			if ($result > 0)
			{
				$error=0;
			}
			else
			{
				$mesg = '<div class="error">'.$langs->trans("ErrorFailedToAddContact").'</div>';
				$error=1;
			}
		}

		if (! $error)
		{
			$db->commit();

			// Define output language
			$outputlangs = $langs;
			$newlang='';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
			if (! empty($newlang))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($newlang);
			}
			propale_pdf_create($db, $object, $object->modelpdf, $outputlangs);

			Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
			exit;
		}
		else
		{
			$db->rollback();
		}
	}
	else
	{
		dol_print_error($db,$object->error);
		$db->rollback();
		exit;
	}
}

// Classify billed
if ($_GET["action"] == 'classifybilled')
{
	$object->fetch($_GET["id"]);
	$object->cloture($user, 4, '');
}

/*
 *  Cloture de la propale
 */
if ($_REQUEST['action'] == 'setstatut' && $user->rights->propale->cloturer)
{
	if (! $_POST['cancel'])
	{
		if (empty($_REQUEST['statut']))
		{
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("CloseAs")).'</div>';
			$_REQUEST['action']='statut';
			$_GET['action']='statut';
		}
		else
		{
			$object->fetch($_GET["id"]);
			// prevent browser refresh from closing proposal several times
			if ($object->statut==1)
			{
				$object->cloture($user, $_REQUEST['statut'], $_REQUEST['note']);
			}
		}
	}
}


/*
 * Add file in email form
 */
if ($_POST['addfile'])
{
	require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

	// Set tmp user directory TODO Use a dedicated directory for temp mails files
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir = $vardir.'/temp/';

	$mesg=dol_add_file_process($upload_dir,0,0);

	$_GET["action"]='presend';
	$_POST["action"]='presend';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']))
{
	require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir = $vardir.'/temp/';

	$mesg=dol_remove_file_process($_POST['removedfile'],0);

	$_GET["action"]='presend';
	$_POST["action"]='presend';
}

/*
 * Send mail
 */
if ($_POST['action'] == 'send' && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
{
	$langs->load('mails');

	$result=$object->fetch($_POST["id"]);
	$result=$object->fetch_thirdparty();

	if ($result > 0)
	{
		$objectref = dol_sanitizeFileName($object->ref);
		$file = $conf->propale->dir_output . '/' . $objectref . '/' . $objectref . '.pdf';

		if (is_readable($file))
		{
			if ($_POST['sendto'])
			{
				// Le destinataire a ete fourni via le champ libre
				$sendto = $_POST['sendto'];
				$sendtoid = 0;
			}
			elseif ($_POST['receiver'])
			{
				// Le destinataire a ete fourni via la liste deroulante
				if ($_POST['receiver'] < 0)	// Id du tiers
				{
					$sendto = $object->client->email;
					$sendtoid = 0;
				}
				else	// Id du contact
				{
					$sendto = $object->client->contact_get_email($_POST['receiver']);
					$sendtoid = $_POST['receiver'];
				}
			}

			if (dol_strlen($sendto))
			{
				$langs->load("commercial");

				$from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
				$replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
				$message = $_POST['message'];
				$sendtocc = $_POST['sendtocc'];
				$deliveryreceipt = $_POST['deliveryreceipt'];

				if ($_POST['action'] == 'send')
				{
					if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
					else $subject = $langs->transnoentities('Propal').' '.$object->ref;
					$actiontypecode='AC_PROP';
					$actionmsg = $langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
					if ($message)
					{
						$actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
						$actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
						$actionmsg.=$message;
					}
					$actionmsg2=$langs->transnoentities('Action'.$actiontypecode);
				}

				// Create form object
				include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
				$formmail = new FormMail($db);

				$attachedfiles=$formmail->get_attached_files();
				$filepath = $attachedfiles['paths'];
				$filename = $attachedfiles['names'];
				$mimetype = $attachedfiles['mimes'];

				// Envoi de la propal
				require_once(DOL_DOCUMENT_ROOT.'/lib/CMailFile.class.php');
				$mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt);
				if ($mailfile->error)
				{
					$mesg='<div class="error">'.$mailfile->error.'</div>';
				}
				else
				{
					$result=$mailfile->sendfile();
					if ($result)
					{
						$mesg=$langs->trans('MailSuccessfulySent',$from,$sendto);	// Must not contain "

						$error=0;

						// Initialisation donnees
						$object->sendtoid=$sendtoid;
						$object->actiontypecode=$actiontypecode;
						$object->actionmsg = $actionmsg;
						$object->actionmsg2= $actionmsg2;
						$object->propalrowid=$object->id;

						// Appel des triggers
						include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
						$interface=new Interfaces($db);
						$result=$interface->run_triggers('PROPAL_SENTBYMAIL',$object,$user,$langs,$conf);
						if ($result < 0) { $error++; $this->errors=$interface->errors; }
						// Fin appel triggers

						if ($error)
						{
							dol_print_error($db);
						}
						else
						{
							// Redirect here
							// This avoid sending mail twice if going out and then back to page
							Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&mesg='.urlencode($mesg));
							exit;
						}
					}
					else
					{
						$langs->load("other");
						$mesg='<div class="error">';
						if ($mailfile->error)
						{
							$mesg.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
							$mesg.='<br>'.$mailfile->error;
						}
						else
						{
							$mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
						}
						$mesg.='</div>';
					}
				}
			}
			else
			{
				$langs->load("other");
				$mesg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').' !</div>';
				dol_syslog('Recipient email is empty');
			}
		}
		else
		{
			$langs->load("other");
			$mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
			dol_syslog('Failed to read file: '.$file);
		}
	}
	else
	{
		$langs->load("other");
		$mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Proposal")).'</div>';
		dol_syslog('Impossible de lire les donnees de la facture. Le fichier propal n\'a peut-etre pas ete genere.');
	}
}

if ($_GET['action'] == 'modif' && $user->rights->propale->creer)
{
	/*
	 *  Repasse la propale en mode brouillon
	 */
	$object->fetch($_GET["id"]);
	$object->fetch_thirdparty();
	$object->set_draft($user);

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}

	propale_pdf_create($db, $object, $object->modelpdf, $outputlangs);
}

if ($_POST['action'] == "setabsolutediscount" && $user->rights->propale->creer)
{
	if ($_POST["remise_id"])
	{
		$object->id=$_GET["id"];
		$ret=$object->fetch($_GET["id"]);
		if ($ret > 0)
		{
			$result=$object->insert_discount($_POST["remise_id"]);
			if ($result < 0)
			{
				$mesg='<div class="error">'.$object->error.'</div>';
			}
		}
		else
		{
			dol_print_error($db,$object->error);
		}
	}
}

/*
 *  Ajout d'une ligne produit dans la propale
 */
if ($_POST['action'] == "addline" && $user->rights->propale->creer)
{
	$result=0;

	if (empty($_POST['idprod']) && $_POST["type"] < 0)
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")).'</div>';
		$result = -1 ;
	}
	if (empty($_POST['idprod']) && (! isset($_POST["np_price"]) || $_POST["np_price"]==''))	// Unit price can be 0 but not ''
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("UnitPriceHT")).'</div>';
		$result = -1 ;
	}

	if ($result >= 0 && isset($_POST['qty']) && (($_POST['np_price']!='' && ($_POST['np_desc'] || $_POST['dp_desc'])) || $_POST['idprod']))
	{
		$ret=$object->fetch($_POST["id"]);
		if ($ret < 0)
		{
			dol_print_error($db,$object->error);
			exit;
		}
		$ret=$object->fetch_thirdparty();

		$price_base_type = 'HT';

		// Ecrase $pu par celui du produit
		// Ecrase $desc par celui du produit
		// Ecrase $txtva par celui du produit
		if ($_POST['idprod'])
		{
			$prod = new Product($db, $_POST['idprod']);
			$prod->fetch($_POST['idprod']);

			$tva_tx = get_default_tva($mysoc,$object->client,$prod->id);
			$localtax1_tx= get_localtax($tva_tx, 1, $object->client);  //get_default_localtax($mysoc,$object->client,1,$prod->id);
			$localtax2_tx= get_localtax($tva_tx, 2, $object->client); //get_default_localtax($mysoc,$object->client,2,$prod->id);
			$tva_npr = get_default_npr($mysoc,$object->client,$prod->id);

			// On defini prix unitaire
			if ($conf->global->PRODUIT_MULTIPRICES && $object->client->price_level)
			{
				$pu_ht  = $prod->multiprices[$object->client->price_level];
				$pu_ttc = $prod->multiprices_ttc[$object->client->price_level];
				$price_min = $prod->multiprices_min[$object->client->price_level];
				$price_base_type = $prod->multiprices_base_type[$object->client->price_level];
			}
			else
			{
				$pu_ht = $prod->price;
				$pu_ttc = $prod->price_ttc;
				$price_min = $prod->price_min;
				$price_base_type = $prod->price_base_type;
			}

			// On reevalue prix selon taux tva car taux tva transaction peut etre different
			// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
			if ($tva_tx != $prod->tva_tx)
			{
				if ($price_base_type != 'HT')
				{
					$pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
				}
				else
				{
					$pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
				}
			}

			$desc = $prod->description;
			$desc.= ($prod->description && $_POST['np_desc']) ? "\n" : "";
			$desc.= $_POST['np_desc'];
			$type = $prod->type;
		}
		else
		{
			$pu_ht=$_POST['np_price'];
			$tva_tx=str_replace('*','',$_POST['np_tva_tx']);
			$tva_npr=preg_match('/\*/',$_POST['np_tva_tx'])?1:0;
			$desc=$_POST['dp_desc'];
			$type=$_POST["type"];
			$localtax1_tx=get_localtax($tva_tx,1,$object->client);
			$localtax2_tx=get_localtax($tva_tx,2,$object->client);
		}

		$info_bits=0;
		if ($tva_npr) $info_bits |= 0x01;

		if ($price_min && (price2num($pu_ht)*(1-price2num($_POST['remise_percent'])/100) < price2num($price_min)))
		{
			$mesg = '<div class="error">'.$langs->trans("CantBeLessThanMinPrice",price2num($price_min,'MU').' '.$langs->trans("Currency".$conf->monnaie)).'</div>' ;
		}
		else
		{
			// Insert line
			$result=$object->addline(
			$_POST["id"],
			$desc,
			$pu_ht,
			$_POST['qty'],
			$tva_tx,
			$localtax1_tx,
			$localtax2_tx,
			$_POST['idprod'],
			$_POST['remise_percent'],
			$price_base_type,
			$pu_ttc,
			$info_bits,
			$type
			);

			if ($result > 0)
			{
				// Define output language
				$outputlangs = $langs;
				$newlang='';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
				if (! empty($newlang))
				{
					$outputlangs = new Translate("",$conf);
					$outputlangs->setDefaultLang($newlang);
				}
				propale_pdf_create($db, $object, $object->modelpdf, $outputlangs);

				unset($_POST['qty']);
				unset($_POST['type']);
				unset($_POST['np_price']);
				unset($_POST['dp_desc']);
				unset($_POST['np_tva_tx']);
			}
			else
			{
				$mesg='<div class="error">'.$object->error.'</div>';
			}
		}
	}
}

/*
 *  Mise a jour d'une ligne dans la propale
 */
if ($_POST['action'] == 'updateligne' && $user->rights->propale->creer && $_POST["save"] == $langs->trans("Save"))
{
	if (! $object->fetch($_POST["id"]) > 0)
	{
		dol_print_error($db,$object->error);
		exit;
	}
	$object->fetch_thirdparty();

	// Define info_bits
	$info_bits=0;
	if (preg_match('/\*/',$_POST['tva_tx'])) $info_bits |= 0x01;

	// Define vat_rate
	$vat_rate=$_POST['tva_tx'];
	$vat_rate=str_replace('*','',$vat_rate);
	$localtax1_rate=get_localtax($vat_rate,1,$object->client);
	$localtax2_rate=get_localtax($vat_rate,2,$object->client);
    $up_ht=GETPOST('pu')?GETPOST('pu'):GETPOST('subprice');

	// On verifie que le prix minimum est respecte
	$productid = $_POST['productid'] ;
	if ($productid)
	{
		$product = new Product($db) ;
		$res=$product->fetch($productid) ;
		$price_min = $product->price_min;
		if ($conf->global->PRODUIT_MULTIPRICES && $object->client->price_level)	$price_min = $product->multiprices_min[$object->client->price_level];
	}
	if ($productid && $price_min && (price2num($up_ht)*(1-price2num($_POST['remise_percent'])/100) < price2num($price_min)))
	{
		$mesg = '<div class="error">'.$langs->trans("CantBeLessThanMinPrice",price2num($price_min,'MU').' '.$langs->trans("Currency".$conf->monnaie)).'</div>' ;
	}
	else
	{
		$result = $object->updateline($_POST['lineid'],
		$up_ht,
		$_POST['qty'],
		$_POST['remise_percent'],
		$vat_rate,
		$localtax1_rate,
		$localtax2_rate,
		$_POST['desc'],
		'HT',
		$info_bits);

		// Define output language
		$outputlangs = $langs;
		$newlang='';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
		if (! empty($newlang))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($newlang);
		}
		propale_pdf_create($db, $object, $object->modelpdf, $outputlangs);
	}
}

/*
 * Generation doc (depuis lien ou depuis cartouche doc)
 */
if ($_REQUEST['action'] == 'builddoc' && $user->rights->propale->creer)
{
	$object->fetch($_GET["id"]);
	$object->fetch_thirdparty();

	if ($_REQUEST['model'])
	{
		$object->setDocModel($user, $_REQUEST['model']);
	}

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$result=propale_pdf_create($db, $object, $object->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
	else
	{
		Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
		exit;
	}
}

// Set project
if ($_POST['action'] == 'classin')
{
	$object->fetch($_GET['id']);
	$object->setProject($_POST['projectid']);
}

// Conditions de reglement
if ($_POST["action"] == 'setconditions')
{
	$object->fetch($_REQUEST['id']);
	$result = $object->cond_reglement($_POST['cond_reglement_id']);
	$_GET['id']=$_REQUEST['id'];
}

if ($_REQUEST['action'] == 'setremisepercent' && $user->rights->propale->creer)
{
	$object->fetch($_REQUEST["id"]);
	$result = $object->set_remise_percent($user, $_POST['remise_percent']);
	$_GET["id"]=$_REQUEST["id"];
}

if ($_REQUEST['action'] == 'setremiseabsolue' && $user->rights->propale->creer)
{
	$object->fetch($_REQUEST["id"]);
	$result = $object->set_remise_absolue($user, $_POST['remise_absolue']);
	$_GET["id"]=$_REQUEST["id"];
}

// Mode de reglement
if ($_POST["action"] == 'setmode')
{
	$object->fetch($_REQUEST["id"]);
	$result = $object->mode_reglement($_POST['mode_reglement_id']);
	$_GET["id"]=$_REQUEST["id"];
}

/*
 * Ordonnancement des lignes
 */

if ($_GET['action'] == 'up' && $user->rights->propale->creer)
{
	$object->fetch($_GET["id"]);
	$object->fetch_thirdparty();
	$object->line_up($_GET['rowid']);

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	propale_pdf_create($db, $object, $object->modelpdf, $outputlangs);

	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'#'.$_GET['rowid']);
	exit;
}

if ($_GET['action'] == 'down' && $user->rights->propale->creer)
{
	$object->fetch($_GET['id']);
	$object->fetch_thirdparty();
	$object->line_down($_GET['rowid']);

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	propale_pdf_create($db, $object, $object->modelpdf, $outputlangs);

	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'#'.$_GET['rowid']);
	exit;
}


/*
 * View
 */

llxHeader('',$langs->trans('Proposal'),'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos');

$html = new Form($db);
$formfile = new FormFile($db);
$companystatic=new Societe($db);

$now=dol_now();

$id = $_REQUEST['id']?$_REQUEST['id']:$_REQUEST['id'];
$ref= $_REQUEST['ref'];

if ($id > 0 || ! empty($ref))
{
	/*
	 * Show object in view mode
	 */

	if ($mesg) 
	{
		if (! preg_match('/div class=/',$mesg)) print '<div class="ok">'.$mesg.'</div><br>';
		else print $mesg."<br>";
	}

	$object->fetch($id,$ref);

	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$head = propal_prepare_head($object);
	dol_fiche_head($head, 'comm', $langs->trans('Proposal'), 0, 'propal');

	// Clone confirmation
	if ($_GET["action"] == 'clone')
	{
		// Create an array for form
		$formquestion=array(
		//'text' => $langs->trans("ConfirmClone"),
		//array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1)
		);
		// Paiement incomplet. On demande si motif = escompte ou autre
		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('ClonePropal'),$langs->trans('ConfirmClonePropal',$object->ref),'confirm_clone',$formquestion,'yes',1);
		if ($ret == 'html') print '<br>';
	}

	/*
	 * Confirmation de la suppression de la propale
	 */
	if ($_GET['action'] == 'delete')
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteProp'), $langs->trans('ConfirmDeleteProp'), 'confirm_delete','',0,1);
		if ($ret == 'html') print '<br>';
	}

	/*
	 * Confirmation de la suppression d'une ligne produit/service
	 */
	if ($_GET['action'] == 'ask_deleteline')
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$_GET["lineid"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline','',0,1);
		if ($ret == 'html') print '<br>';
	}

	/*
	 * TODO ajout temporaire pour test en attendant la migration en template
	 */
	if ($_GET['action'] == 'ask_deletemilestone')
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$_GET["lineid"], $langs->trans('DeleteMilestone'), $langs->trans('ConfirmDeleteMilestone'), 'confirm_deletemilestone','',0,1);
		if ($ret == 'html') print '<br>';
	}

	/*
	 * Confirmation de la validation de la propale
	 */
	if ($_GET['action'] == 'validate')
	{
		// on verifie si l'objet est en numerotation provisoire
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV')
		{
			$numref = $object->getNextNumRef($soc);
		}
		else
		{
			$numref = $object->ref;
		}

		$text=$langs->trans('ConfirmValidateProp',$numref);
		if ($conf->notification->enabled)
		{
			require_once(DOL_DOCUMENT_ROOT ."/core/class/notify.class.php");
			$notify=new Notify($db);
			$text.='<br>';
			$text.=$notify->confirmMessage('NOTIFY_VAL_PROPAL',$object->socid);
		}

		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateProp'), $text, 'confirm_validate','',0,1);
		if ($ret == 'html') print '<br>';
	}


	/*
	 * Fiche propal
	 *
	 */

	print '<table class="border" width="100%">';

	$linkback="<a href=\"propal.php?page=$page&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder\">".$langs->trans("BackToList")."</a>";

	// Ref
	print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="5">';
	print $html->showrefnav($object,'ref',$linkback,1,'ref','ref','');
	print '</td></tr>';

	// Ref client
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
	print $langs->trans('RefCustomer').'</td><td align="left">';
	print '</td>';
	if ($_GET['action'] != 'refclient' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refclient&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="5">';
	if ($user->rights->propale->creer && $_GET['action'] == 'refclient')
	{
		print '<form action="propal.php?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="set_ref_client">';
		print '<input type="text" class="flat" size="20" name="ref_client" value="'.$object->ref_client.'">';
		print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		print $object->ref_client;
	}
	print '</td>';
	print '</tr>';

	$rowspan=9;

	// Company
	print '<tr><td>'.$langs->trans('Company').'</td><td colspan="5">'.$soc->getNomUrl(1).'</td>';
	print '</tr>';

	// Ligne info remises tiers
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
	if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	print '. ';
	$absolute_discount=$soc->getAvailableDiscounts('','fk_facture_source IS NULL');
	$absolute_creditnote=$soc->getAvailableDiscounts('','fk_facture_source IS NOT NULL');
	$absolute_discount=price2num($absolute_discount,'MT');
	$absolute_creditnote=price2num($absolute_creditnote,'MT');
	if ($absolute_discount)
	{
		if ($object->statut > 0)
		{
			print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->monnaie));
		}
		else
		{
			// Remise dispo de type non avoir
			$filter='fk_facture_source IS NULL';
			print '<br>';
			$html->form_remise_dispo($_SERVER["PHP_SELF"].'?id='.$object->id,0,'remise_id',$soc->id,$absolute_discount,$filter);
		}
	}
	if ($absolute_creditnote)
	{
		print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->monnaie)).'. ';
	}
	if (! $absolute_discount && ! $absolute_creditnote) print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
	print '</td></tr>';

	// Date of proposal
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('Date');
	print '</td>';
	if ($_GET['action'] != 'editdate' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($object->brouillon && $_GET['action'] == 'editdate')
	{
		print '<form name="editdate" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setdate">';
		$html->select_date($object->date,'re','','',0,"editdate");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		if ($object->date)
		{
			print dol_print_date($object->date,'daytext');
		}
		else
		{
			print '&nbsp;';
		}
	}
	print '</td>';

	if ($conf->projet->enabled) $rowspan++;
	if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS) $rowspan++;

	//Local taxes
	if ($mysoc->pays_code=='ES')
	{
		if($mysoc->localtax1_assuj=="1") $rowspan++;
		if($mysoc->localtax2_assuj=="1") $rowspan++;
	}

	// Notes
	print '<td valign="top" colspan="2" width="50%" rowspan="'.$rowspan.'">'.$langs->trans('NotePublic').' :<br>'. nl2br($object->note_public).'</td>';
	print '</tr>';

	// Date fin propal
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateEndPropal');
	print '</td>';
	if ($_GET['action'] != 'editecheance' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editecheance&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($object->brouillon && $_GET['action'] == 'editecheance')
	{
		print '<form name="editecheance" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setecheance">';
		$html->select_date($object->fin_validite,'ech','','','',"editecheance");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		if ($object->fin_validite)
		{
			print dol_print_date($object->fin_validite,'daytext');
			if ($object->statut == 1 && $object->fin_validite < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
		}
		else
		{
			print '&nbsp;';
		}
	}
	print '</td>';
	print '</tr>';

	// Delivery date
	$langs->load('deliveries');
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DeliveryDate');
	print '</td>';
	if ($_GET['action'] != 'editdate_livraison' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editdate_livraison')
	{
		print '<form name="editdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setdate_livraison">';
		$html->select_date($object->date_livraison,'liv_','','','',"editdate_livraison");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		print dol_print_date($object->date_livraison,'daytext');
	}
	print '</td>';
	print '</tr>';

	// adresse de livraison
	if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS)
	{
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('DeliveryAddress');
		print '</td>';

		if ($_GET['action'] != 'editdelivery_address' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdelivery_address&amp;socid='.$object->socid.'&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryAddress'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';

		if ($_GET['action'] == 'editdelivery_address')
		{
			$html->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$_GET['socid'],'fk_address','propal',$object->id);
		}
		else
		{
			$html->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$_GET['socid'],'none','propal',$object->id);
		}
		print '</td></tr>';
	}

	// Payment term
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentConditionsShort');
	print '</td>';
	if ($_GET['action'] != 'editconditions' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editconditions')
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'cond_reglement_id');
	}
	else
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'none');
	}
	print '</td>';
	print '</tr>';

	// Payment mode
	print '<tr>';
	print '<td width="25%">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($_GET['action'] != 'editmode' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editmode')
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'mode_reglement_id');
	}
	else
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'none');
	}
	print '</td></tr>';

	// Project
	if ($conf->projet->enabled)
	{
		$langs->load("projects");
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project').'</td>';
		if ($user->rights->propale->creer)
		{
			if ($_GET['action'] != 'classer') print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classer&amp;id='.$object->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($_GET['action'] == 'classer')
			{
				$html->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'projectid');
			}
			else
			{
				$html->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none');
			}
			print '</td></tr>';
		}
		else
		{
			print '</td></tr></table>';
			if (!empty($object->fk_project))
			{
				print '<td colspan="3">';
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				print '<a href="../projet/fiche.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
				print $proj->ref;
				print '</a>';
				print '</td>';
			}
			else {
				print '<td colspan="3">&nbsp;</td>';
			}
		}
		print '</tr>';
	}

	// Amount HT
	print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
	print '<td align="right" colspan="2" nowrap><b>'.price($object->total_ht).'</b></td>';
	print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	// Amount VAT
	print '<tr><td height="10">'.$langs->trans('AmountVAT').'</td>';
	print '<td align="right" colspan="2" nowrap>'.price($object->total_tva).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	// Amount Local Taxes
	if ($mysoc->pays_code=='ES')
	{
		if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
		{
			print '<tr><td height="10">'.$langs->transcountry("AmountLT1",$mysoc->pays_code).'</td>';
			print '<td align="right" colspan="2" nowrap>'.price($object->total_localtax1).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
		}
		if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
		{
			print '<tr><td height="10">'.$langs->transcountry("AmountLT2",$mysoc->pays_code).'</td>';
			print '<td align="right" colspan="2" nowrap>'.price($object->total_localtax2).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
		}
	}

	// Amount TTC
	print '<tr><td height="10">'.$langs->trans('AmountTTC').'</td>';
	print '<td align="right" colspan="2" nowrap>'.price($object->total_ttc).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	// Statut
	print '<tr><td height="10">'.$langs->trans('Status').'</td><td align="left" colspan="3">'.$object->getLibStatut(4).'</td></tr>';
	print '</table><br>';

	/*
	 * Lines
	 */
    $result = $object->getLinesArray();

	if ($conf->use_javascript_ajax && $object->statut == 0)
	{
		include(DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php');
	}

	print '<table id="tablelines" class="noborder" width="100%">';

	if (!empty($object->lines))
	{
		$object->print_title_list();
		$object->printLinesList(0,$mysoc,$soc);
	}

	/*
	 * Form to add new line
	 */
	if ($object->statut == 0 && $user->rights->propale->creer)
	{
		if ($_GET['action'] != 'editline')
		{
			$var=true;

			// Add free products/services
			$object->showAddFreeProductForm(0,$mysoc,$soc);

			// Add predefined products/services
			if ($conf->product->enabled || $conf->service->enabled)
			{
				$var=!$var;
				$object->showAddPredefinedProductForm(0,$mysoc,$soc);
			}

			// Hook of thirdparty module
			if (! empty($object->hooks))
			{
				foreach($object->hooks as $module)
				{
					$var=!$var;
					$module->formAddObject($object);
				}
			}
		}
	}

	print '</table>';

	print '</div>';
	print "\n";

	if ($_GET['action'] == 'statut')
	{
		/*
		 * Formulaire cloture (signe ou non)
		 */
		$form_close = '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		$form_close.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$form_close.= '<table class="border" width="100%">';
		$form_close.= '<tr><td width="150" align="left">'.$langs->trans('Note').'</td><td align="left"><textarea cols="70" rows="'.ROWS_3.'" wrap="soft" name="note">';
		$form_close.= $object->note;
		$form_close.= '</textarea></td></tr>';
		$form_close.= '<tr><td width="150"  align="left">'.$langs->trans("CloseAs").'</td><td align="left">';
		$form_close.= '<input type="hidden" name="action" value="setstatut">';
		$form_close.= '<select name="statut">';
		$form_close.= '<option value="0">&nbsp;</option>';
		$form_close.= '<option value="2">'.$object->labelstatut[2].'</option>';
		$form_close.= '<option value="3">'.$object->labelstatut[3].'</option>';
		$form_close.= '</select>';
		$form_close.= '</td></tr>';
		$form_close.= '<tr><td align="center" colspan="2">';
		$form_close.= '<input type="submit" class="button" name="validate" value="'.$langs->trans('Validate').'">';
		$form_close.= ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
		$form_close.= '<a name="close">&nbsp;</a>';
		$form_close.= '</td>';
		$form_close.= '</tr></table></form>';

		print $form_close;
	}


	/*
	 * Boutons Actions
	 */
	if ($_GET['action'] != 'presend')
	{
		print '<div class="tabsAction">';

		if ($_GET['action'] != 'statut' && $_GET['action'] <> 'editline')
		{
			// Valid
			if ($object->statut == 0 && $user->rights->propale->valider)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validate"';
				print '>'.$langs->trans('Validate').'</a>';
			}

			// Edit
			if ($object->statut == 1 && $user->rights->propale->creer)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=modif">'.$langs->trans('Modify').'</a>';
			}

			// Send
			if ($object->statut == 1 && $user->rights->propale->envoyer)
			{
				$propref = dol_sanitizeFileName($object->ref);
				$file = $conf->propale->dir_output . '/'.$propref.'/'.$propref.'.pdf';
				if (file_exists($file))
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
				}
			}

			// Create an invoice and classify billed
			if ($conf->facture->enabled && $object->statut == 2 && $user->societe_id == 0)
			{
				if ($user->rights->facture->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("BuildBill").'</a>';
				}

				$arraypropal=$object->getInvoiceArrayList();
				if (is_array($arraypropal) && sizeof($arraypropal) > 0)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled&amp;socid='.$object->socid.'">'.$langs->trans("ClassifyBilled").'</a>';
				}
			}

			// Close
			if ($object->statut == 1 && $user->rights->propale->cloturer)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=statut#close"';
				print '>'.$langs->trans('Close').'</a>';
			}

			// Clone
			if ($object->type == 0 && $user->rights->propale->creer)
			{
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=clone&amp;object=propal">'.$langs->trans("ToClone").'</a>';
			}

			// Delete
			if ($user->rights->propale->supprimer)
			{
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete"';
				print '>'.$langs->trans('Delete').'</a>';
			}

		}

		print '</div>';
		print "<br>\n";
	}

	if ($_GET['action'] != 'presend')
	{
		print '<table width="100%"><tr><td width="50%" valign="top">';
		print '<a name="builddoc"></a>'; // ancre


		/*
		 * Documents generes
		 */
		$filename=dol_sanitizeFileName($object->ref);
		$filedir=$conf->propale->dir_output . "/" . dol_sanitizeFileName($object->ref);
		$urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed=$user->rights->propale->creer;
		$delallowed=$user->rights->propale->supprimer;

		$var=true;

		$somethingshown=$formfile->show_documents('propal',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'',0,'',$soc->default_lang);


		/*
		 * Linked object block
		 */
		$object->load_object_linked($object->id,$object->element);
		//var_dump($object->linked_object);

		foreach($object->linked_object as $linked_object => $linked_objectid)
		{
			if($conf->$linked_object->enabled && $linked_object != $object->element)
			{
				$somethingshown=$object->showLinkedObjectBlock($linked_object,$linked_objectid,$somethingshown);
			}
		}

		print '</td><td valign="top" width="50%">';

		// List of actions on element
		include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php');
		$formactions=new FormActions($db);
		$somethingshown=$formactions->showactions($object,'propal',$socid);

		print '</td></tr></table>';
	}


	/*
	 * Action presend
	 *
	 */
	if ($_GET['action'] == 'presend')
	{
		$ref = dol_sanitizeFileName($object->ref);
		$file = $conf->propale->dir_output . '/' . $ref . '/' . $ref . '.pdf';

		print '<br>';
		print_titre($langs->trans('SendPropalByMail'));

		// Create form object
		include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
		$formmail = new FormMail($db);
		$formmail->fromtype = 'user';
		$formmail->fromid   = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		$formmail->withfrom=1;
		$formmail->withto=empty($_POST["sendto"])?1:$_POST["sendto"];
		$formmail->withtosocid=$soc->id;
		$formmail->withtocc=1;
		$formmail->withtoccsocid=0;
		$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
		$formmail->withtocccsocid=0;
		$formmail->withtopic=$langs->trans('SendPropalRef','__PROPREF__');
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;

		// Tableau des substitutions
		$formmail->substit['__PROPREF__']=$object->ref;
		// Tableau des parametres complementaires
		$formmail->param['action']='send';
		$formmail->param['models']='propal_send';
		$formmail->param['id']=$object->id;
		$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

		// Init list of files
		if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
		{
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file,dol_sanitizeFilename($object->ref).'.pdf','application/pdf');
		}

		$formmail->show_form();

		print '<br>';
	}

}
else
{
	/****************************************************************************
	 *                                                                          *
	 *                         Mode Liste des propales                          *
	 *                                                                          *
	 ****************************************************************************/

	$now=dol_now();

	$sortfield = GETPOST("sortfield",'alpha');
	$sortorder = GETPOST("sortorder",'alpha');
	$page = GETPOST("page",'int');
	if ($page == -1) { $page = 0; }
	$offset = $conf->liste_limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;

	$viewstatut=addslashes($_GET['viewstatut']);
	$object_statut = addslashes($_GET['propal_statut']);
	if($object_statut != '')
	$viewstatut=$object_statut;

	if (! $sortfield) $sortfield='p.datep';
	if (! $sortorder) $sortorder='DESC';
	$limit = $conf->liste_limit;

	$sql = 'SELECT s.nom, s.rowid, s.client, ';
	$sql.= 'p.rowid as propalid, p.total_ht, p.ref, p.fk_statut, p.fk_user_author, p.datep as dp, p.fin_validite as dfv,';
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " sc.fk_soc, sc.fk_user,";
	$sql.= ' u.login';
	$sql.= ' FROM ('.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'propal as p';
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= ')';
	if ($sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet as pd ON p.rowid=pd.fk_propal';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON p.fk_user_author = u.rowid';
	$sql.= ' WHERE p.fk_soc = s.rowid';
	$sql.= ' AND s.entity = '.$conf->entity;

	if (!$user->rights->societe->client->voir && !$socid) //restriction
	{
		$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	}
	if (!empty($_GET['search_ref']))
	{
		$sql.= " AND p.ref LIKE '%".addslashes($_GET['search_ref'])."%'";
	}
	if (!empty($_GET['search_societe']))
	{
		$sql.= " AND s.nom LIKE '%".addslashes($_GET['search_societe'])."%'";
	}
	if (!empty($_GET['search_montant_ht']))
	{
		$sql.= " AND p.total_ht='".addslashes($_GET['search_montant_ht'])."'";
	}
	if ($sall) $sql.= " AND (s.nom like '%".addslashes($sall)."%' OR p.note like '%".addslashes($sall)."%' OR pd.description like '%".addslashes($sall)."%')";
	if ($socid) $sql.= ' AND s.rowid = '.$socid;
	if ($viewstatut <> '')
	{
		$sql.= ' AND p.fk_statut in ('.$viewstatut.')';
	}
	if ($month > 0)
	{
		if ($year > 0)
		$sql.= " AND date_format(p.datep, '%Y-%m') = '$year-$month'";
		else
		$sql.= " AND date_format(p.datep, '%m') = '$month'";
	}
	if ($year > 0)
	{
		$sql.= " AND date_format(p.datep, '%Y') = $year";
	}
	if (dol_strlen($_POST['sf_ref']) > 0)
	{
		$sql.= " AND p.ref like '%".addslashes($_POST["sf_ref"]) . "%'";
	}

	$sql.= ' ORDER BY '.$sortfield.' '.$sortorder.', p.ref DESC';
	$sql.= $db->plimit($limit + 1,$offset);
	$result=$db->query($sql);

	if ($result)
	{
		$objectstatic=new Propal($db);
		$userstatic=new User($db);

		$num = $db->num_rows($result);

		$param='&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut;
		if ($month) $param.='&amp;month='.$month;
		if ($year) $param.='&amp;year='.$year;
		print_barre_liste($langs->trans('ListOfProposals'), $page,'propal.php',$param,$sortfield,$sortorder,'',$num);

		$i = 0;
		print '<table class="liste" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans('Ref'),$_SERVER["PHP_SELF"],'p.ref','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Company'),$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Date'),$_SERVER["PHP_SELF"],'p.datep','',$param, 'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('DateEndPropalShort'),$_SERVER["PHP_SELF"],'dfv','',$param, 'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Price'),$_SERVER["PHP_SELF"],'p.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Author'),$_SERVER["PHP_SELF"],'u.login','',$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Status'),$_SERVER["PHP_SELF"],'p.fk_statut','',$param,'align="right"',$sortfield,$sortorder);
		print '<td class="liste_titre">&nbsp;</td>';
		print "</tr>\n";
		// Lignes des champs de filtre
		print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

		print '<tr class="liste_titre">';
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET['search_ref'].'">';
		print '</td>';
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="16" name="search_societe" value="'.$_GET['search_societe'].'">';
		print '</td>';
		print '<td class="liste_titre" colspan="1" align="right">';
		print $langs->trans('Month').': <input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
		print '&nbsp;'.$langs->trans('Year').': ';
		$max_year = date("Y");
		$syear = $year;
		//if($syear == '') $syear = date("Y");
		$html->select_year($syear,'year',1, '', $max_year);
		print '</td>';
		print '<td class="liste_titre" colspan="1">&nbsp;</td>';
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET['search_montant_ht'].'">';
		print '</td>';
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre" align="right">';
		$html->select_propal_statut($viewstatut);
		print '</td>';
		print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
		print '</td>';
		print "</tr>\n";
		print '</form>';

		$var=true;

		while ($i < min($num,$limit))
		{
			$objp = $db->fetch_object($result);
			$now = time();
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td nowrap="nowrap">';

			$objectstatic->id=$objp->propalid;
			$objectstatic->ref=$objp->ref;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td class="nobordernopadding" nowrap="nowrap">';
			print $objectstatic->getNomUrl(1);
			print '</td>';

			print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
			if ($objp->fk_statut == 1 && $objp->dfv < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding">';
			$filename=dol_sanitizeFileName($objp->ref);
			$filedir=$conf->propale->dir_output . '/' . dol_sanitizeFileName($objp->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->propalid;
			$formfile->show_documents('propal',$filename,$filedir,$urlsource,'','','',1,'',1);
			print '</td></tr></table>';

			if ($objp->client == 1)
			{
				$url = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->rowid;
			}
			else
			{
				$url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$objp->rowid;
			}

			// Company
			$companystatic->id=$objp->rowid;
			$companystatic->nom=$objp->nom;
			$companystatic->client=$objp->client;
			print '<td>';
			print $companystatic->getNomUrl(1,'customer');
			print '</td>';

			// Date propale
			print '<td align="center">';
			$y = dol_print_date($db->jdate($objp->dp),'%Y');
			$m = dol_print_date($db->jdate($objp->dp),'%m');
			$mt= dol_print_date($db->jdate($objp->dp),'%b');
			$d = dol_print_date($db->jdate($objp->dp),'%d');
			print $d."\n";
			print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'&amp;month='.$m.'">';
			print $mt."</a>\n";
			print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'">';
			print $y."</a></td>\n";

			// Date fin validite
			if ($objp->dfv)
			{
				print '<td align="center">'.dol_print_date($db->jdate($objp->dfv),'day');
				print '</td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}

			print '<td align="right">'.price($objp->total_ht)."</td>\n";

			$userstatic->id=$objp->fk_user_author;
			$userstatic->login=$objp->login;
			print '<td align="center">';
			if ($userstatic->id) print $userstatic->getLoginUrl(1);
			else print '&nbsp;';
			print "</td>\n";

			print '<td align="right">'.$objectstatic->LibStatut($objp->fk_statut,5)."</td>\n";

			print '<td>&nbsp;</td>';

			print "</tr>\n";

			$total = $total + $objp->total_ht;
			$subtotal = $subtotal + $objp->total_ht;

			$i++;
		}
		print '</table>';
		$db->free($result);
	}
	else
	{
		dol_print_error($db);
	}
}
$db->close();

llxFooter('$Date: 2011/01/29 16:30:41 $ - $Revision: 1.563.2.1 $');

?>
