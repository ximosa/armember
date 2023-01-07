jQuery(document).on('click', '.arm_profile_tab_menu_container ul li a.arm_profile_tab', function () {
    arm_profile_hide_show_section(jQuery(this).attr('data-item_id'));
});
function arm_profile_hide_show_section(section_name) {
    jQuery('.arm_profile_tab_contant_container ul li.arm_profile_li_container').addClass('arm_hide');
    jQuery('.arm_profile_tab_contant_container ul li.arm_section_' + section_name).removeClass('arm_hide');
}
jQuery(document).on("change", ".arm_com_switch", function () {
    if (jQuery("#arm_add_profile_temp_form").length > 0) {
        var data_type = jQuery('.arm_profile_belt_icon.selected').attr('data-type');
        var data = jQuery("#arm_add_profile_temp_form").serialize();
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            beforeSend: function () {
                jQuery(".arm_loading").show();
            },
            data: 'action=arm_change_profile_template&' + data + '&data_type=' + data_type,
            success: function (response) {
                if (typeof old_template != 'undefined' && jQuery('#arm_template_style_' + old_template + '-css').length > 0) {
                    jQuery('#arm_template_style_' + old_template + '-css').remove();
                }
                jQuery('#arm_template_container_wrapper').html(response.template);
                jQuery(".arm_loading").hide();
            }
        });
    }
});
jQuery(document).on('change', '.arm_profile_temp_color_scheme_block .arm_temp_color_radio', function (e) {
    var $this = jQuery(this);
    var color = $this.val();
    var ClrSchms = armTempColorSchemes();
    var clr_opts = ClrSchms[color];

    jQuery('#arm_profile_tab_background_color').val(clr_opts.tab_bg_color);
    jQuery('#arm_profile_tab_background_color').colpickSetColor(clr_opts.tab_bg_color);
    jQuery('#arm_profile_tab_background_color').parent('.arm_colorpicker_label').css('background-color', clr_opts.tab_bg_color);

    jQuery('#arm_profile_tab_title_color').val(clr_opts.tab_link_color);
    jQuery('#arm_profile_tab_title_color').colpickSetColor(clr_opts.tab_link_color);
    jQuery('#arm_profile_tab_title_color').parent('.arm_colorpicker_label').css('background-color', clr_opts.tab_link_color);

    jQuery('#arm_profile_tab_title_hover_color').val(clr_opts.tab_link_hover_color);
    jQuery('#arm_profile_tab_title_hover_color').colpickSetColor(clr_opts.tab_link_hover_color);
    jQuery('#arm_profile_tab_title_hover_color').parent('.arm_colorpicker_label').css('background-color', clr_opts.tab_link_hover_color);

    jQuery('#arm_profile_button_font_color').val(clr_opts.button_font_color);
    jQuery('#arm_profile_button_font_color').colpickSetColor(clr_opts.button_font_color);
    jQuery('#arm_profile_button_font_color').parent('.arm_colorpicker_label').css('background-color', clr_opts.button_font_color);

    jQuery('#arm_profile_button_background_color').val(clr_opts.button_color);
    jQuery('#arm_profile_button_background_color').colpickSetColor(clr_opts.button_color);
    jQuery('#arm_profile_button_background_color').parent('.arm_colorpicker_label').css('background-color', clr_opts.button_color);
    
    e.stopPropagation();
    return false;
});
jQuery(document).on('click', '.arm_profile_tab_menu_container ul li a.arm_profile_tab', function () {
    arm_profile_hide_show_section(jQuery(this).attr('data-item_id'));
});
function arm_profile_hide_show_section(section_name) {
    jQuery('.arm_profile_tab_contant_container ul li.arm_profile_li_container').addClass('arm_hide');
    jQuery('.arm_profile_tab_contant_container ul li.arm_section_' + section_name).removeClass('arm_hide');

    jQuery('.arm_profile_tab_menu_container .arm_profile_tab[data-item_id="' + section_name + '"]').addClass('active');
    jQuery('.arm_profile_tab_menu_container ul li').removeClass('active');
    
    jQuery('.arm_profile_tab_menu_container .arm_profile_tab[data-item_id="' + section_name + '"].active').parent('li').addClass('active');
}
function arm_com_msg_action(id, action){
    return false;
}