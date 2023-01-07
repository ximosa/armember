<?php
if(!class_exists('arm_aff_referrals')){
    
    class arm_aff_referrals{

        var $referral_status;
        function __construct(){
            global $arm_affiliate;

            $this->referral_status = array(
                '0'=>__('pending', 'ARM_AFFILIATE'), 
                '1'=>__('accepted', 'ARM_AFFILIATE'), 
                '2'=>__('paid', 'ARM_AFFILIATE'), 
                '3'=>__('rejected', 'ARM_AFFILIATE')
            );
            add_action( 'wp_ajax_arm_referral_bulk_action', array( $this, 'arm_refferal_bulk_action' ) );
            
            add_action( 'wp_ajax_arm_referrals_list', array( $this, 'arm_referrals_grid_data' ) );
            
            add_action( 'wp_ajax_arm_referral_ajax_action', array( $this, 'arm_referral_ajax_action' ) );
            
            add_action( 'wp_ajax_arm_referral_edit', array( $this, 'arm_referral_edit' ) );
            
            add_filter( 'arm_add_arm_entries_value', array( $this, 'add_affiliate_in_arm_entries' ), 10, 1 );

            if (version_compare($arm_affiliate->get_armember_version(), '2.2.1', '>=') ) {

                add_action( 'arm_after_add_new_user', array( $this, 'arm_aff_add_referral_2_1' ), 11, 2 );
                
                add_action( 'arm_after_add_transaction', array( $this, 'arm_aff_add_referral_transaction_2_1' ), 11, 1 );

                add_action( 'arm_after_recurring_payment_success_outside', array( $this, 'arm_affiliate_add_recurring_referral_transaction' ), 14, 5 );

                add_action( 'arm_after_accept_bank_transfer_payment', array( $this, 'arm_aff_after_success_payment' ), 11, 2 );

            } else {

                add_action( 'arm_after_add_new_user', array( $this, 'arm_aff_add_referral' ), 10, 2 );
                
                add_action( 'arm_after_add_transaction', array( $this, 'arm_aff_add_referral_transaction' ), 10, 1 );

            }


            add_action( 'delete_user', array( $this, 'delete_referrals_when_user_delete' ) );
            
            add_action( 'arm_aff_handle_export', array( $this, 'export_referral_in_csv' ) );
            add_action('arm_after_add_free_plan_transaction', array( $this, 'arm_aff_add_free_plan_referral_transaction' ),10,1);
            add_shortcode('arm_referrals_friend_invite',array( $this,'arm_referrals_friend_invite' ));
            
            add_action( 'wp_ajax_arm_referrals_invite_friend', array( $this, 'arm_referrals_invite_friend' ) );

            add_filter('arm_email_notification_shortcodes_outside', array(&$this, 'arm_email_notification_shortcodes_func'), 10, 1);

            add_filter('arm_admin_email_notification_shortcodes_outside', array(&$this, 'arm_email_notification_shortcodes_func'), 10, 1);

            add_filter('arm_change_advanced_email_communication_email_notification', array(&$this, 'arm_change_advanced_email_communication_func'), 10, 3);

        }

        function arm_change_advanced_email_communication_func($content, $user_id, $user_plan) {
            global $arm_global_settings, $arm_affiliate, $wpdb;
            
            //$user_id = get_current_user_id();
            //$user = get_userdata($user_id);
            //echo "rpt_log : tbl : ".$arm_affiliate->tbl_arm_wp_affiliates;die;
            //$affiliate_tbl = $wpdb->prefix."affiliate_wp_affiliates";
            
            $affiliate_tbl = $arm_affiliate->tbl_arm_aff_affiliates;
            $tmp_query = "SELECT * FROM {$affiliate_tbl} WHERE arm_user_id = {$user_id}";
            
            $affiliate_user_id = $wpdb->get_row($tmp_query, 'OBJECT');
            $affiliate_user_id = !empty($affiliate_user_id) ? $affiliate_user_id->arm_affiliate_id : "-";

            $content = str_replace('{ARM_MESSAGE_AFFILIATE_ID}', $affiliate_user_id, $content);

            return $content;
        }

        function arm_email_notification_shortcodes_func($shortcode_array) {
            $shortcode_array[] = array (
                                'title_on_hover' => __("To Display Affiliate User ID", 'ARM_AFFILIATE'),
                                'shortcode' => '{ARM_MESSAGE_AFFILIATE_ID}',
                                'shortcode_label' => __("Affiliate User ID", 'ARM_AFFILIATE')
                            );
            return $shortcode_array;
        }

        function arm_referrals_invite_friend(){
            global $wpdb, $ARMember, $arm_manage_communication, $arm_global_settings;
            $response = array('status' => 'false');
            if(isset($_POST['invite_email']) && !empty($_POST['invite_email']) && is_user_logged_in()){
                $current_user_id = get_current_user_id();
                $user_info = get_userdata($current_user_id);
                $user_login = $user_info->user_login;
                $invite_emails=explode(',',$_POST['invite_email']);
                $user_plans = get_user_meta($current_user_id, 'arm_user_plan_ids', true);
                $user_plan = isset($user_plans) && !empty($user_plans) ? implode(',', $user_plans) : 0;
                $message_type = 'arm_when_user_referrals_invite_friend';

                $messages = $wpdb->get_results("SELECT `arm_message_subject`, `arm_message_content` , `arm_message_send_copy_to_admin`, `arm_message_send_diff_msg_to_admin`, `arm_message_admin_message` FROM `" . $ARMember->tbl_arm_auto_message . "` WHERE `arm_message_status`='1' AND `arm_message_type`='" . $message_type . "' AND (FIND_IN_SET(" . $user_plan . ", `arm_message_subscription`) OR (`arm_message_subscription`=''))");
                //if(!empty($messages)) {
                    $is_sent_to_admin = 0;
                    foreach ($invite_emails as $friend_email) {
                        if (!empty($messages)) {
                            foreach ($messages as $msg) {
                                $content_subject = $msg->arm_message_subject;
                                $content_description = $msg->arm_message_content;
                                $send_one_copy_to_admin = $msg->arm_message_send_copy_to_admin;
                                $send_diff_copy_to_admin = $msg->arm_message_send_diff_msg_to_admin;
                                $admin_content_description = $msg->arm_message_admin_message;

                                $subject = $arm_manage_communication->arm_filter_communication_content($content_subject, $current_user_id, $user_plan, '');
                                $message = $arm_manage_communication->arm_filter_communication_content($content_description, $current_user_id, $user_plan, '');
                                $admin_message = $arm_manage_communication->arm_filter_communication_content($admin_content_description, $current_user_id, $user_plan, '');


                                $send_mail = $arm_global_settings->arm_wp_mail('', $friend_email, $subject, $message); 
                                if ($send_one_copy_to_admin == 1 && $is_sent_to_admin== 0) {
                                    if($send_diff_copy_to_admin == 1)
                                    {
                                       $send_mail = $arm_global_settings->arm_send_message_to_armember_admin_users('', $subject, $admin_message);
                                    }
                                    else
                                    {                                    
                                       $send_mail = $arm_global_settings->arm_send_message_to_armember_admin_users('', $subject, $message); 
                                    }
                                    
                                }
                            }
                        }
                        $is_sent_to_admin = 1;
                    }  
                    //$arm_manage_communication->membership_communication_mail('arm_when_user_referrals_invite_friend', $current_user_id, '0');

                    $success_msg = 'Successfully mail sent.';
                    $response = array('status' => 'success', 'type' => 'message', 'message' => $success_msg);  
                //}
                
            }
            echo json_encode($response);
            exit;
        }
        function arm_referrals_friend_invite($atts){
            global $wpdb, $ARMember, $arm_affiliate, $arm_member_forms, $arm_aff_layout;

            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }
            $ARMember->arm_session_start();
            $ref_content='';
             if(is_user_logged_in()) {
                
                $ARMember->set_front_css(true);
                $ARMember->set_front_js(true);
                $arm_aff_layout->arm_set_front_js_css(true);


                $current_user_id = get_current_user_id();
                $get_armaffiliates = $wpdb->get_results("SELECT * FROM {$arm_affiliate->tbl_arm_aff_affiliates} WHERE arm_status= 1 and arm_user_id=".$current_user_id, ARRAY_A);
                if(!empty($get_armaffiliates)){

                    $ref_content .='<div class="arm_referrals_main_container_form" >';
                        $atts = shortcode_atts(array(
                            'form_id' =>'',
                            'title' => esc_html__('Refer a Friend', 'ARM_AFFILIATE'),
                            'email_label' =>esc_html__('Email Address', 'ARM_AFFILIATE'),
                            'submit_button_text' => esc_html__("Send Email", 'ARM_AFFILIATE'),
                            'success_msg' => esc_html__("Mail sent successfully", 'ARM_AFFILIATE'),
                        ),$atts);
                        $form_id= isset($atts['form_id']) ? $atts['form_id'] : '101';
                        $arm_referrals_form_title = isset($arraytts['title']) ? $atts['title'] : '';
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
                    
                        $form_attr = ' data-ng-controller="ARMCtrlaffr" data-ng-cloak="" data-ng-id="' . $form_id . '" data-ng-submit="armreferralsFormSubmit(arm_form.$valid, \'arm_referrals_form_' . $setupRandomID . '\', $event);" onsubmit="return false;"';
                        $is_form_class_rtl = '';
                        if (is_rtl()) {
                            $is_form_class_rtl = 'is_form_class_rtl';
                        }
                        $captcha_code = arm_generate_captcha_code();
                        if (!isset($_SESSION['ARM_FILTER_INPUT'])) {
                            $_SESSION['ARM_FILTER_INPUT'] = array();
                        }
                        if (isset($_SESSION['ARM_FILTER_INPUT'][$setupRandomID])) {
                            unset($_SESSION['ARM_FILTER_INPUT'][$setupRandomID]);
                        }
                        $_SESSION['ARM_FILTER_INPUT'][$setupRandomID] = $captcha_code;
                        $_SESSION['ARM_VALIDATE_SCRIPT'] = true;
                        
                        $form_attr .= ' data-submission-key="' . $captcha_code . '" ';

                        $is_form_class_rtl = '';
                        if (is_rtl()) {
                            $is_form_class_rtl = 'is_form_class_rtl';
                        }
                        $ref_content .='<style type="text/css" id="arm_referrals_form_style_' . $form_id . '">' . $form_css['arm_css'] . '</style>';
                        $ref_content .='<div class="arm_member_form_container arm_referrals_form arm_form_' . $form_id . '">';
                            $ref_content .= '<div class="arm_setup_messages arm_form_message_container"></div>';
                            $ref_content .= '<div class="armclear"></div>';
                            $ref_content .='<form method="post" name="arm_form" id="arm_referrals_form_' . $setupRandomID . '" class="arm_referrals_form_id arm_setup_form_' . $form_id . ' arm_referrals_setup_form  ' . $is_form_class_rtl . '" enctype="multipart/form-data" data-random-id="' . $setupRandomID . '"  novalidate ' . $form_attr . '>';
                            
                            $ref_content .='<div class="arm_module_gateway_fields arm_module_gateway_fields arm_member_form_container">';
                            $ref_content .='<div class="' . $form_style_class . '" data-ng-cloak="">';
                            $ref_content.='<div class="arm_update_card_form_heading_container armalign' . $form_title_position . '">';
                            $ref_content .='<span class="arm_form_field_label_wrapper_text">'.esc_html__($arm_referrals_form_title, 'ARM_AFFILIATE').'</span>';
                            $ref_content .='</div>';

                            $type ='referrals';
                            $fieldtable = '';
                            $arm_referrals_fields_array = array('email_label');
                            foreach ($arm_referrals_fields_array as $key) {
                                $fieldLabel = $fieldClass = $fieldAttr = $validation = $fieldDesc = '';
                                switch ($key) {
                                    case 'email_label':
                                        $fieldLabel = $atts['email_label'];
                                        $fieldAttr = 'name="' . $type . '[' . $key . ']" data-ng-model="arm_form.referrals_email' . $type . '" ';
                                        $fieldAttr .= ' data-ng-required="isarmreferralsFormField(\'' . $type . '\')" data-msg-required="' . esc_html__('This field can not be left blank', 'ARM_AFFILIATE') . '"';  

                                        $fieldAttr .= 'onfocusout="ref_validate_field_len(this);"';
                                        //$fieldAttr .= 'onkeydown="return ref_validate_field_value(event,this);"';
                                        $fieldAttr .= 'data-arm_min_len_msg="'.esc_html__('This field required comma separate email.', 'ARM_AFFILIATE').'"';
                                        
                                        $fieldClass = ' referrals_email';
                                        $validation .= '<div data-ng-cloak data-ng-messages="arm_form[\'' . $type . '[' . $key . ']\'].$error" data-ng-show="arm_form[\'' . $type . '[' . $key . ']\'].$touched" class="arm_error_msg_box ng-cloak">';
                                        $ey_error =  esc_html__('This field should not be blank.', 'ARM_AFFILIATE');
                                        $validation .= '<div data-ng-message="required" class="arm_error_msg"><div class="arm_error_box_arrow"></div>' . $ey_error . '</div>';
                                        $validation .= '</div>';
                                        $current_site_url = site_url();
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
                                $fieldtable .="<div class='arm_label_input_separator'></div>";
                                $fieldtable .="<div class='arm_form_input_wrapper' >";
                                $fieldtable .="<div class='arm_form_input_container arm_form_input_container_".$key."' >";
                                $fieldtable .="<md-input-container class='md-block' flex-gt-sm=''>
                                             <label class='arm_material_label' for='arm_".$key."'>".$fieldLabel."</label>
                                                <input type='text' class='field_".$type." ".$fieldClass."' 
                                                 value=''  id='arm_".$key."' ".$fieldAttr.">
                                                ".$validation."
                                            </md-input-container>";
                                $fieldtable .="</div>";
                                $fieldtable .="</div>";
                                $fieldtable .="</div>";
                                

                            }

                        $ref_content .= '<div class="arm_form_inner_container arm_msg_pos_' . $errPos . '">';
                        $ref_content .= '<div class="arm_cc_fields_container arm_' . $type . '_fields arm_form_wrapper_container arm_field_position_' . $fieldPosition . '" >';
                        $ref_content .= '<span class="payment-errors"></span>';
                        $ref_content .= $fieldtable;
                        $ref_content .= '</div>';
                        $ref_content .= '<div class="armclear"></div>';
                        $ref_content .= '</div>';

                        

                        $ref_content .='<div class="armclear"></div>';
                        $ref_content .='<div class="arm_form_field_container arm_form_field_container_submit">';
                        $ref_content .='<div class="arm_label_input_separator"></div>';
                        $ref_content .='<div class="arm_form_label_wrapper arm_form_field_label_wrapper arm_form_member_field_submit"></div>';
                        $ref_content .='<div class="arm_form_input_wrapper">';
                        $ref_content .='<div class="arm_form_input_container_submit arm_form_input_container" id="arm_referrals_form' . $form_id . '">';
                        $ngClick = 'ng-click="armreferralsSubmitBtnClick($event)"';
                        if (current_user_can('administrator')) {
                            $ngClick = 'onclick="return false;"';
                        }
                        $ref_content .='<md-button type="submit" name="arm_referrals_invite" class="arm_form_field_submit_button arm_form_field_container_button arm_form_input_box arm_material_input ' . $btn_style_class . '"  ' . $ngClick . ' id="arm_referrals_invite_submit"><span class="arm_spinner">' . file_get_contents(MEMBERSHIP_IMAGES_DIR . "/loader.svg") . '</span>'.$atts["submit_button_text"].'</md-button>';
                        $ref_content .='<input type="hidden" name="success_msg_hidden" id="success_msg_hidden" value="'.$atts["success_msg"].'">';

                        $ref_content .='</div>';
                        $ref_content .='</div>';
                        $ref_content .='<div class="armclear" data-ng-init="armreferralsForm(\'' . $type . '\');"></div>';
                        $ref_content .='</div>';
                        $ref_content .='</div>';
                        $ref_content .='</form>';
                        $ref_content .='</div>';
                        $ref_content .='</div>';    
                        $ref_content .='</div>';
                    $ref_content .='</div>';
                }
            }
            return $ref_content;
        }
        function date_convert_db_formate($date)
        {
            global $arm_global_settings;
            if( $arm_global_settings->arm_get_wp_date_format() == 'd/m/Y')
            {
                $date = isset($date) ? str_replace('/', '-', $date) : '';
            }
            
            //$date = str_replace('/', '-', $date);
            return  date('Y-m-d', strtotime($date));
        }

        function arm_refferal_bulk_action(){
            global $wpdb, $ARMember, $arm_members_class, $arm_global_settings, $arm_subscription_plans, $arm_case_types, $arm_affiliate;
            if (!isset($_POST)) {
                    return;
            }

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_referral';
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
                    if (!current_user_can('arm_affiliate_referral')) {
                        $errors[] = __('Sorry, You do not have permission to perform this action', 'ARM_AFFILIATE');
                    } else {
                        if (is_array($ids)) {
                            $ids = implode(',',$ids);
                            $delete_referral = $wpdb->query( "DELETE FROM `$arm_affiliate->tbl_arm_aff_referrals` WHERE arm_referral_id IN (".$ids.")");
                            $message = __('Referral(s) has been deleted successfully.', 'ARM_AFFILIATE');
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
        
        function arm_referrals_grid_data() {
            global $wpdb, $arm_affiliate, $ARMember, $arm_slugs, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_subscription_plans, $arm_payment_gateways, $arm_default_user_details_text;

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_referral';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $arm_currency = $arm_payment_gateways->arm_get_global_currency();
            $date_format = $arm_global_settings->arm_get_wp_date_format() . " " . get_option('time_format');

            $armaffiliate_active_woocommerce = $arm_affiliate->arm_affiliate_is_woocommerce_active();

            $nowDate = current_time('mysql');
            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans();
            $grid_columns = array(
                'affiliate_user' => __('Affiliate User', 'ARM_AFFILIATE'),
                'amount' => __('Commission', 'ARM_AFFILIATE'),
                'plan_name' => __('Membership Plan', 'ARM_AFFILIATE'),
                'reference_user' => __('Referred User', 'ARM_AFFILIATE'),
                'date' => __('Referral Date', 'ARM_AFFILIATE'),
                'status' => __('Status', 'ARM_AFFILIATE')
            );

            if($armaffiliate_active_woocommerce){
                $grid_columns = array(
                    'affiliate_user' => __('Affiliate User', 'ARM_AFFILIATE'),
                    'amount' => __('Commission', 'ARM_AFFILIATE'),
                    'plan_name' => __('Membership Plan', 'ARM_AFFILIATE'),
                    'reference_user' => __('Referred User', 'ARM_AFFILIATE'),
                    'order_id' => __('Order', 'ARM_AFFILIATE'),
                    'date' => __('Referral Date', 'ARM_AFFILIATE'),
                    'status' => __('Status', 'ARM_AFFILIATE')
                );
            }

            $tmp_query = "SELECT * FROM {$arm_affiliate->tbl_arm_aff_referrals}";
            $form_result = $wpdb->get_results($tmp_query);
            $total_before_filter = count($form_result);
            
            $where_condition = '';
            
            $sSearch = isset($_REQUEST['sSearch']) ? $_REQUEST['sSearch'] : '';
            if($sSearch != '')
            { $where_condition.= " AND u.user_login LIKE '%{$sSearch}%'"; }
            
            $filter_plan_id = (!empty($_REQUEST['filter_plan_id']) && $_REQUEST['filter_plan_id'] != '0') ? $_REQUEST['filter_plan_id'] : '';
            if($filter_plan_id != '')
            { $where_condition.= " AND r.arm_plan_id IN (".$filter_plan_id.")"; }
            
            $filter_status_id = isset($_REQUEST['filter_status_id']) ? $_REQUEST['filter_status_id'] : '';
            if($filter_status_id != '')
            { $where_condition.= " AND r.arm_status IN (".$filter_status_id.")"; }
            
            $start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : '';
            $end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : '';
            if (!empty($start_date)) {
                $start_datetime = $this->date_convert_db_formate($start_date)." 00:00:00";
                if (!empty($end_date)) {
                    $end_datetime = $this->date_convert_db_formate($end_date)." 23:59:59";
                    if ($start_datetime > $end_datetime) {
                        $end_datetime = $this->date_convert_db_formate($start_date)." 00:00:00";
                        $start_datetime = $this->date_convert_db_formate($end_date)." 23:59:59";
                    }
                    $where_condition .= " AND (r.arm_date_time BETWEEN '$start_datetime' AND '$end_datetime') ";
                } else {
                    $where_condition .= " AND (r.arm_date_time > '$start_datetime') ";
                }
            } else {
                if (!empty($end_date)) {
                    $end_datetime = $this->date_convert_db_formate($end_date);  
                    $where_condition .= " AND (r.arm_date_time < '$end_datetime') ";
                }
            }
            
            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 5;
            $order_by = 'r.arm_date_time';
            if( $sorting_col == 1 ) {
                $order_by = 'u.user_login';
            } else if( $sorting_col == 2 ){
                $order_by = 'r.arm_amount';
            }
            
            $grid_data = array();
            $ai = 0;
            $user_table = $wpdb->users;
            $tmp_query = "SELECT r.*, u.user_login FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r "
                        ." LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff "
                        ." ON aff.arm_affiliate_id = r.arm_affiliate_id "
                        ." LEFT JOIN `{$user_table}` u "
                        ." ON u.ID = aff.arm_user_id "
                        ." WHERE 1=1 "
                        .$where_condition
                        ." ORDER BY {$order_by} {$sorting_ord}";
                        
            $form_result = $wpdb->get_results($tmp_query);
            $total_after_filter = count($form_result);

            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            
            $tmp_query = $tmp_query . " LIMIT {$offset},{$number}";
                        
            $form_result = $wpdb->get_results($tmp_query);
            
            foreach ($form_result as $referral) {
                $arm_referral_id = $referral->arm_referral_id;
                $arm_affiliate_id = $referral->arm_affiliate_id;
                $arm_plan_id = $referral->arm_plan_id;
                $arm_ref_affiliate_id = $referral->arm_ref_affiliate_id;
                $arm_status = $referral->arm_status;
                $arm_amount = $referral->arm_amount;
                //$arm_currency = $referral->arm_currency;

                $arm_date_time = date( $date_format, strtotime( $referral->arm_date_time ) );

                $arm_affiliate_user_name = !empty($referral->user_login) ? $referral->user_login : $arm_default_user_details_text;
                
                $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($arm_plan_id);
                $plan_name = (!empty($plan_name)) ? $plan_name : '-';
                
                $arm_get_ref_affiliate_user_data = get_userdata($arm_ref_affiliate_id);
                $arm_ref_affiliate_user_name = isset($arm_get_ref_affiliate_user_data->user_login) ? $arm_get_ref_affiliate_user_data->user_login : '';
                if(empty($arm_ref_affiliate_user_name))
                {
                    $arm_ref_affiliate_user_name = $arm_default_user_details_text;
                }
                
                $grid_data[$ai][0] = "<input id=\"cb-item-action-{$arm_referral_id}\" class=\"chkstanard\" type=\"checkbox\" value=\"{$arm_referral_id}\" name=\"item-action[]\">";
                $grid_data[$ai][1] = $arm_affiliate_user_name;
                $grid_data[$ai][2] = $arm_payment_gateways->arm_amount_set_separator($arm_currency, $arm_amount)." ".$arm_currency;
                $grid_data[$ai][3] = $plan_name;
                $grid_data[$ai][4] = $arm_ref_affiliate_user_name;

                $ni = 4;

                if($armaffiliate_active_woocommerce){
                    $armaff_order_id = $referral->arm_woo_order;

                    $armaff_order_link = $armaff_order_id;
                    if($armaff_order_id != ''){
                        $armaff_order_url = get_edit_post_link( $armaff_order_id );
                        if($armaff_order_url != ''){
                            $armaff_order_link = '<a href="' . esc_url( $armaff_order_url ) . '">' . $armaff_order_id . '</a>';
                        }
                    }
                    $ni = 5;
                    $grid_data[$ai][$ni] = $armaff_order_link;
                }


                $grid_data[$ai][$ni + 1] = $arm_date_time;
                $grid_data[$ai][$ni + 2] = '<span class="arm_item_status_text_transaction ' . $this->referral_status[$arm_status] . '">'.ucfirst($this->referral_status[$arm_status]).'</span>';
                $arm_amount = number_format($arm_amount,2);
                $gridAction = "<div class='arm_grid_action_btn_container'>";
                                if($arm_status == 0 || $arm_status == 3){
                                    $accept_link = admin_url('admin.php?page=' . $arm_slugs->manage_members . '&action=accept&id=' . $arm_referral_id);
                                    $gridAction .= "<a href='javascript:void(0)' onclick='referrals_action({$arm_referral_id}, \"0\");' class='armhelptip' title='" . __('Accept Referral', 'ARM_AFFILIATE') . "' ><img src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_approved.png' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_approved_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_approved.png';\" /></a>";
                                }
                                if($arm_status == 0 || $arm_status == 1){
                                    $reject_link = admin_url('admin.php?page=' . $arm_slugs->manage_members . '&action=rejected&id=' . $arm_referral_id);
                                    $gridAction .= "<a href='javascript:void(0)' onclick='referrals_action({$arm_referral_id}, \"3\");' class='armhelptip' title='" . __('Reject Referral', 'ARM_AFFILIATE') . "' ><img src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_denied.png' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_denied_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_denied.png';\" /></a>";
                                }
                                
                                if($arm_status != 2){
                                    $gridAction .= "<a href='javascript:void(0)' onclick='arm_referral_edit({$arm_referral_id}, \"{$arm_affiliate_user_name}\", \"{$arm_ref_affiliate_user_name}\", \"{$plan_name}\", \"{$arm_amount}\")' class='armhelptip' title='" . __('Edit Referral Amount', 'ARM_AFFILIATE') . "' ><img src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit.png' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit.png';\" /></a>";
                                }
                                
                                    $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$arm_referral_id});'><img src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete.png' class='armhelptip' title='" . __('Delete', 'ARM_AFFILIATE') . "' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_delete.png';\" /></a>";
                                    $gridAction .= $arm_global_settings->arm_get_confirm_box($arm_referral_id, __("Are you sure you want to delete this referral?", 'ARM_AFFILIATE'), 'arm_aff_refferal_delete_btn');
                                $gridAction .= "</div>";
                $grid_data[$ai][$ni + 3] = $gridAction;
             
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
        
        function arm_referral_ajax_action() {
            global $wpdb, $arm_affiliate, $arm_aff_payouts, $arm_manage_communication, $ARMember;
            if (!isset($_POST)) {
                    return;
            }

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_referral';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $response = array( 'type' => 'error', 'msg' => __( 'Something went wrong in perform action', 'ARM_AFFILIATE' ) );
            $action = $_POST['act'];
            $id = $_POST['id'];
            if ($action == 'delete') {
                $delete_referral = $wpdb->query( "DELETE FROM `$arm_affiliate->tbl_arm_aff_referrals` WHERE arm_referral_id = ".$id );
                $response = array( 'type' => 'success', 'msg' => __( 'Referral is deleted successfully.', 'ARM_AFFILIATE' ) );
            } else if ( $action == 'accept' ) {
                $delete_referral = $wpdb->query( "UPDATE `$arm_affiliate->tbl_arm_aff_referrals` SET arm_status=1 WHERE arm_referral_id = ".$id );
                $response = array( 'type' => 'success', 'msg' => __( 'Referral is accepted successfully.', 'ARM_AFFILIATE' ) );
            } else if ( $action == 'reject' ){
                $delete_referral = $wpdb->query( "UPDATE `$arm_affiliate->tbl_arm_aff_referrals` SET arm_status=3 WHERE arm_referral_id = ".$id );
                $response = array( 'type' => 'success', 'msg' => __( 'Referral is rejected successfully.', 'ARM_AFFILIATE' ) );
            } 
            echo json_encode($response);
            die;
        }
        
        function arm_referral_edit() {
            global $wpdb, $arm_affiliate, $ARMember;

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_referral';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }
            if (!current_user_can('arm_affiliate')) {
                $response = array( 'type' => 'success', 'msg'=> __( 'Sorry, You do not have permission to perform this action', 'ARM_AFFILIATE' ) );
            } else {
                $post_data = $_POST;
                $arm_amount = number_format($post_data['arm_referral_amount'], 2);
                $wpdb->update(
                        $arm_affiliate->tbl_arm_aff_referrals, 
                        array( 'arm_amount' => $arm_amount, ), 
                        array( 'arm_referral_id' => $post_data['arm_referral_id'] ), 
                        array( '%s' ), 
                        array( '%d' ) 
                );

                $response = array( 'type' => 'success', 'msg'=> __( 'Affiliate user saved successfully', 'ARM_AFFILIATE' ) );
            }
            echo json_encode($response);
            die;
        }
        
        function get_refferal_using_id( $id ) {
            global $wpdb, $arm_affiliate;
            if(isset($id) && $id != '' && $id != 0)
            {
                $tmp_query = "SELECT * FROM {$arm_affiliate->tbl_arm_aff_referrals} WHERE arm_referral_id = {$id}";
                return $wpdb->get_row($tmp_query, 'OBJECT');
            }
        }
        
        function add_affiliate_in_arm_entries( $entry_post_data ) {
            global $arm_affiliate;
            $entry_post_data['arm_ref_affiliate_id'] = $arm_affiliate->get_arm_aff_cookie();
	    return $entry_post_data;
        }

        function arm_aff_add_referral( $user_id, $posted_data ) {
            global $wpdb, $arm_affiliate, $ARMember, $arm_affiliate_settings, $arm_payment_gateways;
            
            $ref_affiliate_id = $arm_affiliate->get_arm_aff_cookie();                
            if(($ref_affiliate_id <= 0 ) && isset($posted_data['arm_ref_affiliate_id'])){
                $ref_affiliate_id = isset($posted_data['arm_ref_affiliate_id']) ? $posted_data['arm_ref_affiliate_id'] : 0 ;
            }

            if($this->arm_check_is_allowed_affiliate($ref_affiliate_id)){
                
                $plan_id = isset( $posted_data['subscription_plan'] ) ? $posted_data['subscription_plan'] : 0;
                if ( $plan_id == 0 ) {
                    $plan_id = isset($posted_data['_subscription_plan']) ? $posted_data['_subscription_plan'] : 0;
                }
                
                $plan_amount = isset($posted_data['arm_total_payable_amount']) ? $posted_data['arm_total_payable_amount'] : 0;
                if((!isset($posted_data['arm_total_payable_amount']) || $plan_amount <= 0) && $plan_id > 0 ){
                    $plan_table = $ARMember->tbl_arm_subscription_plans;
                    $plan_data = $wpdb->get_row($wpdb->prepare("SELECT `arm_subscription_plan_id`, `arm_subscription_plan_name`, `arm_subscription_plan_amount` FROM `{$plan_table}` WHERE `arm_subscription_plan_id` = %d ", $plan_id), OBJECT);
                    $plan_amount = $plan_data->arm_subscription_plan_amount;
                }

                $this->arm_affiliate_add_referral( $ref_affiliate_id, $plan_id, $user_id, $plan_amount );
            }
        }
        
        function arm_aff_add_referral_transaction($log_data) {             
            if( (isset($log_data['arm_transaction_status']) && $log_data['arm_transaction_status'] == 'success') || isset($log_data['arm_bank_name']) ) {
                global $wpdb, $arm_affiliate, $ARMember, $arm_affiliate_settings, $arm_payment_gateways;
                $user_id = isset($log_data['arm_user_id']) ? $log_data['arm_user_id'] : 0;
                if($user_id == 0){ return; }
                
                $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                $arm_aff_allow_duplicate_referrals = isset($affiliate_options['arm_aff_allow_duplicate_referrals']) ? $affiliate_options['arm_aff_allow_duplicate_referrals'] : 0;
                $entry_id = get_user_meta($user_id, 'arm_entry_id');
                
                $arm_tbl_entry = $ARMember->tbl_arm_entries;
                $entry_data_value = $wpdb->get_row($wpdb->prepare("SELECT `arm_entry_value` FROM `{$arm_tbl_entry}` WHERE `arm_user_id` = %d AND `arm_entry_id` = %d ", $user_id, $entry_id[0]), ARRAY_A);
                $entry_data = maybe_unserialize($entry_data_value['arm_entry_value']);
                
                $ref_affiliate_id = $arm_affiliate->get_arm_aff_cookie();                
                if(($ref_affiliate_id <= 0 ) && isset($entry_data['arm_ref_affiliate_id'])){
                    $ref_affiliate_id = isset($entry_data['arm_ref_affiliate_id']) ? $entry_data['arm_ref_affiliate_id'] : 0 ;
                }
                
                if ($arm_aff_allow_duplicate_referrals == 0) {
                    $arm_tbl_referral = $arm_affiliate->tbl_arm_aff_referrals;
                    $referral_data = $wpdb->get_row($wpdb->prepare("SELECT count(arm_referral_id) as referral_count FROM `{$arm_tbl_referral}` WHERE `arm_affiliate_id` = %d AND `arm_ref_affiliate_id` = %d ", $ref_affiliate_id, $user_id));
                    
                    if( $referral_data->referral_count > 0 ) {
                        return; 
                    }
                }
                
                if($this->arm_check_is_allowed_affiliate($ref_affiliate_id)){
                    $plan_id = isset( $log_data['arm_plan_id'] ) ? $log_data['arm_plan_id'] : 0;
                    if ( $plan_id == 0 ) {
                        $plan_id = isset($entry_data['_subscription_plan']) ? $entry_data['_subscription_plan'] : 0;
                    }
                    
                    $this->arm_affiliate_add_referral( $ref_affiliate_id, $plan_id, $user_id, $log_data['arm_amount'] );
                }
            }
        }

        function arm_affiliate_add_referral( $ref_affiliate_id, $plan_id, $user_id, $payment_amount ){
            global $wpdb, $ARMember, $arm_payment_gateways, $arm_affiliate, $arm_manage_communication, $arm_affiliate_settings, $arm_affiliate_commision_setup, $armaff_rate_type_arr;

            if($plan_id > 0 && $plan_id != ''){
                $plan_table = $ARMember->tbl_arm_subscription_plans;
                $plan_data = $wpdb->get_row($wpdb->prepare("SELECT `arm_subscription_plan_id`, `arm_subscription_plan_options` FROM `{$plan_table}` WHERE `arm_subscription_plan_id` = %d ", $plan_id), OBJECT );
                $arm_subscription_plan_options = maybe_unserialize(isset($plan_data->arm_subscription_plan_options) ? $plan_data->arm_subscription_plan_options : '');
                
                $referral_disable = isset($arm_subscription_plan_options['arm_affiliate_referral_disable']) ? $arm_subscription_plan_options['arm_affiliate_referral_disable'] : 0;
                $referral_type = isset($arm_subscription_plan_options['arm_affiliate_referral_type']) ? $arm_subscription_plan_options['arm_affiliate_referral_type'] : 0;
                $referral_rate = isset($arm_subscription_plan_options['arm_affiliate_referral_rate']) ? $arm_subscription_plan_options['arm_affiliate_referral_rate'] : 0;

                /* GET COMMISSION FOR PARTICULAR AFFILIATE USER */
                $armaff_affiliate_commision = $arm_affiliate_commision_setup->armaff_get_commision_for_affiliate_user($ref_affiliate_id);
                if(!empty($armaff_affiliate_commision)){
                    $referral_type = isset($armaff_affiliate_commision['armaff_referral_type']) ? $armaff_affiliate_commision['armaff_referral_type'] : 0;
                    $referral_type = $armaff_rate_type_arr[$referral_type]['slug'];
                    $referral_rate = isset($armaff_affiliate_commision['armaff_referral_rate']) ? $armaff_affiliate_commision['armaff_referral_rate'] : 0;
                    $referral_disable = 1;
                }

                if($referral_disable == 1)
                {
                    $referral_amount = 0;

                    if($referral_type == 'fixed_rate')
                    {
                        $referral_amount = $referral_rate;
                    }
                    else if($referral_type == 'percentage')
                    {
                        $referral_amount = ($payment_amount * $referral_rate) / 100;
                    }
                    $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                    $is_allowed_amount = ( isset($affiliate_options['arm_aff_not_allow_zero_commision']) && $affiliate_options['arm_aff_not_allow_zero_commision'] == 1 && $referral_amount <= 0 ) ? false : true;
                    $nowDate = current_time('mysql');
                    $currency = $arm_payment_gateways->arm_get_global_currency();
                    $arm_aff_default_refferal_status = isset($affiliate_options['arm_aff_referral_status']) ? $affiliate_options['arm_aff_referral_status'] :  0 ;
                    

                    if( $is_allowed_amount ){
                        $arm_aff_referrals_values = array(
                            'arm_affiliate_id' => $ref_affiliate_id,
                            'arm_plan_id' => $plan_id,
                            'arm_ref_affiliate_id' => $user_id,
                            'arm_status' => $arm_aff_default_refferal_status,
                            'arm_amount' => $referral_amount,
                            'arm_currency' => $currency,
                            'arm_revenue_amount' => $payment_amount,
                            'arm_date_time' => $nowDate
                        );
                        $wpdb->insert($arm_affiliate->tbl_arm_aff_referrals, $arm_aff_referrals_values);

                        $ref_id = $wpdb->insert_id;
                        
                        if( isset($_COOKIE['arm_aff_ref_cookie']) && $_COOKIE['arm_aff_ref_cookie'] > 0 && isset($_COOKIE['visitor_id']) && $_COOKIE['visitor_id'] > 0 ) {
                             $wpdb->update( 
                                    $arm_affiliate->tbl_arm_aff_visitors, 
                                    array( 'arm_referral_id' => $ref_id ), 
                                    array( 'arm_visitor_id' => $_COOKIE['visitor_id'] ), 
                                    array( '%d' ), 
                                    array( '%d' ) 
                            );
                        }
                        
                        $arm_get_affiliate_details_row = $wpdb->get_row('SELECT arm_user_id FROM '.$arm_affiliate->tbl_arm_aff_affiliates.' WHERE arm_affiliate_id = '.$ref_affiliate_id, ARRAY_A);
                        $arm_get_affiliate_user_id = $arm_get_affiliate_details_row['arm_user_id'];
                        if(!empty($arm_get_affiliate_user_id))
                        {
                            $user_plans = get_user_meta($arm_get_affiliate_user_id, 'arm_user_plan_ids', true);
                            $user_plans = maybe_unserialize($user_plans);
                            $aff_user_plan_ids = "";
                            if(!empty($user_plans) && is_array($user_plans))
                            {
                                $aff_user_plan_ids = implode(',', $user_plans);
                            }
                            $arm_manage_communication->membership_communication_mail('arm_affiliate_notify_when_credited', $arm_get_affiliate_user_id, $aff_user_plan_ids );
                        }

                        
                        $arm_manage_communication->membership_communication_mail('arm_admin_when_user_refer_to_other', $user_id, $plan_id );

                        update_user_meta( $user_id, 'arm_aff_referral_id', $ref_affiliate_id );
                    }
                }
            }
            else
            {
                $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                $referral_amount = isset($affiliate_options['arm_aff_referral_default_rate']) ? $affiliate_options['arm_aff_referral_default_rate'] : 0;

                $is_allowed_amount = ( isset($affiliate_options['arm_aff_not_allow_zero_commision']) && $affiliate_options['arm_aff_not_allow_zero_commision'] == 1 && $referral_amount <= 0 ) ? false : true;
                $nowDate = current_time('mysql');
                $currency = $arm_payment_gateways->arm_get_global_currency();
                $arm_aff_default_refferal_status = isset($affiliate_options['arm_aff_referral_status']) ? $affiliate_options['arm_aff_referral_status'] :  0 ;

                if( $is_allowed_amount ){
                    $arm_aff_referrals_values = array(
                        'arm_affiliate_id' => $ref_affiliate_id,
                        'arm_plan_id' => 0,
                        'arm_ref_affiliate_id' => $user_id,
                        'arm_status' => $arm_aff_default_refferal_status,
                        'arm_amount' => $referral_amount,
                        'arm_currency' => $currency,
                        'arm_date_time' => $nowDate
                    );
                    $wpdb->insert($arm_affiliate->tbl_arm_aff_referrals, $arm_aff_referrals_values);

                    $ref_id = $wpdb->insert_id;
                        
                    if( isset($_COOKIE['arm_aff_ref_cookie']) && $_COOKIE['arm_aff_ref_cookie'] > 0 && isset($_COOKIE['visitor_id']) && $_COOKIE['visitor_id'] > 0 ) {
                         $wpdb->update( 
                                $arm_affiliate->tbl_arm_aff_visitors, 
                                array( 'arm_referral_id' => $ref_id ), 
                                array( 'arm_visitor_id' => $_COOKIE['visitor_id'] ), 
                                array( '%d' ), 
                                array( '%d' ) 
                        );
                    }

                    $arm_get_affiliate_details_row = $wpdb->get_row('SELECT arm_user_id FROM '.$arm_affiliate->tbl_arm_aff_affiliates.' WHERE arm_affiliate_id = '.$ref_affiliate_id, ARRAY_A);
                    $arm_get_affiliate_user_id = $arm_get_affiliate_details_row['arm_user_id'];
                    if(!empty($arm_get_affiliate_user_id))
                    {
                        $user_plans = get_user_meta($arm_get_affiliate_user_id, 'arm_user_plan_ids', true);
                        $user_plans = maybe_unserialize($user_plans);
                        $aff_user_plan_ids = "";
                        if(!empty($user_plans) && is_array($user_plans))
                        {
                            $aff_user_plan_ids = implode(',', $user_plans);
                        }
                        $arm_manage_communication->membership_communication_mail('arm_affiliate_notify_when_credited', $arm_get_affiliate_user_id, $aff_user_plan_ids );
                    }

                    $arm_manage_communication->membership_communication_mail('arm_admin_when_user_refer_to_other', $user_id, $plan_id );

                    update_user_meta( $user_id, 'arm_aff_referral_id', $ref_affiliate_id );
                }
            }
        }


        function arm_aff_add_referral_2_1( $user_id, $posted_data ) {
            global $wpdb, $arm_affiliate, $ARMember, $arm_affiliate_settings, $arm_payment_gateways,$arm_manage_coupons;

            $ref_affiliate_id = $arm_affiliate->get_arm_aff_cookie();
            if(($ref_affiliate_id <= 0 ) && isset($posted_data['arm_ref_affiliate_id'])){
                $ref_affiliate_id = isset($posted_data['arm_ref_affiliate_id']) ? $posted_data['arm_ref_affiliate_id'] : 0 ;
            }
            if(empty($ref_affiliate_id) && !empty($posted_data['arm_coupon_code'])){
                $coupon_code=$posted_data['arm_coupon_code'];
                $couponData = $arm_manage_coupons->arm_get_coupon($coupon_code);
                if(!empty($couponData['arm_coupon_aff_user'])){
                    $ref_affiliate_id=$couponData['arm_coupon_aff_user'];
                }
            }
            if($this->arm_check_is_allowed_affiliate($ref_affiliate_id)){

                $plan_id = isset( $posted_data['subscription_plan'] ) ? $posted_data['subscription_plan'] : 0;
                if ( $plan_id == 0 ) {
                    $plan_id = isset($posted_data['_subscription_plan']) ? $posted_data['_subscription_plan'] : 0;
                }

                $armaff_pgateway = isset($posted_data['payment_gateway']) ? $posted_data['payment_gateway'] : '';
                if ($armaff_pgateway == '') {
                    $armaff_pgateway = isset($posted_data['_payment_gateway']) ? $posted_data['_payment_gateway'] : '';
                }

                $plan_table = $ARMember->tbl_arm_subscription_plans;
                $plan_data = $wpdb->get_row($wpdb->prepare("SELECT `arm_subscription_plan_id`, `arm_subscription_plan_name`, `arm_subscription_plan_amount`, `arm_subscription_plan_options` FROM `{$plan_table}` WHERE `arm_subscription_plan_id` = %d ", $plan_id), OBJECT);
                $plan_amount = $plan_data->arm_subscription_plan_amount;

                $armplan_options = @unserialize($plan_data->arm_subscription_plan_options);

                if( isset($armplan_options['payment_type']) && $armplan_options['payment_type'] == "subscription" ){

                    $arm_selected_payment_cycle = isset($posted_data['arm_selected_payment_cycle']) ? $posted_data['arm_selected_payment_cycle'] : 0;
                    $plan_amount = $armplan_options['payment_cycles'][$arm_selected_payment_cycle]['cycle_amount'];

                    if(isset($posted_data['arm_is_user_logged_in_flag']) && $posted_data['arm_is_user_logged_in_flag'] == 0){
                        if( isset($armplan_options['trial']['is_trial_period']) && $armplan_options['trial']['is_trial_period'] == 1 ){
                            $plan_amount = $armplan_options['trial']['amount'];
                        }
                    }

                }

                if ($armaff_pgateway != '' && $plan_id > 0) {

                    $is_succeed_payment = 0;
                    if ($armaff_pgateway == 'bank_transfer') {
                        $plan_txn_id = isset($posted_data['bank_transfer']['transaction_id']) ? $posted_data['bank_transfer']['transaction_id'] : '';
                        $armaff_entry = $wpdb->get_row($wpdb->prepare("SELECT `arm_status` FROM `{$ARMember->tbl_arm_bank_transfer_log}` WHERE `arm_transaction_id` = %d ", $plan_txn_id), OBJECT);
                        if( isset($armaff_entry->arm_status) && $armaff_entry->arm_status == 1 ){
                            $is_succeed_payment = 1;
                        }
                    } else {
                        $armaff_entry = $wpdb->get_row($wpdb->prepare("SELECT `arm_transaction_status` FROM `{$ARMember->tbl_arm_payment_log}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d ORDER BY `arm_log_id` DESC LIMIT 1", $user_id, $plan_id), OBJECT);
                        if( isset($armaff_entry->arm_transaction_status) && $armaff_entry->arm_transaction_status == 'success' ){
                            $is_succeed_payment = 1;
                        }

                    }

                    if( empty($is_succeed_payment) || $is_succeed_payment != 1 ){
                        $armaff_referral_detail = array( 'ref_affiliate_id' => $ref_affiliate_id, 'plan_amount' => $plan_amount );
                        $armaff_referral_detail = @serialize($armaff_referral_detail);
                        update_user_meta( $user_id, 'arm_affiliate_add_referral_'.$plan_id, $armaff_referral_detail );
                        return;
                    }
                }

                $this->arm_affiliate_add_referral_2_1( $ref_affiliate_id, $plan_id, $user_id, $plan_amount );
            }
        }
        
        function arm_aff_add_referral_transaction_2_1($log_data) {

            if( (isset($log_data['arm_transaction_status']) && $log_data['arm_transaction_status'] == 'success') || isset($log_data['arm_bank_name']) ) {
                global $wpdb, $arm_affiliate, $ARMember, $arm_affiliate_settings, $arm_payment_gateways,$arm_manage_coupons;
                $user_id = isset($log_data['arm_user_id']) ? $log_data['arm_user_id'] : 0;

                if($user_id == 0){ return; }
                
                $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                $arm_aff_allow_duplicate_referrals = isset($affiliate_options['arm_aff_allow_duplicate_referrals']) ? $affiliate_options['arm_aff_allow_duplicate_referrals'] : 0;
                $entry_id = get_user_meta($user_id, 'arm_entry_id');
                
                $arm_tbl_entry = $ARMember->tbl_arm_entries;
                $entry_data_value = $wpdb->get_row($wpdb->prepare("SELECT `arm_entry_value` FROM `{$arm_tbl_entry}` WHERE `arm_user_id` = %d AND `arm_entry_id` = %d ", $user_id, $entry_id[0]), ARRAY_A);
                $entry_data = maybe_unserialize($entry_data_value['arm_entry_value']);
                
                $ref_affiliate_id = $arm_affiliate->get_arm_aff_cookie();
                if(($ref_affiliate_id <= 0 ) && isset($entry_data['arm_ref_affiliate_id'])){
                    $ref_affiliate_id = isset($entry_data['arm_ref_affiliate_id']) ? $entry_data['arm_ref_affiliate_id'] : 0 ;
                }

                if(empty($ref_affiliate_id) && !empty($entry_data['arm_coupon_code'])){
                    $coupon_code=$entry_data['arm_coupon_code'];
                    $couponData = $arm_manage_coupons->arm_get_coupon($coupon_code);
                    if(!empty($couponData['arm_coupon_aff_user'])){
                        $ref_affiliate_id=$couponData['arm_coupon_aff_user'];
                    }
                }

                if($this->arm_check_is_allowed_affiliate($ref_affiliate_id)){
                    $plan_id = isset( $log_data['arm_plan_id'] ) ? $log_data['arm_plan_id'] : 0;
                    if ( $plan_id == 0 ) {
                        $plan_id = isset($entry_data['_subscription_plan']) ? $entry_data['_subscription_plan'] : 0;
                    }

                    $armplan_options = @unserialize($log_data['arm_extra_vars']);
                    $plan_amount = $armplan_options['plan_amount'];

                    if( isset($log_data['arm_payment_type']) && $log_data['arm_payment_type'] == 'subscription' && isset($log_data['arm_is_trial']) && $log_data['arm_is_trial'] == 1 ){
                        $plan_amount = isset($armplan_options['trial']['amount']) ? $armplan_options['trial']['amount'] : $plan_amount;
                    }



                    $armaff_pgateway = isset($log_data['arm_payment_gateway']) ? $log_data['arm_payment_gateway'] : '';
                    if ($armaff_pgateway == '') {
                        $armaff_pgateway = isset($entry_data['payment_gateway']) ? $entry_data['payment_gateway'] : '';
                    }

                    $is_succeed_payment = 0;

                    if ($armaff_pgateway == 'bank_transfer') {
                        $plan_txn_id = isset($log_data['bank_transfer']['transaction_id']) ? $log_data['bank_transfer']['transaction_id'] : '';
                        $armaff_entry = $wpdb->get_row($wpdb->prepare("SELECT `arm_status` FROM `{$ARMember->tbl_arm_bank_transfer_log}` WHERE `arm_transaction_id` = %d ", $plan_txn_id), OBJECT);
                        if( isset($armaff_entry->arm_status) && $armaff_entry->arm_status == 1 ){
                            $is_succeed_payment = 1;
                        }
                    } else {
                        $armaff_entry = $wpdb->get_row($wpdb->prepare("SELECT `arm_transaction_status`, `arm_payment_mode` FROM `{$ARMember->tbl_arm_payment_log}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d ORDER BY `arm_log_id` DESC LIMIT 1", $user_id, $plan_id), OBJECT);
                        if( isset($armaff_entry->arm_transaction_status) && $armaff_entry->arm_transaction_status == 'success' ){
                            $is_succeed_payment = 1;
                        }

                    }

                    if( empty($is_succeed_payment) || $is_succeed_payment != 1 ){
                        $armaff_referral_detail = array( 'ref_affiliate_id' => $ref_affiliate_id, 'plan_amount' => $plan_amount );
                        $armaff_referral_detail = @serialize($armaff_referral_detail);
                        update_user_meta( $user_id, 'arm_affiliate_add_referral_'.$plan_id, $armaff_referral_detail );
                        return;
                    }

                    $armaff_is_recurring_payment = $this->armaff_is_plan_recurring($user_id, $plan_id, $armaff_pgateway);

                    if( $armaff_is_recurring_payment == 1 ){
                        $this->arm_affiliate_add_recurring_referral( $ref_affiliate_id, $plan_id, $user_id, $plan_amount );
                    } else {

                        if ($arm_aff_allow_duplicate_referrals == 0) {
                            $arm_tbl_referral = $arm_affiliate->tbl_arm_aff_referrals;
                            $referral_data = $wpdb->get_row($wpdb->prepare("SELECT count(arm_referral_id) as referral_count FROM `{$arm_tbl_referral}` WHERE `arm_affiliate_id` = %d AND `arm_ref_affiliate_id` = %d ", $ref_affiliate_id, $user_id));
                            if( $referral_data->referral_count > 0 ) {
                                return; 
                            }
                        }

                        $this->arm_affiliate_add_referral_2_1( $ref_affiliate_id, $plan_id, $user_id, $plan_amount );
                    }

                }
            }
        }
        function arm_aff_add_free_plan_referral_transaction($log_data){

            if(isset($log_data['arm_transaction_status']) && $log_data['arm_transaction_status'] == 'success') {
                global $wpdb, $arm_affiliate, $ARMember, $arm_affiliate_settings, $arm_payment_gateways;
                $user_id = isset($log_data['arm_user_id']) ? $log_data['arm_user_id'] : 0;

                if($user_id == 0){ return; }
                
                $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                $arm_aff_allow_duplicate_referrals = isset($affiliate_options['arm_aff_allow_duplicate_referrals']) ? $affiliate_options['arm_aff_allow_duplicate_referrals'] : 0;
                $entry_id = get_user_meta($user_id, 'arm_entry_id');
                
                $arm_tbl_entry = $ARMember->tbl_arm_entries;
                $entry_data_value = $wpdb->get_row($wpdb->prepare("SELECT `arm_entry_value` FROM `{$arm_tbl_entry}` WHERE `arm_user_id` = %d AND `arm_entry_id` = %d ", $user_id, $entry_id[0]), ARRAY_A);
                $entry_data = maybe_unserialize($entry_data_value['arm_entry_value']);
                
                $ref_affiliate_id = $arm_affiliate->get_arm_aff_cookie();
                if(($ref_affiliate_id <= 0 ) && isset($entry_data['arm_ref_affiliate_id'])){
                    $ref_affiliate_id = isset($entry_data['arm_ref_affiliate_id']) ? $entry_data['arm_ref_affiliate_id'] : 0 ;
                }
                if($this->arm_check_is_allowed_affiliate($ref_affiliate_id)){
                    $plan_id = isset( $log_data['arm_plan_id'] ) ? $log_data['arm_plan_id'] : 0;
                    if ( $plan_id == 0 ) {
                        $plan_id = isset($entry_data['_subscription_plan']) ? $entry_data['_subscription_plan'] : 0;
                    }
                    $this->arm_affiliate_add_referral_2_1( $ref_affiliate_id, $plan_id, $user_id, $plan_amount=0);
                }
            }   
            
        }
        function arm_affiliate_add_referral_2_1( $ref_affiliate_id, $plan_id, $user_id, $payment_amount ){
            global $wpdb, $ARMember, $arm_payment_gateways, $arm_affiliate, $arm_manage_communication, $arm_affiliate_settings, $arm_affiliate_commision_setup, $armaff_rate_type_arr;

            if($plan_id > 0 && $plan_id != ''){
                $plan_table = $ARMember->tbl_arm_subscription_plans;
                $plan_data = $wpdb->get_row($wpdb->prepare("SELECT `arm_subscription_plan_id`, `arm_subscription_plan_options` FROM `{$plan_table}` WHERE `arm_subscription_plan_id` = %d ", $plan_id), OBJECT );
                $arm_subscription_plan_options = maybe_unserialize(isset($plan_data->arm_subscription_plan_options) ? $plan_data->arm_subscription_plan_options : '');
                
                $referral_disable = isset($arm_subscription_plan_options['arm_affiliate_referral_disable']) ? $arm_subscription_plan_options['arm_affiliate_referral_disable'] : 0;
                $referral_type = isset($arm_subscription_plan_options['arm_affiliate_referral_type']) ? $arm_subscription_plan_options['arm_affiliate_referral_type'] : 0;
                $referral_rate = isset($arm_subscription_plan_options['arm_affiliate_referral_rate']) ? $arm_subscription_plan_options['arm_affiliate_referral_rate'] : 0;

                /* GET COMMISSION FOR PARTICULAR AFFILIATE USER */
                $armaff_affiliate_commision = $arm_affiliate_commision_setup->armaff_get_commision_for_affiliate_user($ref_affiliate_id);
                if(!empty($armaff_affiliate_commision)){
                    $referral_type = isset($armaff_affiliate_commision['armaff_referral_type']) ? $armaff_affiliate_commision['armaff_referral_type'] : 0;
                    $referral_type = $armaff_rate_type_arr[$referral_type]['slug'];
                    $referral_rate = isset($armaff_affiliate_commision['armaff_referral_rate']) ? $armaff_affiliate_commision['armaff_referral_rate'] : 0;
                    $referral_disable = 1;
                }

                if($referral_disable == 1)
                {
                    $referral_amount = 0;

                    if($referral_type == 'fixed_rate')
                    {
                        $referral_amount = $referral_rate;
                    }
                    else if($referral_type == 'percentage')
                    {
                        $referral_amount = ($payment_amount * $referral_rate) / 100;
                    }
                    $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                    $is_allowed_amount = ( isset($affiliate_options['arm_aff_not_allow_zero_commision']) && $affiliate_options['arm_aff_not_allow_zero_commision'] == 1 && $referral_amount <= 0 ) ? false : true;

                    $nowDate = current_time('mysql');
                    $currency = $arm_payment_gateways->arm_get_global_currency();
                    $arm_aff_default_refferal_status = isset($affiliate_options['arm_aff_referral_status']) ? $affiliate_options['arm_aff_referral_status'] :  0 ;
                    

                    if( $is_allowed_amount ){
                        $arm_aff_referrals_values = array(
                            'arm_affiliate_id' => $ref_affiliate_id,
                            'arm_plan_id' => $plan_id,
                            'arm_ref_affiliate_id' => $user_id,
                            'arm_status' => $arm_aff_default_refferal_status,
                            'arm_amount' => $referral_amount,
                            'arm_currency' => $currency,
                            'arm_revenue_amount' => $payment_amount,
                            'arm_date_time' => $nowDate
                        );
                        $wpdb->insert($arm_affiliate->tbl_arm_aff_referrals, $arm_aff_referrals_values);

                        $ref_id = $wpdb->insert_id;
                        
                        if( isset($_COOKIE['arm_aff_ref_cookie']) && $_COOKIE['arm_aff_ref_cookie'] > 0 && isset($_COOKIE['visitor_id']) && $_COOKIE['visitor_id'] > 0 ) {
                             $wpdb->update( 
                                    $arm_affiliate->tbl_arm_aff_visitors, 
                                    array( 'arm_referral_id' => $ref_id ), 
                                    array( 'arm_visitor_id' => $_COOKIE['visitor_id'] ), 
                                    array( '%d' ), 
                                    array( '%d' ) 
                            );
                        }
                        
                        $arm_get_affiliate_details_row = $wpdb->get_row('SELECT arm_user_id FROM '.$arm_affiliate->tbl_arm_aff_affiliates.' WHERE arm_affiliate_id = '.$ref_affiliate_id, ARRAY_A);
                        $arm_get_affiliate_user_id = $arm_get_affiliate_details_row['arm_user_id'];
                        if(!empty($arm_get_affiliate_user_id))
                        {
                            $user_plans = get_user_meta($arm_get_affiliate_user_id, 'arm_user_plan_ids', true);
                            $user_plans = maybe_unserialize($user_plans);
                            $aff_user_plan_ids = "";
                            if(!empty($user_plans) && is_array($user_plans))
                            {
                                $aff_user_plan_ids = implode(',', $user_plans);
                            }
                            $arm_manage_communication->membership_communication_mail('arm_affiliate_notify_when_credited', $arm_get_affiliate_user_id, $aff_user_plan_ids );
                        }

                        $arm_manage_communication->membership_communication_mail('arm_admin_when_user_refer_to_other', $user_id, $plan_id );

                        update_user_meta( $user_id, 'arm_aff_referral_id', $ref_affiliate_id );
                        update_user_meta( $user_id, 'arm_aff_referral_id_plan_'.$plan_id, $ref_affiliate_id );
                    }
                }
            }
            else
            {
                $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                $referral_amount = isset($affiliate_options['arm_aff_referral_default_rate']) ? $affiliate_options['arm_aff_referral_default_rate'] : 0;

                $is_allowed_amount = ( isset($affiliate_options['arm_aff_not_allow_zero_commision']) && $affiliate_options['arm_aff_not_allow_zero_commision'] == 1 && $referral_amount <= 0 ) ? false : true;
                $nowDate = current_time('mysql');
                $currency = $arm_payment_gateways->arm_get_global_currency();
                $arm_aff_default_refferal_status = isset($affiliate_options['arm_aff_referral_status']) ? $affiliate_options['arm_aff_referral_status'] :  0 ;

                if( $is_allowed_amount ){
                    $arm_aff_referrals_values = array(
                        'arm_affiliate_id' => $ref_affiliate_id,
                        'arm_plan_id' => 0,
                        'arm_ref_affiliate_id' => $user_id,
                        'arm_status' => $arm_aff_default_refferal_status,
                        'arm_amount' => $referral_amount,
                        'arm_currency' => $currency,
                        'arm_date_time' => $nowDate
                    );
                    $wpdb->insert($arm_affiliate->tbl_arm_aff_referrals, $arm_aff_referrals_values);

                    $ref_id = $wpdb->insert_id;
                        
                    if( isset($_COOKIE['arm_aff_ref_cookie']) && $_COOKIE['arm_aff_ref_cookie'] > 0 && isset($_COOKIE['visitor_id']) && $_COOKIE['visitor_id'] > 0 ) {
                         $wpdb->update( 
                                $arm_affiliate->tbl_arm_aff_visitors, 
                                array( 'arm_referral_id' => $ref_id ), 
                                array( 'arm_visitor_id' => $_COOKIE['visitor_id'] ), 
                                array( '%d' ), 
                                array( '%d' ) 
                        );
                    }

                    $arm_get_affiliate_details_row = $wpdb->get_row('SELECT arm_user_id FROM '.$arm_affiliate->tbl_arm_aff_affiliates.' WHERE arm_affiliate_id = '.$ref_affiliate_id, ARRAY_A);
                    $arm_get_affiliate_user_id = $arm_get_affiliate_details_row['arm_user_id'];
                    if(!empty($arm_get_affiliate_user_id))
                    {
                        $user_plans = get_user_meta($arm_get_affiliate_user_id, 'arm_user_plan_ids', true);
                        $user_plans = maybe_unserialize($user_plans);
                        $aff_user_plan_ids = "";
                        if(!empty($user_plans) && is_array($user_plans))
                        {
                            $aff_user_plan_ids = implode(',', $user_plans);
                        }
                        $arm_manage_communication->membership_communication_mail('arm_affiliate_notify_when_credited', $arm_get_affiliate_user_id, $aff_user_plan_ids );
                    }

                    $arm_manage_communication->membership_communication_mail('arm_admin_when_user_refer_to_other', $user_id, $plan_id );

                    update_user_meta( $user_id, 'arm_aff_referral_id', $ref_affiliate_id );
                }
            }

        }

        function arm_affiliate_add_recurring_referral_transaction( $user_id, $plan_id, $payment_gateway = '', $payment_mode = '', $user_subsdata = '' ){

            global $wpdb, $arm_affiliate, $ARMember, $arm_affiliate_settings;

            $is_succeed_payment = 0;

            if($user_id == 0 || $plan_id <= 0 || $plan_id == ''){
                return;
            }

            if( $payment_gateway == 'authorize.net'){
                $payment_gateway = 'authorize_net';
            }

            $entry_id = get_user_meta($user_id, 'arm_entry_id');

            $arm_tbl_entry = $ARMember->tbl_arm_entries;
            $entry_data_value = $wpdb->get_row($wpdb->prepare("SELECT `arm_entry_value` FROM `{$arm_tbl_entry}` WHERE `arm_entry_id` = %d ", $entry_id[0]), ARRAY_A);
            $entry_data = maybe_unserialize($entry_data_value['arm_entry_value']);

            if ($payment_gateway == 'bank_transfer') {
                $plan_txn_id = isset($entry_data['bank_transfer']['transaction_id']) ? $entry_data['bank_transfer']['transaction_id'] : '';
                $armaff_entry = $wpdb->get_row($wpdb->prepare("SELECT `arm_status` FROM `{$ARMember->tbl_arm_bank_transfer_log}` WHERE `arm_transaction_id` = %d ", $plan_txn_id), OBJECT);
                if( isset($armaff_entry->arm_status) && $armaff_entry->arm_status == 1 ){
                    $is_succeed_payment = 1;
                }
            } else {
                $armaff_entry = $wpdb->get_row($wpdb->prepare("SELECT `arm_transaction_status` FROM `{$ARMember->tbl_arm_payment_log}` WHERE `arm_user_id` = %d AND `arm_plan_id` = %d ORDER BY `arm_log_id` DESC LIMIT 1", $user_id, $plan_id), OBJECT);
                if( isset($armaff_entry->arm_transaction_status) && $armaff_entry->arm_transaction_status == 'success' ){
                    $is_succeed_payment = 1;
                }
            }

            $plan_table = $ARMember->tbl_arm_subscription_plans;
            $plan_data = $wpdb->get_row($wpdb->prepare("SELECT `arm_subscription_plan_amount`, `arm_subscription_plan_options` FROM `{$plan_table}` WHERE `arm_subscription_plan_id` = %d ", $plan_id), OBJECT);
            $payment_amount = $plan_data->arm_subscription_plan_amount;

            $armplan_options = @unserialize($plan_data->arm_subscription_plan_options);

            if( isset($armplan_options['payment_type']) && $armplan_options['payment_type'] == "subscription" ){

                $arm_selected_payment_cycle = isset($entry_data['arm_selected_payment_cycle']) ? $entry_data['arm_selected_payment_cycle'] : 0;
                $payment_amount = $armplan_options['payment_cycles'][$arm_selected_payment_cycle]['cycle_amount'];

                if(isset($entry_data['arm_is_user_logged_in_flag']) && $entry_data['arm_is_user_logged_in_flag'] == 0){
                    if( isset($armplan_options['trial']['is_trial_period']) && $armplan_options['trial']['is_trial_period'] == 1 ){
                        $payment_amount = $armplan_options['trial']['amount'];
                    }
                }

            }


            if( empty($is_succeed_payment) || $is_succeed_payment != 1){
                /*$ref_affiliate_id = get_user_meta($user_id, 'arm_aff_referral_id', true);
                if( ($ref_affiliate_id <= 0 || $ref_affiliate_id == '') && isset($entry_data['arm_ref_affiliate_id'])){
                    $ref_affiliate_id = isset($entry_data['arm_ref_affiliate_id']) ? $entry_data['arm_ref_affiliate_id'] : 0 ;
                }

                $armaff_referral_detail = array( 'ref_affiliate_id' => $ref_affiliate_id, 'plan_amount' => $payment_amount );
                $armaff_referral_detail = @serialize($armaff_referral_detail);
                update_user_meta( $user_id, 'arm_affiliate_add_referral_'.$plan_id, $armaff_referral_detail );*/
            } else {

                $ref_affiliate_id = get_user_meta($user_id, 'arm_aff_referral_id_plan_'.$plan_id, true);

                if( ($ref_affiliate_id <= 0) ){
                    $ref_affiliate_id = get_user_meta($user_id, 'arm_aff_referral_id', true);
                    if( ($ref_affiliate_id <= 0 || $ref_affiliate_id == '') && isset($entry_data['arm_ref_affiliate_id'])){
                        $ref_affiliate_id = isset($entry_data['arm_ref_affiliate_id']) ? $entry_data['arm_ref_affiliate_id'] : 0 ;
                    }
                }

                if($ref_affiliate_id == 0){ return; }

                if($this->arm_check_is_allowed_affiliate($ref_affiliate_id)){
                    $this->arm_affiliate_add_recurring_referral( $ref_affiliate_id, $plan_id, $user_id, $payment_amount );
                }

            }

        }

        function arm_aff_after_success_payment( $user_id, $plan_id ) {

            if($user_id <= 0 || $plan_id <= 0 ){
                return;
            }

            $armaff_get_referral_detail = get_user_meta($user_id, 'arm_affiliate_add_referral_'.$plan_id, true);

            if($armaff_get_referral_detail != ''){

                $armaff_get_referral_detail = @maybe_unserialize($armaff_get_referral_detail);

                $ref_affiliate_id = $armaff_get_referral_detail['ref_affiliate_id'];
                $plan_amount = $armaff_get_referral_detail['plan_amount'];

                $armaff_is_recurring_payment = $this->armaff_is_plan_recurring($user_id, $plan_id, 'bank_transfer');
                if($armaff_is_recurring_payment != 1){
                    $this->arm_affiliate_add_referral_2_1( $ref_affiliate_id, $plan_id, $user_id, $plan_amount );
                }

                delete_user_meta($user_id, 'arm_affiliate_add_referral_'.$plan_id);

            }

        }

        function arm_affiliate_add_recurring_referral( $ref_affiliate_id, $plan_id, $user_id, $payment_amount ){

            global $wpdb, $ARMember, $arm_payment_gateways, $arm_affiliate, $arm_manage_communication, $arm_affiliate_settings, $arm_global_settings, $arm_affiliate_commision_setup, $armaff_rate_type_arr;

            $plan_table = $ARMember->tbl_arm_subscription_plans;
            $plan_data = $wpdb->get_row($wpdb->prepare("SELECT `arm_subscription_plan_options` FROM `{$plan_table}` WHERE `arm_subscription_plan_id` = %d ", $plan_id), OBJECT );
            $arm_subscription_plan_options = maybe_unserialize(isset($plan_data->arm_subscription_plan_options) ? $plan_data->arm_subscription_plan_options : '');

            $referral_disable = isset($arm_subscription_plan_options['arm_affiliate_recurring_referral_disable']) ? $arm_subscription_plan_options['arm_affiliate_recurring_referral_disable'] : 0;
            $referral_type = isset($arm_subscription_plan_options['arm_affiliate_recurring_referral_type']) ? $arm_subscription_plan_options['arm_affiliate_recurring_referral_type'] : 0;
            $referral_rate = isset($arm_subscription_plan_options['arm_affiliate_recurring_referral_rate']) ? $arm_subscription_plan_options['arm_affiliate_recurring_referral_rate'] : 0;

            /* GET COMMISSION FOR PARTICULAR AFFILIATE USER */
            $armaff_affiliate_commision = $arm_affiliate_commision_setup->armaff_get_commision_for_affiliate_user($ref_affiliate_id);
            if(!empty($armaff_affiliate_commision)){

                if( isset($armaff_affiliate_commision['armaff_recurring_referral_status']) && $armaff_affiliate_commision['armaff_recurring_referral_status'] == 1){

                    $referral_type = isset($armaff_affiliate_commision['armaff_recurring_referral_type']) ? $armaff_affiliate_commision['armaff_recurring_referral_type'] : 0;
                    $referral_type = $armaff_rate_type_arr[$referral_type]['slug'];
                    $referral_rate = isset($armaff_affiliate_commision['armaff_recurring_referral_rate']) ? $armaff_affiliate_commision['armaff_recurring_referral_rate'] : 0;

                    $referral_disable = 1;
                }

            }

            if($referral_disable == 1) {
                $referral_amount = 0;

                if($referral_type == 'fixed_rate')
                {
                    $referral_amount = $referral_rate;
                }
                else if($referral_type == 'percentage')
                {
                    $referral_amount = ($payment_amount * $referral_rate) / 100;
                }
                $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                $is_allowed_amount = ( isset($affiliate_options['arm_aff_not_allow_zero_commision']) && $affiliate_options['arm_aff_not_allow_zero_commision'] == 1 && $referral_amount <= 0 ) ? false : true;
                $nowDate = current_time('mysql');
                $currency = $arm_payment_gateways->arm_get_global_currency();
                $arm_aff_default_refferal_status = isset($affiliate_options['arm_aff_referral_status']) ? $affiliate_options['arm_aff_referral_status'] :  0 ;

                $already_exist = 0;
                $armaff_date_format = $arm_global_settings->arm_get_wp_date_format() . " " . get_option('time_format');
                $armaff_check_date = date( $armaff_date_format, strtotime( $nowDate ) );
                $armaff_get_referral = $wpdb->get_row($wpdb->prepare("SELECT `arm_referral_id`,`arm_date_time` FROM `{$arm_affiliate->tbl_arm_aff_referrals}` WHERE `arm_affiliate_id` = %d AND `arm_ref_affiliate_id` = %d AND `arm_plan_id` = %d ORDER BY `arm_date_time` DESC LIMIT 1 ", $ref_affiliate_id, $user_id, $plan_id), OBJECT);
                $armaff_get_date = date( $armaff_date_format, strtotime( $armaff_get_referral->arm_date_time ) );
                if($armaff_check_date == $armaff_get_date ){
                    $already_exist = 1;
                    $ref_id = $armaff_get_referral->arm_referral_id;
                }

                if( $is_allowed_amount ){

                    if($already_exist != 1){
                        $arm_aff_referrals_values = array(
                            'arm_affiliate_id' => $ref_affiliate_id,
                            'arm_plan_id' => $plan_id,
                            'arm_ref_affiliate_id' => $user_id,
                            'arm_status' => $arm_aff_default_refferal_status,
                            'arm_amount' => $referral_amount,
                            'arm_currency' => $currency,
                            'arm_revenue_amount' => $payment_amount,
                            'arm_date_time' => $nowDate
                        );
                        $wpdb->insert($arm_affiliate->tbl_arm_aff_referrals, $arm_aff_referrals_values);

                        $ref_id = $wpdb->insert_id;
                    } else {
                         $wpdb->update(
                                $arm_affiliate->tbl_arm_aff_referrals, 
                                array( 'arm_amount' => $referral_amount, 'arm_currency' => $currency, 'arm_revenue_amount' => $payment_amount ), 
                                array( 'arm_referral_id' => $ref_id ), 
                                array( '%s' ),
                                array( '%s' ), 
                                array( '%s' ), 
                                array( '%d' ) 
                        );
                    }
                    
                    if( isset($_COOKIE['arm_aff_ref_cookie']) && $_COOKIE['arm_aff_ref_cookie'] > 0 && isset($_COOKIE['visitor_id']) && $_COOKIE['visitor_id'] > 0 ) {

                         $wpdb->update(
                                $arm_affiliate->tbl_arm_aff_visitors, 
                                array( 'arm_referral_id' => $ref_id ), 
                                array( 'arm_visitor_id' => $_COOKIE['visitor_id'] ), 
                                array( '%d' ), 
                                array( '%d' ) 
                        );
                    }

                    $arm_get_affiliate_details_row = $wpdb->get_row('SELECT arm_user_id FROM '.$arm_affiliate->tbl_arm_aff_affiliates.' WHERE arm_affiliate_id = '.$ref_affiliate_id, ARRAY_A);
                    $arm_get_affiliate_user_id = $arm_get_affiliate_details_row['arm_user_id'];
                    if(!empty($arm_get_affiliate_user_id))
                    {
                        $user_plans = get_user_meta($arm_get_affiliate_user_id, 'arm_user_plan_ids', true);
                        $user_plans = maybe_unserialize($user_plans);
                        $aff_user_plan_ids = "";
                        if(!empty($user_plans) && is_array($user_plans))
                        {
                            $aff_user_plan_ids = implode(',', $user_plans);
                        }
                        $arm_manage_communication->membership_communication_mail('arm_affiliate_notify_when_credited', $arm_get_affiliate_user_id, $aff_user_plan_ids );
                    }
                    
                    $arm_manage_communication->membership_communication_mail('arm_admin_when_user_refer_to_other', $user_id, $plan_id );

                    update_user_meta( $user_id, 'arm_aff_referral_id', $ref_affiliate_id );
                }
            }

        }

        function armaff_is_plan_recurring($user_id, $user_plan, $payment_gateway = ''){

            $armaff_is_recurring = 0;

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
                            $armaff_is_recurring = 1;
                        }
                    } else {
                        if( $recurring_time == $completed ) {
                            $armaff_is_recurring = 0;
                        } else {
                            $armaff_is_recurring = 1;
                        }
                    }
                }

            }

            return $armaff_is_recurring;
        }

        function arm_calculate_referral_amount( $plan_id, $plan_amount ) {
            global $wpdb, $arm_affiliate_settings;
            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
            
            if( $affiliate_options['arm_aff_referral_rate_type'] == 'flat' )
            {
                return isset( $affiliate_options['arm_aff_referral_default_rate'] ) ? $affiliate_options['arm_aff_referral_default_rate'] : 0;
            }
            else if( $affiliate_options['arm_aff_referral_rate_type'] == 'plan_based' )
            {
                $arm_plan_referral_rate_var = 'arm_plan_'.$plan_id.'_referral_rate';
                return isset( $affiliate_options['plan'][$arm_plan_referral_rate_var] ) ? $affiliate_options['plan'][$arm_plan_referral_rate_var] : 0;
            }
            else if( $affiliate_options['arm_aff_referral_rate_type'] == 'percentage' ){
                $arm_aff_percentage = isset( $affiliate_options['arm_aff_referral_default_rate'] ) ? $affiliate_options['arm_aff_referral_default_rate'] : 0;
                $referral_amount = $plan_amount * $arm_aff_percentage / 100;
                return $referral_amount;
            }
        }

        function arm_check_is_allowed_affiliate( $ref_affiliate_id ) {
            global $wpdb, $arm_affiliate;
            if($ref_affiliate_id != ''){
                $current_time = current_time('timestamp');
                $affiliate_user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $arm_affiliate->tbl_arm_aff_affiliates WHERE arm_status='1' AND arm_affiliate_id = %d", $ref_affiliate_id ), ARRAY_A );
                if(!empty($affiliate_user))
                {
                    if($affiliate_user['arm_end_date_time'] != '0000-00-00 00:00:00')
                    {
                        if($current_time > strtotime($affiliate_user['arm_start_date_time']) && $current_time < strtotime($affiliate_user['arm_end_date_time']))
                        {
                            return true;
                        }
                        else {
                            return false;
                        }
                    }
                    else if($current_time > strtotime($affiliate_user['arm_start_date_time']))
                    {
                        return true;
                    }
                    else
                    {
                        return false;
                    }
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }


        function arm_check_is_allowed_user( $ref_affiliate_id ) {
            global $wpdb, $arm_affiliate;
            if($ref_affiliate_id != ''){
                $current_time = current_time('timestamp');
                $affiliate_user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $arm_affiliate->tbl_arm_aff_affiliates WHERE arm_status='1' AND arm_user_id = %d", $ref_affiliate_id ), ARRAY_A );
                if(!empty($affiliate_user))
                {
                    if($affiliate_user['arm_end_date_time'] != '0000-00-00 00:00:00')
                    {
                        if($current_time > strtotime($affiliate_user['arm_start_date_time']) && $current_time < strtotime($affiliate_user['arm_end_date_time']))
                        {
                            return true;
                        }
                        else {
                            return false;
                        }
                    }
                    else if($current_time > strtotime($affiliate_user['arm_start_date_time']))
                    {
                        return true;
                    }
                    else
                    {
                        return false;
                    }
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }

        function get_user_referrals( $user_id ) {
            global $arm_affiliate, $wpdb;
            $tmp_query = "SELECT * FROM {$arm_affiliate->tbl_arm_aff_referrals} WHERE arm_affiliate_id = {$user_id}";
            $form_result = $wpdb->get_results($tmp_query);
            return $form_result;
        }
        
        function get_user_referrals_array( $user_id, $start_date='', $end_date='' ) {
            global $arm_affiliate, $wpdb, $arm_global_settings, $arm_subscription_plans, $arm_get_ref_affiliate_user_data, $arm_payment_gateways;
            
            $where_condition ='';
            if (!empty($start_date)) {
                if (!empty($end_date)) {
                    $where_condition .= " AND (arm_date_time BETWEEN '$start_date' AND '$end_date') ";
                } else {
                    $where_condition .= " AND (arm_date_time > '$start_date') ";
                }
            } else {
                if (!empty($end_date)) {
                    $where_condition .= " AND (arm_date_time < '$end_date') ";
                }
            }
            
            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $tmp_query = "SELECT * FROM {$arm_affiliate->tbl_arm_aff_referrals} WHERE arm_affiliate_id = {$user_id}" . $where_condition;
            $form_result = $wpdb->get_results($tmp_query, 'ARRAY_A');
            $ai = 0;
            $grid_data = array();
            foreach($form_result as $referral) {
                $arm_referral_id = $referral['arm_referral_id'];
                $arm_plan_id = $referral['arm_plan_id'];
                $arm_ref_affiliate_id = $referral['arm_ref_affiliate_id'];
                $arm_status = $referral['arm_status'];
                $arm_amount = $referral['arm_amount'];
                $arm_currency = $referral['arm_currency'];
                $arm_date_time = date( $date_format, strtotime( $referral['arm_date_time'] ) );
                
                $arm_plan = new ARM_Plan($arm_plan_id);
                //$plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($arm_plan_id);
                $plan_name = (!empty($arm_plan->name)) ? $arm_plan->name : '-';
                $plan_amount = (!empty($arm_plan->amount)) ? $arm_plan->amount : '0';
                
                $arm_get_ref_affiliate_user_data = get_userdata($arm_ref_affiliate_id);
                $arm_ref_affiliate_user_name = $arm_get_ref_affiliate_user_data->user_login;
                
                $grid_data[$ai][0] = $arm_ref_affiliate_user_name;
                $grid_data[$ai][1] = "<center>".$plan_name."</center>";
                $grid_data[$ai][2] = "<center>".$arm_payment_gateways->arm_amount_set_separator($arm_currency, $plan_amount)." ".$arm_currency."</center>";
                $grid_data[$ai][3] = "<center>".$arm_payment_gateways->arm_amount_set_separator($arm_currency, $arm_amount)." ".$arm_currency."</center>";
                $grid_data[$ai][4] = "<center>".$arm_date_time."</center>";
                $grid_data[$ai][5] = "<span class='arm_item_status_text_transaction ".$this->referral_status[$arm_status]."'><center>".$this->referral_status[$arm_status]."</center></span>";
                
                $ai++;
            }
            return $grid_data;
        }
        
        function delete_referrals_when_user_delete( $user_id ) {
            global $wpdb, $arm_affiliate;
            /*$wpdb->query( "DELETE FROM `$arm_affiliate->tbl_arm_aff_referrals` WHERE arm_affiliate_id = " . $user_id . " OR arm_ref_affiliate_id = ". $user_id );*/
            $wpdb->query( "UPDATE `$arm_affiliate->tbl_arm_aff_referrals` SET arm_ref_affiliate_id = 0 WHERE arm_ref_affiliate_id = ". $user_id );
        }
    
        function export_referral_in_csv( $request ) {
            global $arm_members_class, $wpdb, $arm_affiliate, $arm_global_settings, $arm_aff_referrals, $arm_subscription_plans, $arm_default_user_details_text;
            
            if( isset($request['arm_action']) && $request['arm_action'] == 'referrals_export_csv' ) {
                $date_format = $arm_global_settings->arm_get_wp_date_format();

                $where_condition = '';
            
                $sSearch = isset($request['sSearch']) ? $request['sSearch'] : '';
                if($sSearch != '')
                { $where_condition.= " AND u.user_login LIKE '%{$sSearch}%'"; }

                $filter_plan_id = (!empty($request['filter_plan_id']) && $request['filter_plan_id'] != '0') ? $request['filter_plan_id'] : '';
                if($filter_plan_id != '')
                { $where_condition.= " AND r.arm_plan_id IN (".$filter_plan_id.")"; }

                $filter_status_id = isset($request['filter_status_id']) ? $request['filter_status_id'] : '';
                if($filter_status_id != '')
                { $where_condition.= " AND r.arm_status IN (".$filter_status_id.")"; }

                $start_date = isset($request['start_date']) ? $request['start_date'] : '';
                $end_date = isset($request['end_date']) ? $request['end_date'] : '';
                if (!empty($start_date)) {
                    $start_datetime = $this->date_convert_db_formate($start_date)." 00:00:00";
                    if (!empty($end_date)) {
                        $end_datetime = $this->date_convert_db_formate($end_date)." 23:59:59";
                        if ($start_datetime > $end_datetime) {
                            $end_datetime = $this->date_convert_db_formate($start_date)." 00:00:00";
                            $start_datetime = $this->date_convert_db_formate($end_date)." 23:59:59";
                        }
                        $where_condition .= " AND (r.arm_date_time BETWEEN '$start_datetime' AND '$end_datetime') ";
                    } else {
                        $where_condition .= " AND (r.arm_date_time > '$start_datetime') ";
                    }
                } else {
                    if (!empty($end_date)) {
                        $end_datetime = $this->date_convert_db_formate($end_date);  
                        $where_condition .= " AND (r.arm_date_time < '$end_datetime') ";
                    }
                }

                $grid_data = array();
                $ai = 0;
                $user_table = $wpdb->users;
                $tmp_query = "SELECT r.*, u.user_login FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r "
                            ." LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff "
                            ." ON aff.arm_affiliate_id = r.arm_affiliate_id "
                            ." LEFT JOIN `{$user_table}` u "
                            ." ON u.ID = aff.arm_user_id "
                            ." WHERE 1=1 "
                            .$where_condition;

                $referrrals = $wpdb->get_results($tmp_query);
                if (!empty($referrrals))
                {
                    $referrals_data = array();
                    foreach ($referrrals as $referral)
                    {
                        $arm_referral_id = $referral->arm_referral_id;
                        $arm_affiliate_id = $referral->arm_affiliate_id;
                        $arm_plan_id = $referral->arm_plan_id;
                        $arm_ref_affiliate_id = $referral->arm_ref_affiliate_id;
                        $arm_status = $referral->arm_status;
                        $arm_amount = $referral->arm_amount;
                        $arm_currency = $referral->arm_currency;
                        $arm_date_time = date( $date_format, strtotime( $referral->arm_date_time ) );

                        $arm_affiliate_user_name = isset($referral->user_login) ? $referral->user_login : '';
                        if(empty($arm_affiliate_user_name)){
                            $arm_affiliate_user_name = $arm_default_user_details_text;
                        }

                        $plan_name_val = $arm_subscription_plans->arm_get_plan_name_by_id($arm_plan_id);
                        $plan_name = (!empty($plan_name_val)) ? $plan_name_val : '- ';

                        $arm_get_ref_affiliate_user_data = get_userdata($arm_ref_affiliate_id);
                        $arm_ref_affiliate_user_name = isset($arm_get_ref_affiliate_user_data->user_login) ? $arm_get_ref_affiliate_user_data->user_login : '';
                        if( empty($arm_ref_affiliate_user_name) ){
                            $arm_ref_affiliate_user_name = $arm_default_user_details_text;
                        }
                        
                        $referrals_data[] = array(
                            'affiliate_username' => $arm_affiliate_user_name,
                            'amount' => number_format($arm_amount,2)." ".$arm_currency,
                            'plan_name' => $plan_name,
                            'reference_username' => $arm_ref_affiliate_user_name,
                            'date' => $arm_date_time,
                            'status' => $this->referral_status[$arm_status]
                        );
                    }
                    $arm_members_class->arm_export_to_csv($referrals_data, 'ARMember-export-referrals.csv', $delimiter=',');
                } 
            }
            
        }

    }
}

global $arm_aff_referrals;
$arm_aff_referrals = new arm_aff_referrals();
?>