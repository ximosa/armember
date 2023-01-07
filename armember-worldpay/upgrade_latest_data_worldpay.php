<?php

global $wpdb, $armnew_worldpay_version;

$armnew_worldpay_version = get_option('arm_worldpay_version');
if (version_compare($armnew_worldpay_version, '1.1', '<')) {
    update_option('arm_worldpay_old_version', $armnew_worldpay_version);
    update_option('arm_worldpay_version', '1.1');
    $armnew_worldpay_version = '1.1';
}
