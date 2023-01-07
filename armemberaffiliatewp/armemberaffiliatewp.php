<?php 
/*
Plugin Name: ARMember - AffiliateWP Addon
Description: Extension for ARMember plugin to integration with AffiliateWP
Version: 1.2
Plugin URI: https://www.armemberplugin.com
Author: Repute InfoSystems
Author URI: https://www.armemberplugin.com
*/

define('ARM_AFFILIATEWP_DIR_NAME', 'armemberaffiliatewp');
define('ARM_AFFILIATEWP_DIR', WP_PLUGIN_DIR . '/' . ARM_AFFILIATEWP_DIR_NAME);

if (is_ssl()) {
    define('ARM_AFFILIATEWP_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_AFFILIATEWP_DIR_NAME));
} else {
    define('ARM_AFFILIATEWP_URL', WP_PLUGIN_URL . '/' . ARM_AFFILIATEWP_DIR_NAME);
}

define('ARM_AFFILIATEWP_TEXTDOMAIN','ARM_AFFILIATEWP');

global $arm_affiliatewp_version;
$arm_affiliatewp_version = '1.2';

global $armnew_affiliatewp_version;

global $armaffiliatewp_api_url, $armaffiliatewp_plugin_slug, $wp_version;

if (!class_exists('ARM_Affiliatewp'))
{
    class ARM_Affiliatewp
    {
        var $affiliatewp_dir;
        var $affiliate_wp_dir;
        function __construct(){
            $this->affiliatewp_dir = "AffiliateWP-master";
            $this->affiliate_wp_dir = "affiliate-wp";
            
            add_action('init', array(&$this, 'arm_affiliatewp_db_check'));

            register_activation_hook(__FILE__, array('ARM_Affiliatewp', 'install'));

            register_uninstall_hook(__FILE__, array('ARM_Affiliatewp', 'uninstall'));
            
            add_action('admin_notices', array(&$this, 'arm_affiliate_wp_admin_notices'));

            add_action('plugins_loaded', array(&$this, 'arm_affiliatewp_load_textdomain'));
            
            add_action('plugins_loaded', array(&$this, 'create_referral'));
            
            add_action('admin_init', array(&$this, 'upgrade_data_affilitewp'));
        }
        
        function upgrade_data_affilitewp(){
            global $armnew_affiliatewp_version;
	
                if (!isset($armnew_affiliatewp_version) || $armnew_affiliatewp_version == "")
                        $armnew_affiliatewp_version = get_option('arm_affiliatewp_version');

                if (version_compare($armnew_affiliatewp_version, '1.2', '<')) {
                        $path = ARM_AFFILIATEWP_DIR . '/upgrade_latest_data_affiliatewp.php';
                        include($path);
                }
        }

        function arm_affiliatewp_load_textdomain() {
            load_plugin_textdomain(ARM_AFFILIATEWP_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        public static function arm_affiliatewp_db_check() {
            global $arm_affiliatewp; 
            $arm_affiliatewp_version = get_option('arm_affiliatewp_version');

            if (!isset($arm_affiliatewp_version) || $arm_affiliatewp_version == '')
                $arm_affiliatewp->install();
        }
		
		function armaffiliatewp_getapiurl() {
            $api_url = 'https://www.arpluginshop.com/';
			return $api_url;
		}

        public static function install() {
            global $arm_affiliatewp;
            $arm_affiliatewp_version = get_option('arm_affiliatewp_version');

            if (!isset($arm_affiliatewp_version) || $arm_affiliatewp_version == '') {

                global $wpdb, $arm_affiliatewp_version;

                update_option('arm_affiliatewp_version', $arm_affiliatewp_version);
            }
        }

        public static function uninstall() {
            delete_option('arm_affiliatewp_version');
        }
        
        function armaffiliatewp_get_remote_post_params($plugin_info = "") {
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
				if (strpos(strtolower($plugin["Title"]), "armemberaffiliatewp") !== false) {
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
		
        function arm_affiliate_wp_admin_notices(){
            global $pagenow, $arm_slugs;    
            if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
                if(!$this->is_armember_support())
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - AffiliateWP plugin requires ARMember Plugin installed and active.', ARM_AFFILIATEWP_TEXTDOMAIN) . "</p></div>";

                else if (!$this->is_version_compatible())
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - AffiliateWP plugin requires ARMember plugin installed with version 1.5 or higher.', ARM_AFFILIATEWP_TEXTDOMAIN) . "</p></div>";
            }
        }

        function is_armember_support() {

            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            return is_plugin_active('armember/armember.php');
        }

        function get_armember_version(){
            $arm_db_version = get_option('arm_version');

            return (isset($arm_db_version)) ? $arm_db_version : 0;
        }

        function is_version_compatible(){
            if (!version_compare($this->get_armember_version(), '1.5', '>=') || !$this->is_armember_support()) :
                return false;
            else : 
                return true;
            endif;
        }
        
        function create_referral()
        {
            if(( $this->is_affiliatewp_active() || $this->is_affiliate_wp_active() ) && $this->is_version_compatible() )
            {
                if( file_exists(AFFILIATEWP_PLUGIN_DIR . 'includes/integrations/class-base.php') )
                {
                    require_once AFFILIATEWP_PLUGIN_DIR . 'includes/integrations/class-base.php';
                    require_once  __DIR__ . '/arm_compatible_affiliatewp.php'; 
                }
                else 
                {
                    add_action('admin_notices', array(&$this, 'arm_affiliatewp_admin_notices'));
                }
            }
            else
            {
                add_action('admin_notices', array(&$this, 'arm_affiliatewp_admin_notices'));
            }
        }

        function is_affiliatewp_active() {

            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            return is_plugin_active($this->affiliatewp_dir . '/affiliate-wp.php');
        }
        
        function is_affiliate_wp_active() {

            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            return is_plugin_active($this->affiliate_wp_dir . '/affiliate-wp.php');
        }
        
        public function arm_affiliatewp_admin_notices() {
            global $pagenow, $arm_slugs;    
            if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs)))
            {
                if( file_exists(WP_PLUGIN_DIR . '/' . $this->affiliatewp_dir . '/affiliate-wp.php') || file_exists(WP_PLUGIN_DIR . '/' . $this->affiliate_wp_dir . '/affiliate-wp.php'))
                {
                    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                    if( file_exists(WP_PLUGIN_DIR . '/' . $this->affiliatewp_dir . '/affiliate-wp.php') ) {
                        $affwp_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->affiliatewp_dir . '/affiliate-wp.php', false, false );
                    } else {
                        $affwp_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->affiliate_wp_dir . '/affiliate-wp.php', false, false );
                    }

                    if ( ! class_exists( 'Affiliate_WP' ) ) {    
                        echo "<div class='updated updated_notices'><p>" . __('You must install and activate AffiliateWP to use ARMember - AffiliateWP Addon.', ARM_AFFILIATEWP_TEXTDOMAIN) . "</p></div>";
                    }

                    if ( $affwp_plugin_data['Version'] < '1.9.4' ) {
                        echo "<div class='updated updated_notices'><p>" . __('ARmember - AffiliateWP Add-on requires AffiliateWP 1.9.4 or higher.  Please update AffiliateWP.', ARM_AFFILIATEWP_TEXTDOMAIN) . "</p></div>";
                    }
                }
                else
                {    
                   echo "<div class='updated updated_notices'><p>" . __('You must install and activate AffiliateWP to use ARMember - AffiliateWP Addon.', ARM_AFFILIATEWP_TEXTDOMAIN) . "</p></div>";
                }
            }
        }
        
    }    
}
global $arm_affiliatewp;
$arm_affiliatewp = new ARM_Affiliatewp();


global $armaffiliatewp_api_url, $armaffiliatewp_plugin_slug;

$armaffiliatewp_api_url = $arm_affiliatewp->armaffiliatewp_getapiurl();
$armaffiliatewp_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armaffiliatewp_check_for_plugin_update');

function armaffiliatewp_check_for_plugin_update($checked_data) {
    global $armaffiliatewp_api_url, $armaffiliatewp_plugin_slug, $wp_version, $arm_affiliatewp_version,$arm_affiliatewp;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armaffiliatewp_plugin_slug,
        'version' => $arm_affiliatewp_version,
        'other_variables' => $arm_affiliatewp->armaffiliatewp_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMAFFILIATEWP-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armaffiliatewp_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armaffiliatewp_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armaffiliatewp_plugin_slug . '/' . $armaffiliatewp_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armaffiliatewp_plugin_api_call', 10, 3);

function armaffiliatewp_plugin_api_call($def, $action, $args) {
    global $armaffiliatewp_plugin_slug, $armaffiliatewp_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armaffiliatewp_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armaffiliatewp_plugin_slug . '/' . $armaffiliatewp_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armaffiliatewp_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMAFFILIATEWP-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armaffiliatewp_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', MEMBERSHIP_TXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', MEMBERSHIP_TXTDOMAIN), $request['body']);
    }

    return $res;
}