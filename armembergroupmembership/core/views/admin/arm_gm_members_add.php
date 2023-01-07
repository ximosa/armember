<?php
	global $wpdb, $ARMember, $arm_members_class, $arm_group_membership, $arm_subscription_plans;
	$arm_gm_all_subscription_plans = $arm_subscription_plans->arm_get_plans_data();


	$arm_gm_subscription_plan_data = array();
	foreach($arm_gm_all_subscription_plans as $arm_gm_plan_key => $arm_gm_plan_vals)
	{
		$arm_gm_subscription_plan_options = $arm_gm_plan_vals['arm_subscription_plan_options'];
		if(!empty($arm_gm_subscription_plan_options['arm_gm_enable_referral']) && ($arm_gm_subscription_plan_options['arm_gm_enable_referral']))
		{
			$tmp_data['arm_gm_plan_id'] = $arm_gm_plan_vals['arm_subscription_plan_id'];
			$tmp_data['arm_gm_plan_name'] = $arm_gm_plan_vals['arm_subscription_plan_name'];
			array_push($arm_gm_subscription_plan_data, $tmp_data);
		}
	}

	if(!empty($_POST) && isset($_POST['arm_gm_save_data']))
	{
		$arm_gm_response_data = $arm_group_membership->arm_gm_save_add_gm_data($_POST);
	}
?>

<style type="text/css">
	.arm_gm_admin_form_content input[type="text"], input[type="email"], input[type="password"], select{ max-width: 50%; color: #5C5C60; border-radius: 3px; -webkit-border-radius: 3px; -moz-border-radius: 3px; -o-border-radius: 3px; border: 1px solid #D2D2D2; height: 34px; padding: 0 10px;  }

	.arm_gm_admin_form_content label{ color: #191818; font-weight: normal; }
	.arm_gm_admin_form_content th{ text-align: right; }

	.hidden_row{ display: none; }
	.arm_gm_error_msg{ color: #f00; display: none; }

	.arm_save_btn_disabled{ cursor: no-drop; opacity: 0.5; }
</style>
<div class="arm_admin_form_content arm_gm_admin_form_content">
	<form method="POST" action="" id="arm_gm_add_form">
		<table class="form-table">
			<tbody>
				<tr class="form-field">
					<th>
						<label><?php _e('Select Group Membership Plan', 'ARMGroupMembership'); ?></label>
					</th>
					<td class="arm-form-table-content">
						<input type='hidden' id="arm_gm_selected_plan_id" name="arm_gm_selected_plan_id" value=""/>
	                    <dl class="arm_selectbox column_level_dd">
	                        <dt style="width: 420px;">
	                            <span></span>
	                            <input type="text" style="display: none;" value="" class="arm_autocomplete"/>
	                            <i class="armfa armfa-caret-down armfa-lg"></i>
	                        </dt>
	                        <dd>
	                            <ul data-id="arm_gm_selected_plan_id">
	                            	<?php
	                            	foreach($arm_gm_subscription_plan_data as $arm_gm_plan_keys => $arm_gm_plan_value)
	                            	{
	                            	?>
	                            		<li data-label="<?php echo $arm_gm_plan_value['arm_gm_plan_name']; ?>" data-value="<?php echo $arm_gm_plan_value['arm_gm_plan_id']; ?>"><?php echo $arm_gm_plan_value['arm_gm_plan_name']; ?></li>
	                            	<?php
	                            	}
	                            	?>
	                            </ul>
	                        </dd>
	                    </dl><br>
	                    <span class="arm_gm_error_msg"></span>
					</td>
				</tr>
				<tr class="form-field">
					<th>
						<label><?php _e('Enter Username', 'ARMGroupMembership'); ?></label>
					</th>
					<td>
	                    <input id="arm_gm_user_name" class="arm_gm_user_name" type="text" name="arm_gm_user_name" value="" placeholder="<?php _e('Enter Username', 'ARMGroupMembership'); ?>" />
	                </td>
				</tr>
				<tr class="form-field">
					<th>
						<label><?php _e('Enter Email', 'ARMGroupMembership'); ?></label>
					</th>
					<td>
	                    <input id="arm_gm_user_email" class="arm_gm_user_email" type="email" name="arm_gm_user_email" value="" placeholder="<?php _e('Enter Username', 'ARMGroupMembership'); ?>" />
	                </td>
				</tr>
				<tr class="form-field">
					<th>
						<label><?php _e('Enter Password', 'ARMGroupMembership'); ?></label>
					</th>
					<td>
	                    <input id="arm_gm_user_pass" class="arm_gm_user_pass" type="password" name="arm_gm_user_pass" value="" placeholder="<?php _e('Enter Password', 'ARMGroupMembership'); ?>" />
	                    <span class="arm_visible_password_admin arm_editor_suffix" onclick="show_hide_pass()">
	                    	<i class="armfa armfa-eye"></i>
	                    </span>
	                </td>
				</tr>
				<tr class="form-field">
					<th>
						<label><?php _e('Maximum Members', 'ARMGroupMembership'); ?></label>
					</th>
					<td>
						<input name="arm_gm_max_members" id="arm_gm_max_members" type="text" size="50" class="" title="<?php _e('Maximum Members', 'ARMGroupMembership'); ?>" aria-required="true" aria-invalid="false" value="" onkeypress="javascript:return ArmNumberValidation(event, this)">
					</td>
				</tr>
				<tr class="form-field">
					<th>
						<label><?php _e('Minimum Members', 'ARMGroupMembership'); ?></label>
					</th>
					<td>
						<input name="arm_gm_min_members" id="arm_gm_min_members" type="text" size="50" class="" title="<?php _e('Minimum Members', 'ARMGroupMembership'); ?>" aria-required="true" aria-invalid="false" value="" onkeypress="javascript:return ArmNumberValidation(event, this)">
					</td>
				</tr>
				<tr class="form-field">
					<th>
						<label><?php _e('Child user seat purchase slot', 'ARMGroupMembership'); ?></label>
					</th>
					<td class="">
						<input type="range" name="gm_sub_user_seat_slot" id="gm_sub_user_seat_slot" min="0" max="100" value="" class="arm_gm_slider" style="width: 47%;">
						<span id="arm_slider_val_preview"></span>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="arm_submit_btn_container">
	        <button class="arm_save_btn" type="submit" name="arm_gm_save_data"><?php _e('Save', 'ARMGroupMembership');?></button>
	        <a class="arm_cancel_btn" href="<?php echo admin_url('admin.php?page=arm_gm_membership'); ?>"><?php _e('Close', 'ARMGroupMembership') ?></a>
	    </div>
	    <div class="armclear"></div>
	</form>
</div>