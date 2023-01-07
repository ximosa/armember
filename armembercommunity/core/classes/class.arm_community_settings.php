<?php
if (!class_exists('ARM_Community_setting')) {
    class ARM_Community_setting {
        var $arm_com_settings = '';
        function __construct() {
            global $arm_community_activity_meta;
            $this->arm_com_settings = $this->arm_community_get_settings();
            add_action('wp_ajax_arm_community_save_settings', array(&$this, 'arm_community_save_settings'));
            add_filter('arm_notification_get_list_msg_type', array(&$this, 'arm_notification_get_list_msg_type_func'), 1);
            add_filter('arm_notification_add_message_types', array(&$this, 'arm_notification_add_message_types_func'), 1);

            $arm_com_settings = $this->arm_com_settings;

            $arm_post_lbl = !empty($arm_com_settings["arm_post_section_lbl"]) ? $arm_com_settings["arm_post_section_lbl"] : __("Post", ARM_COMMUNITY_TEXTDOMAIN);
            $arm_follow_btn_txt = !empty($arm_com_settings["arm_follow_btn_txt"]) ? $arm_com_settings["arm_follow_btn_txt"] : __("Unfollow", ARM_COMMUNITY_TEXTDOMAIN);
            $arm_unfollow_btn_txt = !empty($arm_com_settings["arm_unfollow_btn_txt"]) ? $arm_com_settings["arm_unfollow_btn_txt"] : __("Unfollow", ARM_COMMUNITY_TEXTDOMAIN);
            $arm_review_section_lbl = !empty($arm_com_settings["arm_review_section_lbl"]) ? $arm_com_settings["arm_review_section_lbl"] : __("Unfollow", ARM_COMMUNITY_TEXTDOMAIN);

            $arm_community_activity_meta = array (
                "post" => array ("action" => __("New", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_post_lbl . " " . __("Published", ARM_COMMUNITY_TEXTDOMAIN), "activity" => __("Created New", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_post_lbl),
                "like_post" => array ("action" => __("Liked", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_post_lbl, "activity" => __("Liked", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_post_lbl),
                "unlike_post" => array ("action" => __("Unliked", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_post_lbl, "activity" => __("Unliked", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_post_lbl),
                "avatar" => array ("action" => __("Changed Their Profile Picture", ARM_COMMUNITY_TEXTDOMAIN), "activity" => __("Changed Their Profile Picture", ARM_COMMUNITY_TEXTDOMAIN)),
                "friend_request_sent" => array ("action" => __("Friend Request Sent", ARM_COMMUNITY_TEXTDOMAIN), "activity" => __("Sent Friend Request To", ARM_COMMUNITY_TEXTDOMAIN)),
                "become_friend" => array ("action" => __("Become Friend", ARM_COMMUNITY_TEXTDOMAIN), "activity" => __("Become Friend With", ARM_COMMUNITY_TEXTDOMAIN)),
                "unfriend" => array ("action" => __("Unfriend", ARM_COMMUNITY_TEXTDOMAIN), "activity" => __("Unfriend", ARM_COMMUNITY_TEXTDOMAIN)),
                "friend_request_canceled" => array ("action" => __("Cancel Friend Request", ARM_COMMUNITY_TEXTDOMAIN), "activity" => __("Cancel Friend Request of", ARM_COMMUNITY_TEXTDOMAIN)),
                "follow" => array ("action" => $arm_follow_btn_txt, "activity" => __("Started Following To", ARM_COMMUNITY_TEXTDOMAIN)),
                "unfollow" => array ("action" => $arm_unfollow_btn_txt, "activity" => __("Stop Following To", ARM_COMMUNITY_TEXTDOMAIN)),
                "add_review" => array ("action" => __("Add", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_review_section_lbl, "activity" => __("Give", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_review_section_lbl . " " . __("To", ARM_COMMUNITY_TEXTDOMAIN)),
                "edit_review" => array ("action" => __("Edit", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_review_section_lbl, "activity" => __("Edited", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_review_section_lbl . " " . __("Review Given To", ARM_COMMUNITY_TEXTDOMAIN)),
            );
        }

        function arm_community_default_param_value() {
            return array(
                'arm_com_friendship' => 0,
                'arm_friend_section_lbl' => __('Friends', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_send_friend_request_lbl' => __('Send Friend Request', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_cancel_friend_request_lbl' => __('Cancel', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_accept_friend_request_lbl' => __('Accept', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_unfriend_lbl' => __('Unfriend', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_current_friends_lbl' => __('Current Friends', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_friend_requests_lbl' => __('Friend Requests', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_no_friend_msg' => __('No Friends Found.', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_friend_error_msg' => __('Sorry, You are not able to [code] request.', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_no_friend_requests_msg' => __('No Friendship Request Found.', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_public_friends' => 0,
                'arm_com_private_message' => 0,
                'arm_message_only_friends' => 0,
                'arm_msg_section_lbl' =>__('Message', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_msg_username_lbl' => __('Username OR Email', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_msg_msg_lbl' => __('Message', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_blank_field_msg' => __('Please Enter [label].', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_invalid_field_msg' => __('[label] is invalid.', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_msg_blocked_msg' => __('Message could not be sent because you are blocked.', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_msg_not_frnd_msg' => __('Message could not be sent because you are not friend.', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_msg_success_msg' => __('Message sent successfully.', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_com_follow' => 0,
                'arm_follow_btn_txt' => __('Follow', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_unfollow_btn_txt' => __('Unfollow', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_followers_lbl' => __('Followers', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_following_lbl' => __('Followings', ARM_COMMUNITY_TEXTDOMAIN),
                'arm_com_review' => 0,
                'arm_keep_reviews_public' => 0,
                'arm_com_post' => 0,
                'arm_com_activity' => 0,
                'arm_keep_activity_public' => 0,
                'arm_review_approved_by_admin' => 0,
                'arm_review_editable' => 0,
                'arm_record_per_page' => 10,
                'arm_com_pagination_style' => 'more_link',
                'arm_com_post_fimage' => 0,
                'arm_com_post_like' => 0,
                'arm_com_post_comment' => 0,
                'arm_com_post_wall' => 0,
                'arm_review_section_lbl' => __("Review", ARM_COMMUNITY_TEXTDOMAIN),
                'arm_post_section_lbl' => __("Post", ARM_COMMUNITY_TEXTDOMAIN),
                'arm_activity_section_lbl' => __("Activity", ARM_COMMUNITY_TEXTDOMAIN),
                'arm_wall_section_lbl' => __("News Feed", ARM_COMMUNITY_TEXTDOMAIN),
                'arm_profile_section_lbl' => __("Profile", ARM_COMMUNITY_TEXTDOMAIN),
                'arm_com_email_friend_request' => 0,
                'arm_com_email_friend_accept_request' => 0,
                'arm_com_email_received_new_private_message' => 0,
                'arm_com_email_someone_followed_you' => 0,
                'arm_com_email_friend_liked_your_post' => 0,
                'arm_com_email_friend_commented_on_your_post' => 0,
                'arm_com_post_slug' => 'arm_community'
            );
        }

        function arm_community_save_settings() {
            if (current_user_can('arm_community_settings')) {
                global $wpdb, $arm_community_post, $ARMember, $wp_rewrite, $arm_community_features;
                
                if(method_exists($ARMember, 'arm_check_user_cap')){
                    $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                    $ARMember->arm_check_user_cap($arm_community_capabilities['0'],'1');
                }
                $posted_data = $_POST;
               
                if(!isset($posted_data['arm_com_friendship'])){
                    $posted_data['arm_public_friends'] = 0;
                }

                if(!isset($posted_data['arm_com_private_message'])){
                    $posted_data['arm_message_only_friends'] = 0;
                }

                if(!isset($posted_data['arm_com_review'])){
                    $posted_data['arm_keep_reviews_public'] = 0;
                    $posted_data['arm_review_approved_by_admin'] = 0;
                    $posted_data['arm_review_editable'] = 0;
                }
                if(!isset($posted_data['arm_com_post'])){
                    $posted_data['arm_com_post_fimage'] = 0;
                    $posted_data['arm_com_post_like'] = 0;
                    $posted_data['arm_com_post_comment'] = 0;
                    $posted_data['arm_com_post_wall'] = 0;
                }
                if(!isset($posted_data['arm_com_activity'])){
                    $posted_data['arm_keep_activity_public'] = 0;
                }
                unset($posted_data['action']);
                $arm_com_default_settings = $this->arm_community_default_param_value();
                $arm_com_settings = shortcode_atts($arm_com_default_settings, $posted_data);
                update_option('arm_community_settings', $arm_com_settings);
                $response = array();
                $response['type'] = 'success';
                $response['msg'] = __('Community settings saved successfully.', ARM_COMMUNITY_TEXTDOMAIN);
                $response['arm_com_post'] = $posted_data['arm_com_post'];
                $response['arm_com_activity'] = $posted_data['arm_com_activity'];
                $response['arm_com_review'] = $posted_data['arm_com_review'];

                //Update Rewrite Rule.
                $arm_community_post->arm_com_set_session_for_permalink();
                $arm_community_post->arm_com_rewrite_rules_for_custom_post(1);
                flush_rewrite_rules(false);
                $wp_rewrite->flush_rules(false);
                echo json_encode($response);
                die;
            }
        }

        function arm_community_get_settings() {
            $arm_com_default_settings = $this->arm_community_default_param_value();
            $arm_com_settings = get_option('arm_community_settings');
            $arm_com_settings = shortcode_atts($arm_com_default_settings, $arm_com_settings);
            return $arm_com_settings;
        }

        function arm_community_get_setting_val($key = '') {
            $arm_community_val = '';
            if (!empty($key)) {
                $arm_community_val = $this->arm_com_settings[$key];
            }
            return $arm_community_val;
        }

        function arm_com_profile_get_user_id() {
            global $wp_query, $arm_global_settings, $wpdb, $arm_members_directory;
            $reqUser = $wp_query->get('arm_user');
            $current_user_info = false;
            if (empty($reqUser)) {
                $reqUser = (isset($_REQUEST['arm_user']) && !empty($_REQUEST['arm_user'])) ? $_REQUEST['arm_user'] : '';
            }
            
            if (!empty($reqUser)) {
                $permalinkBase = isset($arm_global_settings->global_settings['profile_permalink_base']) ? $arm_global_settings->global_settings['profile_permalink_base'] : 'user_login';
                if ($permalinkBase == 'user_login') {
                    $current_user_info = get_user_by('login', urldecode($reqUser));
                    if(empty($current_user_info)) {
                        $current_user_info = get_user_by('id', $reqUser);
                    }
                } else {
                    $current_user_info = get_user_by('id', $reqUser);
                }
            } else {
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $current_user_info = get_user_by('id', $user_id);
                }
            }
            if (!empty($current_user_info)) {

                $_data = array($current_user_info);
                $user_data = array();

                if (!empty($_data)) {
                    foreach ($_data as $k => $guser) {
                        $user = get_user_by('id', $guser->ID);
                        $user_d = (array) $user->data;
                        $user_data[$user->ID] = array();
                        $user_data[$user->ID] = array_merge($user_data[$user->ID], $user_d);
                    }
                }
                $_data = $user_data;
            }

            return (isset($_data)) ? $_data : array();
        }

        function arm_com_is_profile_editor() {
            if (( isset($_REQUEST['page']) && $_REQUEST['page'] == 'arm_profiles_directories') || ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'arm_change_profile_template' )) {
                return true;
            } else {
                return false;
            }
        }

        function arm_community_get_footer() {
            $footer = '<div class="wrap arm_page arm_manage_members_main_wrapper" style="float:right; margin-right:20px;">';
            $footer .= '<a href="' . ARM_COMMUNITY_URL . '/documentation" target="_blank">';
            $footer .= __('Documentation', ARM_COMMUNITY_TEXTDOMAIN);
            $footer .= '</a>';
            $footer .= '</div>';
            echo $footer;
        }

        function arm_get_community_user_activity_tab($selected_class = "arm_com_post_selected") {
            $arm_com_post_selected = $arm_com_review_selected = $arm_com_activity_selected = "";
            if($selected_class=="arm_com_post_selected") {
                $arm_com_post_selected = " arm_com_page_nav_active";
            }
            else if($selected_class=="arm_com_review_selected") {
                $arm_com_review_selected = " arm_com_page_nav_active";
            }
            else {
                $arm_com_activity_selected = " arm_com_page_nav_active";
            }
            $activity_tab = 
            '<div class="page_title">'.__("Community Activities", ARM_COMMUNITY_TEXTDOMAIN).'</div><div class="armclear"></div>
            <div class="arm_com_navs">
                <a href="'.admin_url("admin.php?page=arm_community_activity&arm_action=armcompost").'" class="arm_com_page_nav arm_com_page_user_post'.$arm_com_post_selected.'">'.__("Manage User Posts", ARM_COMMUNITY_TEXTDOMAIN).'</a>
                <a href="'.admin_url("admin.php?page=arm_community_activity&arm_action=armcomreview").'" class="arm_com_page_nav arm_com_page_user_review'.$arm_com_review_selected.'">'.__("Manage User Reviews", ARM_COMMUNITY_TEXTDOMAIN).'</a>
                <a href="'.admin_url("admin.php?page=arm_community_activity&arm_action=armcomactivity").'" class="arm_com_page_nav arm_com_page_user_activity'.$arm_com_activity_selected.'">'.__("Manage User Activities", ARM_COMMUNITY_TEXTDOMAIN).'</a>
            </div>';
            return $activity_tab;
        }

        function arm_notification_get_list_msg_type_func($message_type) {
            if($message_type=='arm_com_friend_request')
            {
                $message_type = __('On Friend Request Received', ARM_COMMUNITY_TEXTDOMAIN);
            }
            else if($message_type=='arm_com_friend_accept_request') {
                $message_type = __('On Accepted Friend Request', ARM_COMMUNITY_TEXTDOMAIN);
            }
            else if($message_type=='arm_com_received_new_private_message') {
                $message_type = __('On Received New Private message', ARM_COMMUNITY_TEXTDOMAIN);
            }
            else if($message_type=='arm_com_someone_followed_you') {
                $message_type = __('On Someone Followed you', ARM_COMMUNITY_TEXTDOMAIN);
            }
            else if($message_type=='arm_com_friend_liked_your_post') {
                $message_type = __('On Someone Liked your Post', ARM_COMMUNITY_TEXTDOMAIN);
            }
            else if($message_type=='arm_com_friend_commented_on_your_post') {
                $message_type = __('On Friend Commented on your Post', ARM_COMMUNITY_TEXTDOMAIN);
            }
            return $message_type;
        }

        function arm_notification_add_message_types_func($message_types) {
            $message_types['arm_com_friend_request'] = __('On Friend Request Received', ARM_COMMUNITY_TEXTDOMAIN);
            $message_types['arm_com_friend_accept_request'] = __('On Accepted Friend Request', ARM_COMMUNITY_TEXTDOMAIN);
            $message_types['arm_com_received_new_private_message'] = __('On Received New Private message', ARM_COMMUNITY_TEXTDOMAIN);
            $message_types['arm_com_someone_followed_you'] = __('On Someone Followed you', ARM_COMMUNITY_TEXTDOMAIN);
            $message_types['arm_com_friend_liked_your_post'] = __('On Someone Liked your Post', ARM_COMMUNITY_TEXTDOMAIN);
            $message_types['arm_com_friend_commented_on_your_post'] = __('On Friend Commented on your Post', ARM_COMMUNITY_TEXTDOMAIN);

            return $message_types;
        }

        function arm_com_short_number_format($arm_number, $precision = 1) {
            if ($arm_number < 900) {
                $arm_number_format = number_format($arm_number, $precision);
                $arm_suffix = '';
            } else if ($arm_number < 900000) {
                $arm_number_format = number_format($arm_number / 1000, $precision);
                $arm_suffix = 'K';
            } else if ($arm_number < 900000000) {
                $arm_number_format = number_format($arm_number / 1000000, $precision);
                $arm_suffix = 'M';
            } else if ($arm_number < 900000000000) {
                $arm_number_format = number_format($arm_number / 1000000000, $precision);
                $arm_suffix = 'B';
            } else {
                $arm_number_format = number_format($arm_number / 1000000000000, $precision);
                $arm_suffix = 'T';
            }

            if ( $precision > 0 ) {
                $dotzero = '.' . str_repeat( '0', $precision );
                $arm_number_format = str_replace( $dotzero, '', $arm_number_format );
            }
            return $arm_number_format . $arm_suffix;
        }

        function arm_com_get_confirm_box($item_id = 0, $confirmText = '', $btnClass = '', $deleteType = '', $delBtnText = '', $cancelBtnText = '') {
            $confirmBox = "<div class='arm_confirm_box arm_confirm_box_{$item_id}' id='arm_confirm_box_{$item_id}'>";
            $confirmBox .= "<div class='arm_confirm_box_body'>";
            $confirmBox .= "<div class='arm_confirm_box_arrow'></div>";
            $confirmBox .= "<div class='arm_confirm_box_text'>{$confirmText}</div>";
            $confirmBox .= "<div class='arm_confirm_box_btn_container'>";
            $confirmBox .= "<button type='button' class='arm_confirm_box_btn armok {$btnClass}' data-item_id='{$item_id}' data-type='{$deleteType}'>" . $delBtnText . "</button>";
            $confirmBox .= "<button type='button' class='arm_confirm_box_btn armcancel' onclick='hideConfirmBoxCallback();'>" . $cancelBtnText . "</button>";
            $confirmBox .= "</div>";
            $confirmBox .= "</div>";
            $confirmBox .= "</div>";
            return $confirmBox;
        }
    }
}
global $arm_community_setting;
$arm_community_setting = new ARM_Community_setting();