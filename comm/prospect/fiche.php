<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/comm/prospect/fiche.php
 *	\ingroup    prospect
 *	\brief      Page de la fiche prospect
 *	\version    $Id: fiche.php,v 1.119 2010/09/19 12:50:55 eldy Exp $
 */

require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/comm/prospect/class/prospect.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");

$langs->load('companies');
$langs->load('projects');
$langs->load('propal');

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');


/*
 * Actions
 */

if ($_GET["action"] == 'cstc')
{
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm = ".$_GET["stcomm"];
	$sql .= " WHERE rowid = ".$_GET["socid"];
	$db->query($sql);
}
// set prospect level
if ($_POST["action"] == 'setprospectlevel' && $user->rights->societe->creer)
{

	$societe = new Societe($db, $_GET["socid"]);
	$societe->fk_prospectlevel=$_POST['prospect_level_id'];
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_prospectlevel='".$_POST['prospect_level_id'];
	$sql.= "' WHERE rowid='".$_GET["socid"]."'";
	$result = $db->query($sql);
	if (! $result) dol_print_error($result);
}


/*********************************************************************************
 *
 * Mode fiche
 *
 *********************************************************************************/

llxHeader();

$form=new Form($db);
$formcompany=new FormCompany($db);

if ($socid > 0)
{
	$actionstatic=new ActionComm($db);
	$societe = new Prospect($db, $socid);
	$result = $societe->fetch($socid);
	if ($result < 0)
	{
		dol_print_error($db);
		exit;
	}

	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($societe);

	dol_fiche_head($head, 'prospect', $langs->trans("ThirdParty"),0,'company');

	print '<table width="100%" class="notopnoleftnoright">';
	print '<tr><td valign="top" width="50%" class="notopnoleft">';

	print '<table class="border" width="100%">';
	print '<tr><td width="25%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">';
	$societe->next_prev_filter="te.client in (2,3)";
	print $form->showrefnav($societe,'socid','',($user->societe_id?0:1),'rowid','nom','','');
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($societe->address)."</td></tr>";

	// Zip / Town
	print '<tr><td>'.$langs->trans('Zip').'</td><td>'.$societe->cp.'</td>';
	print '<td>'.$langs->trans('Town').'</td><td>'.$societe->ville.'</td></tr>';

	// Country
	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	$img=picto_from_langcode($societe->pays_code);
	if ($societe->isInEEC()) print $form->textwithpicto(($img?$img.' ':'').$societe->pays,$langs->trans("CountryIsInEEC"),1,0);
	else print ($img?$img.' ':'').$societe->pays;
	print '</td></tr>';

	// Phone
	print '<tr><td>'.$langs->trans("Phone").'</td><td>'.dol_print_phone($societe->tel,$societe->pays_code,0,$societe->id,'AC_TEL').'</td><td>'.$langs->trans("Fax").'</td><td>'.dol_print_phone($societe->fax,$societe->pays_code).'</td></tr>';

	// EMail
	print '<td>'.$langs->trans('EMail').'</td><td colspan="3">'.dol_print_email($societe->email,0,$societe->id,'AC_EMAIL').'</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$societe->url\">$societe->url</a></td></tr>";

	print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">'.$societe->forme_juridique.'</td></tr>';

	// Level of prospect
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
	print $langs->trans('ProspectLevelShort');
	print '<td>';
	if (($_GET['action'] != 'editlevel') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editlevel&amp;socid='.$societe->id.'">'.img_edit($langs->trans('SetLevel'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editlevel')
	{
		$formcompany->form_prospect_level($_SERVER['PHP_SELF'].'?socid='.$societe->id,$societe->fk_prospectlevel,'prospect_level_id',1);
	}
	else
	{
		print $societe->getLibLevel();
		//$formcompany->form_prospect_level($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->mode_reglement,'none');
	}
	print "</td>";
	print '</tr>';

	// Multiprice level
	if ($conf->global->PRODUIT_MULTIPRICES)
	{
		print '<tr><td nowrap>';
		print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
		print $langs->trans("PriceLevel");
		print '<td><td align="right">';
		if ($user->rights->societe->creer)
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/multiprix.php?id='.$societe->id.'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td><td colspan="3">'.$societe->price_level."</td>";
		print '</tr>';
	}

	// Status
	print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">'.$societe->getLibStatut(4).'</td>';
	print '<td>';
	if ($societe->stcomm_id != -1) print '<a href="fiche.php?socid='.$societe->id.'&amp;stcomm=-1&amp;action=cstc">'.img_action(0,-1).'</a>';
	if ($societe->stcomm_id !=  0) print '<a href="fiche.php?socid='.$societe->id.'&amp;stcomm=0&amp;action=cstc">'.img_action(0,0).'</a>';
	if ($societe->stcomm_id !=  1) print '<a href="fiche.php?socid='.$societe->id.'&amp;stcomm=1&amp;action=cstc">'.img_action(0,1).'</a>';
	if ($societe->stcomm_id !=  2) print '<a href="fiche.php?socid='.$societe->id.'&amp;stcomm=2&amp;action=cstc">'.img_action(0,2).'</a>';
	if ($societe->stcomm_id !=  3) print '<a href="fiche.php?socid='.$societe->id.'&amp;stcomm=3&amp;action=cstc">'.img_action(0,3).'</a>';
	print '</td></tr>';
	print '</table>';


	print "</td>\n";
	print '<td valign="top" width="50%" class="notopnoleft">';

	// Nbre max d'elements des petites listes
	$MAXLIST=5;
	$tableaushown=0;

	// Lien recap
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("Summary").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/prospect/recap-prospect.php?socid='.$societe->id.'">'.$langs->trans("ShowProspectPreview").'</a></td></tr></table></td>';
	print '</tr>';
	print '</table>';
	print '<br>';


	/*
	 * Last proposals
	 */
	if ($conf->propal->enabled)
	{
		$propal_static=new Propal($db);

		print '<table class="noborder" width="100%">';
		$sql = "SELECT s.nom, s.rowid as socid, p.rowid as propalid, p.fk_statut, p.total_ht, p.ref, p.remise, ";
		$sql.= " p.datep as dp, p.fin_validite as datelimite,";
		$sql.= " c.label as statut, c.id as statutid";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= ", ".MAIN_DB_PREFIX."propal as p";
		$sql.= ", ".MAIN_DB_PREFIX."c_propalst as c";
		$sql.= " WHERE p.fk_soc = s.rowid";
		$sql.= " AND p.fk_statut = c.id";
		$sql.= " AND p.entity = ".$conf->entity;
		$sql.= " AND s.rowid = ".$societe->id;
		$sql.= " ORDER BY p.datep DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$i = 0;
			$num = $db->num_rows($resql);
			if ($num > 0)
			{
				$tableaushown=1;
				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastPropals",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$societe->id.'">'.$langs->trans("AllPropals").' ('.$num.')</a></td></tr></table></td>';
				print '</tr>';
			}

			$now = dol_now();

			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr $bc[$var]>";
				print "<td><a href=\"../propal.php?id=$objp->propalid\">";
				print img_object($langs->trans("ShowPropal"),"propal");
				print " ".$objp->ref."</a>\n";
				if ($db->jdate($objp->dp) < ($now - $conf->propal->cloture->warning_delay) && $objp->fk_statut == 1)
				{
					print " ".img_warning();
				}
				print "</td><td align=\"right\">".dol_print_date($db->jdate($objp->dp),"day")."</td>\n";
				print "<td align=\"right\">".price($objp->total_ht)."</td>\n";
				print "<td align=\"right\">".$propal_static->LibStatut($objp->fk_statut,5)."</td></tr>\n";
				$i++;
			}
			$db->free();
		}
		else
		{
			dol_print_error($db);
		}

		print "</table>";
	}

	print "</td></tr>";
	print "</table>\n</div>\n";


	/*
	 * Barre d'action
	 */

	print '<div class="tabsAction">';

    if ($conf->propal->enabled && $user->rights->propale->creer)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$societe->id.'&amp;action=create">'.$langs->trans("AddProp").'</a>';
    }

    // Add action
    if ($conf->agenda->enabled && ! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
    {
        if ($user->rights->agenda->myactions->create)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$societe->id.'">'.$langs->trans("AddAction").'</a>';
        }
        else
        {
            print '<a class="butAction" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddAction").'</a>';
        }
    }

    print '<a class="butAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$societe->id.'&amp;action=create">'.$langs->trans("AddContact").'</a>';

	print '</div>';

	print '<br>';


    if (! empty($conf->global->MAIN_REPEATCONTACTONEACHTAB))
    {
        // List of contacts
        show_contacts($conf,$langs,$db,$societe);
    }

    if (! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
    {
        // List of todo actions
        show_actions_todo($conf,$langs,$db,$societe);

        // List of done actions
        show_actions_done($conf,$langs,$db,$societe);
    }
}

$db->close();

llxFooter('$Date: 2010/09/19 12:50:55 $ - $Revision: 1.119 $');
?>
