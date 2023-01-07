<?php
if(!class_exists('ARM_Community_Friendship')) {
	class ARM_Community_Friendship {
		function __construct() {
			add_shortcode('arm_community_friendship', array(&$this, 'arm_com_friendship_func'));
			add_shortcode('arm_community_friendship_tab', array(&$this, 'arm_com_friendship_tab_func'));
			add_action('wp_ajax_arm_com_friendship_send_request', array(&$this, 'arm_com_friendship_send_request'));
			add_action('wp_ajax_arm_com_friendship_approve_request', array(&$this, 'arm_com_friendship_approve_request'));
			add_action('wp_ajax_arm_com_friendship_cancel_request', array(&$this, 'arm_com_friendship_cancel_request'));
			add_action('wp_ajax_arm_com_friendship_cancel_link_request', array(&$this, 'arm_com_friendship_cancel_link_request'));
			add_action('delete_user', array(&$this, 'arm_com_friendship_delete_user_data'));
			add_action('wp_ajax_arm_community_friend_display_front', array(&$this, 'arm_com_friendship_tab_func'));
		}

		function arm_com_friendship_allow() {
			global $arm_community_setting;
			$arm_com_settings = $arm_community_setting->arm_com_settings;
			return (isset( $arm_com_settings['arm_com_friendship'] ) && $arm_com_settings['arm_com_friendship'] == '1') ? true : false;
		}

		function arm_com_friendship_func($atts = array(), $content = array(), $tag = '') {
			global $wpdb, $arm_community_setting, $arm_community_features;
			$button = '';
			if( $arm_community_setting->arm_com_is_profile_editor() ){
                $user_id = 0;
            }
            else {
                $user_data = $arm_community_setting->arm_com_profile_get_user_id();
                $user_data_arr = array_shift($user_data);
                $user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0 ;
            }
            $args = shortcode_atts(array('user_id' => $user_id,), $atts, $tag);
            if( $this->arm_com_friendship_allow() ) {
                $user_id = get_current_user_id();
                if( $user_id != $args['user_id']) {
                    if(!$this->arm_com_friendship_is_friend($args['user_id'])) {
                        if( $this->arm_com_friendship_any_user_send_request( $args['user_id'] ) ) {
                            if( $this->arm_com_friendship_is_request_sent( $args['user_id'] ) ) {
                                $button .= $this->arm_com_friendship_cancel_button( $args['user_id'] );
                            }
                            else {
                                $button .= $this->arm_com_friendship_approve_button( $args['user_id'] );
                            }
                        }
                        else {
                            $button .= $this->arm_com_friendship_send_button( $args['user_id'] );
                        }
                    }
                    else {
                        $button .= $this->arm_com_friendship_unfriend_button( $args['user_id'] );
                    }
                    $content .= '<div id="arm_friendship_button" class="arm_friendship_button">' . $button . '</div>';
                    $content .= '<span class="arm_error" id="arm_error"></span>';
                }
            }
            return $content;
        }
        
        function arm_com_friendship_tab_func($atts = array(), $content = array(), $tag = '') {
            global $wpdb, $arm_community_setting, $arm_community_features;
            $button = '';
            $user_data = $arm_community_setting->arm_com_profile_get_user_id();
            $user_data_arr = array_shift($user_data);
            $user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0 ;
            $args = shortcode_atts(array('user_id' => $user_id,), $atts, $tag);
            if( $this->arm_com_friendship_allow() ) {
                $content.= '<div class="arm_com_friend_container" id="arm_com_friend_container">';
                $content.= $this->arm_com_get_friendship_content($user_id);
                $content.= '</div>';
            }

            return $content;
        }

        function arm_com_get_friendship_content($user_id = 0) {
        	$content = '';
        	$content.= $this->arm_com_friendship_tab($user_id);
            $content.= '<div class="arm_com_frd_content_container">';
            $content .= "<div class='arm_template_loading'><img src='".MEMBERSHIP_IMAGES_URL."/loader_template.gif' alt='".__('Loading', ARM_COMMUNITY_TEXTDOMAIN)."..' /></div>";
            $content.= $this->arm_com_get_friendship_tab_content('current_friends', $user_id);
            $content.= '</div>';
            return $content;
        }

        function arm_com_friendship_tab($user_id = 0) {
            $arm_com_tab_name = $this->arm_com_get_friendship_tab_array($user_id);
            $arm_com_active_tab_name = 'current_friends';
            $tab_content = '<ul class="arm_com_frd_tab_ul">';
            global $wpdb, $arm_community_features;

            $total_friend = $wpdb->get_row("SELECT count(*) AS ttl FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE (arm_initiator_user_id={$user_id} OR arm_friend_id={$user_id} ) AND arm_is_confirmed = 1");
            $total_friend = $total_friend->ttl;

            $total_friend_req  = $wpdb->get_row("SELECT count(arm_initiator_user_id) AS ttl FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE arm_friend_id={$user_id} AND arm_is_confirmed != 1");
            $total_friend_req = $total_friend_req->ttl;

            foreach($arm_com_tab_name as $tab_key => $tab_val) {
                $active_class = ($tab_key == $arm_com_active_tab_name) ? ' arm_com_frd_tab_active' : '';
                $arm_ttl_count = ($active_class != "") ? $total_friend : $total_friend_req;
                $tab_content .= '<li class="arm_com_frd_tab_li ' . $active_class . '" data-tab_key="' . $tab_key . '"><a href="javascript:void(0);" class="arm_com_frd_tab_a">' . $tab_val . ' ('.$arm_ttl_count.')</a></li>';
            }
            $tab_content .= '</ul>';
            return $tab_content;
        }

        function arm_com_get_friendship_tab_content($arm_com_active_tab_name = 'current_friends', $user_id=0) {
            global $arm_community_setting;
            $arm_com_tab_name = $this->arm_com_get_friendship_tab_array($user_id);

            $tab_content = $tab_key_type = '';

            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $arm_unfriend_lbl = !empty($arm_com_settings["arm_unfriend_lbl"]) ? $arm_com_settings["arm_unfriend_lbl"] : __("Unfriend", ARM_COMMUNITY_TEXTDOMAIN);

            $tab_content .= "<div class='arm_com_friend_delete_btn_div'>" . $arm_community_setting->arm_com_get_confirm_box(0, __("Are you sure you want to remove this friend?", ARM_COMMUNITY_TEXTDOMAIN), 'arm_com_friend_delete_btn', '', $arm_unfriend_lbl, __('Cancel', ARM_COMMUNITY_TEXTDOMAIN)) . "</div>";

            foreach($arm_com_tab_name as $tab_key => $tab_val) {
                $active_class = ($tab_key == $arm_com_active_tab_name) ? ' arm_com_frd_tab_content_active' : '';
                $tab_content .= '<div class="arm_com_frd_content_main_wrapper arm_com_frd_content_'.$tab_key.$active_class.'">';

                $show_friend_button = false;

                if(is_user_logged_in()) {
                    $current_user_id = get_current_user_id();
                    if($user_id == $current_user_id) {
                        $user_id = get_current_user_id();
                        $show_friend_button = true;
                    }
                }


                $arm_user_friends_arr = array();
                if ($tab_key == 'friend_requests') {
                    $tab_key_type = 'friend_requests';
                    $arm_user_friends_arr = $this->arm_com_get_friends_request( $user_id );
                    $paging = $arm_user_friends_arr["paging"];
                    $arm_user_friends_arr = $arm_user_friends_arr["requests"];
                    $arm_user_friends_msg = $arm_community_setting->arm_community_get_setting_val('arm_no_friend_requests_msg');
                    $arm_user_function_button = 'arm_com_friendship_approve_button';
                }
                else {
                    $tab_key_type = 'current_friends';
                    $arm_user_friends_arr = $this->arm_com_get_user_friends( $user_id );
                    $paging = $arm_user_friends_arr["paging"];
                    $arm_user_friends_arr = $arm_user_friends_arr["user_friends"];
                    $arm_user_friends_msg = $arm_user_friends_msg = $arm_community_setting->arm_community_get_setting_val('arm_no_friend_msg');
                    $arm_user_function_button = 'arm_com_friendship_unfriend_button';
                }


                if(!empty($arm_user_friends_arr)) {
                    $arm_user_arg = array(
                        'orderby' => 'display_name',
                        'order'   => 'DESC',
                        'number'  => count($arm_user_friends_arr),
                        'include' => $arm_user_friends_arr
                    );
                    $arm_user_data = get_users($arm_user_arg);
                }
                else {
                    $arm_user_data = '';
                }


                if( $arm_community_setting->arm_com_is_profile_editor() ) {
                    $arm_user_data = array();
                    $arm_user_data[0] = new stdClass();
                    $arm_user_data[0]->ID = $user_id;
                    $arm_user_data[0]->display_name = 'Test User';
                    $arm_user_data[1] = new stdClass();
                    $arm_user_data[1]->ID = 2;
                    $arm_user_data[1]->display_name = 'Dummy User';
                }

                if(!empty($arm_user_data)) {
                    global $arm_global_settings;
                    $count_users = count($arm_user_data);
                    $tab_content_tmp = "";
                    foreach ( $arm_user_data as $arm_user ) {
                        $tab_content_tmp .= '<div class="arm_com_user_box" id="arm_com_user_box_'.$arm_user->ID.'">';

                        if( $arm_community_setting->arm_com_is_profile_editor() ) {
                            $profile_url = '#';
                        }
                        else {
                            $profile_url = $arm_global_settings->arm_get_user_profile_url($arm_user->ID);
                        }

                        $tab_content_tmp .=  '<a href="'.$profile_url.'" class="arm_com_user_friend_avatar">'.get_avatar( $arm_user->ID , 50 ).'</a>';
                        $tab_content_tmp .= '<a href="'.$profile_url.'" class="arm_com_user_friend_name">'.$arm_user->display_name.'</a>';

                        $tab_content_tmp .= '<div class="arm_com_user_box_right_bottom">';

                        if($show_friend_button) {
                            $tab_content_tmp .= $this->$arm_user_function_button( $arm_user->ID, 1 );
                        }
                        
                        $tab_content_tmp .= '</div>';
                        $tab_content_tmp .= '</div>';
                    }

                    if($tab_key_type == "friend_requests") {

                        $tab_content .= "<div class='arm_com_user_box_div_req_wrapper'>" . $tab_content_tmp . "</div>";

                        if(!empty($paging)) {
                            $tab_content .= "<div class='arm_friend_req_list_paging_div'>".$paging."</div>";
                        }
                    }
                    else {
                        $tab_content .= "<div class='arm_com_user_box_div_wrapper'>" . $tab_content_tmp . "</div>";
                        if(!empty($paging)) {
                            $tab_content .= "<div class='arm_current_friend_paging_div'>".$paging."</div>";
                        }
                    }
                }
                else {
                    $tab_content .= '<div class="arm_com_msg_no_records">'.$arm_user_friends_msg.'</div>';
                }

                $tab_content .= '</div>';
            }


            if(isset($_REQUEST["arm_friend_tab_type"])) {
                $response = array("content" => $tab_content_tmp, "paging" => $paging);
                echo json_encode($response);
                exit;
            }
            else {
                return $tab_content;
            }
        }

        function arm_com_get_friendship_tab_array($user_id = 0) {

            global $arm_community_setting;

            if( isset($_REQUEST["arm_friend_tab_type"]) ) {
                if( $_REQUEST["arm_friend_tab_type"] == "current_friends" ) {
                    return array('current_friends' => $arm_community_setting->arm_community_get_setting_val('arm_current_friends_lbl'));
                }
                else {
                    return array('friend_requests' => $arm_community_setting->arm_community_get_setting_val('arm_friend_requests_lbl'));
                }
            }
            else {

                $arm_current_friends_lbl = $arm_community_setting->arm_community_get_setting_val('arm_current_friends_lbl');

                $return_array = array('current_friends' => $arm_current_friends_lbl);

                if(is_user_logged_in()) {
                    $current_user_id = get_current_user_id();
                    if($user_id != $current_user_id) {
                        return $return_array; 
                    }
                    else {
                        return array (
                            'current_friends' => $arm_current_friends_lbl,
                            'friend_requests' => $arm_community_setting->arm_community_get_setting_val('arm_friend_requests_lbl')
                        );
                    }
                }
                else {
                    return $return_array;
                }
            }
        }

        function arm_com_get_user_friends( $user_id ) {
            global $wpdb, $arm_community_features, $arm_community_setting;

            $friend_qry = "SELECT * FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE (arm_initiator_user_id={$user_id} OR arm_friend_id={$user_id} ) AND arm_is_confirmed = 1";

            $total_friend_qry = $wpdb->get_row("SELECT count(*) AS ttl FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE (arm_initiator_user_id={$user_id} OR arm_friend_id={$user_id} ) AND arm_is_confirmed = 1");

            $total_friend = $total_friend_qry->ttl;        

            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $records_per_page = isset($arm_com_settings["arm_record_per_page"]) ? $arm_com_settings["arm_record_per_page"] : 10;

            $paging = "";

            if($total_friend > $records_per_page) {

                $pageno = !empty($_REQUEST["pageno"]) ? $_REQUEST["pageno"] : 1;

                $offset = ($pageno - 1) * $records_per_page;

                $friend_qry .= " LIMIT {$offset}, {$records_per_page}";

                $com_pagination_style = isset($arm_com_settings["arm_com_pagination_style"]) ? $arm_com_settings["arm_com_pagination_style"] : 'numeric';

                if($com_pagination_style == "numeric") {
                    global $arm_global_settings;
                    $paging = $arm_global_settings->arm_get_paging_links($pageno, $total_friend, $records_per_page, 'arm_com_current_friends');
                }
                else {

                    $total_pages = ceil( $total_friend / $records_per_page );

                    $more_link_cnt = '<a class="arm_com_friends_load_more_link arm_page_numbers" href="javascript:void(0)" data-page="' . ($pageno + 1) . '" data-type="arm_com_friends" data-arm_ttl_page="'.$total_pages.'">' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '</a>';

                    $more_link_cnt .= '<img class="arm_load_more_loader" src="'.MEMBERSHIP_IMAGES_URL.'/arm_loader.gif" alt="' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '" style="display:none;">';

                    $paging .= '<div class="arm_com_arm_com_friends_paging_container arm_com_friends_paging_container_infinite">' . $more_link_cnt . '</div>';
                }
            }

            $arm_user_friends_arr = $wpdb->get_results($friend_qry, ARRAY_A);
            $arm_user_friends = array();
            if( !empty( $arm_user_friends_arr ) ) {
                foreach ($arm_user_friends_arr as $arm_user_data) {
                    if($arm_user_data['arm_initiator_user_id'] != $user_id) {
                        array_push($arm_user_friends, $arm_user_data['arm_initiator_user_id']);
                    }
                    else {
                        array_push($arm_user_friends, $arm_user_data['arm_friend_id']);
                    }
                }
            }
            return array("user_friends" => $arm_user_friends, "paging" => $paging) ;
        }

        function arm_com_get_friends_request( $user_id ) {
            global $wpdb, $arm_community_features, $arm_community_setting;
            $arm_get_user_friend_requests_arr = array();

            $request_query = "SELECT arm_initiator_user_id FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE arm_friend_id={$user_id} AND arm_is_confirmed != 1";

            $arm_get_user_friend_requests = $wpdb->get_results( $request_query, ARRAY_A );

            $total_request = (!empty($arm_get_user_friend_requests)) ? count($arm_get_user_friend_requests) : 0;

            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $records_per_page = isset($arm_com_settings["arm_record_per_page"]) ? $arm_com_settings["arm_record_per_page"] : 10;

            $paging = "";

            if( $total_request > $records_per_page ) {

                $pageno = !empty($_REQUEST["pageno"]) ? $_REQUEST["pageno"] : 1;

                $offset = ( $pageno - 1 ) * $records_per_page;

                $request_query .= " LIMIT {$offset}, {$records_per_page}";

                $arm_get_user_friend_requests = $wpdb->get_results( $request_query, ARRAY_A );

                $com_pagination_style = isset($arm_com_settings["arm_com_pagination_style"]) ? $arm_com_settings["arm_com_pagination_style"] : 'numeric';

                if($com_pagination_style == "numeric") {
                    global $arm_global_settings;
                    $paging = $arm_global_settings->arm_get_paging_links($pageno, $total_request, $records_per_page, 'arm_com_friend_request');
                }
                else {
                    $total_pages = ceil( $total_request / $records_per_page );

                    $more_link_cnt = '<a class="arm_com_friend_req_load_more_link arm_page_numbers" href="javascript:void(0)" data-page="' . ($pageno + 1) . '" data-type="friend_req" data-arm_ttl_page="'.$total_pages.'">' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '</a>';

                    $more_link_cnt .= '<img class="arm_load_more_loader" src="'.MEMBERSHIP_IMAGES_URL.'/arm_loader.gif" alt="' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '" style="display:none;">';

                    $paging .= '<div class="arm_com_friend_req_paging_container arm_com_friend_req_paging_container_infinite">' . $more_link_cnt . '</div>';
                }
            }

            if( !empty( $arm_get_user_friend_requests ) ) {
                foreach ($arm_get_user_friend_requests as $arm_user_data) {
                    array_push($arm_get_user_friend_requests_arr, $arm_user_data['arm_initiator_user_id']);
                }
            }

            return array("requests" => $arm_get_user_friend_requests_arr, "paging" => $paging);
        }

        function arm_com_friendship_send_button( $friend_id, $arm_is_for_tab = 0 ) {
            global $arm_community_setting;
            $send_button = '';
            $send_button.= '<div id="arm_com_friendship_send_button_wrapper" class="arm_com_friendship_send_button_wrapper">';

            if(is_user_logged_in()) {
                $send_button.= '<button id="arm_com_friendship_send_btn" class="arm_com_friendship_send_btn" data-friend_id="'.$friend_id.'" data-is_for_tab="'.$arm_is_for_tab.'">';
                $send_button.= $arm_community_setting->arm_community_get_setting_val('arm_send_friend_request_lbl');
                $send_button.= '</button>';
            }
            else {
                global $arm_global_settings;
                $global_settings = $arm_global_settings->arm_get_all_global_settings(TRUE);
                $login_page = get_permalink($global_settings["login_page_id"]);

                $send_button.= '<a href="'.$login_page.'" id="arm_com_friendship_send_btn" class="arm_com_friendship_send_btn" data-friend_id="'.$friend_id.'" data-is_for_tab="'.$arm_is_for_tab.'">';
                $send_button.= $arm_community_setting->arm_community_get_setting_val('arm_send_friend_request_lbl');
                $send_button.= '</a>';
            }

            $send_button.= '</div>';
            return $send_button;
        }
        
        function arm_com_friendship_approve_button( $friend_id, $arm_is_for_tab = 0 ) {
            global $arm_community_setting;
            $approve_button = '';

            $logged_in_user_id = get_current_user_id();
            $approve_button .= '<div class="arm_friendship_buttons">';
            $approve_button.= '<a id="arm_com_friendship_approve_btn" class="arm_com_friendship_approve_btn" data-friend_id="'.$friend_id.'" data-is_for_tab="'.$arm_is_for_tab.'">';

            $approve_button.= $arm_community_setting->arm_community_get_setting_val('arm_accept_friend_request_lbl');
            $approve_button.= '</a>';

            $approve_button.= $this->arm_com_friendship_cancel_link($logged_in_user_id, $arm_is_for_tab,  $friend_id);
            
            $approve_button .= '<div id="arm_com_fs_display_loader_'.$friend_id.'" class="arm_com_fs_display_loader" style="display:none;">';
            $approve_button .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . '/arm_loader.gif" /></div>';
            $approve_button .= '</div>';

            return $approve_button;
        }
        
        function arm_com_friendship_cancel_button( $friend_id, $arm_is_for_tab = 0 ) {
            global $arm_community_setting;
            $cancel_button = '';
            
            $cancel_button.= '<a id="arm_com_friendship_cancel_btn" class="arm_com_friendship_cancel_btn" data-friend_id="'.$friend_id.'" data-is_for_tab="'.$arm_is_for_tab.'">';
            $cancel_button.= $arm_community_setting->arm_community_get_setting_val('arm_cancel_friend_request_lbl');
            $cancel_button.= '</a>';
            
            return $cancel_button;
        }

        function arm_com_friendship_cancel_link( $user_id, $arm_is_for_tab = 0 , $friend_id = 0) {
            global $arm_community_setting;
            $cancel_button = '';
            $cancel_button.= '<a id="arm_com_friendship_cancel_link" class="arm_com_friendship_cancel_link" data-friend_id="'.$friend_id.'" data-is_for_tab="'.$arm_is_for_tab.'">';
            $cancel_button.= $arm_community_setting->arm_community_get_setting_val('arm_cancel_friend_request_lbl');
            $cancel_button.= '</a>';
           
            return $cancel_button;
        }
        
        function arm_com_friendship_unfriend_button( $friend_id, $arm_is_for_tab = 0 ) {
            global $arm_community_setting;
            $unfriend_button = '<img src="' . ARM_COMMUNITY_IMAGES_URL . '/arm_loader.gif" class="arm_com_friendship_unfriend_button_loader" />';
            
            $tmp_class = ($arm_is_for_tab == 1) ? "arm_com_friendship_unfriend_btn" : "arm_com_friendship_unfriend_nav";

            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $arm_unfriend_lbl = !empty($arm_com_settings["arm_unfriend_lbl"]) ? $arm_com_settings["arm_unfriend_lbl"] : __("Unfriend", ARM_COMMUNITY_TEXTDOMAIN);

            $unfriend_button .= $arm_community_setting->arm_com_get_confirm_box(0, __("Are you sure you want to remove this friend?", ARM_COMMUNITY_TEXTDOMAIN), 'arm_com_friend_delete_btn', '', $arm_unfriend_lbl, __('Cancel', ARM_COMMUNITY_TEXTDOMAIN));

            $unfriend_button .= '<a id="arm_com_friendship_cancel_btn" class="'.$tmp_class.' arm_com_friendship_unfriend_link" data-friend_id="'.$friend_id.'" data-is_for_tab="'.$arm_is_for_tab.'">';

            $unfriend_button .= $arm_community_setting->arm_community_get_setting_val('arm_unfriend_lbl');
            $unfriend_button .= '</a>';
            return $unfriend_button;
        }
        
        function arm_com_friendship_is_friend( $friend_id ) {
            $arm_is_friend = false;
            if( is_user_logged_in() && $this->arm_com_friendship_allow() ) {
                global $wpdb, $arm_community_features;
                $user_id = get_current_user_id();
                if( $friend_id != $user_id ) {
                    $check_is_already_friend = $wpdb->get_row($wpdb->prepare("SELECT arm_friendship_id FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE ( arm_initiator_user_id=%d AND arm_friend_id=%d AND arm_is_confirmed=%d ) || ( arm_initiator_user_id=%d AND arm_friend_id=%d AND arm_is_confirmed=%d  ) ", $user_id, $friend_id, 1, $friend_id, $user_id, 1), ARRAY_A);
                    if( !empty($check_is_already_friend)) {
                        $arm_is_friend = true;
                    }
                }
            }
            return $arm_is_friend;
        }
        
        function arm_com_friendship_any_user_send_request( $friend_id ) {
            $arm_allow_to_send_request = false;
            if( is_user_logged_in() && $this->arm_com_friendship_allow() ) {
                global $wpdb, $arm_community_features;
                $user_id = get_current_user_id();
                if( $friend_id != $user_id ) {
                    $check_is_already_friend = $wpdb->get_row( $wpdb->prepare("SELECT arm_friendship_id FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE ( arm_initiator_user_id=%d AND arm_friend_id=%d ) || ( arm_initiator_user_id=%d AND arm_friend_id=%d )", $user_id, $friend_id, $friend_id, $user_id ), ARRAY_A );
                    if( !empty($check_is_already_friend) ) {
                        $arm_allow_to_send_request = true;
                    }
                }
            }
            return $arm_allow_to_send_request;
        }
        
        function arm_com_friendship_is_request_sent( $friend_id ) {
            $arm_allow_to_send_request = false;
            if( is_user_logged_in() && $this->arm_com_friendship_allow() ) {
                global $wpdb, $arm_community_features;
                $user_id = get_current_user_id();
                if( $friend_id != $user_id ) {
                    $check_is_already_friend = $wpdb->get_row( $wpdb->prepare("SELECT arm_friendship_id FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE arm_initiator_user_id=%d AND arm_friend_id=%d", $user_id, $friend_id ), ARRAY_A );
                    if( !empty($check_is_already_friend) ) {
                        $arm_allow_to_send_request = true;
                    }
                } 
            }
            return $arm_allow_to_send_request;
        }

        function arm_com_friendship_is_request_sent_link( $friend_id ) {
            $arm_allow_to_send_request = false;
            if( is_user_logged_in() && $this->arm_com_friendship_allow() ) {
                global $wpdb, $arm_community_features;
                $user_id = get_current_user_id();
                if( $friend_id != $user_id ) {
                    $check_is_already_friend = $wpdb->get_row( $wpdb->prepare("SELECT arm_friendship_id FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE arm_initiator_user_id=%d AND arm_friend_id=%d", $friend_id, $user_id ), ARRAY_A );
                    if( !empty($check_is_already_friend) ) {
                        $arm_allow_to_send_request = true;
                    }
                } 
            }
            return $arm_allow_to_send_request;
        }
        
        function arm_com_friendship_send_request() {
            global $arm_community_features, $wpdb, $arm_community_setting, $arm_manage_communication;
            $posted_data = $_POST;
            $cancel_label = __('Send', ARM_COMMUNITY_TEXTDOMAIN);
            $error_msg = str_replace('[code]', $cancel_label ,$arm_community_setting->arm_community_get_setting_val('arm_friend_error_msg'));
            $response = array( 'type' => 'error', 'msg' => $error_msg );
            if( isset( $posted_data['friend_id'] ) && !$this->arm_com_friendship_is_request_sent( $posted_data['friend_id'] ) && $this->arm_com_friendship_allow() && !$this->arm_com_friendship_is_friend( $posted_data['friend_id'] ) ) {
                $friend_id = $posted_data['friend_id'];
                $user_id = get_current_user_id();
                $wpdb->insert( $arm_community_features->tbl_arm_com_friendship, 
                        array( 'arm_initiator_user_id' => $user_id, 'arm_friend_id' => $friend_id, 'arm_datetime' => current_time('mysql') ), 
                        array( '%d', '%d', '%s' )  
                );
                $friendship_id = $wpdb->insert_id;
                if($friendship_id > 0) {
                    do_action('arm_com_activity', '[arm_com_displayname id="' . $user_id . '"] ' . __('send friend request to', ARM_COMMUNITY_TEXTDOMAIN) . ' [arm_com_displayname id="' . $friend_id . '"]', __('Friend Request Sent', ARM_COMMUNITY_TEXTDOMAIN), $friend_id, 'friend_request_sent', 0);

                    $plan_id = get_user_meta($friend_id, 'arm_user_plan_ids', true);
                    $plan_id = isset($plan_id[0]) ? $plan_id[0] : 0;
                    $arm_com_message_email_type = 'arm_com_friend_request';
                    $arm_manage_communication->membership_communication_mail($arm_com_message_email_type, $friend_id, $plan_id);

                    $response = array('type' => 'success', 'content'=> $this->arm_com_friendship_cancel_button($friend_id));
                }
            }
            echo json_encode($response);
            die;
        }
        
        function arm_com_friendship_approve_request($user_id = 0) {
            global $arm_community_features, $wpdb, $arm_community_setting, $arm_manage_communication;
            $posted_data = $_POST;

            $cancel_label = __('Approve', ARM_COMMUNITY_TEXTDOMAIN);
            $error_msg = str_replace('[code]', $cancel_label ,$arm_community_setting->arm_community_get_setting_val('arm_friend_error_msg'));

            $response = array( 'type' => 'error', 'msg' =>$error_msg );

            if( isset($posted_data['friend_id']) && !$this->arm_com_friendship_is_friend($posted_data['friend_id'])  ) {

                $friend_id = $posted_data['friend_id'];
            
                $user_id = get_current_user_id();
                $wpdb->update( $arm_community_features->tbl_arm_com_friendship,
                        array( 'arm_is_confirmed' => 1 ), 
                        array( 'arm_initiator_user_id' => $friend_id, 'arm_friend_id' => $user_id ), 
                        array( '%d' ),
                        array( '%d', '%d' )
                );

                do_action('arm_com_activity', '[arm_com_displayname id="' . $user_id . '"] ' . __('user become a friend with', ARM_COMMUNITY_TEXTDOMAIN) . ' [arm_com_displayname id="' . $friend_id . '"]', __('Friendship Established', ARM_COMMUNITY_TEXTDOMAIN), $friend_id, 'become_friend', 0);

                $plan_id = get_user_meta($friend_id, 'arm_user_plan_ids', true);
                $plan_id = !empty($plan_id[0]) ? $plan_id[0] : 0;
                $arm_com_message_email_type = 'arm_com_friend_accept_request';

                $arm_manage_communication->membership_communication_mail($arm_com_message_email_type, $friend_id, $plan_id);
                if(isset($posted_data['arm_is_for_tab']) && $posted_data['arm_is_for_tab'] == 1) {
                	$content = $this->arm_com_get_friendship_content($user_id);
                	$response = array('type' => 'success', 'content' => $content);
                }
                else {
                	$response = array('type' => 'success', 'content' => $this->arm_com_friendship_unfriend_button($friend_id));
                }
            }
            echo json_encode($response);
            die;
        }
        
        function arm_com_friendship_cancel_request($user_id = 0) {
            global $arm_community_features, $wpdb, $arm_community_setting;
            $posted_data = $_POST;
            $cancel_label = __('Cancel', ARM_COMMUNITY_TEXTDOMAIN);
            $error_msg = str_replace('[code]', $cancel_label ,$arm_community_setting->arm_community_get_setting_val('arm_friend_error_msg'));
            $response = array( 'type' => 'error', 'msg' => $error_msg);
            if( isset($posted_data['friend_id']) && $this->arm_com_friendship_allow() && ( $this->arm_com_friendship_is_request_sent($posted_data['friend_id']) || $this->arm_com_friendship_is_friend($posted_data['friend_id']) ) ) {

                $friend_id = $posted_data['friend_id'];
                $user_id = get_current_user_id();

                $wpdb->get_results( $wpdb->prepare( "DELETE FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE ( arm_initiator_user_id=%d AND arm_friend_id=%d ) OR ( arm_initiator_user_id=%d AND arm_friend_id=%d )", $user_id, $friend_id, $friend_id, $user_id ) );

                do_action('arm_com_activity', '[arm_com_displayname id="' . $user_id . '"] ' . __('remove', ARM_COMMUNITY_TEXTDOMAIN) . ' [arm_com_displayname id="' . $friend_id . '"] ' . __('from friend list', ARM_COMMUNITY_TEXTDOMAIN), __('Unfriend', ARM_COMMUNITY_TEXTDOMAIN), $friend_id, 'unfriend', 0);


                if(isset($posted_data['arm_is_for_tab']) && $posted_data['arm_is_for_tab'] == 1) {
                	$content = $this->arm_com_get_friendship_content($user_id);
                	$response = array('type' => 'success', 'content' => $content);
                }
                else {
                	$response = array('type' => 'success', 'content'=> $this->arm_com_friendship_send_button( $friend_id ));
                }
            }
            echo json_encode($response);
            die;
        }

         function arm_com_friendship_cancel_link_request($user_id = 0) {
            global $arm_community_features, $wpdb, $arm_community_setting;
            $posted_data = $_POST;
            $cancel_label = __('Cancel', ARM_COMMUNITY_TEXTDOMAIN);
            $error_msg = str_replace('[code]', $cancel_label ,$arm_community_setting->arm_community_get_setting_val('arm_friend_error_msg'));
            $response = array( 'type' => 'error', 'msg' => $error_msg );
            if( isset($posted_data['friend_id']) && $this->arm_com_friendship_allow() && ( $this->arm_com_friendship_is_request_sent_link($posted_data['friend_id']) || $this->arm_com_friendship_is_friend($posted_data['friend_id']) ) ) {
                
                $user_id = get_current_user_id();
                $friend_id = $posted_data['friend_id'];
                
                $wpdb->get_results( $wpdb->prepare( "DELETE FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE ( arm_initiator_user_id=%d AND arm_friend_id=%d ) OR ( arm_initiator_user_id=%d AND arm_friend_id=%d )", $friend_id, $user_id, $user_id, $friend_id ) );

                do_action('arm_com_activity', '[arm_com_displayname id="' . $user_id . '"] ' . __('cancel', ARM_COMMUNITY_TEXTDOMAIN) . ' [arm_com_displayname id="' . $friend_id . '"] ' . __('friend request', ARM_COMMUNITY_TEXTDOMAIN), __('Friend Request Canceled', ARM_COMMUNITY_TEXTDOMAIN), $friend_id, 'friend_request_canceled', 0);

                if(isset($posted_data['arm_is_for_tab']) && $posted_data['arm_is_for_tab'] == 1) {
                    $content = $this->arm_com_get_friendship_content($user_id);
                }
                else {
                    $content = $this->arm_com_friendship_send_button($friend_id);
                }
                $response = array('type' => 'success', 'content'=> $content);
            }
            echo json_encode($response);
            die;
        }

        function arm_com_friendship_delete_user_data( $user_id ) {
            global $arm_community_features, $wpdb;
            if( isset( $user_id ) && !empty( $user_id ) ) {
                $wpdb->get_results( $wpdb->prepare( "DELETE FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE arm_initiator_user_id=%d OR arm_friend_id=%d", $user_id, $user_id ) );
            }
        }
    }
}
global $arm_community_friendship;
$arm_community_friendship = new ARM_Community_Friendship();