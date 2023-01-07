<?php 
if(!isset($_SESSION)) 
{ @session_start(); }
//@error_reporting(E_ALL);
@error_reporting(E_ERROR | E_WARNING | E_PARSE);
/*
Plugin Name: ARMember - WooCommerce Discount Addon
Description: Extension for ARMember plugin to integration with WooCommerce for give discount on product to user membership wise.
Version: 1.0
Plugin URI: https://www.armemberplugin.com
Author: Repute Infosystems
Author URI: https://www.armemberplugin.com
*/

define( 'ARM_WD_DIR_NAME', 'armemberwoocommerce' );
define( 'ARM_WD_DIR', WP_PLUGIN_DIR . '/' . ARM_WD_DIR_NAME );

if (is_ssl()) {
    define( 'ARM_WD_URL', str_replace( 'http://', 'https://', WP_PLUGIN_URL . '/' . ARM_WD_DIR_NAME ) );
} else {
    define( 'ARM_WD_URL', WP_PLUGIN_URL . '/' . ARM_WD_DIR_NAME );
}

define('ARM_WD_TEXTDOMAIN', 'ARM_WD');

define( 'ARM_WD_CORE_DIR', ARM_WD_DIR . '/core/' );
define( 'ARM_WD_CLASSES_DIR', ARM_WD_CORE_DIR . 'classes/' );
define( 'ARM_WD_VIEW_DIR', ARM_WD_CORE_DIR . 'views/' );

define( 'ARM_WD_IMAGES_URL', ARM_WD_URL . '/images/' );

global $arm_wd_version;
$arm_wd_version = '1.0';

global $arm_wd_woocommerce_path; 
$arm_wd_woocommerce_path = 'woocommerce/woocommerce.php';

global $arm_wd;
$arm_wd = new ARM_WD();

if ( file_exists( ARM_WD_CLASSES_DIR . 'class.arm_wd_front_product.php' ) && $arm_wd->arm_wd_is_compatible() ) {
    require_once( ARM_WD_CLASSES_DIR . 'class.arm_wd_front_product.php' );
}

if ( file_exists( ARM_WD_CLASSES_DIR . 'class.arm_wd_product.php' ) && $arm_wd->arm_wd_is_compatible() && $arm_wd->arm_wd_is_page('product') ) {
    require_once( ARM_WD_CLASSES_DIR . 'class.arm_wd_product.php' );
}

if ( file_exists( ARM_WD_CLASSES_DIR . 'class.arm_wd_category.php' ) && $arm_wd->arm_wd_is_compatible() && $arm_wd->arm_wd_is_page('category') ) {
    require_once( ARM_WD_CLASSES_DIR . 'class.arm_wd_category.php' );
}

class ARM_WD
{
    var $arm_wd_tab_title;
    function __construct(){

        $this->arm_wd_tab_title = __('ARMember Plan wise Discount', ARM_WD_TEXTDOMAIN);

        add_action( 'init', array( $this, 'arm_wd_db_check' ) );

        register_activation_hook( __FILE__, array( 'ARM_WD', 'install' ) );

        register_uninstall_hook( __FILE__, array( 'ARM_WD', 'uninstall' ) );

        add_action( 'admin_notices', array( $this, 'arm_wd_admin_notices' ) );

        add_action( 'plugins_loaded', array( $this, 'arm_wd_load_textdomain' ) );

        if ($this->arm_wd_is_compatible()){
            add_action( 'admin_enqueue_scripts', array( $this, 'arm_wd_scripts' ), 20 );
        }
    }

    public static function arm_wd_db_check() {
        global $arm_wd; 
        $arm_wd_version = get_option( 'arm_woocommerce_discount_version' );

        if ( !isset( $arm_wd_version ) || $arm_wd_version == '' )
            $arm_wd->install();
    }
	
    public static function install() {
        global $arm_wd, $wpdb, $arm_wd_version;
        
        $arm_wd_db_version = get_option( 'arm_woocommerce_discount_version' );

        if ( !isset( $arm_wd_db_version ) || $arm_wd_db_version == '' ) {

            update_option( 'arm_woocommerce_discount_version', $arm_wd_version );

        }
    }

    public static function uninstall() {
    	global $wpdb;
        $wpdb->query("DELETE FROM `".$wpdb->postmeta."` WHERE  `meta_key` LIKE  'arm_wd%'");
        $wpdb->query("DELETE FROM `".$wpdb->termmeta."` WHERE  `meta_key` LIKE  'arm_wd%'");
        delete_option( 'arm_woocommerce_discount_version' );
    }

    function arm_wd_admin_notices() {
        global $pagenow, $arm_slugs;
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if( !$this->arm_wd_is_armember_support() ) :
                echo "<div class='updated updated_notices'><p>" . __('ARMember - WooCommerce Discount plugin requires ARMember Plugin installed and active.', ARM_WD_TEXTDOMAIN) . "</p></div>";
            elseif ( !$this->arm_wd_is_version_compatible() ) :
                echo "<div class='updated updated_notices'><p>" . __('ARMember - WooCommerce Discount plugin requires ARMember plugin installed with version 2.0 or higher.', ARM_WD_TEXTDOMAIN) . "</p></div>";
            endif;


            if( !( $this->arm_wd_is_woocommerce_support() && $this->arm_wd_is_woocommerce_version_compatible() ) )
            {
                if( !$this->arm_wd_is_woocommerce_support() )
                    echo "<div class='updated updated_notices'><p>" . __('You must install and activate WooCommerce 3.0.2 or higher to use ARMember - WooCommerce Discount Addon.', ARM_WD_TEXTDOMAIN) . "</p></div>";

                else if( !$this->arm_wd_is_woocommerce_version_compatible() )
                    echo "<div class='updated updated_notices'><p>" . __('ARmember - WooCommerce Discount Add-on requires WooCommerce 3.0.2 or higher. Please update WooCommerce.', ARM_WD_TEXTDOMAIN) . "</p></div>";
            }

        }
    }

    function arm_wd_is_armember_support() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        return is_plugin_active( 'armember/armember.php' );
    }

    function arm_wd_get_armember_version() {
        $arm_db_version = get_option( 'arm_version' );

        return ( isset( $arm_db_version ) ) ? $arm_db_version : 0;
    }

    function armwd_getapiurl() 
    {
        $api_url = 'https://www.arpluginshop.com/';
        return $api_url;
    }

    

    function armwd_get_remote_post_params($plugin_info = "") {
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
                if (strpos(strtolower($plugin["Title"]), "armemberwoocommerce") !== false) {
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

    function arm_wd_is_version_compatible() {
        if ( !version_compare( $this->arm_wd_get_armember_version(), '2.0', '>=' ) || !$this->arm_wd_is_armember_support() ) :
            return false;
        else : 
            return true;
        endif;
    }

    function arm_wd_is_woocommerce_support() {
        global $arm_wd_woocommerce_path;
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        return is_plugin_active( $arm_wd_woocommerce_path );
    }

    function arm_wd_get_woocommerce_version() {
        global $arm_wd_woocommerce_path;
        $woocommerce_path = WP_PLUGIN_DIR . '/' . $arm_wd_woocommerce_path;
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $woocommerce_plugin_data = get_plugin_data( $woocommerce_path, false, false );
        return $woocommerce_plugin_data['Version'];
    }

    function arm_wd_is_woocommerce_version_compatible() {
        if ( !version_compare( $this->arm_wd_get_woocommerce_version(), '3.0.2', '>=' ) || !$this->arm_wd_is_woocommerce_support() ) :
            return false;
        else : 
            return true;
        endif;
    }

    function arm_wd_is_compatible() {
        if( $this->arm_wd_is_armember_support() && $this->arm_wd_is_version_compatible() && $this->arm_wd_is_woocommerce_support() && $this->arm_wd_is_woocommerce_version_compatible() ) :
            return true;
        else :
            return false;
        endif;
    }

    function arm_wd_load_textdomain() {
        load_plugin_textdomain(ARM_WD_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    function arm_wd_is_page( $arm_wd_page = '' ) {
        $return = false;

        if( isset( $arm_wd_page) && $arm_wd_page != '' )
        {
            switch ($arm_wd_page) {
                case 'product':
                    if( (isset($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'product') || ( isset( $_GET['post'] ) ) ) { 
                        $return = true;
                    }
                    break;
                
                case 'category':
                    if( (isset($_REQUEST['taxonomy']) && $_REQUEST['taxonomy'] == 'product_cat') || isset( $_GET['tag_ID'] ) ) {
                        $return = true;
                    }
                    break;

                default:
                    $return = false;
                    break;
            }
        }
        return $return;
    }

    function arm_wd_scripts() {
        global $arm_wd_version;
        if( $this->arm_wd_is_compatible() && ( $this->arm_wd_is_page( 'product' ) || $this->arm_wd_is_page( 'category' ) ) ) {
            // load in woocommerce product page and category page.
            wp_register_style('arm_wd_admin_css', ARM_WD_URL . '/css/arm_wd_admin.css', array(), $arm_wd_version);
            wp_enqueue_style('arm_wd_admin_css');
        }
    }

    // Product Meta Add / Edit / Delete
    function arm_wd_meta_add_update_product_plans( $post_id='', $post_meta_value=array() ) {
        if( $post_id != '' ) { 
            update_post_meta($post_id, 'arm_wd_plans', $post_meta_value);
        }
    }

    function arm_wd_meta_get_product_plans( $post_id='' ) {
        if( $post_id != '' ) { 
            $arm_product_plans = get_post_meta($post_id, 'arm_wd_plans');
        }
        return isset($arm_product_plans[0]) ? $arm_product_plans[0] : array();
    }

    function arm_wd_meta_add_update_plan_discount( $post_id='', $plan_id='', $plan_discount_type='fix', $plan_amount='' ) {
        if( $post_id != '' && $plan_id != '' ){
            $arm_discount = array('arm_discount_type' => $plan_discount_type, 'arm_amount' => $plan_amount);
            $arm_discount_type = update_post_meta($post_id, 'arm_wd_discount_'.$plan_id, $arm_discount);
        }
    }

    function arm_wd_meta_get_plan_discount( $post_id='',  $plan_id='' ) {
        if( $post_id != '' && $plan_id != '' ){
            $plan_discount = get_post_meta($post_id, 'arm_wd_discount_'.$plan_id);
        }
        return isset($plan_discount[0]) ? $plan_discount[0] : array();
    }

    function arm_wd_meta_delete_product( $post_id = '' ) {
        if( $post_id != '' ){
            $plan_ids = $this->arm_wd_meta_get_product_plans($post_id);
            foreach ($plan_ids as $plan_id) {
                delete_post_meta($post_id, 'arm_wd_discount_'.$plan_id);
            }
            delete_post_meta($post_id, 'arm_wd_plans');
        }
    }

    // Category Meta Add / Edit / Delete
    function arm_wd_meta_add_update_cat_plans( $term_id='', $term_meta_value=array() ) {
        if( $term_id != '' ) { 

            $test = update_term_meta($term_id, 'arm_wd_plans', $term_meta_value);
        }
    }

    function arm_wd_meta_get_cat_plans( $term_id='' ) {
        if( $term_id != '' ) { 
            $arm_term_plans = get_term_meta( $term_id, 'arm_wd_plans' );
        }
        return isset($arm_term_plans[0]) ? $arm_term_plans[0] : array();
    }

    function arm_wd_meta_add_update_cat_plan_discount( $term_id='', $plan_id='', $plan_discount_type='fix', $plan_amount='' ) {
        if( $term_id != '' && $plan_id != '' ){
            $arm_discount = array('arm_discount_type' => $plan_discount_type, 'arm_amount' => $plan_amount);
            $arm_discount_type = update_term_meta($term_id, 'arm_wd_discount_'.$plan_id, $arm_discount);
        }
    }

    function arm_wd_meta_get_cat_plan_discount( $term_id='',  $plan_id='' ) {
        if( $term_id != '' && $plan_id != '' ){
            $arm_term_plans = get_term_meta( $term_id, 'arm_wd_discount_'.$plan_id );
        }
        return isset($arm_term_plans[0]) ? $arm_term_plans[0] : array();
    }

    function arm_wd_meta_delete_cat( $term_id = '' ) {
        if( $term_id != '' ){
            $plan_ids = $this->arm_wd_meta_get_cat_plans($term_id);
            foreach ($plan_ids as $plan_id) {
                delete_term_meta($term_id, 'arm_wd_discount_'.$plan_id);
            }
            delete_term_meta($term_id, 'arm_wd_plans');
        }
    }
} 

global $arm_wd;
global $armwd_api_url, $armwd_plugin_slug;

$armwd_api_url = $arm_wd->armwd_getapiurl();
$armwd_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armwd_check_for_plugin_update');

function armwd_check_for_plugin_update($checked_data) {
    global $armwd_api_url, $armwd_plugin_slug, $wp_version, $arm_wd_version,$arm_wd;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armwd_plugin_slug,
        'version' => $wp_version,
        'other_variables' => $arm_wd->armwd_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMWOOCOMMERCE-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armwd_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armwoocommerce_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armwd_plugin_slug . '/' . $armwd_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armwd_plugin_api_call', 10, 3);

function armwd_plugin_api_call($def, $action, $args) {
    global $armwd_plugin_slug, $armwd_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armwd_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armwd_plugin_slug . '/' . $armwd_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armwoocommerce_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMWOOCOMMERCE-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armwd_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', ARM_WD_TEXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', ARM_WD_TEXTDOMAIN), $request['body']);
    }

    return $res;
}   
