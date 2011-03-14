<?php
/* Copyright (C) 2006-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 *	\file       htdocs/accountancy/class/accountancysystem.class.php
 * 	\ingroup    accounting
 * 	\brief      File of class to manage accountancy systems
 * 	\version    $Id: accountancysystem.class.php,v 1.2 2010/07/21 17:24:43 eldy Exp $
 */


/**	\class 		AccountancySystem
 *	\brief 		Classe to manage accountancy systems
 */
class AccountancySystem
{
	var $db;
	var $error;

	var $rowid;
	var $fk_pcg_version;
	var $pcg_type;
	var $pcg_subtype;
	var $label;
	var $account_number;
	var $account_parent;


	/**
	 *    \brief  Constructor of class
	 *    \param  DB          Database handler
	 *    \param  id          Id compte (0 by default)
	 */
	function AccountancySystem($DB, $id=0)
	{
		$this->db = $DB;
		$this->id   = $id ;
	}


	/**
	 *    \brief  	Insert accountancy system name into database
	 *    \param  	user 	User making insert
	 *    \return	int		<0 if KO, Id of line if OK
	 */
	function create($user)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."accountingsystem";
		$sql.= " (date_creation, fk_user_author, numero,intitule)";
		$sql.= " VALUES (".$this->db->idate(mktime()).",".$user->id.",'".$this->numero."','".$this->intitule."')";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."accountingsystem");

			if ($id > 0)
			{
				$this->id = $id;
				$result = $this->id;
			}
			else
			{
				$result = -2;
				$this->error="AccountancySystem::Create Erreur $result";
				dol_syslog($this->error, LOG_ERR);
			}
		}
		else
		{
			$result = -1;
			$this->error="AccountancySystem::Create Erreur $result";
			dol_syslog($this->error, LOG_ERR);
		}

		return $result;
	}

}
?>
