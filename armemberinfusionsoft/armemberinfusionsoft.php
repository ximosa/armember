<?php
if(!isset($_SESSION)) 
{ @session_start(); }
@error_reporting(E_ERROR | E_WARNING | E_PARSE);
/*
  Plugin Name: ARMember - Infusionsoft Addon
  Description: Extension for ARMember plugin to integration with Infusionsoft
  Version: 1.1
  Plugin URI: https://www.armemberplugin.com
  Author: Repute InfoSystems
  Author URI: https://www.armemberplugin.com
 */

define('ARM_INFUSIONSOFT_DIR_NAME', 'armemberinfusionsoft');
define('ARM_INFUSIONSOFT_DIR', WP_PLUGIN_DIR . '/' . ARM_INFUSIONSOFT_DIR_NAME);

if (is_ssl()) {
    define('ARM_INFUSIONSOFT_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_INFUSIONSOFT_DIR_NAME));
} else {
    define('ARM_INFUSIONSOFT_URL', WP_PLUGIN_URL . '/' . ARM_INFUSIONSOFT_DIR_NAME);
}

define('ARM_INFUSIONSOFT_TXTDOMAIN', 'ARM_INFUSIONSOFT');
define('ARM_INFUSIONSOFT_CORE_DIR', ARM_INFUSIONSOFT_DIR . '/core/');
define('ARM_INFUSIONSOFT_CLASSES_DIR', ARM_INFUSIONSOFT_CORE_DIR . 'classes/');
define('ARM_INFUSIONSOFT_VIEWS_DIR', ARM_INFUSIONSOFT_CORE_DIR . 'view/');
define('ARM_INFUSIONSOFT_IMAGE_URL', ARM_INFUSIONSOFT_URL . '/images/');
define('ARM_INFUSIONSOFT_JS_URL', ARM_INFUSIONSOFT_URL . '/js/');
define('ARM_INFUSIONSOFT_LIB_URL', ARM_INFUSIONSOFT_DIR . '/lib/');

global $arm_infusionsoft_ver, $arm_infusionsoft;
$arm_infusionsoft_ver = '1.1';

require_once(ARM_INFUSIONSOFT_LIB_URL.'isdk.php');

global $arminfusionsoft_api_url, $arminfusionsoft_plugin_slug, $wp_version;

if (!class_exists('ARM_InfusionSoft')) {

    class ARM_InfusionSoft {

        public function __construct() {
            add_action('init', array('ARM_InfusionSoft', 'arm_infusionsoft_db_check'), 100);
            register_activation_hook(__FILE__, array('ARM_InfusionSoft', 'arm_infusionsoft_install'));
            register_uninstall_hook(__FILE__, array('ARM_InfusionSoft', 'arm_infusionsoft_uninstall'));
            add_action('admin_notices', array(&$this, 'arm_infusionsoft_admin_notices'), 100);
            //add_action('admin_enqueue_scripts', array('ARM_InfusionSoft', 'arm_infusionsoft_set_css_js'), 100);
            add_action('plugins_loaded', array(&$this, 'arm_infusionsoft_load_textdomain'));
            add_action('admin_init', array(&$this, 'upgrade_data_infusionsoft'));
        }

        function upgrade_data_infusionsoft() {
            global $arm_infusionsoft_newver;

            if (!isset($arm_infusionsoft_newver) || $arm_infusionsoft_newver == "")
                $arm_infusionsoft_newver = get_option('arm_infusionsoft_version');

            if (version_compare($arm_infusionsoft_newver, '1.1', '<')) {
                $path = ARM_INFUSIONSOFT_DIR . '/upgrade_latest_data_is.php';
                include($path);
            }
        }

        public static function arm_infusionsoft_db_check() {
            global $arm_infusionsoft;
            $arm_get_infusionsoft_version = get_option('arm_infusionsoft_version');
            if (!isset($arm_get_infusionsoft_version) || $arm_get_infusionsoft_version == '') {
                $arm_infusionsoft->arm_infusionsoft_install();
            }
        }

        public static function arm_infusionsoft_install() {
            global $arm_infusionsoft_version;
            $arm_get_infusionsoft_version = get_option('arm_infusionsoft_version');

            if (!isset($arm_get_infusionsoft_version) || $arm_get_infusionsoft_version == '') {
                update_option('arm_infusionsoft_version', $arm_infusionsoft_version);
            }
        }

        public static function arm_infusionsoft_is_armember_support() {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php');
            return is_plugin_active('armember/armember.php');
        }

        public static function arm_infusionsoft_get_armember_version() {
            $arm_infusionsoft_armember_version = get_option('arm_version');
            return isset($arm_infusionsoft_armember_version) ? $arm_infusionsoft_armember_version : 0;
        }

        function arminfusion_getapiurl() {
            $api_url = 'https://www.arpluginshop.com/';
            return $api_url;
        }

        public static function arm_infusionsoft_is_armember_version_compatible() {
            global $arm_infusionsoft;
            if ($arm_infusionsoft->arm_infusionsoft_is_armember_support() && version_compare($arm_infusionsoft->arm_infusionsoft_get_armember_version(), '2.0', '>=')) {
                return true;
            } else {
                return false;
            }
        }

        public function arm_infusionsoft_uninstall() {
            delete_option('arm_infusionsoft_verson');
            delete_option('arm_infusionsoft_appname');
            delete_option('arm_infusionsoft_apikey');
        }

        public static function arm_infusionsoft_admin_notices() {
            global $pagenow, $arm_slugs, $arm_infusionsoft;    
            if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
                if (!$arm_infusionsoft->arm_infusionsoft_is_armember_support()) {
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - Infusionsoft plugin requires ARMember Plugin installed and active.', ARM_INFUSIONSOFT_TXTDOMAIN) . "</p></div>";
                } else if (!$arm_infusionsoft->arm_infusionsoft_is_armember_version_compatible()) {
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - Infusionsoft plugin requires ARMember plugin installed with version 2.0 or higher.', ARM_INFUSIONSOFT_TXTDOMAIN) . "</p></div>";
                }
            }
        }

        public static function arm_infusionsoft_set_css_js() {
            global $arm_infusionsoft;
            if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'opt_ins_options' && $arm_infusionsoft->arm_infusionsoft_is_armember_version_compatible()) {
                global $arm_infusionsoft_version;
                wp_register_script('arm_infusionsoft_admin_js', ARM_INFUSIONSOFT_JS_URL, 'arm_admin.js', array(), $arm_infusionsoft_version);
                wp_enqueue_script('arm_infusionsoft_admin_js');
                $arm_infusionsoft_messages = $arm_infusionsoft->arm_infusionsoft_messages();
                wp_localize_script('jquery', 'armInnfusionsoftMessage', $arm_infusionsoft_messages);
            }
        }

        public static function arm_infusionsoft_messages() {
            $armInfusionSoftMessages = array(
                'delOptInsConfirm' => __("Are you sure to delete configuration?", ARM_INFUSIONSOFT_TXTDOMAIN),
            );
            return $armInfusionSoftMessages;
        }

        public static function arm_infusionsoft_load_textdomain() {
            load_plugin_textdomain(ARM_INFUSIONSOFT_TXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }
		
		function arminfusion_get_remote_post_params($plugin_info = "") {
			global $wpdb;
	
			$action = "";
			$action = $plugin_info;
	
			if (!function_exists('get_plugins')) {
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}
			$plugin_list = get_plugins();
			$site_url = home_url();
			$plugins = array();
	
			$active_plugins = get_option('active_plugins');
	
			foreach ($plugin_list as $key => $plugin) {
				$is_active = in_array($key, $active_plugins);
	
				//filter for only armember ones, may get some others if using our naming convention
				if (strpos(strtolower($plugin["Title"]), "armemberinfusionsoft") !== false) {
					$name = substr($key, 0, strpos($key, "/"));
					$plugins[] = array("name" => $name, "version" => $plugin["Version"], "is_active" => $is_active);
				}
			}
			$plugins = json_encode($plugins);
	
			//get theme info
			$theme = wp_get_theme();
			$theme_name = $theme->get("Name");
			$theme_uri = $theme->get("ThemeURI");
			$theme_version = $theme->get("Version");
			$theme_author = $theme->get("Author");
			$theme_author_uri = $theme->get("AuthorURI");
	
			$im = is_multisite();
			$sortorder = get_option("armSortOrder");
	
			$post = array("wp" => get_bloginfo("version"), "php" => phpversion(), "mysql" => $wpdb->db_version(), "plugins" => $plugins, "tn" => $theme_name, "tu" => $theme_uri, "tv" => $theme_version, "ta" => $theme_author, "tau" => $theme_author_uri, "im" => $im, "sortorder" => $sortorder);
	
			return $post;
		}

    }

}
$arm_infusionsoft = new ARM_InfusionSoft();

if (file_exists(ARM_INFUSIONSOFT_CLASSES_DIR . '/class.arm_general_settings.php') && $arm_infusionsoft->arm_infusionsoft_is_armember_version_compatible()) {
    require_once(ARM_INFUSIONSOFT_CLASSES_DIR . '/class.arm_general_settings.php');
}


global $arminfusionsoft_api_url, $arminfusionsoft_plugin_slug, $wp_version;

$arminfusionsoft_api_url = $arm_infusionsoft->arminfusion_getapiurl();
$arminfusionsoft_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'arminfusion_check_for_plugin_update');

function arminfusion_check_for_plugin_update($checked_data) {
    global $arminfusionsoft_api_url, $arminfusionsoft_plugin_slug, $wp_version, $arm_infusionsoft_ver,$arm_infusionsoft;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $arminfusionsoft_plugin_slug,
        'version' => $arm_infusionsoft_ver,
        'other_variables' => $arm_infusionsoft->arminfusion_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMINFUSION-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($arminfusionsoft_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('arminfusion_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$arminfusionsoft_plugin_slug . '/' . $arminfusionsoft_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'arminfusion_plugin_api_call', 10, 3);

function arminfusion_plugin_api_call($def, $action, $args) {
    global $arminfusionsoft_plugin_slug, $arminfusionsoft_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $arminfusionsoft_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$arminfusionsoft_plugin_slug . '/' . $arminfusionsoft_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('arminfusion_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMINFUSION-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($arminfusionsoft_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', MEMBERSHIP_TXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', MEMBERSHIP_TXTDOMAIN), $request['body']);
    }

    return $res;
}