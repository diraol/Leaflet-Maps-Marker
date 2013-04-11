<?php
/*
    License - Leaflet Maps Marker Plugin
*/
//info prevent file from being accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) == 'leaflet-license.php') { die ("Please do not access this file directly. Thanks!<br/><a href='http://www.mapsmarker.com/go'>www.mapsmarker.com</a>"); }
?>
<div class="wrap">
<?php include('inc' . DIRECTORY_SEPARATOR . 'admin-header.php'); ?>
<h3 style="font-size:23px;"><?php _e('Pro License Settings','lmm'); ?></h3>

<div class="wrap">
	<?php if ($spbas->errors && !isset($_POST['maps_marker_pro_register_free'])): ?>
		<div id="message" class="updated">
			<p><b><?php echo $spbas->errors; ?></b></p>
		</div>
	<?php endif; ?>

	<?php if ($license_updated&&!$spbas->errors): ?>
		<div id="message" class="updated">
			<p><b><?php _e('Your license was activated successfully!','lmm'); ?></b></p>
		</div>
	<?php endif; ?> 
  
	<?php if(isset($maps_marker_pro_reg_success)): ?>
		<div id="message" class="updated">
			<p><b><?php _e($maps_marker_pro_reg_success); ?></b></p>
		</div> 
	<?php endif; ?> 

	<?php if (!$spbas->license_key && !maps_marker_pro_is_paid_version()): ?>
		<p><?php echo sprintf(__('You can test <i>Maps Marker Pro</i> for 30 days for free without any obligations. After the trial period the plugin´s admin pages will become inaccessible unless you enter a <a href="%1s" target="_blank">valid license key</a>.','lmm'), 'http://www.mapsmarker.com'); ?></p>
		<p><?php _e('Please submit the following form to start a free 30 day trial:','lmm'); ?></p>

		<?php if (!empty($maps_marker_pro_reg_errors)): ?>
			<div id="message" class="error">
				<?php foreach($maps_marker_pro_reg_errors as $e): ?>
					<p><b><?php _e($e); ?></b></p>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<form method="post">
		<input type='hidden' name='maps_marker_pro_register_free' value='y' />
			<table>
				<tr>
					<td><b><?php  _e('First name','lmm'); ?></b></td>
					<td><input name="maps_marker_pro_first_name" type="text" style="width:225px;" value="<?php echo maps_marker_pro_reg('maps_marker_pro_first_name'); ?>" /></td>
				</tr>
				<tr>
					<td><b><?php _e('Last name','lmm'); ?></b></td>
					<td><input name="maps_marker_pro_last_name" type="text" style="width:225px;" value="<?php echo maps_marker_pro_reg('maps_marker_pro_last_name') ?>" /></td>
				</tr>
				<tr>
					<td><b><?php _e('E-mail','lmm'); ?></b></td>
					<td><input name="maps_marker_pro_email" type="text" style="width:225px;" value="<?php echo maps_marker_pro_reg('maps_marker_pro_email') ?>" /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="checkbox" name="maps_marker_pro_tos" value="Yes" checked="checked" /> <?php echo sprintf(__('I have read the <a href="%1$s" target="_blank">Terms of Service</a> and <a href="%2$s" target="_blank">Privacy Policy</a>.','lmm'), 'http://www.mapsmarker.com/terms-of-services', 'http://www.mapsmarker.com/privacy-policy'); ?></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" class="button-primary" value="<?php echo _e('Register Free 30 Day Trial','lmm'); ?>" /></td>
				</tr>
			</table>
		</form>
	<hr noshade size="1" style="margin:25px 0;" />
	<?php endif; ?>

	<form method="post">
	<?php wp_nonce_field('maps_marker_pro_license', 'maps_marker_pro_license'); ?>
		<?php if (!$spbas->license_key): ?>	
			<p class="howto"><?php _e('Already got a valid license key? Then please enter the license key that was e-mailed to you.','lmm'); ?></p>
		<?php endif; ?>
		<p>
			<b><?php _e('License Key','lmm'); ?></b> <input name="leafletmapsmarkerpro_license_key" type="text" style="width:225px;" value="<?php echo $spbas->license_key; ?>" /> <input type="submit" class="button-primary" value="<?php echo _e('Activate','lmm'); ?>" />
		</p>
	</form>
	<p>
	<?php 
		if ( $spbas->license_key && (maps_marker_pro_validate_access()===true) ) {	
			$download_expires = $spbas->key_data['download_access_expires'];
			$download_expires_diff = abs(floor((time()-$download_expires)/(60*60*24)));
			echo __('Access to plugin updates and support area valid until:','lmm') . date('d/m/Y', $download_expires) . ' (' . $download_expires_diff . ' ' . __('days left','lmm') . ')';
		} else if (  $spbas->license_key && (maps_marker_pro_validate_access()!==true) ) {
			$plugin_version = get_option('leafletmapsmarker_version_pro');
			echo "<div id='message' class='error' style='padding:5px;'><strong>" . __('Warning: your access to updates and support for Leaflet Maps Marker Pro has expired!','lmm') . "</strong><br/>" . sprintf(__('You can continue using version %s without any limitations. Nevertheless you will not be able to get updates including bugfixes, new features and optimizations as well as access to our support system. ','lmm'), $plugin_version) . "<br/>" . sprintf(__('<a href="%s" target="_blank">Please renew your access to updates and support to keep your plugin up-to-date and safe</a>.','lmm'), 'http://www.mapsmarker.com/renew') . "</div>";
		}
	?>
	</p>

	<?php if ($spbas->license_key): ?>	
		<p><?php echo sprintf(__('If you have any issues with your license, <a href="%1$s" target="_blank">please open a new support ticket</a>!','lmm'), 'https://www.mapsmarker.com/store/customers/index.php?task=helpdesk'); ?></p>
	<?php endif; ?>
</div>
<!--wrap-->
<?php include('inc' . DIRECTORY_SEPARATOR . 'admin-footer.php'); ?>