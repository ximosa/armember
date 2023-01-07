<?php
    global $arm_affiliate_settings, $arm_subscription_plans, $arm_members_class, $arm_payment_gateways, $arm_aff_referrals, $arm_global_settings, $wpdb, $arm_affiliate;

    $global_currency = $arm_payment_gateways->arm_get_global_currency();
    
    $all_members = $arm_members_class->arm_get_all_members_without_administrator(0,0);
    $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();

    $armaffiliate_active_woocommerce = $arm_affiliate->arm_affiliate_is_woocommerce_active();

    $affiliate_options['arm_aff_referral_var'] = isset($affiliate_options['arm_aff_referral_var']) ? $affiliate_options['arm_aff_referral_var'] : 'armaff' ;
    $affiliate_options['arm_aff_referral_timeout'] = isset($affiliate_options['arm_aff_referral_timeout']) ? $affiliate_options['arm_aff_referral_timeout'] : '1' ;
//    $affiliate_options['arm_aff_referral_rate_type'] = isset($affiliate_options['arm_aff_referral_rate_type']) ? $affiliate_options['arm_aff_referral_rate_type'] : 'percentage' ;
    $affiliate_options['arm_aff_referral_default_rate'] = isset($affiliate_options['arm_aff_referral_default_rate']) ? $affiliate_options['arm_aff_referral_default_rate'] : '20' ;
    $affiliate_options['arm_aff_referral_url'] = isset($affiliate_options['arm_aff_referral_url']) ? $affiliate_options['arm_aff_referral_url'] :  get_home_url() ;
    $affiliate_options['arm_aff_referral_status'] = isset($affiliate_options['arm_aff_referral_status']) ? $affiliate_options['arm_aff_referral_status'] :  '0' ;
    $affiliate_options['arm_aff_allow_duplicate_referrals'] = isset($affiliate_options['arm_aff_allow_duplicate_referrals']) ? $affiliate_options['arm_aff_allow_duplicate_referrals'] : '0' ;
    $affiliate_options['arm_aff_not_allow_zero_commision'] = isset($affiliate_options['arm_aff_not_allow_zero_commision']) ? $affiliate_options['arm_aff_not_allow_zero_commision'] : '0' ;
    $affiliate_options['arm_aff_allow_affiliate_register'] = isset($affiliate_options['arm_aff_allow_affiliate_register']) ? $affiliate_options['arm_aff_allow_affiliate_register'] : '0' ;
    $affiliate_options['arm_aff_id_encoding'] = isset($affiliate_options['arm_aff_id_encoding']) ? $affiliate_options['arm_aff_id_encoding'] : '0' ;
    $affiliate_options['arm_aff_allow_fancy_url'] = isset($affiliate_options['arm_aff_allow_fancy_url']) ? $affiliate_options['arm_aff_allow_fancy_url'] : '0' ;

    if($armaffiliate_active_woocommerce){
        $armaffiliate_woo = isset($affiliate_options['armaffiliate_woo_options']) ? $affiliate_options['armaffiliate_woo_options'] : array();
        $armaffiliate_woo['status'] = isset($armaffiliate_woo['status']) ? $armaffiliate_woo['status'] : '0';
    }

    $armaffiliate_slug = 'create_user_affiliate';
    $armaffiliate_form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$arm_affiliate->tbl_arm_aff_forms} WHERE `arm_form_slug` = '%s' LIMIT 1;", $armaffiliate_slug ) );

    $armaffiliate_formid = $armaffiliate_form->arm_form_id;
    $armaffiliate_formtitle = $armaffiliate_form->arm_form_title;
    $armaffiliate_formstyle = ($armaffiliate_form->arm_form_style != '') ? $armaffiliate_form->arm_form_style : 'material';
    $armaffiliate_formfields = $armaffiliate_form->arm_form_fields;
    $armaffiliate_formfields = ($armaffiliate_formfields != '') ? json_decode($armaffiliate_formfields) : '';

    $armaff_ignore_required = array('affiliate_uname', 'affiliate_email', 'submit');

?>
<style>
    .form-sub-table td{padding:5px !important;}
    .form-sub-table input[type="text"]{ width: 70%;}

    .armaff_form_setting_header {
        width: 100%;
        padding-top: 10px;
    }
    .armaff_form_setting_header div { 
        display: inline-block;
        min-width: 150px;
        font-weight: bold;
    }
    .armaff_form_setting_body {
        width:  100%;
        padding-top:  10px;
    }
    .armaff_form_setting_field_wrapper{ padding: 10px 0; width:  100%; }
    .armaff_form_setting_body .armaff_form_setting_field_wrapper div {
        display: inline-block;
    }
    .armaff_form_setting_body .armaff_form_setting_field_wrapper:not(.arm_aff_checkbo_field_wrapper_checkbox) div {
        min-width: 150px;
    }
    .arm_aff_checkbo_field_wrapper_checkbox .armaff_field, .arm_aff_checkbo_field_wrapper_checkbox .armaff_field_required_status { min-width: 150px;}
    .armaff_field_label { width: 300px; }
    .armaff_field_required_status { padding-left: 10px; }
    .armaff_form_setting_body .armaff_form_setting_field_wrapper div input { width: 100%; }

</style>

<div class="wrap arm_page arm_affiliate_settings_main_wrapper">
    <div class="content_wrapper arm_affiliate_settings_content" id="content_wrapper">
        <div class="page_title"><?php _e('Affiliate Settings','ARM_AFFILIATE');?></div>
        <div class="arm_affiliate_settings_wrapper">
            <?php /*<input type="hidden" name="currency_position" id="currency_position" value="<?php echo $global_currency_sym_pos; ?>" /> */ ?>
            <form method="post" action="#" id="arm_affiliate_settings" name="arm_affiliate_settings" class="arm_affiliate_settings arm_admin_form" onsubmit="return false;">
                <div class="arm_solid_divider"></div>
                <table class="form-table">
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e(' Affiliate URL Parameter name', 'ARM_AFFILIATE'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Whenever you will change this parameter name existing affiliate URLs will be affected. Old Affiliate URLs will be stopped working.( So Please do not change until its necessary. )', 'ARM_AFFILIATE'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_aff_referral_var" type="text" name="arm_aff_referral_var" value="<?php echo (!empty($affiliate_options['arm_aff_referral_var']) ? $affiliate_options['arm_aff_referral_var'] : '' ); ?>" >
                            <br/>
                            <span class="arm_info_text"><?php _e('The current Affiliate URL parameter name Ex. :', 'ARM_AFFILIATE'); echo 'armaff'; ?></span><br/>
                            
                            <span id="aff_referral_var_error" class="arm_error_msg aff_referral_var_error" style="display:none;"><?php _e('Please enter Referral Variable.', 'ARM_AFFILIATE');?></span>         
                            <span id="invalid_aff_referral_var_error" class="arm_error_msg invalid_aff_referral_var_error" style="display:none;"><?php _e('Please enter valid Referral Variable.', 'ARM_AFFILIATE');?></span>         
                        </td>
                    </tr>


                    <tr class="form-field" id="arm_referral_rete">
                        <th class="arm-form-table-label">
                            <?php _e('Referral URL', 'ARM_AFFILIATE'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Set page URL that you want to referral visit the page first.', 'ARM_AFFILIATE'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_aff_referral_url" type="text" name="arm_aff_referral_url" value="<?php echo (!empty($affiliate_options['arm_aff_referral_url']) ? $affiliate_options['arm_aff_referral_url'] : '' ); ?>" >
                            <span id="arm_aff_referral_url_error" class="arm_error_msg arm_aff_referral_url_error" style="display:none;"><?php _e('Please enter Referral URL.', 'ARM_AFFILIATE');?></span>         
                            <span id="invalid_arm_aff_referral_url_error" class="arm_error_msg invalid_arm_aff_referral_url_error" style="display:none;"><?php _e('Please enter valid Referral URL.', 'ARM_AFFILIATE');?></span>         
                        </td>
                    </tr>


                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Affiliate ID Style', 'ARM_AFFILIATE'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('It is for security purpose. if you select MD5 encoding then the affiliate user share that URL then other user will not able to see actual affiliate user id in URL.', 'ARM_AFFILIATE'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <input type='hidden' id='arm_aff_id_encoding' name="arm_aff_id_encoding" value="<?php echo $affiliate_options['arm_aff_id_encoding']; ?>" />
                            <dl class="arm_selectbox column_level_dd">
                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                <dd>
                                    <ul data-id="arm_aff_id_encoding">
                                        <li data-label="<?php _e('Plain ID','ARM_AFFILIATE');?>" data-value="0"><?php _e('Plain ID', 'ARM_AFFILIATE');?></li>
                                        <li data-label="<?php _e('MD5 Encoded ID','ARM_AFFILIATE');?>" data-value="MD5"><?php _e('MD5 Encoded ID', 'ARM_AFFILIATE');?></li>
                                        <li data-label="<?php _e('Username','ARM_AFFILIATE');?>" data-value="username"><?php _e('Username', 'ARM_AFFILIATE');?></li>
                                    </ul>
                                </dd>
                            </dl>
                        </td>
                    </tr>
                    <?php
                    /*
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Affiliate URL Pattern', 'ARM_AFFILIATE'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <?php $affiliate_options['arm_aff_url_pattern'] = (isset($affiliate_options['arm_aff_url_pattern'])) ? $affiliate_options['arm_aff_url_pattern'] : '0'; ?>
                            <div class="arm_form_fields_wrapper">
                                <input id="arm_aff_url_simple_pattern" class="arm_general_input arm_iradio" type="radio" value="0" name="arm_aff_url_pattern" <?php checked($affiliate_options['arm_aff_url_pattern'], '0');?>  />
                                <label for="arm_aff_url_simple_pattern"><?php _e('Simple','ARMember');?></label>
                                
                                <input id="arm_aff_url_fancy_pattern" class="arm_general_input arm_iradio" type="radio" value="1" name="arm_aff_url_pattern" <?php checked($affiliate_options['arm_aff_url_pattern'], '1');?>  />
                                <label for="arm_aff_url_fancy_pattern"><?php _e('Fancy','ARMember');?></label>
                            </div>
                        </td>
                    </tr>
                    */
                    ?>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Enable Fancy Affiliate URL', 'ARM_AFFILIATE'); ?>
                            <!-- <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Provide fancy URL like.', 'ARM_AFFILIATE'); ?>"></i> -->
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_aff_allow_fancy_url" <?php checked($affiliate_options['arm_aff_allow_fancy_url'], '1');?> value="1" class="armswitch_input" name="arm_aff_allow_fancy_url"/>
                                <label for="arm_aff_allow_fancy_url" class="armswitch_label"></label>
                            </div>

                            <?php
                                $arm_aff_id_encoding = $affiliate_options['arm_aff_id_encoding'];
                                if($arm_aff_id_encoding=='username')
                                {
                                    $arm_aff_id_encoding_str = "{username}";
                                }
                                else {
                                    $arm_aff_id_encoding_str = "{affiliate_id}";
                                }
                                if($affiliate_options['arm_aff_allow_fancy_url'])
                                {
                                    $armaff_url= parse_url( $affiliate_options['arm_aff_referral_url'] );
                                    $armaff_query_string = array_key_exists( 'query', $armaff_url ) ? '?' . $armaff_url['query'] : '';
                                    $armaff_url_scheme      = isset( $armaff_url['scheme'] ) ? $armaff_url['scheme'] : 'http';
                                    $armaff_url_host        = isset( $armaff_url['host'] ) ? $armaff_url['host'] : '';
                                    $armaff_constructed_url = $armaff_url_scheme . '://' . $armaff_url_host . $armaff_url['path'];
                                    $armaff_base_url = $armaff_constructed_url;
                                    

                                    $arm_ex_referal_url = trailingslashit( $armaff_base_url ) . trailingslashit($affiliate_options['arm_aff_referral_var']) . $arm_aff_id_encoding_str . $armaff_query_string;

                                } else {
                                    $arm_ex_referal_url = $arm_global_settings->add_query_arg($affiliate_options['arm_aff_referral_var'], $arm_aff_id_encoding_str, $affiliate_options['arm_aff_referral_url']);
                                }
                             ?>
                            <p>&nbsp;</p>
                            <span class="arm_info_text" style="width: 100%;"><?php _e('The Referral URL is :', 'ARM_AFFILIATE'); ?><code><span id="armaff_referral_url_example"><?php echo $arm_ex_referal_url; ?></span></span></code>
                        </td>
                    </tr>


                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Referral Cookie Expiration', 'ARM_AFFILIATE'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Enter number of days you want to keep valid generated affiliate URL in users browser.', 'ARM_AFFILIATE'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_aff_referral_timeout" type="text" name="arm_aff_referral_timeout" value="<?php echo $affiliate_options['arm_aff_referral_timeout']; ?>" onkeydown="javascript:return checkNumber(event)" /> 
                            <span class="arm_suffix_currency_symbol" ><?php _e('Days', 'ARM_AFFILIATE'); ?></span><br/>
                            <span class="arm_info_text"><?php _e('If you enter 0, referrals will only be valid until the visitor closes the browser (session).', 'ARM_AFFILIATE'); ?></span><br/>
                            <span class="arm_info_text"><?php _e('The default value is 1. In this case, if a visitor comes to your site via an affiliate link,<br/> a suggested referral will be valid until 1 days after she or he clicked that affiliate link.', 'ARM_AFFILIATE'); ?></span>
                            <span id="arm_aff_referral_timeout_error" class="arm_error_msg arm_aff_referral_timeout_error" style="display:none;"><?php _e('Please enter Referral Timeout.', 'ARM_AFFILIATE');?></span>         
                            <span id="invalid_arm_aff_referral_timeout_error" class="arm_error_msg invalid_arm_aff_referral_timeout_error" style="display:none;"><?php _e('Please enter valid Referral Timeout.', 'ARM_AFFILIATE');?></span>         
                        </td>
                    </tr>
                    
                    
                    <tr class="form-field" id="arm_referral_rete" style="<?php // echo $referral_rate_style; ?>">
                        <th class="arm-form-table-label">
                            <?php _e('Default Referral Amount', 'ARM_AFFILIATE'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('If user will signup without any plan selection of ARMember than default amount entered here will be considered.', 'ARM_AFFILIATE'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <input id="arm_aff_referral_default_rate" type="text" name="arm_aff_referral_default_rate" value="<?php echo (!empty($affiliate_options['arm_aff_referral_default_rate']) ? $affiliate_options['arm_aff_referral_default_rate'] : '' ); ?>" onkeydown="javascript:return checkNumber(event)" /> 
                            <span class="arm_suffix_currency_symbol" ><?php echo $global_currency; ?></span>
                            <span id="arm_aff_referral_default_rate_error" class="arm_error_msg arm_aff_referral_default_rate_error" style="display:none;"><?php _e('Please enter Referral Rate.', 'ARM_AFFILIATE');?></span>         
                            <span id="invalid_arm_aff_referral_default_rate_error" class="arm_error_msg invalid_arm_aff_referral_default_rate_error" style="display:none;"><?php _e('Please enter valid Referral Rate.', 'ARM_AFFILIATE');?></span>         
                        </td>
                    </tr>

                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Default Referral Status', 'ARM_AFFILIATE'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Set default referral status.', 'ARM_AFFILIATE'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <?php // print_r($arm_aff_referrals->referral_status);?>
                            <input type='hidden' id='arm_aff_referral_status' name="arm_aff_referral_status" value="<?php echo $affiliate_options['arm_aff_referral_status']; ?>" />
                            <dl class="arm_selectbox column_level_dd">
                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                <dd>
                                    <ul data-id="arm_aff_referral_status">
                                        <li data-label="<?php _e('Accepted','ARM_AFFILIATE');?>" data-value="1"><?php _e('Accepted', 'ARM_AFFILIATE');?></li>
                                        <li data-label="<?php _e('Pending','ARM_AFFILIATE');?>" data-value="0"><?php _e('Pending', 'ARM_AFFILIATE');?></li>
                                        <li data-label="<?php _e('Rejected','ARM_AFFILIATE');?>" data-value="3"><?php _e('Rejected', 'ARM_AFFILIATE');?></li>
                                    </ul>
                                </dd>
                            </dl>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Allow Referrals when Renew / Change Plan', 'ARM_AFFILIATE'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Allow affiliate user to get referral commission when any referred user will change / renew his membership plan.', 'ARM_AFFILIATE'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_aff_allow_duplicate_referrals" <?php checked($affiliate_options['arm_aff_allow_duplicate_referrals'], '1');?> value="1" class="armswitch_input" name="arm_aff_allow_duplicate_referrals"/>
                                <label for="arm_aff_allow_duplicate_referrals" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Do Not Allow Zero Amount Commission', 'ARM_AFFILIATE'); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_aff_not_allow_zero_commision" <?php checked($affiliate_options['arm_aff_not_allow_zero_commision'], '1');?> value="1" class="armswitch_input" name="arm_aff_not_allow_zero_commision"/>
                                <label for="arm_aff_not_allow_zero_commision" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('Automatic Create Affiliate link upon new Registration', 'ARM_AFFILIATE'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Allow newly registered user to become affiliate user by default.', 'ARM_AFFILIATE'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_aff_allow_affiliate_register" <?php checked($affiliate_options['arm_aff_allow_affiliate_register'], '1');?> value="1" class="armswitch_input" name="arm_aff_allow_affiliate_register"/>
                                <label for="arm_aff_allow_affiliate_register" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>
                    
                    
                </table>

                <div class="arm_solid_divider"></div>
                <div class="page_sub_title">
                    <?php _e('Form Settings', 'ARM_AFFILIATE'); ?>
                    <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Set your form fields to let your user create affiliate logged in to your site.', 'ARM_AFFILIATE'); ?>"></i>
                </div>
                <table class="form-table">
                    <tr class="form-field">
                        <th class="arm-form-table-label"><?php _e('Form Input Style', 'ARM_AFFILIATE'); ?></th>
                        <td>
                            <input type='hidden' id='armaff_form_style' name="armaff_form_style" value="<?php echo $armaffiliate_formstyle; ?>" />
                            <dl class="arm_selectbox column_level_dd">
                                <dt><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                <dd>
                                    <ul data-id="armaff_form_style">
                                        <li data-label="<?php _e('Material Style','ARM_AFFILIATE');?>" data-value="material"><?php _e('Material Style', 'ARM_AFFILIATE');?></li>
                                        <li data-label="<?php _e('Standard Style','ARM_AFFILIATE');?>" data-value="standard"><?php _e('Standard Style', 'ARM_AFFILIATE');?></li>
                                    </ul>
                                </dd>
                            </dl>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e(' Form Title', 'ARM_AFFILIATE'); ?>
                            <!-- <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Whenever you will change this parameter name existing affiliate URLs will be affected. Old Affiliate URLs will be stopped working.( So Please do not change until its necessary. )', 'ARM_AFFILIATE'); ?>"></i> -->
                        </th>
                        <td class="arm-form-table-content">
                            <input id="armaff_form_title" type="text" name="armaff_form_title" value="<?php echo $armaffiliate_formtitle; ?>" >
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th class="arm-form-table-label"><?php _e('Field Options', 'ARM_AFFILIATE'); ?></th>
                        <td>
                            <div class="armaff_form_setting_header">
                                <div class="armaff_field">Field</div>
                                <div class="armaff_field_label">Field Label</div>
                                <div class="armaff_field_required_status">Required</div>
                            </div>
                            <div class="armaff_form_setting_body">
                                <?php
                                    foreach ($armaffiliate_formfields as $field_slug => $field_options) {
                                        $armaff_field_type = $field_options->type;
                                        $armaff_field_label = $field_options->label;
                                        $armaff_field_display_label = isset($field_options->display_label) ? $field_options->display_label : $armaff_field_label;
                                        ?>
                                        <div class="armaff_form_setting_field_wrapper <?php if(!in_array($field_slug, $armaff_ignore_required)){ ?> arm_aff_checkbo_field_wrapper_checkbox <?php } ?>">
                                            <div class="armaff_field"><?php echo $armaff_field_label; ?></div>
                                            <div class="armaff_field_label"><input type="text" name="armaff_field_display_label[<?php echo $field_slug; ?>]" value="<?php echo $armaff_field_display_label; ?>" /></div>
                                            <div class="armaff_field_required_status">
                                                <div class="arm_form_fields_wrapper">
                                                    <?php if(!in_array($field_slug, $armaff_ignore_required)){ ?>
                                                            <input id="armaff_field_required_<?php echo $field_slug; ?>" class="arm_icheckbox armaff_field_required" name="armaff_field_required[<?php echo $field_slug; ?>]" type="checkbox" value="1" <?php checked($field_options->required, '1'); ?> />
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                <?php
                                    }
                                ?>
                            </div>

                        </td>
                    </tr>
                </table>

                <!-- Display wooCommerce Settings only if wooCommerce plugin Active -->
                <?php if($armaffiliate_active_woocommerce){ ?>
                    <div class="arm_solid_divider"></div>
                    <div class="page_sub_title">
                        <?php _e('WooCommerce', 'ARM_AFFILIATE'); ?>
                        <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('By Enable this settings ARMember Affiliate will allow to provide referrals on each sale of WooCommerce Products.', 'ARM_AFFILIATE'); ?>"></i>
                    </div>
                    <table class="form-table">
                        <tr class="form-field">
                            <th class="arm-form-table-label"><?php _e('Enable Integration', 'ARM_AFFILIATE'); ?></th>
                            <td class="arm-form-table-content">
                                <div class="armswitch arm_global_setting_switch">
                                    <input type="checkbox" id="armaffiliate_woocommerce_enable" <?php checked($armaffiliate_woo['status'], '1'); ?> value="1" class="armswitch_input armaff_payment_payouts_switch" name="armaffiliate_woo_options[status]"/>
                                    <label for="armaffiliate_woocommerce_enable" class="armswitch_label"></label>
                                </div>
                            </td>
                        </tr>
                    </table>
                <?php } ?>

                <div class="arm_submit_btn_container">
                    <button id="arm_affiliate_settings_btn" class="arm_save_btn" name="arm_affiliate_settings_btn" type="submit"><?php _e('Save', 'ARM_AFFILIATE') ?></button>&nbsp;<img src="<?php echo ARM_AFFILIATE_IMAGES_URL.'/arm_loader.gif' ?>" id="arm_loader_img" style="position:relative;top:8px;display:none;" width="24" height="24" />
                </div>
                <?php wp_nonce_field( 'arm_wp_nonce' );?>
            </form>
            <div class="armclear"></div>
         </div>
    </div>
</div>
<?php $arm_affiliate_settings->arm_affiliate_get_footer(); ?>