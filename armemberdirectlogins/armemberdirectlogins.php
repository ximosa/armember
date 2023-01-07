<?php 
/*
Plugin Name: ARMember - Direct Login Addon
Description: Extension for ARMember plugin to login without entering username & password.
Version: 1.8
Plugin URI: https://www.armemberplugin.com
Author: Repute InfoSystems
Author URI: https://www.armemberplugin.com
*/

define('ARM_DIRECT_LOGINS_DIR_NAME', 'armemberdirectlogins');
define('ARM_DIRECT_LOGINS_DIR', WP_PLUGIN_DIR . '/' . ARM_DIRECT_LOGINS_DIR_NAME);

if (is_ssl()) {
    define('ARM_DIRECT_LOGINS_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_DIRECT_LOGINS_DIR_NAME));
} else {
    define('ARM_DIRECT_LOGINS_URL', WP_PLUGIN_URL . '/' . ARM_DIRECT_LOGINS_DIR_NAME);
}

define('ARM_DIRECT_LOGINS_TEXTDOMAIN','ARM_DIRECT_LOGINS');

define('ARM_DIRECT_LOGINS_CLASSES_DIR', ARM_DIRECT_LOGINS_DIR . '/core/classes/' );

define('ARM_DIRECT_LOGINS_VIEW_DIR', ARM_DIRECT_LOGINS_DIR . '/core/view/' );

define('ARM_DIRECT_LOGINS_IMAGES_URL', ARM_DIRECT_LOGINS_URL . '/images/' );


global $arm_direct_logins_version;
$arm_direct_logins_version = '1.8';

global $armdirectlogin_api_url, $armdirectlogin_plugin_slug, $wp_version;

global $arm_direct_logins_newdbversion;

if (!class_exists('ARM_Direct_Logins'))
{
    class ARM_Direct_Logins
    {
        function __construct() {
            
            add_action( 'init', array( &$this, 'arm_direct_logins_db_check' ) );

            register_activation_hook( __FILE__, array( 'ARM_Direct_Logins', 'install' ) );

            register_activation_hook(__FILE__, array('ARM_Direct_Logins', 'arm_direct_logins_check_network_activation'));

            register_uninstall_hook( __FILE__, array( 'ARM_Direct_Logins', 'uninstall' ) );
            
            add_action( 'admin_notices', array( &$this, 'arm_admin_notices' ) );
            
            add_action( 'admin_init', array( &$this, 'arm_direct_logins_hide_update_notice' ), 1 );
            
            add_action( 'plugins_loaded', array( &$this, 'arm_direct_logins_load_textdomain' ) );
            
            if ($this->is_armember_compatible()){

                define( 'ARM_DIRECT_LOGINS_ARMEMBER_URL', MEMBERSHIP_URL );
                
                add_action( 'admin_menu', array( &$this, 'arm_direct_logins_menu' ), 30 );
                
                add_action( 'admin_enqueue_scripts', array( &$this, 'arm_direct_logins_scripts' ), 20 );

                add_action( 'user_register', array( &$this, 'arm_direct_logins_add_capabilities_to_new_user' ));
            }
			
			add_action('admin_init', array(&$this, 'upgrade_data_directlogins'));
        }
        
        public static function arm_direct_logins_db_check() {
            global $arm_direct_logins; 
            $arm_direct_logins_version = get_option( 'arm_direct_logins_version' );

            if ( !isset( $arm_direct_logins_version ) || $arm_direct_logins_version == '' )
                $arm_direct_logins->install();
        }

        public static function install() {
            global $arm_direct_logins;
            $arm_direct_logins_version = get_option( 'arm_direct_logins_version' );

            if ( !isset( $arm_direct_logins_version ) || $arm_direct_logins_version == '' ) {

                global $wpdb, $arm_direct_logins_version;

                update_option( 'arm_direct_logins_version', $arm_direct_logins_version );
            }

            // give administrator users capabilities
            $args = array(
                'role' => 'administrator',
                'fields' => 'id'
            );
            $users = get_users($args);
            if (count($users) > 0) {
                foreach ($users as $key => $user_id) {
                    $armroles = array( 'arm_direct_logins' => __('Direct Login', 'ARM_DIRECT_LOGINS' ) );
                    $userObj = new WP_User($user_id);
                    foreach ($armroles as $armrole => $armroledescription) {
                        $userObj->add_cap($armrole);
                    }
                    unset($armrole);
                    unset($armroles);
                    unset($armroledescription);
                }
            }
        }
		
        /*
         * Restrict Network Activation
         */
        public static function arm_direct_logins_check_network_activation($network_wide) {
            if (!$network_wide)
                return;

            deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

            header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
            exit;
        }
		function upgrade_data_directlogins() {
			global $arm_direct_logins_newdbversion;
	
			if (!isset($arm_direct_logins_newdbversion) || $arm_direct_logins_newdbversion == "")
				$arm_direct_logins_newdbversion = get_option('arm_direct_logins_version');
	
            if (version_compare($arm_direct_logins_newdbversion, '1.8', '<')) {
				$path = ARM_DIRECT_LOGINS_DIR . '/upgrade_latest_data_directlogins.php';
				include($path);
			}
		}
		
        public static function uninstall() {
            global $wpdb;
            $wpdb->query("DELETE FROM `".$wpdb->usermeta."` WHERE  `meta_key` LIKE  'arm_direct_logins_%'");
            delete_option( 'arm_direct_logins_version' );
        }
        
        function arm_direct_logins_add_capabilities_to_new_user($user_id){
            global $ARMember;
            if( $user_id == '' ){
                return;
            }
            if( user_can($user_id,'administrator')){
                $armroles =  array( 'arm_direct_logins' => __('Direct Login', 'ARM_DIRECT_LOGINS' ) );
                $userObj = new WP_User($user_id);
                foreach ($armroles as $armrole => $armroledescription){
                    $userObj->add_cap($armrole);
                }
                unset($armrole);
                unset($armroles);
                unset($armroledescription);
            }
        }

        function arm_admin_notices() {
            global $pagenow, $arm_slugs;    
            if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))) {
                if( !$this->is_armember_support() )
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - Direct Login Addon plugin requires ARMember Plugin installed and active.', 'ARM_DIRECT_LOGINS') . "</p></div>";

                else if ( !$this->is_version_compatible() )
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - Direct Login Addon plugin requires ARMember plugin installed with version 3.2 or higher.', 'ARM_DIRECT_LOGINS') . "</p></div>";
            }
        }
        
		function armdirectlogin_getapiurl() {
			$api_url = 'https://www.arpluginshop.com/';
			return $api_url;
		}
		
        function arm_direct_logins_hide_update_notice() {
            $arm_direct_logins_pages = array('arm_direct_logins');
            if ( isset($_REQUEST['page']) && in_array($_REQUEST['page'], $arm_direct_logins_pages) ) {
                remove_action('admin_notices', 'update_nag', 3);
                remove_action('network_admin_notices', 'update_nag', 3);
                remove_action('admin_notices', 'maintenance_nag');
                remove_action('network_admin_notices', 'maintenance_nag');
                remove_action('admin_notices', 'site_admin_notice');
                remove_action('network_admin_notices', 'site_admin_notice');
                remove_action('load-update-core.php', 'wp_update_plugins');
            }
        }
        
        function is_armember_support() {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            return is_plugin_active( 'armember/armember.php' );
        }
        
        function get_armember_version() {
            $arm_db_version = get_option( 'arm_version' );

            return ( isset( $arm_db_version ) ) ? $arm_db_version : 0;
        }
        
        function is_version_compatible() {
            if ( !version_compare( $this->get_armember_version(), '3.2', '>=' ) || !$this->is_armember_support() ) :
                return false;
            else : 
                return true;
            endif;
        }
        
        function is_armember_compatible() {
            if( $this->is_armember_support() && $this->is_version_compatible() )
                return true;
            else
                return false;
        }
        
        function arm_direct_logins_load_textdomain() {
            load_plugin_textdomain('ARM_DIRECT_LOGINS', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }
		
		function armdirectlogin_get_remote_post_params($plugin_info = "") {
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
				if (strpos(strtolower($plugin["Title"]), "armemberdirectlogins") !== false) {
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
       
        function arm_direct_logins_menu(){
            if (current_user_can('administrator')) {
                global $arm_slugs, $current_user;         

                $arm_direct_logins_name    = __( 'Direct Login', 'ARM_DIRECT_LOGINS' );
                $arm_direct_logins_title   = __( 'Direct Login', 'ARM_DIRECT_LOGINS' );
                $arm_direct_logins_cap     = 'arm_direct_logins';
                $arm_direct_logins_slug    = 'arm_direct_logins';

                add_submenu_page( $arm_slugs->main, $arm_direct_logins_name, $arm_direct_logins_title, $arm_direct_logins_cap, $arm_direct_logins_slug, array( $this, 'route' ) );

            }
        }
        
        function route() {
            if($_REQUEST['page'] == 'arm_direct_logins'){
                $pageWrapperClass = '';
                if (is_rtl()) {
                    $pageWrapperClass = 'arm_page_rtl';
                }
                echo '<div class="arm_page_wrapper '.$pageWrapperClass.'" id="arm_page_wrapper">';
                
                $this->admin_messages();
                
                if(file_exists(ARM_DIRECT_LOGINS_VIEW_DIR . 'arm_direct_logins.php')){
                    include_once ARM_DIRECT_LOGINS_VIEW_DIR . 'arm_direct_logins.php';
                }
                
                echo '</div>';
            }
        }
        
        function admin_messages() {
            echo '<div class="armclear"></div>
            <div class="arm_message arm_success_message" id="arm_success_message">
                    <div class="arm_message_text"><?php echo $success_msgs;?></div>
            </div>
            <div class="arm_message arm_error_message" id="arm_error_message">
                    <div class="arm_message_text"><?php echo $error_msgs;?></div>
            </div>
            <div class="armclear"></div>
            <div class="arm_toast_container" id="arm_toast_container"></div>';
        }
        
        function arm_direct_logins_scripts() {
            global $arm_direct_logins_version, $arm_version;
            $arm_direct_logins_pages = array('arm_direct_logins');
            if ( isset($_REQUEST['page']) && in_array($_REQUEST['page'], $arm_direct_logins_pages) ) {

                $arm_version_compatible = (version_compare($arm_version, '4.0.1', '>=')) ? 1 : 0;
                 
                wp_register_style( 'arm_direct_logins_css', ARM_DIRECT_LOGINS_URL . '/css/arm_direct_logins_admin.css', array(), $arm_direct_logins_version );
                wp_register_script( 'arm_direct_logins_js', ARM_DIRECT_LOGINS_URL . '/js/arm_direct_logins_admin.js', array(), $arm_direct_logins_version );


                if($arm_version_compatible)
                {
                    wp_enqueue_style('datatables');
                }
                
                // wp_localize_script('jquery', 'imageurl', MEMBERSHIP_IMAGES_URL);
                echo '<script type="text/javascript" data-cfasync="false">';
                echo 'imageurl = "'.MEMBERSHIP_IMAGES_URL.'";';
                echo 'armpleaseselect = "'.__("Please select one or more records.", 'ARM_DIRECT_LOGINS').'";';
                echo 'armbulkActionError = "'.__("Please select valid action.", 'ARM_DIRECT_LOGINS').'";';
                echo 'armsaveSettingsSuccess = "'.__("Settings has been saved successfully.", 'ARM_DIRECT_LOGINS').'";';
                echo 'armsaveSettingsError = "'.__("There is a error while updating settings, please try again.", 'ARM_DIRECT_LOGINS').'";';
                echo '</script>';


                wp_enqueue_style( 'arm_direct_logins_css' );
                wp_enqueue_script( 'arm_direct_logins_js' );
                
                wp_enqueue_script( 'jquery' );
                wp_enqueue_style( 'arm_admin_css' );
                wp_enqueue_style( 'arm-font-awesome-css' );
                wp_enqueue_style( 'arm_form_style_css' );
                wp_enqueue_style( 'arm_chosen_selectbox' );
                wp_enqueue_script( 'arm_chosen_jq_min' );
                wp_enqueue_script('jquery-ui-autocomplete');
                wp_enqueue_script( 'arm_tipso' );
                wp_enqueue_script( 'arm_icheck-js' );
                wp_enqueue_script( 'arm_bpopup' );


                if($arm_version_compatible)
                {
                    wp_enqueue_script('datatables');
                    wp_enqueue_script('buttons-colvis');
                    wp_enqueue_script('fixedcolumns');
                    wp_enqueue_script('fourbutton');
                }
                else
                {
                    wp_enqueue_script( 'jquery_dataTables', ARM_DIRECT_LOGINS_ARMEMBER_URL . '/datatables/media/js/jquery.dataTables.js', array(), $arm_direct_logins_version );
                    wp_enqueue_script( 'FourButton', ARM_DIRECT_LOGINS_ARMEMBER_URL . '/datatables/media/js/four_button.js', array(), $arm_direct_logins_version );
                    wp_enqueue_script( 'FixedColumns', ARM_DIRECT_LOGINS_ARMEMBER_URL . '/datatables/media/js/FixedColumns.js', array(), $arm_direct_logins_version );
                }
                
            }
        }
        
    }    
}

global $arm_direct_logins;
$arm_direct_logins = new ARM_Direct_Logins();

if (file_exists(ARM_DIRECT_LOGINS_CLASSES_DIR . '/class.arm_direct_logins.php') && $arm_direct_logins->is_armember_compatible()){
    require_once(ARM_DIRECT_LOGINS_CLASSES_DIR . '/class.arm_direct_logins.php' );
}

global $armdirectlogin_api_url, $armdirectlogin_plugin_slug;

$armdirectlogin_api_url = $arm_direct_logins->armdirectlogin_getapiurl();
$armdirectlogin_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armdirectlogin_check_for_plugin_update');

function armdirectlogin_check_for_plugin_update($checked_data) {
    global $armdirectlogin_api_url, $armdirectlogin_plugin_slug, $wp_version, $arm_direct_logins_version,$arm_direct_logins;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armdirectlogin_plugin_slug,
        'version' => $arm_direct_logins_version,
        'other_variables' => $arm_direct_logins->armdirectlogin_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMDIRECTLOGIN-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armdirectlogin_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armdirectlogin_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armdirectlogin_plugin_slug . '/' . $armdirectlogin_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armdirectlogin_plugin_api_call', 10, 3);

function armdirectlogin_plugin_api_call($def, $action, $args) {
    global $armdirectlogin_plugin_slug, $armdirectlogin_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armdirectlogin_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armdirectlogin_plugin_slug . '/' . $armdirectlogin_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armdirectlogin_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMDIRECTLOGIN-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armdirectlogin_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', 'ARM_DIRECT_LOGINS'), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', 'ARM_DIRECT_LOGINS'), $request['body']);
    }

    return $res;
}