<style type="text/css">
	.arm_gm_setup_child_user_signup_main_wrapper .arm_form_input_wrapper{ padding-bottom: 3rem; }
	.arm_gm_setup_child_user_signup_main_wrapper .arm_form_field_container{ padding-top: 4rem; border-bottom: 2px solid #ececec; padding-left: 2em; padding-right: 2em; }
</style>

<div class="arm_gm_setup_child_user_signup_main_wrapper">
	<div class="arm_gm_setup_child_user_selection_wrapper">
		<div class="arm_gm_module_child_user_container <?php echo $arm_gm_div_class; ?>">
			<div class="arm_setup_section_title_wrapper">
				<?php echo $arm_gm_child_user_invite_label; ?>
			</div>
			<div class="arm_bt_field_wrapper arm_form_field_container arm_form_field_container_text">
				<div class="arm_form_label_wrapper arm_form_field_label_wrapper arm_form_member_field_text">
					<div class="arm_member_form_field_label">
						<div class="arm_form_field_label_text"><?php echo $arm_gm_child_user_invite_label ?></div>
					</div>
				</div>
				<div class="arm_label_input_separator"></div>
				<div class="arm_form_input_wrapper">
					<div class="arm_form_input_container">
						<md-input-container class="md-block" flex-gt-sm="">
							<label class="arm_material_label"><?php echo $arm_gm_child_user_invite_label ?></label>
							<input type="text" name="arm_gm_child_invite_code" value="" class="" data-ng-model="arm_gm_child_invite_code">
						</md-input-container>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>