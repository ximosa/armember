jQuery(document).on('change', '.arm_mollie_mode_radio', function (e) {
    arm_hide_show_mollie_section();
});

function arm_hide_show_mollie_section() {
    var mollie_mode_type = jQuery('.arm_mollie_mode_radio:checked').val();
    if (mollie_mode_type == 'sandbox') {
        jQuery('.arm_mollie_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_mollie_sandbox_fields').removeClass('hidden_section');
    } else {
        jQuery('.arm_mollie_sandbox_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_mollie_fields').removeClass('hidden_section');
    }
}