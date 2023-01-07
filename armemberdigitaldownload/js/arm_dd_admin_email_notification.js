/******* Start email notification page events and function *******/
jQuery( document ).ajaxSuccess(function( event, xhr, settings ) {
    //console.log(settings.url);
    //str = JSON.stringify(settings);
    //console.log("setting " + str);
    //console.log(settings.data);
    var ajax_data = settings.data;
    if (ajax_data.indexOf("arm_edit_template_data") >= 0) {
        var response = jQuery.parseJSON(xhr.responseText);
        if(response.arm_template_slug == 'user-download-file-admin') {
            jQuery('.arm_email_notification_dd_shortcode').show();
        } else {
            jQuery('.arm_email_notification_dd_shortcode').hide();
        }
    }
});