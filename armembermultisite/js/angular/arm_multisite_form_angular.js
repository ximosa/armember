"use strict";

ARMApp.controller('ARMCtrl', ARMCtrl);
function ARMCtrl($scope, $http, $timeout) {
    $scope.arm_form = {};
    var original = $scope.arm_form;
    $scope.armMultisiteForm = function(arm_site_type){
    $scope.arm_form.arm_site_type = arm_site_type;
    }

    /*$scope.isarmMultisiteValidateField = function(arm_site_type) {
        console.log("rpt_log");
        console.log(arm_site_type);
        return true;
    }*/

    $scope.isarmMultisiteFormField = function(arm_site_type){

        if ($scope.arm_form.arm_site_type == arm_site_type ) {
        return true;
        }
        return false;
    }
    
    $scope.armSubmitBtnClick = function($event){

        if ($event.isTrigger != undefined && $event.isTrigger){
            $event.preventDefault();
            return false;
        }
    }
    $scope.armMultisiteFormSubmit = function(isValid, formID, $event){
        if (isValid) {
            if(is_error==1) {
                var msg = jQuery(".site_name").attr("data-arm_min_len_msg");
                var msg_content = '<div data-ng-message="required" class="arm_error_msg md-input-message-animation ng-scope" style="opacity: 1; margin-top: 0px;"><div class="arm_error_box_arrow"></div>'+msg+'</div>';
                //console.log("msg:"+msg);
                jQuery(".site_name").parents(".arm_form_input_container").find(".arm_error_msg_box").html(msg_content);
                jQuery(window).scrollTop();
                jQuery(".site_name").focus();
                return false;
            } 
            var form = jQuery('#' + formID);
            
            jQuery(form).find('.arm_setup_submit_btn').addClass('active');
            setTimeout(function(){
            arm_site_creation_submit(jQuery('#' + formID));  }, 2000);
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


var is_error = 0;

function validate_field_len(obj) {
    if('' == obj.value) {
        var err_msg = jQuery(obj).attr("data-msg-required");
        var msg_content = '<div data-ng-message="required" class="arm_error_msg md-input-message-animation ng-scope" style="opacity: 1; margin-top: 0px;"><div class="arm_error_box_arrow"></div>'+err_msg+'</div>';
        jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html(msg_content);
        is_error = 1;
        return false;
    } else {
        if(obj.value.length < 4) { 
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

function validate_field_value(e, obj) {
    var keyCode = e.keyCode == 0 ? e.charCode : e.keyCode;
    //console.log(keyCode);
    //var letters = /^[0-9a-z]+$/;
    //if(obj.value.length != 0){
        /*if(obj.value.match(letters)) {
            if( obj.value.length < 4 ) {
                var err_msg = jQuery(obj).attr("data-arm_min_len_msg");
                jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html(err_msg);
            } else {
                //jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html("");
                return true;
            }
        } else {
            // var err_msg = jQuery(obj).attr("data-arm_validation_msg");
            // jQuery(obj).parents(".arm_form_input_container").find('.arm_error_msg_box').html(err_msg);
            return false;
        }*/
        //(keyCode >= 48 && keyCode <= 57)
        var valid_char = [39, 37, 18, 9, 8, 46, 20, 17];
        if(valid_char.indexOf(keyCode) >= 0 || (keyCode >= 65 && keyCode <= 90) || (keyCode >= 96 && keyCode <= 105) ) {
            return true;
        } else {
            return false;
        }
        
    //}
    
}
