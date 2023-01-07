jQuery(document).on('change', "input[name='arm_selected_payment_mode']", function () {
    var form = jQuery(this).parents('form');
    var gateway_skin = form.find('#arm_front_gateway_skin_type').val();
    var plan_skin = form.find('#arm_front_plan_skin_type').val();
    
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
        var gatewayInput = form.find('.arm_module_gateway_input').find('md-option:selected');
        gateway_name = gatewayInput.attr('value')
    }
    
    var payment_type = jQuery(".arm_selected_payment_mode:checked").val();
    
    var arm_payable_amout = jQuery('.arm_payable_amount_text').html();
    
    if(gateway_name === 'mollie' && payment_type === 'auto_debit_subscription' && selected_plan === 'subscription' && arm_payable_amout == '0.00')
    {
        jQuery('.arm_payable_amount_text').html('0.1');
    }
});

jQuery(document).on('change', '.arm_module_gateway_input', function() {
    if(jQuery(this).val() == 'mollie'){
        var form = jQuery(this).parents('form');
        var gateway_skin = form.find('#arm_front_gateway_skin_type').val();
        var plan_skin = form.find('#arm_front_plan_skin_type').val();

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
            var gatewayInput = form.find('.arm_module_gateway_input').find('md-option:selected');
            gateway_name = gatewayInput.attr('value')
        }

        var payment_type = jQuery(".arm_selected_payment_mode:checked").val();

        var arm_payable_amout = jQuery('.arm_payable_amount_text').html();

        if(gateway_name === 'mollie' && payment_type === 'auto_debit_subscription' && selected_plan === 'subscription' && arm_payable_amout == '0.00')
        {
            jQuery('.arm_payable_amount_text').html('0.1');
        }
    }
});

jQuery(document).on('change', jQuery('.arm_module_gateway_input').attr('aria-label'), function () {
    setTimeout(function(){
        arm_mollie_change_payable_amount();
    }, 100);
});

jQuery(document).on('change', jQuery('.arm_module_plan_input').attr('aria-label'), function () {
    setTimeout(function(){arm_mollie_change_payable_amount(); }, 100);
 
});

jQuery(document).on('change', '.arm_module_plan_input', function () {
    arm_mollie_change_payable_amount();
});

function arm_mollie_change_payable_amount(){
    var form = jQuery('.arm_setup_submit_btn').parents('form');
    var gateway_skin = form.find('#arm_front_gateway_skin_type').val();
    var plan_skin = form.find('#arm_front_plan_skin_type').val();

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
        var gatewayInput = form.find('.arm_module_gateway_input').find('md-option:selected');
        gateway_name = gatewayInput.attr('value')
    }

    var payment_type = jQuery(".arm_selected_payment_mode:checked").val();

    var arm_payable_amout = jQuery('.arm_payable_amount_text').html();

    if(gateway_name === 'mollie' && payment_type === 'auto_debit_subscription' && selected_plan === 'subscription' && arm_payable_amout == '0.00')
    {
        jQuery('.arm_payable_amount_text').html('0.1');
    }
}