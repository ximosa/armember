<?php 
/*
  Plugin Name: ARMember - Online Worldpay payment gateway Addon
  Description: Extension for ARMember plugin to integration with Online Worldpay
  Version: 1.1
  Plugin URI: https://www.armemberplugin.com
  Author: Repute InfoSystems
  Author URI: https://www.armemberplugin.com
  Text Domain: ARMember-Worldpay
 */

define('ARM_WORLDPAY_DIR_NAME', 'armember-worldpay');
define('ARM_WORLDPAY_DIR', WP_PLUGIN_DIR . '/' . ARM_WORLDPAY_DIR_NAME);

if (is_ssl()) {
    define('ARM_WORLDPAY_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_WORLDPAY_DIR_NAME));
} else {
    define('ARM_WORLDPAY_URL', WP_PLUGIN_URL . '/' . ARM_WORLDPAY_DIR_NAME);
}

define('ARM_WORLDPAY_CORE_DIR', ARM_WORLDPAY_DIR . '/core');
define('ARM_WORLDPAY_CLASSES_DIR', ARM_WORLDPAY_CORE_DIR . '/classes');
define('ARM_WORLDPAY_VIEWS_DIR', ARM_WORLDPAY_CORE_DIR . '/views');

define('ARM_WORLDPAY_TXTDOMAIN', 'ARMember-Worldpay');
if(!defined('FS_METHOD')){
    define('FS_METHOD', 'direct');
}

global $arm_worldpay_version;
$arm_worldpay_version = '1.1';

global $armnew_worldpay_version;

global $ArmWorldpay;
$ArmWorldpay = new ARMemberWorldpay();


global $armworldpay_api_url, $armworldpay_plugin_slug, $wp_version;

if (file_exists(ARM_WORLDPAY_CLASSES_DIR . '/class.arm_online_worldpay.php')) {
    require_once ARM_WORLDPAY_CLASSES_DIR . '/class.arm_online_worldpay.php';
}

class ARMemberWorldpay {

    function __construct() {

        register_activation_hook(__FILE__, array('ARMemberWorldpay', 'arm_worldpay_installer'));

        register_activation_hook(__FILE__, array('ARMemberWorldpay', 'arm_worldpay_check_network_activation'));

        register_uninstall_hook(__FILE__, array('ARMemberWorldpay', 'arm_worldpay_uninstaller'));

        add_action('admin_notices', array($this, 'arm_worldpay_admin_notices'));

        add_action('plugins_loaded', array($this, 'arm_worldpay_load_textdomain'));

        add_action('admin_init', array(&$this, 'upgrade_data_worldpay'));
        
    }

    function upgrade_data_worldpay(){
        global $armnew_worldpay_version;
    
        if (!isset($armnew_worldpay_version) || $armnew_worldpay_version == "")
            $armnew_worldpay_version = get_option('arm_worldpay_version');

        if (version_compare($armnew_worldpay_version, '1.1', '<')) {
            $path = ARM_WORLDPAY_DIR . '/upgrade_latest_data_worldpay.php';
            include($path);
        }
    }
    
    public static function arm_worldpay_installer() {
        $arm_worldpay_db_version = get_option('arm_worldpay_version');
        if (empty($arm_worldpay_db_version) || $arm_worldpay_db_version == '') {
            global $wpdb, $arm_worldpay_version;
            update_option('arm_worldpay_version', $arm_worldpay_version);
        }
    }

    public static function arm_worldpay_uninstaller() {
        delete_option('arm_worldpay_version');
    }

    public static function arm_worldpay_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }

    function arm_worldpay_admin_notices() {
        global $pagenow, $arm_slugs;    
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if (!$this->is_armember_support()) {
                echo "<div class='updated updated_notices'><p>" . esc_html__('ARMember - Online Worldpay payment gateway plugin requires ARMember Plugin installed and active.', ARM_WORLDPAY_TXTDOMAIN) . "</p></div>";
            }else if (!$this->arm_armember_version_check()) {
                echo "<div class='updated updated_notices'><p>" . esc_html__('ARMember - Online Worldpay payment gateway plugin requires ARMember plugin installed with version 3.2.1 or higher.', ARM_WORLDPAY_TXTDOMAIN) . "</p></div>";
            }
       }
    }
	
	function armworldpay_getapiurl() {
		$api_url = 'https://www.arpluginshop.com/';
		return $api_url;
	}
	
	function armworldpay_get_remote_post_params($plugin_info = "") {
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
			if (strpos(strtolower($plugin["Title"]), "armember-worldpay") !== false) {
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
			
    function is_armember_support() {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        return is_plugin_active('armember/armember.php');
    }

    function arm_armember_version_check() {
        if (!version_compare($this->arm_get_armember_version(), '3.2.1', '>=') || !$this->is_armember_support()) :
            return false;
        else :
            return true;
        endif;
    }

    function arm_get_armember_version() {

        $arm_db_version = get_option('arm_version');

        return (isset($arm_db_version)) ? $arm_db_version : 0;
    }

    function arm_worldpay_load_textdomain() {
        load_plugin_textdomain(ARM_WORLDPAY_TXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

}

global $armworldpay_api_url, $armworldpay_plugin_slug;

$armworldpay_api_url = $ArmWorldpay->armworldpay_getapiurl();
$armworldpay_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armworldpay_check_for_plugin_update');

function armworldpay_check_for_plugin_update($checked_data) {
    global $armworldpay_api_url, $armworldpay_plugin_slug, $wp_version, $arm_worldpay_version,$ArmWorldpay;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armworldpay_plugin_slug,
        'version' => $arm_worldpay_version,
        'other_variables' => $ArmWorldpay->armworldpay_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMWORLDPAY-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armworldpay_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200)){
        if(isset($raw_response['body']) && $raw_response['body'] !=''){
            $response = unserialize($raw_response['body']);
        }
    }

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armworldpay_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armworldpay_plugin_slug . '/' . $armworldpay_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armworldpay_plugin_api_call', 10, 3);

function armworldpay_plugin_api_call($def, $action, $args) {
    global $armworldpay_plugin_slug, $armworldpay_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armworldpay_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armworldpay_plugin_slug . '/' . $armworldpay_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armworldpay_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMWORLDPAY-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armworldpay_api_url, $request_string);

    if (is_wp_error($request)) {
        $res_msg=sprintf(esc_html__('An Unexpected HTTP Error occurred during the API request.%s Try again%s',
            ARM_WORLDPAY_TXTDOMAIN),'</p> <p><a href="?" onclick="document.location.reload(); return false;">','</a>');
        $res = new WP_Error('plugins_api_failed',$res_msg , $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', esc_html__('An unknown error occurred', ARM_WORLDPAY_TXTDOMAIN), $request['body']);
    }

    return $res;
}