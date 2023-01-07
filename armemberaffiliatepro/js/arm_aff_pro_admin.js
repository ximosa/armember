jQuery(document).on('change', "#arm_affiliatepro_price_container input[type=radio]", function () {
    var type = jQuery(this).val();
    if(type == 'percentage')
    {
        jQuery('.arm_affpro_price_type_percentage').show();
        jQuery('.arm_affpro_price_type_currency').hide();
    }
    else
    {
        jQuery('.arm_affpro_price_type_percentage').hide();
        jQuery('.arm_affpro_price_type_currency').show();
    }
});