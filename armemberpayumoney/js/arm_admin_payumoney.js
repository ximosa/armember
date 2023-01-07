/*   for general setting page start    */

jQuery(document).on('change', '.arm_payumoney_mode_radio', function (e) {
    arm_hide_show_payumoney_section();
});
function arm_hide_show_payumoney_section() {
    var payumoney_mode_type = jQuery('.arm_payumoney_mode_radio:checked').val();
    if (payumoney_mode_type == 'sandbox') {
        jQuery('.arm_payumoney_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_payumoney_sandbox_fields').removeClass('hidden_section');
    } else {
        jQuery('.arm_payumoney_sandbox_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_payumoney_fields').removeClass('hidden_section');
    }
}

/*   for general setting page end    */