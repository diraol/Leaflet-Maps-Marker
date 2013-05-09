<?php
/*
    License - Leaflet Maps Marker Plugin
*/
//info prevent file from being accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) == 'leaflet-license.php') { die ("Please do not access this file directly. Thanks!<br/><a href='http://www.mapsmarker.com/go'>www.mapsmarker.com</a>"); }
?>
<div class="wrap">
<?php 
//info: delete transients on update
if ( isset($_POST['leafletmapsmarkerpro_license_key']) || isset($_POST['maps_marker_pro_register_free']) ) {
	delete_transient('leafletmapsmarkerpro_update_api_cache');
	delete_transient('leafletmapsmarkerpro_plugin_page_api_cache');
	delete_transient('leafletmapsmarkerpro_dashboard_api_cache');
	delete_transient('leafletmapsmarkerpro_adminheader_api_cache');
	delete_transient('leafletmapsmarkerpro_adminheader2_api_cache');
	delete_transient('leafletmapsmarkerpro_showmap_api_cache');
} 

//info: propagate license key to subsites on WordPress Multisite
if (isset($_POST['maps_marker_pro_multisite_propagate'])) {
	$multisite_nonce = isset($_POST['maps_marker_pro_license_multisite']) ? $_POST['maps_marker_pro_license_multisite'] : '';
	if (! wp_verify_nonce($multisite_nonce, 'maps_marker_pro_license_multisite') ) die('<br/>'.__('Security check failed - please call this function from the according Leaflet Maps Marker admin page!','lmm').'');
		if (is_multisite()) {
			if (current_user_can( 'activate_plugins' )) {
				global $wpdb;
				$blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
				if ($blogs) {
					$mu_license_key = (get_option('leafletmapsmarkerpro_license_key') == TRUE) ? get_option('leafletmapsmarkerpro_license_key') : '';
					$mu_license_local_key = (get_option('leafletmapsmarkerpro_license_local_key') == TRUE) ? get_option('leafletmapsmarkerpro_license_local_key') : '';
					foreach($blogs as $blog) {
						switch_to_blog($blog['blog_id']);
						update_option('leafletmapsmarkerpro_license_key', $mu_license_key);
						update_option('leafletmapsmarkerpro_license_local_key', $mu_license_local_key);
					}
					restore_current_blog();
				}
				echo '<div class="updated" style="padding:5px;"><p>' . __('License key was successfully propagated to all subsites','lmm') . '</p></div>';
			}
		}	
}
include('inc' . DIRECTORY_SEPARATOR . 'admin-header.php'); 
?>

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
		<?php 
			if ($spbas->license_key) { if ($spbas->errors) { $css_license_color = 'background:#ff0000;color:#ffffff;'; } else { $css_license_color = 'background:#00FF00;color:#000000;'; } } 
			if ($spbas->license_key) { $button_text = __('update','lmm'); } else { $button_text = __('activate','lmm'); }
		?>
		<b><?php _e('License Key','lmm'); ?></b> <input name="leafletmapsmarkerpro_license_key" type="text" style="width:225px;<?php echo $css_license_color; ?>" value="<?php echo $spbas->license_key; ?>" /> <input type="submit" class="button-primary" value="<?php echo $button_text; ?>" />
		</p>
	</form>
	<p>
	<?php 
	if (maps_marker_pro_validate_access($release_date=VERSION_RELEASE_DATE,$license_only=false)===true) { 
			if ( (maps_marker_pro_validate_access($release_date=false, $license_only=true)===true) && (maps_marker_pro_validate_access()===true) ) {
				if (!maps_marker_pro_is_paid_version()) {
					$download_expires = $spbas->key_data['license_expires'];
				} else {
					$download_expires = $spbas->key_data['download_access_expires'];
				}
				$download_expires_diff = abs(floor((time()-$download_expires)/(60*60*24)));
				echo __('Access to plugin updates and support area valid until:','lmm') . ' ' . date('d/m/Y', $download_expires) . ' (' . $download_expires_diff . ' ' . __('days left','lmm') . ')';
			} else if ( (maps_marker_pro_validate_access($release_date=false, $license_only=true)===true) && (maps_marker_pro_validate_access()===false) ) {
				$plugin_version = get_option('leafletmapsmarker_version_pro');
				echo "<div id='message' class='error' style='padding:5px;'><strong>" . __('Warning: your access to updates and support for Leaflet Maps Marker Pro has expired!','lmm') . "</strong><br/>" . sprintf(__('You can continue using version %s without any limitations. Nevertheless you will not be able to get updates including bugfixes, new features and optimizations as well as access to our support system. ','lmm'), $plugin_version) . "</div>";
				if (maps_marker_pro_is_twentyfive_pack_license() == TRUE) {
					echo '<a href="http://www.mapsmarker.com/renewal-25-pack" target="_blank">link for 25 renewal</a>';
				} else if (maps_marker_pro_is_five_pack_license() == TRUE) {
					echo '<a href="http://www.mapsmarker.com/renewal-5-pack" target="_blank">link for 5 renewal</a>';
				} else if ( (maps_marker_pro_is_twentyfive_pack_license() == FALSE) && (maps_marker_pro_is_five_pack_license() == FALSE) ) {
					echo '<a href="http://www.mapsmarker.com/renewal-single" target="_blank">link for single site renewal</a>';
				}
				echo '<p>' . __('Important: please click the update button next to the license key after purchasing a renewal to finish your order.','lmm') . '</p>';
			} 
	} else if ( ($spbas->license_key) && (maps_marker_pro_validate_access($release_date=false, $license_only=true)===true) && (maps_marker_pro_validate_access($release_date=VERSION_RELEASE_DATE,$license_only=false)===false) ) {
		if (maps_marker_pro_is_twentyfive_pack_license() == TRUE) {
			$renewlink = 'http://www.mapsmarker.com/renewal-25-pack';
		} else if (maps_marker_pro_is_five_pack_license() == TRUE) {
			$renewlink = 'http://www.mapsmarker.com/renewal-5-pack';
		} else if ( (maps_marker_pro_is_twentyfive_pack_license() == FALSE) && (maps_marker_pro_is_five_pack_license() == FALSE) ) {
			$renewlink = 'http://www.mapsmarker.com/renewal-single';
		}
		echo "<div id='message' class='error' style='padding:5px;'><strong>" . sprintf(__('Error: This version of the plugin was released after your download access expired. Please <a href="%1$s" target="_blank">renew your download and support access</a> or <a href="%2$s" target="_blank">downgrade to your previous valid version</a>.','lmm'), $renewlink, 'https://www.mapsmarker.com/updates/archive') . "</strong></div>";		
	}
	?>
	</p>

	<?php if ($spbas->license_key): ?>	
		<p><?php echo sprintf(__('If you have any issues with your license, <a href="%1$s" target="_blank">please open a new support ticket</a>!','lmm'), 'https://www.mapsmarker.com/store/customers/index.php?task=helpdesk'); ?></p>
	<?php endif; ?>

	<?php
	if (is_multisite()) {
		if (current_user_can( 'activate_plugins' )) {
			echo '<hr noshade size="1" /><h3 style="font-size:18px;">' . __('WordPress Multisite settings','lmm') . '</h3>';
			echo '<p>' . __('Use the button below to propagate the license key entered above to all WordPress Multisite subsites.','lmm') . '</p>';
			if ( (SUBDOMAIN_INSTALL == true) || is_plugin_active('wordpress-mu-domain-mapping/domain_mapping.php') ) {
				echo '<p>' . __('Important: you seem to be using different domains for your subsites. Please make sure that your license key is valid for the number of domains you want to use it on and update the license key on each subsite directly first before propagating the license key! This will ensure that all these domains are registered on your customer profile on mapsmarker.com - which will result in a valid license validation on subsites after propagating the license key.','lmm') . '</p>';
			}
			echo '<form method="post">';	
			wp_nonce_field('maps_marker_pro_license_multisite', 'maps_marker_pro_license_multisite');
			echo '<input type="checkbox" name="maps_marker_pro_multisite_propagate" /> <label for="maps_marker_pro_multisite_propagate">' . __('Yes I want to propagate the license key to all subsites','lmm') . '</label>';
			echo ' <input type="submit" class="button-primary" value="' . __('update','lmm') . '" />';
		}
	}
	?>
</div>
<!--wrap-->
<?php include('inc' . DIRECTORY_SEPARATOR . 'admin-footer.php'); ?>