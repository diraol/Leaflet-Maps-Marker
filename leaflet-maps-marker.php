<?php
/*
Plugin Name: Leaflet Maps Marker Pro &reg;
Plugin URI: http://www.mapsmarker.com
Description: Pin, organize & show your favorite places through OpenStreetMap, Google Maps, Google Earth (KML), Bing Maps, APIs or Augmented-Reality browsers
Tags: map, maps, Leaflet, OpenStreetMap, geoJSON, json, jsonp, OSM, travelblog, opendata, open data, opengov, open government, ogdwien, WMTS, geoRSS, location, geo, geo-mashup, geocoding, geolocation, travel, mapnick, osmarender, cloudmade, mapquest, geotag, geocaching, gpx, OpenLayers, mapping, bikemap, coordinates, geocode, geocoding, geotagging, latitude, longitude, position, route, tracks, google maps, googlemaps, gmaps, google map, google map short code, google map widget, google maps v3, google earth, gmaps, ar, augmented-reality, wikitude, wms, web map service, geocache, geocaching, qr, qr code, fullscreen, marker, marker icons, layer, multiple markers, karte, blogmap, geocms, geographic, routes, tracks, directions, navigation, routing, location plan, YOURS, yournavigation, ORS, openrouteservice, widget, bing, bing maps, microsoft, map short code, map widget, kml, cross-browser, fully documented, traffic, bike lanes, map short code, custom marker text, custom marker icons and text
Version: 1.0
Requires at least: 3.0
Tested up to: 3.6
Requires at least PHP 5.2
Author: Robert Harm
Author URI: http://www.harm.co.at
Customer Area: https://www.mapsmarker.com/login
Terms of Service: http://www.mapsmarker.com/tos
Privacy Policy: http://www.mapsmarker.com/privacy
Copyright 2011-2013 - @RobertHarm - All rights reserved
MapsMarker &reg; - registration pending
*/
if (extension_loaded('ionCube Loader')) {
	require_once('leaflet-control.php');
} else {
	function lmm_ioncube_install_check() {
		echo '<div class="error" style="padding:10px;"><strong>' . sprintf(__('Attention: your web server does not meet the requirements for "Leaflet Maps Marker Pro". <a href="%1s" target="_blank">Please click here to start the installation wizard</a> which offers an interactive tutorial on how to install the required "ionCube Loader" first.','lmm'), plugin_dir_url(__FILE__) . 'inc/loader-wizard.php' ) . '</strong></div>';
	}
	add_action('admin_notices', 'lmm_ioncube_install_check');	
}
?>