<div class="<?php echo $arm_gm_group_membership_container_class; ?>_loader_img"></div>
<div class="<?php echo $arm_gm_group_membership_container_class; ?>">
<?php
    $frontfontstyle = $arm_global_settings->arm_get_front_font_style();
?>
<link id="group_membership_google_font" rel="stylesheet" type="text/css" href="<?php echo $frontfontstyle['google_font_url']; ?>" />

<style type="text/css">
    <?php
        $arm_gm_css_string = "";
        $arm_gm_css_string .= ".{$arm_gm_group_membership_container_class} .arm_group_membership_list_item .arm_renew_subscription_button{ text-transform: none; ".$frontfontstyle['frontOptions']['button_font']['font']. "}";

        if(!empty($custom_css))
        {
            $arm_gm_css_string .= $arm_shortcodes->arm_br2nl($custom_css);
        }

        $arm_gm_css_string .= ".{$arm_gm_group_membership_container_class} .arm_group_membership_heading_main{ {$frontfontstyle['frontOptions']['level_1_font']['font']} }";

        $arm_gm_css_string .= ".{$arm_gm_group_membership_container_class} .arm_group_membership_list_header th{ {$frontfontstyle['frontOptions']['level_2_font']['font']} }";

        $arm_gm_css_string .= ".{$arm_gm_group_membership_container_class} .arm_group_membership_list_item td{ {$frontfontstyle['frontOptions']['level_3_font']['font']} }";

        $arm_gm_css_string .= ".{$arm_gm_group_membership_container_class} .arm_paging_wrapper .arm_paging_info, .{$arm_gm_group_membership_container_class} .arm_paging_wrapper .arm_paging_links a{ {$frontfontstyle['frontOptions']['level_4_font']['font']} }";
	
	$arm_gm_css_string .= ".arm_gm_child_user_parent_wrapper_container .arm_invite_user_button, .arm_gm_child_user_parent_wrapper_container .arm_gm_resend_email_button, .arm_gm_child_user_parent_wrapper_container .arm_delete_user_button { {$frontfontstyle['frontOptions']['button_font']['font']} }";

        if(!empty($delete_button_css)){
            $arm_gm_css_string .= $delete_button_css;
        }

        if(!empty($delete_button_hover_css)){
            $arm_gm_css_string .= $delete_button_hover_css;
        }

        if(!empty($resend_email_button_css)){
            $arm_gm_css_string .= $resend_email_button_css;
        }

        if(!empty($resend_email_button_hover_css)){
            $arm_gm_css_string .= $resend_email_button_hover_css;
        }

        echo $arm_gm_css_string;
    ?>
</style>


<?php
    $arm_gm_body_content = "";


    // Invite User Modal Code Starts
        $arm_gm_body_content .= '<div id="arm_gm_form_invite_user_shortcode_modal" class="arm_gm_invite_modal arm_msg_pos_'.$validation_pos.'" style="width:550px;">';
        $arm_gm_body_content .= '<div class="popup_wrapper_inner">';

        $arm_gm_body_content .= '<div class="popup_header">';
        $arm_gm_body_content .= '<span class="popup_close_btn arm_popup_close_btn"></span>';
        $arm_gm_body_content .= '<span class="popup_header_text">'.__($popup_title, 'ARMGroupMembership').'</span>';
        $arm_gm_body_content .= '</div>';

        $arm_gm_body_content .= '<div class="popup_content_text">'; // Modal Body Code

        $arm_gm_body_content .= '<input type="hidden" name="arm_gm_coupon_code" id="arm_gm_coupon_code_modal" value="">';
        $arm_gm_body_content .= '<input type="hidden" name="arm_gm_coupon_id" id="arm_gm_coupon_id_modal" value="">';

        $default_form_id = $arm_member_forms->arm_get_default_form_id('registration');
        $form = new ARM_Form('id', $default_form_id);

        if ($form->exists() && !empty($form->fields)) 
        {
            $form_id = $form->ID;
            $form_settings = $form->settings;
            $ref_template = $form->form_detail['arm_ref_template'];
            $form_style = $form_settings['style'];
            $fieldPosition = 'left';
            $errPos = 'right';

            $form_style['button_position'] = (!empty($form_style['button_position'])) ? $form_style['button_position'] : 'left';
            $form_css = $arm_member_forms->arm_ajax_generate_form_styles($form_id, $form_settings, array(), $ref_template);
            $form_style_class = 'arm_shortcode_form arm_form_' . $form_id;
            $form_style_class .= ' arm_form_layout_' . $form_style['form_layout'];
            $form_style_class .= ($form_style['label_hide'] == '1') ? ' armf_label_placeholder' : '';
            $form_style_class .= ' armf_alignment_' . $form_style['label_align'];
            $form_style_class .= ' armf_layout_' . $form_style['label_position'];
            $form_style_class .= ' armf_button_position_' . $form_style['button_position'];
            $form_style_class .= ($form_style['rtl'] == '1') ? ' arm_form_rtl' : ' arm_form_ltr';
        }

        $form_title_position = (!empty($form_style['form_title_position'])) ? $form_style['form_title_position'] : 'left';
        $buttonStyle = (isset($form_settings['style']['button_style']) && !empty($form_settings['style']['button_style'])) ? $form_settings['style']['button_style'] : 'flat';
        $btn_style_class = ' arm_btn_style_' . $buttonStyle;
        $setupRandomID = $form_id . '_' . arm_generate_random_code();

        $is_form_class_rtl = '';
        if (is_rtl()) {
            $is_form_class_rtl = 'is_form_class_rtl';
        }


        $arm_gm_body_content .='<style type="text/css" id="arm_invite_form_style_' . $form_id . '">' . $form_css['arm_css'] . '</style>';


        $arm_gm_body_content .='<div class="arm_member_form_container arm_invite_form arm_form_' . $form_id . '">';
        $arm_gm_body_content .= '<div class="arm_setup_messages arm_form_message_container">';
        $arm_gm_body_content .= "<div class='arm_message_div'><ul><li>".$arm_gm_user_invitation_success_msg."</li></ul></div>";
        $arm_gm_body_content .= '</div>';

        $arm_gm_body_content .= '<div class="armclear"></div>';

        $arm_gm_body_content .='<form method="post" name="arm_form" id="arm_invite_form_' . $setupRandomID . '" class="arm_invite_form_id arm_setup_form_' . $form_id . ' arm_invite_setup_form  ' . $is_form_class_rtl . '" enctype="multipart/form-data" data-random-id="' . $setupRandomID . '"  novalidate>';

        $arm_gm_pass_hidden_fields = array(
            'display_delete_button' => $display_delete_button, 
            'delete_button_text' => $delete_button_text, 
            'display_resend_email_button' => $display_resend_email_button, 
            'resend_email_button_text' => $resend_email_button_text, 
            'display_refresh_invite_code_button' => $display_refresh_invite_code_button,
        );
        foreach($arm_gm_pass_hidden_fields as $arm_gm_hidden_key => $arm_gm_hidden_value)
        {
            $arm_gm_body_content .= "<input type='hidden' name='".$arm_gm_hidden_key."' value='".$arm_gm_hidden_value."'>";
        }


        $arm_gm_body_content .='<div class="arm_module_gateway_fields arm_module_gateway_fields arm_member_form_container">';
        $arm_gm_body_content .='<div class="' . $form_style_class . '">';
        

        $type ='invite';
        $fieldtable = '';
        $arm_invite_fields_array = array('email_label');



        foreach ($arm_invite_fields_array as $key) 
        {
            $fieldLabel = $fieldClass = $fieldAttr = $validation = $fieldDesc = '';
            switch ($key) {
                case 'email_label':
                    $fieldLabel = $popup_field_label;
                    $fieldAttr = 'name="' . $type . '[' . $key . ']" data-ng-model="arm_form.invite_email' . $type . '" ';

                    $fieldAttr .= ' data-ng-required="" data-msg-required="' . esc_html__('This field can not be left blank', 'ARMGroupMembership') . '"';  

                    $fieldAttr .= 'onfocusout="arm_gm_validate_field_len(this);"';

                    $fieldAttr .= 'data-arm_min_len_msg="'.esc_html__('Please enter valid email address', 'ARMGroupMembership').'"';
                    
                    $fieldClass = ' invite_email';
                    $validation .= '<div data-ng-messages="arm_form[\'' . $type . '[' . $key . ']\'].$error" data-ng-show="arm_form[\'' . $type . '[' . $key . ']\'].$touched" class="arm_error_msg_box">';
                    $ey_error =  esc_html__('This field can not be left blank', 'ARMGroupMembership');
                    $validation .= '<div data-ng-message="required" class="arm_error_msg"><div class="arm_error_box_arrow"></div>' . $ey_error . '</div>';
                    $validation .= '</div>';
                    $current_site_url = site_url();
                    break;
                default:
                    break;
            }

            $fieldtable .="<div class='arm_cc_field_wrapper arm_form_field_container arm_form_field_container_text arm_subsite_form_field_container'>";
            $fieldtable .="<div class='arm_form_label_wrapper arm_form_field_label_wrapper arm_form_member_field_text'>";
            $fieldtable .="<div class='arm_member_form_field_label'>";
            $fieldtable .="<div class='arm_form_field_label_text' >".$popup_field_label."</div>";
            $fieldtable .="</div>";
            $fieldtable .="</div>";
            $fieldtable .="<div class='arm_label_input_separator'></div>";
            $fieldtable .="<div class='arm_form_input_wrapper' >";
            $fieldtable .="<div class='arm_form_input_container arm_form_input_container_".$key."' >";
            $ngKeyPress = 'onkeypress="armKeyPressSubmitForm(this, event)"';
            $fieldtable .="<md-input-container class='md-block' flex-gt-sm=''>
                         <label class='arm_material_label' for='arm_".$key."'> * ".$fieldLabel."</label>
                            <input type='text' class='md-input field_".$type." ".$fieldClass."' 
                             value=''  id='arm_".$key."' ".$fieldAttr." ".$ngKeyPress." data-current_page='".$current_page."' data-per_page='".$per_page."'>
                            ".$validation."
                        </md-input-container>";
            $fieldtable .="</div>";
            $fieldtable .="</div>";
            $fieldtable .="</div>";
        }



        $arm_gm_body_content .= '<div class="arm_cc_fields_container arm_' . $type . '_fields arm_form_wrapper_container arm_field_position_' . $fieldPosition . '" >';
        $arm_gm_body_content .= '<span class="payment-errors"></span>';
        $arm_gm_body_content .= $fieldtable;
        $arm_gm_body_content .= '</div>';
        $arm_gm_body_content .= '<div class="armclear"></div>';
        $arm_gm_body_content .= '</div>';



        $arm_gm_body_content .='<div class="armclear"></div>';
        $arm_gm_body_content .='<div class="arm_form_field_container arm_form_field_container_submit" style="text-align: center;">';
        $arm_gm_body_content .='<div class="arm_label_input_separator"></div>';
        $arm_gm_body_content .='<div class="arm_form_label_wrapper arm_form_field_label_wrapper arm_form_member_field_submit"></div>';
        $arm_gm_body_content .='<div class="arm_form_input_wrapper">';
        $arm_gm_body_content .='<div class="arm_form_input_container_submit arm_form_input_container" id="arm_invite_form' . $form_id . '">';


        $ngClick = 'onclick="arminviteSubmitBtnClick(this)"';
        if (current_user_can('administrator')) {
            $ngClick = 'onclick="return false;"';
        }

        $arm_gm_body_content .='<button type="button" name="arm_gm_invite_btn" class="arm_form_field_submit_button arm_form_field_container_button arm_form_input_box arm_material_input ' . $btn_style_class . '"  ' . $ngClick . ' id="arm_invite_invite_submit" data-current_page="'.$current_page.'" data-per_page="'.$per_page.'"><span class="arm_spinner">'.file_get_contents(MEMBERSHIP_IMAGES_DIR."/loader.svg").'</span>'.$popup_button_text.'</button>';

        $arm_gm_body_content .='</div>';
        $arm_gm_body_content .='</div>';
        $arm_gm_body_content .='<div class="armclear" data-ng-init="arminviteForm(\'' . $type . '\');"></div>';
        $arm_gm_body_content .='</div>';
        $arm_gm_body_content .='</div>';
        $arm_gm_body_content .='</form>';
        $arm_gm_body_content .='</div>';
        $arm_gm_body_content .='</div>';    
        $arm_gm_body_content .='</div>';
        $arm_gm_body_content .='</div>';

        
        $arm_gm_body_content .= '</div>'; 
    // Invite User Modal Code End


    
    $arm_gm_body_content .= '<form method="POST" class="arm_group_membership_form_container">';
    $arm_gm_body_content .= '<div class="arm_setup_messages arm_form_message_container"></div>';

    $arm_gm_body_content .= "<div class='arm_gm_child_user_parent_wrapper_container'>";
    $arm_gm_body_content .= '<div class="armclear"></div>';
    $arm_gm_body_content .= "<div><span class='arm_gm_delete_user_msg'>".$arm_gm_user_delete_success_msg."</span></div>";
    $arm_gm_body_content .= "<div><span class='arm_gm_resend_email_msg'>".$arm_gm_user_resend_email_msg."</span></div>";
    $arm_gm_body_content .= "<div><span class='arm_gm_refresh_invite_code_msg'>".$arm_gm_user_refresh_invite_code_msg."</span></div>";
    $arm_gm_body_content .= '<div class="armclear"></div>';

    if (is_rtl()) {
        $is_group_membership_class_rtl = 'is_group_membership_class_rtl';
    } else {
        $is_group_membership_class_rtl = '';
    }



    $arm_gm_body_content .= "<div class='arm_group_membership_content " . $is_group_membership_class_rtl . "'>";

    //Invite Button Code
        $arm_gm_hide_button = (empty($arm_gm_max_coupon_count)) ? "style='display: none;'" : '';
        $arm_gm_body_content .= "<div><button type='button' class='arm_gm_child_member_list_invite_btn arm_invite_user_button' ".$arm_gm_hide_button.">".__('Invite Users', 'ARMGroupMembership')."</button></div>";

    

    $arm_gm_body_content .= "<table class='arm_user_group_membership_list_table arm_front_grid' cellpadding='0' cellspacing='0' border='0'>";
    $arm_gm_body_content .= "<thead>";
    $arm_gm_body_content .= "<tr class='arm_group_membership_list_header' id='arm_group_membership_list_header'>";

    $arm_gm_body_content .= "<th class='arm_gm_username_th'>".$arm_gm_membership_field_username."</th>";
    $arm_gm_body_content .= "<th class='arm_gm_name_th'>".$arm_gm_membership_field_name."</th>";
    $arm_gm_body_content .= "<th class='arm_gm_email_th'>".$arm_gm_membership_field_email."</th>";
    $arm_gm_body_content .= "<th class='arm_gm_status_th'>".$arm_gm_membership_field_status."</th>";
    $arm_gm_body_content .= "<th class='arm_gm_action_th'>".$arm_gm_membership_field_action."</th>";

    $arm_gm_body_content .= "</tr>";
    $arm_gm_body_content .= "</thead>";
    $arm_gm_body_content .= "<tbody class='arm_gm_child_user_list_tbody'>";

    if(!empty($child_users_list))
    {
        $sr_no = ($current_page > 1) ? (($current_page * $per_page) - $per_page) : 0;
        foreach($child_users_list as $arm_gm_child_users_key => $arm_gm_child_users_val)
        {
            $sr_no++;

            $arm_gm_user_data = get_user_by('email', $arm_gm_child_users_val->arm_gm_email_id);
            $arm_gm_first_name = "-";
            $arm_gm_last_name = "";
            $arm_gm_username = "-";

            if(!empty($arm_gm_user_data))
            {
                $arm_gm_first_name = get_user_meta($arm_gm_user_data->data->ID, 'first_name', true);
                $arm_gm_last_name = get_user_meta($arm_gm_user_data->data->ID, 'last_name', true);

                $arm_gm_username = !empty($arm_gm_user_data->data->user_login) ? $arm_gm_user_data->data->user_login : '';
            }

            $arm_gm_user_email = !empty($arm_gm_child_users_val->arm_gm_email_id) ? $arm_gm_child_users_val->arm_gm_email_id : '';
            $arm_gm_first_name = !empty($arm_gm_first_name) ? $arm_gm_first_name : '';
            $arm_gm_last_name = !empty($arm_gm_last_name) ? $arm_gm_last_name : '';

            $arm_gm_status_text = !empty($arm_gm_child_users_val->arm_gm_status) ? "<span class='arm_gm_status_active'>".__('Active', 'ARMGroupMembership')."</span>" : "<span class='arm_gm_status_pending'>".__('Pending', 'ARMGroupMembership')."</span>";


            $arm_gm_fetch_coupon_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$ARMember->tbl_arm_coupons." WHERE arm_coupon_id = %d", array($arm_gm_child_users_val->arm_gm_invite_code_id)));


            $arm_gm_body_content .= "<tr class='arm_group_membership_list_item' id='arm_group_membership_tr_" . $sr_no . "'>";

            $arm_gm_body_content .= "<td class='arm_group_membership_list_item_plan_sr'>".$arm_gm_username."</td>";

            $arm_gm_body_content .= "<td class='arm_group_membership_list_item_plan_sr'>".$arm_gm_first_name.' '.$arm_gm_last_name."</td>";

            $arm_gm_body_content .= "<td class='arm_group_membership_list_item_plan_sr'>".$arm_gm_user_email."</td>";



            $arm_gm_body_content .= "<td class='arm_group_membership_list_item_plan_sr'>".$arm_gm_status_text."<br>".__('Code:', 'ARMGroupMembership')." ";
            if(empty($arm_gm_child_users_val->arm_gm_status))
            {
                $arm_gm_body_content .= "<div class='arm_shortcode_text arm_form_shortcode_box'><span class='armCopyText'>".$arm_gm_fetch_coupon_data->arm_coupon_code."</span><span class='arm_click_to_copy_text' data-code='".$arm_gm_fetch_coupon_data->arm_coupon_code."'>Click to copy</span><span class='arm_copied_text'><img src='".MEMBERSHIP_IMAGES_URL."/copied_ok.png' alt='ok'>Code Copied</span>".$arm_gm_fetch_coupon_data->arm_coupon_code."";
            }
            else
            {
                $arm_gm_body_content .= $arm_gm_fetch_coupon_data->arm_coupon_code;
            }
            $arm_gm_body_content .= "</td>";

            $arm_gm_body_content .= "<td class='arm_group_membership_list_item_plan_sr' style='text-align: left;'>";

            if($display_delete_button == "true")
            {
            $arm_gm_body_content .= "<div class='arm_gm_delete_btn_div'><button type='button' class='arm_delete_user_button' data-cnt='".$sr_no."' data-delete_email='".$arm_gm_child_users_val->arm_gm_email_id."' data-page_no='".$current_page."' data-coupon_id='".$arm_gm_child_users_val->arm_gm_invite_code_id."' data-per_page='".$per_page."'>".$delete_button_text."</button></div>";
            }

            if(empty($arm_gm_child_users_val->arm_gm_status) && ($display_resend_email_button == "true"))
            {
                $arm_gm_body_content .= "<div class='arm_gm_resend_btn_div'><button type='button' class='arm_gm_resend_email_button' data-cnt='".$sr_no."' data-resend_email='".$arm_gm_child_users_val->arm_gm_email_id."' data-page_no='".$current_page."' data-coupon_id='".$arm_gm_child_users_val->arm_gm_invite_code_id."' data-per_page='".$per_page."'>".$resend_email_button_text."</button></div>";
            }

            if(empty($arm_gm_child_users_val->arm_gm_status) && ($display_refresh_invite_code_button == "true"))
            {
                $arm_gm_body_content .= "<div class='arm_gm_refresh_btn_div'><button type='button' class='arm_gm_refresh_coupon_code' data-coupon_id='".$arm_gm_child_users_val->arm_gm_invite_code_id."' data-page_no='".$current_page."' data-per_page='".$per_page."'><img id='arm_loader_img' src='".MEMBERSHIP_IMAGES_URL."/reset-button.png' alt='Loading..'></button></div>";
            }

            $arm_gm_body_content .= "</td>";

            $arm_gm_body_content .= "</tr>";
        }
    }
    else
    {
        $arm_gm_body_content .="<tr class='arm_group_membership_list_item' id='arm_group_membership_list_item_no_plan'>";
        $arm_gm_body_content .="<td colspan='5' align='center' class='arm_no_plan'>" . __('No Child user found.', 'ARMGroupMembership') . "</td>";
        $arm_gm_body_content .="</tr>";
    }

    $arm_gm_body_content .= "</tbody>";
    $arm_gm_body_content .= "</table>";

    $membershipPaging = $arm_global_settings->arm_get_paging_links($current_page, $child_users_count, $per_page, 'group_membership');
    $arm_gm_body_content .= "<div class='arm_group_membership_child_user_paging_container'>$membershipPaging</div>";
    $arm_gm_body_content .= "</div>";
    $arm_gm_body_content .= "</div>";

    echo $arm_gm_body_content;

?>