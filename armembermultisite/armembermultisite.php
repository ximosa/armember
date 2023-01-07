<?php
/*
  Plugin Name: ARMember - Member Network Site Addon
  Description: Extension for ARMember plugin to allow members to create subsite.
  Version: 1.1
  Plugin URI: https://www.armemberplugin.com
  Author: Repute InfoSystems
  Author URI: https://www.armemberplugin.com
  Text Domain: ARM_MULTISUBSITE
*/

define('ARM_MULTISUBSITE_DIR_NAME', 'armembermultisite');
define('ARM_MULTISUBSITE_DIR', WP_PLUGIN_DIR . '/' . ARM_MULTISUBSITE_DIR_NAME);

if (is_ssl()) {
    define('ARM_MULTISUBSITE_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_MULTISUBSITE_DIR_NAME));
} else {
    define('ARM_MULTISUBSITE_URL', WP_PLUGIN_URL . '/' . ARM_MULTISUBSITE_DIR_NAME);
}

define( 'ARM_MULTISUBSITE_CORE_DIR', ARM_MULTISUBSITE_DIR . '/core/' );
define( 'ARM_MULTISUBSITE_CLASSES_DIR', ARM_MULTISUBSITE_CORE_DIR . 'classes/' );
define( 'ARM_MULTISUBSITE_VIEW_DIR', ARM_MULTISUBSITE_CORE_DIR . 'views/' );
define( 'ARM_MULTISUBSITE_IMAGES_URL', ARM_MULTISUBSITE_URL . '/images/' );

define('ARM_MULTISUBSITE_TEXTDOMAIN', 'ARM_MULTISUBSITE');

define('ARM_MULTISUBSITE_DOC_URL', ARM_MULTISUBSITE_URL . '/documentation/index.html#content');

global $arm_multisubsite_version;
$arm_multisubsite_version = '1.1';

global $armnew_multisubsite_version;


global $armmultisubsite_api_url, $armmultisubsite_plugin_slug, $wp_version;


class ARM_MultiSubSite{

    public $site_limit_msg;
    
    function __construct() {

        global $arm_payment_gateways;
        
        add_action('init', array($this, 'arm_multisubsite_db_check'));

        register_activation_hook(__FILE__, array('ARM_MultiSubSite', 'install'));
        register_activation_hook(__FILE__, array('ARM_MultiSubSite', 'arm_multisite_check_network_activation'));

        register_uninstall_hook(__FILE__, array('ARM_MultiSubSite', 'uninstall'));

        add_action('admin_notices', array($this, 'arm_multisubsite_admin_notices'));
               
        add_action('arm_after_common_messages_settings_html', array($this, 'arm_multisite_related_common_message'), 10);

        add_action( 'arm_after_global_settings_html', array($this, 'arm_multisite_global_option'),10);

        if ($this->arm_multisubsite_is_compatible())
        {
        	add_action( 'admin_menu', array( $this, 'arm_multisubsite_menu' ), 30 );
	        add_action('admin_enqueue_scripts', array($this, 'set_admin_css'), 13);
	        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_script'), 12);
	        add_action('wp_head', array($this, 'set_front_js'), 1);
	        add_action('wp_head', array($this, 'set_front_css'), 1);

            add_action( 'user_register',array(&$this,'arm_subsite_add_capabilities_to_new_user') );

            add_filter( 'arm_before_update_global_settings', array($this, 'arm_multisite_update_all_settings'), 10, 2);
            add_filter( 'arm_view_member_details_outside', array($this, 'arm_multisite_member_details'), 10, 2);

            add_action('wp_ajax_arm_multisite_delete', array($this, 'arm_multisite_delete'));

            add_action('wp_ajax_arm_site_creation', array($this, 'arm_site_creation'));

            add_action('wp_ajax_arm_multisite_deactive', array($this, 'arm_multisite_deactive'));
            add_action( 'arm_after_user_plan_change', array($this, 'arm_multisite_deactive'), 10, 2);

            add_action( 'arm_user_plan_status_action_failed_payment', array($this, 'arm_multisite_user_status'), 10, 2);
            add_action( 'arm_user_plan_status_action_cancel_payment', array($this, 'arm_multisite_user_status'), 10, 2);

            add_action( 'arm_cancel_subscription', array($this, 'arm_multisite_user_status_site_remove'), 10, 2);
            add_action( 'arm_user_plan_status_action_eot', array($this, 'arm_multisite_user_status_site_remove'), 10, 2);

            add_action( 'arm_after_user_plan_renew', array($this, 'arm_multisite_user_plan_renew'), 10, 2);
            add_action( 'add_others_section_option_tinymce',array($this,'arm_multisite_shortcode_option'),10,2);
            add_action( 'add_others_section_select_option_tinymce',array($this,'arm_multisite_select_option'),10,2);
            add_action( 'add_others_section_select_option_tinymce',array($this,'arm_multisite_manage_option'),10,2);
            add_action('arm_shortcode_add_tab_buttons',array($this,'arm_multisite_shortcode_add_tab_buttons'));
            add_action('arm_shortcode_add_tab_buttons',array($this,'arm_manage_multisite_shortcode_add_tab_buttons'));
            add_action( 'arm_display_field_add_membership_plan', array( $this, 'arm_multisite_plan_field_function' ) );
            add_filter( 'arm_befor_save_field_membership_plan', array( $this, 'before_save_multitesite_field_membership_plan' ), 10, 2 );
            add_action('wp_ajax_arm_multisite_activate', array($this, 'arm_multisite_activate'));

            add_action('wp_ajax_arm_multisite_deactivate', array($this, 'arm_multisite_deactivate'));

            add_filter('arm_set_plan_attribute_outside', array($this, 'arm_set_plan_attribute_outside'), 2, 2);

            add_action("wp_ajax_paging_multisite_list", array($this, 'paging_multisite_list'));

            add_action('arm_after_user_plan_change_by_admin', array($this, 'arm_deactivate_multisite_after_action_by_admin'), 10, 2);

            add_shortcode( 'arm_site_creation', array($this ,'arm_multisite_creation'));
            add_shortcode( 'arm_manage_subsite', array($this ,'arm_multisite_view'));

            add_action( 'arm_after_add_new_user', array( $this, 'arm_subsite_save_plan_details' ), 11, 2 );
            add_action( 'arm_after_add_transaction', array( $this, 'arm_subsite_save_plan_details_add_transaction' ), 11, 1 );
            add_action( 'arm_after_accept_bank_transfer_payment', array( $this, 'arm_subsite_save_plan_details_after_approved' ), 11, 2 );
        }
        
        
        add_action('plugins_loaded', array($this, 'arm_multisubsite_load_textdomain'));
		
		add_action('admin_init', array($this, 'upgrade_data_multisubsite'));

        

        
    }

    function arm_subsite_save_plan_details( $user_id, $posted_data )
    {
        global $wpdb, $ARMember, $arm_payment_gateways;

        $plan_id = isset( $posted_data['subscription_plan'] ) ? $posted_data['subscription_plan'] : 0;
        if ( $plan_id == 0 ) {
            $plan_id = isset($posted_data['_subscription_plan']) ? $posted_data['_subscription_plan'] : 0;
        }

        $arm_pgateway = isset($posted_data['payment_gateway']) ? $posted_data['payment_gateway'] : '';
        if ($arm_pgateway == '') {
            $arm_pgateway = isset($posted_data['_payment_gateway']) ? $posted_data['_payment_gateway'] : '';
        }

        if ($arm_pgateway != '' && $plan_id > 0) {
            $is_succeed_payment = 0;
            if ($arm_pgateway == 'bank_transfer') {
                $plan_txn_id = isset($posted_data['bank_transfer']['transaction_id']) ? $posted_data['bank_transfer']['transaction_id'] : '';
                $arm_entry = $wpdb->get_row($wpdb->prepare("SELECT `arm_status` FROM `{$ARMember->tbl_arm_bank_transfer_log}` WHERE `arm_transaction_id` = '%s' order by arm_log_id desc ", $plan_txn_id), OBJECT);
                if( isset($arm_entry->arm_status) && $arm_entry->arm_status == 1 ){
                    $is_succeed_payment = 1;
                }
            } else {
                $arm_entry = $wpdb->get_row($wpdb->prepare("SELECT `arm_transaction_status` FROM `{$ARMember->tbl_arm_payment_log}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d ORDER BY `arm_log_id` DESC LIMIT 1", $user_id, $plan_id), OBJECT);
                if( isset($arm_entry->arm_transaction_status) && $arm_entry->arm_transaction_status == 'success' ){
                    $is_succeed_payment = 1;
                }

            }

            if($is_succeed_payment==1)
            {
                global $arm_subscription_plans, $is_multiple_membership_feature;
                

                $user_plan_details = $arm_subscription_plans->arm_get_subscription_plan($plan_id,'arm_subscription_plan_id, arm_subscription_plan_name, arm_subscription_plan_options');


                $arm_multisite_create = (!empty($user_plan_details['arm_subscription_plan_options']['arm_multisite_create'])) ? $user_plan_details['arm_subscription_plan_options']['arm_multisite_create'] : 0;
                $enable_disable_planwise_multisite = !empty($user_plan_details['arm_subscription_plan_options']['enable_disable_planwise_multisite']) ? $user_plan_details['arm_subscription_plan_options']['enable_disable_planwise_multisite'] : 0;
                $arm_multisite_user_role = (!empty($user_plan_details['arm_subscription_plan_options']["arm_multisite_user_role"])) ? $user_plan_details['arm_subscription_plan_options']["arm_multisite_user_role"] : 'administrator';

                
                $member_plan_details_update = get_user_meta($user_id, 'arm_multisite_member_plan_options', true);
                if(empty($member_plan_details_update) || !$is_multiple_membership_feature->isMultipleMembershipFeature)
                {
                    $member_plan_details_update = array();
                }


                $member_plan_details_update[$plan_id]['arm_multisite_create'] = $arm_multisite_create;
                $member_plan_details_update[$plan_id]['enable_disable_planwise_multisite'] = $enable_disable_planwise_multisite;
                $member_plan_details_update[$plan_id]['arm_multisite_user_role'] = $arm_multisite_user_role;

                update_user_meta($user_id, 'arm_multisite_member_plan_options', $member_plan_details_update);


            }
        }

    }

    function arm_subsite_save_plan_details_add_transaction($log_data)
    {
        if( (isset($log_data['arm_transaction_status']) && $log_data['arm_transaction_status'] == 'success') || isset($log_data['arm_bank_name']) ) 
        {
            global $wpdb, $ARMember, $arm_payment_gateways;
            $user_id = isset($log_data['arm_user_id']) ? $log_data['arm_user_id'] : 0;

            if($user_id == 0){ return; }

            $entry_id = get_user_meta($user_id, 'arm_entry_id');

            $arm_tbl_entry = $ARMember->tbl_arm_entries;
            $entry_data_value = $wpdb->get_row($wpdb->prepare("SELECT `arm_entry_value` FROM `{$arm_tbl_entry}` WHERE `arm_user_id` = %d AND `arm_entry_id` = %d ", $user_id, $entry_id[0]), ARRAY_A);
            $entry_data = maybe_unserialize($entry_data_value['arm_entry_value']);

            $plan_id = isset( $log_data['arm_plan_id'] ) ? $log_data['arm_plan_id'] : 0;
            if ( $plan_id == 0 ) {
                $plan_id = isset($entry_data['_subscription_plan']) ? $entry_data['_subscription_plan'] : 0;
            }

            $arm_pgateway = isset($log_data['arm_payment_gateway']) ? $log_data['arm_payment_gateway'] : '';
            if ($arm_pgateway == '') {
                $arm_pgateway = isset($entry_data['payment_gateway']) ? $entry_data['payment_gateway'] : '';
            }

            $is_succeed_payment = 0;

            if ($arm_pgateway == 'bank_transfer') {
                $plan_txn_id = isset($log_data['bank_transfer']['transaction_id']) ? $log_data['bank_transfer']['transaction_id'] : '';
                $arm_entry = $wpdb->get_row($wpdb->prepare("SELECT `arm_status` FROM `{$ARMember->tbl_arm_bank_transfer_log}` WHERE `arm_transaction_id` = %d ", $plan_txn_id), OBJECT);
                if( isset($arm_entry->arm_status) && $arm_entry->arm_status == 1 ){
                    $is_succeed_payment = 1;
                }
            } else {
                $arm_entry = $wpdb->get_row($wpdb->prepare("SELECT `arm_transaction_status`, `arm_payment_mode` FROM `{$ARMember->tbl_arm_payment_log}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d ORDER BY `arm_log_id` DESC LIMIT 1", $user_id, $plan_id), OBJECT);
                if( isset($arm_entry->arm_transaction_status) && $arm_entry->arm_transaction_status == 'success' ){
                    $is_succeed_payment = 1;
                }

            }

            if($is_succeed_payment==1 && $plan_id>0 && !empty($arm_pgateway))
            {
                $arm_subsite_is_recurring_payment = $this->arm_subsite_is_plan_recurring($user_id, $plan_id, $arm_pgateway);

                if($arm_subsite_is_recurring_payment==0)
                {

                    global $arm_subscription_plans, $is_multiple_membership_feature;

                    $user_plan_details = $arm_subscription_plans->arm_get_subscription_plan($plan_id,'arm_subscription_plan_id, arm_subscription_plan_name, arm_subscription_plan_options');


                    $arm_multisite_create = (!empty($user_plan_details['arm_subscription_plan_options']['arm_multisite_create'])) ? $user_plan_details['arm_subscription_plan_options']['arm_multisite_create'] : 0;
                    $enable_disable_planwise_multisite = !empty($user_plan_details['arm_subscription_plan_options']['enable_disable_planwise_multisite']) ? $user_plan_details['arm_subscription_plan_options']['enable_disable_planwise_multisite'] : 0;
                    $arm_multisite_user_role = (!empty($user_plan_details['arm_subscription_plan_options']["arm_multisite_user_role"])) ? $user_plan_details['arm_subscription_plan_options']["arm_multisite_user_role"] : 'administrator';


                    $member_plan_details_update = get_user_meta($user_id, 'arm_multisite_member_plan_options', true);
                    if(empty($member_plan_details_update) || !$is_multiple_membership_feature->isMultipleMembershipFeature)
                    {
                        $member_plan_details_update = array();
                    }

                    $member_plan_details_update[$plan_id]['arm_multisite_create'] = $arm_multisite_create;
                    $member_plan_details_update[$plan_id]['enable_disable_planwise_multisite'] = $enable_disable_planwise_multisite;
                    $member_plan_details_update[$plan_id]['arm_multisite_user_role'] = $arm_multisite_user_role;

                    update_user_meta($user_id, 'arm_multisite_member_plan_options', $member_plan_details_update);

                }
            }
        }
    }

    function arm_subsite_is_plan_recurring($user_id, $user_plan, $payment_gateway = '')
    {
        $arm_is_recurring = 0;

        $plan_info = new ARM_Plan($user_plan);

        $planData = get_user_meta($user_id, 'arm_user_plan_' . $user_plan, true);

        if( $plan_info->is_recurring() ) {

            $payment_cycle = $planData['arm_payment_cycle'];
            $recurring_plan_options = $plan_info->prepare_recurring_data($payment_cycle);
            $recurring_time = $recurring_plan_options['rec_time'];
            $completed = $planData['arm_completed_recurring'];

            if( $recurring_time != 'infinite' && $recurring_time > 0 ) {

                if( $payment_gateway == 'bank_transfer' || in_array($payment_gateway, array('2checkout', 'stripe')) ) {
                    if( $recurring_time > 1 && $completed > 1) {
                        $arm_is_recurring = 1;
                    }
                } else {
                    if( $recurring_time == $completed ) {
                        $arm_is_recurring = 0;
                    } else {
                        $arm_is_recurring = 1;
                    }
                }
            }

        }

        return $arm_is_recurring;
    }

    function arm_subsite_save_plan_details_after_approved( $user_id, $plan_id )
    {
        if($user_id <= 0 || $plan_id <= 0 ){
            return;
        }

        $arm_is_recurring_payment = $this->arm_subsite_is_plan_recurring($user_id, $plan_id, 'bank_transfer');
        if($arm_is_recurring_payment != 1)
        {
            global $arm_subscription_plans, $is_multiple_membership_feature;

            $user_plan_details = $arm_subscription_plans->arm_get_subscription_plan($plan_id,'arm_subscription_plan_id, arm_subscription_plan_name, arm_subscription_plan_options');


            $arm_multisite_create = (!empty($user_plan_details['arm_subscription_plan_options']['arm_multisite_create'])) ? $user_plan_details['arm_subscription_plan_options']['arm_multisite_create'] : 0;
            $enable_disable_planwise_multisite = !empty($user_plan_details['arm_subscription_plan_options']['enable_disable_planwise_multisite']) ? $user_plan_details['arm_subscription_plan_options']['enable_disable_planwise_multisite'] : 0;
            $arm_multisite_user_role = (!empty($user_plan_details['arm_subscription_plan_options']["arm_multisite_user_role"])) ? $user_plan_details['arm_subscription_plan_options']["arm_multisite_user_role"] : 'administrator';


            $member_plan_details_update = get_user_meta($user_id, 'arm_multisite_member_plan_options', true);
            if(empty($member_plan_details_update) || !$is_multiple_membership_feature->isMultipleMembershipFeature)
            {
                $member_plan_details_update = array();
            }

            $member_plan_details_update[$plan_id]['arm_multisite_create'] = $arm_multisite_create;
            $member_plan_details_update[$plan_id]['enable_disable_planwise_multisite'] = $enable_disable_planwise_multisite;
            $member_plan_details_update[$plan_id]['arm_multisite_user_role'] = $arm_multisite_user_role;

            update_user_meta($user_id, 'arm_multisite_member_plan_options', $member_plan_details_update);

        }
    }


    function arm_multisubsite_load_textdomain() {
        load_plugin_textdomain('ARM_MULTISUBSITE', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public static function arm_multisubsite_db_check() {
        global $arm_multisubsite;
        $arm_multisubsite_version = get_option('arm_multisubsite_version');

        if (!isset($arm_multisubsite_version) || $arm_multisubsite_version == '')
            $arm_multisubsite->install();
    }

    function paging_multisite_list() {
        $response = array();

        if(!empty($_REQUEST['current_page']) && !empty($_REQUEST['per_page'])) {
            global $arm_subscription_plans, $arm_global_settings, $is_multiple_membership_feature;

            $user_multisite_common_message = $arm_global_settings->common_message;

            $delete_button_label = isset($_REQUEST['delete_button_label']) ? $_REQUEST['delete_button_label'] : esc_html__('Delete', 'ARM_MULTISUBSITE') ;
            $activate_button_label = isset($_REQUEST['activate_button_label']) ? $_REQUEST['activate_button_label'] : esc_html__('Activate', 'ARM_MULTISUBSITE');
            $deactivate_button_label = isset($_REQUEST['deactivate_button_label']) ? $_REQUEST['deactivate_button_label'] : esc_html__('Deactivate', 'ARM_MULTISUBSITE');

            $current_page = $_REQUEST['current_page'];
            $prev_page = $current_page - 1;
            $per_page = $_REQUEST['per_page'];

            $end_index = ($current_page * $per_page) - 1;
            $start_index = ($prev_page * $per_page);

            $user_id = get_current_user_id();
            $user_multisite_list = get_user_meta($user_id, 'arm_multisite_id', true);
            $all_membership_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name,arm_subscription_plan_options');
            $arm_manage_multisite_no_record_msg = esc_html__('There is no record of subsite', 'ARM_MULTISUBSITE');

            $table = "";

            if(!empty($user_multisite_list)) {
                $arm_subsite_display_form = 0;
                foreach ($user_multisite_list as $user_plan_list_id) 
                {

                    $current_user_site_id = $user_plan_list_id['site_id'];
                    $current_user_plan_id = $user_plan_list_id['plan_id'];

                    $enable_disable_planwise_multisite = !empty($all_membership_plans[$current_user_plan_id]['arm_subscription_plan_options']['enable_disable_planwise_multisite']) ? $all_membership_plans[$current_user_plan_id]['arm_subscription_plan_options']['enable_disable_planwise_multisite'] : 0;

                    if(!empty($user_plan_list_id) && $enable_disable_planwise_multisite == 1)
                    {
                        $arm_subsite_display_form = 1;
                    }

                }
                $response['pagination_link'] = "";
                if (1 == $arm_subsite_display_form) {
                    $totalRecord = count($user_multisite_list);
                    $current_page = $_REQUEST['current_page']; $perPage = $_REQUEST['per_page'];
                    $historyPaging = $arm_global_settings->arm_get_paging_links($current_page, $totalRecord, $perPage, 'subsite_listing');
                    $response['pagination_link'] = $historyPaging;

                    krsort( $user_multisite_list );
                    $rec_cnt = 0;
                    foreach ($user_multisite_list as $user_plan_list_id) {
                        
                        if($start_index <= $rec_cnt && $end_index >= $rec_cnt) {
                            $arm_multisite_subsite_id = !empty($user_plan_list_id['site_id']) ? $user_plan_list_id['site_id'] : '';
                            $arm_multisite_subsite_plan_id = !empty($user_plan_list_id['plan_id']) ? $user_plan_list_id['plan_id'] : '';

                            if(!empty($arm_multisite_subsite_id) && !empty($arm_multisite_subsite_plan_id))
                            {
                                $current_user_plan_name = !empty($all_membership_plans[$arm_multisite_subsite_plan_id]['arm_subscription_plan_name']) ? $all_membership_plans[$arm_multisite_subsite_plan_id]['arm_subscription_plan_name'] : '';
                                $blog_user_details = get_blog_details($arm_multisite_subsite_id);
                                $userblog_id = $blog_user_details->blog_id;
                                $user_blog_site = $blog_user_details->blogname;
                                $current_admin_site_url = get_admin_url($userblog_id);
                                $user_plan_list = get_user_meta($user_id, 'arm_user_plan_ids', true);

                                $multisite_delete_btn = "";
                                if($blog_user_details->deleted == 1) {                                    
                                    $user_suspend_plan_list = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
                                    $activate_btn_cls = "";
                                    $activation_cnf_content = "";
                                    if($is_multiple_membership_feature->isMultipleMembershipFeature) {
                                        $activation_dd_content = '<div class="arm_form_input_wrapper"><div class="arm_form_input_container_select arm_form_input_container" id="arm_form_input_container"><select class="md-visually-hidden" name="arm_activate_plan_list">';
                                    
                                        foreach ($user_plan_list as $key => $user_plan) {
                                            if(!in_array($user_plan, $user_suspend_plan_list)) {
                                                $plan_name = $arm_subscription_plans->arm_get_subscription_plan($user_plan,'arm_subscription_plan_id, arm_subscription_plan_name');
                                                $activation_dd_content .= '<option value="'.$plan_name['arm_subscription_plan_id'].'">'.$plan_name["arm_subscription_plan_name"].'</option>';    
                                            }
                                            

                                        }
                                        
                                        $activation_dd_content .= '</select></div></div>';
                                        $multisiteActiveConfirm = isset($user_multisite_common_message['arm_multisite_activation_alert']) ? $user_multisite_common_message['arm_multisite_activation_alert'] : esc_html__('Are you sure you want to activate this Site?', 'ARM_MULTISUBSITE');
                                        $activation_cnf_content = '<div class="arm_confirm_activation_box" id="arm_confirm_activation_box_'.$userblog_id.'">
                                            <div class="arm_confirm_box_body">
                                                <div class="arm_confirm_box_arrow"></div>
                                                <div class="arm_confirm_box_text">'.esc_html__("Please select a plan to activate the site.", 'ARM_MULTISUBSITE').'</div>
                                                '.$activation_dd_content.'
                                                <div class="arm_confirm_box_btn_container">
                                                    <button type="button" class="arm_confirm_box_btn armok arm_mutisite_subsite_activate_btn" data-userplan="'.$user_plan_list_id['plan_id'].'" data-userid="'.$user_id.'" data-siteid="'.$userblog_id.'" data-msg="'.$multisiteActiveConfirm.'" data-is_multiple_membership="1">'.$activate_button_label.'</button>
                                                    <button type="button" class="arm_confirm_box_btn armcancel" id="arm_multisite_cancel_activate_btn">'.esc_html__('Cancel', 'ARM_MULTISUBSITE').'</button>
                                                </div>
                                            </div></div>';
                                            $activate_btn_cls = "arm_activate_multisite";
                                    } else {
                                        $activate_btn_cls = "arm_mutisite_subsite_activate_btn";
                                    }  

                                    if(!empty($user_plan_list)) {
                                        $multisite_delete_btn .= '<div class="arm_multisite_btn_div"><button type="button" class="'.$activate_btn_cls.'" data-userplan="'.$user_plan_list_id['plan_id'].'" data-userid="'.$user_id.'" data-siteid="'.$userblog_id.'" data-msg="'.$multisiteActiveConfirm.'">'.$activate_button_label.'</button>'.$activation_cnf_content.'</div>';    
                                    }
                                    
                                } else {
                                    if(!empty($user_plan_list)) {
                                        $cnf_deactive_content = '<div class="arm_confirm_activation_box" id="arm_confirm_activation_box_">
                                            <div class="arm_confirm_box_body">
                                                <div class="arm_confirm_box_arrow"></div>
                                                <div class="arm_confirm_box_text">'.$user_multisite_common_message['arm_multisite_deactivation_alert'].'</div>
                                                
                                                <div class="arm_confirm_box_btn_container">
                                                    <button type="button" class="arm_confirm_box_btn armok arm_deactivate_multisite arm_mutisite_subsite_deactivate_btn" data-userplan="'.$arm_multisite_subsite_plan_id.'" data-userid="'.$user_id.'" data-siteid="'.$userblog_id.'" data-msg="'.$user_multisite_common_message['arm_multisite_deactivate_msg'].'">'.$deactivate_button_label.'</button>
                                                    <button type="button" class="arm_confirm_box_btn armcancel" id="arm_multisite_cancel_deactivate_btn">Cancel</button>
                                                </div>
                                            </div></div>';
                                        $multisite_delete_btn .= '<div class="arm_multisite_btn_div"><button type="button" class="arm_deactivate_multisite">'.$deactivate_button_label.'</button>'.$cnf_deactive_content.'</div>';
                                    }
                                }
                                
                                $usermultisiteCancel= !empty($user_multisite_common_message['arm_multisite_delete_alert']) ? $user_multisite_common_message['arm_multisite_delete_alert'] : esc_html__('Are you sure you want to delete this Site?', 'ARM_MULTISUBSITE');
                                $multisite_delete_btn .= '<div class="arm_multisite_btn_div"><button type="button" class="arm_delete_multisite" data-userid="'.$user_id.'" data-siteid="'.$userblog_id.'" data-msg="'.$usermultisiteCancel.'">'.$delete_button_label.'</button></div>';

                                $user_status = ( $blog_user_details->deleted == 0 ) ? "Active" : "Deactive";

                                $arf_created_multisite ='arf_created_multisite';
                                $user_plan ='';
                                $arm_multisite_link = "<span class='".$arf_created_multisite."' style='cursor:pointer'><a href='".$current_admin_site_url."'>".$user_blog_site."</a></span>";
                                $table .="<tr class='arm_multisite_list_item' id='arm_manage_multisite_row_".$userblog_id."'>
                                    <td class='arm_current_user_plan' data-val='".$current_user_plan_name."'>".$current_user_plan_name."</td>
                                    <td class='arm_site_title_list'>".$arm_multisite_link."</td>
                                    <td class='arm_user_status'>".$user_status."</td>
                                    <td class='arm_action_status'>".$multisite_delete_btn."</td>
                                </tr>       
                                ";

                                $arm_user_blog_flag = 1;
                                $arm_user_site_blog_count ++;
                            } else {
                                $table .="<tr class='arm_multisite_list_item'>
                                    <td colspan='4' >".$arm_manage_multisite_no_record_msg."</td>
                                    </tr>";
                            }
                        }

                        $rec_cnt++;
                    }
                } else {
                    $table .= "<tr class='arm_multisite_list_item'><td colspan='4'>";
                    $table .= isset($user_multisite_common_message['arm_multisite_plan_error']) ? $user_multisite_common_message['arm_multisite_plan_error'] : esc_html__('No any subsite found', 'ARM_MULTISUBSITE');
                    $table .= "</td></tr>";
                }

                $response['body'] = $table;       
                $response['status'] = "success";

            } else {
                $table .= "<tr class='arm_multisite_list_item'><td colspan='4'>";
                $table .= isset($user_multisite_common_message['arm_multisite_plan_error']) ? $user_multisite_common_message['arm_multisite_plan_error'] : esc_html__('No any subsite found', 'ARM_MULTISUBSITE');
                $table .= "</td></tr>";
                $response['body'] = $table;
                $response['status'] = "success";
            }
            
        } else {
            $response['status'] = "fail";
            $response['message'] = "Invalid parameter";
        }

        echo json_encode($response);die;
    }

    function arm_set_plan_attribute_outside($planInputAttr, $plan_id) {

    	$user_id = get_current_user_id();
    	if($user_id) {
    		global $arm_global_settings, $is_multiple_membership_feature;
    		$user_multisite_common_message = $arm_global_settings->common_message;
    		
    		
    		$site_limit_arr = $this->get_site_limit_note($user_id, array($plan_id));
	    	
	    	$site_to_delete = abs($site_limit_arr['max_site_count'] - $site_limit_arr['created_site_count']);
	    	
	    	$message = (isset($user_multisite_common_message['arm_multisite_delete_note_msg']) && ''!=$user_multisite_common_message['arm_multisite_delete_note_msg']) ? $user_multisite_common_message['arm_multisite_delete_note_msg'] : sprintf(esc_html__("Please delete any %d subsite.", 'ARM_MULTISUBSITE'), $site_to_delete);

	    	
    		if(strpos($message, "[ARM_MULTISITE_TO_DELETE]") >= 0 || ''!=strpos($message, "[ARM_MULTISITE_TO_DELETE]")) {
    			$message = str_replace("[ARM_MULTISITE_TO_DELETE]", "%d", $message);	
    			$message = sprintf(esc_html__($message, 'ARM_MULTISUBSITE'), $site_to_delete);
    		}
    		if(strpos($message, "[ARM_MAX_MULTISITE_ALLOWED]") >= 0 || ''!=strpos($message, "[ARM_MAX_MULTISITE_ALLOWED]")) {
    			$message = str_replace("[ARM_MAX_MULTISITE_ALLOWED]", "%d", $message);	
    			$message = sprintf(esc_html__($message, 'ARM_MULTISUBSITE'), $site_limit_arr['max_site_count']);
    		}
    		if(strpos($message, "[ARM_MULTISITE_CREATED]") >= 0 || ''!=strpos($message, "[ARM_MULTISITE_CREATED]")) {
    			$message = str_replace("[ARM_MULTISITE_CREATED]", "%d", $message);	
    			$message = sprintf(esc_html__($message, 'ARM_MULTISUBSITE'), $site_limit_arr['created_site_count']);
    		}
            
            if($is_multiple_membership_feature->isMultipleMembershipFeature) {
                $planInputAttr .= " data-is_multiple_membership='true' ";
            } else {
                $planInputAttr .= " data-is_multiple_membership='false' ";
            }

	    	$planInputAttr .= " data-multisite_created_cnt='".$site_limit_arr['created_site_count']."'";
	    	$planInputAttr .= " data-max_multisite_cnt='".$site_limit_arr['max_site_count']."'";
	    	$planInputAttr .= " data-multisite_error_msg='".$message."'";
    	}
    	return $planInputAttr;
    }

    function arm_multisite_deactivate() {
        $response = array();

        $delete_button_label = isset($_REQUEST['delete_button_label']) ? $_REQUEST['delete_button_label'] : esc_html__('Delete', 'ARM_MULTISUBSITE') ;
        $activate_button_label = isset($_REQUEST['activate_button_label']) ? $_REQUEST['activate_button_label'] : esc_html__('Activate', 'ARM_MULTISUBSITE');
        $deactivate_button_label = isset($_REQUEST['deactivate_button_label']) ? $_REQUEST['deactivate_button_label'] : esc_html__('Deactivate', 'ARM_MULTISUBSITE');

        $site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : 1;
        $user_id = isset($_REQUEST['userId']) ? $_REQUEST['userId'] : 0;
        $plan_id = isset($_REQUEST['planId']) ? $_REQUEST['planId'] : 0;
        $site_action = isset($_REQUEST['site_action']) ? $_REQUEST['site_action'] : "";

        if(''!=$site_action) {
            global $arm_subscription_plans, $arm_global_settings, $is_multiple_membership_feature;
            $user_multisite_common_message = $arm_global_settings->common_message;
            $html_content = "";
            update_blog_status($site_id, 'public', 0);
            update_blog_status($site_id, 'deleted', 1);
            
            $user_plan_list = get_user_meta($user_id, 'arm_user_plan_ids', true);
            $user_suspend_plan_list = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
            $activation_cnf_content = "";

            $msg = isset($user_multisite_common_message['arm_multisite_deactivate_msg'] ) ? $user_multisite_common_message['arm_multisite_deactivate_msg'] : esc_html__('Your Site deactivated successfully.', 'ARM_MULTISUBSITE');

            if($is_multiple_membership_feature->isMultipleMembershipFeature) {
                $activation_dd_content = '<div class="arm_form_input_wrapper"><div class="arm_form_input_container_select arm_form_input_container" id="arm_form_input_container"><select class="md-visually-hidden" name="arm_activate_plan_list">';
                foreach ($user_plan_list as $key => $user_plan) {
                    if(!in_array($user_plan, $user_suspend_plan_list)) { 
                        $plan_name = $arm_subscription_plans->arm_get_subscription_plan($user_plan,'arm_subscription_plan_id, arm_subscription_plan_name');
                        $activation_dd_content .= '<option value="'.$plan_name['arm_subscription_plan_id'].'">'.$plan_name["arm_subscription_plan_name"].'</option>';
                    }
                    

                }

                $activation_dd_content .= '</select></div></div>';
                
                $arm_multisite_activation_alert = isset($user_multisite_common_message['arm_multisite_activation_alert']) ? $user_multisite_common_message['arm_multisite_activation_alert'] : esc_html__("Please select a plan to activate the site.", 'ARM_MULTISUBSITE');

                $activation_cnf_content = '<div class="arm_confirm_activation_box" id="arm_confirm_activation_box_'.$site_id.'">
                    <div class="arm_confirm_box_body">
                        <div class="arm_confirm_box_arrow"></div>
                        <div class="arm_confirm_box_text">'.$arm_multisite_activation_alert.'</div>
                        '.$activation_dd_content.'
                        <div class="arm_confirm_box_btn_container">
                            <button type="button" class="arm_confirm_box_btn armok arm_mutisite_subsite_activate_btn" data-userplan="'.$plan_id.'" data-userid="'.$user_id.'" data-siteid="'.$site_id.'" data-msg="'.$msg.'" data-is_multiple_membership="1">'.$activate_button_label.'</button>
                            <button type="button" class="arm_confirm_box_btn armcancel" id="arm_multisite_cancel_activate_btn">'.esc_html__('Cancel', 'ARM_MULTISUBSITE').'</button>
                        </div>
                    </div></div>';
                $deactivate_btn_cls = "arm_activate_multisite";
            } else {
                $deactivate_btn_cls = "arm_mutisite_subsite_activate_btn";
            }
            
            $html_content .= '<button type="button" class="'.$deactivate_btn_cls.'" data-userplan="'.$plan_id.'" data-userid="'.$user_id.'" data-siteid="'.$site_id.'" data-msg="'.$msg.'">'.$activate_button_label.'</button>'.$activation_cnf_content;

            $response = array( 'status'=>'success', 'status_label' => 'Deactive', 'message' => $msg, 'button_label'=>'Activate', 'html_content' => $html_content);  
        } else {
            $msg = "Invalid parameter supplied";
            $response = array( 'status'=>'fail', 'message' => $msg );  
        }

        echo json_encode($response);die;
    }

    function arm_multisite_activate() {
        global $is_multiple_membership_feature;
    	$response = array();
        $delete_button_label = isset($_REQUEST['delete_button_label']) ? $_REQUEST['delete_button_label'] : esc_html__('Delete', 'ARM_MULTISUBSITE') ;
        $activate_button_label = isset($_REQUEST['activate_button_label']) ? $_REQUEST['activate_button_label'] : esc_html__('Activate', 'ARM_MULTISUBSITE');
        $deactivate_button_label = isset($_REQUEST['deactivate_button_label']) ? $_REQUEST['deactivate_button_label'] : esc_html__('Deactivate', 'ARM_MULTISUBSITE');

    	$site_id = isset($_REQUEST['siteId']) ? $_REQUEST['siteId'] : 1;
    	$user_id = isset($_REQUEST['userId']) ? $_REQUEST['userId'] : 0;
    	$plan_id = isset($_REQUEST['planId']) ? $_REQUEST['planId'] : 0;
        $changed_plan_id = isset($_REQUEST['change_plan_id']) ? $_REQUEST['change_plan_id'] : 0;
        
    	if($site_id != 1 && $user_id != 0 && isset($_REQUEST['action'])) {
    		$user_plan_list = get_user_meta($user_id, 'arm_user_plan_ids', true);
            
            $max_site_msg = "";
            if($is_multiple_membership_feature->isMultipleMembershipFeature) {
                
                if( !empty($user_plan_list) && in_array($changed_plan_id, $user_plan_list) ) {
                    global $arm_subscription_plans, $arm_global_settings;
                    $user_multisite_common_message = $arm_global_settings->common_message;
                    $changed_plan_name = "";
                    $subsite_list = get_user_meta($user_id, 'arm_multisite_id', true);
                    foreach ($subsite_list as $key => $site) {
                        if($site_id == $site['site_id']) {
                            $subsite_list[$key]["plan_id"] = $changed_plan_id;
                            $user_plan_details = $arm_subscription_plans->arm_get_subscription_plan($changed_plan_id,'arm_subscription_plan_name');

                            $changed_plan_name = $user_plan_details['arm_subscription_plan_name'];
                        }
                    }                    
                } else {
                    $max_site_msg = esc_html__('No plan available.', 'ARM_MULTISUBSITE');
                } 
            } else {
                
                if( !empty($user_plan_list) ) {
                    global $arm_subscription_plans, $arm_global_settings;
                    $user_multisite_common_message = $arm_global_settings->common_message;
                    $changed_plan_name = "";
                    $subsite_list = get_user_meta($user_id, 'arm_multisite_id', true);
                    $changed_plan_id = isset($user_plan_list[0]) ? $user_plan_list[0] : 0;
                    
                    foreach ($subsite_list as $key => $site) {
                        if($site_id == $site['site_id']) {
                            $subsite_list[$key]["plan_id"] = $changed_plan_id;
                            $user_plan_details = $arm_subscription_plans->arm_get_subscription_plan($changed_plan_id,'arm_subscription_plan_name');

                            $changed_plan_name = $user_plan_details['arm_subscription_plan_name'];
                        }
                    }
                } else {
                    $max_site_msg = esc_html__('No plan available.', 'ARM_MULTISUBSITE');
                }
            }

            $max_site_arr = $this->get_site_limit_note($user_id);
            $activate_multisite = 0;
            $max_site_count = 0;
            $max_site_acitve = 0;
            $user_plan_details = $arm_subscription_plans->arm_get_subscription_plan($changed_plan_id,'arm_subscription_plan_id, arm_subscription_plan_name, arm_subscription_plan_options');
            
            $multisite_member_plan_option = get_user_meta($user_id, 'arm_multisite_member_plan_options', true);

            if($is_multiple_membership_feature->isMultipleMembershipFeature) { 
                foreach ($max_site_arr['active_site_ids'] as $key => $active_plan_ids) {
                    if($active_plan_ids['plan_id']==$changed_plan_id){
                        $max_site_acitve++;
                    }
                }
                $max_site_count = (!empty($multisite_member_plan_option[$changed_plan_id]['arm_multisite_create'])) ? $multisite_member_plan_option[$changed_plan_id]['arm_multisite_create'] : 0;

                $enable_multisite = (!empty($multisite_member_plan_option[$changed_plan_id]['enable_disable_planwise_multisite'])) ? !empty($multisite_member_plan_option[$changed_plan_id]['enable_disable_planwise_multisite']) : 0;
                
                if( $enable_multisite != 0 && $max_site_acitve < $max_site_count) {
                    $activate_multisite = 1;   
                } else if($enable_multisite == 0 && $max_site_acitve < $max_site_count) {
                    $max_site_msg = esc_html__("Multisite creation is disabled for the current plan.", 'ARM_MULTISUBSITE');
                } else {
                    $max_site_msg = sprintf(esc_html__("You can not activate more than %d site of plan %s.", 'ARM_MULTISUBSITE'), $max_site_count, $user_plan_details['arm_subscription_plan_name']);
                }
        
            } else {
                
                $enable_multisite = (!empty($multisite_member_plan_option[$changed_plan_id]['enable_disable_planwise_multisite'])) ? !empty($multisite_member_plan_option[$changed_plan_id]['enable_disable_planwise_multisite']) : 0;

                if( $enable_multisite != 0 && count($max_site_arr['active_site_ids']) < $max_site_arr['max_site_count'] ) { 
                    $activate_multisite = 1;
                } else {
                    $max_site_msg = sprintf(esc_html__("You can not activate more than %d site.", 'ARM_MULTISUBSITE'), $max_site_arr['max_site_count']);    
                }
                
            } 
            
            if( 1 == $activate_multisite && '' == $max_site_msg) 
            {
                update_user_meta($user_id, 'arm_multisite_id', $subsite_list);    

                $msg = isset($user_multisite_common_message['arm_multisite_activate_msg']) ? $user_multisite_common_message['arm_multisite_activate_msg'] : esc_html__('Your Site activated successfully.', 'ARM_MULTISUBSITE');
                update_blog_status($site_id, 'public', 1);
                update_blog_status($site_id, 'deleted', 0);
                
                $arm_multisite_deactivation_alert = isset($user_multisite_common_message['arm_multisite_deactivation_alert']) ? $user_multisite_common_message['arm_multisite_deactivation_alert'] : esc_html__('Are you sure you want to deactivate this Site?', 'ARM_MULTISUBSITE');

                $html_content = '<button type="button" class="arm_deactivate_multisite" >'.$deactivate_button_label.'</button>
                <div class="arm_confirm_activation_box" id="arm_confirm_activation_box_">
                <div class="arm_confirm_box_body">
                    <div class="arm_confirm_box_arrow"></div>
                    <div class="arm_confirm_box_text">'.$arm_multisite_deactivation_alert.'</div>
                    
                    <div class="arm_confirm_box_btn_container">
                        <button type="button" class="arm_confirm_box_btn armok arm_deactivate_multisite arm_mutisite_subsite_deactivate_btn" data-userplan="'.$changed_plan_id.'" data-userid="'.$user_id.'" data-siteid="'.$site_id.'" data-msg="'.$msg.'">'.$deactivate_button_label.'</button>
                        <button type="button" class="arm_confirm_box_btn armcancel" id="arm_multisite_cancel_deactivate_btn">'.esc_html__('Cancel', 'ARM_MULTISUBSITE').'</button>
                    </div>
                </div></div>';

                $response = array( 'status'=>'success', 'is_multiple_membership'=>$is_multiple_membership_feature->isMultipleMembershipFeature, 'status_label' => 'Active', 'changed_plan_name' => $changed_plan_name, 'message' => $msg, 'html_content' => $html_content );  
            } 
            else 
            {
                $response = array( 'status'=>'warning', 'message' => $max_site_msg);
            }
		
		} else {
			$response = array( 'status'=>'error', 'message' => esc_html__('Invalid parameter.', 'ARM_MULTISUBSITE'));
		}

		echo json_encode($response);
        die;
            
    }

    function get_site_limit_note($user_id, $user_plan_list=array()){
    	global $arm_subscription_plans;
    	$result = array('max_site_count' => 0, 'created_site_count' => 0);
    	$max_site_count = 0;
        $multisite_member_plan_option = get_user_meta($user_id, 'arm_multisite_member_plan_options', true);
        if(!empty($user_plan_list)) {    		
            foreach ($user_plan_list as $user_plan_list_id) {
	    		$user_membership_plan_details = $arm_subscription_plans->arm_get_subscription_plan($user_plan_list_id,'arm_subscription_plan_id, arm_subscription_plan_name,arm_subscription_plan_options');

	    		if(isset($user_membership_plan_details['arm_subscription_plan_options']['arm_multisite_create']) && ''!=$user_membership_plan_details['arm_subscription_plan_options']['arm_multisite_create']) {
	    			$max_site_count += $user_membership_plan_details['arm_subscription_plan_options']['arm_multisite_create'];		
	    		}
	    	}
    	} else {
            if(!empty($multisite_member_plan_option)) {
                foreach ($multisite_member_plan_option as $key => $user_plan_list_id) {
                    if( 1 == $user_plan_list_id['enable_disable_planwise_multisite'] && isset($user_plan_list_id['arm_multisite_create']) && ''!=$user_plan_list_id['arm_multisite_create'] ) {
                        
                        $max_site_count += $user_plan_list_id['arm_multisite_create'];      
                    }

                }
            }
        }
    	
		$arm_multisite_site_id = get_user_meta($user_id,'arm_multisite_id' , TRUE );
		$arm_multisite_site_ids = !empty($arm_multisite_site_id) ? maybe_unserialize($arm_multisite_site_id) : array();
		
        $created_site_count = 0;
		$active_site_arr = array();
		$deactive_site_arr = array();
		if(!empty($arm_multisite_site_ids)) {
			$active_site_count = 0;
			$deactive_site_count = 0;
			foreach ($arm_multisite_site_ids as $arm_multisite_site_id_key => $arm_multisite_site_id_value) {
                $site_arr = array();
				if(!empty($arm_multisite_site_id_value['site_id']) && (!empty($arm_multisite_site_id_value['plan_id'])) )
				{
					$created_site_count ++;
				}
				$blog_detail = get_blog_details( $arm_multisite_site_id_value['site_id'] );
                $site_arr['site_id'] = $arm_multisite_site_id_value['site_id'];
                $site_arr['plan_id'] = $arm_multisite_site_id_value['plan_id'];
				if(1 == $blog_detail->public) {    
                    array_push($active_site_arr, $site_arr);
				}
				if(1 == $blog_detail->deleted) {
                    array_push($deactive_site_arr, $site_arr);
				}
			}	
		}
		
		$result['created_site_count'] = $created_site_count;
		$result['max_site_count'] = $max_site_count;
		$result['active_site_ids'] = $active_site_arr;
		$result['deactive_site_ids'] = $deactive_site_arr;
		
		return $result;
    }

    function arm_multisite_creation($atts) {
        global $arm_member_forms,$arm_subscription_plans, $arm_global_settings, $is_multiple_membership_feature,$ARMember;
      
	    $user_membership_plan = $arm_global_settings->global_settings;
	    $user_multisite_common_message = $arm_global_settings->common_message;

	    if ( is_user_logged_in() ) 
	    {
	    	$user_id = get_current_user_id();
	    	$user_plan_list = get_user_meta($user_id, 'arm_user_plan_ids', true);
	    	$all_membership_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name,arm_subscription_plan_options');
	    	$table = '';
	    	
	    	$table .='<div class="arm_multisite_main_container_form " >';
	    	if(!empty($user_plan_list))
	    	{
	    		$multisite_member_plan_option = get_user_meta($user_id, 'arm_multisite_member_plan_options', true);
	    		$arm_subsite_display_form = 0;
	    		foreach ($user_plan_list as $user_plan_list_id) 
	    		{
	    			$user_plan_list_id = $user_plan_list_id;
	    			$current_user_plan_id = $user_plan_list_id;
			    	$enable_disable_planwise_multisite = !empty($multisite_member_plan_option[$current_user_plan_id]['enable_disable_planwise_multisite']) ? $multisite_member_plan_option[$current_user_plan_id]['enable_disable_planwise_multisite'] : 0;

                    $multisite_member_plan_option = get_user_meta($user_id, 'arm_multisite_member_plan_options', true);

			    	if(!empty($user_plan_list_id) && $enable_disable_planwise_multisite == 1)
			    	{
			    		$arm_subsite_display_form = 1;
			    	}

	    		}
	    		
	    		$max_site_note_arr = $this->get_site_limit_note($user_id);
				$max_site_note = sprintf(esc_html__("You have created %d out of %d site(s).", 'ARM_MULTISUBSITE'), $max_site_note_arr['created_site_count'], $max_site_note_arr['max_site_count']);
	    		

	    		if (!empty($arm_subsite_display_form))
		        {
			    	wp_enqueue_script('arm_angular_with_material_js');
		            wp_enqueue_script('arm_form_angular_js');
		            wp_enqueue_script('arm_multisite_form_angular_js');
		            wp_enqueue_style('arm_angular_material_css');

		            $site_address_note = "";
	            	$site_address_note .= "<p class='arm_form_field_label_text'><strong>Note:</strong> ";
		            if(is_subdomain_install()) {
		            	$site_url = get_bloginfo('siteurl');
		            	$explode_url = explode("//", $site_url);
		            	$subsite_url = trim($explode_url[0])."//{site_name}.".trim($explode_url[1]);

		            	$site_address_note .= sprintf(esc_html__("Your address will be %s{$subsite_url}/%s.%s", 'ARM_MULTISUBSITE'), '<b>', '</b>', '<br>');
		            } else {
		            	$subsite_url = home_url()."/{site_name}/";
		            	
		            	$site_address_note .= sprintf(esc_html__("Your address will be %s{$subsite_url}%s.%s", 'ARM_MULTISUBSITE'), '<b>', '</b>', '<br>');
		            }
		            $site_address_note .= sprintf(esc_html__("Your Site Name must be at least 4 characters (latters/numbers only). Once your site is created the site name cannot be changed.%s", 'ARM_MULTISUBSITE'), '</p>');

		            $atts = shortcode_atts(array(
		                'form_id' =>'',
		                'arm_multisite_form_title' => esc_html__('Add New Subsite', 'ARM_MULTISUBSITE'),
		                'site_name' =>esc_html__('Site Name', 'ARM_MULTISUBSITE'),
		                'site_title' => esc_html__('Site Title', 'ARM_MULTISUBSITE'),
		                'site_address_note' => $site_address_note, 
		                'enable_note_belt' => 'false',
		                'note_belt_color' => "#eff1f1",
		                'note_belt_font_color' => "#444444",
                        'site_limit_message' => esc_html__("You have reached to limit of site(s) allowed to create.", 'ARM_MULTISUBSITE'),
                        'submit_button_text' => esc_html__("Submit", 'ARM_MULTISUBSITE'),
		            ),$atts  );
		            
		            $form_id= !empty($atts['form_id']) ? $atts['form_id'] : '101';
		            $arm_multisite_form_title = isset($atts['arm_multisite_form_title']) ? $atts['arm_multisite_form_title'] : '';
		            $form_style['button_position'] = (!empty($form_style['button_position'])) ? $form_style['button_position'] : 'left';
		            
		            $default_form_id = (!empty($form_id)) ? $form_id : $arm_member_forms->arm_get_default_form_id('registration');
		            $form = new ARM_Form('id', $default_form_id);

		            if ($form->exists() && !empty($form->fields)) 
			        {
			        	$form_id = $form->ID;
			        	$form_settings = $form->settings;
			        	$ref_template = $form->form_detail['arm_ref_template'];
			        	$form_style = $form_settings['style'];
			        	$fieldPosition = 'left';
			        	$errPos = 'right';

			        	$form_style['button_position'] = (!empty($form_style['button_position'])) ? $form_style['button_position'] : 'left';
			        	$form_css = $arm_member_forms->arm_ajax_generate_form_styles($form_id, $form_settings, array(), $ref_template);
			        	$form_style_class = 'arm_shortcode_form arm_form_' . $form_id;
			        	$form_style_class .= ' arm_form_layout_' . $form_style['form_layout'];
			        	$form_style_class .= ($form_style['label_hide'] == '1') ? ' armf_label_placeholder' : '';
			        	$form_style_class .= ' armf_alignment_' . $form_style['label_align'];
			        	$form_style_class .= ' armf_layout_' . $form_style['label_position'];
			        	$form_style_class .= ' armf_button_position_' . $form_style['button_position'];
			        	$form_style_class .= ($form_style['rtl'] == '1') ? ' arm_form_rtl' : ' arm_form_ltr';
			        }

			        $form_title_position = (!empty($form_style['form_title_position'])) ? $form_style['form_title_position'] : 'left';
			        $buttonStyle = (isset($form_settings['style']['button_style']) && !empty($form_settings['style']['button_style'])) ? $form_settings['style']['button_style'] : 'flat';
			            $btn_style_class = ' arm_btn_style_' . $buttonStyle;

			        $setupRandomID = $form_id . '_' . arm_generate_random_code();
			        
			        $form_attr = ' data-ng-controller="ARMCtrl" data-ng-cloak="" data-ng-id="' . $form_id . '" data-ng-submit="armMultisiteFormSubmit(arm_form.$valid, \'arm_multisite_form_' . $setupRandomID . '\', $event);" onsubmit="return false;"';
			        $is_form_class_rtl = '';
                    if (is_rtl()) {
                        $is_form_class_rtl = 'is_form_class_rtl';
                    }
			        $captcha_code = arm_generate_captcha_code();
                    $ARMember->arm_session_start();
			        if (!isset($_SESSION['ARM_FILTER_INPUT'])) {
			            $_SESSION['ARM_FILTER_INPUT'] = array();
			        }
			        if (isset($_SESSION['ARM_FILTER_INPUT'][$setupRandomID])) {
			            unset($_SESSION['ARM_FILTER_INPUT'][$setupRandomID]);
			        }
			        $_SESSION['ARM_FILTER_INPUT'][$setupRandomID] = $captcha_code;
			        $_SESSION['ARM_VALIDATE_SCRIPT'] = true;
			        
			        $form_attr .= ' data-submission-key="' . $captcha_code . '" ';

			        $table .='<style type="text/css" id="arm_update_card_form_style_' . $form_id . '">' . $form_css['arm_css'] . '</style>';
			        $table .='<div class="arm_multisite_form arm_member_form_container arm_update_card_form_container arm_form_' . $form_id . '" >';
			        $table .= '<div class="arm_setup_messages arm_form_message_container"></div>';
			        
			        if($atts['enable_note_belt'] == 'true') {
			        	$table .= '<div class="arm_site_limit_message arm_site_message_container arm_form_field_label_text" style="background-color: '.$atts['note_belt_color'].'; color:'.$atts['note_belt_font_color'].'">'.$max_site_note.'</div>';	
			        }
			        
			        $table .= '<div class="armclear"></div>';
			        $table .='<form method="post" name="arm_form" id="arm_multisite_form_' . $setupRandomID . '" class="arm_multisite_form_id arm_setup_form_' . $form_id . ' arm_multisite_setup_form  ' . $is_form_class_rtl . '" enctype="multipart/form-data" data-random-id="' . $setupRandomID . '"  novalidate ' . $form_attr . '>';
			        
			        $table .='<div class="arm_module_gateway_fields arm_module_gateway_fields arm_member_form_container">';
			        $table .='<div class="' . $form_style_class . '" data-ng-cloak="">';
			        $table.='<div class="arm_update_card_form_heading_container armalign' . $form_title_position . '">';
			        $table .='<span class="arm_form_field_label_wrapper_text">'.esc_html__($arm_multisite_form_title, 'ARM_MULTISUBSITE').'</span>';
			        $table .='</div>';

			        $type ='multisite';
			        $fieldtable = '';
			        $arm_multisubsite_fields_array = array();
			        if ($is_multiple_membership_feature->isMultipleMembershipFeature) {
			        	$arm_multisubsite_fields_array = array('site_name','site_title','subsite_plan');
			        }
			        else
			        {
			        	$arm_multisubsite_fields_array = array('site_name','site_title');
			        }

			        foreach ($arm_multisubsite_fields_array as $key) {
			            $fieldLabel = $fieldClass = $fieldAttr = $validation = $fieldDesc = '';
			            switch ($key) {
			                case 'site_name':
			                    $fieldLabel = $atts['site_name'];
			                    $fieldAttr = 'name="' . $type . '[' . $key . ']" data-ng-model="arm_form.site_name' . $type . '" ';
			                    $fieldAttr .= ' data-ng-required="isarmMultisiteFormField(\'' . $type . '\')" data-msg-required="' . esc_html__('This field can not be left blank', 'ARM_MULTISUBSITE') . '"';  

			                    $fieldAttr .= 'onfocusout="validate_field_len(this);"';
			                    $fieldAttr .= 'onkeydown="return validate_field_value(event,this);"';
			                    $fieldAttr .= 'data-arm_min_len_msg="'.esc_html__('This field required minimum 4 characters.', 'ARM_MULTISUBSITE').'"';
			                    
			                    $fieldClass = ' site_name';
			                    $validation .= '<div data-ng-cloak data-ng-messages="arm_form[\'' . $type . '[' . $key . ']\'].$error" data-ng-show="arm_form[\'' . $type . '[' . $key . ']\'].$touched" class="arm_error_msg_box ng-cloak">';
			                    $ey_error =  esc_html__('This field should not be blank.', 'ARM_MULTISUBSITE');
			                    $validation .= '<div data-ng-message="required" class="arm_error_msg"><div class="arm_error_box_arrow"></div>' . $ey_error . '</div>';
			                    $validation .= '</div>';
			                    $current_site_url = site_url();
			                    break;
			                case 'site_title':
			                    $fieldLabel = $atts['site_title'];
			                    $fieldAttr = 'name ="' . $type . '[' . $key . ']" data-ng-model="arm_form.site_title' . $type . '" ';
			                    $fieldAttr .=' data-ng-required="isarmMultisiteFormField(\'' . $type . '\')" required data-msg-required="' . esc_html__('This field can not be left blank', 'ARM_MULTISUBSITE') . '"';
			                    $fieldClass = ' site_title';
			                    $validation .= '<div data-ng-cloak data-ng-messages="arm_form[\'' . $type . '[' . $key . ']\'].$error" data-ng-show="arm_form[\'' . $type . '[' . $key . ']\'].$touched" class="arm_error_msg_box ng-cloak">';
			                    $ey_error =  esc_html__('This field can not be left blank.', 'ARM_MULTISUBSITE');
			                    $validation .= '<div data-ng-message="required" class="arm_error_msg"><div class="arm_error_box_arrow"></div>' . $ey_error . '</div>';
			                    $validation .= '</div>';
			                    break;
			                case 'subsite_plan':
			                	$fieldLabel = "Membership Plan";
			                    $fieldAttr = 'name ="' . $type . '[' . $key . ']" data-ng-model="arm_form.subsite_plan' . $type . '" ';
			                    $fieldAttr .=' data-ng-required="isarmMultisiteFormField(\'' . $type . '\')" required data-msg-required="' . esc_html__('This field can not be left blank', 'ARM_MULTISUBSITE') . '"';
			                    $fieldClass = ' subsite_plan';
			                    $validation .= '<div data-ng-cloak data-ng-messages="arm_form[\'' . $type . '[' . $key . ']\'].$error" data-ng-show="arm_form[\'' . $type . '[' . $key . ']\'].$touched" class="arm_error_msg_box ng-cloak">';
			                    $ey_error =  esc_html__('This field can not be left blank.', 'ARM_MULTISUBSITE');
			                    $validation .= '<div data-ng-message="required" class="arm_error_msg"><div class="arm_error_box_arrow"></div>' . $ey_error . '</div>';
			                    $validation .= '</div>';
			                	break;

			                default:
			                    break;
			            }

			            $fieldtable .="<div class='arm_cc_field_wrapper arm_form_field_container arm_form_field_container_text arm_subsite_form_field_container'>";
			            $fieldtable .="<div class='arm_form_label_wrapper arm_form_field_label_wrapper arm_form_member_field_text'>";
			            $fieldtable .="<div class='arm_member_form_field_label'>";
			            $fieldtable .="<div class='arm_form_field_label_text' >".$fieldLabel."</div>";
			            $fieldtable .="</div>";
			            $fieldtable .="</div>";
			            if($is_multiple_membership_feature->isMultipleMembershipFeature && $key=='subsite_plan')
			            {
                            $user_suspend_plan_list = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
			    $user_suspend_plan_list = !empty($user_suspend_plan_list) ? $user_suspend_plan_list : array();
			            	$fieldtable .="<div class='arm_label_input_separator'></div>";
				            $fieldtable .="<div class='arm_form_input_wrapper' >";
				            $fieldtable .="<div class='arm_form_input_container arm_form_input_container_".$key."' >";
				            $fieldtable .="<md-input-container class='md-block' flex-gt-sm=''>";
				            $fieldtable .="<label class='arm_material_label' for='arm_".$key."'>".$fieldLabel."</label>";
			            	$fieldtable .="<md-select flex-gt-sm='' ".$fieldAttr." id='arm_".$key."'>";
			            	foreach ($user_plan_list as $user_plan_list_id) 
    						{
                                if(!in_array($user_plan_list_id, $user_suspend_plan_list)) {
                                    $selected_val = ($user_plan_list_id == $current_user_plan_id) ? 'selected' : '';
                                    $current_user_plan_name = $all_membership_plans[$user_plan_list_id]['arm_subscription_plan_name'];
                                    $fieldtable .="<md-option value='".$user_plan_list_id."' class='armMDOption armSelectOption'  ".$selected_val." >" . $current_user_plan_name . "</md-option>";    
                                }
    							
			            	}
			                $fieldtable .="</md-select>";
			                $fieldtable .= $validation;
			                $fieldtable .= "</md-input-container>";
			                $fieldtable .= "<input type='hidden' data-ng-model='arm_form.subsite_plan". $type . "' name='" . $type . "[arm_user_plan_list_id]' value='{{ arm_form.subsite_plan". $type . " }}' id='arm_subsite_plan' >";
			                $fieldtable .="</div>";
				            $fieldtable .="</div>";
			            }
			            else
			            {
				            $fieldtable .="<div class='arm_label_input_separator'></div>";
				            $fieldtable .="<div class='arm_form_input_wrapper' >";
				            $fieldtable .="<div class='arm_form_input_container arm_form_input_container_".$key."' >";
				            $fieldtable .="<md-input-container class='md-block' flex-gt-sm=''>
				                         <label class='arm_material_label' for='arm_".$key."'>".$fieldLabel."</label>
				                            <input type='text' class='field_".$type." ".$fieldClass."' 
				                             value=''  id='arm_".$key."' ".$fieldAttr.">
				                            ".$validation."
				                        </md-input-container>";
				            if(!$is_multiple_membership_feature->isMultipleMembershipFeature)
			            	{
				            	$fieldtable .="<input type='hidden' class='arm_user_plan_list_id' 
				                             value='".$user_plan_list_id."' name ='" . $type . "[arm_user_plan_list_id]' id='arm_subsite_plan' >";
				            }
				            $fieldtable .="</div>";
				            
				            if('site_name' == $key){
				            	$fieldtable .= "<span>".$atts['site_address_note']."</span>";	
				            }
				            
				            $fieldtable .="</div>";
				            $fieldtable .="</div>";
				        }

			        }

			        $table .= '<div class="arm_form_inner_container arm_msg_pos_' . $errPos . '">';
			        $table .= '<div class="arm_cc_fields_container arm_' . $type . '_fields arm_form_wrapper_container arm_field_position_' . $fieldPosition . '" >';
			        $table .= '<span class="payment-errors"></span>';
			        $table .= $fieldtable;
			        $table .= '</div>';
			        $table .= '<div class="armclear"></div>';
			        $table .= '</div>';

			        

			        $table .='<div class="armclear"></div>';
			        $table .='<div class="arm_form_field_container arm_form_field_container_submit">';
			        $table .='<div class="arm_label_input_separator"></div>';
			        $table .='<div class="arm_form_label_wrapper arm_form_field_label_wrapper arm_form_member_field_submit"></div>';
			        $table .='<div class="arm_form_input_wrapper">';
			        $table .='<div class="arm_form_input_container_submit arm_form_input_container" id="arm_multisite_form' . $form_id . '">';
			        $ngClick = 'ng-click="armSubmitBtnClick($event)"';
			        if (current_user_can('administrator')) {
			            $ngClick = 'onclick="return false;"';
			        }
			        $table .='<md-button type="submit" name="arm_site_creation" class="arm_form_field_submit_button arm_form_field_container_button arm_form_input_box arm_material_input ' . $btn_style_class . '"  ' . $ngClick . ' id="arm_site_creation_submit"><span class="arm_spinner">' . file_get_contents(MEMBERSHIP_IMAGES_DIR . "/loader.svg") . '</span>'.$atts["submit_button_text"].'</md-button>';

			        $table .='<input type="hidden" data-ng-model="arm_form.arm_site_type" name="arm_site_type" value="' . $type . '" />';
			        $table .= '<input type="hidden" name="arm_site_limit_msg" id="arm_site_limit_msg" value="'.$atts['site_limit_message'].'" />';
			        $table .='</div>';
			        $table .='</div>';
			        $table .='<div class="armclear" data-ng-init="armMultisiteForm(\'' . $type . '\');"></div>';
			        $table .='</div>';
			        $table .='</div>';
			        $table .='</form>';
			        $table .='</div>';
			        $table .='</div>';
			    }
	    	}
	    	$table .= '</div>';
	        return $table;

	    }
	}

	function arm_multisite_view($atts)
	{
		global $arm_member_forms,$arm_subscription_plans, $arm_global_settings, $is_multiple_membership_feature;
      
	    $user_membership_plan = $arm_global_settings->global_settings;
	    $user_multisite_common_message = $arm_global_settings->common_message;
	    
        if(is_multisite()) {
            if ( is_user_logged_in() ) 
            {

                $user_id = get_current_user_id();
                $user_plan_list = get_user_meta($user_id, 'arm_user_plan_ids', true);
                $user_multisite_list = get_user_meta($user_id, 'arm_multisite_id', true);
                $all_membership_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name,arm_subscription_plan_options');
                $current_user_plan_id = "";
                $current_user_plan_name = "";
                $enable_disable_planwise_multisite = "";
                wp_enqueue_style('arm_form_style_css');


                $args = shortcode_atts(array(
                    'form_title' => esc_html__('', 'ARM_MULTISUBSITE'),
                    'current_page' => 1,
                    'records_per_page' => 10,
                    'no_record_message' => isset($user_multisite_common_message['arm_multisite_plan_error']) ? $user_multisite_common_message['arm_multisite_plan_error'] : esc_html__('No any subsite found', 'ARM_MULTISUBSITE'),
                    'enable_note_belt' => 'false',
                    'note_belt_color' => "#eff1f1",
                    'note_belt_font_color' => "#444444",
                    'membership_plan_label' => esc_html__("Membership Plan", 'ARM_MULTISUBSITE'),
                    'site_label' => esc_html__('Site', 'ARM_MULTISUBSITE'),
                    'status_label' => esc_html__('Status', 'ARM_MULTISUBSITE'),
                    'action_label' => esc_html__('Action', 'ARM_MULTISUBSITE'),
                    'delete_button_label' => esc_html__('Delete', 'ARM_MULTISUBSITE'),
                    'activate_button_label' => esc_html__('Activate', 'ARM_MULTISUBSITE'),
                    'deactivate_button_label' => esc_html__('Deactivate', 'ARM_MULTISUBSITE'),
                ),$atts  );

                extract($args);

                $max_site_note_arr = $this->get_site_limit_note($user_id);
                $max_site_note = sprintf(esc_html__("You have created %d out of %d site(s).", 'ARM_MULTISUBSITE'), $max_site_note_arr['created_site_count'], $max_site_note_arr['max_site_count']);


                $table = "";
                $table .="<div class='arm_multisite_container'>";
                $table .="
                        <div class='arm_setup_messages arm_form_message_container'></div>";
                    
                    if($args['enable_note_belt']=='true') {
                        $table .= "<div class='arm_site_limit_message arm_site_message_container arm_form_field_label_text' style='background-color:".$args['note_belt_color']."; color: ".$args['note_belt_font_color'].";'>".$max_site_note."</div>";
                    }

                $table .="<div class='arm_multisite_loader_container'><div class='arm_loading_grid'><img src='" .MEMBERSHIP_IMAGES_URL . "/arm_loader.gif' alt='Loading..'></div></div>
                        <div class='arm_multisite_heading_main'>
                            ".$form_title."
                        </div>
                        <div class='armclear'></div>
                        <div class='arm_multisite_wrapper'>
                            <div class='arm_multisite_content '>
                                <input type='hidden' name='arm_delete_button_label' id='arm_delete_button_label' value='".$args['delete_button_label']."' />
                                <input type='hidden' name='arm_activate_button_label' id='arm_activate_button_label' value='".$args['activate_button_label']."' />
                                <input type='hidden' name='arm_deactivate_button_label' id='arm_deactivate_button_label' value='".$args['deactivate_button_label']."' />
                                <table class='arm_add_new_multisite arm_multisite_list_table arm_front_grid' cellspacing='0' cellpadding='0' border='0'>
                                    <tr class='arm_multisite_list_header'>
                                        <th>".$args['membership_plan_label']."</th>
                                        <th>".$args['site_label']."</th>
                                        <th>".$args['status_label']."</th>
                                        <th>".$args['action_label']."</th>
                                    </tr>";
                $historyPaging = "";
                if(!empty($user_multisite_list))
                {
                    $arm_user_site_blog_count = 0;
                    $totalRecord = count($user_multisite_list);
                    $current_page = $args['current_page'];
                    $perPage = $records_per_page;
                    
                    $historyPaging = $arm_global_settings->arm_get_paging_links($current_page, $totalRecord, $perPage, 'subsite_listing');
                    $multisite_member_plan_option = get_user_meta($user_id, 'arm_multisite_member_plan_options', true);
                    $arm_subsite_display_form = "";
                    foreach ($user_multisite_list as $user_plan_list_id) 
                    {

                        $current_user_site_id = $user_plan_list_id['site_id'];
                        $current_user_plan_id = $user_plan_list_id['plan_id'];

                        if(isset($multisite_member_plan_option[$current_user_plan_id]['enable_disable_planwise_multisite']))
                        {
                            $enable_disable_planwise_multisite = !empty($multisite_member_plan_option[$current_user_plan_id]['enable_disable_planwise_multisite']) ? $multisite_member_plan_option[$current_user_plan_id]['enable_disable_planwise_multisite'] : 0;
                        }
                        else 
                        {
                            $multisite_member_plan_option_old = get_user_meta($user_id, 'arm_multisite_member_plan_options_old', true);
                            if(isset($multisite_member_plan_option_old[$current_user_plan_id]['enable_disable_planwise_multisite']))
                            {
                                $enable_disable_planwise_multisite = !empty($multisite_member_plan_option_old[$current_user_plan_id]['enable_disable_planwise_multisite']) ? $multisite_member_plan_option_old[$current_user_plan_id]['enable_disable_planwise_multisite'] : 0;
                            }
                    
                        }
                        

                        if(!empty($user_plan_list_id) && $enable_disable_planwise_multisite == 1)
                        {
                            $arm_subsite_display_form = 1;
                        }

                    }
                    
                    if (!empty($arm_subsite_display_form))
                    {
                        
                        $usermultisiteCancel= isset($user_multisite_common_message['arm_multisite_delete_alert']) ? $user_multisite_common_message['arm_multisite_delete_alert'] : esc_html__('Are you sure you want to delete this Site?', 'ARM_MULTISUBSITE');

                        $multisiteActiveConfirm = isset($user_multisite_common_message['arm_multisite_activation_alert']) ? $user_multisite_common_message['arm_multisite_activation_alert'] : esc_html__('Are you sure you want to activate this Site?', 'ARM_MULTISUBSITE');
                        $arm_multisite_deactivation_alert = isset($user_multisite_common_message['arm_multisite_deactivation_alert']) ? $user_multisite_common_message['arm_multisite_deactivation_alert'] : esc_html__('Are you sure you want to deactivate this Site?', 'ARM_MULTISUBSITE');
                        $arm_multisite_deactivate_msg = isset($user_multisite_common_message['arm_multisite_deactivate_msg'] ) ? $user_multisite_common_message['arm_multisite_deactivate_msg'] : esc_html__('Your Site deactivated successfully.', 'ARM_MULTISUBSITE');

                        
                        $arm_multisite_subsite_ids = get_user_meta($user_id,'arm_multisite_id' , TRUE );
                        $arm_multisite_link = '';
                        $arm_user_blog_flag = '';
                        if(!empty($arm_multisite_subsite_ids))
                        {    
                            $site_cnt=0;
                            krsort( $arm_multisite_subsite_ids );
                            foreach ($arm_multisite_subsite_ids as $arm_multisite_subsite_id_value) {
                                if($perPage>$site_cnt) {
                                    $arm_multisite_subsite_id = !empty($arm_multisite_subsite_id_value['site_id']) ? $arm_multisite_subsite_id_value['site_id'] : '';
                                    $arm_multisite_subsite_plan_id = !empty($arm_multisite_subsite_id_value['plan_id']) ? $arm_multisite_subsite_id_value['plan_id'] : '';

                                    if(!empty($arm_multisite_subsite_id) && !empty($arm_multisite_subsite_plan_id))
                                    {
                                        
                                        $current_user_plan_name = !empty($all_membership_plans[$arm_multisite_subsite_plan_id]['arm_subscription_plan_name']) ? $all_membership_plans[$arm_multisite_subsite_plan_id]['arm_subscription_plan_name'] : '';
                                        $blog_user_details = get_blog_details($arm_multisite_subsite_id);
                                        $userblog_id = $blog_user_details->blog_id;
                                        $user_blog_site = $blog_user_details->blogname;
                                        $current_admin_site_url = get_admin_url($userblog_id);
                                        $user_plan_list = get_user_meta($user_id, 'arm_user_plan_ids', true);
                                        $multisite_delete_btn = "";
                                        if($blog_user_details->deleted == 1) {
                                            global $arm_subscription_plans;
                                            
                                            $user_suspend_plan_list = get_user_meta($user_id, 'arm_user_suspended_plan_ids', true);
					                          $user_suspend_plan_list = !empty($user_suspend_plan_list) ? $user_suspend_plan_list : array();

                                            $activation_cnf_content = '';
                                            $activate_btn_cls = '';
                                            if($is_multiple_membership_feature->isMultipleMembershipFeature) {
                                                $activation_dd_content = '<div class="arm_form_input_wrapper"><div class="arm_form_input_container_select arm_form_input_container" id="arm_form_input_container"><select class="md-visually-hidden" name="arm_activate_plan_list">';
                                                if(!empty($user_plan_list)) {
                                                    foreach ($user_plan_list as $key => $user_plan) {
                                                        if(!in_array($user_plan, $user_suspend_plan_list)) { 
                                                            $plan_name = $arm_subscription_plans->arm_get_subscription_plan($user_plan,'arm_subscription_plan_id, arm_subscription_plan_name');
                                                            $activation_dd_content .= '<option value="'.$plan_name['arm_subscription_plan_id'].'">'.$plan_name["arm_subscription_plan_name"].'</option>';
                                                        }    
                                                    }
                                                }
                                                
                                                $activation_dd_content .= '</select></div></div>';

                                                $activation_cnf_content = '<div class="arm_confirm_activation_box" id="arm_confirm_activation_box_'.$userblog_id.'">
                                                    <div class="arm_confirm_box_body">
                                                        <div class="arm_confirm_box_arrow"></div>
                                                        <div class="arm_confirm_box_text">'.esc_html__("Please select a plan to activate the site.", 'ARM_MULTISUBSITE').'</div>
                                                        '.$activation_dd_content.'
                                                        <div class="arm_confirm_box_btn_container">
                                                            <button type="button" class="arm_confirm_box_btn armok arm_mutisite_subsite_activate_btn" data-userplan="'.$arm_multisite_subsite_id_value['plan_id'].'" data-userid="'.$user_id.'" data-siteid="'.$userblog_id.'" data-msg="'.$multisiteActiveConfirm.'" data-is_multiple_membership="1">'.esc_html__('Activate', 'ARM_MULTISUBSITE').'</button>
                                                            <button type="button" class="arm_confirm_box_btn armcancel" id="arm_multisite_cancel_activate_btn">'.esc_html__('Cancel', 'ARM_MULTISUBSITE').'</button>
                                                        </div>
                                                    </div></div>';   
                                                    $activate_btn_cls = 'arm_activate_multisite';
                                            } else {
                                                $activate_btn_cls = 'arm_mutisite_subsite_activate_btn';
                                            }
                                            
                                            if(!empty($user_plan_list)) {
                                                $multisite_delete_btn .= '<div class="arm_multisite_btn_div"><button type="button" class="'.$activate_btn_cls.'" data-userplan="'.$arm_multisite_subsite_id_value['plan_id'].'" data-userid="'.$user_id.'" data-siteid="'.$userblog_id.'" data-msg="'.$multisiteActiveConfirm.'">'.$args['activate_button_label'].'</button>'.$activation_cnf_content.'</div>';    
                                            }
                                            
                                        } else {

                                            if(!empty($user_plan_list)) {
                                                $cnf_deactive_content = '<div class="arm_confirm_activation_box" id="arm_confirm_activation_box_">
                                                <div class="arm_confirm_box_body">
                                                    <div class="arm_confirm_box_arrow"></div>
                                                    <div class="arm_confirm_box_text">'.$arm_multisite_deactivation_alert.'</div>
                                                    
                                                    <div class="arm_confirm_box_btn_container">
                                                        <button type="button" class="arm_confirm_box_btn armok arm_deactivate_multisite arm_mutisite_subsite_deactivate_btn" data-userplan="'.$arm_multisite_subsite_id_value['plan_id'].'" data-userid="'.$user_id.'" data-siteid="'.$userblog_id.'" data-msg="'.$arm_multisite_deactivate_msg.'">Deactivate</button>
                                                        <button type="button" class="arm_confirm_box_btn armcancel" id="arm_multisite_cancel_deactivate_btn">Cancel</button>
                                                    </div>
                                                </div></div>';

                                                $multisite_delete_btn .= '<div class="arm_multisite_btn_div"><button type="button" class="arm_activate_multisite" data-userplan="'.$arm_multisite_subsite_id_value['plan_id'].'" data-userid="'.$user_id.'" data-siteid="'.$userblog_id.'" data-msg="'.$multisiteActiveConfirm.'">'.$args['deactivate_button_label'].'</button>'.$cnf_deactive_content.'</div>';
                                            }
                                        }
                                        
                                        $multisite_delete_btn .= '<div class="arm_multisite_btn_div"><button type="button" class="arm_delete_multisite" data-userid="'.$user_id.'" data-siteid="'.$userblog_id.'" data-msg="'.$usermultisiteCancel.'">'.$args['delete_button_label'].'</button></div>';

                                        $user_status = ($blog_user_details->deleted == 0) ? "Active" : "Deactive";

                                        $arf_created_multisite ='arf_created_multisite';
                                        $user_plan ='';
                                        $arm_multisite_link = "<span class='".$arf_created_multisite."' style='cursor:pointer'><a href='".$current_admin_site_url."'>".$user_blog_site."</a></span>";
                                        $table .="<tr class='arm_multisite_list_item' id='arm_manage_multisite_row_".$userblog_id."'>
                                            <td class='arm_current_user_plan' data-val='".$current_user_plan_name."'>".$current_user_plan_name."</td>
                                            <td class='arm_site_title_list'>".$arm_multisite_link."</td>
                                            <td class='arm_user_status'>".$user_status."</td>
                                            <td class='arm_action_status'>".$multisite_delete_btn."</td>
                                        </tr>       
                                        ";

                                        $arm_user_blog_flag = 1;
                                        $arm_user_site_blog_count ++;
                                    }
                                }
                                $site_cnt++;
                            }
                        }
                        else{
                            $table .="<tr class='arm_multisite_list_item'>
                                <td colspan='4' >".$no_record_message."</td>
                                </tr>";
                        }
                    }
                    else
                    {

                        $table .= "<tr class='arm_multisite_list_item'><td colspan='4'>";

                        if(empty($no_record_message))
                        {
                            $table .= isset($user_multisite_common_message['arm_multisite_plan_error']) ? $user_multisite_common_message['arm_multisite_plan_error'] : esc_html__('No any subsite found', 'ARM_MULTISUBSITE');
                        }
                        else {
                            $table .= $no_record_message;
                        }
                        
                        $table .= "</td></tr>";
                    }
                    $table .='<input type="hidden" id="loader_img" name="loader_img" value="' . MEMBERSHIP_IMAGES_URL . '/arm_loader.gif"/>';
                    $table .='<input type="hidden" id="arm_multisite_site_count" name="arm_multisite_site_count" value="' . $arm_user_site_blog_count . '"/>';
                    $table .='<input type="hidden" id="arm_multisite_no_record_msg" name="arm_multisite_no_record_msg" value="' . $no_record_message . '"/>';
                }
                else
                {

                    $table .= "<tr class='arm_multisite_list_item'><td colspan='4'>";
                    $table .= $no_record_message;
                    $table .= "</td></tr>";
                }

                $table .='</table>';
                $table .= "<div class='arm_transaction_paging_container'>" . $historyPaging . "</div>";
                $table .='</div>';
                $table .='</div>';
                $table .='</div>';
                
                return $table;

            }
        }
	    
	}

	function arm_multisite_plan_field_function($plan_options){
        if($this->arm_multisubsite_is_compatible()) {
            $enable_disable_planwise_multisite = (!empty($plan_options["enable_disable_planwise_multisite"])) ? $plan_options["enable_disable_planwise_multisite"] : 0;
            $arm_multisite_create = (!empty($plan_options["arm_multisite_create"])) ? $plan_options["arm_multisite_create"] : '';
            $arm_multisite_user_role = (!empty($plan_options["arm_multisite_user_role"])) ? $plan_options["arm_multisite_user_role"] : 'administrator';
            $arm_multisite_planwise_isChecked = checked($enable_disable_planwise_multisite, 1, false);
            $arm_multisite_opt_cls = ($arm_multisite_planwise_isChecked) ? '' : 'hidden_section';    
    	
        ?>
        <div class="arm_solid_divider"></div>
        <div id="arm_multisite_plan_box_content" class="arm_plan_price_box">
            <div class="page_sub_content">
                <div class="page_sub_title"><?php esc_html_e('Multisite Settings','ARM_MULTISUBSITE');?></div>
                <table class="form-table">
                    <tr class="form-field form-required">
                        <th><label><?php esc_html_e('Allow members to create subsite(s) for this plan?' ,'ARM_MULTISUBSITE');?></label></th>   
                        <td>
                            <div class="armclear"></div>
                            <div class="armswitch arm_global_setting_switch" style="vertical-align: middle;">
                                <input type="checkbox" id="enable_disable_planwise_multisite" <?php echo $arm_multisite_planwise_isChecked;?> value="1" class="armswitch_input" name="arm_subscription_plan_options[enable_disable_planwise_multisite]"/>
                                <label for="enable_disable_planwise_multisite" class="armswitch_label" style="min-width:40px;"></label>
                            </div>
                            
                            <div class="armclear"></div>
                        </td>
                    </tr>
                    <tr class="form-field form-required arm_multisite_create_box <?php echo $arm_multisite_opt_cls; ?>">
                        <th><label><?php esc_html_e('Number of site(s) allowed to create' ,'ARM_MULTISUBSITE');?></label></th>   
                        <td>
                            <div class="armclear"></div>
                            <div class="">
                                <input name="arm_subscription_plan_options[arm_multisite_create]" id="arm_multisite_create" type="text" size="50" class="arm_multisite_create" value="<?php echo $arm_multisite_create; ?>" onkeypress="return isNumber(event)" />
                            </div>
                            
                            <div class="armclear"></div>
                        </td>
                    </tr>
                    <tr class="form-field form-required arm_multisite_user_role_box <?php echo $arm_multisite_opt_cls; ?>">
                        <th><label><?php esc_html_e('Select Default Role to Apply Subsite Creator' ,'ARM_MULTISUBSITE');?></label></th>   
                        <td>
                            <div class="armclear"></div>
                            <div class="">
                            	<?php 
                                    $arm_multisite_user_role_list = $this->arm_multisite_user_role(); 
                                    $arm_role_first_selection_label = isset($arm_multisite_user_role_list['administrator']) ? $arm_multisite_user_role_list['administrator'] : '';
                                    $arm_role_first_selection_label_value = 'administrator';

                                ?>
                                <input name="arm_subscription_plan_options[arm_multisite_user_role]" id="arm_multisite_user_role" type="hidden" class="arm_multisite_user_role" value="<?php echo $arm_multisite_user_role; ?>" />
                                <dl class="arm_selectbox column_level_dd">
		                            <dt><span><?php echo $arm_role_first_selection_label; ?></span><input type="text" style="display:none;" value="<?php echo $arm_role_first_selection_label_value; ?>" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
		                            <dd>
		                                <ul data-id="arm_multisite_user_role">
		                                    <?php foreach ($arm_multisite_user_role_list as $role_key => $role_name){?>
		                                        <li data-label="<?php echo $role_name;?>" data-value="<?php echo esc_attr($role_key);?>"><?php echo $role_name; ?></li>
		                                    <?php } ?>
		                                </ul>
		                            </dd>
		                        </dl>
                            </div>
                            
                            <div class="armclear"></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
        }
    }

    function arm_multisite_user_role(){
    	$arm_multisite_user_role_list =array(
            'administrator'=> esc_html__('Administrator', 'ARM_MULTISUBSITE'),
            'author'=> esc_html__('Author', 'ARM_MULTISUBSITE'),
            'contributor'=> esc_html__('Contributor', 'ARM_MULTISUBSITE'),
            'editor'=> esc_html__('Editor', 'ARM_MULTISUBSITE'),
            'subscriber'=> esc_html__('Subscriber', 'ARM_MULTISUBSITE'),
        );

        $arm_multisite_user_role_list = apply_filters('arm_filter_subsite_user_role_outsite', $arm_multisite_user_role_list);
	    return $arm_multisite_user_role_list;
    }
    function before_save_multitesite_field_membership_plan($plan_options, $posted_data)
    {
        $plan_options['enable_disable_planwise_multisite'] = isset($posted_data['arm_subscription_plan_options']['enable_disable_planwise_multisite']) ? $posted_data['arm_subscription_plan_options']['enable_disable_planwise_multisite'] : 0;
        $plan_options['arm_multisite_create'] = isset($posted_data['arm_subscription_plan_options']['arm_multisite_create']) ? $posted_data['arm_subscription_plan_options']['arm_multisite_create'] : 0;
        return $plan_options;
    }

	function arm_multisite_delete(){
		global $arm_global_settings;
		$arm_common_message_settings = $arm_global_settings->common_message;
		$arm_multisite_delete_msg = isset($arm_common_message_settings['arm_multisite_delete_msg']) ? $arm_common_message_settings['arm_multisite_delete_msg'] : esc_html__('Site deleted successfully.', 'ARM_MULTISUBSITE'); 
		$arm_multisite_general_msg = !empty($arm_common_message_settings['arm_general_msg']) ? $arm_common_message_settings['arm_general_msg'] : '';
		$user_id = $_POST['userId'];
		$site_id = $_POST['siteId'];
		$response = array();
		$response = array('status' => 'error', 'type' => 'message', 'message' => $arm_multisite_general_msg);	;
		$user_multisite_data=get_user_meta($user_id, 'arm_multisite_id',true);
		if($site_id != 1){
			
			if(!empty($user_multisite_data))
			{
				foreach ($user_multisite_data as $user_multisite_id_key => $user_multisite_id_value) {

					if($site_id==$user_multisite_data[$user_multisite_id_key]['site_id'])
					{
						wpmu_delete_blog($site_id,true);
						unset($user_multisite_data[$user_multisite_id_key]);
					}
				}


			}
			update_user_meta($user_id,'arm_multisite_id' , $user_multisite_data  );
			$response = array('status' => 'success', 'type' => 'message', 'message' => $arm_multisite_delete_msg);
		}
		echo json_encode($response);
		exit;
	}

	function arm_site_creation(){
		
		global $arm_global_settings, $arm_subscription_plans, $is_multiple_membership_feature, $is_multiple_membership_feature;
		$result =array();
		if ( !is_multisite() )
		{ 
			$result['error'] = esc_html_e('Please Create WordPress Multisite Network. For more information','ARM_MULTISUBSITE').' <a href="https://wordpress.org/support/article/create-a-network/">'.esc_html_e('click here','ARM_MULTISUBSITE').'</a>';

			die;
		}
        
		if(!empty($_POST['site_title']) && !empty($_POST['site_name']))
		{

			$site_limit_msg = isset($_POST['arm_site_limit_msg']) ? $_POST['arm_site_limit_msg'] : '';
			$site_name =str_replace(' ', '', $_POST['site_name']);
            $site_name=preg_replace('/[^A-Za-z0-9\-]/', '', $site_name);
            $user_id = get_current_user_id();

			$site_title = !empty($_POST['site_title']) ? $_POST['site_title'] : '';

			$current_user_plan_id = !empty($_POST['multisite']['arm_user_plan_list_id']) ? $_POST['multisite']['arm_user_plan_list_id'] : 0;
			$user_membership_plan_details = $arm_subscription_plans->arm_get_subscription_plan($current_user_plan_id,'arm_subscription_plan_id, arm_subscription_plan_name,arm_subscription_plan_options');

            $multisite_member_plan_option = get_user_meta($user_id, 'arm_multisite_member_plan_options', true);

			$arm_common_message_settings = $arm_global_settings->common_message;
			$usermultisite_create_msg = isset($arm_common_message_settings['arm_multisite_create_msg']) ? $arm_common_message_settings['arm_multisite_create_msg'] : esc_html__('Site has been successfully created.','ARM_MULTISUBSITE');
			
			$current_user_plan = $user_membership_plan_details['arm_subscription_plan_name'];
			$arm_multisite_create = isset($multisite_member_plan_option[$current_user_plan_id]['arm_multisite_create']) ? $multisite_member_plan_option[$current_user_plan_id]['arm_multisite_create'] : 0;

            $enable_disable_planwise_multisite = isset($multisite_member_plan_option[$current_user_plan_id]['arm_multisite_create']) ? isset($multisite_member_plan_option[$current_user_plan_id]['arm_multisite_create']) : 0;
			
			$arm_multisite_user_role = isset($multisite_member_plan_option[$current_user_plan_id]['arm_multisite_user_role']) ? $multisite_member_plan_option[$current_user_plan_id]['arm_multisite_user_role'] : 'administrator';

			if($arm_multisite_create>0 && $enable_disable_planwise_multisite==1)
			{
				$result = array();
				$arm_multisite_site_ids = array();
				
				$url =network_site_url();
				$main_site =explode('/', $url);
				$main_site = $main_site[count($main_site) -2];
				$domain = $_SERVER['SERVER_NAME'];
				$path = '/'.$main_site.'/'.$site_name.'/';
				

				
				$arm_multisite_subsite_ids = array();
				$arm_multisite_site_id = get_user_meta($user_id,'arm_multisite_id' , TRUE );
				$arm_multisite_site_ids = !empty($arm_multisite_site_id) ? maybe_unserialize($arm_multisite_site_id) : array();
                
				$arm_multisite_site_ids_count = 0;
				foreach ($arm_multisite_site_ids as $arm_multisite_site_id_key => $arm_multisite_site_id_value) {
					
                    if(!$is_multiple_membership_feature->isMultipleMembershipFeature) {
                        if(!empty($arm_multisite_site_id_value['site_id']) && (!empty($arm_multisite_site_id_value['plan_id'])) )
                        {    
                            $arm_multisite_site_ids_count ++;         
                        }
                    } else {
                        if(!empty($arm_multisite_site_id_value['site_id']) && (!empty($arm_multisite_site_id_value['plan_id']) && $arm_multisite_site_id_value['plan_id'] ==  $current_user_plan_id) )
                        {
                            $arm_multisite_site_ids_count ++;
                        }    
                    }
					
				}

                if($arm_multisite_site_ids_count < $arm_multisite_create)
				{

					$arm_multisubsite_subsite_status = array('public' => 1);
					$site_id = wpmu_create_blog($domain, $path, $site_title, $user_id, $arm_multisubsite_subsite_status);
					if(is_int($site_id)){

						
						if(!empty($site_id) && !empty($user_id) && !empty($arm_multisite_user_role)){
							add_user_to_blog( $site_id, $user_id, $arm_multisite_user_role );
						}

						$arm_multisite_subsite_ids['site_id'] = $site_id;
						$arm_multisite_subsite_ids['plan_id'] = $current_user_plan_id;
						
						$arm_multisite_site_ids[] = $arm_multisite_subsite_ids;
					
						update_user_meta($user_id,'arm_multisite_id' , $arm_multisite_site_ids  );

						$max_site_note_arr = $this->get_site_limit_note($user_id);
						$max_site_note = sprintf(esc_html__("You have created %d site out of %d site.", 'ARM_MULTISUBSITE'), $max_site_note_arr['created_site_count'], $max_site_note_arr['max_site_count']);
						
						$success_msg = $usermultisite_create_msg;
						$response = array('status' => 'success', 'type' => 'message', 'message' => $success_msg, 'site_limit_note' => $max_site_note);
					}
					else
					{
						$error_msg = $site_id->errors['blog_taken'][0];
						$response = array('status' => 'error', 'type' => 'message', 'message' => $error_msg);	
					}
				}
				else
				{
					$error_msg = $site_limit_msg;
					$response = array('status' => 'error', 'type' => 'message', 'message' => $error_msg);
				}
				echo json_encode($response);
				exit;
			}
			
		}
    }

    function arm_multisite_user_status($args, $plan_detail){
        global $ARMember;
    	
		$arm_log_date = date("Y-m-d H:i:s")." => ";
         $user_id = (isset($args) && is_array($args)) ? $args['user_id'] : $args;
         $blog_ids=get_user_meta($user_id, 'arm_multisite_id', TRUE);
         $ARMember->arm_write_response($arm_log_date.'arm_multisite_user_status plan_detail1 =>'.maybe_serialize($plan_detail));
        
         if(!empty($blog_ids))
         {
        	foreach ($blog_ids as $blog_id_key => $blog_id_val) 
        	{
        		$blog_id = !empty($blog_id_val['site_id']) ? $blog_id_val['site_id'] : '';
        		$arm_multisite_user_plan_id = !empty($blog_id_val['plan_id']) ? $blog_id_val['plan_id'] : '' ;
        		$ARMember->arm_write_response($arm_log_date.'arm_multisite_user_status blog_id=>'.$blog_id);
		        $ARMember->arm_write_response($arm_log_date.'arm_multisite_user_status args=>'.maybe_serialize($args));
		        $ARMember->arm_write_response($arm_log_date.'arm_multisite_user_status user_id=>'.$user_id);
		        $ARMember->arm_write_response($arm_log_date.'arm_multisite_user_status plan_id=> '.$arm_multisite_user_plan_id);
		        
		        if($plan_detail == $arm_multisite_user_plan_id)
		        {
		        	update_blog_status($blog_id, 'deleted', 1);	
		        	update_blog_status($blog_id, 'public', 0);	
		        }
         	}
         }
    }

    function arm_multisite_user_status_site_remove($args, $plan_detail){
        global $ARMember;
        
        $arm_log_date = date("Y-m-d H:i:s")." => ";
        $user_id = (isset($args) && is_array($args)) ? $args['user_id'] : $args;
        $blog_ids=get_user_meta($user_id, 'arm_multisite_id', TRUE);
        $ARMember->arm_write_response($arm_log_date.'arm_multisite_user_status_site_remove plan_detail1 =>'.maybe_serialize($plan_detail));
        
        if(!empty($blog_ids))
        {
            foreach ($blog_ids as $blog_id_key => $blog_id_val) 
            {
                $blog_id = !empty($blog_id_val['site_id']) ? $blog_id_val['site_id'] : '';
                $arm_multisite_user_plan_id = !empty($blog_id_val['plan_id']) ? $blog_id_val['plan_id'] : '' ;
                $ARMember->arm_write_response($arm_log_date.'arm_multisite_user_status_site_remove blog_id=>'.$blog_id);
                $ARMember->arm_write_response($arm_log_date.'arm_multisite_user_status_site_remove args=>'.maybe_serialize($args));
                $ARMember->arm_write_response($arm_log_date.'arm_multisite_user_status_site_remove user_id=>'.$user_id);
                $ARMember->arm_write_response($arm_log_date.'arm_multisite_user_status_site_remove plan_id=> '.$arm_multisite_user_plan_id);
                
                if(!empty($arm_multisite_user_plan_id) && $plan_detail == $arm_multisite_user_plan_id)
                {
                    update_blog_status($blog_id, 'deleted', 1); 
                    update_blog_status($blog_id, 'public', 0);

                    $member_plan_details_update = get_user_meta($user_id, 'arm_multisite_member_plan_options', true);
                    if(!empty($member_plan_details_update) && isset($member_plan_details_update[$arm_multisite_user_plan_id]))
                    {
                        $member_plan_details_update_old = get_user_meta($user_id, 'arm_multisite_member_plan_options_old', true);
                        if(empty($member_plan_details_update_old))
                        {
                            $member_plan_details_update_old = array();
                        }
                        $member_plan_details_update_old[$arm_multisite_user_plan_id] = $member_plan_details_update[$arm_multisite_user_plan_id];
                        update_user_meta($user_id, 'arm_multisite_member_plan_options_old', $member_plan_details_update_old);
                        

                        unset($member_plan_details_update[$arm_multisite_user_plan_id]);
                        update_user_meta($user_id, 'arm_multisite_member_plan_options', $member_plan_details_update);
                    }
                }
            }
         }

    }

    function arm_multisite_user_plan_renew($user_id, $plan_id){
        global $ARMember;
    	$user_plan_list =get_user_meta($user_id, 'arm_user_plan_ids', true);
        $blog_ids = get_user_meta($user_id, 'arm_multisite_id', TRUE);
        if(!empty($blog_ids))
        {
        	foreach ($blog_ids as $blog_id_key => $blog_id) 
        	{
        		$blog_id = !empty($blog_id['site_id']) ? $blog_id['site_id'] : '';
        		$arm_multisite_user_plan_id = !empty($blog_ids[$blog_id_key]['plan_id']) ? $blog_ids[$blog_id_key]['plan_id'] : '' ;
        		
		        if($plan_id == $arm_multisite_user_plan_id){
		            update_blog_status($blog_id, 'public', 1);
		            update_blog_status($blog_id, 'deleted', 0);
		        }
		    }
		}
    }
    function arm_multisite_shortcode_option($data =array()){
        if($this->arm_multisubsite_is_compatible() || 1==1) {
            $data = '<li data-label="'.esc_html__('Create Subsite', 'ARM_MULTISUBSITE').'" data-value="arm_multisiteinfo">
                '.esc_html__('Create Subsite',  'ARM_MULTISUBSITE').'
             </li>
             <li data-label="'.esc_html__('Manage Subsites', 'ARM_MULTISUBSITE').'" data-value="arm_manage_multisiteinfo">
                '.esc_html__('Manage Subsites',  'ARM_MULTISUBSITE').'
             </li>';    
        }
        
        echo $data;
    }


    function arm_multisite_select_option($select_option=array()){
    	global $ARMember, $arm_membership_setup;
        $select_option ='<form class="arm_shortcode_other_opts arm_shortcode_other_opts_arm_multisiteinfo arm_hidden" onsubmit="return false;">
                            <div class="arm_group_body">
                                <table class="arm_shortcode_option_table">
                                <tr>
                                	<th>'.esc_html__('Select Form for Styling','ARM_MULTISUBSITE').'</th>
	                                <td>
	                                
	                                        <input type="hidden" id="arm_multistite_form_id" name="form_id" value="" data-msg-required="'. esc_html__("Please select signup / registration form.", 'ARM_MULTISUBSITE') .'" />
	                                        <dl class="arm_selectbox column_level_dd">
	                                            <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
	                                            <dd>
	                                                <ul data-id="arm_multistite_form_id" class="arm_setup_form_options_list">'.
	                                                     $arm_membership_setup->arm_setup_form_list_options()
	                                                .'</ul>
	                                            </dd>
	                                        </dl>
	                                        <span class="arm_setup_error_msg"></span>
	                                    
	                                </td>
                                </tr>
                                 <tr>
                                    <th>'.esc_html__('Title','ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="arm_multisite_form_title" name="arm_multisite_form_title" value="'.esc_html__('Add New Subsite', 'ARM_MULTISUBSITE').'" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Site Name Label','ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="site_name" name="site_name" value="'.esc_html__('Site Name', 'ARM_MULTISUBSITE').'" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Site Title Label','ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="site_title" name="site_title" value="'.esc_html__('Site title', 'ARM_MULTISUBSITE').'" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Display subsite information belt', 'ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <div class="armswitch enable_note_belt_switch_container">
                                            <input type="checkbox" value="true" class="armswitch_input" id="enable_note_belt_checkbox" name="enable_note_belt" checked="checked">
                                            <label for="enable_note_belt_checkbox" class="armswitch_label"></label>
                                            <div class="armclear"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Maximum Site limit message', 'ARM_MULTISUBSITE').'</th>
                                    <td><input type="text" name="site_limit_message" id="site_limit_message" value="'.esc_html__("You have reached to limit of site(s) allowed to create.", 'ARM_MULTISUBSITE').'" /></td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Submit button text', 'ARM_MULTISUBSITE').'</th>
                                    <td><input type="text" name="submit_button_text" id="submit_button_text" value="'.esc_html__("Submit", 'ARM_MULTISUBSITE').'" /></td>
                                </tr>
                                </table>
                            </div>
                        </form>'; 
                echo $select_option;  

    }

    function arm_multisite_manage_option($select_option=array()){
    	global $ARMember, $arm_membership_setup;
        $select_option ='<form class="arm_shortcode_other_opts arm_shortcode_other_opts_arm_manage_multisiteinfo arm_hidden" onsubmit="return false;">
                            <div class="arm_group_body">
                                <table class="arm_shortcode_option_table">
                                <tr>
                                    <th>'.esc_html__('Title','ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="form_title" name="form_title" value="'.esc_html__('Manage Subsites','ARM_MULTISUBSITE').'" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Records per page','ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="records_per_page" name="records_per_page" value="10" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('No Record Message','ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="no_record_message" name="no_record_message" value="'.esc_html__('There is no record of subsite','ARM_MULTISUBSITE').'" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Display subsite information belt', 'ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <div class="armswitch enable_note_belt_switch_container">
                                            <input type="checkbox" value="true" class="armswitch_input" id="enable_note_belt_checkbox2" name="enable_note_belt" checked="checked">
                                            <label for="enable_note_belt_checkbox2" class="armswitch_label"></label>
                                            <div class="armclear"></div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Subsite activate button label', 'ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="multisite_activate_btn_label" name="activate_button_label" value="Activate" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Subsite deactivate button label', 'ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="multisite_deactivate_btn_label" name="deactivate_button_label" value="Deactivate" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Subsite delete button label', 'ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="multisite_delete_btn_label" name="delete_button_label" value="Delete" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Membership plan heading label', 'ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="membership_plan_label" name="membership_plan_label" value="Membership Plan" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Subsite heading label', 'ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="site_label" name="site_label" value="Site" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Status heading label', 'ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="status_label" name="status_label" value="Status" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>'.esc_html__('Action heading label', 'ARM_MULTISUBSITE').'</th>
                                    <td>
                                        <input type="text" id="action_label" name="action_label" value="Action" />
                                    </td>
                                </tr>
                                </table>
                            </div>
                        </form>'; 
        echo $select_option;  

    }

    function arm_multisite_shortcode_add_tab_buttons($tab_buttons =array()){
        $tab_buttons =' <div class="arm_group_footer arm_shortcode_other_opts arm_shortcode_other_opts_arm_multisiteinfo arm_hidden" style="">
                                <div class="popup_content_btn_wrapper">
                                        <button type="button" class="arm_shortcode_insert_btn arm_insrt_btn" id="arm_shortcode_other_opts_arm_multisiteinfo" data-code="arm_site_creation">'.esc_html__('Add Shortcode', 'ARM_MULTISUBSITE').'</button>
                                        <a class="arm_cancel_btn popup_close_btn" href="javascript:void(0)">'.esc_html__('Cancel', 'ARM_MULTISUBSITE').'</a>
                                </div>
                        </div>';
        echo $tab_buttons;
    }

    function arm_manage_multisite_shortcode_add_tab_buttons($tab_buttons =array()){
        $tab_buttons =' <div class="arm_group_footer arm_shortcode_other_opts arm_shortcode_other_opts_arm_manage_multisiteinfo arm_hidden" style="">
                                <div class="popup_content_btn_wrapper">
                                        <button type="button" class="arm_shortcode_insert_btn arm_insrt_btn" id="arm_shortcode_other_opts_arm_manage_multisiteinfo" data-code="arm_manage_subsite">'.esc_html__('Add Shortcode', 'ARM_MULTISUBSITE').'</button>
                                        <a class="arm_cancel_btn popup_close_btn" href="javascript:void(0)">'.esc_html__('Cancel', 'ARM_MULTISUBSITE').'</a>
                                </div>
                        </div>';
        echo $tab_buttons;
    }

    function armmultisubsite_getapiurl() {
            $api_url = 'https://www.arpluginshop.com/';
            return $api_url;
    }
		
	function upgrade_data_multisubsite() {
			global $armnew_multisubsite_version;
	
			if (!isset($armnew_multisubsite_version) || $armnew_multisubsite_version == "")
				$armnew_multisubsite_version = get_option('arm_multisubsite_version');
	
            if (version_compare($armnew_multisubsite_version, '1.1', '<')) {
				$path = ARM_MULTISUBSITE_DIR . '/upgrade_latest_data_multisite.php';
				include($path);
			}
	}

    function arm_multisite_update_all_settings($new_global_settings = array(), $posted_data){
        
        $new_global_settings['general_settings']['multisite_subscriptions'] = isset($posted_data['arm_general_settings']['multisite_subscriptions']) ? $posted_data['arm_general_settings']['multisite_subscriptions'] : 0;

        return $new_global_settings;
    }

    function arm_multisite_member_details($arm_member_details = array(), $user_id){
            global $arm_subscription_plans,$arm_global_settings;
            $all_membership_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name');
            $user_plan_list =get_user_meta($user_id, 'arm_user_plan_ids',TRUE);
            
            $blog_user =get_blogs_of_user($user_id ,$all = TRUE);
            $arm_multisite_subsite_ids = get_user_meta($user_id,'arm_multisite_id' , TRUE );
            
            $arm_member_site ='<div class="arm_view_member_sub_title">'.esc_html__('Subsite Details', 'ARM_MULTISUBSITE').'</div>
                        <div class="arm_view_member_sub_content arm_membership_history_list armPageContainer">
                        <div class="arm_multisitehistory_wrapper" data-user_id="' . $user_id . '">
                            <table class="form-table arm_member_last_subscriptions_table" width="100%">
                            <tr>
                            <td>' . esc_html__('Membership Plan', 'ARM_MULTISUBSITE') . '</td>
                            <td>' . esc_html__('Site', 'ARM_MULTISUBSITE') . '</td>
                            <td>' . esc_html__('Status', 'ARM_MULTISUBSITE') . '</td>
                            </tr>';

            if(!empty($arm_multisite_subsite_ids))
            {
            	krsort( $arm_multisite_subsite_ids );
            	foreach ($arm_multisite_subsite_ids as $arm_multisite_subsite_id_value) 
            	{
            		$arm_multisite_subsite_id = !empty($arm_multisite_subsite_id_value['site_id']) ? $arm_multisite_subsite_id_value['site_id'] : '';
            		$arm_multisite_subsite_plan_id = !empty($arm_multisite_subsite_id_value['plan_id']) ? $arm_multisite_subsite_id_value['plan_id'] : '';

			        if(!empty($arm_multisite_subsite_id) && !empty($arm_multisite_subsite_plan_id))
			        {
			        	$current_user_plan_name = !empty($all_membership_plans[$arm_multisite_subsite_plan_id]['arm_subscription_plan_name']) ? $all_membership_plans[$arm_multisite_subsite_plan_id]['arm_subscription_plan_name'] : '';
			        	$blog_user_details = get_blog_details($arm_multisite_subsite_id);
			        	$userblog_id = $blog_user_details->blog_id;
                        $user_blog_site = $blog_user_details->blogname;
                        $current_admin_site_url = get_admin_url($userblog_id);
                        $multisite_delete_btl = 'Delete'; 
                        $member_site_active = ( ($blog_user_details->deleted == 0) ? 'Deactive' : 'Active');
                        $user_status = ( ($blog_user_details->deleted == 0) ? 'Active' : 'Deactive');
                        
                        $arm_member_site .='<tr class="arm_member_last_subscriptions_data">
                                <td>'.$current_user_plan_name.'</td>
                                <td><a href='.$current_admin_site_url.'>'.$user_blog_site.'</a></td>
                                <td id="blog_status_'.$userblog_id.'" class="blog_status">'.$user_status.'</td>
                            </tr>
                        ';
                        $arm_blog_user_flag = 1;
			        }
            	}
            }
            else{
                $arm_member_site .='<tr class="arm_member_last_subscriptions_data">
                        <td colspan="5" style="text-align: center;">' . esc_html__('No Multisite created.', 'ARM_MULTISUBSITE') . '</td>
                      </tr>';
            }
            
            $arm_member_site .='</table>
                        <div class="armclear"></div>';
            echo $arm_member_site;
    }

    function arm_deactivate_multisite_after_action_by_admin($user_id=0, $plan_id=0) {
        $blog_ids = get_user_meta($user_id, 'arm_multisite_id', TRUE);
        $arm_membership_plan_list = get_user_meta($user_id, 'arm_user_plan_ids', TRUE);
    
        if( isset($_REQUEST['action']) && 'update_member' == $_REQUEST['action'] ) {
            if(!empty($arm_membership_plan_list) && in_array($plan_id, $arm_membership_plan_list)) {
                foreach ($blog_ids as $key => $blog) {
                    if(!in_array($blog['plan_id'], $arm_membership_plan_list)) {
                        update_blog_status($blog['site_id'], 'public', 0);
                        update_blog_status($blog['site_id'], 'deleted', 1);    

                        $member_plan_details_update = get_user_meta($user_id, 'arm_multisite_member_plan_options', true);
                        if(!empty($member_plan_details_update) && isset($member_plan_details_update[$arm_multisite_user_plan_id]))
                        {
                            $member_plan_details_update_old = get_user_meta($user_id, 'arm_multisite_member_plan_options_old', true);
                            if(empty($member_plan_details_update_old))
                            {
                                $member_plan_details_update_old = array();
                            }
                            $member_plan_details_update_old[$arm_multisite_user_plan_id] = $member_plan_details_update[$arm_multisite_user_plan_id];
                            update_user_meta($user_id, 'arm_multisite_member_plan_options_old', $member_plan_details_update_old);

                            unset($member_plan_details_update[$arm_multisite_user_plan_id]);
                            update_user_meta($user_id, 'arm_multisite_member_plan_options', $member_plan_details_update);
                        }
                    }
                }
            }    
        }
        
    }

    function arm_multisite_deactive($user_id=0,$plan_id=0){
        global $ARMember, $arm_global_settings;
    	
        $user_id = (isset($_POST['userid'])) ? $_POST['userid'] : $user_id;
        $site_action= (isset($_POST['site_action'])) ? $_POST['site_action'] : '';
        $blog_ids_posted = $blog_ids = (!empty($_POST['siteid'])) ? $_POST['siteid'] : '';
        $blog_ids = empty($blog_ids) ? get_user_meta($user_id, 'arm_multisite_id', TRUE) : array( array('site_id' => $blog_ids) );
    
        $arm_membership_plan_list = get_user_meta($user_id, 'arm_user_plan_ids', TRUE);
        $arm_multisite_user_plan_update_flag = '';
        $ARMember->arm_write_response('plan blog_ids => before '. maybe_serialize($blog_ids));
        
        if(!empty($blog_ids))
        {
        	foreach ($blog_ids as $blog_id_key => $blog_id) 
        	{
        		$blog_id = !empty($blog_id['site_id']) ? $blog_id['site_id'] : '';
        		$arm_multisite_user_plan_id = !empty($blog_ids[$blog_id_key]['plan_id']) ? $blog_ids[$blog_id_key]['plan_id'] : 0 ;
        		
	        	if(empty($site_action) && (in_array($plan_id, $arm_membership_plan_list)))
		        {
		        	$site_action = 'Deactive';
		        }
		        
		        $result =array();
		        if($site_action =='Deactive' ){
		        	update_blog_status($blog_id, 'public', 0);
		            update_blog_status($blog_id, 'deleted', 1);
		            $result['site_action'] ='Active';
		            $result['site_status'] ='Deactive';
		        }else{
		            update_blog_status($blog_id, 'deleted', 0);
		            update_blog_status($blog_id, 'public', 1);
		            $result['site_action'] ='Deactive';
		            $result['site_status'] ='Active';
		        }
		    }
		    
		    if(!empty($arm_multisite_user_plan_update_flag))
		    {
		    	update_user_meta($user_id,'arm_multisite_id' , $blog_ids  );
		    }
	    }
	    
	    if(!empty($blog_ids_posted))
	    {
        	echo json_encode($result);
        	exit;
        }
    }
	
	function armmultisubsite_get_remote_post_params($plugin_info = "") {
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
	
				if (strpos(strtolower($plugin["Title"]), ARM_MULTISUBSITE_DIR_NAME) !== false) {
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
			
        /**
         * Restrict Network Activation
         */
        public static function arm_multisite_check_network_activation($network_wide) {
            if (!$network_wide)
                return;

            deactivate_plugins(plugin_basename(__FILE__), TRUE, TRUE);

            header('Location: ' . network_admin_url('plugins.php?deactivate=true'));
            exit;
        }

    public static function install() {
        global $arm_multisubsite;
        $arm_multisubsite_version = get_option('arm_multisubsite_version');

        if (!isset($arm_multisubsite_version) || $arm_multisubsite_version == '') {
            global $wpdb, $arm_multisubsite_version;
            update_option('arm_multisubsite_version', $arm_multisubsite_version);
        }
        // give administrator users capabilities
        $args = array(
            'role' => 'administrator',
            'fields' => 'id'
        );
        $users = get_users($args);
        if (count($users) > 0) {
            foreach ($users as $key => $user_id) {
                $armroles = $arm_multisubsite->arm_multisubsite_capabilities();
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

    public static function uninstall() {
        delete_option('arm_multisubsite_version');
        delete_option('armmultisubsite_update_token');
    }

    function arm_multisubsite_admin_notices() {
        if (!$this->is_armember_support())
            echo "<div class='updated'><p>" . esc_html__('Multisite (subsite) For ARMember plugin requires ARMember Plugin installed and active.', 'ARM_MULTISUBSITE') . "</p></div>";

        else if (!$this->is_version_compatible())
            echo "<div class='updated'><p>" . esc_html__('Multisite (subsite) For ARMember plugin requires ARMember plugin installed with version 3.2.1 or higher.', 'ARM_MULTISUBSITE') . "</p></div>";

        else if (!$this->arm_multisubsite_is_compatible()) 
            echo "<div class='updated'><p>" . esc_html__('To use Multisite (subsite) For ARMember plugin, you have to enable wordpress multisite.', 'ARM_MULTISUBSITE') . "</p></div>";
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
        if (!version_compare($this->get_armember_version(), '3.2.1', '>=') || !$this->is_armember_support()) :
            return false;
        else :
            return true;
        endif;
    }

    function arm_multisubsite_is_compatible() {
        if( $this->is_armember_support() && $this->is_version_compatible() && is_multisite() ) :
            return true;
        else :
            return false;
        endif;
    }

    function admin_enqueue_script(){
        global $arm_multisubsite_version, $arm_slugs, $arm_version;
        
        $arm_multisite_subsite_page_arr = $this->arm_multisubsite_page_slug();

        $arm_version_compatible = (version_compare($arm_version, '4.0.1', '>=')) ? 1 : 0;

        if( isset( $_REQUEST['page'] ) && ( (in_array($_REQUEST['page'], (array) $arm_slugs)) || (in_array( $_REQUEST['page'], (array) $arm_multisite_subsite_page_arr )) ) ) {
            

            if ( (in_array($_REQUEST['page'], array($arm_slugs->manage_members)) && (isset($_GET['action']) && in_array($_GET['action'], array('view_member')) ) )  || (in_array( $_REQUEST['page'], (array) $arm_multisite_subsite_page_arr )) || (in_array($_REQUEST['page'], array($arm_slugs->manage_plans)) && (isset($_GET['action']) && in_array($_GET['action'], array('edit_plan','new')) )) ) {
	            wp_register_script( 'arm-admin-multisubsite', ARM_MULTISUBSITE_URL . '/js/arm_admin_multisite.js', array(), $arm_multisubsite_version );
	            wp_enqueue_script( 'arm-admin-multisubsite' );
        	}
            
            wp_enqueue_script( 'arm_tipso' );
            wp_enqueue_script( 'arm_validate' );
            wp_enqueue_script( 'arm_bpopup' );
            wp_enqueue_script( 'arm_chosen_jq_min' );
            wp_enqueue_script( 'arm_icheck-js' );
             wp_enqueue_script( 'sack' );
             
            
            echo '<script type="text/javascript" data-cfasync="false">';
            echo 'imageurl = "'.MEMBERSHIP_IMAGES_URL.'";';
            echo 'armpleaseselect = "'.esc_html__("Please select one or more records.", 'ARM_MULTISUBSITE').'";';
            echo 'armbulkActionError = "'.esc_html__("Please select valid action.", 'ARM_MULTISUBSITE').'";';
            echo 'armsaveSettingsSuccess = "'.esc_html__("Settings has been saved successfully.", 'ARM_MULTISUBSITE').'";';
            echo 'armsaveSettingsError = "'.esc_html__("There is a error while updating settings, please try again.", 'ARM_MULTISUBSITE').'";';

            echo '</script>';

            wp_enqueue_script('arm_bootstrap_js');
            wp_enqueue_script('arm_bootstrap_datepicker_with_locale');
           

            if($arm_version_compatible)
            {
                wp_enqueue_script('datatables');
                wp_enqueue_script('buttons-colvis');
                wp_enqueue_script('fixedcolumns');
                wp_enqueue_script('fourbutton');
            }
            else
            {
                wp_enqueue_script( 'jquery_dataTables', MEMBERSHIP_URL.'/datatables/media/js/jquery.dataTables.js', array(), $arm_multisubsite_version );
                wp_enqueue_script( 'FixedColumns', MEMBERSHIP_URL . '/datatables/media/js/FixedColumns.js', array(), $arm_multisubsite_version );
                wp_enqueue_script( 'FourButton', MEMBERSHIP_URL . '/datatables/media/js/four_button.js', array(), $arm_multisubsite_version );
            }            
            
        }

        if (in_array(basename($_SERVER['PHP_SELF']), array('post.php', 'page.php', 'post-new.php', 'page-new.php'))) {
            //arm_tinymce        
            $wp_internal_script = 'jQuery(document).on("change", "#arm_shortcode_other_type", function (e) {
                            var arm_shortcode_other_type = jQuery(this).val();
                            var arm_multistite_form_id = jQuery("#arm_multistite_form_id").val();
                            if ((arm_shortcode_other_type == "arm_multisiteinfo" && arm_multistite_form_id == "")) {
                                jQuery(".arm_shortcode_insert_btn").attr("disabled", "disabled");
                                jQuery(".arm_shortcode_form_options").addClass("arm_hidden");
                            } else {
                                jQuery(".arm_shortcode_insert_btn").removeAttr("disabled");
                                jQuery(".arm_shortcode_form_options").removeClass("arm_hidden");
                            }
                            return false;
                        });
            
                        jQuery(document).on("change", "#arm_multistite_form_id", function (e) {
                            var arm_multistite_form_id = jQuery(this).val();
                            var arm_shortcode_other_type = jQuery("#arm_shortcode_other_type").val();
                            if (arm_shortcode_other_type == "arm_multisiteinfo" && arm_multistite_form_id == "") {
                            jQuery(".arm_shortcode_insert_btn").attr("disabled", "disabled");
                            jQuery(".arm_shortcode_form_options").addClass("arm_hidden");
                            } else {
                                jQuery(".arm_shortcode_insert_btn").removeAttr("disabled");
                                jQuery(".arm_shortcode_form_options").removeClass("arm_hidden");
                            }
                            return false;
                        });';
            wp_add_inline_script('arm_tinymce', $wp_internal_script);
        }
    }

    function set_admin_css(){
        global $arm_multisubsite_version, $arm_slugs, $arm_version;

        $arm_multisite_subsite_page_arr = $this->arm_multisubsite_page_slug();
        
        $arm_version_compatible = (version_compare($arm_version, '4.0.1', '>=')) ? 1 : 0;
        
        if (isset($_REQUEST['page']) && ( (in_array($_REQUEST['page'], (array) $arm_slugs)) || (in_array($_REQUEST['page'], (array) $arm_multisite_subsite_page_arr)) ) ) {
        	wp_enqueue_style( 'arm_admin_css' );
            wp_enqueue_style( 'arm-font-awesome-css' );
            wp_enqueue_style( 'arm_form_style_css' );
            wp_enqueue_style( 'arm_chosen_selectbox' );
            wp_enqueue_style('arm_bootstrap_all_css');
            

            if($arm_version_compatible)
            {
                wp_enqueue_style('datatables');
            }
            else
            {
                $internal_style = '
                @import "'.MEMBERSHIP_URL.'/datatables/media/css/demo_page.css";
                @import "'.MEMBERSHIP_URL.'/datatables/media/css/demo_table_jui.css";
                @import "'.MEMBERSHIP_URL.'/datatables/media/css/jquery-ui-1.8.4.custom.css";';

                $internal_style .= "
                .paginate_page a{display:none;}
                #poststuff #post-body {margin-top: 32px;}
                .DTFC_ScrollWrapper{background-color: #EEF1F2;}
                #arm_multisite_subsite_list_form table thead th.left, 
                #arm_multisite_subsite_list_form table tbody td.left {
                    text-align: left !important;
                }
                .dataTables_filter .arm_datatable_searchbox input {
                    margin-left : 5px;
                }
                .arm_multisite_documentation_link{
                    float: right;
                    margin: 0 20px;
                    width: auto;
                    text-align: right;
                }";

                wp_add_inline_style("arm_admin_css", $internal_style);
            }
    	}
    }
    
    function set_front_js() {
        global $arm_multisubsite_version;

        wp_enqueue_script('jquery');
        wp_register_script('arm_multisubsite_js', ARM_MULTISUBSITE_URL . '/js/arm_multisite.js', array(), $arm_multisubsite_version);
        wp_enqueue_script('arm_multisubsite_js');

        wp_register_script('arm_angular_with_material_js', MEMBERSHIP_URL . '/js/angular/arm_angular_with_material.js', $arm_multisubsite_version);

        wp_register_script('arm_form_angular_js', MEMBERSHIP_URL . '/js/angular/arm_form_angular.js', $arm_multisubsite_version);
        
        wp_register_script('arm_multisite_form_angular_js', ARM_MULTISUBSITE_URL . '/js/angular/arm_multisite_form_angular.js', $arm_multisubsite_version);
        
        wp_register_style('arm_form_style_css', MEMBERSHIP_URL . '/css/arm_form_style.css', array(), MEMBERSHIP_VERSION);
        wp_enqueue_style('arm_form_style_css');
        wp_register_style('arm_angular_material_css', MEMBERSHIP_URL . '/css/arm_angular_material.css', array(), MEMBERSHIP_VERSION);
        

        }

    function set_front_css() {
        global $arm_multisubsite_version;
        wp_register_style('arm_multisubsite_css', ARM_MULTISUBSITE_URL . '/css/arm_multisite.css', array(), $arm_multisubsite_version);
        wp_enqueue_style('arm_multisubsite_css');

        global $arm_global_settings;
        $frontfontstyle = $arm_global_settings->arm_get_front_font_style();

        $transactionsWrapperClass = ".arm_multisite_container";

        $add_internal_css = "
                        $transactionsWrapperClass .arm_form_input_container_select select{
                            {$frontfontstyle['frontOptions']['level_3_font']['font']}
                        }
                        $transactionsWrapperClass .arm_form_input_container_select select option{
                            {$frontfontstyle['frontOptions']['level_3_font']['font']}
                        }
                        $transactionsWrapperClass .arm_multisite_heading_main {
                            {$frontfontstyle['frontOptions']['level_1_font']['font']}
                        }
                        $transactionsWrapperClass .arm_multisite_list_header th{
                            {$frontfontstyle['frontOptions']['level_2_font']['font']}
                        }
                        $transactionsWrapperClass .arm_multisite_list_item td{
                            {$frontfontstyle['frontOptions']['level_3_font']['font']}
                        }
                        $transactionsWrapperClass .arm_paging_wrapper .arm_paging_info,
                        $transactionsWrapperClass .arm_paging_wrapper .arm_paging_links a{
                            {$frontfontstyle['frontOptions']['level_4_font']['font']}
                        }";

        wp_add_inline_style('arm_multisubsite_css', $add_internal_css);
    }

    function arm_multisubsite_menu(){
            global $arm_slugs;

            $arm_multisite_subsite_name    = esc_html__( 'Manage Subsites', 'ARM_MULTISUBSITE' );
            $arm_multisite_subsite_title   = esc_html__( 'Manage Subsites', 'ARM_MULTISUBSITE' );
            $arm_multisite_subsite_cap     = 'arm_manage_subsites';
            $arm_multisite_subsite_slug    = 'arm_manage_subsites';

            add_submenu_page( $arm_slugs->main, $arm_multisite_subsite_name, $arm_multisite_subsite_title, $arm_multisite_subsite_cap, $arm_multisite_subsite_slug, array( $this, 'arm_multisubsite_route' ) );
            
    }

    function arm_multisubsite_route() {
        global $ARMember;
        $pageWrapperClass = '';
        $request = $_REQUEST;

        if(isset($request['page']))
        {
            if (is_rtl()) {
                $pageWrapperClass = 'arm_page_rtl';
            }
            echo '<div class="arm_page_wrapper '.$pageWrapperClass.'" id="arm_page_wrapper">';
            $ARMember->arm_admin_messages_init();
            switch($request['page']) {
                case 'arm_manage_subsites':
                    if( file_exists( ARM_MULTISUBSITE_VIEW_DIR . 'arm_multisite_subsite.php' ) ) {
                        include_once ARM_MULTISUBSITE_VIEW_DIR . 'arm_multisite_subsite.php';
                    }
                break;
            }
            echo '</div>';
        }
    }

    function arm_multisubsite_page_slug() {
        return array(
            'arm_manage_subsites',
        );
    }

    function arm_multisubsite_capabilities() {
        $arm_multisubsite_cap = array(
            'arm_manage_subsites' => esc_html__('Manage Subsites', 'ARM_MULTISUBSITE'),
        );
        return $arm_multisubsite_cap;
    }

    function arm_subsite_add_capabilities_to_new_user($user_id){
        global $ARMember, $arm_multisubsite;
        if( $user_id == '' ){
            return;
        }
        if( user_can($user_id,'administrator')){
            $armroles = $arm_multisubsite->arm_multisubsite_capabilities();
            $userObj = new WP_User($user_id);
            foreach ($armroles as $armrole => $armroledescription){
                $userObj->add_cap($armrole);
            }
            unset($armrole);
            unset($armroles);
            unset($armroledescription);
        }
    }
    function arm_multisite_related_common_message($common_messages) {
        if ($this->is_version_compatible()) {
            ?>
            <table class="form-table">
            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_multisite_create_msg"><?php esc_html_e('Susite Added Message', 'ARM_MULTISUBSITE'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_multisite_create_msg]" id="arm_multisite_create_msg" value="<?php echo (!empty($common_messages['arm_multisite_create_msg']) ) ? $common_messages['arm_multisite_create_msg'] : esc_html__('Site has been successfully created.', 'ARM_MULTISUBSITE'); ?>" />
                </td>
            </tr>
             <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_multisite_plan_error"><?php esc_html_e('Empty Subsite list message', 'ARM_MULTISUBSITE'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_multisite_plan_error]" id="arm_multisite_plan_error" value="<?php echo (!empty($common_messages['arm_multisite_plan_error']) ) ? $common_messages['arm_multisite_plan_error'] : esc_html__('No any subsite found', 'ARM_MULTISUBSITE'); ?>" />

                </td>
            </tr>
            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_multisite_delete_alert"><?php esc_html_e('Subsite Delete Confirm Message', 'ARM_MULTISUBSITE'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_multisite_delete_alert]" id="arm_multisite_delete_alert" value="<?php echo (!empty($common_messages['arm_multisite_delete_alert']) ) ? $common_messages['arm_multisite_delete_alert'] : esc_html__('Are you sure you want to delete this Site?', 'ARM_MULTISUBSITE'); ?>" />
                </td>
            </tr>
            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_multisite_delete_msg"><?php esc_html_e('Subsite Deleted Success Message', 'ARM_MULTISUBSITE'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_multisite_delete_msg]" id="arm_multisite_delete_msg" value="<?php echo (!empty($common_messages['arm_multisite_delete_msg']) ) ? $common_messages['arm_multisite_delete_msg'] : esc_html__('Site deleted successfully.', 'ARM_MULTISUBSITE'); ?>" />
                </td>
            </tr>

            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_multisite_activation_alert"><?php esc_html_e('Subsite Activation Confirm Message', 'ARM_MULTISUBSITE'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_multisite_activation_alert]" id="arm_multisite_activation_alert" value="<?php echo (!empty($common_messages['arm_multisite_activation_alert']) ) ? $common_messages['arm_multisite_activation_alert'] : esc_html__('Are you sure you want to activate this Site?', 'ARM_MULTISUBSITE'); ?>" />
                </td>
            </tr>

            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_multisite_activate_msg"><?php esc_html_e('Subsite Activated Success Message', 'ARM_MULTISUBSITE'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_multisite_activate_msg]" id="arm_multisite_activate_msg" value="<?php echo (!empty($common_messages['arm_multisite_activate_msg']) ) ? $common_messages['arm_multisite_activate_msg'] : esc_html__('Your Site activated successfully.', 'ARM_MULTISUBSITE'); ?>" />
                </td>
            </tr>

            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_multisite_deactivation_alert"><?php esc_html_e('Subsite Deactivation Confirm Message', 'ARM_MULTISUBSITE'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_multisite_deactivation_alert]" id="arm_multisite_deactivation_alert" value="<?php echo (!empty($common_messages['arm_multisite_deactivation_alert']) ) ? $common_messages['arm_multisite_deactivation_alert'] : esc_html__('Are you sure you want to deactivate this Site?', 'ARM_MULTISUBSITE'); ?>" />
                </td>
            </tr>

            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_multisite_deactivate_msg"><?php esc_html_e('Subsite Deactivated Success Message', 'ARM_MULTISUBSITE'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_multisite_deactivate_msg]" id="arm_multisite_deactivate_msg" value="<?php echo (!empty($common_messages['arm_multisite_deactivate_msg']) ) ? $common_messages['arm_multisite_deactivate_msg'] : esc_html__('Your Site deactivated successfully.', 'ARM_MULTISUBSITE'); ?>" />
                </td>
            </tr>


            <tr class="form-field">
                <th class="arm-form-table-label"><label for="arm_multisite_delete_note_msg"><?php esc_html_e('Number of Subsite Allowed Warning Message', 'ARM_MULTISUBSITE'); ?></th>
                <td class="arm-form-table-content">
                    <input type="text" name="arm_common_message_settings[arm_multisite_delete_note_msg]" id="arm_multisite_delete_note_msg" value="<?php echo (!empty($common_messages['arm_multisite_delete_note_msg']) ) ? $common_messages['arm_multisite_delete_note_msg'] : esc_html__('Please Delete any [ARM_MULTISITE_TO_DELETE] Subsite.', 'ARM_MULTISUBSITE'); ?>" />
                    <span class="arm_info_text"><b>[ARM_MULTISITE_TO_DELETE]</b> : <?php esc_html_e("This will replace difference between number of maximum site allowed and number of created site.",'ARM_MULTISUBSITE'); ?></span>
                    <span class="arm_info_text"><b>[ARM_MAX_MULTISITE_ALLOWED]</b> : <?php esc_html_e("This will replace number of maximum subsite allowed.",'ARM_MULTISUBSITE'); ?></span>
                    <span class="arm_info_text"><b>[ARM_MULTISITE_CREATED]</b> : <?php esc_html_e("This will replace number of subsite added.",'ARM_MULTISUBSITE'); ?></span>
                </td>
            </tr>

            </table>

            <?php
        }
    }

    function arm_multisite_global_option() {
        global $arm_subscription_plans , $arm_global_settings; 
        $all_global_settings = $arm_global_settings->arm_get_all_global_settings();
        $general_settings = $all_global_settings['general_settings'];
      
    ?>
            <div class="arm_solid_divider"></div>
            <div class="page_sub_title"><?php esc_html_e('Multisite(Subsite)','ARM_MULTISUBSITE' ); ?></div>
            <table class="form-table">
                 <tr class="form-field">
                    <th class="arm-form-table-label"><?php esc_html_e('Multisite Subscriptions','ARM_MULTISUBSITE');?></th>
                    <td class="arm-form-table-content">
                        <div class="armswitch arm_global_setting_switch">
                        <?php $multisite_subscriptions = isset($general_settings['multisite_subscriptions']) ? $general_settings['multisite_subscriptions'] : 0; ?>
                            <input type="checkbox" id="multisite_subscriptions" <?php checked($multisite_subscriptions, '1');?> value="1" class="armswitch_input" name="arm_general_settings[multisite_subscriptions]"/>
                            <label for="multisite_subscriptions" class="armswitch_label"></label>
                        </div>
                        <label for="multisite_subscriptions" class="arm_global_setting_switch_label"><?php esc_html_e('Provides "Singlesites" based on purchased subscriptions', 'ARM_MULTISUBSITE');?> </label>
                    </td>
                </tr>
            </table>
    <?php
    }
}

global $arm_multisubsite;
$arm_multisubsite = new ARM_MultiSubSite();

if( file_exists( ARM_MULTISUBSITE_CLASSES_DIR . 'class.arm_multisite_subsite.php' ) ) {
    require_once ARM_MULTISUBSITE_CLASSES_DIR . 'class.arm_multisite_subsite.php';
}

global $armmultisubsite_api_url, $armmultisubsite_plugin_slug;

$armmultisubsite_api_url = $arm_multisubsite->armmultisubsite_getapiurl();
$armmultisubsite_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'multisubsite_check_for_plugin_update');

function multisubsite_check_for_plugin_update($checked_data) {
    global $armmultisubsite_api_url, $armmultisubsite_plugin_slug, $wp_version, $arm_multisubsite_version, $arm_multisubsite;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armmultisubsite_plugin_slug,
        'version' => $arm_multisubsite_version,
        'other_variables' => $arm_multisubsite->armmultisubsite_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMMULTISITE-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armmultisubsite_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = !empty($raw_response['body']) ? unserialize($raw_response['body']) : array();

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armmultisubsite_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armmultisubsite_plugin_slug . '/' . $armmultisubsite_plugin_slug . '.php'] = $response;

    return $checked_data;
}


add_filter('plugins_api', 'arm_multisite_plugin_api_call', 10, 3);

function arm_multisite_plugin_api_call($def, $action, $args) {
    global $armmultisubsite_plugin_slug, $armmultisubsite_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armmultisubsite_plugin_slug))
        return false;


    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armmultisubsite_plugin_slug . '/' . $armmultisubsite_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armmultisubsite_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMMULTISITE-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armmultisubsite_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', 'An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', $request->get_error_message());
    } else {
        $res = maybe_unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', 'An unknown error occurred', $request['body']);
    }

    return $res;
}

?>