"use strict";

jQuery(document).on('click', '.arm_activate_multisite, .arm_deactivate_multisite', function(){
    jQuery(this).parents(".arm_multisite_btn_div").find(".arm_confirm_activation_box").show();
});

jQuery(document).on('click', '#arm_multisite_cancel_activate_btn, #arm_multisite_cancel_deactivate_btn', function(){
    jQuery(this).parents(".arm_confirm_activation_box").hide();
});

jQuery(document).on("click", '.arm_mutisite_subsite_activate_btn', function(){

    var arm_delete_button_label = jQuery("#arm_delete_button_label").val();
    var arm_activate_button_label = jQuery("#arm_activate_button_label").val();
    var arm_deactivate_button_label = jQuery("#arm_deactivate_button_label").val();
    var $this = jQuery(this);
    var $btn_container = jQuery($this).parents(".arm_multisite_btn_div");
    var $status_node = jQuery(this).parents(".arm_multisite_list_item").find("td.arm_user_status");
    var $action_node = jQuery(this).parents("td.arm_action_status");
    var $plan_node = jQuery(this).parents(".arm_multisite_list_item").find("td.arm_current_user_plan");
    var userId = jQuery(this).attr('data-userid');
    var siteId = jQuery(this).attr('data-siteid');   
    var planId = jQuery(this).attr('data-userplan');   
    var is_mulitiple_membership = (jQuery(this).attr("data-is_multiple_membership") != undefined) ? jQuery(this).attr("data-is_multiple_membership") : 0;
    var change_plan_id = 0;
    if(is_mulitiple_membership == 1) {
        change_plan_id = jQuery(this).parents(".arm_confirm_activation_box").find('select').val();
        change_plan_id = (change_plan_id != null) ? change_plan_id : 0;
    }
    /*console.log("planId : "+planId);
    console.log("planId : "+planId);
    console.log("userId : "+userId);
    console.log("siteId : "+siteId);
    return false;*/
    var multisiteContainer = jQuery(this).parents('.arm_multisite_container');

    var multisiteActiveConfirm = jQuery(this).attr('data-msg');
    //var multisite_activate_confirm = confirm(multisiteActiveConfirm);

    jQuery('.arm_multisite_loader_container').show(); 
    //if(multisite_activate_confirm) {
    
    var do_request = 0;
    var post_data = "";
    if(is_mulitiple_membership==1) {
        if(change_plan_id!=0) {
            do_request = 1;
            post_data = 'action=arm_multisite_activate&userId='+userId+'&siteId='+siteId+'&planId='+planId+'&change_plan_id='+change_plan_id+"&delete_button_label="+arm_delete_button_label+"&activate_button_label="+arm_activate_button_label+"&deactivate_button_label="+arm_deactivate_button_label;
        }
    }
    if(is_mulitiple_membership==undefined || is_mulitiple_membership==0 ) {
        do_request = 1;
        post_data = 'action=arm_multisite_activate&userId='+userId+'&siteId='+siteId+'&planId='+planId+"&delete_button_label="+arm_delete_button_label+"&activate_button_label="+arm_activate_button_label+"&deactivate_button_label="+arm_deactivate_button_label;
    }
    console.log("is_mulitiple_membership : "+is_mulitiple_membership);
    console.log("change_plan_id : "+change_plan_id);
    console.log("do_request : "+do_request);
    if(do_request==1) {
        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            dataType: 'json',
            data: post_data,
            success: function(res) {
                jQuery('.arm_multisite_loader_container').hide();
                
                if (res.status == 'success') {
                    jQuery($status_node).html(res.status_label);
                    jQuery($btn_container).html("");
                    jQuery($btn_container).html(res.html_content);
                    var message = '<div class="arm_success_msg"><ul><li>'+ res.message +'</li></ul></div>';
                    if( '' != res.changed_plan_name ) {
                        jQuery($plan_node).html(res.changed_plan_name);
                    }
                    multisiteContainer.find('.arm_setup_messages').html('');
                    multisiteContainer.find('.arm_setup_messages').html(message).show().delay(5000).fadeOut(2000);
                } else if(res.status == 'warning') {
                    var message = '<div class="arm_error_msg"><ul><li>'+ res.message +'</li></ul></div>';
                    multisiteContainer.find('.arm_setup_messages').html('');
                    multisiteContainer.find('.arm_setup_messages').html(message).show().delay(5000).fadeOut(2000);
                } else {
                    console.log(res.message);
                }

                if (res.status != 'redirect') {            
                    jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: multisiteContainer.find('.arm_setup_messages').offset().top - 160}, 1000);
                }
                jQuery($this).parents(".arm_confirm_activation_box").hide();
            },
        });
    } else {
        jQuery('.arm_multisite_loader_container').hide();
    }

});


jQuery(document).on('click', '.arm_mutisite_subsite_deactivate_btn', function() {
    var arm_delete_button_label = jQuery("#arm_delete_button_label").val();
    var arm_activate_button_label = jQuery("#arm_activate_button_label").val();
    var arm_deactivate_button_label = jQuery("#arm_deactivate_button_label").val();
    var $this = jQuery(this);
    var $btn_container = jQuery($this).parents(".arm_multisite_btn_div");
    var $status_node = jQuery(this).parents(".arm_multisite_list_item").find("td.arm_user_status");
    var $action_node = jQuery(this).parents("td.arm_action_status");
    var $plan_node = jQuery(this).parents(".arm_multisite_list_item").find("td.arm_current_user_plan");
    var userId = jQuery(this).attr('data-userid');
    var siteId = jQuery(this).attr('data-siteid');   
    var planId = jQuery(this).attr('data-userplan');
    var site_action = "Deactive";
    /*console.log("planId : "+planId);
    console.log("planId : "+planId);
    console.log("userId : "+userId);
    console.log("siteId : "+siteId);
    return false;*/
    var multisiteContainer = jQuery(this).parents('.arm_multisite_container');

    jQuery('.arm_multisite_loader_container').show(); 

    jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        dataType: 'json',
        data: 'action=arm_multisite_deactivate&userId='+ userId + '&site_id='+ siteId+'&site_action='+site_action+'&planId='+planId+"&delete_button_label="+arm_delete_button_label+"&activate_button_label="+arm_activate_button_label+"&deactivate_button_label="+arm_deactivate_button_label,
        success : function(res) {
            
            if('success'==res.status) {
                jQuery($btn_container).html("");
                jQuery($btn_container).html(res.html_content);
                jQuery($status_node).html(res.status_label);
                //jQuery($action_node).find(".arm_activate_multisite").html(res.button_label);
                var message = '<div class="arm_success_msg"><ul><li>'+ res.message +'</li></ul></div>';
                multisiteContainer.find('.arm_setup_messages').html("");
                multisiteContainer.find('.arm_setup_messages').html(message).show().delay(5000).fadeOut(2000);
                jQuery('.arm_multisite_loader_container').hide();
            } else {
                jQuery('.arm_multisite_loader_container').hide();
                console.log(res.message)
            }
            if (res.status != 'redirect') {            
                jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: multisiteContainer.find('.arm_setup_messages').offset().top - 160}, 1000);
            }
            jQuery($this).parents(".arm_confirm_activation_box").hide();
        },
    }); 


});


jQuery(document).on("click", '.arm_delete_multisite', function(){

    var userId = jQuery(this).attr('data-userid');
    var siteId = jQuery(this).attr('data-siteid');
    var usermultisiteCancel = jQuery(this).attr('data-msg');
    var multisite_cancel_confirm = confirm(usermultisiteCancel);
    var multisiteContainer = jQuery(this).parents('.arm_multisite_container');
    var loader_img = multisiteContainer.find('#loader_img').val();
    jQuery('.arm_multisite_loader_container').show();
    var arm_multisite_site_count = multisiteContainer.find('#arm_multisite_site_count').val();
    var arm_multisite_no_record_msg = multisiteContainer.find('#arm_multisite_no_record_msg').val();
    if(multisite_cancel_confirm){
        jQuery.ajax({
            type: "POST",
            url: __ARMAJAXURL,
            dataType: 'json',
            data: 'action=arm_multisite_delete&userId='+ userId + '&siteId='+ siteId,
           
            success: function (res) {
                jQuery('.arm_multisite_loader_container').hide();
                var message = res.message;

                if (res.status == 'success') {
                    var message = '<div class="arm_success_msg"><ul><li>'+ message +'</li></ul></div>';
                    jQuery('#arm_manage_multisite_row_'+siteId).remove();
                    arm_multisite_site_count = arm_multisite_site_count - 1 ;
                    multisiteContainer.find('#arm_multisite_site_count').val(arm_multisite_site_count);
                    
                    if(arm_multisite_site_count==0)
                    {
                        jQuery( "<tr class='arm_multisite_list_item'><td colspan='4'>"+ arm_multisite_no_record_msg +"</td></tr>" ).insertAfter( ".arm_multisite_list_header" );
                    }
                    multisiteContainer.find('.arm_setup_messages').html('');
                    multisiteContainer.find('.arm_setup_messages').html(message).show().delay(5000).fadeOut(2000);
                }
                else {
                    var message = '<div class="arm_error_msg"><ul><li>'+ message +'</li></ul></div>';
                    multisiteContainer.find('.arm_setup_messages').html('');
                    multisiteContainer.find('.arm_setup_messages').html(message).show();
                }
                
                if (res.type != 'redirect') {
                
                    jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: multisiteContainer.find('.arm_setup_messages').offset().top - 50}, 1000);
                    multisiteContainer.find('.arm_setup_messages').html(message).show().delay(5000).fadeOut(2000);
                }
                return false;
            }
        });

    }
    else
    {
        jQuery('.arm_multisite_loader_container').hide();
    }
        
}); 

function arm_site_creation_submit(form)
{
    var formdata = form.serialize();
    var site_name =jQuery('#arm_site_name').val();
    var site_title =jQuery('#arm_site_title').val();
    var $formContainer = jQuery(form).parents('.arm_multisite_form');
    var arm_current_user_plan=jQuery('#arm_subsite_plan').val();
    if(site_name!='' && site_title!='' && formdata!='')
    {
        jQuery('#arm_site_creation_submit').addClass('active');
        jQuery.ajax({
            type:'POST',
            url:__ARMAJAXURL,
            data:'action=arm_site_creation&site_name='+site_name+'&site_title='+site_title+'&arm_current_user_plan='+arm_current_user_plan+'&formdata='+formdata,
            dataType: 'json',
            beforeSend: function () {
                jQuery(form).find("input[type='submit'], button[type='submit']").attr('disabled', 'disabled').addClass('active');
            },
            success:function (res){
                jQuery(form).find("input[type='submit'], button[type='submit']").removeAttr('disabled').removeClass('active');
                var message = res.message;

                if (res.status == 'success') {
                    var site_limit_msg = res.site_limit_note;    
                    var message = '<div class="arm_success_msg"><ul><li>'+ message +'</li></ul></div>';
                    $formContainer.find('.arm_setup_messages').html('');
                    $formContainer.find('.arm_setup_messages').html(message).show().delay(5000).fadeOut(2000);
                    if(jQuery(".arm_site_limit_message").length > 0) {
                        $formContainer.find('.arm_site_limit_message').html("");
                        $formContainer.find('.arm_site_limit_message').html(site_limit_msg);    
                    }
                    
                    if (res.type != 'redirect') {
                        jQuery(form).trigger("reset");
                        jQuery(form).find('input').trigger("change");
                        jQuery(form).find('input').parent().removeClass('md-input-has-value').trigger("change");
                    }
                }
                else {

                    var message = '<div class="arm_error_msg"><ul><li>'+ message +'</li></ul></div>';
                    $formContainer.find('.arm_setup_messages').html('');
                    $formContainer.find('.arm_setup_messages').html(message).show();
                }
                
                if (res.type != 'redirect') {
                
                    jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: $formContainer.find('.arm_setup_messages').offset().top - 50}, 1000);
                    $formContainer.find('.arm_setup_messages').html(message).show().delay(5000).fadeOut(2000);
                }
            }
        });
    }
}


jQuery(document).on('click', '.arm_setup_submit_btn', function($event){
    var planId = 0;    
    var is_mulitiple_membership = 'false';
    var max_site_cnt = 0;
    var created_site_cnt = 0;
    var message = "";

    if( 'skin5' == jQuery("input[name='arm_front_plan_skin_type']").val() ) {
        jQuery(".md-select-menu-container").find(".armMDOption").each(function(){
            if( 'true' == jQuery(this).attr("aria-selected")) {
                planId = jQuery(this).attr("value");
                is_mulitiple_membership = (undefined !== jQuery(this).attr("data-is_multiple_membership")) ? jQuery(this).attr("data-is_multiple_membership") : 'false';
                max_site_cnt = (undefined !== jQuery(this).attr("data-max_multisite_cnt")) ? jQuery(this).attr("data-max_multisite_cnt") : 0;
                created_site_cnt = (undefined !== jQuery(this).attr("data-multisite_created_cnt")) ? jQuery(this).attr("data-multisite_created_cnt") : 0;
                message = (undefined !== jQuery(this).attr("data-multisite_error_msg")) ? jQuery(this).attr("data-multisite_error_msg") : '';                
            }
        });
        
    } else {
        jQuery("input[name='subscription_plan']").each(function(){
            if(jQuery(this).is(":checked")) {
                
                planId = jQuery(this).val();
                is_mulitiple_membership = (undefined !== jQuery(this).attr("data-is_multiple_membership")) ? jQuery(this).attr("data-is_multiple_membership") : 'false';
                max_site_cnt = (undefined !== jQuery(this).attr("data-max_multisite_cnt")) ? jQuery(this).attr("data-max_multisite_cnt") : 0;
                created_site_cnt = (undefined !== jQuery(this).attr("data-multisite_created_cnt")) ? jQuery(this).attr("data-multisite_created_cnt") : 0;
                message = (undefined !== jQuery(this).attr("data-multisite_error_msg")) ? jQuery(this).attr("data-multisite_error_msg") : '';
            }
        }); 
    }
    if('true'==is_mulitiple_membership) {
        return true;
    } else {
        if(0!=planId && max_site_cnt < created_site_cnt ) {
        
            var message_content = '<div class="arm_error_msg"><ul><li>'+ message +'</li></ul></div>';
            jQuery(".arm_setup_form_container").find('.arm_setup_messages').html('');
            jQuery(".arm_setup_form_container").find('.arm_setup_messages').html(message_content).show();
            jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: jQuery(".arm_setup_form_container").find('.arm_setup_messages').offset().top - 160}, 1000);
            jQuery(".arm_setup_form_container").find('.arm_setup_messages').html(message_content).show().delay(5000).fadeOut(2000);
            
            return false;
        } else {
            return true;
        }
    }
    

});


jQuery(document).on("click", ".arm_page_numbers", function() {

    var arm_delete_button_label = jQuery("#arm_delete_button_label").val();
    var arm_activate_button_label = jQuery("#arm_activate_button_label").val();
    var arm_deactivate_button_label = jQuery("#arm_deactivate_button_label").val();

    var per_page = jQuery(this).attr("data-per_page");
    //var current_page = jQuery(".current.arm_page_numbers").attr("data-page");
    var current_page = parseInt(jQuery(this).attr("data-page"), 10);
    var total_page = parseInt(jQuery(this).attr("data-total_page"), 10);
    var $this = jQuery(this);
    var $selected_page = jQuery(".arm_paging_links").find(".current.arm_page_numbers:not('.arm_prev'):not('.arm_next')");
    
    var prev_page = current_page - 1;
    var next_page = current_page + 1;
    if(total_page >= current_page ) {
        jQuery('.arm_multisite_loader_container').show();
        jQuery.ajax({
            type:'POST',
            url:__ARMAJAXURL,
            data: 'action=paging_multisite_list&current_page='+current_page+"&per_page="+per_page+"&delete_button_label="+arm_delete_button_label+"&activate_button_label="+arm_activate_button_label+"&deactivate_button_label="+arm_deactivate_button_label,
            dataType: 'json',
            success: function(res) {
                if('success' == res.status) {
                    jQuery("a.arm_page_numbers").removeClass("current");

                    if(jQuery($this).hasClass("arm_prev")) {
                        jQuery($selected_page).prev().addClass("current");
                    } else if(jQuery($this).hasClass("arm_next")) {
                        jQuery($selected_page).next().addClass("current");
                    } else {
                        jQuery($this).addClass("current");    
                    }
                    
                    jQuery(".arm_paging_wrapper_subsite_listing").html(res.pagination_link);
                    jQuery(".arm_page_numbers.arm_prev").attr("data-page", prev_page);
                    jQuery(".arm_page_numbers.arm_next").attr("data-page", next_page);
                    jQuery(".arm_multisite_list_table").find(".arm_multisite_list_item").remove();
                    jQuery(".arm_multisite_list_table").find("tbody").append(res.body);
                }
                jQuery('.arm_multisite_loader_container').hide();
            }
        });    
    }
    
    
});