/*   for general setting page start    */

jQuery(document).on('change', '.arm_skrill_mode_radio', function (e) {
    arm_hide_show_skrill_section();
});
function arm_hide_show_skrill_section() {
    var skrill_mode_type = jQuery('.arm_skrill_mode_radio:checked').val();
    if (skrill_mode_type == 'sandbox') {
        jQuery('.arm_skrill_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_skrill_sandbox_fields').removeClass('hidden_section');
    } else {
        jQuery('.arm_skrill_sandbox_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_skrill_fields').removeClass('hidden_section');
    }
}
/*   for general setting page end    */

function addSkrillPlanBox(plan_id) {
    jQuery('.arm_skrill_plan_label_' + plan_id).show();
    if (jQuery('#arm_skrill_auto_mode, #arm_skrill_both_mode').is(':checked')) {
        jQuery('.arm_skrill_plan_container').show();
    } else {
        jQuery('.arm_skrill_plan_container').hide();
    }
}

jQuery(document).ready(function () {
	jQuery(document).on('change', '.gateways_chk_inputs', function () {
	    var gateway = jQuery(this).val();
	    if (gateway == 'skrill') {
	        if (jQuery(this).is(':checked')) {
	            jQuery('.plans_chk_inputs:checked').each(function () {
	                var plan_id = jQuery(this).val();
	                var plan_type = jQuery(this).attr('data-plan_type');
	                var payment_type = jQuery(this).attr('data-payment_type');
	                var payment_mode = jQuery(this).attr('data-payment_mode');
	                if (plan_id != 0 && plan_type == 'recurring' && payment_type == 'subscription') {
	                    addSkrillPlanBox(plan_id);
	                }
	            });
	        } else {
	            
	            jQuery('.arm_skrill_plan_container').hide();
	        }
	    }
	    ShowHideGatewayWarning();
	});
	jQuery(document).on('change', '.arm_skrill_gateway_payment_mode_input', function () {
	    jQuery('.plans_chk_inputs:checked').each(function () {
	        var plan_id = jQuery(this).val();
	        var plan_type = jQuery(this).attr('data-plan_type');
	        var payment_type = jQuery(this).attr('data-payment_type');
	        var payment_mode = jQuery(this).attr('data-payment_mode');
	        if (plan_id != 0 && plan_type == 'recurring' && payment_type == 'subscription') {
	            addSkrillPlanBox(plan_id);
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
	                if (jQuery('#gateway_chk_skrill').length > 0 && jQuery('#gateway_chk_skrill').is(':checked')) {
	                    addSkrillPlanBox(plan_id);
	                }
	            } else {
	                jQuery('.arm_skrill_plan_label_' + plan_id).hide();
	                if (jQuery('.arm_skrill_plans').length == 0) {
	                    jQuery('.arm_skrill_plan_container').hide();
	                }
	            }
	        }
	    }
	    var arm_display_skrill_heading = 0;
	    jQuery('.arm_skrill_plans').each(function () {
	        if (jQuery(this).is(':visible')) {
	            arm_display_skrill_heading++;
	        }
	    });
	    if (arm_display_skrill_heading > 0) {
	        jQuery(".arm_skrill_plan_container h4").show();
	    } else {
	        jQuery(".arm_skrill_plan_container h4").hide();
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
});