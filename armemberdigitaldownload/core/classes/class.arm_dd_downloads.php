<?php
if(!class_exists('arm_dd_downloads')){
    
    class arm_dd_downloads{
        
        function __construct(){

            add_action( 'wp_ajax_arm_dd_download_list', array( $this, 'arm_dd_download_list' ) );
            
            add_action( 'wp_ajax_arm_dd_download_ajax_action', array( $this, 'arm_dd_download_ajax_action' ) );
            
            add_action( 'wp_ajax_arm_dd_download_bulk_action', array( $this, 'arm_dd_download_bulk_action' ) );
            
            add_action( 'arm_dd_handle_export', array( $this, 'arm_dd_download_export_csv' ) );
            
            add_action( 'delete_user', array( $this, 'arm_dd_download_delete_user' ), 10, 1 );
            
            add_action( 'arm_email_notification_template_shortcode', array( $this, 'arm_dd_download_email_notification_shortcode' ) );
        }
        
        function arm_dd_download_date_convert_db_formate($date) {
            global $arm_global_settings;
            if( $arm_global_settings->arm_get_wp_date_format() == 'd/m/Y')
            {
                $date = isset($date) ? str_replace('/', '-', $date) : '';
            }
            
            //$date = str_replace('/', '-', $date);
            return  date('Y-m-d', strtotime($date));
        }
        
        function arm_dd_download_list() {
            global $wpdb, $ARMember, $arm_global_settings, $arm_payment_gateways, $arm_dd;
            
            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_dd_capabilities = $arm_dd->arm_dd_page_slug();
                $ARMember->arm_check_user_cap($arm_dd_capabilities['2'],'1');
            }
            $date_format = $arm_global_settings->arm_get_wp_date_format()." ".get_option('time_format');
            $nowDate = current_time('mysql');
            
            $grid_columns = array(
                'id' => __('ID', 'ARM_DD'),
                'item_name' => __('Item Name', 'ARM_DD'),
                'username' => __('Username', 'ARM_DD'),
                'ip_address' => __('IP Address', 'ARM_DD'),
                'browser' => __('Browser', 'ARM_DD'),
                'country' => __('Country', 'ARM_DD'),
                'datetime' => __('Date Time', 'ARM_DD')
            );

            $arm_pro_tmp_query = "SELECT arm_dd_id FROM {$arm_dd->tbl_arm_dd_downloads}";
            $form_result = $wpdb->get_results($arm_pro_tmp_query);
            $total_before_filter = count($form_result);
            
            $where_condition = '';
            
            $sSearch = isset($_REQUEST['sSearch']) ? $_REQUEST['sSearch'] : '';
            if($sSearch != '')
            { $where_condition.= " AND u.user_login LIKE '%{$sSearch}%' "; }
            
            $filter_download_id = isset($_REQUEST['filter_download_id']) ? $_REQUEST['filter_download_id'] : '';
            if($filter_download_id != '')
            { $where_condition.= " AND i.arm_item_id IN (".$filter_download_id.")"; }
            
            $start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : '';
            $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : '';
            if (!empty($start_date)) {                
                $start_datetime = $this->arm_dd_download_date_convert_db_formate($start_date)." 00:00:00";
                if (!empty($end_date)) {
                    $end_datetime = $this->arm_dd_download_date_convert_db_formate($end_date)." 23:59:59";
                    if ($start_datetime > $end_datetime) {
                        $end_datetime = $this->arm_dd_download_date_convert_db_formate($start_date)." 00:00:00";
                        $start_datetime = $this->arm_dd_download_date_convert_db_formate($end_date)." 23:59:59";
                    }
                    $where_condition .= " AND (d.arm_dd_datetime BETWEEN '$start_datetime' AND '$end_datetime') ";
                } else {
                    $where_condition .= " AND (d.arm_dd_datetime > '$start_datetime') ";
                }
            } else {
                if (!empty($end_date)) {
                    $end_datetime = $this->arm_dd_download_date_convert_db_formate($end_date);  
                    $where_condition .= " AND (d.arm_dd_datetime < '$end_datetime') ";
                }
            }
            
            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 5;
            $order_by = 'd.arm_dd_id';
            if( $sorting_col == 2 ) {
                $order_by = 'i.arm_item_name';
            }
            if( $sorting_col == 3 ) {
                $order_by = 'u.user_login';
            }
            if( $sorting_col == 6 ){
                $order_by = 'd.arm_dd_country';
            }
            if($sorting_col == 7){
                $order_by = 'd.arm_dd_datetime';
            }
            
            $arm_pro_tmp_query = "SELECT d.*, i.arm_item_name AS arm_item_name, u.user_login AS arm_user_login "
                        . " FROM `{$arm_dd->tbl_arm_dd_downloads}` AS d "
                        . " LEFT JOIN `{$arm_dd->tbl_arm_dd_items}` AS i ON d.arm_dd_item_id = i.arm_item_id"
                        . " LEFT JOIN `{$wpdb->users}` AS u ON d.arm_dd_user_id = u.ID"
                        . " WHERE 1=1 "
                        . $where_condition
                        . " ORDER BY {$order_by} {$sorting_ord}";
            $form_result = $wpdb->get_results($arm_pro_tmp_query);
            $total_after_filter = count($form_result);
            
            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            $arm_pro_query = $arm_pro_tmp_query . " LIMIT {$offset},{$number}";
            $form_result = $wpdb->get_results($arm_pro_query);
            
            $grid_data = array();
            $ai = 0;
            foreach ($form_result as $downloads) {
                $arm_dd_id = $downloads->arm_dd_id;
                $arm_item_name = $downloads->arm_item_name;
                $arm_user_login = ($downloads->arm_user_login != '') ? $downloads->arm_user_login : $arm_default_user_details_text;
                $arm_dd_ip_address = !empty($downloads->arm_dd_ip_address) ? $downloads->arm_dd_ip_address : $arm_default_user_details_text;
                $arm_dd_browser = !empty($downloads->arm_dd_browser) ? $downloads->arm_dd_browser : $arm_default_user_details_text;
                $arm_dd_country = !empty($downloads->arm_dd_country) ? $downloads->arm_dd_country : $arm_default_user_details_text;
                $arm_dd_datetime = date($date_format, strtotime($downloads->arm_dd_datetime));
                
                $gridAction = "<div class='arm_grid_action_btn_container'>";
                $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$arm_dd_id});'><img src='" . ARM_DD_IMAGES_URL . "grid_delete.png' class='armhelptip' title='" . __('Delete', 'ARM_DD') . "' onmouseover=\"this.src='" . ARM_DD_IMAGES_URL . "grid_delete_hover.png';\" onmouseout=\"this.src='" . ARM_DD_IMAGES_URL . "grid_delete.png';\" /></a>";
                $gridAction .= $arm_global_settings->arm_get_confirm_box($arm_dd_id, __("Are you sure you want to delete this download hitory?", 'ARM_DD'), 'arm_dd_download_delete_btn');
                $gridAction .= "</div>";
                
                $grid_data[$ai][0] = "<input id=\"cb-item-action-{$arm_dd_id}\" class=\"chkstanard\" type=\"checkbox\" value=\"{$arm_dd_id}\" name=\"item-action[]\">";
                $grid_data[$ai][1] = $arm_dd_id;
                $grid_data[$ai][2] = $arm_item_name;
                $grid_data[$ai][3] = $arm_user_login;
                $grid_data[$ai][4] = $arm_dd_ip_address;
                $grid_data[$ai][5] = $arm_dd_browser;
                $grid_data[$ai][6] = $arm_dd_country;
                $grid_data[$ai][7] = $arm_dd_datetime;    
                $grid_data[$ai][8] = $gridAction;
             
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
        
        function arm_dd_download_ajax_action() {
            global $wpdb, $arm_dd, $ARMember;
            
            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_dd_capabilities = $arm_dd->arm_dd_page_slug();
                $ARMember->arm_check_user_cap($arm_dd_capabilities['2'],'1');
            }
            $action_data = $_POST;
            if( isset( $action_data['act'] ) && $action_data['act'] ){
                if( isset( $action_data['id'] ) && $action_data['id'] != '' && $action_data['act'] == 'delete' )
                {
                    if (!current_user_can('arm_dd_download')) {
                        $response = array( 'type' => 'error', 'msg'=> __( 'Sorry, You do not have permission to perform this action.', 'ARM_DD' ) );
                    } else {
                        $delete_item = $wpdb->query( $wpdb->prepare( "DELETE FROM `$arm_dd->tbl_arm_dd_downloads` WHERE arm_dd_id = %d", $action_data['id'] ) );
                        $response = array( 'type' => 'success', 'msg' => __( 'Download history deleted successfully.', 'ARM_DD' ) );                    
                    }
                }
            }
            else
            {
                 $response = array( 'type' => 'error', 'msg'=> __( 'Sorry, Action not found.', 'ARM_DD' ) );
            }
            echo json_encode($response);
            die;
        }
        
        function arm_dd_download_bulk_action() {
            global $wpdb, $ARMember, $arm_global_settings, $arm_dd;
            
            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_dd_capabilities = $arm_dd->arm_dd_page_slug();
                $ARMember->arm_check_user_cap($arm_dd_capabilities['2'],'1');
            }
            if (!isset($_POST)) {
                    return;
            }
            $bulkaction = $arm_global_settings->get_param('action1');
            $ids = $arm_global_settings->get_param('item-action', '');
                        
            if (empty($ids)) {
                $errors[] = __('Please select one or more records.', 'ARM_DD');
            } else {
                if ($bulkaction == '' || $bulkaction == '-1') {
                        $errors[] = __('Please select valid action.', 'ARM_DD');
                } else 
                {
                    if (!is_array($ids)) {
                        $ids = explode(',', $ids);
                    }
                    
                    if (!current_user_can('arm_dd_item')) {
                        $errors[] = __('Sorry, You do not have permission to perform this action', 'ARM_DD');
                    } else {
                        if (is_array($ids)) {
                            $download_ids = implode(',',$ids);
                            $delete_item = $wpdb->query( "DELETE FROM `$arm_dd->tbl_arm_dd_downloads` WHERE arm_dd_id IN (".$download_ids.")");
                            $message = __('Download history has been deleted successfully.', 'ARM_DD');
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
        
        function arm_dd_download_add( $item_data ) {
            global $wpdb, $ARMember, $arm_dd, $arm_dd_items, $arm_global_settings, $arm_email_settings;
            if( !is_array($item_data) && $item_data > 0 ) :
                $item_data = $arm_dd_items->arm_dd_item_data($args['item_id']);
            endif;
            $return = array( 'status' => 'failed', 'message' => __("Sorry, Something went wrong. Please try again.", 'ARM_DD') );
            if(isset($item_data['arm_item_id']) && $item_data['arm_item_id'] > 0)
            {
                $arm_dd_settings = $arm_dd->arm_dd_get_settings();

            
                $date_format = $arm_global_settings->arm_get_wp_date_format();
                
                /******* check prevent hotlinking ********/
                if( isset( $arm_dd_settings['prevent_hotlinking'] ) && $arm_dd_settings['prevent_hotlinking'] == '1' ) {
                    $home_url = home_url();
                    $referer_url = wp_get_referer(); 
                    if( substr( $referer_url, 0, strlen( $home_url ) ) != $home_url ) {
                        return array( 'status' => 'failed', 'message' => __("Sorry, Something went wrong. Please try again.", 'ARM_DD') );
                    }
                }

                /******* check Download File Limit *******/
                $item_id = $item_data['arm_item_id'];
                $item_name = $item_data['arm_item_name'];
                $arm_user_id = 0;
                if( is_user_logged_in() ) {
                    $arm_user_id = get_current_user_id();
                    if( $item_data['arm_item_permission_type'] == 'plan' ) {
                        /******* check download limit ******/
                        $days_limit = 0;
                        $file_limit = 0;
                        $user_plan_id = get_user_meta($arm_user_id, 'arm_user_plan_ids', true);
                        $user_plan_id = !empty($user_plan_id) ? $user_plan_id : array(-2);
                        foreach($user_plan_id as $plan_id)
                        {
                            $limit_days_option = isset($arm_dd_settings['limit_day'][$plan_id]) ? $arm_dd_settings['limit_day'][$plan_id] : 0 ;
                            $limit_file = isset($arm_dd_settings['limit_file'][$plan_id]) ? $arm_dd_settings['limit_file'][$plan_id] : 0 ;
                            $limit_days = 0;
                            if ($limit_days_option == 'd') { $limit_days = 1; } 
                            else if ($limit_days_option == 'w') { $limit_days = 7; } 
                            else if ($limit_days_option == 'm') { $limit_days = 30; } 
                            else if ($limit_days_option == 'y') { $limit_days = 365; } 

                            $days_limit = $days_limit + $limit_days;
                            $file_limit = $file_limit + $limit_file;
                        }

                        
                        if( $days_limit > 0 ) {
                            
                            $start_date = date('Y-m-d', strtotime('-' . $days_limit . ' days'))." 00:00:00";
                            $end_date = date('Y-m-d')." 23:59:59";

                            $limit_query = "SELECT arm_dd_item_id FROM {$arm_dd->tbl_arm_dd_downloads} WHERE arm_dd_user_id = %d AND arm_dd_datetime BETWEEN %s AND %s group by arm_dd_item_id ";
                            $limit_result = $wpdb->get_results( $wpdb->prepare( $limit_query, $arm_user_id, $start_date, $end_date ) );
                            $limit_count = count($limit_result);

                            $allready_download_file = false;
                            if( $limit_count > 0 ) {
                                foreach( $limit_result as $arm_item_id){
                                    if( !$allready_download_file && $arm_item_id->arm_dd_item_id == $item_id) {
                                        $allready_download_file = true;
                                    }
                                }
                            }

                            if(!$allready_download_file && $limit_count >= $file_limit)
                            {
                                $arm_limit_options = $this->arm_dd_download_limit_options();
                                $arm_limit_days = isset($arm_limit_options[$limit_days_option]) ? $arm_limit_options[$limit_days_option] : '0';
                                $message = __("Sorry, You can not download more than", 'ARM_DD') . " " . $file_limit . " ";
                                $message .= __("file(s) in a", 'ARM_DD') . " " . $arm_limit_days . ".";
                                return array( 'status' => 'failed', 'message' => $message );
                            }
                        } 
                    }
                }
                $arm_dd_ip_address = $ARMember->arm_get_ip_address();
                $arm_dd_country = $ARMember->arm_get_country_from_ip($arm_dd_ip_address);
                $browser_info = $ARMember->getBrowser($_SERVER['HTTP_USER_AGENT']);
                $arm_dd_browser = $browser_info['name'] . ' (' . $browser_info['version'] . ')';
                $arm_dd_datetime = current_time('mysql');
                
                /******* check count only uniqe ip ********/
                $arm_can_add_download_history = true;
                if( isset( $arm_dd_settings['count_uniqe_ip'] ) && $arm_dd_settings['count_uniqe_ip'] == '1' ) {
                    $count_q = "SELECT arm_dd_id FROM {$arm_dd->tbl_arm_dd_downloads} WHERE arm_dd_ip_address = %s AND arm_dd_item_id = %d AND arm_dd_user_id = %d ";
                    $count_download_result = $wpdb->get_results( $wpdb->prepare($count_q, $arm_dd_ip_address, $item_id, $arm_user_id) );
                    $total_downloads = count( $count_download_result );
                    if( $total_downloads >= 1 ) {
                        $arm_can_add_download_history = false;
                    }
                }
                
                if( $arm_can_add_download_history ) {
                    $download_data = array(
                        'arm_dd_item_id' => $item_id,
                        'arm_dd_user_id' => $arm_user_id,
                        'arm_dd_ip_address' => $arm_dd_ip_address,
                        'arm_dd_browser' => $arm_dd_browser,
                        'arm_dd_country' => $arm_dd_country,
                        'arm_dd_datetime' => $arm_dd_datetime
                    );
                    $new_download_results = $wpdb->insert($arm_dd->tbl_arm_dd_downloads, $download_data);
                    $wpdb->query( "UPDATE `$arm_dd->tbl_arm_dd_items` SET arm_item_download_count = arm_item_download_count + 1 WHERE arm_item_id = '" . $item_id ."' ");

                    $arm_download_id = $wpdb->insert_id;
                    
                    // send mail.
                    if( isset( $arm_dd_settings['admin_email'] ) && $arm_dd_settings['admin_email'] == '1' ) {
                        $admin_template = $arm_email_settings->arm_get_email_template('user-download-file-admin');
                        if ($admin_template->arm_template_status == '1') {
                            $blog_name = get_bloginfo('name');
                            $blog_url = ARM_HOME_URL;
                            $subject_admin = $arm_global_settings->arm_filter_email_with_user_detail($admin_template->arm_template_subject, $arm_user_id, 0, 0);
                            $subject_admin = str_replace('{ARM_BLOGNAME}', $blog_name, $subject_admin);

                            $message_admin = $arm_global_settings->arm_filter_email_with_user_detail($admin_template->arm_template_content, $arm_user_id, 0, 0);
                            $message_admin = str_replace('{ARM_BLOGNAME}', $blog_name, $message_admin);
                            $message_admin = str_replace('{ARM_BLOG_URL}', $blog_url, $message_admin);
                            $message_admin = str_replace('{ARM_DOWNLOAD_FILE}', $item_name, $message_admin);
                            $message_admin = str_replace('{ARM_DOWNLOAD_IP}', $arm_dd_ip_address, $message_admin);
                            $message_admin = str_replace('{ARM_DOWNLOAD_BROWSER}', $arm_dd_browser, $message_admin);
                            $message_admin = str_replace('{ARM_DOWNLOAD_DATETIME}', $arm_dd_datetime, $message_admin);
                            
                            $pattern = '/(\{ARM\_)(.*?)(\})(.*?)/';
                            preg_match_all($pattern,$message_admin,$arm_tags);
                            $arm_user_not_login = __('User Not Logged in', 'ARM_DD');
                            foreach ($arm_tags[0] as $arm_tag) {
                                if( in_array( $arm_tag, array( '{ARM_USERNAME}', '{ARM_FIRST_NAME}', '{ARM_LAST_NAME}', '{ARM_NAME}', '{ARM_EMAIL}', '{ARM_USERMETA_meta_key}' ) ) )
                                {
                                    $message_admin = str_replace($arm_tag, $arm_user_not_login, $message_admin);
                                }
                                else
                                {
                                    $message_admin = str_replace($arm_tag, '-', $message_admin);
                                }
                            }

                            $admin_send_mail = $arm_global_settings->arm_send_message_to_armember_admin_users($arm_user_id, $subject_admin, $message_admin);
                        }
                    }
                }
                return array( 'status' => 'success', 'message' => '' );
            }
            else
            {
                return $return;
            }
        }
        
        function arm_dd_download_export_csv( $request ) {
            global $arm_members_class, $wpdb, $arm_dd, $arm_global_settings, $arm_default_user_details_text;
            
            if( isset($request['arm_action']) && $request['arm_action'] == 'downloads_history_export_csv' ) {
                
                $date_format = $arm_global_settings->arm_get_wp_date_format()." ".get_option('time_format');
                $nowDate = current_time('mysql');

                $where_condition = '';
                $sSearch = isset($_REQUEST['sSearch']) ? $_REQUEST['sSearch'] : '';
                if($sSearch != '')
                { $where_condition.= " AND u.user_login LIKE '%{$sSearch}%' "; }

                $filter_download_id = isset($_REQUEST['filter_download_id']) ? $_REQUEST['filter_download_id'] : '';
                if($filter_download_id != '')
                { $where_condition.= " AND i.arm_item_id IN (".$filter_download_id.")"; }

                $start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : '';
                $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : '';
                if (!empty($start_date)) {                
                    $start_datetime = $this->arm_dd_download_date_convert_db_formate($start_date)." 00:00:00";
                    if (!empty($end_date)) {
                        $end_datetime = $this->arm_dd_download_date_convert_db_formate($end_date)." 23:59:59";
                        if ($start_datetime > $end_datetime) {
                            $end_datetime = $this->arm_dd_download_date_convert_db_formate($start_date)." 00:00:00";
                            $start_datetime = $this->arm_dd_download_date_convert_db_formate($end_date)." 23:59:59";
                        }
                        $where_condition .= " AND (d.arm_dd_datetime BETWEEN '$start_datetime' AND '$end_datetime') ";
                    } else {
                        $where_condition .= " AND (d.arm_dd_datetime > '$start_datetime') ";
                    }
                } else {
                    if (!empty($end_date)) {
                        $end_datetime = $this->arm_dd_download_date_convert_db_formate($end_date);  
                        $where_condition .= " AND (d.arm_dd_datetime < '$end_datetime') ";
                    }
                }
                
                $sorting_ord = 'desc';
                $order_by = 'd.arm_dd_datetime';

                $arm_pro_tmp_query = "SELECT d.*, i.arm_item_name AS arm_item_name, u.user_login AS arm_user_login "
                            . " FROM `{$arm_dd->tbl_arm_dd_downloads}` AS d "
                            . " LEFT JOIN `{$arm_dd->tbl_arm_dd_items}` AS i ON d.arm_dd_item_id = i.arm_item_id"
                            . " LEFT JOIN `{$wpdb->users}` AS u ON d.arm_dd_user_id = u.ID"
                            . " WHERE 1=1 "
                            . $where_condition
                            . " ORDER BY {$order_by} {$sorting_ord}";
                $form_result = $wpdb->get_results($arm_pro_tmp_query);

                
                
                if (!empty($form_result))
                {
                    
                    $download_history_data = array();
                    $ai = 0;
                    foreach ($form_result as $downloads) {
                        $arm_dd_id = $downloads->arm_dd_id;
                        $arm_item_name = $downloads->arm_item_name;
                        $arm_user_login = ($downloads->arm_user_login != '') ? $downloads->arm_user_login : $arm_default_user_details_text ;
                        $arm_dd_ip_address = !empty($downloads->arm_dd_ip_address) ? $downloads->arm_dd_ip_address : $arm_default_user_details_text;
                        $arm_dd_browser = !empty($downloads->arm_dd_browser) ? $downloads->arm_dd_browser : $arm_default_user_details_text;
                        $arm_dd_datetime = date($date_format, strtotime($downloads->arm_dd_datetime));

                        $download_history_data[] = array(
                            'arm_item_name' => $arm_item_name,
                            'arm_user_login' => $arm_user_login,
                            'arm_dd_ip_address' => $arm_dd_ip_address,
                            'arm_dd_browser' => $arm_dd_browser,
                            'arm_dd_datetime' => $arm_dd_datetime
                        );
                        $ai++;
                    }
                    $arm_members_class->arm_export_to_csv($download_history_data, 'ARMember-export-download-history.csv', $delimiter=',');
                }
            }
        }
        
        function arm_dd_download_delete_user( $user_id ) {
            global $wpdb, $arm_dd;
            //$wpdb->query( "DELETE FROM `$arm_dd->tbl_arm_dd_downloads` WHERE arm_dd_user_id = " . $user_id ." ");
            $wpdb->query( "UPDATE `$arm_dd->tbl_arm_dd_downloads` SET arm_dd_user_id='0', arm_dd_ip_address='', arm_dd_browser='', arm_dd_country='' WHERE arm_dd_user_id = '" . $user_id ."' ");
        }
        
        function arm_dd_download_email_notification_shortcode() {
            ?>
            <div class="arm_shortcode_row arm_email_code_transaction_id arm_email_notification_dd_shortcode" style='display:none;'>
                    <span class="arm_variable_code arm_standard_email_code" data-code="{ARM_DOWNLOAD_FILE}" title="<?php _e("Click to add shortcode in textarea", 'ARM_DD'); ?>"><?php _e('Download Item', 'ARM_DD'); ?></span><i class="arm_email_helptip_icon armfa armfa-question-circle" title="<?php _e("Displays the name of downloaded file by user", 'ARM_DD'); ?>"></i>
            </div>
            <div class="arm_shortcode_row arm_email_code_transaction_id arm_email_notification_dd_shortcode" style='display:none;'>
                    <span class="arm_variable_code arm_standard_email_code" data-code="{ARM_DOWNLOAD_IP}" title="<?php _e("Click to add shortcode in textarea", 'ARM_DD'); ?>"><?php _e('Download IP Address', 'ARM_DD'); ?></span><i class="arm_email_helptip_icon armfa armfa-question-circle" title="<?php _e("Displays the IP Address of downloaded file by user", 'ARM_DD'); ?>"></i>
            </div> 
            <div class="arm_shortcode_row arm_email_code_transaction_id arm_email_notification_dd_shortcode" style='display:none;'>
                    <span class="arm_variable_code arm_standard_email_code" data-code="{ARM_DOWNLOAD_BROWSER}" title="<?php _e("Click to add shortcode in textarea", 'ARM_DD'); ?>"><?php _e('Download Browser', 'ARM_DD'); ?></span><i class="arm_email_helptip_icon armfa armfa-question-circle" title="<?php _e("Displays the browser of downloaded file by user", 'ARM_DD'); ?>"></i>
            </div> 
            <div class="arm_shortcode_row arm_email_code_transaction_id arm_email_notification_dd_shortcode" style='display:none;'>
                    <span class="arm_variable_code arm_standard_email_code" data-code="{ARM_DOWNLOAD_DATETIME}" title="<?php _e("Click to add shortcode in textarea", 'ARM_DD'); ?>"><?php _e('Download Date Time', 'ARM_DD'); ?></span><i class="arm_email_helptip_icon armfa armfa-question-circle" title="<?php _e("Displays the date and time of download file by user", 'ARM_DD'); ?>"></i>
            </div> 
            <?php
        }

        function arm_dd_download_limit_options() {
            return array('' => __('Select','ARM_DD'),
                        'd' => __('Day','ARM_DD'),
                        'w' => __('Week','ARM_DD'),
                        'm' => __('Month','ARM_DD'),
                        'y' => __('Year','ARM_DD'),
                    );
        }
    }
}

global $arm_dd_downloads;
$arm_dd_downloads = new arm_dd_downloads();
?>