<tr class="form-field">
    <th class="arm-form-table-label"><?php _e('Child user signup page', 'ARMGroupMembership'); ?></th>
    <td class="arm-form-table-content">
		<span data-type="arm_gm_child_user_register_page" class="arm_page_type"></span>
		<?php
		$page_settings['child_user_signup_page_id'] = isset($page_settings['child_user_signup_page_id']) ? $page_settings['child_user_signup_page_id'] : 0;
		$is_valid_md_page = $arm_global_settings->arm_shortcode_exist_in_page('arm_gm_child_user_register_page', $page_settings['child_user_signup_page_id']);
		$arm_global_settings->arm_wp_dropdown_pages(
			array(
				'selected' => $page_settings['child_user_signup_page_id'],
				'name' => 'arm_page_settings[child_user_signup_page_id]',
				'id' => 'child_user_signup_page_id',
				'show_option_none' => __('Select Page', 'ARMGroupMembership'),
				'option_none_value' => '0',
				'class' => 'arm_page_setup_input',
			)
		);
		?>
		<i class="armfa armfa-1x armfa-refresh armfa-spin arm_refresh arm_no_error"></i>
		<i class="armfa armfa-1x armfa-check arm_check arm_no_error"></i>
		<span class="arm_error_msg <?php echo ($is_valid_md_page) ? 'arm_no_error' : ''; ?>"><?php _e('Shortcode of child user register not found on selected page. Please add shrotcode on that page Or please select appropriate page.', 'ARMGroupMembership'); ?></span>
    </td>
</tr>