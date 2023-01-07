<?php

global $wpdb, $armnew_pagseguro_version;

$arm_pagseguro_version = get_option('arm_pagseguro_version');
if (version_compare($arm_pagseguro_version, '1.5', '<')) {
    update_option('arm_pagseguro_old_version', $arm_pagseguro_version);
    update_option('arm_pagseguro_version', '1.5');
    $armnew_pagseguro_version = '1.5';
}
