<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *    	\file       htdocs/includes/modules/supplier_order/mod_commande_fournisseur_muguet.php
 *		\ingroup    commande
 *		\brief      Fichier contenant la classe du modele de numerotation de reference de commande fournisseur Muguet
 *		\version    $Id: mod_commande_fournisseur_muguet.php,v 1.7 2010/10/13 15:34:19 eldy Exp $
 */

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/supplier_order/modules_commandefournisseur.php");


/**	    \class      mod_commande_fournisseur_muguet
 *		\brief      Classe du modele de numerotation de reference de commande fournisseur Muguet
 */
class mod_commande_fournisseur_muguet extends ModeleNumRefSuppliersOrders
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $error = '';
	var $nom = 'Muguet';
	var $prefix='CF';


    /**     \brief      Return description of numbering module
     *      \return     string      Text with description
     */
    function info()
    {
    	global $langs;
      	return $langs->trans("SimpleNumRefModelDesc",$this->prefix);
    }


    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample()
    {
        return $this->prefix."0501-0001";
    }


    /**     \brief      Test si les numeros deja en vigueur dans la base ne provoquent pas de
     *                  de conflits qui empechera cette numerotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
    	global $conf,$langs;

        $coyymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur";
		$sql.= " WHERE ref LIKE '".$this->prefix."____-%'";
        $sql.= " AND entity = ".$conf->entity;
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) { $coyymm = substr($row[0],0,6); $max=$row[0]; }
        }
        if (! $coyymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/i',$coyymm))
        {
            return true;
        }
        else
        {
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel',$max);
            return false;
        }
    }

    /**     \brief      Return next value
	*      	\param      objsoc      Object third party
	*      	\param      object		Object
	*       \return     string      Valeur
    */
    function getNextValue($objsoc=0,$object='')
    {
        global $db,$conf;

        // D'abord on recupere la valeur max
        $posindice=8;
        $sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur";
		$sql.= " WHERE ref like '".$this->prefix."____-%'";
        $sql.= " AND entity = ".$conf->entity;

        $resql=$db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) $max = intval($obj->max);
            else $max=0;
        }

		//$date=time();
        $date=$object->date_commande;   // Not always defined
        if (empty($date)) $date=$object->date;  // Creation date is order date for suppliers orders
        $yymm = strftime("%y%m",$date);
        $num = sprintf("%04s",$max+1);

        return $this->prefix.$yymm."-".$num;
    }


    /**     \brief      Renvoie la reference de commande suivante non utilisee
	*      	\param      objsoc      Object third party
	*      	\param      object		Object
    *      	\return     string      Texte descripif
    */
    function commande_get_num($objsoc=0,$object='')
    {
        return $this->getNextValue($objsoc,$object);
    }
}

?>
