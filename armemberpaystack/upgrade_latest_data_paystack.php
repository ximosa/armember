<?php

global $wpdb, $armnew_paystack_version;

$arm_paystack_version = get_option('arm_paystack_version');
if (version_compare($arm_paystack_version, '1.7', '<')) {
    update_option('arm_paystack_old_version', $arm_paystack_version);
    update_option('arm_paystack_version', '1.7');
    $armnew_paystack_version = '1.7';
}
