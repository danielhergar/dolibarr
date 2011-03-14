<?php
/* Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/includes/menus/barre_left/empty.php
		\brief      This is an example of an empty left menu handler
		\version    $Id: empty.php,v 1.10.2.1 2010/08/26 12:39:23 eldy Exp $
*/

/**
        \class      MenuLeftTop
	    \brief      Class for left empty menu
*/
class MenuLeft {

    var $require_top=array("empty");     // If this top menu handler must be used with a particular left menu handler

    var $db;
    var $menu_array;
    var $menu_array_after;


    /**
     *  \brief      Constructor
     *  \param      db                  Database handler
     *  \param      menu_array          Table of menu entries to show before entries of menu handler
     *  \param      menu_array_after    Table of menu entries to show after entries of menu handler
     */
    function MenuLeft($db,&$menu_array,&$menu_array_after)
    {
        $this->db=$db;
        $this->menu_array=$menu_array;
        $this->menu_array_after=$menu_array_after;
    }


    /**
     *    \brief      Show menu
     */
    function showmenu()
    {
        global $user,$conf,$langs,$dolibarr_main_db_name;
        $newmenu = new Menu();

	    // Put here left menu entries
	    // ***** START *****

		$langs->load("admin");	// Load translation file admin.lang
		$newmenu->add(DOL_URL_ROOT."/admin/index.php?leftmenu=setup", $langs->trans("Setup"),0);
		$newmenu->add(DOL_URL_ROOT."/admin/company.php", $langs->trans("MenuCompanySetup"),1);
		$newmenu->add(DOL_URL_ROOT."/admin/modules.php", $langs->trans("Modules"),1);
		$newmenu->add(DOL_URL_ROOT."/admin/menus.php", $langs->trans("Menus"),1);
		$newmenu->add(DOL_URL_ROOT."/admin/ihm.php", $langs->trans("GUISetup"),1);
		$newmenu->add(DOL_URL_ROOT."/admin/boxes.php", $langs->trans("Boxes"),1);
		$newmenu->add(DOL_URL_ROOT."/admin/delais.php",$langs->trans("Alerts"),1);
		$newmenu->add(DOL_URL_ROOT."/admin/perms.php", $langs->trans("Security"),1);
		$newmenu->add(DOL_URL_ROOT."/admin/mails.php", $langs->trans("EMails"),1);
		$newmenu->add(DOL_URL_ROOT."/admin/limits.php", $langs->trans("Limits"),1);
		$newmenu->add(DOL_URL_ROOT."/admin/dict.php", $langs->trans("DictionnarySetup"),1);
		$newmenu->add(DOL_URL_ROOT."/admin/const.php", $langs->trans("OtherSetup"),1);

	    // ***** END *****

        // do not change code after this

        // override menu_array by value array in $newmenu
		$this->menu_array=$newmenu->liste;

        $alt=0;
        for ($i = 0 ; $i < sizeof($this->menu_array) ; $i++)
        {
            $alt++;
            if (empty($this->menu_array[$i]['level']))
            {
                if (($alt%2==0))
                {
                    print '<div class="blockvmenuimpair">'."\n";
                }
                else
                {
                    print '<div class="blockvmenupair">'."\n";
                }
            }

        	// Place tabulation
			$tabstring='';
			$tabul=($this->menu_array[$i]['level'] - 1);
			if ($tabul > 0)
			{
				for ($j=0; $j < $tabul; $j++)
				{
					$tabstring.='&nbsp; &nbsp;';
				}
			}

			if ($this->menu_array[$i]['level'] == 0) {
				if ($this->menu_array[$i]['enabled'])
				{
					print '<div class="menu_titre">'.$tabstring.'<a class="vmenu" href="'.$this->menu_array[$i]['url'].'"'.($this->menu_array[$i]['target']?' target="'.$this->menu_array[$i]['target'].'"':'').'>'.$this->menu_array[$i]['titre'].'</a></div>'."\n";
				}
				else
				{
					print '<div class="menu_titre">'.$tabstring.'<font class="vmenudisabled">'.$this->menu_array[$i]['titre'].'</font></div>'."\n";
				}
				print '<div class="menu_top"></div>'."\n";
            }

            if ($this->menu_array[$i]['level'] > 0) {
				print '<div class="menu_contenu">';

            	if ($this->menu_array[$i]['enabled'])
                    print $tabstring.'<a class="vsmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
                else
                    print $tabstring.'<font class="vsmenudisabled">'.$this->menu_array[$i]['titre'].'</font><br>';

				print '</div>'."\n";
            }

			// If next is a new block or end
			if (empty($this->menu_array[$i+1]['level']))
			{
				print '<div class="menu_end"></div>'."\n";
				print "</div>\n";
			}
		}
    }

}

?>
