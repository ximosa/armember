<?php
    global $arm_affiliate_migration, $arm_affiliate_settings;

    $arm_affiliate_migration->armaff_reset_migrate_session();

?>

<div class="wrap arm_page arm_affiliate_migration_main_wrapper">
    <div class="content_wrapper arm_affiliate_migration_content" id="content_wrapper">
        <div class="page_title"><?php _e('Migration Tool','ARM_AFFILIATE');?></div>
        <div class="arm_affiliate_migration_wrapper">
            <form method="post" action="#" id="arm_affiliate_migration" name="arm_affiliate_migration" class="arm_affiliate_migration arm_admin_form" onsubmit="return false;">
                <div class="arm_solid_divider"></div>
                <table class="form-table">
                    
                    <tr class="form-field"><td class="arm-form-table-content" colspan="2">With this feature, you can migrate your affiliate accounts from other popular plugins. Before start migration from other plugins, just make sure you don't have any affiliates created with ARMember affilliate addon as they might conflict with other plugin which you are going to migrate.</td>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('select affiliate plugin <br> to migrate', 'ARM_AFFILIATE'); ?>
                            <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e('Please select plugin from where you would like to migrate affiliate accounts.', 'ARM_AFFILIATE'); ?>"></i>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="arm_form_fields_wrapper armaff_migrate_data_options_container">
                                <input id="armaff_affiliateWP" class="arm_general_input arm_iradio" type="radio" value="affiliateWP" name="armaff_migrate" checked="checked" armaff_wptext="AffiliateWP" />
                                <label for="armaff_affiliateWP">AffiliateWP</label>
                                
                                <input id="armaff_affiliatesPro" class="arm_general_input arm_iradio" type="radio" value="affiliatesPro" name="armaff_migrate" armaff_wptext="Affiliates Pro" />
                                <label for="armaff_affiliatesPro">Affiliates Pro</label>
                            </div>
                        </td>
                    </tr>
                    <tr class="form-field armaff_migrate_confirmation_row">
                        <th>&nbsp;</th>

                        <td class="armaff_migrate_confirmation_col arm-form-table-content"></td>

                        <td class="armaff_migrate_confirmation armaff_hide">
                            <div class="armaff_highlight_text"><?php _e('Before migration process starts please note that', 'ARM_AFFILIATE')?>,</div>
                            <ol class="armaff_migration_confirm_points">
                                <li><?php _e('There are {WP_AFFILIATE_RECORDS} total affiliates to migrate from {WP_AFFILIATE} plugin.', 'ARM_AFFILIATE'); ?></li>
                                <li><?php _e('Make sure to change Affiliate URL Parameter name same as you have set for {WP_AFFILIATE} plugin.', 'ARM_AFFILIATE'); ?></li>
                                <li><?php _e('While migrating banner/creative if there is already exist banner with same id then it will be skipped to be migrated.', 'ARM_AFFILIATE'); ?> </li>
                                <li><?php _e('After complete migration please check affiliate settings first to make all user referral URL working properly.', 'ARM_AFFILIATE'); ?></li>
                            </ol>
                            <div class="armaff_migrate_input_wrapper">
                                <div class="arm_form_fields_wrapper">
                                    <input id="armaff_wpdeactivate_plugin" class="armaff_icheckbox armaff_wpdeactivate_plugin" name="armaff_wpdeactivate_plugin" type="checkbox" value="1" checked="checked" />
                                    <label for="armaff_wpdeactivate_plugin"><?php _e('Deactivate {WP_AFFILIATE} plugin After Complete Migration.', 'ARM_AFFILIATE');?></label>
                                </div>
                                <div class="armaff_migrate_input_description">
                                    (<?php _e('Check this option to deactivate {WP_AFFILIATE} plugin after complete migration.', 'ARM_AFFILIATE'); ?>)
                                </div>
                            </div>
                            <div class="armaff_migrate_input_wrapper">
                                <div class="arm_form_fields_wrapper">
                                    <input id="armaff_update_url_param_name" class="armaff_icheckbox armaff_update_url_param_name" name="armaff_update_url_param_name" type="checkbox" value="1" checked="checked" />
                                    <label for="armaff_update_url_param_name"><?php _e('Change affiliate URL parameter name', 'ARM_AFFILIATE');?></label>
                                </div>
                                <div class="armaff_migrate_input_description">
                                    (<?php _e('This will change affiliate URL parameter name same as you have set for {WP_AFFILIATE} plugin.', 'ARM_AFFILIATE'); ?>)
                                </div>
                            </div>
                            <div class="armaff_migrate_input_wrapper armaff_hide" id="armaff_enable_fancy_url_option">
                                <div class="arm_form_fields_wrapper">
                                    <input id="armaff_enable_fancy_url" class="armaff_icheckbox armaff_enable_fancy_url" name="armaff_enable_fancy_url" type="checkbox" value="1" />
                                    <label for="armaff_enable_fancy_url"><?php _e('Enable Fancy referral URL after migration.', 'ARM_AFFILIATE');?></label>
                                </div>
                                <div class="armaff_migrate_input_description">
                                    (<?php _e('Check this option to enable fancy URL after complete migration.', 'ARM_AFFILIATE'); ?>)
                                </div>
                            </div>
                            <div class="armaff_migrate_input_wrapper armaff_hide" id="armaff_enable_affid_encoding_option">
                                <div class="arm_form_fields_wrapper">
                                    <input id="armaff_enable_affid_encoding" class="armaff_icheckbox armaff_enable_affid_encoding" name="armaff_enable_affid_encoding" type="checkbox" value="1" />
                                    <label for="armaff_enable_affid_encoding"><?php _e('Enable Affiliate Id Encoding after migration.', 'ARM_AFFILIATE');?></label>
                                </div>
                                <div class="armaff_migrate_input_description">
                                    (<?php _e('Check this option to enable Affiliate ID Encoding.', 'ARM_AFFILIATE'); ?>)
                                </div>
                            </div>
                            <div class="armaff_migrate_input_wrapper">
                                <div class="arm_form_fields_wrapper">
                                    <input id="armaff_replace_wpshortcode" class="armaff_icheckbox armaff_replace_wpshortcode" name="armaff_replace_wpshortcode" type="checkbox" value="1" />
                                    <label for="armaff_replace_wpshortcode"><?php _e('Replace {WP_AFFILIATE} Shortcodes With ARMember Affiliates Shortcodes.', 'ARM_AFFILIATE');?></label>
                                </div>
                                <div class="armaff_migrate_input_description">
                                    (<?php _e('Replace your {WP_AFFILIATE} plugin shortcodes placed in pages or posts with ARMember Affiliates provided shortcodes.', 'ARM_AFFILIATE'); ?>)
                                </div>
                            </div>
                        </td>
                        <td class="armaff_migrate_restrict armaff_hide">
                            <div class="armaff_migration_error_text"><?php _e("Sorry! Migration is not possible!", 'ARM_AFFILIATE'); ?> <br/><br/> <?php _e("You already have {ARMAFF_AFFILIATE_RECORDS} affiliate accounts exist in ARMember Affiliate and it has last affiliate ID is {ARM_LAST_AFFILIATE}. While as {WP_AFFILIATE} has last affiliate ID {WP_FIRST_AFFILIATE}. So, its conflicting with both the IDs.", 'ARM_AFFILIATE')?> <br><br> <?php _e("If you still want to migrate all Affiliates accounts from {WP_AFFILIATE} to ARMember affiliates, you need to delete all the affiliate accounts first from ARMember affiliates. So, it will not be any confliction and you will be able to migrate successfully.", 'ARM_AFFILIATE')?></div>
                        </td>
                    </tr>
                    <input type="hidden" name="armaff_account_last_id" id="armaff_account_last_id" value="">
                    <input type="hidden" name="wp_account_first_id" id="wp_account_first_id" value="">
                </table>
                <div class="arm_submit_btn_container">
                    <button id="arm_affiliate_migrate_btn" class="arm_save_btn" name="arm_affiliate_migrate_btn" type="submit"><?php _e('Migrate', 'ARM_AFFILIATE') ?></button>&nbsp;<img src="<?php echo ARM_AFFILIATE_IMAGES_URL.'/arm_loader.gif' ?>" id="arm_migrate_loader_img" style="position:relative;top:8px;display:none;" width="24" height="24" />
                    <?php wp_nonce_field( 'arm_wp_nonce' );?>
                </div>
            </form>
            <div class="armclear"></div>
         </div>
    </div>
</div>
<?php $arm_affiliate_settings->arm_affiliate_get_footer(); ?>