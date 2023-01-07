/*   for general setting page start    */

jQuery(document).on('change', '.arm_payfast_mode_radio', function (e) {
    arm_hide_show_payfast_section();
});
function arm_hide_show_payfast_section() {
    var payfast_mode_type = jQuery('.arm_payfast_mode_radio:checked').val();
    if (payfast_mode_type == 'sandbox') {
        jQuery('.arm_payfast_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_payfast_sandbox_fields').removeClass('hidden_section');
    } else {
        jQuery('.arm_payfast_sandbox_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_payfast_fields').removeClass('hidden_section');
    }
}
/*   for general setting page end    */

function addPayfastPlanBox(plan_id) {
    jQuery('.arm_payfast_plan_label_' + plan_id).show();
    if (jQuery('#arm_payfast_auto_mode, #arm_payfast_both_mode').is(':checked')) {
        jQuery('.arm_payfast_plan_container').show();
    } else {
        jQuery('.arm_payfast_plan_container').hide();
    }
}

jQuery(document).ready(function () {
	jQuery(document).on('change', '.gateways_chk_inputs', function () {
	    var gateway = jQuery(this).val();
	    if (gateway == 'payfast') {
	        if (jQuery(this).is(':checked')) {
	            jQuery('.plans_chk_inputs:checked').each(function () {
	                var plan_id = jQuery(this).val();
	                var plan_type = jQuery(this).attr('data-plan_type');
	                var payment_type = jQuery(this).attr('data-payment_type');
	                var payment_mode = jQuery(this).attr('data-payment_mode');
	                if (plan_id != 0 && plan_type == 'recurring' && payment_type == 'subscription') {
	                    addPayfastPlanBox(plan_id);
	                }
	            });
	        } else {
	            
	            jQuery('.arm_payfast_plan_container').hide();
	        }
	    }
	    ShowHideGatewayWarning();
	});
	jQuery(document).on('change', '.arm_payfast_gateway_payment_mode_input', function () {
	    jQuery('.plans_chk_inputs:checked').each(function () {
	        var plan_id = jQuery(this).val();
	        var plan_type = jQuery(this).attr('data-plan_type');
	        var payment_type = jQuery(this).attr('data-payment_type');
	        var payment_mode = jQuery(this).attr('data-payment_mode');
	        if (plan_id != 0 && plan_type == 'recurring' && payment_type == 'subscription') {
	            addPayfastPlanBox(plan_id);
	        }
	    });
	});
	jQuery(document).on('change', ".plans_chk_inputs", function () {
	    var plan_id = jQuery(this).val();
	    var plan_type = jQuery(this).attr('data-plan_type');
	    var payment_type = jQuery(this).attr('data-payment_type');
	    if (plan_id != 0 && plan_type != 'free') {
	        if (!jQuery(this).is(':checked')) {
	            jQuery(this).parents('.arm_membership_setup_plans_li').removeClass('arm_required_text');
	        }
	        if (payment_type == 'subscription' && plan_type == 'recurring') {
	            if (jQuery(this).is(':checked')) {
	                if (jQuery('#gateway_chk_payfast').length > 0 && jQuery('#gateway_chk_payfast').is(':checked')) {
	                    addPayfastPlanBox(plan_id);
	                }
	            } else {
	                jQuery('.arm_payfast_plan_label_' + plan_id).hide();
	                if (jQuery('.arm_payfast_plans').length == 0) {
	                    jQuery('.arm_payfast_plan_container').hide();
	                }
	            }
	        }
	    }
	    var arm_display_payfast_heading = 0;
	    jQuery('.arm_payfast_plans').each(function () {
	        if (jQuery(this).is(':visible')) {
	            arm_display_payfast_heading++;
	        }
	    });
	    if (arm_display_payfast_heading > 0) {
	        jQuery(".arm_payfast_plan_container h4").show();
	    } else {
	        jQuery(".arm_payfast_plan_container h4").hide();
	    }
	    jQuery('.arm_gateway_payment_mode_box').hide();
	    jQuery(".plans_chk_inputs").each(function () {
	        var plan_type = jQuery(this).attr('data-plan_type');
	        var arm_show_payment_cycle = jQuery(this).attr('data-show_payment_cycle');
	        if (plan_type == 'recurring') {
	            if (jQuery(this).is(':checked')) {
	                jQuery('.arm_gateway_payment_mode_box').show();
	            }
	        } else if (plan_type == 'paid_finite' && arm_show_payment_cycle == 1) {
	            if (jQuery(this).is(':checked')) {
	                jQuery('.arm_gateway_payment_mode_box').show();
	            }
	        }
	    });
	    arm_tooltip_init();
	});
	jQuery(document).on('click', '.arm_save_btn', function () {
		var payfast_error ;
		if (jQuery('#gateway_chk_payfast').length > 0 && jQuery('#gateway_chk_payfast').is(':checked')) {
			jQuery('.arm_setup_payfast_plan_input').each(function () {
				var arm_payfast_plan_id = jQuery(this).attr('data-plan_id');
				var arm_payfast_plans_chk_inputs = jQuery('#plan_chk_'+arm_payfast_plan_id);
				
				if (jQuery(this).val() == '' && arm_payfast_plans_chk_inputs.is(':checked')) { 

	                payfast_error = true;
	                jQuery(this).addClass('error arm_invalid');
	                jQuery(this).focus();
	                return false;
	            } 
	        });
			if (payfast_error) {
	            jQuery(window.opera ? 'html' : 'html, body').animate({
	                scrollTop: jQuery('.arm_invalid').offset().top - 100
	            }, 0);
	            return false;
	        }
	    }
	});
});