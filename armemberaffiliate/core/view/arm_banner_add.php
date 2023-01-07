<?php
global $wpdb, $ARMember, $arm_aff_banner, $arm_affiliate_settings;
/**
 * Process Submited Form.
 */

if ( isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'add_item', 'update_item' ) ) ) {
    $arm_aff_banner->arm_aff_item_save( $_POST );
}

$except_files = '';
$arm_form_id = 0;
$form_mode = __('Add New Banner', 'ARM_AFFILIATE');
$action = 'add_item';
$cancel_url = admin_url('admin.php?page=arm_affiliate_banners');
$item_id = $arm_item_description = $arm_item_name = $arm_item_shortcode = $arm_open_new_tab = $arm_item_url = '';
$arm_item_file_content = '';
$arm_status = 1;
$arm_no_file = 0;
if(isset($_POST['action']) && $_POST['action'] == 'add_item') {
    $item_id = '';
    
     $arm_item_action = isset($_POST['action']) ? $_POST['action'] : '';
    $arm_item_id = isset($item_data['item_id']) ? $item_data['item_id'] : '';
    $arm_item_name = isset($item_data['arm_item_name']) ? $item_data['arm_item_name'] : '';
    $arm_item_file = isset($item_data['file_urls']) ? $item_data['file_urls'] : '';
    $arm_item_description = isset($item_data['arm_item_description']) ? $item_data['arm_item_description'] : '';
    $arm_item_url = isset($item_data['arm_item_url']) ? $item_data['arm_item_url'] : '';
    $arm_open_new_tab = isset($item_data['arm_open_new_tab']) ? $item_data['arm_open_new_tab'] : 0;
    $arm_status = isset($item_data['arm_status']) ? $item_data['arm_status'] : 0;
    
}
if( isset($_GET['action']) && $_GET['action'] == 'edit_item' && !empty($_GET['id'])) {
    $item_id = abs($_GET['id']);
    $form_mode = __('Update Banner', 'ARM_AFFILIATE');
    $action = 'update_item';
    $arm_item_data = $arm_aff_banner->arm_aff_item_data( $item_id );
    
    $arm_item_name = !empty($arm_item_data['arm_title']) ? $arm_item_data['arm_title'] : '';
    $arm_item_description = !empty($arm_item_data['arm_description']) ? $arm_item_data['arm_description'] : '';
    $arm_item_url = !empty($arm_item_data['arm_link']) ? $arm_item_data['arm_link'] : '';
    $arm_item_file = isset($arm_item_data['arm_image']) ? $arm_item_data['arm_image'] : '';   
    $arm_open_new_tab = isset($arm_item_data['arm_open_new_tab']) ? $arm_item_data['arm_open_new_tab'] : 0;
    $arm_status = isset($arm_item_data['arm_status']) ? $arm_item_data['arm_status'] : 0;
    $arm_no_file = 1;
    $arm_item_shortcode = "[arm_aff_banner item_id='".$item_id."']";
    
    $arm_item_file_content.= '<div class="arm_dd_item"><img src="'.ARM_AFF_OUTPUT_URL.$arm_item_file.'" height="100" />';
    $arm_item_file_content.= '<input type="hidden" name="file_urls" id="file_url" value="'.$arm_item_file.'" /></div>';
    
}

?>
<div class="wrap arm_page arm_add_item_page armPageContainer">
    <div class="content_wrapper" id="content_wrapper">
        <div class="page_title">
            <?php echo $form_mode; ?>
            <?php if($arm_item_shortcode != ''): ?>
                <div class="arm_add_new_item_box">
                    <span class="arm_dd_short_code">Short Code</span>

                    <div class="arm_shortcode_text arm_form_shortcode_box">
                            <span class="armCopyText"><?php echo $arm_item_shortcode; ?></span>
                            <span class="arm_click_to_copy_text" data-code="<?php echo $arm_item_shortcode; ?>">Click to copy</span>
                            <span class="arm_copied_text"><img src="<?php echo ARM_AFFILIATE_IMAGES_URL; ?>/copied_ok.png" alt="ok">Code Copied</span>
                    </div>
                </div>
            <?php endif; ?>
            <div class="armclear"></div>
        </div>
        <div class="armclear"></div>
        <?php 
        if(isset($_REQUEST['error']) && $_REQUEST['error'] != '') {
                        echo '<div class="arm_message arm_error_message" style="display:block;">';
                        echo '<div class="arm_message_text">this is test item</div>';
                        echo '</div>';
        }
        ?>
        <div class="armclear"></div>
        <div class="arm_add_edit_item_wrapper arm_item_detail_box">
            <form method="post" id="arm_add_edit_item_form" class="arm_add_edit_item_form arm_admin_form" enctype="multipart/form-data">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>" />
                <input type="hidden" name="action" value="<?php echo $action ?>">
                <div class="arm_admin_form_content">
                    <table class="form-table">
                        
                        <tr class="form-field form-required">
                            <th>
                                <label for="arm_item_name">
                                    <?php _e('Title','ARM_AFFILIATE'); ?><span class="required_icon">*</span>
                                </label>
                            </th>
                            <td>
                                <input id="arm_item_name" class="arm_item_name" type="text" name="arm_item_name" value="<?php echo $arm_item_name;?>" data-msg-required="<?php _e('Title can not be left blank.', 'ARM_AFFILIATE');?>" required />
                            </td>
                        </tr>
                        
                        <tr class="form-field form-required">
                            <th>
                                <label for="arm_item_description">
                                    <?php _e('Description','ARM_AFFILIATE');?>
                                </label>
                            </th>
                            <td>
                                <textarea id="arm_item_description" class="arm_item_description" name="arm_item_description" style="height:150px" ><?php echo $arm_item_description; ?></textarea>
                            </td>
                        </tr>
                        
                        
                        <tr class="form-field download_file">
                            <th>
                                <label><?php _e('Banner', 'ARM_AFFILIATE');?><span class="required_icon">*</span></label>
                           </th>
                            <td>
                                <div class="arm_form_fields_wrapper">
                                    <div class="arm_form_input_container_avatar arm_form_input_container" id="">
                                        <div class="armFileUploadWrapper file-field input-field" data-iframe="arm_avatar_0avatar">
                                            <div class="armFileUploadContainer" style="">
                                                <div class="armFileUpload-icon"></div>Upload
                                                <input id="arm_avatar_0avatar" class="arm_item_FileUpload arm_form_input_box " name="arm_item_file" value="" data-file_size="2" data-avatar-type="profile" data-update-meta="no" type="file" accept="<?php echo $except_files; ?>">
                                            </div>
                                            <input class="arm_file_no_url" id="arm_file_no_url" name="arm_file_no_url" value="<?php echo $arm_no_file; ?>" data-msg-required="<?php _e('Please select Banner file.', 'ARM_AFFILIATE');?>" type="hidden"  />
                                        </div>
                                    </div>
                                    <div class="armclear"></div>
                                    <?php //echo $arm_member_forms->arm_member_form_get_fields_by_type($avatarOptions, $avatar_field_id, $arm_form_id, 'active', $armform);?>
                                </div>
                                <div id="error_msg"></div>
                                <div id="file_urls" class="files_name"><?php echo $arm_item_file_content; ?></div>
                                <div id="arm_dd_file_require_error" style="display:none; color:#FF0000"><?php _e('Please select banner file.', 'ARM_AFFILIATE'); ?></div>
                            </td>
                        </tr>
                        
                        <tr class="form-field download_url" >
                            <th>
                                <label><?php _e('Link', 'ARM_AFFILIATE');?></label>
                           </th>
                            <td>
                                <div class="arm_form_fields_wrapper">
                                    <input id="arm_item_url" class="arm_item_url" name="arm_item_url" type="text" value="<?php echo $arm_item_url; ?>" data-msg-required="<?php _e('Link can not be left blank.', 'ARM_AFFILIATE');?>" />
                                    <span class="arm_info_text" style="margin: 10px 0 0; display:block;"><?php _e('Please insert link with http or https.', 'ARM_AFFILIATE'); ?></span>
                                </div>
                            </td>
                        </tr>
                        
                        <tr class="form-field download_url" >
                            <th>
                                <label><?php _e('Open Link In New Tab', 'ARM_AFFILIATE');?></label>
                           </th>
                            <td>
                                <div class="arm_form_fields_wrapper">
                                    <input id="arm_open_new_tab" class="arm_open_new_tab" name="arm_open_new_tab" type="checkbox" value="1" <?php if($arm_open_new_tab == '1') { echo 'checked="checked"'; } ?> />
                                </div>
                            </td>
                        </tr>
                        
                        <tr class="form-field download_url" >
                            <th>
                                <label><?php _e('Status', 'ARM_AFFILIATE');?></label>
                           </th>
                            <td>
                                <div class="arm_form_fields_wrapper">
                                    
                                    <input id="arm_status_active" class="arm_general_input arm_iradio" type="radio" value="1" name="arm_status" <?php checked($arm_status, '1'); ?> />
                                    <label for="arm_status_active"><?php _e('Active', 'ARM_AFFILIATE'); ?></label>
                                    
                                    <input id="arm_status_inactive" class="arm_general_input arm_iradio" type="radio" value="0" name="arm_status" <?php checked($arm_status, '0'); ?> />
                                    <label for="arm_status_inactive"><?php _e('Inactive', 'ARM_AFFILIATE'); ?></label>
                                    
                                </div>
                            </td>
                        </tr>
                        
                    </table>
                    <div class="arm_submit_btn_container">
                        <button class="arm_save_btn arm_dd_item_save_btn" type="submit"><?php _e('Save', 'ARM_AFFILIATE');?></button>
                        <a class="arm_cancel_btn" href="<?php echo $cancel_url;?>"><?php _e('Close', 'ARM_AFFILIATE') ?></a>
                    </div>
                    <div class="armclear"></div>
                </div>
            </form>
            <div class="armclear"></div>
        </div>
    </div>
</div>
<?php $arm_affiliate_settings->arm_affiliate_get_footer(); ?>