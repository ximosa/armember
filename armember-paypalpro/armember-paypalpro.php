<?php 
/*
  Plugin Name: ARMember - Paypal Pro Addon
  Description: Extension for ARMember plugin to integration with Paypal Pro
  Version: 1.9
  Plugin URI: https://www.armemberplugin.com
  Author: Repute InfoSystems
  Author URI: https://www.armemberplugin.com
 */

define('ARM_PAYPALPRO_DIR_NAME', 'armember-paypalpro');
define('ARM_PAYPALPRO_DIR', WP_PLUGIN_DIR . '/' . ARM_PAYPALPRO_DIR_NAME);

if (is_ssl()) {
    define('ARM_PAYPALPRO_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_PAYPALPRO_DIR_NAME));
} else {
    define('ARM_PAYPALPRO_URL', WP_PLUGIN_URL . '/' . ARM_PAYPALPRO_DIR_NAME);
}

define('ARM_PAYPALPRO_CORE_DIR', ARM_PAYPALPRO_DIR . '/core');
define('ARM_PAYPALPRO_CLASSES_DIR', ARM_PAYPALPRO_CORE_DIR . '/classes');
define('ARM_PAYPALPRO_VIEWS_DIR', ARM_PAYPALPRO_CORE_DIR . '/views');

define('ARM_PAYPALPRO_TXTDOMAIN', 'ARMember-PaypalPro');
@define('FS_METHOD', 'direct');

global $arm_paypalpro_version;
$arm_paypalpro_version = '1.9';

global $armnew_paypalpro_version;

global $ArmPaypalPro;
$ArmPaypalPro = new ARMemberPaypalPro();


global $armpaypalpro_api_url, $armpaypalpro_plugin_slug, $wp_version;

if (file_exists(ARM_PAYPALPRO_CLASSES_DIR . '/class.arm_paypal_pro.php')) {
    require_once ARM_PAYPALPRO_CLASSES_DIR . '/class.arm_paypal_pro.php';
}

class ARMemberPaypalPro {

    function __construct() {

        register_activation_hook(__FILE__, array('ARMemberPaypalPro', 'arm_paypalpro_installer'));

        register_activation_hook(__FILE__, array('ARMemberPaypalPro', 'arm_paypalpro_check_network_activation'));

        register_uninstall_hook(__FILE__, array('ARMemberPaypalPro', 'arm_paypalpro_uninstaller'));

        add_action('admin_notices', array(&$this, 'arm_paypalpro_admin_notices'));

        add_action('plugins_loaded', array(&$this, 'arm_paypalpro_load_textdomain'));
        
        add_action('admin_init', array(&$this, 'upgrade_data_paypalpro'));
        
    }
    
    function upgrade_data_paypalpro(){
        global $armnew_paypalpro_version;
	
        if (!isset($armnew_paypalpro_version) || $armnew_paypalpro_version == "")
            $armnew_paypalpro_version = get_option('arm_paypalpro_version');

        if (version_compare($armnew_paypalpro_version, '1.9', '<')) {
            $path = ARM_PAYPALPRO_DIR . '/upgrade_latest_data_paypalpro.php';
            include($path);
        }
    }

    public static function arm_paypalpro_installer() {
        $arm_paypalpro_db_version = get_option('arm_paypalpro_version');
        if (empty($arm_paypalpro_db_version) || $arm_paypalpro_db_version == '') {
            global $wpdb, $arm_paypalpro_version;
            update_option('arm_paypalpro_version', $arm_paypalpro_version);
        }
    }

    public static function arm_paypalpro_uninstaller() {
        delete_option('arm_paypalpro_version');
    }

    public static function arm_paypalpro_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }

    function arm_paypalpro_admin_notices() {
        
        global $pagenow, $arm_slugs;    
       if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
        if (!$this->is_armember_support()) {
            echo "<div class='updated updated_notices'><p>" . __('ARMember - Paypal Pro plugin requires ARMember Plugin installed and active.', ARM_PAYPALPRO_TXTDOMAIN) . "</p></div>";
        } else if (!$this->arm_armember_version_check()) {
            echo "<div class='updated updated_notices'><p>" . __('ARMember - Paypal Pro plugin requires ARMember plugin installed with version 3.0 or higher.', ARM_PAYPALPRO_TXTDOMAIN) . "</p></div>";
        }
       }
    }
	
	function armpaypalpro_getapiurl() {
			$api_url = 'https://www.arpluginshop.com/';
			return $api_url;
		}
	
	function armpaypalpro_get_remote_post_params($plugin_info = "") {
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
				if (strpos(strtolower($plugin["Title"]), "armember-paypalpro") !== false) {
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
        if (!version_compare($this->arm_get_armember_version(), '3.0', '>=') || !$this->is_armember_support()) :
            return false;
        else :
            return true;
        endif;
    }

    function arm_get_armember_version() {

        $arm_db_version = get_option('arm_version');

        return (isset($arm_db_version)) ? $arm_db_version : 0;
    }

    function arm_paypalpro_load_textdomain() {
        load_plugin_textdomain(ARM_PAYPALPRO_TXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

}

global $armpaypalpro_api_url, $armpaypalpro_plugin_slug;

$armpaypalpro_api_url = $ArmPaypalPro->armpaypalpro_getapiurl();
$armpaypalpro_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armpaypalpro_check_for_plugin_update');

function armpaypalpro_check_for_plugin_update($checked_data) {
    global $armpaypalpro_api_url, $armpaypalpro_plugin_slug, $wp_version, $arm_paypalpro_version,$ArmPaypalPro;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armpaypalpro_plugin_slug,
        'version' => $arm_paypalpro_version,
        'other_variables' => $ArmPaypalPro->armpaypalpro_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMPAYPALPRO-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armpaypalpro_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armpaypalpro_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armpaypalpro_plugin_slug . '/' . $armpaypalpro_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armpaypalpro_plugin_api_call', 10, 3);

function armpaypalpro_plugin_api_call($def, $action, $args) {
    global $armpaypalpro_plugin_slug, $armpaypalpro_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armpaypalpro_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armpaypalpro_plugin_slug . '/' . $armpaypalpro_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armpaypalpro_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMPAYPALPRO-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armpaypalpro_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', ARM_PAYPALPRO_TXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', ARM_PAYPALPRO_TXTDOMAIN), $request['body']);
    }

    return $res;
}