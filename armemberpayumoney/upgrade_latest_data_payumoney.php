<?php

global $wpdb, $armnew_payumoney_version;

$arm_payumoney_version = get_option('arm_payumoney_version');
if (version_compare($arm_payumoney_version, '1.5', '<')) {
    update_option('arm_payumoney_old_version', $arm_payumoney_version);
    update_option('arm_payumoney_version', '1.5');
    $armnew_payumoney_version = '1.5';
}
