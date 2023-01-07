var active_tab = "";
jQuery(document).on('click', '.arm_profile_tab_menu_container ul li a.arm_profile_tab', function () {
    if(!jQuery(this).hasClass('arm_nav_redirect')) {
        var item_id = jQuery(this).attr('data-item_id');
        active_tab = item_id;
        jQuery(".arm_com_post_comment_nav").removeClass("arm_comment_active");
        jQuery(".arm_com_post_comment_box").slideUp();
        jQuery(".arm_com_post_commnet_single.arm_com_comment_new_loaded").remove('');
        jQuery(".arm_post_comment_load_more_nav span").attr("data-pageno", 2);
        jQuery(".arm_post_comment_load_more_nav").show();
        arm_profile_hide_show_section(item_id);
        jQuery(".arm_com_main_tab_btn span").text(jQuery(this).text().trim());
        if(jQuery(window).width() < 768) {
            jQuery(".arm_com_main_tab_btn_arrow_up").removeClass("arm_com_main_tab_btn_arrow_current");
            jQuery(".arm_com_main_tab_btn_arrow_down").addClass("arm_com_main_tab_btn_arrow_current");
            jQuery(".arm_profile_tab_menu_ul").slideUp("fast");
        }
    }
});

function arm_profile_hide_show_section(section_name) {
    jQuery('.arm_profile_tab_contant_container ul li.arm_profile_li_container').addClass('arm_hide');
    jQuery('.arm_profile_tab_contant_container ul li.arm_section_' + section_name).removeClass('arm_hide');
    jQuery('.arm_profile_tab_menu_container .arm_profile_tab[data-item_id="' + section_name + '"]').addClass('active');
    jQuery('.arm_profile_tab_menu_container ul li').removeClass('active');
    jQuery('.arm_profile_tab_menu_container .arm_profile_tab[data-item_id="' + section_name + '"].active').parent('li').addClass('active');
}

jQuery(document).on('click', '#arm_com_post_btn, #arm_com_post_edit_btn', function () {
    var error_count = 0;
    var arm_title = jQuery('#arm_title').val().trim();
    var arm_description = jQuery("#arm_description").val().trim();

    if (arm_title == '') {
        jQuery('.arm_com_post_title_error').show();
        error_count++;
    }
    else {
        jQuery('.arm_com_post_title_error').hide();
    }

    if (arm_description == '') {
        jQuery('.arm_com_post_desc_error').show();
        error_count++;
    }
    else {
        jQuery('.arm_com_post_desc_error').hide();
    }

    if (error_count > 0) {
        return false;
    }
    else {
        var file_data = "";
        if(jQuery('#arm_com_post_featur_image').prop('files')!=undefined)
        {
            file_data = jQuery('#arm_com_post_featur_image').prop('files')[0];
        }
        var post_added_type = jQuery('#arm_com_post_added_type').val();
        var post_id = jQuery(".arm_com_post_added_type input#arm_com_post_id").val();
        post_form_mode = jQuery(".arm_com_post_added_type input#arm_com_post_mode").val();
        var form_data = new FormData();
        form_data.append('arm_title', arm_title);
        form_data.append('arm_description', arm_description);
        form_data.append('arm_added_type', post_added_type);
        form_data.append('arm_form_mode', post_form_mode);
        form_data.append('file', file_data);
        form_data.append('post_id', post_id);
        form_data.append('remove_attachment', arm_com_post_attachment_remove);
        form_data.append('action', 'arm_com_post_add');
        var $this = jQuery(this);
        $this.attr('disabled', 'disabled').addClass('active');
        jQuery(".arm_com_post_form_loader").show();
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            contentType: false,
            processData: false,
            data: form_data,
            success: function (response)
            {
                if (response.type == 'success') {
                    jQuery('.arm_post_add_success').html(response.msg).show().delay(5000).fadeOut(400);
                    if(jQuery('.arm_com_posts_box').length==0) {
                        jQuery(".arm_com_post_display_container .arm_com_post_no_msg").hide();
                    }
                    
                    if(post_form_mode == "edit") {
                        jQuery('.arm_com_post_wrapper .arm_com_post_box_' + post_id).html("").html(response.content);
                    }
                    else {
                        if(jQuery(".arm_posts_paging_div").length > 0 && jQuery(".arm_posts_paging_div .arm_paging_info").length > 0) {
                            var current_page = jQuery(".arm_posts_paging_div .arm_page_numbers.current:not(.arm_prev.current, .arm_next.current)").text().trim();
                            if( parseInt(current_page) == 1 ) {
                                jQuery('.arm_com_post_wrapper').prepend(response.content);
                            }
                            else {
                                jQuery(".arm_com_wall_post_display_container .arm_com_post_box_wrapper").prepend(response.content);
                            }
                        }
                        else {
                            jQuery('.arm_com_post_wrapper').prepend(response.content);
                        }
                    }

                    jQuery(".arm_com_wall_post_display_container .arm_com_post_wrapper .arm_com_post_box_" + response.post_id + " .arm_com_post_title").remove();

                    jQuery(".arm_com_wall_post_display_container .arm_com_post_wrapper .arm_com_post_box_" + response.post_id + " .arm_com_post_meta").remove();

                    jQuery(".arm_com_wall_post_display_container .arm_com_post_wrapper .arm_com_post_box_" + response.post_id + " .arm_com_post_content_box_wrapper").prepend(response.arm_post_wall_section);

                    jQuery(".arm_com_wall_post_display_container .arm_com_no_message").css("display", "none");
                }
                else {
                    jQuery('.arm_post_add_error').html(response.msg).show().delay(5000).fadeOut(100);
                }
                arm_reset_post_form(post_added_type, '', '', '', 'add');
                jQuery(".arm_com_post_form_loader").hide();
            }
        });
    }
    return false;
});

jQuery(document).on('click', '.arm_com_post_like_nav', function () {
    var post_id = jQuery(this).attr('data-post_id');
    jQuery(this).prop("disabled", true);
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: 'action=arm_com_post_like&post_id=' + post_id,
        success: function (response)
        {
            if (response.type == 'success') {
                var parent = jQuery(".arm_com_post_box_"+post_id+" .arm_com_post_action_section");
                parent.find(".arm_com_post_like_nav").remove();
                parent.prepend(response.content);

                if(active_tab == "wall_post") {
                    var ttl_likes = jQuery(".arm_com_wall_post_display_container .arm_com_post_box_" + post_id + " .arm_post_total_likes").text();
                }
                else {
                    var ttl_likes = jQuery(".arm_com_post_display_container .arm_com_post_box_" + post_id + " .arm_post_total_likes").text();
                }

                ttl_likes = parseInt(ttl_likes) + 1;

                jQuery(".arm_com_post_box_" + post_id + " .arm_post_total_likes").text(ttl_likes);
            }
        }
    });
});

jQuery(document).on('click', '.arm_com_post_dislike_nav', function () {
    var post_id = jQuery(this).attr('data-post_id');
    jQuery(this).prop("disabled", true);
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: 'action=arm_com_post_unlike&post_id=' + post_id,
        success: function (response)
        {
            if (response.type == 'success') {
                var parent = jQuery(".arm_com_post_box_"+post_id+" .arm_com_post_action_section");
                parent.find(".arm_com_post_dislike_nav").remove();
                parent.prepend(response.content);
                if(active_tab == "wall_post") {
                    var ttl_likes = jQuery(".arm_com_wall_post_display_container .arm_com_post_box_" + post_id + " .arm_post_total_likes").text();
                }
                else {
                    var ttl_likes = jQuery(".arm_com_post_display_container .arm_com_post_box_" + post_id + " .arm_post_total_likes").text();
                }

                ttl_likes = (parseInt(ttl_likes) > 0) ? (parseInt(ttl_likes) - 1) : 0;

                jQuery(".arm_com_post_box_" + post_id + " .arm_post_total_likes").text(ttl_likes);
            }
        }
    });
});

jQuery(document).on('click', '.arm_com_post_remove', function () {
    var $this = jQuery(this);
    var post_id = $this.attr('data-post_id');
    jQuery(".arm_com_post_box .arm_confirm_box").remove();
    $this.after( jQuery(".arm_com_post_remove_confirm_box_div").html() );
    var confirm_box = $this.next(".arm_confirm_box");
    confirm_box.slideDown("fast", function() {
        confirm_box.find(".arm_com_post_remove_confirm_box").attr("data-item_id", post_id);
    });
});

jQuery(document).on("click", ".arm_com_post_remove_confirm_box", function() {
    var post_id = jQuery(this).attr("data-item_id");
    jQuery('.arm_com_post_display_loader').show();
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: 'action=arm_com_post_remove&post_id=' + post_id,
        success: function (response)
        {
            if (response.type == 'success') {
                jQuery('.arm_com_post_box_' + post_id).remove();
                jQuery(".arm_com_post_comment_box_" + post_id).remove();

                if( jQuery(".arm_com_wall_post_display_container .arm_com_post_box_wrapper .arm_com_post_box").length <= 0 ) {
                    jQuery(".arm_com_wall_post_display_container .arm_com_no_message").css("display", "block");
                }
            }

            jQuery('.arm_com_post_display_loader').hide();
        }
    });
    return false;
});

jQuery(document).on("click", ".arm_com_post_box .arm_confirm_box .arm_confirm_box_btn.armcancel", function() {
    jQuery(".arm_com_post_box .arm_confirm_box").slideUp("fast", function() {
        jQuery(".arm_com_post_box .arm_confirm_box").remove();
    });
});

jQuery(document).on('click', '#arm_com_give_review:not(.arm_edit_user_review_button)', function () {
    jQuery('.arm_com_review_popup').bPopup({
        opacity: 0.5,
        closeClass: 'popup_close_btn',
        follow: [false, false]
    });
});

jQuery(document).on('click', '#arm_com_send_msg', function () {
    jQuery('.arm_com_msg_popup').bPopup({
        opacity: 0.5,
        closeClass: 'popup_close_btn',
        follow: [false, false]
    });
});

jQuery(document).on('click', '.arm_edit_user_review_button', function () {
    var user_from = jQuery(this).attr('data-user_from');
    var user_to = jQuery(this).attr('data-user_to');
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: 'action=arm_get_user_reviews&user_from=' + user_from + '&user_to=' + user_to,
        success: function (response)
        {
            if (response.type == 'success') {
                jQuery('.arm_edit_review_popup').html(response.content);
                jQuery('.arm_edit_com_review_popup').bPopup({
                    opacity: 0.5,
                    closeClass: 'popup_close_btn',
                    follow: [false, false]
                });
            }
        }
    });
});

jQuery(document).on('click', '.arm_com_msg_content_sender_img,.arm_com_msg_content_sender_title', function () {
    var sender_id = jQuery(this).parent('.arm_com_msg_content_sender').attr('id');
    var $this = jQuery('#' + sender_id);
    jQuery('.arm_com_msg_content_sender').removeClass('active');
    jQuery('.arm_com_message_convo_div').replaceWith('');
    jQuery('#arm_com_message_msg').val('');
    $this.addClass('active');
    var sender = $this.attr('data-sender_id');
    var sender_username = $this.attr('data-sender_username');
    jQuery('.arm_com_msg_content_sender').attr('disabled', 'disabled');
    jQuery('#arm_com_msg_display_loader_img').show();
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: 'action=arm_com_get_message_thread&sender_id=' + sender,
        success: function (response)
        {
            if (response.type == 'success') {
                jQuery('.arm_com_msg_content_right_content').html(response.content);
                jQuery('#arm_total_sender_msgs_' + sender).replaceWith('');
                jQuery('#arm_com_message_receiver').val(sender_username);
                jQuery('#arm_com_message_receiver_id').val(sender);
                if (jQuery('#arm_com_message_convo_div').length !== 0) {
                    var element = document.getElementById("arm_com_message_convo_div");
                    element.scrollTop = element.scrollHeight;
                }
            }
            else {
                jQuery('.arm_com_msg_content_right_content').html(response.content);
            }

            if( response.paging != "" ) {
                jQuery('.arm_com_msgs_paging_div').html("").html(response.paging).show();
            }
            else {
                jQuery('.arm_com_msgs_paging_div').html("").hide();
            }

            jQuery('.arm_com_msg_content_sender').removeAttr('disabled');
            jQuery('#arm_com_msg_display_loader_img').hide();
        }
    });
});

jQuery(document).on('click', '#arm_com_review_btn', function () {
    var form = jQuery(this).parents('form');
    var user_id = jQuery(this).attr('data-user_id');
    var form_data = form.serialize();
    var error_count = 0;

    if (form.find('input[name=arm_rating]').length > 0) {
        if (form.find('input[name=arm_rating]:checked').length <= 0) {
            form.find('.arm_com_review_rating_error').show();
            error_count++;
        }
        else {
            form.find('.arm_com_review_rating_error').hide();
        }
    }

    if (form.find('input[name=arm_popup_rating]').length > 0) {
        if (form.find('input[name=arm_popup_rating]:checked').length <= 0) {
            form.find('.arm_com_review_rating_error').show();
            error_count++;
        }
        else {
            form.find('.arm_com_review_rating_error').hide();
        }
    }

    if (form.find('#arm_title').val() == '') {
        form.find('.arm_com_review_title_error').show();
        error_count++;
    }
    else {
        form.find('.arm_com_review_title_error').hide();
    }

    if (form.find('#arm_description').val() == '') {
        form.find('.arm_com_review_desc_error').show();
        error_count++;
    }
    else {
        form.find('.arm_com_review_desc_error').hide();
    }

    if (error_count > 0) {
        return false;
    }
    else {
        var $this = jQuery(this);
        $this.attr('disabled', 'disabled');
        jQuery(this).addClass('active');
        jQuery('.arm_com_review_btn_spinner').css("opacity", "1");
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arm_com_review_add&' + form_data,
            success: function (response)
            {
                if (response.type == 'success') {
                    if (response.content != "") {
                        form[0].reset()
                        form.find('.arm_review_add_error').hide();
                        form.find('.arm_review_add_success').html(response.msg);
                        jQuery('.arm_no_review').hide();
                        if (jQuery('.arm_com_review_box_' + user_id).length == 0) {

                            if(jQuery('.arm_com_review_box').length == 0) {
                                jQuery('.arm_com_displ_review_container').html(response.content);
                            }
                            else {
                                jQuery('.arm_com_review_box:first').before(response.content);
                            }

                            jQuery('.arm_review_avg_rating').html(response.avg_rating);
                            jQuery('.arm_com_review_popup.popup_wrapper').remove();

                            if(response.review_editable == 1) {
                                jQuery('.arm_review_title_add_edit_link').html('<a class="arm_edit_user_review_button" id="arm_com_give_review" data-user_from = "' + response.user_from + '" data-user_to = "' + response.user_to + '">+Edit Review</a>');
                            }
                            else {
                                jQuery('.arm_review_title_add_edit_link').html('');
                            }
                        }
                        else {
                            console.log(response.content );
                            jQuery('#arm_com_review_box_' + user_id).replaceWith(response.content);
                            jQuery('.arm_review_avg_rating').html(response.avg_rating);
                        }
                    }
                    jQuery('.arm_com_review_popup').bPopup().close();
                    jQuery('.arm_edit_com_review_popup').bPopup().close();
                }
                else {
                    form.find('.arm_review_add_success').hide();
                    form.find('.arm_review_add_error').html(response.msg);
                }
                $this.removeAttr('disabled');
                $this.removeClass('active');
                jQuery('.arm_com_review_btn_spinner').css("opacity", "0");
            }
        });
    }
    return false;
});

jQuery(document).on('click', '#arm_com_follow_btn', function () {
    var btn_id = jQuery(this).attr('id');
    arm_com_follow(btn_id, 'arm_com_follow');
});

jQuery(document).on('click', '#arm_com_unfollow_btn', function () {
    jQuery(".arm_com_unfollow_button_wrapper .arm_confirm_box").slideDown("fast");
});

jQuery(document).on("click", ".arm_com_unfollow_button_wrapper .arm_confirm_box .arm_com_user_unfollow_btn", function() {
    arm_com_follow("arm_com_unfollow_btn", 'arm_com_unfollow');
});

jQuery(document).on("click", ".arm_com_unfollow_button_wrapper .arm_confirm_box .arm_confirm_box_btn.armcancel", function() {
    jQuery(".arm_com_unfollow_button_wrapper .arm_confirm_box").slideUp("fast");
    return false;
});

function arm_com_follow(btn_id, action) {
    var $this = jQuery('#' + btn_id);
    var arm_user_id = $this.attr('data-user_id');
    if (arm_user_id != '' && arm_user_id > 0)
    {
        $this.attr('disabled', 'disabled');
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=' + action + '&user_id=' + arm_user_id,
            success: function (response)
            {
                if (response.type == 'success') {
                    jQuery('#arm_follow_button').html(response.content);
                }
                else {
                    jQuery('#arm_error').html(response.msg);
                }
                $this.removeAttr('disabled');
            }
        });
    }
}

var arm_com_user_follow_flag = "";
jQuery(document).on('click', '#arm_com_display_follower', function () {
    arm_com_user_follow_flag = "follower";
    jQuery(".arm_com_follow_paging_wrapper").hide();
    jQuery(".arm_com_user_follower_paging_wrapper").show();
    jQuery('.arm_com_follower_popup').bPopup({
        opacity: 0.5,
        closeClass: 'popup_close_btn',
        follow: [false, false]
    });
});

jQuery(document).on('click', '#arm_com_display_following', function () {
    arm_com_user_follow_flag = "following";
    jQuery(".arm_com_follow_paging_wrapper").hide();
    jQuery(".arm_com_user_following_paging_wrapper").show();
    jQuery('.arm_com_following_popup').bPopup({
        opacity: 0.5,
        closeClass: 'popup_close_btn',
        follow: [false, false]
    });
});

jQuery(document).on('click', '.arm_com_frd_tab_li', function () {
    var tab_key = jQuery(this).attr('data-tab_key');
    jQuery('.arm_com_frd_tab_li').removeClass('arm_com_frd_tab_active');
    jQuery(this).addClass('arm_com_frd_tab_active');
    jQuery('.arm_com_frd_content_main_wrapper').removeClass('arm_com_frd_tab_content_active');
    jQuery('.arm_com_frd_content_' + tab_key).addClass('arm_com_frd_tab_content_active');
    if(tab_key == "friend_requests") {
        jQuery(".arm_current_friend_paging_div").hide();
    }
    else {
        jQuery(".arm_current_friend_paging_div").show();
    }
});

jQuery(document).on('click', '#arm_com_friendship_send_btn', function () {
    var arm_btn = jQuery(this);
    arm_com_friendship(arm_btn, 'arm_com_friendship_send_request');
});

var friendship_cancel_btn_ele = "";
jQuery(document).on('click', '.arm_com_friendship_unfriend_btn', function () {
    var $this = jQuery(this);
    friendship_cancel_btn_ele = $this;
    jQuery(".arm_com_user_box .arm_confirm_box").remove();
    $this.parents(".arm_com_user_box").append(jQuery(".arm_com_friend_delete_btn_div").html());
    var confirm_box = $this.parents(".arm_com_user_box").find(".arm_confirm_box");
    confirm_box.slideDown("fast", function() {
        confirm_box.find(".arm_com_friend_delete_btn").attr("data-item_id", $this.attr("data-friend_id"));
    });
});
jQuery(document).on("click", ".arm_com_friend_delete_btn", function() {
    friendship_cancel_btn_ele.parents(".arm_com_user_box_right_bottom").find(".arm_com_friendship_unfriend_button_loader").show();
    arm_com_friendship(friendship_cancel_btn_ele, 'arm_com_friendship_cancel_request');
    return false;
});

jQuery(document).on('click', '.arm_com_friendship_unfriend_nav', function () {
    var $this = jQuery(this);
    friendship_cancel_btn_ele = $this;
    jQuery(".arm_friendship_button .arm_confirm_box").slideDown("fast");
});
jQuery(document).on("click", ".arm_com_unfriend_btn", function() {
    arm_com_friendship(friendship_cancel_btn_ele, 'arm_com_friendship_cancel_request');
    return false;
});
jQuery(document).on("click", ".arm_friendship_button .arm_confirm_box .arm_confirm_box_btn.armcancel", function() {
    jQuery(".arm_friendship_button .arm_confirm_box").slideUp("fast");
    return false;
});

jQuery(document).on("click", ".arm_com_user_box .arm_confirm_box .arm_confirm_box_btn.armcancel", function() {
    jQuery(".arm_com_user_box .arm_confirm_box").slideUp("fast", function() {
        jQuery(".arm_com_user_box .arm_confirm_box").remove();
    });
    return false;
});

jQuery(document).on('click', '.arm_com_friendship_cancel_btn', function () {
    var arm_btn = jQuery(this);
    arm_com_friendship(arm_btn, 'arm_com_friendship_cancel_request');
});

jQuery(document).on('click', '#arm_com_friendship_cancel_link', function () {
    var arm_btn = jQuery(this);
    arm_com_friendship(arm_btn, 'arm_com_friendship_cancel_link_request');
});

jQuery(document).on('click', '#arm_com_friendship_approve_btn', function () {
    var arm_btn = jQuery(this);
    arm_com_friendship(arm_btn, 'arm_com_friendship_approve_request');
});

function arm_com_friendship(arm_btn, action) {
    var $this = jQuery(arm_btn);
    var arm_friend_id = $this.attr('data-friend_id');
    var arm_is_for_tab = $this.attr('data-is_for_tab');
    var user_id = jQuery('#arm_user_to').val();
    if (arm_friend_id != '' && arm_friend_id > 0)
    {
        $this.attr('disabled', 'disabled');
        jQuery('#arm_com_fs_display_loader_'+arm_friend_id).show();
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=' + action + '&friend_id=' + arm_friend_id + '&arm_is_for_tab=' + arm_is_for_tab + '&user_id=' + user_id,
            success: function(response)
            {
                if(action == "arm_com_friendship_cancel_request" && $this.hasClass("arm_com_friendship_unfriend_btn")) {
                    $this.parents(".arm_com_user_box").slideUp(function() {
                        arm_com_friendship_process(response, arm_is_for_tab, $this, arm_friend_id);
                    });
                }
                else {
                    arm_com_friendship_process(response, arm_is_for_tab, $this, arm_friend_id);
                }
            }
        });
    }
}

function arm_com_friendship_process(response, arm_is_for_tab, $this, arm_friend_id) {
    if (response.type == 'success') {
        if (arm_is_for_tab == 1) {
            jQuery('#arm_com_friend_container').html(response.content);
        }
        else {
            jQuery('#arm_friendship_button').html(response.content);
        }
    }
    else {
        jQuery('#arm_error').html(response.msg);
    }
    $this.removeAttr('disabled');
    jQuery('#arm_com_fs_display_loader_'+arm_friend_id).hide();
}

jQuery(document).on('click', '#arm_blocked_user', function () {
    var blocked_user_id = jQuery(this).attr('data-user-id');
    if (blocked_user_id > 0) {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arm_com_block_user_rmeove&user_id=' + blocked_user_id,
            success: function (response)
            {
                if (response.type == 'success') {
                    jQuery('.arm_blocked_user_' + blocked_user_id).remove();
                }
                else {
                    jQuery('.arm_com_msg_block_error').html(response.msg);
                }
            }
        });
    }
});

jQuery(document).on('click', '#arm_com_block_user', function () {
    var form_data = jQuery('#arm_com_msg_privacy_form').serialize();
    var error_count = 0;
    if (jQuery('#arm_com_block_username').val() == '') {
        jQuery('.arm_com_block_username_error').show();
        error_count++;
    }
    else {
        jQuery('.arm_com_block_username_error').hide();
    }

    if (error_count > 0) {
        return false;
    }
    else {
        var $this = jQuery(this);
        $this.attr('disabled', 'disabled');
        jQuery(this).addClass('active');

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arm_com_block_user&' + form_data,
            success: function (response)
            {
                if (response.type == 'success') {
                    jQuery('#arm_com_block_username').val('');
                    jQuery('.arm_com_msg_error_success').html(response.msg);
                    jQuery('.arm_blocked_users_container').append(response.content);
                    jQuery('.arm_com_msg_tab_active').click();
                }
                else {
                    jQuery('.arm_com_msg_block_error').html(response.msg);
                }
                $this.removeAttr('disabled');
                $this.removeClass('active');
            }
        });
    }
    return false;
});

jQuery(document).on('mouseover', '.arm_com_msg_content_sender', function () {
    var sender_id = jQuery(this).attr('data-sender_id');
    jQuery('.arm_com_msg_content_sender_icons').hide();
    jQuery('#arm_com_msg_content_sender_icons_' + sender_id).css('display', 'inline-block');

});
jQuery(document).on('mouseout', '.arm_com_msg_content_sender', function () {
    var sender_id = jQuery(this).attr('data-sender_id');
    jQuery('#arm_com_msg_content_sender_icons_' + sender_id).hide();
});

jQuery(document).on('click', '.arm_com_msg_tab_li:not(.arm_com_msg_compose,.arm_com_msg_reply)', function () {
    var tab_key = jQuery(this).attr('data-tab_key');
    jQuery('#arm_com_msg_display_loader').show();
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: 'action=arm_com_get_msg_div&tab_key=' + tab_key,
        success: function (response)
        {
            if (response.type == 'success') {
                jQuery('.arm_com_msg_content_main_wrapper').html(response.content);
                
                if (jQuery('.arm_com_msg_content_sender').length == 0) {
                    jQuery('.arm_com_msg_tab_li.arm_com_msg_reply').hide();
                }
                else {
                    jQuery('.arm_com_msg_tab_li.arm_com_msg_reply').show();
                }

                jQuery('.arm_com_msg_tab_li').removeClass('arm_com_msg_tab_active');
                jQuery('.arm_com_msg_'+tab_key).addClass('arm_com_msg_tab_active');
            }
            else {
                alert(response.content);
            }
            jQuery('#arm_com_msg_display_loader').hide();
        }
    });
});

jQuery(document).on('click', '.arm_com_msg_reply', function (event) {
    if (jQuery('#arm_com_msg_compose_form').length !== 0) {
        event.preventDefault();

        jQuery('html, body').animate({
            scrollTop: jQuery('#arm_com_msg_compose_form').last().offset().top
        }, 500);

        jQuery('#arm_com_message_msg').focus();
        var objDiv = document.getElementById("arm_com_message_convo_div");
        objDiv.scrollTop = objDiv.scrollHeight;
    }
});

function arm_com_msg_action(sender_id, action) {
    if (action == 'delete') {
        if (confirm(arm_delete_conversation_text)) {
            jQuery('#arm_com_msg_display_loader').show();
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: 'action=arm_com_message_delete&sender=' + sender_id,
                success: function (response)
                {
                    if (response.type == 'success') {
                        jQuery('#arm_com_msg_content_sender_' + sender_id).remove();
                        if (jQuery('.arm_com_msg_content_sender').length == 0) {
                            jQuery('.arm_com_msg_content_left').remove();
                            jQuery('.arm_com_msg_content_right').remove();
                            jQuery('.arm_com_msg_content_main_wrapper').append('<div class="arm_com_msg_content_wrapper"><div id="arm_com_msg_list_wrapper" class="arm_com_msg_list_wrapper"><div class="arm_com_no_message">'+arm_empty_inbox_text+'</div></div></div>');
                            jQuery('.arm_com_msg_reply').hide();
                        }
                        else {
                            jQuery('.arm_com_msg_content_sender').first().find('.arm_com_msg_content_sender_img').trigger('click');
                        }
                    }
                    else {
                        alert(response.message);
                    }

                    jQuery('#arm_com_msg_display_loader').hide();
                }
            });
        }
        else {
            return false;
        }
    }
    else if (action == 'archive') {
        if (confirm(arm_hide_conversation_text)) {
            jQuery('#arm_com_msg_display_loader').show();
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: 'action=arm_com_message_archive&sender=' + sender_id,
                success: function (response)
                {
                    if (response.type == 'success') {
                        jQuery('#arm_com_msg_content_sender_' + sender_id).remove();
                        if (jQuery('.arm_com_msg_content_sender').length == 0) {
                            jQuery('.arm_com_msg_content_left').remove();
                            jQuery('.arm_com_msg_content_right').remove();
                            jQuery('.arm_com_msg_content_main_wrapper').append('<div class="arm_com_msg_content_wrapper"><div id="arm_com_msg_list_wrapper" class="arm_com_msg_list_wrapper"><div class="arm_com_no_message">'+arm_empty_inbox_text+'</div></div></div>');
                            jQuery('.arm_com_msg_reply').hide();

                        }
                        else {
                            jQuery('.arm_com_msg_content_sender').first().find('.arm_com_msg_content_sender_img').trigger('click');
                        }
                    }
                    else {
                        alert(response.message);
                    }
                    jQuery('#arm_com_msg_display_loader').hide();
                }
            });
        }
        else {
            return false;
        }
    }
    else {
        if (confirm(arm_move_conversation_text)) {
            jQuery('#arm_com_msg_display_loader').show();
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: 'action=arm_com_message_inbox&sender=' + sender_id,
                success: function (response)
                {
                    if (response.type == 'success') {
                        jQuery('#arm_com_msg_content_sender_' + sender_id).remove();

                        if (jQuery('.arm_com_msg_content_sender').length == 0) {
                            jQuery('.arm_com_msg_content_left').remove();
                            jQuery('.arm_com_msg_content_right').remove();
                            jQuery('.arm_com_msg_content_main_wrapper').append('<div class="arm_com_msg_content_wrapper"><div id="arm_com_msg_list_wrapper" class="arm_com_msg_list_wrapper"><div class="arm_com_no_message">'+arm_empty_archive_text+'</div></div></div>');
                            jQuery('.arm_com_msg_reply').hide();

                        }
                        else {
                            jQuery('.arm_com_msg_content_sender').first().find('.arm_com_msg_content_sender_img').trigger('click');
                        }
                    }
                    else {
                        alert(response.message);
                    }
                    jQuery('#arm_com_msg_display_loader').hide();
                }
            });
        }
        else {
            return false;
        }
    }
}

if (jQuery.isFunction(jQuery().autocomplete)) {
    if(jQuery('#arm_com_message_receiver_popup').length > 0) {
        jQuery('#arm_com_message_receiver_popup').autocomplete({
            minLength: 0,
            delay: 500,
            appendTo: "#arm_com_msg_user_list_container",
            source: function (request, response) {
                jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    dataType: 'json',
                    data: "action=arm_com_get_user_list&search_key=" + request.term,
                    beforeSend: function () {},
                    success: function (res) {
                        response(res.data);
                    }
                });
            },
            focus: function () {
                return false;
            },
            select: function (event, ui) {
                var itemData = ui.item;
                jQuery("#arm_com_message_receiver_popup").val(itemData.value);
                return false;
            },
        })
        .data('uiAutocomplete')._renderItem = function (ul, item) {
            var itemClass = 'ui-menu-item';
            var itemHtml = '<li class="' + itemClass + '" data-value="' + item.value + '" data-id="' + item.id + '" ><a>' + item.label + '</a></li>';
            return jQuery(itemHtml).appendTo(ul);
        };
    }
}

jQuery(document).on('click', '#arm_com_message_send_btn', function () {
    var form_data = jQuery('#arm_com_msg_compose_form').serialize();
    var error_count = 0;
    var sender_id = jQuery('#arm_com_message_receiver_id').val();

    if (jQuery('#arm_com_message_msg').val() == '') {
        jQuery('#arm_com_message_message_error').show();
        error_count++;
    }
    else {
        jQuery('#arm_com_message_message_error').hide();
    }

    if (error_count > 0) {
        return false;
    }
    else {
        var $this = jQuery(this);
        $this.attr('disabled', 'disabled');
        var tab = $this.attr('data-tab');
        jQuery(this).addClass('active');
        jQuery(".arm_com_message_send_btn_spinner").css("opacity", "1");
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arm_com_message_compose&' + form_data+'&tab=reply',
            success: function (response)
            {
                if (response.type == 'success') {
                    jQuery('#arm_com_msg_compose_success').html(response.msg).show().delay(4000).fadeOut(500);
                    jQuery('.arm_com_msg_content_right_content').html(response.content);
                    jQuery('#arm_com_message_msg').val('');
                    jQuery('#arm_com_message_msg').focus();
                    var objDiv = document.getElementById("arm_com_message_convo_div");
                    objDiv.scrollTop = objDiv.scrollHeight;
                }
                else {
                    jQuery('#arm_com_msg_compose_error').html(response.msg);
                }
                $this.removeAttr('disabled');
                $this.removeClass('active');
                jQuery(".arm_com_message_send_btn_spinner").css("opacity", "0");
            }
        });
    }
    return false;
});


jQuery(document).on('click', '#arm_com_message_send_btn_popup', function () {
    var form = jQuery(this).parents('form');
    var form_data = jQuery('#arm_com_msg_compose_form_popup').serialize();
    var error_count = 0;
    var sender_id = jQuery('#arm_com_message_receiver_popup').val();

    if (jQuery('#arm_com_message_receiver_popup').val() == '') {
        jQuery('#arm_com_message_receiver_error_popup').show();
        error_count++;
    }
    else {
        jQuery('#arm_com_message_receiver_error_popup').hide();
    }

    if (jQuery('#arm_com_message_msg_popup').val() == '') {
        jQuery('#arm_com_message_message_error_popup').show();
        error_count++;
    }
    else {
        jQuery('#arm_com_message_message_error_popup').hide();
    }

    if (error_count > 0) {
        return false;
    }
    else {
        var $this = jQuery(this);
        $this.attr('disabled', 'disabled');
        jQuery(this).addClass('active');
        jQuery(".arm_com_message_send_btn_popup_spinner").css("opacity", "1");
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arm_com_message_compose&' + form_data,
            success: function (response)
            {
                console.log(response); 
                if (response.type == 'success') {
                    jQuery('#arm_com_msg_compose_success_popup').html(response.msg);
                    jQuery('.arm_popup_close_btn').trigger('click');
                    jQuery('#arm_com_message_receiver_popup').val('');
                    jQuery('#arm_com_message_msg_popup').val('');
                    jQuery('#arm_com_msg_compose_error_popup').text('');
                    jQuery('#arm_com_msg_compose_success_popup').text('');
                    jQuery('.arm_com_msg_content_main_wrapper').html(response.content);
                    jQuery('.arm_com_msg_tab_li').removeClass('arm_com_msg_tab_active');
                    jQuery('.arm_com_msg_inbox').addClass('arm_com_msg_tab_active');
                    jQuery('.arm_com_msg_content_sender_img[data-sender_username="' + sender_id + '"]').trigger('click');
                    if( jQuery(".arm_com_msg_reply").hasClass("arm_display_none") ) {
                        jQuery(".arm_com_msg_reply").removeClass("arm_display_none")
                    }
                }
                else {
                    jQuery('#arm_com_msg_compose_error_popup').html(response.msg);
                }
                
                form[0].reset();
                $this.removeAttr('disabled');
                $this.removeClass('active');
                jQuery(".arm_com_message_send_btn_popup_spinner").css("opacity", "0");
            }
        });
    }
    return false;
});

function arm_do_signle_action(action, message_id, message_type) {
    if (action != '' && message_id != '' && message_type != '') {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arm_com_message_single_action&arm_com_actoin=' + action + '&arm_com_message_id=' + message_id + '&arm_com_message_type=' + message_type,
            success: function (response)
            {
                if (response.type == 'success') {
                    jQuery('.arm_com_msg_content_main_wrapper').html(response.content);
                    jQuery('.arm_com_msg_tab_active').click();
                }
                else {
                    jQuery('.arm_com_msg_error').html(response.content);
                }
            }
        });
    }
}

jQuery(document).on('click', '#arm_com_msg_bult_apply', function () {
    var form = jQuery(this).parents('form');
    var action_val = form.find('#arm_com_msg_bulk').val();
    var chk_count = form.find('input[name="item-action[]"]:checked').length;
    if (chk_count > 0)
    {
        if (action_val == '') {
            form.find('.arm_com_msg_error').html(jQuery(this).attr('data_select_bulk_action'));
        }
        else {
            var str = form.serialize();
            jQuery.ajax({
                type: "POST",
                url: ajaxurl,
                data: "action=arm_com_message_bulk_action&" + str,
                dataType: 'json',
                beforeSend: function () {
                    form.find('.arm_com_msg_list_wrapper').css('opacity', '0.5');
                },
                success: function (res) {
                    if (res.type == 'success') {
                        form.find('.arm_com_msg_list_wrapper').css('opacity', '1');
                        jQuery('.arm_com_msg_content_main_wrapper').html(res.content);
                        jQuery('.arm_com_msg_tab_active').click();
                    }
                    else {
                        form.find('.arm_com_msg_list_wrapper').css('opacity', '1');
                        form.find('.arm_com_msg_error').html(jQuery(this).attr(res.content));
                    }
                }
            });
        }
    }
    else {
        form.find('.arm_com_msg_error').html(jQuery(this).attr('data_select_chk_box'));
    }
    return false;
});

jQuery(document).on('click', "#cb-select-all-1", function () {
    var form = jQuery(this).parents('form');
    form.find('input[name="item-action[]"]').attr('checked', this.checked);
});

jQuery(document).on('click', 'input[name="item-action[]"]', function () {
    var form = jQuery(this).parents('form');
    if (form.find('input[name="item-action[]"]').length == form.find('input[name="item-action[]"]:checked').length) {
        form.find("#cb-select-all-1").attr("checked", "checked");
    }
    else {
        form.find("#cb-select-all-1").removeAttr("checked");
    }
});

jQuery(document).on('click', '.arm_message_paging_container .arm_page_numbers', function () {
    var transForm = jQuery(this).parents('form');
    var pageNum = jQuery(this).attr('data-page');
    if (!jQuery(this).hasClass('current')) {
        var formData = transForm.serialize();
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: 'action=arm_com_message_paging_action&current_page=' + pageNum + '&' + formData,
            beforeSend: function () {
                transForm.find('.arm_com_msg_list_wrapper').css('opacity', '0.5');
            },
            success: function (res) {
                transForm.find('.arm_com_msg_list_wrapper').css('opacity', '1');
                jQuery('.arm_com_msg_tab_content_active').html(res);
                jQuery('.arm_com_msg_tab_active').click();
                return false;
            }
        });
    }
    return false;
});

jQuery(document).on('click', "#arm_com_msg_confirm_box", function () {
    var item_id = jQuery(this).attr('data-item_id');
    if (item_id != '') {
        var form = jQuery(this).parents('form');
        var deleteBox = form.find('#arm_confirm_box_' + item_id);
        deleteBox.addClass('armopen').toggle('slide');
    }
    return false;
});

function hideConfirmBoxCallback() {
    jQuery('.arm_confirm_box.armopen').removeClass('armopen').toggle('slide');
    return false;
}

jQuery(document).on('click', '#arm_com_message_reply_btn', function () {
    var form_data = jQuery('#arm_com_msg_reply_form').serialize();
    var error_count = 0;
    if (jQuery('#arm_com_message_msg_popup').val() == '') {
        jQuery('.arm_com_message_message_error').show();
        error_count++;
    }
    else {
        jQuery('.arm_com_message_message_error').hide();
    }

    if (error_count > 0) {
        return false;
    }
    else {
        var $this = jQuery(this);
        $this.attr('disabled', 'disabled');
        jQuery(this).addClass('active');
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arm_com_message_compose&' + form_data,
            success: function (response)
            {
                if (response.type == 'success') {
                    jQuery('.arm_com_msg_content_main_wrapper').html(response.content);
                    jQuery('.arm_com_msg_success').html(response.msg);
                    jQuery('.arm_com_msg_tab_active').click();
                }
                else {
                    jQuery('.arm_com_msg_error').html(response.msg);
                }
                $this.removeAttr('disabled');
                $this.removeClass('active');
                jQuery('.arm_popup_close_btn').click();
            }
        });
    }
    return false;
});

jQuery(document).on('click', '.arm_com_message_popup_close_btn', function () {
    jQuery('.arm_com_message_popup').bPopup().close();
});

jQuery(document).ready(function () {
    if(jQuery(".arm_template_wrapper").hasClass("arm_template_wrapper_profiletemplate3")) {
        jQuery(".arm_follow_user_box img").css("border-radius", "7px");
    }
    else {
        jQuery(".arm_follow_user_box img").css("border-radius", "50%")
    }

    active_tab = jQuery(".arm_profile_tab_menu_ul li.active a").attr("data-item_id");
    jQuery(document).on('click', '#arm_open_emoji_nav', function () {
        jQuery('#arm_emoji_wrapper').slideToggle();
        jQuery('.arm_profile_container .arm_profile_detail_wrapper').css('padding-bottom', '100px');
    });

    jQuery(document).on('click', '#arm_emoji_wrapper img', function () {
        jQuery("#arm_com_message_msg").val(jQuery(" " + "#arm_com_message_msg").val() + " " + jQuery(this).attr("data-entity") + " ").focus();
    });

    jQuery(document).on('click', '#arm_open_emoji_nav_popup', function () {
        jQuery('#arm_emoji_wrapper_popup').slideToggle();
    });

    jQuery(document).on('click', '#arm_emoji_wrapper_popup img', function () {
        jQuery("#arm_com_message_msg_popup").val(jQuery(" " + "#arm_com_message_msg_popup").val() + " " + jQuery(this).attr("data-entity"));
    });
});

function arm_com_msg_convo_delete(msg_id, sender_id, receiver_id) {
    if (confirm(arm_delete_message_text)) {
        jQuery('#arm_com_message_convo_' + msg_id+' .arm_com_message_convo_action img').css('display', 'none');
        jQuery('#arm_com_message_convo_' + msg_id).css('opacity', '0.4');
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arm_com_message_single_delete&msg_id=' + msg_id+'&sender_id='+sender_id+'&receiver_id='+receiver_id,
            success: function (response)
            {
                if (response.type == 'success') {
                    jQuery('#arm_com_message_convo_' + msg_id).html('<div class="arm_com_msg_deleted">' + response.msg + '</div>');
                    jQuery('#arm_com_message_convo_' + msg_id).delay(5000).fadeOut(400);
                }
                else {
                    alert(response.msg);
                }
                jQuery('#arm_com_message_convo_' + msg_id+' .arm_com_message_convo_action img').css('display', 'block');
            }
        });
    }
    else {
        return false;
    }
}

jQuery( document ).on( 'heartbeat-send', function ( event, data ) {
    data.arm_com_msg_count = 'arm_com_msg_count';
});

jQuery( document ).on( 'heartbeat-tick', function ( event, data ) {
    if ( ! data.message_count ) {
        return;
    }

    if(jQuery('.arm_com_msg_content_sender').length !== 0) {
        jQuery.each( data.message_count, function( index, value ) {
            if(value > 0)  {
                if(jQuery('#arm_total_sender_msgs_div_'+index).length !== 0) {
                    jQuery('#arm_total_sender_msgs_div_'+index).html('<div class="arm_total_sender_msgs" id="arm_total_sender_msgs_'+index+'">'+value+'</div>');
                }
            }
        });
    }
});

jQuery(document).on('click', '.arm_current_friend_paging_div .arm_page_numbers:not(.dots)', function () {
    if (!jQuery(this).hasClass('current')) {
        var $this = jQuery(this);
        var pageNum = $this.attr('data-page');
        if($this.hasClass("arm_com_friends_load_more_link")) {
            $this.parent(".arm_com_friends_paging_container_infinite").find("img").show();
            $this.hide();
        }
        else {
            jQuery('.arm_com_friend_container .arm_template_loading').show();
        }
        jQuery.ajax({
            type: "GET",
            url: __ARMAJAXURL,
            data: 'action=arm_community_friend_display_front&pageno=' + pageNum + '&arm_friend_tab_type=current_friends&arm_user=' + jQuery("#arm_com_current_user").val(),
            dataType: "json",
            success: function (res) {
                if($this.hasClass("arm_com_friends_load_more_link")) {
                    $this.show();
                    $this.parent(".arm_com_friends_paging_container_infinite").find("img").hide();
                    var rec_page = parseInt($this.attr("data-page")) + 1;
                    jQuery(".arm_section_friendship_section .arm_com_user_box_div_wrapper").append(res.content);
                    $this.attr("data-page", rec_page);
                    if(rec_page > $this.attr("data-arm_ttl_page")) {
                        $this.remove();
                    }
                }
                else {
                    jQuery(".arm_section_friendship_section .arm_com_user_box_div_wrapper").html("").html(res.content);

                    if( res.paging != "" ) {
                        jQuery(".arm_current_friend_paging_div").html("").html(res.paging);
                    }

                    jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: jQuery('.arm_section_friendship_section .arm_com_user_box_div_wrapper').offset().top - 30}, 1000);
                    jQuery('.arm_com_friend_container .arm_template_loading').hide();
                }
            }
        });
    }
    return false;
});

jQuery(document).on('click', '.arm_friend_req_list_paging_div .arm_page_numbers:not(.dots)', function () {
    if (!jQuery(this).hasClass('current')) {
        var $this = jQuery(this);
        var pageNum = $this.attr('data-page');
        if($this.hasClass("arm_com_friend_req_load_more_link")) {
            $this.parent(".arm_com_friend_req_paging_container_infinite").find("img").show();
            $this.hide();
        }
        else {
            jQuery('.arm_com_friend_container .arm_template_loading').show();
        }
        jQuery.ajax({
            type: "GET",
            url: __ARMAJAXURL,
            data: 'action=arm_community_friend_display_front&pageno=' + pageNum + '&arm_friend_tab_type=friend_requests&arm_user=' + jQuery("#arm_com_current_user").val(),
            dataType: "json",
            success: function (res) {
                if($this.hasClass("arm_com_friend_req_load_more_link")) {
                    $this.show();
                    $this.parent(".arm_com_friend_req_paging_container_infinite").find("img").hide();
                    var rec_page = parseInt($this.attr("data-page")) + 1;
                    jQuery(".arm_section_friendship_section .arm_com_user_box_div_req_wrapper").append(res.content);
                    $this.attr("data-page", rec_page);
                    if(rec_page > $this.attr("data-arm_ttl_page")) {
                        $this.remove();
                    }
                }
                else {
                    jQuery(".arm_section_friendship_section .arm_com_user_box_div_req_wrapper").html("").html(res.content);

                    if( res.paging != "" ) {
                        jQuery(".arm_friend_req_list_paging_div").html("").html(res.paging);
                    }

                    jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: jQuery('.arm_section_friendship_section .arm_com_user_box_div_req_wrapper').offset().top - 30}, 1000);
                    jQuery('.arm_com_friend_container .arm_template_loading').hide();
                }
            }
        });
    }
    return false;
});

jQuery(document).on('click', '.arm_review_paging_div .arm_page_numbers:not(.dots)', function () {
    if (!jQuery(this).hasClass('current')) {
        var $this = jQuery(this);
        var pageNum = $this.attr('data-page');
        if($this.hasClass("arm_com_review_load_more_link")) {
            $this.parent(".arm_com_review_paging_container_infinite").find("img").show();
            $this.hide();
        }
        else {
            jQuery('.arm_com_disp_review_container .arm_template_loading').show();
        }
        jQuery.ajax({
            type: "GET",
            url: __ARMAJAXURL,
            data: 'action=arm_community_review_display_front&pageno=' + pageNum + '&arm_user=' + jQuery("#arm_com_current_user").val(),
            dataType: "json",
            success: function (res) {
                if($this.hasClass("arm_com_review_load_more_link")) {
                    $this.show();
                    $this.parent(".arm_com_review_paging_container_infinite").find("img").hide();
                    var rec_page = parseInt($this.attr("data-page")) + 1;
                    jQuery(".arm_section_review .arm_com_displ_review_container").append(res.content);
                    $this.attr("data-page", rec_page);
                    if(rec_page > $this.attr("data-arm_ttl_page")) {
                        $this.remove();
                    }
                }
                else {
                    jQuery(".arm_section_review .arm_com_displ_review_container .arm_com_review_box_container").remove();
                    jQuery(".arm_section_review .arm_com_displ_review_container").prepend(res.content);

                    if(res.paging != "") {
                        jQuery(".arm_review_paging_div").html("").html(res.paging);
                    }
                    
                    jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: jQuery('.arm_com_displ_review_container').offset().top - 30}, 1000);
                    jQuery('.arm_com_disp_review_container .arm_template_loading').hide();
                }
            }
        });
    }
    return false;
});

jQuery(document).on('click', '.arm_posts_paging_div .arm_page_numbers:not(.dots)', function () {
    if (!jQuery(this).hasClass('current')) {
        var $this = jQuery(this);
        var pageNum = $this.attr('data-page');

        if($this.hasClass("arm_com_posts_load_more_link")) {
            $this.parent(".arm_com_posts_paging_container_infinite").find("img").show();
            $this.hide();
        }
        else {
            jQuery('.arm_com_post_wrapper .arm_template_loading').show();
        }
        jQuery.ajax({
            type: "GET",
            url: __ARMAJAXURL,
            data: 'action=arm_community_posts_display_front&pageno=' + pageNum + '&arm_user=' + jQuery("#arm_com_current_user").val(),
            dataType: "json",
            success: function (res) {
                if($this.hasClass("arm_com_posts_load_more_link")) {
                    $this.show();
                    $this.parent(".arm_com_posts_paging_container_infinite").find("img").hide();
                    var rec_page = parseInt($this.attr("data-page")) + 1;
                    jQuery(".arm_com_post_display_container .arm_com_post_box_wrapper").append(res.content);
                    $this.attr("data-page", rec_page);
                    if(rec_page > $this.attr("data-arm_ttl_page")) {
                        $this.remove();
                    }
                }
                else {
                    jQuery(".arm_com_post_display_container .arm_com_post_box_wrapper").html("").html(res.content);
                    
                    if(res.paging != "") {
                        jQuery(".arm_posts_paging_div").html("").html(res.paging);
                    }
                    
                    jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: jQuery('.arm_com_post_display_container .arm_com_post_box_wrapper').offset().top - 30}, 1000);
                    jQuery('.arm_com_post_wrapper .arm_template_loading').hide();
                }
            }
        });
    }
    return false;
});

jQuery(document).on('click', '.arm_activity_paging_div .arm_page_numbers', function () {
    if (!jQuery(this).hasClass('current')) {
        var $this = jQuery(this);
        var pageNum = $this.attr('data-page');
        var current_user  = jQuery("#arm_com_current_user").val();

        if($this.hasClass("arm_com_activity_load_more_link")) {
            $this.parent(".arm_com_activity_paging_container_infinite").find("img").show();
            $this.hide();
        }
        else {
            jQuery('.arm_com_activity_box_wrapper .arm_template_loading').show();
        }
        jQuery.ajax({
            type: "GET",
            url: __ARMAJAXURL,
            data: 'action=arm_community_activity_display_front&pageno=' + pageNum + '&arm_user=' + current_user,
            dataType: "json",
            success: function (res) {
                if($this.hasClass("arm_com_activity_load_more_link")) {
                    $this.show();
                    $this.parent(".arm_com_activity_paging_container_infinite").find("img").hide();
                    var rec_page = parseInt($this.attr("data-page")) + 1;
                    jQuery(".arm_com_activity_box_wrapper").append(res.content);
                    $this.attr("data-page", rec_page);
                    if(rec_page > $this.attr("data-arm_ttl_page")) {
                        $this.remove();
                    }
                }
                else {
                    jQuery(".arm_com_activity_box_wrapper").html("").html(res.content);
                    
                    if( res.paging != "" ) {
                        jQuery(".arm_activity_paging_div").html("").html(res.paging);
                    }
                    
                    jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: jQuery('.arm_com_activity_box_wrapper').offset().top - 30}, 1000);
                    jQuery('.arm_com_activity_box_wrapper .arm_template_loading').hide();
                }
            }
        });
    }
    return false;
});

jQuery(document).on('click', '.arm_com_msgs_paging_div .arm_page_numbers', function () {
    jQuery('#arm_com_msg_display_loader_img').show();
    var $this = jQuery(this);
    var pageNum = $this.attr('data-page');
    var sender = $this.attr("data-arm_sener_id");
    jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        data: 'action=arm_community_message_fetch_front&pageno=' + pageNum + '&sender_id=' + sender,
        dataType: "json",
        success: function (res) {
            var rec_page = parseInt($this.attr("data-page")) + 1;
            jQuery(".arm_com_message_convo_div_wrapper").prepend(res.content);
            $this.attr("data-page", rec_page);
            if(rec_page > $this.attr("data-arm_ttl_page")) {
                $this.remove();
            }
            jQuery('#arm_com_msg_display_loader_img').hide();
        }
    });
    return false;
});

jQuery(document).on("click", "#arm_com_post_fimage_btn", function() {
    jQuery("#arm_com_post_featur_image").trigger("click");
    return false;
});

jQuery(document).on("change", "#arm_com_post_featur_image", function(input) {
    arm_read_post_thumb_url(this);
});

function arm_read_post_thumb_url(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      jQuery('.arm_post_attachment_thumb').attr('src', e.target.result).show();
    }
    reader.readAsDataURL(input.files[0]);
    jQuery(".arm_com_post_fimage_btn").hide();
    jQuery(".arm_com_post_fimage_remove_btn").show();
  }
}

jQuery(document).on("click", ".arm_com_post_added_type_btn", function() {
    var arm_post_type = jQuery(this).attr("data-arm_post_type");
    jQuery(".arm_com_post_added_type_btn").removeClass("arm_post_type_btn_active");
    jQuery(this).addClass("arm_post_type_btn_active");
    jQuery("#arm_com_post_added_type").val(arm_post_type);

    if( arm_post_type == "status" ) {
        arm_com_post_attachment_remove = 1;
        jQuery(".arm_post_attachment_thumb, .arm_com_post_fimage_btn, .arm_com_post_fimage_remove_btn").slideUp();
    }
    else {
        if(jQuery(".arm_post_attachment_thumb").attr("src").trim() == "") {
            jQuery("#arm_com_post_featur_image").val("");
            jQuery(".arm_com_post_fimage_remove_btn").hide();
            jQuery(".arm_com_post_fimage_btn").slideDown();
        }
        else {
            jQuery(".arm_post_attachment_thumb, .arm_com_post_fimage_remove_btn").slideDown();
        }
    }
});

var arm_com_post_attachment_remove = 0;
jQuery(document).on("click", ".arm_com_post_fimage_remove_btn", function() {
    jQuery('.arm_post_attachment_thumb').attr('src', '').hide();
    jQuery("#arm_com_post_featur_image").val("");
    jQuery(".arm_com_post_fimage_remove_btn").hide();
    jQuery(".arm_com_post_fimage_btn").show();
    arm_com_post_attachment_remove = 1;
});

var arm_comment_box = "";
jQuery(document).on("click", ".arm_com_post_comment_nav", function() {
    if(arm_comment_box == "") {
        arm_comment_box = document.getElementById("arm_com_post_comment_box_wrapper").outerHTML;
    }
    var $this = jQuery(this);
    var post_id = $this.attr("data-post_id");
    jQuery(".arm_com_post_comment_box").slideUp();
    if( $this.hasClass("arm_comment_active") ) {
        $this.removeClass("arm_comment_active")
        return false;
    }

    jQuery(".arm_com_post_comment_nav").removeClass("arm_comment_active")
    $this.addClass("arm_comment_active");

    var comment_box = jQuery(".arm_com_post_comment_box_" + post_id);
    var commnet_list = document.getElementById("arm_com_post_comment_list_div_" + post_id).outerHTML;
    comment_box.html("").append(arm_comment_box);
    comment_box.find(".arm_com_post_comment_box_wrapper").prepend(commnet_list);
    comment_box.find(".arm_com_post_comment_btn").attr("data-arm_post_id", post_id);
    comment_box.find(".arm_com_post_comment_box_wrapper, .arm_com_post_comment_box_wrapper .arm_com_post_comment_list_div").show();
    comment_box.slideDown();
});

jQuery(document).on("click", ".arm_com_post_comment_btn", function() {
    jQuery(".arm_com_post_comment_loader").show();
    jQuery(".arm_com_post_comment_msg").hide();
    var $this = jQuery(this);
    var parent = $this.parents(".arm_com_post_comment_section");
    var post_id = $this.attr("data-arm_post_id");
    $this.prop("disabled", true);
    if( parent.find(".arm_com_post_comment").val().trim() == "" ) {
        parent.find(".arm_com_post_comment_error").show();
        parent.find(".arm_com_post_comment").focus();
        jQuery(".arm_com_post_comment_loader").hide();
        $this.prop("disabled", false);
        return false;
    }
    jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        data: 'action=arm_community_comment_add&post_id=' + post_id + '&comment=' + parent.find(".arm_com_post_comment").val(),
        dataType: "json",
        success: function (res) {
            if(res.type == "success") {
                jQuery(".arm_com_post_comment_msg").show().delay(5000).fadeOut(300);
                parent.find(".arm_com_post_comment").val("").focus();
                jQuery(".arm_com_post_comment_box_" + post_id + " .arm_com_post_comment_list_div").remove();
                jQuery(".arm_com_post_comment_box_" + post_id + " .arm_com_post_comment_box_wrapper").prepend(res.comments);
                jQuery(".arm_com_post_comment_box_" + post_id).find(".arm_com_post_comment_list_div").show();
                if(active_tab == "wall_post") {
                    var ttl_cmnt_ele = jQuery(".arm_com_wall_post_content_container .arm_com_post_box_" + post_id + " .arm_post_total_comments");
                }
                else {
                    var ttl_cmnt_ele = jQuery(".arm_com_post_display_container .arm_com_post_box_" + post_id + " .arm_post_total_comments");
                }
                
                var ttl_cmnt = parseInt(ttl_cmnt_ele.text().trim()) + 1;
                jQuery(".arm_com_post_box_" + post_id + " .arm_post_total_comments").text(ttl_cmnt);
                $this.prop("disabled", false);
            }
            else {
                alert(res.msg);
            }
            jQuery(".arm_com_post_comment_loader, .arm_com_post_comment_error").hide();
        }
    });

    return false;
});

jQuery(document).on("click", ".arm_post_comment_load_more_nav span", function() {
    var $this = jQuery(this);
    var post_id = $this.attr("data-post_id");
    var pageno = parseInt($this.attr("data-pageno"));
    jQuery.ajax({
        type: "POST",
        url: __ARMAJAXURL,
        data: 'action=arm_get_post_comments_front&post_id=' + post_id + '&pageno=' + pageno,
        dataType: "json",
        success: function (res) {
            pageno += 1;
            $this.parents(".arm_com_post_comment_list_div").find(".arm_com_post_comment_list_ul").prepend(res.comments);
            $this.parents(".arm_com_post_comment_list_div").find(".arm_com_post_comment_list_ul .arm_com_comment_new_loaded").fadeIn(1000);

            if(pageno > $this.attr("data-ttl_page")) {
                $this.parent(".arm_post_comment_load_more_nav").hide();
            }
            else {
                $this.attr("data-pageno", pageno);
            }
        }
    });
});

jQuery(document).on('click', '.arm_com_post_comment_remove', function () {
    var $this = jQuery(this);
    var comment_id = $this.attr('data-comment_id');
    var parent = $this.parents(".arm_com_post_commnet_single");
    jQuery(".arm_com_post_commnet_single .arm_confirm_box").remove();
    parent.append( jQuery(".arm_com_post_comment_remove_confirm_box_div").html() );
    var confirm_box = $this.next(".arm_confirm_box");
    confirm_box.slideDown("fast", function() {
        confirm_box.find(".arm_com_post_comment_remove_confirm_box").attr("data-item_id", comment_id);
    });
    return false;
});

jQuery(document).on("click", ".arm_com_post_comment_remove_confirm_box", function() {
    var $this = jQuery(this);
    var comment_id = $this.attr('data-item_id');
    var parent = $this.parents(".arm_com_post_commnet_single");
    var post_id = parent.attr("data-post_id");
    jQuery('.arm_com_post_display_loader').show();
    $this.remove();
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: 'action=arm_com_post_comment_remove&comment_id=' + comment_id,
        success: function (response) {
            var ttl_cmnt_ele = jQuery(".arm_com_post_display_container .arm_com_post_box_" + post_id).find(".arm_post_total_comments");
            var ttl_cmnt = parseInt(ttl_cmnt_ele.text().trim()) - 1;
            jQuery(".arm_com_post_box_" + post_id).find(".arm_post_total_comments").text(ttl_cmnt);
            jQuery(".arm_com_post_commnet_single[data-comment_id='"+comment_id+"']").remove();
            jQuery('.arm_com_post_display_loader').hide();
        }
    });
});

jQuery(document).on("click", ".arm_com_post_commnet_single .arm_confirm_box .arm_confirm_box_btn.armcancel", function() {
    jQuery(".arm_com_post_commnet_single .arm_confirm_box").slideUp("fast", function() {
        jQuery(".arm_com_post_commnet_single .arm_confirm_box").remove();
    });
});

jQuery(document).on('click', '.arm_wall_posts_paging_div .arm_page_numbers', function () {
    var $this = jQuery(this);
    var pageNum = $this.attr('data-page');
    $this.parent(".arm_com_posts_paging_container_infinite").find("img").show();
    $this.hide();
    jQuery.ajax({
        type: "GET",
        url: __ARMAJAXURL,
        data: 'action=arm_community_display_wall_post_front&pageno=' + pageNum + '&arm_user=' + jQuery("#arm_com_current_user").val(),
        dataType: "json",
        success: function (res) {
            $this.show();
            $this.parent(".arm_com_posts_paging_container_infinite").find("img").hide();
            var rec_page = parseInt($this.attr("data-page")) + 1;
            jQuery(".arm_com_wall_post_display_container .arm_com_post_box_wrapper").append(res.content);
            $this.attr("data-page", rec_page);
            if(rec_page > $this.attr("data-arm_ttl_page")) {
                $this.hide();
            }
        }
    });
    
    return false;
});

jQuery(document).on("click", ".arm_com_post_edit", function() {
    var post_id = jQuery(this).attr("data-post_id");
    jQuery('.arm_com_post_display_loader').show();
    jQuery.ajax({
        type: "GET",
        url: __ARMAJAXURL,
        data: 'action=arm_community_get_single_post&post_id=' + post_id,
        dataType: "json",
        success: function (res) {
            if(res.type == "success") {
                arm_reset_post_form(res.added_type, res.title, res.content, res.fimg, 'edit');
                jQuery(".arm_com_post_added_type input#arm_com_post_id").val(post_id);
                arm_com_post_attachment_remove = 0;
            }
            jQuery(window.opera ? 'html' : 'html, body').animate({scrollTop: jQuery('.arm_section_post.arm_profile_li_container').offset().top - 50}, 300);
            jQuery('.arm_com_post_display_loader').hide();
        }
    });

    return false;
});

jQuery(document).on("click", ".arm_com_post_edit_cancel_nav", function() {
    arm_reset_post_form('photo', '', '', '', 'add');
});

function arm_reset_post_form(type, title, content, fimg, mode) {
    jQuery(".arm_com_post_added_type_btn").removeClass("arm_post_type_btn_active");
    jQuery(".arm_post_attachment_thumb, .arm_com_post_fimage_btn, .arm_com_post_fimage_remove_btn, .arm_com_post_btn, .arm_com_post_edit_cancel_nav, .arm_com_post_edit_btn").hide();
    jQuery("#arm_com_post_featur_image, .arm_com_post_added_type input#arm_com_post_id").val("");
    jQuery("input#arm_com_post_added_type").val(type);
    jQuery(".arm_com_post_added_type_btn[data-arm_post_type='"+type+"']").addClass("arm_post_type_btn_active");
    jQuery(".arm_com_post_user_title_box input#arm_title").val(title).focus();
    jQuery(".arm_com_post_description textarea#arm_description").val(content);
    jQuery("input#arm_com_post_mode").val(mode);
    arm_com_post_attachment_remove = 0;
    jQuery("#arm_com_post_btn, #arm_com_post_edit_btn").removeAttr('disabled').removeClass('active');
    if( type == "photo" ) {
        if( fimg != "" ) {
            jQuery(".arm_post_attachment_thumb").attr("src", fimg);
            jQuery(".arm_post_attachment_thumb, .arm_com_post_fimage_remove_btn").show();
        }
        else {
            jQuery(".arm_com_post_fimage_btn").show();
        }
        jQuery(".arm_com_post_added_type_btn[data-arm_post_type='photo']").addClass("arm_post_type_btn_active");
    }

    if( mode == "edit" ) {
        jQuery(".arm_com_post_edit_cancel_nav, .arm_com_post_edit_btn").show();
    }
    else {
        jQuery(".arm_com_post_btn").show();
    }
}

jQuery(document).on('click', '.arm_com_follow_paging_wrapper .arm_page_numbers:not(.dots)', function () {
    if (!jQuery(this).hasClass('current')) {
        jQuery(".arm_com_follow_popup_text .arm_template_loading").css("display","block");
        var $this = jQuery(this);
        var pageNum = $this.attr('data-page');
        jQuery.ajax({
            type: "GET",
            url: __ARMAJAXURL,
            data: 'action=arm_community_user_following_front&pageno=' + pageNum + '&arm_user=' + jQuery("#arm_com_current_user").val() + '&action_type=' + arm_com_user_follow_flag,
            dataType: "json",
            success: function (res) {
                jQuery(".arm_follow_"+arm_com_user_follow_flag+"_box_wrapper").html("").html(res.content);
                if(res.paging != "") {
                    jQuery(".arm_com_user_"+arm_com_user_follow_flag+"_paging_wrapper").html("").html(res.paging);
                }
                if(jQuery(".arm_template_wrapper").hasClass("arm_template_wrapper_profiletemplate3")) {
                    jQuery(".arm_follow_user_box img").css("border-radius", "7px");
                }
                else {
                    jQuery(".arm_follow_user_box img").css("border-radius", "50%")
                }
                jQuery(".arm_com_follow_popup_text .arm_template_loading").css("display","none");
            }
        });
    }
    return false;
});

jQuery(".arm_com_main_tab_btn").click(function() {
    if( jQuery(".arm_com_main_tab_btn_arrow_down").hasClass("arm_com_main_tab_btn_arrow_current") ) {
        jQuery(".arm_com_main_tab_btn_arrow_down").removeClass("arm_com_main_tab_btn_arrow_current");
        jQuery(".arm_com_main_tab_btn_arrow_up").addClass("arm_com_main_tab_btn_arrow_current");
    }
    else {
        jQuery(".arm_com_main_tab_btn_arrow_up").removeClass("arm_com_main_tab_btn_arrow_current");
        jQuery(".arm_com_main_tab_btn_arrow_down").addClass("arm_com_main_tab_btn_arrow_current");
    }
    jQuery(".arm_profile_tab_menu_ul").toggle("fast");
});

jQuery(".arm_com_msg_button").click(function() {
    if( jQuery(".arm_com_msg_arrow_down").hasClass("arm_com_msg_arrow_current") ) {
        jQuery(".arm_com_msg_arrow_down").removeClass("arm_com_msg_arrow_current");
        jQuery(".arm_com_msg_arrow_up").addClass("arm_com_msg_arrow_current");
    }
    else {
        jQuery(".arm_com_msg_arrow_up").removeClass("arm_com_msg_arrow_current");
        jQuery(".arm_com_msg_arrow_down").addClass("arm_com_msg_arrow_current");
    }
    jQuery(".arm_com_msg_content_left").slideToggle();
});

jQuery(".arm_com_msg_content_sender").click(function() {
    var $this = jQuery(this);
    var user_avatar = $this.find(".arm_com_msg_content_sender_img img").attr("src");
    var user_name = $this.find(".arm_com_msg_content_sender_title .arm_com_msg_content_sender_name .arm_sender_name").text().trim();
    jQuery(".arm_com_msg_div .arm_com_msg_button img:eq(0)").attr("src", user_avatar);
    jQuery(".arm_com_msg_div .arm_com_msg_button span").text(user_name);
    if(jQuery(window).width() <= 480) {
        jQuery(".arm_com_msg_arrow_up").removeClass("arm_com_msg_arrow_current");
        jQuery(".arm_com_msg_arrow_down").addClass("arm_com_msg_arrow_current");
        jQuery(".arm_com_msg_content_left").slideUp("");
    }
});