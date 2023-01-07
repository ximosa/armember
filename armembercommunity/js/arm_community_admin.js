function apply_com_activity_bulk_action(id) {
    jQuery('#' + id).val('true');
    jQuery('.popup_close_btn').trigger("click");
    jQuery('#arm_com_activity_list_form').submit();
}
function arm_com_activity_list_form_bulk_action() {
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
            var str = jQuery('#arm_com_activity_list_form').serialize();

            jQuery.ajax({type: "POST", url: ajaxurl, data: "action=arm_com_activity_bulk_action&" + str, dataType: 'json',
                success: function (res) {
                    if (res.type == 'success')
                    {
                        armToast(res.msg, 'success');
                        arm_load_com_activity_grid_after_filtered();
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
jQuery(document).on('click', '.arm_com_activity_delete_btn', function () {
    var activity_id = jQuery(this).attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (activity_id != '' && activity_id != 0) {
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: "action=arm_com_activity_remove_by_admin&activity_id=" + activity_id + "&_wpnonce=" + _wpnonce,
            success: function (res) {
                if (res.type == 'success') {
                    armToast(res.msg, 'success');
                    arm_load_com_activity_grid_after_filtered();
                } else {
                    armToast(res.msg, 'error');
                }
            }
        });
    }
    hideConfirmBoxCallback();
    return false;
});
function apply_com_post_bulk_action(id) {
    jQuery('#' + id).val('true');
    jQuery('.popup_close_btn').trigger("click");
    jQuery('#arm_com_post_list_form').submit();
}
function arm_com_post_list_form_bulk_action() {
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
            var str = jQuery('#arm_com_post_list_form').serialize();
            jQuery.ajax({type: "POST", url: ajaxurl, data: "action=arm_com_post_bulk_action&" + str, dataType: 'json',
                success: function (res) {
                    if (res.type == 'success')
                    {
                        armToast(res.msg, 'success');
                        arm_load_com_post_grid_after_filtered();
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
jQuery(document).on('click', '.arm_com_post_delete_btn', function () {
    var post_id = jQuery(this).attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (post_id != '' && post_id != 0) {
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: "action=arm_com_post_remove_by_admin&post_id=" + post_id + "&_wpnonce=" + _wpnonce,
            success: function (res) {
                if (res.type == 'success') {
                    armToast(res.msg, 'success');
                    arm_load_com_post_grid_after_filtered();
                } else {
                    armToast(res.msg, 'error');
                }
            }
        });
    }
    hideConfirmBoxCallback();
    return false;
});
jQuery(document).on('change', '#arm_com_friendship', function () {
    if (jQuery(this).is(':checked')) {
        jQuery('.arm_com_friendship_section').removeClass('hidden_section');
    } else {
        jQuery('.arm_com_friendship_section').addClass('hidden_section');
    }
});
jQuery(document).on('change', '#arm_com_private_message', function () {
    if (jQuery(this).is(':checked')) {
        jQuery('.arm_com_message_section').removeClass('hidden_section');
    } else {
        jQuery('.arm_com_message_section').addClass('hidden_section');
    }
});
jQuery(document).on('change', '#arm_com_follow', function () {
    if (jQuery(this).is(':checked')) {
        jQuery('.arm_com_follow_section').removeClass('hidden_section');
    } else {
        jQuery('.arm_com_follow_section').addClass('hidden_section');
    }
});
jQuery(document).on('change', '#arm_com_review', function () {
    if (jQuery(this).is(':checked')) {
        jQuery('.arm_com_review_section').removeClass('hidden_section');
    } else {
        jQuery('.arm_com_review_section').addClass('hidden_section');
    }
});
jQuery(document).on('change', '#arm_com_post', function() {
    if (jQuery(this).is(':checked')) {
        jQuery('.arm_com_post_fimage_section').removeClass('hidden_section');
    } else {
        jQuery('.arm_com_post_fimage_section').addClass('hidden_section');
    }
});
jQuery(document).on('change', '#arm_com_post_wall', function() {
    if (jQuery(this).is(':checked')) {
        jQuery('.arm_com_wll_section_lbl_tr').removeClass('hidden_section');
    } else {
        jQuery('.arm_com_wll_section_lbl_tr').addClass('hidden_section');
    }
});
jQuery(document).on('change', '#arm_com_activity', function() {
    if (jQuery(this).is(':checked')) {
        jQuery('.arm_com_activity_section').removeClass('hidden_section');
    } else {
        jQuery('.arm_com_activity_section').addClass('hidden_section');
    }
});
jQuery(document).on('click', '#arm_com_settings_btn', function () {
    var arm_com_data = jQuery('#arm_com_settings').serialize();
    var $this = jQuery(this);
    if (!$this.hasClass('arm_already_clicked')) {
        var error_count = 0;

        if( jQuery("#arm_com_post_slug").val().trim() == "" ) {
            error_count++;
            jQuery("#arm_com_post_slug").focus().addClass("arm_com_input_error");
            jQuery(".arm_com_post_slug_error").css("visibility", "visible");

            jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: jQuery('.arm_com_post_slug_error').offset().top - 130}, 300);
        }
        else {
            jQuery("#arm_com_post_slug").removeClass("arm_com_input_error");
            jQuery(".arm_com_post_slug_error").css("visibility", "hidden");
        }

        if (error_count > 0) {
            return false;
        } else {
            $this.addClass('arm_already_clicked');
            $this.attr('disabled', 'disabled');
            jQuery('#arm_loader_img').show();
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: 'action=arm_community_save_settings&' + arm_com_data,
                success: function (response)
                {
                    if (response.type == 'success')
                    {
                        var msg = (response.msg != '') ? response.msg : armMessages.saveSettingsSuccess;
                        armToast(msg, 'success');
                        arm_tipso_init();
                        setTimeout(function () {
                            location.reload(true);
                        }, 1000);
                    } else {
                        var msg = (response.msg != '') ? response.msg : armMessages.saveSettingsError;
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
jQuery(document).ready(function () {
    arm_icheck_init();
    arm_selectbox_init();
    arm_tipso_init();

    jQuery('.arm_direct_logins_form_role').find('dt').css('width', '328px');
    jQuery('.arm_direct_logins_form_hours').find('dt').css('width', '145px');
    jQuery('.arm_direct_logins_form_days').find('dt').css('width', '145px');

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
    jQuery('#arm_filter_pstart_date, #arm_filter_pend_date').datetimepicker({
        useCurrent: false,
        format: 'MM/DD/YYYY',
        locale: '',
        maxDate: new Date()
    });
});
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
jQuery(document).on('keyup', '.arm_selectbox dt input.arm_payment_transaction_users', function (e) {
    var excludeKeys = [16, 17, 18, 19, 20, 33, 34, 35, 36, 37, 38, 39, 40, 45, 91, 92, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 144, 145];
    if (jQuery.inArray(e.keyCode, excludeKeys) === -1) {
        var value = jQuery(this).val();
        value = value.toLowerCase();
        var lists = jQuery('#arm_all_users').html();
        var object = jQuery.parseJSON(lists);
        jQuery('.arm_payment_members_list').remove();
        var select_user_label = typeof __SELECT_USER !== 'undefined' ? __SELECT_USER : 'Type username to select user';
        var html = "<li class='arm_payment_members_list' data-label='" + select_user_label + "' data-value='" + select_user_label + "'> Type username to select user </li>";
        for (var n in object) {
            var obj = object[n];
            var obj_val = obj.user_login;
            obj_val = obj_val.toLowerCase();
            html += (obj_val.indexOf(value) != -1) ? "<li class='arm_payment_members_list' data-label='" + obj_val + "' data-value='" + obj.ID + "' >" + obj_val + "</li>" : "";
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
    jQuery('#arm_setup_clicked_plan_skin').attr('data-value', optValue);
    jQuery('#arm_setup_clicked_plan_skin').attr('data-label', optLabel);
    jQuery('#arm_setup_clicked_plan_skin').attr('data-type', data_type);
    if (jQuery(this).parent().attr('data-id') == 'arm_setup_plan_skin') {

        jQuery('#plan_skin_change_message').bPopup({closeClass: 'popup_close_btn'});
        return false;
    }
    if (jQuery(this).parent().attr('data-id') == 'arm_form_opacity') {
        var frmBGColor = jQuery('.arm_editor_form_fileds_wrapper').css('background-color');
        var form_opacity = optValue;
        frmBGColor = frmBGColor.replace(/(rgb\()|(rgba\()/g, '');
        frmBGColor = frmBGColor.replace(')', '');
        frmBGColor = frmBGColor.split(',');
        console.log(frmBGColor);
        var frmR = frmBGColor[0].trim();
        var frmG = frmBGColor[1].trim();
        var frmB = frmBGColor[2].trim();
        var newColor = "rgba(" + frmR + "," + frmG + "," + frmB + "," + form_opacity + ")";
        var style_attr = jQuery('.arm_editor_form_fileds_wrapper').attr('style');
        if (typeof style_attr != 'undefined') {
            jQuery('.arm_editor_form_fileds_wrapper').css('background-color', newColor);
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
    setTimeout(function () {
        jQuery('input#' + id).attr("value", optValue);
    }, 500);
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
    if ($thisLI.find('input[type="checkbox"]').is(':checked')) {
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
jQuery(document).on('click', '.arm_com_review_delete_btn', function () {
    var review_id = jQuery(this).attr('data-item_id');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (review_id != '' && review_id != 0) {
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: "action=arm_com_review_remove_by_admin&review_id=" + review_id + "&_wpnonce=" + _wpnonce,
            success: function (res) {
                if (res.type == 'success') {
                    armToast(res.msg, 'success');
                    arm_load_com_review_grid_after_filtered();
                } else {
                    armToast(res.msg, 'error');
                }
            }
        });
    }
    hideConfirmBoxCallback();
    return false;
});
function apply_com_review_bulk_action(id) {
    jQuery('#' + id).val('true');
    jQuery('.popup_close_btn').trigger("click");
    jQuery('#arm_com_review_list_form').submit();
}
function arm_com_review_list_form_bulk_action() {
    var action1 = jQuery('[name="action1"]').val();
    var action_delete = jQuery('#bulk_delete_flag').val();
    var chk_count = jQuery('input[name="item-action[]"]:checked').length;
    if (chk_count > 0)
    {
        var arm_action_type = "";

        if (action1 == '' || action1 == '-1') {
            armToast(armbulkActionError, 'error');
        }
        else if(action1 == "approve_review") {
            arm_action_type = "approve_review";
        }
        else if(action1 == "disapprove_review") {
            arm_action_type = "disapprove_review";
        }
        else {
            arm_action_type = "delete_review";
            if (action_delete == 'false') {
                jQuery('#delete_bulk_form_message').bPopup({closeClass: 'popup_close_btn'});
                return false;
            }
        }        

        if(arm_action_type != "") {
            jQuery('#bulk_delete_flag').val('false');
            jQuery("#arm_com_review_list_form #arm_action_type").val(arm_action_type);
            var str = jQuery('#arm_com_review_list_form').serialize();

            jQuery.ajax({
                url: ajaxurl,
                type: "POST",
                data: "action=arm_com_review_bulk_action&" + str,
                dataType: 'json',
                success: function (res) {
                    if (res.type == 'success')
                    {
                        armToast(res.msg, 'success');
                        arm_load_com_review_grid_after_filtered();
                    } else {
                        armToast(res.msg, 'error');
                    }
                }
            });
        }
    }
    else {
        armToast(armpleaseselect, 'error');
    }
    return false;
}
jQuery(document).on("click", ".arm_approve_review_nav", function() {
    var arm_review_id = jQuery(this).attr("data-arm_review_id");
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if(arm_review_id != "" && arm_review_id > 0) {
        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: "action=arm_com_review_approve&review_id=" + arm_review_id + "&_wpnonce=" + _wpnonce,
            dataType: 'json',
            success: function (res) {
                if (res.type == 'success')
                {
                    armToast(res.msg, 'success');
                    arm_load_com_review_grid_after_filtered();
                } else {
                    armToast(res.msg, 'error');
                }
                jQuery(".tipso_bubble").remove();
            }
        });
    }
    return false;
});
jQuery(document).on("click", ".arm_disapprove_review_nav", function() {
    var arm_review_id = jQuery(this).attr("data-arm_review_id");
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if(arm_review_id != "" && arm_review_id > 0) {
        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: "action=arm_com_review_disapprove&review_id=" + arm_review_id + "&_wpnonce=" + _wpnonce,
            dataType: 'json',
            success: function (res) {
                if (res.type == 'success')
                {
                    armToast(res.msg, 'success');
                    arm_load_com_review_grid_after_filtered();
                } else {
                    armToast(res.msg, 'error');
                }
                jQuery(".tipso_bubble").remove();
            }
        });
    }
    return false;
});
jQuery(document).on("click", ".arm_edit_review_nav", function() {
    var review_id = jQuery(this).attr("data-arm_review_id");
    var user_from = jQuery(this).attr('data-user_from');
    var user_to = jQuery(this).attr('data-user_to');
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: 'action=arm_get_user_reviews&user_from=' + user_from + '&user_to=' + user_to + "&_wpnonce=" + _wpnonce,
        success: function (response)
        {
            jQuery('.arm_edit_review_popup').html("").html(response.content);
            jQuery('.arm_edit_com_review_popup').bPopup({
                opacity: 0.5,
                closeClass: 'popup_close_btn',
                follow: [false, false]
            });
            jQuery("#arm_com_give_review_form").append("<input type='hidden' name='arm_com_review_edit_id' value='"+review_id+"'>");
        }
    });
});
jQuery(document).on('click', '#arm_com_review_btn', function () {
    var form = jQuery(this).parents('form');
    var error_count = 0;
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (form.find('input[name=arm_rating]').length > 0) {
        if (form.find('input[name=arm_rating]:checked').length <= 0) {
            form.find('.arm_com_review_rating_error').show();
            error_count++;
        } else {
            form.find('.arm_com_review_rating_error').hide();
        }
    }

    if (form.find('input[name=arm_popup_rating]').length > 0) {
        if (form.find('input[name=arm_popup_rating]:checked').length <= 0) {
            form.find('.arm_com_review_rating_error').show();
            error_count++;
        } else {
            form.find('.arm_com_review_rating_error').hide();
        }
    }

    if (form.find('#arm_title').val() == '') {
        form.find('.arm_com_review_title_error').show();
        error_count++;
    } else {
        form.find('.arm_com_review_title_error').hide();
    }

    if (form.find('#arm_description').val() == '') {
        form.find('.arm_com_review_desc_error').show();
        error_count++;
    } else {
        form.find('.arm_com_review_desc_error').hide();
    }

    if (error_count > 0) {
        return false;
    } else {
        var form_data = form.serialize();
        var $this = jQuery(this);
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: 'action=arm_com_review_edit_by_admin&' + form_data + "&_wpnonce=" + _wpnonce,
            dataType: 'json',
            success: function (response)
            {
                if(response.type == "success") {
                    arm_load_com_review_grid_after_filtered();
                }
                jQuery('.arm_edit_com_review_popup').bPopup().close();
                armToast(response.msg, response.type);
            }
        });
    }
    return false;
});
jQuery(document).on("click", ".arm_com_post_comment_view_nav", function() {
    var $this = jQuery(this);
    var post_id = $this.attr("data-post_id");
    jQuery(".arm_form_field_label_wrapper_text").text( $this.parents("tr").find("td:eq(1)").text().trim() );
    jQuery("#arm_com_post_comment_list_form").hide();
    jQuery('.arm_com_view_post_comment_popup').bPopup({
        opacity: 0.5,
        closeClass: 'popup_close_btn',
        follow: [false, false]
    }, function() {
        arm_load_com_post_comment_grid(post_id);
        jQuery("#arm_com_post_comment_list_form").show();
    });
    return false;
});
jQuery(document).on("click", ".arm_com_post_comment_delete_btn", function() {
    var comment_id = jQuery(this).attr("data-item_id");
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (comment_id != '' && comment_id > 0) {
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: "action=arm_com_post_comment_remove_by_admin&comment_id=" + comment_id + "&_wpnonce=" + _wpnonce,
            success: function (res) {
                if (res.type == 'success') {
                    armToast(res.msg, 'success');
                    arm_load_com_post_comment_grid(arm_com_post_comment_id);
                } else {
                    armToast(res.msg, 'error');
                }
            }
        });
    }
    hideConfirmBoxCallback();
    return false;
});
jQuery(document).on("click", ".arm_com_post_comment_status_nav", function() {
    var post_id = jQuery(this).attr("data-arm_post_id");
    var comment_id = jQuery(this).attr("data-arm_comment_id");
    var status_change_to = jQuery(this).attr("data-arm_status_change_to");
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (comment_id != '' && comment_id > 0) {
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: "action=arm_com_change_comment_status&comment_id=" + comment_id + "&status_change_to=" + status_change_to + "&_wpnonce=" + _wpnonce,
            success: function (res) {
                if (res.type == 'success') {
                    armToast(res.msg, 'success');
                    arm_load_com_post_comment_grid(post_id);
                } else {
                    armToast(res.msg, 'error');
                }
                jQuery(".tipso_bubble.small.top").remove();
            }
        });
    }
});
jQuery(document).on("click", ".arm_com_user_activity_status_nav", function() {
    var arm_activity_id = jQuery(this).attr("data-arm_activity_id");
    var status_change_to = jQuery(this).attr("data-arm_status_change_to");
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();
    if (arm_activity_id != '' && arm_activity_id > 0) {
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: "action=arm_com_change_activity_status&activity_id=" + arm_activity_id + "&status_change_to=" + status_change_to + "&_wpnonce=" + _wpnonce,
            success: function (res) {
                if (res.type == 'success') {
                    armToast(res.msg, 'success');
                    arm_load_com_activity_grid_after_filtered();
                } else {
                    armToast(res.msg, 'error');
                }
                jQuery(".tipso_bubble.small.top").remove();
            }
        });
    }
});