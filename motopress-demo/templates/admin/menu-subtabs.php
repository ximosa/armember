<div class="wrap">
	<?php if (!empty($title)) { ?>
		<h1><?php echo $title; ?></h1>
	<?php } ?>
	<?php $cssClassSufix = (!empty($cssClassSufix)) ? $css_class_sufix : 'subtabs'; ?>

	<ul class="subsubsub mp-demo-nav-<?php echo $cssClassSufix; ?>-wrapper"><?php
		if (is_array($tabs)) {
			uasort($tabs, function ($a, $b) {
				return $a['priority'] - $b['priority'];
			});
			foreach ($tabs as $tabId => $tab) {
				$class = ($tabId == $curTabId) ? ' mp-demo-nav-'. $cssClassSufix .'-active' : '';
				echo '<li class="mp-demo-nav-wrap"><a href="'
						. esc_url(add_query_arg(array('page' => $_GET['page'], 'subtab' => $tabId, 'settings-updated' => 'false')))
						. '" class="mp-demo-nav-' . $cssClassSufix . $class . '">'
						. esc_html($tab['label'])
						. '</a></li>';
			}
		}
		?>
	</ul>
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