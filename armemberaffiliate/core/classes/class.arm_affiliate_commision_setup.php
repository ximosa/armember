<?php
if(!class_exists('arm_affiliate_commision_setup')){
    
    class arm_affiliate_commision_setup{

        private $armaff_exist_affiliates;

        function __construct(){

            add_action( 'wp_ajax_armaff_commision_setup_plans_grid', array( $this, 'armaff_commision_setup_plans_grid_function' ) );
            add_action( 'wp_ajax_armaff_commision_setup_affiliates_grid', array( $this, 'armaff_commision_setup_affiliates_grid_function' ) );
            add_action( 'wp_ajax_armaff_get_plan_commision_setup_content', array( $this, 'armaff_get_plan_commision_setup_content_function' ) );
            add_action( 'wp_ajax_armaff_plan_commision_edit', array( $this, 'armaff_plan_commision_edit_function' ) );
            add_action( 'wp_ajax_arm_affiliate_commision_save', array( $this, 'arm_affiliate_commision_save_function' ) );
            add_action( 'wp_ajax_armaff_get_affiliate_commision_setup_content', array( $this, 'armaff_get_affiliate_commision_setup_content_function' ) );
            add_action( 'wp_ajax_armaff_setup_ajax_action', array( $this, 'armaff_setup_ajax_action_function' ) );

        }

        function armaff_commision_setup_plans_grid_function() {
            global $wpdb, $arm_affiliate, $ARMember, $arm_subscription_plans, $arm_payment_gateways;
            
            $armaff_currency = $arm_payment_gateways->arm_get_global_currency();

            $grid_columns = array(
                'planid' => __('Plan ID', 'ARM_AFFILIATE'),
                'planname' => __('Plan Name', 'ARM_AFFILIATE'),
                'plantype' => __('Plan Type', 'ARM_AFFILIATE'),
                'referraltype' => __('Referral Type', 'ARM_AFFILIATE'),
                'referralrate' => __('Referral Rate', 'ARM_AFFILIATE'),
            );

            $form_result = $this->armaff_get_all_subscription_plans();
            $total_before_filter = count($form_result);
            
            $where_condition = '';
            
            $sSearch = isset($_REQUEST['sSearch']) ? $_REQUEST['sSearch'] : '';

            $grid_data = array();
            $ai = 0;

            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;

            $form_result = $this->armaff_get_all_subscription_plans($offset,$number);
            $total_after_filter = count($form_result);


            foreach($form_result as $planData) {
                $planObj = new ARM_Plan();
                $planObj->init((object) $planData);
                $planID = $planData['arm_subscription_plan_id'];

                $armplan_type = '';

                if( $planObj->is_recurring() && isset($planObj->options['payment_cycles']) && count($planObj->options['payment_cycles']) > 1 ) {
                    $armplan_type = '<span class="arm_item_status_text active">' . __('Paid','ARM_AFFILIATE') . '</span><br/>
                    <a href="javascript:void(0);" onclick="arm_plan_cycle('. $planID .')">' . __('Multiple Cycle','ARM_AFFILIATE') . '</a>';
                } else {
                    $armplan_type = $planObj->plan_text(true);
                }

                $aff_referral_type_text = ''; $aff_referral_type_unit = '';$aff_referral_type = "";$aff_referral_rate = "";

                $is_referral_enable = isset($planObj->options['arm_affiliate_referral_disable']) ? $planObj->options['arm_affiliate_referral_disable'] : 0;
                if($is_referral_enable){
                    $aff_referral_type = isset($planObj->options['arm_affiliate_referral_type']) ? $planObj->options['arm_affiliate_referral_type'] : '';
                    $aff_referral_rate = isset($planObj->options['arm_affiliate_referral_rate']) ? $planObj->options['arm_affiliate_referral_rate'] : '';
                }

                if($aff_referral_type == 'percentage'){
                    $aff_referral_type_text = __('Percentage', 'ARM_AFFILIATE');
                    $aff_referral_type_unit = '%';
                } else if($aff_referral_type == 'fixed_rate'){
                    $aff_referral_type_text = __('Fixed Rate', 'ARM_AFFILIATE');
                    $aff_referral_type_unit = $armaff_currency;
                }

                if($aff_referral_rate != ''){
                    $aff_referral_rate = $aff_referral_rate. ' ' . $aff_referral_type_unit;
                }
                
                $grid_data[$ai][0] = $planID;
                $grid_data[$ai][1] = esc_html(stripslashes($planObj->name));
                $grid_data[$ai][2] = $armplan_type;
                $grid_data[$ai][3] = $aff_referral_type_text;
                
                $grid_data[$ai][4] = $aff_referral_rate;

                $gridAction = "<div class='arm_grid_action_btn_container'>";
                if (current_user_can('arm_manage_plans')) {
                    $gridAction .= "<a href='javascript:void(0)' onclick='armaff_edit_plan_commision({$planID})' class='armhelptip' title='" . __('Edit Referral', 'ARM_AFFILIATE') . "' ><img src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit.png' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit.png';\" /></a>";
                }
                $gridAction .= "</div>";

                $grid_data[$ai][5] = $gridAction;
             
                $ai++;
            }
            
            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
            $response = array(
                'sColumns' => implode(',', $grid_columns),
                'sEcho' => $sEcho,
                'iTotalRecords' => $total_before_filter, // Before Filtered Records
                'iTotalDisplayRecords' => $total_before_filter, // After Filter Records
                'aaData' => $grid_data,
            );
            echo json_encode($response);
            die();
        }

        function armaff_commision_setup_affiliates_grid_function() {
            global $wpdb, $arm_affiliate, $arm_global_settings, $arm_payment_gateways, $arm_default_user_details_text, $arm_affiliate_commision_setup, $arm_affiliate_settings, $armaff_rate_type_arr, $ARMember;
            
            $armaff_currency = $arm_payment_gateways->arm_get_global_currency();

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_commision_setup';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $grid_columns = array(
                'affiliateid' => __('Affiliate ID', 'ARM_AFFILIATE'),
                'Username' => __('Username', 'ARM_AFFILIATE'),
                'userid' => __('User ID', 'ARM_AFFILIATE'),
                'referraltype' => __('Referral Type', 'ARM_AFFILIATE'),
                'referralrate' => __('Referral Rate', 'ARM_AFFILIATE'),
                'recurringreferralstatus' => __('Recurring Referral Status', 'ARM_AFFILIATE'),
            );

            $form_result = $arm_affiliate_commision_setup->armaff_get_all_affiliates_commision_setup();
            $total_before_filter = count($form_result);
            
            $where_condition = '';
            
            $sSearch = isset($_REQUEST['sSearch']) ? $_REQUEST['sSearch'] : '';
            if($sSearch != '')
            {
                $where_condition.= " WHERE (u.user_login LIKE '%{$sSearch}%' || u.user_email LIKE '%{$sSearch}%' )";
            }

            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 0;
            $order_by = 'a.armaff_setup_id';
            if( $sorting_col == 0 ) {
                $order_by = 'a.armaff_affiliate_id';
            } else if( $sorting_col == 1 ) {
                $order_by = 'u.user_login';
            } else if( $sorting_col == 2 ){
                $order_by = 'a.armaff_user_id';
            } else if( $sorting_col == 4 ){
                $order_by = 'a.armaff_referral_rate';
            }

            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;

            $grid_data = array();
            $ai = 0;

            $user_table = $wpdb->users;

            $armaff_qry = "SELECT a.*, u.user_login FROM {$arm_affiliate->tbl_arm_aff_affiliates_commision} a LEFT JOIN `{$user_table}` u  ON u.ID = a.armaff_user_id" .$where_condition ." ORDER BY {$order_by} {$sorting_ord}";

            $armaff_qry = $armaff_qry . " LIMIT {$offset},{$number}";

            $form_result = $wpdb->get_results($armaff_qry, ARRAY_A);

            $total_after_filter = count($form_result);

            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
            $affiliate_encoding = isset($affiliate_options['arm_aff_id_encoding']) ? $affiliate_options['arm_aff_id_encoding'] : '0';

            foreach($form_result as $affiliateData) {
                $armaff_setup_id = $affiliateData['armaff_setup_id'];
                $armaff_affiliate_id = $affiliateData['armaff_affiliate_id'];
                $armaff_user_id = $affiliateData['armaff_user_id'];
                $armaff_referral_type = $affiliateData['armaff_referral_type'];
                $armaff_referral_rate = $affiliateData['armaff_referral_rate'];
                $armaff_recurring_referral_status = $affiliateData['armaff_recurring_referral_status'];
                $armaff_added_date = $affiliateData['armaff_added_date'];

                $armaff_rate_type = isset($armaff_rate_type_arr[$armaff_referral_type]) ? $armaff_rate_type_arr[$armaff_referral_type]['label'] : "-";

                $armaff_referral_type_unit = '';
                if($armaff_referral_type == 0){ $armaff_referral_type_unit = '%'; }
                if($armaff_referral_type == 1){ $armaff_referral_type_unit = $armaff_currency; }

                $recurring_referral_status = '<span class="armaff_disabled">'. __('Disabled', 'ARM_AFFILIATE').'</span>';
                if($armaff_recurring_referral_status == 1){
                    $recurring_referral_status = '<span class="armaff_enabled">'. __('Enabled', 'ARM_AFFILIATE').'</span>';
                }

                $armaff_encoded_affiliate_id = $arm_affiliate_settings->arm_affiliate_get_user_id($affiliate_encoding, $armaff_affiliate_id);

                $grid_data[$ai][0] = $armaff_encoded_affiliate_id;
                $grid_data[$ai][1] = !empty($affiliateData['user_login']) ? $affiliateData['user_login'] : $arm_default_user_details_text;
                $grid_data[$ai][2] = $armaff_user_id;
                $grid_data[$ai][3] = $armaff_rate_type;
                
                $grid_data[$ai][4] = $armaff_referral_rate.' '.$armaff_referral_type_unit;

                $grid_data[$ai][5] = $recurring_referral_status;

                $gridAction = "<div class='arm_grid_action_btn_container'>";
                if (current_user_can('arm_manage_plans')) {
                    $gridAction .= "<a href='javascript:void(0)' onclick='armaff_edit_affiliate_commision({$armaff_setup_id})' class='armhelptip' title='" . __('Edit Referral', 'ARM_AFFILIATE') . "' ><img src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit.png' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit.png';\" /></a>";
                    $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$armaff_setup_id});'><img src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete.png' class='armhelptip' title='" . __('Delete', 'ARM_AFFILIATE') . "' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete.png';\" /></a>";
                    $gridAction .= $arm_global_settings->arm_get_confirm_box($armaff_setup_id, __("Are you sure you want to delete setup for this affiliate user?", 'ARM_AFFILIATE'), 'armaff_affiliate_setup_delete_btn');
                }
                $gridAction .= "</div>";

                $grid_data[$ai][6] = $gridAction;
             
                $ai++;
            }
            
            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
            $response = array(
                'sColumns' => implode(',', $grid_columns),
                'sEcho' => $sEcho,
                'iTotalRecords' => $total_before_filter, // Before Filtered Records
                'iTotalDisplayRecords' => $total_before_filter, // After Filter Records
                'aaData' => $grid_data,
            );
            echo json_encode($response);
            die();
        }

        function armaff_get_plan_commision_setup_popup_content(){

            $response = array( 'type' => 'error', 'msg'=> __( 'There is an error while getting plan options, please try again.', 'ARM_AFFILIATE' ) );

            $armaff_planid = isset($_REQUEST['armaff_planid']) ? $_REQUEST['armaff_planid'] : 0;

            // if($armaff_planid > 0){

                global $arm_subscription_plans, $arm_aff_affiliate;

                $plan_options = array(
                    'access_type' => 'lifetime',
                    'payment_type' => 'one_time',
                    'recurring' => array('type' => 'D'),
                    'trial' => array('type' => 'D'),
                    'eopa' => array('type' => 'D'),
                    'pricetext' => '',
                    'expity_type' => 'joined_date_expiry',
                    'expiry_date' => date('Y-m-d 23:59:59'),
                    'upgrade_action' => 'immediate',
                    'downgrade_action' => 'on_expire',
                    'cancel_action' => 'block',
                    'cancel_plan_action' => 'immediate',
                    'eot' => 'block',
                    'payment_failed_action' => 'block',
                );

                $plan_data = $arm_subscription_plans->arm_get_subscription_plan($armaff_planid);
                if (!empty($plan_data['arm_subscription_plan_options'])) {
                    $plan_options = $plan_data['arm_subscription_plan_options'];
                }

                $armaff_content = $arm_aff_affiliate->armaff_get_plan_affiliate_settings_content($plan_options, 'affiliate_plan_edit');
                return $armaff_content;

            // }

            // echo json_encode($response); exit;
        }

        function armaff_get_plan_commision_setup_content_function(){

            $response = array( 'type' => 'error', 'msg'=> __( 'There is an error while getting plan options, please try again.', 'ARM_AFFILIATE' ) );

            $armaff_planid = isset($_REQUEST['armaff_planid']) ? $_REQUEST['armaff_planid'] : 0;

            if($armaff_planid > 0){

                global $arm_subscription_plans, $arm_aff_affiliate, $ARMember;

                if(method_exists($ARMember, 'arm_check_user_cap'))
                {
                    $arm_affiliate_capabilities = 'arm_affiliate_commision_setup';
                    $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
                }

                $plan_options = array(
                    'access_type' => 'lifetime',
                    'payment_type' => 'one_time',
                    'recurring' => array('type' => 'D'),
                    'trial' => array('type' => 'D'),
                    'eopa' => array('type' => 'D'),
                    'pricetext' => '',
                    'expity_type' => 'joined_date_expiry',
                    'expiry_date' => date('Y-m-d 23:59:59'),
                    'upgrade_action' => 'immediate',
                    'downgrade_action' => 'on_expire',
                    'cancel_action' => 'block',
                    'cancel_plan_action' => 'immediate',
                    'eot' => 'block',
                    'payment_failed_action' => 'block',
                );

                $plan_data = $arm_subscription_plans->arm_get_subscription_plan($armaff_planid);
                if (!empty($plan_data['arm_subscription_plan_options'])) {
                    $plan_options = $plan_data['arm_subscription_plan_options'];
                }

                $response = array( 'type' => 'success', 'armaff_plan_options'=> $plan_options );

            }

            echo json_encode($response); exit;
        }

        function armaff_plan_commision_edit_function(){
            if (!current_user_can('arm_affiliate')) {
                $response = array( 'type' => 'error', 'msg'=> __( 'Sorry, You do not have permission to perform this action', 'ARM_AFFILIATE' ) );
            } else {

                global $arm_subscription_plans, $ARMember, $wpdb, $arm_aff_affiliate;

                $plan_referral_data = $_POST;

                if(method_exists($ARMember, 'arm_check_user_cap'))
                {
                    $arm_affiliate_capabilities = 'arm_affiliate_commision_setup';
                    $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
                }

                $armaff_planid = isset($plan_referral_data['armaff_commision_plan_id']) ? $plan_referral_data['armaff_commision_plan_id'] : 0;

                $plan_options = array();
                if($armaff_planid > 0){

                    $plan_data = $arm_subscription_plans->arm_get_subscription_plan($armaff_planid);
                    
                    if (!empty($plan_data['arm_subscription_plan_options'])) {
                        $plan_options = $plan_data['arm_subscription_plan_options'];
                    }
                    $plan_options = $arm_aff_affiliate->before_save_field_membership_plan($plan_options, $plan_referral_data);

                    $plan_options = maybe_serialize($plan_options);

                    $armaff_update_plan = $wpdb->update($ARMember->tbl_arm_subscription_plans, array( 'arm_subscription_plan_options' => $plan_options ), array('arm_subscription_plan_id' => $armaff_planid));

                    $response = array( 'type' => 'success', 'msg'=> __( 'Plan referral detail is updated successfully', 'ARM_AFFILIATE' ) );
                } else {
                    $response = array( 'type' => 'error', 'msg'=> __( 'There is an error while saving plan referral options, please try again.', 'ARM_AFFILIATE' ) );
                }

            }
            echo json_encode($response);
            die;
        }

        function arm_affiliate_commision_save_function(){

            global $ARMember;

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_commision_setup';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $posted_data = $_POST;

            $response = array( 'type' => 'error', 'msg'=> __( 'Sorry, Affiliate user does not found.', 'ARM_AFFILIATE' ) );

            $arm_affiliate_user_id = isset($posted_data['arm_affiliate_user_id']) ? $posted_data['arm_affiliate_user_id'] : 0;
            $arm_affiliate_id = isset($posted_data['arm_affiliate_id']) ? $posted_data['arm_affiliate_id'] : 0;
            $arm_subscription_plan_options = isset($posted_data['arm_subscription_plan_options']) ? $posted_data['arm_subscription_plan_options'] : array();
            $armaff_referral_type = isset($arm_subscription_plan_options['arm_aff_affiliate_price_type']) ? $arm_subscription_plan_options['arm_aff_affiliate_price_type'] : 0;
            if($armaff_referral_type == 'percentage'){
                $armaff_referral_type = 0;
            } else if($armaff_referral_type == 'fixed_rate'){
                $armaff_referral_type = 1;
            }
            $armaff_referral_rate = isset($arm_subscription_plan_options['arm_aff_affilaite_rate']) ? $arm_subscription_plan_options['arm_aff_affilaite_rate'] : 0;

            $armaff_recurring_referral_status = isset($arm_subscription_plan_options['arm_aff_recurring_affiliate_disable_referral']) ? $arm_subscription_plan_options['arm_aff_recurring_affiliate_disable_referral'] : 0;
            $armaff_recurring_referral_type = isset($arm_subscription_plan_options['arm_aff_recurring_referral_type']) ? $arm_subscription_plan_options['arm_aff_recurring_referral_type'] : 0;
            if($armaff_recurring_referral_type == 'percentage'){
                $armaff_recurring_referral_type = 0;
            } else if($armaff_recurring_referral_type == 'fixed_rate'){
                $armaff_recurring_referral_type = 1;
            }

            $armaff_recurring_referral_rate = isset($arm_subscription_plan_options['arm_aff_recurring_affilaite_rate']) ? $arm_subscription_plan_options['arm_aff_recurring_affilaite_rate'] : 0;


            if ($arm_affiliate_user_id > 0 && $arm_affiliate_id > 0) {

                global $wpdb, $arm_affiliate;

                $arm_aff_action = isset($posted_data['arm_aff_action']) ? $posted_data['arm_aff_action'] : '';

                if($arm_aff_action == 'add'){

                    $check_commision_exists = $wpdb->get_row('SELECT armaff_setup_id FROM '.$arm_affiliate->tbl_arm_aff_affiliates_commision.' WHERE armaff_user_id = '.$arm_affiliate_user_id, ARRAY_A);
                    if($wpdb->num_rows > 0)
                    {
                        $arm_aff_action = 'exist';
                        $arm_affiliate_setup_id = $check_commision_exists['armaff_setup_id'];
                    }
                    else
                    {
                        $nowDate = current_time('mysql');
                        $arm_add_affiliate_commision = array(
                            'armaff_affiliate_id' => $arm_affiliate_id,
                            'armaff_user_id' => $arm_affiliate_user_id,
                            'armaff_referral_type' => $armaff_referral_type,
                            'armaff_referral_rate' => $armaff_referral_rate,
                            'armaff_recurring_referral_status' => $armaff_recurring_referral_status,
                            'armaff_recurring_referral_type' => $armaff_recurring_referral_type,
                            'armaff_recurring_referral_rate' => $armaff_recurring_referral_rate,
                            'armaff_added_date' => current_time('mysql')
                        );
                        $wpdb->insert($arm_affiliate->tbl_arm_aff_affiliates_commision, $arm_add_affiliate_commision);

                        $response = array( 'type' => 'success', 'msg'=> __( 'Affiliate user commission saved successfully', 'ARM_AFFILIATE' ) );
                    }

                } else if($arm_aff_action == 'edit'){

                    $wpdb->update( 
                            $arm_affiliate->tbl_arm_aff_affiliates_commision, 
                            array( 'armaff_referral_type' => $armaff_referral_type, 'armaff_referral_rate' => $armaff_referral_rate, 'armaff_recurring_referral_status' => $armaff_recurring_referral_status, 'armaff_recurring_referral_type' => $armaff_recurring_referral_type, 'armaff_recurring_referral_rate' => $armaff_recurring_referral_rate ), 
                            array( 'armaff_affiliate_id' => $arm_affiliate_id ), 
                            array( '%d' ),
                            array( '%d' ),
                            array( '%d' ),
                            array( '%d' ),
                            array( '%d' )
                    );

                    $response = array( 'type' => 'success', 'msg'=> __( 'Affiliate user commission updated successfully', 'ARM_AFFILIATE' ) );


                }

                if($arm_aff_action == 'exist') {
                    $response = array( 'type' => 'error', 'msg'=> __( 'Commission setup is already exist for this affiliate user.', 'ARM_AFFILIATE' ) );
                }

            }

            echo json_encode($response);
            die;
        }

        function armaff_get_all_affiliates_commision_setup(){
            global $wpdb, $arm_affiliate;

            $get_affiliates_commision = $wpdb->get_results("SELECT * FROM {$arm_affiliate->tbl_arm_aff_affiliates_commision}", ARRAY_A);
            return $get_affiliates_commision;
        }


        function armaff_get_affiliate_commision_setup_content_function(){

            $response = array( 'type' => 'error', 'msg'=> __( 'There is an error while getting setup options, please try again.', 'ARM_AFFILIATE' ) );

            $arm_affiliate_id = isset($_REQUEST['arm_affiliate_id']) ? $_REQUEST['arm_affiliate_id'] : 0;
            $armaff_setupid = isset($_REQUEST['armaff_setupid']) ? $_REQUEST['armaff_setupid'] : 0;

            global $wpdb, $arm_aff_affiliate, $arm_affiliate, $armaff_rate_type_arr, $arm_default_user_details_text, $ARMember;

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_commision_setup';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $armaff_options = array(
                'arm_affiliate_referral_disable' => 1,
                'arm_affiliate_referral_type' => 0,
                'arm_affiliate_referral_rate' => 0,
                'arm_affiliate_recurring_referral_disable' => 0,
                'arm_affiliate_recurring_referral_type' => 0,
                'arm_affiliate_recurring_referral_rate' => 0,
            );

            $armaff_affiliate_id = $armaff_user_id = 0; $armaff_username = $arm_default_user_details_text;

            if($arm_affiliate_id > 0 || $armaff_setupid > 0){

                $armaff_where = 'armaff_affiliate_id = '.$arm_affiliate_id;
                if($armaff_setupid > 0){
                    $armaff_where = 'armaff_setup_id = '.$armaff_setupid;
                }

                $armaff_get_commision = $wpdb->get_row('SELECT * FROM '.$arm_affiliate->tbl_arm_aff_affiliates_commision.' WHERE '.$armaff_where, ARRAY_A);

                if($wpdb->num_rows > 0)
                {
                    $armaff_referral_type = isset($armaff_rate_type_arr[$armaff_get_commision['armaff_referral_type']]) ? $armaff_rate_type_arr[$armaff_get_commision['armaff_referral_type']]['slug'] : '';
                    $armaff_options['arm_affiliate_referral_type'] = $armaff_referral_type;
                    $armaff_options['arm_affiliate_referral_rate'] = $armaff_get_commision['armaff_referral_rate'];

                    $armaff_options['arm_affiliate_recurring_referral_disable'] = $armaff_get_commision['armaff_recurring_referral_status'];
                    $armaff_recurring_referral_type = isset($armaff_rate_type_arr[$armaff_get_commision['armaff_recurring_referral_type']]) ? $armaff_rate_type_arr[$armaff_get_commision['armaff_recurring_referral_type']]['slug'] : '';
                    $armaff_options['arm_affiliate_recurring_referral_type'] = $armaff_recurring_referral_type;
                    $armaff_options['arm_affiliate_recurring_referral_rate'] = $armaff_get_commision['armaff_recurring_referral_rate'];

                    $armaff_affiliate_id = isset($armaff_get_commision['armaff_affiliate_id']) ? $armaff_get_commision['armaff_affiliate_id'] : 0;
                    $armaff_user_id = isset($armaff_get_commision['armaff_user_id']) ? $armaff_get_commision['armaff_user_id'] : 0;
                    $arm_get_affiliate_user_data = get_userdata($armaff_user_id);
                    $armaff_username = !empty($arm_get_affiliate_user_data->user_login) ? $arm_get_affiliate_user_data->user_login : $arm_default_user_details_text;

                }

            }

            $response = array( 'type' => 'success', 'armaff_options'=>$armaff_options, 'armaff_affiliate_id'=> $armaff_affiliate_id, 'armaff_user_id'=> $armaff_user_id, 'armaff_username'=> $armaff_username );

            echo json_encode($response); exit;
        }

        function armaff_get_affiliate_commision_setup_popup_content(){

            $response = array( 'type' => 'error', 'msg'=> __( 'There is an error while getting setup options, please try again.', 'ARM_AFFILIATE' ) );

            $arm_affiliate_id = isset($_REQUEST['arm_affiliate_id']) ? $_REQUEST['arm_affiliate_id'] : 0;
            $armaff_setupid = isset($_REQUEST['armaff_setupid']) ? $_REQUEST['armaff_setupid'] : 0;

            global $wpdb, $arm_aff_affiliate, $arm_affiliate, $armaff_rate_type_arr;

            $armaff_options = array(
                'arm_affiliate_referral_disable' => 1,
                'arm_affiliate_referral_type' => 0,
                'arm_affiliate_referral_rate' => 0,
                'arm_affiliate_recurring_referral_disable' => 0,
                'arm_affiliate_recurring_referral_type' => 0,
                'arm_affiliate_recurring_referral_rate' => 0,
            );

            $armaff_content = $arm_aff_affiliate->armaff_get_plan_affiliate_settings_content($armaff_options, 'arm_affiliate_setup');
            return $armaff_content;
        }

        function armaff_setup_ajax_action_function() {
            global $wpdb, $arm_affiliate, $ARMember;
            $action_data = $_POST;

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_commision_setup';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            if( isset( $action_data['act'] ) && $action_data['act'] ){
                if( isset( $action_data['id'] ) && $action_data['id'] != '' && $action_data['act'] == 'delete' )
                {
                    if (!current_user_can('arm_affiliate')) {
                        $response = array( 'type' => 'error', 'msg'=> __( 'Sorry, You do not have permission to perform this action.', 'ARM_AFFILIATE' ) );
                    } else {
                        $delete_setup = $wpdb->query( $wpdb->prepare( "DELETE FROM `$arm_affiliate->tbl_arm_aff_affiliates_commision` WHERE armaff_setup_id = %d", $action_data['id'] ) );
                        $response = array( 'type' => 'success', 'msg' => __( 'Affiliate user commission is deleted successfully.', 'ARM_AFFILIATE' ) );
                    }
                }
            }
            else
            {
                 $response = array( 'type' => 'error', 'msg'=> __( 'Sorry, Action not found.', 'ARM_AFFILIATE' ) );
            }
            echo json_encode($response);
            die;
        }

        function armaff_get_commision_for_affiliate_user($armaff_affiliate_id = 0){

            global $wpdb, $arm_affiliate;

            $armaff_affiliate_commision = array();

            if($armaff_affiliate_id != '' && $armaff_affiliate_id > 0){
                $armaff_affiliate_commision = $wpdb->get_row('SELECT * FROM '.$arm_affiliate->tbl_arm_aff_affiliates_commision.' WHERE armaff_affiliate_id = '.$armaff_affiliate_id, ARRAY_A);
            }

            return $armaff_affiliate_commision;
        }


        /**
         * Get all subscritpion plans
         * @return array of plans, False if there is no plan(s).
         */
        function armaff_get_all_subscription_plans($offset = '',$limit = '', $fields = 'all', $object_type = ARRAY_A, $allow_user_no_plan = false) {
            global $wp, $wpdb, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings;
            $selectFields = '*';
            if (!empty($fields)) {
                if ($fields != 'all' && $fields != '*') {
                    $selectFields = $fields;
                }
            }
            $object_type = !empty($object_type) ? $object_type : ARRAY_A;

            $armaff_qry_limit = '';
            if($offset != '' && $limit != ''){
                $armaff_qry_limit = " LIMIT {$offset},{$limit}";
            }

            $results = $wpdb->get_results("SELECT {$selectFields}, `arm_subscription_plan_id` FROM `" . $ARMember->tbl_arm_subscription_plans . "` WHERE `arm_subscription_plan_is_delete`='0' ORDER BY `arm_subscription_plan_id` DESC {$armaff_qry_limit}", $object_type);
            if (!empty($results) || $allow_user_no_plan) {
                $plans_data = array();
                if ($allow_user_no_plan) {
                    $plnID = -2;
                    $plnName = __('Users Having No Plan', 'ARM_AFFILIATE');
                    if ($object_type == OBJECT || $object_type == OBJECT_K) {
                        $sp->arm_subscription_plan_id = $plnID;
                        $sp->arm_subscription_plan_name = $plnName;
                        $sp->arm_subscription_plan_description = '';
                        $sp->arm_subscription_plan_options = array();
                    } else {
                        $sp['arm_subscription_plan_id'] = $plnID;
                        $sp['arm_subscription_plan_name'] = $plnName;
                        $sp['arm_subscription_plan_description'] = '';
                        $sp['arm_subscription_plan_options'] = array();
                    }
                    $plans_data[$plnID] = $sp;
                }
                if (!empty($results)) {
                    foreach ($results as $sp) {
                        if ($object_type == OBJECT || $object_type == OBJECT_K) {
                            $plnID = $sp->arm_subscription_plan_id;
                            if (isset($sp->arm_subscription_plan_name)) {
                                $sp->arm_subscription_plan_name = stripslashes($sp->arm_subscription_plan_name);
                            }
                            if (isset($sp->arm_subscription_plan_description)) {
                                $sp->arm_subscription_plan_description = stripslashes($sp->arm_subscription_plan_description);
                            }
                            if (isset($sp->arm_subscription_plan_options)) {
                                $sp->arm_subscription_plan_options = maybe_unserialize($sp->arm_subscription_plan_options);
                            }
                        } else {
                            $plnID = $sp['arm_subscription_plan_id'];
                            if (isset($sp['arm_subscription_plan_name'])) {
                                $sp['arm_subscription_plan_name'] = stripslashes($sp['arm_subscription_plan_name']);
                            }
                            if (isset($sp['arm_subscription_plan_description'])) {
                                $sp['arm_subscription_plan_description'] = stripslashes($sp['arm_subscription_plan_description']);
                            }
                            if (isset($sp['arm_subscription_plan_options'])) {
                                $sp['arm_subscription_plan_options'] = maybe_unserialize($sp['arm_subscription_plan_options']);
                            }
                        }
                        $plans_data[$plnID] = $sp;
                    }
                }
                return $plans_data;
            } else {
                return FALSE;
            }
        }


    }
}

global $arm_affiliate_commision_setup;
$arm_affiliate_commision_setup = new arm_affiliate_commision_setup();
?>