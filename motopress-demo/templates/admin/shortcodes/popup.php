<?php
/**
 * Date: 5/3/2016
 */
//
//$params = array(
//  'form_id' => 'id',
//  'popup_title' => 'title',
//	'options' => array(
//		0 => array(
//			'type' => array('input', 'select', 'combobox', 'checkbox', 'number', 'textarea'),
//			'name' => 'string',
//			'label' => 'string',
//
//			'value' => array(
//				'text' => 'sourceText',
//				'value' => 'sourceValue'
//			), // ||
//			'value' => 'sourceText'
//		)
//	)
//);

$options = $params['options'];

?>
<form id="<?php echo $params['form_id'] ?>" tabindex="-1" style="display: none;" class="mce-mp-demo-popup">
	<?php if (!empty($params['form_id'])): ?>
		<h2><?php echo $params['popup_title'] ?></h2>
	<?php endif; ?>

	<div class="mp-mce-controls">
		<?php foreach ($options as $key => $option): ?>
			<p>
				<?php switch ($option['type']) {
					case 'input':
						echo "<label>" . $option['label'] . "</label><br>";
						echo '<input type="text" class="widefat" name="' . $option['name'] . '" value="' . $option['value'] . '">';
						break;
					case 'number':
						echo "<label>" . $option['label'] . "</label><br>";
						echo '<input type="number" name="' . $option['name'] . '" value="' . $option['value'] . '">';
						break;
					case 'textarea':
						echo "<label>" . $option['label'] . "</label><br>";
						echo '<textarea rows="4" cols="45" name="' . $option['name'] . '">' . $option['value'] . '</textarea>';
						break;
					case 'select':
						echo "<label>" . $option['label'] . "</label><br>";
						?>
						<select class="widefat" name="<?php echo $option['name']; ?>" <?php echo $option['multiple']; ?>>
							<?php foreach ($option['value'] as $val): ?>
								<option value="<?php echo $val['value'] ?>" <?php selected(in_array($val['value'], $option['selected'])); ?>><?php echo $val['text'] ?></option>
							<?php endforeach; ?>
						</select>
						<?php
						break;
					case 'combobox':
						echo "<label>" . $option['label'] . "</label><br>";
						?>
						<select name="<?php echo $option['name']; ?>[]" multiple>
							<?php foreach ($option['value'] as $val): ?>
								<?php $checked = ($option['name'] === 'display') ? ' selected' : ''; ?>
								<option
									value="<?php echo $val['value']; ?>"<?php echo $checked; ?>><?php echo $val['text'] ?></option>
							<?php endforeach; ?>
						</select>
						<?php
						break;
					case 'checkbox':
						$checked = (isset($option['checked']) && $option['checked']) ? ' checked' : '';
						?>
						<label><input type="checkbox" name="<?php echo $option['name']; ?>"
						              value="<?php echo $option['value']; ?>"<?php echo $checked; ?>><?php echo $option['label'] ?>
						</label>
						<?php
				} ?>
			</>
		<?php endforeach; ?>
		<br></div>
	<input type="submit" class="button button-primary" value="<?php _e('Insert', 'mp-demo'); ?>"/>
	<input type="button" class="button" value="<?php _e('Cancel', 'mp-demo'); ?>"/>
</form>