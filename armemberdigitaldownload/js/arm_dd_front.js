jQuery(document).on('click', '.arm_dd_tags', function() {
    var arm_tag = jQuery(this).attr('data-tag');
    var arm_tag_key = jQuery(this).attr('data_tag_key');   
    jQuery('.arm_dd_download_item_content').slideUp('slow');
    jQuery('.arm_dd_download_item_content').html('');
    jQuery('.'+arm_tag).html("Loading...");
    setTimeout(function () {
        jQuery('.'+arm_tag).slideDown('slow');
    }, 500);
    if (arm_tag != '' && arm_tag != 0) {
    	jQuery.ajax({
    	    type: "POST",
    	    url: ajaxurl,
    	    dataType: 'json',
    	    data: "action=arm_dd_tag_item_list&arm_dd_tag=" + arm_tag_key,
    	    success: function (res) {
    		if (res.type == 'success') {
                jQuery('.'+arm_tag).html(res.msg);
    		} else {
    		    jQuery('.'+arm_tag).html(res.msg);
    		}
    	    }
    	});
    }
    return false;
});


