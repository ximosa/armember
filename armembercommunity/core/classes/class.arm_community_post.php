<?php
if (!class_exists('ARM_Community_Post')) {
	class ARM_Community_Post {
		function __construct() {
			add_shortcode('arm_community_add_post', array(&$this, 'arm_community_add_post_func'));
			add_shortcode('arm_community_display_post', array(&$this, 'arm_community_display_post_func'));
			add_shortcode('arm_community_display_wall_post', array(&$this, 'arm_community_display_wall_post_func'));
			add_action('init', array(&$this, 'arm_com_custom_post_type'));
			add_action('wp_ajax_arm_com_post_list', array(&$this, 'arm_com_post_list'));
			add_action('wp_ajax_arm_com_post_remove_by_admin', array(&$this, 'arm_com_post_remove_by_admin'));
			add_action('wp_ajax_arm_com_post_bulk_action', array(&$this, 'arm_com_post_bulk_action'));
			add_action('wp_ajax_arm_com_post_add', array(&$this, 'arm_com_post_add'));
			add_action('wp_ajax_arm_com_post_remove', array(&$this, 'arm_com_post_remove'));
			add_action('wp_ajax_arm_com_post_like', array(&$this, 'arm_com_post_like_func'));
			add_action('wp_ajax_arm_com_post_unlike', array(&$this, 'arm_com_post_unlike_func'));
			add_action('wp_ajax_arm_community_posts_display_front', array(&$this, 'arm_community_display_post_func'));
			add_action('wp_ajax_arm_community_comment_add', array(&$this, 'arm_community_comment_add'));
			add_action('wp_ajax_arm_get_post_comments_front', array(&$this, 'arm_get_post_comments'));
			add_action('delete_user', array(&$this, 'arm_com_post_delete_user_data'));
			add_action('wp_ajax_arm_com_post_comment_remove', array(&$this, 'arm_com_post_comment_remove'));
			add_action('wp_ajax_arm_community_display_wall_post_front', array(&$this, 'arm_community_display_wall_post_func'));
			add_action('wp_ajax_arm_com_post_comment_list', array(&$this, 'arm_com_post_comment_list'));
			add_action('wp_ajax_arm_community_get_single_post', array(&$this, 'arm_community_get_single_post_func'));
			add_action('wp_ajax_arm_com_post_comment_remove_by_admin', array(&$this, 'arm_com_post_comment_remove_by_admin_func'));
            add_action('wp_ajax_arm_com_change_comment_status', array(&$this, 'arm_com_change_comment_status_func'));

            add_action('admin_footer',array(&$this,'arm_com_rewrite_rules_for_custom_post'),100);
            add_filter( 'generate_rewrite_rules', array(&$this,'arm_com_generate_rewrite_rules'),10, 1 );

		}

		function arm_com_post_allow() {
			global $arm_community_setting;
			$arm_com_settings = $arm_community_setting->arm_com_settings;
			return (isset($arm_com_settings['arm_com_post']) && $arm_com_settings['arm_com_post'] == '1') ? true : false;
		}

		function arm_com_post_wall_allow() {
			global $arm_community_setting;
			$arm_com_settings = $arm_community_setting->arm_com_settings;
			return (isset($arm_com_settings['arm_com_post_wall']) && $arm_com_settings['arm_com_post_wall'] == '1') ? true : false;
		}

        function arm_com_post_list() {
            global $wpdb, $arm_global_settings, $arm_community_setting, $arm_community_features, $ARMember;
            $date_format = $arm_global_settings->arm_get_wp_date_time_format();
            if(method_exists($ARMember, 'arm_check_user_cap')){
                $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
            }
            $ai = 0;
            $grid_columns = array(
                'post_title' => __('Post Title', ARM_COMMUNITY_TEXTDOMAIN),
                'post_content' => __('Post Content', ARM_COMMUNITY_TEXTDOMAIN),
                'post_by' => __('Post By', ARM_COMMUNITY_TEXTDOMAIN),
                'date' => __('Date', ARM_COMMUNITY_TEXTDOMAIN)
            );

            $sorting_ord = !empty($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
            $sorting_col = !empty($_REQUEST['iSortCol_0']) ? $_REQUEST['iSortCol_0'] : 1;
            $search_term = !empty($_REQUEST["com_post_search_term"]) ? $_REQUEST["com_post_search_term"] : '';
            $start_data = !empty($_REQUEST["com_post_search_start_data"]) ? $_REQUEST["com_post_search_start_data"] : '';
            $end_data = !empty($_REQUEST["com_post_search_end_data"]) ? $_REQUEST["com_post_search_end_data"] : '';
            $order_by = 'post_date';

            $where_flag = "WHERE com_post.post_type = 'arm_community' AND com_post.post_status = 'publish'";

            if( !empty($search_term) ) {
                $where_flag .= " AND (com_post.post_title LIKE '%{$search_term}%' OR com_user.user_login LIKE '%{$search_term}%')";
            }

            if( !empty($start_data) ) {
                $start_data = date("Y-m-d", strtotime($start_data));
                $where_flag .= " AND com_post.post_date >= '$start_data'";
            }

            if( !empty($end_data) ) {
                $end_data = date("Y-m-d", strtotime("+1 day", strtotime($end_data)));
                $where_flag .= " AND com_post.post_date < '$end_data'";
            }

            $post_table = $wpdb->posts;
            $user_table = $wpdb->users;
            $tmp_query = "SELECT com_post.* FROM `{$post_table}` com_post LEFT JOIN `{$user_table}` com_user ON com_post.post_author = com_user.ID ".$where_flag." ORDER BY {$order_by} {$sorting_ord}";

            $arm_posts = $wpdb->get_results($tmp_query);
            $total_before_filter = count($arm_posts);
            $total_after_filter = count($arm_posts);

            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            $tmp_query = $tmp_query . " LIMIT {$offset},{$number}";
            $arm_posts = $wpdb->get_results($tmp_query);

            $grid_data = array();
            if (is_array($arm_posts) && count($arm_posts) > 0) {
                foreach ($arm_posts as $arm_post) {
                    $post_id = $arm_post->ID;
                    $user_id = $arm_post->post_author;
                    $post_title = $arm_post->post_title;
                    $post_content = $arm_post->post_content;
                    $post_date = $arm_post->post_date;

                    $user = '';
                    if (is_numeric($user_id) && !empty($user_id)) {
                        $user = get_user_by('id', $user_id);
                    }

                    $grid_data[$ai][0] = "<input id=\"cb-item-action-{$post_id}\" class=\"chkstanard\" type=\"checkbox\" value=\"{$post_id}\" name=\"item-action[]\">";
                    $grid_data[$ai][1] = $post_title;
                    $grid_data[$ai][3] = $user->display_name;
                    $grid_data[$ai][2] = nl2br(stripslashes($post_content));
                    $grid_data[$ai][4] = date($date_format, strtotime($post_date));

                    $gridAction = "<div class='arm_grid_action_btn_container'>";

                    $gridAction .= "<a href='javascript:void(0)' class='arm_com_post_comment_view_nav' data-post_id=".$post_id."><img src='" . ARM_COMMUNITY_IMAGES_URL . "/comment_icon.png' class='armhelptip' title='" . __('View Comments', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/comment_icon_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/comment_icon.png';\" /></a>";
                 
                    $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$post_id});'><img src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete.png' class='armhelptip' title='" . __('Delete', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete.png';\" /></a>";

                    $gridAction .= $arm_community_setting->arm_com_get_confirm_box($post_id, __("Are you sure you want to delete this post?", ARM_COMMUNITY_TEXTDOMAIN), 'arm_com_post_delete_btn', '', __('Delete', ARM_COMMUNITY_TEXTDOMAIN), __('Cancel', ARM_COMMUNITY_TEXTDOMAIN));
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

        function arm_com_post_remove_by_admin() {
            global $ARMember, $arm_community_features;

            if(method_exists($ARMember, 'arm_check_user_cap')){
                $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
            }
            if (isset($_POST['post_id']) && $_POST['post_id'] > 0) {
                if (is_admin()) {
                    wp_delete_post($_POST['post_id']);
                    $response = array('type' => 'success', 'msg' => __('Record is deleted successfully.', ARM_COMMUNITY_TEXTDOMAIN));
                }
                else {
                    $response = array('type' => 'failed', 'msg' => __('Sorry, You do not have permission to perform this action.', ARM_COMMUNITY_TEXTDOMAIN));
                }
            }
            else {
                $response = array('type' => 'failed', 'msg' => __('Invalid action.', ARM_COMMUNITY_TEXTDOMAIN));
            }
            echo json_encode($response);
            die;
        }

        function arm_com_post_bulk_action() {
            global $wpdb, $ARMember, $arm_members_class, $arm_global_settings, $arm_subscription_plans, $arm_community_features;
            if (!isset($_POST)) {
                return;
            }
            
            if(method_exists($ARMember, 'arm_check_user_cap')){
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
                                wp_delete_post($id);
                            }
                            $message = __('Post(s) has been deleted successfully.', ARM_COMMUNITY_TEXTDOMAIN);
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

        function arm_com_custom_post_type() {

            $labels = array(
                'name' => _x('User Posts', ARM_COMMUNITY_TEXTDOMAIN),
                'singular_name' => _x('User Post', ARM_COMMUNITY_TEXTDOMAIN),
                'menu_name' => __('User Posts', ARM_COMMUNITY_TEXTDOMAIN),
                'parent_item_colon' => __('Parent User Posts', ARM_COMMUNITY_TEXTDOMAIN),
                'all_items' => __('All User Posts', ARM_COMMUNITY_TEXTDOMAIN),
                'view_item' => __('View User Post', ARM_COMMUNITY_TEXTDOMAIN),
                'add_new_item' => __('Add New Post', ARM_COMMUNITY_TEXTDOMAIN),
                'add_new' => __('Add New', ARM_COMMUNITY_TEXTDOMAIN),
                'edit_item' => __('Edit Post', ARM_COMMUNITY_TEXTDOMAIN),
                'update_item' => __('Update Post', ARM_COMMUNITY_TEXTDOMAIN),
                'search_items' => __('Search Post', ARM_COMMUNITY_TEXTDOMAIN),
                'not_found' => __('Not Found', ARM_COMMUNITY_TEXTDOMAIN),
                'not_found_in_trash' => __('Not found in Trash', ARM_COMMUNITY_TEXTDOMAIN),
            );

            global $arm_community_setting;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $arm_com_post_slug = isset($arm_com_settings['arm_com_post_slug']) ? $arm_com_settings['arm_com_post_slug'] : 'arm_community';

            $args = array(
                'label' => __('user_posts', ARM_COMMUNITY_TEXTDOMAIN),
                'description' => __('Users Posts', ARM_COMMUNITY_TEXTDOMAIN),
                'labels' => $labels,
                // Features this CPT supports in Post Editor
                'supports' => array('title', 'editor', 'author', 'thumbnail', 'comments', 'revisions'),
                // You can associate this CPT with a taxonomy or custom taxonomy. 
                'taxonomies' => array(),
                // A hierarchical CPT is like Pages and can have Parent and child items. A non-hierarchical CPT is like Posts.
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => false,
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                'menu_position' => 50,
                'can_export' => true,
                'has_archive' => true,
                'exclude_from_search' => false,
                'publicly_queryable' => true,
                'capability_type' => 'page',
                'rewrite' => array(
                    'slug' => $arm_com_post_slug
                )
            );

            register_post_type('arm_community', $args);
            //$args = get_post_type_object('arm_community');
            //$args->rewrite["slug"] = $arm_com_post_slug;
            //register_post_type($args->name, $args);
        }

        function arm_com_set_session_for_permalink(){
            global $ARMember;
            $ARMember->arm_session_start();
            $_SESSION['arm_com_post_permalink_is_changed'] = true;
        }

        function arm_com_rewrite_rules_for_custom_post($unset_flag){
            global $wp, $wpdb, $wp_rewrite, $ARMember, $arm_community_setting;
            $ARMember->arm_session_start();
            if( isset($_SESSION['arm_com_post_permalink_is_changed']) && $_SESSION['arm_com_post_permalink_is_changed'] == true )
            {
                $arm_com_settings = $arm_community_setting->arm_com_settings;
                $profileSlug = isset($arm_com_settings['arm_com_post_slug']) ? $arm_com_settings['arm_com_post_slug'] : 'arm_community';
                if (!empty($profileSlug)) {
                    add_rewrite_rule($profileSlug . '/?$', 'index.php?post_type=arm_community' , 'top');
                    flush_rewrite_rules(false);
                    $wp_rewrite->flush_rules(false);
                    if($unset_flag!=1)
                    {
                        unset($_SESSION['arm_com_post_permalink_is_changed']);
                    }
                }
            }
        }

        /**
         * Add rewrite tags and rules
         */
        function arm_com_generate_rewrite_rules($wp_rewrite) {

            global $wp, $wpdb, $wp_rewrite, $ARMember, $arm_community_setting;

            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $profileSlug = isset($arm_com_settings['arm_com_post_slug']) ? $arm_com_settings['arm_com_post_slug'] : 'arm_community';

            $feed_rules = array(
                //$profileSlug.'/([^/]+)/?$' => 'index.php?page_id=' . $profile_page_id . '&arm_user=$matches[1]',
                $profileSlug.'/?$' => 'index.php?post_type=arm_community',
            );

            $wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
            return $wp_rewrite;
        }

        function arm_community_add_post_func($atts = array(), $content = array(), $tag = '') {
            global $wpdb, $arm_community_setting, $arm_community_features;
            $user_data = $arm_community_setting->arm_com_profile_get_user_id();
            $user_data_arr = array_shift($user_data);
            $user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0;
            $args = shortcode_atts(array('user_id' => $user_id,), $atts, $tag);
            if (is_user_logged_in() && $this->arm_com_post_allow() && !empty($args['user_id'])) {
                $user_id = get_current_user_id();
                if ($user_id == $args['user_id']) {
                    $content .= $this->arm_com_post_form($args['user_id']);
                }
            }
            return $content;
        }

        function arm_com_post_form($usre_id) {
            global $arm_community_setting;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $fimage_content = $post_type_btn = $arm_form = "";
            if(!empty($arm_com_settings['arm_com_post_fimage'])) {
                $post_type_btn .= "<div class='arm_com_post_added_type'>";
                $post_type_btn .= "<button type='button' class='arm_com_post_added_type_btn' data-arm_post_type='status'>Status</button>";
                $post_type_btn .= "<button type='button' class='arm_com_post_added_type_btn arm_post_type_btn_active' data-arm_post_type='photo'>Photo</button>";
                $post_type_btn .= "<input type='hidden' name='arm_com_post_added_type' id='arm_com_post_added_type' value='photo'>";
                $post_type_btn .= "<input type='hidden' name='arm_com_post_mode' id='arm_com_post_mode' value='add'>";
                $post_type_btn .= "<input type='hidden' name='arm_com_post_id' id='arm_com_post_id'>";
                $post_type_btn .= "</div>";

                $fimage_content .= "<img src='' class='arm_post_attachment_thumb'>";
                $fimage_content .= "<button type='button' class='arm_com_post_fimage_btn' id='arm_com_post_fimage_btn'>".__("Upload Attachment", ARM_COMMUNITY_TEXTDOMAIN)."</button>";

                $fimage_content .= "<button type='button' class='arm_com_post_fimage_remove_btn' id='arm_com_post_fimage_remove_btn'>".__("Remove Attachment", ARM_COMMUNITY_TEXTDOMAIN)."</button>";

                $fimage_content .= "<input type='file' name='arm_com_post_featur_image' id='arm_com_post_featur_image' class='arm_com_post_featur_image'>";
            }
            if (!$arm_community_setting->arm_com_is_profile_editor()) {
                $arm_form .= '<form method="post" action="#" id="arm_com_post_form" name="arm_com_post_form" class="arm_admin_form arm_com_post_form" enctype="multipart/form-data">';
            }
            
            $arm_form = '<div class="arm_post_form_container">';
            $arm_form .= '<div class="arm_post_title">';
            $arm_form .= '<div class="arm_com_post_user_img">';
            $arm_form .= get_avatar($usre_id, '50');
            $arm_form .= '</div>';
            $arm_form .= '<div class="arm_com_post_user_title_box">';
            $arm_form .= '<input type="text" name="arm_title" id="arm_title" value="" class="arm_title" placeholder="Post Title" />';
            $arm_form .= $post_type_btn;
            $arm_form .= '</div>';
            $arm_form .= '</div>';
            $arm_form .= '<div class="arm_com_post_description">';
            $arm_form .= '<textarea name="arm_description" id="arm_description" class="arm_description" rows="5" placeholder="Post Content...."></textarea>';
            $arm_form .= '</div>';
            $arm_form .= '<div class="arm_com_post_button_wrapper">';
            $arm_form .= $fimage_content;

            /* add post button */
            $arm_form .= '<button type="button" id="arm_com_post_btn" class="arm_com_post_btn" name="arm_com_post_btn">';
            $arm_form .= __('Post', ARM_COMMUNITY_TEXTDOMAIN);
            $arm_form .= '</button>';
            /* add post button over */

            /* cancel edit post nav */
            $arm_form .= '<span class="arm_com_post_edit_cancel_nav">'.__('Cancel', ARM_COMMUNITY_TEXTDOMAIN).'</span>';
            /* cancel edit post nav over */

            /* edit post button */
            $arm_form .= '<button type="button" id="arm_com_post_edit_btn" class="arm_com_post_edit_btn" name="arm_com_post_edit_btn">';
            $arm_form .= __('Edit', ARM_COMMUNITY_TEXTDOMAIN);
            $arm_form .= '</button>';
            /* edit post button over */

            $arm_form .= '<span class="arm_spinner arm_com_post_form_loader"><svg version="1.1" id="arm_form_loader" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="18px" height="18px" viewBox="0 0 26.349 26.35" style="enable-background:new 0 0 26.349 26.35;" xml:space="preserve" ><g><g><circle cx="13.792" cy="3.082" r="3.082" /><circle cx="13.792" cy="24.501" r="1.849"/><circle cx="6.219" cy="6.218" r="2.774"/><circle cx="21.365" cy="21.363" r="1.541"/><circle cx="3.082" cy="13.792" r="2.465"/><circle cx="24.501" cy="13.791" r="1.232"/><path d="M4.694,19.84c-0.843,0.843-0.843,2.207,0,3.05c0.842,0.843,2.208,0.843,3.05,0c0.843-0.843,0.843-2.207,0-3.05 C6.902,18.996,5.537,18.988,4.694,19.84z"/><circle cx="21.364" cy="6.218" r="0.924"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg></span>';

            $arm_form .= '<div class="arm_com_post_desc_error">' . __('Please enter post description.', ARM_COMMUNITY_TEXTDOMAIN) . '</div>';
            $arm_form .= '<div class="arm_com_post_title_error">' . __('Please enter post title.', ARM_COMMUNITY_TEXTDOMAIN) . '</div>';
            $arm_form .= '<div class="arm_post_add_success"></div>';
            $arm_form .= '<div class="arm_post_add_error"></div>';
            $arm_form .= '</div>';
            $arm_form .= '</div>';
            if (!$arm_community_setting->arm_com_is_profile_editor()) {
                $arm_form .= '</form>';
            }
            return $arm_form;
        }

        function arm_community_display_post_func($atts = array(), $content = array(), $tag = '') {
            global $wpdb, $arm_community_setting, $arm_community_features;
            if ($arm_community_setting->arm_com_is_profile_editor()) {
                $user_id = 0;
            } else {
                $user_data = $arm_community_setting->arm_com_profile_get_user_id();
                $user_data_arr = array_shift($user_data);
                $user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0;
            }
            $args = shortcode_atts(array('user_id' => $user_id,), $atts, $tag);
            if ($this->arm_com_post_allow()) {
                $user_id = get_current_user_id();
                $arm_com_settings = $arm_community_setting->arm_com_settings;
                $arm_post_section_lbl = !empty($arm_com_settings['arm_post_section_lbl']) ? $arm_com_settings['arm_post_section_lbl'] : __("Post", ARM_COMMUNITY_TEXTDOMAIN);

                $arm_com_post_remove_msg = __("Are you sure you want to delete this", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_post_section_lbl . " " . __("?", ARM_COMMUNITY_TEXTDOMAIN);

                $content .= '<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Noto Sans" />';
                $content .= '<div class="arm_com_post_display_container" id="arm_com_post_display_container">';
                $content .= '<div class="arm_com_post_wrapper">';
                $content .= "<div class='arm_template_loading'><img src='".MEMBERSHIP_IMAGES_URL."/loader_template.gif' alt='".__('Loading',ARM_COMMUNITY_TEXTDOMAIN)."..' /></div>";

                $content .= "<div class='arm_com_post_remove_confirm_box_div'>".$arm_community_setting->arm_com_get_confirm_box(0, $arm_com_post_remove_msg, 'arm_com_post_remove_confirm_box', '', __('Delete', ARM_COMMUNITY_TEXTDOMAIN), __('Cancel', ARM_COMMUNITY_TEXTDOMAIN))."</div>";

                $content .= "<div class='arm_com_post_comment_remove_confirm_box_div'>".$arm_community_setting->arm_com_get_confirm_box(0, __("Are you sure you want to delete this comment ?", ARM_COMMUNITY_TEXTDOMAIN), 'arm_com_post_comment_remove_confirm_box', '', __('Delete', ARM_COMMUNITY_TEXTDOMAIN), __('Cancel', ARM_COMMUNITY_TEXTDOMAIN))."</div>";

                $content .= $this->arm_com_post_boxes($args['user_id']);
                $content .= '<div id="arm_com_post_display_loader" class="arm_com_post_display_loader">';
                $content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . '/arm_loader.gif" /></div>';
                $content .= '</div>';
                $content .= '</div>';
            }
            return $content;
        }

        function arm_com_post_boxes($user_id) {
            global $wpdb, $arm_community_setting, $arm_global_settings;
            $arm_loggedin_user_id = $arm_posts = $paging = "";
            $pageno = !empty($_REQUEST["pageno"]) ? $_REQUEST["pageno"] : 1;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $arm_com_post_like = isset($arm_com_settings["arm_com_post_like"]) ? $arm_com_settings["arm_com_post_like"] : 0;
            $arm_com_post_comment = isset($arm_com_settings["arm_com_post_comment"]) ? $arm_com_settings["arm_com_post_comment"] : 0;
            $records_per_page = isset($arm_com_settings["arm_record_per_page"]) ? $arm_com_settings["arm_record_per_page"] : 10;
            $offset = ( $pageno - 1 ) * $records_per_page;
            
            if (is_user_logged_in()) {
                $arm_loggedin_user_id = get_current_user_id();
            }

            if ($arm_community_setting->arm_com_is_profile_editor()) {
                $posts_array = array();
                $posts_array[0] = new stdClass();
                $posts_array[0]->ID = 1;
                $posts_array[0]->post_type = "arm_community";
                $posts_array[0]->post_author = $arm_loggedin_user_id;
                $posts_array[0]->post_date = current_time('mysql');
                $posts_array[0]->post_title = __('This is my first title', ARM_COMMUNITY_TEXTDOMAIN);
                $posts_array[0]->post_content = __('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.', ARM_COMMUNITY_TEXTDOMAIN);

                $posts_array[1] = new stdClass();
                $posts_array[1]->ID = 1;
                $posts_array[1]->post_type = "arm_community";
                $posts_array[1]->post_author = $arm_loggedin_user_id;
                $posts_array[1]->post_date = current_time('mysql');
                $posts_array[1]->post_title = __('This is my second title', ARM_COMMUNITY_TEXTDOMAIN);
                $posts_array[1]->post_content = __('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.', ARM_COMMUNITY_TEXTDOMAIN);
            }
            else {
                $args = array (
                    'posts_per_page' => $records_per_page,
                    'offset' => $offset,
                    'category' => '',
                    'category_name' => '',
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'include' => '',
                    'exclude' => '',
                    'meta_key' => '',
                    'meta_value' => '',
                    'post_type' => 'arm_community',
                    'post_mime_type' => '',
                    'post_parent' => '',
                    'author' => $user_id,
                    'author_name' => '',
                    'post_status' => 'publish',
                    'suppress_filters' => true
                );

                $posts_array = get_posts($args);
                $total_posts = count_user_posts( $user_id , "arm_community");
                if( $total_posts > $records_per_page ) {
                    $com_pagination_style = isset($arm_com_settings["arm_com_pagination_style"]) ? $arm_com_settings["arm_com_pagination_style"] : 'numeric';
                    if($com_pagination_style == "numeric") {
                        $paging = $arm_global_settings->arm_get_paging_links($pageno, $total_posts, $records_per_page, 'posts');
                    }
                    else {
                        $total_pages = ceil( $total_posts / $records_per_page );
                        $more_link_cnt = '<a class="arm_com_posts_load_more_link arm_page_numbers" href="javascript:void(0)" data-page="' . ($pageno + 1) . '" data-type="posts" data-arm_ttl_page="'.$total_pages.'">' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '</a>';
                        $more_link_cnt .= '<img class="arm_load_more_loader" src="'.MEMBERSHIP_IMAGES_URL.'/arm_loader.gif" alt="' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '" style="display:none;">';
                        $paging .= '<div class="arm_com_posts_paging_container arm_com_posts_paging_container_infinite">' . $more_link_cnt . '</div>';
                    }
                }
            }

            if (!empty($posts_array)) {
                $arm_posts_tmp = "";
                global $arm_community_setting;
                foreach ($posts_array as $post) {
                    $post_id = $post->ID;
                    $post_author = $post->post_author;
                    $author_data = get_user_by('ID', $post_author);
                    $post_date = $this->arm_com_post_time_diff($post->post_date);
                    $post_title = $post->post_title;
                    $post_content = $post->post_content;
                    $comment_count = !empty($post->comment_count) ? $post->comment_count : 0;
                    $cmnt_lbl = ($comment_count > 1) ? __('Comments', ARM_COMMUNITY_TEXTDOMAIN) : __('Comment', ARM_COMMUNITY_TEXTDOMAIN);
                    $comment_count = $arm_community_setting->arm_com_short_number_format($comment_count);

                    $arm_like_results = $wpdb->get_results("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key LIKE 'arm_com_post_like_%' AND meta_value != 0 AND post_id = ".$post_id." ",ARRAY_A);
                    $like_count = count($arm_like_results);

                    $like_count = (empty($like_count)) ? 0 : $like_count;
                    $like_lbl = ($like_count > 1) ? __("Likes", ARM_COMMUNITY_TEXTDOMAIN) : __("Like", ARM_COMMUNITY_TEXTDOMAIN);
                    $like_count = $arm_community_setting->arm_com_short_number_format($like_count);

                    $post_content_class = "arm_box_width_100";

                    $arm_posts_tmp .= '<div class="arm_com_post_box arm_com_posts_box arm_com_post_box_' . $post_id . '">';

                    if( $post_author == $arm_loggedin_user_id ) {
                        $arm_posts_tmp .= '<img src="'.ARM_COMMUNITY_IMAGES_URL.'/close.png" class="arm_com_post_remove" data-post_id="'.$post_id.'" />';

                        $arm_posts_tmp .= '<span class="arm_com_post_edit" data-post_id="'.$post_id.'"><img src="'.ARM_COMMUNITY_IMAGES_URL.'/edit.png" /></span>';
                    }

                    if (has_post_thumbnail($post->ID)) {
                        $post_content_class = "arm_box_width_60";
                        $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'single-post-thumbnail');
                        $arm_posts_tmp .= "<div class='arm_com_post_image_box' style='background-image: url(".$image[0].")'></div>";
                    }

                    $arm_posts_tmp .= '<div class="arm_com_post_content_box '.$post_content_class.'">';
                    $arm_posts_tmp .= '<div class="arm_com_post_content_box_wrapper">';
                    $arm_posts_tmp .= '<p class="arm_com_post_title">'.$post_title.'</p>';
                    
                    $arm_posts_tmp .= '<p class="arm_com_post_meta"><span>'.$post_date.'</span> | <span>'.$cmnt_lbl.' </span><span class="arm_post_total_comments">'.$comment_count.'</span> | <span>'.$like_lbl.' </span><span class="arm_post_total_likes">'.$like_count.'</span></p>';

                    $arm_posts_tmp .= '<p class="arm_com_post_content">';
                    $arm_posts_tmp .= stripslashes(substr($post_content, 0, 160));
                    if(strlen($post_content) > 160) {$arm_posts_tmp .= ' ...';}
                    $arm_posts_tmp .= '</p>';

                    if( $arm_community_setting->arm_com_is_profile_editor() ) {
                        $post_parmalink = "#";
                    }
                    else {
                        $post_parmalink = get_post_permalink($post->ID);
                    }

                    $arm_posts_tmp .= '<a href="'.$post_parmalink.'" class="arm_com_post_read_more_nav">'.__('Read More', ARM_COMMUNITY_TEXTDOMAIN).'</a>';
                    $arm_posts_tmp .= '</div>'; 

                    if(is_user_logged_in() && (!empty($arm_com_post_like) || !empty($arm_com_post_comment)) ) {
                        $arm_posts_tmp .= '<div class="arm_com_post_action_section">';
                        if(!empty($arm_com_post_like)) {
                            if (get_post_meta($post->ID, 'arm_com_post_like_' . $arm_loggedin_user_id)) {
                                $arm_posts_tmp .= $this->arm_com_post_get_unlike_button($post->ID);
                            }
                            else {
                                $arm_posts_tmp .= $this->arm_com_post_get_like_button($post->ID);
                            }
                        }
                        if(!empty($arm_com_post_comment)) {
                            $arm_posts_tmp .= '<a href="javascript:void(0);" class="arm_com_post_comment_nav" data-post_id="'.$post->ID.'"><img src="'.ARM_COMMUNITY_IMAGES_URL.'/comment.png" />Comment</a>';
                        }
                        $arm_posts_tmp .= '</div>'; 
                    }
                    $arm_posts_tmp .= '</div>'; 
                    $arm_posts_tmp .= '</div>'; 
                    $arm_posts_tmp .= "<div class='arm_com_post_comment_box arm_com_post_comment_box_".$post->ID."'></div>";
                    $arm_posts_tmp .= $this->arm_get_post_comments($post->ID, $records_per_page);
                }
                $arm_posts .= "<div class='arm_com_post_box_wrapper'>" . $arm_posts_tmp . "</div>";
                if(!empty($paging)) {
                    $arm_posts .= "<div class='arm_posts_paging_div'>".$paging."</div>";
                }
            }
            else {
                $arm_com_settings = $arm_community_setting->arm_com_settings;
                $arm_post_text = isset($arm_com_settings["arm_post_section_lbl"]) ? $arm_com_settings["arm_post_section_lbl"] : __('Post', ARM_COMMUNITY_TEXTDOMAIN);
                $arm_posts .= '<div class="arm_com_post_no_msg">';
                $arm_posts .= __("No", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_post_text . " " . __("found.", ARM_COMMUNITY_TEXTDOMAIN);
                $arm_posts .= '</div>';
            }
            if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "arm_community_posts_display_front" ) {
                $response = array("content" => $arm_posts_tmp, "paging" => $paging);
                echo json_encode($response);
                exit;
            }
            else {
                $arm_posts .= "<div class='arm_com_post_comment_box_wrapper' id='arm_com_post_comment_box_wrapper'>";
                $arm_posts .= "<div class='arm_com_post_comment_section'>";
                $arm_posts .= get_avatar($arm_loggedin_user_id, '50');
                $arm_posts .= "<textarea name='arm_com_post_comment' class='arm_com_post_comment' rows='4' placeholder='Write a comment'></textarea>";
                $arm_posts .= "<span class='arm_com_post_comment_error'>".__("Please enter comment", ARM_COMMUNITY_TEXTDOMAIN)."</span>";
                $arm_posts .= "<p class='arm_com_post_comment_msg'>".__("Comment added successfully.", ARM_COMMUNITY_TEXTDOMAIN)."</p>";
                $arm_posts .= '<span class="arm_spinner arm_com_post_comment_loader"><svg version="1.1" id="arm_form_loader" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="18px" height="18px" viewBox="0 0 26.349 26.35" style="enable-background:new 0 0 26.349 26.35;" xml:space="preserve" ><g><g><circle cx="13.792" cy="3.082" r="3.082" /><circle cx="13.792" cy="24.501" r="1.849"/><circle cx="6.219" cy="6.218" r="2.774"/><circle cx="21.365" cy="21.363" r="1.541"/><circle cx="3.082" cy="13.792" r="2.465"/><circle cx="24.501" cy="13.791" r="1.232"/><path d="M4.694,19.84c-0.843,0.843-0.843,2.207,0,3.05c0.842,0.843,2.208,0.843,3.05,0c0.843-0.843,0.843-2.207,0-3.05 C6.902,18.996,5.537,18.988,4.694,19.84z"/><circle cx="21.364" cy="6.218" r="0.924"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg></span>';

                $arm_posts .= '<button type="button" class="arm_com_post_comment_btn" data-arm_post_id="">'.__("Post comment", ARM_COMMUNITY_TEXTDOMAIN).'</button>';
                $arm_posts .= "</div>";
                $arm_posts .= "</div>";
                return $arm_posts;
            }
        }

        function arm_com_post_add() {
            global $wpdb,$arm_community_setting, $arm_global_settings, $arm_members_activity;
            $arm_com_settings = $arm_community_setting->arm_com_settings;
            $arm_post_text = isset($arm_com_settings["arm_post_section_lbl"]) ? $arm_com_settings["arm_post_section_lbl"] : __('Post', ARM_COMMUNITY_TEXTDOMAIN);
            $response = array('type' => 'error', 'msg' => $arm_post_text . " " . __('has been not added successfully.', ARM_COMMUNITY_TEXTDOMAIN));
            if (is_user_logged_in() && $this->arm_com_post_allow()) {
                $arm_post_title = $_POST['arm_title'];
                $arm_com_post_content = $_POST['arm_description'];
                $arm_com_added_type = $_POST['arm_added_type'];
                $user_id = get_current_user_id();
                $fimg = "";
                $args = array(
                    'post_title' => $arm_post_title,
                    'post_content' => $arm_com_post_content,
                    'meta_input' => array('arm_com_post_type' => $arm_com_added_type)
                );

                if( $_POST["arm_form_mode"] == "edit" ) {
                    $post_id = $_POST["post_id"];
                    $post = get_post($post_id);
                    if( !empty($post) && $post->post_author == $user_id ) {
                        $args["ID"] = $post_id;
                        if (has_post_thumbnail($post_id)) {
                            if( !empty($_POST['remove_attachment']) ) {
                                delete_post_thumbnail($post->ID);
                            }
                            else {
                                $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
                                $fimg = $image[0];
                            }
                        }
                        $date = $post->post_date;

                        $comment_count = !empty($post->comment_count) ? $post->comment_count : 0;
                        $cmnt_lbl = ($comment_count > 1) ? __('Comments', ARM_COMMUNITY_TEXTDOMAIN) : __('Comment', ARM_COMMUNITY_TEXTDOMAIN);
                        $comment_count = $arm_community_setting->arm_com_short_number_format($comment_count);

                        $arm_like_results = $wpdb->get_results("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key LIKE 'arm_com_post_like_%' AND meta_value != 0 AND post_id = ".$post_id." ",ARRAY_A);
                        $like_count = count($arm_like_results);

                        $like_count = (empty($like_count)) ? 0 : $like_count;
                        $like_lbl = ($like_count > 1) ? __("Likes", ARM_COMMUNITY_TEXTDOMAIN) : __("Like", ARM_COMMUNITY_TEXTDOMAIN);
                        $like_count = $arm_community_setting->arm_com_short_number_format($like_count);
                        wp_update_post($args);
                    }
                    else {
                        exit;
                    }
                }
                else {
                    $date = current_time('mysql');
                    $comment_count = $like_count = 0;
                    $cmnt_lbl = __('Comment', ARM_COMMUNITY_TEXTDOMAIN);
                    $like_lbl = __("Like", ARM_COMMUNITY_TEXTDOMAIN);

                    $args['post_status'] = 'publish';
                    $args['post_author'] = $user_id;
                    $args['post_date'] = $date;
                    $args['post_type'] = 'arm_community';
                    $args['comment_status'] = 'open';
                    $post_id = wp_insert_post($args);
                }

                if ($post_id > 0) {
                    if($_POST["arm_form_mode"] == "add") {
                        do_action('arm_com_activity', '[arm_com_displayname id="' . $user_id . '"] ' . __('user created new post', ARM_COMMUNITY_TEXTDOMAIN) .' '. $arm_post_title, __('New post published', ARM_COMMUNITY_TEXTDOMAIN), '', 'post', $post_id);
                    }
                    $arm_com_post_like = isset($arm_com_settings["arm_com_post_like"]) ? $arm_com_settings["arm_com_post_like"] : 0;
                    $arm_com_post_comment = isset($arm_com_settings["arm_com_post_comment"]) ? $arm_com_settings["arm_com_post_comment"] : 0;
                    if($_FILES["file"] && $arm_com_added_type == "photo") {
                        $upload_dir = wp_upload_dir();
                        $upload_dirname = $upload_dir['basedir'] . "/armember/";
                        $file_extension = explode('.', $_FILES["file"]["name"]);
                        $file_ext = $file_extension[count($file_extension) - 1];
                        $filename = 'arm_file_' . wp_generate_password(15, false) . '.' . $file_ext;

                        $file_ext_lower = strtolower($file_ext);

                        $denyExts = array("php", "php3", "php4", "php5", "pl", "py", "jsp", "asp", "exe", "cgi");
                        $allowed_feature_images_ext = array('jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff' );
                        $file = FALSE;
                        if (!in_array($file_ext_lower, $denyExts) && in_array($file_ext_lower,$allowed_feature_images_ext)) {
                            $file = move_uploaded_file($_FILES["file"]["tmp_name"], MEMBERSHIP_UPLOAD_DIR . '/' . $filename);
                        }

                        if($file === TRUE) {
                            $upload_dir = wp_upload_dir();
                            $image_data = file_get_contents($image_url);
                            $file = MEMBERSHIP_UPLOAD_DIR . '/' . $filename;
                            $wp_filetype = wp_check_filetype($filename, null );
                            $attachment = array('post_mime_type' => $wp_filetype['type']);
                            $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
                            require_once(ABSPATH . 'wp-admin/includes/image.php');
                            $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
                            $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
                            $res2= set_post_thumbnail( $post_id, $attach_id );
                            $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'single-post-thumbnail' );
                            $fimg = $image[0];
                        }
                    }
                    $author_data = get_user_by('ID', $user_id);
                    $post_author_name = $author_data->display_name;
                    $post_date = $this->arm_com_post_time_diff($date);
                    $post_content_class = "arm_box_width_100";
                    $arm_posts = "";
                    if($_POST["arm_form_mode"] == "add") {
                        $arm_posts .= '<div class="arm_com_post_box arm_com_posts_box arm_com_post_box_' . $post_id . '">';
                    }

                    $arm_posts .= '<img src="'.ARM_COMMUNITY_IMAGES_URL.'/close.png" class="arm_com_post_remove" data-post_id="'.$post_id.'" />';
                    $arm_posts .= '<span class="arm_com_post_edit" data-post_id="'.$post_id.'"><img src="'.ARM_COMMUNITY_IMAGES_URL.'/edit.png" /></span>';

                    if(!empty($fimg)) {
                        $post_content_class = "arm_box_width_60";
                        $arm_posts .= "<div class='arm_com_post_image_box' style='background-image: url(".$fimg.")'></div>";
                    }
                    $arm_posts .= '<div class="arm_com_post_content_box '.$post_content_class.'">';
                    $arm_posts .= '<div class="arm_com_post_content_box_wrapper">';
                    $arm_posts .= '<p class="arm_com_post_title">'.$arm_post_title.'</p>';

                    $arm_posts .= '<p class="arm_com_post_meta"><span>'.$post_date.'</span> | <span>'.$cmnt_lbl.' </span><span class="arm_post_total_comments">'.$comment_count.'</span> | <span>'.$like_lbl.' </span><span class="arm_post_total_likes">'.$like_count.'</span></p>';

                    $arm_posts .= '<p class="arm_com_post_content">';
                    $arm_posts .= stripslashes(substr($arm_com_post_content, 0, 160));
                    if(strlen($arm_com_post_content) > 160) {$arm_posts .= ' ...';}
                    $arm_posts .= '</p>';
                    $arm_posts .= '<a href="'.get_post_permalink($post_id).'" class="arm_com_post_read_more_nav">'.__('Read More', ARM_COMMUNITY_TEXTDOMAIN).'</a>';
                    $arm_posts .= '</div>';
                    $arm_user_like_count = get_post_meta($post_id, 'arm_com_post_like_' . $user_id, 1);
                    if( !empty($arm_com_post_like) || !empty($arm_com_post_comment) ) {
                        $arm_posts .= '<div class="arm_com_post_action_section">';
                        if(!empty($arm_com_post_like) && empty($arm_user_like_count)) {
                            $arm_posts .= $this->arm_com_post_get_like_button($post_id);
                        }
                        else
                        {
                            $arm_posts .= $this->arm_com_post_get_unlike_button($post_id);
                        }
                        if(!empty($arm_com_post_comment)) {
                            $arm_posts .= '<a href="javascript:void(0);" class="arm_com_post_comment_nav" data-post_id="'.$post_id.'"><img src="'.ARM_COMMUNITY_IMAGES_URL.'/comment.png" />Comment</a>';
                        }
                        $arm_posts .= '</div>';
                    }
                    $arm_posts .= '</div>';

                    if($_POST["arm_form_mode"] == "add") {
                        $arm_posts .= '</div>';

                        $arm_posts .= "<div class='arm_com_post_comment_box arm_com_post_comment_box_".$post_id."'></div>";
                        $arm_posts .= "<div class='arm_com_post_comment_list_div' id='arm_com_post_comment_list_div_".$post_id."'>";
                        $arm_posts .= "<ul class='arm_com_post_comment_list_ul'></ul>";
                        $arm_posts .= "</div>";
                    }

                    $arm_post_wall_section = "";
                    $arm_post_wall_section .= '<div class="arm_com_wall_post_header">';
                    if( $arm_community_setting->arm_com_is_profile_editor() ) {
                        $profile_url = '#';
                    }
                    else {
                        $profile_url = $arm_global_settings->arm_get_user_profile_url($user_id);
                    }

                    $arm_post_wall_section .= "<span class='arm_com_wall_post_avatar'><a href='".$profile_url."'>".get_avatar($user_id)."</a></span>";

                    $arm_post_wall_section .= '<p class="arm_com_post_title">';
                    $arm_post_wall_section .= $arm_post_title;
                    $arm_post_wall_section .= '<p class="arm_com_post_meta"><span>'.$post_date.'</span> | <span>'.$cmnt_lbl.' </span><span class="arm_post_total_comments">'.$comment_count.'</span> | <span>'.$like_lbl.' </span><span class="arm_post_total_likes">'.$like_count.'</span></p>';
                    $arm_post_wall_section .= '</p>';
                    $arm_post_wall_section .='</div>';

                    
                    if($_POST["arm_form_mode"] == "edit") {
                        $arm_response_msg = $arm_post_text . " " . __('has been updated successfully.', ARM_COMMUNITY_TEXTDOMAIN);
                    }
                    else {
                        $arm_response_msg = $arm_post_text . " " . __('has been added successfully.', ARM_COMMUNITY_TEXTDOMAIN);
                    }
                    $response = array('type' => 'success', 'post_id' => $post_id, 'arm_post_wall_section' => $arm_post_wall_section, 'msg' => $arm_response_msg, 'content' => $arm_posts);
                }
            }
            echo json_encode($response);
            die;
        }

        function arm_com_post_remove() {
            $response = array('type' => 'failed');
            if(is_user_logged_in() && !empty($_POST['post_id'])) {
                $user_id = get_current_user_id();
                $args = array('post_type' => 'arm_community', 'include' => $_POST['post_id'], 'author' => $user_id);
                $posts_array = get_posts($args);
                if (!empty($posts_array)) {
                    foreach ($posts_array as $post) {
                        wp_delete_post($post->ID);
                    }
                    $response = array('type' => 'success');
                }
            }
            echo json_encode($response);
            die;
        }

        function arm_com_post_like_func() {
            $response = array ("type" => "failed");
            if ( is_user_logged_in() && !empty($_POST['post_id'])) {
                global $arm_manage_communication;
                $post_id = $_POST['post_id'];
                $author_id = get_post_field( 'post_author', $post_id );
                $user_id = get_current_user_id();
                update_post_meta($post_id, 'arm_com_post_like_' . $user_id, 1);
                $arm_post_title = get_the_title($post_id);

                do_action('arm_com_activity', '[arm_com_displayname id="' . $user_id . '"] ' . __('user like post', ARM_COMMUNITY_TEXTDOMAIN) .' '. $arm_post_title, __('Member like post', ARM_COMMUNITY_TEXTDOMAIN), '', 'like_post', $post_id);

                if( $user_id != $author_id ) {
                    $plan_id = get_user_meta($author_id, 'arm_user_plan_ids', true);
                    $plan_id = isset($plan_id[0]) ? $plan_id[0] : 0;
                    $arm_com_message_email_type = 'arm_com_friend_liked_your_post';
                    $arm_manage_communication->membership_communication_mail($arm_com_message_email_type, $author_id, $plan_id);
                }

                $response = array('type' => 'success', 'content' => $this->arm_com_post_get_unlike_button($post_id));
            }
            echo trim(json_encode($response));
            exit;
        }

        function arm_com_post_unlike_func() {
            $response = array('type' => 'failed');
            if (isset($_POST['post_id']) && $_POST['post_id'] > 0) {
                $post_id = $_POST['post_id'];
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                }
                delete_post_meta($post_id, 'arm_com_post_like_' . $user_id);
                $arm_post_title = get_the_title($post_id);

                do_action('arm_com_activity', '[arm_com_displayname id="' . $user_id . '"] ' . __('user unlike post', ARM_COMMUNITY_TEXTDOMAIN) .' '. $arm_post_title, __('Member unlike post', ARM_COMMUNITY_TEXTDOMAIN), '', 'unlike_post', $post_id);

                $response = array('type' => 'success', 'content' => $this->arm_com_post_get_like_button($post_id));
            }
            echo json_encode($response);
            die;
        }

        function arm_com_post_get_like_button($post_id) {
            $arm_like_button = '<a href="javascript:void(0);" class="arm_com_post_like_nav" data-post_id="'.$post_id.'"><img src="'.ARM_COMMUNITY_IMAGES_URL.'/like.png" />'.__('Like', ARM_COMMUNITY_TEXTDOMAIN).'</a>';
            return $arm_like_button;
        }

        function arm_com_post_get_unlike_button($post_id) {
            $arm_like_button = '<a href="javascript:void(0);" class="arm_com_post_dislike_nav" data-post_id="'.$post_id.'"><img src="'.ARM_COMMUNITY_IMAGES_URL.'/dislike.png" />'.__('Unlike', ARM_COMMUNITY_TEXTDOMAIN).'</a>';
            return $arm_like_button;
        }

        function arm_com_post_time_diff($date) {
            global $arm_global_settings;
            $date_formate = $arm_global_settings->arm_get_wp_date_format();
            $date1 = new DateTime($date);
            $date2 = new DateTime("now");
            $interval = $date1->diff($date2);
            $years = $interval->format('%y');
            $months = $interval->format('%m');
            $days = $interval->format('%d');
            $hours = $interval->format('%h');
            $minute = $interval->format('%i');
            $second = $interval->format('%s');

            if ($years != 0 || $months != 0 || $days > 1) {
                return date($date_formate, strtotime($date));
            } else {
                if ($days != 0) {
                    return $days . " " . __('day ago', ARM_COMMUNITY_TEXTDOMAIN);
                } else if ($hours != 0) {
                    return $hours . " " . __('hour ago', ARM_COMMUNITY_TEXTDOMAIN);
                } else if ($minute != 0) {
                    return $minute . " " . __('minute ago', ARM_COMMUNITY_TEXTDOMAIN);
                } else {
                    return __('Just now', ARM_COMMUNITY_TEXTDOMAIN);
                }
            }
        }

        function arm_com_post_delete_user_data($user_id) {
            $arm_posts = '';
            $args = array (
                'posts_per_page' => 0,
                'offset' => 0,
                'category' => '',
                'category_name' => '',
                'orderby' => 'date',
                'order' => 'DESC',
                'include' => '',
                'exclude' => '',
                'meta_key' => '',
                'meta_value' => '',
                'post_type' => 'arm_community',
                'post_mime_type' => '',
                'post_parent' => '',
                'author' => $user_id,
                'author_name' => '',
                'post_status' => 'publish',
                'suppress_filters' => true
            );
            $posts_array = get_posts($args);

            foreach ($posts_array as $post) :
                wp_delete_post($post->ID);
            endforeach;
        }

        function arm_community_comment_add() {
            $response = array('type' => 'failed', 'msg' => __('Something went wrong. Try after sometime.', ARM_COMMUNITY_TEXTDOMAIN));
            if (is_user_logged_in() && !empty($_POST['post_id']) && !empty($_POST["comment"])) {
                $post = get_post($_POST['post_id']);
                if(!empty($post)) {
                    $user_id = get_current_user_id();
                    $post_id = $_POST["post_id"];
                    $comment = $_POST["comment"];
                    $data = array(
                        'comment_post_ID' => $post_id,
                        'comment_content' => $comment,
                        'user_id' => $user_id,
                        'comment_approved' => 1,
                    );

                    $comment_id = wp_insert_comment($data);

                    if($comment_id != FALSE && $comment_id > 0) {
                        global $arm_community_setting, $arm_manage_communication;
                        $author_id = get_post_field( 'post_author', $post_id );
                        if($user_id != $author_id) {
                            $plan_id = get_user_meta($author_id, 'arm_user_plan_ids', true);
                            $plan_id = isset($plan_id[0]) ? $plan_id[0] : 0;
                            $arm_com_message_email_type = 'arm_com_friend_commented_on_your_post';
                            $arm_manage_communication->membership_communication_mail($arm_com_message_email_type, $author_id, $plan_id);
                        }

                        $arm_com_settings = $arm_community_setting->arm_com_settings;
                        $records_per_page = isset($arm_com_settings["arm_record_per_page"]) ? $arm_com_settings["arm_record_per_page"] : 10;
                        $comment_list = $this->arm_get_post_comments($post_id, $records_per_page);
                        $response = array('type' => 'success', "comments" => $comment_list, 'msg' => __('Comment added successfully.', ARM_COMMUNITY_TEXTDOMAIN));
                    }
                }
            }
            echo json_encode($response);
            die;
        }

        function arm_get_post_comments($post_id, $records_per_page) {
            global $arm_community_setting, $arm_global_settings;
            if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "arm_get_post_comments_front") {
                $post_id = $_REQUEST["post_id"];
                $pageno = $_REQUEST["pageno"];
                $arm_com_settings = $arm_community_setting->arm_com_settings;
                $records_per_page = isset($arm_com_settings["arm_record_per_page"]) ? $arm_com_settings["arm_record_per_page"] : 10;
                $records_per_page = $records_per_page;
                $offset = ( $pageno - 1 ) * $records_per_page;
            }
            else {
                $post_id = $post_id;
                $records_per_page = $records_per_page;
                $offset = 0;
                $pageno = 1;
            }
            $comments_args = array('post_id' => $post_id, 'order' => 'DESC');
            $arm_cmnts = get_comments($comments_args);
            $arm_ttl_cmnts = count($arm_cmnts);
            $paging = "";
            $current_user = get_current_user_id();
            if($arm_ttl_cmnts > $records_per_page) {
                $comments_args['number'] = $records_per_page;
                $comments_args['offset'] = $offset;
                $arm_cmnts = get_comments($comments_args);
                $ttl_page = ceil( $arm_ttl_cmnts / $records_per_page );
                $arm_ttl_cmnts = count($arm_cmnts);
                $paging = "<div class='arm_post_comment_load_more_nav'><span data-post_id='".$post_id."' data-pageno='".($pageno + 1)."' data-ttl_page = '".$ttl_page."'>".__("Load More", ARM_COMMUNITY_TEXTDOMAIN)."</span></div>";
            }

            $cmnts_box = $cmnts_list = $cmnts_list_class = "";
            if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "arm_get_post_comments_front") {
                $cmnts_list_class = "arm_com_comment_new_loaded";
            }
            if( $arm_ttl_cmnts > 0 )
            {
                for( $i = $arm_ttl_cmnts - 1; $i >= 0; $i-- ) {
                    $cmnts_list .= "<li class='arm_com_post_commnet_single ".$cmnts_list_class."' data-comment_id='".$arm_cmnts[$i]->comment_ID."' data-post_id='".$post_id."'>";

                    if( $arm_community_setting->arm_com_is_profile_editor() ) {
                        $profile_url = '#';
                    }
                    else {
                        $profile_url = $arm_global_settings->arm_get_user_profile_url($arm_cmnts[$i]->user_id);
                    }

                    $cmnts_list .= "<a href='".$profile_url."' class='arm_com_comment_avatar'>";

                    $cmnts_list .= get_avatar($arm_cmnts[$i]->user_id, '50', array('class' => 'arm_com_rounded_avatar') );

                    $cmnts_list .= "</a>";

                    $cmnts_list .= "<span class='arm_com_post_commnet_single_content'>".$arm_cmnts[$i]->comment_content."</span>";
                    if($current_user == $arm_cmnts[$i]->user_id) {
                        $cmnts_list .= '<img src="'.ARM_COMMUNITY_IMAGES_URL.'/close.png" class="arm_com_post_comment_remove" data-comment_id="'.$arm_cmnts[$i]->comment_ID.'" />';
                    }
                    $cmnts_list .= "</li>";
                }
            }
            if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "arm_get_post_comments_front") {
                $response = array("comments" => $cmnts_list);
                echo json_encode($response);
                exit;
            }
            $cmnts_box = "<div class='arm_com_post_comment_list_div' id='arm_com_post_comment_list_div_".$post_id."'>";
            $cmnts_box .= $paging;
            $cmnts_box .= "<ul class='arm_com_post_comment_list_ul'>" . $cmnts_list . "</ul>";
            $cmnts_box .= "</div>";
            return $cmnts_box;
        }

        function arm_com_post_comment_remove() {
            $response = array('type' => 'failed');
            if(is_user_logged_in() && !empty($_POST['comment_id'])) {
                wp_delete_comment( $_POST['comment_id'], true );
                $response = array('type' => 'success');
            }
            echo json_encode($response);
            die;
        }

        function arm_community_display_wall_post_func($atts = array(), $content = array(), $tag = '') {
            if (is_user_logged_in() && $this->arm_com_post_wall_allow()) {
                global $wpdb, $arm_community_setting, $arm_community_features, $arm_global_settings;
                $arm_loggedin_user_id = get_current_user_id();
                $arm_com_settings = $arm_community_setting->arm_com_settings;
                $records_per_page = isset($arm_com_settings["arm_record_per_page"]) ? $arm_com_settings["arm_record_per_page"] : 10;
                $arm_posts_tmp = $paging = "";
                $posts_array = array();
                if ($arm_community_setting->arm_com_is_profile_editor()) {
                    $user_id = 0;
                    $posts_array[0] = new stdClass();
                    $posts_array[0]->ID = 1;
                    $posts_array[0]->post_author = $arm_loggedin_user_id;
                    $posts_array[0]->post_date = current_time('mysql');
                    $posts_array[0]->post_title = __('This is my first title', ARM_COMMUNITY_TEXTDOMAIN);
                    $posts_array[0]->post_content = __('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.', ARM_COMMUNITY_TEXTDOMAIN);
                    $posts_array[1] = new stdClass();
                    $posts_array[1]->ID = 1;
                    $posts_array[1]->post_author = $arm_loggedin_user_id;
                    $posts_array[1]->post_date = current_time('mysql');
                    $posts_array[1]->post_title = __('This is my second title', ARM_COMMUNITY_TEXTDOMAIN);
                    $posts_array[1]->post_content = __('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.', ARM_COMMUNITY_TEXTDOMAIN);
                }
                else {
                    $user_data = $arm_community_setting->arm_com_profile_get_user_id();
                    $user_data_arr = array_shift($user_data);
                    $user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0;
                    $loggedin_user_id = get_current_user_id();
                    $following_id = $wpdb->get_results("SELECT arm_following_id FROM `".$arm_community_features->tbl_arm_com_follow."` WHERE arm_follower_id = {$user_id}", ARRAY_A);
                    $friends = $wpdb->get_results("SELECT * FROM `".$arm_community_features->tbl_arm_com_friendship."` WHERE (arm_initiator_user_id={$user_id} OR arm_friend_id={$user_id} ) AND arm_is_confirmed = 1", ARRAY_A);
                    $friends_id = array($user_id);
                    if(!empty( $friends)) {
                        foreach ($friends as $arm_user_data) {
                            if($arm_user_data['arm_initiator_user_id'] != $user_id) {
                                array_push($friends_id, $arm_user_data['arm_initiator_user_id']);
                            } else {
                                array_push($friends_id, $arm_user_data['arm_friend_id']);
                            }
                        }
                    }

                    if( ! empty($following_id) ) {
                        foreach ($following_id as $value) {
                            array_push($friends_id, $value["arm_following_id"]);
                        }
                    }

                    $merged_id = array_unique($friends_id);

                    if(!empty($merged_id)) {
                        $args = array (
                            'posts_per_page' => -1,
                            'orderby' => 'date',
                            'order' => 'DESC',
                            'post_type' => 'arm_community',
                            'author__in' => $merged_id,
                            'post_status' => 'publish',
                            'suppress_filters' => true
                        );
                        $posts_array = get_posts($args);
                        $total_posts = count($posts_array);
                        if($total_posts > $records_per_page) {
                            $pageno = isset($_REQUEST["pageno"]) ? $_REQUEST["pageno"] : 1;
                            $offset = ( $pageno - 1 ) * $records_per_page;
                            $args["posts_per_page"] = $records_per_page;
                            $args["offset"] = $offset;
                            $posts_array = get_posts($args);
                            $total_pages = ceil( $total_posts / $records_per_page );
                            $more_link_cnt = '<a class="arm_com_posts_load_more_link arm_page_numbers" href="javascript:void(0)" data-page="' . ($pageno + 1) . '" data-type="posts" data-arm_ttl_page="'.$total_pages.'">' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '</a>';
                            $more_link_cnt .= '<img class="arm_load_more_loader" src="'.MEMBERSHIP_IMAGES_URL.'/arm_loader.gif" alt="' . __('Load More', ARM_COMMUNITY_TEXTDOMAIN) . '" style="display:none;">';
                            $paging = '<div class="arm_com_wall_posts_paging_container arm_com_wall_posts_paging_container_infinite">' . $more_link_cnt . '</div>';
                        }
                    }
                }

                $content .= '<div class="arm_com_wall_post_display_container" id="arm_com_wall_post_display_container">';
                $content .= '<div class="arm_com_post_wrapper">';
                if (!empty($posts_array)) {
                    $arm_com_post_like = isset($arm_com_settings["arm_com_post_like"]) ? $arm_com_settings["arm_com_post_like"] : 0;
                    $arm_com_post_comment = isset($arm_com_settings["arm_com_post_comment"]) ? $arm_com_settings["arm_com_post_comment"] : 0;
                    foreach ($posts_array as $post) {
                        $post_id = $post->ID;
                        $post_author = $post->post_author;
                        $author_data = get_user_by('ID', $post_author);

                        $post_date = $this->arm_com_post_time_diff($post->post_date);
                        $post_title = $post->post_title;
                        $post_content = $post->post_content;
                        
                        $comment_count = !empty($post->comment_count) ? $post->comment_count : 0;
                        $cmnt_lbl = ($comment_count > 1) ? __('Comments', ARM_COMMUNITY_TEXTDOMAIN) : __('Comment', ARM_COMMUNITY_TEXTDOMAIN);
                        $comment_count = $arm_community_setting->arm_com_short_number_format($comment_count);
                        
                        $arm_like_results = $wpdb->get_results("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key LIKE 'arm_com_post_like_%' AND meta_value != 0 AND post_id = ".$post_id." ",ARRAY_A);
                        $like_count = count($arm_like_results);
                        
                        $like_count = (empty($like_count)) ? 0 : $like_count;
                        $like_lbl = ($like_count > 1) ? __("Likes", ARM_COMMUNITY_TEXTDOMAIN) : __("Like", ARM_COMMUNITY_TEXTDOMAIN);
                        $like_count = $arm_community_setting->arm_com_short_number_format($like_count);

                        $post_content_class = "arm_box_width_100";
                        $arm_posts_tmp .= "<div class='arm_com_post_box arm_com_post_box_".$post_id."'>";
                        if (has_post_thumbnail($post->ID)) {
                            $post_content_class = "arm_box_width_60";
                            $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
                            $arm_posts_tmp .= "<div class='arm_com_post_image_box' style='background-image: url(".$image[0].")'></div>";
                        }
                        $arm_posts_tmp .= '<div class="arm_com_post_content_box '.$post_content_class.'">';
                        $arm_posts_tmp .= '<div class="arm_com_post_content_box_wrapper">';

                        $arm_posts_tmp .= '<div class="arm_com_wall_post_header">';

                        if( $arm_community_setting->arm_com_is_profile_editor() ) {
                            $profile_url = '#';
                        }
                        else {
                            $profile_url = $arm_global_settings->arm_get_user_profile_url($author_data->ID);
                        }

                        $arm_posts_tmp .= "<span class='arm_com_wall_post_avatar'><a href='".$profile_url."'>".get_avatar($author_data->ID)."</a></span>";

                        $arm_posts_tmp .= '<p class="arm_com_post_title">';
                        $arm_posts_tmp .= $post_title;
                        $arm_posts_tmp .= '<p class="arm_com_post_meta"><span>'.$post_date.'</span> | <span>'.$cmnt_lbl.' </span><span class="arm_post_total_comments">'.$comment_count.'</span> | <span>'.$like_lbl.' </span><span class="arm_post_total_likes">'.$like_count.'</span></p>';
                        $arm_posts_tmp .= '</p>';
                        $arm_posts_tmp .='</div>';
                        
                        $arm_posts_tmp .= '<p class="arm_com_post_content">';
                        $arm_posts_tmp .= stripslashes(substr($post_content, 0, 160));
                        if(strlen($post_content) > 160) {$arm_posts_tmp .= ' ...';}
                        $arm_posts_tmp .= '</p>';
                        if( $arm_community_setting->arm_com_is_profile_editor() ) {
                            $post_parmalink = "#";
                        }
                        else {
                            $post_parmalink = get_post_permalink($post->ID);
                        }
                        $arm_posts_tmp .= '<a href="'.$post_parmalink.'" class="arm_com_post_read_more_nav">'.__('Read More', ARM_COMMUNITY_TEXTDOMAIN).'</a>';
                        $arm_posts_tmp .= '</div>';
                        if( !empty($arm_com_post_like) || !empty($arm_com_post_comment) ) {
                            $arm_posts_tmp .= '<div class="arm_com_post_action_section">';
                            if(!empty($arm_com_post_like)) {
                                if (get_post_meta($post->ID, 'arm_com_post_like_' . $arm_loggedin_user_id)) {
                                    $arm_posts_tmp .= $this->arm_com_post_get_unlike_button($post->ID);
                                }
                                else {
                                    $arm_posts_tmp .= $this->arm_com_post_get_like_button($post->ID);
                                }
                            }
                            if(!empty($arm_com_post_comment)) {
                                $arm_posts_tmp .= '<a href="javascript:void(0);" class="arm_com_post_comment_nav" data-post_id="'.$post->ID.'"><img src="'.ARM_COMMUNITY_IMAGES_URL.'/comment.png" />Comment</a>';
                            }
                            $arm_posts_tmp .= '</div>';
                        }
                        $arm_posts_tmp .= '</div>';
                        $arm_posts_tmp .= '</div>';
                        $arm_posts_tmp .= "<div class='arm_com_post_comment_box arm_com_post_comment_box_".$post->ID."'></div>";
                        $arm_posts_tmp .= $this->arm_get_post_comments($post->ID, $records_per_page);
                    }
                }
                else {
                    $arm_com_settings = $arm_community_setting->arm_com_settings;
                    $arm_wall_section_lbl = !empty($arm_com_settings["arm_wall_section_lbl"]) ? $arm_com_settings["arm_wall_section_lbl"] : __("News Feed", ARM_COMMUNITY_TEXTDOMAIN);

                    $content .= "<div class='arm_com_no_message'>";
                    $content .= __("No", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_wall_section_lbl . " " . __("found", ARM_COMMUNITY_TEXTDOMAIN);
                    $content .= "</div>";
                }

                if( isset($_REQUEST["action"]) && $_REQUEST["action"] == "arm_community_display_wall_post_front" ) {
                    $response = array("content" => $arm_posts_tmp);
                    echo json_encode($response);
                    exit;
                }

                $content .= "<div class='arm_com_post_box_wrapper'>" . $arm_posts_tmp . "</div>";

                if( trim($paging)  != "") {
                    $content .= "<div class='arm_wall_posts_paging_div'>".$paging."</div>";
                }

                $content .= '</div>';
                $content .= '</div>';
            }
            return $content;
        }

        function arm_com_post_comment_list() {
            if(!empty($_REQUEST["post_id"])) {
                $post_id = $_REQUEST["post_id"];
                global $wpdb, $arm_global_settings, $arm_community_setting, $arm_community_features, $ARMember;
                
                if(method_exists($ARMember, 'arm_check_user_cap')){
                    $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                    $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
                }
                $date_format = $arm_global_settings->arm_get_wp_date_time_format();
                $ai = 0;
                $comment_grid_columns = array(
                    'user_avatar' => __('User Avatar', ARM_COMMUNITY_TEXTDOMAIN),
                    'user_name' => __('User Name', ARM_COMMUNITY_TEXTDOMAIN),
                    'post_commnet' => __('Comment', ARM_COMMUNITY_TEXTDOMAIN),
                );

                $offset = !empty($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
                $number = !empty($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
                $sorting_ord = !empty($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
                $order_by = 'comment_ID';
                $comments_args["number"] = $number;
                $comments_args["offset"] = $offset;
                $comments_args["order"] = $sorting_ord;
                $comments_args["orderby"] = $order_by;

                $comment_table = $wpdb->comments;

                $comment_query = "SELECT * FROM {$comment_table} WHERE comment_post_ID = {$post_id}";

                $arm_cmnts = $wpdb->get_results($comment_query);

                $total_before_filter = $total_after_filter = count($arm_cmnts);

                $comment_query .= " ORDER BY {$order_by} {$sorting_ord} LIMIT {$offset},{$number}";

                $arm_cmnts = $wpdb->get_results($comment_query);
                $grid_data = array();
                if (is_array($arm_cmnts) && count($arm_cmnts) > 0) {
                    foreach ($arm_cmnts as $arm_cmnt) {
                        $comment_id = $arm_cmnt->comment_ID;
                        $user_avatar = get_avatar($arm_cmnt->user_id, '50');
                        $comment = $arm_cmnt->comment_content;

                        $author_data = get_user_by('ID', $arm_cmnt->user_id);

                        $grid_data[$ai][0] = $user_avatar;
                        $grid_data[$ai][1] = $author_data->user_login;
                        $grid_data[$ai][2] = nl2br(stripslashes($comment));

                        $gridAction = "<div class='arm_grid_action_btn_container'>";

                        if($arm_cmnt->comment_approved == "spam") {
                            $gridAction .= "<a href='javascript:void(0)' class='arm_com_post_comment_status_nav' data-arm_comment_id='".$comment_id."' data-arm_post_id='".$post_id."' data-arm_status_change_to='approve'><img src='" . ARM_COMMUNITY_IMAGES_URL . "/unspam.png' class='armhelptip' title='" . __('Unspam', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/unspam_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/unspam.png';\" /></a>";
                        }
                        else {
                            $gridAction .= "<a href='javascript:void(0)' class='arm_com_post_comment_status_nav' data-arm_comment_id='".$comment_id."' data-arm_post_id='".$post_id."' data-arm_status_change_to='spam'><img src='" . ARM_COMMUNITY_IMAGES_URL . "/spam.png' class='armhelptip' title='" . __('Spam', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/spam_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/spam.png';\" /></a>";
                        }
                        
                        $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$comment_id});'><img src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete.png' class='armhelptip' title='" . __('Delete', ARM_COMMUNITY_TEXTDOMAIN) . "' onmouseover=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete_hover.png';\" onmouseout=\"this.src='" . ARM_COMMUNITY_IMAGES_URL . "/grid_delete.png';\" /></a>";

                        $gridAction .= $arm_community_setting->arm_com_get_confirm_box($comment_id, __("Are you sure you want to delete this comment?", ARM_COMMUNITY_TEXTDOMAIN), 'arm_com_post_comment_delete_btn', '', __('Delete', ARM_COMMUNITY_TEXTDOMAIN), __('Cancel', ARM_COMMUNITY_TEXTDOMAIN));
                        $gridAction .= "</div>";

                        $grid_data[$ai][3] = $gridAction;

                        $ai++;
                    }
                }

                $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
                $response = array(
                    'sColumns' => implode(',', $comment_grid_columns),
                    'sEcho' => $sEcho,
                    'iTotalRecords' => $total_before_filter,
                    'iTotalDisplayRecords' => $total_after_filter,
                    'aaData' => $grid_data,
                );
                echo json_encode($response);
                die();
            }
        }

        function arm_community_get_single_post_func() {
            $response = array("type" => "failed", "msg" => __("Something went wrong. Try after sometime.", ARM_COMMUNITY_TEXTDOMAIN));
            if(is_user_logged_in() && $this->arm_com_post_allow() && !empty($_REQUEST["post_id"])) {
                $post_id = $_REQUEST["post_id"];
                $post = get_post($post_id);
                if(!empty($post)) {
                    $post_fimg = "";
                    if (has_post_thumbnail($post_id)) {
                        $post_fimg = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'single-post-thumbnail');
                        $post_fimg = $post_fimg[0];
                    }
                    $response = array (
                        "title" => $post->post_title,
                        "content" => $post->post_content,
                        'added_type' => get_post_meta($post_id, 'arm_com_post_type', true),
                        "fimg" => $post_fimg,
                        "type" => "success",
                        "msg" => ""
                    );
                }
            }
            echo json_encode($response);
            exit;
        }

        function arm_com_post_comment_remove_by_admin_func() {
            global $ARMember, $arm_community_features;
            
            if(method_exists($ARMember, 'arm_check_user_cap')){
                $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
            }
            $response = array("type" => "failed", "msg" => __("Something went wrong. Try after sometime.", ARM_COMMUNITY_TEXTDOMAIN));
            if(!empty($_REQUEST["comment_id"])) {
                wp_delete_comment( $_REQUEST['comment_id'], true );
                $response = array("type" => "success", "msg" => __("Comment deleted successfully", ARM_COMMUNITY_TEXTDOMAIN));
            }
            echo json_encode($response);
            exit;
        }

        function arm_com_change_comment_status_func() {
            global $ARMember, $arm_community_features;
            
            if(method_exists($ARMember, 'arm_check_user_cap')){
                $arm_community_capabilities = $arm_community_features->arm_community_page_slug();
                $ARMember->arm_check_user_cap($arm_community_capabilities['1'],'1');
            }
            $response = array("type" => "failed", "msg" => __("Something went wrong. Try after sometime.", ARM_COMMUNITY_TEXTDOMAIN));
            $comment_id = $_REQUEST['comment_id'];
            $status_change_to = $_REQUEST['status_change_to'];
            if( !empty($comment_id) && !empty($status_change_to) ) {
                $status = wp_set_comment_status($comment_id, $status_change_to);
                if($status) {
                    if($status_change_to == "approve") {
                        $msg = __("Comment unspammed successfully.", ARM_COMMUNITY_TEXTDOMAIN);
                    }
                    else {
                        $msg = __("Comment spammed successfully.", ARM_COMMUNITY_TEXTDOMAIN);
                    }
                    $response = array("type" => "success", "msg" => $msg);
                }
            }
            echo json_encode($response);
            exit;
        }
    }
}
global $arm_community_post;
$arm_community_post = new ARM_Community_Post();