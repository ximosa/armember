<?php
	global $arm_members_activity, $check_sorting;
	$setact = 0;
	$setact = $arm_members_activity->$check_sorting();
	if ($setact != 1) 
	{
        $admin_css_url = admin_url('admin.php?page=arm_manage_license');
?>
		<div style="margin-top:20px;margin-bottom:10px;border-left: 4px solid #ffba00;box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);height:20px;width:99%;padding:10px 25px 10px 0px;background-color:#f2f2f2;color:#000000;font-size:17px;display:block;visibility:visible;text-align:right;" >ARMember License is not activated. Please activate license from <a href="<?php echo $admin_css_url; ?>">here</a></div>
<?php
	}

	$action = "";
	if(!empty($_GET['action']))
	{
		$action = $_GET['action'];
	}
?>
<div class="wrap arm_page arm_manage_members_main_wrapper">
	<div class="content_wrapper" id="content_wrapper">
		<div class="page_title">
			<?php
			if(empty($action))
			{
				_e('Manage Group Membership', 'ARMGroupMembership');
			}
			else
			{
				_e('Add Group Membership', 'ARMGroupMembership');
			}
			?>
		</div>
		<div class="armclear"></div>
		<?php
			if(!empty($action) && $action == "add_group_membership")
			{
				require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/admin/arm_gm_members_add.php');
			}
			else
			{
		?>
		<div class="arm_gm_members_grid_container" id="arm_gm_members_grid_container">
			<input type="hidden" id="arm_gm_child_user_confirmation_txt" value="<?php _e('Okay', 'ARMGroupMembership'); ?>">
			<?php
				require(ARM_GROUP_MEMBERSHIP_VIEW_DIR.'/admin/arm_gm_members_listing_records.php');
			?>
		</div>
		<?php
			}
		?>
	</div>
</div>
<style type="text/css" title="currentStyle">
    .paginate_page a{display:none;}
	#poststuff #post-body {margin-top: 32px;}
	.DTFC_ScrollWrapper{background-color: #EEF1F2;}
</style>
<?php // Child Users Modal ?>
<?php // --------------------------------------- ?>
<div id="arm_gm_child_users_data" class="arm_members_list_detail_popup popup_wrapper arm_members_list_detail_popup_wrapper" style="width:900px;min-height: 250px;">
	<div class="arm_loading_grid" id="arm_loading_grid_members" style="display: none;"><img src="<?php echo MEMBERSHIP_IMAGES_URL;?>/loader.gif" alt="Loading.."></div>
	<div class="popup_wrapper_inner">
		<div class="popup_header">
			<span class="popup_close_btn arm_popup_close_btn"></span>
			<span class="popup_header_text"><?php _e('Child Users List', 'ARMGroupMembership'); ?></span>
		</div>

		<div class="popup_content_text arm_gm_child_user_content_data arm_members_list_detail_popup_text">

		</div>

		<div class="armclear"></div>
	</div>
</div>
<?php // Members Listing Details Modal ?>
<div class="arm_member_view_detail_container">
</div>
<?php // Member Edit Modal ?>
<div id="arm_gm_edit_users_data" class="arm_member_edit_detail_popup popup_wrapper arm_member_edit_detail_popup_wrapper" style="width:810px;min-height: 250px;">
	<div class="arm_loading_grid" id="arm_loading_grid_members" style="display: none;"><img src="<?php echo MEMBERSHIP_IMAGES_URL;?>/loader.gif" alt="Loading.."></div>
	<div class="popup_wrapper_inner" style="overflow: hidden; border: none;">
		<div class="popup_header">
			<span class="popup_close_btn arm_popup_close_btn"></span>
			<span class="popup_header_text"><?php _e('Edit Group Membership', 'ARMGroupMembership'); ?></span>
		</div>

		<div class="popup_content_text arm_gm_edit_user_content_data arm_member_edit_detail_popup_text">

		</div>
		<div class="armclear"></div>
	</div>
</div>
<?php // Sub User Add Modal ?>
<div id="arm_gm_add_sub_users_data" class="arm_member_edit_detail_popup popup_wrapper arm_member_edit_detail_popup_wrapper" style="width:810px;min-height: 250px;">
	<div class="arm_loading_grid" id="arm_loading_grid_members" style="display: none;"><img src="<?php echo MEMBERSHIP_IMAGES_URL;?>/loader.gif" alt="Loading.."></div>
	<div class="popup_wrapper_inner" style="overflow: hidden; border: none;">
		<div class="popup_header">
			<span class="popup_close_btn arm_popup_close_btn"></span>
			<span class="popup_header_text"><?php _e('Add Child User', 'ARMGroupMembership'); ?></span>
		</div>

		<div class="popup_content_text arm_gm_add_sub_user_content_data arm_member_add_sub_user_detail_popup_text" style="border-bottom: 1px solid #ebeef1;">
			<form method="POST" id="arm_gm_add_sub_user_form" class="arm_admin_form">
				<input type="hidden" name="arm_gm_parent_user_id" id="arm_gm_parent_user_id" value="">
				<div class='arm_gm_sub_user_add_div'>
					<table>
						<tbody>
							<tr>
								<th>
									<label><?php _e('Enter Child Username', 'ARMGroupMembership'); ?> <span class="required_icon">*</span> </label>
								</th>
								<td>
									<input type='text' name='arm_gm_sub_user_username' class='arm_form_input_box arm_gm_sub_user_username' required="">
									<div class="arm_gm_username_required_error" style="color: #f00;display: none;"><?php _e('Please enter child user username', 'ARMGroupMembership'); ?></div>
									<div class="arm_gm_username_error" style="color: #f00;display: none;"><?php _e('Child User Username already exists...', 'ARMGroupMembership'); ?></div>
								</td>
							</tr>
							<tr>
								<th>
									<label><?php _e('Enter Child Email', 'ARMGroupMembership'); ?> <span class="required_icon">*</span> </label>
								</th>
								<td>
									<input type='email' name='arm_gm_sub_user_email' class='arm_form_input_box arm_gm_sub_user_email' required="">
									<div class="arm_gm_valid_email_required_error" style="color: #f00;display: none;"><?php _e('Please enter valid child user email', 'ARMGroupMembership'); ?></div>
									<div class="arm_gm_email_required_error" style="color: #f00;display: none;"><?php _e('Please enter child user email', 'ARMGroupMembership'); ?></div>
									<div class="arm_gm_email_error" style="color: #f00;display: none;"><?php _e('Child User Email already exists', 'ARMGroupMembership'); ?></div>
								</td>
							</tr>
							<tr>
								<th>
									<label><?php _e('Enter Child Password', 'ARMGroupMembership'); ?> <span class="required_icon">*</span> </label>
								</th>
								<td>
									<input type='password' id="arm_gm_user_pass" name='arm_gm_sub_user_password' class='arm_form_input_box arm_gm_sub_user_password' required="">
									<span class="arm_visible_password_admin arm_editor_suffix" id="" style="" onclick="show_hide_pass()"><i class="armfa armfa-eye"></i></span>
									<div class="arm_gm_pass_required_error" style="color: #f00;display: none;"><?php _e('Please enter child user password', 'ARMGroupMembership'); ?></div>
								</td>
							</tr>
							<tr><th>&nbsp;</th><td></td></tr>
							<tr>
								<th>&nbsp;</th>
								<td>
									<button class="arm_member_add_sub_user_gm_save_btn arm_save_btn" type="button"><?php _e('Save', 'ARMGroupMembership'); ?></button>
									<button class="arm_member_add_sub_user_gm_cancel_btn arm_cancel_btn" type="button"><?php _e('Close', 'ARMGroupMembership'); ?></button>					
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</form>
			<div class="armclear"></div>
		</div>
	</div>
</div>