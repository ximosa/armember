jQuery(document).ready(function(){
    arm_tipso_init();
    arm_selectbox_init();
    load_datepicker();
    arm_icheck_init();
    
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
    
    var permission_type = jQuery('#arm_item_permission_type').val();
    jQuery('.permission').hide();
    jQuery('.permission_' + permission_type).show();
    
    if (jQuery.isFunction(jQuery().chosen)) {
	jQuery(".arm_chosen_selectbox").chosen({
	    no_results_text: "Oops, nothing found."
	});
    }
    
    jQuery('#arm_add_edit_item_form').validate({
        ignore: "",
        errorClass: "error arm_invalid",
        validClass: "valid arm_valid",
        errorPlacement: function (error, element) {
            error.appendTo(element.parents('td'));
        },
        focusInvalid: false,
        invalidHandler: function(form, validator) {
            if (!validator.numberOfInvalids())
            {
                return;
            }else
            {
                jQuery('html, body').animate({
                    scrollTop: jQuery(validator.errorList[0].element).offset().top - 150
                }, 0);
            }
        },
        rules: {
            arm_item_name: 'required',
            arm_item_url: {
            	required: function(){
            		return (jQuery('#arm_item_type_external').is(':checked'));
            	},
                url: true,
            },
            arm_file_no_url: {
            	required: function(){
            		return (jQuery('#arm_item_type_default').is(':checked'));
            	}
            }
        },
        submitHandler: function (form) {
            var $this = jQuery(form);
            jQuery("#arm_dd_file_require_error").hide();
            if (!$this.hasClass('arm_already_clicked')) {
                $this.find('input[type=submit], button[type=submit]').addClass('arm_already_clicked').attr('disabled', 'disabled');
                form.submit();
            }
        }
    });

    jQuery(document).on('change', "#arm_item_type_span input[type=radio]", function () {
        var item_type = jQuery(this).val();
        if(item_type == 'default'){
            jQuery('.download_file').show();
            jQuery('.download_url').hide();
        } else if(item_type == 'external'){
            jQuery('.download_file').hide();
            jQuery('.download_url').show();
        }
    });

    jQuery(document).on('click', '.arm_remove_user_multiauto_selected_itembox', function () {
        jQuery(this).parents('.arm_users_multiauto_itembox').remove();
        if(jQuery('#arm_users_multiauto_items .arm_users_multiauto_itembox').length == 0) {
            jQuery('#arm_dd_items_users_input').attr('required', 'required');
            jQuery('#arm_users_multiauto_items').hide();
        }
        return false;
    });


    if (jQuery.isFunction(jQuery().autocomplete))
    {
        if(jQuery("#arm_dd_items_users_input").length > 0){

            jQuery('#arm_dd_items_users_input').autocomplete({
                minLength: 0,
                delay: 500,
                appendTo: ".arm_multiauto_user_field",
                source: function (request, response) {
                    jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        dataType: 'json',
                        data: "action=get_arm_member_list&txt="+request.term,
                        beforeSend: function () {},
                        success: function (res) {
                            response(res.data);
                        }
                    });
                },
                focus: function() {return false;},
                select: function(event, ui) {
                    var itemData = ui.item;
                    jQuery("#arm_dd_items_users_input").val('');

                    if(jQuery('.arm_users_multiauto_items .arm_users_multiauto_itembox_'+itemData.id).length > 0) {
                    } else {
                        var itemHtml = '<div class="arm_users_multiauto_itembox arm_users_multiauto_itembox_'+itemData.id+'">';
                        itemHtml += '<input type="hidden" name="arm_user_ids['+itemData.id+']" value="'+itemData.id+'"/>';
                        itemHtml += '<label>'+itemData.label+'<span class="arm_remove_user_multiauto_selected_itembox">x</span></label>';
                        itemHtml += '</div>';
                        jQuery("#arm_users_multiauto_items").append(itemHtml);
                        jQuery('#arm_dd_items_users_input').removeAttr('required');
                        if(jQuery("#arm_dd_items_users_input_error").length > 0){
                            jQuery("#arm_dd_items_users_input_error").remove();
                        }
                    }
                    jQuery('#arm_users_multiauto_items').show();
                    return false;
                },
            }).data('uiAutocomplete')._renderItem = function (ul, item) {
                var itemClass = 'ui-menu-item';

                if(jQuery('arm_users_multiauto_items .arm_users_multiauto_itembox_'+item.id).length > 0) {
                    itemClass += ' ui-menu-item-selected';
                }
                var itemHtml = '<li class="'+itemClass+'" data-value="'+item.value+'" data-id="'+item.id+'" ><a>' + item.label + '</a></li>';
                return jQuery(itemHtml).appendTo(ul);
            };
        }
    }

    jQuery(document).on('click', '.arm_download_settings_wrapper .arm_remove_user_multiauto_selected_itembox', function () {
        
        jQuery(this).parents('.arm_users_multiauto_itembox').remove();
        if(jQuery('#arm_users_multiauto_items .arm_users_multiauto_itembox').length == 0) {
            
            jQuery('#arm_users_multiauto_items').hide();
        }
        return false;
    });

    if (jQuery.isFunction(jQuery().autocomplete))
    {
        if(jQuery("#arm_dd_items_block_users_input").length > 0){

            jQuery('#arm_dd_items_block_users_input').autocomplete({
                minLength: 0,
                delay: 500,
                appendTo: ".arm_download_settings_wrapper .arm_multiauto_user_field",
                source: function (request, response) {
                    jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        dataType: 'json',
                        data: "action=get_arm_member_list&txt="+request.term,
                        beforeSend: function () {},
                        success: function (res) {
                            response(res.data);
                        }
                    });
                },
                focus: function() {return false;},
                select: function(event, ui) {
                    var itemData = ui.item;
                    jQuery("#arm_dd_items_block_users_input").val('');

                    if(jQuery('.arm_users_multiauto_items .arm_users_multiauto_itembox_'+itemData.id).length > 0) {
                    } else {
                        var itemHtml = '<div class="arm_users_multiauto_itembox arm_users_multiauto_itembox_'+itemData.id+'">';
                        itemHtml += '<input type="hidden" name="block_users['+itemData.id+']" value="'+itemData.id+'"/>';
                        itemHtml += '<label>'+itemData.label+'<span class="arm_remove_user_multiauto_selected_itembox">x</span></label>';
                        itemHtml += '</div>';
                        jQuery("#arm_users_multiauto_items").append(itemHtml);
                        jQuery('#arm_dd_items_block_users_input').removeAttr('required');
                        if(jQuery("#arm_dd_items_block_users_input_error").length > 0){
                            jQuery("#arm_dd_items_block_users_input_error").remove();
                        }
                    }
                    jQuery('#arm_users_multiauto_items').show();
                    return false;
                },
            }).data('uiAutocomplete')._renderItem = function (ul, item) {
                var itemClass = 'ui-menu-item';

                if(jQuery('arm_users_multiauto_items .arm_users_multiauto_itembox_'+item.id).length > 0) {
                    itemClass += ' ui-menu-item-selected';
                }
                var itemHtml = '<li class="'+itemClass+'" data-value="'+item.value+'" data-id="'+item.id+'" ><a>' + item.label + '</a></li>';
                return jQuery(itemHtml).appendTo(ul);
            };
        }
    }

    jQuery(document).on('click','.arm_dd_generate_shortcode',function (){

        var item_id = jQuery(this).attr('data-id');    
        var item_name = jQuery(this).attr('data-name'); 
        jQuery('#arm_dd_item_id').val(item_id);
        jQuery('#arm_dd_item_name').html(item_name);
        jQuery("form.arm_digital_download_shortcode_form")[0].reset();
        jQuery('.arm_insert_dd_shortcode_main_wrapper').hide();
        jQuery('.add_dd_shortcode_wrapper').bPopup({
            opacity: 0.5,
            closeClass: 'popup_close_btn',
            follow: [false, false],

        });
    });

    jQuery(document).on('click', '.dd_generate_shortcode_close_btn', function () {
        jQuery('.add_dd_shortcode_wrapper').bPopup().close();
    });

    jQuery(document).on('click', ".arm_dd_shortcode_insert_btn", function (e) {

    var shortcode = '', args = '';
    var code = jQuery(this).attr('data-code');

    if (code != '')
    {

        var $ddForm = jQuery('form.arm_digital_download_shortcode_form');
        var $formData = $ddForm.serializeArray();
        
        if ($formData != '') {
        jQuery($formData).each(function (i, e) {

                if(typeof e.name != 'undefined' && e.name=='show_description'){
                    if($ddForm.find('#arm_dd_display_desc_input').is(':checked')){
                        e.value = true; 
                    }else{
                        e.value = false;    
                    }
                }   
                if(typeof e.name != 'undefined' && e.name=='show_size'){
                    if($ddForm.find('#arm_dd_display_files_input').is(':checked')){
                        e.value = true; 
                    }else{
                        e.value = false;    
                    }
                }
                if(typeof e.name != 'undefined' && e.name=='show_download_count'){
                    
                    if($ddForm.find('#arm_dd_display_download_count_input').is(':checked')){
                        e.value = true; 
                    }else{
                        e.value = false;    
                    }
                }
                if(typeof e.name != 'undefined' && e.name=='link_type' && e.value=='link'){

                    e.value = '';

                }
            
            if(typeof e.value != 'undefined' && e.value!=''){
            
                args += ' ' + e.name + '="' + e.value + '"';
            }
            
        });

        shortcode = '[' + code + ' ' + args + ']';
        jQuery('.arm_insert_dd_shortcode_main_wrapper').hide();
        jQuery('.arm_insert_dd_shortcode_main_wrapper').slideDown();
        jQuery('.arm_insert_dd_shortcode').empty().append(shortcode);
        jQuery('.arm_insert_dd_shortcode_wrapper .arm_click_to_copy_text').attr('data-code',shortcode);        
        }
    } else {
        alert('Invalid Shortcode');
    }
    return false;
    });

    jQuery(document).on('click', '.arm_dd_click_to_copy_text', function () {
        var code = jQuery(this).attr('data-code');
        var isSuccess = armDDCopyToClipboard(code,1);
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
});


jQuery(document).on('change', '#arm_item_permission_type', function () {
    var permission_type = jQuery(this).val();
    jQuery('.permission').hide();
    jQuery('.permission_' + permission_type).show();
    jQuery('#arm_users_multiauto_items').empty();
    jQuery('#arm_dd_items_users_input-error').empty();
});

jQuery(document).on('change', "#arm_user_restriction_type input[type=radio]", function () {
    var arm_permission_user_label = jQuery(this).attr('data-label');
    jQuery('.arm_permission_user_label').html(arm_permission_user_label);
});

jQuery(document).on('change', '.arm_item_FileUpload', function() {
    var data = new FormData();

    var file_data = jQuery('input[type="file"]')[0].files; 
    for(var file = 0; file < file_data.length; file++){
        data.append("arm_item_file_"+file, file_data[file]);
    }
    
    data.append('arm_item_no_file', file_data.length);
    data.append('arm_file_no_url', jQuery('#arm_file_no_url').val());
    data.append('action', 'arm_dd_add_item');
    jQuery('#arm_dd_loder').show();
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: data,
        processData: false,
        contentType: false,
        dataType:'json',
        success: function ( response ) {
            if(response.type == 'success')
            {
                jQuery('#file_urls tbody').append(response.content);
                if (jQuery.isFunction(jQuery().tipso)) {
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
                // jQuery('.arm_item_url').val(response.no_file);
                jQuery('.arm_file_no_url').val(response.no_file);
                jQuery('#arm_file_no_url-error').hide();
                
            }
            else
            {
                jQuery('#error_msg').html(response.error_msg);
            }
            jQuery('#arm_dd_loder').hide();
            
        },
        error: function () {
            alert("error in uploading file");
        }
     });
});
jQuery(document).on('click', '.arm_add_item_bulk', function(){ 
    jQuery('.arm_dd_bulk_import_item_popup').bPopup({
            opacity: 0.5,
            follow: [false, false],
            closeClass: 'arm_dd_bulk_import_item_popup_close_btn',
        });
});
jQuery(document).on('click', '.arm_dd_remove_selected_itembox', function () {
    var item_id = jQuery(this).attr('date_item_id');
    var file_name = jQuery(this).attr('data_file_name'); 
    jQuery('.arm_dd_item_' + item_id).remove();
    jQuery('#file_urls').append('<input type="hidden" name="arm_remove_file[]" class="remove_file" id="remove_'+file_name+'" value="'+file_name+'" />');

    
    if(jQuery('#file_urls tbody tr td').length<1){
        jQuery('.arm_dd_upload_file_heading').remove();
        jQuery('#arm_file_no_url').val(''); 

    }
    
});
jQuery(document).on('click', '.arm_dd_item_delete_btn', function() {
    var item_id = jQuery(this).attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (item_id != '' && item_id != 0) {
	jQuery.ajax({
	    type: "POST",
	    url: ajaxurl,
	    dataType: 'json',
	    data: "action=arm_dd_item_ajax_action&act=delete&id=" + item_id + "&_wpnonce=" + _wpnonce,
	    success: function (res) {
		if (res.type == 'success') {
		    armToast(res.msg, 'success');
		    arm_load_item_grid_after_filtered();
		} else {
		    armToast(res.msg, 'error');
		}
	    }
	});
    }
    hideConfirmBoxCallback();
    return false;
});
jQuery(document).on('click','.arm_item_active_switch', function () {
    var item_id = jQuery(this).attr('data-item_id');
    var arm_pro_status = 0;
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (jQuery(this).is(':checked')) {
	var arm_pro_status = 1;
    }
    if (item_id == '' && arm_pro_status == '') {
	return false;
    }
    jQuery(this).parents('.armswitch').find('.arm_status_loader_img').show();
    jQuery.ajax({
	type: "POST",
	url: ajaxurl,
	dataType: 'json',
	data: {action: "arm_dd_item_update_status", arm_item_id: item_id, arm_pro_status: arm_pro_status, _wpnonce : _wpnonce},
	success: function (res) {
	    if (res.type != 'success') {
		armToast(res.msg, 'error');
	    } else { }
	    jQuery('.arm_status_loader_img').hide();
	}
    });
});
function arm_item_bulk_action(id) {
    jQuery('#' + id).val('true');
    jQuery('.popup_close_btn').trigger("click");
    jQuery('#arm_item_list_form').submit();
}
function arm_item_list_form_bulk_action(){
    var action1 = jQuery('[name="action1"]').val();
    var action_delete = jQuery('#bulk_delete_flag').val();
    var chk_count = jQuery('input[name="item-action[]"]:checked').length;
    if (chk_count > 0)
    {
    	if (action1 == '' || action1 == '-1') {
    	    armToast(armbulkActionError, 'error');
    	} else {
            if (action_delete == 'false') {
                jQuery('#delete_bulk_form_message').bPopup({closeClass: 'popup_close_btn'});
                return false;
            }
            jQuery('#bulk_delete_flag').val('false');
            var str = jQuery('#arm_item_list_form').serialize();
            
            jQuery.ajax({type: "POST", url: ajaxurl, data: "action=arm_dd_item_bulk_action&" + str, dataType: 'json',
                success: function (res) {
                    if (res.type == 'success')
                    {
                        armToast(res.msg, 'success');
                        arm_load_item_grid_after_filtered();
                    } else {
                        armToast(res.msg, 'error');
                    }
                }
            });
    	}
    } else {
	   armToast(armpleaseselect, 'error');
    }
    return false;
}

jQuery(document).on('click', '.arm_dd_download_delete_btn', function() {
    var item_id = jQuery(this).attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (item_id != '' && item_id != 0) {
    	jQuery.ajax({
    	    type: "POST",
    	    url: ajaxurl,
    	    dataType: 'json',
    	    data: "action=arm_dd_download_ajax_action&act=delete&id=" + item_id + "&_wpnonce=" + _wpnonce,
    	    success: function (res) {
        		if (res.type == 'success') {
        		    armToast(res.msg, 'success');
        		    arm_load_download_grid_after_filtered();
        		} else {
        		    armToast(res.msg, 'error');
        		}
    	    }
    	});
    }
    hideConfirmBoxCallback();
    return false;
});
function arm_download_bulk_action(id) {
    jQuery('#' + id).val('true');
    jQuery('.popup_close_btn').trigger("click");
    jQuery('#arm_download_list_form').submit();
}
function arm_download_list_form_bulk_action(){
    var action1 = jQuery('[name="action1"]').val();
    var action_delete = jQuery('#bulk_delete_flag').val();
    var chk_count = jQuery('input[name="item-action[]"]:checked').length;
    if (chk_count > 0)
    {
    	if (action1 == '' || action1 == '-1') {
    	    armToast(armbulkActionError, 'error');
    	} else {
            if (action_delete == 'false') {
                jQuery('#delete_bulk_form_message').bPopup({closeClass: 'popup_close_btn'});
                return false;
            }
            
            jQuery('#bulk_delete_flag').val('false');
            var str = jQuery('#arm_download_list_form').serialize();
            
            jQuery.ajax({type: "POST", url: ajaxurl, data: "action=arm_dd_download_bulk_action&" + str, dataType: 'json',
                success: function (res) {
                    if (res.type == 'success')
                    {
                        armToast(res.msg, 'success');
                        arm_load_download_grid_after_filtered();
                    } else {
                        armToast(res.msg, 'error');
                    }
                }
            });
    	}
    } else {
	   armToast(armpleaseselect, 'error');
    }
    return false;
}

jQuery(document).on('click', '#arm_download_settings_btn', function () {
    var download_settings = jQuery('#arm_download_settings').serialize();
    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        var permission_msg = jQuery('#permission_msg').val();
        
        var error_count = 0;
        if (permission_msg == '') {
    	    jQuery('#permission_msg').css('border-color', '#ff0000');
    	    jQuery('#permission_msg_error').show();
    	    jQuery('html, body').animate({
    		scrollTop: jQuery("#permission_msg").offset().top - 40
    	    }, 0);
    	    error_count++;
    	} else {
            jQuery('#permission_msg').css('border-color', '');
            jQuery('#permission_msg_error').hide();
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
                data: 'action=arm_download_settings&' + download_settings,
                success: function (response) {
                    if (response.type == 'success') {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                        armToast(msg, 'success');
                    } else {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsError;
                        armToast(msg, 'error');
                    }
                    jQuery('#arm_loader_img').hide();
                }
            });
        }
    }
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
function showPermissionBoxCallback(item_id) {
    if (item_id != '') {
        var deleteBox = jQuery('#arm_permission_box_' + item_id);
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

jQuery(document).on('click', '.arm_click_to_copy_text', function () {
    var code = jQuery(this).attr('data-code');
    var isSuccess = armDDCopyToClipboard(code,0);
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

function armDDCopyToClipboard(text, msgflag) {
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
        if(msgflag==1)
        {
            armToast('Link Copied', 'success');
        }
    } catch (err) {
    }
    document.body.removeChild(textArea);
    return successful;
}

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

function load_datepicker() {
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
                locale: ''
            });
        });
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

function arm_icheck_init()
{
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




function arm_add_download_form_action() {


    var permission_type = jQuery('#arm_item_permission_type').val();
    var arm_user_restriction_type = jQuery('input[name="arm_user_restriction_type"]:checked').val();
    var selected_users = jQuery('#arm_user_ids_select').val();
    var selected_plans = jQuery('#arm_plans_select').val();
    var selected_roles = jQuery('#arm_roles_select').val();

    var $form = jQuery('#arm_dd_bulk_import_item_form');
    $form.find('.arm_loader_img').fadeIn('slow');
    var form_data = $form.serialize();
    
    jQuery('#arm_user_download_btn').attr('disabled', 'disabled');

    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        dataType: 'json',
        data: "action=arm_dd_handle_import_download&" + form_data,
        success: function (response) {
            $form.find('.arm_loader_img').fadeOut();
            if (response.type == 'success') {

                console.log(response.message);
            } else {
                alert(response.message);
            }
            jQuery('#arm_user_download_btn').removeAttr('disabled');
        }
    });
     
    return false;
}






jQuery(document).ready(function ($) {
    function RandomString(limit) {
    var size = (limit != undefined && limit != 0) ? limit : 5;
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for (var i = 0; i < size; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }
    return text;
    }

    function DownloadSelectHandler(e) {
    
    var files = e.target.files || e.dataTransfer.files;
    
    for (var i = 0, f; f = files[i]; i++) {
        UploadDownload(f, e.target);
    }
    }
    
    function UploadDownload(file, input)
    {
    var $uploader = jQuery(input);
    var $targetDiv = jQuery(input).parents('.arm_dd_bulk_import_item_form');
    var $uploaderClass = $uploader.attr('class');
    var acceptSizeTxt = $uploader.attr('data-file_size');
    var acceptSize = acceptSizeTxt * 1024 * 1024;
    var denyfileext = ["php", "php3", "php4", "php5", "pl", "py", "jsp", "asp", "exe", "cgi"];
    var xhr = new XMLHttpRequest();
    var file_type = file.type;
    var file_index = file.name.lastIndexOf('.');
    var file_extension = file.name.substring(file_index + 1);
    file_extension = file_extension.toLowerCase();
    var invalid_msg = $uploader.attr('data-msg-invalid');
    if (invalid_msg == undefined) {
        invalid_msg = 'Sorry, this file type is not permitted for security reasons.';
    }

    var acceptExt = ["csv", "xls", "xlsx", "xml"];
    if (jQuery.inArray(file_extension, acceptExt) === -1) {
        alert(invalid_msg);
        $uploader.val('');
        return false;
    } else {
        if (xhr.upload) {
                jQuery("#arm_user_download_btn").attr('disabled','disabled');
                jQuery(".arm_loader_img_download_user").show();
        var hashstr = RandomString(6);
        var index = file.name.lastIndexOf('.');
        var extension = file.name.substring(index + 1);
        var filename = /.*(?=\.)/.exec(file.name);
        var fname1 = "armDownloadUser" + hashstr + "_" + filename;
        var fname = fname1.replace(/[^\w\s]/gi, '').replace(/ /g, "") + '.' + extension;
        
        xhr.open("POST", __ARMDDURL + "/js/upload_download.php", true);
        xhr.setRequestHeader("X_FILENAME", fname);
        xhr.setRequestHeader("X-FILENAME", fname);
        xhr.send(file);
        xhr.onreadystatechange = function (e) {
            if (xhr.readyState == 4 && xhr.status == 200) {
            var data = xhr.responseText;
            $targetDiv.find('input.arm_file_url').val(data);

            } else {
            if(xhr.status != 200){

                alert("There is an error in uploading file, Please try again");

            }
            }
                    jQuery("#arm_user_download_btn").removeAttr('disabled');
                    jQuery(".arm_loader_img_download_user").hide();
            return false;
        };
        } else {
        alert("There is an error in uploading file, Please try again");
        $uploader.val('');
        }
    }
    return false;
    }
    
    
    jQuery('.armDownloadUpload').each(function(i, ele){
        ele.addEventListener("change", DownloadSelectHandler, false);
    });
    
    
    if (window.File && window.FileList && window.FileReader) {
    
    }
    jQuery('.armDownloadUpload').each(function(i, ele){
    ele.addEventListener("change", DownloadSelectHandler, false);
    });
    
    
    jQuery('.armRemoveDownload').click(function (i) {
    var $targetDiv = jQuery(this).parents('.arm_dd_bulk_import_item_form');
    var file_name = jQuery(this).attr('data-download');
    if (file_name != '') {

        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            data: "action=arm_remove_uploaded_file&file_name=" + file_name +"&type=download",
            success: function (res) {

            window.location.reload();
            }
        });

    }
    });
    
});


jQuery(document).ready(function ($) {
    if (jQuery.isFunction(jQuery().validate)) {
jQuery('#arm_dd_bulk_import_item_form').validate({
            ignore: "",
            errorClass: "error arm_invalid",
            validClass: "valid arm_valid",
            rules: {
                "import_bulk_download": {
                    required: true,
                },
               
            },
            errorPlacement: function (error, element) {
                error.appendTo(element.parent());
            },
            submitHandler: function (form) {
                var form_data = jQuery(form).serialize();
                var _wpnonce = jQuery('input[name="_wpnonce"]').val();
                    jQuery.ajax({
                        type: "POST",
                        url: __ARMAJAXURL,
                        data: "action=arm_handle_import_download&" + form_data + "&_wpnonce=" + _wpnonce,
                        success: function (response) {
                            jQuery('.arm_loader_img_download_user').fadeOut();

                            if(response == '100'){
                                armToast(__HUNDREDRECORDS, 'error');
                            }
                            else if(response == 'empty'){
                                armToast(__EMPTYFILEURL, 'error'); 
                            }
                            else if (response != '') {
                                jQuery('.arm_dd_bulk_import_item_popup_close_btn').trigger('click');
                    
                                jQuery('.arm_import_download_list_detail_popup').bPopup({
                                    opacity: 0.5,
                                    follow: [false, false],
                                    closeClass: 'arm_import_download_list_detail_close_btn',
                                    onClose: function () {
                                        var html = '<div class="arm_import_processing_loader"><div class="arm_import_processing_text">' + __PROCESSING + '</div></div>';
                                        jQuery('.arm_import_download_list_detail_popup_text').html(html);
                                        jQuery('#arm_user_metas_to_import').val('');
                                    }
                                });
                                jQuery('.arm_loader_img_download_user').fadeIn('slow');
                                jQuery('.arm_import_download_list_detail_previous_btn').show();
                                jQuery('.arm_add_import_download_submit_btn').show();
                                jQuery('#arm_download_cancel_btn').hide();
                                jQuery('#arm_download_close_btn').hide();
                                jQuery('.arm_import_download_list_detail_popup_text').html(response);
                                
                            } 
                        }
                    });
                
                return false;
            }
        });
}

jQuery(document).on('click', '.arm_import_download_list_detail_previous_btn', function(){

    jQuery('arm_import_download_list_detail_close_btn').trigger('click');
        jQuery('.arm_dd_bulk_import_item_popup').bPopup({
            opacity: 0.5,
            follow: [false, false],
            closeClass: 'arm_dd_bulk_import_item_popup_close_btn',
        });
});


jQuery(document).on('change', 'input.arm_all_import_download_chks', function () {
        if (jQuery(this).is(':checked')) {
            jQuery('input.arm_import_download_chks:not([disabled="disabled"])').each(function () {
                jQuery(this).attr('checked', true);
            });
        } else {
            jQuery('input.arm_import_download_chks:not([disabled="disabled"])').each(function () {
                jQuery(this).attr('checked', false);
            });
        }
    });










});

function arm_import_download_progressbar(total_members, interval) {

 
    var ajax_opt = {
        url: __ARMAJAXURL,
        type:'POST',
        dataType:'json',
        data:'action=arm_import_download_progress&total_downloads='+total_members,
        success:function(response){
            var percentage = response.percentage;
            jQuery('.arm_import_progressbar_inner').css('width', percentage + '%');
            jQuery('.arm_import_progressbar_inner').html(percentage + '%');

            if( true === response.continue ){
                arm_import_download_progressbar(total_members);
            } else {
                jQuery('.arm_import_progressbar_inner').html('100%');

                if(response.content != undefined){
                    if(response.content != ''){
                        jQuery('.arm_member_import_loader_wrapper').remove();
                        jQuery('.arm_import_download_list_detail_close_btn').trigger('click');

                        jQuery('.arm_dd_bulk_download_history_popup').bPopup({
                            opacity: 0.5,
                            follow: [false, false],
                            closeClass: 'arm_dd_bulk_download_history_popup_close_btn',
                            onClose: function () {
                                window.location.reload();
                            }
                        });
                        jQuery('.arm_dd_bulk_download_history_popup_detail').html(response.content);
                    }
                }
                armToast(response.msg, 'success');
            }
        }
    };
    jQuery.ajax(ajax_opt);
}


function arm_add_import_download_form_action() {

     
    var chk_count = jQuery('input[name="item-action[]"]:checked').length;
   
   
    if (chk_count > 0) {

        arm_import_download_progressbar(chk_count);

        jQuery('body').append('<div class="arm_member_import_loader_wrapper">&nbsp;</div>');
        jQuery('.arm_import_progressbar').show();
       
        var $form = jQuery('#arm_add_import_download_form');
        $form.find('.arm_loader_img').fadeIn('slow');
        jQuery('.arm_add_import_download_submit_btn').attr('disabled', 'disabled');

       
        var formData = $form.ArmFilterFormData();
      

        var strJson = jQuery.toJSON(formData);
          
        var armSack = new sack(ajaxurl);
          
        armSack.execute = 0;
       
        armSack.method = 'POST';
        armSack.async = true;
      
        armSack.setVar("action", "arm_add_import_download");
        
        armSack.setVar("filtered_form", strJson);
        armSack.onError = function () {
            armToast(wentwrong, 'error');
        };
        armSack.onCompletion = armImportDownloadParseSackRes;
        armSack.runAJAX();

        function armImportDownloadParseSackRes() {
            jQuery('.arm_add_import_download_submit_btn').removeAttr('disabled');
            jQuery('.arm_loader_img').fadeOut();
            var res = armSack.response;
            var reponse = jQuery.parseJSON(res);
            var resType = reponse.type;
            var message = reponse.msg;
            if (resType == 'success') {

            } else {
                armToast(message, 'error');
            }
        }
    } else {
        armToast(bulkRecordsError, 'error');
    }
    return false;
}

jQuery.fn.ArmFilterFormData = function () {
    var formarray = jQuery(this).serializeArray();
    for (index = 0; index < formarray.length; ++index) {
        if (formarray[index].value == "") {
            delete formarray[index];
        }
    }
    var fields = {};
    var frmsa = formarray;
    var p = 0;
    for (var key in frmsa) {
        var k = frmsa[key].name;
        var v = frmsa[key].value;
        if (k.search(/(.*?)\[(.*?)\]/) != -1) {
            var x = k.replace(/(.*?)\[(.*?)\]/, '$2');
            var m = k.replace(/(.*?)\[(.*?)\]/, '$1');
            if (m.search(/(.*?)\[(.*?)\]/) != -1) {
                var m = m.replace(/(.*?)\[(.*?)\]/, '$1');
                fields[m] = {};
            } else {
                fields[m] = {};
            }
            if (x.search(/(.*?)\[(.*?)\]/) != -1) {
                var x = x.replace(/(.*?)\[(.*?)\]/, '$1');
            }
            if (x == '') {
                fields[m][p] = {};
                p++;
            } else {
                fields[m][x] = {};
            }
        }
    }
    var p = 0;
    var z = 0;
    for (var key in frmsa) {
        var k = frmsa[key].name;
        var v = frmsa[key].value;
        if (k.search(/(.*?)\[(.*?)\]/) != -1) {
            var x = k.replace(/(.*?)\[(.*?)\]/, '$2');
            var m = k.replace(/(.*?)\[(.*?)\]/, '$1');
            if (m.search(/(.*?)\[(.*?)\]/) != -1) {
                var m = m.replace(/(.*?)\[(.*?)\]/, '$1');
            }
            if (fields[m] == null)
                fields[m] = {};
            if (x == '') {
                fields[m][p] = v;
                p++;
            } else {
                if (x.search(/(.*?)\[(.*?)\]/) != -1) {
                    x = x.replace(/(.*?)\[(.*?)\]/, '$1');
                    if (fields[m][x] == null) {
                        fields[m][x] = {};
                        var z = 0;
                    }
                    fields[m][x][z] = v;
                    z++;
                } else {
                    fields[m][x] = v;
                }
            }
        } else {
            fields[k] = v;
        }
    }
    nfields = getObj(fields);
    return nfields;
}

function getObj(obj) {
    var new_fields = {};
    var x = 0;
    var fields = {};
    var ftypes = new Array();
    for (var key in obj) {
        if (key.search(/(.*?)\[(.*?)\]/) != -1) {
            var f = key.replace(/(.*?)\[(.*?)\]/, '$2');
            var k = key.replace(/(.*?)\[(.*?)\]/, '$1');
            if (typeof (obj[key] == 'object')) {
                for (var n in obj[key]) {
                    var p = n.replace(/(.*?)\[(.*?)\]/, '$1');
                    var o = n.replace(/(.*?)\[(.*?)\]/, '$2');
                    obj[k][p] = {};
                }
                for (var n in obj[key]) {
                    var o = n.replace(/(.*?)\[(.*?)\]/, '$2');
                    var f = n.replace(/(.*?)\[(.*?)\]/, '$1');
                    if (ftypes.indexOf(f) == -1) {
                        fields = {};
                    }
                    ftypes.push(f);
                    fields[o] = obj[key][n];
                    obj[k][p] = fields;
                }
                delete obj[key][n];
            }
            delete obj[key];
        } else {
            new_fields[key] = obj[key];
        }
    }
    return obj;
}
function validate_arm_add_edit_item_form() {
    var arm_item_permission_type = jQuery('#arm_item_permission_type').val();
    if(arm_item_permission_type=='user'){
        if(jQuery(".arm_users_multiauto_items .arm_users_multiauto_itembox").length > 0) {
            if(jQuery("#arm_dd_items_users_input_error").length > 0) {
                jQuery("#arm_dd_items_users_input_error").remove();
            }
            return true;
        } else {
                var msg = jQuery("#arm_dd_items_users_input").attr('data-msg-required');
                jQuery('#arm_dd_items_users_input').attr('required', 'required');
                var error_msg = '<span id="arm_dd_items_users_input_error" class="error arm_invalid">'+msg+'</span>';
                jQuery(".arm_dd_items_user_required_wrapper").append(error_msg).show();
                
                jQuery('html, body').animate({
                    scrollTop: jQuery("body").offset().top
                }, 0);
                return false;
            }
    }else{
        jQuery('#arm_dd_items_users_input').removeAttr('required');
        return true;
    }   
    
}

