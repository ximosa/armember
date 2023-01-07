<?php
if (!function_exists('arm_paypal_pro_settings')) {

    function arm_paypal_pro_settings($gateway_name, $gateway_options) {
        global $arm_global_settings;
        if ($gateway_name == 'paypal_pro') {
            $gateway_options['paypal_pro_payment_mode'] = (!empty($gateway_options['paypal_pro_payment_mode']) ) ? $gateway_options['paypal_pro_payment_mode'] : 'sandbox';
            $gateway_options['paypal_pro_payment_type'] = (!empty($gateway_options['paypal_pro_payment_type']) ) ? $gateway_options['paypal_pro_payment_type'] : 'payflow_pro';
            $gateway_options['status'] = isset($gateway_options['status']) ? $gateway_options['status'] : 0;
            $arm_status_switchChecked = ($gateway_options['status'] == '1') ? 'checked="checked"' : '';
            $disabled_field_attr = ($gateway_options['status'] == '1') ? '' : 'disabled="disabled"';
            $readonly_field_attr = ($gateway_options['status'] == '1') ? '' : 'readonly="readonly"';
            ?>
            <script type="text/javascript">
                jQuery(document).on('change', '.arm_paypal_pro_type_radio', function (e) {
                    arm_hide_show_paypal_pro_section();
                });
                jQuery(document).on('change', '.arm_paypal_pro_mode_radio', function (e) {
                    arm_hide_show_paypal_pro_section();
                });
                function arm_hide_show_paypal_pro_section() {
                    var paypal_mode_type = jQuery('.arm_paypal_pro_mode_radio:checked').val();
                    var paypal_pro_type = jQuery('.arm_paypal_pro_type_radio:checked').val();
                    if (paypal_mode_type == 'sandbox') {
                        jQuery('.arm_payflow_pro_sandbox_fields').removeClass('hidden_section');
                        jQuery('.arm_payflow_pro_fields:not(.hidden_section)').addClass('hidden_section');
                        jQuery('.arm_payments_pro_sandbox_fields:not(.hidden_section)').addClass('hidden_section');
                        jQuery('.arm_payments_pro_fields:not(.hidden_section)').addClass('hidden_section');
                    } else {
                        jQuery('.arm_payflow_pro_fields').removeClass('hidden_section');
                        jQuery('.arm_payflow_pro_sandbox_fields:not(.hidden_section)').addClass('hidden_section');
                        jQuery('.arm_payments_pro_sandbox_fields:not(.hidden_section)').addClass('hidden_section');
                        jQuery('.arm_payments_pro_fields:not(.hidden_section)').addClass('hidden_section');
                    }
                }
            </script>
            <tr class="form-field">
                <th class="arm-form-table-label"><label><?php _e('Payment Mode', ARM_PAYPALPRO_TXTDOMAIN); ?> *</label></th>
                <td class="arm-form-table-content">
                    <input id="arm_paypal_pro_payment_gateway_mode_sand" class="arm_general_input arm_paypal_pro_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="sandbox" name="payment_gateway_settings[paypal_pro][paypal_pro_payment_mode]" <?php checked($gateway_options['paypal_pro_payment_mode'], 'sandbox'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_paypal_pro_payment_gateway_mode_sand"><?php _e('Sandbox', ARM_PAYPALPRO_TXTDOMAIN); ?></label>
                    <input id="arm_paypal_pro_payment_gateway_mode_pro" class="arm_general_input arm_paypal_pro_mode_radio arm_iradio arm_active_payment_<?php echo strtolower($gateway_name); ?>" type="radio" value="live" name="payment_gateway_settings[paypal_pro][paypal_pro_payment_mode]" <?php checked($gateway_options['paypal_pro_payment_mode'], 'live'); ?> <?php echo $disabled_field_attr; ?>>
                    <label for="arm_paypal_pro_payment_gateway_mode_pro"><?php _e('Live', ARM_PAYPALPRO_TXTDOMAIN); ?></label>
                </td>
            </tr>
            <!-- ***** Begining of Sandbox Input for Payflow Pro ***** -->
            <?php
            $payflow_pro_hidden = "hidden_section";
            if (isset($gateway_options['paypal_pro_payment_mode']) && $gateway_options['paypal_pro_payment_mode'] == 'sandbox' && $gateway_options['paypal_pro_payment_type'] == 'payflow_pro') {
                $payflow_pro_hidden = "";
            }
            if (!isset($gateway_options['paypal_pro_payment_mode']) || empty($gateway_options['paypal_pro_payment_mode'])) {
                $payflow_pro_hidden = "";
            }
            ?>
            <tr class="form-field arm_payflow_pro_sandbox_fields <?php echo $payflow_pro_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Sandbox API Username', ARM_PAYPALPRO_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payflow_pro_sandbox_username" name="payment_gateway_settings[paypal_pro][payflow_pro_sandbox_username]" value="<?php echo (!empty($gateway_options['payflow_pro_sandbox_username'])) ? $gateway_options['payflow_pro_sandbox_username'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payflow_pro_sandbox_fields <?php echo $payflow_pro_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Sandbox API Password', ARM_PAYPALPRO_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payflow_pro_sandbox_password" name="payment_gateway_settings[paypal_pro][payflow_pro_sandbox_password]" value="<?php echo (!empty($gateway_options['payflow_pro_sandbox_password'])) ? $gateway_options['payflow_pro_sandbox_password'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payflow_pro_sandbox_fields <?php echo $payflow_pro_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Sandbox Vendor', ARM_PAYPALPRO_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payflow_pro_sandbox_vendor" name="payment_gateway_settings[paypal_pro][payflow_pro_sandbox_vendor]" value="<?php echo (!empty($gateway_options['payflow_pro_sandbox_vendor'])) ? $gateway_options['payflow_pro_sandbox_vendor'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payflow_pro_sandbox_fields <?php echo $payflow_pro_hidden; ?> ">
                <th class="arm-form-table-label"><?php _e('Sandbox Partner', ARM_PAYPALPRO_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payflow_pro_sandbox_partner" name="payment_gateway_settings[paypal_pro][payflow_pro_sandbox_partner]" value="<?php echo (!empty($gateway_options['payflow_pro_sandbox_partner'])) ? $gateway_options['payflow_pro_sandbox_partner'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <!-- ***** Ending of Sandbox Input for Payflow Pro ***** -->

            <!-- ***** Begining of Live Input for Payflow Pro ***** -->
            <?php
            $payflow_pro_live_fields = "hidden_section";
            if (isset($gateway_options['paypal_pro_payment_mode']) && $gateway_options['paypal_pro_payment_mode'] == "live" && $gateway_options['paypal_pro_payment_type'] == "payflow_pro") {
                $payflow_pro_live_fields = "";
            }
            ?>
            <tr class="form-field arm_payflow_pro_fields <?php echo $payflow_pro_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live API Username', ARM_PAYPALPRO_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payflow_pro_sandbox_username" name="payment_gateway_settings[paypal_pro][payflow_pro_username]" value="<?php echo (!empty($gateway_options['payflow_pro_username'])) ? $gateway_options['payflow_pro_username'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payflow_pro_fields <?php echo $payflow_pro_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live API Password', ARM_PAYPALPRO_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payflow_pro_sandbox_password" name="payment_gateway_settings[paypal_pro][payflow_pro_password]" value="<?php echo (!empty($gateway_options['payflow_pro_password'])) ? $gateway_options['payflow_pro_password'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payflow_pro_fields <?php echo $payflow_pro_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live Vendor', ARM_PAYPALPRO_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payflow_pro_sandbox_vendor" name="payment_gateway_settings[paypal_pro][payflow_pro_vendor]" value="<?php echo (!empty($gateway_options['payflow_pro_vendor'])) ? $gateway_options['payflow_pro_vendor'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <tr class="form-field arm_payflow_pro_fields <?php echo $payflow_pro_live_fields; ?> ">
                <th class="arm-form-table-label"><?php _e('Live Partner', ARM_PAYPALPRO_TXTDOMAIN); ?> *</th>
                <td class="arm-form-table-content">
                    <input type="text" class="arm_active_payment_<?php echo strtolower($gateway_name); ?>" id="arm_payflow_pro_sandbox_partner" name="payment_gateway_settings[paypal_pro][payflow_pro_partner]" value="<?php echo (!empty($gateway_options['payflow_pro_partner'])) ? $gateway_options['payflow_pro_partner'] : ''; ?>" <?php echo $readonly_field_attr; ?> />
                </td>
            </tr>
            <?php
        }
    }

}

if (!function_exists('arm_paypal_pro_common_message_settings')) {

    function arm_paypal_pro_common_message_settings($common_message) {
        ?>
        <tr class="form-field">
            <th class="arm-form-table-label"><label for="arm_unauthorized_paypal_pro_credit_card"><?php _e('Credit Card Not Authorized (Paypal Pro)', ARM_PAYPALPRO_TXTDOMAIN); ?></label></th>
            <td class="arm-form-table-content">
                <input type="text" name="arm_common_message_settings[arm_unauthorized_paypal_pro_credit_card]" id="arm_unauthorized_paypal_pro_credit_card" value="<?php echo (!empty($common_messages['arm_unauthorized_paypal_pro_credit_card'])) ? $common_messages['arm_unauthorized_paypal_pro_credit_card'] : __('Card details could not be authorized, please use other card detail.', ARM_PAYPALPRO_TXTDOMAIN); ?>"/>
            </td>
        </tr>
        <tr class="form-field">
            <th class="arm-form-table-label"><label for="arm_payment_fail_paypal_pro"><?php _e('Payment Fail (Paypal Pro)', ARM_PAYPALPRO_TXTDOMAIN); ?></th>
            <td class="arm-form-table-content">
                <input type="text" name="arm_common_message_settings[arm_payment_fail_paypal_pro]" id="arm_payment_fail_paypal_pro" value="<?php echo (!empty($common_messages['arm_payment_fail_paypal_pro']) ) ? $common_messages['arm_payment_fail_paypal_pro'] : __('Sorry something went wrong while processing payment with Paypal Pro.', ARM_PAYPALPRO_TXTDOMAIN); ?>" />
            </td>
        </tr>
        <?php
    }

}