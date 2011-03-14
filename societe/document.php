<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/societe/document.php
 *  \brief      Tab for documents linked to third party
 *  \ingroup    societe
 *  \version    $Id: document.php,v 1.23 2010/12/15 18:15:09 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load("companies");
$langs->load('other');

$mesg = "";

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:(! empty($_GET["id"])?$_GET["id"]:'');
if ($user->societe_id > 0)
{
	unset($_GET["action"]);
	$action='';
	$socid = $user->societe_id;
}
$result = restrictedArea($user, 'societe', $socid);

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$upload_dir = $conf->societe->dir_output . "/" . $socid ;
$courrier_dir = $conf->societe->dir_output . "/courrier/" . get_exdir($socid) ;


/*
 * Actions
 */

// Envoie fichier
if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

	if (create_exdir($upload_dir) >= 0)
	{
		$resupload=dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0,0,$_FILES['userfile']['error']);
		if (is_numeric($resupload) && $resupload > 0)
		{
			$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
		}
		else
		{
			$langs->load("errors");
			if ($resupload < 0)	// Unknown error
			{
				$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
			}
			else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
			{
				$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWithAVirus").'</div>';
			}
			else	// Known error
			{
				$mesg = '<div class="error">'.$langs->trans($resupload).'</div>';
			}
		}
	}
}

// Suppression fichier
if ($_REQUEST['action'] == 'confirm_deletefile' && $_REQUEST['confirm'] == 'yes')
{
	$file = $upload_dir . "/" . $_GET['urlfile'];	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	dol_delete_file($file);
	$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
}


/*
 * View
 */

$form = new Form($db);

llxHeader();

if ($socid > 0)
{
	$societe = new Societe($db);
	if ($societe->fetch($socid))
	{
		/*
		 * Affichage onglets
		 */
		if ($conf->notification->enabled) $langs->load("mails");
		$head = societe_prepare_head($societe);

		$html=new Form($db);

		dol_fiche_head($head, 'document', $langs->trans("ThirdParty"),0,'company');


		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),3);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}


		print '<table class="border"width="100%">';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Name").'</td>';
		print '<td colspan="3">';
		print $form->showrefnav($societe,'socid','',($user->societe_id?0:1),'rowid','nom');
		print '</td></tr>';

		// Prefix
		print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$societe->prefix_comm.'</td></tr>';

	    if ($societe->client)
	    {
	        print '<tr><td>';
	        print $langs->trans('CustomerCode').'</td><td colspan="3">';
	        print $societe->code_client;
	        if ($societe->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
	        print '</td></tr>';
	    }

	    if ($societe->fournisseur)
	    {
	        print '<tr><td>';
	        print $langs->trans('SupplierCode').'</td><td colspan="3">';
	        print $societe->code_fournisseur;
	        if ($societe->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
	        print '</td></tr>';
	    }

    	// Nbre fichiers
		print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';

		//Total taille
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

		print '</table>';

		print '</div>';

		if ($mesg) { print "$mesg<br>"; }

		/*
		 * Confirmation suppression fichier
		 */
		if ($_GET['action'] == 'delete')
		{
			$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?socid='.$_GET["id"].'&urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
			if ($ret == 'html') print '<br>';
		}


		// Affiche formulaire upload
		$formfile=new FormFile($db);
		$formfile->form_attach_new_file(DOL_URL_ROOT.'/societe/document.php?socid='.$socid,'',0,0,$user->rights->societe->creer);


		// List of document
		$param='&socid='.$societe->id;
		$formfile->list_of_documents($filearray,$societe,'societe',$param);


		print "<br><br>";

		// Courriers
		// Les courriers sont des documents speciaux generes par des scripts
		// situes dans scripts/courrier.
		// Voir Rodo
		if ($conf->global->MAIN_MODULE_EDITEUR)
		{
			$filearray=array();
			$errorlevel=error_reporting();
			error_reporting(0);
			$handle=opendir($courrier_dir);
			error_reporting($errorlevel);
			if (is_resource($handle))
			{
				$i=0;
				while (($file = readdir($handle))!==false)
				{
					if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
					{
						$filearray[$i]=$file;
						$i++;
					}
				}
				closedir($handle);
			}

			print '<table width="100%" class="noborder">';
			print '<tr class="liste_titre"><td>'.$langs->trans("Courriers").'</td><td align="right">'.$langs->trans("Size").'</td><td align="center">'.$langs->trans("Date").'</td></tr>';

			$var=true;
			foreach($filearray as $key => $file)
			{
				if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
				{
					$var=!$var;
					print "<tr $bc[$var]><td>";
					$loc = "courrier/".get_exdir($socid);
					echo '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=societe&attachment=1&file='.urlencode($loc.'/'.$file).'">'.$file.'</a>';
					print "</td>\n";

					print '<td align="right">'.dol_print_size(dol_filesize($courrier_dir."/".$file)).'</td>';
					print '<td align="center">'.dol_print_date(dol_filemtime($courrier_dir."/".$file),'dayhour').'</td>';
					print "</tr>\n";
				}
			}
			print "</table>";
		}
	}
	else
	{
		dol_print_error($db);
	}
}
else
{
	dol_print_error();
}

$db->close();


llxFooter('$Date: 2010/12/15 18:15:09 $ - $Revision: 1.23 $');

?>
