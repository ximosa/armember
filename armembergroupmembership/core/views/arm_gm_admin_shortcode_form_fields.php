<form class="arm_shortcode_other_opts arm_shortcode_other_opts_arm_group_child_member_list arm_hidden" onsubmit="return false;">


    <div class="arm_group_body">
        <table class="arm_shortcode_option_table">
            <tr style="font-size: 18px; padding: 5px;">
                <th colspan="2" style="text-align: left;"><?php _e('Group Membership Listing Fields', 'ARMGroupMembership'); ?></th>
            </tr>
            <tr>
                <th><label><?php _e('Username Label', 'ARMGroupMembership'); ?></label></th>
                <td><input type="text" class="arm_gm_member_group_membership_fields" name="arm_gm_membership_field_username" value="<?php _e('Username','ARMGroupMembership'); ?>" /></td>
            </tr>
            <tr>
                <th><label><?php _e('Email Label', 'ARMGroupMembership'); ?></label></th>
                <td><input type="text" class="arm_gm_member_group_membership_fields" name="arm_gm_membership_field_email" value="<?php _e('Email','ARMGroupMembership'); ?>" /></td>
            </tr>
            <tr>
                <th><label><?php _e('Name Label', 'ARMGroupMembership'); ?></label></th>
                <td><input type="text" class="arm_gm_member_group_membership_fields" name="arm_gm_membership_field_name" value="<?php _e('Name','ARMGroupMembership'); ?>" /></td>
            </tr>

            <tr>
                <th><label><?php _e('Invite Status Label', 'ARMGroupMembership'); ?></label></th>
                <td><input type="text" class="arm_gm_member_group_membership_fields" name="arm_gm_membership_field_status" value="<?php _e('Invite Status','ARMGroupMembership'); ?>" /></td>
            </tr>

            <tr>
                <th><label><?php _e('Action Label', 'ARMGroupMembership'); ?></label></th>
                <td><input type="text" class="arm_gm_member_group_membership_fields" name="arm_gm_membership_field_action" value="<?php _e('Action','ARMGroupMembership'); ?>" /></td>
            </tr>

            <tr>
                <th><?php _e('Display Delete Button?','ARMGroupMembership'); ?></th>
                <td>
                    <label class="delete_button_radio">
                        <input type="radio" name="display_delete_button" value="false" class="arm_iradio arm_gm_display_delete_btn" />
                        <?php _e('No', 'ARMGroupMembership'); ?>
                    </label>
                    <label class="delete_button_radio">
                        <input type="radio" name="display_delete_button" value="true" class="arm_iradio arm_gm_display_delete_btn" checked="checked" />
                        <?php _e('Yes','ARMGroupMembership'); ?>
                    </label>
                </td>
            </tr>
            <tr class="arm_gm_delete_display_options">
                <th><?php _e('Delete Button Text','ARMGroupMembership'); ?></th>
                <td><input type="text" name="delete_button_text" value="<?php _e('Delete','ARMGroupMembership'); ?>" /></td>
            </tr>
            <tr class="arm_gm_delete_display_options">
                <th><?php _e('Button CSS','ARMGroupMembership'); ?></th>
                <td>
                    <textarea class="arm_popup_textarea" name="delete_button_css" rows="3"></textarea>
                    <br/>
                    <em>e.g. color: #ffffff;</em>
                </td>
            </tr>
            <tr class="arm_gm_delete_display_options">
                <th><?php _e('Button Hover CSS','ARMGroupMembership'); ?></th>
                <td>
                    <textarea class="arm_popup_textarea" name="delete_button_hover_css" rows="3"></textarea>
                    <br/>
                    <em>e.g. color: #ffffff;</em>
                </td>
            </tr>
            <tr>
                <th><?php _e('Display Resend Email Button?','ARMGroupMembership'); ?></th>
                <td>
                    <label class="resend_email_button">
                        <input type="radio" name="display_resend_email_button" value="false" class="arm_iradio arm_gm_display_resend_email_btn" />
                        <?php _e('No', 'ARMGroupMembership'); ?>
                    </label>
                    <label class="resend_email_button">
                        <input type="radio" name="display_resend_email_button" value="true" class="arm_iradio arm_gm_display_resend_email_btn" checked="checked" />
                        <?php _e('Yes','ARMGroupMembership'); ?>
                    </label>
                </td>
            </tr>
            <tr class="arm_gm_resend_email_btn_options">
                <th><?php _e('Resend Email Button Text','ARMGroupMembership'); ?></th>
                <td><input type="text" name="resend_email_button_text" value="<?php _e('Resend','ARMGroupMembership'); ?>" /></td>
            </tr>
            <tr class="arm_gm_resend_email_btn_options">
                <th><?php _e('Button CSS','ARMGroupMembership'); ?></th>
                <td>
                    <textarea class="arm_popup_textarea" name="resend_email_button_css" rows="3"></textarea>
                    <br/>
                    <em>e.g. color: #ffffff;</em>
                </td>
            </tr>
            <tr class="arm_gm_resend_email_btn_options">
                <th><?php _e('Button Hover CSS','ARMGroupMembership'); ?></th>
                <td>
                    <textarea class="arm_popup_textarea" name="resend_email_button_hover_css" rows="3"></textarea>
                    <br/>
                    <em>e.g. color: #ffffff;</em>
                </td>
            </tr>
            <tr>
                <th><?php _e('Allow Re-generate Invite Code?','ARMGroupMembership'); ?></th>
                <td>
                    <label class="refresh_invite_code_btn">
                        <input type="radio" name="display_refresh_invite_code_button" value="false" class="arm_iradio arm_gm_refresh_invite_code" />
                        <?php _e('No', 'ARMGroupMembership'); ?>
                    </label>
                    <label class="refresh_invite_code_btn">
                        <input type="radio" name="display_refresh_invite_code_button" value="true" class="arm_iradio arm_gm_refresh_invite_code" checked="checked" />
                        <?php _e('Yes','ARMGroupMembership'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th><?php _e('Records Per Page', 'ARMGroupMembership');?></th>
                <td>
                    <input type="text" class="arm_group_child_member_list_opts" name="per_page" value="5">
                </td>
            </tr>
            <tr>
                <th><?php _e('No Records Message', 'ARMGroupMembership');?></th>
                <td>
                    <input type="text" class="arm_group_child_member_list_opts" name="message_no_record" value="<?php _e('There is no invite code exists.', 'ARMGroupMembership');?>">
                </td>
            </tr>

            <tr style="font-size: 18px; padding: 5px;">
                <th colspan="2" style="text-align: left;"><?php _e('Invite Popup Form Fields', 'ARMGroupMembership'); ?></th>
            </tr>

            <tr>
                <th><?php _e('Select Form', 'ARMGroupMembership');?></th>
                <td>
                    <input type="hidden" id="arm_gm_shortcode_form_id" name="arm_gm_shortcode_form_id" value="" />
                    <dl class="arm_selectbox column_level_dd">
                        <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                        <dd>
                            <ul class="arm_gm_shortcode_form_id_wrapper" data-id="arm_gm_shortcode_form_id">
                                <li data-label="<?php _e('Select Form','ARMGroupMembership');?>" data-value=""><?php _e('Select Form', 'ARMGroupMembership');?></li>
                                <?php if(!empty($arm_gm_forms)): ?>
                                <?php 
                                        foreach($arm_gm_forms as $_arm_gm_form){ 
                                            if ($_arm_gm_form['arm_form_type'] == 'registration') 
                                            {
                                                $formTitle = strip_tags(stripslashes($_arm_gm_form['arm_form_label'])) . ' &nbsp;(ID: ' . $_arm_gm_form['arm_form_id'] . ')';
                                ?>
                                                <li class="arm_shortcode_form_id_li <?php echo $_arm_gm_form['arm_form_type'];?>" data-label="<?php echo $formTitle;?>" data-value="<?php echo $_arm_gm_form['arm_form_id'];?>"><?php echo $formTitle;?></li>
                                <?php 
                                            } 
                                        } 
                                ?>
                                <?php endif;?>
                            </ul>
                        </dd>
                    </dl>
                </td>
            </tr>
            <tr>
                <th><?php _e('Invite User Title', 'ARMGroupMembership');?></th>
                <td>
                    <input type="text" class="arm_group_child_member_list_opts" name="popup_title" value="<?php _e('Invite User', 'ARMGroupMembership'); ?>">
                </td>
            </tr>
            <tr>
                <th><?php _e('Invite User Email Field Label', 'ARMGroupMembership');?></th>
                <td>
                    <input type="text" class="arm_group_child_member_list_opts" name="popup_field_label" value="<?php _e('Invite User Email Address', 'ARMGroupMembership'); ?>">
                </td>
            </tr>
            <tr>
                <th><?php _e('Invite User Button Text', 'ARMGroupMembership');?></th>
                <td>
                    <input type="text" class="arm_group_child_member_list_opts" name="popup_button_text" value="<?php _e('Send Invitation', 'ARMGroupMembership'); ?>">
                </td>
            </tr>
            <tr style="font-size: 18px; padding: 5px;">
                <th colspan="2" style="text-align: left;"><?php _e('Custom CSS', 'ARMGroupMembership'); ?></th>
            </tr>
            <tr>
                <th><?php _e('Custom CSS','ARMGroupMembership'); ?></th>
                <td>
                    <textarea class="arm_popup_textarea" name="custom_css" rows="3"></textarea>
                </td>
            </tr>
            
        </table>
    </div>



</form>