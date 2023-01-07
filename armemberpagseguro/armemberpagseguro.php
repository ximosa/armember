<?php 
if(!isset($_SESSION)) 
{ @session_start(); }
//@error_reporting(E_ALL);
/*
  Plugin Name: ARMember - Pagseguro payment gateway Addon
  Description: Extension for ARMember plugin to accept payments using pagseguro.
  Version: 1.5
  Plugin URI: https://www.armemberplugin.com
  Author: Repute InfoSystems
  Author URI: https://www.armemberplugin.com
 */



define('ARM_PAGSEGURO_DIR_NAME', 'armemberpagseguro');
define('ARM_PAGSEGURO_DIR', WP_PLUGIN_DIR . '/' . ARM_PAGSEGURO_DIR_NAME);

if (is_ssl()) {
    define('ARM_PAGSEGURO_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_PAGSEGURO_DIR_NAME));
} else {
    define('ARM_PAGSEGURO_URL', WP_PLUGIN_URL . '/' . ARM_PAGSEGURO_DIR_NAME);
}

define('ARM_PAGSEGURO_TEXTDOMAIN', 'ARM_PAGSEGURO');

define('ARM_PAGSEGURO_DOC_URL', ARM_PAGSEGURO_URL . '/documentation/index.html#content');

global $arm_pagseguro_version;
$arm_pagseguro_version = '1.5';

global $armnew_pagseguro_version;


global $armpagseguro_api_url, $armpagseguro_plugin_slug, $wp_version;

class ARM_Pagseguro{
    
    function __construct() {
        global $arm_payment_gateways;
        $arm_payment_gateways->currency['pagseguro'] = $this->arm_pagseguro_currency_symbol();

        add_action('init', array(&$this, 'arm_pagseguro_db_check'));

        register_activation_hook(__FILE__, array('ARM_Pagseguro', 'install'));

        register_uninstall_hook(__FILE__, array('ARM_Pagseguro', 'uninstall'));

        add_filter('arm_get_payment_gateways', array(&$this, 'arm_add_pagseguro_payment_gateways'));
        add_filter('arm_get_payment_gateways_in_filters', array(&$this, 'arm_add_pagseguro_payment_gateways'));
        add_action('admin_notices', array(&$this, 'arm_pagseguro_admin_notices'));
        add_filter('arm_change_payment_gateway_tooltip', array(&$this, 'arm_change_payment_gateway_tooltip_func'), 10, 3);
        add_filter('arm_gateway_callback_info', array(&$this, 'arm_gateway_callback_info_func'), 10, 3);
        add_filter('arm_filter_gateway_names', array(&$this, 'arm_filter_gateway_names_func'), 10);
        
        add_action('arm_show_payment_gateway_recurring_notice', array(&$this, 'arm_show_payment_gateway_pagseguro_recurring_notice'), 10);
        add_filter('arm_set_gateway_warning_in_plan_with_recurring', array(&$this, 'arm_pagseguro_recurring_trial'), 10);
        add_filter('arm_allowed_payment_gateways', array(&$this, 'arm_payment_allowed_gateways'), 10, 3);
        add_action('arm_payment_related_common_message', array(&$this, 'arm_payment_related_common_message'), 10);

        add_filter('arm_currency_support', array(&$this, 'arm_pagseguro_currency_support'), 10, 2);
        //add_filter('arm_not_display_payment_mode_setup', array(&$this, 'arm_not_display_payment_mode_setup_func'), 10, 1);

        add_action('arm_after_payment_gateway_listing_section', array(&$this, 'arm_after_payment_gateway_listing_section_func'), 10, 2);

        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_script'), 10);
        add_action('wp_head', array(&$this, 'arm_pagseguro_set_front_js'), 10);
        
        add_action('plugins_loaded', array(&$this, 'arm_pagseguro_load_textdomain'));
		
        add_action('admin_init', array(&$this, 'upgrade_data_pagseguro'));
		
	    add_filter('arm_change_coupon_code_outside_from_pagseguro',array(&$this,'arm_pagseguro_modify_coupon_code'),10,5);
        
        add_filter('script_loader_tag', array(&$this, 'arm_prevent_rocket_loader_script'), 10, 2);
        
        if(version_compare($this->get_armember_version(), '2.0', '>=')){
            add_filter('arm_change_pending_gateway_outside',array(&$this,'arm2_change_pending_gateway_outside'),100,3);
            
            add_filter('arm_membership_update_user_meta_from_outside', array(&$this, 'arm2_membership_pagseguro_update_usermeta'), 10, 5);
            
            add_action('arm_update_user_meta_after_renew_outside', array(&$this, 'arm2_pagseguro_update_meta_after_renew'), 10, 4);
            
            add_filter('arm_default_plan_array_filter', array(&$this, 'arm2_default_plan_array_filter_func'), 10, 1);
            
            add_filter('arm_need_to_cancel_old_subscription_gateways', array(&$this, 'arm2_need_to_cancel_old_subscription_gateways'), 10, 1);
            
            add_action('arm_payment_gateway_validation_from_setup', array(&$this, 'arm2_payment_gateway_form_submit_action'), 10, 4);
            
            add_action('wp', array(&$this, 'arm2_pagseguro_webhook'), 5);
            
            add_action('arm_cancel_subscription_gateway_action', array(&$this, 'arm2_pagseguro_cancel_subscription'), 10, 2);
        }
        else
        {
            add_filter('arm_change_pending_gateway_outside', array($this, 'arm_change_pending_gateway_outside'), 10, 3);
            
            add_action('arm_payment_gateway_validation_from_setup', array(&$this, 'arm_payment_gateway_form_submit_action'), 10, 4);
            
            add_action('wp', array(&$this, 'arm_pagseguro_webhook'), 5);
            
            add_action('arm_cancel_subscription_gateway_action', array(&$this, 'arm_pagseguro_cancel_subscription'), 10, 2);
        }

        add_action('arm_enqueue_js_css_from_outside', array(&$this,'arm_enqueue_pagseguro_js_css_for_model'),10);
    }
    
    

    function arm2_need_to_cancel_old_subscription_gateways( $payment_gateway_array ) {
        array_push($payment_gateway_array, 'pagseguro');
        return $payment_gateway_array;
    }
    
    function arm2_default_plan_array_filter_func( $default_plan_array ) {
        global $ARMember;
        $default_plan_array['arm_pagseguro'] = '';
        return $default_plan_array;
    }
    
    function arm2_membership_pagseguro_update_usermeta($posted_data, $user_id, $plan, $log_detail, $pgateway) {
        if ($pgateway == 'pagseguro') {
            $posted_data['arm_pagseguro'] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
        }
        return $posted_data;
    }
    
    function arm2_pagseguro_update_meta_after_renew($user_id, $log_detail, $plan_id, $payment_gateway) {
        global $ARMember;
        if ($payment_gateway == 'pagseguro') {
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
                $plan_data['arm_pagseguro'] = $pg_subsc_data;
                update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $plan_data);
            }
        }
    }
    
    function arm_pagseguro_load_textdomain() {
        load_plugin_textdomain(ARM_PAGSEGURO_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public static function arm_pagseguro_db_check() {
        global $arm_pagseguro;
        $arm_pagseguro_version = get_option('arm_pagseguro_version');

        if (!isset($arm_pagseguro_version) || $arm_pagseguro_version == '')
            $arm_pagseguro->install();
    }

	function armpagseguro_getapiurl() {
			$api_url = 'https://www.arpluginshop.com/';
			return $api_url;
		}
		
	function upgrade_data_pagseguro() {
			global $armnew_pagseguro_version;
	
			if (!isset($armnew_pagseguro_version) || $armnew_pagseguro_version == "")
				$armnew_pagseguro_version = get_option('arm_pagseguro_version');
	
            if (version_compare($armnew_pagseguro_version, '1.5', '<')) {
				$path = ARM_PAGSEGURO_DIR . '/upgrade_latest_data_pagseguro.php';
				include($path);
			}
		}
	
	function armpagseguro_get_remote_post_params($plugin_info = "") {
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
				if (strpos(strtolower($plugin["Title"]), "armemberpagseguro") !== false) {
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
        global $arm_pagseguro;
        $arm_pagseguro_version = get_option('arm_pagseguro_version');

        if (!isset($arm_pagseguro_version) || $arm_pagseguro_version == '') {
            global $wpdb, $arm_pagseguro_version;
            update_option('arm_pagseguro_version', $arm_pagseguro_version);
        }
    }

    public static function uninstall() {
        delete_option('arm_pagseguro_version');
    }

    function arm_pagseguro_currency_symbol() {
        $currency_symbol = array(
            'BRL' => 'R$',
        );
        return $currency_symbol;
    }

    function arm_add_pagseguro_payment_gateways($default_payment_gateways) {
        if ($this->is_version_compatible()) {
            global $arm_payment_gateways;
            $default_payment_gateways['pagseguro']['gateway_name'] = __('Pagseguro', ARM_PAGSEGURO_TEXTDOMAIN);
            return $default_payment_gateways;
        } else {
            return $default_payment_gateways;
        }
    }

    function arm_pagseguro_admin_notices() {
        global $pagenow, $arm_slugs;    
        if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
            if (!$this->is_armember_support())
                echo "<div class='updated updated_notices'><p>" . __('Pagseguro For ARMember plugin requires ARMember Plugin installed and active.', ARM_PAGSEGURO_TEXTDOMAIN) . "</p></div>";

            else if (!$this->is_version_compatible())
                echo "<div class='updated updated_notices'><p>" . __('Pagseguro For ARMember plugin requires ARMember plugin installed with version 3.0 or higher.', ARM_PAGSEGURO_TEXTDOMAIN) . "</p></div>";
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
        if ($gateway_name == 'pagseguro') {
            return __("You can find Token in your pagseguro account. To get more details, Please refer this", ARM_PAGSEGURO_TEXTDOMAIN)." <a href='https://pagseguro.uol.com.br/atendimento/perguntas_frequentes/categoria/seguranca/pela-internet/onde-obtenho-o-token.jhtml#rmcl' target='_blank'>".__("document", ARM_PAGSEGURO_TEXTDOMAIN)."</a>.";
        }
        return $titleTooltip;
    }
    
    function arm_gateway_callback_info_func($apiCallbackUrlInfo, $gateway_name, $gateway_options) {
        if ($gateway_name == 'pagseguro') {
            global $arm_global_settings;
            $apiCallbackUrl = $arm_global_settings->add_query_arg("arm-listener", "arm_pagseguro_api", get_home_url() . "/");
            $apiCallbackUrlInfo = __('Please make sure you have set following notification URL in your pagseguro account.', ARM_PAGSEGURO_TEXTDOMAIN);
            $callbackTooltip = __('To get more information about how to set notification URL in your pagseguro account, please refer this', ARM_PAGSEGURO_TEXTDOMAIN).' <a href="'. ARM_PAGSEGURO_DOC_URL .'" target="_blank">'.__('document', ARM_PAGSEGURO_TEXTDOMAIN).'</a>';
            $apiCallbackUrlInfo .= '<i class="arm_helptip_icon armfa armfa-question-circle" title="'.htmlentities($callbackTooltip).'"></i>';
            $apiCallbackUrlInfo .= '<br/><b>' . $apiCallbackUrl . '</b>';
            
        }
        return $apiCallbackUrlInfo;
    }

    function arm_filter_gateway_names_func($pgname) {
        $pgname['pagseguro'] = __('Pagseguro', ARM_PAGSEGURO_TEXTDOMAIN);
        return $pgname;
    }

    function arm_change_pending_gateway_outside($getway, $plan_ID, $user_id) {
        $getway[] = 'pagseguro';
        return $getway;
    }
    
    function arm2_change_pending_gateway_outside($user_pending_pgway,$plan_ID,$user_id){
        global $is_free_manual,$ARMember;
        if( $is_free_manual ){
            $key = array_search('pagseguro',$user_pending_pgway);
            unset($user_pending_pgway[$key]);
        }
        return $user_pending_pgway;
    }
    
    function admin_enqueue_script(){
        global $arm_pagseguro_version;
        wp_register_script( 'arm-admin-pagseguro', ARM_PAGSEGURO_URL . '/js/arm_admin_pagseguro.js', array(), $arm_pagseguro_version );
        wp_enqueue_script( 'arm-admin-pagseguro' );
    }
    
    function arm_pagseguro_set_front_js( $force_enqueue = false ) {
        if( $this->is_version_compatible() ){
            global $ARMember, $arm_pagseguro_version;
            $is_arm_front_page = $ARMember->is_arm_front_page();
            if ($is_arm_front_page === TRUE || $force_enqueue == TRUE){
                wp_register_script('arm_pagseguro_js', ARM_PAGSEGURO_URL . '/js/arm_pagseguro.js', array('jquery'), $arm_pagseguro_version);
                wp_enqueue_script('arm_pagseguro_js');
            }
        }
    }

    function arm_enqueue_pagseguro_js_css_for_model(){
        $this->arm_pagseguro_set_front_js(true);
    }
    
    function arm_show_payment_gateway_pagseguro_recurring_notice($plan_details) {
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
          ?><span class="arm_invalid" id="arm_pagseguro_warning" style="<?php echo $display; ?>"><?php _e("NOTE: pagseguro method will be hidden when unsupported plan is selected.", ARM_PAGSEGURO_TEXTDOMAIN); ?> (<span class="arm_pagseguro_not_support_plans" style="font-weight:bold;"><?php echo implode(',', $plans); ?></span>)</span><?php */
    }

    function arm_pagseguro_recurring_trial($notice) {
        // if need to display any notice related subscription in Add / Edit plan page
        if ($this->is_version_compatible()){
            $notice .= "<span style='margin-bottom:10px;'><b>". __('Pagseguro (if Pagseguro payment gateway is enabled)',ARM_PAGSEGURO_TEXTDOMAIN)."</b><br/>";
            $notice .= "<ol style='margin-left:30px;'>";
            $notice .= "<li>".__('Subscription with Trial period will not be applied in Pagseguro.',ARM_PAGSEGURO_TEXTDOMAIN)."</li>";
            $notice .= "<li>".__('Pagseguro does not support billing cycle in Days. Also you can set billing cycle only for 1 Month, 2 Month, 3 Month and 1 Year.',ARM_PAGSEGURO_TEXTDOMAIN)."</li>";
            $notice .= "<li>".__('Pagseguro does not support infinite recurring time.',ARM_PAGSEGURO_TEXTDOMAIN)."</li>";
            $notice .= "</ol>";
            $notice .= "</span>";
        } 
        return $notice;
    }

    function arm_payment_allowed_gateways($allowed_gateways, $plan_obj, $plan_options) {
        // if any subscription not able to do in the payment gatewey
        //if ($plan_obj->payment_type == 'subscription') {
        //    if ($plan_obj->plan_detail->arm_subscription_plan_options['trial']['amount'] > 0) {
        //        $allowed_gateways['pagseguro'] = "0";
        //    } else {
        //        $allowed_gateways['pagseguro'] = "0";
        //    }
        //} else {
        //    $allowed_gateways['pagseguro'] = "1";
        //}

        $allowed_gateways['pagseguro'] = "1";
        return $allowed_gateways;
    }

    function arm_payment_related_common_message($common_messages) {
        if ($this->is_version_compatible()) {
            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_payment_fail_pagseguro"><?php _e('Payment Fail (Pagseguro)', ARM_PAGSEGURO_TEXTDOMAIN); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_payment_fail_pagseguro]" id="arm_payment_fail_pagseguro" value="<?php echo (!empty($common_messages['arm_payment_fail_pagseguro']) ) ? $common_messages['arm_payment_fail_pagseguro'] : 'Sorry something went wrong while processing payment with Pagseguro.'; ?>" />
                </td>
            </tr>
            <?php
        }
    }

    function arm_payment_gateway_has_ccfields_func($pgHasCcFields, $gateway_name, $gateway_options) {
        if ($gateway_name == 'pagseguro') {
            return true;
        } else {
            return $pgHasCcFields;
        }
    }

    function arm_pagseguro_currency_support($notAllow, $currency) {
        global $arm_payment_gateways;
        if (!array_key_exists($currency, $arm_payment_gateways->currency['pagseguro'])) {
            $notAllow[] = 'pagseguro';
        }
        return $notAllow;
    }

    function arm_not_display_payment_mode_setup_func($gateway_name_arr) {
        //for remove auto debit payment and menual payment option from front side page and admin site. Its allow only manual payment.
        //$gateway_name_arr[] = 'pagseguro';
        return $gateway_name_arr;
    }

    function arm_after_payment_gateway_listing_section_func($gateway_name, $gateway_options) {
        // set paymetn geteway setting field in general settgin > payment gateway
        global $arm_global_settings;
        if ($gateway_name == 'pagseguro') {
            $gateway_options['pagseguro_payment_mode'] = (!empty($gateway_options['pagseguro_payment_mode']) ) ? $gateway_options['pagseguro_payment_mode'] : 'sandbox';
            $gateway_options['status'] = isset($gateway_options['status']) ? $gateway_options['status'] : 0;
            $disabled_field_attr = ($gateway_options['status'] == '1') ? '' : 'disabled="disabled"';
            $readonly_field_attr = ($gateway_options['status'] == '1') ? '' : 'readonly="readonly"';
            ?>
            <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Payment Mode', ARM_PAGSEGURO_TEXTDOMAIN); ?> *</label></th>
                <td class="arm-form-table-content">
                    <input id="arm_pagseguro_payment_gateway_mode_sand" class="arm_general_input arm_pagseguro_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="sandbox" name="payment_gateway_settings[pagseguro][pagseguro_payment_mode]" <?php checked($gateway_options['pagseguro_payment_mode'], 'sandbox'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_pagseguro_payment_gateway_mode_sand"><?php _e('Sandbox', ARM_PAGSEGURO_TEXTDOMAIN); ?></label>
                    <input id="arm_pagseguro_payment_gateway_mode_pro" class="arm_general_input arm_pagseguro_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="production" name="payment_gateway_settings[pagseguro][pagseguro_payment_mode]" <?php checked($gateway_options['pagseguro_payment_mode'], 'production'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_pagseguro_payment_gateway_mode_pro"><?php _e('Production', ARM_PAGSEGURO_TEXTDOMAIN); ?></label>
                </td>
            </tr>
            <!-- ***** Begining of Sandbox Input for pagseguro ***** -->
            <?php
            $pagseguro_hidden = "hidden_section";
            if (isset($gateway_options['pagseguro_payment_mode']) && $gateway_options['pagseguro_payment_mode'] == 'sandbox') {
                $pagseguro_hidden = "";
            } else if (!isset($gateway_options['pagseguro_payment_mode'])) {
                $pagseguro_hidden = "";
            }
            ?>
            <tr class="form-field arm_pagseguro_sandbox_fields <?php echo $pagseguro_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Sandbox Email', ARM_PAGSEGURO_TEXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_pagseguro_sandbox_email" name="payment_gateway_settings[pagseguro][pagseguro_sandbox_email]" value="<?php echo (!empty($gateway_options['pagseguro_sandbox_email'])) ? $gateway_options['pagseguro_sandbox_email'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_pagseguro_sandbox_fields <?php echo $pagseguro_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Sandbox Token', ARM_PAGSEGURO_TEXTDOMAIN); ?> *</th> 
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_pagseguro_sandbox_token" name="payment_gateway_settings[pagseguro][pagseguro_sandbox_token]" value="<?php echo (!empty($gateway_options['pagseguro_sandbox_token'])) ? $gateway_options['pagseguro_sandbox_token'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <?php /* not needed for one time payment required only for subscription
              <tr class="form-field arm_pagseguro_sandbox_fields <?php echo $pagseguro_hidden; ?> ">
              <th class="arm-form-table-label"><?php _e('Sandbox Application Id', ARM_PAGSEGURO_TEXTDOMAIN); ?> *</th>
              <td class="arm-form-table-content">
              <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_pagseguro_sandbox_app_id" name="payment_gateway_settings[pagseguro][pagseguro_sandbox_app_id]" value="<?php echo (!empty($gateway_options['pagseguro_sandbox_app_id'])) ? $gateway_options['pagseguro_sandbox_app_id'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
              </td>
              </tr>
              <tr class="form-field arm_pagseguro_sandbox_fields <?php echo $pagseguro_hidden; ?> ">
              <th class="arm-form-table-label"><?php _e('Sandbox Application Key', ARM_PAGSEGURO_TEXTDOMAIN); ?> *</th>
              <td class="arm-form-table-content">
              <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_pagseguro_sandbox_app_key" name="payment_gateway_settings[pagseguro][pagseguro_sandbox_app_key]" value="<?php echo (!empty($gateway_options['pagseguro_sandbox_app_key'])) ? $gateway_options['pagseguro_sandbox_app_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
              </td>
              </tr>
              <tr class="form-field arm_pagseguro_sandbox_fields <?php echo $pagseguro_hidden; ?> ">
              <th class="arm-form-table-label"><?php _e('Sandbox Authorization Code', ARM_PAGSEGURO_TEXTDOMAIN); ?> *</th>
              <td class="arm-form-table-content">
              <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_pagseguro_sandbox_auth_code" name="payment_gateway_settings[pagseguro][pagseguro_sandbox_auth_code]" value="<?php echo (!empty($gateway_options['pagseguro_sandbox_auth_code'])) ? $gateway_options['pagseguro_sandbox_auth_code'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
              </td>
              </tr>
             */ ?>
            <!-- ***** Ending of Sandbox Input for pagseguro ***** -->

            <!-- ***** Begining of Live Input for pagseguro ***** -->
            <?php
            $pagseguro_live_fields = "hidden_section";
            if (isset($gateway_options['pagseguro_payment_mode']) && $gateway_options['pagseguro_payment_mode'] == "production") {
                $pagseguro_live_fields = "";
            }
            ?>
            <tr class="form-field arm_pagseguro_fields <?php echo $pagseguro_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Production Email', ARM_PAGSEGURO_TEXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_pagseguro_production_email" name="payment_gateway_settings[pagseguro][pagseguro_email]" value="<?php echo (!empty($gateway_options['pagseguro_email'])) ? $gateway_options['pagseguro_email'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_pagseguro_fields <?php echo $pagseguro_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Production Token', ARM_PAGSEGURO_TEXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_pagseguro_production_token" name="payment_gateway_settings[pagseguro][pagseguro_token]" value="<?php echo (!empty($gateway_options['pagseguro_token'])) ? $gateway_options['pagseguro_token'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <?php /* not needed for one time payment required only for subscription
              <tr class="form-field arm_pagseguro_fields <?php echo $pagseguro_live_fields; ?> ">
              <th class="arm-form-table-label"><?php _e('Production Application Id', ARM_PAGSEGURO_TEXTDOMAIN); ?> *</th>
              <td class="arm-form-table-content">
              <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_pagseguro_production_app_id" name="payment_gateway_settings[pagseguro][pagseguro_app_id]" value="<?php echo (!empty($gateway_options['pagseguro_app_id'])) ? $gateway_options['pagseguro_app_id'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
              </td>
              </tr>
              <tr class="form-field arm_pagseguro_fields <?php echo $pagseguro_live_fields; ?> ">
              <th class="arm-form-table-label"><?php _e('Production Application Key', ARM_PAGSEGURO_TEXTDOMAIN); ?> *</th>
              <td class="arm-form-table-content">
              <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_pagseguro_production_app_key" name="payment_gateway_settings[pagseguro][pagseguro_app_key]" value="<?php echo (!empty($gateway_options['pagseguro_app_key'])) ? $gateway_options['pagseguro_app_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
              </td>
              </tr>
              <tr class="form-field arm_pagseguro_fields <?php echo $pagseguro_live_fields; ?> ">
              <th class="arm-form-table-label"><?php _e('Production Authorization Code', ARM_PAGSEGURO_TEXTDOMAIN); ?> *</th>
              <td class="arm-form-table-content">
              <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_pagseguro_production_auth_code" name="payment_gateway_settings[pagseguro][pagseguro_auth_code]" value="<?php echo (!empty($gateway_options['pagseguro_auth_code'])) ? $gateway_options['pagseguro_auth_code'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
              </td>
              </tr>
             */ ?>
            <!-- ***** Ending of Live Input for pagseguro ***** -->

            <?php
        }
    }

    function arm_pagseguro_config() {
        global $arm_payment_gateways, $arm_global_settings;

        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        if (isset($all_payment_gateways['pagseguro']) && !empty($all_payment_gateways['pagseguro'])) {
            $payment_gateway_options = $all_payment_gateways['pagseguro'];
            $ARM_Pagseguro_payment_mode = $payment_gateway_options['pagseguro_payment_mode'];
            $is_sandbox_mode = $ARM_Pagseguro_payment_mode == "sandbox" ? true : false;

            $PagSeguroConfig = array();

            $PagSeguroConfig['environment'] = ( $is_sandbox_mode ) ? "sandbox" : "production"; // production, sandbox

            $PagSeguroConfig['credentials'] = array();
            $PagSeguroConfig['credentials']['email'] = ( $is_sandbox_mode ) ? $payment_gateway_options['pagseguro_sandbox_email'] : $payment_gateway_options['pagseguro_email'];
            $PagSeguroConfig['credentials']['token']['production'] = $payment_gateway_options['pagseguro_token'];
            $PagSeguroConfig['credentials']['token']['sandbox'] = $payment_gateway_options['pagseguro_sandbox_token'];

            $PagSeguroConfig['application'] = array();
            $PagSeguroConfig['application']['charset'] = "UTF-8"; // UTF-8, ISO-8859-1

            $PagSeguroConfig['log'] = array();
            $PagSeguroConfig['log']['active'] = false;
            // Informe o path completo (relativo ao path da lib) para o arquivo, ex.: ../PagSeguroLibrary/logs.txt
            $PagSeguroConfig['log']['fileLocation'] = "";

            return $PagSeguroConfig;
        }
    }

    function arm_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {

        global $wpdb, $ARMember, $arm_global_settings, $arm_membership_setup, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $paid_trial_stripe_payment_done;

        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        $currency = $arm_payment_gateways->arm_get_global_currency();
        if ($payment_gateway == 'pagseguro' && isset($all_payment_gateways['pagseguro']) && !empty($all_payment_gateways['pagseguro'])) {
            $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
            $current_payment_gateway = (isset($posted_data['payment_gateway'])) ? $posted_data['payment_gateway'] : '';
            if ($current_payment_gateway == '') {
                $current_payment_gateway = (isset($posted_data['_payment_gateway'])) ? $posted_data['_payment_gateway'] : '';
            }
            if (!empty($entry_data) && $current_payment_gateway == $payment_gateway) {

                $payment_mode_ = !empty($posted_data['arm_payment_mode']['pagseguro']) ? $posted_data['arm_payment_mode']['pagseguro'] : 'both';
                $recurring_payment_mode = 'manual_subscription';
                if ($payment_mode_ == 'both') {
                    $recurring_payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : 'manual_subscription';
                } else {
                    $recurring_payment_mode = $payment_mode_;
                }

                $form_id = $entry_data['arm_form_id'];
                $user_id = $entry_data['arm_user_id'];
                $plan_id = (!empty($posted_data['subscription_plan'])) ? $posted_data['subscription_plan'] : 0;
                if ($plan_id == 0) {
                    $plan_id = (!empty($posted_data['_subscription_plan'])) ? $posted_data['_subscription_plan'] : 0;
                }

                $plan = new ARM_Plan($plan_id);

                $plan_action = 'new_subscription';
                if (isset($posted_data['old_plan_id']) && !empty($posted_data['old_plan_id']) && $posted_data['old_plan_id'] != 0) {
                    if ($posted_data['old_plan_id'] == $plan_id) {
                        $plan_action = 'renew_subscription';
                    }
                }

                $plan_id = $plan->ID;
                $plan_payment_type = $plan->payment_type;
                $is_recurring = $plan->is_recurring();
                $plan_name = !empty($plan->name) ? $plan->name : "Plan Name";
                $amount = !empty($plan->amount) ? $plan->amount : "0";
                $iscouponfeature = false;
                
                $extraParam = array();
                if ($plan_action == 'new_subscription') {
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
                        $trial_period = $plan->options['trial']['period'];
                        $trial_interval = $plan->options['trial']['interval'];
                    }
                    $amount = str_replace(',','',$amount);

                    // Coupon Details 
                    $extraParam = array('plan_amount' => $amount, 'paid_amount' => $amount);
                    if ($arm_manage_coupons->isCouponFeature && isset($posted_data['arm_coupon_code']) && !empty($posted_data['arm_coupon_code'])) {
                        $couponApply = $arm_manage_coupons->arm_apply_coupon_code($posted_data['arm_coupon_code'], $plan);
                        $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                        $coupon_amount = str_replace(',','',$coupon_amount);

                        $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                        $discount_amt = str_replace(',','',$discount_amt);
                        if (!empty($coupon_amount) && $coupon_amount > 0) {
                            $iscouponfeature = true;
                            $extraParam['coupon'] = array(
                                'coupon_code' => $posted_data['arm_coupon_code'],
                                'amount' => $coupon_amount,
                            );
                        }
                    } else {
                        $posted_data['arm_coupon_code'] = '';
                        $discount_amt = $amount;
                    }
                } else {
                    $discount_amt = $amount;
                }

                $discount_amt = str_replace(",", "", $discount_amt);
                $discount_amt = number_format((float)$discount_amt, 2, '.','');

                $setup_id = $posted_data['setup_id'];
                $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                $redirect_page_id = $setup_data['arm_setup_modules']['redirect_page'];
                $arm_redirecturl = get_permalink($redirect_page_id);
                $arm_pagseguro_webhookurl = '';
                $arm_pagseguro_webhookurl = $arm_global_settings->add_query_arg("arm-listener", "arm_pagseguro_api", get_home_url() . "/");

                if (($discount_amt <= 0 || $discount_amt == '0.00') && $recurring_payment_mode == 'manual_subscription') {
                    $this->arm_add_user_and_transaction($entry_id, '-');
                    $redirect = '<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="' . $arm_redirecturl . '";</script>';
                    $return = array('status' => 'success', 'type' => 'redirect', 'message' => $redirect);
                    echo json_encode($return);
                    die;
                }
                
                if ($recurring_payment_mode == 'manual_subscription') {

                    $data_array['currency'] = $currency;
                    $data_array['item_id'] = $plan_id;
                    $data_array['item_name'] = $plan_name;
                    $data_array['item_qty'] = 1;
                    $data_array['item_amount'] = $discount_amt;
                    $data_array['reference'] = 'ref-' . $entry_id;
                    $data_array['redirect_url'] = $arm_redirecturl;
                    $data_array['notification_url'] = $arm_pagseguro_webhookurl;

                    $createpaymentrequest = new CreatePaymentRequest();
                    $response = $createpaymentrequest->main($data_array);

                    //$ARMember->arm_write_response("pagseguro redirect_url with code ==> " . maybe_serialize($response));

                    if ($response['status'] == 'success') {
                        $redirect = '<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="' . $response['redirect_url'] . '";</script>';
                        $return = array('status' => 'success', 'type' => 'redirect', 'message' => $redirect);
                        echo json_encode($return);
                        die;
                    } else {
                        //$err_msg = '<div class="arm_error_msg"><ul><li>' . $response['message'] . '</li></ul></div>';
                        $err_msg = $arm_global_settings->common_message['arm_payment_fail_pagseguro'];
                        $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Pagseguro',ARM_PAGSEGURO_TEXTDOMAIN);
                        $err_msg = '<div class="arm_error_msg"><ul><li>' . $err_msg . '</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $err_msg);
                        echo json_encode($return);
                        die;
                    }
                }
                else 
                {
                    $plan_not_support = 0;
                    $amount = !empty($plan->amount) ? $plan->amount : "0";
                    $recurring_data = $plan->prepare_recurring_data();    
                    
                    $amount = str_replace(",", "", $amount);
                    $amount = number_format((float)$amount, 2, '.','');
                    
                    $arm_pagseguro_recur_period = $recurring_data['period'];
                    switch ($arm_pagseguro_recur_period) {
                        case 'M':
                            $arm_pagseguro_payperiod = "months";
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
                            $arm_pagseguro_payperiod = "days";
                            break;
                        case 'W':
                            $plan_not_support++;
                            $arm_pagseguro_payperiod = "weeks";
                            break;
                        case 'Y':
                            $arm_pagseguro_payperiod = "years";
                            if($recurring_data['interval'] == 1) {
                                $arm_subscription_period = 'YEARLY';
                                $total_years = $recurring_data['rec_time'];
                                $arm_finel_date = date('Y-m-d', strtotime('+'.$total_years.' years'));
                            } else { 
                                $plan_not_support++;
                            }
                            break;
                    }
                    
                    $data_array['currency'] = $currency;
                    $data_array['item_id'] = $plan_id;
                    $data_array['item_name'] = $plan_name;
                    $data_array['item_qty'] = 1;
                    $data_array['item_amount'] = $amount;
                    $data_array['reference'] = 'ref-' . $entry_id;
                    $data_array['subscription_period'] = $arm_subscription_period;
                    $data_array['max_total_amount'] = number_format($recurring_data['rec_time'] * $amount, 2, '.', '');
                    $data_array['discount_percentage'] = isset($couponApply['discount']) ? number_format($couponApply['discount'],2) : 0.00 ;
                    $data_array['finel_date'] = $arm_finel_date.'T00:00:00';
                    $data_array['redirect_url'] = $arm_redirecturl;
                    $data_array['notification_url'] = $arm_pagseguro_webhookurl;
                    
                    
                    if( $plan_not_support > 0 || $plan->has_trial_period() )
                    {
                        $err_msg = '<div class="arm_error_msg"><ul><li>' . __('Payment through Pagseguro is not supported using auto debit payment for selected plan.', ARM_PAGSEGURO_TEXTDOMAIN) . '</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $err_msg);
                        echo json_encode($return);
                        die;
                    }
                    else if( $iscouponfeature == true )
                    {
                        $err_msg = '<div class="arm_error_msg"><ul><li>' . __('Payment through Pagseguro is not supported using auto debit payment with coupan for selected plan.', ARM_PAGSEGURO_TEXTDOMAIN) . '</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $err_msg);
                        echo json_encode($return);
                        die;
                    }
                    else
                    {
                        //$ARMember->arm_write_response("pagseguro subscription data ==> " . maybe_serialize($data_array));
                        $createpaymentrequest = new CreatePreApproval();
                        $response = $createpaymentrequest->main($data_array);

                        //$ARMember->arm_write_response("pagseguro subscription redirect_url with code ==> " . maybe_serialize($response));

                        if ($response['status'] == 'success' && isset($response['redirect_url']['checkoutUrl'])) {
                            $redirect = '<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="' . $response['redirect_url']['checkoutUrl'] . '";</script>';
                            $return = array('status' => 'success', 'type' => 'redirect', 'message' => $redirect);
                            echo json_encode($return);
                            die;
                        } else {
                            //$err_msg = '<div class="arm_error_msg"><ul><li>' . $response['message'] . '</li></ul></div>';
                            $err_msg = $arm_global_settings->common_message['arm_payment_fail_pagseguro'];
                            $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Pagseguro',ARM_PAGSEGURO_TEXTDOMAIN);
                            $err_msg = '<div class="arm_error_msg"><ul><li>' . $err_msg . '</li></ul></div>';
                            $return = array('status' => 'error', 'type' => 'message', 'message' => $err_msg);
                            echo json_encode($return);
                            die;
                        }
                    }
                }
            } else {
                
            }
        } else {
            
        }
    }

    function arm2_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {

        global $wpdb, $ARMember, $arm_global_settings, $arm_membership_setup, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $paid_trial_stripe_payment_done, $is_free_manual;
        
        $is_free_manual = false;

        $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
        $currency = $arm_payment_gateways->arm_get_global_currency();
        if ($payment_gateway == 'pagseguro' && isset($all_payment_gateways['pagseguro']) && !empty($all_payment_gateways['pagseguro'])) {
            $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
            $current_payment_gateway = (isset($posted_data['payment_gateway'])) ? $posted_data['payment_gateway'] : '';
            if ($current_payment_gateway == '') {
                $current_payment_gateway = (isset($posted_data['_payment_gateway'])) ? $posted_data['_payment_gateway'] : '';
            }
            if (!empty($entry_data) && $current_payment_gateway == $payment_gateway) {
                $payment_mode_ = !empty($posted_data['arm_payment_mode']['pagseguro']) ? $posted_data['arm_payment_mode']['pagseguro'] : 'both';
                $arm_subs_plan_id = (!empty($posted_data['subscription_plan'])) ? $posted_data['subscription_plan'] : 0;
                if ($arm_subs_plan_id == 0) {
                    $arm_subs_plan_id = (!empty($posted_data['_subscription_plan'])) ? $posted_data['_subscription_plan'] : 0;
                }
                
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
                    if ($payment_mode_ == 'both') {
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

                if ($is_recurring) {
                $setup_id = $posted_data['setup_id'];
                $payment_mode_ = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : 'manual_subscription';
                    if(isset($posted_data['arm_payment_mode']['pagseguro'])){
                        $payment_mode_ = !empty($posted_data['arm_payment_mode']['pagseguro']) ? $posted_data['arm_payment_mode']['pagseguro'] : 'manual_subscription';
                    }
                    else{
                        $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
                        if (!empty($setup_data) && !empty($setup_data['setup_modules']['modules'])) {
                            $setup_modules = $setup_data['setup_modules'];
                            $modules = $setup_modules['modules'];
                            $payment_mode_ = $modules['payment_mode']['pagseguro'];
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
                            $user_subsdata = $planData['pagseguro'];
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
                $amount = str_replace(',','',$amount);
                    
                    $arm_coupon_discount = $arm_coupon_discount_type = '';
                    $discount_amt = $amount;
                    $arm_coupon_on_each_subscriptions = 0;
                    $extraParam = array('plan_amount' => $amount, 'paid_amount' => $amount);
                    if ($arm_manage_coupons->isCouponFeature && isset($posted_data['arm_coupon_code']) && !empty($posted_data['arm_coupon_code'])) {
                        $couponApply = $arm_manage_coupons->arm_apply_coupon_code($posted_data['arm_coupon_code'], $plan, $setup_id, $payment_cycle, $arm_user_old_plan);
                        $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                        $coupon_amount = str_replace(',','',$coupon_amount);

                        $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                        $discount_amt = str_replace(',','',$discount_amt);
                        
                        $arm_coupon_discount = (isset($couponApply['discount']) && !empty($couponApply['discount'])) ? $couponApply['discount'] : 0;
                        $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $currency : "%";
                        if (!empty($coupon_amount) && $coupon_amount > 0) {
                            $extraParam['coupon'] = array(
                                'coupon_code' => $posted_data['arm_coupon_code'],
                                'amount' => $coupon_amount,
                            );

                            if(($plan->is_recurring() && $payment_mode == "manual_subscription") || ($plan->is_recurring() && $payment_mode == "auto_debit_subscription" && !empty($couponApply['arm_coupon_on_each_subscriptions'])) ) {
                                $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : 0;
                                $extraParam['coupon']["arm_coupon_on_each_subscriptions"] = $arm_coupon_on_each_subscriptions;
                            }
                        }
                    } else {
                        $posted_data['arm_coupon_code'] = '';
                    }
                

                


                $discount_amt = str_replace(",", "", $discount_amt);
                $amount = str_replace(",", "", $amount);
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
                if($arm_coupon_on_each_subscriptions==1)
                {
                    $amount = $discount_amt;
                }
                
                $arm_redirecturl = $entry_values['setup_redirect'];
                if (empty($setup_redirect)) {
                    $arm_redirecturl = ARM_HOME_URL;
                }

//                $setup_data = $arm_membership_setup->arm_get_membership_setup($setup_id);
//                $redirect_page_id = $setup_data['arm_setup_modules']['redirect_page'];
//                $arm_redirecturl = get_permalink($redirect_page_id);
                
                $arm_pagseguro_webhookurl = '';
                $arm_pagseguro_webhookurl = $arm_global_settings->add_query_arg("arm-listener", "arm_pagseguro_api", get_home_url() . "/");

                if ((($discount_amt <= 0 || $discount_amt == '0.00') && $recurring_payment_mode == 'manual_subscription' && $plan->is_recurring()) || (!$plan->is_recurring() && ($discount_amt <= 0 || $discount_amt == '0.00'))) {
                    
                    global $payment_done;
                    $pagseguro_response = array();
                    $current_user_id = 0;
                    if (is_user_logged_in()) {
                        $current_user_id = get_current_user_id();
                        $pagseguro_response['arm_user_id'] = $current_user_id;
                    }
                    $pagseguro_response['arm_plan_id'] = $plan->ID;
                    $pagseguro_response['arm_payment_gateway'] = 'pagseguro';
                    $pagseguro_response['arm_payment_type'] = $plan->payment_type;
                    $pagseguro_response['arm_token'] = '-';
                    $pagseguro_response['arm_payer_email'] = $user_email_add;
                    $pagseguro_response['arm_receiver_email'] = '';
                    $pagseguro_response['arm_transaction_id'] = '-';
                    $pagseguro_response['arm_transaction_payment_type'] = $plan->payment_type;
                    $pagseguro_response['arm_transaction_status'] = 'completed';
                    $pagseguro_response['arm_payment_mode'] = 'manual_subscription';
                    $pagseguro_response['arm_payment_date'] = date('Y-m-d H:i:s');
                    $pagseguro_response['arm_amount'] = 0;
                    $pagseguro_response['arm_currency'] = $currency;
                    $pagseguro_response['arm_coupon_code'] = $posted_data['arm_coupon_code'];
                    $pagseguro_response['arm_response_text'] = '';
                    $pagseguro_response['arm_extra_vars'] = '';
                    $pagseguro_response['arm_is_trial'] = $arm_is_trial;
                    $pagseguro_response['arm_created_date'] = current_time('mysql');
                    
                    $payment_log_id = $arm_payment_gateways->arm_save_payment_log($pagseguro_response);
                    $return = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                    $payment_done = $return;
                    $is_free_manual = true;
                    do_action('arm_after_pagseguro_free_payment',$plan,$payment_log_id,$arm_is_trial,$posted_data['arm_coupon_code'],$extraParam);
                    return $return;
                }
                
                if($is_recurring && $recurring_payment_mode == 'auto_debit_subscription' && !empty($recurring_data))
                {

                    $plan_not_support = 0;                    
                    $arm_pagseguro_recur_period = $recurring_data['period'];
                    switch ($arm_pagseguro_recur_period) {
                        case 'M':
                            $arm_pagseguro_payperiod = "months";
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
                            $arm_pagseguro_payperiod = "days";
                            break;
                        case 'W':
                            $plan_not_support++;
                            $arm_pagseguro_payperiod = "weeks";
                            break;
                        case 'Y':
                            $arm_pagseguro_payperiod = "years";
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
                    $data_array['redirect_url'] = $arm_redirecturl;
                    $data_array['notification_url'] = $arm_pagseguro_webhookurl;
                    
                    if( $plan_not_support > 0 || $plan->has_trial_period() )
                    {

                        
                        $err_msg = '<div class="arm_error_msg"><ul><li>' . __('Payment through Pagseguro is not supported using auto debit payment for selected plan.', ARM_PAGSEGURO_TEXTDOMAIN) . '</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $err_msg);
                        echo json_encode($return);
                        die;
                    }
                    else
                    {
                      
                        $createpaymentrequest = new CreatePreApproval();

                      
                        $response = $createpaymentrequest->main($data_array);
                        
                        if ($response['status'] == 'success' && isset($response['redirect_url']['checkoutUrl'])) {
                            $redirect = '<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="' . $response['redirect_url']['checkoutUrl'] . '";</script>';
                            $return = array('status' => 'success', 'type' => 'redirect', 'message' => $redirect);
                            echo json_encode($return);
                            die;
                        } else {
                            //$err_msg = '<div class="arm_error_msg"><ul><li>' . $response['message'] . '</li></ul></div>';
                            $err_msg = $arm_global_settings->common_message['arm_payment_fail_pagseguro'];
                            $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Pagseguro',ARM_PAGSEGURO_TEXTDOMAIN);
                            $err_msg = '<div class="arm_error_msg"><ul><li>' . $err_msg . '</li></ul></div>';
                            $return = array('status' => 'error', 'type' => 'message', 'message' => $err_msg);
                            echo json_encode($return);
                            die;
                        }
                    }
                }
                else 
                {

                    $extraVars['paid_amount'] = $discount_amt;
                    $data_array['currency'] = $currency;
                    $data_array['item_id'] = $plan_id;
                    $data_array['item_name'] = $plan_name;
                    $data_array['item_qty'] = 1;
                    $data_array['item_amount'] = $discount_amt;
                    $data_array['reference'] = 'ref-' . $entry_id;
                    $data_array['redirect_url'] = $arm_redirecturl;
                    $data_array['notification_url'] = $arm_pagseguro_webhookurl;

                    $createpaymentrequest = new CreatePaymentRequest();
                    $response = $createpaymentrequest->main($data_array);

                    if ($response['status'] == 'success') {
                        if($plan_action=='recurring_payment')
                        {
                            do_action('arm_after_recurring_payment_success_outside',$user_id,$plan_id,'pagseguro',$recurring_payment_mode,$user_subsdata);
                        }
                        $redirect = '<script data-cfasync="false" type="text/javascript" language="javascript">window.location.href="' . $response['redirect_url'] . '";</script>';
                        $return = array('status' => 'success', 'type' => 'redirect', 'message' => $redirect);
                        echo json_encode($return);
                        die;
                    } else {
                        //$err_msg = '<div class="arm_error_msg"><ul><li>' . $response['message'] . '</li></ul></div>';
                        $err_msg = $arm_global_settings->common_message['arm_payment_fail_pagseguro'];
                        $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Pagseguro',ARM_PAGSEGURO_TEXTDOMAIN);
                        $err_msg = '<div class="arm_error_msg"><ul><li>' . $err_msg . '</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $err_msg);
                        echo json_encode($return);
                        die;
                    }
                }
            } else {
                
            }
        } else {
            
        }
    }
    
    function arm_pagseguro_webhook($transaction_id = 0, $arm_listener = '', $tran_id = '') {

        if (isset($_REQUEST['arm-listener']) && in_array($_REQUEST['arm-listener'], array('arm_pagseguro_api'))) {
            global $wpdb, $ARMember, $arm_payment_gateways;
            //$ARMember->arm_write_response("pagseguro webhook request parameters ==> " . maybe_serialize($_REQUEST));
            if( $_REQUEST['notificationType'] == 'transaction' )
            {
                $notificationCode = $_REQUEST['notificationCode'];
                $credentials = PagSeguroConfig::getAccountCredentials();
                try {
                    $transaction = PagSeguroNotificationService::checkTransaction($credentials, $notificationCode);
                    //$ARMember->arm_write_response("pagseguro webhook transaction values ==> " . maybe_serialize($transaction));

                    $transaction_code = $transaction->getCode();
                    $reference = $transaction->getReference();
                    $status = $transaction->getStatus();
                    if (isset($reference) && isset($status)) {
                        if ($status->getValue() == 3) {
                            // debug status
                            //if ($this->debug === true) {
                                //$ARMember->arm_write_response("PagSeguro Gateway Debug 6: Status Check : OK");
                            //}
                            // update succesful payment
                            $reference_code = explode('-', $reference);
                            $entry_id = $reference_code[1];
                            $this->arm_add_user_and_transaction($entry_id, $transaction_code);
                        } else {
                            // debug status
                            if ($this->debug === true) {
                                //$ARMember->arm_write_response("PagSeguro Gateway Debug 6: Status Check : ERROR");
                            }
                        }
                    }

                    // Do something with $transaction
                } catch (PagSeguroServiceException $e) {
                    die($e->getMessage());
                }
            }
            else if( $_REQUEST['notificationType'] == 'preApproval' )
            {
                $notificationCode = $_REQUEST['notificationCode'];
                $credentials = PagSeguroConfig::getAccountCredentials();
                try {
                    $notificationType = new PagSeguroNotificationType('preApproval');
                    $strType = $notificationType->getTypeFromValue();
                    
                    $preApproval = PagSeguroNotificationService::checkPreApproval($credentials, $notificationCode);
                    //$ARMember->arm_write_response("pagseguro webhook transaction values ==> " .  maybe_serialize($preApproval));

                    $transaction_code = $preApproval->getCode();
                    $reference = $preApproval->getReference();
                    $status = $preApproval->getStatus();
                    if (isset($reference) && isset($status)) {
                        if ($status->getValue() == 2) {
                            
                            $reference_code = explode('-', $reference);
                            $entry_id = $reference_code[1];
                            
                            $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
                            $arm_entry_value = maybe_unserialize($entry_data['arm_entry_value']);
                            $arm_entry_value['arm_pagseguro_subscription_code'] = $transaction_code;
                            $arm_entry_value = maybe_serialize($arm_entry_value);
                            
                            $wpdb->update( 
                                    $ARMember->tbl_arm_entries, 
                                    array( 'arm_entry_value' => $arm_entry_value), 
                                    array( 'arm_entry_id' => $entry_id ), 
                                    array( '%s' ), 
                                    array( '%d' ) 
                            );
                            //$ARMember->arm_write_response("pagseguro webhook transaction values ==> " .  $reference . ' -- '.$transaction_code);
                            //$this->arm_add_user_and_transaction($entry_id, $transaction_code);
                        } else {
                            // debug status
                            if ($this->debug === true) {
                                //$ARMember->arm_write_response("PagSeguro Gateway Debug 6: Status Check : ERROR");
                            }
                        }
                    }

                    // Do something with $transaction
                } catch (PagSeguroServiceException $e) {
                    //$ARMember->arm_write_response("pagseguro webhook transaction values ==> " . $e->getMessage());
                    die($e->getMessage());
                }
            } 
        }
    }

    function arm2_pagseguro_webhook($transaction_id = 0, $arm_listener = '', $tran_id = '') {

        if (isset($_REQUEST['arm-listener']) && in_array($_REQUEST['arm-listener'], array('arm_pagseguro_api'))) {
            global $wpdb, $ARMember, $arm_manage_communication, $arm_payment_gateways;
            //$ARMember->arm_write_response("pagseguro webhook request parameters ==> " . maybe_serialize($_REQUEST));
            if( $_REQUEST['notificationType'] == 'transaction' )
            {
                $notificationCode = $_REQUEST['notificationCode'];
                $credentials = PagSeguroConfig::getAccountCredentials();
                try {
                    $transaction = PagSeguroNotificationService::checkTransaction($credentials, $notificationCode);
                    //$ARMember->arm_write_response("pagseguro webhook transaction values ==> " . maybe_serialize($transaction));

                    $transaction_code = $transaction->getCode();
                    $reference = $transaction->getReference();
                    $status = $transaction->getStatus();
                    if (isset($reference) && isset($status)) {
                        if ($status->getValue() == 3) {
                            // debug status
                            //if ($this->debug === true) {
                                //$ARMember->arm_write_response("PagSeguro Gateway Debug 6: Status Check : OK");
                            //}
                            // update succesful payment
                            $reference_code = explode('-', $reference);
                            $entry_id = $reference_code[1];
                            $arm_get_payment_log = $wpdb->get_row( $wpdb->prepare( "SELECT arm_log_id FROM `$ARMember->tbl_arm_payment_log` WHERE arm_transaction_id = %s", $transaction_code), ARRAY_A );
                            $arm_log_id = ( isset($arm_get_payment_log['arm_log_id']) && !empty($arm_get_payment_log['arm_log_id']) ) ? $arm_get_payment_log['arm_log_id'] : '';
                            if($arm_log_id == '') {
                                $this->arm2_add_user_and_transaction($entry_id, $transaction_code);
                            }
                        } else {
                            // debug status
                            if ($this->debug === true) {
                                //$ARMember->arm_write_response("PagSeguro Gateway Debug 6: Status Check : ERROR");
                            }
                        }
                    }

                    // Do something with $transaction
                } catch (PagSeguroServiceException $e) {
                    die($e->getMessage());
                }
            }
            else if( $_REQUEST['notificationType'] == 'preApproval' )
            {
                $notificationCode = $_REQUEST['notificationCode'];
                $credentials = PagSeguroConfig::getAccountCredentials();
                try {
                    $notificationType = new PagSeguroNotificationType('preApproval');
                    $strType = $notificationType->getTypeFromValue();
                    
                    $preApproval = PagSeguroNotificationService::checkPreApproval($credentials, $notificationCode);
                    //$ARMember->arm_write_response("pagseguro webhook transaction values ==> " .  maybe_serialize($preApproval));

                    $transaction_code = $preApproval->getCode();
                    $reference = $preApproval->getReference();
                    $reference_code = explode('-', $reference);
                    $entry_id = $reference_code[1];
                    $status = $preApproval->getStatus();
                    if (isset($reference) && isset($status)) {
                        if ($status->getValue() == 2) {
                            
                            $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
                            $arm_entry_value = maybe_unserialize($entry_data['arm_entry_value']);
                            $arm_entry_value['arm_pagseguro_subscription_code'] = $transaction_code;
                            $arm_entry_value = maybe_serialize($arm_entry_value);
                            
                            $wpdb->update( 
                                    $ARMember->tbl_arm_entries, 
                                    array( 'arm_entry_value' => $arm_entry_value), 
                                    array( 'arm_entry_id' => $entry_id ), 
                                    array( '%s' ), 
                                    array( '%d' ) 
                            );
                            //$ARMember->arm_write_response("pagseguro webhook transaction values ==> " .  $reference . ' -- '.$transaction_code);
                            $arm_display_log = 0;
                            $this->arm2_add_user_and_transaction($entry_id, $transaction_code, $arm_display_log);
                            
                            $payLog = $wpdb->get_row( $wpdb->prepare("SELECT `arm_user_id`, `arm_plan_id` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_token`=%s AND `arm_payment_gateway`=%s ORDER BY `arm_log_id` DESC", $transaction_code, 'pagseguro'));
                            $user_id = $payLog->arm_user_id;
                            $plan_id = $payLog->arm_plan_id;
                            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                            $user_subsdata = $planData['pagseguro'];
                            $payment_mode = $planData['arm_payment_mode'];
                            do_action('arm_after_recurring_payment_success_outside',$user_id,$plan_id,'pagseguro',$payment_mode,$user_subsdata);
                        } else {
                            // debug status
                            if ($this->debug === true) {
                                //$ARMember->arm_write_response("PagSeguro Gateway Debug 6: Status Check : ERROR");
                                $payLog = $wpdb->get_row( $wpdb->prepare("SELECT `arm_user_id`, `arm_plan_id` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_token`=%s AND `arm_payment_gateway`=%s ORDER BY `arm_log_id` DESC", $transaction_code, 'pagseguro'));
                                $user_id = $payLog->arm_user_id;
                                $plan_id = $payLog->arm_plan_id;
                                $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                                $user_subsdata = $planData['pagseguro'];
                                $payment_mode = $planData['arm_payment_mode'];
                                do_action('arm_after_recurring_payment_failed_outside',$user_id,$plan_id,'pagseguro',$payment_mode,$user_subsdata);
                            }
                        }
                    }

                    // Do something with $transaction
                } catch (PagSeguroServiceException $e) {
                    //$ARMember->arm_write_response("pagseguro webhook transaction values ==> " . $e->getMessage());
                    die($e->getMessage());
                }
            } 
        }
    }
    
    function arm_add_user_and_transaction($entry_id = 0, $tran_id = '') {
        global $wpdb, $pagseguro, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication, $arm_manage_coupons, $payment_done, $paid_trial_stripe_payment_done;
        if (isset($entry_id) && $entry_id != '') {
            $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
            if (isset($all_payment_gateways['pagseguro']) && !empty($all_payment_gateways['pagseguro'])) {
                $options = $all_payment_gateways['pagseguro'];
                $payment_mode = $options['pagseguro_payment_mode'];
                $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;
                $currency = $arm_payment_gateways->arm_get_global_currency();

                $entry_data = $wpdb->get_row("SELECT * FROM `" . $ARMember->tbl_arm_entries . "` WHERE `arm_entry_id`='" . $entry_id . "' ", ARRAY_A);
                $arm_entry_value = maybe_unserialize($entry_data['arm_entry_value']);
                $entry_email = isset($entry_data['arm_entry_email']) ? $entry_data['arm_entry_email'] : '';
                $arm_log_plan_id = $entry_data['arm_plan_id'];
                $arm_log_amount = isset($arm_entry_value['arm_total_payable_amount']) ? $arm_entry_value['arm_total_payable_amount'] : '';
                $arm_token = $tran_id;
                $plan = new ARM_Plan($arm_log_plan_id);
                $arm_payment_type = $plan->payment_type;
                $entry_id = $entry_id;
                $payment_status = 'success';

                $plan_action = 'new_subscription';
                if (isset($arm_entry_value['arm_user_old_plan']) && !empty($arm_entry_value['arm_user_old_plan']) && $arm_entry_value['arm_user_old_plan'] != 0) {
                    if ($arm_entry_value['arm_user_old_plan'] == $plan_id) {
                        $plan_action = 'renew_subscription';
                    } else {
                        $plan_action = 'change_subscription';
                    }
                }

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
                        $trial_period = $plan->options['trial']['period'];
                        $trial_interval = $plan->options['trial']['interval'];
                    }
                }

                if (!empty($entry_data) && $arm_payment_type == 'one_time') {
                    $is_log = false;
                    $extraParam = array('plan_amount' => $arm_log_amount, 'paid_amount' => $arm_log_amount);
                    $entry_values = maybe_unserialize($entry_data['arm_entry_value']);
                    $entry_plan = $entry_data['arm_plan_id'];
                    $pagsegurolLog['arm_coupon_code'] = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                    $pagsegurolLog['arm_payment_type'] = $arm_payment_type;
                    $pagsegurolLog['token_id'] = $arm_token;
                    $pagsegurolLog['arm_transaction_id'] = $arm_token;
                    $pagsegurolLog['payer_email'] = $entry_email;
                    $pagsegurolLog['payment_type'] = $arm_payment_type;
                    $pagsegurolLog['payment_status'] = 'success';
                    $pagsegurolLog['payment_date'] = $entry_data['arm_created_date'];
                    $pagsegurolLog['payment_amount'] = $arm_log_amount;
                    $pagsegurolLog['currency'] = $currency;
                    $pagsegurolLog['arm_is_trial'] = $arm_is_trial;
                    $extraParam['trans_id'] = isset($arm_token) ? $arm_token : '';
                    $extraParam['date'] = current_time('mysql');
                    $extraParam['message_type'] = isset($arm_payment_type) ? $arm_payment_type : '';

                    $form_id = $entry_data['arm_form_id'];
                    $armform = new ARM_Form('id', $form_id);
                    $user_info = get_user_by('email', $entry_email);
                    $new_plan = new ARM_Plan($entry_plan);
                    $extraParam['plan_amount'] = $new_plan->amount;
                    if (!$user_info && in_array($armform->type, array('registration'))) {
                        /* Coupon Details */
                        $couponCode = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                        if (!empty($couponCode)) {
                            $couponApply = $arm_manage_coupons->arm_apply_coupon_code($couponCode, $new_plan);
                            $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                            if ($coupon_amount != 0) {
                                $extraParam['coupon'] = array(
                                    'coupon_code' => $couponCode,
                                    'amount' => $coupon_amount,
                                );
                            }
                        }

                        $payment_log_id = self::arm_store_pagseguro_log($pagsegurolLog, 0, $entry_plan, $extraParam);
                        $payment_done = array();
                        $paid_trial_stripe_payment_done = array();
                        if ($payment_log_id) {
                            $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                            $paid_trial_stripe_payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id, 'gateway' => 'pagseguro');
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
                        $payment_log_id = self::arm_store_pagseguro_log($pagsegurolLog, $user_id, $entry_plan, $extraParam);
                        $old_plan_id = get_user_meta($user_id, 'arm_user_plan', true);
                        $old_subscription_id = get_user_meta($user_id, 'arm_subscr_id_' . $old_plan_id, true);
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
                            update_user_meta($user_id, 'arm_using_gateway_' . $entry_plan, 'pagseguro');
                            if (!empty($arm_token)) {
                                update_user_meta($user_id, 'arm_subscr_id_' . $entry_plan, $arm_token);
                            }
                            if ($is_update_plan) {
                                $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan);
                            } else {
                                $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'change_subscription');
                            }
                        } else if ($old_plan_id == $entry_plan && (empty($old_subscription_id) || $old_subscription_id == '')) {
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
                            update_user_meta($user_id, 'arm_using_gateway_' . $entry_plan, 'pagseguro');
                            if (!empty($arm_token)) {
                                update_user_meta($user_id, 'arm_subscr_id_' . $entry_plan, $arm_token);
                            }
                            if ($is_update_plan) {
                                $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan);
                            } else {
                                $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'change_subscription');
                            }
                            $is_log = true;
                        } else {
                            $entry_values['arm_coupon_code'] = '';
                        }
                        $is_log = true;
                    }

                    if ($is_log && !empty($user_id) && $user_id != 0) {
                        $payment_log_id = self::arm_store_pagseguro_log($pagsegurolLog, $user_id, $entry_plan, $extraParam);
                        delete_user_meta($user_id, 'arm_payment_cancelled_by');
                    }
                } else if (!empty($entry_data) && $arm_payment_type == 'subscription') {
                    $extraParam = array('plan_amount' => $arm_log_amount, 'paid_amount' => $arm_log_amount, 'payment_type' => 'pagseguro', 'payment_mode' => $payment_mode);
                    $entry_plan = $entry_data['arm_plan_id'];
                    $entry_values = maybe_unserialize($entry_data['arm_entry_value']);

                    $pagsegurolLog['arm_coupon_code'] = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                    $pagsegurolLog['arm_payment_type'] = $arm_payment_type;
                    $pagsegurolLog['token_id'] = $entry_values['arm_pagseguro_subscription_code'];
                    $pagsegurolLog['arm_transaction_id'] = $arm_token;
                    $pagsegurolLog['payer_email'] = $entry_email;
                    $pagsegurolLog['payment_type'] = $arm_payment_type;
                    $pagsegurolLog['payment_status'] = 'success';
                    $pagsegurolLog['payment_date'] = $entry_data['arm_created_date'];
                    $pagsegurolLog['payment_amount'] = $arm_log_amount;
                    $pagsegurolLog['currency'] = $currency;
                    $pagsegurolLog['arm_is_trial'] = $arm_is_trial;

                    $form_id = $entry_data['arm_form_id'];
                    $armform = new ARM_Form('id', $form_id);
                    $user_info = get_user_by('email', $entry_email);
                    $new_plan = new ARM_Plan($entry_plan);
                    $couponCode = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                    /* Coupon Details */
                    $extraParam['plan_amount'] = $new_plan->amount;
                    if (!empty($couponCode)) {
                        $couponApply = $arm_manage_coupons->arm_apply_coupon_code($couponCode, $new_plan);
                        $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                        if ($coupon_amount != 0) {
                            $extraParam['coupon'] = array(
                                'coupon_code' => $couponCode,
                                'amount' => $coupon_amount,
                            );
                        }
                    }


                    if (!$user_info && in_array($armform->type, array('registration'))) {
                        $payment_log_id = self::arm_store_pagseguro_log($pagsegurolLog, 0, $entry_plan, $extraParam);
                        $payment_done = array();
                        $paid_trial_stripe_payment_done = array();
                        if ($payment_log_id) {
                            $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                            $paid_trial_stripe_payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id, 'gateway' => 'pagseguro');
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
                        $payment_log_id = self::arm_store_pagseguro_log($pagsegurolLog, $user_id, $entry_plan, $extraParam);
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
                            update_user_meta($user_id, 'arm_using_gateway_' . $entry_plan, 'pagseguro');
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
                            update_user_meta($user_id, 'arm_using_gateway_' . $entry_plan, 'pagseguro');
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
        }
    }

    function arm2_add_user_and_transaction($entry_id = 0, $tran_id = '', $arm_display_log = 1) {

        global $wpdb, $pagseguro, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication, $arm_manage_coupons, $payment_done, $paid_trial_stripe_payment_done, $arm_members_class;

        if (isset($entry_id) && $entry_id != '') {
            $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
            if (isset($all_payment_gateways['pagseguro']) && !empty($all_payment_gateways['pagseguro'])) {
                $options = $all_payment_gateways['pagseguro'];
                $pagseguro_payment_mode = $options['pagseguro_payment_mode'];
                $is_sandbox_mode = $pagseguro_payment_mode == "sandbox" ? true : false;
                $currency = $arm_payment_gateways->arm_get_global_currency();

                $entry_data = $wpdb->get_row("SELECT * FROM `" . $ARMember->tbl_arm_entries . "` WHERE `arm_entry_id`='" . $entry_id . "' ", ARRAY_A);
                $entry_values = maybe_unserialize($entry_data['arm_entry_value']);
                $entry_email = isset($entry_data['arm_entry_email']) ? $entry_data['arm_entry_email'] : '';
                $arm_log_plan_id = $entry_data['arm_plan_id'];
                $arm_log_amount = isset($entry_values['arm_total_payable_amount']) ? $entry_values['arm_total_payable_amount'] : '';
                $arm_token = $tran_id;
                $plan = new ARM_Plan($arm_log_plan_id);
                $arm_payment_type = $plan->payment_type;
                $entry_id = $entry_id;
                $payment_status = 'success';
                $form_id = $entry_data['arm_form_id'];
                $armform = new ARM_Form('id', $form_id);
                $user_info = get_user_by('email', $entry_email);
                
               
                $extraParam = array();
                $tax_percentage = isset($entry_values['tax_percentage']) ? $entry_values['tax_percentage'] : 0;
                $extraParam['tax_percentage'] = $tax_percentage;
                $payment_mode = $entry_values['arm_selected_payment_mode'];
                $payment_cycle = $entry_values['arm_selected_payment_cycle'];
                $arm_user_old_plan = (isset($entry_values['arm_user_old_plan']) && !empty($entry_values['arm_user_old_plan'])) ? explode(",", $entry_values['arm_user_old_plan']) : array();
                $setup_id = $entry_values['setup_id'];
                $entry_plan = $entry_data['arm_plan_id'];
                $pagseguroLog['arm_coupon_code'] = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                $pagseguroLog['arm_payment_type'] = $arm_payment_type;
                $pagseguroLog['payment_type'] = $arm_payment_type;
                $pagseguroLog['payment_status'] = $payment_status;
                $pagseguroLog['cust_id'] = '';
                $pagseguroLog['token_id'] = $arm_token;
                $pagseguroLog['arm_transaction_id'] = $arm_token;
                $pagseguroLog['payer_email'] = $entry_email;
                $pagseguroLog['payment_date'] = $entry_data['arm_created_date'];
                $extraParam['payment_type'] = 'pagseguro';
                $extraParam['payment_mode'] = $pagseguro_payment_mode;
                $extraParam['arm_is_trial'] = '0';
                $extraParam['subs_id'] = $arm_token;
                $extraParam['trans_id'] = $arm_token;
                $extraParam['error'] = '';
                $extraParam['date'] = current_time('mysql');
                $extraParam['message_type'] = '';

                $amount = '';
                $form_id = $entry_data['arm_form_id'];
                $armform = new ARM_Form('id', $form_id);
                $user_info = get_user_by('email', $entry_email);        
                $new_plan = new ARM_Plan($entry_plan);
                $user_id = isset($user_info->ID) ? $user_info->ID : 0;

                if ($new_plan->is_recurring()) {
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
                $extraParam['plan_amount'] = str_replace(',','',$extraParam['plan_amount']);

                $discount_amt = $extraParam['plan_amount'];
                $arm_coupon_discount = 0;
                $amount_for_tax = $discount_amt;
                $arm_coupon_on_each_subscriptions = 0;
                $couponCode = isset($entry_values['arm_coupon_code']) ? $entry_values['arm_coupon_code'] : '';
                if (!empty($couponCode)) {
                    $couponApply = $arm_manage_coupons->arm_apply_coupon_code($couponCode, $new_plan, $setup_id, $payment_cycle, $arm_user_old_plan);
                    $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                    $coupon_amount = str_replace(',','',$coupon_amount);

                    $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                    $discount_amt = str_replace(',','',$discount_amt);

                    if ($coupon_amount != 0) {
                        $extraParam['coupon'] = array(
                            'coupon_code' => $couponCode,
                            'amount' => $coupon_amount,
                        );

                        $arm_coupon_discount = $couponApply['discount'];
                        $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $global_currency : "%";
                        $pagseguroLog['coupon_code'] = $couponCode;
                        $pagseguroLog['arm_coupon_discount'] = $arm_coupon_discount;
                        $pagseguroLog['arm_coupon_discount_type'] = $arm_coupon_discount_type;

                        if(($new_plan->is_recurring() && $payment_mode == "manual_subscription") || ($new_plan->is_recurring() && $payment_mode == "auto_debit_subscription" && !empty($couponApply['arm_coupon_on_each_subscriptions'])) ) {
                            $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : 0;
                            $extraParam['coupon']["arm_coupon_on_each_subscriptions"] = $arm_coupon_on_each_subscriptions;
                            $pagseguroLog['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                        }
                    }
                } 

                $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                $pagseguroLog['currency'] = $currency;
                $pagseguroLog['payment_amount'] = $discount_amt;

                $pgateway = 'pagseguro';

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
                    $pagseguroLog['payment_amount'] = $amount_for_tax;
                    $payment_log_id = self::arm_store_pagseguro_log($pagseguroLog, 0, $entry_plan, $extraParam, $arm_display_log);
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
                            $userPlanData['arm_pagseguro']['transaction_id'] = $arm_token;
                            update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                            $arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $payment_log_id, $pgateway, $userPlanData);
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
                                $old_subscription_id = $oldPlanData['arm_pagseguro']['sale_id'];
                            }

                            $arm_user_old_plan_details = (isset($userPlanData['arm_current_plan_detail']) && !empty($userPlanData['arm_current_plan_detail'])) ? $userPlanData['arm_current_plan_detail'] : array();
                            $arm_user_old_plan_details['arm_user_old_payment_mode'] = $userPlanData['arm_payment_mode'];
                            
                            if(!empty($old_subscription_id) && $payment_mode == 'auto_debit_subscription' && $old_subscription_id == $arm_token)
                            {
                                //$ARMember->arm_write_response("reputelog pagseguro subscription id else");   
                                $arm_next_due_payment_date = $userPlanData['arm_next_due_payment'];
                                if(!empty($arm_next_due_payment_date)){
                                    if(strtotime(current_time('mysql')) >= $arm_next_due_payment_date){
                                        $arm_user_completed_recurrence = $userPlanData['arm_completed_recurring'];
                                        $arm_user_completed_recurrence++;
                                        $userPlanData['arm_completed_recurring'] = $arm_user_completed_recurrence;
                                        update_user_meta($user_id, 'arm_user_plan_'.$entry_plan, $userPlanData);
                                        $arm_next_payment_date = $arm_members_class->arm_get_next_due_date($user_id, $entry_plan, false, $payment_cycle);
                                        if ($arm_next_payment_date != '') {
                                            //$ARMember->arm_write_response("reputelog 3");
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
                                //$ARMember->arm_write_response("reputelog pagseguro user data : ".maybe_serialize($userPlanData));
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
                                $ARMember->arm_write_response("pagseguro log : ".date('Y-m-d')." User ID : ". $user_id." in single membership old subscription id is empty");
                                $ARMember->arm_write_response("pagseguro log : last payment status => ".maybe_serialize($arm_last_payment_status));

                                $ARMember->arm_write_response(maybe_serialize($userPlanData));

                                $userPlanData['arm_current_plan_detail'] = $arm_user_old_plan_details;
                                
                                $ARMember->arm_write_response("pagseguro log no multiple membership and not subscription");
                                $ARMember->arm_write_response("reputelog pagseguro subscription id if");   
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
                                $extraParam['paid_amount'] = $arm_log_amount;
                                $pagseguroLog['payment_amount'] = $arm_log_amount;
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
                                $userPlanData['arm_user_gateway'] = 'pagseguro';

                                if (!empty($arm_token)) {
                                    $userPlanData['arm_pagseguro']['transaction_id'] = $arm_token;
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
                                $old_subscription_id = $oldPlanData['arm_pagseguro']['transaction_id'];



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
                                    $pagseguroLog['payment_amount'] = $amount_for_tax;

                                    update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                    $userPlanData['arm_user_gateway'] = 'pagseguro';

                                    if (!empty($arm_token)) {
                                        $userPlanData['arm_pagseguro']['transaction_id'] = $arm_token;
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
                                    $pagseguroLog['payment_amount'] = $amount_for_tax;

                                update_user_meta($user_id, 'arm_entry_id', $entry_id);
                                $userPlanData['arm_user_gateway'] = 'pagseguro';

                                if (!empty($arm_token)) {
                                    $userPlanData['arm_pagseguro']['transaction_id'] = $arm_token;
                                }
                                update_user_meta($user_id, 'arm_user_plan_' . $entry_plan, $userPlanData);
                                if ($is_update_plan) {
                                    $arm_subscription_plans->arm_update_user_subscription($user_id, $entry_plan,  '', true, $arm_last_payment_status);
                                } else {
                                   $arm_subscription_plans->arm_add_membership_history($user_id, $entry_plan, 'new_subscription');
                                }
                            }
                        }
                        


                        $payment_log_id = self::arm_store_pagseguro_log($pagseguroLog, $user_id, $entry_plan, $extraParam, $arm_display_log);

                        if ($arm_payment_type == 'subscription' && $pagseguroLog['arm_coupon_on_each_subscriptions']=='1') {
                            $arm_manage_coupons->arm_coupon_apply_to_subscription($user_id, $payment_log_id, $pgateway, $userPlanData);
                        }
                    }
                }
            }
        }
    }
    
    function arm_store_pagseguro_log($pagseguro_response = '', $user_id = 0, $plan_id = 0, $extraVars = array(), $arm_display_log = '1') {
        global $wpdb, $ARMember, $arm_global_settings, $arm_member_forms, $arm_payment_gateways;
        $payment_log_table = $ARMember->tbl_arm_payment_log;
        $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $pagseguro_response['arm_transaction_id']));
        if (!empty($pagseguro_response) && empty($transaction)) {
            $payment_data = array(
                'arm_user_id' => $user_id,
                'arm_plan_id' => (!empty($plan_id) ? $plan_id : 0),
                'arm_payment_gateway' => 'pagseguro',
                'arm_payment_type' => $pagseguro_response['arm_payment_type'],
                'arm_token' => $pagseguro_response['token_id'],
                'arm_payer_email' => $pagseguro_response['payer_email'],
                'arm_receiver_email' => '',
                'arm_transaction_id' => $pagseguro_response['arm_transaction_id'],
                'arm_transaction_payment_type' => $pagseguro_response['payment_type'],
                'arm_transaction_status' => $pagseguro_response['payment_status'],
                'arm_payment_date' => date('Y-m-d H:i:s', strtotime($pagseguro_response['payment_date'])),
                'arm_amount' => $pagseguro_response['payment_amount'],
                'arm_currency' => $pagseguro_response['currency'],
                'arm_coupon_code' => $pagseguro_response['arm_coupon_code'],
                'arm_coupon_discount' => (isset($pagseguro_response['arm_coupon_discount']) && !empty($pagseguro_response['arm_coupon_discount'])) ? $pagseguro_response['arm_coupon_discount'] : 0,
                'arm_coupon_discount_type' => isset($pagseguro_response['arm_coupon_discount_type']) ? $pagseguro_response['arm_coupon_discount_type'] : '',
                'arm_response_text' => maybe_serialize($pagseguro_response),
                'arm_extra_vars' => maybe_serialize($extraVars),
                'arm_is_trial' => isset($pagseguro_response['arm_is_trial']) ? $pagseguro_response['arm_is_trial'] : 0,
                'arm_display_log' => $arm_display_log,
                'arm_created_date' => current_time('mysql'),
                'arm_coupon_on_each_subscriptions' => isset($pagseguro_response['arm_coupon_on_each_subscriptions']) ? $pagseguro_response['arm_coupon_on_each_subscriptions'] : 0
            );
            $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
            return $payment_log_id;
        }
        return false;
    }

    function arm_pagseguro_cancel_subscription($user_id, $plan_id){
        global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
        if (isset($user_id) && $user_id != 0 && isset($plan_id) && $plan_id != 0) {
            $user_detail = get_userdata($user_id);
            $payer_email = $user_detail->user_email;
            $user_payment_gateway = get_user_meta($user_id, 'arm_using_gateway_' . $plan_id, true);
            if ($user_payment_gateway == 'pagseguro') {
                
                $payment_log_table = $ARMember->tbl_arm_payment_log;
                $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'pagseguro', 'success'));
                if (!empty($transaction)) {
                    $extra_var = maybe_unserialize($transaction->arm_extra_vars);
                    $payer_email = $transaction->arm_payer_email;
                    $payment_type = $extra_var['payment_type'];
                    $payment_mode = $extra_var['payment_mode'];
                    $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;
                    
                    $gateway_options = get_option('arm_payment_gateway_settings');
                    $pgoptions = maybe_unserialize($gateway_options);
                    $pgoptions = $pgoptions['pagseguro'];
                                        
                    if ($payment_type == 'pagseguro') {
                        
                        $credentials = PagSeguroConfig::getAccountCredentials();

                        $response = PagSeguroPreApprovalService::cancelPreApproval($credentials, $transaction->arm_token);

                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'cancel_payment'));
                        $payment_data = array(
                            'arm_user_id' => $user_id,
                            'arm_plan_id' => $plan_id,
                            'arm_payment_gateway' => 'pagseguro',
                            'arm_payment_type' => 'subscription',
                            'arm_token' => $transaction->arm_token,
                            'arm_payer_email' => $payer_email,
                            'arm_receiver_email' => '',
                            'arm_transaction_id' => $transaction->arm_transaction_id,
                            'arm_transaction_payment_type' => $transaction->arm_transaction_payment_type,
                            'arm_transaction_status' => 'canceled',
                            'arm_payment_date' => current_time('mysql'),
                            'arm_amount' => 0,
                            'arm_currency' => '',
                            'arm_coupon_code' => '',
                            'arm_response_text' => maybe_serialize($response),
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
    
    function arm2_pagseguro_cancel_subscription($user_id, $plan_id){
        global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
        if (isset($user_id) && $user_id != 0 && isset($plan_id) && $plan_id != 0) {
            $user_detail = get_userdata($user_id);
            $payer_email = $user_detail->user_email;
            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
            $currency = $arm_payment_gateways->arm_get_global_currency();
            if(!empty($planData)){
                $user_payment_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
                if ($user_payment_gateway == 'pagseguro') {
                    
                    $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                    $planDetail = $planData['arm_current_plan_detail'];

                    if (!empty($planDetail)) { 
                        $planObj = new ARM_Plan(0);
                        $planObj->init((object) $planDetail);
                    } else {
                        $planObj = new ARM_Plan($plan_id);
                    }

        //                    if ($planObj->is_recurring() && $user_selected_payment_mode == 'manual_subscription') {
        //                        return false;
        //                    }
                    
                    $payment_log_table = $ARMember->tbl_arm_payment_log;
                    $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s AND `arm_display_log` = %d ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'pagseguro', 'success', 0));

                    $transaction_id = $wpdb->get_row($wpdb->prepare("SELECT arm_token,arm_transaction_id,arm_extra_vars,arm_payer_email,arm_transaction_payment_type,arm_amount FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s AND `arm_display_log` = %d ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'pagseguro', 'success', 1));
                     $ARMember->arm_write_response("reptuelog payment log id => ".maybe_serialize($transaction));
                    if (!empty($transaction)) {
                        $extra_var = maybe_unserialize($transaction->arm_extra_vars);
                        $payer_email = $transaction->arm_payer_email;
                        $payment_type = $extra_var['payment_type'];
                        $payment_mode = $extra_var['payment_mode'];
                        $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;

                        $gateway_options = get_option('arm_payment_gateway_settings');
                        $pgoptions = maybe_unserialize($gateway_options);
                        $pgoptions = $pgoptions['pagseguro'];
                        if ($payment_type == 'pagseguro') {
                            if($user_selected_payment_mode == 'auto_debit_subscription') {
                                $ARMember->arm_write_response("reputelog pagseguro transaction ");
                                $credentials = PagSeguroConfig::getAccountCredentials();
                                $ARMember->arm_write_response("reputelog pagseguro credentials => ".maybe_serialize($credentials));
                                $response = PagSeguroPreApprovalService::cancelPreApproval($credentials, $transaction->arm_token);
                                $ARMember->arm_write_response("reputelog pagseguro transaction => ".maybe_serialize($response));
                                $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'cancel_payment'));
                                $payment_data = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan_id,
                                    'arm_payment_gateway' => 'pagseguro',
                                    'arm_payment_type' => 'subscription',
                                    'arm_token' => $transaction_id->arm_token,
                                    'arm_payer_email' => $payer_email,
                                    'arm_receiver_email' => '',
                                    'arm_transaction_id' => $transaction_id->arm_transaction_id,
                                    'arm_transaction_payment_type' => $transaction->arm_transaction_payment_type,
                                    'arm_transaction_status' => 'canceled',
                                    'arm_payment_date' => current_time('mysql'),
                                    'arm_amount' => $transaction_id->arm_amount,
                                    'arm_currency' => $currency,
                                    'arm_coupon_code' => '',
                                    'arm_response_text' => maybe_serialize($response),
                                    'arm_created_date' => current_time('mysql')
                                );
                                //$is_cancelled_by_system = get_user_meta($user_id, 'arm_payment_cancelled_by', true);
                                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
                                $ARMember->arm_write_response("reptuelog payment log id => ".$payment_log_id);
                                delete_user_meta($user_id, 'arm_payment_cancelled_by');
                                return;
                            } else {
                                $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                $payment_data = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan_id,
                                    'arm_payment_gateway' => 'pagseguro',
                                    'arm_payment_type' => 'subscription',
                                    'arm_payer_email' => $payer_email,
                                    'arm_receiver_email' => '',
                                    'arm_transaction_id' => $transaction_id->arm_transaction_id,
                                    'arm_token' => $transaction_id->arm_token,
                                    'arm_transaction_payment_type' => 'subscription',
                                    'arm_payment_mode' => 'manual_subscription',
                                    'arm_transaction_status' => 'canceled',
                                    'arm_payment_date' => current_time('mysql'),
                                    'arm_amount' => $transaction_id->arm_amount,
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
    
    function arm_pagseguro_modify_coupon_code($data,$payment_mode,$couponData,$planAmt, $plan_obj){

        if(isset($plan_obj) && !empty($plan_obj)){
        if( $plan_obj->is_recurring() && $payment_mode=='auto_debit_subscription' && empty($couponData['arm_coupon_on_each_subscriptions']) ) {
            if( $data['status'] == 'success' ){
                $data['coupon_amt'] = '0.00';
                $data['total_amt'] = $planAmt;
            }
        }
        }
        return $data;
    }
    
    function arm_prevent_rocket_loader_script($tag, $handle) {
        $pattern = '/(.*?)(data\-cfasync\=)(.*?)/';
        preg_match_all($pattern,$tag,$matches);
        if( !is_array($matches) ){
            return str_replace(' src', ' data-cfasync="false" src', $tag);
        } else if( !empty($matches) && !empty($matches[2]) && !empty($matches[2][0]) && strtolower(trim($matches[2][0])) != 'data-cfasync=' ){
            return str_replace(' src', ' data-cfasync="false" src', $tag);
        } else if( !empty($matches) && empty($matches[2]) ) {
            return str_replace(' src', ' data-cfasync="false" src', $tag);
        } else {
            return $tag;
        }
    }
    
}

global $arm_pagseguro;
$arm_pagseguro = new ARM_Pagseguro();

if ($arm_pagseguro->is_armember_support() && $arm_pagseguro->is_version_compatible()) {
    if (file_exists(ARM_PAGSEGURO_DIR . "/lib/PagSeguroLibrary/PagSeguroLibrary.php")) {
        require_once ARM_PAGSEGURO_DIR . "/lib/PagSeguroLibrary/PagSeguroLibrary.php";
    }
}


global $armpagseguro_api_url, $armpagseguro_plugin_slug;

$armpagseguro_api_url = $arm_pagseguro->armpagseguro_getapiurl();
$armpagseguro_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armpagseguro_check_for_plugin_update');

function armpagseguro_check_for_plugin_update($checked_data) {
    global $armpagseguro_api_url, $armpagseguro_plugin_slug, $wp_version, $arm_pagseguro_version,$arm_pagseguro;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armpagseguro_plugin_slug,
        'version' => $arm_pagseguro_version,
        'other_variables' => $arm_pagseguro->armpagseguro_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMPAGSEGURO-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armpagseguro_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armpagseguro_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armpagseguro_plugin_slug . '/' . $armpagseguro_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armpagseguro_plugin_api_call', 10, 3);

function armpagseguro_plugin_api_call($def, $action, $args) {
    global $armpagseguro_plugin_slug, $armpagseguro_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armpagseguro_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armpagseguro_plugin_slug . '/' . $armpagseguro_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armpagseguro_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMPAGSEGURO-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armpagseguro_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', MEMBERSHIP_TXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', MEMBERSHIP_TXTDOMAIN), $request['body']);
    }

    return $res;
}

class PagSeguroConfigWrapper {

    public static function getConfig() {
        global $arm_pagseguro;
        return $arm_pagseguro->arm_pagseguro_config();
    }

}

class CreatePaymentRequest {

    public static function main($data_array) {
        $paymentRequest = new PagSeguroPaymentRequest();

        $paymentRequest->setCurrency($data_array['currency']);

        $paymentRequest->addItem($data_array['item_id'], $data_array['item_name'], $data_array['item_qty'], $data_array['item_amount']);

        $paymentRequest->setReference($data_array['reference']);

        $paymentRequest->setRedirectUrl($data_array['redirect_url']);

        $paymentRequest->addMetadata('PASSENGER_CPF', '15600944276', 1);
        $paymentRequest->addMetadata('GAME_NAME', 'DOTA');
        $paymentRequest->addMetadata('PASSENGER_PASSPORT', '23456', 1);

        $paymentRequest->addParameter('notificationURL', $data_array['notification_url']);

        $paymentRequest->acceptPaymentMethodGroup('CREDIT_CARD', 'DEBITO_ITAU');
        $paymentRequest->excludePaymentMethodGroup('BOLETO', 'BOLETO');

        try {
            $credentials = PagSeguroConfig::getAccountCredentials();
            
            $url = $paymentRequest->register($credentials);

            $response = array('status' => 'success', 'redirect_url' => $url);
            return $response;
        } catch (PagSeguroServiceException $e) {
            $response = array('status' => 'failed', 'message' => $e->getMessage());
            return $response;
        }
    }
}

class CreatePreApproval {
    public static function main($data_array) {
        
        $preApprovalRequest = new PagSeguroPreApprovalRequest();
        
        $preApprovalRequest->setCurrency($data_array['currency']);

        $preApprovalRequest->setReference($data_array['reference']);

        $preApprovalRequest->setPreApprovalCharge('auto'); // auto and manual
        $preApprovalRequest->setPreApprovalName($data_array['item_name']);
        $preApprovalRequest->setPreApprovalDetails($data_array['item_name'].' - '.$data_array['item_id']);
        $preApprovalRequest->setPreApprovalAmountPerPayment($data_array['item_amount']);
        //$preApprovalRequest->setPreApprovalMaxAmountPerPeriod('200.00');  // only require for manual charge
        $preApprovalRequest->setPreApprovalPeriod($data_array['subscription_period']); // values should be WEEKLY, MONTHLY, BIMONTHLY, TRIMONTHLY, SEMIANNUALLY, YEARLY.
        $preApprovalRequest->setPreApprovalMaxTotalAmount($data_array['max_total_amount']);
        //$preApprovalRequest->setPreApprovalInitialDate('2017-01-04T00:00:00');  // only require for manual charge
        $preApprovalRequest->setPreApprovalFinalDate($data_array['finel_date']);
        $preApprovalRequest->setRedirectURL($data_array['redirect_url']);
        $preApprovalRequest->setReviewURL($data_array['notification_url']);
        
//        $preApprovalRequest->addParameter('paymentMethodGroup1', 'ONLINE_DEBIT');
//        $preApprovalRequest->addParameter('paymentMethodConfigKey1_1', 'DISCOUNT_PERCENT');
//        $preApprovalRequest->addParameter('paymentMethodConfigValue1_1', '50.00');
        
//        $preApprovalRequest->addPaymentMethodConfig('CREDIT_CARD', $data_array['discount_percentage'], 'DISCOUNT_PERCENT');
//        $preApprovalRequest->addPaymentMethodConfig('EFT', $data_array['discount_percentage'], 'DISCOUNT_PERCENT');
//        //$preApprovalRequest->addPaymentMethodConfig('BOLETO', $data_array['discount_percentage'], 'DISCOUNT_PERCENT');
//        $preApprovalRequest->addPaymentMethodConfig('DEPOSIT', $data_array['discount_percentage'], 'DISCOUNT_PERCENT');
//        $preApprovalRequest->addPaymentMethodConfig('BALANCE', $data_array['discount_percentage'], 'DISCOUNT_PERCENT');
//        
//        $preApprovalRequest->addPaymentMethodConfig('CREDIT_CARD', 6, 'MAX_INSTALLMENTS_NO_INTEREST');
//        
//        $preApprovalRequest->addPaymentMethodConfig('CREDIT_CARD', 8, 'MAX_INSTALLMENTS_LIMIT');
//        // Add and remove a group and payment methods
//        $preApprovalRequest->acceptPaymentMethodGroup('CREDIT_CARD', 'DEBITO_ITAU');      
//        $preApprovalRequest->excludePaymentMethodGroup('BOLETO', 'BOLETO');
        
        try {

            $credentials = PagSeguroConfig::getAccountCredentials();
            $url = $preApprovalRequest->register($credentials);

            $response = array('status' => 'success', 'redirect_url' => $url);
            return $response;
        } catch (PagSeguroServiceException $e) {
            $response = array('status' => 'failed', 'message' => $e->getMessage());
            return $response;
        }
    }
}
?>