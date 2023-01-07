<?php
if (!class_exists('ARM_Community_profile')) {
	class ARM_Community_profile {
		var $arm_com_settings = '';
		function __construct() {
			add_action('arm_profile_default_options_outside', array(&$this, 'arm_profile_default_options_func'), 10, 1);
			add_action('arm_profile_setting_section_outside', array(&$this, 'arm_profile_setting_section_func'), 10, 1);
			add_filter('arm_profile_dummy_content_before_fields_outside', array(&$this, 'arm_profile_dummy_content_before_fields_func'), 10, 2);
			add_filter('arm_profile_dummy_content_after_fields_outside', array(&$this, 'arm_profile_dummy_content_after_fields_func'), 10, 2);
			add_filter('arm_profile_content_before_fields_outside', array(&$this, 'arm_profile_content_before_fields_func'), 10, 3);
			add_filter('arm_profile_content_after_fields_outside', array(&$this, 'arm_profile_content_after_fields_func'), 10, 3);
			add_filter('arm_change_content_before_display_profile_and_directory', array(&$this, 'arm_change_profile_directory_style_outside_func'), 10, 2);
			add_filter('arm_profile_dummy_content_before_fields_outside', array(&$this, 'arm_change_dummy_profile_directory_style_outside_func'), 10, 2);
			add_action('arm_profile_font_settings_outside', array(&$this, 'arm_profile_font_settings_outside_func'));
			add_action('arm_profile_color_options_outside', array(&$this, 'arm_profile_color_options_outside_func'));
			add_filter('arm_profile_default_options_outside', array(&$this, 'arm_profile_default_options_outside_func'));
		}

		function arm_profile_default_options_outside_func($options = array()) {
			$options['com_tab_font']['font_family'] = isset($options['com_tab_font']['font_family']) ? $options['com_tab_font']['font_family'] : 'Helvetica';
			$options['com_tab_font']['font_size'] = isset($options['com_tab_font']['font_size']) ? $options['com_tab_font']['font_size'] : 16;
			$options['com_tab_font']['font_bold'] = isset($options['com_tab_font']['font_bold']) ? $options['com_tab_font']['font_bold'] : 1;
			$options['com_tab_font']['font_italic'] = isset($options['com_tab_font']['font_italic']) ? $options['com_tab_font']['font_italic'] : 0;
			$options['com_tab_font']['font_decoration'] = isset($options['com_tab_font']['font_decoration']) ? $options['com_tab_font']['font_decoration'] : '';
			$options['com_title_font']['font_family'] = isset($options['com_title_font']['font_family']) ? $options['com_title_font']['font_family'] : 'Helvetica';
			$options['com_title_font']['font_size'] = isset($options['com_title_font']['font_size']) ? $options['com_title_font']['font_size'] : 16;
			$options['com_title_font']['font_bold'] = isset($options['com_title_font']['font_bold']) ? $options['com_title_font']['font_bold'] : 1;
			$options['com_title_font']['font_italic'] = isset($options['com_title_font']['font_italic']) ? $options['com_title_font']['font_italic'] : 0;
			$options['com_title_font']['font_decoration'] = isset($options['com_title_font']['font_decoration']) ? $options['com_title_font']['font_decoration'] : '';
			$options['com_content_font']['font_family'] = isset($options['com_content_font']['font_family']) ? $options['com_content_font']['font_family'] : 'Helvetica';
			$options['com_content_font']['font_size'] = isset($options['com_content_font']['font_size']) ? $options['com_content_font']['font_size'] : 16;
			$options['com_content_font']['font_bold'] = isset($options['com_content_font']['font_bold']) ? $options['com_content_font']['font_bold'] : 0;
			$options['com_content_font']['font_italic'] = isset($options['com_content_font']['font_italic']) ? $options['com_content_font']['font_italic'] : 0;
			$options['com_content_font']['font_decoration'] = isset($options['com_content_font']['font_decoration']) ? $options['com_content_font']['font_decoration'] : '';
			$options['com_subtitle_font']['font_family'] = isset($options['com_subtitle_font']['font_family']) ? $options['com_subtitle_font']['font_family'] : 'Helvetica';
			$options['com_subtitle_font']['font_size'] = isset($options['com_subtitle_font']['font_size']) ? $options['com_subtitle_font']['font_size'] : 16;
			$options['com_subtitle_font']['font_bold'] = isset($options['com_subtitle_font']['font_bold']) ? $options['com_subtitle_font']['font_bold'] : 0;
			$options['com_subtitle_font']['font_italic'] = isset($options['com_subtitle_font']['font_italic']) ? $options['com_subtitle_font']['font_italic'] : 0;
			$options['com_subtitle_font']['font_decoration'] = isset($options['com_subtitle_font']['font_decoration']) ? $options['com_subtitle_font']['font_decoration'] : '';
			$options['com_button_font']['font_family'] = isset($options['com_button_font']['font_family']) ? $options['com_button_font']['font_family'] : 'Helvetica';
			$options['com_button_font']['font_size'] = isset($options['com_button_font']['font_size']) ? $options['com_button_font']['font_size'] : 16;
			$options['com_button_font']['font_bold'] = isset($options['com_button_font']['font_bold']) ? $options['com_button_font']['font_bold'] : 0;
			$options['com_button_font']['font_italic'] = isset($options['com_button_font']['font_italic']) ? $options['com_button_font']['font_italic'] : 0;
			$options['com_button_font']['font_decoration'] = isset($options['com_button_font']['font_decoration']) ? $options['com_button_font']['font_decoration'] : '';
			return $options;
		}

		function arm_profile_color_options_outside_func($options = array()) {
			$content = '';
			$tab_title_color = ($options["tab_link_color"] != '') ? $options["tab_link_color"] : '#000000';
			$tab_title_hover_color = ($options["tab_link_hover_color"] != '') ? $options["tab_link_hover_color"] : '#0c7cd5';
			$button_color = ($options["button_color"] != '') ? $options["button_color"] : '#0c7cd5';
			$button_font_color = ($options["button_font_color"] != '') ? $options["button_font_color"] : '#ffffff';
			$content .='
			<div class="arm_pdtemp_color_opts">
				<span class="arm_temp_form_label">'.__('Tab Title Color', ARM_COMMUNITY_TEXTDOMAIN).'</span>
				<label class="arm_colorpicker_label arm_custom_colorpicker_label" style="background-color:'.$tab_title_color.'">
					<input type="text" name="template_options[tab_link_color]" id="arm_profile_tab_title_color" class="arm_colorpicker" value="'.$tab_title_color.'" />
				</label>
			</div>
			<div class="arm_pdtemp_color_opts">
				<span class="arm_temp_form_label">'.__('Tab Title Hover Color', ARM_COMMUNITY_TEXTDOMAIN).'</span>
				<label class="arm_colorpicker_label arm_custom_colorpicker_label" style="background-color:'.$tab_title_hover_color.'">
					<input type="text" name="template_options[tab_link_hover_color]" id="arm_profile_tab_title_hover_color" class="arm_colorpicker" value="'.$tab_title_hover_color.'" />
				</label>
			</div>
			<div class="arm_pdtemp_color_opts">
				<span class="arm_temp_form_label">'.__('Button Font Color', ARM_COMMUNITY_TEXTDOMAIN).'</span>
				<label class="arm_colorpicker_label arm_custom_colorpicker_label" style="background-color:'.$button_font_color.'">
					<input type="text" name="template_options[button_font_color]" id="arm_profile_button_font_color" class="arm_colorpicker" value="'.$button_font_color.'" />
				</label>
			</div>
			<div class="arm_pdtemp_color_opts">
				<span class="arm_temp_form_label">'.__('Button bg Color', ARM_COMMUNITY_TEXTDOMAIN).'</span>
				<label class="arm_colorpicker_label arm_custom_colorpicker_label" style="background-color:'.$button_color.'">
					<input type="text" name="template_options[button_color]" id="arm_profile_button_background_color" class="arm_colorpicker" value="'.$button_color.'" />
				</label>
			</div>';
			echo $content;
		}

		function arm_profile_font_settings_outside_func($options = array()) {
			global $arm_member_forms;
			$font_content='<div class="arm_com_profile_font_settings_popup_title">'.__("Community Font Settings", ARM_COMMUNITY_TEXTDOMAIN);
			$font_content .= '<span class="arm_profile_settings_popup_close_button" data-id="arm_profile_font_settings_popup_div"></span></div>';
			$fontOptions = array(
				'com_tab_font' => __('Community Tab Font', MEMBERSHIP_TXTDOMAIN),
				'com_title_font' => __('Community Title Font', MEMBERSHIP_TXTDOMAIN),
				'com_subtitle_font' => __('Community Sub Title Font', MEMBERSHIP_TXTDOMAIN),
				'com_content_font' => __('Community Content Font', MEMBERSHIP_TXTDOMAIN),
				'com_button_font' => __('Community Button Font', MEMBERSHIP_TXTDOMAIN),
			);
			foreach ($fontOptions as $key => $value) {
				$font_content .='<div class="arm_temp_font_opts_box"><div class="arm_opt_label">'.$value.'</div><div class="arm_temp_font_opts">';
				$font_family = ($_GET['action'] == 'edit_profile' && $options[$key]['font_family'] != '' ) ? $options[$key]["font_family"] : 'Helvetica';
				$font_content .= '<input type="hidden" id="arm_template_font_family_'.$key.'" name="template_options['.$key.'][font_family]" value="'. $font_family .'"/><dl class="arm_selectbox column_level_dd"><dt><span>'.$font_family.'</span><input type="text" style="display:none;" value="" class="arm_autocomplete" readonly="readonly"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt><dd><ul data-id="arm_template_font_family_'.$key.'">'.$arm_member_forms->arm_fonts_list().'</ul></dd></dl>';
				$fontSize = $options[$key]['font_size'];
				$font_content .= '<input type="hidden" id="arm_template_font_size_'.$key.'" name="template_options['.$key.'][font_size]" value="'.$fontSize.'"/><dl class="arm_selectbox column_level_dd"><dt style="width:75px;min-width: 75px;"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" readonly="readonly"  /><i class="armfa armfa-caret-down armfa-lg"></i></dt><dd><ul data-id="arm_template_font_size_'.$key.'">';
				for ($i = 8; $i < 41; $i++) {
					$font_content .= '<li data-label="'.$i.' px" data-value="'.$i.'">'.$i.' px</li>';
				}
				$font_content .= '</ul></dd></dl><div class="arm_font_style_options arm_template_font_style_options">';
				$bold_cls = isset($options[$key]['font_bold']) && $options[$key]['font_bold'] == 1 ? 'arm_style_active' : '';
				$italic_cls = isset($options[$key]['font_italic']) && $options[$key]['font_italic'] == 1 ? 'arm_style_active' : '';
				$underline_cls = isset($options[$key]['font_decoration']) && $options[$key]['font_decoration'] == 'underline' ? 'arm_style_active' : '';
				$strike_cls = isset($options[$key]['font_decoration']) && $options[$key]['font_decoration'] == 'line-through' ? 'arm_style_active' : '';
				$font_content .= '<label class="arm_font_style_label '.$bold_cls.'" data-value="bold" data-field="arm_template_font_bold_'.$key.'"><i class="armfa armfa-bold"></i></label><input type="hidden" name="template_options['.$key.'][font_bold]" id="arm_template_font_bold_'.$key.'" class="arm_template_font_bold_'.$key.'" value="'.$options[$key]["font_bold"].'" /><label class="arm_font_style_label '.$italic_cls.'" data-value="italic" data-field="arm_template_font_italic_'.$key.'"><i class="armfa armfa-italic"></i></label><input type="hidden" name="template_options['.$key.'][font_italic]" id="arm_template_font_italic_'.$key.'" class="arm_template_font_italic_'.$key.'" value="'.$options[$key]["font_italic"].'" /><label class="arm_font_style_label arm_decoration_label '.$underline_cls.'" data-value="underline" data-field="arm_template_font_decoration_'.$key.'"><i class="armfa armfa-underline"></i></label><label class="arm_font_style_label arm_decoration_label '.$strike_cls.'" data-value="line-through" data-field="arm_template_font_decoration_'.$key.'"><i class="armfa armfa-strikethrough"></i></label><input type="hidden" name="template_options['.$key.'][font_decoration]" id="arm_template_font_decoration_'.$key.'" class="arm_template_font_decoration_'.$key.'" value="'.$options[$key]["font_decoration"].'" /></div></div></div>';
			}
			echo $font_content;
		}

		function arm_get_profile_directory_template_syte($tempOptions, $tempWrapperClass) {
			$tempWrapperClass = $tempWrapperClass;
			$tempstyle = "
			.arm_rating_display > input:checked ~ label,
			.arm_rating_display:not(:checked),
			.arm_rating_display:not(:checked) ~ label { color: {$tempOptions['button_color']} !important; }
			.arm_rating > input:checked ~ label,
			.arm_rating:not(:checked) > label:hover,
			.arm_rating:not(:checked) > label:hover ~ label { color: {$tempOptions['button_color']} !important; }
			$tempWrapperClass .arm_com_friend_container .arm_com_frd_tab_ul .arm_com_frd_tab_li a, 
			$tempWrapperClass .arm_com_message_container .arm_com_msg_tab_ul .arm_com_msg_tab_li a,
			$tempWrapperClass .arm_com_follow_wrapper a,
			$tempWrapperClass .arm_profile_tab_menu_container a {
				color: {$tempOptions['tab_link_color']} !important;
				{$tempOptions['com_tab_font']['font']}
				box-shadow: none !important;
			}
			$tempWrapperClass .arm_com_follow_wrapper .arm_com_follow_count {
				color: {$tempOptions['tab_link_color']} !important;
				{$tempOptions['com_tab_font']['font']}
			}
			$tempWrapperClass .arm_com_friend_container .arm_com_frd_tab_li:hover a,
			$tempWrapperClass .arm_com_message_container .arm_com_msg_tab_ul .arm_com_msg_tab_li:hover a,
			$tempWrapperClass .arm_com_follow_wrapper a:hover,
			$tempWrapperClass .arm_com_follow_wrapper a:focus,
			$tempWrapperClass .arm_profile_tab_menu_container a:hover,
			$tempWrapperClass .arm_profile_tab_menu_container a:focus,
			$tempWrapperClass .arm_profile_tab_menu_container li.active a,
			$tempWrapperClass .arm_com_friend_container .arm_com_frd_tab_ul .arm_com_frd_tab_active a,
			$tempWrapperClass .arm_com_message_container .arm_com_msg_tab_ul .arm_com_msg_tab_active a,
			$tempWrapperClass .arm_com_message_container .arm_com_msg_tab_ul .arm_com_msg_tab_li .arm_com_msg_tab_a.compose:focus,
			$tempWrapperClass .arm_com_message_container .arm_com_msg_tab_ul .arm_com_msg_tab_li .arm_com_msg_tab_a.compose:active, 
			$tempWrapperClass #arm_com_give_review:hover,
			$tempWrapperClass #arm_com_give_review:focus,
			$tempWrapperClass #arm_com_give_review:active,
			$tempWrapperClass .arm_com_follow_wrapper a:hover .arm_com_follow_count,
			$tempWrapperClass .arm_com_follow_wrapper a:focus .arm_com_follow_count {
				color: {$tempOptions['tab_link_hover_color']} !important;
				{$tempOptions['com_tab_font']['font']}
				box-shadow: none !important;
			}
			$tempWrapperClass .arm_com_friend_container ul, 
			$tempWrapperClass .arm_com_message_container ul,
			$tempWrapperClass .arm_com_wall_post_wrapper_title,
			$tempWrapperClass .arm_com_post_wrapper_title,
			$tempWrapperClass .arm_follow_friendship_belt,
			$tempWrapperClass .arm_review_title,
			$tempWrapperClass .arm_review_title .arm_review_title_text, 
			$tempWrapperClass #arm_com_give_review,
			$tempWrapperClass .arm_com_activity_title_wrapper {
				color: {$tempOptions['tab_link_color']} !important; 
				{$tempOptions['com_tab_font']['font']}
			}
			$tempWrapperClass .arm_com_friendship_approve_btn,
			$tempWrapperClass .arm_com_post_btn,
			$tempWrapperClass .arm_com_post_btn:hover,
			$tempWrapperClass .arm_com_post_btn:focus,
			$tempWrapperClass .arm_com_post_btn:active,
			$tempWrapperClass .arm_com_post_edit_btn,
			$tempWrapperClass .arm_com_post_edit_btn:hover,
			$tempWrapperClass .arm_com_post_edit_btn:focus,
			$tempWrapperClass .arm_com_post_edit_btn:active,
			$tempWrapperClass .arm_com_post_read_more_nav,
			$tempWrapperClass .arm_com_post_read_more_nav:hover,
			$tempWrapperClass .arm_com_post_read_more_nav:focus,
			$tempWrapperClass .arm_com_post_read_more_nav:active,
			$tempWrapperClass .arm_com_friendship_cancel_btn,
			$tempWrapperClass .arm_com_friendship_cancel_btn:hover,
			$tempWrapperClass .arm_com_friendship_cancel_btn:active,
			$tempWrapperClass .arm_com_friendship_cancel_btn:focus,
			$tempWrapperClass .arm_com_follow_btn, 
			$tempWrapperClass .arm_com_follow_btn:focus,
			$tempWrapperClass .arm_com_follow_btn:active,
			$tempWrapperClass .arm_com_follow_btn:hover,
			$tempWrapperClass .arm_com_unfollow_btn,
			$tempWrapperClass .arm_com_unfollow_btn:focus,
			$tempWrapperClass .arm_com_unfollow_btn:active,
			$tempWrapperClass .arm_com_unfollow_btn:hover,
			.arm_com_review_button_wrapper .arm_com_review_btn, 
			.arm_com_review_button_wrapper .arm_com_review_btn:hover,
			.arm_com_review_button_wrapper .arm_com_review_btn:focus, 
			.arm_com_review_button_wrapper .arm_com_review_btn:active, 
			.arm_com_message_send_btn,
			.arm_com_message_send_btn:hover,
			.arm_com_message_send_btn:focus,
			.arm_com_message_send_btn:active,
			.arm_com_main_tab_btn,
			.arm_com_msg_compose_column .arm_com_message_send_btn, 
			.arm_com_msg_compose_column .arm_com_message_send_btn:hover, 
			.arm_com_msg_compose_column .arm_com_message_send_btn:focus, 
			.arm_com_msg_compose_column .arm_com_message_send_btn:active, 
			$tempWrapperClass .arm_com_block_user,
			$tempWrapperClass .arm_com_block_user:hover,
			$tempWrapperClass .arm_com_block_user:focus,
			$tempWrapperClass .arm_com_block_user:active,
			$tempWrapperClass .arm_com_friendship_send_btn,
			$tempWrapperClass .arm_com_friendship_send_btn:hover
			$tempWrapperClass .arm_com_friendship_send_btn:focus
			$tempWrapperClass .arm_com_friendship_send_btn:active {
				background-color: {$tempOptions['button_color']} !important;
				color: {$tempOptions['button_font_color']} !important; 
				{$tempOptions['com_button_font']['font']}
				box-shadow: none !important;
			}
			$tempWrapperClass .arm_blocked_user {
				background-color: {$tempOptions['button_color']} !important;
				color: {$tempOptions['button_font_color']} !important; 
				{$tempOptions['com_content_font']['font']}
			}
			$tempWrapperClass a.arm_blocked_user_remove {
				background-color: {$tempOptions['button_font_color']} !important; 
				color: {$tempOptions['button_color']} !important;
				{$tempOptions['com_content_font']['font']}
			}
			$tempWrapperClass .arm_com_post_date,
			$tempWrapperClass .arm_user_name {
				color: {$tempOptions['subtitle_color']} !important; 
				{$tempOptions['com_subtitle_font']['font']}
			}
			$tempWrapperClass .arm_com_post_content, 
			$tempWrapperClass .arm_com_review_box.arm_no_review,
			$tempWrapperClass .arm_com_activity_user_title_box, 
			$tempWrapperClass .arm_com_reivew_desc, 
			$tempWrapperClass .arm_no_review_msg {
				color: {$tempOptions['subtitle_color']} !important;
				{$tempOptions['com_subtitle_font']['font']}
			}
			$tempWrapperClass .arm_com_post_no_msg,
			$tempWrapperClass .arm_com_activity_no_msg,
			$tempWrapperClass .arm_com_msg_no_records, 
			$tempWrapperClass .arm_com_no_message, 
			.arm_follow_no_msg_record {
				color: {$tempOptions['content_font_color']} !important;
				{$tempOptions['com_content_font']['font']}
			}
			$tempWrapperClass .arm_com_message_convo_msg,
			$tempWrapperClass .arm_com_message_convo_time,
			$tempWrapperClass .arm_sender_time {
				color: {$tempOptions['content_font_color']} !important;
				{$tempOptions['com_subtitle_font']['font']}
			}
			
			$tempWrapperClass .arm_com_activity_user_title_box a {
				color: {$tempOptions['title_color']} !important; 
			}
			$tempWrapperClass .arm_com_activity_user_title_box a:hover {
				color: {$tempOptions['tab_link_hover_color']} !important;
			}
			$tempWrapperClass .arm_post_form_container .arm_post_title input[type='text'] {
				{$tempOptions['com_title_font']['font']}
			}
			$tempWrapperClass .arm_post_form_container .arm_com_post_description textarea, 
			.arm_com_msg_compose_column input[type='text'],
			.arm_com_msg_compose_column textarea,
			.arm_com_review_rating_error, .arm_com_review_title_error, .arm_com_review_desc_error,
			$tempWrapperClass .arm_com_block_username_error,
			$tempWrapperClass .arm_com_msg_block_error,
			$tempWrapperClass .arm_com_msg_block_success {
				{$tempOptions['com_content_font']['font']}
			}
			$tempWrapperClass .arm_post_form_container .arm_com_post_title_error,
			$tempWrapperClass .arm_post_form_container .arm_com_post_desc_error,
			$tempWrapperClass .arm_post_form_container .arm_post_add_success,
			$tempWrapperClass .arm_post_form_container .arm_post_add_error {
				{$tempOptions['com_content_font']['font']}
			}
			$tempWrapperClass a.arm_com_user_friend_name, 
			$tempWrapperClass .arm_post_title .arm_com_post_user_title_box strong,
			$tempWrapperClass .arm_com_post_content strong, 
			$tempWrapperClass .arm_com_reivew_title, 
			$tempWrapperClass .arm_sender_name,
			.arm_com_msg_compose_column_label {
				{$tempOptions['com_title_font']['font']}
				color: {$tempOptions['title_color']} !important;
			}
			$tempWrapperClass a.arm_com_user_friend_name:hover,
			$tempWrapperClass .arm_com_msg_content_sender:hover .arm_sender_name,
			$tempWrapperClass .arm_com_msg_content_sender.active .arm_sender_name {
				{$tempOptions['com_title_font']['font']}
				color: {$tempOptions['tab_link_hover_color']} !important;
				box-shadow: none;
			}
			.arm_popup_community_form .arm_follow_user_name a {
				{$tempOptions['com_subtitle_font']['font']}
				color: {$tempOptions['subtitle_color']} !important;
			}
			.arm_popup_community_form .arm_follow_user_name a:hover {
				{$tempOptions['com_subtitle_font']['font']}
				color: {$tempOptions['tab_link_hover_color']} !important;
			}
			$tempWrapperClass .arm_user_name a {
				{$tempOptions['com_subtitle_font']['font']}
				color: {$tempOptions['title_color']} !important;
			}
			$tempWrapperClass .arm_user_name a:hover {
				{$tempOptions['com_subtitle_font']['font']}
				color: {$tempOptions['tab_link_hover_color']} !important;
			}
			.popup_header_text .arm_form_field_label_wrapper_text {
				font-family:{$tempOptions['title_font']['font_family']}, sans-serif, 'Trebuchet MS' !important;
				font-size:{$tempOptions['title_font']['font_size']}px !important;
			}
			.arm_com_message_send_btn.active #arm_form_loader,
			.arm_com_review_btn #arm_form_loader,
			.arm_com_block_user #arm_form_loader {
				fill: {$tempOptions['button_font_color']} !important; 
			}";
			return $tempstyle;
		}

		function arm_change_profile_directory_style_outside_func($content='', $opts = array()) {
			global $arm_member_forms;
			$tempOptions = $opts['template_options'];
			if($opts['type'] == 'profile') {
				$armcomtempFontFamilys = array();
				$fontOptions = array('com_tab_font', 'com_subtitle_font', 'com_title_font', 'com_content_font', 'com_button_font');
				foreach ($fontOptions as $key) {
					$tfont_family = (isset($tempOptions[$key]['font_family'])) ? $tempOptions[$key]['font_family'] : "Helvetica";
					$armcomtempFontFamilys[] = $tfont_family;
					$tfont_size = (isset($tempOptions[$key]['font_size'])) ? $tempOptions[$key]['font_size'] : "";
					$tfont_bold = (isset($tempOptions[$key]['font_bold']) && $tempOptions[$key]['font_bold'] == '1') ? "font-weight: bold !important;" : "font-weight: normal !important;";
					$tfont_italic = (isset($tempOptions[$key]['font_italic']) && $tempOptions[$key]['font_italic'] == '1') ? "font-style: italic !important;" : "font-style: normal !important;";
					$tfont_decoration = (!empty($tempOptions[$key]['font_decoration'])) ? "text-decoration: ".$tempOptions[$key]['font_decoration']." !important;" : "text-decoration: none !important;";
					$tempOptions[$key]['font'] = "font-family: {$tfont_family}, sans-serif, 'Trebuchet MS' !important;font-size: {$tfont_size}px !important;{$tfont_bold}{$tfont_italic}{$tfont_decoration}";
					$tempOptions[$key]['font_family'] = "font-family:{$tfont_family}, sans-serif, 'Trebuchet MS' !important;";
					$tempOptions[$key]['font_size'] = "font-size:{$tfont_size}px !important;";
				}
				$tempWrapperClass = ".arm_template_wrapper_1";
				$tempstyle = "";
				if( isset($tempOptions['tab_link_color']) ) {
					$tempstyle = $this->arm_get_profile_directory_template_syte($tempOptions, $tempWrapperClass);
				}
				$gFontUrl = $arm_member_forms->arm_get_google_fonts_url($armcomtempFontFamilys);
				if (!empty($gFontUrl)) {
					$content .= '<link id="google-font-com" rel="stylesheet" type="text/css" href="' . $gFontUrl . '" />';
				}
				$content .= '<style type="text/css">'.$tempstyle.'</style>';
			}
			return $content;
		}

		function arm_change_dummy_profile_directory_style_outside_func($content='', $opts = array()) {
			global $arm_member_forms;
			$tempOptions = $opts[2];
			$tempstyle = '';
			$armcomtempFontFamilys = array();
			$fontOptions = array('com_tab_font', 'com_subtitle_font', 'com_title_font', 'com_content_font', 'com_button_font');
			foreach ($fontOptions as $key) {
				$tfont_family = (isset($tempOptions[$key]['font_family'])) ? $tempOptions[$key]['font_family'] : "Helvetica";
				$armcomtempFontFamilys[] = $tfont_family;
				$tfont_size = (isset($tempOptions[$key]['font_size'])) ? $tempOptions[$key]['font_size'] : "";
				$tfont_bold = (isset($tempOptions[$key]['font_bold']) && $tempOptions[$key]['font_bold'] == '1') ? "font-weight: bold !important;" : "font-weight: normal !important;";
				$tfont_italic = (isset($tempOptions[$key]['font_italic']) && $tempOptions[$key]['font_italic'] == '1') ? "font-style: italic !important;" : "font-style: normal !important;";
				$tfont_decoration = (!empty($tempOptions[$key]['font_decoration'])) ? "text-decoration: ".$tempOptions[$key]['font_decoration']." !important;" : "text-decoration: none !important;";
				$tempOptions[$key]['font'] = "font-family: {$tfont_family}, sans-serif, 'Trebuchet MS' !important;font-size: {$tfont_size}px !important;{$tfont_bold}{$tfont_italic}{$tfont_decoration}";
				$tempOptions[$key]['font_family'] = "font-family:{$tfont_family}, sans-serif, 'Trebuchet MS' !important;";
				$tempOptions[$key]['font_size'] = "font-size:{$tfont_size}px !important;";
			}
			$tempWrapperClass = ".arm_template_wrapper";
			$tempstyle = "";
			if(isset($tempOptions['tab_link_color'])) {
				$tempstyle = $this->arm_get_profile_directory_template_syte($tempOptions, $tempWrapperClass);
			}
			$gFontUrl = $arm_member_forms->arm_get_google_fonts_url($armcomtempFontFamilys);
			if (!empty($gFontUrl)) {
				$content .= '<link id="google-font" rel="stylesheet" type="text/css" href="' . $gFontUrl . '" />';
			}
			$content .= '<style type="text/css">'.$tempstyle.'</style>';
			return $content;
		}

		function arm_profile_default_options_func($options) {
			global $arm_community_setting;
			/*echo "<pre>";
			print_r($arm_community_setting);
			exit;*/
			$options['arm_com']['follow_section'] = isset($options['arm_com']['follow_section']) ? $options['arm_com']['follow_section'] : 0;
			$options['arm_com']['friendship_button'] = isset($options['arm_com']['friendship_button']) ? $options['arm_com']['friendship_button'] : 0;
			$options['arm_com']['friendship_section'] = isset($options['arm_com']['friendship_section']) ? $options['arm_com']['friendship_section'] : 0;
			$options['arm_com']['message'] = isset($options['arm_com']['message']) ? $options['arm_com']['message'] : 0;
			$options['arm_com']['post'] = isset($options['arm_com']['post']) ? $options['arm_com']['post'] : 0;
			$options['arm_com']['review'] = isset($options['arm_com']['review']) ? $options['arm_com']['review'] : 0;
			$options['arm_com']['activity'] = isset($options['arm_com']['activity']) ? $options['arm_com']['activity'] : 0;
			$options['arm_com']['section_tabwise'] = isset($options['arm_com']['section_tabwise']) ? $options['arm_com']['section_tabwise'] : 0;

			if(isset($arm_community_setting->arm_com_settings['arm_com_post_wall']) && $arm_community_setting->arm_com_settings['arm_com_post_wall']==1)
			{
				$options['arm_com']['wall_post'] = isset($options['arm_com']['wall_post']) ? $options['arm_com']['wall_post'] : 0;
			}
			else {
				$options['arm_com']['wall_post'] = 0;
			}
			return $options;
		}

		function arm_profile_setting_section_func($options) {
			global $arm_community_follow, $arm_community_friendship, $arm_community_message, $arm_community_post, $arm_community_review, $arm_community_activity;
			$follow_section = isset($options['arm_com']['follow_section']) ? $options['arm_com']['follow_section'] : 0;
			$friendship_button = isset($options['arm_com']['friendship_button']) ? $options['arm_com']['friendship_button'] : 0;
			$friendship_section = isset($options['arm_com']['friendship_section']) ? $options['arm_com']['friendship_section'] : 0;
			$wall_post = isset($options['arm_com']['wall_post']) ? $options['arm_com']['wall_post'] : 0;
			$message = isset($options['arm_com']['message']) ? $options['arm_com']['message'] : 0;
			$post = isset($options['arm_com']['post']) ? $options['arm_com']['post'] : 0;
			$review = isset($options['arm_com']['review']) ? $options['arm_com']['review'] : 0;
			$activity = isset($options['arm_com']['activity']) ? $options['arm_com']['activity'] : 0;
			$section_tabwise = isset($options['arm_com']['section_tabwise']) ? $options['arm_com']['section_tabwise'] : 0;
			?>
			<li>
				<a href="javascript:void(0)" class="arm_accordion_header"><?php _e('Community Settings', ARM_COMMUNITY_TEXTDOMAIN); $gf_tooltip = __('Select Community Settings.', ARM_COMMUNITY_TEXTDOMAIN); ?><i></i></a>
				<div id="five" class="arm_accordion">
					<div class="arm_profile_other_settings">
						<?php
							if ($arm_community_follow->arm_com_follow_allow() || $arm_community_friendship->arm_com_friendship_allow() || $arm_community_message->arm_com_message_allow() || $arm_community_post->arm_com_post_allow() || $arm_community_review->arm_com_review_allow() || $arm_community_activity->arm_com_activity_allow()) {

								if ($arm_community_follow->arm_com_follow_allow()) { ?> 
								<div class="arm_profile_setting_switch_div">
									<label for="arm_com_follow_section"><?php _e('Display Follow Section?', ARM_COMMUNITY_TEXTDOMAIN); ?></label>
									<div class="armswitch arm_profile_setting_switch">
										<input type="checkbox" id="arm_com_follow_section" value="1" class="armswitch_input arm_com_switch" name="template_options[arm_com][follow_section]" <?php checked($follow_section, 1); ?>/>
										<label for="arm_com_follow_section" class="armswitch_label"></label>
									</div>
								</div>
								<?php } if ($arm_community_friendship->arm_com_friendship_allow()) { ?>
								<div class="arm_profile_setting_switch_div">
									<label for="arm_com_display_friend_button">
										<?php _e('Display Friendship Button?', ARM_COMMUNITY_TEXTDOMAIN); ?></label>
									<div class="armswitch arm_profile_setting_switch">
										<input type="checkbox" id="arm_com_display_friend_button" value="1" class="armswitch_input arm_com_switch" name="template_options[arm_com][friendship_button]" <?php checked($friendship_button, 1); ?>/>
										<label for="arm_com_display_friend_button" class="armswitch_label"></label>
									</div>
								</div>
								<div class="arm_profile_setting_switch_div">
									<label for="arm_com_display_friend_section">
										<?php _e('Display Friendship Section?', ARM_COMMUNITY_TEXTDOMAIN); ?>
									</label>
									<div class="armswitch arm_profile_setting_switch">
										<input type="checkbox" id="arm_com_display_friend_section" value="1" class="armswitch_input arm_com_switch" name="template_options[arm_com][friendship_section]" <?php checked($friendship_section, 1); ?>/>
										<label for="arm_com_display_friend_section" class="armswitch_label"></label>
									</div>
								</div>
								<?php } if ($arm_community_post->arm_com_post_wall_allow()) {?>
								<div class="arm_profile_setting_switch_div">
									<label for="arm_com_display_wall_post">
										<?php _e('Display News Feed Section?', ARM_COMMUNITY_TEXTDOMAIN); ?>
									</label>
									<div class="armswitch arm_profile_setting_switch">
										<input type="checkbox" id="arm_com_display_wall_post" value="1" class="armswitch_input arm_com_switch" name="template_options[arm_com][wall_post]" <?php checked($wall_post, 1); ?>/>
										<label for="arm_com_display_wall_post" class="armswitch_label"></label>
									</div>
								</div>
								<?php } if ($arm_community_message->arm_com_message_allow()) { ?>
								<div class="arm_profile_setting_switch_div">
									<label for="arm_com_display_message_section">
										<?php _e('Display Message Section?', ARM_COMMUNITY_TEXTDOMAIN); ?>
									</label>
									<div class="armswitch arm_profile_setting_switch">
										<input type="checkbox" id="arm_com_display_message_section" value="1" class="armswitch_input arm_com_switch" name="template_options[arm_com][message]" <?php checked($message, 1); ?>/>
										<label for="arm_com_display_message_section" class="armswitch_label"></label>
									</div>
								</div>
								<?php } if ($arm_community_post->arm_com_post_allow()) { ?>
								<div class="arm_profile_setting_switch_div">
									<label for="arm_com_display_post_section">
										<?php _e('Display Post Section?', ARM_COMMUNITY_TEXTDOMAIN); ?>
									</label>
									<div class="armswitch arm_profile_setting_switch">
										<input type="checkbox" id="arm_com_display_post_section" value="1" class="armswitch_input arm_com_switch" name="template_options[arm_com][post]" <?php checked($post, 1); ?>/>
										<label for="arm_com_display_post_section" class="armswitch_label"></label>
									</div>
								</div>
								<?php } if ($arm_community_review->arm_com_review_allow()) { ?>
								<div class="arm_profile_setting_switch_div">
									<label for="arm_com_display_review_section">
										<?php _e('Display Review Section?', ARM_COMMUNITY_TEXTDOMAIN); ?>
									</label>
									<div class="armswitch arm_profile_setting_switch">
										<input type="checkbox" id="arm_com_display_review_section" value="1" class="armswitch_input arm_com_switch" name="template_options[arm_com][review]" <?php checked($review, 1); ?>/>
										<label for="arm_com_display_review_section" class="armswitch_label"></label>
									</div>
								</div>
								<?php } if ($arm_community_activity->arm_com_activity_allow()) { ?>
								<div class="arm_profile_setting_switch_div">
									<label for="arm_com_display_activity_section">
										<?php _e('Display Activity Section?', ARM_COMMUNITY_TEXTDOMAIN); ?>
									</label>
									<div class="armswitch arm_profile_setting_switch">
										<input type="checkbox" id="arm_com_display_activity_section" value="1" class="armswitch_input arm_com_switch" name="template_options[arm_com][activity]" <?php checked($activity, 1); ?>/>
										<label for="arm_com_display_activity_section" class="armswitch_label"></label>
									</div>
								</div>
								<?php } ?>
								<div class="arm_profile_setting_switch_div">
									<label for="arm_com_display_section_tabwise_section">
										<?php _e('Display Section In Tab?', ARM_COMMUNITY_TEXTDOMAIN); ?>
									</label>
									<div class="armswitch arm_profile_setting_switch">
										<input type="checkbox" id="arm_com_display_section_tabwise_section" value="1" class="armswitch_input arm_com_switch" name="template_options[arm_com][section_tabwise]" <?php checked($section_tabwise, 1); ?>/>
										<label for="arm_com_display_section_tabwise_section" class="armswitch_label"></label>
									</div>
								</div>
						<?php
							}
							else {
							?>
								<div class="arm_profile_setting_switch_div">
									<label for="arm_com_display_section_tabwise_section"><a href="<?php echo admin_url("admin.php?page=arm_community_settings") ?>" target="_blank"><?php _e('Click here to enable community settings', ARM_COMMUNITY_TEXTDOMAIN); ?></a></label>
								</div>
							<?php
							}
						?>
					</div>
				</div>
			</li>
			<?php
		}

		function arm_profile_dummy_content_before_fields_func($content, $arm_com_args) {
			global $arm_community_friendship, $arm_community_follow;
			$arm_com_default = $this->arm_profile_default_options_func(array());
			$arm_options = isset($arm_com_args['2']) ? $arm_com_args['2'] : array();
			$content = '';
			if (isset($arm_options['arm_com']) && !empty($arm_options['arm_com'])) {
				$arm_args = shortcode_atts($arm_com_default['arm_com'], $arm_options['arm_com']);
				$content .= $this->arm_profile_before_content($arm_args);
			}
			return $content;
        }

		function arm_profile_dummy_content_after_fields_func($content, $arm_com_args) {
			global $arm_community_friendship, $arm_community_message, $arm_community_post, $arm_community_review;
			$arm_com_default = $this->arm_profile_default_options_func(array());
			$arm_options = isset($arm_com_args['2']) ? $arm_com_args['2'] : array();
			$content = '';
			if (isset($arm_options['arm_com']) && !empty($arm_options['arm_com'])) {
				$arm_args = shortcode_atts($arm_com_default['arm_com'], $arm_options['arm_com']);
				$content .= $this->arm_profile_after_content($arm_args);
			}
			return $content;
		}

		function arm_profile_content_before_fields_func($content, $arm_com_args, $user_id) {
			global $arm_community_friendship, $arm_community_follow;
			$arm_com_default = $this->arm_profile_default_options_func(array());
			$arm_options = isset($arm_com_args['2']['template_options']) ? $arm_com_args['2']['template_options'] : array();
			$content = '';
			if (isset($arm_options['arm_com']) && !empty($arm_options['arm_com'])) {
				$arm_args = shortcode_atts($arm_com_default['arm_com'], $arm_options['arm_com']);
				$content .= $this->arm_profile_before_content($arm_args);
			}
			return $content;
		}

		function arm_profile_content_after_fields_func($content, $arm_com_args, $user_id) {
			global $arm_community_friendship, $arm_community_message, $arm_community_post, $arm_community_review;
			$arm_com_default = $this->arm_profile_default_options_func(array());
			$arm_options = isset($arm_com_args['2']['template_options']) ? $arm_com_args['2']['template_options'] : array();
			$content = '';
			if (isset($arm_options['arm_com']) && !empty($arm_options['arm_com'])) {
				$arm_args = shortcode_atts($arm_com_default['arm_com'], $arm_options['arm_com']);
				$content .= $this->arm_profile_after_content($arm_args);
			}
			return $content;
		}

		function arm_profile_before_content($arm_args) {
				global $arm_community_setting, $arm_community_follow, $arm_community_friendship, $arm_community_message, $arm_community_review, $arm_community_post, $arm_community_activity;
				$arm_com_settings = $arm_community_setting->arm_com_settings;
				$arm_content = $loggedin_user_id = $arm_belt_cnt = '';
				$arm_profile_tab_container_class = 'arm_profile_tab_container_top_60';
				$user_id = $arm_belt_flag = 0;
				$loggedin_user_id = get_current_user_id();

				if ($arm_community_setting->arm_com_is_profile_editor()) {
					$loggedin_user_id = $user_id = 0;
				}
				else {
					$user_data = $arm_community_setting->arm_com_profile_get_user_id();
					$user_data_arr = array_shift($user_data);
					$user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0;
				}

				if($arm_community_follow->arm_com_follow_allow() && !empty($arm_args["follow_section"]) ) {
					$arm_belt_flag = 1;
					$arm_profile_tab_container_class = '';
					$arm_belt_cnt .= do_shortcode('[arm_community_follow_display]');
				}

				if($arm_community_friendship->arm_com_friendship_allow() && $arm_args['friendship_button'] && ($loggedin_user_id != $user_id || $arm_community_setting->arm_com_is_profile_editor()) ) {					
					$arm_belt_flag = 1;
					$arm_profile_tab_container_class = '';
					$arm_belt_cnt .= do_shortcode('[arm_community_friendship]');
				}

				if ($arm_belt_flag == 1) {
					$arm_content .= "<div class='arm_follow_friendship_belt'>" . $arm_belt_cnt . "</div>";
				}

				$arm_profile_tab_array = array();

				if ( !empty($arm_com_settings["arm_com_post_wall"]) && $user_id == $loggedin_user_id) {
					$arm_profile_tab_array["wall_post"] = !empty($arm_com_settings["arm_wall_section_lbl"]) ? $arm_com_settings["arm_wall_section_lbl"] : __('News Feed', ARM_COMMUNITY_TEXTDOMAIN);
				}

				$arm_profile_tab_array["my_account"] = !empty($arm_com_settings["arm_profile_section_lbl"]) ? $arm_com_settings["arm_profile_section_lbl"] : __('Profile', ARM_COMMUNITY_TEXTDOMAIN);

				if ($arm_community_friendship->arm_com_friendship_allow() && is_user_logged_in() && ($arm_community_setting->arm_com_is_profile_editor() || !empty($arm_com_settings["arm_public_friends"]) || $loggedin_user_id == $user_id)) {
					$arm_profile_tab_array["friendship_section"] = !empty($arm_com_settings["arm_friend_section_lbl"]) ? $arm_com_settings["arm_friend_section_lbl"] : __('Friends', ARM_COMMUNITY_TEXTDOMAIN);
				}
				

				if ($arm_community_message->arm_com_message_allow() && ($arm_community_setting->arm_com_is_profile_editor() || $loggedin_user_id == $user_id)) {
					$arm_profile_tab_array["message"] = !empty($arm_com_settings["arm_msg_section_lbl"]) ? $arm_com_settings["arm_msg_section_lbl"] : __('Message', ARM_COMMUNITY_TEXTDOMAIN);

					$arm_delete_message_text = __("Are you sure you want to delete this", ARM_COMMUNITY_TEXTDOMAIN) . " " . $arm_profile_tab_array["message"] . " " . __("?", ARM_COMMUNITY_TEXTDOMAIN);

					$arm_delete_conversation_text = __("Are you sure you want to delete this conversation ?", ARM_COMMUNITY_TEXTDOMAIN);

					$arm_hide_conversation_text = __("Are you sure you want to hide this conversation?", ARM_COMMUNITY_TEXTDOMAIN);

					$arm_move_conversation_text = __("Are you sure you want to move this conversation to Inbox?", ARM_COMMUNITY_TEXTDOMAIN);

					$arm_empty_inbox_text = __("You have no any inbox message.", ARM_COMMUNITY_TEXTDOMAIN);

					$arm_empty_archive_text = __("You have no any archive message.", ARM_COMMUNITY_TEXTDOMAIN);

					$arm_content .= '<script type="text/javascript">var arm_delete_message_text = "'.$arm_delete_message_text.'"; var arm_delete_conversation_text = "'.$arm_delete_conversation_text.'"; var arm_hide_conversation_text = "'.$arm_hide_conversation_text.'"; var arm_move_conversation_text = "'.$arm_move_conversation_text.'"; var arm_empty_inbox_text = "'.$arm_empty_inbox_text.'"; var arm_empty_archive_text = "'.$arm_empty_archive_text.'";</script>';
				}
				if ($arm_community_post->arm_com_post_allow()) {
					$arm_profile_tab_array["post"] = !empty($arm_com_settings["arm_post_section_lbl"]) ? $arm_com_settings["arm_post_section_lbl"] : __('Post', ARM_COMMUNITY_TEXTDOMAIN);
				}
				if ( $arm_community_review->arm_com_review_allow() && ( $arm_community_setting->arm_com_is_profile_editor() || is_user_logged_in() || !empty($arm_com_settings["arm_keep_reviews_public"]) ) ) {

					$arm_profile_tab_array["review"] = !empty($arm_com_settings["arm_review_section_lbl"]) ? $arm_com_settings["arm_review_section_lbl"] : __('Review', ARM_COMMUNITY_TEXTDOMAIN);
				}
				if ($arm_community_activity->arm_com_activity_allow() && ($arm_community_setting->arm_com_is_profile_editor() || !empty($arm_com_settings["arm_keep_activity_public"]) || $loggedin_user_id == $user_id)) {

					$arm_profile_tab_array["activity"] = !empty($arm_com_settings["arm_activity_section_lbl"]) ? $arm_com_settings["arm_activity_section_lbl"] : __('Activity', ARM_COMMUNITY_TEXTDOMAIN);
				}
				$arm_arm_profile_tab_array_count = count($arm_profile_tab_array);
				
				$arm_content .= '<input type="hidden" id="arm_com_current_user" value="'.$user_id.'">';
				if (isset($arm_args['section_tabwise']) && $arm_args['section_tabwise'] == 1 && $arm_arm_profile_tab_array_count > 1 ) {
					$arm_content .= '<div class="arm_profile_tab_container '.$arm_profile_tab_container_class.'">';
					$arm_content .= '<div class="arm_profile_tab_menu_container">';
					$arm_content_tmp = $first_tab_lbl = "";
					foreach ($arm_profile_tab_array as $tab_key => $tab_value) {
						$active_class = '';
						if($tab_key == 'wall_post' && $loggedin_user_id == $user_id && !empty($arm_com_settings["arm_com_post_wall"])) {
							$active_class = 'active';
							$first_tab_lbl = $tab_value;
						}
						else if($tab_key == 'my_account' && ($loggedin_user_id != $user_id || empty($arm_com_settings["arm_com_post_wall"]) || empty($arm_args["wall_post"]) ) ) {
							$active_class = 'active';
							$first_tab_lbl = $tab_value;
						}

						if ($tab_key == 'my_account' || (isset($arm_args[$tab_key]) && $arm_args[$tab_key])) {

							$arm_tab_redirect_nav = "javascript:void(0);";
							$arm_tab_temp_class = "";

							if( $tab_key == "activity" && !is_user_logged_in() ) {
								global $arm_global_settings;
				                $global_settings = $arm_global_settings->arm_get_all_global_settings(TRUE);
				                $arm_tab_redirect_nav = get_permalink($global_settings["login_page_id"]);
								$arm_tab_temp_class = "arm_nav_redirect";
							}

							$arm_content_tmp .= '<li class="'.$active_class.'"><a href="'.$arm_tab_redirect_nav.'" class="arm_profile_tab '.$arm_tab_temp_class.'" data-item_id="' . $tab_key . '">' . $tab_value . '</a></li>';
						}
					}

					$arm_content .= '<button type="button" class="arm_com_main_tab_btn">';
					$arm_content .= '<span>'.$first_tab_lbl.'</span>';

					$arm_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'dropdown_arrow_2_icon.png" class="arm_com_main_tab_btn_arrow arm_com_main_tab_btn_arrow_down arm_com_main_tab_btn_arrow_current" />';

					$arm_content .= '<img src="' . ARM_COMMUNITY_IMAGES_URL . 'dropdown_arrow_2_icon_hover.png" class="arm_com_main_tab_btn_arrow arm_com_main_tab_btn_arrow_up" />';

					$arm_content .= '</button>';

					$arm_content .= '<ul class="arm_profile_tab_menu_ul">';
					$arm_content .= $arm_content_tmp;
					$arm_content .= '<ul>';
					$arm_content .= '</div>';
					$arm_content .= '<div class="arm_profile_tab_contant_container">';
					$arm_content .= '<ul class="arm_profile_tab_contant_ul">';
					$arm_tmp_hide = '';
					if($loggedin_user_id == $user_id && !empty($arm_com_settings["arm_com_post_wall"]) && !empty($arm_args["wall_post"]) ) {
						$arm_tmp_hide = 'arm_hide';
					}
					$arm_content .= '<li class="arm_section_my_account '.$arm_tmp_hide.' arm_profile_li_container">';
				}
				return $arm_content;
		}

		function arm_profile_after_content($arm_args) {
				global $arm_community_setting;
				$arm_com_settings = $arm_community_setting->arm_com_settings;
				$arm_content = ($arm_args['section_tabwise']) ? '</li>' : '';
				$user_id = 0;
				$loggedin_user_id = get_current_user_id();

				if ($arm_community_setting->arm_com_is_profile_editor()) {
					$loggedin_user_id = $user_id = 0;
				}
				else {
					$user_data = $arm_community_setting->arm_com_profile_get_user_id();
					$user_data_arr = array_shift($user_data);
					$user_id = isset($user_data_arr['ID']) ? $user_data_arr['ID'] : 0;
				}

				if ($arm_args['wall_post'] && (isset($arm_community_setting->arm_com_settings['arm_com_post_wall']) && $arm_community_setting->arm_com_settings['arm_com_post_wall']==1)) {
					$arm_tmp_hide = 'arm_hide';
					if($loggedin_user_id == $user_id) {
						$arm_tmp_hide = '';
					}
					$arm_content .= ($arm_args['section_tabwise']) ? '<li class="arm_section_wall_post '.$arm_tmp_hide.' arm_profile_li_container">' : '';
					$arm_content .= '<div class="arm_com_wall_post_wrapper_title" id="arm_com_wall_post_wrapper_title">';

					$arm_wall_section_lbl = !empty($arm_com_settings["arm_wall_section_lbl"]) ? $arm_com_settings["arm_wall_section_lbl"] : __('News Feed', ARM_COMMUNITY_TEXTDOMAIN);

					$arm_content .= $arm_wall_section_lbl.'' . '</div>';

					$arm_content .= '<div class="arm_com_post_container" id="arm_com_post_container">';
					$arm_content .= '<div class="arm_com_post_content_container arm_com_wall_post_content_container">';
					$arm_content .= do_shortcode('[arm_community_display_wall_post]');
					$arm_content .= '</div>';
					$arm_content .= '</div>';
					$arm_content .= ($arm_args['section_tabwise']) ? '</li>' : '';
				}

				if ($arm_args['friendship_section']) {
					$show_friends = true;
					$arm_keep_friends_in_public = $arm_com_settings['arm_public_friends'];

					if(!is_user_logged_in()) {
						$show_friends = false;
					}
					else if(!$arm_keep_friends_in_public) {
						if ($user_id != $loggedin_user_id) {
							$show_friends = false;
						}
					}


					if($show_friends) {
						$arm_content .= ($arm_args['section_tabwise']) ? '<li class="arm_section_friendship_section arm_hide arm_profile_li_container">' : '';
						$arm_content .= do_shortcode('[arm_community_friendship_tab]');
						$arm_content .= ($arm_args['section_tabwise']) ? '</li>' : '';
					}
				}

				if ($arm_args['message'] && $user_id == $loggedin_user_id) {
					$arm_content .= ($arm_args['section_tabwise']) ? '<li class="arm_section_message arm_hide arm_profile_li_container">' : '';
					$arm_content .= do_shortcode('[arm_community_message]');
					$arm_content .= ($arm_args['section_tabwise']) ? '</li>' : '';
				}

				if ($arm_args['post']) {
					$arm_content .= ($arm_args['section_tabwise']) ? '<li class="arm_section_post arm_hide arm_profile_li_container">' : '';
					$arm_content .= '<div class="arm_com_post_wrapper_title" id="arm_com_post_wrapper_title">';

					$arm_post_section_lbl = !empty($arm_com_settings["arm_post_section_lbl"]) ? ($arm_com_settings["arm_post_section_lbl"] . " " . __('Listing', ARM_COMMUNITY_TEXTDOMAIN) ) : __('Post Listing', ARM_COMMUNITY_TEXTDOMAIN);

					$arm_content .= $arm_post_section_lbl . '</div>';

					$arm_content .= '<div class="arm_com_post_container" id="arm_com_post_container">';
					$arm_content .= '<div class="arm_com_post_content_container">';
					$arm_content .= do_shortcode('[arm_community_add_post]');
					$arm_content .= do_shortcode('[arm_community_display_post]');
					$arm_content .= '</div>';
					$arm_content .= '</div>';
					$arm_content .= ($arm_args['section_tabwise']) ? '</li>' : '';
				}

				if ($arm_args['review']) {
					$arm_content .= ($arm_args['section_tabwise']) ? '<li class="arm_section_review arm_hide arm_profile_li_container">' : '';
					$arm_content .= do_shortcode('[arm_community_review_display]');
					$arm_content .= ($arm_args['section_tabwise']) ? '</li>' : '';
				}

				if ($arm_args['activity']) {
					$arm_content .= ($arm_args['section_tabwise']) ? '<li class="arm_section_activity arm_hide arm_profile_li_container">' : '';
					$arm_content .= do_shortcode('[arm_com_display_activity]');
					$arm_content .= ($arm_args['section_tabwise']) ? '</li>' : '';
				}

				$arm_content .= ($arm_args['section_tabwise']) ? '</li></ul></div></div>' : '';
				return $arm_content;
		}
	}
}
global $arm_community_profile;
$arm_community_profile = new ARM_Community_profile();