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
	echo 'The WordPress plugin <a href="http://www.mapsmarker.com" target="_blank">Leaflet Maps Marker</a> is inactive on this site and therefore this API link is not working.<br/><br/>Please contact the site owner (' . hide_email(get_bloginfo('admin_email')) . ') who can activate this plugin again.';
} else {
	global $wpdb;
	$table_name_markers = $wpdb->prefix.'leafletmapsmarker_markers';
	$table_name_layers = $wpdb->prefix.'leafletmapsmarker_layers';
	$lmm_options = get_option( 'leafletmapsmarker_options' );
	$callback = (isset($_GET['callback'])) ? $_GET['callback'] : '';
	$version = (isset($_GET['version'])) ? $_GET['version'] : '';

	if ($lmm_options['api_status'] == 'enabled') {
		if ( ($version == '1') || ($version == '') ) { //info: change OR condition if v2 is available
			$api_key = isset($_GET['key']) ? $_GET['key'] : '';
			if ($api_key == $lmm_options['api_key']) {
				$action = isset($_GET['action']) ? $_GET['action'] : '';
				$id = isset($_GET['id']) ? intval($_GET['id']) : '';
				$type = isset($_GET['type']) ? $_GET['type'] : '';

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
									echo '"id":"'.$query_result['id'].'",'.PHP_EOL;
									echo '"markername":"' . stripslashes($query_result['markername']) . '",'.PHP_EOL;
									echo '"basemap":"'.$query_result['basemap'].'",'.PHP_EOL;
									echo '"layer":"'.$query_result['layer'].'",'.PHP_EOL;
									echo '"lat":"'.$query_result['lat'].'",'.PHP_EOL;
									echo '"lon":"'.$query_result['lon'].'",'.PHP_EOL;
									echo '"icon":"'.$query_result['icon'].'",'.PHP_EOL;
									$mpopuptext = stripslashes(str_replace('"', '\'', preg_replace('/(\015\012)|(\015)|(\012)/','<br/>',$query_result['popuptext'])));
									echo '"popuptext":"' . $mpopuptext . '",'.PHP_EOL;
									echo '"zoom":"' . $query_result['zoom'] . '",'.PHP_EOL;
									echo '"openpopup":"' . $query_result['openpopup'] . '",'.PHP_EOL;
									echo '"mapwidth":"' . $query_result['mapwidth'] . '",'.PHP_EOL;
									echo '"mapwidthunit":"' . $query_result['mapwidthunit'] . '",'.PHP_EOL;
									echo '"mapheight":"' . $query_result['mapheight'] . '",'.PHP_EOL;
									echo '"panel":"' . $query_result['panel'] . '",'.PHP_EOL;
									echo '"createdby":"' . stripslashes($query_result['createdby']) . '",'.PHP_EOL;
									echo '"createdon":"' . $query_result['createdon'] . '",'.PHP_EOL;
									echo '"updatedby":"' . stripslashes($query_result['updatedby']) . '",'.PHP_EOL;
									echo '"updatedon":"' . stripslashes($query_result['updatedon']) . '",'.PHP_EOL;
									echo '"controlbox":"'.$query_result['controlbox'].'",'.PHP_EOL;
									echo '"overlays_custom":"'.$query_result['overlays_custom'].'",'.PHP_EOL;
									echo '"overlays_custom2":"'.$query_result['overlays_custom2'].'",'.PHP_EOL;
									echo '"overlays_custom3":"'.$query_result['overlays_custom3'].'",'.PHP_EOL;
									echo '"overlays_custom4":"'.$query_result['overlays_custom4'].'",'.PHP_EOL;
									echo '"wms":"'.$query_result['wms'].'",'.PHP_EOL;
									echo '"wms2":"'.$query_result['wms2'].'",'.PHP_EOL;
									echo '"wms3":"'.$query_result['wms3'].'",'.PHP_EOL;
									echo '"wms4":"'.$query_result['wms4'].'",'.PHP_EOL;
									echo '"wms5":"'.$query_result['wms5'].'",'.PHP_EOL;
									echo '"wms6":"'.$query_result['wms6'].'",'.PHP_EOL;
									echo '"wms7":"'.$query_result['wms7'].'",'.PHP_EOL;
									echo '"wms8":"'.$query_result['wms8'].'",'.PHP_EOL;
									echo '"wms9":"'.$query_result['wms9'].'",'.PHP_EOL;
									echo '"wms10":"'.$query_result['wms10'].'",'.PHP_EOL;
									echo '"kml_timestamp":"'.$query_result['kml_timestamp'].'",'.PHP_EOL;
									$maddress = stripslashes(str_replace('"', '\'', $query_result['address']));
									echo '"address":"'.$maddress.'"'.PHP_EOL;
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
								echo '"id":"'.$query_result['id'].'",'.PHP_EOL;
								echo '"name":"' . stripslashes($query_result['name']) . '",'.PHP_EOL;
								echo '"basemap":"'.$query_result['basemap'].'",'.PHP_EOL;
								echo '"layerzoom":"'.$query_result['layerzoom'].'",'.PHP_EOL;
								echo '"mapwidth":"'.$query_result['mapwidth'].'",'.PHP_EOL;
								echo '"mapwidthunit":"'.$query_result['mapwidthunit'].'",'.PHP_EOL;
								echo '"mapheight":"'.$query_result['mapheight'].'",'.PHP_EOL;
								echo '"panel":"' . $query_result['panel'] . '",'.PHP_EOL;
								echo '"layerviewlat":"' . $query_result['layerviewlat'] . '",'.PHP_EOL;
								echo '"layerviewlon":"' . $query_result['layerviewlon'] . '",'.PHP_EOL;
								echo '"createdby":"' . $query_result['createdby'] . '",'.PHP_EOL;
								echo '"createdon":"' . stripslashes($query_result['createdon']) . '",'.PHP_EOL;
								echo '"updatedby":"' . $query_result['updatedby'] . '",'.PHP_EOL;
								echo '"updatedon":"' . stripslashes($query_result['updatedon']) . '",'.PHP_EOL;
								echo '"controlbox":"' . stripslashes($query_result['controlbox']) . '",'.PHP_EOL;
								echo '"overlays_custom":"'.$query_result['overlays_custom'].'",'.PHP_EOL;
								echo '"overlays_custom2":"' . $query_result['overlays_custom2'] . '",'.PHP_EOL;
								echo '"overlays_custom3":"' . $query_result['overlays_custom3'] . '",'.PHP_EOL;
								echo '"overlays_custom4":"' . $query_result['overlays_custom4'] . '",'.PHP_EOL;
								echo '"wms":"'.$query_result['wms'].'",'.PHP_EOL;
								echo '"wms2":"'.$query_result['wms2'].'",'.PHP_EOL;
								echo '"wms3":"'.$query_result['wms3'].'",'.PHP_EOL;
								echo '"wms4":"'.$query_result['wms4'].'",'.PHP_EOL;
								echo '"wms5":"'.$query_result['wms5'].'",'.PHP_EOL;
								echo '"wms6":"'.$query_result['wms6'].'",'.PHP_EOL;
								echo '"wms7":"'.$query_result['wms7'].'",'.PHP_EOL;
								echo '"wms8":"'.$query_result['wms8'].'",'.PHP_EOL;
								echo '"wms9":"'.$query_result['wms9'].'",'.PHP_EOL;
								echo '"wms10":"'.$query_result['wms10'].'",'.PHP_EOL;
								echo '"listmarkers":"'.$query_result['listmarkers'].'",'.PHP_EOL;
								echo '"multi_layer_map":"'.$query_result['multi_layer_map'].'",'.PHP_EOL;
								echo '"multi_layer_map_list":"'.$query_result['multi_layer_map_list'].'",'.PHP_EOL;
								echo '"address":"'.$query_result['address'].'"'.PHP_EOL;
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