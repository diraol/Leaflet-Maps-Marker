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
 * Returns a Lat and Lng from an Address using Google Geocoder API. It does not require any Google API Key
 * @author Abdullah Rubiyath
 */
function lmm_getLatLng($address) {
	$url = 'http://maps.googleapis.com/maps/api/geocode/xml?' . 'address=' . $address . '&sensor=false';
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$xml_raw = curl_exec($ch);
	curl_close($ch);
	$xml = simplexml_load_string($xml_raw);
	$response = array();
	$statusCode = $xml->status;
	if ( ($statusCode != false) && ($statusCode != NULL) && ($statusCode == 'OK') ) {
		$latDom = $xml->result[0]->geometry->location->lat;
		$lonDom = $xml->result[0]->geometry->location->lng;
		$addressDom = $xml->result[0]->formatted_address;
		if ($latDom != NULL) { 
			$response = array (
				'status' 	=> true,
				'lat' 		=> $latDom,
				'lon' 		=> $lonDom,
				'address'	=> $addressDom
			);
			return $response;
		}
	}
	$response = array (
		'status' => false,
		'message' => "Oh snap! Error in geocoding. Please check address"
	);
	return $response;
}
$lmm_options = get_option( 'leafletmapsmarker_options' );
$callback = isset($_POST['callback']) ? $_POST['callback'] : (isset($_GET['callback']) ? $_GET['callback'] :  $lmm_options['api_json_callback']);
$format = isset($_POST['format']) ? $_POST['format'] : (isset($_GET['format']) ? $_GET['format'] : $lmm_options['api_default_format']);

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
	if ($format == 'json') {
		header('Content-type: application/json; charset=utf-8');
		if ($callback != NULL) { echo $callback . '('; }
		echo '{'.PHP_EOL;
		echo '"success":false,'.PHP_EOL;
		echo '"message":"' . sprintf(esc_attr__('The WordPress plugin "Leaflet Maps Marker" is inactive on this site and therefore this API link is not working.<br/><br/>Please contact the site owner (%1s) who can activate this plugin again.','lmm'), get_bloginfo('admin_email') ) . '",'.PHP_EOL;
		echo '"data": { }'.PHP_EOL;
		echo '}';
		if ($callback != NULL) { echo ');'; }
	} else if ($format == 'xml') {
		header('Content-type: application/xml; charset=utf-8');
		echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
		echo '<mapsmarker>'.PHP_EOL;
		echo '<success>false</success>'.PHP_EOL;
		echo '<message>' . sprintf(esc_attr__('The WordPress plugin "Leaflet Maps Marker" is inactive on this site and therefore this API link is not working.<br/><br/>Please contact the site owner (%1s) who can activate this plugin again.','lmm'), get_bloginfo('admin_email') ) . '</message>'.PHP_EOL;
		echo '<data></data>'.PHP_EOL;
		echo '</mapsmarker>';
	}
} else {
	global $wpdb;
	$table_name_markers = $wpdb->prefix.'leafletmapsmarker_markers';
	$table_name_layers = $wpdb->prefix.'leafletmapsmarker_layers';
	$version = isset($_POST['version']) ? $_POST['version'] : (isset($_GET['version']) ? $_GET['version'] : '');
	//info: remap marker + layer
	$remap_id = isset($_POST['remap_id']) ? $_POST['remap_id'] : (isset($_GET['remap_id']) ? $_GET['remap_id'] : 'id');
	$remap_markername = isset($_POST['remap_markername']) ? $_POST['remap_markername'] : (isset($_GET['remap_markername']) ? $_GET['remap_markername'] : 'markername');
	$remap_basemap = isset($_POST['remap_basemap']) ? $_POST['remap_basemap'] : (isset($_GET['remap_basemap']) ? $_GET['remap_basemap'] : 'basemap');
	$remap_layer = isset($_POST['remap_layer']) ? $_POST['remap_layer'] : (isset($_GET['remap_layer']) ? $_GET['remap_layer'] : 'layer');
	$remap_lat = isset($_POST['remap_lat']) ? $_POST['remap_lat'] : (isset($_GET['remap_lat']) ? $_GET['remap_lat'] : 'lat');
	$remap_lon = isset($_POST['remap_lon']) ? $_POST['remap_lon'] : (isset($_GET['remap_lon']) ? $_GET['remap_lon'] : 'lon');
	$remap_icon = isset($_POST['remap_icon']) ? $_POST['remap_icon'] : (isset($_GET['remap_icon']) ? $_GET['remap_icon'] : 'icon');
	$remap_popuptext = isset($_POST['remap_popuptext']) ? $_POST['remap_popuptext'] : (isset($_GET['remap_popuptext']) ? $_GET['remap_popuptext'] : 'popuptext');
	$remap_zoom = isset($_POST['remap_zoom']) ? $_POST['remap_zoom'] : (isset($_GET['remap_zoom']) ? $_GET['remap_zoom'] : 'zoom');
	$remap_openpopup = isset($_POST['remap_openpopup']) ? $_POST['remap_openpopup'] : (isset($_GET['remap_openpopup']) ? $_GET['remap_openpopup'] : 'openpopup');
	$remap_mapwidth = isset($_POST['remap_mapwidth']) ? $_POST['remap_mapwidth'] : (isset($_GET['remap_mapwidth']) ? $_GET['remap_mapwidth'] : 'mapwidth');
	$remap_mapwidthunit = isset($_POST['remap_mapwidthunit']) ? $_POST['remap_mapwidthunit'] : (isset($_GET['remap_mapwidthunit']) ? $_GET['remap_mapwidthunit'] : 'mapwidthunit');
	$remap_mapheight = isset($_POST['remap_mapheight']) ? $_POST['remap_mapheight'] : (isset($_GET['remap_mapheight']) ? $_GET['remap_mapheight'] : 'mapheight');
	$remap_panel = isset($_POST['remap_panel']) ? $_POST['remap_panel'] : (isset($_GET['remap_panel']) ? $_GET['remap_panel'] : 'panel');
	$remap_createdby = isset($_POST['remap_createdby']) ? $_POST['remap_createdby'] : (isset($_GET['remap_createdby']) ? $_GET['remap_createdby'] : 'createdby');
	$remap_createdon = isset($_POST['remap_createdon']) ? $_POST['remap_createdon'] : (isset($_GET['remap_createdon']) ? $_GET['remap_createdon'] : 'createdon');
	$remap_updatedby = isset($_POST['remap_updatedby']) ? $_POST['remap_updatedby'] : (isset($_GET['remap_updatedby']) ? $_GET['remap_updatedby'] : 'updatedby');
	$remap_updatedon = isset($_POST['remap_updatedon']) ? $_POST['remap_updatedon'] : (isset($_GET['remap_updatedon']) ? $_GET['remap_updatedon'] : 'updatedon');
	$remap_controlbox = isset($_POST['remap_controlbox']) ? $_POST['remap_controlbox'] : (isset($_GET['remap_controlbox']) ? $_GET['remap_controlbox'] : 'controlbox');
	$remap_overlays_custom = isset($_POST['remap_overlays_custom']) ? $_POST['remap_overlays_custom'] : (isset($_GET['remap_overlays_custom']) ? $_GET['remap_overlays_custom'] : 'overlays_custom');
	$remap_overlays_custom2 = isset($_POST['remap_overlays_custom2']) ? $_POST['remap_overlays_custom2'] : (isset($_GET['remap_overlays_custom2']) ? $_GET['remap_overlays_custom2'] : 'overlays_custom2');
	$remap_overlays_custom3 = isset($_POST['remap_overlays_custom3']) ? $_POST['remap_overlays_custom3'] : (isset($_GET['remap_overlays_custom3']) ? $_GET['remap_overlays_custom3'] : 'overlays_custom3');
	$remap_overlays_custom4 = isset($_POST['remap_overlays_custom4']) ? $_POST['remap_overlays_custom4'] : (isset($_GET['remap_overlays_custom4']) ? $_GET['remap_overlays_custom4'] : 'overlays_custom4');
	$remap_wms = isset($_POST['remap_wms']) ? $_POST['remap_wms'] : (isset($_GET['remap_wms']) ? $_GET['remap_wms'] : 'wms');
	$remap_wms2 = isset($_POST['remap_wms2']) ? $_POST['remap_wms2'] : (isset($_GET['remap_wms2']) ? $_GET['remap_wms2'] : 'wms2');
	$remap_wms3 = isset($_POST['remap_wms3']) ? $_POST['remap_wms3'] : (isset($_GET['remap_wms3']) ? $_GET['remap_wms3'] : 'wms3');
	$remap_wms4 = isset($_POST['remap_wms4']) ? $_POST['remap_wms4'] : (isset($_GET['remap_wms4']) ? $_GET['remap_wms4'] : 'wms4');
	$remap_wms5 = isset($_POST['remap_wms5']) ? $_POST['remap_wms5'] : (isset($_GET['remap_wms5']) ? $_GET['remap_wms5'] : 'wms5');
	$remap_wms6 = isset($_POST['remap_wms6']) ? $_POST['remap_wms6'] : (isset($_GET['remap_wms6']) ? $_GET['remap_wms6'] : 'wms6');
	$remap_wms7 = isset($_POST['remap_wms7']) ? $_POST['remap_wms7'] : (isset($_GET['remap_wms7']) ? $_GET['remap_wms7'] : 'wms7');
	$remap_wms8 = isset($_POST['remap_wms8']) ? $_POST['remap_wms8'] : (isset($_GET['remap_wms8']) ? $_GET['remap_wms8'] : 'wms8');
	$remap_wms9 = isset($_POST['remap_wms9']) ? $_POST['remap_wms9'] : (isset($_GET['remap_wms9']) ? $_GET['remap_wms9'] : 'wms9');
	$remap_wms10 = isset($_POST['remap_wms10']) ? $_POST['remap_wms10'] : (isset($_GET['remap_wms10']) ? $_GET['remap_wms10'] : 'wms10');
	$remap_kml_timestamp = isset($_POST['remap_kml_timestamp']) ? $_POST['remap_kml_timestamp'] : (isset($_GET['remap_kml_timestamp']) ? $_GET['remap_kml_timestamp'] : 'kml_timestamp');
	$remap_address = isset($_POST['remap_address']) ? $_POST['remap_address'] : (isset($_GET['remap_address']) ? $_GET['remap_address'] : 'address');
	//info: remap layer only
	$remap_name = isset($_POST['remap_name']) ? $_POST['remap_name'] : (isset($_GET['remap_name']) ? $_GET['remap_name'] : 'name');
	$remap_layerzoom = isset($_POST['remap_layerzoom']) ? $_POST['remap_layerzoom'] : (isset($_GET['remap_layerzoom']) ? $_GET['remap_layerzoom'] : 'layerzoom');
	$remap_layerviewlat = isset($_POST['remap_layerviewlat']) ? $_POST['remap_layerviewlat'] : (isset($_GET['remap_layerviewlat']) ? $_GET['remap_layerviewlat'] : 'layerviewlat');
	$remap_layerviewlon = isset($_POST['remap_layerviewlon']) ? $_POST['remap_layerviewlon'] : (isset($_GET['remap_layerviewlon']) ? $_GET['remap_layerviewlon'] : 'layerviewlon');
	$remap_listmarkers = isset($_POST['remap_listmarkers']) ? $_POST['remap_listmarkers'] : (isset($_GET['remap_listmarkers']) ? $_GET['remap_listmarkers'] : 'listmarkers');
	$remap_multi_layer_map = isset($_POST['remap_multi_layer_map']) ? $_POST['remap_multi_layer_map'] : (isset($_GET['remap_multi_layer_map']) ? $_GET['remap_multi_layer_map'] : 'multi_layer_map');
	$remap_multi_layer_map_list = isset($_POST['remap_multi_layer_map_list']) ? $_POST['remap_multi_layer_map_list'] : (isset($_GET['remap_multi_layer_map_list']) ? $_GET['remap_multi_layer_map_list'] : 'multi_layer_map_list');
	$remap_address = isset($_POST['remap_address']) ? $_POST['remap_address'] : (isset($_GET['remap_address']) ? $_GET['remap_address'] : 'address');

	if ($lmm_options['api_status'] == 'enabled') {

		if ( ($version == '1') || ($version == '') ) { //info: change OR condition if v2 is available
			$api_key = isset($_POST['key']) ? $_POST['key'] : (isset($_GET['key']) ? $_GET['key'] : '');

			if ($api_key == $lmm_options['api_key']) {
				$referer = wp_get_referer();

				if ( ($lmm_options['api_allowed_referer'] == NULL) || ( ($lmm_options['api_allowed_referer'] != NULL) && ($referer == $lmm_options['api_allowed_referer'])) ) {

					if ( ($lmm_options['api_allowed_ip'] == null) || (($lmm_options['api_allowed_ip'] != null) && (strpos ($_SERVER['REMOTE_ADDR'], str_replace("..",".",str_replace("...",".",str_replace("*", "", $lmm_options['api_allowed_ip'])))) === 0)) ) {
						$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
						$id = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : '');
						$type = isset($_POST['type']) ? $_POST['type'] : (isset($_GET['type']) ? $_GET['type'] : '');
	
						if ($action == 'view') {
							if ( $lmm_options['api_permissions_view'] == TRUE ) {
								if ($type == 'marker') {
									$query_result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name_markers WHERE id = %d", $id), ARRAY_A);
									if (count($query_result) >= 1) {
										$mpopuptext = stripslashes(str_replace('"', '\'', preg_replace('/(\015\012)|(\015)|(\012)/','<br/>',$query_result['popuptext'])));
										$address = stripslashes(str_replace('"', '\'', $query_result['address']));
										if ($format == 'json') {
											header('Cache-Control: no-cache, must-revalidate');
											header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
											header('Content-type: application/json; charset=utf-8');
											if ($callback != NULL) { echo $callback . '('; }
											echo '{'.PHP_EOL;
											echo '"success":true,'.PHP_EOL;
											echo '"message":"' . esc_attr__('API call was successful','lmm') . '",'.PHP_EOL;
											echo '"data": {'.PHP_EOL;
												echo '"' . $remap_id . '":"'.$query_result['id'].'",'.PHP_EOL;
												echo '"' . $remap_markername . '":"' . stripslashes($query_result['markername']) . '",'.PHP_EOL;
												echo '"' . $remap_basemap . '":"'.$query_result['basemap'].'",'.PHP_EOL;
												echo '"' . $remap_layer . '":"'.$query_result['layer'].'",'.PHP_EOL;
												echo '"' . $remap_lat . '":"'.$query_result['lat'].'",'.PHP_EOL;
												echo '"' . $remap_lon . '":"'.$query_result['lon'].'",'.PHP_EOL;
												echo '"' . $remap_icon . '":"'.$query_result['icon'].'",'.PHP_EOL;
												echo '"' . $remap_popuptext . '":"' . $mpopuptext . '",'.PHP_EOL;
												echo '"' . $remap_zoom . '":"' . $query_result['zoom'] . '",'.PHP_EOL;
												echo '"' . $remap_openpopup . '":"' . $query_result['openpopup'] . '",'.PHP_EOL;
												echo '"' . $remap_mapwidth . '":"' . $query_result['mapwidth'] . '",'.PHP_EOL;
												echo '"' . $remap_mapwidthunit . '":"' . $query_result['mapwidthunit'] . '",'.PHP_EOL;
												echo '"' . $remap_mapheight . '":"' . $query_result['mapheight'] . '",'.PHP_EOL;
												echo '"' . $remap_panel . '":"' . $query_result['panel'] . '",'.PHP_EOL;
												echo '"' . $remap_createdby . '":"' . stripslashes($query_result['createdby']) . '",'.PHP_EOL;
												echo '"' . $remap_createdon . '":"' . $query_result['createdon'] . '",'.PHP_EOL;
												echo '"' . $remap_updatedby . '":"' . stripslashes($query_result['updatedby']) . '",'.PHP_EOL;
												echo '"' . $remap_updatedon . '":"' . stripslashes($query_result['updatedon']) . '",'.PHP_EOL;
												echo '"' . $remap_controlbox . '":"'.$query_result['controlbox'].'",'.PHP_EOL;
												echo '"' . $remap_overlays_custom . '":"'.$query_result['overlays_custom'].'",'.PHP_EOL;
												echo '"' . $remap_overlays_custom2 . '":"'.$query_result['overlays_custom2'].'",'.PHP_EOL;
												echo '"' . $remap_overlays_custom3 . '":"'.$query_result['overlays_custom3'].'",'.PHP_EOL;
												echo '"' . $remap_overlays_custom4 . '":"'.$query_result['overlays_custom4'].'",'.PHP_EOL;
												echo '"' . $remap_wms . '":"'.$query_result['wms'].'",'.PHP_EOL;
												echo '"' . $remap_wms2 . '":"'.$query_result['wms2'].'",'.PHP_EOL;
												echo '"' . $remap_wms3 . '":"'.$query_result['wms3'].'",'.PHP_EOL;
												echo '"' . $remap_wms4 . '":"'.$query_result['wms4'].'",'.PHP_EOL;
												echo '"' . $remap_wms5 . '":"'.$query_result['wms5'].'",'.PHP_EOL;
												echo '"' . $remap_wms6 . '":"'.$query_result['wms6'].'",'.PHP_EOL;
												echo '"' . $remap_wms7 . '":"'.$query_result['wms7'].'",'.PHP_EOL;
												echo '"' . $remap_wms8 . '":"'.$query_result['wms8'].'",'.PHP_EOL;
												echo '"' . $remap_wms9 . '":"'.$query_result['wms9'].'",'.PHP_EOL;
												echo '"' . $remap_wms10 . '":"'.$query_result['wms10'].'",'.PHP_EOL;
												echo '"' . $remap_kml_timestamp . '":"'.$query_result['kml_timestamp'].'",'.PHP_EOL;
												echo '"' . $remap_address . '":"'.$address.'"'.PHP_EOL;
												echo '}';
											echo '}';
											if ($callback != NULL) { echo ');'; }
										} else if ($format == 'xml') {
											header('Cache-Control: no-cache, must-revalidate');
											header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
											header('Content-type: application/xml; charset=utf-8');
											echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
											/*
											echo '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">'.PHP_EOL;
											echo '<xs:element name="success" type="boolean" />'.PHP_EOL;
											echo '<xs:element name="message" type="string" />'.PHP_EOL;
											echo '<xs:element name="data"><xs:complexType><xs:sequence>'.PHP_EOL;
												echo '<xs:element name="' . $remap_id . '" type="xs:integer"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_markername . '" type="xs:string"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_basemap . '" type="xs:string"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_layer . '" type="xs:integer"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_lat . '" type="xs:decimal"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_lon . '" type="xs:decimal"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_icon . '" type="xs:string"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_popuptext . '" type="xs:string"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_zoom . '" type="xs:integer"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_openpopup . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_mapwith . '" type="xs:integer"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_mapwithunit . '" type="xs:string"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_mapheight . '" type="xs:integer"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_panel . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_createdby . '" type="xs:string"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_createdon . '" type="xs:string"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_updatedby . '" type="xs:string"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_updatedon . '" type="xs:string"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_controlbox . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_overlays_custom . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_overlays_custom2 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_overlays_custom3 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_overlays_custom4 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_wms . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_wms2 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_wms3 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_wms4 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_wms5 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_wms6 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_wms7 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_wms8 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_wms9 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_wms10 . '" type="xs:boolean"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_kml_timestamp . '" type="xs:string"/>'.PHP_EOL;
												echo '<xs:element name="' . $remap_address . '" type="xs:string"/>'.PHP_EOL;
											echo '</xs:sequence></xs:complexType></xs:element></xs:schema>'.PHP_EOL;
											*/
											echo '<mapsmarker>'.PHP_EOL;
											echo '<success>true</success>'.PHP_EOL;
											echo '<message>' . esc_attr__('API call was successful','lmm') . '</message>'.PHP_EOL;
											echo '<data>'.PHP_EOL;
												echo '<' . $remap_id . '>'.$query_result['id'].'</' . $remap_id . '>'.PHP_EOL;
												echo '<' . $remap_markername . '><![CDATA[' . stripslashes($query_result['markername']) . ']]></' . $remap_markername . '>'.PHP_EOL;
												echo '<' . $remap_basemap . '>'.$query_result['basemap'].'</' . $remap_basemap . '>'.PHP_EOL;
												echo '<' . $remap_layer . '>'.$query_result['layer'].'</' . $remap_layer . '>'.PHP_EOL;
												echo '<' . $remap_lat . '>'.$query_result['lat'].'</' . $remap_lat . '>'.PHP_EOL;
												echo '<' . $remap_lon . '>'.$query_result['lon'].'</' . $remap_lon . '>'.PHP_EOL;
												echo '<' . $remap_icon . '><![CDATA['.$query_result['icon'].']]></' . $remap_icon . '>'.PHP_EOL;
												echo '<' . $remap_popuptext . '><![CDATA[' . $mpopuptext . ']]></' . $remap_popuptext . '>'.PHP_EOL;
												echo '<' . $remap_zoom . '>' . $query_result['zoom'] . '</' . $remap_zoom . '>'.PHP_EOL;
												echo '<' . $remap_openpopup . '>' . $query_result['openpopup'] . '</' . $remap_openpopup . '>'.PHP_EOL;
												echo '<' . $remap_mapwidth . '>' . $query_result['mapwidth'] . '</' . $remap_mapwidth . '>'.PHP_EOL;
												echo '<' . $remap_mapwidthunit . '>' . $query_result['mapwidthunit'] . '</' . $remap_mapwidthunit . '>'.PHP_EOL;
												echo '<' . $remap_mapheight . '>' . $query_result['mapheight'] . '</' . $remap_mapheight . '>'.PHP_EOL;
												echo '<' . $remap_panel . '>' . $query_result['panel'] . '</' . $remap_panel . '>'.PHP_EOL;
												echo '<' . $remap_createdby . '><![CDATA[' . stripslashes($query_result['createdby']) . ']]></' . $remap_createdby . '>'.PHP_EOL;
												echo '<' . $remap_createdon . '>' . $query_result['createdon'] . '</' . $remap_createdon . '>'.PHP_EOL;
												echo '<' . $remap_updatedby . '><![CDATA[' . stripslashes($query_result['updatedby']) . ']]></' . $remap_updatedby . '>'.PHP_EOL;
												echo '<' . $remap_updatedon . '>' . stripslashes($query_result['updatedon']) . '</' . $remap_updatedon . '>'.PHP_EOL;
												echo '<' . $remap_controlbox . '>'.$query_result['controlbox'].'</' . $remap_controlbox . '>'.PHP_EOL;
												echo '<' . $remap_overlays_custom . '>'.$query_result['overlays_custom'].'</' . $remap_overlays_custom . '>'.PHP_EOL;
												echo '<' . $remap_overlays_custom2 . '>'.$query_result['overlays_custom2'].'</' . $remap_overlays_custom2 . '>'.PHP_EOL;
												echo '<' . $remap_overlays_custom3 . '>'.$query_result['overlays_custom3'].'</' . $remap_overlays_custom3 . '>'.PHP_EOL;
												echo '<' . $remap_overlays_custom4 . '>'.$query_result['overlays_custom4'].'</' . $remap_overlays_custom4 . '>'.PHP_EOL;
												echo '<' . $remap_wms . '>'.$query_result['wms'].'</' . $remap_wms . '>'.PHP_EOL;
												echo '<' . $remap_wms2 . '>'.$query_result['wms2'].'</' . $remap_wms2 . '>'.PHP_EOL;
												echo '<' . $remap_wms3 . '>'.$query_result['wms3'].'</' . $remap_wms3 . '>'.PHP_EOL;
												echo '<' . $remap_wms4 . '>'.$query_result['wms4'].'</' . $remap_wms4 . '>'.PHP_EOL;
												echo '<' . $remap_wms5 . '>'.$query_result['wms5'].'</' . $remap_wms5 . '>'.PHP_EOL;
												echo '<' . $remap_wms6 . '>'.$query_result['wms6'].'</' . $remap_wms6 . '>'.PHP_EOL;
												echo '<' . $remap_wms7 . '>'.$query_result['wms7'].'</' . $remap_wms7 . '>'.PHP_EOL;
												echo '<' . $remap_wms8 . '>'.$query_result['wms8'].'</' . $remap_wms8 . '>'.PHP_EOL;
												echo '<' . $remap_wms9 . '>'.$query_result['wms9'].'</' . $remap_wms9 . '>'.PHP_EOL;
												echo '<' . $remap_wms10 . '>'.$query_result['wms10'].'</' . $remap_wms10 . '>'.PHP_EOL;
												echo '<' . $remap_kml_timestamp . '>'.$query_result['kml_timestamp'].'</' . $remap_kml_timestamp . '>'.PHP_EOL;
												echo '<' . $remap_address . '><![CDATA['.$address.']]></' . $remap_address . '>'.PHP_EOL;
											echo '</data>'.PHP_EOL;
											echo '</mapsmarker>';
										} //info: end format marker / view
									} else if ($id == null) {
										if ($format == 'json') {
											header('Content-type: application/json; charset=utf-8');
											if ($callback != NULL) { echo $callback . '('; }
											echo '{'.PHP_EOL;
											echo '"success":false,'.PHP_EOL;
											echo '"message":"' . esc_attr__('API parameter id has to be set','lmm') . '",'.PHP_EOL;
											echo '"data": { }'.PHP_EOL;
											echo '}';
											if ($callback != NULL) { echo ');'; }
										} else if ($format == 'xml') {
											header('Content-type: application/xml; charset=utf-8');
											echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
											echo '<mapsmarker>'.PHP_EOL;
											echo '<success>false</success>'.PHP_EOL;
											echo '<message>' . esc_attr__('API parameter id has to be set','lmm') . '</message>'.PHP_EOL;
											echo '<data></data>'.PHP_EOL;
											echo '</mapsmarker>';
										}									
									} else {
										if ($format == 'json') {
											header('Content-type: application/json; charset=utf-8');
											if ($callback != NULL) { echo $callback . '('; }
											echo '{'.PHP_EOL;
											echo '"success":false,'.PHP_EOL;
											echo '"message":"' . sprintf(esc_attr__('A marker with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
											echo '"data": { }'.PHP_EOL;
											echo '}';
											if ($callback != NULL) { echo ');'; }
										} else if ($format == 'xml') {
											header('Content-type: application/xml; charset=utf-8');
											echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
											echo '<mapsmarker>'.PHP_EOL;
											echo '<success>false</success>'.PHP_EOL;
											echo '<message>' . sprintf(esc_attr__('A marker with the ID %1s does not exist','lmm'), $id) . '</message>'.PHP_EOL;
											echo '<data></data>'.PHP_EOL;
											echo '</mapsmarker>';
										}									
									} //info: end check if query_result markers >=1 / view
								} else if ($type == 'layer') {
									$query_result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name_layers WHERE id = %d", $id), ARRAY_A);
									if (count($query_result) >= 1) {
										if ($format == 'json') {
											$address = stripslashes(str_replace('"', '\'', $query_result['address']));
											header('Cache-Control: no-cache, must-revalidate');
											header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
											header('Content-type: application/json; charset=utf-8');
											if ($callback != NULL) { echo $callback . '('; }
											echo '{'.PHP_EOL;
											echo '"success":true,'.PHP_EOL;
											echo '"message":"' . esc_attr__('API call was successful','lmm') . '",'.PHP_EOL;
											echo '"data": {'.PHP_EOL;
												echo '"' . $remap_id . '":"'.$query_result['id'].'",'.PHP_EOL;
												echo '"' . $remap_name . '":"' . stripslashes($query_result['name']) . '",'.PHP_EOL;
												echo '"' . $remap_basemap . '":"'.$query_result['basemap'].'",'.PHP_EOL;
												echo '"' . $remap_layerzoom . '":"'.$query_result['layerzoom'].'",'.PHP_EOL;
												echo '"' . $remap_mapwidth . '":"' . $query_result['mapwidth'] . '",'.PHP_EOL;
												echo '"' . $remap_mapwidthunit . '":"' . $query_result['mapwidthunit'] . '",'.PHP_EOL;
												echo '"' . $remap_mapheight . '":"' . $query_result['mapheight'] . '",'.PHP_EOL;
												echo '"' . $remap_panel . '":"' . $query_result['panel'] . '",'.PHP_EOL;
												echo '"' . $remap_layerviewlat . '":"' . $query_result['layerviewlat'] . '",'.PHP_EOL;
												echo '"' . $remap_layerviewlon . '":"' . $query_result['layerviewlon'] . '",'.PHP_EOL;
												echo '"' . $remap_createdby . '":"' . stripslashes($query_result['createdby']) . '",'.PHP_EOL;
												echo '"' . $remap_createdon . '":"' . $query_result['createdon'] . '",'.PHP_EOL;
												echo '"' . $remap_updatedby . '":"' . stripslashes($query_result['updatedby']) . '",'.PHP_EOL;
												echo '"' . $remap_updatedon . '":"' . stripslashes($query_result['updatedon']) . '",'.PHP_EOL;
												echo '"' . $remap_controlbox . '":"'.$query_result['controlbox'].'",'.PHP_EOL;
												echo '"' . $remap_overlays_custom . '":"'.$query_result['overlays_custom'].'",'.PHP_EOL;
												echo '"' . $remap_overlays_custom2 . '":"'.$query_result['overlays_custom2'].'",'.PHP_EOL;
												echo '"' . $remap_overlays_custom3 . '":"'.$query_result['overlays_custom3'].'",'.PHP_EOL;
												echo '"' . $remap_overlays_custom4 . '":"'.$query_result['overlays_custom4'].'",'.PHP_EOL;
												echo '"' . $remap_wms . '":"'.$query_result['wms'].'",'.PHP_EOL;
												echo '"' . $remap_wms2 . '":"'.$query_result['wms2'].'",'.PHP_EOL;
												echo '"' . $remap_wms3 . '":"'.$query_result['wms3'].'",'.PHP_EOL;
												echo '"' . $remap_wms4 . '":"'.$query_result['wms4'].'",'.PHP_EOL;
												echo '"' . $remap_wms5 . '":"'.$query_result['wms5'].'",'.PHP_EOL;
												echo '"' . $remap_wms6 . '":"'.$query_result['wms6'].'",'.PHP_EOL;
												echo '"' . $remap_wms7 . '":"'.$query_result['wms7'].'",'.PHP_EOL;
												echo '"' . $remap_wms8 . '":"'.$query_result['wms8'].'",'.PHP_EOL;
												echo '"' . $remap_wms9 . '":"'.$query_result['wms9'].'",'.PHP_EOL;
												echo '"' . $remap_wms10 . '":"'.$query_result['wms10'].'",'.PHP_EOL;
												echo '"' . $remap_listmarkers . '":"'.$query_result['listmarkers'].'",'.PHP_EOL;
												echo '"' . $remap_multi_layer_map . '":"'.$query_result['multi_layer_map'].'",'.PHP_EOL;
												echo '"' . $remap_multi_layer_map_list . '":"'.$query_result['multi_layer_map_list'].'",'.PHP_EOL;
												echo '"' . $remap_address . '":"'.$address.'"'.PHP_EOL;
												echo '}';
											echo '}';
											if ($callback != NULL) { echo ');'; }
										} else if ($format == 'xml') {
											header('Cache-Control: no-cache, must-revalidate');
											header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
											header('Content-type: application/xml; charset=utf-8');
											echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
											echo '<mapsmarker>'.PHP_EOL;
											echo '<success>true</success>'.PHP_EOL;
											echo '<message>' . esc_attr__('API call was successful','lmm') . '</message>'.PHP_EOL;
											echo '<data>'.PHP_EOL;
												echo '<' . $remap_id . '>'.$query_result['id'].'</' . $remap_id . '>'.PHP_EOL;
												echo '<' . $remap_name . '><![CDATA[' . stripslashes($query_result['name']) . ']]></' . $remap_name . '>'.PHP_EOL;
												echo '<' . $remap_basemap . '>'.$query_result['basemap'].'</' . $remap_basemap . '>'.PHP_EOL;
												echo '<' . $remap_layerzoom . '>' . $query_result['layerzoom'] . '</' . $remap_layerzoom . '>'.PHP_EOL;
												echo '<' . $remap_mapwidth . '>' . $query_result['mapwidth'] . '</' . $remap_mapwidth . '>'.PHP_EOL;
												echo '<' . $remap_mapwidthunit . '>' . $query_result['mapwidthunit'] . '</' . $remap_mapwidthunit . '>'.PHP_EOL;
												echo '<' . $remap_mapheight . '>' . $query_result['mapheight'] . '</' . $remap_mapheight . '>'.PHP_EOL;
												echo '<' . $remap_panel . '>' . $query_result['panel'] . '</' . $remap_panel . '>'.PHP_EOL;
												echo '<' . $remap_layerviewlat . '>'.$query_result['layerviewlat'].'</' . $remap_layerviewlat . '>'.PHP_EOL;
												echo '<' . $remap_layerviewlon . '>'.$query_result['layerviewlon'].'</' . $remap_layerviewlon . '>'.PHP_EOL;
												echo '<' . $remap_createdby . '><![CDATA[' . stripslashes($query_result['createdby']) . ']]></' . $remap_createdby . '>'.PHP_EOL;
												echo '<' . $remap_createdon . '>' . $query_result['createdon'] . '</' . $remap_createdon . '>'.PHP_EOL;
												echo '<' . $remap_updatedby . '><![CDATA[' . stripslashes($query_result['updatedby']) . ']]></' . $remap_updatedby . '>'.PHP_EOL;
												echo '<' . $remap_updatedon . '>' . stripslashes($query_result['updatedon']) . '</' . $remap_updatedon . '>'.PHP_EOL;
												echo '<' . $remap_controlbox . '>'.$query_result['controlbox'].'</' . $remap_controlbox . '>'.PHP_EOL;
												echo '<' . $remap_overlays_custom . '>'.$query_result['overlays_custom'].'</' . $remap_overlays_custom . '>'.PHP_EOL;
												echo '<' . $remap_overlays_custom2 . '>'.$query_result['overlays_custom2'].'</' . $remap_overlays_custom2 . '>'.PHP_EOL;
												echo '<' . $remap_overlays_custom3 . '>'.$query_result['overlays_custom3'].'</' . $remap_overlays_custom3 . '>'.PHP_EOL;
												echo '<' . $remap_overlays_custom4 . '>'.$query_result['overlays_custom4'].'</' . $remap_overlays_custom4 . '>'.PHP_EOL;
												echo '<' . $remap_wms . '>'.$query_result['wms'].'</' . $remap_wms . '>'.PHP_EOL;
												echo '<' . $remap_wms2 . '>'.$query_result['wms2'].'</' . $remap_wms2 . '>'.PHP_EOL;
												echo '<' . $remap_wms3 . '>'.$query_result['wms3'].'</' . $remap_wms3 . '>'.PHP_EOL;
												echo '<' . $remap_wms4 . '>'.$query_result['wms4'].'</' . $remap_wms4 . '>'.PHP_EOL;
												echo '<' . $remap_wms5 . '>'.$query_result['wms5'].'</' . $remap_wms5 . '>'.PHP_EOL;
												echo '<' . $remap_wms6 . '>'.$query_result['wms6'].'</' . $remap_wms6 . '>'.PHP_EOL;
												echo '<' . $remap_wms7 . '>'.$query_result['wms7'].'</' . $remap_wms7 . '>'.PHP_EOL;
												echo '<' . $remap_wms8 . '>'.$query_result['wms8'].'</' . $remap_wms8 . '>'.PHP_EOL;
												echo '<' . $remap_wms9 . '>'.$query_result['wms9'].'</' . $remap_wms9 . '>'.PHP_EOL;
												echo '<' . $remap_wms10 . '>'.$query_result['wms10'].'</' . $remap_wms10 . '>'.PHP_EOL;
												echo '<' . $remap_listmarkers . '>'.$query_result['listmarkers'].'</' . $remap_listmarkers . '>'.PHP_EOL;
												echo '<' . $remap_multi_layer_map . '><![CDATA['.$query_result['multi_layer_map'].']]></' . $remap_multi_layer_map . '>'.PHP_EOL;
												echo '<' . $remap_multi_layer_map_list . '><![CDATA[' . $query_result['multi_layer_map_list'] . ']]></' . $remap_multi_layer_map_list . '>'.PHP_EOL;
												echo '<' . $remap_address . '>' . $query_result['address'] . '</' . $remap_address . '>'.PHP_EOL;
											echo '</data>'.PHP_EOL;
											echo '</mapsmarker>';										
										} //info: end format layer / view
									} else {
										if ($format == 'json') {
											header('Content-type: application/json; charset=utf-8');
											if ($callback != NULL) { echo $callback . '('; }
											echo '{'.PHP_EOL;
											echo '"success":false,'.PHP_EOL;
											echo '"message":"' . sprintf(esc_attr__('A layer with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
											echo '"data": { }'.PHP_EOL;
											echo '}';
											if ($callback != NULL) { echo ');'; }
										} else if ($format == 'xml') {
											header('Content-type: application/xml; charset=utf-8');
											echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
											echo '<mapsmarker>'.PHP_EOL;
											echo '<success>false</success>'.PHP_EOL;
											echo '<message>' . sprintf(esc_attr__('A layer with the ID %1s does not exist','lmm'), $id) . '</message>'.PHP_EOL;
											echo '<data></data>'.PHP_EOL;
											echo '</mapsmarker>';
										}												
									} //info: end check if query_result layers >=1 / view
								} else if ($type == '') {
									if ($format == 'json') {
										header('Content-type: application/json; charset=utf-8');
										if ($callback != NULL) { echo $callback . '('; }
										echo '{'.PHP_EOL;
										echo '"success":false,'.PHP_EOL;
										echo '"message":"' . esc_attr__('API parameter type has to be set','lmm') . '",'.PHP_EOL;
										echo '"data": { }'.PHP_EOL;
										echo '}';
										if ($callback != NULL) { echo ');'; }
									} else if ($format == 'xml') {
										header('Content-type: application/xml; charset=utf-8');
										echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
										echo '<mapsmarker>'.PHP_EOL;
										echo '<success>false</success>'.PHP_EOL;
										echo '<message>' . esc_attr__('API parameter type has to be set','lmm') . '</message>'.PHP_EOL;
										echo '<data></data>'.PHP_EOL;
										echo '</mapsmarker>';
									}											
								} else {
									if ($format == 'json') {
										header('Content-type: application/json; charset=utf-8');
										if ($callback != NULL) { echo $callback . '('; }
										echo '{'.PHP_EOL;
										echo '"success":false,'.PHP_EOL;
										echo '"message":"' . esc_attr__('API parameter type is invalid','lmm') . '",'.PHP_EOL;
										echo '"data": { }'.PHP_EOL;
										echo '}';
										if ($callback != NULL) { echo ');'; }
									} else if ($format == 'xml') {
										header('Content-type: application/xml; charset=utf-8');
										echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
										echo '<mapsmarker>'.PHP_EOL;
										echo '<success>false</success>'.PHP_EOL;
										echo '<message>' . esc_attr__('API parameter type is invalid','lmm') . '</message>'.PHP_EOL;
										echo '<data></data>'.PHP_EOL;
										echo '</mapsmarker>';
									}	
								} //info: end type check / view
							} else {
								if ($format == 'json') {
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":false,'.PHP_EOL;
									echo '"message":"' . esc_attr__('API action is not allowed','lmm') . '",'.PHP_EOL;
									echo '"data": { }'.PHP_EOL;
									echo '}';
									if ($callback != NULL) { echo ');'; }
								} else if ($format == 'xml') {
									header('Content-type: application/xml; charset=utf-8');
									echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
									echo '<mapsmarker>'.PHP_EOL;
									echo '<success>false</success>'.PHP_EOL;
									echo '<message>' . esc_attr__('API action is not allowed','lmm') . '</message>'.PHP_EOL;
									echo '<data></data>'.PHP_EOL;
									echo '</mapsmarker>';
								}	
							} //info: end permission check / view
						/******************************
						* action add                  *
						******************************/
						} else if ($action == 'add') {
							if ( $lmm_options['api_permissions_add'] == TRUE ) {
								if ($type == 'marker') {
	
								} else if ($type == 'layer') {
	
								} else if ($type == '') {
									if ($format == 'json') {
										header('Content-type: application/json; charset=utf-8');
										if ($callback != NULL) { echo $callback . '('; }
										echo '{'.PHP_EOL;
										echo '"success":false,'.PHP_EOL;
										echo '"message":"' . esc_attr__('API parameter type has to be set','lmm') . '",'.PHP_EOL;
										echo '"data": { }'.PHP_EOL;
										echo '}';
										if ($callback != NULL) { echo ');'; }
									} else if ($format == 'xml') {
										header('Content-type: application/xml; charset=utf-8');
										echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
										echo '<mapsmarker>'.PHP_EOL;
										echo '<success>false</success>'.PHP_EOL;
										echo '<message>' . esc_attr__('API parameter type has to be set','lmm') . '</message>'.PHP_EOL;
										echo '<data></data>'.PHP_EOL;
										echo '</mapsmarker>';
									}										
								} else {
									if ($format == 'json') {
										header('Content-type: application/json; charset=utf-8');
										if ($callback != NULL) { echo $callback . '('; }
										echo '{'.PHP_EOL;
										echo '"success":false,'.PHP_EOL;
										echo '"message":"' . esc_attr__('API parameter type is invalid','lmm') . '",'.PHP_EOL;
										echo '"data": { }'.PHP_EOL;
										echo '}';
										if ($callback != NULL) { echo ');'; }
									} else if ($format == 'xml') {
										header('Content-type: application/xml; charset=utf-8');
										echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
										echo '<mapsmarker>'.PHP_EOL;
										echo '<success>false</success>'.PHP_EOL;
										echo '<message>' . esc_attr__('API parameter type is invalid','lmm') . '</message>'.PHP_EOL;
										echo '<data></data>'.PHP_EOL;
										echo '</mapsmarker>';
									}										
								} //info: end type check / add
							} else {
								if ($format == 'json') {
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":false,'.PHP_EOL;
									echo '"message":"' . esc_attr__('API action is not allowed','lmm') . '",'.PHP_EOL;
									echo '"data": { }'.PHP_EOL;
									echo '}';
								if ($callback != NULL) { echo ');'; }
								} else if ($format == 'xml') {
									header('Content-type: application/xml; charset=utf-8');
									echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
									echo '<mapsmarker>'.PHP_EOL;
									echo '<success>false</success>'.PHP_EOL;
									echo '<message>' . esc_attr__('API action is not allowed','lmm') . '</message>'.PHP_EOL;
									echo '<data></data>'.PHP_EOL;
									echo '</mapsmarker>';
								}									
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
										if ($format == 'json') {
											header('Content-type: application/json; charset=utf-8');
											if ($callback != NULL) { echo $callback . '('; }
											echo '{'.PHP_EOL;
											echo '"success":false,'.PHP_EOL;
											echo '"message":"' . sprintf(esc_attr__('A marker with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
											echo '"data": { }'.PHP_EOL;
											echo '}';
											if ($callback != NULL) { echo ');'; }
										} else if ($format == 'xml') {
											header('Content-type: application/xml; charset=utf-8');
											echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
											echo '<mapsmarker>'.PHP_EOL;
											echo '<success>false</success>'.PHP_EOL;
											echo '<message>' . sprintf(esc_attr__('A marker with the ID %1s does not exist','lmm'), $id) . '</message>'.PHP_EOL;
											echo '<data></data>'.PHP_EOL;
											echo '</mapsmarker>';
										}
									} //info: end check if query_result markers >=1 / update
								} else if ($type == 'layer') {
									$query_result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name_layers WHERE id = %d", $id), ARRAY_A);
									if (count($query_result) >= 1) {
	
									} else {
										if ($format == 'json') {
											header('Content-type: application/json; charset=utf-8');
											if ($callback != NULL) { echo $callback . '('; }
											echo '{'.PHP_EOL;
											echo '"success":false,'.PHP_EOL;
											echo '"message":"' . sprintf(esc_attr__('A layer with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
											echo '"data": { }'.PHP_EOL;
											echo '}';
											if ($callback != NULL) { echo ');'; }
										} else if ($format == 'xml') {
											header('Content-type: application/xml; charset=utf-8');
											echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
											echo '<mapsmarker>'.PHP_EOL;
											echo '<success>false</success>'.PHP_EOL;
											echo '<message>' . sprintf(esc_attr__('A layer with the ID %1s does not exist','lmm'), $id) . '</message>'.PHP_EOL;
											echo '<data></data>'.PHP_EOL;
											echo '</mapsmarker>';
										}
									} //info: end check if query_result layers >=1 / update
	
								} else if ($type == '') {
									if ($format == 'json') {
										header('Content-type: application/json; charset=utf-8');
										if ($callback != NULL) { echo $callback . '('; }
										echo '{'.PHP_EOL;
										echo '"success":false,'.PHP_EOL;
										echo '"message":"' . esc_attr__('API parameter type has to be set','lmm') . '",'.PHP_EOL;
										echo '"data": { }'.PHP_EOL;
										echo '}';
										if ($callback != NULL) { echo ');'; }
									} else if ($format == 'xml') {
										header('Content-type: application/xml; charset=utf-8');
										echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
										echo '<mapsmarker>'.PHP_EOL;
										echo '<success>false</success>'.PHP_EOL;
										echo '<message>' . esc_attr__('API parameter type has to be set','lmm') . '</message>'.PHP_EOL;
										echo '<data></data>'.PHP_EOL;
										echo '</mapsmarker>';
									}
								} else {
									if ($format == 'json') {
										header('Content-type: application/json; charset=utf-8');
										if ($callback != NULL) { echo $callback . '('; }
										echo '{'.PHP_EOL;
										echo '"success":false,'.PHP_EOL;
										echo '"message":"' . esc_attr__('API parameter type is invalid','lmm') . '",'.PHP_EOL;
										echo '"data": { }'.PHP_EOL;
										echo '}';
										if ($callback != NULL) { echo ');'; }
									} else if ($format == 'xml') {
										header('Content-type: application/xml; charset=utf-8');
										echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
										echo '<mapsmarker>'.PHP_EOL;
										echo '<success>false</success>'.PHP_EOL;
										echo '<message>' . esc_attr__('API parameter type is invalid','lmm') . '</message>'.PHP_EOL;
										echo '<data></data>'.PHP_EOL;
										echo '</mapsmarker>';
									}
								} //info: end type check / update
							} else {
								if ($format == 'json') {
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":false,'.PHP_EOL;
									echo '"message":"' . esc_attr__('API action is not allowed','lmm') . '",'.PHP_EOL;
									echo '}';
									if ($callback != NULL) { echo ');'; }
								} else if ($format == 'xml') {
									header('Content-type: application/xml; charset=utf-8');
									echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
									echo '<mapsmarker>'.PHP_EOL;
									echo '<success>false</success>'.PHP_EOL;
									echo '<message>' . esc_attr__('API action is not allowed','lmm') . '</message>'.PHP_EOL;
									echo '<data></data>'.PHP_EOL;
									echo '</mapsmarker>';
								}
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
										if ($format == 'json') {
											header('Content-type: application/json; charset=utf-8');
											if ($callback != NULL) { echo $callback . '('; }
											echo '{'.PHP_EOL;
											echo '"success":false,'.PHP_EOL;
											echo '"message":"' . sprintf(esc_attr__('A marker with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
											echo '"data": { }'.PHP_EOL;
											echo '}';
											if ($callback != NULL) { echo ');'; }
										} else if ($format == 'xml') {
											header('Content-type: application/xml; charset=utf-8');
											echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
											echo '<mapsmarker>'.PHP_EOL;
											echo '<success>false</success>'.PHP_EOL;
											echo '<message>' . sprintf(esc_attr__('A marker with the ID %1s does not exist','lmm'), $id) . '</message>'.PHP_EOL;
											echo '<data></data>'.PHP_EOL;
											echo '</mapsmarker>';
										}
									} //info: end check if query_result markers >=1 / view
								} else if ($type == 'layer') {
									$query_result = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name_layers WHERE id = %d", $id), ARRAY_A);
									if (count($query_result) >= 1) {
	
									} else {
										if ($format == 'json') {
											header('Content-type: application/json; charset=utf-8');
											if ($callback != NULL) { echo $callback . '('; }
											echo '{'.PHP_EOL;
											echo '"success":false,'.PHP_EOL;
											echo '"message":"' . sprintf(esc_attr__('A layer with the ID %1s does not exist','lmm'), $id) . '",'.PHP_EOL;
											echo '"data": { }'.PHP_EOL;
											echo '}';
											if ($callback != NULL) { echo ');'; }
										} else if ($format == 'xml') {
											header('Content-type: application/xml; charset=utf-8');
											echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
											echo '<mapsmarker>'.PHP_EOL;
											echo '<success>false</success>'.PHP_EOL;
											echo '<message>' . sprintf(esc_attr__('A layer with the ID %1s does not exist','lmm'), $id) . '</message>'.PHP_EOL;
											echo '<data></data>'.PHP_EOL;
											echo '</mapsmarker>';
										}
									} //info: end check if query_result markers >=1 / view
								} else if ($type == '') {
									if ($format == 'json') {
										header('Content-type: application/json; charset=utf-8');
										if ($callback != NULL) { echo $callback . '('; }
										echo '{'.PHP_EOL;
										echo '"success":false,'.PHP_EOL;
										echo '"message":"' . esc_attr__('API parameter type has to be set','lmm') . '",'.PHP_EOL;
										echo '"data": { }'.PHP_EOL;
										echo '}';
										if ($callback != NULL) { echo ');'; }
									} else if ($format == 'xml') {
										header('Content-type: application/xml; charset=utf-8');
										echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
										echo '<mapsmarker>'.PHP_EOL;
										echo '<success>false</success>'.PHP_EOL;
										echo '<message>' . esc_attr__('API parameter type has to be set','lmm') . '</message>'.PHP_EOL;
										echo '<data></data>'.PHP_EOL;
										echo '</mapsmarker>';
									}
								} else {
									if ($format == 'json') {
										header('Content-type: application/json; charset=utf-8');
										if ($callback != NULL) { echo $callback . '('; }
										echo '{'.PHP_EOL;
										echo '"success":false,'.PHP_EOL;
										echo '"message":"' . esc_attr__('API parameter type is invalid','lmm') . '",'.PHP_EOL;
										echo '"data": { }'.PHP_EOL;
										echo '}';
										if ($callback != NULL) { echo ');'; }
									} else if ($format == 'xml') {
										header('Content-type: application/xml; charset=utf-8');
										echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
										echo '<mapsmarker>'.PHP_EOL;
										echo '<success>false</success>'.PHP_EOL;
										echo '<message>' . esc_attr__('API parameter type is invalid','lmm') . '</message>'.PHP_EOL;
										echo '<data></data>'.PHP_EOL;
										echo '</mapsmarker>';
									}
								} //info: end type check / delete
							} else {
								if ($format == 'json') {
									header('Content-type: application/json; charset=utf-8');
									if ($callback != NULL) { echo $callback . '('; }
									echo '{'.PHP_EOL;
									echo '"success":false,'.PHP_EOL;
									echo '"message":"' . esc_attr__('API action is not allowed','lmm') . '",'.PHP_EOL;
									echo '"data": { }'.PHP_EOL;
									echo '}';
									if ($callback != NULL) { echo ');'; }
								} else if ($format == 'xml') {
									header('Content-type: application/xml; charset=utf-8');
									echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
									echo '<mapsmarker>'.PHP_EOL;
									echo '<success>false</success>'.PHP_EOL;
									echo '<message>' . esc_attr__('API action is not allowed','lmm') . '</message>'.PHP_EOL;
									echo '<data></data>'.PHP_EOL;
									echo '</mapsmarker>';
								}
							} //info: end permission check / delete
						} else if ($action == '') {
							if ($format == 'json') {
								header('Content-type: application/json; charset=utf-8');
								if ($callback != NULL) { echo $callback . '('; }
								echo '{'.PHP_EOL;
								echo '"success":false,'.PHP_EOL;
								echo '"message":"' . esc_attr__('API parameter action has to be set','lmm') . '",'.PHP_EOL;
								echo '"data": { }'.PHP_EOL;
								echo '}';
								if ($callback != NULL) { echo ');'; }
							} else if ($format == 'xml') {
								header('Content-type: application/xml; charset=utf-8');
								echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
								echo '<mapsmarker>'.PHP_EOL;
								echo '<success>false</success>'.PHP_EOL;
								echo '<message>' . esc_attr__('API parameter action has to be set','lmm') . '</message>'.PHP_EOL;
								echo '<data></data>'.PHP_EOL;
								echo '</mapsmarker>';
							}
						} else {
							if ($format == 'json') {
								header('Content-type: application/json; charset=utf-8');
								if ($callback != NULL) { echo $callback . '('; }
								echo '{'.PHP_EOL;
								echo '"success":false,'.PHP_EOL;
								echo '"message":"' . esc_attr__('API parameter action is invalid','lmm') . '",'.PHP_EOL;
								echo '"data": { }'.PHP_EOL;
								echo '}';
								if ($callback != NULL) { echo ');'; }
							} else if ($format == 'xml') {
								header('Content-type: application/xml; charset=utf-8');
								echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
								echo '<mapsmarker>'.PHP_EOL;
								echo '<success>false</success>'.PHP_EOL;
								echo '<message>' . esc_attr__('API parameter action is invalid','lmm') . '</message>'.PHP_EOL;
								echo '<data></data>'.PHP_EOL;
								echo '</mapsmarker>';
							}
						} //info: end check action / general
					} else {
						if ($format == 'json') {
							header('Content-type: application/json; charset=utf-8');
							if ($callback != NULL) { echo $callback . '('; }
							echo '{'.PHP_EOL;
							echo '"success":false,'.PHP_EOL;
							echo '"message":"' . sprintf(esc_attr__('API access via IP %1s is not allowed','lmm'), $_SERVER['REMOTE_ADDR']) . '",'.PHP_EOL;
							echo '"data": { }'.PHP_EOL;
							echo '}';
							if ($callback != NULL) { echo ');'; }
						} else if ($format == 'xml') {
							header('Content-type: application/xml; charset=utf-8');
							echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
							echo '<mapsmarker>'.PHP_EOL;
							echo '<success>false</success>'.PHP_EOL;
							echo '<message>' . sprintf(esc_attr__('API access via IP %1s is not allowed','lmm'), $_SERVER['REMOTE_ADDR']) . '</message>'.PHP_EOL;
							echo '<data></data>'.PHP_EOL;
							echo '</mapsmarker>';
						}
					} //info: end ip access check / general
				} else {
					if ($format == 'json') {
						header('Content-type: application/json; charset=utf-8');
						if ($callback != NULL) { echo $callback . '('; }
						echo '{'.PHP_EOL;
						echo '"success":false,'.PHP_EOL;
						echo '"message":"' . sprintf(esc_attr__('Referer (%1s) is not allowed','lmm'), $referer) . '",'.PHP_EOL;
						echo '"data": { }'.PHP_EOL;
						echo '}';
						if ($callback != NULL) { echo ');'; }
					} else if ($format == 'xml') {
						header('Content-type: application/xml; charset=utf-8');
						echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
						echo '<mapsmarker>'.PHP_EOL;
						echo '<success>false</success>'.PHP_EOL;
						echo '<message>' . sprintf(esc_attr__('Referer (%1s) is invalid','lmm'), $referer) . '</message>'.PHP_EOL;
						echo '<data></data>'.PHP_EOL;
						echo '</mapsmarker>';
					}									
				} //info: end referer check / general
			} else {
				if ($format == 'json') {
					header('Content-type: application/json; charset=utf-8');
					if ($callback != NULL) { echo $callback . '('; }
					echo '{'.PHP_EOL;
					echo '"success":false,'.PHP_EOL;
					echo '"message":"' . esc_attr__('API key is invalid','lmm') . '",'.PHP_EOL;
					echo '"data": { }'.PHP_EOL;
					echo '}';
					if ($callback != NULL) { echo ');'; }
				} else if ($format == 'xml') {
					header('Content-type: application/xml; charset=utf-8');
					echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
					echo '<mapsmarker>'.PHP_EOL;
					echo '<success>false</success>'.PHP_EOL;
					echo '<message>' . esc_attr__('API key is invalid','lmm') . '</message>'.PHP_EOL;
					echo '<data></data>'.PHP_EOL;
					echo '</mapsmarker>';
				}
			} //info: end key validity check / general
		} else { //info: change if v2 is released
			if ($format == 'json') {
				header('Content-type: application/json; charset=utf-8');
				if ($callback != NULL) { echo $callback . '('; }
				echo '{'.PHP_EOL;
				echo '"success":false,'.PHP_EOL;
				echo '"message":"' . esc_attr__('API version is invalid','lmm') . '",'.PHP_EOL;
				echo '"data": { }'.PHP_EOL;
				echo '}';
				if ($callback != NULL) { echo ');'; }
			} else if ($format == 'xml') {
				header('Content-type: application/xml; charset=utf-8');
				echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
				echo '<mapsmarker>'.PHP_EOL;
				echo '<success>false</success>'.PHP_EOL;
				echo '<message>' . esc_attr__('API version is invalid','lmm') . '</message>'.PHP_EOL;
				echo '<data></data>'.PHP_EOL;
				echo '</mapsmarker>';
			}				
		} //info: end API version check
	} else {
		if ($format == 'json') {
			header('Content-type: application/json; charset=utf-8');
			if ($callback != NULL) { echo $callback . '('; }
			echo '{'.PHP_EOL;
			echo '"success":false,'.PHP_EOL;
			echo '"message":"' . esc_attr__('API is disabled','lmm') . '",'.PHP_EOL;
			echo '"data": { }'.PHP_EOL;
			echo '}';
			if ($callback != NULL) { echo ');'; }
		} else if ($format == 'xml') {
			header('Content-type: application/xml; charset=utf-8');
			echo '<?xml version="1.0" encoding="utf8"?>'.PHP_EOL;
			echo '<mapsmarker>'.PHP_EOL;
			echo '<success>false</success>'.PHP_EOL;
			echo '<message>' . esc_attr__('API is disabled','lmm') . '</message>'.PHP_EOL;
			echo '<data></data>'.PHP_EOL;
			echo '</mapsmarker>';
		}	
	} //info: end api_status enabled
} //info: end plugin active check
?>