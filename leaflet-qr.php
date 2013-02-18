<?php
/*
    QR code generator - Leaflet Maps Marker Plugin
*/
//info: construct path to wp-load.php
while(!is_file('wp-load.php')){
  if(is_dir('../')) chdir('../');
  else die('Error: Could not construct path to wp-load.php - please check <a href="http://mapsmarker.com/path-error">http://mapsmarker.com/path-error</a> for more details');
}
include( 'wp-load.php' );
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
	$lmm_options = get_option( 'leafletmapsmarker_options' );
	if (isset($_GET['layer'])) {
			$url = LEAFLET_PLUGIN_URL . 'leaflet-fullscreen.php?layer=' . htmlspecialchars($_GET['layer']);
	} else if (isset($_GET['marker'])) {
			$url = LEAFLET_PLUGIN_URL . 'leaflet-fullscreen.php?marker=' . htmlspecialchars($_GET['marker']);
	}
	//info: visualead settings
	if ($lmm_options['qrcode_provider'] == 'visualead') {
		$ch=curl_init(); 
		curl_setopt($ch, CURLOPT_URL,"http://api.visualead.com/v1/generate"); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
		curl_setopt($ch, CURLOPT_HTTPHEADER,array("Content-type: application/json")); 
		curl_setopt($ch, CURLOPT_POST,true); 
		$filedata = ( $lmm_options['qrcode_visualead_image_url'] == NULL ) ? urlencode(LEAFLET_PLUGIN_URL . 'inc/img/logo-qr-code.png') : urlencode(htmlspecialchars($lmm_options['qrcode_visualead_image_url']));
		$api_key = ($lmm_options['qrcode_visualead_api_key'] == NULL) ? '511e2952-b43c-4b44-884c-057e0a1f8a7b' : htmlspecialchars($lmm_options['qrcode_visualead_api_key']); 
		if ($lmm_options['qrcode_visualead_qr_cell_size'] == NULL) {
			$qr_cell_size = '';
		} else {
			$qr_cell_size = intval($lmm_options['qrcode_visualead_qr_cell_size']);
		}
		if ($lmm_options['qrcode_visualead_qr_gravity'] == NULL) {
			$qr_gravity = '';
		} else {
			$qr_gravity = htmlspecialchars($lmm_options['qrcode_visualead_qr_gravity']);
		}
		if ($lmm_options['qrcode_visualead_output_image_width'] == NULL) {
			$output_image_width = '';
		} else if ( ($lmm_options['qrcode_visualead_output_image_width'] != NULL) && (intval($lmm_options['qrcode_visualead_output_image_width']) < 124) ) {
			_e('Output size must be larger than 124px!','lmm');
		} else if ( ($lmm_options['qrcode_visualead_output_image_width'] != NULL) && (intval($lmm_options['qrcode_visualead_output_image_width']) >= 124) ) {
			$output_image_width = intval($lmm_options['qrcode_visualead_output_image_width']);
		}
		$data=array( 
			'api_key'=>$api_key, 
			'image'=>$filedata, 
			'qr_x'=>intval($lmm_options['qrcode_visualead_qr_x']),
			'qr_y'=>intval($lmm_options['qrcode_visualead_qr_y']),
			'qr_size'=>intval($lmm_options['qrcode_visualead_qr_size']),
			'qr_cell_size'=>$qr_cell_size,
			'qr_rotation'=>intval($lmm_options['qrcode_visualead_qr_rotation']),
			'qr_gravity'=>$qr_gravity,
			'output_image_width'=>$output_image_width,
			'output_type' => 1,
			'action'=>'url', 
			'content'=>array('url'=>$url) 
		); 
		$data = json_encode($data); 
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data); 
		$output = curl_exec($ch); 
		curl_close($ch); 
		$results = json_decode($output); 
		if($results->response ==1){ 
			$image_decoded= base64_decode($results->image); 
			echo '<a href="data:image/png;base64,' . $results->image . '" title="' . sprintf(esc_attr__('QR code image for link to full screen map (%s)','lmm'),$url) . '"><img src="data:image/png;base64,' . $results->image . '" alt="QR-Code"/></a>';
			echo '<br/><a href="http://www.visualead.com" target="_blank" title="' . esc_attr__('QR code powered by visualead.com','lmm') . '"><img style="margin:10px 0 0 35px;" src="' . LEAFLET_PLUGIN_URL . 'inc/img/logo-visualead.png"></a>';
		}
	//info: Google QR settings
	} else if ($lmm_options['qrcode_provider'] == 'google') {
		$google_qr_link = 'https://chart.googleapis.com/chart?chs=' . $lmm_options[ 'misc_qrcode_size' ] . 'x' . $lmm_options[ 'misc_qrcode_size' ] . '&cht=qr&chl=' . $url;
		echo '<script type="text/javascript">window.location.href = "' . $google_qr_link . '";</script>  ';
	}
} //info: end plugin active check
?>