<?php

global $wpdb, $armnew_razorpay_version;

$arm_razorpay_version = get_option('arm_razorpay_version');
if (version_compare($arm_razorpay_version, '1.0', '<')) {
    update_option('arm_razorpay_old_version', $arm_razorpay_version);
    update_option('arm_razorpay_version', '1.0');
    $armnew_razorpay_version = '1.0';
}
