<?php
    $arm_gm_tbody_content = "";
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


            $arm_gm_tbody_content .= "<tr class='arm_group_membership_list_item' id='arm_group_membership_tr_" . $sr_no . "'>";

            $arm_gm_tbody_content .= "<td class='arm_group_membership_list_item_plan_sr'>".$arm_gm_username."</td>";

            $arm_gm_tbody_content .= "<td class='arm_group_membership_list_item_plan_sr'>".$arm_gm_first_name.' '.$arm_gm_last_name."</td>";

            $arm_gm_tbody_content .= "<td class='arm_group_membership_list_item_plan_sr'>".$arm_gm_user_email."</td>";



            $arm_gm_tbody_content .= "<td class='arm_group_membership_list_item_plan_sr'>".$arm_gm_status_text."<br>".__('Code:', 'ARMGroupMembership')." ";
            if(empty($arm_gm_child_users_val->arm_gm_status))
            {
                $arm_gm_tbody_content .= "<div class='arm_shortcode_text arm_form_shortcode_box'><span class='armCopyText'>".$arm_gm_fetch_coupon_data->arm_coupon_code."</span><span class='arm_click_to_copy_text' data-code='".$arm_gm_fetch_coupon_data->arm_coupon_code."'>Click to copy</span><span class='arm_copied_text'><img src='".MEMBERSHIP_IMAGES_URL."/copied_ok.png' alt='ok'>Code Copied</span>".$arm_gm_fetch_coupon_data->arm_coupon_code."";
            }
            else
            {
                $arm_gm_tbody_content .= $arm_gm_fetch_coupon_data->arm_coupon_code;
            }
            $arm_gm_tbody_content .= "</td>";

            $arm_gm_tbody_content .= "<td class='arm_group_membership_list_item_plan_sr' style='text-align: left;'>";

            if($display_delete_button == "true")
            {
            $arm_gm_tbody_content .= "<div class='arm_gm_delete_btn_div'><button type='button' class='arm_delete_user_button' data-cnt='".$sr_no."' data-delete_email='".$arm_gm_child_users_val->arm_gm_email_id."' data-page_no='".$current_page."' data-coupon_id='".$arm_gm_child_users_val->arm_gm_invite_code_id."' data-per_page='".$per_page."'>".$delete_button_text."</button></div>";
            }

            if(empty($arm_gm_child_users_val->arm_gm_status) && ($display_resend_email_button == "true"))
            {
                $arm_gm_tbody_content .= "<div class='arm_gm_resend_btn_div'><button type='button' class='arm_gm_resend_email_button' data-cnt='".$sr_no."' data-resend_email='".$arm_gm_child_users_val->arm_gm_email_id."' data-page_no='".$current_page."' data-coupon_id='".$arm_gm_child_users_val->arm_gm_invite_code_id."' data-per_page='".$per_page."'>".$resend_email_button_text."</button></div>";
            }

            if(empty($arm_gm_child_users_val->arm_gm_status) && ($display_refresh_invite_code_button == "true"))
            {
                $arm_gm_tbody_content .= "<div class='arm_gm_refresh_btn_div'><button type='button' class='arm_gm_refresh_coupon_code' data-coupon_id='".$arm_gm_child_users_val->arm_gm_invite_code_id."' data-page_no='".$current_page."' data-per_page='".$per_page."'><img id='arm_loader_img' src='".MEMBERSHIP_IMAGES_URL."/reset-button.png' alt='Loading..'></button></div>";
            }

            $arm_gm_tbody_content .= "</td>";

            $arm_gm_tbody_content .= "</tr>";
        }
    }
    else
    {
        $arm_gm_tbody_content .="<tr class='arm_group_membership_list_item' id='arm_group_membership_list_item_no_plan'>";
        $arm_gm_tbody_content .="<td colspan='5' align='center' class='arm_no_plan'>" . __('No Child user found.', 'ARMGroupMembership') . "</td>";
        $arm_gm_tbody_content .="</tr>";
    }


    $membershipPaging = $arm_global_settings->arm_get_paging_links($current_page, $child_users_count, $per_page, 'group_membership');
    $arm_gm_paging_content = "$membershipPaging";

    $arm_gm_return_content['arm_gm_tbody_content'] = $arm_gm_tbody_content;
    $arm_gm_return_content['arm_gm_paging_content'] = $arm_gm_paging_content;
    $arm_gm_return_content['arm_gm_is_hide'] = ($arm_gm_max_coupon_count == count($arm_gm_child_users)) ? true : false;
    echo json_encode($arm_gm_return_content);
?>