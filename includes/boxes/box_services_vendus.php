<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       htdocs/includes/boxes/box_services_vendus.php
 *		\ingroup    produits,services
 *      \brief      Module de generation de l'affichage de la box services_vendus
 *		\version	$Id: box_services_vendus.php,v 1.50 2010/04/28 08:35:57 grandoc Exp $
 */

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_services_vendus extends ModeleBoxes {

	var $boxcode="lastproductsincontract";
	var $boximg="object_product";
	var $boxlabel;
	var $depends = array("service","contrat");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();

	/**
	 *      \brief      Constructeur de la classe
	 */
	function box_services_vendus()
	{
		global $langs;
		$langs->load("boxes");

		$this->boxlabel=$langs->trans("BoxLastProductsInContract");
	}

	/**
	 *      \brief      Charge les donnees en memoire pour affichage ulterieur
	 *      \param      $max        Nombre maximum d'enregistrements a charger
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;

		include_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
		$contratlignestatic=new ContratLigne($db);

		$this->info_box_head = array('text' => $langs->trans("BoxLastProductsInContract",$max));

		if ($user->rights->service->lire && $user->rights->contrat->lire)
		{
			$sql = "SELECT s.nom, s.rowid as socid,";
			$sql.= " c.rowid,";
			$sql.= " cd.rowid as cdid, cd.tms as datem, cd.statut,";
			$sql.= " p.rowid as pid, p.label, p.fk_product_type";
			$sql.= " FROM (".MAIN_DB_PREFIX."societe as s";
			$sql.= ", ".MAIN_DB_PREFIX."contrat as c";
			$sql.= ", ".MAIN_DB_PREFIX."contratdet as cd";
			$sql.= ", ".MAIN_DB_PREFIX."product as p";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql.= ")";
			$sql.= " WHERE s.rowid = c.fk_soc";
			$sql.= " AND s.entity = ".$conf->entity;
			if (!$user->rights->produit->hidden) $sql.=' AND (p.hidden=0 OR p.fk_product_type != 0)';
			if (!$user->rights->service->hidden) $sql.=' AND (p.hidden=0 OR p.fk_product_type != 1)';
			$sql.= " AND c.rowid = cd.fk_contrat";
			$sql.= " AND cd.fk_product = p.rowid";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
			$sql.= $db->order("c.tms","DESC");
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$now=gmmktime();

				$i = 0;

				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$datem=$db->jdate($objp->datem);

					// Multilangs
					if ($conf->global->MAIN_MULTILANGS) // si l'option est active
					{
						$sqld = "SELECT label";
						$sqld.= " FROM ".MAIN_DB_PREFIX."product_lang";
						$sqld.= " WHERE fk_product=".$objp->pid;
						$sqld.= " AND lang='". $langs->getDefaultLang() ."'";
						$sqld.= " LIMIT 1";

						$resultd = $db->query($sqld);
						if ($resultd)
						{
							$objtp = $db->fetch_object($resultd);
							if ($objtp->label != '') $objp->label = $objtp->label;
						}
					}

					$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
                    'logo' => ($objp->fk_product_type==1?'object_service':'object_product'),
                    'url' => DOL_URL_ROOT."/contrat/fiche.php?id=".$objp->rowid);

					$this->info_box_contents[$i][1] = array('td' => 'align="left"',
                    'text' => $objp->label,
                    'maxlength' => 16,
                    'url' => DOL_URL_ROOT."/contrat/fiche.php?id=".$objp->rowid);

					$this->info_box_contents[$i][2] = array('td' => 'align="left" width="16"',
                    'logo' => 'company',
                    'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

					$this->info_box_contents[$i][3] = array('td' => 'align="left"',
                    'text' => $objp->nom,
                    'maxlength' => 28,
                    'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

					$this->info_box_contents[$i][4] = array('td' => 'align="right"',
                    'text' => dol_print_date($datem,'day'));

					$this->info_box_contents[$i][5] = array('td' => 'align="right" width="18"',
                    'text' => $contratlignestatic->LibStatut($objp->statut,3)
					);

					$i++;
				}
				if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoContractedProducts"));
			}
			else
			{
				$this->info_box_contents[0][0] = array(	'td' => 'align="left"',
    	        										'maxlength'=>500,
	            										'text' => ($db->error().' sql='.$sql));
			}
		}
		else {
			$this->info_box_contents[0][0] = array('td' => 'align="left"',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
		}

	}

	function showBox()
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}

?>
