function arm_show_hide_css_textarea(e){"undefined"!=typeof e&&""!=e?jQuery("#arm_close_acc_css").show():jQuery("#arm_close_acc_css").hide()}function arm_show_hide_logged_in_message(e){var r=jQuery('ul.arm_form_select li[data-value="'+e+'"]'),_=r.attr("data-form-type");"undefined"!=typeof _&&"change_password"!=_?jQuery("#arm_member_form_logged_in_message").show():jQuery("#arm_member_form_logged_in_message").hide(),"registration"==_?jQuery("#arm_member_form_default_free_plan").show():jQuery("#arm_member_form_default_free_plan").hide()}function arm_show_hide_title(){var e=jQuery('input[name="arm_hide_title"]:checked').val();jQuery("input#arm_show_hide_title_hidden").val(e)}function arm_show_hide_popup(){var e=jQuery('input[name="arm_popup"]:checked').val();return jQuery("input#arm_popup_hidden").val(e),"true"==e&&(jQuery("div.form_popup_options").show(),jQuery("#arm_form_position_wrapper").hide()),"false"==e&&(jQuery("div.form_popup_options").hide(),jQuery("#arm_form_position_wrapper").show()),!1}function arm_position_input(){var e=jQuery('input[name="arm_form_position"]:checked').val();jQuery("input#arm_position_hidden").val(e)}function arm_edit_form_position_input(){var e=jQuery('input[name="arm_edit_profile_position"]:checked').val();jQuery("input#arm_edit_profile_position").val(e)}function arm_user_info_action(){var e=jQuery('input[name="arm_user_info"]:checked').val();jQuery("input#arm_user_info_hidden").val(e)}function arm_subscription_show_hide_title(){var e=jQuery('input[name="arm_subscription_hide_title"]:checked').val();jQuery("input#arm_subscription_show_hide_title_hidden").val(e)}function arm_subscription_setup_display_type(){var e=jQuery('input[name="arm_subscription_display_type"]:checked').val();return jQuery("input#arm_subscription_display_form_type_hidden").val(e),"true"==e?jQuery("div.form_popup_options").show():jQuery("div.form_popup_options").hide(),!1}function arm_activities_paging_type_check(){var e=jQuery("input[name='arm_activitie_paging_type']:checked").val();return jQuery("input#arm_activitie_paging_type_hidden").val(e),"numeric"==e&&jQuery(".form_popup_options").hide(),"infinite"==e&&jQuery(".form_popup_options").show(),!1}function arm_activitie_show_hide_paging(){var e=jQuery('input[name="arm_activitie_paging"]:checked').val();jQuery("input#arm_activitie_show_hide_paging_hidden").val(e),"true"==e?jQuery("#arm_paging_type_wrapper").show():jQuery("#arm_paging_type_wrapper").hide()}function arm_account_detail_tab_func(){var e="",r="";jQuery(".arm_account_chk_fields").each(function(){if(jQuery(this).is(":checked")){e+=jQuery(this).val()+",";var _=jQuery(this);r+=_.parents(".arm_acount_field_details_option").find('input[type="text"]').val()+","}}),jQuery("input#arm_profile_label_hidden").val(e),jQuery("input#arm_profile_value_hidden").val(r)}function arm_social_networks_icon_list(e){jQuery(".arm_social_network_icons").removeClass("selected"),jQuery("#social_network_"+e+"_icon").addClass("selected");var r=jQuery('li.arm_social_login_network[data-value="'+e+'"]').attr("data-icon");jQuery(".arm_social_network_icons").prop("checked",!1),jQuery('.arm_social_network_icons[data-key="'+r+'"]').prop("checked",!0)}function arm_set_social_network_icon(){var e=jQuery("input[name='arm_social_icon']:checked").val();jQuery("input#arm_social_network_icon_hidden").val(e)}function arm_view_profile_checked(){jQuery("#arm_view_profile_checkbox").is(":checked")?jQuery("#arm_view_profile_hidden").val("true"):jQuery("#arm_view_profile_hidden").val("false")}function arm_get_social_fields(e,r,_){var i=jQuery("#ajax_url_hidden").val();"undefined"==typeof r&&(r=!1),"undefined"==typeof _&&(_=""),""!==e&&jQuery.ajax({url:i,method:"POST",dataType:"json",data:"action=arm_get_spf_in_tinymce&form_name="+e+"&is_vc="+!0,success:function(e){0==e.error&&(jQuery("#arm_social_fields_wrapper").html(e.content),1==r&&""!==_&&(jQuery("#social_fields_hidden").val(_),_=_.split(","),jQuery("#social_fields_hidden").parent().find(".arm_spf_active_checkbox").each(function(){var e=jQuery(this).val();_.indexOf(e)>-1?jQuery(this).prop("checked",!0):jQuery(this).prop("checked",!1)})))}})}function arm_select_profile_social_fields(){var e="";jQuery(".arm_spf_profile_fields").each(function(){jQuery(this).is(":checked")&&(e+=jQuery(this).val()+",")}),jQuery("#profile_social_fields_hidden").val(e)}function arm_select_social_fields(){var e="";jQuery(".arm_spf_active_checkbox_input").each(function(){jQuery(this).is(":checked")&&(e+=jQuery(this).val()+",")}),jQuery("#social_fields_hidden").val(e)}function arm_select_transaction_fields(){var e="",r="";jQuery(".arm_member_transaction_field_input").each(function(){if(jQuery(this).is(":checked")){e+=jQuery(this).val()+",";var _=jQuery(this);r+=_.parents(".arm_member_transaction_field_list").find('input[type="text"]').val()+","}}),jQuery("#arm_transaction_label_hidden").val(e),jQuery("#arm_transaction_value_hidden").val(r)}function arm_select_membership_fields(){var e="";e=jQuery("#current_membership_label").val()+","+jQuery("#current_membership_started").val()+","+jQuery("#membership_expired_on").val()+","+jQuery("#membership_recurring_profile").val()+","+jQuery("#membership_remaining_occurence").val()+","+jQuery("#membership_next_billing_date").val()+","+jQuery("#membership_trial_period").val(),jQuery("#arm_current_membership_fields_value").val(e)}function arm_select_login_history_fields(){var e="",r="";jQuery(".arm_member_login_history_field_input").each(function(){if(jQuery(this).is(":checked")){e+=jQuery(this).val()+",";var _=jQuery(this);r+=_.parents(".arm_member_login_history_field_list").find('input[type="text"]').val()+","}}),jQuery("#arm_login_history_label_hidden").val(e),jQuery("#arm_login_history_value_hidden").val(r)}function arm_show_change_subscription(){var e=jQuery('input[name="arm_show_change_subscription_input"]:checked').val();return jQuery("input#arm_show_change_subscription_hidden").val(e),"true"==e&&jQuery("tr.form_popup_options").show(),"false"==e&&jQuery("tr.form_popup_options").hide(),!1}function arm_show_renew_subscription(){var e=jQuery('input[name="arm_show_renew_subscription_input"]:checked').val();return jQuery("input#arm_show_renew_subscription_hidden").val(e),"true"==e&&jQuery("tr.form_popup_options#show_renew_subscription_section").show(),"false"==e&&jQuery("tr.form_popup_options#show_renew_subscription_section").hide(),!1}function arm_display_invoice(){var e=jQuery('input[name="display_invoice_button_radio"]:checked').val();jQuery("input#display_invoice_button").val(e)}function arm_show_cancel_subscription(){var e=jQuery('input[name="arm_show_cancel_subscription_input"]:checked').val();return jQuery("input#arm_show_cancel_subscription_hidden").val(e),"true"==e&&jQuery("tr.form_popup_options#show_cancel_subscription_section").show(),"false"==e&&jQuery("tr.form_popup_options#show_cancel_subscription_section").hide(),!1}function arm_select_current_membership_fields(){var e="",r="";jQuery(".arm_current_membership_field_input").each(function(){if(jQuery(this).is(":checked")){e+=jQuery(this).val()+",";var _=jQuery(this);r+=_.parents(".arm_member_current_membership_field_list").find('input[type="text"]').val()+","}}),jQuery("#arm_current_membership_fields_label").val(e),jQuery("#arm_current_membership_fields_value").val(r)}function arm_change_hide_plan_settigs(){jQuery(".hide_plans_checkbox").is(":checked")?jQuery(".hide_plans").val("1"):jQuery(".hide_plans").val("0")}function arm_show_update_card_subscription(){var e=jQuery('input[name="arm_show_update_card_subscription_input"]:checked').val();return jQuery("input#arm_show_update_card_subscription_hidden").val(e),"true"==e&&jQuery("tr.form_popup_options#show_update_card_subscription_section").show(),"false"==e&&jQuery("tr.form_popup_options#show_update_card_subscription_section").hide(),!1}jQuery(document).ready(function(){jQuery(".ARM_arm_form_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");if("id"==r&&(jQuery("#arm_form_select").val(e),arm_show_hide_logged_in_message(e)),"logged_in_message"==r&&jQuery("input#logged_in_message").val(e),"assign_default_plan"==r){jQuery("#assign_default_plan").val(e);var _=jQuery("#assign_default_plan_dd dd").find('.arm_shortcode_form_id_li[data-value="'+e+'"]').html();jQuery("#assign_default_plan_dd dt").find("span").html(_)}"form_position"==r&&(jQuery("input#arm_position_hidden").val(e),"left"==e&&jQuery("input#arm_position_left").prop("checked",!0),"center"==e&&jQuery("input#arm_position_center").prop("checked",!0),"right"==e&&jQuery("input#arm_position_right").prop("checked",!0)),"popup"==r&&(jQuery("input#arm_popup_hidden").val(e),"true"==e&&(jQuery("input#arm_popup_true").prop("checked",!0),jQuery("div.form_popup_options").show(),jQuery("#arm_form_position_wrapper").hide()),"false"==e&&(jQuery("input#arm_popup_false").prop("checked",!0),jQuery("div.form_popup_options").hide(),jQuery("#arm_form_position_wrapper").show())),"link_type"==r&&(jQuery("#arm_shortcode_form_link_type").val(e),"link"==e?(jQuery(".arm_shortcode_form_link_opts").removeClass("arm_hidden"),jQuery(".arm_shortcode_form_button_opts").addClass("arm_hidden")):(jQuery(".arm_shortcode_form_link_opts").addClass("arm_hidden"),jQuery(".arm_shortcode_form_button_opts").removeClass("arm_hidden"))),"link_title"==r&&jQuery("input#arm_link_title").val(e),"overlay"==r&&jQuery('select#arm_overlay_select option[value="'+e+'"]').prop("selected",!0),"modal_bgcolor"==r&&(jQuery(".arm_colorpicker_label").css("background",e),jQuery("input#arm_vc_form_modal_bgcolor").val(e)),"popup_height"==r&&jQuery("input#arm_popup_height").val(e),"popup_width"==r&&jQuery("input#arm_popup_width").val(e),"link_css"==r&&jQuery("textarea#arm_link_css").val(e),"link_hover_css"==r&&jQuery("textarea#arm_link_hover_css").val(e)}),jQuery(".ARM_arm_edit_profile_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");if("title"==r&&jQuery("input#arm_title").val(e),"message"==r&&jQuery("input#arm_message").val(e),"form_position"==r&&(jQuery("input#arm_edit_profile_position").val(e),"left"==e&&jQuery("#arm_edit_profile_form_left").prop("checked",!0),"center"==e&&jQuery("#arm_edit_profile_form_center").prop("checked",!0),"right"==e&&jQuery("#arm_edit_profile_form_right").prop("checked",!0)),"view_profile_link"==r&&jQuery("input#view_profile_link_label").val(e),"view_profile"==r&&("true"==e?jQuery("input#arm_view_profile_checkbox").prop("checked",!0):jQuery("input#arm_view_profile_checkbox").prop("checked",!1),jQuery("input#arm_view_profile_hidden").val(e)),"form_id"==r&&jQuery("#arm_edit_profile_form").val(e),"social_fields"==r){var _=jQuery("#arm_edit_profile_form").val();arm_get_social_fields(_,!0,e)}}),jQuery(".ARM_arm_logout_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");"label"==r&&jQuery("input#arm_logout_label").val(e),"type"==r&&(jQuery("#arm_shortcode_logout_link_type").val(e),"link"==e?(jQuery(".arm_shortcode_logout_link_opts").removeClass("arm_hidden"),jQuery(".arm_shortcode_logout_button_opts").addClass("arm_hidden")):(jQuery(".arm_shortcode_logout_link_opts").addClass("arm_hidden"),jQuery(".arm_shortcode_logout_button_opts").removeClass("arm_hidden"))),"user_info"==r&&(jQuery("input#arm_user_info_hidden").val(e),"true"==e&&jQuery("input#arm_user_info_true").prop("checked",!0),"false"==e&&jQuery("input#arm_user_info_false").prop("checked",!0)),"redirect_to"==r&&jQuery("input#arm_redirect_to").val(e),"link_css"==r&&jQuery("#arm_logout_link_css").text(e),"link_hover_css"==r&&jQuery("#arm_logout_link_hover_css").text(e)}),jQuery(".ARM_arm_setup_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");"id"==r&&jQuery("#arm_subscription_id_select").val(e),"subscription_plan"==r&&jQuery("#subscription_plan_input").val(e),"popup"==r&&(jQuery("input#arm_subscription_display_form_type_hidden").val(e),"false"==e&&jQuery("input#arm_subscription_display_type_internal").prop("checked",!0),"true"==e&&jQuery("input#arm_subscription_display_type_external").prop("checked",!0),arm_subscription_setup_display_type()),"hide_title"==r&&(jQuery("input#arm_subscription_show_hide_title_hidden").val(e),"true"==e&&jQuery("input#arm_subscription_hide_title_true").prop("checked",!0),"false"==e&&jQuery("input#arm_subscription_hide_title_false").prop("checked",!0)),"hide_plans"==r&&(1==e?jQuery(".hide_plans_checkbox").prop("checked",!0):jQuery(".hide_plans_checkbox").prop("checked",!1)),"link_type"==r&&jQuery("input#arm_subscription_link_type").val(e),"link_title"==r&&jQuery("input#arm_setup_link_text_id").val(e),"modal_bgcolor"==r&&(jQuery(".arm_colorpicker_label").css("background",e),jQuery("input#arm_vc_setup_modal_bgcolor").val(e)),"popup_height"==r&&jQuery("input#arm_setup_popup_height").val(e),"popup_width"==r&&jQuery("input#arm_setup_popup_width").val(e),"link_css"==r&&jQuery("textarea#arm_link_css").val(e),"link_hover_css"==r&&jQuery("textarea#arm_link_hover_css").val(e)}),jQuery(".ARM_arm_restrict_content_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");if("type"==r&&jQuery("#arm_restrict_content_type_select").val(e),"plan"==r){var _=e.split(",");jQuery.each(_,function(e,r){jQuery('select#arm_restrict_content_plan_select option[value="'+r+'"]').prop("selected",!0)})}"armshortcodecontent"==r&&jQuery("textarea#armshortcodecontent").val(e),"armelse_message"==r&&jQuery("textarea#armelse_message").val(e)}),jQuery(".ARM_arm_member_transaction_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");"display_invoice_button"==r&&(jQuery("#display_invoice_button.wpb_vc_param_value").val(e),"true"==e&&(jQuery("input#display_invoice_button_radio_true").prop("checked",!0),jQuery(".view_invoice_btn_options").show()),"false"==e&&(jQuery("input#display_invoice_button_radio_false").prop("checked",!0),jQuery(".view_invoice_btn_options").hide())),"view_invoice_text"==r&&jQuery("#view_invoice_text_input").val(e),"view_invoice_css"==r&&jQuery("#view_invoice_css_input").val(e),"view_invoice_hover_css"==r&&jQuery("#view_invoice_hover_css_input").val(e),"title"==r&&jQuery("input#arm_transaction_title").val(e),"per_page"==r&&jQuery("input#arm_transaction_per_page_record").val(e),"message_no_record"==r&&jQuery("input#arm_transaction_message_no_record").val(e),"label"==r&&(jQuery("#arm_transaction_label_hidden").val(e),last_char=e[e.length-1],","==last_char&&(e=e.substr(0,e.length-1)),field_value=e.split(","),jQuery(".arm_member_transaction_field_input").each(function(){if(jQuery(this).is(":checked")){var e=jQuery(this).val();jQuery.inArray(e,field_value)>-1?jQuery(this).prop("checked",!0):jQuery(this).prop("checked",!1)}}),__FIELD_VALUE=field_value),"value"==r&&(jQuery("#arm_transaction_value_hidden").val(e),""!=e&&(last_char=e[e.length-1],","==last_char&&(e=e.substr(0,e.length-1)),field_value=e.split(","),"undefined"!=typeof __FIELD_VALUE&&""!==__FIELD_VALUE&&jQuery(".arm_member_transaction_field_input").each(function(){if(jQuery(this).is(":checked")){var e=jQuery(this).val(),r=jQuery.inArray(e,__FIELD_VALUE),_=field_value[r];jQuery(this).parents(".arm_member_transaction_fields").find('input[type="text"].arm_member_transaction_fields').eq(r).val(_)}})))}),jQuery(".ARM_arm_account_detail_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");"label"==r&&(jQuery("#arm_profile_label_hidden").val(e),last_char=e[e.length-1],","==last_char&&(e=e.substr(0,e.length-1)),field_value=e.split(","),jQuery(".arm_account_chk_fields").each(function(){var e=jQuery(this).val();jQuery.inArray(e,field_value)>-1?jQuery(this).prop("checked",!0):jQuery(this).prop("checked",!1)}),__ACC_FIELD_VALUE=field_value),"value"==r&&(jQuery("#arm_profile_value_hidden").val(e),""!=e&&(last_char=e[e.length-1],","==last_char&&(e=e.substr(0,e.length-1)),field_value=e.split(","),"undefined"!=typeof __ACC_FIELD_VALUE&&""!==__ACC_FIELD_VALUE&&jQuery(".arm_account_chk_fields").each(function(){jQuery(this).parent();if(jQuery(this).is(":checked")){var e=jQuery(this).val(),r=jQuery.inArray(e,__ACC_FIELD_VALUE),_=field_value[r];jQuery(this).parents(".arm_acount_field_details_option").find(".arm_account_detail_input").val(_)}}))),"social_fields"==r&&(jQuery("#profile_social_fields_hidden").val(e),last_char=e[e.length-1],","==last_char&&(e=e.substr(0,e.length-1)),field_value=e.split(","),jQuery(".arm_spf_profile_fields").each(function(){var e=jQuery(this).val();jQuery.inArray(e,field_value)>-1?jQuery(this).prop("checked",!0):jQuery(this).prop("checked",!1)}))}),jQuery(".ARM_arm_close_account_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");if("set_id"==r){jQuery("#arm_set_id").val(e);var _=jQuery(".arm_set_id_dd dd").find("li[data-value='"+e+"']").html();jQuery(".arm_set_id_dd dt").find("span").html(_),""!=e||0!=e?jQuery("#arm_close_acc_css").show():jQuery("#arm_close_acc_css").hide()}"css"==r&&jQuery("#arm_cancel_link_css").val(e)}),jQuery(".ARM_arm_cancel_membership_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");"label"==r&&jQuery("input#arm_cancel_label").val(e),"type"==r&&(jQuery("#arm_shortcode_cancel_membership_link_type").val(e),"link"==e?(jQuery(".arm_shortcode_cancel_membership_link_opts").removeClass("arm_hidden"),jQuery(".arm_shortcode_cancel_membership_button_opts").addClass("arm_hidden")):(jQuery(".arm_shortcode_cancel_membership_link_opts").addClass("arm_hidden"),jQuery(".arm_shortcode_cancel_membership_button_opts").removeClass("arm_hidden"))),"link_css"==r&&jQuery("#arm_cancel_link_css").text(e),"link_hover_css"==r&&jQuery("#arm_cancel_link_hover_css").text(e)}),jQuery(".ARM_arm_social_login_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");if("network"==r&&jQuery("#arm_shortcode_social_networks").val(e),"icon"==r){var _=jQuery("#arm_shortcode_social_networks").val();jQuery(".arm_social_network_icons").removeClass("selected"),jQuery("#social_network_"+_+"_icon").addClass("selected"),jQuery("input.arm_social_network_icons").prop("checked",!1),jQuery("#arm_social_network_icon_hidden").val(e),jQuery("input.arm_social_network_icons[value='"+e+"']").prop("checked",!0)}}),jQuery(".ARM_arm_membership_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");if("show_change_subscription"==r&&(jQuery("#arm_show_change_subscription_hidden").val(e),"true"==e?(jQuery("#arm_show_change_subscription_true").prop("checked",!0),jQuery("tr.form_popup_options").show()):(jQuery("#arm_show_change_subscription_false").prop("checked",!0),jQuery("tr.form_popup_options").hide())),"change_subscription_url"==r&&jQuery("#arm_change_subscription_url").val(e),"title"==r&&jQuery("#current_membership_label").val(e),"setup_id"==r){jQuery("#arm_form_select").val(e);var _=jQuery("#arm_form_select_dropdown dd").find(".arm_shortcode_form_id_li[data-value='"+e+"']").html();jQuery("#arm_form_select_dropdown dt").find("span").html(_)}"membership_label"==r&&(jQuery("#arm_current_membership_fields_label").val(e),""!=e&&(last_char=e[e.length-1],","==last_char&&(e=e.substr(0,e.length-1)),field_value=e.split(","),jQuery(".arm_current_membership_field_input").each(function(){if(jQuery(this).is(":checked")){var e=jQuery(this).val();jQuery.inArray(e,field_value)>-1?jQuery(this).prop("checked",!0):jQuery(this).prop("checked",!1)}}),__FIELD_VALUE=field_value)),"membership_value"==r&&(jQuery("#arm_current_membership_fields_value").val(e),""!=e&&(last_char=e[e.length-1],","==last_char&&(e=e.substr(0,e.length-1)),field_value=e.split(","),"undefined"!=typeof __FIELD_VALUE&&""!==__FIELD_VALUE&&jQuery(".arm_current_membership_field_input").each(function(){if(jQuery(this).is(":checked")){var e=jQuery(this).val(),r=jQuery.inArray(e,__FIELD_VALUE),_=field_value[r];jQuery(this).parents(".arm_member_current_membership_field_list").find(".arm_member_current_membership_fields.arm_text_input").val(_)}}))),"display_renew_button"==r&&(jQuery("#arm_show_renew_subscription_hidden").val(e),"true"==e?(jQuery("#arm_show_renew_subscription_false").prop("checked",!1),jQuery("#arm_show_renew_subscription_true").prop("checked",!0),jQuery("tr.form_popup_options#show_renew_subscription_section").show()):(jQuery("#arm_show_renew_subscription_true").prop("checked",!1),jQuery("#arm_show_renew_subscription_false").prop("checked",!0),jQuery("tr.form_popup_options#show_renew_subscription_section").hide())),"renew_text"==r&&jQuery("#arm_renew_membership_text").val(e),"make_payment_text"==r&&jQuery("#arm_make_payment_membership_text").val(e),"renew_css"==r&&jQuery("#arm_button_css").val(e),"renew_hover_css"==r&&jQuery("#arm_button_hover_css").val(e),"display_cancel_button"==r&&(jQuery("#arm_show_cancel_subscription_hidden").val(e),"true"==e?(jQuery("#arm_show_cancel_subscription_hidden_false").prop("checked",!1),jQuery("#arm_show_cancel_subscription_true").prop("checked",!0),jQuery("tr.form_popup_options#show_cancel_subscription_section").show()):(jQuery("#arm_show_cancel_subscription_true").prop("checked",!1),jQuery("#arm_show_cancel_subscription_hidden_false").prop("checked",!0),jQuery("tr.form_popup_options#show_cancel_subscription_section").hide())),"cancel_text"==r&&jQuery("#arm_cancel_membership_text").val(e),"cancel_css"==r&&jQuery("#arm_cancel_button_css").val(e),"cancel_hover_css"==r&&jQuery("#arm_cancel_button_hover_css").val(e),"cancel_message"==r&&jQuery("#arm_cancel_message").val(e),"display_update_card_button"==r&&(jQuery("#arm_show_update_card_subscription_hidden").val(e),"true"==e?(jQuery("#arm_show_update_card_subscription_hidden_false").prop("checked",!1),jQuery("#arm_show_update_card_subscription_true").prop("checked",!0),jQuery("tr.form_popup_options#show_update_card_subscription_section").show()):(jQuery("#arm_show_update_card_subscription_true").prop("checked",!1),jQuery("#arm_show_update_card_subscription_hidden_false").prop("checked",!0),jQuery("tr.form_popup_options#show_update_card_subscription_section").hide())),"update_card_text"==r&&jQuery("#arm_update_card_membership_text").val(e),"update_card_css"==r&&jQuery("#arm_update_card_button_css").val(e),"update_card_hover_css"==r&&jQuery("#arm_update_card_button_hover_css").val(e),"trial_active"==r&&jQuery("#arm_trial_active").val(e),"message_no_record"==r&&jQuery("#arm_message_no_record").val(e)}),jQuery(".ARM_arm_conditional_redirection_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");"condition"==r&&jQuery("#arm_conditional_redirection_condition").val(e),"plans"==r&&jQuery("#arm_conditional_redirection_plans").val(e),"redirect_to"==r&&jQuery("#arm_conditional_redirection_url").val(e)}),jQuery(".ARM_arm_conditional_redirection_role_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");"condition"==r&&jQuery("#arm_conditional_redirection_condition_role").val(e),"roles"==r&&jQuery("#arm_conditional_redirection_roles").val(e),"redirect_to"==r&&jQuery("#arm_conditional_redirection_url").val(e)}),jQuery(".ARM_arm_usermeta_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");"meta"==r&&jQuery("#arm_user_custom_meta").val(e)}),jQuery(".ARM_arm_user_badge_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");"user_id"==r&&jQuery("#arm_user_id").val(e)}),jQuery(".ARM_arm_user_planinfo_shortcode_armfield").each(function(){var e=jQuery(this).val(),r=jQuery(this).attr("id");"plan_id"==r&&jQuery("#arm_plan_id").val(e),"plan_info"==r&&jQuery("#plan_info").val(e)}),jQuery.isFunction(jQuery().chosen)&&jQuery(".arm_chosen_selectbox").chosen({no_results_text:"Oops, nothing found."}),jQuery.isFunction(jQuery().colpick)&&jQuery(".arm_colorpicker").each(function(){var e=jQuery(this),r=e.val();""==r&&(r="#000000"),e.wrap('<label class="arm_colorpicker_label" style="background-color:'+r+'"></label>'),e.colpick({layout:"hex",submit:0,colorScheme:"dark",color:r,onChange:function(e,r,_,i,a){jQuery(i).parent(".arm_colorpicker_label").css("background-color","#"+r),a||jQuery(i).val("#"+r)}})}),arm_selectbox_init()}),jQuery(document).on("change","#arm_subscription_link_type",function(){var e=jQuery(this).val();"link"==e?(jQuery(".arm_shortcode_setup_link_opts").removeClass("arm_hidden"),jQuery(".arm_shortcode_setup_button_opts").addClass("arm_hidden")):(jQuery(".arm_shortcode_setup_link_opts").addClass("arm_hidden"),jQuery(".arm_shortcode_setup_button_opts").removeClass("arm_hidden"))});