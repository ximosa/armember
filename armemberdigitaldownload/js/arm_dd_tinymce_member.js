(function () {

    jQuery(document).on('click', ".arm_dd_shortcode_insert_btn", function (e) {
    var shortcode = '', args = '';
    var code = jQuery(this).attr('data-code');

    if (code != '')
    {
        if (typeof tinyMCE != 'undefined' && jQuery.isFunction(tinyMCE.triggerSave)) {
    	tinyMCE.triggerSave();
        }
        var $ddForm = jQuery('form.arm_digital_download_form');
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
    	    	else if(typeof e.name != 'undefined' && e.name=='show_size'){
    	    		if($ddForm.find('#arm_dd_display_file_size_input').is(':checked')){
    	    			e.value = true;	
    	    		}else{
    	    			e.value = false;	
    	    		}
    	    	}
    	    	else if(typeof e.name != 'undefined' && e.name=='show_download_count'){
    	    		if($ddForm.find('#arm_dd_display_download_count_input').is(':checked')){
    	    			e.value = true;	
    	    		}else{
    	    			e.value = false;	
    	    		}
    	    	}else if(typeof e.name != 'undefined' && e.name=='link_type' && e.value=='link'){

    	    		e.value = '';

    	    	}
    	    
    	    if(typeof e.value != 'undefined' && e.value!=''){
    		
    			args += ' ' + e.name + '="' + e.value + '"';
    		}
    	    
    	});

    	shortcode = '[' + code + ' ' + args + ']';
    	
    	ARMDDInsertShortcodeIntoEditor(shortcode);
    	jQuery('.popup_close_btn').trigger('click');
        }
    } else {
        alert('Invalid Shortcode');
    }
    return false;
    });

    jQuery(document).on('change','#arm_dd_item_id',function(e){

        if(jQuery(this).val()>0){
            jQuery('.arm_dd_shortcode_insert_btn').prop("disabled", false);  
        }else{
            jQuery('.arm_dd_shortcode_insert_btn').prop("disabled", true);   
        }
        
    });

    function ARMDDInsertShortcodeIntoEditor(shortcode) {
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


})();