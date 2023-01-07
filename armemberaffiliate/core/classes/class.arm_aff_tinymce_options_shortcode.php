<?php
if(!class_exists('arm_aff_tinymce_options')){
    
    class arm_aff_tinymce_options{
        
        function __construct(){
            
            add_action( 'arm_shortcode_add_tab', array( $this, 'arm_shortcode_add_tab' ), 10 );
            
            add_action( 'arm_shortcode_add_tab_content', array( $this, 'arm_shortcode_add_tab_content' ), 10 );
            
            add_action( 'arm_shortcode_add_tab_buttons', array( $this, 'arm_shortcode_add_tab_buttons' ), 10 );
            
        }

        function arm_shortcode_add_tab() {
            ?>
            <li class="arm_tabgroup_link">
                    <a href="#arm-affiliate" data-id="arm-affiliate"><?php _e('Affiliate', 'ARM_AFFILIATE');?></a>
            </li>
            <?php
        }
        
        function arm_shortcode_add_tab_content() {
            global $wpdb, $arm_affiliate; 
            $tmp_query = "SELECT * FROM `{$arm_affiliate->tbl_arm_aff_banner}` ";
            $banner_result = $wpdb->get_results($tmp_query);
            ?>
            <div id="arm-affiliate" class="arm_tabgroup_content">
                <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                                <tr>
                                        <th><?php _e('Select Option', 'ARM_AFFILIATE');?></th>
                                        <td>
                                                <input type="hidden" id="arm_shortcode_other_type" value="" />
                                                <dl class="arm_selectbox column_level_dd">
                                                        <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                        <dd>
                                                                <ul data-id="arm_shortcode_other_type">
                                                                        <li data-label="<?php _e('Select Option','ARM_AFFILIATE');?>" data-value=""><?php _e('Select Option', 'ARM_AFFILIATE');?></li>
                                                                        <li data-label="<?php _e('Refer/Invite Friend','ARM_AFFILIATE');?>" data-value="affilate_referrals_friend" data-code="arm_referrals_friend_invite"><?php _e('Reffer Friend', 'ARM_AFFILIATE');?></li>
                                                                        <li data-label="<?php _e('Affiliate Information','ARM_AFFILIATE');?>" data-value="affiliate_info" data-code="arm_affiliate"><?php _e('Affiliate Information', 'ARM_AFFILIATE');?></li>
                                                                        <li data-label="<?php _e('Affiliate Visits','ARM_AFFILIATE');?>" data-value="arm_aff_visits" data-code="arm_aff_visits"><?php _e('Affiliate Visits', 'ARM_AFFILIATE');?></li>
                                                                        <li data-label="<?php _e('Affiliate User Referral','ARM_AFFILIATE');?>" data-value="affiliate_user_referral" data-code="arm_user_referral"><?php _e('Affiliate User Referral', 'ARM_AFFILIATE');?></li>    
                                                                        <li data-label="<?php _e('Affiliate User Payment','ARM_AFFILIATE');?>" data-value="affiliate_user_payout_transaction" data-code="arm_user_payout_transaction"><?php _e('Affiliate User Payment', 'ARM_AFFILIATE');?></li>    
                                                                        <li data-label="<?php _e('Affiliate Banner','ARM_AFFILIATE');?>" data-value="affiliate_aff_banner" data-code="arm_aff_banner"><?php _e('Affiliate Banner', 'ARM_AFFILIATE');?></li>
                                                                        <li data-label="<?php _e('Affiliate Register','ARM_AFFILIATE');?>" data-value="arm_affiliate_register" data-code="arm_affiliate_register"><?php _e('Affiliate Register', 'ARM_AFFILIATE');?></li>
                                                                        <li data-label="<?php _e('Conditional Content','ARM_AFFILIATE');?>" data-value="armaff_conditional_content" data-code="armaff_conditional_content"><?php _e('Conditional Content', 'ARM_AFFILIATE');?></li>
                                                                        <li data-label="<?php _e('Affiliate Statistics','ARM_AFFILIATE');?>" data-value="arm_aff_statistics" data-code="arm_aff_statistics"><?php _e('Affiliate Statistics', 'ARM_AFFILIATE');?></li>
                                                                        <li data-label="<?php _e('Affiliate Earning','ARM_AFFILIATE');?>" data-value="arm_aff_earning" data-code="arm_aff_earning"><?php _e('Affiliate Earning', 'ARM_AFFILIATE');?></li>
                                                                        <li data-label="<?php _e('Affiliate Payment Paid','ARM_AFFILIATE');?>" data-value="arm_aff_payment_paid" data-code="arm_aff_payment_paid"><?php _e('Affiliate Payment Paid', 'ARM_AFFILIATE');?></li>
                                                                        <li data-label="<?php _e('Affiliate Payment Unpaid','ARM_AFFILIATE');?>" data-value="arm_aff_payment_unpaid" data-code="arm_aff_payment_unpaid"><?php _e('Affiliate Payment Unpaid', 'ARM_AFFILIATE');?></li>
                                                                        <li data-label="<?php _e('Affiliate Referral','ARM_AFFILIATE');?>" data-value="arm_aff_referral" data-code="arm_aff_referral"><?php _e('Affiliate Referral', 'ARM_AFFILIATE');?></li>

                                                                </ul>
                                                        </dd>
                                                </dl>
                                        </td>
                                </tr>
                        </table>
                </div>

                <form class="arm_shortcode_other_opts arm_shortcode_other_opts_affilate_referrals_friend arm_hidden" onsubmit="return false;">
                    <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                            <tr>
                                <?php
                                global $arm_member_forms;
                                $arm_forms = $arm_member_forms->arm_get_all_member_forms('arm_form_id, arm_form_label, arm_form_type'); ?>
                                <th><?php _e('Select Form', 'ARM_AFFILIATE'); ?></th>
                                <td>
                                    <div>
                                        <input type="hidden" id="arm_edit_profile_form" name="form_id" value="" class="wpb_vc_param_value">
                                        <dl class="arm_selectbox column_level_dd">
                                            <dt>
                                                <span><?php _e('Select Form', 'ARM_AFFILIATE'); ?></span>
                                                <input type="text" style="display:none;" value="<?php _e('Select Form', 'ARM_AFFILIATE'); ?>" class="arm_autocomplete"/>
                                                <i class="armfa armfa-caret-down armfa-lg"></i>
                                            </dt>
                                            <dd>
                                                <ul data-id="arm_edit_profile_form">
                                                    <li data-label="<?php _e('Select Form', 'ARM_AFFILIATE'); ?>" data-value=""><?php _e('Select Form', 'ARM_AFFILIATE'); ?></li>
                                                    <?php if (!empty($arm_forms)) {
                                                        foreach ($arm_forms as $_form) {
                                                            if ($_form['arm_form_type'] == 'registration') {
                                                                $formTitle = strip_tags(stripslashes($_form['arm_form_label'])) . ' &nbsp;(ID: ' . $_form['arm_form_id'] . ')'; ?>
                                                                <li class="arm_shortcode_form_id_li_edit_profile <?php echo $_form['arm_form_type']; ?>" data-label="<?php echo $formTitle; ?>" data-value="<?php echo $_form['arm_form_id']; ?>"><?php echo $formTitle; ?></li>
                                                                    <?php
                                                            }
                                                        }
                                                    } ?>
                                                </ul>
                                            </dd>
                                        </dl>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Title','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="title" value="<?php _e('Refer a Friend', 'ARM_AFFILIATE') ?>" id="arm_reffer_title_input"><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Email Label','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="email_label" value="<?php _e('Email Address', 'ARM_AFFILIATE') ?>" id="arm_reffer_email_label_input"><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Button Text','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="submit_button_text" value="<?php _e('Send Email', 'ARM_AFFILIATE') ?>" id="arm_reffer_button_text_input"><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Mail Sent Message','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="success_msg" value="<?php _e('Mail sent successfully', 'ARM_AFFILIATE') ?>" id="arm_reffer_mail_sent_message_input"><br/>
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>

                <form class="arm_shortcode_other_opts arm_shortcode_other_opts_affiliate_info arm_hidden" onsubmit="return false;">
                    <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                            <tr>
                                <th><?php _e('Affiliate Text','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <textarea class="arm_popup_textarea" name="affiliate_text" rows="3"></textarea>
                                    <br>
                                    <em><b>{<?php _e('URL','ARM_AFFILIATE'); ?>}</b> <?php _e(": It will be replaced with user's affiliate URL.",'ARM_AFFILIATE'); ?> </em>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Select Social Network(s) to Share Affiliate Link','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <div class='arm_social_field_popup_wrapper'>
                                        <div class='arm_aff_share_field_item' style="float: left; width: 30%;">
                                            <input type='checkbox' class='arm_icheckbox arm_spf_active_checkbox arm_shortcode_form_popup_opt' value='facebook' name='arm_affiliate_share_fields[]' id='arm_aff_facebook_status'  />
                                            <label for='arm_aff_facebook_status'><?php _e('Facebook','ARM_AFFILIATE'); ?></label>
                                        </div>
                                        <div class='arm_aff_share_field_item' style="float: left; width: 30%;">
                                            <input type='checkbox' class='arm_icheckbox arm_spf_active_checkbox arm_shortcode_form_popup_opt' value='twitter' name='arm_affiliate_share_fields[]' id='arm_aff_twitter_status'  />
                                            <label for='arm_aff_twitter_status'><?php _e('Twitter','ARM_AFFILIATE'); ?></label>
                                        </div>
                                        <div class='arm_aff_share_field_item' style="float: left; width: 30%;">
                                            <input type='checkbox' class='arm_icheckbox arm_spf_active_checkbox arm_shortcode_form_popup_opt' value='linkedin' name='arm_affiliate_share_fields[]' id='arm_aff_linkedin_status'  />
                                            <label for='arm_aff_linkedin_status'><?php _e('Linked In','ARM_AFFILIATE'); ?></label>
                                        </div>
                                        <div class='arm_aff_share_field_item' style="float: left; width: 30%;">
                                            <input type='checkbox' class='arm_icheckbox arm_spf_active_checkbox arm_shortcode_form_popup_opt' value='vkontakt' name='arm_affiliate_share_fields[]' id='arm_aff_vkontakt_status'  />
                                            <label for='arm_aff_vkontakt_status'><?php _e('VKontakt','ARM_AFFILIATE'); ?></label>
                                        </div>
                                        <div class='arm_aff_share_field_item' style="float: left; width: 30%;">
                                            <input type='checkbox' class='arm_icheckbox arm_spf_active_checkbox arm_shortcode_form_popup_opt' value='email' name='arm_affiliate_share_fields[]' id='arm_aff_email_status'  />
                                            <label for='arm_aff_email_status'><?php _e('Email','ARM_AFFILIATE'); ?></label>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        </table>
                    </div>
                </form>
                <form class="arm_shortcode_other_opts arm_shortcode_other_opts_affiliate_user_referral arm_hidden" onsubmit="return false;">
                    <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                            <tr>
                                <th><?php _e('User Referral', 'ARM_AFFILIATE');?></th>
                                <td>
                                    <ul class="arm_member_transaction_fields">
                                        <li class="arm_member_transaction_field_list">
                                                <label class="arm_member_transaction_field_item">
                                                        <input type="checkbox" class="arm_icheckbox arm_referral_transaction_fields" name="arm_transaction_fields[]" value="arm_ref_affiliate_id" checked="checked" />
                                                </label>
                                                <input type="text" class="arm_referral_transaction_fields arm_member_transaction_fields" name="arm_transaction_field_label_arm_ref_affiliate_id" value="<?php _e('User Name','ARM_AFFILIATE'); ?>" />
                                        </li>
                                        <li class="arm_member_transaction_field_list">
                                                <label class="arm_member_transaction_field_item">
                                                        <input type="checkbox" class="arm_icheckbox arm_referral_transaction_fields" name="arm_transaction_fields[]" value="arm_amount" checked="checked" />
                                                </label>
                                                <input type="text" class="arm_referral_transaction_fields arm_member_transaction_fields" name="arm_transaction_field_label_arm_amount" value="<?php _e('Amount','ARM_AFFILIATE'); ?>" />
                                        </li>
                                        <li class="arm_member_transaction_field_list">
                                                <label class="arm_member_transaction_field_item">
                                                        <input type="checkbox" class="arm_icheckbox arm_referral_transaction_fields" name="arm_transaction_fields[]" value="arm_plan_id" checked="checked" />
                                                </label>
                                                <input type="text" class="arm_referral_transaction_fields arm_member_transaction_fields" name="arm_transaction_field_label_arm_plan_id" value="<?php _e('Plan','ARM_AFFILIATE'); ?>" />
                                        </li>
                                        <li class="arm_member_transaction_field_list">
                                                <label class="arm_member_transaction_field_item">
                                                        <input type="checkbox" class="arm_icheckbox arm_referral_transaction_fields" name="arm_transaction_fields[]" value="arm_date_time" checked="checked" />
                                                </label>
                                                <input type="text" class="arm_referral_transaction_fields arm_member_transaction_fields" name="arm_transaction_field_label_arm_date_time" value="<?php _e('Date','ARM_AFFILIATE'); ?>" />
                                        </li>
                                        <li class="arm_member_transaction_field_list">
                                                <label class="arm_member_transaction_field_item">
                                                        <input type="checkbox" class="arm_icheckbox arm_referral_transaction_fields" name="arm_transaction_fields[]" value="arm_status" checked="checked" />
                                                </label>
                                                <input type="text" class="arm_referral_transaction_fields arm_member_transaction_fields" name="arm_transaction_field_label_arm_status" value="<?php _e('Status','ARM_AFFILIATE'); ?>" />
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Title', 'ARM_AFFILIATE');?></th>
                                <td>
                                    <input type="text" class='arm_member_transaction_opts' name="title" value="<?php _e('Referrals', 'ARM_AFFILIATE');?>">
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Records Per Page', 'ARM_AFFILIATE');?></th>
                                <td>
                                    <input type="text" class="arm_member_transaction_opts" name="per_page" value="5">
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('No Records Message', 'ARM_AFFILIATE');?></th>
                                <td>
                                    <input type="text" class="arm_member_transaction_opts" name="message_no_record" value="<?php _e('There is no any referral found', 'ARM_AFFILIATE');?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>  
                <form class="arm_shortcode_other_opts arm_shortcode_other_opts_affiliate_user_payout_transaction arm_hidden" onsubmit="return false;">
                    <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                            <tr>
                                <th><?php _e('User Payments', 'ARM_AFFILIATE');?></th>
                                <td>
                                    <ul class="arm_member_transaction_fields">
                                        <li class="arm_member_transaction_field_list">
                                                <label class="arm_member_transaction_field_item">
                                                        <input type="checkbox" class="arm_icheckbox arm_payouts_transaction_fields" name="arm_payment_fields[]" value="arm_tr_no" checked="checked" />
                                                </label>
                                                <input type="text" class="arm_payouts_transaction_fields arm_member_transaction_fields" name="arm_payment_field_label_arm_tr_no" value="<?php _e('Payout No.','ARM_AFFILIATE'); ?>" />
                                        </li>
                                        <li class="arm_member_transaction_field_list">
                                                <label class="arm_member_transaction_field_item">
                                                        <input type="checkbox" class="arm_icheckbox arm_payouts_transaction_fields" name="arm_payment_fields[]" value="arm_amount" checked="checked" />
                                                </label>
                                                <input type="text" class="arm_payouts_transaction_fields arm_member_transaction_fields" name="arm_payment_field_label_arm_amount" value="<?php _e('Amount','ARM_AFFILIATE'); ?>" />
                                        </li>
                                        <li class="arm_member_transaction_field_list">
                                                <label class="arm_member_transaction_field_item">
                                                        <input type="checkbox" class="arm_icheckbox arm_payouts_transaction_fields" name="arm_payment_fields[]" value="arm_date_time" checked="checked" />
                                                </label>
                                                <input type="text" class="arm_payouts_transaction_fields arm_member_transaction_fields" name="arm_payment_field_label_arm_date_time" value="<?php _e('Date','ARM_AFFILIATE'); ?>" />
                                        </li>
                                        <li class="arm_member_transaction_field_list">
                                                <label class="arm_member_transaction_field_item">
                                                        <input type="checkbox" class="arm_icheckbox arm_payouts_transaction_fields" name="arm_payment_fields[]" value="arm_balance" checked="checked" />
                                                </label>
                                                <input type="text" class="arm_payouts_transaction_fields arm_member_transaction_fields" name="arm_payment_field_label_arm_balance" value="<?php _e('Balance','ARM_AFFILIATE'); ?>" />
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Title', 'ARM_AFFILIATE');?></th>
                                <td>
                                    <input type="text" class='arm_member_payment_opts' name="title" value="<?php _e('Payouts', 'ARM_AFFILIATE');?>">
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Records Per Page', 'ARM_AFFILIATE');?></th>
                                <td>
                                    <input type="text" class="arm_member_payment_opts" name="per_page" value="5">
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('No Records Message', 'ARM_AFFILIATE');?></th>
                                <td>
                                    <input type="text" class="arm_member_payment_opts" name="message_no_record" value="<?php _e('There is no any payouts found', 'ARM_AFFILIATE');?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>  
                <form class="arm_shortcode_other_opts arm_shortcode_other_opts_affiliate_aff_banner arm_hidden" onsubmit="return false;">
                    <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                            <tr>
                                <th><?php _e('Select Banner', 'ARM_AFFILIATE');?></th>
                                <td>
                                        <input type="hidden" id="item_id" name="item_id" value="" />
                                        <dl class="arm_selectbox column_level_dd">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                        <ul data-id="item_id">
                                                                <li data-label="<?php _e('Select Banner','ARM_AFFILIATE');?>" data-value=""><?php _e('Select Option', 'ARM_AFFILIATE');?></li>
                                                                <?php 
                                                                if(!empty($banner_result)) {
                                                                    foreach ($banner_result as $banner) {
                                                                       $arm_banner_id = $banner->arm_banner_id;
                                                                       $arm_affiliate_title = $banner->arm_title;
                                                                        ?><li data-label="<?php echo $arm_affiliate_title;?>" data-value="<?php echo $arm_banner_id; ?>" ><?php echo $arm_affiliate_title; ?></li><?php
                                                                    }
                                                                } 
                                                                ?>
                                                        </ul>
                                                </dd>
                                        </dl>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Select Social Network(s) to Share Affiliate Link','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <div class='arm_social_field_popup_wrapper'>
                                        <div class='arm_aff_share_field_item' style="float: left; width: 30%;">
                                            <input type='checkbox' class='arm_icheckbox arm_spf_active_checkbox arm_shortcode_form_popup_opt' value='facebook' name='arm_affiliate_share_fields[]' id='arm_aff_facebook_status'  />
                                            <label for='arm_aff_facebook_status'><?php _e('Facebook','ARM_AFFILIATE'); ?></label>
                                        </div>
                                        <div class='arm_aff_share_field_item' style="float: left; width: 30%;">
                                            <input type='checkbox' class='arm_icheckbox arm_spf_active_checkbox arm_shortcode_form_popup_opt' value='twitter' name='arm_affiliate_share_fields[]' id='arm_aff_twitter_status'  />
                                            <label for='arm_aff_twitter_status'><?php _e('Twitter','ARM_AFFILIATE'); ?></label>
                                        </div>
                                        <div class='arm_aff_share_field_item' style="float: left; width: 30%;">
                                            <input type='checkbox' class='arm_icheckbox arm_spf_active_checkbox arm_shortcode_form_popup_opt' value='linkedin' name='arm_affiliate_share_fields[]' id='arm_aff_linkedin_status'  />
                                            <label for='arm_aff_linkedin_status'><?php _e('Linked In','ARM_AFFILIATE'); ?></label>
                                        </div>
                                        <div class='arm_aff_share_field_item' style="float: left; width: 30%;">
                                            <input type='checkbox' class='arm_icheckbox arm_spf_active_checkbox arm_shortcode_form_popup_opt' value='vkontakt' name='arm_affiliate_share_fields[]' id='arm_aff_vkontakt_status'  />
                                            <label for='arm_aff_vkontakt_status'><?php _e('VKontakt','ARM_AFFILIATE'); ?></label>
                                        </div>
                                        <div class='arm_aff_share_field_item' style="float: left; width: 30%;">
                                            <input type='checkbox' class='arm_icheckbox arm_spf_active_checkbox arm_shortcode_form_popup_opt' value='email' name='arm_affiliate_share_fields[]' id='arm_aff_email_status'  />
                                            <label for='arm_aff_email_status'><?php _e('Email','ARM_AFFILIATE'); ?></label>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        </table>
                    </div>
                </form>  
                <form class="arm_shortcode_other_opts arm_shortcode_other_opts_armaff_conditional_content arm_hidden" onsubmit="return false;">
                    <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                            <tr>
                                <th><?php _e('Display Content Based On', 'ARM_AFFILIATE');?></th>
                                <td>
                                        <input type="hidden" id="armaff_conditional_content" name="armaff_conditional_content" value="arm_if_affiliate" class="type" />
                                        <dl class="arm_selectbox column_level_dd">
                                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                                <dd>
                                                        <ul data-id="armaff_conditional_content">
                                                                <li data-label="<?php _e('If Affiliate User','ARM_AFFILIATE'); ?>" data-value="arm_if_affiliate" ><?php _e('If Affiliate User','ARM_AFFILIATE'); ?></li>
                                                                <li data-label="<?php _e('If Non Affiliate User','ARM_AFFILIATE'); ?>" data-value="arm_if_non_affiliate" ><?php _e('If Non Affiliate User','ARM_AFFILIATE'); ?></li>
                                                        </ul>
                                                </dd>
                                        </dl>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Content','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <textarea class="arm_popup_textarea" name="armaff_conditional_text" rows="3"></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>
                <form class="arm_shortcode_other_opts arm_shortcode_other_opts_arm_aff_statistics arm_hidden" onsubmit="return false;">
                    <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                            <tr>
                                <th><?php _e('Earning Title','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="earning_title" value="<?php _e('Earnings', 'ARM_AFFILIATE') ?>" id="earning_title_input"><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Earning Background Color','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="hidden" id="arm_aff_statistics" name="arm_aff_statistics" value="arm_aff_statistics" class="type" />
                                    <input type="text" name="earning_back_color" class="arm_colorpicker arm_form_modal_bgcolor" value="#27DDFE"  id="arm_earning_back_color_input"/><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Payment Paid Title','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="payment_paid_title" value="<?php _e('Payments (Paid)', 'ARM_AFFILIATE') ?>" id="payment_paid_title_input"><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Paid Payment Background Color','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="paid_payment_back_color" class="arm_colorpicker arm_form_modal_bgcolor" value="#4caf50"  id="paid_payment_back_color_input"/><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Payment Unpaid Title','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="payment_unpaid_title" value="<?php _e('Payments (Unpaid)', 'ARM_AFFILIATE') ?>" id="payment_unpaid_title_input"><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Unpaid Payment Background Color','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="unpaid_payment_back_color" class="arm_colorpicker arm_form_modal_bgcolor" value="#23b7e5"  id="unpaid_payment_back_color_input"/><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Visitor Title','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="visitor_title" value="<?php _e('Visitor', 'ARM_AFFILIATE') ?>" id="visitor_title_input"><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Visitor Background Color','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="visitor_back_color" class="arm_colorpicker arm_form_modal_bgcolor" value="#f44284"  id="visitor_back_color_input"/><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Referral Title','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="referral_title" value="<?php _e('Referral', 'ARM_AFFILIATE') ?>" id="referral_title_input"><br/>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Referral Background Color','ARM_AFFILIATE'); ?></th>
                                <td>
                                    <input type="text" name="referral_back_color" class="arm_colorpicker arm_form_modal_bgcolor" value="#44425b"  id="referral_back_color_input"/><br/>
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>
                <form class="arm_shortcode_other_opts arm_shortcode_other_opts_arm_aff_earning arm_hidden" onsubmit="return false;">
                    <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                            <tr>
                                <td>
                                    
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>
                <form class="arm_shortcode_other_opts arm_shortcode_other_opts_arm_aff_payment_paid arm_hidden" onsubmit="return false;">
                    <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                            <tr>
                                <td>
                                    
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>
                <form class="arm_shortcode_other_opts arm_shortcode_other_opts_arm_aff_payment_unpaid arm_hidden" onsubmit="return false;">
                    <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                            <tr>
                                <td>
                                    
                                </td>
                            </tr>
                        </table>
                    </div>
                </form>
                <form class="arm_shortcode_other_opts arm_shortcode_other_opts_arm_aff_referral arm_hidden" onsubmit="return false;">
                    <div class="arm_group_body">
                        <table class="arm_shortcode_option_table">
                            <tr>
                                <td>
                                    
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
            <div id="arm-affiliate_buttons" class="arm_tabgroup_content_buttons">
                    <div class="arm_group_footer">
                        <div class="popup_content_btn_wrapper">
                            <button type="button" class="arm_aff_shortcode_insert_btn arm_insrt_btn"><?php _e('Add Shortcode', 'ARM_AFFILIATE');?></button>
                            <a class="arm_cancel_btn popup_close_btn" href="javascript:void(0)"><?php _e('Cancel', 'ARM_AFFILIATE') ?></a>
                        </div>
                    </div>                         
            </div>
            <?php
        }
        
    }
}

global $arm_aff_tinymce_options;
$arm_aff_tinymce_options = new arm_aff_tinymce_options();
?>