
jQuery(document).on('change', '.arm_module_gateway_input', function () {
    can_hide_coupan_section();
});

jQuery(document).on('change', "input[name='arm_selected_payment_mode']", function () {
    can_hide_coupan_section();
});

jQuery(document).on('change', jQuery('.arm_module_gateway_input').attr('aria-label'), function () {
    setTimeout(function(){can_hide_coupan_section(); }, 100);
});

jQuery(document).on('change', jQuery('.arm_module_plan_input').attr('aria-label'), function () {
    setTimeout(function(){can_hide_coupan_section(); }, 100);
    arm_pagseguro_icheck_init();
});

jQuery(document).on('change', '.arm_module_plan_input', function () {
    can_hide_coupan_section();
    arm_pagseguro_icheck_init(); 
});

function can_hide_coupan_section(){
    var form = jQuery('.arm_setup_submit_btn').parents('form');
    var gateway_skin = form.find('[data-id="arm_front_gateway_skin_type"]').val();
    var plan_skin = form.find('[data-id="arm_front_plan_skin_type"]').val();
    
    if(plan_skin == 'skin5')
    {
        var planInput = form.find('.arm_module_plan_input').find('md-option:selected');
        var selected_plan = planInput.attr('data-recurring');
    }
    else
    {
        var planInput = form.find('input.arm_module_plan_input:checked');
         var selected_plan = planInput.attr('data-recurring');
    }
    
    if(gateway_skin == 'radio')
    {
        var gatewayInput = form.find('input.arm_module_gateway_input:checked'); 
        var totalGateways = form.find('input.arm_module_gateway_input').length;
        var gateway_name = gatewayInput.val();
    }
    else
    {

        var container = jQuery('.arm_module_gateway_input').attr('aria-owns');
        var gateway_name = jQuery('#' + container).find('md-option:selected').attr('value');
        
    }
    
    
    if(gateway_name === 'pagseguro' ){
        var plan_type = planInput.attr('data-type');
        var user_selected_payment_mode = form.find('[data-id="arm_user_selected_payment_mode_' + selected_plan+'"]').val();
        var user_old_plan_ids = form.find('[data-id="arm_user_old_plan"]').val();
        var user_old_plan = user_old_plan_ids.split(',');
        var user_old_plan_cycle = form.find('[data-id="arm_user_old_plan_total_cycle_' + selected_plan+'"]').val();
        var user_old_plan_done_payment = form.find('[data-id="arm_user_done_payment_' + selected_plan+'"]').val();
        var payment_mode = gatewayInput.attr('data-payment_mode');
        
        var isCouponRequired = form.find('input[name="arm_coupon_code"]').attr('data-isrequiredcoupon');
        if( typeof isCouponRequired != 'boolean' ){
            isCouponRequired = ( isCouponRequired == 'true' ) ? true : false;
        }
        
        if (plan_type == 'recurring' && jQuery.inArray(selected_plan, user_old_plan) != -1 && (user_old_plan_cycle != user_old_plan_done_payment || user_old_plan_cycle == 'infinite') && user_selected_payment_mode == 'manual_subscription')
        {
            
            form.find.find('.arm_setup_couponbox_wrapper').hide('slow');
        }
        else
        {
            if(payment_mode == 'both'){
                var payment_mode = jQuery('.arm_selected_payment_mode:checked').val();
            }
            
            if(payment_mode === 'auto_debit_subscription' && selected_plan === 'subscription' && !isCouponRequired)
            {
                
                form.find('.arm_setup_couponbox_wrapper').slideUp();
            }
            else
            {
                
                form.find('.arm_setup_couponbox_wrapper').slideDown();
            }
        }
    }
}

function arm_pagseguro_icheck_init()
{
    if (jQuery.isFunction(jQuery().iCheck))
    {
        jQuery('.arm_icheckbox').iCheck({
            checkboxClass: 'icheckbox_minimal-red',
            radioClass: 'iradio_minimal-red',
            increaseArea: '20%',
            disabledClass: '',
        });
        jQuery('.arm_icheckbox').on('ifChanged', function (event) {
            jQuery(this).trigger('change');
        });
        jQuery('.arm_icheckbox').on('ifClicked', function (event) {
            jQuery(this).trigger('click');
        });
        jQuery('.arm_iradio').iCheck({
            checkboxClass: 'icheckbox_minimal-red',
            radioClass: 'iradio_minimal-red',
            increaseArea: '20%',
            disabledClass: '',
        });
        jQuery('.arm_iradio').on('ifChanged', function (event) {
            jQuery(this).trigger('change');
        });
        jQuery('.arm_iradio').on('ifClicked', function (event) {
            jQuery(this).trigger('click');
        });
    }
}