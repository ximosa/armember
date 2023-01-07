<?php global $wpdb, $arm_multisubsite, $arm_global_settings, $arm_members_class, $arm_subscription_plans;

?>

<div class="wrap arm_page arm_manage_items_main_wrapper">
    <div class="content_wrapper" id="content_wrapper">
        <div class="page_title">
            <?php esc_html_e('Manage Subsites', 'ARM_MULTISUBSITE');?>
            <div class="armclear"></div>
        </div>
        <div class="armclear"></div>
        <div class="arm_multisite_subsite_grid_container" id="arm_multisite_subsite_grid_container">
            <?php 
                if ( file_exists( ARM_MULTISUBSITE_VIEW_DIR . '/arm_multisite_subsite_records.php' ) ) {
                    include( ARM_MULTISUBSITE_VIEW_DIR . '/arm_multisite_subsite_records.php' );
                }
            ?>
        </div>
        <?php
            /* **********./Begin Bulk Delete item Popup/.********** */
            $bulk_delete_multisite_subsite_popup_content = '<span class="arm_confirm_text">'.esc_html__("Are you sure you want to delete this subsite(s)?",'ARM_MULTISUBSITE' );
            $bulk_delete_multisite_subsite_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
            $bulk_delete_item_popup_arg = array(
                    'id' => 'delete_bulk_form_message',
                    'class' => 'delete_bulk_form_message',
                    'title' => esc_html__('Delete Subsite(s)', 'ARM_MULTISUBSITE'),
                    'content' => $bulk_delete_multisite_subsite_popup_content,
                    'button_id' => 'arm_bulk_delete_subsite_ok_btn',
                    'button_onclick' => "arm_item_bulk_action('bulk_delete_flag');",
            );
            echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_item_popup_arg);
            /* **********./End Bulk Delete item Popup/.********** */
        ?>
    </div>
    <div class="arm_multisite_documentation_link"><a href="<?php echo ARM_MULTISUBSITE_URL."/documentation"; ?>" target="_blank">Documentation</a></div>
</div>

