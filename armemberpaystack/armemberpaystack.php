<?php 
/*
  Plugin Name: ARMember - Paystack payment gateway Addon
  Description: Extension for ARMember plugin to accept payments using Paystack.
  Version: 1.7
  Plugin URI: https://www.armemberplugin.com
  Author: Repute InfoSystems
  Author URI: https://www.armemberplugin.com
 */

define('ARM_PAYSTACK_DIR_NAME', 'armemberpaystack');
define('ARM_PAYSTACK_DIR', WP_PLUGIN_DIR . '/' . ARM_PAYSTACK_DIR_NAME);

if (is_ssl()) {
    define('ARM_PAYSTACK_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_PAYSTACK_DIR_NAME));
    define('ARM_PAYSTACK_HOME_URL', home_url('','https'));
} else {
    define('ARM_PAYSTACK_URL', WP_PLUGIN_URL . '/' . ARM_PAYSTACK_DIR_NAME);
    define('ARM_PAYSTACK_HOME_URL', home_url());
}

define('ARM_PAYSTACK_TEXTDOMAIN', 'ARM_PAYSTACK');

define('ARM_PAYSTACK_DOC_URL', ARM_PAYSTACK_URL . '/documentation/index.html#content');

global $arm_paystack_version;
$arm_paystack_version = '1.7';

global $armnew_paystack_version;


global $armpaystack_api_url, $armpaystack_plugin_slug, $wp_version;

class ARM_Paystack{
    
    function __construct() {
        global $arm_payment_gateways, $arm_transaction;
        $arm_payment_gateways->currency['paystack'] = $this->arm_paystack_currency_symbol();

        add_action('init', array(&$this, 'arm_paystack_db_check'));

        register_activation_hook(__FILE__, array('ARM_Paystack', 'install'));

        register_activation_hook(__FILE__, array('ARM_Paystack', 'arm_paystack_check_network_activation'));

        register_uninstall_hook(__FILE__, array('ARM_Paystack', 'uninstall'));

        add_filter('arm_get_payment_gateways', array(&$this, 'arm_add_paystack_payment_gateways'));
        add_filter('arm_get_payment_gateways_in_filters', array(&$this, 'arm_add_paystack_payment_gateways'));
        add_action('admin_notices', array(&$this, 'arm_paystack_admin_notices'));
        add_filter('arm_change_payment_gateway_tooltip', array(&$this, 'arm_change_payment_gateway_tooltip_func'), 10, 3);
        add_filter('arm_gateway_callback_info', array(&$this, 'arm_gateway_callback_info_func'), 10, 3);
        add_filter('arm_filter_gateway_names', array(&$this, 'arm_filter_gateway_names_func'), 10);
        add_filter('arm_payment_gateway_has_plan_field_outside', array(&$this, 'arm_payment_gateway_has_plan_field_outside_func'), 10, 6);
        //add_action('arm_show_payment_gateway_recurring_notice', array(&$this, 'arm_show_payment_gateway_paystack_recurring_notice'), 10);
        add_filter('arm_set_gateway_warning_in_plan_with_recurring', array(&$this, 'arm_paystack_recurring_trial'), 10);
        add_filter('arm_allowed_payment_gateways', array(&$this, 'arm_payment_allowed_gateways'), 10, 3);
        add_action('arm_payment_related_common_message', array(&$this, 'arm_payment_related_common_message'), 10);

        add_filter('arm_currency_support', array(&$this, 'arm_paystack_currency_support'), 10, 2);
        //add_filter('arm_not_display_payment_mode_setup', array(&$this, 'arm_not_display_payment_mode_setup_func'), 10, 1);

        add_action('arm_after_payment_gateway_listing_section', array(&$this, 'arm_after_payment_gateway_listing_section_func'), 10, 2);

        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_script'), 10);

        add_action('wp_head', array(&$this, 'arm_paystack_set_front_js'), 10);
        
        add_action('plugins_loaded', array(&$this, 'arm_paystack_load_textdomain'));
        
        add_action('admin_init', array(&$this, 'upgrade_data_paystack'));
        
        //add_filter('arm_change_coupon_code_outside_from_paystack',array(&$this,'arm_paystack_modify_coupon_code'),10,5); //
        
        add_filter('arm_change_pending_gateway_outside',array(&$this,'arm2_change_pending_gateway_outside'),100,3);
        
        add_filter('arm_membership_update_user_meta_from_outside', array(&$this, 'arm2_membership_paystack_update_usermeta'), 10, 5);
        
        add_action('arm_update_user_meta_after_renew_outside', array(&$this, 'arm2_paystack_update_meta_after_renew'), 10, 4);
        
        add_filter('arm_default_plan_array_filter', array(&$this, 'arm2_default_plan_array_filter_func'), 10, 1);
        
        add_filter('arm_need_to_cancel_old_subscription_gateways', array(&$this, 'arm2_need_to_cancel_old_subscription_gateways'), 10, 1);
        
        add_action('arm_payment_gateway_validation_from_setup', array(&$this, 'arm2_payment_gateway_form_submit_action'), 10, 4);
        
        add_action('wp', array(&$this, 'arm2_paystack_webhook'), 5);
        
        add_action('arm_cancel_subscription_gateway_action', array(&$this, 'arm2_paystack_cancel_subscription'), 10, 2);

        add_action('arm_enqueue_js_css_from_outside', array(&$this,'arm_enqueue_paystack_js_css_for_model'),10);

        add_filter('arm_filter_cron_hook_name_outside', array(&$this, 'arm_filter_cron_hook_name_outside_func'), 10);

        add_action('arm_membership_paystack_recurring_payment', array(&$this, 'arm2_membership_paystack_check_recurring_payment'));
    }
    
    

    function arm2_need_to_cancel_old_subscription_gateways( $payment_gateway_array ) {
        array_push($payment_gateway_array, 'paystack');
        return $payment_gateway_array;
    }
    
    function arm2_default_plan_array_filter_func( $default_plan_array ) {
        global $ARMember;
        $default_plan_array['arm_paystack'] = '';
        return $default_plan_array;
    }
    
    function arm2_membership_paystack_update_usermeta($posted_data, $user_id, $plan, $log_detail, $pgateway) {
        if ($pgateway == 'paystack') {
            $posted_data['arm_paystack'] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
        }
        return $posted_data;
    }
    
    function arm2_paystack_update_meta_after_renew($user_id, $log_detail, $plan_id, $payment_gateway) {
        global $ARMember;
        if ($payment_gateway == 'paystack') {
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
                $plan_data['arm_paystack'] = $pg_subsc_data;
                update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $plan_data);
            }
        }
    }
    
    function arm_paystack_load_textdomain() {
        load_plugin_textdomain(ARM_PAYSTACK_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public static function arm_paystack_db_check() {
        global $arm_paystack;
        $arm_paystack_version = get_option('arm_paystack_version');

        if (!isset($arm_paystack_version) || $arm_paystack_version == '')
            $arm_paystack->install();
    }

    function armpaystack_getapiurl() {
            $api_url = 'https://www.arpluginshop.com/';
            return $api_url;
        }
        
    function upgrade_data_paystack() {
        global $armnew_paystack_version;

        if (!isset($armnew_paystack_version) || $armnew_paystack_version == "")
            $armnew_paystack_version = get_option('arm_paystack_version');

        if (version_compare($armnew_paystack_version, '1.7', '<')) {
            $path = ARM_PAYSTACK_DIR . '/upgrade_latest_data_paystack.php';
            include($path);
        }
    }
    
    function armpaystack_get_remote_post_params($plugin_info = "") {
            global $wpdb;
    
            $action = "";
            $action = $plugin_info;
    
            if (!function_exists('get_plugins')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            $plugin_list = get_plugins();
            $site_url = ARM_PAYSTACK_HOME_URL;
            $plugins = array();
    
            $active_plugins = get_option('active_plugins');
    
            foreach ($plugin_list as $key => $plugin) {
                $is_active = in_array($key, $active_plugins);
    
                //filter for only armember ones, may get some others if using our naming convention
                if (strpos(strtolower($plugin["Title"]), "armemberpaystack") !== false) {
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
        global $arm_paystack;
        $arm_paystack_version = get_option('arm_paystack_version');

        if (!isset($arm_paystack_version) || $arm_paystack_version == '') {
            global $wpdb, $arm_paystack_version;
            update_option('arm_paystack_version', $arm_paystack_version);
        }
    }

    
    /*
     * Restrict Network Activation
     */
    public static function arm_paystack_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }

    public static function uninstall() {
        delete_option('arm_paystack_version');
    }

    function arm_paystack_currency_symbol() {
        global $arm_payment_gateways, $arm_global_settings;
        $gateway_options = get_option('arm_payment_gateway_settings');
        $pgoptions = maybe_unserialize($gateway_options);
        $is_sandbox_mode  = isset($pgoptions['paystack']['paystack_payment_mode']) ? $pgoptions['paystack']['paystack_payment_mode'] : 'sandbox';
        
        if($is_sandbox_mode == 'sandbox')
        {
            $currency_symbol = array(
                'NGN' => '&#x20a6;',
            );
        }
        else
        {
            $currency_symbol = array(
                //'GBP' => '&#163;',
                'GHS' => '&#x20b5;',
                'NGN' => '&#x20a6;',
                'USD' => '$',
            );
        }
        return $currency_symbol;
    }

    function arm_add_paystack_payment_gateways($default_payment_gateways) {
        if ($this->is_version_compatible()) {
            global $arm_payment_gateways;
            $default_payment_gateways['paystack']['gateway_name'] = __('Paystack', ARM_PAYSTACK_TEXTDOMAIN);
            return $default_payment_gateways;
        } else {
            return $default_payment_gateways;
        }
    }

    function arm_paystack_admin_notices() {
        global $pagenow, $arm_slugs;    
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if (!$this->is_armember_support())
                echo "<div class='updated updated_notices'><p>" . __('Paystack For ARMember plugin requires ARMember Plugin installed and active.', ARM_PAYSTACK_TEXTDOMAIN) . "</p></div>";

            else if (!$this->is_version_compatible())
                echo "<div class='updated updated_notices'><p>" . __('Paystack For ARMember plugin requires ARMember plugin installed with version 3.0 or higher.', ARM_PAYSTACK_TEXTDOMAIN) . "</p></div>";
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
        if ($gateway_name == 'paystack') {
            return __("You can find Secret key and Public key in your Paystack account. To get more details, Please refer this", ARM_PAYSTACK_TEXTDOMAIN)." <a href='https://dashboard.paystack.com/#/settings/developer' target='_blank'>".__("document", ARM_PAYSTACK_TEXTDOMAIN)."</a>.";
        }
        return $titleTooltip;
    }
    
    function arm_gateway_callback_info_func($apiCallbackUrlInfo, $gateway_name, $gateway_options) {
        if ($gateway_name == 'paystack') {
            global $arm_global_settings;
            $apiCallbackUrl = $arm_global_settings->add_query_arg("arm-listener", "arm_paystack_api", get_home_url() . "/");
            $apiCallbackUrlInfo = __('Please make sure you have set following callback URL in your paystack account.', ARM_PAYSTACK_TEXTDOMAIN);
            $callbackTooltip = __('To get more information about how to set callback URL in your paystack account, please refer this', ARM_PAYSTACK_TEXTDOMAIN).' <a href="'. ARM_PAYSTACK_DOC_URL .'" target="_blank">'.__('document', ARM_PAYSTACK_TEXTDOMAIN).'</a>';
            //$apiCallbackUrlInfo = '<a href="'. ARM_PAYSTACK_DOC_URL .'" target="_blank">'.__('ARMember Paystack Documentation', ARM_PAYSTACK_TEXTDOMAIN).'</a>';
            

            $apiCallbackUrlInfo .= '<i class="arm_helptip_icon armfa armfa-question-circle" title="'.htmlentities($callbackTooltip).'"></i>';
            $apiCallbackUrlInfo .= '<br/><b>' . $apiCallbackUrl . '</b>';
            
        }
        return $apiCallbackUrlInfo;
    }

    function arm_filter_gateway_names_func($pgname) {
        $pgname['paystack'] = __('Paystack', ARM_PAYSTACK_TEXTDOMAIN);
        return $pgname;
    }

    function arm2_change_pending_gateway_outside($user_pending_pgway,$plan_ID,$user_id){
        global $is_free_manual,$ARMember;
        if( $is_free_manual ){
            $key = array_search('paystack',$user_pending_pgway);
            unset($user_pending_pgway[$key]);
        }
        return $user_pending_pgway;
    }
    
    function admin_enqueue_script(){
        global $arm_paystack_version, $arm_slugs;

        if(!empty($arm_slugs->general_settings)) {
            $arm_paystack_page_array = array($arm_slugs->general_settings);
            $arm_paystack_action_array = array('payment_options');
            
            if( isset($_REQUEST['page']) && isset($_REQUEST['action']) && (in_array($_REQUEST['page'], $arm_paystack_page_array) && in_array($_REQUEST['action'], $arm_paystack_action_array)) ||  (isset($_REQUEST['page']) && $_REQUEST['page']==$arm_slugs->membership_setup)) {
                wp_register_script( 'arm-admin-paystack', ARM_PAYSTACK_URL . '/js/arm_admin_paystack.js', array(), $arm_paystack_version );
                wp_enqueue_script( 'arm-admin-paystack' );
                wp_register_style('arm-admin-paystack-css', ARM_PAYSTACK_URL . '/css/arm_admin_paystack.css', array(), $arm_paystack_version);
                wp_enqueue_style('arm-admin-paystack-css');
            }    
        }
    }
    
    
    function arm_paystack_set_front_js( $force_enqueue = false ) {
        if( $this->is_version_compatible() ){
            global $ARMember, $arm_paystack_version;
            $is_arm_front_page = $ARMember->is_arm_front_page();
            if ($is_arm_front_page === TRUE || $force_enqueue == TRUE){
                wp_register_script('arm_paystack_js', ARM_PAYSTACK_URL . '/js/arm_paystack.js', array('jquery'), $arm_paystack_version);
                wp_enqueue_script('arm_paystack_js');
            }
        }
    }

    function arm_enqueue_paystack_js_css_for_model(){
        $this->arm_paystack_set_front_js(true);
    }
    
    
    function arm_paystack_recurring_trial($notice) {
        // if need to display any notice related subscription in Add / Edit plan page
        if ($this->is_version_compatible()){
            $notice .= "<span style='margin-bottom:10px;'><b>". __('Paystack (if Paystack payment gateway is enabled)',ARM_PAYSTACK_TEXTDOMAIN)."</b><br/>";
            $notice .= "<ol style='margin-left:30px;'>";
            $notice .= "<li>".__('Paystack does not support Free trial/plan amount with auto recurring billing cycle.',ARM_PAYSTACK_TEXTDOMAIN)."</li>";
            $notice .= "</ol>";
            $notice .= "</span>";
        } 
        return $notice;
    }

    function arm_payment_allowed_gateways($allowed_gateways, $plan_obj, $plan_options) {
        
        $allowed_gateways['paystack'] = "1";
        return $allowed_gateways;
    }

    function arm_payment_related_common_message($common_messages) {
        if ($this->is_version_compatible()) {
            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_payment_fail_paystack"><?php _e('Payment Fail (Paystack)', ARM_PAYSTACK_TEXTDOMAIN); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_payment_fail_paystack]" id="arm_payment_fail_paystack" value="<?php echo (!empty($common_messages['arm_payment_fail_paystack']) ) ? $common_messages['arm_payment_fail_paystack'] : 'Sorry something went wrong while processing payment with Paystack.'; ?>" />
                </td>
            </tr>
            <?php
        }
    }

    function arm_payment_gateway_has_ccfields_func($pgHasCcFields, $gateway_name, $gateway_options) {
        if ($gateway_name == 'paystack') {
            return true;
        } else {
            return $pgHasCcFields;
        }
    }

    function arm_paystack_currency_support($notAllow, $currency) {
        global $arm_payment_gateways;
        $paystack_currency = $this->arm_paystack_currency_symbol();
        if (!array_key_exists($currency, $paystack_currency)) {
            $notAllow[] = 'paystack';
        }
        return $notAllow;
    }

    function arm_not_display_payment_mode_setup_func($gateway_name_arr) {
        //for remove auto debit payment and menual payment option from front side page and admin site. Its allow only manual payment.
        $gateway_name_arr[] = 'paystack';
        return $gateway_name_arr;
    }

    function arm_after_payment_gateway_listing_section_func($gateway_name, $gateway_options) {
        // set paymetn geteway setting field in general settgin > payment gateway
        global $arm_global_settings;
        if ($gateway_name == 'paystack') {
            $gateway_options['paystack_payment_mode'] = (!empty($gateway_options['paystack_payment_mode']) ) ? $gateway_options['paystack_payment_mode'] : 'sandbox';
            $gateway_options['status'] = isset($gateway_options['status']) ? $gateway_options['status'] : 0;
            $disabled_field_attr = ($gateway_options['status'] == '1') ? '' : 'disabled="disabled"';
            $readonly_field_attr = ($gateway_options['status'] == '1') ? '' : 'readonly="readonly"';
            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Payment Mode', ARM_PAYSTACK_TEXTDOMAIN); ?> *</label></th>
                <td class="arm-form-table-content">
                    <input id="arm_paystack_payment_gateway_mode_sand" class="arm_general_input arm_paystack_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="sandbox" name="payment_gateway_settings[paystack][paystack_payment_mode]" <?php checked($gateway_options['paystack_payment_mode'], 'sandbox'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_paystack_payment_gateway_mode_sand"><?php _e('Sandbox', ARM_PAYSTACK_TEXTDOMAIN); ?></label>
                    <input id="arm_paystack_payment_gateway_mode_pro" class="arm_general_input arm_paystack_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="production" name="payment_gateway_settings[paystack][paystack_payment_mode]" <?php checked($gateway_options['paystack_payment_mode'], 'production'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_paystack_payment_gateway_mode_pro"><?php _e('Production', ARM_PAYSTACK_TEXTDOMAIN); ?></label>
                </td>
            </tr>
            <!-- ***** Begining of Sandbox Input for paystack ***** -->
            <?php
            $paystack_hidden = "hidden_section";
            if (isset($gateway_options['paystack_payment_mode']) && $gateway_options['paystack_payment_mode'] == 'sandbox') {
                $paystack_hidden = "";
            } else if (!isset($gateway_options['paystack_payment_mode'])) {
                $paystack_hidden = "";
            }
            ?>
            <tr class="form-field arm_paystack_sandbox_fields <?php echo $paystack_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Test Secret Key', ARM_PAYSTACK_TEXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_paystack_test_secret_key" name="payment_gateway_settings[paystack][paystack_test_secret_key]" value="<?php echo (!empty($gateway_options['paystack_test_secret_key'])) ? $gateway_options['paystack_test_secret_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_paystack_sandbox_fields <?php echo $paystack_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Test Public Key', ARM_PAYSTACK_TEXTDOMAIN); ?> *</th> 
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_paystack_test_public_key" name="payment_gateway_settings[paystack][paystack_test_public_key]" value="<?php echo (!empty($gateway_options['paystack_test_public_key'])) ? $gateway_options['paystack_test_public_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            
            <!-- ***** Ending of Sandbox Input for paystack ***** -->

            <!-- ***** Begining of Live Input for paystack ***** -->
            <?php
            $paystack_live_fields = "hidden_section";
            if (isset($gateway_options['paystack_payment_mode']) && $gateway_options['paystack_payment_mode'] == "production") {
                $paystack_live_fields = "";
            }
            ?>
            <tr class="form-field arm_paystack_fields <?php echo $paystack_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live Secret Key', ARM_PAYSTACK_TEXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_paystack_live_secret_key" name="payment_gateway_settings[paystack][paystack_live_secret_key]" value="<?php echo (!empty($gateway_options['paystack_live_secret_key'])) ? $gateway_options['paystack_live_secret_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_paystack_fields <?php echo $paystack_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live Public Key', ARM_PAYSTACK_TEXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_paystack_live_public_key" name="payment_gateway_settings[paystack][paystack_live_public_key]" value="<?php echo (!empty($gateway_options['paystack_live_public_key'])) ? $gateway_options['paystack_live_public_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            
            <!-- ***** Ending of Live Input for paystack ***** -->

            <?php
        }
    }

    function arm_paystack_config() {
        global $arm_payment_gateways, $arm_global_settings;

        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        if (isset($all_payment_gateways['paystack']) && !empty($all_payment_gateways['paystack'])) {
            $payment_gateway_options = $all_payment_gateways['paystack'];
            $ARM_Paystack_payment_mode = $payment_gateway_options['paystack_payment_mode'];
            $is_sandbox_mode = $ARM_Paystack_payment_mode == "sandbox" ? true : false;

            $PaystackConfig = array();

            $PaystackConfig['environment'] = ( $is_sandbox_mode ) ? "sandbox" : "production"; // production, sandbox

            $PaystackConfig['credentials'] = array();
            $PaystackConfig['credentials']['secret_key'] = ( $is_sandbox_mode ) ? $payment_gateway_options['paystack_test_secret_key'] : $payment_gateway_options['paystack_live_secret_key'];
            $PaystackConfig['credentials']['public_key']['sandbox'] = $payment_gateway_options['paystack_test_public_key'];
            $PaystackConfig['credentials']['public_key']['production'] = $payment_gateway_options['paystack_live_public_key'];
            

            $PaystackConfig['application'] = array();
            $PaystackConfig['application']['charset'] = "UTF-8"; // UTF-8, ISO-8859-1

            $PaystackConfig['log'] = array();
            $PaystackConfig['log']['active'] = false;
            
            $PaystackConfig['log']['fileLocation'] = "";

            return $PaystackConfig;
        }
    }

    
    function arm2_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {
        
        global $wpdb, $ARMember, $arm_global_settings, $arm_membership_setup, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $paid_trial_stripe_payment_done, $is_free_manual;
        
        $is_free_manual = false;

        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        $currency = $arm_payment_gateways->arm_get_global_currency();
        if ($payment_gateway == 'paystack' && isset($all_payment_gateways['paystack']) && !empty($all_payment_gateways['paystack'])) 
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
                
                $payment_mode_ = !empty($posted_data['arm_payment_mode']['paystack']) ? $posted_data['arm_payment_mode']['paystack'] : 'both';

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
                        if(isset($posted_data['arm_payment_mode']['paystack'])){
                            $payment_mode_ = !empty($posted_data['arm_payment_mode']['paystack']) ? $posted_data['arm_payment_mode']['paystack'] : 'manual_subscription';
                            $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                            
                            if($recurring_payment_mode=='auto_debit_subscription')
                            {
                                //echo 'inside_here paystack_plan_id<br>$plan_id=>'.$plan_id.'cycle_key-->';

                                if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
                                     $payment_cycle_key = $plan->options['payment_cycles'][$payment_cycle]['cycle_key'];
                                     $PaystackPlanID = $setup_data['setup_modules']['modules']['paystack_plans'][$plan_id][$payment_cycle_key];
                                }
                                //exit;
                            }
                        }
                        else{
                            $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                            if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
                                $setup_modules = $setup_data['setup_modules'];
                                $modules = $setup_modules['modules'];
                                $payment_mode_ = $modules['payment_mode']['paystack'];
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
                
                $arm_paystack_plan_amount = str_replace(",", "", $plan->amount);
                if($couponApply["status"] == "success") {
                    $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : '0';
                    if($arm_coupon_on_each_subscriptions=='1')
                    {
                        $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $currency : "%";
                        $coupon_discount_amount = isset($couponApply['discount']) ? $couponApply['discount'] : 0;
                        if($arm_coupon_discount_type=='%')
                        {
                            $coupon_discount_amount = ($arm_paystack_plan_amount * $coupon_discount_amount) /100;
                            $arm_paystack_plan_amount = $arm_paystack_plan_amount - $coupon_discount_amount;
                        }
                        else
                        {
                           $arm_paystack_plan_amount = $arm_paystack_plan_amount - $coupon_discount_amount;
                        }
                    }
                }//paystack tax amount
                if($tax_percentage > 0){
                    $paystack_tax_amount =($arm_paystack_plan_amount*$tax_percentage)/100;
                    $paystack_tax_amount = number_format((float)$paystack_tax_amount, 2, '.','');
                    $arm_paystack_plan_amount = $arm_paystack_plan_amount+$paystack_tax_amount;
                  
                }
                //exit;

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


                
                $arm_paystack_redirecturl = '';
                $arm_paystack_redirecturl = $arm_global_settings->add_query_arg("arm-listener", "arm_paystack_api", get_home_url() . "/");



                if ((($discount_amt <= 0 || $discount_amt == '0.00') && $recurring_payment_mode == 'manual_subscription' && $plan->is_recurring()) || (!$plan->is_recurring() && ($discount_amt <= 0 || $discount_amt == '0.00'))) 
                {
                    
                    global $payment_done;
                    $paystack_response = array();
                    $current_user_id = 0;
                    if (is_user_logged_in()) {
                        $current_user_id = get_current_user_id();
                        $paystack_response['arm_user_id'] = $current_user_id;
                    }
                    $arm_first_name=(isset($posted_data['first_name']))?$posted_data['first_name']:'';
                    $arm_last_name=(isset($posted_data['last_name']))?$posted_data['last_name']:'';
                    if($user_id){
                        if(empty($arm_first_name)){
                            $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                            $arm_first_name=$user_detail_first_name;
                        }
                        if(empty($arm_last_name)){
                            $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                            $arm_last_name=$user_detail_last_name;
                        }    
                    }
                    $paystack_response['arm_plan_id'] = $plan->ID;
                    $paystack_response['arm_first_name']=$arm_first_name;
                    $paystack_response['arm_last_name']=$arm_last_name;
                    $paystack_response['arm_payment_gateway'] = 'paystack';
                    $paystack_response['arm_payment_type'] = $plan->payment_type;
                    $paystack_response['arm_token'] = '-';
                    $paystack_response['arm_payer_email'] = $user_email_add;
                    $paystack_response['arm_receiver_email'] = '';
                    $paystack_response['arm_transaction_id'] = '-';
                    $paystack_response['arm_transaction_payment_type'] = $plan->payment_type;
                    $paystack_response['arm_transaction_status'] = 'completed';
                    $paystack_response['arm_payment_mode'] = 'manual_subscription';
                    $paystack_response['arm_payment_date'] = date('Y-m-d H:i:s');
                    $paystack_response['arm_amount'] = $amount;
                    $paystack_response['arm_currency'] = $currency;
                    $paystack_response['arm_coupon_code'] = $posted_data['arm_coupon_code'];
                    $paystack_response['arm_response_text'] = '';
                    $paystack_response['arm_extra_vars'] = '';
                    $paystack_response['arm_is_trial'] = $arm_is_trial;
                    $paystack_response['arm_created_date'] = current_time('mysql');
                    $paystack_response['arm_coupon_discount'] = $arm_coupon_discount;
                    $paystack_response['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                    $paystack_response['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;

                    $payment_log_id = $arm_payment_gateways->arm_save_payment_log($paystack_response);
                    $return = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                    $payment_done = $return;
                    $is_free_manual = true;

                    if($arm_manage_coupons->isCouponFeature && !empty($posted_data['arm_coupon_code']) && !empty($arm_coupon_on_each_subscriptions)) {
                            $payment_done["coupon_on_each"] = TRUE;
                            $payment_done["trans_log_id"] = $payment_log_id;
                    }

                    do_action('arm_after_paystack_free_payment',$plan,$payment_log_id,$arm_is_trial,$posted_data['arm_coupon_code'],$extraParam);

                    return $return;
                }
                else
                {
                    $extraVars['paid_amount'] = $amount;
                    $data_array['arm_paystack_entry_id'] = $entry_id;
                    $data_array['currency'] = $currency;
                    $data_array['arm_plan_id'] = $plan_id;
                    $data_array['arm_plan_name'] = $plan_name;
                    $data_array['arm_plan_amount'] = $discount_amt;
                    $data_array['reference'] = 'ref-' . $entry_id.'-'.time();
                    $data_array['redirect_url'] = $arm_paystack_redirecturl;
                    $data_array['arm_coupon_code'] = $posted_data['arm_coupon_code'];

                    if($pgoptions['paystack']['paystack_payment_mode'] == 'sandbox')
                    {
                        $data_array['arm_paystack_secret_key'] = $pgoptions['paystack']['paystack_test_secret_key'];
                        $data_array['arm_paystack_public_key'] = $pgoptions['paystack']['paystack_test_public_key'];
                    }
                    else
                    {
                        $data_array['arm_paystack_secret_key'] = $pgoptions['paystack']['paystack_live_secret_key'];
                        $data_array['arm_paystack_public_key'] = $pgoptions['paystack']['paystack_live_public_key'];
                    }
                    //$ARMember->arm_write_response("reputelog paystack 1 arm_paystack_secret_key =>".$data_array['arm_paystack_secret_key']);
                    //$ARMember->arm_write_response("reputelog paystack 1 arm_paystack_public_key =>".$data_array['arm_paystack_public_key']);
                    $data_array['first_name'] = $entry_data['arm_entry_value']['first_name'];
                    $data_array['last_name'] = $entry_data['arm_entry_value']['last_name'];
                    $data_array['user_email'] = $user_email_add;

                    if($recurring_payment_mode == 'auto_debit_subscription' )
                    {
                        $arm_paystack_plan_interval = "";
                        $pg_error_flag = "";
                        if($recurring_data['period']=='D' && $recurring_data['interval']=='1')
                        {
                            $arm_paystack_plan_interval = 'daily';
                            $pg_error_flag = "1";
                        }
                        else if($recurring_data['period']=='D' && $recurring_data['interval']=='7')
                        {
                            $arm_paystack_plan_interval = 'weekly';
                            $pg_error_flag = "1";
                        }
                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='1')
                        {
                            $arm_paystack_plan_interval = 'monthly';
                            $pg_error_flag = "1";
                        }
                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='3')
                        {
                            $arm_paystack_plan_interval = 'quarterly';
                            $pg_error_flag = "1";
                        }
                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='6')
                        {
                            $arm_paystack_plan_interval = 'biannually';
                            $pg_error_flag = "1";
                        }
                        else if($recurring_data['period']=='Y' && $recurring_data['interval']=='1')
                        {
                            $arm_paystack_plan_interval = 'annually';
                            $pg_error_flag = "1";
                        }

                        //recurring time
                        $arm_paystack_recurring_time = $recurring_data['rec_time'];
                        if($plan->has_trial_period() && $allow_trial)
                        {
                            if($arm_paystack_recurring_time=='infinite')
                            {
                                $arm_paystack_recurring_time = 0;
                            }
                            else
                            {
                                $arm_paystack_recurring_time = $arm_paystack_recurring_time;
                            }

                        }
                        else
                        {
                            if($arm_paystack_recurring_time=='infinite')
                            {
                                $arm_paystack_recurring_time = 0;
                            }
                            else
                            {
                                $arm_paystack_recurring_time = $arm_paystack_recurring_time - 1;
                            }
                        }
                        if(empty($pg_error_flag))
                        {
                            $paystack_err_msg = '<div class="arm_error_msg"><ul><li>' . __('Payment through Paystack is not supported for selected plan.', ARM_PAYSTACK_TEXTDOMAIN) . '</li></ul></div>';
                            $return = array('status' => 'error', 'type' => 'message', 'message' => $paystack_err_msg);
                            echo json_encode($return);
                            exit;
                        }
                        
                        $arm_paystack_subscription_amount = $arm_paystack_plan_amount * 100;
                        //$ARMember->arm_write_response("reputelog paystack 1 arm_paystack_subscription_amount =>".$arm_paystack_subscription_amount);
                        //print_r($plan);
                            $arm_paystack_plan_check_url = 'https://api.paystack.co/plan/'.$PaystackPlanID;
                            
                            $arm_paystack_plan_headers = array(
                                'Content-Type'  => 'application/json',
                                'Authorization' => 'Bearer ' . $data_array['arm_paystack_secret_key']
                            );

                            $arm_paystack_plan_args = array(
                                'headers' => $arm_paystack_plan_headers,
                                'timeout' => 60
                            );

                            $arm_paystack_plancheck_request = wp_remote_get($arm_paystack_plan_check_url, $arm_paystack_plan_args);
                            if (!is_wp_error($arm_paystack_plancheck_request)) 
                            {
                                $arm_paystack_plan_response = json_decode(wp_remote_retrieve_body($arm_paystack_plancheck_request));
                                //print_r($arm_paystack_plan_response);
                                //exit;
                                $arm_paystack_plan_response_error = $arm_paystack_plan_response->message;

                                if($arm_paystack_plan_response_error=='Invalid key')
                                {
                                    $paystack_err_msg = '<div class="arm_error_msg"><ul><li>' . __($arm_paystack_plan_response_error, ARM_PAYSTACK_TEXTDOMAIN) . '</li></ul></div>';
                                    $return = array('status' => 'error', 'type' => 'message', 'message' => $paystack_err_msg);
                                    echo json_encode($return);
                                    exit;
                                }
                                else if($arm_paystack_plan_response_error=='Plan ID/code specified is invalid' || ($arm_paystack_subscription_amount!=$arm_paystack_plan_response->data->amount || $arm_paystack_plan_interval!=$arm_paystack_plan_response->data->interval))
                                {
                                    //$ARMember->arm_write_response("reputelog paystack 2 arm_paystack_subscription_amount =>".$arm_paystack_subscription_amount." response amount =>".$arm_paystack_plan_response->data->amount);
                                    $arm_paystack_plan_check_with_amount_url = 'https://api.paystack.co/plan?amount='.$arm_paystack_subscription_amount.'&interval='.$arm_paystack_plan_interval;
                                    $arm_paystack_plan_check_with_amount_request = wp_remote_get($arm_paystack_plan_check_with_amount_url, $arm_paystack_plan_args);
                                    
                                    if (!is_wp_error($arm_paystack_plan_check_with_amount_request)) 
                                    {
                                        $arm_paystack_plan_check_with_amount_response = json_decode(wp_remote_retrieve_body($arm_paystack_plan_check_with_amount_request));
                                        //echo "arm_paystack_plan_check_with_amount_response=><br>";
                                        //print_r($arm_paystack_plan_check_with_amount_response);
                                        //$ARMember->arm_write_response("reputelog paystack 4 arm_paystack_plan_check_with_amount_response =>".maybe_serialize($arm_paystack_plan_check_with_amount_response));
                                        if ($arm_paystack_plan_check_with_amount_response->meta->total >= 1) {
                                            $arm_paystack_subscription_plan = $arm_paystack_plan_check_with_amount_response->data[0];
                                            $arm_paystack_subscription_details = $arm_paystack_subscription_plan->subscriptions[0];

                                            //$ARMember->arm_write_response("reputelog paystack -4- arm_paystack_subscription_plan =>".maybe_serialize($arm_paystack_subscription_plan));
                                            //$ARMember->arm_write_response("reputelog paystack -4.1- arm_paystack_subscription_details =>".maybe_serialize($arm_paystack_subscription_details));
                                            $arm_paystack_plancode = $arm_paystack_subscription_plan->plan_code;
                                            $arm_paystack_subscription_code = $arm_paystack_subscription_details->subscription_code;
                                        } 
                                        else 
                                        {
                                            $arm_paystack_plan_url = 'https://api.paystack.co/plan';
                                            if($arm_paystack_recurring_time==0)
                                            {
                                                $arm_paystack_subscription_plan_body = array(
                                                    'name'      => $plan->name,
                                                    'amount'    => $arm_paystack_subscription_amount,
                                                    'interval'  => $arm_paystack_plan_interval,
                                                );
                                            }
                                            else
                                            {
                                                $arm_paystack_subscription_plan_body = array(
                                                    'name'      => $plan->name,
                                                    'amount'    => $arm_paystack_subscription_amount,
                                                    'interval'  => $arm_paystack_plan_interval,
                                                    'invoice_limit' => $arm_paystack_recurring_time
                                                );
                                            }
                                            //$ARMember->arm_write_response("reputelog paystack 5 arm_paystack_subscription_plan_body =>".maybe_serialize($arm_paystack_subscription_plan_body));
                                            //print_r($arm_paystack_subscription_plan_body);
                                            $arm_paystack_subscription_plan_args = array(
                                                'body'      => json_encode($arm_paystack_subscription_plan_body),
                                                'headers'   => $arm_paystack_plan_headers,
                                                'timeout'   => 60
                                            );

                                            $arm_paystack_subscription_plan_create_request = wp_remote_post($arm_paystack_plan_url, $arm_paystack_subscription_plan_args);

                                            
                                            if (!is_wp_error($arm_paystack_subscription_plan_create_request)) {

                                                $arm_paystack_subscription_plan_response = json_decode(wp_remote_retrieve_body($arm_paystack_subscription_plan_create_request));
                                                $arm_paystack_plan_response_status_error = $arm_paystack_subscription_plan_response->status;
                                                $arm_paystack_plan_response_error = $arm_paystack_subscription_plan_response->message;
                                                if($arm_paystack_plan_response_status_error==false)
                                                {

                                                    $paystack_err_msg = '<div class="arm_error_msg"><ul><li>' . __($arm_paystack_plan_response_error, ARM_PAYSTACK_TEXTDOMAIN) . '</li></ul></div>';
                                                    $return = array('status' => 'error', 'type' => 'message', 'message' => $paystack_err_msg);
                                                    echo json_encode($return);
                                                    exit;
                                                }
                                                else
                                                {
                                                    //echo "arm_paystack_subscription_coupon_plan_response=><br>";
                                                    //print_r($arm_paystack_subscription_plan_response);
                                                    //$ARMember->arm_write_response("reputelog paystack 6 arm_paystack_subscription_plan_response =>".maybe_serialize($arm_paystack_subscription_plan_response));
                                                    $arm_paystack_plancode = $arm_paystack_subscription_plan_response->data->plan_code;
                                                    $arm_paystack_subscription_code = $arm_paystack_subscription_plan_response->subscriptions->subscription_code;
                                                    //$ARMember->arm_write_response("reputelog paystack -6- arm_paystack_subscription_code =>".maybe_serialize($arm_paystack_subscription_code));
                                                }
                                                
                                            }
                                        }
                                    }
                                }
                                else
                                {
                                    //echo 'already create plan';
                                    $arm_paystack_plancode = $arm_paystack_plan_response->data->plan_code;
                                    //$ARMember->arm_write_response("reputelog paystack 7 arm_paystack_plancode =>".$arm_paystack_plancode);
                                    $arm_paystack_subscription_code = $arm_paystack_plan_response->subscriptions->subscription_code;
                                }
                                /*echo '<pre>';
                                print_r($arm_paystack_plan_response);
                                echo '<br>Plan';
                                print_r($plan->name);*/
                            }
                            //echo $arm_paystack_plancode;
                        //exit;
                            //$ARMember->arm_write_response("reputelog paystack -7- arm_paystack_plancode =>".$arm_paystack_plancode);
                        $data_array['arm_paystack_plancode'] = !empty($arm_paystack_plancode) ? $arm_paystack_plancode : $PaystackPlanID;
                        $data_array['arm_paystack_subscription_code'] = !empty($arm_paystack_subscription_code) ? $arm_paystack_subscription_code : '';

                        $data_array['recurring_payment_mode'] = $recurring_payment_mode;
                        $data_array['arm_paystack_invoice_limit'] = $arm_paystack_recurring_time;
                        //print_r($plan);
                        $data_array['arm_has_trial_period'] = $plan->has_trial_period();
                        $data_array['arm_paystack_trail_invoice_limit'] = $recurring_data['trial']['rec_time'];
                        //exit;
                         //$ARMember->arm_write_response("reputelog paystack -7.1- data_array =>".maybe_serialize($data_array));
                        $createpaymentrequest = new CreatePaystackPaymentRequest();
                        $arm_paystack_response = $createpaymentrequest->main($data_array);
                        
                        //$ARMember->arm_write_response("reputelog paystack -7.1.2.3- arm_paystack_response =>".maybe_serialize($arm_paystack_response));
                        //print_r($arm_paystack_response);
                        //$ARMember->arm_write_response("reputelog paystack -7.1.2.3.1- arm_paystack_response =>".$arm_paystack_response->data->message);
                        if (isset($posted_data['action']) && in_array($posted_data['action'], array('arm_shortcode_form_ajax_action', 'arm_membership_setup_form_ajax_action'))) {
                                
                                if(!empty($arm_paystack_response->status))
                                {
                                    $arm_paystack_autho_url = isset($arm_paystack_response->data->authorization_url) ? $arm_paystack_response->data->authorization_url : '' ;

                                    $redirect = '<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="' . $arm_paystack_autho_url . '";</script>';
                                    $return = array('status' => 'success', 'type' => 'redirect', 'message' => $redirect);
                                    echo json_encode($return);
                                    exit;
                                }
                                else
                                {
                                    $paystack_err_msg = !empty($arm_paystack_response->message) ? '<div class="arm_error_msg"><ul><li>'.$arm_paystack_response->message.'</li></ul></div>' : '';
                                    $return = array('status' => 'error', 'type' => 'message', 'message' => $paystack_err_msg);
                                    echo json_encode($return);
                                    exit;
                                }
                        }

                        
                    }
                    else
                    {
                        $createpaymentrequest = new CreatePaystackPaymentRequest();
                        $arm_paystack_response = $createpaymentrequest->main($data_array);
                        //$ARMember->arm_write_response("reputelog paystack -7.1.2.4- arm_paystack_response =>".maybe_serialize($arm_paystack_response));
                        
                        if (isset($posted_data['action']) && in_array($posted_data['action'], array('arm_shortcode_form_ajax_action', 'arm_membership_setup_form_ajax_action'))) {
                                
                                if(!empty($arm_paystack_response->status))
                                {
                                    $arm_paystack_autho_url = isset($arm_paystack_response->data->authorization_url) ? $arm_paystack_response->data->authorization_url : '' ;
                                    //echo 'inside here manual 1';
                                    $redirect = '<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="' . $arm_paystack_autho_url . '";</script>';
                                    $return = array('status' => 'success', 'type' => 'redirect', 'message' => $redirect);
                                    echo json_encode($return);
                                    exit;
                                }
                                else
                                {
                                    $paystack_err_msg = !empty($arm_paystack_response->message) ? '<div class="arm_error_msg"><ul><li>'.$arm_paystack_response->message.'</li></ul></div>' : '';
                                    $return = array('status' => 'error', 'type' => 'message', 'message' => $paystack_err_msg);
                                    echo json_encode($return);
                                    exit;
                                }
                        }
                    }
                }

                    $extraVars['paid_amount'] = $discount_amt;
                    $data_array['currency'] = $currency;
                    $data_array['arm_plan_id'] = $plan_id;
                    $data_array['arm_plan_name'] = $plan_name;
                    $data_array['arm_plan_amount'] = $discount_amt;
                    $data_array['reference'] = 'ref-' . $entry_id;
                    $data_array['redirect_url'] = $arm_paystack_redirecturl;
                    
                    
                    if($pgoptions['paystack']['paystack_payment_mode']=='sandbox')
                    {
                        $data_array['arm_paystack_secret_key'] = $pgoptions['paystack']['paystack_test_secret_key'];
                        $data_array['arm_paystack_public_key'] = $pgoptions['paystack']['paystack_test_public_key'];
                    }
                    else
                    {
                        $data_array['arm_paystack_secret_key'] = $pgoptions['paystack']['paystack_live_secret_key'];
                        $data_array['arm_paystack_public_key'] = $pgoptions['paystack']['paystack_live_public_key'];
                    }
                    //$ARMember->arm_write_response("reputelog paystack 2 arm_paystack_secret_key =>".$data_array['arm_paystack_secret_key']);
                    //$ARMember->arm_write_response("reputelog paystack 2 arm_paystack_public_key =>".$data_array['arm_paystack_public_key']);

                    $data_array['first_name'] = $entry_data['arm_entry_value']['first_name'];
                    $data_array['last_name'] = $entry_data['arm_entry_value']['last_name'];
                    $data_array['user_email'] = $user_email_add;
                    $data_array['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                    $data_array['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                    $paystack_response['arm_coupon_discount'] = $arm_coupon_discount;
                    

                    $createpaymentrequest = new CreatePaystackPaymentRequest();
                    $arm_paystack_response = $createpaymentrequest->main($data_array);
                    //$ARMember->arm_write_response("reputelog paystack -7.1.2- arm_paystack_response =>".maybe_serialize($arm_paystack_response));
                    if (isset($posted_data['action']) && in_array($posted_data['action'], array('arm_shortcode_form_ajax_action', 'arm_membership_setup_form_ajax_action'))) {
                        
                        if(!empty($arm_paystack_response->status))
                        {
                            $arm_paystack_autho_url = isset($arm_paystack_response->data->authorization_url) ? $arm_paystack_response->data->authorization_url : '' ;
                                    //echo 'inside here manual 1';
                            $redirect = '<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="' . $arm_paystack_autho_url . '";</script>';
                            $return = array('status' => 'success', 'type' => 'redirect', 'message' => $redirect);
                            echo json_encode($return);
                            exit;
                        }
                        else
                        {
                            $paystack_err_msg = !empty($arm_paystack_response->message) ? '<div class="arm_error_msg"><ul><li>'.$arm_paystack_response->message.'</li></ul></div>' : '';
                            $return = array('status' => 'error', 'type' => 'message', 'message' => $paystack_err_msg);
                            echo json_encode($return);
                            exit;
                        }
                    }
                /*}*/
            } else {
                
            }
        } else {
            
        }
    }

    function arm2_paystack_webhook($transaction_id = 0, $arm_listener = '', $tran_id = '') {
        global $wpdb, $ARMember, $arm_payment_gateways;
        //print_r($_REQUEST);
        //$ARMember->arm_write_response("reputelog paystack webhook 1 REQUEST=>".maybe_serialize($_REQUEST));
        if (isset($_REQUEST['arm-listener']) && in_array($_REQUEST['arm-listener'], array('arm_paystack_api'))) 
        {   
            //$ARMember->arm_write_response("reputelog paystack webhook 2 REQUEST=>".maybe_serialize($_REQUEST));
            if(!empty($_REQUEST['trxref']) || !empty($_REQUEST['reference']))
            {
                //print_r($_REQUEST['arm-listener']);
                
                $gateway_options   = get_option('arm_payment_gateway_settings');
                $pgoptions         = maybe_unserialize($gateway_options);
                if($pgoptions['paystack']['paystack_payment_mode']=='sandbox')
                {
                    $paystack_secret_key  = $pgoptions['paystack']['paystack_test_secret_key'];
                }
                else
                {
                    $paystack_secret_key  = $pgoptions['paystack']['paystack_live_secret_key'];
                }
                //$ARMember->arm_write_response("reputelog paystack webhook 2 paystack_secret_key=>".maybe_serialize($paystack_secret_key));
                $reference_txn_id = $_REQUEST['trxref'];
                $arm_paystack_url = 'https://api.paystack.co/transaction/verify/' . $_REQUEST['trxref'];
                $arm_paystack_headers = array(
                    'Authorization' => 'Bearer ' . $paystack_secret_key
                );
                $arm_paystack_args = array(
                    'headers'   => $arm_paystack_headers,
                    'timeout'   => 60
                );
                $arm_paystack_request = wp_remote_get($arm_paystack_url, $arm_paystack_args);
                if(!is_wp_error($arm_paystack_request))
                {
                    $arm_paystack_response = json_decode(wp_remote_retrieve_body($arm_paystack_request));
                    $arm_paystack_response->paystack_secret_key = $paystack_secret_key;
                    $redirect_url = $arm_paystack_response->metadata->referrer;
                    if('success' == $arm_paystack_response->data->status)
                    {
                        $reference_code = explode('-', $reference_txn_id);
                        $entry_id = $reference_code[1];
                        $arm_get_payment_log = $wpdb->get_row( $wpdb->prepare( "SELECT arm_log_id,arm_user_id FROM `$ARMember->tbl_arm_payment_log` WHERE arm_transaction_id = %s", $reference_txn_id), ARRAY_A );
                        $arm_log_id = (!empty($arm_get_payment_log['arm_log_id'])) ? $arm_get_payment_log['arm_log_id'] : '';
                        $user_id = (!empty($arm_get_payment_log['arm_user_id'])) ? $arm_get_payment_log['arm_user_id'] : '';
                        if($arm_log_id == '') {
                            $user_id = $this->arm2_add_user_and_transaction($entry_id, $arm_paystack_response);
                        }
                        if(empty($redirect_url))
                        {
                            $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
                            if (!empty($entry_data))  {
                                $entry_values = $entry_data['arm_entry_value'];
                                $arm_redirecturl = $entry_values['setup_redirect'];
                                
                                $user_info = get_userdata($user_id);
                                $arm_get_username = $user_info->user_login;

                                $arm_redirecturl = str_replace('{ARMCURRENTUSERNAME}', $arm_get_username, $arm_redirecturl);
                                $arm_redirecturl = str_replace('{ARMCURRENTUSERID}', $user_id, $arm_redirecturl);

                                if (empty($arm_redirecturl)) {
                                    $arm_redirecturl = ARM_HOME_URL;
                                }
                            } else {
                                $arm_redirecturl = ARM_HOME_URL;
                            }
                            header('location: '.$arm_redirecturl); 
                        }
                        else{
                            header('location: '.$redirect_url); 
                        }
                    }
                    
                }
            }
            else
            {
                header('location: '.ARM_HOME_URL); 
            }
            
        }
    }
    
    

    function arm2_add_user_and_transaction($entry_id = 0, $paystack_response, $arm_display_log = 1) {
        global $wpdb, $paystack, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication, $arm_manage_coupons, $payment_done, $paid_trial_stripe_payment_done, $arm_members_class,$arm_transaction,$arm_membership_setup;
        if (isset($entry_id) && $entry_id != '') {
            $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
            if (isset($all_payment_gateways['paystack']) && !empty($all_payment_gateways['paystack'])) {
                $options = $all_payment_gateways['paystack'];
                $paystack_payment_mode = $options['paystack_payment_mode'];
                $is_sandbox_mode = $paystack_payment_mode == "sandbox" ? true : false;
                $currency = $arm_payment_gateways->arm_get_global_currency();

                $entry_data = $wpdb->get_row("SELECT * FROM `" . $ARMember->tbl_arm_entries . "` WHERE `arm_entry_id`='" . $entry_id . "' ", ARRAY_A);
                $entry_values = maybe_unserialize($entry_data['arm_entry_value']);
                $entry_email = isset($entry_data['arm_entry_email']) ? $entry_data['arm_entry_email'] : '';
                $arm_log_plan_id = $entry_data['arm_plan_id'];
                $arm_log_amount = isset($entry_values['arm_total_payable_amount']) ? $entry_values['arm_total_payable_amount'] : '';
                
                $paystack_secret_key = $paystack_response->paystack_secret_key;
                //$ARMember->arm_write_response("reputelog paystack 1 paystack_response =>".maybe_serialize($paystack_response));
                $arm_plan_paystack_code = !empty($paystack_response->data->metadata->arm_plan_paystack_code) ? $paystack_response->data->metadata->arm_plan_paystack_code : '';
                $arm_paystack_customer_code = !empty($paystack_response->data->customer->customer_code) ? $paystack_response->data->customer->customer_code : '';
                $arm_token = !empty($paystack_response->data->metadata->arm_paystack_subscription_code) ? $paystack_response->data->metadata->arm_paystack_subscription_code : '';
                $arm_paystack_transaction_id = !empty($paystack_response->data->reference) ? $paystack_response->data->reference : '';
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
                
                
                $paystackLog['arm_coupon_code'] = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                $paystackLog['arm_payment_type'] = $arm_payment_type;
                $paystackLog['payment_type'] = $arm_payment_type;
                $paystackLog['payment_status'] = $payment_status;
                $paystackLog['payer_email'] = $entry_email;
                $paystackLog['arm_first_name']	=	$user_detail_first_name;
                $paystackLog['arm_last_name']	=	$user_detail_last_name;
                $extraParam['payment_type'] = 'paystack';
                $extraParam['payment_mode'] = $paystack_payment_mode;
                $extraParam['arm_is_trial'] = '0';
                $extraParam['subs_id'] = $paystack_response->data->customer->customer_code;
                $extraParam['trans_id'] = $arm_paystack_transaction_id;
                $cardnumber = $paystack_response->data->authorization->last4;
                $extraParam['card_number'] = $arm_transaction->arm_mask_credit_card_number($cardnumber);
                $extraParam['error'] = '';
                $extraParam['date'] = current_time('mysql');
                $extraParam['message_type'] = '';

                $amount = '';
                $new_plan = new ARM_Plan($entry_plan);
                
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
                        $paystackLog['coupon_code'] = $couponCode;
                        $paystackLog['arm_coupon_discount'] = $arm_coupon_discount;
                        $paystackLog['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                        $paystackLog['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                    }
                } 

                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $paystackLog['currency'] = $currency;
                $paystackLog['payment_amount'] = $discount_amt;
                //$ARMember->arm_write_response("reputelog paystack form_type =>".$armform->type);
                if(!$user_info && in_array($armform->type, array('registration'))) {

                    /* Coupon Details */
                    $recurring_data = $new_plan->prepare_recurring_data($payment_cycle);
                    if(!empty($recurring_data['trial'])) {
                        $extraParam['trial'] = array(
                            'amount' => $recurring_data['trial']['amount'],
                            'period' => $recurring_data['trial']['period'],
                            'interval' => $recurring_data['trial']['interval'],
                        );
                        $extraParam['arm_is_trial'] = '1';
                        $paystackLog['arm_is_trial'] = '1';
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
                    $paystackLog['payment_amount'] = $amount_for_tax;

                    $paystackLog['arm_paystack_response'] = maybe_serialize($paystack_response);
                    $paystackLog['arm_payment_mode'] = $payment_mode;
                    //$ARMember->arm_write_response("reputelog paystack 1 payment_mode =>".$payment_mode);
                    if($payment_mode=='auto_debit_subscription')
                    {
                        $paystackLog['arm_payment_mode'] = $payment_mode;
                        //$ARMember->arm_write_response("reputelog paystack 2 payment_mode =>".$payment_mode);
                        $arm_paystack_plan_interval = "";
                        $arm_paystack_plan_date_added_days = '';
                        $pg_error_flag = "";
                        if($recurring_data['period']=='D' && $recurring_data['interval']=='1')
                        {
                            $arm_paystack_plan_interval = 'daily';
                            $pg_error_flag = "1";
                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' days';
                        }
                        else if($recurring_data['period']=='D' && $recurring_data['interval']=='7')
                        {
                            $arm_paystack_plan_interval = 'weekly';
                            $pg_error_flag = "1";
                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' days';
                        }
                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='1')
                        {
                            $arm_paystack_plan_interval = 'monthly';
                            $pg_error_flag = "1";
                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' months';
                        }
                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='3')
                        {
                            $arm_paystack_plan_interval = 'quarterly';
                            $pg_error_flag = "1";
                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' months';
                        }
                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='6')
                        {
                            $arm_paystack_plan_interval = 'biannually';
                            $pg_error_flag = "1";
                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' months';
                        }
                        else if($recurring_data['period']=='Y' && $recurring_data['interval']=='1')
                        {
                            $arm_paystack_plan_interval = 'annually';
                            $pg_error_flag = "1";
                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' year';
                        }


                        $arm_paystack_subscription_amount = $amount_for_tax * 100;
                        
                        
                        $arm_paystack_subscription_url = 'https://api.paystack.co/subscription';
                        //$ARMember->arm_write_response("reputelog paystack 8 $arm_plan_paystack_code =>".$arm_plan_paystack_code);
                        $arm_paystack_plan_headers = array(
                            'Content-Type'  => 'application/json',
                            'Authorization' => 'Bearer ' . $paystack_secret_key
                        );

                        
                        $arm_paystack_plancode = !empty($arm_plan_paystack_code) ? $arm_plan_paystack_code : '';
                        if ($plan->has_trial_period()) 
                        {
                            
                            $arm_trial_date_added_days = '';
                            if($recurring_data['trial']['period']=='D' )
                            {
                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' days';
                            }
                            else if($recurring_data['trial']['period']=='D' && $recurring_data['trial']['interval']=='7')
                            {
                                $arm_trial_date_added_days = $recurring_data['interval'].' days';
                            }
                            else if($recurring_data['trial']['period']=='M')
                            {
                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' month';
                            }
                            else if($recurring_data['trial']['period']=='M' && $recurring_data['trial']['interval']=='3')
                            {
                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' month';
                            }
                            else if($recurring_data['trial']['period']=='Y')
                            {
                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' year';
                            }
                            
                            
                            $arm_trial_start_date = date('Y-m-d H:i:s', strtotime('+'.$arm_trial_date_added_days));
                            //echo '$arm_paystack_plan_start_date->'.$arm_paystack_plan_start_date;
                            $arm_paystack_subscription_body = array(
                                'customer'  => $arm_paystack_customer_code,
                                'plan'      => $arm_paystack_plancode,
                                'start_date' => $arm_trial_start_date
                            );
                            //print_r($arm_paystack_subscription_body);
                        }
                        else
                        {
                            $arm_paystack_subscription_body = array(
                                'customer'  => $arm_paystack_customer_code,
                                'plan'      => $arm_paystack_plancode,
                            );

                        }
                        $arm_paystack_subscription_args = array(
                            'body'      => json_encode($arm_paystack_subscription_body),
                            'headers'   => $arm_paystack_plan_headers,
                            'timeout'   => 60
                        );
                        
                        $arm_paystack_subscription_request = wp_remote_post($arm_paystack_subscription_url, $arm_paystack_subscription_args);
                        
                        //$ARMember->arm_write_response("reputelog paystack 8 arm_paystack_subscription_request =>".maybe_serialize($arm_paystack_subscription_request));

                        if (!is_wp_error($arm_paystack_subscription_request)) {
                            $arm_paystack_subscription_response = json_decode(wp_remote_retrieve_body($arm_paystack_subscription_request));
                            //$ARMember->arm_write_response("reputelog paystack 9 arm_paystack_subscription_response =>".maybe_serialize($arm_paystack_subscription_response));
                            //echo "arm_paystack_subscription_response=><br>";
                                //    print_r($arm_paystack_subscription_response);
                            $arm_paystack_subscription_transaction_id = $arm_paystack_subscription_response->data->subscription_code;
                            $arm_paystack_subscription_token = $arm_paystack_subscription_response->data->email_token;
                            //$paystackLog['arm_paystack_subscription_response'] = maybe_serialize($arm_paystack_subscription_response);
                        }
                    }
                    $paystackLog['arm_paystack_subscription_code'] = !empty($arm_paystack_subscription_transaction_id) ? $arm_paystack_subscription_transaction_id : '';
                    //exit;
                    $payment_log_id = self::arm_store_paystack_log($paystackLog, 0, $entry_plan, $extraParam, $arm_display_log);
                    $payment_done = array();
                    if ($payment_log_id) {
                        $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                    }
                    $entry_values['payment_done'] = '1';
                    $entry_values['arm_entry_id'] = $entry_id;
                    $entry_values['arm_update_user_from_profile'] = 0;
                    
                    $user_id = $arm_member_forms->arm_register_new_member($entry_values, $armform);

                    if (is_numeric($user_id) && !is_array($user_id)) {
                        $arm_paystack_transaction_meta_detail = array();
                        if ($arm_payment_type == 'subscription') {
                            //$ARMember->arm_write_response("reputelog paystack add user arm_paystack_transaction_id =>".maybe_serialize($arm_paystack_transaction_id));
                            //$ARMember->arm_write_response("reputelog paystack add user arm_paystack_customer_code =>".maybe_serialize($arm_paystack_customer_code));
                            //$ARMember->arm_write_response("reputelog paystack add user arm_paystack_subscription_transaction_id =>".maybe_serialize($arm_paystack_subscription_transaction_id));

                            $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                            $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                            $userPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);

                            /*$arm_paystack_transaction_meta_detail = array(
                                                                    'transaction_id' => $arm_paystack_transaction_id, 
                                                                    'arm_paystack_customer_code' => $arm_paystack_customer_code, 
                                                                    'arm_paystack_subscription_code' => $arm_paystack_subscription_transaction_id, 
                                                                    'arm_paystack_subscription_token' => $arm_paystack_subscription_token
                                                                    );
                            $userPlanData['arm_paystack'] = $arm_paystack_transaction_meta_detail;*/
                            $userPlanData['arm_paystack']['transaction_id'] = $arm_paystack_transaction_id;
                            $userPlanData['arm_paystack']['arm_paystack_customer_code'] = $arm_paystack_customer_code;
                            $userPlanData['arm_paystack']['arm_paystack_subscription_code'] = $arm_paystack_subscription_transaction_id;
                            $userPlanData['arm_paystack']['arm_paystack_subscription_token'] = $arm_paystack_subscription_token;
                            //$ARMember->arm_write_response("reputelog paystack add user transaction 1 =>".maybe_serialize($userPlanData));
                            update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                            $pgateway = 'paystack';
                            $arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $payment_log_id, $pgateway, $userPlanData);
                        }
                        $paystackLog['arm_paystack_subscription_code'] = !empty($arm_paystack_subscription_transaction_id) ? $arm_paystack_subscription_transaction_id : '';
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
                        //$ARMember->arm_write_response("reputelog paystack 8 user_id =>".$user_id);
                        $arm_is_paid_post = false;
                        if( !empty( $entry_values['arm_is_post_entry'] ) && !empty( $entry_values['arm_paid_post_id'] ) ){
                            $arm_is_paid_post = true;
                        }
                        if (!$is_multiple_membership_feature->isMultipleMembershipFeature && !$arm_is_paid_post ){

                        //$ARMember->arm_write_response("reputelog paystack 8 isMultipleMembershipFeature =>");
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
                                $old_subscription_id = $oldPlanData['arm_paystack']['transaction_id'];
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
                                $paystackLog['payment_amount'] = $amount_for_tax;
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
                                /*paystack subscription start*/
                                    $paystackLog['arm_payment_mode'] = $payment_mode;
                                    //$ARMember->arm_write_response("reputelog paystack 3 payment_mode =>".$payment_mode);
                                    if($payment_mode=='auto_debit_subscription')
                                    {
                                        $paystackLog['arm_payment_mode'] = $payment_mode;
                                        //$ARMember->arm_write_response("reputelog paystack 4 payment_mode =>".$payment_mode);
                                        $arm_paystack_plan_interval = "";
                                        $arm_paystack_plan_date_added_days = '';
                                        $pg_error_flag = "";
                                        if($recurring_data['period']=='D' && $recurring_data['interval']=='1')
                                        {
                                            $arm_paystack_plan_interval = 'daily';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' days';
                                        }
                                        else if($recurring_data['period']=='D' && $recurring_data['interval']=='7')
                                        {
                                            $arm_paystack_plan_interval = 'weekly';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' days';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='1')
                                        {
                                            $arm_paystack_plan_interval = 'monthly';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='3')
                                        {
                                            $arm_paystack_plan_interval = 'quarterly';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='6')
                                        {
                                            $arm_paystack_plan_interval = 'biannually';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='Y' && $recurring_data['interval']=='1')
                                        {
                                            $arm_paystack_plan_interval = 'annually';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' year';
                                        }


                                        $arm_paystack_subscription_amount = $amount_for_tax * 100;
                                        $arm_paystack_subscription_url = 'https://api.paystack.co/subscription';
                                        //$ARMember->arm_write_response("reputelog paystack 8 arm_paystack_subscription_url =>".$arm_paystack_subscription_url);
                                        $arm_paystack_plan_headers = array(
                                            'Content-Type'  => 'application/json',
                                            'Authorization' => 'Bearer ' . $paystack_secret_key
                                        );

                                        $arm_paystack_plancode = !empty($arm_plan_paystack_code) ? $arm_plan_paystack_code : '';
                                        if ($plan->has_trial_period()) 
                                        {
                                            
                                            $arm_trial_date_added_days = '';
                                            if($recurring_data['trial']['period']=='D' )
                                            {
                                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' days';
                                            }
                                            else if($recurring_data['trial']['period']=='D' && $recurring_data['trial']['interval']=='7')
                                            {
                                                $arm_trial_date_added_days = $recurring_data['interval'].' days';
                                            }
                                            else if($recurring_data['trial']['period']=='M')
                                            {
                                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' month';
                                            }
                                            else if($recurring_data['trial']['period']=='M' && $recurring_data['trial']['interval']=='3')
                                            {
                                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' month';
                                            }
                                            else if($recurring_data['trial']['period']=='Y')
                                            {
                                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' year';
                                            }
                                            
                                            
                                            $arm_trial_start_date = date('Y-m-d H:i:s', strtotime('+'.$arm_trial_date_added_days));
                                            //echo '$arm_paystack_plan_start_date->'.$arm_paystack_plan_start_date;
                                            $arm_paystack_subscription_body = array(
                                                'customer'  => $arm_paystack_customer_code,
                                                'plan'      => $arm_paystack_plancode,
                                                'start_date' => $arm_trial_start_date
                                            );
                                            //print_r($arm_paystack_subscription_body);
                                        }
                                        else
                                        {
                                            $arm_paystack_subscription_body = array(
                                                'customer'  => $arm_paystack_customer_code,
                                                'plan'      => $arm_paystack_plancode,
                                            );

                                        }
                                        $arm_paystack_subscription_args = array(
                                            'body'      => json_encode($arm_paystack_subscription_body),
                                            'headers'   => $arm_paystack_plan_headers,
                                            'timeout'   => 60
                                        );
                                        
                                        $arm_paystack_subscription_request = wp_remote_post($arm_paystack_subscription_url, $arm_paystack_subscription_args);
                                        
                                        //$ARMember->arm_write_response("reputelog paystack 8 arm_paystack_subscription_request =>".maybe_serialize($arm_paystack_subscription_request));

                                        if (!is_wp_error($arm_paystack_subscription_request)) {
                                            $arm_paystack_subscription_response = json_decode(wp_remote_retrieve_body($arm_paystack_subscription_request));
                                            //$ARMember->arm_write_response("reputelog paystack 9 arm_paystack_subscription_response =>".maybe_serialize($arm_paystack_subscription_response));
                                            //echo "arm_paystack_subscription_response=><br>";
                                                //    print_r($arm_paystack_subscription_response);
                                            $arm_paystack_subscription_transaction_id = $arm_paystack_subscription_response->data->subscription_code;
                                            $arm_paystack_subscription_token = $arm_paystack_subscription_response->data->email_token;
                                            $paystackLog['arm_paystack_subscription_response'] = maybe_serialize($arm_paystack_subscription_response);
                                        }
                                    }
                                    /*paystack subscription end*/
                                update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                $userPlanData['arm_user_gateway'] = 'paystack';

                                if (!empty($arm_paystack_transaction_id)) {
                                    $userPlanData['arm_paystack']['transaction_id'] = $arm_paystack_transaction_id;
                                    $userPlanData['arm_paystack']['arm_paystack_subscription_code'] = $arm_paystack_subscription_transaction_id;
                                    $userPlanData['arm_paystack']['arm_paystack_subscription_token'] = $arm_paystack_subscription_token;
                                }
                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                $paystackLog['arm_paystack_subscription_code'] = !empty($arm_paystack_subscription_transaction_id) ? $arm_paystack_subscription_transaction_id : '';
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
                                $old_subscription_id = $oldPlanData['arm_paystack']['transaction_id'];



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
                                    $paystackLog['payment_amount'] = $amount_for_tax;
                                    $paystackLog['arm_payment_mode'] = $payment_mode;
                                    /*paystack subscription start*/
                                    //$ARMember->arm_write_response("reputelog paystack 5 payment_mode =>".$payment_mode);
                                    if($payment_mode=='auto_debit_subscription')
                                    {
                                        $paystackLog['arm_payment_mode'] = $payment_mode;
                                        //$ARMember->arm_write_response("reputelog paystack 6 payment_mode =>".$payment_mode);
                                        $arm_paystack_plan_interval = "";
                                        $arm_paystack_plan_date_added_days = '';
                                        $pg_error_flag = "";
                                        if($recurring_data['period']=='D' && $recurring_data['interval']=='1')
                                        {
                                            $arm_paystack_plan_interval = 'daily';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' days';
                                        }
                                        else if($recurring_data['period']=='D' && $recurring_data['interval']=='7')
                                        {
                                            $arm_paystack_plan_interval = 'weekly';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' days';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='1')
                                        {
                                            $arm_paystack_plan_interval = 'monthly';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='3')
                                        {
                                            $arm_paystack_plan_interval = 'quarterly';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='6')
                                        {
                                            $arm_paystack_plan_interval = 'biannually';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='Y' && $recurring_data['interval']=='1')
                                        {
                                            $arm_paystack_plan_interval = 'annually';
                                            $pg_error_flag = "1";
                                            $arm_paystack_plan_date_added_days = $recurring_data['interval'].' year';
                                        }


                                        $arm_paystack_subscription_amount = $amount_for_tax * 100;
                                        $arm_paystack_subscription_url = 'https://api.paystack.co/subscription';
                                        //$ARMember->arm_write_response("reputelog paystack 8 arm_paystack_subscription_url =>".$arm_paystack_subscription_url);
                                        $arm_paystack_plan_headers = array(
                                            'Content-Type'  => 'application/json',
                                            'Authorization' => 'Bearer ' . $paystack_secret_key
                                        );

                                        $arm_paystack_plancode = !empty($arm_plan_paystack_code) ? $arm_plan_paystack_code : '';
                                        if ($plan->has_trial_period()) 
                                        {
                                            
                                            $arm_trial_date_added_days = '';
                                            if($recurring_data['trial']['period']=='D' )
                                            {
                                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' days';
                                            }
                                            else if($recurring_data['trial']['period']=='D' && $recurring_data['trial']['interval']=='7')
                                            {
                                                $arm_trial_date_added_days = $recurring_data['interval'].' days';
                                            }
                                            else if($recurring_data['trial']['period']=='M')
                                            {
                                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' month';
                                            }
                                            else if($recurring_data['trial']['period']=='M' && $recurring_data['trial']['interval']=='3')
                                            {
                                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' month';
                                            }
                                            else if($recurring_data['trial']['period']=='Y')
                                            {
                                                $arm_trial_date_added_days = $recurring_data['trial']['interval'].' year';
                                            }
                                            
                                            
                                            $arm_trial_start_date = date('Y-m-d H:i:s', strtotime('+'.$arm_trial_date_added_days));
                                            //echo '$arm_paystack_plan_start_date->'.$arm_paystack_plan_start_date;
                                            $arm_paystack_subscription_body = array(
                                                'customer'  => $arm_paystack_customer_code,
                                                'plan'      => $arm_paystack_plancode,
                                                'start_date' => $arm_trial_start_date
                                            );
                                            //print_r($arm_paystack_subscription_body);
                                        }
                                        else
                                        {
                                            $arm_paystack_subscription_body = array(
                                                'customer'  => $arm_paystack_customer_code,
                                                'plan'      => $arm_paystack_plancode,
                                            );

                                        }
                                        $arm_paystack_subscription_args = array(
                                            'body'      => json_encode($arm_paystack_subscription_body),
                                            'headers'   => $arm_paystack_plan_headers,
                                            'timeout'   => 60
                                        );
                                        
                                        $arm_paystack_subscription_request = wp_remote_post($arm_paystack_subscription_url, $arm_paystack_subscription_args);
                                        
                                        //$ARMember->arm_write_response("reputelog paystack 8 arm_paystack_subscription_request =>".maybe_serialize($arm_paystack_subscription_request));

                                        if (!is_wp_error($arm_paystack_subscription_request)) {
                                            $arm_paystack_subscription_response = json_decode(wp_remote_retrieve_body($arm_paystack_subscription_request));
                                            //$ARMember->arm_write_response("reputelog paystack 9 arm_paystack_subscription_response =>".maybe_serialize($arm_paystack_subscription_response));
                                            //echo "arm_paystack_subscription_response=><br>";
                                                //    print_r($arm_paystack_subscription_response);
                                            $arm_paystack_subscription_transaction_id = $arm_paystack_subscription_response->data->subscription_code;
                                            $arm_paystack_subscription_token = $arm_paystack_subscription_response->data->email_token;
                                            $paystackLog['arm_paystack_subscription_response'] = maybe_serialize($arm_paystack_subscription_response);
                                        }
                                    }
                                    /*paystack subscription end*/

                                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                    $userPlanData['arm_user_gateway'] = 'paystack';

                                    if (!empty($arm_paystack_transaction_id)) {
                                        $userPlanData['arm_paystack']['transaction_id'] = $arm_paystack_transaction_id;
                                        $userPlanData['arm_paystack']['arm_paystack_customer_code'] = $arm_paystack_customer_code;
                                        $userPlanData['arm_paystack']['arm_paystack_subscription_code'] = $arm_paystack_subscription_transaction_id;
                                        $userPlanData['arm_paystack']['arm_paystack_subscription_token'] = $arm_paystack_subscription_token;
                                        $paystackLog['arm_paystack_subscription_code'] = $arm_paystack_subscription_transaction_id;
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
                                    $paystackLog['payment_amount'] = $amount_for_tax;

                                update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                $userPlanData['arm_user_gateway'] = 'paystack';

                                if (!empty($arm_token)) {
                                    $userPlanData['arm_paystack']['transaction_id'] = $arm_token;
                                }
                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                if ($is_update_plan) {
                                    $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan,  '', true, $arm_last_payment_status);
                                } else {
                                   $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'new_subscription');
                                }
                            }
                        }
                        
                        $paystackLog['arm_paystack_response'] = maybe_serialize($paystack_response);
                        
                        $payment_log_id = self::arm_store_paystack_log($paystackLog, $user_id, $entry_plan, $extraParam, $arm_display_log);

                        if ($arm_payment_type == 'subscription') {
                            if($plan_action=='recurring_payment')
                            {
                                $user_subsdata = isset($planData['arm_paystack']) ? $planData['arm_paystack'] : array();
                                do_action('arm_after_recurring_payment_success_outside',$user_id,$entry_plan,'paystack',$payment_mode,$user_subsdata);
                            }
                            //$ARMember->arm_write_response("paystack subscription 1 arm_payment_type ".$arm_payment_type);
                            $pgateway = 'paystack';
                            $arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $payment_log_id, $pgateway, $userPlanData);
                        }
                    }
                }
            }
        }
        return $user_id;
    }
    
    function arm_store_paystack_log($paystack_response = '', $user_id = 0, $plan_id = 0, $extraVars = array(), $arm_display_log = '1') {

        global $wpdb, $ARMember, $arm_global_settings, $arm_member_forms, $arm_payment_gateways;
        $payment_log_table = $ARMember->tbl_arm_payment_log;
        $arm_paystack_response = maybe_unserialize($paystack_response['arm_paystack_response']);
        
        $arm_paystack_transaction_id = !empty($arm_paystack_response->data->reference) ? $arm_paystack_response->data->reference : '';
        $arm_paystack_subscription_id = !empty($paystack_response['arm_paystack_subscription_code']) ? $paystack_response['arm_paystack_subscription_code']: '';
        $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $arm_paystack_transaction_id));
        if (!empty($paystack_response) && empty($transaction)) {
            $payment_data = array(
                'arm_user_id' => $user_id,
                'arm_first_name'=>$paystack_response['arm_first_name'],
                'arm_last_name'=>$paystack_response['arm_last_name'],
                'arm_plan_id' => (!empty($plan_id) ? $plan_id : 0),
                'arm_payment_gateway' => 'paystack',
                'arm_payment_type' => $paystack_response['arm_payment_type'],
                'arm_token' => $arm_paystack_subscription_id,
                'arm_payer_email' => $paystack_response['payer_email'],
                'arm_receiver_email' => '',
                'arm_transaction_id' => $arm_paystack_transaction_id,
                'arm_transaction_payment_type' => $paystack_response['payment_type'],
                'arm_transaction_status' => $paystack_response['payment_status'],
                'arm_payment_date' => date('Y-m-d H:i:s', strtotime($arm_paystack_response->data->paidAt)),
                'arm_payment_mode' => $paystack_response['arm_payment_mode'],
                'arm_amount' => $paystack_response['payment_amount'],
                'arm_currency' => $paystack_response['currency'],
                'arm_coupon_code' => $paystack_response['arm_coupon_code'],
                'arm_coupon_discount' => (isset($paystack_response['arm_coupon_discount']) && !empty($paystack_response['arm_coupon_discount'])) ? $paystack_response['arm_coupon_discount'] : 0,
                'arm_coupon_discount_type' => isset($paystack_response['arm_coupon_discount_type']) ? $paystack_response['arm_coupon_discount_type'] : '',
                'arm_response_text' => maybe_serialize($arm_paystack_response),
                'arm_extra_vars' => maybe_serialize($extraVars),
                'arm_is_trial' => isset($paystack_response['arm_is_trial']) ? $paystack_response['arm_is_trial'] : 0,
                'arm_display_log' => $arm_display_log,
                'arm_created_date' => current_time('mysql'),
                'arm_coupon_on_each_subscriptions' => !empty($paystack_response['arm_coupon_on_each_subscriptions']) ? $paystack_response['arm_coupon_on_each_subscriptions'] : 0,
            );
            //$ARMember->arm_write_response("reputelog paystack payment_data => ".maybe_serialize($payment_data));
            $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
            return $payment_log_id;
        }
        return false;
    }

    
    function arm2_paystack_cancel_subscription($user_id, $plan_id){
        global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
        if (isset($user_id) && $user_id != 0 && isset($plan_id) && $plan_id != 0) {
            $user_detail = get_userdata($user_id);
            $payer_email = $user_detail->user_email;
            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
            $currency = $arm_payment_gateways->arm_get_global_currency();
            //$ARMember->arm_write_response("reputelog paystack cancel 1 planData => ".maybe_serialize($planData));
            if(!empty($planData)){
                $user_payment_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
                if (strtolower($user_payment_gateway) == 'paystack') 
                {
                    $user_paystack_data = $planData['arm_paystack'];
                    //$ARMember->arm_write_response("reputelog paystack  cancel 2 planData => ".maybe_serialize($planData));

                    $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                    $paystack_transaction_id = isset($user_paystack_data['transaction_id']) ? $user_paystack_data['transaction_id'] : '';
                    $arm_paystack_customer_code = isset($user_paystack_data['arm_paystack_customer_code']) ? $user_paystack_data['arm_paystack_customer_code'] : '';

                    $arm_paystack_subscription_code = isset($user_paystack_data['arm_paystack_subscription_code']) ? $user_paystack_data['arm_paystack_subscription_code'] : '';

                    $arm_paystack_subscription_token = isset($user_paystack_data['arm_paystack_subscription_token']) ? $user_paystack_data['arm_paystack_subscription_token'] : '';
                            
                    $planDetail = $planData['arm_current_plan_detail'];

                    if (!empty($planDetail)) { 
                        $planObj = new ARM_Plan(0);
                        $planObj->init((object) $planDetail);
                    } else {
                        $planObj = new ARM_Plan($plan_id);
                    }

                    $payment_log_table = $ARMember->tbl_arm_payment_log;
                    $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type,arm_amount FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s AND `arm_display_log` = %d ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'paystack', 'success', 1));
                     
                    if (!empty($transaction)) {
                        $extra_var = maybe_unserialize($transaction->arm_extra_vars);
                        //$ARMember->arm_write_response("reputelog paystack extra_var => ".maybe_serialize($extra_var));
                        $payer_email = $transaction->arm_payer_email;
                        $payment_type = $extra_var['payment_type'];
                        $payment_mode = $extra_var['payment_mode'];
                        $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;

                        $gateway_options = get_option('arm_payment_gateway_settings');
                        $pgoptions = maybe_unserialize($gateway_options);
                        $paystack_options = $pgoptions['paystack'];
                        if($paystack_options['paystack_payment_mode']=='sandbox')
                        {
                            $paystack_secret_key  = $paystack_options['paystack_test_secret_key'];
                        }
                        else
                        {
                            $paystack_secret_key  = $paystack_options['paystack_live_secret_key'];
                        }
                        //$ARMember->arm_write_response("reputelog paystack cancel paystack_secret_key => ".maybe_serialize($paystack_secret_key));
                        
                        if ($payment_type == 'paystack') {
                            //$ARMember->arm_write_response("reputelog paystack cancel user_selected_payment_mode => ".maybe_serialize($user_selected_payment_mode));
                             if($user_selected_payment_mode == 'auto_debit_subscription') {
                                if (!empty($paystack_transaction_id) && !empty($arm_paystack_subscription_code)) {

                                    $arm_paystack_subscription_url = 'https://api.paystack.co/subscription/' . $arm_paystack_subscription_code;
                                    $arm_paystack_headers = array(
                                        'Authorization' => 'Bearer ' . $paystack_secret_key
                                    );
                                    $arm_paystack_args = array(
                                        'headers' => $arm_paystack_headers,
                                        'timeout' => 60
                                    );
                                   $arm_paystack_subscription_get_request = wp_remote_get($arm_paystack_subscription_url, $arm_paystack_args);
                                   if(!is_wp_error($arm_paystack_subscription_get_request))
                                   {
                                        $arm_paystack_subscription_get_response = json_decode(wp_remote_retrieve_body($arm_paystack_subscription_get_request));

                                        //$ARMember->arm_write_response("reputelog paystack arm_paystack_subscription_get_response => ".maybe_serialize($arm_paystack_subscription_get_response));
                                        $arm_paystack_cancel_url = 'https://api.paystack.co/subscription/disable';
                                        $arm_paystack_cancel_headers = array(
                                            'Content-Type'  => 'application/json',
                                            'Authorization' => "Bearer ".$paystack_secret_key
                                        );
                                        $arm_paystack_cancel_body = array(
                                            'code'  => $arm_paystack_subscription_get_response->data->subscription_code,
                                            'token' => $arm_paystack_subscription_get_response->data->email_token,

                                        );
                                        $arm_paystack_cancel_args = array(
                                            'body'      => json_encode($arm_paystack_cancel_body),
                                            'headers'   => $arm_paystack_cancel_headers,
                                            'timeout'   => 60
                                        );

                                        $arm_paystack_cancel_request = wp_remote_post($arm_paystack_cancel_url, $arm_paystack_cancel_args);
                                        // print_r($request);
                                        if (!is_wp_error($arm_paystack_cancel_request)) {
                                            $arm_paystack_cancel_response = json_decode(wp_remote_retrieve_body($arm_paystack_cancel_request));
                                            
                                            //$ARMember->arm_write_response("reputelog paystack arm_paystack_cancel_response => ".maybe_serialize($arm_paystack_cancel_response));
                                        }
                                    if($arm_paystack_cancel_response->message=='Subscription disabled successfully')
                                    {
                                        $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                        $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'cancel_payment'));
                                        $payment_data = array(
                                            'arm_user_id' => $user_id,
                                            'arm_plan_id' => $plan_id,
                                            'arm_first_name'=> $user_detail_first_name,
                                            'arm_last_name'=> $user_detail_last_name,
                                            'arm_payment_gateway' => 'paystack',
                                            'arm_payment_type' => 'subscription',
                                            'arm_token' => $arm_paystack_subscription_code,
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
                                        $is_cancelled_by_system = get_user_meta($user_id, 'arm_payment_cancelled_by', true);
                                        $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
                                        //$ARMember->arm_write_response("reptuelog payment log id => ".$payment_log_id);
                                        delete_user_meta($user_id, 'arm_payment_cancelled_by');
                                        return;
                                    }

                                   }
                                    
                                }
                            } else {
                                $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);

                                $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                $payment_data = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan_id,
                                    'arm_first_name'=> $user_detail_first_name,
                                    'arm_last_name'=> $user_detail_last_name,
                                    'arm_payment_gateway' => 'paystack',
                                    'arm_payment_type' => 'subscription',
                                    'arm_payer_email' => $payer_email,
                                    'arm_receiver_email' => '',
                                    'arm_transaction_id' => $transaction->arm_transaction_id,
                                    'arm_token' => $arm_paystack_subscription_code,
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
    }
    function arm_payment_gateway_has_plan_field_outside_func($paymentgateway_main_plan_options, $selectedPlans, $allPlans, $alertMessages, $setup_modules,$selectedGateways)
    {
        if(!empty($allPlans))
        {
            $paymentgateway_plan_options = "";
            //$paymentgatewayPlanIDWarning = $alertMessages['paystackPlanIDWarning'];
            $paymentgateway_plan_options .= "<h4>" . __('Paystack Plans', ARM_PAYSTACK_TEXTDOMAIN) . "<i class='arm_helptip_icon armfa armfa-question-circle' title='".__('You must need to add plan ID for recurring plans', ARM_PAYSTACK_TEXTDOMAIN). '<br/>' . __("You can find / create plans easily via the", ARM_PAYSTACK_TEXTDOMAIN) . ' <a href="https://dashboard.paystack.com/#/plans">' . __('plan management', ARM_PAYSTACK_TEXTDOMAIN) . "</a>' ". __("page of the Paystack dashboard.", ARM_PAYSTACK_TEXTDOMAIN) ."'></i></h4>";
            $paystack_plans = isset($setup_modules['modules']['paystack_plans']) ? $setup_modules['modules']['paystack_plans'] : array();
            $plan_options = array();
            $plan_detail = array();

            $show_paystack_plan_title=0;  
            $plan_object_array = array();
            foreach ($allPlans as $pID => $pdata) {
                $pddata = isset($allPlans[$pID]) ? $allPlans[$pID] : array();
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
                        $paystack_payment_mode = (isset($selectedPaymentModes['paystack'])) ? $selectedPaymentModes['paystack'] : 'both';
                        $show_paystack_plan_block = 'display: none;';
                        if(in_array($pID, $selectedPlans) && $paystack_payment_mode != 'manual_subscription'){
                            $show_paystack_plan_title++;
                            $show_paystack_plan_block = 'display: block;';
                        }
                        
                        $paymentgateway_plan_options .= '<label class="arm_paystack_plans arm_paystack_plan_label_' . $pID . '" style="'.$show_paystack_plan_block.'"><span class="arm_paystack_plan_class">' . stripslashes($pddata['arm_subscription_plan_name']) . '</span>';
                       
                        foreach($plan_payment_cycles as $plan_cycle_key => $plan_cycle_data){
                            $cycle_key = isset($plan_cycle_data['cycle_key']) ? $plan_cycle_data['cycle_key'] : ''; 
                            if(isset($paystack_plans[$pID])){
                                if(is_array($paystack_plans[$pID])){
                                     $paystack_pID = isset($paystack_plans[$pID][$cycle_key]) ? $paystack_plans[$pID][$cycle_key] : '';
                                }
                                else{
                                     $paystack_pID = isset($paystack_plans[$pID]) ? $paystack_plans[$pID]: '';
                                }
                            }
                            else{
                                $paystack_pID = '';
                            }
                           $cycle_label = isset($plan_cycle_data['cycle_label']) ? $plan_cycle_data['cycle_label']: ''; 
                            $paymentgateway_plan_options .= '<label class="arm_paystack_plans arm_paystack_plan_div"><span>' . stripslashes($cycle_label) . '</span>';
                            $paymentgateway_plan_options .= '<input type="text" name="setup_data[setup_modules][modules][paystack_plans][' . $pID . ']['.$cycle_key.']" value="' . $paystack_pID . '" class="arm_setup_paystack_plan_input" data-plan_id="' . $pID . '" placeholder="' . __('Paystack plan ID', ARM_PAYSTACK_TEXTDOMAIN) . '">';
                            $paymentgateway_plan_options .= '</label>';
                        }
                        $paymentgateway_plan_options .= '</label>';
                    }
                }
            }

            $arm_show_paystack_plans = false;
            if(!empty($selectedPlans)) {
                foreach($selectedPlans as $sPID) {
                    $plan_object = (isset($plan_object_array[$sPID]) && !empty($plan_object_array[$sPID] ) )? $plan_object_array[$sPID] : '' ;
                    if(is_object($plan_object)){
                        if( $plan_object->is_recurring()){
                            if(in_array('paystack', $selectedGateways) && $show_paystack_plan_title>0){
                                $arm_show_paystack_plans = true;
                            }
                        }
                    }
                }
            }

            $arm_gateway_option_display = 'display: block;';
            if(!$arm_show_paystack_plans) {
                $arm_gateway_option_display = 'display: none;';
            }
            
            $paymentgateway_main_plan_options .= "<div class='arm_paystack_plan_container' style='".$arm_gateway_option_display."'>";
            $paymentgateway_main_plan_options .= $paymentgateway_plan_options;
            $paymentgateway_main_plan_options .= "</div>";
        }
        return $paymentgateway_main_plan_options;
    }

    function arm_paystack_modify_coupon_code($data,$payment_mode,$couponData,$planAmt, $plan_obj){

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
        $cron_hook_array[] = 'arm_membership_paystack_recurring_payment';
        return $cron_hook_array;
    }

    function arm2_membership_paystack_check_recurring_payment() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_payment_gateways, $arm_subscription_plan, $arm_manage_communication, $arm_members_class, $arm_subscription_plans;
            set_time_limit(0);
            //$ARMember->arm_write_response("reputelog paystack : in cron");
            $payment_log_table = $ARMember->tbl_arm_payment_log;           
            
            $args = array(
                'meta_query' => array(

                    array(
                        'key' => 'arm_user_plan_ids',
                        'value' => '',
                        'compare' => '!='
                    ),
                )
            );
            
            $users = get_users($args);
            
            if (!empty($users)) {
                foreach ($users as $usr) {
                    $user_id = $usr->ID;
                    //$ARMember->arm_write_response("reputelog paystack : user id => ".$user_id);
                    $plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true); 
                    $plan_ids = !empty($plan_ids) ? $plan_ids : array(); 
                    if(!empty($plan_ids) && is_array($plan_ids)){
                        foreach($plan_ids as $plan_id){
                            //$ARMember->arm_write_response("reputelog paystack : user id => ".$user_id." plan id => ".$plan_id);
                            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                            //$ARMember->arm_write_response("reputelog paystack : planData ".maybe_serialize($planData));
                            if(!empty($planData)){
                                $arm_user_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
                                if($arm_user_gateway != 'paystack')
                                { continue; }
                                $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                                $planDetail = $planData['arm_current_plan_detail'];
                                $arm_paysatck_details = isset($planData['arm_paystack']) ? $planData['arm_paystack'] : array();
                                
                                if (!empty($planDetail)) { 
                                    $plan = new ARM_Plan(0);
                                    $plan->init((object) $planDetail);
                                } else {
                                    $plan = new ARM_Plan($plan_id);
                                }
                                if ($plan->is_recurring() && $user_selected_payment_mode == 'auto_debit_subscription') {
                                    //$ARMember->arm_write_response("reputelog paystack : user id => ".$user_id." plan id => ".$plan_id."  Auto debit");
                                    $get_payment = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id, arm_transaction_id, arm_extra_vars, arm_coupon_discount, arm_coupon_discount_type,arm_coupon_on_each_subscriptions FROM `{$payment_log_table}` WHERE `arm_plan_id` = %d AND `arm_user_id` = %d AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s ORDER BY arm_log_id DESC LIMIT 0,1", $plan_id, $user_id, 'paystack', 'success'));
                                    //$ARMember->arm_write_response("reputelog paystack get_payment :  => ".maybe_serialize($get_payment));
                                    if (empty($get_payment)) {
                                        continue;
                                    }
                                    $extra_vars = maybe_unserialize($get_payment[0]->arm_extra_vars);
                                    //$ARMember->arm_write_response("reputelog paystack extra_vars :  => ".maybe_serialize($extra_vars));
                                    if(isset($extra_vars['trial']))
                                    {
                                        unset($extra_vars['trial']);
                                        $arm_paystack_plan_amount = $extra_vars['plan_amount'];
                                        $arm_paystack_tax_percentage = $extra_vars['tax_percentage'];
                                        if(isset($extra_vars['coupon']) && $get_payment[0]->arm_coupon_on_each_subscriptions==1)
                                        {
                                            $arm_paystack_coupon_amount = $get_payment[0]->arm_coupon_discount;
                                            if($get_payment[0]->arm_coupon_discount_type=='%')
                                            {
                                                $arm_paystack_coupon_amount = ($arm_paystack_plan_amount * $arm_paystack_coupon_amount) / 100;
                                                //$ARMember->arm_write_response("reputelog paystack arm_paystack_plan_amount %:  => ".$arm_paystack_plan_amount);
                                                //$ARMember->arm_write_response("reputelog paystack arm_paystack_coupon_amount %:  => ".$arm_paystack_coupon_amount);
                                            }

                                            $arm_paystack_plan_amount = $arm_paystack_plan_amount - $arm_paystack_coupon_amount;
                                        }
                                        
                                        $arm_paystack_tax_amount = ($arm_paystack_plan_amount * $arm_paystack_tax_percentage) / 100 ;
                                        //$ARMember->arm_write_response("reputelog paystack arm_paystack_plan_amount tax %:  1=> ".$arm_paystack_plan_amount);
                                        //$ARMember->arm_write_response("reputelog paystack arm_paystack_tax_percentage tax %:  1=> ".$arm_paystack_tax_percentage);
                                        //$ARMember->arm_write_response("reputelog paystack arm_paystack_tax_amount %:  1=> ".$arm_paystack_tax_amount);
                                        $arm_paystack_tax_amount = number_format((float)$arm_paystack_tax_amount, 2, '.','');
                                        //$ARMember->arm_write_response("reputelog paystack arm_paystack_plan_amount tax %:  2=> ".$arm_paystack_plan_amount);
                                        //$ARMember->arm_write_response("reputelog paystack arm_paystack_tax_percentage tax %:  2=> ".$arm_paystack_tax_percentage);
                                        //$ARMember->arm_write_response("reputelog paystack arm_paystack_tax_amount %:  2=> ".$arm_paystack_tax_amount);
                                        $extra_vars['tax_amount'] = $arm_paystack_tax_amount ;

                                    }
                                    else
                                    {
                                        $arm_paystack_plan_amount = $extra_vars['plan_amount'];
                                        $arm_paystack_tax_percentage = $extra_vars['tax_percentage'];
                                        //$ARMember->arm_write_response("reputelog paystack without trail arm_paystack_plan_amount tax %:  1=> ".$arm_paystack_plan_amount);
                                        //$ARMember->arm_write_response("reputelog paystack without trail arm_paystack_tax_percentage tax %:  1=> ".$arm_paystack_tax_percentage);
                                        
                                        if(isset($extra_vars['coupon']) && $get_payment[0]->arm_coupon_on_each_subscriptions==1)
                                        {
                                            $arm_paystack_coupon_amount = $get_payment[0]->arm_coupon_discount;
                                            if($get_payment[0]->arm_coupon_discount_type=='%')
                                            {
                                                //$ARMember->arm_write_response("reputelog paystack without trail arm_paystack_coupon_amount %:  1=> ".$arm_paystack_coupon_amount);
                                                $arm_paystack_coupon_amount = ($arm_paystack_plan_amount * $arm_paystack_coupon_amount) / 100;
                                            }
                                            //$ARMember->arm_write_response("reputelog paystack without trail arm_paystack_coupon_amount :  2=> ".$arm_paystack_coupon_amount);
                                            $arm_paystack_plan_amount = $arm_paystack_plan_amount - $arm_paystack_coupon_amount;
                                            //$ARMember->arm_write_response("reputelog paystack without trail arm_paystack_coupon_amount :  2=> ".$arm_paystack_coupon_amount);
                                        }
                                        
                                        $arm_paystack_tax_amount = ($arm_paystack_plan_amount * $arm_paystack_tax_percentage) / 100 ;
                                        $arm_paystack_tax_amount = number_format((float)$arm_paystack_tax_amount, 2, '.','');
                                        //$ARMember->arm_write_response("reputelog paystack without trail arm_paystack_tax_amount %:  1=> ".$arm_paystack_tax_amount);
                                        $extra_vars['tax_amount'] = $arm_paystack_tax_amount ;
                                    }
                                    
                                    $gateway_options = get_option('arm_payment_gateway_settings');

                                    $pgoptions = maybe_unserialize($gateway_options);
                                    $pgoptions = $pgoptions['paystack'];

                                    $arm_paystack_subscription_id = !empty($arm_paysatck_details['arm_paystack_subscription_code']) ? $arm_paysatck_details['arm_paystack_subscription_code'] : '';
                                    
                                    
                                    $payment_type = isset($extra_vars['payment_type']) ? $extra_vars['payment_type'] : 'paystack';
                                    $payment_mode = isset($extra_vars['payment_mode']) ? $extra_vars['payment_mode'] : $pgoptions['paystack_payment_mode'];
                                    $is_sandbox_mode = ($payment_mode == 'sandbox') ? true : false;

                                    $arm_paystack_secret_key = ($is_sandbox_mode) ? $pgoptions['paystack_test_secret_key'] : $pgoptions['paystack_live_secret_key'];
                                    $arm_paystack_public_key = ($is_sandbox_mode) ? $pgoptions['paystack_test_public_key'] : $pgoptions['paystack_live_public_key'];
                                    //$ARMember->arm_write_response("reputelog paystack : arm_paystack_secret_key => ".$arm_paystack_secret_key);
                                    if ($payment_type == 'paystack' && ($payment_type != '' || $payment_mode != '')) {
                                        //$ARMember->arm_write_response("reputelog paystack : payment_type => ".$payment_type);

                                        $arm_paystack_url = 'https://api.paystack.co/subscription/'.$arm_paystack_subscription_id;
                                        
                                        $arm_paystack_headers = array(
                                            'Content-Type'  => 'application/json',
                                            'Authorization' => 'Bearer '.$arm_paystack_secret_key
                                        );
                                        
                                        $arm_paystack_args = array(
                                            'headers'   => $arm_paystack_headers,
                                            'timeout'   => 60
                                        );

                                        
                                        $arm_paystack_request = wp_remote_get($arm_paystack_url, $arm_paystack_args);
                                        
                                        //$ARMember->arm_write_response("reputelog paystack : arm_paystack_request 1 => ".maybe_serialize($arm_paystack_request));
                                        $arm_paystack_response = json_decode(wp_remote_retrieve_body($arm_paystack_request));
                                        
                                        $transaction_id = $get_payment[0]->arm_transaction_id;
                                        $arm_log_id = $get_payment[0]->arm_log_id;

                                        //$ARMember->arm_write_response("reputelog paystack : arm_paystack_response 1 => ".maybe_serialize($arm_paystack_response));

                                        $total_transaction = !empty($arm_paystack_response->data->invoices) ? count($arm_paystack_response->data->invoices) : '0';
                                        $arm_subscription_data_status = $arm_paystack_response->data->status;
                                        /*if($arm_subscription_data_status == 'active')
                                        {*/
                                            if($total_transaction > 0)
                                            {
                                                $i = 1;
                                                foreach($arm_paystack_response->data->invoices as $arm_paystack_invoice_details)
                                                {
                                                    //$ARMember->arm_write_response("reputelog paystack : arm_paystack_invoice_details 1 => ".maybe_serialize($arm_paystack_invoice_details));
                                                    //$ARMember->arm_write_response("reputelog paystack : i-1=> ".$i);
                                                    $arm_paystack_invoice_status = $arm_paystack_invoice_details->status;
                                                    if ($arm_paystack_invoice_status == 'success') {
                                                            $payer_email = $arm_paystack_response->data->customer->email;
                                                            
                                                            $first_transaction_id = isset($arm_paystack_invoice_details->reference) ? $arm_paystack_invoice_details->reference : '';
                                                             //$ARMember->arm_write_response("reputelog paystack : first_transaction_id=> 1 ".$first_transaction_id);
                                                             //$ARMember->arm_write_response("reputelog paystack : transaction_id=> 1 ".$transaction_id);
                                                            $first_amount = isset($arm_paystack_invoice_details->amount) ? ($arm_paystack_invoice_details->amount / 100) : 0;
                                                            $arm_update_meta = false;
                                                            /*if ($first_transaction_id != '' && $transaction_id == $first_transaction_id) 
                                                            { 
                                                                
                                                                //$ARMember->arm_write_response("reputelog paystack : first_transaction_id=> 2 ".$first_transaction_id);
                                                             //$ARMember->arm_write_response("reputelog paystack : transaction_id=> 2 ".$transaction_id);
                                                                $first_transaction_time = strtotime($arm_paystack_invoice_details->paidAt);
                                                                
                                                                $getTransaction = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s", $first_transaction_id));
                                                                if (empty($getTransaction)) {
                                                                    $arm_update_meta = true;
                                                                    $first_trxn_date = date('Y-m-d H:i:s', $first_transaction_time);
                                                                    $wpdb->update($payment_log_table, array(
                                                                        'arm_transaction_id' => $first_transaction_id, 
                                                                        'arm_payment_date' => $first_trxn_date, 
                                                                        'arm_created_date' => $first_trxn_date, 
                                                                        'arm_transaction_status' => $arm_paystack_invoice_status,
                                                                        'arm_display_log' => 1, 
                                                                        'arm_amount'=>$first_amount), 
                                                                        array('arm_log_id' => $arm_log_id));
                                                                }
                                                            
                                                            }
                                                            else
                                                            {*/
                                                            
                                                             $arm_paystack_transaction_id = isset($arm_paystack_invoice_details->reference) ? $arm_paystack_invoice_details->reference : '';
                                                             //$ARMember->arm_write_response("reputelog paystack : arm_paystack_transaction_id ".$arm_paystack_transaction_id." user_id => ".$user_id);
                                                             if ($arm_paystack_transaction_id == '') {
                                                                    continue;
                                                                }
                                                           
                                                                $getTransaction = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s ", $arm_paystack_transaction_id));
                                                                //$ARMember->arm_write_response("reputelog getTransaction : ".maybe_serialize($getTransaction));
                                                                //$ARMember->arm_write_response("reputelog paystack : i=> 2 ".$i);
                                                                if (empty($getTransaction)) { 
                                                                   
                                                                    $arm_paystack_transaction_time = isset($arm_paystack_invoice_details->paidAt) ? strtotime($arm_paystack_invoice_details->paidAt) : '';
                                                                    $amount = isset($arm_paystack_invoice_details->amount) ? ($arm_paystack_invoice_details->amount / 100) : 0;
                                                                    
                                                                    $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                                                    $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                                                                    $recurring_payment_data = array(
                                                                        'arm_user_id' => $user_id,
                                                                        'arm_plan_id' => $plan_id,
                                                                        'arm_first_name' => $user_detail_first_name,
                                                                        'arm_last_name' => $user_detail_last_name,
                                                                        'arm_token' => $arm_paystack_subscription_id,
                                                                        'arm_payment_gateway' => 'paystack',
                                                                        'arm_payment_type' => 'subscription',
                                                                        'arm_payer_email' => $payer_email,
                                                                        'arm_transaction_payment_type' => 'subscription',
                                                                        'arm_transaction_id' => $arm_paystack_transaction_id,
                                                                        'arm_transaction_status' => $arm_paystack_invoice_status,
                                                                        'arm_payment_date' => date('Y-m-d H:i:s', $arm_paystack_transaction_time),
                                                                        'arm_amount' => $amount,
                                                                        'arm_extra_vars' => maybe_serialize($extra_vars),
                                                                        'arm_response_text' => maybe_serialize($arm_paystack_invoice_details),
                                                                        'arm_created_date' => current_time('mysql'),
                                                                        'arm_display_log' => '1'
                                                                    );
                                                                    //$ARMember->arm_write_response("reputelog arm_update_meta = recurring_payment_data => ".maybe_serialize($recurring_payment_data));
                                                                    $payment_log_id = $arm_payment_gateways->arm_save_payment_log($recurring_payment_data);
                                                                    
                                                                    
                                                                    $arm_update_meta = true;
                                                                    
                                                                    
                                                                }
                                                            
                                                                if($arm_update_meta){
                                                                    $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                                                                    $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $plan_id, true);
                                                                    $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                                                                    $planData = shortcode_atts($defaultPlanData, $userPlanDatameta);
                                                                    //$ARMember->arm_write_response("reputelog arm_update_meta = planData => ".maybe_serialize($planData));
                                                                    $arm_next_due_payment_date = $planData['arm_next_due_payment'];
                                                                    //$ARMember->arm_write_response("reputelog arm_update_meta = arm_next_due_payment_date => ".maybe_serialize($arm_next_due_payment_date));
                                                                    //$ARMember->arm_write_response("reputelog arm_update_meta = current_time => ".maybe_serialize(strtotime(current_time('mysql'))));
                                                                    
                                                                    if(!empty($arm_next_due_payment_date)){
                                                                        if(strtotime(current_time('mysql')) >= $arm_next_due_payment_date){
                                                                            $total_completed_recurrence = $planData['arm_completed_recurring'];
                                                                            $total_completed_recurrence++;
                                                                            $planData['arm_completed_recurring'] = $total_completed_recurrence;
                                                                            //$ARMember->arm_write_response("reputelog arm_update_meta = if not empty arm_next_due_payment_date = plan data=> ".maybe_serialize($planData));
                                                                            update_user_meta($user_id, 'arm_user_plan_'.$plan_id, $planData);
                                                                            $payment_cycle = $planData['arm_payment_cycle'];

                                                                            $arm_next_payment_date = $arm_members_class->arm_get_next_due_date($user_id, $plan_id, false, $payment_cycle);
                                                                            $planData['arm_next_due_payment'] = $arm_next_payment_date;
                                                                            //$ARMember->arm_write_response("reputelog arm_update_meta = if not empty arm_next_due_payment_date = plan data=> ".maybe_serialize($planData));
                                                                            update_user_meta($user_id, 'arm_user_plan_'.$plan_id, $planData); 

                                                                        }
                                                                        else{
                                                                            $now = current_time('mysql');
                                                                            $arm_last_payment_status = $wpdb->get_var($wpdb->prepare("SELECT `arm_transaction_status` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d AND `arm_created_date`<=%s ORDER BY `arm_log_id` DESC LIMIT 0,1", $user_id, $plan_id, $now));  
                                                                            if(in_array($arm_last_payment_status, array('success', 'pending'))){
                                                                                $total_completed_recurrence = $planData['arm_completed_recurring'];
                                                                                $total_completed_recurrence++;
                                                                                $planData['arm_completed_recurring'] = $total_completed_recurrence;
                                                                                //$ARMember->arm_write_response("reputelog arm_update_meta = else arm_next_due_payment_date plan data=> 1 ".maybe_serialize($planData));
                                                                                update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $planData);
                                                                                $payment_cycle = $planData['arm_payment_cycle'];

                                                                                $arm_next_payment_date = $arm_members_class->arm_get_next_due_date($user_id, $plan_id, false, $payment_cycle);
                                                                                $planData['arm_next_due_payment'] = $arm_next_payment_date;
                                                                                //$ARMember->arm_write_response("reputelog arm_update_meta = else arm_next_due_payment_date plan data=> 2 ".maybe_serialize($planData));
                                                                                update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $planData);
                                                                            }
                                                                        }
                                                                    }
                                                                
                                                                    $suspended_plan_ids = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
                                                                    $suspended_plan_id = (isset($suspended_plan_ids) && !empty($suspended_plan_ids)) ? $suspended_plan_ids :  array(); 

                                                                    if(in_array($plan_id, $suspended_plan_id)){
                                                                         unset($suspended_plan_id[array_search($plan_id,$suspended_plan_id)]);
                                                                         update_user_meta($user_id, 'arm_user_suspended_plan_ids', array_values($suspended_plan_id));
                                                                    }

                                                                    $user_subsdata = $planData['arm_paystack'];
                                                                    do_action('arm_after_recurring_payment_success_outside',$user_id,$plan_id,'paystack',$payment_mode,$user_subsdata);
                                                                }
                                                                
                                                            //}
                                                               
                                                            
                                                            } 
                                                            else {
                                                            //$ARMember->arm_write_response("reputelog paystack : else arm_paystack_invoice_status => ".$arm_paystack_invoice_status);
                                                            switch ($arm_paystack_invoice_status) {
                                                                case 'EXPIRED':
                                                                    $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'eot'));
                                                                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'eot'));
                                                                    break;
                                                                case 'failed':
                                                                    $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                                                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                                                    
                                                                    break;
                                                                case 'abandoned':
                                                                    $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                                                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                                                    break;
                                                                default:
                                                                    break;
                                                            }
                                                            break;
                                                        }
                                                        $i++;
                                                }
                                                
                                            }

                                            if($arm_subscription_data_status=='complete')
                                            {
                                                $payment_cycle = $planData['arm_payment_cycle'];
                                                $recurring_plan_options = $plan->prepare_recurring_data($payment_cycle);
                                                $recurring_time = $recurring_plan_options['rec_time'];
                                                $total_completed_recurrence = $planData['arm_completed_recurring'];
                                                //$ARMember->arm_write_response("reputelog paystack : recurring_time => ".$recurring_time);
                                                //$ARMember->arm_write_response("reputelog paystack : total_completed_recurrence => ".$total_completed_recurrence);
                                                if($recurring_time!=$total_completed_recurrence)
                                                {
                                                    if (!empty($arm_paystack_subscription_id)) 
                                                    {
                                                        //$ARMember->arm_write_response("reputelog paystack  cancel : arm_subscription_data_status => ".$arm_subscription_data_status);
                                                    /*switch ($arm_subscription_data_status) {
                                                        case 'complete':*/
                                                            //$ARMember->arm_write_response("reputelog paystack cancel : user_id => ".$user_id);
                                                            //$ARMember->arm_write_response("reputelog paystack cancel : plan_id => ".$plan_id);

                                                            $arm_subscription_plans->arm_add_membership_history($user_id, $plan_id, 'cancel_subscription');
                                                            do_action('arm_cancel_subscription', $user_id, $plan_id);
                                                            $arm_subscription_plans->arm_clear_user_plan_detail($user_id, $plan_id);
                                                            //break;
                                                        
                                                        /*default:
                                                            break;
                                                    }*/

                                                        $cancel_plan_act = isset($plan->options['cancel_action']) ? $plan->options['cancel_action'] : 'block';
                                                        if ($arm_subscription_plans->isPlanExist($cancel_plan_act)) {
                                                            $arm_members_class->arm_new_plan_assigned_by_system($cancel_plan_act, $plan_id, $user_id);
                                                        }

                                                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));

                                                        do_action('arm_after_recurring_payment_cancelled_outside', $user_id, $plan_id, 'paystack');
                                                    

                                                    }

                                                }
                                            }
                                            
                                        //}
                                        /*else {
                                                //$ARMember->arm_write_response("reputelog paystack else cancel : arm_subscription_data_status => ".$arm_subscription_data_status);
                                                switch ($arm_subscription_data_status) {
                                                    case 'complete':
                                                        //$ARMember->arm_write_response("reputelog paystack else : user_id => ".$user_id);
                                                        //$ARMember->arm_write_response("reputelog paystack else : plan_id => ".$plan_id);
                                                        do_action('arm_cancel_subscription_gateway_action',$user_id, $plan_id);
                                                        break;
                                                    
                                                    default:
                                                        break;
                                                }
                                            }*/
                                    } else {
                                        // Paystack
                                    }
                                   
                                }
                            }
                        }
                    }
                }
            }   
        }
    
}

global $arm_paystack;
$arm_paystack = new ARM_Paystack();


global $armpaystack_api_url, $armpaystack_plugin_slug;

$armpaystack_api_url = $arm_paystack->armpaystack_getapiurl();
$armpaystack_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armpaystack_check_for_plugin_update');

function armpaystack_check_for_plugin_update($checked_data) {
    global $armpaystack_api_url, $armpaystack_plugin_slug, $wp_version, $arm_paystack_version,$arm_paystack;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armpaystack_plugin_slug,
        'version' => $arm_paystack_version,
        'other_variables' => $arm_paystack->armpaystack_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(ARM_PAYSTACK_HOME_URL)
        ),
        'user-agent' => 'ARMPAYSTACK-WordPress/' . $wp_version . '; ' . ARM_PAYSTACK_HOME_URL
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armpaystack_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armpaystack_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armpaystack_plugin_slug . '/' . $armpaystack_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armpaystack_plugin_api_call', 10, 3);

function armpaystack_plugin_api_call($def, $action, $args) {
    global $armpaystack_plugin_slug, $armpaystack_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armpaystack_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armpaystack_plugin_slug . '/' . $armpaystack_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armpaystack_update_token'),
            'request' => serialize($args),
            'api-key' => md5(ARM_PAYSTACK_HOME_URL)
        ),
        'user-agent' => 'ARMPAYSTACK-WordPress/' . $wp_version . '; ' . ARM_PAYSTACK_HOME_URL
    );

    $request = wp_remote_post($armpaystack_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', ARM_PAYSTACK_TEXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', ARM_PAYSTACK_TEXTDOMAIN), $request['body']);
    }

    return $res;
}

class PaystackConfigWrapper {

    public static function getConfig() {
        global $arm_paystack;
        return $arm_paystack->arm_paystack_config();
    }

}

class CreatePaystackPaymentRequest {

    public static function main($data_array) {
        global $ARMember;
        
        $arm_paystack_secret_key = $data_array['arm_paystack_secret_key'];
        $arm_paystack_public_key = $data_array['arm_paystack_public_key'];

        $arm_paystack_url = 'https://api.paystack.co/transaction/initialize';
        $redirect_url = $data_array['redirect_url'];
        
        $amount = $data_array['arm_plan_amount'] * 100;
        $productinfo = $data_array['arm_plan_name'];
        $reference = $data_array['reference'];
        $firstname = $data_array['first_name'];
        $lastname = $data_array['last_name'];
        $email = $data_array['user_email'];
        $currency = $data_array['currency'];
        $PaystackPlanID = $data_array['arm_paystack_plancode'] ;
        $arm_paystack_subscription_code = $data_array['arm_paystack_subscription_code'] ;
        $arm_paystack_invoice_limit = $data_array['arm_paystack_invoice_limit'];
        $arm_has_trial_period = $data_array['arm_has_trial_period'];
        $arm_paystack_trail_invoice_limit = $data_array['arm_paystack_trail_invoice_limit'];
        $arm_coupon_code = $data_array['arm_coupon_code'];
        $arm_paystack_entry_id = $data_array['arm_paystack_entry_id'];
        
        $arm_paystack_headers = array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer '.$arm_paystack_secret_key
        );
        $recurring_payment_mode = $data_array['recurring_payment_mode'] ;

        if($recurring_payment_mode=='auto_debit_subscription' )
        {
            $arm_paystack_body = array(
                'email'        => $email,
                'first_name'   => $firstname,
                'last_name'   => $lastname,
                'amount'       => $amount,
                'reference'    => $reference,
                'currency'     => $currency,
                'callback_url' => $redirect_url,
                'metadata'      => array(
                                    'arm_plan_paystack_code' => $PaystackPlanID,
                                    'arm_paystack_entry_id' => $arm_paystack_entry_id,
                                    'arm_paystack_subscription_code' => $arm_paystack_subscription_code

                                    ),
                'invoice_limit' => $arm_paystack_invoice_limit
            );
        }
        else
        {
            $arm_paystack_body = array(
                'email'        => $email,
                'first_name'   => $firstname,
                'last_name'   => $lastname,
                'amount'       => $amount,
                'reference'    => $reference,
                'currency'     => $currency,
                'callback_url' => $redirect_url,
                'metadata'      => array(
                                    'arm_paystack_entry_id' => $arm_paystack_entry_id,
                                    ),
            );
        }
        
        //$ARMember->arm_write_response("reputelog paystack create payment arm_paystack_body =>".maybe_serialize($arm_paystack_body));
        $arm_paystack_args = array(
            'body'      => json_encode($arm_paystack_body),
            'headers'   => $arm_paystack_headers,
            'timeout'   => 60
        );

        $arm_paystack_request = wp_remote_post($arm_paystack_url, $arm_paystack_args);
        $arm_paystack_response = json_decode(wp_remote_retrieve_body($arm_paystack_request));
        //$ARMember->arm_write_response("reputelog paystack -7.1.2.1- arm_paystack_response =>".maybe_serialize($arm_paystack_response));
        //$arm_paystack_autho_url = $arm_paystack_response->data->authorization_url;

        return $arm_paystack_response;
        
    }

}
?>