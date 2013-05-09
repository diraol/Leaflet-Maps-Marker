<?php
//info prevent file from being accessed directly
if (basename($_SERVER['SCRIPT_FILENAME']) == 'leaflet-core.php') { die ("Please do not access this file directly. Thanks!<br/><a href='http://www.mapsmarker.com/go'>www.mapsmarker.com</a>"); }
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
define( 'VERSION_RELEASE_DATE', '01/04/2013' );

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
class LeafletmapsmarkerPro
{
	function __construct() {
		global $wp_version;
		$lmm_options = get_option( 'leafletmapsmarker_options' );
		add_action('init', array(&$this, 'lmm_load_translation_files'),1);
		add_action('admin_init', array(&$this, 'lmm_install_and_updates'),3); //info: register_action_hook not used as otherwise Wordpress Network installs break
		add_action('wp_enqueue_scripts', array(&$this, 'lmm_frontend_enqueue_scripts') );
		add_action('wp_print_styles', array(&$this, 'lmm_frontend_enqueue_stylesheets'),4);
		add_action('admin_menu', array(&$this, 'lmm_admin_menu'),5);
		add_action('admin_init', array(&$this, 'lmm_plugin_meta_links'),6);
		//info: override max image width in popups
		if ( version_compare( $wp_version, '3.3', '<' ) ) {
			add_action('wp_head', array(&$this, 'lmm_image_css_override'),1000);
		}
		add_action('admin_bar_menu', array(&$this, 'lmm_add_admin_bar_menu'),149);
		add_shortcode($lmm_options['shortcode'], array(&$this, 'lmm_showmap'));
		add_filter('widget_text', 'do_shortcode'); //info: needed for widgets
		if ( isset($lmm_options['misc_global_admin_notices']) && ($lmm_options['misc_global_admin_notices'] == 'show') ){
			add_action('admin_notices', array(&$this, 'lmm_compatibility_checks'));
		}
		if ($lmm_options['misc_add_georss_to_head'] == 'enabled') {
			add_action( 'wp_head', array( &$this, 'lmm_add_georss_to_head' ) );
		}
		if ( isset($lmm_options['misc_tinymce_button']) && ($lmm_options['misc_tinymce_button'] == 'enabled') ) {
			require_once( plugin_dir_path( __FILE__ ) . 'inc' . DIRECTORY_SEPARATOR . 'tinymce-plugin.php' );
		}
		if ( isset($lmm_options['misc_plugin_language']) && ($lmm_options['misc_plugin_language'] != 'automatic') ){
			add_filter('plugin_locale', array(&$this,'lmm_set_plugin_locale'), 'lmm');
		}
		add_action('widgets_init', create_function('', 'return register_widget("Class_leaflet_recent_marker_widget");'));
		if ( isset($lmm_options['misc_admin_dashboard_widget']) && ($lmm_options['misc_admin_dashboard_widget'] == 'enabled') ){
			if ( !is_multisite() ) {
				add_action('wp_dashboard_setup', array( &$this,'lmm_register_widgets' ));
			} else {
				add_action('wp_network_dashboard_setup', array( &$this,'lmm_register_widgets' ));
				add_action('wp_dashboard_setup', array( &$this,'lmm_register_widgets' ));
			}
		}
		if ( (isset($lmm_options['misc_pointers'])) && ($lmm_options['misc_pointers'] == 'enabled') ) {
			//info: dont show update pointers on new installs
			$version_before_update = get_option('leafletmapsmarker_version_pro_before_update');
			if ($version_before_update != '0') {
				add_action( 'admin_enqueue_scripts', array( $this, 'lmm_update_pointer_admin_scripts' ),1001);
			}
		}
		//info: add features pointers
		add_action( 'admin_enqueue_scripts', array( $this, 'lmm_feature_pointer_admin_scripts' ),1002);
		//info: multisite only - delete tables+options+files if blog deleted from network admin
		if ( is_multisite() ) {
			add_action('delete_blog', array( &$this,'lmm_delete_multisite_blog' ));
		}
		//info: check template files for do_shortcode()-action
		if ( (isset($lmm_options['misc_conditional_css_loading'])) && ($lmm_options['misc_conditional_css_loading'] == 'enabled') ){
			add_action('template_include', array( &$this,'lmm_template_check_shortcode' ));
		}
		//info: style & add extra links to plugin page (to check if update license is still valid)
		add_action('plugin_row_meta', array( &$this,'lmm_plugins_page_add_links' ), 10, 2);
	}
	function lmm_plugins_page_add_links($links, $file) {
		$plugin = 'leaflet-maps-marker-pro/leaflet-maps-marker.php';
		$plugin_version = get_option('leafletmapsmarker_version_pro');
		if ($file == $plugin) {
			$go_pro_link = '<a style="float:left;" href="http://www.mapsmarker.com/reviews" target="_blank"  title="' . esc_attr__('Upgrade to pro version for even more features - click here to find out how you can start a free 30-day-trial easily','lmm') . '"><img style="margin-top:4px;margin-right:5px;" src="' . LEAFLET_PLUGIN_URL . 'inc/img/pro-upgrade.png" width="80" height="15" alt="go pro"></a>';
			$rate_link = '<a style="text-decoration:none;" href="http://www.mapsmarker.com/reviews" target="_blank" title="' . esc_attr__('please rate this plugin on wordpress.org','lmm') . '"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-star.png" width="16" height="16" alt="ratings"></a>';
			$translation_link = '<a href="http://translate.mapsmarker.com/projects/lmm" target="_blank" title="' . esc_attr__('translations','lmm') . '"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-translations.png" width="16" height="16" alt="translations"></a>';
			$fbook_link = '<a href="http://facebook.com/mapsmarker" target="_blank" title="' . esc_attr__('Follow MapsMarker on Facebook','lmm') . '"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-facebook.png" width="16" height="16" alt="facebook"></a>';
			$twitter_link = '<a href="http://twitter.com/mapsmarker" target="_blank" title="' . esc_attr__('Follow @MapsMarker on Twitter','lmm') . '"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-twitter.png" width="16" height="16" alt="twitter"></a>';
			$googleplus_link = '<a href="http://www.mapsmarker.com/+" target="_blank" title="' . esc_attr__('Follow MapsMarker on Google+','lmm') . '"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-google-plus.png" width="16" height="16" alt="google+"></a>';
			$rss_link = '<a href="http://feeds.feedburner.com/MapsMarker" target="_blank" title="RSS"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-rss.png" width="16" height="16" alt="rss"></a>';
			$rss_email_link = '<a href="http://feedburner.google.com/fb/a/mailverify?uri=MapsMarker" target="_blank" title="RSS (via Email)"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-rss-email.png" width="16" height="16" alt="rss-email"></a>';
			//info: check if update license has expired - dont check for valid license to show warning on invalid license too
			if ( (maps_marker_pro_validate_access($release_date=false, $license_only=true)===true) && (maps_marker_pro_validate_access()===false) ) {
				$pro_update_info = '<br/></td></tr><tr><td colspan="3"><div style="padding: 3px 5px;background-color: #FFEBE8;border: 1px solid #CC0000;border-radius: 3px;color: #333;"><strong>' . __('Warning: your access to updates and support for Leaflet Maps Marker Pro has expired!','lmm') . '</strong><br/>' . sprintf(__('You can continue using version %s without any limitations. Nevertheless you will not be able to get updates including bugfixes, new features and optimizations as well as access to our support system. ','lmm'), $plugin_version) . '<br/>' . sprintf(__('<a href="%s">Please renew your access to updates and support to keep your plugin up-to-date and safe</a>.','lmm'), LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_license') . '</div>';
			} else {
				$pro_update_info = '';
			}
			$links[] = $rate_link . '&nbsp;&nbsp;&nbsp;' . $translation_link . '&nbsp;&nbsp;&nbsp;&nbsp;' . $fbook_link . '&nbsp;&nbsp;&nbsp;&nbsp;' . $twitter_link . '&nbsp;&nbsp;&nbsp;&nbsp;' . $googleplus_link . '&nbsp;&nbsp;&nbsp;&nbsp;' . $rss_link . '&nbsp;&nbsp;&nbsp;&nbsp;' . $rss_email_link . $pro_update_info;
		}
		return $links;
	}
	function lmm_delete_multisite_blog($blog_id) {
		switch_to_blog($blog_id);
		/* Remove tables */
		$GLOBALS['wpdb']->query("DROP TABLE `".$GLOBALS['wpdb']->prefix."leafletmapsmarker_layers`");
		$GLOBALS['wpdb']->query("DROP TABLE `".$GLOBALS['wpdb']->prefix."leafletmapsmarker_markers`");
		/*remove map icons directory for subsite*/
		$lmm_upload_dir = wp_upload_dir();
		$icons_directory = $lmm_upload_dir['basedir'] . DIRECTORY_SEPARATOR . "leaflet-maps-marker-icons" . DIRECTORY_SEPARATOR;
		if (is_dir($icons_directory)) {
			foreach(glob($icons_directory.'*.*') as $v) {
				unlink($v);
			}
			rmdir($icons_directory);
		}
	}
	function lmm_update_pointer_admin_scripts() {
		$dismissed_pointers = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$dismissed_pointers = array_flip($dismissed_pointers);
		$do_add_script = false;
		$lmm_version_new = get_option( 'leafletmapsmarker_version_pro' );
		$version_without_dots = "lmmvp" . str_replace('.', '', $lmm_version_new);
	
		if ( !isset($dismissed_pointers[$version_without_dots]) ) {
			$do_add_script = true;
			add_action( 'admin_print_footer_scripts', array( $this, 'lmm_update_pointer_footer_script' ) );
		}
		if ( $do_add_script ) {
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_style( 'wp-pointer' );
		}
	}
	function lmm_update_pointer_footer_script() {
		$lmm_version_new = get_option( 'leafletmapsmarker_version_pro' );
		$version_without_dots = "lmmv" . str_replace('.', '', $lmm_version_new);
		$pointer_content = '<h3>' . sprintf(esc_attr__('Leaflet Maps Marker plugin update to v%1s was successful','lmm'), $lmm_version_new) . '</h3>';
		$changelog_url = '<a href="' . LEAFLET_WP_ADMIN_URL . '/admin.php?page=leafletmapsmarker_markers' . '" style="text-decoration:none;">' . __('changelog','lmm') . '</a>';
		$blogpost_url = '<a href="http://www.mapsmarker.com/v' . $lmm_version_new . '" target="_blank" style="text-decoration:none;">mapsmarker.com</a>';
		$pointer_content .= '<p>' . sprintf(esc_attr__('Please see the %1s for new features or the blog post on %2s for more details','lmm'), $changelog_url, $blogpost_url) . '</p>';
	  ?>
		<script type="text/javascript">// <![CDATA[
		jQuery(document).ready(function($) {
			if(typeof(jQuery().pointer) != 'undefined') {
				$('#toplevel_page_leafletmapsmarker_markers').pointer({
					content: '<?php echo $pointer_content; ?>',
					position: {
						edge: 'left',
						align: 'center'
					},
					close: function() {
						$.post( ajaxurl, {
							pointer: '<?php echo $version_without_dots; ?>',
							action: 'dismiss-wp-pointer'
						});
					}
				}).pointer('open');
			}
		});
		// ]]></script>
		<?php
	}
	function lmm_feature_pointer_admin_scripts() {
		$dismissed_pointers = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$dismissed_pointers = array_flip($dismissed_pointers);
		$do_add_script = false;
		//info: add new feature pointer IDs below
		if ( !isset($dismissed_pointers["lmmesw"]) ) {
			$do_add_script = true;
			add_action( 'admin_print_footer_scripts', array( $this, 'lmm_feature_pointer_footer_script' ) );
		}
		if ( $do_add_script ) {
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_style( 'wp-pointer' );
		}
	}
	function lmm_feature_pointer_footer_script() {
		include('inc' . DIRECTORY_SEPARATOR . 'feature-pointers.php');
	}
	function lmm_register_widgets(){
		wp_add_dashboard_widget( 'lmm-admin-dashboard-widget', __('Maps Marker Pro - recent markers','lmm'), array( &$this,'lmm_dashboard_widget'), array( &$this,'lmm_dashboard_widget_control'));
	}
	function lmm_dashboard_widget(){
		global $wpdb;
		$lmm_options = get_option( 'leafletmapsmarker_options' );
		//info: set custom marker icon dir/url
		if ( $lmm_options['defaults_marker_custom_icon_url_dir'] == 'no' ) {
			$defaults_marker_icon_dir = LEAFLET_PLUGIN_ICONS_DIR;
			$defaults_marker_icon_url = LEAFLET_PLUGIN_ICONS_URL;
		} else {
			$defaults_marker_icon_dir = htmlspecialchars($lmm_options['defaults_marker_icon_dir']);
			$defaults_marker_icon_url = htmlspecialchars($lmm_options['defaults_marker_icon_url']);
		}
		$table_name_markers = $wpdb->prefix.'leafletmapsmarker_markers';
		$widgets = get_option( 'dashboard_widget_options' );
		$widget_id = 'lmm-admin-dashboard-widget';
		$number_of_markers =  isset( $widgets[$widget_id] ) && isset( $widgets[$widget_id]['items'] ) ? absint( $widgets[$widget_id]['items'] ) : 4;
		//info: show license update warning
		if ( (maps_marker_pro_validate_access($release_date=false, $license_only=true)===true) && (maps_marker_pro_validate_access()===false) ) {
			$plugin_version = get_option('leafletmapsmarker_version_pro');
			echo '<div style="padding: 3px 5px;background-color: #FFEBE8;border: 1px solid #CC0000;border-radius: 3px;color: #333;"><strong>' . __('Warning: your access to updates and support for Leaflet Maps Marker Pro has expired!','lmm') . '</strong><br/>' . sprintf(__('You can continue using version %s without any limitations. Nevertheless you will not be able to get updates including bugfixes, new features and optimizations as well as access to our support system. ','lmm'), $plugin_version) . '<br/>' . sprintf(__('<a href="%s">Please renew your access to updates and support to keep your plugin up-to-date and safe</a>.','lmm'), LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_license') . '</div>';
			echo '<hr style="border:0;height:1px;background-color:#d8d8d8;"/>';
		}
		$result = $wpdb->get_results($wpdb->prepare("SELECT ID,markername,icon,createdon,createdby FROM $table_name_markers ORDER BY createdon desc LIMIT %d", $number_of_markers), ARRAY_A);
		if ($result != NULL) {
			echo '<table style="margin-bottom:5px;"><tr>';
			foreach ($result as $row ) {
				$icon = ($row['icon'] == NULL) ? LEAFLET_PLUGIN_URL . 'leaflet-dist/images/marker.png' : $defaults_marker_icon_url . '/' . $row['icon'];
				echo '<td><a href="' . LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_marker&id=' . $row['ID'] . '" title="' . esc_attr__('edit marker','lmm') . '"><img src="' . $icon . '" style="width:80%;"></a>';
				echo '<td style="vertical-align:top;line-height:1.2em;">';
				echo '<a href="' . LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_marker&id=' . $row['ID'] . '" title="' . esc_attr__('edit marker','lmm') . '">'.htmlspecialchars(stripslashes($row['markername'])).'</a><br/>' . __('created on','lmm') . ' ' . date("Y-m-d - h:m", strtotime($row['createdon'])) . ', ' . __('created by','lmm') . ' ' . $row['createdby'];
				echo '</td></tr>';
			}
			echo '</table>';
		} else {
			echo '<p style="margin-bottom:5px;">' . __('No marker created yet','lmm') . '</p>';
		}
		if  ( !isset($widgets[$widget_id]['blogposts']) ) {
			$show_rss = 1;
		} else if ( isset($widgets[$widget_id]['blogposts']) && ($widgets[$widget_id]['blogposts'] == 1) ) {
			$show_rss = 0;
		} else {
			$show_rss = 1;
		}
		if ($show_rss == 1)
		{
				require_once(ABSPATH . WPINC . DIRECTORY_SEPARATOR . 'class-simplepie.php');
				$feed = new SimplePie();
				if ( file_exists($defaults_marker_icon_dir . DIRECTORY_SEPARATOR . 'readme-icons.txt') ) {
					$feed->enable_cache(true);
					$feed->set_cache_location($location = $defaults_marker_icon_dir);
					$feed->set_cache_duration(86400);
				} else {
					$feed->enable_cache(false);
				}
				$feed->set_feed_url('http://feeds.feedburner.com/MapsMarker');
				$feed->set_stupidly_fast(true);
				$feed->enable_order_by_date(true);
				$feed->init();
				$feed->handle_content_type();
				echo '<hr style="border:0;height:1px;background-color:#d8d8d8;"/><strong><p>' . __('Latest blog posts from www.mapsmarker.com','lmm') . '</p></strong>';
				if ($feed->get_items() == NULL) {
					$blogpost_url = '<a href="http://www.mapsmarker.com/news" target="_blank">http://www.mapsmarker.com/news</a>';
					echo sprintf(__('Feed could not be retrieved, please try again later or read the latest blog posts at %s','lmm'),$blogpost_url);
				}
				foreach ($feed->get_items(0,3) as $item) {
					echo '<p>' . $item->get_date('j F Y') . ': <strong><a href="' . $item->get_permalink() . '">' . $item->get_title() . '</a></strong><br/>' . $item->get_description() . '</p>'.PHP_EOL;
				}
				echo '<p><a style="text-decoration:none;" href="http://www.mapsmarker.com" target="_blank"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-website-home.png" width="16" height="16" alt="mapsmarker.com"> MapsMarker.com</a>&nbsp;&nbsp;
				<a style="text-decoration:none;" href="http://www.mapsmarker.com/reviews" target="_blank" title="' . esc_attr__('please rate this plugin on wordpress.org','lmm') . '"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-star.png" width="16" height="16" alt="ratings"> ' . __('rate plugin','lmm') . '</a>&nbsp;&nbsp;&nbsp;<a href="http://translate.mapsmarker.com/projects/lmm" target="_blank"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-translations.png" width="16" height="16" alt="translations"> ' . __('translations','lmm') . '</a>&nbsp;&nbsp;&nbsp;<a href="http://twitter.com/mapsmarker" target="_blank"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-twitter.png" width="16" height="16" alt="twitter"> Twitter</a>&nbsp;&nbsp;&nbsp;<a href="http://facebook.com/mapsmarker" target="_blank"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-facebook.png" width="16" height="16" alt="facebook"> Facebook</a>&nbsp;&nbsp;&nbsp;<a href="http://www.mapsmarker.com/+" target="_blank"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-google-plus.png" width="16" height="16" alt="google+"> Google+</a>&nbsp;&nbsp;&nbsp;<a style="text-decoration:none;" href="http://www.mapsmarker.com/changelog" target="_blank"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-changelog-header.png" width="16" height="16" alt="changelog"> ' . __('Changelog','lmm') . '</a>&nbsp;&nbsp;&nbsp;<a href="http://feeds.feedburner.com/MapsMarker" target="_blank"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-rss.png" width="16" height="16" alt="rss"> RSS</a>&nbsp;&nbsp;&nbsp;<a href="http://feedburner.google.com/fb/a/mailverify?uri=MapsMarker" target="_blank"><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-rss-email.png" width="16" height="16" alt="rss-email"> ' . __('E-Mail','lmm') . '</a>&nbsp;&nbsp;&nbsp;</p>';
		}
	}
	function lmm_dashboard_widget_control(){
		$widget_id = 'lmm-admin-dashboard-widget';
		$form_id = 'lmm-admin-dashboard-widget-control';
		$update = false;
		if ( !$widget_options = get_option( 'dashboard_widget_options' ) )
		  $widget_options = array();
		if ( !isset($widget_options[$widget_id]) ) {
		//info: set default value
		  $widget_options[$widget_id] = array(
				'blogposts' => 0,
				'items' => 5
		  );
		  $update = true;
		}
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST[$form_id]) ) {
		  $number = ($_POST[$form_id]['items'] == NULL) ? '3' : absint( $_POST[$form_id]['items'] );
		  //$number = absint( $_POST[$form_id]['items'] );
		  $blogposts = isset($_POST[$form_id]['blogposts']) ? '1' : '0';
		  $widget_options[$widget_id]['items'] = $number;
		  $widget_options[$widget_id]['blogposts'] = $blogposts;
		  $update = true;
		}
		if($update) update_option( 'dashboard_widget_options', $widget_options );
		$number = isset( $widget_options[$widget_id]['items'] ) ? (int) $widget_options[$widget_id]['items'] : '';
		echo '<p><label for="lmm-admin-dashboard-widget-number">' . __('Number of markers to show:') . ' </label>';
		echo '<input id="lmm-admin-dashboard-widget-number" name="'.$form_id.'[items]" type="text" value="' . $number . '" size="2" /></p>';
		echo '<p><label for="lmm-admin-dashboard-widget-blogposts">' . __('Hide blog posts and link section:') . ' </label>';
		echo '<input id="lmm-admin-dashboard-widget-blogposts" name="'.$form_id.'[blogposts]" type="checkbox" ' . checked($widget_options[$widget_id]['blogposts'],1,false) . '/></p>';
	}
	function lmm_load_translation_files() {
		load_plugin_textdomain('lmm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
	}
	function lmm_set_plugin_locale( $lang ) {
		$lmm_options = get_option( 'leafletmapsmarker_options' );
		global $locale;
		if ($lmm_options['misc_plugin_language_area'] == 'backend') {
			return is_admin() ? $lmm_options['misc_plugin_language'] : $locale;
		} else if ($lmm_options['misc_plugin_language_area'] == 'frontend') {
			return is_admin() ? $locale : $lmm_options['misc_plugin_language'];
		} else if ($lmm_options['misc_plugin_language_area'] == 'both') {
			return $lmm_options['misc_plugin_language'];
		} else {
			return $locale;
		}
	}
	function lmm_compatibility_checks() {
		include('inc' . DIRECTORY_SEPARATOR . 'compatibility-checks.php');
	}
	function lmm_help() {
		include('leaflet-help-credits.php');
	}
	function lmm_settings() {
		global $lmm_options_class;
		$lmm_options_class->display_page();
	}
	function lmm_list_layers() {
		include('leaflet-list-layers.php');
	}
	function lmm_list_markers() {
		include('leaflet-list-markers.php');
	}
	function lmm_layer() {
		include('leaflet-layer.php');
	}
	function lmm_marker() {
		include('leaflet-marker.php');
	}
	function lmm_tools() {
		include('leaflet-tools.php');
	}
	function lmm_add_georss_to_head() {
		$georss_to_head = '<link rel="alternate" type="application/rss+xml" title="' . get_bloginfo('name') . ' GeoRSS-Feed" href="' . LEAFLET_PLUGIN_URL . 'leaflet-georss.php?layer=all" />'.PHP_EOL;
		echo $georss_to_head;
	}
	function lmm_showmap($atts) {
		require('inc' . DIRECTORY_SEPARATOR . 'showmap.php');
		if ( maps_marker_pro_is_free_version_and_license_entered() ) {
			if ( (maps_marker_pro_validate_access($release_date=false, $license_only=false)===true) && (maps_marker_pro_validate_access($release_date=false, $license_only=true)===true) ) {
				$licenseexpired = '';
			} else {
				$licenseexpired = '<a style="color:white;text-decoration:none;" href="http://www.mapsmarker.com/expired" target="_blank"><div style="padding:5px;background:#FF4500;text-align:center;">MapsMarker.com: ' . __('please get a valid license key to activate the pro version','lmm') . '</div></a>';
			}
			return $licenseexpired . $lmm_out . $licenseexpired;
		}
		return $lmm_out;
	}
	function lmm_admin_menu() {
		$page = (isset($_GET['page']) ? $_GET['page'] : '');
		$oid = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : '');
		if ( ($oid == NULL) && ($page == 'leafletmapsmarker_marker') ) {
			$marker_menu_name = __("Add new marker", "lmm");
		} else if ( ($oid != NULL) && ($page == 'leafletmapsmarker_marker') ) {
			$marker_menu_name = __("Edit marker", "lmm");
		} else {
			$marker_menu_name = __("Add new marker", "lmm");
		}
		if ( ($oid == NULL) && ($page == 'leafletmapsmarker_layer') ) {
			$layer_menu_name = __("Add new layer", "lmm");
		} else if ( ($oid != NULL) && ($page == 'leafletmapsmarker_layer') ) {
			$layer_menu_name = __("Edit layer", "lmm");
		} else {
			$layer_menu_name = __("Add new layer", "lmm");
		}
		$lmm_options = get_option( 'leafletmapsmarker_options' );
		$page = add_object_page('Maps Marker Pro', 'Maps Marker Pro', $lmm_options[ 'capabilities_edit' ], 'leafletmapsmarker_markers', array(&$this, 'lmm_list_markers'), LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-page.png' );
		$page2 = add_submenu_page('leafletmapsmarker_markers', 'Maps Marker Pro - ' . __('List all markers', 'lmm'), '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-list.png"> ' . __('List all markers', 'lmm'), $lmm_options[ 'capabilities_edit' ], 'leafletmapsmarker_markers', array(&$this, 'lmm_list_markers') );
		$page3 = add_submenu_page('leafletmapsmarker_markers', 'Maps Marker Pro - ' . __('add/edit marker', 'lmm'), '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-add.png"> ' . $marker_menu_name, $lmm_options[ 'capabilities_edit' ], 'leafletmapsmarker_marker', array(&$this, 'lmm_marker') );
		$page4 = add_submenu_page('leafletmapsmarker_markers', 'Maps Marker Pro - ' . __('List all layers', 'lmm'), '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-list.png"> ' . __('List all layers', 'lmm'), $lmm_options[ 'capabilities_edit' ], 'leafletmapsmarker_layers', array(&$this, 'lmm_list_layers') );
		$page5 = add_submenu_page('leafletmapsmarker_markers', 'Maps Marker Pro - ' . __('add/edit layer', 'lmm'), '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-add.png"> ' . $layer_menu_name, $lmm_options[ 'capabilities_edit' ], 'leafletmapsmarker_layer', array(&$this, 'lmm_layer') );
		$page6 = add_submenu_page('leafletmapsmarker_markers', 'Maps Marker Pro - ' . __('Tools', 'lmm'), '<hr noshade size="1"/><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-tools.png"> ' . __('Tools', 'lmm'), 'activate_plugins','leafletmapsmarker_tools', array(&$this, 'lmm_tools') );
		$page7 = add_submenu_page('leafletmapsmarker_markers', 'Maps Marker - ' . __('Settings', 'lmm'), '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-settings.png"> ' . __('Settings', 'lmm'), 'activate_plugins','leafletmapsmarker_settings', array(&$this, 'lmm_settings') );
		$page8 = add_submenu_page('leafletmapsmarker_markers', 'Maps Marker Pro - ' . __('Support', 'lmm'), '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-help.png"> ' . __('Support', 'lmm'), $lmm_options[ 'capabilities_edit' ], 'leafletmapsmarker_help', array(&$this, 'lmm_help') );
		$page9 = add_submenu_page('leafletmapsmarker_markers', 'www.mapsmarker.com', '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-external.png"> ' . 'mapsmarker.com', $lmm_options[ 'capabilities_edit' ], 'www_mapsmarker_com', array(&$this, 'lmm_mapsmarker_com') );
		$page10 = add_submenu_page('leafletmapsmarker_markers', 'Maps Marker Pro - ' . __('License settings', 'lmm'), '<hr noshade size="1"/><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-settings.png"> ' . __('License settings', 'lmm'), $lmm_options[ 'capabilities_edit' ], 'leafletmapsmarker_license', 'maps_marker_pro_license_activation_page' );

		//info: add javascript - leaflet.js - for admin area
		add_action('admin_print_scripts-'.$page3, array(&$this, 'lmm_admin_enqueue_scripts'),7);
		add_action('admin_print_scripts-'.$page5, array(&$this, 'lmm_admin_enqueue_scripts'),8);
		add_action('admin_print_scripts-'.$page7, array(&$this, 'lmm_admin_jquery_ui'),9);
		//info: add css styles for admin area
		add_action('admin_print_styles-'.$page, array(&$this, 'lmm_admin_enqueue_stylesheets'),17);
		add_action('admin_print_styles-'.$page2, array(&$this, 'lmm_admin_enqueue_stylesheets'),18);
		add_action('admin_print_styles-'.$page3, array(&$this, 'lmm_admin_enqueue_stylesheets'),19);
		add_action('admin_print_styles-'.$page4, array(&$this, 'lmm_admin_enqueue_stylesheets'),20);
		add_action('admin_print_styles-'.$page5, array(&$this, 'lmm_admin_enqueue_stylesheets'),21);
		add_action('admin_print_styles-'.$page6, array(&$this, 'lmm_admin_enqueue_stylesheets'),22);
		add_action('admin_print_styles-'.$page7, array(&$this, 'lmm_admin_enqueue_stylesheets'),23);
		add_action('admin_print_styles-'.$page8, array(&$this, 'lmm_admin_enqueue_stylesheets'),23);
		add_action('admin_print_styles-'.$page10, array(&$this, 'lmm_admin_enqueue_stylesheets'),23);
		//info: add css styles for datepicker
		add_action('admin_print_styles-'.$page3, array(&$this, 'lmm_admin_enqueue_stylesheets_datepicker'),24);
		//info: add contextual help on all pages
		add_action('admin_print_scripts-'.$page, array(&$this, 'lmm_add_contextual_help'));
		add_action('admin_print_scripts-'.$page2, array(&$this, 'lmm_add_contextual_help'));
		add_action('admin_print_scripts-'.$page3, array(&$this, 'lmm_add_contextual_help'));
		add_action('admin_print_scripts-'.$page4, array(&$this, 'lmm_add_contextual_help'));
		add_action('admin_print_scripts-'.$page5, array(&$this, 'lmm_add_contextual_help'));
		add_action('admin_print_scripts-'.$page6, array(&$this, 'lmm_add_contextual_help'));
		add_action('admin_print_scripts-'.$page7, array(&$this, 'lmm_add_contextual_help'));
		add_action('admin_print_scripts-'.$page8, array(&$this, 'lmm_add_contextual_help'));
		add_action('admin_print_scripts-'.$page10, array(&$this, 'lmm_add_contextual_help'));
		//info: add jquery datepicker on marker page
		add_action('admin_print_scripts-'.$page3, array(&$this, 'lmm_admin_enqueue_scripts_jquerydatepicker'));
		//info: add image css override for marker+layer edit page
		add_action( 'admin_head-'. $page3, array(&$this, 'lmm_image_css_override'),1000);
		add_action( 'admin_head-'. $page5, array(&$this, 'lmm_image_css_override'),1000);
	}
	function lmm_mapsmarker_com(){
		echo '<script type="text/javascript">window.location.href = "http://www.mapsmarker.com";</script>  ';
	}
	function lmm_add_admin_bar_menu() {
		global $wp_version;
		if ( version_compare( $wp_version, '3.1', '>=' ) )
		{
			$lmm_options = get_option( 'leafletmapsmarker_options' );
			if ( $lmm_options[ 'admin_bar_integration' ] == 'enabled' && current_user_can($lmm_options[ 'capabilities_edit' ]) )
			{
			global $wp_admin_bar;
				$menu_items = array(
					array(
						'id' => 'lmm',
						'title' => '<img style="float:left;margin:3px 5px 0 0;" src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-tinymce.png"/></span> Maps Marker Pro',
						'href' => LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_markers',
						'meta' => array( 'title' => 'Wordpress-Plugin ' . __('by','lmm') . ' www.mapsmarker.com' )
					),
					array(
						'id' => 'lmm-markers',
						'parent' => 'lmm',
						'title' => '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-list.png"> ' . __('List all markers','lmm'),
						'href' => LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_markers'
					),
					array(
						'id' => 'lmm-add-marker',
						'parent' => 'lmm',
						'title' => '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-add.png"> ' . __('Add new marker','lmm'),
						'href' => LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_marker'
					),
					array(
						'id' => 'lmm-layers',
						'parent' => 'lmm',
						'title' => '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-list.png"> ' . __('List all layers','lmm'),
						'href' => LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_layers'
					),
					array(
						'id' => 'lmm-add-layers',
						'parent' => 'lmm',
						'title' => '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-add.png"> ' . __('Add new layer','lmm'),
						'href' => LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_layer'
					)
				);
				if ( current_user_can( 'activate_plugins' ) ) {
					$menu_items = array_merge($menu_items, array(
						array(
							'id' => 'lmm-tools',
							'parent' => 'lmm',
							'title' => '<hr style="margin:3px 0;" noshade size="1"/><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-tools.png"> ' . __('Tools','lmm'),
							'href' => LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_tools'
						),
						array(
							'id' => 'lmm-settings',
							'parent' => 'lmm',
							'title' => '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-settings.png"> ' . __('Settings','lmm'),
							'href' => LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_settings'
						)
					));
				}
				$menu_items = array_merge($menu_items, array(
						array(
							'id' => 'lmm-help-credits',
							'parent' => 'lmm',
							'title' => '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-help.png"> ' . __('Support','lmm'),
							'href' => LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_help'
						),
						array(
							'id' => 'lmm-plugin-website',
							'parent' => 'lmm',
							'title' => '<img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-external.png"> ' . 'mapsmarker.com',
							'href' => 'http://www.mapsmarker.com',
							'meta' => array( 'target' => '_blank', 'title' => __('Open plugin website','lmm') )
						),
						array(
							'id' => 'lmm-license',
							'parent' => 'lmm',
							'title' => '<hr style="margin:3px 0;" noshade size="1"/><img src="' . LEAFLET_PLUGIN_URL . 'inc/img/icon-menu-settings.png"> ' . __('License settings','lmm'),
							'href' => LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_license'
						)
					));
	
				foreach ($menu_items as $menu_item) {
					$wp_admin_bar->add_menu($menu_item);
				}
			}
		}
	}
	function lmm_add_contextual_help() {
		global $wp_version;
		$helptext = '<p>' . __('Do you have questions or issues with Leaflet Maps Marker? Please use the following support channels appropriately.','lmm') . '<br/>';
		$helptext .= '<strong>' . __('One personal request: before you post a new support ticket in the <a href="http://wordpress.org/support/plugin/leaflet-maps-marker" target="_blank">Wordpress Support Forum</a>, please follow the instructions from <a href="http://www.mapsmarker.com/readme-first" target="_blank">http://www.mapsmarker.com/readme-first</a> which give you a guideline on how to deal with the most common issues.','lmm') . '</strong></p>';
		$helptext .= '<ul>';
		$helptext .= '<li><a href="http://www.mapsmarker.com/faq/" target="_blank">' . __('FAQ','lmm') . '</a> (' . __('frequently asked questions','lmm') . ')</li>';
		$helptext .= '<li><a href="http://www.mapsmarker.com/docs/" target="_blank">' . __('Documentation','lmm') . '</a></li>';
		$helptext .= '<li><a href="http://wordpress.org/support/plugin/leaflet-maps-marker" target="_blank">WordPress Support Forum</a> (' . __('free community support','lmm') . ')</li>';
		$helptext .= '<li><a href="http://www.mapsmarker.com/login" target="_blank">mapsmarker.com/login</a> (' . __('support for pro users','lmm') . ')</li>';
		$helptext .= '</ul>';
		if ( version_compare( $wp_version, '3.3', '<' ) )
		{
			global $current_screen;
			add_contextual_help( $current_screen, $helptext );
		}
		else if ( version_compare( $wp_version, '3.3', '>=' ) )
		{
			$screen = get_current_screen();
			$screen->add_help_tab( array( 'id' => 'lmm_help_tab', 'title' => __('Help & Support','lmm'), 'content' => $helptext ));
		}
	}
	function lmm_admin_jquery_ui() {
		wp_enqueue_script( array ( 'jquery', 'jquery-ui-tabs' ) );
	}
	function lmm_frontend_enqueue_scripts() {
		global $wp_version;
		$lmm_options = get_option( 'leafletmapsmarker_options' );
		$plugin_version = get_option('leafletmapsmarker_version_pro');
	
		//info: load needed Google libraries only
		$google_adsense_status = $lmm_options['google_adsense_status'];
		if ($google_adsense_status == 'enabled') {
			$gmaps_libraries = '&libraries=adsense';
		} else {
			$gmaps_libraries = '';
		}
	
		//info: Google language localization (JSON API)
		if ($lmm_options['google_maps_language_localization'] == 'browser_setting') {
			$google_language = '';
		} else if ($lmm_options['google_maps_language_localization'] == 'wordpress_setting') {
			if ( defined('WPLANG') ) { $google_language = "&language=" . substr(WPLANG, 0, 2); } else { $google_language =  '&language=en'; }
		} else {
			$google_language = "&language=" . $lmm_options['google_maps_language_localization'];
		}
		if ($lmm_options['google_maps_base_domain_custom'] == '') {
			$gmaps_base_domain = "&base_domain=" . $lmm_options['google_maps_base_domain'];
		} else {
			$gmaps_base_domain = "&base_domain=" . $lmm_options['google_maps_base_domain_custom'];
		}
	
		//info: Google API key
		if ( isset($lmm_options['google_maps_api_key']) && ($lmm_options['google_maps_api_key'] != NULL) ) { $google_maps_api_key = $lmm_options['google_maps_api_key']; } else { $google_maps_api_key = ''; }
		//info: fallback for adding js to footer 1
		if ( (version_compare( $wp_version, '3.3', '>=' )) && ($lmm_options['misc_javascript_header_footer'] == 'footer') ) {
			wp_register_script( 'leafletmapsmarker-googlemaps-loader', 'https://www.google.com/jsapi?key='.$google_maps_api_key, array(), 3.7, true);
		} else if ( (version_compare( $wp_version, '3.3', '<' )) || ((version_compare( $wp_version, '3.3', '>=' )) && ($lmm_options['misc_javascript_header_footer'] == 'header')) ) {
			wp_enqueue_script( 'leafletmapsmarker-googlemaps-loader', 'https://www.google.com/jsapi?key='.$google_maps_api_key, array(), NULL);
		}
	
		//info: Google Maps styling
		$google_styling_json = ($lmm_options['google_styling_json'] == NULL) ? 'disabled' : str_replace("\"", "'", $lmm_options['google_styling_json']);
		//info: Bing culture code
		if ($lmm_options['bingmaps_culture'] == 'automatic') {
			if ( defined('WPLANG') ) { $bing_culture = WPLANG; } else { $bing_culture =  'en_us'; }
		} else {
			$bing_culture = $lmm_options['bingmaps_culture'];
		}
		//info: load leaflet.js + plugins
		//info: fallback for adding js to footer 2
		if ( (version_compare( $wp_version, '3.3', '>=' )) && ($lmm_options['misc_javascript_header_footer'] == 'footer')) {
			wp_register_script( 'leafletmapsmarker', LEAFLET_PLUGIN_URL . 'leaflet-dist/leaflet.js', array('leafletmapsmarker-googlemaps-loader' ), $plugin_version, true);
			wp_register_script( 'show_map', LEAFLET_PLUGIN_URL . 'inc/js/show_map.js', array('leafletmapsmarker' ), $plugin_version, true);
		} else if ( (version_compare( $wp_version, '3.3', '<' )) || ((version_compare( $wp_version, '3.3', '>=' )) && ($lmm_options['misc_javascript_header_footer'] == 'header')) ) {
			wp_enqueue_script( 'leafletmapsmarker', LEAFLET_PLUGIN_URL . 'leaflet-dist/leaflet.js', array('leafletmapsmarker-googlemaps-loader'), $plugin_version);
		}
		if ($google_adsense_status == 'disabled') {
			wp_localize_script('leafletmapsmarker', 'mapsmarkerjs', array(
				'zoom_in' => __( 'Zoom in', 'lmm' ),
				'zoom_out' => __( 'Zoom out', 'lmm' ),
				'googlemaps_language' => $google_language,
				'googlemaps_libraries' => $gmaps_libraries,
				'googlemaps_base_domain' => $gmaps_base_domain,
				'bing_culture' => $bing_culture,
				'google_adsense_status' => $google_adsense_status,
				'google_styling_json' => $google_styling_json,
				'minimap_show' => __( 'Show minimap', 'lmm' ),
				'minimap_hide' => __( 'Hide minimap', 'lmm' ),
				'minimap_status' => $lmm_options['minimap_status'],
				'fullscreen_button_title' => __('View fullscreen','lmm'),
				'fullscreen_button_position' => $lmm_options['map_fullscreen_button_position']
				) );
		} else {
			$google_adsense_format = $lmm_options['google_adsense_format'];
			$google_adsense_position = $lmm_options['google_adsense_position'];
			$google_adsense_backgroundColor = $lmm_options['google_adsense_backgroundColor'];
			$google_adsense_borderColor = $lmm_options['google_adsense_borderColor'];
			$google_adsense_titleColor = $lmm_options['google_adsense_titleColor'];
			$google_adsense_textColor = $lmm_options['google_adsense_textColor'];
			$google_adsense_urlColor = $lmm_options['google_adsense_urlColor'];
			$google_adsense_channelNumber = $lmm_options['google_adsense_channelNumber'];
			$google_adsense_publisherId = $lmm_options['google_adsense_publisherId'];
			wp_localize_script('leafletmapsmarker', 'mapsmarkerjs', array(
				'zoom_in' => __( 'Zoom in', 'lmm' ),
				'zoom_out' => __( 'Zoom out', 'lmm' ),
				'googlemaps_language' => $google_language,
				'googlemaps_libraries' => $gmaps_libraries,
				'googlemaps_base_domain' => $gmaps_base_domain,
				'bing_culture' => $bing_culture,
				'google_adsense_status' => $google_adsense_status,
				'google_adsense_format' => $google_adsense_format,
				'google_adsense_position' => $google_adsense_position,
				'google_adsense_backgroundColor' => $google_adsense_backgroundColor,
				'google_adsense_borderColor' => $google_adsense_borderColor,
				'google_adsense_titleColor' => $google_adsense_titleColor,
				'google_adsense_textColor' => $google_adsense_textColor,
				'google_adsense_urlColor' => $google_adsense_urlColor,
				'google_adsense_channelNumber' => $google_adsense_channelNumber,
				'google_adsense_publisherId' => $google_adsense_publisherId,
				'google_styling_json' => $google_styling_json,
				'minimap_show' => __( 'Show minimap', 'lmm' ),
				'minimap_hide' => __( 'Hide minimap', 'lmm' ),
				'minimap_status' => $lmm_options['minimap_status'],
				'fullscreen_button_title' => __('View fullscreen','lmm'),
				'fullscreen_button_position' => $lmm_options['map_fullscreen_button_position']
			) );
		}
	}
	function lmm_admin_enqueue_scripts() {
		$lmm_options = get_option( 'leafletmapsmarker_options' );
		$plugin_version = get_option('leafletmapsmarker_version_pro');
	
		//info: load needed Google libraries only
		$google_adsense_status = $lmm_options['google_adsense_status'];
		if ($google_adsense_status == 'enabled') {
			$gmaps_libraries = '&libraries=places,adsense';
		} else {
			$gmaps_libraries = '&libraries=places';
		}
		if ( defined('WPLANG') ) { $lang = substr(WPLANG, 0, 2); } else { $lang =  'en'; }
	
		//info: Google language localization (JSON API)
		if ($lmm_options['google_maps_language_localization'] == 'browser_setting') {
			$google_language = '';
		} else if ($lmm_options['google_maps_language_localization'] == 'wordpress_setting') {
			if ( defined('WPLANG') ) { $google_language = "&language=" . substr(WPLANG, 0, 2); } else { $google_language =  '&language=en'; }
		} else {
			$google_language = "&language=" . $lmm_options['google_maps_language_localization'];
		}
		if ($lmm_options['google_maps_base_domain_custom'] != '') {
			$gmaps_base_domain = "&base_domain=" . $lmm_options['google_maps_base_domain'];
		} else {
			$gmaps_base_domain = "&base_domain=" . $lmm_options['google_maps_base_domain_custom'];
		}
		wp_enqueue_script( array ( 'jquery' ) );
	
		//info: Google API key
		if ( isset($lmm_options['google_maps_api_key']) && ($lmm_options['google_maps_api_key'] != NULL) ) { $google_maps_api_key = $lmm_options['google_maps_api_key']; } else { $google_maps_api_key = ''; }
		wp_enqueue_script( 'leafletmapsmarker-googlemaps-loader', 'https://www.google.com/jsapi?key='.$google_maps_api_key, array(), NULL);
	
		//info: Google Maps styling
		$google_styling_json = ($lmm_options['google_styling_json'] == NULL) ? 'disabled' : str_replace("\"", "'", $lmm_options['google_styling_json']);
	
		//info: Bing culture code
		if ($lmm_options['bingmaps_culture'] == 'automatic') {
			if ( defined('WPLANG') ) { $bing_culture = WPLANG; } else { $bing_culture =  'en_us'; }
		} else {
			$bing_culture = $lmm_options['bingmaps_culture'];
		}
		//info: load leaflet.js + plugins
		wp_enqueue_script( 'leafletmapsmarker', LEAFLET_PLUGIN_URL . 'leaflet-dist/leaflet.js', array('leafletmapsmarker-googlemaps-loader'), $plugin_version);
		if ($google_adsense_status == 'disabled') {
			wp_localize_script('leafletmapsmarker', 'mapsmarkerjs', array(
				'zoom_in' => __( 'Zoom in', 'lmm' ),
				'zoom_out' => __( 'Zoom out', 'lmm' ),
				'googlemaps_language' => $google_language,
				'googlemaps_libraries' => $gmaps_libraries,
				'googlemaps_base_domain' => $gmaps_base_domain,
				'bing_culture' => $bing_culture,
				'google_adsense_status' => $google_adsense_status,
				'google_styling_json' => $google_styling_json,
				'minimap_show' => __( 'Show minimap', 'lmm' ),
				'minimap_hide' => __( 'Hide minimap', 'lmm' ),
				'minimap_status' => $lmm_options['minimap_status'],
				'fullscreen_button_title' => __('View fullscreen','lmm'),
				'fullscreen_button_position' => $lmm_options['map_fullscreen_button_position']
				) );
		} else {
			$google_adsense_format = $lmm_options['google_adsense_format'];
			$google_adsense_position = $lmm_options['google_adsense_position'];
			$google_adsense_backgroundColor = $lmm_options['google_adsense_backgroundColor'];
			$google_adsense_borderColor = $lmm_options['google_adsense_borderColor'];
			$google_adsense_titleColor = $lmm_options['google_adsense_titleColor'];
			$google_adsense_textColor = $lmm_options['google_adsense_textColor'];
			$google_adsense_urlColor = $lmm_options['google_adsense_urlColor'];
			$google_adsense_channelNumber = $lmm_options['google_adsense_channelNumber'];
			$google_adsense_publisherId = $lmm_options['google_adsense_publisherId'];
			wp_localize_script('leafletmapsmarker', 'mapsmarkerjs', array(
				'zoom_in' => __( 'Zoom in', 'lmm' ),
				'zoom_out' => __( 'Zoom out', 'lmm' ),
				'googlemaps_language' => $google_language,
				'googlemaps_libraries' => $gmaps_libraries,
				'googlemaps_base_domain' => $gmaps_base_domain,
				'bing_culture' => $bing_culture,
				'google_adsense_status' => $google_adsense_status,
				'google_adsense_format' => $google_adsense_format,
				'google_adsense_position' => $google_adsense_position,
				'google_adsense_backgroundColor' => $google_adsense_backgroundColor,
				'google_adsense_borderColor' => $google_adsense_borderColor,
				'google_adsense_titleColor' => $google_adsense_titleColor,
				'google_adsense_textColor' => $google_adsense_textColor,
				'google_adsense_urlColor' => $google_adsense_urlColor,
				'google_adsense_channelNumber' => $google_adsense_channelNumber,
				'google_adsense_publisherId' => $google_adsense_publisherId,
				'google_styling_json' => $google_styling_json,
				'minimap_show' => __( 'Show minimap', 'lmm' ),
				'minimap_hide' => __( 'Hide minimap', 'lmm' ),
				'minimap_status' => $lmm_options['minimap_status'],
				'fullscreen_button_title' => __('View fullscreen','lmm'),
				'fullscreen_button_position' => $lmm_options['map_fullscreen_button_position']
				) );
		}
	}
	function lmm_image_css_override() {
		$lmm_options = get_option( 'leafletmapsmarker_options' );
		echo '<style type="text/css" id="leafletmapsmarker-image-css-override">.leaflet-popup-content img { max-width:' . intval($lmm_options['defaults_marker_popups_image_max_width']) . 'px !important; height:auto; margin: 0px !important; padding: 0px !important; box-shadow:none !important; width:auto !important; }</style>';
	}
	function lmm_admin_enqueue_scripts_jquerydatepicker() {
		$plugin_version = get_option('leafletmapsmarker_version_pro');
		wp_enqueue_script( array ( 'jquery', 'jquery-ui-tabs','jquery-ui-datepicker','jquery-ui-slider' ) );
		wp_enqueue_script( 'jquery-ui-timepicker-addon', LEAFLET_PLUGIN_URL . 'inc/js/jquery-ui-timepicker-addon.js', array('jquery', 'jquery-ui-tabs','jquery-ui-datepicker'), $plugin_version);
	}
	function lmm_frontend_enqueue_stylesheets() {
		//info: conditional loading of css files
		$lmm_options = get_option( 'leafletmapsmarker_options' );
		if ( (isset($lmm_options['misc_conditional_css_loading'])) && ($lmm_options['misc_conditional_css_loading'] == 'enabled') ){
				global $wp_query;
				$posts = $wp_query->posts;
				$pattern = get_shortcode_regex();
	
				$plugin_version = get_option('leafletmapsmarker_version_pro');
				global $wp_styles, $wp_version;
				wp_register_style('leafletmapsmarker', LEAFLET_PLUGIN_URL . 'leaflet-dist/leaflet.css', array(), $plugin_version);
				wp_register_style('leafletmapsmarker-ie-only', LEAFLET_PLUGIN_URL . 'leaflet-dist/leaflet.ie.css', array(), $plugin_version);
	
				if (is_array($posts)) {
					foreach ($posts as $post) {
						if ( preg_match_all( '/'. $pattern .'/s', $post->post_content, $matches ) && array_key_exists( 2, $matches ) && in_array( $lmm_options['shortcode'], $matches[2] ) ) {
							wp_enqueue_style('leafletmapsmarker');
							wp_enqueue_style('leafletmapsmarker-ie-only');
							$wp_styles->add_data('leafletmapsmarker-ie-only', 'conditional', 'lt IE 9');
							break;
						}
					}
					//info: override max image width in popups
					if ( version_compare( $wp_version, '3.3', '>=' ) ) {
						$lmm_custom_css = ".leaflet-popup-content img { max-width:" . intval($lmm_options['defaults_marker_popups_image_max_width']) . "px !important; height:auto; margin: 0px !important; padding: 0px !important; box-shadow:none !important; width:auto !important; }";
						wp_add_inline_style('leafletmapsmarker',$lmm_custom_css);
					}
				}
		} else {
				global $wp_styles, $wp_version;
				$plugin_version = get_option('leafletmapsmarker_version_pro');
				wp_register_style('leafletmapsmarker', LEAFLET_PLUGIN_URL . 'leaflet-dist/leaflet.css', array(), $plugin_version);
				wp_enqueue_style('leafletmapsmarker');
				wp_register_style('leafletmapsmarker-ie-only', LEAFLET_PLUGIN_URL . 'leaflet-dist/leaflet.ie.css', array(), $plugin_version);
				wp_enqueue_style('leafletmapsmarker-ie-only');
				$wp_styles->add_data('leafletmapsmarker-ie-only', 'conditional', 'lt IE 9');
				//info: override max image width in popups
				if ( version_compare( $wp_version, '3.3', '>=' ) ) {
					$lmm_custom_css = ".leaflet-popup-content img { max-width:" . intval($lmm_options['defaults_marker_popups_image_max_width']) . "px !important; height:auto; margin: 0px !important; padding: 0px !important; box-shadow:none !important; width:auto !important; }";
					wp_add_inline_style('leafletmapsmarker',$lmm_custom_css);
				}
		}
	}
	function lmm_template_check_shortcode( $template ) {
		$lmm_options = get_option( 'leafletmapsmarker_options' );
		$searchterm = '[' . $lmm_options['shortcode'];
		$files = array( $template, get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'header.php', get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'footer.php' );
		foreach( $files as $file ) {
			if( file_exists($file) ) {
				$contents = file_get_contents($file);
				if( strpos( $contents, $searchterm )  ) {
					global $wp_styles;
					$plugin_version = get_option('leafletmapsmarker_version_pro');
					wp_register_style('leafletmapsmarker', LEAFLET_PLUGIN_URL . 'leaflet-dist/leaflet.css', array(), $plugin_version);
					wp_enqueue_style('leafletmapsmarker');
					wp_register_style('leafletmapsmarker-ie-only', LEAFLET_PLUGIN_URL . 'leaflet-dist/leaflet.ie.css', array(), $plugin_version);
					wp_enqueue_style('leafletmapsmarker-ie-only');
					$wp_styles->add_data('leafletmapsmarker-ie-only', 'conditional', 'lt IE 9');
					break;
				}
			}
		}
		return $template;
	}
	function lmm_admin_enqueue_stylesheets() {
		global $wp_styles;
		$plugin_version = get_option('leafletmapsmarker_version_pro');
		wp_register_style( 'leafletmapsmarker', LEAFLET_PLUGIN_URL . 'leaflet-dist/leaflet.css', array(), $plugin_version);
		wp_enqueue_style( 'leafletmapsmarker' );
		wp_register_style( 'leafletmapsmarker-admin', LEAFLET_PLUGIN_URL . 'inc/css/leafletmapsmarker-admin.css', array(), $plugin_version);
		wp_enqueue_style('leafletmapsmarker-admin' );
		wp_register_style('leafletmapsmarker-ie-only', LEAFLET_PLUGIN_URL . 'leaflet-dist/leaflet.ie.css', array(), $plugin_version);
		wp_enqueue_style('leafletmapsmarker-ie-only');
		$wp_styles->add_data('leafletmapsmarker-ie-only', 'conditional', 'lt IE 9');
		//info: compatibility fix for flickr gallery plugin which is breaking the settings page
		if (is_plugin_active('flickr-gallery/flickr-gallery.php') ) {
			wp_dequeue_style('fg-jquery-ui');
		}
	}
	function lmm_admin_enqueue_stylesheets_datepicker() {
		$plugin_version = get_option('leafletmapsmarker_version_pro');
		wp_register_style( 'jquery-ui-all', LEAFLET_PLUGIN_URL . 'inc/css/jquery-datepicker-theme/jquery-ui-1.9.2.custom.css', array(), $plugin_version);
		wp_enqueue_style( 'jquery-ui-all' );
		wp_register_style( 'jquery-ui-timepicker-addon', LEAFLET_PLUGIN_URL . 'inc/css/jquery-datepicker-theme/jquery-ui-timepicker-addon.css', array('jquery-ui-all'), NULL );
		wp_enqueue_style( 'jquery-ui-timepicker-addon' );
	}
	function lmm_install_and_updates() {
		//info: set transient to execute install & update-routine only once a day and on updates
		$current_version = "vp10"; //2do - mandatory: change on each update to new version!
		$schedule_transient = 'leafletmapsmarker_install_update_cache_' . $current_version;
		$install_update_schedule = get_transient( $schedule_transient );
		if ( $install_update_schedule === FALSE ) {
			$schedule_transient = 'leafletmapsmarker_install_update_cache_' . $current_version;
			set_transient( $schedule_transient, 'execute install and update-routine only once a day', 60*60*24 );
			include('inc' . DIRECTORY_SEPARATOR . 'install-and-updates.php');
		}
	}
	function lmm_plugin_meta_links() {
		define( 'FB_BASENAME', plugin_basename( __FILE__ ) );
		define( 'FB_BASEFOLDER', plugin_basename( dirname( __FILE__ ) ) );
		define( 'FB_FILENAME', str_replace( FB_BASEFOLDER.'/', '', plugin_basename(__FILE__) ) );
		function leafletmapsmarker_filter_plugin_meta($links, $file) {
			if ( $file == FB_BASENAME ) {
				array_unshift(
					$links,
					'<a href="' . LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_markers">'.__('Markers','lmm').'</a>',
					'<a href="' . LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_layers">'.__('Layers','lmm').'</a>' ,
					'<a href="' . LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_settings">'.__('Settings','lmm').'</a>',
					'<a href="' . LEAFLET_WP_ADMIN_URL . 'admin.php?page=leafletmapsmarker_license">'.__('License','lmm').'</a>'
				);
			}
			return $links;
		}
		add_filter( 'plugin_action_links', 'leafletmapsmarker_filter_plugin_meta', 10, 2 );
	  } //info: end plugin_meta_links()
} //info: end class LeafletmapsmarkerPro

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
						'download_access_expired' => __('Error: This version of the software was released after your download access expired. Please downgrade or contact support for more information.','lmm'), 
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
						'download_access_expired' => __('Error: This version of the software was released after your download access expired. Please downgrade or contact support for more information.','lmm'), 
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