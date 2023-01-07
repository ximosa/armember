/*   for general setting page start    */

jQuery(document).on('change', '.arm_pagseguro_mode_radio', function (e) {
    arm_hide_show_pagseguro_section();
});
function arm_hide_show_pagseguro_section() {
    var pagseguro_mode_type = jQuery('.arm_pagseguro_mode_radio:checked').val();
    if (pagseguro_mode_type == 'sandbox') {
        jQuery('.arm_pagseguro_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_pagseguro_sandbox_fields').removeClass('hidden_section');
    } else {
        jQuery('.arm_pagseguro_sandbox_fields:not(.hidden_section)').addClass('hidden_section');
        jQuery('.arm_pagseguro_fields').removeClass('hidden_section');
    }
}

/*   for general setting page end    */