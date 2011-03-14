<?php
/* Copyright (C) 2010 Juanjo Menent             <jmenent@2byte.es>
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
 *	\file       htdocs/lib/prelevement.lib.php
 *	\brief      Ensemble de fonctions de base pour le module prelevement
 *	\ingroup    propal
 *	\version    $Id: prelevement.lib.php,v 1.3 2010/12/19 18:43:44 hregis Exp $
 *
 * 	Ensemble de fonctions de base de dolibarr sous forme d'include
 */


/**
 *	Prepare head for prelevement screen and return it
 *	@param	    prelevement         class BonPrelevement
 *	@return    	array               head
 */
function prelevement_prepare_head($object)
{
	global $langs, $conf, $user;
	$langs->load("withdrawals");
	
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'prelevement';
	$h++;

	if ($conf->use_preview_tabs)
	{
		$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/bon.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Preview");
		$head[$h][2] = 'preview';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/lignes.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Lines");
	$head[$h][2] = 'lines';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/factures.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Bills");
	$head[$h][2] = 'invoices';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-rejet.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Rejects");
	$head[$h][2] = 'rejects';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/fiche-stat.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Statistics");
	$head[$h][2] = 'statistics';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['prelevement']))
	{
		$i=0;
		foreach ($conf->tabs_modules['prelevement'] as $value)
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