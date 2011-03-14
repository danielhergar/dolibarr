<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
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
 *  \file       htdocs/admin/system/modules.php
 *  \brief      Page to list all modules
 *  \version    $Id: modules.php,v 1.12.2.2 2011/01/22 09:54:55 eldy Exp $
 */

require("../../main.inc.php");

$langs->load("admin");
$langs->load("install");
$langs->load("other");

if (!$user->admin)
accessforbidden();


/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("AvailableModules"),'','setup');

print $langs->trans("ToActivateModule").'<br>';
print "<br>\n";

$modules = array();
$modules_names = array();
$modules_files = array();

// Load list of modules
foreach($conf->file->dol_document_root as $searchdir)
{
	$dirtoscan = $searchdir . "/includes/modules/";
	$handle=opendir($dirtoscan);
    if (is_resource($handle))
    {
    	while (($file = readdir($handle))!==false)
    	{
    		if (is_readable($dirtoscan.$file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php')
    		{
    			$modName = substr($file, 0, dol_strlen($file) - 10);

    			if ($modName)
    			{
    				include_once($dirtoscan.$file);
    				$objMod = new $modName($db);

    				$modules[$objMod->numero]=$objMod;
    				$modules_names[$objMod->numero]=$objMod->name;
    				$modules_files[$objMod->numero]=$file;
    				$picto[$objMod->numero]=(isset($objMod->picto) && $objMod->picto)?$objMod->picto:'generic';
    			}
    		}
    	}
    	closedir($handle);
    }
}
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Modules").'</td>';
print '<td>'.$langs->trans("Version").'</td>';
print '<td align="center">'.$langs->trans("Id Module").'</td>';
print '<td>'.$langs->trans("Id Permissions").'</td>';
print '</tr>';
$var=false;
$sortorder=$modules_names;
ksort($sortorder);
$rights_ids = array();
foreach($sortorder as $numero=>$name)
{
	$idperms="";
	$var=!$var;
	// Module
	print "<tr $bc[$var]><td width=\"300\" nowrap=\"nowrap\">";
	$alt=$name.' - '.$modules_files[$numero];
    if (! empty($picto[$numero]))
    {
       	if (preg_match('/^\//',$picto[$numero])) print img_picto($alt,$picto[$numero],'width="14px"',1);
       	else print img_object($alt,$picto[$numero],'width="14px"');
    }
    else
    {
      	print img_object($alt,$picto[$numero],'width="14px"');
    }
	print ' '.$modules[$numero]->getName();
	print "</td>";
	// Version
	print '<td>'.$modules[$numero]->getVersion().'</td>';
	// Id
	print '<td align="center">'.$numero.'</td>';
	// Permissions
	if ($modules[$numero]->rights)
	{
		foreach($modules[$numero]->rights as $rights)
		{
			$idperms.=($idperms?", ":"").$rights[0];
			array_push($rights_ids, $rights[0]);
		}
	}
	print '<td>'.($idperms?$idperms:"&nbsp;").'</td>';
	print "</tr>\n";
}
print '</table>';
print '<br>';
sort($rights_ids);
foreach($rights_ids as $right_id)
{
	if ($old == $right_id)
	print "Warning duplicate id on permission : ".$right_id."<br>";
	$old = $right_id;
}

llxFooter('$Date: 2011/01/22 09:54:55 $ - $Revision: 1.12.2.2 $');
?>
