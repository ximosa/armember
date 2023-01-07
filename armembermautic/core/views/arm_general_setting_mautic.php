<?php 

$mautic_url = ARM_MAUTIC_URL . '/auth_mautic.php';

$arm_mautic_base_url =  '';
$arm_mautic_public_key =  '';
$arm_mautic_secret_key =  '';
$arm_mautic_status =  0;
$arm_mautic_list =  array();
$arm_mautic_default_list_id = 0;

$arm_mautic_settings_ser = get_option('arm_mautic_settings');
$arm_mautic_settings = ($arm_mautic_settings_ser != '') ? maybe_unserialize($arm_mautic_settings_ser) : array();
if(!empty($arm_mautic_settings))
{
    $arm_mautic_base_url =  ($arm_mautic_settings['base_url'] != '') ? $arm_mautic_settings['base_url'] : '';
    $arm_mautic_public_key = ($arm_mautic_settings['public_key'] != '') ? $arm_mautic_settings['public_key'] : '';
    $arm_mautic_secret_key =  ($arm_mautic_settings['secret_key'] != '') ? $arm_mautic_settings['secret_key'] : '';
    $arm_mautic_status =  ($arm_mautic_settings['status'] != '') ? $arm_mautic_settings['status'] : 0;
    $arm_mautic_list =  !empty($arm_mautic_settings['lists']) ? $arm_mautic_settings['lists'] : array();
    $arm_mautic_default_list_id = ($arm_mautic_settings['default_list'] != '') ? $arm_mautic_settings['default_list'] : 0;
}

?>
<div class="arm_solid_divider"></div>
<table class="form-table">
        <tr class="form-field">
                <td colspan="2"><img src="<?php echo ARM_MAUTIC_IMAGE_URL; ?>optins_mautic.png" alt="Mautic"></td>
        </tr>
        <input type="hidden" id="mautic_url" value="<?php echo $mautic_url; ?>" />
        <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Base URL', ARM_MAUTIC_TEXTDOMAIN); ?></label></th>
                <td class="arm-form-table-content">
                        <input id="arm_mautic_base_url" type="text" name="arm_mautic_settings[base_url]" value="<?php echo $arm_mautic_base_url;?>" onkeyup="show_mautic_tool_verify_btn('mautic');">
                        <span class="error arm_invalid" id="arm_mautic_base_url_invalid_error" style="display: none;"><?php _e('Please enter valid URL.', ARM_MAUTIC_TEXTDOMAIN);?></span>
                        <span class="error arm_invalid" id="arm_mautic_base_url_error" style="display: none;"><?php _e('This field cannot be blank.', ARM_MAUTIC_TEXTDOMAIN);?></span>
                </td>
        </tr>
        
        
        <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Redirect URI', ARM_MAUTIC_TEXTDOMAIN); ?></label></th>
                <td class="arm-form-table-content">
                    <span class="arm_info_text"><?php echo ARM_MAUTIC_URL.'/auth_mautic.php'; ?></span>
                    <br/>
                    <span style="font-size:13px;"><em><?php _e('Redirect URI that you should add in Mautic API Credential.', ARM_MAUTIC_TEXTDOMAIN); ?></em></span>
                    
                        
                </td>
        </tr>
        
        <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Auth Type', ARM_MAUTIC_TEXTDOMAIN); ?></label></th>
                <td class="arm-form-table-content">
                    <span class="arm_info_text">OAuth2</span>
                    
                </td>
        </tr>
        
        <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Public Key', ARM_MAUTIC_TEXTDOMAIN); ?></label></th>
                <td class="arm-form-table-content">
                        <input id="arm_mautic_public_key" type="text" name="arm_mautic_settings[public_key]" value="<?php echo $arm_mautic_public_key;?>" onkeyup="show_mautic_tool_verify_btn('mautic');">
                        <span class="error arm_invalid" id="arm_mautic_public_key_error" style="display: none;"><?php _e('This field cannot be blank.', ARM_MAUTIC_TEXTDOMAIN);?></span>
                </td>
        </tr>
        <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Secret Key', ARM_MAUTIC_TEXTDOMAIN); ?></label></th>
                <td class="arm-form-table-content">
                        <input id="arm_mautic_secret_key" type="text" name="arm_mautic_settings[secret_key]" value="<?php echo $arm_mautic_secret_key;?>" onkeyup="show_mautic_tool_verify_btn('mautic');">
                         <?php 
                        if($arm_mautic_base_url != '' && $arm_mautic_public_key != '' && $arm_mautic_secret_key != '')
                        {
                        ?>
                        <span id="arm_mautic_link" <?php if ($arm_mautic_status == 1) { ?>style="display:none;"<?php } ?>><a href="javascript:void(0);" onclick="verify_mautic_tool('mautic', '0');"><?php _e('Verify', ARM_MAUTIC_TEXTDOMAIN); ?></a></span>
                        <span id="arm_mautic_varify" style="display:none;" class="arm_success_msg"><?php _e('Verified', ARM_MAUTIC_TEXTDOMAIN); ?></span>
                        <span id="arm_mautic_error" style="display:none;" class="arm_error_msg"><?php _e('Not Verified', ARM_MAUTIC_TEXTDOMAIN); ?></span>
                        <?php
                        }
                        ?>
                        <input type="hidden" name="arm_mautic_settings[status]" id="arm_mautic_status" value="<?php echo $arm_mautic_status; ?>">
                        
                        <span class="error arm_invalid" id="arm_mautic_secret_key_error" style="display: none;"><?php _e('This field cannot be blank.', ARM_MAUTIC_TEXTDOMAIN);?></span>
                </td>
        </tr>
        
        <?php 
        if($arm_mautic_base_url != '' && $arm_mautic_public_key != '' && $arm_mautic_secret_key != '')
        {
            ?>
       
        <tr class="form-field" id="arm_mautic_list_tr">
                <th class="arm-form-table-label"><label><?php _e('List Name', ARM_MAUTIC_TEXTDOMAIN); ?></label></th>
                <td class="arm-form-table-content">
                   
                        <input type="hidden" id="mautic_list_name" name="arm_mautic_settings[list_id]" value="<?php echo $arm_mautic_default_list_id; ?>" />
                        <dl id="arm_mautic_dl" class="arm_selectbox column_level_dd <?php if ($arm_mautic_status == 0) { ?>disabled<?php } ?>">
                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="fa fa-caret-down fa-lg"></i></dt>
                                <dd>
                                        <ul data-id="mautic_list_name" id="arm_mautic_list">
                                                <?php if(!empty($arm_mautic_list)) :?>
                                                        <?php foreach($arm_mautic_list as $list):?>
                                                        <li data-label="<?php echo $list['name'];?>" data-value="<?php echo $list['id'];?>"><?php echo $list['name'];?></li>
                                                        <?php endforeach;?>
                                                <?php endif;?>
                                        </ul>
                                </dd>
                        </dl>
                        <span id="arm_mautic_refresh" class="arm_success_msg" style="display:none;"><?php _e('Refreshed', ARM_MAUTIC_TEXTDOMAIN); ?></span>
                        <div id="arm_mautic_action_link" style="padding-left:5px; margin-top:10px;<?php if ($arm_mautic_status == 0) { ?>display:none;<?php } ?>" class="arlinks">					
                                <span id="arm_mautic_link_refresh"><a href="javascript:void(0);" onclick="refresh_mautic_tool('refresh');"><?php _e('Refresh List', ARM_MAUTIC_TEXTDOMAIN); ?></a></span>
                                &nbsp;	&nbsp;	&nbsp;	&nbsp;
                                <span id="arm_mautic_link_delete"><a href="javascript:void(0);" onclick="refresh_mautic_tool('delete');"><?php _e('Delete Configuration', ARM_MAUTIC_TEXTDOMAIN); ?></a></span>
                        </div>
                </td>
        </tr>
        <?php 
        }
        ?>
</table>