<?php
if (!class_exists('ARM_Community_Follow')) {
    class ARM_Community_Follow {
        function __construct() {
            add_shortcode('arm_community_follow_btn', array(&$this, 'arm_community_follow_btn_func'));
            add_shortcode('arm_community_follow_display', array(&$this, 'arm_community_follow_display_func'));
            add_action('wp_ajax_arm_com_follow', array(&$this, 'arm_com_follow'));
            add_action('wp_ajax_arm_com_unfollow', array(&$this, 'arm_com_unfollow'));
            add_action('delete_user', array(&$this, 'arm_com_follow_delete_user_data'));
            add_action('wp_ajax_arm_community_user_following_front', array(&$this, 'arm_community_follow_display_func'));
        }

        function arm_com_follow_allow() {
            global $arm_community_setting;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            return (isset($arm_com_settings['arm_com_follow']) && $arm_com_settings['arm_com_follow'] == '1') ? true : false;
        }

        function arm_community_follow_btn_func($atts = array(), $content = array(), $tag = '') {
            global $wpdb, $arm_community_setting, $arm_community_features;
            $button = '';
            if ($arm_community_setting->arm_com_is_profile_editor()) {
                $user_id = 0;
            }
            else {
                $user_data = $arm_community_setting->arm_com_profile_get_user_id();
                $user_data_arr = array_shift($user_data);
                $user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0;
            }
            $args = shortcode_atts(array('user_id' => $user_id,), $atts, $tag);
            if (is_user_logged_in() && $this->arm_com_follow_allow()) {
                $user_id = get_current_user_id();
                if ($user_id != $args['user_id']) {
                    if ($this->arm_com_is_follow($args['user_id'])) {
                        $button .= $this->arm_com_unfollow_button($args['user_id']);
                    }
                    else {
                        $button .= $this->arm_com_follow_button($args['user_id']);
                    }
                    $content .= '<div id="arm_follow_button" class="arm_follow_button">' . $button . '</div>';
                    $content .= '<span class="arm_error" id="arm_error"></span>';
                }
            }
            return $content;
        }

        function arm_community_follow_display_func($atts = array(), $content = array(), $tag = '') {
            if ($this->arm_com_follow_allow()) {
                global $wpdb, $arm_community_setting, $arm_community_features;
                $button = '';
                if ($arm_community_setting->arm_com_is_profile_editor()) {
                    $user_id = 0;
                }
                else {
                    $user_data = $arm_community_setting->arm_com_profile_get_user_id();
                    $user_data_arr = array_shift($user_data);
                    $user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0;
                }
                $args = shortcode_atts(array('user_id' => $user_id,), $atts, $tag);

                $user_follower = $this->arm_com_get_user_follower($args['user_id']);
                $total_follower = $user_follower[2];
                $follower_paging = $user_follower[1];
                $user_follower = $user_follower[0];

                $user_following = $this->arm_com_get_user_following($args['user_id']);
                $total_following = $user_following[2];
                $following_paging = $user_following[1];
                $user_following = $user_following[0];

                $follower_nav = '<a href="javascript:void(0);" id="arm_com_display_follower">';
                $following_nav = '<a href="javascript:void(0);" id="arm_com_display_following">';

                if( !is_user_logged_in() ) {
                    global $arm_global_settings;
                    $global_settings = $arm_global_settings->arm_get_all_global_settings(TRUE);
                    $user_redirect = get_permalink($global_settings["login_page_id"]);
                    $follower_nav = '<a href="'.$user_redirect.'">';
                    $following_nav = '<a href="'.$user_redirect.'">';
                }

                $content .= '<div class="arm_com_follow_container" id="arm_com_follow_container">';
                $content .= '<div class="arm_com_follow_wrapper" id="arm_com_follower_wrapper">';
                
                $content .= $follower_nav;
                $content .= '<span class="arm_com_follow_count"> ' . $total_follower . ' </span>';
                $content .= '<span class="arm_com_follow_label">'.$arm_community_setting->arm_community_get_setting_val('arm_followers_lbl').'</span>';
                $content .= '</a>';
                $content .= '</div>';
                $content .= '<div class="arm_com_follow_wrapper" id="arm_com_following_wrapper">';

                $content .= $following_nav;
                $content .= '<span class="arm_com_follow_count"> ' . $total_following . ' </span>';
                $content .= '<span class="arm_com_follow_label">'.$arm_community_setting->arm_community_get_setting_val('arm_following_lbl').'</span>';
                $content .= '</a>';
                $content .= '</div>';
                $content .= '<div class="arm_com_follow_btns" id="arm_com_follow_btns">';
                $content .= do_shortcode('[arm_community_follow_btn]');
                $content .= '</div>';

                $arm_com_settings = $arm_community_setting->arm_com_settings;
                $arm_followers_lbl = !empty($arm_com_settings["arm_followers_lbl"]) ? $arm_com_settings["arm_followers_lbl"] : __("Followers", ARM_COMMUNITY_TEXTDOMAIN);
                $arm_following_lbl = !empty($arm_com_settings["arm_following_lbl"]) ? $arm_com_settings["arm_following_lbl"] : __("Following", ARM_COMMUNITY_TEXTDOMAIN);

                $content .= $this->arm_com_follow_popup_display($arm_followers_lbl, 'follower', $user_follower, $follower_paging);
                $content .= $this->arm_com_follow_popup_display($arm_following_lbl, 'following', $user_following, $following_paging);
                $content .= '</div>';
            }
            return $content;
        }

        function arm_com_get_user_follower($user_id) {
            global $wpdb, $arm_community_features,$arm_community_setting, $arm_global_settings;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $records_per_page = isset($arm_com_settings["arm_record_per_page"]) ? $arm_com_settings["arm_record_per_page"] : 10;

            $arm_get_user_follower = $wpdb->get_results($wpdb->prepare("SELECT arm_follower_id FROM `" . $arm_community_features->tbl_arm_com_follow . "` WHERE arm_following_id=%d ", $user_id), ARRAY_A);

            $total_records = count($arm_get_user_follower);

            $paging = "";
            if( $total_records > $records_per_page ) {
                $pageno = !empty($_REQUEST["pageno"]) ? $_REQUEST["pageno"] : 1;
                $offset = ( $pageno - 1 ) * $records_per_page;
                $paging = $arm_global_settings->arm_get_paging_links($pageno, $total_records, $records_per_page, 'arm_follower');

                $arm_get_user_follower = $wpdb->get_results($wpdb->prepare("SELECT arm_follower_id FROM `" . $arm_community_features->tbl_arm_com_follow . "` WHERE arm_following_id=%d LIMIT {$offset},{$records_per_page}", $user_id), ARRAY_A);
            }

            $arm_user_follower_arr = array();
            if (!empty($arm_get_user_follower)) {
                foreach ($arm_get_user_follower as $arm_user_data) {
                    array_push($arm_user_follower_arr, $arm_user_data['arm_follower_id']);
                }
            }
            return array($arm_user_follower_arr, $paging, $total_records);
        }

        function arm_com_get_user_following($user_id) {
            global $wpdb, $arm_community_features,$arm_community_setting, $arm_global_settings;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $records_per_page = isset($arm_com_settings["arm_record_per_page"]) ? $arm_com_settings["arm_record_per_page"] : 10;

            $arm_get_user_following = $wpdb->get_results($wpdb->prepare("SELECT arm_following_id FROM `" . $arm_community_features->tbl_arm_com_follow . "` WHERE arm_follower_id=%d ", $user_id), ARRAY_A);

            $total_records = count($arm_get_user_following);

            $paging = "";
            if( $total_records > $records_per_page ) {
                $pageno = !empty($_REQUEST["pageno"]) ? $_REQUEST["pageno"] : 1;
                $offset = ( $pageno - 1 ) * $records_per_page;
                $paging = $arm_global_settings->arm_get_paging_links($pageno, $total_records, $records_per_page, 'arm_following');

                $arm_get_user_following = $wpdb->get_results($wpdb->prepare("SELECT arm_following_id FROM `" . $arm_community_features->tbl_arm_com_follow . "` WHERE arm_follower_id=%d LIMIT {$offset},{$records_per_page} ", $user_id), ARRAY_A);
            }

            $arm_user_following_arr = array();
            if (!empty($arm_get_user_following)) {
                foreach ($arm_get_user_following as $arm_user_data) {
                    array_push($arm_user_following_arr, $arm_user_data['arm_following_id']);
                }
            }
            return array($arm_user_following_arr, $paging, $total_records);
        }

        function arm_com_follow_popup_display($type_lbl, $type, $userlist, $paging) {
            $arm_user_data = array();
            if (!empty($userlist)) {
                $arm_user_arg = array (
                    'orderby' => 'display_name',
                    'order' => 'DESC',
                    'include' => $userlist
                );
                $arm_user_data = get_users($arm_user_arg);
            }

            $popup = '';
            $popup .= '<div class="arm_com_' . $type . '_popup popup_wrapper arm_popup_wrapper arm_popup_community_form" style="width: 650px; margin-top: 40px;">';
            $popup .= '<div class="popup_wrapper_inner">';
            $popup .= '<div class="popup_header">';
            $popup .= '<span class="popup_close_btn arm_popup_close_btn"></span>';
            $popup .= '<div class="popup_header_text arm_form_heading_container">';
            $popup .= '<span class="arm_form_field_label_wrapper_text">' . ucfirst($type_lbl) . '</span>';
            $popup .= '</div></div>';

            $popup .= '<div class="popup_content_text arm_com_follow_popup_text">';
            $popup .= "<div class='arm_template_loading'><img src='".MEMBERSHIP_IMAGES_URL."/loader_template.gif' alt='".__('Loading', ARM_COMMUNITY_TEXTDOMAIN)."..' /></div>";
            $popup .= '<div class="arm_follow_form_container">';
            $popup .= '<div class="arm_follow_'.$type.'_box_wrapper">';

            $tmp_popup = "";
            if (!empty($arm_user_data)) {
                global $arm_global_settings;
                foreach ($arm_user_data as $arm_user) {
                    $tmp_popup .= '<div class="arm_follow_user_box">';
                    $tmp_popup .= '<div class="arm_follow_user_image">';

                    $arm_profile_link = $arm_global_settings->arm_get_user_profile_url($arm_user->ID);

                    $tmp_popup .= '<a href="'.$arm_profile_link.'">';
                    $tmp_popup .= get_avatar($arm_user->ID, '100');
                    $tmp_popup .= '</a>';
                    $tmp_popup .= '</div>';
                    $tmp_popup .= '<div class="arm_follow_user_name">';
                    $tmp_popup .= '<a href="' . $arm_profile_link . '" style="margin: 15px; vertical-align: top;">' . $arm_user->display_name . '</a>';
                    $tmp_popup .= '</div>';
                    $tmp_popup .= '</div>';
                }
            }
            else {
                $tmp_popup .= '<div class="arm_follow_no_msg_record">'.__("No any",ARM_COMMUNITY_TEXTDOMAIN).' ' . ucfirst($type) . ' '.__("Found.",ARM_COMMUNITY_TEXTDOMAIN).'</div>';
            }

            if(!empty($_REQUEST['action']) && $_REQUEST['action'] == 'arm_community_user_following_front' && $_REQUEST['action_type'] == $type) {
                $response = array("content" => $tmp_popup, "paging" => $paging);
                echo json_encode($response);
                exit;
            }

            $popup .= $tmp_popup;

            if(!empty($paging)) {
                $paging = "<div class='arm_com_follow_paging_wrapper arm_com_user_".$type."_paging_wrapper'>".$paging."</div>";
            }

            $popup .= '</div></div></div><div class="armclear"></div>'.$paging.'</div></div>';
            return $popup;
        }

        function arm_com_follow_button($user_id) {
            global $arm_community_setting;
            $follow_button = '';
            $follow_button .= '<div id="arm_com_follow_button_wrapper" class="arm_com_follow_button_wrapper">';
            $follow_button .= '<button id="arm_com_follow_btn" class="arm_com_follow_btn" data-user_id="' . $user_id . '">';
            $follow_button .= $arm_community_setting->arm_community_get_setting_val('arm_follow_btn_txt');
            $follow_button .= '</button>';
            $follow_button .= '</div>';
            return $follow_button;
        }

        function arm_com_unfollow_button($user_id) {
            global $arm_community_setting;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $arm_unfollow_btn_txt = !empty($arm_com_settings["arm_unfollow_btn_txt"]) ? $arm_com_settings["arm_unfollow_btn_txt"] : __("Unfollow", ARM_COMMUNITY_TEXTDOMAIN);
            $unfollow_button = '';
            $unfollow_button .= '<div id="arm_com_unfollow_button_wrapper" class="arm_com_unfollow_button_wrapper">';
            $unfollow_button .= $arm_community_setting->arm_com_get_confirm_box($user_id, __("Are you sure you want to unfollow this user?", ARM_COMMUNITY_TEXTDOMAIN), 'arm_com_user_unfollow_btn', '', $arm_unfollow_btn_txt, __('Cancel', ARM_COMMUNITY_TEXTDOMAIN));
            $unfollow_button .= '<button id="arm_com_unfollow_btn" class="arm_com_unfollow_btn" data-user_id="'.$user_id.'">';
            $unfollow_button .= $arm_community_setting->arm_community_get_setting_val('arm_unfollow_btn_txt');
            $unfollow_button .= '</button>';
            $unfollow_button .= '</div>';
            return $unfollow_button;
        }

        function arm_com_is_follow($following_id) {
            $arm_is_follow = false;
            if (is_user_logged_in() && $this->arm_com_follow_allow()) {
                global $wpdb, $arm_community_features;
                $user_id = get_current_user_id();
                if ($following_id != $user_id) {
                    $check_is_already_follow = $wpdb->get_row($wpdb->prepare("SELECT   arm_follow_id FROM `" . $arm_community_features->tbl_arm_com_follow . "` WHERE arm_follower_id=%d AND arm_following_id=%d", $user_id, $following_id), ARRAY_A);
                    if (!empty($check_is_already_follow)) {
                        $arm_is_follow = true;
                    }
                }
            }
            return $arm_is_follow;
        }

        function arm_com_follow() {
            global $arm_community_features, $wpdb, $arm_manage_communication;
            $posted_data = $_POST;
            $response = array('type' => 'error', 'msg' => __('Sorry, You are not able to follow this user.', ARM_COMMUNITY_TEXTDOMAIN));
            if (isset($posted_data['user_id']) && !$this->arm_com_is_follow($posted_data['user_id'])) {
                $following_id = $posted_data['user_id'];
                $user_id = get_current_user_id();
                $wpdb->insert($arm_community_features->tbl_arm_com_follow, array('arm_follower_id' => $user_id, 'arm_following_id' => $following_id, 'arm_datetime' => current_time('mysql')), array('%d', '%d', '%s')
                );
                $follow_id = $wpdb->insert_id;
                if ($follow_id > 0) {
                    do_action('arm_com_activity', __('started following to', ARM_COMMUNITY_TEXTDOMAIN) . ' [arm_com_displayname id="' . $following_id . '"]', __('Start Following', ARM_COMMUNITY_TEXTDOMAIN), $following_id, 'follow', 0);

                    $plan_id = get_user_meta($following_id, 'arm_user_plan_ids', true);
                    $plan_id = isset($plan_id[0]) ? $plan_id[0] : 0;
                    $arm_com_message_email_type = 'arm_com_someone_followed_you';
                    $arm_manage_communication->membership_communication_mail($arm_com_message_email_type, $following_id, $plan_id);
                    
                    $response = array('type' => 'success', 'content' => $this->arm_com_unfollow_button($following_id));
                }
            }
            echo json_encode($response);
            die;
        }

        function arm_com_unfollow() {
            global $arm_community_features, $wpdb;
            $posted_data = $_POST;
            $response = array('type' => 'error', 'msg' => __('Sorry, You are not able to unfollow this user.', ARM_COMMUNITY_TEXTDOMAIN));
            if (isset($posted_data['user_id']) && $this->arm_com_is_follow($posted_data['user_id'])) {
                $following_id = $posted_data['user_id'];
                $user_id = get_current_user_id();
                $wpdb->delete($arm_community_features->tbl_arm_com_follow, array('arm_follower_id' => $user_id, 'arm_following_id' => $following_id), array('%d', '%d')
                );

                do_action('arm_com_activity', __('unfollowed', ARM_COMMUNITY_TEXTDOMAIN) . ' [arm_com_displayname id="' . $following_id . '"]', __('Stop Following', ARM_COMMUNITY_TEXTDOMAIN), $following_id, 'unfollow', 0);

                $response = array('type' => 'success', 'content' => $this->arm_com_follow_button($following_id));
            }
            echo json_encode($response);
            die;
        }

        function arm_com_follow_delete_user_data($user_id) {
            global $arm_community_features, $wpdb;
            if (isset($user_id) && !empty($user_id)) {
                $wpdb->get_results($wpdb->prepare("DELETE FROM `" . $arm_community_features->tbl_arm_com_follow . "` WHERE arm_follower_id=%d OR arm_following_id=%d", $user_id, $user_id));
            }
        }
    }
}
global $arm_community_follow;
$arm_community_follow = new ARM_Community_Follow();