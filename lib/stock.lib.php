<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/lib/stock.lib.php
 *		\brief      Library file with function for stock module
 *		\version    $Id: stock.lib.php,v 1.6 2010/12/19 18:43:44 hregis Exp $
 */

/**
 * Enter description here...
 *
 * @param   $object
 * @return  array
 */
function stock_prepare_head($object)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("WarehouseCard");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/product/stock/mouvement.php?id='.$object->id;
	$head[$h][1] = $langs->trans("StockMovements");
	$head[$h][2] = 'movements';
	$h++;

	/*
	$head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche-valo.php?id='.$object->id;
	$head[$h][1] = $langs->trans("EnhancedValue");
	$head[$h][2] = 'value';
	$h++;
	*/

	/* Disabled because will never be implemented. Table always empty.
	if ($conf->global->STOCK_USE_WAREHOUSE_BY_USER)
	{
		// Should not be enabled by defaut because does not work yet correctly because
		// personnal stocks are not tagged into table llx_entrepot
		$head[$h][0] = DOL_URL_ROOT.'/product/stock/user.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Users");
		$head[$h][2] = 'user';
		$h++;
	}
	*/

	$head[$h][0] = DOL_URL_ROOT.'/product/stock/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;
	
	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['stock']))
	{
		$i=0;
		foreach ($conf->tabs_modules['stock'] as $value)
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