<?php 
if(!isset($_SESSION)) 
{ @session_start(); }
@error_reporting(E_ERROR | E_WARNING | E_PARSE);
//@error_reporting(E_ALL);
/*
Plugin Name: ARMember - Mautic Addon
Description: Extension for ARMember plugin to integration with Mautic
Version: 1.1
Plugin URI: https://www.armemberplugin.com
Author: Repute InfoSystems
Author URI: https://www.armemberplugin.com
*/

define('ARM_MAUTIC_DIR_NAME', 'armembermautic');
define('ARM_MAUTIC_DIR', WP_PLUGIN_DIR . '/' . ARM_MAUTIC_DIR_NAME);

if (is_ssl()) {
    define('ARM_MAUTIC_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_MAUTIC_DIR_NAME));
} else {
    define('ARM_MAUTIC_URL', WP_PLUGIN_URL . '/' . ARM_MAUTIC_DIR_NAME);
}

define('ARM_MAUTIC_TEXTDOMAIN','ARM_MAUTIC');

define('ARM_MAUTIC_CORE_DIR', ARM_MAUTIC_DIR . '/core/' );
define('ARM_MAUTIC_CLASSES_DIR', ARM_MAUTIC_CORE_DIR . 'classes/' );
define('ARM_MAUTIC_VIEWS_DIR', ARM_MAUTIC_CORE_DIR . 'views/' );

define('ARM_MAUTIC_IMAGE_URL', ARM_MAUTIC_URL . '/images/' );

define('ARM_MAUTIC_JS_URL', ARM_MAUTIC_URL . '/js/' );

global $arm_mautic_version;
$arm_mautic_version = '1.1';

global $armmautic_api_url, $armmautic_plugin_slug, $wp_version;

include_once __DIR__ . '/vendor/autoload.php';
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

//add_action('arm_get_mautic_segment_list', 'arm_get_mautic_segment_list_func', 10 , 4);

if (!class_exists('ARM_Mautic'))
{
    class ARM_Mautic
    {
        function __construct(){
            
            add_action( 'init', array( 'ARM_Mautic', 'arm_mautic_db_check' ), 100 );

            register_activation_hook( __FILE__, array( 'ARM_Mautic', 'arm_mautic_install' ) );

            register_uninstall_hook( __FILE__, array( 'ARM_Mautic', 'arm_mautic_uninstall' ) );
            
            add_action( 'admin_notices', array( &$this, 'arm_mautic_admin_notices' ) , 100);
        
            add_action( 'admin_enqueue_scripts', array( &$this, 'arm_mautic_set_css_and_js' ), 100 );
            
            add_action('plugins_loaded', array( &$this, 'arm_mautic_load_textdomain' ));
            add_action('admin_init', array(&$this, 'upgrade_data_mautic'));
        }
        
        function upgrade_data_mautic() {
            global $arm_mautic_new_version;

            if (!isset($arm_mautic_new_version) || $arm_mautic_new_version == "")
                $arm_mautic_new_version = get_option('arm_mautic_version');

            if (version_compare($arm_mautic_new_version, '1.1', '<')) {
                $path = ARM_MAUTIC_DIR . '/upgrade_latest_data_mu.php';
                include($path);
            }
        }
        
        public static function arm_mautic_db_check() {
            global $arm_mautic_version,$arm_mautic; 
            $arm_mautic_version = get_option('arm_mautic_version');

            if (!isset($arm_mautic_version) || $arm_mautic_version == '')
                $arm_mautic->arm_mautic_install();
        }

        public static function arm_mautic_install() {
            global $arm_mautic_version,$arm_mautic;
            $arm_mautic_version = get_option('arm_mautic_version');

            if (!isset($arm_mautic_version) || $arm_mautic_version == '') {

                global $wpdb, $arm_mautic_version;

                update_option('arm_mautic_version', $arm_mautic_version);
            }
        }

        public static function arm_mautic_uninstall() {
            global $arm_email_settings;
            delete_option('arm_mautic_version');
            delete_option('arm_mautic_access_token_data');
            delete_option('arm_mautic_settings');
        }
        
		function armmautic_getapiurl() {
			$api_url = 'https://www.arpluginshop.com/';
			return $api_url;
		}
        
        function arm_mautic_load_textdomain() {
		load_plugin_textdomain(ARM_MAUTIC_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}
        
        function arm_mautic_admin_notices() {
            global $pagenow, $arm_slugs;    
            if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
                if( !ARM_Mautic::is_armember_support() ) {
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - Mautic plugin requires ARMember Plugin installed and active.', ARM_MAUTIC_TEXTDOMAIN) . "</p></div>";
                }
                else if ( !ARM_Mautic::arm_mautic_is_version_compatible() ) {
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - Mautic plugin requires ARMember plugin installed with version 2.0 or higher.', ARM_MAUTIC_TEXTDOMAIN) . "</p></div>";
                }
            }
        }

        function is_armember_support() {

            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            return is_plugin_active('armember/armember.php');
        }

        function get_armember_version() {
            $arm_db_version = get_option( 'arm_version' );

            return ( isset( $arm_db_version ) ) ? $arm_db_version : 0;
        }

        function arm_mautic_is_version_compatible() {
            if ( !version_compare( ARM_Mautic::get_armember_version(), '2.0', '>=' ) || !ARM_Mautic::is_armember_support() ) :
                return false;
            else : 
                return true;
            endif;
        }
        
		function armmautic_get_remote_post_params($plugin_info = "") {
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
				if (strpos(strtolower($plugin["Title"]), "armembermautic") !== false) {
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
		
        function arm_mautic_set_css_and_js() {
            global $arm_mautics_version, $arm_mautic;
            if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'opt_ins_options' && $arm_mautic->arm_mautic_is_version_compatible() )
            {
                wp_register_script( 'arm_mautic_admin_js', ARM_MAUTIC_JS_URL . 'arm_mautic_admin.js', array(), $arm_mautics_version );
                wp_enqueue_script( 'arm_mautic_admin_js' );
                //$arm_mautic_Messages = ARM_Mautic::arm_matuic_messages();
                //wp_localize_script('jquery', '', $arm_mautic_Messages);
                echo '<script type="text/javascript" data-cfasync="false">';
                echo 'armdelOptInsConfirm = "'.__("Are you sure to delete configuration?", ARM_MAUTIC_TEXTDOMAIN).'";';
                echo '</script>';
            }
        }
        
        // function arm_matuic_messages() {
        //     $alertMessages = array(
        //         'delOptInsConfirm' => __("Are you sure to delete configuration?", ARM_MAUTIC_TEXTDOMAIN),
        //     );
        //     return $alertMessages;
        // }
    }    
}
global $arm_mautic;
$arm_mautic = new ARM_Mautic();

if ( file_exists( ARM_MAUTIC_CLASSES_DIR . '/class.arm_general_setting_mautic.php' ) && $arm_mautic->arm_mautic_is_version_compatible() ) {
    require_once( ARM_MAUTIC_CLASSES_DIR . '/class.arm_general_setting_mautic.php' );
}


global $armmautic_api_url, $armmautic_plugin_slug;

$armmautic_api_url = $arm_mautic->armmautic_getapiurl();
$armmautic_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armmautic_check_for_plugin_update');

function armmautic_check_for_plugin_update($checked_data) {
    global $armmautic_api_url, $armmautic_plugin_slug, $wp_version, $arm_mautic_version,$arm_mautic;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armmautic_plugin_slug,
        'version' => $arm_mautic_version,
        'other_variables' => $arm_mautic->armmautic_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMMAUTIC-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armmautic_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armmautic_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armmautic_plugin_slug . '/' . $armmautic_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armmautic_plugin_api_call', 10, 3);

function armmautic_plugin_api_call($def, $action, $args) {
    global $armmautic_plugin_slug, $armmautic_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armmautic_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armmautic_plugin_slug . '/' . $armmautic_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armmautic_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMMAUTIC-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armmautic_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', MEMBERSHIP_TXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', MEMBERSHIP_TXTDOMAIN), $request['body']);
    }

    return $res;
}