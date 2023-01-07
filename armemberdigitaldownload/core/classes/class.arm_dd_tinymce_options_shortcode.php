<?php
if(!class_exists('arm_dd_tinymce_options')){
    
    class arm_dd_tinymce_options{
        
        function __construct(){
            
            add_action( 'arm_shortcode_add_tab', array( $this, 'arm_shortcode_add_tab' ), 10 );
            
            add_action( 'arm_shortcode_add_tab_content', array( $this, 'arm_shortcode_add_tab_content' ), 10 );
            
            add_action( 'arm_shortcode_add_tab_buttons', array( $this, 'arm_shortcode_add_tab_buttons' ), 10 );
            
        }

        function arm_shortcode_add_tab() {
            ?>
            <li class="arm_tabgroup_link">
                    <a href="#arm-digitaldownload" data-id="arm-digitaldownload"><?php _e('Digital Download', 'ARM_DD');?></a>
            </li>
            <?php
        }

        function arm_shortcode_add_tab_content() {

            global $wpdb, $arm_dd_items; 
            $arm_dd_item_data = $arm_dd_items->arm_dd_item_all_data();
            ?>
            <div id="arm-digitaldownload" class="arm_tabgroup_content">
                <form class="arm_digital_download_form" onsubmit="return false;">
                    <div class="arm_group_body">
                            <table class="arm_shortcode_option_table">
                                    <tr>
                                        <th><?php _e('Select Download', 'ARM_DD');?></th>
                                        <td>
                                            <input type="hidden" name="item_id" id="arm_dd_item_id" value="" />
                                            <dl class="arm_selectbox column_level_dd">
                                                <dt><span></span><input type="text" style="display:none;" value="0" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                    <ul class="arm_dd_item_wrapper" data-id="arm_dd_item_id">
                                                        <li data-label="<?php _e('Select Download','ARM_DD');?>" data-value=""><?php _e('Select Download', 'ARM_DD');?></li>
                                                        <?php if(!empty($arm_dd_item_data)): ?>
                                                            <?php foreach($arm_dd_item_data as $arm_dd_item_data): ?>
                                                                <li class="arm_dd_item_li <?php echo stripslashes($arm_dd_item_data['arm_item_id']);?>" data-label="<?php echo stripslashes($arm_dd_item_data['arm_item_name']);?>" data-value="<?php echo $arm_dd_item_data['arm_item_id'];?>"><?php echo stripslashes($arm_dd_item_data['arm_item_name']);?></li>
                                                            <?php endforeach;?>
                                                        <?php endif;?>
                                                    </ul>
                                                </dd>
                                            </dl>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Select Type', 'ARM_DD');?></th>
                                        <td>
                                                <input type="hidden" id="arm_dd_link_type"  name="link_type" value="" />
                                                <dl class="arm_selectbox column_level_dd">
                                                        <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                        <dd>
                                                                <ul data-id="arm_dd_link_type">
                                                                        <li data-label="<?php _e('Select Type','ARM_DD');?>" data-value=""><?php _e('Select Type', 'ARM_DD');?></li>

                                                                        <li data-label="<?php _e('Link','ARM_DD');?>" data-value="link" ><?php _e('Link', 'ARM_DD');?></li>

                                                                        <li data-label="<?php _e('Button','ARM_DD');?>" data-value="button"><?php _e('Button', 'ARM_DD');?></li>
                                                                </ul>
                                                        </dd>
                                                </dl>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Display Description','ARM_DD'); ?></th>
                                        <td>
                                            <div class="arm_form_fields_wrapper">
                                                <div class="armclear"></div>
                                                <div class="armswitch arm_dd_switch" style="vertical-align: middle;">
                                                    <input type="checkbox" id="arm_dd_display_desc_input" value="" class="armswitch_input" name="show_description"/>
                                                    <label for="arm_dd_display_desc_input" class="armswitch_label"></label>
                                                </div>
                                            </div>

                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <label><?php _e('Display File Size', 'ARM_DD');?></label>
                                        </th>
                                        <td>
                                            <div class="arm_form_fields_wrapper">
                                                <div class="armclear"></div>
                                                <div class="armswitch arm_dd_switch" style="vertical-align: middle;">
                                                    <input type="checkbox" id="arm_dd_display_file_size_input" value="" class="armswitch_input" name="show_size"/>
                                                    <label for="arm_dd_display_file_size_input" class="armswitch_label"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <label><?php _e('Display Download Count', 'ARM_DD');?></label>
                                        </th>
                                        <td>
                                            <div class="arm_form_fields_wrapper">
                                                <div class="armclear"></div>
                                                <div class="armswitch arm_dd_switch" style="vertical-align: middle;">
                                                    <input type="checkbox" id="arm_dd_display_download_count_input" value="" class="armswitch_input" name="show_download_count"/>
                                                    <label for="arm_dd_display_download_count_input" class="armswitch_label"></label>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Custom Css', 'ARM_DD');?></th>
                                        <td>
                                            <textarea class="arm_popup_textarea" name="css" rows="3"></textarea>
                                            
                                        </td>
                                    </tr>
                            </table>
                    </div>
                </form>
            </div>
        <?php 
        }

        function arm_shortcode_add_tab_buttons() {
            ?>
            <div id="arm-digitaldownload_buttons" class="arm_tabgroup_content_buttons">
                    <div class="arm_group_footer">
                        <div class="popup_content_btn_wrapper">
                            <button type="button" class="arm_dd_shortcode_insert_btn arm_insrt_btn"  disabled="disabled" data-code="arm_download"><?php _e('Add Shortcode', 'ARM_DD');?></button>
                            <a class="arm_cancel_btn popup_close_btn" href="javascript:void(0)"><?php _e('Cancel', 'ARM_DD') ?></a>
                        </div>
                    </div>                         
            </div>
            <?php
        }
        
    }
}

global $arm_dd_tinymce_options;
$arm_dd_tinymce_options = new arm_dd_tinymce_options();

?>