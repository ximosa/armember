<?php
/*
  Plugin Name: ARMember - Group/Umbrella Membership Addon
  Description: Extension for ARMember plugin for Group/Umbrella Membership.
  Version: 1.0
  Plugin URI: https://www.armemberplugin.com
  Author: Repute Infosystems
  AUthor URI: https://www.armemberplugins.com
  Text Domain: ARMGroupMembership
 */

define('ARM_GROUP_MEMBERSHIP_DIR_NAME', 'armembergroupmembership');
define('ARM_GROUP_MEMBERSHIP_DIR', WP_PLUGIN_DIR . '/' . ARM_GROUP_MEMBERSHIP_DIR_NAME);
if (is_ssl()) {
    define('ARM_GROUP_MEMBERSHIP_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_GROUP_MEMBERSHIP_DIR_NAME));
} else {
    define('ARM_GROUP_MEMBERSHIP_URL', WP_PLUGIN_URL . '/' . ARM_GROUP_MEMBERSHIP_DIR_NAME);
}

define('ARM_GROUP_MEMBERSHIP_CORE_DIR', ARM_GROUP_MEMBERSHIP_DIR . '/core');
define('ARM_GROUP_MEMBERSHIP_CLASS_DIR', ARM_GROUP_MEMBERSHIP_CORE_DIR . '/classes');
define('ARM_GROUP_MEMBERSHIP_VIEW_DIR', ARM_GROUP_MEMBERSHIP_CORE_DIR . '/views');
define('ARM_GROUP_MEMBERSHIP_IMAGES_URL', ARM_GROUP_MEMBERSHIP_URL . '/images');
define('ARM_GROUP_MEMBERSHIP_CSS_URL', ARM_GROUP_MEMBERSHIP_URL . '/css');
define('ARM_GROUP_MEMBERSHIP_JS_URL', ARM_GROUP_MEMBERSHIP_URL . '/js');

global $arm_gm_version, $arm_gm_class;
$arm_gm_version = '1.0';

global $armgm_api_url, $arm_gm_plugin_slug, $wp_version;

global $arm_gm_newdbversion;

if (!class_exists('ARM_GroupMembership')) {

    class ARM_GroupMembership {

        public $isActiveGroupMembership;
        var $tbl_arm_gm_child_users_status;

        function __construct($arm_gm_active_value) {
            global $wpdb;
            $this->tbl_arm_gm_child_users_status = $wpdb->prefix . 'arm_gm_child_users_status';

            $this->isActiveGroupMembership = $arm_gm_active_value;

            register_activation_hook(__FILE__, array('ARM_GroupMembership', 'arm_gm_install'));

            register_activation_hook(__FILE__, array('ARM_GroupMembership', 'arm_gm_check_network_activation'));

            register_uninstall_hook(__FILE__, array('ARM_GroupMembership', 'arm_gm_uninstall'));

            add_action('init', array(&$this, 'arm_gm_db_check'), 100);

            add_action('plugins_loaded', array(&$this, 'arm_gm_load_textdomain'));

            add_action('admin_notices', array(&$this, 'arm_gmtive_campaign_admin_notices'), 100);

            add_action('admin_enqueue_scripts', array( &$this, 'arm_gm_scripts' ), 20 );

            add_action('wp_enqueue_scripts', array( &$this, 'arm_gm_front_scripts' ) );

            add_action('arm_deactivate_feature_settings', array($this, 'arm_gm_deactivate_plugin'), 10, 1);

            add_action( 'admin_menu', array( $this, 'arm_gm_membership_menu' ), 30 );

            register_deactivation_hook( __FILE__, array($this, 'arm_gm_plugin_deactivate'));

            add_action('arm_update_feature_settings', array($this, 'arm_gm_check_addon_enable_or_not'));

            add_filter('arm_default_global_settings', array($this, 'arm_gm_add_default_global_settings'), 10);
        }


        function arm_gm_check_addon_enable_or_not()
        {
            $arm_gm_action = $_POST['action'];
            $arm_gm_arm_features_options = $_POST['arm_features_options'];
            $arm_gm_features_status = $_POST['arm_features_status'];

            if($arm_gm_arm_features_options == "arm_is_multiple_membership_feature")
            {
                $response = array('type' => 'wocommerce_error', 'msg' => __("Group Membership Activated. So Multiple Membership Can't be Activated.", 'ARMGroupMembership'));
                echo json_encode($response);
                die();
            }
        }


        function arm_gm_plugin_deactivate()
        {
            global $wpdb, $ARMember;
            $arm_gm_coupon_exist = $wpdb->get_row("SELECT COUNT(arm_coupon_id) as total FROM `" . $ARMember->tbl_arm_coupons . "` WHERE arm_group_parent_user_id IS NOT NULL", ARRAY_A);

            $arm_coupon_exist_count = $arm_gm_coupon_exist['total'];
            if($arm_coupon_exist_count > 0)
            {
                if(!empty($_POST['action']))
                {
                    $response = array('type' => 'wocommerce_error', 'msg' => __("One or more users have group membership enabled. So plugin cannot deactivate.", 'ARMGroupMembership'));
                    echo json_encode($response);
                    die();
                }
                else
                {
                    wp_die('One or more users have group membership enabled. So plugin cannot deactivate', 'Plugin Deactivation Error', array('response' => 500,'back_link' => true));
                }
            }
        }


        function arm_gm_membership_menu()
        {
            global $arm_slugs, $current_user;
            $arm_gm_name    = __( 'Group Membership', 'ARMGroupMembership' );
            $arm_gm_title   = __( 'Group Membership', 'ARMGroupMembership' );
            $arm_gm_cap     = 'arm_gm_membership';
            $arm_gm_slug    = 'arm_gm_membership';

            add_submenu_page( $arm_slugs->main, $arm_gm_name, $arm_gm_title, $arm_gm_cap, $arm_gm_slug, array( $this, 'arm_gm_members_route' ) );
        }


        function arm_gm_members_route()
        {
            global $ARMember;
            $pageWrapperClass = '';
            $content = '';
            $request = $_REQUEST;

            if(!empty($request) && ($request['page'] == "arm_gm_membership"))
            {
                if (is_rtl()) 
                {
                    $pageWrapperClass = 'arm_page_rtl';
                }
                echo '<div class="arm_page_wrapper '.$pageWrapperClass.'" id="arm_page_wrapper">';

                $ARMember->arm_admin_messages_init();

                switch($request['page']) 
                {
                    case 'arm_gm_membership':
                        if( file_exists( ARM_GROUP_MEMBERSHIP_VIEW_DIR . '/admin/arm_gm_members_listing.php' ) ) 
                        {
                            if(!empty($request['action']) && $request['action'] == "new")
                            {
                                ob_start();
                                require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/admin/arm_gm_add_group_membership.php');
                                $content = ob_get_clean();
                            }
                            else
                            {
                                ob_start();
                                require(ARM_GROUP_MEMBERSHIP_VIEW_DIR . '/admin/arm_gm_members_listing.php');
                                $content = ob_get_clean();
                            }
                        }
                        break;
                }

                echo $content.'</div>';
            }
        }


        function arm_gm_scripts()
        {
            global $arm_gm_version, $arm_global_settings, $arm_version;
            
            wp_register_style('arm-gm-admin-css', ARM_GROUP_MEMBERSHIP_CSS_URL . '/arm_gm_admin.css', array(), $arm_gm_version );
            wp_register_script('arm-gm-admin-js', ARM_GROUP_MEMBERSHIP_JS_URL . '/arm_gm_admin.js', array(), $arm_gm_version);
            wp_enqueue_style('arm-gm-admin-css');
            wp_enqueue_script('arm-gm-admin-js');


            $arm_version_compatible = (version_compare($arm_version, '4.1', '>=')) ? 1 : 0;

            if($arm_version_compatible)
            {
                wp_enqueue_style('datatables');
            }


            if(!empty($_REQUEST['page']) && ($_REQUEST['page'] == "arm_gm_membership" || $_REQUEST['page'] == "arm_gm_add_group_membership"))
            {
                wp_enqueue_script( 'jquery' );
                wp_enqueue_style( 'arm_admin_css' );
                wp_enqueue_style( 'arm-font-awesome-css' );
                wp_enqueue_style( 'arm_form_style_css' );
                wp_enqueue_style( 'arm_chosen_selectbox' );
                wp_enqueue_script( 'arm_chosen_jq_min' );
                wp_enqueue_script( 'arm_tipso' );
                wp_enqueue_script( 'arm_icheck-js' );
                wp_enqueue_script( 'arm_bpopup' );

                wp_enqueue_script('arm_bootstrap_datepicker_with_locale');

                wp_register_style('arm_bootstrap_all_css', MEMBERSHIP_URL . '/bootstrap/css/bootstrap_all.css', array(), MEMBERSHIP_VERSION);
                wp_enqueue_style('arm_bootstrap_all_css');

            if($arm_version_compatible)
            {
                wp_enqueue_script('datatables');
                wp_enqueue_script('buttons-colvis');
                wp_enqueue_script('fixedcolumns');
                wp_enqueue_script('fourbutton');
            }
            else
            {
                wp_enqueue_script( 'jquery_dataTables', MEMBERSHIP_URL . '/datatables/media/js/jquery.dataTables.js', array(), $arm_gm_version );
                wp_enqueue_script( 'FourButton', MEMBERSHIP_URL . '/datatables/media/js/four_button.js', array(), $arm_gm_version );
                wp_enqueue_script( 'FixedColumns', MEMBERSHIP_URL . '/datatables/media/js/FixedColumns.js', array(), $arm_gm_version );
            }

            }

            echo '<script type="text/javascript" data-cfasync="false">';
            echo 'var __Users_Del_Success_Msg = "'.__("Users Deleted Successfully", 'ARMGroupMembership').'";';
            echo 'var __Data_Update = "'.__("Data Updated Successfully", 'ARMGroupMembership').'";';
            echo 'var __Sub_User_Added = "'.__("Sub User Added Successfully", 'ARMGroupMembership').'";';
            echo '</script>';
        }



        function arm_gm_front_scripts()
        {
            global $arm_gm_version;
            
            wp_register_style('arm-gm-front-css', ARM_GROUP_MEMBERSHIP_CSS_URL . '/arm_gm_front.css', array(), $arm_gm_version );
            wp_register_style('arm-gm-front-css', ARM_GROUP_MEMBERSHIP_CSS_URL . '/arm_gm_front.css', array(), $arm_gm_version );
            wp_register_style('arm_form_style_css', MEMBERSHIP_URL . '/css/arm_form_style.css', array(), $arm_gm_version);
            wp_register_style('arm-font-awesome-css', MEMBERSHIP_URL . '/css/arm-font-awesome.css', array(), $arm_gm_version);

            wp_enqueue_style( 'arm-gm-front-css' );
            wp_enqueue_style( 'arm_form_style_css' );
            wp_enqueue_style( 'arm-font-awesome-css' );

            wp_register_script('arm-gm-front-js', ARM_GROUP_MEMBERSHIP_JS_URL . '/arm_gm_front.js', array('jquery', 'arm_angular_with_material', 'arm_form_angular'), $arm_gm_version);
            wp_enqueue_script('arm-gm-front-js');

            $arm_gm_get_plugin_version = get_option('arm_gm_version');
            /*if (isset($arm_gm_get_plugin_version) || $arm_gm_get_plugin_version != '') 
            {*/
                echo '<script type="text/javascript" data-cfasync="false">';
                echo 'var __ARMGM = true;';
                echo 'var __CodeRefreshText = "'.__("Are you sure you want to re-generate invite code?", "ARMGroupMembership").'";';
                echo 'var __ChildDeleteText = "'.__("Are you sure you want to delete child user?", "ARMGroupMembership").'";';
                echo 'var __CodeResendText = "'.__("Are you sure you want to resend invite code?", "ARMGroupMembership").'";';
                echo '</script>';
            //}
        }

        public static function arm_gm_install() {
            global $arm_gm_version, $wpdb, $ARMember, $is_multiple_membership_feature;

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            if($is_multiple_membership_feature->isMultipleMembershipFeature)
            {
                $arm_gm_err_msg = __("ARMember Multiple Membership module is Activated. So Group Membership can't Activate.", 'ARMGroupMembership');
                if(!empty($_POST['action']))
                {
                    $response = array('type' => 'error', 'msg' => $arm_gm_err_msg);
                    echo json_encode($response);
                    die();
                }
                else
                {
                    wp_die($arm_gm_err_msg, "Plugin Activation Error", array("response" => 500,"back_link" => true));
                }
            }

            $isColumnExist = false;
            $arm_gm_table_fields = $wpdb->get_results("DESCRIBE $ARMember->tbl_arm_coupons");
            foreach($arm_gm_table_fields as $arm_gm_tbl_keys => $arm_gm_tbl_val)
            {
                if(!empty($arm_gm_tbl_val->Field) && $arm_gm_tbl_val->Field == "arm_group_parent_user_id")
                {
                    $isColumnExist = true;
                }
            }

            if(!$isColumnExist)
            {
                $wpdb->query("ALTER TABLE $ARMember->tbl_arm_coupons ADD arm_group_parent_user_id INT(11)");
            }


            $charset_collate = '';
            if ($wpdb->has_cap('collation')) {
                if (!empty($wpdb->charset)):
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                endif;

                if (!empty($wpdb->collate)):
                    $charset_collate .= " COLLATE $wpdb->collate";
                endif;
            }
            $tbl_arm_gm_child_users_status = $wpdb->prefix . 'arm_gm_child_users_status';
            $create_tbl_arm_gm_child_users_status = "CREATE TABLE IF NOT EXISTS `{$tbl_arm_gm_child_users_status}` (
                arm_gm_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                arm_gm_email_id varchar(255) NULL,
                arm_gm_user_id bigint(20) NULL,
                arm_gm_parent_user_id bigint(20) NULL,
                arm_gm_status int(1) NOT NULL DEFAULT 0,
                arm_gm_invite_code_id bigint(20) NULL,
                arm_gm_added_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                arm_gm_updated_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            dbDelta( $create_tbl_arm_gm_child_users_status );

            $arm_gm_get_plugin_version = get_option('arm_gm_version');
            if (!isset($arm_gm_get_plugin_version) || $arm_gm_get_plugin_version == '') {
                update_option('arm_gm_version', $arm_gm_version);
            }

            $arm_group_membership_cap = array('arm_gm_membership');
            $args = array(
                'role' => 'administrator',
                'fields' => 'id'
            );
            $arm_gm_admin_users = get_users($args);
            if (count($arm_gm_admin_users) > 0) {
                foreach ($arm_gm_admin_users as $arm_gm_key => $arm_gm_user_id) {
                    $userObj = new WP_User($arm_gm_user_id);
                    foreach ($arm_group_membership_cap as $arm_gm_role) {
                        $userObj->add_cap($arm_gm_role);
                    }
                }
                unset($arm_gm_role);
                unset($arm_group_membership_cap);
            }
        }


        /*
         * Restrict Network Activation
         */
        public static function arm_gm_check_network_activation($network_wide) {
            if (!$network_wide)
                return;

            deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

            header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
            exit;
        }


        public static function arm_gm_uninstall() {
            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS ".$this->tbl_arm_gm_child_users_status."");
            delete_option('arm_gm_version');
        }

        public static function arm_gm_db_check() {
            global $arm_gm_class;
            $arm_gm_get_plugin_version = get_option('arm_gm_version');

            if (!isset($arm_gm_get_plugin_version) || $arm_gm_get_plugin_version == '') {
                $arm_gm_class->arm_gm_install();
            }
        }

        public static function arm_gm_load_textdomain() {
            load_plugin_textdomain('ARMGroupMembership', false, dirname(plugin_basename(__FILE__) . '/languages/'));
        }

        function armgm_getapiurl() {
            $api_url = 'https://www.arpluginshop.com/';
            return $api_url;
        }
		
        static function arm_gm_get_armember_version() {
            $arm_gm_armemberplugin_version = get_option('arm_version');
            return isset($arm_gm_armemberplugin_version) ? $arm_gm_armemberplugin_version : 0;
        }

        static function arm_gm_is_armember_plugin_installed() {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
            return is_plugin_active('armember/armember.php');
        }

        public static function arm_gm_is_plugin_version_comatible() {
            global $arm_gm_class;
            if ($arm_gm_class->arm_gm_is_armember_plugin_installed() && version_compare($arm_gm_class->arm_gm_get_armember_version(), '4.1', '>=')) {
                return true;
            } else {
                return false;
            }
        }

        public static function arm_gmtive_campaign_admin_notices() {
            global $pagenow, $arm_slugs, $arm_gm_class;    
            if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
                if (!$arm_gm_class->arm_gm_is_armember_plugin_installed()) {
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - Group Membership plugin requires ARMember Plugin installed and active', 'ARMGroupMembership') . "</p></div>";
                } else if (!$arm_gm_class->arm_gm_is_plugin_version_comatible()) {
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - Group Membership plugin requires ARMember plugin installed with version 4.1 or higher.', 'ARMGroupMembership') . "</p></div>";
                }
            }
        }

        function arm_gm_messages() {
            $alertMessages = array(
                'delOptInsConfirm' => __("Are you sure to delete configuration?", 'ARMGroupMembership'),
            );
            return $alertMessages;
        }


        function arm_gm_deactivate_plugin($post_data)
        {
            if(!empty($post_data['arm_features_options']) && $post_data['arm_features_options'] == "arm_is_group_membership_feature")
            {
                $response = array('type' => 'wocommerce_error', 'msg' => __("One or more users have Group Membership, so addon can't be deactivated.", 'ARMGroupMembership'));
                echo json_encode($response);
                die();
            }
        }


        function armgm_get_remote_post_params($plugin_info = "") {
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
                if (strpos(strtolower($plugin["Title"]), "armembergroupmembership") !== false) {
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

        function arm_gm_add_default_global_settings($default_global_settings)
        {
	    if(!empty($default_global_settings['page_settings']))
	    {
            	$default_global_settings['page_settings']['child_user_signup_page_id'] = 0;
	    }

            return $default_global_settings;
        }
    }

}


$arm_gm_active_value = is_plugin_active('armembergroupmembership/armembergroupmembership.php') ? true : false;
$arm_gm_class = new ARM_GroupMembership($arm_gm_active_value);

global $armgm_api_url, $arm_gm_plugin_slug;
$armgm_api_url = $arm_gm_class->armgm_getapiurl();
$arm_gm_plugin_slug = basename(dirname(__FILE__));

if (file_exists(ARM_GROUP_MEMBERSHIP_CLASS_DIR . '/class.arm_group_membership.php') && $arm_gm_class->arm_gm_is_plugin_version_comatible()) {
    require_once(ARM_GROUP_MEMBERSHIP_CLASS_DIR . '/class.arm_group_membership.php');
}

if (file_exists(ARM_GROUP_MEMBERSHIP_CLASS_DIR . '/class.arm_group_membership_child_signup.php') && $arm_gm_class->arm_gm_is_plugin_version_comatible()) {
    require_once(ARM_GROUP_MEMBERSHIP_CLASS_DIR . '/class.arm_group_membership_child_signup.php');
}




add_filter('pre_set_site_transient_update_plugins', 'armgm_check_for_plugin_update');

function armgm_check_for_plugin_update($checked_data) {
    global $armgm_api_url, $armgm_plugin_slug, $wp_version, $arm_gm_version,$arm_gm_class;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armgm_plugin_slug,
        'version' => $arm_gm_version,
        'other_variables' => $arm_gm_class->armgm_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMGM-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armgm_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armgm_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armgm_plugin_slug . '/' . $armgm_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armgm_plugin_api_call', 10, 3);

function armgm_plugin_api_call($def, $action, $args) {
    global $armgm_plugin_slug, $armgm_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armgm_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armgm_plugin_slug . '/' . $armgm_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armgm_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMGM-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armgm_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', 'ARMGroupMembership'), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', 'ARMGroupMembership'), $request['body']);
    }

    return $res;
}