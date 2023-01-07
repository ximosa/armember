<?php 
/*
Plugin Name: ARMember - Digital Download
Description: Extension for ARMember plugin for download digital items.
Version: 1.7
Plugin URI: https://www.armemberplugin.com
Author: Repute InfoSystems
Author URI: https://www.armemberplugin.com
Text Domain: ARM_DD
*/

define('ARM_DD_DIR_NAME', 'armemberdigitaldownload');
define('ARM_DD_DIR', WP_PLUGIN_DIR . '/' . ARM_DD_DIR_NAME);



if (is_ssl()) {
    define('ARM_DD_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_DD_DIR_NAME));
} else {
    define('ARM_DD_URL', WP_PLUGIN_URL . '/' . ARM_DD_DIR_NAME);
}

define( 'ARM_DD_TEXTDOMAIN', 'ARM_DD' );

define( 'ARM_DD_CORE_DIR', ARM_DD_DIR . '/core/' );
define( 'ARM_DD_CLASSES_DIR', ARM_DD_CORE_DIR . 'classes/' );
define( 'ARM_DD_VIEW_DIR', ARM_DD_CORE_DIR . 'views/' );
define( 'ARM_DD_WIDGET_DIR', ARM_DD_CORE_DIR . 'widgets/' );

define( 'ARM_DD_WIDGET_URL', ARM_DD_URL . '/core/widgets' );
define( 'ARM_DD_IMAGES_URL', ARM_DD_URL . '/images/' );
define('ARM_DD_LIBRARY_DIR', ARM_DD_DIR . '/lib');
define('ARM_DD_LIBRARY_URL', ARM_DD_URL . '/lib');

global $arm_dd_version;
$arm_dd_version = '1.7';

global $arm_dd;
$arm_dd = new ARM_DD();

if( file_exists( ARM_DD_CLASSES_DIR . 'class.arm_dd_layout.php' ) && $arm_dd->arm_dd_is_compatible() ) {
    require_once ARM_DD_CLASSES_DIR . 'class.arm_dd_layout.php';
}

if( file_exists( ARM_DD_CLASSES_DIR . 'class.arm_dd_items.php' ) && $arm_dd->arm_dd_is_compatible() ) {
    require_once ARM_DD_CLASSES_DIR . 'class.arm_dd_items.php';
}

if( file_exists( ARM_DD_CLASSES_DIR . 'class.arm_dd_downloads.php' ) && $arm_dd->arm_dd_is_compatible() ) {
    require_once ARM_DD_CLASSES_DIR . 'class.arm_dd_downloads.php';
}

if(file_exists( ARM_DD_WIDGET_DIR . "/class.arm_dd_tag_widgets.php" ) && $arm_dd->arm_dd_is_compatible() ) {
   require_once( ARM_DD_WIDGET_DIR . "/class.arm_dd_tag_widgets.php" );
}

if(file_exists( ARM_DD_CLASSES_DIR . "/class.arm_dd_tinymce_options_shortcode.php" ) && $arm_dd->arm_dd_is_compatible() ) {
        
   require_once( ARM_DD_CLASSES_DIR . "/class.arm_dd_tinymce_options_shortcode.php" );
}



class ARM_DD
{
    var $tbl_arm_dd_items;
    var $tbl_arm_dd_downloads;
    function __construct(){        
        
        add_action( 'init', array( $this, 'arm_dd_db_check' ) );

        register_activation_hook( __FILE__, array( 'ARM_DD', 'install' ) );
        
        register_activation_hook(__FILE__, array('ARM_DD', 'arm_dd_check_network_activation'));

        register_uninstall_hook( __FILE__, array( 'ARM_DD', 'uninstall' ) );

        add_action( 'admin_notices', array( $this, 'arm_dd_admin_notices' ) );

        add_action( 'admin_init', array( $this, 'arm_dd_hide_update_notice' ), 1 );
        
        add_action( 'admin_init', array(&$this, 'arm_add_tinymce_styles'));

        add_action( 'plugins_loaded', array( $this, 'arm_dd_load_textdomain' ) );

        add_action('admin_init', array(&$this, 'upgrade_data_ddownload'));
        


        if ($this->arm_dd_is_compatible()){
            
            global $wpdb;
            $this->tbl_arm_dd_items = $wpdb->prefix . 'arm_dd_items';
            $this->tbl_arm_dd_downloads = $wpdb->prefix . 'arm_dd_downloads';
            
            $arm_dd_wp_upload_dir = wp_upload_dir();

           

            $arm_dd_folder_name = $this->arm_dd_get_folder_name();

            define( 'ARM_DD_UPLOAD_DIR', $arm_dd_wp_upload_dir['basedir'] . '/armember/'.$arm_dd_folder_name.'/' );
            define( 'ARM_DD_OUTPUT_DIR', $arm_dd_wp_upload_dir['basedir'] . '/armember/'.$arm_dd_folder_name.'/' );
            define( 'ARM_DD_OUTPUT_URL', $arm_dd_wp_upload_dir['baseurl'] . '/armember/'.$arm_dd_folder_name.'/' );
            add_action('admin_enqueue_scripts', array(&$this, 'set_global_javascript_variables_for_downloads'), 10);
            add_action('wp_head', array(&$this, 'set_global_javascript_variables_for_downloads'));
            add_action( 'admin_enqueue_scripts', array( $this, 'arm_dd_scripts' ), 20 );

            add_action( 'admin_menu', array( $this, 'arm_dd_menu' ),30 );

            add_action( 'wp_ajax_arm_download_settings', array( $this, 'arm_download_settings' ) );

            add_action('user_register',array(&$this,'arm_dd_add_capabilities_to_new_user'));
        }
    }

    function set_global_javascript_variables_for_downloads(){
        echo '<script type="text/javascript" data-cfasync="false">';
        echo '__ARMDDURL = "'.ARM_DD_URL.'";';
        echo '</script>';
    }

    function upgrade_data_ddownload() {
            global $arm_dd_newdbversion;
    
            if (!isset($arm_dd_newdbversion) || $arm_dd_newdbversion == "")
                $arm_dd_newdbversion = get_option('arm_dd_version');
    
            if (version_compare($arm_dd_newdbversion, '1.7', '<')) {
                $path = ARM_DD_VIEW_DIR . '/upgrade_latest_data_ddownload.php';
                include($path);
            }
        }

    public static function arm_dd_db_check() {
        global $arm_dd; 
        $arm_dd_version = get_option( 'arm_dd_version' );

        if ( !isset( $arm_dd_version ) || $arm_dd_version == '' )
            $arm_dd->install();
    }

    public static function install() {
        global $arm_dd, $wpdb, $arm_dd_version, $ARMember;
        $arm_dd_db_version = get_option( 'arm_dd_version' );

        if ( !isset( $arm_dd_db_version ) || $arm_dd_db_version == '' ) {

            update_option( 'arm_dd_version', $arm_dd_version );

            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $arm_dd_upload_dir_string = '';

            for ($i = 0; $i < 11; $i++) {
                $arm_dd_upload_dir_string .= $characters[mt_rand(0, strlen($characters) - 1)];
            }

            $arm_dd_upload_dir_string = "arm_dd_".$arm_dd_upload_dir_string;

            
            /*
            $arm_dd_settings = get_option( 'arm_dd_setting' );
                        
            $arm_dd_options = $arm_dd_settings;
            $arm_dd_options['folder_name'] = $arm_dd_upload_dir_string;
            update_option( 'arm_dd_setting', $arm_dd_options );
            */

            update_option('arm_dd_main_folder_name', $arm_dd_upload_dir_string );

            $wp_upload_dir  = wp_upload_dir();
            $upload_dir = $wp_upload_dir['basedir'] . '/armember/'.$arm_dd_upload_dir_string;

            wp_mkdir_p($upload_dir);

            copy(ARM_DD_DIR.'/htaccess/.htaccess', $upload_dir.'/.htaccess');
            
            $arm_dd_options = array();
           
            $arm_dd_options['block_users'] = '0';
            $arm_dd_options['count_uniqe_ip'] = '0';
            $arm_dd_options['prevent_hotlinking'] = '0';
            $arm_dd_options['open_file_browser'] = '0';
            $arm_dd_options['download_zip'] = '0';
            $arm_dd_options['admin_email'] = '0';
            //$arm_dd_options['folder_name'] = 'arm_dd_'.current_time('timestamp');
            $arm_dd_options['folder_name'] = $arm_dd_upload_dir_string;
            update_option( 'arm_dd_setting', $arm_dd_options );
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $charset_collate = '';
            if ( $wpdb->has_cap( 'collation' ) ) {
                if ( !empty( $wpdb->charset ) ):
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                endif;

                if ( !empty( $wpdb->collate ) ):
                    $charset_collate .= " COLLATE $wpdb->collate";
                endif;
            }
            
            $tbl_items = $wpdb->prefix . 'arm_dd_items';
            $create_tbl_items = "CREATE TABLE IF NOT EXISTS `{$tbl_items}` (
                `arm_item_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `arm_item_name` varchar(100) NOT NULL,
                `arm_item_description` text NOT NULL,
                `arm_item_type` varchar(20) NOT NULL DEFAULT 'Default',
                `arm_file_names` text NOT NULL,
                `arm_item_url` text NOT NULL,
                `arm_item_permission_type` varchar(20) NOT NULL DEFAULT 'any',
                `arm_item_permission` text NOT NULL,
                `arm_item_tag` varchar(255) NOT NULL,
                `arm_item_msg` text NOT NULL,
                `arm_item_download_count` int(11) NOT NULL DEFAULT '0',
                `arm_item_status` int(1) NOT NULL DEFAULT '0',
                `arm_item_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            //`arm_item_summery` varchar(255) NOT NULL,
            //`arm_item_note` varchar(255) NOT NULL,
            dbDelta( $create_tbl_items );
            
            $tbl_downloads = $wpdb->prefix . 'arm_dd_downloads';
            $create_tbl_downloads = "CREATE TABLE IF NOT EXISTS `{$tbl_downloads}` (
                `arm_dd_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `arm_dd_item_id` int(11) NOT NULL DEFAULT '0',
                `arm_dd_file_id` int(11) NOT NULL DEFAULT '0',
                `arm_dd_user_id` int(11) NOT NULL DEFAULT '0',
                `arm_dd_ip_address` varchar(50) NOT NULL,
                `arm_dd_browser` varchar(50) NOT NULL,
                `arm_dd_country` varchar(30) NOT NULL,
                `arm_dd_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            dbDelta( $create_tbl_downloads );
            
            $email_notification_template_data = array(
                    'arm_template_name' => 'User Download File Notification To Admin',
                    'arm_template_slug' => 'user-download-file-admin',
                    'arm_template_subject' => 'User download file at {ARM_BLOGNAME}',
                    'arm_template_content' => '<p>Hello Administrator,</p><br><p>A user is just download file at {ARM_BLOGNAME}. Here are some basic details of user and downloaded file.</p><br><p>Username: {ARM_USERNAME}</p><br><p>Email: {ARM_EMAIL}</p><br><p>Item Name: {ARM_DOWNLOAD_FILE}</p><br><p>IP Address: {ARM_DOWNLOAD_IP}</p><br><p>Browser: {ARM_DOWNLOAD_BROWSER}</p><br><p>Date Time: {ARM_DOWNLOAD_DATETIME}</p><br><br><p>Thank You</p><br><p>{ARM_BLOGNAME}</p>'
                );
            $email_notification_template_data_formate = array( '%s', '%s', '%s', '%s' );
            $wpdb->insert($ARMember->tbl_arm_email_templates, $email_notification_template_data, $email_notification_template_data_formate);
        }

        // give administrator users capabilities
        $args = array(
            'role' => 'administrator',
            'fields' => 'id'
        );
        $users = get_users($args);
        if (count($users) > 0) {
            foreach ($users as $key => $user_id) {
                $armroles = $arm_dd->arm_dd_capabilities();
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
    public static function arm_dd_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }
    public static function uninstall() {
        global $wpdb, $ARMember;
        $tbl_items = $wpdb->prefix . 'arm_dd_items';
        $tbl_downloads = $wpdb->prefix . 'arm_dd_downloads';
        $wpdb->query( "DROP TABLE IF EXISTS $tbl_items " );
        $wpdb->query( "DROP TABLE IF EXISTS $tbl_downloads " );
        $wpdb->delete( $ARMember->tbl_arm_email_templates, array( 'arm_template_slug' => 'user-download-file-admin' ), array( '%s' ) );        
        delete_option( 'arm_dd_setting' );
        delete_option( 'arm_dd_version' );
        delete_option( 'arm_dd_main_folder_name' );
    }

     function arm_dd_capabilities() {
        $arm_dd_cap = array(
            'arm_dd_item' => __('Manage Download', 'ARM_DD'),
            'arm_dd_download' => __('Manage Download History', 'ARM_DD'),
            'arm_dd_setting' => __('Download Settings', 'ARM_DD'),            
        );
        return $arm_dd_cap;
    }

    function arm_dd_add_capabilities_to_new_user($user_id){
        global $ARMember, $arm_dd;
        if( $user_id == '' ){
            return;
        }
        if( user_can($user_id,'administrator')){
            $armroles = $arm_dd->arm_dd_capabilities();
            $userObj = new WP_User($user_id);
            foreach ($armroles as $armrole => $armroledescription){
                $userObj->add_cap($armrole);
            }
            unset($armrole);
            unset($armroles);
            unset($armroledescription);
        }
    }

    function armdd_getapiurl() 
    {
        $api_url = 'https://www.arpluginshop.com/';
        return $api_url;
    }


    function armdd_get_remote_post_params($plugin_info = "") {
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
                if (strpos(strtolower($plugin["Title"]), "armemberdigitaldownload") !== false) {
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


    function arm_dd_admin_notices(){
        global $pagenow, $arm_slugs;    
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if( !$this->arm_dd_is_armember_support() ) :
                echo "<div class='updated updated_notices'><p>" . __( 'ARMember - Digital Download plugin requires ARMember Plugin installed and active.', 'ARM_DD' ) . "</p></div>";
            elseif ( !$this->arm_dd_is_version_compatible() ) :
                echo "<div class='updated updated_notices'><p>" . __( 'ARMember - Digital Download plugin requires ARMember plugin installed with version 3.2 or higher.', 'ARM_DD' ) . "</p></div>";
            endif;
        }
    }

    function arm_dd_is_armember_support() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        return is_plugin_active( 'armember/armember.php' );
    }

    function arm_dd_get_armember_version() {
        $arm_db_version = get_option( 'arm_version' );

        return ( isset( $arm_db_version ) ) ? $arm_db_version : 0;
    }

    function arm_dd_is_version_compatible() {
        if ( !version_compare( $this->arm_dd_get_armember_version(), '3.3', '>=' ) || !$this->arm_dd_is_armember_support() ) :
            return false;
        else : 
            return true;
        endif;
    }

    function arm_dd_is_compatible() {
        if( $this->arm_dd_is_armember_support() && $this->arm_dd_is_version_compatible() ) :
            return true;
        else :
            return false;
        endif;
    }
    
    function arm_dd_page_slug() {
        return array(
            'arm_dd_setting',
            'arm_dd_item',
            'arm_dd_download',
        );
    }
    
    function arm_dd_is_listing_page( $page_slug ) {
        $dd_page_listing_arr = array(
            'arm_dd_item',
            'arm_dd_download'
        );
        if( in_array( $page_slug, $dd_page_listing_arr ) ) :
            return true;
        else :
            return false;
        endif;
    }
    
    function arm_dd_hide_update_notice() {
        $dd_page_arr = $this->arm_dd_page_slug();
        if ( isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $dd_page_arr ) )
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

    function arm_dd_load_textdomain() {
        load_plugin_textdomain( 'ARM_DD', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    function arm_dd_scripts() {
        global $arm_dd_version, $arm_version;
        $dd_page_arr = $this->arm_dd_page_slug();
        if( isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $dd_page_arr ) ) {
            
            wp_register_style( 'arm_dd_admin_css', ARM_DD_URL . '/css/arm_dd_admin.css', array(), $arm_dd_version );
            wp_register_script( 'arm_dd_admin_js', ARM_DD_URL . '/js/arm_dd_admin.js', array(), $arm_dd_version );


            $arm_version_compatible = (version_compare($arm_version, '4.0.1', '>=')) ? 1 : 0;

            if($arm_version_compatible)
            {
                wp_enqueue_style('datatables');
            }
            wp_register_script( 'arm_dd_admin_json_js', ARM_DD_URL . '/js/jquery.json.js', array(), $arm_dd_version );
            wp_localize_script('arm_dd_admin_js', 'imageurl', MEMBERSHIP_IMAGES_URL);
            wp_enqueue_style( 'arm_admin_css' );
            wp_enqueue_style( 'arm-font-awesome-css' );
            wp_enqueue_style( 'arm_form_style_css' );
            wp_enqueue_style( 'arm_dd_admin_css' );
            wp_enqueue_style( 'arm_chosen_selectbox' );
            wp_enqueue_script( 'arm_tipso' );
            wp_enqueue_script( 'arm_dd_admin_js' );
            wp_enqueue_script( 'arm_validate' );
            wp_enqueue_script( 'arm_bpopup' );
            wp_enqueue_script( 'arm_chosen_jq_min' );
            wp_enqueue_script( 'arm_icheck-js' );
             wp_enqueue_script( 'sack' );
             wp_enqueue_script( 'arm_dd_admin_json_js' );
            wp_enqueue_script('jquery-ui-autocomplete');
            
            echo '<script type="text/javascript" data-cfasync="false">';
            echo 'imageurl = "'.MEMBERSHIP_IMAGES_URL.'";';
            echo 'armpleaseselect = "'.__("Please select one or more records.", 'ARM_DD').'";';
            echo 'armbulkActionError = "'.__("Please select valid action.", 'ARM_DD').'";';
            echo 'armsaveSettingsSuccess = "'.__("Settings has been saved successfully.", 'ARM_DD').'";';
            echo 'armsaveSettingsError = "'.__("There is a error while updating settings, please try again.", 'ARM_DD').'";';
            echo '</script>';

            if( in_array( $_REQUEST['page'], array( 'arm_dd_download' ) ) ) {
                //********* date picker start *************/
                //wp_enqueue_style('arm_bootstrap_datepicker_css');
                //wp_enqueue_style('arm_bootstrap_css');
                wp_enqueue_style('arm_bootstrap_all_css');
                wp_enqueue_script('arm_bootstrap_js');
                //wp_enqueue_script('arm_bootstrap_locale_js');
                wp_enqueue_script('arm_bootstrap_datepicker_with_locale');
                //wp_enqueue_script('arm_bootstrap_datepicker_js');
                //********* date picker end *************/
            }
            
            if( $this->arm_dd_is_listing_page( $_REQUEST['page'] ) ) {
                if($arm_version_compatible)
                {
                    wp_enqueue_script('datatables');
                    wp_enqueue_script('buttons-colvis');
                    wp_enqueue_script('fixedcolumns');
                    wp_enqueue_script('fourbutton');
                }
                else
                {
                    wp_enqueue_script( 'jquery_dataTables', MEMBERSHIP_URL.'/datatables/media/js/jquery.dataTables.js', array(), $arm_dd_version );
                    wp_enqueue_script( 'FixedColumns', MEMBERSHIP_URL . '/datatables/media/js/FixedColumns.js', array(), $arm_dd_version );
                    wp_enqueue_script( 'FourButton', MEMBERSHIP_URL . '/datatables/media/js/four_button.js', array(), $arm_dd_version );
                }
            }
        }
        if( isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], array( 'arm_email_notifications' ) ) ) {
            wp_register_script( 'arm_dd_admin_email_notification_js', ARM_DD_URL . '/js/arm_dd_admin_email_notification.js', array(), $arm_dd_version );
            wp_enqueue_script( 'arm_dd_admin_email_notification_js' );
        }
    }

    function arm_add_tinymce_styles() {
        global $arm_dd_version;
        if (!in_array(basename($_SERVER['PHP_SELF']), array('post.php', 'page.php', 'post-new.php', 'page-new.php'))) {
            return;
        }
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script('arm_dd_tinymce_member', ARM_DD_URL . '/js/arm_dd_tinymce_member.js', array('jquery'), $arm_dd_version);
    }

    function arm_dd_menu() {
        global $arm_slugs;
        $arm_dd_item_name  = __( 'Manage Downloads', 'ARM_DD' );
        $arm_dd_item_title = __( 'Manage Downloads', 'ARM_DD' );
        $arm_dd_item_cap   = 'arm_dd_item';
        $arm_dd_item_slug  = 'arm_dd_item';
        
        $arm_dd_download_name  = __( 'Download History', 'ARM_DD' );
        $arm_dd_download_title = __( 'Download History', 'ARM_DD' );
        $arm_dd_download_cap   = 'arm_dd_download';
        $arm_dd_download_slug  = 'arm_dd_download';
        
        $arm_dd_name  = __( 'Download Settings', 'ARM_DD' );
        $arm_dd_title = __( 'Download Settings', 'ARM_DD' );
        $arm_dd_cap   = 'arm_dd_setting';
        $arm_dd_slug  = 'arm_dd_setting';

        add_submenu_page( $arm_slugs->main, $arm_dd_item_name, $arm_dd_item_title, $arm_dd_item_cap, $arm_dd_item_slug, array( $this, 'arm_dd_route' ) );
        add_submenu_page( $arm_slugs->main, $arm_dd_download_name, $arm_dd_download_title, $arm_dd_download_cap, $arm_dd_download_slug, array( $this, 'arm_dd_route' ) );
        add_submenu_page( $arm_slugs->main, $arm_dd_name, $arm_dd_title, $arm_dd_cap, $arm_dd_slug, array( $this, 'arm_dd_route' ) );
    }

    function arm_dd_route() {
        global $ARMember;
        $pageWrapperClass = '';
        $request = $_REQUEST;
        if( isset( $request['page'] ) )
        {
            if (is_rtl()) {
                $pageWrapperClass = 'arm_page_rtl';
            }
            echo '<div class="arm_page_wrapper '.$pageWrapperClass.'" id="arm_page_wrapper">';
            $ARMember->arm_admin_messages_init();
            //$this->arm_dd_admin_messages();
            switch($request['page']) {
                case 'arm_dd_item':
                    if( $this->arm_dd_is_valid_action( $request ) && file_exists( ARM_DD_VIEW_DIR . 'arm_dd_items_add.php' ) ) :
                        include_once ARM_DD_VIEW_DIR . 'arm_dd_items_add.php';
                    elseif( file_exists( ARM_DD_VIEW_DIR . 'arm_dd_items.php' ) ) :
                        include_once ARM_DD_VIEW_DIR . 'arm_dd_items.php';
                    endif;
                break;
                case 'arm_dd_download':
                    if( file_exists( ARM_DD_VIEW_DIR . 'arm_dd_downloads.php' ) ) :
                        include_once ARM_DD_VIEW_DIR . 'arm_dd_downloads.php';
                    endif;
                break;
                case 'arm_dd_setting':
                    if( file_exists( ARM_DD_VIEW_DIR . 'arm_dd_settings.php' ) ) :
                        include_once ARM_DD_VIEW_DIR . 'arm_dd_settings.php';
                    endif;
                break;
            }
            echo '</div>';
        }
    }
    
    function arm_dd_is_valid_action( $request ) {
        if( $request['page'] == 'arm_dd_item' ) {
            if( isset($request['action']) && in_array($request['action'], array( 'new_item', 'add_item' ) ) ) : 
                return true;
            elseif( isset($request['action']) && in_array($request['action'], array( 'edit_item', 'update_item' ) ) && isset($request['id']) && $request['id'] > 0 ) :
                return true;
            else:
                return false;
            endif;
        }
        else
        {
            return false;
        }
    }

    /*function arm_dd_admin_messages() {
        $success_msgs = '';
        $error_msgs = '';
        if (isset($_SESSION['arm_message']) && !empty($_SESSION['arm_message']))
        {
            foreach ($_SESSION['arm_message'] as $snotice) {
                if ($snotice['type'] == 'success') {
                        $success_msgs .= $snotice['message'];
                } else {
                        $error_msgs .= $snotice['message'];
                }
            }
            if(!empty($success_msgs)){
                ?><script type="text/javascript">jQuery(window).load(function(){armToast('<?php echo $snotice['message']; ?>', 'success');});</script><?php
            } elseif(!empty($error_msgs)) {
                ?><script type="text/javascript">jQuery(window).load(function(){armToast('<?php echo $snotice['message']; ?>', 'error');});</script><?php
            }
            unset($_SESSION['arm_message']);
        }        
        echo '<div class="armclear"></div>
        <div class="arm_message arm_success_message" id="arm_success_message">
                <div class="arm_message_text"><?php echo $success_msgs;?></div>
        </div>
        <div class="arm_message arm_error_message" id="arm_error_message">
                <div class="arm_message_text"><?php echo $error_msgs;?></div>
        </div>
        <div class="armclear"></div>
        <div class="arm_toast_container" id="arm_toast_container"></div>';
    }*/
    
    function arm_dd_get_footer() {
        $footer = '<div class="wrap arm_page arm_manage_members_main_wrapper" style="float:right; margin-right:20px;">';
        $footer .= '<a href="'.ARM_DD_URL.'/documentation" target="_blank">';
        $footer .= __('Documentation', 'ARM_DD');
        $footer .= '</a>';
        $footer .= '</div>';
        echo $footer;
    }
    
    function arm_download_settings() {
        global $ARMember, $arm_dd;
        if( method_exists($ARMember, 'arm_check_user_cap') ){
            $arm_dd_capabilities = $arm_dd->arm_dd_page_slug();
            $ARMember->arm_check_user_cap($arm_dd_capabilities['0'],'1');
        }
        $posted_data = $_POST;
        update_option( 'arm_dd_setting', $posted_data );
        $response = array( 'type' => 'success', 'msg'=> __( 'Download settings saved successfully.', 'ARM_DD' ) );
        echo json_encode($response);
        die;
    }

    function arm_dd_get_settings() {
        return get_option( 'arm_dd_setting' );
    }
    
    function arm_dd_get_folder_name() {


        $arm_dd_settings = $this->arm_dd_get_settings();

        $arm_dd_folder = isset($arm_dd_settings['folder_name']) ? $arm_dd_settings['folder_name'] : '';
        if(empty($arm_dd_folder))
        {
            $arm_dd_options = $arm_dd_settings;

            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $arm_dd_upload_dir_string = '';

            for ($i = 0; $i < 11; $i++) {
                $arm_dd_upload_dir_string .= $characters[mt_rand(0, strlen($characters) - 1)];
            }
            
            $arm_dd_options['folder_name'] = $arm_dd_upload_dir_string;
            update_option( 'arm_dd_setting', $arm_dd_options );
            $arm_dd_folder = $arm_dd_upload_dir_string;
        }
        return $arm_dd_folder;
    }
} 

global $arm_dd;
global $armdd_api_url, $armdd_plugin_slug;

$armdd_api_url = $arm_dd->armdd_getapiurl();
$armdd_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armdd_check_for_plugin_update');

function armdd_check_for_plugin_update($checked_data) {
    global $armdd_api_url, $armdd_plugin_slug, $wp_version, $arm_dd_version,$arm_dd;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armdd_plugin_slug,
        'version' => $arm_dd_version,
        'other_variables' => $arm_dd->armdd_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMDIGITLDOWNLOAD-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armdd_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armdigitaldownload_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armdd_plugin_slug . '/' . $armdd_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armdd_plugin_api_call', 10, 3);

function armdd_plugin_api_call($def, $action, $args) {
    global $armdd_plugin_slug, $armdd_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armdd_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armdd_plugin_slug . '/' . $armdd_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armdigitaldownload_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMDIGITLDOWNLOAD-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armdd_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', ARM_DD_TEXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', ARM_DD_TEXTDOMAIN), $request['body']);
    }

    return $res;
}