<?php 
$vk_code = isset($_REQUEST['code']) ? $_REQUEST['code'] : '';
if ($vk_code !== '') {
	echo "<script type='text/javascript' id='authorize'>";
            echo "arm_vk_token();";
            echo "function arm_vk_token(){";
            echo "window.opener.document.getElementById('arm_vk_user_data').value = '".json_encode(array())."';";
            echo "window.close();";
            echo "window.opener.arm_VKAuthCallBack('".$vk_code."')";
            echo "}";
            echo "</script>";
	exit;
}
