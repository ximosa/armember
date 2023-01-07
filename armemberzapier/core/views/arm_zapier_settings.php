<?php
    global $arm_zapier_settings,$arm_subscription_plans;
    
    $zapier_options = $arm_zapier_settings->arm_zapier_get_settings();
    $arm_zapier_field_list = $arm_zapier_settings->arm_zapier_field_list();
    
    /*
    $arm_zapier_default_webhook_handler = $arm_zapier_settings->arm_zapier_webhook_handler_url();
    $arm_zapier_default_api_key = $arm_zapier_settings->arm_zapier_api_key();
    $arm_zapier_webhook_handler = isset( $zapier_options['arm_zapier_webhook_handler'] ) ? $zapier_options['arm_zapier_webhook_handler'] : $arm_zapier_default_webhook_handler;
    $arm_zapier_api_key = isset($zapier_options['arm_zapier_api_key']) ? $zapier_options['arm_zapier_api_key'] : $arm_zapier_default_api_key ;
    */
    
    $arm_zapier_selected_field_list = isset( $zapier_options['arm_zapier_fields'] ) ? $zapier_options['arm_zapier_fields'] : array();
    
    $arm_zapier_user_register = isset( $zapier_options['arm_zapier_user_register'] ) ? $zapier_options['arm_zapier_user_register'] : '0' ;
    $arm_zapier_user_register_webhook_url = isset( $zapier_options['arm_zapier_user_register_webhook_url'] ) ? $zapier_options['arm_zapier_user_register_webhook_url'] : '' ;
    $arm_zapier_user_register_webhook_url_display = ( $arm_zapier_user_register == '0' ) ? 'readonly="readonly"' : '';

    $arm_zapier_update_profile = isset( $zapier_options['arm_zapier_update_profile'] ) ? $zapier_options['arm_zapier_update_profile'] : '0' ;
    $arm_zapier_user_profile_webhook_url = isset( $zapier_options['arm_zapier_user_profile_webhook_url'] ) ? $zapier_options['arm_zapier_user_profile_webhook_url'] : '' ;
    $arm_zapier_user_profile_webhook_url_display = ( $arm_zapier_update_profile == '0' ) ? 'readonly="readonly"' : '';
    
    $arm_zapier_user_renew_plan = isset( $zapier_options['arm_zapier_user_renew_plan'] ) ? $zapier_options['arm_zapier_user_renew_plan'] : '0' ;
    $arm_zapier_user_renew_plan_webhook_url = isset( $zapier_options['arm_zapier_user_renew_plan_webhook_url'] ) ? $zapier_options['arm_zapier_user_renew_plan_webhook_url'] : '' ;
    $arm_zapier_user_renew_plan_webhook_url_display = ( $arm_zapier_user_renew_plan == '0' ) ? 'readonly="readonly"' : '';
    
    $arm_zapier_user_change_plan = isset( $zapier_options['arm_zapier_user_change_plan'] ) ? $zapier_options['arm_zapier_user_change_plan'] : '0' ;
    $arm_zapier_user_change_plan_webhook_url = isset( $zapier_options['arm_zapier_user_change_plan_webhook_url'] ) ? $zapier_options['arm_zapier_user_change_plan_webhook_url'] : '' ;
    $arm_zapier_user_change_plan_webhook_url_display = ( $arm_zapier_user_change_plan == '0' ) ? 'readonly="readonly"' : '';
    
    $arm_zapier_user_delete = isset( $zapier_options['arm_zapier_user_delete'] ) ? $zapier_options['arm_zapier_user_delete'] : '0' ;
    $arm_zapier_user_delete_webhook_url = isset( $zapier_options['arm_zapier_user_delete_webhook_url'] ) ? $zapier_options['arm_zapier_user_delete_webhook_url'] : '' ;
    $arm_zapier_user_delete_webhook_url_display = ( $arm_zapier_user_delete == '0' ) ? 'readonly="readonly"' : '';

    $arm_zapier_user_cancel_plan = isset( $zapier_options['arm_zapier_user_cancel_plan'] ) ? $zapier_options['arm_zapier_user_cancel_plan'] : '0' ;
    $arm_zapier_user_cancel_plan_webhook_url = isset( $zapier_options['arm_zapier_user_cancel_plan_webhook_url'] ) ? $zapier_options['arm_zapier_user_cancel_plan_webhook_url'] : '' ;
    $arm_zapier_user_cancel_plan_webhook_url_display = ( $arm_zapier_user_cancel_plan == '0' ) ? 'readonly="readonly"' : '';

    $arm_zapier_user_register_zap = isset( $zapier_options['arm_zapier_user_register_zap'] ) ? $zapier_options['arm_zapier_user_register_zap'] : '0' ;

    $arm_zapier_user_register_zap_action_display = ( $arm_zapier_user_register_zap == '0' ) ? 'readonly="readonly"' : '';
    $arm_zapier_action = isset($zapier_options['arm_zapier_action']) ? $zapier_options['arm_zapier_action']: "success";
    $arm_zapier_user_register_zap_custom_field = isset($zapier_options['arm_zapier_custom_field']) ? $zapier_options['arm_zapier_custom_field']: array();
    $arm_zapier_user_register_zap_custom_field_display = ( $arm_zapier_user_register_zap == '0' ) ? 'readonly="readonly"' : '';
    $arm_zapier_user_plan_display = ( $arm_zapier_user_register_zap == '0' ) ? 'disabled="disabled"' : '';
    $arm_zapier_user_plan = isset($zapier_options['arm_zapier_user_plan']) ? $zapier_options['arm_zapier_user_plan']: "";
?>

<div class="wrap arm_page arm_zapier_settings_main_wrapper">
    <div class="content_wrapper arm_zapier_settings_content" id="content_wrapper">
        <form method="post" action="#" id="arm_zapier_settings" name="arm_zapier_settings" class="arm_zapier_settings arm_admin_form" onsubmit="return false;">
            <div class="page_title"><?php _e('Zapier Settings For Triggers','ARM_ZAPIER');?></div>
            <?php /*
            <div class="arm_zapier_settings_wrapper">
                <div class="arm_solid_divider"></div>
                <table class="form-table">
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Webhook Handler', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_zapier_webhook_handler" type="text" name="arm_zapier_webhook_handler" readonly="readonly" value="<?php echo $arm_zapier_webhook_handler; ?>" >     
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('API Key', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_zapier_api_key" type="text" name="arm_zapier_api_key" readonly="readonly" value="<?php echo $arm_zapier_api_key ?>" />
                        </td>
                    </tr>
                </table>
            </div>
            */ ?>
            <div class="arm_zapier_settings_wrapper">
                <div class="arm_solid_divider"></div>
                <div class="page_sub_title"><?php _e('Field Setting','ARM_ZAPIER');?></div>
                <table class="form-table">
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Select fields to send in Zapier Trigger', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <select name="arm_zapier_fields[]" class="arm_chosen_selectbox" multiple tabindex="-1">
                                <?php
                                if (!empty($arm_zapier_field_list)) {
                                    foreach ($arm_zapier_field_list as $field_key => $field_val) {
                                        ?><option value="<?php echo $field_key; ?>" <?php echo ( in_array($field_key, $arm_zapier_selected_field_list ) ) ? 'selected="selected"' : ''; ?>><?php echo stripslashes( $field_val ); ?></option><?php
                                    }
                                }
                                ?>
                            </select>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('If you will not select any field(s) here, Than it will send all those field\'s value which you have created in your registration (signup) forms as well as user\'s plan details like, Plan Id & Plan Name.', 'ARM_ZAPIER'); ?>"></i> 
                            <br/>
                            <span class="arm_info_text"><?php _e('Select field(s) that you want to send while any trigger called to zapier. Leave blank to send all fields.', 'ARM_ZAPIER'); ?></span>
                        </td>
                    </tr>
                </table>
                <div class="arm_solid_divider"></div>
                <div class="page_sub_title"><?php _e('Trigger Settings','ARM_ZAPIER');?></div>
                <table class="form-table">
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On User Registration', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_zapier_user_register" <?php checked( $arm_zapier_user_register , '1');?> value="1" class="armswitch_input arm_zapier_switch" name="arm_zapier_user_register" data-user_action = 'register' />
                                <label for="arm_zapier_user_register" class="armswitch_label"></label>
                            </div>
                            <br/><br/>
                            <span class="arm_info_text"><?php _e('Send data to zapier when any new user register / signup with your site.', 'ARM_ZAPIER'); ?></span>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Webhook URL', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_zapier_user_register_webhook_url" type="text" name="arm_zapier_user_register_webhook_url" value="<?php echo $arm_zapier_user_register_webhook_url; ?>" class="arm_zapier_active_register" <?php echo $arm_zapier_user_register_webhook_url_display; ?> />
                            <span id="arm_zapier_user_register_webhook_url_error" class="arm_error_msg arm_zapier_user_register_webhook_url_error" style="display:none;"><?php _e('Please enter user register webhook url.', 'ARM_ZAPIER');?></span>
                            <span id="arm_zapier_user_register_webhook_url_error_invalid" class="arm_error_msg arm_zapier_user_register_webhook_url_error_invalid" style="display:none;"><?php _e('Please enter valid user register webhook url.', 'ARM_ZAPIER');?></span>
                        </td>
                    </tr>
                    <tr> <td align="center" colspan="3"> <div class="arm_solid_divider"></div> </td> </tr>
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On User Update Profile', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_zapier_update_profile" <?php checked( $arm_zapier_update_profile , '1');?> value="1" class="armswitch_input arm_zapier_switch" name="arm_zapier_update_profile" data-user_action = 'profile' />
                                <label for="arm_zapier_update_profile" class="armswitch_label"></label>
                            </div>
                            <br/><br/>
                            <span class="arm_info_text"><?php _e('Send data to zapier when any user update profile in your site.', 'ARM_ZAPIER'); ?></span>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Webhook URL', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_zapier_user_profile_webhook_url" type="text" name="arm_zapier_user_profile_webhook_url" value="<?php echo $arm_zapier_user_profile_webhook_url; ?>" class="arm_zapier_active_profile" <?php echo $arm_zapier_user_profile_webhook_url_display; ?> />
                            <span id="arm_zapier_user_profile_webhook_url_error" class="arm_error_msg arm_zapier_user_profile_webhook_url_error" style="display:none;"><?php _e('Please enter user profile webhook url.', 'ARM_ZAPIER');?></span>
                            <span id="arm_zapier_user_profile_webhook_url_error_invalid" class="arm_error_msg arm_zapier_user_profile_webhook_url_error_invalid" style="display:none;"><?php _e('Please enter valid user profile webhook url.', 'ARM_ZAPIER');?></span>
                        </td>
                    </tr>
                    <tr> <td align="center" colspan="3"> <div class="arm_solid_divider"></div> </td> </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On Membership Plan Renewed', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_zapier_user_renew_plan" <?php checked( $arm_zapier_user_renew_plan , '1');?> value="1" class="armswitch_input arm_zapier_switch" name="arm_zapier_user_renew_plan" data-user_action = 'renew_plan' />
                                <label for="arm_zapier_user_renew_plan" class="armswitch_label"></label>
                            </div>
                            <br/><br/>
                            <span class="arm_info_text"><?php _e('Send data to zapier when any user will renew his membership plan.', 'ARM_ZAPIER'); ?></span>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Webhook URL', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_zapier_user_renew_plan_webhook_url" type="text" name="arm_zapier_user_renew_plan_webhook_url" value="<?php echo $arm_zapier_user_renew_plan_webhook_url; ?>" class="arm_zapier_active_renew_plan" <?php echo $arm_zapier_user_renew_plan_webhook_url_display; ?> />
                            <span id="arm_zapier_user_renew_plan_webhook_url_error" class="arm_error_msg arm_zapier_user_renew_plan_webhook_url_error" style="display:none;"><?php _e('Please enter user renew plan webhook url.', 'ARM_ZAPIER');?></span>
                            <span id="arm_zapier_user_renew_plan_webhook_url_error_invalid" class="arm_error_msg arm_zapier_user_renew_plan_webhook_url_error_invalid" style="display:none;"><?php _e('Please enter valid user renew plan webhook url.', 'ARM_ZAPIER');?></span>
                        </td>
                    </tr>
                    
                    <tr> <td align="center" colspan="3"> <div class="arm_solid_divider"></div> </td> </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On Membership Plan Changed', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_zapier_user_change_plan" <?php checked( $arm_zapier_user_change_plan , '1');?> value="1" class="armswitch_input arm_zapier_switch" name="arm_zapier_user_change_plan" data-user_action = 'change_plan' />
                                <label for="arm_zapier_user_change_plan" class="armswitch_label"></label>
                            </div>
                            <br/><br/>
                            <span class="arm_info_text"><?php _e('Send data to zapier when any user change his existing membership plan to other plan.', 'ARM_ZAPIER'); ?></span>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Webhook URL', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_zapier_user_change_plan_webhook_url" type="text" name="arm_zapier_user_change_plan_webhook_url" value="<?php echo $arm_zapier_user_change_plan_webhook_url; ?>" class="arm_zapier_active_change_plan" <?php echo $arm_zapier_user_change_plan_webhook_url_display; ?> />
                            <span id="arm_zapier_user_change_plan_webhook_url_error" class="arm_error_msg arm_zapier_user_change_plan_webhook_url_error" style="display:none;"><?php _e('Please enter user change plan webhook url.', 'ARM_ZAPIER');?></span>
                            <span id="arm_zapier_user_change_plan_webhook_url_error_invalid" class="arm_error_msg arm_zapier_user_change_plan_webhook_url_error_invalid" style="display:none;"><?php _e('Please enter valid user change plan webhook url.', 'ARM_ZAPIER');?></span>
                        </td>
                    </tr>
                    
                    <tr> <td align="center" colspan="3"> <div class="arm_solid_divider"></div> </td> </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On User Deleted', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_zapier_user_delete" <?php checked( $arm_zapier_user_delete , '1');?> value="1" class="armswitch_input arm_zapier_switch" name="arm_zapier_user_delete" data-user_action = 'delete' />
                                <label for="arm_zapier_user_delete" class="armswitch_label"></label>
                            </div>
                            <br/><br/>
                            <span class="arm_info_text"><?php _e('Send data to zapier when admin will delete any user from your site.', 'ARM_ZAPIER'); ?></span>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Webhook URL', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_zapier_user_delete_webhook_url" type="text" name="arm_zapier_user_delete_webhook_url" value="<?php echo $arm_zapier_user_delete_webhook_url; ?>" class="arm_zapier_active_delete" <?php echo $arm_zapier_user_delete_webhook_url_display; ?> />
                            <span id="arm_zapier_user_delete_webhook_url_error" class="arm_error_msg arm_zapier_user_delete_webhook_url_error" style="display:none;"><?php _e('Please enter user delete webhook url.', 'ARM_ZAPIER');?></span>
                            <span id="arm_zapier_user_delete_webhook_url_error_invalid" class="arm_error_msg arm_zapier_user_delete_webhook_url_error_invalid" style="display:none;"><?php _e('Please enter valid user delete webhook url.', 'ARM_ZAPIER');?></span>
                        </td>
                    </tr>

                    <tr> <td align="center" colspan="3"> <div class="arm_solid_divider"></div> </td> </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On Membership Plan Cancel', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_zapier_user_cancel_plan" <?php checked( $arm_zapier_user_cancel_plan , '1');?> value="1" class="armswitch_input arm_zapier_switch" name="arm_zapier_user_cancel_plan" data-user_action = 'cancel_plan' />
                                <label for="arm_zapier_user_cancel_plan" class="armswitch_label"></label>
                            </div>
                            <br/><br/>
                            <span class="arm_info_text"><?php _e('Send data to zapier when any user cancel his existing membership plan.', 'ARM_ZAPIER'); ?></span>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Webhook URL', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_zapier_user_cancel_plan_webhook_url" type="text" name="arm_zapier_user_cancel_plan_webhook_url" value="<?php echo $arm_zapier_user_cancel_plan_webhook_url; ?>" class="arm_zapier_active_cancel_plan" <?php echo $arm_zapier_user_cancel_plan_webhook_url_display; ?> />
                            <span id="arm_zapier_user_cancel_plan_webhook_url_error" class="arm_error_msg arm_zapier_user_cancel_plan_webhook_url_error" style="display:none;"><?php _e('Please enter user cancel plan webhook url.', 'ARM_ZAPIER');?></span>
                            <span id="arm_zapier_user_cancel_plan_webhook_url_error_invalid" class="arm_error_msg arm_zapier_user_cancel_plan_webhook_url_error_invalid" style="display:none;"><?php _e('Please enter valid user cancel plan webhook url.', 'ARM_ZAPIER');?></span>
                        </td>
                    </tr>

                    <tr> <td align="center" colspan="3"> <div class="arm_solid_divider"></div> </td> </tr>                 

                    <tr>
                        <td colspan="3"><div class="page_title"><?php _e('Zapier Settings For Actions','ARM_ZAPIER');?></div><div class="arm_solid_divider"></div></td>
                    </tr>
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Enable User Register Using Zapier Action', 'ARM_ZAPIER'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_zapier_user_register_zap" <?php checked( $arm_zapier_user_register_zap , '1');?> value="1" class="armswitch_input arm_zapier_switch" name="arm_zapier_user_register_zap" data-user_action = 'zap_register' />
                                <label for="arm_zapier_user_register_zap" class="armswitch_label"></label>
                            </div>
                            
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <span style="display:inline-block; padding-top: 5px;">
                                <?php _e('Zapier Action parameter name and value', 'ARM_ZAPIER'); ?>:
                            </span>
                        </th>
                        <td class="arm-form-table-content">
                            <input type="text" value="armember_zap_action" class="armember_zap_action" readonly="readonly" /> :
                            <input id="arm_zapier_action" type="text" name="arm_zapier_action" value="<?php echo $arm_zapier_action; ?>" class="armember_zap_action arm_zapier_active_zap_register" <?php echo $arm_zapier_user_register_zap_action_display?> />
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('For security purpose, user will be created only if above parameter and entered value will be matched with webhook URL. Use above parameter pass with Zapier webhook action.', 'ARM_ZAPIER'); ?><br><?php _e('Please use exact (case sensitive) parameter name and value in your Zapier webhook action.', 'ARM_ZAPIER'); ?>"></i>
                            <span id="arm_zapier_action_error" class="arm_error_msg arm_zapier_action_error" style="display:none;"><?php _e('Please enter zap action.', 'ARM_ZAPIER');?></span>
                            <span style="display:inline-block; padding-top: 10px;"><?php _e('(Required)', 'ARM_ZAPIER'); ?></span>
                            <table>
                                <tr class="form-field">
                                    <td class="arm-form-table-label arm_zap_parameter_label"><?php _e('Parameter Name', 'ARM_ZAPIER'); ?></th>
                                    <td class="arm-form-table-label arm_zap_parameter_label"><?php _e('Parameter Value', 'ARM_ZAPIER'); ?></th>
                                </tr>
                            </table>
                        </td>
                        
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <span style="display:inline-block; padding-top: 5px;">
                            <?php _e('Assign default membership plan to user upon signup', 'ARM_ZAPIER'); ?>:
                            </span>
                        </th>
                        <td class="arm-form-table-content" >
                        <?php
                        $all_active_plans = $arm_subscription_plans->arm_get_all_active_subscription_plans();
                        $plansLists = '<li data-label="' . addslashes( __('Select Plan', 'ARM_ZAPIER')) . '" data-value="">' . addslashes( __('Select Plan', 'ARM_ZAPIER') ) . '</li>';
                        if (!empty($all_active_plans)) {
                            foreach ($all_active_plans as $arm_plan) {
                                $p_id = $arm_plan['arm_subscription_plan_id'];
                                
                                $plansLists .= '<li data-label="' . esc_attr($arm_plan['arm_subscription_plan_name']) . '" data-value="' . $p_id . '">' . esc_attr($arm_plan['arm_subscription_plan_name']) . '</li>';
                                
                            }
                        }
                        ?>
                            <ul class="arm_user_plan_ul" id="arm_user_plan_ul">
                                <li class="arm_user_plan_li_0" style="margin-bottom: 0px; float: left;">
                                    <div class="arm_user_plns_box">
                                        <input type='hidden' class="arm_user_plan_change_input arm_mm_user_plan_change_input_get_cycle" name="arm_zapier_user_plan" id="arm_zapier_user_plan" value="<?php echo $arm_zapier_user_plan?>" />
                                        <?php $arm_zapier_user_plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($arm_zapier_user_plan);?>
                                        <dl class="arm_selectbox arm_zapier_user_plan column_level_dd arm_member_form_dropdown arm_zapier_active_zap_register" <?php echo $arm_zapier_user_plan_display?>>
                                            <dt><span><?php echo !empty($arm_zapier_user_plan_name) ? $arm_zapier_user_plan_name: 'Select Plan' ?></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                            <dd><ul data-id="arm_zapier_user_plan"><?php echo $plansLists; ?></ul></dd>
                                        </dl>
                                    </div>
                                </li>
                            </ul>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Selected plan from this dropdown will be assigned to member only if \'arm_membership_plan\' parameter (see all possible parameters below) is not passed in your webhook action.', 'ARM_ZAPIER'); ?>"></i>
                            <span style="display:inline-block; padding-top: 10px;"><?php _e('(Optional)', 'ARM_ZAPIER'); ?></span>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <span style="display:inline-block; padding-top: 8px;">
                            <?php _e('Enter parameter name for user custom fields', 'ARM_ZAPIER'); ?> <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('You can add all custom meta keys here for user registration. Please add only those parameters which are not listed below.', 'ARM_ZAPIER'); ?>"></i> :
                            </span>
                        </th>
                        <td class="arm-form-table-content arm_zapier_custom_fields_main">
                        <?php 
                        if(count($arm_zapier_user_register_zap_custom_field)>0 )
                        {
                            $i = 1;
                            foreach($arm_zapier_user_register_zap_custom_field as $arm_zapier_user_register_zap_field_val)
                            {
                        ?>
                        <div class="arm_zapier_custom_fields">
                            <input id="arm_zapier_custom_field_<?php echo $i;?>" type="text" name="arm_zapier_custom_field[]" value="<?php echo $arm_zapier_user_register_zap_field_val; ?>" class="arm_zapier_custom_field arm_zapier_active_zap_register" <?php echo $arm_zapier_user_register_zap_custom_field_display?> />
                            <div class="arm_zapier_helptip_icon">
                                <div class="arm_plan_cycle_plus_icon arm_zapier_plus_icon arm_zapier_custom_field_add_plus_icon arm_helptip_icon tipso_style " title="<?php _e('Add Custom Field', 'ARM_ZAPIER'); ?>"  ></div>

                                <div class="arm_plan_cycle_minus_icon arm_zapier_minus_icon arm_zapier_custom_field_minus_icon arm_helptip_icon tipso_style " title="<?php _e('Remove Custom Field', 'ARM_ZAPIER'); ?>" ></div>
                            </div>
                        </div>
                        <?php
                            $i++;
                            }
                        }
                        else
                        {
                        ?>
                        <div class="arm_zapier_custom_fields">
                            <input id="arm_zapier_custom_field_1" type="text" name="arm_zapier_custom_field[]" value="" class="arm_zapier_custom_field arm_zapier_active_zap_register" <?php echo $arm_zapier_user_register_zap_custom_field_display?> />
                            <div class="arm_zapier_helptip_icon">
                                <div class="arm_plan_cycle_plus_icon arm_zapier_plus_icon arm_zapier_custom_field_add_plus_icon arm_helptip_icon tipso_style " title="<?php _e('Add Custom Field', 'ARM_ZAPIER'); ?>"  ></div>

                                <div class="arm_plan_cycle_minus_icon arm_zapier_minus_icon arm_zapier_custom_field_minus_icon arm_helptip_icon tipso_style " title="<?php _e('Remove Custom Field', 'ARM_ZAPIER'); ?>" ></div>
                            </div>
                        </div>
                        <?php
                        }
                        ?>
                        <input id="arm_zapier_custom_field_counter" type="hidden" name="arm_zapier_custom_field_counter" value="1" <?php echo $arm_zapier_user_register_zap_action_display?> />

                        </td>
                    </tr>
                    <tr class="form-field">
                        <td class="arm-form-table-label">&nbsp;</td>
                        <td class="arm-form-table-content"><span class="arm_info_text"><b>Note:</b> <?php _e('Default parameters listed below can be passed to webhook action directly whithout adding anywhere.', 'ARM_ZAPIER'); ?></span>:
                            <div>
                                <span class="arm_zap_custom_fields"><strong>Field Name</strong></span> 
                                <span class="arm_zap_custom_fields_label"><strong>Parameters</strong></span> 
                            </div>
                            <div>
                                <span class="arm_zap_custom_fields">Email Address</span> 
                                <span class="arm_zap_custom_fields_label"><code>email</code></span> 
                                <span class="arm_zap_custom_fields_optional">(required)</span> 
                            </div>
                            <div>
                                <span class="arm_zap_custom_fields">Username</span> 
                                <span class="arm_zap_custom_fields_label"><code>user_login</code></span> 
                                <span class="arm_zap_custom_fields_optional">(optional)</span> 
                            </div>
                            <div>
                                <span class="arm_zap_custom_fields">First Name</span> 
                                <span class="arm_zap_custom_fields_label"><code>first_name</code></span> 
                                <span class="arm_zap_custom_fields_optional">(optional)</span> 
                            </div>
                            <div>
                                <span class="arm_zap_custom_fields">Last Name</span> 
                                <span class="arm_zap_custom_fields_label"><code>last_name</code></span> 
                                <span class="arm_zap_custom_fields_optional">(optional)</span> 
                            </div>
                            <div>
                                <span class="arm_zap_custom_fields">Plain Password</span> 
                                <span class="arm_zap_custom_fields_label"><code>user_pass</code></span> 
                                <span class="arm_zap_custom_fields_optional">(optional)</span> 
                            </div>
                            <div>
                                <span class="arm_zap_custom_fields">Display Name</span>
                                <span class="arm_zap_custom_fields_label"><code>display_name</code></span> 
                                <span class="arm_zap_custom_fields_optional">(optional)</span>
                            </div>
                            <div>
                                <span class="arm_zap_custom_fields">User Nice Name</span> 
                                <span class="arm_zap_custom_fields_label"><code>user_nicename</code></span>
                                <span class="arm_zap_custom_fields_optional">(optional)</span> 
                            </div>
                            <div>
                                <span class="arm_zap_custom_fields">Website Url</span> 
                                <span class="arm_zap_custom_fields_label"><code>user_url</code></span> 
                                <span class="arm_zap_custom_fields_optional">(optional)</span> 
                            </div>
                            <div>
                                <span class="arm_zap_custom_fields">Assign Membership Plan</span> 
                                <span class="arm_zap_custom_fields_label"><code>arm_member_plan_id</code></span> 
                                <span class="arm_zap_custom_fields_optional">(optional)</span>
                            </div>
                        </td>
                    </tr>
                </table>
                <div class="arm_submit_btn_container">
                    <button id="arm_zapier_settings_btn" class="arm_save_btn" name="arm_settings_btn" type="submit"><?php _e('Save', 'ARM_ZAPIER') ?></button>&nbsp;<img src="<?php echo ARM_ZAPIER_IMAGES_URL.'arm_loader.gif' ?>" id="arm_loader_img" style="position:relative;top:8px;display:none;" width="24" height="24" />
                </div>
                <?php wp_nonce_field( 'arm_wp_nonce' );?>
                <div class="armclear"></div>
            </div>
        </form>
    </div>
</div>
<?php $arm_zapier_settings->arm_zapier_get_footer(); ?>