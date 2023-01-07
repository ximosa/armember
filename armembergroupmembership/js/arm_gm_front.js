jQuery(document).ready(function(){
	//To check that default plan enable group membership and hide if not enable
	var arm_gm_default_selected_plan = jQuery("input.arm_module_plan_input:checked").val();
    
    var arm_gm_subscription_plan_val = jQuery('[name=subscription_plan]').val();
    if(arm_gm_subscription_plan_val == "")
    {
    	var arm_gm_default_selected_dropdown_plan = jQuery("select[name='subscription_plan'] option:selected");
    	arm_gm_default_selected_dropdown_plan = arm_gm_default_selected_dropdown_plan[Object.keys(arm_gm_default_selected_dropdown_plan)[0]];

	   arm_gm_subscription_plan_val = document.getElementsByTagName(arm_gm_default_selected_dropdown_plan.tagName)[0].getAttribute('value');
    }


    if((jQuery(".arm_gm_sub_user_"+arm_gm_default_selected_plan).length == 0 && arm_gm_default_selected_plan != undefined) || (jQuery(".arm_gm_sub_user_"+arm_gm_subscription_plan_val).length == 0) || arm_gm_subscription_plan_val == 0)
	{
		jQuery(".arm_gm_setup_sub_user_selection_main_wrapper").css('display', 'none');
	}
	else
	{
		jQuery(".arm_gm_sub_user_"+arm_gm_default_selected_plan).trigger('change');
	}

    if(jQuery("span").hasClass("arm_selected_child_users"))
    {
        jQuery(".arm_selected_child_users").html('<b>'+jQuery('.arm_gm_hidden_min_members_'+arm_gm_subscription_plan_val).val()+'</b>');
    }

    if(arm_gm_default_selected_plan != undefined){
        jQuery(".arm_gm_sub_user_"+arm_gm_default_selected_plan).css('display', 'block');
    }else{
        jQuery(".arm_gm_sub_user_"+arm_gm_subscription_plan_val).css('display', '');
    }
});
function arm_gm_tax_calculation(arm_gm_plan_amount, arm_gm_selected_country = "")
{
	jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        data: "action=arm_gm_recalculate_plan_amount&arm_gm_plan_amount="+arm_gm_plan_amount+"&arm_gm_selected_country="+arm_gm_selected_country,
        dataType: 'json',
        success: function (response) {
        	jQuery(".arm_payable_amount_text").html(response.toFixed(2));
            jQuery("input[name='arm_total_payable_amount']").val(response.toFixed(2));
        }
    });
}


jQuery(document).on('change', "input.arm_module_plan_input", function () {
	var arm_gm_selected_plan_id = jQuery(this).val();
    if (jQuery('input:radio[name="arm_selected_payment_mode"]').length) {
        jQuery('input:radio[name="arm_selected_payment_mode"]').filter('[value="auto_debit_subscription"]').attr('checked', true).trigger('change');
    }
    armSetupHideShowSections(jQuery(this).parents('.arm_membership_setup_form'));
	jQuery(".arm_gm_setup_sub_user_selection_main_wrapper").css('display', 'none');
    if(jQuery(".arm_gm_sub_user_"+arm_gm_selected_plan_id).length > 0)
	{
		jQuery(".arm_gm_sub_user_"+arm_gm_selected_plan_id).css('display', 'block');
	}
});

jQuery(document).on('click', '.arm_group_membership_child_user_paging_container .arm_page_numbers', function(){
    var arm_gm_page_no = jQuery(this).data('page');
    var arm_gm_per_page = jQuery(this).data('per_page');
    armRefreshChildUsersList(arm_gm_page_no, arm_gm_per_page);
});


jQuery(document).on('click', '.arm_invite_user_button', function(e){
    jQuery(".arm_error_msg_box").removeClass('ng-inactive');
    jQuery(".arm_error_msg_box").addClass('ng-hide');
	jQuery('#arm_gm_form_invite_user_shortcode_modal').bPopup({
	    opacity: 0.5,
	    closeClass: 'popup_close_btn',
	    follow: [false, false],
	});
});


jQuery(document).on('click', '.arm_user_group_membership_list_table .arm_click_to_copy_text', function () {
    var code = jQuery(this).attr('data-code');
    var isSuccess = armCopyToClipboard(code);
    if (!isSuccess) {
        var $this = jQuery(this).parent('.arm_form_shortcode_box').find('.armCopyText');
        var $thisHover = jQuery(this).parent('.arm_form_shortcode_box').find('.arm_click_to_copy_text');
        var $input = jQuery('<input type=text>');
        $input.prop('value', code);
        $input.prop('readonly', true);
        $input.insertAfter($this);
        $input.focus();
        $input.select();
        $this.hide();
        $thisHover.hide();
        $input.focusout(function () {
            $this.show();
            $thisHover.removeAttr('style');
            $input.remove();
        });
    } else {
        jQuery(this).parent('.arm_form_shortcode_box').find('.arm_copied_text').show().delay(3000).fadeOut();
    }
});
/*
	Inherit parent controller of ARMember Form and create new controller and assign new function to new controller.
*/
ARMApp.controller('ARMCtrl2', ['$scope', '$controller', function($scope, $controller){
	$scope.arm_gm_change_plan_price = function(element)
	{
        var arm_gm_form = jQuery(".arm_selected_payment_mode").parents('form:first');
        var arm_gm_form_id = '#'+arm_gm_form.attr('id')+' ';
        armResetCouponCode(arm_gm_form);
        var arm_gm_plan_id = jQuery('[name=subscription_plan]').val();
        if(arm_gm_plan_id == "")
        {
            var arm_gm_default_selected_dropdown_plan = jQuery("select[name='subscription_plan'] option:selected");
            arm_gm_default_selected_dropdown_plan = arm_gm_default_selected_dropdown_plan[Object.keys(arm_gm_default_selected_dropdown_plan)[0]];

           arm_gm_plan_id = document.getElementsByTagName(arm_gm_default_selected_dropdown_plan.tagName)[0].getAttribute('value');
        }

        var arm_gm_min_members = parseFloat(jQuery('select[name=gm_sub_user_select_'+arm_gm_plan_id+'] option:first').val());

        var arm_gm_selected_user_val = parseFloat(element.gm_sub_user_select);
        var arm_gm_plan_amount = parseFloat(jQuery(".arm_setup_summary_text .arm_plan_amount_text").html());

        if(!Number.isNaN(arm_gm_plan_amount))
        {
            var arm_gm_per_user_plan_amount = arm_gm_plan_amount / arm_gm_min_members;

            var arm_gm_discount_amount = parseFloat(jQuery(".arm_discount_amount_text").html().substr(1));
            var arm_gm_discounted_amount = ((arm_gm_per_user_plan_amount) * arm_gm_selected_user_val);
        
        arm_gm_tax_calculation(arm_gm_discounted_amount);


            if(jQuery("span").hasClass("arm_selected_child_users"))
            {
                jQuery(".arm_selected_child_users").html('<b>'+arm_gm_selected_user_val+'</b>');
            }
        }
	}
}]);




function armRefreshChildUsersList(page_no, per_page)
{
    var arm_gm_page_no = page_no;
    var arm_gm_per_page = per_page;
    var arm_gm_invite_form_data = jQuery(".arm_invite_form_id").serialize();
    jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        data: "action=arm_gm_invite_code_pagination&pagination_type=child_users&arm_gm_page_no="+arm_gm_page_no+"&arm_gm_per_page="+arm_gm_per_page+'&'+arm_gm_invite_form_data,
        beforeSend: function () {
            jQuery(".gm_parent_wrapper_container").css('opacity', '0.4');
        },
        success: function (response) {
            var arm_gm_response_content = JSON.parse(response);
            jQuery(".arm_gm_child_user_list_tbody").empty();
            jQuery(".arm_group_membership_child_user_paging_container").empty();
            jQuery(".arm_gm_child_user_list_tbody").append(arm_gm_response_content.arm_gm_tbody_content);
            jQuery(".arm_group_membership_child_user_paging_container").append(arm_gm_response_content.arm_gm_paging_content);
            jQuery(".gm_parent_wrapper_container").css('opacity', '1');
            if(arm_gm_response_content.arm_gm_is_hide){
                jQuery(".arm_invite_user_button").css('display', 'none');
            }else{
                jQuery(".arm_invite_user_button").css('display', 'block');
            }
        }
    });
}


function armKeyPressSubmitForm(obj, e)
{
    if(e.which == 13)
    {
        arminviteSubmitBtnClick(obj);
    }
}


function arminviteSubmitBtnClick(obj)
{
    var email_value = jQuery("#arm_email_label").val();
    if('' == email_value)
    {
        jQuery(".arm_error_msg_box").addClass('ng-inactive');
        jQuery(".arm_error_msg_box").removeClass('ng-hide');
        var err_msg = jQuery("#arm_email_label").attr("data-msg-required");
        var msg_content = '<div data-ng-message="required" class="arm_error_msg md-input-message-animation ng-scope" style="opacity: 1; margin-top: 0px;"><div class="arm_error_box_arrow"></div>'+err_msg+'</div>';
        jQuery("#arm_email_label").parents(".arm_form_input_container_email_label").find('.arm_error_msg_box').html(msg_content);
        jQuery("#arm_email_label").trigger('blur');
    }
    else
    {
        var emails = email_value.split(',');
        var invalidEmails = [];
        for (var i = 0; i < emails.length; i++) { 
            if(!gm_validateEmail(emails[i].trim())) {
              invalidEmails.push(emails[i].trim())
            }
        }
        if(invalidEmails.length > 0) { 
            var err_msg = jQuery("#arm_email_label").attr("data-arm_min_len_msg");
            var msg_content = '<div data-ng-message="required" class="arm_error_msg md-input-message-animation ng-scope" style="opacity: 1; margin-top: 0px;"><div class="arm_error_box_arrow"></div>'+err_msg+'</div>';
            jQuery("#arm_email_label").parents(".arm_form_input_container").find('.arm_error_msg_box').html(msg_content);
            jQuery(".arm_error_msg_box").fadeIn("slow");
        } else {
            jQuery("#arm_email_label").parents(".arm_form_input_container").find('.arm_error_msg_box').html("");
            jQuery(".arm_error_msg_box").fadeOut("slow");

            var arm_gm_page_no = jQuery(obj).data('current_page');
            var arm_gm_per_page = jQuery(obj).data('per_page');

            jQuery.ajax({
                type: "POST",
                url: __ARMAJAXURL,
                data: 'action=arm_gm_invite_users&invited_emails='+emails+'&arm_gm_page_no='+arm_gm_page_no+'&arm_gm_per_page='+arm_gm_per_page,
                beforeSend: function () {
                    jQuery("#arm_invite_invite_submit").addClass('active');
                    jQuery("#arm_invite_invite_submit").attr('disabled', 'disabled');
                    jQuery(".gm_parent_wrapper_container").css('opacity', '0.4');
                },
                success: function(response)
                {
                    jQuery(".gm_parent_wrapper_container").css('opacity', '1');
                    jQuery("#arm_invite_invite_submit").removeAttr('disabled', 'disabled');
                    jQuery("#arm_invite_invite_submit").removeClass('active');
                    jQuery("#arm_email_label").val('');
                    var response_data = JSON.parse(response);
                    jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_form_message_container .arm_message_div").html('<ul><li>'+response_data.message+'</li></ul>');
                    if(response_data.status == "success")
                    {
                        jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_form_message_container .arm_message_div").removeClass('arm_error_msg');
                        jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_form_message_container .arm_message_div").addClass('arm_success_msg');
                    }
                    else
                    {
                        jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_form_message_container .arm_message_div").removeClass('arm_success_msg');
                        jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_form_message_container .arm_message_div").addClass('arm_error_msg');
                    }

                    jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_form_message_container").css('display', 'block');

                    setTimeout(function(){
                        jQuery("#arm_gm_form_invite_user_shortcode_modal .arm_form_message_container").css('display', 'none');
                        if(response_data.status == "success"){ 
                            armRefreshChildUsersList(arm_gm_page_no, arm_gm_per_page);
                            jQuery(".arm_popup_close_btn").trigger('click');
                        }
                    }, 3000);
                }
            });
        }
    }
}


function gm_validateEmail(email) {
    var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
    return re.test(email);
}



var is_error = 0;

function arm_gm_validate_field_len(obj) {
    if('' == obj.value) {
        jQuery(".arm_error_msg_box").addClass('ng-inactive');
        jQuery(".arm_error_msg_box").removeClass('ng-hide');
        var err_msg = jQuery(obj).attr("data-msg-required");
        var msg_content = '<div data-ng-message="required" class="arm_error_msg md-input-message-animation ng-scope" style="opacity: 1; margin-top: 0px;"><div class="arm_error_box_arrow"></div>'+err_msg+'</div>';
        jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html(msg_content);
        jQuery(".arm_error_msg_box").fadeIn("slow");
        is_error = 1;
        return false;
    } else {
        var emails = obj.value.split(',');
        var invalidEmails = [];
        for (var i = 0; i < emails.length; i++) { 
            if(!gm_validateEmail(emails[i].trim())) {
              invalidEmails.push(emails[i].trim())
            }
        }
        if(invalidEmails.length > 0) { 
            var err_msg = jQuery(obj).attr("data-arm_min_len_msg");
            var msg_content = '<div data-ng-message="required" class="arm_error_msg md-input-message-animation ng-scope" style="opacity: 1; margin-top: 0px;"><div class="arm_error_box_arrow"></div>'+err_msg+'</div>';
            jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html(msg_content);
            jQuery(".arm_error_msg_box").fadeIn("slow");
            is_error = 1;
            return false;
        } else {
            jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html("");
            jQuery(".arm_error_msg_box").fadeOut("slow");
            is_error = 0;
            return true;
        }    
    }   
}



jQuery(document).on('click', '.arm_delete_user_button', function(e){
    var delete_cnt = jQuery(this).data('cnt');
    var arm_gm_confirmation = confirm(__ChildDeleteText);
    if(arm_gm_confirmation)
    {
        var delete_email = jQuery(this).data('delete_email');
        var arm_gm_page_no = jQuery(this).data('page_no');
        var arm_gm_per_page = jQuery(this).data('per_page');
        var arm_gm_coupon_id = jQuery(this).data('coupon_id');
        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            data: "action=arm_gm_delete_member&delete_email="+delete_email+"&arm_gm_coupon_id="+arm_gm_coupon_id+"&arm_gm_page_no="+arm_gm_page_no+"&arm_gm_per_page="+arm_gm_per_page,
            beforeSend: function () {
                jQuery(".gm_parent_wrapper_container").css('opacity', '0.4');
            },
            success: function (response) {
                armRefreshChildUsersList(arm_gm_page_no, arm_gm_per_page);
                setTimeout(function(){ jQuery(".arm_gm_delete_user_msg").css('display', 'block'); }, 500);
                setTimeout(function(){ jQuery(".arm_gm_delete_user_msg").css('display', 'none'); }, 5000);
            }
        });
    }
});

jQuery(document).on('click', '.armcancel', function(e){
    var delete_cnt = jQuery(this).data('cnt');
    jQuery(".arm_confirm_box_"+delete_cnt).fadeOut("slow");  
});



jQuery(document).on('click', '.arm_gm_refresh_coupon_code', function(){
    var arm_gm_confirmation = confirm(__CodeRefreshText);
    if(arm_gm_confirmation)
    {
        var arm_gm_coupon_id = jQuery(this).data('coupon_id');
        var arm_gm_page_no = jQuery(this).data('page');
        var arm_gm_per_page = jQuery(this).data('per_page');
        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            data: "action=arm_gm_update_coupon_code&arm_gm_coupon_id="+arm_gm_coupon_id+"&arm_gm_page_no="+arm_gm_page_no+"&arm_gm_per_page="+arm_gm_per_page,
            beforeSend: function () {
                jQuery(".gm_parent_wrapper_container").css('opacity', '0.4');
            },
            success: function(response)
            {
                armRefreshChildUsersList(arm_gm_page_no, arm_gm_per_page);
                setTimeout(function(){ jQuery(".arm_gm_refresh_invite_code_msg").css('display', 'block'); }, 500);
                setTimeout(function(){
                    jQuery(".arm_gm_refresh_invite_code_msg").css('display', 'none');
                }, 5000);
            }
        });
    }
});


jQuery(document).on('click', '.arm_gm_resend_email_button', function(e){
    var arm_gm_confirmation = confirm(__CodeResendText);
    if(arm_gm_confirmation)
    {
        var arm_gm_resend_email = jQuery(this).data('resend_email');
        var arm_gm_coupon_id = jQuery(this).data('coupon_id');
        var arm_gm_page_no = jQuery(this).data('page');
        var arm_gm_per_page = jQuery(this).data('per_page');

        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            data: "action=arm_gm_resend_email&arm_gm_coupon_id="+arm_gm_coupon_id+"&arm_gm_resend_email="+arm_gm_resend_email+"&arm_gm_page_no="+arm_gm_page_no+"&arm_gm_per_page="+arm_gm_per_page,
            beforeSend: function () {
                jQuery(".gm_parent_wrapper_container").css('opacity', '0.4');
            },
            success: function(response)
            {
                armRefreshChildUsersList(arm_gm_page_no, arm_gm_per_page);
                setTimeout(function(){ jQuery(".arm_gm_resend_email_msg").css('display', 'block'); }, 500);
                setTimeout(function(){
                    jQuery(".arm_gm_resend_email_msg").css('display', 'none');
                }, 5000);
            }
        });
    }
});


jQuery(document).on('focus', '.invite_email', function(e){
    jQuery(this).parent().addClass('md-input-focused');
});

jQuery(document).on('focusout', '.invite_email', function(e){
    jQuery(this).parent().removeClass('md-input-focused');
});