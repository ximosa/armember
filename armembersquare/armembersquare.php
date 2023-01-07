<?php 
/*
  Plugin Name: ARMember - Square payment gateway Addon
  Description: Extension for ARMember plugin to accept payments using Square Payment Gateway.
  Version: 1.0
  Plugin URI: https://www.armemberplugin.com
  Author: Repute InfoSystems
  Author URI: https://www.armemberplugin.com
 */

define('ARM_SQUARE_DIR_NAME', 'armembersquare');
define('ARM_SQUARE_DIR', WP_PLUGIN_DIR . '/' . ARM_SQUARE_DIR_NAME);

if (is_ssl()) {
    define('ARM_SQUARE_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_SQUARE_DIR_NAME));
    define('ARM_SQUARE_HOME_URL', home_url('','https'));
} else {
    define('ARM_SQUARE_URL', WP_PLUGIN_URL . '/' . ARM_SQUARE_DIR_NAME);
    define('ARM_SQUARE_HOME_URL', home_url());
}

define('ARM_SQUARE_DOC_URL', ARM_SQUARE_URL . '/documentation/index.html#content');

global $arm_square_version;
$arm_square_version = '1.0';

global $armnew_square_version;


global $armsquare_api_url, $armsquare_plugin_slug, $wp_version;

class ARM_Square{
    
    function __construct() {
        global $arm_payment_gateways, $arm_transaction;
        $arm_payment_gateways->currency['square'] = $this->arm_square_currency_symbol();

        add_action('init', array(&$this, 'arm_square_db_check'));

        register_activation_hook(__FILE__, array('ARM_Square', 'install'));

        register_activation_hook(__FILE__, array('ARM_Square', 'arm_square_check_network_activation'));

        register_uninstall_hook(__FILE__, array('ARM_Square', 'uninstall'));

        add_filter('arm_get_payment_gateways', array(&$this, 'arm_add_square_payment_gateways'));
        
        add_filter('arm_get_payment_gateways_in_filters', array(&$this, 'arm_add_square_payment_gateways'));
        
        add_action('admin_notices', array(&$this, 'arm_square_admin_notices'));
        
        add_filter('arm_change_payment_gateway_tooltip', array(&$this, 'arm_change_payment_gateway_tooltip_func'), 10, 3);
        
        add_filter('arm_gateway_callback_info', array(&$this, 'arm_gateway_callback_info_func'), 10, 3);
        
        add_filter('arm_filter_gateway_names', array(&$this, 'arm_filter_gateway_names_func'), 10);
        
        
        add_filter('arm_set_gateway_warning_in_plan_with_recurring', array(&$this, 'arm_square_recurring_trial'), 10);

        add_filter('arm_not_display_payment_mode_setup', array(&$this, 'arm_not_display_payment_mode_setup_func'), 10, 1);
        
        add_filter('arm_allowed_payment_gateways', array(&$this, 'arm_payment_allowed_gateways'), 10, 3);
        
        add_action('arm_payment_related_common_message', array(&$this, 'arm_payment_related_common_message'), 10);

        add_filter('arm_currency_support', array(&$this, 'arm_square_currency_support'), 10, 2);

        add_action('arm_after_payment_gateway_listing_section', array(&$this, 'arm_after_payment_gateway_listing_section_func'), 10, 2);

        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_script'), 10);

        add_action('wp_head', array(&$this, 'arm_square_set_front_js'), 10);
        
        add_action('plugins_loaded', array(&$this, 'arm_square_load_textdomain'));
        
        //add_action('admin_init', array(&$this, 'upgrade_data_square'));
        
        add_filter('arm_change_pending_gateway_outside',array(&$this,'arm2_change_pending_gateway_outside'),100,3);
        
        add_filter('arm_membership_update_user_meta_from_outside', array(&$this, 'arm2_membership_square_update_usermeta'), 10, 5);
        
        add_action('arm_update_user_meta_after_renew_outside', array(&$this, 'arm2_square_update_meta_after_renew'), 10, 4);
        
        add_filter('arm_default_plan_array_filter', array(&$this, 'arm2_default_plan_array_filter_func'), 10, 1);
        
        add_filter('arm_need_to_cancel_old_subscription_gateways', array(&$this, 'arm2_need_to_cancel_old_subscription_gateways'), 10, 1);
        
        add_action('arm_payment_gateway_validation_from_setup', array(&$this, 'arm2_payment_gateway_form_submit_action'), 10, 4);
        
        add_action('wp', array(&$this, 'arm2_square_webhook'), 5);
        
        add_action('arm_cancel_subscription_gateway_action', array(&$this, 'arm2_square_cancel_subscription'), 10, 2);

        add_action('arm_enqueue_js_css_from_outside', array(&$this,'arm_enqueue_square_js_css_for_model'),10);

        add_filter('arm_filter_cron_hook_name_outside', array(&$this, 'arm_filter_cron_hook_name_outside_func'), 10);

        //For disable update card button at front end side.
        add_filter( 'arm_display_update_card_button_from_outside', array( $this, 'arm_display_update_card_button'), 10, 3 );
        add_filter( 'arm_render_update_card_button_from_outside', array( $this, 'arm_render_update_card_button'), 10, 6 );
    }
    
    function arm_display_update_card_button( $display, $pg, $planData ){
        if( 'square' == $pg ){
            $display = true;
        }
        return $display;
    }
    
    
    function arm_render_update_card_button(  $content, $pg, $planData, $user_plan, $arm_disable_button, $update_card_text ){
        if( 'square' == $pg ){
            $content .= '';
        }
        return $content;
    }
    

    function arm2_need_to_cancel_old_subscription_gateways( $payment_gateway_array ) {
        array_push($payment_gateway_array, 'square');
        return $payment_gateway_array;
    }
    
    function arm2_default_plan_array_filter_func( $default_plan_array ) {
        global $ARMember;
        $default_plan_array['arm_square'] = '';
        return $default_plan_array;
    }
    
    function arm2_membership_square_update_usermeta($posted_data, $user_id, $plan, $log_detail, $pgateway) {
        if ($pgateway == 'square') {
            $posted_data['arm_square'] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
        }
        return $posted_data;
    }
    
    function arm2_square_update_meta_after_renew($user_id, $log_detail, $plan_id, $payment_gateway) {
        global $ARMember;
        if ($payment_gateway == 'square') {
            if ($user_id != '' && !empty($log_detail) && $plan_id != '' && $plan_id != 0) {
                global $arm_subscription_plans;
                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $plan_data = get_user_meta($user_id, 'arm_user_plan_' . $plan_id, true);
                $plan_data = !empty($plan_data) ? $plan_data : array();
                $plan_data = shortcode_atts($defaultPlanData, $plan_data);
                $pg_subsc_data = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
                $plan_data['arm_2checkout'] = '';
                $plan_data['arm_authorize_net'] = '';
                $plan_data['arm_square'] = '';
                $plan_data['arm_square'] = $pg_subsc_data;
                update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $plan_data);
            }
        }
    }
    
    function arm_square_load_textdomain() {
        load_plugin_textdomain('ARM_SQUARE', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public static function arm_square_db_check() {
        global $arm_square;
        $arm_square_version = get_option('arm_square_version');

        if (!isset($arm_square_version) || $arm_square_version == '')
            $arm_square->install();
    }

    function armsquare_getapiurl() {
            $api_url = 'https://www.arpluginshop.com/';
            return $api_url;
        }
        
    /*
    function upgrade_data_square() {
        global $armnew_square_version;

        if (!isset($armnew_square_version) || $armnew_square_version == "")
            $armnew_square_version = get_option('arm_square_version');

        if (version_compare($armnew_square_version, '1.0', '<')) {
            $path = ARM_SQUARE_DIR . '/upgrade_latest_data_square.php';
            include($path);
        }
    }
    */
    
    function armsquare_get_remote_post_params($plugin_info = "") {
            global $wpdb;
    
            $action = "";
            $action = $plugin_info;
    
            if (!function_exists('get_plugins')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            $plugin_list = get_plugins();
            $site_url = ARM_SQUARE_HOME_URL;
            $plugins = array();
    
            $active_plugins = get_option('active_plugins');
    
            foreach ($plugin_list as $key => $plugin) {
                $is_active = in_array($key, $active_plugins);
    
                //filter for only armember ones, may get some others if using our naming convention
                if (strpos(strtolower($plugin["Title"]), "square") !== false) {
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
        global $arm_square;
        $arm_square_version = get_option('arm_square_version');

        if (!isset($arm_square_version) || $arm_square_version == '') {
            global $wpdb, $arm_square_version;
            update_option('arm_square_version', $arm_square_version);
        }
    }

    
    /*
     * Restrict Network Activation
     */
    public static function arm_square_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }

    public static function uninstall() {
        delete_option('arm_square_version');
    }

    function arm_square_currency_symbol() {
        global $arm_payment_gateways, $arm_global_settings;
        $gateway_options = get_option('arm_payment_gateway_settings');
        $pgoptions = maybe_unserialize($gateway_options);
        $is_sandbox_mode  = isset($pgoptions['square']['square_payment_mode']) ? $pgoptions['square']['square_payment_mode'] : 'sandbox';
        
        if($is_sandbox_mode == 'sandbox')
        {
            $currency_symbol = array(
                'AUD' => '$',
                'BRL' => 'R$',
                'CAD' => '$',
                'CZK' => '&#75;&#269;',
                'DKK' => '&nbsp;&#107;&#114;',
                'EUR' => '&#128;',
                'HKD' => '&#20803;',
                'HUF' => '&#70;&#116;',
                'ILS' => '&#8362;',
                'JPY' => '&#165;',
                'MYR' => '&#82;&#77;',
                'MXN' => '&#36;',
                'TWD' => '&#36;',
                'NZD' => '&#36;',
                'NOK' => '&nbsp;&#107;&#114;',
                'PHP' => '&#8369;',
                'PLN' => '&#122;&#322;',
                'GBP' => '&#163;',
                'RUB' => '&#1088;&#1091;',
                'SGD' => '&#36;',
                'SEK' => '&nbsp;&#107;&#114;',
                'CHF' => '&#67;&#72;&#70;',
                'THB' => '&#3647;',
                'USD' => '$',
                'TRY' => '&#89;&#84;&#76;',
                'INR' => '&#8377;',
            );
        }
        else
        {
            $currency_symbol = array(
                'AUD' => '$',
                'BRL' => 'R$',
                'CAD' => '$',
                'CZK' => '&#75;&#269;',
                'DKK' => '&nbsp;&#107;&#114;',
                'EUR' => '&#128;',
                'HKD' => '&#20803;',
                'HUF' => '&#70;&#116;',
                'ILS' => '&#8362;',
                'JPY' => '&#165;',
                'MYR' => '&#82;&#77;',
                'MXN' => '&#36;',
                'TWD' => '&#36;',
                'NZD' => '&#36;',
                'NOK' => '&nbsp;&#107;&#114;',
                'PHP' => '&#8369;',
                'PLN' => '&#122;&#322;',
                'GBP' => '&#163;',
                'RUB' => '&#1088;&#1091;',
                'SGD' => '&#36;',
                'SEK' => '&nbsp;&#107;&#114;',
                'CHF' => '&#67;&#72;&#70;',
                'THB' => '&#3647;',
                'USD' => '$',
                'TRY' => '&#89;&#84;&#76;',
                'INR' => '&#8377;',
            );
        }
        return $currency_symbol;
    }

    function arm_add_square_payment_gateways($default_payment_gateways) {
        if ($this->is_version_compatible()) {
            global $arm_payment_gateways;
            $default_payment_gateways['square']['gateway_name'] = __('Square', 'ARM_SQUARE');
            return $default_payment_gateways;
        } else {
            return $default_payment_gateways;
        }
    }

    function arm_square_admin_notices() {
        global $pagenow, $arm_slugs;    
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if (!$this->is_armember_support())
                echo "<div class='updated updated_notices'><p>" . __('Square For ARMember plugin requires ARMember Plugin installed and active.', 'ARM_SQUARE') . "</p></div>";

            else if (!$this->is_version_compatible())
                echo "<div class='updated updated_notices'><p>" . __('Square For ARMember plugin requires ARMember plugin installed with version 3.0 or higher.', 'ARM_SQUARE') . "</p></div>";
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
        if ($gateway_name == 'square') {
            return __("You can find Application ID and Access Token in your Square account. To get more details, Please refer this", 'ARM_SQUARE')." <a href='https://developer.squareup.com/apps' target='_blank'>".__("document", 'ARM_SQUARE')."</a>.";
        }
        return $titleTooltip;
    }
    
    function arm_gateway_callback_info_func($apiCallbackUrlInfo, $gateway_name, $gateway_options) {
        if ($gateway_name == 'square') {           
            global $arm_global_settings;
            $apiCallbackUrl = $arm_global_settings->add_query_arg("arm-listener", "arm_square_api", get_home_url() . "/");
            $apiCallbackUrlInfo = __('Please make sure you have set following callback URL in your square account.', 'ARM_SQUARE');
            $callbackTooltip = __('To get more information about how to set callback URL in your square account, please refer this', 'ARM_SQUARE').' <a href="'. ARM_SQUARE_DOC_URL .'" target="_blank">'.__('document', 'ARM_SQUARE').'</a>';
            //$apiCallbackUrlInfo = '<a href="'. ARM_SQUARE_DOC_URL .'" target="_blank">'.__('ARMember Square Documentation', 'ARM_SQUARE').'</a>';
            

            $apiCallbackUrlInfo .= '<i class="arm_helptip_icon armfa armfa-question-circle" title="'.htmlentities($callbackTooltip).'"></i>';
            $apiCallbackUrlInfo .= '<br/><b>' . $apiCallbackUrl . '</b>';
        }
        return $apiCallbackUrlInfo;
    }

    function arm_filter_gateway_names_func($pgname) {
        $pgname['square'] = __('Square', 'ARM_SQUARE');
        return $pgname;
    }

    function arm2_change_pending_gateway_outside($user_pending_pgway,$plan_ID,$user_id){
        global $is_free_manual,$ARMember;
        if( $is_free_manual ){
            $key = array_search('square',$user_pending_pgway);
            unset($user_pending_pgway[$key]);
        }
        return $user_pending_pgway;
    }
    
    function admin_enqueue_script(){
        global $arm_square_version, $arm_slugs;

        if(!empty($arm_slugs->general_settings)) {
            $arm_square_page_array = array($arm_slugs->general_settings);
            $arm_square_action_array = array('payment_options');
            
            if( isset($_REQUEST['page']) && isset($_REQUEST['action']) && (in_array($_REQUEST['page'], $arm_square_page_array) && in_array($_REQUEST['action'], $arm_square_action_array)) ||  (isset($_REQUEST['page']) && $_REQUEST['page']==$arm_slugs->membership_setup)) {
                wp_register_script( 'arm-admin-square', ARM_SQUARE_URL . '/js/arm_admin_square.js', array(), $arm_square_version );
                wp_enqueue_script( 'arm-admin-square' );
                wp_register_style('arm-admin-square-css', ARM_SQUARE_URL . '/css/arm_admin_square.css', array(), $arm_square_version);
                wp_enqueue_style('arm-admin-square-css');

            }    
        }
    }
    
    
    function arm_square_set_front_js( $force_enqueue = false ) {
        if( $this->is_version_compatible() ){
            global $ARMember, $arm_square_version;
            $is_arm_front_page = $ARMember->is_arm_front_page();
            if ($is_arm_front_page === TRUE || $force_enqueue == TRUE){
                wp_register_script('arm_square_js', ARM_SQUARE_URL . '/js/arm_square.js', array('jquery'), $arm_square_version);
                wp_enqueue_script('arm_square_js');

                $gateway_options = get_option('arm_payment_gateway_settings');
                $pgoptions = maybe_unserialize($gateway_options);
                $arm_square_load_js_url = (!empty($pgoptions['square']) && $pgoptions['square']['square_payment_mode'] == "sandbox") ? '/js/arm_squareup_payment_sandbox.js' : '/js/arm_squareup_payment.js';
                wp_register_script( 'arm-admin-square-js', ARM_SQUARE_URL. $arm_square_load_js_url, array(), $arm_square_version );
                //wp_register_script( 'arm-admin-square-js', 'https://js.squareupsandbox.com/v2/paymentform', array(), $arm_square_version );
                wp_enqueue_script( 'arm-admin-square-js' );
            }
        }
    }

    function arm_enqueue_square_js_css_for_model(){
        $this->arm_square_set_front_js(true);
    }
    
    
    function arm_square_recurring_trial($notice) {
        // if need to display any notice related subscription in Add / Edit plan page
        if ($this->is_version_compatible()){
            $notice .= "<span style='margin-bottom:10px;'><b>". __('Square (if Square payment gateway is enabled)','ARM_SQUARE')."</b><br/>";
            $notice .= "<ol style='margin-left:30px;'>";
            $notice .= "<li>".__('Square Payment Gateway does not support auto debit payment method.','ARM_SQUARE')."</li>";
            $notice .= "</ol>";
            $notice .= "</span>";
        } 
        return $notice;
    }

    function arm_payment_allowed_gateways($allowed_gateways, $plan_obj, $plan_options) {
        
        $allowed_gateways['square'] = "1";
        return $allowed_gateways;
    }

    function arm_payment_related_common_message($common_messages) {
        if ($this->is_version_compatible()) {
            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_payment_fail_square"><?php _e('Payment Fail (Square)', 'ARM_SQUARE'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_payment_fail_square]" id="arm_payment_fail_square" value="<?php echo (!empty($common_messages['arm_payment_fail_square']) ) ? $common_messages['arm_payment_fail_square'] : 'Sorry something went wrong while processing payment with Square.'; ?>" />
                </td>
            </tr>
            <?php
        }
    }

    function arm_payment_gateway_has_ccfields_func($pgHasCcFields, $gateway_name, $gateway_options) {
        if ($gateway_name == 'square') {
            return true;
        } else {
            return $pgHasCcFields;
        }
    }

    function arm_square_currency_support($notAllow, $currency) {
        global $arm_payment_gateways;
        $square_currency = $this->arm_square_currency_symbol();
        if (!array_key_exists($currency, $square_currency)) {
            $notAllow[] = 'square';
        }
        return $notAllow;
    }

    function arm_not_display_payment_mode_setup_func($gateway_name_arr) {
        //for remove auto debit payment and manual payment option from front side page and admin site. Its allow only manual payment.
        $gateway_name_arr[] = 'square';
        return $gateway_name_arr;
    }

    function arm_after_payment_gateway_listing_section_func($gateway_name, $gateway_options) {
        // set paymetn geteway setting field in general settgin > payment gateway
        global $arm_global_settings;
        if ($gateway_name == 'square') {
            $gateway_options['square_payment_mode'] = (!empty($gateway_options['square_payment_mode']) ) ? $gateway_options['square_payment_mode'] : 'sandbox';
            $gateway_options['status'] = isset($gateway_options['status']) ? $gateway_options['status'] : 0;
            $disabled_field_attr = ($gateway_options['status'] == '1') ? '' : 'disabled="disabled"';
            $readonly_field_attr = ($gateway_options['status'] == '1') ? '' : 'readonly="readonly"';

            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Payment Mode', 'ARM_SQUARE'); ?> *</label></th>
                <td class="arm-form-table-content">
                    <input id="arm_square_payment_gateway_mode_sand" class="arm_general_input arm_square_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="sandbox" name="payment_gateway_settings[square][square_payment_mode]" <?php checked($gateway_options['square_payment_mode'], 'sandbox'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_square_payment_gateway_mode_sand"><?php _e('Sandbox', 'ARM_SQUARE'); ?></label>
                    <input id="arm_square_payment_gateway_mode_pro" class="arm_general_input arm_square_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="production" name="payment_gateway_settings[square][square_payment_mode]" <?php checked($gateway_options['square_payment_mode'], 'production'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_square_payment_gateway_mode_pro"><?php _e('Production', 'ARM_SQUARE'); ?></label>
                </td>
            </tr>
            <!-- ***** Begining of Sandbox Input for square ***** -->
            <?php
            $square_hidden = "hidden_section";
            if (isset($gateway_options['square_payment_mode']) && $gateway_options['square_payment_mode'] == 'sandbox') {
                $square_hidden = "";
            } else if (!isset($gateway_options['square_payment_mode'])) {
                $square_hidden = "";
            }
            ?>
            <tr class="form-field arm_square_sandbox_fields <?php echo $square_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Test Application ID', 'ARM_SQUARE'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_square_test_application_id" name="payment_gateway_settings[square][square_test_application_id]" value="<?php echo (!empty($gateway_options['square_test_application_id'])) ? $gateway_options['square_test_application_id'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_square_sandbox_fields <?php echo $square_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Test Access Token', 'ARM_SQUARE'); ?> *</th> 
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_square_test_access_token" name="payment_gateway_settings[square][square_test_access_token]" value="<?php echo (!empty($gateway_options['square_test_access_token'])) ? $gateway_options['square_test_access_token'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            
            
            <!-- ***** Ending of Sandbox Input for square ***** -->

            <!-- ***** Begining of Live Input for square ***** -->
            <?php
            $square_live_fields = "hidden_section";
            if (isset($gateway_options['square_payment_mode']) && $gateway_options['square_payment_mode'] == "production") {
                $square_live_fields = "";
            }
            ?>
            <tr class="form-field arm_square_fields <?php echo $square_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live Application ID', 'ARM_SQUARE'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_square_live_application_id" name="payment_gateway_settings[square][square_live_application_id]" value="<?php echo (!empty($gateway_options['square_live_application_id'])) ? $gateway_options['square_live_application_id'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_square_fields <?php echo $square_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live Access Token', 'ARM_SQUARE'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_square_live_access_token" name="payment_gateway_settings[square][square_live_access_token]" value="<?php echo (!empty($gateway_options['square_live_access_token'])) ? $gateway_options['square_live_access_token'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            
            <!-- ***** Ending of Live Input for square ***** -->

            <tr class="form-field">
                <th class="arm-form-table-label"><?php _e('Popup Title', 'ARM_SQUARE'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_square_payment_button_title" name="payment_gateway_settings[square][square_popup_title]" value="<?php echo (!empty($gateway_options['square_popup_title'])) ? $gateway_options['square_popup_title'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                    <i class="arm_helptip_icon armfa armfa-question-circle" title="{arm_selected_plan_title} : <?php _e("This shortcode will be replaced with the user selected plan name.", 'ARM_SQUARE');?>"></i>
                </td>
            </tr>

            <tr class="form-field">
                <th class="arm-form-table-label"><?php _e('Popup Button Title', 'ARM_SQUARE'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_square_payment_button_title" name="payment_gateway_settings[square][square_payment_button_title]" value="<?php echo (!empty($gateway_options['square_payment_button_title'])) ? $gateway_options['square_payment_button_title'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>


            <?php
                /*$arm_preset_form_fields = get_option('arm_preset_form_fields');
            ?>


            <tr class="form-field">
                <th class="arm-form-table-label"><?php _e('Postal Code', 'ARM_TWISPAY'); ?> *</th>
                <td class="arm-form-table-content">
                    <input type='hidden' id='arm_square_postal_code' name="payment_gateway_settings[square][square_postal_code]" value="<?php echo (!empty($gateway_options['square_postal_code'])) ? $gateway_options['square_postal_code'] : 'Postal Code'; ?>" />
                    <dl class="arm_selectbox arm_active_payment_<?php echo strtolower($gateway_name);?>">
                        <dt <?php echo ($gateway_options['status']=='1') ? '' : 'style="border:1px solid #DBE1E8"'; ?>>
                            <span></span>
                            <input type="text" style="display:none;" value="<?php _e('Postal Code', 'ARM_TWISPAY'); ?>" class="arm_autocomplete"/>
                            <i class="armfa armfa-caret-down armfa-lg"></i>
                        </dt>
                        <dd>
                            <ul data-id="arm_square_postal_code">
                                <?php foreach ($arm_preset_form_fields['default'] as $key => $val): ?>
                                    <li data-label="<?php echo $key; ?>" data-value="<?php echo esc_attr($val['meta_key']);?>"><?php echo $val['label']; ?></li>
                                <?php endforeach;?>

                                <?php 
                                    if(!empty($arm_preset_form_fields['other']))
                                    {
                                        foreach ($arm_preset_form_fields['other'] as $key => $val): 
                                ?>
                                            <li data-label="<?php echo $key; ?>" data-value="<?php echo esc_attr($val['meta_key']);?>"><?php echo $val['label']; ?></li>
                                <?php 
                                        endforeach;
                                    }
                                ?>
                            </ul>
                        </dd>
                    </dl>
                </td>
            </tr>

            <?php */
        }
    }

    function arm_square_config() {
        global $arm_payment_gateways, $arm_global_settings;

        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        if (isset($all_payment_gateways['square']) && !empty($all_payment_gateways['square'])) {
            $payment_gateway_options = $all_payment_gateways['square'];
            $ARM_Square_payment_mode = $payment_gateway_options['square_payment_mode'];
            $is_sandbox_mode = $ARM_Square_payment_mode == "sandbox" ? true : false;

            $SquareConfig = array();

            $SquareConfig['environment'] = ( $is_sandbox_mode ) ? "sandbox" : "production"; // production, sandbox

            $SquareConfig['credentials'] = array();
            $SquareConfig['credentials']['secret_key'] = ( $is_sandbox_mode ) ? $payment_gateway_options['square_test_application_id'] : $payment_gateway_options['square_live_application_id'];
            $SquareConfig['credentials']['public_key']['sandbox'] = $payment_gateway_options['square_test_access_token'];
            $SquareConfig['credentials']['public_key']['production'] = $payment_gateway_options['square_live_access_token'];
            

            $SquareConfig['application'] = array();
            $SquareConfig['application']['charset'] = "UTF-8"; // UTF-8, ISO-8859-1

            $SquareConfig['log'] = array();
            $SquareConfig['log']['active'] = false;
            
            $SquareConfig['log']['fileLocation'] = "";

            return $SquareConfig;
        }
    }

    
    function arm2_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {
        
        global $wpdb, $ARMember, $arm_global_settings, $arm_membership_setup, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $paid_trial_square_payment_done, $is_free_manual;
        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        $currency = $arm_payment_gateways->arm_get_global_currency();
        if ($payment_gateway == 'square' && isset($all_payment_gateways['square']) && !empty($all_payment_gateways['square'])) 
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


                $payment_mode_ = !empty($posted_data['arm_payment_mode']['square']) ? $posted_data['arm_payment_mode']['square'] : 'both';

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
                        if(isset($posted_data['arm_payment_mode']['square'])){
                            $payment_mode_ = !empty($posted_data['arm_payment_mode']['square']) ? $posted_data['arm_payment_mode']['square'] : 'manual_subscription';
                            $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                            
                            if($recurring_payment_mode=='auto_debit_subscription')
                            {
                                //echo 'inside_here square_plan_id<br>$plan_id=>'.$plan_id.'cycle_key-->';

                                if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
                                     $payment_cycle_key = $plan->options['payment_cycles'][$payment_cycle]['cycle_key'];
                                     $SquarePlanID = $setup_data['setup_modules']['modules']['square_plans'][$plan_id][$payment_cycle_key];
                                }
                                //exit;
                            }
                        }
                        else{
                            $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                            if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
                                $setup_modules = $setup_data['setup_modules'];
                                $modules = $setup_modules['modules'];
                                $payment_mode_ = $modules['payment_mode']['square'];
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
                        $square_err_msg = '<div class="arm_error_msg"><ul><li>'.__('Square does not support Free trial/plan amount.', 'ARM_SQUARE').'</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $square_err_msg);
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
                
                $arm_square_plan_amount = str_replace(",", "", $plan->amount);

                if(isset($couponApply) && $couponApply["status"] == "success") {
                    $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : '0';
                    if($arm_coupon_on_each_subscriptions=='1')
                    {
                        $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $currency : "%";
                        $coupon_discount_amount = isset($couponApply['discount']) ? $couponApply['discount'] : 0;
                        if($arm_coupon_discount_type=='%')
                        {
                            $coupon_discount_amount = ($arm_square_plan_amount * $coupon_discount_amount) /100;
                            $arm_square_plan_amount = $arm_square_plan_amount - $coupon_discount_amount;
                        }
                        else
                        {
                           $arm_square_plan_amount = $arm_square_plan_amount - $coupon_discount_amount;
                        }
                    }
                }


                //square tax amount
                if($tax_percentage > 0){
                    $square_tax_amount =($arm_square_plan_amount*$tax_percentage)/100;
                    $square_tax_amount = number_format((float)$square_tax_amount, 2, '.','');
                    $arm_square_plan_amount = $arm_square_plan_amount+$square_tax_amount;
                  
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


                $arm_redirecturl = $entry_values['setup_redirect'];
                if (empty($arm_redirecturl)) {
                    $arm_redirecturl = ARM_HOME_URL;
                }


                if ((($discount_amt <= 0 || $discount_amt == '0.00') && $recurring_payment_mode == 'manual_subscription' && $plan->is_recurring()) || (!$plan->is_recurring() && ($discount_amt <= 0 || $discount_amt == '0.00'))) 
                {
                    
                    global $payment_done;
                    $square_response = array();
                    $current_user_id = 0;
                    if (is_user_logged_in()) {
                        $current_user_id = get_current_user_id();
                        $square_response['arm_user_id'] = $current_user_id;
                    }
                    $arm_first_name=(isset($posted_data['first_name']))?$posted_data['first_name']:'';
                    $arm_last_name=(isset($posted_data['last_name']))?$posted_data['last_name']:'';
                    if($user_id){
                        if(empty($arm_first_name)){
                            $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                            $arm_first_name = $user_detail_first_name;
                        }
                        if(empty($arm_last_name)){
                            $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                            $arm_last_name=$user_detail_last_name;
                        }    
                    }
                    $square_response['arm_plan_id'] = $plan->ID;
                    $square_response['arm_first_name']=$arm_first_name;
                    $square_response['arm_last_name']=$arm_last_name;
                    $square_response['arm_payment_gateway'] = 'square';
                    $square_response['arm_payment_type'] = $plan->payment_type;
                    $square_response['arm_token'] = '-';
                    $square_response['arm_payer_email'] = $user_email_add;
                    $square_response['arm_receiver_email'] = '';
                    $square_response['arm_transaction_id'] = '-';
                    $square_response['arm_transaction_payment_type'] = $plan->payment_type;
                    $square_response['arm_transaction_status'] = 'completed';
                    $square_response['arm_payment_mode'] = 'manual_subscription';
                    $square_response['arm_payment_date'] = date('Y-m-d H:i:s');
                    $square_response['arm_amount'] = $amount;
                    $square_response['arm_currency'] = $currency;
                    $square_response['arm_coupon_code'] = $posted_data['arm_coupon_code'];
                    $square_response['arm_response_text'] = '';
                    $square_response['arm_extra_vars'] = '';
                    $square_response['arm_is_trial'] = $arm_is_trial;
                    $square_response['arm_created_date'] = current_time('mysql');
                    $square_response['arm_coupon_discount'] = $arm_coupon_discount;
                    $square_response['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                    $square_response['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;

                    $payment_log_id = $arm_payment_gateways->arm_save_payment_log($square_response);
                    $return = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                    $payment_done = $return;
                    $is_free_manual = true;

                    if($arm_manage_coupons->isCouponFeature && !empty($posted_data['arm_coupon_code']) && !empty($arm_coupon_on_each_subscriptions)) {
                            $payment_done["coupon_on_each"] = TRUE;
                            $payment_done["trans_log_id"] = $payment_log_id;
                    }

                    do_action('arm_after_square_free_payment',$plan,$payment_log_id,$arm_is_trial,$posted_data['arm_coupon_code'],$extraParam);

                    return $return;
                }
                else
                {
                    $extraVars['paid_amount'] = $amount;
                    $data_array['arm_square_entry_id'] = $entry_id;
                    $data_array['currency'] = $currency;
                    $data_array['arm_plan_id'] = $plan_id;
                    $data_array['arm_plan_name'] = $plan_name;
                    $data_array['arm_plan_amount'] = $discount_amt;
                    $data_array['reference'] = 'ref-' . $entry_id.'-'.time();
                    $data_array['redirect_url'] = $arm_square_redirecturl;
                    $data_array['arm_coupon_code'] = $posted_data['arm_coupon_code'];

                    if($pgoptions['square']['square_payment_mode'] == 'sandbox')
                    {
                        $data_array['arm_square_application_id'] = $pgoptions['square']['square_test_application_id'];
                        $data_array['arm_square_access_token'] = $pgoptions['square']['square_test_access_token'];
                    }
                    else
                    {
                        $data_array['arm_square_application_id'] = $pgoptions['square']['square_live_application_id'];
                        $data_array['arm_square_access_token'] = $pgoptions['square']['square_live_access_token'];
                    }
                
                    $data_array['first_name'] = $entry_data['arm_entry_value']['first_name'];
                    $data_array['last_name'] = $entry_data['arm_entry_value']['last_name'];
                    $data_array['user_email'] = $user_email_add;

                    if($recurring_payment_mode == 'auto_debit_subscription' )
                    {
                        $square_err_msg = '<div class="arm_error_msg"><ul><li>'.__('Square does not support subscription payment.', 'ARM_SQUARE').'</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $square_err_msg);
                        echo json_encode($return);
                        exit;
                        /*$arm_square_plan_interval = "";
                        $pg_error_flag = "";
                        if($recurring_data['period']=='D' && $recurring_data['interval']=='1')
                        {
                            $arm_square_plan_interval = 'daily';
                            $pg_error_flag = "1";
                        }
                        else if($recurring_data['period']=='D' && $recurring_data['interval']=='7')
                        {
                            $arm_square_plan_interval = 'weekly';
                            $pg_error_flag = "1";
                        }
                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='1')
                        {
                            $arm_square_plan_interval = 'monthly';
                            $pg_error_flag = "1";
                        }
                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='3')
                        {
                            $arm_square_plan_interval = 'quarterly';
                            $pg_error_flag = "1";
                        }
                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='6')
                        {
                            $arm_square_plan_interval = 'biannually';
                            $pg_error_flag = "1";
                        }
                        else if($recurring_data['period']=='Y' && $recurring_data['interval']=='1')
                        {
                            $arm_square_plan_interval = 'annually';
                            $pg_error_flag = "1";
                        }

                        //recurring time
                        $arm_square_recurring_time = $recurring_data['rec_time'];
                        if($plan->has_trial_period() && $allow_trial)
                        {
                            if($arm_square_recurring_time=='infinite')
                            {
                                $arm_square_recurring_time = 0;
                            }
                            else
                            {
                                $arm_square_recurring_time = $arm_square_recurring_time;
                            }

                        }
                        else
                        {
                            if($arm_square_recurring_time=='infinite')
                            {
                                $arm_square_recurring_time = 0;
                            }
                            else
                            {
                                $arm_square_recurring_time = $arm_square_recurring_time - 1;
                            }
                        }
                        if(empty($pg_error_flag))
                        {
                            $square_err_msg = '<div class="arm_error_msg"><ul><li>' . __('Payment through square is not supported for selected plan.', ARM_square_TEXTDOMAIN) . '</li></ul></div>';
                            $return = array('status' => 'error', 'type' => 'message', 'message' => $square_err_msg);
                            echo json_encode($return);
                            exit;
                        }
                        
                        $arm_square_subscription_amount = $arm_square_plan_amount * 100;

                        $data_array['arm_square_plancode'] = !empty($arm_square_plancode) ? $arm_square_plancode : $squarePlanID;
                        $data_array['arm_square_subscription_code'] = !empty($arm_square_subscription_code) ? $arm_square_subscription_code : '';

                        $data_array['recurring_payment_mode'] = $recurring_payment_mode;
                        $data_array['arm_square_invoice_limit'] = $arm_square_recurring_time;
                        //print_r($plan);
                        $data_array['arm_has_trial_period'] = $plan->has_trial_period();
                        $data_array['arm_square_trail_invoice_limit'] = $recurring_data['trial']['rec_time'];*/
                    }
                }


                $extraVars['paid_amount'] = $discount_amt;
                $data_array['currency'] = $currency;
                $data_array['arm_plan_id'] = $plan_id;
                $data_array['arm_plan_name'] = $plan_name;
                $data_array['arm_plan_amount'] = $discount_amt;
                $data_array['reference'] = 'ref-' . $entry_id;
                $data_array['redirect_url'] = $arm_redirecturl;

                
                
                if($pgoptions['square']['square_payment_mode']=='sandbox')
                {
                    $data_array['arm_square_application_id'] = $pgoptions['square']['square_test_application_id'];
                    $data_array['arm_square_access_token'] = $pgoptions['square']['square_test_access_token'];
                }
                else
                {
                    $data_array['arm_square_application_id'] = $pgoptions['square']['square_test_application_id'];
                    $data_array['arm_square_access_token'] = $pgoptions['square']['square_live_access_token'];
                }

                $data_array['first_name'] = $entry_data['arm_entry_value']['first_name'];
                $data_array['last_name'] = $entry_data['arm_entry_value']['last_name'];
                $data_array['user_email'] = $user_email_add;
                $data_array['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                $data_array['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                $square_response['arm_coupon_discount'] = $arm_coupon_discount;

                $arm_checkout_form_data = array();
                $arm_checkout_form_data['idempotency_key'] = md5(uniqid());
                $arm_checkout_form_data['amount_money'] = implode('*', [
                    'amount'   => $discount_amt,
                    'currency' => $currency
                ]);
                $arm_checkout_form_data['buyer_email_address'] = $user_email_add;
                $arm_checkout_form_data['note'] = $plan_name;
                $arm_checkout_form_data['reference_id'] = $data_array['reference'];
                $arm_checkout_form_data['order_id'] = $entry_id.'_'.time();

                $arm_checkout_form_data = implode('|',$arm_checkout_form_data);

                $arm_square_payment_button_title = $pgoptions['square']['square_payment_button_title'];
                $arm_square_payment_modal_title = get_bloginfo('name');
                if($pgoptions['square']['square_popup_title'] == "{arm_selected_plan_title}")
                {
                    $arm_square_payment_modal_title = $plan_name;
                }
                else
                {
                    if(!empty($pgoptions['square']['square_popup_title']))
                    {
                        $arm_square_payment_modal_title = $pgoptions['square']['square_popup_title'];
                    }
                }




                $arm_checkout_form .= "<style id='arm_square_css'>";
                $arm_checkout_form .= ".arm_setup_form_container iframe{ width: unset !important;border: unset !important;position: unset !important;left: unset !important;height: 4rem !important; }";


                $arm_checkout_form .= ".square_element_wrapper{position:fixed;top:0;left:0;width:100%;height:100%;text-align:center;background:rgba(0,0,0,0.6);z-index:999999;}.square_element_wrapper .form-inner-row{ float: left; width: 300px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #F5F5F7;text-align:left;border-radius:5px;overflow:hidden;}.square_element_wrapper #sq-creditcard,#update-card-button{ background:linear-gradient(#43B0E9,#3299DE); padding:0 !important; font-weight:normal; border:none; color: #fff; display: inline-block; margin-top: 25px; margin-bottom:15px; height: 40px; line-height: normal; float: left; border-radius:4px;width:100%;font-size:20px;}.square_element_wrapper .form-row{ float:left; width: 70%;}.square_element_wrapper iframe{position:relative;left:0}.StripeElement {box-sizing: border-box;height: 40px;padding: 10px 12px;border: 1px solid transparent;border-radius: 4px;background-color: white;box-shadow: 0 1px 3px 0 #e6ebf1;-webkit-transition: box-shadow 150ms ease;transition: box-shadow 150ms ease;}.card-errors{font-size: 14px;color: #ff0000;}.site_info_row {float: left;width: 100%;height: 95px;background: #E8E9EB;border-bottom: 1px solid #DBDBDD;box-sizing: border-box;text-align: center;padding: 25px 10px;}.field_wrapper{float:left;padding:30px;width:100%;box-sizing:border-box;}.form-inner-row .field_wrapper .arm_square_field_row{float:left;width:100%;margin-bottom:10px;}.site_title,.site_tag{float:left;width:100%;text-align:center;font-size:16px;} .site_title{font-weight:bold;}.site_info_row .close_icon{position: absolute;width: 20px;height: 20px;background: #cecccc;right: 10px;top: 10px;border-radius: 20px;cursor:pointer;}.site_info_row .close_icon::before{content: '';width: 12px;height: 2px;background: #fff;display: block;top: 50%;left: 50%;transform: translate(-50%,-50%) rotate(45deg);position: absolute;}.site_info_row .close_icon::after{content: '';width: 12px;height: 2px;background: #fff;display: block;top: 50%;left: 50%;transform: translate(-50%,-50%) rotate(-45deg);position: absolute;}.StripeElement--focus { box-shadow: 0 1px 3px 0 #cfd7df; }.StripeElement--invalid {border-color: #fa755a;}.StripeElement--webkit-autofill {background-color: #fefde5 !important;}.arm_square_loader{float:none;display:inline-block;width:15px;height:15px;border:3px solid #fff;border-radius:15px;border-top:3px solid transparent;margin-right:5px;position:relative;top:3px;display:none;animation:spin infinite 1.5s}@keyframes spin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg)}} #sq-creditcard[disabled],#update-card-button[disabled]{opacity:0.7;} #sq-creditcard[disabled] .arm_square_loader,#update-card-button[disabled] .arm_square_loader{display:inline-block;} #arm_square_error_msg{ color: #f00;font-size: 1.8rem; }";

                $arm_checkout_form .= "</style>";
                
                $arm_checkout_form .= "<div class='square_element_wrapper'>";
                $arm_checkout_form .= "<div class='form-inner-row' data-locale-reversible>";
                $arm_checkout_form .= "<div class='site_info_row'>";
                $arm_checkout_form .= "<div class='site_info'>";
                $arm_checkout_form .= "<div class='site_title'>".$arm_square_payment_modal_title."</div>";
                $arm_checkout_form .= "<div class='close_icon' id='square_wrapper_close_icon' onclick='armSquarePaymentModal()'></div>";
                $arm_checkout_form .= "</div>";
                $arm_checkout_form .= "</div>";
                $arm_checkout_form .= "<div class='field_wrapper'>";
                $arm_checkout_form .= "<div id='arm_square_error_msg'></div>";
                $arm_checkout_form .= "<div id='form-container'>";

                $arm_checkout_form .= "<div class='arm_square_field_row'>";
                $arm_checkout_form .= "<div id='sq-card-number'></div>";
                $arm_checkout_form .= "</div>";

                $arm_checkout_form .= "<div class='arm_square_field_row'>";
                $arm_checkout_form .= "<div class='third' id='sq-expiration-date'></div>";
                $arm_checkout_form .= "</div>";

                $arm_checkout_form .= "<div class='arm_square_field_row'>";
                $arm_checkout_form .= "<div class='third' id='sq-cvv'></div>";
                $arm_checkout_form .= "</div>";

                $arm_checkout_form .= "<div class='arm_square_field_row'>";
                $arm_checkout_form .= "<div class='third' id='sq-postal-code'></div>";
                $arm_checkout_form .= "</div>";

                $arm_checkout_form .= "<button id='sq-creditcard' class='button-credit-card' onclick='armSquareGetCardNonce(event)'>".$arm_square_payment_button_title."</button>";


                $arm_checkout_form .= "</div>";
                $arm_checkout_form .= "<form id='arm_final_payment_form' method='POST' action=''>";
                $arm_checkout_form .= "<input type='hidden' name='arm_square_payment_data' value='".$arm_checkout_form_data."'>";
                $arm_checkout_form .= "<input type='hidden' name='arm-listener' value='arm_square_api'>";
                $arm_checkout_form .= "<input type='hidden' name='arm_payment_nonce' id='arm_payment_nonce' value=''>";
                $arm_checkout_form .= "</form>";
                
                

                
                $arm_main_checkout_form .= '<script type="text/javascript">';
                $arm_main_checkout_form .= 'jQuery(".arm_setup_form_inner_container").after("'.$arm_checkout_form.'");';
                $arm_main_checkout_form .= 'const paymentForm = new SqPaymentForm({';
                $arm_main_checkout_form .= 'applicationId: "'.$data_array['arm_square_application_id'].'",';
                $arm_main_checkout_form .= 'inputClass: "sq-input",';
                $arm_main_checkout_form .= 'autoBuild: false,';
                $arm_main_checkout_form .= 'inputStyles: [{ fontSize: "16px", lineHeight: "24px", padding: "16px", placeholderColor: "#a0a0a0", backgroundColor: "transparent", }],';
                $arm_main_checkout_form .= 'cardNumber: { elementId: "sq-card-number", placeholder: "Card Number" },';
                $arm_main_checkout_form .= 'cvv: { elementId: "sq-cvv", placeholder: "CVV" },';
                $arm_main_checkout_form .= 'expirationDate: { elementId: "sq-expiration-date", placeholder: "MM/YY" },';
                $arm_main_checkout_form .= 'postalCode: { elementId: "sq-postal-code", placeholder: "Postal" },';
                $arm_main_checkout_form .= 'callbacks: { cardNonceResponseReceived: function (errors, nonce, cardData) { if(errors){ jQuery("#arm_square_error_msg").empty(); errors.forEach(function (error){ jQuery("#arm_square_error_msg").append("<span>"+error.message+"</span><br/>"); }); return false; } document.querySelector("#arm_payment_nonce").value = `${nonce}`; setTimeout(function(){ document.getElementById("arm_final_payment_form").submit(); }, 1000); } }';
                $arm_main_checkout_form .= '});';
                $arm_main_checkout_form .= 'function armSquareGetCardNonce(event) {';
                $arm_main_checkout_form .= 'event.preventDefault();';
                $arm_main_checkout_form .= 'paymentForm.requestCardNonce();';
                $arm_main_checkout_form .= '}';
                $arm_main_checkout_form .= 'function armSquarePaymentModal(){';
                $arm_main_checkout_form .= 'jQuery(".square_element_wrapper").remove();';
                $arm_main_checkout_form .= 'jQuery("#arm_square_css").remove();';
                $arm_main_checkout_form .= '}';
                $arm_main_checkout_form .= 'paymentForm.build();';
                $arm_main_checkout_form .= '</script>';

                echo json_encode( array('type' => 'script', 'isHide' => false, 'message' => $arm_main_checkout_form));
                //$return = array('status' => 'success', 'type' => 'message', 'message' => $arm_main_checkout_form);
                //echo json_encode($return);
                exit;
            }
        }
        else
        {

        }
    }

    function arm2_square_webhook($transaction_id = 0, $arm_listener = '', $tran_id = '') {
        global $wpdb, $ARMember, $arm_payment_gateways, $arm_subscription_plans, $arm_members_class, $arm_manage_communication, $wp_version;
        if(isset($_REQUEST['arm-listener']) && in_array($_REQUEST['arm-listener'], array('arm_square_api'))) 
        {   
            //$ARMember->arm_write_response("Square Payment Request Details =>".maybe_serialize($_REQUEST));

            $arm_square_payment_data = explode('|', $_REQUEST['arm_square_payment_data']);
            $arm_square_amount_arr = explode('*', $arm_square_payment_data[1]);

            $arm_checkout_form_data = array();
            $arm_checkout_form_data['source_id'] = $_REQUEST['arm_payment_nonce'];
            $arm_checkout_form_data['idempotency_key'] = $arm_square_payment_data[0];
            $arm_checkout_form_data['amount_money'] = [
                'amount'   => (int)$arm_square_amount_arr[0],
                'currency' => $arm_square_amount_arr[1]
            ];
            $arm_checkout_form_data['buyer_email_address'] = $arm_square_payment_data[2];
            $arm_checkout_form_data['note'] = $arm_square_payment_data[3];
            $arm_checkout_form_data['reference_id'] = $arm_square_payment_data[4];
            //$arm_checkout_form_data['order_id'] = $arm_square_payment_data[5];



            $arm_get_payment_log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$ARMember->tbl_arm_payment_log` WHERE arm_token = %s", $arm_checkout_form_data['idempotency_key']), ARRAY_A );
                
            $arm_log_id = (!empty($arm_get_payment_log['arm_log_id'])) ? $arm_get_payment_log['arm_log_id'] : '';
            $arm_square_user_id = (!empty($arm_get_payment_log['arm_user_id'])) ? $arm_get_payment_log['arm_user_id'] : '';
            $arm_square_plan_id = (!empty($arm_get_payment_log['arm_plan_id'])) ? $arm_get_payment_log['arm_plan_id'] : '';


            //Explode entry from order id from request
            $arm_square_entry_id = (!empty($arm_square_payment_data[5])) ? explode('_', $arm_square_payment_data[5]) : '';
            $arm_square_entry_id = $arm_square_entry_id[0];


            if($arm_log_id == '') 
            {
                $gateway_options = get_option('arm_payment_gateway_settings');
                $pgoptions = maybe_unserialize($gateway_options);
                $arm_square_payment_access_token = ($pgoptions['square']['square_payment_mode'] == 'sandbox') ? $pgoptions['square']['square_test_access_token'] :  $pgoptions['square']['square_live_access_token'];

                $arm_square_payment_url = ($pgoptions['square']['square_payment_mode'] == 'sandbox') ? 'https://connect.squareupsandbox.com/v2/payments' : 'https://connect.squareup.com/v2/payments';

                $arm_square_payment_body = array(
                    'method'  => 'POST',
                    'body'    => wp_json_encode($arm_checkout_form_data),
                    'headers' => [
                        'Authorization'  => 'Bearer '.$arm_square_payment_access_token,
                        'Square-Version' => '2020-05-28',
                        'Content-Type'   => 'application/json'
                    ],
                    'sslverify'   => false
                );

                //$ARMember->arm_write_response("Square Payment Body Details =>".maybe_serialize($arm_square_payment_body));

                
                $arm_square_payment_request = wp_remote_post($arm_square_payment_url, $arm_square_payment_body);

                $arm_square_payment_response = json_decode(wp_remote_retrieve_body($arm_square_payment_request));

                if(isset($arm_square_payment_response->payment))
                {
                    //$ARMember->arm_write_response("Payment Response =>".maybe_serialize($arm_square_payment_response));
                    $arm_square_user_id = $this->arm2_add_user_and_transaction($arm_square_entry_id, $arm_square_payment_response->payment);
                }
                else
                {
                    $square_err_msg = '<div class="arm_error_msg"><ul><li style="text-color: #f00 !important;">'.__('Your payment is failed. Please try again.', 'ARM_SQUARE').'</li></ul></div>';
                    echo $square_err_msg;
                }
            }
        }
    }
    
    

    function arm2_add_user_and_transaction($entry_id = 0, $square_response, $arm_display_log = 1) {
        global $wpdb, $square, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication, $arm_manage_coupons, $payment_done, $paid_trial_square_payment_done, $arm_members_class,$arm_transaction,$arm_membership_setup;
        if (isset($entry_id) && $entry_id != '') {
            $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
            
            if (isset($all_payment_gateways['square']) && !empty($all_payment_gateways['square'])) {
                $options = $all_payment_gateways['square'];
                $square_payment_mode = $options['square_payment_mode'];
                
                $is_sandbox_mode = $square_payment_mode == "sandbox" ? true : false;
                $currency = $arm_payment_gateways->arm_get_global_currency();
                
                

                $entry_data = $wpdb->get_row("SELECT * FROM `" . $ARMember->tbl_arm_entries . "` WHERE `arm_entry_id`='" . $entry_id . "' ", ARRAY_A);
                $entry_values = maybe_unserialize($entry_data['arm_entry_value']);
                $entry_email = isset($entry_data['arm_entry_email']) ? $entry_data['arm_entry_email'] : '';
                $arm_log_plan_id = $entry_data['arm_plan_id'];
                $arm_log_amount = isset($entry_values['arm_total_payable_amount']) ? $entry_values['arm_total_payable_amount'] : '';

                $arm_is_post_entry = !empty($entry_values['arm_is_post_entry']) ? $entry_values['arm_is_post_entry'] : 0;
                $arm_paid_post_id = !empty($entry_values['arm_paid_post_id']) ? $entry_values['arm_paid_post_id'] : 0;
                
                
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
                
                // $ARMember->arm_write_response("Square Response =>".maybe_serialize($user_detail_first_name.' '.$user_detail_last_name));
                // die();
                
                $squareLog['arm_coupon_code'] = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                $squareLog['arm_payment_type'] = $arm_payment_type;
                $squareLog['payment_type'] = $arm_payment_type;
                $squareLog['payment_status'] = $payment_status;
                $squareLog['payer_email'] = $entry_email;
                $squareLog['arm_first_name']   =   $user_detail_first_name;
                $squareLog['arm_last_name']    =   $user_detail_last_name;
                $extraParam['payment_type'] = 'square';
                $extraParam['payment_mode'] = $square_payment_mode;
                $extraParam['arm_is_trial'] = '0';
                $extraParam['subs_id'] = $square_response->order_id;
                $extraParam['trans_id'] = $square_response->reference_id;
                //$cardnumber = $square_response->data->authorization->last4;
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
                        $squareLog['coupon_code'] = $couponCode;
                        $squareLog['arm_coupon_discount'] = $arm_coupon_discount;
                        $squareLog['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                        $squareLog['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                    }
                } 

                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $squareLog['currency'] = $currency;
                $squareLog['payment_amount'] = $discount_amt;
                    
                /* $ARMember->arm_write_response("Arm Form Response =>".maybe_serialize($defaultPlanData));
                die(); */
                
                //$ARMember->arm_write_response("reputelog square form_type =>".$armform->type);
                if(!$user_info && in_array($armform->type, array('registration'))) {
                    $user_id = $arm_member_forms->arm_register_new_member($entry_values, $armform);

                    if (is_numeric($user_id) && !is_array($user_id)) {
                        $arm_square_transaction_meta_detail = array();
                        if ($arm_payment_type == 'subscription') {
                            $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                            $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                            $userPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);
                            
                            $userPlanData['arm_square']['transaction_id'] = $square_response->reference_id;
                            //$userPlanData['arm_square']['arm_square_customer_code'] = $arm_square_customer_code;
                            $userPlanData['arm_square']['arm_square_subscription_code'] = $square_response->order_id;
                            $userPlanData['arm_square']['arm_square_subscription_token'] = $square_response->order_id;
                            //$ARMember->arm_write_response("reputelog square add user transaction 1 =>".maybe_serialize($userPlanData));
                            update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                            $pgateway = 'square';
                            $arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $payment_log_id, $pgateway, $userPlanData);
                        }
                        
                        $arm_square_subscription_transaction_id = $square_response->order_id;
                        
                        $squareLog['arm_square_subscription_code'] = !empty($square_response->order_id) ? $square_response->order_id : '';

                        $squareLog['arm_square_response'] = maybe_serialize($square_response);

                        update_user_meta($user_id, 'arm_entry_id', $entry_id);
                        /**
                         * Send Email Notification for Successful Payment
                         */
                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $entry_plan, 'user_id' => $user_id, 'action' => 'new_subscription'));
                        
                        $payment_log_id = self::arm_store_square_log($squareLog, $user_id, $entry_plan, $extraParam);
                        //$ARMember->arm_write_response("Log Data: =>".maybe_serialize($payment_log_id));
                    }
                } else {
                    $user_id = $user_info->ID;
                    if(!empty($user_id)) {
                        $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $entry_plan, true);
                        $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                        $userPlanData = shortcode_atts($defaultPlanData, $userPlanDatameta);
                        //$ARMember->arm_write_response("reputelog square 8 user_id =>".$user_id);
                        if (!$is_multiple_membership_feature->isMultipleMembershipFeature && !$arm_is_post_entry){
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
                                $old_subscription_id = $oldPlanData['arm_square']['transaction_id'];
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
                                $squareLog['payment_amount'] = $amount_for_tax;
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
                                /*square subscription start*/
                                    $squareLog['arm_payment_mode'] = $payment_mode;
                                    //$ARMember->arm_write_response("reputelog square 3 payment_mode =>".$payment_mode);
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
                                        


                                        $squareLog['arm_payment_mode'] = $payment_mode;
                                        //$ARMember->arm_write_response("Recurring Data =>".maybe_serialize($recurring_data));
                                        $arm_square_plan_interval = "";
                                        $arm_square_plan_date_added_days = '';
                                        $pg_error_flag = "";
                                        if($recurring_data['period']=='D' && $recurring_data['interval']=='1')
                                        {
                                            $arm_square_plan_interval = 'daily';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' days';
                                        }
                                        else if($recurring_data['period']=='D' && $recurring_data['interval']=='7')
                                        {
                                            $arm_square_plan_interval = 'weekly';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' days';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='1')
                                        {
                                            $arm_square_plan_interval = 'monthly';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='3')
                                        {
                                            $arm_square_plan_interval = 'quarterly';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='6')
                                        {
                                            $arm_square_plan_interval = 'biannually';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='Y' && $recurring_data['interval']=='1')
                                        {
                                            $arm_square_plan_interval = 'annually';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' year';
                                        }


                                        $arm_square_subscription_amount = $amount_for_tax * 100;
                                        
                                        $arm_square_plancode = !empty($arm_plan_square_code) ? $arm_plan_square_code : '';
                                    }
                                    /*square subscription end*/
                                update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                $userPlanData['arm_user_gateway'] = 'square';

                                if (!empty($square_response->reference_id)) {
                                    $userPlanData['arm_square']['transaction_id'] = $square_response->reference_id;
                                    $userPlanData['arm_square']['arm_square_subscription_code'] = $square_response->order_id;
                                    $userPlanData['arm_square']['arm_square_subscription_token'] = $square_response->order_id;
                                }
                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                $squareLog['arm_square_subscription_code'] = !empty($square_response->order_id) ? $square_response->order_id : '';
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
                                $old_subscription_id = $oldPlanData['arm_square']['transaction_id'];



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
                                    $squareLog['payment_amount'] = $amount_for_tax;
                                    $squareLog['arm_payment_mode'] = $payment_mode;
                                    /*square subscription start*/
                                    //$ARMember->arm_write_response("reputelog square 5 payment_mode =>".$payment_mode);
                                    if($payment_mode=='auto_debit_subscription')
                                    {
                                        $squareLog['arm_payment_mode'] = $payment_mode;
                                        //$ARMember->arm_write_response("reputelog square 6 payment_mode =>".$payment_mode);
                                        $arm_square_plan_interval = "";
                                        $arm_square_plan_date_added_days = '';
                                        $pg_error_flag = "";
                                        if($recurring_data['period']=='D' && $recurring_data['interval']=='1')
                                        {
                                            $arm_square_plan_interval = 'daily';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' days';
                                        }
                                        else if($recurring_data['period']=='D' && $recurring_data['interval']=='7')
                                        {
                                            $arm_square_plan_interval = 'weekly';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' days';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='1')
                                        {
                                            $arm_square_plan_interval = 'monthly';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='3')
                                        {
                                            $arm_square_plan_interval = 'quarterly';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='M' && $recurring_data['interval']=='6')
                                        {
                                            $arm_square_plan_interval = 'biannually';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' months';
                                        }
                                        else if($recurring_data['period']=='Y' && $recurring_data['interval']=='1')
                                        {
                                            $arm_square_plan_interval = 'annually';
                                            $pg_error_flag = "1";
                                            $arm_square_plan_date_added_days = $recurring_data['interval'].' year';
                                        }


                                        $arm_square_subscription_amount = $amount_for_tax * 100;
                                        $arm_square_plancode = !empty($arm_plan_square_code) ? $arm_plan_square_code : '';
                                    }
                                    /*square subscription end*/

                                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                    $userPlanData['arm_user_gateway'] = 'square';

                                    if (!empty($square_response->reference_id)) {
                                        $userPlanData['arm_square']['transaction_id'] = $square_response->reference_id;
                                        $userPlanData['arm_square']['arm_square_customer_code'] = $arm_square_customer_code;
                                        $userPlanData['arm_square']['arm_square_subscription_code'] = $square_response->order_id;
                                        $userPlanData['arm_square']['arm_square_subscription_token'] = $square_response->order_id;
                                        $squareLog['arm_square_subscription_code'] = $square_response->order_id;
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
                                    $squareLog['payment_amount'] = $amount_for_tax;

                                update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                $userPlanData['arm_user_gateway'] = 'square';

                                if (!empty($arm_token)) {
                                    $userPlanData['arm_square']['transaction_id'] = $arm_token;
                                }
                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                if ($is_update_plan) {
                                    $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan,  '', true, $arm_last_payment_status);
                                } else {
                                   $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'new_subscription');
                                }
                            }
                        }
                        
                        $squareLog['arm_square_response'] = maybe_serialize($square_response);
                        
                        $payment_log_id = self::arm_store_square_log($squareLog, $user_id, $entry_plan, $extraParam, $arm_display_log);

                        if ($arm_payment_type == 'subscription') {
                            if($plan_action=='recurring_payment')
                            {
                                $user_subsdata = isset($planData['arm_square']) ? $planData['arm_square'] : array();
                                do_action('arm_after_recurring_payment_success_outside',$user_id,$entry_plan,'square',$payment_mode,$user_subsdata);
                            }
                            //$ARMember->arm_write_response("square subscription 1 arm_payment_type ".$arm_payment_type);
                            $pgateway = 'square';
                            $arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $payment_log_id, $pgateway, $userPlanData);
                        }
                    }
                }
            }
        }
        return $user_id;
    }
    
    function arm_store_square_log($square_response = '', $user_id = 0, $plan_id = 0, $extraVars = array(), $arm_display_log = '1') {

        global $wpdb, $ARMember, $arm_global_settings, $arm_member_forms, $arm_payment_gateways;
        $payment_log_table = $ARMember->tbl_arm_payment_log;
        $arm_square_response = maybe_unserialize($square_response['arm_square_response']);
        
        //$ARMember->arm_write_response("Square Response ". maybe_serialize($square_response));
        
        $arm_square_transaction_id = !empty($extraVars['trans_id']) ? $extraVars['trans_id'] : '';
        $arm_square_subscription_id = !empty($square_response['arm_square_subscription_code']) ? $square_response['arm_square_subscription_code']: '';
        $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $arm_square_transaction_id));
        
        //$ARMember->arm_write_response("Square Response Data: =>".maybe_serialize($square_response));
        
        
        if (!empty($square_response) && empty($transaction)) {
            $payment_data = array(
                'arm_user_id' => $user_id,
                'arm_first_name'=>$square_response['arm_first_name'],
                'arm_last_name'=>$square_response['arm_last_name'],
                'arm_plan_id' => (!empty($plan_id) ? $plan_id : 0),
                'arm_payment_gateway' => 'square',
                'arm_payment_type' => $square_response['arm_payment_type'],
                'arm_token' => $arm_square_subscription_id,
                'arm_payer_email' => $square_response['payer_email'],
                'arm_receiver_email' => '',
                'arm_transaction_id' => $arm_square_transaction_id,
                'arm_transaction_payment_type' => $square_response['payment_type'],
                'arm_transaction_status' => $square_response['payment_status'],
                'arm_payment_date' => date('Y-m-d H:i:s', strtotime($arm_square_response->created_at)),
                'arm_payment_mode' => $arm_square_response->source_type,
                'arm_amount' => str_replace(',', '', $square_response['payment_amount']),
                'arm_currency' => $square_response['currency'],
                'arm_coupon_code' => $square_response['arm_coupon_code'],
                'arm_coupon_discount' => (isset($square_response['arm_coupon_discount']) && !empty($square_response['arm_coupon_discount'])) ? $square_response['arm_coupon_discount'] : 0,
                'arm_coupon_discount_type' => isset($square_response['arm_coupon_discount_type']) ? $square_response['arm_coupon_discount_type'] : '',
                'arm_response_text' => maybe_serialize($arm_square_response),
                'arm_extra_vars' => maybe_serialize($extraVars),
                'arm_is_trial' => isset($square_response['arm_is_trial']) ? $square_response['arm_is_trial'] : 0,
                'arm_display_log' => $arm_display_log,
                'arm_created_date' => current_time('mysql'),
                'arm_coupon_on_each_subscriptions' => !empty($square_response['arm_coupon_on_each_subscriptions']) ? $square_response['arm_coupon_on_each_subscriptions'] : 0,
            );
            $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
            //$ARMember->arm_write_response("Payment Log ID => ".maybe_serialize($payment_log_id));
            return $payment_log_id;
        }
        return false;
    }

    
    function arm2_square_cancel_subscription($user_id, $plan_id){
        global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
        if (isset($user_id) && $user_id != 0 && isset($plan_id) && $plan_id != 0) {
            $user_detail = get_userdata($user_id);
            $payer_email = $user_detail->user_email;
            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
            $currency = $arm_payment_gateways->arm_get_global_currency();
            //$ARMember->arm_write_response("reputelog square cancel 1 planData => ".maybe_serialize($planData));
            if(!empty($planData)){
                $user_payment_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
                if (strtolower($user_payment_gateway) == 'square') 
                {
                    $user_square_data = $planData['arm_square'];
                    //$ARMember->arm_write_response("reputelog square  cancel 2 planData => ".maybe_serialize($planData));
                    //die;

                    $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                    $square_transaction_id = isset($user_square_data['transaction_id']) ? $user_square_data['transaction_id'] : '';
                    $arm_square_customer_code = isset($user_square_data['arm_square_customer_code']) ? $user_square_data['arm_square_customer_code'] : '';

                    $arm_square_subscription_code = isset($user_square_data['arm_square_subscription_code']) ? $user_square_data['arm_square_subscription_code'] : '';

                    $arm_square_subscription_token = isset($user_square_data['arm_square_subscription_token']) ? $user_square_data['arm_square_subscription_token'] : '';
                            
                    $planDetail = $planData['arm_current_plan_detail'];

                    if (!empty($planDetail)) { 
                        $planObj = new ARM_Plan(0);
                        $planObj->init((object) $planDetail);
                    } else {
                        $planObj = new ARM_Plan($plan_id);
                    }

                    $payment_log_table = $ARMember->tbl_arm_payment_log;
                    $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type,arm_amount FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s AND `arm_display_log` = %d ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'square', 'success', 1));
                    
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
                        $square_options = $pgoptions['square'];
                        if($square_options['square_payment_mode']=='sandbox')
                        {
                            $square_secret_key  = $square_options['square_test_application_id'];
                        }
                        else
                        {
                            $square_secret_key  = $square_options['square_live_application_id'];
                        }
                        //$ARMember->arm_write_response("reputelog square cancel square_secret_key => ".maybe_serialize($square_secret_key));
                        
                        //$ARMember->arm_write_response("reputelog square extra_var => ".maybe_serialize($extra_var));
                        
                        if ($payment_type == 'square') {
                            if($user_selected_payment_mode == 'auto_debit_subscription') {
                                //$ARMember->arm_write_response("If Condition User Id & Plan ID => ".maybe_serialize($user_id." ".$plan_id));
                                $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                                $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                $payment_data = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan_id,
                                    'arm_first_name'=> $user_detail_first_name,
                                    'arm_last_name'=> $user_detail_last_name,
                                    'arm_payment_gateway' => 'square',
                                    'arm_payment_type' => 'subscription',
                                    'arm_token' => $arm_square_subscription_code,
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
                                //$ARMember->arm_write_response("Else Condition User Id & Plan ID => ".maybe_serialize($user_id." ".$plan_id));
                                $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);

                                $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                $payment_data = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan_id,
                                    'arm_first_name'=> $user_detail_first_name,
                                    'arm_last_name'=> $user_detail_last_name,
                                    'arm_payment_gateway' => 'square',
                                    'arm_payment_type' => 'subscription',
                                    'arm_payer_email' => $payer_email,
                                    'arm_receiver_email' => '',
                                    'arm_transaction_id' => $transaction->arm_transaction_id,
                                    'arm_token' => $arm_square_subscription_code,
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

    function arm_filter_cron_hook_name_outside_func($cron_hook_array){
        $cron_hook_array[] = 'arm_membership_square_recurring_payment';
        return $cron_hook_array;
    }
    
}

global $arm_square;
$arm_square = new ARM_Square();


global $armsquare_api_url, $armsquare_plugin_slug;

$armsquare_api_url = $arm_square->armsquare_getapiurl();
$armsquare_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armsquare_check_for_plugin_update');

function armsquare_check_for_plugin_update($checked_data) {
    global $armsquare_api_url, $armsquare_plugin_slug, $wp_version, $arm_square_version,$arm_square;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armsquare_plugin_slug,
        'version' => $arm_square_version,
        'other_variables' => $arm_square->armsquare_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(ARM_SQUARE_HOME_URL)
        ),
        'user-agent' => 'ARMSQUARE-WordPress/' . $wp_version . '; ' . ARM_SQUARE_HOME_URL
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armsquare_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armsquare_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armsquare_plugin_slug . '/' . $armsquare_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armsquare_plugin_api_call', 10, 3);

function armsquare_plugin_api_call($def, $action, $args) {
    global $armsquare_plugin_slug, $armsquare_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armsquare_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armsquare_plugin_slug . '/' . $armsquare_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armsquare_update_token'),
            'request' => serialize($args),
            'api-key' => md5(ARM_SQUARE_HOME_URL)
        ),
        'user-agent' => 'ARMSQUARE-WordPress/' . $wp_version . '; ' . ARM_SQUARE_HOME_URL
    );

    $request = wp_remote_post($armsquare_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', 'ARM_SQUARE'), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', 'ARM_SQUARE'), $request['body']);
    }

    return $res;
}

class SquareConfigWrapper {

    public static function getConfig() {
        global $arm_square;
        return $arm_square->arm_square_config();
    }

}
?>