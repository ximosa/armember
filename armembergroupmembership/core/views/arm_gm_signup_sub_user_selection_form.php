<?php
	foreach($arm_gm_plan_data as $arm_gm_plan_key => $arm_gm_plan_data_val)
	{
		if($arm_gm_plan_data_val['arm_gm_enable_referral'])
		{
			$arm_gm_max_members = $arm_gm_plan_data_val['arm_gm_max_members'];
			$arm_gm_min_members = $arm_gm_plan_data_val['arm_gm_min_members'];
			$arm_gm_sub_user_seat_slot = $arm_gm_plan_data_val['arm_gm_sub_user_seat_slot'];

			if(empty($arm_gm_sub_user_seat_slot))
			{
				$arm_gm_sub_user_seat_slot = 1;
			}

			$arm_gm_user_id = get_current_user_id();
			if(!empty($arm_gm_user_id))
			{
				$arm_already_purchased_members = get_user_meta($arm_gm_user_id, 'gm_sub_user_select_'.$arm_gm_plan_key, true);
				if($arm_already_purchased_members <= $arm_gm_max_members)
				{
					$arm_gm_max_members = $arm_gm_max_members - $arm_already_purchased_members;
				}
			}

			$arm_gm_sub_user_selection_css = ".arm_gm_setup_sub_user_selection_main_wrapper .md-select-value span{ margin-left: 15px !important; }";
			$arm_gm_sub_user_selection_css .= ".arm_gm_sub_user_".$arm_gm_plan_key." { display: none; }";

?>
<style type="text/css">
	<?php echo $arm_gm_sub_user_selection_css; ?>
</style>
			<div class="arm_gm_setup_sub_user_selection_main_wrapper arm_gm_sub_user_<?php echo $arm_gm_plan_key; ?>">
				<div class="arm_gm_setup_sub_user_selection_wrapper">
					<div class="arm_gm_module_sub_user_container <?php echo $arm_gm_div_class; ?>">
						<div class="arm_setup_section_title_wrapper arm_gm_sub_user_container_title"><?php echo $arm_gm_sub_user_selection_label; ?></div>
						<div class="arm_setup_sub_user_section_dropdown_area">
							<div class="arm_form_input_container arm_container payment_gateway_dropdown_skin1">
								<div class="payment_gateway_dropdown_skin" data-ng-controller="ARMCtrl2">
									<input type="hidden" class="arm_gm_hidden_min_members_<?php echo $arm_gm_plan_key; ?>" value="<?php echo $arm_gm_min_members; ?>">
									<md-select name="gm_sub_user_select_<?php echo $arm_gm_plan_key; ?>" class="arm_module_cycle_input select_skin" data-ng-model="gm_sub_user_select" aria-label="gateway" ng-change="arm_gm_change_plan_price(this)">
										<?php
											for($arm_gm_i=$arm_gm_min_members; $arm_gm_i<=$arm_gm_max_members; $arm_gm_i=$arm_gm_i+$arm_gm_sub_user_seat_slot)
											{
												if($arm_gm_i == $arm_gm_min_members)
												{
										?>
													<md-option value="<?php echo $arm_gm_i; ?>" class="armMDOption armSelectOption" selected=""><?php echo $arm_gm_i; ?></md-option>
										<?php
												}
												else
												{
										?>
													<md-option value="<?php echo $arm_gm_i; ?>" class="armMDOption armSelectOption"><?php echo $arm_gm_i; ?></md-option>
										<?php
												}
											}
										?>
									</md-select>
								</div>	
							</div>
						</div>
					</div>
				</div>
			</div>
<?php
		}
	}
?>