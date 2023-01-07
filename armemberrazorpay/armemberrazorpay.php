<?php 
/*
  Plugin Name: ARMember - Razorpay payment gateway Addon
  Description: Extension for ARMember plugin to accept payments using Razorpay.
  Version: 1.0
  Plugin URI: https://www.armemberplugin.com
  Author: Repute InfoSystems
  Author URI: https://www.armemberplugin.com
 */

define('ARM_RAZORPAY_DIR_NAME', 'armemberrazorpay');
define('ARM_RAZORPAY_DIR', WP_PLUGIN_DIR . '/' . ARM_RAZORPAY_DIR_NAME);

if (is_ssl()) {
    define('ARM_RAZORPAY_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_RAZORPAY_DIR_NAME));
} else {
    define('ARM_RAZORPAY_URL', WP_PLUGIN_URL . '/' . ARM_RAZORPAY_DIR_NAME);
}

define('ARM_RAZORPAY_DOC_URL', ARM_RAZORPAY_URL . '/documentation/index.html#content');

global $arm_razorpay_version;
$arm_razorpay_version = '1.0';

global $armnew_razorpay_version;


require_once __DIR__.'/razorpay-sdk/Razorpay.php';
use Razorpay\Api\Api;

global $armrazorpay_api_url, $armrazorpay_plugin_slug, $wp_version;

class ARM_Razorpay{
    
    function __construct() {
        global $arm_payment_gateways, $arm_transaction;
        $arm_payment_gateways->currency['razorpay'] = $this->arm_razorpay_currency_symbol();

        add_action('init', array(&$this, 'arm_razorpay_db_check'));

        register_activation_hook(__FILE__, array('ARM_Razorpay', 'install'));

        register_activation_hook(__FILE__, array('ARM_Razorpay', 'arm_razorpay_check_network_activation'));

        register_uninstall_hook(__FILE__, array('ARM_Razorpay', 'uninstall'));

        add_filter('arm_get_payment_gateways', array(&$this, 'arm_add_razorpay_payment_gateways'));
        add_filter('arm_get_payment_gateways_in_filters', array(&$this, 'arm_add_razorpay_payment_gateways'));
        add_action('admin_notices', array(&$this, 'arm_razorpay_admin_notices'));
        add_filter('arm_change_payment_gateway_tooltip', array(&$this, 'arm_change_payment_gateway_tooltip_func'), 10, 3);
        add_filter('arm_gateway_callback_info', array(&$this, 'arm_gateway_callback_info_func'), 10, 3);
        add_filter('arm_filter_gateway_names', array(&$this, 'arm_filter_gateway_names_func'), 10);
        //add_filter('arm_payment_gateway_has_plan_field_outside', array(&$this, 'arm_payment_gateway_has_plan_field_outside_func'), 10, 6);
        
        add_filter('arm_set_gateway_warning_in_plan_with_recurring', array(&$this, 'arm_razorpay_recurring_trial'), 10);
        add_filter('arm_allowed_payment_gateways', array(&$this, 'arm_payment_allowed_gateways'), 10, 3);
        add_action('arm_payment_related_common_message', array(&$this, 'arm_payment_related_common_message'), 10);

        add_filter('arm_currency_support', array(&$this, 'arm_razorpay_currency_support'), 10, 2);
        add_filter('arm_not_display_payment_mode_setup', array(&$this, 'arm_not_display_payment_mode_setup_func'), 10, 1);

        add_action('arm_after_payment_gateway_listing_section', array(&$this, 'arm_after_payment_gateway_listing_section_func'), 10, 2);

        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_script'), 10);
        add_action('wp_head', array(&$this, 'arm_razorpay_set_front_js'), 10);
        
        add_action('plugins_loaded', array(&$this, 'arm_razorpay_load_textdomain'));
        
        add_action('admin_init', array(&$this, 'upgrade_data_razorpay'));
        
        add_filter('arm_change_coupon_code_outside_from_razorpay',array(&$this,'arm_razorpay_modify_coupon_code'),10,5);
        
        add_filter('arm_change_pending_gateway_outside',array(&$this,'arm2_change_pending_gateway_outside'),100,3);
        
        add_filter('arm_membership_update_user_meta_from_outside', array(&$this, 'arm2_membership_razorpay_update_usermeta'), 10, 5);
        
        add_action('arm_update_user_meta_after_renew_outside', array(&$this, 'arm2_razorpay_update_meta_after_renew'), 10, 4);
        
        add_filter('arm_default_plan_array_filter', array(&$this, 'arm2_default_plan_array_filter_func'), 10, 1);
        
        add_filter('arm_need_to_cancel_old_subscription_gateways', array(&$this, 'arm2_need_to_cancel_old_subscription_gateways'), 10, 1);
        
        add_action('arm_payment_gateway_validation_from_setup', array(&$this, 'arm2_payment_gateway_form_submit_action'), 10, 4);
        
        add_action('wp', array(&$this, 'arm2_razorpay_response'), 5);
        
        add_action('arm_cancel_subscription_gateway_action', array(&$this, 'arm2_razorpay_cancel_subscription'), 10, 2);

        add_action('arm_enqueue_js_css_from_outside', array(&$this,'arm_enqueue_razorpay_js_css_for_model'),10);


    }
    
    

    function arm2_need_to_cancel_old_subscription_gateways( $payment_gateway_array ) {
        array_push($payment_gateway_array, 'razorpay');
        return $payment_gateway_array;
    }
    
    function arm2_default_plan_array_filter_func( $default_plan_array ) {
        global $ARMember;
        $default_plan_array['arm_razorpay'] = '';
        return $default_plan_array;
    }
    
    function arm2_membership_razorpay_update_usermeta($posted_data, $user_id, $plan, $log_detail, $pgateway) {
        if ($pgateway == 'razorpay') {
            $posted_data['arm_razorpay'] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
        }
        return $posted_data;
    }
    
    function arm2_razorpay_update_meta_after_renew($user_id, $log_detail, $plan_id, $payment_gateway) {
        global $ARMember;
        if ($payment_gateway == 'razorpay') {
            if ($user_id != '' && !empty($log_detail) && $plan_id != '' && $plan_id != 0) {
                global $arm_subscription_plans;
                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $plan_data = get_user_meta($user_id, 'arm_user_plan_' . $plan_id, true);
                $plan_data = !empty($plan_data) ? $plan_data : array();
                $plan_data = shortcode_atts($defaultPlanData, $plan_data);
                $pg_subsc_data = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
                $plan_data['arm_2checkout'] = '';
                $plan_data['arm_authorize_net'] = '';
                $plan_data['arm_stripe'] = '';
                $plan_data['arm_razorpay'] = $pg_subsc_data;
                update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $plan_data);
            }
        }
    }
    
    function arm_razorpay_load_textdomain() {
        load_plugin_textdomain('ARM_RAZORPAY', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public static function arm_razorpay_db_check() {
        global $arm_razorpay;
        $arm_razorpay_version = get_option('arm_razorpay_version');

        if (!isset($arm_razorpay_version) || $arm_razorpay_version == '')
            $arm_razorpay->install();
    }

    function armrazorpay_getapiurl() {
            $api_url = 'https://www.arpluginshop.com/';
            return $api_url;
        }
        
    function upgrade_data_razorpay() {
        global $armnew_razorpay_version;

        if (!isset($armnew_razorpay_version) || $armnew_razorpay_version == "")
            $armnew_razorpay_version = get_option('arm_razorpay_version');

        /*
        if (version_compare($armnew_razorpay_version, '1.0', '<')) {
            $path = ARM_RAZORPAY_DIR . '/upgrade_latest_data_razorpay.php';
            include($path);
        }
        */
    }
    
    function armrazorpay_get_remote_post_params($plugin_info = "") {
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
                if (strpos(strtolower($plugin["Title"]), "armemberrazorpay") !== false) {
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
            
    public static function install() {
        global $arm_razorpay;
        $arm_razorpay_version = get_option('arm_razorpay_version');

        if (!isset($arm_razorpay_version) || $arm_razorpay_version == '') {
            global $wpdb, $arm_razorpay_version;
            update_option('arm_razorpay_version', $arm_razorpay_version);
        }
    }

    
    /*
     * Restrict Network Activation
     */
    public static function arm_razorpay_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }
    
    public static function uninstall() {
        delete_option('arm_razorpay_version');
    }

    function arm_razorpay_currency_symbol() {
        $currency_symbol = array(
            'INR' => '&#8377;',
            'USD' => '&#36;',
            'AED' => '&#1583;.&#1573;',
            'ALL' => '&#76;&#101;&#107;',
            'AMD' => '&#1423; &#x58F;',
            'ARS' => '&#36;',
            'AWG' => '&#402;',
            'BBD' => '&#36;',
            'BDT' => '&#2547;',
            'BMD' => '&#36;',
            'BND' => '&#36;',
            'BOB' => '&#36;&#98;',
            'BSD' => '&#36;',
            'BWP' => '&#80;',
            'BZD' => '&#66;&#90;&#36;',
            'CAD' => '&#36;',
            'CHF' => '&#67;&#72;&#70;',
            'CNY' => '&#165;',
            'COP' => '&#36;',
            'CRC' => '&#8353;',
            'CUP' => '&#8369;',
            'CZK' => '&#75;&#269;',
            'DKK' => '&#107;&#114;',
            'DOP' => '&#82;&#68;&#36;',
            'DZD' => '&#x20ac;',
            'EGP' => '&#163;',
            'ETB' => 'ETB',
            'EUR' => '&#8364;',
            'FJD' => '&#36;',
            'GBP' => '&#163;',
            'GIP' => '&#163;',
            'GMD' => 'GMD',
            'GTQ' => '&#81;',
            'GYD' => '&#36;',
            'HKD' => '&#36;',
            'HNL' => '&#76;',
            'HRK' => '&#107;&#110;',
            'HTG' => 'HTG',
            'HUF' => '&#70;&#116;',
            'IDR' => '&#82;&#112;',
            'ILS' => '&#8362;',
            'JMD' => '&#74;&#36;',
            'KES' => 'KES',
            'KGS' => '&#1083;&#1074;',
            'KHR' => '&#6107;',
            'KYD' => '&#36;',
            'KZT' => '&#1083;&#1074;',
            'LAK' => '&#8365;',
            'LBP' => '&#163;',
            'LKR' => '&#8360;',
            'LRD' => '&#36;',
            'LSL' => 'LSL',
            'MAD' => 'MAD',
            'MDL' => 'MDL',
            'MKD' => '&#1076;&#1077;&#1085;',
            'MMK' => 'MMK',
            'MNT' => '&#8366;',
            'MOP' => 'MOP',
            'MUR' => '&#8360;',
            'MVR' => 'MVR',
            'MWK' => 'MWK',
            'MXN' => '&#36;',
            'MYR' => '&#82;&#77;',
            'NAD' => '&#36;',
            'NGN' => '&#8358;',
            'NIO' => '&#67;&#36;',
            'NOK' => '&#107;&#114;',
            'NPR' => '&#8360;',
            'NZD' => '&#36;',
            'PEN' => '&#83;&#47;&#46;',
            'PGK' => 'PGK',
            'PHP' => '&#8369;',
            'PKR' => '&#8360;',
            'QAR' => '&#65020;',
            'RUB' => '&#1088;&#1091;&#1073;',
            'SAR' => '&#65020;',
            'SCR' => '&#8360;',
            'SEK' => '&#107;&#114;',
            'SGD' => '&#36;',
            'SLL' => 'SLL',
            'SOS' => '&#83;',
            'SSP' => 'SSP',
            'SVC' => '&#36;',
            'SZL' => 'SZL',
            'THB' => '&#3647;',
            'TTD' => '&#84;&#84;&#36;',
            'TZS' => 'TZS',
            'UYU' => '&#36;&#85;',
            'UZS' => '&#1083;&#1074;',
            'YER' => '&#65020;',
            'ZAR' => '&#82;',
        );
        return $currency_symbol;
    }

    function arm_add_razorpay_payment_gateways($default_payment_gateways) {
        if ($this->is_version_compatible()) {
            global $arm_payment_gateways;
            $default_payment_gateways['razorpay']['gateway_name'] = __('Razorpay', 'ARM_RAZORPAY');
            return $default_payment_gateways;
        } else {
            return $default_payment_gateways;
        }
    }

    function arm_razorpay_admin_notices() {
        global $pagenow, $arm_slugs;    
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if (!$this->is_armember_support())
                echo "<div class='updated updated_notices'><p>" . __('Razorpay For ARMember plugin requires ARMember Plugin installed and active.', 'ARM_RAZORPAY') . "</p></div>";

            else if (!$this->is_version_compatible())
                echo "<div class='updated updated_notices'><p>" . __('Razorpay For ARMember plugin requires ARMember plugin installed with version 3.0 or higher.', 'ARM_RAZORPAY') . "</p></div>";
        }
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
        if (!version_compare($this->get_armember_version(), '3.0', '>=') || !$this->is_armember_support()) :
            return false;
        else :
            return true;
        endif;
    }

    function arm_change_payment_gateway_tooltip_func($titleTooltip, $gateway_name, $gateway_options) {
        if ($gateway_name == 'razorpay') {
            return __("You can find merchant key id and merchant key secret in your Razorpay account. To get more details, Please refer this", 'ARM_RAZORPAY')." <a href='https://www.razorpay.com/merchant-dashboard/#/integration' target='_blank'>".__("document", 'ARM_RAZORPAY')."</a>.";
        }
        return $titleTooltip;
    }
    
    function arm_gateway_callback_info_func($apiCallbackUrlInfo, $gateway_name, $gateway_options) {
        if ($gateway_name == 'razorpay') {
            global $arm_global_settings;
            $apiCallbackUrl = $arm_global_settings->add_query_arg("arm-listener", "arm_razorpay_api", get_home_url() . "/");
            
            $apiCallbackUrlInfo = '<a href="'. ARM_RAZORPAY_DOC_URL .'" target="_blank">'.__('ARMember Razorpay Documentation', 'ARM_RAZORPAY').'</a>';
            
        }
        return $apiCallbackUrlInfo;
    }

    function arm_filter_gateway_names_func($pgname) {
        $pgname['razorpay'] = __('Razorpay', 'ARM_RAZORPAY');
        return $pgname;
    }

    function arm2_change_pending_gateway_outside($user_pending_pgway,$plan_ID,$user_id){
        global $is_free_manual,$ARMember;
        if( $is_free_manual ){
            $key = array_search('razorpay',$user_pending_pgway);
            unset($user_pending_pgway[$key]);
        }
        return $user_pending_pgway;
    }
    
    function admin_enqueue_script(){
        global $arm_razorpay_version,$arm_slugs;
        if(!empty($arm_slugs->general_settings))
        {
            $arm_razorpay_page_array = array($arm_slugs->general_settings);
            $arm_razorpay_action_array = array('payment_options');
            if( isset($_REQUEST['page']) && isset($_REQUEST['action']) && in_array($_REQUEST['page'], $arm_razorpay_page_array) && in_array($_REQUEST['action'], $arm_razorpay_action_array) ||  (isset($_REQUEST['page']) && $_REQUEST['page']==$arm_slugs->membership_setup)) {
                wp_register_script( 'arm-admin-razorpay', ARM_RAZORPAY_URL . '/js/arm_admin_razorpay.js', array(), $arm_razorpay_version );
                wp_enqueue_script( 'arm-admin-razorpay' );
                wp_register_style('arm-admin-razorpay-css', ARM_RAZORPAY_URL . '/css/arm_admin_razorpay.css', array(), $arm_razorpay_version);
                wp_enqueue_style('arm-admin-razorpay-css');    
            }
        }        
    }
    
    function arm_razorpay_set_front_js( $force_enqueue = false ) {
        if( $this->is_version_compatible() ){
            global $ARMember, $arm_razorpay_version;
            $is_arm_front_page = $ARMember->is_arm_front_page();
            if ($is_arm_front_page === TRUE || $force_enqueue == TRUE){
                wp_register_script('arm_razorpay_js', ARM_RAZORPAY_URL . '/js/arm_razorpay.js', array('jquery'), $arm_razorpay_version);
                wp_enqueue_script('arm_razorpay_js');
            }
        }
    }

    function arm_enqueue_razorpay_js_css_for_model(){
        $this->arm_razorpay_set_front_js(true);
    }

    function arm_razorpay_recurring_trial($notice) {
        // if need to display any notice related subscription in Add / Edit plan page
        if ($this->is_version_compatible()){
            $notice .= "<span style='margin-bottom:10px;'><b>". __('Razorpay (if Razorpay payment gateway is enabled)','ARM_RAZORPAY')."</b><br/>";
            $notice .= "<ol style='margin-left:30px;'>";
            $notice .= "<li>".__('Razorpay does not support auto debit recurring billing cycle.','ARM_RAZORPAY')."</li>";
            $notice .= "</ol>";
            $notice .= "</span>";
        } 
        return $notice;
    }

    function arm_payment_allowed_gateways($allowed_gateways, $plan_obj, $plan_options) {
        $allowed_gateways['razorpay'] = "1";
        return $allowed_gateways;
    }

    function arm_payment_related_common_message($common_messages) {
        if ($this->is_version_compatible()) {
            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_payment_fail_razorpay"><?php _e('Payment Fail (Razorpay)', 'ARM_RAZORPAY'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_payment_fail_razorpay]" id="arm_payment_fail_razorpay" value="<?php echo (!empty($common_messages['arm_payment_fail_razorpay']) ) ? $common_messages['arm_payment_fail_razorpay'] : __('Sorry something went wrong while processing payment with Razorpay.', 'ARM_RAZORPAY'); ?>" />
                </td>
            </tr>
            <?php
        }
    }

    function arm_payment_gateway_has_ccfields_func($pgHasCcFields, $gateway_name, $gateway_options) {
        if ($gateway_name == 'razorpay') {
            return true;
        } else {
            return $pgHasCcFields;
        }
    }

    function arm_razorpay_currency_support($notAllow, $currency) {
        global $arm_payment_gateways;
        if (!array_key_exists($currency, $arm_payment_gateways->currency['razorpay'])) {
            $notAllow[] = 'razorpay';
        }
        return $notAllow;
    }

    function arm_not_display_payment_mode_setup_func($gateway_name_arr) {
        //for remove auto debit payment and menual payment option from front side page and admin site. Its allow only manual payment.
        $gateway_name_arr[] = 'razorpay';
        return $gateway_name_arr;
    }

    function arm_after_payment_gateway_listing_section_func($gateway_name, $gateway_options) {
        // set paymetn geteway setting field in general settgin > payment gateway
        global $arm_global_settings;
        if ($gateway_name == 'razorpay') {
            $gateway_options['razorpay_payment_mode'] = (!empty($gateway_options['razorpay_payment_mode']) ) ? $gateway_options['razorpay_payment_mode'] : 'sandbox';
            $gateway_options['status'] = isset($gateway_options['status']) ? $gateway_options['status'] : 0;
            $disabled_field_attr = ($gateway_options['status'] == '1') ? '' : 'disabled="disabled"';
            $readonly_field_attr = ($gateway_options['status'] == '1') ? '' : 'readonly="readonly"';
            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Payment Mode', 'ARM_RAZORPAY'); ?> *</label></th>
                <td class="arm-form-table-content">
                    <input id="arm_razorpay_payment_gateway_mode_sand" class="arm_general_input arm_razorpay_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="sandbox" name="payment_gateway_settings[razorpay][razorpay_payment_mode]" <?php checked($gateway_options['razorpay_payment_mode'], 'sandbox'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_razorpay_payment_gateway_mode_sand"><?php _e('Sandbox', 'ARM_RAZORPAY'); ?></label>
                    <input id="arm_razorpay_payment_gateway_mode_pro" class="arm_general_input arm_razorpay_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="live" name="payment_gateway_settings[razorpay][razorpay_payment_mode]" <?php checked($gateway_options['razorpay_payment_mode'], 'live'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_razorpay_payment_gateway_mode_pro"><?php _e('Live', 'ARM_RAZORPAY'); ?></label>
                </td>
            </tr>
            <!-- ***** Begining of Sandbox Input for razorpay ***** -->
            <?php
            $razorpay_hidden = "hidden_section";
            if (isset($gateway_options['razorpay_payment_mode']) && $gateway_options['razorpay_payment_mode'] == 'sandbox') {
                $razorpay_hidden = "";
            } else if (!isset($gateway_options['razorpay_payment_mode'])) {
                $razorpay_hidden = "";
            }
            ?>
            <tr class="form-field arm_razorpay_sandbox_fields <?php echo $razorpay_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Sandbox Merchant Key ID', 'ARM_RAZORPAY'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_razorpay_sandbox_merchant_key" name="payment_gateway_settings[razorpay][razorpay_sandbox_merchant_key]" value="<?php echo (!empty($gateway_options['razorpay_sandbox_merchant_key'])) ? $gateway_options['razorpay_sandbox_merchant_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_razorpay_sandbox_fields <?php echo $razorpay_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Sandbox Merchant Key Secret', 'ARM_RAZORPAY'); ?> *</th> 
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_razorpay_sandbox_secret" name="payment_gateway_settings[razorpay][razorpay_sandbox_secret]" value="<?php echo (!empty($gateway_options['razorpay_sandbox_secret'])) ? $gateway_options['razorpay_sandbox_secret'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            
            <!-- ***** Ending of Sandbox Input for razorpay ***** -->

            <!-- ***** Begining of Live Input for razorpay ***** -->
            <?php
            $razorpay_live_fields = "hidden_section";
            if (isset($gateway_options['razorpay_payment_mode']) && $gateway_options['razorpay_payment_mode'] == "live") {
                $razorpay_live_fields = "";
            }
            ?>
            <tr class="form-field arm_razorpay_fields <?php echo $razorpay_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live Merchant Key ID', 'ARM_RAZORPAY'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_razorpay_live_merchant_key" name="payment_gateway_settings[razorpay][razorpay_merchant_key]" value="<?php echo (!empty($gateway_options['razorpay_merchant_key'])) ? $gateway_options['razorpay_merchant_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_razorpay_fields <?php echo $razorpay_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live Merchant Key Secret', 'ARM_RAZORPAY'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_razorpay_live_token" name="payment_gateway_settings[razorpay][razorpay_secret]" value="<?php echo (!empty($gateway_options['razorpay_secret'])) ? $gateway_options['razorpay_secret'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>

            <!-- ***** Ending of Live Input for razorpay ***** -->

            <tr class="form-field">
                <th class="arm-form-table-label"><?php _e('Select Field for send Buyer Contact Number to Razorpay', 'ARM_RAZORPAY'); ?> *</th> 
                <td class="arm-form-table-content">

                    <?php 
                        $form_fields = array();
                        $get_form_fields = get_option('arm_preset_form_fields');
                        $unserialize_form_fields = maybe_unserialize($get_form_fields);
                        if(!empty($unserialize_form_fields['default']) && !empty($unserialize_form_fields['other']))
                        {
                            $form_fields = array_merge($unserialize_form_fields['default'],$unserialize_form_fields['other']);
                        }
                        else if(!empty($unserialize_form_fields['default'])) {
                            $form_fields = $unserialize_form_fields['default'];
                        }
                    ?>

                    <input type="hidden" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_razorpay_live_contact" name="payment_gateway_settings[razorpay][razorpay_contact]" value="<?php echo (!empty($gateway_options['razorpay_contact'])) ? $gateway_options['razorpay_contact'] : ''; ?>" <?php echo $readonly_field_attr; ?> />

                    <dl class="arm_selectbox column_level_dd">
                        <dt><span>Select field</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                        <dd>
                            <ul data-id="arm_razorpay_live_contact">
                                <?php foreach ($form_fields as $key => $value): ?>
                                <li data-label="<?php echo $value['label'];?>" data-value="<?php echo esc_attr($key);?>"><?php echo $value['label']; ?></li>
                                <?php endforeach;?>
                            </ul>
                        </dd>
                    </dl>
                </td>
            </tr>
            
            

            <?php
        }
    }

    function arm_razorpay_config() {
        global $arm_payment_gateways, $arm_global_settings;

        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        if (isset($all_payment_gateways['razorpay']) && !empty($all_payment_gateways['razorpay'])) {
            $payment_gateway_options = $all_payment_gateways['razorpay'];
            $ARM_Razorpay_payment_mode = $payment_gateway_options['razorpay_payment_mode'];
            $is_sandbox_mode = $ARM_Razorpay_payment_mode == "sandbox" ? true : false;

            $RazorpayConfig = array();

            $RazorpayConfig['environment'] = ( $is_sandbox_mode ) ? "sandbox" : "live"; // live, sandbox

            $RazorpayConfig['credentials'] = array();
            $RazorpayConfig['credentials']['merchant_key_id'] = ( $is_sandbox_mode ) ? $payment_gateway_options['razorpay_sandbox_merchant_key_id'] : $payment_gateway_options['razorpay_merchant_key_id'];
            $RazorpayConfig['credentials']['merchant_key_secret'] = ( $is_sandbox_mode ) ? $payment_gateway_options['razorpay_sandbox_merchant_key_secret'] : $payment_gateway_options['razorpay_merchant_key_secret'];

            $RazorpayConfig['application'] = array();
            $RazorpayConfig['application']['charset'] = "UTF-8"; // UTF-8, ISO-8859-1

            $RazorpayConfig['log'] = array();
            $RazorpayConfig['log']['active'] = false;
            
            $RazorpayConfig['log']['fileLocation'] = "";

            return $RazorpayConfig;
        }
    }

    
    function arm2_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {

        global $wpdb, $ARMember, $arm_global_settings, $arm_membership_setup, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $paid_trial_stripe_payment_done, $is_free_manual;
        
        $is_free_manual = false;

        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        $currency = $arm_payment_gateways->arm_get_global_currency();
        if ($payment_gateway == 'razorpay' && isset($all_payment_gateways['razorpay']) && !empty($all_payment_gateways['razorpay'])) 
        {
            $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);

            $gateway_options = get_option('arm_payment_gateway_settings');
            $pgoptions = maybe_unserialize($gateway_options);
            $arm_razorpay_payment_mode = isset($pgoptions['razorpay']['razorpay_payment_mode']) ? $pgoptions['razorpay']['razorpay_payment_mode'] : 'sandbox';
            $arm_razorpay_key      = ($arm_razorpay_payment_mode=='sandbox') ? $pgoptions['razorpay']['razorpay_sandbox_merchant_key'] : $pgoptions['razorpay']['razorpay_merchant_key'];
            $arm_razorpay_secret   = ($arm_razorpay_payment_mode=='sandbox') ? $pgoptions['razorpay']['razorpay_sandbox_secret'] : $pgoptions['razorpay']['razorpay_secret'];
            $arm_razorpay_contact   = !empty($pgoptions['razorpay']['razorpay_contact']) ? $pgoptions['razorpay']['razorpay_contact'] : '';


            $current_payment_gateway = (isset($posted_data['payment_gateway'])) ? $posted_data['payment_gateway'] : '';
            if ($current_payment_gateway == '') 
            {
                $current_payment_gateway = (isset($posted_data['_payment_gateway'])) ? $posted_data['_payment_gateway'] : '';
            }
            
            if (!empty($entry_data) && $current_payment_gateway == $payment_gateway) 
            {
                $payment_mode_ = !empty($posted_data['arm_payment_mode']['razorpay']) ? $posted_data['arm_payment_mode']['razorpay'] : 'both';

                
                $recurring_payment_mode = 'manual_subscription';
                
                if ($payment_mode_ == 'both') 
                {
                    $recurring_payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : 'manual_subscription';
                } else {
                    $recurring_payment_mode = $payment_mode_;
                }


                $form_id = $entry_data['arm_form_id'];
                $user_id = $entry_data['arm_user_id'];
                
                $entry_values = $entry_data['arm_entry_value'];

                $payment_cycle = $entry_values['arm_selected_payment_cycle']; 
                $tax_percentage =  isset($entry_values['tax_percentage']) ? $entry_values['tax_percentage'] : 0;
                $arm_user_old_plan = (isset($entry_values['arm_user_old_plan']) && !empty($entry_values['arm_user_old_plan'])) ? explode(",",$entry_values['arm_user_old_plan']) : array();
                $setup_id = (isset($entry_values['setup_id']) && !empty($entry_values['setup_id'])) ? $entry_values['setup_id'] : 0 ;
                $user_email_add = $entry_data['arm_entry_email'];
                if (is_user_logged_in()) {
                    $user_obj = get_user_by( 'ID', $user_id);
                    $user_name = $user_obj->first_name." ".$user_obj->last_name;
                    $user_email_add = $user_obj->user_email;
                }else { 
                    $user_name = $entry_data['arm_entry_value']['first_name']." ".$entry_data['arm_entry_value']['last_name'];
                }
                
                $plan_id = (!empty($posted_data['subscription_plan'])) ? $posted_data['subscription_plan'] : 0;
                if ($plan_id == 0) {
                    $plan_id = (!empty($posted_data['_subscription_plan'])) ? $posted_data['_subscription_plan'] : 0;
                }
                
                $plan_action = 'new_subscription';
                $oldPlanIdArray = (isset($posted_data['old_plan_id']) && !empty($posted_data['old_plan_id'])) ? explode(",", $posted_data['old_plan_id']) : 0;
                $plan = new ARM_Plan($plan_id);
                
                $plan_id = $plan->ID;
                $plan_payment_type = $plan->payment_type;

                $is_recurring = $plan->is_recurring();

                if ($is_recurring) 
                {
                    $setup_id = $posted_data['setup_id'];
                    $payment_mode_ = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : 'manual_subscription';
                        if(isset($posted_data['arm_payment_mode']['razorpay'])){
                            $payment_mode_ = !empty($posted_data['arm_payment_mode']['razorpay']) ? $posted_data['arm_payment_mode']['razorpay'] : 'manual_subscription';

                            $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);


                            /*
                            if($recurring_payment_mode == 'auto_debit_subscription')
                            {

                                if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
                                     $payment_cycle_key = $plan->options['payment_cycles'][$payment_cycle]['cycle_key'];
                                     $RazorpayPlanID = $setup_data['setup_modules']['modules']['razorpay_plans'][$plan_id][$payment_cycle_key];
                                }
                                
                                $recurring_data = $plan->prepare_recurring_data($payment_cycle);
                                $amount = $recurring_data['amount'] * 100;
                                $period = $recurring_data['period'];
                                $interval = $recurring_data['interval'];

                                switch ($period) {
                                    case 'D':
                                        $period = 'daily';
                                        break;

                                    case 'M':
                                        $period = 'monthly';
                                        break;

                                    case 'Y':
                                        $period = 'yearly';
                                        break;
                                    
                                    default:
                                        $period = $period;
                                        break;
                                }

                                $plan_data = $wpdb->get_row("SELECT * FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_id`='".$plan_id."'", ARRAY_A);

                                $plan_post_array = array('period' =>  $period, 'interval' =>  $interval, 'item' => array('name' => $plan_data['arm_subscription_plan_name'] , 'amount' => $amount , 'currency' => $currency) );

                                $plan_post_json = json_encode($plan_post_array);
                                $razorpay_auth = base64_encode( $arm_razorpay_key . ':' . $arm_razorpay_secret );
                                $razorpay_args = [
                                    'headers' => [
                                        'Authorization' => "Basic $razorpay_auth"
                                    ],   
                                ];  
                                $result      = wp_remote_get( 'https://api.razorpay.com/v1/plans', $razorpay_args );
                                $result_obj = json_decode($result);
                                
                                if (!is_wp_error($result)) {
                                    if(!empty($result_obj->items))
                                    {
                                        foreach ($result_obj->items as $key => $value) {

                                            $item = $value->item;

                                            if ($interval == $value->interval and $period == $value->period and $amount == $item->amount) {
                                                $exist_plan_id = $value->id;                                    
                                            }
                                        }
                                    }
                                }

                                if (empty($exist_plan_id)) {


                                    $razorpay_args = [
                                        'headers' => [
                                            'Authorization' => "Basic $razorpay_auth",
                                            'content-type'  => 'application/json',
                                        ],
                                        'body'    => $plan_post_json,   
                                    ];  
                                    $result1      = wp_remote_post( 'https://api.razorpay.com/v1/plans', $razorpay_args );
                                    if(!is_wp_error($result1))
                                    {
                                        $result_obj1 = json_decode($result1['body']);
                                        $razorpay_plan_id = $result_obj1->id;
                                    }
                                    
                                }else{
                                    $razorpay_plan_id = $exist_plan_id;
                                }

                                 
                                $cus_contact = $entry_data['arm_entry_value'][$arm_razorpay_contact];
                                $cus_array = array('name' => $entry_values['first_name'], 'email' => $entry_values['user_email'], 'contact' => $cus_contact, 'fail_existing' => 0);
                                $cus_obj = json_encode($cus_array);

                                $razorpay_args = [
                                    'headers' => [
                                        'Authorization' => "Basic $razorpay_auth",
                                        'content-type'  => 'application/json',
                                    ],
                                    'body'    => $cus_obj,   
                                ];  
                                $result3      = wp_remote_post( 'https://api.razorpay.com/v1/customers', $razorpay_args );
                                if(!is_wp_error($result3))
                                {
                                    $result3_obj = json_decode($result3['body']);

                                }
                                
                                
                                $subs_post_array = array('plan_id' => $RazorpayPlanID , 'total_count' => $interval , 'customer_id' => $result3_obj->id);
                                $subs_post_json = json_encode($subs_post_array);

                                $razorpay_args = [
                                    'headers' => [
                                        'Authorization' => "Basic $razorpay_auth",
                                        'content-type'  => 'application/json',
                                    ],
                                    'body'    => $subs_post_json,   
                                ];  
                                $result2      = wp_remote_post( 'https://api.razorpay.com/v1/subscriptions', $razorpay_args );
                                if(!is_wp_error($result2))
                                {
                                    $result_obj2 = json_decode($result2['body']);
                                }
                                
                                
                            }
                            */
                        }
                        else{
                            $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                            if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
                                $setup_modules = $setup_data['setup_modules'];
                                $modules = $setup_modules['modules'];
                                $payment_mode_ = $modules['payment_mode']['razorpay'];
                            }
                        }


                        $payment_mode = 'manual_subscription';
                        if ($payment_mode_ == 'both') {
                            $payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : 'manual_subscription';
                        } else {
                            $payment_mode = $payment_mode_;
                        }
                        $payment_mode = 'manual_subscription';
                }
                else{
                    $payment_mode = '';
                }
                
                if (!empty($oldPlanIdArray)) {
                    if (in_array($plan_id, $oldPlanIdArray)) {
                        $plan_action = 'renew_subscription';
                        $is_recurring_payment = $arm_subscription_plans->arm_is_recurring_payment_of_user($user_id, $plan_id, $payment_mode);
                        if($is_recurring_payment){
                            $plan_action = 'recurring_payment';
                            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                            $oldPlanDetail = $planData['arm_current_plan_detail'];
                            if (!empty($oldPlanDetail)) {
                                $plan = new ARM_Plan(0);
                                $plan->init((object) $oldPlanDetail);
                            }
                        }
                    }
                    else{
                        $plan_action = 'change_subscription';
                    }
                }
               

                
                $plan_name = !empty($plan->name) ? $plan->name : "Plan Name";
                $recurring_data = '';
                if($plan->is_recurring()) {
                    $recurring_data = $plan->prepare_recurring_data($payment_cycle);
                    $amount = $recurring_data['amount'];
                } else {
                    $amount = !empty($plan->amount) ? $plan->amount : 0;
                }
                
                $amount = str_replace(",", "", $amount);
                $amount = number_format((float)$amount, 2, '.','');
                
                $iscouponfeature = false;
                $arm_is_trial = '0';
                $extraParam = array();
                if ($plan_action == 'new_subscription') {
                    $is_trial = false;
                    $allow_trial = true;
                    if (is_user_logged_in()) {
                        $user_id = get_current_user_id();
                        $user_plan = get_user_meta($user_id, 'arm_user_plan_ids', true);
                        $user_plan_id = $user_plan;
                        if (!empty($user_plan)) {
                            $allow_trial = false;
                        }
                    }
                    if ($plan->has_trial_period() && $allow_trial) {
                        $is_trial = true;
                        $arm_is_trial = '1';
                        $amount = $plan->options['trial']['amount'];
                        $trial_period = $plan->options['trial']['period'];
                        $trial_interval = $plan->options['trial']['interval'];
                    }
                } 
                    
                    $arm_coupon_discount_type = '';
                    $arm_coupon_discount = 0;
                    $discount_amt = $amount;
                    $extraParam = array('plan_amount' => $amount, 'paid_amount' => $amount);
                    $arm_coupon_on_each_subscriptions = 0;
                    if ($arm_manage_coupons->isCouponFeature && isset($posted_data['arm_coupon_code']) && !empty($posted_data['arm_coupon_code'])) {
                        $couponApply = $arm_manage_coupons->arm_apply_coupon_code($posted_data['arm_coupon_code'], $plan, $setup_id, $payment_cycle, $arm_user_old_plan);

                        if($couponApply["status"] == "success") {
                            $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                            $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                            $arm_coupon_discount = (isset($couponApply['discount']) && !empty($couponApply['discount'])) ? $couponApply['discount'] : 0;
                            $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $currency : "%";
                            $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : '0';
                            
                            $extraParam['coupon'] = array(
                                'coupon_code' => $posted_data['arm_coupon_code'],
                                'amount' => $coupon_amount,
                                'arm_coupon_on_each_subscriptions' => $arm_coupon_on_each_subscriptions
                            );

                        }
                    } else {
                        $posted_data['arm_coupon_code'] = '';
                    }
                

                $discount_amt = str_replace(",", "", $discount_amt);

                if($tax_percentage > 0){
                    $tax_amount =($amount*$tax_percentage)/100;
                    $tax_amount = number_format((float)$tax_amount, 2, '.','');
                    $amount = $amount+$tax_amount;

                    $tax_discount_amt =($discount_amt*$tax_percentage)/100;
                    $tax_discount_amt = number_format((float)$tax_discount_amt, 2, '.','');
                    $discount_amt = $discount_amt+$tax_discount_amt;
                  
                }
                $amount = number_format((float)$amount, 2, '.','');
                $discount_amt = number_format((float)$discount_amt, 2, '.','');
                
                $arm_redirecturl = $entry_values['setup_redirect'];
                
                if (empty($arm_redirecturl)) {
                    $arm_redirecturl = ARM_HOME_URL;
                }


                
                $arm_razorpay_notificationurl = '';
                $arm_razorpay_notificationurl = $arm_global_settings->add_query_arg("arm-listener", "arm_razorpay_api", get_home_url() . "/");

                if ((($discount_amt <= 0 || $discount_amt == '0.00') && $recurring_payment_mode == 'manual_subscription' && $plan->is_recurring()) || (!$plan->is_recurring() && ($discount_amt <= 0 || $discount_amt == '0.00'))) 
                {
                    
                    global $payment_done;
                    $razorpay_response = array();
                    $current_user_id = 0;
                    if (is_user_logged_in()) {
                        $current_user_id = get_current_user_id();
                        $razorpay_response['arm_user_id'] = $current_user_id;
                    }
                    $arm_first_name=(isset($posted_data['first_name']))?$posted_data['first_name']:'';
                    $arm_last_name=(isset($posted_data['last_name']))?$posted_data['last_name']:'';
                    if($user_id){
                        if(empty($arm_first_name)){
                            $user_detail_first_name = get_user_meta( $user_id, 'first_name');
                            $arm_first_name=$user_detail_first_name;
                        }
                        if(empty($arm_last_name)){
                            $user_detail_last_name = get_user_meta( $user_id, 'last_name');
                            $arm_last_name=$user_detail_last_name;
                        }
                    }
                    $razorpay_response['arm_plan_id'] = $plan->ID;
                    $razorpay_response['arm_first_name']=$arm_first_name;
                    $razorpay_response['arm_last_name']=$arm_last_name;
                    $razorpay_response['arm_payment_gateway'] = 'razorpay';
                    $razorpay_response['arm_payment_type'] = $plan->payment_type;
                    $razorpay_response['arm_token'] = '-';
                    $razorpay_response['arm_payer_email'] = $user_email_add;
                    $razorpay_response['arm_receiver_email'] = '';
                    $razorpay_response['arm_transaction_id'] = '-';
                    $razorpay_response['arm_transaction_payment_type'] = $plan->payment_type;
                    $razorpay_response['arm_transaction_status'] = 'completed';
                    $razorpay_response['arm_payment_mode'] = 'manual_subscription';
                    $razorpay_response['arm_payment_date'] = date('Y-m-d H:i:s');
                    $razorpay_response['arm_amount'] = $amount;
                    $razorpay_response['arm_currency'] = $currency;
                    $razorpay_response['arm_coupon_code'] = $posted_data['arm_coupon_code'];
                    $razorpay_response['arm_response_text'] = '';
                    $razorpay_response['arm_extra_vars'] = '';
                    $razorpay_response['arm_is_trial'] = $arm_is_trial;
                    $razorpay_response['arm_created_date'] = current_time('mysql');
                    $razorpay_response['arm_coupon_discount'] = $arm_coupon_discount;
                    $razorpay_response['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                    $razorpay_response['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;

                    $payment_log_id = $arm_payment_gateways->arm_save_payment_log($razorpay_response);
                    $return = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                    $payment_done = $return;
                    $is_free_manual = true;

                    if($arm_manage_coupons->isCouponFeature && !empty($posted_data['arm_coupon_code']) && !empty($arm_coupon_on_each_subscriptions)) {
                            $payment_done["coupon_on_each"] = TRUE;
                            $payment_done["trans_log_id"] = $payment_log_id;
                    }

                    do_action('arm_after_razorpay_free_payment',$plan,$payment_log_id,$arm_is_trial,$posted_data['arm_coupon_code'],$extraParam);

                    return $return;
                }
                else
                {
                    $extraVars['paid_amount'] = $amount;
                    $data_array['currency'] = $currency;
                    $data_array['arm_plan_id'] = $plan_id;
                    $data_array['arm_plan_name'] = $plan_name;
                    $data_array['arm_plan_amount'] = $discount_amt;
                    $data_array['reference'] = 'ref-' . $entry_id;
                    $data_array['redirect_url'] = $arm_redirecturl;
                    $data_array['notification_url'] = $arm_razorpay_notificationurl;
                    
                    if($pgoptions['razorpay']['razorpay_payment_mode'] == 'sandbox')
                    {
                        $data_array['arm_razorpay_status_mode'] = 1;
                        $data_array['arm_razorpay_marchant_id'] = $pgoptions['razorpay']['razorpay_sandbox_merchant_key'];
                        $data_array['arm_razorpay_secret_key'] = $pgoptions['razorpay']['razorpay_sandbox_secret'];
                        
                    }
                    else
                    {
                        $data_array['arm_razorpay_status_mode'] = 2;
                        $data_array['arm_razorpay_marchant_id'] = $pgoptions['razorpay']['razorpay_merchant_key'];
                        $data_array['arm_razorpay_secret_key'] = $pgoptions['razorpay']['razorpay_secret'];
                    }

                    $data_array['arm_razorpay_contact'] = $pgoptions['razorpay']['razorpay_contact'];

                    if(!empty($user_obj))
                    {
                        $arm_razorpay_contact = get_user_meta($user_id, $data_array['arm_razorpay_contact'], true);
                        
                        $data_array['first_name'] = $user_obj->first_name;
                        $data_array['last_name'] = $user_obj->last_name;
                        $data_array['contact'] = $arm_razorpay_contact;
                    }
                    else 
                    {
                        $data_array['first_name'] = $entry_data['arm_entry_value']['first_name'];
                        $data_array['last_name'] = $entry_data['arm_entry_value']['last_name'];
                        $data_array['contact'] = isset($entry_data['arm_entry_value'][$data_array['arm_razorpay_contact']]) ? $entry_data['arm_entry_value'][$data_array['arm_razorpay_contact']] : '';
                    }

                    if(empty($data_array['contact']))
                    {
                        //$return = array('status' => 'error', 'message' => $razorpayform);
                        global $payment_done;
                        $err_msg = __('Sorry, Phone number details is empty. Please add phone number.', 'ARM_RAZORPAY');

                        return $payment_done = array('status' => FALSE, 'error' => $err_msg);
                    }
                    
                    $data_array['user_email'] = $user_email_add;
                    $data_array['plan_action'] = $plan_action;
                    $data_array['subscription_id'] = isset($subscription_id) ? $subscription_id : '';
                    $data_array['payment_mode'] = $recurring_payment_mode;

                    $createpaymentrequest = new CreateRazorpayPaymentRequest();
                    $razorpayform = $createpaymentrequest->main($data_array);

                    if (isset($posted_data['action']) && in_array($posted_data['action'], array('arm_shortcode_form_ajax_action', 'arm_membership_setup_form_ajax_action'))) {

                        global $payment_done;
                        if(isset($payment_done['status']) && $payment_done['status']==FALSE)
                        {
                            return $return = array('status' => FALSE, 'message' => $payment_done['error']);
                        }
                        else 
                        {
                            $return = array('status' => 'success', 'type' => 'redirect', 'message' => $razorpayform);
                            echo json_encode($return);
                            exit;
                        }
                        
                    }
                }
                

                $extraVars['paid_amount'] = $discount_amt;
                $data_array['currency'] = $currency;
                $data_array['arm_plan_id'] = $plan_id;
                $data_array['arm_plan_name'] = $plan_name;
                $data_array['arm_plan_amount'] = $discount_amt;
                $data_array['reference'] = 'ref-' . $entry_id;
                $data_array['redirect_url'] = $arm_redirecturl;
                $data_array['notification_url'] = $arm_razorpay_notificationurl;

                
                
                if($pgoptions['razorpay']['razorpay_payment_mode']=='sandbox')
                {
                    $data_array['arm_razorpay_status_mode'] = 1;
                    $data_array['arm_razorpay_marchant_id'] = $pgoptions['razorpay']['razorpay_sandbox_merchant_key'];
                    $data_array['arm_razorpay_secret_key'] = $pgoptions['razorpay']['razorpay_sandbox_secret'];
                }
                else
                {
                    $data_array['arm_razorpay_status_mode'] = 2;
                    $data_array['arm_razorpay_marchant_id'] = $pgoptions['razorpay']['razorpay_merchant_key'];
                }
                $data_array['arm_razorpay_contact'] = $pgoptions['razorpay']['razorpay_contact'];

                if(!empty($user_obj))
                {
                    $arm_razorpay_contact = get_user_meta($user_id, $data_array['arm_razorpay_contact'], true);
                    
                    $data_array['first_name'] = $user_obj->first_name;
                    $data_array['last_name'] = $user_obj->last_name;
                    $data_array['contact'] = $arm_razorpay_contact;
                }
                else 
                {
                    $data_array['first_name'] = $entry_data['arm_entry_value']['first_name'];
                    $data_array['last_name'] = $entry_data['arm_entry_value']['last_name'];
                    $data_array['contact'] = isset($entry_data['arm_entry_value'][$data_array['arm_razorpay_contact']]) ? $entry_data['arm_entry_value'][$data_array['arm_razorpay_contact']] : '';
                }
                $data_array['user_email'] = $user_email_add;
                $data_array['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                $data_array['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                $razorpay_response['arm_coupon_discount'] = $arm_coupon_discount;  


                $createpaymentrequest = new CreateRazorpayPaymentRequest();
                $razorpayform = $createpaymentrequest->main($data_array);

                if (isset($posted_data['action']) && in_array($posted_data['action'], array('arm_shortcode_form_ajax_action', 'arm_membership_setup_form_ajax_action'))) {
                    
                    global $payment_done;
                    if(isset($payment_done['status']) && $payment_done['status']==FALSE)
                    {
                        return $return = array('status' => FALSE, 'error' => $payment_done['error']);
                    }
                    else 
                    {
                        $return = array('status' => 'success', 'type' => 'redirect', 'message' => $razorpayform);
                        
                    }
                    echo json_encode($return);
                    exit;
                }
                
            } else {
                
            }
        } else {
            
        }
    }

    function arm2_razorpay_response($transaction_id = 0, $arm_listener = '', $tran_id = '') { 

        if (isset($_REQUEST['arm-listener']) && in_array($_REQUEST['arm-listener'], array('arm_razorpay_api'))) {

            global $wpdb, $ARMember, $arm_payment_gateways, $arm_subscription_plans, $arm_manage_coupons;
            
            $ARMember->arm_write_response("repute log razorpay 1 => ".maybe_serialize($_REQUEST));

            if (!empty($_REQUEST['razorpay_payment_id'])) 
            {   

                $razorpay_payment_id = $_REQUEST['razorpay_payment_id'];

                try {
                    $razorpayresponse  = $_POST;

                    $gateway_options    = get_option('arm_payment_gateway_settings');
                    $pgoptions          = maybe_unserialize($gateway_options);
                    $arm_razorpay_payment_mode = isset($pgoptions['razorpay']['razorpay_payment_mode']) ? $pgoptions['razorpay']['razorpay_payment_mode'] : 'sandbox';
                    $arm_razorpay_key      = ($arm_razorpay_payment_mode=='sandbox') ? $pgoptions['razorpay']['razorpay_sandbox_merchant_key'] : $pgoptions['razorpay']['razorpay_merchant_key'];
                    $arm_razorpay_secret   = ($arm_razorpay_payment_mode=='sandbox') ? $pgoptions['razorpay']['razorpay_sandbox_secret'] : $pgoptions['razorpay']['razorpay_secret'];

                    $api = new Api($arm_razorpay_key, $arm_razorpay_secret);

                    $razorpay_payment_response = $api->payment->fetch($razorpay_payment_id);

                    $ARMember->arm_write_response("repute log razorpay verify response => ".maybe_serialize($razorpay_payment_response));
                                        
                    $payment_id = isset($razorpay_payment_response['id']) ? $razorpay_payment_response['id'] : '';
                    $entity = isset($razorpay_payment_response['entity']) ? $razorpay_payment_response['entity'] : '';
                    $amount = isset($razorpay_payment_response['amount']) ? $razorpay_payment_response['amount'] : '';
                    $currency = isset($razorpay_payment_response['currency']) ? $razorpay_payment_response['currency'] : '';
                    $status = isset($razorpay_payment_response['status']) ? $razorpay_payment_response['status'] : '';
                    $order_id = isset($razorpay_payment_response['order_id']) ? $razorpay_payment_response['order_id'] : '';
                    $invoice_id = isset($razorpay_payment_response['invoice_id']) ? $razorpay_payment_response['invoice_id'] : '';
                    $international = isset($razorpay_payment_response['international']) ? $razorpay_payment_response['international'] : '';
                    $method = isset($razorpay_payment_response['method']) ? $razorpay_payment_response['method'] : '';
                    $amount_refunded = isset($razorpay_payment_response['amount_refunded']) ? $razorpay_payment_response['amount_refunded'] : '';
                    $refund_status = isset($razorpay_payment_response['refund_status']) ? $razorpay_payment_response['refund_status'] : '';
                    $captured = isset($razorpay_payment_response['captured']) ? $razorpay_payment_response['captured'] : '';
                    $description = isset($razorpay_payment_response['description']) ? $razorpay_payment_response['description'] : '';
                    $card_id = isset($razorpay_payment_response['card_id']) ? $razorpay_payment_response['card_id'] : '';
                    $bank = isset($razorpay_payment_response['bank']) ? $razorpay_payment_response['bank'] : '';
                    $wallet = isset($razorpay_payment_response['wallet']) ? $razorpay_payment_response['wallet'] : '';
                    $vpa = isset($razorpay_payment_response['vpa']) ? $razorpay_payment_response['vpa'] : '';
                    $email = isset($razorpay_payment_response['email']) ? $razorpay_payment_response['email'] : '';
                    $contact = isset($razorpay_payment_response['contact']) ? $razorpay_payment_response['contact'] : '';
                    $fee = isset($razorpay_payment_response['fee']) ? $razorpay_payment_response['fee'] : '';
                    $tax = isset($razorpay_payment_response['tax']) ? $razorpay_payment_response['tax'] : '';
                    $reference  = isset($razorpay_payment_response['notes']['udf1']) ? $razorpay_payment_response['notes']['udf1'] : '';
                    $redirect_url = isset($razorpay_payment_response['notes']['udf2']) ? $razorpay_payment_response['notes']['udf2'] : '';
                    $plan_action  = isset($razorpay_payment_response['notes']['udf3']) ? $razorpay_payment_response['notes']['udf3'] : '';
                    $txnid  = isset($razorpay_payment_response['notes']['txnid']) ? $razorpay_payment_response['notes']['txnid'] : '' ;  
                    $created_at = isset($razorpay_payment_response['created_at']) ? $razorpay_payment_response['created_at'] : current_time('timestamp');

                    if($status=='captured' || $status=='authorized')
                    {

                        if (!empty($_POST['razorpay_subscription_id'])) 
                        {

                            $gateway_options = get_option('arm_payment_gateway_settings');
                            $pgoptions = maybe_unserialize($gateway_options);
                            $arm_razorpay_payment_mode = isset($pgoptions['razorpay']['razorpay_payment_mode']) ? $pgoptions['razorpay']['razorpay_payment_mode'] : 'sandbox';
                            $arm_razorpay_key      = ($arm_razorpay_payment_mode=='sandbox') ? $pgoptions['razorpay']['razorpay_sandbox_merchant_key'] : $pgoptions['razorpay']['razorpay_merchant_key'];
                            $arm_razorpay_secret   = ($arm_razorpay_payment_mode=='sandbox') ? $pgoptions['razorpay']['razorpay_sandbox_secret'] : $pgoptions['razorpay']['razorpay_secret'];

                            $razorpay_sub_id = isset($_POST['razorpay_subscription_id']) ? $_POST['razorpay_subscription_id'] : '';

                            $razorpay_auth = base64_encode( $arm_razorpay_key . ':' . $arm_razorpay_secret );
                            $razorpay_args = [
                                'headers' => [
                                    'Authorization' => "Basic $razorpay_auth"
                                ],   
                            ];  
                            $result1      = wp_remote_get( 'https://api.razorpay.com/v1/subscriptions/'.$razorpay_sub_id, $razorpay_args );
                            $result1_obj = json_decode($result1);
                            

                            $razorpay_payment_response['subs_id'] = isset($result1_obj->id) ? $result1_obj->id : '';
                            $razorpay_payment_response['customer_id'] = isset($result1_obj->customer_id) ? $result1_obj->customer_id : '';
                        }                     
                       
                        
                        // update succesful payment
                        $reference_code = explode('-', $reference);
                        $entry_id = $reference_code[1];
                        $arm_get_payment_log = $wpdb->get_row( $wpdb->prepare( "SELECT arm_log_id FROM `$ARMember->tbl_arm_payment_log` WHERE arm_token = %s", $txnid), ARRAY_A );

                        $arm_log_id = ( isset($arm_get_payment_log['arm_log_id']) && !empty($arm_get_payment_log['arm_log_id']) ) ? $arm_get_payment_log['arm_log_id'] : '';
                        if($arm_log_id == '') {
                            $arm_log_id = $this->arm2_add_user_and_transaction($entry_id, $razorpay_payment_response);
                        }

                        $arm_get_payment_log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$ARMember->tbl_arm_payment_log` WHERE arm_token = %s AND arm_log_id= %s", $txnid, $arm_log_id), ARRAY_A );
                        
                        $ARMember->arm_write_response("repute log Razorpay Gateway : plan_action Details ".maybe_serialize($plan_action));
                        $pgateway = 'razorpay';
                        if(!empty($arm_get_payment_log) && $plan_action=='recurring_payment')
                        {
                            $plan_payment_mode = 'manual_subscription';
                            $user_id = $arm_get_payment_log['arm_user_id'];
                            $plan_id = $arm_get_payment_log['arm_plan_id'];
                            $is_recurring_payment = $arm_subscription_plans->arm_is_recurring_payment_of_user($user_id, $plan_id, $plan_payment_mode);

                            if($is_recurring_payment)
                            {
                                $ARMember->arm_write_response("repute log Razorpay Gateway : Inside IF recurring payment condition is_recurring_payment".$is_recurring_payment.'| PlanID='.$plan_id.' | UserID='.$user_id);
                                do_action('arm_after_recurring_payment_success_outside', $user_id, $plan_id, $pgateway, $plan_payment_mode);
                            }
                            else {
                                $ARMember->arm_write_response("repute log Razorpay Gateway : Inside ELSE recurring payment condition is_recurring_payment".$is_recurring_payment.'| PlanID='.$plan_id.' | UserID='.$user_id);
                            }
                        }
                        else if(!empty($arm_get_payment_log) && $plan_action=='renew_subscription')
                        {
                            $plan_payment_mode = 'manual_subscription';
                            $user_id = $arm_get_payment_log['arm_user_id'];
                            $plan_id = $arm_get_payment_log['arm_plan_id'];
                            
                            if($arm_get_payment_log['arm_coupon_on_each_subscriptions']=='1')
                            {
                                $userPlanData = get_user_meta($user_id, 'arm_user_plan_' . $plan_id, true);
                                $arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $arm_log_id, $pgateway, $userPlanData);
                            }
                        }
                        else {
                            $ARMember->arm_write_response("repute log Razorpay Gateway : Outside (ELSE) recurring payment condition is_recurring_payment".$is_recurring_payment.'| PlanID='.$plan_id.' | UserID='.$user_id);
                        }
                    }
                    
                    if(empty($redirect_url))
                    {
                        header('location: '.ARM_HOME_URL); 
                    }
                    else
                    {
                        header('location: '.$redirect_url); 
                    }
                                     
            }
            catch (Exception $e) {
                $ARMember->arm_write_response("repute log Razorpay Gateway cache error=>".maybe_serialize( $e->getMessage() ) );
            }                  
                 

            }
            else
            {
                header('location: '.ARM_HOME_URL); 
            }
            
        }
    }
    
    

    function arm2_add_user_and_transaction($entry_id = 0, $razorpay_payment_response, $arm_display_log = 1) {


        global $wpdb, $razorpay, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication, $arm_manage_coupons, $payment_done, $paid_trial_stripe_payment_done, $arm_members_class,$arm_transaction, $is_multiple_membership_feature;


        if (isset($entry_id) && $entry_id != '') {

            $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();

            if (isset($all_payment_gateways['razorpay']) && !empty($all_payment_gateways['razorpay'])) {
                $options = $all_payment_gateways['razorpay'];
                $razorpay_payment_mode = $options['razorpay_payment_mode'];
                $is_sandbox_mode = $razorpay_payment_mode == "sandbox" ? true : false;
                $currency = $arm_payment_gateways->arm_get_global_currency();

                $entry_data = $wpdb->get_row("SELECT * FROM `" . $ARMember->tbl_arm_entries . "` WHERE `arm_entry_id`='" . $entry_id . "' ", ARRAY_A);
                
                $entry_values = maybe_unserialize($entry_data['arm_entry_value']);

                $entry_email = isset($entry_data['arm_entry_email']) ? $entry_data['arm_entry_email'] : '';
                $arm_log_plan_id = $entry_data['arm_plan_id'];
                $arm_log_amount = isset($entry_values['arm_total_payable_amount']) ? $entry_values['arm_total_payable_amount'] : '';
                $arm_token = $razorpay_payment_response['id'];
                $plan = new ARM_Plan($arm_log_plan_id);
                $arm_payment_type = $plan->payment_type;
                $entry_id = $entry_id;
                $payment_status = 'success';
                $form_id = $entry_data['arm_form_id'];
                $armform = new ARM_Form('id', $form_id);
                $user_info = get_user_by('email', $entry_email);

                $user_id = isset($user_info->ID) ? $user_info->ID : 0;
                $user_detail_first_name = $user_detail_last_name = "";
                if(!empty($user_id))
                {
                    $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                    $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                }
                
               
                $extraParam = array();
                $tax_percentage = isset($entry_values['tax_percentage']) ? $entry_values['tax_percentage'] : 0;
                $extraParam['tax_percentage'] = $tax_percentage;
                $payment_mode = $entry_values['arm_selected_payment_mode'];
                $payment_cycle = $entry_values['arm_selected_payment_cycle'];
                $arm_user_old_plan = (isset($entry_values['arm_user_old_plan']) && !empty($entry_values['arm_user_old_plan'])) ? explode(",", $entry_values['arm_user_old_plan']) : array();
                $razorpay_subs_id = isset($razorpay_payment_response['subs_id']) ? $razorpay_payment_response['subs_id'] : '';
                $razorpay_customer_id = isset($razorpay_payment_response['customer_id']) ? $razorpay_payment_response['customer_id'] : '';
                $setup_id = $entry_values['setup_id'];
                $entry_plan = $entry_data['arm_plan_id'];
                $razorpayLog = $razorpay_payment_response;
                $razorpayLog['arm_coupon_code'] = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                $razorpayLog['arm_payment_type'] = $arm_payment_type;
                $razorpayLog['payment_type'] = $arm_payment_type;
                $razorpayLog['payment_status'] = $payment_status;
                $razorpayLog['payer_email'] = $entry_email;
                $razorpayLog['arm_first_name']=$user_detail_first_name;
                $razorpayLog['arm_last_name']=$user_detail_last_name;
                $extraParam['payment_type'] = 'razorpay';
                $extraParam['payment_mode'] = $razorpay_payment_mode;
                $extraParam['arm_is_trial'] = '0';
                $extraParam['subs_id'] = $razorpay_subs_id;
                $extraParam['customer_id'] = $razorpay_customer_id;
                $extraParam['trans_id'] = $arm_token;
                $extraParam['card_id'] = $razorpay_payment_response['card_id'];
                $extraParam['error'] = '';
                $extraParam['date'] = current_time('mysql');
                $extraParam['message_type'] = '';

                $amount = '';
                       
                $new_plan = new ARM_Plan($entry_plan);
                
                if ($new_plan->is_recurring() ) {
                    if (in_array($entry_plan, $arm_user_old_plan) && $user_id > 0 ) {
                        $is_recurring_payment = $arm_subscription_plans->arm_is_recurring_payment_of_user($user_id, $entry_plan, $payment_mode);
                        if ($is_recurring_payment) {
                            $planData = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                            $oldPlanDetail = $planData['arm_current_plan_detail'];
                            if (!empty($oldPlanDetail)) {
                                $plan = new ARM_Plan(0);
                                $plan->init((object) $oldPlanDetail);
                                $plan_data = $plan->prepare_recurring_data($payment_cycle);
                                $extraParam['plan_amount'] = $plan_data['amount'];
                            }
                        } else {
                            $plan_data = $new_plan->prepare_recurring_data($payment_cycle);
                            $extraParam['plan_amount'] = $plan_data['amount'];
                        }
                    } else {
                        $plan_data = $new_plan->prepare_recurring_data($payment_cycle);
                        $extraParam['plan_amount'] = $plan_data['amount'];
                    }
                } else {
                    $extraParam['plan_amount'] = $new_plan->amount;
                }
                $extraParam['plan_amount'] = str_replace(',', '', $extraParam['plan_amount']);

                $discount_amt = $extraParam['plan_amount'];
                $arm_coupon_discount = 0;
                //$amount_for_tax = $arm_log_amount;
                $amount_for_tax = $discount_amt;
                $arm_coupon_on_each_subscriptions = 0;
                $couponCode = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                if (!empty($couponCode)) {
                    $couponApply = $arm_manage_coupons->arm_apply_coupon_code($couponCode, $new_plan, $setup_id, $payment_cycle, $arm_user_old_plan);
                    if($couponApply["status"] == "success") {
                        $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                        $coupon_amount = str_replace(',', '', $coupon_amount);

                        $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                        $discount_amt = str_replace(',', '', $discount_amt);

                        $extraParam['coupon'] = array(
                            'coupon_code' => $couponCode,
                            'amount' => $coupon_amount,
                        );

                        $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : 0;

                        $arm_coupon_discount = str_replace(",", "", $couponApply['discount']);
                        $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $global_currency : "%";
                        $razorpayLog['coupon_code'] = $couponCode;
                        $razorpayLog['arm_coupon_discount'] = $arm_coupon_discount;
                        $razorpayLog['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                        $razorpayLog['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                    }
                } 

                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $razorpayLog['currency'] = $currency;
                $razorpayLog['payment_amount'] = $discount_amt;
                if (!$user_info && in_array($armform->type, array('registration'))) {

                    /* Coupon Details */
                    $recurring_data = $new_plan->prepare_recurring_data($payment_cycle);
                    if (!empty($recurring_data['trial'])) {
                        $extraParam['trial'] = array(
                            'amount' => $recurring_data['trial']['amount'],
                            'period' => $recurring_data['trial']['period'],
                            'interval' => $recurring_data['trial']['interval'],
                        );
                        $extraParam['arm_is_trial'] = '1';

                        $amount_for_tax = $recurring_data['trial']['amount'];
                    }

                    if( $arm_coupon_discount > 0){
                       $amount_for_tax = $discount_amt;
                    }

                    $amount_for_tax = str_replace(",", "", $amount_for_tax);

                    $tax_amount = 0;
                    if($tax_percentage > 0){
                         $tax_amount =($amount_for_tax*$tax_percentage)/100;
                            $tax_amount = number_format((float)$tax_amount, 2, '.','');

                            $amount_for_tax = $amount_for_tax+$tax_amount;

                    }

                    $amount_for_tax = number_format((float)$amount_for_tax, 2, '.','');
                    $extraParam['tax_amount'] = $tax_amount;
                    $extraParam['paid_amount'] = $amount_for_tax;
                    $razorpayLog['payment_amount'] = $amount_for_tax;
                    $payment_log_id = self::arm_store_razorpay_log($razorpayLog, 0, $entry_plan, $extraParam, $arm_display_log);
                    $payment_done = array();
                    if ($payment_log_id) {
                        $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                    }
                    $entry_values['payment_done'] = '1';
                    $entry_values['arm_entry_id'] = $entry_id;
                    $entry_values['arm_update_user_from_profile'] = 0;
                    $user_id = $arm_member_forms->arm_register_new_member($entry_values, $armform);

                    if (is_numeric($user_id) && !is_array($user_id)) {
                        if ($arm_payment_type == 'subscription') {

                            $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                            $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                            $userPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);

                            $userPlanData['arm_razorpay']['transaction_id'] = $arm_token;
                            update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                        }
                        update_user_meta($user_id, 'arm_entry_id', $entry_id);
                        /**
                         * Send Email Notification for Successful Payment
                         */
                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $entry_plan, 'user_id' => $user_id, 'action' => 'new_subscription'));
                    }
                } else {
                    $user_id = $user_info->ID;
                    if(!empty($user_id)) {
                        $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                        $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                        $userPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);
                        
                        if (empty($is_multiple_membership_feature->isMultipleMembershipFeature)){

                            $old_plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true);
                            $old_plan_id = isset($old_plan_ids[0]) ? $old_plan_ids[0] : 0;
                            $oldPlanDetail = array();
                            $old_subscription_id = '';
                            if(!empty($old_plan_id) && in_array($entry_plan, $old_plan_ids)){
                                $oldPlanData = get_user_meta($user_id, 'arm_user_plan_'.$old_plan_id, true);
                                $oldPlanData = !empty($oldPlanData) ? $oldPlanData : array();
                                $oldPlanData = shortcode_atts($defaultPlanData, $oldPlanData);
                                $oldPlanDetail = $oldPlanData['arm_current_plan_detail'];
                                $subscr_effective = $oldPlanData['arm_expire_plan'];
                                $old_subscription_id = $oldPlanData['arm_razorpay']['sale_id'];
                            }

                            $arm_user_old_plan_details = (isset($userPlanData['arm_current_plan_detail']) && !empty($userPlanData['arm_current_plan_detail'])) ? $userPlanData['arm_current_plan_detail'] : array();
                            $arm_user_old_plan_details['arm_user_old_payment_mode'] = $userPlanData['arm_payment_mode'];
                            
                            if(!empty($old_subscription_id) && $payment_mode == 'auto_debit_subscription' && $old_subscription_id == $arm_token)
                            {
                                $arm_next_due_payment_date = $userPlanData['arm_next_due_payment'];
                                if(!empty($arm_next_due_payment_date)){
                                    if(strtotime(current_time('mysql')) >= $arm_next_due_payment_date){
                                        $arm_user_completed_recurrence = $userPlanData['arm_completed_recurring'];
                                        $arm_user_completed_recurrence++;
                                        $userPlanData['arm_completed_recurring'] = $arm_user_completed_recurrence;
                                        update_user_meta($user_id, 'arm_user_plan_'.$entry_plan, $userPlanData);
                                        $arm_next_payment_date = $arm_members_class->arm_get_next_due_date($user_id, $entry_plan, false, $payment_cycle);
                                        if ($arm_next_payment_date != '') {
                                            $userPlanData['arm_next_due_payment'] = $arm_next_payment_date;
                                            update_user_meta($user_id, 'arm_user_plan_'.$entry_plan, $userPlanData);
                                        }
                                    }
                                }
                                else{
                                    $now = current_time('mysql');
                                    $arm_last_payment_status = $wpdb->get_var($wpdb->prepare("SELECT `arm_transaction_status` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d AND `arm_created_date`<=%s ORDER BY `arm_log_id` DESC LIMIT 0,1", $user_id, $entry_plan, $now));

                                       if(in_array($arm_last_payment_status, array('success'))){
                                        $arm_user_completed_recurrence = $userPlanData['arm_completed_recurring'];
                                            $arm_user_completed_recurrence++;
                                            $userPlanData['arm_completed_recurring'] = $arm_user_completed_recurrence;
                                            update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                            $arm_next_payment_date = $arm_members_class->arm_get_next_due_date($user_id, $entry_plan, false, $payment_cycle);
                                            if ($arm_next_payment_date != '') {
                                                $userPlanData['arm_next_due_payment'] = $arm_next_payment_date;
                                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                            }
                                        
                                    }
                                }
                                $suspended_plan_ids = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
                                $suspended_plan_id = (isset($suspended_plan_ids) && !empty($suspended_plan_ids)) ? $suspended_plan_ids :  array(); 

                                if(in_array($entry_plan, $suspended_plan_id)){
                                     unset($suspended_plan_id[array_search($entry_plan,$suspended_plan_id)]);
                                     update_user_meta($user_id, 'arm_user_suspended_plan_ids', array_values($suspended_plan_id));
                                }
                            }
                            else
                            {
                                
                                $now = current_time('mysql');
                                $arm_last_payment_status = $wpdb->get_var($wpdb->prepare("SELECT `arm_transaction_status` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d AND `arm_payment_date`<=%s ORDER BY `arm_log_id` DESC LIMIT 0,1", $user_id, $entry_plan, $now)); 

                                $userPlanData['arm_current_plan_detail'] = $arm_user_old_plan_details;
                                
                                $userPlanData['arm_payment_mode'] = $entry_values['arm_selected_payment_mode'];

                                $userPlanData['arm_payment_cycle'] = $entry_values['arm_selected_payment_cycle'];

                                if (!empty($oldPlanDetail)) {
                                    $old_plan = new ARM_Plan(0);
                                    $old_plan->init((object) $oldPlanDetail);
                                } else {
                                    $old_plan = new ARM_Plan($old_plan_id);
                                }
                                $is_update_plan = true;
                                /* Coupon Details */

                                $recurring_data = $new_plan->prepare_recurring_data($payment_cycle);
                                if (!empty($recurring_data['trial']) && empty($arm_user_old_plan)) {
                                    $extraParam['trial'] = array(
                                        'amount' => $recurring_data['trial']['amount'],
                                        'period' => $recurring_data['trial']['period'],
                                        'interval' => $recurring_data['trial']['interval'],
                                    );
                                    $extraParam['arm_is_trial'] = '1';

                                    $amount_for_tax = $recurring_data['trial']['amount'];
                                }

                                if( $arm_coupon_discount > 0){
                                   $amount_for_tax = $discount_amt ;
                                }

                                $tax_amount = 0;
                                if($tax_percentage > 0){
                                     $tax_amount =($amount_for_tax*$tax_percentage)/100;
                                        $tax_amount = number_format((float)$tax_amount, 2, '.','');

                                        $amount_for_tax = $amount_for_tax+$tax_amount;

                                }

                                $amount_for_tax = number_format((float)$amount_for_tax, 2, '.','');
                                $extraParam['tax_amount'] = $tax_amount;
                                $extraParam['paid_amount'] = $amount_for_tax;
                                $razorpayLog['payment_amount'] = $amount_for_tax;
                                if ($old_plan->exists()) {
                                    if ($old_plan->is_lifetime() || $old_plan->is_free() || ($old_plan->is_recurring() && $new_plan->is_recurring())) {
                                        $is_update_plan = true;
                                    } else {
                                        $change_act = 'immediate';
                                        if ($old_plan->enable_upgrade_downgrade_action == 1) {
                                            if (!empty($old_plan->downgrade_plans) && in_array($new_plan->ID, $old_plan->downgrade_plans)) {
                                                $change_act = $old_plan->downgrade_action;
                                            }
                                            if (!empty($old_plan->upgrade_plans) && in_array($new_plan->ID, $old_plan->upgrade_plans)) {
                                                $change_act = $old_plan->upgrade_action;
                                            }
                                        }
                                        if ($change_act == 'on_expire' && !empty($subscr_effective)) {
                                            $is_update_plan = false;
                                            $oldPlanData['arm_subscr_effective'] = $subscr_effective;
                                            $oldPlanData['arm_change_plan_to'] = $entry_plan;
                                            update_user_meta($user_id, 'arm_user_plan_' . $old_plan_id, $oldPlanData);
                                        }
                                    }
                                }

                                update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                $userPlanData['arm_user_gateway'] = 'razorpay';

                                if (!empty($arm_token)) {
                                    $userPlanData['arm_razorpay']['transaction_id'] = $arm_token;
                                }
                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                if ($is_update_plan) {
                                    $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan, '', true, $arm_last_payment_status);
                                } else {
                                    $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'change_subscription');
                                }
                            }
                        }
                        else{
                            
                            $now = current_time('mysql');
                            $arm_last_payment_status = $wpdb->get_var($wpdb->prepare("SELECT `arm_transaction_status` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d AND `arm_payment_date`<=%s ORDER BY `arm_log_id` DESC LIMIT 0,1", $user_id, $entry_plan, $now));
                            $old_plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true);
                            if(in_array($entry_plan, $old_plan_ids)){
                                $oldPlanData = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                                $old_subscription_id = $oldPlanData['arm_razorpay']['transaction_id'];



                                if(empty($old_subscription_id) || empty($arm_token) || $old_subscription_id != $arm_token){

                                    $userPlanData['arm_payment_mode'] = $entry_values['arm_selected_payment_mode'];
                                    $userPlanData['arm_payment_cycle'] = $entry_values['arm_selected_payment_cycle'];

                                    $is_update_plan = true;
                                    /* Coupon Details */

                                    $recurring_data = $new_plan->prepare_recurring_data($payment_cycle);
                                    if (!empty($recurring_data['trial']) && empty($arm_user_old_plan)) {
                                        $extraParam['trial'] = array(
                                            'amount' => $recurring_data['trial']['amount'],
                                            'period' => $recurring_data['trial']['period'],
                                            'interval' => $recurring_data['trial']['interval'],
                                        );
                                        $amount_for_tax = $recurring_data['trial']['amount']; 
                                    }

                                    if($arm_coupon_discount > 0){
                                        $amount_for_tax = $discount_amt;
                                    }

                                    $tax_amount = 0;
                                    if($tax_percentage > 0){
                                         $tax_amount =($amount_for_tax*$tax_percentage)/100;
                                            $tax_amount = number_format((float)$tax_amount, 2, '.','');

                                            $amount_for_tax = $amount_for_tax+$tax_amount;

                                    }

                                    $amount_for_tax = number_format((float)$amount_for_tax, 2, '.','');
                                    $extraParam['tax_amount'] = $tax_amount;
                                    $extraParam['paid_amount'] = $amount_for_tax;
                                    $razorpayLog['payment_amount'] = $amount_for_tax;

                                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                    $userPlanData['arm_user_gateway'] = 'razorpay';

                                    if (!empty($arm_token)) {
                                        $userPlanData['arm_razorpay']['transaction_id'] = $arm_token;
                                    }
                                    update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                    if ($is_update_plan) {
                                        $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan,'', true, $arm_last_payment_status);
                                    } else {
                                       $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'new_subscription');
                                    }
                                }
                            }
                            else{
                                $userPlanData['arm_payment_mode'] = $entry_values['arm_selected_payment_mode'];
                                $userPlanData['arm_payment_cycle'] = $entry_values['arm_selected_payment_cycle'];
                                $is_update_plan = true;
                                /* Coupon Details */
                                $recurring_data = $new_plan->prepare_recurring_data($payment_cycle);
                                if (!empty($recurring_data['trial']) && empty($arm_user_old_plan)) {
                                    $extraParam['trial'] = array(
                                        'amount' => $recurring_data['trial']['amount'],
                                        'period' => $recurring_data['trial']['period'],
                                        'interval' => $recurring_data['trial']['interval'],
                                    );
                                    $amount_for_tax = $recurring_data['trial']['amount'];
                                }

                                if($arm_coupon_discount > 0){
                                    $amount_for_tax = $discount_amt;
                                }

                                $tax_amount = 0;
                                    if($tax_percentage > 0){
                                         $tax_amount =($amount_for_tax*$tax_percentage)/100;
                                            $tax_amount = number_format((float)$tax_amount, 2, '.','');

                                            $amount_for_tax = $amount_for_tax+$tax_amount;

                                    }

                                    $amount_for_tax = number_format((float)$amount_for_tax, 2, '.','');
                                    $extraParam['tax_amount'] = $tax_amount;
                                    $extraParam['paid_amount'] = $amount_for_tax;
                                    $razorpayLog['payment_amount'] = $amount_for_tax;

                                update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                $userPlanData['arm_user_gateway'] = 'razorpay';

                                if (!empty($arm_token)) {
                                    $userPlanData['arm_razorpay']['transaction_id'] = $arm_token;
                                }
                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                if ($is_update_plan) {
                                    $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan,  '', true, $arm_last_payment_status);
                                } else {
                                   $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'new_subscription');
                                }
                            }
                        }
                        


                        $payment_log_id = self::arm_store_razorpay_log($razorpayLog, $user_id, $entry_plan, $extraParam, $arm_display_log);
                    }
                }
                return $payment_log_id;
            }
        }
    }
    
    function arm_store_razorpay_log($razorpay_response = '', $user_id = 0, $plan_id = 0, $extraVars = array(), $arm_display_log = '1') {

        global $wpdb, $ARMember, $arm_global_settings, $arm_member_forms, $arm_payment_gateways;
        $payment_log_table = $ARMember->tbl_arm_payment_log;
        $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $razorpay_response['id']));
        if (!empty($razorpay_response) && empty($transaction)) {
            $payment_data = array(
                'arm_user_id' => $user_id,
                'arm_first_name'=>$razorpay_response['arm_first_name'],
                'arm_last_name'=>$razorpay_response['arm_last_name'],
                'arm_plan_id' => (!empty($plan_id) ? $plan_id : 0),
                'arm_payment_gateway' => 'razorpay',
                'arm_payment_type' => $razorpay_response['arm_payment_type'],
                'arm_token' => $razorpay_response['notes']['txnid'],
                'arm_payer_email' => $razorpay_response['payer_email'],
                'arm_receiver_email' => '',
                'arm_transaction_id' => $razorpay_response['id'],
                'arm_transaction_payment_type' => $razorpay_response['payment_type'],
                'arm_transaction_status' => $razorpay_response['payment_status'],
                'arm_payment_date' => date('Y-m-d H:i:s', strtotime($razorpay_response['created_at'])),
                'arm_payment_mode' => $razorpay_response['notes']['udf4'],
                'arm_amount' => $razorpay_response['payment_amount'],
                'arm_currency' => $razorpay_response['currency'],
                'arm_coupon_code' => $razorpay_response['arm_coupon_code'],
                'arm_coupon_discount' => (isset($razorpay_response['arm_coupon_discount']) && !empty($razorpay_response['arm_coupon_discount'])) ? $razorpay_response['arm_coupon_discount'] : 0,
                'arm_coupon_discount_type' => isset($razorpay_response['arm_coupon_discount_type']) ? $razorpay_response['arm_coupon_discount_type'] : '',
                'arm_response_text' => maybe_serialize($razorpay_response),
                'arm_extra_vars' => maybe_serialize($extraVars),
                'arm_is_trial' => isset($razorpay_response['arm_is_trial']) ? $razorpay_response['arm_is_trial'] : 0,
                'arm_display_log' => $arm_display_log,
                'arm_created_date' => current_time('mysql'),
                'arm_coupon_on_each_subscriptions' => 0,
            );


            $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
            return $payment_log_id;
        }
        return false;
    }

    
    function arm2_razorpay_cancel_subscription($user_id, $plan_id){
        global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
        if (isset($user_id) && $user_id != 0 && isset($plan_id) && $plan_id != 0) {
            $user_detail = get_userdata($user_id);
            $payer_email = $user_detail->user_email;
            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
            $currency = $arm_payment_gateways->arm_get_global_currency();
            if(!empty($planData)){
                $user_payment_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
                if ($user_payment_gateway == 'razorpay') {
                    
                    $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                    $planDetail = $planData['arm_current_plan_detail'];

                    if (!empty($planDetail)) { 
                        $planObj = new ARM_Plan(0);
                        $planObj->init((object) $planDetail);
                    } else {
                        $planObj = new ARM_Plan($plan_id);
                    }
            
                    $payment_log_table = $ARMember->tbl_arm_payment_log;
                    $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type,arm_amount FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s AND `arm_display_log` = %d ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'razorpay', 'success', 1));
                     
                    if (!empty($transaction)) {
                        $extra_var = maybe_unserialize($transaction->arm_extra_vars);
                        
                        $payer_email = $transaction->arm_payer_email;
                        $payment_type = $extra_var['payment_type'];
                        $payment_mode = $extra_var['payment_mode'];
                        $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;

                        $gateway_options = get_option('arm_payment_gateway_settings');
                        $pgoptions = maybe_unserialize($gateway_options);
                        $pgoptions = $pgoptions['razorpay'];

                        $arm_razorpay_payment_mode = isset($pgoptions['razorpay_payment_mode']) ? $pgoptions['razorpay_payment_mode'] : 'sandbox';
                        $arm_razorpay_key      = ($arm_razorpay_payment_mode=='sandbox') ? $pgoptions['razorpay_sandbox_merchant_key'] : $pgoptions['razorpay_merchant_key'];
                        $arm_razorpay_secret   = ($arm_razorpay_payment_mode=='sandbox') ? $pgoptions['razorpay_sandbox_secret'] : $pgoptions['razorpay_secret'];

                        if ($payment_type == 'razorpay') {

                                
                                /*$ch = curl_init();

                                curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/subscriptions/'.$extra_var['subs_id'].'/cancel');
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_POST, 1);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, "{\n  \"cancel_at_cycle_end\": 0\n}");
                                curl_setopt($ch, CURLOPT_USERPWD, $arm_razorpay_key . ':' . $arm_razorpay_secret);

                                $headers = array();
                                $headers[] = 'Content-Type: application/json';
                                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                                $result = curl_exec($ch);
                                
                                if (curl_errno($ch)) {
                                    echo 'Error:' . curl_error($ch);
                                }
                                curl_close($ch);*/

                                $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                            
                                $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                $payment_data = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan_id,
                                    'arm_first_name'=>$user_detail_first_name,
                                    'arm_last_name'=>$user_detail_last_name,
                                    'arm_payment_gateway' => 'razorpay',
                                    'arm_payment_type' => 'subscription',
                                    'arm_payer_email' => $payer_email,
                                    'arm_receiver_email' => '',
                                    'arm_transaction_id' => $transaction->arm_transaction_id,
                                    'arm_token' => $transaction->arm_token,
                                    'arm_transaction_payment_type' => 'subscription',
                                    'arm_payment_mode' => 'manual_subscription',
                                    'arm_transaction_status' => 'canceled',
                                    'arm_payment_date' => current_time('mysql'),
                                    'arm_amount' => $transaction->arm_amount,
                                    'arm_currency' => $currency,
                                    'arm_coupon_code' => '',
                                    'arm_response_text' => '',
                                    'arm_is_trial' => '0',
                                    'arm_created_date' => current_time('mysql')
                                );
                                
                                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);

                                return;
                            
                        }
                    }
                }
            }
        }
    }

    function arm_payment_gateway_has_plan_field_outside_func($razpaymentgateway_main_plan_options, $rselectedPlans, $rallPlans, $alertMessages, $setup_modules,$selectedGateways)
    {
        if(!empty($rallPlans))
        {
            $razpaymentgateway_plan_options = "";
            
            $razpaymentgateway_plan_options .= "<h4>" . __('Razorpay Plans', 'ARM_RAZORPAY') . "<i class='arm_helptip_icon armfa armfa-question-circle' title='".__('You must need to add plan ID for recurring plans', 'ARM_RAZORPAY'). '<br/>' . __("You can find / create plans easily via the", 'ARM_RAZORPAY') . ' <a href="https://dashboard.razorpay.com/#/plans">' . __('plan management', 'ARM_RAZORPAY') . "</a>' ". __("page of the Razorpay dashboard.", 'ARM_RAZORPAY') ."'></i></h4>";
            $razorpay_plans = isset($setup_modules['modules']['razorpay_plans']) ? $setup_modules['modules']['razorpay_plans'] : array();
            $plan_options = array();
            $plan_detail = array();

            $show_razorpay_plan_title=0;  
            $plan_object_array = array();
            foreach ($rallPlans as $pID => $pdata) {
                $pddata = isset($rallPlans[$pID]) ? $rallPlans[$pID] : array();
                $plan_object = new ARM_Plan($pID); 
                 $plan_object_array[$pID] = $plan_object;
                if (!empty($pddata)) {
                    array_push($plan_detail,$pddata);
                    $s_plan_name = $pddata['arm_subscription_plan_name'];
                    $plan_type = $pddata['arm_subscription_plan_type'];
                    $plan_options = maybe_unserialize($pddata['arm_subscription_plan_options']);
                    $plan_payment_cycles = (isset($plan_options['payment_cycles']) && !empty($plan_options['payment_cycles'])) ? $plan_options['payment_cycles'] : array(); 
                    if(empty($plan_payment_cycles)) {
                        $plan_payment_cycles= array(array(
                            'cycle_key' => 'arm0',
                            'cycle_label' =>$plan_object->plan_text(false,false),
                        ));
                    }
                    $payment_type = isset($plan_options['payment_type']) ? $plan_options['payment_type'] : '';
                    
                    if ($plan_type == 'recurring' && $payment_type == 'subscription') {
                        $razorpay_payment_mode = (isset($selectedPaymentModes['razorpay'])) ? $selectedPaymentModes['razorpay'] : 'both';
                        $show_razorpay_plan_block = 'display: none;';
                        if(in_array($pID, $rselectedPlans) && $razorpay_payment_mode != 'manual_subscription'){
                            $show_razorpay_plan_title++;
                            $show_razorpay_plan_block = 'display: block;';
                        }
                        
                        $razpaymentgateway_plan_options .= '<label class="arm_razorpay_plans arm_razorpay_plan_label_' . $pID . '" style="'.$show_razorpay_plan_block.'"><span class="arm_razorpay_plan_class">' . stripslashes($pddata['arm_subscription_plan_name']) . '</span>';
                       
                        foreach($plan_payment_cycles as $plan_cycle_key => $plan_cycle_data){
                            $cycle_key = isset($plan_cycle_data['cycle_key']) ? $plan_cycle_data['cycle_key'] : ''; 
                            if(isset($razorpay_plans[$pID])){
                                if(is_array($razorpay_plans[$pID])){
                                     $razorpay_pID = isset($razorpay_plans[$pID][$cycle_key]) ? $razorpay_plans[$pID][$cycle_key] : '';
                                }
                                else{
                                     $razorpay_pID = isset($razorpay_plans[$pID]) ? $razorpay_plans[$pID]: '';
                                }
                            }
                            else{
                                $razorpay_pID = '';
                            }
                           $cycle_label = isset($plan_cycle_data['cycle_label']) ? $plan_cycle_data['cycle_label']: ''; 
                            $razpaymentgateway_plan_options .= '<label class="arm_razorpay_plans arm_razorpay_plan_div"><span>' . stripslashes($cycle_label) . '</span>';
                            $razpaymentgateway_plan_options .= '<input type="text" name="setup_data[setup_modules][modules][razorpay_plans][' . $pID . ']['.$cycle_key.']" value="' . $razorpay_pID . '" class="arm_setup_razorpay_plan_input" data-plan_id="' . $pID . '" placeholder="' . __('Razorpay plan ID', 'ARM_RAZORPAY') . '">';
                            $razpaymentgateway_plan_options .= '</label>';
                        }
                        $razpaymentgateway_plan_options .= '</label>';
                    }
                }
            }

            $rarm_show_razorpay_plans = false;
            if(!empty($rselectedPlans)) {
                foreach($rselectedPlans as $sPID) {
                    $plan_object = (isset($plan_object_array[$sPID]) && !empty($plan_object_array[$sPID] ) )? $plan_object_array[$sPID] : '' ;
                    if(is_object($plan_object)){
                        if( $plan_object->is_recurring()){
                            if(in_array('razorpay', $selectedGateways) && $show_razorpay_plan_title>0){
                                $rarm_show_razorpay_plans = true;
                            }
                        }
                    }
                }
            }

            $rarm_gateway_option_display = 'display: block;';
            if(!$rarm_show_razorpay_plans) {
                $rarm_gateway_option_display = 'display: none;';
            }
            
            $razpaymentgateway_main_plan_options .= "<div class='arm_razorpay_plan_container' style='".$rarm_gateway_option_display."'>";
            $razpaymentgateway_main_plan_options .= $razpaymentgateway_plan_options;
            $razpaymentgateway_main_plan_options .= "</div>";
        }
        return $razpaymentgateway_main_plan_options;
    }
    
    function arm_razorpay_modify_coupon_code($data,$payment_mode,$couponData,$planAmt, $plan_obj){

        if(isset($plan_obj) && !empty($plan_obj)){
        if($plan_obj->is_recurring() && $payment_mode == 'auto_debit_subscription' ){
            if( $data['status'] == 'success' ){
                $data['coupon_amt'] = '0.00';
                $data['total_amt'] = $planAmt;
            }
        }
        }
        return $data;
    }
}

global $arm_razorpay;
$arm_razorpay = new ARM_Razorpay();

global $armrazorpay_api_url, $armrazorpay_plugin_slug;

$armrazorpay_api_url = $arm_razorpay->armrazorpay_getapiurl();
$armrazorpay_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armrazorpay_check_for_plugin_update');

function armrazorpay_check_for_plugin_update($checked_data) {
    global $armrazorpay_api_url, $armrazorpay_plugin_slug, $wp_version, $arm_razorpay_version,$arm_razorpay;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armrazorpay_plugin_slug,
        'version' => $arm_razorpay_version,
        'other_variables' => $arm_razorpay->armrazorpay_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMRAZORPAY-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armrazorpay_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armrazorpay_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armrazorpay_plugin_slug . '/' . $armrazorpay_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armrazorpay_plugin_api_call', 10, 3);

function armrazorpay_plugin_api_call($def, $action, $args) {
    global $armrazorpay_plugin_slug, $armrazorpay_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armrazorpay_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armrazorpay_plugin_slug . '/' . $armrazorpay_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armrazorpay_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMRAZORPAY-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armrazorpay_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', MEMBERSHIP_TXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', MEMBERSHIP_TXTDOMAIN), $request['body']);
    }

    return $res;
}

class RazorpayConfigWrapper {

    public static function getConfig() {
        global $arm_razorpay;
        return $arm_razorpay->arm_razorpay_config();
    }

}

class CreateRazorpayPaymentRequest {

    public static function main($data_array) {

        $razorpay_form = '';
        $key = $data_array['arm_razorpay_marchant_id'];
        $testurl = 'https://api.razorpay.com/v1/checkout/embedded';
        
        $notification_url = $data_array['notification_url'];
        $redirect_url = $data_array['redirect_url'];
        $plan_action  = $data_array['plan_action'];
        $txnid = $data_array['arm_plan_id'].'_'.time();
        
        $amount = $data_array['arm_plan_amount'] * 100;
        $currency = $data_array['currency'];
        $plan_id = $data_array['arm_plan_id'];
        $firstname = $data_array['first_name'];
        $lastname = $data_array['last_name'];
        $email = $data_array['user_email'];
        $contact = $data_array['contact']; 
        $reference = $data_array['reference'];
        $subscription_id = isset($data_array['subscription_id']) ? $data_array['subscription_id'] : '';

        $recurring_payment_mode = $data_array['payment_mode'];

        $post_field = array('amount' => $amount, 'currency' => $currency , 'payment_capture' =>  '1'); 
        $post_field_json = json_encode($post_field);

        if ($recurring_payment_mode !== 'auto_debit_subscription') {

            $razorpay_auth = base64_encode( $data_array['arm_razorpay_marchant_id'] . ':' . $data_array['arm_razorpay_secret_key'] );
            $razorpay_args = [
                'headers' => [
                    'Authorization' => "Basic $razorpay_auth",
                    'content-type'  => 'application/json',
                ],
                'body'    => $post_field_json,   
            ];  
            $result      = wp_remote_post( 'https://api.razorpay.com/v1/orders', $razorpay_args );
            
            if(!is_wp_error($result))
            {
                $result_obj = json_decode($result['body']);
            }
            else 
            {
                $err_msg = $result->get_error_message();
                $err_msg = __("Sorry something went wrong while processing payment with Razorpay. Please try again.", 'ARM_RAZORPAY'). ' '. __("Error:", 'ARM_RAZORPAY'). ' ' . $err_msg;

                global $payment_done;
                
                $payment_done = array('status' => FALSE, 'error' => $err_msg);
                return;
                
            }
            
             

        }  


        $razorpay_form = '<form action="'.$testurl.'" method="post" id="arm_razorpay_form">';
        $razorpay_form .= '<input type="hidden" name="key_id" value="'.$key.'"/>';
        if ($recurring_payment_mode == 'auto_debit_subscription') {
            $razorpay_form .= '<input type="hidden" name="subscription_id" value="'.$subscription_id.'"/>';
        }else{
            $razorpay_form .= '<input type="hidden" name="order_id" value="'.$result_obj->id.'">';                      
        }
        $razorpay_form .= '<input type="hidden" name="name" value="'.$firstname.' '.$lastname.'"/>';
        $razorpay_form .= '<input type="hidden" name="amount" value="'.$amount.'"/>';
        $razorpay_form .= '<input type="hidden" name="currency" value="'.$currency.'"/>';
        $razorpay_form .= '<input type="hidden" name="prefill[name]" value="'.$firstname.'">';
        $razorpay_form .= '<input type="hidden" name="prefill[email]" value="'.$email.'">';
        $razorpay_form .= '<input type="hidden" name="prefill[contact]" value="'.$contact.'">';
        $razorpay_form .= '<input type="hidden" name="notes[udf1]" value="'.$reference.'"/>';
        $razorpay_form .= '<input type="hidden" name="notes[udf2]" value="'.$redirect_url.'"/>';
        $razorpay_form .= '<input type="hidden" name="notes[udf3]" value="'.$plan_action.'"/>';
        $razorpay_form .= '<input type="hidden" name="notes[udf4]" value="'.$recurring_payment_mode.'"/>';
        $razorpay_form .= '<input type="hidden" name="notes[txnid]" value="'.$txnid.'"/>';              
        $razorpay_form .= '<input type="hidden" name="callback_url" value="'.$notification_url.'">';

        $razorpay_form .= '<input type="submit" style="display:none" id="arm_razorpay_submit" value="'.__('Pay via Razorpay', 'ARM_RAZORPAY').'" /> 
                </form>
                <script data-cfasync="false" type="text/javascript" language="javascript">
                document.getElementById("arm_razorpay_form").submit();</script>
                ';
       
        return $razorpay_form;
       
    }
}
?>