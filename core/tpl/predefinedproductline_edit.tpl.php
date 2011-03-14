<?php
/* Copyright (C) 2010 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2010 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *
 * $Id: predefinedproductline_edit.tpl.php,v 1.7 2011/01/09 15:37:23 eldy Exp $
 *
 * Need to have following variables defined:
 * $conf
 * $langs
 * $dateSelector
 * $this (invoice, order, ...)
 * $line defined
 */
?>

<!-- BEGIN PHP TEMPLATE predefinedproductline_edit.tpl.php -->
<form action="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'#'.$line->id; ?>" method="POST">
<input type="hidden" name="token" value="<?php  echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="updateligne">
<input type="hidden" name="id" value="<?php echo $this->id; ?>">
<input type="hidden" name="lineid" value="<?php echo $_GET["lineid"]; ?>">

<tr <?php echo $bc[$var]; ?>>
	<td>
	<a name="<?php echo $line->id; ?>"></a>

	<input type="hidden" name="productid" value="<?php echo $line->fk_product; ?>">
	<a href="<?php echo DOL_URL_ROOT.'/product/fiche.php?id='.$line->fk_product; ?>">
	<?php
	if ($line->product_type==1) echo img_object($langs->trans('ShowService'),'service');
	else print img_object($langs->trans('ShowProduct'),'product');
	echo ' '.$line->ref;
    ?></a><?php
	echo ' - '.nl2br($line->product_label);
	echo '<br>';

	// editeur wysiwyg
    $nbrows=ROWS_2;
    if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
    require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
	$doleditor=new DolEditor('desc',$line->description,164,'dolibarr_details','',false,true,$conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS,$nbrows,70);
	$doleditor->Create();
	?>
	</td>

	<td align="right"><?php echo $html->select_tva('tva_tx',$line->tva_tx,$seller,$buyer,'',$line->info_bits); ?></td>

	<td align="right"><input size="6" type="text" class="flat" name="subprice" value="<?php echo price($line->subprice,0,'',0); ?>"></td>

	<td align="right">
	<?php if (($line->info_bits & 2) != 2) { ?>
		<input size="2" type="text" class="flat" name="qty" value="<?php echo $line->qty; ?>">
	<?php } else { ?>
		&nbsp;
	<?php } ?>
	</td>

	<td align="right" nowrap>
	<?php if (($line->info_bits & 2) != 2) { ?>
		<input size="1" type="text" class="flat" name="remise_percent" value="<?php echo $line->remise_percent; ?>">%
	<?php } else { ?>
		&nbsp;
	<?php } ?>
	</td>

	<td align="center" colspan="5" valign="middle"><input type="submit" class="button" name="save" value="<?php echo $langs->trans("Save"); ?>">
	<br><input type="submit" class="button" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>"></td>
</tr>

<?php if ($conf->service->enabled && $dateSelector && $line->product_type == 1)	{ ?>
<tr <?php echo $bc[$var]; ?>>
	<td colspan="9"><?php echo $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' '; ?>
	<?php
	echo $html->select_date($line->date_start,'date_start',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$line->date_start?0:1,"updateligne");
	echo ' '.$langs->trans('to').' ';
	echo $html->select_date($line->date_end,'date_end',$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$line->date_end?0:1,"updateligne");
	?>
	</td>
</tr>
<?php } ?>

</form>
<!-- END PHP TEMPLATE predefinedproductline_edit.tpl.php -->
