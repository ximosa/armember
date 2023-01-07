<?php global $wpdb, $arm_dd_newdbversion;
if (version_compare($arm_dd_newdbversion, '1.1', '<')) {

	global $wpdb, $arm_dd;

	$wpdb->query("ALTER TABLE {$arm_dd->tbl_arm_dd_items} ADD `arm_item_msg` text NOT NULL AFTER `arm_item_tag`");
	$wpdb->query("ALTER TABLE {$arm_dd->tbl_arm_dd_items} ADD `arm_file_names` text NOT NULL AFTER `arm_item_type`");
	$wpdb->query("ALTER TABLE {$arm_dd->tbl_arm_dd_downloads} ADD `arm_dd_file_id` int(11) NOT NULL DEFAULT '0' AFTER `arm_dd_item_id`");
$folder_name = $arm_dd->arm_dd_get_folder_name();

  $wp_upload_dir  = wp_upload_dir();
  $upload_dir = $wp_upload_dir['basedir'] . '/armember/'.$folder_name;

  $myfile = fopen($upload_dir."/.htaccess", "w");
  $txt = "Order deny,allow\n";
  fwrite($myfile, $txt);
  $txt = "Deny from all";
  fwrite($myfile, $txt);
  fclose($myfile);
}

if (version_compare($arm_dd_newdbversion, '1.2', '<')) {
  global $wpdb, $arm_dd;
  $folder_name = $arm_dd->arm_dd_get_folder_name();

  $wp_upload_dir  = wp_upload_dir();
  $upload_dir = $wp_upload_dir['basedir'] . '/armember/'.$folder_name;

  $myfile = fopen($upload_dir."/.htaccess", "w");
  $txt = "Order deny,allow\n";
  fwrite($myfile, $txt);
  $txt = "Deny from all";
  fwrite($myfile, $txt);
  fclose($myfile);
}

if (version_compare($arm_dd_newdbversion, '1.6', '<')) {
  global $wpdb, $arm_dd;

  $wpdb->query("ALTER TABLE {$arm_dd->tbl_arm_dd_items} ADD `arm_item_download_count` int(11) NOT NULL DEFAULT '0' AFTER `arm_item_msg`");

  $arm_dd_downloads = "SELECT arm_dd_item_id, count(arm_dd_item_id) AS arm_item_dd_count FROM `{$arm_dd->tbl_arm_dd_downloads}`  GROUP BY arm_dd_item_id";
  $downloads_result = $wpdb->get_results($arm_dd_downloads);
  if(!empty($downloads_result))
  {
    foreach ($downloads_result as $arm_dd_download_items) {
      $wpdb->query( "UPDATE `{$arm_dd->tbl_arm_dd_items}` SET arm_item_download_count = ". $arm_dd_download_items->arm_item_dd_count . " WHERE arm_item_id = '" . $arm_dd_download_items->arm_dd_item_id ."' ");
    }
  }
}
update_option('arm_dd_version', '1.7');
$arm_dd_newdbversion = '1.7';
