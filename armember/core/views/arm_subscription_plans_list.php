<?php
global $wpdb, $ARMember, $arm_subscription_plans, $arm_members_class, $arm_member_forms, $arm_global_settings, $arm_payment_gateways;
$user_roles = get_editable_roles();
$user_roles1 = $arm_global_settings->arm_get_all_roles();

$filter_search = (!empty($_POST['search'])) ? $_POST['search'] : '';
?>
<style type="text/css" title="currentStyle">
	.paginate_page a{display:none;}
	#poststuff #post-body {margin-top: 32px;}
	.ColVis_Button{display:none;}
</style>
<script type="text/javascript" charset="utf-8">
// <![CDATA[
jQuery(document).ready( function () {
    arm_load_plan_list_grid();

});

function arm_load_plan_list_filtered_grid(data)
{
    var tbl = jQuery('#armember_datatable').dataTable(); 
    tbl.fnDeleteRow(data);
    jQuery('#armember_datatable').dataTable().fnDestroy();
    arm_load_plan_list_grid();
}

function arm_load_plan_list_grid(){
	var __ARM_Showing = '<?php echo addslashes(__('Showing','ARMember')); ?>';
        var __ARM_Showing_empty = '<?php echo addslashes(__('Showing 0 to 0 of 0 enteries','ARMember')); ?>';
        var __ARM_to = '<?php echo addslashes(__('to','ARMember')); ?>';
        var __ARM_of = '<?php echo addslashes(__('of','ARMember')); ?>';
        var __ARM_PLANS = ' <?php echo addslashes(__('entries','ARMember')); ?>';
        var __ARM_Show = '<?php echo addslashes(__('Show','ARMember')); ?> ';
        var __ARM_NO_FOUND = '<?php echo addslashes(__('No any subscription plan found.','ARMember')); ?>';
        var __ARM_NO_MATCHING = '<?php echo addslashes(__('No matching records found.','ARMember')); ?>';


	var table = jQuery('#armember_datatable').dataTable({
		"sDom": '<"H"fr>t<"footer"ipl>',
		"sPaginationType": "four_button",
                "oLanguage": {
					"sInfo": __ARM_Showing + " _START_ " + __ARM_to + " _END_ " + __ARM_of + " _TOTAL_ " + __ARM_PLANS,
					"sInfoEmpty": __ARM_Showing_empty,
				
					"sLengthMenu": __ARM_Show + "_MENU_" + __ARM_PLANS,
					"sEmptyTable": __ARM_NO_FOUND,
					"sZeroRecords": __ARM_NO_MATCHING,
                  },
		"bJQueryUI": true,
		"bPaginate": true,
		"bAutoWidth" : false,
		"aaSorting": [],
		"aoColumnDefs": [
			{ "bVisible": false, "aTargets": [] },
			{ "bSortable": false, "aTargets": [] }
		],
		"language":{
		    "searchPlaceholder": "<?php _e('Search', 'ARMember') ?>",
		    "search":"",
		},
		"fnPreDrawCallback": function () {
            jQuery('.arm_loading_grid').show();
        },
		"fnDrawCallback":function(){
			setTimeout(function(){
				jQuery('.arm_loading_grid').hide();
				arm_show_data();
			}, 1000);
			if (jQuery.isFunction(jQuery().tipso)) {
                jQuery('.armhelptip').each(function () {
                    jQuery(this).tipso({
                        position: 'top',
                        size: 'small',
                        background: '#939393',
                        color: '#ffffff',
                        width: false,
                        maxWidth: 400,
                        useTitle: true
                    });
                });
            }
		}
	});
	var filter_box = jQuery('#arm_filter_wrapper').html();
	jQuery('div#armember_datatable_filter').parent().append(filter_box);
	jQuery('#arm_filter_wrapper').remove();
}
function ChangeID(id) {
	document.getElementById('delete_id').value = id;
}
// ]]>
</script>
<?php

global $arm_members_activity;
$setact = 0;
global $check_sorting;
$setact = $arm_members_activity->$check_sorting();
?>
<div class="wrap arm_page arm_subscription_plans_main_wrapper">
	<?php
    if ($setact != 1) {
        $admin_css_url = admin_url('admin.php?page=arm_manage_license');
        ?>
        <div style="margin-top:20px;margin-bottom:20px;border-left: 4px solid #ffba00;box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);height:20px;width:99%;padding:10px 0px 10px 10px;background-color:#ffffff;color:#000000;font-size:16px;display:block;visibility:visible;text-align:left;" >ARMember License is not activated. Please activate license from <a href="<?php echo $admin_css_url; ?>">here</a></div>
    <?php } ?>
	<div class="content_wrapper arm_subscription_plans_content" id="content_wrapper">
		<div class="page_title">
			<?php _e('Manage Membership plans','ARMember');?>
			<div class="arm_add_new_item_box">
				<a class="greensavebtn" href="<?php echo admin_url('admin.php?page='.$arm_slugs->manage_plans.'&action=new');?>"><img align="absmiddle" src="<?php echo MEMBERSHIP_IMAGES_URL ?>/add_new_icon.png"><span><?php _e('Add New Plan', 'ARMember') ?></span></a>
			</div>
			<div class="armclear"></div>
		</div>
		<div class="armclear"></div>
		<div class="arm_subscription_plans_list">
			<form method="GET" id="subscription_plans_list_form" class="data_grid_list" onsubmit="return apply_bulk_action_subscription_plans_list();">
				<input type="hidden" name="page" value="<?php echo $arm_slugs->manage_plans;?>" />
				<input type="hidden" name="armaction" value="list" />
			    <div id="armmainformnewlist">
			    	<div class="arm_loading_grid" style="display: none;"><img src="<?php echo MEMBERSHIP_IMAGES_URL; ?>/loader.gif" alt="Loading.."></div>
					<table cellpadding="0" cellspacing="0" border="0" class="display arm_on_display" id="armember_datatable" style="visibility: hidden;">
						<thead>
							<tr>
								<th class="arm_min_width_50"><?php _e('Plan ID','ARMember');?></th>
								<th class="arm_min_width_200"><?php _e('Plan Name','ARMember');?></th>
								<th style=""><?php _e('Plan Type','ARMember');?></th>
								<th class="arm_width_100"><?php _e('Members','ARMember');?></th>
                                <th class="arm_width_120"><?php _e('Wp Role','ARMember');?></th>							
								<th class="armGridActionTD"></th>
							</tr>
						</thead>
						<tbody>
						<?php 
						$form_result = $arm_subscription_plans->arm_get_all_subscription_plans();
						if (!empty($form_result)) {
							$arm_is_multisite = is_multisite();
							$arm_current_blog_id = !empty($arm_is_multisite) ? get_current_blog_id() : 0;
										
                           $arm_user_query = $wpdb->get_results($wpdb->prepare("SELECT `user_id`, `meta_value` FROM `".$wpdb->usermeta."` WHERE `meta_key` = %s",'arm_user_plan_ids'));
                           $arm_user_array = array(); 
                           if(!empty($arm_user_query)){
                                foreach($arm_user_query as $arm_user){
                                	$user_meta=get_userdata($arm_user->user_id);
									$user_roles= !empty($user_meta->roles) ? $user_meta->roles : array();
									if(!in_array('administrator', $user_roles)) {

										if ($arm_is_multisite) {
											if(is_user_member_of_blog($arm_user->user_id, $arm_current_blog_id))
											{
												$arm_user_array[$arm_user->user_id] = maybe_unserialize($arm_user->meta_value);
											}
											else
											{
												continue;
											}
										}
										else {
                                    		$arm_user_array[$arm_user->user_id] = maybe_unserialize($arm_user->meta_value);
                                    	}
                                	}
                                }
                            }
                                                    
							foreach($form_result as $planData) {
								$planObj = new ARM_Plan();
								$planObj->init((object) $planData);
								$planID = $planData['arm_subscription_plan_id'];
                                $total_users = 0;
                                if(!empty($arm_user_array)){
                                    foreach($arm_user_array as $arm_user_id => $arm_user_plans){
                                        if(!empty($arm_user_plans) && in_array($planID, $arm_user_plans)){
                                            $total_users++;
                                        }
                                    }
                                }
                                                                
                                                                
                                                                
								$edit_link = admin_url('admin.php?page='.$arm_slugs->manage_plans.'&action=edit_plan&id='.$planID);
								?>
								<tr class="row_<?php echo $planID;?>">
                                                                    <td class=""><?php echo '<a href="'.$edit_link.'">'. $planID .'</a> ';?></td>
									<td class=""><?php echo '<a href="'.$edit_link.'">'. esc_html(stripslashes($planObj->name)) .'</a> ';?></td>
									<td><?php //echo $planObj->plan_text(true);?>
										<?php 

										if( $planObj->is_recurring() && isset($planObj->options['payment_cycles']) && count($planObj->options['payment_cycles']) > 1 ) {
											echo '<span class="arm_item_status_text active">' . __('Paid', 'ARMember') . '</span><br/>
											<a href="javascript:void(0);" onclick="arm_plan_cycle('. $planID .')">' . __('Multiple Cycle', 'ARMember') . '</a>';
										} else {
											echo $planObj->plan_text(true);
										}
										?>
									</td>
									<td class="center">
									<?php
                                    $planMembers = $total_users;
									if ($planMembers > 0) {
                                        $membersLink = admin_url('admin.php?page=' . $arm_slugs->manage_members . '&plan_id=' . $planID);
										echo "<a href='{$membersLink}'>".$planMembers."</a>";
									} else {
										echo $planMembers;
									}
									?>										
									</td>
									<td><?php 
									$planRole = $planObj->plan_role;
                                                                        if (!empty($user_roles1[$planRole])) {
										echo $user_roles1[$planRole];
									} else {
										echo '-';
									}
									?></td>						
									<td class="armGridActionTD"><?php
										$gridAction = "<div class='arm_grid_action_btn_container'>";
					    if (current_user_can('arm_manage_plans')) {
											$gridAction .= "<a href='" . $edit_link . "'><img src='".MEMBERSHIP_IMAGES_URL."/grid_edit.png' onmouseover=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_edit_hover.png';\" class='armhelptip' title='".__('Edit Plan','ARMember')."' onmouseout=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_edit.png';\" /></a>";
											$gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$planID});'><img src='".MEMBERSHIP_IMAGES_URL."/grid_delete.png' class='armhelptip' title='".__('Delete','ARMember')."' onmouseover=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_delete_hover.png';\" onmouseout=\"this.src='".MEMBERSHIP_IMAGES_URL."/grid_delete.png';\" /></a>";
											if (empty($planMembers) || $planMembers == 0) {
												$gridAction .= $arm_global_settings->arm_get_confirm_box($planID, __("Are you sure you want to delete this plan?", 'ARMember'), 'arm_plan_delete_btn');
											} else {
												$gridAction .= $arm_global_settings->arm_get_confirm_box($planID, __("This plan has one or more subscribers. So this plan can not be deleted.", 'ARMember'), 'arm_plan_delete_btn_not arm_hide');
											}
										}
										$gridAction .= "</div>";
										echo '<div class="arm_grid_action_wrapper">'.$gridAction.'</div>';
									?></td>
								</tr>
							<?php 
							}//End Foreach
						}
						?>
						</tbody>
					</table>
					<div class="armclear"></div>
					<input type="hidden" name="show_hide_columns" id="show_hide_columns" value="<?php _e('Show / Hide columns','ARMember');?>"/>
					<input type="hidden" name="search_grid" id="search_grid" value="<?php _e('Search','ARMember');?>"/>
					<input type="hidden" name="entries_grid" id="entries_grid" value="<?php _e('plans','ARMember');?>"/>
					<input type="hidden" name="show_grid" id="show_grid" value="<?php _e('Show','ARMember');?>"/>
					<input type="hidden" name="showing_grid" id="showing_grid" value="<?php _e('Showing','ARMember');?>"/>
					<input type="hidden" name="to_grid" id="to_grid" value="<?php _e('to','ARMember');?>"/>
					<input type="hidden" name="of_grid" id="of_grid" value="<?php _e('of','ARMember');?>"/>
					<input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php _e('No matching plans found','ARMember');?>"/>
					<input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php _e('No any subscription plan found.','ARMember');?>"/>
					<input type="hidden" name="filter_grid" id="filter_grid" value="<?php _e('filtered from','ARMember');?>"/>
					<input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php _e('total','ARMember');?>"/>
					<?php wp_nonce_field( 'arm_wp_nonce' );?>
				</div>
				<div class="footer_grid"></div>
			</form>
		</div>
		<?php 
		/* **********./Begin Bulk Delete Plan Popup/.********** */
		$bulk_delete_plan_popup_content = '<span class="arm_confirm_text">'.__("Are you sure you want to delete this plan(s)?",'ARMember' ).'</span>';
		$bulk_delete_plan_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
		$bulk_delete_plan_popup_arg = array(
			'id' => 'delete_bulk_plan_message',
			'class' => 'delete_bulk_plan_message',
            'title' => __('Delete Plan(s)', 'ARMember'),
			'content' => $bulk_delete_plan_popup_content,
			'button_id' => 'arm_bulk_delete_plan_ok_btn',
			'button_onclick' => "arm_delete_bulk_plan('true');",
		);
		echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_plan_popup_arg);
		/* **********./End Bulk Delete Plan Popup/.********** */
		?>
		<div class="armclear"></div>
	</div>
</div>


<script type="text/javascript" charset="utf-8">
// <![CDATA[
var ARM_IMAGE_URL = "<?php echo MEMBERSHIP_IMAGES_URL; ?>";
// ]]>
</script>

<div class="arm_plan_cycle_detail_popup popup_wrapper arm_import_user_list_detail_popup_wrapper <?php echo (is_rtl()) ? 'arm_page_rtl' : ''; ?>" >    
    <div>
        <div class="popup_header">
            <span class="popup_close_btn arm_popup_close_btn arm_plan_cycle_detail_close_btn"></span>
            <input type="hidden" id="arm_edit_plan_user_id" />
            <span class="add_rule_content"><?php _e('Plans Cycles', 'ARMember'); ?> <span class="arm_plan_name"></span></span>
        </div>
        <div class="popup_content_text arm_plan_cycle_text arm_text_align_center" >
        	<div class="arm_width_100_pct" style="margin: 45px auto;">	<img src="<?php echo MEMBERSHIP_IMAGES_URL."/arm_loader.gif"; ?>"></div>
        </div>
        <div class="armclear"></div>
    </div>

</div>
<?php
    echo $ARMember->arm_get_need_help_html_content('membership-plans-list');
?>