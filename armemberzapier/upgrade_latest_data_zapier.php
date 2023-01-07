<?php

global $wpdb, $armnew_zapier_version;

$arm_zapier_version = get_option('arm_zapier_version');
if (version_compare($arm_zapier_version, '1.7', '<')) {
    update_option('arm_zapier_old_version', $arm_zapier_version);
    update_option('arm_zapier_version', '1.7');
    $armnew_zapier_version = '1.7';
}