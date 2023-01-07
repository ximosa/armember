<?php global $wpdb, $arm_global_settings, $arm_subscription_plans, $arm_affiliate_settings, $arm_payment_gateways, $arm_version; 
$currency = $arm_payment_gateways->arm_get_global_currency();
?>

<div class="wrap arm_page arm_manage_members_main_wrapper">
	<div class="content_wrapper" id="content_wrapper">
		<div class="page_title">
			<?php _e('Manage Referrals', 'ARM_AFFILIATE');?>
			<div class="armclear"></div>
		</div>
		<div class="armclear"></div>
                <?php 
		//Handle Import/Export Process
		do_action('arm_aff_handle_export', $_REQUEST);
		?>
		<div class="arm_members_grid_container" id="arm_members_grid_container">
			<?php 
			if (file_exists(ARM_AFFILIATE_VIEW_DIR . '/arm_referral_list_records.php')) {
				include( ARM_AFFILIATE_VIEW_DIR.'/arm_referral_list_records.php');
			}
			?>
		</div>
		<?php 
		global $arm_global_settings;
		/* **********./Begin Bulk Delete Member Popup/.********** */
		$bulk_delete_member_popup_content = '<span class="arm_confirm_text">'.__("Are you sure you want to delete this referral(s)?",'ARM_AFFILIATE' );
		$bulk_delete_member_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
		$bulk_delete_member_popup_arg = array(
			'id' => 'delete_bulk_form_message',
			'class' => 'delete_bulk_form_message',
			'title' => __('Delete Referral(s)', 'ARM_AFFILIATE'),
			'content' => $bulk_delete_member_popup_content,
			'button_id' => 'arm_bulk_delete_member_ok_btn',
			'button_onclick' => "apply_referral_bulk_action('bulk_delete_flag');",
		);
		echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_member_popup_arg);
		/* **********./End Bulk Delete Member Popup/.********** */
		?>
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
        </style>
<?php        
    }
?>
<!--./******************** Edit Referral Form ********************/.-->
<div class="arm_edit_referral popup_wrapper" style="width: 650px;margin-top: 40px;">
    <form method="post" action="#" id="arm_edit_referral_wrapper_frm" class="arm_admin_form arm_edit_referral_wrapper_frm">
        <table cellspacing="0">
            <tr class="popup_wrapper_inner">	
                <td class="arm_edit_referral_close_btn arm_popup_close_btn"></td>
                <td class="popup_header"><?php _e('Edit Referral', 'ARM_AFFILIATE');?></td>
                <input type="hidden" name="arm_referral_id" id="arm_referral_id" value="" />
                
                <td class="popup_content_text">
                    <table class="arm_table_label_affiliate">
                        
                        <tr class="form-field exists_user_section" id="select_user">
                            <th class="arm-form-table-label">
                                <?php _e('Username', 'ARM_AFFILIATE'); ?>
                            </th>
                            <td class="arm-form-table-content">
                                <span class="arm_aff_set_user_name"></span>
                            </td>
                        </tr>
                        
                        <tr class="form-field exists_user_section" id="select_user">
                            <th class="arm-form-table-label">
                                <?php _e('Reference Username', 'ARM_AFFILIATE'); ?>
                            </th>
                            <td class="arm-form-table-content">
                                <span class="arm_aff_set_ref_user_name"></span>
                            </td>
                        </tr>
                        
                        <tr class="form-field exists_user_section" id="select_user">
                            <th class="arm-form-table-label">
                                <?php _e('Plan Name', 'ARM_AFFILIATE'); ?>
                            </th>
                            <td class="arm-form-table-content">
                                <span class="arm_aff_set_plan_name"></span>
                            </td>
                        </tr>
                        
                        <tr>
                            <th>
                                <?php _e('Amount', 'ARM_AFFILIATE');?>
                            </th>
                            <td class="arm_required_wrapper">
                                <input type='text' id="arm_referral_amount" class="arm_referral_amount" name="arm_referral_amount" value="" /> 
                                <?php echo $currency; ?>
                            </td>
                        </tr>
                    </table>
                    <div class="armclear"></div>
                </td>
                
                <td class="popup_content_btn popup_footer">
                    <div class="popup_content_btn_wrapper">
                        <img src="<?php echo ARM_AFFILIATE_IMAGES_URL.'/arm_loader.gif' ?>" id="arm_loader_img" class="arm_loader_img" style="position: relative;top: 15px;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left';?>;" width="20" height="20" />
                        <button class="arm_save_btn arm_edit_referral_submit" type="submit" data-type="edit"><?php _e('Save', 'ARM_AFFILIATE') ?></button>
                        <button class="arm_cancel_btn arm_edit_referral_close_btn" type="button"><?php _e('Cancel','ARM_AFFILIATE');?></button>
                        <?php wp_nonce_field( 'arm_wp_nonce' );?>
                    </div>
                </td>
            </tr>
        </table>
        <div class="armclear"></div>
    </form>
</div>
<!--./******************** Edit Referral Form ********************/.-->