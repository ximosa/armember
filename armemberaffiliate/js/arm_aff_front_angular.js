"use strict";

ARMApp.controller('ARMCtrlaffr', ARMCtrlaffr);
function ARMCtrlaffr($scope, $http, $timeout) {
    $scope.arm_form = {};
    var original = $scope.arm_form;
    $scope.armreferralsForm = function(arm_site_type){
        $scope.arm_form.arm_site_type = arm_site_type;
    }

    $scope.isarmreferralsFormField = function(arm_site_type){
        
        if ($scope.arm_form.arm_site_type == arm_site_type ) {
        return true;
        }
        return false;
    }
    
    $scope.armreferralsSubmitBtnClick = function($event){
        
        if ($event.isTrigger != undefined && $event.isTrigger){
            $event.preventDefault();
            return false;
        }
    }
    $scope.armreferralsFormSubmit = function(isValid, formID, $event){
        if (isValid) {
            if(is_error==1) {
                var msg = jQuery(".referrals_email").attr("data-arm_min_len_msg");
                var msg_content = '<div data-ng-message="required" class="arm_error_msg md-input-message-animation ng-scope" style="opacity: 1; margin-top: 0px;"><div class="arm_error_box_arrow"></div>'+msg+'</div>';
                jQuery(".referrals_email").parents(".arm_form_input_container").find(".arm_error_msg_box").html(msg_content);
                jQuery(window).scrollTop();
                jQuery(".referrals_email").focus();
                return false;
            } 
            var form = jQuery('#' + formID);
            
            jQuery(form).find('.arm_form_field_submit_button').addClass('active');
            arm_referrals_invite_submit(jQuery('#' + formID));
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
    }
}

ARMApp.controller('ARMCtrlaff', ARMCtrlaff);

function ARMCtrlaff($scope, $http, $timeout) {

    $scope.arm_form = {};
    var original = $scope.arm_form;

    $scope.armaffiliateSubmitBtnClick = function($event){
        if ($event.isTrigger != undefined && $event.isTrigger){
        $event.preventDefault();
                return false;
        }
    }

    $scope.armaffFormSubmit = function(isValid, formID, $event){
        if (isValid) {
           
            arm_aff_reg_form_ajax_action(jQuery('#' + formID));

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
    }
}

var is_error = 0;

function ref_validate_field_len(obj) {
    if('' == obj.value) {
        var err_msg = jQuery(obj).attr("data-msg-required");
        var msg_content = '<div data-ng-message="required" class="arm_error_msg md-input-message-animation ng-scope" style="opacity: 1; margin-top: 0px;"><div class="arm_error_box_arrow"></div>'+err_msg+'</div>';
        jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html(msg_content);
        is_error = 1;
        return false;
    } else {
        var emails = obj.value.split(',');
        var invalidEmails = [];
        for (var i = 0; i < emails.length; i++) { 
            if(!ref_validateEmail(emails[i].trim())) {
              invalidEmails.push(emails[i].trim())
            }
        }
        if(invalidEmails.length > 0) { 
            var err_msg = jQuery(obj).attr("data-arm_min_len_msg");
            var msg_content = '<div data-ng-message="required" class="arm_error_msg md-input-message-animation ng-scope" style="opacity: 1; margin-top: 0px;"><div class="arm_error_box_arrow"></div>'+err_msg+'</div>';
            jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html(msg_content);
            is_error = 1;
            return false;
        } else {
            jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html("");
            is_error = 0;
            return true;
        }    
    }
    
}

function ref_validate_field_value(e, obj) {
    var keyCode = e.keyCode == 0 ? e.charCode : e.keyCode;
    
        var valid_char = [39, 37, 18, 9, 8, 46, 20, 17];
        if(valid_char.indexOf(keyCode) >= 0 || (keyCode >= 65 && keyCode <= 90) || (keyCode >= 96 && keyCode <= 105) ) {
            return true;
        } else {
            return false;
        }
    
}
function ref_validateEmail(email) {
    var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
    return re.test(email);
}