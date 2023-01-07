<?php global $wpdb, $arm_dd, $arm_global_settings; ?>
<div class="wrap arm_page arm_manage_downloads_main_wrapper">
    <div class="content_wrapper" id="content_wrapper">
        <div class="page_title">
            <?php _e('Download History', 'ARM_DD');?>
            <div class="armclear"></div>
        </div>
        <div class="armclear"></div>
        <?php 
            //Handle Import/Export Process
            do_action('arm_dd_handle_export', $_REQUEST);
        ?>
        <div class="arm_downloads_grid_container" id="arm_downloads_grid_container">
            <?php 
                if ( file_exists( ARM_DD_VIEW_DIR . '/arm_dd_downloads_records.php' ) ) {
                    include( ARM_DD_VIEW_DIR . '/arm_dd_downloads_records.php' );
                }
            ?>
        </div>
        <?php
            /* **********./Begin Bulk Delete download history Popup/.********** */
            $bulk_delete_download_popup_content = '<span class="arm_confirm_text">'.__("Are you sure you want to delete this download history?",'ARM_DD' );
            $bulk_delete_download_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
            $bulk_delete_download_popup_arg = array(
                    'id' => 'delete_bulk_form_message',
                    'class' => 'delete_bulk_form_message',
                    'title' => __('Delete Download History', 'ARM_DD'),
                    'content' => $bulk_delete_download_popup_content,
                    'button_id' => 'arm_bulk_download_ok_btn',
                    'button_onclick' => "arm_download_bulk_action('bulk_delete_flag');",
            );
            echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_download_popup_arg);
            /* **********./End Bulk Delete download history Popup/.********** */
        ?>
    </div>
</div>
<?php $arm_dd->arm_dd_get_footer(); ?>
<?php
    global $arm_version;
    if(version_compare($arm_version, '4.0.1', '<'))
    {
?>
        <style type="text/css" title="currentStyle">
            @import "<?php echo MEMBERSHIP_URL; ?>/datatables/media/css/demo_page.css";
            @import "<?php echo MEMBERSHIP_URL; ?>/datatables/media/css/demo_table_jui.css";
            @import "<?php echo MEMBERSHIP_URL; ?>/datatables/media/css/jquery-ui-1.8.4.custom.css";
            
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
            .arm_datatable_filters_options { padding: 1rem !important; }
        </style>
<?php        
    }
?>