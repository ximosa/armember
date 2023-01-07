<div class="arm_solid_divider"></div>
<div id="arm_group_membership_div" class="arm_plan_price_box">
	<div class="page_sub_content">
		<div class="page_sub_title"><?php _e('Group Membership Settings', 'ARMGroupMembership'); ?></div>
		<table class="form-table">
			<tbody>
				<tr class="form-field form-required">
					<th>
						<label><?php _e('Enable Group Membership', 'ARMGroupMembership'); ?></label>
					</th>
					<td>
						<div class="armclear"></div>
						<div class="armswitch arm_global_setting_switch">
							<input type="checkbox" id="arm_gm_group_membership_disable_referral" value="1" class="armswitch_input" name="arm_subscription_plan_options[arm_gm_group_membership_disable_referral]" <?php checked($arm_gm_enable_referral); ?>>
							<label for="arm_gm_group_membership_disable_referral" class="armswitch_label" style="min-width:40px;"></label>
						</div>
					</td>
				</tr>
				<tr class="form-field form-required arm_gm_sub_opt <?php echo $arm_gm_hidden_class; ?>">
					<th>
						<label><?php _e('Minimum Child Members', 'ARMGroupMembership'); ?></label>
					</th>
					<td>
						<div class="armclear"></div>
						<input name="gm_min_members" id="gm_min_members" type="text" size="50" class="" title="<?php _e('Minimum Child Members', 'ARMGroupMembership'); ?>" aria-required="true" aria-invalid="false" value="<?php echo $arm_gm_min_members; ?>" onkeypress="javascript:return ArmNumberValidation(event, this)">
						<label><?php _e('Seats', 'ARMGroupMembership'); ?></label><br />
						<span class="arm_min_member_error"><?php _e("( Minimum Child Member value cannot be grater than Maximum Child Member. )", "ARMGroupMembership"); ?></span>
					</td>
				</tr>
				<tr class="form-field form-required arm_gm_sub_opt <?php echo $arm_gm_hidden_class; ?>">
					<th>
						<label><?php _e('Maximum Child Members', 'ARMGroupMembership'); ?></label>
					</th>
					<td>
						<div class="armclear"></div>
						<input name="gm_max_members" id="gm_max_members" type="text" size="50" class="" title="<?php _e('Maximum Child Members', 'ARMGroupMembership'); ?>" aria-required="true" aria-invalid="false" value="<?php echo $arm_gm_max_members; ?>" onkeypress="javascript:return ArmNumberValidation(event, this)">
						<label><?php _e('Seats', 'ARMGroupMembership'); ?></label><br />
						<span class="arm_max_member_error"><?php _e("( Maximum Child Member value can't low then Minimum Child Member. )", "ARMGroupMembership"); ?></span>
					</td>
				</tr>
				<tr class="form-field form-required arm_gm_sub_opt <?php echo $arm_gm_hidden_class; ?>">
					<th>
						<label><?php _e('Child Member Purchase Slab', 'ARMGroupMembership'); ?></label>
					</th>
					<td>
						<div class="armclear"></div>
						<input name="gm_sub_user_seat_slot" id="gm_sub_user_seat_slot" type="text" size="50" class="" title="<?php _e('Child Member Purchase Slab', 'ARMGroupMembership'); ?>" aria-required="true" aria-invalid="false" value="<?php echo $arm_gm_sub_user_seat_slot; ?>" onkeypress="javascript:return ArmNumberValidation(event, this)">
						<label><?php _e('Seats', 'ARMGroupMembership'); ?></label><br />
						<span class="arm_sub_user_slot_error"><?php _e("Purchase Slab Value cannot be greater than Maximum Child Members.", "ARMGroupMembership"); ?></span><br />
						<span><?php _e('For Example: If you set "Minimum Child Members" to "5", "Maximum Child Members" to "50" and "Child Member Purchase Slab" to "5" then child user purchase selection will be as 5,10,15,20,... until Maximum seat.', 'ARMGroupMembership'); ?></span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
			$arm_group_membership_recurring_error_display = false;
			$arm_group_membership_recurring_css = 'color: #f00; margin-left: 3rem; display: none;';
			if(!empty($arm_gm_plan_type) && $arm_gm_plan_type == "subscription")
			{
				$arm_group_membership_recurring_css = 'color: #f00; margin-left: 3rem;';
			}
		?>
		<div class="arm_group_membership_recurring_error" style="<?php echo $arm_group_membership_recurring_css; ?>">
			<span><?php _e('Note: For subscription plan type, group membership is not possible to purchase with Auto-Debit Recurring Payment Method.', 'ARMGroupMembership'); ?></span>
		</div>
	</div>
</div>