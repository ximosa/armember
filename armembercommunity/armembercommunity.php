<?php 
/*
Plugin Name: ARMember - Social Community Addon
Description: Extension for ARMember plugin to integrate Social Community Features.
Version: 1.5
Plugin URI: https://www.armemberplugin.com
Author: Repute InfoSystems
Author URI: https://www.armemberplugin.com
*/

define('ARM_COMMUNITY_DIR_NAME', 'armembercommunity');
define('ARM_COMMUNITY_DIR', WP_PLUGIN_DIR . '/' . ARM_COMMUNITY_DIR_NAME);

if (is_ssl()) {
    define('ARM_COMMUNITY_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_COMMUNITY_DIR_NAME));
    define('ARM_COMMUNITY_HOME_URL', home_url('','https'));
}
else {
    define('ARM_COMMUNITY_URL', WP_PLUGIN_URL . '/' . ARM_COMMUNITY_DIR_NAME);
    define('ARM_COMMUNITY_HOME_URL', home_url());
}

define('ARM_COMMUNITY_TEXTDOMAIN', 'ARM_COMMUNITY');
define('ARM_COMMUNITY_CLASSES_DIR', ARM_COMMUNITY_DIR . '/core/classes/' );
define('ARM_COMMUNITY_VIEW_DIR', ARM_COMMUNITY_DIR . '/core/view/' );
define('ARM_COMMUNITY_IMAGES_URL', ARM_COMMUNITY_URL . '/images/' );

global $arm_community_version;
$arm_community_version = '1.5';

global $arm_community_features;
$arm_community_features = new ARM_Community();

global $arm_community_activity_meta;

global $arm_social_community_newdbversion;


class ARM_Community {
    var $tbl_arm_com_friendship = '';
    var $tbl_arm_com_message = '';
    var $tbl_arm_com_follow = '';
    var $tbl_arm_com_review = '';
    var $tbl_arm_com_activity = '';
    function __construct() {
        global $wpdb, $arm_community_setting;
        $this->tbl_arm_com_friendship = $wpdb->prefix . 'arm_com_friendship';
        $this->tbl_arm_com_message = $wpdb->prefix . 'arm_com_message';
        $this->tbl_arm_com_follow = $wpdb->prefix . 'arm_com_follow';
        $this->tbl_arm_com_review = $wpdb->prefix . 'arm_com_review';
        $this->tbl_arm_com_activity = $wpdb->prefix . 'arm_com_activity';
        add_action( 'init', array( &$this, 'arm_community_db_check' ) );
        register_activation_hook( __FILE__, array( 'ARM_Community', 'install' ) );
        register_activation_hook(__FILE__, array('ARM_Community', 'arm_community_check_network_activation'));
        register_uninstall_hook( __FILE__, array( 'ARM_Community', 'uninstall' ) );
        add_action( 'admin_notices', array( &$this, 'arm_admin_notices' ) );
        add_action( 'admin_init', array( &$this, 'arm_community_hide_update_notice' ), 1 );
        add_action( 'plugins_loaded', array( &$this, 'arm_community_load_textdomain' ) );
        add_filter('arm_email_notification_shortcodes_outside', array(&$this, 'arm_email_notification_shortcodes_func'), 10, 1);
        add_filter('arm_change_advanced_email_communication_email_notification', array(&$this, 'arm_change_advanced_email_communication_func'), 10, 3);
        add_filter('arm_admin_email_notification_shortcodes_outside', array(&$this, 'arm_email_notification_shortcodes_func'), 10, 1);

        if ($this->is_armember_compatible()){
            define( 'ARM_COM_ARMEMBER_URL', MEMBERSHIP_URL );
            add_action( 'admin_menu', array( &$this, 'arm_community_menu' ), 30 );
            add_action( 'admin_enqueue_scripts', array( &$this, 'arm_community_scripts' ), 20 );
            add_action( 'wp_head', array( $this, 'arm_community_set_front_js_css' ) );
            add_filter( 'heartbeat_received', array(&$this, 'myplugin_receive_heartbeat'), 10, 2 );
            add_filter( 'heartbeat_settings', array(&$this,'wptuts_heartbeat_settings') );
            add_action('user_register', array(&$this, 'arm_add_community_capabilities_to_new_user'));
        }
        add_action('admin_init', array(&$this, 'upgrade_data_socialcommunity'));
        add_filter( 'armember_setup_demo_plugin_outside', array(&$this, 'armcom_armember_setup_demo_plugin'), 11, 1);
    }

    function armcf_getapiurl() 
    {
        $api_url = 'https://www.arpluginshop.com/';
        return $api_url;
    }


    function armcf_get_remote_post_params($plugin_info = "") {
        global $wpdb;

        $action = "";
        $action = $plugin_info;

        if (!function_exists('get_plugins')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_list = get_plugins();
        $site_url = ARM_COMMUNITY_HOME_URL;
        $plugins = array();

        $active_plugins = get_option('active_plugins');

        foreach ($plugin_list as $key => $plugin) {
            $is_active = in_array($key, $active_plugins);

            //filter for only armember ones, may get some others if using our naming convention
            if (strpos(strtolower($plugin["Title"]), "armembercommunity") !== false) {
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

    function wptuts_heartbeat_settings( $settings ) {
        $settings['interval'] = 30;
        return $settings;
    }
    
    function myplugin_receive_heartbeat( $response, $data ) {
        global $arm_community_message, $arm_community_features, $wpdb;
        if ( empty( $data['arm_com_msg_count'] ) ) {
            return $response;
        }
        if(is_user_logged_in()){
            $user_id = get_current_user_id();
            $arm_com_sender_record = $arm_community_message->arm_com_sender_records();
            if (!empty($arm_com_sender_record)) {
                foreach ($arm_com_sender_record as $key => $value) {
                    $arm_sender_id = $value['arm_sender_id'];
                    if ($user_id == $arm_sender_id) {
                        $arm_sender_id = $value['arm_receiver_id'];
                    }
                    $message_result = "SELECT count(`arm_msg_id`) FROM {$arm_community_features->tbl_arm_com_message} WHERE 1=1 AND `arm_receiver_read`= 0 AND `arm_sender_id`={$arm_sender_id} AND `arm_receiver_id`={$user_id} AND `arm_is_message_blocked`=0";

                    $arm_total_msgs = $wpdb->get_var($message_result);
                    $response['message_count'][$arm_sender_id] = $arm_total_msgs;
                }
            }
        }
        return $response;
    }


    function arm_community_our_heartbeat_received($response, $received ) {
        if ( !empty( $received['my_data'] ) && $received['my_data'] == 'hello' ) {
            $response['my-response'] = 'Hi there!';
        }
        return $response;
    }

    public static function arm_community_db_check() {
        global $arm_community_features; 
        $arm_community_version = get_option( 'arm_community_version' );

        if ( !isset( $arm_community_version ) || $arm_community_version == '' ) {
            $this->install();
        }
    }

    public static function install() {
        global $arm_community_features;
        $arm_community_version = get_option( 'arm_community_version' );

        if ( !isset( $arm_community_version ) || $arm_community_version == '' ) {

            global $wpdb, $arm_community_version, $arm_community_setting, $arm_community_features;
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            
            update_option( 'arm_community_version', $arm_community_version );
            
            $arm_community_settings = array(
                'arm_com_friendship' => 0,
                'arm_send_friend_request_lbl' => __('Send Friend Request', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_cancel_friend_request_lbl' => __('Cancel Friend Request', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_accept_friend_request_lbl' => __('Accept Friend Request', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_unfriend_lbl' => __('Unfriend', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_current_friends_lbl' => __('Current Friends', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_friend_requests_lbl' => __('Friend Requests', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_public_friends' => 0,
                'arm_com_private_message' => 0,
                'arm_message_only_friends' => 0,
                'arm_com_follow' => 0,
                'arm_follow_btn_txt' => __('Follow', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_unfollow_btn_txt' => __('Unfollow', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_followers_lbl' => __('Followers', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_following_lbl' => __('Followings', ARM_COMMUNITY_TEXTDOMAIN), 
                'arm_com_reivew' => 0,
                'arm_keep_reviews_public' => 0,
                'arm_com_post' => 0,
                'arm_com_activity' => 0,
            );
            update_option( 'arm_community_settings', $arm_community_settings );
            
            $charset_collate = '';
            if ($wpdb->has_cap('collation')) {
                if (!empty($wpdb->charset)) {
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                }

                if (!empty($wpdb->collate)) {
                    $charset_collate .= " COLLATE $wpdb->collate";
                }
            }

            $tbl_friendship = $wpdb->prefix . 'arm_com_friendship';
            $create_tbl_friendship = "CREATE TABLE IF NOT EXISTS `{$tbl_friendship}` (
                `arm_friendship_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `arm_initiator_user_id` int(11) NOT NULL,
                `arm_friend_id` int(11) NOT NULL,
                `arm_is_confirmed` int(1) DEFAULT '0' NOT NULL,
                `arm_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            dbDelta( $create_tbl_friendship );
            
            $tbl_message = $wpdb->prefix . 'arm_com_message';
            $create_tbl_message = "CREATE TABLE IF NOT EXISTS `{$tbl_message}` (
                `arm_msg_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `arm_receiver_id` int(11) NOT NULL,
                `arm_sender_id` int(11) NOT NULL,
                `arm_subject` varchar(100) NOT NULL,
                `arm_message` text NOT NULL,
                `arm_sender_read` int(1) DEFAULT '0' NOT NULL,
                `arm_receiver_read` int(1) DEFAULT '0' NOT NULL,
                `arm_sender_delete` int(1) DEFAULT '0' NOT NULL,
                `arm_receiver_delete` int(1) DEFAULT '0' NOT NULL,
                `arm_sender_complete_delete` int(1) DEFAULT '0' NOT NULL,
                `arm_receiver_complete_delete` int(1) DEFAULT '0' NOT NULL,
                `arm_sender_starred` int(1) DEFAULT '0' NOT NULL,
                `arm_receiver_starred` int(1) DEFAULT '0' NOT NULL,
                `arm_is_message_blocked` int(11) DEFAULT '0' NOT NULL,
                `arm_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            dbDelta( $create_tbl_message );
            
            $tbl_follow = $wpdb->prefix . 'arm_com_follow';
            $create_tbl_follow = "CREATE TABLE IF NOT EXISTS `{$tbl_follow}` (
                `arm_follow_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `arm_follower_id` int(11) NOT NULL,
                `arm_following_id` int(11) NOT NULL,
                `arm_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            dbDelta( $create_tbl_follow );

            $tbl_review = $wpdb->prefix . 'arm_com_review';
            $create_tbl_review = "CREATE TABLE IF NOT EXISTS `{$tbl_review}` (
                `arm_review_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `arm_rating` float NOT NULL,
                `arm_user_from` int(11) NOT NULL,
                `arm_user_to` int(11) NOT NULL,
                `arm_title` varchar(100) NOT NULL,
                `arm_description` text NOT NULL,
                `arm_approved` int(1) DEFAULT '0' NOT NULL,
                `arm_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            dbDelta( $create_tbl_review );

            $tbl_activity = $wpdb->prefix . 'arm_com_activity';
            $create_tbl_activity = "CREATE TABLE IF NOT EXISTS `{$tbl_activity}` (
                `arm_activity_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `arm_user_from` int(11) NOT NULL,
                `arm_activity` text NOT NULL,
                `arm_action` varchar(100) NOT NULL,
                `arm_user_to` int(11) NOT NULL,
                `activity_type` varchar(100) NOT NULL,
                `type_id` int(11) NOT NULL,
                `arm_activity_status` TINYINT(1) DEFAULT '0' NOT NULL,
                `arm_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            dbDelta( $create_tbl_activity );
        }

        $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
        $args = array(
            'role' => 'administrator',
            'fields' => 'id'
        );
        $users = get_users($args);
        if (count($users) > 0) {
            foreach ($users as $key => $user_id) {
                $userObj = new WP_User($user_id);
                foreach ($arm_community_capabilities as $armcomrole) {
                    $userObj->add_cap($armcomrole);
                }
            }
            unset($armcomrole);
            unset($arm_community_capabilities);
        }
    }
    
    /*
     * Restrict Network Activation
     */
    public static function arm_community_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }

    function arm_add_community_capabilities_to_new_user($user_id)
    {
        global $ARMember, $arm_community_features;
        if ($user_id == '') {
            return;
        }
        if (user_can($user_id, 'administrator')) {
            $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
            $userObj = new WP_User($user_id);
            foreach ($arm_community_capabilities as $armcomrole) {
                $userObj->add_cap($armcomrole);
            }
            unset($armcomrole);
            unset($arm_community_capabilities);
        }
    }

    function upgrade_data_socialcommunity() {
        global $arm_social_community_newdbversion;

        if (!isset($arm_social_community_newdbversion) || $arm_social_community_newdbversion == "")
            $arm_social_community_newdbversion = get_option('arm_community_version');

        if (version_compare($arm_social_community_newdbversion, '1.5', '<')) {
            $path = ARM_COMMUNITY_DIR . '/upgrade_latest_data_socialcommunity.php';
            include($path);
        }
    }

    public static function uninstall() {
        global $wpdb, $arm_community_features;
        if (is_multisite()) {
            $blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
            if ($blogs) {
                foreach ($blogs as $blog) {
                    switch_to_blog($blog['blog_id']);
                    $arm_community_features->arm_community_uninstall();
                }
            }
        }
        else {
            $arm_community_features->arm_community_uninstall();
        }
    }

    public static function arm_community_uninstall() {
        global $wpdb, $arm_community_features;
        $wpdb->query("DELETE FROM `" . $wpdb->options . "` WHERE  `option_name` LIKE  '%arm_community\_%'");
        $wpdb->query("DELETE FROM `" . $wpdb->posts . "` WHERE  `post_type`='arm_community'");
        $blog_tables = array(
            $wpdb->prefix . 'arm_com_friendship',
            $wpdb->prefix . 'arm_com_message',
            $wpdb->prefix . 'arm_com_follow',
            $wpdb->prefix . 'arm_com_review',
            $wpdb->prefix . 'arm_com_activity',
        );
        foreach ($blog_tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table ");
        }
    }

    function arm_admin_notices() {
        global $pagenow, $arm_slugs;
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if( !$this->is_armember_support() ) {
                echo "<div class='updated updated_notices'><p>" . __('ARMember - Social Community Addon plugin requires ARMember Plugin installed and active.', ARM_COMMUNITY_TEXTDOMAIN) . "</p></div>";
            }
            else if ( !$this->is_version_compatible() ) {
                echo "<div class='updated updated_notices'><p>" . __('ARMember - Social Community Addon plugin requires ARMember plugin installed with version 3.0 or higher.', ARM_COMMUNITY_TEXTDOMAIN) . "</p></div>";
            }
        }
    }

    function arm_community_hide_update_notice() {
        $arm_community_pages = $this->arm_community_page_slug();
        if ( isset($_REQUEST['page']) && in_array($_REQUEST['page'], $arm_community_pages) ) {
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
        if ( !version_compare( $this->get_armember_version(), '3.0', '>=' ) || !$this->is_armember_support() ) {
            return false;
        }
        else {
            return true;
        }
    }

    function is_armember_compatible() {
        if( $this->is_armember_support() && $this->is_version_compatible() ) {
            return true;
        }
        else {
            return false;
        }
    }

    function arm_community_load_textdomain() {
        load_plugin_textdomain(ARM_COMMUNITY_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    function arm_community_page_slug() {
        return array(
            'arm_community_settings',
            'arm_community_activity',
        );
    }
    
    function arm_community_is_listing_page( $page_slug ) {
        $dd_page_listing_arr = $this->arm_community_page_slug();
        if( in_array( $page_slug, $dd_page_listing_arr ) ) {
            return true;
        }
        else {
            return false;
        }
    }

    function arm_community_scripts() {
        global $arm_community_version, $arm_version;
        $arm_com_page_arr = $this->arm_community_page_slug();
        if ( isset($_REQUEST['page']) && in_array($_REQUEST['page'], $arm_com_page_arr) ) {

            $arm_version_compatible = (version_compare($arm_version, '4.0.1', '>=')) ? 1 : 0;

            wp_register_style( 'arm_community_css', ARM_COMMUNITY_URL . '/css/arm_community_admin.css', array(), $arm_community_version );
            wp_register_script( 'arm_community_js', ARM_COMMUNITY_URL . '/js/arm_community_admin.js', array(), $arm_community_version );

            if($arm_version_compatible)
            {
                wp_enqueue_style('datatables');
            }

            echo '<style type="text/css"> .toplevel_page_arm_affiliate .wp-menu-image img{padding: 5px !important;}</style>';
	        echo '<script type="text/javascript" data-cfasync="false">';
	        echo 'armpleaseselect = "'.__("Please select one or more records.", ARM_COMMUNITY_TEXTDOMAIN).'";';
	        echo 'armbulkActionError = "'.__("Please select valid action.", ARM_COMMUNITY_TEXTDOMAIN).'";';
	        echo 'armsaveSettingsSuccess = "'.__("Settings has been saved successfully.", ARM_COMMUNITY_TEXTDOMAIN).'";';
	        echo 'armsaveSettingsError = "'.__("There is a error while updating settings, please try again.", ARM_COMMUNITY_TEXTDOMAIN).'";';
            
	        echo '</script>';

            wp_enqueue_style( 'arm_community_css' );
            wp_enqueue_script( 'arm_community_js' );

            wp_register_script('arm_bootstrap_datepicker_with_locale', MEMBERSHIP_URL . '/bootstrap/js/bootstrap-datetimepicker-with-locale.js', array('jquery'), MEMBERSHIP_VERSION);

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

            if( $this->arm_community_is_listing_page( $_REQUEST['page'] ) ) {
                if($arm_version_compatible)
                {
                    wp_enqueue_script('datatables');
                    wp_enqueue_script('buttons-colvis');
                    wp_enqueue_script('fixedcolumns');
                    wp_enqueue_script('fourbutton');
                }
                else
                {
                    wp_enqueue_script( 'jquery_dataTables', MEMBERSHIP_URL . '/datatables/media/js/jquery.dataTables.js', array(), $arm_community_version );
                    wp_enqueue_script( 'FourButton', MEMBERSHIP_URL . '/datatables/media/js/four_button.js', array(), $arm_community_version );
                    wp_enqueue_script( 'FixedColumns', MEMBERSHIP_URL . '/datatables/media/js/FixedColumns.js', array(), $arm_community_version );
                }
            }
        }

        if ( isset($_REQUEST['page']) && in_array($_REQUEST['page'], array('arm_profiles_directories') ) ) {
            wp_register_script( 'arm_community_profile_js', ARM_COMMUNITY_URL . '/js/arm_community_admin_profile.js', array(), $arm_community_version );
            wp_enqueue_script( 'arm_community_profile_js' );
            
            wp_register_style( 'arm_community_layout_css', ARM_COMMUNITY_URL . '/css/arm_community_front.css', array(), $arm_community_version );
            wp_enqueue_style( 'arm_community_layout_css' );
        }
    }
    
    function arm_community_set_front_js_css() {
        global $ARMember, $arm_community_version, $arm_ajaxurl;
        $is_arm_front_page = $ARMember->is_arm_front_page();
        if ( $is_arm_front_page === TRUE )
        {
            wp_enqueue_style('arm_front_css');
            wp_enqueue_style('arm_form_style_css');
            wp_enqueue_style('arm_fontawesome_css');

            wp_enqueue_script('jquery-ui-autocomplete');
            wp_enqueue_script( 'arm_bpopup' );
            wp_register_script( 'arm_community_layout_js', ARM_COMMUNITY_URL . '/js/arm_community_front.js', array('jquery','heartbeat'), $arm_community_version );
            wp_enqueue_script( 'arm_community_layout_js' );
            wp_register_style( 'arm_community_layout_css', ARM_COMMUNITY_URL . '/css/arm_community_front.css', array(), $arm_community_version );
            wp_enqueue_style( 'arm_community_layout_css' );

            echo '<script type="text/javascript" data-cfasync="false">';
            echo 'ajaxurl = "'.$arm_ajaxurl.'";';
            echo '</script>';
        }
    }
    
    function arm_community_menu(){
            global $arm_slugs, $current_user, $arm_community_post, $arm_community_activity, $arm_community_review;

            $arm_community_name    = __( 'Community Settings', ARM_COMMUNITY_TEXTDOMAIN );
            $arm_community_title   = __( 'Community Settings', ARM_COMMUNITY_TEXTDOMAIN );
            $arm_community_cap     = 'arm_community_settings';
            $arm_community_slug    = 'arm_community_settings';

            $arm_community_activity_name  = __( 'Community Activities', ARM_COMMUNITY_TEXTDOMAIN);
            $arm_community_activity_title = __( 'Community Activities', ARM_COMMUNITY_TEXTDOMAIN);
            $arm_community_activity_cap   = 'arm_community_activity';
            $arm_community_activity_slug  = 'arm_community_activity';

            add_submenu_page( $arm_slugs->main, $arm_community_name, $arm_community_title, $arm_community_cap, $arm_community_slug, array( $this, 'arm_community_route' ) );
            add_submenu_page($arm_slugs->main, $arm_community_activity_name, $arm_community_activity_title, $arm_community_activity_cap, $arm_community_activity_slug, array( $this, 'arm_community_route' ) );
    }

    function arm_community_route() {
        global $ARMember;
        $pageWrapperClass = '';
        $request = $_REQUEST;

        if(isset($request['page']))
        {
            if (is_rtl()) {
                $pageWrapperClass = 'arm_page_rtl';
            }
            echo '<div class="arm_page_wrapper '.$pageWrapperClass.'" id="arm_page_wrapper">';
            $ARMember->arm_admin_messages_init();
            switch($request['page']) {
                case 'arm_community_settings':
                    if( file_exists( ARM_COMMUNITY_VIEW_DIR . 'arm_community_settings.php' ) ) {
                        include_once ARM_COMMUNITY_VIEW_DIR . 'arm_community_settings.php';
                    }
                break;
                case 'arm_community_activity':
                    $arm_action = !empty($request['arm_action']) ? $request['arm_action'] : '';
                    if($arm_action=='armcomreview')
                    {
                        if( file_exists( ARM_COMMUNITY_VIEW_DIR . 'arm_com_review.php' ) ) {
                            include_once ARM_COMMUNITY_VIEW_DIR . 'arm_com_review.php';
                        }
                    }
                    else if($arm_action=='armcomactivity'){
                        if( file_exists( ARM_COMMUNITY_VIEW_DIR . 'arm_com_activity.php' ) ) {
                            include_once ARM_COMMUNITY_VIEW_DIR . 'arm_com_activity.php';
                        }
                    }
                    else {
                        if( file_exists( ARM_COMMUNITY_VIEW_DIR . 'arm_com_post.php' ) ) {
                            include_once ARM_COMMUNITY_VIEW_DIR . 'arm_com_post.php';
                        }
                    }
                break;
            }
            echo '</div>';
        }
    }

    function arm_email_notification_shortcodes_func($shortcode_array) {

        $shortcode_array[] = array (
                                'title_on_hover' => __("To Display Sender username", ARM_COMMUNITY_TEXTDOMAIN),
                                'shortcode' => '{ARM_COM_SENDER_USERNAME}',
                                'shortcode_label' => __("Sender Username", ARM_COMMUNITY_TEXTDOMAIN)
                            );

        $shortcode_array[] = array (
                                'title_on_hover' => __("To Display Sender First Name", ARM_COMMUNITY_TEXTDOMAIN),
                                'shortcode' => '{ARM_COM_SENDER_FIRSTNAME}',
                                'shortcode_label' => __("Sender First Name", ARM_COMMUNITY_TEXTDOMAIN)
                            );

        $shortcode_array[] = array (
                                'title_on_hover' => __("To Display Sender Last Name", ARM_COMMUNITY_TEXTDOMAIN),
                                'shortcode' => '{ARM_COM_SENDER_LASTNAME}',
                                'shortcode_label' => __("Sender Last Name", ARM_COMMUNITY_TEXTDOMAIN)
                            );

        $shortcode_array[] = array (
                                'title_on_hover' => __("To Display Sender User ID", ARM_COMMUNITY_TEXTDOMAIN),
                                'shortcode' => '{ARM_COM_SENDER_USER_ID}',
                                'shortcode_label' => __("Sender User ID", ARM_COMMUNITY_TEXTDOMAIN)
                            );

        $shortcode_array[] = array (
                                'title_on_hover' => __("To Display Sender Nice Name", ARM_COMMUNITY_TEXTDOMAIN),
                                'shortcode' => '{ARM_COM_SENDER_NICENAME}',
                                'shortcode_label' => __("Sender Nice Name", ARM_COMMUNITY_TEXTDOMAIN)
                            );

        $shortcode_array[] = array (
                                'title_on_hover' => __("To Display Sender Display Name", ARM_COMMUNITY_TEXTDOMAIN),
                                'shortcode' => '{ARM_COM_SENDER_DISPLAYNAME}',
                                'shortcode_label' => __("Sender Display Name", ARM_COMMUNITY_TEXTDOMAIN)
                            );

        $shortcode_array[] = array (
                                'title_on_hover' => __("To Display Sender Email Address", ARM_COMMUNITY_TEXTDOMAIN),
                                'shortcode' => '{ARM_COM_SENDER_EMAIL}',
                                'shortcode_label' => __("Sender Email Address", ARM_COMMUNITY_TEXTDOMAIN)
                            );

        $shortcode_array[] = array (
                                'title_on_hover' => __("To Display Sender Profile Link", ARM_COMMUNITY_TEXTDOMAIN),
                                'shortcode' => '{ARM_COM_SENDER_PROFILE_LINK}',
                                'shortcode_label' => __("Sender Profile Link", ARM_COMMUNITY_TEXTDOMAIN)
                            );

        $shortcode_array[] = array (
                                'title_on_hover' => __("To Display Sender's meta field value. (Where `meta_key` is meta field name.)", ARM_COMMUNITY_TEXTDOMAIN),
                                'shortcode' => '{ARM_COM_SENDER_USERMETA_meta_key}',
                                'shortcode_label' => __("Sender Meta Key", ARM_COMMUNITY_TEXTDOMAIN)
                            );

        return $shortcode_array;
    }

    function arm_change_advanced_email_communication_func($content, $user_id, $user_plan) {
        global $arm_global_settings;
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        $user_name = "-";
        $first_name = "-";
        $last_name = "-";
        $nice_name = "-";
        $display_name = "-";
        $email = "-";
        $profile_link = "-";

        if( !empty($user) ) {
            $user_name = $user->user_login;
            $first_name = $user->first_name;
            $last_name = $user->last_name;
            $nice_name = $user->nicename;
            $display_name = $user->display_name;
            $email = $user->user_email;
            $profile_link = $arm_global_settings->arm_get_user_profile_url($user_id);
        }

        $content = str_replace('{ARM_COM_SENDER_USERNAME}', $user_name, $content);
        $content = str_replace('{ARM_COM_SENDER_FIRSTNAME}', $first_name, $content);
        $content = str_replace('{ARM_COM_SENDER_LASTNAME}', $last_name, $content);
        $content = str_replace('{ARM_COM_SENDER_USER_ID}', $user_id, $content);
        $content = str_replace('{ARM_COM_SENDER_NICENAME}', $nice_name, $content);
        $content = str_replace('{ARM_COM_SENDER_DISPLAYNAME}', $user_name, $content);
        $content = str_replace('{ARM_COM_SENDER_EMAIL}', $email, $content);
        $content = str_replace('{ARM_COM_SENDER_PROFILE_LINK}', $profile_link, $content);

        $matches = array();
        preg_match_all("/\b(\w*ARM_COM_SENDER_USERMETA_\w*)\b/", $content, $matches, PREG_PATTERN_ORDER);
        $matches = $matches[0];
        if (!empty($matches)) {
            foreach ($matches as $mat_var) {
                $key = str_replace('ARM_COM_SENDER_USERMETA_', '', $mat_var);
                $meta_val = "";
                if (!empty($key)) {
                    $meta_val = get_user_meta($user_id, $key, TRUE);
                }
                $content = str_replace('{' . $mat_var . '}', $meta_val, $content);
            }
        }

        return $content;
    }

    function armcom_armember_setup_demo_plugin($arm_demo_arr = array()){
        global $arm_community_features;
        $arm_demo_arr[ARM_COMMUNITY_DIR_NAME.'/'.ARM_COMMUNITY_DIR_NAME.'.php'] = array(
            'name' => 'ARMember - Social Community Addon',
            'caps' => $arm_community_features->arm_community_page_slug(),
            'is_display' => 1
        );

        return $arm_demo_arr;
    }
}

if ( file_exists( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_friendship.php' ) && $arm_community_features->is_armember_compatible()){
    require_once( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_friendship.php' );
}

if ( file_exists( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_settings.php' ) && $arm_community_features->is_armember_compatible()){
    require_once( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_settings.php' );
}

if ( file_exists( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_message.php' ) && $arm_community_features->is_armember_compatible()){
    require_once( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_message.php' );
}

if ( file_exists( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_follow.php' ) && $arm_community_features->is_armember_compatible()){
    require_once( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_follow.php' );
}

if ( file_exists( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_review.php' ) && $arm_community_features->is_armember_compatible()){
    require_once( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_review.php' );
}

if ( file_exists( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_post.php' ) && $arm_community_features->is_armember_compatible()){
    require_once( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_post.php' );
}

if ( file_exists( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_profile.php' ) && $arm_community_features->is_armember_compatible()){
    require_once( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_profile.php' );
}

if ( file_exists( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_activity.php' ) && $arm_community_features->is_armember_compatible()){
    require_once( ARM_COMMUNITY_CLASSES_DIR . '/class.arm_community_activity.php' );
}


global $arm_community_features;
global $armcf_api_url, $armcf_plugin_slug;

$armcf_api_url = $arm_community_features->armcf_getapiurl();

$armcf_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armcf_check_for_plugin_update');

function armcf_check_for_plugin_update($checked_data) {
    global $armcf_api_url, $armcf_plugin_slug, $wp_version, $arm_community_version,$arm_community_features;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armcf_plugin_slug,
        'version' => $arm_community_version,
        'other_variables' => $arm_community_features->armcf_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(ARM_COMMUNITY_HOME_URL)
        ),
        'user-agent' => 'ARMCOMMUNITY-WordPress/' . $wp_version . '; ' . ARM_COMMUNITY_HOME_URL
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armcf_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armcommunity_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armcf_plugin_slug . '/' . $armcf_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armcf_plugin_api_call', 10, 3);

function armcf_plugin_api_call($def, $action, $args) {
    global $armcf_plugin_slug, $armcf_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armcf_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armcf_plugin_slug . '/' . $armcf_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armcommunity_update_token'),
            'request' => serialize($args),
            'api-key' => md5(ARM_COMMUNITY_HOME_URL)
        ),
        'user-agent' => 'ARMCOMMUNITY-WordPress/' . $wp_version . '; ' . ARM_COMMUNITY_HOME_URL
    );

    $request = wp_remote_post($armcf_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', ARM_COMMUNITY_TEXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', ARM_COMMUNITY_TEXTDOMAIN), $request['body']);
    }

    return $res;
}