<?php
/* Copyright (C) 2009-2010 Regis Houssin <regis@dolibarr.fr>
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
 * $Id: passwordforgotten.tpl.php,v 1.9 2010/10/22 20:30:21 hregis Exp $
 */
$smartphone->smartheader();
?>

<!-- BEGIN SMARTPHONE TEMPLATE -->

<div data-role="page" id="dol-home" data-theme="b">

	<div data-role="header" data-theme="b" data-position="inline">
			<h1><?php echo $langs->trans('Identification'); ?></h1>
	</div>

	<div data-role="content">
	
		<form id="login" name="login" method="post" action="<?php echo $php_self; ?>">
		<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
		<input type="hidden" name="loginfunction" value="buildnewpassword" />
		
		<div data-role="fieldcontain">
			<label for="username"><?php echo $langs->trans('Login'); ?></label>
			<input type="text" name="username" id="username" value="<?php echo $login; ?>"  />
			
			<?php if ($select_entity) { ?>
			<label for="entity" class="select"><?php echo $langs->trans('Entity'); ?></label>
			<?php echo $select_entity; ?>
			<?php } ?>
			
			<?php if ($captcha) { ?>
			<label for="securitycode"><?php echo $langs->trans('SecurityCode'); ?></label>
			<input type="text" id="securitycode" name="securitycode" />
			<div align="center"><img src="<?php echo $dol_url_root.'/lib/antispamimage.php'; ?>" border="0" width="256" height="48" /></div>
			<?php } ?>
		</div>
		
		<input type="submit" data-theme="b" value="<?php echo $langs->trans('SendByMail'); ?>" />
		
		</form>

	</div><!-- /content -->
	
	<div data-role="footer" data-theme="b">
		<?php if ($mode == 'dolibarr' || ! $disabled) {
			echo $langs->trans('SendNewPasswordDesc');
		}else{
			echo $langs->trans('AuthenticationDoesNotAllowSendNewPassword', $mode);
		} ?>
	</div>

</div><!-- /page -->

<?php if ($message) { ?>
	<script type="text/javascript" language="javascript">
		alert('<?php echo $message; ?>');
	</script>
<?php } ?>

<!-- END SMARTPHONE TEMPLATE -->

<?php $smartphone->smartfooter(); ?>