=== Leaflet Maps Marker Pro (Google Maps, OpenStreetMap, Bing Maps) ===
Contributors:      harmr
Plugin Name:       Leaflet Maps Marker Pro
Plugin URI:        http://www.mapsmarker.com
Tags:              map, maps, Leaflet, OpenStreetMap, geoJSON, json, jsonp, OSM, travelblog, opendata, open data, opengov, open government, ogdwien, WMTS, geoRSS, location, geo, geo-mashup, geocoding, geolocation, travel, mapnick, osmarender, cloudmade, mapquest, geotag, geocaching, gpx, OpenLayers, mapping, bikemap, coordinates, geocode, geocoding, geotagging, latitude, longitude, position, route, tracks, google maps, googlemaps, gmaps, google map, google map short code, google map widget, google maps v3, google earth, gmaps, ar, augmented-reality, wikitude, wms, web map service, geocache, geocaching, qr, qr code, fullscreen, marker, marker icons, layer, multiple markers, karte, blogmap, geocms, geographic, routes, tracks, directions, navigation, routing, location plan, YOURS, yournavigation, ORS, openrouteservice, widget, bing, bing maps, microsoft, map short code, map widget, kml, cross-browser, fully documented, traffic, bike lanes, map short code, custom marker text, custom marker icons and text
Author URI:        http://www.harm.co.at
Author:            Robert Harm
Requires at least: 3.0
Tested up to:      3.6
Stable tag:        1.0

Pin, organize & show your favorite places through OpenStreetMap, Google Maps, Google Earth (KML), Bing Maps, APIs or Augmented-Reality browsers

== Description ==
Maps Marker allows you to pin, organize and share your favorite spots through your WordPress powered site easily. You can use maps from OpenStreetMap, Google Maps, Google Earth, Bing Maps or custom maps and additionally display your spots in innovative ways like through augmented-reality browsers.

= Vision =
We are working hard on delivering the best mapping solution available for WordPress - helping you to share your favorite spots

= Mission Statement =
[Maps Marker](http://www.mapsmarker.com) helps you to share your favorite spots easily. The plugin is based on the famous [leaflet.js library](http://www.leafletjs.com) from [CloudMade](http://www.cloudmade.com) which also powers maps on sites like [Flickr](http://www.flickr.com/map), [Foursquare](http://readwrite.com/2012/02/29/foursquare_dumps_google_goes_open-source_for_maps), [Craigslist](http://www.theverge.com/2012/10/4/3452526/craigslist-map-view-apartment-listings-roll-out), [Wikipedia](http://en.wikipedia.org/wiki/Wikipedia_App) and [Washington Post](http://www.washingtonpost.com/wp-srv/special/politics/election-map-2012/senate/). We use this library as the basis for our plugin and have integrated several other libraries for enhancing its functionality. Our main goal is to provide you with an intuitive and user-friendly interface for organizing your spots within your WordPress powered site. Furthermore we are constantly working on improving our plugin by adding new and innovative ways for managing and showing your spots. Our roadmap for new releases gets aligned to our users' needs - so giving support and talking to our users is essential.

= Plugin's Official Site =
http://mapsmarker.com

* [Demo](http://www.mapsmarker.com/demo/) - [FAQ](http://www.mapsmarker.com/faq/) - [Docs](http://www.mapsmarker.com/docs/) - [Support](http://mapsmarker.com/support/) - [Github](https://github.com/robertharm/Leaflet-Maps-Marker) - [Twitter](http://twitter.com/mapsmarker) - [Facebook](http://facebook.com/mapsmarker) - [Translations](http://translate.mapsmarker.com/projects/lmm)

= Main features =
Leaflet Maps Marker allows you to

* pin your favorites places with markers,
* use integrated address search (Google Places API) for quickly finding your places,
* choose from up to 800 custom free map icons from [Maps Icons Collection](http://mapicons.nicolasmollet.com),
* add popup description text or images for each marker,
* choose individual basemap, size and zoom level for each marker/layer map,
* organize your markers in layers and
* show them thanks to the [Leaflet library from Cloudmade](http://www.leafletjs.com)
* by adding a shortcode (e.g. mapsmarker marker="1")] to posts, pages, widgets or template

on basemaps from

* OpenStreetMap,
* Google Maps (Road, Satellite, Hybrid, Terrain),
* Bing Maps (Aerial, Aerials+Labels, Road),
* MapQuest (Road, Satellite),
* [OGD Vienna Maps](http://data.wien.gv.at) (Road, Aerial, Addresses)
* or any custom WMTS-map

to the visitors of your website.

= Additional features =

* show directions for your locations using Google Maps, yournavigation.org, openrouteservice.org or map.project-osrm.org
* support for Google Maps API key which is required for commercial users
* configure up to 10 WMS servers to display additional information from external geodata providers (like the European Environment Agency) on your maps
* organize markers from different layers in multi-layer-maps
* export your markers as KML file for displaying in Google Earth or Google Maps
* export your markers as GeoJSON file for embedding in external websites or apps
* export your markers as GeoRSS for embedding in external websites or apps
* export your markers as ARML for displaying in the augmented-reality browser from Wikitude
* export your markers as csv-file
* option to switch between simplified and advanced editor
* option to add widgets showing recent marker entries
* configure up to 3 basemaps from Cloudmade with custom styles
* option to add an unobtrusive scale control to maps
* keyboard navigation support for maps
* TinyMCE button for easily searching and adding maps on post/pages edit screen
* display a list of markers below layer maps
* show standalone maps in fullscreen mode
* automatically generated geo sitemap for all maps (for submitting to Google)
* support for microformat geo-markup to make your maps machine-readable
* support for custom marker timestamps for more precise KML animations - [demo video](http://www.youtube.com/watch?v=LXliLaZ4u-E)
* create QR code images for standalone maps in fullscreen mode
* automatically add meta-tags with location information to maps
* automatically add microformat geo-markup to maps
* support for maps that do not reflect the real world (e.g. game, indoor or photo maps)
* option to set Wordpress roles (administrator, editor, author, contributor) which are allowed to add/edit/delete markers and layers
* option to add marker directly to posts or pages without saving them to your database
* audit log for changes on markers & layers (saving first created by/on and last updated by/on info)
* search within your marker list
* mass actions for markers (assignment to layers, deletions)
* option to reset plugin settings to defaults
* option to change the default shortcode '[mapsmarker...]'
* dynamic preview of maps in backend (no need to reload)
* option to select plugin default language in settings for backend and frontend
* WordPress Admin Bar integration to quickly access plugins features (can be disabled)
* "OGD Vienna selector": if a place within boundaries of Vienna/Austria is chosen, OGD Vienna basemaps are automatically selected
* admin dashboard widget showing latest markers and blog posts from mapsmarker.com
* support for google maps and bing map localization (cultures)

= Technical details =
* full support for responsive designs (= automatic resizing of maps if viewport is wider than map width)
* Wordpress Multisite compatibility
* Full RTL (Right-to-Left) Language Support
* plugin was successfully tested to ÖNORM 7700 and OWASP TOP 10 for security issues
* full UTF8-support for cyrillic, chinese or other alphabets on marker/layername and marker popup text
* integrated check for known incompatible plugins (and instructions on how to fix)
* support for other languages through .po/.mo-files (please see http://mapsmarker.com/languages for details if you want to contribute a new translation)
* option to select plugin default language in settings for backend and frontend (separately from language set in wp-config.php)
* support for Retina displays to display maps in a higher resolution
* GeoJSON feeds for every marker and layer with [JSONP support](http://www.mapsmarker.com/geojson)
* use of Wordpress settings API for storing options
* TinyMCE editor on backend for editing popuptext
* integrated plugin compatibility check to warn about incompatible plugins
* dynamic changelog to show all changes since your last plugin update
* version check for minimum Wordpress (3.0) and PHP (5.2) requirements
* use of prepared SQL statements to prevent SQL injections
* use of Wordpress nounces on forms to prevent attacks and input mistakes
* use of custom function names and enque plugin scripts/css only on plugin pages to prevent conflicts with other plugins
* update functions implemented for smooth updates of the plugin
* uninstall function to completely remove the plugin and its data (also on WordPress Multisite installations)

= Available translations =
* Bengali (ba_BD) thanks to Nur Hasan
* Bosnian (bs_BA) thanks to Kenan Dervišević
* Bulgarian (bg_BG) thanks to Andon Ivanov
* Catalan (ca) thanks to Vicent Cubells
* Chinese (zh_CN) thanks to John Shen
* Chinese (zh_TW) thanks to jamesho Ho
* Croatian (hr) thanks to Neven Pausic and Alan Benic
* Czech (cs_CZ) thanks to Viktor Kleiner
* Danish (da_DK) thanks to Mads Dyrmann Larsen
* Dutch (nl_NL) thanks to Marijke Metz and Patrick Ruers
* English (en_US)
* French (fr_FR) thanks to Vincèn Pujol and Rodolphe Quiedeville
* German (de_DE)
* Hindi (hi_IN) thanks to by Outshine Solutions and Guntupalli Karunakar
* Hungarian (hu_HU) thanks to István Pintér
* Indonesian (id_ID) thanks to Emir Hartato
* Italian (it_IT) thanks to Luca Barbetti
* Japanese (ja) thanks to Shu Higashi
* Portuguese (pt_BR) thanks to Andre Santos and Antonio Hammerl
* Polish (pl_PL) translation thanks to Pawel Wyszynski and Tomasz Rudnicki
* Portuguese (pt_PT) translation thanks to Joao Campos
* Romanian (ro_RO) translation thanks to Daniel Codrea
* Russian (ru_RU) thanks to Ekaterina Golubina, supported by Teplitsa of Social Technologies
* Slovak (sk_SK) thanks to Zdenko Podobny
* Spanish (es_ES) thanks to David Ramirez, Alvaro Lara, Ricardo Viteri and Victor Guevara
* Turkish (tr_TR) thanks to Emre Erkan
* Ukrainian (uk_UK) thanks to Andrexj
* Yiddish (yi) thanks to Raphael Finkel

For full credits of each translations please visit [http://www.mapsmarker.com/languages](http://www.mapsmarker.com/languages)

Leaflet Maps Marker also supports easy switching between translations through a specific plugin setting.
For more information on translations and how to contribute a new translation, please visit [http://translate.mapsmarker.com](http://translate.mapsmarker.com/projects/lmm).

== Changelog ==

= v1.0p =
Initial release  - see [http://www.mapsmarker.com/v1.0p](http://www.mapsmarker.com/v1.0p) for more details

= changelog for all versions =
[http://www.mapsmarker.com/changelog](http://www.mapsmarker.com/changelog)