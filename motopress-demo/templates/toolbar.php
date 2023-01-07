<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">

	<?php
	do_action('mp_demo_toolbar_head');
	?>

</head>
<body>
<?php
$settings = \motopress_demo\classes\models\Toolbar_Settings::get_instance()->get_options();
$demo_src = (isset($_GET['dl']) && array_key_exists($_GET['dl'], $settings['select']))
	? $settings['select'][$_GET['dl']] : false;

?>
<div
	class="toolbar <?php echo apply_filters('mp_demo_toolbar_theme', (empty($settings['theme'])) ? '' : $settings['theme']); ?>"
	id="mp-toolbar"<?php if (!empty($settings['background'])) { ?> style="background-color: <?php echo $settings['background'] ?>" <?php } ?>>
	<?php if (!empty($settings['logo'])) : ?>
		<div class="logo">
			<img src="<?php echo esc_url($settings['logo']); ?>">
		</div>
	<?php endif; ?>

	<?php if (isset($settings['select']) && is_array($settings['select']) && (count($settings['select']) > 0) ): ?>
		<ul class="list">
			<li class="select-wrap">
				<a class="current"><?php
					if ($demo_src) {
						echo $demo_src['text'];
					} else {
						_e('Select...', 'mp-demo');
					}
					?><span class="arrow"><img src="<?php echo Motopress_Demo::get_plugin_url('assets/images/arrow.png'); ?>" width="29" height="29"></span></a>
				<ul>
					<?php
					do_action('mp_demo_toolbar_list_before', $settings['select']);

						?>
						<?php foreach ($settings['select'] as $option): ?>
							<li>
								<a href="<?php echo add_query_arg(array('dr' => 1, 'dl' => $option['link_id']), $option['link']) ?>">
									<?php echo $option['text'] ?>
									<br><img src="<?php echo $option['img'] ?>">
								</a>
							</li>
						<?php endforeach; ?>
						<?php
					do_action('mp_demo_toolbar_list_after', $settings['select']);
					?>
				</ul>
			</li>
		</ul>
	<?php endif; ?>

	<div class="responsive">
		<a class="desktop active" data-width="100%" data-height="100%"
		   title="<?php _e('View Desktop', 'mp-demo'); ?>"></a>
		<a class="tabletlandscape" data-width="1024px" data-height="768px"
		   title="<?php _e('View Tablet Landscape (1024x768)', 'mp-demo'); ?>"></a>
		<a class="tabletportrait" data-width="768px" data-height="1024px"
		   title="<?php _e('View Tablet Portrait (768x1024)', 'mp-demo'); ?>"></a>
		<a class="mobilelandscape" data-width="480px" data-height="320px"
		   title="<?php _e('View Mobile Landscape (480x320)', 'mp-demo'); ?>"></a>
		<a class="mobileportrait" data-width="320px" data-height="480px"
		   title="<?php _e('View Mobile Portrait (320x480)', 'mp-demo'); ?>"></a>
	</div>

	<ul class="links">

		<?php
		$btn_text = $demo_src ? $demo_src : $settings;
		do_action('mp_demo_toolbar_links', $btn_text);
		?>
		<?php if (!empty($btn_text['btn_text'])) : ?>
			<li class="purchase">
				<a href="<?php echo esc_url($btn_text['btn_url']); ?>"<?php echo (!empty($btn_text['btn_class'])) ? ' class="' . $btn_text['btn_class'] . '"' : ''; ?>>
					<?php echo $btn_text['btn_text']; ?>
				</a>
			</li>
		<?php endif; ?>
		<li class="close-button"><a
				href="<?php echo remove_query_arg(array('dr' => 1), get_permalink()); ?>">&times;</a>
		</li>
	</ul>

</div>
<div class="iframe-wrap">
	<?php
	$demo_src = ($demo_src) ? $demo_src['link'] : get_site_url(1) . remove_query_arg(array('dr'));
	?>
	<iframe id="mp-iframe" src="<?php echo apply_filters('mp_demo_responsive_preview_url', $demo_src); ?>"
	        frameborder="0" width="100%" height="100%"></iframe>
</div>
<?php
do_action('mp_demo_toolbar_footer');
?>

</body>
</html>