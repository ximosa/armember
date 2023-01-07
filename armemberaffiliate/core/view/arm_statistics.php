<?php 
    global $wpdb, $arm_aff_statistics, $arm_affiliate_settings, $arm_global_settings, $arm_payment_gateways, $arm_version; 
    $armaffstatistics = $arm_aff_statistics->arm_aff_get_statistics();
    
    $date_format = $arm_global_settings->arm_get_wp_date_format();
    $current_mon_start_date = date('Y-m-01');
    $current_mon_end_date = date('Y-m-t');    
    $today_date = date($date_format);
    $start_date = date($date_format, strtotime($current_mon_start_date));
    $end_date = date($date_format, strtotime($current_mon_end_date));
    
    $current_date = date($date_format, strtotime($today_date.' -1 months'));
    $before_one_month_date = date($date_format, strtotime($today_date));
    
    $active = 'arm_general_settings_tab_active';
    $b_action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "arm_aff_visits";
    
    $global_currency = $arm_payment_gateways->arm_get_global_currency();
    

?>

<div class="wrap arm_page arm_manage_members_main_wrapper">
	<div class="content_wrapper" id="content_wrapper">
		<div class="page_title">
			<?php _e('Manage Statistics', 'ARM_AFFILIATE');?>
			<div class="armclear"></div>
		</div>
		<div class="armclear"></div>
		<div class="arm_members_grid_container" id="arm_members_grid_container">
                        <div class="arm_dashboard_member_summary">
			
                            <div class="arm_box_wrapper">
                                <div class="arm_box_title"> Earnings </div>
                                <div class="arm_month_total_visitor arm_member_summary">
					<a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics['total_earning']); ?> </div>
						<div class="arm_member_summary_label"><?php _e('Total', 'ARM_AFFILIATE');?></div>
					</a>
                                        <a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics['month_earning']); ?> </div>
						<div class="arm_member_summary_label"><?php _e('Current Month', 'ARM_AFFILIATE');?></div>
					</a>
				</div>
                            </div>	
                            <div class="arm_box_wrapper">
                                <div class="arm_box_title"> Payments (Paid) </div>
				<div class="arm_active_members arm_member_summary">
					<a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics['total_paid']); ?> </div>
						<div class="arm_member_summary_label"><?php _e('Total', 'ARM_AFFILIATE');?></div>
					</a>
                                        <a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics['month_paid']); ?> </div>
						<div class="arm_member_summary_label"><?php _e('Current Month', 'ARM_AFFILIATE');?></div>
					</a>
				</div>
                            </div>
                            <div class="arm_box_wrapper">
                                <div class="arm_box_title"> Payments (Unpaid) </div>
				<div class="arm_membership_plans arm_member_summary">
					<a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics['total_unpaid']); ?> </div>
						<div class="arm_member_summary_label"><?php _e('Total', 'ARM_AFFILIATE');?></div>
					</a>
                                        <a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics['month_unpaid']); ?> </div>
						<div class="arm_member_summary_label"><?php _e('Current Month', 'ARM_AFFILIATE');?></div>
					</a>
				</div>
                            </div>
                            
                            
                            <div class="arm_box_wrapper">
                                <div class="arm_box_title"> Visitor </div>
                                <div class="arm_total_visitor arm_member_summary">
					<a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $armaffstatistics['total_visits']; ?> </div>
						<div class="arm_member_summary_label"><?php _e('Total', 'ARM_AFFILIATE');?></div>
					</a>
                                        <a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $armaffstatistics['month_visits']; ?> </div>
						<div class="arm_member_summary_label"><?php _e('Current Month', 'ARM_AFFILIATE');?></div>
					</a>
				</div>
                            </div>	
                            <div class="arm_box_wrapper">
                                <div class="arm_box_title"> Referral </div>
				<div class="arm_total_members arm_member_summary">
					<a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $armaffstatistics['total_referral']; ?> </div>
						<div class="arm_member_summary_label"><?php _e('Total Referrals', 'ARM_AFFILIATE');?></div>
					</a>
                                        <a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $armaffstatistics['month_referral']; ?> </div>
						<div class="arm_member_summary_label"><?php _e('Current Month', 'ARM_AFFILIATE');?></div>
					</a>
				</div>
                            </div>
                            <div class="arm_box_wrapper">
                                <div class="arm_box_title"> Affiliate User </div>
				<div class="arm_inactive_members arm_member_summary">
					<a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $armaffstatistics['total_affiliate']; ?> </div>
						<div class="arm_member_summary_label"><?php _e('Total', 'ARM_AFFILIATE');?></div>
					</a>
                                        <a href="#" class="welcome-icon">
						<div class="arm_member_summary_count"> <?php echo $armaffstatistics['month_affiliate']; ?> </div>
						<div class="arm_member_summary_label"><?php _e('Current Month', 'ARM_AFFILIATE');?></div>
					</a>
				</div>
                            </div>
                           
                            	
                            	
                            
			</div>
                        <br/>
                        <br/>
                        <div class="armclear"></div>
                        <div class="armclear"></div>
                    
                        <div class="arm_general_settings_wrapper">
			<div class="arm_general_settings_tab_wrapper">
				<a class="arm_general_settings_tab <?php echo(in_array($b_action, array('arm_aff_visits'))) ? $active : ""; ?>" href="<?php echo admin_url('admin.php?page=arm_affiliate_statistics'); ?>">&nbsp;<?php _e('Visits', 'ARM_AFFILIATE'); ?>&nbsp;&nbsp;</a>
                <a class="arm_general_settings_tab <?php echo (in_array($b_action, array('arm_aff_summery'))) ? $active : "";?>" href="<?php echo admin_url('admin.php?page=arm_affiliate_statistics&action=arm_aff_summery'); ?>">&nbsp;&nbsp;<?php _e('Summary', 'ARM_AFFILIATE'); ?>&nbsp;&nbsp;</a>
				
				<div class="armclear"></div>
                        </div>			
			<div class="arm_settings_container">
				<?php 
				$file_path = ARM_AFFILIATE_VIEW_DIR . '/arm_statistics_list_records.php';
				switch ($b_action)
				{
					case 'arm_aff_visits':
						$file_path = ARM_AFFILIATE_VIEW_DIR . '/arm_statistics_list_records.php';
						break;
					case 'arm_aff_summery':
						$file_path = ARM_AFFILIATE_VIEW_DIR . '/arm_statistics_summery_records.php';
						break;
					default:
						$file_path = ARM_AFFILIATE_VIEW_DIR . '/arm_statistics_list_records.php';
						break;
				}
				if (file_exists($file_path)) {
					include($file_path);
				}
                ?>
			</div>
		</div>
                        
		</div>
	</div>
</div>
<?php
    if(version_compare($arm_version, '4.0.1', '<'))
    {
?>
        <style type="text/css" title="currentStyle">
            @import "<?php echo ARM_URL; ?>/datatables/media/css/demo_page.css";
            @import "<?php echo ARM_URL; ?>/datatables/media/css/demo_table_jui.css";
            @import "<?php echo ARM_URL; ?>/datatables/media/css/jquery-ui-1.8.4.custom.css";
            
            .paginate_page a{display:none;}
            #poststuff #post-body {margin-top: 32px;}
            .DTFC_ScrollWrapper{background-color: #EEF1F2;}
        </style>
<?php
    }
    else
    {
?>
        <style type="text/css">
            .arm_datatable_filters_options{ padding: 1rem; }
            .buttons-colvis{ padding: 0.5rem !important; visibility: hidden; }
        </style>
<?php        
    }
?>
<?php $arm_affiliate_settings->arm_affiliate_get_footer(); ?>