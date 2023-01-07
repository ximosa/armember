!function(){function e(e){function l(e){e && r.push(e)}for (var r = [e], t = [], n = [], a = ["ng:module", "ng-module", "x-ng-module", "data-ng-module", "ng:modules", "ng-modules", "x-ng-modules", "data-ng-modules"], u = /\sng[:\-]module[s](:\s*([\w\d_]+);?)?\s/, o = 0; o < a.length; o++){var s = a[o]; if (l(document.getElementById(s)), s = s.replace(":", "\\:"), e.querySelectorAll){var g; g = e.querySelectorAll("." + s); for (var d = 0; d < g.length; d++)l(g[d]); g = e.querySelectorAll("." + s + "\\:"); for (var d = 0; d < g.length; d++)l(g[d]); g = e.querySelectorAll("[" + s + "]"); for (var d = 0; d < g.length; d++)l(g[d])}}for (var o = 0; o < r.length; o++){var e = r[o], c = " " + e.className + " ", m = u.exec(c); if (m)t.push(e), n.push((m[2] || "").replace(/\s+/g, ",")); else if (e.attributes)for (var d = 0; d < e.attributes.length; d++){var f = e.attributes[d]; - 1 != a.indexOf(f.name) && (t.push(e), n.push(f.value))}}for (var o = 0; o < t.length; o++){var v = t[o], h = n[o].replace(/ /g, "").split(","); try{angular.bootstrap(v, h)} catch (err){}}}angular.element(document).ready(function(){e(document)})}();
        function armInitAngularElement(id, app)
        {
        if (id != 'undefined') {
        angular.bootstrap(jQuery('#' + id), [app]);
        }
        return false;
        }


var ARMApp = angular.module('ARMApp', ['ngAnimate', 'ngAria', 'ngMaterial', 'ngMessages']);

        var creditcardModules = ['credit-cards'];

        if(jQuery('.cardNumber').length || jQuery('.arm_renew_subscription_button').length){
       
        angular.forEach(creditcardModules, function(dependency) {
            ARMApp.requires.push(dependency);
        });
        }


        ARMCtrl.$inject = ["$scope", "$http", "$timeout"];
        ARMApp.controller('ARMCtrl', ARMCtrl);
        function ARMCtrl($scope, $http, $timeout) {
        $scope.masterFormData = {};
                $scope.armPW = '';
                $scope.arm_form = {};
                $scope.arm_form_ca = {};
                $scope.arm_form.user_pass = '';
                $scope.arm_form.coupon_code_val = '';
                var original = $scope.arm_form;
                $scope.armPlanChange = function(form_id){
                    $timeout(function(){
                          
                        var container = jQuery('#'+form_id+' .arm_module_plan_input').attr('aria-owns');

                      
                        var plan_name = jQuery('#'+container).find('md-option[selected="selected"]');
                        if (jQuery('#'+form_id+' input:radio[name="arm_selected_payment_mode"]').length) {
                
                jQuery('#'+form_id+' input:radio[name="arm_selected_payment_mode"]').filter('[value="auto_debit_subscription"]').attr('checked', 'checked');
                }

                


                armSetupHideShowSections1('#'+form_id, plan_name);
                        var gateway_skin = jQuery('#'+form_id+' [data-id="arm_front_gateway_skin_type"]').val();
                        
                        if (gateway_skin == 'dropdown'){
                        
                            var container = jQuery('#'+form_id+' .arm_module_gateway_input').attr('aria-owns');
                            var gateway_name = jQuery('#'+container).find('md-option:first').attr('value');
                            $scope.payment_gateway = gateway_name;
                           $scope.arm_form.payment_gateway = gateway_name;
                            $scope.armPaymentGatewayChange(form_id);
                        }
                    });
                }

        $scope.armPaymentGatewayChange = function(form_id){
        var gateway = $scope.payment_gateway;
                $scope.arm_form.payment_gateway = gateway;

              
                var arm_total_payable_amount = jQuery('#'+form_id+' [data-id="arm_total_payable_amount"]').val();
                var arm_selected_payment_mode = jQuery('#'+form_id+' [name=arm_selected_payment_mode]:checked').val();
                if (arm_total_payable_amount != '0.00' && arm_total_payable_amount != '0')
        {
        jQuery('#'+form_id+' .arm_module_gateway_fields').not('.arm_module_gateway_fields_' + gateway).slideUp('slow').addClass('arm_hide');
               jQuery('#'+form_id+' .arm_module_gateway_fields_' + gateway).slideDown('slow').removeClass('arm_hide');
        }
        else if ((arm_total_payable_amount == '0.00' || arm_total_payable_amount == '0') && arm_selected_payment_mode == 'auto_debit_subscription')
        {
        jQuery('#'+form_id+' .arm_module_gateway_fields').not('.arm_module_gateway_fields_' + gateway).slideUp('slow').addClass('arm_hide');
                jQuery('#'+form_id+' .arm_module_gateway_fields_' + gateway).slideDown('slow').removeClass('arm_hide');
        }
        else{
        jQuery('#'+form_id+' .arm_module_gateway_fields').slideUp('slow').addClass('arm_hide');
        }

        $timeout(function(){
            var plan_skin = jQuery('#'+form_id+' [data-id="arm_front_plan_skin_type"]').val();
            if (plan_skin != 'skin5')
            {
                armSetupHideShowSections('#'+form_id);
            }
            else
            {
                var container = jQuery('#'+form_id+' .arm_module_plan_input').attr('aria-owns');
                var plan_name = jQuery('#'+container).find('md-option[selected="selected"]');
                if (plan_name != '')
        {
        armSetupHideShowSections1('#'+form_id, plan_name);
        }
        }
        });
        }



         $scope.armPaymentCycleChange = function(plan_id , form_id){

            
            var cycle = $scope['payment_cycle_'+plan_id];
           
            
            
            var setupForm = jQuery('#'+form_id);
            var scope = angular.element('[data-ng-controller=ARMCtrl]').scope();
            var plan_amt = jQuery('md-option[data-plan_id="'+plan_id+'"][value="'+cycle+'"]').attr('data-plan_amount');

            var selectedPlanSkin = jQuery(setupForm).find('[data-id="arm_front_plan_skin_type"]').val();

            if (selectedPlanSkin == 'skin5') {
             
                var container = jQuery(setupForm).find('md-select[name="subscription_plan"]').attr('aria-owns');
                var planInput = jQuery('#'+container).find('md-option[selected="selected"][value="'+plan_id+'"]');
                planInput.find('.arm_module_plan_cycle_price').html(plan_amt);
            }
            else
            {
                var planInput = jQuery(setupForm).find('input.arm_module_plan_input:checked');
                planInput.parents('.arm_setup_column_item').find('.arm_module_plan_cycle_price').html(plan_amt);
            }

          
           planInput.attr('data-amt', plan_amt);

            armResetCouponCode(setupForm);
            armUpdateOrderAmount1(planInput, setupForm);
          
               
       
        }

        $scope.armSubmitBtnClick = function($event){
        if ($event.isTrigger != undefined && $event.isTrigger){
        $event.preventDefault();
                return false;
        }
        $scope.resetApplyCouponBox();
        }

        $scope.armresetradiofield = function(field_id, event){
            $scope.arm_form[field_id] = false;
        }

        $scope.armFormSubmit = function(isValid, formID, $event){
        if (isValid) {
            if (jQuery("#"+formID).find(".arm_form_input_box_sms_otp").length >= 1) {
                var generated_otp = jQuery("#"+formID).find("input[type='hidden'][name='arm_sms_nonce']").val();
                var entered_otp   = jQuery("#"+formID).find("input[type='text'][name='arm_sms_otp']").val();
                if (generated_otp != "" && typeof generated_otp != "undefined" && entered_otp != "" && typeof entered_otp != "undefined") {
                    generated_otp = atob(generated_otp);
                    if (entered_otp != generated_otp) {
                        $scope.arm_form.arm_sms_otp.$setValidity("invalid", false);
                        return false;
                    }
                }
            }
        var gateway_skin = jQuery('[data-id="arm_front_gateway_skin_type"]').val();
        
                if (gateway_skin == 'radio')
        {
        var gateway = jQuery('.arm_module_gateways_container input.arm_module_gateway_input:checked').val();
        }
        else
        {
           var container = jQuery('.arm_module_gateway_input').attr('aria-owns');
            var gateway = jQuery('#' + container).find('md-option:selected').attr('value'); 
        
        }
        arm_form_ajax_action(jQuery('#' + formID));
                if (!jQuery('#' + formID).hasClass('arm_form_edit_profile')) {
        $scope.arm_form.$setPristine(true);
                $scope.arm_form.$setUntouched();
        }
        } else {
        if ($event.isTrigger != undefined && $event.isTrigger){
        $event.preventDefault();
                return false;
        }
        $scope.setFormErrors($scope.arm_form, formID);
        }
    }

    $scope.armSetTax = function(formRandomID) {
        var form = jQuery('#'+formRandomID);
        var skin = form.find('[data-id="arm_front_plan_skin_type"]').val();

        if(skin == "skin5") {
            var container = jQuery(form).find('.arm_module_plan_input').attr('aria-owns');
            var planInput = jQuery('#'+container).find('md-option:selected');
            if(typeof planInput.attr('data-tax') != "undefined") {
                setTimeout(function() {
                    armResetCouponCode(form);
                    armUpdateOrderAmount1(planInput, form, 0);
                }, 350);
            }
        }
        else {
            setTimeout(function() {
                armResetCouponCode(form);
                armUpdateOrderAmount(form, 0, '');
            }, 350);
        }
    }

    $scope.armSetDefaultPaymentGateway = function(gateway){
        $scope.arm_form.payment_gateway = gateway;
        }
        $scope.isPaymentGatewayField = function(gateway){
        if ($scope.arm_form.arm_plan_type != undefined && $scope.arm_form.arm_plan_type == "free") {
        return false;
        }
        if ($scope.arm_form.payment_gateway == gateway) {
        var arm_total_payable_amount = jQuery('[data-id="arm_total_payable_amount"]').val();
                var arm_selected_payment_mode = jQuery('[name=arm_selected_payment_mode]:checked').val();

                var plan_skin = jQuery('[data-id="arm_front_plan_skin_type"]').val();
                if(plan_skin == 'skin5'){
                    var container = jQuery('.arm_module_gateway_input').attr('aria-owns');
                    var arm_plan_type = jQuery('#' + container).find('md-option:selected').attr('data-type');

                }
                else{
                    var arm_plan_type = jQuery('.arm_module_plan_input:checked').attr('data-type');
                }
                


                    if(arm_plan_type == 'recurring'){

                if ((arm_total_payable_amount == '0.00' || arm_total_payable_amount == '0') && arm_selected_payment_mode != 'auto_debit_subscription')
                {

                return false;
                }
                else
                {
                return true;
                }
                    }
                    else{
                       if ((arm_total_payable_amount == '0.00' || arm_total_payable_amount == '0'))
                {
                return false;
                }
                else
                {
                return true;
                } 
                    }

                }
                return false;
                }


        $scope.isminlengthField = function(gateway){

        if ($scope.arm_form.payment_gateway == gateway) {
        return '13';
        }
        else
        {
        return '';
        }
        }

        $scope.isCouponRequired = function(setupForm)
        {
        var arm_is_user_logged_in_flag = jQuery('[data-id="arm_is_user_logged_in_flag"]').val();
                if (arm_is_user_logged_in_flag == '' || arm_is_user_logged_in_flag == 0)
        {

        return true;
        }
        else
        {
        return false;
        }
        }

        $scope.armUpdateCardFormSubmit = function(isValid, formID, $event){
            if (isValid) {
                
                form = jQuery('#' + formID);
                jQuery(form).find('.arm_setup_submit_btn').addClass('active');
                
                setTimeout(function(){
                arm_update_card_form_ajax_action(jQuery('#' + formID));  }, 2000);
                
                $scope.arm_form.$setPristine(true);
                $scope.arm_form.$setUntouched();
                
            }
            else {

            if ($event.isTrigger != undefined && $event.isTrigger){
            $event.preventDefault();
                    return false;
            }
            $scope.setFormErrors($scope.arm_form, formID);
            }
        }
        $scope.armSetupFormSubmit = function(isValid, formID, $event){
        if (isValid) {
            if (jQuery("#"+formID).find(".arm_form_input_box_sms_otp").length >= 1) {
                var generated_otp = jQuery("#"+formID).find("input[type='hidden'][name='arm_sms_nonce']").val();
                var entered_otp   = jQuery("#"+formID).find("input[type='text'][name='arm_sms_otp']").val();
                if (generated_otp != "" && typeof generated_otp != "undefined" && entered_otp != "" && typeof entered_otp != "undefined") {
                    generated_otp = atob(generated_otp);
                    if (entered_otp != generated_otp) {
                        $scope.arm_form.arm_sms_otp.$setValidity("invalid", false);
                        return false;
                    }
                }
            }
            if(jQuery("#"+formID).find(".arm_form_input_container_payment_mode md-select:visible").length > 0) {
                var transfer_mod = jQuery("#"+formID).find(".arm_form_input_container_payment_mode input[name='bank_transfer[transfer_mode]']").val();
                if(transfer_mod == '') {
                    var invalid_elements = jQuery("#"+formID).find(".arm_form_input_container_payment_mode md-select");
                    invalid_elements.focus();
                    var scrollPos = invalid_elements.offset().top;
                    jQuery(window).scrollTop((scrollPos - 100));
                    return false;
                }
            }
            var plan_skin = jQuery('#' + formID).find('[data-id="arm_front_plan_skin_type"]').val();
        
            var gateway_skin = jQuery('#' + formID).find('input[data-id="arm_front_gateway_skin_type"]').val();

            if (gateway_skin == 'radio'){
                var gateway = jQuery('#' + formID).find('input.arm_module_gateway_input:checked').val();
            } else {
                var container = jQuery('#' + formID).find('.arm_module_gateway_input').attr('aria-owns');
                var gateway = jQuery('#' + container).find('md-option:selected').attr('value');
            }

            if (plan_skin == 'skin5'){
                var container = jQuery('#' + formID).find('.arm_module_plans_container .arm_module_plan_input').attr('aria-owns');
                var plan_type = jQuery('#' + container).find('md-option:selected').attr('data-type');
            } else {
                var plan_type = jQuery('#' + formID).find('.arm_module_plans_container .arm_module_plan_input:checked').attr('data-type');
            }

            if (plan_type == "free"){
                arm_setup_form_ajax_action(jQuery('#' + formID));
            } else {

                if (gateway == 'stripe') {
                    arm_setup_form_ajax_action(jQuery('#' + formID));
                    jQuery('#' + formID).find('input[name=stripeToken]').remove();
                }
                else
                {
                    arm_setup_form_ajax_action(jQuery('#' + formID));
                }
            }
                $scope.arm_form.$setPristine(true);
                $scope.arm_form.$setUntouched();
        } else {

        if ($event.isTrigger != undefined && $event.isTrigger){
        $event.preventDefault();
                return false;
        }
        $scope.setFormErrors($scope.arm_form, formID);
        }
        }
        $scope.setFormErrors = function(sf, formID) {
        angular.forEach(sf.$error, function (field) {
        angular.forEach(field, function(errorField){
            
           
        errorField.$setTouched();
                errorField.$setDirty();
        });
        });
                if (formID){
        var invalid_elements = jQuery('#' + formID).find('.ng-invalid');
   
                if (invalid_elements.length > 0) {
        invalid_elements[0].focus();
        
     
                var scrollPos = jQuery("#" + invalid_elements[0].id).offset().top;
                jQuery(window).scrollTop((scrollPos - 70));
        }
        }
        };
                $scope.reset = function(form) {
                if (form) {
                form.$setValidity();
                        form.$setPristine();
                        form.$setUntouched();
                }
                };
                $scope.resetForm = function(form, id) {

                if (form) {


                jQuery('#' + id).find("[data-ng-model]").each(function() {
                var type = jQuery(this).attr('type');
                        if (type != 'hidden')
                {
                var $name = jQuery(this).attr('name');
                        var $type = jQuery(this).attr('type');
                        if (form[$name] != undefined)
                {
                if (jQuery.type(form[$name]) == 'object'){

                form[$name].$setViewValue(null);
                        form[$name].$modelValue = '';
                        form[$name].$setPristine();
                        form[$name].$setUntouched();
                        if (jQuery(this).val() != ''){
                jQuery(this).val('');
                }
                if ($type == 'file'){
                jQuery('.armFileRemoveContainer').trigger('click');
                }
                }

                }
                }
                });
                        form.$setPristine();
                        form.$setUntouched();
                }
                };
                $scope.isSomeSelected = function (object) {
                    
                   
                if (!object) {
                return false;
                }
                return Object.keys(object).some(function (key) {
                    
                return object[key];
                });
                }
                
                
        $scope.armFormCloseAccountSubmit = function(isValid, formID) {
        if (isValid) {
        arm_form_close_account_action(jQuery('#' + formID));
        } else {
        $scope.setFormErrors($scope.arm_form_ca, formID);
        }
        }
        $scope.resetApplyCouponBox = function() {
        if (typeof $scope.arm_form.arm_coupon_code != 'undefined') {
        $scope.arm_form.arm_coupon_code.$setValidity('required_coupon', true);
                $scope.arm_form.arm_coupon_code.$setValidity('invalid_coupon', true);
                $scope.arm_form.arm_coupon_code.$setValidity('invalid_plan', true);
                $scope.arm_form.arm_coupon_code.$setValidity('expired', true);
        }
        };
        }

checkcouponcode.$inject = ["$q", "$http", "$templateCache"];
        ARMApp.directive("checkcouponcode", checkcouponcode);
        function checkcouponcode($q, $http, $templateCache) {
            return {
                require: "ngModel",
                restrict: "A",
                link: function(scope, element, attributes, ngModel) {
                    element.bind('change', function (e) {
                        scope.resetApplyCouponBox();
                    });

                    var couponBox = element.parents('.arm_apply_coupon_container');
                    var $parentForm = couponBox.parents('form');
                    couponBox.find('.arm_apply_coupon_btn').click(function (e) {
                        if (e.isTrigger != undefined && e.isTrigger) {
                            e.preventDefault();
                            return false;
                        }
                        jQuery(this).attr('disabled', true);
                        ngModel.$setTouched();
                        scope.resetApplyCouponBox();
                        var couponCode = couponBox.find('.arm_coupon_code').val();
                        var plan_skin = $parentForm.find('[data-id="arm_front_plan_skin_type"]').val();
                        var gateway_skin = $parentForm.find('[data-id="arm_front_gateway_skin_type"]').val();
                        var user_old_plan = $parentForm.find('[data-id="arm_user_old_plan"]').val();
                        var setup_id = $parentForm.find('[data-id="arm_setup_id"]').val();
                       
                        if (plan_skin == 'skin5') {
                            var container = $parentForm.find('.arm_module_plan_input').attr('aria-owns');
                            var plan_id = jQuery('#'+container).find('md-option:selected').attr('value');
                            var payment_cycle_box = $parentForm.find('.arm_payment_cycle_box_' + plan_id + ' .arm_module_cycle_input').attr('aria-owns');
                            var payment_cycle = jQuery('#'+payment_cycle_box).find('md-option:selected').attr('value');
                            var arm_arm_form_id = $parentForm.attr('id');
                            var arm_arm_container = jQuery('#'+arm_arm_form_id+' .arm_module_plan_input').attr('aria-owns');
                            var planInput = jQuery('#'+arm_arm_container).find('md-option[selected="selected"]');
                        }
                        else {
                            var plan_id = $parentForm.find('.arm_module_plans_container .arm_module_plan_input:checked').val();
                            var payment_cycle = $parentForm.find('.arm_payment_cycle_box_' + plan_id + ' .arm_module_cycle_input:checked').val();
                            var planInput = $parentForm.find('input.arm_module_plan_input:checked');
                        }

                        if (gateway_skin == 'radio') {
                            var gateway_name = $parentForm.find('.arm_module_gateways_container .arm_module_gateway_input:checked').val();
                        }
                        else {
                            var container = $parentForm.find('.arm_module_gateway_input').attr('aria-owns');
                            var gateway_name = jQuery('#'+container).find('md-option:selected').attr('value');
                        }

                        if (payment_cycle == undefined) {
                            payment_cycle = 0;
                        }

                        var payment_mode = $parentForm.find('input[name="arm_selected_payment_mode"]:checked').val();
                        couponBox.find('.arm_form_input_container_coupon_code').find('span.notify_msg').remove();

                        if (couponCode != '' && typeof couponCode != 'undefined') {
                            var data = {action: 'arm_apply_coupon_code', coupon_code: couponCode, plan_id: plan_id, gateway:gateway_name, payment_mode:payment_mode, payment_cycle:payment_cycle, user_old_plan: user_old_plan};

                            couponBox.find(".arm_apply_coupon_btn").addClass('active');

                            var checkRes = $http.post(__ARMAJAXURL + '?action=arm_apply_coupon_code&coupon_code=' + couponCode + '&plan_id=' + plan_id + '&setup_id=' + setup_id + '&gateway=' + gateway_name + '&payment_mode=' + payment_mode + '&payment_cycle=' + payment_cycle + '&user_old_plan=' + user_old_plan, data).then(function(res) {
                                    var total_amt = '';
                                    var coupon_amt = '';
                                    var arm_coupon_on_each_subscriptions = '';
                                    var arm_discount_on_plan = planInput.attr('data-amt');
                                    var arm_discount_amt = 0;
                                    if (res.data.status == 'success') {
                                        couponBox.find(".arm_apply_coupon_btn").removeClass('active');
                                        total_amt = res.data.total_amt;
                                        coupon_amt = res.data.coupon_amt;
                                        arm_coupon_on_each_subscriptions = res.data.arm_coupon_on_each_subscriptions;
                                        arm_discount_amt = res.data.discount;

                                        if (arm_discount_amt != undefined)
                                        {
                                            arm_discount_amt = arm_discount_amt.replace(',','');
                                        }
                                        if(arm_coupon_on_each_subscriptions!='' && arm_coupon_on_each_subscriptions!='0') {
                                            var arm_plan_amt = planInput.attr('data-amt');
                                            if (arm_plan_amt != undefined)
                                            {
                                                arm_plan_amt = arm_plan_amt.replace(',','');
                                            }
                                            if(res.data.discount_type=='fixed') {
                                                arm_discount_on_plan = parseFloat(arm_plan_amt) - parseFloat(arm_discount_amt);
                                            }
                                            else {
                                                arm_discount_on_plan = (parseFloat(arm_plan_amt) * parseFloat(arm_discount_amt)) / parseFloat(100);
                                                arm_discount_on_plan = parseFloat(arm_plan_amt) - parseFloat(arm_discount_on_plan);
                                            }

                                            if(arm_discount_on_plan<=0) {
                                                arm_discount_on_plan = 0.0001; 
                                            }
                                        }
                                        couponBox.find('.arm_form_input_container_coupon_code').append('<span class="success notify_msg">' + res.data.message + '</span>');
                                    }
                                    else {
                                        couponBox.find(".arm_apply_coupon_btn").removeClass('active');
                                        ngModel.$setValidity(res.data.validity, false);
                                    }

                                    if($parentForm.find('input[name="arm_coupon_code"]').attr('data-isRequiredCoupon') == 'true')
                                    {
                                        armUpdateOrderAmount($parentForm, coupon_amt, total_amt, arm_discount_on_plan);
                                        if (coupon_amt != '' && coupon_amt != '0') {
                                            armAnimateCounter($parentForm.find('.arm_discount_amount_text'));
                                            armAnimateCounter($parentForm.find('.arm_payable_amount_text'));
                                            if(arm_discount_on_plan!='' && arm_discount_on_plan!='0') {
                                                armAnimateCounter($parentForm.find('.arm_plan_amount_text'));
                                            }
                                        }
                                    }
                                    else {
                                        armUpdateOrderAmount($parentForm, coupon_amt, total_amt, arm_discount_on_plan);
                                        if (coupon_amt != '' && coupon_amt != '0') {
                                            armAnimateCounter($parentForm.find('.arm_discount_amount_text'));
                                            armAnimateCounter($parentForm.find('.arm_payable_amount_text'));
                                            if(arm_discount_on_plan!='' && arm_discount_on_plan!='0') {
                                                armAnimateCounter($parentForm.find('.arm_plan_amount_text'));
                                            }
                                        }
                                    }
                                    couponBox.find('.arm_apply_coupon_btn').attr('disabled', false);
                                });
                            }
                            else {
                                couponBox.find('.arm_apply_coupon_btn').attr('disabled', false);
                                ngModel.$setValidity('required_coupon', false);
                                if (plan_skin == 'skin5') {
                                    armUpdateOrderAmount1(planInput, $parentForm, '', '');
                                }
                                else {
                                    armUpdateOrderAmount($parentForm, '', '', '');
                                }
                            }
                        });
                    }
                };
            }
            input.$inject = ["$parse"];
            ARMApp.directive('input', input);
            function input($parse) {
                return {
                    restrict: 'E',
                    require: '?ngModel',
                    link: function (scope, element, attrs, ngModel) {
                        element.bind('change', function() {
                            if (attrs.class == 'arm_module_plan_input' && attrs.type != 'hidden') {
                                var gateway_skin = jQuery('[data-id="arm_front_gateway_skin_type"]').val();

                                if (gateway_skin == 'dropdown') {
                                    var container = jQuery('.arm_module_gateway_input').attr('aria-owns');
                                    var gateway_name = jQuery('#'+container).find('md-option:first').attr('value');
                                    scope.payment_gateway = gateway_name;
                                    scope.armPaymentGatewayChange();
                                }
                            }
                            scope.$apply(function() {
                                if (ngModel != null) {
                                    ngModel.$setViewValue(element.val());
                                    ngModel.$render();
                                }
                            });
                        });
                        if (attrs.ngModel && attrs.value) {
                            $parse(attrs.ngModel).assign(scope, attrs.value);
                        }
                    }
                };
            }
flnamecheck.$inject = ["$q", "$http", "$templateCache"];
        ARMApp.directive("flnamecheck", flnamecheck);
        function flnamecheck($q, $http, $templateCache) {
        return {
        require: "ngModel",
                restrict: "C",
                link: function(scope, element, attributes, ngModel) {
                scope.$watch(function() {
                ngModel.$parsers.unshift(function(viewValue) {
                var result = !/[!@#$%`\^&*(){}[\]<>?/|]/.test(viewValue);
                        ngModel.$setValidity("flnamecheck", result);
                        return viewValue;
                });
                });
                }
        };
        }

customvalidationalpha.$inject = ["$q", "$http", "$templateCache"];
        ARMApp.directive("customvalidationalpha", customvalidationalpha);
        function customvalidationalpha($q, $http, $templateCache) {
        return {
        require: "ngModel",
                restrict: "C",
                link: function(scope, element, attributes, ngModel) {
                scope.$watch(function() {
                ngModel.$parsers.unshift(function(viewValue) {

                var result = !/[^a-zA-Z._]/.test(viewValue);
                        ngModel.$setValidity("customvalidationalpha", result);
                        return viewValue;
                });
                });
                }
        };
        }

customvalidationnumber.$inject = ["$q", "$http", "$templateCache"];
        ARMApp.directive("customvalidationnumber", customvalidationnumber);
        function customvalidationnumber($q, $http, $templateCache) {
        return {
        require: "ngModel",
                restrict: "C",
                link: function(scope, element, attributes, ngModel) {
                scope.$watch(function() {
                ngModel.$parsers.unshift(function(viewValue) {

                var result = !/[^0-9._]/.test(viewValue);
                        ngModel.$setValidity("customvalidationnumber", result);
                        return viewValue;
                });
                });
                }
        };
        }

customvalidationalphanumber.$inject = ["$q", "$http", "$templateCache"];
        ARMApp.directive("customvalidationalphanumber", customvalidationalphanumber);
        function customvalidationalphanumber($q, $http, $templateCache) {
        return {
        require: "ngModel",
                restrict: "C",
                link: function(scope, element, attributes, ngModel) {
                scope.$watch(function() {
                ngModel.$parsers.unshift(function(viewValue) {

                var result = !/[^a-zA-Z0-9._]/.test(viewValue);
                        ngModel.$setValidity("customvalidationalphanumber", result);
                        return viewValue;
                });
                });
                }
        };
        }
existcheck.$inject = ["$q", "$http", "$templateCache"];
        ARMApp.directive("existcheck", existcheck);
        function existcheck($q, $http, $templateCache) {
        return {
        require: "ngModel",
                restrict: "C",
                link: function(scope, element, attributes, ngModel) {
                    element.bind('blur', function (e) {
                        var fieldName = attributes.name;
                        var currentValue = element.val();
                        if (!ngModel || !currentValue || !fieldName || element.parents('form').attr('id') == 'arm_form_edit_profile') {
                            return;
                        }
                        var data = {action: 'arm_check_exist_field', field: fieldName, value: currentValue};
                        var checkRes = $http.post(__ARMAJAXURL + '?action=arm_check_exist_field&field=' + fieldName + '&value=' + currentValue, data).then(function(res) {
                                if (res.data.check == '0') {
                                ngModel.$setValidity('existcheck', false);
                                } else {
                                ngModel.$setValidity('existcheck', true);
                                }
                            });
                    });
                    element.bind('keypress', function (e) {
                        var fieldName = attributes.name;
                        var currentValue = element.val();
                        if (!ngModel || !currentValue || !fieldName || element.parents('form').attr('id') == 'arm_form_edit_profile') {
                            return;
                        }
                        if(ngModel.$invalid==true)
                        {
                            ngModel.$setValidity('existcheck', true);
                        }
                    
                    });
                }
        };
        }


usernamecheck.$inject = ["$q", "$http", "$templateCache"];
        ARMApp.directive("usernamecheck", usernamecheck);
        function usernamecheck($q, $http, $templateCache){
        return {
        require: "ngModel",
                restrict: "C",
                link: function(scope, element, attributes, ngModel) {
                element.bind('blur', function(e){
                var fieldName = attributes.name;
                        var currentValue = element.val();
                        var pattern = /^[0-9a-z_]+$/i;
                        if (element.hasClass('arm_multisite_validate')){
                pattern = /^[0-9a-z_]+$/;
                }
                if (!pattern.test(currentValue)){
                ngModel.$setValidity('usernamecheck', false);
                } else {
                ngModel.$setValidity('usernamecheck', true);
                }
                });
                }
        };
                }
ARMApp.directive('armfileuploader', armfileuploader);
        function armfileuploader(){
        return {
        require:'ngModel',
                priority: '10',
                link:function(scope, element, attributes, ngModel){
                element.bind('change', function() {
                var fileValue = element.val();
                        var fileExt = fileValue.substring(fileValue.lastIndexOf('.') + 1).toLowerCase();
                        scope.$apply(function(){
                        ngModel.$setViewValue(element.val());
                                ngModel.$render();
                        });
                });
                        var upload_el = element.prev().find('.armFileUploaderWrapper')[0];
                        upload_el.addEventListener('drop', function(e){
                        var el = element.parents('.armFileUploadWrapper').find('input.arm_file_url');
                                el.bind('change', function(e){
                                var fileValue = jQuery(this).val();
                                        var fileExt = fileValue.substring(fileValue.lastIndexOf('.') + 1).toLowerCase();
                                        scope.$apply(function(){
                                        ngModel.$setViewValue(fileValue);
                                                ngModel.$render();
                                        });
                                });
                        })
                        var acceptExtTxt = attributes.accept;
                        ngModel.$validators.accept = function(modelValue) {
                        if (modelValue != '' && modelValue != undefined){
                        var fileExt = modelValue.substring(modelValue.lastIndexOf('.') + 1).toLowerCase();
                                var denyfileext = ["php", "php3", "php4", "php5", "pl", "py", "jsp", "asp", "exe", "cgi"];
                                if (denyfileext.indexOf(fileExt) !== - 1){
                        return false;
                        }
                        if (acceptExtTxt != undefined) {
                        var acceptExt = acceptExtTxt.replace(/\./g, '').split(",");
                                return (acceptExt.indexOf(fileExt) !== - 1);
                        }
                        }
                        return true;
                        };
                }
        }
        }
ARMApp.directive('checkStrength', checkStrength);
        function checkStrength() {
        return {
        priority: '50',
                replace: false,
                link: function (scope, iElement, iAttrs) {
                var _passedMatches = 1;
                        var strength = {
                        colors: ['#F00', '#F90', '#FF0', '#9F0', '#0F0'],
                                mesureStrength: function (p) {
                                var _force = 0;
                                        var _regex = /[!,%,&,@,#,$,^,*,?,_,~,-,+]/g;
                                        var _lowerLetters = /[a-z]+/.test(p);
                                        var _upperLetters = /[A-Z]+/.test(p);
                                        var _numbers = /[0-9]+/.test(p);
                                        var _symbols = _regex.test(p);
                                        var _flags = [_lowerLetters, _upperLetters, _numbers, _symbols];
                                        var _passedMatches = jQuery.grep(_flags, function (el) { return el === true; }).length;
                                        _force += 2 * p.length + ((p.length >= 8) ? 1 : 0);
                                        _force += _passedMatches * 10;
                                        _force = (p.length <= 5) ? Math.min(_force, 10) : _force;
                                        _force = (_passedMatches == 1) ? Math.min(_force, 20) : _force;
                                        _force = (_passedMatches == 2) ? Math.min(_force, 30) : _force;
                                        _force = (_passedMatches == 3) ? Math.min(_force, 40) : _force;
                                        _force = (_passedMatches == 4) ? Math.min(_force, 50) : _force;
                                        _force = (_force < 30 && p.length >= 10) ? 30 : _force;
                                        _force = (_force < 40 && p.length >= 15) ? 40 : _force;
                                        _force = (_force < 50 && p.length >= 20) ? 50 : _force;
                                        return _force;
                                },
                                getColor: function (s) {
                                var idx = 0;
                                        if (s <= 10) { idx = 0; }
                                else if (s <= 20) { idx = 1; }
                                else if (s <= 30) { idx = 2; }
                                else if (s <= 40) { idx = 3; }
                                else if (s <= 50) { idx = 4; }
                                else { idx = 5; }

                                return { idx: idx + 1, col: this.colors[idx] };
                                }
                        };
                        scope.$watch(iAttrs.checkStrength, function () {
                        var checkPW = scope.$eval(iAttrs.checkStrength);
                                if (checkPW === '' || checkPW == undefined) {
                        iElement.css({ "display": "inline" });
                                iElement.children('li')
                                .css({ "background": "#DDD" })
                                .slice(0, 1)
                                .css({ "background": "#F00" });
                                iElement.parents('.arm_form_input_wrapper').find('.arm_strength_meter_label').addClass('too_short').html(pwdstrength_vweak);
                        } else {
                        var c = strength.getColor(strength.mesureStrength(checkPW));
                                iElement.css({ "display": "inline" });
                                iElement.children('li')
                                .css({ "background": "#DDD" })
                                .slice(0, c.idx)
                                .css({ "background": c.col });
                                if (c.idx < 2) {
                        iElement.parents('.arm_form_input_wrapper').find('.arm_strength_meter_label').addClass('too_short').html(pwdstrength_vweak);
                        } else if (c.idx == 2) {
                        iElement.parents('.arm_form_input_wrapper').find('.arm_strength_meter_label').addClass('weak').html(pwdstrength_weak);
                        } else if (c.idx > 2 && c.idx < 5) {
                        iElement.parents('.arm_form_input_wrapper').find('.arm_strength_meter_label').addClass('good').html(pwdstrength_good);
                        } else if (c.idx > 4) {
                        iElement.parents('.arm_form_input_wrapper').find('.arm_strength_meter_label').addClass('strong').html(pwdstrength_vgood);
                        }
                        }
                        });
                },
                template: '<li class="arm_strength_meter_block" style="background: rgb(255, 0, 0);"></li><li class="arm_strength_meter_block" style="background: rgb(221, 221, 221);"></li><li class="arm_strength_meter_block" style="background: rgb(221, 221, 221);"></li><li class="arm_strength_meter_block" style="background: rgb(221, 221, 221);"></li><li class="arm_strength_meter_block" style="background: rgb(221, 221, 221);"></li>'
        };
        }
ARMApp.directive("compare", compare);
        function compare() {
        return {
        require: "ngModel",
                transclude: true,
                link: function(scope, element, attrs, ngModel) {
                ngModel.$validators.compare = function(modelValue) {
                var parentForm = element.parents('form');
                        var refInputVal = parentForm.find('.' + attrs.compare).val();
                        var v = (element.val() === refInputVal);
                        return v;
                };
                        if (element.val() != undefined) {
                scope.$watch(function() {
                ngModel.$validate();
                });
                }
                }
        };
        }
ARMApp.directive('armlowercase', armlowercase);
        function armlowercase(){
        return {
        priority: '51',
                restrict: 'A',
                require: "ngModel",
                link: function(scope, el, attr, ngModel) {
                ngModel.$validators.armlowercase = function(modelValue) {
                var resultLow = /[a-z]/.test(modelValue);
                        return resultLow;
                };
                }
        };
        }
ARMApp.directive('armuppercase', armuppercase);
        function armuppercase(){
        return {
        priority: '52',
                restrict: 'A',
                require: "ngModel",
                link: function(scope, el, attr, ngModel) {
                ngModel.$validators.armuppercase = function(modelValue) {
                var resultUp = /[A-Z]/.test(modelValue);
                        return resultUp;
                };
                }
        };
        }
ARMApp.directive('armnumeric', armnumeric);
        function armnumeric(){
        return {
        priority: '53',
                restrict: 'A',
                require: "ngModel",
                link: function(scope, el, attr, ngModel) {
                ngModel.$validators.armnumeric = function(modelValue) {
                var resultNum = /[0-9]/.test(modelValue);
                        return resultNum;
                };
                }
        };
        }
ARMApp.directive('armspecial', armspecial);
        function armspecial(){
        return {
        priority: '54',
                restrict: 'A',
                require: "ngModel",
                link: function(scope, el, attr, ngModel) {
                ngModel.$validators.armspecial = function(modelValue) {
                var resultSpl = /[!,%,&,@,#,$,^,*,?,_,~,-,+]/.test(modelValue);
                        return resultSpl;
                };
                }
        };
        }
jQuery(document).bind('click', function (e) {
var $clicked = jQuery(e.target);
        var mdSelectList = jQuery('md-select .md-select-menu-container');
        if (!mdSelectList.is($clicked) && mdSelectList.has($clicked).length === 0) {
mdSelectList.fadeOut();
}
});