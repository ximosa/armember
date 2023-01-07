
jQuery(document).on('click', '.arm_add_direct_login', function() {

    if (jQuery.isFunction(jQuery().autocomplete))
    {
        
        if(jQuery("#arm_direct_logins_user_id").length > 0){
            
            jQuery('#arm_direct_logins_user_id').autocomplete({
                minLength: 0,
                delay: 500,
                appendTo: ".arm_auto_user_field",
                source: function (request, response) {

                    jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        dataType: 'json',
                        data: "action=get_direct_logins_member_list&txt="+request.term,
                        beforeSend: function () {},
                        success: function (res) {
                            jQuery('#arm_direct_logins_users_items').html('');
                            response(res.data);
                        }
                    });
                },
                focus: function() {return false;},
                select: function(event, ui) {
                    var itemData = ui.item;
                    jQuery("#arm_direct_logins_user_id").val('');
                    if(jQuery('#arm_direct_logins_users_items .arm_direct_logins_users_itembox_'+itemData.id).length > 0) {
                    } else {
                        var itemHtml = '<div class="arm_direct_logins_users_itembox arm_direct_logins_users_itembox_'+itemData.id+'">';
                        itemHtml += '<input type="hidden" id="arm_direct_logins_user_id_hidden" name="arm_direct_logins_user_id_hidden" value="'+itemData.id+'"/>';
                        //itemHtml += '<label>'+itemData.label+'<span class="arm_remove_user_selected_itembox">x</span></label>';
                        itemHtml += '</div>';
                        jQuery("#arm_direct_logins_users_items").html(itemHtml);
                        jQuery("#arm_direct_logins_user_id").val(itemData.label);
                    }
                    jQuery('#arm_direct_logins_users_items').hide();
                    return false;
                },
            }).data('uiAutocomplete')._renderItem = function (ul, item) {
                var itemClass = 'ui-menu-item';
                if(jQuery('#arm_direct_logins_users_items .arm_direct_logins_users_itembox_'+item.id).length > 0) {
                    itemClass += ' ui-menu-item-selected';
                }
                var itemHtml = '<li class="'+itemClass+'" data-value="'+item.value+'" data-id="'+item.id+'" ><a>' + item.label + '</a></li>';
                return jQuery(itemHtml).appendTo(ul);
            };
        }
    }

    jQuery('.arm_add_new_direct_logins').bPopup({
	opacity: 0.5,
	closeClass: 'popup_close_btn',
	follow: [false, false]
    }); 

});
jQuery(document).on('click', '.add_add_direct_login_close_btn', function () {
    jQuery('.arm_add_new_direct_logins').bPopup().close();
});

function arm_direct_logins_edit(arm_dl_user_id, arm_dl_username){
    jQuery('#arm_direct_logins_edit_user_id').val(arm_dl_user_id);
    jQuery('.arm_dl_set_username').html(arm_dl_username);
    
    jQuery('.arm_edit_direct_logins').bPopup({
	opacity: 0.5,
	closeClass: 'popup_close_btn',
	follow: [false, false]
    }); 
}

jQuery(document).on('click', '.arm_edit_direct_login_close_btn', function () {
    jQuery('.arm_edit_direct_logins').bPopup().close();
});

jQuery(document).on('change', "input[name='arm_direct_logins_user_type']", function () {
    if(jQuery(this).val() === 'new_user')
    {
        jQuery('.exists_user_section').slideUp('normal', function(){
            jQuery('.new_user_section').slideDown('normal');
        });
    }
    else if(jQuery(this).val() === 'exists_user')
    {
        jQuery('.new_user_section').slideUp('normal', function(){
            jQuery('.exists_user_section').slideDown('normal');
        });        
    }
});

jQuery(document).on('click', '.arm_add_direct_logins_submit', function () {
    var arm_direct_logins_data = jQuery('#arm_add_direct_logins_wrapper_frm').serialize();
    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        var arm_dl_user_type = jQuery("input[name='arm_direct_logins_user_type']:checked").val();
        
        var error_count = 0;
        if( arm_dl_user_type === 'exists_user' )
        {   
            if (jQuery('#arm_direct_logins_user_id').val() == '' && jQuery('#arm_direct_logins_user_id_hidden').val()== '') {
                jQuery('#arm_user_ids_select_chosen').css('border-color', '#ff0000');
                jQuery('#arm_user_ids_error').show();
                error_count++;
            }
            else
            {
                jQuery('#arm_user_ids_select_chosen').css('border-color', '');
                jQuery('#arm_user_ids_error').hide();
            }
        }
        else
        {
            var arm_dl_email = jQuery('#arm_direct_logins_email').val();
            
            if (arm_dl_email == '') {
                jQuery('#arm_direct_logins_email_invalid_error').hide();
                jQuery('#arm_direct_logins_email').css('border-color', '#ff0000');
                jQuery('#arm_direct_logins_email_error').show();
                error_count++;
            } else {
                jQuery('#arm_direct_logins_email_error').hide();
                if(arm_dl_email.match(/^\w+@[a-zA-Z_]+?\.[a-zA-Z]{2,3}$/)){
                    jQuery('#arm_direct_logins_email').css('border-color', '');
                    jQuery('#arm_direct_logins_email_invalid_error').hide();
                    jQuery('#arm_direct_logins_email_error').hide();
                } else {
                    jQuery('#arm_direct_logins_email_invalid_error').show();
                    jQuery('#arm_direct_logins_email').css('border-color', '#ff0000');
                    error_count++;
                }
            }
        }
        
        
        if(error_count > 0) {
            return false;
        } else {
            $this.addClass('arm_already_clicked');
            $this.attr('disabled', 'disabled');
            jQuery('#arm_loader_img').show();
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: 'action=arm_direct_logins_save&' + arm_direct_logins_data,
                success: function (response)
                {
                    if (response.type == 'success')
                    {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                        jQuery('#arm_edit_direct_logins_wrapper_frm')[0].reset();
                        jQuery('#arm_add_direct_logins_wrapper_frm')[0].reset();
                        jQuery('.arm_add_new_direct_logins').bPopup().close();
                        //jQuery('#arm_direct_logins_user_id').val('Select user');
                        jQuery('#arm_direct_logins_hours').val('1');
                        jQuery('#arm_direct_logins_days').val('1');
                        jQuery('#arm_direct_logins_role').val('administrator');
                        
                        arm_icheck_init();
                        arm_selectbox_init();
                        arm_tipso_init();
                        jQuery('.exists_user_section').slideUp();
                        jQuery('.new_user_section').slideDown();
                        arm_load_direct_logins_grid_after_filtered(msg);
                        
                    } else if(response.type == 'msg') {
                        jQuery('#arm_direct_logins_email').css('border-color', '#ff0000');
                        jQuery('#arm_direct_logins_email_error').hide();
                        jQuery('#arm_direct_logins_email_invalid_error').hide();
                        jQuery('#arm_direct_logins_email_exists_error').show();
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

jQuery(document).on('click', '.arm_edit_direct_logins_submit', function () {
    var arm_direct_logins_data = jQuery('#arm_edit_direct_logins_wrapper_frm').serialize();
    var $this = jQuery(this);
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (!$this.hasClass('arm_already_clicked')) {
        var arm_dl_user_type = jQuery("input[name='arm_direct_logins_user_type']:checked").val();
        
        var error_count = 0;
        if( arm_dl_user_type === 'exists_user' )
        {   
            if (jQuery('#arm_direct_logins_edit_user_id').val() == '') {
                error_count++;
            }
        }
        
        if(error_count > 0) {
            return false;
        } else {
            $this.addClass('arm_already_clicked');
            $this.attr('disabled', 'disabled');
            jQuery('#arm_loader_img').show();
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: 'action=arm_direct_logins_save&' + arm_direct_logins_data,
                success: function (response)
                {
                    if (response.type == 'success')
                    {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                        jQuery('#arm_edit_direct_logins_wrapper_frm')[0].reset();
                        jQuery('#arm_add_direct_logins_wrapper_frm')[0].reset();
                        jQuery('.arm_edit_direct_logins').bPopup().close();
                        jQuery('#arm_direct_logins_edit_user_id').val('');
                        jQuery('#arm_direct_logins_hours').val('1');
                        jQuery('#arm_direct_logins_days').val('1');
                        
                        arm_icheck_init();
                        arm_selectbox_init();
                        arm_tipso_init();
                        arm_load_direct_logins_grid_after_filtered(msg);
                    } else if(response.type == 'msg') {
                        jQuery('#arm_direct_logins_email').css('border-color', '#ff0000');
                        jQuery('#arm_direct_logins_email_error').hide();
                        jQuery('#arm_direct_logins_email_invalid_error').hide();
                        jQuery('#arm_direct_logins_email_exists_error').show();
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

jQuery(document).on('click','.arm_direct_logins_active_switch', function () {
    var user_id = jQuery(this).attr('data-item_id');
    var dl_status = 0;
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (jQuery(this).is(':checked')) {
	var dl_status = 1;
    }
    if (user_id == '' && dl_status == '') {
	return false;
    }
    jQuery(this).parents('.armswitch').find('.arm_status_loader_img').show();
    jQuery.ajax({
	type: "POST",
	url: ajaxurl,
	dataType: 'json',
	data: {action: "arm_direct_logins_update_status", arm_dl_user_id: user_id, arm_dl_status: dl_status, _wpnonce: _wpnonce},
	success: function (res) {
	    if (res.type != 'success') {
		armToast(res.msg, 'error');
	    } else {   
                var dl_user_id = res.user_id;
                if(res.dl_status === '1')
                {
                    jQuery('.link_status_'+dl_user_id).removeClass('color_orenge');
                    jQuery('.link_status_'+dl_user_id).addClass('color_green');
                }
                else
                {
                    jQuery('.link_status_'+dl_user_id).removeClass('color_green');
                    jQuery('.link_status_'+dl_user_id).addClass('color_orenge');
                }
                jQuery('.link_status_'+dl_user_id+' span').html(res.dl_status_inword);
            }
            
	    jQuery('.arm_status_loader_img').hide();
	}
    });
});

jQuery(document).on('click', '.arm_direct_logins_delete_btn', function () {
    var user_id = jQuery(this).attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (user_id != '' && user_id != 0) {
	jQuery.ajax({
	    type: "POST",
	    url: ajaxurl,
	    dataType: 'json',
	    data: "action=arm_direct_login_remove&user_id=" + user_id + "&_wpnonce=" + _wpnonce,
	    success: function (res) {
		if (res.type == 'success') {
		    armToast(res.msg, 'success');
		    arm_load_direct_logins_grid_after_filtered();
		} else {
		    armToast(res.msg, 'error');
		}
	    }
	});
    }
    hideConfirmBoxCallback();
    return false;
});

function showConfirmBoxCallback(item_id) {
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

function checkNumber(e) {
    if (!((e.keyCode > 95 && e.keyCode < 106)
	|| (e.keyCode > 47 && e.keyCode < 58)
	|| (e.keyCode >= 35 && e.keyCode <= 40)
	|| e.keyCode == 46
	|| e.keyCode == 8
	|| e.keyCode == 9)) {
	return false;
    }
}

function arm_tipso_init(){
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

jQuery(document).ready(function(){
    arm_icheck_init();
    arm_selectbox_init();
    arm_tipso_init();
    
    jQuery('.arm_direct_logins_form_role').find('dt').css('width','328px');
    jQuery('.arm_direct_logins_form_hours').find('dt').css('width','145px');
    jQuery('.arm_direct_logins_form_days').find('dt').css('width','145px');
    
    if (jQuery.isFunction(jQuery().chosen)) {
	jQuery(".arm_chosen_selectbox").chosen({
	    no_results_text: "Oops, nothing found."
	});
    }
    
    jQuery("#cb-select-all-1").click(function () {
        jQuery('input[name="item-action[]"]').attr('checked', this.checked);
    });
    jQuery('input[name="item-action[]"]').click(function () {
        if (jQuery('input[name="item-action[]"]').length == jQuery('input[name="item-action[]"]:checked').length) {
            jQuery("#cb-select-all-1").attr("checked", "checked");
        } else {
            jQuery("#cb-select-all-1").removeAttr("checked");
        }
    });
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

function arm_icheck_init() {    
    if (jQuery.isFunction(jQuery().iCheck))
    {
	jQuery('.arm_icheckbox').iCheck({
	    checkboxClass: 'icheckbox_minimal-red',
	    radioClass: 'iradio_minimal-red',
	    increaseArea: '20%',
	    disabledClass: '',
	});
	jQuery('.arm_icheckbox').on('ifChanged', function (event) {
	    jQuery(this).trigger('change');
	});
	jQuery('.arm_icheckbox').on('ifClicked', function (event) {
	    jQuery(this).trigger('click');
	});
	jQuery('.arm_iradio').iCheck({
	    checkboxClass: 'icheckbox_minimal-red',
	    radioClass: 'iradio_minimal-red',
	    increaseArea: '20%',
	    disabledClass: '',
	});
	jQuery('.arm_iradio').on('ifChanged', function (event) {
	    jQuery(this).trigger('change');
	});
	jQuery('.arm_iradio').on('ifClicked', function (event) {
	    jQuery(this).trigger('click');
	});
    }
}

function arm_selectbox_init() {
    jQuery('.arm_selectbox').each(function () {
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
    jQuery('.arm_multiple_selectbox').each(function () {
	var $dl = jQuery(this);
	var $ul = $dl.find('dd ul');
	var input_id = $ul.attr('data-id');
	var placeholder = $ul.attr('data-placeholder');
	var value = jQuery('#' + input_id).val();
	var $newText = [];
	var $oldValue = [];
	if (value != '' && value != undefined) {
	    $oldValue = value.split(",");
	    $ul.find('li').each(function () {
		var $thisText = jQuery(this).text();
		var $optVal = jQuery(this).attr('data-value');
		var $optType = jQuery(this).attr('data-type');
		if (jQuery.inArray($optVal, $oldValue) != -1) {
		    jQuery(this).find('input[type="checkbox"]').iCheck('check');
		    $newText.push($thisText);
		}
	    });
	    if ($newText != '') {
		$dl.find('dt span').text($newText.join(', '));
		$dl.find('dt input').val($newText.join(', '));
	    }
	} else {
	    $dl.find('dt span').text(placeholder);
	}
    });
}

jQuery(document).on('click', '.arm_selectbox, .arm_multiple_selectbox', function () {
    jQuery(this).find('dd ul').toggle();
});
jQuery(document).on('click', '.arm_selectbox dt, .arm_multiple_selectbox dt', function (e) {
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
jQuery(document).on('keyup', '.arm_selectbox dt input, .arm_multiple_selectbox dt input', function (e) {
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
jQuery(document).on('keyup','.arm_selectbox dt input.arm_payment_transaction_users',function(e){
	var excludeKeys = [16, 17, 18, 19, 20, 33, 34, 35, 36, 37, 38, 39, 40, 45, 91, 92, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 144, 145];
	if( jQuery.inArray(e.keyCode,excludeKeys) === -1 ){
		var value = jQuery(this).val();
		value = value.toLowerCase();
		var lists = jQuery('#arm_all_users').html();
		var object = jQuery.parseJSON(lists);
		jQuery('.arm_payment_members_list').remove();
		var select_user_label = typeof __SELECT_USER !== 'undefined' ? __SELECT_USER : 'Type username to select user';
		var html = "<li class='arm_payment_members_list' data-label='"+select_user_label+"' data-value='"+select_user_label+"'> Type username to select user </li>";
		for( var n in object ){
			var obj = object[n];
			var obj_val = obj.user_login;
			obj_val = obj_val.toLowerCase();
			html += ( obj_val.indexOf(value) != -1 ) ? "<li class='arm_payment_members_list' data-label='"+obj_val+"' data-value='"+obj.ID+"' >"+obj_val+"</li>" : "";
		}
		jQuery('ul[data-id="arm_user_id"]').html(html);
	}
});

jQuery(document).on('click', ".arm_selectbox dd ul li:not(.field_inactive)", function (e) {
 
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
    	console.log( frmBGColor );
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
jQuery(document).on('click', ".arm_multiple_selectbox dd ul li:not(.field_inactive)", function (e) {
    e.stopPropagation();
    e.preventDefault();
    var $thisLI = jQuery(this);
    var $selectBox = $thisLI.parents('.arm_multiple_selectbox');
    var $list = $selectBox.find('dd ul');
    var input_id = $list.attr('data-id');
    var placeholder = $list.attr('data-placeholder');
    if ($thisLI.find('input[type="checkbox"]').is(':checked')){
	$thisLI.find('input[type="checkbox"]').iCheck('uncheck');
    } else {
	$thisLI.find('input[type="checkbox"]').iCheck('check');
    }
    var $newText = [];
    var $newVal = [];
    $list.find('li').each(function (i, e) {
	var $thisText = jQuery(this).text();
	var $optVal = jQuery(this).attr('data-value');
	var $optType = jQuery(this).attr('data-type');
	if (jQuery(this).find('input[type="checkbox"]').is(':checked')) {
	    $newText.push($thisText);
	    $newVal.push($optVal);
	}
    });
    if ($newText != '') {
	$selectBox.find('dt span').text($newText.join(', ')).show();
	$selectBox.find('dt input').val($newText.join(', ')).hide();
    } else {
	$selectBox.find('dt span').text(placeholder).show();
	$selectBox.find('dt input').val('').hide();
    }
    jQuery('input#' + input_id).val($newVal.join(','));
    return false;
});
jQuery(document).bind('click', function (e) {
    var $clicked = jQuery(e.target);
    if (!$clicked.parents().hasClass("arm_selectbox")) {
	jQuery(".arm_selectbox dd ul").hide();
	jQuery('.arm_selectbox dt span').show();
	jQuery('.arm_selectbox dt input').hide();
	jQuery('.arm_autocomplete').each(function () {
	    if (jQuery(this).val() == '') {
		jQuery(this).val(jQuery(this).parent().find('span').html());
	    }
	});
    }
    var multiSelect = jQuery('.arm_multiple_selectbox');
    if (!multiSelect.is($clicked) && multiSelect.has($clicked).length === 0) {
	jQuery(".arm_multiple_selectbox dd ul").hide();
	jQuery('.arm_multiple_selectbox dt span').show();
	jQuery('.arm_multiple_selectbox dt input').hide();
	jQuery('.arm_autocomplete').each(function () {
	    if (jQuery(this).val() == '') {
		jQuery(this).val(jQuery(this).parent().find('span').html());
	    }
	});
    }
});

function load_datepicker() {
    if (jQuery.isFunction(jQuery().datetimepicker)) {
        jQuery('.arm_datepicker').each(function () {
            var $this = jQuery(this);
            var dateToday = new Date();
            var locale = '';
            var curr_form = $this.attr('data-date_field');
            var dateformat = $this.attr('data-dateformat');
            var show_timepicker = $this.attr('data-show_timepicker');
            if (dateformat == '' || typeof dateformat == 'undefined') {
                dateformat = 'DD/MM/YYYY';
            }
            if (show_timepicker != '' && typeof show_timepicker != 'undefined' && show_timepicker == 1) {
                dateformat = dateformat + ' hh:mm A';
            }

            $this.datetimepicker({
                useCurrent: false,
                format: dateformat,
                locale: '',
            }).on("dp.change", function (e) {
                jQuery(this).trigger('input');
            });
        });
    }
}

jQuery(document).on('click', '.arm_dl_click_to_copy_text', function () {
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

function armCopyToClipboard(text)
{
    var textArea = document.createElement("textarea");
    textArea.id = 'armCopyTextarea';
    textArea.style.position = 'fixed';
    textArea.style.top = 0;
    textArea.style.left = 0;
    textArea.style.width = '2em';
    textArea.style.height = '2em';
    textArea.style.padding = 0;
    textArea.style.border = 'none';
    textArea.style.outline = 'none';
    textArea.style.boxShadow = 'none';
    textArea.style.background = 'transparent';
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    document.getElementById("armCopyTextarea").select();
    var successful = false;
    try {
	var successful = document.execCommand('copy');
        armToast('Link Copied', 'success');
    } catch (err) {
    }
    document.body.removeChild(textArea);
    return successful;
}

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
                }
            },
            error: function (response) {
                jQuery('.arm_loading').fadeOut();
            }
        });
    }
    return false;
});