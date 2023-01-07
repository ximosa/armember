(function () {
    jQuery(document).on('click', ".arm_aff_shortcode_insert_btn", function (e) {
        var opt_val = jQuery("#arm_shortcode_other_type").val();
        var code = jQuery('li[data-value="'+opt_val+'"]').attr('data-code');
	if(code != '')
	{
	    //var shortcode = ARMPrepareShortcode(code, jQuery(this).parents('form'));
	    var shortcode = ARMPrepareShortcode(code, jQuery('.arm_shortcode_other_opts_'+opt_val));
	    ARMAFFInsertShortcodeIntoEditor(shortcode);
	    jQuery('.popup_close_btn').trigger('click');
	} else {
	    alert('Invalid Shortcode');
	}
	return false;
    });
    
    function ARMAFFInsertShortcodeIntoEditor(shortcode) {
    	if( typeof wp.blocks != 'undefined' ){
            if( typeof window.arm_props != 'undefined' && window.arm_props_selected == '1'){
                window.arm_props.setAttributes( {'ArmShortcode': shortcode});
                var check_block_content_length = jQuery('#block-'+window.arm_props.clientId).find('.wp-block-armember-armember-shortcode').length;
                if(check_block_content_length>0)
                {
                    jQuery('#block-'+window.arm_props.clientId).find('.wp-block-armember-armember-shortcode').val(shortcode);
                }

            } else if( typeof window.arm_restrict_content_props != 'undefined' && window.arm_props_selected == '2'){
                window.arm_restrict_content_props.setAttributes({'ArmRestrictContent':shortcode});
                var check_block_content_length = jQuery('#block-'+window.arm_restrict_content_props.clientId).find('.wp-block-armember-armember-restrict-content-textarea').length;
                if(check_block_content_length>0)
                {
                    jQuery('#block-'+window.arm_restrict_content_props.clientId).find('.wp-block-armember-armember-restrict-content-textarea').val(shortcode);
                }
                
            }
        } else {
            if (jQuery('div#wp-content-wrap').length && jQuery('div#wp-content-wrap').hasClass('html-active')) {
                insertAtCaret('content', shortcode);
            } else {
                if (typeof tinyMCE == 'object') {
                    tinyMCE.activeEditor.execCommand('mceInsertContent', false, shortcode);
                    tinyMCE.activeEditor.execCommand('mceRepaint');
                }
            }
        }
    }
    
    function insertAtCaret(areaId, text) {
	var txtarea = document.getElementById(areaId);
	var scrollPos = txtarea.scrollTop;
	var strPos = 0;
	var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
	    "ff" : (document.selection ? "ie" : false));
	if (br == "ie") {
	    txtarea.focus();
	    var range = document.selection.createRange();
	    range.moveStart('character', -txtarea.value.length);
	    strPos = range.text.length;
	}
	else if (br == "ff")
	    strPos = txtarea.selectionStart;

	var front = (txtarea.value).substring(0, strPos);
	var back = (txtarea.value).substring(strPos, txtarea.value.length);
	txtarea.value = front + text + back;
	strPos = strPos + text.length;
	if (br == "ie") {
	    txtarea.focus();
	    var range = document.selection.createRange();
	    range.moveStart('character', -txtarea.value.length);
	    range.moveStart('character', strPos);
	    range.moveEnd('character', 0);
	    range.select();
	}
	else if (br == "ff") {
	    txtarea.selectionStart = strPos;
	    txtarea.selectionEnd = strPos;
	    txtarea.focus();
	}
	txtarea.scrollTop = scrollPos;
    }
    
    function ARMPrepareShortcode(code, form) {

	var shortcode = '';
        switch(code)
	{
	    case 'arm_affiliate':
                var opt = '';
                var scode_transaction_opts = jQuery(form).find(':input:not(.arm_spf_active_checkbox)').serializeArray();
                if( scode_transaction_opts !== '' ){
                        jQuery(scode_transaction_opts).each(function(i,e){
                                opt += ' ' + e.name + '="' + e.value + '"';
                        });
                }

                var socialFields = '';
                var socialProfileFields = jQuery(form).find('.arm_aff_share_field_item :input').serializeArray();
                if (socialProfileFields != '') {
                    jQuery(socialProfileFields).each(function (i, e) {
                        socialFields += e.value + ',';
                    });
                }

                shortcode = '[' + code + ' social_fields="' + socialFields + '"'+ opt + ']';
                break;
            case 'arm_user_referral':
                var opt = '';
                var scode_transaction_opts = jQuery(form).find(':input:not(.arm_referral_transaction_fields)').serializeArray();
                if( scode_transaction_opts !== '' ){
                        jQuery(scode_transaction_opts).each(function(i,e){
                                opt += ' ' + e.name + '="' + e.value + '"';
                        });
                }
                //var scode_transaction_fields = jQuery('.arm_icheckbox.arm_member_transaction_fields').serializeArray();
                //console.log(arm_member_transaction_fields);
                var opt_check = " label='";
                var opt_label = " value='";
                jQuery('.arm_icheckbox.arm_referral_transaction_fields').each(function(){
                        if( jQuery(this).is(':checked') ){
                                var name = jQuery(this).attr('name');
                                var value = jQuery(this).val();
                                var input_value = jQuery('input[name="arm_transaction_field_label_'+value+'"]').val();
                                opt_check += value +",";
                                opt_label += input_value + ",";
                        }
                });
                opt_check += "'";
                opt_label += "'";
                opt += " " + opt_check + " " + opt_label;
                shortcode = '[' + code + opt + ']';
                break;
            case 'arm_user_payout_transaction':
                var opt = '';
                var scode_transaction_opts = jQuery(form).find(':input:not(.arm_payouts_transaction_fields)').serializeArray();
                if( scode_transaction_opts !== '' ){
                        jQuery(scode_transaction_opts).each(function(i,e){
                                opt += ' ' + e.name + '="' + e.value + '"';
                        });
                }
                //var scode_transaction_fields = jQuery('.arm_icheckbox.arm_member_transaction_fields').serializeArray();
                //console.log(arm_member_transaction_fields);
                var opt_check = " label='";
                var opt_label = " value='";
                jQuery('.arm_icheckbox.arm_payouts_transaction_fields').each(function(){
                        if( jQuery(this).is(':checked') ){
                                var name = jQuery(this).attr('name');
                                var value = jQuery(this).val();
                                var input_value = jQuery('input[name="arm_payment_field_label_'+value+'"]').val();
                                opt_check += value +",";
                                opt_label += input_value + ",";
                        }
                });
                opt_check += "'";
                opt_label += "'";
                opt += " " + opt_check + " " + opt_label;
                shortcode = '[' + code + opt + ']';
                break;
            case 'arm_aff_banner':
                var opt = '';
                var scode_transaction_opts = jQuery(form).find(':input:not(.arm_spf_active_checkbox)').serializeArray();
                if( scode_transaction_opts !== '' ){
                        jQuery(scode_transaction_opts).each(function(i,e){
                                opt += ' ' + e.name + '="' + e.value + '"';
                        });
                }
                
                var socialFields = '';
                var socialProfileFields = jQuery(form).find('.arm_aff_share_field_item :input').serializeArray();
                if (socialProfileFields != '') {
                    jQuery(socialProfileFields).each(function (i, e) {
                        socialFields += e.value + ',';
                    });
                }

                shortcode = '[' + code + ' social_fields="' + socialFields + '"'+ opt + ']';
                
                break;

            case 'armaff_conditional_content':

                var conditional_code = jQuery(form).find('#armaff_conditional_content').val();
                var conditional_content = jQuery(form).find('textarea[name="armaff_conditional_text"]').val();
                if(conditional_content == ''){
                    conditional_content = 'Content Goes Here';
                }
                shortcode = '[' + conditional_code + ']'+ conditional_content +'[/' + conditional_code + ']';

                break;
	    default:
		var scode_opts = jQuery(form).find(':input').serializeArray();
		var opt = '';
		if (scode_opts != '') {
		    jQuery(scode_opts).each(function (i, e) {
			opt += ' ' + e.name + '="' + e.value + '"';
		    });
		}
		shortcode = '[' + code + ' ' + opt + ']';
		if(code == 'arm_content') {
		    shortcode += 'Content Goes Here[/' + code + ']';
		}
		break;
	}
	return shortcode;
    }
    arm_selectbox_init();
})();