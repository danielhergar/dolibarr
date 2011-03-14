<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier DUTOIT        <doli@sydesy.com>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
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
 *	\file       htdocs/compta/bank/ligne.php
 *	\ingroup    compta
 *	\brief      Page to edit a bank transaction record
 *	\version    $Id: ligne.php,v 1.84 2010/12/27 23:51:20 eldy Exp $
 */

require("./pre.inc.php");

if (! $user->rights->banque->lire && ! $user->rights->banque->consolidate)
accessforbidden();

$langs->load("banks");
$langs->load("compta");
$langs->load("bills");
$langs->load("categories");
if ($conf->adherent->enabled) $langs->load("members");

$rowid=isset($_GET["rowid"])?$_GET["rowid"]:$_POST["rowid"];
$ref=isset($_GET["ref"])?$_GET["ref"]:$_POST["ref"];
$orig_account=isset($_GET["orig_account"])?$_GET["orig_account"]:$_POST["orig_account"];

$html = new Form($db);

/*
 * Actions
 */

if ($user->rights->banque->consolidate && $_GET["action"] == 'dvnext')
{
    $ac = new Account($db);
    $ac->datev_next($_GET["rowid"]);
}

if ($user->rights->banque->consolidate && $_GET["action"] == 'dvprev')
{
    $ac = new Account($db);
    $ac->datev_previous($_GET["rowid"]);
}

if ($_POST["action"] == 'confirm_delete_categ' && $_POST["confirm"] == "yes")
{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid = $rowid AND fk_categ = ".$_GET["cat1"];
    if (! $db->query($sql))
    {
        dol_print_error($db);
    }
}

if ($_POST["action"] == 'class')
{
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid = $rowid AND fk_categ = ".$_POST["cat1"];
    if (! $db->query($sql))
    {
        dol_print_error($db);
    }

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (lineid, fk_categ) VALUES (".$_GET["rowid"].", ".$_POST["cat1"].")";
    if (! $db->query($sql))
    {
        dol_print_error($db);
    }
}

if ($_POST["action"] == "update")
{
    // Avant de modifier la date ou le montant, on controle si ce n'est pas encore rapproche
    $sql = "SELECT b.rappro FROM ".MAIN_DB_PREFIX."bank as b WHERE rowid=".$rowid;
    $result = $db->query($sql);
    if ($result)
    {
        $objp = $db->fetch_object($result);
        if ($objp->rappro)
        die ("Conciliation of a line already conciliated is not possible");
    }

    $db->begin();

    $amount = price2num($_POST['amount']);
    $dateop = dol_mktime(12,0,0,$_POST["dateomonth"],$_POST["dateoday"],$_POST["dateoyear"]);
    $dateval= dol_mktime(12,0,0,$_POST["datevmonth"],$_POST["datevday"],$_POST["datevyear"]);
    $sql = "UPDATE ".MAIN_DB_PREFIX."bank";
    $sql.= " SET label='".addslashes($_POST["label"])."',";
    if (isset($_POST['amount'])) $sql.=" amount='$amount',";
    $sql.= " dateo = '".$db->idate($dateop)."', datev = '".$db->idate($dateval)."',";
    $sql.= " fk_account = ".$_POST['accountid'];
    $sql.= " WHERE rowid = ".$rowid;

    $result = $db->query($sql);
    if ($result)
    {
        $db->commit();
    }
    else
    {
        $db->rollback();
        dol_print_error($db);
    }
}

if ($_POST["action"] == 'type')
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."bank set fk_type='".$_POST["value"]."', num_chq='".$_POST["num_chq"]."' WHERE rowid = $rowid;";
    $result = $db->query($sql);
}

if ($_POST["action"] == 'banque')
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."bank set banque='".addslashes($_POST["banque"])."' WHERE rowid = $rowid;";
    $result = $db->query($sql);
}

if ($_POST["action"] == 'emetteur')
{
    $sql = "UPDATE ".MAIN_DB_PREFIX."bank set emetteur='".addslashes($_POST["emetteur"])."' WHERE rowid = $rowid;";
    $result = $db->query($sql);
}

// Reconcile
if ($user->rights->banque->consolidate && ($_POST["action"] == 'num_releve' || $_POST["action"] == 'setreconcile'))
{
    $num_rel=trim($_POST["num_rel"]);
    $rappro=$_POST['reconciled']?1:0;

    // Check parameters
    if ($rappro && empty($num_rel))
    {
        $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("AccountStatement"));
        $error++;
    }

    if (! $error)
    {
        $db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."bank";
        $sql.= " SET num_releve=".($num_rel?"'".$num_rel."'":"null");
        if (empty($num_rel)) $sql.= ", rappro = 0";
        else $sql.=", rappro = ".$rappro;
        $sql.= " WHERE rowid = ".$rowid;

        dol_syslog("ligne.php sql=".$sql, LOG_DEBUG);
        $result = $db->query($sql);
        if ($result)
        {
            $db->commit();
        }
        else
        {
            $db->rollback();
            dol_print_error($db);
        }
    }
}



/*
 * View
 */

llxHeader();

// On initialise la liste des categories
$sql = "SELECT rowid, label";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_categ";
$sql.= " ORDER BY label";
$result = $db->query($sql);
if ($result)
{
    $var=True;
    $num = $db->num_rows($result);
    $i = 0;
    $options = "<option value=\"0\" selected=\"true\">&nbsp;</option>";
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n";
        $i++;
    }
    $db->free($result);
}

$var=False;
$h=0;


$head[$h][0] = DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$_GET["rowid"];
$head[$h][1] = $langs->trans('Card');
$hselected=$h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/bank/info.php?rowid='.$_GET["rowid"];
$head[$h][1] = $langs->trans("Info");
$h++;

dol_fiche_head($head, $hselected, $langs->trans('LineRecord'),0,'account');

if ($mesg) print '<div class="error">'.$mesg.'</div><br>';

$sql = "SELECT b.rowid,b.dateo as do,b.datev as dv, b.amount, b.label, b.rappro,";
$sql.= " b.num_releve, b.fk_user_author, b.num_chq, b.fk_type, b.fk_account";
$sql.= ",b.emetteur,b.banque";
$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql.= " WHERE rowid=".$rowid;
$sql.= " ORDER BY dateo ASC";
$result = $db->query($sql);
if ($result)
{
    $i = 0; $total = 0;
    if ($db->num_rows($result))
    {

        // Confirmations
        if ($_GET["action"] == 'delete_categ')
        {
            $ret=$html->form_confirm("ligne.php?rowid=".$_GET["rowid"]."&amp;cat1=".$_GET["fk_categ"]."&amp;orig_account=".$orig_account,$langs->trans("RemoveFromRubrique"),$langs->trans("RemoveFromRubriqueConfirm"),"confirm_delete_categ");
            if ($ret == 'html') print '<br>';
        }

        print '<table class="border" width="100%">';

        $objp = $db->fetch_object($result);
        $total = $total + $objp->amount;

        $acct=new Account($db);
        $acct->fetch($objp->fk_account);
        $account = $acct->id;

        $bankline = new AccountLine($db);
        $bankline->fetch($rowid,$ref);

        $links=$acct->get_url($rowid);
        $bankline->load_previous_next_ref('','rowid');

        // Ref
        print '<tr><td width="20%">'.$langs->trans("Ref")."</td>";
        print '<td colspan="4">';
        print $html->showrefnav($bankline,'rowid','',1,'rowid','rowid');
        print '</td>';
        print '</tr>';

        $i++;


        // Account
        print "<tr><td>".$langs->trans("Account")."</td>";
        print '<td colspan="4">';
        print '<a href="account.php?account='.$acct->id.'">'.img_object($langs->trans("ShowAccount"),'account').' '.$acct->label.'</a>';
        print '</td>';
        print '</tr>';

        // Show links of bank transactions
        if (sizeof($links))
        {
            print "<tr><td>".$langs->trans("Links")."</td>";
            print '<td colspan="4">';
            foreach($links as $key=>$val)
            {
                if ($key) print '<br>';
                if ($links[$key]['type']=='payment') {
                    print '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowPayment'),'payment').' ';
                    print $langs->trans("Payment");
                    print '</a>';
                }
                else if ($links[$key]['type']=='payment_supplier') {
                    print '<a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowPayment'),'payment').' ';
                    print $langs->trans("Payment");
                    print '</a>';
                }
                else if ($links[$key]['type']=='company') {
                    print '<a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowCustomer'),'company').' ';
                    print $links[$key]['label'];
                    print '</a>';
                }
                else if ($links[$key]['type']=='sc') {
                    print '<a href="'.DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowSocialContribution'),'bill').' ';
                    print $langs->trans("SocialContribution").($links[$key]['label']?' - '.$links[$key]['label']:'');
                    print '</a>';
                }
                else if ($links[$key]['type']=='payment_sc') {
                    print '<a href="'.DOL_URL_ROOT.'/compta/payment_sc/fiche.php?id='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowPayment'),'payment').' ';
                    print $langs->trans("SocialContributionPayment");
                    print '</a>';
                }
                else if ($links[$key]['type']=='payment_vat') {
                    print '<a href="'.DOL_URL_ROOT.'/compta/tva/fiche.php?id='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowVAT'),'payment').' ';
                    print $langs->trans("VATPayment");
                    print '</a>';
                }
                else if ($links[$key]['type']=='member') {
                    print '<a href="'.DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowMember'),'user').' ';
                    print $links[$key]['label'];
                    print '</a>';
                }
                else if ($links[$key]['type']=='banktransfert') {
                    print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$links[$key]['url_id'].'">';
                    print img_object($langs->trans('ShowTransaction'),'payment').' ';
                    print $langs->trans("TransactionOnTheOtherAccount");
                    print '</a>';
                }
                else {
                    print '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
                    print $links[$key]['label'];
                    print '</a>';
                }
            }
            print '</td></tr>';
        }

        // Type of payment / Number
        print "<tr><td>".$langs->trans("Type")." / ".$langs->trans("Numero")."</td><td colspan=\"3\">";
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print "<form method=\"post\" action=\"ligne.php?rowid=$objp->rowid\">";
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="type">';
            print "<input type=\"hidden\" name=\"orig_account\" value=\"".$orig_account."\">";
            print $html->select_types_paiements($objp->fk_type,"value",'',2);
            print '<input type="text" class="flat" name="num_chq" value="'.(empty($objp->num_chq) ? '' : $objp->num_chq).'">';
            print '</td><td align="center" width="20%"><input type="submit" class="button" value="'.$langs->trans("Update").'">';
            print "</form>";
        }
        else
        {
            print $objp->fk_type.' '.$objp->num_chq.'</td><td>&nbsp;</td>';
        }
        print "</td></tr>";

        // Bank
        print "<tr><td>".$langs->trans("Bank")."</td><td colspan=\"3\">";
        if ($user->rights->banque->modifier)
        {
            print "<form method=\"post\" action=\"ligne.php?rowid=$objp->rowid\">";
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="banque">';
            print "<input type=\"hidden\" name=\"orig_account\" value=\"".$orig_account."\">";
            print '<input type="text" class="flat" size="40" name="banque" value="'.(empty($objp->banque) ? '' : $objp->banque).'">';
            print '</td><td align="center" width="20%"><input type="submit" class="button" value="'.$langs->trans("Update").'">';
            print "</form>";
        }
        else
        {
            print $objp->banque.'&nbsp;</td><td>&nbsp;</td>';
        }
        print "</td></tr>";

        // Transmitter
        print "<tr><td>".$langs->trans("CheckTransmitter")."</td><td colspan=\"3\">";
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print "<form method=\"post\" action=\"ligne.php?rowid=$objp->rowid\">";
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="emetteur">';
            print "<input type=\"hidden\" name=\"orig_account\" value=\"".$orig_account."\">";
            print '<input type="text" class="flat" size="40" name="emetteur" value="'.(empty($objp->emetteur) ? '' : stripslashes($objp->emetteur)).'">';
            print '</td><td align="center" width="20%"><input type="submit" class="button" value="'.$langs->trans("Update").'">';
            print "</form>";
        }
        else
        {
            print $objp->emetteur.'&nbsp;</td><td>&nbsp;</td>';
        }
        print "</td></tr>";

        print '<form name="update" method="post" action="ligne.php?rowid='.$objp->rowid.'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print "<input type=\"hidden\" name=\"action\" value=\"update\">";
        print "<input type=\"hidden\" name=\"orig_account\" value=\"".$orig_account."\">";
        print '<input type="hidden" name="accountid" value="'.$acct->id.'">';

        // Date ope
        print '<tr><td>'.$langs->trans("DateOperation").'</td>';
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print '<td colspan="3">';
            print $html->select_date($db->jdate($objp->do),'dateo','','','','update',1,0,1,$objp->rappro);
            print '</td><td align="center" rowspan="4" width="20%"><input type="submit" class="button" value="'.$langs->trans("Update").'"'.($objp->rappro?' disabled="true"':'').'></td>';
        }
        else
        {
            print '<td colspan="4">';
            print dol_print_date($db->jdate($objp->do),"day");
        }
        print '</td></tr>';

        // Value date
        print "<tr><td>".$langs->trans("DateValue")."</td>";
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print '<td colspan="3">';
            print $html->select_date($db->jdate($objp->dv),'datev','','','','update',1,0,1,$objp->rappro);
            if (! $objp->rappro)
            {
                print ' &nbsp; ';
                print '<a href="'.$_SERVER['PHP_SELF'].'?action=dvprev&amp;account='.$_GET["account"].'&amp;rowid='.$objp->rowid.'">';
                print img_edit_remove() . "</a> ";
                print '<a href="'.$_SERVER['PHP_SELF'].'?action=dvnext&amp;account='.$_GET["account"].'&amp;rowid='.$objp->rowid.'">';
                print img_edit_add() ."</a>";
            }
        }
        else
        {
            print '<td colspan="4">';
            print dol_print_date($db->jdate($objp->dv),"day");
            print '</td>';
        }
        print "</tr>";

        // Description
        print "<tr><td>".$langs->trans("Label")."</td>";
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print '<td colspan="3">';
            print '<input name="label" class="flat" '.($objp->rappro?' disabled="true"':'').' value="';
            if (preg_match('/^\((.*)\)$/i',$objp->label,$reg))
            {
                // Label generique car entre parentheses. On l'affiche en le traduisant
                print $langs->trans($reg[1]);
            }
            else
            {
                print $objp->label;
            }
            print '" size="50">';
        }
        else
        {
            print '<td colspan="4">';
            if (preg_match('/^\((.*)\)$/i',$objp->label,$reg))
            {
                // Label generique car entre parentheses. On l'affiche en le traduisant
                print $langs->trans($reg[1]);
            }
            else
            {
                print $objp->label;
            }
        }
        print '</td></tr>';

        // Amount
        print "<tr><td>".$langs->trans("Amount")."</td>";
        if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
        {
            print '<td colspan="3">';
            print '<input name="amount" class="flat" size="10" '.($objp->rappro?' disabled="true"':'').' value="'.price($objp->amount).'"> '.$langs->trans("Currency".$conf->monnaie);
            print '</td>';
        }
        else
        {
            print '<td colspan="4">';
            print price($objp->amount);
        }
        print "</td></tr>";

        print "</form>";
        print "</table>";

        // Releve rappro
        if ($acct->rappro)  // Si compte rapprochable
        {
            print '<br>'."\n";
            print_fiche_titre($langs->trans("Reconciliation"),'','');
            print "<form method=\"post\" action=\"ligne.php?rowid=$objp->rowid\">";
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="setreconcile">';
            print "<input type=\"hidden\" name=\"orig_account\" value=\"".$orig_account."\">";

            print '<table class="border" width="100%">';

            print '<tr><td width="20%">'.$langs->trans("Conciliation")."</td>";
            if ($user->rights->banque->consolidate)
            {
                print '<td colspan="3">';
                if ($objp->rappro)
                {
                    print $langs->trans("AccountStatement").' <input name="num_rel_bis" class="flat" value="'.$objp->num_releve.'"'.($objp->rappro?' disabled="true"':'').'>';
                    print '<input name="num_rel" type="hidden" value="'.$objp->num_releve.'">';
                }
                else
                {
                    print $langs->trans("AccountStatement").' <input name="num_rel" class="flat" value="'.$objp->num_releve.'"'.($objp->rappro?' disabled="true"':'').'>';
                }
                print '</td><td align="center" rowspan="2" width="20%"><input type="submit" class="button" value="'.$langs->trans("Update").'"></td>';
            }
            else
            {
                print '<td colspan="4">'.$objp->num_releve.'&nbsp;</td>';
            }
            print '</tr>';

            print "<tr><td>".$langs->trans("BankLineConciliated")."</td>";
            if ($user->rights->banque->consolidate)
            {
                print '<td colspan="3">';
                print '<input type="checkbox" name="reconciled" class="flat" '.(isset($_POST["reconciled"])?($_POST["reconciled"]?' checked="true"':''):($objp->rappro?' checked="true"':'')).'">';
                print '</td>';
            }
            else
            {
                print '<td colspan="4">'.yn($objp->rappro).'</td>';
            }
            print '</tr>';

            print "</table>";
            print '</form>';
        }

    }

    $db->free($result);
}
print '</div>';



// List of bank categories

print '<br>';
print '<table class="noborder" width="100%">';

print "<form method=\"post\" action=\"ligne.php?rowid=$rowid&amp;account=$account\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"class\">";
print "<input type=\"hidden\" name=\"orig_account\" value=\"".$orig_account."\">";
print "<tr class=\"liste_titre\"><td>".$langs->trans("Rubriques")."</td><td colspan=\"2\">";
print "<select class=\"flat\" name=\"cat1\">".$options."</select>&nbsp;";
print '<input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
print "</tr>";
print "</form>";

$sql = "SELECT c.label, c.rowid";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_class as a, ".MAIN_DB_PREFIX."bank_categ as c";
$sql.= " WHERE a.lineid=".$rowid." AND a.fk_categ = c.rowid";
$sql.= " ORDER BY c.label";
$result = $db->query($sql);
if ($result)
{
    $var=True;
    $num = $db->num_rows($result);
    $i = 0; $total = 0;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);

        $var=!$var;
        print "<tr $bc[$var]>";

        print "<td>$objp->label</td>";
        print "<td align=\"center\"><a href=\"budget.php?bid=$objp->rowid\">".$langs->trans("ListBankTransactions")."</a></td>";
        print "<td align=\"right\"><a href=\"ligne.php?action=delete_categ&amp;rowid=$rowid&amp;fk_categ=$objp->rowid\">".img_delete($langs->trans("Remove"))."</a></td>";
        print "</tr>";

        $i++;
    }
    $db->free($result);
}
print "</table>";

$db->close();

llxFooter('$Date: 2010/12/27 23:51:20 $ - $Revision: 1.84 $');
?>
