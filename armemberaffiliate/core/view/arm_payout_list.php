<?php global $wpdb, $arm_global_settings, $arm_subscription_plans, $arm_affiliate_settings, $arm_aff_payouts, $arm_version; 
$all_members =  $arm_aff_payouts->arm_payment_user();
?>

<div class="wrap arm_page arm_manage_members_main_wrapper">
	<div class="content_wrapper" id="content_wrapper">
		<div class="page_title">
			<?php _e('Manage Payouts', 'ARM_AFFILIATE');?>
			<div class="armclear"></div>
		</div>
		<div class="armclear"></div>
                <?php 
		//Handle Import/Export Process
		do_action('arm_aff_payouts_export', $_REQUEST);
		?>
		<div class="arm_members_grid_container" id="arm_members_grid_container">
			<?php 
			if (file_exists(ARM_AFFILIATE_VIEW_DIR . '/arm_payout_list_records.php')) {
				include( ARM_AFFILIATE_VIEW_DIR.'/arm_payout_list_records.php');
			}
			?>
		</div>
	</div>
</div>
<?php $arm_affiliate_settings->arm_affiliate_get_footer(); ?>

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

            .arm_history .dataTables_info{ padding-top: 1.5rem !important; padding-bottom: 1rem !important; padding-left: 0.5rem !important; }
            .arm_history .dataTables_paginate{ padding-top: 1rem !important; padding-bottom: 1rem !important; }
            .arm_history .dataTables_length{ padding-top: 1rem !important; padding-bottom: 1rem !important; }

            .arm_history .display thead tr th{ border-bottom: none !important; }
        </style>
<?php        
    }
?>


<script type="text/javascript" charset="utf-8">
// <![CDATA[
//jQuery(window).load(function () {
//	document.onkeypress = stopEnterKey;
//});
function ChangeID(id) {
	document.getElementById('delete_id').value = id;
}
// ]]>
</script>

<div class="arm_export popup_wrapper" style="width: 650px;margin-top: 40px;">
    <form method="post" action="#" id="arm_export_wrapper_frm" class="arm_admin_form arm_add_affiliate_wrapper_frm">
        <table cellspacing="0">
            <tr class="popup_wrapper_inner">	
                <td class="arm_export_close_btn arm_popup_close_btn"></td>
                <td class="popup_header"><?php _e('Export To CSV', 'ARM_AFFILIATE');?></td>
                
                <td class="popup_content_text">
                    <table class="arm_table_label_affiliate">
                        <input type='hidden' id="arm_action" class="arm_action" name="arm_action" value="payouts_export_csv" />
                        
                        <tr>
                            <th>
                                <?php _e('Start Date', 'ARM_AFFILIATE');?>
                            </th>
                            <td class="arm_required_wrapper">
                                <input type="text" name="start_date" id="start_date" placeholder="<?php _e('Select Date', 'ARM_AFFILIATE');?>" class="arm_datepicker" data-dateformat='<?php echo arm_wp_date_format_to_bootstrap_datepicker(); ?>' value="">
                            </td>
                        </tr>
                        
                        <tr>
                            <th>
                                <?php _e('End Date', 'ARM_AFFILIATE');?>
                            </th>
                            <td class="arm_required_wrapper">
                                <input type="text" name="end_date" id="end_date" placeholder="<?php _e('Select Date', 'ARM_AFFILIATE');?>" class="arm_datepicker" data-dateformat='<?php echo arm_wp_date_format_to_bootstrap_datepicker(); ?>' value="">
                            </td>
                        </tr>
                        
                    </table>
                    <div class="armclear"></div>
                </td>
                
                <td class="popup_content_btn popup_footer">
                    <div class="popup_content_btn_wrapper">
                        <img src="<?php echo ARM_AFFILIATE_IMAGES_URL.'/arm_loader.gif' ?>" id="arm_loader_img" class="arm_loader_img" style="position: relative;top: 15px;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left';?>;" width="20" height="20" />
                        <button class="arm_save_btn arm_export_submit" type="submit" data-type="add"><?php _e('Export', 'ARM_AFFILIATE') ?></button>
                        <button class="arm_cancel_btn arm_export_close_btn" type="button"><?php _e('Cancel','ARM_AFFILIATE');?></button>
                    </div>
                </td>
            </tr>
        </table>
        <div class="armclear"></div>
    </form>
</div>


<!--./******************** payment history List ********************/.-->
<div class="arm_members_list_detail_popup arm_history popup_wrapper arm_members_list_detail_popup_wrapper" style="width:810px;">
    <div class="popup_wrapper_inner" style="overflow: hidden;">
        <div class="popup_header">
            <span class="popup_close_btn arm_popup_close_btn arm_members_list_detail_close_btn"></span>
            <span class="add_rule_content"><?php _e('Payment History', 'ARM_AFFILIATE'); ?></span>
        </div>
        <div class="popup_content_text arm_members_list_detail_popup_text">
            <table width="100%" cellspacing="0" class="display" id="example_1" style="min-width: 802px;">
                <thead>
                    <tr>
                        <th><?php _e('Tr. Id', 'ARM_AFFILIATE'); ?></th>
                        <th><?php _e('Date Time', 'ARM_AFFILIATE'); ?></th>
                        <th><?php _e('Amount', 'ARM_AFFILIATE'); ?></th>
                        <th><?php _e('Balance', 'ARM_AFFILIATE'); ?></th>
                    </tr>
                </thead>
            </table>
            <input type="hidden" name="search_grid" id="search_grid" value="<?php _e('Search','ARM_AFFILIATE');?>"/>
            <input type="hidden" name="entries_grid" id="entries_grid" value="<?php _e('payment','ARM_AFFILIATE');?>"/>
            <input type="hidden" name="show_grid" id="show_grid" value="<?php _e('Show','ARM_AFFILIATE');?>"/>
            <input type="hidden" name="showing_grid" id="showing_grid" value="<?php _e('Showing','ARM_AFFILIATE');?>"/>
            <input type="hidden" name="to_grid" id="to_grid" value="<?php _e('to','ARM_AFFILIATE');?>"/>
            <input type="hidden" name="of_grid" id="of_grid" value="<?php _e('of','ARM_AFFILIATE');?>"/>
            <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php _e('No payment found','ARM_AFFILIATE');?>"/>
            <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php _e('There is no any payment found.','ARM_AFFILIATE');?>"/>
            <input type="hidden" name="filter_grid" id="filter_grid" value="<?php _e('filtered from','ARM_AFFILIATE');?>"/>
            <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php _e('total','ARM_AFFILIATE');?>"/>
        </div>
        <div class="armclear"></div>
    </div>
</div>


