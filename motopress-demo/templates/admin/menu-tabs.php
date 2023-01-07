<div class="wrap">
	<?php if (!empty($title)) { ?>
		<h1><?php echo $title; ?></h1>
	<?php } ?>

	<?php settings_errors('mpDemoSettings', false); ?>

	<h2 class="nav-tab-wrapper"><?php
		if (is_array($tabs)) {
			uasort($tabs, function ($a, $b) {
				return $a['priority'] - $b['priority'];
			});
			foreach ($tabs as $tabId => $tab) {
				$class = ($tabId == $curTabId) ? ' nav-tab-active' : '';
				$url = remove_query_arg(array('subtab', 'settings-updated'));
				echo '<a href="' . esc_url(add_query_arg(array('page' => $_GET['page'], 'tab' => $tabId), $url)) . '" class="nav-tab' . $class . '">' . esc_html($tab['label']) . '</a>';
			}
		}
		?></h2>
	<?php
	if (is_array($tabs) && array_key_exists($curTabId, $tabs)) {
		$callbackFunc = $tabs[$curTabId]['callback'];
		if (!empty($callbackFunc)) {
			if (
				(is_string($callbackFunc) && function_exists($callbackFunc)) ||
				(is_array($callbackFunc) && count($callbackFunc) === 2 && method_exists($callbackFunc[0], $callbackFunc[1]))
			) {
				call_user_func($callbackFunc);
			}
		}
	}
	?>
</div>