<?php foreach ($defaults['options'] as $key => $default_option): ?>
	<?php
	$val = (isset($data[$default_option['name']])) ? $data[$default_option['name']] : $default_option['value'];
	?>
	<p>
		<?php switch ($default_option['type']) {
			case 'input':
				?>
				<label
					for="<?php echo $widget_object->get_field_id($default_option['name']) ?>"><?php echo $default_option['label'] ?></label>
				<input id="<?php echo $widget_object->get_field_id($default_option['name']) ?>"
				       type="text"
				       class="widefat"
				       name="<?php echo $widget_object->get_field_name($default_option['name']) ?>"
				       placeholder=""
				       value="<?php echo trim($val) ?>">
				<?php
				break;
			case 'textarea':
				?>
				<label
					for="<?php echo $widget_object->get_field_id($default_option['name']) ?>"><?php echo $default_option['label'] ?></label>
				<textarea rows="4" cols="45" id="<?php echo $widget_object->get_field_id($default_option['name']) ?>"
				          class="widefat"
				          name="<?php echo $widget_object->get_field_name($default_option['name']) ?>"><?php echo trim($val) ?></textarea>
				<?php
				break;
			case 'select':
				$widget_option_val = false;

				if (isset($data[$default_option['name']])) {
					$widget_option_val = $data[$default_option['name']];
					$widget_option_val = is_array($widget_option_val) ? $widget_option_val : explode(',', $data[$default_option['name']]);
				}

				$val = $widget_option_val ? $widget_option_val : $default_option['selected'];
				?>
				<label for="<?php echo $widget_object->get_field_id($default_option['name']) ?>"><?php echo $default_option['label'] ?></label>
				<select
					id="<?php echo $widget_object->get_field_id($default_option['name']) ?>" <?php echo $default_option['multiple']; ?>
					class="widefat"
					name="<?php echo $widget_object->get_field_name($default_option['name']) ?>[]">
					<?php foreach ($default_option['value'] as $select_key => $select_val): ?>
						<?php $is_selected = in_array($select_val['value'], $val); ?>
						<option
							value="<?php echo $select_val['value'] ?>" <?php selected($is_selected); ?>><?php echo $select_val['text'] ?></option>
					<?php endforeach; ?>
				</select>
				<?php
				break;
			case 'checkbox':
				$checked = (isset($data[$default_option['name']])) ? 1 : $default_option['checked'];
				?>
				<label><input id="<?php echo $widget_object->get_field_id($default_option['name']) ?>"
					   type="checkbox"
					   name="<?php echo $widget_object->get_field_name($default_option['name']) ?>"
					   <?php echo ($checked == 1) ? ' checked' : '' ?>
					   placeholder=""
					   value="<?php echo $default_option['value'] ?>"><?php echo $default_option['label'] ?></label>
				<?php
				break;
		} ?>
	</p>
<?php endforeach; ?>