<?php

global $wpdb, $arm_social_community_newdbversion;


$arm_community_version = get_option('arm_community_version');
if (version_compare($arm_community_version, '1.5', '<')) {
    update_option('arm_community_old_version', $arm_community_version);
    update_option('arm_community_version', '1.5');
    $arm_social_community_newdbversion = '1.5';
}
