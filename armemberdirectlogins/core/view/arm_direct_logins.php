<?php global $wpdb, $arm_member_direct_logins, $arm_members_class, $arm_global_settings; 
$user_roles = $arm_member_direct_logins->arm_direct_logins_get_all_roles();
$all_members = $arm_member_direct_logins->arm_direct_logins_get_all_members(0,0);
?>

<div class="wrap arm_page arm_manage_members_main_wrapper">
    <div class="content_wrapper" id="content_wrapper">
        <div class="page_title">
            <?php _e('Manage Direct Logins', 'ARM_DIRECT_LOGINS');?>
            <div class="arm_add_new_item_box">
                <a class="greensavebtn arm_add_direct_login" href="javascript:void(0);">
                    <img align="absmiddle" src="<?php echo ARM_DIRECT_LOGINS_IMAGES_URL ?>add_new_icon.png" />
                    <span><?php _e('Add Direct Login', 'ARM_DIRECT_LOGINS') ?></span>
                </a>
            </div>
            <div class="armclear"></div>
        </div>
        <div class="armclear"></div>
        <div class="arm_members_grid_container" id="arm_members_grid_container">
            <?php 
            if (file_exists(ARM_DIRECT_LOGINS_VIEW_DIR . '/arm_direct_logins_list_records.php')) {
                    include( ARM_DIRECT_LOGINS_VIEW_DIR.'/arm_direct_logins_list_records.php');
            }
            ?>
        </div>
    </div>
</div>
<?php $arm_member_direct_logins->arm_direct_logins_footer(); ?>

<?php
    global $arm_version;
    if(version_compare($arm_version, '4.0.1', '<'))
    {
?>
        <style type="text/css" title="currentStyle">
            @import "<?php echo ARM_DIRECT_LOGINS_ARMEMBER_URL; ?>/datatables/media/css/demo_page.css";
            @import "<?php echo ARM_DIRECT_LOGINS_ARMEMBER_URL; ?>/datatables/media/css/demo_table_jui.css";
            @import "<?php echo ARM_DIRECT_LOGINS_ARMEMBER_URL; ?>/datatables/media/css/jquery-ui-1.8.4.custom.css";
        </style>
<?php        
    }
    else
    {
?>
        <style type="text/css">
            .dataTables_scroll { padding-top: 1rem !important; }
        </style>
<?php        
    }
?>

<!--./******************** Add New Direct Logins Form ********************/.-->
<div class="arm_add_new_direct_logins popup_wrapper" style="width: 650px;margin-top: 40px;">
    <form method="post" action="#" id="arm_add_direct_logins_wrapper_frm" class="arm_admin_form arm_add_direct_logins_wrapper_frm <?php echo is_rtl() ? 'arm_page_rtl' : ''; ?>">
        <table cellspacing="0">
            <tr class="popup_wrapper_inner">	
                <td class="add_add_direct_login_close_btn arm_popup_close_btn"></td>
                <td class="popup_header"><?php _e('Add New Direct Login', 'ARM_DIRECT_LOGINS');?></td>
                
                <td class="popup_content_text">
                    <table class="arm_table_label_direct_logins">	
                        <tr>
                            <th><?php _e('Select User Type', 'ARM_DIRECT_LOGINS');?></th>
                            <td class="arm_required_wrapper">
                                <table class="arm_dl_select_user_type"><tr><td>
                                <input id="arm_direct_logins_new_user" class="arm_general_input arm_iradio" type="radio" value="new_user" name="arm_direct_logins_user_type" <?php checked('new_user', 'new_user'); ?> />
                                <label for="arm_direct_logins_new_user"><?php _e('New User', 'ARM_DIRECT_LOGINS'); ?></label>
                                <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('By selecting this option, It will create new user in site with selected user role & also will generate direct login token.', 'ARM_DIRECT_LOGINS'); ?>"></i>
                                </td><td>
                                <input id="arm_direct_logins_exists_user" class="arm_general_input arm_iradio" type="radio" value="exists_user" name="arm_direct_logins_user_type"  />
                                <label for="arm_direct_logins_exists_user"><?php _e('Existing User', 'ARM_DIRECT_LOGINS'); ?></label>
                                <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('By selecting this option, It will generate direct login token for selected user.', 'ARM_DIRECT_LOGINS'); ?>"></i>
                                </td></tr></table>
                            </td>
                        </tr>

                        <tr class="new_user_section">
                            <th><?php _e('Enter Email', 'ARM_DIRECT_LOGINS');?></th>
                            <td class="arm_required_wrapper">
                                <input type="text" id="arm_direct_logins_email" name="arm_direct_logins_email" data-msg-required="<?php _e('Email can not be left blank.', 'ARM_DIRECT_LOGINS');?>" value="" >
                                <span id="arm_direct_logins_email_error" class="arm_error_msg arm_direct_logins_email_error" style="display:none;"><?php _e('Please enter an email address.', 'ARM_DIRECT_LOGINS');?></span> 
                                <span id="arm_direct_logins_email_invalid_error" class="arm_error_msg arm_direct_logins_email_invalid_error" style="display:none;"><?php _e('Please enter valid email.', 'ARM_DIRECT_LOGINS');?></span> 
                                <span id="arm_direct_logins_email_exists_error" class="arm_error_msg arm_direct_logins_email_exists_error" style="display:none;"><?php _e('Email address already exists.', 'ARM_DIRECT_LOGINS');?></span> 
                            </td>
                        </tr>
                        
                        <tr class="new_user_section">
                            <th><?php _e('Select User Role', 'ARM_DIRECT_LOGINS');?></th>
                            <td class="arm_required_wrapper">
                                <input type='hidden' id="arm_direct_logins_role" class="arm_direct_logins_role_change_input" name="arm_direct_logins_role" value="administrator" />
                                <dl class="arm_selectbox arm_direct_logins_form_role" style="margin-right:0px;">
                                    <dt style="width: 210px;">
                                        <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" />
                                        <i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_direct_logins_role">
                                            <?php if (!empty($user_roles)): ?>
                                                <?php foreach ($user_roles as $key => $val): ?>
                                                    <li data-label="<?php echo $val; ?>" data-value="<?php echo $key; ?>" data-type="<?php echo $val; ?>"><?php echo $val; ?></li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </dd>
                                </dl>
                            </td>
                        </tr>
                        
                        <tr class="form-field exists_user_section arm_auto_user_field" id="select_user" style="display:none;">
                            <th class="arm-form-table-label"><?php _e('Enter Username/Email', 'ARM_DIRECT_LOGINS'); ?></th>
                            <td class="arm-form-table-content">
                                <input id="arm_direct_logins_user_id" type="text" name="arm_direct_logins_user_id" value="" placeholder="<?php _e('Search by username or email...', 'ARM_DIRECT_LOGINS');?>" required data-msg-required="<?php _e('Please select user.', 'ARM_DIRECT_LOGINS');?>" autocomplete="off">
                                <div class="arm_direct_logins_users_items arm_required_wrapper" id="arm_direct_logins_users_items" style="display: none;"></div>
                                <?php /*?><input type='hidden' id="arm_direct_logins_user_id" class="arm_direct_logins_user_id_change_input" name="arm_direct_logins_user_id" value="" />
                                <dl class="arm_selectbox arm_direct_logins_form_role" style="margin-right:0px;">
                                    <dt style="width: 210px;">
                                        <span></span><input type="text" style="display:none;" value="Select user" class="arm_autocomplete" />
                                        <i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_direct_logins_user_id">
                                            <li data-label="Select user" data-value="Select user" data-type="Select user">Select user</li>
                                            <?php if (!empty($all_members)): ?>
                                                <?php foreach ($all_members as $user): ?>
                                                    <li data-label="<?php echo $user->user_login;?>" data-value="<?php echo $user->ID;?>" data-type="<?php echo $user->user_login;?>"><?php echo $user->user_login;?></li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </dd>
                                </dl><?php */?>
                                <span id="arm_user_ids_error" class="arm_error_msg arm_user_ids_error" style="display:none;"><?php _e('Please select user.', 'ARM_DIRECT_LOGINS');?></span>         
                            </td>
                        </tr>
                        
                        <tr>
                            <th><?php _e('Direct Login Expiration', 'ARM_DIRECT_LOGINS');?></th>
                            
                            <td class="arm_required_wrapper">
                                <table class="arm_table_label_dl_expiration">
                                    <tr>
                                        <td>
                                            <input id="arm_direct_logins_expire_type_hours" class="arm_general_input arm_iradio" type="radio" value="hours" name="arm_direct_logins_expire_type" <?php checked('hours', 'hours'); ?> />
                                            <label for="arm_direct_logins_expire_type_hours"><?php _e('After Selected Hours', 'ARM_DIRECT_LOGINS'); ?></label>

                                            <input type='hidden' id="arm_direct_logins_hours" class="arm_direct_logins_hours_change_input" name="arm_direct_logins_hours" value="1" />
                                        </td>
                                        <td>
                                            <dl class="arm_selectbox arm_direct_logins_form_hours" style="margin-right:0px;">
                                                <dt style="width: 210px;">
                                                    <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" />
                                                    <i class="armfa armfa-caret-down armfa-lg"></i>
                                                </dt>
                                                <dd>
                                                    <ul data-id="arm_direct_logins_hours">
                                                        <?php for ($hours = 1; $hours < 25; $hours++): ?>
                                                                <li data-label="<?php echo $hours; ?>" data-value="<?php echo $hours; ?>" data-type="<?php echo $hours; ?>"><?php echo $hours; ?></li>
                                                        <?php endfor; ?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <td class="arm_required_wrapper">
                                            <input id="arm_direct_logins_expire_type_days" class="arm_general_input arm_iradio" type="radio" value="days" name="arm_direct_logins_expire_type" />
                                            <label for="arm_direct_logins_expire_type_days"><?php _e('After Selected Days', 'ARM_DIRECT_LOGINS'); ?></label>

                                            <input type='hidden' id="arm_direct_logins_days" class="arm_direct_logins_days_change_input" name="arm_direct_logins_days" value="1" />
                                        </td>
                                        <td>
                                            <dl class="arm_selectbox arm_direct_logins_form_days" style="margin-right:0px;">
                                                <dt style="width: 210px;">
                                                    <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" />
                                                    <i class="armfa armfa-caret-down armfa-lg"></i>
                                                </dt>
                                                <dd>
                                                    <ul data-id="arm_direct_logins_days">
                                                        <?php for ($days = 1; $days < 365; $days++): ?>
                                                                <li data-label="<?php echo $days; ?>" data-value="<?php echo $days; ?>" data-type="<?php echo $days; ?>"><?php echo $days; ?></li>
                                                        <?php endfor; ?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </td>
                                    </tr>                                    
                                </table>
                            </td>
                        </tr>
                    </table>
                    <div class="armclear"></div>
                </td>
                
                <td class="popup_content_btn popup_footer">
                    <div class="popup_content_btn_wrapper">
                        <img src="<?php echo ARM_DIRECT_LOGINS_IMAGES_URL.'/arm_loader.gif' ?>" id="arm_loader_img" class="arm_loader_img" style="position: relative;top: 15px;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left';?>;" width="20" height="20" />
                        <button class="arm_save_btn arm_add_direct_logins_submit" type="submit" data-type="add"><?php _e('Save', 'ARM_DIRECT_LOGINS') ?></button>
                        <button class="arm_cancel_btn add_add_direct_login_close_btn" type="button"><?php _e('Cancel','ARM_DIRECT_LOGINS');?></button>
                        <?php wp_nonce_field( 'arm_wp_nonce' );?>
                    </div>
                </td>
            </tr>
        </table>
        <div class="armclear"></div>
    </form>
</div>
<!--./******************** Add New Direct Logins Form ********************/.-->

<!--./******************** Edit Direct Logins Form ********************/.-->
<div class="arm_edit_direct_logins popup_wrapper" style="width: 650px;margin-top: 40px;">
    <form method="post" action="#" id="arm_edit_direct_logins_wrapper_frm" class="arm_admin_form arm_edit_direct_logins_wrapper_frm <?php echo is_rtl() ? 'arm_page_rtl' : ''; ?>">
        <table cellspacing="0">
            <tr class="popup_wrapper_inner">	
                <td class="arm_edit_direct_login_close_btn arm_popup_close_btn"></td>
                <td class="popup_header"><?php _e('Edit Direct Login', 'ARM_DIRECT_LOGINS');?></td>
                
                <td class="popup_content_text">
                    <table class="arm_table_label_direct_logins">
                        <input type="hidden" id="arm_direct_logins_edit_user_type" name="arm_direct_logins_user_type" value="exists_user" />
                        <input type="hidden" id="arm_direct_logins_edit_user_id" name="arm_direct_logins_user_id" value="" />
                        
                        <tr class="form-field edit_exists_user_section" id="select_user">
                            <th class="arm-form-table-label"><?php _e('Username', 'ARM_DIRECT_LOGINS'); ?></th>
                            <td class="arm-form-table-content">
                                <span class="arm_dl_set_username" style="margin-left: 7px;"></span>
                            </td>
                        </tr>
                        <tr><th></th><td></td></tr>
                        <tr>
                            <th><?php _e('Direct Login Expiration', 'ARM_DIRECT_LOGINS');?></th>
                            
                            <td class="arm_required_wrapper">
                                <table class="arm_table_label_dl_expiration">
                                    <tr>
                                        <td>
                                            <input id="arm_direct_logins_expire_type_hours" class="arm_general_input arm_iradio" type="radio" value="hours" name="arm_direct_logins_expire_type" <?php checked('hours', 'hours'); ?> />
                                            <label for="arm_direct_logins_expire_type_hours"><?php _e('After Selected Hours', 'ARM_DIRECT_LOGINS'); ?></label>

                                            <input type='hidden' id="arm_direct_logins_hours" class="arm_direct_logins_hours_change_input" name="arm_direct_logins_hours" value="1" />
                                        </td>
                                        <td>
                                            <dl class="arm_selectbox arm_direct_logins_form_hours" style="margin-right:0px;">
                                                <dt style="width: 210px;">
                                                    <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" />
                                                    <i class="armfa armfa-caret-down armfa-lg"></i>
                                                </dt>
                                                <dd>
                                                    <ul data-id="arm_direct_logins_hours">
                                                        <?php for ($hours = 1; $hours < 25; $hours++): ?>
                                                                <li data-label="<?php echo $hours; ?>" data-value="<?php echo $hours; ?>" data-type="<?php echo $hours; ?>"><?php echo $hours; ?></li>
                                                        <?php endfor; ?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <td class="arm_required_wrapper">
                                            <input id="arm_direct_logins_expire_type_days" class="arm_general_input arm_iradio" type="radio" value="days" name="arm_direct_logins_expire_type" />
                                            <label for="arm_direct_logins_expire_type_days"><?php _e('After Selected Days', 'ARM_DIRECT_LOGINS'); ?></label>

                                            <input type='hidden' id="arm_direct_logins_days" class="arm_direct_logins_days_change_input" name="arm_direct_logins_days" value="1" />
                                        </td>
                                        <td>
                                            <dl class="arm_selectbox arm_direct_logins_form_days" style="margin-right:0px;">
                                                <dt style="width: 210px;">
                                                    <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete" />
                                                    <i class="armfa armfa-caret-down armfa-lg"></i>
                                                </dt>
                                                <dd>
                                                    <ul data-id="arm_direct_logins_days">
                                                        <?php for ($days = 1; $days < 365; $days++): ?>
                                                                <li data-label="<?php echo $days; ?>" data-value="<?php echo $days; ?>" data-type="<?php echo $days; ?>"><?php echo $days; ?></li>
                                                        <?php endfor; ?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </td>
                                    </tr>                                    
                                </table>
                            </td>
                        </tr>
                    </table>
                    <div class="armclear"></div>
                </td>
                
                <td class="popup_content_btn popup_footer">
                    <div class="popup_content_btn_wrapper">
                        <img src="<?php echo ARM_DIRECT_LOGINS_IMAGES_URL.'/arm_loader.gif' ?>" id="arm_loader_img" class="arm_loader_img" style="position: relative;top: 15px;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left';?>;" width="20" height="20" />
                        <button class="arm_save_btn arm_edit_direct_logins_submit" type="submit" data-type="add"><?php _e('Save', 'ARM_DIRECT_LOGINS') ?></button>
                        <button class="arm_cancel_btn arm_edit_direct_login_close_btn" type="button"><?php _e('Cancel','ARM_DIRECT_LOGINS');?></button>
                        <?php wp_nonce_field( 'arm_wp_nonce' );?>
                    </div>
                </td>
            </tr>
        </table>
        <div class="armclear"></div>
    </form>
</div>
<!--./******************** Edit Direct Logins Form ********************/.-->


<div class="arm_badge_details_popup_container" style="display:none;"></div>