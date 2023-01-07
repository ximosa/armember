<?php
if(!class_exists('arm_aff_payouts')){
    
    class arm_aff_payouts{

        var $payouts_status;
        function __construct(){
            
            $this->payouts_status = array(
                '0'=>__('pending', 'ARM_AFFILIATE'),  
                '1'=>__('unpaid', 'ARM_AFFILIATE'),  
                '2'=>__('paid', 'ARM_AFFILIATE'), 
                '3'=>__('rejected', 'ARM_AFFILIATE')
            );
            add_action( 'wp_ajax_arm_payouts_list', array( $this, 'arm_payouts_grid_data' ) );
            
            add_action( 'wp_ajax_arm_add_payouts_user', array( $this, 'arm_add_payouts_user' ) );
            
            add_action( 'delete_user', array($this, 'delete_payouts_when_user_delete') );
            
            add_action( 'arm_aff_payouts_export', array($this, 'arm_aff_payouts_export') );
        }
        
        function arm_payouts_grid_data() {
            global $wpdb, $arm_affiliate, $arm_aff_referrals, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $arm_default_user_details_text;
            
            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_payouts';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $arm_currency = $arm_payment_gateways->arm_get_global_currency();
            $date_format = $arm_global_settings->arm_get_wp_date_format()." ".get_option( 'time_format' );;
            
            $nowDate = current_time('mysql');
            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans();
            $grid_columns = array(
                'affiliate_user' => __('Affiliate User', 'ARM_AFFILIATE'),
                'total' => __('Total Earning', 'ARM_AFFILIATE'),
                'paid' => __('Paid Amount', 'ARM_AFFILIATE'),
                'due' => __('Due Amount', 'ARM_AFFILIATE')
            );
            
            $user_table = $wpdb->users;
            $tmp_query = "SELECT r.arm_affiliate_id, r.arm_currency, u.user_login, sum(arm_amount) as total_amount, "
                    . "(select sum(arm_amount) from `{$arm_affiliate->tbl_arm_aff_payouts}` where arm_affiliate_id = r.arm_affiliate_id) as paid_amount"
                    .", (sum(arm_amount) - IFNULL((select sum(arm_amount) from `{$arm_affiliate->tbl_arm_aff_payouts}` where arm_affiliate_id = r.arm_affiliate_id), 0) ) as arm_unpaid_amount "
                    ." FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r "
                    ." LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff "
                    ." ON aff.arm_affiliate_id = r.arm_affiliate_id "
                    ." LEFT JOIN `{$user_table}` u "
                    ." ON u.ID = aff.arm_user_id "
                    ." WHERE r.arm_status = 1 "
                    ." GROUP BY r.arm_affiliate_id";
                    
            $form_result = $wpdb->get_results($tmp_query);
            $total_before_filter = count($form_result);
            
            $where_condition = '';
            
            $sSearch = isset($_REQUEST['sSearch']) ? $_REQUEST['sSearch'] : '';
            if($sSearch != '')
            { $where_condition.= " AND u.user_login LIKE '%{$sSearch}%'"; }
            
            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] != '' ) ? $_REQUEST['iSortCol_0'] : 0;
            $order_by = 'u.user_login';
            if( $sorting_col == 0 ) {
                $order_by = 'u.user_login';
            } else if( $sorting_col == 1 ){
                $order_by = 'total_amount';
            } else if( $sorting_col == 3){
                $order_by = 'arm_unpaid_amount';
            }
            
            $start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : '';
            $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : '';
            if (!empty($start_date)) {
                $start_datetime = $arm_aff_referrals->date_convert_db_formate($start_date)." 00:00:00";
                if (!empty($end_date)) {
                    $end_datetime = $arm_aff_referrals->date_convert_db_formate($end_date)." 23:59:59";
                    if ($start_datetime > $end_datetime) {
                        $end_datetime = $arm_aff_referrals->date_convert_db_formate($start_date)." 00:00:00";
                        $start_datetime = $arm_aff_referrals->date_convert_db_formate($end_date)." 23:59:59";
                    }
                    $where_condition .= " AND (r.arm_date_time BETWEEN '$start_datetime' AND '$end_datetime') ";
                } else {
                    $where_condition .= " AND (r.arm_date_time > '$start_datetime') ";
                }
            } else {
                if (!empty($end_date)) {
                    $end_datetime = $arm_aff_referrals->date_convert_db_formate($end_date);  
                    $where_condition .= " AND (r.arm_date_time < '$end_datetime') ";
                }
            }
            
            $grid_data = array();
            $ai = 0;
            
            $tmp_query = "SELECT r.arm_affiliate_id, r.arm_currency, u.user_login, sum(arm_amount) as total_amount, "
                    . "(select sum(arm_amount) from `{$arm_affiliate->tbl_arm_aff_payouts}` where arm_affiliate_id = r.arm_affiliate_id) as paid_amount"
                    .", (sum(arm_amount) - IFNULL((select sum(arm_amount) from `{$arm_affiliate->tbl_arm_aff_payouts}` where arm_affiliate_id = r.arm_affiliate_id), 0) ) as arm_unpaid_amount "
                    ." FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r "
                    ." LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff "
                    ." ON aff.arm_affiliate_id = r.arm_affiliate_id "
                    ." LEFT JOIN `{$user_table}` u "
                    ." ON u.ID = aff.arm_user_id "
                    ." WHERE r.arm_status = 1 "
                    .$where_condition
                    ." GROUP BY r.arm_affiliate_id"
                    ." ORDER BY {$order_by} {$sorting_ord} ";


            $form_result = $wpdb->get_results($tmp_query);
            $total_after_filter = count($form_result);
            
            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            
            $tmp_query = $tmp_query . " LIMIT {$offset},{$number}";
                        
            $form_result = $wpdb->get_results($tmp_query);
            
            foreach ($form_result as $payouts) {
                $arm_affiliate_id = $payouts->arm_affiliate_id;
                $total_commission = $payouts->total_amount;
                //$arm_currency = $payouts->arm_currency;
                $arm_paid_amount = $payouts->paid_amount;
                $arm_unpaid_amount = $payouts->arm_unpaid_amount; // $total_commission - $arm_paid_amount;

                $arm_affiliate_user_name = isset($payouts->user_login) ? $payouts->user_login : '';

//                $arm_get_ref_affiliate_user_data = get_userdata($arm_ref_affiliate_id);
//                $arm_ref_affiliate_user_name = $arm_get_ref_affiliate_user_data->user_login;
                
                $grid_data[$ai][0] = !empty($arm_affiliate_user_name) ? $arm_affiliate_user_name : $arm_default_user_details_text;
                $grid_data[$ai][1] = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $total_commission)." ".$arm_currency;
                $grid_data[$ai][2] = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $arm_paid_amount)." ".$arm_currency;
                $grid_data[$ai][3] = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $arm_unpaid_amount)." ".$arm_currency;
                $payment_history = '';
                $payment_history = $this->arm_affiliate_payment_history($arm_affiliate_id);
                $count = 0;
                $Data = array();
                    foreach($payment_history as $mData){
                        $count++;
                        $mData['arm_date_time'] = "<center>".date( $date_format, strtotime( $mData['arm_date_time'] ) )."</center>";
                        $mData['arm_amount'] = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $mData['arm_amount'])." ".$arm_currency;
                        $mData['arm_remaining_balance'] = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $mData['arm_remaining_balance'])." ".$arm_currency;
                        $Data[] = array_values($mData);
                    }
                
                $gridAction = "<div class='arm_grid_action_btn_container'>";
                $gridAction .= '<textarea id="arm_payment_history_'.$arm_affiliate_id.'" style="display:none;" >'.json_encode($Data).'</textarea>';
                
                $gridAction .= "<a href='javascript:void(0)' onclick='arm_user_export_payout_hisroty({$arm_affiliate_id});'><img src='" . ARM_AFFILIATE_IMAGES_URL . "/export_to_csv.png' class='armhelptip' title='" . __('Export To CSV', 'ARM_AFFILIATE') . "' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/export_to_csv_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/export_to_csv.png';\" /></a>";
                
                $gridAction .= "<a href='javascript:void(0)' onclick='arm_user_payout_hisroty({$arm_affiliate_id});'><img src='" . ARM_AFFILIATE_IMAGES_URL . "/payment_history.png' class='armhelptip' title='" . __('Payment History', 'ARM_AFFILIATE') . "' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/payment_history_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/payment_history.png';\" /></a>";
                
                $gridAction .= "<a href='javascript:void(0)' onclick='showPaymentConfirmBoxCallback({$arm_affiliate_id});'><img src='" . ARM_AFFILIATE_IMAGES_URL . "/payment.png' class='armhelptip' title='" . __('Pay To User', 'ARM_AFFILIATE') . "' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/payment_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/payment.png';\" /></a>";
                $gridAction .= "<div class='arm_confirm_box arm_change_status_box arm_payment_box_{$arm_affiliate_id}' id='arm_payment_box_{$arm_affiliate_id}'>";
                $gridAction .= "<div class='arm_confirm_box_body'>";
                $gridAction .= "<div class='arm_confirm_box_arrow'></div>";
                $gridAction .= "<div class='arm_confirm_box_text'>";

                $gridAction .= "<span>".__('Amount', 'ARM_AFFILIATE')."</span>";
                $gridAction .= "<br/> <input type='text' id='arm_amount_".$arm_affiliate_id."' data-id='arm_amount' value='".$arm_payment_gateways->arm_amount_set_separator($arm_currency, $arm_unpaid_amount)."'> ". $arm_currency;

                $gridAction .= "</div>";
                $gridAction .= "<div class='arm_confirm_box_btn_container'>";
                $gridAction .= "<button type='button' class='arm_confirm_box_btn armemailaddbtn arm_payment_ok_btn' data-item_id='{$arm_affiliate_id}'>" . __('Pay', 'ARM_AFFILIATE') . "</button>&nbsp;";
                $gridAction .= "<button type='button' class='arm_confirm_box_btn armcancel' onclick='hideConfirmBoxCallback();'>" . __('Cancel', 'ARM_AFFILIATE') . "</button>";
                $gridAction .= "</div>";
                $gridAction .= "</div>";
                $gridAction .= "</div>";
                
                $gridAction .= "</div>";
                
                $grid_data[$ai][4] = $gridAction;
                $ai++;
            }
            
            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
            $response = array(
                'sColumns' => implode(',', $grid_columns),
                'sEcho' => $sEcho,
                'iTotalRecords' => $total_before_filter, // Before Filtered Records
                'iTotalDisplayRecords' => $total_after_filter, // After Filter Records
                'aaData' => $grid_data,
            );
            echo json_encode($response);
            die();
        }
        
        function arm_affiliate_payment_history ($affiliate_id) {
            global $wpdb, $arm_affiliate;
            
            $tmp_query = "SELECT arm_payout_id, arm_date_time, arm_amount, arm_remaining_balance "
                        ." FROM `{$arm_affiliate->tbl_arm_aff_payouts}` r "
                        ." WHERE arm_affiliate_id = ".$affiliate_id
                        ." ORDER BY arm_date_time desc ";
                        
            $form_result = $wpdb->get_results($tmp_query, ARRAY_A);
            return $form_result;
        }
        
        function arm_payment_user() {
            global $wpdb, $arm_affiliate;
            
            $user_table = $wpdb->users;
            $tmp_query = "SELECT r.arm_affiliate_id, u.ID, u.user_login "
                        ." FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r "
                        ." LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff "
                        ." ON aff.arm_affiliate_id = r.arm_affiliate_id "
                        ." LEFT JOIN `{$user_table}` u "
                        ." ON u.ID = aff.arm_user_id "
                        ." WHERE 1=1 "
                        ." GROUP BY r.arm_affiliate_id"
                        ." ORDER BY u.user_login ";
                        
            $form_result = $wpdb->get_results($tmp_query);
            return $form_result;
        }
        
        function arm_add_payouts_user() {
            global $wpdb, $arm_affiliate, $arm_payment_gateways, $ARMember;

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_payouts';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $currency = $arm_payment_gateways->arm_get_global_currency();
            $affiliate_id = $_POST['arm_affiliate_user_id'];
            $amount = $_POST['arm_amount'];
            $user_query = "SELECT r.arm_affiliate_id, r.arm_currency, sum(arm_amount) as total_amount, (select sum(arm_amount) from `{$arm_affiliate->tbl_arm_aff_payouts}` where arm_affiliate_id = r.arm_affiliate_id) as paid_amount FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r WHERE r.arm_affiliate_id = $affiliate_id and  r.arm_status = 1 group by r.arm_affiliate_id ";
            
            $user_commission = $wpdb->get_row( $user_query, ARRAY_A);
            $unpaid_amount = $user_commission['total_amount'] - $user_commission['paid_amount'];
            $unpaid_amount = number_format($unpaid_amount, 2);
            
            if($amount > $unpaid_amount)
            {
                $response = array( 'type' => 'error', 'msg'=> __( 'You will not able to pay more than commission amount', 'ARM_AFFILIATE' ),'unpaid_amount' => $unpaid_amount );
            }
            else
            {
                
                $nowDate = current_time('mysql');
                $arm_reamining_balance = $unpaid_amount - $amount;
                $arm_aff_payouts_values = array(
                    'arm_affiliate_id' => $affiliate_id,
                    //'arm_ref_affiliate_id' => $arm_ref_affiliate_id,
                    //'arm_referral_id' => $arm_referral_id,
                    'arm_amount' => $amount,
                    'arm_currency' => $currency,
                    //'arm_status' => $arm_status,
                    'arm_date_time' => $nowDate,
                    'arm_remaining_balance' => $arm_reamining_balance
                );
                $wpdb->insert($arm_affiliate->tbl_arm_aff_payouts, $arm_aff_payouts_values);
                $response = array( 'type' => 'success', 'msg'=> __( 'Amount paid successfully.', 'ARM_AFFILIATE' ) );
            }
            echo json_encode($response);
            die;
        }
        
        function delete_payouts_when_user_delete($user_id){
            global $wpdb, $arm_affiliate;
            $wpdb->query( "DELETE FROM `$arm_affiliate->tbl_arm_aff_payouts` WHERE arm_affiliate_id = " . $user_id . " OR arm_ref_affiliate_id = ". $user_id );
        }
        
        function arm_aff_payouts_export( $request ) {
            global $arm_members_class, $ARMember, $wpdb, $arm_affiliate, $arm_global_settings, $arm_aff_referrals,$arm_payment_gateways, $arm_default_user_details_text;
            
            if( isset($request['arm_action']) && $request['arm_action'] == 'payouts_export_csv' ) {
                $date_format = $arm_global_settings->arm_get_wp_date_format()." ".get_option('time_format');;
                $arm_currency = $arm_payment_gateways->arm_get_global_currency();
                
                $where_condition = '';
                $sSearch = isset($request['sSearch']) ? $request['sSearch'] : '';
                if($sSearch != '')
                { $where_condition.= " AND u.user_login LIKE '%{$sSearch}%'"; }
                
                $start_date = isset($request['start_date']) ? $request['start_date'] : '';
                $end_date = isset($request['end_date']) ? $request['end_date'] : '';
                if (!empty($start_date)) {
                    $start_datetime = $arm_aff_referrals->date_convert_db_formate($start_date)." 00:00:00";
                    if (!empty($end_date)) {
                        $end_datetime = $arm_aff_referrals->date_convert_db_formate($end_date)." 23:59:59";
                        if ($start_datetime > $end_datetime) {
                            $end_datetime = $arm_aff_referrals->date_convert_db_formate($start_date)." 00:00:00";
                            $start_datetime = $arm_aff_referrals->date_convert_db_formate($end_date)." 23:59:59";
                        }
                        $where_condition .= " AND (p.arm_date_time BETWEEN '$start_datetime' AND '$end_datetime') ";
                    } else {
                        $where_condition .= " AND (p.arm_date_time > '$start_datetime') ";
                    }
                } else {
                    if (!empty($end_date)) {
                        $end_datetime = $arm_aff_referrals->date_convert_db_formate($end_date);  
                        $where_condition .= " AND (p.arm_date_time < '$end_datetime') ";
                    }
                }
                
                if(isset($request['arm_affiliate_user_id']) && $request['arm_affiliate_user_id'] > 0)
                {
                    $where_condition .= " AND p.arm_affiliate_id = ".$request['arm_affiliate_user_id'];
                }

                $grid_data = array();
                $ai = 0;
                $user_table = $wpdb->users;
                $tmp_query = "SELECT p.*, u.user_login FROM `{$arm_affiliate->tbl_arm_aff_payouts}` p "
                            ." LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff "
                            ." ON aff.arm_affiliate_id = p.arm_affiliate_id "
                            ." LEFT JOIN `{$user_table}` u "
                            ." ON u.ID = aff.arm_user_id "
                            ." WHERE 1=1 "
                            .$where_condition;

                $redirect_to = admin_url('admin.php?page=arm_affiliate_payouts');
                $payouts = $wpdb->get_results($tmp_query);
                if (!empty($payouts))
                {
                    $payouts_data = array();
                    foreach ($payouts as $payout)
                    {
                        
                        $arm_affiliate_id = $payout->arm_affiliate_id;
                        $arm_amount = $payout->arm_amount;
                        $arm_balance = $payout->arm_remaining_balance;
                        $arm_date_time = date( $date_format, strtotime( $payout->arm_date_time ) );

                        $arm_affiliate_user_name = isset($payout->user_login) ? $payout->user_login : '';
                        if(empty($arm_affiliate_user_name)){
                            $arm_affiliate_user_name = $arm_default_user_details_text;
                        }
                        $payouts_data[] = array(
                            'arm_affiliate_username' => $arm_affiliate_user_name,
                            'arm_amount' => $arm_payment_gateways->arm_amount_set_separator($arm_currency, $arm_amount)." ".$arm_currency,
                            'arm_balance' => $arm_payment_gateways->arm_amount_set_separator($arm_currency, $arm_balance)." ".$arm_currency,
                            'arm_date_time' => $arm_date_time,
                        );
                    }
                    $arm_members_class->arm_export_to_csv($payouts_data, 'ARMember-export-payouts.csv', $delimiter=',');
                }
                else
                {
                    $success_message = __('No any payments found for export to CSV.', 'ARM_AFFILIATE');
                    $ARMember->arm_set_message('error', $success_message);
                }
                
                wp_redirect($redirect_to);
                die;
            }
        }
    }
}

global $arm_aff_payouts;
$arm_aff_payouts = new arm_aff_payouts();
?>