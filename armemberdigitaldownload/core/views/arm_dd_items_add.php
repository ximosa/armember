<?php
global $wpdb, $ARMember, $arm_dd ,$arm_dd_items, $arm_member_forms, $arm_members_class, $arm_global_settings, $arm_subscription_plans;

/**
 * Process Submited Form.
 */

if ( isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'add_item', 'update_item' ) ) ) {

    $arm_dd_items->arm_dd_item_save( $_POST );
}

$user_roles = $arm_global_settings->arm_get_all_roles();
$all_active_plans = $arm_subscription_plans->arm_get_all_active_subscription_plans('', '', true);
//$all_members = $arm_members_class->arm_get_all_members(0, 0);
//$plansLists = '<li data-label="'.__('Select Plan','ARM_DD').'" data-value="">'.__('Select Plan','ARM_DD').'</li>';
//if (!empty($all_active_plans)) {
//	foreach ($all_active_plans as $p) {
//		$p_id = $p['arm_subscription_plan_id'];
//		$plansLists .= '<li data-label="' . stripslashes(esc_attr($p['arm_subscription_plan_name'])) . '" data-value="' . $p_id . '">' . stripslashes(esc_attr($p['arm_subscription_plan_name'])) . '</li>';
//	}
//}
$arm_form_id = 0;
$form_mode = __('Add New Download', 'ARM_DD');
$action = 'add_item';
$cancel_url = admin_url('admin.php?page=arm_dd_item');
//$arm_item_note = $arm_item_summery = 
$item_id = $arm_files = $arm_item_description = $arm_item_name = $arm_item_type = $arm_item_urls = $arm_item_permission_type = $arm_item_permission = $arm_item_shortcode = $arm_item_tag = $user_restriction_type = '';
$arm_item_type = 'default';
$arm_item_msg = '';
$arm_item_permission_type = 'any';
$arm_file_names = '';
if(isset($_POST['action']) && $_POST['action'] == 'add_item') {
    $item_id = '';
    //$arm_item_note = !empty($_POST['arm_item_note']) ? $_POST['arm_item_note'] : '';
    //$arm_item_summery = !empty($_POST['arm_item_summery']) ? $_POST['arm_item_summery'] : '';
    $arm_files = !empty($_POST['arm_files']) ? $_POST['arm_files'] : '';
    $arm_item_type = !empty($_POST['arm_item_type']) ? $_POST['arm_item_type'] : 'default';
    $arm_item_urls = !empty($_POST['arm_item_url']) ? $_POST['arm_item_url'] : '';
    $arm_file_names = !empty($_POST['arm_file_names']) ? $_POST['arm_file_names'] : '';
    $arm_item_permission_type = !empty($_POST['arm_item_permission_type']) ? $_POST['arm_item_permission_type'] : 'any';
    $arm_item_permission = '';
    $arm_item_tag = !empty($_POST['arm_item_tag']) ? $_POST['arm_item_tag'] : '';
   
    $arm_item_description = !empty($_POST['arm_item_description']) ? $_POST['arm_item_description'] : '';
    $arm_item_name = !empty($_POST['arm_item_name']) ? $_POST['arm_item_name'] : '';
}
if( isset($_GET['action']) && $_GET['action'] == 'edit_item' && !empty($_GET['id'])) {
    $item_id = abs($_GET['id']);
    $form_mode = __('Update Download', 'ARM_DD');
    $action = 'update_item';
    $arm_item_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$arm_dd->tbl_arm_dd_items}` WHERE arm_item_id = %d", $item_id), ARRAY_A );
    //$arm_item_note = !empty($arm_item_data['arm_item_note']) ? $arm_item_data['arm_item_note'] : '';
    //$arm_item_summery = !empty($arm_item_data['arm_item_summery']) ? $arm_item_data['arm_item_summery'] : '';
    $arm_files = !empty($arm_item_data['arm_files']) ? $arm_item_data['arm_files'] : '';
    $arm_item_type = !empty($arm_item_data['arm_item_type']) ? $arm_item_data['arm_item_type'] : '';
    $arm_item_urls = !empty($arm_item_data['arm_item_url']) ? $arm_item_data['arm_item_url'] : '';

    $arm_file_names = !empty($arm_item_data['arm_file_names']) ? $arm_item_data['arm_file_names'] : '';
    $arm_item_permission_type = !empty($arm_item_data['arm_item_permission_type']) ? $arm_item_data['arm_item_permission_type'] : 'any';
    $arm_item_permission = !empty($arm_item_data['arm_item_permission']) ? maybe_unserialize($arm_item_data['arm_item_permission']) : array();
    if($arm_item_permission_type == 'user' && !empty($arm_item_permission)){
        $user_restriction_type = isset($arm_item_permission['arm_user_restriction_type']) ? $arm_item_permission['arm_user_restriction_type'] : '';
        unset($arm_item_permission['arm_user_restriction_type']);
    }

    $arm_item_tag = !empty($arm_item_data['arm_item_tag']) ? $arm_item_data['arm_item_tag'] : '';
    $arm_item_msg = !empty($arm_item_data['arm_item_msg']) ? $arm_item_data['arm_item_msg'] : '';
    $arm_item_description = !empty($arm_item_data['arm_item_description']) ? $arm_item_data['arm_item_description'] : '';
    $arm_item_name = !empty($arm_item_data['arm_item_name']) ? $arm_item_data['arm_item_name'] : '';
    $arm_item_shortcode = "[arm_download item_id='".$item_id."']";
    $arm_item_id = isset($arm_item_data['arm_item_id']) ? $arm_item_data['arm_item_id'] : '';

    $arm_dd_settings = $arm_dd->arm_dd_get_settings();
    $arm_download = (isset($arm_dd_settings['download_zip']) && $arm_dd_settings['download_zip'] == 1 ) ? 'zip' : 'item';
    $arm_show_item_urls = isset($arm_item_data['arm_item_url']) ? $arm_dd_items->arm_dd_item_file_urls($arm_item_data['arm_item_url'], $arm_download) : array();
}
$except_files = implode(',', $arm_dd_items->arm_dd_allowed_file_types());
$hidden_file = '';
$hidden_url = '';
$arm_no_file = '';
$arm_item_file_content = '';
$arm_txt_item_urls = '';
$arm_item_file_require = '';
$arm_item_url_require = '';
if($arm_item_type == 'default') {
    $hidden_file = '';
    $hidden_url = 'hidden_row';
    

    $arm_item_url = $arm_dd_items->arm_dd_item_file_urls($arm_item_urls, 'item');
    $arm_file_name = $arm_dd_items->arm_dd_item_file_names($arm_file_names, 'item');

    $arm_file_no_url = 0;
    $i = 0;


    
    if(is_array($arm_item_url) && !empty($arm_item_url)) { 


        
        $file_name = '';
        foreach($arm_item_url as $file_url)
        {
            //$arm_item_file_content.=  '<br/>'.$file_url;
            //$arm_item_file_content.=  '<input type="hidden" name="file_urls[]" id="file_url" value="'.$file_url.'" />';


            $arm_file_no_url++;

            $arm_item_file_name = $arm_dd_items->arm_dd_item_file_name_of_url($file_url); 
            if($arm_item_file_name != '' && file_exists( ARM_DD_OUTPUT_DIR . $arm_item_file_name )){

                $arm_item_file_content.='<tr class="arm_dd_itembox arm_dd_item_'.$arm_file_no_url.'">';
                        $arm_remove_download_title = __("Remove Download Item", "ARM_DD");
                        $arm_item_file_content.='<td>';
                        $exploded_name = explode(".",$arm_item_file_name);
                        $file_name = isset($arm_file_name[$i]) ? $arm_file_name[$i] : '';
                        $file_name = ($file_name=='')? $arm_item_name : $file_name;
                            $arm_item_file_content.= '<input type="text" name="file_names[]" value="'.$file_name.'" class="arm_dd_file_name_input">' ;
                            $arm_item_file_content.= '<input type="hidden" name="file_urls[]" id="file_url" value="'.$file_url.'" />';
                        $arm_item_file_content.='</td>';

                        $arm_item_file_content.='<td class="arm_dd_remove_item_wrapper">';
                                $arm_item_file_content.= '<div class="arm_dd_upload_item_minus_icon arm_helptip_icon tipso_style arm_dd_remove_selected_itembox" title="'.$arm_remove_download_title.'" data_file_name="'.$arm_item_file_name.'" date_item_id="'.$arm_file_no_url.'"></div>';
                        $arm_item_file_content.='</td>';
                $arm_item_file_content.='</tr>';               
            }
            $i++;
        }
        
        $arm_no_file = count($arm_item_url);
    }
    
    $arm_item_file_require = 'required';
} else {
    $hidden_file = 'hidden_row';
    $hidden_url = '';
    $arm_txt_item_urls = $arm_item_urls;
    $arm_item_url_require = 'required';
}

if(empty($user_restriction_type)){
    $user_restriction_type = 'allowed_user';
}
$allowed_user_lbl = __('Allowed User', 'ARM_DD');
$denied_user_lbl = __('Denied User', 'ARM_DD');
if($user_restriction_type == 'allowed_user'){
    $user_restriction_label = $allowed_user_lbl;
} else {
    $user_restriction_label = $denied_user_lbl;
}



?>
<div class="wrap arm_page arm_add_item_page armPageContainer">
    <div class="content_wrapper" id="content_wrapper">
        <div class="page_title">
            <?php echo $form_mode; ?>
            
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
            <form method="post" id="arm_add_edit_item_form" class="arm_add_edit_item_form arm_admin_form" enctype="multipart/form-data" onsubmit="return validate_arm_add_edit_item_form();">
                <input type="hidden" name="item_id" value="<?php echo $item_id; ?>" />
                <input type="hidden" name="action" value="<?php echo $action ?>">
                <div class="arm_admin_form_content" style="position: relative;left: -33px;">
                    <table class="form-table">
                        
                        <tr class="form-field form-required">
                            <th>
                                <label for="arm_item_name">
                                    <?php _e('Name','ARM_DD'); ?><span class="required_icon">*</span>
                                </label>
                            </th>
                            <td>
                                <input id="arm_item_name" class="arm_item_name" type="text" name="arm_item_name" value="<?php echo $arm_item_name;?>" data-msg-required="<?php _e('Name can not be left blank.', 'ARM_DD');?>" required />
                            </td>
                        </tr>
                        
                        <tr class="form-field form-required">
                            <th>
                                <label for="arm_item_description">
                                    <?php _e('Description','ARM_DD');?>
                                </label>
                            </th>
                            <td>
                                <textarea id="arm_item_description" class="arm_item_description" name="arm_item_description" ><?php echo $arm_item_description; ?></textarea>
                            </td>
                        </tr>

                        <tr class="form-field">
                            <th>
                                <label for="arm_item_type">
                                    <?php //_e('Type','ARM_DD');?>
                                </label>
                            </th>
                            <td>

                                <span class="arm_item_type_span" id="arm_item_type_span">
                                    <input type="radio" class="arm_iradio" <?php checked($arm_item_type, 'default'); ?> value="default" name="arm_item_type" id="arm_item_type_default" />
                                    <label for="arm_item_type_default"><?php _e('Upload File', 'ARM_DD'); ?></label>

                                    <input type="radio" class="arm_iradio" <?php checked($arm_item_type, 'external'); ?> value="external" name="arm_item_type" id="arm_item_type_external" />
                                    <label for="arm_item_type_external"><?php _e('Use External URL', 'ARM_DD'); ?></label>
                                </span>

                                <?php /*
                                <input type='hidden' id="arm_item_type" name="arm_item_type" value="<?php echo $arm_item_type;?>"/>
                                <dl class="arm_selectbox column_level_dd">
                                    <dt>
                                        <span></span>
                                        <input type="text" style="display:none;" value="" class="arm_autocomplete"/>
                                        <i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_item_type">
                                            <li data-label="<?php _e('Default','ARM_DD');?>" data-value="default"><?php _e('Default','ARM_DD');?></li>
                                            <li data-label="<?php _e('External','ARM_DD');?>" data-value="external"><?php _e('External','ARM_DD');?></li>
                                        </ul>
                                    </dd>
                                </dl> */ ?>

                            </td>
                        </tr>
                        
                        <?php /* $armform = new ARM_Form();
                        $avatar_field_id = 'avatar_' . arm_generate_random_code();
                        $avatarOptions = array(
                            'id' => 'avatar',
                            'label' => __('Avatar', 'ARM_DD'),
                            'placeholder' => __('Drop file here or click to select.', 'ARM_DD'),
                            'type' => 'avatar',
                            'value' => '',
                            'allow_ext' => '',
                            'file_size_limit' => '2',
                            'meta_key' => 'avatar',
                            'required' => 0,
                            'blank_message' => __('Please select download file.', 'ARM_DD'),
                            'invalid_message' => __('Invalid image selected.', 'ARM_DD'),
                            'onchange' => 'arm_dd_upload_image(this);'
                        ); */ ?>
                        
                        <tr class="form-field download_file <?php echo $hidden_file; ?>">
                            <th>
                                <label><?php _e('Download File', 'ARM_DD');?><span class="required_icon">*</span></label>
                           </th>
                            <td>
                                <div class="arm_form_fields_wrapper">
                                    <div class="arm_form_input_container_avatar arm_form_input_container" id="">
                                        <div class="armFileUploadWrapper file-field input-field" data-iframe="arm_avatar_0avatar">
                                            <div class="armFileUploadContainer" style="">
                                                <div class="armFileUpload-icon"></div>Add File
                                                <input id="arm_avatar_0avatar" class="arm_item_FileUpload arm_form_input_box " name="arm_item_file[]" value="" data-file_size="2" data-avatar-type="profile" data-update-meta="no" type="file" multiple="true" accept="<?php echo $except_files; ?>">
                                            </div>
                                            <input class="arm_file_no_url" id="arm_file_no_url" name="arm_file_no_url" value="<?php echo $arm_no_file; ?>" data-msg-required="<?php _e('Please select Download file.', 'ARM_DD');?>" type="hidden" <?php echo $arm_item_file_require; ?> />
                                            <img src="<?php echo ARM_DD_IMAGES_URL.'arm_loader.gif'; ?>" id="arm_dd_loder">
                                        </div>
                                    </div>
                                    <div class="armclear"></div>
                                    <?php //echo $arm_member_forms->arm_member_form_get_fields_by_type($avatarOptions, $avatar_field_id, $arm_form_id, 'active', $armform);?>
                                </div>
                                <div id="error_msg"></div>
                                <table id="file_urls" cellspacing="0" cellpadding="0"><tbody><?php echo $arm_item_file_content; ?></tbody></table>
                                <div id="arm_dd_file_require_error" style="display:none; color:#FF0000">Please select Download file.</div>
                            </td>
                        </tr>
                        
                        <tr class="form-field download_url <?php echo $hidden_url; ?>" >
                            <th>
                                <label><?php _e('Download URL', 'ARM_DD');?><span class="required_icon">*</span></label>
                           </th>
                            <td>
                                <div class="arm_form_fields_wrapper">
                                    <input id="arm_item_url" class="arm_item_url" name="arm_item_url" type="text" value="<?php echo $arm_txt_item_urls; ?>" data-msg-required="<?php _e('Download URL can not be left blank.', 'ARM_DD');?>" <?php echo $arm_item_url_require; ?> />
                                </div>
                            </td>
                        </tr>
                        
                        <tr class="form-field">
                            <th>
                                <label for="arm_item_permission_type">
                                    <?php _e('Permission Type','ARM_DD');?>
                                </label>
                            </th>
                            <td>
                                <input type='hidden' id="arm_item_permission_type" name="arm_item_permission_type" value="<?php echo $arm_item_permission_type;?>"/>
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
                                    <input type="radio" class="arm_iradio" <?php checked($user_restriction_type, 'allowed_user'); ?> value="allowed_user" name="arm_user_restriction_type" id="arm_dd_allowed_user" data-label="<?php echo $allowed_user_lbl; ?>" />
                                    <label for="arm_dd_allowed_user"><?php echo $allowed_user_lbl; ?></label>

                                    <input type="radio" class="arm_iradio" <?php checked($user_restriction_type, 'denied_user'); ?> value="denied_user" name="arm_user_restriction_type" id="arm_dd_denied_user" data-label="<?php echo $denied_user_lbl; ?>" />
                                    <label for="arm_dd_denied_user"><?php echo $denied_user_lbl; ?></label>
                                </span>
                            </td>
                        </tr>
                        
                        <tr class="form-field permission permission_user">
                            <th>
                                <label for="arm_item_permission" class="arm_permission_user_label">
                                    <?php echo $user_restriction_label; ?>
                                </label>
                            </th>
                            <td class="arm_multiauto_user_field">
                                <input id="arm_dd_items_users_input" type="text" value="" placeholder="<?php esc_html_e('Search by username or email...', 'ARM_DD');?>" data-msg-required="<?php esc_html_e('Please select user.', 'ARM_DD');?>">
                                <?php 


                                    if($arm_item_permission_type == 'user'){
                                        if(!empty($arm_item_permission)){?>
                                            <div class="arm_users_multiauto_items" id="arm_users_multiauto_items">
                                            <?php foreach ($arm_item_permission as  $arm_item_permission_value) {

                                                    $users = get_userdata( $arm_item_permission_value );                                                    
                                                ?>
                                                    <div class="arm_users_multiauto_itembox arm_users_multiauto_itembox_<?php echo $arm_item_permission_value; ?>">
                                                     <input type="hidden" name="arm_user_ids[<?php echo $arm_item_permission_value; ?>]" value="<?php echo $arm_item_permission_value; ?>">
                                                    <label><?php echo $users->user_login." (".$users->user_email.")"; ?><span class="arm_remove_user_multiauto_selected_itembox">x</span></label>
                                                    </div>  
                                            <?php }?>
                                            </div>

                                        <?php }
                                     }else{?>
                                        <div class="arm_users_multiauto_items arm_dd_items_user_required_wrapper" id="arm_users_multiauto_items" style="display: none;">
                                <?php } 
                                ?>
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
                                            $s_plan = '';
                                            if($arm_item_permission_type == 'plan'):
                                                if(!empty($arm_item_permission) && in_array($p_id, $arm_item_permission)):
                                                    $s_plan = 'selected="selected"';
                                                endif;
                                            endif;
                                            ?>
                                            <option class="arm_message_selectbox_op" value="<?php echo $p_id;?>" <?php echo $s_plan; ?>>
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
                                            <?php 
                                            $s_role = '';
                                            if($arm_item_permission_type == 'role'):
                                                if(!empty($arm_item_permission) && in_array($key, $arm_item_permission)):
                                                    $s_role = 'selected="selected"';
                                                endif;
                                            endif;
                                            ?>
                                            <option class="arm_message_selectbox_op" value="<?php echo $key;?>" <?php echo $s_role; ?>>
                                                <?php echo $val;?>
                                            </option>
                                        <?php endforeach;?>
                                    <?php else: ?>
                                        <option value=""><?php _e('No Role(s) Available', 'ARM_DD');?></option>
                                    <?php endif;?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr class="form-field form-required">
                            <th>
                                <label for="arm_item_tag">
                                    <?php _e('Tag','ARM_DD'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="arm_item_tag" class="arm_item_tag" type="text" name="arm_item_tag" value="<?php echo $arm_item_tag;?>" />
                                <br/>
                                <div class="arm_info_text">Add tag Separate with commas.</div>
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th>
                                <label for="arm_item_tag">
                                    <?php _e('Message for restricted users','ARM_DD'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="arm_item_msg" class="arm_item_msg" type="text" name="arm_item_msg" value="<?php echo stripslashes(htmlentities($arm_item_msg));?>" />
                            </td>
                        </tr>
                        <tr class="form-field form-required">
                            <th>
                                <label for="arm_item_tag">
                                    <?php _e('Download count','ARM_DD'); ?>
                                </label>
                            </th>
                            <td>
                                <input id="arm_item_download_count" class="arm_item_download_count" type="text" name="arm_item_download_count" value="" /><span> &nbsp;(<?php _e('optional','ARM_DD'); ?>)</span>
                            </td>
                        </tr>
                        
                        <?php /*
                        <tr class="form-field">
                            <th>
                                <label for="arm_item_summery">
                                    <?php _e('Summery','ARM_DD');?>
                                </label>
                            </th>
                            <td>
                                <textarea id="arm_item_summery" class="arm_item_summery" name="arm_item_summery" type="text" ><?php echo $arm_item_summery; ?></textarea>
                            </td>
                        </tr>
                        
                        <tr class="form-field">
                            <th>
                                <label for="arm_item_note">
                                    <?php _e('Notes','ARM_DD');?>
                                </label>
                            </th>
                            <td>
                                <textarea id="arm_item_note" class="arm_item_note" name="arm_item_note" type="text" ><?php echo $arm_item_note; ?></textarea>
                            </td>
                        </tr>
                        */ ?>

                    </table>
                    <div class="arm_submit_btn_container">
                        <button class="arm_save_btn arm_dd_item_save_btn" type="submit"><?php _e('Save', 'ARM_DD');?></button>
                        <a class="arm_cancel_btn" href="<?php echo $cancel_url;?>"><?php _e('Close', 'ARM_DD') ?></a>
                    </div>
                    <div class="armclear"></div>
                </div>
            </form>
            <?php if($arm_item_shortcode != ''):  ?>
                    <button class="armemailaddbtn arm_dd_generate_shortcode arm_dd_add_generate_shortcode_btn" data-id="<?php echo $arm_item_id; ?>" data-name="<?php echo $arm_item_name; ?>">Generate Shortcode</button>
                    
                    <?php 
                        $dd_generate_shortcode_popup_arg = array(
                            'id' => 'add_dd_shortcode_wrapper',
                            'class' => 'add_dd_shortcode_wrapper',
                            'title' => __('Generate Shortcode', 'ARM_DD'),
                        );
                        echo $arm_dd_items->arm_dd_get_bpopup_html($dd_generate_shortcode_popup_arg);
                     ?>
            <?php endif; ?>
            <div class="armclear"></div>
        </div>
    </div>
</div>