<?php
/**
 * Date: 6/3/2016
 * Time: 9:27 AM
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;
global $wpdb;

/**
 * Generates the System Info Download File
 */
function mp_demo_generate_sysinfo_download() {
	if (!empty($_POST['mp-demo-action']) && ($_POST['mp-demo-action'] == 'download_sysinfo')) {
		nocache_headers();
		header("Content-type: text/plain");
		header('Content-Disposition: attachment; filename="mp-demo-system-info.txt"');
		echo wp_strip_all_tags($_POST['mp-demo-sysinfo']);
		exit;
	}
}

function mp_demo_render_toolbar_table_row($data, $print = true) {
	ob_start();
	?>
	<tr data-id="<?php echo $data['link_id'] ?>">
		<td class="check-column"></td>
		<td class="select-link_id"><?php echo $data['link_id']; ?></td>
		<td class="select-text"><?php echo $data['text']; ?></td>
		<td class="select-link"><?php echo $data['link']; ?></td>
		<td class="select-img"><img src="<?php echo $data['img']; ?>" height="30"></td>
		<td class="select-btn_text"><?php echo $data['btn_text']; ?></td>
		<td class="select-btn_url"><?php echo $data['btn_url']; ?></td>
		<td>
			<?php if ($print): ?>
				<a class="button view-event-button" href="<?php echo add_query_arg(array('dr' => '1', 'dl' => $data['link_id']), $data['link']) ?>" target="_blank"><?php _e('View', 'mp-demo') ?></a>
			<?php endif; ?>
			<a class="button edit-event-button" data-id="<?php echo $data['link_id'] ?>"><?php _e('Edit', 'mp-demo') ?></a>
			<a class="button delete-event-button" data-id="<?php echo $data['link_id'] ?>"><?php _e('Delete', 'mp-demo') ?></a>
			<!-- HIDDEN -->
			<input type="hidden" name="settings[select][<?php echo $data['link_id'] ?>][link_id]" value="<?php echo $data['link_id']; ?>">
			<input type="hidden" name="settings[select][<?php echo $data['link_id'] ?>][text]" value="<?php echo $data['text']; ?>">
			<input type="hidden" name="settings[select][<?php echo $data['link_id'] ?>][link]" value="<?php echo $data['link']; ?>">
			<input type="hidden" name="settings[select][<?php echo $data['link_id'] ?>][img]" value="<?php echo $data['img']; ?>">
			<input type="hidden" name="settings[select][<?php echo $data['link_id'] ?>][btn_text]" value="<?php echo $data['btn_text']; ?>">
			<input type="hidden" name="settings[select][<?php echo $data['link_id'] ?>][btn_url]" value="<?php echo $data['btn_url']; ?>">
			<input type="hidden" name="settings[select][<?php echo $data['link_id'] ?>][btn_class]" value="<?php echo $data['btn_class']; ?>">
		</td>
	</tr>
	<?php

	$result = ob_get_clean();

	if ($print) {
		echo $result;
	}

	return $result;
}


function mp_demo_render_replace_table_row($data, $print = true) {
	ob_start();
	?>
	<tr>
		<td style="cursor:move;">&#8597;</td>
		<td><input type="text" class="large-text" name="options[find][]" value="<?php echo isset($data['find'])? $data['find'] : ''; ?>" required></td>
		<td><span class="mp-demo-symbol-arrow">&rarr;</span></td>
		<td><input type="text" class="large-text" name="options[replace][]" value="<?php echo isset($data['replace'])? $data['replace'] : ''; ?>"></td>
		<td><span class="mp-demo-symbol-delete">&#10005;</span></td>
	</tr>
	<?php

	$result = ob_get_clean();

	if ($print) {
		echo $result;
	}

	return $result;
}

function mp_demo_compare_restrictions($a, $b) {
	if ($a == $b) {
		return true;
	}

	$is_customize = false;

	if (mp_demo_is_customize_header_submenu($a) && mp_demo_is_customize_header_submenu($b)) {
		$is_customize = true;
	} else if (mp_demo_is_customize_bg_submenu($a) && mp_demo_is_customize_bg_submenu($b)) {
		$is_customize = true;
	} else if (mp_demo_is_customize_submenu($a) && mp_demo_is_customize_submenu($b)
		&& !mp_demo_is_customize_header_submenu($a) && !mp_demo_is_customize_header_submenu($b)
		&& !mp_demo_is_customize_bg_submenu($a) && !mp_demo_is_customize_bg_submenu($b)
	) {
		$is_customize = true;
	}

	return $is_customize;
}

function mp_demo_is_customize_header_submenu($uri) {
	$pattern = '/^customize\.php\?return=[\w%-=#]+header_image$/';
	return preg_match($pattern, $uri);
}

function mp_demo_is_customize_bg_submenu($uri) {
	$pattern = '/^customize\.php\?return=[\w%-=#]+background_image$/';
	return preg_match($pattern, $uri);
}

function mp_demo_is_customize_submenu($uri) {
	$pattern = '/^customize\.php\?return=[\w%-^]+/';
	return preg_match($pattern, $uri);
}

function mp_demo_check_customize_restriction($link) {
	$is_customize = false;
	$pattern = '/^customize\.php\?return=[\w%-^]+/';
	$pattern_bg = '/^customize\.php\?return=[\w%-=#]+background_image$/';
	$pattern_header = '/^customize\.php\?return=[\w%-=#]+header_image$/';

	if (preg_match($pattern_header, $link)) {
		$is_customize = true;
	} else if (preg_match($pattern_bg, $link)) {
		$is_customize = true;
	} else if (preg_match($pattern, $link)
		&& !preg_match($pattern_header, $link)
		&& !preg_match($pattern_bg, $link)
	) {
		$is_customize = true;
	}

	return $is_customize;
}

/**
 * Check if this page slug is forbidden
 *
 * @param $page_slug
 *
 * @return bool
 */
function mp_demo_is_forbidden_page($page_slug) {
	$forbidden_pages = array('mp-demo', 'mp-demo-restrictions', 'users.php');

	if (Motopress_Demo::hide_plugins()) {
		$forbidden_pages[] = 'plugins.php';
	}

	$forbidden_pages = apply_filters('mp_demo_forbidden_pages', $forbidden_pages);

	return in_array($page_slug, $forbidden_pages);
}

function mp_demo_generate_submenu_uri($parent_slug, $submenu_slug) {

	if (strpos($submenu_slug, '.php') !== false) {
		return $submenu_slug;
	}

	if (strpos($parent_slug, '?') == false) {
		return $parent_slug . '?page=' . $submenu_slug;
	}

	return $parent_slug . '&page=' . $submenu_slug;
}

/**
 * SANDBOX EXPORT
 */

 function mp_demo_create_zip_output($data) {

	 if ($data['status'] == 1) {
		 return '<hr><p><strong>'
		 . __('Dowload archive:', 'mp-demo')
		 . ' <a href="' . $data['zip_url'] . '" >' . $data['zip_url'] . '</a>'
		 . ' </strong></p>'
		 . ' <p>'
		 . ' <input type="button" id="mp-demo-remove-export-files" class="button-secondary" data-export="' . $data['zip_path'] . '" value="' . __('Delete archive', 'mp-demo') . '">'
		 . ' <span class="spinner"></span>'
		 .'</p>';
	 } else {
		 return '<p>' . sprintf(__('Something went wrong, check zip message: %s', 'mp-demo'), $data['status']) . '</p>';
	 }
 }

 function mp_demo_export_uploads_output($data) {
	 if ($data['export_uploads']) {
		return '<p>'
				 . sprintf(
						 __('<b>Copied uploads:</b> %d files', 'mp-demo'),
						$data['count']
				 )
				 . '</p>';
	 } else {
//		 return '<p><i>' . __('Skip uploads', 'mp-demo') . '</i></p>';
	 }
 }

 function mp_demo_export_tables_output($data) {
	 $html = '';
	 if ($data['export_tables']) {
		 $html = '<p>'
				 . '<b>' . __('Copied tables:', 'mp-demo') . '</b><br/>'
				 . ' ' . implode(', ', $data['tables']) . ' '
				 . '</p>';

		 if (!empty($data['details'])) {
			 $html .= '<p>'
					 . sprintf(__('<b>Details:</b> %s', 'mp-demo'), $data['details'])
					 . '</p>';
		 }

	 } else {
//		 $html = '<p>' . __('No tables to copy', 'mp-demo') . '</p>';
	 }

	 return $html;
 }

 function mp_demo_remove_export_output($data) {

	 return '<p><strong>' . __('Archive was deleted', 'mp-demo') . '</strong></p>';
 }
 