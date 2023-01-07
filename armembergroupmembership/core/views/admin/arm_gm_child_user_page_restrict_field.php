<div class="arm_solid_divider"></div>
<div class="page_sub_title">
	<?php _e('Set Child User Restrict Page', 'ARMGroupMembership'); ?>
</div>
<table class="form-table">
	<tr class="form-field">
		<th class="arm-form-table-label"><?php _e('Select Child User Restrict Page','ARMGroupMembership');?></th>
		<td class="arm-form-table-content">
			<?php 
				$arm_global_settings->arm_wp_dropdown_pages(
					array(
						'selected' => $arm_gm_selected_page,
						'name' => 'arm_general_settings[arm_gm_child_user_restrict_page]',
						'id' => 'arm_gm_child_user_restrict_page',
						'show_option_none' => __('Select Page', 'ARMGroupMembership'),
						'option_none_value' => '0',
						'class' => 'arm_gm_child_user_restrict_page',
					)
				);
			?>
		</td>
	</tr>
</table>