<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
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
 *  \file       htdocs/categories/categorie.php
 *  \ingroup    category
 *  \brief      Page to show category tab
 *  \version    $Id: categorie.php,v 1.61.2.1 2011/02/06 23:03:42 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");

$langs->load("categories");
$langs->load("products");

$mesg=isset($_GET["mesg"])?'<div class="ok">'.$_GET["mesg"].'</div>':'';

$dbtablename = '';


// For categories on third parties
if (! empty($_REQUEST["socid"])) {
	$_REQUEST["id"]=$_REQUEST["socid"];
}
if (! isset($_REQUEST["type"])) $_REQUEST["type"]=0;
if ($_REQUEST["type"] == 1) $_GET["socid"]=$_REQUEST["id"];
if ($_REQUEST["type"] == 2) $_GET["socid"]=$_REQUEST["id"];

if ($_REQUEST["id"] || $_REQUEST["ref"])
{
	if ($_REQUEST["type"] == 0) {
		$type = 'product';
		$objecttype = 'produit|service&categorie';
		$objectid = isset($_REQUEST["id"])?$_REQUEST["id"]:(isset($_REQUEST["ref"])?$_REQUEST["ref"]:'');
		$dbtablename = 'product';
		$fieldid = isset($_REQUEST["ref"])?'ref':'rowid';
	}
	if ($_REQUEST["type"] == 1) {
		$type = 'fournisseur'; $socid = isset($_REQUEST["socid"])?$_REQUEST["socid"]:'';
		$objecttype = 'societe&categorie';
		$objectid = isset($_REQUEST["id"])?$_REQUEST["id"]:(isset($_REQUEST["socid"])?$_REQUEST["socid"]:'');
		$fieldid = 'rowid';
	}
	if ($_REQUEST["type"] == 2) {
		$type = 'societe'; $socid = isset($_REQUEST["socid"])?$_REQUEST["socid"]:'';
		$objecttype = 'societe&categorie';
		$objectid = isset($_REQUEST["id"])?$_REQUEST["id"]:(isset($_REQUEST["socid"])?$_REQUEST["socid"]:'');
		$fieldid = 'rowid';
	}
	if ($_REQUEST["type"] == 3) {
		$type = 'member';
		$objecttype = 'adherent&categorie';
		$objectid = isset($_REQUEST["id"])?$_REQUEST["id"]:(isset($_REQUEST["ref"])?$_REQUEST["ref"]:'');
		$dbtablename = 'adherent';
		$fieldid = isset($_REQUEST["ref"])?'ref':'rowid';
	}
}

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,$objecttype,$objectid,$dbtablename,'','',$fieldid);


/*
 *	Actions
 */

//Suppression d'un objet d'une categorie
if ($_REQUEST["removecat"])
{
	if ($_REQUEST["type"]==0 && ($user->rights->produit->creer || $user->rights->service->creer))
	{
		require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
		$object = new Product($db);
		if ($_REQUEST["ref"]) $result = $object->fetch('',$_REQUEST["ref"]);
		if ($_REQUEST["id"])  $result = $object->fetch($_REQUEST["id"]);
		$type = 'product';
	}
	if ($_REQUEST["type"]==1 && $user->rights->societe->creer)
	{
		$object = new Societe($db);
		$result = $object->fetch($objectid);
	}
	if ($_REQUEST["type"]==2 && $user->rights->societe->creer)
	{
		$object = new Societe($db);
		$result = $object->fetch($objectid);
	}
	if ($_REQUEST["type"] == 3 && $user->rights->adherent->creer)
	{
		require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
		$object = new Adherent($db);
		$result = $object->fetch($objectid);
	}
	$cat = new Categorie($db);
	$result=$cat->fetch($_REQUEST["removecat"]);

	$result=$cat->del_type($object,$type);
}

// Add object into a category
if (isset($_REQUEST["catMere"]) && $_REQUEST["catMere"]>=0)
{
	$_GET["id"]=$_REQUEST["id"];
	$_GET["type"]=$_REQUEST["type"];

	if ($_REQUEST["type"]==0 && ($user->rights->produit->creer || $user->rights->service->creer))
	{
		require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
		$object = new Product($db);
		if ($_REQUEST["ref"]) $result = $object->fetch('',$_REQUEST["ref"]);
		if ($_REQUEST["id"])  $result = $object->fetch($_REQUEST["id"]);
		$type = 'product';
	}
	if ($_REQUEST["type"]==1 && $user->rights->societe->creer)
	{
		$object = new Societe($db);
		$result = $object->fetch($objectid);
		$type = 'fournisseur';
	}
	if ($_REQUEST["type"]==2 && $user->rights->societe->creer)
	{
		$object = new Societe($db);
		$result = $object->fetch($objectid);
		$type = 'societe';
	}
	if ($_REQUEST["type"]==3 && $user->rights->adherent->creer)
	{
		require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
		$object = new Adherent($db);
		$result = $object->fetch($objectid);
		$type = 'member';
	}
	$cat = new Categorie($db);
	$result=$cat->fetch($_REQUEST["catMere"]);

	$result=$cat->add_type($object,$type);
	if ($result >= 0)
	{
		$mesg='<div class="ok">'.$langs->trans("WasAddedSuccessfully",$cat->label).'</div>';
	}
	else
	{
		if ($cat->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') $mesg='<div class="warning">'.$langs->trans("ObjectAlreadyLinkedToCategory").'</div>';
		else $mesg='<div class="error">'.$langs->trans("Error").' '.$cat->error.'</div>';
	}

}


/*
 *	View
 */

$html = new Form($db);

/*
 * Fiche categorie de client et/ou fournisseur
 */
if ($_GET["socid"])
{
	require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
	require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");

	$langs->load("companies");
	if ($conf->notification->enabled) $langs->load("mails");

	/*
	 * Creation de l'objet client/fournisseur correspondant au socid
	 */
	$soc = new Societe($db);
	$result = $soc->fetch($_GET["socid"]);
	llxHeader("","",$langs->trans("Category"));


	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'category', $langs->trans("ThirdParty"),0,'company');

	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans("Name").'</td><td colspan="3">';
	print $html->showrefnav($soc,'socid','',($user->societe_id?0:1),'rowid','nom');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';

	if ($soc->client)
	{
		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $soc->code_client;
		if ($soc->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	if ($soc->fournisseur)
	{
		print '<tr><td>';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $soc->code_fournisseur;
		if ($soc->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
	}

	if ($conf->global->MAIN_MODULE_BARCODE)
	{
		print '<tr><td>'.$langs->trans('Gencod').'</td><td colspan="3">'.$soc->gencod.'</td></tr>';
	}

	// Address
	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->address)."</td></tr>";

	// Zip / Town
	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$soc->cp."</td>";
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$soc->ville."</td></tr>";

	// Country
	if ($soc->pays) {
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		$img=picto_from_langcode($soc->pays_code);
		print ($img?$img.' ':'');
		print $soc->pays;
		print '</td></tr>';
	}

	// Phone
	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->tel,$soc->pays_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->pays_code,0,$soc->id,'AC_FAX').'</td></tr>';

	// EMail
	print '<tr><td>'.$langs->trans('EMail').'</td><td>';
	print dol_print_email($soc->email,0,$soc->id,'AC_EMAIL');
	print '</td>';

	// Web
	print '<td>'.$langs->trans('Web').'</td><td>';
	print dol_print_url($soc->url);
	print '</td></tr>';

	// Assujeti a TVA ou pas
	print '<tr>';
	print '<td nowrap="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($soc->tva_assuj);
	print '</td>';
	print '</tr>';

	print '</table>';

	print '</div>';

	if ($mesg) print($mesg);

	if ($soc->client) formCategory($db,$soc,2);

	if ($soc->client && $soc->fournisseur) print '<br><br>';

	if ($soc->fournisseur) formCategory($db,$soc,1);
}
else if ($_GET["id"] || $_GET["ref"])
{
	if ($_GET["type"] == 0)
	{
		$langs->load("products");

		/*
		 * Fiche categorie de produit
		 */
		require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
		require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

		// Produit
		$product = new Product($db);
		if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
		if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

		llxHeader("","",$langs->trans("CardProduct".$product->type));


		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'category', $titre,0,$picto);


		print '<table class="border" width="100%">';

		// Ref
		print "<tr>";
		print '<td width="15%">'.$langs->trans("Ref").'</td><td>';
		print $html->showrefnav($product,'ref','',1,'ref');
		print '</td>';
		print '</tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
		print '</tr>';

		// Status (to sell)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')'.'</td><td>';
		print $product->getLibStatut(2,0);
		print '</td></tr>';

		// Status (to buy)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')'.'</td><td>';
		print $product->getLibStatut(2,1);
		print '</td></tr>';

		print '</table>';

		print '</div>';

		if ($mesg) print($mesg);

		formCategory($db,$product,0);
	}

	if ($_GET["type"] == 3)
	{
		$langs->load("members");

		/*
		 * Fiche categorie d'adherent
		 */
		require_once(DOL_DOCUMENT_ROOT."/lib/member.lib.php");
		require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
		require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php");

		// Produit
		$member = new Adherent($db);
		if ($_GET["ref"]) $result = $member->fetch('',$_GET["ref"]);
		if ($_GET["id"]) $result = $member->fetch($_GET["id"]);

		$adht = new AdherentType($db);
		$adht->fetch($member->typeid);

		llxHeader("","",$langs->trans("Member"));


		$head=member_prepare_head($member, $user);
		$titre=$langs->trans("Member");
		$picto='user';
		dol_fiche_head($head, 'category', $titre,0,$picto);


		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td class="valeur">';
		print $html->showrefnav($member,'rowid');
		print '</td></tr>';

		// Nom
		print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur">'.$member->nom.'&nbsp;</td>';
		print '</tr>';

		// Prenom
		print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur">'.$member->prenom.'&nbsp;</td>';
		print '</tr>';

		// Login
		print '<tr><td>'.$langs->trans("Login").'</td><td class="valeur">'.$member->login.'&nbsp;</td></tr>';

		// Type
		print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

		// Status
		print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$member->getLibStatut(4).'</td></tr>';

		print '</table>';

		print '</div>';

		if ($mesg) print($mesg);

		formCategory($db,$member,3);
	}
}


/**
 * Fonction Barre d'actions
 */
function formCategory($db,$object,$typeid)
{
	global $user,$langs,$html,$bc;

	if ($typeid == 0) $title = $langs->trans("ProductsCategoriesShort");
	if ($typeid == 1) $title = $langs->trans("SuppliersCategoriesShort");
	if ($typeid == 2) $title = $langs->trans("CustomersProspectsCategoriesShort");
	if ($typeid == 3) $title = $langs->trans("MembersCategoriesShort");

	// Formulaire ajout dans une categorie
	print '<br>';
	print_fiche_titre($title,'','');
	print '<form method="post" action="'.DOL_URL_ROOT.'/categories/categorie.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="typeid" value="'.$typeid.'">';
	print '<input type="hidden" name="type" value="'.$typeid.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td width="40%">';
	print $langs->trans("ClassifyInCategory");
	print $html->select_all_categories($typeid);
	print '</td><td>';
	print '<input type="submit" class="button" value="'.$langs->trans("Classify").'"></td>';
	if ($user->rights->categorie->creer)
	{
		print '<td align="right">';
		print "<a href='".DOL_URL_ROOT."/categories/fiche.php?action=create&amp;origin=".$object->id."&type=".$typeid."&urlfrom=".urlencode($_SERVER["PHP_SELF"].'?'.(($typeid==1||$typeid==2)?'socid':'id').'='.$object->id.'&type='.$typeid)."'>";
		print img_picto($langs->trans("Create"),'filenew');
		print "</a>";
		print '</td>';
	}
	print '</tr>';
	print '</table>';
	print '</form>';
	print '<br/>';


	$c = new Categorie($db);
	$cats = $c->containing($object->id,$typeid);

	if (sizeof($cats) > 0)
	{
		if ($typeid == 0) $title=$langs->trans("ProductIsInCategories");
		if ($typeid == 1) $title=$langs->trans("CompanyIsInSuppliersCategories");
		if ($typeid == 2) $title=$langs->trans("CompanyIsInCustomersCategories");
		if ($typeid == 3) $title=$langs->trans("MemberIsInCategories");
		print "\n";
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$title.':</td></tr>';

		$var = true;
		foreach ($cats as $cat)
		{
			$ways = $cat->print_all_ways();
			foreach ($ways as $way)
			{
				$var = ! $var;
				print "<tr ".$bc[$var].">";

				// Categorie
				print "<td>";
				//$c->id=;
				//print $c->getNomUrl(1);
				print img_object('','category').' '.$way."</td>";

				// Lien supprimer
				print '<td align="right">';
				$permission=0;
				if ($typeid == 0) $permission=($user->rights->produit->creer || $user->rights->service->creer);
				if ($typeid == 1) $permission=$user->rights->societe->creer;
				if ($typeid == 2) $permission=$user->rights->societe->creer;
				if ($typeid == 3) $permission=$user->rights->adherent->creer;
				if ($permission)
				{
					print "<a href= '".DOL_URL_ROOT."/categories/categorie.php?".(empty($_REQUEST["socid"])?'id':'socid')."=".$object->id.(empty($_REQUEST["socid"])?"&amp;type=".$typeid."&amp;typeid=".$typeid:'')."&amp;removecat=".$cat->id."'>";
					print img_delete($langs->trans("DeleteFromCat")).' ';
					print $langs->trans("DeleteFromCat")."</a>";
				}
				else
				{
					print '&nbsp;';
				}
				print "</td>";

				print "</tr>\n";
			}
		}
		print "</table>\n";
	}
	else if($cats < 0)
	{
		print $langs->trans("ErrorUnknown");
	}
	else
	{
		if ($typeid == 0) $title=$langs->trans("ProductHasNoCategory");
		if ($typeid == 1) $title=$langs->trans("CompanyHasNoCategory");
		if ($typeid == 2) $title=$langs->trans("CompanyHasNoCategory");
		if ($typeid == 3) $title=$langs->trans("MemberHasNoCategory");
		print $title;
		print "<br/>";
	}
}

$db->close();

llxFooter('$Date: 2011/02/06 23:03:42 $ - $Revision: 1.61.2.1 $');
?>
