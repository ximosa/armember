<?php
if(!class_exists('arm_aff_statistics')){
    
    class arm_aff_statistics{
        
        function __construct(){
            
            add_action( 'wp_ajax_arm_visits_list', array( $this, 'arm_visits_list' ) );
            
            add_action( 'wp_ajax_arm_summery_list', array( $this, 'arm_summery_list' ) );
            
            add_action( 'arm_aff_visits_export', array($this, 'arm_aff_visits_export') );
            
            add_action( 'arm_aff_summery_export', array($this, 'arm_aff_summery_export') );
        }
        
        function arm_visits_list() {
            global $wpdb, $arm_affiliate, $arm_affiliate_settings, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $arm_aff_referrals, $arm_default_user_details_text;
            
            $date_format = $arm_global_settings->arm_get_wp_date_format()." ".get_option( 'time_format' );
            $arm_currency = $arm_payment_gateways->arm_get_global_currency();
            
            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_statistics';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $grid_columns = array(
                'sr_no' => __('SR. No', 'ARM_AFFILIATE'),
                'date' => __('Date', 'ARM_AFFILIATE'),
                'browser' => __('Browser', 'ARM_AFFILIATE'),
                'ip' => __('IP', 'ARM_AFFILIATE'),
                'country' => __('Country', 'ARM_AFFILIATE'),
                'converted' => __('Converted', 'ARM_AFFILIATE'),
                'affiliate_user' => __('Affiliate User', 'ARM_AFFILIATE'),
                'referral_id' => __('Referred User', 'ARM_AFFILIATE'),
                'commision' => __('Commission', 'ARM_AFFILIATE'),
                'plan' => __('Membership Plan', 'ARM_AFFILIATE'),
            );

            $where_condition = '';
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
                    $where_condition .= " AND (v.arm_date_time BETWEEN '$start_datetime' AND '$end_datetime') ";
                } else {
                    $where_condition .= " AND (v.arm_date_time > '$start_datetime') ";
                }
            } else {
                if (!empty($end_date)) {
                    $end_datetime = $arm_aff_referrals->date_convert_db_formate($end_date);  
                    $where_condition .= " AND (v.arm_date_time < '$end_datetime') ";
                }
            }
            
            $filter_plan_id = (!empty($_REQUEST['filter_plan_id']) && $_REQUEST['filter_plan_id'] != '0') ? $_REQUEST['filter_plan_id'] : '';
            if($filter_plan_id != '')
            { $where_condition.= " AND r.arm_plan_id IN (".$filter_plan_id.")"; }
            
            $filter_user_id = (!empty($_REQUEST['filter_user_id']) && $_REQUEST['filter_user_id'] != '0') ? $_REQUEST['filter_user_id'] : '';
            
            if(!empty($filter_user_id))
            {
                $string_filter_user_id = implode(",",$filter_user_id);
                $where_condition.= " AND aff.arm_user_id IN (".$string_filter_user_id.")";
            }
            
            
            $search = isset($_REQUEST['sSearch']) ? $_REQUEST['sSearch'] : '';
            if($search != '')
            {
                $where_condition.= " AND u.user_login LIKE '%{$search}%'"; 
            }
            
            $tmp_query = "SELECT * FROM {$arm_affiliate->tbl_arm_aff_visitors}";
            $form_result = $wpdb->get_results($tmp_query);
            $total_before_filter = count($form_result);
            
            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 1;
            $order_by = ' v.arm_date_time';
            if( $sorting_col == 1 ) {
                $order_by = ' v.arm_date_time';
            } 
            if( $sorting_col == 6 ) {
                $order_by = ' u.user_login';
            } 
            
            
            $grid_data = array();
            $ai = 0;
            $user_table = $wpdb->users;
            $tmp_query = "SELECT v.*, u.user_login, r.arm_ref_affiliate_id, r.arm_amount, r.arm_plan_id FROM `{$arm_affiliate->tbl_arm_aff_visitors}` v "
                        ." LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff "
                        ." ON aff.arm_affiliate_id = v.arm_affiliate_id "
                        ." LEFT JOIN `{$user_table}` u "
                        ." ON u.ID = aff.arm_user_id "
                        ." LEFT JOIN `{$arm_affiliate->tbl_arm_aff_referrals}` as r "
                        ." ON r.arm_referral_id = v.arm_referral_id "
                        ." WHERE 1=1 " . $where_condition
                        ." ORDER BY {$order_by} {$sorting_ord}";

            $form_result = $wpdb->get_results($tmp_query);
            $total_after_filter = count($form_result);
            
            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            
            $tmp_query = $tmp_query . " LIMIT {$offset},{$number}";
                        
            $form_result = $wpdb->get_results($tmp_query);
            $sr_no = $offset;
            foreach ($form_result as $visits) {
                $sr_no++;
                $arm_affiliate_id = $visits->arm_affiliate_id;
                $arm_referral_id = ($visits->arm_referral_id > 0) ? $visits->arm_referral_id : 0;
                $arm_visitor_ip = $visits->arm_visitor_ip;
                $arm_browser = $visits->arm_browser;
                $arm_country = $visits->arm_country;
                $arm_converted = ($visits->arm_referral_id > 0) ? 'Yes' : 'No';
                $arm_date_time = date( $date_format, strtotime( $visits->arm_date_time ) );

                $arm_affiliate_user_name = isset($visits->user_login) ? $visits->user_login : '';

                $arm_get_referral_user_data = get_userdata($visits->arm_ref_affiliate_id);
                $arm_referral_user_name = (isset($arm_get_referral_user_data->user_login) && $arm_get_referral_user_data->user_login != '') ? $arm_get_referral_user_data->user_login : '';
                $arm_commission = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $visits->arm_amount). " " .$arm_currency;
                $arm_plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($visits->arm_plan_id);

                $grid_data[$ai][0] = $sr_no;
                $grid_data[$ai][1] = $arm_date_time;
                $grid_data[$ai][2] = $arm_browser;
                $grid_data[$ai][3] = $arm_visitor_ip;
                $grid_data[$ai][4] = $arm_country;
                $grid_data[$ai][5] = '<span class="arm_aff_converted_user '.$arm_converted.'">'.$arm_converted.'</span>';
                $grid_data[$ai][6] = !empty($arm_affiliate_user_name) ? $arm_affiliate_user_name : $arm_default_user_details_text;
                $grid_data[$ai][7] = !empty($arm_referral_user_name) ? $arm_referral_user_name : $arm_default_user_details_text;
                $grid_data[$ai][8] = $arm_commission;
                $grid_data[$ai][9] = ($arm_plan_name != '') ? $arm_plan_name : '-';
             
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
        
        function arm_summery_list() {
            global $wpdb, $arm_affiliate, $arm_affiliate_settings, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $arm_aff_referrals;
            
            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $arm_currency = $arm_payment_gateways->arm_get_global_currency();
            
            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_statistics';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $grid_columns = array(
                'sr_no' => __('SR. No', 'ARM_AFFILIATE'),
                'date' => __('Date', 'ARM_AFFILIATE'),
                'visitor' => __('Total Visitor', 'ARM_AFFILIATE'),
                'total_converted' => __('Total Converted', 'ARM_AFFILIATE'),
                'earning' => __('Commission', 'ARM_AFFILIATE'),
                'revenue' => __('Revenue Amount', 'ARM_AFFILIATE'),
            );                 

            $where_condition = '';
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
                    $where_condition .= " AND (v.arm_date_time BETWEEN '$start_datetime' AND '$end_datetime') ";
                } else {
                    $where_condition .= " AND (v.arm_date_time > '$start_datetime') ";
                }
            } else {
                if (!empty($end_date)) {
                    $end_datetime = $arm_aff_referrals->date_convert_db_formate($end_date);  
                    $where_condition .= " AND (v.arm_date_time < '$end_datetime') ";
                }
            }
            
            $sub_where_condition = '';
            $sub_where_condition1 = '';
            $filter_plan_id = (!empty($_REQUEST['filter_plan_id']) && $_REQUEST['filter_plan_id'] != '0') ? $_REQUEST['filter_plan_id'] : '';
            if($filter_plan_id != '')
            { 
                $sub_where_condition = " AND arm_plan_id IN (".$filter_plan_id.")"; 
                $sub_where_condition1 = " AND arm_plan_id IN (".$filter_plan_id.")";
            }
            
            
            $tmp_query = "SELECT CAST(v.arm_date_time AS DATE) as arm_date, "
                            . " count(v.arm_affiliate_id) as total_visitor, "
                            . " (SELECT count(arm_affiliate_id) from {$arm_affiliate->tbl_arm_aff_referrals} where CAST(arm_date_time AS DATE) = arm_date ".$sub_where_condition." ) as total_converted, "
                            . " (SELECT sum(arm_amount) from {$arm_affiliate->tbl_arm_aff_referrals} where CAST(arm_date_time AS DATE) = arm_date ".$sub_where_condition.") as total_earning, "
                            . " (SELECT sum( arm_revenue_amount ) FROM `{$arm_affiliate->tbl_arm_aff_referrals}` WHERE CAST(arm_date_time AS DATE) = arm_date ".$sub_where_condition1.") AS sumplanamt  "
                    . " FROM {$arm_affiliate->tbl_arm_aff_visitors} v "
                    . " GROUP BY CAST(v.arm_date_time AS DATE)";
           
                    
            $form_result = $wpdb->get_results($tmp_query);
            $total_before_filter = count($form_result);
            
            $grid_data = array();
            $ai = 0;
  $tmp_query = "SELECT CAST(v.arm_date_time AS DATE) as arm_date, "
                            . " count(v.arm_affiliate_id) as total_visitor, "
                            . " (SELECT count(arm_affiliate_id) from {$arm_affiliate->tbl_arm_aff_referrals} where CAST(arm_date_time AS DATE) = arm_date ".$sub_where_condition." ) as total_converted, "
                            . " (SELECT sum(arm_amount) from {$arm_affiliate->tbl_arm_aff_referrals} where CAST(arm_date_time AS DATE) = arm_date and arm_status = 1 ".$sub_where_condition.") as total_earning, "
                            . " (SELECT sum( arm_revenue_amount ) FROM `{$arm_affiliate->tbl_arm_aff_referrals}` WHERE CAST(arm_date_time AS DATE) = arm_date ".$sub_where_condition1.") AS sumplanamt  "
                    . " FROM {$arm_affiliate->tbl_arm_aff_visitors} v "
                    . " WHERE 1=1 ".$where_condition
                    . " GROUP BY CAST(v.arm_date_time AS DATE)";
                        
            $form_result = $wpdb->get_results($tmp_query);
            $total_after_filter = count($form_result);
            
            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            
            $tmp_query = $tmp_query . " LIMIT {$offset},{$number}";
                        
            $form_result = $wpdb->get_results($tmp_query);
            $sr_no = $offset;
            foreach ($form_result as $summery) {                
                $sr_no++;
                
                $arm_date = date( $date_format, strtotime( $summery->arm_date ) );
                $total_visitor = $summery->total_visitor;
                $total_converted = $summery->total_converted;
                $total_earning = $summery->total_earning;
                $sumplanamt = ($summery->sumplanamt != '') ? $summery->sumplanamt : 0;
                
                $grid_data[$ai][0] = $sr_no;
                $grid_data[$ai][1] = $arm_date;
                $grid_data[$ai][2] = $total_visitor;
                $grid_data[$ai][3] = $total_converted;
                $grid_data[$ai][4] = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $total_earning). " " .$arm_currency;
                $grid_data[$ai][5] = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $sumplanamt ). " " .$arm_currency;
                
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
        
        function arm_aff_get_statistics(){
            
            global $wpdb, $arm_affiliate;
            
            $start_datetime = date('Y-m-d')." 00:00:00";
            $end_datetime = date('Y-m-d')." 23:59:59";
            
            $where_condition = " WHERE year( arm_date_time ) = year( curdate( ) ) AND month( arm_date_time ) = month( curdate( ) )";
            $aff_where_condition = " WHERE year( arm_start_date_time ) = year( curdate( ) ) AND month( arm_start_date_time ) = month( curdate( ) )";
            $user_table = $wpdb->users;
            
            //details about earnings
            $total_earning = $wpdb->get_row("SELECT sum(arm_amount) as total_earning FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id and r.arm_status = 1");
            $month_earning = $wpdb->get_row("SELECT sum(arm_amount) as total_earning FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id and r.arm_status = 1 ".$where_condition);

            //details about paid
            $total_paid = $wpdb->get_row("SELECT sum(arm_amount) as total_paid FROM `{$arm_affiliate->tbl_arm_aff_payouts}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id ");
            $month_paid = $wpdb->get_row("SELECT sum(arm_amount) as total_paid FROM `{$arm_affiliate->tbl_arm_aff_payouts}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id ".$where_condition);
            
            //details about unpaid
            $total_unpaid = $total_earning->total_earning - $total_paid->total_paid;
            $month_unpaid = $month_earning->total_earning - $month_paid->total_paid;
            
            //details about visits
            $total_visits = $wpdb->get_row("SELECT count(arm_visitor_id) as total_visits FROM `{$arm_affiliate->tbl_arm_aff_visitors}`");
            $month_visits = $wpdb->get_row("SELECT count(arm_visitor_id) as total_visits FROM `{$arm_affiliate->tbl_arm_aff_visitors}` ".$where_condition);
           
            //details about referrals
            $total_referral = $wpdb->get_row("SELECT count(arm_referral_id) as total_referral FROM `{$arm_affiliate->tbl_arm_aff_referrals}`");
            $month_referral = $wpdb->get_row("SELECT count(arm_referral_id) as total_referral FROM `{$arm_affiliate->tbl_arm_aff_referrals}` ".$where_condition);
            
            //details about affiliate
            $total_affiliate = $wpdb->get_row("SELECT count(arm_affiliate_id) as total_affiliate FROM `{$arm_affiliate->tbl_arm_aff_affiliates}`");
            $month_affiliate = $wpdb->get_row("SELECT count(arm_affiliate_id) as total_affiliate FROM `{$arm_affiliate->tbl_arm_aff_affiliates}` ".$aff_where_condition);
            
            $statistics_arr = array(
                'total_earning' => $total_earning->total_earning,
                'month_earning' => $month_earning->total_earning,
                
                'total_paid' => $total_paid->total_paid,
                'month_paid' => $month_paid->total_paid,
                
                'total_unpaid' => $total_unpaid,
                'month_unpaid' => $month_unpaid,
                
                'total_visits' => $total_visits->total_visits,
                'month_visits' => $month_visits->total_visits,
                
                'total_referral' => $total_referral->total_referral,
                'month_referral' => $month_referral->total_referral,
                
                'total_affiliate' => $total_affiliate->total_affiliate,
                'month_affiliate' => $month_affiliate->total_affiliate,
            );
            
            return $statistics_arr;   
        }

        function arm_aff_get_affiliate_user_statistics($user_aff_id)
        {
            global $wpdb, $arm_affiliate;
            $where_condition = " WHERE year( arm_date_time ) = year( curdate( ) ) AND month( arm_date_time ) = month( curdate( ) )";
            $user_table = $wpdb->users;
            if (!empty($user_aff_id))
            {
                //details about earnings
                $total_earning = $wpdb->get_row("SELECT sum(arm_amount) as total_earning FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id and r.arm_status = 1 WHERE aff.arm_affiliate_id = ". $user_aff_id);

                $month_earning = $wpdb->get_row("SELECT sum(arm_amount) as total_earning FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id and r.arm_status = 1 ".$where_condition ."and aff.arm_affiliate_id = ". $user_aff_id);

                //details about paid
                $total_paid = $wpdb->get_row("SELECT sum(arm_amount) as total_paid FROM `{$arm_affiliate->tbl_arm_aff_payouts}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id WHERE aff.arm_affiliate_id = ". $user_aff_id);
                $month_paid = $wpdb->get_row("SELECT sum(arm_amount) as total_paid FROM `{$arm_affiliate->tbl_arm_aff_payouts}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id ".$where_condition."and aff.arm_affiliate_id = ". $user_aff_id);
                
                //details about unpaid
                $total_unpaid = $total_earning->total_earning - $total_paid->total_paid;
                $month_unpaid = $month_earning->total_earning - $month_paid->total_paid;
                
                //details about visits
                $total_visits = $wpdb->get_row("SELECT count(arm_visitor_id) as total_visits FROM `{$arm_affiliate->tbl_arm_aff_visitors}` WHERE arm_affiliate_id = ". $user_aff_id);
                $month_visits = $wpdb->get_row("SELECT count(arm_visitor_id) as total_visits FROM `{$arm_affiliate->tbl_arm_aff_visitors}` ".$where_condition . "and arm_affiliate_id = " . $user_aff_id);
               
                //details about referrals
                $total_referral = $wpdb->get_row("SELECT count(arm_referral_id) as total_referral FROM `{$arm_affiliate->tbl_arm_aff_referrals}` WHERE arm_affiliate_id = " . $user_aff_id);
                $month_referral = $wpdb->get_row("SELECT count(arm_referral_id) as total_referral FROM `{$arm_affiliate->tbl_arm_aff_referrals}` ".$where_condition. "and arm_affiliate_id = " . $user_aff_id);

                $statistics_arr = array(
                    'total_earning' => $total_earning->total_earning,
                    'month_earning' => $month_earning->total_earning,
                    
                    'total_paid' => $total_paid->total_paid,
                    'month_paid' => $month_paid->total_paid,
                    
                    'total_unpaid' => $total_unpaid,
                    'month_unpaid' => $month_unpaid,
                    
                    'total_visits' => $total_visits->total_visits,
                    'month_visits' => $month_visits->total_visits,
                    
                    'total_referral' => $total_referral->total_referral,
                    'month_referral' => $month_referral->total_referral,
                );
                
                return $statistics_arr;
            }
        }
        
        function arm_aff_visits_export( $request ) {
            global $wpdb, $arm_affiliate, $arm_affiliate_settings, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $arm_aff_referrals, $arm_default_user_details_text;
            
            if( isset($request['arm_action']) && $request['arm_action'] == 'visits_export_csv' ) {
            
                $date_format = $arm_global_settings->arm_get_wp_date_format()." ".get_option( 'time_format' );
                $arm_currency = $arm_payment_gateways->arm_get_global_currency();
                $where_condition = '';
                
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
                        $where_condition .= " AND (v.arm_date_time BETWEEN '$start_datetime' AND '$end_datetime') ";
                    } else {
                        $where_condition .= " AND (v.arm_date_time > '$start_datetime') ";
                    }
                } else {
                    if (!empty($end_date)) {
                        $end_datetime = $arm_aff_referrals->date_convert_db_formate($end_date);  
                        $where_condition .= " AND (v.arm_date_time < '$end_datetime') ";
                    }
                }

                $filter_plan_id = (!empty($_REQUEST['filter_plan_id']) && $_REQUEST['filter_plan_id'] != '0') ? $_REQUEST['filter_plan_id'] : '';
                if($filter_plan_id != '')
                { $where_condition.= " AND r.arm_plan_id IN (".$filter_plan_id.")"; }

                $filter_user_id = (!empty($_REQUEST['filter_user_id']) && $_REQUEST['filter_user_id'] != '0') ? $_REQUEST['filter_user_id'] : '';
                if($filter_user_id != '')
                { $where_condition.= " AND aff.arm_user_id IN (".$filter_user_id.")"; }


                $search = isset($_REQUEST['sSearch']) ? $_REQUEST['sSearch'] : '';
                if($search != '')
                {
                    $where_condition.= " AND u.user_login LIKE '%{$search}%'"; 
                }

                $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
                $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 5;
                $order_by = ' v.arm_date_time';
                if( $sorting_col == 1 ) {
                    $order_by = ' v.arm_date_time';
                } 

                $grid_data = array();
                $ai = 0;
                $user_table = $wpdb->users;
                $tmp_query = "SELECT v.*, u.user_login, r.arm_ref_affiliate_id, r.arm_amount, r.arm_plan_id FROM `{$arm_affiliate->tbl_arm_aff_visitors}` v "
                            ." LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff "
                            ." ON aff.arm_affiliate_id = v.arm_affiliate_id "
                            ." LEFT JOIN `{$user_table}` u "
                            ." ON u.ID = aff.arm_user_id "
                            ." LEFT JOIN `{$arm_affiliate->tbl_arm_aff_referrals}` as r "
                            ." ON r.arm_referral_id = v.arm_referral_id "
                            ." WHERE 1=1 " . $where_condition
                            ." ORDER BY {$order_by} {$sorting_ord}";
                     
                $form_result = $wpdb->get_results($tmp_query);
                $sr_no = 0;
                if (!empty($form_result))
                {
                    $visits_data = array();
                    foreach ($form_result as $visits) {
                        $sr_no++;
                        $arm_affiliate_id = $visits->arm_affiliate_id;
                        $arm_referral_id = ($visits->arm_referral_id > 0) ? $visits->arm_referral_id : 0;
                        $arm_visitor_ip = $visits->arm_visitor_ip;
                        $arm_browser = $visits->arm_browser;
                        $arm_country = $visits->arm_country;
                        $arm_converted = ($visits->arm_referral_id > 0) ? 'Yes' : 'No';
                        $arm_date_time = date( $date_format, strtotime( $visits->arm_date_time ) );

                        $arm_affiliate_user_name = isset($visits->user_login) ? $visits->user_login : '';
                        if( empty($arm_affiliate_user_name)){
                            $arm_affiliate_user_name = $arm_default_user_details_text;
                        }

                        $arm_get_referral_user_data = get_userdata($visits->arm_ref_affiliate_id);
                        $arm_referral_user_name = (isset($arm_get_referral_user_data->user_login) && $arm_get_referral_user_data->user_login != '') ? $arm_get_referral_user_data->user_login : $arm_default_user_details_text;
                        $arm_commission = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $visits->arm_amount). " " .$arm_currency;
                        $arm_plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($visits->arm_plan_id);

                        $visits_data[] = array(
                            'SR No.' => $sr_no,
                            'arm_date_time' => $arm_date_time,
                            'arm_browser' => $arm_browser,
                            'arm_visitor_ip' => $arm_visitor_ip,
                            'arm_country' => $arm_country,
                            'arm_converted' => $arm_converted,
                            'arm_affiliate_user' => $arm_affiliate_user_name,
                            'arm_referral_user' => $arm_referral_user_name,
                            'arm_commission' => $arm_commission,
                            'arm_plan_name' => ($arm_plan_name != '') ? $arm_plan_name : '- ',
                        );
                        $ai++;
                    }
                    $arm_members_class->arm_export_to_csv($visits_data, 'ARMember-export-visits.csv', $delimiter=',');
                }
            }
        }
        
        function arm_aff_summery_export( $request ) {
            global $wpdb, $arm_affiliate, $arm_affiliate_settings, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $arm_aff_referrals;
            if( isset($request['arm_action']) && $request['arm_action'] == 'summery_export_csv' ) {  
                $date_format = $arm_global_settings->arm_get_wp_date_format();
                $arm_currency = $arm_payment_gateways->arm_get_global_currency();   

                $visits_data = array();
                $where_condition = '';
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
                        $where_condition .= " AND (v.arm_date_time BETWEEN '$start_datetime' AND '$end_datetime') ";
                    } else {
                        $where_condition .= " AND (v.arm_date_time > '$start_datetime') ";
                    }
                } else {
                    if (!empty($end_date)) {
                        $end_datetime = $arm_aff_referrals->date_convert_db_formate($end_date);  
                        $where_condition .= " AND (v.arm_date_time < '$end_datetime') ";
                    }
                }

                $sub_where_condition = '';
                $sub_where_condition1 = '';
                $filter_plan_id = (!empty($_REQUEST['filter_plan_id']) && $_REQUEST['filter_plan_id'] != '0') ? $_REQUEST['filter_plan_id'] : '';
                if($filter_plan_id != '')
                { 
                    $sub_where_condition = " AND arm_plan_id IN (".$filter_plan_id.")"; 
                    $sub_where_condition1 = " AND arm_plan_id IN (".$filter_plan_id.")";
                }

                $grid_data = array();
                $ai = 0;
                $tmp_query = "SELECT CAST(v.arm_date_time AS DATE) as arm_date, "
                                . " count(v.arm_affiliate_id) as total_visitor, "
                                . " (SELECT count(arm_affiliate_id) from {$arm_affiliate->tbl_arm_aff_referrals} where CAST(arm_date_time AS DATE) = arm_date ".$sub_where_condition." ) as total_converted, "
                                . " (SELECT sum(arm_amount) from {$arm_affiliate->tbl_arm_aff_referrals} where CAST(arm_date_time AS DATE) = arm_date and arm_status = 1 ".$sub_where_condition." ) as total_earning, "
                                . " (SELECT sum( arm_revenue_amount ) FROM `{$arm_affiliate->tbl_arm_aff_referrals}` WHERE CAST(arm_date_time AS DATE) = arm_date ".$sub_where_condition1.") AS sumplanamt  "
                        . " FROM {$arm_affiliate->tbl_arm_aff_visitors} v "
                        . " WHERE 1=1 ".$where_condition
                        . " GROUP BY CAST(v.arm_date_time AS DATE)";

                $form_result = $wpdb->get_results($tmp_query);
                $total_after_filter = count($form_result);

                $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
                $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;

                $tmp_query = $tmp_query . " LIMIT {$offset},{$number}";

                $form_result = $wpdb->get_results($tmp_query);
                $sr_no = $offset;
                foreach ($form_result as $summery) {                
                    $sr_no++;

                    $arm_date = date( $date_format, strtotime( $summery->arm_date ) );
                    $total_visitor = $summery->total_visitor;
                    $total_converted = $summery->total_converted;
                    $total_earning = $summery->total_earning;
                    $sumplanamt = ($summery->sumplanamt != '') ? $summery->sumplanamt : 0;

                    $grid_data[$ai][0] = $sr_no;
                    $grid_data[$ai][1] = $arm_date;
                    $grid_data[$ai][2] = $total_visitor;
                    $grid_data[$ai][3] = $total_converted;
                    $grid_data[$ai][4] = ($total_earning > 0) ? $total_earning : 0;
                    $grid_data[$ai][5] = ($sumplanamt > 0) ? $sumplanamt : 0;
                    
                    $visits_data[] = array(
                            'SR No.' => $sr_no,
                            'arm_date_time' => $arm_date,
                            'arm_visitor' => $total_visitor,
                            'arm_converted' => $total_converted,
                            'arm_commission' => $arm_payment_gateways->arm_amount_set_separator($arm_currency, $total_earning). " " .$arm_currency,
                            'arm_revenue_amount' => $arm_payment_gateways->arm_amount_set_separator($arm_currency, $sumplanamt ). " " .$arm_currency
                        );
                        $ai++;
                 
                }
                    $arm_members_class->arm_export_to_csv($visits_data, 'ARMember-export-summery.csv', $delimiter=',');

            }
        } 
    }
}

global $arm_aff_statistics, $arm_aff_date_range_exists;
$arm_aff_statistics = new arm_aff_statistics();
?>