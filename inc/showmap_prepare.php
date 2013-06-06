<?php
/*
    License - Leaflet Maps Marker Plugin
*/
//info prevent file from being accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) == 'showmap_prepare.php') { die ("Please do not access this file directly. Thanks!<br/><a href='http://www.mapsmarker.com/go'>www.mapsmarker.com</a>"); }

require('showmap.php');
if ( maps_marker_pro_is_free_version_and_license_entered() ) {
	if ( (maps_marker_pro_validate_access($release_date=false, $license_only=false)===true) && (maps_marker_pro_validate_access($release_date=false, $license_only=true)===true) ) {
		$licenseexpired = '';
	} else {
		$licenseexpired = '<a style="color:white;text-decoration:none;" href="http://www.mapsmarker.com/expired" target="_blank"><div style="padding:5px;background:#FF4500;text-align:center;">MapsMarker.com: ' . __('please get a valid license key to activate the pro version','lmm') . '</div></a>';
	}
	return $licenseexpired . $lmm_out . $licenseexpired;
}
?>