<?php 
//@error_reporting(E_ERROR | E_WARNING | E_PARSE);
//@error_reporting(E_ALL);
/*
Plugin Name: ARMember - Mollie payment gateway Addon
Description: Extension for ARMember plugin to accept payments using mollie.
Version: 2.3
Plugin URI: https://www.armemberplugin.com
Author: Repute InfoSystems
Author URI: https://www.armemberplugin.com
*/



define('ARM_MOLLIE_DIR_NAME', 'armembermollie');
define('ARM_MOLLIE_DIR', WP_PLUGIN_DIR . '/' . ARM_MOLLIE_DIR_NAME);

if (is_ssl()) {
    define('ARM_MOLLIE_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_MOLLIE_DIR_NAME));
} else {
    define('ARM_MOLLIE_URL', WP_PLUGIN_URL . '/' . ARM_MOLLIE_DIR_NAME);
}

define('ARM_MOLLIE_TEXTDOMAIN','ARM_MOLLIE');

global $mollie;
if (file_exists(ARM_MOLLIE_DIR . "/lib/Mollie/API/Autoloader.php")) {
    if(!class_exists('Mollie_API_Client'))
    {
        require_once  ARM_MOLLIE_DIR . "/lib/Mollie/API/Autoloader.php";
    }
    $mollie = new Mollie_API_Client();
}

global $arm_mollie_version;
$arm_mollie_version = '2.3';

global $armnew_mollie_version;

global $armmollie_api_url, $armmollie_plugin_slug, $wp_version;

class ARM_Mollie{
    
    var $ARM_Mollie_API_KEY;
    
    function __construct(){
        global $arm_payment_gateways;
        $arm_payment_gateways->currency['mollie'] = $this->arm_mollie_currency_symbol();
        
        add_action('init', array(&$this, 'arm_mollie_db_check'));

        register_activation_hook(__FILE__, array('ARM_Mollie', 'install'));

        register_activation_hook(__FILE__, array('ARM_Mollie', 'arm_mollie_check_network_activation'));

        register_uninstall_hook(__FILE__, array('ARM_Mollie', 'uninstall'));
        
        add_filter('arm_get_payment_gateways', array(&$this, 'arm_add_mollie_payment_gateways'));
        
        add_filter('arm_get_payment_gateways_in_filters', array(&$this,'arm_add_mollie_payment_gateways'));
        
        add_action('admin_notices', array(&$this, 'arm_mollie_admin_notices'));
        
        add_filter('arm_change_payment_gateway_tooltip', array(&$this, 'arm_change_payment_gateway_tooltip_func'), 10, 3);
        
        add_filter('arm_allowed_payment_gateways', array(&$this, 'arm_payment_allowed_gateways'), 10, 3);
        
        add_action('arm_after_payment_gateway_listing_section', array(&$this, 'arm_after_payment_gateway_listing_section_func'), 10, 2);
        
        add_filter('arm_filter_gateway_names', array(&$this, 'arm_filter_gateway_names_func'), 10);
        
        add_filter('arm_change_pending_gateway_outside', array($this, 'arm_change_pending_gateway_outside'), 10, 3);
        
        
        add_filter('arm_currency_support',array(&$this,'arm_mollie_currency_support'), 10, 2);
        
        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_script'), 10);
        
        add_action('wp_head', array(&$this, 'arm_mollie_set_front_js'), 10);
        
        add_action('plugins_loaded', array(&$this, 'arm_mollie_load_textdomain'));
        
        add_action('admin_init', array(&$this, 'upgrade_data_mollie'));
                
        add_filter('arm_change_coupon_code_outside_from_mollie',array(&$this,'arm_mollie_modify_coupon_code'),10,5);    
        
        add_filter('arm_change_pending_gateway_outside',array(&$this,'arm_mollie_change_pending_gateway_outside'),100,3);
        
        if(version_compare($this->get_armember_version(), '2.0', '>=')){
            add_filter('arm_default_plan_array_filter', array(&$this, 'arm2_default_plan_array_filter_func'), 10, 1);
             
            add_filter('arm_membership_update_user_meta_from_outside', array(&$this, 'arm2_membership_mollie_update_usermeta'), 10, 5);
            
            add_action('arm_update_user_meta_after_renew_outside', array(&$this, 'arm2_mollie_update_meta_after_renew'), 10, 4);    
            
            add_action('arm_payment_gateway_validation_from_setup', array(&$this, 'arm2_payment_gateway_form_submit_action'), 10, 4);
            
            add_action('arm_cancel_subscription_gateway_action', array(&$this, 'arm2_mollie_cancel_subscription'), 10, 2);
            
            add_action('wp', array(&$this, 'arm2_mollie_webhook'), 5);
            
            add_filter('arm_setup_show_payment_gateway_notice', array(&$this, 'arm_setup_show_mollie_payment_gateway_notice'), 10, 2);
        }
        else
        {
            add_action('arm_show_payment_gateway_recurring_notice', array(&$this, 'arm_show_payment_gateway_mollie_recurring_notice'), 10);
            
            add_action('arm_payment_gateway_validation_from_setup', array(&$this, 'arm_payment_gateway_form_submit_action'), 10, 4);
            
            add_action('arm_cancel_subscription_gateway_action', array(&$this, 'arm_mollie_cancel_subscription'), 10, 2);
            
            add_action('wp', array(&$this, 'arm_mollie_webhook'), 5);
        }

        add_action('arm_enqueue_js_css_from_outside', array(&$this,'arm_enqueue_mollie_js_css_for_model'),10);
    }
    
    function arm_setup_show_mollie_payment_gateway_notice( $gateway_note, $payment_gateway ) {
        if($payment_gateway == 'mollie') {
            global $arm_payment_gateways;
            $currency = $arm_payment_gateways->arm_get_global_currency();
            $gateway_note = '<span class="arm_invalid" id="arm_mollie_warning" style="width:254px;">'.__("NOTE : In case of automatic subscription, if final payable amount will be zero, then also user need to pay minimum 0.10", ARM_MOLLIE_TEXTDOMAIN).' '.$currency.' '.__("amount to start subscription for mollie.", ARM_MOLLIE_TEXTDOMAIN).'</span>';
        }
        return $gateway_note;
    }
    
    function arm2_default_plan_array_filter_func( $default_plan_array ) {
        $default_plan_array['arm_mollie'] = '';
        return $default_plan_array;
    }
    
    function arm2_membership_mollie_update_usermeta($posted_data, $user_id, $plan, $log_detail, $pgateway) {
        if ($pgateway == 'mollie') {
            $posted_data['arm_mollie'] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
        }
        return $posted_data;
    }
    
    function arm2_mollie_update_meta_after_renew($user_id, $log_detail, $plan_id, $payment_gateway) {
        global $ARMember;
        if ($payment_gateway == 'mollie') {
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
                $plan_data['arm_mollie'] = $pg_subsc_data;
                $ARMember->arm_write_response("rpeutelog log detail : ".maybe_serialize($plan_data));
                update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $plan_data);
            }
        }
    }
    
    function arm_mollie_set_front_js($force_enqueue = false){
        if( $this->is_version_compatible() ){
            global $ARMember, $arm_mollie_version;
            $is_arm_front_page = $ARMember->is_arm_front_page();
            if ($is_arm_front_page === TRUE || $force_enqueue == TRUE){
                wp_register_script('arm_mollie_front_js', ARM_MOLLIE_URL . '/js/arm_front_mollie.js', array(), $arm_mollie_version);
                wp_enqueue_script('arm_mollie_front_js');
            }
        }
    }

    function arm_enqueue_mollie_js_css_for_model(){
        $this->arm_mollie_set_front_js(true);
    }
    
    function upgrade_data_mollie() {
            global $armnew_mollie_version;
    
            if (!isset($armnew_mollie_version) || $armnew_mollie_version == "")
                $armnew_mollie_version = get_option('arm_mollie_version');
    
            if (version_compare($armnew_mollie_version, '2.3', '<')) {
                $path = ARM_MOLLIE_DIR . '/upgrade_latest_data_mollie.php';
                include($path);
            }
        }
        
    function arm_mollie_load_textdomain() {
        load_plugin_textdomain(ARM_MOLLIE_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public static function arm_mollie_db_check() {
        global $arm_mollie; 
        $arm_mollie_version = get_option('arm_mollie_version');

        if (!isset($arm_mollie_version) || $arm_mollie_version == '')
            $arm_mollie->install();
    }
    
    public static function install() {
        global $arm_mollie;
        $arm_mollie_version = get_option('arm_mollie_version');

        if (!isset($arm_mollie_version) || $arm_mollie_version == '') {

            global $wpdb, $arm_mollie_version;

            update_option('arm_mollie_version', $arm_mollie_version);
        }
    }
    
    /*
     * Restrict Network Activation
     */
    public static function arm_mollie_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }
    public static function uninstall() {
        delete_option('arm_mollie_version');
    }
   
    function arm_mollie_currency_symbol() {
        $currency_symbol = array(
            'EUR' => '&#128;',
        );
        return $currency_symbol;
    }
    
    function armmollie_getapiurl() {
            $api_url = 'https://www.arpluginshop.com/';
            return $api_url;
        }
    
    function arm_add_mollie_payment_gateways($default_payment_gateways) {
        if ($this->is_version_compatible()){
            global $arm_payment_gateways;
            $default_payment_gateways['mollie']['gateway_name'] = __('Mollie', ARM_MOLLIE_TEXTDOMAIN);
            return $default_payment_gateways;
        }
        else
        {
            return $default_payment_gateways;
        }
    }
    
    function arm_mollie_admin_notices(){
        global $pagenow, $arm_slugs;    
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if(!$this->is_armember_support())
                echo "<div class='updated updated_notices'><p>" . __('Mollie For ARMember plugin requires ARMember Plugin installed and active.', ARM_MOLLIE_TEXTDOMAIN) . "</p></div>";

            else if (!$this->is_version_compatible())
                echo "<div class='updated updated_notices'><p>" . __('Mollie For ARMember plugin requires ARMember plugin installed with version 3.0 or higher.', ARM_MOLLIE_TEXTDOMAIN) . "</p></div>";
        }
    }
    
    function is_armember_support() {

        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        return is_plugin_active('armember/armember.php');
    }
    
    function get_armember_version(){
        $arm_db_version = get_option('arm_version');
        
        return (isset($arm_db_version)) ? $arm_db_version : 0;
    }
    
    function is_version_compatible(){
        if (!version_compare($this->get_armember_version(), '3.0', '>=') || !$this->is_armember_support()) :
            return false;
        else : 
            return true;
        endif;
    }
    
    function arm_change_payment_gateway_tooltip_func($titleTooltip, $gateway_name, $gateway_options) {
        if ($gateway_name == 'mollie') {
            return " You can find API Key in your mollie account. To get more details, Please refer this <a href='https://www.mollie.com/en/docs/authentication'>document</a>.";
        }
        return $titleTooltip;
    }
    
    function arm_filter_gateway_names_func($pgname) {
        $pgname['mollie'] = __('Mollie', ARM_MOLLIE_TEXTDOMAIN);
        return $pgname;
    }
    
    function arm_payment_allowed_gateways($allowed_gateways, $plan_obj, $plan_options) {
//        if ($plan_obj->payment_type == 'subscription') {
//            if ($plan_obj->plan_detail->arm_subscription_plan_options['trial']['amount'] > 0) {
//                $allowed_gateways['mollie'] = "0";
//            } else {
//                $allowed_gateways['mollie'] = "1";
//            }
//        } else {
//            $allowed_gateways['mollie'] = "1";
//        }
        $allowed_gateways['mollie'] = "1";
        return $allowed_gateways;
    }
    
    function admin_enqueue_script(){
        $arm_mollie_page_array = array('arm_general_settings', 'arm_membership_setup');
        $arm_mollie_action_array = array('payment_options', 'new_setup', 'edit_setup');
        if ($this->is_version_compatible() && isset($_REQUEST['page']) && isset($_REQUEST['action'])){
            if(in_array($_REQUEST['page'], $arm_mollie_page_array) && in_array($_REQUEST['action'], $arm_mollie_action_array))
            {
                global $arm_mollie_version;
                wp_register_script( 'arm-admin-mollie', ARM_MOLLIE_URL . '/js/arm_admin_mollie.js', array(), $arm_mollie_version );
                wp_enqueue_script( 'arm-admin-mollie' );
            }
        }
    }
    
    function arm_after_payment_gateway_listing_section_func($gateway_name, $gateway_options){
        global $arm_global_settings;
        if ($gateway_name == 'mollie') {            
            $gateway_options['mollie_payment_mode'] = (!empty($gateway_options['mollie_payment_mode']) ) ? $gateway_options['mollie_payment_mode'] : 'sandbox';
            $gateway_options['status'] = isset($gateway_options['status']) ? $gateway_options['status'] : 0;
            $disabled_field_attr = ($gateway_options['status'] == '1') ? '' : 'disabled="disabled"';
            $readonly_field_attr = ($gateway_options['status'] == '1') ? '' : 'readonly="readonly"';
            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Payment Mode', ARM_MOLLIE_TEXTDOMAIN); ?> *</label></th>
                <td class="arm-form-table-content">
                    <input id="arm_mollie_payment_gateway_mode_sand" class="arm_general_input arm_mollie_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="sandbox" name="payment_gateway_settings[mollie][mollie_payment_mode]" <?php checked($gateway_options['mollie_payment_mode'], 'sandbox'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_mollie_payment_gateway_mode_sand"><?php _e('Sandbox', ARM_MOLLIE_TEXTDOMAIN); ?></label>
                    <input id="arm_mollie_payment_gateway_mode_pro" class="arm_general_input arm_mollie_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="live" name="payment_gateway_settings[mollie][mollie_payment_mode]" <?php checked($gateway_options['mollie_payment_mode'], 'live'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_mollie_payment_gateway_mode_pro"><?php _e('Live', ARM_MOLLIE_TEXTDOMAIN); ?></label>
                </td>
            </tr>
            <!-- ***** Begining of Sandbox Input for mollie ***** -->
            <?php
            $mollie_hidden = "hidden_section";
            if (isset($gateway_options['mollie_payment_mode']) && $gateway_options['mollie_payment_mode'] == 'sandbox') {
                $mollie_hidden = "";
            }else if(!isset($gateway_options['mollie_payment_mode'])){
                $mollie_hidden = "";
            }
            ?>
            <tr class="form-field arm_mollie_sandbox_fields <?php echo $mollie_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Sandbox API Key', ARM_MOLLIE_TEXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_mollie_sandbox_api_key" name="payment_gateway_settings[mollie][mollie_sandbox_api_key]" value="<?php echo (!empty($gateway_options['mollie_sandbox_api_key'])) ? $gateway_options['mollie_sandbox_api_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
           
            <!-- ***** Ending of Sandbox Input for mollie ***** -->

            <!-- ***** Begining of Live Input for mollie ***** -->
            <?php
            $mollie_live_fields = "hidden_section";
            if (isset($gateway_options['mollie_payment_mode']) && $gateway_options['mollie_payment_mode'] == "live") {
                $mollie_live_fields = "";
            }
            ?>
            <tr class="form-field arm_mollie_fields <?php echo $mollie_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live API Key', ARM_MOLLIE_TEXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_mollie_live_api_key" name="payment_gateway_settings[mollie][mollie_api_key]" value="<?php echo (!empty($gateway_options['mollie_api_key'])) ? $gateway_options['mollie_api_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
           
            <!-- ***** Ending of Live Input for mollie ***** -->
            <?php
            if(version_compare($this->get_armember_version(), '2.0', '>=')) { 
                ?>
                <tr class="form-field">
                    <th class="arm-form-table-label"><label><?php _e('Language', ARM_MOLLIE_TEXTDOMAIN);?></label></th>
                    <td class="arm-form-table-content">
                        <?php $arm_mollie_language = $this->arm_mollie_language(); ?>
                        <input type='hidden' id='arm_mollie_language' name="payment_gateway_settings[mollie][language]" value="<?php echo (!empty($gateway_options['language'])) ? $gateway_options['language'] : 'en';?>" />
                        <dl class="arm_selectbox arm_active_payment_<?php echo strtolower($gateway_name);?>" <?php echo $disabled_field_attr; ?>>
                            <dt <?php echo ($gateway_options['status']=='1') ? '' : 'style="border:1px solid #DBE1E8"'; ?>><span></span><input type="text" style="display:none;" value="<?php _e('English ( en )', ARM_MOLLIE_TEXTDOMAIN); ?>" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                            <dd>
                                <ul data-id="arm_mollie_language">
                                    <?php foreach ($arm_mollie_language as $key => $value): ?>
                                    <li data-label="<?php echo $value . " ( $key ) ";?>" data-value="<?php echo esc_attr($key);?>"><?php echo $value . " ( $key ) ";?></li>
                                    <?php endforeach;?>
                                </ul>
                            </dd>
                        </dl>
                    </td>
                </tr>
                <?php
            }
            
        }
    }
    
    function arm_mollie_language(){
        $currency_symbol = array(
            'be' => __('Belarusian', ARM_MOLLIE_TEXTDOMAIN),
            'be-fr' => __('Belgium / French)', ARM_MOLLIE_TEXTDOMAIN),
            'nl' => __('Dutch', ARM_MOLLIE_TEXTDOMAIN),
            'en' => __('English', ARM_MOLLIE_TEXTDOMAIN),
            'de' => __('German', ARM_MOLLIE_TEXTDOMAIN),
            'fr' => __('French', ARM_MOLLIE_TEXTDOMAIN),
            'es' => __('Spanish', ARM_MOLLIE_TEXTDOMAIN),
        );
        return $currency_symbol;
    }
    
    
    function arm_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {


        
        global $wpdb, $mollie, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $arm_membership_setup, $is_free_manual;
        
        $is_free_manual = false;
        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        $currency = $arm_payment_gateways->arm_get_global_currency();
        if ($payment_gateway == 'mollie' && isset($all_payment_gateways['mollie']) && !empty($all_payment_gateways['mollie'])) {
            $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
            $current_payment_gateway = (isset($posted_data['payment_gateway'])) ? $posted_data['payment_gateway'] : '';
            if ($current_payment_gateway == '') {
                $current_payment_gateway = (isset($posted_data['_payment_gateway'])) ? $posted_data['_payment_gateway'] : '';
            }

            if (!empty($entry_data) && $current_payment_gateway == $payment_gateway) {
                
                $plan_id = (!empty($posted_data['subscription_plan'])) ? $posted_data['subscription_plan'] : 0;
                if($plan_id == 0)
                {
                    $plan_id = (!empty($posted_data['_subscription_plan'])) ? $posted_data['_subscription_plan'] : 0;
                }
                $plan_action = 'new_subscription';
                if (isset($posted_data['old_plan_id']) && !empty($posted_data['old_plan_id']) && $posted_data['old_plan_id'] != 0) {
                    if ($posted_data['old_plan_id'] == $plan_id) {
                        $plan_action = 'renew_subscription';
                    }
                }
                
                $user_email_add = $entry_data['arm_entry_email'];
                $form_id = $entry_data['arm_form_id'];
                $user_id = $entry_data['arm_user_id'];
                $user_name = '';
                if (is_user_logged_in()) {
                    $user_obj = get_user_by( 'ID', $user_id);
                    $user_name = $user_obj->first_name." ".$user_obj->last_name;
                    $user_email_add = $user_obj->user_email;
                }else { 
                    $user_name = $entry_data['arm_entry_value']['first_name']." ".$entry_data['arm_entry_value']['last_name'];
                }
                

                $plan = new ARM_Plan($plan_id);
                $plan_payment_type = $plan->payment_type;
                $is_recurring = $plan->is_recurring();
                $plan_name = !empty($plan->name) ? $plan->name : "Plan Name";
                $amount = !empty($plan->amount) ? $plan->amount : "0";

                $current_payment_gateway = (isset($posted_data['payment_gateway'])) ? $posted_data['payment_gateway'] : '';
                if ($current_payment_gateway == '') {
                    $current_payment_gateway = (isset($posted_data['_payment_gateway'])) ? $posted_data['_payment_gateway'] : '';
                }
                $discount_amt = $amount;
                        
                $coupon_amount = 0;
                $arm_is_trial = 0;
                $is_trial = false;
                $allow_trial = true;
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $user_plan = get_user_meta($user_id, 'arm_user_plan', true);
                    $user_plan_id = $user_plan;
                    if ($user_plan == $plan->ID) {
                        $allow_trial = false;
                    }
                    
                }
                
                if ($plan->has_trial_period() && $allow_trial) {
                    $is_trial = true;
                    $arm_is_trial = '1';
                    $discount_amt = $plan->options['trial']['amount'];
                    $trial_period = isset($plan->options['trial']['period']) ? $plan->options['trial']['period'] : '';
                    $trial_interval = isset($plan->options['trial']['interval']) ? $plan->options['trial']['period'] : '';
                }
                
                
                if ($arm_manage_coupons->isCouponFeature && !empty($posted_data['arm_coupon_code'])) {
                    $couponApply = $arm_manage_coupons->arm_apply_coupon_code($posted_data['arm_coupon_code'], $plan);
                    $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                    $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                    $extraParam['coupon'] = array(
                        'coupon_code' => $posted_data['arm_coupon_code'],
                        'amount' => $coupon_amount,
                    );
                }
              
                
                $payment_data = array();
                
                $payment_mode = $payment_gateway_options['mollie_payment_mode'];
                $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;
                $this->ARM_Mollie_API_KEY = ( $is_sandbox_mode ) ? $payment_gateway_options['mollie_sandbox_api_key'] : $payment_gateway_options['mollie_api_key'];
                
                $arm_mollie_webhookurl = '';
                $arm_mollie_webhookurl = $arm_global_settings->add_query_arg("arm-listener", "arm_mollie_wh_api", get_home_url() . "/");
                
                
                $mollie->setApiKey($this->ARM_Mollie_API_KEY);
                
                //get customer id
                $arm_mollie_customer_id = '';
                if($plan_action == 'renew_subscription' || isset($posted_data['old_plan_id'])){
                    $arm_entry_tbl = $ARMember->tbl_arm_entries;
                    $arm_entry_data = $wpdb->get_row($wpdb->prepare("SELECT arm_user_id, arm_entry_email FROM `{$arm_entry_tbl}` WHERE `arm_entry_id` = %s ", $entry_data['arm_entry_id']));
                    
                    $arm_payment_log_tbl = $ARMember->tbl_arm_payment_log;
                    $arm_entry_data = $wpdb->get_row($wpdb->prepare("SELECT arm_token FROM `{$arm_payment_log_tbl}` WHERE `arm_user_id` = %s AND `arm_payer_email` = %s AND `arm_payment_gateway` = %s", $arm_entry_data->arm_user_id , $arm_entry_data->arm_entry_email, 'mollie'));

                    if(isset($arm_entry_data->arm_token) && $arm_entry_data->arm_token != '-')
                    {
                        $arm_mollie_customer = $mollie->customers->get($arm_entry_data->arm_token);

                        $arm_mollie_customer_id = isset($arm_mollie_customer->id) ? $arm_mollie_customer->id : '';
                    }
                }
                
                if(!isset($arm_mollie_customer_id) || $arm_mollie_customer_id == ''){
                    $arm_mollie_customer = $mollie->customers->create(array(
                        "name"     => $user_name,
                        "email"    => $user_email_add,
                    ));
                    $arm_mollie_customer_id = $arm_mollie_customer->id;
                }
                
                $setup_id = $posted_data['setup_id'];
                $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                $redirect_page_id = $setup_data['arm_setup_modules']['redirect_page'];
                $arm_mollie_redirecturl = get_permalink($redirect_page_id);
                
                $payment_mode_ = !empty($posted_data['arm_payment_mode']['mollie']) ? $posted_data['arm_payment_mode']['mollie'] : 'both';
               
                $recurring_payment_mode = 'manual_subscription';
                if( $payment_mode_ == 'both'){
                    $recurring_payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : 'manual_subscription';
                } else {
                    $recurring_payment_mode = $payment_mode_;
                }
                
                if(($discount_amt <= 0 || $discount_amt == '0.00') && $recurring_payment_mode == 'auto_debit_subscription')
                {
                    $discount_amt = 0.10;
//                    $err_msg = '<div class="arm_error_msg"><ul><li>' . __('Payment through mollie is not supported using auto debit payment for selected plan.', ARM_PAGSEGURO_ONSITE_TEXTDOMAIN) . '</li></ul></div>';
//                    $return = array('status' => 'error', 'type' => 'message', 'message' => $err_msg);
//                    echo json_encode($return);
//                    die;
                }


                
                if(($discount_amt <= 0 || $discount_amt == '0.00') && $recurring_payment_mode == 'manual_subscription')
                {
                    //$this->arm_after_payment_process($entry_data, $user_email_add, $plan_id, $discount_amt, '-', $plan_payment_type, $arm_mollie_customer_id, 'success', $entry_id, current_time('mysql'));
                    //$redirect = '<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="' . $arm_mollie_redirecturl . '";</script>';
                    //$return = array('status' => 'success', 'type' => 'redirect', 'message' => $redirect);
                    
                    global $payment_done;
                    $mollie_response = array();
                    $current_user_id = 0;
                    if (is_user_logged_in()) {
                        $current_user_id = get_current_user_id();
                        $mollie_response['arm_user_id'] = $current_user_id;
                    }
                    $arm_first_name=(isset($posted_data['first_name']))?$posted_data['first_name']:'';
                    $arm_last_name=(isset($posted_data['last_name']))?$posted_data['last_name']:'';
                    if(!empty($user_id)){
                        if(empty($arm_first_name)){
                            $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                            $arm_first_name=$user_detail_first_name;
                        }
                        if(empty($arm_last_name)){
                            $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                            $arm_last_name=$user_detail_last_name;
                        }    
                    }
                    $mollie_response['arm_plan_id'] = $plan->ID;
                    $mollie_response['arm_first_name']=$arm_first_name;
                    $mollie_response['arm_last_name']=$arm_last_name;
                    $mollie_response['arm_payment_gateway'] = 'mollie';
                    $mollie_response['arm_payment_type'] = $plan->payment_type;
                    $mollie_response['arm_token'] = '-';
                    $mollie_response['arm_payer_email'] = $user_email_add;
                    $mollie_response['arm_receiver_email'] = '';
                    $mollie_response['arm_transaction_id'] = '-';
                    $mollie_response['arm_transaction_payment_type'] = $plan->payment_type;
                    $mollie_response['arm_transaction_status'] = 'completed';
                    $mollie_response['arm_payment_mode'] = 'manual_subscription';
                    $mollie_response['arm_payment_date'] = date('Y-m-d H:i:s');
                    $mollie_response['arm_amount'] = 0;
                    $mollie_response['arm_currency'] = $currency;
                    $mollie_response['arm_coupon_code'] = $posted_data['arm_coupon_code'];
                    $mollie_response['arm_response_text'] = '';
                    $mollie_response['arm_extra_vars'] = '';
                    $mollie_response['arm_is_trial'] = $arm_is_trial;
                    $mollie_response['arm_created_date'] = current_time('mysql');
                    
                    $payment_log_id = $arm_payment_gateways->arm_save_payment_log($mollie_response);
                    //$ARMember->arm_write_response("Mollie id " . $payment_log_id. " " .$entry_id);
                    $return = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                    $payment_done = $return;
                    $is_free_manual = true;
                    return $return;
                }
                
                
                //customer payment
                if($is_recurring && $recurring_payment_mode == 'auto_debit_subscription')
                {
                    $arm_mollie_payment = $mollie->payments->create(array(
                        'amount'        => $discount_amt,       // 1 cent or higher
                        'customerId'    => $arm_mollie_customer_id,
                        'recurringType' => 'first',       // important
                        'description'   => $plan_name,
                        'redirectUrl'   => $arm_mollie_redirecturl,
                        'webhookUrl'   => $arm_mollie_webhookurl,
                        'metadata'      => array(
                            'plan_id'           => $plan_id,
                            'entry_id'          => $entry_id,
                            'user_email'        => $user_email_add,
                            'form_id'           => $form_id,
                            'plan_payment_type' => $plan_payment_type,
                            'cst_id'            => $arm_mollie_customer_id
                        ),
                        'locale'        => 'en_US'
                    ));
                }
                else
                {    
                    $arm_mollie_payment = $mollie->payments->create(array(
                        'amount'       => $discount_amt,
                        'description'  => $plan_name,
                        'customerId'   => $arm_mollie_customer_id,
                        'redirectUrl'  => $arm_mollie_redirecturl,
                        'webhookUrl'   => $arm_mollie_webhookurl,
                        'metadata'     => array(
                            'plan_id'           => $plan_id,
                            'entry_id'          => $entry_id,
                            'user_email'        => $user_email_add,
                            'form_id'           => $form_id,
                            'plan_payment_type' => $plan_payment_type,
                            'cst_id'            => $arm_mollie_customer_id
                        ),
                        'locale'       => 'en_US'
                    ));
                } 

                
                //$ARMember->arm_write_response(maybe_serialize($arm_mollie_payment));
                
                if (!isset($arm_mollie_payment->error)) {
                    $redirect='<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="'.$arm_mollie_payment->links->paymentUrl.'";</script>';
                    $return = array('status' => 'success', 'type' => 'redirect', 'message' => $redirect);
                    echo json_encode($return);
                    die;
                } else {
                    $err_msg = $arm_global_settings->common_message['arm_payment_fail_mollie'];
                    $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Mollie', ARM_MOLLIE_TEXTDOMAIN);
                    return array('status' => FALSE, 'error' => $err_msg);
                }
            } else {

            }
        } else {

        }        
    }
    
    function arm2_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {
        
        global $wpdb, $mollie, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $arm_membership_setup, $is_free_manual;
        
        $is_free_manual = false;
        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        $currency = $arm_payment_gateways->arm_get_global_currency();
        if ($payment_gateway == 'mollie' && isset($all_payment_gateways['mollie']) && !empty($all_payment_gateways['mollie'])) {
            $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
            $current_payment_gateway = (isset($posted_data['payment_gateway'])) ? $posted_data['payment_gateway'] : '';
            if ($current_payment_gateway == '') {
                $current_payment_gateway = (isset($posted_data['_payment_gateway'])) ? $posted_data['_payment_gateway'] : '';
            }

            if (!empty($entry_data) && $current_payment_gateway == $payment_gateway) {
                
                $user_email_add = $entry_data['arm_entry_email'];
                $form_id = $entry_data['arm_form_id'];
                $user_id = $entry_data['arm_user_id'];
                $entry_values = maybe_unserialize($entry_data['arm_entry_value']);
                $payment_cycle = $entry_values['arm_selected_payment_cycle']; 
                $tax_percentage = (isset($entry_values['tax_percentage']) && $entry_values['tax_percentage']!='') ? $entry_values['tax_percentage'] : 0;

                $ARMember->arm_write_response("mollie tax : ". $tax_percentage);
                $arm_user_old_plan = (isset($entry_values['arm_user_old_plan']) && !empty($entry_values['arm_user_old_plan'])) ? explode(",",$entry_values['arm_user_old_plan']) : array();
                $setup_id = (isset($entry_values['setup_id']) && !empty($entry_values['setup_id'])) ? $entry_values['setup_id'] : 0 ;
                
                $user_name = '';
                if (is_user_logged_in()) {
                    $user_obj = get_user_by( 'ID', $user_id);
                    $user_name = $user_obj->first_name." ".$user_obj->last_name;
                    $user_email_add = $user_obj->user_email;
                }else { 
                    $user_name = $entry_values['first_name']." ".$entry_values['last_name'];
                }
                
                $plan_id = (!empty($posted_data['subscription_plan'])) ? $posted_data['subscription_plan'] : 0;
                if ($plan_id == 0) {
                    $plan_id = (!empty($posted_data['_subscription_plan'])) ? $posted_data['_subscription_plan'] : 0;
                }
                
                $plan = new ARM_Plan($plan_id);
                
                $payment_mode_ = !empty($posted_data['arm_payment_mode']['mollie']) ? $posted_data['arm_payment_mode']['mollie'] : 'both';
               
                $recurring_payment_mode = 'manual_subscription';
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
                    if( $payment_mode_ == 'both'){
                        $recurring_payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : 'manual_subscription';
                    } else {
                        $recurring_payment_mode = $payment_mode_;
                    }
                }
                
                $plan_action = 'new_subscription';
                $oldPlanIdArray = (isset($posted_data['old_plan_id']) && !empty($posted_data['old_plan_id'])) ? explode(",", $posted_data['old_plan_id']) : 0;
                if (!empty($oldPlanIdArray)) {
                    if (in_array($plan_id, $oldPlanIdArray)) {
                        $plan_action = 'renew_subscription';
                        $is_recurring_payment = $arm_subscription_plans->arm_is_recurring_payment_of_user($user_id, $plan_id, $recurring_payment_mode);
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
                
                $plan_payment_type = $plan->payment_type;
                $is_recurring = $plan->is_recurring();
                $plan_name = !empty($plan->name) ? $plan->name : "Plan Name";
                $amount = !empty($plan->amount) ? $plan->amount : "0";

                if($plan->is_recurring()) {
                    $recurring_data = $plan->prepare_recurring_data($payment_cycle);
                    $amount = $recurring_data['amount'];
                } else {
                    $amount = !empty($plan->amount) ? $plan->amount : 0;
                }
                $amount = str_replace(',','',$amount);
                $discount_amt = $amount;

                $ARMember->arm_write_response("mollie discopunt amount 1 ".$discount_amt);
                        
                $coupon_amount = 0;
                $arm_is_trial = 0;
                $is_trial = false;
                $allow_trial = true;
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $user_plan = get_user_meta($user_id, 'arm_user_plan_ids', true);
                    $user_plan_id = $user_plan;
                    if (in_array($plan->ID, $user_plan)) {
                        $allow_trial = false;
                    }
                    
                }

                
                if($plan->is_recurring()) {
                    if ($plan->has_trial_period() && $allow_trial) {
                        $is_trial = true;
                        $arm_is_trial = '1';
                        $discount_amt = $plan->options['trial']['amount'];
                        $trial_period = isset($plan->options['trial']['period']) ? $plan->options['trial']['period'] : '';
                        $trial_interval = isset($plan->options['trial']['interval']) ? $plan->options['trial']['period'] : '';
                    }
                }               
                
                $coupon_amount = $arm_coupon_discount = 0;
                $arm_coupon_discount_type = '';
                $extraFirstParam = array();
                if ($arm_manage_coupons->isCouponFeature && isset($posted_data['arm_coupon_code']) && !empty($posted_data['arm_coupon_code'])) {
                    $couponApply = $arm_manage_coupons->arm_apply_coupon_code($posted_data['arm_coupon_code'], $plan, $setup_id, $payment_cycle, $arm_user_old_plan);
                    $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                    $coupon_amount = str_replace(',','',$coupon_amount);

                    $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $discount_amt;
                    $discount_amt = str_replace(',','',$discount_amt);

                    $arm_coupon_discount = $couponApply['discount'];
                    $global_currency = $arm_payment_gateways->arm_get_global_currency();
                    $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $global_currency : "%";
                    if (!empty($coupon_amount) && $coupon_amount > 0) {
                        $extraFirstParam['coupon'] = array(
                            'coupon_code' => $posted_data['arm_coupon_code'],
                            'amount' => $coupon_amount,
                        );
                    }
                } else {
                    $posted_data['arm_coupon_code'] = '';
                }
                
                $payment_data = array();
                
                $payment_mode = $payment_gateway_options['mollie_payment_mode'];
                $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;
                $this->ARM_Mollie_API_KEY = ( $is_sandbox_mode ) ? $payment_gateway_options['mollie_sandbox_api_key'] : $payment_gateway_options['mollie_api_key'];
                $arm_mollie_language = isset($payment_gateway_options['language']) ? $payment_gateway_options['language'] : 'en';
                
                $arm_mollie_webhookurl = '';
                $arm_mollie_webhookurl = $arm_global_settings->add_query_arg("arm-listener", "arm_mollie_wh_api", get_home_url() . "/");
                
                
                $mollie->setApiKey($this->ARM_Mollie_API_KEY);

                //get customer id
                $arm_mollie_customer_id = '';
                if($plan_action == 'renew_subscription' || isset($posted_data['old_plan_id'])){
                    $arm_entry_tbl = $ARMember->tbl_arm_entries;
                    $arm_entry_data = $wpdb->get_row($wpdb->prepare("SELECT arm_user_id, arm_entry_email FROM `{$arm_entry_tbl}` WHERE `arm_entry_id` = %s ", $entry_data['arm_entry_id']));
                    
                    $arm_payment_log_tbl = $ARMember->tbl_arm_payment_log;
                    $arm_entry_data = $wpdb->get_row($wpdb->prepare("SELECT arm_token FROM `{$arm_payment_log_tbl}` WHERE `arm_user_id` = %s AND `arm_payer_email` = %s AND `arm_payment_gateway` = %s", $arm_entry_data->arm_user_id , $arm_entry_data->arm_entry_email, 'mollie'));
                    
                    if(isset($arm_entry_data->arm_token) && $arm_entry_data->arm_token != '-')
                    {
                        $arm_mollie_customer = $mollie->customers->get($arm_entry_data->arm_token);
                        $arm_mollie_customer_id = isset($arm_mollie_customer->id) ? $arm_mollie_customer->id : '';
                    }
                }
                
                if(!isset($arm_mollie_customer_id) || $arm_mollie_customer_id == ''){
                    $arm_mollie_customer = $mollie->customers->create(array(
                        "name"     => $user_name,
                        "email"    => $user_email_add,
                    ));
                    $arm_mollie_customer_id = $arm_mollie_customer->id;
                }                
                
                $arm_mollie_redirecturl = $entry_values['setup_redirect'];
                if (empty($arm_mollie_redirecturl)) {
                    $arm_mollie_redirecturl = ARM_HOME_URL;
                }

                //$ARMember->arm_write_response("mollie arm_mollie_redirecturl  ". $arm_mollie_redirecturl );

                $tax_amount = 0;
                if($tax_percentage > 0){
                    $tax_amount = ($tax_percentage*$discount_amt)/100;
                    $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                    $ARMember->arm_write_response("mollie tax amount  ".$tax_amount);
                    $discount_amt = $discount_amt+$tax_amount;
                
                }

                //$ARMember->arm_write_response("mollie discopunt amount  ".$discount_amt);
                //$setup_id = $posted_data['setup_id'];
                //$setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                //$redirect_page_id = $setup_data['arm_setup_modules']['redirect_page'];
                //$arm_mollie_redirecturl = get_permalink($redirect_page_id);
                
                if(($discount_amt <= 0 || $discount_amt == '0.00') && $recurring_payment_mode == 'auto_debit_subscription')
                {
                    $discount_amt = 0.10;
                }

                if(($discount_amt <= 0 || $discount_amt == '0.00') && $recurring_payment_mode == 'manual_subscription')
                {
                    global $payment_done;
                    $mollie_response = array();
                    $current_user_id = 0;
                    if (is_user_logged_in()) {
                        $current_user_id = get_current_user_id();
                        $mollie_response['arm_user_id'] = $current_user_id;
                    }
                    $arm_first_name=(isset($posted_data['first_name']))?$posted_data['first_name']:'';
                    $arm_last_name=(isset($posted_data['last_name']))?$posted_data['last_name']:'';
                    if(!empty($user_id)){
                        if(empty($arm_first_name)){
                            $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                            $arm_first_name=$user_detail_first_name;
                        }
                        if(empty($arm_last_name)){
                            $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                            $arm_last_name=$user_detail_last_name;
                        }    
                    }
                    $mollie_response['arm_plan_id'] = $plan->ID;
                    $mollie_response['arm_first_name']=$arm_first_name;
                    $mollie_response['arm_last_name']=$arm_last_name;
                    $mollie_response['arm_payment_gateway'] = 'mollie';
                    $mollie_response['arm_payment_type'] = $plan->payment_type;
                    $mollie_response['arm_token'] = '-';
                    $mollie_response['arm_payer_email'] = $user_email_add;
                    $mollie_response['arm_receiver_email'] = '';
                    $mollie_response['arm_transaction_id'] = '-';
                    $mollie_response['arm_transaction_payment_type'] = $plan->payment_type;
                    $mollie_response['arm_transaction_status'] = 'success';
                    $mollie_response['arm_payment_mode'] = 'manual_subscription';
                    $mollie_response['arm_payment_date'] = date('Y-m-d H:i:s');
                    $mollie_response['arm_amount'] = 0;
                    $mollie_response['arm_currency'] = $currency;
                    $mollie_response['arm_coupon_code'] = $posted_data['arm_coupon_code'];
                    $mollie_response['arm_coupon_discount'] = $arm_coupon_discount;
                    $mollie_response['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                    $mollie_response['arm_response_text'] = '';
                    $mollie_response['arm_extra_vars'] = array( 'payment_type'=> 'mollie', 'payment_mode' => $payment_mode );
                    $mollie_response['arm_is_trial'] = $arm_is_trial;
                    $mollie_response['arm_created_date'] = current_time('mysql');
                    
                    $payment_log_id = $arm_payment_gateways->arm_save_payment_log($mollie_response);
                    $return = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                    $payment_done = $return;
                    $is_free_manual = true;
                    do_action('arm_after_mollie_free_payment',$plan,$payment_log_id,$arm_is_trial,$posted_data['arm_coupon_code'],$extraFirstParam);
                    return $return;
                }
                
                //customer payment
                if($is_recurring && $recurring_payment_mode == 'auto_debit_subscription')
                {
                    try{
                        $arm_mollie_payment = $mollie->payments->create(array(
                            'amount'        => $discount_amt,       // 1 cent or higher
                            'customerId'    => $arm_mollie_customer_id,
                            'recurringType' => 'first',       // important
                            'description'   => $plan_name,
                            'redirectUrl'   => $arm_mollie_redirecturl,
                            'webhookUrl'   => $arm_mollie_webhookurl,
                            'metadata'      => array(
                                'plan_id'           => $plan_id,
                                'entry_id'          => $entry_id,
                                'user_email'        => $user_email_add,
                                'form_id'           => $form_id,
                                'plan_payment_type' => $plan_payment_type,
                                'cst_id'            => $arm_mollie_customer_id
                            ),
                            'locale'        => $arm_mollie_language
                        ));

                    } 
                    catch (Exception $e) {
                        $actual_message = $e->getMessage();

                        $arm_mollie_payment->error = "1";
                        $arm_mollie_payment->message = $actual_message;
                        $ARMember->arm_write_response("mollie error1 ".maybe_serialize($actual_message));
                    }                    
                }
                else
                {
                    //$ARMember->arm_write_response("mollie tax percentage ".$tax_percentage);
                    //$ARMember->arm_write_response("mollie amount ".$discount_amt);
                    try{
                        $arm_mollie_payment = $mollie->payments->create(array(
                            'amount'       => $discount_amt,
                            'description'  => $plan_name,
                            'customerId'   => $arm_mollie_customer_id,
                            'redirectUrl'  => $arm_mollie_redirecturl,
                            'webhookUrl'   => $arm_mollie_webhookurl,
                            'metadata'     => array(
                                'plan_id'           => $plan_id,
                                'entry_id'          => $entry_id,
                                'user_email'        => $user_email_add,
                                'form_id'           => $form_id,
                                'plan_payment_type' => $plan_payment_type,
                                'cst_id'            => $arm_mollie_customer_id
                            ),
                            'locale'       => $arm_mollie_language
                        ));
                    } 
                    catch (Exception $e) {
                        $actual_message = $e->getMessage();

                        $arm_mollie_payment->error = "1";
                        $arm_mollie_payment->message = $actual_message;
                        $ARMember->arm_write_response("mollie error2 ".maybe_serialize($actual_message));
                    }                    
                }
                
                //$ARMember->arm_write_response(maybe_serialize($arm_mollie_payment));
                
                if (empty($arm_mollie_payment->error))
                {
                    if($plan_action=='recurring_payment')
                    {
                        $user_subsdata = get_user_meta($user_id, 'arm_mollie_' . $plan_id, true);
                        $payment_mode = get_user_meta($user_id,'arm_selected_payment_mode',true);
                        do_action('arm_after_recurring_payment_success_outside',$user_id,$plan->ID,'molie',$payment_mode,$user_subsdata);
                    }
                    $redirect='<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="'.$arm_mollie_payment->links->paymentUrl.'";</script>';
                    $return = array('status' => 'success', 'type' => 'redirect', 'message' => $redirect);
                    echo json_encode($return);
                    die;
                } else {
                    $err_msg = isset($arm_global_settings->common_message['arm_payment_fail_mollie']) ? $arm_global_settings->common_message['arm_payment_fail_mollie'] : '';
                    $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Mollie', ARM_MOLLIE_TEXTDOMAIN);
                    

                    if(!empty($arm_mollie_payment->message))
                    {
                        $err_msg = $arm_mollie_payment->message;
                    }
                    $payment_done = array('status' => FALSE, 'error' => $err_msg);
                    
                    return $payment_done;
                }
            } else {

            }
        } else {

        }        
    }
    
    function armmollie_get_remote_post_params($plugin_info = "") {
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
                if (strpos(strtolower($plugin["Title"]), "armembermollie") !== false) {
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
    
    function arm_show_payment_gateway_mollie_recurring_notice($plan_details) {
        global $arm_payment_gateways;        
        $display = "display:none;";
        $currency = $arm_payment_gateways->arm_get_global_currency();
        $plans = array();
        if (!empty($plan_details) && $this->is_version_compatible() ) {
            foreach ($plan_details as $plan_detail) {
                if (isset($plan_detail['arm_subscription_plan_options']) && @$plan_detail['arm_subscription_plan_options']['payment_type'] == 'subscription' && $plan_detail['arm_subscription_plan_options']['trial']['amount'] <= 0) {
                    $display = "";
                }
            }
        }
        ?>
        <span class="arm_invalid" id="arm_mollie_warning" style="<?php echo $display; ?>">
            <?php 
            _e("NOTE : In case of automatic subscription, if final payable amount will be zero, then also user need to pay minimum 0.10", ARM_MOLLIE_TEXTDOMAIN);
            echo " ".$currency." ";
            _e("amount to start subscription for mollie.", ARM_MOLLIE_TEXTDOMAIN); ?> 
        </span>
        <?php 
    }
    
    function arm_change_pending_gateway_outside($getway, $plan_ID, $user_id){
        $getway[] = 'mollie';        
        return $getway;
    }
    
    function arm_mollie_cancel_subscription($user_id, $plan_id){
        global $wpdb, $ARMember, $mollie, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
        
        if (isset($user_id) && $user_id != 0 && $user_id > 0 && isset($plan_id) && $plan_id != 0 && $plan_id > 0) { 
            $user_detail = get_userdata($user_id);
            $payer_email = $user_detail->user_email;
            $user_payment_gateway = get_user_meta($user_id, 'arm_using_gateway_' . $plan_id, true);
            if ($user_payment_gateway == 'mollie') {
                $payment_log_table = $ARMember->tbl_arm_payment_log;
                $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'mollie', 'completed'));
                if (!empty($transaction)) {
                    $extra_var = maybe_unserialize($transaction->arm_extra_vars);
                    $payer_email = $transaction->arm_payer_email;
                    $payment_type = $extra_var['payment_type'];
                    $payment_mode = $extra_var['payment_mode'];
                    $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;
                    
                    $gateway_options = get_option('arm_payment_gateway_settings');
                    $pgoptions = maybe_unserialize($gateway_options);
                    $pgoptions = $pgoptions['mollie'];
                    
                    if ($payment_type == 'mollie') {
                        
                        $ARM_Mollie_API_KEY = ( $is_sandbox_mode ) ? $pgoptions['mollie_sandbox_api_key'] : $pgoptions['mollie_api_key'];
                        $mollie->setApiKey($ARM_Mollie_API_KEY);

                        $get_subscriptions = $mollie->customers_subscriptions->withParentId($transaction->arm_token)->get($transaction->arm_transaction_id);
                        
                        //$ARMember->arm_write_response("Mollie Cancellation subscription for " . $transaction->arm_token . " Response => " . maybe_serialize($get_subscriptions));
                        
                        if($get_subscriptions->status != 'cancelled')
                        {
                            $cancel_subscription = $mollie->customers_subscriptions->withParentId($transaction->arm_token)->cancel($transaction->arm_transaction_id);
                            
                            //$ARMember->arm_write_response("Mollie Cancellation subscription for " . $transaction->arm_token . " Response => " . maybe_serialize($cancel_subscription));

                            if ($cancel_subscription->status == 'cancelled') {
                                $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'cancel_payment'));
                                $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                                $payment_data = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan_id,
                                    'arm_first_name'=> $user_detail_first_name,
                                    'arm_last_name'=> $user_detail_last_name,
                                    'arm_payment_gateway' => 'mollie',
                                    'arm_payment_type' => 'subscription',
                                    'arm_token' => $transaction->arm_token,
                                    'arm_payer_email' => $payer_email,
                                    'arm_receiver_email' => '',
                                    'arm_transaction_id' => $transaction->arm_transaction_id,
                                    'arm_transaction_payment_type' => $cancel_subscription->resource,
                                    'arm_transaction_status' => 'canceled',
                                    'arm_payment_date' => current_time('mysql'),
                                    'arm_amount' => 0,
                                    'arm_currency' => '',
                                    'arm_coupon_code' => '',
                                    'arm_response_text' => maybe_serialize($cancel_subscription),
                                    'arm_created_date' => current_time('mysql')
                                );
                                //$is_cancelled_by_system = get_user_meta($user_id, 'arm_payment_cancelled_by', true);
                                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
                                delete_user_meta($user_id, 'arm_payment_cancelled_by');
                                return;
                            }
                        }
                    }
                }
            }
        }
    }
    
    function arm2_mollie_cancel_subscription($user_id, $plan_id){
        global $wpdb, $ARMember, $mollie, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
        
        if (isset($user_id) && $user_id != 0 && $user_id > 0 && isset($plan_id) && $plan_id != 0 && $plan_id > 0) { 
            $user_detail = get_userdata($user_id);
            $payer_email = $user_detail->user_email;
            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
            $arm_user_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
            if ($arm_user_gateway == 'mollie') {
                $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                $payment_log_table = $ARMember->tbl_arm_payment_log;
                $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_amount FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'mollie', 'success'));
                $ARMember->arm_write_response("reptuelog transaction ".maybe_serialize($transaction));
                if (!empty($transaction)) {
                    $ARMember->arm_write_response("reptuelog transaction in transaction if");
                    $currency = $arm_payment_gateways->arm_get_global_currency();
                    $extra_var = maybe_unserialize($transaction->arm_extra_vars);
                    $payer_email = $transaction->arm_payer_email;
                    $payment_type = $extra_var['payment_type'];
                    $payment_mode = $extra_var['payment_mode'];
                    $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;
                    
                    $gateway_options = get_option('arm_payment_gateway_settings');
                    $pgoptions = maybe_unserialize($gateway_options);
                    $pgoptions = $pgoptions['mollie'];
                    
                    if ($payment_type == 'mollie') {
                        $ARMember->arm_write_response("reptuelog transaction in transaction if mollie ");
                        $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                        $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                        if($user_selected_payment_mode == 'auto_debit_subscription')
                        {
                            $ARMember->arm_write_response("reptuelog transaction in transaction if mollie auto debit ");
                            $ARM_Mollie_API_KEY = ( $is_sandbox_mode ) ? $pgoptions['mollie_sandbox_api_key'] : $pgoptions['mollie_api_key'];
                            $ARMember->arm_write_response("reptuelog transaction in transaction if mollie auto debit ".$ARM_Mollie_API_KEY);
                            $mollie->setApiKey($ARM_Mollie_API_KEY);
                            $ARMember->arm_write_response("reptuelog transaction in transaction if mollie mollie ".maybe_serialize($mollie));
                            try {
                                $get_subscriptions = $mollie->customers_subscriptions->withParentId($transaction->arm_token)->get($transaction->arm_transaction_id);
                            } catch (Exception $e) {                                  
                                
                            }
                            $ARMember->arm_write_response("Mollie get subscription for " . $transaction->arm_token . " Response => " . maybe_serialize($get_subscriptions));
                            
                            if($get_subscriptions->status != 'cancelled')
                            {
                                $cancel_subscription = '';
                                try {
                                    $cancel_subscription = $mollie->customers_subscriptions->withParentId($transaction->arm_token)->cancel($transaction->arm_transaction_id);
                                } catch (Exception $e) {                                  
                                    
                                }
                                $ARMember->arm_write_response("Mollie Cancellation subscription for " . $transaction->arm_token . " Response => " . maybe_serialize($cancel_subscription));
                                if (isset($cancel_subscription->status) && $cancel_subscription->status == 'cancelled') {
                                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'cancel_payment'));

                                    $payment_data = array(
                                        'arm_user_id' => $user_id,
                                        'arm_plan_id' => $plan_id,
                                        'arm_first_name'=> $user_detail_first_name,
                                        'arm_last_name'=> $user_detail_last_name,
                                        'arm_payment_gateway' => 'mollie',
                                        'arm_payment_type' => 'subscription',
                                        'arm_token' => $transaction->arm_token,
                                        'arm_payer_email' => $payer_email,
                                        'arm_receiver_email' => '',
                                        'arm_transaction_id' => $transaction->arm_transaction_id,
                                        'arm_transaction_payment_type' => $cancel_subscription->resource,
                                        'arm_transaction_status' => 'canceled',
                                        'arm_payment_date' => current_time('mysql'),
                                        'arm_amount' => $transaction->arm_amount,
                                        'arm_currency' => $currency,
                                        'arm_coupon_code' => '',
                                        'arm_response_text' => maybe_serialize($cancel_subscription),
                                        'arm_is_trial' => '0',
                                        'arm_created_date' => current_time('mysql')
                                    );
                                    //$is_cancelled_by_system = get_user_meta($user_id, 'arm_payment_cancelled_by', true);
                                    $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
                                    delete_user_meta($user_id, 'arm_payment_cancelled_by');
                                    return;
                                }
                            }
                        }
                        else
                        {
                            $ARMember->arm_write_response("reptuelog transaction in transaction if mollie manul ");
                            $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                            $payment_data = array(
                                'arm_user_id' => $user_id,
                                'arm_plan_id' => $plan_id,
                                'arm_first_name'=> $user_detail_first_name,
                                'arm_last_name'=> $user_detail_last_name,
                                'arm_payment_gateway' => 'mollie',
                                'arm_payment_type' => 'subscription',
                                'arm_payer_email' => $payer_email,
                                'arm_receiver_email' => '',
                                'arm_transaction_id' => $transaction->arm_transaction_id,
                                'arm_token' => '',
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
    

    
    function arm_mollie_currency_support($notAllow, $currency){
        global $arm_payment_gateways;
        if (!array_key_exists($currency, $arm_payment_gateways->currency['mollie'])) {
            $notAllow[] = 'mollie';
        }
        return $notAllow;
    }
    
    function arm_mollie_webhook(){
        if(( isset($_POST['id']) && $_POST['id'] != '' ) || ( isset($_POST['subscriptionId']) && $_POST['subscriptionId'] != '' ) ) 
        {
            global $wpdb, $mollie, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication, $arm_manage_coupons, $payment_done;
            if (isset($_REQUEST['arm-listener']) && in_array($_REQUEST['arm-listener'], array('arm_mollie_wh_api')))
            {
                $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
                if (isset($all_payment_gateways['mollie']) && !empty($all_payment_gateways['mollie'])) {
                    $options = $all_payment_gateways['mollie'];
                    $payment_mode = $options['mollie_payment_mode'];
                    $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;
                    $ARM_Mollie_API_KEY = ( $is_sandbox_mode ) ? $options['mollie_sandbox_api_key'] : $options['mollie_api_key'];
                    $mollie->setApiKey($ARM_Mollie_API_KEY);
                    
                    $transaction_id = $_POST['id'];
                    $payment = $mollie->payments->get($transaction_id);
                    
                    //$ARMember->arm_write_response("webhook Mollie payment for " . $payment->metadata->cst_id . " user ==> " . maybe_serialize($payment));
                    
                    if(isset($payment) && $payment->isPaid()){
                        
                        $entry_email = $payment->metadata->user_email;
                        $arm_log_plan_id = $payment->metadata->plan_id;
                        $arm_log_amount = $payment->amount;
                        $arm_token = $transaction_id;
                        $arm_payment_type = $payment->metadata->plan_payment_type;
                        $entry_id = $payment->metadata->entry_id;
                        $cst_id = $payment->metadata->cst_id;
                        $payment_status = $payment->status;
                        $payment_datetime = $payment->paidDatetime;
                        
                        $entry_data = $wpdb->get_row("SELECT `arm_entry_id`, `arm_entry_email`, `arm_entry_value`, `arm_form_id`, `arm_user_id`, `arm_plan_id` FROM `".$ARMember->tbl_arm_entries."` WHERE `arm_entry_id`='".$entry_id."' AND `arm_entry_email`='".$entry_email."'", ARRAY_A);
                    }
                    else if(isset($_POST['subscriptionId']) && $_POST['subscriptionId'] != '')
                    {
                        $transaction_id = $_POST['subscriptionId'];
                        $payment_log_data = $wpdb->get_row("SELECT `arm_log_id`, `arm_transaction_id`, `arm_plan_id`, `arm_payer_email`, `arm_amount`, `arm_token` FROM `".$ARMember->tbl_arm_payment_log."` WHERE `arm_transaction_id`='".$transaction_id."' OR `arm_token`='".$transaction_id."' ORDER BY arm_log_id DESC LIMIT 1", ARRAY_A);
                        
                        $entry_email = $payment_log_data['arm_payer_email'];
                        $arm_log_plan_id = $payment_log_data['arm_plan_id'];
                        $arm_log_amount = $payment_log_data['arm_amount'];
                        $arm_token = $transaction_id;                        
                        
                        $subscriptions = $mollie->customers_subscriptions->withParentId($arm_log_token)->get($transaction_id);
                        $arm_payment_type = $subscriptions->resource;
                        $cst_id = $subscriptions->customerId;
                        $payment_status = $subscriptions->status;
                        $payment_datetime = $payment->paidDatetime;
                        
                        if(isset($subscriptions) && $subscriptions->status == 'active')
                        {
                            $entry_data = $wpdb->get_row("SELECT `arm_entry_id`, `arm_entry_email`, `arm_entry_value`, `arm_form_id`, `arm_user_id`, `arm_plan_id` FROM `".$ARMember->tbl_arm_entries."` WHERE `arm_entry_email`='".$arm_log_email_id."' AND `arm_plan_id`='".$arm_log_plan_id."' ", ARRAY_A);
                            
                            $entry_id = $entry_data['arm_entry_id'];
                        }
                    }    
                        
                    $this->arm_after_payment_process($entry_data, $entry_email, $arm_log_plan_id, $arm_log_amount, $arm_token, $arm_payment_type, $cst_id, $payment_status, $entry_id, $payment_datetime);
                    
                    
                    if(isset($arm_payment_type) && $arm_payment_type == 'subscription' && isset($_POST['id']) && !isset($_POST['subscriptionId']))
                    {
                        $arm_mollie_cst = $cst_id;
                        $arm_mollie_trn = $transaction_id;
                        $plan_id = $arm_log_plan_id;

                        $arm_mollie_webhookurl = '';
                        $arm_mollie_webhookurl = $arm_global_settings->add_query_arg("arm-listener", "arm_mollie_wh_api", get_home_url() . "/");

                        $plan = new ARM_Plan($plan_id);
                        $is_recurring = $plan->is_recurring();
                        if($is_recurring)
                        {
                            $arm_mollie_mandates = $mollie->customers_mandates->withParentId($arm_mollie_cst)->all();

                            if(isset($arm_mollie_mandates->data[0]->id) && (isset($arm_mollie_mandates->data[0]->status) && ($arm_mollie_mandates->data[0]->status == 'pending' || $arm_mollie_mandates->data[0]->status == 'valid')))
                            {

                                $plan_name = !empty($plan->name) ? $plan->name : "Plan Name";
                                $amount = !empty($plan->amount) ? $plan->amount : "0";
                                $recurring_data = $plan->prepare_recurring_data();
                                $arm_mollie_recur_period = $recurring_data['period'];
                                switch ($arm_mollie_recur_period) {
                                    case 'M':
                                        $arm_mollie_payperiod = "months";
                                        break;
                                    case 'D':
                                        $arm_mollie_payperiod = "days";
                                        break;
                                    case 'W':
                                        $arm_mollie_payperiod = "weeks";
                                        break;
                                    case 'Y':
                                        $arm_mollie_payperiod = "years";
                                        break;
                                }

                                $arm_mollie_recur_interval = $recurring_data['interval']." ".$arm_mollie_payperiod;
                                $arm_mollie_recur_cycles = !empty($recurring_data['cycles']) ? ($recurring_data['cycles']-1) : '';
                                $arm_mollie_start_date = date('Y-m-d', strtotime('+' . $recurring_data['interval'] . ' ' . $arm_mollie_payperiod));
                                //$ARMember->arm_write_response("Mollie subscription ".$arm_mollie_start_date);

                                $arm_mollie_create_subscription_arr = array(
                                    "amount"      => $amount,
                                    "interval"    => $arm_mollie_recur_interval,
                                    "startDate"   => $arm_mollie_start_date,
                                    "description" => $plan_name,
                                    "webhookUrl"  => $arm_mollie_webhookurl
                                );
                                if(!empty($arm_mollie_recur_cycles))
                                {
                                    $arm_mollie_create_subscription_arr["times"] = $arm_mollie_recur_cycles;
                                }

                                $arm_mollie_subscription = $mollie->customers_subscriptions->withParentId($arm_mollie_cst)->create($arm_mollie_create_subscription_arr);

                                //$ARMember->arm_write_response("Mollie subscription for " . $arm_mollie_cst . " user ==> " . maybe_serialize($arm_mollie_subscription));

                                $mollielLog['token_id'] = $arm_mollie_subscription->id;
                                
                                $wpdb->update( $ARMember->tbl_arm_payment_log, 
                                    array( 'arm_transaction_id' => $arm_mollie_subscription->id, 'arm_transaction_status'=>'completed' ), 
                                    array( 'arm_token' => $arm_mollie_cst, 'arm_payment_type' => $plan->payment_type ), 
                                    array( '%s', '%s' ), 
                                    array( '%s', '%s', '%s' ) 
                                );
                            }
                        }
                    }
                }
            }
        }
    }
    
    function arm2_mollie_webhook(){
        if (isset($_REQUEST['arm-listener']) && in_array($_REQUEST['arm-listener'], array('arm_mollie_wh_api')))
        {
            global $wpdb, $mollie, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication, $arm_manage_coupons, $payment_done, $is_multiple_membership_feature, $arm_members_class, $arm_mollie;
            $arm_log_date = date("y-m-d H:i:s")." => ";
            $ARMember->arm_write_response($arm_log_date."mollie request parameter => ".maybe_serialize($_REQUEST), 'response_mollie.txt');
            if(( isset($_POST['id']) && $_POST['id'] != '' ) || ( isset($_POST['subscriptionId']) && $_POST['subscriptionId'] != '' ) ) 
            {
                $ARMember->arm_write_response($arm_log_date."mollie_log", 'response_mollie.txt');
                $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
                if (isset($all_payment_gateways['mollie']) && !empty($all_payment_gateways['mollie'])) {
                    $ARMember->arm_write_response($arm_log_date."mollie_log is active", 'response_mollie.txt');
                    $ARMember->arm_write_response($arm_log_date."mollie_log post parameter : ".maybe_serialize($_POST), 'response_mollie.txt');
                    $options = $all_payment_gateways['mollie'];
                    $mollie_payment_mode = $options['mollie_payment_mode'];
                    $is_sandbox_mode = $mollie_payment_mode == "sandbox" ? true : false;
                    $ARM_Mollie_API_KEY = ( $is_sandbox_mode ) ? $options['mollie_sandbox_api_key'] : $options['mollie_api_key'];
                    $mollie->setApiKey($ARM_Mollie_API_KEY);
                    
                    $transaction_id = $arm_transaction_id_unique = $_POST['id'];
                    $payment = $mollie->payments->get($transaction_id);
                    
                                        
                    $ARMember->arm_write_response($arm_log_date."webhook Mollie payment for " . $payment->metadata->cst_id . " user ==> " . maybe_serialize($payment), 'response_mollie.txt');

                    if(!empty($transaction_id))
                    {
                        $payment_log_data_check = $wpdb->get_row("SELECT `arm_log_id` FROM `".$ARMember->tbl_arm_payment_log."` WHERE `arm_transaction_id`='".$transaction_id."' OR `arm_token`='".$transaction_id."' ORDER BY arm_log_id DESC LIMIT 1", ARRAY_A);
                        $arm_log_id_check = $payment_log_data_check['arm_log_id'];
                        if(!empty($arm_log_id_check))
                        {
                            die();
                        }
                    }
                    
                    
                    if(isset($payment) && $payment->isPaid()){
                        $ARMember->arm_write_response($arm_log_date."mollie_log payment is paid", 'response_mollie.txt');
                        $entry_email = $payment->metadata->user_email;
                        $arm_log_plan_id = $payment->metadata->plan_id;
                        $arm_log_amount = $payment->amount;
                        $arm_token = $transaction_id;
                        $arm_payment_type = $payment->metadata->plan_payment_type;
                        $entry_id = $payment->metadata->entry_id;
                        $cst_id = $payment->metadata->cst_id;
                        $payment_status = $payment->status;
                        $payment_datetime = $payment->paidDatetime;
                        
                        $entry_data = $wpdb->get_row("SELECT `arm_entry_id`, `arm_entry_email`, `arm_entry_value`, `arm_form_id`, `arm_user_id`, `arm_plan_id` FROM `".$ARMember->tbl_arm_entries."` WHERE `arm_entry_id`='".$entry_id."' AND `arm_entry_email`='".$entry_email."'", ARRAY_A);
                    }
                    else if(isset($_POST['subscriptionId']) && $_POST['subscriptionId'] != '')
                    {
                        $ARMember->arm_write_response($arm_log_date."mollie_log payment is subscription id ".$_POST['subscriptionId'], 'response_mollie.txt');
                        $transaction_id = $_POST['subscriptionId'];
                        $payment_log_data = $wpdb->get_row("SELECT `arm_log_id`, `arm_transaction_id`, `arm_plan_id`, `arm_payer_email`, `arm_amount`, `arm_token` FROM `".$ARMember->tbl_arm_payment_log."` WHERE `arm_transaction_id`='".$transaction_id."' OR `arm_token`='".$transaction_id."' ORDER BY arm_log_id DESC LIMIT 1", ARRAY_A);
                        
                        $cst_id = $payment->metadata->cst_id;
                        $entry_email = $payment_log_data['arm_payer_email'];
                        $arm_log_plan_id = $payment_log_data['arm_plan_id'];
                        $arm_log_amount = $payment_log_data['arm_amount'];
                        $arm_token = $transaction_id;                        
                        
                        $subscriptions = $mollie->customers_subscriptions->withParentId($cst_id)->get($transaction_id);
                        $arm_payment_type = $subscriptions->resource;
                        $cst_id = $subscriptions->customerId;
                        $payment_status = $subscriptions->status;
                        $payment_datetime = $payment->paidDatetime;
                        
                        if(isset($subscriptions) && $subscriptions->status == 'active')
                        {
                            $entry_data = $wpdb->get_row("SELECT `arm_entry_id`, `arm_entry_email`, `arm_entry_value`, `arm_form_id`, `arm_user_id`, `arm_plan_id` FROM `".$ARMember->tbl_arm_entries."` WHERE `arm_entry_email`='".$arm_log_email_id."' AND `arm_plan_id`='".$arm_log_plan_id."' order by arm_entry_id desc", ARRAY_A);
                            
                            $entry_id = $entry_data['arm_entry_id'];
                        }
                    }

                    $ARMember->arm_write_response($arm_log_date."mollie_log payment entry data => ".maybe_serialize($entry_data), 'response_mollie.txt');
                    
                    $global_currency = $arm_payment_gateways->arm_get_global_currency();
                    $extraParam = array();
                    $entry_values = maybe_unserialize($entry_data['arm_entry_value']);
                    $payment_mode = $entry_values['arm_selected_payment_mode'];
                    $tax_percentage = (isset($entry_values['tax_percentage']) && $entry_values['tax_percentage']!='') ? $entry_values['tax_percentage'] : 0;
                    $payment_cycle = $entry_values['arm_selected_payment_cycle'];
                    $arm_user_old_plan = (isset($entry_values['arm_user_old_plan']) && !empty($entry_values['arm_user_old_plan'])) ? explode(",", $entry_values['arm_user_old_plan']) : array();
                    $setup_id = $entry_values['setup_id'];
                    $entry_plan = $entry_data['arm_plan_id'];
                    $mollieLog['arm_coupon_code'] = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                    $mollieLog['arm_payment_type'] = $arm_payment_type;
                    $mollieLog['payment_type'] = $arm_payment_type;
                    $mollieLog['payment_status'] = $payment_status;
                    $mollieLog['cust_id'] = $cst_id;
                    $mollieLog['trans_id'] = $arm_token;
                    $mollieLog['payer_email'] = $entry_email;
                    $mollieLog['payment_date'] = $payment_datetime;
                    $extraParam['arm_is_trial'] = '0';
                    $extraParam['subs_id'] = $arm_token;
                    $extraParam['trans_id'] = $arm_token;
                    $extraParam['error'] = '';
                    $extraParam['date'] = current_time('mysql');
                    $extraParam['message_type'] = '';
                    $extraParam['payment_type'] = 'mollie';
                    $extraParam['payment_mode'] = $mollie_payment_mode;

                    $amount = '';
                    $form_id = $entry_data['arm_form_id'];
                    $armform = new ARM_Form('id', $form_id);
                    $user_info = get_user_by('email', $entry_email);        
                    $new_plan = new ARM_Plan($entry_plan);
                    $user_id = isset($user_info->ID) ? $user_info->ID : 0;
                    $plan_action = 'new_subscription';
                    if ($new_plan->is_recurring() ) {
                        if (in_array($entry_plan, $arm_user_old_plan) && $user_id > 0 ) {
                            $plan_action = 'renew_subscription';
                            $planData = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                            $is_recurring_payment = $arm_subscription_plans->arm_is_recurring_payment_of_user($user_id, $entry_plan, 'manual_subscription');
                            if ($is_recurring_payment) {
                                $plan_action = 'recurring_payment';
                                $oldPlanDetail = $planData['arm_current_plan_detail'];
                                if (!empty($oldPlanDetail)) {
                                    $plan = new ARM_Plan(0);
                                    $plan->init((object) $oldPlanDetail);
                                    $plan_data = $plan->prepare_recurring_data($payment_cycle);
                                    $extraParam['plan_amount'] = $plan_data['amount'];
                                }
                            } else {
                                $plan_action = 'change_subscription';
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

                    $arm_is_trial = 0;
                    $is_trial = false;
                    $allow_trial = true;
                    if (isset($user_info->ID) && $user_info->ID > 0) {
                        $user_id = $user_info->ID;
                        $user_plan = get_user_meta($user_id, 'arm_user_plan_ids', true);

                        if (is_array($user_plan) && in_array($new_plan->ID, $user_plan)) {
                            $allow_trial = false;
                        }

                    }

                    if ($new_plan->has_trial_period() && $allow_trial) {
                        $is_trial = true;
                        $arm_is_trial = '1';
                        $discount_amt = $new_plan->options['trial']['amount'];
                        $trial_period = isset($new_plan->options['trial']['period']) ? $new_plan->options['trial']['period'] : '';
                        $trial_interval = isset($new_plan->options['trial']['interval']) ? $new_plan->options['trial']['period'] : '';
                    }

                    $couponCode = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';

                    $arm_coupon_on_each_subscriptions = 0;

                    if (!empty($couponCode)) {
                        $couponApply = $arm_manage_coupons->arm_apply_coupon_code($couponCode, $new_plan, $setup_id, $payment_cycle, $arm_user_old_plan);
                        $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                        $coupon_amount = str_replace(',', '', $coupon_amount);
                        
                        $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                        $discount_amt = str_replace(',', '', $discount_amt);
                        
                        if ($coupon_amount != 0) {
                            $arm_coupon_discount = $couponApply['discount'];
                            $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $global_currency : "%";
                            $extraParam['coupon'] = array(
                                'coupon_code' => $couponCode,
                                'amount' => $coupon_amount,
                                'arm_coupon_discount_type' => $couponApply['discount_type'],
                                'arm_coupon_discount_unit' => $arm_coupon_discount,
                            );

                            
                            $mollieLog['coupon_code'] = $couponCode;
                            $mollieLog['arm_coupon_discount'] = $arm_coupon_discount;
                            $mollieLog['arm_coupon_discount_type'] = $arm_coupon_discount_type;

                            if($new_plan->is_recurring()) {
                                $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : 0;
                                $extraParam['coupon']["arm_coupon_on_each_subscriptions"] = $arm_coupon_on_each_subscriptions;
                                $mollieLog['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                            }
                        }
                    } 

                    $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                    $mollieLog['currency'] = $global_currency;
                    $tax_amount = 0;
                    if($tax_percentage > 0){
                        $tax_amount = ($tax_percentage*$discount_amt)/100;
                        $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                        $discount_amt = $discount_amt+$tax_amount;
                    }
                    $extraParam['tax_percentage'] = $tax_percentage;
                    $extraParam['tax_amount'] = $tax_amount;
                    $mollieLog['amount'] = $discount_amt;
                    if($mollieLog['amount'] <= 0.1 && $payment_mode == 'auto_debit_subscription') {
                        $mollieLog['amount'] = '0.1';
                    }
                    $extraParam['paid_amount'] =  $mollieLog['amount'];
                    if (!$user_info && in_array($armform->type, array('registration'))) {
                        $ARMember->arm_write_response($arm_log_date."mollie_log register ", 'response_mollie.txt');
                        /* Coupon Details */
                        $recurring_data = $new_plan->prepare_recurring_data($payment_cycle);
                        if (!empty($recurring_data['trial'])) {
                            $extraParam['trial'] = array(
                                'amount' => $recurring_data['trial']['amount'],
                                'period' => $recurring_data['trial']['period'],
                                'interval' => $recurring_data['trial']['interval'],
                            );
                            $extraParam['arm_is_trial'] = '1';
                        }
                        $ARMember->arm_write_response("1");
                        $payment_log_id = $arm_mollie->arm2_store_mollie_log($mollieLog, 0, $entry_plan, $extraParam, $payment_mode);
                        $ARMember->arm_write_response($arm_log_date."mollie_log register log id ".$payment_log_id, 'response_mollie.txt');
                        $payment_done = array();
                        if ($payment_log_id) {
                            $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                        }
                        $entry_values['payment_done'] = '1';
                        $entry_values['arm_entry_id'] = $entry_id;
                        $entry_values['arm_update_user_from_profile'] = 0;
                        $user_id = $arm_member_forms->arm_register_new_member($entry_values, $armform);
                        $ARMember->arm_write_response($arm_log_date."mollie_log register user id ".$user_id, 'response_mollie.txt');
                        if (is_numeric($user_id) && !is_array($user_id)) {
                            if ($arm_payment_type == 'subscription') {
                                $ARMember->arm_write_response($arm_log_date."mollie_log register arm_payment_type ".$arm_payment_type, 'response_mollie.txt');
                                $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                                $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                                $userPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);

                                $userPlanData['arm_mollie']['transaction_id'] = $arm_token;
                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                $pgateway = 'mollie';
                                $arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $payment_log_id, $pgateway, $userPlanData);
                            }
                            update_user_meta($user_id, 'arm_entry_id', $entry_id);
                            /**
                             * Send Email Notification for Successful Payment
                             */
                            $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $entry_plan, 'user_id' => $user_id, 'action' => 'new_subscription'));
                        }
                    } else {
                        $ARMember->arm_write_response($arm_log_date."mollie_log not register", 'response_mollie.txt');
                        $user_id = $user_info->ID;
                        if(!empty($user_id)) {
                            $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                            $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                            $userPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);
                            $ARMember->arm_write_response("2");
                            $payment_log_id = $arm_mollie->arm2_store_mollie_log($mollieLog, $user_id, $entry_plan, $extraParam, $payment_mode);
                            if (!$is_multiple_membership_feature->isMultipleMembershipFeature){
                                $ARMember->arm_write_response($arm_log_date."mollie_log no multiple membership", 'response_mollie.txt');
                                $old_plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true);
                                $old_plan_id = isset($old_plan_ids[0]) ? $old_plan_ids[0] : 0;
                                $oldPlanDetail = array();
                                $old_subscription_id = '';
                                if(!empty($old_plan_id)){
                                    $ARMember->arm_write_response($arm_log_date."mollie_log no multiple membership old plan", 'response_mollie.txt');
                                    $oldPlanData = get_user_meta($user_id, 'arm_user_plan_'.$old_plan_id, true);
                                    $oldPlanData = !empty($oldPlanData) ? $oldPlanData : array();
                                    $oldPlanData = shortcode_atts($defaultPlanData, $oldPlanData);
                                    $oldPlanDetail = $oldPlanData['arm_current_plan_detail'];
                                    $subscr_effective = $oldPlanData['arm_expire_plan'];
                                    $old_subscription_id = isset($oldPlanData['arm_mollie']['sale_id']) ? $oldPlanData['arm_mollie']['sale_id'] : '';
                                }

                                $arm_user_old_plan_details = (isset($userPlanData['arm_current_plan_detail']) && !empty($userPlanData['arm_current_plan_detail'])) ? $userPlanData['arm_current_plan_detail'] : array();
                                $arm_user_old_plan_details['arm_user_old_payment_mode'] = $userPlanData['arm_payment_mode'];


                                if (!empty($old_subscription_id) && $payment_mode == 'auto_debit_subscription' && $old_subscription_id == $arm_token){

                                    $ARMember->arm_write_response($arm_log_date."mollie_log no multiple membership and set only next due date ", 'response_mollie.txt');
                                    $arm_next_due_payment_date = $userPlanData['arm_next_due_payment'];
                                    if(!empty($arm_next_due_payment_date)){
                                        if(strtotime(current_time('mysql')) >= $arm_next_due_payment_date){
                                            $arm_user_completed_recurrence = $userPlanData['arm_completed_recurring'];
                                            $arm_user_completed_recurrence++;
                                            $userPlanData['arm_completed_recurring'] = $arm_user_completed_recurrence;
                                            update_user_meta($user_id, 'arm_user_plan_'.$entry_plan, $userPlanData);
                                            $arm_next_payment_date = $arm_members_class->arm_get_next_due_date($user_id, $entry_plan, false, $payment_cycle);
                                            $ARMember->arm_write_response($arm_log_date."mollie_log arm_user_completed_recurrence => ".$arm_user_completed_recurrence, 'response_mollie.txt');
                                            $ARMember->arm_write_response($arm_log_date."mollie_log arm_next_payment_date => ".$arm_next_payment_date, 'response_mollie.txt');
                                            if ($arm_next_payment_date != '') {

                                                $userPlanData['arm_next_due_payment'] = $arm_next_payment_date;
                                                update_user_meta($user_id, 'arm_user_plan_'.$entry_plan, $userPlanData);
                                            }
                                        }
                                        else{
                                            $now = current_time('mysql');
                                            $arm_last_payment_status = $wpdb->get_var($wpdb->prepare("SELECT `arm_transaction_status` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d AND `arm_created_date`<=%s ORDER BY `arm_log_id` DESC LIMIT 0,1", $user_id, $entry_plan, $now));  
                                            if($arm_last_payment_status=='success'){
                                                $total_completed_recurrence = $planData['arm_completed_recurring'];
                                                $total_completed_recurrence++;
                                                $planData['arm_completed_recurring'] = $total_completed_recurrence;

                                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $planData);
                                                $payment_cycle = $planData['arm_payment_cycle'];

                                                $arm_next_payment_date = $arm_members_class->arm_get_next_due_date($user_id, $entry_plan, false, $payment_cycle);
                                                $planData['arm_next_due_payment'] = $arm_next_payment_date;
                                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $planData);
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
                                   $ARMember->arm_write_response($arm_log_date."mollie log : ".date('Y-m-d')." User ID : ". $user_id." in single membership old subscription id is empty", 'response_mollie.txt');
                                   $ARMember->arm_write_response($arm_log_date."mollie log : last payment status => ".maybe_serialize($arm_last_payment_status), 'response_mollie.txt');

                                   $ARMember->arm_write_response($arm_log_date.maybe_serialize($userPlanData), 'response_mollie.txt');

                                   $userPlanData['arm_current_plan_detail'] = $arm_user_old_plan_details;

                                    $ARMember->arm_write_response($arm_log_date."mollie_log no multiple membership and not subscription", 'response_mollie.txt');
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
                                    }
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
                                    $userPlanData['arm_user_gateway'] = 'mollie';

                                    if (!empty($arm_token)) {
                                        $userPlanData['arm_token'] = $arm_token;
                                    }
                                    update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                    if ($is_update_plan) {
                                        $ARMember->arm_write_response($arm_log_date."mollie_log update plan", 'response_mollie.txt');
                                        $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan, '', true, $arm_last_payment_status);
                                    } else {
                                        $ARMember->arm_write_response($arm_log_date."mollie_log no update plan", 'response_mollie.txt');
                                        $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'change_subscription');
                                    }
                                }
                            }
                            else{
                                $ARMember->arm_write_response($arm_log_date."mollie_log in multiple membership", 'response_mollie.txt');
                                $old_plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true);
                                
                                $arm_user_old_plan_details = (isset($userPlanData['arm_current_plan_detail']) && !empty($userPlanData['arm_current_plan_detail'])) ? $userPlanData['arm_current_plan_detail'] : array();
                                $arm_user_old_plan_details['arm_user_old_payment_mode'] = $userPlanData['arm_payment_mode'];
                                $oldPlanDetail = array();
                                $old_subscription_id = '';
                                if(in_array($entry_plan, $old_plan_ids)){
                                    $oldPlanData = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                                    $oldPlanDetail = $oldPlanData['arm_current_plan_detail'];
                                    $subscr_effective = $oldPlanData['arm_expire_plan'];
                                    $old_subscription_id = $oldPlanData['arm_mollie']['sale_id'];
                                    $arm_user_old_plan_details = (isset($userPlanData['arm_current_plan_detail']) && !empty($userPlanData['arm_current_plan_detail'])) ? $userPlanData['arm_current_plan_detail'] : array();
                                    $arm_user_old_plan_details['arm_user_old_payment_mode'] = $userPlanData['arm_payment_mode'];

                                    if(empty($old_subscription_id) || empty($arm_token) || $old_subscription_id != $arm_token){

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
                                        }

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
                                        $userPlanData['arm_user_gateway'] = 'mollie';

                                        if (!empty($arm_token)) {
                                            $userPlanData['arm_mollie']['transaction_id'] = $arm_token;
                                        }
                                        $ARMember->arm_write_response($arm_log_date."mollie_log multiple membership userPlanData".maybe_serialize($userPlanData), 'response_mollie.txt');
                                        update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
	                                    if ($is_update_plan) {
	                                        $ARMember->arm_write_response($arm_log_date."mollie_log multiple membership update plan", 'response_mollie.txt');
	                                        $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan, '', true, $arm_last_payment_status);
	                                    } else {
	                                        $ARMember->arm_write_response($arm_log_date."mollie_log multiple membership no update plan", 'response_mollie.txt');
	                                        $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'change_subscription');
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
                                    }
                                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                    $userPlanData['arm_user_gateway'] = 'mollie';

                                    if (!empty($arm_token)) {
                                        $userPlanData['arm_mollie']['transaction_id'] = $arm_token;
                                    }
                                    update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                    if ($is_update_plan) {
                                        $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan);
                                    } else {
                                       $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'new_subscription');
                                    }
                                }
                            }

                            if ($arm_payment_type == 'subscription') {
                                $ARMember->arm_write_response($arm_log_date."mollie_log subscription 1 arm_payment_type ".$arm_payment_type, 'response_mollie.txt');
                                $pgateway = 'mollie';
                                $arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $payment_log_id, $pgateway, $userPlanData);
                            }
                        }
                        $is_log = true;
                    }
                    
                    if(isset($arm_payment_type) && $arm_payment_type == 'subscription' && isset($_POST['id']) && !isset($_POST['subscriptionId']) && $payment_mode == 'auto_debit_subscription')
                    {
                        $ARMember->arm_write_response($arm_log_date."mollie_log first subscription", 'response_mollie.txt');
                        $arm_mollie_cst = $cst_id;
                        $arm_mollie_trn = $transaction_id;
                        $plan_id = $arm_log_plan_id;

                        $arm_mollie_webhookurl = '';
                        $arm_mollie_webhookurl = $arm_global_settings->add_query_arg("arm-listener", "arm_mollie_wh_api", get_home_url() . "/");

                        $plan = new ARM_Plan($plan_id);
                        $is_recurring = $plan->is_recurring();
                        if($is_recurring)
                        {
                            $arm_mollie_mandates = $mollie->customers_mandates->withParentId($arm_mollie_cst)->all();
                            $ARMember->arm_write_response($arm_log_date."mollie_log mandates response => ".maybe_serialize($arm_mollie_mandates), 'response_mollie.txt');
                            if(isset($arm_mollie_mandates->data[0]->id) && (isset($arm_mollie_mandates->data[0]->status) && ($arm_mollie_mandates->data[0]->status == 'pending' || $arm_mollie_mandates->data[0]->status == 'valid')))
                            {
                                $ARMember->arm_write_response($arm_log_date."mollie_log in mandates  ", 'response_mollie.txt');
                            
                                $plan_name = !empty($plan->name) ? $plan->name : "Plan Name";
                                $amount = !empty($plan->amount) ? $plan->amount : "0";
                                
                                if( $arm_coupon_on_each_subscriptions == 1 && $amount > 0) {
                                    $arm_coupon_discount_unit = isset($extraParam['coupon']['arm_coupon_discount_unit']) ? $extraParam['coupon']['arm_coupon_discount_unit'] : '0';
                                    $arm_coupon_discount_type = isset($extraParam['coupon']['arm_coupon_discount_type']) ? $extraParam['coupon']['arm_coupon_discount_type'] : 'percentage';
                                    
                                    $discount_amt_next = 0;
                                    if($arm_coupon_discount_type=='percentage')
                                    {
                                        $discount_amt_next = ($amount * $arm_coupon_discount_unit) / 100;
                                        $discount_amt_next = $amount-$discount_amt_next;
                                    }
                                    else
                                    {
                                        $discount_amt_next = $amount - $arm_coupon_discount_unit;
                                    }
                                    
                                    if($discount_amt_next<=0.1)
                                    {
                                       $discount_amt_next = 0.1; 
                                    }
                                    $amount = $discount_amt_next;
                                }
                                
                                $recurring_data = $plan->prepare_recurring_data();
                                $arm_mollie_recur_period = $recurring_data['period'];
                                switch ($arm_mollie_recur_period) {
                                    case 'M':
                                        $arm_mollie_payperiod = "months";
                                        break;
                                    case 'D':
                                        $arm_mollie_payperiod = "days";
                                        break;
                                    case 'W':
                                        $arm_mollie_payperiod = "weeks";
                                        break;
                                    case 'Y':
                                        $arm_mollie_payperiod = "years";
                                        break;
                                }

                                $arm_mollie_recur_interval = $recurring_data['interval']." ".$arm_mollie_payperiod;
                                $arm_mollie_recur_cycles = !empty($recurring_data['cycles']) ? ($recurring_data['cycles']-1) : '';
                                
                                $getTrialDate = false;
                                if ($plan_action == 'new_subscription') {
                                    $getTrialDate = true;
                                }
                                
                                $arm_mollie_start_date = $arm_members_class->arm_get_start_date_for_auto_debit_plan($plan->ID, $getTrialDate, $payment_cycle, $plan_action, $user_id);
                                $ARMember->arm_write_response($arm_log_date."mollie_log mandates startDate => ".$arm_mollie_start_date, 'response_mollie.txt');
                                
                                $arm_mollie_start_date = date('Y-m-d', $arm_mollie_start_date);
                                $ARMember->arm_write_response($arm_log_date."mollie_log mandates arm_mollie_start_date => ".$arm_mollie_start_date, 'response_mollie.txt');


                                $tax_amount = 0;
                                if($tax_percentage > 0){
                                    $tax_amount = ($tax_percentage*$amount)/100;
                                    $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                                    $amount = $amount+$tax_amount;
                                }
                                $ARMember->arm_write_response("Final Amount is : " . $amount, 'response_mollie.txt');

                                
                                $arm_mollie_create_subscription_arr = array(
                                    "amount"      => $amount,
                                    "interval"    => $arm_mollie_recur_interval,
                                    "startDate"   => $arm_mollie_start_date,
                                    "description" => $plan_name,
                                    "webhookUrl"  => $arm_mollie_webhookurl
                                );
                                if(!empty($arm_mollie_recur_cycles))
                                {
                                    $arm_mollie_create_subscription_arr["times"] = $arm_mollie_recur_cycles;
                                }

                                $arm_mollie_subscription = $mollie->customers_subscriptions->withParentId($arm_mollie_cst)->create($arm_mollie_create_subscription_arr);
                                $ARMember->arm_write_response($arm_log_date."mollie_log subscription for " . $arm_mollie_cst . " user ==> " . maybe_serialize($arm_mollie_subscription), 'response_mollie.txt');
                    
                                if(isset($arm_mollie_subscription->id))
                                {
                                    $mollielLog['token_id'] = $arm_mollie_subscription->id;

                                    $wpdb->update( $ARMember->tbl_arm_payment_log, 
                                        array( 'arm_transaction_id' => $arm_mollie_subscription->id, 'arm_transaction_status'=>'success' ), 
                                        array( 'arm_token' => $arm_mollie_cst, 'arm_payment_type' => $plan->payment_type ), 
                                        array( '%s', '%s' ), 
                                        array( '%s', '%s', '%s' ) 
                                    );
                                    
                                    $payLog = $wpdb->get_row( $wpdb->prepare("SELECT `arm_user_id`, `arm_plan_id` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_token`=%s AND `arm_payment_gateway`=%s AND arm_transaction_id=%s ORDER BY `arm_log_id` DESC", $arm_mollie_cst, 'mollie', $arm_mollie_subscription->id));
                                    $user_id = $payLog->arm_user_id;
                                    $user_subsdata = get_user_meta($user_id, 'arm_mollie_' . $plan_id, true);
                                    $payment_mode = get_user_meta($user_id,'arm_selected_payment_mode',true);
                                    do_action('arm_after_recurring_payment_success_outside',$user_id,$plan_id,'molie',$payment_mode,$user_subsdata);
                                }
                                else{
                                    $payLog = $wpdb->get_row( $wpdb->prepare("SELECT `arm_user_id`, `arm_plan_id` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_token`=%s AND `arm_payment_gateway`=%s ORDER BY `arm_log_id` DESC", $arm_mollie_cst, 'mollie'));
                                    $user_id = $payLog->arm_user_id;
                                    $plan_id = $payLog->arm_plan_id;
                                    $user_subsdata = get_user_meta($user_id, 'arm_mollie_' . $plan_id, true);
                                    $payment_mode = get_user_meta($user_id,'arm_selected_payment_mode',true);
                                    do_action('arm_after_recurring_payment_failed_outside',$user_id,$plan_id,'molie',$payment_mode,$user_subsdata);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    function arm_after_payment_process($entry_data, $entry_email, $arm_log_plan_id, $arm_log_amount, $arm_token, $arm_payment_type, $cst_id, $payment_status, $entry_id, $payment_datetime){
        
        global $wpdb, $mollie, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication, $arm_manage_coupons, $payment_done, $arm_mollie;
        
        $currency = $arm_payment_gateways->arm_get_global_currency();
        $entry_values = maybe_unserialize($entry_data['arm_entry_value']);
        
        $plan_action = 'new_subscription';
        if (isset($entry_values['arm_user_old_plan']) && !empty($entry_values['arm_user_old_plan']) && $entry_values['arm_user_old_plan'] != 0) {
            if ($entry_values['arm_user_old_plan'] == $arm_log_plan_id) {
                $plan_action = 'renew_subscription';
            } else {
                $plan_action = 'change_subscription';
            }
        }

        $plan = new ARM_Plan($arm_log_plan_id);
        if ($plan_action == 'new_subscription') {
            $arm_is_trial = 0;
            $is_trial = false;
            $allow_trial = true;
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $user_plan = get_user_meta($user_id, 'arm_user_plan', true);
                $user_plan_id = $user_plan;
                if ($user_plan == $plan->ID) {
                    $allow_trial = false;
                }
            }
            if ($plan->has_trial_period() && $allow_trial) {
                $is_trial = true;
                $arm_is_trial = '1';
                $amount = $plan->options['trial']['amount'];
                $trial_period = isset($plan->options['trial']['period']) ? $plan->options['trial']['period'] : '';
                $trial_interval = isset($plan->options['trial']['interval']) ? $plan->options['trial']['interval'] : '';
            }
        }
        
        
        if (!empty($entry_data) && $arm_payment_type == 'one_time')
        {    
            $is_log = false;
            $extraParam = array('plan_amount' => $arm_log_amount, 'paid_amount' => $arm_log_amount);
            $entry_plan = $entry_data['arm_plan_id'];
             $tax_percentage = (isset($entry_values['tax_percentage']) && $entry_values['tax_percentage']!='') ? $entry_values['tax_percentage'] : 0;
             $extraParam['tax_percentage'] = $tax_percentage;
            $mollielLog['arm_coupon_code'] = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : ''; 

            $mollielLog['arm_payment_type'] = $arm_payment_type;
            $mollielLog['cst_id'] = $cst_id;
            $mollielLog['token_id'] = $arm_token;
            $mollielLog['payer_email'] = $entry_email;
            $mollielLog['payment_type'] = $arm_payment_type;
            $mollielLog['payment_status'] = $payment_status;
            $mollielLog['payment_date'] = $payment_datetime;
            $mollielLog['payment_amount'] = $arm_log_amount;
            $mollielLog['currency'] = $currency;
            $mollielLog['payment_mode'] = $entry_values['arm_selected_payment_mode'];
            $mollielLog['arm_is_trial'] = $arm_is_trial;
            
            $extraParam['trans_id'] = isset($arm_token) ? $arm_token : '';
            $extraParam['date'] = current_time('mysql');
            $extraParam['message_type'] = isset($arm_payment_type) ? $arm_payment_type : '';



            $form_id = $entry_data['arm_form_id'];
            $armform = new ARM_Form('id', $form_id);
            $user_info = get_user_by('email', $entry_email);
            $new_plan = new ARM_Plan($entry_plan);
            $extraParam['plan_amount'] = $total_amount = $new_plan->amount;

            if (!$user_info && in_array($armform->type, array('registration')))
            {
                /* Coupon Details */
                $total_amount = $new_plan->amount;
                $couponCode = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                if (!empty($couponCode)) {
                    $couponApply = $arm_manage_coupons->arm_apply_coupon_code($couponCode, $new_plan);
                    $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                    $total_amount = isset($couponApply['total_amt']) ? $couponApply['total_amt'] :  $total_amount;
                    if ($coupon_amount != 0) {
                        $extraParam['coupon'] = array(
                            'coupon_code' => $couponCode,
                            'amount' => $coupon_amount,
                        );
                    }

                    $tax_amount = 0;
                    if($tax_percentage > 0){
                        $tax_amount = ($tax_percentage*$total_amount)/100;
                        $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                    }
                    $extraParam['tax_amount'] = $tax_amount;


                }

                $payment_log_id = $arm_mollie->arm_store_mollie_log($mollielLog, 0, $entry_plan, $extraParam);
                $payment_done = array();
                if ($payment_log_id) {
                    $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                }
                $entry_values['payment_done'] = '1';
                $entry_values['arm_entry_id'] = $entry_id;
                $user_id = $arm_member_forms->arm_register_new_member($entry_values, $armform);
                if (is_numeric($user_id) && !is_array($user_id)) {
                    if ($arm_payment_type == 'subscription') {
                        update_user_meta($user_id, 'arm_subscr_id_' . $entry_plan, $arm_token);
                    }
                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                    /**
                     * Send Email Notification for Successful Payment
                     */
                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $entry_plan, 'user_id' => $user_id, 'action' => 'new_subscription'));
                }
            } else {
                $user_id = $user_info->ID;
                $payment_log_id = $arm_mollie->arm_store_mollie_log($mollielLog, $user_id, $entry_plan, $extraParam);
                $old_plan_id = get_user_meta($user_id, 'arm_user_plan', true);
                $old_subscription_id = get_user_meta($user_id, 'arm_subscr_id_'.$old_plan_id, true);
                if ($old_plan_id != $entry_plan) {
                    $oldPlanDetail = get_user_meta($user_id, 'arm_current_plan_detail', true);
                    if (!empty($oldPlanDetail)) {
                        $old_plan = new ARM_Plan(0);
                        $old_plan->init((object) $oldPlanDetail);
                    } else {
                        $old_plan = new ARM_Plan($old_plan_id);
                    }
                    $is_update_plan = true;
                    /* Coupon Details */
                    $couponCode = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                    if (!empty($couponCode)) {
                        $couponApply = $arm_manage_coupons->arm_apply_coupon_code($couponCode, $new_plan);
                        $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                        $total_amount = isset($couponApply['total_amt']) ? $couponApply['total_amt'] :  $total_amount;
                        if ($coupon_amount != 0) {
                            $extraParam['coupon'] = array(
                                'coupon_code' => $couponCode,
                                'amount' => $coupon_amount,
                            );
                        }
                    }

                    


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
                            $subscr_effective = get_user_meta($user_id, 'arm_expire_plan_' . $old_plan_id, true);
                            if ($change_act == 'on_expire' && !empty($subscr_effective)) {
                                $is_update_plan = false;
                                update_user_meta($user_id, 'arm_subscr_effective', $subscr_effective);
                                update_user_meta($user_id, 'arm_change_plan_to', $entry_plan);
                            }
                        }
                    }
                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                    update_user_meta($user_id, 'arm_using_gateway_' . $entry_plan, 'mollie');
                    if (!empty($arm_token)) {
                        update_user_meta($user_id, 'arm_subscr_id_' . $entry_plan, $arm_token);
                    }
                    if ($is_update_plan) {
                        $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan);
                    } else {
                        $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'change_subscription');
                    }
                } 
                else if ($old_plan_id == $entry_plan && (empty($old_subscription_id) || $old_subscription_id == '')) {
                        $oldPlanDetail = get_user_meta($user_id, 'arm_current_plan_detail', true);
                        if (!empty($oldPlanDetail)) {
                            $old_plan = new ARM_Plan(0);
                            $old_plan->init((object) $oldPlanDetail);
                        } else {
                            $old_plan = new ARM_Plan($old_plan_id);
                        }
                        $is_update_plan = true;
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
                                $subscr_effective = get_user_meta($user_id, 'arm_expire_plan_' . $old_plan_id, true);
                                if ($change_act == 'on_expire' && !empty($subscr_effective)) {
                                    $is_update_plan = false;
                                    update_user_meta($user_id, 'arm_subscr_effective', $subscr_effective);
                                    update_user_meta($user_id, 'arm_change_plan_to', $entry_plan);
                                }
                            }
                        }
                        update_user_meta($user_id, 'arm_entry_id', $entry_id);
                        update_user_meta($user_id, 'arm_using_gateway_' . $entry_plan, 'mollie');
                        if (!empty($arm_token)) {
                            update_user_meta($user_id, 'arm_subscr_id_' . $entry_plan, $arm_token);
                        }
                        if ($is_update_plan) {
                            $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan);
                        } else {
                            $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'change_subscription');
                        }
                        $is_log = true;
                    }                                            
                else {
                    $entry_values['arm_coupon_code'] = '';
                }
                $is_log = true;
            }


            $tax_amount = 0;
            if($tax_percentage > 0){
                $tax_amount = ($tax_percentage*$total_amount)/100;
                $tax_amount = number_format((float)$tax_amount, 2, '.', '');
            }
            $extraParam['tax_amount'] = $tax_amount;

            if ($is_log && !empty($user_id) && $user_id != 0) {
                $payment_log_id = $arm_mollie->arm_store_mollie_log($mollielLog, $user_id, $entry_plan, $extraParam);
                delete_user_meta($user_id, 'arm_payment_cancelled_by');
            } 
        } else if(!empty($entry_data) && $arm_payment_type == 'subscription'){
            $gateway_options = get_option('arm_payment_gateway_settings');
            $pgoptions = maybe_unserialize($gateway_options);
            $pgoptions = $pgoptions['mollie'];
            
            $extraParam = array('plan_amount' => $arm_log_amount, 'paid_amount' => $arm_log_amount, 'payment_type' => 'mollie', 'payment_mode' => $pgoptions['mollie_payment_mode']);
            $entry_plan = $entry_data['arm_plan_id'];
            $entry_values = maybe_unserialize($entry_data['arm_entry_value']);
             $tax_percentage = (isset($entry_values['tax_percentage']) && $entry_values['tax_percentage']!='') ? $entry_values['tax_percentage'] : 0;
             $extraParam['tax_percentage'] = $tax_percentage;
            $mollielLog['arm_coupon_code'] = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : ''; 
            $mollielLog['arm_payment_type'] = $arm_payment_type;
            $mollielLog['cst_id'] = $cst_id;
            $mollielLog['token_id'] = $arm_token;
            $mollielLog['payer_email'] = $entry_email;
            $mollielLog['payment_type'] = $arm_payment_type;
            $mollielLog['payment_status'] = $payment_status;
            $mollielLog['payment_date'] = isset($payment->paidDatetime) ? $payment->paidDatetime : current_time('mysql');
            $mollielLog['payment_amount'] = $arm_log_amount;
            $mollielLog['currency'] = $currency;
            $mollielLog['payment_mode'] = $entry_values['arm_selected_payment_mode'];
            $mollielLog['arm_is_trial'] = $arm_is_trial;
            
            $form_id = $entry_data['arm_form_id'];
            $armform = new ARM_Form('id', $form_id);
            $user_info = get_user_by('email', $entry_email);
            $new_plan = new ARM_Plan($entry_plan);
            $couponCode = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
            /* Coupon Details */
            $extraParam['plan_amount'] = $total_amount = $new_plan->amount;
            if (!empty($couponCode)) {
                $couponApply = $arm_manage_coupons->arm_apply_coupon_code($couponCode, $new_plan);
                $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                $total_amount = isset($couponApply['total_amt']) ? $couponApply['total_amt'] :  $total_amount;
                if ($coupon_amount != 0) {
                    $extraParam['coupon'] = array(
                        'coupon_code' => $couponCode,
                        'amount' => $coupon_amount,
                    );
                }
            }

            $tax_amount = 0;
            if($tax_percentage > 0){
                $tax_amount = ($tax_percentage*$total_amount)/100;
                $tax_amount = number_format((float)$tax_amount, 2, '.', '');
            }
            $extraParam['tax_amount'] = $tax_amount;
            if (!$user_info && in_array($armform->type, array('registration')))
            {
                $payment_log_id = $arm_mollie->arm_store_mollie_log($mollielLog, 0, $entry_plan, $extraParam);
                $payment_done = array();
                if ($payment_log_id) {
                    $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                }
                $entry_values['payment_done'] = '1';
                $entry_values['arm_entry_id'] = $entry_id;
                $user_id = $arm_member_forms->arm_register_new_member($entry_values, $armform);
                if (is_numeric($user_id) && !is_array($user_id)) {
                    if ($arm_payment_type == 'subscription') {
                        update_user_meta($user_id, 'arm_subscr_id_' . $entry_plan, $arm_token);
                    }
                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                    /**
                     * Send Email Notification for Successful Payment
                     */
                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $entry_plan, 'user_id' => $user_id, 'action' => 'new_subscription'));
                }
            } else {
                $user_id = $user_info->ID;
                $payment_log_id = $arm_mollie->arm_store_mollie_log($mollielLog, $user_id, $entry_plan, $extraParam);
                $old_plan_id = get_user_meta($user_id, 'arm_user_plan', true);
                if ($old_plan_id != $entry_plan) {
                    $oldPlanDetail = get_user_meta($user_id, 'arm_current_plan_detail', true);
                    if (!empty($oldPlanDetail)) {
                        $old_plan = new ARM_Plan(0);
                        $old_plan->init((object) $oldPlanDetail);
                    } else {
                        $old_plan = new ARM_Plan($old_plan_id);
                    }
                    $is_update_plan = true;
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
                            $subscr_effective = get_user_meta($user_id, 'arm_expire_plan_' . $old_plan_id, true);
                            if ($change_act == 'on_expire' && !empty($subscr_effective)) {
                                $is_update_plan = false;
                                update_user_meta($user_id, 'arm_subscr_effective', $subscr_effective);
                                update_user_meta($user_id, 'arm_change_plan_to', $entry_plan);
                            }
                        }
                    }
                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                    update_user_meta($user_id, 'arm_using_gateway_' . $entry_plan, 'mollie');
                    if (!empty($arm_token)) {
                        update_user_meta($user_id, 'arm_subscr_id_' . $entry_plan, $arm_token);
                    }
                    if ($is_update_plan) {
                        $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan);
                    } else {
                        $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'change_subscription');
                    }
                    $is_log = true;
                } else if ($old_plan_id == $entry_plan) {
                    $oldPlanDetail = get_user_meta($user_id, 'arm_current_plan_detail', true);
                    if (!empty($oldPlanDetail)) {
                        $old_plan = new ARM_Plan(0);
                        $old_plan->init((object) $oldPlanDetail);
                    } else {
                        $old_plan = new ARM_Plan($old_plan_id);
                    }
                    $is_update_plan = true;
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
                            $subscr_effective = get_user_meta($user_id, 'arm_expire_plan_' . $old_plan_id, true);
                            if ($change_act == 'on_expire' && !empty($subscr_effective)) {
                                $is_update_plan = false;
                                update_user_meta($user_id, 'arm_subscr_effective', $subscr_effective);
                                update_user_meta($user_id, 'arm_change_plan_to', $entry_plan);
                            }
                        }
                    }
                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                    update_user_meta($user_id, 'arm_using_gateway_' . $entry_plan, 'mollie');
                    if (!empty($arm_token)) {
                        update_user_meta($user_id, 'arm_subscr_id_' . $entry_plan, $arm_token);
                    }
                    if ($is_update_plan) {
                        $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan);
                    } else {
                        $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'change_subscription');
                    }
                    $is_log = true;
                }
            }
        }
    }
    
    function arm_store_mollie_log($mollie_response = '', $user_id = 0, $plan_id = 0, $extraVars = array()){
        global $wpdb, $ARMember, $arm_global_settings, $arm_member_forms, $arm_payment_gateways;
        if (!empty($mollie_response)) 
        {
            $arm_payment_log_tbl = $ARMember->tbl_arm_payment_log;
            $arm_check_mollie_transactions = $wpdb->get_row($wpdb->prepare("SELECT arm_token FROM `{$arm_payment_log_tbl}` WHERE `arm_transaction_id` = %s AND `arm_payment_gateway` = %s", $mollie_response['token_id'] , 'mollie'));
            if(empty($arm_check_mollie_transactions))
            {
                $payment_data = array(
                    'arm_user_id' => $user_id,
                    'arm_first_name'=>$mollie_response['arm_first_name'],
                    'arm_last_name'=>$mollie_response['arm_last_name'],
                    'arm_plan_id' => (!empty($plan_id) ? $plan_id : 0),
                    'arm_payment_gateway' => 'mollie',
                    'arm_payment_type' => $mollie_response['arm_payment_type'],
                    'arm_token' => $mollie_response['cst_id'],
                    'arm_payer_email' => $mollie_response['payer_email'],
                    'arm_receiver_email' => '',
                    'arm_transaction_id' => $mollie_response['token_id'],
                    'arm_transaction_payment_type' => $mollie_response['payment_type'],
                    'arm_transaction_status' => $mollie_response['payment_status'],
                    'arm_payment_date' => date('Y-m-d H:i:s', strtotime($mollie_response['payment_date'])),
                    'arm_amount' => $mollie_response['payment_amount'],
                    'arm_payment_mode' => $mollie_response['payment_mode'],
                    'arm_currency' => $mollie_response['currency'],
                    'arm_coupon_code' => $mollie_response['arm_coupon_code'],
                    'arm_response_text' => maybe_serialize($mollie_response),
                    'arm_extra_vars' => maybe_serialize($extraVars),
                    'arm_is_trial' => $mollie_response['arm_is_trial'] ? $mollie_response['arm_is_trial'] : 0,
                    'arm_created_date' => current_time('mysql')
                );
                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
                return $payment_log_id;
            }
        }
        return false;
    }
    
    function arm2_store_mollie_log($mollie_response = '', $user_id = 0, $plan_id = 0, $extraVars = array(), $payment_mode = 'manual_subscription') {
        global $wpdb, $ARMember, $arm_global_settings, $arm_member_forms, $arm_payment_gateways;

        $ARMember->arm_write_response("reputelog extra vars : ".maybe_serialize($extraVars));

        if (!empty($mollie_response)) {
            $payment_data = array(
                'arm_user_id' => $user_id,
                'arm_first_name'=>$mollie_response['arm_first_name'],
                'arm_last_name'=>$mollie_response['arm_last_name'],
                'arm_plan_id' => (!empty($plan_id) ? $plan_id : 0),
                'arm_payment_gateway' => 'mollie',
                'arm_payment_type' => $mollie_response['arm_payment_type'],
                'arm_token' => $mollie_response['cust_id'],
                'arm_payer_email' => $mollie_response['payer_email'],
                'arm_receiver_email' => '',
                'arm_transaction_id' => $mollie_response['trans_id'],
                'arm_transaction_payment_type' => $mollie_response['payment_type'],
                'arm_transaction_status' => $mollie_response['payment_status'],
                'arm_payment_mode' => $payment_mode,
                'arm_payment_date' => date('Y-m-d H:i:s', strtotime($mollie_response['payment_date'])),
                'arm_amount' => $mollie_response['amount'],
                'arm_currency' => $mollie_response['currency'],
                'arm_coupon_code' => $mollie_response['arm_coupon_code'],
                'arm_coupon_discount' => (isset($mollie_response['arm_coupon_discount']) && !empty($mollie_response['arm_coupon_discount'])) ? $mollie_response['arm_coupon_discount'] : 0,
                'arm_coupon_discount_type' => isset($mollie_response['arm_coupon_discount_type']) ? $mollie_response['arm_coupon_discount_type'] : '',
                'arm_response_text' => utf8_encode(maybe_serialize($mollie_response)),
                'arm_extra_vars' => maybe_serialize($extraVars),
                'arm_is_trial' => $extraVars['arm_is_trial'],
                'arm_created_date' => current_time('mysql'),
                'arm_coupon_on_each_subscriptions' => isset($mollie_response['arm_coupon_on_each_subscriptions']) ? $mollie_response['arm_coupon_on_each_subscriptions'] : 0
            );

            $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
            return $payment_log_id;
        }
        return false;
    }
    
    function arm_mollie_modify_coupon_code($data,$payment_mode,$couponData,$planAmt,$planObj){
        
        if( isset($planObj->type) && 'recurring' == $planObj->type && $payment_mode == 'auto_debit_subscription' ){
            if( $data['status'] == 'success' && $data['total_amt'] <= 0 ){
                $coupon_amt = $planAmt - 0.10;
                $data['coupon_amt'] = $coupon_amt;
                $data['total_amt'] = 0.10;
            }
        }
        return $data;
    }
    
    function arm_mollie_change_pending_gateway_outside($user_pending_pgway,$plan_ID,$user_id){
            global $is_free_manual,$ARMember;
            if( $is_free_manual ){
                $key = array_search('mollie',$user_pending_pgway);
                unset($user_pending_pgway[$key]);
            }
            return $user_pending_pgway;
        }
        
}

global $arm_mollie;
$arm_mollie = new ARM_Mollie();



global $armmollie_api_url, $armmollie_plugin_slug;

$armmollie_api_url = $arm_mollie->armmollie_getapiurl();
$armmollie_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armmollie_check_for_plugin_update');

function armmollie_check_for_plugin_update($checked_data) {
    global $armmollie_api_url, $armmollie_plugin_slug, $wp_version, $arm_mollie_version,$arm_mollie;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armmollie_plugin_slug,
        'version' => $arm_mollie_version,
        'other_variables' => $arm_mollie->armmollie_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMMOLLIE-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armmollie_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armmollie_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armmollie_plugin_slug . '/' . $armmollie_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armmollie_plugin_api_call', 10, 3);

function armmollie_plugin_api_call($def, $action, $args) {
    global $armmollie_plugin_slug, $armmollie_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armmollie_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armmollie_plugin_slug . '/' . $armmollie_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armmollie_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMMOLLIE-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armmollie_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', MEMBERSHIP_TXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', MEMBERSHIP_TXTDOMAIN), $request['body']);
    }

    return $res;
}
?>