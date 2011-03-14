<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
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
 *	\file       htdocs/cashdesk/index.php
 * 	\ingroup	cashdesk
 *  \brief      File to login to point of sales
 *  \version    $Id: index.php,v 1.30 2010/12/14 23:27:26 eldy Exp $
 */

// Set and init common variables
// This include will set: config file variable $dolibarr_xxx, $conf, $langs and $mysoc objects
require_once("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");

$langs->load("admin");
$langs->load("cashdesk");

// Test if user logged
if ( $_SESSION['uid'] > 0 )
{
	header ('Location: '.DOL_URL_ROOT.'/cashdesk/affIndex.php');
	exit;
}

$usertxt=GETPOST('user','',1);


/*
 * View
 */

$form=new Form($db);
$formproduct=new FormProduct($db);

$arrayofcss=array('/cashdesk/css/style.css');
top_htmlhead('','',0,0,'',$arrayofcss);
?>

<body>
<div class="conteneur">
<div class="conteneur_img_gauche">
<div class="conteneur_img_droite">

<h1 class="entete"></h1>

<div class="menu_principal">
</div>

<div class="contenu">
<div class="principal_login">
<?php if (! empty($_GET["err"])) print $_GET["err"]."<br><br>\n"; ?>
<fieldset class="cadre_facturation"><legend class="titre1">Identification</legend>
<form id="frmLogin" method="post" action="index_verif.php">
	<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />

<table>

	<tr>
		<td class="label1"><?php echo $langs->trans("Login"); ?></td>
		<td><input name="txtUsername" class="texte_login" type="text" value="<?php echo $usertxt; ?>" /></td>
	</tr>
	<tr>
		<td class="label1"><?php echo $langs->trans("Password"); ?></td>
		<td><input name="pwdPassword" class="texte_login" type="password"	value="" /></td>
	</tr>
<?php
print "<tr>";
print '<td class="label1">'.$langs->trans("CashDeskThirdPartyForSell").'</td>';
print '<td>';
$disabled=0;
if (! empty($conf->global->CASHDESK_ID_THIRDPARTY)) $disabled=1; // If a particular third party is defined, we disable choice
$form->select_societes($conf->global->CASHDESK_ID_THIRDPARTY,'socid','s.client=1',!$disabled,$disabled,1);
//print '<input name="warehouse_id" class="texte_login" type="warehouse_id" value="" />';
print '</td>';
print "</tr>\n";

if ($conf->stock->enabled)
{
	$langs->load("stocks");
	print "<tr>";
	print '<td class="label1">'.$langs->trans("Warehouse").'</td>';
	print '<td>';
	$disabled=0;
	if (! empty($conf->global->CASHDESK_ID_WAREHOUSE)) $disabled=1;	// If a particular stock is defined, we disable choice
	$formproduct->selectWarehouses($conf->global->CASHDESK_ID_WAREHOUSE,'warehouseid','',!$disabled,$disabled);
	//print '<input name="warehouse_id" class="texte_login" type="warehouse_id" value="" />';
	print '</td>';
	print "</tr>\n";
}
?>
</table>

<center><span class="bouton_login"><input name="sbmtConnexion" type="submit" value="Connexion" /></span></center>

</form>
</fieldset>

<?php
if ($_GET['err'] < 0) {

	echo ('<script type="text/javascript">');
	echo ('	document.getElementById(\'frmLogin\').pwdPassword.focus();');
	echo ('</script>');

} else {

	echo ('<script type="text/javascript">');
	echo ('	document.getElementById(\'frmLogin\').txtUsername.focus();');
	echo ('</script>');

}
?></div>
</div>

<?php include ('affPied.php'); ?></div>
</div>
</div>
</body>
</html>