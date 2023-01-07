<?php
if (!class_exists('ARM_Community_Activity')) {
    class ARM_Community_Activity {
        function __construct() {
            add_shortcode('arm_com_displayname', array(&$this, 'arm_com_displayname_func'));
            add_shortcode('arm_com_display_activity', array(&$this, 'arm_com_display_activity_func'));
            add_action('arm_com_activity', array(&$this, 'arm_com_store_activity'), 10, 5);
            add_action('delete_user', array(&$this, 'arm_com_post_delete_user_data'));
            add_action('arm_after_upload_bp_avatar', array(&$this, 'arm_com_after_upload_avatar'));
            add_action('wp_ajax_arm_com_activity_list', array(&$this, 'arm_com_activity_list_func'));
            add_action('wp_ajax_arm_com_activity_bulk_action', array(&$this, 'arm_com_activity_bulk_action'));
            add_action('wp_ajax_arm_com_activity_remove_by_admin', array(&$this, 'arm_com_activity_remove_by_admin'));
            add_action('wp_ajax_arm_community_activity_display_front', array(&$this, 'arm_com_display_activity_func'));
            add_action('wp_ajax_arm_com_change_activity_status', array(&$this, 'arm_com_change_activity_status_func'));
        }

        function arm_com_activity_allow() {
            global $arm_community_setting;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            return (isset($arm_com_settings['arm_com_activity']) && $arm_com_settings['arm_com_activity'] == '1') ? true : false;
        }

        function arm_com_display_activity_func($atts = array(), $content = array(), $tag = '') {
            global $wpdb, $arm_community_setting, $arm_community_features;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            if ($arm_community_setting->arm_com_is_profile_editor()) {
                $user_id = 0;
            } else {
                $user_data = $arm_community_setting->arm_com_profile_get_user_id();
                $user_data_arr = array_shift($user_data);
                $user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0;
            }

            $args = shortcode_atts(array('user_id' => $user_id), $atts, $tag);

            if (is_user_logged_in() && $this->arm_com_activity_allow() && ( !empty($arm_com_settings["arm_keep_activity_public"]) || $user_id == get_current_user_id() || $arm_community_setting->arm_com_is_profile_editor() ) ) {
                $user_id = get_current_user_id();
                $content .= '<div class="arm_com_activity_title_wrapper" id="arm_com_activity_title_wrapper">';

                $arm_activity_section_lbl = !empty($arm_com_settings["arm_activity_section_lbl"]) ? ($arm_com_settings["arm_activity_section_lbl"] . " " . __('Listing', ARM_COMMUNITY_TEXTDOMAIN) ) : __('Activity Listing', ARM_COMMUNITY_TEXTDOMAIN);

                $content .= $arm_activity_section_lbl . '</div>';

                $content .= '<div class="arm_com_activity_display_container" id="arm_com_activity_display_container">';
                $content .= '<div class="arm_com_activity_wrapper" id="arm_com_activity_wrapper">';
                $content .= $this->arm_com_activity_boxes($args['user_id']);
                $content .= '</div>';
                $content .= '</div>';
            }
            return $content;
        }

        function arm_com_activity_boxes($user_id) {
            if (is_user_logged_in()) {
                global $arm_community_setting, $arm_community_features, $arm_community_post, $wpdb, $arm_global_settings;
                $arm_act_content = $arm_loggedin_user_id = '';
                $arm_loggedin_user_id = get_current_user_id();

                if ($arm_community_setting->arm_com_is_profile_editor()) {
                    $arm_activities = array();
                    $arm_activities[0] = new stdClass();
                    $arm_activities[0]->arm_activity_id = 1;
                    $arm_activities[0]->arm_user_from = $arm_loggedin_user_id;
                    $arm_activities[0]->arm_activity = current_time('mysql');
                    $arm_activities[0]->arm_action = __('This is my first title', ARM_COMMUNITY_TEXTDOMAIN);
                    $arm_activities[0]->activity_type = 'post';
                    $arm_activities[0]->type_id = 'post';
                    $arm_activities[0]->arm_datetime = current_time('mysql');

                    $arm_activities[1] = new stdClass();
                    $arm_activities[1]->arm_activity_id = 1;
                    $arm_activities[1]->arm_user_from = $arm_loggedin_user_id;
                    $arm_activities[1]->arm_activity = current_time('mysql');
                    $arm_activities[1]->arm_action = __('This is my first title', ARM_COMMUNITY_TEXTDOMAIN);
                    $arm_activities[1]->activity_type = 'avatar';
                    $arm_activities[1]->type_id = 'avatar';
                    $arm_activities[1]->arm_datetime = current_time('mysql');

                    $arm_activities[2] = new stdClass();
                    $arm_activities[2]->arm_activity_id = 1;
                    $arm_activities[2]->arm_user_from = $arm_loggedin_user_id;
                    $arm_activities[2]->arm_activity = current_time('mysql');
                    $arm_activities[2]->arm_action = __('This is my first title', ARM_COMMUNITY_TEXTDOMAIN);
                    $arm_activities[2]->activity_type = 'like_post';
                    $arm_activities[2]->type_id = 'like_post';
                    $arm_activities[2]->arm_datetime = current_time('mysql');

                    $content_post = new stdClass();
                    $content_post->guid = '#';
                    $content_post->post_title = __('This is my first title', ARM_COMMUNITY_TEXTDOMAIN);
                    $content_post->post_content = __('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.', ARM_COMMUNITY_TEXTDOMAIN);
                }
                else {
                    $arm_com_settings = $arm_community_setting->arm_com_settings;
                    $records_per_page = isset($arm_com_settings["arm_record_per_page"]) ? $arm_com_settings["arm_record_per_page"] : 10;
                    
                    $tmp_query = "SELECT * FROM `{$arm_community_features->tbl_arm_com_activity}` WHERE arm_activity_status = 0 AND (arm_user_from = {$user_id}) OR (arm_user_to = {$user_id} AND activity_type = '') ORDER BY arm_datetime DESC";
                    
                    $arm_activities = $wpdb->get_results($tmp_query);
                    $total_activities = count($arm_activities);
                    if( $total_activities > $records_per_page ) {
                        $pageno = !empty($_REQUEST["pageno"]) ? $_REQUEST["pageno"] : 1;
                        $offset = ( $pageno - 1 ) * $records_per_page;
                        $com_pagination_style = isset($arm_com_settings["arm_com_pagination_style"]) ? $arm_com_settings["arm_com_pagination_style"] : 'numeric';
                        $paging = "";
                        if($com_pagination_style == "numeric") {
                            global $arm_global_settings;
                            $paging = $arm_global_settings->arm_get_paging_links($pageno, $total_activities, $records_per_page, 'activity');
                        }
                        else {
                            $total_pages = ceil( $total_activities / $records_per_page );
                            $more_link_cnt = '<a class="arm_com_activity_load_more_link arm_page_numbers" href="javascript:void(0)" data-page="' . ($pageno + 1) . '" data-type="activity" data-arm_ttl_page="'.$total_pages.'">' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '</a>';
                            $more_link_cnt .= '<img class="arm_load_more_loader" src="'.MEMBERSHIP_IMAGES_URL.'/arm_loader.gif" alt="' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '" style="display:none;">';
                            $paging .= '<div class="arm_com_activity_paging_container arm_com_activity_paging_container_infinite">' . $more_link_cnt . '</div>';
                        }
                        $tmp_query .= " LIMIT {$offset}, {$records_per_page}";
                        $arm_activities = $wpdb->get_results($tmp_query);
                    }
                }

                $arm_act_content_tmp = "";

                if (!empty($arm_activities)) {
                    global $arm_community_activity_meta, $arm_default_user_details_text;
                    foreach ($arm_activities as $activity) {
                        $act_flag = 0;
                        $activity_id = $activity->arm_activity_id;
                        $arm_user_from = $activity->arm_user_from;
                        $arm_user_to = isset($activity->arm_user_to) ? $activity->arm_user_to : 0;
                        $arm_activity = $activity->arm_activity;
                        $arm_action = $activity->arm_action;
                        $arm_activity_type = $activity->activity_type;
                        $arm_type_id = $activity->type_id;
                        $arm_date = $activity->arm_datetime;
                        $author_data = get_user_by('ID', $arm_user_from);
                        $arm_user_from_name = $author_data->display_name;
                        $arm_user_profile_url = $arm_global_settings->arm_get_user_profile_url($user_id, 1);
                        $post_date = $arm_community_post->arm_com_post_time_diff($arm_date);
                        $activity_title = $activity_content = $content_post ='';
                        if ($arm_activity_type == 'post') {
                            $content_post = get_post($arm_type_id);
                            $act_flag = 1;
                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            if (!empty($content_post)) {
                                $activity_title .= ' <a href="' . $content_post->guid . '">' . $content_post->post_title . '</a> ';
                                $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;
                            }
                            else {
                                $activity_title .= ", (" . $arm_default_user_details_text . ")";
                            }
                        } else if ($arm_activity_type == 'like_post') {
                            $act_flag = 1;
                            $content_post = get_post($arm_type_id);
                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            if (!empty($content_post)) {
                                $activity_title .= ' <a href="' . $content_post->guid . '">' . $content_post->post_title . '</a> ';
                                $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;
                            }
                            else {
                                $activity_title .= ", (" . $arm_default_user_details_text . ")";
                            }
                        } else if ($arm_activity_type == 'unlike_post') {
                            $act_flag = 1;
                            $content_post = get_post($arm_type_id);
                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            if (!empty($content_post)) {
                                $activity_title .= ' <a href="' . $content_post->guid . '">' . $content_post->post_title . '</a> ';
                                $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;
                            }
                            else {
                                $activity_title .= ", (" . $arm_default_user_details_text . ")";
                            }
                        } else if ($arm_activity_type == 'avatar') {
                            $act_flag = 1;
                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;
                        } else if ($arm_activity_type == 'friend_request_sent' && $user_id == $activity->arm_user_from) {
                            $act_flag = 1;
                            $arm_user_to_tmp = $arm_user_to;
                            $arm_user_profile_url_tmp = $arm_global_settings->arm_get_user_profile_url($arm_user_to_tmp, 1);
                            $author_data_tmp = get_user_by('ID', $arm_user_to_tmp);
                            $arm_user_from_name_tmp = $author_data_tmp->display_name;
                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            $activity_title .= ' <a href="' . $arm_user_profile_url_tmp . '">' . $arm_user_from_name_tmp . '</a> ';
                            $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;
                        } else if ($arm_activity_type == 'become_friend') {
                            $act_flag = 1;
                            if( $user_id != $arm_user_from ) {
                                $arm_user_from = $activity->arm_user_to;
                                $arm_user_to = $activity->arm_user_from;
                                $arm_user_profile_url_tmp = $arm_user_profile_url;
                                $arm_user_from_name_tmp = $arm_user_from_name;
                                $arm_user_profile_url = $arm_global_settings->arm_get_user_profile_url($arm_user_from, 1);
                                $author_data_tmp = get_user_by('ID', $arm_user_from);
                                $arm_user_from_name = $author_data_tmp->display_name;
                            }
                            else {
                                $arm_user_profile_url_tmp = $arm_global_settings->arm_get_user_profile_url($arm_user_to, 1);
                                $author_data_tmp = get_user_by('ID', $arm_user_to);
                                $arm_user_from_name_tmp = $author_data_tmp->display_name;
                            }

                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            $activity_title .= ' <a href="' . $arm_user_profile_url_tmp . '">' . $arm_user_from_name_tmp . '</a> ';
                            $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;
                        } else if ($arm_activity_type == 'unfriend') {
                            $act_flag = 1;
                            $arm_user_to_tmp = $arm_user_to;
                            $arm_user_profile_url_tmp = $arm_global_settings->arm_get_user_profile_url($arm_user_to_tmp, 1);
                            $author_data_tmp = get_user_by('ID', $arm_user_to_tmp);
                            $arm_user_from_name_tmp = $author_data_tmp->display_name;
                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            $activity_title .= ' <a href="' . $arm_user_profile_url_tmp . '">' . $arm_user_from_name_tmp . '</a> ';
                            $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;
                        } else if ($arm_activity_type == 'friend_request_canceled') {
                            $act_flag = 1;
                            $arm_user_to_tmp = $arm_user_to;
                            $arm_user_profile_url_tmp = $arm_global_settings->arm_get_user_profile_url($arm_user_to_tmp, 1);
                            $author_data_tmp = get_user_by('ID', $arm_user_to_tmp);
                            $arm_user_from_name_tmp = $author_data_tmp->display_name;
                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            $activity_title .= ' <a href="' . $arm_user_profile_url_tmp . '">' . $arm_user_from_name_tmp . '</a> ';
                            $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;
                        } else if ($arm_activity_type == 'follow') {
                            $act_flag = 1;
                            $arm_user_to_tmp = $arm_user_to;
                            $arm_user_profile_url_tmp = $arm_global_settings->arm_get_user_profile_url($arm_user_to_tmp, 1);
                            $author_data_tmp = get_user_by('ID', $arm_user_to_tmp);
                            $arm_user_from_name_tmp = $author_data_tmp->display_name;
                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            $activity_title .= ' <a href="' . $arm_user_profile_url_tmp . '">' . $arm_user_from_name_tmp . '</a> ';
                            $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;
                        } else if ($arm_activity_type == 'unfollow') {
                            $act_flag = 1;
                            $arm_user_to_tmp = $arm_user_to;
                            $arm_user_profile_url_tmp = $arm_global_settings->arm_get_user_profile_url($arm_user_to_tmp, 1);
                            $author_data_tmp = get_user_by('ID', $arm_user_to_tmp);
                            $arm_user_from_name_tmp = $author_data_tmp->display_name;
                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            $activity_title .= ' <a href="' . $arm_user_profile_url_tmp . '">' . $arm_user_from_name_tmp . '</a> ';
                            $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;;
                        } else if ($arm_activity_type == 'add_review') {
                            $act_flag = 1;
                            $arm_user_to_tmp = $arm_user_to;
                            $arm_user_profile_url_tmp = $arm_global_settings->arm_get_user_profile_url($arm_user_to_tmp, 1);
                            $author_data_tmp = get_user_by('ID', $arm_user_to_tmp);
                            $arm_user_from_name_tmp = $author_data_tmp->display_name;
                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            $activity_title .= ' <a href="' . $arm_user_profile_url_tmp . '">' . $arm_user_from_name_tmp . '</a> ';
                            $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;
                        } else if ($arm_activity_type == 'edit_review') {
                            $act_flag = 1;
                            $arm_user_to_tmp = $arm_user_to;
                            $arm_user_profile_url_tmp = $arm_global_settings->arm_get_user_profile_url($arm_user_to_tmp, 1);
                            $author_data_tmp = get_user_by('ID', $arm_user_to_tmp);
                            $arm_user_from_name_tmp = $author_data_tmp->display_name;
                            $activity_title .= '<a href="' . $arm_user_profile_url . '">' . $arm_user_from_name . '</a> ';
                            $activity_title .= $arm_community_activity_meta[$arm_activity_type]["activity"];
                            $activity_title .= ' <a href="' . $arm_user_profile_url_tmp . '">' . $arm_user_from_name_tmp . '</a> ';
                            $activity_title .= __('at', ARM_COMMUNITY_TEXTDOMAIN) . " " . $post_date;
                        }

                        if($act_flag == 1) {
                            $arm_act_content_tmp .= '<div class="arm_com_activity_box" id="arm_com_activity_' . $activity_id . '">';
                            $arm_act_content_tmp .= '<div class="arm_activity_title">';
                            $arm_act_content_tmp .= '<div class="arm_com_activity_user_img">';

                            if( empty($arm_user_to) ) {
                                $arm_user_to = $arm_user_from;
                            }

                            if( $arm_community_setting->arm_com_is_profile_editor() ) {
                                $profile_url = '#';
                            }
                            else {
                                $profile_url = $arm_global_settings->arm_get_user_profile_url($arm_user_to);
                            }

                            $arm_act_content_tmp .= "<a href='".$profile_url."'>";
                            $arm_act_content_tmp .= get_avatar($arm_user_to, '50');
                            $arm_act_content_tmp .= "</a>";

                            $arm_act_content_tmp .= '</div>';
                            $arm_act_content_tmp .= '<div class="arm_com_activity_user_title_box">';
                            $arm_act_content_tmp .= $activity_title;
                            $arm_act_content_tmp .= '<span class="arm_activity_content">' . $activity_content . '</span>';
                            $arm_act_content_tmp .= '</div>';
                            $arm_act_content_tmp .= '</div>';
                            $arm_act_content_tmp .= '</div>';
                        }
                    }

                    $arm_act_content .= "<div class='arm_com_activity_box_wrapper'>" . $arm_act_content_tmp . "</div>";

                    if(!empty($paging)) {
                        $arm_act_content .= "<div class='arm_activity_paging_div'>".$paging."</div>";
                    }
                }
                else {
                    $arm_com_settings = $arm_community_setting->arm_com_settings;
                    $arm_activity_section_lbl = !empty($arm_com_settings["arm_activity_section_lbl"]) ? $arm_com_settings["arm_activity_section_lbl"] : __("News Feed", ARM_COMMUNITY_TEXTDOMAIN);

                    $arm_act_content .= '<div class="arm_com_activity_no_msg">';
                    $arm_act_content .= __("No", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_activity_section_lbl . " " . __("found.", ARM_COMMUNITY_TEXTDOMAIN);
                    $arm_act_content .= '</div>';
                }

                if( isset($_REQUEST["action"]) && $_REQUEST["action"] == "arm_community_activity_display_front" ) {
                    $response = array("content" => $arm_act_content_tmp, "paging" => $paging);
                    echo json_encode($response);
                    exit;
                }
                else {
                    return $arm_act_content;
                }
            }
        }

        function arm_com_activity_list_func() {
            global $wpdb, $arm_global_settings, $arm_community_features, $arm_community_setting, $ARMember;
            
            if( method_exists($ARMember, 'arm_check_user_cap')){
                $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
            }
            $date_format = $arm_global_settings->arm_get_wp_date_time_format();

            $ai = 0;
            $grid_columns = array(
                'user' => __('User', ARM_COMMUNITY_TEXTDOMAIN),
                'activity' => __('Activity', ARM_COMMUNITY_TEXTDOMAIN),
                'action' => __('Action', ARM_COMMUNITY_TEXTDOMAIN),
                'date' => __('Date', ARM_COMMUNITY_TEXTDOMAIN)
            );

            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 3;
            $search_term = !empty($_REQUEST["com_activity_search_term"]) ? $_REQUEST["com_activity_search_term"] : '';
            $start_data = !empty($_REQUEST["com_activity_search_start_data"]) ? $_REQUEST["com_activity_search_start_data"] : '';
            $end_data = !empty($_REQUEST["com_activity_search_end_data"]) ? $_REQUEST["com_activity_search_end_data"] : '';
            $order_by = 'arm_datetime';

            $where_flag = "WHERE 1 = 1";

            if( !empty($search_term) ) {
                $where_flag .= " AND com_user.user_login LIKE '%{$search_term}%'";
            }

            if( !empty($start_data) ) {
                $start_data = date("Y-m-d", strtotime($start_data));
                $where_flag .= " AND com_activity.arm_datetime >= '$start_data'";
            }

            if( !empty($end_data) ) {
                $end_data = date("Y-m-d", strtotime("+1 day", strtotime($end_data)));
                $where_flag .= " AND com_activity.arm_datetime < '$end_data'";
            }

            $user_table = $wpdb->users;

            $tmp_query = "SELECT com_activity.* FROM `{$arm_community_features->tbl_arm_com_activity}` com_activity LEFT JOIN {$user_table} com_user ON com_activity.arm_user_from = com_user.ID ".$where_flag." ORDER BY {$order_by} {$sorting_ord}";

            $arm_activities = $wpdb->get_results($tmp_query);
            $total_after_filter = $total_before_filter = count($arm_activities);

            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            $tmp_query = $tmp_query . " LIMIT {$offset},{$number}";
            $arm_activities = $wpdb->get_results($tmp_query);

            $grid_data = array();
            if (is_array($arm_activities) && count($arm_activities) > 0) {
                global $arm_community_activity_meta;
                foreach ($arm_activities as $arm_acitvity) {
                    $activity_id = $arm_acitvity->arm_activity_id;
                    $user_id = $arm_acitvity->arm_user_from;
                    $arm_activity = $arm_acitvity->arm_activity;
                    $arm_action = $arm_acitvity->arm_action;
                    $arm_date = $arm_acitvity->arm_datetime;
                    $activity_type = $arm_acitvity->activity_type;
                    $arm_activity_status = $arm_acitvity->arm_activity_status;

                    $user = '';
                    if (is_numeric($user_id) && !empty($user_id)) {
                        $user = get_user_by('id', $user_id);
                    }

                    $grid_data[$ai][0] = "<input id=\"cb-item-action-{$activity_id}\" class=\"chkstanard\" type=\"checkbox\" value=\"{$activity_id}\" name=\"item-action[]\">";
                    $grid_data[$ai][1] = $user->display_name;

                    $arm_activity_tmp = $arm_community_activity_meta[$activity_type]["activity"];

                    if ($activity_type == 'post' || $activity_type == 'like_post' || $activity_type == 'unlike_post') {
                        $arm_acitvity->type_id;
                        $content_post = get_post($arm_acitvity->type_id);
                        if( !empty($content_post) ) {
                            $arm_activity_tmp .= ' <a href="' . $content_post->guid . '">' . $content_post->post_title . '</a>';
                        }
                        else {
                            $arm_activity_tmp .= ", (" . __("Post Deleted", ARM_COMMUNITY_TEXTDOMAIN) . ")";
                        }
                    }
                    else if($activity_type == 'follow' || $activity_type == 'unfollow' || $activity_type == 'friend_request_sent' || $activity_type == 'unfriend' || $activity_type == 'friend_request_canceled' || $activity_type == 'add_review' || $activity_type == 'edit_review' || $activity_type == 'become_friend') {
                        $arm_user_to = $arm_acitvity->arm_user_to;
                        $author_data = get_user_by('ID', $arm_user_to);
                        if( !empty($author_data) ) {
                            $arm_user_to_name = $author_data->display_name;
                            $arm_user_profile_url = $arm_global_settings->arm_get_user_profile_url($arm_user_to, 1);
                            $arm_activity_tmp .= ' <a href="' . $arm_user_profile_url . '">' . $arm_user_to_name . '</a> ';
                        }
                        else {
                            $arm_activity_tmp .= ", (" . __("User Deleted", ARM_COMMUNITY_TEXTDOMAIN) . ")";
                        }
                    }

                    $grid_data[$ai][2] = $arm_activity_tmp;
                    $grid_data[$ai][3] = $arm_community_activity_meta[$activity_type]["action"];
                    $grid_data[$ai][4] = date($date_format, strtotime($arm_date));

                    $gridAction = "<div class='arm_grid_action_btn_container'>";

                    if($arm_activity_status == 0) {
                        $gridAction .= "<a href='javascript:void(0)' class='arm_com_user_activity_status_nav' data-arm_activity_id='".$activity_id."' data-arm_status_change_to='1'><img src='" . ARM_COMMUNITY_IMAGES_URL . "/spam.png' class='armhelptip' title='" . __('Spam', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/spam_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/spam.png';\" /></a>";
                    }
                    else {
                        $gridAction .= "<a href='javascript:void(0)' class='arm_com_user_activity_status_nav' data-arm_activity_id='".$activity_id."' data-arm_status_change_to='0'><img src='" . ARM_COMMUNITY_IMAGES_URL . "/unspam.png' class='armhelptip' title='" . __('Unspam', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/unspam_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/unspam.png';\" /></a>";
                    }

                    $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$activity_id});'><img src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete.png' class='armhelptip' title='" . __('Delete', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete.png';\" /></a>";
                    
                    $gridAction .= $arm_community_setting->arm_com_get_confirm_box($activity_id, __("Are you sure you want to delete this activity?", ARM_COMMUNITY_TEXTDOMAIN), 'arm_com_activity_delete_btn', '', __('Delete', ARM_COMMUNITY_TEXTDOMAIN), __('Cancel', ARM_COMMUNITY_TEXTDOMAIN));
                    
                    $gridAction .= "</div>";

                    $grid_data[$ai][5] = $gridAction;

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

        function arm_com_displayname_func($atts = array(), $content = array(), $tag = '') {
            global $ARMember, $arm_global_settings;
            if (isset($atts['id']) && $atts['id'] != '' && $atts['id'] > 0) {
                $user_id = $atts['id'];
                $user_object = get_user_by('ID', $user_id);
                $user_profile_link = $arm_global_settings->arm_get_user_profile_url($user_id, 1);
                $content .= '<a href="' . $user_profile_link . '" target="_blank">' . $user_object->data->display_name . '</a>';
            }
            return $content;
        }

        function arm_com_activity_remove_by_admin() {
            global $arm_community_features, $wpdb, $ARMember;
            
            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
            }
            if (isset($_POST['activity_id']) && $_POST['activity_id'] > 0) {
                if (is_admin()) {
                    $wpdb->delete($arm_community_features->tbl_arm_com_activity, array('arm_activity_id' => $_POST['activity_id']), array('%d')
                    );
                    $response = array('type' => 'success', 'msg' => __('Record is deleted successfully.', ARM_COMMUNITY_TEXTDOMAIN));
                } else {
                    $response = array('type' => 'failed', 'msg' => __('Sorry, You do not have permission to perform this action.', ARM_COMMUNITY_TEXTDOMAIN));
                }
            } else {
                $response = array('type' => 'failed', 'msg' => __('Invalid action.', ARM_COMMUNITY_TEXTDOMAIN));
            }
            echo json_encode($response);
            die;
        }

        function arm_com_activity_bulk_action() {
            global $wpdb, $ARMember, $arm_members_class, $arm_global_settings, $arm_community_features;
            if (!isset($_POST)) {
                return;
            }
            
            if( method_exists($ARMember, 'arm_check_user_cap')){
                $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
            }
            $bulkaction = $arm_global_settings->get_param('action1');
            $ids = $arm_global_settings->get_param('item-action', '');

            if (empty($ids)) {
                $errors[] = __('Please select one or more records.', ARM_COMMUNITY_TEXTDOMAIN);
            } else {
                if ($bulkaction == '' || $bulkaction == '-1') {
                    $errors[] = __('Please select valid action.', ARM_COMMUNITY_TEXTDOMAIN);
                } else {
                    if (!is_array($ids)) {
                        $ids = explode(',', $ids);
                    }

                    if (!current_user_can('arm_community_activity')) {
                        $errors[] = __('Sorry, You do not have permission to perform this action', ARM_COMMUNITY_TEXTDOMAIN);
                    } else {
                        if (is_array($ids)) {
                            foreach ($ids as $id) {
                                $res_var = $wpdb->delete($arm_community_features->tbl_arm_com_activity, array('arm_activity_id' => $id));
                            }
                        }
                        $message = __('Activities has been deleted successfully.', ARM_COMMUNITY_TEXTDOMAIN);
                        $return_array = array('type' => 'success', 'msg' => $message);
                    }
                }
            }
            if (!isset($return_array)) {
                $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
                $ARMember->arm_set_message('success', $message);
            }
            echo json_encode($return_array);
            die;
        }

        function arm_com_after_upload_avatar() {
            if (is_user_logged_in() && $this->arm_com_activity_allow()) {
                $user_id = get_current_user_id();
                $activity = '[arm_com_displayname id="' . $user_id . '"] ' . __('user changed their profile picture.', ARM_COMMUNITY_TEXTDOMAIN);
                $action = __('Member changed profile picture', ARM_COMMUNITY_TEXTDOMAIN);
                $this->arm_com_store_activity($activity, $action, '', 'avatar', '');
            }
        }

        function arm_com_store_activity($activity, $action, $user_to, $activity_type, $type_id) {
            if (is_user_logged_in()) {
                global $arm_community_features, $wpdb;
                $user_id = get_current_user_id();
                $date = current_time('mysql');

                $args = array (
                    'arm_user_from' => $user_id,
                    'arm_activity' => $activity,
                    'arm_action' => $action,
                    'arm_user_to' => $user_to,
                    'activity_type' => $activity_type,
                    'type_id' => $type_id,
                    'arm_datetime' => $date
                );

                $wpdb->insert($arm_community_features->tbl_arm_com_activity, $args, array('%d', '%s', '%s', '%d', '%s', '%d', '%s'));
                $activity_id = $wpdb->insert_id;
            }
        }

        function arm_com_post_delete_user_data($user_id) {
            $arm_posts = '';
            global $arm_community_features, $wpdb;
            if (isset($user_id) && !empty($user_id)) {
                $wpdb->get_results($wpdb->prepare("DELETE FROM `" . $arm_community_features->tbl_arm_com_activity . "` WHERE arm_user_from=%d OR arm_user_to=%d", $user_id, $user_id));
            }
        }

        function arm_com_change_activity_status_func() {
            $response = array("type" => "failed", "msg" => __("Something went wrong. Try after sometime.", ARM_COMMUNITY_TEXTDOMAIN));
            $activity_id = $_REQUEST["activity_id"];
            $status_change_to = $_REQUEST["status_change_to"];

            if(!empty($activity_id) && ($status_change_to == 0 || $status_change_to == 1)) {
                global $arm_community_features,$wpdb, $ARMember;
                
                if( method_exists($ARMember, 'arm_check_user_cap') ){
                    $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                    $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
                }
                $wpdb->get_results($wpdb->prepare("UPDATE `".$arm_community_features->tbl_arm_com_activity."` SET arm_activity_status=%d WHERE arm_activity_id=%d", $status_change_to, $activity_id));
                if( $status_change_to == 0 ) {
                    $msg =  __("Activity unspammed successfully", ARM_COMMUNITY_TEXTDOMAIN);
                }
                else {
                    $msg =  __("Activity spammed successfully", ARM_COMMUNITY_TEXTDOMAIN);
                }
                $response = array("type" => "success", "msg" => $msg);
            }

            echo json_encode($response);
            exit;
        }
    }
}
global $arm_community_activity;
$arm_community_activity = new ARM_Community_Activity();