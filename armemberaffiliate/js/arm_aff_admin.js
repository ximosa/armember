jQuery(document).ready(function(){
    arm_tipso_init();
    arm_selectbox_init();
    
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
        },
        submitHandler: function (form) {
            var $this = jQuery(form);
            var no_files = jQuery('.arm_file_no_url').val();
            if(no_files > 0)
            {
                jQuery("#arm_dd_file_require_error").hide();
                if (!$this.hasClass('arm_already_clicked')) {
                    $this.find('input[type=submit], button[type=submit]').addClass('arm_already_clicked').attr('disabled', 'disabled');
                    form.submit();
                }
            }
            else
            {
                jQuery("#arm_dd_file_require_error").show();
                jQuery('html, body').animate({
                    scrollTop: jQuery("#arm_dd_file_require_error").offset().top - 150
                }, 0);
            }
        }
    });
    
});


function apply_banner_bulk_action(id) {
    jQuery('#' + id).val('true');
    jQuery('.popup_close_btn').trigger("click");
    jQuery('#arm_banner_list_form').submit();
}

function arm_banner_list_form_bulk_action() {
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
            var str = jQuery('#arm_banner_list_form').serialize();
            
            jQuery.ajax({type: "POST", url: ajaxurl, data: "action=arm_banner_bulk_action&" + str, dataType: 'json',
                success: function (res) {
                    if (res.type == 'success')
                    {
                        armToast(res.msg, 'success');
                        arm_load_affiliate_grid_after_filtered();
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

jQuery(document).on('click', '.arm_aff_banner_delete_btn', function () {
    var banner_id = jQuery(this).attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (banner_id != '' && banner_id != 0) {
        
	jQuery.ajax({
	    type: "POST",
	    url: ajaxurl,
	    dataType: 'json',
	    data: "action=arm_banner_delete&act=delete&id=" + banner_id + "&_wpnonce=" + _wpnonce,
	    success: function (res) {
		if (res.type == 'success') {
		    armToast(res.msg, 'success');
		    arm_load_affiliate_grid_after_filtered();
		} else {
		    armToast(res.msg, 'error');
		}
	    }
	});
    }
    hideConfirmBoxCallback();
    return false;
});

jQuery(document).on('change', '.arm_item_FileUpload', function() {
    var data = new FormData();

    var file_data = jQuery('input[type="file"]')[0].files; /* for multiple files */
    for(var file = 0; file < file_data.length; file++){
        data.append("arm_item_file_"+file, file_data[file]);
    }
    
    data.append('arm_item_no_file', file_data.length);
    data.append('arm_file_no_url', jQuery('#arm_file_no_url').val());
    data.append('action', 'arm_aff_add_item');
    
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
                jQuery('#file_urls').html(response.content);
                jQuery('.arm_file_no_url').val(response.no_file);
                jQuery('#arm_file_no_url-error').hide();
            }
            else
            {
                jQuery('#error_msg').html(response.error_msg);
            }
            
        },
        error: function () {
            alert("error in uploading file");
        }
     });
});


jQuery(document).ready(function(){
    //load_referral_chart();
    
    arm_icheck_init();
    arm_selectbox_init();
    arm_tipso_init();
    
    var page = jQuery("#page").val();
    if(page == 'arm_affiliate'){
        jQuery('.arm_autocomplete').bind('keypress', function(e) {
            var keyCode = e.keyCode || e.which;
            var dl = jQuery(this).parents('dl');
            var ul = dl.find('ul');
            var li_hovered = dl.find('li.hovered');
            if (keyCode >= 38 && keyCode <= 41) {
                if (ul.find("li.hovered").length > 0) {
                    var current = ul.find("li.hovered");
                    if (keyCode == 38) {
                        ul.scrollTop( 30 * (li_hovered.index()-3) );
                        ul.find("li.hovered").prevAll('li:visible').first().addClass('hovered');
                    } else if (keyCode == 40) {
                        ul.scrollTop( 30 * (li_hovered.index()-1) );
                        ul.find("li.hovered").nextAll('li:visible').first().addClass('hovered');
                    }
                    current.removeClass('hovered');
                } else {
                    ul.find("li:visible:first").addClass('hovered');
                }
            }
            else if (keyCode == 13 || keyCode == 27) {       
                li_hovered.trigger('click');
                jQuery(this).change();
            }
        });

        jQuery('.arm_selectbox').on({
            'mouseenter' : function(){
                jQuery(this).find('li.hovered').removeClass("hovered");
            }
        });
    }
});


jQuery(document).on('change', "#arm_affiliate_price_container input[type=radio]", function () {
    var type = jQuery(this).val();
    if(type == 'percentage')
    {
        jQuery('.arm_aff_price_type_percentage').show();
        jQuery('.arm_aff_price_type_currency').hide();
    }
    else
    {
        jQuery('.arm_aff_price_type_percentage').hide();
        jQuery('.arm_aff_price_type_currency').show();
    }
});

jQuery(document).on('change', "#arm_affiliate_recurring_referral_type_container input[type=radio]", function () {
    var type = jQuery(this).val();
    if(type == 'percentage')
    {
        jQuery('.arm_aff_recurring_affiliate_rate_percentage').show();
        jQuery('.arm_aff_recurring_affiliate_rate_currency').hide();
    }
    else
    {
        jQuery('.arm_aff_recurring_affiliate_rate_percentage').hide();
        jQuery('.arm_aff_recurring_affiliate_rate_currency').show();
    }
});

jQuery(document).on('click', '.export_popup', function() {
    jQuery('.arm_export').bPopup({
	opacity: 0.5,
	closeClass: 'popup_close_btn',
	follow: [false, false]
    }); 
});

jQuery(document).on('click', '.arm_export_close_btn', function () {
    jQuery('.arm_export').bPopup().close();
});


jQuery(document).on('click', '.arm_history_close_btn', function () {
    jQuery('.arm_history').bPopup().close();
});


jQuery(document).on('click', '.arm_add_payout', function() {
    jQuery('#arm_affiliate_user_id').val('Select user');
    jQuery('#arm_aff_expire_after_days').val('0');
    arm_selectbox_init();
    jQuery('.arm_add_new_payout').bPopup({
	opacity: 0.5,
	closeClass: 'popup_close_btn',
	follow: [false, false]
    }); 
});

jQuery(document).on('click', '.add_add_payout_close_btn', function () {
    jQuery('.arm_add_new_payout').bPopup().close();
});

jQuery(document).on('click', '.arm_add_payout_submit', function () {
    var arm_payouts_data = jQuery('#arm_add_payout_wrapper_frm').serialize();
    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        var error_count = 0;
        
        var arm_affiliate_user_id = jQuery('#arm_affiliate_user_id').val();
        if (arm_affiliate_user_id == '' || arm_affiliate_user_id == 'Select user') {
            jQuery('#arm_user_ids_select_chosen').css('border-color', '#ff0000');
            jQuery('#arm_user_ids_error').show();
            error_count++;
        }
        else
        {
            jQuery('#arm_user_ids_select_chosen').css('border-color', '');
            jQuery('#arm_user_ids_error').hide();
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
                data: 'action=arm_add_payouts_user&' + arm_payouts_data,
                success: function (response)
                {
                    if (response.type == 'success')
                    {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                        jQuery('#arm_add_payout_wrapper_frm')[0].reset();
                        jQuery('.arm_add_new_payout').bPopup().close();
                        jQuery('#arm_affiliate_user_id').val('Select user');
                        
                        jQuery('.arm_loading_grid').show();
                        setTimeout(function () {
                            arm_load_membership_grid_after_filtered(msg);
                        }, 1000);
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



jQuery(document).on('click', '.arm_add_affiliate_user', function() {
    jQuery('#arm_aff_expire_after_days').val('0');
    arm_selectbox_init();
    jQuery('.arm_add_new_affiliate').bPopup({
	opacity: 0.5,
	closeClass: 'popup_close_btn',
	follow: [false, false]
    }); 
});

jQuery(document).on('click', '.add_add_affiliate_close_btn', function () {
    jQuery('.arm_add_new_affiliate').bPopup().close();
});

jQuery(document).on('click', '.arm_add_affiliate_submit', function () {
    var arm_affiliate_data = jQuery('#arm_add_affiliate_wrapper_frm').serialize();
    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        var error_count = 0;
        
        var arm_affiliate_user_id = jQuery('#arm_affiliate_user_id').val();
        if (arm_affiliate_user_id == '' || arm_affiliate_user_id == 'Select user') {
            jQuery('#arm_user_ids_select_chosen').css('border-color', '#ff0000');
            jQuery('#arm_user_ids_error').show();
            error_count++;
        }
        else
        {
            jQuery('#arm_user_ids_select_chosen').css('border-color', '');
            jQuery('#arm_user_ids_error').hide();
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
                data: 'action=arm_affiliate_user_save&' + arm_affiliate_data,
                success: function (response)
                {
                    if (response.type == 'success')
                    {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                        jQuery('#arm_add_affiliate_wrapper_frm')[0].reset();
                        jQuery('.arm_add_new_affiliate').bPopup().close();
                        
                        jQuery('#arm_affiliate_days').val('1');
                        jQuery('.arm_affiliate_form dd ul').find('li[data-value="'+arm_affiliate_user_id+'"]').remove();
                        
                        arm_icheck_init();
                        arm_selectbox_init();
                        arm_tipso_init();
                        jQuery('.arm_loading_grid').show();
                        setTimeout(function () {
                            arm_load_affiliate_grid_after_filtered(msg);
                        }, 1000);
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

function arm_affiliate_edit(user_id, username){
    if( user_id != '' && username != '' ) {
        jQuery('#arm_aff_edit_user_id').val(user_id);
        jQuery('.arm_aff_set_user_name').html(username);
        jQuery('#arm_aff_expire_after_days').val('0');
        arm_selectbox_init();
        jQuery('.arm_edit_affiliate').bPopup({
            opacity: 0.5,
            closeClass: 'popup_close_btn',
            follow: [false, false]
        }); 
    }
}

jQuery(document).on('click', '.arm_edit_affiliate_close_btn', function () {
    jQuery('.arm_edit_affiliate').bPopup().close();
});

jQuery(document).on('click', '.arm_edit_affiliate_submit', function () {
    var arm_affiliate_data = jQuery('#arm_edit_affiliate_wrapper_frm').serialize();
    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        $this.addClass('arm_already_clicked');
        $this.attr('disabled', 'disabled');
        jQuery('#arm_loader_img').show();
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arm_affiliate_user_save&' + arm_affiliate_data,
            success: function (response)
            {
                if (response.type == 'success')
                {
                    var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                    jQuery('#arm_add_affiliate_wrapper_frm')[0].reset();
                    jQuery('.arm_edit_affiliate').bPopup().close();
                    jQuery('#arm_aff_expire_after_days').val('0');

                    arm_icheck_init();
                    arm_selectbox_init();
                    arm_tipso_init();
                    arm_load_affiliate_grid_after_filtered(msg);
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
    return false;
});

function apply_affiliate_bulk_action(id) {
    jQuery('#' + id).val('true');
    jQuery('.popup_close_btn').trigger("click");
    jQuery('#arm_affiliate_list_form').submit();
}

function arm_affiliate_list_form_bulk_action() {
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
            var str = jQuery('#arm_affiliate_list_form').serialize();
            
            jQuery.ajax({type: "POST", url: ajaxurl, data: "action=arm_affiliate_bulk_action&" + str, dataType: 'json',
                success: function (res) {
                    if (res.type == 'success')
                    {
                        armToast(res.msg, 'success');
                        arm_load_affiliate_grid_after_filtered();
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

jQuery(document).on('click', '.arm_aff_affiliate_delete_btn', function () {
    var affiliate_id = jQuery(this).attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (affiliate_id != '' && affiliate_id != 0) {
	jQuery.ajax({
	    type: "POST",
	    url: ajaxurl,
	    dataType: 'json',
	    data: "action=arm_affiliate_ajax_action&act=delete&id=" + affiliate_id + "&_wpnonce=" + _wpnonce,
	    success: function (res) {
		if (res.type == 'success') {
		    armToast(res.msg, 'success');
		    arm_load_affiliate_grid_after_filtered();
		} else {
		    armToast(res.msg, 'error');
		}
	    }
	});
    }
    hideConfirmBoxCallback();
    return false;
});

jQuery(document).on('click','.arm_affiliate_active_switch', function () {
    var user_id = jQuery(this).attr('data-item_id');
    var arm_aff_status = 0;
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (jQuery(this).is(':checked')) {
	var arm_aff_status = 1;
    }
    if (user_id == '' && arm_aff_status == '') {
	return false;
    }
    jQuery(this).parents('.armswitch').find('.arm_status_loader_img').show();
    jQuery.ajax({
	type: "POST",
	url: ajaxurl,
	dataType: 'json',
	data: {action: "arm_affiliate_update_status", arm_aff_user_id: user_id, arm_aff_status: arm_aff_status, _wpnonce: _wpnonce},
	success: function (res) {
	    if (res.type != 'success') {
		armToast(res.msg, 'error');
	    } else {   
                
            }
	    jQuery('.arm_status_loader_img').hide();
	}
    });
});


jQuery(document).on('click','.arm_banner_active_switch', function () {
    var item_id = jQuery(this).attr('data-item_id');
    var arm_aff_status = 0;
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (jQuery(this).is(':checked')) {
	var arm_aff_status = 1;
    }
    if (item_id == '' && arm_aff_status == '') {
	return false;
    }
    jQuery(this).parents('.armswitch').find('.arm_status_loader_img').show();
    jQuery.ajax({
	type: "POST",
	url: ajaxurl,
	dataType: 'json',
	data: {action: "arm_banner_update_status", arm_banner_id: item_id, arm_aff_status: arm_aff_status, _wpnonce: _wpnonce},
	success: function (res) {
	    if (res.type != 'success') {
		armToast(res.msg, 'error');
	    } else {   
                
            }
	    jQuery('.arm_status_loader_img').hide();
	}
    });
});

function arm_referral_edit(referral_id, user_name, ref_user_name, plan_name, amount){   
    if( referral_id != '' && user_name != '' && ref_user_name != '' && plan_name != '' && amount != '' ) {
        jQuery('#arm_referral_id').val(referral_id);
        jQuery('.arm_aff_set_user_name').html(user_name);
        jQuery('.arm_aff_set_ref_user_name').html(ref_user_name);
        jQuery('.arm_aff_set_plan_name').html(plan_name);
        jQuery('.arm_referral_amount').val(amount);
        
        jQuery('.arm_edit_referral').bPopup({
            opacity: 0.5,
            closeClass: 'popup_close_btn',
            follow: [false, false]
        }); 
    }
}

jQuery(document).on('click', '.arm_edit_referral_close_btn', function () {
    jQuery('.arm_edit_referral').bPopup().close();
});

jQuery(document).on('click', '.arm_edit_referral_submit', function () {
    var arm_referral_data = jQuery('#arm_edit_referral_wrapper_frm').serialize();
    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        $this.addClass('arm_already_clicked');
        $this.attr('disabled', 'disabled');
        jQuery('#arm_loader_img').show();
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arm_referral_edit&' + arm_referral_data,
            success: function (response)
            {
                if (response.type == 'success')
                {
                    var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                    arm_icheck_init();
                    arm_selectbox_init();
                    arm_tipso_init();
                    arm_load_membership_grid_after_filtered();
                    armToast(msg, 'success');
                    jQuery('.arm_edit_referral').bPopup().close();
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
    return false;
});



jQuery(document).on('click', "#arm_visits_grid_filter_btn", function () {
    var search = jQuery('#armmanagesearch_new').val();
    var end_date = jQuery('#end_date').val();
    var start_date = jQuery('#start_date').val();
    arm_reload_visits_list(search, start_date, end_date);
});

function arm_reload_visits_list(search, start_date, end_date) {
    var search = (search == '' || search == null || typeof search == 'undefined') ? '' : search;
    var start_date = (start_date == '' || start_date == null || typeof start_date == 'undefined') ? '0' : start_date;
    var end_date = (end_date == '' || end_date == null || typeof end_date == 'undefined') ? '0' : end_date;
    
    jQuery('.arm_loading_grid').fadeIn('slow');
    jQuery.ajax({
	type: "POST",
	url: ajaxurl,
	data: "action=arm_filter_visits_list&search=" + search + "&start_date=" + start_date + "&end_date=" + end_date,
	success: function (response) {
	    jQuery('#arm_members_grid_container').html(response);
	    jQuery('.arm_loading_grid').fadeOut();
	    arm_aff_js_init();
	}
    });
}

function arm_user_export_payout_hisroty( affiliate_id ) {
    jQuery('#arm_affiliate_user_id').val(affiliate_id);
    jQuery('#arm_user_export_payout_hisroty').submit();
}

function arm_user_payout_hisroty( affiliate_id ) {
    var list_data = jQuery('#arm_payment_history_'+affiliate_id).val();
    if (typeof list_data != undefined && affiliate_id > 0) {
       
        var tableData = jQuery.parseJSON(list_data);
        jQuery('.arm_history').bPopup({
            opacity: 0.5,
            follow: [false, false],
            closeClass: 'arm_members_list_detail_close_btn',
            onClose: function () {
                var oTable = jQuery('#example_1').dataTable();
                oTable.fnDestroy();
            }
        });
        var oTable = jQuery('#example_1').DataTable({
            "sDom": 't<"F"ipl>',
            "sPaginationType": "four_button",
            "bJQueryUI": true,
            "bPaginate": true,
            "bAutoWidth" : false,
            "sScrollX": "100%",
            "bScrollCollapse": true,
            "aaData": tableData,
            "aoColumnDefs": [
                {"bVisible": false, "aTargets": []},
                {"sClass": "left", "aTargets": [1]},
                {"sClass": "center", "aTargets": [2,3]},
                {"bSortable": false, "aTargets": [0]},
            ],
            "aaSorting": [],
            "aLengthMenu": [10, 25, 50, 100, 150, 200],
            "oLanguage": {
                "sInfo": "Showing _START_ to _END_ of _TOTAL_ visitor",
                "sInfoEmpty": "Showing 0 to 0 of 0 members",
                "sInfoFiltered": "(_FILTERES_FROM_ _MAX_ _TOTALWD__ visitor)",
                "sLengthMenu": "Show _MENU_ visitor",
                "sEmptyTable": "No any visitor found.",
                "sZeroRecords": "No matching visitor found."
            }
        });
    }
}

jQuery(document).on('click', '.arm_drip_members_list_detail', function () {
    var list_id = jQuery(this).attr('data-list_id');
    var list_type = jQuery(this).attr('data-list_type');
    
    if (list_id != '' && list_id != 0)
    {
        if(list_type === 'visits')
        {
            var list_data = jQuery('#arm_json_visits_data_'+list_id).val();
            if (typeof list_data != undefined) {
                var tableData = jQuery.parseJSON(list_data);
                jQuery('.arm_visitor_list_detail_popup').bPopup({
                    opacity: 0.5,
                    follow: [false, false],
                    closeClass: 'arm_members_list_detail_close_btn',
                    onClose: function () {
                        var oTable = jQuery('#example_1').dataTable();
                        oTable.fnDestroy();
                    }
                });
                var oTable = jQuery('#example_1').DataTable({
                    "sDom": 't<"F"ipl>',
                    "sPaginationType": "four_button",
                    "bJQueryUI": true,
                    "bPaginate": true,
                    "bAutoWidth" : false,
                    "sScrollX": "100%",
                    "bScrollCollapse": true,
                    "aaData": tableData,
                    "aoColumnDefs": [
                        {"bVisible": false, "aTargets": []},
                        {"sClass": "left", "aTargets": [1]},
                        {"bSortable": false, "aTargets": [0,1]},
                    ],
                    "aaSorting": [],
                    "aLengthMenu": [10, 25, 50, 100, 150, 200],
                    "oLanguage": {
                        "sInfo": "Showing _START_ to _END_ of _TOTAL_ visitor",
                        "sInfoEmpty": "Showing 0 to 0 of 0 members",
                        "sInfoFiltered": "(_FILTERES_FROM_ _MAX_ _TOTALWD__ visitor)",
                        "sLengthMenu": "Show _MENU_ visitor",
                        "sEmptyTable": "No any visitor found.",
                        "sZeroRecords": "No matching visitor found."
                    }
                });
            }
        }
        else if(list_type === 'converted_user')
        {
            var list_data1 = jQuery('#arm_json_converted_user_data_'+list_id).val();
            if (typeof list_data != undefined) {
                var tableData1 = jQuery.parseJSON(list_data1);
                jQuery('.arm_converted_user_list_detail_popup').bPopup({
                    opacity: 0.5,
                    follow: [false, false],
                    closeClass: 'arm_converted_user_list_detail_close_btn',
                    onClose: function () {
                        var oTable = jQuery('#example_2').dataTable();
                        oTable.fnDestroy();
                    }
                });
                
                var oTable = jQuery('#example_2').DataTable({
                    "sDom": 't<"F"ipl>',
                    "sPaginationType": "four_button",
                    "bJQueryUI": true,
                    "bPaginate": true,
                    "bAutoWidth" : false,
                    "sScrollX": "100%",
                    "bScrollCollapse": true,
                    "aaData": tableData1,
                    "aoColumnDefs": [
                        {"bVisible": false, "aTargets": []},
                        {"bSortable": false, "aTargets": []},
                        {"sClass": "left", "aTargets": [1,2,3,4]},
                    ],
                    "aaSorting": [],
                    "aLengthMenu": [10, 25, 50, 100, 150, 200],
                    "oLanguage": {
                        "sInfo": "Showing _START_ to _END_ of _TOTAL_ converted as user",
                        "sInfoEmpty": "Showing 0 to 0 of 0 converted as user",
                        "sInfoFiltered": "(_FILTERES_FROM_ _MAX_ _TOTALWD__ converted as user)",
                        "sLengthMenu": "Show _MENU_ converted as user",
                        "sEmptyTable": "No any converted as user found.",
                        "sZeroRecords": "No matching converted as user found."
                    }
                });
            }
        }
    }
    return false;
});

jQuery(document).on('click', '#arm_affiliate_settings_btn', function () {
    var affiliate_settings = jQuery('#arm_affiliate_settings').serialize();
    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        var referral_var = jQuery('#arm_aff_referral_var').val();
        var referral_timeout = jQuery('#arm_aff_referral_timeout').val();
        //var referral_rate_type = jQuery('#arm_aff_referral_rate_type').val();
        var referral_default_rate = jQuery('#arm_aff_referral_default_rate').val();

        var error_count = 0;
        if (referral_var == '') {
	    jQuery('#invalid_aff_referral_var_error').hide();
	    jQuery('#arm_aff_referral_var').css('border-color', '#ff0000');
	    jQuery('#aff_referral_var_error').show();
	    jQuery('html, body').animate({
		scrollTop: jQuery("#arm_aff_referral_var").offset().top - 40
	    }, 0);
	    error_count++;
	} else {
            jQuery('#arm_aff_referral_var').css('border-color', '');
            jQuery('#invalid_aff_referral_var_error').hide();
            jQuery('#aff_referral_var_error').hide();
	}
        
        if (referral_timeout == '') {
	    jQuery('#invalid_arm_aff_referral_timeout_error').hide();
	    jQuery('#arm_aff_referral_timeout').css('border-color', '#ff0000');
	    jQuery('#arm_aff_referral_timeout_error').show();
	    jQuery('html, body').animate({
		scrollTop: jQuery("#arm_aff_referral_timeout").offset().top - 40
	    }, 0);
	    error_count++;
	} else {
            jQuery('#arm_aff_referral_timeout_error').hide();
            if(referral_timeout.match(/^[0-9]+$/)){
                jQuery('#arm_aff_referral_timeout').css('border-color', '');
                jQuery('#invalid_arm_aff_referral_timeout_error').hide();
                jQuery('#arm_aff_referral_timeout_error').hide();
            } else {
                jQuery('#invalid_arm_aff_referral_timeout_error').show();
                jQuery('#arm_aff_referral_timeout').css('border-color', '#ff0000');
                jQuery('html, body').animate({
                    scrollTop: jQuery("#arm_aff_referral_timeout").offset().top - 40
                }, 0);
                error_count++;
            }
	}
        
        if (referral_default_rate == '') {
            jQuery('#invalid_arm_aff_referral_default_rate_error').hide();
            jQuery('#arm_aff_referral_default_rate').css('border-color', '#ff0000');
            jQuery('#arm_aff_referral_default_rate_error').show();
            jQuery('html, body').animate({
                scrollTop: jQuery("#arm_aff_referral_default_rate").offset().top - 40
            }, 0);
            error_count++;
        } else {
            jQuery('#arm_aff_referral_default_rate_error').hide();
            if(referral_default_rate.match(/^[0-9]+$/)){
                jQuery('#arm_aff_referral_default_rate').css('border-color', '');
                jQuery('#invalid_arm_aff_referral_default_rate_error').hide();
                jQuery('#arm_aff_referral_default_rate_error').hide();
            } else {
                jQuery('#invalid_arm_aff_referral_default_rate_error').show();
                jQuery('#arm_aff_referral_default_rate').css('border-color', '#ff0000');
                jQuery('html, body').animate({
                    scrollTop: jQuery("#arm_aff_referral_default_rate").offset().top - 40
                }, 0);
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
                data: 'action=arm_affiliate_settings&' + affiliate_settings,
                success: function (response)
                {
                    if (response.type == 'success')
                    {
                        if(response.armaff_ref_url !== undefined && response.armaff_ref_url !== ''){
                            jQuery("#armaff_referral_url_example").html(response.armaff_ref_url);
                        }
                        
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


jQuery(document).on('click', '#arm_affiliate_migrate_btn', function () {

    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {

        $this.addClass('arm_already_clicked');
        $this.attr('disabled', 'disabled');
        jQuery('#arm_migrate_loader_img').show();

        var aff_migrate_type = jQuery('input[name="armaff_migrate"]:checked').val();
        var aff_migrate_text = jQuery('#armaff_'+aff_migrate_type).attr('armaff_wptext');
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();
        if($this.attr('armaff-confirm') == 1){

                var formData = jQuery('#arm_affiliate_migration').serialize();

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: 'action=arm_affiliate_migrate_data&'+formData,
                    success: function (response)
                    {
                        $this.removeAttr('disabled').removeClass('arm_already_clicked');
                        jQuery("#arm_affiliate_migrate_btn").html("Migrate").removeAttr('armaff-confirm');
                        jQuery("tr.armaff_migrate_confirmation_row").hide();
                        jQuery('#arm_migrate_loader_img').hide();

                        jQuery('html, body').animate({
                            scrollTop: 0
                        }, 0);

                        if (response.type == 'success')
                        {
                            var msg = (response.message != '') ? response.message : armMigrateDataSuccess;
                            armToast(msg, 'success');
                        } else {
                            var msg = (response.message != '') ? response.message : armMigrateDataError;
                            armToast(msg, 'error');
                        }
                    }
                });

        } else {

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: 'action=armaff_get_wpaffiliate_details&armaff_migrate=' + aff_migrate_type + "&_wpnonce=" + _wpnonce ,
                    success: function (response)
                    {

                        $this.removeAttr('disabled').removeClass('arm_already_clicked');
                        jQuery('#arm_migrate_loader_img').hide();

                        if (response.type == 'success')
                        {
                            armMigrateInfo = jQuery.parseJSON(response.arm_migrate_info);

                            jQuery("#armaff_account_last_id").val(armMigrateInfo.armAffiliate_id);
                            jQuery("#wp_account_first_id").val(armMigrateInfo.wpAffiliate_id);

                            if(armMigrateInfo.armMigrate_conflict == 1){

                                var migrate_list_html = jQuery(".armaff_migrate_restrict").html();

                                migrate_list_html = migrate_list_html.replace(/{WP_AFFILIATE}/ig, aff_migrate_text);
                                migrate_list_html = migrate_list_html.replace(/{ARM_LAST_AFFILIATE}/ig, armMigrateInfo.armAffiliate_id);
                                migrate_list_html = migrate_list_html.replace(/{WP_FIRST_AFFILIATE}/ig, armMigrateInfo.wpAffiliate_id);
                                migrate_list_html = migrate_list_html.replace(/{WP_AFFILIATE_RECORDS}/ig, armMigrateInfo.wpTotalAffiliate);
                                migrate_list_html = migrate_list_html.replace(/{ARMAFF_AFFILIATE_RECORDS}/ig, armMigrateInfo.armaffTotalAffiliate);

                                jQuery(".armaff_migrate_confirmation_col").html(migrate_list_html);

                                jQuery("tr.armaff_migrate_confirmation_row").slideDown( 1000 );

                                jQuery("#arm_affiliate_migrate_btn").removeAttr('armaff-confirm');

                                return false;
                            }

                            var migrate_list_html = jQuery(".armaff_migrate_confirmation").html();

                            migrate_list_html = migrate_list_html.replace(/{WP_AFFILIATE}/ig, aff_migrate_text);
                            migrate_list_html = migrate_list_html.replace(/{ARM_LAST_AFFILIATE}/ig, armMigrateInfo.armAffiliate_id);
                            migrate_list_html = migrate_list_html.replace(/{WP_FIRST_AFFILIATE}/ig, armMigrateInfo.wpAffiliate_id);
                            migrate_list_html = migrate_list_html.replace(/{WP_AFFILIATE_RECORDS}/ig, armMigrateInfo.wpTotalAffiliate);
                            migrate_list_html = migrate_list_html.replace(/{ARMAFF_AFFILIATE_RECORDS}/ig, armMigrateInfo.armaffTotalAffiliate);

                            jQuery(".armaff_migrate_confirmation_col").html(migrate_list_html);

                            jQuery(".armaff_migrate_confirmation_col").find('.armaff_icheckbox').addClass('arm_icheckbox').removeClass('armaff_icheckbox');

                            jQuery("#armaff_enable_affid_encoding_option, #armaff_enable_fancy_url_option").addClass("armaff_hide");

                            if(armMigrateInfo.armEnable_FancyURL == 1){
                                jQuery("#armaff_enable_fancy_url_option").removeClass("armaff_hide");
                            }
                            if(armMigrateInfo.armEnable_Encoding == 1){
                                jQuery("#armaff_enable_affid_encoding_option").removeClass("armaff_hide");
                            }

                            jQuery("#arm_affiliate_migrate_btn").html("Continue").attr('armaff-confirm', 1);


                            jQuery("tr.armaff_migrate_confirmation_row").slideDown( 1000 );

                            arm_icheck_init();

                            return false;


                        }

                        if (response.type == 'database_error' || response.type == 'error')
                        {

                            var armaff_wperror_html = '<div class="armaff_migration_error_text">'+response.message+'</div>';

                            jQuery(".armaff_migrate_confirmation_col").html(armaff_wperror_html);

                            jQuery("tr.armaff_migrate_confirmation_row").slideDown( 1000 );

                            jQuery("#arm_affiliate_migrate_btn").removeAttr('armaff-confirm');

                            return false;

                        }

                        if(response.type == 'plugin_inactive'){
                            var msg = (response.message != '') ? response.message : armMigrateDataError;
                            armToast(msg, 'error');
                        }

                    }
                });

        }

    }

    return false;

});

jQuery(document).on('change', 'input[name="armaff_migrate"]', function() {

    jQuery("tr.armaff_migrate_confirmation_row").hide();
    jQuery("#arm_affiliate_migrate_btn").removeAttr("disabled");
    jQuery("#arm_affiliate_migrate_btn").html("Migrate").removeAttr('armaff-confirm');

});


jQuery(document).ready(function(){
    arm_icheck_init();
    arm_selectbox_init();
    load_datepicker();
    
    jQuery('.arm_filter_plans_box').find('dt').css('width','180px');
    jQuery('.arm_filter_payment_node_box').find('dt').css('width','100px');
    
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

function arm_aff_js_init() {
    if (jQuery.isFunction(jQuery().tabs)) {
	jQuery("#arm_tabs").tabs();
    }
    if (jQuery.isFunction(jQuery().chosen)) {
	jQuery(".arm_chosen_selectbox").chosen({
	    no_results_text: "Oops, nothing found."
	});
    }
    arm_icheck_init();
    arm_selectbox_init();
    load_datepicker();
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
}

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
                locale: ''
            })/*.on("dp.change", function (e) {
                jQuery(this).trigger('input');
            })*/;
        });
    }
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

function showChangeStatusBoxCallback(item_id) {
    if (item_id != '') {
	var deleteBox = jQuery('#arm_change_status_box_' + item_id);
	deleteBox.addClass('armopen').toggle('slide');
	deleteBox.parents('.armGridActionTD').toggleClass('armopen');
	deleteBox.parents('tr').toggleClass('armopen');
	deleteBox.parents('.dataTables_wrapper').append('<div class="arm_confirm_back_wrapper" onclick="hideConfirmBoxCallback();"></div>');
	deleteBox.parents('.armPageContainer').append('<div class="arm_confirm_back_wrapper" onclick="hideConfirmBoxCallback();"></div>');
    }
    return false;
}

function showPaymentConfirmBoxCallback(item_id) { 
    if (item_id != '') {
	var deleteBox = jQuery('#arm_payment_box_' + item_id);
	deleteBox.addClass('armopen').toggle('slide');
	deleteBox.parents('.armGridActionTD').toggleClass('armopen');
	deleteBox.parents('tr').toggleClass('armopen');
	deleteBox.parents('.dataTables_wrapper').append('<div class="arm_confirm_back_wrapper" onclick="hideConfirmBoxCallback();"></div>');
	deleteBox.parents('.armPageContainer').append('<div class="arm_confirm_back_wrapper" onclick="hideConfirmBoxCallback();"></div>');
    }
    return false;
}

jQuery(document).on('click', '.arm_payment_ok_btn', function () {
    var $this = jQuery(this);
    var user_id = $this.attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (!$this.hasClass('arm_already_clicked') && user_id > 0) {
        var amount = jQuery('#arm_amount_'+user_id).val();
        if(amount > 0) {
            $this.addClass('arm_already_clicked');
            $this.attr('disabled', 'disabled');
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: 'action=arm_add_payouts_user&arm_affiliate_user_id='+user_id+'&arm_amount='+amount + "&_wpnonce=" + _wpnonce,
                success: function (response)
                {
                    if (response.type == 'success')
                    {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                        jQuery('.arm_loading_grid').show();
                        armToast(msg, 'success');
                        setTimeout(function () {
                            arm_load_membership_grid_after_filtered(msg);
                        }, 1000);
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
        else 
        {
            armToast('Please insert valid amount.', 'error');
        }
    }
    return false;
});


function referrals_action(affiliate_id, action){
    if (affiliate_id != '' && affiliate_id != 0 && action != '') {
        
        var act_action = '';
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();
        if( action == '0' ) { 
            act_action = 'accept';
        } else if( action == 1 ) {
            act_action = 'paid';
        } else if( action == 3 ) {
            act_action = 'reject';
        } else if( action == '4' ) {
            act_action = 'unpaid';
        } else {
            return false;
        }
        
        show_grid_loader();
	jQuery.ajax({
	    type: "POST",
	    url: ajaxurl,
	    dataType: 'json',
	    data: "action=arm_referral_ajax_action&act=" + act_action + "&id=" + affiliate_id + "&_wpnonce=" + _wpnonce,
	    success: function (res) {
		if (res.type == 'success') {
		    armToast(res.msg, 'success');
		    arm_load_membership_grid_after_filtered();
		} else {
		    armToast(res.msg, 'error');
		}
	    }
	});
    }
    return false;
}

jQuery(document).on('click', '.arm_aff_refferal_delete_btn', function () {
    var affiliate_id = jQuery(this).attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (affiliate_id != '' && affiliate_id != 0) {
	jQuery.ajax({
	    type: "POST",
	    url: ajaxurl,
	    dataType: 'json',
	    data: "action=arm_referral_ajax_action&act=delete&id=" + affiliate_id + "&_wpnonce=" + _wpnonce,
	    success: function (res) {
		if (res.type == 'success') {
		    armToast(res.msg, 'success');
		    arm_load_membership_grid_after_filtered();
		} else {
		    armToast(res.msg, 'error');
		}
	    }
	});
    }
    hideConfirmBoxCallback();
    return false;
});

function export_referrals_csv(){
    jQuery('#export_data_to_csv').submit();
}

function apply_referral_bulk_action(id) {
    jQuery('#' + id).val('true');
    jQuery('.popup_close_btn').trigger("click");
    jQuery('#arm_referral_list_form').submit();
}

function arm_referral_list_form_bulk_action() {
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
            var str = jQuery('#arm_referral_list_form').serialize();
            
            jQuery.ajax({type: "POST", url: ajaxurl, data: "action=arm_referral_bulk_action&" + str, dataType: 'json',
                success: function (res) {
                    if (res.type == 'success')
                    {
                        armToast(res.msg, 'success');
                        arm_load_membership_grid_after_filtered();
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

/********* visits bulk action  ***********/

function apply_visits_bulk_action(id) {
    jQuery('#' + id).val('true');
    jQuery('.popup_close_btn').trigger("click");
    jQuery('#arm_referral_list_form').submit();
}

function arm_visits_list_form_bulk_action() {
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
            var str = jQuery('#arm_referral_list_form').serialize();
            
            jQuery.ajax({type: "POST", url: ajaxurl, data: "action=arm_visits_bulk_action&" + str, dataType: 'json',
                success: function (res) {
                    if (res.type == 'success')
                    {
                        armToast(res.msg, 'success');
                        arm_reload_visits_list('','','');
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

jQuery(document).on('change', "#arm_aff_affiliate_disable_referral", function () {

    var isChecked = jQuery(this).is(':checked');
    var parent_wrapper = jQuery(this).parents('.page_sub_content');
    if(isChecked){
        parent_wrapper.find('tr.aff_affiliate_sub_opt').removeClass('hidden_section');
    } else {
        parent_wrapper.find('tr.aff_affiliate_sub_opt').addClass('hidden_section');
    }

});

jQuery(document).on('change', ".arm_aff_recurring_affiliate_disable_referral", function () {

    var isChecked = jQuery(this).is(':checked');
    var parent_wrapper = jQuery(this).parents('.page_sub_content');
    if(isChecked){
        parent_wrapper.find('tr.aff_subscription_affiliate_sub_opt').removeClass('hidden_section');
    } else {
        parent_wrapper.find('tr.aff_subscription_affiliate_sub_opt').addClass('hidden_section');
    }

});

jQuery(document).on('change', "#arm_subscription_types_container input[type=radio]", function () {
    var type = jQuery(this).val();
    switch (type) {
        case 'recurring':
            var isChecked = jQuery('.arm_aff_recurring_affiliate_disable_referral').is(':checked');
            if(isChecked){
                jQuery('tr.aff_subscription_affiliate_sub_opt').removeClass('hidden_section');
            } else {
                jQuery('tr.aff_subscription_affiliate_sub_opt').addClass('hidden_section');
            }
            break;
        default:
            break;
    }
});

function armaff_edit_plan_commision(armaff_planid){
    if( armaff_planid != '' ) {
        jQuery('#armaff_commision_plan_id').val(armaff_planid);
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();
        jQuery('.arm_edit_plan_comission').bPopup({
            opacity: 0.5,
            closeClass: 'popup_close_btn',
            follow: [false, false]
        });

        jQuery("#armaff_edit_plan_commision_loader_img").css('display','block');
        jQuery(".arm_edit_plan_comission_content").hide();
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: "action=armaff_get_plan_commision_setup_content&armaff_planid=" + armaff_planid + "&_wpnonce=" + _wpnonce,
            success: function (res) {
                jQuery("#armaff_edit_plan_commision_loader_img").css('display','none');
                if(res.type == 'success'){

                    var referral_options = res.armaff_plan_options;

                    if(referral_options.arm_affiliate_referral_disable == 1){
                        jQuery(".arm_edit_plan_comission_content").find("input[name='arm_subscription_plan_options[arm_aff_affiliate_disable_referral]']").prop('checked', true);
                        jQuery(".arm_edit_plan_comission_content").find('tr.aff_affiliate_sub_opt').removeClass('hidden_section');
                    } else {
                        jQuery(".arm_edit_plan_comission_content").find("input[name='arm_subscription_plan_options[arm_aff_affiliate_disable_referral]']").prop('checked', false);
                        jQuery(".arm_edit_plan_comission_content").find('tr.aff_affiliate_sub_opt').addClass('hidden_section');
                    }

                    if(referral_options.arm_affiliate_referral_type == "fixed_rate"){
                        jQuery(".arm_edit_plan_comission_content").find('#arm_aff_affiliate_price_type_fixed_rate').prop('checked', true);
                        jQuery(".arm_edit_plan_comission_content").find('.arm_aff_price_type_percentage').hide();
                        jQuery(".arm_edit_plan_comission_content").find('.arm_aff_price_type_currency').show();
                    } else {
                        jQuery(".arm_edit_plan_comission_content").find('#arm_aff_affiliate_price_type_percentage').prop('checked', true);
                        jQuery(".arm_edit_plan_comission_content").find('.arm_aff_price_type_percentage').show();
                        jQuery(".arm_edit_plan_comission_content").find('.arm_aff_price_type_currency').hide();
                    }

                    jQuery(".arm_edit_plan_comission_content").find("input[name='arm_subscription_plan_options[arm_aff_affilaite_rate]']").val(referral_options.arm_affiliate_referral_rate);

                    if(referral_options.payment_type == 'subscription'){
                        jQuery(".arm_edit_plan_comission_content").find(".form-field.form-required.paid_subscription_options_recurring").removeClass('hidden_section');
                    } else {
                        jQuery(".arm_edit_plan_comission_content").find(".form-field.form-required.paid_subscription_options_recurring").addClass('hidden_section');
                    }

                    if(referral_options.arm_affiliate_recurring_referral_disable == 1){
                        jQuery(".arm_edit_plan_comission_content").find("input[name='arm_subscription_plan_options[arm_aff_recurring_affiliate_disable_referral]']").prop('checked', true);
                        jQuery(".arm_edit_plan_comission_content").find('tr.aff_subscription_affiliate_sub_opt').removeClass('hidden_section');
                    } else {
                        jQuery(".arm_edit_plan_comission_content").find("input[name='arm_subscription_plan_options[arm_aff_recurring_affiliate_disable_referral]']").prop('checked', false);
                        jQuery(".arm_edit_plan_comission_content").find('tr.aff_subscription_affiliate_sub_opt').addClass('hidden_section');
                    }

                    if(referral_options.arm_affiliate_recurring_referral_type == "fixed_rate"){
                        jQuery(".arm_edit_plan_comission_content").find('#arm_aff_recurring_referral_type_fixed_rate').prop('checked', true);
                        jQuery(".arm_edit_plan_comission_content").find('.arm_aff_recurring_affiliate_rate_percentage').hide();
                        jQuery(".arm_edit_plan_comission_content").find('.arm_aff_recurring_affiliate_rate_currency').show();
                    } else {
                        jQuery(".arm_edit_plan_comission_content").find('#arm_aff_recurring_referral_type_percentage').prop('checked', true);
                        jQuery(".arm_edit_plan_comission_content").find('.arm_aff_recurring_affiliate_rate_percentage').show();
                        jQuery(".arm_edit_plan_comission_content").find('.arm_aff_recurring_affiliate_rate_currency').hide();
                    }

                    jQuery(".arm_edit_plan_comission_content").find("input[name='arm_subscription_plan_options[arm_aff_recurring_affilaite_rate]']").val(referral_options.arm_affiliate_recurring_referral_rate);

                } else {
                    armToast(res.msg, 'error');
                }
                jQuery(".arm_edit_plan_comission_content").show();
                arm_icheck_init();
            }
        });

    }
}

jQuery(document).on('click', '.arm_edit_plan_comission_close_btn', function () {
    jQuery('.arm_edit_plan_comission').bPopup().close();
});

jQuery(document).on('click', '.arm_edit_plan_comission_submit', function () {
    var armaff_plan_referral_data = jQuery('#arm_edit_plan_comission_wrapper_frm').serialize();

    var armaff_referral_type_text = ''; var armaff_referral_type_unit = '';
    var armaff_referral_type = jQuery('#arm_edit_plan_comission_wrapper_frm').find("input[name='arm_subscription_plan_options[arm_aff_affiliate_price_type]']:checked").val();
    if(armaff_referral_type == 'percentage'){
        armaff_referral_type_text = 'Percentage';
        armaff_referral_type_unit = '%';
    } else if(armaff_referral_type == 'fixed_rate'){
        armaff_referral_type_text = 'Fixed Rate';
        armaff_referral_type_unit = jQuery('#arm_edit_plan_comission_wrapper_frm').find(".arm_aff_price_type_currency").html();
    }
    var armaff_referral_rate = jQuery('#arm_edit_plan_comission_wrapper_frm').find("#arm_aff_affilaite_rate").val();
    var armaff_referral_enable = jQuery('#arm_edit_plan_comission_wrapper_frm').find("input[name='arm_subscription_plan_options[arm_aff_affiliate_disable_referral]']").is(':checked');

    var armaff_edit_plan_id = jQuery("#armaff_commision_plan_id").val();

    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        $this.addClass('arm_already_clicked');
        $this.attr('disabled', 'disabled');
        jQuery('#arm_loader_img').show();
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=armaff_plan_commision_edit&' + armaff_plan_referral_data,
            success: function (response)
            {
                if (response.type == 'success')
                {
                    var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                    arm_icheck_init();
                    arm_selectbox_init();
                    arm_tipso_init();
                    armToast(msg, 'success');
                    jQuery('.arm_edit_plan_comission').bPopup().close();
                    if(armaff_referral_enable){
                        jQuery('.armaff_plan_type_'+armaff_edit_plan_id).html(armaff_referral_type_text);
                        jQuery('.armaff_plan_rate_'+armaff_edit_plan_id).html(armaff_referral_rate+' '+armaff_referral_type_unit);
                    } else {
                        jQuery('.armaff_plan_type_'+armaff_edit_plan_id).html('');
                        jQuery('.armaff_plan_rate_'+armaff_edit_plan_id).html('');
                    }
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
    return false;
});

function armaff_reset_affiliate_commision_setup_popup(){

    jQuery(".armaff_add_affiliate_commision_section").find('#arm_aff_affiliate_price_type_percentage').prop('checked', true);
    jQuery(".armaff_add_affiliate_commision_section").find("input[name='arm_subscription_plan_options[arm_aff_affilaite_rate]']").val(0);

    jQuery(".armaff_add_affiliate_commision_section").find("input[name='arm_subscription_plan_options[arm_aff_recurring_affiliate_disable_referral]']").prop('checked', false)
    jQuery(".armaff_add_affiliate_commision_section").find('tr.aff_subscription_affiliate_sub_opt').addClass('hidden_section');

    jQuery(".armaff_add_affiliate_commision_section").find('#arm_aff_recurring_referral_type_percentage').prop('checked', true);
    jQuery(".armaff_add_affiliate_commision_section").find("input[name='arm_subscription_plan_options[arm_aff_recurring_affilaite_rate]']").val(0);

}

jQuery(document).on('click', '.arm_add_affiliate_userwise_referral', function() {

    jQuery('#armaff_select_affiliate').show();
    jQuery("#armaff_edit_affiliate_name").hide();
    jQuery('#arm_affiliate_user_id').val('');
    arm_selectbox_init();
    jQuery("#arm_aff_action").val('add');
    jQuery(".armaff_add_affiliate_title").show();
    jQuery(".armaff_edit_affiliate_title").hide();

    jQuery('.armaff_select_affiliate_user').bPopup({
    opacity: 0.5,
    closeClass: 'popup_close_btn',
    follow: [false, false]
    });

    armaff_reset_affiliate_commision_setup_popup();
    arm_icheck_init();


});

jQuery(document).on('click', '.armaff_add_user_referral_close_btn', function () {
    jQuery('.armaff_select_affiliate_user').bPopup().close();
});

jQuery(document).on('click', ".arm_selectbox.arm_affiliate_commision_form dd ul li:not(.field_inactive)", function (e) {

    var arm_affiliate_id = jQuery(this).attr('data-affiliate');
    jQuery("#arm_affiliate_id").val(arm_affiliate_id);

});

jQuery(document).on('click', '.arm_submit_affiliate_user_referral', function () {
    var arm_affiliate_commision_data = jQuery('#arm_add_user_referral_wrapper_frm').serialize();
    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        var error_count = 0;
        
        var arm_affiliate_user_id = jQuery('#arm_affiliate_user_id').val();
        if (arm_affiliate_user_id == '' || arm_affiliate_user_id == 'Select Affiliate User') {
            jQuery('#arm_user_ids_select_chosen').css('border-color', '#ff0000');
            jQuery('#arm_user_ids_error').show();
            error_count++;
        }
        else
        {
            jQuery('#arm_user_ids_select_chosen').css('border-color', '');
            jQuery('#arm_user_ids_error').hide();
        }
        
        if(error_count > 0) {
            return false;
        } else {
            $this.addClass('arm_already_clicked');
            $this.attr('disabled', 'disabled');
            jQuery('#arm_affiliate_loader_img').show();
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: 'action=arm_affiliate_commision_save&' + arm_affiliate_commision_data,
                success: function (response)
                {
                    if (response.type == 'success')
                    {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsSuccess;
                        jQuery('#arm_add_user_referral_wrapper_frm')[0].reset();
                        jQuery('.armaff_select_affiliate_user').bPopup().close();
                        jQuery('#arm_affiliate_user_id').val('');
                        jQuery('.arm_affiliate_commision_form dd ul').find('li[data-value="'+arm_affiliate_user_id+'"]').remove();
                        
                        arm_icheck_init();
                        arm_selectbox_init();
                        arm_tipso_init();
                        setTimeout(function () {
                            arm_load_affiliates_referral_filtered_grid(msg);
                        }, 1000);
                    } else {
                        var msg = (response.msg != '') ? response.msg : armsaveSettingsError;
                        armToast(msg, 'error');
                    }
                    jQuery('#arm_affiliate_loader_img').hide();
                    $this.removeAttr('disabled');
                    $this.removeClass('arm_already_clicked');
                }
            });
        }
    }
    return false;
});

function armaff_edit_affiliate_commision(armaff_setupid){
    if( armaff_setupid != '' ) {
        jQuery('#armaff_select_affiliate').hide();
        jQuery(".armaff_add_affiliate_title").hide();
        jQuery(".armaff_edit_affiliate_title").show();
        jQuery("#armaff_edit_affiliate_name").hide();
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();

        jQuery('.armaff_select_affiliate_user').bPopup({
        opacity: 0.5,
        closeClass: 'popup_close_btn',
        follow: [false, false]
        });

        jQuery("#arm_aff_action").val('edit');
        jQuery("#armaff_get_affiliate_commision_loader_img").css('display','block');
        jQuery(".armaff_add_affiliate_commision_section").hide();

        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: "action=armaff_get_affiliate_commision_setup_content&armaff_setupid=" + armaff_setupid + "&_wpnonce=" + _wpnonce,
            success: function (res) {
                jQuery("#armaff_get_affiliate_commision_loader_img").css('display','none');
                if(res.type == 'success'){
                    var referral_options = res.armaff_options;
                    if(referral_options.arm_affiliate_referral_type == "fixed_rate"){
                        jQuery(".armaff_add_affiliate_commision_section").find('#arm_aff_affiliate_price_type_fixed_rate').prop('checked', true);
                        jQuery(".armaff_add_affiliate_commision_section").find('.arm_aff_price_type_percentage').hide();
                        jQuery(".armaff_add_affiliate_commision_section").find('.arm_aff_price_type_currency').show();
                    } else {
                        jQuery(".armaff_add_affiliate_commision_section").find('#arm_aff_affiliate_price_type_percentage').prop('checked', true);
                        jQuery(".armaff_add_affiliate_commision_section").find('.arm_aff_price_type_percentage').show();
                        jQuery(".armaff_add_affiliate_commision_section").find('.arm_aff_price_type_currency').hide();
                    }

                    jQuery(".armaff_add_affiliate_commision_section").find("input[name='arm_subscription_plan_options[arm_aff_affilaite_rate]']").val(referral_options.arm_affiliate_referral_rate);

                    if(referral_options.arm_affiliate_recurring_referral_disable == 1){
                        jQuery(".armaff_add_affiliate_commision_section").find("input[name='arm_subscription_plan_options[arm_aff_recurring_affiliate_disable_referral]']").prop('checked', true);
                        jQuery(".armaff_add_affiliate_commision_section").find('tr.aff_subscription_affiliate_sub_opt').removeClass('hidden_section');
                    } else {
                        jQuery(".armaff_add_affiliate_commision_section").find("input[name='arm_subscription_plan_options[arm_aff_recurring_affiliate_disable_referral]']").prop('checked', false);
                        jQuery(".armaff_add_affiliate_commision_section").find('tr.aff_subscription_affiliate_sub_opt').addClass('hidden_section');
                    }

                    if(referral_options.arm_affiliate_recurring_referral_type == "fixed_rate"){
                        jQuery(".armaff_add_affiliate_commision_section").find('#arm_aff_recurring_referral_type_fixed_rate').prop('checked', true);
                        jQuery(".armaff_add_affiliate_commision_section").find('.arm_aff_recurring_affiliate_rate_percentage').hide();
                        jQuery(".armaff_add_affiliate_commision_section").find('.arm_aff_recurring_affiliate_rate_currency').show();
                    } else {
                        jQuery(".armaff_add_affiliate_commision_section").find('#arm_aff_recurring_referral_type_percentage').prop('checked', true);
                        jQuery(".armaff_add_affiliate_commision_section").find('.arm_aff_recurring_affiliate_rate_percentage').show();
                        jQuery(".armaff_add_affiliate_commision_section").find('.arm_aff_recurring_affiliate_rate_currency').hide();
                    }

                    jQuery(".armaff_add_affiliate_commision_section").find("input[name='arm_subscription_plan_options[arm_aff_recurring_affilaite_rate]']").val(referral_options.arm_affiliate_recurring_referral_rate);


                    jQuery("#arm_affiliate_id").val(res.armaff_affiliate_id);
                    jQuery("#arm_affiliate_user_id").val(res.armaff_user_id);
                    jQuery("#armaff_edit_affiliate_name td.armaff_affiliate_name").html(res.armaff_username);
                    jQuery("#armaff_edit_affiliate_name").show();
                } else {

                }
                jQuery(".armaff_add_affiliate_commision_section").show();
                arm_icheck_init();
            }
        });

    }
}

jQuery(document).on('click', '.armaff_affiliate_setup_delete_btn', function () {
    var armaff_setup_id = jQuery(this).attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (armaff_setup_id != '' && armaff_setup_id != 0) {
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        dataType: 'json',
        data: "action=armaff_setup_ajax_action&act=delete&id=" + armaff_setup_id + "&_wpnonce=" + _wpnonce,
        success: function (res) {
        if (res.type == 'success') {
            arm_load_affiliates_referral_filtered_grid(res.msg);
        } else {
            armToast(res.msg, 'error');
        }
        }
    });
    }
    hideConfirmBoxCallback();
    return false;
});

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
jQuery(document).ready(function(){
    var page = jQuery("#page").val();
    if(page != 'arm_manage_plans'){        
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
    }

    jQuery(document).on('change', 'input[name="arm_aff_allow_fancy_url"], #arm_aff_referral_var, #arm_aff_id_encoding, #arm_aff_referral_url', function() {
        ARMAFFReferralURLFunc();
    });
    
});
jQuery(function(){ 
    if (jQuery.isFunction(jQuery().autocomplete))
    {
        if(jQuery("#arm_affiliate_user_id").length > 0){
            
            jQuery('#arm_affiliate_user_id').autocomplete({
                minLength: 0,
                delay: 500,
                appendTo: ".arm_auto_user_field",
                source: function (request, response) {
                    var arm_display_admin_user= jQuery('#arm_display_admin_user').val();
                    jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        dataType: 'json',
                        data: "action=get_arm_member_list&txt="+request.term+'&arm_display_admin_user='+arm_display_admin_user,
                        beforeSend: function () {},
                        success: function (res) {
                            jQuery("#arm_users_items").html('');
                            jQuery('#arm_affiliate_user_id').attr('required', 'required');
                            response(res.data);
                        }
                    });
                },
                focus: function() {return false;},
                select: function(event, ui) {
                    var itemData = ui.item;
                    jQuery("#arm_affiliate_user_id").val('');
                    if(jQuery('#arm_users_items .arm_users_itembox_'+itemData.id).length > 0) {
                    } else {
                        var itemHtml = '<div class="arm_users_itembox arm_users_itembox_'+itemData.id+'">';
                        itemHtml += '<input type="hidden" class="arm_auto_user_id_hidden" name="arm_affiliate_user_id_hidden" value="'+itemData.id+'"/>';
                        itemHtml += '<label>'+itemData.label+'<span class="arm_remove_user_selected_itembox">x</span></label>';
                        itemHtml += '</div>';
                        jQuery("#arm_users_items").html(itemHtml);
                        jQuery('#arm_affiliate_user_id').removeAttr('required');
                        jQuery("#arm_affiliate_user_id").val(itemData.label);
                    }
                    jQuery('#arm_users_items').hide();
                    return false;
                },
            }).data('uiAutocomplete')._renderItem = function (ul, item) {
                var itemClass = 'ui-menu-item';
                if(jQuery('#arm_users_items .arm_users_itembox_'+item.id).length > 0) {
                    itemClass += ' ui-menu-item-selected';
                }
                var itemHtml = '<li class="'+itemClass+'" data-value="'+item.value+'" data-id="'+item.id+'" ><a>' + item.label + '</a></li>';
                return jQuery(itemHtml).appendTo(ul);
            };
        }
    }
    jQuery(document).on('click', '.arm_remove_user_selected_itembox', function () {
        jQuery(this).parents('.arm_users_itembox').remove();
        if(jQuery('#arm_users_items .arm_users_itembox').length == 0) {
            jQuery('#arm_affiliate_user_id').attr('required', 'required');
            jQuery('#arm_users_items').hide();
        }
        return false;
    });
});    

function plan_skin_change(id1) {
   
    jQuery('.popup_close_btn').trigger("click");
var $this = jQuery('#arm_setup_clicked_plan_skin');
 var $selectBox = $this.parents('.arm_selectbox');
    var optLabel = $this.attr('data-label');
   
    var optValue = $this.attr('data-value');
    var data_type = $this.attr('data-type');
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


var arm_membership_skin_array = arm_setup_skin_default_color_array();
                    arm_membership_skin_array = jQuery.parseJSON(arm_membership_skin_array);
		
			var plan_skin = jQuery('#arm_setup_clicked_plan_skin').val();
                      
			if( plan_skin === '' ){
				plan_skin = 'skin4';
			}
			for( var id in arm_membership_skin_array[plan_skin] ){
				//console.log( arm_membership_skin_array[plan_skin] );
				var color = arm_membership_skin_array[plan_skin][id];
				jQuery('#'+id).val(color);
				if(jQuery.isFunction(jQuery().colpick)){
					jQuery('#'+id).colpickSetColor(color);
				}
			}


}
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
    	//frmBGColor = frmBGColor.replace('rgba(','');
    	frmBGColor = frmBGColor.replace(')','');
    	frmBGColor = frmBGColor.split(',');
    	//console.log( frmBGColor );
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

/********* Auto Complete End ********/

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

jQuery(document).on('click', '.arm_click_to_copy_text', function () {
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

function armCopyToClipboard(text) {
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
        /* armToast('Link Copied', 'success'); */
    } catch (err) {
    }
    document.body.removeChild(textArea);
    return successful;
}

function armaff_isNumber(evt) {
    var iKeyCode = (evt.which) ? evt.which : evt.keyCode
    if (iKeyCode != 46 && iKeyCode > 31 && (iKeyCode < 48 || iKeyCode > 57)) {
        return false;
    }
    return true;
}
function ARMAFFReferralURLFunc()
{
    var arm_aff_referral_var = jQuery("#arm_aff_referral_var").val();
    var arm_aff_referral_url = jQuery("#arm_aff_referral_url").val();
    var arm_aff_id_encoding = jQuery("#arm_aff_id_encoding").val(); //pain iD:0, MD5 Encoding:MD5, Username:username

    var arm_aff_id_encoding_str = '{affiliate_id}';
    if(arm_aff_id_encoding=='MD5') 
    {
        arm_aff_id_encoding_str = '{affiliate_id}';
    }
    else if(arm_aff_id_encoding=='username')
    {
        arm_aff_id_encoding_str = '{username}';
    }
    else {

    }

    var arm_aff_allow_fancy_url = jQuery('input[name="arm_aff_allow_fancy_url"]:checked').val(); //simple:0, Fancy:1
    console.log('arm_aff_allow_fancy_url=>'+arm_aff_allow_fancy_url);
    if(arm_aff_allow_fancy_url==1)
    {


        var chklastchar_url = arm_aff_referral_url.substr(-1);
        var lastchar_url = "";
        if (chklastchar_url !== '/') {
            lastchar_url = "/";
        }



        var arm_aff_referral_url_final = arm_aff_referral_url+lastchar_url+arm_aff_referral_var+"/"+arm_aff_id_encoding_str;
    }
    else {
        var arm_aff_referral_url_final = arm_aff_referral_url+"?"+arm_aff_referral_var+"="+arm_aff_id_encoding_str;
    }
    //console.log(arm_aff_referral_url_final);
    jQuery("#armaff_referral_url_example").html(arm_aff_referral_url_final);
}