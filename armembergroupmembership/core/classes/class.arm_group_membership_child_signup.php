<?php
	if (!class_exists('ARM_GROUPMEMBERSHIP_CHILD_SIGNUP')) 
	{
		class ARM_GROUPMEMBERSHIP_CHILD_SIGNUP
		{
			function __construct()
			{	
				//Restrict Use of Invite Code Access in Setup Form
				add_action('arm_restrict_specific_coupon_code', array($this, 'arm_gm_restrict_setup_form_invite_code'), 10, 1);

				//Child user signup action
				add_action('arm_before_form_submit_action', array($this, 'arm_gm_check_invited_code_user'), 10, 1);
				//add_action('arm_after_form_submit_action', array($this, 'arm_gm_child_user_signup'));
				add_action('arm_after_add_new_user', array($this, 'arm_gm_child_user_signup'), 10, 2);

				//Add Invite Code field in signup form
				add_filter( 'arm_form_data_before_form_shortcode', array($this, 'arm_gm_change_field_option'), 10, 2);


				//Add dynamic shortcode attribute
				add_filter('arm_add_register_dynamic_atts', array($this, 'arm_gm_dynamic_shortcode_atts'), 10, 2);


				//Add shortcode form fields for registration form
				add_action('arm_add_forms_shortcode_options', array($this, 'arm_gm_add_shortcode_options_fields'));


				//Add Invite Code field in signup form
				//add_filter('arm_after_setup_gateway_section', array($this, 'arm_gm_add_invite_code_field'), 10, 3);

				//Filter for allow access of parent user restricted pages and posts
				add_filter('arm_allow_specific_user_restricted_access', array($this, 'arm_gm_allow_child_user_restricted_access'), 10, 2);

				//Add option for restrict page for child user in general options
				add_action('arm_after_global_settings_html', array($this, 'arm_gm_child_user_page_restrict_view'), 10, 1);
				add_filter('arm_before_update_global_settings', array($this, 'arm_gm_update_global_settings'), 10, 2);


				//Restrict child page
				add_filter('arm_restrict_page_for_user', array($this, 'arm_gm_restrict_child_user_page'), 10, 1);
				
				add_action('arm_default_access_rules_restriction_specific_user', array($this, 'arm_gm_restrict_child_user_page_direct_access'), 10, 1);


				//Display Error msg to user if current plan have group membership and user try to change it.
				add_action('arm_modify_content_on_plan_change', array($this, 'arm_gm_plan_change_check_group_membership'), 10, 2);


				//Display error if user have group membership on and payment method is selected to 'Auto-debit'.
				add_action('arm_payment_gateway_validation_from_setup', array($this, 'arm_gm_plan_check_auto_debit_or_not'), 10, 4);

				//Display Parent User Content to Child user if restricted using [restrict_content] shortcode.
				add_filter('arm_restrict_content_shortcode_hasaccess', array($this, 'arm_gm_allow_restrict_content'), 10, 2);

				//Allow Drip Content Access
				add_filter('arm_assign_plan_data', array($this, 'arm_gm_filter_drip_access'), 20, 2);
				add_filter('arm_assign_suspended_plan_data', array($this, 'arm_gm_assign_suspended_plan_data'), 20, 2);

				//New shortcode for users Listing
				add_shortcode( 'arm_gm_users_list_count', array( $this, 'arm_gm_users_list_count_func' ) );
			}


			function arm_gm_restrict_setup_form_invite_code($coupon_code)
			{
				global $wp, $wpdb, $ARMember, $arm_global_settings;
				if(!empty($coupon_code))
				{
					$arm_gm_coupon_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_code`=%s", $coupon_code));

					$arm_gm_parent_user_id = $arm_gm_coupon_data->arm_group_parent_user_id;
					if(!empty($arm_gm_parent_user_id))
					{
						$return = array(
			                'status' => 'error',
			                'message' => __('Invalid Invite Code.', 'ARMGroupMembership'),
			                'validity' => 'invalid_coupon',
			                'coupon_amt' => 0,
			                'total_amt' => 0,
			                'discount' => 0,
			                'discount_type' => '',
			            );

			            echo json_encode($return);
			            exit();
					}
				}
			}


			function arm_gm_parent_and_child_user_filter_shortcode_content($content, $arm_gm_parent_user_id, $arm_gm_signup_posted_data)
			{
				global $wp, $wpdb, $ARMember, $arm_global_settings;
				if(!empty($content) && !empty($arm_gm_parent_user_id) && !empty($arm_gm_signup_posted_data))
				{
					$arm_gm_userdata = get_userdata($arm_gm_parent_user_id);
					$arm_gm_parent_user_name = isset($arm_gm_userdata->user_login) ? $arm_gm_userdata->user_login : '';
					
					if(strpos($content, '{ARM_GM_PARENT_USER_USERNAME}') && !empty($arm_gm_parent_user_name))
					{
						$content = str_replace('{ARM_GM_PARENT_USER_USERNAME}', $arm_gm_parent_user_name, $content);
					}


					$arm_gm_signup_invite_code = $arm_gm_signup_posted_data['arm_gm_child_invite_code'];
					if(strpos($content, '{ARM_GM_INVITE_COUPON_CODE}'))
					{
						$content = str_replace('{ARM_GM_INVITE_COUPON_CODE}', $arm_gm_signup_invite_code, $content);
					}
				}

				return $content;
			}



			function arm_gm_check_invited_code_user($armform)
			{
				global $ARMember, $wpdb, $arm_global_settings;

				if(!empty($_POST) && !empty($_POST['arm_gm_child_invite_code']))
				{
					$arm_global_common_messages = isset($arm_global_settings->common_message) ? $arm_global_settings->common_message : array();

					$arm_gm_posted_invited_user_email = !empty($_POST['user_email']) ? $_POST['user_email'] : '';
					$arm_gm_child_invite_code = !empty($_POST['arm_gm_child_invite_code']) ? $_POST['arm_gm_child_invite_code'] : '';

					$arm_gm_check_user_invited_or_not = $wpdb->get_row("SELECT *, COUNT(arm_gm_id) as total_cnt FROM ".$wpdb->prefix."arm_gm_child_users_status WHERE arm_gm_email_id = '".$arm_gm_posted_invited_user_email."'");

					$arm_gm_check_coupon_exist = $wpdb->get_row("SELECT * FROM ".$ARMember->tbl_arm_coupons." WHERE arm_coupon_code = '".$arm_gm_child_invite_code."'");

					$arm_gm_child_user_invite_email_available_error = !empty($arm_global_common_messages['arm_gm_child_user_invite_email_available_error']) ? $arm_global_common_messages['arm_gm_child_user_invite_email_available_error'] :  __('Sorry, This Invite Email Address not exist.', 'ARMGroupMembership');

					$arm_gm_child_user_invite_code_available_error = !empty($arm_global_common_messages['arm_gm_child_user_invite_code_available_error']) ? $arm_global_common_messages['arm_gm_child_user_invite_code_available_error'] : __('Sorry, This Invite Code is not currently available.', 'ARMGroupMembership');

					if($arm_gm_check_user_invited_or_not->total_cnt == 0)
					{
						$arm_not_existing_user_msg = '<div class="arm_error_msg"><ul><li>' . $arm_gm_child_user_invite_email_available_error . '</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $arm_not_existing_user_msg);
                        echo json_encode($return);
                        exit;
					}
					else if($arm_gm_check_user_invited_or_not->arm_gm_invite_code_id != $arm_gm_check_coupon_exist->arm_coupon_id)
					{
						$arm_gm_invalid_invite_code_msg = '<div class="arm_error_msg"><ul><li>' . $arm_gm_child_user_invite_code_available_error . '</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $arm_gm_invalid_invite_code_msg);
                        echo json_encode($return);
                        exit;
					}
					else if($arm_gm_check_user_invited_or_not->arm_gm_status == 1 || empty($arm_gm_check_coupon_exist))
					{
						$arm_not_existing_user_msg = '<div class="arm_error_msg"><ul><li>' . $arm_gm_child_user_invite_code_available_error . '</li></ul></div>';
                        $return = array('status' => 'error', 'type' => 'message', 'message' => $arm_not_existing_user_msg);
                        echo json_encode($return);
                        exit;	
					}
				}
			}

			function arm_gm_child_user_signup($user_id, $posted_data)
			{
				global $wpdb, $ARMember, $arm_global_settings, $arm_manage_communication, $arm_group_membership;

				if(!empty($posted_data) && !empty($posted_data['arm_gm_child_invite_code']))
				{
					$arm_gm_email = !empty($posted_data['user_email']) ? $posted_data['user_email'] : '';

					$arm_gm_invite_code = $_POST['arm_gm_child_invite_code'];
					$arm_gm_coupon_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_coupon_code`=%s AND `arm_group_parent_user_id` IS NOT NULL", $arm_gm_invite_code));
					if(!empty($arm_gm_coupon_data) && !empty($arm_gm_coupon_data->arm_group_parent_user_id))
					{
						$arm_gm_parent_user_id = array('arm_parent_user_id' => $arm_gm_coupon_data->arm_group_parent_user_id);

						$arm_gm_parent_user_plans = get_user_meta($arm_gm_parent_user_id, 'arm_user_plan_ids', true);
            			$arm_gm_parent_user_plan = isset($arm_gm_parent_user_plans) && !empty($arm_gm_parent_user_plans) ? implode(',', $arm_gm_parent_user_plans) : 0;

						$arm_gm_is_child_user_data = array('plan_id' => $arm_gm_subscription_plan);

						update_user_meta($user_id, 'arm_coupon_code', $arm_gm_invite_code);
						update_user_meta($user_id, 'arm_is_child_user', $arm_gm_is_child_user_data);
						update_user_meta($user_id, 'arm_parent_user_id', $arm_gm_parent_user_id);

						//Update coupon counter
						$arm_gm_update_data['arm_coupon_used'] = 1;
						$update_data = $wpdb->update($ARMember->tbl_arm_coupons, $arm_gm_update_data, array('arm_coupon_id' => $arm_gm_coupon_data->arm_coupon_id));


						//Get Parent User Email Id & Details
						$arm_gm_parent_user_email = "";
						$arm_gm_parent_user_details = get_user_by('id', $arm_gm_coupon_data->arm_group_parent_user_id);
						if(!empty($arm_gm_parent_user_details))
						{
							$arm_gm_parent_user_email = $arm_gm_parent_user_details->user_email;
						}


						//Get Child User Email Id & Details
						$arm_gm_child_user_email = "";
						$arm_gm_child_user_details = get_user_by('id', $user_id);
						if(!empty($arm_gm_child_user_details))
						{
							$arm_gm_child_user_email = $arm_gm_child_user_details->user_email;
						}

						//Update child user status in status table after signup.
						$wpdb->query("UPDATE ".$wpdb->prefix."arm_gm_child_users_status SET arm_gm_user_id = '".$user_id."', arm_gm_status='1' WHERE arm_gm_email_id = '".$arm_gm_child_user_email."'");


						$arm_gm_parent_user_message_type = "arm_gm_send_signup_notification_to_parent";
						$arm_gm_child_user_message_type = "arm_gm_send_signup_notification_to_child";
						$is_sent_to_admin = 0;

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

                                $subject = $arm_manage_communication->arm_filter_communication_content($content_subject, $arm_gm_coupon_data->arm_group_parent_user_id, $arm_gm_parent_user_plan, '');
                                $subject = $this->arm_gm_parent_and_child_user_filter_shortcode_content($content_subject, $arm_gm_coupon_data->arm_group_parent_user_id, $posted_data);


                                $message = $arm_manage_communication->arm_filter_communication_content($content_description, $arm_gm_coupon_data->arm_group_parent_user_id, $arm_gm_parent_user_plan, '');
                                $message = $this->arm_gm_parent_and_child_user_filter_shortcode_content($content_description, $arm_gm_coupon_data->arm_group_parent_user_id, $posted_data);


                                $admin_message = $arm_manage_communication->arm_filter_communication_content($admin_content_description, $arm_gm_coupon_data->arm_group_parent_user_id, $arm_gm_parent_user_plan, '');
                                $admin_message = $this->arm_gm_parent_and_child_user_filter_shortcode_content($admin_content_description, $arm_gm_coupon_data->arm_group_parent_user_id, $posted_data);

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

                                $subject = $arm_manage_communication->arm_filter_communication_content($content_subject, $user_id, $arm_gm_parent_user_plan, '');
                                $subject = $this->arm_gm_parent_and_child_user_filter_shortcode_content($content_subject, $arm_gm_coupon_data->arm_group_parent_user_id, $posted_data);


                                $message = $arm_manage_communication->arm_filter_communication_content($content_description, $user_id, $arm_gm_parent_user_plan, '');
                                $message = $this->arm_gm_parent_and_child_user_filter_shortcode_content($content_description, $arm_gm_coupon_data->arm_group_parent_user_id, $posted_data);


                                $admin_message = $arm_manage_communication->arm_filter_communication_content($admin_content_description, $user_id, $arm_gm_parent_user_plan, '');
                                $admin_message = $this->arm_gm_parent_and_child_user_filter_shortcode_content($admin_content_description, $arm_gm_coupon_data->arm_group_parent_user_id, $posted_data);

                                $send_mail = $arm_global_settings->arm_wp_mail('', $arm_gm_child_user_email, $subject, $message);


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
					}
				}
			}

			function arm_gm_change_field_option($form, $atts)
			{
				global $wpdb, $ARMember, $arm_global_settings;
				$common_messages = $arm_global_settings->arm_get_all_common_message_settings();
				$arm_gm_child_user_invite_code_label = !empty($common_messages['arm_gm_child_user_invite_code_label']) ? $common_messages['arm_gm_child_user_invite_code_label'] : __('Invite Code', 'ARMGroupMembership');
				$arm_gm_empty_field_error_message = !empty($common_messages['arm_gm_child_user_invite_code_empty_error']) ? $common_messages['arm_gm_child_user_invite_code_empty_error'] : __('Please Enter Invite Code', 'ARMGroupMembership') ;

				if(!empty($atts['is_child']) && ($atts['is_child'] == 1))
				{
					if($form->slug == "please-signup")
					{
						$arm_existing_form_fields = $form->fields;

						$arm_txt_field_arr = array();
						$arm_txt_field_val = "";

						if(!empty($_GET) && !empty($_GET['arm_invite_code']))
						{
							$arm_txt_field_val = $_GET['arm_invite_code'];
						}
						
						foreach($arm_existing_form_fields as $arm_form_field_keys => $arm_form_field_val)
						{
							if($arm_form_field_val['arm_form_field_slug'] == 'user_email')
							{
								$arm_form_field_val['arm_form_field_slug'] = 'arm_gm_child_invite_code';

								$arm_form_field_option_type = (!empty($arm_txt_field_val)) ? 'hidden' : 'text' ;
								$arm_form_field_val['arm_form_field_option']['type'] = $arm_form_field_option_type;

								$arm_form_field_val['arm_form_field_option']['id'] = 'arm_gm_child_invite_code';
								$arm_form_field_val['arm_form_field_option']['label'] = $arm_gm_child_user_invite_code_label;
								$arm_form_field_val['arm_form_field_option']['required'] = 1;
								$arm_form_field_val['arm_form_field_option']['meta_key'] = 'arm_gm_child_invite_code';
								$arm_form_field_val['arm_form_field_option']['blank_message'] = $arm_gm_empty_field_error_message;
								$arm_form_field_val['arm_form_field_option']['invalid_username'] = '';
								$arm_form_field_val['arm_form_field_option']['value'] = $arm_txt_field_val;
								$arm_form_field_val['arm_form_field_option']['placeholder'] = !empty($atts['arm_gm_invite_placeholder']) ? $atts['arm_gm_invite_placeholder'] : '';
								$arm_form_field_val['arm_form_field_option']['prefix'] = !empty($atts['arm_gm_invite_prefix_icon']) ? $atts['arm_gm_invite_prefix_icon'] : '';
								$arm_form_field_val['arm_form_field_option']['suffix'] = !empty($atts['arm_gm_invite_suffix_icon']) ? $atts['arm_gm_invite_suffix_icon'] : '';
								$arm_form_field_val['arm_form_field_option']['description'] = !empty($atts['arm_gm_invite_description']) ? $atts['arm_gm_invite_description'] : '';

								array_push($arm_txt_field_arr, $arm_form_field_val);
								break;
							}
						}
						
						if(!empty($arm_txt_field_arr))
						{
							$arm_gm_arr_pos = count($arm_existing_form_fields) - 1;
							array_splice($arm_existing_form_fields, $arm_gm_arr_pos, 0, $arm_txt_field_arr);
						}


						$form->fields = $arm_existing_form_fields;
					}
				}
				return $form;
			}


			function arm_gm_dynamic_shortcode_atts($short_atts, $tag)
			{
				$short_atts['is_child'] = 0;
				$short_atts['arm_gm_invite_prefix_icon'] = '';
				$short_atts['arm_gm_invite_suffix_icon'] = '';
				$short_atts['arm_gm_invite_placeholder'] = '';
				$short_atts['arm_gm_invite_description'] = '';
				return $short_atts;
			}


			function arm_gm_add_shortcode_options_fields()
			{
				ob_start();
				require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/admin/arm_gm_shortcode_options.php');
				$content = ob_get_clean();
				echo $content;
			}


			function arm_gm_allow_child_user_restricted_access($current_user_plan, $user_id)
			{
				if(metadata_exists('user', $user_id, 'arm_parent_user_id'))
                {
                    $arm_parent_user_id = get_user_meta($user_id, 'arm_parent_user_id', true);
                    $arm_parent_user_id = $arm_parent_user_id['arm_parent_user_id'];

                    //Get parent user plan ids
                    $parent_user_plans_ids = get_user_meta($arm_parent_user_id, 'arm_user_plan_ids', true);
                	$current_user_plan = $parent_user_plans_ids;
                }

                return $current_user_plan;
			}


			function arm_gm_child_user_page_restrict_view($general_settings)
			{
				global $arm_global_settings;
				$all_global_settings = $arm_global_settings->arm_get_all_global_settings();

				$arm_gm_selected_page = !empty($all_global_settings['general_settings']['arm_gm_child_user_restrict_page']) ? $all_global_settings['general_settings']['arm_gm_child_user_restrict_page'] : '';

				ob_start();
				require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/admin/arm_gm_child_user_page_restrict_field.php');
				$content = ob_get_clean();
				echo $content;
			}


			function arm_gm_update_global_settings($new_global_settings, $post_data)
			{
				$new_global_settings['general_settings']['arm_gm_child_user_restrict_page'] = $post_data['arm_general_settings']['arm_gm_child_user_restrict_page'];
				return $new_global_settings;
			}


			function arm_gm_restrict_child_user_page($items)
			{
				global $current_user, $arm_global_settings;
				$user_id = $current_user->ID;
				$current_page_id = get_the_ID();

				$all_global_settings = $arm_global_settings->arm_get_all_global_settings();
                $arm_gm_child_user_restricted_page_id = !empty($all_global_settings['general_settings']['arm_gm_child_user_restrict_page']) ? $all_global_settings['general_settings']['arm_gm_child_user_restrict_page'] : '';

                if(!empty($items))
				{
					$arm_parent_user_id = get_user_meta($user_id, 'arm_parent_user_id', true);
					if(!empty($arm_parent_user_id) && !is_admin())
					{
						$arm_gm_parent_user_id = $arm_parent_user_id['arm_parent_user_id'];
						if(!empty($arm_gm_parent_user_id))
						{

			                foreach($items as $item_key => $item_val)
			                {
			                	if($item_val->object_id == $arm_gm_child_user_restricted_page_id)
			                	{
			                		unset($items[$item_key]);
			                	}
			                }
						}
					}
				}
				
				return $items;
			}

			function arm_gm_restrict_child_user_page_direct_access($page_obj)
			{
				global $current_user, $arm_global_settings;
				$user_id = $current_user->ID;

				$arm_parent_user_id = get_user_meta($user_id, 'arm_parent_user_id', true);
				if(!empty($arm_parent_user_id) && !is_admin())
				{
					$page_obj_data = $page_obj->queried_object;
					if(!empty($page_obj_data->ID) && !is_admin())
					{
						$current_page_id = $page_obj_data->ID;

						$all_global_settings = $arm_global_settings->arm_get_all_global_settings();
		                $arm_gm_child_user_restricted_page_id = !empty($all_global_settings['general_settings']['arm_gm_child_user_restrict_page']) ? $all_global_settings['general_settings']['arm_gm_child_user_restrict_page'] : '';

						if(($arm_gm_child_user_restricted_page_id == $current_page_id) && (!in_array("administrator", wp_get_current_user()->roles)))
						{
							wp_redirect(ARM_HOME_URL);
							exit;
						}
					}
				}
			}

			function arm_gm_plan_change_check_group_membership($post_data, $user_ID)
			{
				global $wpdb, $ARMember, $is_multiple_membership_feature, $arm_pay_per_post_feature, $arm_group_membership;

				$arm_gm_coupon_count = $wpdb->get_row($wpdb->prepare("SELECT COUNT(arm_coupon_id) as total_rec FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_group_parent_user_id`=%d AND arm_coupon_used != 0", $user_ID));
                $arm_gm_total_coupons = $arm_gm_coupon_count->total_rec;
                if($arm_gm_total_coupons > 0)
                {

					//Code for check that current user is child user or not
					$arm_gm_is_child_user = false;
					$arm_parent_user_id = get_user_meta($user_ID, 'arm_parent_user_id', true);
					if(!empty($arm_parent_user_id))
					{
						$arm_gm_parent_user_id = $arm_parent_user_id['arm_parent_user_id'];
						if(!empty($arm_gm_parent_user_id))
						{
							$arm_gm_is_child_user = true;
						}
					}


					//Code for check current user is parent user or not
					if($arm_gm_is_child_user)
					{
						$response = array('type' => 'error', 'msg' => __("You cannot assign plan to child user.", 'ARMGroupMembership'), 'content' => '');
						echo json_encode($response);
	                    exit();
					}
					else
					{
						//This function only works for single membership plan.
						if((!empty($post_data['arm_user_plan']) || !empty($post_data['subscription_plan'])) && (!$is_multiple_membership_feature->isMultipleMembershipFeature))
						{
							if(is_array($post_data['arm_user_plan']) && !empty($post_data['arm_user_plan']))
							{
								$arm_gm_user_plan = !empty($post_data['arm_user_plan'][0]) ? $post_data['arm_user_plan'][0] : 0;
							}
							else {
								$arm_gm_user_plan = !empty($post_data['arm_user_plan']) ? $post_data['arm_user_plan'] : 0;
							}
							if(empty($arm_gm_user_plan))
							{
								$arm_gm_user_plan = !empty($post_data['subscription_plan']) ? $post_data['subscription_plan'] : 0;
							}
		                    $arm_gm_plan_obj = new ARM_Plan($arm_gm_user_plan);
		                    //Condition for check that selected plan is paid post or not.
		                    if(empty($arm_gm_plan_obj->isPaidPost))
		                    {
				                $arm_gm_coupon_count = $wpdb->get_row($wpdb->prepare("SELECT COUNT(arm_coupon_id) as total_rec FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_group_parent_user_id`=%d AND arm_coupon_used != 0", $user_ID));
				                $arm_gm_total_coupons = $arm_gm_coupon_count->total_rec;
				                if(!empty($arm_gm_user_plan) && $arm_gm_total_coupons > 0)
				                {
				                    $arm_gm_subscription_plan_options = $arm_gm_plan_obj->arm_subscription_plan_options;
				                    $arm_gm_enable_referral = $arm_gm_subscription_plan_options['arm_gm_enable_referral'];

				                    if($arm_gm_enable_referral == 0)
				                    {
				                    	if(!empty($post_data['subscription_plan']))
				                    	{
				                    		$response = array(
				                    			'type' => 'message',
				                    			'status' => 'error',
				                    			'message' => '<div class="arm_error_msg"><ul>'.__("Your current plan have group membership enabled and your new selected plan haven't enable group membership.", 'ARMGroupMembership').'</ul></div>',
				                    		);
				                    	}
				                    	else
				                    	{
					                        $response = array('type' => 'error', 'msg' => __("Your current plan have group membership enabled and your new selected plan haven't enable group membership.", 'ARMGroupMembership'), 'content' => '');
				                    	}
				                        echo json_encode($response);
				                        exit();
				                    }
					                else
					                {
					                	if(!empty($post_data['subscription_plan']))
				                    	{
				                    		$response = array(
				                    			'type' => 'message',
				                    			'status' => 'error',
				                    			'message' => '<div class="arm_error_msg"><ul>'.__("Your current plan have group membership enabled and your new selected plan has group membership enabled.", 'ARMGroupMembership').'</ul></div>',
				                    		);
				                    	}
				                    	else
				                    	{
					                        $response = array('type' => 'error', 'msg' => __("Your current plan have group membership enabled and your new selected plan has group membership enabled.", 'ARMGroupMembership'), 'content' => '');
				                    	}
				                        echo json_encode($response);
				                        exit();	
					                }
				                }
				                
				            }
			            }
			        }
			    }
			    else if(!empty($post_data['arm_user_plan']) && !empty($post_data['arm_gm_selected_user']))
                {
                	/*
                		=> This condition checks that if current plan is group membership then generate new coupons for that user.
                	*/
            		$arm_gm_plan_id = $post_data['arm_user_plan'];
            		$arm_gm_plan_obj = new ARM_Plan($arm_gm_plan_id);

            		$arm_gm_plan_max_members = $arm_gm_plan_options['arm_gm_max_members'];
		    		$arm_gm_plan_min_members = $arm_gm_plan_options['arm_gm_min_members'];
		    		$arm_gm_plan_sub_user_seat_slot = $arm_gm_plan_options['arm_gm_sub_user_seat_slot'];

					$arm_gm_selected_sub_user = $post_data['arm_gm_selected_user'][0];

					$arm_gm_coupon_count = $wpdb->get_row($wpdb->prepare("SELECT COUNT('arm_coupon_id') as total_coupons FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_group_parent_user_id` = ".$user_ID));

					$arm_gm_previous_purchased_user = $arm_gm_coupon_count->total_coupons;

					$arm_total_purchased_user = $arm_gm_previous_purchased_user + $arm_gm_selected_sub_user;

					update_user_meta($user_ID, 'gm_max_members_'.$arm_gm_plan_id, $arm_gm_plan_max_members);
					update_user_meta($user_ID, 'gm_min_members_'.$arm_gm_plan_id, $arm_gm_plan_min_members);
					update_user_meta($user_ID, 'gm_sub_user_select_'.$arm_gm_plan_id, $arm_total_purchased_user);

					for($arm_sub_user_cnt = 1;$arm_sub_user_cnt <= $arm_gm_selected_sub_user; $arm_sub_user_cnt++)
					{
						$arm_gm_generate_random_coupon_code['arm_coupon_code'] = $arm_group_membership->arm_gm_generate_random_code(12);
						$arm_gm_generate_random_coupon_code['arm_group_parent_user_id'] = $user_ID;
						$arm_gm_generate_random_coupon_code['arm_coupon_discount'] = 100;
						$arm_gm_generate_random_coupon_code['arm_coupon_discount_type'] = 'percentage';
						$arm_gm_generate_random_coupon_code['arm_coupon_period_type'] = 'unlimited';
						$arm_gm_generate_random_coupon_code['arm_coupon_allowed_uses'] = '1';
						$arm_gm_generate_random_coupon_code['arm_coupon_status'] = 1;
						$arm_gm_generate_random_coupon_code['arm_coupon_subscription'] = $arm_gm_plan_id;
						$arm_gm_generate_random_coupon_code['arm_coupon_start_date'] = date('Y-m-d H:i:s');
						$arm_gm_generate_random_coupon_code['arm_coupon_expire_date'] = date('Y-m-d H:i:s');
						$ins = $wpdb->insert($ARMember->tbl_arm_coupons, $arm_gm_generate_random_coupon_code);
					}
                }
			}


			function arm_gm_plan_check_auto_debit_or_not($payment_gateway, $payment_gateway_options, $posted_data, $entry_id = 0)
			{
				$arm_gm_payment_mode = $posted_data['arm_selected_payment_mode'];
				$arm_gm_subscription_plan = $posted_data['subscription_plan'];
				$arm_gm_plan_obj = new ARM_Plan($arm_gm_subscription_plan);
				$arm_gm_plan_options = $arm_gm_plan_obj->options;
				if(!empty($arm_gm_plan_options['arm_gm_enable_referral']) && $arm_gm_payment_mode == "auto_debit_subscription")
				{
					$err_msg = '<div class="arm_error_msg"><ul><li>' . __('Payment through Auto-Debit payment method is not possible with Group Membership.', 'ARMGroupMembership') . '</li></ul></div>';
                    $return = array('status' => 'error', 'type' => 'message', 'message' => $err_msg);
                    echo json_encode($return);
                    die;	
				}
			}


			function arm_gm_allow_restrict_content($hasaccess, $opts)
			{
				$arm_gm_allowed_plan_ids = explode(',', $opts['plan']);
				$arm_gm_current_user_id = get_current_user_id();

				//Code for check that current user is child user or not
				$arm_gm_is_child_user = false;
				$arm_parent_user_id = get_user_meta($arm_gm_current_user_id, 'arm_parent_user_id', true);

				if(!empty($arm_parent_user_id))
				{
					$arm_gm_parent_user_id = $arm_parent_user_id['arm_parent_user_id'];

					//Get Parent User Plan Ids
					$arm_gm_parent_plan_ids = get_user_meta($arm_gm_parent_user_id, 'arm_user_plan_ids', true);


					foreach($arm_gm_parent_plan_ids as $arm_gm_plan_key => $arm_gm_plan_val)
					{
						if(in_array($arm_gm_plan_val, $arm_gm_allowed_plan_ids))
						{
							if($opts['type'] == "hide")
							{
								$hasaccess = false;
							}
							
							if($opts['type'] == "show")
							{
								$hasaccess = true;
							}
							break;
						}
					}
				}
				

				return $hasaccess;
			}


			function arm_gm_filter_drip_access($user_plans, $user_id)
			{
				$arm_parent_user_id = get_user_meta($user_id, 'arm_parent_user_id', true);

				if(!empty($arm_parent_user_id))
				{
					$arm_gm_parent_user_id = $arm_parent_user_id['arm_parent_user_id'];

					//Get Parent User Plan Ids
					$user_plans = get_user_meta($arm_gm_parent_user_id, 'arm_user_plan_ids', true);
				}

				return $user_plans;
			}


			function arm_gm_assign_suspended_plan_data($suspended_plan_ids, $user_id)
			{
				$arm_parent_user_id = get_user_meta($user_id, 'arm_parent_user_id', true);

				if(!empty($arm_parent_user_id))
				{
					$arm_gm_parent_user_id = $arm_parent_user_id['arm_parent_user_id'];

					//Get Parent User Plan Ids
					$suspended_plan_ids = get_user_meta($arm_gm_parent_user_id, 'arm_user_suspended_plan_ids', true);
				}				
				return $suspended_plan_ids;
			}


			function arm_gm_users_list_count_func($atts, $content, $tag)
			{
				global $wpdb, $ARMember, $arm_global_settings;
				$arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
	            if($arm_check_is_gutenberg_page)
	            {
	                return;
	            }

	            $atts = shortcode_atts(array(
         			
         		), $atts, $tag);

         		$arm_gm_current_user_id = get_current_user_id();
         		if(!empty($arm_gm_current_user_id))
         		{
         			$arm_gm_count_total_invite_codes = $wpdb->get_row("SELECT COUNT(arm_coupon_id) as total_rec FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_group_parent_user_id` = ".$arm_gm_current_user_id."");

         			$arm_gm_total_invite_codes = $arm_gm_count_total_invite_codes->total_rec;

         			$arm_gm_count_purchased_invite_codes = $wpdb->get_row("SELECT COUNT(arm_coupon_id) as total_rec FROM `" . $ARMember->tbl_arm_coupons . "` WHERE `arm_group_parent_user_id` = ".$arm_gm_current_user_id." AND arm_coupon_used = 1");
         			$arm_gm_total_purchased_invite_code = $arm_gm_count_purchased_invite_codes->total_rec;

         			$content = $arm_gm_total_purchased_invite_code."/".$arm_gm_total_invite_codes;
         		}

         		return do_shortcode($content);
			}
		}

		global $arm_group_child_signup_membership;
		$arm_group_child_signup_membership = new ARM_GROUPMEMBERSHIP_CHILD_SIGNUP();
	}
?>