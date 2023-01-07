<?php
if(!class_exists('arm_wd_product')){
    
    class arm_wd_product{

        function __construct(){

            // To Set JS in product page
            add_action( 'admin_enqueue_scripts', array( $this, 'arm_wd_pro_set_js' ) );

            // To add ARMember Plan tab in product data metabox of woocommerce 
            add_action('woocommerce_product_write_panel_tabs', array($this, 'arm_wp_plan_tab'));
            
            // To add ARmember Plans checkbox, discount type and amount in product data metabox of woocommerce 
            add_action('woocommerce_product_data_panels', array($this, 'arm_wd_plan_tab_options')); 

            // To save data of ARmember Plans checkbox, discount type and amount in product data metabox of woocommerce 
            add_action('woocommerce_process_product_meta', array(&$this, 'arm_wd_save_plan_tab_meta')); 

            // To Set WooCommerce Product Column Header
            //add_filter( 'manage_posts_columns', array( $this, 'arm_wp_pro_column_header' ), 10, 2 ); 

            // To Set WooCommerce Product Column Value
            //add_action( 'manage_posts_custom_column', array( $this, 'arm_wp_pro_column_value' ), 10, 2 ); 

            // To Set WooCommerce Product Column Header in Manage Product
            add_filter('manage_product_posts_columns', array( $this, 'arm_wp_pro_manage_column_header' ) );

            // To Set WooCommerce Product Column Value in Manage Product
            add_action('manage_product_posts_custom_column', array( $this, 'arm_wp_pro_manage_column_value' ), 10, 2);

            // To Set WooCommerce Product Quick OR Bulk Edit Custom Box
            add_action( 'bulk_edit_custom_box', array( $this, 'arm_wd_pro_quick_bulk_edit' ), 10, 2 ); 
            add_action( 'quick_edit_custom_box', array( $this, 'arm_wd_pro_quick_edit' ), 10, 2 ); 

            // To Save Quick Edited Product Data
            add_action( 'save_post', array( $this, 'arm_wd_pro_quick_save_post' ), 10, 2 ); 

            // To Save Bulk Edited Product Data 
            add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'arm_wd_pro_bulk_save_post' ), 10, 1 );  

            // To hide columns in manage product and manage category page.
            add_filter( 'hidden_columns', array( $this, 'arm_wd_hidden_columns' ), 10, 3 ); 
        }

        function arm_wd_pro_set_js() {
            global $arm_wd_version, $arm_wd, $pagenow;;
            if( $arm_wd->arm_wd_is_page( 'product' ) && ( $pagenow == 'edit.php' || $pagenow == 'post.php' || $pagenow == 'post-new.php' ) ) {
                wp_enqueue_script( 'arm_wd_pro_admin_js', ARM_WD_URL . '/js/arm_wd_pro_admin.js', array( 'jquery', 'inline-edit-post' ), $arm_wd_version, true );
            }
        }

        function arm_wp_plan_tab() {
            global $arm_wd;
            echo '<li class="arm_plan_mapping_tab"><a href="#arm_wd_mapping_data_tab"><span class="arm_plan_map_span">'.$arm_wd->arm_wd_tab_title.'</span></a></li>';
        }

        function arm_wd_plan_tab_options() {
            global $arm_subscription_plans, $post, $arm_wd;
            ?>
            <div id="arm_wd_mapping_data_tab" class="panel woocommerce_options_panel">
                <div class="options_group">
                    <?php $this->arm_wd_get_product_content( $post->ID ); ?>
                </div>   
            </div>
            <?php
        }

        function arm_wd_save_plan_tab_meta($post_id) {
            global $arm_wd;
            // Save ARMember Plans

            $plans = isset($_REQUEST['arm_wd_plans']) ? $_REQUEST['arm_wd_plans'] : array();
            $discount_type = isset($_REQUEST['arm_discount_type']) ? $_REQUEST['arm_discount_type'] : array();
            $arm_amount = isset($_REQUEST['arm_amount']) ? $_REQUEST['arm_amount'] : array();

            if (isset($plans) && !empty($plans)) {
                $arm_wd->arm_wd_meta_add_update_product_plans( $post_id, $plans );
                foreach ($plans as $plan_id) {
                    $plan_discount_type = (isset($discount_type[$plan_id]) && !empty($discount_type) ) ? $discount_type[$plan_id] : '';
                    $plan_amount = (isset($arm_amount[$plan_id]) && !empty($arm_amount) ) ? $arm_amount[$plan_id] : '';
                    if( $plan_discount_type != '' && $plan_amount >= 0 && $plan_amount != '' ) {
                        $arm_wd->arm_wd_meta_add_update_plan_discount( $post_id, $plan_id, $plan_discount_type, $plan_amount );
                    }
                }
            }
            else
            {
                $arm_wd->arm_wd_meta_delete_product($post_id);
            }
        }

        function arm_wp_pro_manage_column_header($defaults) {
            global $arm_subscription_plans;
            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('all');
            foreach ($all_plans as $plan) {
                $defaults['arm_wd_plan_'.$plan['arm_subscription_plan_id']] = $plan['arm_subscription_plan_name'];
                $defaults['arm_wd_discount_type_'.$plan['arm_subscription_plan_id']] = $plan['arm_subscription_plan_name']." Discount Type";
                $defaults['arm_wd_amount_'.$plan['arm_subscription_plan_id']] = $plan['arm_subscription_plan_name']." Amount";
            }
            return $defaults;
        }
 
        function arm_wp_pro_manage_column_value($column_name, $post_ID) {
            global $arm_subscription_plans, $arm_wd;
            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('all');
            $arm_product_plans = $arm_wd->arm_wd_meta_get_product_plans( $post_ID );
            $arm_plan_ids = array();
            foreach ($all_plans as $plan) {
                $arm_plan_ids[] = $plan['arm_subscription_plan_id'];
            }
            foreach ($all_plans as $plan) {
                $plan_id = $plan['arm_subscription_plan_id'];
                if( !empty( $arm_product_plans ) && in_array( $plan_id, $arm_product_plans ) ){
                    $arm_plan_discount = $arm_wd->arm_wd_meta_get_plan_discount($post_ID, $plan_id);
                    $arm_plan_is_discount = 'Yes';
                    $discount_type = isset($arm_plan_discount['arm_discount_type']) ? $arm_plan_discount['arm_discount_type'] : 'fix';
                    $arm_amount = isset($arm_plan_discount['arm_amount']) ? $arm_plan_discount['arm_amount'] : '';  
                } else {
                    $arm_plan_is_discount = 'No';
                    $discount_type = '-';
                    $arm_amount = '-';
                }

                if( $column_name == 'arm_wd_plan_'.$plan_id ){
                    echo '<input type="hidden" id="arm_plan_id_'.$post_ID.'" name="arm_plan_id_'.$post_ID.'" value="'.implode(',',$arm_plan_ids).'" />';
                    echo '<div id="arm_wd_plan_'.$plan_id.'_'.$post_ID.'">'.$arm_plan_is_discount.'</div>';
                }

                if( $column_name == 'arm_wd_discount_type_'.$plan_id ){
                    echo '<div id="arm_wd_discount_type_'.$plan_id.'_'.$post_ID.'">'.$discount_type.'</div>';
                }

                if( $column_name == 'arm_wd_amount_'.$plan_id ){
                    echo '<div id="arm_wd_amount_'.$plan_id.'_'.$post_ID.'">'.$arm_amount.'</div>';
                }
            }
        }

        // function arm_wp_pro_column_header( $columns, $post_type ) {
        //     if ( $post_type == 'product' )
        //         $columns[ 'release_date' ] = 'Release Date';
        //     return $columns;
        // }

        // function arm_wp_pro_column_value( $column_name, $post_id ) {
        //     switch( $column_name ) {
        //         case 'release_date':
        //             echo '<div id="release_date-' . $post_id . '">' . get_post_meta( $post_id, 'release_date', true ) . '</div>';
        //             break;
        //     }
        // }

        function arm_wd_pro_quick_bulk_edit( $column_name, $post_type ) {
            if( $post_type == 'product' ){
                global $arm_subscription_plans, $arm_wd, $arm_count_bulk_column;
                $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('all');
                if($arm_count_bulk_column <= 0) {
                    foreach ($all_plans as $plan) {
                        $plan_id = $plan['arm_subscription_plan_id'];
                        if( $column_name == 'arm_wd_plan_'.$plan_id ){
                            $arm_count_bulk_column++;
                            echo '<fieldset class="arm_fieldset">';
                            echo '<div class="arm_main_wrapper arm_quick_edit_product_wrapper">';
                            echo '<div class="arm_title_container"><div class="arm_title"><span>' . $arm_wd->arm_wd_tab_title . '<span></div></div>';
                            $this->arm_wd_get_product_content();
                            echo '</div>';
                            echo '</fieldset>';
                        }
                    }
                }
            }
        }

        function arm_wd_pro_quick_edit( $column_name, $post_type ) {
            if( $post_type == 'product' ){
                global $arm_subscription_plans, $arm_wd, $arm_count_column;
                $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('all');
                if($arm_count_column <= 0) {
                    foreach ($all_plans as $plan) {
                        $plan_id = $plan['arm_subscription_plan_id'];
                        if( $column_name == 'arm_wd_plan_'.$plan_id ){
                            $arm_count_column++;
                            echo '<fieldset class="arm_fieldset">';
                            echo '<div class="arm_main_wrapper arm_quick_edit_product_wrapper">';
                            echo '<div class="arm_title_container"><div class="arm_title"><span>' . $arm_wd->arm_wd_tab_title . '<span></div></div>';
                            $this->arm_wd_get_product_content();
                            // echo '<div class="arm_main_container">';
                            // echo '<div class="arm_wd_table">';
                            // echo '<div class="arm_table_row">';
                            // echo '<div class="arm_tbl_col_header arm_wd_no">'.__('No.', ARM_WD_TEXTDOMAIN).'</div>';
                            // echo '<div class="arm_tbl_col_header arm_wd_plan_name">'.__('Plan Name', ARM_WD_TEXTDOMAIN).'</div>';
                            // echo '<div class="arm_tbl_col_header arm_wd_discount">'.__('Discount', ARM_WD_TEXTDOMAIN).'</div>';
                            // echo '<div class="arm_tbl_col_header arm_wd_amount">'.__('Amount', ARM_WD_TEXTDOMAIN).'</div>';
                            // echo '</div>';
                            
                            // $arm_count_plan = 0;
                            // foreach ($all_plans as $plan) {
                            //     $plan_id = $plan['arm_subscription_plan_id'];
                            //     $arm_count_plan++;
                            //     $plan_content = '<input type="checkbox" class="arm_wd_plans arm_wd_plans_'.$plan_id.'" id="arm_wd_plans_'.$plan_id.'" name="arm_wd_plans[]" value="'.$plan_id.'" ><label for="arm_wd_plans_'.$plan_id.'" class="arm_wd_plans_lbl_'.$plan_id.' active">'.$plan['arm_subscription_plan_name'].'</label>';

                            //     $plan_discount_type = '<div id="arm_discount_type_'.$plan_id.'" class="arm_discount_opt">';
                            //     $plan_discount_type .= '<select name="arm_discount_type['.$plan_id.']" id="arm_wd_discount_'.$plan_id.'" class="arm_wd_disc_type_'.$plan_id.'" >';
                            //     $plan_discount_type .= '<option value="-">'.__('Select Type', ARM_WD_TEXTDOMAIN).'</option>';
                            //     $plan_discount_type .= '<option value="fix">'.__('Fix', ARM_WD_TEXTDOMAIN).'</option>';
                            //     $plan_discount_type .= '<option value="per">'.__('Percantage', ARM_WD_TEXTDOMAIN).'</option>';
                            //     $plan_discount_type .= '</select>';
                            //     $plan_discount_type .= '</div>';

                            //     $plan_discount_amount = '<div id="arm_discount_amt_'.$plan_id.'" class="arm_discount_opt">';
                            //     $plan_discount_amount .= '<input type="text" name="arm_amount['.$plan_id.']" value="" id="arm_wd_amount_'.$plan_id.'" />';
                            //     $plan_discount_amount .= '</div>';

                            //     echo '<div class="arm_table_row">';
                            //     echo '<div class="arm_tbl_col arm_wd_no">'. $arm_count_plan .'</div>';
                            //     echo '<div class="arm_tbl_col arm_wd_plan_name">'. $plan_content .'</div>';
                            //     echo '<div class="arm_tbl_col arm_wd_discount">'. $plan_discount_type .'</div>';
                            //     echo '<div class="arm_tbl_col arm_wd_amount">'. $plan_discount_amount .'</div>';
                            //     echo '</div>';
                            // }
                            // echo '</div>';
                            // echo '</div>';
                            echo '</div>';
                            echo '</fieldset>';
                        }
                    }
                }
            }
        }

        function arm_wd_get_product_content( $product_id = '' ) {
            global $arm_subscription_plans, $arm_wd;
            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('all');
            $arm_product_plans = array();
            if( isset( $product_id ) && !empty( $product_id ) ) {
                $arm_product_plans = $arm_wd->arm_wd_meta_get_product_plans( $product_id );
            }

            echo '<div class="arm_main_container">';
            echo '<div class="arm_wd_table">';
            echo '<div class="arm_table_row">';
            echo '<div class="arm_tbl_col_header arm_wd_no">'.__('No.', ARM_WD_TEXTDOMAIN).'</div>';
            echo '<div class="arm_tbl_col_header arm_wd_plan_name">'.__('Plan Name', ARM_WD_TEXTDOMAIN).'</div>';
            echo '<div class="arm_tbl_col_header arm_wd_discount">'.__('Discount', ARM_WD_TEXTDOMAIN).'</div>';
            echo '<div class="arm_tbl_col_header arm_wd_amount">'.__('Amount', ARM_WD_TEXTDOMAIN).'</div>';
            echo '</div>';
            
            $arm_count_plan = 0;
            foreach ($all_plans as $plan) {
                $plan_id = $plan['arm_subscription_plan_id'];
                $is_plan_selected = '';
                $is_plan_active = '';
                $discount_opt_attr = ' disabled="disabled" ';
                $discount_type = '-';
                $arm_amount = '';
                if( !empty( $arm_product_plans ) && in_array( $plan_id, $arm_product_plans ) ){
                    $arm_plan_discount = $arm_wd->arm_wd_meta_get_plan_discount($product_id, $plan_id);
                    $is_plan_selected = 'checked="checked"';
                    $is_plan_active = ' active';
                    $discount_opt_attr = '';
                    $discount_type = isset($arm_plan_discount['arm_discount_type']) ? $arm_plan_discount['arm_discount_type'] : '-';
                    $arm_amount = isset($arm_plan_discount['arm_amount']) ? $arm_plan_discount['arm_amount'] : '';
                }

                $arm_count_plan++;
                $plan_content = '<input type="checkbox" class="arm_wd_plans arm_wd_plans_'.$plan_id.'" id="arm_wd_plans_'.$plan_id.'" name="arm_wd_plans[]" value="'.$plan_id.'" '.$is_plan_selected.' >';
                $plan_content .= '<label for="arm_wd_plans_'.$plan_id.'" class="arm_wd_plans_lbl_'.$plan_id.$is_plan_active.'">'.$plan['arm_subscription_plan_name'].'</label>';

                $plan_discount_type = '<div id="arm_discount_type_'.$plan_id.'" class="arm_discount_opt">';
                $plan_discount_type .= '<select name="arm_discount_type['.$plan_id.']" id="arm_wd_discount_'.$plan_id.'" class="arm_wd_disc_type_'.$plan_id.'" '.$discount_opt_attr.' >';
                $plan_discount_type .= '<option value="-">'.__('Select Type', ARM_WD_TEXTDOMAIN).'</option>';
                $plan_discount_type .= '<option value="fix" '.selected("fix", $discount_type, false).'>'.__('Fix', ARM_WD_TEXTDOMAIN).'</option>';
                $plan_discount_type .= '<option value="per" '.selected("per", $discount_type, false).'>'.__('Percantage', ARM_WD_TEXTDOMAIN).'</option>';
                $plan_discount_type .= '</select>';
                $plan_discount_type .= '</div>';

                $plan_discount_amount = '<div id="arm_discount_amt_'.$plan_id.'" class="arm_discount_opt">';
                $plan_discount_amount .= '<input type="text" name="arm_amount['.$plan_id.']" value="'.$arm_amount.'" id="arm_wd_amount_'.$plan_id.'" '.$discount_opt_attr.' />';
                $plan_discount_amount .= '</div>';

                echo '<div class="arm_table_row">';
                echo '<div class="arm_tbl_col arm_wd_no">'. $arm_count_plan .'</div>';
                echo '<div class="arm_tbl_col arm_wd_plan_name">'. $plan_content .'</div>';
                echo '<div class="arm_tbl_col arm_wd_discount">'. $plan_discount_type .'</div>';
                echo '<div class="arm_tbl_col arm_wd_amount">'. $plan_discount_amount .'</div>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }

        function arm_wd_pro_quick_save_post( $post_id, $post ) {

            // don't save for autosave
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
                return $post_id;

            // dont save for revisions
            if ( isset( $post->post_type ) && $post->post_type == 'revision' )
                return $post_id;

            switch( $post->post_type ) {

                case 'product':
                    // release date
                    // Because this action is run in several places, checking for the array key keeps WordPress from editing
                    // data that wasn't in the form, i.e. if you had this post meta on your "Quick Edit" but didn't have it
                    // on the "Edit Post" screen.
                    $this->arm_wd_save_plan_tab_meta( $post_id );
                    break;
            }
        }

        function arm_wd_pro_bulk_save_post( $array ) {
            global $arm_wd;
            if($arm_wd->arm_wd_is_page( 'product' ) && isset($_REQUEST['post']) ) {
                foreach($_REQUEST['post'] as $post_id)
                {
                    $this->arm_wd_save_plan_tab_meta( $post_id );
                }
            }
        }

        function arm_wd_hidden_columns( $hidden, $screen, $use_defaults ) { 
            global $arm_subscription_plans;
            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('all');
            foreach ($all_plans as $plan) {
                $hidden[] = 'arm_wd_plan_'.$plan['arm_subscription_plan_id'];
                $hidden[] = 'arm_wd_discount_type_'.$plan['arm_subscription_plan_id'];
                $hidden[] = 'arm_wd_amount_'.$plan['arm_subscription_plan_id'];
            }
            return $hidden;
        }
    }
}

global $arm_wd_product;
$arm_wd_product = new arm_wd_product();
         



// apply_filters( 'hidden_columns', array $hidden, WP_Screen $screen, bool $use_defaults )
// add_filter('hidden_columns', array( $this, 'arm_wp_hidden_columns' ), 10, 3 );

// function arm_wp_hidden_columns($hidden, $screen, $use_defaults) {
//     global $ARMember;
//     $ARMember->arm_write_response('reputelog hidden => '.$hidden);
//     $ARMember->arm_write_response('reputelog screen => '.$screen);
//     $ARMember->arm_write_response('reputelog use defaults => '.$use_defaults);
// }


/** Quick edit post **//*
add_filter( 'manage_posts_columns', 'rachel_carden_managing_my_posts_columns', 10, 2 );
function rachel_carden_managing_my_posts_columns( $columns, $post_type ) {
   if ( $post_type == 'product' )
      $columns[ 'release_date' ] = 'Release Date';
   return $columns;
}

add_action( 'manage_posts_custom_column', 'rachel_carden_populating_my_posts_columns', 10, 2 );
function rachel_carden_populating_my_posts_columns( $column_name, $post_id ) {
   switch( $column_name ) {
      case 'release_date':
         echo '<div id="release_date-' . $post_id . '">' . get_post_meta( $post_id, 'release_date', true ) . '</div>';
         break;
   }
}

add_action( 'bulk_edit_custom_box', 'rachel_carden_add_to_bulk_quick_edit_custom_box', 10, 2 );
add_action( 'quick_edit_custom_box', 'rachel_carden_add_to_bulk_quick_edit_custom_box', 10, 2 );
function rachel_carden_add_to_bulk_quick_edit_custom_box( $column_name, $post_type ) {
    switch ( $post_type ) {
        case 'product':
            switch( $column_name ) {
                case 'sku':
                    ?>
                    <fieldset class="inline-edit-col-right">
                        <div class="inline-edit-group">
                            <label>
                                <span class="title">ARMembmer release date</span>
                                <input type="text" name="release_date" value="" />
                            </label>
                        </div>
                    </fieldset>
                    <?php
                  break;
            }
        break;
    }
}


add_action( 'admin_print_scripts-edit.php', 'rachel_carden_enqueue_edit_scripts' );
function rachel_carden_enqueue_edit_scripts() {
   wp_enqueue_script( 'rachel-carden-admin-edit', get_bloginfo( 'stylesheet_directory' ) . '/quick_edit.js', array( 'jquery', 'inline-edit-post' ), '', true );
}


add_action( 'save_post','rachel_carden_save_post', 10, 2 );
function rachel_carden_save_post( $post_id, $post ) {

   // don't save for autosave
   if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
      return $post_id;

   // dont save for revisions
   if ( isset( $post->post_type ) && $post->post_type == 'revision' )
      return $post_id;

   switch( $post->post_type ) {

      case 'product':

         // release date
     // Because this action is run in several places, checking for the array key keeps WordPress from editing
         // data that wasn't in the form, i.e. if you had this post meta on your "Quick Edit" but didn't have it
         // on the "Edit Post" screen.
     if ( array_key_exists( 'release_date', $_POST ) )
        update_post_meta( $post_id, 'release_date', $_POST[ 'release_date' ] );

     break;

   }

}

// define the woocommerce_product_bulk_edit_save callback 
function action_woocommerce_product_bulk_edit_save( $array ) { 
    foreach($_REQUEST['post'] as $post_id)
    {
        update_post_meta( $post_id, 'release_date', $_REQUEST[ 'release_date' ] );
    }
}; 
// add the action 
add_action( 'woocommerce_product_bulk_edit_save', 'action_woocommerce_product_bulk_edit_save', 10, 1 ); 



// ADD NEW COLUMN
function ST4_columns_head($defaults) {
    $defaults['dealer_price'] = 'Dealer Price';
    //print_r($defaults);
    return $defaults;
}
 
// SHOW THE FEATURED IMAGE
function ST4_columns_content($column_name, $post_ID) {
    if ($column_name == 'dealer_price') {
        $sale_price = get_post_meta($post_ID, '_sale_price');
        $price = get_post_meta($post_ID, '_regular_price');
        if( $sale_price[0] > 0 ){
            $sale_price = $sale_price[0] / 10;
            echo wc_price($sale_price);
        } else {
            $price = $price[0] / 10;
            echo wc_price($price);
        }
    }
}

add_filter('manage_product_posts_columns', 'ST4_columns_head');
add_action('manage_product_posts_custom_column', 'ST4_columns_content', 10, 2);

/*
// change in exists price column
function custom_columns( $column, $post_id ) {
    if ( $column == 'price') {
        echo "<br/>Dealer Price : <br/>".$post_id;

        // $terms = get_the_term_list( $post_id, 'book_author', '', ',', '' );
        // if ( is_string( $terms ) ) {
        //     echo $terms;
        // } else {
        //     _e( 'Unable to get author(s)', 'your_text_domain' );
        // }
    }
}
add_action( 'manage_product_posts_custom_column' , 'custom_columns', 10, 2 );

*/

?>