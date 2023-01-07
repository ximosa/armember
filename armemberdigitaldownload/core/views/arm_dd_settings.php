<?php
    global $arm_dd, $arm_dd_downloads, $arm_subscription_plans, $arm_members_class;
    
    $download_options = $arm_dd->arm_dd_get_settings();
    $limit_day_option = $arm_dd_downloads->arm_dd_download_limit_options();
    $all_members = $arm_members_class->arm_get_all_members(0, 0);

    $count_uniqe_ip = isset($download_options['count_uniqe_ip']) ? $download_options['count_uniqe_ip'] : '0';
    $block_users = isset($download_options['block_users']) ? $download_options['block_users'] : '0';
    $block_plans = isset($download_options['block_plans']) ? $download_options['block_plans'] : '0';
    $block_ip_address = isset($download_options['block_ip_address']) ? $download_options['block_ip_address'] : '';
    $prevent_hotlinking = isset($download_options['prevent_hotlinking']) ? $download_options['prevent_hotlinking'] : '0';
    $open_file_browser = isset($download_options['open_file_browser']) ? $download_options['open_file_browser'] : '0';
    $download_zip = isset($download_options['download_zip']) ? $download_options['download_zip'] : '0';
    $admin_email = isset($download_options['admin_email']) ? $download_options['admin_email'] : '0';
    
    $limit_file = isset($download_options['limit_file']) ? $download_options['limit_file'] : array();
    $limit_day = isset($download_options['limit_day']) ? $download_options['limit_day'] : array();
    $folder_name = isset($download_options['folder_name']) ? $download_options['folder_name'] : 'arm_dd_'.current_time('timestamp');
    $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('', '', false);
    
?>
<div class="wrap arm_page arm_download_settings_main_wrapper">
    <div class="content_wrapper arm_download_settings_content" id="content_wrapper">
        <form method="post" action="#" id="arm_download_settings" name="arm_download_settings" class="arm_download_settings arm_admin_form" onsubmit="return false;">
            <div class="page_title"><?php _e('Download Settings','ARM_DD');?></div>
            <div class="arm_download_settings_wrapper">
                <div class="arm_solid_divider"></div>
                <input type="hidden" name="folder_name" id="folder_name" value="<?php echo $folder_name; ?>" />
                <table class="form-table">

                   

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Count only unique IP address', 'ARM_DD'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('If enabled, then same exact file, by same member will not be counted again in total no of downloads.', 'ARM_DD'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="count_uniqe_ip" <?php checked($count_uniqe_ip, '1');?> value="1" class="armswitch_input" name="count_uniqe_ip"/>
                                <label for="count_uniqe_ip" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Prevent hotlinking', 'ARM_DD'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('If enabled, then download handler will check for PHP referer to see if it originated from your site and if not, redirect them to the homepage.', 'ARM_DD'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="prevent_hotlinking" <?php checked($prevent_hotlinking, '1');?> value="1" class="armswitch_input" name="prevent_hotlinking"/>
                                <label for="prevent_hotlinking" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Enable open file in browser', 'ARM_DD'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('If enabled, then downloaded file will open in browser (htm, html, pdf, jpg, jpeg, jpe, gif, png, mp3, mp4, ogg, webm).', 'ARM_DD'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="open_file_browser" <?php checked($open_file_browser, '1');?> value="1" class="armswitch_input" name="open_file_browser"/>
                                <label for="open_file_browser" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Download multiple files as ZIP', 'ARM_DD'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('If enabled, then mulitple files will be download in ZIP extenstion.', 'ARM_DD'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="download_zip" <?php checked($download_zip, '1');?> value="1" class="armswitch_input" name="download_zip"/>
                                <label for="download_zip" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Email to administrator when user download', 'ARM_DD'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('If enabled, then administator user will get email notification of download detail, when any user download files.', 'ARM_DD'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="admin_email" <?php checked($admin_email, '1');?> value="1" class="armswitch_input" name="admin_email"/>
                                <label for="admin_email" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>


            <div class="arm_solid_divider"></div>
            <div class="page_sub_title"> 
                <?php _e('General Restriction Option','ARM_DD');?> 
            </div>
            <div class="arm_download_settings_wrapper">
                <table class="form-table">
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Block users for download', 'ARM_DD'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Selected users will not able to download the file.', 'ARM_DD'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content arm_multiauto_user_field">
                            <input id="arm_dd_items_block_users_input" type="text" value="" placeholder="<?php _e('Search by username or email...', 'ARM_DD');?>" data-msg-required="<?php _e('Please select user.', 'ARM_DD');?>">
                            <?php 

                                    if(!empty($block_users)){?>
                                        <div class="arm_users_multiauto_items arm_dd_items_user_required_wrapper" id="arm_users_multiauto_items">
                                        <?php foreach ($block_users as $block_users_key => $block_users_value) {

                                                $users = get_userdata( $block_users_value );                                                    
                                            ?>
                                                <div class="arm_users_multiauto_itembox arm_users_multiauto_itembox_<?php echo $block_users_key; ?>">
                                                 <input type="hidden" name="block_users[<?php echo $block_users_key; ?>]" value="<?php echo $block_users_value; ?>">
                                                <label><?php echo $users->user_login; ?><span class="arm_remove_user_multiauto_selected_itembox">x</span></label>
                                                </div>  
                                        <?php }?>
                                        </div>

                                    <?php }
                                 else{?>
                                    <div class="arm_users_multiauto_items arm_dd_items_user_required_wrapper" id="arm_users_multiauto_items" style="display: none;">
                             <?php } 
                            ?>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Plan wise block download', 'ARM_DD'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Selected plan users are not able to download the file.', 'ARM_DD'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <select id="arm_user_ids_select" class="arm_chosen_selectbox arm_user_list" data-msg-required="<?php _e('Please select plans.', 'ARM_DD');?>" name="block_plans[]" data-placeholder="<?php _e('Select Plan(s)..', 'ARM_DD');?>" multiple="multiple" style="width:500px;">
                                <?php if (!empty($all_plans)): ?>
                                    <?php 
                                        $s_plan = '';
                                        if( !empty( $block_plans ) && in_array(-2, $block_plans ) ):
                                            $s_plan = 'selected="selected"';
                                        endif;
                                        ?>
                                    <option class="arm_message_selectbox_op" value="-2" <?php echo $s_plan; ?>>
                                            <?php _e("User having no plan", 'ARM_DD');?>
                                        </option>
                                    <?php foreach ($all_plans as $plan): ?>
                                        <?php 
                                        $s_plan = '';
                                        if( !empty( $block_plans ) && in_array( $plan['arm_subscription_plan_id'], $block_plans ) ):
                                            $s_plan = 'selected="selected"';
                                        endif;
                                        ?>
                                        <option class="arm_message_selectbox_op" value="<?php echo $plan['arm_subscription_plan_id'];?>" <?php echo $s_plan; ?>>
                                            <?php echo $plan['arm_subscription_plan_name'];?>
                                        </option>
                                    <?php endforeach;?>
                                <?php else: ?>
                                    <option value=""><?php _e('No Plan(s) Available', 'ARM_DD');?></option>
                                <?php endif;?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Block IP download', 'ARM_DD'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e("Those IP Address(es) which are entered here, will not be able to download the file. Please note that IP address should exact match. For example, 0.0.0.1 will be banned if and only if IP address will exact match with user's IP address.", 'ARM_DD'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <textarea id="block_ip_address" name="block_ip_address" rows="8" style="width:500px"><?php echo $block_ip_address; ?></textarea>
                        </td>
                    </tr>

                </table>
                <div class="armclear"></div>
            </div>


            <div class="arm_solid_divider"></div>
            <div class="page_sub_title"> 
                <?php _e('Download File Limit','ARM_DD');?> 
                <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Only this many unique downloads will be permitted every X day(s). Repeated downloads of the same exact file are NOT tabulated against the total.', 'ARM_DD'); ?>" ></i>
            </div>
            <div class="arm_download_settings_wrapper">
                <table class="form-table">
                <tr class="form-field">
                       <th style="width: 50%;"><?php _e('Users having <b>no</b> plan can download maximum', 'ARM_DD'); ?>
                            
                           </th>
                        <td class="arm-form-table-content"  style="width: 50%;">
                        
                        
                            <input id="limit_file" class="arm_dd_plan_limit" type="text" name="limit_file[-2]" value="<?php echo isset($limit_file[-2]) ? $limit_file[-2] : ''; ?>" onkeydown="javascript:return checkNumber(event)" style="width:55px !important;" >
                            &nbsp; <?php _e(' no of files per', 'ARM_DD'); ?> &nbsp;
                            <?php $select_limit_day = isset($limit_day[-2]) ? $limit_day[-2] : ''; ?>
                            <input type='hidden' id="limit_day[-2]" name="limit_day[-2]" value="<?php echo $select_limit_day; ?>"/>
                            <dl class="arm_selectbox column_level_dd">
                                <dt style="width:80px;">
                                    <span><?php echo $limit_day_option[$select_limit_day]; ?></span>
                                    <input type="text" style="display:none;" value="" class="arm_autocomplete"/>
                                    <i class="armfa armfa-caret-down armfa-lg"></i>
                                </dt>
                                <dd>
                                    <ul data-id="limit_day[-2]">
                                        <?php foreach ($limit_day_option as $l_key => $l_value) { ?>
                                            <li data-label="<?php echo $l_value; ?>" data-value="<?php echo $l_key; ?>"><?php echo $l_value; ?></li>
                                        <?php } ?>
                                    </ul>
                                </dd>
                            </dl>
                        </td>
                    </tr>
                    <?php foreach($all_plans as $key => $plan) { ?>
                    <tr class="form-field">
                       <th style="width: 50%;"><?php _e('Users having', 'ARM_DD'); ?>
                        <b><?php echo $plan['arm_subscription_plan_name']; ?></b>
                             <?php _e(' plan can download maximum', 'ARM_DD'); ?></th>
                        <td class="arm-form-table-content"  style="width: 50%;">
                        
                        
                            <input id="limit_file" class="arm_dd_plan_limit" type="text" name="limit_file[<?php echo $key; ?>]" value="<?php echo isset($limit_file[$key]) ? $limit_file[$key] : ''; ?>" onkeydown="javascript:return checkNumber(event)" style="width:55px !important;" >
                            &nbsp; <?php _e(' no of files per', 'ARM_DD'); ?> &nbsp;
                            <?php $select_limit_day = isset($limit_day[$key]) ? $limit_day[$key] : ''; ?>
                            <input type='hidden' id="limit_day[<?php echo $key; ?>]" name="limit_day[<?php echo $key; ?>]" value="<?php echo $select_limit_day; ?>"/>
                            <dl class="arm_selectbox column_level_dd">
                                <dt style="width:80px;">
                                    <span><?php echo $limit_day_option[$select_limit_day]; ?></span>
                                    <input type="text" style="display:none;" value="" class="arm_autocomplete"/>
                                    <i class="armfa armfa-caret-down armfa-lg"></i>
                                </dt>
                                <dd>
                                    <ul data-id="limit_day[<?php echo $key; ?>]">
                                        <?php foreach ($limit_day_option as $l_key => $l_value) { ?>
                                            <li data-label="<?php echo $l_value; ?>" data-value="<?php echo $l_key; ?>"><?php echo $l_value; ?></li>
                                        <?php } ?>
                                    </ul>
                                </dd>
                            </dl>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
                <div class="armclear"></div>
            </div>
            
            <div class="arm_download_settings_wrapper">
                <div class="arm_submit_btn_container">
                    <button id="arm_download_settings_btn" class="arm_save_btn" name="arm_download_settings_btn" type="submit"><?php _e('Save', 'ARM_DD') ?></button>&nbsp;<img src="<?php echo ARM_DD_IMAGES_URL . 'arm_loader.gif' ?>" id="arm_loader_img" style="position:relative;top:8px;display:none;" width="24" height="24" />
                </div>
                <?php wp_nonce_field( 'arm_wp_nonce' );?>
                <div class="armclear"></div>
            </div>
        </form>
    </div>
</div>
<?php $arm_dd->arm_dd_get_footer(); ?>