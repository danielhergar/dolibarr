<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copytight (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *		\file   	htdocs/compta/bank/pre.inc.php
 *		\ingroup    compta
 *		\brief  	Fichier gestionnaire du menu compta banque
 *		\version	$Id: pre.inc.php,v 1.48 2010/12/27 20:09:48 eldy Exp $
 */

require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");

$langs->load("banks");
$langs->load("categories");


/**
 * Replace the default llxHeader function
 * @param $head
 * @param $title
 * @param $help_url
 * @param $target
 * @param $disablejs
 * @param $disablehead
 * @param $arrayofjs
 * @param $arrayofcss
 */
function llxHeader($head = '', $title='', $help_url='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
{
	global $db, $user, $conf, $langs;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);	// Show html headers
	top_menu($head, $title, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss);	// Show html headers

	$menu = new Menu();

	// Entry for each bank account
	if ($user->rights->banque->lire)
	{
		$sql = "SELECT rowid, label, courant, rappro, courant";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql.= " WHERE entity = ".$conf->entity;
		$sql.= " AND clos = 0";

		$resql = $db->query($sql);
		if ($resql)
		{
			$numr = $db->num_rows($resql);
			$i = 0;

			if ($numr > 0) 	$menu->add('/compta/bank/index.php',$langs->trans("BankAccounts"),0,$user->rights->banque->lire);

			while ($i < $numr)
			{
				$objp = $db->fetch_object($resql);
				$menu->add_submenu('/compta/bank/fiche.php?id='.$objp->rowid,$objp->label,1,$user->rights->banque->lire);
                if ($objp->rappro && $objp->courant != 2)  // If not cash account and can be reconciliate
                {
				    $menu->add_submenu('/compta/bank/rappro.php?account='.$objp->rowid,$langs->trans("Conciliate"),2,$user->rights->banque->consolidate);
                }
/*
				$menu->add_submenu("/compta/bank/annuel.php?account=".$objp->rowid ,$langs->trans("IOMonthlyReporting"));
				$menu->add_submenu("/compta/bank/graph.php?account=".$objp->rowid ,$langs->trans("Graph"));
				if ($objp->courant != 2) $menu->add_submenu("/compta/bank/releve.php?account=".$objp->rowid ,$langs->trans("AccountStatements"));
*/
				$i++;
			}
		}
		else dol_print_error($db);
		$db->free($resql);
	}

	left_menu('', $help_url, '', $menu->liste, 1);
    main_area();
}
?>
