<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 * 	    \file       htdocs/compta/deplacement/index.php
 * 		\brief      Page liste des deplacements
 * 		\version	$Id: index.php,v 1.39.2.1 2011/01/21 18:26:23 hregis Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/class/tva.class.php");

$langs->load("companies");
$langs->load("users");
$langs->load("trips");

// Security check
$socid = $_GET["socid"]?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'deplacement','','');


llxHeader();

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="d.dated";
$limit = $conf->liste_limit;


$sql = "SELECT s.nom, s.rowid as socid,";								// Ou
$sql.= " d.rowid, d.type, d.dated as dd, d.km, ";		// Comment
$sql.= " u.name, u.firstname";											// Qui
$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
$sql.= ", ".MAIN_DB_PREFIX."deplacement as d";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on d.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
$sql.= " WHERE d.fk_user = u.rowid";
$sql.= " AND d.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
$sql.= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1 ,$offset);

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);

  print_barre_liste($langs->trans("ListOfFees"), $page, "index.php","&socid=$socid",$sortfield,$sortorder,'',$num);

  $i = 0;
  print '<table class="noborder" width="100%">';
  print "<tr class=\"liste_titre\">";
  print_liste_field_titre($langs->trans("Ref"),"index.php","d.rowid","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Type"),"index.php","d.type","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Date"),"index.php","d.dated","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Person"),"index.php","u.name","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("FeesKilometersOrAmout"),"index.php","d.km","","&socid=$socid",'align="right"',$sortfield,$sortorder);
  print "</tr>\n";

  $var=true;
  while ($i < $num)
    {
      $objp = $db->fetch_object($resql);

      $soc = new Societe($db);
      if ($objp->socid) $soc->fetch($objp->socid);

      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowTrip"),"trip").' '.$objp->rowid.'</a></td>';
      print '<td>'.$langs->trans($objp->type).'</td>';
      print '<td>'.dol_print_date($db->jdate($objp->dd),'day').'</td>';
      if ($objp->socid) print '<td>'.$soc->getNomUrl(1).'</td>';
      else print '<td>&nbsp;</td>';
      print '<td align="left"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowUser"),"user").' '.$objp->firstname.' '.$objp->name.'</a></td>';
      print '<td align="right">'.$objp->km.'</td>';
      print "</tr>\n";

      $i++;
    }

  print "</table>";
  $db->free($resql);
}
else
{
  dol_print_error($db);
}
$db->close();

llxFooter('$Date: 2011/01/21 18:26:23 $ - $Revision: 1.39.2.1 $');
?>
