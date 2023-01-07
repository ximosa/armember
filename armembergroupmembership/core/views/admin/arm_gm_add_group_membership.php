<?php
	global $arm_members_activity, $check_sorting, $wpdb, $ARMember, $arm_global_settings;


	$arm_gm_user_args = [
		'role__not_in' => ['administrator'],
		'orderby' => 'nicename',
		'order' => 'ASC',
		'fields' => 'all',
	];
	$arm_gm_get_users_list = get_users($arm_gm_user_args);


	$cancel_url = admin_url('admin.php?page=arm_gm_membership');


	if ( isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'add_item', 'update_item' ) ) )
	{

	}

	$arm_gm_selected_user_id = "";
	$arm_gm_max_members = "";
	$arm_gm_min_members = "";
	$arm_gm_sub_user_seat_slot = 0;

	$setact = $arm_members_activity->$check_sorting();

	$action = "add_item";
	$arm_gm_title = __('Add Group Membership', 'ARMGroupMembership');
?>

<div class="wrap arm_page arm_add_member_page armPageContainer">
    <div class="content_wrapper" id="content_wrapper">
        <div class="page_title"><?php echo $arm_gm_title; ?></div>
        <?php
        	if($setact != 1)
        	{
        		$arm_gm_admin_css_url = admin_url('admin.php?page=arm_manage_license');
       	?>
       			<div style="margin-top:20px;margin-bottom:20px;border-left: 4px solid #ffba00;box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);height:20px;width:97%;padding:10px 25px 10px 0px;background-color:#f2f2f2;color:#000000;font-size:17px;display:block;visibility:visible;text-align:right;" >ARMember License is not activated. Please activate license from <a href="<?php echo $admin_css_url; ?>">here</a></div>
       	<?php
        	}
        ?>
        <div class="armclear"></div>
        <?php
	        global $arm_errors;
	        $errors = $arm_errors->get_error_messages();
	        if (!empty($errors)) {
	            foreach ($errors as $err) {
	                echo '<div class="arm_message arm_error_message" style="display:block;">';
	                echo '<div class="arm_message_text">' . $err . '</div>';
	                echo '</div>';
	            }
	        }
        ?>
        <div class="armclear"></div>
        <div class="arm_add_edit_gm_member_wrapper arm_member_detail_box">
        	<form method="post" id="arm_add_edit_gm_member_form" class="arm_add_edit_gm_member_form arm_admin_form" enctype="multipart/form-data">
        		<input type="hidden" name="action" value="<?php echo $action; ?>">
        		<div class="arm_admin_form_content">
        			<table class="form-table">
                        <tr class="form-field form-required">
                            <th>
                                <label><?php _e('Enable Group Membership', 'ARMGroupMembership'); ?></label>
                            </th>
                            <td>
                                <div class="armclear"></div>
                                <div class="armswitch arm_global_setting_switch">
                                    <input type="checkbox" id="arm_gm_group_membership_disable_referral" value="1" class="armswitch_input" name="arm_subscription_plan_options[arm_gm_group_membership_disable_referral]" <?php //checked($arm_gm_enable_referral); ?>>
                                    <label for="arm_gm_group_membership_disable_referral" class="armswitch_label" style="min-width:40px;"></label>
                                </div>
                            </td>
                        </tr>

                        <tr class="form-field form-required">
        					<th>
                                <label for="arm_gm_max_members">
                                    <?php _e('Maximum Members','ARMGroupMembership'); ?><span class="required_icon">*</span>
                                </label>
                            </th>
                            <td>
                                <input id="arm_gm_max_members" class="arm_gm_max_members" type="text" name="arm_gm_max_members" value="<?php echo $arm_gm_max_members;?>" data-msg-required="<?php _e('Maximum numbers cannot be left blank.', 'ARMGroupMembership');?>" required />
                            </td>
                        </tr>

                        <tr class="form-field form-required">
        					<th>
                                <label for="arm_gm_min_members">
                                    <?php _e('Minimum Members','ARMGroupMembership'); ?><span class="required_icon">*</span>
                                </label>
                            </th>
                            <td>
                                <input id="arm_gm_min_members" class="arm_gm_min_members" type="text" name="arm_gm_min_members" value="<?php echo $arm_gm_min_members;?>" data-msg-required="<?php _e('Minimum numbers cannot be left blank.', 'ARMGroupMembership');?>" required />
                            </td>
                        </tr>


                        <tr class="form-field form-required">
							<th>
								<label for="arm_gm_sub_user_seat_slot">
									<?php _e('Child user seat purchase slot', 'ARMGroupMembership'); ?>
								</label>
							</th>
							<td class="arm_gm_slidecontainer">
								<div class="armclear"></div>
								<input type="range" name="gm_sub_user_seat_slot" id="gm_sub_user_seat_slot" min="0" max="100" value="<?php echo $arm_gm_sub_user_seat_slot; ?>" class="arm_gm_slider" style="width: 60% !important;">
								<span id="arm_slider_val_preview"><?php echo $arm_gm_sub_user_seat_slot; ?></span>
							</td>
						</tr>
        			</table>
        			<div class="arm_submit_btn_container">
                        <button class="arm_save_btn arm_gm_item_save_btn" type="submit"><?php _e('Save', 'ARMGroupMembership'); ?></button>
                        <a class="arm_cancel_btn" href="<?php echo $cancel_url;?>"><?php _e('Close', 'ARMGroupMembership'); ?></a>
                    </div>
                    <div class="armclear"></div>
        		</div>
        	</form>
        	<div class="armclear"></div>
        </div>
    </div>
</div>