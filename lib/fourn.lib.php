<?php
/* Copyright (C) 2005-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Marc Barilley / Ocebo <marc@ocebo.com>
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
 *	    \file       htdocs/lib/fourn.lib.php
 *		\brief      Ensemble de fonctions de base pour le module fournisseur
 *		\version    $Id: fourn.lib.php,v 1.19 2010/12/19 18:43:44 hregis Exp $
 */

function facturefourn_prepare_head($object)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Card');
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/contact.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('BillContacts');
	$head[$h][2] = 'contact';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/note.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Notes');
	$head[$h][2] = 'note';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/document.php?facid='.$object->id;
	/*$filesdir = $conf->fournisseur->dir_output.'/facture/'.get_exdir($fac->id,2).$fac->id;
	include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');
	$listoffiles=dol_dir_list($filesdir,'files',1);
	$head[$h][1] = (sizeof($listoffiles)?$langs->trans('DocumentsNb',sizeof($listoffiles)):$langs->trans('Documents'));*/
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/facture/info.php?facid='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['invoice_supplier']))
	{
		$i=0;
		foreach ($conf->tabs_modules['invoice_supplier'] as $value)
		{
			$values=explode(':',$value);
			if ($values[2]) $langs->load($values[2]);
			$head[$h][0] = dol_buildpath(preg_replace('/__ID__/i',$object->id,$values[3]),1);
			$head[$h][1] = $langs->trans($values[1]);
			$head[$h][2] = 'tab'.$values[1];
			$h++;
		}
	}

	return $head;
}


function ordersupplier_prepare_head($object)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("OrderCard");
	$head[$h][2] = 'card';
	$h++;

	if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)
	{
		$langs->load("stocks");
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/dispatch.php?id='.$object->id;
		$head[$h][1] = $langs->trans("OrderDispatch");
		$head[$h][2] = 'dispatch';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/contact.php?id='.$object->id;
	$head[$h][1] = $langs->trans('OrderContact');
	$head[$h][2] = 'contact';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/note.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Notes");
	$head[$h][2] = 'note';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/document.php?id='.$object->id;
	/*$filesdir = $conf->fournisseur->dir_output . "/commande/" . dol_sanitizeFileName($commande->ref);
	include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');
	$listoffiles=dol_dir_list($filesdir,'files',1);
	$head[$h][1] = (sizeof($listoffiles)?$langs->trans('DocumentsNb',sizeof($listoffiles)):$langs->trans('Documents'));*/
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/history.php?id='.$object->id;
	$head[$h][1] = $langs->trans("OrderFollow");
	$head[$h][2] = 'info';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['order_supplier']))
	{
		$i=0;
		foreach ($conf->tabs_modules['order_supplier'] as $value)
		{
			$values=explode(':',$value);
			if ($values[2]) $langs->load($values[2]);
			$head[$h][0] = dol_buildpath(preg_replace('/__ID__/i',$object->id,$values[3]),1);
			$head[$h][1] = $langs->trans($values[1]);
			$head[$h][2] = 'tab'.$values[1];
			$h++;
		}
	}

	return $head;
}


?>