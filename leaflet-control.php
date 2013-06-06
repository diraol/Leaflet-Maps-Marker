<?php
//info prevent file from being accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) == 'leaflet-control.php') { die ("Please do not access this file directly. Thanks!<br/><a href='http://www.mapsmarker.com/go'>www.mapsmarker.com</a>"); }
global $wp_version;
if (version_compare($wp_version,"3.0","<")){
	exit('[Leaflet Maps Marker Plugin - installation failed!]: WordPress Version 3.0 or higher is needed for this plugin (you are using version '.$wp_version.') - please upgrade your WordPress installation!');
}
if (version_compare(phpversion(),"5.2","<")){
	exit('[Leaflet Maps Marker Plugin - installation failed]: PHP 5.2 is needed for this plugin (you are using PHP '.phpversion().'; note: support for PHP 4 has been officially discontinued since 2007-12-31!) - please upgrade your PHP installation!');
}

//info: deactive free version first if active
include_once( ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php' );
if (is_plugin_active('leaflet-maps-marker/leaflet-maps-marker.php') ) {
	if (!is_multisite()) {
		deactivate_plugins('leaflet-maps-marker/leaflet-maps-marker.php', $silent = false, $network_wide = null);
		activate_plugin('leaflet-maps-marker-pro/leaflet-maps-marker.php', $redirect = 'plugins.php?activate=true', $network_wide = false, $silent = false);
	} else {
		include_once( ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'pluggable.php' );
		if (is_network_admin()) {
			deactivate_plugins('leaflet-maps-marker/leaflet-maps-marker.php', $silent = false, $network_wide = true);
			activate_plugin('leaflet-maps-marker-pro/leaflet-maps-marker.php', $redirect = false, $network_wide = true, $silent = false);
		} else {
			deactivate_plugins('leaflet-maps-marker/leaflet-maps-marker.php', $silent = false, $network_wide = null);
			activate_plugin('leaflet-maps-marker-pro/leaflet-maps-marker.php', $redirect = 'plugins.php?activate=true', $network_wide = false, $silent = false);
		}
	}
}

//2do: update on each release (MM/DD/YYYY) - preventing manual update without valid upgrade license
if ( ! defined( 'VERSION_RELEASE_DATE' ) )
define( 'VERSION_RELEASE_DATE', '05/01/2013' );

//info: define necessary paths and urls
if ( ! defined( 'LEAFLET_WP_ADMIN_URL' ) )
	define( 'LEAFLET_WP_ADMIN_URL', get_admin_url() );
if ( ! defined( 'LEAFLET_PLUGIN_URL' ) )
	define ("LEAFLET_PLUGIN_URL", plugin_dir_url(__FILE__));
if ( ! defined( 'LEAFLET_PLUGIN_DIR' ) )
	define ("LEAFLET_PLUGIN_DIR", plugin_dir_path(__FILE__));
$lmm_upload_dir = wp_upload_dir();
if ( ! defined( 'LEAFLET_PLUGIN_ICONS_URL' ) )
	define ("LEAFLET_PLUGIN_ICONS_URL", $lmm_upload_dir['baseurl'] . "/leaflet-maps-marker-icons");
if ( ! defined( 'LEAFLET_PLUGIN_ICONS_DIR' ) )
	define ("LEAFLET_PLUGIN_ICONS_DIR", $lmm_upload_dir['basedir'] . DIRECTORY_SEPARATOR . "leaflet-maps-marker-icons");
if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'inc' . DIRECTORY_SEPARATOR . 'class-leaflet-options.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'inc' . DIRECTORY_SEPARATOR . 'class-plugin-update-checker.php' );
	global $lmm_options_class;
	$lmm_options_class = new Class_leaflet_options();
}
require_once( plugin_dir_path( __FILE__ ) . 'leaflet-core.php' );

//*****************************/
//info: SPBAS Licensing BEGIN */
//*****************************/

if ( is_admin() ) {
	$current_page = isset($_GET['page']) ? $_GET['page'] : '';
	$check_pages = array('leafletmapsmarker_markers','leafletmapsmarker_marker','leafletmapsmarker_layers','leafletmapsmarker_layer','leafletmapsmarker_tools','leafletmapsmarker_settings');
	if (in_array($current_page, $check_pages)) {
		// Validate the license!
		if (maps_marker_pro_validate_license()!==true) { 
			die(header('Location: '.self_admin_url('admin.php?page=leafletmapsmarker_license'))); 
		}
		//info: check release date vs. download expire date
		if (maps_marker_pro_validate_access($release_date=VERSION_RELEASE_DATE,$license_only=false)===false) {
			$protected_pages = array('leafletmapsmarker_markers','leafletmapsmarker_marker','leafletmapsmarker_layers','leafletmapsmarker_layer','leafletmapsmarker_tools','leafletmapsmarker_settings');
			if (in_array($current_page, $protected_pages)) {
				echo '<script type="text/javascript">window.location.href = "' . LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_license&error=download_expired";</script>  ';	
			} 
		}
	}
}

/**
* The pages that should be validated
* 
* @param array $licensed_pages 
* @param boolean $is_theme 
* @return boolean
*/
function maps_marker_pro_do_validate_license($licensed_pages, $is_theme=false) {
	$this_page='';
	if (isset($_GET['page'])) {
		$this_page=$_GET['page'];
	} elseif (isset($_POST['page'])) {
		$this_page=$_POST['page'];
	}
	$do_validate_license=in_array($this_page, $licensed_pages);

	if ($is_theme) { return $do_validate_license; }

	$plugin_active=in_array(plugin_basename(__FILE__), get_option('active_plugins'));

	return ($do_validate_license&&$plugin_active);
}

/**
* Validate the license
* 
* @param boolean $raw 
* @return boolean
*/
function maps_marker_pro_validate_license($raw=false) {
	$spbas=new spbas_maps_marker_pro;
	$spbas->local_key_storage='database';  
	$spbas->read_query=array('local_key' => get_option('leafletmapsmarkerpro_license_local_key'));
	$spbas->update_query=array('function' => 'update_option', 'key' => 'leafletmapsmarkerpro_license_local_key');
	$spbas->local_key_grace_period='1,2,3,4,5,6,7,8,9,10';
	$spbas->license_key=get_option('leafletmapsmarkerpro_license_key');
	$spbas->secret_key='7ca99e156b5e30e0648f49b81178cd7e';
	$spbas->api_server='https://www.mapsmarker.com/store/api/index.php';
	$spbas->validate();

	if ($raw) { return $spbas; }

	$licensed_pages=array(
		'leafletmapsmarker_markers',
		'leafletmapsmarker_marker',
		'leafletmapsmarker_layers',
		'leafletmapsmarker_layer',
		'leafletmapsmarker_tools',
		'leafletmapsmarker_settings'
	); 
	if (is_admin()&&maps_marker_pro_do_validate_license($licensed_pages, true)&&$spbas->errors) {
		return $spbas->errors;
	}
	return true;

}

/**
* Validate the license, support and download access.
* 
* @param string $release_date MM/DD/YYYY; The date that you released this version. Will need to be updated with each release.
* @return boolean true for valid access; false for access expired
*/
function maps_marker_pro_validate_access($release_date=false, $license_only=false) {
	$spbas=new spbas_maps_marker_pro;
	if ($release_date !== false) { $spbas->release_date=strtotime($release_date);}
	$spbas->local_key_storage='database';  
	$spbas->read_query=array('local_key' => get_option('leafletmapsmarkerpro_license_local_key'));
	$spbas->update_query=array('function' => 'update_option', 'key' => 'leafletmapsmarkerpro_license_local_key');
	$spbas->local_key_grace_period='1,2,3,4,5,6,7,8,9,10';
	$spbas->license_key=get_option('leafletmapsmarkerpro_license_key');
	$spbas->secret_key='7ca99e156b5e30e0648f49b81178cd7e';
	$spbas->api_server='https://www.mapsmarker.com/store/api/index.php';
	$spbas->validate();

	//check validity of license only
	if ($license_only !== false) { 
		if ( $spbas->errors ) { return false; } else { return true; }
	}

	// USER-HAS-VALID-LICENSE
	if ( $spbas->errors ) { return false; }
	
	if ($release_date === false) {
		// Figure out when the support & download access expires. Use the most distant date.
		$download_expires = $spbas->key_data['download_access_expires'];
		$support_expires = $spbas->key_data['support_access_expires'];
		$expires = abs(($download_expires > $support_expires)?$download_expires:$support_expires);
	
		// Is the free version?
		if (!maps_marker_pro_is_paid_version())	{
			// USER-HAS-VALID-LICENSE (sanity check)
			if ($spbas->key_data['license_expires'] == 'never')	{
				return false;
			}
			// USER-IS-WITHIN-30-DAY-TRIAL-LICENSE-PERIOD
			if (time() > $spbas->key_data['license_expires']) {
				return false;
			}
			return true;
		}
		// USER-HAS-VALID-DOWNLOAD-ACCESS
		return ($expires > time())?true:false;
	} else if ($release_date !== false) {
		$download_expires = $spbas->key_data['download_access_expires'];
		$support_expires = $spbas->key_data['support_access_expires'];
		$expires = abs(($download_expires > $support_expires)?$download_expires:$support_expires);

		// Is the free version?
		if (!maps_marker_pro_is_paid_version()) {
			// USER-HAS-VALID-LICENSE (sanity check)
			if ($spbas->key_data['license_expires'] == 'never')	{
				return false;
			}
			// USER-IS-WITHIN-30-DAY-TRIAL-LICENSE-PERIOD
			if (time() > $spbas->key_data['license_expires']) {
				return false;
			}
			return true;
		}

		// Was this release pushed out during a period that the customer had a valid package in place?
		if ($spbas->release_date > $expires) { return false; } else { return true; }
	}
}
function maps_marker_pro_is_paid_version($prefix = 'MapsMarkerPro-') {
	return substr(get_option('leafletmapsmarkerpro_license_key'), 0, strlen($prefix)) == $prefix;
}
function maps_marker_pro_is_free_version_and_license_entered($prefix = 'MapsMarkerFree-') {
	$license_key = get_option('leafletmapsmarkerpro_license_key');
	if ( (substr($license_key, 0, strlen($prefix)) == $prefix) || (substr($license_key, 0, 14) != 'MapsMarkerPro-') ) {
		return true;
	} else { 
		return false; 
	}
}
function maps_marker_pro_is_five_pack_license($prefix = 'MapsMarkerPro-5P-') {
	return substr(get_option('leafletmapsmarkerpro_license_key'), 0, strlen($prefix)) == $prefix;
}
function maps_marker_pro_is_twentyfive_pack_license($prefix = 'MapsMarkerPro-25P-') {
	return substr(get_option('leafletmapsmarkerpro_license_key'), 0, strlen($prefix)) == $prefix;
}
function maps_marker_pro_is_hundred_pack_license($prefix = 'MapsMarkerPro-100P-') {
	return substr(get_option('leafletmapsmarkerpro_license_key'), 0, strlen($prefix)) == $prefix;
}
function maps_marker_pro_is_twohundredfifty_pack_license($prefix = 'MapsMarkerPro-250P-') {
	return substr(get_option('leafletmapsmarkerpro_license_key'), 0, strlen($prefix)) == $prefix;
}
function maps_marker_pro_is_fivehundred_pack_license($prefix = 'MapsMarkerPro-500P-') {
	return substr(get_option('leafletmapsmarkerpro_license_key'), 0, strlen($prefix)) == $prefix;
}
function maps_marker_pro_is_thousand_pack_license($prefix = 'MapsMarkerPro-1000P-') {
	return substr(get_option('leafletmapsmarkerpro_license_key'), 0, strlen($prefix)) == $prefix;
}

function maps_marker_pro_reg($attribute) {
	if ( isset($_POST[$attribute]) && ($_POST[$attribute] == TRUE) ) { return $_POST[$attribute]; }

	global $current_user; 
	get_currentuserinfo();

	switch($attribute) {
		case 'maps_marker_pro_first_name':
			 return $current_user->user_firstname; 
		case 'maps_marker_pro_last_name':
			 return $current_user->user_lastname; 
		case 'maps_marker_pro_email':
			 return $current_user->user_email; 
	}
	return '';
}

/**
* The license validation page
* 
* @return boolean
*/
function maps_marker_pro_license_activation_page() {
	if (isset($_POST['maps_marker_pro_register_free']))	{
		$spbas=new spbas_maps_marker_pro;
		$maps_marker_pro_reg_success = '';
		$maps_marker_pro_reg_errors=array();
		if (!$_POST['maps_marker_pro_first_name']) { $maps_marker_pro_reg_errors[]=__('Please enter your first name to continue.','lmm'); }
		if (!$_POST['maps_marker_pro_last_name']) { $maps_marker_pro_reg_errors[]=__('Please enter your last name to continue.','lmm'); }
		if (!$_POST['maps_marker_pro_email']) { $maps_marker_pro_reg_errors[]=__('Please enter your email to continue.','lmm'); }
		if (!$_POST['maps_marker_pro_tos']) { $maps_marker_pro_reg_errors[]=__('You must agree to the TOS to continue.','lmm'); }

		if (empty($maps_marker_pro_reg_errors)) {
			$qs=array();
			$qs['email']=$_POST['maps_marker_pro_email'];
			$qs['first_name']=$_POST['maps_marker_pro_first_name'];
			$qs['last_name']=$_POST['maps_marker_pro_last_name'];
			$qs['company_name']="{$_POST['maps_marker_pro_first_name']} {$_POST['maps_marker_pro_last_name']}";
			$qs['username']=strtolower($_POST['maps_marker_pro_last_name'].substr($_POST['maps_marker_pro_first_name'], 0, 1));
			$response=$spbas->use_curl('https://www.mapsmarker.com/store/api/index.php?mod=3rd_party&task=api&api_key=50a2094314fbc82e712b3f9b21f00745', $qs);

			if (strtolower(substr($response, 0, 5)) == 'error')	{
				$maps_marker_pro_reg_errors[]=$response;
			} else {
				$response=json_decode($response);

				update_option('leafletmapsmarkerpro_license_key', $spbas->license_key = $response[0]);
				update_option('leafletmapsmarkerpro_license_local_key', ''); 

				$maps_marker_pro_reg_success=__('Your new license was registered successfully!','lmm');
			}
		}
	} else {
		// make sure we have the needed function to verify the nonce.
		if (!function_exists('wp_verify_nonce')) { require_once(ABSPATH .'wp-includes/pluggable.php');  }

		// save the new license key
		$license_updated=(isset($_POST['maps_marker_pro_license'])&&wp_verify_nonce($_POST['maps_marker_pro_license'], 'maps_marker_pro_license'));
		if ($license_updated) {
			// clear the local key cache
			update_option('leafletmapsmarkerpro_license_local_key', ''); 
			// save the new license key
			update_option('leafletmapsmarkerpro_license_key', $_POST['leafletmapsmarkerpro_license_key']); 
		}
		// include the license validation data
		$spbas=maps_marker_pro_validate_license(true);
	}
	include dirname(__FILE__).'/leaflet-license.php';
	return true;
}

/**
* SPBAS License Validation
*
* @license 		Commercial / Proprietary
* @copyright	SolidPHP, Inc.
* @package		SPBAS_License_Method
* @author		Andy Rockwell <support@solidphp.com>
*/
class spbas_maps_marker_pro
	{
	var $errors;
	var $license_key;
	var $api_server;
	var $remote_port;
	var $remote_timeout;
	var $local_key_storage;
	var $read_query;
	var $update_query;
	var $local_key_path;
	var $local_key_name;
	var $local_key_transport_order;
	var $local_key_grace_period;
	var $local_key_last;
	var $validate_download_access;
	var $release_date;
	var $key_data;
	var $status_messages;
	var $valid_for_product_tiers;

	function spbas_maps_marker_pro()
		{
		$this->errors=false;
		$this->remote_port=80;
		$this->remote_timeout=10;
		$this->valid_local_key_types=array('spbas');
		$this->local_key_type='spbas';
		$this->local_key_storage='filesystem';
		$this->local_key_grace_period=0;
		$this->local_key_last=0;
		$this->read_query=false;
		$this->update_query=false;
		$this->local_key_path='';
		$this->local_key_name='license.txt';
		$this->local_key_transport_order='scf';
		$this->validate_download_access=false;
		$this->release_date=false;
		$this->valid_for_product_tiers=false;

		$this->key_data=array(
						'custom_fields' => array(), 
						'download_access_expires' => 0, 
						'license_expires' => 0, 
						'local_key_expires' => 0, 
						'status' => 'Invalid', 
						);

		$this->status_messages=array(
						'active' => __('This license is active.','lmm'), 
						'suspended' => __('Error: This license has been suspended.','lmm'), 
						'expired' => __('Error: This license has expired.','lmm'), 
						'pending' => __('Error: This license is pending review.','lmm'), 
						'download_access_expired' => __('Error: This version of the software was released after your download access expired. Please renew your download and support access or downgrade to a previous version.','lmm'), 
						'missing_license_key' => __('Please enter a license key to continue.','lmm'),
						'unknown_local_key_type' => __('Error: An unknown type of local key validation was requested.','lmm'),
						'could_not_obtain_local_key' => __('Error: a new local license key could not be obtained.','lmm'), 
						'maximum_grace_period_expired' => __('Error: The maximum local license key grace period has expired.','lmm'),
						'local_key_tampering' => __('Error: The local license key has been tampered with or is invalid.','lmm'),
						'local_key_invalid_for_location' => __('Error: The local license key is invalid for this location.','lmm'),
						'missing_license_file' => __('Error: Please create the following file (and directories if they do not exist already):<br />\r\n<br />\r\n','lmm'),
						'license_file_not_writable' => __('Error: Please make the following path writable:<br />','lmm'),
						'invalid_local_key_storage' => __('Error: the local key storage on clear could not determined.','lmm'),
						'could_not_save_local_key' => __('Error: the local license key could not saved.','lmm'),
						'license_key_string_mismatch' => __('Error: The local key is invalid for this license.','lmm'),
						);

		// replace plain text messages with tags, make the tags keys for this localization array on the server side.
		// move all plain text messages to tags & localizations
		$this->localization=array(
						'active' => __('This license is active.','lmm'), 
						'suspended' => __('Error: This license has been suspended.','lmm'), 
						'expired' => __('Error: This license has expired.','lmm'), 
						'pending' => __('Error: This license is pending review.','lmm'), 
						'download_access_expired' => __('Error: This version of the software was released after your download access expired. Please renew your download and support access or downgrade to a previous version.','lmm') 
						);
		}

	/**
	* Validate the license
	* 
	* @return string
	*/
	function validate()
		{
		// Make sure we have a license key.
		if (!$this->license_key) 
			{ 
			return $this->errors=$this->status_messages['missing_license_key']; 
			}

		// Make sure we have a valid local key type.
		if (!in_array(strtolower($this->local_key_type), $this->valid_local_key_types)) 
			{ 
			return $this->errors=$this->status_messages['unknown_local_key_type'];
			}

		// Read in the local key.
		$this->trigger_grace_period=$this->status_messages['could_not_obtain_local_key'];
		switch($this->local_key_storage)
			{
			case 'database':
				$local_key=$this->db_read_local_key();
				break;

			case 'filesystem':
				$local_key=$this->read_local_key();
				break;

			default:
				return $this->errors=$this->status_messages['missing_license_key'];
			}

		// The local key has expired, we can't go remote and we have grace periods defined.
		if ($this->errors==$this->trigger_grace_period&&$this->local_key_grace_period)
			{
			// Process the grace period request
			$grace=$this->process_grace_period($this->local_key_last); 
			if ($grace['write'])
				{
				// We've consumed one of the allowed grace periods.
				if ($this->local_key_storage=='database')
					{
					$this->db_write_local_key($grace['local_key']);
					}
				elseif ($this->local_key_storage=='filesystem')
					{
					$this->write_local_key($grace['local_key'], "{$this->local_key_path}{$this->local_key_name}");
					}
				}

			// We've consumed all the allowed grace periods.
			if ($grace['errors']) { return $this->errors=$grace['errors']; }

			// We are in a valid grace period, let it slide!
			$this->errors=false;
			return $this;
			}

		// Did reading in the local key go ok?
		if ($this->errors) 
			{ 
			return $this->errors; 
			}

		// Validate the local key.
		return $this->validate_local_key($local_key);
		}

	/**
	* Calculate the maximum grace period in unix timestamp.
	* 
	* @param integer $local_key_expires 
	* @param integer $grace 
	* @return integer
	*/
	function calc_max_grace($local_key_expires, $grace)
		{
		return ((integer)$local_key_expires+((integer)$grace*86400));
		}

	/**
	* Process the grace period for the local key.
	* 
	* @param string $local_key 
	* @return string
	*/
	function process_grace_period($local_key)
		{
		// Get the local key expire date
		$local_key_src=$this->decode_key($local_key); 
		$parts=$this->split_key($local_key_src);
		$key_data=unserialize($parts[0]);
		$local_key_expires=(integer)$key_data['local_key_expires'];
		unset($parts, $key_data);

		// Build the grace period rules
		$write_new_key=false;
		$parts=explode("\n\n", $local_key); $local_key=$parts[0];
		foreach ($local_key_grace_period=explode(',', $this->local_key_grace_period) as $key => $grace)
			{
			// add the separator
			if (!$key) { $local_key.="\n"; }

			// we only want to log days past
			if ($this->calc_max_grace($local_key_expires, $grace)>time()) { continue; }

			// log the new attempt, we'll try again next time
			$local_key.="\n{$grace}";

			$write_new_key=true;
			}

		// Are we at the maximum limit? 
		if (time()>$this->calc_max_grace($local_key_expires, array_pop($local_key_grace_period)))
			{
			return array('write' => false, 'local_key' => '', 'errors' => $this->status_messages['maximum_grace_period_expired']);
			}

		return array('write' => $write_new_key, 'local_key' => $local_key, 'errors' => false);
		}

	/**
	* Are we still in a grace period?
	* 
	* @param string $local_key 
	* @param integer $local_key_expires 
	* @return integer
	*/
	function in_grace_period($local_key, $local_key_expires)
		{
		$grace=$this->split_key($local_key, "\n\n"); 
		if (!isset($grace[1])) { return -1; }

		return (integer)($this->calc_max_grace($local_key_expires, array_pop(explode("\n", $grace[1])))-time());
		}

	/**
	* Validate the local license key.
	* 
	* @param string $local_key 
	* @return string
	*/
	function decode_key($local_key)
		{
		return base64_decode(str_replace("\n", '', urldecode($local_key)));
		}

	/**
	* Validate the local license key.
	* 
	* @param string $local_key 
	* @param string $token		{spbas} or \n\n 
	* @return string
	*/
	function split_key($local_key, $token='{spbas}')
		{
		return explode($token, $local_key);
		}

	/**
	* Does the key match anything valid?
	* 
	* @param string $key
	* @param array $valid_accesses
	* @return array
	*/ 
	function validate_access($key, $valid_accesses)
		{
		return in_array($key, (array)$valid_accesses);
		}

	/**
	* Create an array of wildcard IP addresses
	* 
	* @param string $key
	* @param array $valid_accesses
	* @return array
	*/ 
	function wildcard_ip($key)
		{
		$octets=explode('.', $key);

		array_pop($octets);
		$ip_range[]=implode('.', $octets).'.*';

		array_pop($octets);
		$ip_range[]=implode('.', $octets).'.*';

		array_pop($octets);
		$ip_range[]=implode('.', $octets).'.*';

		return $ip_range;
		}

	/**
	* Create an array of wildcard IP addresses
	* 
	* @param string $key
	* @param array $valid_accesses
	* @return array
	*/ 
	function wildcard_domain($key)
		{
		return '*.'.str_replace('www.', '', $key);
		}

	/**
	* Create a wildcard server hostname
	* 
	* @param string $key
	* @param array $valid_accesses
	* @return array
	*/ 
	function wildcard_server_hostname($key)
		{
		$hostname=explode('.', $key);
		unset($hostname[0]);

		$hostname=(!isset($hostname[1]))?array($key):$hostname;

		return '*.'.implode('.', $hostname);
		}

	/**
	* Extract a specific set of access details from the instance
	* 
	* @param array $instances
	* @param string $enforce
	* @return array
	*/ 
	function extract_access_set($instances, $enforce)
		{
		foreach ($instances as $key => $instance)
			{
			if ($key!=$enforce) { continue; }
			return $instance;
			}

		return array();
		}

	/**
	* Validate the local license key.
	* 
	* @param string $local_key 
	* @return string
	*/
	function validate_local_key($local_key)
		{
		// Convert the license into a usable form.
		$local_key_src=$this->decode_key($local_key); 
		
		// Break the key into parts.
		$parts=$this->split_key($local_key_src);

		// If we don't have all the required parts then we can't validate the key.
		if (!isset($parts[1]))
			{
			return $this->errors=$this->status_messages['local_key_tampering'];
			}

		// Make sure the data wasn't forged.
		if (md5($this->secret_key.$parts[0])!=$parts[1])
			{
			return $this->errors=$this->status_messages['local_key_tampering'];
			}
		unset($this->secret_key);

		// The local key data in usable form.
		$key_data=unserialize($parts[0]);
		$instance=$key_data['instance']; unset($key_data['instance']);
		$enforce=$key_data['enforce']; unset($key_data['enforce']);
		$this->key_data=$key_data;

		// Make sure this local key is valid for the license key string
		if ((string)$key_data['license_key_string']!=(string)$this->license_key)
			{
			return $this->errors=$this->status_messages['license_key_string_mismatch'];
			}

		// Make sure we are dealing with an active license.
		if ((string)$key_data['status']!='active')
			{
			return $this->errors=$this->status_messages[$key_data['status']];
			}

		// License string expiration check
		if ((string)$key_data['license_expires']!='never'&&(integer)$key_data['license_expires']<time())
			{
			return $this->errors=$this->status_messages['expired'];
			}

		// Local key expiration check
		if ((string)$key_data['local_key_expires']!='never'&&(integer)$key_data['local_key_expires']<time())
			{
			if ($this->in_grace_period($local_key, $key_data['local_key_expires'])<0)
				{
				// It's absolutely expired, go remote for a new key!
				$this->clear_cache_local_key(true);
				return $this->validate();
				}
			}

		// Download access check
		if ($this->validate_download_access&&(integer)$key_data['download_access_expires']<strtotime($this->release_date))
			{
			return $this->errors=$this->status_messages['download_access_expired'];
			}

		// Is this key valid for this location?
		$conflicts=array(); 
		$access_details=$this->access_details();
		foreach ((array)$enforce as $key)
			{
			$valid_accesses=$this->extract_access_set($instance, $key);
			if (!$this->validate_access($access_details[$key], $valid_accesses))
				{
				$conflicts[$key]=true; 

				// check for wildcards
				if (in_array($key, array('ip', 'server_ip')))
					{
					foreach ($this->wildcard_ip($access_details[$key]) as $ip) 
						{
						if ($this->validate_access($ip, $valid_accesses))
							{
							unset($conflicts[$key]);
							break;
							}
						}
					}
				elseif (in_array($key, array('domain')))
					{
					if ($this->validate_access($this->wildcard_domain($access_details[$key]) , $valid_accesses))
						{
						unset($conflicts[$key]);
						}
					}
				elseif (in_array($key, array('server_hostname')))
					{
					if ($this->validate_access($this->wildcard_server_hostname($access_details[$key]) , $valid_accesses))
						{
						unset($conflicts[$key]);
						}
					}
				}
			}

		// Is the local key valid for this location?
		if (!empty($conflicts))
			{
			return $this->errors=$this->status_messages['local_key_invalid_for_location'];
			}
		}

	/**
	* Read in a new local key from the database.
	* 
	* @return string
	*/
	function db_read_local_key()
		{
		$result=array();
		if (is_array($this->read_query)) { $result=$this->read_query; }
		else
			{
			$query=@mysql_query($this->read_query);
			if ($mysql_error=mysql_error()) { return $this -> errors="Error: {$mysql_error}"; }

			$result=@mysql_fetch_assoc($query);
			if ($mysql_error=mysql_error()) { return $this -> errors="Error: {$mysql_error}"; }
			}

		// is the local key empty?
		if (!$result['local_key'])
			{ 
			// Yes, fetch a new local key.
			$result['local_key']=$this->fetch_new_local_key();

			// did fetching the new key go ok?
			if ($this->errors) { return $this->errors; }

			// Write the new local key.
			$this->db_write_local_key($result['local_key']);
			}

		// return the local key
		return $this->local_key_last=$result['local_key'];
		}

	/**
	* Write the local key to the database.
	* 
	* @return string|boolean string on error; boolean true on success
	*/
	function db_write_local_key($local_key)
		{
		if (is_array($this->update_query))
			{
			$run=$this->update_query['function'];
			return $run($this->update_query['key'], $local_key);
			}

		@mysql_query(str_replace('{local_key}', $local_key, $this->update_query));
		if ($mysql_error=mysql_error()) { return $this -> errors="Error: {$mysql_error}"; }

		return true;
		}

	/**
	* Read in the local license key.
	* 
	* @return string
	*/
	function read_local_key()
		{ 
		if (!file_exists($path="{$this->local_key_path}{$this->local_key_name}"))
			{
			return $this -> errors=$this->status_messages['missing_license_file'].$path;
			}

		if (!is_writable($path))
			{
			return $this -> errors=$this->status_messages['license_file_not_writable'].$path;
			}

		// is the local key empty?
		if (!$local_key=@file_get_contents($path))
			{
			// Yes, fetch a new local key.
			$local_key=$this->fetch_new_local_key();

			// did fetching the new key go ok?
			if ($this->errors) { return $this->errors; }

			// Write the new local key.
			$this->write_local_key(urldecode($local_key), $path);
			}

		// return the local key
		return $this->local_key_last=$local_key;
		}

	/**
	* Clear the local key file cache by passing in ?clear_local_key_cache=y
	* 
	* @param boolean $clear 
	* @return string on error
	*/
	function clear_cache_local_key($clear=false)
		{
		switch(strtolower($this->local_key_storage))
			{
			case 'database':
				$this->db_write_local_key('');
				break;

			case 'filesystem':
				$this->write_local_key('', "{$this->local_key_path}{$this->local_key_name}");
				break;

			default:
				return $this -> errors=$this->status_messages['invalid_local_key_storage'];
			}
		}

	/**
	* Write the local key to a file for caching.
	* 
	* @param string $local_key 
	* @param string $path 
	* @return string|boolean string on error; boolean true on success
	*/
	function write_local_key($local_key, $path)
		{
		$fp=@fopen($path, 'w');
		if (!$fp) { return $this -> errors=$this->status_messages['could_not_save_local_key']; }
		@fwrite($fp, $local_key);
		@fclose($fp);

		return true;
		}

	/**
	* Query the API for a new local key
	*  
	* @return string|false string local key on success; boolean false on failure.
	*/
	function fetch_new_local_key()
		{
		// build a querystring
		$querystring="mod=license&task=SPBAS_validate_license&license_key={$this->license_key}&";
		$querystring.=$this->build_querystring($this->access_details());

		// was there an error building the access details?
		if ($this->errors) { return false; }

		$priority=$this->local_key_transport_order;
		while (strlen($priority)) 
			{
			$use=substr($priority, 0, 1);

			// try fsockopen()
			if ($use=='s') 
				{ 
				if ($result=$this->use_fsockopen($this->api_server, $querystring))
					{
					break;
					}
				}

			// try curl()
			if ($use=='c') 
				{
				if ($result=$this->use_curl($this->api_server, $querystring))
					{
					break;
					}
				}

			// try fopen()
			if ($use=='f') 
				{ 
				if ($result=$this->use_fopen($this->api_server, $querystring))
					{
					break;
					}
				}

			$priority=substr($priority, 1);
			}

		if (!$result) 
			{ 
			$this->errors=$this->status_messages['could_not_obtain_local_key']; 
			return false;
			}

		if (substr($result, 0, 7)=='Invalid') 
			{ 
			$this->errors=str_replace('Invalid', 'Error', $result); 
			return false;
			}

		if (substr($result, 0, 5)=='Error') 
			{ 
			$this->errors=$result; 
			return false;
			}

		return $result;
		}

	/**
	* Convert an array to querystring key/value pairs
	* 
	* @param array $array 
	* @return string
	*/
	function build_querystring($array)
		{
		$buffer='';
		foreach ((array)$array as $key => $value)
			{
			if ($buffer) { $buffer.='&'; }
			$buffer.="{$key}={$value}";
			}

		return $buffer;
		}

	/**
	* Build an array of access details
	* 
	* @return array
	*/
	function access_details()
		{
		$access_details=array();
		$access_details['domain']='';
		$access_details['ip']='';
		$access_details['directory']='';
		$access_details['server_hostname']='';
		$access_details['server_ip']='';
		$access_details['valid_for_product_tiers']='';

		// Try phpinfo() - only when suhosin hardening is not loaded
		if (!extension_loaded('suhosin') || !constant("SUHOSIN_PATCH")) {
			if (function_exists('phpinfo'))
				{
				ob_start();
				phpinfo(INFO_GENERAL);
				phpinfo(INFO_ENVIRONMENT);
				$phpinfo=ob_get_contents();
				ob_end_clean();
	
				$list=strip_tags($phpinfo);
				$access_details['domain']=$this->scrape_phpinfo($list, 'HTTP_HOST');
				$access_details['ip']=$this->scrape_phpinfo($list, 'SERVER_ADDR');
				$access_details['directory']=$this->scrape_phpinfo($list, 'SCRIPT_FILENAME');
				$access_details['server_hostname']=$this->scrape_phpinfo($list, 'System');
				$access_details['server_ip']=@gethostbyname($access_details['server_hostname']);
				}
		}
		// Try legacy.
		$access_details['domain']=($access_details['domain'])?$access_details['domain']:$_SERVER['HTTP_HOST'];
		$access_details['ip']=($access_details['ip'])?$access_details['ip']:$this->server_addr();
		$access_details['directory']=($access_details['directory'])?$access_details['directory']:$this->path_translated();
		$access_details['server_hostname']=($access_details['server_hostname'])?$access_details['server_hostname']:@gethostbyaddr($access_details['ip']);
		$access_details['server_hostname']=($access_details['server_hostname'])?$access_details['server_hostname']:'Unknown';
		$access_details['server_ip']=($access_details['server_ip'])?$access_details['server_ip']:@gethostbyaddr($access_details['ip']);
		$access_details['server_ip']=($access_details['server_ip'])?$access_details['server_ip']:'Unknown';

		// Last resort, send something in...
		foreach ($access_details as $key => $value)
			{
			if ($key=='valid_for_product_tiers') { continue; }
			$access_details[$key]=($access_details[$key])?$access_details[$key]:'Unknown';
			}

		// enforce product IDs
		if ($this->valid_for_product_tiers)
			{
			$access_details['valid_for_product_tiers']=$this->valid_for_product_tiers;
			}

		return $access_details;
		}

	/**
	* Get the directory path
	* 
	* @return string|boolean string on success; boolean on failure
	*/
	function path_translated()
		{
		$option=array('PATH_TRANSLATED', 
					'||IG_PATH_TRANSLATED', 
					'SCRIPT_FILENAME', 
					'DOCUMENT_ROOT',
					'APPL_PHYSICAL_PATH');

		foreach ($option as $key)
			{
			if (!isset($_SERVER[$key])||strlen(trim($_SERVER[$key]))<=0) { continue; }

			if ($this->is_windows()&&strpos($_SERVER[$key], '\\'))
				{
				return  @substr($_SERVER[$key], 0, @strrpos($_SERVER[$key], '\\'));
				}
			
			return  @substr($_SERVER[$key], 0, @strrpos($_SERVER[$key], '/'));
			}

		return false;
		}

	/**
	* Get the server IP address
	* 
	* @return string|boolean string on success; boolean on failure
	*/
	function server_addr()
		{
		$options=array('SERVER_ADDR', 'LOCAL_ADDR');
		foreach ($options as $key)
			{
			if (isset($_SERVER[$key])) { return $_SERVER[$key]; }
			}

		return false;
		}

	/**
	* Get access details from phpinfo()
	* 
	* @param array $all 
	* @param string $target
	* @return string|boolean string on success; boolean on failure
	*/
	function scrape_phpinfo($all, $target)
		{
		$all=explode($target, $all);
		if (count($all)<2) { return false; }
		$all=explode("\n", $all[1]);
		$all=trim($all[0]);

		if ($target=='System')
			{
			$all=explode(" ", $all);
			$all=trim($all[(strtolower($all[0])=='windows'&&strtolower($all[1])=='nt')?2:1]);
			}

		if ($target=='SCRIPT_FILENAME')
			{
			$slash=($this->is_windows()?'\\':'/');

			$all=explode($slash, $all);
			array_pop($all);
			$all=implode($slash, $all);
			}

		if (substr($all, 1, 1)==']') { return false; }

		return $all;
		}

	/**
	* Pass the access details in using fsockopen
	* 
	* @param string $url 
	* @param string $querystring
	* @return string|boolean string on success; boolean on failure
	*/
	function use_fsockopen($url, $querystring)
		{
		if (!function_exists('fsockopen')) { return false; }

		$url=parse_url($url);

		$fp=@fsockopen($url['host'], $this->remote_port, $errno, $errstr, $this->remote_timeout);
		if (!$fp) { return false; }

		$header="POST {$url['path']} HTTP/1.0\r\n";
		$header.="Host: {$url['host']}\r\n";
		$header.="Content-type: application/x-www-form-urlencoded\r\n";
		$header.="User-Agent: SPBAS (http://www.spbas.com)\r\n";
		$header.="Content-length: ".@strlen($querystring)."\r\n";
		$header.="Connection: close\r\n\r\n";
		$header.=$querystring;

		$result=false;
		fputs($fp, $header);
		while (!feof($fp)) { $result.=fgets($fp, 1024); }
		fclose ($fp);

		if (strpos($result, '200')===false) { return false; }

		$result=explode("\r\n\r\n", $result, 2);

		if (!$result[1]) { return false; }

		return $result[1];
		}

	/**
	* Pass the access details in using cURL
	* 
	* @param string $url 
	* @param string $querystring
	* @return string|boolean string on success; boolean on failure
	*/
	function use_curl($url, $querystring)
		{ 
		if (!function_exists('curl_init')) { return false; }

		$curl = curl_init();
		
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$header[] = "Pragma: ";
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_USERAGENT, 'SPBAS (http://www.spbas.com)');
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $querystring);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->remote_timeout);
		curl_setopt($curl, CURLOPT_TIMEOUT, $this->remote_timeout); // 60

		$result= curl_exec($curl);
		$info=curl_getinfo($curl);
		curl_close($curl);

		if ((integer)$info['http_code']!=200) { return false; }

		return $result;
		}

	/**
	* Pass the access details in using the fopen wrapper file_get_contents()
	* 
	* @param string $url 
	* @param string $querystring
	* @return string|boolean string on success; boolean on failure
	*/
	function use_fopen($url, $querystring)
		{ 
		if (!function_exists('file_get_contents')) { return false; }

		return @file_get_contents("{$url}?{$querystring}");
		}

	/**
	* Determine if we are running windows or not.
	* 
	* @return boolean
	*/
	function is_windows()
		{
		return (strtolower(substr(php_uname(), 0, 7))=='windows'); 
		}

	/**
	* Debug - prints a formatted array
	* 
	* @param array $stack The array to display
	* @param boolean $stop_execution
	* @return string 
	*/
	function pr($stack, $stop_execution=true)
		{
		$formatted='<pre>'.var_export((array)$stack, 1).'</pre>';

		if ($stop_execution) { die($formatted); }

		return $formatted;
		}
	}

//***************************/
//info: SPBAS Licensing END */
//***************************/

$run_leafletmapsmarker_pro = new LeafletmapsmarkerPro();

if ( is_admin() ) {
	if ( maps_marker_pro_validate_access() ) {
		$run_PluginUpdateChecker = new PluginUpdateChecker(
			'http://www.mapsmarker.com/updates/?action=get_metadata&slug=leaflet-maps-marker-pro',
			__FILE__,
			'leaflet-maps-marker-pro',
			'12',
			'leafletmapsmarkerpro_pluginupdatechecker'
		);
	}
}

//info: include widget class
require_once( plugin_dir_path( __FILE__ ) . 'inc' . DIRECTORY_SEPARATOR . 'class-leaflet-recent-marker-widget.php' );
unset($run_leafletmapsmarker_pro);
unset($run_PluginUpdateChecker);
?>