<?php global $wpdb, $arm_global_settings, $arm_subscription_plans, $arm_affiliate_settings, $arm_aff_affiliate, $arm_version; 
$all_members =  $arm_aff_affiliate->arm_affiliate_except_user_get_all_members(0,0);
?>

<div class="wrap arm_page arm_manage_members_main_wrapper">
	<div class="content_wrapper" id="content_wrapper">
		<div class="page_title">
			<?php _e('Manage Affiliate Users', 'ARM_AFFILIATE');?>
                        <div class="arm_add_new_item_box">
                            <a class="greensavebtn arm_add_affiliate_user" href="javascript:void(0);">
                                <img align="absmiddle" src="<?php echo ARM_AFFILIATE_IMAGES_URL; ?>/add_new_icon.png" />
                                <span><?php _e('Create Affiliate User', 'ARM_AFFILIATE') ?></span>
                            </a>
                        </div>
			<div class="armclear"></div>
		</div>
		<div class="armclear"></div>
                <div class="arm_members_grid_container" id="arm_members_grid_container">
			<?php 
			if (file_exists(ARM_AFFILIATE_VIEW_DIR . '/arm_affiliate_list_records.php')) {
				include( ARM_AFFILIATE_VIEW_DIR.'/arm_affiliate_list_records.php');
			}
			?>
		</div>
		<?php 
		global $arm_global_settings;
		/* **********./Begin Bulk Delete Member Popup/.********** */
		$bulk_delete_member_popup_content = '<span class="arm_confirm_text">'.__("Are you sure you want to delete this affiliate user(s)?",'ARM_AFFILIATE' );
		$bulk_delete_member_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
		$bulk_delete_member_popup_arg = array(
			'id' => 'delete_bulk_form_message',
			'class' => 'delete_bulk_form_message',
			'title' => __('Delete Affiliate user(s)', 'ARM_AFFILIATE'),
			'content' => $bulk_delete_member_popup_content,
			'button_id' => 'arm_bulk_delete_member_ok_btn',
			'button_onclick' => "apply_affiliate_bulk_action('bulk_delete_flag');",
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
            .arm_datatable_filters_options{ padding: 1rem !important; }
        </style>
<?php        
    }
?>

<!--./******************** Add New Affiliate Form ********************/.-->
<div class="arm_add_new_affiliate popup_wrapper" style="width: 650px;margin-top: 40px;">
    <form method="post" action="#" onsubmit="return false;" id="arm_add_affiliate_wrapper_frm" class="arm_admin_form arm_add_affiliate_wrapper_frm <?php echo is_rtl() ? 'arm_page_rtl' : ''; ?>">
        <table cellspacing="0">
            <tr class="popup_wrapper_inner">	
                <td class="add_add_affiliate_close_btn arm_popup_close_btn"></td>
                <td class="popup_header"><?php _e('Create Affiliate User', 'ARM_AFFILIATE');?></td>
                
                <td class="popup_content_text">
                    <table class="arm_table_label_affiliate">
                        
                        <tr class="form-field exists_user_section" id="select_user">
                            <th class="arm-form-table-label"><?php _e('Select User', 'ARM_AFFILIATE'); ?></th>
                            <td class="arm-form-table-content arm_auto_user_field">
                                <input type='hidden' id="arm_aff_action" class="arm_aff_action" name="arm_aff_action" value="add" />
                                <input type='text' id="arm_affiliate_user_id" class="arm_affiliate_user_id_change_input" name="arm_affiliate_user_id" value="" placeholder="<?php _e('Search by username...', 'ARM_AFFILIATE');?>" data-msg-required="<?php _e('Please select user.', 'ARM_AFFILIATE');?>" required  style="width: 210px;margin-right:0px;"/>
                                <input type="hidden" name="arm_display_admin_user" id="arm_display_admin_user" value="0">
                                <div class="arm_users_items arm_required_wrapper" id="arm_users_items" style="display: none;"></div>
                                <span id="arm_user_ids_error" class="arm_error_msg arm_user_ids_error" style="display:none;"><?php _e('Please select user.', 'ARM_AFFILIATE');?></span>         
                            </td>
                        </tr>
                        
                    </table>
                    <div class="armclear"></div>
                </td>
                
                <td class="popup_content_btn popup_footer">
                    <div class="popup_content_btn_wrapper">
                        <img src="<?php echo ARM_AFFILIATE_IMAGES_URL.'/arm_loader.gif' ?>" id="arm_loader_img" class="arm_loader_img" style="position: relative;top: 15px;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left';?>;" width="20" height="20" />
                        <button class="arm_save_btn arm_add_affiliate_submit" type="button" data-type="add"><?php _e('Save', 'ARM_AFFILIATE') ?></button>
                        <button class="arm_cancel_btn add_add_affiliate_close_btn" type="button"><?php _e('Cancel','ARM_AFFILIATE');?></button>
                    </div>
                </td>
            </tr>
        </table>
        <?php wp_nonce_field( 'arm_wp_nonce' );?>
        <div class="armclear"></div>
    </form>
</div>
<!--./******************** Add New Affiliate Form ********************/.-->
