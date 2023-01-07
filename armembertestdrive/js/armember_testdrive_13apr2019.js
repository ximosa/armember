var ajaxurl = jQuery('#ajaxurl').val();
jQuery.fn.serializeObject = function() {
    var o = {};
    var a = this.serializeArray();
    jQuery.each(a, function() {
        if (o[this.name]) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

function validate_form() {
    jQuery('.armember_testdrive_response_err_div').html("");
    jQuery('.armember_testdrive_loader').css('display', 'inline-block');
    var form_val = jQuery('.try-demo').serializeObject();
    jQuery(".mp-submit").attr('disabled','disabled');
    jQuery.ajax({
        url: ajaxurl,
        data: form_val,
        method: 'POST',
        success: function(res) {
            if (res.success === false) {
                res.errors[0];
                jQuery('.armember_testdrive_loader').css('display', 'none');
                jQuery('.armember_testdrive_response_err_div').html('<h4>' + res.errors[0] + '</h4>');
            }
            if (res.success === true) {
                var login = res.data.login;
                var password = res.data.password;
                var url = res.data.demo_url;
                var iframe = document.createElement('iframe');
                iframe.id = 'sandbox_access';
                iframe.setAttribute('style', 'display:none;');
                iframe.setAttribute('src', url);
                console.log(url);
                jQuery('body').append(iframe);
                jQuery("#sandbox_access").on('load', function(e) {
                    jQuery('.armember_testdrive_loader').css('display', 'none');
                    jQuery('.try-demo').slideUp('slow');
                    jQuery('.mp_demo_success').slideDown('slow');
                    //var site_url = jQuery(this)[0].contentDocument.URL;
                    var site_url = "";
                    if(jQuery(this)[0].src!==undefined)
                    {
                        site_url = jQuery(this)[0].src;
                    }
                    else if(jQuery(this)[0].contentDocument.URL!==undefined){
                        site_url = jQuery(this)[0].contentDocument.URL;
                    }
                    //console.log(site_url);
                    var is_ssl = jQuery('#is_ssl').val() || false;
                    if( is_ssl ){
                    	site_url = site_url.replace('http://','https://');
                    }
                    var final_url = site_url.split('?');
                    final_url = final_url[0].replace('wp-login.php', 'wp-admin');
                    jQuery('#testdrive_created_site_url a').attr('href', site_url);
                    jQuery('#testdrive_created_site_url a').html(final_url);
                    
                    //console.log(final_url);
                    
                    jQuery('#testdrive_created_login').text(login);
                    jQuery('#testdrive_created_password').text(password);
                    //jQuery("#sandbox_access").remove();
                });
            }
        },
    });
    return false;
}