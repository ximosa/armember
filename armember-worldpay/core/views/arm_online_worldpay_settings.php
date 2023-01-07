<?php
if (!function_exists('arm_online_worldpay_settings')) {

    function arm_online_worldpay_settings($gateway_name, $gateway_options) {
        global $arm_global_settings;
        
        if ($gateway_name == 'online_worldpay') {
            $gateway_options['online_worldpay_payment_mode'] = (!empty($gateway_options['online_worldpay_payment_mode']) ) ? $gateway_options['online_worldpay_payment_mode'] : 'sandbox';
            $gateway_options['status'] = isset($gateway_options['status']) ? $gateway_options['status'] : 0;
            $arm_status_switchChecked = ($gateway_options['status'] == '1') ? 'checked="checked"' : '';
            $disabled_field_attr = ($gateway_options['status'] == '1') ? '' : 'disabled="disabled"';
            $readonly_field_attr = ($gateway_options['status'] == '1') ? '' : 'readonly="readonly"';
            ?>
            
            <tr class="form-field">
                <th class="arm-form-table-label"><label><?php echo esc_html__('Payment Mode', ARM_WORLDPAY_TXTDOMAIN); ?> *</label></th>
                <td class="arm-form-table-content">
                    <input id="arm_online_worldpay_payment_gateway_mode_sand" class="arm_general_input arm_online_worldpay_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="sandbox" name="payment_gateway_settings[online_worldpay][online_worldpay_payment_mode]" <?php checked($gateway_options['online_worldpay_payment_mode'], 'sandbox'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_online_worldpay_payment_gateway_mode_sand"><?php echo esc_html__('Sandbox', ARM_WORLDPAY_TXTDOMAIN); ?></label>
                    <input id="arm_online_worldpay_payment_gateway_mode_pro" class="arm_general_input arm_online_worldpay_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="live" name="payment_gateway_settings[online_worldpay][online_worldpay_payment_mode]" <?php checked($gateway_options['online_worldpay_payment_mode'], 'live'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_online_worldpay_payment_gateway_mode_pro"><?php echo esc_html__('Live', ARM_WORLDPAY_TXTDOMAIN); ?></label>
                </td>
            </tr>
            <!-- ***** Begining of Sandbox Input for Online Worldpay ***** -->
            <?php
            $online_worldpay_hidden = "hidden_section";
            if (isset($gateway_options['online_worldpay_payment_mode']) && $gateway_options['online_worldpay_payment_mode'] == 'sandbox') {
                $online_worldpay_hidden = "";
            }
            if (!isset($gateway_options['online_worldpay_payment_mode']) || empty($gateway_options['online_worldpay_payment_mode'])) {
                $online_worldpay_hidden = "";
            }
            ?>
            <tr class="form-field arm_online_worldpay_sandbox_fields <?php echo $online_worldpay_hidden; ?> ">
                <th class="arm-form-table-label"><?php echo esc_html__('Test Service key', ARM_WORLDPAY_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_online_worldpay_sandbox_service_key" name="payment_gateway_settings[online_worldpay][online_worldpay_sandbox_service_key]" value="<?php echo (!empty($gateway_options['online_worldpay_sandbox_service_key'])) ? $gateway_options['online_worldpay_sandbox_service_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_online_worldpay_sandbox_fields <?php echo $online_worldpay_hidden; ?> ">
                <th class="arm-form-table-label"><?php echo esc_html__('Test Client key', ARM_WORLDPAY_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_online_worldpay_sandbox_client_key" name="payment_gateway_settings[online_worldpay][online_worldpay_sandbox_client_key]" value="<?php echo (!empty($gateway_options['online_worldpay_sandbox_client_key'])) ? $gateway_options['online_worldpay_sandbox_client_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            
            <!-- ***** Ending of Sandbox Input for Online Worldpay ***** -->

            <!-- ***** Begining of Live Input for Online Worldpay ***** -->
            <?php
            $online_worldpay_live_fields = "hidden_section";
            if (isset($gateway_options['online_worldpay_payment_mode']) && $gateway_options['online_worldpay_payment_mode'] == "live") {
                $online_worldpay_live_fields = "";
            }
            ?>
            <tr class="form-field arm_online_worldpay_fields <?php echo $online_worldpay_live_fields; ?> ">
                <th class="arm-form-table-label"><?php echo esc_html__('Live Service key', ARM_WORLDPAY_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_online_worldpay_service_key" name="payment_gateway_settings[online_worldpay][online_worldpay_service_key]" value="<?php echo (!empty($gateway_options['online_worldpay_service_key'])) ? $gateway_options['online_worldpay_service_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_online_worldpay_fields <?php echo $online_worldpay_live_fields; ?> ">
                <th class="arm-form-table-label"><?php echo esc_html__('Live Client key', ARM_WORLDPAY_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_online_worldpay_client_key" name="payment_gateway_settings[online_worldpay][online_worldpay_client_key]" value="<?php echo (!empty($gateway_options['online_worldpay_client_key'])) ? $gateway_options['online_worldpay_client_key'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <?php
        }
    }

}

if (!function_exists('arm_online_worldpay_common_message_settings')) {

    function arm_online_worldpay_common_message_settings($common_message) {
        ?>
        <tr class="form-field">
            <th class="arm-form-table-label"><label for="arm_unauthorized_online_worldpay_credit_card"><?php echo esc_html__('Credit Card Not Authorized (Online Worldpay)', ARM_WORLDPAY_TXTDOMAIN); ?></label></th>
            <td class="arm-form-table-content">
                <input type="text" name="arm_common_message_settings[arm_unauthorized_online_worldpay_credit_card]" id="arm_unauthorized_online_worldpay_credit_card" value="<?php echo (!empty($common_messages['arm_unauthorized_online_worldpay_credit_card'])) ? $common_messages['arm_unauthorized_online_worldpay_credit_card'] : esc_html__('Card details could not be authorized, please use other card detail.', ARM_WORLDPAY_TXTDOMAIN); ?>"/>
            </td>
        </tr>
        <tr class="form-field">
            <th class="arm-form-table-label"><label for="arm_payment_fail_online_worldpay"><?php echo esc_html__('Payment Fail (Online Worldpay)', ARM_WORLDPAY_TXTDOMAIN); ?></th>
            <td class="arm-form-table-content">
                <input type="text" name="arm_common_message_settings[arm_payment_fail_online_worldpay]" id="arm_payment_fail_online_worldpay" value="<?php echo (!empty($common_messages['arm_payment_fail_online_worldpay']) ) ? $common_messages['arm_payment_fail_online_worldpay'] : esc_html__('Sorry something went wrong while processing payment with Online Worldpay.', ARM_WORLDPAY_TXTDOMAIN); ?>" />
            </td>
        </tr>
        <?php
    }

}