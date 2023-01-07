<?php global $wpdb, $arm_community_setting, $arm_global_settings; ?>

<div class="wrap arm_page arm_manage_members_main_wrapper">
    <div class="content_wrapper" id="content_wrapper">
        <?php echo $arm_community_setting->arm_get_community_user_activity_tab("arm_com_activity_selected"); ?>
        <div class="armclear"></div>
        <div class="arm_members_grid_container" id="arm_members_grid_container">
            <?php
            if (file_exists(ARM_COMMUNITY_VIEW_DIR . '/arm_com_activity_list_records.php')) {
                include( ARM_COMMUNITY_VIEW_DIR . '/arm_com_activity_list_records.php');
            }
            ?>
        </div>
        <?php
        /*         * *********./Begin Bulk Delete Member Popup/.********** */
        $bulk_delete_post_popup_content = '<span class="arm_confirm_text">' . __("Are you sure you want to delete this Activit(ies)?", ARM_COMMUNITY_TEXTDOMAIN);
        $bulk_delete_post_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
        $bulk_delete_post_popup_arg = array(
            'id' => 'delete_bulk_form_message',
            'class' => 'delete_bulk_form_message',
            'title' => __('Delete Activit(ies)', ARM_COMMUNITY_TEXTDOMAIN),
            'content' => $bulk_delete_post_popup_content,
            'button_id' => 'arm_bulk_delete_activity_ok_btn',
            'button_onclick' => "apply_com_activity_bulk_action('bulk_delete_flag');",
        );
        echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_post_popup_arg);
        /*         * *********./End Bulk Delete Member Popup/.********** */
        ?>
    </div>
</div>
<?php $arm_community_setting->arm_community_get_footer(); ?>
<?php
    global $arm_version;
    if(version_compare($arm_version, '4.0.1', '<'))
    {
?>
        <style type="text/css" title="currentStyle">
            @import "<?php echo ARM_COM_ARMEMBER_URL; ?>/datatables/media/css/demo_page.css";
            @import "<?php echo ARM_COM_ARMEMBER_URL; ?>/datatables/media/css/demo_table_jui.css";
            @import "<?php echo ARM_COM_ARMEMBER_URL; ?>/datatables/media/css/jquery-ui-1.8.4.custom.css";
        </style>
<?php        
    }
?>