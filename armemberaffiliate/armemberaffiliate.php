<?php 
/*
Plugin Name: ARMember - Individual Affiliate Addon
Description:  A Complete Affiliate Module For ARMember Plugin
Version: 3.2
Plugin URI: https://www.armemberplugin.com
Author: Repute InfoSystems
Author URI: https://www.armemberplugin.com
Text Domain: ARM_AFFILIATE
*/

define('ARM_AFFILIATE_DIR_NAME', 'armemberaffiliate');
define('ARM_AFFILIATE_DIR', WP_PLUGIN_DIR . '/' . ARM_AFFILIATE_DIR_NAME);

if (is_ssl()) {
    define('ARM_AFFILIATE_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_AFFILIATE_DIR_NAME));
} else {
    define('ARM_AFFILIATE_URL', WP_PLUGIN_URL . '/' . ARM_AFFILIATE_DIR_NAME);
}

define( 'ARM_AFFILIATE_TEXTDOMAIN', 'ARM_AFFILIATE' );

define( 'ARM_AFFILIATE_CSS_DIR', ARM_AFFILIATE_DIR . '/css/' );

define( 'ARM_AFFILIATE_CORE_DIR', ARM_AFFILIATE_DIR . '/core/' );
define( 'ARM_AFFILIATE_CLASSES_DIR', ARM_AFFILIATE_CORE_DIR . 'classes/' );
define( 'ARM_AFFILIATE_WIDGETS_DIR', ARM_AFFILIATE_CORE_DIR . 'widgets/' );
define( 'ARM_AFFILIATE_VIEW_DIR', ARM_AFFILIATE_CORE_DIR . 'view/' );

define( 'ARM_AFFILIATE_IMAGES_URL', ARM_AFFILIATE_URL . '/images' );

$arm_aff_wp_upload_dir = wp_upload_dir();
define( 'ARM_AFF_UPLOAD_DIR', $arm_aff_wp_upload_dir['basedir'] . '/armember/affiliate/' );
define( 'ARM_AFF_OUTPUT_DIR', $arm_aff_wp_upload_dir['basedir'] . '/armember/affiliate/' );
define( 'ARM_AFF_OUTPUT_URL', $arm_aff_wp_upload_dir['baseurl'] . '/armember/affiliate/' );

global $arm_affiliate_version;
$arm_affiliate_version = '3.2';

global $arm_affiliate_woocommerce_path; 
$arm_affiliate_woocommerce_path = 'woocommerce/woocommerce.php';

global $armaff_rate_type_arr;
$armaff_rate_type_arr = array(
    '0' => array('slug' => 'percentage', 'label' => 'Percentage'),
    '1' => array('slug' => 'fixed_rate', 'label' => 'Fixed Rate')
);

class ARM_Affilaite
{
    var $tbl_arm_aff_affiliates;
    var $tbl_arm_aff_referrals;
    var $tbl_arm_aff_payouts;
    var $tbl_arm_aff_visitors;
    var $tbl_arm_aff_banner;
    
    function __construct(){
        global $wpdb;
        $this->tbl_arm_aff_affiliates = $wpdb->prefix . 'arm_aff_affiliates';
        $this->tbl_arm_aff_referrals = $wpdb->prefix . 'arm_aff_referrals';
        $this->tbl_arm_aff_payouts = $wpdb->prefix . 'arm_aff_payouts';
        $this->tbl_arm_aff_visitors = $wpdb->prefix . 'arm_aff_visitors';
        $this->tbl_arm_aff_banner = $wpdb->prefix . 'arm_aff_banners';
        $this->tbl_arm_aff_forms = $wpdb->prefix . 'arm_aff_forms';
        $this->tbl_arm_aff_affiliates_commision = $wpdb->prefix . 'arm_aff_affiliates_commision';


        add_action( 'init', array( &$this, 'arm_affiliate_db_check' ) );

        register_activation_hook( __FILE__, array( 'ARM_Affilaite', 'install' ) );
        register_activation_hook(__FILE__, array('ARM_Affilaite', 'arm_aff_check_network_activation'));
        register_uninstall_hook( __FILE__, array( 'ARM_Affilaite', 'uninstall' ) );

        add_action( 'admin_notices', array( &$this, 'arm_aff_admin_notices' ) );


        add_action( 'admin_init', array( &$this, 'arm_aff_hide_update_notice' ), 1 );

        add_action('plugins_loaded', array(&$this, 'arm_aff_load_textdomain'));

        if ($this->is_version_compatible()){

            define( 'ARM_URL', MEMBERSHIP_URL );

            add_action( 'admin_init', array(&$this, 'armaff_upgrade_data'));

            add_action( 'admin_menu', array( &$this, 'arm_aff_menu' ),30 );

            add_action( 'admin_enqueue_scripts', array( &$this, 'arm_aff_scripts' ), 20 );

            add_action( 'admin_init', array(&$this, 'arm_add_tinymce_styles'));

            add_action( 'wp', array( &$this, 'arm_set_ref_in_cookie' ), 10 );

            add_action( 'user_register',array(&$this,'arm_aff_add_capabilities_to_new_user') );
        }

        add_action('wp_head', array(&$this, 'armaff_global_javascript_variables'));

        add_action( 'armaff_after_create_database_tables', array(&$this,'arm_aff_insert_database_tables_data') );

        add_filter( 'armember_setup_demo_plugin_outside', array(&$this, 'armaff_armember_setup_demo_plugin'), 11, 1);

    }

    public static function arm_affiliate_db_check() {
        global $arm_affiliate; 
        $arm_affiliate_version = get_option('arm_affiliate_version');

        if (!isset($arm_affiliate_version) || $arm_affiliate_version == '')
            $arm_affiliate->install();
    }
    
    public static function install() {
        global $arm_affiliate, $ARMember;
        $arm_affiliate_version = get_option('arm_affiliate_version');

        if (!isset($arm_affiliate_version) || $arm_affiliate_version == '') {

            global $wpdb, $arm_affiliate_version;
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            
            update_option('arm_affiliate_version', $arm_affiliate_version);
            
            $affiliate_options = array();
            $affiliate_options['arm_aff_referral_var'] = 'armaff' ;
            $affiliate_options['arm_aff_referral_timeout'] = '1' ;
            $affiliate_options['arm_aff_referral_rate_type'] = 'percentage' ;
            $affiliate_options['arm_aff_referral_default_rate'] = '20' ;
            $affiliate_options['arm_aff_referral_url'] = get_home_url() ;
            $affiliate_options['arm_aff_not_allow_zero_commision'] = '0' ;
            $affiliate_options['arm_aff_user'] = 'all' ;
    
            update_option('arm_affiliate_setting', $affiliate_options);
    
            $charset_collate = '';
            if ($wpdb->has_cap('collation')) {
                if (!empty($wpdb->charset)):
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                endif;

                if (!empty($wpdb->collate)):
                    $charset_collate .= " COLLATE $wpdb->collate";
                endif;
            }

            $tbl_affiliates = $wpdb->prefix . 'arm_aff_affiliates';
            $tbl_referrals = $wpdb->prefix . 'arm_aff_referrals';
            $tbl_payouts = $wpdb->prefix . 'arm_aff_payouts';
            $tbl_visitors = $wpdb->prefix . 'arm_aff_visitors';
            $tbl_banners = $wpdb->prefix . 'arm_aff_banners';
            $tbl_forms = $wpdb->prefix . 'arm_aff_forms';
            $tbl_affiliates_commision = $wpdb->prefix . 'arm_aff_affiliates_commision';
            $tbl_affiliates_arm_coupons=$wpdb->prefix . 'arm_coupons';

            $create_tbl_affiliates = "CREATE TABLE IF NOT EXISTS `{$tbl_affiliates}` (
                arm_affiliate_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                arm_user_id bigint(20) NOT NULL,
                arm_status int(1) DEFAULT '1' NOT NULL,
                affiliate_website varchar(100) NULL,
                affiliate_website_desc varchar(255) NULL,
                arm_start_date_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                arm_end_date_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            dbDelta( $create_tbl_affiliates );

            $create_tbl_referrals = "CREATE TABLE IF NOT EXISTS `{$tbl_referrals}` (
                arm_referral_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                arm_affiliate_id bigint(20) NOT NULL,
                arm_plan_id int(11) NOT NULL,
                arm_ref_affiliate_id bigint(20)  NOT NULL,
                arm_status int(1) DEFAULT '0' NOT NULL,
                arm_amount double NOT NULL,
                arm_currency varchar(10) NOT NULL,
                arm_woo_order mediumtext DEFAULT NULL,
                arm_revenue_amount double DEFAULT NULL,
                arm_date_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            dbDelta( $create_tbl_referrals );
            
            $create_tbl_payouts = "CREATE TABLE IF NOT EXISTS `{$tbl_payouts}` (
                arm_payout_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                arm_affiliate_id bigint(20) NOT NULL,
                arm_ref_affiliate_id bigint(20) NOT NULL,
                arm_referral_id bigint(20) NOT NULL,
                arm_amount double NOT NULL,
                arm_currency varchar(10) NOT NULL,
                arm_status int(1) DEFAULT '0' NOT NULL,
                arm_remaining_balance double NOT NULL,
                arm_date_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            dbDelta( $create_tbl_payouts );
            
            $create_tbl_visitors = "CREATE TABLE IF NOT EXISTS `{$tbl_visitors}` (
                arm_visitor_id bigint(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                arm_affiliate_id bigint(20) NOT NULL,
                arm_referral_id bigint(20) NOT NULL,
                arm_visitor_ip varchar(50) NOT NULL,
                arm_browser varchar(100) NOT NULL,
                arm_country varchar(100) NOT NULL,
                arm_date_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
             dbDelta( $create_tbl_visitors );

            $create_tbl_banner = "CREATE TABLE IF NOT EXISTS `{$tbl_banners}` (
                arm_banner_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                arm_title varchar(100) NOT NULL,
                arm_description TEXT NOT NULL,
                arm_image varchar(255) NOT NULL,
                arm_link TEXT NOT NULL,
                arm_open_new_tab int(1) NOT NULL DEFAULT 0,
                arm_status int(1) DEFAULT '0' NOT NULL
            ) {$charset_collate};";
             dbDelta( $create_tbl_banner );

            $create_tbl_forms = "CREATE TABLE IF NOT EXISTS `{$tbl_forms}` (
                arm_form_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                arm_form_title VARCHAR(255) DEFAULT NULL,
                arm_form_style VARCHAR(100) DEFAULT NULL,
                arm_form_slug VARCHAR(255) DEFAULT NULL,
                arm_form_fields LONGTEXT,
                arm_added_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
             dbDelta( $create_tbl_forms );

            $create_tbl_affiliates_commision = "CREATE TABLE IF NOT EXISTS `{$tbl_affiliates_commision}` (
                armaff_setup_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                armaff_affiliate_id bigint(20) NOT NULL,
                armaff_user_id bigint(20) NOT NULL,
                armaff_referral_type tinyint(4) DEFAULT '0' NOT NULL,
                armaff_referral_rate double DEFAULT '0' NOT NULL,
                armaff_recurring_referral_status BOOLEAN DEFAULT FALSE NOT NULL,
                armaff_recurring_referral_type tinyint(4) DEFAULT '0' NOT NULL,
                armaff_recurring_referral_rate double DEFAULT '0' NOT NULL,
                armaff_added_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";
            dbDelta( $create_tbl_affiliates_commision );


            $tbl_arm_coupons_data = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$tbl_affiliates_arm_coupons."' AND column_name = 'arm_coupon_aff_user' AND TABLE_SCHEMA='".DB_NAME."' "  );
            if(empty($tbl_arm_coupons_data)) {
                $add_aff_column_sql = "ALTER TABLE " . $tbl_affiliates_arm_coupons . " ADD arm_coupon_aff_user INT NOT NULL AFTER arm_coupon_status;";
                $wpdb->query($add_aff_column_sql);
            }    
             do_action('armaff_after_create_database_tables');

        }

        // give administrator users capabilities
        $args = array(
            'role' => 'administrator',
            'fields' => 'id'
        );
        $users = get_users($args);
        if (count($users) > 0) {
            foreach ($users as $key => $user_id) {
                $armroles = $arm_affiliate->arm_aff_capabilities();
                $userObj = new WP_User($user_id);
                foreach ($armroles as $armrole => $armroledescription) {
                    $userObj->add_cap($armrole);
                }
                unset($armrole);
                unset($armroles);
                unset($armroledescription);
            }
        }

        flush_rewrite_rules();
    }
    
    /*
     * Restrict Network Activation
     */
    public static function arm_aff_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }

    public static function uninstall() {
        
        global $wpdb;
        if (is_multisite()) {
                $blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
                if ($blogs) {
                        foreach ($blogs as $blog) {
                                switch_to_blog($blog['blog_id']);
                                self::arm_uninstall();
                        }
                        restore_current_blog();
                }
        } else {
                self::arm_uninstall();
        }
            
    }
    
    function arm_uninstall() {
        
        global $wpdb;
        
        $arm_aff_affiliates = $wpdb->prefix . 'arm_aff_affiliates';
        $arm_aff_referrals = $wpdb->prefix . 'arm_aff_referrals';
        $arm_aff_payouts = $wpdb->prefix . 'arm_aff_payouts';
        $arm_aff_visitors = $wpdb->prefix . 'arm_aff_visitors';
        $arm_aff_banners = $wpdb->prefix . 'arm_aff_banners';
        $arm_aff_forms = $wpdb->prefix . 'arm_aff_forms';
        $arm_aff_affiliates_commision = $wpdb->prefix . 'arm_aff_affiliates_commision';

        $wpdb->query("DROP TABLE IF EXISTS $arm_aff_affiliates ");
        $wpdb->query("DROP TABLE IF EXISTS $arm_aff_referrals ");
        $wpdb->query("DROP TABLE IF EXISTS $arm_aff_payouts ");
        $wpdb->query("DROP TABLE IF EXISTS $arm_aff_visitors ");
        $wpdb->query("DROP TABLE IF EXISTS $arm_aff_banners ");
        $wpdb->query("DROP TABLE IF EXISTS $arm_aff_forms ");
        $wpdb->query("DROP TABLE IF EXISTS $arm_aff_affiliates_commision ");
        delete_option('arm_affiliate_setting');
        
        delete_option('arm_affiliate_version');

        delete_option('armaff_referrals_migrated');
        delete_option('armaff_referralsPro_migrated');
    }

    function armaff_global_javascript_variables(){
        echo '<script type="text/javascript" data-cfasync="false">';
            echo 'ajaxurl  = "'.admin_url('admin-ajax.php').'";';
        echo '</script>';
    }

    function arm_aff_capabilities() {
        $armaff_cap = array(
            'arm_affiliate' => __( 'Manage Affiliate', 'ARM_AFFILIATE' ),
            'arm_affiliate_option' => __( 'Affiliate Settings', 'ARM_AFFILIATE' ),
            'arm_affiliate_referral' => __( 'Manage Referral', 'ARM_AFFILIATE' ),
            'arm_affiliate_payouts' => __( 'Manage Payouts', 'ARM_AFFILIATE' ),     
            'arm_affiliate_visits' => __( 'Affiliate User Visits', 'ARM_AFFILIATE' ),
            'arm_affiliate_statistics' => __( 'Affiliate Statistics', 'ARM_AFFILIATE' ),
            'arm_affiliate_banners' => __( 'Manage Banners', 'ARM_AFFILIATE' ),
            'arm_affiliate_migration' => __( 'Migration', 'ARM_AFFILIATE' ),
            'arm_affiliate_commision_setup' => __( 'Manage User Commission', 'ARM_AFFILIATE' ),
        );
        return $armaff_cap;
    }

    function arm_aff_insert_database_tables_data() {

        global $wpdb;

        /*Insert Template Forms Data*/
        $armaff_form_title = 'Register Affiliate Account';
        $armaff_form_type = 'material';
        $armaff_form_slug = 'register_affiliate';
        $armaff_form_fields = array(
            'affiliate_uname' => array(
                'type'  => 'text',
                'name'  => 'affiliate_uname',
                'label' => 'Username',
                'order' => 1,
                'required'  => 1,
                'invalid_message'   => 'Please enter valid username.'
            ),
            'affiliate_fname' => array(
                'type'  => 'text',
                'name'  => 'affiliate_fname',
                'label' => 'First Name',
                'order' => 2,
                'required'  => 1,
                'invalid_message'   => 'Please enter first name'
            ),
            'affiliate_lname' => array(
                'type'  => 'text',
                'name'  => 'affiliate_lname',
                'label' => 'Last Name',
                'order' => 3,
                'required'  => 1,
                'invalid_message'   => 'Please enter last name'
            ),
            'affiliate_email' => array(
                'type'  => 'email',
                'name'  => 'affiliate_email',
                'label' => 'Email Address',
                'order' => 4,
                'required'  => 1,
                'invalid_message'   => 'Please enter valid email address.'
            ),
            'affiliate_pwd' => array(
                'type'  => 'password',
                'name'  => 'affiliate_pwd',
                'label' => 'Password',
                'order' => 5,
                'required'  => 1,
                'invalid_message'   => 'Please enter valid password.'
            ),
            'affiliate_website' => array(
                'type'  => 'url',
                'name'  => 'affiliate_website',
                'label' => 'Website URL',
                'order' => 6,
                'required'  => 1,
                'invalid_message'   => 'Please enter valid website URL.'
            ),
            'affiliate_website_desc' => array(
                'type'  => 'textarea',
                'name'  => 'affiliate_website_desc',
                'label' => 'About Your Website',
                'order' => 7,
                'required'  => 0,
                'invalid_message'   => 'Please enter about your website.'
            ),
            'submit' => array(
                'type'  => 'submit',
                'name'  => 'affiliate_submit',
                'label' => 'Register',
                'order' => 8,
                'required'  => 0,
                'invalid_message'   => ''
            ),
        );

        $armaff_form_fields = json_encode($armaff_form_fields);

        $armaff_template_register = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tbl_arm_aff_forms} WHERE `arm_form_slug` = '%s' LIMIT 1;", $armaff_form_slug ) );

        if($armaff_template_register){

        } else {

            $arm_aff_forms_data = array(
                'arm_form_title'    => $armaff_form_title,
                'arm_form_style'     => $armaff_form_type,
                'arm_form_slug'     => $armaff_form_slug,
                'arm_form_fields'   => $armaff_form_fields,
                'arm_added_date'    => current_time('mysql')
            );

            $wpdb->insert($this->tbl_arm_aff_forms, $arm_aff_forms_data);

        }

        /*Insert Template Forms Data*/
        $armaff_form_title = 'Create Affiliate Account';
        $armaff_form_type = 'material';
        $armaff_form_slug = 'create_user_affiliate';
        $armaff_form_fields = array(
            'affiliate_uname' => array(
                'type'  => 'label',
                'name'  => 'affiliate_uname',
                'label' => 'Username',
                'order' => 1,
                'required'  => 0,
                'invalid_message'   => ''
            ),
            'affiliate_email' => array(
                'type'  => 'label',
                'name'  => 'affiliate_email',
                'label' => 'Email Address',
                'order' => 2,
                'required'  => 0,
                'invalid_message'   => ''
            ),
            'affiliate_website' => array(
                'type'  => 'url',
                'name'  => 'affiliate_website',
                'label' => 'Website URL',
                'order' => 3,
                'required'  => 1,
                'invalid_message'   => 'Please enter valid website URL.'
            ),
            'affiliate_website_desc' => array(
                'type'  => 'textarea',
                'name'  => 'affiliate_website_desc',
                'label' => 'About Your Website',
                'order' => 4,
                'required'  => 0,
                'invalid_message'   => 'Please enter about your website.'
            ),
            'submit' => array(
                'type'  => 'submit',
                'name'  => 'affiliate_submit',
                'label' => 'Submit',
                'display_label' => 'Apply',
                'order' => 5,
                'required'  => 0,
                'invalid_message'   => ''
            ),
        );

        $armaff_form_fields = json_encode($armaff_form_fields);

        $armaff_template_register = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->tbl_arm_aff_forms} WHERE `arm_form_slug` = '%s' LIMIT 1;", $armaff_form_slug ) );

        if($armaff_template_register){

        } else {

            $arm_aff_forms_data = array(
                'arm_form_title'    => $armaff_form_title,
                'arm_form_style'     => $armaff_form_type,
                'arm_form_slug'     => $armaff_form_slug,
                'arm_form_fields'   => $armaff_form_fields,
                'arm_added_date'    => current_time('mysql')
            );

            $wpdb->insert($this->tbl_arm_aff_forms, $arm_aff_forms_data);

        }

    }

    function armaff_armember_setup_demo_plugin($arm_demo_arr = array()){

        $arm_demo_arr['armemberaffiliate/armemberaffiliate.php'] = array(
            'name' => 'ARMember - Individual Affiliate Addon',
            'caps' => array('arm_affiliate','arm_affiliate_option','arm_affiliate_referral','arm_affiliate_payouts', 'arm_affiliate_visits', 'arm_affiliate_statistics', 'arm_affiliate_banners', 'arm_affiliate_migration', 'arm_affiliate_commision_setup'),
            'is_display' => 1
        );

        return $arm_demo_arr;
    }

    function armaff_getapiurl() 
    {
        $api_url = 'https://www.arpluginshop.com/';
        return $api_url;
    }


    function armaff_get_remote_post_params($plugin_info = "") {
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
                if (strpos(strtolower($plugin["Title"]), "armemberaffiliate") !== false) {
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


    function arm_aff_add_capabilities_to_new_user($user_id){
        global $ARMember, $arm_affiliate;
        $args = array(
            'role' => 'administrator',
            'fields' => 'id'
        );
        $users = get_users($args);
        if (count($users) > 0) {
            foreach ($users as $key => $user_id) {
                $armroles = $arm_affiliate->arm_aff_capabilities();
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
    
    function arm_aff_admin_notices(){
        global $pagenow, $arm_slugs;    
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if(!$this->is_armember_support())
                echo "<div class='updated updated_notices'><p>" . __('ARMember Affiliate plugin requires ARMember Plugin installed and active.', 'ARM_AFFILIATE') . "</p></div>";

            else if (!$this->is_version_compatible())
                echo "<div class='updated updated_notices'><p>" . __('ARmember Affiliate plugin requires ARMember plugin installed with version 3.3.2 or higher.', 'ARM_AFFILIATE') . "</p></div>";
        }
    }

    function armaff_upgrade_data(){
        global $armaff_newdbversion;

        if (!isset($armaff_newdbversion) || $armaff_newdbversion == "") {
            $armaff_newdbversion = get_option('arm_affiliate_version');
        }

        if (version_compare($armaff_newdbversion, '3.2', '<')) {
            $armaff_path = ARM_AFFILIATE_VIEW_DIR . 'upgrade_latest_data_affiliate.php';
            include($armaff_path);
        }

    }

    function arm_aff_hide_update_notice(){
        $arm_aff_page_arr = array( 'arm_affiliate', 'arm_affiliate_option', 'arm_affiliate_referral', 'arm_affiliate_payouts', 'arm_affiliate_statistics', 'arm_affiliate_visits', 'arm_affiliate_banners', 'arm_affiliate_migration', 'arm_affiliate_commision_setup' );
        if ( isset($_REQUEST['page']) && in_array($_REQUEST['page'], $arm_aff_page_arr) ) {
            remove_action('admin_notices', 'update_nag', 3);
            remove_action('network_admin_notices', 'update_nag', 3);
            remove_action('admin_notices', 'maintenance_nag');
            remove_action('network_admin_notices', 'maintenance_nag');
            remove_action('admin_notices', 'site_admin_notice');
            remove_action('network_admin_notices', 'site_admin_notice');
            remove_action('load-update-core.php', 'wp_update_plugins');
        }
    }
    
    function arm_aff_load_textdomain(){
        load_plugin_textdomain('ARM_AFFILIATE', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    function is_armember_support() {

        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        return is_plugin_active('armember/armember.php');
    }
    
    function get_armember_version() {
        $arm_db_version = get_option('arm_version');
        
        return (isset($arm_db_version)) ? $arm_db_version : 0;
    }
    
    function is_version_compatible() {
        if (!version_compare($this->get_armember_version(), '3.3.2', '>=') || !$this->is_armember_support()) :
            return false;
        else : 
            return true;
        endif;
    }

    function arm_affiliate_is_woocommerce_active() {
        global $arm_affiliate_woocommerce_path;
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        return is_plugin_active( $arm_affiliate_woocommerce_path );
    }

    function arm_affiliate_enabled_integration( $armaff_integration ) {

        if(!$this->is_armember_support())
        {
            return false;
        }

        global $arm_affiliate_settings;

        $armaff_return = false;

        $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();

        switch ($armaff_integration) {
            case 'woocommerce':
                $armaffiliate_woo = isset($affiliate_options['armaffiliate_woo_options']) ? $affiliate_options['armaffiliate_woo_options'] : array();
                $armaff_enable = isset($armaffiliate_woo['status']) ? $armaffiliate_woo['status'] : 0;
                if($armaff_enable){
                    $armaff_return = true;
                }
                break;
            
            default:
                $armaff_return = false;
                break;
        }


        return $armaff_return;

    }

    function arm_aff_scripts() {
        global $arm_affiliate_version, $arm_version;
        $all_page = array( 'arm_affiliate', 'arm_affiliate_option', 'arm_affiliate_referral', 'arm_affiliate_payouts', 'arm_affiliate_statistics', 'arm_affiliate_visits', 'arm_affiliate_banners', 'arm_affiliate_migration', 'arm_affiliate_commision_setup' );
        $listing_page = array( 'arm_affiliate', 'arm_affiliate_referral', 'arm_affiliate_payouts', 'arm_affiliate_visits', 'arm_affiliate_banners', 'arm_affiliate_statistics', 'arm_affiliate_commision_setup' );
        wp_register_style( 'arm-aff-admin-css', ARM_AFFILIATE_URL . '/css/arm_aff_admin.css', array(), $arm_affiliate_version );
        wp_register_script('arm-aff-admin-js', ARM_AFFILIATE_URL . '/js/arm_aff_admin.js', array(), $arm_affiliate_version);


        $arm_version_compatible = (version_compare($arm_version, '4.0.1', '>=')) ? 1 : 0;
        if($arm_version_compatible)
        {
            wp_enqueue_style('datatables');
        }
        
        echo '<style type="text/css"> .toplevel_page_arm_affiliate .wp-menu-image img{padding: 5px !important;}</style>';
        echo '<script type="text/javascript" data-cfasync="false">';
        echo 'imageurl = "'.MEMBERSHIP_IMAGES_URL.'";';
        echo 'armpleaseselect = "'.__("Please select one or more records.", 'ARM_AFFILIATE').'";';
        echo 'armbulkActionError = "'.__("Please select valid action.", 'ARM_AFFILIATE').'";';
        echo 'armsaveSettingsSuccess = "'.__("Settings has been saved successfully.", 'ARM_AFFILIATE').'";';
        echo 'armsaveSettingsError = "'.__("There is a error while updating settings, please try again.", 'ARM_AFFILIATE').'";';
        echo 'armMigrateDataSuccess = "'.__("Your data is migrated successfully.", 'ARM_AFFILIATE').'";';
        echo 'armMigrateDataError = "'.__("There is a error while migrating data, please try again.", 'ARM_AFFILIATE').'";';
        echo '</script>';
        //wp_localize_script('arm-aff-admin-js', 'imageurl', MEMBERSHIP_IMAGES_URL);
        
        if( isset($_REQUEST['page']) && $_REQUEST['page'] == 'arm_manage_plans')
        {
            wp_enqueue_script( 'arm-aff-admin-js' );
        }
        
        if( isset($_REQUEST['page']) && ( in_array($_REQUEST['page'], $all_page) ) ) {
            wp_enqueue_script( 'arm_validate' );
            wp_enqueue_style( 'arm_admin_css' );
            wp_enqueue_style( 'arm-aff-admin-css' );
            wp_enqueue_style( 'arm-font-awesome-css' );
            wp_enqueue_style( 'arm_form_style_css' );
            wp_enqueue_style( 'arm_chosen_selectbox' );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'arm-aff-admin-js' );
            wp_enqueue_script( 'arm_chosen_jq_min' );
            wp_enqueue_script( 'arm-aff-tipso', ARM_URL . '/js/tipso.min.js', array(), $arm_affiliate_version );
            wp_enqueue_script( 'arm-aff-bpopup', ARM_URL . '/js/jquery.bpopup.min.js', array('jquery'), $arm_affiliate_version);
            
            if( in_array($_REQUEST['page'], $listing_page) ) {
                //********* date picker start *************/
                wp_enqueue_style('arm_bootstrap_all_css');      
                wp_enqueue_script('arm_bootstrap_js');
                wp_enqueue_script('arm_bootstrap_datepicker_with_locale');
                //********* date picker end *************/

                wp_enqueue_script( 'arm-aff-icheck', ARM_URL . '/js/icheck.js', array(), $arm_affiliate_version );

                if($arm_version_compatible)
                {
                    wp_enqueue_script('datatables');
                    wp_enqueue_script('buttons-colvis');
                    wp_enqueue_script('fixedcolumns');
                    wp_enqueue_script('fourbutton');
                }
                else
                {
                    wp_enqueue_script( 'jquery_dataTables', ARM_URL . '/datatables/media/js/jquery.dataTables.js', array(), $arm_affiliate_version );
                    wp_enqueue_script( 'FixedColumns', ARM_URL . '/datatables/media/js/FixedColumns.js', array(), $arm_affiliate_version );
                    wp_enqueue_script( 'FourButton', ARM_URL . '/datatables/media/js/four_button.js', array(), $arm_affiliate_version );
                }
            }
            
            if( $_REQUEST['page'] == 'arm_affiliate_statistics' ) {
                //********* date picker start *************/
                wp_enqueue_style('arm_bootstrap_datepicker_css');
                wp_enqueue_style('arm_bootstrap_css');                
                wp_enqueue_script('arm_bootstrap_js');
                wp_enqueue_script('arm_bootstrap_locale_js');
                wp_enqueue_script('arm_bootstrap_datepicker_js');
                //********* date picker end *************/
                
                //wp_enqueue_script('arm_highchart', ARM_URL . '/js/highcharts.js', array('jquery'), $arm_affiliate_version);
            }

            if( $_REQUEST['page'] == 'arm_affiliate_migration' || $_REQUEST['page'] == 'arm_affiliate_option') {
                wp_enqueue_script( 'arm-aff-icheck', ARM_URL . '/js/icheck.js', array(), $arm_affiliate_version );
            }
            if($_REQUEST['page'] == 'arm_affiliate'){
                wp_enqueue_script('jquery-ui-autocomplete');
            }
        }
    }
    
    function arm_add_tinymce_styles() {
        global $arm_affiliate_version;
        if (!in_array(basename($_SERVER['PHP_SELF']), array('post.php', 'page.php', 'post-new.php', 'page-new.php'))) {
            return;
        }
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script('arm_aff_tinymce_member', ARM_AFFILIATE_URL . '/js/arm_aff_tinymce_member.js', array('jquery'), $arm_affiliate_version);
    }
    
    function arm_aff_menu() {
        
        global $ARMember;
        $arm_aff_affiliate_name  = __( 'Manage Affiliate', 'ARM_AFFILIATE' );
        $arm_aff_affiliate_title = __( 'Manage Affiliate', 'ARM_AFFILIATE' );
        $arm_aff_affiliate_cap   = 'arm_affiliate';
        $arm_aff_affiliate_slug  = 'arm_affiliate';
        
        $arm_aff_setting_name    = __( 'Affiliate Settings', 'ARM_AFFILIATE' );
        $arm_aff_setting_title   = __( 'Affiliate Settings', 'ARM_AFFILIATE' );
        $arm_aff_setting_cap     = 'arm_affiliate_option';
        $arm_aff_setting_slug    = 'arm_affiliate_option';
        
        $arm_aff_referral_name   = __( 'Manage Referral', 'ARM_AFFILIATE' );
        $arm_aff_referral_title  = __( 'Manage Referral', 'ARM_AFFILIATE' );
        $arm_aff_referral_cap    = 'arm_affiliate_referral';
        $arm_aff_referral_slug   = 'arm_affiliate_referral';
        
        $arm_aff_payouts_name    = __( 'Manage Payouts', 'ARM_AFFILIATE' );
        $arm_aff_payouts_title   = __( 'Manage Payouts', 'ARM_AFFILIATE' );
        $arm_aff_payouts_cap     = 'arm_affiliate_payouts';
        $arm_aff_payouts_slug    = 'arm_affiliate_payouts';
        
        $arm_aff_visits_name    = __( 'Affiliate User Visits', 'ARM_AFFILIATE' );
        $arm_aff_visits_title   = __( 'Affiliate User Visits', 'ARM_AFFILIATE' );
        $arm_aff_visits_cap     = 'arm_affiliate_visits';
        $arm_aff_visits_slug    = 'arm_affiliate_visits';
        
        $arm_aff_statistics_name    = __( 'Affiliate Statistics', 'ARM_AFFILIATE' );
        $arm_aff_statistics_title   = __( 'Affiliate Statistics', 'ARM_AFFILIATE' );
        $arm_aff_statistics_cap     = 'arm_affiliate_statistics';
        $arm_aff_statistics_slug    = 'arm_affiliate_statistics';
        
        $arm_aff_banner_name    = __( 'Manage Banners', 'ARM_AFFILIATE' );
        $arm_aff_banner_title   = __( 'Manage Banners', 'ARM_AFFILIATE' );
        $arm_aff_banner_cap     = 'arm_affiliate_banners';
        $arm_aff_banner_slug    = 'arm_affiliate_banners';

        $arm_aff_migrate_name    = __( 'Migration Tool', 'ARM_AFFILIATE' );
        $arm_aff_migrate_title   = __( 'Migration Tool', 'ARM_AFFILIATE' );
        $arm_aff_migrate_cap     = 'arm_affiliate_migration';
        $arm_aff_migrate_slug    = 'arm_affiliate_migration';

        $arm_aff_commision_setup_name    = __( 'Manage User Commission', 'ARM_AFFILIATE' );
        $arm_aff_commision_setup_title   = __( 'Manage User Commission', 'ARM_AFFILIATE' );
        $arm_aff_commision_setup_cap     = 'arm_affiliate_commision_setup';
        $arm_aff_commision_setup_slug    = 'arm_affiliate_commision_setup';

        $place = $ARMember->get_free_menu_position(26.1, 0.3);
        add_menu_page('ARMember Affiliate', __('ARMember Affiliate', 'ARM_AFFILIATE'), $arm_aff_affiliate_cap, $arm_aff_affiliate_cap, array( $this, 'route' ), ARM_AFFILIATE_IMAGES_URL . '/armember_affiliate_menu_icon.png', $place);
        
        add_submenu_page( $arm_aff_affiliate_cap, $arm_aff_affiliate_name, $arm_aff_affiliate_title, $arm_aff_affiliate_cap, $arm_aff_affiliate_slug, array( $this, 'route' ) );
        add_submenu_page( $arm_aff_affiliate_cap, $arm_aff_referral_name, $arm_aff_referral_title, $arm_aff_referral_cap, $arm_aff_referral_slug, array( $this, 'route' ) );
        add_submenu_page( $arm_aff_affiliate_cap, $arm_aff_commision_setup_name, $arm_aff_commision_setup_title, $arm_aff_commision_setup_cap, $arm_aff_commision_setup_slug, array( $this, 'route' ) );
        add_submenu_page( $arm_aff_affiliate_cap, $arm_aff_payouts_name, $arm_aff_payouts_title, $arm_aff_payouts_cap, $arm_aff_payouts_slug, array( $this, 'route' ) );
        add_submenu_page( $arm_aff_affiliate_cap, $arm_aff_banner_name, $arm_aff_banner_title, $arm_aff_banner_cap, $arm_aff_banner_slug, array( $this, 'route' ) );
        add_submenu_page( $arm_aff_affiliate_cap, $arm_aff_statistics_name, $arm_aff_statistics_title, $arm_aff_statistics_cap, $arm_aff_statistics_slug, array( $this, 'route' ) );
        add_submenu_page( $arm_aff_affiliate_cap, $arm_aff_setting_name, $arm_aff_setting_title, $arm_aff_setting_cap, $arm_aff_setting_slug, array( $this, 'route' ) );
        add_submenu_page( $arm_aff_affiliate_cap, $arm_aff_migrate_name, $arm_aff_migrate_title, $arm_aff_migrate_cap, $arm_aff_migrate_slug, array( $this, 'route' ) );
    }
    
    function route() {
        $pageWrapperClass = '';
        if (is_rtl()) {
            $pageWrapperClass = 'arm_page_rtl';
        }
        echo '<div class="arm_page_wrapper '.$pageWrapperClass.'" id="arm_page_wrapper">';
        $this->admin_messages();
        if($_REQUEST['page'] == 'arm_affiliate'){
            if(file_exists(ARM_AFFILIATE_VIEW_DIR . 'arm_affiliate_list.php')){
                include_once ARM_AFFILIATE_VIEW_DIR . 'arm_affiliate_list.php';
            }
        }
        else if($_REQUEST['page'] == 'arm_affiliate_option'){
            if(file_exists(ARM_AFFILIATE_VIEW_DIR . 'arm_affiliate_settings.php')){
                include_once ARM_AFFILIATE_VIEW_DIR . 'arm_affiliate_settings.php';
            }
        }
        else if($_REQUEST['page'] == 'arm_affiliate_referral'){
            if(file_exists(ARM_AFFILIATE_VIEW_DIR . 'arm_referral_list.php')){
                include_once ARM_AFFILIATE_VIEW_DIR . 'arm_referral_list.php';
            }
        }
        else if($_REQUEST['page'] == 'arm_affiliate_payouts'){
            if(file_exists(ARM_AFFILIATE_VIEW_DIR . 'arm_payout_list.php')){
                include_once ARM_AFFILIATE_VIEW_DIR . 'arm_payout_list.php';
            }
        }
        else if($_REQUEST['page'] == 'arm_affiliate_statistics'){
            if(file_exists(ARM_AFFILIATE_VIEW_DIR . 'arm_statistics.php')){
                include_once ARM_AFFILIATE_VIEW_DIR . 'arm_statistics.php';
            }
        }
        else if($_REQUEST['page'] == 'arm_affiliate_visits'){
            if(file_exists(ARM_AFFILIATE_VIEW_DIR . 'arm_visits_list.php')){
                include_once ARM_AFFILIATE_VIEW_DIR . 'arm_visits_list.php';
            }
        }
        else if($_REQUEST['page'] == 'arm_affiliate_banners'){
            if(isset($_REQUEST['action']) && in_array($_REQUEST['action'], array('new_item', 'edit_item', 'add_item', 'update_item')))
            {
                if(file_exists(ARM_AFFILIATE_VIEW_DIR . 'arm_banner_add.php')){
                    include_once ARM_AFFILIATE_VIEW_DIR . 'arm_banner_add.php';
                }
            }
            else
            {
                if(file_exists(ARM_AFFILIATE_VIEW_DIR . 'arm_banner_list.php')){
                    include_once ARM_AFFILIATE_VIEW_DIR . 'arm_banner_list.php';
                }
            }
        }
        else if($_REQUEST['page'] == 'arm_affiliate_migration'){
            if(file_exists(ARM_AFFILIATE_VIEW_DIR . 'arm_affiliate_migration.php')){
                include_once ARM_AFFILIATE_VIEW_DIR . 'arm_affiliate_migration.php';
            }
        }
        else if($_REQUEST['page'] == 'arm_affiliate_commision_setup'){
            if(file_exists(ARM_AFFILIATE_VIEW_DIR . 'arm_affiliate_commision_setup.php')){
                include_once ARM_AFFILIATE_VIEW_DIR . 'arm_affiliate_commision_setup.php';
            }
        }
        
        echo '</div>';
    }
    
    function admin_messages() {
        global $wp, $wpdb, $arm_errors, $ARMember, $pagenow, $arm_slugs;
		$success_msgs = '';
		$error_msgs = '';
        $ARMember->arm_session_start();
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
				?>
				<script type="text/javascript">jQuery(window).load(function(){armToast('<?php echo $snotice['message']; ?>', 'success');});</script>
				<?php
			} elseif(!empty($error_msgs)) {
				?>
				<script type="text/javascript">jQuery(window).load(function(){armToast('<?php echo $snotice['message']; ?>', 'error');});</script>
				<?php
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
    }
    
    function arm_set_ref_in_cookie() {
        global $arm_affiliate_settings, $wpdb, $ARMember, $arm_aff_referrals, $wp_query;
        $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
        $referral_var = $affiliate_options['arm_aff_referral_var'];

        $armaffReqAffiliate = isset($_REQUEST[$referral_var]) ? $_REQUEST[$referral_var] : '';
        if($armaffReqAffiliate == ''){
            $armaffReqAffiliate = $wp_query->get($referral_var);
        }

        if($armaffReqAffiliate != '')
        {
            $arm_aff_id_encoding = isset($affiliate_options['arm_aff_id_encoding']) ? $affiliate_options['arm_aff_id_encoding'] : '';
            if( $arm_aff_id_encoding == 'MD5' ) {
                $cookie_value = $arm_affiliate_settings->arm_get_actual_user_id($armaffReqAffiliate);
            } 
            else if( $arm_aff_id_encoding == 'username' )
            {
                $cookie_value = $arm_affiliate_settings->arm_get_actual_affiliate_id_from_username($armaffReqAffiliate);
            }
            else {
                $cookie_value = $armaffReqAffiliate;
            }

            if(!isset($_COOKIE['arm_aff_ref_cookie']) && $arm_aff_referrals->arm_check_is_allowed_affiliate($cookie_value))
            {

                
                $cookie_name = 'arm_aff_ref_cookie';
                $cookie_exp_time = 0;
                if($affiliate_options['arm_aff_referral_timeout'] > 0)
                {
                    $cookie_exp_time = time() + 60 * 60 * 24 * $affiliate_options['arm_aff_referral_timeout'];
                }
                setcookie($cookie_name, $cookie_value, $cookie_exp_time, '/');
                
                $visitor_ip = $ARMember->arm_get_ip_address();
                $browser_data = $ARMember->getBrowser($_SERVER['HTTP_USER_AGENT']);
                $browser = $browser_data['name'].' ('.$browser_data['version'].')';
                
                $logged_in_ip = $ARMember->arm_get_ip_address();
                $country = $ARMember->arm_get_country_from_ip($logged_in_ip);
                
                $nowDate = current_time('mysql');
                $arm_aff_visists_values = array(
                    'arm_affiliate_id' => $cookie_value,
                    'arm_visitor_ip' => $visitor_ip,
                    'arm_browser' => $browser,
                    'arm_country' => $country,
                    'arm_date_time' => $nowDate
                );

                $wpdb->insert($this->tbl_arm_aff_visitors, $arm_aff_visists_values);
                
                setcookie('visitor_id', $wpdb->insert_id, $cookie_exp_time, '/');
            }
        }
    }
    
    function get_arm_aff_cookie() {
        if(isset($_COOKIE['arm_aff_ref_cookie'])):
            return $_COOKIE['arm_aff_ref_cookie'];
        else:
            return 0;
        endif;
    }
    
    function get_user_login($user_id) {
        global $wpdb;
        if(isset($user_id) && $user_id != '' && $user_id != 0)
        {
            $tmp_query = "SELECT user_login FROM {$wpdb->users} WHERE ID = {$user_id}";
            return $wpdb->get_row($tmp_query, 'OBJECT');
        }
    }
}    

global $arm_affiliate;
$arm_affiliate = new ARM_Affilaite();

// Include class files
if($arm_affiliate->is_version_compatible())
{
    if (file_exists(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_affiliate_settings.php') ){
        require_once(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_affiliate_settings.php');
    }

    if (file_exists(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_affiliate.php') ){
        require_once(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_affiliate.php');
    }

    if (file_exists(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_referrals.php') ){
        require_once(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_referrals.php');
    }

    if (file_exists(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_payouts.php') ){
        require_once(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_payouts.php');
    }

    if (file_exists(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_tinymce_options_shortcode.php') ){
        require_once(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_tinymce_options_shortcode.php');
    }

    if (file_exists(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_layout.php') ){
        require_once(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_layout.php' );
    }

    if (file_exists(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_statistics.php') && $arm_affiliate->is_version_compatible() ){
        require_once(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_statistics.php' );
    }

    if (file_exists(ARM_AFFILIATE_WIDGETS_DIR . '/class.arm_aff_dashboard_widgets.php') && $arm_affiliate->is_version_compatible() ){
        require_once(ARM_AFFILIATE_WIDGETS_DIR . '/class.arm_aff_dashboard_widgets.php' );
    }

    if (file_exists(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_banner.php') && $arm_affiliate->is_version_compatible() ){
        require_once(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_aff_banner.php' );
    }

    if (file_exists(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_affiliate_migration.php') && $arm_affiliate->is_version_compatible() ){
        require_once(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_affiliate_migration.php' );
    }

    if (file_exists(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_affiliate_commision_setup.php') && $arm_affiliate->is_version_compatible() ){
        require_once(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_affiliate_commision_setup.php' );
    }

    if($arm_affiliate->arm_affiliate_is_woocommerce_active() && $arm_affiliate->arm_affiliate_enabled_integration( 'woocommerce' ) ){
        if (file_exists(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_affiliate_woocommerce.php') && $arm_affiliate->is_version_compatible() ){
            require_once(ARM_AFFILIATE_CLASSES_DIR . '/class.arm_affiliate_woocommerce.php' );
        }
    }
}

global $arm_affiliate;
global $armaff_api_url, $armaff_plugin_slug;

$armaff_api_url = $arm_affiliate->armaff_getapiurl();
$armaff_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armaff_check_for_plugin_update');

if( $arm_affiliate->is_version_compatible() && version_compare($arm_affiliate->get_armember_version(), '2.2', '<=') ) {
    global $arm_default_user_details_text;
    $arm_default_user_details_text = __('Unknown', 'ARM_AFFILIATE');
}

function armaff_check_for_plugin_update($checked_data) {
    global $armaff_api_url, $armaff_plugin_slug, $wp_version, $arm_affiliate_version,$arm_affiliate;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armaff_plugin_slug,
        'version' => $arm_affiliate_version,
        'other_variables' => $arm_affiliate->armaff_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMAFFILIATE-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armaff_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armaffiliate_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armaff_plugin_slug . '/' . $armaff_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armaff_plugin_api_call', 10, 3);

function armaff_plugin_api_call($def, $action, $args) {
    global $armaff_plugin_slug, $armaff_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armaff_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armaff_plugin_slug . '/' . $armaff_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armaffiliate_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMAFFILIATE-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armaff_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', 'ARM_AFFILIATE'), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', 'ARM_AFFILIATE'), $request['body']);
    }

    return $res;
}  