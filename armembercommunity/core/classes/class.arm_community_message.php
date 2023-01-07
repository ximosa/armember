<?php
if (!class_exists('ARM_Community_Message')) {
    class ARM_Community_Message {
        function __construct() {
            add_shortcode('arm_community_message', array(&$this, 'arm_com_message_func'));
            add_action('wp_ajax_arm_com_message_compose', array(&$this, 'arm_com_message_compose'));
            add_action('wp_ajax_arm_com_message_single_action', array(&$this, 'arm_com_message_single_action'));
            add_action('wp_ajax_arm_com_message_bulk_action', array(&$this, 'arm_com_message_bulk_action'));
            add_action('wp_ajax_arm_com_message_paging_action', array(&$this, 'arm_com_message_paging_action'));
            add_action('wp_ajax_arm_com_block_user', array(&$this, 'arm_com_block_user'));
            add_action('wp_ajax_arm_com_block_user_rmeove', array(&$this, 'arm_com_block_user_rmeove'));
            add_action('wp_ajax_arm_com_get_message_thread', array(&$this, 'arm_com_get_message_thread'));
            add_action('wp_ajax_arm_com_message_delete', array(&$this, 'arm_com_message_delete'));
            add_action('wp_ajax_arm_com_message_archive', array(&$this, 'arm_com_message_archive'));
            add_action('wp_ajax_arm_com_message_inbox', array(&$this, 'arm_com_message_inbox'));
            add_action('wp_ajax_arm_com_get_user_list', array(&$this, 'arm_com_get_user_list'));
            add_action('wp_ajax_arm_com_message_single_delete', array(&$this, 'arm_com_message_single_delete'));
            add_action('wp_ajax_arm_com_get_msg_div', array(&$this, 'arm_com_get_msg_div'));
            add_action('wp_ajax_arm_community_message_fetch_front', array(&$this, 'arm_com_get_message_thread'));
        }

        function arm_com_get_msg_div() {
            $posted_data = $_POST;
            $tab_key = (isset($posted_data['tab_key']) && !empty($posted_data['tab_key'])) ? $posted_data['tab_key'] : 'inbox';
            $response = array('type' => 'error', 'content' => __('Sorry, Something went wrong.', ARM_COMMUNITY_TEXTDOMAIN));
            if (!empty($tab_key)) {
                if ($tab_key == 'inbox') {
                    $content = $this->arm_com_message_get_inbox();
                } else if ($tab_key == 'archive') {
                    $content = $this->arm_com_message_get_archive();
                } else {
                    $content = $this->arm_com_get_privacy_content();
                }

                $response = array('type' => 'success', 'content' => $content);
            }

            echo json_encode($response);
            exit;
        }

        function arm_com_message_single_delete($msg_id = '') {
            global $wpdb, $arm_community_features;
            $posted_data = $_POST;
            
            $msg_id = isset($posted_data['msg_id']) ? $posted_data['msg_id'] : $msg_id;
            $sender_id = $posted_data['sender_id'] ? $posted_data['sender_id'] : 0;
            $receiver_id = $posted_data['receiver_id'] ? $posted_data['receiver_id'] : 0;
            $response = array('type' => 'error', 'msg' => __('Sorry, something went wrong while deleting this message', ARM_COMMUNITY_TEXTDOMAIN));
            if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
            if (!empty($msg_id)) {

                if($sender_id == $user_id){
                    $wpdb->query('update `' . $arm_community_features->tbl_arm_com_message . '` set `arm_sender_delete`=1 where `arm_msg_id`=' . $msg_id);
                }
                else if($receiver_id == $user_id){
                    $wpdb->query('update `' . $arm_community_features->tbl_arm_com_message . '` set `arm_receiver_delete`=1 where `arm_msg_id`=' . $msg_id); 
                }

                $response = array('type' => 'success', 'msg' => __('This message has been deleted.', ARM_COMMUNITY_TEXTDOMAIN));
            }
        }
            echo json_encode($response);
            exit;
        }

        function arm_com_get_user_list($post_type = 'page') {
            $search_key = '';
            $urData = array();
            if (!empty($_POST['action']) && $_POST['action'] == 'arm_com_get_user_list') {
                $search_key = !empty(trim($_POST['search_key'])) ? trim($_POST['search_key']) : '';
                $response = array('status' => 'error', 'data' => __('Sorry, Something went wrong. Please try again.', MEMBERSHIP_TXTDOMAIN));
                if(!empty($search_key)) {
                    $user_id = get_current_user_id();

                    $usrArgs = array(
                        'search' => $search_key,
                        'exclude' => $user_id
                    );

                    $items = get_users($usrArgs);
                    if (!empty($items)) {
                        foreach ($items as $apost) {
                            $urData[] = array(
                                'id' => $apost->ID,
                                'value' => $apost->user_login,
                                'label' => $apost->user_login,
                            );
                        }
                    }
                }

                $response = array('status' => 'success', 'data' => $urData);
                echo json_encode($response);
                exit;
            }
        }

        function arm_com_message_delete() {
            global $wpdb, $arm_community_features;
            $posted_data = $_POST;
            $response = array('type' => 'error', 'message' => __('Sorry, something went wrong while deleting conversation.', ARM_COMMUNITY_TEXTDOMAIN));
            if (!empty($posted_data)) {
                $sender_id = isset($posted_data['sender']) ? $posted_data['sender'] : 0;
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();

                    $wpdb->query('update `' . $arm_community_features->tbl_arm_com_message . '` set `arm_sender_delete`=1,`arm_sender_complete_delete`=1 where (arm_sender_id=' . $user_id  . ' and arm_receiver_id=' . $sender_id . ')');

                    $wpdb->query('update `' . $arm_community_features->tbl_arm_com_message . '` set `arm_receiver_delete`=1, `arm_receiver_complete_delete`=1 where (arm_sender_id=' . $sender_id . ' and arm_receiver_id=' . $user_id . ')'); 

                    $response = array('type' => 'success', 'message' => '');
                    
                }
            }
            echo json_encode($response);
            exit;
        }

        function arm_com_message_archive() {
            global $wpdb, $arm_community_features;
            $posted_data = $_POST;
            $response = array('type' => 'error', 'message' => __('Sorry, something went wrong while hiding conversation.', ARM_COMMUNITY_TEXTDOMAIN));
            if (!empty($posted_data)) {
                $sender_id = isset($posted_data['sender']) ? $posted_data['sender'] : 0;
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $archived_chat = get_user_meta($user_id, 'arm_com_archive_chat', true);
                    $archived_chat = ( $archived_chat != '') ? $archived_chat : array();
                    $archived_chat[] = $sender_id;
                    update_user_meta($user_id, 'arm_com_archive_chat', $archived_chat);
                    $response = array('type' => 'success', 'message' => '');
                }
            }
            echo json_encode($response);
            exit;
        }

        function arm_com_message_inbox() {
            global $wpdb, $arm_community_features;
            $posted_data = $_POST;
            $response = array('type' => 'error', 'message' => __('Sorry, something went wrong while moving this conversation.', ARM_COMMUNITY_TEXTDOMAIN));
            if (!empty($posted_data)) {
                $sender_id = isset($posted_data['sender']) ? $posted_data['sender'] : 0;
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $archived_chat = get_user_meta($user_id, 'arm_com_archive_chat', true);
                    $archived_chat = ( $archived_chat != '') ? maybe_unserialize($archived_chat) : array();

                    unset($archived_chat[array_search($sender_id, $archived_chat)]);

                    update_user_meta($user_id, 'arm_com_archive_chat', $archived_chat);
                    $response = array('type' => 'success', 'message' => '');
                }
            }
            echo json_encode($response);
            exit;
        }

        function arm_com_message_allow() {
            global $arm_community_setting;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            return (isset($arm_com_settings['arm_com_private_message']) && $arm_com_settings['arm_com_private_message'] == '1') ? true : false;
        }

        function arm_com_message_func($atts = array(), $content = array(), $tag = '') {
            global $wpdb, $arm_com_total_msgs_count;
            $arm_com_total_msgs_count = 0;
            $args = shortcode_atts(array('user_id' => 0,), $atts, $tag);
            if (is_user_logged_in() && $this->arm_com_message_allow()) {

                $arm_com_get_message_tab_content = $this->arm_com_get_message_tab_content('inbox');

                $arm_com_get_message_tab = $this->arm_com_get_message_tab();

                $content .= '<div class="arm_com_message_container">';
                $content .= $arm_com_get_message_tab;
                $content .= '<div class="arm_com_msg_content_container">';
                $content .= $arm_com_get_message_tab_content;
                $content .= '</div>';
                $content .= '</div>';
            }
            return $content;
        }

        function arm_com_get_message_tab_array() {
            return array(
                'inbox' => __('Inbox', ARM_COMMUNITY_TEXTDOMAIN),
                'archive' => __('Archive', ARM_COMMUNITY_TEXTDOMAIN),
                'privacy' => __('Block Users', ARM_COMMUNITY_TEXTDOMAIN),
            );
        }

        function arm_com_get_message_tab() {
            $arm_com_tab_name = $this->arm_com_get_message_tab_array();
            $arm_com_active_tab_name = 'inbox';
            $tab_content = '<ul class="arm_com_msg_tab_ul">';
            foreach ($arm_com_tab_name as $tab_key => $tab_val) {
                $active_class = ($tab_key == $arm_com_active_tab_name) ? ' arm_com_msg_tab_active' : '';
                $tab_content .= '<li class="arm_com_msg_tab_li arm_com_msg_' . $tab_key . ' ' . $active_class . '" data-tab_key="' . $tab_key . '">'
                        . '<a href="javascript:void(0);" class="arm_com_msg_tab_a">' . $tab_val . '</a></li>';
            }
            $tab_content .= '<li class="arm_com_msg_tab_li arm_com_msg_compose" data-tab_key="compose">'
                    . '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'new_message.png"/> <a href="javascript:void(0);" class="arm_com_msg_tab_a compose" id="arm_com_send_msg">' . __('New Message', ARM_COMMUNITY_TEXTDOMAIN) . '</a>' . $this->arm_com_send_msg_popup_display() . '</li>';

            global $arm_com_total_msgs_count;

            $is_display_reply = $arm_com_total_msgs_count == 0 ? "arm_display_none" : "";

            $tab_content .= '<li class="arm_com_msg_tab_li arm_com_msg_reply '.$is_display_reply.'" data-tab_key="reply">'
                    . '<a href="javascript:void(0);" class="arm_com_msg_tab_a reply" id="arm_com_send_reply">' . __('Reply', ARM_COMMUNITY_TEXTDOMAIN) . '</a></li>';


            $tab_content .= '</ul>';

            return $tab_content;
        }

        function arm_com_send_msg_popup_display() {
            $popup = '';
            $popup .= '<div class="arm_com_msg_popup popup_wrapper arm_popup_wrapper arm_popup_community_form" style="width: 650px; margin-top: 40px;">';
            $popup .= '<div class="popup_wrapper_inner">';
            $popup .= '<div class="popup_header">';
            $popup .= '<span class="popup_close_btn arm_popup_close_btn"></span>';
            $popup .= '<div class="popup_header_text arm_form_heading_container">';
            $popup .= '<span class="arm_form_field_label_wrapper_text">' . __('New Message', ARM_COMMUNITY_TEXTDOMAIN) . '</span>';
            $popup .= '</div></div>';
            $popup .= '<div class="popup_content_text">';
            $popup .= $this->arm_com_msg_form();
            $popup .= '</div>';
            $popup .= '</div>';
            $popup .= '</div>';
            return $popup;
        }

        function arm_com_msg_form() {
            global $arm_community_setting;
            $user_id = 0;
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
            }

            $compose_content = '';
            $compose_content .= '<div class="arm_com_msg_content_wrapper">';

            if (!$arm_community_setting->arm_com_is_profile_editor()) {
                $compose_content .= '<form id="arm_com_msg_compose_form_popup" class="arm_com_msg_compose_form">';
            }

            $compose_content .= '<div class="arm_com_msg_compose_row">';
            $compose_content .= '<div class="arm_com_msg_compose_column_label">';

            $username_lbl = $arm_community_setting->arm_community_get_setting_val('arm_msg_username_lbl');
            $compose_content .= '<label>' .$username_lbl . '</label>';
            $compose_content .= '</div>';
            
            $compose_content .= '<div class="arm_com_msg_compose_column">';

            $compose_content .= '<input id="arm_com_message_receiver_popup" name="arm_com_message_receiver" type="text" value="" placeholder="' . __('Search by username', MEMBERSHIP_TXTDOMAIN) . '">';
            $compose_content .= '<input type="hidden" id="arm_com_message_receiver_id_popup" name="arm_com_message_receiver_id" value="" />
                                <div class="arm_com_msg_user_list_container" id="arm_com_msg_user_list_container"></div>';

            $blank_field_msg = str_replace('[label]', $username_lbl , $arm_community_setting->arm_community_get_setting_val('arm_blank_field_msg'));
            $compose_content .= '<span class="arm_com_message_receiver_error arm_com_field_error" id="arm_com_message_receiver_error_popup">'.$blank_field_msg.'</span>';
            $compose_content .= '</div>';
            $compose_content .= '</div>';

            $compose_content .= '<div class="arm_com_msg_compose_row">';
            $compose_content .= '<div class="arm_com_msg_compose_column_label">';
             $msg_lbl = $arm_community_setting->arm_community_get_setting_val('arm_msg_msg_lbl');
            $compose_content .= '<label>' . $msg_lbl . '</label>';
            $compose_content .= '</div>';
            $compose_content .= '<div class="arm_com_msg_compose_column">';
            $compose_content .= '<textarea id="arm_com_message_msg_popup" name="arm_com_message_msg" ></textarea>';
            $blank_field_msg_msg = str_replace('[label]', $msg_lbl , $arm_community_setting->arm_community_get_setting_val('arm_blank_field_msg'));

            $compose_content .= '<span class="arm_com_message_message_error arm_com_field_error" id="arm_com_message_message_error_popup">'.$blank_field_msg_msg.'</span>';
            $compose_content .= '</div>';
            $compose_content .= '</div>';

            $compose_content .= '<div class="arm_com_msg_compose_row">';
            $compose_content .= '<div class="arm_com_msg_compose_column_label">';
            $compose_content .= '<label></label>';
            $compose_content .= '</div>';
            $compose_content .= '<div class="arm_com_msg_compose_column">';
            $compose_content .= '<div class="arm_com_msg_compose_smiley" style="position:relative;">';

            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'emoji_icon.png" style="position:absolute; cursor:pointer;  right:0; top:0;" id="arm_open_emoji_nav_popup" />';
            $compose_content .= '<div class="arm_emoji_wrapper" id="arm_emoji_wrapper_popup">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'smile.svg" alt="" draggable="false" data-entity=":)">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'sad.svg" alt="" draggable="false" data-entity=":(">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'grin.svg" alt="" draggable="false" data-entity=":D">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'eek.svg" alt="" draggable="false" data-entity=":o">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'shock.svg" alt="" draggable="false" data-entity="8O">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'puzzled.svg" alt="" draggable="false" data-entity=":?">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'mad.svg" alt="" draggable="false" data-entity=":x">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'razz.svg" alt="" draggable="false" data-entity=":P">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'neutral.svg" alt="" draggable="false" data-entity=":|">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'wink.svg" alt="" draggable="false" data-entity=";)">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'laughs.svg" alt="" draggable="false" data-entity=":lol:">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'oops.svg" alt="" draggable="false" data-entity=":oops:">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'cry.svg" alt="" draggable="false" data-entity=":cry:">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'evil.svg" alt="" draggable="false" data-entity=":evil:">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'twisted.svg" alt="" draggable="false" data-entity=":twisted:">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'roll.svg" alt="" draggable="false" data-entity=":roll:">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'exclamation.svg" alt="" draggable="false" data-entity=":!:">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'question.svg" alt="" draggable="false" data-entity=":?:">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'idea.svg" alt="" draggable="false" data-entity=":idea:">';
            $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'arrow.svg" alt="" draggable="false" data-entity=":arrow:">';
            $compose_content .= '</div></div>';

            $compose_content .= '<div class="arm_com_msg_compose_submit">';

            $compose_content .= '<button id="arm_com_message_send_btn_popup" class="arm_com_message_send_btn" name="arm_com_message_send_btn" value="" >';

            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $arm_msg_section_lbl = !empty($arm_com_settings["arm_msg_section_lbl"]) ? (__("Send", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_com_settings["arm_msg_section_lbl"]) : __("Send Message", ARM_COMMUNITY_TEXTDOMAIN);

            $compose_content .= $arm_msg_section_lbl;
            $compose_content .= '</button>';
            $compose_content .= '<span class="arm_spinner arm_com_message_send_btn_popup_spinner"><svg version="1.1" id="arm_form_loader" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="18px" height="18px" viewBox="0 0 26.349 26.35" style="enable-background:new 0 0 26.349 26.35;" xml:space="preserve" ><g><g><circle cx="13.792" cy="3.082" r="3.082" /><circle cx="13.792" cy="24.501" r="1.849"/><circle cx="6.219" cy="6.218" r="2.774"/><circle cx="21.365" cy="21.363" r="1.541"/><circle cx="3.082" cy="13.792" r="2.465"/><circle cx="24.501" cy="13.791" r="1.232"/><path d="M4.694,19.84c-0.843,0.843-0.843,2.207,0,3.05c0.842,0.843,2.208,0.843,3.05,0c0.843-0.843,0.843-2.207,0-3.05 C6.902,18.996,5.537,18.988,4.694,19.84z"/><circle cx="21.364" cy="6.218" r="0.924"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg></span>';
            $compose_content .= '</div>';
            $compose_content .= '<span class="arm_com_msg_compose_error" id="arm_com_msg_compose_error_popup"></span>';
            $compose_content .= '<span class="arm_com_msg_compose_success" id="arm_com_msg_compose_success_popup"></span></div></div>';

            if (!$arm_community_setting->arm_com_is_profile_editor()) {
                $compose_content .= '</form>';
            }
            
            $compose_content .= '<div class="arm_com_msg_popup_user_list" id="arm_com_msg_popup_user_list"></div>';
            $compose_content .= '</div>';

            return $compose_content;
        }

        function arm_com_get_message_tab_content($arm_com_active_tab_name = 'inbox') {
            $arm_com_tab_name = $this->arm_com_get_message_tab_array();
            $tab_content = '';

            $active_class = ' arm_com_msg_tab_content_active';
            $tab_content .= '<div class="arm_com_msg_content_main_wrapper ' . $active_class . '">';

            $tab_content .= $this->arm_com_message_get_inbox();

            $tab_content .= '</div>';

            $tab_content .= '<div id="arm_com_msg_display_loader" class="arm_com_msg_display_loader" style="display:none;">';
            $tab_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . '/arm_loader.gif" /></div>';
            $tab_content .= '<div class="arm_com_message_popup popup_wrapper arm_popup_wrapper arm_popup_community_form" style="width: 650px; margin-top: 40px;">';
            $tab_content .= '</div>';
            return $tab_content;
        }

        function arm_com_message_get_inbox($list_type = 'inbox', $per_page = 10, $current_page = 0) {

            global $arm_global_settings, $arm_community_setting, $wpdb, $arm_community_features;
            $arm_com_sender_record = $this->arm_com_sender_records();
            $user_id = get_current_user_id();
            $list_content = '';
            if (!empty($arm_com_sender_record)) {
                $archived_chat = get_user_meta($user_id, 'arm_com_archive_chat', true);
                $archived_chat = ($archived_chat != '') ? $archived_chat : array();
                foreach ($arm_com_sender_record as $key => $value) {
                    $arm_sender_id = $value['arm_sender_id'];
                    if ($user_id == $arm_sender_id) {
                        $arm_sender_id = $value['arm_receiver_id'];
                    }
                    if (in_array($arm_sender_id, $archived_chat)) {
                        unset($arm_com_sender_record[$key]);
                    }
                }
            }

            if (!empty($arm_com_sender_record)) {

                global $arm_com_total_msgs_count; $arm_com_total_msgs_count = 1;

                
                $sender_id = 0;
                $sender_username = $list_content_tmp = $first_user_name = $first_user_avatar = '';
                $sender_id_array = array();
                $i = 0;
                foreach ($arm_com_sender_record as $key => $value) {
                    $arm_sender_id = $value['arm_sender_id'];
                    if ($user_id == $arm_sender_id) {
                        $arm_sender_id = $value['arm_receiver_id'];
                    }
                    if (in_array($arm_sender_id, $archived_chat)) {
                        continue;
                    }
                    if (in_array($arm_sender_id, $sender_id_array)) {
                        continue;
                    }
                   
                    $sender_id_array[] = $arm_sender_id;
                    $message_result = "SELECT count(`arm_msg_id`) FROM {$arm_community_features->tbl_arm_com_message} WHERE 1=1 AND `arm_receiver_read`= 0 AND `arm_sender_id`={$arm_sender_id} AND `arm_receiver_id`={$user_id} AND `arm_is_message_blocked`=0";

                    $arm_total_msgs = $wpdb->get_var($message_result);

                    $sender_data = get_user_by('ID', $arm_sender_id);

                    if(!empty($sender_data)){
                        $i++;
                        $sender_name = $sender_data->display_name;
                        $active_class = '';
                        if ($i == 1) {
                            $sender_username = $sender_data->user_login;
                            $active_class = ' active';
                            $sender_id = $arm_sender_id;
                        }
                        $list_content_tmp .= '<div class="arm_com_msg_content_sender' . $active_class . '" id="arm_com_msg_content_sender_' . $arm_sender_id . '" data-sender_id="' . $arm_sender_id . '" data-sender_username="' . $sender_data->user_login . '">';
                        $list_content_tmp .= '<div class="arm_com_msg_content_sender_img" data-sender_id="' . $arm_sender_id . '" data-sender_username="' . $sender_data->user_login . '">';
                        $list_content_tmp .= get_avatar($arm_sender_id, '50');
                        $list_content_tmp .= '<div class="arm_total_sender_msgs_div" id="arm_total_sender_msgs_div_' . $arm_sender_id . '">';

                        if ($arm_total_msgs != 0) {
                            $list_content_tmp .= '<div class="arm_total_sender_msgs" id="arm_total_sender_msgs_' . $arm_sender_id . '">' . $arm_total_msgs . '</div>';
                        }

                        $list_content_tmp .= '</div></div>';
                        $list_content_tmp .= '<div class="arm_com_msg_content_sender_title">';
                        $list_content_tmp .= '<div class="arm_com_msg_content_sender_name">';
                        $list_content_tmp .= '<div class="arm_sender_name">' . $sender_name . '</div>';
                        $list_content_tmp .= '<div class="arm_sender_time">'.human_time_diff( strtotime($value["arm_datetime"] )).' '.__("ago", ARM_COMMUNITY_TEXTDOMAIN).'</div>';
                        $list_content_tmp .= '</div>';

                        $list_content_tmp .= '</div>';
                        $list_content_tmp .= '<div class="arm_com_msg_content_sender_icons" id="arm_com_msg_content_sender_icons_' . $arm_sender_id . '">';
                        $list_content_tmp .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'archive_blue.png" class="arm_com_msg_archive_icon" id="arm_com_msg_archive_' . $arm_sender_id . '" title="'.__("Hide Convorsation",ARM_COMMUNITY_TEXTDOMAIN).'" onclick="arm_com_msg_action(' . $arm_sender_id . ',\'archive\')"/>';
                        $list_content_tmp .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'delete_blue.png" class="arm_com_msg_delete_icon" id="arm_com_msg_delete_' . $arm_sender_id . '" title="'.__("Delete Convorsation",ARM_COMMUNITY_TEXTDOMAIN).'" onclick="arm_com_msg_action(' . $arm_sender_id . ',\'delete\')" />';
                        $list_content_tmp .= '</div>';
                        $list_content_tmp .= '</div>';

                        if($i == 1) {
                            $first_user_name = $sender_name;
                            $first_user_avatar = get_avatar($arm_sender_id, '50');
                        }
                    }
                }

                $arm_com_msg_button_text = "test";
                $list_content .= '<div class="arm_com_msg_div">';
                $list_content .= '<button type="button" class="arm_com_msg_button">';
                $list_content .= $first_user_avatar;
                $list_content .= '<span>'.$first_user_name.'</span>';
                $list_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'dropdown_arrow_1_icon.png" class="arm_com_msg_arrow arm_com_msg_arrow_down arm_com_msg_arrow_current" />';
                $list_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'dropdown_arrow_1_icon_hover.png" class="arm_com_msg_arrow arm_com_msg_arrow_up" />';
                $list_content .= '</button>';
                $list_content .= '</div>';
                $list_content .= '<div class="arm_com_msg_content_left">'.$list_content_tmp.'</div>';

                $compose_content = '';
                $compose_content .= '<div class="arm_com_msg_content_wrapper">';

                if (!$arm_community_setting->arm_com_is_profile_editor()) {
                    $compose_content .= '<form id="arm_com_msg_compose_form" class="arm_com_msg_compose_form">';
                }

                $compose_content .= '<input type="hidden" id="arm_com_message_receiver" name="arm_com_message_receiver" value="' . $sender_username . '" />';
                $compose_content .= '<input type="hidden" id="arm_com_message_receiver_id" name="arm_com_message_receiver_id" value="' . $sender_id . '" />';


                $compose_content .= '<div class="arm_com_msg_compose_row">';
                $compose_content .= '</div>';

                $compose_content .= '<div class="arm_com_msg_compose_row">';
                $compose_content .= '<div class="arm_com_msg_compose_row_img">';
                $compose_content .= get_avatar($user_id, 50);
                $compose_content .= '</div>';
                $compose_content .= '<div class="arm_com_msg_compose_column">';
                $compose_content .= '<textarea id="arm_com_message_msg" name="arm_com_message_msg" ></textarea>';
                $compose_content .= '<span class="arm_com_message_message_error arm_com_field_error" id="arm_com_message_message_error">Please enter message.</span>';
                $compose_content .= '</div>';
                $compose_content .= '</div>';

                $compose_content .= '<div class="arm_com_msg_compose_row">';
                $compose_content .= '<div class="arm_com_msg_compose_smiley">';

                $compose_content .= '<div class="arm_com_msg_compose_column" style="position:relative;">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'emoji_icon.png" style="position:absolute; cursor:pointer;  right:0; top:0;" id="arm_open_emoji_nav" />';
                $compose_content .= '<div class="arm_emoji_wrapper" id="arm_emoji_wrapper">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'smile.svg" alt="" draggable="false" data-entity=":)">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'sad.svg" alt="" draggable="false" data-entity=":(">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'grin.svg" alt="" draggable="false" data-entity=":D">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'eek.svg" alt="" draggable="false" data-entity=":o">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'shock.svg" alt="" draggable="false" data-entity="8O">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'puzzled.svg" alt="" draggable="false" data-entity=":?">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'mad.svg" alt="" draggable="false" data-entity=":x">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'razz.svg" alt="" draggable="false" data-entity=":P">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'neutral.svg" alt="" draggable="false" data-entity=":|">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'wink.svg" alt="" draggable="false" data-entity=";)">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'laughs.svg" alt="" draggable="false" data-entity=":lol:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'oops.svg" alt="" draggable="false" data-entity=":oops:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'cry.svg" alt="" draggable="false" data-entity=":cry:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'evil.svg" alt="" draggable="false" data-entity=":evil:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'twisted.svg" alt="" draggable="false" data-entity=":twisted:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'roll.svg" alt="" draggable="false" data-entity=":roll:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'exclamation.svg" alt="" draggable="false" data-entity=":!:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'question.svg" alt="" draggable="false" data-entity=":?:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'idea.svg" alt="" draggable="false" data-entity=":idea:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'arrow.svg" alt="" draggable="false" data-entity=":arrow:">';
                $compose_content .= '</div>';
                $compose_content .= '</div>';
                $compose_content .= '</div>';

                $compose_content .= '<div class="arm_com_msg_compose_submit">';

                $compose_content .= '<div class="arm_com_msg_compose_column">';
                $compose_content .= '<button id="arm_com_message_send_btn" class="arm_com_message_send_btn" name="arm_com_message_send_btn" value="" data-tab="inbox">';
                $arm_com_settings = $arm_community_setting->arm_com_settings;
                $arm_msg_section_lbl = !empty($arm_com_settings["arm_msg_section_lbl"]) ? (__("Send", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_com_settings["arm_msg_section_lbl"]) : __("Send Message", ARM_COMMUNITY_TEXTDOMAIN);

                $compose_content .= $arm_msg_section_lbl;
                $compose_content .= '</button>';
                $compose_content .= '<span class="arm_spinner arm_com_message_send_btn_spinner"><svg version="1.1" id="arm_form_loader" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="18px" height="18px" viewBox="0 0 26.349 26.35" style="enable-background:new 0 0 26.349 26.35;" xml:space="preserve" ><g><g><circle cx="13.792" cy="3.082" r="3.082" /><circle cx="13.792" cy="24.501" r="1.849"/><circle cx="6.219" cy="6.218" r="2.774"/><circle cx="21.365" cy="21.363" r="1.541"/><circle cx="3.082" cy="13.792" r="2.465"/><circle cx="24.501" cy="13.791" r="1.232"/><path d="M4.694,19.84c-0.843,0.843-0.843,2.207,0,3.05c0.842,0.843,2.208,0.843,3.05,0c0.843-0.843,0.843-2.207,0-3.05 C6.902,18.996,5.537,18.988,4.694,19.84z"/><circle cx="21.364" cy="6.218" r="0.924"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg></span>';
                $compose_content .= '</div>';
                $compose_content .= '</div>';
                

                
                $compose_content .= '<span class="arm_com_msg_compose_error" id="arm_com_msg_compose_error"></span>';
                $compose_content .= '<span class="arm_com_msg_compose_success" id="arm_com_msg_compose_success"></span>';
                $compose_content .= '</div>';
                if (!$arm_community_setting->arm_com_is_profile_editor()) {
                    $compose_content .= '</form>';
                }
                $compose_content .= '</div>';
                $list_content .= '<div class="arm_com_msg_content_right">';
                $list_content .= '<div id="arm_com_msg_display_loader_img" class="arm_com_msg_display_loader" style="display:none;">';
                $list_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . '/arm_loader.gif" /></div>';
                
                if($i>0){
                    $list_content .= '<div class="arm_com_msg_content_right_content">' . $this->arm_com_get_message_thread($sender_id) . '</div>';
                    $list_content .= $compose_content;
                }
                
                
                $list_content .= '</div>';
                if($i == 0)
                {
                    $list_content .= '<div class="arm_com_msg_content_wrapper"><div id="arm_com_msg_list_wrapper" class="arm_com_msg_list_wrapper">';
                    $list_content .= '<div class="arm_com_no_message">'.__("You have no any",ARM_COMMUNITY_TEXTDOMAIN).' ' . $list_type . ' '.__("message.",ARM_COMMUNITY_TEXTDOMAIN).'</div></div></div>';
                }
            } else {
                $list_content .= '<div class="arm_com_msg_content_wrapper"><div id="arm_com_msg_list_wrapper" class="arm_com_msg_list_wrapper">';
                $list_content .= '<div class="arm_com_no_message">'.__("You have no any",ARM_COMMUNITY_TEXTDOMAIN).' ' . $list_type . ' '.__("message.",ARM_COMMUNITY_TEXTDOMAIN).'</div></div></div>';
            }



            return $list_content;
        }



       

        function arm_com_message_get_archive($list_type = 'archive', $per_page = 10, $current_page = 0) {

            global $arm_global_settings, $arm_community_setting, $wpdb, $arm_community_features;
            $arm_com_sender_record = $this->arm_com_sender_records('archive');



            $user_id = get_current_user_id();
            $list_content = '';
            if (!empty($arm_com_sender_record)) {
                $list_content .= '<div class="arm_com_msg_content_left">';
                $sender_id = 0;
                $sender_username = '';
                $i=0;
                $sender_id_array = array();
                foreach ($arm_com_sender_record as $key => $value) {
                    $arm_sender_id = $value['arm_sender_id'];

                    if ($user_id == $arm_sender_id) {
                        $arm_sender_id = $value['arm_receiver_id'];
                    }
                    if (in_array($arm_sender_id, $sender_id_array)) {
                        continue;
                    }
                    $sender_id_array[] = $arm_sender_id;



                    $message_result = "SELECT count(`arm_msg_id`) FROM {$arm_community_features->tbl_arm_com_message} WHERE 1=1 AND `arm_receiver_read`= 0 AND `arm_sender_id`={$arm_sender_id} AND `arm_receiver_id`={$user_id} AND `arm_is_message_blocked`=0";

                    $arm_total_msgs = $wpdb->get_var($message_result);

                    $sender_data = get_user_by('ID', $arm_sender_id);
                    if(!empty($sender_data)){
                        $i++;

                    $sender_name = $sender_data->display_name;

                    $active_class = '';



                    if ($i == 1) {
                        $sender_username = $sender_data->user_login;
                        $active_class = ' active';
                        $sender_id = $arm_sender_id;
                    }



                    $list_content .= '<div class="arm_com_msg_content_sender' . $active_class . '" id="arm_com_msg_content_sender_' . $arm_sender_id . '" data-sender_id="' . $arm_sender_id . '" data-sender_username="' . $sender_data->user_login . '">';
                    $list_content .= '<div class="arm_com_msg_content_sender_img" data-sender_id="' . $arm_sender_id . '" data-sender_username="' . $sender_data->user_login . '">';
                    $list_content .= get_avatar($arm_sender_id, '50');
                    $list_content .= '<div class="arm_total_sender_msgs_div" id="arm_total_sender_msgs_div_' . $arm_sender_id . '">';

                    if ($arm_total_msgs != 0) {
                        $list_content .= '<div class="arm_total_sender_msgs" id="arm_total_sender_msgs_' . $arm_sender_id . '">' . $arm_total_msgs . '</div>';
                    }

                    $list_content .= '</div></div>';
                    $list_content .= '<div class="arm_com_msg_content_sender_title">';
                    $list_content .= '<div class="arm_com_msg_content_sender_name">';
                    $list_content .= '<div class="arm_sender_name">' . $sender_name . '</div>';
                    $list_content .= '<div class="arm_sender_time">'.human_time_diff( strtotime($value["arm_datetime"] )).' '.__("ago", ARM_COMMUNITY_TEXTDOMAIN).'</div>';
                    $list_content .= '</div>';

                    $list_content .= '</div>';
                    $list_content .= '<div class="arm_com_msg_content_sender_icons" id="arm_com_msg_content_sender_icons_' . $arm_sender_id . '">';
                    $list_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'archive_blue.png" class="arm_com_msg_inbox_icon" id="arm_com_msg_archive_' . $arm_sender_id . '" title="'.__("Move To Inbox",ARM_COMMUNITY_TEXTDOMAIN).'"  onclick="arm_com_msg_action(' . $arm_sender_id . ',\'inbox\')"/>';
                    $list_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'delete_blue.png"class="arm_com_msg_delete_icon" id="arm_com_msg_delete_' . $arm_sender_id . '" title="'.__("Delete Convorsation",ARM_COMMUNITY_TEXTDOMAIN).'"  onclick="arm_com_msg_action(' . $arm_sender_id . ',\'delete\')" />';
                    $list_content .= '</div>';
                    $list_content .= '</div>';
                }
            }

                $list_content .= '</div>';





                $compose_content = '';
                $compose_content .= '<div class="arm_com_msg_content_wrapper">';

                if (!$arm_community_setting->arm_com_is_profile_editor()) {
                    $compose_content .= '<form id="arm_com_msg_compose_form" class="arm_com_msg_compose_form">';
                }


                $compose_content .= '<input type="hidden" id="arm_com_message_receiver" name="arm_com_message_receiver" value="' . $sender_username . '" />';
                $compose_content .= '<input type="hidden" id="arm_com_message_receiver_id" name="arm_com_message_receiver_id" value="' . $sender_id . '" />';




                $compose_content .= '<div class="arm_com_msg_compose_row">';
                $compose_content .= '<div class="arm_com_msg_compose_row_img">';
                $compose_content .= get_avatar($user_id, 50);
                $compose_content .= '</div>';
                $compose_content .= '<div class="arm_com_msg_compose_column">';
                $compose_content .= '<textarea id="arm_com_message_msg" name="arm_com_message_msg" ></textarea>';
                $compose_content .= '<span class="arm_com_message_message_error arm_com_field_error" id="arm_com_message_message_error">Please enter message.</span>';
                $compose_content .= '</div>';
                $compose_content .= '</div>';

                $compose_content .= '<div class="arm_com_msg_compose_row">';
                $compose_content .= '<div class="arm_com_msg_compose_smiley">';

                $compose_content .= '<div class="arm_com_msg_compose_column" style="position:relative;">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'emoji_icon.png" style="position:absolute; cursor:pointer;  right:0; top:0;" id="arm_open_emoji_nav" />';
                $compose_content .= '<div class="arm_emoji_wrapper" id="arm_emoji_wrapper">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'smile.svg" alt="" draggable="false" data-entity=":)">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'sad.svg" alt="" draggable="false" data-entity=":(">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'grin.svg" alt="" draggable="false" data-entity=":D">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'eek.svg" alt="" draggable="false" data-entity=":o">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'shock.svg" alt="" draggable="false" data-entity="8O">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'puzzled.svg" alt="" draggable="false" data-entity=":?">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'mad.svg" alt="" draggable="false" data-entity=":x">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'razz.svg" alt="" draggable="false" data-entity=":P">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'neutral.svg" alt="" draggable="false" data-entity=":|">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'wink.svg" alt="" draggable="false" data-entity=";)">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'laughs.svg" alt="" draggable="false" data-entity=":lol:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'oops.svg" alt="" draggable="false" data-entity=":oops:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'cry.svg" alt="" draggable="false" data-entity=":cry:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'evil.svg" alt="" draggable="false" data-entity=":evil:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'twisted.svg" alt="" draggable="false" data-entity=":twisted:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'roll.svg" alt="" draggable="false" data-entity=":roll:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'exclamation.svg" alt="" draggable="false" data-entity=":!:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'question.svg" alt="" draggable="false" data-entity=":?:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'idea.svg" alt="" draggable="false" data-entity=":idea:">';
                $compose_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'arrow.svg" alt="" draggable="false" data-entity=":arrow:">';
                $compose_content .= '</div>';
                $compose_content .= '</div>';
                $compose_content .= '</div>';

                $compose_content .= '<div class="arm_com_msg_compose_submit">';

                $compose_content .= '<div class="arm_com_msg_compose_column">';
                $compose_content .= '<button id="arm_com_message_send_btn" class="arm_com_message_send_btn" name="arm_com_message_send_btn" value="" data-tab="archive" >';
                $compose_content .= '<span class="arm_spinner"><svg version="1.1" id="arm_form_loader" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="18px" height="18px" viewBox="0 0 26.349 26.35" style="enable-background:new 0 0 26.349 26.35;" xml:space="preserve" ><g><g><circle cx="13.792" cy="3.082" r="3.082" /><circle cx="13.792" cy="24.501" r="1.849"/><circle cx="6.219" cy="6.218" r="2.774"/><circle cx="21.365" cy="21.363" r="1.541"/><circle cx="3.082" cy="13.792" r="2.465"/><circle cx="24.501" cy="13.791" r="1.232"/><path d="M4.694,19.84c-0.843,0.843-0.843,2.207,0,3.05c0.842,0.843,2.208,0.843,3.05,0c0.843-0.843,0.843-2.207,0-3.05 C6.902,18.996,5.537,18.988,4.694,19.84z"/><circle cx="21.364" cy="6.218" r="0.924"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg></span>';
                $compose_content .= __('Send Message', ARM_COMMUNITY_TEXTDOMAIN);
                $compose_content .= '</button>';
                $compose_content .= '</div>';
                $compose_content .= '</div>';
                     $compose_content .= '<span class="arm_com_msg_compose_error" id="arm_com_msg_compose_error"></span>';
                $compose_content .= '<span class="arm_com_msg_compose_success" id="arm_com_msg_compose_success"></span>';
                $compose_content .= '</div>';
                if (!$arm_community_setting->arm_com_is_profile_editor()) {
                    $compose_content .= '</form>';
                }
                $compose_content .= '</div>';



               


                $list_content .= '<div class="arm_com_msg_content_right">';
                $list_content .= '<div id="arm_com_msg_display_loader_img" class="arm_com_msg_display_loader" style="display:none;">';
                $list_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . '/arm_loader.gif" /></div>';
                
               

                 if($i>0){
                    $list_content .= '<div class="arm_com_msg_content_right_content">' . $this->arm_com_get_message_thread($sender_id) . '</div>';
                    $list_content .= $compose_content;
                }
                
                
                $list_content .= '</div>';
                if($i == 0)
                {
                    $list_content .= '<div class="arm_com_msg_content_wrapper"><div id="arm_com_msg_list_wrapper" class="arm_com_msg_list_wrapper">';
                    $list_content .= '<div class="arm_com_no_message">'.__("You have no any",ARM_COMMUNITY_TEXTDOMAIN).' ' . $list_type . ' '.__("message.",ARM_COMMUNITY_TEXTDOMAIN).'</div></div></div>';
                }



            } else {
                $list_content .= '<div class="arm_com_msg_content_wrapper"><div id="arm_com_msg_list_wrapper" class="arm_com_msg_list_wrapper">';
                $list_content .= '<div class="arm_com_no_message">'.__("You have no any",ARM_COMMUNITY_TEXTDOMAIN).' ' . $list_type . ' '.__("message.",ARM_COMMUNITY_TEXTDOMAIN).'</div></div></div>';
            }



            return $list_content;
        }

        function arm_com_message_records($list_type = '') {


            global $arm_community_features, $wpdb, $arm_global_settings, $arm_community_setting, $ARMember;
            $date_format = $arm_global_settings->arm_get_wp_date_time_format();
            $where_query = '';
            $list_content = '';
            $user_id = get_current_user_id();
            $blocked_user_ids = get_user_meta($user_id, 'arm_com_msg_blocked');

            if ($list_type != '') {
                switch ($list_type) {
                    case 'starred' :
                        $where_query = ' ( arm_sender_starred = 1 AND arm_sender_id = ' . $user_id . ' ) OR ( arm_receiver_starred = 1 AND arm_receiver_id = ' . $user_id . ') ';
                        break;
                    case 'sent' :
                        $where_query = ' arm_sender_id = ' . $user_id ;
                        break;
                    default :
                        $where_query = ' arm_receiver_id = ' . $user_id ;
                        $where_query .= ' and arm_is_message_blocked = 0';
                }
            }

            if ($arm_community_setting->arm_com_is_profile_editor()) {
                $user_data = get_userdata($user_id);
                $username = $user_data->user_login;
                $user_pro_pic = get_avatar($user_id, '80');

                $msg_count = 1;
                $message_records[$msg_count]['msg_id'] = 1;
                $message_records[$msg_count]['arm_receiver_id'] = $user_id;
                $message_records[$msg_count]['arm_receiver_username'] = $username;
                $message_records[$msg_count]['arm_receiver_pro_pic'] = $user_pro_pic;
                $message_records[$msg_count]['arm_sender_id'] = $user_id;
                $message_records[$msg_count]['arm_sender_username'] = $username;
                $message_records[$msg_count]['arm_sender_pro_pic'] = $user_pro_pic;
                $message_records[$msg_count]['arm_subject'] = __('This is my first message', ARM_COMMUNITY_TEXTDOMAIN);
                $message_records[$msg_count]['arm_message'] = __('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.', ARM_COMMUNITY_TEXTDOMAIN);
                $message_records[$msg_count]['arm_sender_read'] = 1;
                $message_records[$msg_count]['arm_receiver_read'] = 1;
                $message_records[$msg_count]['arm_sender_delete'] = 0;
                $message_records[$msg_count]['arm_receiver_delete'] = 0;
                $message_records[$msg_count]['arm_sender_starred'] = 0;
                $message_records[$msg_count]['arm_receiver_starred'] = 0;
                $message_records[$msg_count]['datetime'] = date($date_format, current_time('timestamp'));

                $msg_count = 2;
                $message_records[$msg_count]['msg_id'] = 1;
                $message_records[$msg_count]['arm_receiver_id'] = $user_id;
                $message_records[$msg_count]['arm_receiver_username'] = $username;
                $message_records[$msg_count]['arm_receiver_pro_pic'] = $user_pro_pic;
                $message_records[$msg_count]['arm_sender_id'] = $user_id;
                $message_records[$msg_count]['arm_sender_username'] = $username;
                $message_records[$msg_count]['arm_sender_pro_pic'] = $user_pro_pic;
                $message_records[$msg_count]['arm_subject'] = __('This is my first message', ARM_COMMUNITY_TEXTDOMAIN);
                $message_records[$msg_count]['arm_message'] = __('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.', ARM_COMMUNITY_TEXTDOMAIN);
                $message_records[$msg_count]['arm_sender_read'] = 0;
                $message_records[$msg_count]['arm_receiver_read'] = 0;
                $message_records[$msg_count]['arm_sender_delete'] = 0;
                $message_records[$msg_count]['arm_receiver_delete'] = 0;
                $message_records[$msg_count]['arm_sender_starred'] = 0;
                $message_records[$msg_count]['arm_receiver_starred'] = 0;
                $message_records[$msg_count]['datetime'] = date($date_format, current_time('timestamp'));

                return $message_records;
            }

            $message_result = "SELECT * FROM {$arm_community_features->tbl_arm_com_message} WHERE 1=1 AND " . $where_query . " ORDER BY arm_datetime DESC";

            $message_result = $wpdb->get_results($message_result);
            $message_records = array();
            if (count($message_result) > 0) {
                $msg_count = 0;
                foreach ($message_result as $message) {
                    $receiver_data = get_userdata($message->arm_receiver_id);
                    $receiver_username = $receiver_data->user_login;
                    $sender_data = get_userdata($message->arm_sender_id);
                    $sender_username = $sender_data->user_login;
                    $receiver_pro_pic = get_avatar($message->arm_receiver_id, '80');
                    $sender_pro_pic = get_avatar($message->arm_sender_id, '80');

                    $message_records[$msg_count]['msg_id'] = $message->arm_msg_id;
                    $message_records[$msg_count]['arm_receiver_id'] = $message->arm_receiver_id;
                    $message_records[$msg_count]['arm_receiver_username'] = $receiver_username;
                    $message_records[$msg_count]['arm_receiver_pro_pic'] = $receiver_pro_pic;
                    $message_records[$msg_count]['arm_sender_id'] = $message->arm_sender_id;
                    $message_records[$msg_count]['arm_sender_username'] = $sender_username;
                    $message_records[$msg_count]['arm_sender_pro_pic'] = $sender_pro_pic;
                    $message_records[$msg_count]['arm_subject'] = $message->arm_subject;
                    $message_records[$msg_count]['arm_message'] = $message->arm_message;
                    $message_records[$msg_count]['arm_sender_read'] = $message->arm_sender_read;
                    $message_records[$msg_count]['arm_receiver_read'] = $message->arm_receiver_read;
                    $message_records[$msg_count]['arm_sender_delete'] = $message->arm_sender_delete;
                    $message_records[$msg_count]['arm_receiver_delete'] = $message->arm_receiver_delete;
                    $message_records[$msg_count]['arm_sender_starred'] = $message->arm_sender_starred;
                    $message_records[$msg_count]['arm_receiver_starred'] = $message->arm_receiver_starred;
                    $message_records[$msg_count]['datetime'] = date($date_format, strtotime($message->arm_datetime));
                    $msg_count++;
                }
            }
            return $message_records;
        }

        function arm_com_sender_records($type = 'inbox') {

            global $arm_community_features, $wpdb, $arm_global_settings, $arm_community_setting, $ARMember;
            $date_format = $arm_global_settings->arm_get_wp_date_time_format();
            $where_query = '';
            $list_content = '';
            $user_id = get_current_user_id();
            $blocked_user_ids = get_user_meta($user_id, 'arm_com_msg_blocked');

            if ($arm_community_setting->arm_com_is_profile_editor()) {
                $user_data = get_userdata($user_id);
                $username = $user_data->user_login;
                $user_pro_pic = get_avatar($user_id, '80');

                $msg_count = 1;
                $message_records[$msg_count]['arm_msg_id'] = 1;
                $message_records[$msg_count]['arm_receiver_id'] = $user_id;
                $message_records[$msg_count]['arm_receiver_username'] = $username;
                $message_records[$msg_count]['arm_receiver_pro_pic'] = $user_pro_pic;
                $message_records[$msg_count]['arm_sender_id'] = $user_id;
                $message_records[$msg_count]['arm_sender_username'] = $username;
                $message_records[$msg_count]['arm_sender_pro_pic'] = $user_pro_pic;
                $message_records[$msg_count]['arm_subject'] = __('This is my first message', ARM_COMMUNITY_TEXTDOMAIN);
                $message_records[$msg_count]['arm_message'] = __('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.', ARM_COMMUNITY_TEXTDOMAIN);
                $message_records[$msg_count]['arm_sender_read'] = 1;
                $message_records[$msg_count]['arm_receiver_read'] = 1;
                $message_records[$msg_count]['arm_sender_delete'] = 0;
                $message_records[$msg_count]['arm_receiver_delete'] = 0;
                $message_records[$msg_count]['arm_sender_starred'] = 0;
                $message_records[$msg_count]['arm_receiver_starred'] = 0;
                $message_records[$msg_count]['arm_datetime'] = date($date_format, current_time('timestamp'));

                $msg_count = 2;
                $message_records[$msg_count]['arm_msg_id'] = 1;
                $message_records[$msg_count]['arm_receiver_id'] = $user_id;
                $message_records[$msg_count]['arm_receiver_username'] = $username;
                $message_records[$msg_count]['arm_receiver_pro_pic'] = $user_pro_pic;
                $message_records[$msg_count]['arm_sender_id'] = $user_id;
                $message_records[$msg_count]['arm_sender_username'] = $username;
                $message_records[$msg_count]['arm_sender_pro_pic'] = $user_pro_pic;
                $message_records[$msg_count]['arm_subject'] = __('This is my first message', ARM_COMMUNITY_TEXTDOMAIN);
                $message_records[$msg_count]['arm_message'] = __('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.', ARM_COMMUNITY_TEXTDOMAIN);
                $message_records[$msg_count]['arm_sender_read'] = 0;
                $message_records[$msg_count]['arm_receiver_read'] = 0;
                $message_records[$msg_count]['arm_sender_delete'] = 0;
                $message_records[$msg_count]['arm_receiver_delete'] = 0;
                $message_records[$msg_count]['arm_sender_starred'] = 0;
                $message_records[$msg_count]['arm_receiver_starred'] = 0;
                $message_records[$msg_count]['arm_datetime'] = date($date_format, current_time('timestamp'));

                return $message_records;
            }
            $message_result = array();

            if ($type == 'archive') {

                $archived_chat = get_user_meta($user_id, 'arm_com_archive_chat', true);


                $archived_chat = ( $archived_chat != '') ? $archived_chat : array();
                if (!empty($archived_chat)) {
                    $archived_ids = implode(",", $archived_chat);

                    $where_query = 'AND (arm_sender_id IN (' . $archived_ids . ') AND arm_receiver_id=' . $user_id . ') OR (arm_receiver_id IN (' . $archived_ids . ') AND arm_sender_id=' . $user_id . ')';
                    $where_query .= ' and arm_is_message_blocked = 0 ';
                    $message_result = "SELECT `arm_sender_id`,`arm_receiver_id`,`arm_msg_id`,`arm_datetime` FROM {$arm_community_features->tbl_arm_com_message} WHERE 1=1 " . $where_query . " ORDER BY `arm_msg_id` DESC";

            $message_result = $wpdb->get_results($message_result, ARRAY_A);
                }
            } else {
                $where_query = 'AND ((arm_receiver_id = ' . $user_id . ' AND `arm_receiver_complete_delete` = 0) OR (arm_sender_id=' . $user_id . ' AND `arm_sender_complete_delete` = 0))';
                $where_query .= ' and arm_is_message_blocked = 0 ';
                $message_result = "SELECT `arm_sender_id`,`arm_receiver_id`,`arm_msg_id`,`arm_datetime` FROM {$arm_community_features->tbl_arm_com_message} WHERE 1=1 " . $where_query . " ORDER BY `arm_msg_id` DESC";

            $message_result = $wpdb->get_results($message_result, ARRAY_A);
            }

         


            return $message_result;
        }

        function arm_com_message_action_data($action = '', $message_type = '') {
            $action_data = array();
            if ($action != '' && $message_type != '') {
                switch ($action) {
                    case 'starred' :
                        $action_data['field'] = 'arm_' . $message_type . '_starred';
                        $action_data['val'] = 1;
                        break;
                    case 'unstarred' :
                        $action_data['field'] = 'arm_' . $message_type . '_starred';
                        $action_data['val'] = 0;
                        break;
                    case 'read' :
                        $action_data['field'] = 'arm_' . $message_type . '_read';
                        $action_data['val'] = 1;
                        break;
                    case 'unread' :
                        $action_data['field'] = 'arm_' . $message_type . '_read';
                        $action_data['val'] = 0;
                        break;
                    case 'delete' :
                        $action_data['field'] = 'arm_' . $message_type . '_delete';
                        $action_data['val'] = 1;
                        break;
                }
            }
            return $action_data;
        }

        function arm_com_message_single_action() {
            global $wpdb, $arm_community_features;
            $posted_data = $_POST;
            $response = array('type' => 'error', 'content' => 'Sorry, not able to action this perform.');
            $arm_com_message_tbl = $arm_community_features->tbl_arm_com_message;
            $arm_com_action = trim($posted_data['arm_com_actoin']);
            $arm_com_message_id = $posted_data['arm_com_message_id'];
            $arm_com_message_type = $posted_data['arm_com_message_type'];
            $arm_com_msg_action_data = $this->arm_com_message_action_data($arm_com_action, $arm_com_message_type);

            if (isset($arm_com_msg_action_data['field']) && $arm_com_msg_action_data['field'] != '') {
                $wpdb->update($arm_com_message_tbl, array($arm_com_msg_action_data['field'] => $arm_com_msg_action_data['val']), array('arm_msg_id' => $arm_com_message_id), array('%d'), array('%d'));
                $response = array('type' => 'success', 'content' => $this->arm_com_get_message_tab_content('inbox'));
            }
            echo json_encode($response);
            die;
        }

        function arm_com_get_message_thread($sender_id = 0) {
            global $wpdb, $arm_community_features, $arm_community_setting, $arm_global_settings, $ARMember;
            $posted_data = $_POST;

            $response = array('type' => 'error', 'content' => 'Sorry, not able to action this perform.');
            $content = '';

            if (is_user_logged_in() && $this->arm_com_message_allow() ) {
                $date_format = $arm_global_settings->arm_get_wp_date_time_format();
                $sener_id = isset($posted_data['sender_id']) ? $posted_data['sender_id'] : $sender_id;
                $receiver_id = get_current_user_id();

                $where_query = ' (arm_receiver_id = ' . $receiver_id . ' AND arm_sender_id = ' . $sener_id . ') OR ( arm_receiver_id = ' . $sener_id . ' AND arm_sender_id = ' . $receiver_id . ')';
                $where_query .= ' AND arm_is_message_blocked = 0';

                $message_result_qry = "SELECT * FROM {$arm_community_features->tbl_arm_com_message} WHERE 1=1 AND " . $where_query . " ORDER BY arm_msg_id DESC";

                $message_result = $wpdb->get_results($message_result_qry);

                $total_messages = count($message_result);

                $arm_com_settings = $arm_community_setting->arm_com_settings;
                $records_per_page = isset($arm_com_settings["arm_record_per_page"]) ? $arm_com_settings["arm_record_per_page"] : 10;

                $paging = "";

                if( $total_messages > $records_per_page ) {

                    $total_pages = ceil( $total_messages / $records_per_page );

                    $pageno = isset($_REQUEST['pageno']) ? $_REQUEST['pageno'] : 1;

                    $offset = ( $pageno - 1 ) * $records_per_page;

                    $message_result_qry .= " LIMIT {$offset}, {$records_per_page}";

                    $message_result = $wpdb->get_results($message_result_qry);

                    $paging = '<div class="arm_com_msgs_paging_container arm_com_msgs_paging_container_infinite"><a class="arm_com_msgs_load_more_link arm_page_numbers" href="javascript:void(0)" data-page="' . ($pageno + 1) . '" data-type="msgs" data-arm_ttl_page="'.$total_pages.'" data-arm_sener_id="'.$sener_id.'">' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '</a></div>';
                }

                if (!empty($message_result)) {

                    $content .= '<div class="arm_com_message_convo_div" id="arm_com_message_convo_div">';

                    if($paging != "") {
                        $content .= "<div class='arm_com_msgs_paging_div'>".$paging."</div>";
                    }

                    $content .= "<div class='arm_com_message_convo_div_wrapper'>";

                    //foreach ($message_result as $key => $value) {
                    $content_tmp = "";
                    for( $m = count($message_result)-1; $m >= 0; $m-- ) {

                        $class_name = 'left';

                        if ($receiver_id == $message_result[$m]->arm_sender_id) {
                            $class_name = 'right';
                        }

                        if($message_result[$m]->arm_sender_delete == 1){
                            if($receiver_id == $message_result[$m]->arm_sender_id){
                                continue;
                            }
                        }

                        if($message_result[$m]->arm_receiver_delete == 1){
                            if($receiver_id == $message_result[$m]->arm_receiver_id){
                                continue;
                            }
                        }

                        $content_tmp .= '<div class="arm_com_message_convo ' . $class_name . '" id="arm_com_message_convo_' . $message_result[$m]->arm_msg_id . '">';
                        $content_tmp .= '<div class="arm_com_message_convo_img">';

                        if( $arm_community_setting->arm_com_is_profile_editor() ) {
                            $profile_url = '#';
                        }
                        else {
                            $profile_url = $arm_global_settings->arm_get_user_profile_url($message_result[$m]->arm_sender_id);
                        }

                        $content_tmp .= "<a href='".$profile_url."'>";
                        $content_tmp .= get_avatar($message_result[$m]->arm_sender_id, '50');
                        $content_tmp .= "</a>";

                        $content_tmp .= '</div>';
                        $content_tmp .= '<div class="arm_com_message_convo_msg">';
                        $content_tmp .=  convert_smilies(nl2br(stripslashes($message_result[$m]->arm_message)));
                        $content_tmp .= '</div>';
                        $content_tmp .= '<div class="arm_com_message_convo_time">';
                        $content_tmp .= date($date_format, strtotime($message_result[$m]->arm_datetime));
                        $content_tmp .= '</div>';
                        $content_tmp .= '<div class="arm_com_message_convo_action">';
                        $content_tmp .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'delete_blue.png" onclick="arm_com_msg_convo_delete(' . $message_result[$m]->arm_msg_id . ', '.$message_result[$m]->arm_sender_id.','.$message_result[$m]->arm_receiver_id.')"/>';
                        $content_tmp .= '</div>';
                        $content_tmp .= '</div>';
                    }

                    if( isset($_REQUEST["action"]) && $_REQUEST["action"] == "arm_community_message_fetch_front" ) {
                        $response = array("content" => $content_tmp);
                        echo json_encode($response);
                        exit;
                    }

                    $content .= $content_tmp;

                    $content .= '</div></div>';
                }

                if ($arm_community_setting->arm_com_is_profile_editor()) {
                    $content .= '<div class="arm_com_message_convo_div" id="arm_com_message_convo_div">';
                    $content .= '<div class="arm_com_message_convo left">';
                        $content .= '<div class="arm_com_message_convo_img">';
                            $content .= get_avatar(1, '50');
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_msg">';
                            $content .= __('Sender sends message1', ARM_COMMUNITY_TEXTDOMAIN);
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_time">';
                       
                            $content .= date($date_format);
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_action">';
                          $content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'delete_blue.png" />';
                        $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="arm_com_message_convo right" >';
                        $content .= '<div class="arm_com_message_convo_img">';
                            $content .= get_avatar(1, '50');
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_msg">';
                            $content .= __('Receiver sends message1', ARM_COMMUNITY_TEXTDOMAIN);
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_time">';
                        
                            $content .= date($date_format);
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_action">';
                          $content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'delete_blue.png"/>';
                        $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="arm_com_message_convo left" >';
                        $content .= '<div class="arm_com_message_convo_img">';
                            $content .= get_avatar(1, '50');
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_msg">';
                            $content .= __('Sender sends message2', ARM_COMMUNITY_TEXTDOMAIN);
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_time">';
                       
                            $content .= date($date_format);
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_action">';
                          $content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'delete_blue.png" />';
                        $content .= '</div>';
                    $content .= '</div>';
                     $content .= '<div class="arm_com_message_convo right" >';
                        $content .= '<div class="arm_com_message_convo_img">';
                            $content .= get_avatar(1, '50');
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_msg">';
                            $content .= __('receiver sends message2', ARM_COMMUNITY_TEXTDOMAIN);
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_time">';
                       
                            $content .= date($date_format);
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_action">';
                          $content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'delete_blue.png" />';
                        $content .= '</div>';
                    $content .= '</div>';
                    $content .= '<div class="arm_com_message_convo left" >';
                        $content .= '<div class="arm_com_message_convo_img">';
                            $content .= get_avatar(1, '50');
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_msg">';
                            $content .= __('sender sends message3', ARM_COMMUNITY_TEXTDOMAIN);
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_time">';
                       
                            $content .= date($date_format);
                        $content .= '</div>';
                        $content .= '<div class="arm_com_message_convo_action">';
                          $content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'delete_blue.png" />';
                        $content .= '</div>';
                    $content .= '</div>';
                    $content .= '</div>';
                }


                if (isset($posted_data['action']) && $posted_data['action'] == 'arm_com_get_message_thread') {
                    $response = array('type' => 'success', 'content' => $content, 'paging' => $paging);
                    if (!empty($message_result)) {
                        $wpdb->update(
                                $arm_community_features->tbl_arm_com_message, array(
                            'arm_receiver_read' => 1, // string
                                ), array('arm_receiver_id' => $receiver_id, 'arm_sender_id' => $sener_id), array(
                            '%d'    // value2
                                ), array('%d', '%d')
                        );
                    }
                } else {
                    return $content;
                }
            }
            
            echo json_encode($response);
            die;
        }

        function arm_com_message_bulk_action() {
            global $wpdb, $arm_global_settings, $arm_community_features;
            if (!isset($_POST)) {
                return;
            }

            $bulkaction = $arm_global_settings->get_param('arm_com_msg_bulk');
            $message_type = $arm_global_settings->get_param('message_type');
            $ids = $arm_global_settings->get_param('item-action', '');
            if (empty($ids)) {
                $errors = __('Please select one or more records.', ARM_COMMUNITY_TEXTDOMAIN);
            } else {
                if ($bulkaction == '' || $bulkaction == '-1') {
                    $errors = __('Please select valid action.', ARM_COMMUNITY_TEXTDOMAIN);
                } else {
                    if (!is_array($ids)) {
                        $ids = explode(',', $ids);
                    }
                    $arm_com_msg_action_data = $this->arm_com_message_action_data($bulkaction, $message_type);
                    $arm_com_message_tbl = $arm_community_features->tbl_arm_com_message;
                    if (isset($arm_com_msg_action_data['field']) && $arm_com_msg_action_data['field'] != '') {
                        $aff_ids = implode(',', $ids);
                        $delete_referral = $wpdb->query("UPDATE `$arm_com_message_tbl` SET " . $arm_com_msg_action_data['field'] . "=" . $arm_com_msg_action_data['val'] . " WHERE arm_msg_id IN (" . $aff_ids . ")");
                        $return_array = array('type' => 'success', 'content' => $this->arm_com_get_message_tab_content('inbox'));
                    }
                }
            }
            if (!isset($return_array)) {
                $return_array = array('type' => 'error', 'content' => $errors);
            }
            echo json_encode($return_array);
            die;
        }

        function arm_com_message_paging_action() {
            if (isset($_POST['action']) && $_POST['action'] == 'arm_com_message_paging_action') {
                unset($_POST['action']);
                if (!empty($_POST)) {
                    $per_page = isset($_POST['per_page']) ? $_POST['per_page'] : 3;
                    $current_page = isset($_POST['current_page']) ? $_POST['current_page'] : 0;
                    $list_type = isset($_POST['list_type']) ? $_POST['list_type'] : 0;
                    echo $this->arm_com_get_message_list($list_type, $per_page, $current_page);
                    exit;
                }
            }
        }

        function arm_com_message_compose() {
            global $arm_community_features, $wpdb, $arm_community_setting, $arm_community_friendship, $arm_manage_communication;
            $arm_can_send = false;
            $arm_msg_only_friends = 0;
            $response = array('type' => 'error', 'msg' => 'Message has been not send successfully.');
            $posted_data = $_POST;
            $arm_com_msg_receiver_id = $posted_data['arm_com_message_receiver_id'];
            $username_lbl = $arm_community_setting->arm_community_get_setting_val('arm_msg_username_lbl');
            if ($arm_com_msg_receiver_id <= 0) {
                if (email_exists($posted_data['arm_com_message_receiver'])) {
                    $arm_com_msg_receiver_data = get_user_by('email', $posted_data['arm_com_message_receiver']);
                } else {
                    $arm_com_msg_receiver_data = get_user_by('login', $posted_data['arm_com_message_receiver']);
                }

                if(isset($arm_com_msg_receiver_data) && !empty($arm_com_msg_receiver_data)){
                    $arm_com_msg_receiver_id = $arm_com_msg_receiver_data->ID;
                }
            }

            if ($arm_com_msg_receiver_id <= 0) {
                $msg = str_replace('[label]', $username_lbl , $arm_community_setting->arm_community_get_setting_val('arm_invalid_field_msg'));
                $response = array('type' => 'error', 'msg' => $msg);
            }

            if (is_user_logged_in() && $arm_com_msg_receiver_id > 0 && $this->arm_com_message_allow()) {
                $arm_msg_only_friends = $arm_community_setting->arm_community_get_setting_val('arm_message_only_friends');
                if ($arm_msg_only_friends && $arm_community_friendship->arm_com_friendship_is_friend($arm_com_msg_receiver_id)) {
                    $arm_can_send = true;
                } else if (!$arm_msg_only_friends) {
                    $arm_can_send = true;
                }
                $user_id = get_current_user_id();
                if ($arm_com_msg_receiver_id == $user_id) {
                    $arm_can_send = false;
                }

                if ($arm_can_send) {

                    $arm_is_message_blocked = 0;

                    $arm_get_blocked_users = get_user_meta($arm_com_msg_receiver_id, 'arm_com_msg_blocked');

                    if (!empty($arm_get_blocked_users)) {
                        $arm_get_blocked_users = (isset($arm_get_blocked_users[0]) && is_array($arm_get_blocked_users[0])) ? $arm_get_blocked_users[0] : $arm_get_blocked_users;

                        if (in_array($user_id, $arm_get_blocked_users)) {
                            $arm_is_message_blocked = 1;
                        }
                    }

                    if ($arm_is_message_blocked == 0) {
                        $arm_com_message = $posted_data['arm_com_message_msg'];
                        $wpdb->insert($arm_community_features->tbl_arm_com_message, array('arm_receiver_id' => $arm_com_msg_receiver_id,
                            'arm_sender_id' => $user_id,
                            'arm_subject' => 'test',
                            'arm_message' => $arm_com_message,
                            'arm_is_message_blocked' => $arm_is_message_blocked,
                            'arm_datetime' => current_time('mysql')
                                ), array('%d', '%d', '%s', '%s', '%s', '%s')
                        );
                        $message_id = $wpdb->insert_id;
                        if ($message_id > 0) {
                            $tab = isset($posted_data['tab']) ? $posted_data['tab'] : 'inbox';

                            if($tab == 'inbox'){
                                $content = $this->arm_com_message_get_inbox('inbox');
                            }
                            else if($tab == 'reply'){
                                    $content = $this->arm_com_get_message_thread($arm_com_msg_receiver_id);

                            }else{
                                $content = $this->arm_com_message_get_archive();
                            }

                            $plan_id = get_user_meta($arm_com_msg_receiver_id, 'arm_user_plan_ids', true);
                            $plan_id = isset($plan_id[0]) ? $plan_id[0] : 0;
                            $arm_com_message_email_type = 'arm_com_received_new_private_message';
                            $arm_manage_communication->membership_communication_mail($arm_com_message_email_type, $arm_com_msg_receiver_id, $plan_id);

                            $msg = $arm_community_setting->arm_community_get_setting_val('arm_msg_success_msg');
                            $response = array('type' => 'success', 'msg' => $msg, 'content' => $content);
                        }
                        
                    }
                    else {
                        $msg = $arm_community_setting->arm_community_get_setting_val('arm_msg_blocked_msg');
                        $response = array('type' => 'error', 'msg' => $msg);
                    }
                } else {
                    $msg = $arm_community_setting->arm_community_get_setting_val('arm_msg_not_frnd_msg');
                    $response = array('type' => 'error', 'msg' => $msg);
                }
            }
            echo json_encode($response);
            die;
        }

        function arm_com_block_user() {
            global $arm_community_features, $wpdb, $arm_community_setting, $arm_community_friendship, $ARMember;
            $arm_can_send = false;
            $arm_msg_only_friends = 0;
            $response = array('type' => 'error', 'msg' => __('Entered an invalid username.', ARM_COMMUNITY_TEXTDOMAIN));
            $posted_data = $_POST;
            $arm_com_block_user = 0;
            $arm_com_block_user_name = '';
            $arm_type = 'failed';
            if (email_exists($posted_data['arm_com_block_username'])){
                $arm_com_msg_receiver_data = get_user_by('email', $posted_data['arm_com_block_username']);
                $arm_com_block_user = $arm_com_msg_receiver_data->ID;
                $arm_com_block_user_name = $arm_com_msg_receiver_data->user_login;
            }
            else {
                $arm_com_msg_receiver_data = get_user_by('login', $posted_data['arm_com_block_username']);
                $arm_com_block_user = $arm_com_msg_receiver_data->ID;
                $arm_com_block_user_name = $arm_com_msg_receiver_data->user_login;
            }

            if ($arm_com_block_user <= 0) {
                $response = array('type' => 'error', 'msg' => __('Entered an invalid username.', ARM_COMMUNITY_TEXTDOMAIN));
            }

            if (is_user_logged_in() && $arm_com_block_user > 0 && $this->arm_com_message_allow()) {
                $user_id = get_current_user_id();
                $arm_get_blocked_users = get_user_meta($user_id, 'arm_com_msg_blocked');
                if (!empty($arm_get_blocked_users)) {
                    $arm_get_blocked_users = (isset($arm_get_blocked_users[0]) && is_array($arm_get_blocked_users[0])) ? $arm_get_blocked_users[0] : $arm_get_blocked_users;
                    if (!in_array($arm_com_block_user, $arm_get_blocked_users)) {
                        array_push($arm_get_blocked_users, $arm_com_block_user);
                        $arm_type = 'success';
                    }
                } else {
                    $arm_get_blocked_users = $arm_com_block_user;
                    $arm_type = 'success';
                }
                update_user_meta($user_id, 'arm_com_msg_blocked', $arm_get_blocked_users);

                $content .= '<div class="arm_blocked_user arm_blocked_user_' . $arm_com_block_user . '">';
                $content .= '<span class="arm_blocked_username"> ' . $arm_com_block_user_name . '</span>';
                $content .= '<a href="javascript:void(0);" class="arm_blocked_user_remove" id="arm_blocked_user" data-user-id="' . $arm_com_block_user . '"><i class="armfa armfa-times" aria-hidden="true"></i></a></div>';
                $response = array('type' => $arm_type, 'msg' => __('User blocked successfully', ARM_COMMUNITY_TEXTDOMAIN), 'content' => $content);
            }

            echo json_encode($response);
            die;
        }

        function arm_com_block_user_rmeove() {
            $response = array('type' => 'failed');
            if (is_user_logged_in() && $this->arm_com_message_allow() && isset($_POST['user_id']) && $_POST['user_id'] > 0) {
                $user_id = get_current_user_id();
                $arm_get_blocked_users = get_user_meta($user_id, 'arm_com_msg_blocked');
                if (!empty($arm_get_blocked_users)) {
                    $arm_get_blocked_users = ( is_array($arm_get_blocked_users[0]) ) ? $arm_get_blocked_users[0] : $arm_get_blocked_users;
                    if (in_array($_POST['user_id'], $arm_get_blocked_users)) {
                        unset($arm_get_blocked_users[array_search($_POST['user_id'], $arm_get_blocked_users)]);
                    }
                    update_user_meta($user_id, 'arm_com_msg_blocked', $arm_get_blocked_users);
                    $response = array('type' => 'success');
                }
            }
            echo json_encode($response);
            die;
        }

        function arm_com_get_privacy_content() {
            global $arm_community_setting;

            $user_id = '';
            $user_login = '';
            $user_data = $arm_community_setting->arm_com_profile_get_user_id();
            $user_data_arr = array_shift($user_data);
            $profile_user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0;
            $arm_blocked_users = array();

            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $arm_blocked_users = get_user_meta($user_id, 'arm_com_msg_blocked');
                $arm_blocked_users = ( isset($arm_blocked_users[0]) && is_array($arm_blocked_users[0]) ) ? $arm_blocked_users[0] : $arm_blocked_users;
            }
            if (is_user_logged_in() && $user_id != $profile_user_id) {
                $user_id = $profile_user_id;
                $user_login = isset($user_data_arr['user_login']) ? $user_data_arr['user_login'] : '';
            }

            $compose_content = '';
            $compose_content .= '<div class="arm_com_msg_content_wrapper"><div class="arm_blocked_users_main_container">';

            $compose_content .= '<div class="arm_blocked_users_container">';
            if (is_array($arm_blocked_users)) {
                foreach ($arm_blocked_users as $arm_block_user_id) {
                    $arm_block_user_data = get_userdata($arm_block_user_id);
                    $compose_content .= '<div class="arm_blocked_user arm_blocked_user_' . $arm_block_user_id . '">';
                    $compose_content .= '<span class="arm_blocked_username"> ' . $arm_block_user_data->user_login . '</span>';
                    $compose_content .= '<a href="javascript:void(0);" class="arm_blocked_user_remove" id="arm_blocked_user" data-user-id="' . $arm_block_user_id . '"><i class="armfa armfa-times" aria-hidden="true"></i></a></div>';
                }
            }
            $compose_content .= '</div>';


            if (!$arm_community_setting->arm_com_is_profile_editor()) {
                $compose_content .= '<form id="arm_com_msg_privacy_form" class="arm_com_msg_privacy_form">';
            }

            $compose_content .= '<div class="arm_com_msg_compose_row">';
            $compose_content .= '<div class="arm_com_msg_compose_column_label">';
            $username_lbl = $arm_community_setting->arm_community_get_setting_val('arm_msg_username_lbl');
            $compose_content .= '<label>' .$username_lbl . '</label>';

            $compose_content .= '</div>';
            $compose_content .= '<div class="arm_com_msg_compose_column arm_block_user_lbl">';
            $compose_content .= '<input type="text" id="arm_com_block_username" name="arm_com_block_username" value="' . $user_login . '" />';
            $blank_field_msg = str_replace('[label]', $username_lbl , $arm_community_setting->arm_community_get_setting_val('arm_blank_field_msg'));
            $compose_content .= '<span class="arm_com_block_username_error arm_com_field_error">'.$blank_field_msg.'</span>';

            $compose_content .= '</div>';
            $compose_content .= '</div>';

            $compose_content .= '<div class="arm_com_msg_compose_row">';

            $compose_content .= '<div class="arm_com_msg_compose_column">';
            $compose_content .= '<button id="arm_com_block_user" class="arm_com_block_user" name="arm_com_block_user" value="" >';
            $compose_content .= '<span class="arm_spinner"><svg version="1.1" id="arm_form_loader" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="18px" height="18px" viewBox="0 0 26.349 26.35" style="enable-background:new 0 0 26.349 26.35;" xml:space="preserve" ><g><g><circle cx="13.792" cy="3.082" r="3.082" /><circle cx="13.792" cy="24.501" r="1.849"/><circle cx="6.219" cy="6.218" r="2.774"/><circle cx="21.365" cy="21.363" r="1.541"/><circle cx="3.082" cy="13.792" r="2.465"/><circle cx="24.501" cy="13.791" r="1.232"/><path d="M4.694,19.84c-0.843,0.843-0.843,2.207,0,3.05c0.842,0.843,2.208,0.843,3.05,0c0.843-0.843,0.843-2.207,0-3.05 C6.902,18.996,5.537,18.988,4.694,19.84z"/><circle cx="21.364" cy="6.218" r="0.924"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg></span>';
            $compose_content .= __('Submit', ARM_COMMUNITY_TEXTDOMAIN);
            $compose_content .= '</button>';
            $compose_content .= '</div>';
            $compose_content .= '</div>';
            $compose_content .= '<span class="arm_com_msg_block_error"></span>';
            $compose_content .= '<span class="arm_com_msg_block_success"></span>';

            if (!$arm_community_setting->arm_com_is_profile_editor()) {
                $compose_content .= '</form></div>';
            }


            $compose_content .= '</div>';
            return $compose_content;
        }

        function arm_com_message_get_time($time) {
            $etime = current_time('timestamp') - $time;

            if ($etime < 1) {
                return __('Expired', ARM_DIRECT_LOGINS_TEXTDOMAIN);
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
                    return __(sprintf('sent %d %s ago', $r, ($r > 1 ? $a_plural[$str] : $str)), ARM_DIRECT_LOGINS_TEXTDOMAIN);
                }
            }
        }
    }
}
global $arm_community_message;
$arm_community_message = new ARM_Community_Message();