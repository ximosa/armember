jQuery(document).ready(function(){
    arm_tooltip_init();
    arm_tipso_init();
    if (jQuery.isFunction(jQuery().chosen)) {
	jQuery(".arm_chosen_selectbox").chosen({
	    no_results_text: "Oops, nothing found."
	});
    }
});

jQuery(document).on('click', '.arm_zapier_switch', function () {
    var user_action = jQuery(this).attr('data-user_action');
    if (jQuery(this).is(':checked')) {
	jQuery('.arm_zapier_active_' + user_action).each(function () {
	    var inputType = jQuery(this).attr('type');
        var inputClass = jQuery(this).hasClass('arm_selectbox');
        if (inputType == 'checkbox' || inputType == 'radio' || inputType == 'file' || inputClass == true) {
		jQuery(this).removeAttr('disabled');
	    } else {
		jQuery(this).removeAttr('readonly');
	    }
	});
    } else {
	jQuery('.arm_zapier_active_' + user_action).each(function () {
	    var inputType = jQuery(this).attr('type');
        var inputClass = jQuery(this).hasClass('arm_selectbox');
        if (inputType == 'checkbox' || inputType == 'radio' || inputType == 'file' || inputClass == true) {
		jQuery(this).attr('disabled', 'disabled');
	    } else {
		jQuery(this).attr('readonly', 'readonly');
	    }
	});
    }
});

jQuery(document).on('click', '#arm_zapier_settings_btn', function () {
    var arm_zapier_data = jQuery('#arm_zapier_settings').serialize();
    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        var error_count = 0;
        var arm_error_zap_focus='';
        var url_regular_exp = "^(http|https)://";
        
        if (jQuery('#arm_zapier_user_register').is(':checked')) {
            var arm_zapier_user_register_webhook_url = jQuery('#arm_zapier_user_register_webhook_url').val();
            if (arm_zapier_user_register_webhook_url == '') {
                jQuery('#arm_zapier_user_register_webhook_url').css('border-color', '#ff0000');
                jQuery('#arm_zapier_user_register_webhook_url_error').show();
                jQuery('#arm_zapier_user_register_webhook_url_error_invalid').hide();
                
                if(arm_error_zap_focus==''){
                    arm_error_zap_focus='arm_zapier_user_register_webhook_url';
                }
                error_count++;
            }
            else
            {
                jQuery('#arm_zapier_user_register_webhook_url_error').hide();
                if(arm_zapier_user_register_webhook_url.match(url_regular_exp)){
                    jQuery('#arm_zapier_user_register_webhook_url').css('border-color', '');
                    jQuery('#arm_zapier_user_register_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_register_webhook_url_error_invalid').hide();
                } else {
                    jQuery('#arm_zapier_user_register_webhook_url').css('border-color', '#ff0000');
                    jQuery('#arm_zapier_user_register_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_register_webhook_url_error_invalid').show();
                    
                    if(arm_error_zap_focus==''){
                        arm_error_zap_focus='arm_zapier_user_register_webhook_url';
                    }    
                    error_count++;
                }
            }
        }else{
            jQuery('#arm_zapier_user_register_webhook_url_error').hide();
            jQuery('#arm_zapier_user_register_webhook_url_error_invalid').hide();
        }
        
        if (jQuery('#arm_zapier_update_profile').is(':checked')) {
            var arm_zapier_user_profile_webhook_url = jQuery('#arm_zapier_user_profile_webhook_url').val();
            if (arm_zapier_user_profile_webhook_url == '') {
                jQuery('#arm_zapier_user_profile_webhook_url').css('border-color', '#ff0000');
                jQuery('#arm_zapier_user_profile_webhook_url_error').show();
                jQuery('#arm_zapier_user_profile_webhook_url_error_invalid').hide();
                
                if(arm_error_zap_focus==''){
                    arm_error_zap_focus='arm_zapier_user_profile_webhook_url';
                }    
                error_count++;
            }
            else
            {
                jQuery('#arm_zapier_user_profile_webhook_url_error').hide();
                if(arm_zapier_user_profile_webhook_url.match(url_regular_exp)){
                    jQuery('#arm_zapier_user_profile_webhook_url').css('border-color', '');
                    jQuery('#arm_zapier_user_profile_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_profile_webhook_url_error_invalid').hide();
                } else {
                    jQuery('#arm_zapier_user_profile_webhook_url').css('border-color', '#ff0000');
                    jQuery('#arm_zapier_user_profile_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_profile_webhook_url_error_invalid').show();
                    
                    if(arm_error_zap_focus==''){
                        arm_error_zap_focus='arm_zapier_user_profile_webhook_url';
                    }    
                    error_count++;
                }
            }
        }else{
            jQuery('#arm_zapier_user_profile_webhook_url_error').hide();
            jQuery('#arm_zapier_user_profile_webhook_url_error_invalid').hide();
        }
        
        if (jQuery('#arm_zapier_user_renew_plan').is(':checked')) {
            var arm_zapier_user_renew_plan_webhook_url = jQuery('#arm_zapier_user_renew_plan_webhook_url').val();
            if (arm_zapier_user_renew_plan_webhook_url == '') {
                jQuery('#arm_zapier_user_renew_plan_webhook_url').css('border-color', '#ff0000');
                jQuery('#arm_zapier_user_renew_plan_webhook_url_error').show();
                jQuery('#arm_zapier_user_renew_plan_webhook_url_error_invalid').hide();
                
                if(arm_error_zap_focus==''){
                    arm_error_zap_focus='arm_zapier_user_renew_plan_webhook_url';
                }    
                error_count++;
            }
            else
            {
                jQuery('#arm_zapier_user_renew_plan_webhook_url_error').hide();
                if(arm_zapier_user_renew_plan_webhook_url.match(url_regular_exp)){
                    jQuery('#arm_zapier_user_renew_plan_webhook_url').css('border-color', '');
                    jQuery('#arm_zapier_user_renew_plan_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_renew_plan_webhook_url_error_invalid').hide();
                } else {
                    jQuery('#arm_zapier_user_renew_plan_webhook_url').css('border-color', '#ff0000');
                    jQuery('#arm_zapier_user_renew_plan_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_renew_plan_webhook_url_error_invalid').show();
                    
                    if(arm_error_zap_focus==''){
                        arm_error_zap_focus='arm_zapier_user_renew_plan_webhook_url';
                    }    
                    error_count++;
                }
            }
        }else{
            jQuery('#arm_zapier_user_renew_plan_webhook_url_error').hide();
            jQuery('#arm_zapier_user_renew_plan_webhook_url_error_invalid').hide();
        }
        
        if (jQuery('#arm_zapier_user_change_plan').is(':checked')) {
            var arm_zapier_user_change_plan_webhook_url = jQuery('#arm_zapier_user_change_plan_webhook_url').val();
            if (arm_zapier_user_change_plan_webhook_url == '') {
                jQuery('#arm_zapier_user_change_plan_webhook_url').css('border-color', '#ff0000');
                jQuery('#arm_zapier_user_change_plan_webhook_url_error').show();
                jQuery('#arm_zapier_user_change_plan_webhook_url_error_invalid').hide();
                
                if(arm_error_zap_focus==''){
                    arm_error_zap_focus='arm_zapier_user_change_plan_webhook_url';
                }    
                error_count++;
            }
            else
            {
                jQuery('#arm_zapier_user_change_plan_webhook_url_error').hide();
                if(arm_zapier_user_change_plan_webhook_url.match(url_regular_exp)){
                    jQuery('#arm_zapier_user_change_plan_webhook_url').css('border-color', '');
                    jQuery('#arm_zapier_user_change_plan_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_change_plan_webhook_url_error_invalid').hide();
                } else {
                    jQuery('#arm_zapier_user_change_plan_webhook_url').css('border-color', '#ff0000');
                    jQuery('#arm_zapier_user_change_plan_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_change_plan_webhook_url_error_invalid').show();
                    
                    if(arm_error_zap_focus==''){
                        arm_error_zap_focus='arm_zapier_user_change_plan_webhook_url';
                    }    
                    error_count++;
                }
            }
        }else{
            jQuery('#arm_zapier_user_change_plan_webhook_url_error').hide();
            jQuery('#arm_zapier_user_change_plan_webhook_url_error_invalid').hide();
        }
        
        if (jQuery('#arm_zapier_user_delete').is(':checked')) {
            var arm_zapier_user_delete_webhook_url = jQuery('#arm_zapier_user_delete_webhook_url').val();
            if (arm_zapier_user_delete_webhook_url == '') {
                jQuery('#arm_zapier_user_delete_webhook_url').css('border-color', '#ff0000');
                jQuery('#arm_zapier_user_delete_webhook_url_error').show();
                jQuery('#arm_zapier_user_delete_webhook_url_error_invalid').hide();
                
                if(arm_error_zap_focus==''){
                    arm_error_zap_focus='arm_zapier_user_delete_webhook_url';
                }    
                error_count++;
            }
            else
            {
                jQuery('#arm_zapier_user_delete_webhook_url_error').hide();
                if(arm_zapier_user_delete_webhook_url.match(url_regular_exp)){
                    jQuery('#arm_zapier_user_delete_webhook_url').css('border-color', '');
                    jQuery('#arm_zapier_user_delete_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_delete_webhook_url_error_invalid').hide();
                } else {
                    jQuery('#arm_zapier_user_delete_webhook_url').css('border-color', '#ff0000');
                    jQuery('#arm_zapier_user_delete_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_delete_webhook_url_error_invalid').show();
                    
                    if(arm_error_zap_focus==''){
                        arm_error_zap_focus='arm_zapier_user_delete_webhook_url';
                    }    
                    error_count++;
                }
            }
        }else{
            jQuery('#arm_zapier_user_delete_webhook_url_error').hide();
            jQuery('#arm_zapier_user_delete_webhook_url_error_invalid').hide();
        }

        if (jQuery('#arm_zapier_user_cancel_plan').is(':checked')) {
            var arm_zapier_user_cancel_plan_webhook_url = jQuery('#arm_zapier_user_cancel_plan_webhook_url').val();
            if (arm_zapier_user_cancel_plan_webhook_url == '') {
                jQuery('#arm_zapier_user_cancel_plan_webhook_url').css('border-color', '#ff0000');
                jQuery('#arm_zapier_user_cancel_plan_webhook_url_error').show();
                jQuery('#arm_zapier_user_cancel_plan_webhook_url_error_invalid').hide();
                
                if(arm_error_zap_focus==''){
                    arm_error_zap_focus='arm_zapier_user_cancel_plan_webhook_url';
                }    
                error_count++;
            }
            else
            {
                jQuery('#arm_zapier_user_cancel_plan_webhook_url_error').hide();
                if(arm_zapier_user_cancel_plan_webhook_url.match(url_regular_exp)){
                    jQuery('#arm_zapier_user_cancel_plan_webhook_url').css('border-color', '');
                    jQuery('#arm_zapier_user_cancel_plan_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_cancel_plan_webhook_url_error_invalid').hide();
                } else {
                    jQuery('#arm_zapier_user_cancel_plan_webhook_url').css('border-color', '#ff0000');
                    jQuery('#arm_zapier_user_cancel_plan_webhook_url_error').hide();
                    jQuery('#arm_zapier_user_cancel_plan_webhook_url_error_invalid').show();
                    
                    if(arm_error_zap_focus==''){
                        arm_error_zap_focus='arm_zapier_user_cancel_plan_webhook_url';
                    }    
                    error_count++;
                }
            }
        }else{
            jQuery('#arm_zapier_user_cancel_plan_webhook_url_error').hide();
            jQuery('#arm_zapier_user_cancel_plan_webhook_url_error_invalid').hide();
        }

        if (jQuery('#arm_zapier_user_register_zap').is(':checked')) {
            var arm_zapier_action = jQuery('#arm_zapier_action').val();
            var arm_zapier_custom_field_length = jQuery('.arm_zapier_custom_field').length;
            if (arm_zapier_action == '') {
                jQuery('#arm_zapier_action').css('border-color', '#ff0000');
                jQuery('#arm_zapier_action_error').show();
                
                if(arm_error_zap_focus==''){
                    arm_error_zap_focus='arm_zapier_action';
                }    
                error_count++;
            }
            else 
            {
                jQuery('#arm_zapier_action_error').hide();
                jQuery('#arm_zapier_action').css('border-color', '');
            }
            
        }else{
            jQuery('#arm_zapier_action_error').hide();
        }
        if(error_count > 0) {
            if(arm_error_zap_focus!=''){
                jQuery('#'+arm_error_zap_focus).focus();
                jQuery('html, body').animate({  scrollTop: jQuery("#"+arm_error_zap_focus).offset().top - 40  }, 0);
            }
            return false;
        } else {
            $this.addClass('arm_already_clicked');
            $this.attr('disabled', 'disabled');
            jQuery('#arm_loader_img').show();
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: 'action=arm_zapier_save_settings&' + arm_zapier_data,
                success: function (response)
                {
                    if (response.type == 'success')
                    {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                        armToast(msg, 'success');
                        arm_tipso_init();
                    } else {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsError;
                        armToast(msg, 'error');
                    }
                    jQuery('#arm_loader_img').hide();
                    $this.removeAttr('disabled');
                    $this.removeClass('arm_already_clicked');
                }
            });
        }
    }
    return false;
});

function armToast(message, type, time, reload){
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
	if (type != 'error') {
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

function arm_tipso_init() {
    if (jQuery.isFunction(jQuery().tipso)) {
        jQuery('.armhelptip').each(function () {
            jQuery(this).tipso({
                position: 'top',
                size: 'small',
                background: '#939393',
                color: '#ffffff',
                width: false,
                maxWidth: 400,
                useTitle: true
            });
        });
        jQuery('.arm_helptip_icon').each(function () {
            jQuery(this).tipso({
                position: 'top',
                size: 'small',
                background: '#939393',
                color: '#ffffff',
                width: false,
                maxWidth: 400,
                useTitle: true
            });
        });
    }
}
jQuery(document).on("click", ".arm_zapier_custom_field_add_plus_icon", function(e) {

    var arm_zapier_user_register_zap = jQuery('#arm_zapier_user_register_zap').is(':checked');

    if (arm_zapier_user_register_zap == false) {
        return false;
    }
    e.stopPropagation();
    
    var arm_zapier_last_custom_field_div = jQuery(".arm_zapier_custom_fields:last");
    
    arm_zapier_last_custom_field_div.after(jQuery(".arm_zapier_custom_fields:first").prop("outerHTML"));

    var parents_zapier_custom_object = jQuery(this).parents(".arm_zapier_custom_fields_main");
    var parents_zapier_custom_field_object = parents_zapier_custom_object.find(".arm_zapier_custom_fields:last");
    var arm_zapier_custom_field_counter = parents_zapier_custom_object.find('#arm_zapier_custom_field_counter').val();
    var arm_zapier_custom_field_count = parseInt(arm_zapier_custom_field_counter) + 1;
    
    arm_zapier_custom_field_counter = parents_zapier_custom_object.find('#arm_zapier_custom_field_counter').val(arm_zapier_custom_field_count);
    
    parents_zapier_custom_field_object.find('.arm_zapier_custom_field').attr('id','arm_zapier_custom_field_'+arm_zapier_custom_field_count);
    
    return false;
});
jQuery(document).on("click", ".arm_zapier_custom_field_minus_icon", function(e) {

    var arm_zapier_user_register_zap = jQuery('#arm_zapier_user_register_zap').is(':checked');

    if (arm_zapier_user_register_zap == false) {
        return false;
    }
    
    e.stopPropagation();

    if( jQuery(".arm_zapier_custom_fields").length == 1 ) {
        alert('Minimum one required');
        return false;
    }
    
    var parents_zapier_custom_object = jQuery(this).parents(".arm_zapier_custom_fields_main");
    var arm_zapier_custom_field_counter = parents_zapier_custom_object.find('#arm_zapier_custom_field_counter').val();
    var arm_zapier_custom_field_count = parseInt(arm_zapier_custom_field_counter) - 1;
    arm_zapier_custom_field_counter = parents_zapier_custom_object.find('#arm_zapier_custom_field_counter').val(arm_zapier_custom_field_count);
    jQuery(this).parents(".arm_zapier_custom_fields_main div.arm_zapier_custom_fields").remove();
    
    jQuery(".tipso_bubble").remove();
    return false;
});
function arm_tooltip_init() {
    if (jQuery.isFunction(jQuery().tipso))
    {
        jQuery('.arm_helptip_icon').each(function () {
            jQuery(this).tipso({
                position: 'top',
                size: 'small',
                tooltipHover: true,
                background: '#939393',
                color: '#ffffff',
                width: false,
                maxWidth: 400,
                useTitle: true
            });
        });
        
    }
}