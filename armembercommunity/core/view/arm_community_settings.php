<?php
global $arm_community_setting;
$arm_com_settings = $arm_community_setting->arm_com_settings;
$arm_com_friendship = isset($arm_com_settings['arm_com_friendship']) ? $arm_com_settings['arm_com_friendship'] : '';
$arm_friend_section_lbl = isset($arm_com_settings['arm_friend_section_lbl']) ? $arm_com_settings['arm_friend_section_lbl'] : '';
$arm_no_friend_requests_msg = isset($arm_com_settings['arm_no_friend_requests_msg']) ? $arm_com_settings['arm_no_friend_requests_msg'] : '';
$arm_no_friend_msg = isset($arm_com_settings['arm_no_friend_msg']) ? $arm_com_settings['arm_no_friend_msg'] : '';
$arm_send_friend_request_lbl = isset($arm_com_settings['arm_send_friend_request_lbl']) ? $arm_com_settings['arm_send_friend_request_lbl'] : '';
$arm_cancel_friend_request_lbl = isset($arm_com_settings['arm_cancel_friend_request_lbl']) ? $arm_com_settings['arm_cancel_friend_request_lbl'] : '';
$arm_accept_friend_request_lbl = isset($arm_com_settings['arm_accept_friend_request_lbl']) ? $arm_com_settings['arm_accept_friend_request_lbl'] : '';
$arm_unfriend_lbl = isset($arm_com_settings['arm_unfriend_lbl']) ? $arm_com_settings['arm_unfriend_lbl'] : '';
$arm_current_friends_lbl = isset($arm_com_settings['arm_current_friends_lbl']) ? $arm_com_settings['arm_current_friends_lbl'] : '';
$arm_friend_requests_lbl = isset($arm_com_settings['arm_friend_requests_lbl']) ? $arm_com_settings['arm_friend_requests_lbl'] : '';
$arm_public_friends = isset($arm_com_settings['arm_public_friends']) ? $arm_com_settings['arm_public_friends'] : '';
$arm_friend_error_msg = isset($arm_com_settings['arm_friend_error_msg']) ? $arm_com_settings['arm_friend_error_msg'] : '';
$arm_com_private_message = isset($arm_com_settings['arm_com_private_message']) ? $arm_com_settings['arm_com_private_message'] : '';
$arm_message_only_friends = isset($arm_com_settings['arm_message_only_friends']) ? $arm_com_settings['arm_message_only_friends'] : '';
$arm_msg_section_lbl = isset($arm_com_settings['arm_msg_section_lbl']) ? $arm_com_settings['arm_msg_section_lbl'] : '';
$arm_msg_username_lbl = isset($arm_com_settings['arm_msg_username_lbl']) ? $arm_com_settings['arm_msg_username_lbl'] : '';
$arm_msg_msg_lbl = isset($arm_com_settings['arm_msg_msg_lbl']) ? $arm_com_settings['arm_msg_msg_lbl'] : '';
$arm_blank_field_msg = isset($arm_com_settings['arm_blank_field_msg']) ? $arm_com_settings['arm_blank_field_msg'] : '';
$arm_invalid_field_msg = isset($arm_com_settings['arm_invalid_field_msg']) ? $arm_com_settings['arm_invalid_field_msg'] : '';
$arm_msg_blocked_msg = isset($arm_com_settings['arm_msg_blocked_msg']) ? $arm_com_settings['arm_msg_blocked_msg'] : '';
$arm_msg_not_frnd_msg = isset($arm_com_settings['arm_msg_not_frnd_msg']) ? $arm_com_settings['arm_msg_not_frnd_msg'] : '';
$arm_msg_success_msg = isset($arm_com_settings['arm_msg_success_msg']) ? $arm_com_settings['arm_msg_success_msg'] : '';
$arm_com_follow = isset($arm_com_settings['arm_com_follow']) ? $arm_com_settings['arm_com_follow'] : 0;
$arm_follow_btn_txt = isset($arm_com_settings['arm_follow_btn_txt']) ? $arm_com_settings['arm_follow_btn_txt'] : '';
$arm_unfollow_btn_txt = isset($arm_com_settings['arm_unfollow_btn_txt']) ? $arm_com_settings['arm_unfollow_btn_txt'] : '';
$arm_followers_lbl = isset($arm_com_settings['arm_followers_lbl']) ? $arm_com_settings['arm_followers_lbl'] : '';
$arm_following_lbl = isset($arm_com_settings['arm_following_lbl']) ? $arm_com_settings['arm_following_lbl'] : '';
$arm_keep_reviews_public = isset($arm_com_settings['arm_keep_reviews_public']) ? $arm_com_settings['arm_keep_reviews_public'] : '';
$arm_com_review = isset($arm_com_settings['arm_com_review']) ? $arm_com_settings['arm_com_review'] : '';
$arm_com_post = isset($arm_com_settings['arm_com_post']) ? $arm_com_settings['arm_com_post'] : '';
$arm_com_activity = isset($arm_com_settings['arm_com_activity']) ? $arm_com_settings['arm_com_activity'] : '';
$arm_keep_activity_public = isset($arm_com_settings['arm_keep_activity_public']) ? $arm_com_settings['arm_keep_activity_public'] : '';
$social_feature = get_option('arm_is_social_feature');
$arm_com_review_approval = isset($arm_com_settings['arm_com_review_approval']) ? $arm_com_settings['arm_com_review_approval'] : 'auto_approve';
$arm_review_approved_by_admin = isset($arm_com_settings['arm_review_approved_by_admin']) ? $arm_com_settings['arm_review_approved_by_admin'] : 0;
$arm_review_editable = isset($arm_com_settings['arm_review_editable']) ? $arm_com_settings['arm_review_editable'] : 0;
$arm_record_per_page = isset($arm_com_settings['arm_record_per_page']) ? $arm_com_settings['arm_record_per_page'] : 10;
$arm_com_pagination_style = isset($arm_com_settings['arm_com_pagination_style']) ? $arm_com_settings['arm_com_pagination_style'] : 'more_link';
$arm_com_post_fimage = isset($arm_com_settings['arm_com_post_fimage']) ? $arm_com_settings['arm_com_post_fimage'] : 0;
$arm_com_post_like = isset($arm_com_settings['arm_com_post_like']) ? $arm_com_settings['arm_com_post_like'] : 0;
$arm_com_post_comment = isset($arm_com_settings['arm_com_post_comment']) ? $arm_com_settings['arm_com_post_comment'] : 0;
$arm_com_post_wall = isset($arm_com_settings['arm_com_post_wall']) ? $arm_com_settings['arm_com_post_wall'] : 0;
$arm_review_section_lbl = isset($arm_com_settings['arm_review_section_lbl']) ? $arm_com_settings['arm_review_section_lbl'] : __("Review", ARM_COMMUNITY_TEXTDOMAIN);
$arm_post_section_lbl = isset($arm_com_settings['arm_post_section_lbl']) ? $arm_com_settings['arm_post_section_lbl'] : __("Post", ARM_COMMUNITY_TEXTDOMAIN);
$arm_activity_section_lbl = isset($arm_com_settings['arm_activity_section_lbl']) ? $arm_com_settings['arm_activity_section_lbl'] : __("Activity", ARM_COMMUNITY_TEXTDOMAIN);
$arm_wall_section_lbl = isset($arm_com_settings['arm_wall_section_lbl']) ? $arm_com_settings['arm_wall_section_lbl'] : __("News Feed", ARM_COMMUNITY_TEXTDOMAIN);
$arm_profile_section_lbl = isset($arm_com_settings['arm_profile_section_lbl']) ? $arm_com_settings['arm_profile_section_lbl'] : __("Profile", ARM_COMMUNITY_TEXTDOMAIN);

$arm_com_email_friend_request = isset($arm_com_settings['arm_com_email_friend_request']) ? $arm_com_settings['arm_com_email_friend_request'] : 0;
$arm_com_email_friend_accept_request = isset($arm_com_settings['arm_com_email_friend_accept_request']) ? $arm_com_settings['arm_com_email_friend_accept_request'] : 0;
$arm_com_email_received_new_private_message = isset($arm_com_settings['arm_com_email_received_new_private_message']) ? $arm_com_settings['arm_com_email_received_new_private_message'] : 0;
$arm_com_email_someone_followed_you = isset($arm_com_settings['arm_com_email_someone_followed_you']) ? $arm_com_settings['arm_com_email_someone_followed_you'] : 0;
$arm_com_email_friend_liked_your_post = isset($arm_com_settings['arm_com_email_friend_liked_your_post']) ? $arm_com_settings['arm_com_email_friend_liked_your_post'] : 0;
$arm_com_email_friend_commented_on_your_post = isset($arm_com_settings['arm_com_email_friend_commented_on_your_post']) ? $arm_com_settings['arm_com_email_friend_commented_on_your_post'] : 0;

$arm_com_post_slug = isset($arm_com_settings['arm_com_post_slug']) ? $arm_com_settings['arm_com_post_slug'] : 'arm_community';
?>
<div class="wrap arm_page arm_com_settings_main_wrapper">
    <div class="content_wrapper arm_com_settings_content" id="content_wrapper">
        <form method="post" action="#" id="arm_com_settings" name="arm_com_settings" class="arm_com_settings arm_admin_form" onsubmit="return false;">
            <div class="page_title"><?php _e('Community Settings', ARM_COMMUNITY_TEXTDOMAIN); ?></div>

            <div class="arm_com_settings_wrapper">
                <div class="arm_solid_divider"></div>
                <?php if(empty($social_feature)) { ?>
                <p class="notice notice-error arm_com_social_feature_active_notice"><?php echo __('ARMember Social Community Addon Require', ARM_COMMUNITY_TEXTDOMAIN) . ' <b>' . __('Social Feature', ARM_COMMUNITY_TEXTDOMAIN) . '</b> ' . __('Active From ARMember', ARM_COMMUNITY_TEXTDOMAIN) . ' -> <a href="'.admin_url('admin.php?page=arm_feature_settings').'">' . __('Add-ons', ARM_COMMUNITY_TEXTDOMAIN) . '</a> ' . __('Page', ARM_COMMUNITY_TEXTDOMAIN); ?>.</p>
                <?php } ?>
                <table class="form-table">
                    <tr class="form-field">
                        <th class="arm-form-table-label"><?php _e('Profile Section Label', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                        <td class="arm-form-table-content">
                            <input id="arm_profile_section_lbl" class="arm_member_form_input arm_profile_section_lbl" type="text" name="arm_profile_section_lbl" value="<?php echo $arm_profile_section_lbl; ?>"/>
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th class="arm-form-table-label"><?php _e('Allow Friendship', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_friendship" <?php checked($arm_com_friendship, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_friendship" />
                                <label for="arm_com_friendship" class="armswitch_label"></label>
                            </div>
                            <span class="arm_info_text" style="margin: 10px 0 0; display:block;">(<?php _e('Enable if you want that user can send friend request to other user.', ARM_COMMUNITY_TEXTDOMAIN); ?>)</span>

                            <table class="form-table arm_com_friendship_section <?php if (!checked($arm_com_friendship, '1', false)) { echo 'hidden_section'; } ?>">
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Section Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_friend_section_lbl" class="arm_member_form_input arm_friend_section_lbl" type="text" name="arm_friend_section_lbl" value="<?php echo $arm_friend_section_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Send Friend Request Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_send_friend_request_lbl" class="arm_member_form_input arm_send_friend_request_lbl" type="text" name="arm_send_friend_request_lbl" value="<?php echo $arm_send_friend_request_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Cancel Friend Request Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_cancel_friend_request_lbl" class="arm_member_form_input arm_cancel_friend_request_lbl" type="text" name="arm_cancel_friend_request_lbl" value="<?php echo $arm_cancel_friend_request_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Accept Friend Request Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_accept_friend_request_lbl" class="arm_member_form_input arm_accept_friend_request_lbl" type="text" name="arm_accept_friend_request_lbl" value="<?php echo $arm_accept_friend_request_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Unfriend Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_unfriend_lbl" class="arm_member_form_input arm_unfriend_lbl" type="text" name="arm_unfriend_lbl" value="<?php echo $arm_unfriend_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Current Friends Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_current_friends_lbl" class="arm_member_form_input arm_current_friends_lbl" type="text" name="arm_current_friends_lbl" value="<?php echo $arm_current_friends_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Friends Requests Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_friend_requests_lbl" class="arm_member_form_input arm_friend_requests_lbl" type="text" name="arm_friend_requests_lbl" value="<?php echo $arm_friend_requests_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('No Friends Requests Message', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_no_friend_requests_msg" class="arm_member_form_input arm_no_friend_requests_msg" type="text" name="arm_no_friend_requests_msg" value="<?php echo $arm_no_friend_requests_msg; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('No Friends Message', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_no_friend_msg" class="arm_member_form_input arm_no_friend_msg" type="text" name="arm_no_friend_msg" value="<?php echo $arm_no_friend_msg; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Error Message', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_friend_error_msg" class="arm_member_form_input arm_friend_error_msg" type="text" name="arm_friend_error_msg" value="<?php echo $arm_friend_error_msg; ?>"/>
                                       <br/> <span class="remained_login_attempts_notice"><b>[code]</b> = <?php _e('Approve/Send/Cancel',ARM_COMMUNITY_TEXTDOMAIN); ?></span>

                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Display friends to other users', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <div class="armswitch arm_global_setting_switch">
                                            <input type="checkbox" id="arm_public_friends" <?php checked($arm_public_friends, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_public_friends" />
                                            <label for="arm_public_friends" class="armswitch_label"></label>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label"><?php _e('Allow Private Messaging', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_private_message" <?php checked($arm_com_private_message, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_private_message" />
                                <label for="arm_com_private_message" class="armswitch_label"></label>
                            </div>

                            <span class="arm_info_text" style="margin: 10px 0 0; display:block;">(<?php _e('Enable if you want that user can send message to each other.', ARM_COMMUNITY_TEXTDOMAIN); ?>)</span>

                            <table class="form-table arm_com_message_section <?php if (!checked($arm_com_private_message, '1', false)) {echo 'hidden_section';} ?>">
                            <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Section Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_msg_section_lbl" class="arm_member_form_input arm_msg_section_lbl" type="text" name="arm_msg_section_lbl" value="<?php echo $arm_msg_section_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Username Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_msg_username_lbl" class="arm_member_form_input arm_msg_username_lbl" type="text" name="arm_msg_username_lbl" value="<?php echo $arm_msg_username_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Message Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_msg_msg_lbl" class="arm_member_form_input arm_msg_msg_lbl" type="text" name="arm_msg_msg_lbl" value="<?php echo $arm_msg_msg_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Blank Field Message', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_blank_field_msg" class="arm_member_form_input arm_blank_field_msg" type="text" name="arm_blank_field_msg" value="<?php echo $arm_blank_field_msg; ?>"/>
                                        <br/> <span class="remained_login_attempts_notice"><b>[label]</b> = <?php _e('Field Label',ARM_COMMUNITY_TEXTDOMAIN); ?></span>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Invalid Field Message', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_invalid_field_msg" class="arm_member_form_input arm_invalid_field_msg" type="text" name="arm_invalid_field_msg" value="<?php echo $arm_invalid_field_msg; ?>"/>
                                        <br/> <span class="remained_login_attempts_notice"><b>[label]</b> = <?php _e('Field Label',ARM_COMMUNITY_TEXTDOMAIN); ?></span>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Success Message', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_msg_success_msg" class="arm_member_form_input arm_msg_success_msg" type="text" name="arm_msg_success_msg" value="<?php echo $arm_msg_success_msg; ?>"/>
                                        
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('User is not Friend', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_msg_not_frnd_msg" class="arm_member_form_input arm_msg_not_frnd_msg" type="text" name="arm_msg_not_frnd_msg" value="<?php echo $arm_msg_not_frnd_msg; ?>"/>
                                        
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('User is blocked message', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_msg_blocked_msg" class="arm_member_form_input arm_msg_blocked_msg" type="text" name="arm_msg_blocked_msg" value="<?php echo $arm_msg_blocked_msg; ?>"/>
                                        
                                    </td>
                                </tr>

                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Allow Messaging Only With Friends', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <div class="armswitch arm_global_setting_switch">
                                            <input type="checkbox" id="arm_message_only_friends" <?php checked($arm_message_only_friends, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_message_only_friends" />
                                            <label for="arm_message_only_friends" class="armswitch_label"></label>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label"><?php _e('Allow Follow / Unfollow', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_follow" <?php checked($arm_com_follow, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_follow" />
                                <label for="arm_com_follow" class="armswitch_label"></label>
                            </div>

                            <span class="arm_info_text" style="margin: 10px 0 0; display:block;">(<?php _e('Enable if you want that user can follow / unfollow to other user.', ARM_COMMUNITY_TEXTDOMAIN); ?>)</span>

                            <table class="form-table arm_com_follow_section <?php if (!checked($arm_com_follow, '1', false)) { echo 'hidden_section';} ?>">
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Follow Button Text', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_follow_btn_txt" class="arm_member_form_input arm_follow_btn_txt" type="text" name="arm_follow_btn_txt" value="<?php echo $arm_follow_btn_txt; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Unfollow Button Text', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_unfollow_btn_txt" class="arm_member_form_input arm_unfollow_btn_txt" type="text" name="arm_unfollow_btn_txt" value="<?php echo $arm_unfollow_btn_txt; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Followers Label', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_followers_lbl" class="arm_member_form_input arm_followers_lbl" type="text" name="arm_followers_lbl" value="<?php echo $arm_followers_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Following Label', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_following_lbl" class="arm_member_form_input arm_following_lbl" type="text" name="arm_following_lbl" value="<?php echo $arm_following_lbl; ?>"/>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label"><?php _e('Allow Review', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_review" <?php checked($arm_com_review, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_review" />
                                <label for="arm_com_review" class="armswitch_label"></label>
                            </div>
                         
                            <span class="arm_info_text" style="margin: 10px 0 0; display:block;">(<?php _e('Enable if you want that user can give review on other user profile.', ARM_COMMUNITY_TEXTDOMAIN); ?>)</span>

                            <table class="form-table arm_com_review_section <?php if (!checked($arm_com_review, '1', false)) echo 'hidden_section'; ?>">
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Section Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_review_section_lbl" class="arm_member_form_input arm_review_section_lbl" type="text" name="arm_review_section_lbl" value="<?php echo $arm_review_section_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Keep Reviews Public', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <div class="armswitch arm_global_setting_switch">
                                            <input type="checkbox" id="arm_keep_reviews_public" <?php checked($arm_keep_reviews_public, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_keep_reviews_public" />
                                            <label for="arm_keep_reviews_public" class="armswitch_label"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Review Approved By Admin', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <div class="armswitch arm_global_setting_switch">
                                            <input type="checkbox" id="arm_review_approved_by_admin" <?php checked($arm_review_approved_by_admin, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_review_approved_by_admin" />
                                            <label for="arm_review_approved_by_admin" class="armswitch_label"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Allow User To Change Their Submitted Review', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <div class="armswitch arm_global_setting_switch">
                                            <input type="checkbox" id="arm_review_editable" <?php checked($arm_review_editable, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_review_editable" />
                                            <label for="arm_review_editable" class="armswitch_label"></label>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label"><?php _e('Allow Post', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_post" <?php checked($arm_com_post, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_post" />
                                <label for="arm_com_post" class="armswitch_label"></label>
                            </div>
                            <span class="arm_info_text" style="margin: 10px 0 0; display:block;">(<?php _e('Enable if you want that user can add his posts in his profile.', ARM_COMMUNITY_TEXTDOMAIN); ?>)</span>

                            <table class="form-table arm_com_post_fimage_section <?php if (!checked($arm_com_post, '1', false)) echo 'hidden_section'; ?>">
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Section Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_post_section_lbl" class="arm_member_form_input arm_post_section_lbl" type="text" name="arm_post_section_lbl" value="<?php echo $arm_post_section_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Post Slug', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_com_post_slug" class="arm_member_form_input arm_com_post_slug" type="text" name="arm_com_post_slug" value="<?php echo $arm_com_post_slug; ?>"/>
                                        <span class="arm_info_text" style="margin: 10px 0 0; display:block;">(<?php _e('Recommend to use alphabets only', ARM_COMMUNITY_TEXTDOMAIN); ?>)</span>
                                        <span id="arm_com_post_slug_error" class="arm_error_msg arm_com_post_slug_error"><?php _e("Please enter post slug", ARM_COMMUNITY_TEXTDOMAIN); ?></span>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Allow User to Set Featured Image', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <div class="armswitch arm_global_setting_switch">
                                            <input type="checkbox" id="arm_com_post_fimage" <?php checked($arm_com_post_fimage, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_post_fimage" />
                                            <label for="arm_com_post_fimage" class="armswitch_label"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Allow User to Like Post', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <div class="armswitch arm_global_setting_switch">
                                            <input type="checkbox" id="arm_com_post_like" <?php checked($arm_com_post_like, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_post_like" />
                                            <label for="arm_com_post_like" class="armswitch_label"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Allow User to Add Comment on Post', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <div class="armswitch arm_global_setting_switch">
                                            <input type="checkbox" id="arm_com_post_comment" <?php checked($arm_com_post_comment, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_post_comment" />
                                            <label for="arm_com_post_comment" class="armswitch_label"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Allow User News Feed', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <div class="armswitch arm_global_setting_switch">
                                            <input type="checkbox" id="arm_com_post_wall" <?php checked($arm_com_post_wall, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_post_wall" />
                                            <label for="arm_com_post_wall" class="armswitch_label"></label>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="form-field arm_com_wll_section_lbl_tr <?php if (!checked($arm_com_post_wall, '1', false)) echo 'hidden_section'; ?>">
                                    <th class="arm-form-table-label">
                                        <?php _e('User News Feed Section Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_wall_section_lbl" class="arm_member_form_input arm_wall_section_lbl" type="text" name="arm_wall_section_lbl" value="<?php echo $arm_wall_section_lbl; ?>"/>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label"><?php _e('Allow Activity', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_activity" <?php checked($arm_com_activity, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_activity" />
                                <label for="arm_com_activity" class="armswitch_label"></label>
                            </div>
                            <span class="arm_info_text" style="margin: 10px 0 0; display:block;">(<?php _e('Enable if you want to show user\'s activity.', ARM_COMMUNITY_TEXTDOMAIN); ?>)</span>

                            <table class="form-table arm_com_activity_section <?php if (!checked($arm_com_activity, '1', false)) echo 'hidden_section'; ?>">
                                <tr class="form-field">
                                    <th class="arm-form-table-label">
                                        <?php _e('Section Label', ARM_COMMUNITY_TEXTDOMAIN); ?>
                                    </th>
                                    <td class="arm-form-table-content">
                                        <input id="arm_activity_section_lbl" class="arm_member_form_input arm_activity_section_lbl" type="text" name="arm_activity_section_lbl" value="<?php echo $arm_activity_section_lbl; ?>"/>
                                    </td>
                                </tr>
                                <tr class="form-field">
                                    <th class="arm-form-table-label"><?php _e('Show Activity To All User', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                                    <td class="arm-form-table-content">
                                        <div class="armswitch arm_global_setting_switch">
                                            <input type="checkbox" id="arm_keep_activity_public" <?php checked($arm_keep_activity_public, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_keep_activity_public" />
                                            <label for="arm_keep_activity_public" class="armswitch_label"></label>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label"><?php _e('No. of Records per Page', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                        <td class="arm-form-table-content">
                            <input id="arm_record_per_page" class="arm_member_form_input arm_record_per_page" type="text" name="arm_record_per_page" value="<?php echo $arm_record_per_page; ?>"/>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label"><?php _e('Pagination Style', ARM_COMMUNITY_TEXTDOMAIN); ?></th>
                        <td class="arm-form-table-content">
                            <input type="radio" id="arm_com_numeric" class="arm_iradio" name="arm_com_pagination_style" value="numeric" <?php checked($arm_com_pagination_style, 'numeric');?> />
                            <label for="arm_com_numeric" class="arm_email_settings_help_text"><?php _e('Numeric',ARM_COMMUNITY_TEXTDOMAIN);?></label>
                            <input type="radio" id="arm_com_more_link" class="arm_iradio" name="arm_com_pagination_style" value="more_link" <?php checked($arm_com_pagination_style, 'more_link');?> />
                            <label for="arm_com_more_link" class="arm_email_settings_help_text"><?php _e('Load More Link',ARM_COMMUNITY_TEXTDOMAIN);?></label>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label" style="padding-right: 0"><h3 style="font-size: 16px;"><?php _e('Email Notification Settings', ARM_COMMUNITY_TEXTDOMAIN); ?> <i class="arm_helptip_icon armfa armfa-question-circle" title="<?php _e("You can add/edit Email content from ARMember -> Email Notification -> Add New Response button.", ARM_COMMUNITY_TEXTDOMAIN); ?>"></i></h3></th>
                        <!-- <td>&nbsp;</td> -->
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On Friend Request Received', ARM_COMMUNITY_TEXTDOMAIN); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_email_friend_request" <?php checked($arm_com_email_friend_request, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_email_friend_request" />
                                <label for="arm_com_email_friend_request" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On Accepted Friend Request', ARM_COMMUNITY_TEXTDOMAIN); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_email_friend_accept_request" <?php checked($arm_com_email_friend_accept_request, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_email_friend_accept_request" />
                                <label for="arm_com_email_friend_accept_request" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On Received New Private message', ARM_COMMUNITY_TEXTDOMAIN); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_email_received_new_private_message" <?php checked($arm_com_email_received_new_private_message, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_email_received_new_private_message" />
                                <label for="arm_com_email_received_new_private_message" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On Someone Followed you', ARM_COMMUNITY_TEXTDOMAIN); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_email_someone_followed_you" <?php checked($arm_com_email_someone_followed_you, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_email_someone_followed_you" />
                                <label for="arm_com_email_someone_followed_you" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On Someone Liked your Post', ARM_COMMUNITY_TEXTDOMAIN); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_email_friend_liked_your_post" <?php checked($arm_com_email_friend_liked_your_post, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_email_friend_liked_your_post" />
                                <label for="arm_com_email_friend_liked_your_post" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>

                    <tr class="form-field">
                        <th class="arm-form-table-label">
                            <?php _e('On Friend Commented on your Post', ARM_COMMUNITY_TEXTDOMAIN); ?>
                        </th>
                        <td class="arm-form-table-content">
                            <div class="armswitch arm_global_setting_switch">
                                <input type="checkbox" id="arm_com_email_friend_commented_on_your_post" <?php checked($arm_com_email_friend_commented_on_your_post, '1'); ?> value="1" class="armswitch_input arm_com_switch" name="arm_com_email_friend_commented_on_your_post" />
                                <label for="arm_com_email_friend_commented_on_your_post" class="armswitch_label"></label>
                            </div>
                        </td>
                    </tr>
                </table>

                <div class="arm_submit_btn_container">
                    <button id="arm_com_settings_btn" class="arm_save_btn" name="arm_settings_btn" type="submit"><?php _e('Save', ARM_COMMUNITY_TEXTDOMAIN) ?></button>&nbsp;<img src="<?php echo ARM_COMMUNITY_IMAGES_URL . 'arm_loader.gif' ?>" id="arm_loader_img" style="position:relative;top:8px;display:none;" width="24" height="24" />
                </div>
                <?php wp_nonce_field( 'arm_wp_nonce' );?>
                <div class="armclear"></div>
            </div>
        </form>
    </div>
</div>
<?php $arm_community_setting->arm_community_get_footer(); ?>