<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/compta/paiement/cheque/fiche.php
 *	\ingroup    facture
 *	\brief      Tab cheque deposit
 *	\version    $Id: fiche.php,v 1.59 2010/12/29 11:08:03 hregis Exp $
 */

require("./pre.inc.php");	// We use pre.inc.php to have a dynamic menu
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');

$langs->load('bills');
$langs->load('banks');
$langs->load('companies');

$id = isset($_REQUEST["id"])?$_REQUEST["id"]:'';
$ref= isset($_REQUEST["ref"])?$_REQUEST["ref"]:'';

// Security check
$fieldid = isset($_GET["ref"])?'number':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'cheque', $id, 'bordereau_cheque','','',$fieldid);

$mesg='';

$sortfield=isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder=isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=$_GET["page"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="b.emetteur";
if ($page < 0) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$dir=$conf->banque->dir_output.'/bordereau/';


/*
 * Actions
 */

if ($_POST['action'] == 'setdate' && $user->rights->banque->cheque)
{
    $remisecheque = new RemiseCheque($db);
    $result = $remisecheque->fetch(GETPOST('id'));
    if ($result > 0)
    {
        //print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
        $date=dol_mktime(0, 0, 0, $_POST['datecreate_month'], $_POST['datecreate_day'], $_POST['datecreate_year']);

        $result=$remisecheque->set_date($user,$date);
        if ($result < 0)
        {
            $mesg='<div class="error">'.$remisecheque->error.'</div>';
        }
    }
    else
    {
        $mesg='<div class="error">'.$remisecheque->error.'</div>';
    }
}

if ($_GET['action'] == 'create' && $_GET["accountid"] > 0 && $user->rights->banque->cheque)
{
	$remisecheque = new RemiseCheque($db);
	$result = $remisecheque->create($user, $_GET["accountid"]);
	if ($result > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$remisecheque->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$remisecheque->error.'</div>';
	}
}

if ($_GET['action'] == 'remove' && $_GET["id"] > 0 && $_GET["lineid"] > 0 && $user->rights->banque->cheque)
{
	$remisecheque = new RemiseCheque($db);
	$remisecheque->id = $_GET["id"];
	$result = $remisecheque->removeCheck($_GET["lineid"]);
	if ($result === 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$remisecheque->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
	}
}

if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes' && $user->rights->banque->cheque)
{
	$remisecheque = new RemiseCheque($db);
	$remisecheque->id = $_GET["id"];
	$result = $remisecheque->delete();
	if ($result == 0)
	{
		Header("Location: index.php");
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
	}
}

if ($_REQUEST['action'] == 'confirm_valide' && $_REQUEST['confirm'] == 'yes' && $user->rights->banque->cheque)
{
	$remisecheque = new RemiseCheque($db);
	$result = $remisecheque->fetch($_GET["id"]);
	$result = $remisecheque->validate($user);
	if ($result >= 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$remisecheque->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
	}
}

if ($_POST['action'] == 'builddoc' && $user->rights->banque->cheque)
{
	$remisecheque = new RemiseCheque($db);
	$result = $remisecheque->fetch($_GET["id"]);

	/*if ($_REQUEST['model'])
	{
		$remisecheque->setDocModel($user, $_REQUEST['model']);
	}*/

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}

	$result = $remisecheque->generatePdf($_POST["model"], $outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$remisecheque->error);
		exit;
	}
	else
	{
		Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$remisecheque->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
		exit;
	}
}


/*
 * View
 */

llxHeader();

$html = new Form($db);
$formfile = new FormFile($db);

if ($_GET['action'] == 'new')
{
	$h=0;
	$head[$h][0] = $_SERVER["PHP_SELF"].'?action=new';
	$head[$h][1] = $langs->trans("MenuChequeDeposits");
	$hselected = $h;
	$h++;

	print_fiche_titre($langs->trans("Cheques"));
}
else
{
	$remisecheque = new RemiseCheque($db);
	$result = $remisecheque->fetch($_REQUEST["id"],$_REQUEST["ref"]);
	if ($result < 0)
	{
		dol_print_error($db,$remisecheque->error);
		exit;
	}

	$h=0;
	$head[$h][0] = $_SERVER["PHP_SELF"].'?id='.$remisecheque->id;
	$head[$h][1] = $langs->trans("CheckReceipt");
	$hselected = $h;
	$h++;
	//  $head[$h][0] = DOL_URL_ROOT.'/compta/paiement/cheque/info.php?id='.$remisecheque->id;
	//  $head[$h][1] = $langs->trans("Info");
	//  $h++;

	dol_fiche_head($head, $hselected, $langs->trans("Cheques"),0,'payment');

	/*
	 * Confirmation de la suppression du bordereau
	 */
	if ($_GET['action'] == 'delete')
	{
		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$remisecheque->id, $langs->trans("DeleteCheckReceipt"), $langs->trans("ConfirmDeleteCheckReceipt"), 'confirm_delete','','',1);
		if ($ret == 'html') print '<br>';
	}

	/*
	 * Confirmation de la validation du bordereau
	 */
	if ($_GET['action'] == 'valide')
	{
		$facid = $_GET['facid'];
		$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$remisecheque->id, $langs->trans("ValidateCheckReceipt"), $langs->trans("ConfirmValidateCheckReceipt"), 'confirm_valide','','',1);
		if ($ret == 'html') print '<br>';
	}
}

if ($mesg) print $mesg.'<br>';


if ($_GET['action'] == 'new')
{
	$accounts = array();
	$lines = array();

	$now=time();

	print '<table class="border" width="100%">';
	print '<tr><td width="30%">'.$langs->trans('Date').'</td><td width="70%">'.dol_print_date($now,'day').'</td></tr>';
	print '</table><br>';

	$sql = "SELECT ba.rowid as bid, b.dateo as date,";
	$sql.= " b.amount, ba.label, b.emetteur, b.num_chq, b.banque";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b ";
	$sql.= ",".MAIN_DB_PREFIX."bank_account as ba ";
	$sql.= " WHERE b.fk_type = 'CHQ'";
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= " AND ba.entity = ".$conf->entity;
	$sql.= " AND b.fk_bordereau = 0";
	$sql.= " AND b.amount > 0";
	$sql.= " ORDER BY b.emetteur ASC, b.rowid ASC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$i = 0;
		while ( $obj = $db->fetch_object($resql) )
		{
			$accounts[$obj->bid] = $obj->label;
			$lines[$obj->bid][$i]["date"] = $db->jdate($obj->date);
			$lines[$obj->bid][$i]["amount"] = $obj->amount;
			$lines[$obj->bid][$i]["emetteur"] = $obj->emetteur;
			$lines[$obj->bid][$i]["numero"] = $obj->num_chq;
			$lines[$obj->bid][$i]["banque"] = $obj->banque;
			$i++;
		}

		if ($i == 0)
		{
			print $langs->trans("NoWaitingChecks").'<br>';
		}
	}

	foreach ($accounts as $bid => $account_label)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("DateChequeReceived")." &nbsp;</td>\n";
		print '<td width="120">'.$langs->trans("ChequeNumber")."</td>\n";
		print '<td>'.$langs->trans("CheckTransmitter")."</td>\n";
		print '<td>'.$langs->trans("Bank")."</td>\n";
		print '<td align="right">'.$langs->trans("Amount")."</td>\n";
		print "</tr>\n";

		$var=true;

		foreach ($lines[$bid] as $lid => $value)
		{
			$var=!$var;

			$account_id = $objp->bid;
			$accounts[$objp->bid] += 1;

			print "<tr ".$bc[$var].">";
			print '<td width="120">'.dol_print_date($value["date"],'day').'</td>';
			print '<td>'.$value["numero"]."</td>\n";
			print '<td>'.$value["emetteur"]."</td>\n";
			print '<td>'.$value["banque"]."</td>\n";
			print '<td align="right">'.price($value["amount"]).'</td>';
			print '</tr>';
			$i++;
		}
		print "</table>";

		print '<div class="tabsAction">';
		if ($user->rights->banque->cheque)
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=create&amp;accountid='.$bid.'">'.$langs->trans('NewCheckDepositOn',$account_label).'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('NewCheckDepositOn',$account_label).'</a>';
		}
		print '</div><br>';
	}

}
else
{
    $object=$remisecheque;
	$linkback='<a href="'.$_SERVER["PHP_SELF"].'?leftmenu=customers_bills_checks&action=new">'.$langs->trans("BackToList").'</a>';
	$paymentstatic=new Paiement($db);
	$accountlinestatic=new AccountLine($db);
	$accountstatic=new Account($db);

	$accountstatic->id=$remisecheque->account_id;
	$accountstatic->label=$remisecheque->account_label;

	print '<table class="border" width="100%">';
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td><td colspan="2" >';

	print $html->showrefnav($remisecheque,'ref',$linkback, 1, 'number');

	print "</td>";
	print "</tr>\n";

	print '<tr><td>';

    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('Date');
    print '</td>';
    if ($_GET['action'] != 'editdate') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
    print '</tr></table>';
    print '</td><td colspan="2">';
    if ($_GET['action'] == 'editdate')
    {
        print '<form name="setdate" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="setdate">';
        $html->select_date($object->date_bordereau,'datecreate_','','','',"setdate");
        print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
        print '</form>';
    }
    else
    {
        print $object->date_bordereau ? dol_print_date($object->date_bordereau,'day') : '&nbsp;';
    }

	print '</td>';
	print '</tr>';

	print '<tr><td>'.$langs->trans('Account').'</td><td colspan="2">';
	print $accountstatic->getNomUrl(1);
	print '</td></tr>';

	// Nb of cheques
	print '<tr><td>'.$langs->trans('NbOfCheques').'</td><td colspan="2">';
	print $remisecheque->nbcheque;
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Total').'</td><td colspan="2">';
	print price($remisecheque->amount);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Status').'</td><td colspan="2">';
	print $remisecheque->getLibStatut(4);
	print '</td></tr>';

	print '</table><br>';


	// Liste des cheques
	$sql = "SELECT b.rowid, b.amount, b.num_chq, b.emetteur,";
	$sql.= " b.dateo as date, b.datec as datec, b.banque,";
	$sql.= " p.rowid as pid";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."bank as b";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement as p ON p.fk_bank = b.rowid";
	$sql.= " WHERE ba.rowid = b.fk_account";
	$sql.= " AND ba.entity = ".$conf->entity;
	$sql.= " AND b.fk_type= 'CHQ'";
	$sql.= " AND b.fk_bordereau = ".$remisecheque->id;
	$sql.= " ORDER BY $sortfield $sortorder";
	//print $sql;
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';

		$param="&amp;id=".$remisecheque->id;
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Cheque"),'','','','','width="30"');
		print_liste_field_titre($langs->trans("Numero"),$_SERVER["PHP_SELF"],"b.num_chq", "",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("CheckTransmitter"),$_SERVER["PHP_SELF"],"b.emetteur", "",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Bank"),$_SERVER["PHP_SELF"],"b.banque", "",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"b.amount", "",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("LineRecord"),$_SERVER["PHP_SELF"],"b.rowid", "",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DateChequeReceived"),$_SERVER["PHP_SELF"],"b.datec", "",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre('','','');
		print "</tr>\n";
		$i=1;
		$var=false;
		while ( $objp = $db->fetch_object($resql) )
		{
			$account_id = $objp->bid;
			$accounts[$objp->bid] += 1;

			print "<tr $bc[$var]>";
			print '<td align="center">'.$i.'</td>';
			print '<td align="center">'.($objp->num_chq?$objp->num_chq:'&nbsp;').'</td>';
			print '<td>'.dol_trunc($objp->emetteur,24).'</td>';
			print '<td>'.dol_trunc($objp->banque,24).'</td>';
			print '<td align="right">'.price($objp->amount).'</td>';
			print '<td align="center">';
			$accountlinestatic->rowid=$objp->rowid;
			if ($accountlinestatic->rowid)
			{
				print $accountlinestatic->getNomUrl(1);
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';
			print '<td align="center">'.dol_print_date($db->jdate($objp->datec),'day').'</td>';
			if($remisecheque->statut == 0)
			{
				print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?id='.$remisecheque->id.'&amp;action=remove&amp;lineid='.$objp->rowid.'">'.img_delete().'</a></td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}
			print '</tr>';
			$var=!$var;
			$i++;
		}
		print "</table>";
	}
	else
	{
		dol_print_error($db);
	}

    dol_fiche_end();
}




/*
 * Boutons Actions
 */

print '<div class="tabsAction">';

if ($user->societe_id == 0 && sizeof($accounts) == 1 && $_GET['action'] == 'new' && $user->rights->banque->cheque)
{
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=create&amp;accountid='.$account_id.'">'.$langs->trans('NewCheckReceipt').'</a>';
}

if ($user->societe_id == 0 && $remisecheque->statut == 0 && $remisecheque->id && $user->rights->banque->cheque)
{
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$remisecheque->id.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
}

if ($user->societe_id == 0 && $remisecheque->id && $user->rights->banque->cheque)
{
	print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$remisecheque->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';

}
print '</div>';



if ($_GET['action'] != 'new')
{
	if ($remisecheque->statut == 1)
	{
		$dirchequereceipts = $dir.get_exdir($remisecheque->number,2,1).$remisecheque->ref;
		$formfile->show_documents("remisecheque",$remisecheque->ref,$dirchequereceipts,$_SERVER["PHP_SELF"].'?id='.$remisecheque->id,1,1);
	}
}


$db->close();

llxFooter('$Date: 2010/12/29 11:08:03 $ - $Revision: 1.59 $');
?>
