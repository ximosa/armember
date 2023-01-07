<div class="arm_solid_divider"></div>
<?php
$arm_infusionsoft_appname = get_option('arm_infusionsoft_appname');
$arm_infusionsoft_apikey = get_option('arm_infusionsoft_apikey');
?>
<table class="form-table">
    <tr class="form-field">
        <td colspan="2"><img src="<?php echo ARM_INFUSIONSOFT_IMAGE_URL; ?>infusionsoft.png" alt="Infusiojn Soft"></td>
    </tr>

    <tr class="form-field">
        <th class="arm-form-table-label"><label><?php _e('App Name', ARM_INFUSIONSOFT_TXTDOMAIN); ?></label></th>
        <td class="arm-form-table-content">
            <input id="arm_infusionsoft_appname" type="text" name="arm_infusionsoft_appname" value="<?php echo ((isset($arm_infusionsoft_appname) && $arm_infusionsoft_appname != '') ? $arm_infusionsoft_appname : ''); ?>">
            <br/> <span style="font-size:13px;"><em><?php _e('The App Name is the portion of your InfusionSoft URL that comes before ".infusionsoft.com"', ARM_INFUSIONSOFT_TXTDOMAIN); ?></em></span>
        </td>
    </tr>

    <tr class="form-field">
        <th class="arm-form-table-label"><label><?php _e('API Encrypted Key', ARM_INFUSIONSOFT_TXTDOMAIN); ?></label></th>
        <td class="arm-form-table-content">
            <input id="arm_infusionsoft_apikey" type="text" name="arm_infusionsoft_apikey" value="<?php echo ((isset($arm_infusionsoft_apikey) && $arm_infusionsoft_apikey != '') ? $arm_infusionsoft_apikey : ''); ?>">
            <br/> <span style="font-size:13px;"><em><?php _e('Instructions for how to get API Encrypted Key is available at ', ARM_INFUSIONSOFT_TXTDOMAIN); ?>
                    <a href="http://help.infusionsoft.com/userguides/get-started/tips-and-tricks/api-key" target="_blank"><?php _e('InfusionSoft User Guide', ARM_INFUSIONSOFT_TXTDOMAIN); ?></a></em></span>
        </td>
    </tr>
</table>