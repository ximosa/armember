<?php
	if (!class_exists('ARM_GROUP_MEMBERSHIP')) 
	{
		class ARM_GROUP_MEMBERSHIP
		{
			function __construct()
			{
				//Delete child User
				add_action('wp_ajax_arm_gm_delete_child_user', array($this, 'arm_gm_delete_child_user'));

				//Check username or email exist or not
				add_action('wp_ajax_arm_gm_check_email_username', array($this, 'arm_gm_check_email_username'));

				//Edit Text for Email Notification Modal
				add_filter('arm_edit_message_type', array($this, 'arm_gm_edit_message_data'), 10, 1);


				//Add filter of Summary Text Field
				add_filter('arm_summary_text_filter', array($this, 'arm_gm_summary_text_filter'), 10, 1);

				//Add field in Summary Text Field
				add_action('arm_add_summary_text_field', array($this, 'arm_gm_add_summary_text_field'));

				//To Display Group Membership Plan form fields (Admin Side)
				add_action('arm_display_field_add_membership_plan', array($this, 'add_group_membership_plan_fields'));

				//To save Group Membership Data (Admin Side)
				add_filter('arm_befor_save_field_membership_plan', array( $this, 'arm_save_group_membership_plan_fields' ), 10, 2 );

				//Change Form Data after render form on frontside.
				add_filter('arm_after_setup_form_content', array($this, 'arm_gm_check_sub_user'), 10, 3);

				//Restrict cancel subscription button at frontend for sub user
				add_filter('arm_membership_btn_filter', array($this, 'arm_gm_filter_membership_atts'), 10, 2);

				//To stop sending mail notification to user
				add_action( 'arm_before_send_email_notification', array($this, 'arm_gm_stop_sending_mail_notification'), 10, 5 );

				//For add field of sub user selection in signup form.
				add_filter('arm_before_setup_reg_form_section', array($this, 'arm_gm_signup_sub_user_selection_form'), 10, 3);

				//For recalculate tax details on selection of sub-user.
				add_action( 'wp_ajax_arm_gm_recalculate_plan_amount', array( $this, 'arm_gm_recalculate_plan_amount' ) );
            	add_action( 'wp_ajax_nopriv_arm_gm_recalculate_plan_amount', array( $this, 'arm_gm_recalculate_plan_amount' ) );

            	//Modify setup data if user already purchased the plan
            	add_filter('arm_setup_data_before_submit', array($this, 'arm_gm_before_submit_form_data'), 10, 2);

            	//Generate coupon codes of registered parent user.
            	add_action('arm_after_add_new_user', array($this, 'arm_gm_form_data_submit'), 10, 2);

            	//Add new field in Coupon Table
            	add_filter('arm_add_new_coupon_field_heading', array($this, 'arm_add_new_coupon_heading'), 10, 1);

            	//Add Records in Coupon Table
            	add_filter( 'arm_add_new_coupon_field_body', array( $this, 'arm_add_new_coupn_field_body' ), 10, 2 );

            	//Add configuration Option in 'Configure Plan + Signup Page' -> 'Other Options'
            	add_action( 'arm_add_configuration_option', array( $this, 'arm_add_configuration_option' ), 10, 1);


            	//Add Admin Side shortcode option in Membership Shortcode.
            	add_action( 'add_others_section_option_tinymce', array($this, 'arm_gm_other_section_option_add_func') );

            	//Add shortcode action buttons for admin side.
            	add_action( 'arm_shortcode_add_other_tab_buttons', array($this, 'arm_gm_other_tab_buttons_func'));

            	//Form details for group membership at Admin Side.
            	add_action( 'add_others_section_select_option_tinymce', array($this, 'arm_gm_other_section_option_form_fields'));

            	//Shortcode `[arm_group_child_member_list]` for display child members.
            	add_shortcode( 'arm_group_child_member_list', array( $this, 'arm_group_child_member_list_func' ) );


            	//Invition code pagination request.
            	add_action( 'wp_ajax_arm_gm_invite_code_pagination', array( $this, 'arm_gm_invite_code_pagination' ) );
            	add_action( 'wp_ajax_nopriv_arm_gm_invite_code_pagination', array( $this, 'arm_gm_invite_code_pagination' ) );


            	//Update Invite Code when refresh from shortcode table.
            	add_action( 'wp_ajax_arm_gm_update_coupon_code' , array($this, 'arm_gm_refresh_coupon'));


            	//Add option of Response in Email Notification Module of ARMember
            	add_filter('arm_notification_add_message_types', array( $this, 'arm_gm_set_notification_message_type' ), 10, 1 );

            	add_filter('arm_notification_get_list_msg_type', array( $this, 'arm_gm_set_notification_list_msg_type' ), 10, 1 );


            	//Add Invite Code ShortCode in Email Notification Module
            	add_filter('arm_email_notification_shortcodes_outside', array(&$this, 'arm_gm_email_notification_shortcodes_func'), 10, 1);

            	add_filter('arm_admin_email_notification_shortcodes_outside', array(&$this, 'arm_gm_email_notification_shortcodes_func'), 10, 1);

            	//Invite Users , specified in Invite modal in frontend
				add_action( 'wp_ajax_arm_gm_invite_users' , array($this, 'arm_gm_invite_users_coupon'));

				//Resend Invite User
				add_action('wp_ajax_arm_gm_resend_email', array($this, 'arm_gm_invite_resend_email'));

				//Delete child user from system
				add_action( 'wp_ajax_arm_gm_delete_member' , array($this, 'arm_gm_delete_child_member'));

				//Retrieve Admin Display Data
				add_action( 'wp_ajax_get_arm_gm_admin_data' , array($this, 'get_arm_gm_admin_data'));

				//Retrieve child users data
				add_action( 'wp_ajax_arm_child_user_details' , array($this, 'get_arm_gm_child_users_data'));
				add_action( 'wp_ajax_get_sub_user_table_data' , array($this, 'get_arm_gm_sub_user_table_data'));

				//Delete Parent User , Coupon & Child User
				add_action('arm_delete_users_external', array($this, 'arm_gm_delete_child_users'), 10, 1);
				add_action( 'wp_ajax_arm_gm_delete_users' , array($this, 'arm_gm_delete_parent_user'));				

				//Add new section in page setup
				add_action('arm_page_setup_section', array($this, 'arm_gm_add_page_setup'));
				add_filter('arm_before_update_page_settings', array($this, 'arm_gm_save_page_settings'), 10, 2);


				//Add option to select child user seat in membership plan in 'Manage Members'.
				add_filter('arm_add_membership_plan_option', array($this, 'arm_gm_add_membership_child_user_option'), 10, 2);

				//Update sub user seat slot of 'Manage Members'
				add_action('arm_member_update_meta', array($this, 'arm_gm_update_sub_user_seat_slot'), 10, 2);

				//Display edit view when edit group membership button clicked
				add_action('wp_ajax_arm_gm_edit_group_membership', array($this, 'arm_gm_load_edit_data'));

				//Update group membership data
				add_action('wp_ajax_arm_gm_update_group_membership', array($this, 'arm_gm_update_group_membership_data'));


				//Add child user data (added from backend)
				add_action('wp_ajax_arm_gm_add_sub_user_group_membership', array($this, 'arm_gm_add_sub_user_data'));


				//Ajax action for check user have plan or not
				add_action('wp_ajax_arm_check_user_plan_status', array($this, 'arm_gm_check_user_plan_status'));

				//For set common error messages in 'Common Message' section in General Settings.
				add_action('arm_after_common_messages_settings_html', array($this, 'arm_gm_set_common_error_messages'), 10, 1);

				//For add users calculation when coupon applied.
				add_filter('arm_modify_coupon_pricing', array($this, 'arm_gm_modify_coupon_pricing'), 10, 4);

				//For check child user enable or not in selected page.
				add_filter('arm_shortcode_exist_in_page', array($this, 'arm_gm_check_child_user_exist_or_not'), 10, 2);
			}


			function arm_gm_delete_child_user()
			{
				global $wpdb, $ARMember, $arm_global_settings;
				$arm_gm_delete_user_id = !empty($_POST['arm_gm_delete_user_id']) ? $_POST['arm_gm_delete_user_id'] : '';
				$arm_gm_user_status = !empty($_POST['arm_gm_user_status']) ? $_POST['arm_gm_user_status'] : '';
				if($arm_gm_user_status == "active")
				{
					$arm_gm_used_coupon_code = get_user_meta($arm_gm_delete_user_id, 'arm_coupon_code', true);

					$update_data = $wpdb->query("UPDATE $ARMember->tbl_arm_coupons SET arm_coupon_used = 0 WHERE arm_coupon_code = '".$arm_gm_used_coupon_code."'");

					wp_delete_user($arm_gm_delete_user_id);
				}
				else if($arm_gm_user_status == "pending")
				{
					$arm_gm_get_pending_users_data = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."arm_gm_child_users_status WHERE arm_gm_id = '".$arm_gm_delete_user_id."'");
					$arm_gm_invite_code_id = $arm_gm_get_pending_users_data->arm_gm_invite_code_id;

					$arm_gm_update_used_coupon_status = ['arm_coupon_status' => '0', 'arm_coupon_used' => '0'];
					$arm_gm_where_condition = ['arm_coupon_id' => $arm_gm_invite_code_id];
					$wpdb->update($ARMember->tbl_arm_coupons, $arm_gm_update_used_coupon_status, $arm_gm_where_condition);

					$arm_gm_delete_pending_user = $wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."arm_gm_child_users_status WHERE arm_gm_id = %d", $arm_gm_delete_user_id));
				}

				$return['status'] = 1;
				$return['msg'] = __('Child User Deleted Successfully', 'ARMGroupMembership');
				echo json_encode($return);
				exit();
			}


			function arm_gm_check_email_username()
			{
				$arm_gm_current_val = $_POST['arm_gm_current_val'];
				$arm_gm_check_username = username_exists($arm_gm_current_val);

				$arm_gm_check_email = email_exists($arm_gm_current_val);

				$return_data['username'] = ($arm_gm_check_username) ? 1 : 0;
				$return_data['email'] = ($arm_gm_check_email) ? 1 : 0;

				echo json_encode($return_data);
				exit();
			}


			function arm_gm_edit_message_data($arm_message_type)
			{
				if($arm_message_type == "arm_gm_on_invite_user_as_group_member")
				{
					$arm_message_type = __('On users invite with invite code', 'ARMGroupMembership');
				}
				else if($arm_message_type == "arm_gm_send_signup_notification_to_parent")
				{
					$arm_message_type = __('Send Signup Notification to Parent User', 'ARMGroupMembership');
				}
				else if($arm_message_type == "arm_gm_send_signup_notification_to_child")
				{
					$arm_message_type = __('Send Signup Notification to Child User', 'ARMGroupMembership');
				}
				return $arm_message_type;
			}

			function arm_gm_check_user_plan_status()
			{
				if(!empty($_POST) && !empty($_POST['arm_gm_user_id']))
				{
					$arm_gm_user_id = $_POST['arm_gm_user_id'];
					$arm_gm_user_plans = get_user_meta($arm_gm_user_id, 'arm_user_plan_ids', true);
					$arm_gm_suspended_plan_ids = get_user_meta($arm_gm_user_id, 'arm_user_suspended_plan_ids', true);

					if(!empty($arm_gm_user_plans) && is_array($arm_gm_user_plans)){
                        foreach($arm_gm_user_plans as $arm_gm_plan){
                            if(in_array($arm_gm_plan, $arm_gm_suspended_plan_ids)){
                                unset($arm_gm_user_plans[array_search($arm_gm_plan, $arm_gm_user_plans)]);
                            }
                        }
                    }

					$arm_gm_user_post_ids = get_user_meta($arm_gm_user_id, 'arm_user_post_ids', true);

					foreach($arm_gm_user_plans as $arm_gm_plan_keys => $arm_gm_plan_val)
					{
						if(!empty($arm_gm_user_post_ids[$arm_gm_plan_val]))
						{
							unset($arm_gm_user_plans[$arm_gm_plan_keys]);
						}
					}

					$return_data = array();

					if(!empty($arm_gm_user_plans))
					{
						$return_data['status'] = 0;
						$return_data['msg'] = __('User have already assigned plan.', 'ARMGroupMembership');
					}
					else
					{
						$return_data['status'] = 1;
						$return_data['msg'] = '';
					}

					echo json_encode($return_data);
					exit();
				}
			}


			function arm_gm_summary_text_filter($setupSummaryText)
			{
				$setupSummaryText = str_replace('[SELECTED_CHILD]', '<span class="arm_selected_child_users"></span>', $setupSummaryText);
				return $setupSummaryText;
			}


			function arm_gm_add_summary_text_field()
			{
				$arm_summary_text_content = '<li><code>[SELECTED_CHILD]</code> - '.__("This will be replaced with total of selected child user.", "ARMGroupMembership").'</li>';
				echo $arm_summary_text_content;
			}



			function arm_gm_add_sub_user_data()
			{
				global $wpdb, $wp, $ARMember, $arm_global_settings, $arm_manage_communication, $arm_group_child_signup_membership;

				//If sub user fields values not blank then add child user.
				$arm_gm_parent_user_id = $_POST['arm_gm_parent_user_id'];
				$arm_gm_plan_val = get_user_meta($arm_gm_parent_user_id, 'arm_user_plan_ids', true);
				$arm_gm_suspended_plan_ids = get_user_meta($arm_gm_parent_user_id, 'arm_user_suspended_plan_ids', true);

				if(!empty($arm_gm_plan_val) && is_array($arm_gm_plan_val)){
                    foreach($arm_gm_plan_val as $arm_gm_plan){
                        if(in_array($arm_gm_plan, $arm_gm_suspended_plan_ids)){
                            unset($arm_gm_plan_val[array_search($arm_gm_plan, $arm_gm_plan_val)]);
                        }
                    }
                }

				$arm_gm_child_user_plan = "";
				
				foreach($arm_gm_plan_val as $arm_gm_plan_keys => $arm_gm_plan_vals)
				{
					$arm_gm_plan_obj = new ARM_Plan($arm_gm_plan_vals);
					if(!$arm_gm_plan_obj->isPaidPost)
					{
						$arm_gm_child_user_plan = $arm_gm_plan_vals;
					}
				}

				$arm_gm_plan_obj = new ARM_Plan($arm_gm_child_user_plan);
				$arm_gm_plan_options = $arm_gm_plan_obj->options;

				//Get Maximum Sub User Limit
				$arm_gm_total_invite_code_counters = $wpdb->get_row($wpdb->prepare("SELECT COUNT(arm_coupon_id) as total_rec FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_group_parent_user_id` = ".$arm_gm_parent_user_id.""));
				$arm_gm_total_invite_code_counters = $arm_gm_total_invite_code_counters->total_rec;

				//Get Sub users counter
				$arm_gm_sub_users_counter = $wpdb->get_row($wpdb->prepare("SELECT COUNT(arm_coupon_id) as total_rec FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_group_parent_user_id` = ".$arm_gm_parent_user_id." AND arm_coupon_used = 1"));
				$arm_gm_total_sub_users = $arm_gm_sub_users_counter->total_rec;

				if($arm_gm_total_invite_code_counters == $arm_gm_total_sub_users)
				{
					$return['msg'] = __('Maximum limit for adding child user is reached.', 'ARMGroupMembership');
					$return['status'] = 0;
					echo json_encode($return);
				}
				else
				{
					$arm_gm_sub_user_username = $_POST['arm_gm_sub_user_username'];
					$arm_gm_sub_user_email = $_POST['arm_gm_sub_user_email'];
					$arm_gm_sub_user_password = $_POST['arm_gm_sub_user_password'];

					$arm_gm_inserted_user_id = wp_create_user($arm_gm_sub_user_username, $arm_gm_sub_user_password, $arm_gm_sub_user_email);

					//Get one invite code and assign to user
					$arm_gm_coupon_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_group_parent_user_id` = ".$arm_gm_parent_user_id." AND arm_coupon_used = 0"));
					$arm_gm_invite_code_id = $arm_gm_coupon_data->arm_coupon_id;
					$arm_gm_invite_code_name = $arm_gm_coupon_data->arm_coupon_code;



					$arm_gm_child_user_data = array('plan_id' => $arm_gm_child_user_plan);
					$arm_gm_parent_user_data = array('arm_parent_user_id' => $arm_gm_parent_user_id);


					update_user_meta($arm_gm_inserted_user_id, 'arm_coupon_code', $arm_gm_invite_code_name);
					update_user_meta($arm_gm_inserted_user_id, 'arm_is_child_user', $arm_gm_child_user_data);
					update_user_meta($arm_gm_inserted_user_id, 'arm_parent_user_id', $arm_gm_parent_user_data);


					//Send Email Notifications
					$arm_gm_parent_user_message_type = "arm_gm_send_signup_notification_to_parent";
					$arm_gm_child_user_message_type = "arm_gm_send_signup_notification_to_child";
					$is_sent_to_admin = 0;


					//Get Parent User Email Id & Details
					$arm_gm_parent_user_email = "";
					$arm_gm_parent_user_details = get_user_by('id', $arm_gm_parent_user_id);
					if(!empty($arm_gm_parent_user_details))
					{
						$arm_gm_parent_user_email = $arm_gm_parent_user_details->user_email;
					}


					$arm_gm_parent_user_plans = get_user_meta($arm_gm_parent_user_id, 'arm_user_plan_ids', true);
					$arm_gm_suspended_plan_ids = get_user_meta($arm_gm_parent_user_id, 'arm_user_suspended_plan_ids', true);
					if(!empty($arm_gm_parent_user_plans) && is_array($arm_gm_parent_user_plans)){
                        foreach($arm_gm_parent_user_plans as $arm_gm_plan){
                            if(in_array($arm_gm_plan, $arm_gm_suspended_plan_ids)){
                                unset($arm_gm_parent_user_plans[array_search($arm_gm_plan, $arm_gm_parent_user_plans)]);
                            }
                        }
                    }

					$arm_gm_parent_user_plan = isset($arm_gm_parent_user_plans) && !empty($arm_gm_parent_user_plans) ? implode(',', $arm_gm_parent_user_plans) : 0;


					//Posted Data
					$posted_data['arm_gm_child_invite_code'] = $arm_gm_invite_code_name;


					//Get Message of Parent User Notification
					$arm_gm_parent_user_msg = $wpdb->get_results("SELECT `arm_message_subject`, `arm_message_content` , `arm_message_send_copy_to_admin`, `arm_message_send_diff_msg_to_admin`, `arm_message_admin_message` FROM `" . $ARMember->tbl_arm_auto_message . "` WHERE `arm_message_status`='1' AND `arm_message_type`='" . $arm_gm_parent_user_message_type . "' AND (FIND_IN_SET(" . $arm_gm_parent_user_plan . ", `arm_message_subscription`) OR (`arm_message_subscription`=''))");

					//Send email notification to parent user if notification is enabled
					if(!empty($arm_gm_parent_user_msg))
					{
						foreach($arm_gm_parent_user_msg as $arm_gm_parent_user_msg_key => $arm_gm_parent_user_msg_val)
						{
							$content_subject = $arm_gm_parent_user_msg_val->arm_message_subject;
                            $content_description = $arm_gm_parent_user_msg_val->arm_message_content;
                            $send_one_copy_to_admin = $arm_gm_parent_user_msg_val->arm_message_send_copy_to_admin;
                            $send_diff_copy_to_admin = $arm_gm_parent_user_msg_val->arm_message_send_diff_msg_to_admin;
                            $admin_content_description = $arm_gm_parent_user_msg_val->arm_message_admin_message;

                            $subject = $arm_manage_communication->arm_filter_communication_content($content_subject, $arm_gm_parent_user_id, $arm_gm_parent_user_plan, '');
                            $subject = $arm_group_child_signup_membership->arm_gm_parent_and_child_user_filter_shortcode_content($content_subject, $arm_gm_parent_user_id, $posted_data);


                            $message = $arm_manage_communication->arm_filter_communication_content($content_description, $arm_gm_parent_user_id, $arm_gm_parent_user_plan, '');
                            $message = $arm_group_child_signup_membership->arm_gm_parent_and_child_user_filter_shortcode_content($content_description, $arm_gm_parent_user_id, $posted_data);


                            $admin_message = $arm_manage_communication->arm_filter_communication_content($admin_content_description, $arm_gm_parent_user_id, $arm_gm_parent_user_plan, '');
                            $admin_message = $arm_group_child_signup_membership->arm_gm_parent_and_child_user_filter_shortcode_content($admin_content_description, $arm_gm_parent_user_id, $posted_data);

                            $send_mail = $arm_global_settings->arm_wp_mail('', $arm_gm_parent_user_email, $subject, $message);


                            if ($send_one_copy_to_admin == 1 && $is_sent_to_admin == 0) {
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



					//Get Message of Child User Notification
					$arm_gm_child_user_msg = $wpdb->get_results("SELECT `arm_message_subject`, `arm_message_content` , `arm_message_send_copy_to_admin`, `arm_message_send_diff_msg_to_admin`, `arm_message_admin_message` FROM `" . $ARMember->tbl_arm_auto_message . "` WHERE `arm_message_status`='1' AND `arm_message_type`='" . $arm_gm_child_user_message_type . "' AND (FIND_IN_SET(" . $arm_gm_parent_user_plan . ", `arm_message_subscription`) OR (`arm_message_subscription`=''))");

					//Send email notification to child user if notification is enabled
					if(!empty($arm_gm_child_user_msg))
					{
						foreach($arm_gm_child_user_msg as $arm_gm_child_user_msg_key => $arm_gm_child_user_msg_val)
						{
							$content_subject = $arm_gm_child_user_msg_val->arm_message_subject;
                            $content_description = $arm_gm_child_user_msg_val->arm_message_content;
                            $send_one_copy_to_admin = $arm_gm_child_user_msg_val->arm_message_send_copy_to_admin;
                            $send_diff_copy_to_admin = $arm_gm_child_user_msg_val->arm_message_send_diff_msg_to_admin;
                            $admin_content_description = $arm_gm_child_user_msg_val->arm_message_admin_message;

                            $subject = $arm_manage_communication->arm_filter_communication_content($content_subject, $arm_gm_inserted_user_id, $arm_gm_parent_user_plan, '');
                            $subject = $arm_group_child_signup_membership->arm_gm_parent_and_child_user_filter_shortcode_content($content_subject,$arm_gm_parent_user_id, $posted_data);


                            $message = $arm_manage_communication->arm_filter_communication_content($content_description, $arm_gm_inserted_user_id, $arm_gm_parent_user_plan, '');
                            $message = $arm_group_child_signup_membership->arm_gm_parent_and_child_user_filter_shortcode_content($content_description, $arm_gm_parent_user_id, $posted_data);


                            $admin_message = $arm_manage_communication->arm_filter_communication_content($admin_content_description, $arm_gm_inserted_user_id, $arm_gm_parent_user_plan, '');
                            $admin_message = $arm_group_child_signup_membership->arm_gm_parent_and_child_user_filter_shortcode_content($admin_content_description, $arm_gm_parent_user_id, $posted_data);

                            $send_mail = $arm_global_settings->arm_wp_mail('', $arm_gm_sub_user_email, $subject, $message);


                            if ($send_one_copy_to_admin == 1 && $is_sent_to_admin == 0) {
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


					//Update coupon counter
					$arm_gm_update_data['arm_coupon_used'] = 1;
					$update_data = $wpdb->update($ARMember->tbl_arm_coupons, $arm_gm_update_data, array('arm_coupon_id' => $arm_gm_invite_code_id));

					//Insert data to users list table.
					$arm_gm_new_user_data['arm_gm_email_id'] = $arm_gm_sub_user_email;
					$arm_gm_new_user_data['arm_gm_user_id'] = $arm_gm_inserted_user_id;
					$arm_gm_new_user_data['arm_gm_parent_user_id'] = $arm_gm_parent_user_id;
					$arm_gm_new_user_data['arm_gm_status'] = 1;
					$arm_gm_new_user_data['arm_gm_invite_code_id'] = $arm_gm_invite_code_id;

					$arm_gm_users_list_table = $wpdb->prefix.'arm_gm_child_users_status';
					$arm_gm_user_format = array('%s', '%d', '%d', '%d', '%d');

					$wpdb->insert($arm_gm_users_list_table, $arm_gm_new_user_data, $arm_gm_user_format);

					$return['msg'] = __('Sub User Added Successfully.', 'ARMGroupMembership');
					$return['status'] = 1;
					echo json_encode($return);
				}

				exit();
			}


			function arm_gm_update_group_membership_data()
			{
				global $wpdb, $wp, $ARMember, $arm_global_settings;

				$arm_gm_update_user_id = $_POST['arm_gm_update_user_id'];
				$arm_gm_update_plan_ids = $_POST['arm_gm_update_plan_id'];
				$arm_gm_update_maximum_numbers = $_POST['edit_maximum_seat_slot'];

				$arm_gm_member_counter = 0;

				foreach($arm_gm_update_plan_ids as $arm_gm_plan_key => $arm_gm_plan_val)
				{
					$arm_gm_plan_data = new ARM_Plan($arm_gm_plan_val);
					$arm_gm_plan_options = $arm_gm_plan_data->options;

					$arm_gm_previous_total_members = get_user_meta($arm_gm_update_user_id, 'gm_sub_user_select_'.$arm_gm_plan_val, true);

					$arm_gm_new_max_members = $arm_gm_update_maximum_numbers[$arm_gm_member_counter];
					
					if($arm_gm_previous_total_members != $arm_gm_new_max_members)
					{
						for($tmp_i = 1; $tmp_i <= $arm_gm_new_max_members; $tmp_i++)
						{
							$arm_gm_generate_random_coupon_code['arm_coupon_code'] = $this->arm_gm_generate_random_code(12);
							$arm_gm_generate_random_coupon_code['arm_group_parent_user_id'] = $arm_gm_update_user_id;
							$arm_gm_generate_random_coupon_code['arm_coupon_discount'] = 100;
							$arm_gm_generate_random_coupon_code['arm_coupon_discount_type'] = 'percentage';
							$arm_gm_generate_random_coupon_code['arm_coupon_period_type'] = 'unlimited';
							$arm_gm_generate_random_coupon_code['arm_coupon_allowed_uses'] = 1;
							$arm_gm_generate_random_coupon_code['arm_coupon_status'] = 1;
							$arm_gm_generate_random_coupon_code['arm_coupon_subscription'] = $arm_gm_plan_val;
							$arm_gm_generate_random_coupon_code['arm_coupon_start_date'] = date('Y-m-d H:i:s');
							$arm_gm_generate_random_coupon_code['arm_coupon_expire_date'] = date('Y-m-d H:i:s');
							$ins = $wpdb->insert($ARMember->tbl_arm_coupons, $arm_gm_generate_random_coupon_code);
						}

						$arm_gm_new_max_members = $arm_gm_new_max_members + $arm_gm_previous_total_members;

						update_user_meta($arm_gm_update_user_id, 'gm_sub_user_select_'.$arm_gm_plan_val, $arm_gm_new_max_members);
					}

					$arm_gm_member_counter++;
				}

				exit();
			}


			function arm_gm_load_edit_data()
			{
				global $wpdb, $wp, $ARMember, $arm_global_settings;

				$arm_gm_edit_user_id = $_POST['edit_user_id'];

				$arm_gm_plan_content = "";
				$arm_gm_plan_content .= "<style type='text/css'>";
				$arm_gm_plan_content .= ".arm_gm_edit_plan_heading{ font-size: 1rem; }";
				$arm_gm_plan_content .= ".arm_gm_edit_plan_body .arm_gm_edit_form_group{ margin-left: 3rem; margin-top: 1rem; }";
				$arm_gm_plan_content .= ".arm_gm_edit_form_group label{ margin-right: 3rem; }";
				$arm_gm_plan_content .= "</style>";

				$arm_gm_plan_content .= "<form id='arm_gm_update_form' class='arm_admin_form'>";
				$arm_gm_plan_content .= "<input type='hidden' name='arm_gm_update_user_id' value='".$arm_gm_edit_user_id."'>";

				//Fetch all plan details of user
				$arm_gm_plan_data = get_user_meta($arm_gm_edit_user_id, 'arm_user_plan_ids', true);
				$arm_gm_suspended_plan_ids = get_user_meta($arm_gm_edit_user_id, 'arm_user_suspended_plan_ids', true);

				foreach($arm_gm_plan_data as $arm_gm_plan_key => $arm_gm_plan_val)
				{
					$plan_data = new ARM_Plan($arm_gm_plan_val);
					$plan_ID = $plan_data->ID;
					$plan_options = $plan_data->options;

					if($plan_data->arm_subscription_plan_options['arm_gm_group_membership_disable_referral']){
						$arm_gm_plan_max_members = get_user_meta($arm_gm_edit_user_id, 'gm_max_members_'.$plan_ID, true);
						$arm_gm_plan_min_members = get_user_meta($arm_gm_edit_user_id, 'gm_min_members_'.$plan_ID, true);
						$arm_gm_plan_purchased_member = get_user_meta($arm_gm_edit_user_id, 'gm_sub_user_select_'.$plan_ID, true);
						

						$arm_count_temp_purchased_members = $wpdb->get_row("SELECT COUNT(arm_coupon_id) as total FROM {$ARMember->tbl_arm_coupons} WHERE arm_group_parent_user_id = {$arm_gm_edit_user_id} AND arm_coupon_used = '1'");
						$arm_already_total_purchased_members = $arm_count_temp_purchased_members->total;

						$arm_gm_remaining_seats = !empty($arm_gm_plan_max_members) ? ($arm_gm_plan_max_members - $arm_gm_plan_purchased_member) : 0;

						$plan_name = $plan_data->name;

						$arm_suspend_message = '';
						$arm_input_box_disabled = '';
						if(in_array($plan_ID, $arm_gm_suspended_plan_ids)){
							$arm_input_box_disabled = "disabled='disabled'";
							$arm_suspend_message = __('Parent user plan is "Suspended" therefore it will not possible to modify "Add Child Users seat".', 'ARMGroupMembership');
						}

						$arm_gm_plan_content .= "<input type='hidden' name='arm_gm_update_plan_id[]' value='".$plan_ID."'>";
						$arm_gm_plan_content .= "<table class='arm_gm_edit_plan_body'>";
						$arm_gm_plan_content .= "<tr>";
						$arm_gm_plan_content .= "<th style='text-align: right;'>".__('Membership Plan','ARMGroupMembership')."</th>";
						$arm_gm_plan_content .= "<td>".$plan_name."</td>";
						$arm_gm_plan_content .= "</tr>";
						$arm_gm_plan_content .= "<tr>";
						$arm_gm_plan_content .= "<th class='edit_total_purchased_seat'>";
						$arm_gm_plan_content .= "<label>".__('Add Child Users seat', 'ARMGroupMembership')."</label>";
						$arm_gm_plan_content .= "</th>";
						$arm_gm_plan_content .= "<td>";
						$arm_gm_plan_content .= "<input type='number' class='arm_form_input_box edit_maximum_seat_slot' name='edit_maximum_seat_slot[]' value='' ".$arm_input_box_disabled." onchange='javascript:return ArmgmCheckLimit(event, this, ".$arm_already_total_purchased_members.", ".$arm_gm_plan_max_members.", ".$arm_gm_plan_purchased_member.")' data-error='".__('Your '.$arm_already_total_purchased_members.' are purchased. So you cannot enter less value.', 'ARMGroupMembership')."' data-max_error='".__('You have maximum '.$arm_gm_plan_max_members.' users limit.', 'ARMGroupMembership')."' data-total_error='".__('You have already purchased '.$arm_gm_plan_purchased_member.' seats and you can purchased upto '.$arm_gm_remaining_seats.' seats.', 'ARMGroupMembership')."'>";
						$arm_gm_plan_content .= '<span class="arm_gm_status_pending arm_gm_child_users_seat_error" style="display:none;">'.__('Please add child users seat', 'ARMGroupMembership').'</span>';
						$arm_gm_plan_content .= '<span class="arm_gm_status_pending arm_gm_child_users_seat_positive_error" style="display:none;">'.__('Please add valid child users seat', 'ARMGroupMembership').'</span>';
						$arm_gm_plan_content .= '<span class="arm_info_text">'.__('Note: The value entered in the above textbox will added to the current purchased child users value. e.g. So far if parent user has purchased 20 child users, and you will add 10 to this textbox, it will be total 30 child users purchased.', 'ARMGroupMembership').'</span>';

						$arm_gm_plan_content .= "</td>";
						$arm_gm_plan_content .= "</tr>";
						if(!empty($arm_suspend_message))
						{
							$arm_gm_plan_content .= "<tr><th>&nbsp;</th><td class='arm_gm_status_pending'>".$arm_suspend_message."</td></tr>";
						}

						if(empty($arm_input_box_disabled))
						{
							$arm_gm_plan_content .= "<tr>";
							$arm_gm_plan_content .= "<th>&nbsp;</th>";
							$arm_gm_plan_content .= "<td style='padding-top: 0px;'>";
							$arm_gm_plan_content .= "<ul class='arm_gm_edit_member_notes'>";
								$arm_gm_plan_max_members = !empty($arm_gm_plan_max_members) ? $arm_gm_plan_max_members : $arm_gm_plan_purchased_member;
								$arm_gm_max_members_txt = __("Maximum Allowed Child Users", "ARMGroupMembership").": ".$arm_gm_plan_max_members;
								$arm_gm_purchased_members_txt = __("Currently Total Purchased Child Users", "ARMGroupMembership").": ".($arm_gm_plan_max_members - $arm_gm_remaining_seats);
								$arm_gm_remaining_members_txt = __("Remaining number of child users you are allowed to purchase", "ARMGroupMembership").": ".$arm_gm_remaining_seats;
								
							$arm_gm_plan_content .= "<li>".$arm_gm_max_members_txt."</li>";
							$arm_gm_plan_content .= "<li>".$arm_gm_purchased_members_txt."</li>";
							$arm_gm_plan_content .= "<li>".$arm_gm_remaining_members_txt."</li>";
							$arm_gm_plan_content .= "</ul>";
							$arm_gm_plan_content .= "</td>";
							$arm_gm_plan_content .= "</tr>";
						}
						
						$arm_gm_plan_content .= "<tr>";
						$arm_gm_plan_content .= "<th>&nbsp;</th>";
						$arm_gm_plan_content .= "<td>";
						$arm_gm_plan_content .= "<button class='arm_member_edit_gm_save_btn arm_save_btn' ".$arm_input_box_disabled." type='button'>".__('Save', 'ARMGroupMembership')."</button>
						<button class='arm_member_edit_gm_cancel_btn arm_cancel_btn' type='button'>".__('Close', 'ARMGroupMembership')."</button>";
						$arm_gm_plan_content .= "</tr>";
						$arm_gm_plan_content .= "</table>";
					}
				}


				$arm_gm_plan_content .= "</form>";

				echo $arm_gm_plan_content;
				exit();
			}

			function arm_gm_update_sub_user_seat_slot($user_ID, $member_data)
			{
				global $wpdb, $wp, $ARMember, $arm_global_settings;
				if(!empty($member_data) && !empty($user_ID) && is_admin())
				{
					$is_child_user = $this->arm_gm_is_child_user($user_ID);
					if(!$is_child_user)
					{
						$arm_gm_plans = !empty($member_data['arm_user_plan']) ? $member_data['arm_user_plan'] : 0;
						$arm_gm_sub_user_slot = !empty($member_data['arm_gm_selected_user']) ? $member_data['arm_gm_selected_user'] : 0;

						$cnt = 0;
						if(!empty($arm_gm_plans) && !empty($arm_gm_sub_user_slot))
						{
							foreach($arm_gm_plans as $arm_gm_keys => $arm_gm_vals)
							{
								$arm_gm_plan_obj = new ARM_Plan($arm_gm_vals);
								$arm_gm_plan_options = !empty($arm_gm_plan_obj->options) ? $arm_gm_plan_obj->options : '';
								if(!empty($arm_gm_plan_options['arm_gm_enable_referral']) && ($arm_gm_plan_options['arm_gm_enable_referral']))
								{
									// Generate New Coupons
									for($arm_sub_user_cnt = 1;$arm_sub_user_cnt <= $arm_gm_sub_user_slot[$cnt]; $arm_sub_user_cnt++)
									{
										$arm_gm_generate_random_coupon_code['arm_coupon_code'] = $this->arm_gm_generate_random_code(12);
										$arm_gm_generate_random_coupon_code['arm_group_parent_user_id'] = $user_ID;
										$arm_gm_generate_random_coupon_code['arm_coupon_discount'] = 100;
										$arm_gm_generate_random_coupon_code['arm_coupon_discount_type'] = 'percentage';
										$arm_gm_generate_random_coupon_code['arm_coupon_period_type'] = 'unlimited';
										$arm_gm_generate_random_coupon_code['arm_coupon_allowed_uses'] = 1;
										$arm_gm_generate_random_coupon_code['arm_coupon_status'] = 1;
										$arm_gm_generate_random_coupon_code['arm_coupon_subscription'] = $arm_gm_vals;
										$arm_gm_generate_random_coupon_code['arm_coupon_start_date'] = date('Y-m-d H:i:s');
										$arm_gm_generate_random_coupon_code['arm_coupon_expire_date'] = date('Y-m-d H:i:s');
										$ins = $wpdb->insert($ARMember->tbl_arm_coupons, $arm_gm_generate_random_coupon_code);
									}


									//Update User Meta with old value 
									$arm_gm_tmp_old_value = get_user_meta($user_ID, 'gm_sub_user_select_'.$arm_gm_vals, true);
									$arm_gm_old_value = $arm_gm_tmp_old_value[0];

									$arm_gm_new_seat_value = $arm_gm_old_value + $arm_gm_sub_user_slot[$cnt];

									update_user_meta($user_ID, 'gm_sub_user_select_'.$arm_gm_vals, $arm_gm_new_seat_value);
								}

								$cnt++;
							}
						}
					}
				}
			}


			function arm_gm_add_membership_child_user_option($content, $plan_id)
			{
				global $ARMember, $wpdb, $wp, $arm_global_settings;

				$plan_data = new ARM_Plan($plan_id);
				$arm_gm_plan_options = $plan_data->options;
				$arm_gm_enable_referral = $arm_gm_plan_options['arm_gm_enable_referral'];
				$arm_gm_max_members = $arm_gm_plan_options['arm_gm_max_members'];
				$arm_gm_min_members = $arm_gm_plan_options['arm_gm_min_members'];
				$arm_gm_sub_user_seat_slot = $arm_gm_plan_options['arm_gm_sub_user_seat_slot'];

				if($arm_gm_enable_referral)
				{
					if(empty($arm_gm_sub_user_seat_slot))
					{
						$arm_gm_sub_user_seat_slot = 1;
					}
					$classes = 'arm_child_user_selection_area';
					$classes1 = 'arm_add_plan_filter_label arm_choose_payment_cycle_label';
					$classes2 = '';
					if ($_POST['arm_manage_plan_grid'] == 1) {
						$classes = '';
						$classes1 = 'arm_edit_plan_lbl';
						$classes2 = 'arm_edit_field';
					}

					$content .= '<div class="armclear"></div>';
					$content .= '<div class="arm_plan_modal_design '.$classes.'">';
					$content .= '<span class="'.$classes1.'">'.__('Select Child user seats', 'ARMGroupMembership').'</span>';

					$content .= '<div class="arm_setup_option_input arm_setup_forms_container '.$classes2.'">';
					$content .= '<div class="arm_setup_module_box">';
					$content .= '<input type="hidden" id="arm_gm_selected_user" name="arm_gm_selected_user[]" value="'.$arm_gm_min_members.'" data-msg-required="'.__('Please select at least one user.', 'ARMGroupMembership').'" />';
					$content .= '<dl class="arm_selectbox column_level_dd arm_member_form_dropdown">';
					$content .= '<dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>';
					$content .= '<dd>';
					$content .= '<ul data-id="arm_gm_selected_user" class="arm_setup_form_options_list">';

					for($arm_gm_i=$arm_gm_min_members; $arm_gm_i<=$arm_gm_max_members; $arm_gm_i=$arm_gm_i+$arm_gm_sub_user_seat_slot)
					{
						$content .= '<li data-label="'.$arm_gm_i.'" data-value="'.$arm_gm_i.'">'.$arm_gm_i.'</li>';
					}

					$content .= '</ul>';
					$content .= '</dd>';
					$content .= '</dl>';
					$content .= '</div>';
					$content .= '</div>';
					$content .= '</div>';
					$content .= '<div class="armclear"></div>';
				}

				return $content;
			}


			function arm_gm_save_page_settings($new_global_settings, $post_data)
			{
				$new_global_settings['page_settings']['child_user_signup_page_id'] = $post_data['arm_page_settings']['child_user_signup_page_id'];
				return $new_global_settings;
			}

			function arm_gm_add_page_setup()
			{
				global $wpdb, $ARMember, $arm_global_settings;
				$arm_all_global_settings = $arm_global_settings->arm_get_all_global_settings();
				$page_settings = $arm_all_global_settings['page_settings'];

				ob_start();
				require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/admin/arm_gm_page_setup_section.php');
				$content = ob_get_clean();
				echo $content;
			}



			function arm_gm_delete_parent_user()
			{
				global $wpdb, $ARMember, $arm_global_settings, $arm_slugs;

				$arm_gm_parent_user_id = $_POST['arm_gm_parent_user_delete'];

				//Fetch Child Users Data
				$arm_gm_child_users_data = array();
				$arm_gm_fetch_used_coupons_qry = "SELECT arm_coupon_code FROM {$ARMember->tbl_arm_coupons} WHERE arm_group_parent_user_id = {$arm_gm_parent_user_id} AND arm_coupon_used = '1'";
				$arm_gm_fetch_used_coupons_data = $wpdb->get_results($arm_gm_fetch_used_coupons_qry);

				foreach($arm_gm_fetch_used_coupons_data as $arm_gm_coupon_key => $arm_gm_coupon_vals)
				{
					$arm_gm_tmp_child_users_list = get_users(array(
						'meta_key' => 'arm_coupon_code',
						'meta_value' => $arm_gm_coupon_vals->arm_coupon_code,
						'meta_compare' => '=',
					));
					array_push($arm_gm_child_users_data, $arm_gm_tmp_child_users_list[0]->ID);
				}


				//Delete Coupon Data
				$arm_gm_delete_coupon_data = "DELETE FROM {$ARMember->tbl_arm_coupons} WHERE arm_group_parent_user_id = {$arm_gm_parent_user_id}";
				$arm_gm_delete_coupon = $wpdb->query($wpdb->prepare($arm_gm_delete_coupon_data));

				//Delete Child Users
				if(!empty($arm_gm_child_users_data))
				{
					foreach($arm_gm_child_users_data as $child_user_key => $child_user_val)
					{
						wp_delete_user($child_user_val);
					}
				}


				//Delete Parent User Data
				//wp_delete_user($arm_gm_parent_user_id);

				echo 1;
				exit();
			}



			function get_arm_gm_sub_user_table_data()
			{
				global $wpdb, $ARMember, $arm_global_settings, $arm_slugs;
				$arm_gm_parent_user_id = $_REQUEST['arm_gm_parent_user_id'];

				$offset = isset( $_POST['iDisplayStart'] ) ? $_POST['iDisplayStart'] : 0;
	            $limit = isset( $_POST['iDisplayLength'] ) ? $_POST['iDisplayLength'] : 10;

	            $search_term = ( isset( $_POST['sSearch'] ) && '' != $_POST['sSearch'] ) ? true : false;

	            $search_query = '';
	            
	            $sortOrder = isset( $_POST['sSortDir_0'] ) ? $_POST['sSortDir_0'] : 'DESC';

	            $orderBy = '';
	            

	            $arm_gm_child_users_data = array();
	            $arm_gm_child_users_coupon_data = array();
				$arm_gm_fetch_used_coupons_qry = "SELECT arm_coupon_id,arm_coupon_code FROM {$ARMember->tbl_arm_coupons} WHERE arm_group_parent_user_id = {$arm_gm_parent_user_id} AND arm_coupon_used = 1 {$search_query} {$orderBy} LIMIT {$offset}, {$limit}";
				$arm_gm_fetch_used_coupons_data = $wpdb->get_results($arm_gm_fetch_used_coupons_qry);

				foreach($arm_gm_fetch_used_coupons_data as $arm_gm_coupon_key => $arm_gm_coupon_vals)
				{
					array_push($arm_gm_child_users_coupon_data, $arm_gm_coupon_vals->arm_coupon_code);
					$tbl_arm_child_users = $wpdb->prefix.'arm_gm_child_users_status';
					$arm_gm_fetch_used_coupons_user_qry = "SELECT arm_gm_email_id FROM {$tbl_arm_child_users} WHERE arm_gm_invite_code_id = '{$arm_gm_coupon_vals->arm_coupon_id}'";
					$arm_gm_fetch_used_coupons_user_data = $wpdb->get_results($arm_gm_fetch_used_coupons_user_qry);
					foreach ($arm_gm_fetch_used_coupons_user_data as $arm_gm_user_key => $arm_gm_user_vals) {
						array_push($arm_gm_child_users_data, $arm_gm_user_vals->arm_gm_email_id);
					}
				}


				$arm_gm_fetch_used_coupons_count = "SELECT COUNT(arm_coupon_id) AS total FROM {$ARMember->tbl_arm_coupons} WHERE arm_group_parent_user_id = {$arm_gm_parent_user_id} AND arm_coupon_used = '1' {$orderBy}";
				$arm_gm_fetch_used_coupons_total = $wpdb->get_results($arm_gm_fetch_used_coupons_count);
	            $total_arm_gm_data = $arm_gm_fetch_used_coupons_total[0]->total;
	                                              
	            $grid_data = array();
	            $ai = 0;
	            if( !empty( $arm_gm_child_users_data )){
	                foreach ($arm_gm_child_users_data as $key => $arm_gm_val) {
	                    $arm_gm_user_data = get_user_by('email', $arm_gm_val);

						if (isset($arm_gm_user_data->data)) {
		                    $grid_data[$ai][] = $arm_gm_user_data->data->ID;
		                    $grid_data[$ai][] = $arm_gm_user_data->data->user_login;
		                    $grid_data[$ai][] = "<a href='".admin_url('admin.php?page=arm_manage_members&action=view_member&id='.$arm_gm_user_data->data->ID)."' target='_blank'>".$arm_gm_user_data->data->user_email."</a>";

		                    $arm_coupon_code = get_user_meta($arm_gm_user_data->data->ID, 'arm_coupon_code', true);
		                    $grid_data[$ai][] = '<span class="arm_gm_status_active">'.__('Active', 'ARMGroupMembership').'</span>';
		                    $grid_data[$ai][] = $arm_coupon_code;

		                    $gridAction = "<div class='arm_grid_action_btn_container'>";

		                    $gridAction .= "<a href='".admin_url('admin.php?page=arm_manage_members&action=edit_member&id='.$arm_gm_user_data->data->ID)."' target='_blank'><img src='".MEMBERSHIP_IMAGES_URL."/grid_edit.png' class='armhelptip' title='".esc_html__('Edit User','ARMGroupMembership')."' onmouseover=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_edit_hover.png';\" onmouseout=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_edit.png';\" /></a>";


		                    $gridAction .= "<a href='javascript:void(0)' onclick='showDeleteConfirmation({$arm_gm_user_data->data->ID});'><img src='".MEMBERSHIP_IMAGES_URL."/grid_delete.png' class='armhelptip' title='".esc_html__('Delete','ARMGroupMembership')."' onmouseover=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_delete_hover.png';\" onmouseout=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_delete.png';\" /></a>";

	                        $gridAction .= $arm_global_settings->arm_get_confirm_box($arm_gm_user_data->data->ID, esc_html__("Are you sure you want to delete child user?", 'ARMGroupMembership'), 'arm_gm_user_delete_btn');

	                        $gridAction .= "</div>";

	                    	$grid_data[$ai][] = '<div class="arm_grid_action_wrapper">'.$gridAction.'</div>';

		                } else {
		                	$arm_gm_get_pending_users_data = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."arm_gm_child_users_status WHERE arm_gm_email_id = '".$arm_gm_val."'");

		                	$arm_gm_delete_id = $arm_gm_get_pending_users_data->arm_gm_id;

		                	$grid_data[$ai][] = '-';
		                    $grid_data[$ai][] = '-';
		                    $grid_data[$ai][] = $arm_gm_val;

		                    $grid_data[$ai][] = '<span class="arm_gm_status_pending">'.__('Pending', 'ARMGroupMembership').'</span>';
		                    $grid_data[$ai][] = $arm_gm_child_users_coupon_data[$key];

		                    	$gridAction = "<div class='arm_grid_action_btn_container'>";
		                    	$gridAction .= "<a href='javascript:void(0)' onclick='showDeleteConfirmation({$arm_gm_delete_id});'><img src='".MEMBERSHIP_IMAGES_URL."/grid_delete.png' class='armhelptip' title='".esc_html__('Delete','ARMGroupMembership')."' onmouseover=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_delete_hover.png';\" onmouseout=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_delete.png';\" /></a>";
		                    	$gridAction .= $arm_global_settings->arm_get_confirm_box($arm_gm_delete_id, esc_html__("Are you sure you want to delete child user?", 'ARMGroupMembership'), 'arm_gm_pending_user_delete_btn');
		                    	$gridAction .= "</div>";

		                    $grid_data[$ai][] = '<div class="arm_grid_action_wrapper">'.$gridAction.'</div>';
		                }
	                    $ai++;
	                }
	            }

	            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
	            $after_filter = $total_arm_gm_data;
	            if( $search_term ){
	                $after_filter = $ai;
	            }

	            $response = array(
	                'sColumns' => implode(',',array('User ID','Username','Email')),
	                'sEcho' => $sEcho,
	                'iTotalRecords' => $total_arm_gm_data,
	                'iTotalDisplayRecords' => $after_filter,
	                'aaData' => $grid_data
	            );

	            echo json_encode( $response );
	            die;
			}


			function get_arm_gm_child_users_data()
			{
				global $wp, $wpdb, $ARMember;
				$arm_gm_parent_user_id = $_REQUEST['parent_user_id'];

				ob_start();
				require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/admin/arm_gm_child_users_listing.php');
				$content = ob_get_clean();
				echo $content;
				exit();
			}


			function get_arm_gm_admin_data()
			{
				global $wpdb, $ARMember, $arm_global_settings, $arm_slugs;
           
	            $offset = isset( $_POST['iDisplayStart'] ) ? $_POST['iDisplayStart'] : 0;
	            $limit = isset( $_POST['iDisplayLength'] ) ? $_POST['iDisplayLength'] : 10;

	            $search_term = ( isset( $_POST['sSearch'] ) && '' != $_POST['sSearch'] ) ? true : false;

	            $search_query = '';

	            if( $search_term )
	            {
	            	$search_query = " AND (t2.user_nicename LIKE '%".$_POST['sSearch']."%' OR t2.user_email LIKE '%".$_POST['sSearch']."%')";
	            }

	            $sortOrder = isset( $_POST['sSortDir_0'] ) ? $_POST['sSortDir_0'] : 'DESC';


	            $orderBy = '';

	            $arm_join = "LEFT JOIN {$wpdb->users} AS t2 ON t1.arm_group_parent_user_id = t2.ID";

	            $gm_members_query = "SELECT * FROM {$ARMember->tbl_arm_coupons} t1 {$arm_join} WHERE arm_group_parent_user_id IS NOT NULL {$search_query} {$orderBy} GROUP BY arm_group_parent_user_id LIMIT {$offset}, {$limit}";

	            $get_arm_gm_parent_user_ids = $wpdb->get_results( $gm_members_query );


	            $total_arm_gm_query =  "SELECT COUNT(arm_coupon_id) AS total FROM {$ARMember->tbl_arm_coupons} WHERE arm_group_parent_user_id IS NOT NULL {$orderBy} GROUP BY arm_group_parent_user_id";

	            $total_arm_gm_result = $wpdb->get_results( $total_arm_gm_query );
	            //$total_arm_gm_data = $total_arm_gm_result[0]->total;
	            $total_arm_gm_data = count($total_arm_gm_result);
	                                              
	            $grid_data = array();
	            $ai = 0;
	            if( !empty( $get_arm_gm_parent_user_ids )){
	                foreach ($get_arm_gm_parent_user_ids as $key => $arm_gm_val) {
	                    if( !isset($grid_data[$ai]) || !is_array( $grid_data[$ai] ) ){
	                        $grid_data[$ai] = array();
	                    }

	                    $grid_data[$ai][] = $arm_gm_val->arm_group_parent_user_id;
	                    $arm_gm_user_data = get_user_by('ID', $arm_gm_val->arm_group_parent_user_id);


	                    $grid_data[$ai][] = $arm_gm_user_data->data->user_login;

	                    $grid_data[$ai][] = "<td><a class='arm_openpreview_popup' href='javascript:void(0)' data-id='".$arm_gm_val->arm_group_parent_user_id."'>".$arm_gm_user_data->data->user_email."</a></td>";

	                    $arm_gm_fetch_child_users = "SELECT COUNT(arm_coupon_id) as total_childs FROM {$ARMember->tbl_arm_coupons} WHERE arm_group_parent_user_id = {$arm_gm_val->arm_group_parent_user_id} AND arm_coupon_used = 1";
	            		$arm_gm_total_child_users = $wpdb->get_row( $arm_gm_fetch_child_users );

	            		if (isset($arm_gm_total_child_users->total_childs) && $arm_gm_total_child_users->total_childs > 0) {
	                    	$grid_data[$ai][] = "<a href='javascript:void(0)' class='armhelptip arm_gm_child_users tipso_style' data-parent_id='".$arm_gm_val->arm_group_parent_user_id."' title='".__('View Child Users', 'ARMGroupMembership')."'>".$arm_gm_total_child_users->total_childs."</a>";
	            		} else {
	            			$grid_data[$ai][] = 0;
	            		}

	                    $arm_gm_total_invitation_coupons = "SELECT COUNT(arm_coupon_id) as total_coupons FROM {$ARMember->tbl_arm_coupons} WHERE arm_group_parent_user_id = {$arm_gm_val->arm_group_parent_user_id}";
	                    $arm_total_coupons = $wpdb->get_row($arm_gm_total_invitation_coupons);

	                    $grid_data[$ai][] = $arm_total_coupons->total_coupons;

	                    $grid_data[$ai][] = $arm_total_coupons->total_coupons - $arm_gm_total_child_users->total_childs;

	                    $gridAction = "<div class='arm_grid_action_btn_container'>";

	                    $gridAction .= "<a href='javascript:void(0)' onclick='showEditDataModal({$arm_gm_val->arm_group_parent_user_id})'><img src='".MEMBERSHIP_IMAGES_URL."/grid_edit.png' class='armhelptip' title='".esc_html__('Edit Group Membership','ARMGroupMembership')."' onmouseover=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_edit_hover.png';\" onmouseout=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_edit.png';\" /></a>";


	                    if($arm_total_coupons->total_coupons == $arm_gm_total_child_users->total_childs)
	                    {
	                    	$gridAction .= "<a href='javascript:void(0)' onclick='showSubUserConfirmation({$arm_gm_val->arm_coupon_id})'><img src='".MEMBERSHIP_IMAGES_URL."/change_status_icon.png' class='armhelptip' title='".esc_html__('Add Child User','ARMGroupMembership')."' onmouseover=\"this.src='".MEMBERSHIP_IMAGES_URL."/change_status_icon_hover.png';\" onmouseout=\"this.src='".MEMBERSHIP_IMAGES_URL."/change_status_icon.png';\" /></a>";

	                    	$gridAction .= $arm_global_settings->arm_get_confirm_box($arm_gm_val->arm_coupon_id, esc_html__("Maximum limit for adding child user is reached.", 'ARMGroupMembership'), 'arm_gm_child_user_add_confirmation');
	                    }
	                    else
	                    {
	                    	$gridAction .= "<a href='javascript:void(0)' onclick='showSubUserAddModal({$arm_gm_val->arm_group_parent_user_id})'><img src='".MEMBERSHIP_IMAGES_URL."/change_status_icon.png' class='armhelptip' title='".esc_html__('Add Child User','ARMGroupMembership')."' onmouseover=\"this.src='".MEMBERSHIP_IMAGES_URL."/change_status_icon_hover.png';\" onmouseout=\"this.src='".MEMBERSHIP_IMAGES_URL."/change_status_icon.png';\" /></a>";
	                    }


	                    

	                    $gridAction .= "<a href='javascript:void(0)' onclick='showDeleteConfirmation({$arm_gm_val->arm_group_parent_user_id});'><img src='".MEMBERSHIP_IMAGES_URL."/grid_delete.png' class='armhelptip' title='".esc_html__('Delete','ARMGroupMembership')."' onmouseover=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_delete_hover.png';\" onmouseout=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_delete.png';\" /></a>";

                        $gridAction .= $arm_global_settings->arm_get_confirm_box($arm_gm_val->arm_group_parent_user_id, esc_html__("Are you sure you want to delete parent user because all coupons and child users delete ?", 'ARMGroupMembership'), 'arm_gm_parent_user_delete_btn');

                        $gridAction .= "</div>";

                    	$grid_data[$ai][] = '<div class="arm_grid_action_wrapper">'.$gridAction.'</div>';

	                    $ai++;
	                }
	            }

	            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
	            $after_filter = $total_arm_gm_data;
	            if( $search_term ){
	                $after_filter = $ai;
	            }

	            $response = array(
	                'sColumns' => implode(',',array('User ID','Username','Email','Child Users', 'Total Invite Codes', 'Remaining Invite Codes')),
	                'sEcho' => $sEcho,
	                'iTotalRecords' => $total_arm_gm_data,
	                'iTotalDisplayRecords' => $after_filter,
	                'aaData' => $grid_data
	            );

	            echo json_encode( $response );
	            die;
			}



			function arm_gm_delete_child_users($user_id)
			{
				global $wpdb, $wp, $ARMember, $arm_global_settings;

				//Check requested user is parent user or not
				$arm_gm_invite_code_data = $wpdb->get_row($wpdb->prepare("SELECT COUNT(arm_coupon_id) as total_rec FROM `". $ARMember->tbl_arm_coupons ."` WHERE `arm_group_parent_user_id` = %d", $user_id));

				$arm_gm_invite_code_count = $arm_gm_invite_code_data->total_rec;

				if($arm_gm_invite_code_count > 0)
				{
					//Fetch Child Users Data
					$arm_gm_child_users_data = array();
					$arm_gm_fetch_used_coupons_qry = "SELECT arm_coupon_code FROM {$ARMember->tbl_arm_coupons} WHERE arm_group_parent_user_id = ".$user_id." AND arm_coupon_used = '1'";
					$arm_gm_fetch_used_coupons_data = $wpdb->get_results($arm_gm_fetch_used_coupons_qry);

					foreach($arm_gm_fetch_used_coupons_data as $arm_gm_coupon_key => $arm_gm_coupon_vals)
					{
						$arm_gm_tmp_child_users_list = get_users(array(
							'meta_key' => 'arm_coupon_code',
							'meta_value' => $arm_gm_coupon_vals->arm_coupon_code,
							'meta_compare' => '=',
						));
						array_push($arm_gm_child_users_data, $arm_gm_tmp_child_users_list[0]->ID);
					}

					//Delete Child Users
					if(!empty($arm_gm_child_users_data))
					{
						foreach($arm_gm_child_users_data as $child_user_key => $child_user_val)
						{
							wp_delete_user($child_user_val);
						}
					}

					//Delete Coupon Data
					$arm_gm_delete_coupon_data = "DELETE FROM {$ARMember->tbl_arm_coupons} WHERE arm_group_parent_user_id = ".$user_id;
					$arm_gm_delete_coupon = $wpdb->query($wpdb->prepare($arm_gm_delete_coupon_data));
				}
			}


			function arm_gm_delete_child_member()
			{
				global $wpdb, $wp, $ARMember, $arm_global_settings;
				$arm_gm_delete_email = $_POST['delete_email'];
				$current_page = !empty($_POST['arm_gm_page_no']) ? $_POST['arm_gm_page_no'] : 1;
				$per_page = !empty($_POST['arm_gm_per_page']) ? $_POST['arm_gm_per_page'] : 5;
				$arm_gm_coupon_id = !empty($_POST['arm_gm_coupon_id']) ? $_POST['arm_gm_coupon_id'] : 0;

				//Delete Record from status table
				$arm_delete_status_record = $wpdb->query("DELETE FROM ".$wpdb->prefix."arm_gm_child_users_status WHERE arm_gm_email_id = '".$arm_gm_delete_email."' AND arm_gm_invite_code_id = ".$arm_gm_coupon_id."");

				$arm_gm_user_details = get_user_by('email', $arm_gm_delete_email);
				$arm_gm_user_id = $arm_gm_user_details->data->ID;

				//Delete Old Invite Code and Generate New Invite Code
				$arm_gm_new_coupon = $this->arm_gm_generate_random_code(12);

				//$arm_gm_coupon_code = get_user_meta($arm_gm_user_id, 'arm_coupon_code', true);
				if(!empty($arm_gm_coupon_id))
				{
					$arm_gm_update_coupon = $wpdb->update($ARMember->tbl_arm_coupons, array('arm_coupon_code' => $arm_gm_new_coupon, 'arm_coupon_used' => 0), array('arm_coupon_id' => $arm_gm_coupon_id));
				}

				wp_delete_user($arm_gm_user_id);


				global $wp, $wpdb, $current_user, $arm_slugs, $ARMember, $arm_global_settings, $arm_shortcodes, $arm_member_forms;

		    	$arm_gm_current_user_id = get_current_user_id();


		    	$arm_gm_child_users_list = $wpdb->get_results($wpdb->prepare("SELECT arm_coupon_id, arm_coupon_code FROM `". $ARMember->tbl_arm_coupons ."` WHERE `arm_group_parent_user_id` = %d AND `arm_coupon_used` = %d", $arm_gm_current_user_id, 1));

				$arm_gm_child_users = array();

				foreach($arm_gm_child_users_list as $arm_gm_child_user_key => $arm_gm_child_user_value)
				{
					$arm_gm_tmp_child_users_list = get_users(array(
						'meta_key' => 'arm_coupon_code',
						'meta_value' => $arm_gm_child_user_value->arm_coupon_code,
						'meta_compare' => '=',
					));

					$child_user_plan_id = get_user_meta($arm_gm_tmp_child_users_list[0]->ID, 'arm_user_plan_ids', true);
					$arm_gm_suspended_plan_ids = get_user_meta($arm_gm_tmp_child_users_list[0]->ID, 'arm_user_suspended_plan_ids', true);
					if(!empty($child_user_plan_id) && is_array($child_user_plan_id)){
                        foreach($child_user_plan_id as $arm_gm_plan){
                            if(in_array($arm_gm_plan, $arm_gm_suspended_plan_ids)){
                                unset($child_user_plan_id[array_search($arm_gm_plan, $child_user_plan_id)]);
                            }
                        }
                    }

					$arm_gm_tmp_child_users_list[0]->coupon_code = $arm_gm_child_user_value->arm_coupon_code;
					$arm_gm_tmp_child_users_list[0]->coupon_id = $arm_gm_child_user_value->arm_coupon_id;
					$arm_gm_tmp_child_users_list[0]->plan_id = $child_user_plan_id[0];

					array_push($arm_gm_child_users, $arm_gm_tmp_child_users_list[0]);
				}
		    	exit();
			}


			function arm_gm_filter_shortcode_content($content, $coupon_code = '', $coupon_id = '')
			{
				global $wp, $wpdb, $ARMember, $arm_global_settings;

				if(!empty($content) && !empty($coupon_code) && !empty($coupon_id))
				{
					$arm_gm_coupon_list = $wpdb->get_row($wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_id`=%d", $coupon_id));

					$arm_gm_coupon_code = $arm_gm_coupon_list->arm_coupon_code;
					if($arm_gm_coupon_code == $coupon_code)
					{
						if(strpos($content, '{ARM_GM_INVITE_COUPON_CODE}'))
						{
							$content = str_replace('{ARM_GM_INVITE_COUPON_CODE}', $coupon_code, $content);
						}
					}


					if(strpos($content, '{ARM_GM_INVITE_PAGE}'))
					{
						$arm_all_global_settings = $arm_global_settings->arm_get_all_global_settings();
						$page_settings = $arm_all_global_settings['page_settings'];
						$child_user_signup_page_id = isset($page_settings['child_user_signup_page_id']) ? $page_settings['child_user_signup_page_id'] : 0;

						$arm_gm_child_page_link = get_permalink($child_user_signup_page_id).'?arm_invite_code='.$arm_gm_coupon_code;

						$content = str_replace('{ARM_GM_INVITE_PAGE}', $arm_gm_child_page_link, $content);
					}
				}
				return $content;
			}


			function arm_gm_email_notification_shortcodes_func($shortcode_array)
			{
				$shortcode_array[] = array (
                    'title_on_hover' => __("To Display Group Membership Invited User Code", 'ARMGroupMembership'),
                    'shortcode' => '{ARM_GM_INVITE_COUPON_CODE}',
                    'shortcode_label' => __("Child User Invite Code", 'ARMGroupMembership')
                );

                $shortcode_array[] = array(
                	'title_on_hover' => __("To Display Group Membership Invited Page URL with Invite Code", 'ARMGroupMembership'),
                    'shortcode' => '{ARM_GM_INVITE_PAGE}',
                    'shortcode_label' => __("Child User Signup Invite URL", 'ARMGroupMembership')
                );

                $shortcode_array[] = array(
                	'title_on_hover' => __("To Display Group Membership Invited Parent User", "ARMGroupMembership"),
                	'shortcode' => '{ARM_GM_PARENT_USER_USERNAME}',
                	'shortcode_label' => __("Parent User", "ARMGroupMembership")
                );

            	return $shortcode_array;
			}


			function arm_gm_set_notification_message_type($message_types)
			{
				$message_types['arm_gm_on_invite_user_as_group_member'] = __('On Invite User For Group Membership', 'ARMGroupMembership');
				$message_types['arm_gm_send_signup_notification_to_parent'] = __('Send Group Membership Signup to Parent User', 'ARMGroupMembership');
				$message_types['arm_gm_send_signup_notification_to_child'] = __('Send Group Membership Signup to Child User', 'ARMGroupMembership');
				return $message_types;
			}


			function arm_gm_set_notification_list_msg_type($message_types)
			{
				if($message_types == "arm_gm_on_invite_user_as_group_member")
				{
					return __('On Invite User For Group Membership', 'ARMGroupMembership');
				}
				else if($message_types == "arm_gm_send_signup_notification_to_parent")
				{
					return __('Send Group Membership Signup to Parent User', 'ARMGroupMembership');
				}
				else if($message_types == "arm_gm_send_signup_notification_to_child")
				{
					return __('Send Group Membership Signup to Child User', 'ARMGroupMembership');
				}

				return $message_types;
			}


			function arm_gm_invite_users_coupon()
			{
				global $wpdb, $ARMember, $arm_manage_communication, $arm_global_settings;
				$common_messages = $arm_global_settings->arm_get_all_common_message_settings();

				$current_page = !empty($_POST['arm_gm_page_no']) ? $_POST['arm_gm_page_no'] : 1;
				$per_page = !empty($_POST['arm_gm_per_page']) ? $_POST['arm_gm_per_page'] : 5;
				$arm_gm_invited_emails = explode(',' ,$_POST['invited_emails']);

				$arm_gm_current_user_id = get_current_user_id();
				$arm_gm_max_limit = $wpdb->get_var("SELECT COUNT(*) FROM ".$ARMember->tbl_arm_coupons." WHERE arm_group_parent_user_id = ".$arm_gm_current_user_id."");

				if(is_user_logged_in())
				{
					$user_info = get_userdata($arm_gm_current_user_id);
					$user_login = $user_info->user_login;

					$user_plans = get_user_meta($arm_gm_current_user_id, 'arm_user_plan_ids', true);
					$arm_gm_suspended_plan_ids = get_user_meta($arm_gm_current_user_id, 'arm_user_suspended_plan_ids', true);
					if(!empty($user_plans) && is_array($user_plans)){
                        foreach($user_plans as $arm_gm_plan){
                            if(in_array($arm_gm_plan, $arm_gm_suspended_plan_ids)){
                                unset($user_plans[array_search($arm_gm_plan, $user_plans)]);
                            }
                        }
                    }

            		$user_plan = isset($user_plans) && !empty($user_plans) ? implode(',', $user_plans) : 0;

					$message_type = 'arm_gm_on_invite_user_as_group_member';

					$messages = $wpdb->get_results("SELECT `arm_message_subject`, `arm_message_content` , `arm_message_send_copy_to_admin`, `arm_message_send_diff_msg_to_admin`, `arm_message_admin_message` FROM `" . $ARMember->tbl_arm_auto_message . "` WHERE `arm_message_status`='1' AND `arm_message_type`='" . $message_type . "' AND (FIND_IN_SET(" . $user_plan . ", `arm_message_subscription`) OR (`arm_message_subscription`=''))");

					if(count($messages) == 0)
					{
						$error_msg = __('Email Notification disabled from admin. Kindly contact to site admin for enable it', 'ARMGroupMembership');
        				$response = array('status' => 'error', 'type' => 'error', 'message' => $error_msg);
        				echo json_encode($response);
        				exit();	
					}
				}


				if(count($arm_gm_invited_emails) > $arm_gm_max_limit)
				{
					$arm_max_difference = count($arm_gm_invited_emails) - $arm_gm_max_limit;
					for($k=1;$k<=$arm_max_difference;$k++)
					{
						array_pop($arm_gm_invited_emails);
					}
				}
				
				$arm_gm_coupon_availability_check = $wpdb->get_row($wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_used`=%d AND `arm_group_parent_user_id`=%d", 0, $arm_gm_current_user_id));

				if(empty($arm_gm_coupon_availability_check))
				{
					$error_msg = __('No Invite Code remains to Invite user', 'ARMGroupMembership');
    				$response = array('status' => 'error', 'type' => 'message', 'message' => $error_msg);
    				echo json_encode($response);
    				exit();
				}

				$arm_gm_invite_coupon_arr = array();

				foreach($arm_gm_invited_emails as $arm_gm_email_key => $arm_gm_email_value)
				{
					if(empty(trim($arm_gm_email_value)))
					{
						unset($arm_gm_invited_emails[$arm_gm_email_key]);
					}

					//Insert data into default status table.
					$arm_gm_coupon_availability_check = $wpdb->get_row($wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_used`=%d AND `arm_group_parent_user_id`=%d", 0, $arm_gm_current_user_id));

					$arm_gm_coupon_id = !empty($arm_gm_coupon_availability_check->arm_coupon_id) ? $arm_gm_coupon_availability_check->arm_coupon_id : 0;
					$arm_gm_coupon_code = !empty($arm_gm_coupon_availability_check->arm_coupon_code) ? $arm_gm_coupon_availability_check->arm_coupon_code : 0 ;
					$arm_gm_parent_user_id = !empty($arm_gm_coupon_availability_check->arm_group_parent_user_id) ? $arm_gm_coupon_availability_check->arm_group_parent_user_id : 0;

					$arm_gm_user_id = 0;
					$arm_gm_invite_code_id = $arm_gm_coupon_id;

					$wpdb->query("INSERT INTO ".$wpdb->prefix."arm_gm_child_users_status (arm_gm_email_id, arm_gm_user_id, arm_gm_parent_user_id, arm_gm_invite_code_id) VALUES('".$arm_gm_email_value."', ".$arm_gm_user_id.", ".$arm_gm_parent_user_id.", ".$arm_gm_invite_code_id.")");

					$arm_gm_update_coupon_status = ['arm_coupon_used' => 1];
					$arm_gm_where_update = ['arm_coupon_id' => $arm_gm_coupon_id];
					$wpdb->update($ARMember->tbl_arm_coupons, $arm_gm_update_coupon_status, $arm_gm_where_update);

					array_push($arm_gm_invite_coupon_arr, $arm_gm_coupon_code);
				}

				
				if(!empty($arm_gm_invited_emails) && is_user_logged_in())
				{
					

					$is_sent_to_admin = 0;
					$is_mail_sent = true;
					$arm_coupon_value_counter = 0;
					foreach($arm_gm_invited_emails as $arm_gm_invite_key => $arm_gm_invite_val)
					{
						$arm_gm_coupon_code = $arm_gm_invite_coupon_arr[$arm_coupon_value_counter];
						$arm_gm_invite_val = trim($arm_gm_invite_val);
						if(!empty($messages))
						{
							foreach($messages as $msg)
							{
								$content_subject = $msg->arm_message_subject;
                                $content_description = $msg->arm_message_content;
                                $send_one_copy_to_admin = $msg->arm_message_send_copy_to_admin;
                                $send_diff_copy_to_admin = $msg->arm_message_send_diff_msg_to_admin;
                                $admin_content_description = $msg->arm_message_admin_message;

                                $subject = $arm_manage_communication->arm_filter_communication_content($content_subject, $arm_gm_current_user_id, $user_plan, '');
                                $subject = $this->arm_gm_filter_shortcode_content($subject, $arm_gm_coupon_code, $arm_gm_coupon_id);

                                $message = $arm_manage_communication->arm_filter_communication_content($content_description, $arm_gm_current_user_id, $user_plan, '');
                                $message = $this->arm_gm_filter_shortcode_content($message, $arm_gm_coupon_code, $arm_gm_coupon_id);

                                $admin_message = $arm_manage_communication->arm_filter_communication_content($admin_content_description, $arm_gm_current_user_id, $user_plan, '');
                                $admin_message = $this->arm_gm_filter_shortcode_content($admin_message, $arm_gm_coupon_code, $arm_gm_coupon_id);

                                $send_mail = $arm_global_settings->arm_wp_mail('', $arm_gm_invite_val, $subject, $message);


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

                                if($send_mail)
                                {
                                	$is_mail_sent = true;
                                }
                                else
                                {
                                	$is_mail_sent = false;
                                }
                                
							}
						}
						$arm_coupon_value_counter++;
						$is_sent_to_admin = 1;
					}

					$arm_gm_user_invitation_success_msg = !empty($common_messages['arm_gm_invite_sent_success_msg']) ? $common_messages['arm_gm_invite_sent_success_msg'] : __('Invitation Sent Successfully', 'ARMGroupMembership') ;
                	$success_msg = $arm_gm_user_invitation_success_msg;
    				$response = array('status' => 'success', 'type' => 'message', 'message' => $success_msg);
    				echo json_encode($response);
    				exit();
				}
				
				return true;
			}



			function arm_gm_invite_resend_email()
			{
				global $wpdb, $ARMember, $arm_manage_communication, $arm_global_settings;

				$current_page = !empty($_POST['arm_gm_page_no']) ? $_POST['arm_gm_page_no'] : 1;
				$per_page = !empty($_POST['arm_gm_per_page']) ? $_POST['arm_gm_per_page'] : 5;
				$arm_gm_invite_val = trim($_POST['arm_gm_resend_email']);
				$arm_gm_coupon_id = !empty($_POST['arm_gm_coupon_id']) ? $_POST['arm_gm_coupon_id'] : 0;

				$arm_gm_coupon_availability_check = $wpdb->get_row($wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_id`=%d", $arm_gm_coupon_id));

				if(empty($arm_gm_coupon_availability_check))
				{
					$error_msg = __('Coupon is Expired.', 'ARMGroupMembership');
    				$response = array('status' => 'error', 'type' => 'message', 'message' => $error_msg);
    				echo json_encode($response);
    				exit();
				}

				$arm_gm_coupon_id = $arm_gm_coupon_availability_check->arm_coupon_id;
				$arm_gm_coupon_code = $arm_gm_coupon_availability_check->arm_coupon_code;

				if(!empty($arm_gm_invite_val) && is_user_logged_in())
				{
					$current_user_id = get_current_user_id();
					$user_info = get_userdata($current_user_id);
					$user_login = $user_info->user_login;

					$user_plans = get_user_meta($current_user_id, 'arm_user_plan_ids', true);
					$arm_gm_suspended_plan_ids = get_user_meta($current_user_id, 'arm_user_suspended_plan_ids', true);
					if(!empty($user_plans) && is_array($user_plans)){
                        foreach($user_plans as $arm_gm_plan){
                            if(in_array($arm_gm_plan, $arm_gm_suspended_plan_ids)){
                                unset($user_plans[array_search($arm_gm_plan, $user_plans)]);
                            }
                        }
                    }

            		$user_plan = isset($user_plans) && !empty($user_plans) ? implode(',', $user_plans) : 0;

					$message_type = 'arm_gm_on_invite_user_as_group_member';

					$messages = $wpdb->get_results("SELECT `arm_message_subject`, `arm_message_content` , `arm_message_send_copy_to_admin`, `arm_message_send_diff_msg_to_admin`, `arm_message_admin_message` FROM `" . $ARMember->tbl_arm_auto_message . "` WHERE `arm_message_status`='1' AND `arm_message_type`='" . $message_type . "' AND (FIND_IN_SET(" . $user_plan . ", `arm_message_subscription`) OR (`arm_message_subscription`=''))");

					if(count($messages) == 0)
					{
						$error_msg = __('Email Notification disabled from admin. Kindly contact to site admin for enable it', 'ARMGroupMembership');
        				$response = array('status' => 'error', 'type' => 'error', 'message' => $error_msg);
        				echo json_encode($response);
        				exit();	
					}

					$is_sent_to_admin = 0;
					$is_mail_sent = true;
					
					$arm_gm_invite_val = trim($arm_gm_invite_val);
					if(!empty($messages))
					{
						foreach($messages as $msg)
						{
							$content_subject = $msg->arm_message_subject;
                            $content_description = $msg->arm_message_content;
                            $send_one_copy_to_admin = $msg->arm_message_send_copy_to_admin;
                            $send_diff_copy_to_admin = $msg->arm_message_send_diff_msg_to_admin;
                            $admin_content_description = $msg->arm_message_admin_message;

                            $subject = $arm_manage_communication->arm_filter_communication_content($content_subject, $current_user_id, $user_plan, '');
                            $subject = $this->arm_gm_filter_shortcode_content($subject, $arm_gm_coupon_code, $arm_gm_coupon_id);

                            $message = $arm_manage_communication->arm_filter_communication_content($content_description, $current_user_id, $user_plan, '');
                            $message = $this->arm_gm_filter_shortcode_content($message, $arm_gm_coupon_code, $arm_gm_coupon_id);

                            $admin_message = $arm_manage_communication->arm_filter_communication_content($admin_content_description, $current_user_id, $user_plan, '');
                            $admin_message = $this->arm_gm_filter_shortcode_content($admin_message, $arm_gm_coupon_code, $arm_gm_coupon_id);

                            $send_mail = $arm_global_settings->arm_wp_mail('', $arm_gm_invite_val, $subject, $message);


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

                            if($send_mail)
                            {
                            	$is_mail_sent = true;
                            }
                            else
                            {
                            	$is_mail_sent = false;
                            }
                            
						}
					}
					$is_sent_to_admin = 1;
					

                	$success_msg = __('Invitation Sent Successfully.', 'ARMGroupMembership');
    				$response = array('status' => 'success', 'type' => 'message', 'message' => $success_msg);
    				echo json_encode($response);
    				exit();
				}
				
				return true;
			}


			function arm_gm_refresh_coupon()
			{
				global $wp, $wpdb, $current_user, $arm_slugs, $ARMember, $arm_global_settings, $arm_shortcodes, $arm_member_forms;
				$arm_coupon_code_id = !empty($_POST['arm_gm_coupon_id']) ? $_POST['arm_gm_coupon_id'] : 0;

				$arm_gm_new_coupon = $this->arm_gm_generate_random_code(12);
				$arm_gm_update_coupon = $wpdb->update($ARMember->tbl_arm_coupons, array('arm_coupon_code' => $arm_gm_new_coupon), array('arm_coupon_id' => $arm_coupon_code_id));
		        exit();
			}


			function arm_gm_invite_code_pagination()
			{
				global $wp, $wpdb, $current_user, $arm_slugs, $ARMember, $arm_global_settings, $arm_shortcodes, $arm_member_forms;

				$current_page = !empty($_POST['arm_gm_page_no']) ? $_POST['arm_gm_page_no'] : 1;
				$per_page = !empty($_POST['arm_gm_per_page']) ? $_POST['arm_gm_per_page'] : 5;

				$pagination_type = !empty($_POST['pagination_type']) ? $_POST['pagination_type'] : '';

				$display_delete_button = !empty($_POST['display_delete_button']) ? $_POST['display_delete_button'] : '';
				$delete_button_text = !empty($_POST['delete_button_text']) ? $_POST['delete_button_text'] : '';
				$display_resend_email_button = !empty($_POST['display_resend_email_button']) ? $_POST['display_resend_email_button'] : '';
				$resend_email_button_text = !empty($_POST['resend_email_button_text']) ? $_POST['resend_email_button_text'] : '';
				$display_refresh_invite_code_button = !empty($_POST['display_refresh_invite_code_button']) ? $_POST['display_refresh_invite_code_button'] : '';

				$content = "";

		    	$arm_gm_current_user_id = get_current_user_id();
		    	
		    	$date_format = $arm_global_settings->arm_get_wp_date_format();

		    	$arm_gm_fetch_coupon_data = $wpdb->get_row($wpdb->prepare("SELECT COUNT(arm_coupon_id) as arm_coupon_count FROM ".$ARMember->tbl_arm_coupons." WHERE arm_group_parent_user_id = %d", array($arm_gm_current_user_id)));

				$arm_gm_max_coupon_count = $arm_gm_fetch_coupon_data->arm_coupon_count;

		    	if(!empty($arm_gm_current_user_id) && !current_user_can('administrator'))
		    	{
					if($this->arm_gm_is_child_user($arm_gm_current_user_id))
			    	{
			    		$content = '';
			    	}
			    	else
			    	{
			    		$content = "";

			    		$arm_gm_child_users = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."arm_gm_child_users_status WHERE arm_gm_parent_user_id = %d", array($arm_gm_current_user_id)));

			    		$offset = (!empty($current_page) && $current_page > 1) ? (($current_page - 1) * $per_page) : 0;
					    $child_users_count = count($arm_gm_child_users);
					    $child_users_list = array_slice($arm_gm_child_users, $offset, $per_page);

			    		ob_start();
				    	require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/arm_gm_child_member_refresh_list.php');
				    	$content .= ob_get_clean();
			    	}
				}
				else if(current_user_can('administrator'))
				{
					$content = "";
				}
				else
				{
					$default_login_form_id = $arm_member_forms->arm_get_default_form_id('login');
                	return do_shortcode("[arm_form id='$default_login_form_id' is_referer='1']");
				}

		        echo $content;
		        exit();
			}


			function arm_gm_other_tab_buttons_func()
			{
				global $wp, $wpdb, $ARMember;
				ob_start();
		    	require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/arm_gm_admin_shortcode_form_buttons.php');
		    	$gm_form_buttons_content = ob_get_clean();
		    	echo $gm_form_buttons_content;	
			}



			function arm_gm_other_section_option_form_fields()
			{
				global $wp, $wpdb, $ARMember, $arm_member_forms;
				$arm_gm_forms = $arm_member_forms->arm_get_all_member_forms('arm_form_id, arm_form_label, arm_form_type');
				ob_start();
		    	require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/arm_gm_admin_shortcode_form_fields.php');
		    	$gm_form_content = ob_get_clean();
		    	echo $gm_form_content;
			}


			function arm_gm_other_section_option_add_func()
			{
				$arm_gm_shortcode_option = "<li data-label='".__('Group Membership Details','ARMGroupMembership')."' data-value='arm_group_child_member_list'>".__('Group Membership Details','ARMGroupMembership' )."</li>";

				$arm_gm_shortcode_option .= "<li data-label='".__('Group Membership Parent Seat Count','ARMGroupMembership')."' data-value='arm_gm_parent_user_seat_counter'>".__('Group Membership Parent Seat Count','ARMGroupMembership' )."</li>";

				echo $arm_gm_shortcode_option;
			}


			function arm_group_child_member_list_func($atts, $content, $tag)
			{
				global $wp, $wpdb, $current_user, $arm_slugs, $ARMember, $arm_global_settings, $arm_shortcodes, $arm_member_forms;

				$arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();

				$ARMember->enqueue_angular_script(true);

				

				$arm_gm_group_membership_container_class = "arm_group_membership_container";
	            if($arm_check_is_gutenberg_page)
	            {
	                return;
	            }


	            $atts = shortcode_atts(array(
         			'custom_css' => '',
	                'message_no_record' => __('There is no invite code exists.', 'ARMGroupMembership'),
	                'current_page' => 0,
	                'per_page' => 5,
	                'form_id' => '',
	                'arm_gm_membership_field_username' => __('Username', 'ARMGroupMembership'),
	                'arm_gm_membership_field_email' => __('Email', 'ARMGroupMembership'),
	                'arm_gm_membership_field_name' => __('Name', 'ARMGroupMembership'),
	                'arm_gm_membership_field_status' => __('Invite Status', 'ARMGroupMembership'),
	                'arm_gm_membership_field_action' => __('Action', 'ARMGroupMembership'),
	                'popup_title' => __('Invite User', 'ARMGroupMembership'),
	                'popup_field_label' => __('Email Id For Invite User', 'ARMGroupMembership'),
	                'popup_button_text' => __('Send Invitation', 'ARMGroupMembership'),
	                'display_delete_button' => 'true',
	                'delete_button_text' => __('Delete', 'ARMGroupMembership'),
	                'delete_button_css' => '',
	                'delete_button_hover_css' => '',
	                'display_resend_email_button' => 'true',
	                'resend_email_button_text' => __('Resend', 'ARMGroupMembership'),
	                'resend_email_button_css' => '',
	                'resend_email_button_hover_css' => '',
	                'display_refresh_invite_code_button' => 'true',
         		), $atts, $tag);

         		extract($atts);

         		$arm_gm_current_user_id = get_current_user_id();
         		$user_form_id = !empty($form_id) ? $form_id : $arm_member_forms->arm_get_default_form_id('registration');
				$form = new ARM_Form('id', $user_form_id);
				$form_id = $form->ID;
				$form_settings = $form->settings;
				$form_style = $form_settings['style'];
				$form_style['button_position'] = (!empty($form_style['button_position'])) ? $form_style['button_position'] : 'left';
				 if(!empty($form_style['validation_type']) && $form_style['validation_type'] == 'standard') {
                    $form_style_class = " arm_standard_validation_type ";
                    $validation_pos = "bottom";
                }
                $validation_pos = !empty($form_style['validation_position']) ? $form_style['validation_position'] : 'bottom';

		    	
		    	$date_format = $arm_global_settings->arm_get_wp_date_format();

		    	$common_messages = $arm_global_settings->arm_get_all_common_message_settings();
		    	$arm_gm_user_delete_success_msg = !empty($common_messages['arm_gm_user_delete_msg']) ? $common_messages['arm_gm_user_delete_msg'] : __('Child User Deleted Successfully', 'ARMGroupMembership') ;
		    	$arm_gm_user_resend_email_msg = !empty($common_messages['arm_gm_resend_email_msg']) ? $common_messages['arm_gm_resend_email_msg'] : __('Email Sent Successfully', 'ARMGroupMembership') ;
		    	$arm_gm_user_refresh_invite_code_msg = !empty($common_messages['arm_gm_refresh_invite_code_msg']) ? $common_messages['arm_gm_refresh_invite_code_msg'] : __('Invite Code Refresh Successfully', 'ARMGroupMembership') ;
		    	$arm_gm_user_invitation_success_msg = !empty($common_messages['arm_gm_invite_sent_success_msg']) ? $common_messages['arm_gm_invite_sent_success_msg'] : __('Invitation Sent Successfully', 'ARMGroupMembership') ;

				$arm_gm_fetch_coupon_data = $wpdb->get_row($wpdb->prepare("SELECT COUNT(arm_coupon_id) as arm_coupon_count FROM ".$ARMember->tbl_arm_coupons." WHERE arm_group_parent_user_id = %d AND arm_coupon_used = %d", array($arm_gm_current_user_id, 0)));

				$arm_gm_max_coupon_count = $arm_gm_fetch_coupon_data->arm_coupon_count;

		    	if(!empty($arm_gm_current_user_id) && !current_user_can('administrator'))
		    	{
					if($this->arm_gm_is_child_user($arm_gm_current_user_id))
			    	{
			    		$content = '';
			    	}
			    	else
			    	{
					    $arm_gm_child_users = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."arm_gm_child_users_status WHERE arm_gm_parent_user_id = %d", array($arm_gm_current_user_id)));

					    $offset = (!empty($current_page) && $current_page > 1) ? (($current_page - 1) * $per_page) : 0;
					    $child_users_count = count($arm_gm_child_users);
					    $child_users_list = array_slice($arm_gm_child_users, $offset, $per_page);

				    	ob_start();
				    	require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/arm_gm_child_member_list.php');
				    	$content .= "<div class='gm_parent_wrapper_container'>";
				    	$content .= ob_get_clean();
				    	$content .= "</div>";

			    	}
				}
				else if(current_user_can('administrator'))
				{
					$content = "";
				}
				else
				{
					$default_login_form_id = $arm_member_forms->arm_get_default_form_id('login');
                	return do_shortcode("[arm_form id='$default_login_form_id' is_referer='1']");
				}


         		return do_shortcode($content);
			}


			function arm_add_new_coupn_field_body($filter_data, $rc)
			{
				global $arm_slugs;
				$arm_gm_parent_user_id = $rc->arm_group_parent_user_id;
				if(!empty($arm_gm_parent_user_id))
				{
					$arm_gm_user_details = new WP_User($arm_gm_parent_user_id);
					$view_link = admin_url("admin.php?page=" . $arm_slugs->manage_members . "&action=view_member&id=" . $arm_gm_parent_user_id);
					$filter_data .= '<td><a href="'.$view_link.'" target="_blank">'.$arm_gm_user_details->data->user_nicename.'</a></td>';
				}
				else
				{
					$filter_data .= '<td> - </td>';					
				}
				return $filter_data;
			}


			function arm_add_new_coupon_heading($arm_coupon_filter_heading)
			{
				$arm_coupon_filter_heading .= "<th>".__('Parent User', 'ARMGroupMembership')."</th>";
				return $arm_coupon_filter_heading;
			}


			function arm_gm_is_child_user($user_id)
			{	
				$arm_parent_user_id = get_user_meta($user_id, 'arm_parent_user_id', true);
				if(!empty($arm_parent_user_id))
				{
					$arm_gm_parent_user_id = $arm_parent_user_id['arm_parent_user_id'];
					if(!empty($arm_gm_parent_user_id))
					{
						return true;
					}
				}
				return false;
			}


			function add_group_membership_plan_fields($plan_options)
			{
				return $this->arm_load_plan_fields_view($plan_options);
			}


			function arm_load_plan_fields_view($plan_options)
			{
				$arm_gm_plan_type = !empty($plan_options['payment_type']) ? $plan_options['payment_type'] : '';
				$arm_gm_enable_referral = !empty($plan_options['arm_gm_enable_referral']) ? 1 : 0;
				$arm_gm_max_members = !empty($plan_options['arm_gm_max_members']) ? $plan_options['arm_gm_max_members'] : 1;
				$arm_gm_min_members = !empty($plan_options['arm_gm_min_members']) ? $plan_options['arm_gm_min_members'] : 1;
				$arm_gm_sub_user_seat_slot = !empty($plan_options['arm_gm_sub_user_seat_slot']) ? $plan_options['arm_gm_sub_user_seat_slot'] : 1;

				$arm_gm_hidden_class = (!$arm_gm_enable_referral) ? 'hidden_section' : '';


				require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/arm_group_membership.php');
			}


			function arm_save_group_membership_plan_fields($plan_options, $posted_data)
			{	
				$plan_options['arm_gm_enable_referral'] = !empty($posted_data['arm_subscription_plan_options']['arm_gm_group_membership_disable_referral']) ? 1 : 0;
				$plan_options['arm_gm_max_members'] = !empty($posted_data['gm_max_members']) ? $posted_data['gm_max_members'] : '';
				$plan_options['arm_gm_min_members'] = !empty($posted_data['gm_min_members']) ? $posted_data['gm_min_members'] : '';
				$plan_options['arm_gm_sub_user_seat_slot'] = !empty($posted_data['gm_sub_user_seat_slot']) ? $posted_data['gm_sub_user_seat_slot'] : '';

				return $plan_options;
			}


			function arm_gm_check_sub_user($content, $setupID, $setup_data)
			{
				if(is_user_logged_in() && (!is_admin()))
				{
					$arm_gm_user_id = get_current_user_id();
					$arm_check_child_user = $this->arm_gm_is_child_user($arm_gm_user_id);
					if($arm_check_child_user)
					{
						$content = __('Sorry! you are not allowed to purchase any membership plan because you are a child user.', 'ARMGroupMembership');
					}
				}
				return $content;
			}


		    function arm_gm_filter_membership_atts($atts)
		    {
		    	if(is_user_logged_in() && (!is_admin()))
				{
					$arm_gm_user_id = get_current_user_id();
					$arm_check_child_user = $this->arm_gm_is_child_user($arm_gm_user_id);
					if($arm_check_child_user)
					{
				    	$atts['display_cancel_button'] = false;
				    	$atts['display_update_card_button'] = false;
				    	$atts['display_renew_button'] = false;
				    }
				}
		    	return $atts;
		    }


		    function arm_gm_stop_sending_mail_notification($from, $recipient, $subject, $message, $attachments)
		    {
		    	if(is_user_logged_in() && (!is_admin()))
				{
					$arm_gm_user_id = get_current_user_id();
					$arm_check_child_user = $this->arm_gm_is_child_user($arm_gm_user_id);
					if($arm_check_child_user)
					{
						exit();
					}
				}
		    }


		    function arm_gm_signup_sub_user_selection_form($module_content, $setupID, $setup_data)
		    {
		    	global $wpdb, $ARMember, $arm_global_settings;

		    	$arm_gm_sub_user_selection_label = !empty($setup_data['setup_labels']['button_labels']['sub_user_selection_label']) ? $setup_data['setup_labels']['button_labels']['sub_user_selection_label'] : __('Select Child User', 'ARMGroupMembership');

		    	$arm_gm_plan_data = array();

		    	$arm_gm_setup_plans = $setup_data['arm_setup_modules']['modules']['plans'];
		    	foreach($arm_gm_setup_plans as $arm_gm_key => $arm_gm_plan_val)
		    	{
		    		$arm_gm_plan_obj = new ARM_Plan($arm_gm_plan_val);
		    		$arm_gm_plan_options = $arm_gm_plan_obj->options;
		    		if(!empty($arm_gm_plan_options['arm_gm_enable_referral']))
		    		{
			    		$arm_gm_plan_data[$arm_gm_plan_val]['arm_gm_enable_referral'] = $arm_gm_plan_options['arm_gm_enable_referral'];
			    		$arm_gm_plan_data[$arm_gm_plan_val]['arm_gm_max_members'] = $arm_gm_plan_options['arm_gm_max_members'];
			    		$arm_gm_plan_data[$arm_gm_plan_val]['arm_gm_min_members'] = $arm_gm_plan_options['arm_gm_min_members'];
			    		$arm_gm_plan_data[$arm_gm_plan_val]['arm_gm_sub_user_seat_slot'] = $arm_gm_plan_options['arm_gm_sub_user_seat_slot'];
			    	}
		    	}


		    	$arm_gm_form_fields = $wpdb->get_row("SELECT * FROM `" . $ARMember->tbl_arm_forms . "` WHERE `arm_form_id`='" . $setup_data['arm_setup_modules']['modules']['forms'] . "' ORDER BY `arm_form_id` ASC", ARRAY_A);

		    	$arm_gm_form_input_arr = maybe_unserialize($arm_gm_form_fields['arm_form_settings']);
		    	$arm_gm_form_input_style = $arm_gm_form_input_arr['style']['form_layout'];

		    	$arm_gm_div_class = '';
		    	if($arm_gm_form_input_style == 'writer')
		    	{
		    		//If input style is materialize then this class included.
		    		$arm_gm_div_class = 'arm_form_layout_writer';
		    	}

		    	ob_start();
		    	require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/arm_gm_signup_sub_user_selection_form.php');
		    	$module_content .= ob_get_clean();

		    	return $module_content;
		    }



		    function arm_gm_recalculate_plan_amount()
		    {
		    	global $wpdb, $ARMember, $arm_global_settings;

		    	$arm_gm_taxable_amount = $_POST['arm_gm_plan_amount'];
		   		$arm_gm_plan_amount = $_POST['arm_gm_plan_amount'];

		    	$arm_gm_global_settings = $arm_global_settings->global_settings;
		    	$arm_gm_tax_enable = $arm_gm_global_settings['enable_tax'];


		    	if($arm_gm_tax_enable)
		    	{
			    	$arm_gm_enable_tax_type = $arm_gm_global_settings['tax_type'];
			    	$arm_gm_tax_amount = $arm_gm_global_settings['tax_amount'];

			    	if($arm_gm_enable_tax_type == "country")
			    	{
			    		$arm_gm_selected_country = $_POST['arm_gm_selected_country'];
			    		$arm_gm_tax_countries = maybe_unserialize($arm_gm_global_settings['arm_tax_country_name']);
			    		$arm_gm_tax_countries_val = maybe_unserialize($arm_gm_global_settings['arm_country_tax_val']);
			    		$arm_gm_tax_country_default_val = $arm_gm_global_settings['arm_country_tax_default_val'];

			    		if(!empty($arm_gm_selected_country) && in_array($arm_gm_selected_country, $arm_gm_tax_countries))
			    		{
			    			$arm_gm_selected_country_key = array_search($arm_gm_selected_country, $arm_gm_tax_countries);
			    			$arm_gm_taxable_amount = number_format($arm_gm_plan_amount + ($arm_gm_plan_amount * ($arm_gm_tax_countries_val[$arm_gm_selected_country_key] / 100)), 2);

			    			echo $arm_gm_taxable_amount;
			    			exit();
			    		}
			    		else
			    		{
			    			$arm_gm_taxable_amount = number_format($arm_gm_plan_amount + ($arm_gm_plan_amount * ($arm_gm_tax_country_default_val / 100)), 2);

			    			echo $arm_gm_taxable_amount;
			    			exit();
			    		}
			    	}
			    	else
			    	{
			    		$arm_gm_tax_default_val = $arm_gm_global_settings['tax_amount'];

			    		$arm_gm_taxable_amount = number_format($arm_gm_plan_amount + ($arm_gm_plan_amount * ($arm_gm_tax_default_val / 100)), 2);

			    		echo $arm_gm_taxable_amount;
			    		exit();
			    	}
			    }

			    echo $arm_gm_taxable_amount;
			    exit();
		    }


		    function arm_gm_generate_random_code($length = 10)
			{
				$charLength = round($length * 0.8);
                $numLength = round($length * 0.2);
                $keywords = array(
                    array('count' => $charLength, 'char' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
                    array('count' => $numLength, 'char' => '0123456789')
                );
                $temp_array = array();
                foreach ($keywords as $char_set) {
                    for ($i = 0; $i < $char_set['count']; $i++) {
                        $temp_array[] = $char_set['char'][rand(0, strlen($char_set['char']) - 1)];
                    }
                }
                shuffle($temp_array);
                return implode('', $temp_array);
			}


			//Function to restrict update subscription data if parent user purchased same plan.
			function arm_gm_before_submit_form_data($setup_data, $post_data)
			{
				global $wpdb, $ARMember;
				if($post_data['arm_is_user_logged_in_flag'] && ($post_data['arm_user_old_plan'] == $post_data['subscription_plan']))
				{
					unset($setup_data['arm_created_date']);
				}
				return $setup_data;
			}


			function arm_gm_form_data_submit($user_id, $posted_register_data)
			{	
				global $wpdb, $ARMember, $arm_manage_coupons;
				$post_data = $posted_register_data;

				$arm_gm_subscription_plan = $post_data['subscription_plan'];
				if(!empty($post_data['gm_sub_user_select_'.$arm_gm_subscription_plan]) && !empty($user_id))
				{
					$arm_gm_plan_obj = new ARM_Plan($arm_gm_subscription_plan);
		    		$arm_gm_plan_options = $arm_gm_plan_obj->options;
		    		
		    		$arm_gm_plan_max_members = $arm_gm_plan_options['arm_gm_max_members'];
		    		$arm_gm_plan_min_members = $arm_gm_plan_options['arm_gm_min_members'];
		    		$arm_gm_plan_sub_user_seat_slot = $arm_gm_plan_options['arm_gm_sub_user_seat_slot'];

					$arm_gm_selected_sub_user = $post_data['gm_sub_user_select_'.$arm_gm_subscription_plan];

					$arm_gm_coupon_count = $wpdb->get_row($wpdb->prepare("SELECT COUNT('arm_coupon_id') as total_coupons FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_group_parent_user_id` = ".$user_id));

					$arm_gm_previous_purchased_user = $arm_gm_coupon_count->total_coupons;

					$arm_total_purchased_user = $arm_gm_previous_purchased_user + $arm_gm_selected_sub_user;

					update_user_meta($user_id, 'gm_max_members_'.$arm_gm_subscription_plan, $arm_gm_plan_max_members);
					update_user_meta($user_id, 'gm_min_members_'.$arm_gm_subscription_plan, $arm_gm_plan_min_members);
					update_user_meta($user_id, 'gm_sub_user_select_'.$arm_gm_subscription_plan, $arm_total_purchased_user);

					for($arm_sub_user_cnt = 1;$arm_sub_user_cnt <= $arm_gm_selected_sub_user; $arm_sub_user_cnt++)
					{
						$arm_gm_generate_random_coupon_code['arm_coupon_code'] = $this->arm_gm_generate_random_code(12);
						$arm_gm_generate_random_coupon_code['arm_group_parent_user_id'] = $user_id;
						$arm_gm_generate_random_coupon_code['arm_coupon_discount'] = 100;
						$arm_gm_generate_random_coupon_code['arm_coupon_discount_type'] = 'percentage';
						$arm_gm_generate_random_coupon_code['arm_coupon_period_type'] = 'unlimited';
						$arm_gm_generate_random_coupon_code['arm_coupon_allowed_uses'] = '1';
						$arm_gm_generate_random_coupon_code['arm_coupon_status'] = 1;
						$arm_gm_generate_random_coupon_code['arm_coupon_subscription'] = $arm_gm_subscription_plan;
						$arm_gm_generate_random_coupon_code['arm_coupon_start_date'] = date('Y-m-d H:i:s');
						$arm_gm_generate_random_coupon_code['arm_coupon_expire_date'] = date('Y-m-d H:i:s');
						$ins = $wpdb->insert($ARMember->tbl_arm_coupons, $arm_gm_generate_random_coupon_code);
					}
				}
				else if(!empty($post_data['arm_gm_child_invite_code']))
				{
					//This condition updates child user signup status.
					$arm_gm_email_id = $post_data['user_email'];
					$arm_gm_invite_code = $post_data['arm_gm_child_invite_code'];

					$arm_gm_coupon_availability_check = $wpdb->get_row($wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_code`=%s", $arm_gm_invite_code));
					if(!empty($arm_gm_coupon_availability_check))
					{
						$arm_gm_coupon_id = $arm_gm_coupon_availability_check->arm_coupon_id;

						$arm_gm_update_status = $wpdb->query("UPDATE ".$wpdb->prefix."arm_gm_child_users_status SET arm_gm_status = 1, arm_gm_user_id = ".$user_id." WHERE arm_gm_email_id = '".$arm_gm_email_id."' AND arm_gm_invite_code_id = ".$arm_gm_coupon_id."");
					}

				}
			}

			function arm_add_configuration_option($button_labels)
			{
				$arm_gm_config_val = isset($button_labels['sub_user_selection_label']) ? esc_html(stripslashes($button_labels['sub_user_selection_label'])) : __('Select Child User', 'ARMGroupMembership');

				$arm_gm_config_data = '<div class="arm_setup_option_field arm_setup_coupon_labels">';
				$arm_gm_config_data .= '<div class="arm_setup_option_label">'.__('Select Child User Label', 'ARMGroupMembership').'</div>';
				$arm_gm_config_data .= '<div class="arm_setup_option_input">';
				$arm_gm_config_data .= '<div class="arm_setup_module_box">';
				$arm_gm_config_data .= '<input type="text" name="setup_data[setup_labels][button_labels][sub_user_selection_label]" value="'.$arm_gm_config_val.'">';
				$arm_gm_config_data .= '<span class="arm_setup_error_msg"></span>';
				$arm_gm_config_data .= '</div>';
				$arm_gm_config_data .= '</div>';
				$arm_gm_config_data .= '</div>';

				echo $arm_gm_config_data;
			}


			function arm_gm_set_common_error_messages($common_messages)
			{
?>
				<div class="arm_solid_divider"></div>
				<div class="page_sub_title"><?php _e('Group Membership Messages', 'ARMGroupMembership'); ?></div>
				<div class="armclear"></div>
				<table class="form-table">
					<tr class="form-field">
						<th class="arm-form-table-label"><label for="arm_gm_child_user_invite_code_label"><?php _e('Child User Invite Code Label', 'ARMGroupMembership'); ?></label></th>
						<td class="arm-form-table-content">
							<input type="text" name="arm_common_message_settings[arm_gm_child_user_invite_code_label]" id="arm_gm_child_user_invite_code_label" value="<?php echo (!empty($common_messages['arm_gm_child_user_invite_code_label'])) ? $common_messages['arm_gm_child_user_invite_code_label'] : __('Invite Code', 'ARMGroupMembership'); ?>" />
						</td>
					</tr>
					<tr class="form-field">
						<th class="arm-form-table-label"><label for="arm_gm_child_user_invite_code_empty_error"><?php _e('Child User Invite Code Empty Error', 'ARMGroupMembership'); ?></label></th>
						<td class="arm-form-table-content">
							<input type="text" name="arm_common_message_settings[arm_gm_child_user_invite_code_empty_error]" id="arm_gm_child_user_invite_code_empty_error" value="<?php echo (!empty($common_messages['arm_gm_child_user_invite_code_empty_error'])) ? $common_messages['arm_gm_child_user_invite_code_empty_error'] : __('Please Enter Invite Code', 'ARMGroupMembership'); ?>" />
						</td>
					</tr>
					<tr class="form-field">
						<th class="arm-form-table-label"><label for="arm_gm_child_user_invite_code_used_error"><?php _e('Child User Invite Code Used Error', 'ARMGroupMembership'); ?></label></th>
						<td class="arm-form-table-content">
							<input type="text" name="arm_common_message_settings[arm_gm_child_user_invite_code_used_error]" id="arm_gm_child_user_invite_code_used_error" value="<?php echo (!empty($common_messages['arm_gm_child_user_invite_code_used_error'])) ? $common_messages['arm_gm_child_user_invite_code_used_error'] : __('Sorry, This Invite Code is already redeemed', 'ARMGroupMembership'); ?>" />
						</td>
					</tr>
					<tr class="form-field">
						<th class="arm-form-table-label"><label for="arm_gm_child_user_invite_email_available_error"><?php _e('Child User Invite Email Available Error', 'ARMGroupMembership'); ?></label></th>
						<td class="arm-form-table-content">
							<input type="text" name="arm_common_message_settings[arm_gm_child_user_invite_email_available_error]" id="arm_gm_child_user_invite_email_available_error" value="<?php echo (!empty($common_messages['arm_gm_child_user_invite_email_available_error'])) ? $common_messages['arm_gm_child_user_invite_email_available_error'] : __('Sorry, This Invite Email Address not exist', 'ARMGroupMembership'); ?>" />
						</td>
					</tr>
					<tr class="form-field">
						<th class="arm-form-table-label"><label for="arm_gm_child_user_invite_code_available_error"><?php _e('Child User Invite Code Available Error', 'ARMGroupMembership'); ?></label></th>
						<td class="arm-form-table-content">
							<input type="text" name="arm_common_message_settings[arm_gm_child_user_invite_code_available_error]" id="arm_gm_child_user_invite_code_available_error" value="<?php echo (!empty($common_messages['arm_gm_child_user_invite_code_available_error'])) ? $common_messages['arm_gm_child_user_invite_code_available_error'] : __('Sorry, This Invite Code is not currently available', 'ARMGroupMembership'); ?>" />
						</td>
					</tr>
					<tr class="form-field">
						<th class="arm-form-table-label"><label for="arm_gm_invite_sent_success_msg"><?php _e('Child User Invitation Success Message', 'ARMGroupMembership'); ?></label></th>
						<td class="arm-form-table-content">
							<input type="text" name="arm_common_message_settings[arm_gm_invite_sent_success_msg]" id="arm_gm_invite_sent_success_msg" value="<?php echo (!empty($common_messages['arm_gm_invite_sent_success_msg'])) ? $common_messages['arm_gm_invite_sent_success_msg'] : __('Invitation Sent Successfully', 'ARMGroupMembership'); ?>" />
						</td>
					</tr>
					<tr class="form-field">
						<th class="arm-form-table-label"><label for="arm_gm_user_delete_msg"><?php _e('Child User Delete Message', 'ARMGroupMembership'); ?></label></th>
						<td class="arm-form-table-content">
							<input type="text" name="arm_common_message_settings[arm_gm_user_delete_msg]" id="arm_gm_user_delete_msg" value="<?php echo (!empty($common_messages['arm_gm_user_delete_msg'])) ? $common_messages['arm_gm_user_delete_msg'] : __('Child User Deleted Successfully', 'ARMGroupMembership'); ?>" />
						</td>
					</tr>
					<tr class="form-field">
						<th class="arm-form-table-label"><label for="arm_gm_resend_email_msg"><?php _e('Child User Resend Email Success Message', 'ARMGroupMembership'); ?></label></th>
						<td class="arm-form-table-content">
							<input type="text" name="arm_common_message_settings[arm_gm_resend_email_msg]" id="arm_gm_resend_email_msg" value="<?php echo (!empty($common_messages['arm_gm_resend_email_msg'])) ? $common_messages['arm_gm_resend_email_msg'] : __('Email Sent Successfully', 'ARMGroupMembership'); ?>" />
						</td>
					</tr>
					<tr class="form-field">
						<th class="arm-form-table-label"><label for="arm_gm_refresh_invite_code_msg"><?php _e('Child User Refresh Invite Code Message', 'ARMGroupMembership'); ?></label></th>
						<td class="arm-form-table-content">
							<input type="text" name="arm_common_message_settings[arm_gm_refresh_invite_code_msg]" id="arm_gm_refresh_invite_code_msg" value="<?php echo (!empty($common_messages['arm_gm_refresh_invite_code_msg'])) ? $common_messages['arm_gm_refresh_invite_code_msg'] : __('Invite Code Refresh Successfully', 'ARMGroupMembership'); ?>" />
						</td>
					</tr>
	            </table>
<?php	            
			}

			function arm_gm_modify_coupon_pricing($discount_amount, $planObj, $planAmt, $couponAmt)
			{
				$arm_gm_plan_options = $planObj->options;
                if(!empty($arm_gm_plan_options['arm_gm_enable_referral']) && $arm_gm_plan_options['arm_gm_enable_referral'])
                {
                    $arm_gm_min_members = !empty($arm_gm_plan_options['arm_gm_min_members']) ? $arm_gm_plan_options['arm_gm_min_members'] : '';

                    if(!empty($_REQUEST['armgm']))
                    {
                        $arm_gm_selected_members = $_REQUEST['armgm'];
                        if(!empty($arm_gm_selected_members))
                        {
                            $arm_gm_per_members = $planAmt / $arm_gm_min_members;

                            $discount_amount = ($arm_gm_per_members * $arm_gm_selected_members) - $couponAmt;
                        }
                    }
                }

                return $discount_amount;
			}


			function arm_gm_check_child_user_exist_or_not($is_exist, $post_content)
			{
				$matched = "";
	            $pattern = '\[arm_form (.*?)\]';
	            preg_match_all('/'.$pattern.'/s', $post_content, $matched);
	            foreach($matched as $match_key1 => $match_val1)
	            {
	                foreach($match_val1 as $match_key2 => $match_val2)
	                {
	                    $tmp_arr = explode(' ', $match_val2);
	                    if(in_array('is_child="1"', $tmp_arr) || in_array("is_child='1'", $tmp_arr))
	                    {
	                        $is_exist = true;
	                        break;
	                    }
	                }

	                if($is_exist)
	                {
	                    break;
	                }
	            }
				return $is_exist;
			}
		}


	}
	global $arm_group_membership;
	$arm_group_membership = new ARM_GROUP_MEMBERSHIP();
?>