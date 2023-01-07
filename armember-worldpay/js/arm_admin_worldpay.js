"use strict";
jQuery(document).on('change', '.arm_online_worldpay_type_radio', function (e) {
    arm_hide_show_online_worldpay_section();
});
jQuery(document).on('change', '.arm_online_worldpay_mode_radio', function (e) {
    arm_hide_show_online_worldpay_section();
});
function arm_hide_show_online_worldpay_section() {
	var worldpay_mode_type = jQuery('.arm_online_worldpay_mode_radio:checked').val();
    var online_worldpay_type = jQuery('.arm_online_worldpay_type_radio:checked').val();
    if (worldpay_mode_type == 'sandbox') {
        jQuery('.arm_online_worldpay_sandbox_fields').removeClass('hidden_section');
        jQuery('.arm_online_worldpay_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_payments_pro_sandbox_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_payments_pro_fields:not(.hidden_section)').addClass('hidden_section');
    } else {
        jQuery('.arm_online_worldpay_fields').removeClass('hidden_section');
        jQuery('.arm_online_worldpay_sandbox_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_payments_pro_sandbox_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_payments_pro_fields:not(.hidden_section)').addClass('hidden_section');
    }
}