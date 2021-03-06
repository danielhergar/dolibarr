<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.org>
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
 *	\defgroup   clicktodial      Module clicktodial
 *	\brief      Module pour gerer l'appel automatique
 */

/**
 *	\file       htdocs/includes/modules/modClickToDial.class.php
 *	\ingroup    clicktodial
 *	\brief      Fichier de description et activation du module de click to Dial
 *	\version	$Id: modClickToDial.class.php,v 1.21 2010/07/26 23:50:21 eldy Exp $
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 \class      modClickToDial
 \brief      Classe de description et activation du module de Click to Dial
 */

class modClickToDial extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acc�s base
	 */
	function modClickToDial($DB)
	{
		$this->db = $DB ;
		$this->numero = 58 ;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion du Click To Dial";

		$this->version = 'dolibarr';		// 'development' or 'experimental' or 'dolibarr' or version

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 1;
		$this->picto='phoning';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Dependencies
		$this->depends = array();
		$this->requiredby = array();

		// Config pages
		$this->config_page_url = array("clicktodial.php");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'clicktodial';
	}

    /**
     *      \brief      Function called when module is enabled.
     *                  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *                  It also creates data directories.
     *      \return     int             1 if OK, 0 if KO
     */
	function init()
	{
		global $conf;

		$sql = array();

		return $this->_init($sql);
	}

    /**
     *      \brief      Function called when module is disabled.
     *                  Remove from database constants, boxes and permissions from Dolibarr database.
     *                  Data directories are not deleted.
     *      \return     int             1 if OK, 0 if KO
     */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}
}
?>
