<?php

if (!class_exists('arm_online_worldpay')) {

    class arm_online_worldpay {

        function __construct() {
            global $ArmWorldpay;

            if ($ArmWorldpay->is_armember_support() && $ArmWorldpay->arm_armember_version_check()) {
                global $arm_payment_gateways, $ArmWorldpay;
                $arm_payment_gateways->currency['online_worldpay'] = $this->arm_online_worldpay_currency_symbol();

                add_filter('arm_get_payment_gateways', array($this, 'arm_add_worldpay_payment_gateways'));
                add_filter('arm_get_payment_gateways_in_filters', array($this, 'arm_add_worldpay_payment_gateways'));
                add_filter('arm_change_payment_gateway_tooltip', array($this, 'arm_change_payment_gateway_tooltip_func'), 10, 3);
                add_filter('arm_filter_gateway_names', array($this, 'arm_filter_gateway_names_func'), 10);
                add_action('arm_after_payment_gateway_listing_section', array($this, 'arm_after_payment_gateway_listing_section_func'), 10, 2);
                add_filter('arm_currency_support',array($this,'arm_worldpay_currency_support'), 10, 2);
                add_filter('arm_payment_gateway_has_ccfields', array($this, 'arm_payment_gateway_has_ccfields_func'), 10, 3);
                add_filter('arm_allowed_payment_gateways', array($this, 'arm_payment_allowed_gateways'), 10, 3);
                add_filter('arm_change_pg_name_outside', array($this, 'arm_change_pg_name_for_online_worldpay'), 10, 2);
                add_action('arm_payment_related_common_message', array($this, 'arm_payment_related_common_message'), 10, 3);
                add_filter('arm_filter_cron_hook_name_outside', array($this, 'arm_filter_cron_hook_name_outside_func'), 10);
                add_filter('arm_update_new_subscr_gateway_outside', array($this, 'arm_online_worldpay_update_new_subscr_gateway'), 10);
                add_filter('arm_change_pg_name_outside', array($this, 'arm_change_pg_name_for_online_worldpay'), 10, 2);
                add_filter("arm_get_gateways_update_card_detail_btn", array($this, 'arm_get_gateways_update_card_detail_btn_func'), 10, 4);
                add_filter("arm_allow_gateways_update_card_detail", array($this, 'arm_allow_gateways_update_card_detail_func'), 10, 2);
                add_filter("arm_submit_gateways_updated_card_detail", array($this, 'arm_submit_gateways_updated_card_detail_func'), 10, 11);
                add_filter('arm_default_plan_array_filter', array($this, 'arm2_default_plan_array_filter_func'), 10, 1);
                add_filter('arm_membership_update_user_meta_from_outside', array($this, 'arm2_membership_online_worldpay_update_usermeta'), 10, 5);
                add_action('arm_membership_online_worldpay_recurring_payment', array($this, 'arm2_membership_online_worldpay_check_recurring_payment'));
                add_action('arm_payment_gateway_validation_from_setup', array($this, 'arm2_payment_gateway_form_submit_action'), 11, 4);
                add_action('arm_cancel_subscription_gateway_action', array($this, 'arm2_online_worldpay_cancel_subscription'), 10, 2);
                add_action('arm_update_user_meta_after_renew_outside', array($this, 'arm2_online_worldpay_update_meta_after_renew'), 10, 4);
                add_action('admin_enqueue_scripts', array($this, 'arm_online_worldpay_admin_enqueue_script'), 10);
                
            }
        }
        function arm_online_worldpay_admin_enqueue_script(){
            $arm_worldpay_page_array = array('arm_general_settings', 'arm_membership_setup');
            $arm_worldpay_action_array = array('payment_options', 'new_setup', 'edit_setup');
            if ($this->is_version_compatible() && isset($_REQUEST['page']) && isset($_REQUEST['action'])){
                if(in_array($_REQUEST['page'], $arm_worldpay_page_array) && in_array($_REQUEST['action'], $arm_worldpay_action_array))
                {
                    global $arm_worldpay_version;
                    wp_register_script( 'arm-admin-worldpay', ARM_WORLDPAY_URL.'/js/arm_admin_worldpay.js', array(), $arm_worldpay_version );
                    wp_enqueue_script( 'arm-admin-worldpay' );
                }
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
            if (!version_compare($this->get_armember_version(), '3.2.1', '>=') || !$this->is_armember_support()) :
                return false;
            else : 
                return true;
            endif;
        }
        function arm_worldpay_currency_support($notAllow, $currency) {
            global $arm_payment_gateways, $ARMember;
            $online_worldpay_currency = $this->arm_online_worldpay_currency_symbol();
            if (!array_key_exists($currency, $online_worldpay_currency)) {
                $notAllow[] = 'Online Worldpay';
            }
            return $notAllow;
        }

        function arm_filter_cron_hook_name_outside_func($cron_hook_array){
            $cron_hook_array[] = 'arm_membership_online_worldpay_recurring_payment';
            return $cron_hook_array;
        }

        function arm_add_worldpay_payment_gateways($default_payment_gateways) {
            global $arm_payment_gateways;
            $default_payment_gateways['online_worldpay']['gateway_name'] = 'Online Worldpay';
            return $default_payment_gateways;
        }

        function arm_change_payment_gateway_tooltip_func($titleTooltip, $gateway_name, $gateway_options) {
            if ($gateway_name == 'online_worldpay') {
                return sprintf( esc_html__("You can find Service and Client key in your online worldpay account. To get more details, Please refer this %s document %s. You can find your API Keys  %s here %s.", ARM_WORLDPAY_TXTDOMAIN ),'<a href="https://developer.worldpay.com/jsonapi/docs/api-keys" target="_blank">','</a>','<a href="https://online.worldpay.com/settings/keys" target="_blank">','</a>');
                
            }
            return $titleTooltip;
        }

        function arm_filter_gateway_names_func($pgname) {
            $pgname['online_worldpay'] = esc_html__('Online Worldpay', ARM_WORLDPAY_TXTDOMAIN);
            return $pgname;
        }

        function arm_payment_gateway_has_ccfields_func($pgHasCcFields, $gateway_name, $gateway_options) {
            if ($gateway_name == 'online_worldpay') {
                return true;
            } else {
                return $pgHasCcFields;
            }
        }

        function arm_payment_allowed_gateways($allowed_gateways, $plan_obj, $plan_options) {
            $allowed_gateways['online_worldpay'] = "1";
            return $allowed_gateways;
        }

        function arm_online_worldpay_currency_symbol() {
            $currency_symbol = array(
                'GBP' => '&pound;', 
                'EUR' => '&#128;', 
                'USD' => '&#36;', 
                'CAD' => '&#36;', 
                'DKK' => 'DKK', 
                'HKD' => '&#36;', 
                'NOK' => '&#107;&#114;', 
                'SEK' => '&#107;&#114;', 
                'SGD' => '&#36;' 
            );
            return $currency_symbol;
        }

        function arm_after_payment_gateway_listing_section_func($gateway_name, $gateway_options) {
            if (file_exists(ARM_WORLDPAY_VIEWS_DIR . '/arm_online_worldpay_settings.php')) {
                require ARM_WORLDPAY_VIEWS_DIR . '/arm_online_worldpay_settings.php';
                arm_online_worldpay_settings($gateway_name, $gateway_options);
            }
        }

        function arm_payment_related_common_message($common_messages) {
            if (file_exists(ARM_WORLDPAY_VIEWS_DIR . '/arm_online_worldpay_settings.php')) {
                require ARM_WORLDPAY_VIEWS_DIR . '/arm_online_worldpay_settings.php';
                arm_online_worldpay_common_message_settings($common_messages);
            }
        }

            
        function arm2_membership_online_worldpay_update_usermeta($posted_data, $user_id, $plan, $log_detail, $pgateway) {
            global $ARMember;
            if ($pgateway == 'online_worldpay') {
                $posted_data['arm_online_worldpay'] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
            }
            return $posted_data;
        }
       
        function arm2_default_plan_array_filter_func( $default_plan_array ) {
            $default_plan_array['arm_online_worldpay'] = '';
            return $default_plan_array;
        }

        function arm2_online_worldpay_update_meta_after_renew($user_id, $log_detail, $plan_id, $payment_gateway) {
            global $ARMember;
            if ($payment_gateway == 'online_worldpay') {
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
                    $plan_data['arm_online_worldpay'] = $pg_subsc_data;
                    update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $plan_data);
                }
            }
        }

        function arm2_membership_online_worldpay_check_recurring_payment() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_payment_gateways, $arm_subscription_plan, $arm_manage_communication, $arm_members_class, $arm_subscription_plans;
            set_time_limit(0);
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
                    $user_email_add=$usr->user_email;
                    
                    $plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true); 
                    $plan_ids = !empty($plan_ids) ? $plan_ids : array(); 
                    
                    if(!empty($plan_ids) && is_array($plan_ids)){
                        foreach($plan_ids as $plan_id){
                            
                            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                            
                            if(!empty($planData)){
                                
                                $arm_user_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
                                if($arm_user_gateway != 'online_worldpay'){ continue; }
                                $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                                $planDetail = $planData['arm_current_plan_detail'];
                                $arm_selected_payment_cycle=isset($planData['arm_payment_cycle']) ? $planData['arm_payment_cycle'] : '0';
                                $arm_subscription_plan_options=maybe_unserialize($planDetail['arm_subscription_plan_options']);
                                $arm_selected_payment_cycle_detaitl=$arm_subscription_plan_options['payment_cycles'][$arm_selected_payment_cycle];
                                $arm_plan_payment_cycle_total=$arm_selected_payment_cycle_detaitl['recurring_time'];

                                $arm_completed_recurring=isset($planData['arm_completed_recurring']) ? $planData['arm_completed_recurring'] : '0';
                                if (!empty($planDetail)) { 
                                    $plan = new ARM_Plan(0);
                                    $plan->init((object) $planDetail);
                                } else {
                                    $plan = new ARM_Plan($plan_id);
                                }
                                
                                if ($plan->is_recurring() && $user_selected_payment_mode == 'auto_debit_subscription') {
                                    
                                    $get_payment = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id,arm_transaction_id,arm_extra_vars,arm_token,arm_response_text FROM `{$payment_log_table}` WHERE `arm_plan_id` = %d AND `arm_user_id` = %d AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s ORDER BY arm_log_id DESC LIMIT 0,1", $plan_id, $user_id, 'online_worldpay', 'success'));

                                    
                                    if (empty($get_payment)) {
                                        continue;
                                    }
                                    $extra_vars = maybe_unserialize($get_payment[0]->arm_extra_vars);

                                    $gateway_options = get_option('arm_payment_gateway_settings');

                                    $pgoptions = maybe_unserialize($gateway_options);
                                    $pgoptions = $pgoptions['online_worldpay'];

                                    $payment_type = isset($extra_vars['payment_type']) ? $extra_vars['payment_type'] : '';
                                    $payment_mode = isset($extra_vars['payment_mode']) ? $extra_vars['payment_mode'] : $pgoptions['online_worldpay_payment_mode'];
                                    $is_sandbox_mode = ($payment_mode == 'sandbox') ? true : false;
                                    $arm_worldpay_service_key = ($is_sandbox_mode) ? $pgoptions['online_worldpay_sandbox_service_key'] : $pgoptions['online_worldpay_service_key'];
                                    $arm_worldpay_client_key = ($is_sandbox_mode) ? $pgoptions['online_worldpay_sandbox_client_key'] : $pgoptions['online_worldpay_client_key'];
                                    $current_plan_amount=$planDetail['arm_subscription_plan_amount'];
                                    $entry_id=get_user_meta($user_id, 'arm_entry_id', true); 

                                    
                                    $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
                                    $entry_values = $entry_data['arm_entry_value'];
                                    
                                    $tax_percentage = (isset($entry_values['tax_percentage']) && $entry_values['tax_percentage']!='') ? $entry_values['tax_percentage'] : 0;

                                    
                                    $currency = $arm_payment_gateways->arm_get_global_currency();
                                    
                                    $token_key=($planData['arm_online_worldpay']['sale_id'])? $planData['arm_online_worldpay']['sale_id']:'';
                                    $todaydate=date('Y-m-d');
                                    $arm_update_meta = false;

                                    
                                    $next_payment_flag=false;
                                    if($planData['arm_next_due_payment'] < $planData['arm_expire_plan'] && $arm_completed_recurring < $arm_plan_payment_cycle_total){
                                        $next_payment_flag=true;
                                    }else if(empty($planData['arm_expire_plan']) && "infinite" == $arm_plan_payment_cycle_total){
                                        $next_payment_flag=true;
                                    }    

                                    if(!empty($next_payment_flag)){
                                        
                                        if(isset($token_key)){
                                            
                                            if($token_key && $planData['arm_next_due_payment']<=strtotime($todaydate)){
                                                
                                                $arm_amount=$current_plan_amount;
                                                $tax_amount = 0;
                                                $extraParam=array();
                                                if($tax_percentage > 0){
                                                    $tax_amount = ($tax_percentage*$arm_amount)/100;
                                                    $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                                                    $arm_amount = $arm_amount+$tax_amount;
                                                
                                                }
                                                $extraParam['tax_amount'] = $tax_amount;
                                                $extraParam['tax_percentage'] = $tax_percentage;
                                                $arm_worldpay_argdata=array(
                                                               "token"=>$token_key,
                                                               "orderType"=>"RECURRING",
                                                               "orderDescription"=>"Recurring payment",
                                                               "amount"=>$arm_amount*100,
                                                               "currencyCode"=>$currency,
                                                               "reusable"=>true
                                                            );
                                                $arm_world_payment = $this->arm_worldpay_request_menual_recurring_order('https://api.worldpay.com', $arm_worldpay_service_key, $arm_worldpay_argdata);
                                                
                                                $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                                $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                                                if($arm_world_payment->orderCode){
                                                    $payment_data = array(
                                                        'arm_user_id' => $user_id,
                                                        'arm_plan_id' => $plan->ID,
                                                        'arm_first_name' => $user_detail_first_name,
                                                        'arm_last_name' => $user_detail_last_name,
                                                        'arm_payment_gateway' => 'online_worldpay',
                                                        'arm_payment_type' => $plan->payment_type,
                                                        'arm_payer_email' => $user_email_add,
                                                        'arm_receiver_email' => '',
                                                        'arm_transaction_id' => $arm_world_payment->orderCode,
                                                        'arm_token' => $arm_world_payment->token,
                                                        'arm_transaction_payment_type' => $plan->payment_type,
                                                        'arm_transaction_status' => ($arm_world_payment->paymentStatus == 'SUCCESS') ? 'completed' : 'pending',
                                                        'arm_payment_date' => current_time('mysql'),
                                                        'arm_amount' => $arm_amount,
                                                        'arm_currency' => $currency,
                                                        'arm_coupon_code' => '',
                                                        'arm_coupon_discount' => '',
                                                        'arm_coupon_discount_type' =>'',
                                                        'arm_extra_vars'=> maybe_serialize($extraParam),
                                                        'arm_response_text' => (!empty($arm_world_payment)) ? maybe_serialize((array) $arm_world_payment) : '',
                                                        'arm_created_date' => current_time('mysql'),
                                                        'arm_display_log' => '1',
                                                        'arm_coupon_on_each_subscriptions' =>''
                                                    );
                                                    
                                                    $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
                                                    
                                                    $arm_update_meta = true;    
                                                }else{
                                                    
                                                    $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                                }
                                            }
                                        }
                                        
                                    }

                                    if($arm_update_meta){
                                        $defaultPlanData = $arm_subscription_plans->arm_default_plan_array();
                                        $userPlanDatameta = get_user_meta($user_id, 'arm_user_plan_' . $plan_id, true);
                                        $userPlanDatameta = !empty($userPlanDatameta) ? $userPlanDatameta : array();
                                        $planData = shortcode_atts($defaultPlanData, $userPlanDatameta);
                                        
                                        $arm_next_due_payment_date = $planData['arm_next_due_payment'];
                                        
                                        if(!empty($arm_next_due_payment_date)){
                                            if(strtotime(current_time('mysql')) >= $arm_next_due_payment_date){
                                                $total_completed_recurrence = $planData['arm_completed_recurring'];
                                                
                                                $total_completed_recurrence++;
                                                $planData['arm_completed_recurring'] = $total_completed_recurrence;

                                                update_user_meta($user_id, 'arm_user_plan_'.$plan_id, $planData);
                                                $payment_cycle = $planData['arm_payment_cycle'];
                                                
                                                $arm_next_payment_date = $arm_members_class->arm_get_next_due_date($user_id, $plan_id, false, $payment_cycle);
                                                $planData['arm_next_due_payment'] = $arm_next_payment_date;
                                                update_user_meta($user_id, 'arm_user_plan_'.$plan_id, $planData); 
                                                
                                            }else{
                                                $now = current_time('mysql');
                                                $arm_last_payment_status = $wpdb->get_var($wpdb->prepare("SELECT `arm_transaction_status` FROM `" . $ARMember->tbl_arm_payment_log . "` WHERE `arm_user_id`=%d AND `arm_plan_id`=%d AND `arm_created_date`<=%s ORDER BY `arm_log_id` DESC LIMIT 0,1", $user_id, $plan_id, $now));  
                                                
                                                if(in_array($arm_last_payment_status, array('success', 'pending'))){
                                                    $total_completed_recurrence = $planData['arm_completed_recurring'];
                                                    $total_completed_recurrence++;
                                                    $planData['arm_completed_recurring'] = $total_completed_recurrence;
                                                    
                                                    update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $planData);
                                                    $payment_cycle = $planData['arm_payment_cycle'];

                                                    $arm_next_payment_date = $arm_members_class->arm_get_next_due_date($user_id, $plan_id, false, $payment_cycle);
                                                    $planData['arm_next_due_payment'] = $arm_next_payment_date;
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

                                        $user_subsdata = $planData['arm_online_worldpay'];
                                        do_action('arm_after_recurring_payment_success_outside',$user_id,$plan_id,'online_worldpay',$payment_mode,$user_subsdata);
                                    }
                                   
                                }
                            }
                        }
                    }
                }
            }
            
        }
        function arm_worldpay_request_menual_recurring_order($url,$servicekey,$args){
            $response_body='';
            if($servicekey && $args){
                $args['reusable']=true;
                $response = wp_remote_post($url.'/v1/orders', array(
                            'method' => 'POST',
                            'timeout' => 45,
                            'redirection' => 5,
                            'headers' => Array
                                (
                                    'Authorization' => $servicekey,
                                    'content-type' => 'application/json'
                                ),
                            'body' => json_encode($args),    
                            )                            
                        );
                
                $response_body = wp_remote_retrieve_body($response);    
                $response_body = json_decode($response_body);
            }
            return $response_body; 
        }
        function arm2_online_worldpay_cancel_subscription($user_id, $plan_id) {
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
            if (!empty($user_id) && $user_id != 0 && !empty($plan_id) && $plan_id != 0) {
                $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                $currency = $arm_payment_gateways->arm_get_global_currency();
                if(!empty($planData)){
                    $arm_user_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
                    if($arm_user_gateway == 'online_worldpay')
                    {
                        $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                        $planDetail = $planData['arm_current_plan_detail'];
                        
                        if (!empty($planDetail)) { 
                            $planObj = new ARM_Plan(0);
                            $planObj->init((object) $planDetail);
                        } else {
                            $planObj = new ARM_Plan($plan_id);
                        }

                        $arm_is_trial = '0';
                        $user_detail = get_userdata($user_id);
                        $payer_email = $user_detail->user_email;
                        $payment_log_table = $ARMember->tbl_arm_payment_log;
                        $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_extra_vars,arm_payer_email FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'online_worldpay'));
                        
                        $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                        $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);

                        if (!empty($transaction)) {
                            $extra_var = maybe_unserialize($transaction->arm_extra_vars);

                            $payer_email = $transaction->arm_payer_email;
                            $payment_type = $extra_var['payment_type'];
                            $payment_mode = $extra_var['payment_mode'];

                            $gateway_options = get_option('arm_payment_gateway_settings');
                            $pgoptions = maybe_unserialize($gateway_options);
                            $pgoptions = $pgoptions['online_worldpay'];

                            $is_sandbox_mode = ( $payment_mode == 'sandbox' ) ? true : false;
                                
                                if($user_selected_payment_mode == 'auto_debit_subscription'){
                                    
                                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                        $payment_data = array(
                                            'arm_user_id' => $user_id,
                                            'arm_plan_id' => $plan_id,
                                            'arm_first_name'=>$user_detail_first_name,
                                            'arm_last_name'=>$user_detail_last_name,
                                            'arm_payment_gateway' => 'online_worldpay',
                                            'arm_payment_type' => 'subscription',
                                            'arm_token' => '',
                                            'arm_payer_email' => $payer_email,
                                            'arm_receiver_email' => '',
                                            'arm_transaction_id' => '',
                                            'arm_transaction_payment_type' => $payment_type,
                                            'arm_transaction_status' => 'canceled',
                                            'arm_payment_date' => current_time('mysql'),
                                            'arm_amount' => 0,
                                            'arm_currency' => $currency,
                                            'arm_coupon_code' => '',
                                            'arm_is_trial' => $arm_is_trial,
                                            'arm_response_text' =>'',
                                            'arm_created_date' => current_time('mysql')
                                        );
                                        $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
                                        return;
                                    
                                }else{
                                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                    $payment_data = array(
                                        'arm_user_id' => $user_id,
                                        'arm_plan_id' => $plan_id,
                                        'arm_first_name'=>$user_detail_first_name,
                                        'arm_last_name'=>$user_detail_last_name,
                                        'arm_payment_gateway' => 'online_worldpay',
                                        'arm_payment_type' => 'subscription',
                                        'arm_payer_email' => $payer_email,
                                        'arm_receiver_email' => '',
                                        'arm_transaction_id' => '',
                                        'arm_token' => '',
                                        'arm_transaction_payment_type' => 'subscription',
                                        'arm_payment_mode' => 'manual_subscription',
                                        'arm_transaction_status' => 'canceled',
                                        'arm_payment_date' => current_time('mysql'),
                                        'arm_amount' => 0,
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
        function arm_get_formatted_response($response) {
            if (!$response)
                return false;

            $httpParsedResponseAr = array();
            $httpResponseAr = explode("&", $response);
            foreach ($httpResponseAr as $i => $value) {
                $tmpAr = explode("=", urldecode($value));
                if (count($tmpAr) > 1) {
                    $httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
                }
            }

            return $httpParsedResponseAr;
        }

        function arm_online_worldpay_update_new_subscr_gateway($payment_gateways = array()) {
            array_push($payment_gateways, 'online_worldpay');
            return $payment_gateways;
        }

        function arm2_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $arm_members_class, $paid_trial_stripe_payment_done;
            $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
            $currency = $arm_payment_gateways->arm_get_global_currency();
            if ($payment_gateway == 'online_worldpay' && isset($all_payment_gateways['online_worldpay']) && !empty($all_payment_gateways['online_worldpay'])) {
                $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
                if (!empty($entry_data) && !empty($posted_data[$payment_gateway])) {
                    $user_email_add = $entry_data['arm_entry_email'];
                    $user_id = $entry_data['arm_user_id'];
                    $form_id = $entry_data['arm_form_id'];
                    $first_name = $posted_data['first_name'];
                    $last_name = $posted_data['last_name'];
                    $armform = new ARM_Form('id', $form_id);
                    $entry_values = $entry_data['arm_entry_value'];
                    $payment_cycle = $entry_values['arm_selected_payment_cycle']; 
                    $tax_percentage = (isset($entry_values['tax_percentage']) && $entry_values['tax_percentage']!='') ? $entry_values['tax_percentage'] : 0;
                    $arm_user_old_plan = (isset($entry_values['arm_user_old_plan']) && !empty($entry_values['arm_user_old_plan'])) ? explode(",",$entry_values['arm_user_old_plan']) : array();
                    $setup_id = (isset($entry_values['setup_id']) && !empty($entry_values['setup_id'])) ? $entry_values['setup_id'] : 0 ;
                    
                    
                    $plan_id = (!empty($posted_data['subscription_plan'])) ? $posted_data['subscription_plan'] : 0;
                    if ($plan_id == 0) {
                        $plan_id = (!empty($posted_data['_subscription_plan'])) ? $posted_data['_subscription_plan'] : 0;
                    }
                    
                    $plan = new ARM_Plan($plan_id);
                    
                    $plan_payment_mode_ = !empty($posted_data['arm_payment_mode']['online_worldpay']) ? $posted_data['arm_payment_mode']['online_worldpay'] : 'both';
                    $plan_payment_mode = "manual_subscription";
                    if ($plan_payment_mode_ == 'both') {
                        $plan_payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : "manual_subscription";
                    } else {
                        $plan_payment_mode = $plan_payment_mode_;
                    }
                    
                    $plan_action = 'new_subscription';
                    $oldPlanIdArray = (isset($posted_data['old_plan_id']) && !empty($posted_data['old_plan_id'])) ? explode(",", $posted_data['old_plan_id']) : 0;
                    if (!empty($oldPlanIdArray)) {
                        if (in_array($plan_id, $oldPlanIdArray)) {
                            $plan_action = 'renew_subscription';
                            $is_recurring_payment = $arm_subscription_plans->arm_is_recurring_payment_of_user($user_id, $plan_id, $plan_payment_mode);
                            if($is_recurring_payment){
                                $plan_action = 'recurring_payment';
                                $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                                $oldPlanDetail = $planData['arm_current_plan_detail'];
                                $user_subsdata = $planData['arm_online_worldpay'];
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
                    
                    $custom_var = $entry_id . '|' . $user_email_add . '|' . $form_id . '|' . $plan_payment_type;

                    if($plan->is_recurring()) {
                        $recurring_data = $plan->prepare_recurring_data($payment_cycle);
                        $amount = $recurring_data['amount'];
                    } else {
                        $amount = !empty($plan->amount) ? $plan->amount : 0;
                    }
                    $amount = str_replace(",", "", $amount);
                    $amount = number_format((float)$amount, 2, '.','');
                    
                    $autho_options = $all_payment_gateways['online_worldpay'];

                    $current_payment_gateway = (isset($posted_data['payment_gateway'])) ? $posted_data['payment_gateway'] : '';
                    if ($current_payment_gateway == '') {
                        $current_payment_gateway = (isset($posted_data['_payment_gateway'])) ? $posted_data['_payment_gateway'] : '';
                    }
                    $arm_coupon_on_each_subscriptions = 0;

                    $online_worldpay_card_detail = $posted_data[$current_payment_gateway];

                    $card_holder_name = $online_worldpay_card_detail['card_holder_name'];
                    $card_number = $online_worldpay_card_detail['card_number'];
                    $exp_month = $online_worldpay_card_detail['exp_month'];
                    $exp_year = $online_worldpay_card_detail['exp_year'];
                    $cvc = $online_worldpay_card_detail['cvc'];
                    $payment_mode = $payment_mode_method = $payment_gateway_options['online_worldpay_payment_mode'];
                    $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;
                    
                    $arm_worldpay_service_key = ( $is_sandbox_mode ) ? $payment_gateway_options['online_worldpay_sandbox_service_key'] : $payment_gateway_options['online_worldpay_service_key'];
                    $arm_worldpay_client_key = ( $is_sandbox_mode ) ? $payment_gateway_options['online_worldpay_sandbox_client_key'] : $payment_gateway_options['online_worldpay_client_key'];
                   
                    $arm_online_worldpay_enable_debug_mode = isset($payment_gateway_options['enable_debug_mode']) ? $payment_gateway_options['enable_debug_mode'] : 0;

                    $discount_amt=$amount;

                    $coupon_amount = $arm_coupon_discount = 0;
                    $arm_coupon_discount_type = '';
                    $extraParam = array();
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
                            $extraParam['coupon'] = array(
                                'coupon_code' => $posted_data['arm_coupon_code'],
                                'amount' => $coupon_amount,
                            );
                        }
                        if($plan->is_recurring()) {
                            $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : 0;
                        }
                    } else {
                        $posted_data['arm_coupon_code'] = '';
                    }
                    
                    $arm_worldpay_argdata=array(
                            'paymentMethod'=>array(
                                "type"=>"Card",
                                "name"=>$card_holder_name,
                                "expiryMonth"=>$exp_month,
                                "expiryYear"=>$exp_year,
                                "cardNumber"=>$card_number,
                                "cvc"=>$cvc,
                                "issueNumber"=>"1"
                            ),
                            "orderDescription"=>"Order ".$entry_id,
                            "amount"=>$discount_amt*100,
                            "currencyCode"=>$currency,
                            "customerIdentifiers"=> array('us_email'=>$user_email_add,'arm_form_id'=>$form_id,'arm_payment_type'=>$plan_payment_type,'arm_entry_id'=>"arm_worldpay_recurring_token_".$entry_id)
                        );
                    $arm_is_trial=0;
                    $extraParam['plan_amount'] =$amount;
                    $extraParam['paid_amount'] =$amount;
                    $extraParam['payment_type'] = $payment_type;
                    $extraParam['payment_mode'] = ( $is_sandbox_mode ) ? "sandbox" : 'live';
                    //customer payment
                    if($is_recurring && $plan_payment_mode == 'auto_debit_subscription')
                    {
                        $recurring_data = $plan->prepare_recurring_data($payment_cycle);
                            $recur_period = $recurring_data['period'];
                        $recur_interval = $recurring_data['interval'];
                        $recur_cycles = $recurring_data['cycles'];

                        if ($recur_cycles == '') {
                            $recur_cycles = 0;
                        }
                        if ($recur_cycles > 0 && $plan_action == 'new_subscription' && !$plan->has_trial_period()) {
                            $recur_cycles = $recur_cycles - 1;
                        }
                        if(!empty($coupon_amount)) {
                            $amount = $amount - $coupon_amount;
                        }
                        $tax_amount = 0;
                        if($tax_percentage > 0){
                            $tax_amount = ($tax_percentage*$amount)/100;
                            $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                            $amount = $amount+$tax_amount;
                        
                        }
                        $extraParam['tax_amount'] = $tax_amount;
                        $auth_amount = 0;
                        /* Trial Period Options */
                        $is_trial = false;
                        $trial_interval = 0;
                        $allow_trial = true;
                        if (is_user_logged_in()) {
                            $user_id = get_current_user_id();
                            $user_plan = get_user_meta($user_id, 'arm_user_plan_ids', true);

                            if (!empty($user_plan)) {
                                $allow_trial = false;
                            }
                        }
                        if ($plan->has_trial_period() && $allow_trial) {
                            $is_trial = true;
                            $arm_is_trial = '1';
                            
                            
                            $trial_amount = $recurring_data['trial']['amount'];
                            $trial_period = $recurring_data['trial']['period'];
                            $trial_interval = $recurring_data['trial']['interval'];
                            
                            $extraParam['trial'] = array(
                                'amount' => $trial_amount,
                                'period' => $trial_period,
                                'interval' => $trial_interval,
                            );
                            if($tax_percentage > 0){
                                $tax_amount = ($tax_percentage*$trial_amount)/100;
                                $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                                
                                $trial_amount = $trial_amount+$tax_amount;
                            }
                        } else {
                            $trial_amount = $amount;
                        }
                        
                        if (!empty($coupon_amount) && $coupon_amount > 0) {
                            $trial_amount = $discount_amt;
                            if (!$is_trial) {
                                $recur_cycles = ($recur_cycles > 1) ? $recur_cycles - 1 : 1;
                                $plan_action = 'new_subscription';
                                $trial_interval = $recur_interval;
                                $trial_period = $recur_period;
                            }
                            if($tax_percentage > 0){
                                $tax_amount = ($tax_percentage*$trial_amount)/100;
                                $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                                
                                $trial_amount = $trial_amount+$tax_amount;
                            }
                        }
                        $extraParam['tax_amount'] = $tax_amount;
                        $auth_amount = $trial_amount*100;
                        $arm_worldpay_argdata['amount']=$auth_amount;
                        
                        $card_data=array(
                                        "name"=>$card_holder_name,
                                        "expiremonth"=>$exp_month,
                                        "expireyear"=>$exp_year,
                                        "cardnumber"=>$card_number,
                                        "cvc"=>$cvc
                                    );
                        $token_response=$this->arm_worldpay_create_card_token('https://api.worldpay.com',$arm_worldpay_client_key,$card_data);
                        if(isset($token_response->token) && $token_response->token != ''){
                            $arm_token_key=$token_response->token;
                        }    
                        if(isset($token_response->token) && $token_response->token != ''){
                            $arm_world_payment = $this->arm_worldpay_request_create_recurring_order('https://api.worldpay.com', $arm_worldpay_service_key, $arm_worldpay_argdata);
                        }else{
                            $arm_world_payment=$token_response;
                        }
                        
                        
                    }else if($is_recurring && $plan_payment_mode == 'manual_subscription'){

                        $recurring_data = $plan->prepare_recurring_data($payment_cycle);
                        $recur_period = $recurring_data['period'];
                        $recur_interval = $recurring_data['interval'];
                        $recur_cycles = $recurring_data['cycles'];
                        /* Trial Period Options */
                        $is_trial = false;
                        $trial_interval = 0;
                        $allow_trial = true;
                        $arm_is_trial = '0';
                        if (is_user_logged_in()) {
                            $user_id = get_current_user_id();
                            $user_plan = get_user_meta($user_id, 'arm_user_plan_ids', true);

                            if (!empty($user_plan)) {
                                $allow_trial = false;
                            }
                        }
                        if (!empty($recurring_data['trial']) && $allow_trial) {
                            $is_trial = true;
                            $arm_is_trial = '1';
                            $trial_amount = $recurring_data['trial']['amount'];
                            $trial_period = $recurring_data['trial']['period'];
                            $trial_interval = $recurring_data['trial']['interval'];

                            $extraParam['trial'] = array(
                                'amount' => $trial_amount,
                                'period' => $trial_period,
                                'interval' => $trial_interval,
                            );
                        } else {
                            $trial_amount = $amount;
                        }
                        
                        if (!empty($coupon_amount) && $coupon_amount > 0) {
                            $trial_amount = $discount_amt;
                            if (!$is_trial) {
                                $recur_cycles = ($recur_cycles > 1) ? $recur_cycles - 1 : 1;
                                $plan_action = 'new_subscription';
                                $trial_interval = $recur_interval;
                                $trial_period = $recur_period;
                            }
                        }
                        
                        $trial_amount = str_replace(",", "", $trial_amount);
                        $tax_amount = 0;
                        if($tax_percentage>0){
                            $tax_amount = ($trial_amount*$tax_percentage)/100;
                            $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                            $trial_amount = $trial_amount + $tax_amount;
                        }
                        $extraParam['tax_amount'] = $tax_amount;
                        $trial_amount = number_format((float)$trial_amount, 2, '.','');

                        $arm_worldpay_argdata['amount']=$trial_amount*100;
                        
                        $card_data=array(
                                    "name"=>$card_holder_name,
                                    "expiremonth"=>$exp_month,
                                    "expireyear"=>$exp_year,
                                    "cardnumber"=>$card_number,
                                    "cvc"=>$cvc
                                    );
                        $token_response=$this->arm_worldpay_create_card_token('https://api.worldpay.com',$arm_worldpay_client_key,$card_data);
                        if(isset($token_response->token) && $token_response->token != ''){
                            $arm_token_key=$token_response->token;
                        }    
                        if(isset($token_response->token) && $token_response->token != ''){
                            $arm_world_payment = $this->arm_worldpay_request_create_recurring_order('https://api.worldpay.com', $arm_worldpay_service_key, $arm_worldpay_argdata);
                            
                        }else{
                            $arm_world_payment=$token_response;
                        }
                    }else{

                        $auth_amount = $discount_amt;
                        if (!empty($coupon_amount) && $coupon_amount > 0) {
                            $auth_amount = $discount_amt;
                        }
                        if(isset($auth_amount)){
                            $auth_amount = str_replace(",", "", $auth_amount);
                        }
                        $tax_amount = 0;
                        if($tax_percentage > 0){
                            $tax_amount = ($tax_percentage*$auth_amount)/100;
                            $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                            $auth_amount = $auth_amount+$tax_amount;
                        }
                        $extraParam['tax_amount'] = $tax_amount;
                        $auth_amount = number_format((float)$auth_amount, 2, '.','');

                        $arm_worldpay_argdata['amount']=$auth_amount*100;

                        $arm_world_payment = $this->arm_worldpay_request_create_order('https://api.worldpay.com', $arm_worldpay_service_key, $arm_worldpay_argdata);
                        
                    }
                    $extraParam['paid_amount'] = $trial_amount;
                    $extraParam['tax_percentage'] = $tax_percentage;
                    
                    if (isset($arm_world_payment->orderCode))
                    {
                        $entry_values['payment_done'] = '1';
                        $entry_values['arm_entry_id'] = $entry_id;
                        $entry_values['arm_update_user_from_profile'] = 0;
                        if (is_user_logged_in()) {
                            $user_id = get_current_user_id();
                            $user_plan = get_user_meta($user_id, 'arm_user_plan_ids', true);

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

                        $arm_amount=$arm_worldpay_argdata['amount']/100;
                        $payment_data_onetime = array(
                            'arm_user_id' => intval($user_id),
                            'arm_first_name' => $arm_first_name,
                            'arm_last_name' => $arm_last_name,
                            'arm_plan_id' => intval($plan->ID),
                            'arm_payment_gateway' => sanitize_text_field('online_worldpay'),
                            'arm_payment_type' => sanitize_text_field($plan->payment_type),
                            'arm_payer_email' => sanitize_email($user_email_add),
                            'arm_receiver_email' => '',
                            'arm_transaction_id' => $arm_world_payment->orderCode,
                            'arm_token' => $arm_token_key,
                            'arm_transaction_payment_type' => sanitize_text_field($plan->payment_type),
                            'arm_transaction_status' => ($arm_world_payment->paymentStatus == 'SUCCESS') ? sanitize_text_field('completed') : sanitize_text_field('pending'),
                            'arm_payment_mode' => $plan_payment_mode,
                            'arm_payment_date' => current_time('mysql'),
                            'arm_amount' => $arm_amount,
                            'arm_currency' => $currency,
                            'arm_coupon_code' => isset($posted_data['arm_coupon_code']) ? $posted_data['arm_coupon_code'] : '',
                            'arm_coupon_discount' => $arm_coupon_discount,
                            'arm_coupon_discount_type' => sanitize_text_field($arm_coupon_discount_type),
                            'arm_response_text' => (!empty($arm_world_payment)) ? maybe_serialize((array) $arm_world_payment) : '',
                            'arm_is_trial'=> $arm_is_trial,
                            'arm_extra_vars'=> maybe_serialize($extraParam),
                            'arm_created_date' => current_time('mysql'),
                            'arm_display_log' => '1',
                            'arm_coupon_on_each_subscriptions' => $arm_coupon_on_each_subscriptions
                        );

                        
                        $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data_onetime);
                        if($plan_action=='recurring_payment')
                        {
                            $user_subsdata = get_user_meta($user_id, 'arm_worldpay_' . $plan_id, true);
                            $payment_mode = get_user_meta($user_id,'arm_selected_payment_mode',true);
                            do_action('arm_after_recurring_payment_success_outside',$user_id,$plan->ID,'molie',$payment_mode,$user_subsdata);
                        }
                        $payment_done = array();

                        if ($payment_log_id) {
                            $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                            if($arm_manage_coupons->isCouponFeature && isset($posted_data['arm_coupon_code']) && !empty($posted_data['arm_coupon_code']) && !empty($arm_coupon_on_each_subscriptions)) {
                                    $payment_done["coupon_on_each"] = TRUE;
                                    $payment_done["trans_log_id"] = $payment_log_id;
                            }
                            $paid_trial_world_payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id, 'gateway' => 'online_worldpay');

                            return $payment_done;
                        }else{
                            $err_msg = $arm_global_settings->common_message['arm_payment_fail_worldpay'];
                            $err_msg = (!empty($err_msg)) ? $err_msg : esc_html__('Sorry something went wrong while processing payment with Worldpay', 'ARM_WORLDPAY_TXTDOMAIN');
                            return $payment_done=array('status' => FALSE, 'error' => $err_msg);
                        } 
                        
                    } else {

                        $arm_help_link = sprintf( esc_html__( '%sClick Here%s', ARM_WORLDPAY_TXTDOMAIN ), '<a href="https://developer.worldpay.com/jsonapi/api#http-response-codes-used" target="_blank">','</a>');
                        $actual_error ='';
                        
                        if(isset($arm_world_payment->httpStatusCode)){
                            $actual_error = $arm_world_payment->httpStatusCode.' '.$arm_world_payment->customCode.' '.$arm_world_payment->message.' '.$arm_help_link;
                        }else{
                            $actual_error = json_encode($arm_world_payment);
                        }
                        $err_msg = $arm_global_settings->common_message['arm_payment_fail_worldpay'];
                        $err_msg = (!empty($err_msg)) ? $err_msg : esc_html__('Sorry something went wrong while processing payment with Worldpay', ARM_WORLDPAY_TXTDOMAIN);
                        $actualmsg = ($arm_online_worldpay_enable_debug_mode == '1') ? $actual_error : $err_msg;
                        return $payment_done=array('status' => FALSE, 'error' => $actualmsg);
                    }
                    
                } 
            } 
        }
        function arm_worldpay_request_create_recurring_order($url,$servicekey,$args){
            $response_body='';
            if($servicekey && $args){
                $args['reusable']=true;
                $args['orderType']="RECURRING";
                $response = wp_remote_post($url.'/v1/orders', array(
                            'method' => 'POST',
                            'timeout' => 45,
                            'redirection' => 5,
                            'headers' => Array
                                (
                                    'Authorization' => $servicekey,
                                    'content-type' => 'application/json'
                                ),
                            'body' => json_encode($args),    
                            )                            
                        );
                
                $response_body = wp_remote_retrieve_body($response);    
                $response_body = json_decode($response_body);
            }
            return $response_body; 
        }
        function arm_worldpay_request_create_order($url,$servicekey,$args){
            $response_body='';
            if($servicekey && $args){
                $args['orderType']='ECOM';
                $response = wp_remote_post($url.'/v1/orders', array(
                            'method' => 'POST',
                            'timeout' => 45,
                            'redirection' => 5,
                            'headers' => Array
                                (
                                    'Authorization' => $servicekey,
                                    'content-type' => 'application/json'
                                ),
                            'body' => json_encode($args),    
                            )                            
                        );
                
                $response_body = wp_remote_retrieve_body($response);    
                $response_body = json_decode($response_body);
            }
            return $response_body;
        }
        function arm_worldpay_delete_card_token($url,$servicekey,$token){
            $response_body='';
            if($servicekey && $url && $token){
                $response = wp_remote_post($url.'/v1/tokens/'.$token, array(
                            'method' => 'DELETE',
                            'timeout' => 45,
                            'redirection' => 5,
                            'headers' => Array
                                (
                                    'Authorization' => $servicekey,
                                    'content-type' => 'application/json'
                                ),
                            )                            
                        );
                if($response['response']['code']==200){
                    $response_body=true;
                }
            }
            return $response_body;
        }
        function arm_worldpay_create_card_token($url,$clientkey,$catddetail){
            $response_body='';
            if($clientkey && $url){
                $args=array(
                        "clientKey"=>$clientkey,
                        "reusable"=>true,
                        "paymentMethod"=>array(
                                        "name"=>$catddetail['name'],
                                        "expiryMonth"=>$catddetail['expiremonth'],
                                        "expiryYear"=>$catddetail['expireyear'],
                                        "issueNumber"=>1,
                                        "startMonth"=>date('m'),
                                        "startYear"=>date('Y'),
                                        "cardNumber"=>$catddetail['cardnumber'],
                                        "type"=>"Card",
                                        "cvc"=>$catddetail['cvc']
                                        )
                    );
                $response = wp_remote_post($url.'/v1/tokens', array(
                            'method' => 'POST',
                            'timeout' => 45,
                            'redirection' => 5,
                            'headers' => Array
                                (
                                    'Authorization' => $clientkey,
                                    'content-type' => 'application/json'
                                ),
                            'body' => json_encode($args),
                            )                            
                        );
                
                $response_body = wp_remote_retrieve_body($response);    
                $response_body = json_decode($response_body);
            }
            return $response_body;
        }
        function arm_change_pg_name_for_online_worldpay($pgname, $pg) {
            if ($pg == 'online_worldpay') {
                return esc_html__('Online Worldpay', ARM_WORLDPAY_TXTDOMAIN);
            }
            return $pgname;
        }

        function arm_get_gateways_update_card_detail_btn_func($response, $planData, $user_plan_id, $update_card_text) {
            if($planData["arm_user_gateway"] == "online_worldpay") {
                $response = '<div class="arm_cm_update_btn_div"><button type="button" class= "arm_update_card_button arm_update_card_button_style" data-plan_id="' . $user_plan_id . '">' . $update_card_text . '</button></div>';
            }
            return $response;
        }

        function arm_allow_gateways_update_card_detail_func($return, $arm_user_paymeant_gateway) {
            if($arm_user_paymeant_gateway == "online_worldpay") {
                $return = true;
            }
            return $return;
        }

        function arm_submit_gateways_updated_card_detail_func($err_msg, $success_msg, $arm_user_payment_gateway, $pg_options, $card_holder_name, $card_number, $exp_month, $exp_year, $planData, $response,$cvc) {
            if($arm_user_payment_gateway=='online_worldpay' && is_user_logged_in()) {
                if(!empty($planData['arm_current_plan_detail']['arm_subscription_plan_id']))
                {
                    $arm_plan_id = $planData['arm_current_plan_detail']['arm_subscription_plan_id'];

                    $arm_user_id = get_current_user_id();
                    $planData = get_user_meta($arm_user_id, 'arm_user_plan_' . $arm_plan_id, true);
                    $arm_user_payment_gateway = $planData['arm_user_gateway'];
                    $arm_user_payment_mode = $planData['arm_payment_mode'];
                    if($arm_user_payment_mode=='auto_debit_subscription' && $arm_user_payment_gateway=='online_worldpay')
                    {

                        $is_sandbox_mode = ($pg_options["online_worldpay_payment_mode"] == 'sandbox') ? true : false;
                        
                        $arm_worldpay_service_key = ($is_sandbox_mode) ? $pg_options['online_worldpay_sandbox_service_key'] : $pg_options['online_worldpay_service_key'];
                        $arm_worldpay_client_key = ($is_sandbox_mode) ? $pg_options['online_worldpay_sandbox_client_key'] : $pg_options['online_worldpay_client_key'];
                        
                        $arm_online_worldpay_enable_debug_mode = isset($pg_options['enable_debug_mode']) ? $pg_options['enable_debug_mode'] : 0;
                        if(isset($planData['arm_online_worldpay']['sale_id']) && $planData['arm_online_worldpay']['sale_id'] !=''){
                            $delete_token_response=$this->arm_worldpay_delete_card_token('https://api.worldpay.com',$arm_worldpay_service_key,$planData['arm_online_worldpay']['sale_id']);    
                            if($delete_token_response==true){
                                $card_data=array(
                                    "name"=>$card_holder_name,
                                    "expiremonth"=>$exp_month,
                                    "expireyear"=>$exp_year,
                                    "cardnumber"=>$card_number,
                                    "cvc"=>$cvc
                                    );
                                $token_response=$this->arm_worldpay_create_card_token('https://api.worldpay.com',$arm_worldpay_client_key,$card_data);
                                
                                if(isset($token_response->token) && $token_response->token != ''){
                                    $planData['arm_online_worldpay']['sale_id']=$token_response->token;
                                    update_user_meta($arm_user_id, 'arm_user_plan_' . $arm_plan_id, $planData);
                                    return $response = array('status' => 'success', 'type' => 'message', 'message' => $success_msg);
                                }else{
                                    $actual_error = maybe_serialize($token_response);
                                    $actual_error = !empty($actual_error) ? $actual_error : '';
                                    $actualmsg = ($arm_online_worldpay_enable_debug_mode == '1') ? $actual_error : $err_msg;
                                    return $response = array('status' => 'error', 'type' => 'message', 'message' => $actualmsg);
                                }    
                            }                            
                        }
                       
                        
                    }
                }
            }
            return $response;
        }
    }
}

global $ARMemberWorldpay;
$ARMemberWorldpay = new arm_online_worldpay();