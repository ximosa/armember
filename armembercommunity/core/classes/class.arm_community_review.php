<?php
if (!class_exists('ARM_Community_Review')) {
    class ARM_Community_Review {
        function __construct() {
            add_shortcode('arm_community_review', array(&$this, 'arm_community_review_func'));
            add_shortcode('arm_community_review_display', array(&$this, 'arm_community_review_display_func'));
            add_shortcode('arm_community_average_rating', array(&$this, 'arm_community_average_rating_func'));
            add_action('wp_ajax_arm_com_review_add', array(&$this, 'arm_com_review_add'));
            add_action('wp_ajax_arm_get_user_reviews', array(&$this, 'arm_get_user_reviews_func'));
            add_action('wp_ajax_arm_com_review_list', array(&$this, 'arm_com_review_list'));
            add_action('wp_ajax_arm_com_review_remove_by_admin', array(&$this, 'arm_com_review_remove_by_admin'));
            add_action('wp_ajax_arm_com_review_bulk_action', array(&$this, 'arm_com_review_bulk_action'));
            add_action('wp_ajax_arm_com_review_approve', array(&$this, 'arm_com_review_approve'));
            add_action('wp_ajax_arm_com_review_disapprove', array(&$this, 'arm_com_review_disapprove'));
            add_action('delete_user', array(&$this, 'arm_com_review_delete_user_data'));
            add_action('wp_ajax_arm_com_review_edit_by_admin', array(&$this, 'arm_com_review_edit_by_admin_func'));
            add_action('wp_ajax_arm_community_review_display_front', array(&$this, 'arm_community_review_display_func'));
            add_action('wp_ajax_nopriv_arm_community_review_display_front', array(&$this, 'arm_community_review_display_func'));
        }

        function arm_get_user_reviews_func($posted_data = array()) {

            global $arm_community_features, $wpdb, $arm_community_features, $ARMember;

            if(method_exists($ARMember, 'arm_check_user_cap')){
                $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
            }
            $response = array('type' => 'error', 'content' => '');
            $posted_data = $_POST;

            if (isset($posted_data) && !empty($posted_data)) {

                $user_from = $posted_data['user_from'];
                $user_to = $posted_data['user_to'];

                if (!empty($user_from) && !empty($user_to)) {

                    $arm_get_user_reviews = $wpdb->get_row($wpdb->prepare("SELECT * FROM `" . $arm_community_features->tbl_arm_com_review . "` WHERE arm_user_to=%d and arm_user_from=%d", $user_to, $user_from), ARRAY_A);

                    $content = $this->arm_com_review_form($user_to, true, $arm_get_user_reviews);

                    $response = array('type' => 'success', 'content' => $content, 'edit_review_popup' => $this->arm_com_edit_review_popup_display());
                }
            }

            echo json_encode($response);
            exit;
        }

        function arm_community_average_rating_func($atts = array(), $content = array(), $tag = '') {
            $args = shortcode_atts(array('user_id' => 0,), $atts, $tag);

            extract($args);

            $return_val = '';
            if ($user_id != 0) {

                $user_reviews = $this->arm_com_get_user_reviews($user_id, false);
                $user_reviews = $user_reviews["user_reviews"];
                $avg_rating = 0;

                if (!empty($user_reviews)) {
                    $arm_total_ratings = count($user_reviews);
                    $arm_rating = 0;
                    foreach ($user_reviews as $user_review) {
                        if (isset($user_review['arm_rating']) && ($user_review["arm_approved"] == 1 )) {
                            $arm_rating += $user_review['arm_rating'];
                        }
                    }

                    $avg_rating = ($arm_rating / $arm_total_ratings);
                    $float_avg_rating = number_format($avg_rating, 2);

                    if ($float_avg_rating == 0) {
                        $float_avg_rating = 0;
                    } else if ($float_avg_rating == 0.5) {
                        $float_avg_rating = 0.5;
                    } else if ($float_avg_rating < 1) {
                        $float_avg_rating = 0.5;
                    } else if ($float_avg_rating < 1.5) {
                        $float_avg_rating = 1;
                    } else if ($float_avg_rating < 2) {
                        $float_avg_rating = 1.5;
                    } else if ($float_avg_rating < 2.5) {
                        $float_avg_rating = 2;
                    } else if ($float_avg_rating < 3) {
                        $float_avg_rating = 2.5;
                    } else if ($float_avg_rating < 3.5) {
                        $float_avg_rating = 3;
                    } else if ($float_avg_rating < 4) {
                        $float_avg_rating = 3.5;
                    } else if ($float_avg_rating < 4.5) {
                        $float_avg_rating = 4;
                    } else if ($float_avg_rating < 5) {
                        $float_avg_rating = 4.5;
                    } else {
                        $float_avg_rating = 5;
                    }

                    return '<div class="arm_user_avg_review">' . $this->arm_com_get_star($float_avg_rating, '', false, 'disabled="disabled"') . '</div>';
                }
            }
            return $return_val;
        }

        function arm_com_review_allow() {
            global $arm_community_setting;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            if(isset($arm_com_settings['arm_com_review']) && $arm_com_settings['arm_com_review'] == '1'){
                return true;
            }
            return false;
        }

        function arm_community_review_func($atts = array(), $content = array(), $tag = '') {
            global $wpdb, $arm_community_setting, $arm_community_features;
            if ($arm_community_setting->arm_com_is_profile_editor()) {
                $user_id = 0;
            } else {
                $user_data = $arm_community_setting->arm_com_profile_get_user_id();
                $user_data_arr = array_shift($user_data);
                $user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0;
            }

            $args = shortcode_atts(array('user_id' => $user_id,), $atts, $tag);

            if (is_user_logged_in() && $this->arm_com_review_allow() && !empty($args['user_id'])) {
                $user_id = get_current_user_id();
                $content .= '<div class="arm_com_review_container" id="arm_com_review_container">';
                $content .= '<div class="arm_com_review_wrapper" id="arm_com_review_wrapper">';
                $content .= '<a href="javascript:void(0);" id="arm_com_give_review">';
                $content .= __('Give Review', ARM_COMMUNITY_TEXTDOMAIN);
                $content .= '</a>';
                $content .= '</div>';
                $content .= $this->arm_com_give_review_popup_display($args['user_id']);
                $content .= '</div>';
            }
            return $content;
        }

        function arm_community_review_display_func($atts = array(), $content = array(), $tag = '') {
            if($this->arm_com_review_allow()) {
                global $wpdb, $arm_community_setting, $arm_community_features, $arm_global_settings;
                $arm_com_settings = $arm_community_setting->arm_com_settings;
                $arm_keep_review_public = $arm_com_settings['arm_keep_reviews_public'];

                if ($arm_community_setting->arm_com_is_profile_editor()) {
                    $user_id = 0;
                }
                else {
                    $user_data = $arm_community_setting->arm_com_profile_get_user_id();
                    $user_data_arr = array_shift($user_data);
                    $user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0;
                }

                $current_login_user = get_current_user_id();

                $args = shortcode_atts(array('user_id' => $user_id,), $atts, $tag);
                
                $user_reviews = $this->arm_com_get_user_reviews($args['user_id'], true);

                $paging = $user_reviews["paging"];

                $user_reviews = $user_reviews["user_reviews"];
                
                $content .= '<div class="arm_com_disp_review_container" id="arm_com_disp_review_container">';

                $content .= "<div class='arm_template_loading'><img src='".MEMBERSHIP_IMAGES_URL."/loader_template.gif' alt='".__('Loading',ARM_COMMUNITY_TEXTDOMAIN)."..' /></div>";

                $arm_review_section_lbl = !empty($arm_com_settings["arm_review_section_lbl"]) ? (__('Average', ARM_COMMUNITY_TEXTDOMAIN) . " " .$arm_com_settings["arm_review_section_lbl"]) : __('Average Reviews', ARM_COMMUNITY_TEXTDOMAIN);

                $content .= '<div class="arm_review_title"><div class="arm_review_title_text">' . $arm_review_section_lbl . '</div><div class="arm_review_avg_rating">' . do_shortcode('[arm_community_average_rating user_id= ' . $args['user_id'] . ']') . '</div>';

                $add_edit_review_button = "";
                if(!empty($current_login_user)) {
                    $add_edit_review_button = '<div class="arm_review_title_add_edit_link"><a id="arm_com_give_review">+' . __('Add Review', ARM_COMMUNITY_TEXTDOMAIN) . '</a></div>' . $this->arm_com_give_review_popup_display($args['user_id']) . $this->arm_com_edit_review_popup_display();
                }

                if (!empty($user_reviews)) {
                    $review_from = array();
                    $count = 0;
                    if(!empty($current_login_user)) {
                        foreach ($user_reviews as $user_review) {
                            $review_from[] = $user_review['arm_user_from'];
                            if (in_array($current_login_user, $review_from)) {
                                if(empty($arm_com_settings["arm_review_editable"])) {
                                    $add_edit_review_button = "";
                                }
                                else {
                                    $add_edit_review_button = '<div class="arm_review_title_add_edit_link"><a class="arm_edit_user_review_button" id="arm_com_give_review" data-user_from = "' . $current_login_user . '" data-user_to = "' . $args['user_id'] . '" >+' . __('Edit Review', ARM_COMMUNITY_TEXTDOMAIN) . '</a></div>' . $this->arm_com_edit_review_popup_display();
                                }

                                $count++;
                                break;
                            }
                        }

                        if ($count == 0) {
                            if ($current_login_user != $args['user_id']) {
                                $content .= $add_edit_review_button;
                            }
                        }
                        else {
                            $content .= $add_edit_review_button;
                        }
                    }

                    $content .= '</div>';

                    $content .= '<div class="arm_com_disp_review_wrapper" id="arm_com_disp_review_wrapper"><div class="arm_com_displ_review_container">';

                    $content_tmp = "";
                    foreach ($user_reviews as $user_review) {
                        $user_review_from = $user_review['arm_user_from'];
                        $user_review_to = $user_review['arm_user_to'];
                        if ( $arm_community_setting->arm_com_is_profile_editor() || ($user_review["arm_approved"] == 1 && ($arm_keep_review_public || $user_review_from == $user_id || $user_review_to == $user_id)) ) {
                            $content_tmp .= $this->arm_com_get_review_box($user_review);
                        }
                    }
                    if($content_tmp == "") {
                        $arm_com_settings = $arm_community_setting->arm_com_settings;
                        $arm_review_section_lbl = !empty($arm_com_settings["arm_review_section_lbl"]) ? $arm_com_settings["arm_review_section_lbl"] : __("Review", ARM_COMMUNITY_TEXTDOMAIN);
                        $content .= '<div class="arm_no_review_msg">';
                        $content .= __('No', ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_review_section_lbl . " " . __('found.', ARM_COMMUNITY_TEXTDOMAIN);
                        $content .= '</div>';
                    }
                    else {
                        $content .= $content_tmp;
                    }
                }
                else {
                    if ($current_login_user != $args['user_id']) {
                        $content .= $add_edit_review_button;
                    }

                    $content .= '</div>';

                    $content .= '<div class="arm_com_disp_review_wrapper" id="arm_com_disp_review_wrapper"><div class="arm_com_displ_review_container">';

                    $arm_com_settings = $arm_community_setting->arm_com_settings;
                    $arm_review_section_lbl = !empty($arm_com_settings["arm_review_section_lbl"]) ? $arm_com_settings["arm_review_section_lbl"] : __("Review", ARM_COMMUNITY_TEXTDOMAIN);
                    $content .= '<div class="arm_no_review_msg">';
                    $content .= __('No', ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_review_section_lbl . " " . __('found.', ARM_COMMUNITY_TEXTDOMAIN);
                    $content .= '</div>';
                }
                $content .= '</div>';
                $content .= '</div></div>';
                if( !empty($paging) ) {
                    $content .= "<div class='arm_review_paging_div'>".$paging."</div>";
                }

                if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "arm_community_review_display_front") {
                    $response = array("content" => $content_tmp, "paging" => $paging);
                    echo json_encode($response);
                    exit;
                }
            }        
            
            return $content;
        }

        function arm_com_get_review_box($user_review) {
            $review_box = '';
            if( isset($user_review["arm_approved"]) && $user_review["arm_approved"] == 1 || get_current_user_id() == $user_review["arm_user_from"] || get_current_user_id() == $user_review["arm_user_to"]) {
                global $arm_global_settings, $arm_community_setting;
                $date_format = $arm_global_settings->arm_get_wp_date_time_format();
                $arm_user_from = $user_review['arm_user_from'];
                $user_meta = get_userdata($arm_user_from);
                $arm_user_profile_link = $arm_global_settings->arm_get_user_profile_url($user_meta->ID, 1);
                $review_box .= '<div class="arm_com_review_box_container" id="arm_com_review_box_' . $arm_user_from . '"><div class="arm_com_review_box arm_com_review_box_' . $arm_user_from . '" >';
                $review_box .= '<div class="arm_com_review_user_img">';
                if( $arm_community_setting->arm_com_is_profile_editor() ) {
                    $profile_url = '#';
                }
                else {
                    $profile_url = $arm_global_settings->arm_get_user_profile_url($arm_user_from);
                }

                $review_box .= "<a href='".$profile_url."'>";
                $review_box .= get_avatar($arm_user_from, '50');
                $review_box .= "</a>";
                $review_box .= '</div>';
                $review_box .= '<div class="arm_com_review_content">';
                $review_box .= '<div class="arm_com_review_rating">';
                $review_box .= $this->arm_com_get_star($user_review['arm_rating'], $user_review['arm_review_id'], false, 'disabled="disabled"');
                $review_box .= '</div>';
                $review_box .= '<div class="arm_user_name">';
                $review_box .= __('By', ARM_COMMUNITY_TEXTDOMAIN) . ' <span><a href="' . $arm_user_profile_link . '">' . $user_meta->display_name . '</a></span>';
                $review_box .= ', ' . date_i18n($date_format, strtotime($user_review['arm_datetime']));
                $review_box .= '</div>';
                $review_box .= '<div class="arm_com_reivew_title">' . $user_review['arm_title'] . '</div>';
                $review_box .= '<div class="arm_com_reivew_desc">' . nl2br($user_review['arm_description']) . '</div>';
                $review_box .= '</div>';
                $review_box .= '</div></div>';
            }
            return $review_box;
        }

        function arm_com_give_review_popup_display($user_to, $user_review = array()) {
            $popup = '';
            $popup .= '<div class="arm_com_review_popup popup_wrapper arm_popup_wrapper arm_popup_community_form" style="width: 650px; margin-top: 40px;">';
            $popup .= '<div class="popup_wrapper_inner">';
            $popup .= '<div class="popup_header">';
            $popup .= '<span class="popup_close_btn arm_popup_close_btn"></span>';
            $popup .= '<div class="popup_header_text arm_form_heading_container">';
            $popup .= '<span class="arm_form_field_label_wrapper_text">' . __('Give Review', ARM_COMMUNITY_TEXTDOMAIN) . '</span>';
            $popup .= '</div></div>';
            $popup .= '<div class="popup_content_text">';
            $popup .= $this->arm_com_review_form($user_to, true, $user_review);
            $popup .= '</div>';
            $popup .= '</div>';
            $popup .= '</div>';
            return $popup;
        }

        function arm_com_edit_review_popup_display() {
            $popup = '<div class="arm_edit_com_review_popup popup_wrapper arm_popup_wrapper arm_popup_community_form" style="width: 650px; margin-top: 40px;">';
            $popup .= '<div class="popup_wrapper_inner">';
            $popup .= '<div class="popup_header">';
            $popup .= '<span class="popup_close_btn arm_popup_close_btn"></span>';
            $popup .= '<div class="popup_header_text arm_form_heading_container">';
            $popup .= '<span class="arm_form_field_label_wrapper_text">' . __('Edit Review', ARM_COMMUNITY_TEXTDOMAIN) . '</span>';
            $popup .= '</div></div>';
            $popup .= '<div class="popup_content_text arm_edit_review_popup">';
            $popup .= '</div>';
            $popup .= '</div>';
            $popup .= '</div>';
            return $popup;
        }

        function arm_com_get_star($arm_select = 0, $arm_star_name = '', $review_popup = false, $disable = '') {
            if ($arm_select > 0) {
                $arm_name = 'arm_display_rating_' . $arm_star_name;
                $arm_id_prefix = 'arm_dis_';
            } else {
                $arm_name = 'arm_rating';
                $arm_id_prefix = 'arm_';
                if ($review_popup) {
                    $arm_name = 'arm_popup_rating';
                    $arm_id_prefix = 'arm_popup_';
                }
            }

            if ($disable != '') {
                $arm_class = 'arm_rating_display';
                $arm_name = 'arm_display_rating_' . $arm_star_name;
                $arm_id_prefix = 'arm_dis_';
            } else {
                $arm_class = 'arm_rating';
                $arm_name = 'arm_rating';
                $arm_id_prefix = 'arm_';
                if ($review_popup) {
                    $arm_name = 'arm_popup_rating';
                    $arm_id_prefix = 'arm_popup_';
                }
            }

            $arm_star = '';
            $arm_star .= '<fieldset class="' . $arm_class . '">';

            $arm_star .= '<input type="radio" id="' . $arm_id_prefix . 'star5" name="' . $arm_name . '" value="5" ' . $disable . checked($arm_select, 5, false) . ' />';
            $arm_star .= '<label class = "full" for="' . $arm_id_prefix . 'star5" title="Awesome - 5 stars"></label>';

            $arm_star .= '<input type="radio" id="' . $arm_id_prefix . 'star4half" name="' . $arm_name . '" value="4.5" ' . $disable . checked($arm_select, 4.5, false) . ' />';
            $arm_star .= '<label class="half" for="' . $arm_id_prefix . 'star4half" title="Pretty good - 4.5 stars"></label>';

            $arm_star .= '<input type="radio" id="' . $arm_id_prefix . 'star4" name="' . $arm_name . '" value="4" ' . $disable . checked($arm_select, 4, false) . ' />';
            $arm_star .= '<label class = "full" for="' . $arm_id_prefix . 'star4" title="Pretty good - 4 stars"></label>';

            $arm_star .= '<input type="radio" id="' . $arm_id_prefix . 'star3half" name="' . $arm_name . '" value="3.5" ' . $disable . checked($arm_select, 3.5, false) . ' />';
            $arm_star .= '<label class="half" for="' . $arm_id_prefix . 'star3half" title="Meh - 3.5 stars"></label>';

            $arm_star .= '<input type="radio" id="' . $arm_id_prefix . 'star3" name="' . $arm_name . '" value="3" ' . $disable . checked($arm_select, 3, false) . ' />';
            $arm_star .= '<label class = "full" for="' . $arm_id_prefix . 'star3" title="Meh - 3 stars"></label>';

            $arm_star .= '<input type="radio" id="' . $arm_id_prefix . 'star2half" name="' . $arm_name . '" value="2.5" ' . $disable . checked($arm_select, 2.5, false) . ' />';
            $arm_star .= '<label class="half" for="' . $arm_id_prefix . 'star2half" title="Kinda bad - 2.5 stars"></label>';

            $arm_star .= '<input type="radio" id="' . $arm_id_prefix . 'star2" name="' . $arm_name . '" value="2" ' . $disable . checked($arm_select, 2, false) . ' />';
            $arm_star .= '<label class = "full" for="' . $arm_id_prefix . 'star2" title="Kinda bad - 2 stars"></label>';

            $arm_star .= '<input type="radio" id="' . $arm_id_prefix . 'star1half" name="' . $arm_name . '" value="1.5" ' . $disable . checked($arm_select, 1.5, false) . ' />';
            $arm_star .= '<label class="half" for="' . $arm_id_prefix . 'star1half" title="Meh - 1.5 stars"></label>';

            $arm_star .= '<input type="radio" id="' . $arm_id_prefix . 'star1" name="' . $arm_name . '" value="1" ' . $disable . checked($arm_select, 1, false) . ' />';
            $arm_star .= '<label class = "full" for="' . $arm_id_prefix . 'star1" title="Sucks big time - 1 star"></label>';

            $arm_star .= '<input type="radio" id="' . $arm_id_prefix . 'starhalf" name="' . $arm_name . '" value="0.5" ' . $disable . checked($arm_select, 0.5, false) . ' />';
            $arm_star .= '<label class="half" for="' . $arm_id_prefix . 'starhalf" title="Sucks big time - 0.5 stars"></label>';

            $arm_star .= '</fieldset>';
            return $arm_star;
        }

        function arm_com_review_form($user_to, $review_popup = false, $user_review = array()) {
            global $arm_community_setting;
            $user_id = 0;
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
            }

            $arm_review_title = '';
            $arm_review_description = '';
            $arm_review_rating = '';
            $arm_review_id = '';

            if (!empty($user_review)) {
                if ($user_review['arm_user_from'] == $user_id || (current_user_can('arm_community_activity') && current_user_can('administrator')) ) {
                    $arm_review_title = $user_review['arm_title'];
                    $arm_review_description = $user_review['arm_description'];
                    $arm_review_rating = $user_review['arm_rating'];
                    $arm_review_id = $user_review['arm_review_id'];
                }
            }
            $compose_content = '';
            if (!$arm_community_setting->arm_com_is_profile_editor()) {
                $compose_content .= '<form method="post" action="#" id="arm_com_give_review_form" name="arm_com_give_review_form" class="arm_admin_form arm_com_give_review_form">';
            }
            $compose_content .= '<div class="arm_review_add_success"></div>';
            $compose_content .= '<div class="arm_review_add_error"></div>';
            $compose_content .= '<div class="arm_review_form_container">';


            $compose_content .= '<div class="arm_com_msg_compose_row">';
            $compose_content .= '<div class="arm_com_msg_compose_column_label">';
            $compose_content .= '<label>' . __('Rating', ARM_COMMUNITY_TEXTDOMAIN) . '</label>';
            $compose_content .= '</div>';
            $compose_content .= '<div class="arm_com_msg_compose_column">';
            $compose_content .= $this->arm_com_get_star($arm_review_rating, $arm_review_id, $review_popup);
            $compose_content .= '<br/><span class="arm_com_review_rating_error">' . __('Please selelct Rating', ARM_COMMUNITY_TEXTDOMAIN) . '</span>';
            $compose_content .= '</div>';
            $compose_content .= '</div>';

            $compose_content .= '<div class="arm_com_msg_compose_row">';
            $compose_content .= '<div class="arm_com_msg_compose_column_label">';
            $compose_content .= '<label>' . __('Title', ARM_COMMUNITY_TEXTDOMAIN) . '</label>';
            $compose_content .= '</div>';
            $compose_content .= '<div class="arm_com_msg_compose_column">';
            $compose_content .= '<input type="text" name="arm_title" id="arm_title" class="arm_title" value="' . $arm_review_title . '"/>';
            $compose_content .= '<br/><span class="arm_com_review_title_error">' . __('Please enter title', ARM_COMMUNITY_TEXTDOMAIN) . '</span>';
            $compose_content .= '</div>';
            $compose_content .= '</div>';

            $compose_content .= '<div class="arm_com_msg_compose_row">';
            $compose_content .= '<div class="arm_com_msg_compose_column_label">';
            $compose_content .= '<label>' . __('Description', ARM_COMMUNITY_TEXTDOMAIN) . '</label>';
            $compose_content .= '</div>';
            $compose_content .= '<div class="arm_com_msg_compose_column">';
            $compose_content .= '<textarea name="arm_description" id="arm_description" class="arm_description" rows="5">' . $arm_review_description . '</textarea>';
            $compose_content .= '<br/><span class="arm_com_review_desc_error">' . __('Please enter description', ARM_COMMUNITY_TEXTDOMAIN) . '</span>';
            $compose_content .= '</div>';
            $compose_content .= '</div>';
            $compose_content .= '<input type="hidden" name="arm_user_to" id="arm_user_to" value="' . $user_to . '" />';
            $compose_content .= '<div class="arm_com_msg_compose_row">';
            $compose_content .= '<div class="arm_com_msg_compose_column_label">';
            $compose_content .= '<label></label>';
            $compose_content .= '</div>';
            $compose_content .= '<div class="arm_com_msg_compose_column">';
             $compose_content .= '<div class="arm_com_review_button_wrapper">';

            $compose_content .= '<button id="arm_com_review_btn" class="arm_com_review_btn" name="arm_com_review_btn" value="" data-user_id="' . $user_id . '">';
            $compose_content .= __('Submit', ARM_COMMUNITY_TEXTDOMAIN);
            $compose_content .= '</button>';
            $compose_content .= '<span class="arm_spinner arm_com_review_btn_spinner"><svg version="1.1" id="arm_form_loader" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="18px" height="18px" viewBox="0 0 26.349 26.35" style="enable-background:new 0 0 26.349 26.35;" xml:space="preserve" ><g><g><circle cx="13.792" cy="3.082" r="3.082" /><circle cx="13.792" cy="24.501" r="1.849"/><circle cx="6.219" cy="6.218" r="2.774"/><circle cx="21.365" cy="21.363" r="1.541"/><circle cx="3.082" cy="13.792" r="2.465"/><circle cx="24.501" cy="13.791" r="1.232"/><path d="M4.694,19.84c-0.843,0.843-0.843,2.207,0,3.05c0.842,0.843,2.208,0.843,3.05,0c0.843-0.843,0.843-2.207,0-3.05 C6.902,18.996,5.537,18.988,4.694,19.84z"/><circle cx="21.364" cy="6.218" r="0.924"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg></span>';
            $compose_content .= '</div>';
            $compose_content .= '</div>';

            $compose_content .= '</div>';
             $compose_content .= '</div>';
            if (!$arm_community_setting->arm_com_is_profile_editor()) {
                $compose_content .= '</form>';
            }
            return $compose_content;
        }

        function arm_com_get_avg_review_user($user_id) {
            global $wpdb, $arm_community_features;
            $arm_user_avg_rating = $wpdb->get_row($wpdb->prepare("SELECT avg(arm_rating) as avg_review FROM `" . $arm_community_features->tbl_arm_com_review . "` WHERE arm_user_to=%d", $user_id), ARRAY_A);
            return number_format($arm_user_avg_rating['avg_review'], 2);
        }

        function arm_com_get_user_reviews($user_id, $is_front = false) {
            global $wpdb, $arm_community_features, $arm_community_setting;
            $paging = "";
            if ($arm_community_setting->arm_com_is_profile_editor()) {
                $user_id = get_current_user_id();

                $arm_get_user_reviews[0]['arm_review_id'] = 1;
                $arm_get_user_reviews[0]['arm_rating'] = 4.5;
                $arm_get_user_reviews[0]['arm_user_from'] = $user_id;
                $arm_get_user_reviews[0]['arm_user_to'] = $user_id;
                $arm_get_user_reviews[0]['arm_title'] = 'this is my first post';
                $arm_get_user_reviews[0]['arm_description'] = 'This is my first post content.';
                $arm_get_user_reviews[0]['arm_datetime'] = current_time('mysql');

                $arm_get_user_reviews[1]['arm_review_id'] = 2;
                $arm_get_user_reviews[1]['arm_rating'] = 4;
                $arm_get_user_reviews[1]['arm_user_from'] = $user_id;
                $arm_get_user_reviews[1]['arm_user_to'] = $user_id;
                $arm_get_user_reviews[1]['arm_title'] = 'this is my first post';
                $arm_get_user_reviews[1]['arm_description'] = 'This is my first post content.';
                $arm_get_user_reviews[1]['arm_datetime'] = current_time('mysql');
            } else {
                $arm_current_user = get_current_user_id();
                $reviews_query = "SELECT * FROM `" . $arm_community_features->tbl_arm_com_review . "` WHERE (arm_user_to={$user_id}) AND (arm_approved = 1 OR ( arm_user_from={$arm_current_user} AND arm_approved = 0 ) ) ORDER BY arm_datetime DESC";

                if($is_front) {

                    $total_reviews_qry = $wpdb->get_row("SELECT count(*) AS ttl FROM `" . $arm_community_features->tbl_arm_com_review . "` WHERE arm_user_to={$user_id}");

                    $total_reviews = $total_reviews_qry->ttl;

                    $arm_com_settings = $arm_community_setting->arm_com_settings;
                    $records_per_page = isset($arm_com_settings["arm_record_per_page"]) ? $arm_com_settings["arm_record_per_page"] : 10;

                    if($total_reviews > $records_per_page) {

                        $pageno = !empty($_GET["pageno"]) ? $_GET["pageno"] : 1;

                        $offset = ($pageno - 1) * $records_per_page;

                        $reviews_query .= " LIMIT {$offset}, {$records_per_page}";

                        $com_pagination_style = isset($arm_com_settings["arm_com_pagination_style"]) ? $arm_com_settings["arm_com_pagination_style"] : 'numeric';

                        if($com_pagination_style == "numeric") {
                            global $arm_global_settings;
                            $paging = $arm_global_settings->arm_get_paging_links($pageno, $total_reviews, $records_per_page, 'review');
                        }
                        else {

                            $total_pages = ceil( $total_reviews / $records_per_page );

                            $more_link_cnt = '<a class="arm_com_review_load_more_link arm_page_numbers" href="javascript:void(0)" data-page="' . ($pageno + 1) . '" data-type="review" data-arm_ttl_page="'.$total_pages.'">' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '</a>';

                            $more_link_cnt .= '<img class="arm_load_more_loader" src="'.MEMBERSHIP_IMAGES_URL.'/arm_loader.gif" alt="' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '" style="display:none;">';

                            $paging .= '<div class="arm_com_review_paging_container arm_com_review_paging_container_infinite">' . $more_link_cnt . '</div>';
                        }
                    }
                }

                $arm_get_user_reviews = $wpdb->get_results($reviews_query, ARRAY_A);
            }

            return array( "user_reviews" => $arm_get_user_reviews, "paging" => $paging);
        }

        function arm_com_review_add() {
            global $arm_community_features, $wpdb, $arm_community_setting, $arm_community_friendship;
            $arm_can_send = false;
            $arm_msg_only_friends = 0;
            $response = array('type' => 'error', 'msg' => __('Review has been not added successfully.', ARM_COMMUNITY_TEXTDOMAIN));
            $posted_data = $_POST;

            $arm_com_review_user_to = $posted_data['arm_user_to'];

            if ($arm_com_review_user_to <= 0) {
                $response = array('type' => 'error', 'msg' => __('Review has been not added because the user not exists.', ARM_COMMUNITY_TEXTDOMAIN));
            }

            if (is_user_logged_in() && $arm_com_review_user_to > 0 && $this->arm_com_review_allow()) {

                $user_id = get_current_user_id();
                $arm_com_review_rating = isset($posted_data['arm_popup_rating']) ? $posted_data['arm_popup_rating'] : 0;
                $arm_com_review_description = $posted_data['arm_description'];
                $arm_com_review_title = $posted_data['arm_title'];
                
                $arm_com_settings = $arm_community_setting->arm_com_settings;
                $arm_approved = !empty($arm_com_settings['arm_review_approved_by_admin']) ? 0 : 1;

                $arm_com_review_data = array('arm_rating' => $arm_com_review_rating,
                    'arm_user_from' => $user_id,
                    'arm_user_to' => $arm_com_review_user_to,
                    'arm_title' => $arm_com_review_title,
                    'arm_description' => $arm_com_review_description,
                    'arm_approved' => $arm_approved,
                    'arm_datetime' => current_time('mysql')
                );

                $arm_select_query = $wpdb->get_var("select `arm_review_id` from {$arm_community_features->tbl_arm_com_review} where `arm_user_from` = {$user_id} and `arm_user_to`={$arm_com_review_user_to}");

                if ($arm_select_query != '' && !empty($arm_com_settings['arm_review_editable'])  ) {
                    $review_id = $arm_select_query;
                    $wpdb->update(
                            $arm_community_features->tbl_arm_com_review, array('arm_rating' => $arm_com_review_rating,
                        'arm_user_from' => $user_id,
                        'arm_user_to' => $arm_com_review_user_to,
                        'arm_title' => $arm_com_review_title,
                        'arm_description' => $arm_com_review_description,
                        'arm_datetime' => current_time('mysql')
                            ), array('arm_review_id' => $arm_select_query), array('%f', '%d', '%d', '%s', '%s', '%s'), array('%d')
                    );

                    do_action('arm_com_activity', '[arm_com_displayname id="' . $user_id . '"] ' . __('user edit review given to', ARM_COMMUNITY_TEXTDOMAIN) . ' [arm_com_displayname id="' . $arm_com_review_user_to . '"]', __('Edit Review', ARM_COMMUNITY_TEXTDOMAIN), $arm_com_review_user_to, 'edit_review', 0);

                    $edit_review_poup = '';
                } else {
                    $wpdb->insert($arm_community_features->tbl_arm_com_review, $arm_com_review_data, array('%f', '%d', '%d', '%s', '%s', '%d', '%s'));
                    $review_id = $wpdb->insert_id;
                    $edit_review_poup = $this->arm_com_edit_review_popup_display();

                    if($review_id > 0) {
                        do_action('arm_com_activity', '[arm_com_displayname id="' . $user_id . '"] ' . __('user give review to', ARM_COMMUNITY_TEXTDOMAIN) . ' [arm_com_displayname id="' . $arm_com_review_user_to . '"]', __('Add Review', ARM_COMMUNITY_TEXTDOMAIN), $arm_com_review_user_to, 'add_review', 0);
                    }
                }


                if ($review_id > 0) {
                    $arm_com_review_data['arm_review_id'] = $review_id;

                    $arm_select_query = $wpdb->get_var("select `arm_approved` from {$arm_community_features->tbl_arm_com_review} where `arm_review_id` = {$review_id}");

                    $res_content = "";
                    if($arm_select_query == 1) {
                        $res_content = $this->arm_com_get_review_box($arm_com_review_data);
                    }

                    $arm_review_editable = !empty($arm_com_settings['arm_review_editable']) ? 1 : 0;
                    $avg_rating_display = do_shortcode('[arm_community_average_rating user_id= "' . $arm_com_review_user_to . '"]');
                    $response = array('type' => 'success', 'msg' => __('Review has been added successfully.', ARM_COMMUNITY_TEXTDOMAIN), 'content' => $res_content, 'edit_review_popup' => $edit_review_poup, 'user_from' => $user_id, 'user_to' => $arm_com_review_user_to, 'avg_rating' => $avg_rating_display, 'review_editable' => $arm_review_editable);
                }
            }
            echo json_encode($response);
            die;
        }

        function arm_com_review_delete_user_data($user_id) {
            global $arm_community_features, $wpdb;
            if (isset($user_id) && !empty($user_id)) {
                $wpdb->get_results($wpdb->prepare("DELETE FROM `" . $arm_community_features->tbl_arm_com_review . "` WHERE arm_user_from=%d OR arm_user_to=%d", $user_id, $user_id));
            }
        }

        function arm_com_review_list() {
            global $wpdb, $arm_global_settings, $arm_community_features, $arm_community_setting, $ARMember;
            $date_format = $arm_global_settings->arm_get_wp_date_time_format();

            if(method_exists($ARMember, 'arm_check_user_cap')){
                $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
            }
            $grid_columns = array(
                'review_title' => __('Title', ARM_COMMUNITY_TEXTDOMAIN),
                'review_desc' => __('Description', ARM_COMMUNITY_TEXTDOMAIN),
                'review_from' => __('Review From', ARM_COMMUNITY_TEXTDOMAIN),
                'review_to' => __('Review To', ARM_COMMUNITY_TEXTDOMAIN),
                'review_rating' => __('Rating', ARM_COMMUNITY_TEXTDOMAIN),
                'review_date' => __('Date', ARM_COMMUNITY_TEXTDOMAIN),
            );

            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 3;
            $search_term = !empty($_REQUEST["com_review_search_term"]) ? $_REQUEST["com_review_search_term"] : '';
            $start_data = !empty($_REQUEST["com_review_search_start_data"]) ? $_REQUEST["com_review_search_start_data"] : '';
            $end_data = !empty($_REQUEST["com_review_search_end_data"]) ? $_REQUEST["com_review_search_end_data"] : '';
            $order_by = 'rv.arm_review_id';

            $where_flag = "WHERE 1 = 1";

            if( !empty($search_term) ) {
                $where_flag .= " AND (rv.arm_title LIKE '%{$search_term}%' OR rv_from.user_login LIKE '%{$search_term}%')";
            }

            if( !empty($start_data) ) {
                $start_data = date("Y-m-d", strtotime($start_data));
                $where_flag .= " AND rv.arm_datetime >= '$start_data'";
            }

            if( !empty($end_data) ) {
                $end_data = date("Y-m-d", strtotime("+1 day", strtotime($end_data)));
                $where_flag .= " AND rv.arm_datetime < '$end_data'";
            }

            $reviews_query = "SELECT rv.*, rv_from.user_login AS arm_from, rv_to.user_login AS arm_to FROM `".$arm_community_features->tbl_arm_com_review."` rv LEFT JOIN `".$wpdb->users."` rv_from ON rv.arm_user_from = rv_from.ID LEFT JOIN `".$wpdb->users."` rv_to ON rv.arm_user_to = rv_to.ID ".$where_flag." ORDER BY {$order_by} {$sorting_ord}";

            $reviews = $wpdb->get_results($reviews_query);

            $total_before_filter = $total_after_filter = count($reviews);

            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            $reviews_query = $reviews_query . " LIMIT {$offset},{$number}";
            $reviews = $wpdb->get_results($reviews_query);

            $grid_data = array();
            if (is_array($reviews) && count($reviews) > 0) {
                $ai = 0;
                foreach ($reviews as $review) {
                    $grid_data[$ai][0] = "<input id=\"cb-item-action-{$review->arm_review_id}\" class=\"chkstanard\" type=\"checkbox\" value=\"{$review->arm_review_id}\" name=\"item-action[]\">";
                    $grid_data[$ai][1] = $review->arm_title; 
                    $grid_data[$ai][2] = nl2br(stripslashes($review->arm_description)); $review->arm_to;
                    $grid_data[$ai][3] = $review->arm_from;
                    $grid_data[$ai][4] = $review->arm_to;

                    $arm_review_rating = $this->arm_com_get_star($review->arm_rating, $review->arm_review_id, false, 'disabled="disabled"');

                    $grid_data[$ai][5] = $arm_review_rating;
                    $grid_data[$ai][6] = date($date_format, strtotime($review->arm_datetime));

                    $gridAction = "<div class='arm_grid_action_btn_container'>";

                    if( $review->arm_approved == 0 ) {
                        $gridAction .= "<a href='javascript:void(0)'><img src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_approved.png' class='armhelptip arm_approve_review_nav' title='" . __('Approve Review', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_approved_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_approved.png';\" data-arm_review_id='{$review->arm_review_id}' /></a>";
                    }
                    else {
                        $gridAction .= "<a href='javascript:void(0)'><img src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_denied.png' class='armhelptip arm_disapprove_review_nav' title='" . __('Disapprove', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_denied_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_denied.png';\" data-arm_review_id='{$review->arm_review_id}' /></a>";
                    }

                    $gridAction .= "<a href='javascript:void(0)'><img src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_edit.png' class='armhelptip arm_edit_review_nav' title='" . __('Edit Review', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_edit_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_edit.png';\" data-arm_review_id='{$review->arm_review_id}' data-user_from={$review->arm_user_from} data-user_to={$review->arm_user_to} /></a>";
                 
                    $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$review->arm_review_id});'><img src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete.png' class='armhelptip' title='" . __('Delete Review', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete.png';\" /></a>";
                    
                    $gridAction .= $arm_community_setting->arm_com_get_confirm_box($review->arm_review_id, __("Are you sure you want to delete this review?", ARM_COMMUNITY_TEXTDOMAIN), 'arm_com_review_delete_btn', '', __('Delete', ARM_COMMUNITY_TEXTDOMAIN), __('Cancel', ARM_COMMUNITY_TEXTDOMAIN));
                    
                    $gridAction .= "</div>";

                    $grid_data[$ai][7] = $gridAction;

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

        function arm_com_review_remove_by_admin() {
            if (isset($_POST['review_id']) && $_POST['review_id'] > 0) {
                if (is_admin()) {
                    $review_id = $_POST['review_id'];
                    global $arm_community_features, $wpdb, $arm_community_features, $ARMember;

                    if(method_exists($ARMember, 'arm_check_user_cap')){
                        $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                        $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
                    }
                    $wpdb->get_results($wpdb->prepare("DELETE FROM `" . $arm_community_features->tbl_arm_com_review . "` WHERE arm_review_id=%d", $review_id));
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

        function arm_com_review_bulk_action() {
            if (!isset($_POST)) {
                return;
            }

            global $wpdb, $arm_global_settings, $arm_community_features, $ARMember;

            if(method_exists($ARMember, 'arm_check_user_cap')){
                $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
            }
            $ids = $arm_global_settings->get_param('item-action', '');

            if (empty($ids)) {
                $errors[] = __('Please select one or more records.', ARM_COMMUNITY_TEXTDOMAIN);
            }
            else {

                $bulkaction = $arm_global_settings->get_param('action1');

                if ( $bulkaction == '' || $bulkaction == '-1' ) {
                    $errors[] = __('Please select valid action.', ARM_COMMUNITY_TEXTDOMAIN);
                }
                else {
                    if (!is_array($ids)) {
                        $ids = explode(',', $ids);
                    }

                    if (!current_user_can('arm_community_activity')) {
                        $errors[] = __('Sorry, You do not have permission to perform this action', ARM_COMMUNITY_TEXTDOMAIN);
                    }
                    else {
                        if (is_array($ids)) {
                            $arm_action_type = $_POST["arm_action_type"];
                            if($arm_action_type == "delete_review")
                            {
                                foreach ($ids as $id) {
                                    $wpdb->get_results($wpdb->prepare("DELETE FROM `" . $arm_community_features->tbl_arm_com_review . "` WHERE arm_review_id=%d", $id));
                                }
                                $message = __('Review(s) has been deleted successfully.', ARM_COMMUNITY_TEXTDOMAIN);
                            }
                            else {
                                if($arm_action_type == "approve_review") {
                                    $approved_val = 1;
                                    $message = __('Review(s) has been approved successfully.', ARM_COMMUNITY_TEXTDOMAIN);
                                }
                                else {
                                    $approved_val = 0;
                                    $message = __('Review(s) has been disapproved successfully.', ARM_COMMUNITY_TEXTDOMAIN);
                                }
                                foreach ($ids as $id) {
                                    $wpdb->update($arm_community_features->tbl_arm_com_review, array('arm_approved' => $approved_val), array('arm_review_id' => $id), array('%d'), array('%d'));
                                }
                            }
                            $return_array = array('type' => 'success', 'msg' => $message);
                        }
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

        function arm_com_review_approve() {
            $review_id = $_POST["review_id"];
            if (!empty($review_id)) {
                /*if (!current_user_can('arm_community_activity')) {
                    $errors[] = __('Sorry, You do not have permission to perform this action', ARM_COMMUNITY_TEXTDOMAIN);
                }
                else {*/
                    global $wpdb, $arm_global_settings, $arm_community_features, $ARMember;

                    if(method_exists($ARMember, 'arm_check_user_cap')){
                        $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                        $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
                    }
                    $wpdb->update($arm_community_features->tbl_arm_com_review, array('arm_approved' => 1), array('arm_review_id' => $review_id), array('%d'), array('%d'));
                    $message = __('Review(s) has been approved successfully.', ARM_COMMUNITY_TEXTDOMAIN);
                    $return_array = array('type' => 'success', 'msg' => $message);
                //}
            }
            else {
                $errors[] = __('Please select one or more records.', ARM_COMMUNITY_TEXTDOMAIN);
            }

            if (!isset($return_array)) {
                $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
                $ARMember->arm_set_message('success', $message);
            }
            echo json_encode($return_array);
            die;
        }

        function arm_com_review_disapprove() {
            $review_id = $_POST["review_id"];
            if (!empty($review_id)) {
                /*if (!current_user_can('arm_community_activity')) {
                    $errors[] = __('Sorry, You do not have permission to perform this action', ARM_COMMUNITY_TEXTDOMAIN);
                }
                else {*/
                    global $wpdb, $arm_global_settings, $arm_community_features, $ARMember;

                    if(method_exists($ARMember, 'arm_check_user_cap')){
                        $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                        $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
                    }
                    $wpdb->update($arm_community_features->tbl_arm_com_review, array('arm_approved' => 0), array('arm_review_id' => $review_id), array('%d'), array('%d'));
                    $message = __('Review(s) has been disapproved successfully.', ARM_COMMUNITY_TEXTDOMAIN);
                    $return_array = array('type' => 'success', 'msg' => $message);
                //}
            }
            else {
                $errors[] = __('Please select one or more records.', ARM_COMMUNITY_TEXTDOMAIN);
            }

            if (!isset($return_array)) {
                $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
                $ARMember->arm_set_message('success', $message);
            }
            echo json_encode($return_array);
            die;
        }

        function arm_com_review_edit_by_admin_func() {
            $message = __('Something went wrong. Try after sometime.', ARM_COMMUNITY_TEXTDOMAIN);
            $return_array = array('error' => 'success', 'msg' => $message);
            $posted_data = $_POST;
            $review_id = isset($posted_data['arm_com_review_edit_id']) ? $posted_data['arm_com_review_edit_id'] : 0;
            if(is_user_logged_in() && current_user_can('arm_community_activity') && current_user_can('administrator') && !empty($review_id)) {
                global $arm_community_features, $wpdb, $ARMember;
                
                if(method_exists($ARMember, 'arm_check_user_cap')){
                    $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                    $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
                }
                $arm_com_review_rating = isset($posted_data['arm_popup_rating']) ? $posted_data['arm_popup_rating'] : 0;
                $arm_com_review_title = isset($posted_data['arm_title']) ? $posted_data['arm_title'] : '';
                $arm_com_review_description = isset($posted_data['arm_description']) ? $posted_data['arm_description'] : '';

                $edit_data = array(
                    'arm_rating' => $arm_com_review_rating,
                    'arm_title' => $arm_com_review_title,
                    'arm_description' => $arm_com_review_description,
                );

                $wpdb->update($arm_community_features->tbl_arm_com_review, $edit_data, array('arm_review_id' => $review_id), array('%f', '%s', '%s'), array('%d'));

                $message = __('Review has been edited successfully.', ARM_COMMUNITY_TEXTDOMAIN);
                $return_array = array('type' => 'success', 'msg' => $message);
            }
            echo json_encode($return_array);
            die;
        }
    }
}
global $arm_community_review;
$arm_community_review = new ARM_Community_Review();