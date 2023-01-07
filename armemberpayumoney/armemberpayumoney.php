<?php 
/*
  Plugin Name: ARMember - PayUmoney payment gateway Addon
  Description: Extension for ARMember plugin to accept payments using PayUmoney.
  Version: 1.5
  Plugin URI: https://www.armemberplugin.com
  Author: Repute InfoSystems
  Author URI: https://www.armemberplugin.com
 */

define('ARM_PAYUMONEY_DIR_NAME', 'armemberpayumoney');
define('ARM_PAYUMONEY_DIR', WP_PLUGIN_DIR . '/' . ARM_PAYUMONEY_DIR_NAME);

if (is_ssl()) {
    define('ARM_PAYUMONEY_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_PAYUMONEY_DIR_NAME));
} else {
    define('ARM_PAYUMONEY_URL', WP_PLUGIN_URL . '/' . ARM_PAYUMONEY_DIR_NAME);
}

define('ARM_PAYUMONEY_TEXTDOMAIN', 'ARM_PAYUMONEY');

define('ARM_PAYUMONEY_DOC_URL', ARM_PAYUMONEY_URL . '/documentation/index.html#content');

global $arm_payumoney_version;
$arm_payumoney_version = '1.5';

global $armnew_payumoney_version;


global $armpayumoney_api_url, $armpayumoney_plugin_slug, $wp_version;

class ARM_Payumoney{
    
    function __construct() {
        global $arm_payment_gateways, $arm_transaction;
        $arm_payment_gateways->currency['payumoney'] = $this->arm_payumoney_currency_symbol();

        add_action('init', array(&$this, 'arm_payumoney_db_check'));

        register_activation_hook(__FILE__, array('ARM_Payumoney', 'install'));

        register_activation_hook(__FILE__, array('ARM_Payumoney', 'arm_payumoney_check_network_activation'));

        register_uninstall_hook(__FILE__, array('ARM_Payumoney', 'uninstall'));

        add_filter('arm_get_payment_gateways', array(&$this, 'arm_add_payumoney_payment_gateways'));
        add_filter('arm_get_payment_gateways_in_filters', array(&$this, 'arm_add_payumoney_payment_gateways'));
        add_action('admin_notices', array(&$this, 'arm_payumoney_admin_notices'));
        add_filter('arm_change_payment_gateway_tooltip', array(&$this, 'arm_change_payment_gateway_tooltip_func'), 10, 3);
        add_filter('arm_gateway_callback_info', array(&$this, 'arm_gateway_callback_info_func'), 10, 3);
        add_filter('arm_filter_gateway_names', array(&$this, 'arm_filter_gateway_names_func'), 10);
        
        //add_action('arm_show_payment_gateway_recurring_notice', array(&$this, 'arm_show_payment_gateway_payumoney_recurring_notice'), 10);
        add_filter('arm_set_gateway_warning_in_plan_with_recurring', array(&$this, 'arm_payumoney_recurring_trial'), 10);
        add_filter('arm_allowed_payment_gateways', array(&$this, 'arm_payment_allowed_gateways'), 10, 3);
        add_action('arm_payment_related_common_message', array(&$this, 'arm_payment_related_common_message'), 10);

        add_filter('arm_currency_support', array(&$this, 'arm_payumoney_currency_support'), 10, 2);
        add_filter('arm_not_display_payment_mode_setup', array(&$this, 'arm_not_display_payment_mode_setup_func'), 10, 1);

        add_action('arm_after_payment_gateway_listing_section', array(&$this, 'arm_after_payment_gateway_listing_section_func'), 10, 2);

        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_script'), 10);
        add_action('wp_head', array(&$this, 'arm_payumoney_set_front_js'), 10);
        
        add_action('plugins_loaded', array(&$this, 'arm_payumoney_load_textdomain'));
		
        add_action('admin_init', array(&$this, 'upgrade_data_payumoney'));
		
	    add_filter('arm_change_coupon_code_outside_from_payumoney',array(&$this,'arm_payumoney_modify_coupon_code'),10,5);
        
        add_filter('arm_change_pending_gateway_outside',array(&$this,'arm2_change_pending_gateway_outside'),100,3);
        
        add_filter('arm_membership_update_user_meta_from_outside', array(&$this, 'arm2_membership_payumoney_update_usermeta'), 10, 5);
        
        add_action('arm_update_user_meta_after_renew_outside', array(&$this, 'arm2_payumoney_update_meta_after_renew'), 10, 4);
        
        add_filter('arm_default_plan_array_filter', array(&$this, 'arm2_default_plan_array_filter_func'), 10, 1);
        
        add_filter('arm_need_to_cancel_old_subscription_gateways', array(&$this, 'arm2_need_to_cancel_old_subscription_gateways'), 10, 1);
        
        add_action('arm_payment_gateway_validation_from_setup', array(&$this, 'arm2_payment_gateway_form_submit_action'), 10, 4);
        
        add_action('wp', array(&$this, 'arm2_payumoney_webhook'), 5);
        
        add_action('arm_cancel_subscription_gateway_action', array(&$this, 'arm2_payumoney_cancel_subscription'), 10, 2);

        add_action('arm_enqueue_js_css_from_outside', array(&$this,'arm_enqueue_payumoney_js_css_for_model'),10);
    }
    
    

    function arm2_need_to_cancel_old_subscription_gateways( $payment_gateway_array ) {
        array_push($payment_gateway_array, 'payumoney');
        return $payment_gateway_array;
    }
    
    function arm2_default_plan_array_filter_func( $default_plan_array ) {
        global $ARMember;
        $default_plan_array['arm_payumoney'] = '';
        return $default_plan_array;
    }
    
    function arm2_membership_payumoney_update_usermeta($posted_data, $user_id, $plan, $log_detail, $pgateway) {
        if ($pgateway == 'payumoney') {
            $posted_data['arm_payumoney'] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
        }
        return $posted_data;
    }
    
    function arm2_payumoney_update_meta_after_renew($user_id, $log_detail, $plan_id, $payment_gateway) {
        global $ARMember;
        if ($payment_gateway == 'payumoney') {
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
                $plan_data['arm_payumoney'] = $pg_subsc_data;
                update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $plan_data);
            }
        }
    }
    
    function arm_payumoney_load_textdomain() {
        load_plugin_textdomain(ARM_PAYUMONEY_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public static function arm_payumoney_db_check() {
        global $arm_payumoney;
        $arm_payumoney_version = get_option('arm_payumoney_version');

        if (!isset($arm_payumoney_version) || $arm_payumoney_version == '')
            $arm_payumoney->install();
    }

	function armpayumoney_getapiurl() {
			$api_url = 'https://www.arpluginshop.com/';
			return $api_url;
		}
		
	function upgrade_data_payumoney() {
		global $armnew_payumoney_version;

		if (!isset($armnew_payumoney_version) || $armnew_payumoney_version == "")
			$armnew_payumoney_version = get_option('arm_payumoney_version');

        if (version_compare($armnew_payumoney_version, '1.5', '<')) {
			$path = ARM_PAYUMONEY_DIR . '/upgrade_latest_data_payumoney.php';
			include($path);
		}
	}
	
	function armpayumoney_get_remote_post_params($plugin_info = "") {
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
				if (strpos(strtolower($plugin["Title"]), "armemberpayumoney") !== false) {
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
        global $arm_payumoney;
        $arm_payumoney_version = get_option('arm_payumoney_version');

        if (!isset($arm_payumoney_version) || $arm_payumoney_version == '') {
            global $wpdb, $arm_payumoney_version;
            update_option('arm_payumoney_version', $arm_payumoney_version);
        }
    }

    
    /*
     * Restrict Network Activation
     */
    public static function arm_payumoney_check_network_activation($network_wide) {
        if (!$network_wide)
            return;

        deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

        header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
        exit;
    }
    
    public static function uninstall() {
        delete_option('arm_payumoney_version');
    }

    function arm_payumoney_currency_symbol() {
        $currency_symbol = array(
            'INR' => '&#8377;',
        );
        return $currency_symbol;
    }

    function arm_add_payumoney_payment_gateways($default_payment_gateways) {
        if ($this->is_version_compatible()) {
            global $arm_payment_gateways;
            $default_payment_gateways['payumoney']['gateway_name'] = __('PayUmoney', ARM_PAYUMONEY_TEXTDOMAIN);
            return $default_payment_gateways;
        } else {
            return $default_payment_gateways;
        }
    }

    function arm_payumoney_admin_notices() {
        global $pagenow, $arm_slugs;    
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if (!$this->is_armember_support())
                echo "<div class='updated updated_notices'><p>" . __('PayUmoney For ARMember plugin requires ARMember Plugin installed and active.', ARM_PAYUMONEY_TEXTDOMAIN) . "</p></div>";

            else if (!$this->is_version_compatible())
                echo "<div class='updated updated_notices'><p>" . __('PayUmoney For ARMember plugin requires ARMember plugin installed with version 3.0 or higher.', ARM_PAYUMONEY_TEXTDOMAIN) . "</p></div>";
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
        if ($gateway_name == 'payumoney') {
            return __("You can find merchant key and merchant salt key in your PayUmoney account. To get more details, Please refer this", ARM_PAYUMONEY_TEXTDOMAIN)." <a href='https://www.payumoney.com/merchant-dashboard/#/integration' target='_blank'>".__("document", ARM_PAYUMONEY_TEXTDOMAIN)."</a>.";
        }
        return $titleTooltip;
    }
    
    function arm_gateway_callback_info_func($apiCallbackUrlInfo, $gateway_name, $gateway_options) {
        if ($gateway_name == 'payumoney') {
            global $arm_global_settings;
            $apiCallbackUrl = $arm_global_settings->add_query_arg("arm-listener", "arm_payumoney_api", get_home_url() . "/");
            //$apiCallbackUrlInfo = __('Please make sure you have set following notification URL in your payumoney account.', ARM_PAYUMONEY_TEXTDOMAIN);
            //$callbackTooltip = __('To get more information about how to set notification URL in your payumoney account, please refer this', ARM_PAYUMONEY_TEXTDOMAIN).' <a href="'. ARM_PAYUMONEY_DOC_URL .'" target="_blank">'.__('document', ARM_PAYUMONEY_TEXTDOMAIN).'</a>';
            $apiCallbackUrlInfo = '<a href="'. ARM_PAYUMONEY_DOC_URL .'" target="_blank">'.__('ARMember PayUmoney Documentation', ARM_PAYUMONEY_TEXTDOMAIN).'</a>';
            

            //$apiCallbackUrlInfo .= '<i class="arm_helptip_icon armfa armfa-question-circle" title="'.htmlentities($callbackTooltip).'"></i>';
            //$apiCallbackUrlInfo .= '<br/><b>' . $apiCallbackUrl . '</b>';
            
        }
        return $apiCallbackUrlInfo;
    }

    function arm_filter_gateway_names_func($pgname) {
        $pgname['payumoney'] = __('PayUmoney', ARM_PAYUMONEY_TEXTDOMAIN);
        return $pgname;
    }

    function arm2_change_pending_gateway_outside($user_pending_pgway,$plan_ID,$user_id){
        global $is_free_manual,$ARMember;
        if( $is_free_manual ){
            $key = array_search('payumoney',$user_pending_pgway);
            unset($user_pending_pgway[$key]);
        }
        return $user_pending_pgway;
    }
    
    function admin_enqueue_script(){
        global $arm_payumoney_version,$arm_slugs;
        if(!empty($arm_slugs->general_settings))
        {
            $arm_payumoney_page_array = array($arm_slugs->general_settings);
            $arm_payumoney_action_array = array('payment_options');
            if( isset($_REQUEST['page']) && isset($_REQUEST['action']) && in_array($_REQUEST['page'], $arm_payumoney_page_array) && in_array($_REQUEST['action'], $arm_payumoney_action_array) ) {
                wp_register_script( 'arm-admin-payumoney', ARM_PAYUMONEY_URL . '/js/arm_admin_payumoney.js', array(), $arm_payumoney_version );
                wp_enqueue_script( 'arm-admin-payumoney' );    
            }
        }        
    }
    
    function arm_payumoney_set_front_js( $force_enqueue = false ) {
        if( $this->is_version_compatible() ){
            global $ARMember, $arm_payumoney_version;
            $is_arm_front_page = $ARMember->is_arm_front_page();
            if ($is_arm_front_page === TRUE || $force_enqueue == TRUE){
                wp_register_script('arm_payumoney_js', ARM_PAYUMONEY_URL . '/js/arm_payumoney.js', array('jquery'), $arm_payumoney_version);
                wp_enqueue_script('arm_payumoney_js');
            }
        }
    }

    function arm_enqueue_payumoney_js_css_for_model(){
        $this->arm_payumoney_set_front_js(true);
    }
    
    //function arm_show_payment_gateway_payumoney_recurring_notice($plan_details) {
        // when setup payment geteway from config or signup plan page and if any plan type not support in payment then display note.
        /* $display = "display:none;";
          $plans = array();
          if (!empty($plan_details) && $this->is_version_compatible()) {
          foreach ($plan_details as $plan_detail) {
          if (isset($plan_detail['arm_subscription_plan_options']) && @$plan_detail['arm_subscription_plan_options']['payment_type'] == 'subscription' && $plan_detail['arm_subscription_plan_options']['trial']['amount'] > 0) {
          $display = "";
          array_push($plans, $plan_detail['arm_subscription_plan_name']);
          }
          }
          }
          ?><span class="arm_invalid" id="arm_payumoney_warning" style="<?php echo $display; ?>"><?php _e("NOTE: payumoney method will be hidden when unsupported plan is selected.", ARM_PAYUMONEY_TEXTDOMAIN); ?> (<span class="arm_payumoney_not_support_plans" style="font-weight:bold;"><?php echo implode(',', $plans); ?></span>)</span><?php */
    //}

    function arm_payumoney_recurring_trial($notice) {
        // if need to display any notice related subscription in Add / Edit plan page
        if ($this->is_version_compatible()){
            $notice .= "<span style='margin-bottom:10px;'><b>". __('PayUmoney (if PayUmoney payment gateway is enabled)',ARM_PAYUMONEY_TEXTDOMAIN)."</b><br/>";
            $notice .= "<ol style='margin-left:30px;'>";
            $notice .= "<li>".__('PayUmoney does not support auto recurring billing cycle.',ARM_PAYUMONEY_TEXTDOMAIN)."</li>";
            $notice .= "</ol>";
            $notice .= "</span>";
        } 
        return $notice;
    }

    function arm_payment_allowed_gateways($allowed_gateways, $plan_obj, $plan_options) {
        // if any subscription not able to do in the payment gatewey
        //if ($plan_obj->payment_type == 'subscription') {
        //    if ($plan_obj->plan_detail->arm_subscription_plan_options['trial']['amount'] > 0) {
        //        $allowed_gateways['payumoney'] = "0";
        //    } else {
        //        $allowed_gateways['payumoney'] = "0";
        //    }
        //} else {
        //    $allowed_gateways['payumoney'] = "1";
        //}

        $allowed_gateways['payumoney'] = "1";
        return $allowed_gateways;
    }

    function arm_payment_related_common_message($common_messages) {
        if ($this->is_version_compatible()) {
            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_payment_fail_payumoney"><?php _e('Payment Fail (PayUmoney)', ARM_PAYUMONEY_TEXTDOMAIN); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_payment_fail_payumoney]" id="arm_payment_fail_payumoney" value="<?php echo (!empty($common_messages['arm_payment_fail_payumoney']) ) ? $common_messages['arm_payment_fail_payumoney'] : 'Sorry something went wrong while processing payment with PayUmoney.'; ?>" />
                </td>
            </tr>
            <?php
        }
    }

    function arm_payment_gateway_has_ccfields_func($pgHasCcFields, $gateway_name, $gateway_options) {
        if ($gateway_name == 'payumoney') {
            return true;
        } else {
            return $pgHasCcFields;
        }
    }

    function arm_payumoney_currency_support($notAllow, $currency) {
        global $arm_payment_gateways;
        if (!array_key_exists($currency, $arm_payment_gateways->currency['payumoney'])) {
            $notAllow[] = 'payumoney';
        }
        return $notAllow;
    }

    function arm_not_display_payment_mode_setup_func($gateway_name_arr) {
        //for remove auto debit payment and menual payment option from front side page and admin site. Its allow only manual payment.
        $gateway_name_arr[] = 'payumoney';
        return $gateway_name_arr;
    }

    function arm_after_payment_gateway_listing_section_func($gateway_name, $gateway_options) {
        // set paymetn geteway setting field in general settgin > payment gateway
        global $arm_global_settings;
        if ($gateway_name == 'payumoney') {
            $gateway_options['payumoney_payment_mode'] = (!empty($gateway_options['payumoney_payment_mode']) ) ? $gateway_options['payumoney_payment_mode'] : 'sandbox';
            $gateway_options['status'] = isset($gateway_options['status']) ? $gateway_options['status'] : 0;
            $disabled_field_attr = ($gateway_options['status'] == '1') ? '' : 'disabled="disabled"';
            $readonly_field_attr = ($gateway_options['status'] == '1') ? '' : 'readonly="readonly"';
            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Payment Mode', ARM_PAYUMONEY_TEXTDOMAIN); ?> *</label></th>
                <td class="arm-form-table-content">
                    <input id="arm_payumoney_payment_gateway_mode_sand" class="arm_general_input arm_payumoney_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="sandbox" name="payment_gateway_settings[payumoney][payumoney_payment_mode]" <?php checked($gateway_options['payumoney_payment_mode'], 'sandbox'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_payumoney_payment_gateway_mode_sand"><?php _e('Sandbox', ARM_PAYUMONEY_TEXTDOMAIN); ?></label>
                    <input id="arm_payumoney_payment_gateway_mode_pro" class="arm_general_input arm_payumoney_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="production" name="payment_gateway_settings[payumoney][payumoney_payment_mode]" <?php checked($gateway_options['payumoney_payment_mode'], 'production'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_payumoney_payment_gateway_mode_pro"><?php _e('Production', ARM_PAYUMONEY_TEXTDOMAIN); ?></label>
                </td>
            </tr>
            <!-- ***** Begining of Sandbox Input for payumoney ***** -->
            <?php
            $payumoney_hidden = "hidden_section";
            if (isset($gateway_options['payumoney_payment_mode']) && $gateway_options['payumoney_payment_mode'] == 'sandbox') {
                $payumoney_hidden = "";
            } else if (!isset($gateway_options['payumoney_payment_mode'])) {
                $payumoney_hidden = "";
            }
            ?>
            <tr class="form-field arm_payumoney_sandbox_fields <?php echo $payumoney_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Sandbox Merchant Key', ARM_PAYUMONEY_TEXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payumoney_sandbox_merchant_key" name="payment_gateway_settings[payumoney][payumoney_sandbox_merchant_key]" value="<?php echo (!empty($gateway_options['payumoney_sandbox_merchant_key'])) ? $gateway_options['payumoney_sandbox_merchant_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payumoney_sandbox_fields <?php echo $payumoney_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Sandbox Merchant Salt', ARM_PAYUMONEY_TEXTDOMAIN); ?> *</th> 
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payumoney_sandbox_salt" name="payment_gateway_settings[payumoney][payumoney_sandbox_salt]" value="<?php echo (!empty($gateway_options['payumoney_sandbox_salt'])) ? $gateway_options['payumoney_sandbox_salt'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            
            <!-- ***** Ending of Sandbox Input for payumoney ***** -->

            <!-- ***** Begining of Live Input for payumoney ***** -->
            <?php
            $payumoney_live_fields = "hidden_section";
            if (isset($gateway_options['payumoney_payment_mode']) && $gateway_options['payumoney_payment_mode'] == "production") {
                $payumoney_live_fields = "";
            }
            ?>
            <tr class="form-field arm_payumoney_fields <?php echo $payumoney_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Production Merchant Key', ARM_PAYUMONEY_TEXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payumoney_production_merchant_key" name="payment_gateway_settings[payumoney][payumoney_merchant_key]" value="<?php echo (!empty($gateway_options['payumoney_merchant_key'])) ? $gateway_options['payumoney_merchant_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payumoney_fields <?php echo $payumoney_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Production Merchant Salt', ARM_PAYUMONEY_TEXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payumoney_production_token" name="payment_gateway_settings[payumoney][payumoney_salt]" value="<?php echo (!empty($gateway_options['payumoney_salt'])) ? $gateway_options['payumoney_salt'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            
            <!-- ***** Ending of Live Input for payumoney ***** -->

            <?php
        }
    }

    function arm_payumoney_config() {
        global $arm_payment_gateways, $arm_global_settings;

        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        if (isset($all_payment_gateways['payumoney']) && !empty($all_payment_gateways['payumoney'])) {
            $payment_gateway_options = $all_payment_gateways['payumoney'];
            $ARM_Payumoney_payment_mode = $payment_gateway_options['payumoney_payment_mode'];
            $is_sandbox_mode = $ARM_Payumoney_payment_mode == "sandbox" ? true : false;

            $PayumoneyConfig = array();

            $PayumoneyConfig['environment'] = ( $is_sandbox_mode ) ? "sandbox" : "production"; // production, sandbox

            $PayumoneyConfig['credentials'] = array();
            $PayumoneyConfig['credentials']['merchant_key'] = ( $is_sandbox_mode ) ? $payment_gateway_options['payumoney_sandbox_merchant_key'] : $payment_gateway_options['payumoney_merchant_key'];
            $PayumoneyConfig['credentials']['salt']['production'] = $payment_gateway_options['payumoney_salt'];
            $PayumoneyConfig['credentials']['salt']['sandbox'] = $payment_gateway_options['payumoney_sandbox_salt'];

            $PayumoneyConfig['application'] = array();
            $PayumoneyConfig['application']['charset'] = "UTF-8"; // UTF-8, ISO-8859-1

            $PayumoneyConfig['log'] = array();
            $PayumoneyConfig['log']['active'] = false;
            
            $PayumoneyConfig['log']['fileLocation'] = "";

            return $PayumoneyConfig;
        }
    }

    
    function arm2_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {

        global $wpdb, $ARMember, $arm_global_settings, $arm_membership_setup, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $paid_trial_stripe_payment_done, $is_free_manual;
        
        $is_free_manual = false;

        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        $currency = $arm_payment_gateways->arm_get_global_currency();
        if ($payment_gateway == 'payumoney' && isset($all_payment_gateways['payumoney']) && !empty($all_payment_gateways['payumoney'])) 
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
                $payment_mode_ = !empty($posted_data['arm_payment_mode']['payumoney']) ? $posted_data['arm_payment_mode']['payumoney'] : 'both';

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
	                    if(isset($posted_data['arm_payment_mode']['payumoney'])){
	                        $payment_mode_ = !empty($posted_data['arm_payment_mode']['payumoney']) ? $posted_data['arm_payment_mode']['payumoney'] : 'manual_subscription';
	                    }
	                    else{
	                        $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
	                        if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
	                            $setup_modules = $setup_data['setup_modules'];
	                            $modules = $setup_modules['modules'];
	                            $payment_mode_ = $modules['payment_mode']['payumoney'];
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


                
                $arm_payumoney_webhookurl = '';
                $arm_payumoney_webhookurl = $arm_global_settings->add_query_arg("arm-listener", "arm_payumoney_api", get_home_url() . "/");

                if ((($discount_amt <= 0 || $discount_amt == '0.00') && $recurring_payment_mode == 'manual_subscription' && $plan->is_recurring()) || (!$plan->is_recurring() && ($discount_amt <= 0 || $discount_amt == '0.00'))) 
                {
                    
                    global $payment_done;
                    $payumoney_response = array();
                    $current_user_id = 0;
                    if (is_user_logged_in()) {
                        $current_user_id = get_current_user_id();
                        $payumoney_response['arm_user_id'] = $current_user_id;
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
                    $payumoney_response['arm_plan_id'] = $plan->ID;
                    $payumoney_response['arm_first_name']=$arm_first_name;
                    $payumoney_response['arm_last_name']=$arm_last_name;
                    $payumoney_response['arm_payment_gateway'] = 'payumoney';
                    $payumoney_response['arm_payment_type'] = $plan->payment_type;
                    $payumoney_response['arm_token'] = '-';
                    $payumoney_response['arm_payer_email'] = $user_email_add;
                    $payumoney_response['arm_receiver_email'] = '';
                    $payumoney_response['arm_transaction_id'] = '-';
                    $payumoney_response['arm_transaction_payment_type'] = $plan->payment_type;
                    $payumoney_response['arm_transaction_status'] = 'completed';
                    $payumoney_response['arm_payment_mode'] = 'manual_subscription';
                    $payumoney_response['arm_payment_date'] = date('Y-m-d H:i:s');
                    $payumoney_response['arm_amount'] = $amount;
                    $payumoney_response['arm_currency'] = $currency;
                    $payumoney_response['arm_coupon_code'] = $posted_data['arm_coupon_code'];
                    $payumoney_response['arm_response_text'] = '';
                    $payumoney_response['arm_extra_vars'] = '';
                    $payumoney_response['arm_is_trial'] = $arm_is_trial;
                    $payumoney_response['arm_created_date'] = current_time('mysql');
                    $payumoney_response['arm_coupon_discount'] = $arm_coupon_discount;
                    $payumoney_response['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                    $payumoney_response['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;

                    $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payumoney_response);
                    $return = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                    $payment_done = $return;
                    $is_free_manual = true;

                    if($arm_manage_coupons->isCouponFeature && !empty($posted_data['arm_coupon_code']) && !empty($arm_coupon_on_each_subscriptions)) {
                            $payment_done["coupon_on_each"] = TRUE;
                            $payment_done["trans_log_id"] = $payment_log_id;
                    }

                    /*if($plan_action=='recurring_payment')
                    {
                        $plan_payment_mode = 'manual_subscription';
                        do_action('arm_after_recurring_payment_success_outside', $user_id, $plan_id, 'payumoney', $plan_payment_mode);
                    }*/


                    do_action('arm_after_payumoney_free_payment',$plan,$payment_log_id,$arm_is_trial,$posted_data['arm_coupon_code'],$extraParam);

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
                    $data_array['notification_url'] = $arm_payumoney_webhookurl;
                    
                    if($pgoptions['payumoney']['payumoney_payment_mode'] == 'sandbox')
                    {
                    	$data_array['arm_payumoney_status_mode'] = 1;
                        $data_array['arm_payumoney_marchant_id'] = $pgoptions['payumoney']['payumoney_sandbox_merchant_key'];
                        $data_array['arm_payumoney_secret_key'] = $pgoptions['payumoney']['payumoney_sandbox_salt'];
                    }
                    else
                    {
                    	$data_array['arm_payumoney_status_mode'] = 2;
                        $data_array['arm_payumoney_marchant_id'] = $pgoptions['payumoney']['payumoney_merchant_key'];
                        $data_array['arm_payumoney_secret_key'] = $pgoptions['payumoney']['payumoney_salt'];
                    }

                    $data_array['first_name'] = $entry_data['arm_entry_value']['first_name'];
                    $data_array['last_name'] = $entry_data['arm_entry_value']['last_name'];
                    $data_array['user_email'] = $user_email_add;
                    $data_array['plan_action'] = $plan_action;

                    $createpaymentrequest = new CreatePayUMoneyPaymentRequest();
                    $payumoneyform = $createpaymentrequest->main($data_array);

                    if (isset($posted_data['action']) && in_array($posted_data['action'], array('arm_shortcode_form_ajax_action', 'arm_membership_setup_form_ajax_action'))) {

	                    
	                    $return = array('status' => 'success', 'type' => 'redirect', 'message' => $payumoneyform);
	                    echo json_encode($return);
	                    exit;
	                }
                }
                
                /*if($is_recurring && $recurring_payment_mode == 'auto_debit_subscription' && !empty($recurring_data))
                {

                    $plan_not_support = 0;                    
                    $arm_payumoney_recur_period = $recurring_data['period'];
                    switch ($arm_payumoney_recur_period) {
                        case 'M':
                            $arm_payumoney_payperiod = "months";
                            if( $recurring_data['interval'] == 1 ) {
                                $arm_subscription_period = 'MONTHLY';
                                $total_month = $recurring_data['rec_time'];
                                $arm_finel_date = date('Y-m-d', strtotime('+'.$total_month.' months'));
                            } else if( $recurring_data['interval'] == 2 ) {
                                $arm_subscription_period = 'BIMONTHLY';
                                $total_month = $recurring_data['rec_time'] * 2;
                                $arm_finel_date = date('Y-m-d', strtotime('+'.$total_month.' months'));
                            } else if( $recurring_data['interval'] == 3 ) {
                                $arm_subscription_period = 'TRIMONTHLY';
                                $total_month = $recurring_data['rec_time'] * 3;
                                $arm_finel_date = date('Y-m-d', strtotime('+'.$total_month.' months'));
                            } else {
                                $plan_not_support++;
                            }
                            break;
                        case 'D':
                            $plan_not_support++;
                            $arm_payumoney_payperiod = "days";
                            break;
                        case 'W':
                            $plan_not_support++;
                            $arm_payumoney_payperiod = "weeks";
                            break;
                        case 'Y':
                            $arm_payumoney_payperiod = "years";
                            if($recurring_data['interval'] == 1) {
                                $arm_subscription_period = 'YEARLY';
                                $total_years = $recurring_data['rec_time'];
                                $arm_finel_date = date('Y-m-d', strtotime('+'.$total_years.' years'));
                            } else { 
                                $plan_not_support++;
                            }
                            break;
                    }

                    if($amount< 1 || $amount == '0.00'){
                        $amount = 1;
                    }

                    $extraVars['paid_amount'] = number_format($amount, 2);
                    
                    $data_array['currency'] = $currency;
                    $data_array['item_id'] = $plan_id;
                    $data_array['item_name'] = $plan_name;
                    $data_array['item_qty'] = 1;
                    $data_array['item_amount'] = number_format($amount, 2);
                    $data_array['reference'] = 'ref-' . $entry_id;
                    $data_array['subscription_period'] = $arm_subscription_period;
                    $data_array['max_total_amount'] = number_format($recurring_data['rec_time'] * $amount, 2);
                    $data_array['discount_percentage'] = isset($couponApply['discount']) ? number_format($couponApply['discount'],2) : 0.00 ;
                    $data_array['finel_date'] = $arm_finel_date.'T00:00:00';
                    $data_array['redirect_url'] = $arm_payumoney_webhookurl;
                    $data_array['notification_url'] = $arm_payumoney_webhookurl;
                    
                    if( $plan_not_support > 0 || $plan->has_trial_period() )
                    {

                        
                        $err_msg = '<div class="arm_error_msg"><ul><li>' . __('Payment through PayUmoney is not supported using auto debit payment for selected plan.', ARM_PAYUMONEY_TEXTDOMAIN) . '</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $err_msg);
                        echo json_encode($return);
                        die;
                    }
                }
                else 
                {*/

                    $extraVars['paid_amount'] = $discount_amt;
                    $data_array['currency'] = $currency;
                    $data_array['arm_plan_id'] = $plan_id;
                    $data_array['arm_plan_name'] = $plan_name;
                    $data_array['arm_plan_amount'] = $discount_amt;
                    $data_array['reference'] = 'ref-' . $entry_id;
                    $data_array['redirect_url'] = $arm_redirecturl;
                    $data_array['notification_url'] = $arm_payumoney_webhookurl;

                    
                    
                    if($pgoptions['payumoney']['payumoney_payment_mode']=='sandbox')
                    {
                    	$data_array['arm_payumoney_status_mode'] = 1;
                        $data_array['arm_payumoney_marchant_id'] = $pgoptions['payumoney']['payumoney_sandbox_merchant_key'];
                        $data_array['arm_payumoney_secret_key'] = $pgoptions['payumoney']['payumoney_sandbox_salt'];
                    }
                    else
                    {
                    	$data_array['arm_payumoney_status_mode'] = 2;
                        $data_array['arm_payumoney_marchant_id'] = $pgoptions['payumoney']['payumoney_merchant_key'];
                        $data_array['arm_payumoney_secret_key'] = $pgoptions['payumoney']['payumoney_salt'];
                    }

                    $data_array['first_name'] = $entry_data['arm_entry_value']['first_name'];
                    $data_array['last_name'] = $entry_data['arm_entry_value']['last_name'];
                    $data_array['user_email'] = $user_email_add;
                    $data_array['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                    $data_array['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                    $payumoney_response['arm_coupon_discount'] = $arm_coupon_discount;
                    

                    $createpaymentrequest = new CreatePayUMoneyPaymentRequest();
                    $payumoneyform = $createpaymentrequest->main($data_array);

                    if (isset($posted_data['action']) && in_array($posted_data['action'], array('arm_shortcode_form_ajax_action', 'arm_membership_setup_form_ajax_action'))) {
	                    
	                    $return = array('status' => 'success', 'type' => 'redirect', 'message' => $payumoneyform);
	                    echo json_encode($return);
	                    exit;
	                }
                    
                /*}*/
            } else {
                
            }
        } else {
            
        }
    }

    function arm2_payumoney_webhook($transaction_id = 0, $arm_listener = '', $tran_id = '') {

        if (isset($_REQUEST['arm-listener']) && in_array($_REQUEST['arm-listener'], array('arm_payumoney_api'))) {
            global $wpdb, $ARMember, $arm_payment_gateways, $arm_subscription_plans, $arm_manage_coupons;
                        
            if (!empty($_REQUEST['txnid']) && !empty($_REQUEST["status"]) && $_REQUEST["status"]=='success' ) 
            {    
                try {
                	$payumoneyresponse  = $_POST;

                    $gateway_options 	= get_option('arm_payment_gateway_settings');
                    $pgoptions 			= maybe_unserialize($gateway_options);
                    $arm_payu_payment_mode = isset($pgoptions['payumoney']['payumoney_payment_mode']) ? $pgoptions['payumoney']['payumoney_payment_mode'] : 'sandbox';
                    $arm_payu_solt      = ($arm_payu_payment_mode=='sandbox') ? $pgoptions['payumoney']['payumoney_sandbox_salt'] : $pgoptions['payumoney']['payumoney_salt'];


                    $key 				= isset($payumoneyresponse['key']) ? $payumoneyresponse['key'] : '';
    		        $salt 				= $arm_payu_solt;
    		        
    		        $txnid 				= isset($payumoneyresponse['txnid']) ? $payumoneyresponse['txnid'] : '' ;
    		        $amount 			= isset($payumoneyresponse['amount']) ? $payumoneyresponse['amount'] : '';
    		        $productinfo 		= isset($payumoneyresponse['productinfo']) ? $payumoneyresponse['productinfo'] : '';
    		        $firstname 			= isset($payumoneyresponse['firstname']) ? $payumoneyresponse['firstname'] : '';
    		        $email 				= isset($payumoneyresponse['email']) ? $payumoneyresponse['email'] : '';
    		        $posted_hash		= isset($payumoneyresponse["hash"]) ? $payumoneyresponse["hash"] : '' ;
    		        $txncode 			= isset($payumoneyresponse['payuMoneyId']) ? $payumoneyresponse['payuMoneyId'] : '';
    		        $status 			= isset($payumoneyresponse["status"]) ? $payumoneyresponse["status"] : '';
                    $reference 			= isset($payumoneyresponse["udf1"]) ? $payumoneyresponse["udf1"] : '';
                    $redirect_url       = isset($payumoneyresponse["udf2"]) ? $payumoneyresponse["udf2"] : '';
                    $plan_action        = isset($payumoneyresponse["udf3"]) ? $payumoneyresponse["udf3"] : '';
    		        
        		       if(isset($payumoneyresponse["additionalCharges"])) 
                       {
        		            $additionalCharges  = $payumoneyresponse["additionalCharges"];
        		            $returnHashSeq      = $additionalCharges.'|'.$salt.'|'.$status.'||||||||'.$plan_action.'|'.$redirect_url.'|'.$reference.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
       				   } 
                       else 
                       {
        			        $returnHashSeq      = $salt.'|'.$status.'||||||||'.$plan_action.'|'.$redirect_url.'|'.$reference.'|'.$email.'|'.$firstname.'|'.$productinfo.'|'.$amount.'|'.$txnid.'|'.$key;
        			   }
                       $hash = hash("sha512", $returnHashSeq);
                       
    			       if ($hash == $posted_hash) 
                       {
                        
                            // debug status
                            //if ($this->debug === true) {
                                //$ARMember->arm_write_response("PayUmoney Gateway Debug 6: Status Check : OK");
                            //}
                            // update succesful payment
                            $reference_code = explode('-', $reference);
                            $entry_id = $reference_code[1];
                            $arm_get_payment_log = $wpdb->get_row( $wpdb->prepare( "SELECT arm_log_id FROM `$ARMember->tbl_arm_payment_log` WHERE arm_token = %s", $txnid), ARRAY_A );
                            $arm_log_id = ( isset($arm_get_payment_log['arm_log_id']) && !empty($arm_get_payment_log['arm_log_id']) ) ? $arm_get_payment_log['arm_log_id'] : '';
                            if($arm_log_id == '') {
                                $arm_log_id = $this->arm2_add_user_and_transaction($entry_id, $payumoneyresponse);
                            }

                            //$ARMember->arm_write_response("repute log PayUmoney Gateway : transaction ID & Payment Log ID  txnid".$txnid.' | arm_log_id'. $arm_log_id .' | SELECT * FROM `'.$ARMember->tbl_arm_payment_log.'` WHERE arm_token = "'.$txnid.'" AND arm_log_id= "'.$arm_log_id.'"');

                            $arm_get_payment_log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$ARMember->tbl_arm_payment_log` WHERE arm_token = %s AND arm_log_id= %s", $txnid, $arm_log_id), ARRAY_A );

                            //$ARMember->arm_write_response("repute log PayUmoney Gateway : payment Log Details ".maybe_serialize($arm_get_payment_log));
                            
                            $ARMember->arm_write_response("repute log PayUmoney Gateway : plan_action Details ".maybe_serialize($plan_action));
                            $pgateway = 'payumoney';
                            if(!empty($arm_get_payment_log) && $plan_action=='recurring_payment')
                            {
                                $plan_payment_mode = 'manual_subscription';
                                $user_id = $arm_get_payment_log['arm_user_id'];
                                $plan_id = $arm_get_payment_log['arm_plan_id'];
                                $is_recurring_payment = $arm_subscription_plans->arm_is_recurring_payment_of_user($user_id, $plan_id, $plan_payment_mode);

                                if($is_recurring_payment)
                                {
                                    $ARMember->arm_write_response("repute log PayUmoney Gateway : Inside IF recurring payment condition is_recurring_payment".$is_recurring_payment.'| PlanID='.$plan_id.' | UserID='.$user_id);
                                    do_action('arm_after_recurring_payment_success_outside', $user_id, $plan_id, $pgateway, $plan_payment_mode);
                                }
                                else {
                                    $ARMember->arm_write_response("repute log PayUmoney Gateway : Inside ELSE recurring payment condition is_recurring_payment".$is_recurring_payment.'| PlanID='.$plan_id.' | UserID='.$user_id);
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
                                $ARMember->arm_write_response("repute log PayUmoney Gateway : Outside (ELSE) recurring payment condition is_recurring_payment".$is_recurring_payment.'| PlanID='.$plan_id.' | UserID='.$user_id);
                            }

                            
                            
                            if(empty($redirect_url))
                            {
                                header('location: '.ARM_HOME_URL); 
                            }
                            else{
                                header('location: '.$redirect_url); 
                            }
                            
    				   } else {
                        
        		           // debug status
                                if ($this->debug === true) {
                                    //$ARMember->arm_write_response("PayUmoney Gateway Debug 6: Status Check : ERROR");
                                }
                     
                         }
                         
                    }
                    catch (Exception $e) {
                        die($e->getMessage());
                    }

                    
                 

            }
            else
            {
            	header('location: '.ARM_HOME_URL); 
            }
            
        }
    }
    
    

    function arm2_add_user_and_transaction($entry_id = 0, $payumoneyresponse, $arm_display_log = 1) {
        global $wpdb, $payumoney, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication, $arm_manage_coupons, $payment_done, $paid_trial_stripe_payment_done, $arm_members_class,$arm_transaction;
        if (isset($entry_id) && $entry_id != '') {
            $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
            if (isset($all_payment_gateways['payumoney']) && !empty($all_payment_gateways['payumoney'])) {
                $options = $all_payment_gateways['payumoney'];
                $payumoney_payment_mode = $options['payumoney_payment_mode'];
                $is_sandbox_mode = $payumoney_payment_mode == "sandbox" ? true : false;
                $currency = $arm_payment_gateways->arm_get_global_currency();

                $entry_data = $wpdb->get_row("SELECT * FROM `" . $ARMember->tbl_arm_entries . "` WHERE `arm_entry_id`='" . $entry_id . "' ", ARRAY_A);
                $entry_values = maybe_unserialize($entry_data['arm_entry_value']);
                $entry_email = isset($entry_data['arm_entry_email']) ? $entry_data['arm_entry_email'] : '';
                $arm_log_plan_id = $entry_data['arm_plan_id'];
                $arm_log_amount = isset($entry_values['arm_total_payable_amount']) ? $entry_values['arm_total_payable_amount'] : '';
                $arm_token = $payumoneyresponse['payuMoneyId'];
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
                $setup_id = $entry_values['setup_id'];
                $entry_plan = $entry_data['arm_plan_id'];
                $payumoneyLog = $payumoneyresponse;
                $payumoneyLog['arm_coupon_code'] = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                $payumoneyLog['arm_payment_type'] = $arm_payment_type;
                $payumoneyLog['payment_type'] = $arm_payment_type;
                $payumoneyLog['payment_status'] = $payment_status;
                $payumoneyLog['payer_email'] = $entry_email;
                $payumoneyLog['arm_first_name']=$user_detail_first_name;
                $payumoneyLog['arm_last_name']=$user_detail_last_name;
                $extraParam['payment_type'] = 'payumoney';
                $extraParam['payment_mode'] = $payumoney_payment_mode;
                $extraParam['arm_is_trial'] = '0';
                $extraParam['subs_id'] = $arm_token;
                $extraParam['trans_id'] = $arm_token;
                $cardnumber = $payumoneyresponse['cardnum'];
                $extraParam['card_number'] = $arm_transaction->arm_mask_credit_card_number($cardnumber);
                $extraParam['error'] = '';
                $extraParam['date'] = current_time('mysql');
                $extraParam['message_type'] = '';

                $amount = '';
                //$form_id = $entry_data['arm_form_id'];
                //$armform = new ARM_Form('id', $form_id);
                //$user_info = get_user_by('email', $entry_email);        
                $new_plan = new ARM_Plan($entry_plan);
                //$user_id = isset($user_info->ID) ? $user_info->ID : 0;
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
                        $payumoneyLog['coupon_code'] = $couponCode;
                        $payumoneyLog['arm_coupon_discount'] = $arm_coupon_discount;
                        $payumoneyLog['arm_coupon_discount_type'] = $arm_coupon_discount_type;
                        $payumoneyLog['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                    }
                } 

                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $payumoneyLog['currency'] = $currency;
                $payumoneyLog['payment_amount'] = $discount_amt;
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
                    $payumoneyLog['payment_amount'] = $amount_for_tax;
                    $payment_log_id = self::arm_store_payumoney_log($payumoneyLog, 0, $entry_plan, $extraParam, $arm_display_log);
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

                            $userPlanData['arm_payumoney']['transaction_id'] = $arm_token;
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
                                $old_subscription_id = $oldPlanData['arm_payumoney']['sale_id'];
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
                                $payumoneyLog['payment_amount'] = $amount_for_tax;
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
                                $userPlanData['arm_user_gateway'] = 'payumoney';

                                if (!empty($arm_token)) {
                                    $userPlanData['arm_payumoney']['transaction_id'] = $arm_token;
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
                                $old_subscription_id = $oldPlanData['arm_payumoney']['transaction_id'];



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
                                    $payumoneyLog['payment_amount'] = $amount_for_tax;

                                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                    $userPlanData['arm_user_gateway'] = 'payumoney';

                                    if (!empty($arm_token)) {
                                        $userPlanData['arm_payumoney']['transaction_id'] = $arm_token;
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
                                    $payumoneyLog['payment_amount'] = $amount_for_tax;

                                update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                $userPlanData['arm_user_gateway'] = 'payumoney';

                                if (!empty($arm_token)) {
                                    $userPlanData['arm_payumoney']['transaction_id'] = $arm_token;
                                }
                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                if ($is_update_plan) {
                                    $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan,  '', true, $arm_last_payment_status);
                                } else {
                                   $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'new_subscription');
                                }
                            }
                        }
                        


                        $payment_log_id = self::arm_store_payumoney_log($payumoneyLog, $user_id, $entry_plan, $extraParam, $arm_display_log);
                    }
                }
                return $payment_log_id;
            }
        }
    }
    
    function arm_store_payumoney_log($payumoney_response = '', $user_id = 0, $plan_id = 0, $extraVars = array(), $arm_display_log = '1') {
        global $wpdb, $ARMember, $arm_global_settings, $arm_member_forms, $arm_payment_gateways;
        $payment_log_table = $ARMember->tbl_arm_payment_log;
        $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $payumoney_response['payuMoneyId']));
        if (!empty($payumoney_response) && empty($transaction)) {
            $payment_data = array(
                'arm_user_id' => $user_id,
                'arm_first_name'=>$payumoney_response['arm_first_name'],
                'arm_last_name'=>$payumoney_response['arm_last_name'],
                'arm_plan_id' => (!empty($plan_id) ? $plan_id : 0),
                'arm_payment_gateway' => 'payumoney',
                'arm_payment_type' => $payumoney_response['arm_payment_type'],
                'arm_token' => $payumoney_response['txnid'],
                'arm_payer_email' => $payumoney_response['payer_email'],
                'arm_receiver_email' => '',
                'arm_transaction_id' => $payumoney_response['payuMoneyId'],
                'arm_transaction_payment_type' => $payumoney_response['payment_type'],
                'arm_transaction_status' => $payumoney_response['payment_status'],
                'arm_payment_date' => date('Y-m-d H:i:s', strtotime($payumoney_response['addedon'])),
                'arm_amount' => $payumoney_response['payment_amount'],
                'arm_currency' => $payumoney_response['currency'],
                'arm_coupon_code' => $payumoney_response['arm_coupon_code'],
                'arm_coupon_discount' => (isset($payumoney_response['arm_coupon_discount']) && !empty($payumoney_response['arm_coupon_discount'])) ? $payumoney_response['arm_coupon_discount'] : 0,
                'arm_coupon_discount_type' => isset($payumoney_response['arm_coupon_discount_type']) ? $payumoney_response['arm_coupon_discount_type'] : '',
                'arm_response_text' => maybe_serialize($payumoney_response),
                'arm_extra_vars' => maybe_serialize($extraVars),
                'arm_is_trial' => isset($payumoney_response['arm_is_trial']) ? $payumoney_response['arm_is_trial'] : 0,
                'arm_display_log' => $arm_display_log,
                'arm_created_date' => current_time('mysql'),
                'arm_coupon_on_each_subscriptions' => !empty($payumoney_response['arm_coupon_on_each_subscriptions']) ? $payumoney_response['arm_coupon_on_each_subscriptions'] : 0,
            );
            $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
            return $payment_log_id;
        }
        return false;
    }

    
    function arm2_payumoney_cancel_subscription($user_id, $plan_id){
        global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
        if (isset($user_id) && $user_id != 0 && isset($plan_id) && $plan_id != 0) {
            $user_detail = get_userdata($user_id);
            $payer_email = $user_detail->user_email;
            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
            $currency = $arm_payment_gateways->arm_get_global_currency();
            if(!empty($planData)){
                $user_payment_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
                if ($user_payment_gateway == 'payumoney') {
                    
                    $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                    $planDetail = $planData['arm_current_plan_detail'];

                    if (!empty($planDetail)) { 
                        $planObj = new ARM_Plan(0);
                        $planObj->init((object) $planDetail);
                    } else {
                        $planObj = new ARM_Plan($plan_id);
                    }
            
                    $payment_log_table = $ARMember->tbl_arm_payment_log;
                    $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type,arm_amount FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s AND `arm_display_log` = %d ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'payumoney', 'success', 1));
                     
                    if (!empty($transaction)) {
                        $extra_var = maybe_unserialize($transaction->arm_extra_vars);
                        $payer_email = $transaction->arm_payer_email;
                        $payment_type = $extra_var['payment_type'];
                        $payment_mode = $extra_var['payment_mode'];
                        $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;

                        $gateway_options = get_option('arm_payment_gateway_settings');
                        $pgoptions = maybe_unserialize($gateway_options);
                        $pgoptions = $pgoptions['payumoney'];
                        if ($payment_type == 'payumoney') {

                                $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                            
                                $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                $payment_data = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan_id,
                                    'arm_first_name'=>$user_detail_first_name,
                                    'arm_last_name'=>$user_detail_last_name,
                                    'arm_payment_gateway' => 'payumoney',
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
    
    function arm_payumoney_modify_coupon_code($data,$payment_mode,$couponData,$planAmt, $plan_obj){

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

global $arm_payumoney;
$arm_payumoney = new ARM_Payumoney();

if ($arm_payumoney->is_armember_support() && $arm_payumoney->is_version_compatible()) {
    
}


global $armpayumoney_api_url, $armpayumoney_plugin_slug;

$armpayumoney_api_url = $arm_payumoney->armpayumoney_getapiurl();
$armpayumoney_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armpayumoney_check_for_plugin_update');

function armpayumoney_check_for_plugin_update($checked_data) {
    global $armpayumoney_api_url, $armpayumoney_plugin_slug, $wp_version, $arm_payumoney_version,$arm_payumoney;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armpayumoney_plugin_slug,
        'version' => $arm_payumoney_version,
        'other_variables' => $arm_payumoney->armpayumoney_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMPAYUMONEY-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armpayumoney_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armpayumoney_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armpayumoney_plugin_slug . '/' . $armpayumoney_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armpayumoney_plugin_api_call', 10, 3);

function armpayumoney_plugin_api_call($def, $action, $args) {
    global $armpayumoney_plugin_slug, $armpayumoney_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armpayumoney_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armpayumoney_plugin_slug . '/' . $armpayumoney_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armpayumoney_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMPAYUMONEY-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armpayumoney_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', MEMBERSHIP_TXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', MEMBERSHIP_TXTDOMAIN), $request['body']);
    }

    return $res;
}

class PayUmoneyConfigWrapper {

    public static function getConfig() {
        global $arm_payumoney;
        return $arm_payumoney->arm_payumoney_config();
    }

}

class CreatePayUMoneyPaymentRequest {

    public static function main($data_array) {

        $payumoney_form = '';
        $key = $data_array['arm_payumoney_marchant_id'];
        $secretKey = $data_array['arm_payumoney_secret_key'];
        $is_sandbox_mode = ($data_array['arm_payumoney_status_mode']==1) ? 'sandboxsecure' : 'secure';
        $testurl = 'https://'.$is_sandbox_mode.'.payu.in/_payment';
        $redirect_url = $data_array['redirect_url'];
        
        $txnid = $data_array['arm_plan_id'].'_'.time();
        $amount = $data_array['arm_plan_amount'];
        $productinfo = $data_array['arm_plan_name'];
        $reference = $data_array['reference'];
        $firstname = $data_array['first_name'];
        $lastname = $data_array['last_name'];
        $email = $data_array['user_email'];
        $plan_action  = $data_array['plan_action'];
        
        $surl  = $data_array['notification_url'];
        $furl  = $data_array['notification_url'];
        $curl  = $data_array['notification_url'];

        $service_provider	= 'payu_paisa';
        

        $str = "$key|$txnid|$amount|$productinfo|$firstname|$email|$reference|$redirect_url|$plan_action||||||||$secretKey";
       
        $hash = strtolower(hash('sha512', $str));
        
        
        $payumoney_form = '<form action="'.$testurl.'" method="post" id="arm_payumoney_form">';
        $payumoney_form .= '<input type="hidden" name="key" value="'.$key.'"/>';
        $payumoney_form .= '<input type="hidden" name="hash" value="'.$hash.'"/>';
        $payumoney_form .= '<input type="hidden" name="txnid" value="'.$txnid.'"/>';
        $payumoney_form .= '<input type="hidden" name="amount" value="'.$amount.'"/>';
        $payumoney_form .= '<input type="hidden" name="firstname" value="'.$firstname.'"/>';
        $payumoney_form .= '<input type="hidden" name="email" value="'.$email.'"/>';
        
        $payumoney_form .= '<input type="hidden" name="productinfo" value="'.$productinfo.'"/>';
        $payumoney_form .= '<input type="hidden" name="surl" value="'.$surl.'"/>';
        $payumoney_form .= '<input type="hidden" name="furl" value="'.$furl.'"/>';
        $payumoney_form .= '<input type="hidden" name="udf1" value="'.$reference.'"/>';
        $payumoney_form .= '<input type="hidden" name="udf2" value="'.$redirect_url.'"/>';
        $payumoney_form .= '<input type="hidden" name="udf3" value="'.$plan_action.'"/>';
        $payumoney_form .= '<input type="hidden" name="service_provider" value="'.$service_provider.'"/>';
        
        
        $payumoney_form .= '<input type="submit" style="display:none" id="arm_payumoney_submit" value="'.__('Pay via PayUMoney', ARM_PAYUMONEY_TEXTDOMAIN).'" /> 
                </form>
                <script data-cfasync="false" type="text/javascript" language="javascript">
                document.getElementById("arm_payumoney_form").submit();</script>
                ';
       
        return $payumoney_form;
       
    }
}
?>