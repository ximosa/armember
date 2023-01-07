<?php 
/*
Plugin Name: ARMember - Zapier Addon
Description: Extension for ARMember plugin to integration with Zapier.
Version: 1.7
Plugin URI: https://www.armemberplugin.com
Author: Repute Infosystems
Author URI: https://www.armemberplugin.com
Text Domain: ARM_ZAPIER
*/

define( 'ARM_ZAPIER_DIR_NAME', 'armemberzapier' );
define( 'ARM_ZAPIER_DIR', WP_PLUGIN_DIR . '/' . ARM_ZAPIER_DIR_NAME );

if (is_ssl()) {
    define( 'ARM_ZAPIER_URL', str_replace( 'http://', 'https://', WP_PLUGIN_URL . '/' . ARM_ZAPIER_DIR_NAME ) );
} else {
    define( 'ARM_ZAPIER_URL', WP_PLUGIN_URL . '/' . ARM_ZAPIER_DIR_NAME );
}

define('ARM_ZAPIER_TEXTDOMAIN', 'ARM_ZAPIER');

define( 'ARM_ZAPIER_CORE_DIR', ARM_ZAPIER_DIR . '/core/' );
define( 'ARM_ZAPIER_CLASSES_DIR', ARM_ZAPIER_CORE_DIR . 'classes/' );
define( 'ARM_ZAPIER_VIEW_DIR', ARM_ZAPIER_CORE_DIR . 'views/' );

define( 'ARM_ZAPIER_IMAGES_URL', ARM_ZAPIER_URL . '/images/' );

global $arm_zapier_version;
$arm_zapier_version = '1.7';

global $armnew_zapier_version;

global $armzapier_api_url, $armzapier_plugin_slug, $wp_version;

global $arm_zapier;
$arm_zapier = new ARM_Zapier();

if ( file_exists( ARM_ZAPIER_CLASSES_DIR . 'class.arm_zapier_settings.php' ) && $arm_zapier->arm_zapier_is_compatible() ) {
    require_once( ARM_ZAPIER_CLASSES_DIR . 'class.arm_zapier_settings.php' );
}

class ARM_Zapier
{
    function __construct(){

        add_action( 'init', array( $this, 'arm_zapier_db_check' ) );

        register_activation_hook( __FILE__, array( 'ARM_Zapier', 'install' ) );

        register_activation_hook(__FILE__, array('ARM_Zapier', 'arm_zapier_check_network_activation'));

        register_uninstall_hook( __FILE__, array( 'ARM_Zapier', 'uninstall' ) );

        add_action( 'admin_notices', array( $this, 'arm_zapier_admin_notices' ) );

        add_action( 'admin_init', array( $this, 'arm_zapier_hide_update_notice' ), 1 );

        add_action( 'plugins_loaded', array( $this, 'arm_zapier_load_textdomain' ) );

        if ($this->arm_zapier_is_compatible()){

            add_action( 'user_register', array( $this, 'arm_zapier_add_capabilities_to_new_user' ) );

            add_action( 'admin_enqueue_scripts', array( $this, 'arm_zapier_scripts' ), 20 );

            add_action( 'admin_menu', array( $this, 'arm_zapier_menu' ),30 );

            add_action( 'init', array( $this, 'armember_zap_action_webhook' ) );
        }
		
		add_action('admin_init', array(&$this, 'upgrade_data_zapier'));
		
    }

    public static function arm_zapier_db_check() {
        global $arm_zapier, $ARMember,$arm_zapier_settings,$arm_global_settings,$arm_errors; 
        $arm_zapier_version = get_option( 'arm_zapier_version' );
        
        if ( !isset( $arm_zapier_version ) || $arm_zapier_version == '' )
        {
            $arm_zapier->install();
        }
    }

    public static function armember_zap_action_webhook() 
    {
        if(isset($_REQUEST['armember_zap_action']))
        {
            global $arm_zapier_settings, $ARMember;
            $zapier_options = $arm_zapier_settings->arm_zapier_get_settings();
            //$ARMember->arm_write_response("reputelog zapier_options =>".maybe_serialize($zapier_options));
            if(isset($zapier_options['arm_zapier_user_register_zap']) && $zapier_options['arm_zapier_user_register_zap']=='1')
            {
                $armember_zap_action = strtolower($_REQUEST['armember_zap_action']);
                $arm_zapier_action = strtolower($zapier_options['arm_zapier_action']);
                if($armember_zap_action==$arm_zapier_action)
                {
                    if(!empty($_REQUEST['email']))
                    {
                        $update_user_data = array();
                        $ARMember->arm_write_response("reputelog zapier_action date =>".date('Y-m-d H:i:s'));
                        $user_email = !empty($_REQUEST['email']) ? $_REQUEST['email']: '';
                        $user_login = !empty($_REQUEST['user_login']) ? sanitize_user($_REQUEST['user_login']): sanitize_user($user_email);
                        $update_user_data['first_name'] = !empty($_REQUEST['first_name']) ? $_REQUEST['first_name']: '';
                        $update_user_data['last_name'] = !empty($_REQUEST['last_name']) ? $_REQUEST['last_name']: '';
                        if(!empty($_REQUEST['user_nicename']))
                        {
                            $update_user_data['user_nicename'] =  $_REQUEST['user_nicename'];
                        }
                        if(!empty($_REQUEST['user_url']))
                        {
                            $update_user_data['user_url'] =  $_REQUEST['user_url'];
                        }
                        $display_name = !empty($_REQUEST['display_name']) ? $_REQUEST['display_name'] : '';
                        if (empty($display_name)) {
                            if ($update_user_data['first_name'] && $update_user_data['last_name']) {
                                $display_name = $update_user_data['first_name'] . ' ' . $update_user_data['last_name'];
                            } elseif ($update_user_data['first_name']) {
                                $display_name = $update_user_data['first_name'];
                            } elseif ($update_user_data['last_name']) {
                                $display_name = $update_user_data['last_name'];
                            } else {
                                $display_name = $user_login;
                            }
                        }
                        $update_user_data['display_name'] = $display_name;

                        $user_pass = !empty($_REQUEST['user_pass']) ? $_REQUEST['user_pass'] : '';
                        $arm_zapier_custom_fields = !empty($zapier_options['arm_zapier_custom_field']) ? $zapier_options['arm_zapier_custom_field'] : array();

                        if (empty($user_pass)) {
                            $user_pass = apply_filters('arm_member_registration_pass', wp_generate_password(12, false));
                        }
                        if(empty($arm_errors))
                        {
                            $arm_errors = '';
                        }
                        do_action('register_post', $user_login, $user_email, $arm_errors);
                        remove_all_filters('registration_errors');
                        $arm_errors = apply_filters('registration_errors', $arm_errors, $user_login, $user_email);
                        do_action('arm_remove_third_party_error', $arm_errors);
                        if (!empty($arm_errors)) {
                            if ($arm_errors->get_error_code()) {
                                $ARMember->arm_write_response("reputelog arm_errors 1 =>".maybe_serialize($arm_errors));
                                echo json_encode(array('status' =>'error', 'messasge' => $arm_errors));
                                exit;
                            }
                        }
                        $arm_new_user_notification_flag = "";
                        if(email_exists($user_email))
                        {
                            $arm_user = get_user_by( 'email', $user_email );
                            $user_ID = $arm_user->ID;
                            $user_role = $arm_user->roles;
                            $user_success_message = __( "User Updated Successfully from Zapier WebHook.", 'ARM_ZAPIER' );
                        }
                        else
                        {
                            
                            $user_ID = wp_create_user($user_login, $user_pass, $user_email);    
                            $user_success_message = __( "User Registered Successfully from Zapier WebHook.", 'ARM_ZAPIER' );
                            $arm_new_user_notification_flag = "1";
                        }
                        //$ARMember->arm_write_response("reputelog user_ID =>".maybe_serialize($user_ID));
                        if (is_wp_error($user_ID)) {
                            $ARMember->arm_write_response("reputelog arm_errors 2 =>".maybe_serialize($user_ID));
                            echo json_encode(array('status' =>'error', 'messasge' => $user_ID));
                            exit;
                        }
                        else
                        {
                            if((isset($user_role) && !in_array('administrator', $user_role)) || empty($user_role))
                            {
                                $update_user_data['ID'] = $user_ID;
                                $update_user_data['user_email'] = $user_email;
                                $user_ID = wp_update_user($update_user_data);
                                $arm_member_plan_id = !empty($_REQUEST['arm_member_plan_id']) ? $_REQUEST['arm_member_plan_id'] : '';
                                $plan_id = !empty($zapier_options['arm_zapier_user_plan']) ? $zapier_options['arm_zapier_user_plan'] : '';
                                $plan_id = !empty($arm_member_plan_id) ? $arm_member_plan_id : $plan_id;
                                if(!empty($plan_id))
                                {
                                    do_action( 'arm_apply_plan_to_member', $plan_id, $user_ID);
                                }
                                if(!empty($arm_new_user_notification_flag))
                                {
                                    arm_new_user_notification($user_ID, $user_pass);
                                }
                                if(count($arm_zapier_custom_fields) > 0)
                                {
                                    foreach ($arm_zapier_custom_fields as $arm_zapier_custom_field) {
                                        $arm_zapier_custom_field_val = !empty($_REQUEST[$arm_zapier_custom_field]) ? $_REQUEST[$arm_zapier_custom_field]: '';
                                        //$ARMember->arm_write_response("reputelog arm_zapier_custom_field =>".maybe_serialize($arm_zapier_custom_field));
                                        if(!empty($arm_zapier_custom_field_val))
                                        {
                                            update_user_meta( $user_ID, $arm_zapier_custom_field,  $arm_zapier_custom_field_val);    
                                        }
                                    }
                                }
                                if($user_ID)
                                {
                                    $ARMember->arm_write_response($user_success_message);
                                    echo json_encode(array('status' => 'success', 'message' => $user_success_message));
                                }
                            }
                            else{
                                $arm_zap_error_message = __( "User update failed from Zapier WebHook.", 'ARM_ZAPIER' );
                                $ARMember->arm_write_response($arm_zap_error_message);
                                echo json_encode(array('status' => 'error', 'message' => $arm_zap_error_message));
                            }
                        }
                        exit;
                    }
                    else
                    {
                        $message = __( "'email' parameter is required", 'ARM_ZAPIER' );
                        $ARMember->arm_write_response($message);
                        echo json_encode(array('status' => 'error', 'message' => $message));
                        exit;
                    }
                }
                else
                {
                    $message = __( "zapier verify parameter not matched", 'ARM_ZAPIER' );
                        $ARMember->arm_write_response($message);;
                    $ARMember->arm_write_response($message);
                    echo json_encode(array('status' => 'error', 'message' => $message));
                    exit;
                }
            }
            else
            {
                $message = __( "Zapier Action Not Enable from ARMember Zapier Settings", 'ARM_ZAPIER' );
                $ARMember->arm_write_response($message);
                //echo json_encode(array('status' => 'error', 'message' => $message));
                //exit;
            }
        }
    }
	
	function upgrade_data_zapier() {
			global $armnew_zapier_version;
	
			if (!isset($armnew_zapier_version) || $armnew_zapier_version == "")
				$armnew_zapier_version = get_option('arm_zapier_version');
	
            if (version_compare($armnew_zapier_version, '1.7', '<')) {
				$path = ARM_ZAPIER_DIR . '/upgrade_latest_data_zapier.php';
				include($path);
			}
	}

    public static function install() {
        global $arm_zapier, $wpdb, $arm_zapier_version;
        
        $arm_zapier_db_version = get_option( 'arm_zapier_version' );

        if ( !isset( $arm_zapier_db_version ) || $arm_zapier_db_version == '' ) {

            update_option( 'arm_zapier_version', $arm_zapier_version );
            
            $zapier_options = array();
            /* $zapier_options['arm_zapier_webhook_handler'] = $arm_zapier_settings->arm_zapier_webhook_handler_url(); */
            /* $zapier_options['arm_zapier_api_key'] = $arm_zapier_settings->arm_zapier_api_key(); */
            $zapier_options['arm_zapier_user_register'] = '0';
            $zapier_options['arm_zapier_user_register_webhook_url'] = '';
            $zapier_options['arm_zapier_update_profile'] = '0';
            $zapier_options['arm_zapier_user_profile_webhook_url'] = '';
            $zapier_options['arm_zapier_user_renew_plan'] = '0';
            $zapier_options['arm_zapier_user_renew_plan_webhook_url'] = '';
            $zapier_options['arm_zapier_user_change_plan'] = '0';
            $zapier_options['arm_zapier_user_change_plan_webhook_url'] = '';
            $zapier_options['arm_zapier_user_delete'] = '0';
            $zapier_options['arm_zapier_user_delete_webhook_url'] = '';

            update_option('arm_zapier_setting', $zapier_options);
        }

        // give administrator users capabilities
        $args = array(
            'role' => 'administrator',
            'fields' => 'id'
        );
        $users = get_users($args);
        if (count($users) > 0) {
            foreach ($users as $key => $user_id) {
                $armroles = array('arm_zapier_setting' => __('Zapier Settings', 'ARM_ZAPIER'));
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
    public static function arm_zapier_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }

    public static function uninstall() {
        delete_option( 'arm_zapier_version' );
        delete_option( 'arm_zapier_setting' );
    }

    function arm_zapier_add_capabilities_to_new_user($user_id){
        global $ARMember;
        if( $user_id == '' ){
            return;
        }
        if( user_can($user_id,'administrator')){
            $armroles = array('arm_zapier_setting' => __('Zapier Settings', 'ARM_ZAPIER'));
            $userObj = new WP_User($user_id);
            foreach ($armroles as $armrole => $armroledescription){
                $userObj->add_cap($armrole);
            }
            unset($armrole);
            unset($armroles);
            unset($armroledescription);
        }
    }

    function arm_zapier_admin_notices(){
        global $pagenow, $arm_slugs;    
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if( !$this->arm_zapier_is_armember_support() ) :
                echo "<div class='updated updated_notices'><p>" . __('ARMember - Zapier plugin requires ARMember Plugin installed and active.', 'ARM_ZAPIER') . "</p></div>";
            elseif ( !$this->arm_zapier_is_version_compatible() ) :
                echo "<div class='updated updated_notices'><p>" . __('ARMember - Zapier plugin requires ARMember plugin installed with version 3.2.1 or higher.', 'ARM_ZAPIER') . "</p></div>";
            endif;
        }
    }

    function arm_zapier_is_armember_support() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        return is_plugin_active( 'armember/armember.php' );
    }

    function arm_zapier_get_armember_version() {
        $arm_db_version = get_option( 'arm_version' );

        return ( isset( $arm_db_version ) ) ? $arm_db_version : 0;
    }

    function arm_zapier_is_version_compatible() {
        if ( !version_compare( $this->arm_zapier_get_armember_version(), '3.2.1', '>=' ) || !$this->arm_zapier_is_armember_support() ) :
            return false;
        else : 
            return true;
        endif;
    }

	function armzapier_getapiurl() 
	{
		$api_url = 'https://www.arpluginshop.com/';
		return $api_url;
	}
		
    function arm_zapier_is_compatible() {
        if( $this->arm_zapier_is_armember_support() && $this->arm_zapier_is_version_compatible() ) :
            return true;
        else :
            return false;
        endif;
    }

    function arm_zapier_hide_update_notice() {
        if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'arm_zapier_setting')
        {
            remove_action('admin_notices', 'update_nag', 3);
            remove_action('network_admin_notices', 'update_nag', 3);
            remove_action('admin_notices', 'maintenance_nag');
            remove_action('network_admin_notices', 'maintenance_nag');
            remove_action('admin_notices', 'site_admin_notice');
            remove_action('network_admin_notices', 'site_admin_notice');
            remove_action('load-update-core.php', 'wp_update_plugins');
        }
    }

    function arm_zapier_load_textdomain() {
        load_plugin_textdomain('ARM_ZAPIER', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    function arm_zapier_scripts() {
        if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'arm_zapier_setting'){
            global $arm_zapier_version;
            wp_enqueue_style( 'arm_admin_css' );
            wp_enqueue_style( 'arm-font-awesome-css' );
            wp_enqueue_style( 'arm_form_style_css' );
            wp_enqueue_style( 'arm_chosen_selectbox' );
            
            echo '<script type="text/javascript" data-cfasync="false">';
            echo 'armsaveSettingsSuccess = "'.__("Settings has been saved successfully.", 'ARM_ZAPIER').'";';
            echo 'armsaveSettingsError = "'.__("There is a error while updating settings, please try again.", 'ARM_ZAPIER').'";';
            echo '</script>';
            wp_enqueue_script('arm_validate');
            wp_enqueue_script( 'arm_tipso', MEMBERSHIP_URL . '/js/tipso.min.js', array(), $arm_zapier_version );
            wp_enqueue_script( 'arm_admin_js', MEMBERSHIP_URL . '/js/arm_admin.js', array(), $arm_zapier_version );
            wp_register_script( 'arm_zapier_admin_js', ARM_ZAPIER_URL . '/js/arm_zapier_admin.js', array(), $arm_zapier_version );
            wp_enqueue_script( 'arm_zapier_admin_js' );
            wp_enqueue_script( 'arm_chosen_jq_min' );
            wp_enqueue_style('arm_zapier_admin_css', ARM_ZAPIER_URL . '/css/arm_zapier_admin.css', array(), $arm_zapier_version);
        }
    }

    function arm_zapier_menu() {
        global $arm_slugs;
        $arm_zapier_name  = __( 'Zapier Settings', 'ARM_ZAPIER' );
        $arm_zapier_title = __( 'Zapier Settings', 'ARM_ZAPIER' );
        $arm_zapier_cap   = 'arm_zapier_setting';
        $arm_zapier_slug  = 'arm_zapier_setting';

        add_submenu_page( $arm_slugs->main, $arm_zapier_name, $arm_zapier_title, $arm_zapier_cap, $arm_zapier_slug, array( $this, 'arm_zapier_route' ) );
    }
	
	function armzapier_get_remote_post_params($plugin_info = "") {
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
				if (strpos(strtolower($plugin["Title"]), "armemberzapier") !== false) {
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

    function arm_zapier_route() {
        $pageWrapperClass = '';
        if (is_rtl()) {
            $pageWrapperClass = 'arm_page_rtl';
        }
        echo '<div class="arm_page_wrapper '.$pageWrapperClass.'" id="arm_page_wrapper">';
        $this->arm_zapier_admin_messages();
        if($_REQUEST['page'] == 'arm_zapier_setting'){
            if(file_exists(ARM_ZAPIER_VIEW_DIR . 'arm_zapier_settings.php')){
                include_once ARM_ZAPIER_VIEW_DIR . 'arm_zapier_settings.php';
            }
        }
        echo '</div>';
    }

    function arm_zapier_admin_messages() {
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
}    

global $arm_zapier;
global $armzapier_api_url, $armzapier_plugin_slug;

$armzapier_api_url = $arm_zapier->armzapier_getapiurl();
$armzapier_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armzapier_check_for_plugin_update');

function armzapier_check_for_plugin_update($checked_data) {
    global $armzapier_api_url, $armzapier_plugin_slug, $wp_version, $arm_zapier_version,$arm_zapier;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armzapier_plugin_slug,
        'version' => $arm_zapier_version,
        'other_variables' => $arm_zapier->armzapier_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMZAPIER-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armzapier_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armzapier_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armzapier_plugin_slug . '/' . $armzapier_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armzapier_plugin_api_call', 10, 3);

function armzapier_plugin_api_call($def, $action, $args) {
    global $armzapier_plugin_slug, $armzapier_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armzapier_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armzapier_plugin_slug . '/' . $armzapier_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armzapier_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMZAPIER-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armzapier_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', MEMBERSHIP_TXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', MEMBERSHIP_TXTDOMAIN), $request['body']);
    }

    return $res;
}