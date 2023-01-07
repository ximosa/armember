<?php

if (!class_exists('arm_paypal_pro')) {

    class arm_paypal_pro {

        function __construct() {
            global $ArmPaypalPro;

            if ($ArmPaypalPro->is_armember_support() && $ArmPaypalPro->arm_armember_version_check()) {
                global $arm_payment_gateways, $ArmPaypalPro;
                $arm_payment_gateways->currency['paypal_pro'] = $this->arm_paypal_pro_currency_symbol();

                add_filter('arm_get_payment_gateways', array(&$this, 'arm_add_paypalpro_payment_gateways'));
                add_filter('arm_get_payment_gateways_in_filters', array(&$this, 'arm_add_paypalpro_payment_gateways'));

                add_filter('arm_change_payment_gateway_tooltip', array(&$this, 'arm_change_payment_gateway_tooltip_func'), 10, 3);

                add_filter('arm_filter_gateway_names', array(&$this, 'arm_filter_gateway_names_func'), 10);

                add_action('arm_after_payment_gateway_listing_section', array(&$this, 'arm_after_payment_gateway_listing_section_func'), 10, 2);

                add_filter('arm_currency_support',array(&$this,'arm_paypalpro_currency_support'), 10, 2);

                add_filter('arm_payment_gateway_has_ccfields', array(&$this, 'arm_payment_gateway_has_ccfields_func'), 10, 3);
                add_filter('arm_allowed_payment_gateways', array(&$this, 'arm_payment_allowed_gateways'), 10, 3);
                add_filter('arm_change_pg_name_outside', array(&$this, 'arm_change_pg_name_for_paypal_pro'), 10, 2);
                add_action('arm_payment_related_common_message', array(&$this, 'arm_payment_related_common_message'), 10, 3);

                //add_action('arm_membership_addon_crons', array(&$this, 'arm_membership_paypal_pro_cron'), 10);
                
                add_filter('arm_filter_cron_hook_name_outside', array(&$this, 'arm_filter_cron_hook_name_outside_func'), 10);

                add_filter('arm_update_new_subscr_gateway_outside', array(&$this, 'arm_paypal_pro_update_new_subscr_gateway'), 10);

                add_filter('arm_change_pg_name_outside', array(&$this, 'arm_change_pg_name_for_paypal_pro'), 10, 2);

                add_filter("arm_get_gateways_update_card_detail_btn", array(&$this, 'arm_get_gateways_update_card_detail_btn_func'), 10, 4);

                add_filter("arm_allow_gateways_update_card_detail", array(&$this, 'arm_allow_gateways_update_card_detail_func'), 10, 2);

                add_filter("arm_submit_gateways_updated_card_detail", array(&$this, 'arm_submit_gateways_updated_card_detail_func'), 10, 10);
                
                if(version_compare($ArmPaypalPro->arm_get_armember_version(), '2.0', '>=')){
                    add_filter('arm_default_plan_array_filter', array(&$this, 'arm2_default_plan_array_filter_func'), 10, 1);
                    
                    add_filter('arm_membership_update_user_meta_from_outside', array(&$this, 'arm2_membership_paypal_pro_update_usermeta'), 10, 5);
                    
                    add_action('arm_membership_payflow_pro_recurring_payment', array(&$this, 'arm2_membership_payflow_pro_check_recurring_payment'));
                    
                    add_action('arm_payment_gateway_validation_from_setup', array(&$this, 'arm2_payment_gateway_form_submit_action'), 11, 4);
                    
                    add_action('arm_cancel_subscription_gateway_action', array(&$this, 'arm2_paypal_pro_cancel_subscription'), 10, 2);
                    
                    add_action('arm_update_user_meta_after_renew_outside', array(&$this, 'arm2_paypal_pro_update_meta_after_renew'), 10, 4);
                }
                else
                {
                    add_action('arm_payment_gateway_validation_from_setup', array(&$this, 'arm_payment_gateway_form_submit_action'), 11, 4);
                    
                    add_filter('arm_membership_update_user_meta_from_outside', array(&$this, 'arm_membership_paypal_pro_update_usermeta'), 10, 5);
                    
                    add_action('arm_membership_payflow_pro_recurring_payment', array(&$this, 'arm_membership_payflow_pro_check_recurring_payment'));
                    
                    add_action('arm_update_user_meta_after_renew_outside', array(&$this, 'arm_paypal_pro_update_meta_after_renew'));
                    
                    add_action('arm_cancel_subscription_gateway_action', array(&$this, 'arm_paypal_pro_cancel_subscription'), 10, 2);
                }
            }
        }
        
        function arm_paypalpro_currency_support($notAllow, $currency) {
            global $arm_payment_gateways, $ARMember;
            $paypal_pro_currency = $this->arm_paypal_pro_currency_symbol();
            if (!array_key_exists($currency, $paypal_pro_currency)) {
                $notAllow[] = 'paypal pro';
            }
            return $notAllow;
        }

        function arm_filter_cron_hook_name_outside_func($cron_hook_array){
            $cron_hook_array[] = 'arm_membership_payflow_pro_recurring_payment';
            return $cron_hook_array;
        }

        function arm_add_paypalpro_payment_gateways($default_payment_gateways) {
            global $arm_payment_gateways;
            $default_payment_gateways['paypal_pro']['gateway_name'] = 'Paypal Pro (Payflow Pro)';
            return $default_payment_gateways;
        }

        function arm_change_payment_gateway_tooltip_func($titleTooltip, $gateway_name, $gateway_options) {
            return $titleTooltip;
        }

        function arm_filter_gateway_names_func($pgname) {
            $pgname['paypal_pro'] = __('Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);
            return $pgname;
        }

        function arm_payment_gateway_has_ccfields_func($pgHasCcFields, $gateway_name, $gateway_options) {
            if ($gateway_name == 'paypal_pro') {
                return true;
            } else {
                return $pgHasCcFields;
            }
        }

        function arm_payment_allowed_gateways($allowed_gateways, $plan_obj, $plan_options) {
            $allowed_gateways['paypal_pro'] = "1";
            return $allowed_gateways;
        }

        function arm_paypal_pro_currency_symbol() {
            $currency_symbol = array(
                'USD' => '$',
                'AUD' => '$',
                'BRL' => 'R$',
                'CAD' => '$',
                'CZK' => '&#75;&#269;',
                'DKK' => '&#107;&#114;',
                'EUR' => '&#128;',
                'HKD' => '&#20803;',
                'HUF' => '&#70;&#116;',
                'ILS' => '&#8362;',
                'JPY' => '&#165;',
                'MYR' => '&#82;&#77;',
                'MXN' => '&#36;',
                'NOK' => '&#107;&#114;',
                'NZD' => '&#36;',
                'PHP' => '&#80;&#104;&#11;',
                'PLN' => '&#122;&#322;',
                'GBP' => '&#163;',
                'SGD' => '&#36;',
                'SEK' => '&#107;&#114;',
                'CHF' => '&#67;&#72;&#70;',
                'TWD' => '&#36;',
                'THB' => '&#3647;'
            );
            //'RUB' => '&#1088;&#1091;',
            //'TRY' => '&#89;&#84;&#76;',
            return $currency_symbol;
        }

        function arm_after_payment_gateway_listing_section_func($gateway_name, $gateway_options) {
            if (file_exists(ARM_PAYPALPRO_VIEWS_DIR . '/arm_paypal_pro_settings.php')) {
                require ARM_PAYPALPRO_VIEWS_DIR . '/arm_paypal_pro_settings.php';
                arm_paypal_pro_settings($gateway_name, $gateway_options);
            }
        }

        function arm_payment_related_common_message($common_messages) {
            if (file_exists(ARM_PAYPALPRO_VIEWS_DIR . '/arm_paypal_pro_settings.php')) {
                require ARM_PAYPALPRO_VIEWS_DIR . '/arm_paypal_pro_settings.php';
                arm_paypal_pro_common_message_settings($common_messages);
            }
        }

        function arm_membership_paypal_pro_update_usermeta($posted_data, $user_id, $plan, $log_detail, $pgateway) {
            if ($pgateway == 'paypal_pro') {
                $posted_data['arm_paypal_pro_' . $plan->ID] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
            }
            return $posted_data;
        }
        
        function arm2_membership_paypal_pro_update_usermeta($posted_data, $user_id, $plan, $log_detail, $pgateway) {
            if ($pgateway == 'paypal_pro') {
                $posted_data['arm_paypal_pro'] = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
            }
            return $posted_data;
        }
        
        
        /* function arm_paypal_prochange_pending_gateway($gateways, $plan_id, $user_id) {
            global $wp, $ARMember;
            array_push($gateways, 'paypal_pro');
            return $gateways;
        } */

       /* function arm_membership_paypal_pro_cron($cronperiod) {
            global $arm_crons, $ARMember;
            if (!wp_next_scheduled('arm_membership_payflow_pro_recurring_payment')) {
                wp_schedule_event(time(), 'daily', 'arm_membership_payflow_pro_recurring_payment');
            }
        }*/

        function arm_paypal_pro_update_meta_after_renew($user_id, $log_detail, $plan_id, $payment_gateway) {
            if ($payment_gateway == 'paypal_pro') {
                if ($user_id != '' && !empty($log_detail) && $plan_id != '' && $plan_id != 0) {
                    $updating_data = array('sale_id' => $log_detail->arm_token, 'transaction_id' => $log_detail->arm_transaction_id);
                    update_user_meta($user_id, 'arm_paypal_pro_' . $plan_id, $updating_data);
                }
            }
        }
        
        function arm2_default_plan_array_filter_func( $default_plan_array ) {
            $default_plan_array['arm_paypal_pro'] = '';
            return $default_plan_array;
        }

        function arm2_paypal_pro_update_meta_after_renew($user_id, $log_detail, $plan_id, $payment_gateway) {
            global $ARMember;
            if ($payment_gateway == 'paypal_pro') {
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
                    $plan_data['arm_paypal_pro'] = $pg_subsc_data;
                    $ARMember->arm_write_response("rpeutelog log detail : ".maybe_serialize($plan_data));
                    update_user_meta($user_id, 'arm_user_plan_' . $plan_id, $plan_data);
                }
            }
        }

        
        /* function arm_paypal_pro_recurring_trial($notice) {
            $notice .= "<span style='margin-bottom:10px;'><b>" . __('Paypal Pro (if Paypal Pro payment gateway is enabled)', ARM_PAYPALPRO_TXTDOMAIN) . "</b><br/>";
            $notice .= "<ol style='margin-left:30px;'>";
            $notice .= "<li>" . __('Trial period will not be applied in Paypal Pro.', ARM_PAYPALPRO_TXTDOMAIN) . "</li>";
            $notice .= "<li>" . __('Coupon will not be applied with recurring payment in Paypal Pro.', ARM_PAYPALPRO_TXTDOMAIN) . "</li>";
            $notice .= "</ol>";
            $notice .= "</span>";

            return $notice;
        } */

        function arm_membership_payflow_pro_check_recurring_payment() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_payment_gateways, $arm_subscription_plan, $arm_manage_communication, $arm_members_class;
            set_time_limit(0);

            $payment_log_table = $ARMember->tbl_arm_payment_log;
            $t_user = $wpdb->prefix . 'users';
            $t_userm = $wpdb->prefix . 'usermeta';
            $users = $wpdb->get_results($wpdb->prepare("SELECT u.ID,um.meta_value as plan_id FROM `{$t_user}` u LEFT JOIN `{$t_userm}` um ON u.ID = um.user_id WHERE um.meta_key = %s AND um.meta_value > %d", 'arm_user_plan', 0));
            if (!empty($users)) {
                foreach ($users as $user) {
                    $plan_id = $user->plan_id;
                    $user_id = $user->ID;
                    if (get_user_meta($user_id, 'arm_using_gateway_' . $plan_id, true) != 'paypal_pro') {
                        continue;
                    }
                    $plan_obj = new ARM_Plan($plan_id);
                    $user_selected_payment_mode = get_user_meta($user_id, 'arm_selected_payment_mode', true);
                    if ($plan_obj->is_recurring() && $user_selected_payment_mode == 'auto_debit_subscription') {
                        $get_payment = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id,arm_transaction_id,arm_extra_vars FROM `{$payment_log_table}` WHERE `arm_plan_id` = %d AND `arm_user_id` = %d AND `arm_payment_gateway` = %s ORDER BY arm_log_id DESC LIMIT 0,1", $plan_id, $user_id, 'paypal_pro'));

                    	$user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                        $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                        if (empty($get_payment)) {
                            continue;
                        }
                        $extra_vars = maybe_unserialize($get_payment[0]->arm_extra_vars);

                        $gateway_options = get_option('arm_payment_gateway_settings');

                        $pgoptions = maybe_unserialize($gateway_options);
                        $pgoptions = $pgoptions['paypal_pro'];

                        $profile_id = $extra_vars['profileID'];

                        $payment_type = isset($extra_vars['payment_type']) ? $extra_vars['payment_type'] : 'payflow_pro';
                        $payment_mode = isset($extra_vars['payment_mode']) ? $extra_vars['payment_mode'] : $pgoptions['paypal_pro_payment_mode'];
                        $is_sandbox_mode = ($payment_mode == 'sandbox') ? true : false;
                        $username = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_username'] : $pgoptions['payflow_pro_username'];
                        $password = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_password'] : $pgoptions['payflow_pro_password'];
                        $vendor = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_vendor'] : $pgoptions['payflow_pro_vendor'];
                        $partner = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_partner'] : $pgoptions['payflow_pro_partner'];
                        if ($payment_type == 'payflow_pro' && ($payment_type != '' || $payment_mode != '')) {
                            $api_endpoint = ( $is_sandbox_mode ) ? "https://pilot-payflowpro.paypal.com" : "https://payflowpro.paypal.com";
                            $transaction_id = $get_payment[0]->arm_transaction_id;
                            $arm_log_id = $get_payment[0]->arm_log_id;
                            $inquiry_array = array(
                                'TRXTYPE' => 'R',
                                'TENDER' => 'C',
                                'ACTION' => "I",
                                "ORIGPROFILEID" => $profile_id
                            );
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                            curl_setopt($ch, CURLOPT_VERBOSE, 1);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_POST, 1);

                            $nvpstr = "";
                            if (is_array($inquiry_array)) {
                                foreach ($inquiry_array as $key => $value) {
                                    if (is_array($value)) {
                                        foreach ($value as $item) {
                                            if (strlen($nvpstr) > 0) {
                                                $nvpstr .= "&";
                                            }
                                            $nvpstr .= "$key=" . $item;
                                        }
                                    } else {
                                        if (strlen($nvpstr) > 0) {
                                            $nvpstr .= "&";
                                        }
                                        $nvpstr .= "$key=" . $value;
                                    }
                                }
                            }

                            $nvpreq = "VENDOR=$vendor&PARTNER=$partner&PWD=$password&USER=$username&$nvpstr";

                            curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

                            $response_ = curl_exec($ch);
                            curl_close($ch);

                            $_formatted_response = array();
                            $_response_ex = explode('&', $response_);
                            foreach ($_response_ex as $key => $_value) {
                                $_response_in = explode("=", $_value);
                                $_formatted_response[$_response_in[0]] = $_response_in[1];
                            }
                            $status = $_formatted_response['STATUS'];
                            if ($status == 'ACTIVE') {
                                $terms = $_formatted_response['TERM'];
                                $left_payments = ( $_formatted_response['PAYMENTSLEFT'] ) ? $_formatted_response['PAYMENTSLEFT'] : 0;
                                $payer_email = urldecode($_formatted_response["PROFILENAME"]);
                                $inquiry_array['PAYMENTHISTORY'] = 'Y';
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                                curl_setopt($ch, CURLOPT_VERBOSE, 1);
                                curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_POST, 1);
                                $nvpstr = "";
                                if (is_array($inquiry_array)) {
                                    foreach ($inquiry_array as $key => $value) {
                                        if (is_array($value)) {
                                            foreach ($value as $item) {
                                                if (strlen($nvpstr) > 0)
                                                    $nvpstr .= "&";
                                                $nvpstr .= "$key=" . $item;
                                            }
                                        } else {
                                            if (strlen($nvpstr) > 0)
                                                $nvpstr .= "&";
                                            $nvpstr .= "$key=" . $value;
                                        }
                                    }
                                }

                                $nvpreq = "VENDOR=$vendor&PARTNER=$partner&PWD=$password&USER=$username&$nvpstr";
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

                                $response_ = curl_exec($ch);
                                curl_close($ch);
                                $_formatted_response = array();
                                $_response_ex = explode('&', $response_);
                                foreach ($_response_ex as $key => $_value) {
                                    $_response_in = explode("=", $_value);
                                    $_formatted_response[$_response_in[0]] = $_response_in[1];
                                }

                                $paid_payment_count = $terms - $left_payments;
                                if ($paid_payment_count > 0) {
                                    $first_transaction_id = ($_formatted_response['P_PNREF1']) ? $_formatted_response['P_PNREF1'] : '';
                                    if ($first_transaction_id != '' && $transaction_id != $first_transaction_id) {
                                        $first_transaction_result = @$_formatted_response['P_RESULT1'];
                                        $first_transaction_time = strtotime($_formatted_response['P_TRANSTIME1']);
                                        if ($first_transaction_result == 0) {
                                            $first_status = "success";
                                        } else {
                                            $first_status = "pending";
                                        }
                                        $getTransaction = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s", $first_transaction_id));
                                        if (empty($getTransaction)) {
                                            $wpdb->update($payment_log_table, array('arm_transaction_id' => $first_transaction_id, 'arm_payment_date' => date('Y-m-d H:i:s', $first_transaction_time), 'arm_transaction_status' => $first_status, 'arm_display_log' => 1), array('arm_log_id' => $arm_log_id));
                                        }
                                    }

                                    for ($i = 2; $i <= $paid_payment_count; $i++) {
                                        $transaction_id_ = isset($_formatted_response['P_PNREF' . $i]) ? $_formatted_response['P_PNREF' . $i] : '';
                                        if ($transaction_id_ == '') {
                                            continue;
                                        }
                                        $getTransaction = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s", $transaction_id_));

                                        if (empty($getTransaction)) {

                                            $transaction_result_ = isset($_formatted_response['P_RESULT' . $i]) ? $_formatted_response['P_RESULT' . $i] : '';
                                            $transaction_time_ = isset($_formatted_response['P_TRANSTIME' . $i]) ? strtotime($_formatted_response['P_TRANSTIME' . $i]) : '';
                                            $amount = $_formatted_response['P_AMT' . $i];
                                            if ($transaction_result_ == 0) {
                                                $txn_status = "success";
                                            } else {
                                                $txn_status = "pending";
                                            }
                                            $recurring_payment_data = array(
                                                'arm_user_id' => $user_id,
                                                'arm_plan_id' => $plan_id,
                                                'arm_first_name'=> $user_detail_first_name,
                                                'arm_last_name'=> $user_detail_last_name,
                                                'arm_payment_gateway' => 'paypal_pro',
                                                'arm_payment_type' => 'subscription',
                                                'arm_payer_email' => $payer_email,
                                                'arm_transaction_id' => $transaction_id_,
                                                'arm_transaction_status' => $txn_status,
                                                'arm_payment_date' => date('Y-m-d H:i:s', $transaction_time_),
                                                'arm_amount' => $amount,
                                                'arm_extra_vars' => maybe_serialize($extra_vars),
                                                'arm_response_text' => maybe_serialize($_formatted_response),
                                                'arm_created_date' => current_time('mysql'),
                                                'arm_display_log' => '1'
                                            );
                                            
                                             $payment_log_id = $arm_payment_gateways->arm_save_payment_log($recurring_payment_data);
                                           // $wpdb->insert($payment_log_table, $recurring_payment_data);
                                        }
                                    }
                                }
                            } else {
                                switch ($status) {
                                    case 'EXPIRED':
                                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'eot'));
                                        break;
                                    case 'TOO MANY FAILURES':
                                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                        break;
                                    case 'DEACTIVATED BY MERCHANT':
                                    case 'VENDOR INACTIVE':
                                        $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                        break;
                                    default:
                                        break;
                                }
                            }
                        } else {
                            // Paypal Payments Pro
                        }
                    }
                }
            }
        }

        function arm2_membership_payflow_pro_check_recurring_payment() {
            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_payment_gateways, $arm_subscription_plan, $arm_manage_communication, $arm_members_class, $arm_subscription_plans;
            set_time_limit(0);
            $ARMember->arm_write_response("reputelog paypal pro : in cron");
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
                    $ARMember->arm_write_response("reputelog paypal pro : user id => ".$user_id);
                    $plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true); 
                    $plan_ids = !empty($plan_ids) ? $plan_ids : array(); 
                    if(!empty($plan_ids) && is_array($plan_ids)){
                        foreach($plan_ids as $plan_id){
                            $ARMember->arm_write_response("reputelog paypal pro : user id => ".$user_id." plan id => ".$plan_id);
                            $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                            if(!empty($planData)){
                                $arm_user_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
                                if($arm_user_gateway != 'paypal_pro')
                                { continue; }
                                $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                                $planDetail = $planData['arm_current_plan_detail'];
                                if (!empty($planDetail)) { 
                                    $plan = new ARM_Plan(0);
                                    $plan->init((object) $planDetail);
                                } else {
                                    $plan = new ARM_Plan($plan_id);
                                }
                                if ($plan->is_recurring() && $user_selected_payment_mode == 'auto_debit_subscription') {
                                    $ARMember->arm_write_response("reputelog paypal pro : user id => ".$user_id." plan id => ".$plan_id."  Auto debit");
                                    $get_payment = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id,arm_transaction_id,arm_extra_vars FROM `{$payment_log_table}` WHERE `arm_plan_id` = %d AND `arm_user_id` = %d AND `arm_payment_gateway` = %s AND `arm_transaction_status` = %s ORDER BY arm_log_id DESC LIMIT 0,1", $plan_id, $user_id, 'paypal_pro', 'success'));

                                    $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                                    $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                                    if (empty($get_payment)) {
                                        continue;
                                    }
                                    $extra_vars = maybe_unserialize($get_payment[0]->arm_extra_vars);

                                    $gateway_options = get_option('arm_payment_gateway_settings');

                                    $pgoptions = maybe_unserialize($gateway_options);
                                    $pgoptions = $pgoptions['paypal_pro'];

                                    $profile_id = $extra_vars['profileID'];
                                    $ARMember->arm_write_response("reputelog paypal pro : profile ID => ".$profile_id);
                                    
                                    $payment_type = isset($extra_vars['payment_type']) ? $extra_vars['payment_type'] : 'payflow_pro';
                                    $payment_mode = isset($extra_vars['payment_mode']) ? $extra_vars['payment_mode'] : $pgoptions['paypal_pro_payment_mode'];
                                    $is_sandbox_mode = ($payment_mode == 'sandbox') ? true : false;
                                    $username = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_username'] : $pgoptions['payflow_pro_username'];
                                    $password = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_password'] : $pgoptions['payflow_pro_password'];
                                    $vendor = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_vendor'] : $pgoptions['payflow_pro_vendor'];
                                    $partner = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_partner'] : $pgoptions['payflow_pro_partner'];
                                    if ($payment_type == 'payflow_pro' && ($payment_type != '' || $payment_mode != '')) {
                                        $ARMember->arm_write_response("reputelog paypal pro : payment_type => ".$payment_type);
                                        $api_endpoint = ( $is_sandbox_mode ) ? "https://pilot-payflowpro.paypal.com" : "https://payflowpro.paypal.com";
                                        $transaction_id = $get_payment[0]->arm_transaction_id;
                                        $arm_log_id = $get_payment[0]->arm_log_id;
                                        $inquiry_array = array(
                                            'TRXTYPE' => 'R',
                                            'TENDER' => 'C',
                                            'ACTION' => "I",
                                            "ORIGPROFILEID" => $profile_id
                                        );
                                        $ch = curl_init();
                                        curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                                        curl_setopt($ch, CURLOPT_VERBOSE, 1);
                                        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                        curl_setopt($ch, CURLOPT_POST, 1);

                                        $nvpstr = "";
                                        if (is_array($inquiry_array)) {
                                            foreach ($inquiry_array as $key => $value) {
                                                if (is_array($value)) {
                                                    foreach ($value as $item) {
                                                        if (strlen($nvpstr) > 0) {
                                                            $nvpstr .= "&";
                                                        }
                                                        $nvpstr .= "$key=" . $item;
                                                    }
                                                } else {
                                                    if (strlen($nvpstr) > 0) {
                                                        $nvpstr .= "&";
                                                    }
                                                    $nvpstr .= "$key=" . $value;
                                                }
                                            }
                                        }

                                        $nvpreq = "VENDOR=$vendor&PARTNER=$partner&PWD=$password&USER=$username&$nvpstr";

                                        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

                                        $response_ = curl_exec($ch);
                                        curl_close($ch);

                                        $_formatted_response = array();
                                        $_response_ex = explode('&', $response_);
                                        foreach ($_response_ex as $key => $_value) {
                                            $_response_in = explode("=", $_value);
                                            $_formatted_response[$_response_in[0]] = $_response_in[1];
                                        }
                                        
                                        $ARMember->arm_write_response("reputelog paypal pro : formatted response 1 => ".maybe_serialize($_formatted_response));
                                        
                                        $status = $_formatted_response['STATUS'];
                                        if ($status == 'ACTIVE') {
                                            $terms = $_formatted_response['TERM'];
                                            $NEXTPAYMENTNUM = (isset( $_formatted_response['NEXTPAYMENTNUM'] ) && $_formatted_response['NEXTPAYMENTNUM'] != '' )? $_formatted_response['NEXTPAYMENTNUM'] : 0;
                                            $left_payments = (isset( $_formatted_response['PAYMENTSLEFT'] ) && $_formatted_response['PAYMENTSLEFT'] != '' )? $_formatted_response['PAYMENTSLEFT'] : 0;
                                            $payer_email = urldecode($_formatted_response["PROFILENAME"]);
                                            $inquiry_array['PAYMENTHISTORY'] = 'Y';
                                            $ch = curl_init();
                                            curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                                            curl_setopt($ch, CURLOPT_VERBOSE, 1);
                                            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                            curl_setopt($ch, CURLOPT_POST, 1);
                                            $nvpstr = "";
                                            if (is_array($inquiry_array)) {
                                                foreach ($inquiry_array as $key => $value) {
                                                    if (is_array($value)) {
                                                        foreach ($value as $item) {
                                                            if (strlen($nvpstr) > 0)
                                                                $nvpstr .= "&";
                                                            $nvpstr .= "$key=" . $item;
                                                        }
                                                    } else {
                                                        if (strlen($nvpstr) > 0)
                                                            $nvpstr .= "&";
                                                        $nvpstr .= "$key=" . $value;
                                                    }
                                                }
                                            }

                                            $nvpreq = "VENDOR=$vendor&PARTNER=$partner&PWD=$password&USER=$username&$nvpstr";
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

                                            $response_ = curl_exec($ch);
                                            curl_close($ch);
                                            $_formatted_response = array();
                                            $_response_ex = explode('&', $response_);
                                            foreach ($_response_ex as $key => $_value) {
                                                $_response_in = explode("=", $_value);
                                                $_formatted_response[$_response_in[0]] = $_response_in[1];
                                            }

                                            $ARMember->arm_write_response("reputelog paypal pro : formatted response 2 => ".maybe_serialize($_formatted_response));
                                            if($terms > 0) {
                                                $paid_payment_count = $terms - $left_payments;
                                                if ($paid_payment_count > 0) {
                                                    $ARMember->arm_write_response("reputelog paypal pro : paid_payment_count => ".$paid_payment_count);
                                                    $first_transaction_id = ($_formatted_response['P_PNREF1']) ? $_formatted_response['P_PNREF1'] : '';
                                                    $first_amount = $_formatted_response['P_AMT1'];
                                                    $arm_update_meta = false;
                                                    if ($first_transaction_id != '' && $transaction_id != $first_transaction_id) {
                                                        $first_transaction_result = @$_formatted_response['P_RESULT1'];
                                                        $first_transaction_time = strtotime($_formatted_response['P_TRANSTIME1']);
                                                        if ($first_transaction_result == 0) {
                                                            $first_status = "success";
                                                        } else {
                                                            $first_status = "pending";
                                                        }
                                                        $getTransaction = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s", $first_transaction_id));
                                                        if (empty($getTransaction)) {
                                                            $arm_update_meta = true;
                                                            $first_trxn_date = date('Y-m-d H:i:s', $first_transaction_time);
                                                            $wpdb->update($payment_log_table, array('arm_transaction_id' => $first_transaction_id, 'arm_payment_date' => $first_trxn_date, 'arm_created_date' => $first_trxn_date, 'arm_transaction_status' => $first_status, 'arm_display_log' => 1, 'arm_amount'=>$first_amount), array('arm_log_id' => $arm_log_id));
                                                        }
                                                    }

                                                    for ($i = 2; $i <= $paid_payment_count; $i++) {
                                                        $transaction_id_ = isset($_formatted_response['P_PNREF' . $i]) ? $_formatted_response['P_PNREF' . $i] : '';
                                                        if ($transaction_id_ == '') {
                                                            continue;
                                                        }
                                                        $getTransaction = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s", $transaction_id_));

                                                        if (empty($getTransaction)) {
                                                            $ARMember->arm_write_response("reputelog paypal pro : transaction_id_ => ".$transaction_id_);
                                                            $transaction_result_ = isset($_formatted_response['P_RESULT' . $i]) ? $_formatted_response['P_RESULT' . $i] : '';
                                                            $transaction_time_ = isset($_formatted_response['P_TRANSTIME' . $i]) ? strtotime($_formatted_response['P_TRANSTIME' . $i]) : '';
                                                            $amount = $_formatted_response['P_AMT' . $i];
                                                            if ($transaction_result_ == 0) {
                                                                $txn_status = "success";
                                                            } else {
                                                                $txn_status = "pending";
                                                            }
                                                            $recurring_payment_data = array(
                                                                'arm_user_id' => $user_id,
                                                                'arm_plan_id' => $plan_id,
                                                                'arm_first_name'=> $user_detail_first_name,
                                                                'arm_last_name'=> $user_detail_last_name,
                                                                'arm_payment_gateway' => 'paypal_pro',
                                                                'arm_payment_type' => 'subscription',
                                                                'arm_payer_email' => $payer_email,
                                                                'arm_transaction_id' => $transaction_id_,
                                                                'arm_transaction_status' => $txn_status,
                                                                'arm_payment_date' => date('Y-m-d H:i:s', $transaction_time_),
                                                                'arm_amount' => $amount,
                                                                'arm_extra_vars' => maybe_serialize($extra_vars),
                                                                'arm_response_text' => maybe_serialize($_formatted_response),
                                                                'arm_created_date' => current_time('mysql'),
                                                                'arm_display_log' => '1'
                                                            );
                                                            
                                                            $payment_log_id = $arm_payment_gateways->arm_save_payment_log($recurring_payment_data);
                                                            
                                                            //$wpdb->insert($payment_log_table, $recurring_payment_data);
                                                            $arm_update_meta = true;
                                                            

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

                                                            }
                                                            else{
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

                                                        $user_subsdata = $planData['arm_paypal_pro'];
                                                        do_action('arm_after_recurring_payment_success_outside',$user_id,$plan_id,'paypal_pro',$payment_mode,$user_subsdata);
                                                    }
                                                }
                                            }
                                            else {
                                                $total_payments = isset($NEXTPAYMENTNUM) ? ($NEXTPAYMENTNUM-1) : 0;
                                                $ARMember->arm_write_response("reputelog paypal pro recurring total payments number: => ".$total_payments);
                                                if($total_payments > 0) {
                                                    $first_transaction_id = ($_formatted_response['P_PNREF1']) ? $_formatted_response['P_PNREF1'] : '';
                                                    $first_amount = $_formatted_response['P_AMT1'];
                                                    $arm_update_meta = false;
                                                    if ($first_transaction_id != '' && $transaction_id != $first_transaction_id) {
                                                        $first_transaction_result = @$_formatted_response['P_RESULT1'];
                                                        $first_transaction_time = strtotime($_formatted_response['P_TRANSTIME1']);

                                                        $ARMember->arm_write_response("reputelog paypal pro recurring first_transaction_time: => ".date('Y-m-d H:i:s', $first_transaction_time));

                                                        if ($first_transaction_result == 0) {
                                                            $first_status = "success";
                                                        } else {
                                                            $first_status = "pending";
                                                        }
                                                        $getTransaction = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s", $first_transaction_id));
                                                        if (empty($getTransaction)) {
                                                            $arm_update_meta = true;
                                                            $first_trxn_date = date('Y-m-d H:i:s', $first_transaction_time);
                                                            $wpdb->update($payment_log_table, array('arm_transaction_id' => $first_transaction_id, 'arm_payment_date' => $first_trxn_date, 'arm_created_date' => $first_trxn_date, 'arm_transaction_status' => $first_status, 'arm_display_log' => 1, 'arm_amount'=>$first_amount), array('arm_log_id' => $arm_log_id));
                                                        }
                                                    }

                                                    for ($i = 2; $i <= $total_payments; $i++) {
                                                        $transaction_id_ = isset($_formatted_response['P_PNREF' . $i]) ? $_formatted_response['P_PNREF' . $i] : '';
                                                        if ($transaction_id_ == '') {
                                                            continue;
                                                        }
                                                        $getTransaction = $wpdb->get_results($wpdb->prepare("SELECT arm_log_id FROM `{$payment_log_table}` WHERE `arm_transaction_id` = %s", $transaction_id_));

                                                        if (empty($getTransaction)) {
                                                            $ARMember->arm_write_response("reputelog paypal pro recurring: transaction_id_ => ".$transaction_id_);
                                                            $transaction_result_ = isset($_formatted_response['P_RESULT' . $i]) ? $_formatted_response['P_RESULT' . $i] : '';
                                                            $transaction_time_ = isset($_formatted_response['P_TRANSTIME' . $i]) ? strtotime($_formatted_response['P_TRANSTIME' . $i]) : '';
                                                            $amount = $_formatted_response['P_AMT' . $i];
                                                            if ($transaction_result_ == 0) {
                                                                $txn_status = "success";
                                                            } else {
                                                                $txn_status = "pending";
                                                            }
                                                            $recurring_payment_data = array (
                                                                'arm_user_id' => $user_id,
                                                                'arm_plan_id' => $plan_id,
                                                                'arm_first_name'=> $user_detail_first_name,
                                                                'arm_last_name'=> $user_detail_last_name,
                                                                'arm_payment_gateway' => 'paypal_pro',
                                                                'arm_payment_type' => 'subscription',
                                                                'arm_payer_email' => $payer_email,
                                                                'arm_transaction_id' => $transaction_id_,
                                                                'arm_transaction_status' => $txn_status,
                                                                'arm_payment_date' => date('Y-m-d H:i:s', $transaction_time_),
                                                                'arm_amount' => $amount,
                                                                'arm_extra_vars' => maybe_serialize($extra_vars),
                                                                'arm_response_text' => maybe_serialize($_formatted_response),
                                                                'arm_created_date' => current_time('mysql'),
                                                                'arm_display_log' => '1'
                                                            );
                                                            
                                                            $payment_log_id = $arm_payment_gateways->arm_save_payment_log($recurring_payment_data);
                                                            
                                                            //$wpdb->insert($payment_log_table, $recurring_payment_data);
                                                            $arm_update_meta = true;
                                                            

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

                                                            }
                                                            else{
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

                                                        $user_subsdata = $planData['arm_paypal_pro'];
                                                        do_action('arm_after_recurring_payment_success_outside',$user_id,$plan_id,'paypal_pro',$payment_mode,$user_subsdata);
                                                    }
                                                }
                                            }
                                        } else {
                                            $ARMember->arm_write_response("reputelog paypal pro : status => ".$status);
                                            switch ($status) {
                                                case 'EXPIRED':
                                                    $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'eot'));
                                                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'eot'));
                                                    break;
                                                case 'TOO MANY FAILURES':
                                                    $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                                    
                                                    break;
                                                case 'DEACTIVATED BY MERCHANT':
                                                case 'VENDOR INACTIVE':
                                                    $arm_subscription_plans->arm_user_plan_status_action(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'failed_payment'));
                                                    break;
                                                default:
                                                    break;
                                            }
                                        }
                                    } else {
                                        // Paypal Payments Pro
                                    }
                                   
                                }
                            }
                        }
                    }
                }
            }   
        }
        
        function arm_paypal_pro_cancel_subscription($user_id, $plan_id) {
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
            if (!empty($user_id) && $user_id != 0 && !empty($plan_id) && $plan_id != 0) {
                $planObj = new ARM_plan($plan_id);
                if ($planObj->is_recurring() && $planObj->options['recurring']['payment_mode'] == 'manual_subscription') {
                    return false;
                }
                $arm_is_trial = '0';
                $user_detail = get_userdata($user_id);
                $payer_email = $user_detail->user_email;
                $user_payment_gateway = get_user_meta($user_id, 'arm_using_gateway_' . $plan_id, true);
                if ($user_payment_gateway == 'paypal_pro') {
                    $payment_log_table = $ARMember->tbl_arm_payment_log;
                    $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_extra_vars,arm_payer_email FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'paypal_pro'));
                    if (!empty($transaction)) {
                        $extra_var = maybe_unserialize($transaction->arm_extra_vars);
                        $profileID = $extra_var['profileID'];
                        $payer_email = $transaction->arm_payer_email;
                        $payment_type = $extra_var['payment_type'];
                        $payment_mode = $extra_var['payment_mode'];

                        $gateway_options = get_option('arm_payment_gateway_settings');
                        $pgoptions = maybe_unserialize($gateway_options);
                        $pgoptions = $pgoptions['paypal_pro'];

                        $is_sandbox_mode = ( $payment_mode == 'sandbox' ) ? true : false;
                        if ($payment_type == 'payflow_pro') {
                            $username = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_username'] : $pgoptions['payflow_pro_username'];
                            $password = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_password'] : $pgoptions['payflow_pro_password'];
                            $vendor = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_vendor'] : $pgoptions['payflow_pro_vendor'];
                            $partner = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_partner'] : $pgoptions['payflow_pro_partner'];

                            $api_endpoint = ( $is_sandbox_mode ) ? "https://pilot-payflowpro.paypal.com" : "https://payflowpro.paypal.com";

                            $cancellation_array = array(
                                'TRXTYPE' => 'R',
                                'ACTION' => 'C',
                                'ORIGPROFILEID' => $profileID
                            );

                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                            curl_setopt($ch, CURLOPT_VERBOSE, 1);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_POST, 1);

                            $nvpstr = "";

                            if (is_array($cancellation_array)) {
                                foreach ($cancellation_array as $key => $value) {
                                    if (is_array($value)) {
                                        foreach ($value as $item) {
                                            if (strlen($nvpstr) > 0)
                                                $nvpstr .= "&";
                                            $nvpstr .= "$key=" . $item;
                                        }
                                    } else {
                                        if (strlen($nvpstr) > 0)
                                            $nvpstr .= "&";
                                        $nvpstr .= "$key=" .$value;
                                    }
                                }
                            }

                            $nvpreq = "VENDOR=$vendor&PARTNER=$partner&PWD=$password&USER=$username&$nvpstr";
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

                            $response_ = curl_exec($ch);
                            curl_close($ch);

                            $formatted_response = array();
                            $response_ex = explode('&', $response_);
                            foreach ($response_ex as $key => $value) {
                                $response_in = explode("=", $value);
                                $formatted_response[$response_in[0]] = $response_in[1];
                            }

                            if ($formatted_response['RESULT'] == 0) {
			    	
				$user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
				$user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
		    
                                $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'cancel_payment'));
                                $payment_data = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan_id,
                                    'arm_first_name'=> $user_detail_first_name,
                                    'arm_last_name'=> $user_detail_last_name,
                                    'arm_payment_gateway' => 'paypal_pro',
                                    'arm_payment_type' => 'subscription',
                                    'arm_token' => '',
                                    'arm_payer_email' => $payer_email,
                                    'arm_receiver_email' => '',
                                    'arm_transaction_id' => $formatted_response['PROFILEID'],
                                    'arm_transaction_payment_type' => $payment_type,
                                    'arm_transaction_status' => 'canceled',
                                    'arm_payment_date' => current_time('mysql'),
                                    'arm_amount' => 0,
                                    'arm_currency' => '',
                                    'arm_coupon_code' => '',
                                    'arm_is_trial' => $arm_is_trial,
                                    'arm_response_text' => maybe_serialize($formatted_response),
                                    'arm_created_date' => current_time('mysql')
                                );
                                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
                                delete_user_meta($user_id, 'arm_payment_cancelled_by');
                                return;
                            }
                        }
                    }
                }
            }
        }

        function arm2_paypal_pro_cancel_subscription($user_id, $plan_id) {
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_payment_gateways, $arm_manage_communication;
            if (!empty($user_id) && $user_id != 0 && !empty($plan_id) && $plan_id != 0) {
                $planData = get_user_meta($user_id, 'arm_user_plan_'.$plan_id, true);
                $currency = $arm_payment_gateways->arm_get_global_currency();
                if(!empty($planData)){
                    $arm_user_gateway = isset($planData['arm_user_gateway']) ? $planData['arm_user_gateway'] : '';
                    if($arm_user_gateway == 'paypal_pro')
                    {
                        $user_selected_payment_mode = isset($planData['arm_payment_mode']) ? $planData['arm_payment_mode'] : '';
                        $planDetail = $planData['arm_current_plan_detail'];
                        
                        if (!empty($planDetail)) { 
                            $planObj = new ARM_Plan(0);
                            $planObj->init((object) $planDetail);
                        } else {
                            $planObj = new ARM_Plan($plan_id);
                        }
                        
                        /* if ($planObj->is_recurring() && $user_selected_payment_mode == 'manual_subscription') {
                             $ARMember->arm_write_response("reputelog is recurring and manual subscripotion return cancel ");
                             return false;
                         } */
                        $arm_is_trial = '0';
                        $user_detail = get_userdata($user_id);
                        $payer_email = $user_detail->user_email;
                        $payment_log_table = $ARMember->tbl_arm_payment_log;
                        $transaction = $wpdb->get_row($wpdb->prepare("SELECT arm_extra_vars,arm_payer_email FROM `{$payment_log_table}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d AND `arm_payment_type` = %s AND `arm_payment_gateway` = %s ORDER BY `arm_created_date` DESC LIMIT 0,1", $user_id, $plan_id, 'subscription', 'paypal_pro'));

                        $user_detail_first_name = get_user_meta( $user_id, 'first_name', true);
                        $user_detail_last_name = get_user_meta( $user_id, 'last_name', true);
                        if (!empty($transaction)) {
                            $extra_var = maybe_unserialize($transaction->arm_extra_vars);
                            $profileID = $extra_var['profileID'];
                            $payer_email = $transaction->arm_payer_email;
                            $payment_type = $extra_var['payment_type'];
                            $payment_mode = $extra_var['payment_mode'];

                            $gateway_options = get_option('arm_payment_gateway_settings');
                            $pgoptions = maybe_unserialize($gateway_options);
                            $pgoptions = $pgoptions['paypal_pro'];

                            $is_sandbox_mode = ( $payment_mode == 'sandbox' ) ? true : false;
                            if ($payment_type == 'payflow_pro') {
                                
                                if($user_selected_payment_mode == 'auto_debit_subscription'){
                                $ARMember->arm_write_response("reputelog is recurring and auto debit subscripotion");
                                $username = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_username'] : $pgoptions['payflow_pro_username'];
                                $password = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_password'] : $pgoptions['payflow_pro_password'];
                                $vendor = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_vendor'] : $pgoptions['payflow_pro_vendor'];
                                $partner = ($is_sandbox_mode) ? $pgoptions['payflow_pro_sandbox_partner'] : $pgoptions['payflow_pro_partner'];

                                $api_endpoint = ( $is_sandbox_mode ) ? "https://pilot-payflowpro.paypal.com" : "https://payflowpro.paypal.com";

                                $cancellation_array = array(
                                    'TRXTYPE' => 'R',
                                    'ACTION' => 'C',
                                    'ORIGPROFILEID' => $profileID
                                );

                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                                curl_setopt($ch, CURLOPT_VERBOSE, 1);
                                curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_POST, 1);

                                $nvpstr = "";

                                if (is_array($cancellation_array)) {
                                    foreach ($cancellation_array as $key => $value) {
                                        if (is_array($value)) {
                                            foreach ($value as $item) {
                                                if (strlen($nvpstr) > 0)
                                                    $nvpstr .= "&";
                                                $nvpstr .= "$key=" . $item;
                                            }
                                        } else {
                                            if (strlen($nvpstr) > 0)
                                                $nvpstr .= "&";
                                            $nvpstr .= "$key=" . $value;
                                        }
                                    }
                                }

                                $nvpreq = "VENDOR=$vendor&PARTNER=$partner&PWD=$password&USER=$username&$nvpstr";
                                curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

                                $response_ = curl_exec($ch);
                                curl_close($ch);

                                $formatted_response = array();
                                $response_ex = explode('&', $response_);
                                foreach ($response_ex as $key => $value) {
                                    $response_in = explode("=", $value);
                                    $formatted_response[$response_in[0]] = $response_in[1];
                                }

                                if ($formatted_response['RESULT'] == 0) {
                                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                    $payment_data = array(
                                        'arm_user_id' => $user_id,
                                        'arm_plan_id' => $plan_id,
                                        'arm_first_name'=> $user_detail_first_name,
                                        'arm_last_name'=> $user_detail_last_name,
                                        'arm_payment_gateway' => 'paypal_pro',
                                        'arm_payment_type' => 'subscription',
                                        'arm_token' => '',
                                        'arm_payer_email' => $payer_email,
                                        'arm_receiver_email' => '',
                                        'arm_transaction_id' => $formatted_response['PROFILEID'],
                                        'arm_transaction_payment_type' => $payment_type,
                                        'arm_transaction_status' => 'canceled',
                                        'arm_payment_date' => current_time('mysql'),
                                        'arm_amount' => 0,
                                        'arm_currency' => $currency,
                                        'arm_coupon_code' => '',
                                        'arm_is_trial' => $arm_is_trial,
                                        'arm_response_text' => maybe_serialize($formatted_response),
                                        'arm_created_date' => current_time('mysql')
                                    );
                                    $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data);
                                   // delete_user_meta($user_id, 'arm_payment_cancelled_by');
                                    return;
                                }
                                }
                                else{
                                    $ARMember->arm_write_response("reputelog is recurring and manual subscripotion");
                                    $arm_manage_communication->arm_user_plan_status_action_mail(array('plan_id' => $plan_id, 'user_id' => $user_id, 'action' => 'on_cancel_subscription'));
                                    $payment_data = array(
                                        'arm_user_id' => $user_id,
                                        'arm_plan_id' => $plan_id,
                                        'arm_first_name'=> $user_detail_first_name,
                                        'arm_last_name'=> $user_detail_last_name,
                                        'arm_payment_gateway' => 'paypal_pro',
                                        'arm_payment_type' => 'subscription',
                                        'arm_payer_email' => $payer_email,
                                        'arm_receiver_email' => '',
                                        'arm_transaction_id' => $profileID,
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
        }
        
        /* function arm_nvpToarray($NVPString) {
            $proArray = array();
            while (strlen($NVPString)) {

                $keypos = strpos($NVPString, '=');
                $keyval = substr($NVPString, 0, $keypos);

                $valuepos = strpos($NVPString, '&') ? strpos($NVPString, '&') : strlen($NVPString);
                $valval = substr($NVPString, $keypos + 1, $valuepos - $keypos - 1);

                $proArray[$keyval] = urldecode($valval);
                $NVPString = substr($NVPString, $valuepos + 1, strlen($NVPString));
            }
            return $proArray;
        } */

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

        function arm_paypal_pro_update_new_subscr_gateway($payment_gateways = array()) {
            array_push($payment_gateways, 'paypal_pro');
            return $payment_gateways;
        }

        function arm_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $arm_members_class, $paid_trial_stripe_payment_done;
            $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
            $currency = $arm_payment_gateways->arm_get_global_currency();
            if ($payment_gateway == 'paypal_pro' && isset($all_payment_gateways['paypal_pro']) && !empty($all_payment_gateways['paypal_pro'])) {
                $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
                if (!empty($entry_data) && !empty($posted_data[$payment_gateway])) {
                    $user_email_add = $entry_data['arm_entry_email'];
                    $user_id = $entry_data['arm_user_id'];
                    $form_id = $entry_data['arm_form_id'];
                    $first_name = $posted_data['first_name'];
                    $last_name = $posted_data['last_name'];
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
                    $plan_id = (!empty($posted_data['subscription_plan'])) ? $posted_data['subscription_plan'] : 0;
                    if ($plan_id == 0) {
                        $plan_id = (!empty($posted_data['_subscription_plan'])) ? $posted_data['_subscription_plan'] : 0;
                    }
                    $plan_action = 'new_subscription';

                    if (isset($posted_data['old_plan_id']) && !empty($posted_data['old_plan_id']) && $posted_data['old_plan_id'] != 0) {
                        if ($posted_data['old_plan_id'] == $plan_id) {
                            $plan_action = 'renew_subscription';
                        } else {
                            $plan_action = 'change_subscription';
                        }
                    }


                    $plan = new ARM_Plan($plan_id);

                    $description = "Plan id => {$plan_id} submitted by {$first_name} {$last_name}";
                    $plan_payment_type = $plan->payment_type;
                    $is_recurring = $plan->is_recurring();
                    $plan_payment_mode_ = !empty($posted_data['arm_payment_mode']['paypal_pro']) ? $posted_data['arm_payment_mode']['paypal_pro'] : 'manual_subscription';
                    $plan_payment_mode = "manual_subscription";
                    if ($plan_payment_mode_ == 'both') {
                        $plan_payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : "manual_subscription";
                    } else {
                        $plan_payment_mode = $plan_payment_mode_;
                    }
                    $custom_var = $entry_id . '|' . $user_email_add . '|' . $form_id . '|' . $plan_payment_type;
                    $amount = !empty($plan->amount) ? $plan->amount : "0";
                    $autho_options = $all_payment_gateways['paypal_pro'];

                    $current_payment_gateway = (isset($posted_data['payment_gateway'])) ? $posted_data['payment_gateway'] : '';
                    if ($current_payment_gateway == '') {
                        $current_payment_gateway = (isset($posted_data['_payment_gateway'])) ? $posted_data['_payment_gateway'] : '';
                    }
                    $paypal_pro_card_detail = $posted_data[$current_payment_gateway];
                    $card_holder_name = $paypal_pro_card_detail['card_holder_name'];
                    $card_number = $paypal_pro_card_detail['card_number'];
                    $exp_month = $paypal_pro_card_detail['exp_month'];
                    $exp_year = $paypal_pro_card_detail['exp_year'];
                    $cvc = $paypal_pro_card_detail['cvc'];
                    $payment_type = isset($payment_gateway_options['paypal_pro_payment_type']) ? $payment_gateway_options['paypal_pro_payment_type'] : 'payflow_pro';
                    $payment_mode = $payment_gateway_options['paypal_pro_payment_mode'];
                    $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;

                    if ($payment_type == 'payflow_pro') {
                        $api_endpoint = ( $is_sandbox_mode ) ? "https://pilot-payflowpro.paypal.com" : "https://payflowpro.paypal.com";
                        $api_username = ( $is_sandbox_mode ) ? $payment_gateway_options['payflow_pro_sandbox_username'] : $payment_gateway_options['payflow_pro_username'];
                        $api_password = ( $is_sandbox_mode ) ? $payment_gateway_options['payflow_pro_sandbox_password'] : $payment_gateway_options['payflow_pro_password'];
                        $api_vendor = ( $is_sandbox_mode ) ? $payment_gateway_options['payflow_pro_sandbox_vendor'] : $payment_gateway_options['payflow_pro_vendor'];
                        $api_partner = ( $is_sandbox_mode ) ? $payment_gateway_options['payflow_pro_sandbox_partner'] : $payment_gateway_options['payflow_pro_partner'];

                        $card_expire = $exp_month . $exp_year;
                        $maskCCNum = $arm_transaction->arm_mask_credit_card_number($card_number);
                        $arm_paypal_pro_enable_debug_mode = isset($payment_gateway_options['enable_debug_mode']) ? $payment_gateway_options['enable_debug_mode'] : 0;
                        $arm_help_link = '<a href="https://www.paypalobjects.com/en_AU/vhelp/paypalmanager_help/result_values_for_transaction_declines_or_errors.htm" target="_blank">'.__('Click Here', ARM_PAYPALPRO_TXTDOMAIN).'</a>';

                        $extraParam = array('card_number' => $maskCCNum, 'plan_amount' => $amount, 'paid_amount' => $amount);
                        $extraParam['payment_type'] = $payment_type;
                        $extraParam['payment_mode'] = ( $is_sandbox_mode ) ? "sandbox" : 'live';
                        /* Coupon Details */
                        if ($arm_manage_coupons->isCouponFeature && isset($posted_data['arm_coupon_code']) && !empty($posted_data['arm_coupon_code'])) {
                            $couponApply = $arm_manage_coupons->arm_apply_coupon_code($posted_data['arm_coupon_code'], $plan);
                            $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                            $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                            if (!empty($coupon_amount) && $coupon_amount > 0) {
                                $extraParam['coupon'] = array(
                                    'coupon_code' => $posted_data['arm_coupon_code'],
                                    'amount' => $coupon_amount,
                                );
                            }
                        } else {
                            $posted_data['arm_coupon_code'] = '';
                        }
                        $payment_data = array();

                        /* Authorizing Card Details */
                        $payflow_array = array(
                            'TENDER' => 'C',
                            'TRXTYPE' => 'A',
                            'ACCT' => $card_number,
                            'EXPDATE' => $card_expire,
                            'CVV2' => $cvc,
                            'AMT' => $amount,
                            'CURRENCY' => $currency,
                            'VERBOSITY' => 'MEDIUM',
                            'NAME' => $card_holder_name,
                        );

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                        curl_setopt($ch, CURLOPT_VERBOSE, 1);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        $nvpstr_ = "";

                        if ($is_recurring && $plan_payment_mode == 'auto_debit_subscription') {
                            $recurring_data = $plan->prepare_recurring_data();
                            $recur_period = $recurring_data['period'];
                            $payperiod = "MONT";
                            switch ($recur_period) {
                                case 'M':
                                    $payperiod = "MONT";
                                    break;
                                case 'D':
                                    $payperiod = "DAYS";
                                    break;
                                case 'W':
                                    $payperiod = "WEEK";
                                    break;
                                case 'Y':
                                    $payperiod = "YEAR";
                                    break;
                            }

                            $recur_interval = $recurring_data['interval'];
                            $recur_cycles = $recurring_data['cycles'];

                            if ($recur_cycles == '') {
                                $recur_cycles = 0;
                            }
                            if ($recur_cycles > 0 && $plan_action == 'new_subscription' && !$plan->has_trial_period()) {
                                $recur_cycles = $recur_cycles - 1;
                            }
                            $getTrialDate = false;
                            if ($plan_action == 'new_subscription') {
                                $getTrialDate = true;
                            }
                            $plan_start_date = date("mdY", $arm_members_class->arm_get_start_date_for_auto_debit_plan($plan->ID, $getTrialDate));
                            $paypal_array = array(
                                'TENDER' => 'C',
                                'TRXTYPE' => 'R',
                                'ACTION' => 'A',
                                'ACCT' => $card_number,
                                'PROFILENAME' => $user_email_add,
                                'AMT' => $amount,
                                'START' => $plan_start_date,
                                'TERM' => $recur_cycles,
                                'PAYPERIOD' => $payperiod,
                                'FREQUENCY' => $recur_interval,
                                'NAME' => $card_holder_name,
                            );
                            $auth_amount = 0;
                            if ($plan->has_trial_period()) {
                                $is_trial = true;
                                $arm_is_trial = '1';
                                $trial_amount = $plan->options['trial']['amount'];
                                $trial_period = $plan->options['trial']['period'];
                                $trial_interval = $plan->options['trial']['interval'];
                            } else {
                                $trial_amount = $amount;
                            }
                            if (!empty($coupon_amount) && $coupon_amount > 0) {
                                $trial_amount = $discount_amt;
                                if (!$is_trial) {
                                    $recur_cycles = ($recur_cycles > 1) ? $recur_cycles - 1 : 1;
                                    $is_trial = true;
                                    $arm_is_trial = '1';
                                    $plan_action = 'new_subscription';
                                    $trial_interval = $recur_interval;
                                    $trial_period = $recur_period;
                                }
                            }
                            $auth_amount = $trial_amount;
                        } else if ($is_recurring && $plan_payment_mode == 'manual_subscription') {
                            $recurring_data = $plan->prepare_recurring_data();
                            $recur_period = $recurring_data['period'];
                            $recur_interval = $recurring_data['interval'];
                            $recur_cycles = $recurring_data['cycles'];
                            /* Trial Period Options */
                            $is_trial = false;
                            $arm_is_trial = '0';
                            $allow_trial = true;
                            if (is_user_logged_in()) {
                                $user_id = get_current_user_id();
                                $user_plan = get_user_meta($user_id, 'arm_user_plan', true);
                                $user_plan_id = $user_plan;
                                if ($user_plan != '' || $user_plan != 0) {
                                    $allow_trial = false;
                                }
                            }
                            if ($plan->has_trial_period() && $allow_trial) {
                                $is_trial = true;
                                $arm_is_trial = '1';
                                $trial_amount = $plan->options['trial']['amount'];
                                $trial_period = $plan->options['trial']['period'];
                                $trial_interval = $plan->options['trial']['interval'];
                            } else {
                                $trial_amount = $amount;
                            }
                            if (!empty($coupon_amount) && $coupon_amount > 0) {
                                $trial_amount = $discount_amt;
                                if (!$is_trial) {
                                    $recur_cycles = ($recur_cycles > 1) ? $recur_cycles - 1 : 1;
                                    $is_trial = true;
                                    $arm_is_trial = '1';
                                    $plan_action = 'new_subscription';
                                    $trial_interval = $recur_interval;
                                    $trial_period = $recur_period;
                                }
                            }
                            $paypal_array = array(
                                'TENDER' => 'C',
                                'TRXTYPE' => 'D',
                                'ACCT' => $card_number,
                                'EXPDATE' => $card_expire,
                                'CVV2' => $cvc,
                                'AMT' => $trial_amount,
                                'CURRENCY' => $currency,
                                'VERBOSITY' => 'MEDIUM',
                                'NAME' => $card_holder_name,
                            );
                            if (!empty($coupon_amount) && $coupon_amount > 0) {
                                $paypal_array['AMT'] = $discount_amt;
                            }
                            $auth_amount = $paypal_array['AMT'];
                            if ($paypal_array['AMT'] == 0 || $paypal_array['AMT'] == '0.00') {
                                $return_array = array();
                                $return_array['arm_plan_id'] = $plan->ID;
                                $return_array['arm_first_name'] =   $arm_first_name;
                                $return_array['arm_last_name']  =   $arm_last_name;
                                $return_array['arm_payment_gateway'] = 'paypal_pro';
                                $return_array['arm_payment_type'] = $plan->payment_type;
                                $return_array['arm_token'] = '-';
                                $return_array['arm_payer_email'] = (isset($posted_data['user_email'])) ? $posted_data['user_email'] : '';
                                $return_array['arm_receiver_email'] = '';
                                $return_array['arm_transaction_id'] = '-';
                                $return_array['arm_transaction_payment_type'] = $plan->payment_type;
                                $return_array['arm_transaction_status'] = 'completed';
                                $return_array['arm_payment_mode'] = '';
                                $return_array['arm_payment_date'] = date('Y-m-d H:i:s');
                                $return_array['arm_amount'] = 0;
                                $return_array['arm_currency'] = 'USD';
                                $return_array['arm_coupon_code'] = @$coupon_code;
                                $return_array['arm_response_text'] = '';
                                $return_array['arm_extra_vars'] = '';
                                $return_array['arm_is_trial'] = $arm_is_trial;
                                $return_array['arm_created_date'] = current_time('mysql');
                                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($return_array);
                                $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                                $paid_trial_stripe_payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id, 'gateway' => 'paypal_pro');
                                return $payment_done;
                            }
                        } else {
                            $paypal_array = array(
                                'TENDER' => 'C',
                                'TRXTYPE' => 'D',
                                'ACCT' => $card_number,
                                'EXPDATE' => $card_expire,
                                'CVV2' => $cvc,
                                'AMT' => $amount,
                                'CURRENCY' => $currency,
                                'VERBOSITY' => 'MEDIUM',
                                'NAME' => $card_holder_name,
                            );
                            $auth_amount = $amount;
                            if (!empty($coupon_amount) && $coupon_amount > 0) {
                                $paypal_array['AMT'] = $discount_amt;
                                $auth_amount = $discount_amt;
                            }
                            if ($paypal_array['AMT'] == 0 || $paypal_array['AMT'] == '0.00') {
                                $return_array = array();
                                $return_array['arm_plan_id'] = $plan->ID;
                                $return_array['arm_first_name'] =   $arm_first_name;
                                $return_array['arm_last_name']  =   $arm_last_name;
                                $return_array['arm_payment_gateway'] = 'paypal_pro';
                                $return_array['arm_payment_type'] = $plan->payment_type;
                                $return_array['arm_token'] = '-';
                                $return_array['arm_payer_email'] = (isset($posted_data['user_email'])) ? $posted_data['user_email'] : '';
                                $return_array['arm_receiver_email'] = '';
                                $return_array['arm_transaction_id'] = '-';
                                $return_array['arm_transaction_payment_type'] = $plan->payment_type;
                                $return_array['arm_transaction_status'] = 'completed';
                                $return_array['arm_payment_mode'] = '';
                                $return_array['arm_payment_date'] = date('Y-m-d H:i:s');
                                $return_array['arm_amount'] = 0;
                                $return_array['arm_currency'] = 'USD';
                                $return_array['arm_coupon_code'] = @$coupon_code;
                                $return_array['arm_response_text'] = '';
                                $return_array['arm_extra_vars'] = '';
                                $return_array['arm_created_date'] = current_time('mysql');
                                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($return_array);
                                $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                                $paid_trial_stripe_payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id, 'gateway' => 'paypal_pro');
                                return $payment_done;
                            }
                        }

                        if ($auth_amount > 0 && $auth_amount != '') {
                            $payflow_array['AMT'] = $auth_amount;
                        }
                        if (is_array($payflow_array)) {
                            foreach ($payflow_array as $key => $value) {
                                if (is_array($value)) {
                                    foreach ($value as $item) {
                                        if (strlen($nvpstr_) > 0)
                                            $nvpstr_ .= "&";
                                        $nvpstr_ .= "$key=" . $item;
                                    }
                                } else {
                                    if (strlen($nvpstr_) > 0)
                                        $nvpstr_ .= "&";
                                    $nvpstr_ .= "$key=" . $value;
                                }
                            }
                        }

                        $nvpreq_ = "VENDOR=$api_vendor&PARTNER=$api_partner&PWD=$api_password&USER=$api_username&$nvpstr_";

                        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq_);

                        $response = curl_exec($ch);
                        $headers = curl_getinfo($ch);
                        curl_close($ch);
                        $formatted_response = $this->arm_get_formatted_response($response);
                        $armref_id = "";
                        $ARMember->arm_write_response(" Authorization Response <br/> " . maybe_serialize($formatted_response));
                        if ($formatted_response['RESULT'] == "0" && $formatted_response['PNREF'] != "") {
                            $armref_id = $formatted_response['PNREF'];
                            $paypal_array['ORIGID'] = $armref_id;
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                            curl_setopt($ch, CURLOPT_VERBOSE, 1);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_POST, 1);

                            $nvpstr = "";
                            if (is_array($paypal_array)) {
                                foreach ($paypal_array as $key => $value) {
                                    if (is_array($value)) {
                                        foreach ($value as $item) {
                                            if (strlen($nvpstr) > 0)
                                                $nvpstr .= "&";
                                            $nvpstr .= "$key=" . $item;
                                        }
                                    } else {
                                        if (strlen($nvpstr) > 0)
                                            $nvpstr .= "&";
                                        $nvpstr .= "$key=" . $value;
                                    }
                                }
                            }

                            $nvpreq = "VENDOR=$api_vendor&PARTNER=$api_partner&PWD=$api_password&USER=$api_username&$nvpstr";

                            curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

                            $response = curl_exec($ch);
                            $headers = curl_getinfo($ch);
                            curl_close($ch);

                            $formatted_response = $this->arm_get_formatted_response($response);
                            $ARMember->arm_write_response(" capture Response <br/> " . maybe_serialize($formatted_response));
                            if ($formatted_response['RESULT'] == 0) {
                                $txn_id = ($is_recurring && $plan_payment_mode == 'auto_debit_subscription') ? $formatted_response['PROFILEID'] : $formatted_response['PNREF'];
                                $rec_profile_id = ($is_recurring && $plan_payment_mode == 'auto_debit_subscription') ? $formatted_response['PROFILEID'] : '';
                                $payment_date = ($is_recurring && $plan_payment_mode == 'auto_debit_subscription') ? $plan_start_date : current_time('mysql');
                                $display_log = ($is_recurring && $plan_payment_mode == 'auto_debit_subscription') ? 0 : 1;
                                $arm_is_trial = '0';
                                if ($is_recurring && $plan_payment_mode == 'auto_debit_subscription' && $plan->has_trial_period()) {
                                    $arm_is_trial = '1';
                                    if (is_user_logged_in()) {
                                        $user_id = get_current_user_id();
                                        $user_plan = get_user_meta($user_id, 'arm_user_plan', true);
                                        $user_plan_id = $user_plan;
                                        if ($user_plan != '' || $user_plan != 0) {
                                            $arm_is_trial = '0';
                                        }
                                    }
                                }
                                $coupon_code = isset($posted_data['arm_coupon_code']) ? $posted_data['arm_coupon_code'] : '';
                                if($display_log == 0){
                                    $coupon_code = '';
                                    
                                }
                                $payment_data_captured = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan->ID,
                                    'arm_first_name' =>  $arm_first_name,
                                    'arm_last_name'  =>   $arm_last_name,
                                    'arm_payment_gateway' => 'paypal_pro',
                                    'arm_payment_type' => $plan->payment_type,
                                    'arm_payer_email' => $user_email_add,
                                    'arm_receiver_email' => '',
                                    'arm_transaction_id' => $txn_id,
                                    'arm_token' => '',
                                    'arm_transaction_payment_type' => $plan->payment_type,
                                    'arm_transaction_status' => ($formatted_response['RESPMSG'] == 'Approved') ? 'completed' : 'pending',
                                    'arm_payment_date' => $payment_date,
                                    'arm_amount' => floatval($amount),
                                    'arm_currency' => $currency,
                                    'arm_coupon_code' => $coupon_code,
                                    'arm_response_text' => (!empty($response)) ? maybe_serialize((array) $response) : '',
                                    'arm_created_date' => current_time('mysql'),
                                    'arm_is_trial' => $arm_is_trial,
                                    'arm_display_log' => $display_log
                                );

                                if ($plan_action == 'renew_subscription') {
                                    $active_plan = get_user_meta($user_id, 'arm_user_plan', true);
                                    if ($active_plan != $plan_id) {
                                        
                                    }
                                }

                                $extraParam['profileID'] = $txn_id;

                                /* One time Payment for first occurrence */
                                if (( $is_recurring && $plan_payment_mode == 'auto_debit_subscription')) {
                                    if ($plan->has_trial_period()) {
                                        $amount = $plan->options['trial']['amount'];
                                    }
                                    if (!empty($coupon_amount) && $coupon_amount > 0) {
                                        $amount = $discount_amt;
                                    }
                                    $paypal_array_onetime = array(
                                        'TENDER' => 'C',
                                        'TRXTYPE' => 'D',
                                        'ORIGID' => $armref_id,
                                        'ACCT' => $card_number,
                                        'EXPDATE' => $card_expire,
                                        'CVV2' => $cvc,
                                        'AMT' => $amount,
                                        'CURRENCY' => $currency,
                                        'VERBOSITY' => 'MEDIUM',
                                        'NAME' => $card_holder_name,
                                    );
                                    if (($plan->has_trial_period() && ($amount == 0 || $amount == '0.00') || ($discount_amt != '' && ($discount_amt == 0 || $discount_amt == '0.00')))) {
                                        $return_array = array();
                                        $return_array['arm_plan_id'] = $plan->ID;
                                        $return_array['arm_first_name'] =   $arm_first_name;
                                        $return_array['arm_last_name']  =   $arm_last_name;
                                        $return_array['arm_payment_gateway'] = 'paypal_pro';
                                        $return_array['arm_payment_type'] = $plan->payment_type;
                                        $return_array['arm_token'] = '-';
                                        $return_array['arm_payer_email'] = (isset($posted_data['user_email'])) ? $posted_data['user_email'] : '';
                                        $return_array['arm_receiver_email'] = '';
                                        $return_array['arm_transaction_id'] = '-';
                                        $return_array['arm_transaction_payment_type'] = $plan->payment_type;
                                        $return_array['arm_transaction_status'] = 'completed';
                                        $return_array['arm_payment_mode'] = '';
                                        $return_array['arm_payment_date'] = date('Y-m-d H:i:s');
                                        $return_array['arm_amount'] = 0;
                                        $return_array['arm_currency'] = 'USD';
                                        $return_array['arm_coupon_code'] = @$coupon_code;
                                        $return_array['arm_response_text'] = '';
                                        $return_array['arm_extra_vars'] = '';
                                        $return_array['arm_is_trial'] = $arm_is_trial;
                                        $return_array['arm_created_date'] = current_time('mysql');
                                        $payment_log_id = $arm_payment_gateways->arm_save_payment_log($return_array);
                                        $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                                        $paid_trial_stripe_payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id, 'gateway' => 'paypal_pro');
                                    } else {
                                        $ch = curl_init();
                                        curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                                        curl_setopt($ch, CURLOPT_VERBOSE, 1);
                                        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                        curl_setopt($ch, CURLOPT_POST, 1);

                                        $nvpstrOnetime = "";
                                        if (is_array($paypal_array_onetime)) {
                                            foreach ($paypal_array_onetime as $key => $value) {
                                                if (is_array($value)) {
                                                    foreach ($value as $item) {
                                                        if (strlen($nvpstr) > 0)
                                                            $nvpstrOnetime .= "&";
                                                        $nvpstrOnetime .= "$key=" . $item;
                                                    }
                                                } else {
                                                    if (strlen($nvpstr) > 0)
                                                        $nvpstrOnetime .= "&";
                                                    $nvpstrOnetime .= "$key=" . $value;
                                                }
                                            }
                                        }

                                        $nvpreqOnetime = "VENDOR=$api_vendor&PARTNER=$api_partner&PWD=$api_password&USER=$api_username&$nvpstrOnetime";

                                        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreqOnetime);

                                        $responseOnetime = curl_exec($ch);
                                        $headers = curl_getinfo($ch);
                                        curl_close($ch);

                                        $formatted_response = $this->arm_get_formatted_response($responseOnetime);

                                        if ($formatted_response['RESULT'] == 0) {
                                            $payment_data_onetime = array(
                                                'arm_user_id' => $user_id,
                                                'arm_plan_id' => $plan->ID,
                                                'arm_first_name' =>   $arm_first_name,
                                                'arm_last_name'  =>   $arm_last_name,
                                                'arm_payment_gateway' => 'paypal_pro',
                                                'arm_payment_type' => $plan->payment_type,
                                                'arm_payer_email' => $user_email_add,
                                                'arm_receiver_email' => '',
                                                'arm_transaction_id' => $formatted_response['PNREF'],
                                                'arm_token' => '',
                                                'arm_transaction_payment_type' => $plan->payment_type,
                                                'arm_transaction_status' => ($formatted_response['RESPMSG'] == 'Approved') ? 'completed' : 'pending',
                                                'arm_payment_date' => current_time('mysql'),
                                                'arm_amount' => floatval($amount),
                                                'arm_currency' => $currency,
                                                'arm_coupon_code' => isset($posted_data['arm_coupon_code']) ? $posted_data['arm_coupon_code'] : '',
                                                'arm_response_text' => (!empty($response)) ? maybe_serialize((array) $response) : '',
                                                'arm_created_date' => current_time('mysql'),
                                                'arm_display_log' => '1'
                                            );

                                            $payment_data_onetime['arm_extra_vars'] = maybe_serialize($extraParam);
                                            $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data_onetime);
                                            $payment_done = array();
                                            if ($payment_log_id) {
                                                $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                                                $paid_trial_stripe_payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id, 'gateway' => 'paypal_pro');
                                            } else {
                                                $err_msg = @$arm_global_settings->common_message['arm_payment_fail_paypal_pro'];
                                                $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);
                                                $payment_done = array('status' => FALSE, 'error' => $err_msg);
                                                return $payment_done;
                                            }
                                        } else {
                                            /* Cancel started subscription */
                                            $cancel_params = "TRXTYPE=R&TENDER=C&PARTNER=" . $api_partner . "&VENDOR=" . $api_vendor . "&USER=" . $api_user . "&PWD=" . $api_password . "&ACTION=C&ORIGPROFILEID=" . $rec_profile_id;

                                            $ch_cancel = curl_init();
                                            curl_setopt($ch_cancel, CURLOPT_URL, $api_endpoint);
                                            curl_setopt($ch_cancel, CURLOPT_VERBOSE, 1);
                                            curl_setopt($ch_cancel, CURLOPT_TIMEOUT, 0);
                                            curl_setopt($ch_cancel, CURLOPT_SSL_VERIFYPEER, FALSE);
                                            curl_setopt($ch_cancel, CURLOPT_SSL_VERIFYHOST, FALSE);
                                            curl_setopt($ch_cancel, CURLOPT_RETURNTRANSFER, 1);
                                            curl_setopt($ch_cancel, CURLOPT_POST, 1);
                                            curl_setopt($ch_cancel, CURLOPT_POSTFIELDS, $cancel_params);
                                            $responseCancel = curl_exec($ch_cancel);
                                            curl_close($ch_cancel);

                                            $cancel_formatted_response = $this->arm_get_formatted_response($responseCancel);

                                            $actual_error = isset($cancel_formatted_response['RESPMSG']) ? $cancel_formatted_response['RESPMSG'] : '';
                                            $actual_error = !empty($actual_error) ? $actual_error.' '.$arm_help_link : '';

                                            $err_msg = @$arm_global_settings->common_message['arm_payment_fail_paypal_pro'];
                                            $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);

                                            $actualmsg = ($arm_paypal_pro_enable_debug_mode == '1') ? $actual_error : $err_msg;
                                            $payment_done = array('status' => FALSE, 'error' => $actualmsg);
                                            return $payment_done;
                                        }
                                    }
                                }
                            } else {
                                $actual_error = isset($formatted_response['RESPMSG']) ? $formatted_response['RESPMSG'] : '';
                                $actual_error = !empty($actual_error) ? $actual_error.' '.$arm_help_link : '';

                                $err_msg = @$arm_global_settings->common_message['arm_payment_fail_paypal_pro'];
                                $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);
                                $actualmsg = ($arm_paypal_pro_enable_debug_mode == '1') ? $actual_error : $err_msg;
                                $payment_done = array('status' => FALSE, 'error' => $actualmsg);
                            }
                            if (!empty($payment_data_captured)) {
                                $payment_data_captured['arm_extra_vars'] = maybe_serialize($extraParam);
                                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data_captured);
                                $payment_done = array();
                                if ($payment_log_id) {
                                    $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                                } else {
                                    $err_msg = @$arm_global_settings->common_message['arm_payment_fail_paypal_pro'];
                                    $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);
                                    $payment_done = array('status' => FALSE, 'error' => $err_msg);
                                }
                            } else {
                                $err_msg = @$arm_global_settings->common_message['arm_payment_fail_paypal_pro'];
                                $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);
                                $payment_done = array('status' => FALSE, 'error' => $err_msg);
                            }
                        } else {
                            $actual_error = isset($formatted_response['RESPMSG']) ? $formatted_response['RESPMSG'] : '';
                            $actual_error = !empty($actual_error) ? $actual_error.' '.$arm_help_link : '';
                            $err_msg = @$arm_global_settings->common_message['arm_unauthorized_paypal_pro_credit_card'];
                            $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro.', ARM_PAYPALPRO_TXTDOMAIN);
                            $actualmsg = ($arm_paypal_pro_enable_debug_mode == '1') ? $actual_error : $err_msg;
                            $payment_done = array('status' => FALSE, 'error' => $actualmsg);
                            //return $payment_done;
                        }
                        return $payment_done;
                    }
                } else {
                    
                }
            } else {
                
            }
        }
        
        function arm2_payment_gateway_form_submit_action($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0) {
            global $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_manage_coupons, $payment_done, $arm_payment_gateways, $arm_transaction, $arm_members_class, $paid_trial_stripe_payment_done;
            $all_payment_gateways = $arm_payment_gateways->arm_get_active_payment_gateways();
            $currency = $arm_payment_gateways->arm_get_global_currency();
            if ($payment_gateway == 'paypal_pro' && isset($all_payment_gateways['paypal_pro']) && !empty($all_payment_gateways['paypal_pro'])) {
                $entry_data = $arm_payment_gateways->arm_get_entry_data_by_id($entry_id);
                if (!empty($entry_data) && !empty($posted_data[$payment_gateway])) {
                    $user_email_add = $entry_data['arm_entry_email'];
                    $user_id = $entry_data['arm_user_id'];
                    $form_id = $entry_data['arm_form_id'];
                    $first_name = $posted_data['first_name'];
                    $last_name = $posted_data['last_name'];
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
                    
                    $plan_payment_mode_ = !empty($posted_data['arm_payment_mode']['paypal_pro']) ? $posted_data['arm_payment_mode']['paypal_pro'] : 'both';
                    $plan_payment_mode = "manual_subscription";
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
                        if ($plan_payment_mode_ == 'both') {
                            $plan_payment_mode = !empty($posted_data['arm_selected_payment_mode']) ? $posted_data['arm_selected_payment_mode'] : "manual_subscription";
                        } else {
                            $plan_payment_mode = $plan_payment_mode_;
                        }
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
                                $user_subsdata = $planData['arm_paypal_pro'];
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

                    $description = "Plan id => {$plan_id} submitted by {$first_name} {$last_name}";
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
                    
                    $autho_options = $all_payment_gateways['paypal_pro'];

                    $current_payment_gateway = (isset($posted_data['payment_gateway'])) ? $posted_data['payment_gateway'] : '';
                    if ($current_payment_gateway == '') {
                        $current_payment_gateway = (isset($posted_data['_payment_gateway'])) ? $posted_data['_payment_gateway'] : '';
                    }
                    $paypal_pro_card_detail = $posted_data[$current_payment_gateway];

                    $card_holder_name = $paypal_pro_card_detail['card_holder_name'];
                    $card_number = $paypal_pro_card_detail['card_number'];
                    $exp_month = $paypal_pro_card_detail['exp_month'];
                    $exp_year = $paypal_pro_card_detail['exp_year'];
                    $cvc = $paypal_pro_card_detail['cvc'];
                    $payment_type = isset($payment_gateway_options['paypal_pro_payment_type']) ? $payment_gateway_options['paypal_pro_payment_type'] : 'payflow_pro';
                    $payment_mode = $payment_mode_method = $payment_gateway_options['paypal_pro_payment_mode'];
                    $is_sandbox_mode = $payment_mode == "sandbox" ? true : false;

                    if ($payment_type == 'payflow_pro') {
                        $api_endpoint = ( $is_sandbox_mode ) ? "https://pilot-payflowpro.paypal.com" : "https://payflowpro.paypal.com";
                        $api_username = ( $is_sandbox_mode ) ? $payment_gateway_options['payflow_pro_sandbox_username'] : $payment_gateway_options['payflow_pro_username'];
                        $api_password = ( $is_sandbox_mode ) ? $payment_gateway_options['payflow_pro_sandbox_password'] : $payment_gateway_options['payflow_pro_password'];
                        $api_vendor = ( $is_sandbox_mode ) ? $payment_gateway_options['payflow_pro_sandbox_vendor'] : $payment_gateway_options['payflow_pro_vendor'];
                        $api_partner = ( $is_sandbox_mode ) ? $payment_gateway_options['payflow_pro_sandbox_partner'] : $payment_gateway_options['payflow_pro_partner'];

                        $card_expire = $exp_month . $exp_year;

                        $arm_paypal_pro_enable_debug_mode = isset($payment_gateway_options['enable_debug_mode']) ? $payment_gateway_options['enable_debug_mode'] : 0;
                        $arm_help_link = '<a href="https://www.paypalobjects.com/en_AU/vhelp/paypalmanager_help/result_values_for_transaction_declines_or_errors.htm" target="_blank">'.__('Click Here', ARM_PAYPALPRO_TXTDOMAIN).'</a>';
                        $maskCCNum = $arm_transaction->arm_mask_credit_card_number($card_number);

                        $extraParam = array('card_number' => $maskCCNum, 'plan_amount' => $amount, 'paid_amount' => $amount);
                        $extraParam['payment_type'] = $payment_type;
                        $extraParam['payment_mode'] = ( $is_sandbox_mode ) ? "sandbox" : 'live';
                        /* Coupon Details */
                        $arm_coupon_discount = $arm_coupon_discount_type = $coupon_code = '';
                        $discount_amt = $amount;
                        $arm_coupon_on_each_subscriptions = $coupon_amount = 0;

                        if ($arm_manage_coupons->isCouponFeature && isset($posted_data['arm_coupon_code']) && !empty($posted_data['arm_coupon_code'])) {
                            
                            $couponApply = $arm_manage_coupons->arm_apply_coupon_code($posted_data['arm_coupon_code'], $plan, $setup_id, $payment_cycle, $arm_user_old_plan);

                            if($couponApply["status"] == "success") {
                                $coupon_code = $posted_data['arm_coupon_code'];
                                $coupon_amount = isset($couponApply['coupon_amt']) ? $couponApply['coupon_amt'] : 0;
                                $discount_amt = isset($couponApply['total_amt']) ? $couponApply['total_amt'] : $amount;
                                $arm_coupon_discount = (isset($couponApply['discount']) && !empty($couponApply['discount'])) ? $couponApply['discount'] : 0;
                                $arm_coupon_discount_type = ($couponApply['discount_type'] != 'percentage') ? $currency : "%";
                                $arm_coupon_on_each_subscriptions = isset($couponApply['arm_coupon_on_each_subscriptions']) ? $couponApply['arm_coupon_on_each_subscriptions'] : 0;
				
                                //if (!empty($coupon_amount) && $coupon_amount > 0) {
                                $coupon_code = $posted_data['arm_coupon_code'];
                                $extraParam['coupon'] = array(
                                    'coupon_code' => $posted_data['arm_coupon_code'],
                                    'amount' => $coupon_amount,
                                    'arm_coupon_on_each_subscriptions' => $arm_coupon_on_each_subscriptions,
                                );
                            }
                        } else {
                            $posted_data['arm_coupon_code'] = '';
                        }
                        $payment_data = array();

                        /* Authorizing Card Details */
                        $payflow_array = array(
                            'TENDER' => 'C',
                            'TRXTYPE' => 'A',
                            'ACCT' => $card_number,
                            'EXPDATE' => $card_expire,
                            'CVV2' => $cvc,
                            'AMT' => $amount,
                            'CURRENCY' => $currency,
                            'VERBOSITY' => 'MEDIUM',
                            'NAME' => $card_holder_name,
                        );

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                        curl_setopt($ch, CURLOPT_VERBOSE, 1);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        $nvpstr_ = "";
                        
                        if ($is_recurring && $plan_payment_mode == 'auto_debit_subscription') {
                           
                             $tax_amount = 0;
                            $recurring_data = $plan->prepare_recurring_data($payment_cycle);
                            $recur_period = $recurring_data['period'];
                            $payperiod = "MONT";
                            switch ($recur_period) {
                                case 'M':
                                    $payperiod = "MONT";
                                    break;
                                case 'D':
                                    $payperiod = "DAYS";
                                    break;
                                case 'W':
                                    $payperiod = "WEEK";
                                    break;
                                case 'Y':
                                    $payperiod = "YEAR";
                                    break;
                            }

                            $recur_interval = $recurring_data['interval'];
                            $recur_cycles = $recurring_data['cycles'];

                            if ($recur_cycles == '') {
                                $recur_cycles = 0;
                            }
                            if ($recur_cycles > 0 && $plan_action == 'new_subscription' && !$plan->has_trial_period()) {
                                $recur_cycles = $recur_cycles - 1;
                            }
                            $getTrialDate = false;
                            if ($plan_action == 'new_subscription') {
                                $getTrialDate = true;
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
                            $plan_start_date = $arm_members_class->arm_get_start_date_for_auto_debit_plan($plan->ID, $getTrialDate, $payment_cycle, $plan_action, $user_id);
                            $paypal_array = array(
                                'TENDER' => 'C',
                                'TRXTYPE' => 'R',
                                'ACTION' => 'A',
                                'ACCT' => $card_number,
                                'PROFILENAME' => $user_email_add,
                                'AMT' => $amount,
                                'START' => date("mdY", $plan_start_date),
                                'TERM' => $recur_cycles,
                                'PAYPERIOD' => $payperiod,
                                'FREQUENCY' => $recur_interval,
                                'NAME' => $card_holder_name,
                            );
                            
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
                                    $is_trial = true;
                                    $arm_is_trial = '1';
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
                            $auth_amount = $trial_amount;
                            
                        } 
                        else if ($is_recurring && $plan_payment_mode == 'manual_subscription') {
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
                                    $is_trial = true;
                                    $arm_is_trial = '1';
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

                            $paypal_array = array(
                                'TENDER' => 'C',
                                'TRXTYPE' => 'D',
                                'ACCT' => $card_number,
                                'EXPDATE' => $card_expire,
                                'CVV2' => $cvc,
                                'AMT' => $trial_amount,
                                'CURRENCY' => $currency,
                                'VERBOSITY' => 'MEDIUM',
                                'NAME' => $card_holder_name,
                            );
                            $auth_amount = $paypal_array['AMT'];
                            
                            if ($auth_amount== 0 || $auth_amount == '0.00') {
                                $return_array = array();
                                $return_array['arm_plan_id'] = $plan->ID;
                                $return_array['arm_first_name'] =   $arm_first_name;
                                $return_array['arm_last_name']  =   $arm_last_name;
                                $return_array['arm_payment_gateway'] = 'paypal_pro';
                                $return_array['arm_payment_type'] = $plan->payment_type;
                                $return_array['arm_token'] = '-';
                                $return_array['arm_payer_email'] = (isset($posted_data['user_email'])) ? $posted_data['user_email'] : '';
                                $return_array['arm_receiver_email'] = '';
                                $return_array['arm_transaction_id'] = '-';
                                $return_array['arm_transaction_payment_type'] = $plan->payment_type;
                                $return_array['arm_transaction_status'] = 'completed';
                                $return_array['arm_payment_mode'] = '';
                                $return_array['arm_payment_date'] = date('Y-m-d H:i:s');
                                $return_array['arm_amount'] = 0;
                                $return_array['arm_currency'] = $currency;
                                $return_array['arm_coupon_code'] = @$coupon_code;
                                $return_array['arm_coupon_discount'] = @$arm_coupon_discount;
                                $return_array['arm_coupon_discount_type'] = @$arm_coupon_discount_type;
                                $return_array['arm_response_text'] = '';
                                $return_array['arm_extra_vars'] = '';
                                $return_array['arm_is_trial'] = $arm_is_trial;
                                $return_array['arm_created_date'] = current_time('mysql');
                                $return_array['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($return_array);
                                $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);

                                if($arm_manage_coupons->isCouponFeature && isset($posted_data['arm_coupon_code']) && !empty($posted_data['arm_coupon_code']) && !empty($arm_coupon_on_each_subscriptions)) {
                                    $payment_done["coupon_on_each"] = TRUE;
                                    $payment_done["trans_log_id"] = $payment_log_id;
                                }

                                $paid_trial_stripe_payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id, 'gateway' => 'paypal_pro');
                                return $payment_done;
                            }
                        } 
                        else {
                            $paypal_array = array(
                                'TENDER' => 'C',
                                'TRXTYPE' => 'D',
                                'ACCT' => $card_number,
                                'EXPDATE' => $card_expire,
                                'CVV2' => $cvc,
                                'AMT' => $amount,
                                'CURRENCY' => $currency,
                                'VERBOSITY' => 'MEDIUM',
                                'NAME' => $card_holder_name,
                            );
                            
                            
                            $auth_amount = $amount;
                            if (!empty($coupon_amount) && $coupon_amount > 0) {
                                $auth_amount = $discount_amt;
                            }
                            
                            $auth_amount = str_replace(",", "", $auth_amount);
                            $tax_amount = 0;
                            if($tax_percentage > 0){
                                $tax_amount = ($tax_percentage*$auth_amount)/100;
                                $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                                $auth_amount = $auth_amount+$tax_amount;
                            }
                             $extraParam['tax_amount'] = $tax_amount;
                            $auth_amount = number_format((float)$auth_amount, 2, '.','');
                            
                            $paypal_array['AMT'] = $auth_amount;
                            
                            if ($paypal_array['AMT'] == 0 || $paypal_array['AMT'] == '0.00') {
                                $return_array = array();
                                $return_array['arm_plan_id'] = $plan->ID;
                                $return_array['arm_first_name'] =   $arm_first_name;
                                $return_array['arm_last_name']  =   $arm_last_name;
                                $return_array['arm_payment_gateway'] = 'paypal_pro';
                                $return_array['arm_payment_type'] = $plan->payment_type;
                                $return_array['arm_token'] = '-';
                                $return_array['arm_payer_email'] = (isset($posted_data['user_email'])) ? $posted_data['user_email'] : '';
                                $return_array['arm_receiver_email'] = '';
                                $return_array['arm_transaction_id'] = '-';
                                $return_array['arm_transaction_payment_type'] = $plan->payment_type;
                                $return_array['arm_transaction_status'] = 'completed';
                                $return_array['arm_payment_mode'] = '';
                                $return_array['arm_payment_date'] = date('Y-m-d H:i:s');
                                $return_array['arm_amount'] = 0;
                                $return_array['arm_currency'] = $currency;
                                $return_array['arm_coupon_code'] = @$coupon_code;
                                $return_array['arm_coupon_discount'] = @$arm_coupon_discount;
                                $return_array['arm_coupon_discount_type'] = @$arm_coupon_discount_type;
                                $return_array['arm_response_text'] = '';
                                $return_array['arm_extra_vars'] = '';
                                $return_array['arm_created_date'] = current_time('mysql');
                                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($return_array);
                                $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                                $paid_trial_stripe_payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id, 'gateway' => 'paypal_pro');
                                return $payment_done;
                            }
                            $trial_amount = $auth_amount;
                        }

                        if ($auth_amount > 0 && $auth_amount != '') {
                            $payflow_array['AMT'] = $auth_amount;
                        }
                        if (is_array($payflow_array)) {
                            foreach ($payflow_array as $key => $value) {
                                if (is_array($value)) {
                                    foreach ($value as $item) {
                                        if (strlen($nvpstr_) > 0)
                                            $nvpstr_ .= "&";
                                        $nvpstr_ .= "$key=" . $item;
                                    }
                                } else {
                                    if (strlen($nvpstr_) > 0)
                                        $nvpstr_ .= "&";
                                    $nvpstr_ .= "$key=" .$value;
                                }
                            }
                        }

                        $nvpreq_ = "VENDOR=$api_vendor&PARTNER=$api_partner&PWD=$api_password&USER=$api_username&$nvpstr_";

                        //$ARMember->arm_write_response($nvpreq_); 
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq_);

                        $response = curl_exec($ch);
                        $headers = curl_getinfo($ch);
                        curl_close($ch);
                        $formatted_response = $this->arm_get_formatted_response($response);
                        
                        $armref_id = "";
                        $ARMember->arm_write_response(" Authorization Response 1 <br/> " . maybe_serialize($formatted_response));
                        if ($formatted_response['RESULT'] == "0" && $formatted_response['PNREF'] != "") {
                            $armref_id = $formatted_response['PNREF'];
                            $paypal_array['ORIGID'] = $armref_id;
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                            curl_setopt($ch, CURLOPT_VERBOSE, 1);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_POST, 1);

                            $nvpstr = "";
                            if (is_array($paypal_array)) {
                                foreach ($paypal_array as $key => $value) {
                                    if (is_array($value)) {
                                        foreach ($value as $item) {
                                            if (strlen($nvpstr) > 0)
                                                $nvpstr .= "&";
                                            $nvpstr .= "$key=" . item;
                                        }
                                    } else {
                                        if (strlen($nvpstr) > 0)
                                            $nvpstr .= "&";
                                        $nvpstr .= "$key=" . $value;
                                    }
                                }
                            }

                            $nvpreq = "VENDOR=$api_vendor&PARTNER=$api_partner&PWD=$api_password&USER=$api_username&$nvpstr";

                            curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

                            $response = curl_exec($ch);
                            $headers = curl_getinfo($ch);
                            curl_close($ch);

                            $formatted_response = $this->arm_get_formatted_response($response);
                            $ARMember->arm_write_response(" capture Response 1 <br/> " . maybe_serialize($formatted_response));
                            if ($formatted_response['RESULT'] == 0) {
                                $txn_id = ($is_recurring && $plan_payment_mode == 'auto_debit_subscription') ? $formatted_response['PROFILEID'] : $formatted_response['PNREF'];
                                $rec_profile_id = ($is_recurring && $plan_payment_mode == 'auto_debit_subscription') ? $formatted_response['PROFILEID'] : '';
                                $payment_date = ($is_recurring && $plan_payment_mode == 'auto_debit_subscription') ? date('Y-m-d H:i:s', $plan_start_date) : current_time('mysql');
                                $display_log = ($is_recurring && $plan_payment_mode == 'auto_debit_subscription') ? 0 : 1;
                                
                                $arm_is_trial = '0';
                                if ($is_recurring && $plan_payment_mode == 'auto_debit_subscription' && $plan->has_trial_period()) {
                                    $arm_is_trial = '1';
                                    if (is_user_logged_in()) {
                                        $user_id = get_current_user_id();
                                        $user_plan = get_user_meta($user_id, 'arm_user_plan_ids', true);
                                        if (!empty($user_plan)) {
                                            $arm_is_trial = '0';
                                        }
                                    }
                                }
                                $coupon_code = isset($posted_data['arm_coupon_code']) ? $posted_data['arm_coupon_code'] : '';
                                $arm_coupon_discount_captured = $arm_coupon_discount;
                                $arm_coupon_discount_type_captured = $arm_coupon_discount_type;
                                $coupon_code_captured = isset($posted_data['arm_coupon_code']) ? $posted_data['arm_coupon_code'] : '';
                                if($display_log == 0){
                                    $coupon_code_captured = '';
                                    $arm_coupon_discount_captured = 0;
                                    $arm_coupon_discount_type_captured = '';
                                }
                                
                                $payment_data_captured = array(
                                    'arm_user_id' => $user_id,
                                    'arm_plan_id' => $plan->ID,
                                    'arm_first_name' =>   $arm_first_name,
                                    'arm_last_name'  =>   $arm_last_name,
                                    'arm_payment_gateway' => 'paypal_pro',
                                    'arm_payment_type' => $plan->payment_type,
                                    'arm_payer_email' => $user_email_add,
                                    'arm_receiver_email' => '',
                                    'arm_transaction_id' => $txn_id,
                                    'arm_token' => '',
                                    'arm_transaction_payment_type' => $plan->payment_type,
                                    'arm_transaction_status' => ($formatted_response['RESPMSG'] == 'Approved') ? 'completed' : 'pending',
                                    'arm_payment_date' => $payment_date,
                                    'arm_amount' => floatval($trial_amount),
                                    'arm_currency' => $currency,
                                    'arm_coupon_code' => $coupon_code_captured,
                                    'arm_coupon_discount' => $arm_coupon_discount_captured,
                                    'arm_coupon_discount_type' => $arm_coupon_discount_type_captured,
                                    'arm_response_text' => (!empty($response)) ? maybe_serialize((array) $response) : '',
                                    'arm_created_date' => current_time('mysql'),
                                    'arm_is_trial' => '0',
                                    'arm_display_log' => $display_log,
                                    'arm_coupon_on_each_subscriptions' => $arm_coupon_on_each_subscriptions
                                );

                                $extraParam['profileID'] = $txn_id;
                                $extraParam['paid_amount'] = $trial_amount;
                                $extraParam['tax_percentage'] = $tax_percentage;

                                /* One time Payment for first occurrence */
                                if ($is_recurring && $plan_payment_mode == 'auto_debit_subscription') {
                                    if ($plan->has_trial_period()) {
                                        $amount = $plan->options['trial']['amount'];
                                    }
                                    if (!empty($coupon_amount) && $coupon_amount > 0) {
                                        $amount = $discount_amt;
                                    }

                                    /*$tax_amount = 0;
                                    if($tax_percentage > 0) {
                                        $tax_amount = ($tax_percentage*$amount)/100;
                                        $tax_amount = number_format((float)$tax_amount, 2, '.', '');
                                        $amount = $amount+$tax_amount;
                                    }*/
                                    $extraParam['tax_percentage'] = $tax_percentage;
                                    $extraParam['tax_amount'] = $tax_amount;
                                    $extraParam['paid_amount'] = $amount;
                                    $paypal_array_onetime = array(
                                        'TENDER' => 'C',
                                        'TRXTYPE' => 'D',
                                        'ORIGID' => $armref_id,
                                        'ACCT' => $card_number,
                                        'EXPDATE' => $card_expire,
                                        'CVV2' => $cvc,
                                        'AMT' => $amount,
                                        'CURRENCY' => $currency,
                                        'VERBOSITY' => 'MEDIUM',
                                        'NAME' => $card_holder_name,
                                    );
                                    if (($plan->has_trial_period() && ($amount == 0 || $amount == '0.00') || ($discount_amt != '' && ($discount_amt == 0 || $discount_amt == '0.00')))) {
                                        $return_array = array();
                                        $return_array['arm_plan_id'] = $plan->ID;
                                        $return_array['arm_first_name'] =   $arm_first_name;
                                        $return_array['arm_last_name']  =   $arm_last_name;
                                        $return_array['arm_payment_gateway'] = 'paypal_pro';
                                        $return_array['arm_payment_type'] = $plan->payment_type;
                                        $return_array['arm_token'] = '-';
                                        $return_array['arm_payer_email'] = (isset($posted_data['user_email'])) ? $posted_data['user_email'] : '';
                                        $return_array['arm_receiver_email'] = '';
                                        $return_array['arm_transaction_id'] = '-';
                                        $return_array['arm_transaction_payment_type'] = $plan->payment_type;
                                        $return_array['arm_transaction_status'] = 'completed';
                                        $return_array['arm_payment_mode'] = '';
                                        $return_array['arm_payment_date'] = date('Y-m-d H:i:s');
                                        $return_array['arm_amount'] = 0;
                                        $return_array['arm_currency'] = $currency;
                                        $return_array['arm_coupon_code'] = @$coupon_code;
                                        $return_array['arm_coupon_discount'] = @$arm_coupon_discount;
                                        $return_array['arm_coupon_discount_type'] = @$arm_coupon_discount_type;
                                        $return_array['arm_response_text'] = '';
                                        $return_array['arm_extra_vars'] = maybe_serialize($extraParam);
                                        $return_array['arm_is_trial'] = $arm_is_trial;
                                        $return_array['arm_created_date'] = current_time('mysql');
                                        $return_array['arm_coupon_on_each_subscriptions'] = $arm_coupon_on_each_subscriptions;
                                        $payment_log_id = $arm_payment_gateways->arm_save_payment_log($return_array);
                                        $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                                        if($arm_manage_coupons->isCouponFeature && isset($posted_data['arm_coupon_code']) && !empty($posted_data['arm_coupon_code']) && !empty($arm_coupon_on_each_subscriptions)) {
                                            $payment_done["coupon_on_each"] = TRUE;
                                            $payment_done["trans_log_id"] = $payment_log_id;
                                        }

                                        $paid_trial_stripe_payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id, 'gateway' => 'paypal_pro');
                                    } else {
                                        $ch = curl_init();
                                        curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                                        curl_setopt($ch, CURLOPT_VERBOSE, 1);
                                        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                        curl_setopt($ch, CURLOPT_POST, 1);

                                        $nvpstrOnetime = "";
                                        if (is_array($paypal_array_onetime)) {
                                            foreach ($paypal_array_onetime as $key => $value) {
                                                if (is_array($value)) {
                                                    foreach ($value as $item) {
                                                        if (strlen($nvpstr) > 0)
                                                            $nvpstrOnetime .= "&";
                                                        $nvpstrOnetime .= "$key=" . $item;
                                                    }
                                                } else {
                                                    if (strlen($nvpstr) > 0)
                                                        $nvpstrOnetime .= "&";
                                                    $nvpstrOnetime .= "$key=" . $value;
                                                }
                                            }
                                        }

                                        $nvpreqOnetime = "VENDOR=$api_vendor&PARTNER=$api_partner&PWD=$api_password&USER=$api_username&$nvpstrOnetime";

                                        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreqOnetime);

                                        $responseOnetime = curl_exec($ch);
                                        $headers = curl_getinfo($ch);
                                        curl_close($ch);

                                        $formatted_response = $this->arm_get_formatted_response($responseOnetime);

                                        if ($formatted_response['RESULT'] == 0) {
                                            $payment_data_onetime = array(
                                                'arm_user_id' => $user_id,
                                                'arm_plan_id' => $plan->ID,
                                                'arm_first_name' =>   $arm_first_name,
                                                'arm_last_name'  =>   $arm_last_name,
                                                'arm_payment_gateway' => 'paypal_pro',
                                                'arm_payment_type' => $plan->payment_type,
                                                'arm_payer_email' => $user_email_add,
                                                'arm_receiver_email' => '',
                                                'arm_transaction_id' => $formatted_response['PNREF'],
                                                'arm_token' => '',
                                                'arm_transaction_payment_type' => $plan->payment_type,
                                                'arm_transaction_status' => ($formatted_response['RESPMSG'] == 'Approved') ? 'completed' : 'pending',
                                                'arm_payment_date' => current_time('mysql'),
                                                'arm_amount' => floatval($trial_amount),
                                                'arm_currency' => $currency,
                                                'arm_coupon_code' => isset($posted_data['arm_coupon_code']) ? $posted_data['arm_coupon_code'] : '',
                                                'arm_coupon_discount' => $arm_coupon_discount,
                                                'arm_coupon_discount_type' => $arm_coupon_discount_type,
                                                'arm_response_text' => (!empty($response)) ? maybe_serialize((array) $response) : '',
                                                'arm_created_date' => current_time('mysql'),
                                                'arm_display_log' => '1',
                                                'arm_coupon_on_each_subscriptions' => $arm_coupon_on_each_subscriptions
                                            );

                                            $payment_data_onetime['arm_extra_vars'] = maybe_serialize($extraParam);
                                            
                                 
                                            $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data_onetime);
                                            $payment_done = array();
                                            if ($payment_log_id) {
                                                $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                                                if($arm_manage_coupons->isCouponFeature && isset($posted_data['arm_coupon_code']) && !empty($posted_data['arm_coupon_code']) && !empty($arm_coupon_on_each_subscriptions)) {
                                                        $payment_done["coupon_on_each"] = TRUE;
                                                        $payment_done["trans_log_id"] = $payment_log_id;
                                                }
                                                $paid_trial_stripe_payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id, 'gateway' => 'paypal_pro');
                                            } else {
                                                
                                             
                                                $err_msg = @$arm_global_settings->common_message['arm_payment_fail_paypal_pro'];
                                                $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);
                                                $payment_done = array('status' => FALSE, 'error' => $err_msg);
                                                return $payment_done;
                                            }
                                        } else {
                                            /* Cancel started subscription */
                                            $cancel_params = "TRXTYPE=R&TENDER=C&PARTNER=" . $api_partner . "&VENDOR=" . $api_vendor . "&USER=" . $api_user . "&PWD=" . $api_password . "&ACTION=C&ORIGPROFILEID=" . $rec_profile_id;

                                            $ch_cancel = curl_init();
                                            curl_setopt($ch_cancel, CURLOPT_URL, $api_endpoint);
                                            curl_setopt($ch_cancel, CURLOPT_VERBOSE, 1);
                                            curl_setopt($ch_cancel, CURLOPT_TIMEOUT, 0);
                                            curl_setopt($ch_cancel, CURLOPT_SSL_VERIFYPEER, FALSE);
                                            curl_setopt($ch_cancel, CURLOPT_SSL_VERIFYHOST, FALSE);
                                            curl_setopt($ch_cancel, CURLOPT_RETURNTRANSFER, 1);
                                            curl_setopt($ch_cancel, CURLOPT_POST, 1);
                                            curl_setopt($ch_cancel, CURLOPT_POSTFIELDS, $cancel_params);
                                            $responseCancel = curl_exec($ch_cancel);
                                            curl_close($ch_cancel);

                                            $cancel_formatted_response = $this->arm_get_formatted_response($responseCancel);
                                            $actual_error = isset($cancel_formatted_response['RESPMSG']) ? $cancel_formatted_response['RESPMSG'] : '';
                                            $actual_error = !empty($actual_error) ? $actual_error.' '.$arm_help_link : '';
                                            
                                            $err_msg = @$arm_global_settings->common_message['arm_payment_fail_paypal_pro'];
                                            $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);
                                            $actualmsg = ($arm_paypal_pro_enable_debug_mode == '1') ? $actual_error : $err_msg;
                                            $payment_done = array('status' => FALSE, 'error' => $actualmsg);
                                            return $payment_done;
                                        }
                                    }
                                }
                                else if($is_recurring && $plan_payment_mode == 'manual_subscription' && $arm_manage_coupons->isCouponFeature && isset($posted_data['arm_coupon_code']) && !empty($posted_data['arm_coupon_code']) && !empty($arm_coupon_on_each_subscriptions)) {

                                    $payment_done["coupon_on_each"] = TRUE;
                                    $payment_done["trans_log_id"] = "original";
                                }
                            } else {
                            
                                $actual_error = isset($formatted_response['RESPMSG']) ? $formatted_response['RESPMSG'] : '';
                                $actual_error = !empty($actual_error) ? $actual_error.' '.$arm_help_link : '';
                                
                                $err_msg = @$arm_global_settings->common_message['arm_payment_fail_paypal_pro'];
                                $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);
                                $actualmsg = ($arm_paypal_pro_enable_debug_mode == '1') ? $actual_error : $err_msg;
                                $payment_done = array('status' => FALSE, 'error' => $actualmsg);

                            }
                            if (!empty($payment_data_captured)) {
                                
                                if($payment_data_captured['arm_display_log'] == '0' || $payment_data_captured['arm_display_log'] == 0){
                                 if(isset($extraParam['coupon'])){
                                        unset($extraParam['coupon']);
                                    }
                                    if(isset($extraParam['trial'])){
                                        unset($extraParam['trial']);
                                    }
                                }
                                 
                                $payment_data_captured['arm_extra_vars'] = maybe_serialize($extraParam);
                                $payment_log_id = $arm_payment_gateways->arm_save_payment_log($payment_data_captured);
                                
                                $coupon_on_each = (isset($payment_done["coupon_on_each"]) && $payment_done["coupon_on_each"] == TRUE) ? TRUE : FALSE;
                                
                                $tans_log_id = 0;

                                if($coupon_on_each == TRUE && isset($payment_done["trans_log_id"]) && $payment_done["trans_log_id"] != 0 && $payment_done["trans_log_id"] != "original" ) {
                                    $tans_log_id = $payment_done["trans_log_id"];
                                }
                                $payment_done = array();
                                if ($payment_log_id) {
                                    if($plan_action== 'recurring_payment' && $user_id!='')
                                    {
                                        do_action('arm_after_recurring_payment_success_outside',$user_id,$plan_id,'paypal_pro',$plan_payment_mode,$user_subsdata);
                                    }
                                    $payment_done = array('status' => TRUE, 'log_id' => $payment_log_id, 'entry_id' => $entry_id);
                                    $payment_done["coupon_on_each"] = $coupon_on_each;
                                    if($tans_log_id == "original") {
                                        $payment_done["trans_log_id"] = $payment_log_id;
                                    }
                                    else {
                                        $payment_done["trans_log_id"] = $tans_log_id;
                                    }
                                } else {
                                    $err_msg = @$arm_global_settings->common_message['arm_payment_fail_paypal_pro'];
                                    $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);
                                    $payment_done = array('status' => FALSE, 'error' => $err_msg);
                                }
                            } else {
                                $actual_error = isset($formatted_response['RESPMSG']) ? $formatted_response['RESPMSG'] : '';
                                $actual_error = !empty($actual_error) ? $actual_error.' '.$arm_help_link : '';
                                $err_msg = @$arm_global_settings->common_message['arm_payment_fail_paypal_pro'];
                                $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);
                                $actualmsg = ($arm_paypal_pro_enable_debug_mode == '1') ? $actual_error : $err_msg;
                                $payment_done = array('status' => FALSE, 'error' => $actualmsg);
                            }
                        } else {
                            $actual_error = isset($formatted_response['RESPMSG']) ? $formatted_response['RESPMSG'] : '';
                            $actual_error = !empty($actual_error) ? $actual_error.' '.$arm_help_link : '';
                            $err_msg = @$arm_global_settings->common_message['arm_unauthorized_paypal_pro_credit_card'];
                            $err_msg = (!empty($err_msg)) ? $err_msg : __('Sorry something went wrong while processing payment with Paypal Pro.', ARM_PAYPALPRO_TXTDOMAIN);
                            $actualmsg = ($arm_paypal_pro_enable_debug_mode == '1') ? $actual_error : $err_msg;

                            $payment_done = array('status' => FALSE, 'error' => $actualmsg);
                            //return $payment_done;
                        }
                        return $payment_done;
                    }
                } else {
                    
                }
            } else {
                
            }
        }
        
        function arm_change_pg_name_for_paypal_pro($pgname, $pg) {
            if ($pg == 'paypal_pro') {
                return __('Paypal Pro', ARM_PAYPALPRO_TXTDOMAIN);
            }
            return $pgname;
        }

        function arm_get_gateways_update_card_detail_btn_func($response, $planData, $user_plan_id, $update_card_text) {
            if($planData["arm_user_gateway"] == "paypal_pro") {
                $response = '<div class="arm_cm_update_btn_div"><button type="button" class= "arm_update_card_button arm_update_card_button_style" data-plan_id="' . $user_plan_id . '">' . $update_card_text . '</button></div>';
            }
            return $response;
        }

        function arm_allow_gateways_update_card_detail_func($return, $arm_user_paymeant_gateway) {
            if($arm_user_paymeant_gateway == "paypal_pro") {
                $return = true;
            }
            return $return;
        }

        function arm_submit_gateways_updated_card_detail_func($err_msg, $success_msg, $arm_user_payment_gateway, $pg_options, $card_holder_name, $card_number, $exp_month, $exp_year, $planData, $response) {
            if($arm_user_payment_gateway=='paypal_pro' && is_user_logged_in()) {
                if(!empty($planData['arm_current_plan_detail']['arm_subscription_plan_id']))
                {
                    $arm_plan_id = $planData['arm_current_plan_detail']['arm_subscription_plan_id'];

                    $arm_user_id = get_current_user_id();
                    $planData = get_user_meta($arm_user_id, 'arm_user_plan_' . $arm_plan_id, true);
                    $arm_user_payment_gateway = $planData['arm_user_gateway'];
                    $arm_user_payment_mode = $planData['arm_payment_mode'];
                    if($arm_user_payment_mode=='auto_debit_subscription' && $arm_user_payment_gateway=='paypal_pro')
                    {

                        $api_endpoint = ($pg_options["paypal_pro_payment_mode"] == "sandbox") ? "https://pilot-payflowpro.paypal.com" : "https://pilot-payflowpro.paypal.com";
                        $payflow_array = array(
                            'VENDOR' => !empty($pg_options["payflow_pro_sandbox_vendor"]) ? $pg_options["payflow_pro_sandbox_vendor"] : "",
                            'PARTNER' => !empty($pg_options["payflow_pro_sandbox_partner"]) ? $pg_options["payflow_pro_sandbox_partner"] : "",
                            'PWD' => !empty($pg_options["payflow_pro_sandbox_password"]) ? $pg_options["payflow_pro_sandbox_password"] : "",
                            'USER' => !empty($pg_options["payflow_pro_sandbox_username"]) ? $pg_options["payflow_pro_sandbox_username"] : "",
                            'TRXTYPE' => 'R',
                            'ACTION' => 'M',
                            'TENDER' => 'C',
                            'ACCT' => $card_number,
                            'EXPDATE' => sprintf("%02d", $exp_month) . substr(strval($exp_year), 2),
                            'CVV2' => $cvc,
                            'NAME' => $card_holder_name,
                            'ORIGPROFILEID' => !empty($planData["arm_paypal_pro"]["transaction_id"]) ? $planData["arm_paypal_pro"]["transaction_id"] : ""
                        );

                        $nvpstr_ = "";
                        foreach ($payflow_array as $key => $value) {
                            if (strlen($nvpstr_) > 0) {
                                $nvpstr_ .= "&";
                            }
                            $nvpstr_ .= "$key=" . $value;
                        }

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $api_endpoint);
                        curl_setopt($ch, CURLOPT_VERBOSE, 1);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpstr_);
                        $ppl_res = curl_exec($ch);
                        curl_close($ch);

                        $arm_paypal_pro_enable_debug_mode = isset($pg_options['enable_debug_mode']) ? $pg_options['enable_debug_mode'] : 0;
                        $arm_help_link = '<a href="https://www.paypalobjects.com/en_AU/vhelp/paypalmanager_help/result_values_for_transaction_declines_or_errors.htm" target="_blank">'.__('Click Here', ARM_PAYPALPRO_TXTDOMAIN).'</a>';
                        
                        $formatted_response = $this->arm_get_formatted_response($ppl_res);

                        if ($formatted_response['RESULT'] == "0") {
                            $response = array('status' => 'success', 'type' => 'message', 'message' => $success_msg);
                        }
                        else {
                            $actual_error = isset($formatted_response['RESPMSG']) ? $formatted_response['RESPMSG'] : '';
                            $actual_error = !empty($actual_error) ? $actual_error.' '.$arm_help_link : '';
                            $actualmsg = ($arm_paypal_pro_enable_debug_mode == '1') ? $actual_error : $err_msg;
                            $response = array('status' => 'error', 'type' => 'message', 'message' => $actualmsg);
                        }
                    }
                }
            }
            return $response;
        }
    }
}

global $ARMemberPaypalPro;
$ARMemberPaypalPro = new arm_paypal_pro();
