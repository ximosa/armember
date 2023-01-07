jQuery(document).on('click', '.arm_referral_form_container .arm_page_numbers', function () {
    var transForm = jQuery(this).parents('.arm_referral_form_container');
    var pageNum = jQuery(this).attr('data-page');
    if (!jQuery(this).hasClass('current')) {
	var formData = transForm.serialize();
	jQuery.ajax({
	    type: "POST",
	    url: ajaxurl,
	    data: 'action=arm_referral_paging_action&current_page=' + pageNum + '&' + formData,
	    beforeSend: function () {
		transForm.find('.arm_transactions_wrapper').css('opacity', '0.5');
	    },
	    success: function (res) {
		transForm.find('.arm_transactions_wrapper').css('opacity', '1');
		transForm.parents('.arm_referral_container').replaceWith(res);
		arm_transaction_init();
		return false;
	    }
	});
    }
    return false;
});

jQuery(document).on('click', '.arm_payout_form_container .arm_page_numbers', function () {
    var transForm = jQuery(this).parents('.arm_payout_form_container');
    var pageNum = jQuery(this).attr('data-page');
    if (!jQuery(this).hasClass('current')) {
	var formData = transForm.serialize();
	jQuery.ajax({
	    type: "POST",
	    url: ajaxurl,
	    data: 'action=arm_payout_paging_action&current_page=' + pageNum + '&' + formData,
	    beforeSend: function () {
		transForm.find('.arm_transactions_wrapper').css('opacity', '0.5');
	    },
	    success: function (res) {
		transForm.find('.arm_transactions_wrapper').css('opacity', '1');
		transForm.parents('.arm_payout_container').replaceWith(res);
		arm_transaction_init();
		return false;
	    }
	});
    }
    return false;
});
function arm_referrals_invite_submit(form){
	var arm_referrals_emails =jQuery('#arm_email_label').val();
    var $formContainer = jQuery(form).parents('.arm_referrals_form');
    if(arm_referrals_emails!='')
    {
        jQuery('#arm_referrals_invite_submit').addClass('active');
        jQuery.ajax({
            type:'POST',
            url:__ARMAJAXURL,
            data:'action=arm_referrals_invite_friend&invite_email='+arm_referrals_emails,
            dataType: 'json',
            beforeSend: function () {
                jQuery(form).find("input[type='submit'], button[type='submit']").attr('disabled', 'disabled').addClass('active');
            },
            success:function (res){
                jQuery(form).find("input[type='submit'], button[type='submit']").removeAttr('disabled').removeClass('active');
                

                if (res.status == 'success') {
                    var message = jQuery("#success_msg_hidden").val();
                    var message = '<div class="arm_success_msg"><ul><li>'+ message +'</li></ul></div>';
                    jQuery('#arm_referrals_email').val('');
                    $formContainer.find('.arm_setup_messages').html('');
                    $formContainer.find('.arm_setup_messages').html(message).show().delay(5000).fadeOut(2000);

                    jQuery(form).trigger("reset");
                    jQuery(form).find('input').trigger("change");
                    jQuery(form).find('input').parent().removeClass('md-input-has-value').trigger("change");
                }
                else {
                    var message = res.message;
                    var message = '<div class="arm_error_msg"><ul><li>'+ message +'</li></ul></div>';
                    $formContainer.find('.arm_setup_messages').html('');
                    $formContainer.find('.arm_setup_messages').html(message).show();
                }
                jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: $formContainer.find('.arm_setup_messages').offset().top - 50}, 1000);
                $formContainer.find('.arm_setup_messages').html(message).show().delay(5000).fadeOut(2000);
            }
        });
    }
}

function arm_aff_reg_form_ajax_action (form) {

    var formData = form.serialize();
    var form_slug = form.attr('armaff-form-slug');
    if(form_slug != '' && form_slug != undefined) {

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arm_affiliate_register_account&'+formData,
            beforeSend: function () {
                jQuery(form).find("input[type='submit'], button[type='submit']").attr('disabled', 'disabled').addClass('active');
            },
            success: function (response) {
                if(response.type == "success"){
                    location.reload();
                } else {
                    var armaff_msg = '<div class="arm_error_msg">';
                    armaff_msg += '<ul>';
                    armaff_msg += '<li>';
                    armaff_msg += response.msg;
                    armaff_msg += '</li>';
                    armaff_msg += '</ul>';
                    armaff_msg += '</div>';
                    jQuery(".armaff_message_container.armaff_form_"+form_slug).html(armaff_msg).show().delay(5000).fadeOut(2000);
                    jQuery(window.opera ? 'html' : 'html, body').animate({
                        scrollTop: jQuery(".armaff_message_container.armaff_form_"+form_slug).offset().top - 50
                    }, 1000);
                }
                jQuery(form).find("input[type='submit'], button[type='submit']").removeAttr('disabled').removeClass('active');
                return false;
            }
        });
    }

    return false;  
}

