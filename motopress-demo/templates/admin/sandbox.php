<div class="wrap">
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">

					<?php $sandbox_list->views(); ?>

					<form method="post">
						<?php
						$sandbox_list->prepare_items();
						$sandbox_list->search_box(__('Search E-mail', 'mp-demo'), 'email');
						$sandbox_list->display(); ?>
					</form>

					<?php
					if ( $sandbox_list->has_items() )
//						$sandbox_list->inline_edit();
					?>
				</div>
			</div>
		</div>
	</div>
</div>
