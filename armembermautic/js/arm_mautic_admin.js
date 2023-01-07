
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

function show_mautic_tool_verify_btn(id) {
    jQuery('#arm_mautic_base_url_error').hide();
    jQuery('#arm_mautic_base_url_invalid_error').hide();
    jQuery('#arm_mautic_public_key_error').hide();
    jQuery('#arm_mautic_secret_key_error').hide();
    jQuery('#arm_mautic_error').hide();
  
}

function refresh_mautic_tool(act)
{
    if (act == 'delete') {
        if (confirm(delOptInsConfirm)) {
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: "action=arm_delete_mautic_config&id=mautic",
                success: function (res) {
                    if (res.type == 'success')
                    jQuery('#arm_mautic_base_url').val('');
                    jQuery('#arm_mautic_public_key').val('');
                    jQuery('#arm_mautic_secret_key').val('');
                    jQuery('#arm_mautic_list_tr').hide();
                    jQuery('#arm_mautic_link').hide();
                    jQuery('#arm_mautic_varify').hide();
                    jQuery('#arm_mautic_error').hide();
                    jQuery('#arm_mautic_status').val('0');
                    
                    //jQuery('#arm_mautic_action_link').hide();
                    //jQuery('#arm_mautic_link').css('display', 'inline');
                    //jQuery('#arm_mautic_status').val('0');
                    //jQuery('#arm_mautic_verify').removeAttr('style');
                    //jQuery('#arm_mautic_verify').css('color', 'green');
                    //jQuery('#arm_mautic_list').html('');
                    //jQuery('#arm_mautic_dl').addClass('disabled');
                    //jQuery('#arm_mautic_dl span').html('');
                    //jQuery('#mautic_list_name').val('');
                }
            });
        } else {
            return false;
        }
    } else if (act == 'refresh') {
        verify_mautic_tool('mautic', '1');
    }
}

function verify_mautic_tool(id, refresh_li)
{
    if (id == 'mautic') {
        var base_url = jQuery('#arm_mautic_base_url').val();
        var public_key = jQuery('#arm_mautic_public_key').val();
        var secret_key = jQuery('#arm_mautic_secret_key').val();
        var error_count = 0;
        if (base_url === '') {
            jQuery('#arm_mautic_base_url').css('border-color', '#ff0000');
            jQuery('#arm_mautic_base_url_error').show();
            jQuery('#arm_mautic_base_url_invalid_error').hide();
            error_count++;
        } else {
            jQuery('#arm_mautic_base_url_error').hide();
            var reg_ex = /^(http[s]?:\/\/){0,1}(www\.){0,1}[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,5}[\.]{0,1}/;
            if (!reg_ex.test(base_url)) {
                jQuery('#arm_mautic_base_url').css('border-color', '#ff0000');
                jQuery('#arm_mautic_base_url_invalid_error').show();
                jQuery('#arm_mautic_base_url_error').hide();
                error_count++;
            } else {
                jQuery('#arm_mautic_base_url').css('border-color', '');
                jQuery('#arm_mautic_base_url_invalid_error').hide();
                jQuery('#arm_mautic_base_url_error').hide();
            }
        }
        if (public_key === '') {
            jQuery('#arm_mautic_public_key').css('border-color', '#ff0000');
            jQuery('#arm_mautic_public_key_error').show();
            error_count++;
        } else {
            jQuery('#arm_mautic_public_key').css('border-color', '');
            jQuery('#arm_mautic_public_key_error').hide();
        }
        if (secret_key === '') {
            jQuery('#arm_mautic_secret_key').css('border-color', '#ff0000');
            jQuery('#arm_mautic_secret_key_error').show();
            error_count++;
        } else {
            jQuery('#arm_mautic_secret_key').css('border-color', '');
            jQuery('#arm_mautic_secret_key_error').hide();
        }


        if (error_count <= 0) {

            jQuery('.arm_loading').fadeIn('slow');
            if (refresh_li !== '1') {

            } else {
                jQuery('#arm_mautic_verify').hide();
                jQuery('#arm_mautic_refresh').hide();
            }

            var redirect_url = jQuery('#mautic_url').val();
            //redirect_url = redirect_url.replace('[baseurl]', base_url);
            //redirect_url = redirect_url.replace('[public_key]', public_key);
            //redirect_url = redirect_url.replace('[secret_key]', secret_key);

            var mautic_Window = window.open(redirect_url, '', 'width=800,height=300,scrollbars=yes');

            var interval = setInterval(function () {
                if (mautic_Window.closed) {
                    clearInterval(interval);
                    jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        dataType: 'json',
                        data: "action=arm_get_mautic_segemnets",
                        success: function (res) {
                            if (res.type == 'success') {
                                var list = res.list;
                                jQuery('ul#arm_mautic_list').html(list);
                                jQuery('#arm_mautic_status').val('1');
                               // jQuery('ul#arm_mautic_list li:first').trigger('click');
                               
                               if(res.first_option != '' && res.first_id != '')
                               {
                                   jQuery('#mautic_list_name').val(res.first_id);
                                   jQuery('#arm_mautic_dl span').html(res.first_option);
                               }
                            } else {
                                jQuery('#arm_mautic_error').show(); 
                            }
                        }
                    });
                    
                    jQuery('.arm_loading').fadeOut();
                }
            }, 500);
        }
    }

    arm_selectbox_init();
    return false;
}