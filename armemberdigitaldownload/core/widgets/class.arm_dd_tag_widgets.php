<?php
if(!class_exists('ARM_dd_widget_tag'))
{
	class ARM_dd_widget_tag extends WP_Widget
	{
		function __construct()
		{
			parent::__construct(
				'arm_dd_download_tags',
				__('ARMember Download Tags', 'ARM_DD'),
				array('description' => __('Download Items Tag wise', 'ARM_DD'))
			);

			add_action( 'wp_enqueue_scripts', array( &$this, 'arm_dd_scripts' ) );

			add_action( 'wp_ajax_arm_dd_tag_item_list', array( $this, 'arm_dd_tag_item_list' ) );
            
            add_action( 'wp_ajax_nopriv_arm_dd_tag_item_list', array( $this, 'arm_dd_tag_item_list' ) );
		}

		function arm_dd_tag_item_list(){
			$content =  __("Sorry, Something went wrong. Please try again.", 'ARM_DD');
			$status = 'failed';
            if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'arm_dd_tag_item_list' && isset($_REQUEST['arm_dd_tag']) && $_REQUEST['arm_dd_tag'] != '' ) {
            	global $wpdb, $arm_dd;
	            $arm_dd_items_data = $wpdb->get_results( "SELECT `arm_item_id`, `arm_item_tag` FROM `".$arm_dd->tbl_arm_dd_items."` WHERE arm_item_tag like '%".$_REQUEST['arm_dd_tag']."%' ", ARRAY_A );
	            if( $arm_dd_items_data ) {
	            	$content = '';
	            	foreach ($arm_dd_items_data as $item_data) {
	            		$tags = explode(', ', $item_data['arm_item_tag']);
						foreach ( $tags as $tag ) {
							if( trim($tag) == $_REQUEST['arm_dd_tag'] ) {
			            		$ARM_item_shortcode = "[arm_download item_id='".$item_data['arm_item_id']."']";
			            		$content .= do_shortcode($ARM_item_shortcode);
			            	}
			            }
	            	}
	            	$status = 'success';
	            }
	            else
	            {
	            	$content = __("Sorry, No any downloads found for", 'ARM_DD') . " " . $_REQUEST['arm_dd_tag'] .".";
	            }
            }
            $response = array('type' => $status, 'msg' => $content);
            echo json_encode($response);
            die;
        }
        
		public function widget($args, $instance)
		{
			global $wp, $wpdb, $ARMember, $arm_dd, $arm_global_settings;
			echo $args['before_widget'];

            $date_format = $arm_global_settings->arm_get_wp_date_format();
			if (!empty($instance['title'])) {
				echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
			}
			$total_rec = $instance['total_rec'];

			$frontfontstyle = $arm_global_settings->arm_get_front_font_style();
			$output = '';
			if(!empty($frontfontstyle['google_font_url'])){
				$output .= '<link id="google-font" rel="stylesheet" type="text/css" href="' . $frontfontstyle['google_font_url'] . '" />';
			}

			$dd_get_tags = $wpdb->get_col( $wpdb->prepare( "SELECT `arm_item_tag` FROM `".$arm_dd->tbl_arm_dd_items."` WHERE arm_item_tag != %s ", NULL ) );

			$arm_dd_tag_list = array();
			if( $dd_get_tags ) {
				foreach ($dd_get_tags as $dd_tag) {
					$tags = explode(', ', $dd_tag);
					foreach ($tags as $tag) {
						if( !( empty($tag) && in_array( $arm_dd_tag_list, $tag) ) ) {
							array_push($arm_dd_tag_list, trim($tag));
						}
					}
				}
			}

			$arm_dd_tag_list = array_unique($arm_dd_tag_list);
			if(!empty($arm_dd_tag_list)){
				asort($arm_dd_tag_list);
				$output .= "<div class='arm_widget_dd_tag_wrapper_container'>";
				$output .= "<div class='arm_widget_dd_tag_wrapper'>";
				foreach ($arm_dd_tag_list as $arm_dd_tag) {
					$output .= "<a href='javascript:void(0);' data_tag_key='".$arm_dd_tag."' data-tag='".str_replace(' ', '_', $arm_dd_tag)."' class='arm_dd_tags'>".$arm_dd_tag."</a> <br/>";
					$output .= "<div class='arm_dd_download_item_content ".str_replace(' ', '_', $arm_dd_tag)."'></div>";
				}
				$output .= "</div>";
				$output .= "</div>";
			}

			echo $output;
			echo $args['after_widget'];
		}

		public function form($instance)
		{
			global $arm_member_forms;
			$title = !empty($instance['title']) ? $instance['title'] : __('Download Tags', 'ARM_DD');
			$total_rec = !empty($instance['total_rec']) ? $instance['total_rec'] : 5;
			?>
			<p>
				<label for="<?php echo $this->get_field_id('title');?>"><?php _e('Title', 'ARM_DD');?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id('title');?>" name="<?php echo $this->get_field_name('title');?>" type="text" value="<?php echo esc_attr($title);?>">
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('total_rec');?>"><?php _e('Total Records To Display', 'ARM_DD');?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id('total_rec');?>" name="<?php echo $this->get_field_name('total_rec');?>" type="text" value="<?php echo esc_attr($total_rec);?>">
			</p>
			<?php
		}

		public function update($new_instance, $old_instance)
		{
			$instance = array();
			$instance['title'] = (!empty($new_instance['title']) ) ? strip_tags($new_instance['title']) : '';
			$instance['total_rec'] = !empty($new_instance['total_rec']) ? $new_instance['total_rec'] : 5;
			return $instance;
		}

		function arm_dd_scripts()
		{
			if (is_active_widget(false, false, $this->id_base, true)) {
				global $arm_dd_version, $arm_ajaxurl;
				wp_register_script( 'arm_dd_front_js', ARM_DD_URL . '/js/arm_dd_front.js', array(), $arm_dd_version );
				wp_localize_script( 'arm_dd_front_js', 'ajaxurl', $arm_ajaxurl );
				wp_enqueue_script( 'arm_dd_front_js' );
			}
		}
	}

	if (class_exists('WP_Widget'))
	{
		function arm_dd_tag_widgets()
		{
			register_widget('ARM_dd_widget_tag');
		}
		add_action('widgets_init', 'arm_dd_tag_widgets');
	}
}