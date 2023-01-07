<?php
if(!class_exists('arm_aff_affiliate')){
    
    class arm_aff_affiliate{

        var $referral_status;
        function __construct(){
            
            $this->referral_status = array(
                '0'=>__('pending', 'ARM_AFFILIATE'), 
                '1'=>__('unpaid', 'ARM_AFFILIATE'), 
                '2'=>__('paid', 'ARM_AFFILIATE'), 
                '3'=>__('rejected', 'ARM_AFFILIATE')
            );
        
            add_action( 'wp_ajax_arm_affiliate_user_save', array( $this, 'arm_affiliate_user_save' ) );
            
            add_action( 'wp_ajax_arm_affiliate_list', array( $this, 'arm_affiliate_grid_data' ) );
            
            add_action( 'wp_ajax_arm_affiliate_ajax_action', array( $this, 'arm_affiliate_ajax_action' ) );
            
            add_action( 'wp_ajax_arm_affiliate_bulk_action', array( $this, 'arm_affiliate_bulk_action' ) );
            
            add_action( 'wp_ajax_arm_affiliate_update_status', array( $this, 'arm_affiliate_update_status' ) );
            
            add_action( 'user_register', array( $this, 'arm_affiliate_create_user' ), 10, 1 );
            
            add_action( 'delete_user', array( $this, 'arm_affiliate_delete_user'), 10, 1 );
            
            add_action( 'arm_display_field_add_membership_plan', array( $this, 'display_field_add_membership_plan_page' ) );
            
            add_filter( 'arm_befor_save_field_membership_plan', array( $this, 'before_save_field_membership_plan' ), 10, 2 );

            add_action( 'wp_ajax_arm_affiliate_register_account', array( $this, 'arm_affiliate_register_account') );
            add_action( 'wp_ajax_nopriv_arm_affiliate_register_account', array(&$this, 'arm_affiliate_register_account'));

            add_action( 'init', array( $this, 'armaff_flush_rewrite_rules'), 100 );
            add_action( 'init', array( $this, 'armaff_add_fancy_url_rule'), 101 );

            if ( function_exists( 'wc_get_page_id' ) && get_option( 'page_on_front' ) == wc_get_page_id( 'shop' ) ) {
                add_action( 'pre_get_posts', array( $this, 'armaff_unset_homepage_query_arg' ), -1 );
            } else {
                add_action( 'pre_get_posts', array( $this, 'armaff_unset_homepage_query_arg' ), 101 );
            }
            add_filter('arm_add_field_after_coupon_form',array($this,'armaff_add_field_in_coupon_form'),10,2);
            add_filter('arm_before_admin_save_coupon',array( $this, 'armaff_save_coupon_affilate_user'),10,2);
        }
        function armaff_save_coupon_affilate_user($coupondata,$posteddata){
            if(isset($posteddata['arm_caff_user'])){
                $coupondata['arm_coupon_aff_user']=$posteddata['arm_caff_user'];
            }
            return $coupondata;
        }
        function armaff_add_field_in_coupon_form($arm_aff_coupon_field_html, $c_data){
            global $arm_aff_affiliate;
            
            $selected_aff_user='';
            if(isset($c_data->arm_coupon_aff_user)){
               $selected_aff_user=$c_data->arm_coupon_aff_user;
            }
            $arm_aff_coupon_field_html .='<tr class="form-field form-required">
                        <th><label>'.esc_html('Select Affiliate user', 'ARM_AFFILIATE').'</label></th>
                        <td>
                            <input type="hidden" id="arm_caff_user" name="arm_caff_user" value="'.$selected_aff_user.'"/>
                            <dl class="arm_selectbox column_level_dd">
                                <dt><span>'.esc_html('Select User', 'ARM_AFFILIATE').'</span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                <dd>
                                    <ul data-id="arm_caff_user">
                                    ';
                                    $arm_all_active_affiliates=$arm_aff_affiliate->arm_get_all_active_affiliates_with_userinfo();
                                    if(!empty($arm_all_active_affiliates)){
                                        foreach ($arm_all_active_affiliates as $affuser) {
                                            $arm_aff_coupon_field_html.='<li data-label="'.esc_html($affuser['user_login'], 'ARM_AFFILIATE').' ( '.$affuser['user_email'].' )" data-value="'.$affuser['arm_affiliate_id'].'">'.esc_html($affuser['user_login'], 'ARM_AFFILIATE').' ( '.$affuser['user_email'].' )</li>';
                                        }
                                    }
                                    $arm_aff_coupon_field_html .='</ul>
                                </dd>
                            </dl>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="'.esc_html("When member use coupon code than selected Affiliate user will get referral as set referral in purchasing membership plan.", 'ARM_AFFILIATE').'"></i>
                        </td>
                    </tr>';
            return $arm_aff_coupon_field_html;        
        }
        function armaff_flush_rewrite_rules() {

            if( get_option( 'armaff_flush_rewrites' ) ) {

                flush_rewrite_rules();

                delete_option( 'armaff_flush_rewrites' );

            }

        }

        function armaff_add_fancy_url_rule() {

            global $arm_affiliate_settings;

            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
            $armaff_is_active_fancy_url = isset($affiliate_options['arm_aff_allow_fancy_url']) ? $affiliate_options['arm_aff_allow_fancy_url'] : 0;
            $affiliate_parmeter = isset($affiliate_options['arm_aff_referral_var']) ? $affiliate_options['arm_aff_referral_var'] : 'armaff';

            $taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ), 'objects' );
            foreach( $taxonomies as $tax_id => $tax ) {
                add_rewrite_rule( $tax->rewrite['slug'] . '/(.+?)/' . $affiliate_parmeter . '(/(.*))?/?$', 'index.php?' . $tax_id . '=$matches[1]&' . $affiliate_parmeter . '=$matches[3]', 'top');
            }
            add_rewrite_endpoint( $affiliate_parmeter, EP_ALL );

        }

        function armaff_unset_homepage_query_arg( $query ){

            if ( is_admin() || ! $query->is_main_query() ) {
                return;
            }

            global $wp, $arm_affiliate_settings, $arm_affiliate;

            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
            $armaff_referral_var = isset($affiliate_options['arm_aff_referral_var']) ? $affiliate_options['arm_aff_referral_var'] : 'armaff';
            $armaff_referral  = $query->get( $armaff_referral_var );
            $armaff_path = !empty( $_SERVER['REQUEST_URI' ] ) ? $_SERVER['REQUEST_URI' ] : '';

            if ( (!empty( $armaff_referral ) || strpos( $armaff_path, '/' . $armaff_referral_var ) !== false) && is_home() ) {

                $query->set( $armaff_referral_var, null );
                $armaff_cookie = $wp->query_vars[ $armaff_referral_var ];
                unset( $wp->query_vars[ $armaff_referral_var ] );
                $_REQUEST[$armaff_referral_var] = $armaff_cookie;

                if ( empty( $wp->query_vars ) && get_option( 'show_on_front' ) === 'page' ) {

                    $armaff_page = get_page_by_path( $armaff_referral_var );

                    if( $armaff_page ) {
                        $wp->query_vars['page_id'] = $armaff_page->ID;
                    } else {
                        $wp->query_vars['page_id'] = get_option( 'page_on_front' );
                    }
                    if($armaff_cookie != ""){
                        $_REQUEST[$armaff_referral_var] = $armaff_cookie;
                        /*$arm_affiliate->arm_set_ref_in_cookie();*/
                    }
                    $query->parse_query( $wp->query_vars );

                }

            }

        }

        function arm_affiliate_except_user_get_all_members($type = 0, $only_total = 0) {
            global $wp, $wpdb, $arm_errors, $ARMember, $arm_affiliate;

            $user_table = $wpdb->users;
            $usermeta_table = $wpdb->usermeta;
            $capability_column = $wpdb->get_blog_prefix($GLOBALS['blog_id']) . 'capabilities';

            $super_admin_ids = array();
            if (is_multisite()) {
                $super_admin = get_super_admins();
                if (!empty($super_admin)) {
                    foreach ($super_admin as $skey => $sadmin) {
                        if ($sadmin != '') {
                            $user_obj = get_user_by('login', $sadmin);
                            if ($user_obj->ID != '') {
                                $super_admin_ids[] = $user_obj->ID;
                            }
                        }
                    }
                }
            } else {
                $armaff_admin = array(
                    'role' => 'administrator',
                    'fields'   => 'ID'
                );
                $super_admin_ids = get_users($armaff_admin);
            }

            $user_where = " WHERE 1=1";
            if (!empty($super_admin_ids)) {
                $user_where .= " AND u.ID NOT IN (" . implode(',', $super_admin_ids) . ")";
            }

            /////////////// Start exclude already affiliate user
            $get_affiliate_user_id = $wpdb->get_results("SELECT arm_user_id FROM {$arm_affiliate->tbl_arm_aff_affiliates}", ARRAY_A);
            $affiliate_user_id = array();
            if(!empty($get_affiliate_user_id))
            {
                foreach($get_affiliate_user_id as $affiliate_user)
                {
                    $affiliate_user_id[] = $affiliate_user['arm_user_id'];
                }
            }
            if(!empty($affiliate_user_id)){
                $user_where .= " AND u.ID NOT IN (" . implode(',', $affiliate_user_id) . ")";
            }
            ////////////// End exclude already affiliate user
            
            
            $operator = " AND ";

            $user_where .= " {$operator} um.meta_key = '{$capability_column}' ";
            //$user_where .= " AND um.meta_value NOT LIKE '%administrator%' ";
            $user_join = "";
            if (!empty($type) && in_array($type, array(1, 2, 3))) {
                $user_join = " INNER JOIN {$ARMember->tbl_arm_members} arm1 ON u.ID = arm1.arm_user_id";
                $user_where .= " AND arm1.arm_primary_status='$type' ";
            }

            $user_fields = "u.ID,u.user_registered,u.user_login";
            $user_group_by = " GROUP BY u.ID ";
            $user_order_by = " ORDER BY u.user_registered DESC";
            if ($only_total > 0) {
                $user_fields = " COUNT(*) as total ";
                $user_group_by = "";
                $user_order_by = "";
            }

            $user_query = "SELECT {$user_fields} FROM `{$user_table}` u LEFT JOIN `{$usermeta_table}` um ON u.ID = um.user_id {$user_join} {$user_where} {$user_group_by} {$user_order_by} ";
            $users_details = $wpdb->get_results($user_query);

            if ($only_total > 0) {
                $all_members = $users_details[0]->total;
            } else {
                $all_members = $users_details;
            }

            return $all_members;
        }
        
        function arm_get_affiliate_users($armaff_reason = '') {
            global $wp, $wpdb, $arm_errors, $ARMember, $arm_affiliate;
            
            $type = 0;
            $only_total = 0;
            $user_table = $wpdb->users;
            $usermeta_table = $wpdb->usermeta;
            $capability_column = $wpdb->get_blog_prefix($GLOBALS['blog_id']) . 'capabilities';

            $super_admin_ids = array();
            if (is_multisite()) {
                $super_admin = get_super_admins();
                if (!empty($super_admin)) {
                    foreach ($super_admin as $skey => $sadmin) {
                        if ($sadmin != '') {
                            $user_obj = get_user_by('login', $sadmin);
                            if ($user_obj->ID != '') {
                                $super_admin_ids[] = $user_obj->ID;
                            }
                        }
                    }
                }
            } else {
                $armaff_admin = array(
                    'role' => 'administrator',
                    'fields'   => 'ID'
                );
                $super_admin_ids = get_users($armaff_admin);
            }

            $user_where = " WHERE 1=1";
            if (!empty($super_admin_ids)) {
                $user_where .= " AND u.ID NOT IN (" . implode(',', $super_admin_ids) . ")";
            }

            /////////////// Start exclude already affiliate user
            $affiliates_fields = 'arm_user_id';
            if($armaff_reason == 'add_commision'){
                $affiliates_fields .= ', arm_affiliate_id';
            }
            $get_affiliate_user_id = $wpdb->get_results("SELECT {$affiliates_fields} FROM {$arm_affiliate->tbl_arm_aff_affiliates}", ARRAY_A);
            $affiliate_user_id = $affiliate_ids = array();
            if(!empty($get_affiliate_user_id))
            {
                foreach($get_affiliate_user_id as $affiliate_user)
                {
                    $affiliate_user_id[] = $affiliate_user['arm_user_id'];
                    if($armaff_reason == 'add_commision'){
                        $affiliate_ids[$affiliate_user['arm_user_id']] = $affiliate_user['arm_affiliate_id'];
                    }
                }
            }
            if(!empty($affiliate_user_id)){
                $user_where .= " AND u.ID IN (" . implode(',', $affiliate_user_id) . ")";
            }
            ////////////// End exclude already affiliate user
            
            
            $operator = " AND ";

            $user_where .= " {$operator} um.meta_key = '{$capability_column}' ";
            //$user_where .= " AND um.meta_value NOT LIKE '%administrator%' ";
            $user_join = "";
            if (!empty($type) && in_array($type, array(1, 2, 3))) {
                $user_join = " INNER JOIN {$ARMember->tbl_arm_members} arm1 ON u.ID = arm1.arm_user_id";
                $user_where .= " AND arm1.arm_primary_status='$type' ";
            }

            $user_fields = "u.ID,u.user_registered,u.user_login";
            $user_group_by = " GROUP BY u.ID ";
            $user_order_by = " ORDER BY u.user_registered DESC";
            if ($only_total > 0) {
                $user_fields = " COUNT(*) as total ";
                $user_group_by = "";
                $user_order_by = "";
            }

            $user_query = "SELECT {$user_fields} FROM `{$user_table}` u LEFT JOIN `{$usermeta_table}` um ON u.ID = um.user_id {$user_join} {$user_where} {$user_group_by} {$user_order_by} ";

            $users_details = $wpdb->get_results($user_query);

            if ($only_total > 0) {
                $all_members = $users_details[0]->total;
            } else {
                $all_members = $users_details;
            }

            if($armaff_reason == 'add_commision'){
                $all_affiliates = array(
                    'all_members' => $all_members,
                    'all_affiliates' => $affiliate_ids
                );

                return $all_affiliates;
            }

            return $all_members;
        }
        
        function arm_affiliate_user_save() {
            global $wpdb, $arm_affiliate, $ARMember,$arm_manage_communication;

            if(method_exists($ARMember, 'arm_check_user_cap')){
                $arm_affiliate_capabilities = 'arm_affiliate';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }
            $posted_data = $_POST;
            
            if(!isset($posted_data['arm_affiliate_user_id_hidden']) && isset($posted_data['arm_affiliate_user_id']))
            {
                $arm_affiliate_user_id_username_arr = explode("(", $posted_data['arm_affiliate_user_id']);
                $arm_affiliate_user_id_username = isset($arm_affiliate_user_id_username_arr[0]) ? $arm_affiliate_user_id_username_arr[0] : '';
                if(!empty($arm_affiliate_user_id_username))
                {
                    $user_obj = get_user_by('login', $arm_affiliate_user_id_username);
                    if(!empty($user_obj->data->ID))
                    {
                        $posted_data['arm_affiliate_user_id_hidden'] = $user_obj->data->ID;
                    }
                    
                }
            }
            
            if(isset($posted_data['arm_affiliate_user_id_hidden']) && $posted_data['arm_affiliate_user_id_hidden'] != '')
            {
                $arm_aff_action = '';
                $arm_aff_end_date = '';
                $arm_affiliate_user_id = $posted_data['arm_affiliate_user_id_hidden'];
                if(isset($posted_data['arm_aff_expire_after_days']) && $posted_data['arm_aff_expire_after_days'] != '' && $posted_data['arm_aff_expire_after_days'] != '0'){
                    $arm_aff_end_date = date('Y-m-d 11:59:59', strtotime('+' . $posted_data['arm_aff_expire_after_days'] . ' days'));   
                }
                
                if(isset($posted_data['arm_aff_action']) && $posted_data['arm_aff_action'] == 'add')
                {
                    $check_user_exists = $wpdb->get_row('SELECT arm_affiliate_id FROM '.$arm_affiliate->tbl_arm_aff_affiliates.' WHERE arm_user_id = '.$posted_data['arm_affiliate_user_id_hidden'], ARRAY_A);
                    if($wpdb->num_rows > 0)
                    {
                        $arm_aff_action = 'edit';
                        $arm_affiliate_user_id = $check_user_exists['arm_affiliate_id'];
                    }
                    else
                    {
                        $nowDate = current_time('mysql');
                        $arm_add_affiliate = array(
                            'arm_user_id' => $posted_data['arm_affiliate_user_id_hidden'],
                            'arm_status' => '1',
                            'arm_start_date_time' => $nowDate,
                            'arm_end_date_time' => $arm_aff_end_date
                        );
                        $arm_add_affiliate_response=$wpdb->insert($arm_affiliate->tbl_arm_aff_affiliates, $arm_add_affiliate);
                        
                        if($arm_add_affiliate_response==1){
			    $plan_id='0';
                            $user_plans = get_user_meta($arm_affiliate_user_id, 'arm_user_plan_ids', true);
                            if(!empty($user_plans)){
                                $plan_id=$user_plans[0];
                            }
                            $arm_manage_communication->membership_communication_mail('arm_notify_on_register_affiliate_account',$arm_affiliate_user_id,$plan_id);
                        }
                        $response = array( 'type' => 'success', 'msg'=> __( 'Affiliate user saved successfully', 'ARM_AFFILIATE' ) );
                    }
                }
                else
                {
                    $arm_aff_action = $posted_data['arm_aff_action'];
                }
                
                if($arm_aff_action == 'edit'){
                    
                    $wpdb->update( 
                            $arm_affiliate->tbl_arm_aff_affiliates, 
                            array( 'arm_end_date_time' => $arm_aff_end_date, ), 
                            array( 'arm_affiliate_id' => $arm_affiliate_user_id ), 
                            array( '%s' ), 
                            array( '%d' ) 
                    );

                    $response = array( 'type' => 'success', 'msg'=> __( 'Affiliate user saved successfully', 'ARM_AFFILIATE' ) );
                }
            }
            else
            {
                $response = array( 'type' => 'error', 'msg'=> __( 'Sorry, User not found.', 'ARM_AFFILIATE' ) );
            }
            echo json_encode($response);
            die;
        }
        
        function arm_affiliate_grid_data() {
            global $wpdb, $arm_affiliate, $arm_affiliate_settings, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $arm_aff_referrals, $arm_default_user_details_text;

            if(method_exists($ARMember, 'arm_check_user_cap')){
                $arm_affiliate_capabilities = 'arm_affiliate';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $nowDate = current_time('mysql');
            $current_time = current_time('timestamp');
            $currency = $arm_payment_gateways->arm_get_global_currency();
            
            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
            $affiliate_link = isset($affiliate_options['arm_aff_referral_url']) ? $affiliate_options['arm_aff_referral_url'] : get_home_url();
            $affiliate_parmeter = isset($affiliate_options['arm_aff_referral_var']) ? $affiliate_options['arm_aff_referral_var'] : 'ref';
            $affiliate_encoding = isset($affiliate_options['arm_aff_id_encoding']) ? $affiliate_options['arm_aff_id_encoding'] : '0';
                    
            $grid_columns = array(
                'username' => __('Username', 'ARM_AFFILIATE'),
                'email' => __('Email', 'ARM_AFFILIATE'),
                'start_date' => __('Affiliate Starts From', 'ARM_AFFILIATE'),
                'status' => __('Status', 'ARM_AFFILIATE'),
                'affiliate_link' => __('Affiliate Link', 'ARM_AFFILIATE'),
                'visitor' => __('No. Of Visitors', 'ARM_AFFILIATE'),
                'converted_user' => __('Converted As User', 'ARM_AFFILIATE'),
                'revenue_amount' => __('Revenue Amount', 'ARM_AFFILIATE'),
            );

            $tmp_query = "SELECT * FROM {$arm_affiliate->tbl_arm_aff_affiliates}";
            $form_result = $wpdb->get_results($tmp_query);
            $total_before_filter = count($form_result);
            
            $where_condition = '';
            
            $sSearch = isset($_REQUEST['sSearch']) ? $_REQUEST['sSearch'] : '';
            if($sSearch != '')
            { $where_condition.= " AND (u.user_login LIKE '%{$sSearch}%' || u.user_email LIKE '%{$sSearch}%' )"; }
            
            $arm_status = isset($_REQUEST['filter_status_id']) ? $_REQUEST['filter_status_id'] : '';
            if($arm_status == '0' || $arm_status == '1')
            { 
                $where_condition.= " AND a.arm_status = '{$arm_status}' AND (curdate() < arm_end_date_time OR arm_end_date_time='0000-00-00 00:00:00')";
            }
            else if($arm_status == '2')
            { $where_condition.= " AND curdate() > arm_start_date_time AND curdate() > arm_end_date_time AND arm_end_date_time!='0000-00-00 00:00:00'"; }
            
            $date_filter_field = 'arm_start_date_time';
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
                    $where_condition .= " AND (a.$date_filter_field BETWEEN '$start_datetime' AND '$end_datetime') ";
                } else {
                    $where_condition .= " AND (a.$date_filter_field > '$start_datetime') ";
                }
            } else {
                if (!empty($end_date)) {
                    $end_datetime = $arm_aff_referrals->date_convert_db_formate($end_date);  
                    $where_condition .= " AND (a.$date_filter_field < '$end_datetime') ";
                }
            }
            
            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 5;
            $order_by = 'a.arm_start_date_time';
            if( $sorting_col == 1 ) {
                $order_by = 'u.user_login';
            } else if( $sorting_col == 2 ){
                $order_by = 'u.user_email';
            } else if( $sorting_col == 6 ){
                $order_by = 'user_visitor';
            } else if( $sorting_col == 7 ){
                $order_by = 'converted_user';
            } else if( $sorting_col == 8 ){
                $order_by = 'sumplanamt';
            }
            
            $grid_data = array();
            $ai = 0;
            $user_table = $wpdb->users;
            $tmp_query = "SELECT a.* "
                        . " ,(
                            SELECT count(arm_visitor_id)
                            FROM `{$arm_affiliate->tbl_arm_aff_visitors}`
                            WHERE arm_affiliate_id = a.arm_affiliate_id
                            GROUP BY arm_affiliate_id
                        ) AS user_visitor"
                        ." ,(
                           SELECT count( arm_affiliate_id )
                           FROM `{$arm_affiliate->tbl_arm_aff_referrals}`
                           WHERE arm_affiliate_id = a.arm_affiliate_id
                           GROUP BY arm_affiliate_id
                        ) AS converted_user "
                        ." ,( 
                           SELECT sum( arm_revenue_amount )
                           FROM `{$arm_affiliate->tbl_arm_aff_referrals}`
                           WHERE arm_affiliate_id = a.arm_affiliate_id
                        ) AS sumplanamt "
                        ." FROM `{$arm_affiliate->tbl_arm_aff_affiliates}` a "
                        ." LEFT JOIN `{$user_table}` u "
                        ." ON u.ID = a.arm_user_id"
                        ." WHERE 1=1 "
                        .$where_condition
                        ." ORDER BY {$order_by} {$sorting_ord}";
            
                        //$ARMember->arm_write_response("where condition - ".$tmp_query);
                        
            $form_result = $wpdb->get_results($tmp_query);
            $total_after_filter = count($form_result);
            
            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            
            $tmp_query = $tmp_query . " LIMIT {$offset},{$number}";
                        
            $form_result = $wpdb->get_results($tmp_query);
            
            foreach ($form_result as $affiliate) {
                $arm_affiliate_id = $affiliate->arm_affiliate_id;
                $arm_user_id = $affiliate->arm_user_id;
                $arm_affiliate_user_status = $affiliate->arm_status;
                $arm_start_date =  date( $date_format, strtotime( $affiliate->arm_start_date_time ) );
                
                $arm_get_affiliate_user_data = get_userdata($arm_user_id);
                $arm_affiliate_user_name = !empty($arm_get_affiliate_user_data->user_login) ? $arm_get_affiliate_user_data->user_login : $arm_default_user_details_text;
                $arm_affiliate_user_email = !empty($arm_get_affiliate_user_data->user_email) ? $arm_get_affiliate_user_data->user_email : $arm_default_user_details_text;
                $arm_visitor = $affiliate->user_visitor;
                $arm_converted_user = $affiliate->converted_user;
                $arm_sum_amountplan = ($affiliate->sumplanamt != '') ? $affiliate->sumplanamt : 0;

                $arm_aff_active = '';
                $arm_aff_active .= '<div class="arm_temp_switch_wrapper" style="width: auto;margin: 5px 0;">';
                $arm_aff_active .= '<div class="armswitch arm_affiliate_active">';
                $arm_aff_active .= '<input type="checkbox" id="arm_affiliate_active_switch_'.$arm_affiliate_id.'" value="1" class="armswitch_input arm_affiliate_active_switch" name="arm_affiliate_active_switch_'.$arm_affiliate_id.'" data-item_id="'.$arm_affiliate_id.'" '.checked($arm_affiliate_user_status, 1, false).'/>';
                $arm_aff_active .= '<label for="arm_affiliate_active_switch_'.$arm_affiliate_id.'" class="armswitch_label"></label>';
                $arm_aff_active .= '<span class="arm_status_loader_img" style="display: none;"></span>';
                $arm_aff_active .= '</div></div>';
                
                $arm_affiliate_encoding_id = $arm_affiliate_settings->arm_affiliate_get_user_id($affiliate_encoding, $arm_affiliate_id);
                
                $grid_data[$ai][0] = "<input id=\"cb-item-action-{$arm_affiliate_id}\" class=\"chkstanard\" type=\"checkbox\" value=\"{$arm_affiliate_id}\" name=\"item-action[]\">";
                $grid_data[$ai][1] = $arm_affiliate_user_name;
                $grid_data[$ai][2] = $arm_affiliate_user_email;
                $grid_data[$ai][3] = $arm_start_date;
                
                $grid_data[$ai][4] = $arm_aff_active;
                $grid_data[$ai][5] = $arm_affiliate_encoding_id;
                $grid_data[$ai][6] = ($arm_visitor > 0) ? $arm_visitor : 0;
                $grid_data[$ai][7] = ($arm_converted_user > 0) ? $arm_converted_user : 0;

                $grid_data[$ai][8] = $arm_payment_gateways->arm_amount_set_separator($currency, $arm_sum_amountplan)." ".$currency;
                
                $gridAction = "<div class='arm_grid_action_btn_container'>";                                    
                $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$arm_affiliate_id});'><img src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete.png' class='armhelptip' title='" . __('Delete', 'ARM_AFFILIATE') . "' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete.png';\" /></a>";
                                    $gridAction .= $arm_global_settings->arm_get_confirm_box($arm_affiliate_id, __("Are you sure you want to delete this affiliate user?", 'ARM_AFFILIATE'), 'arm_aff_affiliate_delete_btn');
                                $gridAction .= "</div>";
                $grid_data[$ai][9] = $gridAction;
             
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
        
        function arm_affiliate_ajax_action() {
            global $wpdb, $arm_affiliate, $ARMember;
            $action_data = $_POST;

            if(method_exists($ARMember, 'arm_check_user_cap')){
                $arm_affiliate_capabilities = 'arm_affiliate';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }
            if( isset( $action_data['act'] ) && $action_data['act'] ){
                if( isset( $action_data['id'] ) && $action_data['id'] != '' && $action_data['act'] == 'delete' )
                {
                    if (!current_user_can('arm_affiliate')) {
                        $response = array( 'type' => 'error', 'msg'=> __( 'Sorry, You do not have permission to perform this action.', 'ARM_AFFILIATE' ) );
                    } else {
                        $delete_referral = $wpdb->query( $wpdb->prepare( "DELETE FROM `$arm_affiliate->tbl_arm_aff_affiliates` WHERE arm_affiliate_id = %d", $action_data['id'] ) );
                        $delete_commision_setup = $wpdb->query( $wpdb->prepare( "DELETE FROM `$arm_affiliate->tbl_arm_aff_affiliates_commision` WHERE armaff_affiliate_id = %d", $action_data['id'] ) );
                        $response = array( 'type' => 'success', 'msg' => __( 'Affiliate is deleted successfully.', 'ARM_AFFILIATE' ) );
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
        
        function arm_affiliate_bulk_action() {
            global $wpdb, $ARMember, $arm_members_class, $arm_global_settings, $arm_subscription_plans, $arm_case_types, $arm_affiliate;
            if (!isset($_POST)) {
                    return;
            }
            
            if(method_exists($ARMember, 'arm_check_user_cap')){
                $arm_affiliate_capabilities = 'arm_affiliate';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $bulkaction = $arm_global_settings->get_param('action1');
            $ids = $arm_global_settings->get_param('item-action', '');
                        
            if (empty($ids)) {
                $errors[] = __('Please select one or more records.', 'ARM_AFFILIATE');
            } else {
                if ($bulkaction == '' || $bulkaction == '-1') {
                        $errors[] = __('Please select valid action.', 'ARM_AFFILIATE');
                } else 
                {
                    if (!is_array($ids)) {
                        $ids = explode(',', $ids);
                    }
                    
                    if (!current_user_can('arm_affiliate')) {
                        $errors[] = __('Sorry, You do not have permission to perform this action', 'ARM_AFFILIATE');
                    } else {
                        if (is_array($ids)) {
                            $aff_ids = implode(',',$ids);
                            $delete_referral = $wpdb->query( "DELETE FROM `$arm_affiliate->tbl_arm_aff_affiliates` WHERE arm_affiliate_id IN (".$aff_ids.")");
                            $delete_commision_setup = $wpdb->query( "DELETE FROM `$arm_affiliate->tbl_arm_aff_affiliates_commision` WHERE armaff_affiliate_id IN (".$aff_ids.")");
                            $message = __('Affiliate(s) has been deleted successfully.', 'ARM_AFFILIATE');
                            $return_array = array( 'type'=>'success', 'msg'=>$message );
                        }
                    }
                }
            }
            if(!isset($return_array))
            {
                $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
                $ARMember->arm_set_message('success',$message);
            }
            echo json_encode($return_array);
            die;
        }
        
        function arm_affiliate_update_status() {
            if (current_user_can('administrator')) {
                global $wpdb, $arm_affiliate, $ARMember;

                if(method_exists($ARMember, 'arm_check_user_cap')){
                    $arm_affiliate_capabilities = 'arm_affiliate';
                    $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
                }
                $wpdb->update( 
                        $arm_affiliate->tbl_arm_aff_affiliates, 
                        array( 'arm_status' => $_REQUEST['arm_aff_status'], ), 
                        array( 'arm_affiliate_id' => $_REQUEST['arm_aff_user_id'] ), 
                        array( '%s' ), 
                        array( '%d' ) 
                    );
                
                $response = array( 'type'=>'success');
            }
            echo json_encode($response);
            die;
        }
        
        function arm_affiliate_create_user( $user_id ) {
            global $wpdb, $ARMember,$arm_affiliate_settings, $arm_affiliate;
            
            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
            $allow_register_as_affiliate = isset($affiliate_options['arm_aff_allow_affiliate_register']) ? $affiliate_options['arm_aff_allow_affiliate_register'] : 0;
            if($allow_register_as_affiliate == '1' && $user_id != '')
            {
                $nowDate = current_time('mysql');
                $arm_add_affiliate = array(
                    'arm_user_id' => $user_id,
                    'arm_status' => '1',
                    'arm_start_date_time' => $nowDate,
                    'arm_end_date_time' => ''
                );
                $wpdb->insert($arm_affiliate->tbl_arm_aff_affiliates, $arm_add_affiliate);   
            }
        }
        
        function arm_affiliate_delete_user( $user_id ) {
            global $wpdb, $arm_affiliate;
            /*$wpdb->query( "DELETE FROM `$arm_affiliate->tbl_arm_aff_affiliates` WHERE arm_user_id = " . $user_id ." ");*/
            $wpdb->query( "UPDATE `$arm_affiliate->tbl_arm_aff_affiliates` SET arm_user_id='0' WHERE arm_user_id = '" . $user_id ."' ");
            $wpdb->query( "DELETE FROM `$arm_affiliate->tbl_arm_aff_affiliates_commision` WHERE armaff_user_id = " . $user_id ." ");
        }
        
        function display_field_add_membership_plan_page( $plan_options ) {

            global $arm_aff_affiliate;
            $armaff_affiliate_settings = $arm_aff_affiliate->armaff_get_plan_affiliate_settings_content($plan_options, 'armember_action');
            echo $armaff_affiliate_settings;

        }

        function armaff_get_plan_affiliate_settings_content( $plan_options, $armaff_flag = 'armember_action' ) {

                global $arm_payment_gateways, $arm_affiliate;
                $currency = $arm_payment_gateways->arm_get_global_currency();

                $arm_affiliate_referral_disable = (!empty($plan_options["arm_affiliate_referral_disable"])) ? $plan_options["arm_affiliate_referral_disable"] : 0;
                $arm_affiliate_referral_type = (!empty($plan_options["arm_affiliate_referral_type"])) ? $plan_options["arm_affiliate_referral_type"] : 'percentage';
                $arm_affiliate_referral_rate = (!empty($plan_options["arm_affiliate_referral_rate"])) ? $plan_options["arm_affiliate_referral_rate"] : 0;
                $display_percentage = '';
                $display_currency = '';
                if($arm_affiliate_referral_type == 'percentage')
                {
                    $display_currency = 'hidden_section';
                }
                else
                {
                    $display_percentage = 'hidden_section';
                }
                
                $arm_affiliate_recurring_referral_disable = ( isset($plan_options["arm_affiliate_recurring_referral_disable"]) && !empty($plan_options["arm_affiliate_recurring_referral_disable"])) ? $plan_options["arm_affiliate_recurring_referral_disable"] : 0;
                $arm_affiliate_recurring_referral_type = (isset($plan_options["arm_affiliate_recurring_referral_type"]) && !empty($plan_options["arm_affiliate_recurring_referral_type"])) ? $plan_options["arm_affiliate_recurring_referral_type"] : 'percentage';
                $arm_affiliate_recurring_referral_rate = (isset($plan_options["arm_affiliate_recurring_referral_rate"]) && !empty($plan_options["arm_affiliate_recurring_referral_rate"])) ? $plan_options["arm_affiliate_recurring_referral_rate"] : 0;
                $display_recurring_percentage = '';
                $display_recurring_currency = '';
                if($arm_affiliate_recurring_referral_type == 'percentage')
                {
                    $display_recurring_currency = 'hidden_section';
                }
                else
                {
                    $display_recurring_percentage = 'hidden_section';
                }

                $armaff_payment_type = isset($plan_options['payment_type']) ? $plan_options['payment_type'] : '';
                $armaff_recurring_options_cls = ($armaff_payment_type == 'subscription' || $armaff_flag == 'arm_affiliate_setup') ? '' : 'hidden_section';

                $armaff_plan_settings = '';

                if($armaff_flag == 'armember_action'){
                    $armaff_plan_settings .= '<div class="arm_solid_divider"></div>';
                }
                $armaff_plan_settings .= '<div id="arm_plan_price_box_content" class="arm_plan_price_box">';
                    $armaff_plan_settings .= '<div class="page_sub_content">';
                        if($armaff_flag == 'armember_action'){
                            $armaff_plan_settings .= '<div class="page_sub_title">'. __('Affiliate Settings','ARM_AFFILIATE') . '</div>';
                            $arm_page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
                            $armaff_plan_settings .= '<input type="hidden" name="page" id="page" value="'. $arm_page .'" />';
                        }
                        $armaff_plan_settings .= '<table class="form-table">';
                            $armaff_isChecked = checked($arm_affiliate_referral_disable, 1, false);
                            $armaff_opt_cls = ($armaff_isChecked) ? '' : 'hidden_section';

                            if($armaff_flag != 'arm_affiliate_setup'){
                                $armaff_plan_settings .= '<tr class="form-field form-required ">';
                                    $armaff_plan_settings .= '<th><label>'. __('Enable Affiliate Referral' ,'ARM_AFFILIATE') . '</label></th>';
                                    $armaff_plan_settings .= '<td>';
                                        $armaff_plan_settings .= '<div class="armclear"></div>';
                                        $armaff_plan_settings .= '<div class="armswitch arm_global_setting_switch" style="vertical-align: middle;">';
                                            $armaff_plan_settings .= '<input type="checkbox" id="arm_aff_affiliate_disable_referral" '.$armaff_isChecked.' value="1" class="armswitch_input" name="arm_subscription_plan_options[arm_aff_affiliate_disable_referral]"/>';
                                            $armaff_plan_settings .= '<label for="arm_aff_affiliate_disable_referral" class="armswitch_label" style="min-width:40px;"></label>';
                                        $armaff_plan_settings .= '</div>&nbsp;';

                                        $armaff_plan_settings .= '<span style="float:left;width:100%;position:relative;top:5px;left:5px;">'. __('Enable Affiliate Referral if you want to give affiliate commission to users who will signup with this plan.','ARM_AFFILIATE').'</span>';
                                        $armaff_plan_settings .= '<div class="armclear"></div>';
                                    $armaff_plan_settings .= '</td>';
                                $armaff_plan_settings .= '</tr>';
                            }
                            $armaff_plan_settings .= '<tr class="form-field form-required aff_affiliate_sub_opt '.$armaff_opt_cls.'">';
                                $armaff_plan_settings .= '<th><label>' . __('Referral Type' ,'ARM_AFFILIATE') . ' </label></th>';
                                $armaff_plan_settings .= '<td>';
                                    $armaff_plan_settings .= '<div class="arm_affilite_price_type_box">';
                                        $armaff_plan_settings .= '<span class="arm_affilaite_price_types_container" id="arm_affiliate_price_container">';
                                            $armaff_plan_settings .= '<input type="radio" class="arm_iradio arm_aff_referral_type" ' . checked($arm_affiliate_referral_type, 'percentage', false) . ' value="percentage" name="arm_subscription_plan_options[arm_aff_affiliate_price_type]" id="arm_aff_affiliate_price_type_percentage" />';
                                            $armaff_plan_settings .= '<label for="arm_aff_affiliate_price_type_percentage">' . __('Percentage', 'ARM_AFFILIATE') . '</label>';
                                            $armaff_plan_settings .= '<input type="radio" class="arm_iradio arm_aff_referral_type" ' . checked($arm_affiliate_referral_type, 'fixed_rate', false) . ' value="fixed_rate" name="arm_subscription_plan_options[arm_aff_affiliate_price_type]" id="arm_aff_affiliate_price_type_fixed_rate" />';
                                            $armaff_plan_settings .= '<label for="arm_aff_affiliate_price_type_fixed_rate">' . __('Fixed Rate', 'ARM_AFFILIATE') . '</label>';
                                        $armaff_plan_settings .= '</span>';
                                        $armaff_plan_settings .= '<div class="armclear"></div>';
                                    $armaff_plan_settings .= '</div>';
                                $armaff_plan_settings .= '</td>';
                            $armaff_plan_settings .= '</tr>';
                            $armaff_plan_settings .= '<tr class="form-field form-required aff_affiliate_sub_opt '.$armaff_opt_cls.'">';
                                $armaff_plan_settings .= '<th><label>' . __('Referral Rate' ,'ARM_AFFILIATE') . '</label></th>';
                                $armaff_plan_settings .= '<td>';
                                    $armaff_plan_settings .= '<div class="arm_affilite_rate_box">';
                                        $armaff_plan_settings .= '<input name="arm_subscription_plan_options[arm_aff_affilaite_rate]" id="arm_aff_affilaite_rate" type="text" size="50" class="arm_aff_affilaite_rate" title="Referral Rate" value="'.$arm_affiliate_referral_rate.'" onkeypress="return armaff_isNumber(event)" />';
                                        $armaff_plan_settings .= '<span class="arm_aff_price_type_percentage '.$display_percentage.'"> '. __('%', 'ARM_AFFILIATE') . '</span>';
                                        $armaff_plan_settings .= '<span class="arm_aff_price_type_currency '.$display_currency.'"> '.$currency.'</span>';
                                        $armaff_plan_settings .= '<div class="armclear"></div>';
                                    $armaff_plan_settings .= '</div>';
                                $armaff_plan_settings .= '</td>';
                            $armaff_plan_settings .= '</tr>';
                            if (version_compare($arm_affiliate->get_armember_version(), '2.2.1', '>=')) {
                                $armaff_plan_settings .= '<tr class="form-field form-required paid_subscription_options_recurring '.$armaff_recurring_options_cls.'">
                                        <td colspan="2">
                                            <div class="page_sub_title" style="padding-left: 0px;">'. __('Affiliate Settings For Each Recurring Payment','ARM_AFFILIATE') . '</div>
                                        </td>
                                    </tr>';
                                $armaff_subscription_isChecked = checked($arm_affiliate_recurring_referral_disable, 1, false);
                                $armaff_subscription_opt_cls = ($armaff_subscription_isChecked) ? '' : 'hidden_section';

                                $armaff_plan_settings .= '<tr class="form-field form-required paid_subscription_options_recurring '. $armaff_recurring_options_cls.'">
                                                                    <th><label>'. __('Enable Referral On Each Recurring Payment' ,'ARM_AFFILIATE').'</label></th>
                                                                    <td>
                                                                        <div class="armclear"></div>
                                                                        <div class="armswitch arm_global_setting_switch" style="vertical-align: middle;">
                                                                            <input type="checkbox" id="arm_aff_recurring_affiliate_disable_referral_'.$armaff_flag.'" '.$armaff_subscription_isChecked.' value="1" class="armswitch_input arm_aff_recurring_affiliate_disable_referral" name="arm_subscription_plan_options[arm_aff_recurring_affiliate_disable_referral]"/>
                                                                            <label for="arm_aff_recurring_affiliate_disable_referral_'.$armaff_flag.'" class="armswitch_label" style="min-width:40px;"></label>
                                                                        </div>
                                                                        &nbsp;
                                                                        <span style="float:left;width:100%;position:relative;top:5px;left:5px;">'. __('Enable this setting if you want to give affiliate commission to users on each payment cycle of subscription.','ARM_AFFILIATE').'</span>
                                                                        <div class="armclear"></div>
                                                                    </td>
                                                                </tr>';
                                $armaff_plan_settings .= '<tr class="form-field form-required paid_subscription_options_recurring aff_subscription_affiliate_sub_opt '. $armaff_recurring_options_cls.' '.$armaff_subscription_opt_cls.'">
                                                                    <th><label>'. __('Referral Type' ,'ARM_AFFILIATE').'</label></th>
                                                                    <td>
                                                                        <div class="arm_affilite_price_type_box">
                                                                            <span class="arm_affilaite_price_types_container" id="arm_affiliate_recurring_referral_type_container">
                                                                                <input type="radio" class="arm_iradio arm_aff_recurring_referral_type" '. checked($arm_affiliate_recurring_referral_type, 'percentage', false).' value="percentage" name="arm_subscription_plan_options[arm_aff_recurring_referral_type]" id="arm_aff_recurring_referral_type_percentage" />
                                                                                <label for="arm_aff_recurring_referral_type_percentage">'. __('Percentage', 'ARM_AFFILIATE').'</label>
                                                                                <input type="radio" class="arm_iradio arm_aff_recurring_referral_type" '. checked($arm_affiliate_recurring_referral_type, 'fixed_rate', false).' value="fixed_rate" name="arm_subscription_plan_options[arm_aff_recurring_referral_type]" id="arm_aff_recurring_referral_type_fixed_rate" />
                                                                                <label for="arm_aff_recurring_referral_type_fixed_rate">'. __('Fixed Rate', 'ARM_AFFILIATE').'</label>
                                                                            </span>
                                                                            <div class="armclear"></div>
                                                                        </div>
                                                                    </td>
                                                                </tr>';
                                $armaff_plan_settings .= '<tr class="form-field form-required paid_subscription_options_recurring aff_subscription_affiliate_sub_opt '. $armaff_recurring_options_cls.' '.$armaff_subscription_opt_cls.'">
                                                                    <th><label>'. __('Referral Rate' ,'ARM_AFFILIATE').'</label></th>
                                                                    <td>
                                                                        <div class="arm_affilite_rate_box">
                                                                            <input name="arm_subscription_plan_options[arm_aff_recurring_affilaite_rate]" id="arm_aff_recurring_affilaite_rate" type="text" size="50" class="arm_aff_recurring_affilaite_rate" title="Referral Rate" value="'. $arm_affiliate_recurring_referral_rate.'" onkeypress="return armaff_isNumber(event)" />
                                                                            <span class="arm_aff_recurring_affiliate_rate_percentage '. $display_recurring_percentage.'"> '. __('%', 'ARM_AFFILIATE').' </span>
                                                                            <span class="arm_aff_recurring_affiliate_rate_currency '. $display_recurring_currency.'"> '. $currency.' </span>
                                                                            <div class="armclear"></div>
                                                                        </div>
                                                                    </td>
                                                                </tr>';
                            }
                        $armaff_plan_settings .= '</table>';
                    $armaff_plan_settings .= '</div>';
                $armaff_plan_settings .= '</div>';

                return $armaff_plan_settings;

        }

        function before_save_field_membership_plan($plan_options, $posted_data){

            $plan_options['arm_affiliate_referral_disable'] = isset($posted_data['arm_subscription_plan_options']['arm_aff_affiliate_disable_referral']) ? $posted_data['arm_subscription_plan_options']['arm_aff_affiliate_disable_referral'] : 0;
            $plan_options['arm_affiliate_referral_type'] = isset($posted_data['arm_subscription_plan_options']['arm_aff_affiliate_price_type']) ? $posted_data['arm_subscription_plan_options']['arm_aff_affiliate_price_type'] : 'percentage';
            $plan_options['arm_affiliate_referral_rate'] = isset($posted_data['arm_subscription_plan_options']['arm_aff_affilaite_rate']) ? $posted_data['arm_subscription_plan_options']['arm_aff_affilaite_rate'] : 0;

            $plan_options['arm_affiliate_recurring_referral_disable'] = isset($posted_data['arm_subscription_plan_options']['arm_aff_recurring_affiliate_disable_referral']) ? $posted_data['arm_subscription_plan_options']['arm_aff_recurring_affiliate_disable_referral'] : 0;
            $plan_options['arm_affiliate_recurring_referral_type'] = isset($posted_data['arm_subscription_plan_options']['arm_aff_recurring_referral_type']) ? $posted_data['arm_subscription_plan_options']['arm_aff_recurring_referral_type'] : 'percentage';
            $plan_options['arm_affiliate_recurring_referral_rate'] = isset($posted_data['arm_subscription_plan_options']['arm_aff_recurring_affilaite_rate']) ? $posted_data['arm_subscription_plan_options']['arm_aff_recurring_affilaite_rate'] : 0;

            return $plan_options;
        }
        
        function arm_affiliate_register_account() {

            global $arm_affiliate, $wpdb, $arm_aff_layout,$arm_manage_communication,$ARMember;

            $armaff_affiliate_custom_fields = $arm_aff_layout->armaff_get_affiliate_form_fields();

            $response = array( 'type' => 'error', 'msg'=> __( 'There is an error while creating affiliate, please try again.', 'ARM_AFFILIATE' ) );

            $armaff_fname = (isset($_POST['affiliate_fname'])) ? $_POST['affiliate_fname'] : '';
            $armaff_lname = (isset($_POST['affiliate_lname'])) ? $_POST['affiliate_lname'] : '';
            $armaff_uname = (isset($_POST['affiliate_uname'])) ? $_POST['affiliate_uname'] : '';
            $armaff_email = (isset($_POST['affiliate_email'])) ? $_POST['affiliate_email'] : '';
            $armaff_pwd = (isset($_POST['affiliate_pwd'])) ? $_POST['affiliate_pwd'] : '';
            $armaff_website = (isset($_POST['affiliate_website'])) ? $_POST['affiliate_website'] : '';

            if($armaff_uname == '' || $armaff_email == ''){
                $response = array( 'type' => 'error', 'msg'=> __( 'Please enter required data properly.', 'ARM_AFFILIATE' ) );
                echo json_encode($response); exit;
            }

            if ( !is_user_logged_in() ) {
                $args = array(
                    'user_login'    => sanitize_text_field( $armaff_uname ),
                    'user_email'    => sanitize_text_field( $armaff_email ),
                    'user_pass'     => sanitize_text_field( $armaff_pwd ),
                    'display_name'  => $armaff_fname . ' ' . $armaff_lname
                );

                $user_id = wp_insert_user( $args );
            } else {
                $user_id = get_current_user_id();
                $user    = (array) get_userdata( $user_id );
                $args    = (array) $user['data'];

                $check_affiliate_exists = $wpdb->get_row('SELECT arm_affiliate_id FROM '.$arm_affiliate->tbl_arm_aff_affiliates.' WHERE arm_user_id = '.$user_id, ARRAY_A);
                if($wpdb->num_rows > 0)
                {
                    $response = array( 'type' => 'error', 'msg'=> __( 'This user has already created affiliate account.', 'ARM_AFFILIATE' ) );
                    echo json_encode($response); exit;
                }

            }

            /*wp_update_user( array( 'ID' => $user_id, 'first_name' => $armaff_fname, 'last_name' => $armaff_lname ) );*/

            if ( $armaff_website ) {
                wp_update_user( array( 'ID' => $user_id, 'user_url' => $armaff_website ) );
            }

            $arm_add_affiliate = array(
                'arm_user_id' => $user_id,
                'arm_status' => '1',
            );
            foreach ($armaff_affiliate_custom_fields as $key => $value) {
                $armaff_field_value = (isset($_POST[$value])) ? $_POST[$value] : '';
                $arm_add_affiliate[$value] = $armaff_field_value;
            }

            $arm_add_affiliate['arm_start_date_time'] = current_time('mysql');
            $arm_add_affiliate['arm_end_date_time'] = '';


            $arm_add_affiliate_response = $wpdb->insert($arm_affiliate->tbl_arm_aff_affiliates, $arm_add_affiliate);

            $plan_id='0';
            $user_plans = get_user_meta($user_id, 'arm_user_plan_ids', true);
            if(!empty($user_plans)){
                $plan_id=$user_plans[0];
            }
            
            if($arm_add_affiliate_response==1){
                $arm_manage_communication->membership_communication_mail('arm_notify_on_register_affiliate_account',$user_id,$plan_id);
            }
                       
            $response = array( 'type' => 'success', 'msg'=> __( 'Affiliate account is created successfully.', 'ARM_AFFILIATE' ) );
            echo json_encode($response); exit;
        }

        function arm_get_all_active_affiliates() {

            global $wpdb, $arm_affiliate;

            $get_armaffiliates = $wpdb->get_results("SELECT * FROM {$arm_affiliate->tbl_arm_aff_affiliates} WHERE `arm_status` = 1", ARRAY_A);

            if(!empty($get_armaffiliates))
            {
                return $get_armaffiliates;
            }

            return false;

        }
        function arm_get_all_active_affiliates_with_userinfo() {

            global $wpdb, $arm_affiliate;

            $user_table = $wpdb->users;
            $get_armaffiliates = $wpdb->get_results("SELECT a.*,u.user_login,u.user_email FROM {$arm_affiliate->tbl_arm_aff_affiliates} a LEFT JOIN {$user_table} u ON u.ID = a.arm_user_id WHERE a.arm_status = '1' and a.arm_user_id!='0' and a.arm_user_id!='' ", ARRAY_A);

            if(!empty($get_armaffiliates))
            {
                return $get_armaffiliates;
            }

            return false;

        }

    }
}

global $arm_aff_affiliate;
$arm_aff_affiliate = new arm_aff_affiliate();
?>