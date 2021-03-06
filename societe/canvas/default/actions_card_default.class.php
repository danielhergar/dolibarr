<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 *	\file       htdocs/societe/canvas/default/actions_card_default.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe Thirdparty card controller (default canvas)
 *	\version    $Id: actions_card_default.class.php,v 1.6 2010/11/05 08:28:51 hregis Exp $
 */
include_once(DOL_DOCUMENT_ROOT.'/societe/canvas/actions_card_common.class.php');

/**
 *	\class      ActionsCardDefault
 *	\brief      Classe permettant la gestion des tiers par defaut
 */
class ActionsCardDefault extends ActionsCardCommon
{
	var $db;

	//! Canvas
	var $canvas;

	/**
	 *    Constructeur de la classe
	 *    @param	DB		Handler acces base de donnees
	 */
	function ActionsCardDefault($DB)
	{
		$this->db = $DB;
	}

	/**
	 * 	Return the title of card
	 */
	function getTitle($action)
	{
		global $langs;

		$out='';

		if ($action == 'view') 		$out.= $langs->trans("ThirdParty");
		if ($action == 'edit') 		$out.= $langs->trans("EditCompany");
		if ($action == 'create')	$out.= $langs->trans("NewCompany");

		return $out;
	}
	
	/**
	 * 	Return the head of card (tabs)
	 */
	function showHead($action)
	{
		$head = societe_prepare_head($this->object);
		$title = $this->getTitle($action);
		
		return dol_fiche_head($head, 'card', $title, 0, 'company');
	}

	/**
     *    Assigne les valeurs POST dans l'objet
     */
    function assign_post()
    {
    	parent::assign_post();
    }

	/**
	 * 	Execute actions
	 * 	@param 		Id of object (may be empty for creation)
	 */
	function doActions($socid)
	{
		$return = parent::doActions($socid);

		return $return;
	}

	/**
	 *    Assign custom values for canvas
	 *    @param      action     Type of action
	 */
	function assign_values($action='')
	{
		global $conf, $langs, $user, $mysoc;
		global $form, $formadmin, $formcompany;

		parent::assign_values($action);

		$this->tpl['profid1'] 	= $this->object->siren;
		$this->tpl['profid2'] 	= $this->object->siret;
		$this->tpl['profid3'] 	= $this->object->ape;
		$this->tpl['profid4'] 	= $this->object->idprof4;

		if ($action == 'create' || $action == 'edit')
		{
			for ($i=1; $i<=4; $i++)
			{
				$this->tpl['langprofid'.$i]		= $langs->transcountry('ProfId'.$i,$this->object->pays_code);
				$this->tpl['showprofid'.$i]		= $this->object->get_input_id_prof($i,'idprof'.$i,$this->tpl['profid'.$i]);
			}

			// Type
			$this->tpl['select_companytype']	= $form->selectarray("typent_id",$formcompany->typent_array(0), $this->object->typent_id);

			// Juridical Status
			$this->tpl['select_juridicalstatus'] = $formcompany->select_juridicalstatus($this->object->forme_juridique_code,$this->object->pays_code);

			// Workforce
			$this->tpl['select_workforce'] = $form->selectarray("effectif_id",$formcompany->effectif_array(0), $this->object->effectif_id);

			// VAT intra
			$s ='<input type="text" class="flat" name="tva_intra" size="12" maxlength="20" value="'.$this->object->tva_intra.'">';
			$s.=' ';
			if ($conf->use_javascript_ajax)
			{
				$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
				$this->tpl['tva_intra'] =  $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
			}
			else
			{
				$this->tpl['tva_intra'] =  $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$this->object->id_pays).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
			}

		}

		if ($action == 'view')
		{
			// Confirm delete third party
			if ($_GET["action"] == 'delete')
			{
				$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$this->object->id,$langs->trans("DeleteACompany"),$langs->trans("ConfirmDeleteCompany"),"confirm_delete",'',0,2);
			}

			for ($i=1; $i<=4; $i++)
			{
				$this->tpl['langprofid'.$i]		= $langs->transcountry('ProfId'.$i,$this->object->pays_code);
				$this->tpl['checkprofid'.$i]	= $this->object->id_prof_check($i,$this->object);
				$this->tpl['urlprofid'.$i]		= $this->object->id_prof_url($i,$this->object);
			}

			// TVA intra
			if ($this->tva_intra)
			{
				$s='';
				$s.=$this->object->tva_intra;
				$s.='<input type="hidden" name="tva_intra" size="12" maxlength="20" value="'.$this->object->tva_intra.'">';
				$s.=' &nbsp; ';
				if ($conf->use_javascript_ajax)
				{
					$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
					$this->tpl['tva_intra'] = $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
				}
				else
				{
					$this->tpl['tva_intra'] = $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$this->object->id_pays).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
				}
			}
			else
			{
				$this->tpl['tva_intra'] = '&nbsp;';
			}

			// Parent company
			if ($this->object->parent)
			{
				$socm = new Societe($this->db);
				$socm->fetch($this->object->parent);
				$this->tpl['parent_company'] = $socm->getNomUrl(1).' '.($socm->code_client?"(".$socm->code_client.")":"");
				$this->tpl['parent_company'].= $socm->ville?' - '.$socm->ville:'';
			}
			else
			{
				$this->tpl['parent_company'] = $langs->trans("NoParentCompany");
			}
		}
	}
	
	/**
	 * 	Check permissions of a user to show a page and an object. Check read permission
	 * 	If $_REQUEST['action'] defined, we also check write permission.
	 * 	@param      user      	  	User to check
	 * 	@param      features	    Features to check (in most cases, it's module name)
	 * 	@param      objectid      	Object ID if we want to check permission on a particular record (optionnal)
	 *  @param      dbtablename    	Table name where object is stored. Not used if objectid is null (optionnal)
	 *  @param      feature2		Feature to check (second level of permission)
	 *  @param      dbt_keyfield    Field name for socid foreign key if not fk_soc. (optionnal)
	 *  @param      dbt_select      Field name for select if not rowid. (optionnal)
	 *  @return		int				1
	 */
	function restrictedArea($user, $features='societe', $objectid=0, $dbtablename='', $feature2='', $dbt_keyfield='fk_soc', $dbt_select='rowid')
	{
		return restrictedArea($user,$features,$objectid,$dbtablename,$feature2,$dbt_keyfield,$dbt_select);
	}

}

?>