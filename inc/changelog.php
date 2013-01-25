<!DOCTYPE html>
<head>
<meta http-equiv="Content-Type" content="text/html"; charset="utf-8" />
<title>Changelog for Leaflet Maps Marker Pro</title>
<style type="text/css">
body {
	font-family: sans-serif;
	padding:0 0 0 5px;
	margin:0px;
	font-size: 12px;
	line-height: 1.4em;
}
table {
	line-height:0.7em;
	font-size:12px;
	font-family:sans-serif;
}
.updated {
	padding:10px;
	background-color: #FFFFE0;
}
a {
	color: #21759B;
	text-decoration: none;
}
a:hover, a:active, a:focus {
	color: #D54E21;
}
hr {
	color: #E6DB55;
}
</style>
</head>
<body>
<?php 
while(!is_file('wp-load.php')){
  if(is_dir('../')) chdir('../');
  else die('Error: Could not construct path to wp-load.php - please check <a href="http://mapsmarker.com/path-error">http://mapsmarker.com/path-error</a> for more details');
}
include( 'wp-load.php' );
if (get_option('leafletmapsmarker_update_info') == 'show') {
	$lmm_version_old = get_option( 'leafletmapsmarker_version_pro_before_update' );
	$lmm_version_new = get_option( 'leafletmapsmarker_version_pro' );
/*2do: change verion numbers and date in first line on each update and add if ( ($lmm_version_old < 'x.x' ) ){ to old changelog
		echo '<p style="margin:0.5em 0 0 0;"><strong>' . sprintf(__('Changelog for version %s','lmm'), '1.1') . '</strong> - ' . __('released on','lmm') . ' xx.12.2013 (<a href="http://www.mapsmarker.com/v1.1p" target="_blank">' . __('blog post with more details about this release','lmm') . '</a>):</p>
		<table>
		<tr><td>
		<img src="' . LEAFLET_PLUGIN_URL .'inc/img/icon-changelog-new.png">
		</td><td>
		
		</td></tr>
		<tr><td>
		<img src="' . LEAFLET_PLUGIN_URL .'inc/img/icon-changelog-changed.png">
		</td><td>
		
		</td></tr>
		<img src="' . LEAFLET_PLUGIN_URL .'inc/img/icon-changelog-fixed.png">
		</td><td>
		
		</td></tr>
		<tr><td>
		<img src="' . LEAFLET_PLUGIN_URL .'inc/img/icon-changelog-translations.png">
		</td><td>
		updated German translation
		</td></tr>
		</table>'.PHP_EOL;
*/
		echo '<p style="margin:0.5em 0 0 0;"><strong>' . sprintf(__('Changelog for version %s','lmm'), '1.1') . '</strong> - ' . __('released on','lmm') . ' xx.03.2013 (<a href="http://www.mapsmarker.com/v1.1p" target="_blank">' . __('blog post with more details about this release','lmm') . '</a>):</p>
		<table>
		<tr><td>
		<img src="' . LEAFLET_PLUGIN_URL .'inc/img/icon-changelog-new.png">
		</td><td>
		
		</td></tr>
		<tr><td>
		<img src="' . LEAFLET_PLUGIN_URL .'inc/img/icon-changelog-changed.png">
		</td><td>
		
		</td></tr>
		<tr><td>
		<img src="' . LEAFLET_PLUGIN_URL .'inc/img/icon-changelog-fixed.png">
		</td><td>
		
		</td></tr>
		<tr><td>
		<img src="' . LEAFLET_PLUGIN_URL .'inc/img/icon-changelog-translations.png">
		</td><td>
		updated German translation
		</td></tr>
		</table>'.PHP_EOL;

/*info: template
	if ( ( $lmm_version_old < '1.0' ) && ( $lmm_version_old > '0' ) ) {
	}		
*/
	echo '</div>';
}
?>
</body>
</html>