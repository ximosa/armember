function ArmNumberValidation(evt, obj) {
    var iKeyCode = (evt.which) ? evt.which : evt.keyCode
    var value = jQuery(obj).val();
    if (iKeyCode != 46 && iKeyCode > 31 && (iKeyCode < 48 || iKeyCode > 57) && iKeyCode != 37 && iKeyCode != 39) {
        return false;
    } else {
        if (value != '') {
            var result = value.split('.');
            if (result.length > 1 && iKeyCode == 46) {
                return false;
            }
        }
    }
    return true;
}

jQuery(document).on('blur', '#arm_group_membership_div #gm_max_members, #arm_group_membership_div #gm_min_members', function(e){
    var arm_gm_max_member_val = Number(jQuery("#arm_group_membership_div #gm_max_members").val());
    var arm_gm_min_member_val = Number(jQuery("#arm_group_membership_div #gm_min_members").val());
    if((arm_gm_min_member_val != "") && (arm_gm_max_member_val != "") && (arm_gm_max_member_val < arm_gm_min_member_val))
    {
        jQuery(".arm_max_member_error").fadeIn();
    }
    else
    {
        jQuery(".arm_max_member_error").fadeOut();
    }
});

jQuery(document).on('blur', '#arm_group_membership_div #gm_sub_user_seat_slot', function(){
    var arm_gm_max_member_val = Number(jQuery("#arm_group_membership_div #gm_max_members").val());
    var arm_gm_sub_user_slot = Number(jQuery("#arm_group_membership_div #gm_sub_user_seat_slot").val());

    if((arm_gm_max_member_val != "") && (arm_gm_sub_user_slot != "") && (arm_gm_sub_user_slot > arm_gm_max_member_val))
    {
        jQuery(".arm_sub_user_slot_error").fadeIn();
    }
    else
    {
        jQuery(".arm_sub_user_slot_error").fadeOut();
    }
});

jQuery(document).on('change', 'input[name=arm_subscription_plan_type]', function(e){
    var arm_selected_plan_type = jQuery(this).val();
    if(arm_selected_plan_type == "recurring")
    {
        jQuery(".arm_group_membership_recurring_error").fadeIn();
    }
    else
    {
        jQuery(".arm_group_membership_recurring_error").fadeOut();   
    }
});

jQuery(document).on('change', "#arm_gm_group_membership_disable_referral", function () {
    var isChecked = jQuery(this).is(':checked');
    var parent_wrapper = jQuery(this).parents('.page_sub_content');
    if(isChecked){
        parent_wrapper.find('tr.arm_gm_sub_opt').removeClass('hidden_section');
    } else {
        parent_wrapper.find('tr.arm_gm_sub_opt').addClass('hidden_section');
    }
});

jQuery(document).on('click', '.arm_gm_child_users', function () {
    var arm_gm_parent_user_id = jQuery(this).attr('data-parent_id');
    if (arm_gm_parent_user_id != '' && arm_gm_parent_user_id != 0) {
        jQuery('.arm_loading').fadeIn('slow');
        
        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            data: "action=arm_child_user_details&parent_user_id=" + arm_gm_parent_user_id,
            success: function (response) {
                if (response != '') {
                    jQuery('.arm_gm_child_user_content_data').html(response);
                    var bPopup = jQuery('#arm_gm_child_users_data').bPopup({
			            opacity: 0.5,
			            follow: [false, false],
			            closeClass: 'arm_popup_close_btn',
			            onClose: function () {
			                //jQuery('#arm_gm_child_users_data').close();
			            }
			        });
			        bPopup.reposition(100);
                    setTimeout(function () {
                        jQuery('.arm_loading').fadeOut();
                    }, 500);
                } else {
                    alert(invoiceTransactionError);
                }
            }
        });
    }
});


function showDeleteConfirmation(item_id) {
    if (item_id != '') {
        var deleteBox = jQuery('#arm_confirm_box_' + item_id);
        deleteBox.addClass('armopen').toggle('slide');
        deleteBox.parents('.armGridActionTD').toggleClass('armopen');
        deleteBox.parents('tr').toggleClass('armopen');
        deleteBox.parents('.dataTables_wrapper').append('<div class="arm_confirm_back_wrapper" onclick="hideConfirmBoxCallback();"></div>');
        deleteBox.parents('.armPageContainer').append('<div class="arm_confirm_back_wrapper" onclick="hideConfirmBoxCallback();"></div>');
    }
    return false;
}


function hideConfirmBoxCallback() {
    jQuery('.arm_confirm_box.armopen').removeClass('armopen').toggle('slide', function () {
        jQuery('.armGridActionTD').removeClass('armopen');
        jQuery('tr').removeClass('armopen');
        jQuery('.arm_confirm_back_wrapper').remove();
        jQuery('.arm_field_content_settings_selected').removeClass('arm_field_content_settings_selected');
    });
    return false;
}



function armToast(message, type, time, reload) {
    if (reload == '' || typeof reload == 'undefined') {
        reload = false;
    }
    if (time == '' || typeof time == 'undefined') {
        var time = 2500;
    }
    var msgWrapperID = 'arm_error_message';
    if (type == 'success') {
        var msgWrapperID = 'arm_success_message';
    } else if (type == 'error') {
        var msgWrapperID = 'arm_error_message';
    } else if (type == 'info') {
        var msgWrapperID = 'arm_error_message';
    }
    var toastHtml = '<div class="arm_toast arm_message ' + msgWrapperID + '" id="' + msgWrapperID + '"><div class="arm_message_text">' + message + '</div></div>';
    if (jQuery('.arm_toast_container .arm_toast').length > 0) {
        jQuery('.arm_toast_container .arm_toast').remove();
    }
    jQuery(toastHtml).appendTo('.arm_toast_container').show('slow').addClass('arm_toast_open').delay(time).queue(function () {
        if (type != 'error' && type != 'buddypress_error') {
            var $toast = jQuery(this);
            jQuery('.arm_already_clicked').removeClass('arm_already_clicked').removeAttr('disabled');
            $toast.addClass('arm_toast_close');
            if (reload === true) {
                location.reload();
            }
            setTimeout(function () {
                $toast.remove();
            }, 1000);
        } else {
            var $toast = jQuery(this);
            $toast.addClass('arm_toast_close');
            setTimeout(function () {
                $toast.remove();
            }, 1000);
        }
    });
}


jQuery(document).on('click', '.arm_gm_parent_user_delete_btn', function(e){
    var arm_gm_parent_user_delete = jQuery(this).data('item_id');
    jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        data: "action=arm_gm_delete_users&arm_gm_parent_user_delete="+arm_gm_parent_user_delete,
        beforeSend: function () {
            jQuery('.arm_loading').fadeIn('slow');
        },
        success: function (response) {
            jQuery('.arm_loading').fadeOut();
            armToast(__Users_Del_Success_Msg, 'success');
            setTimeout(function(){
                location.reload();
            }, 400);
        }
    });
});





jQuery(document).on('click', '.arm_openpreview_popup', function () { 
    var member_id = jQuery(this).attr('data-id');
    if (member_id != '' && member_id != 0) {
        jQuery('.arm_loading').fadeIn('slow');
        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            data: "action=arm_member_view_detail&member_id=" + member_id + "&view_type=popup",
            success: function (response) {
                if (response != '') {
                    jQuery('.arm_member_view_detail_container').html(response);
                    var bPopup = jQuery('.arm_member_view_detail_popup').bPopup({
                        opacity: 0.5,
                        follow: [false, false],
                        closeClass: 'arm_member_view_detail_close_btn',
                        onClose: function () {
                            jQuery('.arm_member_view_detail_popup').remove();
                        }
                    });
                    bPopup.reposition(100);
                    setTimeout(function () {
                        jQuery('.arm_loading').fadeOut();
                    }, 1000);
                } else {
                    jQuery('.arm_loading').fadeOut();
                    alert(prevMemberDetailError);
                }
            },
            error: function (response) {
                jQuery('.arm_loading').fadeOut();
            }
        });
    }
    return false;
});




function showEditDataModal(edit_user_id)
{
    jQuery('.arm_loading').fadeIn('slow');
    jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        data: "action=arm_gm_edit_group_membership&edit_user_id=" + edit_user_id,
        success: function (response) {
            if (response != '') {
                jQuery('.arm_gm_edit_user_content_data').html(response);
                var bPopup = jQuery('#arm_gm_edit_users_data').bPopup({
                    opacity: 0.5,
                    follow: [false, false],
                    closeClass: 'arm_popup_close_btn',
                    onClose: function () {
                        jQuery('arm_popup_close_btn').trigger('click');
                    }
                });
                bPopup.reposition(100);
                setTimeout(function () {
                    jQuery('.arm_loading').fadeOut();
                }, 500);
                jQuery('.edit_maximum_seat_slot').focus();
            } else {
                alert(invoiceTransactionError);
            }
        }
    });
}


jQuery(document).on('click', '.arm_member_edit_gm_cancel_btn', function(e){
    jQuery('.arm_popup_close_btn').trigger('click');
});


jQuery(document).on('click', '.arm_member_edit_gm_save_btn', function(e){
    var arm_gm_update_data = jQuery("#arm_gm_update_form").serialize();
    var arm_gm_seat_slot = jQuery('input.edit_maximum_seat_slot').val();
    jQuery('.arm_gm_child_users_seat_error').hide();
    if (arm_gm_seat_slot == ''){
        jQuery('.arm_gm_child_users_seat_error').show();
        return false;
    } else if(arm_gm_seat_slot <= 0) {
        jQuery('.arm_gm_child_users_seat_positive_error').show();
        return false;
    }
    jQuery('.arm_loading').fadeIn('slow');
    jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        data: "action=arm_gm_update_group_membership&" + arm_gm_update_data,
        success: function (response) {
            armToast(__Data_Update, 'success');
            setTimeout(function () {
                jQuery('.arm_loading').fadeOut();
                location.reload();
            }, 800);
        }
    });
});


function ArmgmCheckLimit(evt, obj, AlreadyPurchased, MaximumLimit, totalPurchased) {
    var iKeyCode = (evt.which) ? evt.which : evt.keyCode
    var value = jQuery(obj).val();
    if (iKeyCode != 46 && iKeyCode > 31 && (iKeyCode < 48 || iKeyCode > 57) && iKeyCode != 37 && iKeyCode != 39) {
        return false;
    } else {
        if (value != '') {
            var result = value.split('.');
            if (result.length >= 1 && iKeyCode == 46) {
                return false;
            }
            else{
                var current_purchased = (parseInt(value) + parseInt(totalPurchased));
                if(current_purchased <= MaximumLimit)
                {
                    if(value < AlreadyPurchased)
                    {
                        jQuery(".arm_member_edit_gm_save_btn").attr('disabled', 'disabled');
                        jQuery(".arm_member_edit_gm_save_btn").css('opacity', '0.5');
                        jQuery(".arm_member_edit_gm_save_btn").css('cursor', 'no-drop');
                        armToast(jQuery(obj).data('error'), 'error');
                        return false;
                    }
                    else if(value > MaximumLimit)
                    {
                        jQuery(".arm_member_edit_gm_save_btn").attr('disabled', 'disabled');
                        jQuery(".arm_member_edit_gm_save_btn").css('opacity', '0.5');
                        jQuery(".arm_member_edit_gm_save_btn").css('cursor', 'no-drop');
                        armToast(jQuery(obj).data('max_error'), 'error');
                        return false;    
                    }
                    else
                    {
                        jQuery(".arm_member_edit_gm_save_btn").css('opacity', '1');
                        jQuery(".arm_member_edit_gm_save_btn").css('cursor', 'pointer');
                        jQuery(".arm_member_edit_gm_save_btn").removeAttr('disabled', 'disabled');
                    }
                }
                else
                {
                    jQuery(".arm_member_edit_gm_save_btn").attr('disabled', 'disabled');
                    jQuery(".arm_member_edit_gm_save_btn").css('opacity', '0.5');
                    jQuery(".arm_member_edit_gm_save_btn").css('cursor', 'no-drop');
                    armToast(jQuery(obj).data('total_error'), 'error');
                    return false;
                }
            }
        }
    }
    return true;
}





// Code of display shortcode fields of child user in Membership Shortcode
jQuery(document).on('change', '#arm_shortcode_form_type, #arm_shortcode_form_id', function(e){
    var arm_gm_selected_val = jQuery("#arm_shortcode_form_type").val();
    if(arm_gm_selected_val == "registration")
    {
        jQuery(".arm_gm_child_user_invite_code_tr").removeClass('arm_hidden');
    }
    else
    {
        jQuery(".arm_gm_child_user_invite_code_tr").addClass('arm_hidden');    
    }
});




// Code for Group Membership Child User Add Module
jQuery(function(){
    jQuery('#arm_gm_add_form .arm_selectbox').each(function () {
        var $dl = jQuery(this);
        var $ul = $dl.find('dd ul');
        var input_id = $ul.attr('data-id');
        var value = jQuery('#' + input_id).val();
        $ul.find('li').each(function () {
            var $thisText = jQuery(this).text();
            var $optVal = jQuery(this).attr('data-value');
            var $optType = jQuery(this).attr('data-type');
            if ($optVal == value) {
                $dl.find('dt span').text($thisText);
                $dl.find('dt input').val($thisText);
                jQuery('#' + input_id).attr('data-type', $optType);
            }
        });
    });
});


jQuery(document).on('click', '#arm_gm_add_form .arm_selectbox', function () {
    jQuery(this).find('dd ul').toggle();
});



jQuery(document).on('click', '#arm_gm_add_form .arm_selectbox dt', function (e) {
    var $thisDT = jQuery(this);
    if ($thisDT.parent().find('dd ul').is(":visible") == false) {
        jQuery('dd ul').not(this).hide();
        $thisDT.find('span').hide();
        $thisDT.find('input').show();
        $thisDT.find('input').focus();
    } else {
        $thisDT.parent().find('dd ul').show();
    }
    $thisDT.parent().find('dd ul li:not(.field_inactive)').show();
});


jQuery(document).on('keyup', '#arm_gm_add_form .arm_selectbox dt input', function (e) {
    e.stopPropagation();
    var keyCode = e.keyCode;
    var excludeKeys = [16, 17, 18, 19, 20, 33, 34, 35, 36, 37, 38, 39, 40, 45, 91, 92, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 144, 145];
    if (jQuery.inArray(keyCode, excludeKeys) === -1) {
        jQuery(this).parent().parent().find('dd ul').scrollTop();
        var value = jQuery(this).val();
        value = value.toLowerCase();
        jQuery(this).parent().parent().find('dd ul').show();
        jQuery(this).parent().parent().find('dd ul li:not(.field_inactive)').each(function (x) {
            var text = jQuery(this).attr('data-label').toLowerCase();
            (text.indexOf(value) != -1) ? jQuery(this).show() : jQuery(this).hide();
        });
    }
});


jQuery(document).on('click', "#arm_gm_add_form .arm_selectbox dd ul li:not(.field_inactive)", function (e) {

    jQuery(document).find('.arm_selectbox:active dd ul').hide();
    var optValue = jQuery(this).attr('data-value');
    var data_label = jQuery(this).attr('data-label');
    var optLabel = (data_label != '' && data_label != undefined) ? data_label : jQuery(this).html();
    var data_type = jQuery(this).attr('data-type');
    jQuery('#arm_setup_clicked_plan_skin').val(optValue);
    jQuery('#arm_setup_clicked_plan_skin').attr('data-value',optValue);
    jQuery('#arm_setup_clicked_plan_skin').attr('data-label',optLabel);
    jQuery('#arm_setup_clicked_plan_skin').attr('data-type',data_type);
    if(jQuery(this).parent().attr('data-id') == 'arm_setup_plan_skin'){

        jQuery('#plan_skin_change_message').bPopup({closeClass: 'popup_close_btn'});
        return false;
    }
    if( jQuery(this).parent().attr('data-id') == 'arm_form_opacity'){
        var frmBGColor = jQuery('.arm_editor_form_fileds_wrapper').css('background-color');
        var form_opacity = optValue;
        frmBGColor = frmBGColor.replace(/(rgb\()|(rgba\()/g,'');
        frmBGColor = frmBGColor.replace(')','');
        frmBGColor = frmBGColor.split(',');

        var frmR = frmBGColor[0].trim();
        var frmG = frmBGColor[1].trim();
        var frmB = frmBGColor[2].trim();
        var newColor = "rgba("+frmR+","+frmG+","+frmB+","+form_opacity+")";
        var style_attr = jQuery('.arm_editor_form_fileds_wrapper').attr('style');
        if( typeof style_attr != 'undefined'){
            jQuery('.arm_editor_form_fileds_wrapper').css('background-color',newColor);
        }
    }
    var $selectBox = jQuery(this).parents('.arm_selectbox');
    var data_label = jQuery(this).attr('data-label');
    var optLabel = (data_label != '' && data_label != undefined) ? data_label : jQuery(this).html();
    var optValue = jQuery(this).attr('data-value');
    var data_type = jQuery(this).attr('data-type');
    var $list = $selectBox.find('dd ul');
    var id = $list.attr('data-id');
    var oldValue = jQuery('input#' + id).val();
    $selectBox.find('dt span').html(optLabel).show();
    $selectBox.find('dt input').val(optLabel).hide();
    jQuery('input#' + id).val(optValue);
    setTimeout(function(){    
        jQuery('input#' + id).attr("value", optValue);
    },500);
    document.getElementById(id).setAttribute('value', optValue);
    jQuery('input#' + id).attr('data-type', data_type);
    if (oldValue != optValue) {
        jQuery('input#' + id).attr('data-old_value', oldValue);
        jQuery('input#' + id).trigger('change');
    }
    $list.find('li:not(.field_inactive)').show();
});


function show_hide_pass() 
{
    var field_type = jQuery("#arm_gm_user_pass").attr('type');
    if(field_type == "text")
    {
        jQuery("#arm_gm_user_pass").attr('type', 'password');
        jQuery(".arm_visible_password_admin").find("i").removeClass("armfa-eye-slash");
        jQuery(".arm_visible_password_admin").find("i").addClass("armfa-eye");
    }
    else if(field_type == "password")
    {
        jQuery("#arm_gm_user_pass").attr('type', 'text');
        jQuery(".arm_visible_password_admin").find("i").addClass("armfa-eye-slash");
        jQuery(".arm_visible_password_admin").find("i").removeClass("armfa-eye");    
    }
}


jQuery(document).on('change', '#arm_gm_enable_child_user', function(){
    if(jQuery(this).prop('checked'))
    {
        jQuery(".arm_gm_sub_user_add_div").fadeIn();
    }
    else
    {
        jQuery(".arm_gm_sub_user_add_div").fadeOut();   
    }
});


function showSubUserAddModal(arm_gm_parent_user_id)
{
    jQuery('#arm_gm_add_sub_users_data .arm_loading').fadeIn('slow');
    var bPopup = jQuery('#arm_gm_add_sub_users_data').bPopup({
        opacity: 0.5,
        follow: [false, false],
        closeClass: 'arm_popup_close_btn',
        onClose: function () {
            jQuery('.arm_popup_close_btn').trigger('click');
        }
    });
    bPopup.reposition(100);
    setTimeout(function () {
        jQuery('#arm_gm_add_sub_users_data .arm_loading').fadeOut();
    }, 500);
    jQuery("#arm_gm_add_sub_users_data #arm_gm_parent_user_id").val(arm_gm_parent_user_id);
}


function showSubUserConfirmation(item_id) {
    if (item_id != '') {
        var deleteBox = jQuery('#arm_confirm_box_' + item_id);
        deleteBox.addClass('armopen').toggle('slide');
        deleteBox.parents('.armGridActionTD').toggleClass('armopen');
        deleteBox.parents('tr').toggleClass('armopen');
        deleteBox.parents('.dataTables_wrapper').append('<div class="arm_confirm_back_wrapper" onclick="hideConfirmBoxCallback();"></div>');
        deleteBox.parents('.armPageContainer').append('<div class="arm_confirm_back_wrapper" onclick="hideConfirmBoxCallback();"></div>');
        jQuery(".arm_confirm_box_btn_container .arm_gm_child_user_add_confirmation").css('display', 'none');
        var arm_gm_child_user_confirmation_txt = jQuery("#arm_gm_child_user_confirmation_txt").val();
        jQuery(".arm_confirm_box_btn_container .arm_gm_child_user_add_confirmation").next().text(arm_gm_child_user_confirmation_txt);
    }
    return false;
}


jQuery(document).on('click', '.arm_member_add_sub_user_gm_cancel_btn', function(e){
    jQuery(".arm_popup_close_btn").trigger('click');
});


jQuery(document).on('click', '.arm_member_add_sub_user_gm_save_btn', function(e){
    var arm_gm_username_val = jQuery(".arm_gm_sub_user_username").val();
    var arm_gm_email_val = jQuery(".arm_gm_sub_user_email").val();
    var arm_gm_pass_val = jQuery(".arm_gm_sub_user_password").val();
    if(arm_gm_username_val == "" || arm_gm_email_val == "" || arm_gm_pass_val == ""){
        if(arm_gm_username_val == ""){
            jQuery(".arm_gm_username_required_error").css('display', '');
            jQuery(".arm_gm_sub_user_username").addClass('arm_gm_display_input_error');
        }
        if(arm_gm_email_val == ""){
            jQuery(".arm_gm_email_required_error").css('display', '');
            jQuery(".arm_gm_sub_user_email").addClass('arm_gm_display_input_error');
        }
        if(arm_gm_pass_val == ""){
            jQuery(".arm_gm_pass_required_error").css('display', '');
            jQuery(".arm_gm_sub_user_password").addClass('arm_gm_display_input_error');
        }
        return false;
    }
    var arm_gm_email_validation_regex = /\S+@\S+\.\S+/;
    var arm_gm_email_validate = arm_gm_email_validation_regex.test(arm_gm_email_val);
    if(!arm_gm_email_validate)
    {
        jQuery(this).addClass('arm_gm_display_input_error');
        jQuery(".arm_gm_valid_email_required_error").css('display', 'block');
        return false;
    }
    else{
        jQuery(this).removeClass('arm_gm_display_input_error');
        jQuery(".arm_gm_valid_email_required_error").css('display', 'none');
    }
    jQuery('.arm_loading').fadeIn('slow');
    var arm_gm_add_sub_user_form_data = jQuery("#arm_gm_add_sub_user_form").serialize();
    jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        data: "action=arm_gm_add_sub_user_group_membership&" + arm_gm_add_sub_user_form_data,
        success: function (response) {
            var response_data = JSON.parse(response);
            if(response_data.status == 0)
            {
                armToast(response_data.msg, 'error');
                jQuery('.arm_loading').fadeOut();
            }
            else
            {
                armToast(response_data.msg, 'success');
                setTimeout(function(){
                    jQuery('.arm_loading').fadeOut();
                    jQuery(".arm_popup_close_btn").trigger('click');
                    jQuery('#armember_datatable').DataTable().ajax.reload();
                }, 800);
            }
        }
    });
});



jQuery(document).on('blur', '.arm_gm_sub_user_username', function(e){
    var arm_gm_current_val = jQuery(this).val();
    jQuery.ajax({
        url: __ARMAJAXURL,
        method: 'POST',
        data: 'action=arm_gm_check_email_username&arm_gm_current_val='+arm_gm_current_val,
        success: function(result){
            var response_data = JSON.parse(result);
            if(response_data.username == 1){
                jQuery(".arm_gm_username_error").fadeIn();
                jQuery(".arm_member_add_sub_user_gm_save_btn").attr('disabled', 'disabled');
                jQuery(".arm_member_add_sub_user_gm_save_btn").addClass('arm_gm_display_button_error');
                jQuery(this).addClass('arm_gm_display_input_error');
                jQuery(".arm_gm_username_error").css('display', 'block');
            }else{
                jQuery(".arm_gm_username_error").fadeOut();
                jQuery(".arm_member_add_sub_user_gm_save_btn").removeAttr('disabled', 'disabled');
                jQuery(".arm_member_add_sub_user_gm_save_btn").removeClass('arm_gm_display_button_error');
                jQuery(this).removeClass('arm_gm_display_input_error');
                jQuery(".arm_gm_username_error").css('display', 'none');
            }
        }
    });
});




jQuery(document).on('blur', '.arm_gm_sub_user_email', function(e){
    var arm_gm_current_val = jQuery(this).val();
    var arm_gm_email_validation_regex = /\S+@\S+\.\S+/;
    var arm_gm_email_validate = arm_gm_email_validation_regex.test(arm_gm_current_val);
    if(!arm_gm_email_validate)
    {
        jQuery(this).addClass('arm_gm_display_input_error');
        jQuery(".arm_gm_valid_email_required_error").css('display', 'block');
        return false;
    }
    else{
        jQuery(this).removeClass('arm_gm_display_input_error');
        jQuery(".arm_gm_valid_email_required_error").css('display', 'none');
    }
    jQuery.ajax({
        url: __ARMAJAXURL,
        method: 'POST',
        data: 'action=arm_gm_check_email_username&arm_gm_current_val='+arm_gm_current_val,
        success: function(result){
            var response_data = JSON.parse(result);
            if(response_data.email == 1){
                jQuery(".arm_gm_email_error").fadeIn();
                jQuery(".arm_member_add_sub_user_gm_save_btn").attr('disabled', 'disabled');
                jQuery(".arm_member_add_sub_user_gm_save_btn").addClass('arm_gm_display_button_error');
                jQuery(this).addClass('arm_gm_display_input_error');
                jQuery(".arm_gm_email_error").css('display', 'block');
            }else{
                jQuery(".arm_gm_email_error").fadeOut();
                jQuery(".arm_member_add_sub_user_gm_save_btn").removeAttr('disabled', 'disabled');
                jQuery(".arm_member_add_sub_user_gm_save_btn").removeClass('arm_gm_display_button_error');
                jQuery(this).removeClass('arm_gm_display_input_error');
                jQuery(".arm_gm_email_error").css('display', 'none');
            }
        }
    });
});


function arm_gm_delete_child_users(delete_user_id, user_status = 'active')
{
    var arm_gm_delete_user_id = delete_user_id;
    jQuery('.arm_loading').fadeIn('slow');
    jQuery.ajax({
        url: __ARMAJAXURL,
        method: 'POST',
        data: 'action=arm_gm_delete_child_user&arm_gm_delete_user_id='+arm_gm_delete_user_id+'&arm_gm_user_status='+user_status,
        success: function(result){
            var response_data = JSON.parse(result);
            if(response_data.status == 0)
            {
                armToast(response_data.msg, 'error');
            }
            else
            {
                armToast(response_data.msg, 'success');
            }
            setTimeout(function () {
                jQuery('.arm_loading').fadeOut();
                location.reload();
            }, 800);
        }
    });
}


jQuery(document).on('click', '.arm_gm_user_delete_btn', function(){
    var arm_gm_delete_user_id = jQuery(this).data('item_id');
    arm_gm_delete_child_users(arm_gm_delete_user_id);
});


jQuery(document).on('click', '.arm_gm_pending_user_delete_btn', function(){
    var arm_gm_delete_user_id = jQuery(this).data('item_id');
    arm_gm_delete_child_users(arm_gm_delete_user_id, 'pending');
});



jQuery(document).on('change', 'input[name="display_delete_button"]', function(e){
    if(jQuery(this).val() == "true"){
        jQuery(".arm_gm_delete_display_options").css('display', 'table-row');
    }
    else{
        jQuery(".arm_gm_delete_display_options").css('display', 'none');
    }
});

jQuery(document).on('change', 'input[name="display_resend_email_button"]', function(e){
    if(jQuery(this).val() == "true"){
        jQuery(".arm_gm_resend_email_btn_options").css('display', 'table-row');
    }
    else{
        jQuery(".arm_gm_resend_email_btn_options").css('display', 'none');
    }
});