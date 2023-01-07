<?php
if(!class_exists('ARM_Member_Direct_Logins')){
    
    class ARM_Member_Direct_Logins{
        
        var $blocked_pages;        
        function __construct(){
            
            $this->blocked_pages = array('');
            
            add_action( 'wp_ajax_arm_direct_logins_list', array( $this, 'arm_direct_logins_grid_data' ) );

            add_action( 'wp_ajax_arm_direct_logins_check_email', array( $this, 'arm_direct_logins_check_email' ) );
            
            add_action( 'wp_ajax_arm_direct_logins_save', array( $this, 'arm_direct_logins_save' ) );
            
            add_action( 'wp_ajax_arm_direct_logins_update_status', array( $this, 'arm_direct_logins_update_status' ) );
            
            add_action( 'wp_ajax_arm_direct_login_remove', array( $this, 'arm_direct_login_remove' ) );
            
            add_action( 'init', array( $this, 'arm_direct_logins_tokens' ), 20 );
            
            add_action( 'set_logged_in_cookie', array( $this, 'arm_direct_login_add_history_for_set_logged_in_cookie' ), 15, 5 );
            
            add_action( 'init', array( $this, 'arm_direct_logins_auto_lock_shared_account' ), 150 );
            add_action( 'wp_ajax_get_direct_logins_member_list', array( $this, 'get_direct_logins_member_list_func' ) );
            
        }
        
        function arm_direct_logins_check_email() {
            $arm_dl_data = $_REQUEST;
            if( is_email( $arm_dl_data['arm_direct_logins_email'] ) )
            {
                if( email_exists( $arm_dl_data['arm_direct_logins_email'] ) )
                {
                    $response = array( 'type' => 'error');
                }
                else
                {
                    $response = array( 'type' => 'success');
                }
            }
            echo json_encode($response);
            die;
        }
        
        function arm_direct_logins_save() {
            global $ARMember;
            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_directlogin_capabilities = 'arm_direct_logins';
                $ARMember->arm_check_user_cap($arm_directlogin_capabilities,'1');
            }
            $arm_dl_data = $_REQUEST;
            $arm_dl_expire_type = isset($arm_dl_data['arm_direct_logins_expire_type']) ? $arm_dl_data['arm_direct_logins_expire_type'] : 'hours';
            $arm_dl_expire_time_name  = 'arm_direct_logins_'.$arm_dl_expire_type;
            $arm_dl_expire_time = isset($arm_dl_data[$arm_dl_expire_time_name]) ? $arm_dl_data[$arm_dl_expire_time_name] : '1';
            
            if($arm_dl_data['arm_direct_logins_user_type'] == 'new_user')
            {
                $arm_dl_user_email = $arm_dl_data['arm_direct_logins_email'];
                                
                if(is_email( $arm_dl_user_email ) )
                {
                    if( email_exists( $arm_dl_data['arm_direct_logins_email'] ) )
                    {
                        $response = array( 'type' => 'msg', 'msg'=> '' );
                    }
                    else
                    {
                        $user_args = array(
                            'user_login' => $arm_dl_user_email,
                            'user_email' => sanitize_email($arm_dl_user_email, true),
                            'role' => $arm_dl_data['arm_direct_logins_role'],
                            'user_pass' => wp_generate_password()
                        );

                        $user_id = wp_insert_user($user_args);

                        if (!is_wp_error($user_id)) {
                            update_user_meta( $user_id, 'arm_direct_logins_user', true );
                            update_user_meta( $user_id, 'arm_direct_logins_enable', true );
                            update_user_meta( $user_id, 'arm_direct_logins_expire_time', $this->arm_direct_logins_get_expire_time($arm_dl_expire_type, $arm_dl_expire_time) );
                            update_user_meta( $user_id, 'arm_direct_logins_token', md5( time() . $user_id ) );

                            $response = array( 'type' => 'success', 'msg'=> __( 'Direct login saved successfully.', 'ARM_DIRECT_LOGINS' ) );
                        }
                        else {
                            $code = $user_id->get_error_code();
                            $response = array( 'type' => 'error', 'msg'=> $user_id->get_error_message($code) );
                        }
                    }
                }
                else
                {
                    $response = array( 'type' => 'error', 'msg'=> __( 'Please enter valid email.', 'ARM_DIRECT_LOGINS' ) );
                }
            }
            else if($arm_dl_data['arm_direct_logins_user_type'] == 'exists_user')
            {
                $user_id =(isset($arm_dl_data['arm_direct_logins_user_id_hidden']))? $arm_dl_data['arm_direct_logins_user_id_hidden']:'';
                $user_id = empty($user_id) ? $arm_dl_data['arm_direct_logins_user_id'] : $user_id;
                
                global $wpdb;
                $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->users WHERE ID = %d", $user_id));
                if($count > 0)
                {
                    
                    update_user_meta( $user_id, 'arm_direct_logins_user', true);
                    update_user_meta( $user_id, 'arm_direct_logins_enable', true);
                    update_user_meta( $user_id, 'arm_direct_logins_expire_time', $this->arm_direct_logins_get_expire_time($arm_dl_expire_type, $arm_dl_expire_time));
                    update_user_meta( $user_id, 'arm_direct_logins_token', md5( time() . $user_id ));
                    
                    $response = array( 'type' => 'success', 'msg'=> __( 'Direct login saved successfully.', 'ARM_DIRECT_LOGINS' ) );
                }
                else
                {
                    $response = array( 'type' => 'error', 'msg'=> __( 'Sorry, User not found.', 'ARM_DIRECT_LOGINS' ) );
                }
            }
            echo json_encode($response);
            die;
        }
        
        function arm_direct_logins_get_expire_time( $arm_dl_expire_type, $arm_dl_expire_time ) {
            if($arm_dl_expire_type == 'hours')
            {
                return strtotime('+' . $arm_dl_expire_time . ' hours', current_time('timestamp'));
            }
            else if($arm_dl_expire_type == 'days')
            {
                return strtotime('+' . $arm_dl_expire_time . ' days', current_time('timestamp'));   
            }
        }
        
        function arm_direct_logins_grid_data() {
            global $wpdb, $arm_global_settings, $ARMember, $arm_slugs;   
            
            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_directlogin_capabilities = 'arm_direct_logins';
                $ARMember->arm_check_user_cap($arm_directlogin_capabilities,'1');
            }
            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 5;
            $order_by = 'user_login';
            if( $sorting_col == 1 ) {
                $order_by = 'user_email';
            } else if( $sorting_col == 2 ){
                $order_by = 'role';
            }
            
            $args = array(
                'fields' => 'all',
                'meta_key' => 'arm_direct_logins_expire_time',
                'order' => $sorting_ord,
                'orderby' => $order_by,
                'meta_query' => array(
                    0 => array(
                        'key' => 'arm_direct_logins_user',
                        'value' => 1
                    )
                )
            );
            $total_users = new WP_User_Query($args);
            $total_before_filter = count($total_users->results);
            $total_after_filter = count($total_users->results);
            
            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            
            $args['offset'] = $offset;
            $args['number'] = $number;
            
            $users = new WP_User_Query($args);
            
            $users = $users->results;
            
            $ai = 0;
            $grid_columns = array(
                'User_Name' => __('Username', 'ARM_DIRECT_LOGINS'),
                'Email' => __('Email', 'ARM_DIRECT_LOGINS'),
                'Role' => __('User Role', 'ARM_DIRECT_LOGINS'),
                'Last_Logged_In' => __('Last Logged In', 'ARM_DIRECT_LOGINS'),
                'Active' => __('Active', 'ARM_DIRECT_LOGINS'),
                'Expiry' => __('Link Status', 'ARM_DIRECT_LOGINS'),
            );
            
            $grid_data = array();
            if (is_array($users) && count($users) > 0) {
                foreach ($users as $user) {                
                    if (is_numeric($user) && !is_object($user)) {
                        $user = get_user_by('id', $user);
                    }
                    
                    $arm_dl_user_id = $user->ID;
                    $arm_dl_expire_time = get_user_meta($user->ID, 'arm_direct_logins_expire_time', true);
                    $arm_dl_last_loggedin = get_user_meta($user->ID, 'arm_direct_logins_last_loggedin', true);
                    $is_enable_direct_logins_enable = get_user_meta($arm_dl_user_id, 'arm_direct_logins_enable', true);
                    
                    $capabilities = $user->{$wpdb->prefix . 'capabilities'};
                    $wp_roles = new WP_Roles();
                    $user_role = '';
                    foreach ($wp_roles->role_names as $role => $name) {
                        if (array_key_exists($role, $capabilities)) {
                            $user_role = $name;
                        }
                    }
                    $arm_dl_expire_time = $this->arm_direct_logins_get_remaining_time($arm_dl_expire_time);
                    if($arm_dl_expire_time == 'Expired')
                    {    
                        $arm_dl_active = '';
                        $arm_dl_active .= '<div class="arm_temp_switch_wrapper_disable" style="width: auto;margin: 5px 0px 0 -4px;">';
                        $arm_dl_active .= '<img src="'.ARM_DIRECT_LOGINS_IMAGES_URL.'toggle_disable_icon.png">';
                        $arm_dl_active .= '</div>';
                        $link_status = '<div class="link_status color_red">'.$arm_dl_expire_time.'</div>';
                    }
                    else
                    {
                        $arm_dl_active = '';
                        $arm_dl_active .= '<div class="arm_temp_switch_wrapper" style="width: auto;margin: 5px 0;">';
                        $arm_dl_active .= '<div class="armswitch arm_direct_login_active">';
                        $arm_dl_active .= '<input type="checkbox" id="arm_direct_logins_active_switch_'.$arm_dl_user_id.'" value="1" class="armswitch_input arm_direct_logins_active_switch" name="arm_direct_logins_active_switch_'.$arm_dl_user_id.'" data-item_id="'.$arm_dl_user_id.'" '.checked($is_enable_direct_logins_enable, 1, false).'/>';
                        $arm_dl_active .= '<label for="arm_direct_logins_active_switch_'.$arm_dl_user_id.'" class="armswitch_label"></label>';
                        $arm_dl_active .= '<span class="arm_status_loader_img" style="display: none;"></span>';
                        $arm_dl_active .= '</div></div>';
                        if($is_enable_direct_logins_enable)
                        {
                            $link_status = '<div class="link_status_'.$arm_dl_user_id.' color_green">'.$arm_dl_expire_time
                                        . ' <br/>( <span>'.__('Active','ARM_DIRECT_LOGINS').'</span> )</div>';
                        }
                        else
                        {
                            $link_status = '<div class="link_status_'.$arm_dl_user_id.' color_orenge">'.$arm_dl_expire_time
                                        . ' <br/>( <span>'.__('Inactive','ARM_DIRECT_LOGINS').'</span> )</div>';
                        }
                    }
                    
                    $view_link = admin_url('admin.php?page=' . $arm_slugs->manage_members . '&action=view_member&id=' . $arm_dl_user_id);
                    
                    $grid_view_popup_html = "<a class='arm_openpreview arm_openpreview_popup armhelptip' href='javascript:void(0)' data-id='" . $arm_dl_user_id . "' title='" . __('View Detail', 'ARM_DIRECT_LOGINS') . "'>";
                    $grid_view_popup_html1 = "</a>";


                    $grid_data[$ai][0] = $user->user_login;
                    $grid_data[$ai][1] = $grid_view_popup_html.$user->user_email.$grid_view_popup_html1;
                    $grid_data[$ai][2] = $user_role;
                    $grid_data[$ai][3] = ($arm_dl_last_loggedin != '') ? date($date_format." ".get_option('time_format'), $arm_dl_last_loggedin) : __( 'Not yet logged in', 'ARM_DIRECT_LOGINS' );
                    
                    $grid_data[$ai][4] = $arm_dl_active;
                    $grid_data[$ai][5] = $link_status;
                    
                    
                    
                    $gridAction = "<div class='arm_grid_action_btn_container arm_direct_logins_action_{$arm_dl_user_id}'>";
                    $gridAction .= $this->arm_direct_logins_get_action($arm_dl_user_id, $user->user_login);
                    $gridAction .= "</div>";
                    
                    
                    
                    $grid_data[$ai][6] = $gridAction;
                    
                    $ai++;
                }
            }
            
            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
            $response = array(
                'sColumns' => implode(',', $grid_columns),
                'sEcho' => $sEcho,
                'iTotalRecords' => $total_before_filter,
                'iTotalDisplayRecords' => $total_after_filter,
                'aaData' => $grid_data,
            );
            echo json_encode($response);
            die();
            
        }
        
        function arm_direct_logins_get_action( $arm_dl_user_id, $arm_dl_username ) {
            global $wpdb, $arm_global_settings;
            
            $is_enable_direct_logins_toekn = get_user_meta($arm_dl_user_id, 'arm_direct_logins_token', true);
                $gridAction = ''; 
                $login_url = add_query_arg('armdl_token', $is_enable_direct_logins_toekn, admin_url());
                
                $gridAction .= "<a href='javascript:void(0)'><span class='arm_dl_click_to_copy_text' data-code='{$login_url}'><img src='" . ARM_DIRECT_LOGINS_IMAGES_URL . "/grid_copy_icon.png' class='armhelptip' title='" . __('Copy link to clipboard', 'ARM_DIRECT_LOGINS') . "' onmouseover=\"this.src='" . ARM_DIRECT_LOGINS_IMAGES_URL . "/grid_copy_icon_hover.png';\" onmouseout=\"this.src='" . ARM_DIRECT_LOGINS_IMAGES_URL . "/grid_copy_icon.png';\" /></span></a>";
                
                $gridAction .= "<a href='javascript:void(0)' onclick='arm_direct_logins_edit({$arm_dl_user_id}, \"{$arm_dl_username}\");'><img src='" . ARM_DIRECT_LOGINS_IMAGES_URL . "/grid_edit.png' class='armhelptip' title='" . __('Modify Duration', 'ARM_DIRECT_LOGINS') . "' onmouseover=\"this.src='" . ARM_DIRECT_LOGINS_IMAGES_URL . "/grid_edit_hover.png';\" onmouseout=\"this.src='" . ARM_DIRECT_LOGINS_IMAGES_URL . "/grid_edit.png';\" /></a>";
                
                $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$arm_dl_user_id});'><img src='" . ARM_DIRECT_LOGINS_IMAGES_URL . "/grid_delete.png' class='armhelptip' title='" . __('Delete', 'ARM_DIRECT_LOGINS') . "' onmouseover=\"this.src='" . ARM_DIRECT_LOGINS_IMAGES_URL . "/grid_delete_hover.png';\" onmouseout=\"this.src='" . ARM_DIRECT_LOGINS_IMAGES_URL . "/grid_delete.png';\" /></a>";
                $gridAction .= $arm_global_settings->arm_get_confirm_box($arm_dl_user_id, __("Are you sure you want to delete this direct login?", 'ARM_DIRECT_LOGINS'), 'arm_direct_logins_delete_btn');
                
            return $gridAction;
        }
        
        function arm_direct_logins_get_remaining_time( $expire_time ) {
            $etime = $expire_time - current_time('timestamp');

            if ($etime < 1) {
                return __('Expired', 'ARM_DIRECT_LOGINS');
            }

            $a = array(365 * 24 * 60 * 60 => 'year',
                30 * 24 * 60 * 60 => 'month',
                24 * 60 * 60 => 'day',
                60 * 60 => 'hour',
                60 => 'minute',
                1 => 'second'
            );

            $a_plural = array('year' => 'years',
                'month' => 'months',
                'day' => 'days',
                'hour' => 'hours',
                'minute' => 'minutes',
                'second' => 'seconds'
            );

            foreach ($a as $secs => $str) {
                $d = $etime / $secs;
                if ($d >= 1) {
                    $r = round($d);
                    return __(sprintf('%d %s remaining', $r, ($r > 1 ? $a_plural[$str] : $str)), 'ARM_DIRECT_LOGINS');
                }
            }
        }
        
        function arm_direct_logins_update_status() {
            global $ARMember;
            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_directlogin_capabilities = 'arm_direct_logins';
                $ARMember->arm_check_user_cap($arm_directlogin_capabilities,'1');
            }
            if (current_user_can('administrator')) {
                update_user_meta( $_REQUEST['arm_dl_user_id'], 'arm_direct_logins_enable', $_REQUEST['arm_dl_status']);
                if($_REQUEST['arm_dl_status'])
                    $status_inword = __('Active', 'ARM_DIRECT_LOGINS');
                else
                    $status_inword = __('Inactive', 'ARM_DIRECT_LOGINS');
                
                $response = array( 'type'=>'success', 'user_id'=> $_REQUEST['arm_dl_user_id'], 'dl_status'=>$_REQUEST['arm_dl_status'], 'dl_status_inword' => $status_inword );
            }
            echo json_encode($response);
            die;
        }
        
        function arm_direct_login_remove() {
            global $ARMember;
            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_directlogin_capabilities = 'arm_direct_logins';
                $ARMember->arm_check_user_cap($arm_directlogin_capabilities,'1');
            }
            if (current_user_can('administrator')) {
                delete_user_meta( $_REQUEST['user_id'], 'arm_direct_logins_enable');
                delete_user_meta( $_REQUEST['user_id'], 'arm_direct_logins_expire_time');
                delete_user_meta( $_REQUEST['user_id'], 'arm_direct_logins_token');
                delete_user_meta( $_REQUEST['user_id'], 'arm_direct_logins_user', true);
                $response = array( 'type' => 'success', 'msg'=> __( 'User direct login removed successfully', 'ARM_DIRECT_LOGINS' ) );
            }
            echo json_encode($response);
            die;
        }
        
        function arm_direct_logins_get_all_roles() {
            $allRoles = array();
            if (!function_exists('get_editable_roles') && file_exists(ABSPATH . '/wp-admin/includes/user.php')) {
                require_once(ABSPATH . '/wp-admin/includes/user.php');
            }
            
            $roles = get_editable_roles();
            if (!empty($roles)) {
                foreach ($roles as $key => $role) {
                    $allRoles[$key] = $role['name'];
                }
            }
           
            if (is_plugin_active('bbpress/bbpress.php'))
            {
               if(function_exists('bbp_get_dynamic_roles')) 
               {
                   foreach ( bbp_get_dynamic_roles() as $role => $details )
                    {
                        $allRoles[$role] = $details['name'];         
                    }
               }
            }
            
            return $allRoles;
        }
        
        function arm_direct_logins_get_all_members($type = 0, $only_total = 0) {
            global $wp, $wpdb, $arm_errors, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings;

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
            }

            $user_where = " WHERE 1=1";
            if (!empty($super_admin_ids)) {
                $user_where .= " AND u.ID NOT IN (" . implode(',', $super_admin_ids) . ")";
            }

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
        
        function arm_direct_logins_tokens() 
        {
            if (!empty($_GET['armdl_token'])) 
            {
                if(!is_user_logged_in())
                {
                    $error_messages = array();

                    $armdl_token = $_GET['armdl_token'];
                    $user = $this->arm_direct_logins_check_is_valid($armdl_token);
                                    
                    if (empty($user)) {
                        wp_redirect(home_url());
                    } else {
                        global $browser_session_id;
                        $browser_session_id = session_id();
                        
                        $user = $user[0];

                        $user_id = $user->ID;
                        $user_login = $user->login;
                        wp_set_current_user($user_id, $user_login);
                        wp_set_auth_cookie($user_id);
                        
                        update_user_meta( $user_id, 'arm_direct_logins_last_loggedin', current_time('timestamp'));

                        //$redirect_to = admin_url();
                        $redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : admin_url();
                        $redirect_to_url = apply_filters( 'login_redirect', $redirect_to, isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '', $user );

                        do_action('wp_login', $user_login, $user);

                        if(!empty($redirect_to_url)) {
                            $redirect_to = $redirect_to_url;
                        }

                        wp_redirect($redirect_to); 
                    }
                    exit();
                }
                else 
                {
                    $user_id = get_current_user_id();
                    if (!empty($user_id)) {
                        $check = get_user_meta( $user_id, 'arm_direct_logins_user', true );
                        if(!empty($check) && $check == '1')
                        {
                            $enable = get_user_meta( $user_id, 'arm_direct_logins_enable', true );
                            $expire = get_user_meta( $user_id, 'arm_direct_logins_expire_time' );
                            $expire = $expire[0];
                            if(!empty($expire) && current_time('timestamp') <= floatval($expire) && !empty($enable) && $enable == '1')
                            {
                                global $pagenow;
                                $bloked_pages = $this->blocked_pages;
                                $page = !empty($_GET['page']) ? $_GET['page'] : '';
                                if ((!empty($page) && in_array($page, $bloked_pages)) || (!empty($pagenow) && in_array($pagenow, $bloked_pages))) {
                                    wp_die(__("You don't have permission to access this page", 'ARM_DIRECT_LOGINS' ));
                                }
                            }
                            else
                            {
                                wp_logout();
                                wp_redirect(home_url());
                                exit();
                            }
                        }
                    }
                }
            }
        }
        
        function arm_direct_logins_check_is_valid( $armdl_token ) {
            $args = array(
                'fields' => 'all',
                'meta_key' => 'arm_direct_logins_expire_time',
                'meta_query' => array(
                    0 => array(
                        'key' => 'arm_direct_logins_token',
                        'value' => sanitize_text_field($armdl_token),
                        'compare' => '='
                    ),
                    1 => array(
                        'key' => 'arm_direct_logins_enable',
                        'value' => true,
                        'compare' => '='
                    )
                )
            );

            $users = new WP_User_Query($args);
            
            if (empty($users->results)) {
                return false;
            }

            $users_data = $users->results;
            foreach ($users_data as $key => $user) {
                $expire = get_user_meta($user->ID, 'arm_direct_logins_expire_time', true);
                if ($expire <= current_time('timestamp')) {
                    unset($users_data[$key]);
                }
            }

            return $users_data;
        }
        
        function arm_direct_login_add_history_for_set_logged_in_cookie($auth_cookie, $expire, $expiration, $user_id, $scheme) {
            
            global $wpdb, $ARMember, $arm_global_settings, $arm_is_change_password_form_for_login, $arm_login_from_registration, $arm_is_update_password_form_edit_profile_login, $browser_session_id;
            
            
            $arm_all_block_settings = $arm_global_settings->block_settings;
           
            if (isset($arm_all_block_settings['track_login_history']) && $arm_all_block_settings['track_login_history'] != 1)
                return;
           
            if (empty($user_id)) {
                return;
            }
            
            
            if ($arm_is_change_password_form_for_login == 1) {
                $arm_is_change_password_form_for_login = 0;
                return;
            }
            
            if($arm_is_update_password_form_edit_profile_login == 1)
            {
                $arm_is_update_password_form_edit_profile_login = 0;
                return;
            }

            $logged_in_ip = $ARMember->arm_get_ip_address();
            $country = $ARMember->arm_get_country_from_ip($logged_in_ip);
            $logged_in_time = current_time('mysql');
            $browser_info = $ARMember->getBrowser($_SERVER['HTTP_USER_AGENT']);
            $browser_detail = $browser_info['name'] . ' (' . $browser_info['version'] . ')';
            $tbl_login_history = $ARMember->tbl_arm_login_history;
            $user_current_status = 1;
            
            $select_query = "SELECT count(*) FROM `{$tbl_login_history}` WHERE `arm_history_session` = '".$browser_session_id."' AND `arm_user_current_status` = 1";
        
            $select_result = $wpdb->get_var($select_query);
            
            if($select_result > 0)
            {
                return;
            }
            
            $update_query = $wpdb->prepare("UPDATE `{$tbl_login_history}` SET `arm_user_current_status` = %d  WHERE `arm_user_current_status` != %d AND `arm_user_id` = %d AND `arm_history_browser` = %s AND `arm_logged_in_ip` = %s", 0, 0, $user_id, $browser_detail, $logged_in_ip);
            $update_result = $wpdb->query($update_query);
            $insert_query = $wpdb->prepare("INSERT INTO `{$tbl_login_history}` (`arm_user_id`,`arm_logged_in_ip`,`arm_logged_in_date`,`arm_history_browser`,`arm_history_session`,`arm_login_country`,`arm_user_current_status`) VALUES (%d,%s,%s,%s,%s,%s,%d)", $user_id, $logged_in_ip, $logged_in_time, $browser_detail, $browser_session_id, $country,1);
            $insert_result = $wpdb->query($insert_query);
            
            if( $arm_login_from_registration == 1 ){
                $arm_login_from_registration = 0;
                return;
            }
            
            $autolock_cookie_name = 'arm_dl_autolock_cookie_' . $user_id;
            $cookie_value = $browser_session_id . '||' . $wpdb->insert_id;
            $cookie_exp_time = time() + 60 * 60 * 24 * 30;
            setcookie($autolock_cookie_name, $cookie_value, $cookie_exp_time, '/');
        }
        
        function arm_direct_logins_auto_lock_shared_account() {
            if(is_user_logged_in() && is_admin())
            {
                $user_id = get_current_user_id();
                
                global $arm_global_settings, $ARMember, $wpdb;  
                $arm_all_general_settings = $arm_global_settings->global_settings;
                
                $autolock_shared_account = (isset($arm_all_general_settings['autolock_shared_account'])) ? $arm_all_general_settings['autolock_shared_account'] : 0;
            
                if ($autolock_shared_account == 1) {
                   
                    if (isset($_COOKIE['arm_dl_autolock_cookie_'.$user_id]) && !empty($_COOKIE['arm_dl_autolock_cookie_'.$user_id])) {
                        
                        $arm_autolock_cookie = $_COOKIE['arm_dl_autolock_cookie_'.$user_id]; 
                        $stored_cookie = $arm_autolock_cookie;
                        $inserted_id = explode('||', $stored_cookie);
                        $arm_session_id = $inserted_id[0];
                        $arm_history_id = $inserted_id[1];
                        $logged_out_time = current_time('mysql');
                        $login_history_table = $ARMember->tbl_arm_login_history;
                       
                        $update_query = $wpdb->prepare("UPDATE `{$login_history_table}` SET `arm_logout_date` = %s, `arm_user_current_status` = %d WHERE `arm_history_id` != %d AND `arm_history_session` != %s AND `arm_user_id` = %d AND `arm_user_current_status` != %d", $logged_out_time, 0, $arm_history_id, $arm_session_id, $user_id, 0); 
                        $wpdb->query($update_query);
                        
                        unset($_COOKIE['arm_dl_autolock_cookie_'.$user_id]);
                        setcookie( 'arm_dl_autolock_cookie_'.$user_id, '',time() - 3600,'/');
                      
                    }
                    
                    wp_destroy_other_sessions();
                }
            }
        }
        
        function arm_direct_logins_footer() {
            $footer = '<div class="wrap arm_page arm_manage_members_main_wrapper" style="float:right; margin-right:20px;">';
            $footer .= '<a href="'.ARM_DIRECT_LOGINS_URL.'/documentation" target="_blank">';
            $footer .= __('Documentation', 'ARM_DIRECT_LOGINS');
            $footer .= '</a>';
            $footer .= '</div>';
            echo $footer;
        }

        function get_direct_logins_member_list_func(){
            if(isset($_REQUEST['action']) && $_REQUEST['action']=='get_direct_logins_member_list') {

                global $ARMember;
                if( method_exists($ARMember, 'arm_check_user_cap') ){
                    $arm_directlogin_capabilities = 'arm_direct_logins';
                    $ARMember->arm_check_user_cap($arm_directlogin_capabilities,'1');
                }

                $text = $_REQUEST['txt'];
                $type = 0;
                
                global $wp, $wpdb, $arm_errors, $ARMember, $arm_members_class, $arm_member_forms, $arm_global_settings;

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
                }

                $user_where = " WHERE 1=1";
                if (!empty($super_admin_ids)) {
                    $user_where .= " AND u.ID NOT IN (" . implode(',', $super_admin_ids) . ")";
                }
                $user_where .= " AND (user_login LIKE '%".$text."%' OR `user_email` LIKE '%".$text."%' ) ";
                $operator = " AND ";

                $user_where .= " {$operator} um.meta_key = '{$capability_column}' ";
                //$user_where .= " AND um.meta_value NOT LIKE '%administrator%' ";
                $user_join = "";
                if (!empty($type) && in_array($type, array(1, 2, 3))) {
                    $user_join = " INNER JOIN {$ARMember->tbl_arm_members} arm1 ON u.ID = arm1.arm_user_id";
                    $user_where .= " AND arm1.arm_primary_status='$type' ";
                }

                $user_fields = "u.ID,u.user_registered,u.user_login,u.user_email";
                $user_group_by = " GROUP BY u.ID ";
                $user_order_by = " ORDER BY u.user_registered DESC limit 0,10";
                
                $user_query = "SELECT {$user_fields} FROM `{$user_table}` u LEFT JOIN `{$usermeta_table}` um ON u.ID = um.user_id {$user_join} {$user_where} {$user_group_by} {$user_order_by} ";
                $users_details = $wpdb->get_results($user_query);

                $all_members = $users_details;
                
                $user_list_html = "";
                $drData = array();
                if(!empty($all_members)) {
                    foreach ( $all_members as $user ) {
                        
                        //$user_list_html .= '<li data-id="'.$user->ID.'">' . $user->user_login . ' ('.$user->user_email.')</li>';
                        $drData[] = array(
                                    'id' => $user->ID,
                                    'value' => $user->user_login,
                                    'label' => $user->user_login . ' ('.$user->user_email.')',
                                );
                    }
                }
                $response = array('status' => 'success', 'data' => $drData);
                echo json_encode($response);
                die;
            }
        }
    }
}

global $arm_member_direct_logins;
$arm_member_direct_logins = new ARM_Member_Direct_Logins();
?>