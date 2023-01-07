<?php
if(!class_exists('arm_aff_banner')){
    
    class arm_aff_banner{

        
        function __construct(){
            add_action( 'wp_ajax_arm_aff_add_item', array( $this, 'arm_aff_item_file_save' ) );
            
            add_action( 'wp_ajax_arm_banner_list', array( $this, 'arm_banner_grid_data' ) );
            
            add_action( 'wp_ajax_arm_banner_delete', array( $this, 'arm_banner_delete' ) );
            
            add_action( 'wp_ajax_arm_banner_bulk_action', array( $this, 'arm_banner_bulk_action' ) );
            
            add_action( 'wp_ajax_arm_banner_update_status', array( $this, 'arm_banner_update_status' ) );
            
        }
        
        function arm_banner_update_status() {
            if (current_user_can('administrator')) {
                global $wpdb, $arm_affiliate, $ARMember;

                if(method_exists($ARMember, 'arm_check_user_cap')){
                    $arm_affiliate_capabilities = 'arm_affiliate_banners';
                    $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
                }
                $wpdb->update( 
                        $arm_affiliate->tbl_arm_aff_banner, 
                        array( 'arm_status' => $_REQUEST['arm_aff_status'], ), 
                        array( 'arm_banner_id' => $_REQUEST['arm_banner_id'] ), 
                        array( '%d' ), 
                        array( '%d' ) 
                    );
                
                $response = array( 'type'=>'success');
            }
            echo json_encode($response);
            die;
        }
        
        function arm_aff_item_file_save() {
               
            global $ARMember;
            

            if (!is_dir(ARM_AFF_OUTPUT_DIR))
                wp_mkdir_p(ARM_AFF_OUTPUT_DIR);
            
            $allowed_extensions = array();
            $errors = false;
            $errors_data = array();
            $arm_item_file_urls = array();
            $arm_timestamp = current_time('timestamp');
            $arm_item_file_content = '';
            $arm_file_no_url = isset($_REQUEST['arm_file_no_url']) ? $_REQUEST['arm_file_no_url'] : 0;
            for($file_no = 0; $file_no < $_REQUEST['arm_item_no_file']; $file_no++)
            {
                $arm_item_file_url = '';
                $file_ext = '';
                $file_name = 'arm_item_file_' . $file_no;
                if(isset($_FILES[$file_name]['name'])&&$_FILES[$file_name]['name']!=''){
                    $file_ext = explode('.', $_FILES[$file_name]['name']);
                    $file_ext = end($file_ext);
                    $files_temp = explode('.', $_FILES[$file_name]['name']);
                    $file_ext = strtolower(end($files_temp));
                }
                
                if (!empty($allowed_extensions) && !in_array('.'.$file_ext, $allowed_extensions)) {
                    $errors = true;
                    $errors_data[] = __("Sorry! Not able to upload " . $file_ext . " file.", 'ARM_AFFILIATE');
                    continue;
                }

                if (!empty($_FILES[$file_name]["tmp_name"]) && !@is_uploaded_file($_FILES[$file_name]["tmp_name"])) {
                    $errors = true;
                    $errors_data[] = __("Please select valid file.", 'ARM_AFFILIATE');
                }

                if (!empty($_FILES[$file_name]["tmp_name"]) && isset($_FILES[$file_name]['error']) && !empty($_FILES[$file_name]['error'])) {
                    $errors = true;
                    $errors_data[] = $_FILES['arf_item_file_url']['error'];
                }

                if (isset($_FILES[$file_name]["name"]) && !empty($_FILES[$file_name]["name"])) {
                    $arm_item_file_name = $arm_timestamp . '_' . $_FILES[$file_name]["name"];
                    if (@move_uploaded_file($_FILES[$file_name]["tmp_name"], ARM_AFF_OUTPUT_DIR . $arm_item_file_name)) {
                        $arm_file_no_url++;
                        $arm_item_file_url = ARM_AFF_OUTPUT_URL . $arm_item_file_name;
                    }
                }
                
                $arm_item_file_content.= '<div class="arm_dd_item_'.$arm_file_no_url.'"><img src="'.$arm_item_file_url.'" height="100" />';
                $arm_item_file_content.= '<input type="hidden" name="file_urls" id="file_url" value="'.$arm_item_file_name.'" /></div>';
                
                $arm_item_file_urls[] = urlencode($arm_item_file_url);
            }
            $errors = '<span class="arm_error_msg">'.implode('<br/>',array_unique($errors_data)).'</span>';
            
            if(!empty($arm_item_file_urls)){
                $response = array( 'type' => 'success', 'error_msg'=> $errors, 'no_file'=>count($arm_item_file_urls), 'content' => $arm_item_file_content);
            }
            else
            {
                $response = array( 'type' => 'error', 'error_msg'=> $errors );
            }
            echo json_encode($response);
            die;
        }
        
        function arm_aff_item_save( $item_data ) {
            global $wpdb, $ARMember, $arm_affiliate, $arm_global_settings;
            
            $arm_item_action = isset($item_data['action']) ? $item_data['action'] : '';
            $arm_item_id = isset($item_data['item_id']) ? $item_data['item_id'] : '';
            $arm_item_name = isset($item_data['arm_item_name']) ? $item_data['arm_item_name'] : '';
            $arm_item_file = isset($item_data['file_urls']) ? $item_data['file_urls'] : '';
            $arm_item_description = isset($item_data['arm_item_description']) ? $item_data['arm_item_description'] : '';
            $arm_item_url = isset($item_data['arm_item_url']) ? $item_data['arm_item_url'] : '';
            $arm_status = isset($item_data['arm_status']) ? $item_data['arm_status'] : '';
            $arm_open_new_tab = isset($item_data['arm_open_new_tab']) ? $item_data['arm_open_new_tab'] : 0;
            
            
            
            $arm_remove_file = isset($item_data['arm_remove_file']) ? $item_data['arm_remove_file'] : '';
            $arm_item_datetime = current_time('mysql');
            $arm_timestamp = current_time('timestamp');
            $redirect_to = admin_url('admin.php?page=arm_affiliate_banners');
            
            //remove_file
            if( is_array($arm_remove_file) && $arm_remove_file !='' ) {
                foreach($arm_remove_file as $file_name){
                    if($file_name != '' && file_exists( ARM_DD_OUTPUT_DIR . $file_name )){
                        unlink( ARM_DD_OUTPUT_DIR . $file_name );
                    }
                }
            }
            
            if( $arm_item_action == 'update_item' && $arm_item_id > 0 ) {   
                
                $item_data = array(
                    'arm_title' => $arm_item_name,
                    'arm_description' => $arm_item_description,
                    'arm_image' => $arm_item_file,
                    'arm_link' => $arm_item_url,
                    'arm_open_new_tab' => $arm_open_new_tab,
                    'arm_status' => $arm_status
                );
                
                $item_data_format = array( '%s', '%s', '%s', '%s' );
                $item_where = array( 'arm_banner_id' => $arm_item_id );
                $item_where_formate = array( '%d' );
                
                $wpdb->update( $arm_affiliate->tbl_arm_aff_banner, $item_data, $item_where, $item_data_format, $item_where_formate );
                
                $success_message = __('Banner detail has beed updated successfully.', 'ARM_AFFILIATE');
                $ARMember->arm_set_message('success', $success_message);
                $redirect_to = $arm_global_settings->add_query_arg("action", "edit_item", $redirect_to);
                $redirect_to = $arm_global_settings->add_query_arg("id", $arm_item_id, $redirect_to);
                
            } else {
                
                $item_data = array(
                    'arm_title' => $arm_item_name,
                    'arm_description' => $arm_item_description,
                    'arm_image' => $arm_item_file,
                    'arm_link' => $arm_item_url,
                    'arm_open_new_tab' => $arm_open_new_tab,
                    'arm_status' => $arm_status
                );
                
                $new_item_results = $wpdb->insert($arm_affiliate->tbl_arm_aff_banner, $item_data);
                $arm_item_id = $wpdb->insert_id;
                if( $arm_item_id > 0 ) {
                    $success_message = __('New banner has been added successfully.', 'ARM_AFFILIATE');
                    $ARMember->arm_set_message('success', $success_message);
                    $redirect_to = $arm_global_settings->add_query_arg("action", "edit_item", $redirect_to);
                    $redirect_to = $arm_global_settings->add_query_arg("id", $arm_item_id, $redirect_to);
                }
            }
            
            if (!empty($redirect_to)) {
                wp_redirect($redirect_to);
                exit;
            }
            
        }
        
        function arm_aff_item_data( $item_id ) {
            global $wpdb, $ARMember, $arm_affiliate;
            if($item_id > 0)
            {
                $item_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$arm_affiliate->tbl_arm_aff_banner}` WHERE arm_banner_id = %d ", $item_id ), ARRAY_A );
                return $item_data;
            }
            return false;
        }
        
        function arm_banner_grid_data() {
            global $wpdb, $arm_affiliate, $arm_affiliate_settings, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $arm_aff_referrals;
            
            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_banners';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $nowDate = current_time('mysql');
            $current_time = current_time('timestamp');
            
            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
            $affiliate_link = isset($affiliate_options['arm_aff_referral_url']) ? $affiliate_options['arm_aff_referral_url'] : get_home_url();
            $affiliate_parmeter = isset($affiliate_options['arm_aff_referral_var']) ? $affiliate_options['arm_aff_referral_var'] : 'ref';
            $affiliate_encoding = isset($affiliate_options['arm_aff_id_encoding']) ? $affiliate_options['arm_aff_id_encoding'] : '0';
                    
            $grid_columns = array(
                'title' => __('Title', 'ARM_AFFILIATE'),
                'thumbnail' => __('Banner', 'ARM_AFFILIATE'),
                'shortcode' => __('Shortcode', 'ARM_AFFILIATE'),
                'status' => __('Status', 'ARM_AFFILIATE'),
                'desc' => __('Link', 'ARM_AFFILIATE'),
            );

            $tmp_query = "SELECT * FROM {$arm_affiliate->tbl_arm_aff_banner}";
            $form_result = $wpdb->get_results($tmp_query);
            $total_before_filter = count($form_result);
            
//            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
//            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 5;
            $order_by = ' arm_banner_id desc';
            
            
            $grid_data = array();
            $ai = 0;
            $user_table = $wpdb->users;
            $tmp_query = "SELECT * FROM `{$arm_affiliate->tbl_arm_aff_banner}` "
                        ." ORDER BY {$order_by}";
                        
            $form_result = $wpdb->get_results($tmp_query);
            $total_after_filter = count($form_result);
            
            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            
            $tmp_query = $tmp_query . " LIMIT {$offset},{$number}";
                        
            $form_result = $wpdb->get_results($tmp_query);
            
            foreach ($form_result as $banner) {
                $arm_banner_id = $banner->arm_banner_id;
                $arm_affiliate_title = $banner->arm_title;
                $arm_link = $banner->arm_link;
                $arm_image = $banner->arm_image;
                $arm_banner_user_status = $banner->arm_status;
                $arm_item_shortcode = "[arm_aff_banner item_id='".$arm_banner_id."']";
                $short_code = '<div class="arm_shortcode_text arm_form_shortcode_box">
                            <span class="armCopyText">'.$arm_item_shortcode.'</span>
                            <span class="arm_click_to_copy_text" data-code="'.$arm_item_shortcode.'">Click to copy</span>
                            <span class="arm_copied_text"><img src="'.ARM_AFFILIATE_IMAGES_URL.'/copied_ok.png" alt="ok">Code Copied</span>
                    </div>';
                
                
                $arm_aff_active = '';
                $arm_aff_active .= '<div class="arm_temp_switch_wrapper" style="width: auto;margin: 5px 0;">';
                $arm_aff_active .= '<div class="armswitch arm_banner_active">';
                $arm_aff_active .= '<input type="checkbox" id="arm_banner_active_switch_'.$arm_banner_id.'" value="1" class="armswitch_input arm_banner_active_switch" name="arm_banner_active_switch_'.$arm_banner_id.'" data-item_id="'.$arm_banner_id.'" '.checked($arm_banner_user_status, 1, false).'/>';
                $arm_aff_active .= '<label for="arm_banner_active_switch_'.$arm_banner_id.'" class="armswitch_label"></label>';
                $arm_aff_active .= '<span class="arm_status_loader_img" style="display: none;"></span>';
                $arm_aff_active .= '</div></div>';
                
                
                $grid_data[$ai][0] = "<input id=\"cb-item-action-{$arm_banner_id}\" class=\"chkstanard\" type=\"checkbox\" value=\"{$arm_banner_id}\" name=\"item-action[]\">";
                $grid_data[$ai][1] = $arm_affiliate_title;
                $grid_data[$ai][2] = '<img src="'.ARM_AFF_OUTPUT_URL.$arm_image.'" height="50" />';
                $grid_data[$ai][3] = $short_code;
                $grid_data[$ai][4] = $arm_aff_active;
                $grid_data[$ai][5] = $arm_link;
                
                $edit_banner = admin_url('admin.php?page=arm_affiliate_banners&action=edit_item&id='.$arm_banner_id);
                $gridAction = "<div class='arm_grid_action_btn_container'>";
                                    $gridAction .= "<a href='".$edit_banner."' class='armhelptip' title='" . __('Edit Banner', 'ARM_AFFILIATE') . "' ><img src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit.png' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit.png';\" /></a>";
                                    
                $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$arm_banner_id});'><img src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete.png' class='armhelptip' title='" . __('Delete', 'ARM_AFFILIATE') . "' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete.png';\" /></a>";
                                    $gridAction .= $arm_global_settings->arm_get_confirm_box($arm_banner_id, __("Are you sure you want to delete this banner?", 'ARM_AFFILIATE'), 'arm_aff_banner_delete_btn');
                                $gridAction .= "</div>";
                $grid_data[$ai][6] = $gridAction;
             
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
        
        function arm_banner_delete( $banner_id ) {
            global $wpdb, $arm_affiliate, $ARMember;

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_banners';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }
            $wpdb->query( "DELETE FROM `$arm_affiliate->tbl_arm_aff_banner` WHERE arm_banner_id = " . $_POST['id'] ." ");
            $response = array( 'type' => 'success', 'msg' => __( 'banner is deleted successfully.', 'ARM_AFFILIATE' ) );  
            echo json_encode($response);
            die;
        }
        
        function arm_banner_bulk_action() {
            global $wpdb, $ARMember, $arm_members_class, $arm_global_settings, $arm_subscription_plans, $arm_case_types, $arm_affiliate;
            if (!isset($_POST)) {
                    return;
            }

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_banners';
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
                            $delete_referral = $wpdb->query( "DELETE FROM `$arm_affiliate->tbl_arm_aff_banner` WHERE arm_banner_id IN (".$aff_ids.")");
                            $message = __('banner(s) has been deleted successfully.', 'ARM_AFFILIATE');
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
        
    }
}

global $arm_aff_banner;
$arm_aff_banner = new arm_aff_banner();
?>