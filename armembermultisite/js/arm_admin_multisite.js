"use strict";

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

    jQuery(document).on('click', '.arm_change_user_status_ok_btn', function () {
        var total_columns = parseInt(jQuery('#total_members_grid_columns').val());
        var $this = jQuery(this);
        var tr = jQuery(this).closest('tr');
        var dRow = jQuery(this).closest('tr')[0];
        var site_id = jQuery(this).attr('data-item_id');
        var new_status = jQuery(this).attr('data-status');
        
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();
        if (new_status != '') {
            jQuery('.arm_loading_grid').fadeIn('slow');
            jQuery.ajax({
                type: "POST",
                url: __ARMAJAXURL,
                dataType: 'json',
                data: "action=arm_multisite_subsite_update_status&site_id=" + site_id + "&new_status=" + new_status + "&_wpnonce=" + _wpnonce,
                success: function (res) {
                    if (res.type == "success") {
                        jQuery('#armember_datatable').dataTable().fnUpdate(res.status_label, dRow, 4, 0);
                        var parent_node = jQuery($this).parent("a");
                        jQuery(parent_node).html("");
                        jQuery(parent_node).html(res.status_content);
                        jQuery(parent_node).find(".arm_change_user_status_ok_btn").attr('data-status',res.status);
                        arm_tipso_init();
                        jQuery('#armember_datatable').dataTable().fnAdjustColumnSizing(false);
                    } else {
                        armToast(res.msg, 'error');
                    }
                    jQuery('.arm_loading_grid').fadeOut();
                }
            });
        }
        return false;
    });

    jQuery(document).on('click', '.arm_subsite_spam_btn', function () {
        var $this = jQuery(this);
        var site_id = jQuery(this).attr('data-item_id');
        var spam_status = jQuery(this).attr('data-status');
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();

        if( spam_status != '' ) {
            jQuery('.arm_loading_grid').fadeIn('slow');
            jQuery.ajax({
                type: "POST",
                url: __ARMAJAXURL,
                dataType: 'json',
                data: "action=arm_multisite_subsite_update_spam&site_id=" + site_id + "&status=" + spam_status + "&_wpnonce=" + _wpnonce,
                success: function (res) {
                    if (res.type == "success") {
                        jQuery($this).attr('data-status',res.status)
                        jQuery('#armember_datatable').dataTable().fnAdjustColumnSizing(false);
                    } else {
                        armToast(res.msg, 'error');
                    }
                    jQuery('.arm_loading_grid').fadeOut();
                }
            });
        }
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

    jQuery(document).on('click', '#site_deactive' ,function(){
        var userid= jQuery(this).attr('data-userid');
        var siteid = jQuery(this).attr('data-siteid');
        var site_action=jQuery(this).text();

        jQuery.ajax({
            type:'POST',
            url:__ARMAJAXURL,
            data:'action=arm_multisite_deactive&userid='+userid+'&site_action='+site_action+'&siteid='+siteid,
            dataType:'json',
            success:function(res){
                jQuery('#blog_status_'+siteid).text(res.site_status);
                jQuery('.site_deactive_'+siteid).text(res.site_action);

            }

        });
    });

    jQuery(document).on('change', '#enable_disable_planwise_multisite', function () {
        
        if (jQuery(this).is(':checked')) {
            jQuery('.arm_multisite_create_box, .arm_multisite_user_role_box').removeClass('hidden_section');
        } else {
            jQuery('.arm_multisite_create_box, .arm_multisite_user_role_box').addClass('hidden_section');
        }
    });

    jQuery(document).on('click', '.arm_mutisite_subsite_delete_btn', function() {
        var site_id = jQuery(this).attr('data-item_id');
        var user_id = jQuery(this).attr('data-userid');
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();
        if (site_id != '' && site_id != 0 && user_id != '' && user_id != 0) {
            jQuery('.arm_loading_grid').fadeIn('slow');
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                dataType: 'json',
                data: "action=arm_multisite_subsite_ajax_action&act=delete&id=" + site_id + "&user_id=" + user_id + "&_wpnonce=" + _wpnonce,
                success: function (res) {
                    if (res.type == 'success') {
                        armToast(res.msg, 'success');
                        var row = jQuery('#arm_multisite_' + site_id)[0];
                        arm_load_item_grid_after_filtered(row);
                        jQuery('.arm_loading_grid').fadeOut();
                    } else {
                        armToast(res.msg, 'error');
                        jQuery('.arm_loading_grid').fadeOut();
                    }
                }
            });
        }
        hideConfirmBoxCallback();
        return false;
    });

    jQuery(document).on('click', '#arm_multisite_subsite_list_form .arm_selectbox, #arm_multisite_subsite_list_form .arm_multiple_selectbox', function () {
        jQuery(this).find('dd ul').toggle();
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

function showConfirmBoxCallback(site_id,userid) {
    if (site_id != '') {
    var deleteBox = jQuery('#arm_confirm_box_' + site_id);
    deleteBox.find('button.arm_mutisite_subsite_delete_btn').attr('data-userid',userid)
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

function arm_item_bulk_action(id) {
    jQuery('#' + id).val('true');
    jQuery('.popup_close_btn').trigger("click");
    jQuery('#arm_multisite_subsite_list_form').submit();
}

function arm_multisite_subsite_list_form_bulk_action(){
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
                jQuery('.arm_loading_grid').fadeIn('slow');
                jQuery('#bulk_delete_flag').val('false');
                var str = jQuery('#arm_multisite_subsite_list_form').serialize();
                
                jQuery.ajax({type: "POST", url: ajaxurl, data: "action=arm_multisite_subsite_bulk_action&" + str, dataType: 'json',
                    success: function (res) {
                        if (res.type == 'success')
                        {
                            armToast(res.msg, 'success');
                            var row = jQuery('input[name="item-action[]"]:checked');
                            arm_load_item_grid_after_filtered_bulk(row);
                            jQuery('.arm_loading_grid').fadeOut();
                        } else {
                            armToast(res.msg, 'error');
                            jQuery('.arm_loading_grid').fadeOut();
                        }
                    }
                });
        }
    } else {
        armToast(armpleaseselect, 'error');
    }
    return false;
}
