<?php global $wpdb, $arm_global_settings, $arm_subscription_plans, $arm_affiliate_settings, $arm_aff_affiliate, $arm_version; 
?>

<div class="wrap arm_page arm_manage_members_main_wrapper">
	<div class="content_wrapper" id="content_wrapper">
		<div class="page_title">
			<?php _e('Manage Banners', 'ARM_AFFILIATE');?>
                        <div class="arm_add_new_item_box">
                            <a class="greensavebtn arm_add_affiliate_user" href="<?php echo admin_url('admin.php?page=arm_affiliate_banners&action=new_item'); ?>">
                                <img align="absmiddle" src="<?php echo ARM_AFFILIATE_IMAGES_URL; ?>/add_new_icon.png" />
                                <span><?php _e('Add Banner', 'ARM_AFFILIATE') ?></span>
                            </a>
                        </div>
			<div class="armclear"></div>
		</div>
		<div class="armclear"></div>
                <div class="arm_members_grid_container" id="arm_members_grid_container">
			<?php 
			if (file_exists(ARM_AFFILIATE_VIEW_DIR . '/arm_banner_list_records.php')) {
				include( ARM_AFFILIATE_VIEW_DIR.'/arm_banner_list_records.php');
			}
			?>
		</div>
		<?php 
		global $arm_global_settings;
		/* **********./Begin Bulk Delete Member Popup/.********** */
		$bulk_delete_member_popup_content = '<span class="arm_confirm_text">'.__("Are you sure you want to delete this banner(s)?",'ARM_AFFILIATE' );
		$bulk_delete_member_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
		$bulk_delete_member_popup_arg = array(
			'id' => 'delete_bulk_form_message',
			'class' => 'delete_bulk_form_message',
			'title' => __('Delete Banner(s)', 'ARM_AFFILIATE'),
			'content' => $bulk_delete_member_popup_content,
			'button_id' => 'arm_bulk_delete_member_ok_btn',
			'button_onclick' => "apply_banner_bulk_action('bulk_delete_flag');",
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