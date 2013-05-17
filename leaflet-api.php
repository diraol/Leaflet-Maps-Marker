<?php
/*
    REST-API - Leaflet Maps Marker Plugin
*/
//info: construct path to wp-load.php
while(!is_file('wp-load.php')){
  if(is_dir('../')) chdir('../');
  else die('Error: Could not construct path to wp-load.php - please check <a href="http://mapsmarker.com/path-error">http://mapsmarker.com/path-error</a> for more details');
}
include( 'wp-load.php' );
 /**
 * Returns a Lat and Lng from an Address using Google Geocoder API. It does not 
 * require any Google API Key
 * @author Abdullah Rubiyath
 * @param $opt 	An array containing
 *            'address' => The Address to be parsed
 *            'sensor' => 'true' or 'false' as [string]
 *
 * @return 	An array containing
 *          'status'  => Boolean which is true on success, false on failure
 *          'message' => 'Success' on success, otherwise an error message
 *          'lat'     => The Lat of the address
 *          'lon'     => The Lng of the address
 *          'address' => The Address typed by user.
 */
function getLatLng($address, $sensor=false) {
	$url = 'http://maps.googleapis.com/maps/api/geocode/xml?' . 'address=' . $address . '&sensor=' . $sensor;
	$dom = new DomDocument();
	$dom->load($url);
	$response = array();
	$xpath = new DomXPath($dom);
	$statusCode = $xpath->query("//status");
	if ($statusCode != false && $statusCode->length > 0 && $statusCode->item(0)->nodeValue == "OK") {
		$latDom = $xpath->query("//location/lat");
		$lonDom = $xpath->query("//location/lng");
		$addressDom = $xpath->query("//formatted_address");
		if ($latDom->length > 0) { /* if there's a lat, then there must be lng :) */
			$response = array (
				'status' 	=> true,
				'message' 	=> 'Success',
				'lat' 		=> $latDom->item(0)->nodeValue,
				'lon' 		=> $lonDom->item(0)->nodeValue,
				'address'	=> $addressDom->item(0)->nodeValue
			);
			return $response;
		}	
	}
	$response = array (
		'status' => false,
		'message' => "Oh snap! Error in Geocoding. Please check Address"
	);
	return $response;
}
//info: get callback parameters for JSONP
$callback = (isset($_GET['callback']) == TRUE ) ? $_GET['callback'] : '';
function hide_email($email) { $character_set = '+-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz'; $key = str_shuffle($character_set); $cipher_text = ''; $id = 'e'.rand(1,999999999); for ($i=0;$i<strlen($email);$i+=1) $cipher_text.= $key[strpos($character_set,$email[$i])]; $script = 'var a="'.$key.'";var b=a.split("").sort().join("");var c="'.$cipher_text.'";var d="";'; $script.= 'for(var e=0;e<c.length;e++)d+=b.charAt(a.indexOf(c.charAt(e)));'; $script.= 'document.getElementById("'.$id.'").innerHTML="<a href=\\"mailto:"+d+"\\">"+d+"</a>"'; $script = "eval(\"".str_replace(array("\\",'"'),array("\\\\",'\"'), $script)."\")"; $script = '<script type="text/javascript">/*<![CDATA[*/'.$script.'/*]]>*/</script>'; return '<span id="'.$id.'">[javascript protected email address]</span>'.$script; }
//info: check if plugin is active (didnt use is_plugin_active() due to problems reported by users)
function lmm_is_plugin_active( $plugin ) {
	$active_plugins = get_option('active_plugins');
	$active_plugins = array_flip($active_plugins);
	if ( isset($active_plugins[$plugin]) || lmm_is_plugin_active_for_network( $plugin ) ) { return true; }
}	
function lmm_is_plugin_active_for_network( $plugin ) {
	if ( !is_multisite() )
		return false;
	$plugins = get_site_option( 'active_sitewide_plugins');
	if ( isset($plugins[$plugin]) )
				return true;
	return false;
}
if (!lmm_is_plugin_active('leaflet-maps-marker-pro/leaflet-maps-marker.php') ) {
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json; charset=utf-8');
	if ($callback != NULL) { echo $callback . '('; }
	echo '{'.PHP_EOL;
	echo '"success":false,'.PHP_EOL;
	echo '"message":"' . sprintf(esc_attr__('The WordPress plugin "Leaflet Maps Marker" is inactive on this site and therefore this API link is not working.<br/><br/>Please contact the site owner (%1s) who can activate this plugin again.','lmm'), get_bloginfo('admin_email') ) . '",'.PHP_EOL;
	echo '"data": { }'.PHP_EOL;
	echo '}';
	if ($callback != NULL) { echo ');'; }
} else {
	global $wpdb;
	$table_name_markers = $wpdb->prefix.'leafletmapsmarker_markers';
	$table_name_layers = $wpdb->prefix.'leafletmapsmarker_layers';
	$lmm_options = get_option( 'leafletmapsmarker_options' );
	$callback = isset($_POST['callback']) ? $_POST['callback'] : (isset($_GET['callback']) ? $_GET['callback'] : '');
	$version = isset($_POST['version']) ? $_POST['version'] : (isset($_GET['version']) ? $_GET['version'] : '');

	if ($lmm_options['api_status'] == 'enabled') {

		if ( ($version == '1') || ($version == '') ) { //info: change OR condition if v2 is available
			$api_key = isset($_POST['key']) ? $_POST['key'] : (isset($_GET['key']) ? $_GET['key'] : '');

			if ($api_key == $lmm_options['api_key']) {

				if ( ($lmm_options['api_allowed_ip'] == null) || (($lmm_options['api_allowed_ip'] != null) && (strpos ($_SERVER['REMOTE_ADDR'], str_replace("..",".",str_replace("...",".",str_replace("*", "", $lmm_options['api_allowed_ip'])))) === 0)) ) {
					$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
					$id = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : '');
					$type = isset($_POST['type']) ? $_POST['type'] : (isset($_GET['type']) ? $_GET['type'] : '');
					
					if ($action == 'view') {
						if ( $lmm_options['api_permissions_view'] == TRUE ) {
							if ($type == 'marker') {
								$query_result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name_markers WHERE id = %d", $id), ARRAY_A);
								if (count($query_result) >= 1) {
									header('Cache-Control: no-cache, must-revalidate');
									header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":true,'.PHP_EOL;
									echo '"message":"' . esc_attr__('API call was successful','lmm') . '",'.PHP_EOL;
									echo '"data": {'.PHP_EOL;
										$remap_id = isset($_POST['remap_id']) ? $_POST['remap_id'] : (isset($_GET['remap_id']) ? $_GET['remap_id'] : 'id');
										echo '"' . $remap_id . '":"'.$query_result['id'].'",'.PHP_EOL;
										$remap_markername = isset($_POST['remap_markername']) ? $_POST['remap_markername'] : (isset($_GET['remap_markername']) ? $_GET['remap_markername'] : 'markername');
										echo '"' . $remap_markername . '":"' . stripslashes($query_result['markername']) . '",'.PHP_EOL;
										$remap_basemap = isset($_POST['remap_basemap']) ? $_POST['remap_basemap'] : (isset($_GET['remap_basemap']) ? $_GET['remap_basemap'] : 'basemap');
										echo '"' . $remap_basemap . '":"'.$query_result['basemap'].'",'.PHP_EOL;
										$remap_layer = isset($_POST['remap_layer']) ? $_POST['remap_layer'] : (isset($_GET['remap_layer']) ? $_GET['remap_layer'] : 'layer');
										echo '"' . $remap_layer . '":"'.$query_result['layer'].'",'.PHP_EOL;
										$remap_lat = isset($_POST['remap_lat']) ? $_POST['remap_lat'] : (isset($_GET['remap_lat']) ? $_GET['remap_lat'] : 'lat');
										echo '"' . $remap_lat . '":"'.$query_result['lat'].'",'.PHP_EOL;
										$remap_lon = isset($_POST['remap_lon']) ? $_POST['remap_lon'] : (isset($_GET['remap_lon']) ? $_GET['remap_lon'] : 'lon');
										echo '"' . $remap_lon . '":"'.$query_result['lon'].'",'.PHP_EOL;
										$remap_ = isset($_POST['remap_']) ? $_POST['remap_'] : (isset($_GET['remap_']) ? $_GET['remap_'] : '');
										echo '"icon":"'.$query_result['icon'].'",'.PHP_EOL;
										$mpopuptext = stripslashes(str_replace('"', '\'', preg_replace('/(\015\012)|(\015)|(\012)/','<br/>',$query_result['popuptext'])));
										$remap_popuptext = isset($_POST['remap_popuptext']) ? $_POST['remap_popuptext'] : (isset($_GET['remap_popuptext']) ? $_GET['remap_popuptext'] : 'popuptext');
										echo '"' . $remap_popuptext . '":"' . $mpopuptext . '",'.PHP_EOL;
										$remap_zoom = isset($_POST['remap_zoom']) ? $_POST['remap_zoom'] : (isset($_GET['remap_zoom']) ? $_GET['remap_zoom'] : 'zoom');
										echo '"' . $remap_zoom . '":"' . $query_result['zoom'] . '",'.PHP_EOL;
										$remap_openpopup = isset($_POST['remap_openpopup']) ? $_POST['remap_openpopup'] : (isset($_GET['remap_openpopup']) ? $_GET['remap_openpopup'] : 'openpopup');
										echo '"' . $remap_openpopup . '":"' . $query_result['openpopup'] . '",'.PHP_EOL;
										$remap_mapwidth = isset($_POST['remap_mapwidth']) ? $_POST['remap_mapwidth'] : (isset($_GET['remap_mapwidth']) ? $_GET['remap_mapwidth'] : 'mapwidth');
										echo '"' . $remap_mapwidth . '":"' . $query_result['mapwidth'] . '",'.PHP_EOL;
										$remap_mapwidthunit = isset($_POST['remap_mapwidthunit']) ? $_POST['remap_mapwidthunit'] : (isset($_GET['remap_mapwidthunit']) ? $_GET['remap_mapwidthunit'] : 'mapwidthunit');
										echo '"' . $remap_mapwidthunit . '":"' . $query_result['mapwidthunit'] . '",'.PHP_EOL;
										$remap_mapheight = isset($_POST['remap_mapheight']) ? $_POST['remap_mapheight'] : (isset($_GET['remap_mapheight']) ? $_GET['remap_mapheight'] : 'mapheight');
										echo '"' . $remap_mapheight . '":"' . $query_result['mapheight'] . '",'.PHP_EOL;
										$remap_panel = isset($_POST['remap_panel']) ? $_POST['remap_panel'] : (isset($_GET['remap_panel']) ? $_GET['remap_panel'] : 'panel');
										echo '"' . $remap_panel . '":"' . $query_result['panel'] . '",'.PHP_EOL;
										$remap_createdby = isset($_POST['remap_createdby']) ? $_POST['remap_createdby'] : (isset($_GET['remap_createdby']) ? $_GET['remap_createdby'] : 'createdby');
										echo '"' . $remap_createdby . '":"' . stripslashes($query_result['createdby']) . '",'.PHP_EOL;
										$remap_createdon = isset($_POST['remap_createdon']) ? $_POST['remap_createdon'] : (isset($_GET['remap_createdon']) ? $_GET['remap_createdon'] : 'createdon');
										echo '"' . $remap_createdon . '":"' . $query_result['createdon'] . '",'.PHP_EOL;
										$remap_updatedby = isset($_POST['remap_updatedby']) ? $_POST['remap_updatedby'] : (isset($_GET['remap_updatedby']) ? $_GET['remap_updatedby'] : 'updatedby');
										echo '"' . $remap_updatedby . '":"' . stripslashes($query_result['updatedby']) . '",'.PHP_EOL;
										$remap_updatedon = isset($_POST['remap_updatedon']) ? $_POST['remap_updatedon'] : (isset($_GET['remap_updatedon']) ? $_GET['remap_updatedon'] : 'updatedon');
										echo '"' . $remap_updatedon . '":"' . stripslashes($query_result['updatedon']) . '",'.PHP_EOL;
										$remap_controlbox = isset($_POST['remap_controlbox']) ? $_POST['remap_controlbox'] : (isset($_GET['remap_controlbox']) ? $_GET['remap_controlbox'] : 'controlbox');
										echo '"' . $remap_controlbox . '":"'.$query_result['controlbox'].'",'.PHP_EOL;
										$remap_overlays_custom = isset($_POST['remap_overlays_custom']) ? $_POST['remap_overlays_custom'] : (isset($_GET['remap_overlays_custom']) ? $_GET['remap_overlays_custom'] : 'overlays_custom');
										echo '"' . $remap_overlays_custom . '":"'.$query_result['overlays_custom'].'",'.PHP_EOL;
										$remap_overlays_custom2 = isset($_POST['remap_overlays_custom2']) ? $_POST['remap_overlays_custom2'] : (isset($_GET['remap_overlays_custom2']) ? $_GET['remap_overlays_custom2'] : 'overlays_custom2');
										echo '"' . $remap_overlays_custom2 . '":"'.$query_result['overlays_custom2'].'",'.PHP_EOL;
										$remap_overlays_custom3 = isset($_POST['remap_overlays_custom3']) ? $_POST['remap_overlays_custom3'] : (isset($_GET['remap_overlays_custom3']) ? $_GET['remap_overlays_custom3'] : 'overlays_custom3');
										echo '"' . $remap_overlays_custom3 . '":"'.$query_result['overlays_custom3'].'",'.PHP_EOL;
										$remap_overlays_custom4 = isset($_POST['remap_overlays_custom4']) ? $_POST['remap_overlays_custom4'] : (isset($_GET['remap_overlays_custom4']) ? $_GET['remap_overlays_custom4'] : 'overlays_custom4');
										echo '"' . $remap_overlays_custom4 . '":"'.$query_result['overlays_custom4'].'",'.PHP_EOL;
										$remap_wms = isset($_POST['remap_wms']) ? $_POST['remap_wms'] : (isset($_GET['remap_wms']) ? $_GET['remap_wms'] : 'wms');
										echo '"' . $remap_wms . '":"'.$query_result['wms'].'",'.PHP_EOL;
										$remap_wms2 = isset($_POST['remap_wms2']) ? $_POST['remap_wms2'] : (isset($_GET['remap_wms2']) ? $_GET['remap_wms2'] : 'wms2');
										echo '"' . $remap_wms2 . '":"'.$query_result['wms2'].'",'.PHP_EOL;
										$remap_wms3 = isset($_POST['remap_wms3']) ? $_POST['remap_wms3'] : (isset($_GET['remap_wms3']) ? $_GET['remap_wms3'] : 'wms3');
										echo '"' . $remap_wms3 . '":"'.$query_result['wms3'].'",'.PHP_EOL;
										$remap_wms4 = isset($_POST['remap_wms4']) ? $_POST['remap_wms4'] : (isset($_GET['remap_wms4']) ? $_GET['remap_wms4'] : 'wms4');
										echo '"' . $remap_wms4 . '":"'.$query_result['wms4'].'",'.PHP_EOL;
										$remap_wms5 = isset($_POST['remap_wms5']) ? $_POST['remap_wms5'] : (isset($_GET['remap_wms5']) ? $_GET['remap_wms5'] : 'wms5');
										echo '"' . $remap_wms5 . '":"'.$query_result['wms5'].'",'.PHP_EOL;
										$remap_wms6 = isset($_POST['remap_wms6']) ? $_POST['remap_wms6'] : (isset($_GET['remap_wms6']) ? $_GET['remap_wms6'] : 'wms6');
										echo '"' . $remap_wms6 . '":"'.$query_result['wms6'].'",'.PHP_EOL;
										$remap_wms7 = isset($_POST['remap_wms7']) ? $_POST['remap_wms7'] : (isset($_GET['remap_wms7']) ? $_GET['remap_wms7'] : 'wms7');
										echo '"' . $remap_wms7 . '":"'.$query_result['wms7'].'",'.PHP_EOL;
										$remap_wms8 = isset($_POST['remap_wms8']) ? $_POST['remap_wms8'] : (isset($_GET['remap_wms8']) ? $_GET['remap_wms8'] : 'wms8');
										echo '"' . $remap_wms8 . '":"'.$query_result['wms8'].'",'.PHP_EOL;
										$remap_wms9 = isset($_POST['remap_wms9']) ? $_POST['remap_wms9'] : (isset($_GET['remap_wms9']) ? $_GET['remap_wms9'] : 'wms9');
										echo '"' . $remap_wms9 . '":"'.$query_result['wms9'].'",'.PHP_EOL;
										$remap_wms10 = isset($_POST['remap_wms10']) ? $_POST['remap_wms10'] : (isset($_GET['remap_wms10']) ? $_GET['remap_wms10'] : 'wms10');
										echo '"' . $remap_wms10 . '":"'.$query_result['wms10'].'",'.PHP_EOL;
										$remap_kml_timestamp = isset($_POST['remap_kml_timestamp']) ? $_POST['remap_kml_timestamp'] : (isset($_GET['remap_kml_timestamp']) ? $_GET['remap_kml_timestamp'] : 'kml_timestamp');
										echo '"' . $remap_kml_timestamp . '":"'.$query_result['kml_timestamp'].'",'.PHP_EOL;
										$address = stripslashes(str_replace('"', '\'', $query_result['address']));
										$remap_address = isset($_POST['remap_address']) ? $_POST['remap_address'] : (isset($_GET['remap_address']) ? $_GET['remap_address'] : 'address');
										echo '"' . $remap_address . '":"'.$address.'"'.PHP_EOL;
										echo '}';
									echo '}';								
									if ($callback != NULL) { echo ');'; }
								} else if ($id == null) {
									header('Cache-Control: no-cache, must-revalidate');
									header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":false,'.PHP_EOL;
									echo '"message":"' . esc_attr__('API parameter id has to be set','lmm') . '",'.PHP_EOL;
									echo '"data": { }'.PHP_EOL;
									echo '}';
									if ($callback != NULL) { echo ');'; }
								} else {
									header('Cache-Control: no-cache, must-revalidate');
									header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":false,'.PHP_EOL;
									echo '"message":"' . sprintf(esc_attr__('A marker with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
									echo '"data": { }'.PHP_EOL;
									echo '}';
									if ($callback != NULL) { echo ');'; }
								} //info: end check if query_result markers >=1 / view
							} else if ($type == 'layer') {
								$query_result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name_layers WHERE id = %d", $id), ARRAY_A);
								if (count($query_result) >= 1) {
									header('Cache-Control: no-cache, must-revalidate');
									header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":true,'.PHP_EOL;
									echo '"message":"' . esc_attr__('API call was successful','lmm') . '",'.PHP_EOL;
									echo '"data": {'.PHP_EOL;									
										$remap_id = isset($_POST['remap_id']) ? $_POST['remap_id'] : (isset($_GET['remap_id']) ? $_GET['remap_id'] : 'id');
										echo '"' . $remap_id . '":"'.$query_result['id'].'",'.PHP_EOL;
										$remap_name = isset($_POST['remap_name']) ? $_POST['remap_name'] : (isset($_GET['remap_name']) ? $_GET['remap_name'] : 'name');
										echo '"' . $remap_name . '":"' . stripslashes($query_result['name']) . '",'.PHP_EOL;
										$remap_basemap = isset($_POST['remap_basemap']) ? $_POST['remap_basemap'] : (isset($_GET['remap_basemap']) ? $_GET['remap_basemap'] : 'basemap');
										echo '"' . $remap_basemap . '":"'.$query_result['basemap'].'",'.PHP_EOL;
										$remap_layerzoom = isset($_POST['remap_layerzoom']) ? $_POST['remap_layerzoom'] : (isset($_GET['remap_layerzoom']) ? $_GET['remap_layerzoom'] : 'layerzoom');
										echo '"' . $remap_layerzoom . '":"'.$query_result['layerzoom'].'",'.PHP_EOL;
										$remap_mapwidth = isset($_POST['remap_mapwidth']) ? $_POST['remap_mapwidth'] : (isset($_GET['remap_mapwidth']) ? $_GET['remap_mapwidth'] : 'mapwidth');
										echo '"' . $remap_mapwidth . '":"' . $query_result['mapwidth'] . '",'.PHP_EOL;
										$remap_mapwidthunit = isset($_POST['remap_mapwidthunit']) ? $_POST['remap_mapwidthunit'] : (isset($_GET['remap_mapwidthunit']) ? $_GET['remap_mapwidthunit'] : 'mapwidthunit');
										echo '"' . $remap_mapwidthunit . '":"' . $query_result['mapwidthunit'] . '",'.PHP_EOL;
										$remap_mapheight = isset($_POST['remap_mapheight']) ? $_POST['remap_mapheight'] : (isset($_GET['remap_mapheight']) ? $_GET['remap_mapheight'] : 'mapheight');
										echo '"' . $remap_mapheight . '":"' . $query_result['mapheight'] . '",'.PHP_EOL;
										$remap_panel = isset($_POST['remap_panel']) ? $_POST['remap_panel'] : (isset($_GET['remap_panel']) ? $_GET['remap_panel'] : 'panel');
										echo '"' . $remap_panel . '":"' . $query_result['panel'] . '",'.PHP_EOL;
										$remap_layerviewlat = isset($_POST['remap_layerviewlat']) ? $_POST['remap_layerviewlat'] : (isset($_GET['remap_layerviewlat']) ? $_GET['remap_layerviewlat'] : 'layerviewlat');
										echo '"' . $remap_layerviewlat . '":"' . $query_result['layerviewlat'] . '",'.PHP_EOL;
										$remap_layerviewlon = isset($_POST['remap_layerviewlon']) ? $_POST['remap_layerviewlon'] : (isset($_GET['remap_layerviewlon']) ? $_GET['remap_layerviewlon'] : 'layerviewlon');
										echo '"' . $remap_layerviewlon . '":"' . $query_result['layerviewlon'] . '",'.PHP_EOL;
										$remap_createdby = isset($_POST['remap_createdby']) ? $_POST['remap_createdby'] : (isset($_GET['remap_createdby']) ? $_GET['remap_createdby'] : 'createdby');
										echo '"' . $remap_createdby . '":"' . stripslashes($query_result['createdby']) . '",'.PHP_EOL;
										$remap_createdon = isset($_POST['remap_createdon']) ? $_POST['remap_createdon'] : (isset($_GET['remap_createdon']) ? $_GET['remap_createdon'] : 'createdon');
										echo '"' . $remap_createdon . '":"' . $query_result['createdon'] . '",'.PHP_EOL;
										$remap_updatedby = isset($_POST['remap_updatedby']) ? $_POST['remap_updatedby'] : (isset($_GET['remap_updatedby']) ? $_GET['remap_updatedby'] : 'updatedby');
										echo '"' . $remap_updatedby . '":"' . stripslashes($query_result['updatedby']) . '",'.PHP_EOL;
										$remap_updatedon = isset($_POST['remap_updatedon']) ? $_POST['remap_updatedon'] : (isset($_GET['remap_updatedon']) ? $_GET['remap_updatedon'] : 'updatedon');
										echo '"' . $remap_updatedon . '":"' . stripslashes($query_result['updatedon']) . '",'.PHP_EOL;
										$remap_controlbox = isset($_POST['remap_controlbox']) ? $_POST['remap_controlbox'] : (isset($_GET['remap_controlbox']) ? $_GET['remap_controlbox'] : 'controlbox');
										echo '"' . $remap_controlbox . '":"'.$query_result['controlbox'].'",'.PHP_EOL;
										$remap_overlays_custom = isset($_POST['remap_overlays_custom']) ? $_POST['remap_overlays_custom'] : (isset($_GET['remap_overlays_custom']) ? $_GET['remap_overlays_custom'] : 'overlays_custom');
										echo '"' . $remap_overlays_custom . '":"'.$query_result['overlays_custom'].'",'.PHP_EOL;
										$remap_overlays_custom2 = isset($_POST['remap_overlays_custom2']) ? $_POST['remap_overlays_custom2'] : (isset($_GET['remap_overlays_custom2']) ? $_GET['remap_overlays_custom2'] : 'overlays_custom2');
										echo '"' . $remap_overlays_custom2 . '":"'.$query_result['overlays_custom2'].'",'.PHP_EOL;
										$remap_overlays_custom3 = isset($_POST['remap_overlays_custom3']) ? $_POST['remap_overlays_custom3'] : (isset($_GET['remap_overlays_custom3']) ? $_GET['remap_overlays_custom3'] : 'overlays_custom3');
										echo '"' . $remap_overlays_custom3 . '":"'.$query_result['overlays_custom3'].'",'.PHP_EOL;
										$remap_overlays_custom4 = isset($_POST['remap_overlays_custom4']) ? $_POST['remap_overlays_custom4'] : (isset($_GET['remap_overlays_custom4']) ? $_GET['remap_overlays_custom4'] : 'overlays_custom4');
										echo '"' . $remap_overlays_custom4 . '":"'.$query_result['overlays_custom4'].'",'.PHP_EOL;
										$remap_wms = isset($_POST['remap_wms']) ? $_POST['remap_wms'] : (isset($_GET['remap_wms']) ? $_GET['remap_wms'] : 'wms');
										echo '"' . $remap_wms . '":"'.$query_result['wms'].'",'.PHP_EOL;
										$remap_wms2 = isset($_POST['remap_wms2']) ? $_POST['remap_wms2'] : (isset($_GET['remap_wms2']) ? $_GET['remap_wms2'] : 'wms2');
										echo '"' . $remap_wms2 . '":"'.$query_result['wms2'].'",'.PHP_EOL;
										$remap_wms3 = isset($_POST['remap_wms3']) ? $_POST['remap_wms3'] : (isset($_GET['remap_wms3']) ? $_GET['remap_wms3'] : 'wms3');
										echo '"' . $remap_wms3 . '":"'.$query_result['wms3'].'",'.PHP_EOL;
										$remap_wms4 = isset($_POST['remap_wms4']) ? $_POST['remap_wms4'] : (isset($_GET['remap_wms4']) ? $_GET['remap_wms4'] : 'wms4');
										echo '"' . $remap_wms4 . '":"'.$query_result['wms4'].'",'.PHP_EOL;
										$remap_wms5 = isset($_POST['remap_wms5']) ? $_POST['remap_wms5'] : (isset($_GET['remap_wms5']) ? $_GET['remap_wms5'] : 'wms5');
										echo '"' . $remap_wms5 . '":"'.$query_result['wms5'].'",'.PHP_EOL;
										$remap_wms6 = isset($_POST['remap_wms6']) ? $_POST['remap_wms6'] : (isset($_GET['remap_wms6']) ? $_GET['remap_wms6'] : 'wms6');
										echo '"' . $remap_wms6 . '":"'.$query_result['wms6'].'",'.PHP_EOL;
										$remap_wms7 = isset($_POST['remap_wms7']) ? $_POST['remap_wms7'] : (isset($_GET['remap_wms7']) ? $_GET['remap_wms7'] : 'wms7');
										echo '"' . $remap_wms7 . '":"'.$query_result['wms7'].'",'.PHP_EOL;
										$remap_wms8 = isset($_POST['remap_wms8']) ? $_POST['remap_wms8'] : (isset($_GET['remap_wms8']) ? $_GET['remap_wms8'] : 'wms8');
										echo '"' . $remap_wms8 . '":"'.$query_result['wms8'].'",'.PHP_EOL;
										$remap_wms9 = isset($_POST['remap_wms9']) ? $_POST['remap_wms9'] : (isset($_GET['remap_wms9']) ? $_GET['remap_wms9'] : 'wms9');
										echo '"' . $remap_wms9 . '":"'.$query_result['wms9'].'",'.PHP_EOL;
										$remap_wms10 = isset($_POST['remap_wms10']) ? $_POST['remap_wms10'] : (isset($_GET['remap_wms10']) ? $_GET['remap_wms10'] : 'wms10');
										echo '"' . $remap_wms10 . '":"'.$query_result['wms10'].'",'.PHP_EOL;
										$remap_listmarkers = isset($_POST['remap_listmarkers']) ? $_POST['remap_listmarkers'] : (isset($_GET['remap_listmarkers']) ? $_GET['remap_listmarkers'] : 'listmarkers');
										echo '"' . $remap_listmarkers . '":"'.$query_result['listmarkers'].'",'.PHP_EOL;
										$remap_multi_layer_map = isset($_POST['remap_multi_layer_map']) ? $_POST['remap_multi_layer_map'] : (isset($_GET['remap_multi_layer_map']) ? $_GET['remap_multi_layer_map'] : 'multi_layer_map');
										echo '"' . $remap_multi_layer_map . '":"'.$query_result['multi_layer_map'].'",'.PHP_EOL;
										$remap_multi_layer_map_list = isset($_POST['remap_multi_layer_map_list']) ? $_POST['remap_multi_layer_map_list'] : (isset($_GET['remap_multi_layer_map_list']) ? $_GET['remap_multi_layer_map_list'] : 'multi_layer_map_list');
										echo '"' . $remap_multi_layer_map_list . '":"'.$query_result['multi_layer_map_list'].'",'.PHP_EOL;
										$address = stripslashes(str_replace('"', '\'', $query_result['address']));
										$remap_address = isset($_POST['remap_address']) ? $_POST['remap_address'] : (isset($_GET['remap_address']) ? $_GET['remap_address'] : 'address');
										echo '"' . $remap_address . '":"'.$address.'"'.PHP_EOL;
										echo '}';
									echo '}';
									if ($callback != NULL) { echo ');'; }
								} else {
									header('Cache-Control: no-cache, must-revalidate');
									header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":false,'.PHP_EOL;
									echo '"message":"' . sprintf(esc_attr__('A layer with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
									echo '"data": { }'.PHP_EOL;
									echo '}';
									if ($callback != NULL) { echo ');'; }
								} //info: end check if query_result layers >=1 / view
							} else if ($type == '') {
								header('Cache-Control: no-cache, must-revalidate');
								header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
								header('Content-type: application/json; charset=utf-8');
								if ($callback != NULL) { echo $callback . '('; }
								echo '{'.PHP_EOL;
								echo '"success":false,'.PHP_EOL;
								echo '"message":"' . esc_attr__('API parameter type has to be set','lmm') . '",'.PHP_EOL;
								echo '"data": { }'.PHP_EOL;
								echo '}';
								if ($callback != NULL) { echo ');'; }
							} else {
								header('Cache-Control: no-cache, must-revalidate');
								header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
								header('Content-type: application/json; charset=utf-8');
								if ($callback != NULL) { echo $callback . '('; }
								echo '{'.PHP_EOL;
								echo '"success":false,'.PHP_EOL;
								echo '"message":"' . esc_attr__('API parameter type is invalid','lmm') . '",'.PHP_EOL;
								echo '"data": { }'.PHP_EOL;
								echo '}';
								if ($callback != NULL) { echo ');'; }
							} //info: end type check / view
						} else {
							header('Cache-Control: no-cache, must-revalidate');
							header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
							header('Content-type: application/json; charset=utf-8');
							if ($callback != NULL) { echo $callback . '('; }
							echo '{'.PHP_EOL;
							echo '"success":false,'.PHP_EOL;
							echo '"message":"' . esc_attr__('API action is not allowed','lmm') . '",'.PHP_EOL;
							echo '"data": { }'.PHP_EOL;
							echo '}';
							if ($callback != NULL) { echo ');'; }
						} //info: end permission check / view
					/******************************
					* action add                  *
					******************************/
					} else if ($action == 'add') {
						if ( $lmm_options['api_permissions_add'] == TRUE ) {
							if ($type == 'marker') {
	
							} else if ($type == 'layer') {
	
							} else if ($type == '') {
								header('Cache-Control: no-cache, must-revalidate');
								header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
								header('Content-type: application/json; charset=utf-8');
								if ($callback != NULL) { echo $callback . '('; }
								echo '{'.PHP_EOL;
								echo '"success":false,'.PHP_EOL;
								echo '"message":"' . esc_attr__('API parameter type has to be set','lmm') . '",'.PHP_EOL;
								echo '"data": { }'.PHP_EOL;
								echo '}';
								if ($callback != NULL) { echo ');'; }
							} else {
								header('Cache-Control: no-cache, must-revalidate');
								header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
								header('Content-type: application/json; charset=utf-8');
								if ($callback != NULL) { echo $callback . '('; }
								echo '{'.PHP_EOL;
								echo '"success":false,'.PHP_EOL;
								echo '"message":"' . esc_attr__('API parameter type is invalid','lmm') . '",'.PHP_EOL;
								echo '"data": { }'.PHP_EOL;
								echo '}';
								if ($callback != NULL) { echo ');'; }
							} //info: end type check / add
						} else {
							header('Cache-Control: no-cache, must-revalidate');
							header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
							header('Content-type: application/json; charset=utf-8');
							if ($callback != NULL) { echo $callback . '('; }
							echo '{'.PHP_EOL;
							echo '"success":false,'.PHP_EOL;
							echo '"message":"' . esc_attr__('API action is not allowed','lmm') . '",'.PHP_EOL;
							echo '"data": { }'.PHP_EOL;
							echo '}';
							if ($callback != NULL) { echo ');'; }
						} //info: end permission check / add
					/******************************
					* action update               *
					******************************/
					} else if ($action == 'update') {
						if ( $lmm_options['api_permissions_update'] == TRUE ) {
							if ($type == 'marker') {
								$query_result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name_markers WHERE id = %d", $id), ARRAY_A);
								if (count($query_result) >= 1) {
	
								} else {
									header('Cache-Control: no-cache, must-revalidate');
									header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":false,'.PHP_EOL;
									echo '"message":"' . sprintf(esc_attr__('A marker with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
									echo '"data": { }'.PHP_EOL;
									echo '}';
									if ($callback != NULL) { echo ');'; }
								} //info: end check if query_result markers >=1 / update
							} else if ($type == 'layer') {
								$query_result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name_layers WHERE id = %d", $id), ARRAY_A);
								if (count($query_result) >= 1) {
	
								} else {
									header('Cache-Control: no-cache, must-revalidate');
									header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":false,'.PHP_EOL;
									echo '"message":"' . sprintf(esc_attr__('A layer with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
									echo '"data": { }'.PHP_EOL;
									echo '}';
									if ($callback != NULL) { echo ');'; }
								} //info: end check if query_result layers >=1 / update
	
							} else if ($type == '') {
								header('Cache-Control: no-cache, must-revalidate');
								header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
								header('Content-type: application/json; charset=utf-8');
								if ($callback != NULL) { echo $callback . '('; }
								echo '{'.PHP_EOL;
								echo '"success":false,'.PHP_EOL;
								echo '"message":"' . esc_attr__('API parameter type has to be set','lmm') . '",'.PHP_EOL;
								echo '"data": { }'.PHP_EOL;
								echo '}';
								if ($callback != NULL) { echo ');'; }
							} else {
								header('Cache-Control: no-cache, must-revalidate');
								header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
								header('Content-type: application/json; charset=utf-8');
								if ($callback != NULL) { echo $callback . '('; }
								echo '{'.PHP_EOL;
								echo '"success":false,'.PHP_EOL;
								echo '"message":"' . esc_attr__('API parameter type is invalid','lmm') . '",'.PHP_EOL;
								echo '"data": { }'.PHP_EOL;
								echo '}';
								if ($callback != NULL) { echo ');'; }
							} //info: end type check / update
						} else {
							header('Cache-Control: no-cache, must-revalidate');
							header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
							header('Content-type: application/json; charset=utf-8');
							if ($callback != NULL) { echo $callback . '('; }
							echo '{'.PHP_EOL;
							echo '"success":false,'.PHP_EOL;
							echo '"message":"' . esc_attr__('API action is not allowed','lmm') . '",'.PHP_EOL;
							echo '}';
							if ($callback != NULL) { echo ');'; }
						} //info: end permission check / update
					/******************************
					* action delete                  *
					******************************/
					} else if ($action == 'delete') {
						if ( $lmm_options['api_permissions_delete'] == TRUE ) {
							if ($type == 'marker') {
								$query_result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name_markers WHERE id = %d", $id), ARRAY_A);
								if (count($query_result) >= 1) {
	
								} else {
									header('Cache-Control: no-cache, must-revalidate');
									header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":false,'.PHP_EOL;
									echo '"message":"' . sprintf(esc_attr__('A marker with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
									echo '"data": { }'.PHP_EOL;
									echo '}';
									if ($callback != NULL) { echo ');'; }
								} //info: end check if query_result markers >=1 / view
							} else if ($type == 'layer') {
								$query_result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name_layers WHERE id = %d", $id), ARRAY_A);
								if (count($query_result) >= 1) {
	
								} else {
									header('Cache-Control: no-cache, must-revalidate');
									header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":false,'.PHP_EOL;
									echo '"message":"' . sprintf(esc_attr__('A layer with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
									echo '"data": { }'.PHP_EOL;
									echo '}';
									if ($callback != NULL) { echo ');'; }
								} //info: end check if query_result markers >=1 / view
							} else if ($type == '') {
								header('Cache-Control: no-cache, must-revalidate');
								header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
								header('Content-type: application/json; charset=utf-8');
								if ($callback != NULL) { echo $callback . '('; }
								echo '{'.PHP_EOL;
								echo '"success":false,'.PHP_EOL;
								echo '"message":"' . esc_attr__('API parameter type has to be set','lmm') . '",'.PHP_EOL;
								echo '"data": { }'.PHP_EOL;
								echo '}';
								if ($callback != NULL) { echo ');'; }
							} else {
								header('Cache-Control: no-cache, must-revalidate');
								header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
								header('Content-type: application/json; charset=utf-8');
								if ($callback != NULL) { echo $callback . '('; }
								echo '{'.PHP_EOL;
								echo '"success":false,'.PHP_EOL;
								echo '"message":"' . esc_attr__('API parameter type is invalid','lmm') . '",'.PHP_EOL;
								echo '"data": { }'.PHP_EOL;
								echo '}';
								if ($callback != NULL) { echo ');'; }
							} //info: end type check / delete
						} else {
							header('Cache-Control: no-cache, must-revalidate');
							header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
							header('Content-type: application/json; charset=utf-8');
							if ($callback != NULL) { echo $callback . '('; }
							echo '{'.PHP_EOL;
							echo '"success":false,'.PHP_EOL;
							echo '"message":"' . esc_attr__('API action is not allowed','lmm') . '",'.PHP_EOL;
							echo '"data": { }'.PHP_EOL;
							echo '}';
							if ($callback != NULL) { echo ');'; }
						} //info: end permission check / delete
					} else if ($action == '') {
						header('Cache-Control: no-cache, must-revalidate');
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
						header('Content-type: application/json; charset=utf-8');
						if ($callback != NULL) { echo $callback . '('; }
						echo '{'.PHP_EOL;
						echo '"success":false,'.PHP_EOL;
						echo '"message":"' . esc_attr__('API parameter action has to be set','lmm') . '",'.PHP_EOL;
						echo '"data": { }'.PHP_EOL;
						echo '}';
						if ($callback != NULL) { echo ');'; }
					} else {
						header('Cache-Control: no-cache, must-revalidate');
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
						header('Content-type: application/json; charset=utf-8');
						if ($callback != NULL) { echo $callback . '('; }
						echo '{'.PHP_EOL;
						echo '"success":false,'.PHP_EOL;
						echo '"message":"' . esc_attr__('API parameter action is invalid','lmm') . '",'.PHP_EOL;
						echo '"data": { }'.PHP_EOL;
						echo '}';
						if ($callback != NULL) { echo ');'; }
					} //info: end check action / general
				} else {
					header('Cache-Control: no-cache, must-revalidate');
					header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
					header('Content-type: application/json; charset=utf-8');
					if ($callback != NULL) { echo $callback . '('; }
					echo '{'.PHP_EOL;
					echo '"success":false,'.PHP_EOL;
					echo '"message":"' . sprintf(esc_attr__('API access via IP %1s is not allowed','lmm'), $_SERVER['REMOTE_ADDR']) . '",'.PHP_EOL;
					echo '"data": { }'.PHP_EOL;
					echo '}';
					if ($callback != NULL) { echo ');'; }
				} //info: end ip access check / general				
			} else {
				header('Cache-Control: no-cache, must-revalidate');
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
				header('Content-type: application/json; charset=utf-8');
				if ($callback != NULL) { echo $callback . '('; }
				echo '{'.PHP_EOL;
				echo '"success":false,'.PHP_EOL;
				echo '"message":"' . esc_attr__('API key is invalid','lmm') . '",'.PHP_EOL;
				echo '"data": { }'.PHP_EOL;
				echo '}';
				if ($callback != NULL) { echo ');'; }
			} //info: end key validity check / general
		} else { //info: change if v2 is released
			header('Cache-Control: no-cache, must-revalidate');
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Content-type: application/json; charset=utf-8');
			if ($callback != NULL) { echo $callback . '('; }
			echo '{'.PHP_EOL;
			echo '"success":false,'.PHP_EOL;
			echo '"message":"' . esc_attr__('API version is invalid','lmm') . '",'.PHP_EOL;
			echo '"data": { }'.PHP_EOL;
			echo '}';
			if ($callback != NULL) { echo ');'; }
		} //info: end API version check
	} else {
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json; charset=utf-8');
		if ($callback != NULL) { echo $callback . '('; }
		echo '{'.PHP_EOL;
		echo '"success":false,'.PHP_EOL;
		echo '"message":"' . esc_attr__('API is disabled','lmm') . '",'.PHP_EOL;
		echo '"data": { }'.PHP_EOL;
		echo '}';
		if ($callback != NULL) { echo ');'; }
	} //info: end api_status enabled
} //info: end plugin active check
?>