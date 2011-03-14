<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/comm/action/class/actioncomm.class.php
 *       \ingroup    commercial
 *       \brief      File of class to manage agenda events (actions)
 *       \version    $Id: actioncomm.class.php,v 1.21.2.1 2011/01/26 22:33:14 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php');


/**     \class      ActionComm
 *	    \brief      Class to manage agenda events (actions)
 */
class ActionComm extends CommonObject
{
	var $db;
	var $error;
	var $errors=array();
	var $element='action';
	var $table_element = 'actioncomm';
	var $ismultientitymanaged = 2;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    var $type_id;
    var $type_code;
    var $type;

    var $id;
    var $label;

    var $datec;			// Date creation record (datec)
    var $datem;			// Date modification record (tms)
    var $author;		// Object user that create action
    var $usermod;		// Object user that modified action

    var $datep;			// Date action start (datep)
    var $datef;			// Date action end (datep2)
    var $durationp = -1;
    //var $date;			// Date action realise debut (datea)	// deprecated
    //var $dateend; 		// Date action realise fin (datea2)		// deprecated
    //var $durationa = -1;	// deprecated
	var $priority;
	var $fulldayevent = 0;  // 1=Event on full day
	var $punctual = 1;

    var $usertodo;		// Object user that must do action
    var $userdone;	 	// Object user that did action

    var $societe;		// Company linked to action (optionnal)
    var $contact;		// Contact linked tot action (optionnal)
    var $fk_project;	// Id of project (optionnal)

    var $note;
    var $percentage;

    // Properties for links to other tables
    var $orderrowid;
    var $propalrowid;
    var $facid;
    var $supplierorderrowid;
    var $supplierinvoicerowid;


    /**
     *      \brief      Constructeur
     *      \param      db      Handler d'acces base de donnee
     */
    function ActionComm($db)
    {
        $this->db = $db;
        /*
        $this->societe = new Societe($db);
        $this->author = new User($db);
        $this->usermod = new User($db);
        $this->usertodo = new User($db);
        $this->userdone = new User($db);
        if (class_exists("Contact"))
        {
            $this->contact = new Contact($db);
        }
		*/
    }

    /**
     *    Add an action into database
     *    @param      user      	auteur de la creation de l'action
 	 *    @param      notrigger		1 ne declenche pas les triggers, 0 sinon
     *    @return     int         	id de l'action creee, < 0 if KO
     */
    function add($user,$notrigger=0)
    {
        global $langs,$conf;

		$now=dol_now();

		// Clean parameters
		$this->label=dol_trunc(trim($this->label),128);
		$this->location=dol_trunc(trim($this->location),128);
		$this->note=dol_htmlcleanlastbr(trim($this->note));
        if (empty($this->percentage))   $this->percentage = 0;
        if (empty($this->priority))     $this->priority = 0;
        if (empty($this->fulldayevent)) $this->fuldayevent = 0;
        if (empty($this->punctual))     $this->punctual = 0;
        if ($this->percentage > 100) $this->percentage = 100;
        if ($this->percentage == 100 && ! $this->dateend) $this->dateend = $this->date;
        if ($this->datep && $this->datef)   $this->durationp=($this->datef - $this->datep);
		if ($this->date  && $this->dateend) $this->durationa=($this->dateend - $this->date);
		if ($this->datep && $this->datef && $this->datep > $this->datef) $this->datef=$this->datep;
		if ($this->date  && $this->dateend && $this->date > $this->dateend) $this->dateend=$this->date;
        if ($this->fk_project < 0) $this->fk_project = 0;

		if (! $this->type_id && $this->type_code)
		{
			# Get id from code
			$cactioncomm=new CActionComm($this->db);
			$result=$cactioncomm->fetch($this->type_code);
			if ($result)
			{
				$this->type_id=$cactioncomm->id;
			}
			else
			{
				$this->error=$cactioncomm->error;
				return -1;
			}
		}

		// Check parameters
		if (! $this->type_id)
		{
			$this->error="ErrorWrongParameters";
			return -1;
		}


		$this->db->begin("ActionComm::add");

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm";
        $sql.= "(datec,";
        $sql.= "datep,";
        $sql.= "datep2,";
        $sql.= "datea,";
        $sql.= "datea2,";
        $sql.= "durationp,";
        $sql.= "durationa,";
        $sql.= "fk_action,";
        $sql.= "fk_soc,";
        $sql.= "fk_project,";
        $sql.= "note,";
		$sql.= "fk_contact,";
		$sql.= "fk_user_author,";
		$sql.= "fk_user_action,";
		$sql.= "fk_user_done,";
		$sql.= "label,percent,priority,fulldayevent,location,punctual,";
        $sql.= "fk_facture,";
        $sql.= "propalrowid,";
        $sql.= "fk_commande,";
        $sql.= "fk_supplier_invoice,";
        $sql.= "fk_supplier_order)";
        $sql.= " VALUES (";
        $sql.= "'".$this->db->idate($now)."',";
        $sql.= (strval($this->datep)!=''?"'".$this->db->idate($this->datep)."'":"null").",";
        $sql.= (strval($this->datef)!=''?"'".$this->db->idate($this->datef)."'":"null").",";
        $sql.= (strval($this->date)!=''?"'".$this->db->idate($this->date)."'":"null").",";
        $sql.= (strval($this->dateend)!=''?"'".$this->db->idate($this->dateend)."'":"null").",";
        $sql.= ($this->durationp >= 0 && $this->durationp != ''?"'".$this->durationp."'":"null").",";
        $sql.= ($this->durationa >= 0 && $this->durationa != ''?"'".$this->durationa."'":"null").",";
        $sql.= " '".$this->type_id."',";
        $sql.= ($this->societe->id>0?" '".$this->societe->id."'":"null").",";
        $sql.= ($this->fk_project>0?" '".$this->fk_project."'":"null").",";
        $sql.= " '".addslashes($this->note)."',";
        $sql.= ($this->contact->id > 0?"'".$this->contact->id."'":"null").",";
        $sql.= ($user->id > 0 ? "'".$user->id."'":"null").",";
		$sql.= ($this->usertodo->id > 0?"'".$this->usertodo->id."'":"null").",";
		$sql.= ($this->userdone->id > 0?"'".$this->userdone->id."'":"null").",";
		$sql.= "'".addslashes($this->label)."','".$this->percentage."','".$this->priority."','".$this->fulldayevent."','".addslashes($this->location)."','".$this->punctual."',";
        $sql.= ($this->facid?$this->facid:"null").",";
        $sql.= ($this->propalrowid?$this->propalrowid:"null").",";
        $sql.= ($this->orderrowid?$this->orderrowid:"null").",";
        $sql.= ($this->supplierinvoicerowid?$this->supplierinvoicerowid:"null").",";
        $sql.= ($this->supplierorderrowid?$this->supplierorderrowid:"null");
        $sql.= ")";

        dol_syslog("ActionComm::add sql=".$sql);
        $resql=$this->db->query($sql);
		if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."actioncomm","id");

            if (! $notrigger)
            {
	            // Appel des triggers
	            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('ACTION_CREATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // Fin appel triggers
			}

			$this->db->commit("ActionComm::add");
            return $this->id;
        }
        else
        {
			$this->error=$this->db->lasterror().' sql='.$sql;
			$this->db->rollback("ActionComm::add");
            return -1;
        }

    }

	/**
	*    Charge l'objet action depuis la base
	*    @param      id      id de l'action a recuperer
	*/
	function fetch($id)
	{
		global $langs;

		$sql = "SELECT a.id,";
		$sql.= " a.datep,";
		$sql.= " a.datep2,";
		$sql.= " a.datec,";
        $sql.= " a.durationp,";
		$sql.= " a.tms as datem,";
		$sql.= " a.note, a.label, a.fk_action as type_id,";
		$sql.= " a.fk_soc,";
		$sql.= " a.fk_project,";
		$sql.= " a.fk_user_author, a.fk_user_mod,";
		$sql.= " a.fk_user_action, a.fk_user_done,";
		$sql.= " a.fk_contact, a.percent as percentage, a.fk_facture, a.fk_commande, a.propalrowid,";
		$sql.= " a.priority, a.fulldayevent, a.location,";
		$sql.= " c.id as type_id, c.code as type_code, c.libelle,";
		$sql.= " s.nom as socname,";
		$sql.= " u.firstname, u.name";
		$sql.= " FROM (".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."actioncomm as a)";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_author";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = a.fk_soc";
		$sql.= " WHERE a.id=".$id." AND a.fk_action=c.id";

		dol_syslog("ActionComm::fetch sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id        = $obj->id;
				$this->ref       = $obj->id;

				$this->type_id   = $obj->type_id;
				$this->type_code = $obj->type_code;
				$transcode=$langs->trans("Action".$obj->type_code);
				$type_libelle=($transcode!="Action".$obj->type_code?$transcode:$obj->libelle);
				$this->type    = $type_libelle;

				$this->label   = $obj->label;
				$this->datep   = $this->db->jdate($obj->datep);
				$this->datef   = $this->db->jdate($obj->datep2);

				$this->datec   = $this->db->jdate($obj->datec);
				$this->datem   = $this->db->jdate($obj->datem);

				$this->note =$obj->note;
				$this->percentage =$obj->percentage;

				$this->author->id  = $obj->fk_user_author;
				$this->usermod->id  = $obj->fk_user_mod;

				$this->usertodo->id  = $obj->fk_user_action;
				$this->userdone->id  = $obj->fk_user_done;
				$this->priority = $obj->priority;
                $this->fulldayevent = $obj->fulldayevent;
				$this->location = $obj->location;

				$this->socid       = $obj->fk_soc;	// To have fetch_thirdparty method working
				$this->societe->id = $obj->fk_soc;
				$this->contact->id = $obj->fk_contact;
				$this->fk_project = $obj->fk_project;

				$this->fk_facture = $obj->fk_facture;
				if ($this->fk_facture)
				{
					$this->objet_url = img_object($langs->trans("ShowBill"),'bill').' '.'<a href="'. DOL_URL_ROOT . '/compta/facture.php?facid='.$this->fk_facture.'">'.$langs->trans("Bill").'</a>';
					$this->objet_url_type = 'facture';
				}

				$this->fk_propal = $obj->propalrowid;
				if ($this->fk_propal)
				{
					$this->objet_url = img_object($langs->trans("ShowPropal"),'propal').' '.'<a href="'. DOL_URL_ROOT . '/comm/propal.php?id='.$this->fk_propal.'">'.$langs->trans("Propal").'</a>';
					$this->objet_url_type = 'propal';
				}

				$this->fk_commande = $obj->fk_commande;
				if ($this->fk_commande)
				{
					$this->objet_url = img_object($langs->trans("ShowOrder"),'order').' '.'<a href="'. DOL_URL_ROOT . '/commande/fiche.php?id='.$this->fk_commande.'">'.$langs->trans("Order").'</a>';
					$this->objet_url_type = 'order';
				}

			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	*    Supprime l'action de la base
	*    @return     int     <0 si ko, >0 si ok
	*/
	function delete()
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm";
        $sql.= " WHERE id=".$this->id;

        dol_syslog("ActionComm::delete sql=".$sql, LOG_DEBUG);
        if ($this->db->query($sql))
        {
            return 1;
        }
        else
        {
        	$this->error=$this->db->error()." sql=".$sql;
        	return -1;
        }
    }

	/**
 	 *    Met a jour l'action en base.
 	 *	  Si percentage = 100, on met a jour date 100%
 	 *    @return     	int     <0 si ko, >0 si ok
	 */
    function update($user)
    {
        // Clean parameters
		$this->label=trim($this->label);
        $this->note=trim($this->note);
		if (empty($this->percentage))    $this->percentage = 0;
        if (empty($this->priority))      $this->priority = 0;
        if (empty($this->fulldayevent))  $this->fulldayevent = 0;
        if ($this->percentage > 100) $this->percentage = 100;
        if ($this->percentage == 100 && ! $this->dateend) $this->dateend = $this->date;
		if ($this->datep && $this->datef)   $this->durationp=($this->datef - $this->datep);
		if ($this->date  && $this->dateend) $this->durationa=($this->dateend - $this->date);
		if ($this->datep && $this->datef && $this->datep > $this->datef) $this->datef=$this->datep;
		if ($this->date  && $this->dateend && $this->date > $this->dateend) $this->dateend=$this->date;
        if ($this->fk_project < 0) $this->fk_project = 0;

		// Check parameters
		if ($this->percentage == 0 && $this->userdone->id > 0)
		{
			$this->error="ErrorCantSaveADoneUserWithZeroPercentage";
			return -1;
		}

		//print 'eeea'.$this->datep.'-'.(strval($this->datep) != '').'-'.$this->db->idate($this->datep);
		$sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm ";
        $sql.= " SET percent='".$this->percentage."'";
        $sql.= ", label = ".($this->label ? "'".addslashes($this->label)."'":"null");
        $sql.= ", datep = ".(strval($this->datep)!='' ? "'".$this->db->idate($this->datep)."'" : 'null');
        $sql.= ", datep2 = ".(strval($this->datef)!='' ? "'".$this->db->idate($this->datef)."'" : 'null');
        //$sql.= ", datea = ".(strval($this->date)!='' ? "'".$this->db->idate($this->date)."'" : 'null');
        //$sql.= ", datea2 = ".(strval($this->dateend)!='' ? "'".$this->db->idate($this->dateend)."'" : 'null');
        $sql.= ", note = ".($this->note ? "'".addslashes($this->note)."'":"null");
        $sql.= ", fk_soc =". ($this->societe->id > 0 ? "'".$this->societe->id."'":"null");
        $sql.= ", fk_project =". ($this->fk_project > 0 ? "'".$this->fk_project."'":"null");
        $sql.= ", fk_contact =". ($this->contact->id > 0 ? "'".$this->contact->id."'":"null");
        $sql.= ", priority = '".$this->priority."'";
        $sql.= ", fulldayevent = '".$this->fulldayevent."'";
        $sql.= ", location = ".($this->location ? "'".addslashes($this->location)."'":"null");
        $sql.= ", fk_user_mod = '".$user->id."'";
		$sql.= ", fk_user_action=".($this->usertodo->id > 0 ? "'".$this->usertodo->id."'":"null");
		$sql.= ", fk_user_done=".($this->userdone->id > 0 ? "'".$this->userdone->id."'":"null");
        $sql.= " WHERE id=".$this->id;

		dol_syslog("ActionComm::update sql=".$sql);
        if ($this->db->query($sql))
        {
            return 1;
        }
        else
        {
        	$this->error=$this->db->error();
			dol_syslog("ActionComm::update ".$this->error,LOG_ERR);
        	return -1;
    	}
    }


    /**
     *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *      @param          user    Objet user
     *      @return         int     <0 if KO, >0 if OK
     */
    function load_board($user)
    {
        global $conf, $user;

        $now=dol_now();

        $this->nbtodo=$this->nbtodolate=0;
        $sql = "SELECT a.id, a.datep as dp";
        $sql.= " FROM (".MAIN_DB_PREFIX."actioncomm as a";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= ")";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid AND s.entity in (0, ".$conf->entity.")";
        $sql.= " WHERE a.percent < 100";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND a.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($user->societe_id) $sql.=" AND a.fk_soc = ".$user->societe_id;
        //print $sql;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if (isset($obj->dp) && $this->db->jdate($obj->dp) < ($now - $conf->actions->warning_delay)) $this->nbtodolate++;
            }
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }


	/**
	 *      Charge les informations d'ordre info dans l'objet facture
	 *      @param     id       	Id de la facture a charger
	 */
	function info($id)
	{
		$sql = 'SELECT ';
		$sql.= ' a.id,';
		$sql.= ' datec,';
		$sql.= ' tms as datem,';
		$sql.= ' fk_user_author,';
		$sql.= ' fk_user_mod';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a';
		$sql.= ' WHERE a.id = '.$id;

		dol_syslog("ActionComm::info sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->id;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation     = $cuser;
				}
				if ($obj->fk_user_mod)
				{
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_mod);
					$this->user_modification = $muser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *    	Return label of status
	 *    	@param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	@return     string      Libelle
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->percentage,$mode);
	}

	/**
	 *		Return label of action status
	 *    	@param      percent     Percent
	 *    	@param      mode        0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	@return     string		Label
	 */
	function LibStatut($percent,$mode)
	{
		global $langs;

        if ($mode == 0)
        {
        	if ($percent==0) return $langs->trans('StatusActionToDo').' (0%)';
        	if ($percent > 0 && $percent < 100) return $langs->trans('StatusActionInProcess').' ('.$percent.'%)';
        	if ($percent >= 100) return $langs->trans('StatusActionDone').' (100%)';
		}
        if ($mode == 1)
        {
        	if ($percent==0) return $langs->trans('StatusActionToDo');
        	if ($percent > 0 && $percent < 100) return $percent.'%';
        	if ($percent >= 100) return $langs->trans('StatusActionDone');
        }
        if ($mode == 2)
        {
        	if ($percent==0) return img_picto($langs->trans('StatusActionToDo'),'statut1').' '.$langs->trans('StatusActionToDo');
        	if ($percent > 0 && $percent < 100) return img_picto($langs->trans('StatusActionInProcess'),'statut3').' '. $percent.'%';
        	if ($percent >= 100) return img_picto($langs->trans('StatusActionDone'),'statut6').' '.$langs->trans('StatusActionDone');
        }
        if ($mode == 3)
        {
        	if ($percent==0) return img_picto($langs->trans("Status").': '.$langs->trans('StatusActionToDo').' (0%)','statut1');
        	if ($percent > 0 && $percent < 100) return img_picto($langs->trans("Status").': '.$langs->trans('StatusActionInProcess').' ('.$percent.'%)','statut3');
        	if ($percent >= 100) return img_picto($langs->trans("Status").': '.$langs->trans('StatusActionDone').' (100%)','statut6');
        }
        if ($mode == 4)
        {
        	if ($percent==0) return img_picto($langs->trans('StatusActionToDo'),'statut1').' '.$langs->trans('StatusActionToDo').' (0%)';
        	if ($percent > 0 && $percent < 100) return img_picto($langs->trans('StatusActionInProcess'),'statut3').' '.$langs->trans('StatusActionInProcess').' ('.$percent.'%)';;
        	if ($percent >= 100) return img_picto($langs->trans('StatusActionDone'),'statut6').' '.$langs->trans('StatusActionDone').' (100%)';
        }
        if ($mode == 5)
        {
        	if ($percent==0) return '0% '.img_picto($langs->trans('StatusActionToDo'),'statut1');
        	if ($percent > 0 && $percent < 100) return $percent.'% '.img_picto($langs->trans('StatusActionInProcess'),'statut3');
        	if ($percent >= 100) return $langs->trans('StatusActionDone').' '.img_picto($langs->trans('StatusActionDone'),'statut6');
        }
	}

	/**
	 *    	Renvoie nom clicable (avec eventuellement le picto)
	 *      Utilise $this->id, $this->code et $this->libelle
	 * 		@param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *		@param		maxlength		Nombre de caracteres max dans libelle
	 *		@param		class			Force style class on a link
	 * 		@param		option			''=Link to action,'birthday'=Link to contact
	 *		@return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$maxlength=0,$classname='',$option='',$overwritepicto='')
	{
		global $langs;

		$result='';
		if ($option=='birthday') $lien = '<a '.($classname?'class="'.$classname.'" ':'').'href="'.DOL_URL_ROOT.'/contact/perso.php?id='.$this->id.'">';
		else $lien = '<a '.($classname?'class="'.$classname.'" ':'').'href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';
        //print $this->libelle;
        if (empty($this->libelle))
        {
        	$libelle=$langs->trans("Action".$this->type_code);
        	$libelleshort=$langs->trans("Action".$this->type_code,'','','','',$maxlength);
        }
        else
        {
        	$libelle=$this->libelle;
        	$libelleshort=dol_trunc($this->libelle,$maxlength);
        }

		if ($withpicto)
		{
            $libelle.=(($this->type_code && $libelle!=$langs->trans("Action".$this->type_code) && $langs->trans("Action".$this->type_code)!="Action".$this->type_code)?' ('.$langs->trans("Action".$this->type_code).')':'');
		    $result.=$lien.img_object($langs->trans("ShowAction").': '.$libelle,($overwritepicto?$overwritepicto:'task')).$lienfin;
		}
		if ($withpicto==1) $result.=' ';
		$result.=$lien.$libelleshort.$lienfin;
		return $result;
	}


    /**
     *		Export events from database into a cal file.
	 *		@param		format			'vcal', 'ical/ics', 'rss'
	 *		@param		type			'event' or 'journal'
	 *		@param		cachedelay		Do not rebuild file if date older than cachedelay seconds
	 *		@param		filename		Force filename
	 *		@param		filters			Array of filters
     *		@return     int     		<0 if error, nb of events in new file if ok
     */
	function build_exportfile($format,$type,$cachedelay,$filename,$filters)
	{
		global $conf,$langs,$dolibarr_main_url_root,$mysoc;

		require_once (DOL_DOCUMENT_ROOT ."/lib/xcal.lib.php");
		require_once (DOL_DOCUMENT_ROOT ."/lib/date.lib.php");

		dol_syslog("ActionComm::build_exportfile Build export file format=".$format.", type=".$type.", cachedelay=".$cachedelay.", filename=".$filename.", filters size=".sizeof($filters), LOG_DEBUG);

		// Check parameters
		if (empty($format)) return -1;

		// Clean parameters
		if (! $filename)
		{
			$extension='vcs';
			if ($format == 'ical') $extension='ics';
			$filename=$format.'.'.$extension;
		}

		// Create dir and define output file (definitive and temporary)
		$result=create_exdir($conf->agenda->dir_temp);
		$outputfile=$conf->agenda->dir_temp.'/'.$filename;
		$outputfiletmp=tempnam($conf->agenda->dir_temp,'tmp');	// Temporary file (allow call of function by different threads

		$result=0;

		$buildfile=true;
		$login='';$logina='';$logind='';$logint='';

		$now = dol_now();

		if ($cachedelay)
		{
			$nowgmt = dol_now();
			if (filemtime($outputfile) > ($nowgmt - $cachedelay))
			{
				dol_syslog("ActionComm::build_exportfile file ".$outputfile." is not older than now - cachedelay (".$nowgmt." - ".$cachedelay."). Build is canceled");
				$buildfile = false;
			}
		}

		if ($buildfile)
		{
			// Build event array
			$eventarray=array();

			$sql = "SELECT a.id,";
			$sql.= " a.datep,";		// Start
			$sql.= " a.datep2,";	// End
			$sql.= " a.durationp,";
			$sql.= " a.datec, a.tms as datem,";
			$sql.= " a.note, a.label, a.fk_action as type_id,";
			$sql.= " a.fk_soc,";
			$sql.= " a.fk_user_author, a.fk_user_mod,";
			$sql.= " a.fk_user_action, a.fk_user_done,";
			$sql.= " a.fk_contact, a.fk_facture, a.percent as percentage, a.fk_commande,";
			$sql.= " a.priority, a.fulldayevent, a.location,";
			$sql.= " u.firstname, u.name,";
			$sql.= " s.nom as socname,";
			$sql.= " c.id as type_id, c.code as type_code, c.libelle";
			$sql.= " FROM (".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."actioncomm as a)";
        	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_author";
        	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = a.fk_soc";
			$sql.= " WHERE a.fk_action=c.id";
			foreach ($filters as $key => $value)
			{
				if ($key == 'notolderthan') $sql.=" AND a.datep >= '".$this->db->idate($now-($value*24*60*60))."'";
				if ($key == 'year')         $sql.=" AND a.datep BETWEEN '".$this->db->idate(dol_get_first_day($value,1))."' AND '".$this->db->idate(dol_get_last_day($value,12))."'";
				if ($key == 'id')           $sql.=" AND a.id=".(is_numeric($value)?$value:0);
                if ($key == 'idfrom')       $sql.=" AND a.id >= ".(is_numeric($value)?$value:0);
                if ($key == 'idto')         $sql.=" AND a.id <= ".(is_numeric($value)?$value:0);
                if ($key == 'login')
				{
					$login=$value;
					$userforfilter=new User($this->db);
					$result=$userforfilter->fetch('',$value);
					$sql.= " AND (";
					$sql.= " a.fk_user_author = ".$userforfilter->id;
					$sql.= " OR a.fk_user_action = ".$userforfilter->id;
					$sql.= " OR a.fk_user_done = ".$userforfilter->id;
					$sql.= ")";
				}
				if ($key == 'logina')
				{
					$logina=$value;
					$userforfilter=new User($this->db);
					$result=$userforfilter->fetch('',$value);
					$sql.= " AND a.fk_user_author = ".$userforfilter->id;
				}
				if ($key == 'logint')
				{
					$logint=$value;
					$userforfilter=new User($this->db);
					$result=$userforfilter->fetch('',$value);
					$sql.= " AND a.fk_user_action = ".$userforfilter->id;
				}
				if ($key == 'logind')
				{
					$logind=$value;
					$userforfilter=new User($this->db);
					$result=$userforfilter->fetch('',$value);
					$sql.= " AND a.fk_user_done = ".$userforfilter->id;
				}
			}
			$sql.= " AND a.datep IS NOT NULL";		// To exclude corrupted events and avoid errors in lightning/sunbird import
			$sql.= " ORDER by datep";
			//print $sql;exit;

			dol_syslog("ActionComm::build_exportfile select events sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				// Note: Output of sql request is encoded in $conf->file->character_set_client
				while ($obj=$this->db->fetch_object($resql))
				{
					$qualified=true;

					// 'eid','startdate','duration','enddate','title','summary','category','email','url','desc','author'
					$event=array();
					$event['uid']='dolibarragenda-'.$this->db->database_name.'-'.$obj->id."@".$_SERVER["SERVER_NAME"];
					$event['type']=$type;
					//$datestart=$obj->datea?$obj->datea:$obj->datep;
					//$dateend=$obj->datea2?$obj->datea2:$obj->datep2;
					//$duration=$obj->durationa?$obj->durationa:$obj->durationp;
					$datestart=$this->db->jdate($obj->datep);
					//print $datestart.'x'; exit;
					$dateend=$this->db->jdate($obj->datep2);
					$duration=$obj->durationp;
					$event['summary']=$langs->convToOutputCharset($obj->label.($obj->socname?" (".$obj->socname.")":""));
					$event['desc']=$langs->convToOutputCharset($obj->note);
					$event['startdate']=$datestart;
					$event['duration']=$duration;	// Not required with type 'journal'
					$event['enddate']=$dateend;		// Not required with type 'journal'
					$event['author']=$obj->firstname.($obj->name?" ".$obj->name:"");
					$event['priority']=$obj->priority;
                    $event['fulldayevent']=$obj->fulldayevent;
					$event['location']=$langs->convToOutputCharset($obj->location);
					$event['transparency']='TRANSPARENT';		// OPAQUE (busy) or TRANSPARENT (not busy)
					$event['category']=$langs->convToOutputCharset($obj->libelle);	// libelle type action
					$url=$dolibarr_main_url_root;
					if (! preg_match('/\/$/',$url)) $url.='/';
					$url.='comm/action/fiche.php?id='.$obj->id;
					$event['url']=$url;
                    $event['created']=$this->db->jdate($obj->datec);
                    $event['modified']=$this->db->jdate($obj->datem);

					if ($qualified && $datestart)
					{
						$eventarray[$datestart]=$event;
					}
				}
			}
			else
			{
				$this->error=$this->db->lasterror();
				dol_syslog("ActionComm::build_exportfile ".$this->db->lasterror(), LOG_ERR);
				return -1;
			}

			$langs->load("agenda");

			// Define title and desc
			$more='';
			if ($login)  $more=$langs->transnoentities("User").' '.$langs->convToOutputCharset($login);
			if ($logina) $more=$langs->transnoentities("ActionsAskedBy").' '.$langs->convToOutputCharset($logina);
			if ($logint) $more=$langs->transnoentities("ActionsToDoBy").' '.$langs->convToOutputCharset($logint);
			if ($logind) $more=$langs->transnoentities("ActionsDoneBy").' '.$langs->convToOutputCharset($logind);
			if ($more)
			{
				$title=$langs->convToOutputCharset('Dolibarr actions '.$mysoc->name).' - '.$more;
				$desc=$more;
				$desc.=$langs->convToOutputCharset(' ('.$mysoc->name.' - built by Dolibarr)');
			}
			else
			{
				$title=$langs->convToOutputCharset('Dolibarr actions '.$mysoc->name);
				$desc=$langs->transnoentities('ListOfActions');
				$desc.=$langs->convToOutputCharset(' ('.$mysoc->name.' - built by Dolibarr)');
			}

			// Write file
            if ($format == 'vcal') $result=build_calfile($format,$title,$desc,$eventarray,$outputfiletmp);
			if ($format == 'ical') $result=build_calfile($format,$title,$desc,$eventarray,$outputfiletmp);
			if ($format == 'rss')  $result=build_rssfile($format,$title,$desc,$eventarray,$outputfiletmp);

			if ($result >= 0)
			{
				if (rename($outputfiletmp,$outputfile)) $result=1;
				else $result=-1;
			}
			else
			{
				$langs->load("errors");
				$this->error=$langs->trans("ErrorFailToCreateFile",$outputfile);
			}
		}

		return $result;
	}

}

?>
