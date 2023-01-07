<?php

global $wpdb, $arm_direct_logins_newdbversion;


$arm_direct_logins_version = get_option('arm_direct_logins_version');
if (version_compare($arm_direct_logins_version, '1.8', '<')) {
    update_option('arm_direct_logins_old_version', $arm_direct_logins_version);
    update_option('arm_direct_logins_version', '1.8');
    $arm_direct_logins_newdbversion = '1.8';
}
