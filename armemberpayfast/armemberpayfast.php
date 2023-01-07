<?php 
/*
  Plugin Name: ARMember - PayFast payment gateway Addon
  Description: Extension for ARMember plugin to accept payments using PayFast.
  Version: 1.0
  Plugin URI: https://www.armemberplugin.com
  Author: Repute InfoSystems
  Author URI: https://www.armemberplugin.com
 */

define('ARM_PAYFAST_DIR_NAME', 'armemberpayfast');
define('ARM_PAYFAST_DIR', WP_PLUGIN_DIR . '/' . ARM_PAYFAST_DIR_NAME);

if (is_ssl()) {
    define('ARM_PAYFAST_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_PAYFAST_DIR_NAME));
    define('ARM_PAYFAST_HOME_URL', home_url('','https'));
} else {
    define('ARM_PAYFAST_URL', WP_PLUGIN_URL . '/' . ARM_PAYFAST_DIR_NAME);
    define('ARM_PAYFAST_HOME_URL', home_url());
}

define('ARM_PAYFAST_DOC_URL', ARM_PAYFAST_URL . '/documentation/index.html#content');

global $arm_payfast_version;
$arm_payfast_version = '1.0';

global $armnew_payfast_version;


global $armpayfast_api_url, $armpayfast_plugin_slug, $wp_version;

class ARM_Payfast{
    
    function __construct() {
        global $arm_payment_gateways, $arm_transaction;
        $arm_payment_gateways->currency['payfast'] = $this->arm_payfast_currency_symbol();

        add_action('init', array(&$this, 'arm_payfast_db_check'));

        register_activation_hook(__FILE__, array('ARM_Payfast', 'install'));

        register_activation_hook(__FILE__, array('ARM_Payfast', 'arm_payfast_check_network_activation'));

        register_uninstall_hook(__FILE__, array('ARM_Payfast', 'uninstall'));

        add_filter('arm_get_payment_gateways', array(&$this, 'arm_add_payfast_payment_gateways'));
        
        add_filter('arm_get_payment_gateways_in_filters', array(&$this, 'arm_add_payfast_payment_gateways'));
        
        add_action('admin_notices', array(&$this, 'arm_payfast_admin_notices'));
        
        add_filter('arm_change_payment_gateway_tooltip', array(&$this, 'arm_change_payment_gateway_tooltip_func'), 10, 3);
        
        add_filter('arm_gateway_callback_info', array(&$this, 'arm_gateway_callback_info_func'), 10, 3);
        
        add_filter('arm_filter_gateway_names', array(&$this, 'arm_filter_gateway_names_func'), 10);
        
        //add_filter('arm_payment_gateway_has_plan_field_outside', array(&$this, 'arm_payment_gateway_has_plan_field_outside_func'), 10, 6);
        
        //add_action('arm_show_payment_gateway_recurring_notice', array(&$this, 'arm_show_payment_gateway_payfast_recurring_notice'), 10);
        
        add_filter('arm_set_gateway_warning_in_plan_with_recurring', array(&$this, 'arm_payfast_recurring_trial'), 10);
        
        add_filter('arm_allowed_payment_gateways', array(&$this, 'arm_payment_allowed_gateways'), 10, 3);
        
        add_action('arm_payment_related_common_message', array(&$this, 'arm_payment_related_common_message'), 10);

        add_filter('arm_currency_support', array(&$this, 'arm_payfast_currency_support'), 10, 2);

        //add_filter('arm_not_display_payment_mode_setup', array(&$this, 'arm_not_display_payment_mode_setup_func'), 10, 1);

        add_action('arm_after_payment_gateway_listing_section', array(&$this, 'arm_after_payment_gateway_listing_section_func'), 10, 2);

        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_script'), 10);

        add_action('wp_head', array(&$this, 'arm_payfast_set_front_js'), 10);
        
        add_action('plugins_loaded', array(&$this, 'arm_payfast_load_textdomain'));
        
        //add_action('admin_init', array(&$this, 'upgrade_data_payfast'));
        
        //add_filter('arm_change_coupon_code_outside_from_payfast',array(&$this,'arm_payfast_modify_coupon_code'),10,5); //
        
        add_filter('arm_change_pending_gateway_outside',array(&$this,'arm2_change_pending_gateway_outside'),100,3);
        
        //add_filter('arm_membership_update_user_meta_from_outside', array(&$this, 'arm2_membership_payfast_update_usermeta'), 10, 5);
        
        //add_action('arm_update_user_meta_after_renew_outside', array(&$this, 'arm2_payfast_update_meta_after_renew'), 10, 4);
        
        add_filter('arm_default_plan_array_filter', array(&$this, 'arm2_default_plan_array_filter_func'), 10, 1);
        
        add_filter('arm_need_to_cancel_old_subscription_gateways', array(&$this, 'arm2_need_to_cancel_old_subscription_gateways'), 10, 1);
        
        add_action('arm_payment_gateway_validation_from_setup', array(&$this, 'arm2_payment_gateway_form_submit_action'), 10, 4);
        
        add_action('wp', array(&$this, 'arm2_payfast_webhook'), 5);
        
        add_action('arm_cancel_subscription_gateway_action', array(&$this, 'arm2_payfast_cancel_subscription'), 10, 2);

        add_action('arm_enqueue_js_css_from_outside', array(&$this,'arm_enqueue_payfast_js_css_for_model'),10);

        add_filter('arm_filter_cron_hook_name_outside', array(&$this, 'arm_filter_cron_hook_name_outside_func'), 10);

        //add_action('arm_membership_payfast_recurring_payment', array(&$this, 'arm2_membership_payfast_check_recurring_payment'));
        
        
        add_filter( 'arm_display_update_card_button_from_outside', array( $this, 'arm_display_update_card_button'), 10, 3 );
        add_filter( 'arm_render_update_card_button_from_outside', array( $this, 'arm_render_update_card_button'), 10, 6 );
    }
    
    
    function arm_display_update_card_button( $display, $pg, $planData ){
        if( 'payfast' == $pg ){
            $display = true;
        }
        return $display;
    }
    
    
    function arm_render_update_card_button(  $content, $pg, $planData, $user_plan, $arm_disable_button, $update_card_text ){
        if( 'payfast' == $pg ){
            $content .= '';
        }
        return $content;
    }
    

    function arm2_need_to_cancel_old_subscription_gateways( $payment_gateway_array ) {
        array_push($payment_gateway_array, 'payfast');
        return $payment_gateway_array;
    }
    
    function arm2_default_plan_array_filter_func( $default_plan_array ) {
        global $ARMember;
        $default_plan_array['arm_payfast'] = '';
        return $default_plan_array;
    }
    
    function arm2_membership_payfast_update_usermeta($posted_data, $user_id, $plan, $log_detail, $pgateway) {
        if ($pgateway == 'payfast') {
            $posted_data['arm_payfast'] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
        }
        return $posted_data;
    }
    
    function arm2_payfast_update_meta_after_renew($user_id, $log_detail, $plan_id, $payment_gateway) {
        global $ARMember;
        if ($payment_gateway == 'payfast') {
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
                $plan_data['arm_payfast'] = $pg_subsc_data;
                update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $plan_data);
            }
        }
    }
    
    function arm_payfast_load_textdomain() {
        load_plugin_textdomain('ARM_PAYFAST', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public static function arm_payfast_db_check() {
        global $arm_payfast;
        $arm_payfast_version = get_option('arm_payfast_version');

        if (!isset($arm_payfast_version) || $arm_payfast_version == '')
            $arm_payfast->install();
    }

    function armpayfast_getapiurl() {
            $api_url = 'https://www.arpluginshop.com/';
            return $api_url;
        }
        
    /*
    function upgrade_data_payfast() {
        global $armnew_payfast_version;

        if (!isset($armnew_payfast_version) || $armnew_payfast_version == "")
            $armnew_payfast_version = get_option('arm_payfast_version');

        if (version_compare($armnew_payfast_version, '1.0', '<')) {
            $path = ARM_PAYFAST_DIR . '/upgrade_latest_data_payfast.php';
            include($path);
        }
    }
    */
    
    function armpayfast_get_remote_post_params($plugin_info = "") {
            global $wpdb;
    
            $action = "";
            $action = $plugin_info;
    
            if (!function_exists('get_plugins')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            $plugin_list = get_plugins();
            $site_url = ARM_PAYFAST_HOME_URL;
            $plugins = array();
    
            $active_plugins = get_option('active_plugins');
    
            foreach ($plugin_list as $key => $plugin) {
                $is_active = in_array($key, $active_plugins);
    
                //filter for only armember ones, may get some others if using our naming convention
                if (strpos(strtolower($plugin["Title"]), "armemberpayfast") !== false) {
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
        global $arm_payfast;
        $arm_payfast_version = get_option('arm_payfast_version');

        if (!isset($arm_payfast_version) || $arm_payfast_version == '') {
            global $wpdb, $arm_payfast_version;
            update_option('arm_payfast_version', $arm_payfast_version);
        }
    }

    
    /*
     * Restrict Network Activation
     */
    public static function arm_payfast_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }

    public static function uninstall() {
        delete_option('arm_payfast_version');
    }

    function arm_payfast_currency_symbol() {
        global $arm_payment_gateways, $arm_global_settings;
        
        $currency_symbol = array(
            'ZAR' => '&#x52;',
        );
        return $currency_symbol;
    }

    function arm_add_payfast_payment_gateways($default_payment_gateways) {
        if ($this->is_version_compatible()) {
            global $arm_payment_gateways;
            $default_payment_gateways['payfast']['gateway_name'] = __('PayFast', 'ARM_PAYFAST');
            return $default_payment_gateways;
        } else {
            return $default_payment_gateways;
        }
    }

    function arm_payfast_admin_notices() {
        global $pagenow, $arm_slugs;    
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if (!$this->is_armember_support())
                echo "<div class='updated updated_notices'><p>" . __('PayFast For ARMember plugin requires ARMember Plugin installed and active.', 'ARM_PAYFAST') . "</p></div>";

            else if (!$this->is_version_compatible())
                echo "<div class='updated updated_notices'><p>" . __('PayFast For ARMember plugin requires ARMember plugin installed with version 3.0 or higher.', 'ARM_PAYFAST') . "</p></div>";
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
        if ($gateway_name == 'payfast') {
            return __("You can find Merchant ID, Merchant Key, and Salt Passphrase in your PayFast account. To get more details, please refer to this", 'ARM_PAYFAST')." <a href='https://www.payfast.co.za/user/login' target='_blank'>".__("document", 'ARM_PAYFAST')."</a>.";
        }
        return $titleTooltip;
    }
    
    function arm_gateway_callback_info_func($apiCallbackUrlInfo, $gateway_name, $gateway_options) {
        if ($gateway_name == 'payfast') {           
            global $arm_global_settings;
            $apiCallbackUrlInfo = '<a href="'. ARM_PAYFAST_DOC_URL .'" target="_blank">'.__('ARMember PayFast Documentation', 'ARM_PAYFAST').'</a>';
        }
        return $apiCallbackUrlInfo;
    }

    function arm_filter_gateway_names_func($pgname) {
        $pgname['payfast'] = __('PayFast', 'ARM_PAYFAST');
        return $pgname;
    }

    function arm2_change_pending_gateway_outside($user_pending_pgway,$plan_ID,$user_id){
        global $is_free_manual,$ARMember;
        if( $is_free_manual ){
            $key = array_search('payfast',$user_pending_pgway);
            unset($user_pending_pgway[$key]);
        }
        return $user_pending_pgway;
    }
    
    function admin_enqueue_script(){
        global $arm_payfast_version, $arm_slugs;

        if(!empty($arm_slugs->general_settings)) {
            $arm_payfast_page_array = array($arm_slugs->general_settings);
            $arm_payfast_action_array = array('payment_options');
            
            if( isset($_REQUEST['page']) && isset($_REQUEST['action']) && (in_array($_REQUEST['page'], $arm_payfast_page_array) && in_array($_REQUEST['action'], $arm_payfast_action_array)) ||  (isset($_REQUEST['page']) && $_REQUEST['page']==$arm_slugs->membership_setup)) {
                wp_register_script( 'arm-admin-payfast', ARM_PAYFAST_URL . '/js/arm_admin_payfast.js', array(), $arm_payfast_version );
                wp_enqueue_script( 'arm-admin-payfast' );
                wp_register_style('arm-admin-payfast-css', ARM_PAYFAST_URL . '/css/arm_admin_payfast.css', array(), $arm_payfast_version);
                wp_enqueue_style('arm-admin-payfast-css');
            }    
        }
    }
    
    
    function arm_payfast_set_front_js( $force_enqueue = false ) {
        if( $this->is_version_compatible() ){
            global $ARMember, $arm_payfast_version;
            $is_arm_front_page = $ARMember->is_arm_front_page();
            if ($is_arm_front_page === TRUE || $force_enqueue == TRUE){
                wp_register_script('arm_payfast_js', ARM_PAYFAST_URL . '/js/arm_payfast.js', array('jquery'), $arm_payfast_version);
                wp_enqueue_script('arm_payfast_js');
            }
        }
    }

    function arm_enqueue_payfast_js_css_for_model(){
        $this->arm_payfast_set_front_js(true);
    }
    
    
    function arm_payfast_recurring_trial($notice) {
        // if need to display any notice related subscription in Add / Edit plan page
        if ($this->is_version_compatible()){
            $notice .= "<span style='margin-bottom:10px;'><b>". __('PayFast (if PayFast payment gateway is enabled)','ARM_PAYFAST')."</b><br/>";
            $notice .= "<ol style='margin-left:30px;'>";
            $notice .= "<li>".__('PayFast does not support Free Trial period with auto debit recurring payment.','ARM_PAYFAST')."</li>";
            $notice .= "<li>".__('PayFast does not support Daily Recurring Billing Cycle for auto debit payment method. PayFast Support "Monthly" and "Yearly" billing cycle.', 'ARM_PAYFAST')."</li>";
            $notice .= "</ol>";
            $notice .= "</span>";
        } 
        return $notice;
    }

    function arm_payment_allowed_gateways($allowed_gateways, $plan_obj, $plan_options) {
        
        $allowed_gateways['payfast'] = "1";
        return $allowed_gateways;
    }

    function arm_payment_related_common_message($common_messages) {
        if ($this->is_version_compatible()) {
            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_payment_fail_payfast"><?php _e('Payment Fail (PayFast)', 'ARM_PAYFAST'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_payment_fail_payfast]" id="arm_payment_fail_payfast" value="<?php echo (!empty($common_messages['arm_payment_fail_payfast']) ) ? $common_messages['arm_payment_fail_payfast'] : 'Sorry something went wrong while processing payment with PayFast.'; ?>" />
                </td>
            </tr>
            <?php
        }
    }

    function arm_payment_gateway_has_ccfields_func($pgHasCcFields, $gateway_name, $gateway_options) {
        if ($gateway_name == 'payfast') {
            return true;
        } else {
            return $pgHasCcFields;
        }
    }

    function arm_payfast_currency_support($notAllow, $currency) {
        global $arm_payment_gateways;
        $payfast_currency = $this->arm_payfast_currency_symbol();
        if (!array_key_exists($currency, $payfast_currency)) {
            $notAllow[] = 'payfast';
        }
        return $notAllow;
    }

    function arm_not_display_payment_mode_setup_func($gateway_name_arr) {
        //for remove auto debit payment and menual payment option from front side page and admin site. Its allow only manual payment.
        $gateway_name_arr[] = 'payfast';
        return $gateway_name_arr;
    }

    function arm_after_payment_gateway_listing_section_func($gateway_name, $gateway_options) {
        // set paymetn geteway setting field in general settgin > payment gateway
        global $arm_global_settings;
        if ($gateway_name == 'payfast') {
            $gateway_options['payfast_payment_mode'] = (!empty($gateway_options['payfast_payment_mode']) ) ? $gateway_options['payfast_payment_mode'] : 'sandbox';
            $gateway_options['status'] = isset($gateway_options['status']) ? $gateway_options['status'] : 0;
            $disabled_field_attr = ($gateway_options['status'] == '1') ? '' : 'disabled="disabled"';
            $readonly_field_attr = ($gateway_options['status'] == '1') ? '' : 'readonly="readonly"';

            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Payment Mode', 'ARM_PAYFAST'); ?> *</label></th>
                <td class="arm-form-table-content">
                    <input id="arm_payfast_payment_gateway_mode_sand" class="arm_general_input arm_payfast_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="sandbox" name="payment_gateway_settings[payfast][payfast_payment_mode]" <?php checked($gateway_options['payfast_payment_mode'], 'sandbox'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_payfast_payment_gateway_mode_sand"><?php _e('Sandbox', 'ARM_PAYFAST'); ?></label>
                    <input id="arm_payfast_payment_gateway_mode_pro" class="arm_general_input arm_payfast_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="production" name="payment_gateway_settings[payfast][payfast_payment_mode]" <?php checked($gateway_options['payfast_payment_mode'], 'production'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_payfast_payment_gateway_mode_pro"><?php _e('Production', 'ARM_PAYFAST'); ?></label>
                </td>
            </tr>
            <!-- ***** Begining of Sandbox Input for payfast ***** -->
            <?php
            $payfast_hidden = "hidden_section";
            if (isset($gateway_options['payfast_payment_mode']) && $gateway_options['payfast_payment_mode'] == 'sandbox') {
                $payfast_hidden = "";
            } else if (!isset($gateway_options['payfast_payment_mode'])) {
                $payfast_hidden = "";
            }
            ?>
            <tr class="form-field arm_payfast_sandbox_fields <?php echo $payfast_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Test Merchant ID', 'ARM_PAYFAST'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payfast_test_merchant_id" name="payment_gateway_settings[payfast][payfast_test_merchant_id]" value="<?php echo (!empty($gateway_options['payfast_test_merchant_id'])) ? $gateway_options['payfast_test_merchant_id'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payfast_sandbox_fields <?php echo $payfast_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Test Merchant Key', 'ARM_PAYFAST'); ?> *</th> 
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payfast_test_merchant_key" name="payment_gateway_settings[payfast][payfast_test_merchant_key]" value="<?php echo (!empty($gateway_options['payfast_test_merchant_key'])) ? $gateway_options['payfast_test_merchant_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payfast_sandbox_fields <?php echo $payfast_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Test Salt Passphrase', 'ARM_PAYFAST'); ?> *</th> 
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payfast_test_salt_passphrase" name="payment_gateway_settings[payfast][payfast_test_salt_passphrase]" value="<?php echo (!empty($gateway_options['payfast_test_salt_passphrase'])) ? $gateway_options['payfast_test_salt_passphrase'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            
            
            <!-- ***** Ending of Sandbox Input for payfast ***** -->

            <!-- ***** Begining of Live Input for payfast ***** -->
            <?php
            $payfast_live_fields = "hidden_section";
            if (isset($gateway_options['payfast_payment_mode']) && $gateway_options['payfast_payment_mode'] == "production") {
                $payfast_live_fields = "";
            }
            ?>
            <tr class="form-field arm_payfast_fields <?php echo $payfast_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live Merchant ID', 'ARM_PAYFAST'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payfast_live_merchant_id" name="payment_gateway_settings[payfast][payfast_live_merchant_id]" value="<?php echo (!empty($gateway_options['payfast_live_merchant_id'])) ? $gateway_options['payfast_live_merchant_id'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payfast_fields <?php echo $payfast_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live Merchant Key', 'ARM_PAYFAST'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payfast_live_merchant_key" name="payment_gateway_settings[payfast][payfast_live_merchant_key]" value="<?php echo (!empty($gateway_options['payfast_live_merchant_key'])) ? $gateway_options['payfast_live_merchant_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payfast_fields <?php echo $payfast_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live Salt Passphrase', 'ARM_PAYFAST'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payfast_live_salt_passphrase" name="payment_gateway_settings[payfast][payfast_live_salt_passphrase]" value="<?php echo (!empty($gateway_options['payfast_live_salt_passphrase'])) ? $gateway_options['payfast_live_salt_passphrase'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            
            <!-- ***** Ending of Live Input for payfast ***** -->

            <?php
        }
    }

    function arm_payfast_config() {
        global $arm_payment_gateways, $arm_global_settings;

        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        if (isset($all_payment_gateways['payfast']) && !empty($all_payment_gateways['payfast'])) {
            $payment_gateway_options = $all_payment_gateways['payfast'];
            $ARM_Payfast_payment_mode = $payment_gateway_options['payfast_payment_mode'];
            $is_sandbox_mode = $ARM_Payfast_payment_mode == "sandbox" ? true : false;

            $PayfastConfig = array();

            $PayfastConfig['environment'] = ( $is_sandbox_mode ) ? "sandbox" : "production"; // production, sandbox

            $PayfastConfig['credentials'] = array();
            $PayfastConfig['credentials']['secret_key'] = ( $is_sandbox_mode ) ? $payment_gateway_options['payfast_test_merchant_id'] : $payment_gateway_options['payfast_live_merchant_id'];
            $PayfastConfig['credentials']['public_key']['sandbox'] = $payment_gateway_options['payfast_test_merchant_key'];
            $PayfastConfig['credentials']['public_key']['production'] = $payment_gateway_options['payfast_live_merchant_key'];
            

            $PayfastConfig['application'] = array();
            $PayfastConfig['application']['charset'] = "UTF-8"; // UTF-8, ISO-8859-1

            $PayfastConfig['log'] = array();
            $PayfastConfig['log']['active'] = false;
            
            $PayfastConfig['log']['fileLocation'] = "";

            return $PayfastConfig;
        }
    }

    
    function arm2_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {
        
        global $wpdb, $ARMember, $arm_global_settings, $arm_membership_setup, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $paid_trial_stripe_payment_done, $is_free_manual;
        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        $currency = $arm_payment_gateways->arm_get_global_currency();
        if ($payment_gateway == 'payfast' && isset($all_payment_gateways['payfast']) && !empty($all_payment_gateways['payfast'])) 
        {
            $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
            
            $gateway_options = get_option('arm_payment_gateway_settings');
            $pgoptions = maybe_unserialize($gateway_options);


            $current_payment_gateway = (isset($posted_data['payment_gateway'])) ? $posted_data['payment_gateway'] : '';
            if ($current_payment_gateway == '') 
            {
                $current_payment_gateway = (isset($posted_data['_payment_gateway'])) ? $posted_data['_payment_gateway'] : '';
            }


            if (!empty($entry_data) && $current_payment_gateway == $payment_gateway) 
            {
                $arm_subs_plan_id = (!empty($posted_data['subscription_plan'])) ? $posted_data['subscription_plan'] : 0;
                if ($arm_subs_plan_id == 0) {
                    $arm_subs_plan_id = (!empty($posted_data['_subscription_plan'])) ? $posted_data['_subscription_plan'] : 0;
                }


                $payment_mode_ = !empty($posted_data['arm_payment_mode']['payfast']) ? $posted_data['arm_payment_mode']['payfast'] : 'both';

                $recurring_payment_mode = 'manual_subscription';
                $c_rec_mpayment_mode = "";
                if(isset($posted_data['arm_pay_thgough_mpayment']) && $posted_data['arm_plan_type']=='recurring' && is_user_logged_in())
                {
                    $current_m_user_id = get_current_user_id();
                    $current_m_user_plan_ids = get_user_meta($current_m_user_id, 'arm_user_plan_ids', true);
                    $current_m_user_plan_ids = !empty($current_m_user_plan_ids) ? $current_m_user_plan_ids : array();
                    $Current_M_SPlanData = get_user_meta($current_m_user_id, 'arm_user_plan_' . $arm_subs_plan_id, true);
                    $Current_M_SPlanDetails = $Current_M_SPlanData['arm_current_plan_detail'];
                    if (!empty($current_m_user_plan_ids)) {
                        if(in_array($arm_subs_plan_id, $current_m_user_plan_ids) && !empty($Current_M_SPlanDetails))
                        {
                            $arm_cmember_paymentcycle = $Current_M_SPlanData['arm_payment_cycle'];
                            $arm_cmember_completed_recurrence = $Current_M_SPlanData['arm_completed_recurring'];
                            $arm_cmember_plan = new ARM_Plan(0);
                            $arm_cmember_plan->init((object) $Current_M_SPlanDetails);
                            $arm_cmember_plan_data = $arm_cmember_plan->prepare_recurring_data($arm_cmember_paymentcycle);
                            $arm_cmember_TotalRecurring = $arm_cmember_plan_data['rec_time'];
                            if ($arm_cmember_TotalRecurring == 'infinite' || ($arm_cmember_completed_recurrence !== '' && $arm_cmember_completed_recurrence != $arm_cmember_TotalRecurring)) {
                                $c_rec_mpayment_mode = 1;
                            }
                        }
                    }
                }



                if(empty($c_rec_mpayment_mode))
                {
                    if ($payment_mode_ == 'both') 
                    {
                        $recurring_payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : 'manual_subscription';
                    } else {
                        $recurring_payment_mode = $payment_mode_;
                    }
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
                        if(isset($posted_data['arm_payment_mode']['payfast'])){
                            $payment_mode_ = !empty($posted_data['arm_payment_mode']['payfast']) ? $posted_data['arm_payment_mode']['payfast'] : 'manual_subscription';
                            $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                            
                            if($recurring_payment_mode=='auto_debit_subscription')
                            {
                                //echo 'inside_here payfast_plan_id<br>$plan_id=>'.$plan_id.'cycle_key-->';

                                if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
                                     $payment_cycle_key = $plan->options['payment_cycles'][$payment_cycle]['cycle_key'];
                                     $PayfastPlanID = $setup_data['setup_modules']['modules']['payfast_plans'][$plan_id][$payment_cycle_key];
                                }
                                //exit;
                            }
                        }
                        else{
                            $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                            if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
                                $setup_modules = $setup_data['setup_modules'];
                                $modules = $setup_modules['modules'];
                                $payment_mode_ = $modules['payment_mode']['payfast'];
                            }
                        }


                        $payment_mode = 'manual_subscription';
                        $c_mpayment_mode = "";
                        if(isset($posted_data['arm_pay_thgough_mpayment']) && $posted_data['arm_plan_type']=='recurring' && is_user_logged_in())
                        {
                            $current_user_id = get_current_user_id();
                            $current_user_plan_ids = get_user_meta($current_user_id, 'arm_user_plan_ids', true);
                            $current_user_plan_ids = !empty($current_user_plan_ids) ? $current_user_plan_ids : array();
                            $Current_M_PlanData = get_user_meta($current_user_id, 'arm_user_plan_' . $plan_id, true);
                            $Current_M_PlanDetails = $Current_M_PlanData['arm_current_plan_detail'];
                            if (!empty($current_user_plan_ids)) {
                                if(in_array($plan_id, $current_user_plan_ids) && !empty($Current_M_PlanDetails))
                                {
                                    $arm_cmember_paymentcycle = $Current_M_PlanData['arm_payment_cycle'];
                                    $arm_cmember_completed_recurrence = $Current_M_PlanData['arm_completed_recurring'];
                                    $arm_cmember_plan = new ARM_Plan(0);
                                    $arm_cmember_plan->init((object) $Current_M_PlanDetails);
                                    $arm_cmember_plan_data = $arm_cmember_plan->prepare_recurring_data($arm_cmember_paymentcycle);
                                    $arm_cmember_TotalRecurring = $arm_cmember_plan_data['rec_time'];
                                    if ($arm_cmember_TotalRecurring == 'infinite' || ($arm_cmember_completed_recurrence !== '' && $arm_cmember_completed_recurrence != $arm_cmember_TotalRecurring)) {
                                        $c_mpayment_mode = 1;
                                    }
                                }
                            }
                        }
                        if(empty($c_mpayment_mode))
                        {
                            if ($payment_mode_ == 'both') {
                                $payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : 'manual_subscription';
                            } else {
                                $payment_mode = $payment_mode_;
                            }
                        }
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
                        // $is_trial = true;
                        // $arm_is_trial = '1';
                        // $amount = $plan->options['trial']['amount'];
                        // $trial_period = $plan->options['trial']['period'];
                        // $trial_interval = $plan->options['trial']['interval'];
                        $payfast_err_msg = '<div class="arm_error_msg"><ul><li>'.__('PayFast does not support Trial Period for auto debit payment method.', 'ARM_PAYFAST').'</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $payfast_err_msg);
                        echo json_encode($return);
                        exit;
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
                        //$discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                        $arm_coupon_discount = (isset($couponApply['discount']) && !empty($couponApply['discount'])) ? $couponApply['discount'] : 0;
                        $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $currency : "%";
                        $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : '0';
                        
                        $extraParam['coupon'] = array(
                            'coupon_code' => $posted_data['arm_coupon_code'],
                            'amount' => $coupon_amount,
                            'arm_coupon_on_each_subscriptions' => $arm_coupon_on_each_subscriptions
                        );
                        if($arm_coupon_on_each_subscriptions=='1')
                        {
                            $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $currency : "%";
                            $coupon_discount_amount = isset($couponApply['discount']) ? $couponApply['discount'] : 0;
                            if($arm_coupon_discount_type=='%')
                            {
                                $coupon_discount_amount = ($discount_amt * $coupon_discount_amount) /100;
                                $discount_amt = $discount_amt - $coupon_discount_amount;
                            }
                            else
                            {
                               $discount_amt = $discount_amt - $coupon_discount_amount;
                            }
                        }
                        else
                        {
                            $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $currency : "%";
                            $coupon_discount_amount = isset($couponApply['discount']) ? $couponApply['discount'] : 0;
                            if($arm_coupon_discount_type=='%')
                            {
                                $coupon_discount_amount = ($discount_amt * $coupon_discount_amount) /100;
                                $discount_amt = $discount_amt - $coupon_discount_amount;
                            }
                            else
                            {
                               $discount_amt = $discount_amt - $coupon_discount_amount;
                            }
                        }
                    }
                } else {
                    $posted_data['arm_coupon_code'] = '';
                }


                $discount_amt = str_replace(",", "", $discount_amt);
                
                $arm_payfast_plan_amount = str_replace(",", "", $plan->amount);

                if(isset($couponApply) && $couponApply["status"] == "success") {
                    $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : '0';
                    if($arm_coupon_on_each_subscriptions=='1')
                    {
                        $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $currency : "%";
                        $coupon_discount_amount = isset($couponApply['discount']) ? $couponApply['discount'] : 0;
                        if($arm_coupon_discount_type=='%')
                        {
                            $coupon_discount_amount = ($arm_payfast_plan_amount * $coupon_discount_amount) /100;
                            $arm_payfast_plan_amount = $arm_payfast_plan_amount - $coupon_discount_amount;
                        }
                        else
                        {
                           $arm_payfast_plan_amount = $arm_payfast_plan_amount - $coupon_discount_amount;
                        }
                    }
                }


                //payfast tax amount
                if($tax_percentage > 0){
                    $payfast_tax_amount =($arm_payfast_plan_amount*$tax_percentage)/100;
                    $payfast_tax_amount = number_format((float)$payfast_tax_amount, 2, '.','');
                    $arm_payfast_plan_amount = $arm_payfast_plan_amount+$payfast_tax_amount;
                  
                }



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
                
                

                $arm_payfast_redirecturl = '';
                $arm_payfast_redirecturl = $arm_global_settings->add_query_arg("arm-listener", "arm_payfast_api", get_home_url() . "/");


                

                //--------------------- Payment Gateway Code Starts ------------------------//


                $armpayfast_merchant_id = '';
                $armpayfast_merchant_key = '';
                $armpayfast_salt_passphrase = '';

                if($pgoptions['payfast']['payfast_payment_mode'] == 'sandbox')
                {
                    $armpayfast_merchant_id = $pgoptions['payfast']['payfast_test_merchant_id'];
                    $armpayfast_merchant_key = $pgoptions['payfast']['payfast_test_merchant_key'];
                    $armpayfast_salt_passphrase = $pgoptions['payfast']['payfast_test_salt_passphrase'];
                }
                else
                {
                    $armpayfast_merchant_id = $pgoptions['payfast']['payfast_live_merchant_id'];
                    $armpayfast_merchant_key = $pgoptions['payfast']['payfast_live_merchant_key'];
                    $armpayfast_salt_passphrase = $pgoptions['payfast']['payfast_live_salt_passphrase'];
                }
                

                $arm_redirecturl = $entry_values['setup_redirect'];
                if (empty($arm_redirecturl)) {
                    $arm_redirecturl = ARM_HOME_URL;
                }


                $armpayfast_formdata = array(); //Variable for prepare formdata


                //Check recurring condition
                if($recurring_payment_mode == 'auto_debit_subscription'){
                    $arm_payfast_plan_interval = "";

                    if($recurring_data['period']=='D')
                    {
                        //Daily recurring not supported by payfast.
                        $payfast_err_msg = '<div class="arm_error_msg"><ul><li>'.__('PayFast does not support daily recurring billing cycle for auto debit payment method.', 'ARM_PAYFAST').'</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $payfast_err_msg);
                        echo json_encode($return);
                        exit;
                    }
                    else if($recurring_data['period']=='M' && $recurring_data['interval']=='1')
                    {
                        //Monthly Recurring
                        //$arm_payfast_plan_interval = $recurring_data['interval'];
                        $arm_payfast_plan_interval = '3';
                    }
                    else if($recurring_data['period']=='M' && $recurring_data['interval']=='3')
                    {
                        //Quarterly Recurring
                        $arm_payfast_plan_interval = '4';
                    }
                    else if($recurring_data['period']=='M' && $recurring_data['interval']=='6')
                    {
                        //Biannual Recurring
                        $arm_payfast_plan_interval = '5';
                    }
                    else if($recurring_data['period']=='Y')
                    {
                        //Annual Recurring
                        //$arm_payfast_plan_interval = $recurring_data['interval'];
                        $arm_payfast_plan_interval = '6';
                    }
                    else
                    {
                        $payfast_err_msg = '<div class="arm_error_msg"><ul><li>'.__('Payment through PayFast is not supported for selected plan.', 'ARM_PAYFAST').'</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $payfast_err_msg);
                        echo json_encode($return);
                        exit;
                    }


                    $arm_payfast_recurring_time = $recurring_data['rec_time'];

                    //Prepare form data
                    $armpayfast_formdata = array(
                        'merchant_id'       => $armpayfast_merchant_id,
                        'merchant_key'      => $armpayfast_merchant_key,
                        'return_url'        => $arm_redirecturl,
                        'cancel_url'        => $arm_redirecturl,
                        'notify_url'        => add_query_arg('arm-listener', 'arm_payfast_api', site_url().'/'),
                        'amount'            => $discount_amt,
                        'item_name'         => $plan_name,
                        'custom_str1'       => $entry_data['arm_entry_id'],
                        'subscription_type' => 1,
                        'frequency'         => (int)$arm_payfast_plan_interval,
                        'cycles'            => (int)$arm_payfast_recurring_time,
                    );
                }else{
                    //Prepare form data
                    $armpayfast_formdata = array(
                        'merchant_id'  => $armpayfast_merchant_id,
                        'merchant_key' => $armpayfast_merchant_key,
                        'return_url'   => $arm_redirecturl,
                        'cancel_url'   => $arm_redirecturl,
                        'notify_url'   => add_query_arg('arm-listener', 'arm_payfast_api', site_url().'/'),
                        'amount'       => $discount_amt,
                        'item_name'    => $plan_name,
                        'custom_str1'  => $entry_data['arm_entry_id'],
                    );
                }
                
                //Create a parameter string
                $arm_payfast_paramstring = '';
                foreach($armpayfast_formdata as $key => $val){
                    if(!empty($val)){
                        $arm_payfast_paramstring .= $key.'='.urlencode( trim( $val ) ).'&';
                    }
                }

                //Remove Last ampersand;
                $arm_payfast_paramstring = substr($arm_payfast_paramstring, 0, -1);

                
                //Passphrase is required for subscription payment
                $arm_Passphrase = $armpayfast_salt_passphrase;
                $arm_payfast_paramstring .= '&passphrase='.urlencode( trim( $arm_Passphrase ) );
                
                //Add signature value
                $armpayfast_formdata['signature'] = md5($arm_payfast_paramstring);
                

                $payfast_form = "";
                if($pgoptions['payfast']['payfast_payment_mode'] == 'sandbox'){
                    $payfast_form_mode = 'sandbox';
                }else{
                    $payfast_form_mode = 'www';
                }
                $payfast_form = '<form method="POST" id="arm_payfast_form" action="https://'.$payfast_form_mode.'.payfast.co.za/eng/process">';
                
                //Generate form fields
                foreach($armpayfast_formdata as $key => $val){
                    $payfast_form .= '<input type="hidden" name="'.$key.'" value="'.$val.'">';
                }
                
                $payfast_form .= '</form>';
                $payfast_form .= '<script data-cfasync="false" type="text/javascript" language="javascript">document.getElementById("arm_payfast_form").submit();</script>';
                

                $return = array('status' => 'success', 'type' => 'redirect', 'message' => $payfast_form);
                echo json_encode($return);
                exit;
            }
        }
        else
        {

        }
    }

    function arm2_payfast_webhook($transaction_id = 0, $arm_listener = '', $tran_id = '') {
        global $wpdb, $ARMember, $arm_payment_gateways, $arm_subscription_plans, $arm_members_class, $arm_manage_communication;
        
        $gateway_options = get_option('arm_payment_gateway_settings');
        $pgoptions = maybe_unserialize($gateway_options);
        
        if (isset($_REQUEST['arm-listener']) && in_array($_REQUEST['arm-listener'], array('arm_payfast_api'))) 
        {   
            $armPayfastData = $_REQUEST;

            $ARMember->arm_write_response("PayFast Payment Log =>".maybe_serialize($armPayfastData));
            //die();
            $armEntryId = $armPayfastData['custom_str1'];
            if($armPayfastData['payment_status'] == "COMPLETE")
            {
                $arm_Payfast_passphrase = '';
                $arm_pfHost = 'sandbox.payfast.co.za';
                if($pgoptions['payfast']['payfast_payment_mode'] == 'sandbox')
                {
                    $arm_pfHost = 'sandbox.payfast.co.za';
                    $arm_Payfast_passphrase = $pgoptions['payfast']['payfast_test_salt_passphrase'];
                }
                else
                {
                    $arm_pfHost = 'www.payfast.co.za';
                    $arm_Payfast_passphrase = $pgoptions['payfast']['payfast_live_salt_passphrase'];
                }
                
                
                $arm_pfData = $_POST;
                // Construct variables 
                foreach( $arm_pfData as $key => $val )
                {
                    if( $key != 'signature' )
                    {
                        $arm_pfParamString .= $key .'='. urlencode( $val ) .'&';
                    }
                }
                
                // Remove the last '&' from the parameter string
                $arm_pfParamString = substr( $arm_pfParamString, 0, -1 );
                $arm_pfTempParamString = $arm_pfParamString;
                
                $arm_passPhrase = $arm_Payfast_passphrase;
                
                if( !empty( $arm_passPhrase ) )
                {
                    $arm_pfParamString .= '&passphrase='.urlencode( $arm_passPhrase );
                }
                
                $signature = md5( $arm_pfParamString );

                $ARMember->arm_write_response("Signature Value =>". $signature);

                if($signature!=$arm_pfData['signature'])
                {
                    $ARMember->arm_write_response("Signature verification Log => Signature Not Matched");
                    die();
                }


                $arm_get_payment_log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$ARMember->tbl_arm_payment_log` WHERE arm_token = %s", $armPayfastData['token']), ARRAY_A );
                
                $arm_log_id = (!empty($arm_get_payment_log['arm_log_id'])) ? $arm_get_payment_log['arm_log_id'] : '';
                $arm_payfast_user_id = (!empty($arm_get_payment_log['arm_user_id'])) ? $arm_get_payment_log['arm_user_id'] : '';
                $arm_payfast_plan_id = (!empty($arm_get_payment_log['arm_plan_id'])) ? $arm_get_payment_log['arm_plan_id'] : '';
                
                
                $ARMember->arm_write_response("ARM Log ID => ".maybe_serialize($arm_log_id));
                if($arm_log_id == '') 
                {
                    $armUserData = $arm_get_payment_log['arm_entry_value'];
                    $arm_payfast_user_id = $this->arm2_add_user_and_transaction($armEntryId, $armPayfastData);
                }
                else
                {
                    //$ARMember->arm_write_response("Plan ID => ".maybe_serialize($arm_payfast_plan_id));
                    //$ARMember->arm_write_response("User ID => ".maybe_serialize($arm_payfast_user_id));
                    
                    /**
                        Code for update user meta for update payment cycle.
                    **/
                    $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                    $userPlanDatameta = get_user_meta($arm_payfast_user_id, 'arm_user_plan_' . $arm_payfast_plan_id);

                    $planData = !empty($userPlanDatameta) ? $userPlanDatameta[0] : array();

                    //$ARMember->arm_write_response("User Meta => ".maybe_serialize($userPlanDatameta));

                    $arm_next_due_payment_date = $planData['arm_next_due_payment'];

                    //$ARMember->arm_write_response("Plan Data => ".maybe_serialize($planData));

                    //$ARMember->arm_write_response("Before if Next Payment Due => ".maybe_serialize(current_time('mysql').'>='.date('Y-m-d H:i:s',$arm_next_due_payment_date)));

                    if(strtotime(current_time('mysql')) >= $arm_next_due_payment_date)
                    {
                        $total_completed_recurrence = $planData['arm_completed_recurring'];
                        $total_completed_recurrence++;

                        $planData['arm_completed_recurring'] = $total_completed_recurrence;

                        update_user_meta($arm_payfast_user_id, 'arm_user_plan_'.$arm_payfast_plan_id, $planData);
                        $payment_cycle = $planData['arm_payment_cycle'];

                        $arm_current_plan_details = maybe_unserialize($planData['arm_current_plan_detail']['arm_subscription_plan_options']);
                        $arm_recurring_type = $arm_current_plan_details['recurring']['type'];
                        if($arm_recurring_type == "M")
                        {
                            $arm_recurring_duration = $arm_current_plan_details['recurring']['months'];
                            $arm_next_payment_date = strtotime('+'.$arm_recurring_duration.' months', $planData['arm_next_due_payment']);
                        }
                        else if($arm_recurring_type == "Y")
                        {
                            $arm_recurring_duration = $arm_current_plan_details['recurring']['years'];
                            $arm_next_payment_date = strtotime('+'.$arm_recurring_duration.' years', $planData['arm_next_due_payment']);
                        }
                        //$arm_next_payment_date = $arm_members_class->arm_get_next_due_date($arm_payfast_user_id, $arm_payfast_plan_id, true, $payment_cycle);
                        $ARMember->arm_write_response("Next Payment Due => ".maybe_serialize($arm_next_payment_date));
                        
                        $planData['arm_next_due_payment'] = $arm_next_payment_date;
                        
                        update_user_meta($arm_payfast_user_id, 'arm_user_plan_'.$arm_payfast_plan_id, $planData); 

                    }
                    else{
                        $now = current_time('mysql');
                        $arm_last_payment_status = $wpdb->get_var($wpdb->prepare("SELECT `arm_transaction_status` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d AND `arm_created_date`<=%s ORDER BY `arm_log_id` DESC LIMIT 0,1", $arm_payfast_user_id, $arm_payfast_plan_id, $now));  
                        
                        $total_completed_recurrence = $planData['arm_completed_recurring'];
                        $total_completed_recurrence++;

                        $planData['arm_completed_recurring'] = $total_completed_recurrence;

                        update_user_meta($arm_payfast_user_id, 'arm_user_plan_' . $arm_payfast_plan_id, $planData);
                        $payment_cycle = $planData['arm_payment_cycle'];

                        $arm_current_plan_details = maybe_unserialize($planData['arm_current_plan_detail']['arm_subscription_plan_options']);
                        $arm_recurring_type = $arm_current_plan_details['recurring']['type'];
                        if($arm_recurring_type == "M")
                        {
                            $arm_recurring_duration = $arm_current_plan_details['recurring']['months'];
                            $arm_next_payment_date = strtotime('+'.$arm_recurring_duration.' months', $planData['arm_next_due_payment']);
                        }
                        else if($arm_recurring_type == "Y")
                        {
                            $arm_recurring_duration = $arm_current_plan_details['recurring']['years'];
                            $arm_next_payment_date = strtotime('+'.$arm_recurring_duration.' years', $planData['arm_next_due_payment']);
                        }
                        //$arm_next_payment_date = $arm_members_class->arm_get_next_due_date($arm_payfast_user_id, $arm_payfast_plan_id, true, $payment_cycle);
                        $ARMember->arm_write_response("Else Next Payment Due => ".maybe_serialize($arm_next_payment_date));

                        $planData['arm_next_due_payment'] = $arm_next_payment_date;
                        
                        update_user_meta($arm_payfast_user_id, 'arm_user_plan_' . $arm_payfast_plan_id, $planData);
                    }
                }
            }
            else if($armPayfastData['payment_status'] == "CANCELLED")
            {
                // Code for enter data in payment history
                $arm_payfast_entry_details = $wpdb->get_row("SELECT * FROM `" . $ARMember->tbl_arm_entries . "` WHERE `arm_entry_id`='" . $armEntryId . "' ", ARRAY_A);
                
                $arm_payfast_entry_email = isset($arm_payfast_entry_details['arm_entry_email']) ? $arm_payfast_entry_details['arm_entry_email'] : '';
                
                $arm_payfast_user_info = get_user_by('email', $arm_payfast_entry_email);
                $arm_payfast_user_id = isset($arm_payfast_user_info->ID) ? $arm_payfast_user_info->ID : 0;
                
                $arm_payfast_plan_id = $arm_payfast_entry_details['arm_plan_id'];

                $arm_payfast_cancel_subscription = $this->arm2_payfast_cancel_subscription($arm_payfast_user_id, $arm_payfast_plan_id);




                /*$arm_payfast_usermeta = get_user_meta($arm_payfast_user_id, 'arm_user_plan_'.$arm_payfast_plan_id);
                $arm_current_plan_details = maybe_unserialize($arm_payfast_usermeta[0]['arm_current_plan_detail']['arm_subscription_plan_options']);

                $arm_recurring_type = $arm_current_plan_details['recurring']['type'];
                if($arm_recurring_type == "M")
                {
                    $arm_recurring_duration = $arm_current_plan_details['recurring']['months'];
                    $arm_payfast_usermeta[0]['arm_next_due_payment'] = strtotime('+'.$arm_recurring_duration.' months', $arm_payfast_usermeta[0]['arm_next_due_payment']);
                }
                else if($arm_recurring_type == "Y")
                {
                    $arm_recurring_duration = $arm_current_plan_details['recurring']['years'];
                    $arm_payfast_usermeta[0]['arm_next_due_payment'] = strtotime('+'.$arm_recurring_duration.' years', $arm_payfast_usermeta[0]['arm_next_due_payment']);
                }

                $arm_payfast_usermeta[0]['arm_completed_recurring'] = $arm_payfast_usermeta[0]['arm_completed_recurring'] + 1;
                $ARMember->arm_write_response("User Meta Data =>".maybe_serialize($arm_payfast_usermeta));

                update_user_meta($arm_payfast_user_id, 'arm_user_plan_'.$arm_payfast_plan_id, $arm_payfast_usermeta);

                
                $user_subsdata = $planData['arm_payfast'];
                do_action('arm_after_recurring_payment_success_outside',$user_id,$plan_id,'payfast',$payment_mode,$user_subsdata);*/
                
                
                
                
                //Code for remove plan from member's current plan
                
                $subscription_id = $armPayfastData['token'];
                $payLog_data = $wpdb->get_row("SELECT `arm_user_id`, `arm_plan_id`, `arm_extra_vars` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_token`='".$subscription_id."' AND `arm_payment_gateway`='payfast' ORDER BY `arm_log_id` DESC", ARRAY_A);
                
                //print_r($payLog_data);
                //exit;
                if (!empty($subscription_id) && !empty($payLog_data)) {
                    $payment_log_user_id = $payLog_data['arm_user_id'];
                    if(empty($payment_log_user_id))
                    {
                        $get_user_id_by_subscription = $wpdb->get_row("SELECT * FROM ".$wpdb->usermeta." WHERE meta_value like '%".$subscription_id."%'", ARRAY_A);
                        if(!empty($get_user_id_by_subscription))
                        {
                            $payment_log_user_id = $get_user_id_by_subscription['user_id'];
                        }
                    }
                    
                    $user_info = get_user_by('ID', $payment_log_user_id);
                    
                    $entry_plan = $payLog_data['arm_plan_id'];
                    $extraVars = $payLog_data['arm_extra_vars'];
                    $tax_percentage = $tax_amount = 0;
                    if(isset($extraVars) && !empty($extraVars)){
                        $unserialized_extravars = maybe_unserialize($extraVars);
                        $tax_percentage = (isset($unserialized_extravars['tax_percentage']) && $unserialized_extravars['tax_percentage'] != '' )? $unserialized_extravars['tax_percentage'] : 0;
                    }

                    if (!empty($user_info)) {
                        $user_id = $user_info->ID;
                        $userPlan = get_user_meta($user_id, 'arm_user_plan_ids', true);
                        $userPlan = !empty($userPlan) ? $userPlan : array();

                        $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                        $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                        $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                        $planData = shortcode_atts($defaultPlanData, $userPlanDatameta);

                        $payment_cycle = $planData['arm_payment_cycle'];
                        $planDetail = $planData['arm_current_plan_detail'];
                        $tax_amount = 0;

                        if (!empty($planDetail)) {
                            $plan = new ARM_Plan(0);
                            $plan->init((object) $planDetail);
                            $plan_data = $plan->prepare_recurring_data($payment_cycle);
                            $plan_amount = $plan_data['amount'];
                            
                            if($tax_percentage > 0 && $plan_amount != '') {
                                $tax_amount = ($tax_percentage*$plan_amount)/100;
                                $tax_amount = number_format((float)$tax_amount , 2, '.', '');
                            }

                        } else {
                            $plan = new ARM_Plan($entry_plan);
                            $recurring_data = $plan->prepare_recurring_data($payment_cycle);
                            $plan_amount = $recurring_data['amount']; 
                          
                            if($tax_percentage > 0 && $plan_amount != ''){
                                $tax_amount = ($tax_percentage*$plan_amount)/100;
                                $tax_amount = number_format((float)$tax_amount , 2, '.', '');
                            }
                        }
                        
                        $payment_mode = $planData['arm_payment_mode'];
                        $user_subsdata = $planData['arm_payfast'];
                        
                        $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'cancel_subscription');
                        do_action('arm_cancel_subscription', $user_id, $entry_plan);
                        $arm_subscription_plans->arm_clear_user_plan_detail($user_id, $entry_plan);

                        $cancel_plan_act = isset($plan->options['cancel_action']) ? $plan->options['cancel_action'] : 'block';
                        if ($arm_subscription_plans->isPlanExist($cancel_plan_act)) {
                            $arm_members_class->arm_new_plan_assigned_by_system($cancel_plan_act, $entry_plan, $user_id);
                        } else {
                        }
                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $entry_plan, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                        
                        $payment_log_id = self::arm_store_payfast_log($armPayfastData, $user_id, $entry_plan, $extraVars);
                        
                        //function arm_store_payfast_log($payfast_response = '', $user_id = 0, $plan_id = 0, $extraVars = array(), $arm_display_log = '1') {
                    }
                }   
            }
        }
    }
    
    

    function arm2_add_user_and_transaction($entry_id = 0, $payfast_response, $arm_display_log = 1) {
        global $wpdb, $payfast, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication, $arm_manage_coupons, $payment_done, $paid_trial_stripe_payment_done, $arm_members_class,$arm_transaction,$arm_membership_setup;
        if (isset($entry_id) && $entry_id != '') {
            $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
            
            if (isset($all_payment_gateways['payfast']) && !empty($all_payment_gateways['payfast'])) {
                $options = $all_payment_gateways['payfast'];
                $payfast_payment_mode = $options['payfast_payment_mode'];
                
                $is_sandbox_mode = $payfast_payment_mode == "sandbox" ? true : false;
                $currency = $arm_payment_gateways->arm_get_global_currency();
                
                

                $entry_data = $wpdb->get_row("SELECT * FROM `" . $ARMember->tbl_arm_entries . "` WHERE `arm_entry_id`='" . $entry_id . "' ", ARRAY_A);
                $entry_values = maybe_unserialize($entry_data['arm_entry_value']);
                $entry_email = isset($entry_data['arm_entry_email']) ? $entry_data['arm_entry_email'] : '';
                $arm_log_plan_id = $entry_data['arm_plan_id'];
                $arm_log_amount = isset($entry_values['arm_total_payable_amount']) ? $entry_values['arm_total_payable_amount'] : '';
                
                
                $plan = new ARM_Plan($arm_log_plan_id);
                
                $arm_payment_type = $plan->payment_type;
                $entry_id = $entry_id;
                $payment_status = 'success';
                $form_id = $entry_data['arm_form_id'];
                $armform = new ARM_Form('id', $form_id);
                $user_info = get_user_by('email', $entry_email);
                $user_id = isset($user_info->ID) ? $user_info->ID : 0;

                $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);

                
               
                $extraParam = array();
                $tax_percentage = isset($entry_values['tax_percentage']) ? $entry_values['tax_percentage'] : 0;
                $extraParam['tax_percentage'] = $tax_percentage;
                $payment_mode = $entry_values['arm_selected_payment_mode'];
                $payment_cycle = $entry_values['arm_selected_payment_cycle'];
                
                $arm_user_old_plan = (isset($entry_values['arm_user_old_plan']) && !empty($entry_values['arm_user_old_plan'])) ? explode(",", $entry_values['arm_user_old_plan']) : array();
                $setup_id = $entry_values['setup_id'];
                $entry_plan = $entry_data['arm_plan_id'];
                
                // $ARMember->arm_write_response("PayFast Response =>".maybe_serialize($user_detail_first_name.' '.$user_detail_last_name));
                // die();
                
                $payfastLog['arm_coupon_code'] = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                $payfastLog['arm_payment_type'] = $arm_payment_type;
                $payfastLog['payment_type'] = $arm_payment_type;
                $payfastLog['payment_status'] = $payment_status;
                $payfastLog['payer_email'] = $entry_email;
                $payfastLog['arm_first_name']   =   $user_detail_first_name;
                $payfastLog['arm_last_name']    =   $user_detail_last_name;
                $extraParam['payment_type'] = 'payfast';
                $extraParam['payment_mode'] = $payfast_payment_mode;
                $extraParam['arm_is_trial'] = '0';
                $extraParam['subs_id'] = $payfast_response['token'];
                $extraParam['trans_id'] = $payfast_response['pf_payment_id'];
                //$cardnumber = $payfast_response->data->authorization->last4;
                //$extraParam['card_number'] = $arm_transaction->arm_mask_credit_card_number($cardnumber);
                //$extraParam['error'] = '';
                $extraParam['date'] = current_time('mysql');
                //$extraParam['message_type'] = '';

                $amount = '';
                $new_plan = new ARM_Plan($entry_plan);
                
                
                //$ARMember->arm_write_response("Recurring Payment Details =>".maybe_serialize($new_plan->is_recurring()));
                
                if($new_plan->is_recurring() ) {
                    if(in_array($entry_plan, $arm_user_old_plan) && $user_id > 0 ) {
                        $plan_action = 'renew_subscription';
                        $is_recurring_payment = $arm_subscription_plans->arm_is_recurring_payment_of_user($user_id, $entry_plan, $payment_mode);


                        if($is_recurring_payment) {
                            $plan_action = 'recurring_payment';
                            $planData = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                            $oldPlanDetail = $planData['arm_current_plan_detail'];
                            if(!empty($oldPlanDetail)) {
                                $plan = new ARM_Plan(0);
                                $plan->init((object) $oldPlanDetail);
                                $plan_data = $plan->prepare_recurring_data($payment_cycle);
                                $extraParam['plan_amount'] = $plan_data['amount'];
                            }
                        } else {
                            $plan_data = $new_plan->prepare_recurring_data($payment_cycle);
                            $extraParam['plan_amount'] = $plan_data['amount'];
                            $plan_action = 'change_subscription';
                        }
                    } else {
                        $plan_data = $new_plan->prepare_recurring_data($payment_cycle);
                        $extraParam['plan_amount'] = $plan_data['amount'];
                    }
                } else {
                    $extraParam['plan_amount'] = $new_plan->amount;
                }
                
                

                $discount_amt = $extraParam['plan_amount'];
                $arm_coupon_discount = 0;
                //$amount_for_tax = $arm_log_amount;
                $amount_for_tax = $discount_amt;
                $arm_coupon_on_each_subscriptions = 0;
                $couponCode = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                if(!empty($couponCode)) {
                    $couponApply = $arm_manage_coupons->arm_apply_coupon_code($couponCode, $new_plan, $setup_id, $payment_cycle, $arm_user_old_plan);
                    if($couponApply["status"] == "success") {
                        $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                        $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                        $extraParam['coupon'] = array(
                            'coupon_code' => $couponCode,
                            'amount' => $coupon_amount,
                        );

                        $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : 0;

                        $arm_coupon_discount = $couponApply['discount'];
                        $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $global_currency : "%";
                        $payfastLog['coupon_code'] = $couponCode;
                        $payfastLog['arm_coupon_discount'] = $arm_coupon_discount;
                        $payfastLog['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                        $payfastLog['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                    }
                } 

                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $payfastLog['currency'] = $currency;
                $payfastLog['payment_amount'] = $discount_amt;
                    
                /* $ARMember->arm_write_response("Arm Form Response =>".maybe_serialize($defaultPlanData));
                die(); */
                
                //$ARMember->arm_write_response("reputelog payfast form_type =>".$armform->type);
                if(!$user_info && in_array($armform->type, array('registration'))) {
                    $user_id = $arm_member_forms->arm_register_new_member($entry_values, $armform);

                    if (is_numeric($user_id) && !is_array($user_id)) {
                        $arm_payfast_transaction_meta_detail = array();
                        if ($arm_payment_type == 'subscription') {
                            $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                            $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                            $userPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);
                            
                            $userPlanData['arm_payfast']['transaction_id'] = $payfast_response['pf_payment_id'];
                            //$userPlanData['arm_payfast']['arm_payfast_customer_code'] = $arm_payfast_customer_code;
                            $userPlanData['arm_payfast']['arm_payfast_subscription_code'] = $payfast_response['token'];
                            $userPlanData['arm_payfast']['arm_payfast_subscription_token'] = $payfast_response['token'];
                            //$ARMember->arm_write_response("reputelog payfast add user transaction 1 =>".maybe_serialize($userPlanData));
                            update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                            $pgateway = 'payfast';
                            $arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $payment_log_id, $pgateway, $userPlanData);
                        }
                        
                        $arm_payfast_subscription_transaction_id = $payfast_response['token'];
                        
                        $payfastLog['arm_payfast_subscription_code'] = !empty($payfast_response['token']) ? $payfast_response['token'] : '';
                        update_user_meta($user_id, 'arm_entry_id', $entry_id);
                        /**
                         * Send Email Notification for Successful Payment
                         */
                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $entry_plan, 'user_id' => $user_id, 'action' => 'new_subscription'));
                        
                        $payment_log_id = self::arm_store_payfast_log($payfastLog, $user_id, $entry_plan, $extraParam);
                        //$ARMember->arm_write_response("Log Data: =>".maybe_serialize($payment_log_id));
                    }
                } else {
                    $user_id = $user_info->ID;
                    if(!empty($user_id)) {
                        $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                        $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                        $userPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);
                        //$ARMember->arm_write_response("reputelog payfast 8 user_id =>".$user_id);
                        if (!$is_multiple_membership_feature->isMultipleMembershipFeature){
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
                                $old_subscription_id = $oldPlanData['arm_payfast']['transaction_id'];
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
                                $payfastLog['payment_amount'] = $amount_for_tax;
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
                                /*payfast subscription start*/
                                    $payfastLog['arm_payment_mode'] = $payment_mode;
                                    //$ARMember->arm_write_response("reputelog payfast 3 payment_mode =>".$payment_mode);
                                    if($payment_mode=='auto_debit_subscription')
                                    {
                                        //Code for Update Payment Cycle if subscription is pause and continue from payment gateway.
                                        /*$arm_next_due_payment_date = $userPlanData['arm_next_due_payment'];
                                        
                                        $now = current_time('mysql');
                                        $arm_last_payment_status = $wpdb->get_var($wpdb->prepare("SELECT `arm_transaction_status` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d AND `arm_payment_date`<=%s ORDER BY `arm_log_id` DESC LIMIT 0,1", $user_id, $entry_plan, $now));
                                        
                                        $ARMember->arm_write_response("Next Due Payment Date =>".maybe_serialize($userPlanData));
                                        
                                        if($arm_last_payment_status == 'canceled'){
                                            $arm_next_due_date = '';
                                            if($recurring_data['period']=='M' && $recurring_data['interval']=='1'){
                                                if($arm_next_due_payment_date == ''){
                                                    $arm_next_due_date = strtotime('+'.$recurring_data['interval'].' months', $now);
                                                }else{
                                                    $arm_next_due_date = strtotime('+'.$recurring_data['interval'].' months', strtotime($arm_next_due_payment_date));
                                                }
                                            }
                                        }*/
                                        


                                        $payfastLog['arm_payment_mode'] = $payment_mode;
                                        //$ARMember->arm_write_response("Recurring Data =>".maybe_serialize($recurring_data));

                                        $arm_payfast_subscription_amount = $amount_for_tax * 100;
                                        
                                        $arm_payfast_plancode = !empty($arm_plan_payfast_code) ? $arm_plan_payfast_code : '';
                                    }
                                    /*payfast subscription end*/
                                update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                $userPlanData['arm_user_gateway'] = 'payfast';

                                if (!empty($payfast_response['pf_payment_id'])) {
                                    $userPlanData['arm_payfast']['transaction_id'] = $payfast_response['pf_payment_id'];
                                    $userPlanData['arm_payfast']['arm_payfast_subscription_code'] = $payfast_response['token'];
                                    $userPlanData['arm_payfast']['arm_payfast_subscription_token'] = $payfast_response['token'];
                                }
                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                $payfastLog['arm_payfast_subscription_code'] = !empty($payfast_response['token']) ? $payfast_response['token'] : '';
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
                                $old_subscription_id = $oldPlanData['arm_payfast']['transaction_id'];



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
                                    $payfastLog['payment_amount'] = $amount_for_tax;
                                    $payfastLog['arm_payment_mode'] = $payment_mode;
                                    /*payfast subscription start*/
                                    //$ARMember->arm_write_response("reputelog payfast 5 payment_mode =>".$payment_mode);
                                    if($payment_mode=='auto_debit_subscription')
                                    {
                                        $payfastLog['arm_payment_mode'] = $payment_mode;
                                        //$ARMember->arm_write_response("reputelog payfast 6 payment_mode =>".$payment_mode);


                                        $arm_payfast_subscription_amount = $amount_for_tax * 100;
                                        $arm_payfast_plancode = !empty($arm_plan_payfast_code) ? $arm_plan_payfast_code : '';
                                    }
                                    /*payfast subscription end*/

                                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                    $userPlanData['arm_user_gateway'] = 'payfast';

                                    if (!empty($payfast_response['pf_payment_id'])) {
                                        $userPlanData['arm_payfast']['transaction_id'] = $payfast_response['pf_payment_id'];
                                        $userPlanData['arm_payfast']['arm_payfast_customer_code'] = $arm_payfast_customer_code;
                                        $userPlanData['arm_payfast']['arm_payfast_subscription_code'] = $payfast_response['token'];
                                        $userPlanData['arm_payfast']['arm_payfast_subscription_token'] = $payfast_response['token'];
                                        $payfastLog['arm_payfast_subscription_code'] = $payfast_response['token'];
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
                                /*if (!empty($recurring_data['trial']) && empty($arm_user_old_plan)) {
                                    $extraParam['trial'] = array(
                                        'amount' => $recurring_data['trial']['amount'],
                                        'period' => $recurring_data['trial']['period'],
                                        'interval' => $recurring_data['trial']['interval'],
                                    );
                                    $amount_for_tax = $recurring_data['trial']['amount'];
                                }*/

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
                                    $payfastLog['payment_amount'] = $amount_for_tax;

                                update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                $userPlanData['arm_user_gateway'] = 'payfast';

                                if (!empty($arm_token)) {
                                    $userPlanData['arm_payfast']['transaction_id'] = $arm_token;
                                }
                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                if ($is_update_plan) {
                                    $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan,  '', true, $arm_last_payment_status);
                                } else {
                                   $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'new_subscription');
                                }
                            }
                        }
                        
                        $payfastLog['arm_payfast_response'] = maybe_serialize($payfast_response);
                        
                        $payment_log_id = self::arm_store_payfast_log($payfastLog, $user_id, $entry_plan, $extraParam, $arm_display_log);

                        if ($arm_payment_type == 'subscription') {
                            if($plan_action=='recurring_payment')
                            {
                                $user_subsdata = isset($planData['arm_payfast']) ? $planData['arm_payfast'] : array();
                                do_action('arm_after_recurring_payment_success_outside',$user_id,$entry_plan,'payfast',$payment_mode,$user_subsdata);
                            }
                            //$ARMember->arm_write_response("payfast subscription 1 arm_payment_type ".$arm_payment_type);
                            $pgateway = 'payfast';
                            $arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $payment_log_id, $pgateway, $userPlanData);
                        }
                    }
                }
            }
        }
        return $user_id;
    }
    
    function arm_store_payfast_log($payfast_response = '', $user_id = 0, $plan_id = 0, $extraVars = array(), $arm_display_log = '1') {

        global $wpdb, $ARMember, $arm_global_settings, $arm_member_forms, $arm_payment_gateways;
        $payment_log_table = $ARMember->tbl_arm_payment_log;
        $arm_payfast_response = maybe_unserialize($payfast_response['arm_payfast_response']);
        
        $ARMember->arm_write_response("PayFast Response ". maybe_serialize($payfast_response));
        
        $arm_payfast_transaction_id = !empty($payfast_response['pf_payment_id']) ? $payfast_response['pf_payment_id'] : '';
        $arm_payfast_subscription_id = !empty($payfast_response['arm_payfast_subscription_code']) ? $payfast_response['arm_payfast_subscription_code']: '';
        $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $arm_payfast_transaction_id));
        
        $ARMember->arm_write_response("PayFast Response Data: =>".maybe_serialize($payfast_response));
        
        
        if (!empty($payfast_response) && !empty($transaction)) {
            $payment_data = array(
                'arm_user_id' => $user_id,
                'arm_first_name'=>$payfast_response['arm_first_name'],
                'arm_last_name'=>$payfast_response['arm_last_name'],
                'arm_plan_id' => (!empty($plan_id) ? $plan_id : 0),
                'arm_payment_gateway' => 'payfast',
                'arm_payment_type' => $payfast_response['arm_payment_type'],
                'arm_token' => $arm_payfast_subscription_id,
                'arm_payer_email' => $payfast_response['payer_email'],
                'arm_receiver_email' => '',
                'arm_transaction_id' => $arm_payfast_transaction_id,
                'arm_transaction_payment_type' => $payfast_response['payment_type'],
                'arm_transaction_status' => $payfast_response['payment_status'],
                'arm_payment_date' => date('Y-m-d H:i:s', strtotime($arm_payfast_response->data->paidAt)),
                'arm_payment_mode' => $payfast_response['arm_payment_mode'],
                'arm_amount' => $payfast_response['payment_amount'],
                'arm_currency' => $payfast_response['currency'],
                'arm_coupon_code' => $payfast_response['arm_coupon_code'],
                'arm_coupon_discount' => (isset($payfast_response['arm_coupon_discount']) && !empty($payfast_response['arm_coupon_discount'])) ? $payfast_response['arm_coupon_discount'] : 0,
                'arm_coupon_discount_type' => isset($payfast_response['arm_coupon_discount_type']) ? $payfast_response['arm_coupon_discount_type'] : '',
                'arm_response_text' => maybe_serialize($arm_payfast_response),
                'arm_extra_vars' => maybe_serialize($extraVars),
                'arm_is_trial' => isset($payfast_response['arm_is_trial']) ? $payfast_response['arm_is_trial'] : 0,
                'arm_display_log' => $arm_display_log,
                'arm_created_date' => current_time('mysql'),
                'arm_coupon_on_each_subscriptions' => !empty($payfast_response['arm_coupon_on_each_subscriptions']) ? $payfast_response['arm_coupon_on_each_subscriptions'] : 0,
            );
            $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
            $ARMember->arm_write_response("Payment Log ID => ".maybe_serialize($payment_log_id));
            return $payment_log_id;
        }
        return false;
    }

    
    function arm2_payfast_cancel_subscription($user_id, $plan_id){
        global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
        if (isset($user_id) && $user_id != 0 && isset($plan_id) && $plan_id != 0) {
            $user_detail = get_userdata($user_id);
            $payer_email = $user_detail->user_email;
            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
            $currency = $arm_payment_gateways->arm_get_global_currency();
            $ARMember->arm_write_response("reputelog payfast cancel 1 planData => ".maybe_serialize($planData));
            if(!empty($planData)){
                $user_payment_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
                if (strtolower($user_payment_gateway) == 'payfast') 
                {
                    $user_payfast_data = $planData['arm_payfast'];
                    //$ARMember->arm_write_response("reputelog payfast  cancel 2 planData => ".maybe_serialize($planData));
                    //die;

                    $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                    $payfast_transaction_id = isset($user_payfast_data['transaction_id']) ? $user_payfast_data['transaction_id'] : '';
                    $arm_payfast_customer_code = isset($user_payfast_data['arm_payfast_customer_code']) ? $user_payfast_data['arm_payfast_customer_code'] : '';

                    $arm_payfast_subscription_code = isset($user_payfast_data['arm_payfast_subscription_code']) ? $user_payfast_data['arm_payfast_subscription_code'] : '';

                    $arm_payfast_subscription_token = isset($user_payfast_data['arm_payfast_subscription_token']) ? $user_payfast_data['arm_payfast_subscription_token'] : '';
                            
                    $planDetail = $planData['arm_current_plan_detail'];

                    if (!empty($planDetail)) { 
                        $planObj = new ARM_Plan(0);
                        $planObj->init((object) $planDetail);
                    } else {
                        $planObj = new ARM_Plan($plan_id);
                    }

                    $payment_log_table = $ARMember->tbl_arm_payment_log;
                    $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type,arm_amount FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s AND `arm_display_log` = %d ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'payfast', 'success', 1));
                    
                    // $ARMember->arm_write_response("Transaction Data => ".maybe_serialize($transaction));
                    // die();
                     
                    if (!empty($transaction)) {
                        $extra_var = maybe_unserialize($transaction->arm_extra_vars);
                        
                        $payer_email = $transaction->arm_payer_email;
                        $payment_type = $extra_var['payment_type'];
                        $payment_mode = $extra_var['payment_mode'];
                        $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;

                        $gateway_options = get_option('arm_payment_gateway_settings');
                        $pgoptions = maybe_unserialize($gateway_options);
                        $payfast_options = $pgoptions['payfast'];
                        if($payfast_options['payfast_payment_mode']=='sandbox')
                        {
                            $payfast_secret_key  = $payfast_options['payfast_test_merchant_id'];
                        }
                        else
                        {
                            $payfast_secret_key  = $payfast_options['payfast_live_merchant_id'];
                        }
                        //$ARMember->arm_write_response("reputelog payfast cancel payfast_secret_key => ".maybe_serialize($payfast_secret_key));
                        
                        $ARMember->arm_write_response("reputelog payfast extra_var => ".maybe_serialize($extra_var));
                        
                        if ($payment_type == 'payfast') {
                            if($user_selected_payment_mode == 'auto_debit_subscription') {
                                $ARMember->arm_write_response("If Condition User Id & Plan ID => ".maybe_serialize($user_id." ".$plan_id));
                                $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                                $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                $payment_data = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan_id,
                                    'arm_first_name'=> $user_detail_first_name,
                                    'arm_last_name'=> $user_detail_last_name,
                                    'arm_payment_gateway' => 'payfast',
                                    'arm_payment_type' => 'subscription',
                                    'arm_token' => $arm_payfast_subscription_code,
                                    'arm_payer_email' => $payer_email,
                                    'arm_receiver_email' => '',
                                    'arm_transaction_id' => $transaction->arm_transaction_id,
                                    'arm_transaction_payment_type' => $transaction->arm_transaction_payment_type,
                                    'arm_transaction_status' => 'canceled',
                                    'arm_payment_date' => current_time('mysql'),
                                    'arm_amount' => $transaction->arm_amount,
                                    'arm_currency' => $currency,
                                    'arm_coupon_code' => '',
                                    'arm_response_text' => maybe_serialize($response),
                                    'arm_is_trial' => '0',
                                    'arm_created_date' => current_time('mysql')
                                );
                                //$is_cancelled_by_system = get_user_meta($user_id, 'arm_payment_cancelled_by', true);
                                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
                                //$ARMember->arm_write_response("User Meta Data => ".$is_cancelled_by_system);
                                delete_user_meta($user_id, 'arm_payment_cancelled_by');
                                return;
                            } else {
                                $ARMember->arm_write_response("Else Condition User Id & Plan ID => ".maybe_serialize($user_id." ".$plan_id));
                                $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);

                                $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                $payment_data = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan_id,
                                    'arm_first_name'=> $user_detail_first_name,
                                    'arm_last_name'=> $user_detail_last_name,
                                    'arm_payment_gateway' => 'payfast',
                                    'arm_payment_type' => 'subscription',
                                    'arm_payer_email' => $payer_email,
                                    'arm_receiver_email' => '',
                                    'arm_transaction_id' => $transaction->arm_transaction_id,
                                    'arm_token' => $arm_payfast_subscription_code,
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
                                //$ARMember->arm_write_response("reptuelog payment log id => ".$payment_log_id);
                                delete_user_meta($user_id, 'arm_payment_cancelled_by');
                                return;
                            }
                        }
                    }
                }
            }
        }
    }
    
    function arm_payfast_modify_coupon_code($data,$payment_mode,$couponData,$planAmt, $plan_obj){

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
    
    function arm_filter_cron_hook_name_outside_func($cron_hook_array){
        $cron_hook_array[] = 'arm_membership_payfast_recurring_payment';
        return $cron_hook_array;
    }
}

global $arm_payfast;
$arm_payfast = new ARM_Payfast();


global $armpayfast_api_url, $armpayfast_plugin_slug;

$armpayfast_api_url = $arm_payfast->armpayfast_getapiurl();
$armpayfast_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armpayfast_check_for_plugin_update');

function armpayfast_check_for_plugin_update($checked_data) {
    global $armpayfast_api_url, $armpayfast_plugin_slug, $wp_version, $arm_payfast_version,$arm_payfast;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armpayfast_plugin_slug,
        'version' => $arm_payfast_version,
        'other_variables' => $arm_payfast->armpayfast_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(ARM_PAYFAST_HOME_URL)
        ),
        'user-agent' => 'ARMPAYFAST-WordPress/' . $wp_version . '; ' . ARM_PAYFAST_HOME_URL
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armpayfast_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armpayfast_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armpayfast_plugin_slug . '/' . $armpayfast_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armpayfast_plugin_api_call', 10, 3);

function armpayfast_plugin_api_call($def, $action, $args) {
    global $armpayfast_plugin_slug, $armpayfast_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armpayfast_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armpayfast_plugin_slug . '/' . $armpayfast_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armpayfast_update_token'),
            'request' => serialize($args),
            'api-key' => md5(ARM_PAYFAST_HOME_URL)
        ),
        'user-agent' => 'ARMPAYFAST-WordPress/' . $wp_version . '; ' . ARM_PAYFAST_HOME_URL
    );

    $request = wp_remote_post($armpayfast_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', 'ARM_PAYFAST'), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', 'ARM_PAYFAST'), $request['body']);
    }

    return $res;
}

class PayfastConfigWrapper {

    public static function getConfig() {
        global $arm_payfast;
        return $arm_payfast->arm_payfast_config();
    }

}

class CreatePayfastPaymentRequest {

    public static function main($data_array) {
        global $ARMember;
        
        $arm_payfast_secret_key = $data_array['arm_payfast_secret_key'];
        $arm_payfast_public_key = $data_array['arm_payfast_public_key'];

        //$arm_payfast_url = 'https://api.payfast.co/transaction/initialize';
        $arm_payfast_url = 'https://www.payfast.co.za/eng/process';
        $redirect_url = $data_array['redirect_url'];
        
        $amount = $data_array['arm_plan_amount'] * 100;
        $productinfo = $data_array['arm_plan_name'];
        $reference = $data_array['reference'];
        $firstname = $data_array['first_name'];
        $lastname = $data_array['last_name'];
        $email = $data_array['user_email'];
        $currency = $data_array['currency'];
        $PayfastPlanID = $data_array['arm_payfast_plancode'] ;
        $arm_payfast_subscription_code = $data_array['arm_payfast_subscription_code'] ;
        $arm_payfast_invoice_limit = $data_array['arm_payfast_invoice_limit'];
        $arm_has_trial_period = $data_array['arm_has_trial_period'];
        $arm_payfast_trail_invoice_limit = $data_array['arm_payfast_trail_invoice_limit'];
        $arm_coupon_code = $data_array['arm_coupon_code'];
        $arm_payfast_entry_id = $data_array['arm_payfast_entry_id'];
        
        $arm_payfast_headers = array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$arm_payfast_secret_key
        );
        $recurring_payment_mode = $data_array['recurring_payment_mode'] ;

        if($recurring_payment_mode=='auto_debit_subscription' )
        {
            $arm_payfast_body = array(
                'email'        => $email,
                'first_name'   => $firstname,
                'last_name'   => $lastname,
                'amount'       => $amount,
                'reference'    => $reference,
                'currency'     => $currency,
                'callback_url' => $redirect_url,
                'metadata'      => array(
                                    'arm_plan_payfast_code' => $PayfastPlanID,
                                    'arm_payfast_entry_id' => $arm_payfast_entry_id,
                                    'arm_payfast_subscription_code' => $arm_payfast_subscription_code

                                    ),
                'invoice_limit' => $arm_payfast_invoice_limit
            );
        }
        else
        {
            $arm_payfast_body = array(
                'email'        => $email,
                'first_name'   => $firstname,
                'last_name'   => $lastname,
                'amount'       => $amount,
                'reference'    => $reference,
                'currency'     => $currency,
                'callback_url' => $redirect_url,
                'metadata'      => array(
                                    'arm_payfast_entry_id' => $arm_payfast_entry_id,
                                    ),
            );
        }
        
        //$ARMember->arm_write_response("reputelog payfast create payment arm_payfast_body =>".maybe_serialize($arm_payfast_body));
        $arm_payfast_args = array(
            'body'      => json_encode($arm_payfast_body),
            'headers'   => $arm_payfast_headers,
            'timeout'   => 60
        );

        $arm_payfast_request = wp_remote_post($arm_payfast_url, $arm_payfast_args);
        $arm_payfast_response = json_decode(wp_remote_retrieve_body($arm_payfast_request));
        //$ARMember->arm_write_response("reputelog payfast -7.1.2.1- arm_payfast_response =>".maybe_serialize($arm_payfast_response));
        //$arm_payfast_autho_url = $arm_payfast_response->data->authorization_url;

        return $arm_payfast_response;
        
    }

}
?>