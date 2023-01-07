<div class="wrap">

    <?php settings_errors('mpDemoExport', false); ?>

    <h3><?php _e('Export Sandbox, ID:', 'mp-demo'); ?> <?php echo $sandbox_data['blog_id']; ?>, <?php _e('email:', 'mp-demo'); ?> <?php echo $user_data['email']; ?></h3>
    <form id="mp-demo-export-sandbox" method="POST">
        <input type="hidden" name="options[blog_id]" value="<?php echo $sandbox_data['blog_id']; ?>">
        <input type="hidden" name="options[dist_folder]" value="<?php echo $dist_folder; ?>">
        <h4><?php _e('1. Choose tables you would like to export from database', 'mp-demo'); ?></h4>

        <div class="mp-admin-restrict">
			<p class="description"><?php _e('Note: all tables are exported with DROP TABLE statement. Table with Users is not exported.', 'mp-demo'); ?></p>
            <div class="mp-parent-div box">
                <h4><label><input type="checkbox" class="mp-demo-parent"><?php _e('All tables of this sandbox', 'mp-demo'); ?></label></h4>
                <ul>
                    <?php foreach($sandbox_tables as $sandbox_table): ?>
                        <li>
                            <label>
                                <input type="checkbox"
                                       name="options[tables][]"
                                       value="<?php echo $sandbox_table; ?>"><?php echo $sandbox_table; ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <h4><?php _e('2. Export uploads', 'mp-demo'); ?></h4>

		<p>
			<label>
				<input type="checkbox"
					   name="options[blog_upload_folder]"
					   value="<?php echo $upload_folder['path']; ?>"><?php _e('Export uploads of this sandbox', 'mp-demo'); ?>
			</label>
		</p>

		<p class="description">
            <?php echo __('Path: ', 'mp-demo') . $upload_folder['path']; ?><br/>
            <?php echo __('Total size: ', 'mp-demo') . number_format( $upload_folder['size'], 0, ',', ' ' ) . __(' bytes', 'mp-demo'); ?>
        </p>

        <h4><?php _e('3. Database replacements', 'mp-demo'); ?></h4>

        <div id="mp-replacements-wrap">

            <table id="mp-replacements-table" class="widefat striped">
                <thead>
                <tr>
                    <th style="width:40px;"></th>
					<th><?php _e('Find', 'mp-demo'); ?></th>
                    <th style="width:40px;"></th>
                    <th><?php _e('Replace with', 'mp-demo'); ?></th>
                    <th style="width:40px;"></th>
                </tr>
                </thead>
                <tbody>

                <?php
                foreach($replacements as $replacement){
                    mp_demo_render_replace_table_row($replacement, true);
                }
                ?>

                </tbody>
            </table>

            <p>
                <input type="button" id="mp-demo-add-replacement" class="button-secondary" value="<?php _e('Add replacement', 'mp-demo'); ?>">
                <span class="spinner"></span>
            </p>

        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Export', 'mp-demo'); ?>" disabled>
            <span class="spinner"></span>
        </p>

    </form>

	<div id="mp-demo-export-info-wrap">
    </div>

</div>