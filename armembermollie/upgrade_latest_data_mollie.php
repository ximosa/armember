<?php

global $wpdb, $armnew_mollie_version;

$arm_mollie_version = get_option('arm_mollie_version');
if (version_compare($arm_mollie_version, '2.3', '<')) {
    update_option('arm_mollie_old_version', $arm_mollie_version);
    update_option('arm_mollie_version', '2.3');
    $armnew_mollie_version = '2.3';
}