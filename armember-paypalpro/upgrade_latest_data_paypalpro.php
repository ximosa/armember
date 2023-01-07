<?php

global $wpdb, $armnew_paypalpro_version;

$armnew_paypalpro_version = get_option('arm_paypalpro_version');
if (version_compare($armnew_paypalpro_version, '1.9', '<')) {
    update_option('arm_paypalpro_old_version', $armnew_paypalpro_version);
    update_option('arm_paypalpro_version', '1.9');
    $armnew_paypalpro_version = '1.9';
}