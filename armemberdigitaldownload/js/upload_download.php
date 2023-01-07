<?php
require_once("../../../../wp-load.php");
$wp_upload_dir 	= wp_upload_dir();
$upload_dir = $wp_upload_dir['basedir'] . '/armember/';
$upload_url = $wp_upload_dir['baseurl'] . '/armember/';

$file_name = (isset($_SERVER['HTTP_X_FILENAME']) ? $_SERVER['HTTP_X_FILENAME'] : false);
$response = "";
$userID = get_current_user_id();
if ($file_name && !empty($userID) && $userID != 0) {
	$checkext = explode(".", $file_name);
	$ext = $checkext[count($checkext) - 1];
	$denyExts = array("php", "php3", "php4", "php5", "pl", "py", "jsp", "asp", "exe", "cgi");
	if (!in_array($ext, $denyExts)) {
		file_put_contents($upload_dir . $file_name, file_get_contents('php://input'));
		$response = $upload_url . $file_name;
		echo $response;
		exit;
	}
}
echo $response;
exit;