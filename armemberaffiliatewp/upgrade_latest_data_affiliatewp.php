<?php

global $wpdb, $armnew_affiliatewp_version;

$arm_affiliatewp_version = get_option('arm_affiliatewp_version');
if (version_compare($arm_affiliatewp_version, '1.2', '<')) {
    update_option('arm_affiliatewp_old_version', $arm_affiliatewp_version);
    update_option('arm_affiliatewp_version', '1.2');
    $armnew_affiliatewp_version = '1.2';
}
