<?php 
if(!class_exists('armaffAdminDashboardWidgets'))
{
	class armaffAdminDashboardWidgets
	{
		function __construct()
		{
			add_action('admin_head', array(&$this, 'armAdminDashboardWidgetsStyle'));
			add_action('wp_dashboard_setup', array(&$this, 'armAdminDashboardWidgets_init'));
		}
		function armAdminDashboardWidgetsStyle()
		{
			global $pagenow, $arm_ajaxurl;
			if (current_user_can('administrator') && $pagenow == 'index.php')
			{
				?>
				<style type="text/css">
				.armAdminDashboardWidgetContent{width: 100%;display: block;box-sizing: border-box;font-family: "Open Sans",sans-serif;}
				.armAdminDashboardWidgetContent table{width: 100%;box-sizing: border-box;border: 1px solid #EDEEEF;border-radius: 3px;table-layout: fixed;word-wrap: break-word;}
				.armAdminDashboardWidgetContent table tr:nth-child(odd) {background-color: #FFF;}
				.armAdminDashboardWidgetContent table tr:nth-child(even) {background-color: #F6F8F8;}
				.armAdminDashboardWidgetContent table tr:hover td {background-color: #C8F9FB !important;}
				.armAdminDashboardWidgetContent table th,
				.armAdminDashboardWidgetContent table td{padding: 7px 5px;word-break: break-word;font-size: 13px;}
				.armAdminDashboardWidgetContent table th {
					background: none;
					background-color: #F6F8F8;
					border: 0;
					border-bottom: 1px solid #EDEEEF;
					color: #3C3E4F;
					font-size: 14px;
					font-weight: normal;
					vertical-align: middle;
					height: 20px;
				}
				[dir="rtl"] .armAdminDashboardWidgetContent table th {
					text-align: right;
				}
				.armAdminDashboardWidgetContent table td {border-bottom: 1px solid #F1F1F1;color: #8A8A8A;}
				.arm_center{text-align:center;}
				.arm_empty{display:block;}
				.arm_view_all_link{margin: 10px 0 5px;display: block;box-sizing: border-box;text-align: right;}
				.arm_view_all_link a{padding:5px;}
				.arm_view_all_link a:focus {outline: none;box-shadow: none;}
				.arm_members_statisctics ul{margin-left: 1px !important;}
				.arm_recent_activity .arm_activity_listing_section{
					border-bottom: 1px solid #DDD;
					padding: 2px 0;
					margin-bottom: 6px;
					box-sizing: border-box;
				}
				.arm_recent_activity .arm_member_info_left{
					max-width: 50px;
					padding: 2px;
					margin: 2px 10px;
					box-sizing: border-box;
					float: left;
				}
				.arm_recent_activity .arm_member_info_left img{max-width: 100%;}
				.arm_recent_activity .arm_act_pageing{display:none;}
				.arm_chart_wrapper{
					border: 1px solid #DDD;
					display: block;
					box-sizing: border-box;
					width: 100%;
					margin-bottom: 20px;
					direction: ltr;
				}
				.arm_plugin_logo{
					display: block;
					box-sizing: border-box;
					text-align: center;
					padding: 20px 0 20px 0;
				}
				.arm_plugin_logo img{width: auto;max-width: 100%;height: auto;}
				</style>
				<?php 
			}
		}
		function armAdminDashboardWidgets_init()
		{
			if (current_user_can('administrator'))
			{
				/* Register Admin Widgets */
				wp_add_dashboard_widget('ARMAffReferral', 'Recent Refferals', array(&$this, 'ARMAffReferral_display'));
				
				global $wp_meta_boxes;
				$normal_widgets = $wp_meta_boxes['dashboard']['normal']['core'];
                                
				$side_widgets = $wp_meta_boxes['dashboard']['side']['core'];
				$widget_backup_normal = array('ARMemberSummary' => $normal_widgets['ARMemberSummary']);
				$widget_backup_side = array(
					'ARMAffReferral' => $normal_widgets['ARMAffReferral'],			
				);
				/* Unset Widgets From Main Array */
				unset($normal_widgets['ARMAffReferral']);
				/* Sort & Save Right Side Widgets */
				$sorted_normal = array_merge($widget_backup_normal, $normal_widgets);
				$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_normal;
				/* Sort & Save Left Side Widgets */
				$sorted_side = array_merge($widget_backup_side, $side_widgets);
				$wp_meta_boxes['dashboard']['side']['core'] = $sorted_side;
			}
		}
		function ARMAffReferral_display()
		{
			global $wp, $wpdb, $arm_affiliate, $arm_payment_gateways, $arm_aff_referrals, $ARMember, $arm_slugs, $arm_global_settings, $arm_members_class, $arm_subscription_plans, $arm_default_user_details_text;

                        $referral_query = "SELECT r.arm_ref_affiliate_id, r.arm_currency, r.arm_amount, r.arm_status, u.user_login FROM {$arm_affiliate->tbl_arm_aff_referrals} r LEFT JOIN {$arm_affiliate->tbl_arm_aff_affiliates} aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$wpdb->users}` u ON u.ID = aff.arm_user_id ORDER BY arm_date_time desc LIMIT 6";

                        $referral_result = $wpdb->get_results($referral_query);

			$date_format = $arm_global_settings->arm_get_wp_date_format();
			if (!empty($referral_result)) {
				?>
				<div class="ARMRegisteredMembers_container armAdminDashboardWidgetContent">
					<table cellpadding="0" cellspacing="0" border="0" id="ARMRegisteredMembers_table" class="display">
						<thead>
							<tr>
								<th align="left"><?php _e('Affiliate User', 'ARM_AFFILIATE');?></th>
								<th align="left"><?php _e('Amount', 'ARM_AFFILIATE');?></th>
								<th align="left"><?php _e('Reference User', 'ARM_AFFILIATE');?></th>
								<th align="left"><?php _e('Status', 'ARM_AFFILIATE');?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach($referral_result as $referral):
                                                        $arm_affiliate_user_name = isset($referral->user_login) ? $referral->user_login : '';
                                                        
                                                        $arm_get_ref_affiliate_user_data = get_userdata($referral->arm_ref_affiliate_id);
                                                        $arm_ref_affiliate_user_name = isset($arm_get_ref_affiliate_user_data->user_login) ? $arm_get_ref_affiliate_user_data->user_login : '';
                                                        ?>
							<tr>
                                                            <td><?php echo ($arm_affiliate_user_name != '') ? $arm_affiliate_user_name : $arm_default_user_details_text; ?></td>
                                                            <td><?php echo $arm_payment_gateways->arm_prepare_amount($referral->arm_currency, $referral->arm_amount); ?></td>
                                                            <td><?php echo ($arm_ref_affiliate_user_name != '') ? $arm_ref_affiliate_user_name : $arm_default_user_details_text; ?></td>
                                                            <td><?php echo $arm_aff_referrals->referral_status[$referral->arm_status]; ?></td>
							</tr>
						<?php endforeach;?>
						</tbody>
					</table>
					<div class="armclear"></div>
					<div class="arm_view_all_link">
						<a href="<?php echo admin_url('admin.php?page=arm_affiliate_referral');?>"><?php _e('View All Referrals', 'ARM_AFFILIATE');?></a>
					</div>
				</div>
				<?php
			} else {
				?>
				<div class="arm_dashboard_error_box"><?php _e('There is no any recent referrals found.', 'ARM_AFFILIATE');?></div>
				<?php
			}
		}
	}
	global $armaffAdminDashboardWidgets;
	$armaffAdminDashboardWidgets = new armaffAdminDashboardWidgets();
}