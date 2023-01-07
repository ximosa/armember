<?php global $wpdb, $arm_dd, $arm_global_settings, $arm_members_class, $arm_subscription_plans, $arm_dd_items;

$user_roles = $arm_global_settings->arm_get_all_roles();
$all_members = $arm_members_class->arm_get_all_members(0, 0);
$all_active_plans = $arm_subscription_plans->arm_get_all_active_subscription_plans('', '', true);
$allowed_user_lbl = __('Allowed User', 'ARM_DD');
$denied_user_lbl = __('Denied User', 'ARM_DD');


if(isset($_REQUEST['dd_action']) && $_REQUEST['dd_action']=='download_sample'){

    $arm_dd_items->arm_dd_download_sample_file();

}
 ?>

<div class="wrap arm_page arm_manage_items_main_wrapper">
    <div class="content_wrapper" id="content_wrapper">
        <div class="page_title">
            <?php _e('Manage Download Items', 'ARM_DD');?>
            <div class="arm_add_new_item_box arm_add_new_item_box1">
                <a class="greensavebtn arm_add_item" href="<?php echo admin_url('admin.php?page=arm_dd_item&action=new_item'); ?>">
                    <img align="absmiddle" src="<?php echo ARM_DD_IMAGES_URL; ?>/add_new_icon.png" />
                    <span><?php _e('Add Download Item', 'ARM_DD') ?></span>
                </a>
                <a class="greensavebtn arm_add_item_bulk" href="javascript:void(0)">
                    <img align="absmiddle" src="<?php echo ARM_DD_IMAGES_URL; ?>/bulk_import_icon.png" />
                    <span><?php _e('Bulk Import', 'ARM_DD') ?></span>
                </a>
            </div>
            <div class="armclear"></div>
        </div>
        <div class="armclear"></div>
        <div class="arm_items_grid_container" id="arm_items_grid_container">
            <?php 
                if ( file_exists( ARM_DD_VIEW_DIR . '/arm_dd_items_records.php' ) ) {
                    include( ARM_DD_VIEW_DIR . '/arm_dd_items_records.php' );
                }
            ?>
        </div>
        <?php
            /* **********./Begin Bulk Delete item Popup/.********** */
            $bulk_delete_item_popup_content = '<span class="arm_confirm_text">'.__("Are you sure you want to delete this item(s)?",'ARM_DD' );
            $bulk_delete_item_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
            $bulk_delete_item_popup_arg = array(
                    'id' => 'delete_bulk_form_message',
                    'class' => 'delete_bulk_form_message',
                    'title' => __('Delete Item(s)', 'ARM_DD'),
                    'content' => $bulk_delete_item_popup_content,
                    'button_id' => 'arm_bulk_delete_item_ok_btn',
                    'button_onclick' => "arm_item_bulk_action('bulk_delete_flag');",
            );
            echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_item_popup_arg);
            /* **********./End Bulk Delete item Popup/.********** */
        ?>
    </div>
</div>


<div class="arm_import_download_list_detail_popup popup_wrapper arm_import_download_list_detail_popup_wrapper" style="width:1000px;">
    <form method="GET" id="arm_add_import_download_form" class="arm_admin_form" onsubmit="return arm_add_import_download_form_action();">
        <div class="popup_wrapper_inner" style="overflow: hidden;">
            <div class="popup_header">
                <span class="popup_close_btn arm_popup_close_btn arm_import_download_list_detail_close_btn"></span>
                <span class="add_rule_content"><?php _e('Import Download Items', MEMBERSHIP_TXTDOMAIN); ?></span>
            </div>
            <div class="popup_content_text arm_import_download_list_detail_popup_text">
                <div class="arm_import_processing_loader">
                    <div class="arm_import_processing_text"><?php _e('Processing',MEMBERSHIP_TXTDOMAIN); ?></div>
                </div>
            </div>
            <div class="popup_content_btn popup_footer">
                
                        
                <div class="arm_import_progressbar">
                    <div class="arm_import_progressbar_inner"></div>
                </div>
                <div class="popup_content_btn_wrapper">
                    <img src="<?php echo MEMBERSHIP_IMAGES_URL . '/arm_loader.gif'; ?>" class="arm_loader_img" style="position: relative;top: 15px;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left'; ?>;" width="20" height="20"/>
                    <button class="arm_cancel_btn arm_import_download_list_detail_previous_btn" type="button"><?php _e('Previous', MEMBERSHIP_TXTDOMAIN); ?></button>
                    <button class="arm_submit_btn arm_add_import_download_submit_btn" type="submit"><?php _e('Confirm', MEMBERSHIP_TXTDOMAIN); ?></button>
                    <button class="arm_cancel_btn arm_import_download_list_detail_close_btn" id="arm_download_cancel_btn" type="button"><?php _e('Cancel', MEMBERSHIP_TXTDOMAIN); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=arm_dd_item'); ?>" class="arm_cancel_btn" id="arm_download_close_btn" type="button"><?php _e('Close', MEMBERSHIP_TXTDOMAIN); ?></a>
                </div>
            </div>
            <div class="armclear"></div>
        </div>
    </form>
</div>


<div class="arm_dd_bulk_download_history_popup popup_wrapper <?php echo (is_rtl()) ? 'arm_page_rtl' : ''; ?>" style="width:1000px; min-height: 200px;">
    
        <div>
            <div class="popup_header">
                <span class="popup_close_btn arm_popup_close_btn arm_dd_bulk_download_history_popup_close_btn"></span>
               
                <span class="add_rule_content"><?php _e('Import Download Items', 'ARM_DD'); ?> <span class="arm_manage_plans_username"></span></span>
            </div>
            <div class="popup_content_text arm_dd_bulk_download_history_popup_detail">

            </div>
            <div class="armclear"></div>
        </div>
    
</div>


<div class="arm_dd_bulk_import_item_popup popup_wrapper <?php echo (is_rtl()) ? 'arm_page_rtl' : ''; ?>" style="width:1000px; min-height: 200px;">
    <form method="POST" id="arm_dd_bulk_import_item_form" class="arm_admin_form arm_dd_bulk_import_item_form">
        <div>
            <div class="popup_header">
                <span class="popup_close_btn arm_popup_close_btn arm_dd_bulk_import_item_popup_close_btn"></span>
               
                <span class="add_rule_content"><?php _e('Import Download Items', 'ARM_DD'); ?> <span class="arm_manage_plans_username"></span></span>
            </div>
            <div class="popup_content_text arm_dd_bulk_import_item_detail_popup">
                
            <table class="form-table">
                        <tr class="form-field">
                            <th class="arm-form-table-label"><?php _e('Upload File',MEMBERSHIP_TXTDOMAIN);?></th>
                            <td class="arm-form-table-content">
                                <input type="file" name="import_bulk_download" id="import_bulk_download" data-msg-required="<?php _e('Please select a file.', MEMBERSHIP_TXTDOMAIN); ?>" class="armDownloadUpload" accept=".csv">
                                <input class="arm_file_url" type="hidden" name="import_bulk_download" value=""><br/>
                                <div class="arm_info_text"><?php _e('Only .csv file allowed.', MEMBERSHIP_TXTDOMAIN);?></div>
                                <div class="arm_info_text"><?php _e('CSV file must contain at max 100 records.', MEMBERSHIP_TXTDOMAIN);?></div>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th>
                                <label for="arm_item_permission_type">
                                    <?php _e('Permission Type','ARM_DD');?>
                                </label>
                            </th>
                            <td>
                                <input type='hidden' id="arm_item_permission_type" name="arm_item_permission_type" value="any"/>
                                <dl class="arm_selectbox column_level_dd">
                                    <dt>
                                        <span></span>
                                        <input type="text" style="display:none;" value="" class="arm_autocomplete"/>
                                        <i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_item_permission_type">
                                            <li data-label="<?php _e('All Users','ARM_DD');?>" data-value="any"><?php _e('All Users','ARM_DD');?></li>
                                            <li data-label="<?php _e('User Wise Restriction','ARM_DD');?>" data-value="user"><?php _e('User Wise Restriction','ARM_DD');?></li>
                                            <li data-label="<?php _e('Plan Wise Restriction','ARM_DD');?>" data-value="plan"><?php _e('Plan Wise Restriction','ARM_DD');?></li>
                                            <li data-label="<?php _e('Role Wise Restriction','ARM_DD');?>" data-value="role"><?php _e('Role Wise Restriction','ARM_DD');?></li>
                                        </ul>
                                    </dd>
                                </dl>
                            </td>
                        </tr>

                        <tr class="form-field permission permission_user">
                            <th>
                                <label for="arm_item_type">
                                    <?php _e('Restriction User','ARM_DD');?>
                                </label>
                            </th>
                            <td>
                                <span class="arm_user_restriction_type" id="arm_user_restriction_type">
                                    <input type="radio" class="arm_iradio" checked="checked" value="allowed_user" name="arm_user_restriction_type" id="arm_dd_allowed_user" data-label="<?php echo $allowed_user_lbl; ?>" />
                                    <label for="arm_dd_allowed_user"><?php echo $allowed_user_lbl; ?></label>

                                    <input type="radio" class="arm_iradio"  value="denied_user" name="arm_user_restriction_type" id="arm_dd_denied_user" data-label="<?php echo $denied_user_lbl; ?>" />
                                    <label for="arm_dd_denied_user"><?php echo $denied_user_lbl; ?></label>
                                </span>
                            </td>
                        </tr>
                        
                        <tr class="form-field permission permission_user">
                            <th>
                                <label for="arm_item_permission" class="arm_permission_user_label">
                                    <?php _e('Select User','ARM_DD');?>
                                </label>
                            </th>
                            <td class="arm_multiauto_user_field">
                                <input id="arm_dd_items_users_input" type="text" value="" placeholder="<?php _e('Search by username or email...', 'ARM_DD');?>" data-msg-required="<?php _e('Please select user.', 'ARM_DD');?>">
                                <div class="arm_users_multiauto_items arm_dd_items_user_required_wrapper" id="arm_users_multiauto_items" style="display: none;">
                            </td>
                        </tr>
                        
                        <tr class="form-field permission permission_plan" >
                            <th>
                                <label for="arm_item_permission">
                                    <?php _e('Allowed Plan','ARM_DD');?>
                                </label>
                            </th>
                            <td>
                                <select id="arm_plans_select" class="arm_chosen_selectbox arm_plan_list" data-msg-required="<?php _e('Please select plan.', 'ARM_DD');?>" name="arm_plans[]" data-placeholder="<?php _e('Select User Plan(s)..', 'ARM_DD');?>" multiple="multiple" style="width:500px;">
                                    <?php if (!empty($all_active_plans)):?>
                                        <?php foreach ($all_active_plans as $p): ?>
                                            <?php 
                                            $p_id = $p['arm_subscription_plan_id'];
                                            
                                            ?>
                                            <option class="arm_message_selectbox_op" value="<?php echo $p_id;?>">
                                                <?php echo stripslashes(esc_attr($p['arm_subscription_plan_name']));?>
                                            </option>
                                        <?php endforeach;?>
                                    <?php else: ?>
                                        <option value=""><?php _e('No Plan(s) Available', 'ARM_DD');?></option>
                                    <?php endif;?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr class="form-field permission permission_role">
                            <th>
                                <label for="arm_item_permission">
                                    <?php _e('Allowed Role','ARM_DD');?>
                                </label>
                            </th>
                            <td>
                                <select id="arm_roles_select" class="arm_chosen_selectbox arm_role_list" data-msg-required="<?php _e('Please select role.', 'ARM_DD');?>" name="arm_roles[]" data-placeholder="<?php _e('Select User Role(s)..', 'ARM_DD');?>" multiple="multiple" style="width:500px;">
                                    <?php if (!empty($user_roles)):?>
                                        <?php foreach ($user_roles as $key => $val): ?>
                                            
                                            <option class="arm_message_selectbox_op" value="<?php echo $key;?>">
                                                <?php echo $val;?>
                                            </option>
                                        <?php endforeach;?>
                                    <?php else: ?>
                                        <option value=""><?php _e('No Role(s) Available', 'ARM_DD');?></option>
                                    <?php endif;?>
                                </select>
                            </td>
                        </tr>
                        <tr class="form-field">
                            <th></th>
                            <td class="arm-form-table-content">
                                                               
                                <button id="arm_user_download_btn" class="armemailaddbtn" name="arm_action" value="user_download" type="submit"><?php _e('Import', 'ARM_DD');?></button>&nbsp;<img src="<?php echo ARM_DD_IMAGES_URL.'/arm_loader.gif' ?>" class="arm_loader_img_download_user" style="position:relative;top:8px;display:none;" width="24" height="24" />
                            </td>
                        </tr>
                        <tr class="form-field">
                        <th></th>
                            <td>
                                <span class="">
                                    <?php _e("Please download sample csv", 'ARM_DD');?>&nbsp;<a href="<?php echo admin_url('admin.php?page=arm_dd_item&dd_action=download_sample');?>" class="arm_download_sample_csv_link" target="_blank"><?php _e('here', 'ARM_DD');?></a>.
                                </span>
                            </td>
                        </tr>
            </table>

            </div>
            <div class="armclear"></div>
        </div>
    </form>
</div>

<script type="text/javascript">
    __PROCESSING = '<?php echo __('Processing','ARM_DD'); ?>';
    __HUNDREDRECORDS = '<?php echo __('Uploaded CSV file contain more than 100 records. You can import only 100 records at a time.','ARM_DD'); ?>';
    __EMPTYFILEURL = '<?php echo __('Uploaded CSV file must contain non empty file_url fields.','ARM_DD'); ?>';
</script>

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