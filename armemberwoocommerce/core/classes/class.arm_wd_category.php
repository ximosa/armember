<?php
if(!class_exists('arm_wd_category')){
    
    class arm_wd_category{

        function __construct(){

            // To Set JS in category page
            add_action( 'admin_enqueue_scripts', array( $this, 'arm_wd_cat_set_js' ), 1 );
            //add_action( 'admin_print_scripts-edit-tags.php', array( $this, 'arm_wd_cat_set_js' ), 11 );
            //add_action( 'admin_print_scripts-post.php', 'arm_wd_cat_set_js', 11 );

            // To Set Custom Meta field in Add / Edit Category Form
            add_action( 'product_cat_add_form_fields', array( $this, 'arm_wd_cat_add_new_meta_field' ), 10, 2 );
            add_action( 'product_cat_edit_form_fields', array( $this, 'arm_wd_cat_edit_new_meta_field' ), 10, 2 );

            // To Save Custom Meta field data when form submitted from Add / Edit Category
            add_action( 'created_term', array( $this, 'save_taxonomy_custom_meta' ), 10, 3 );
            add_action( 'edited_terms', array( $this, 'save_taxonomy_custom_meta' ), 10, 3 );
            // add_action( 'edited_product_cat', array( $this, 'save_taxonomy_custom_meta' ), 10, 2 );  

            // Set WooCommerce Category Column Header
            add_filter('manage_edit-product_cat_columns', array( $this, 'arm_wd_cat_column_header' ), 10, 1); // 3.1.1

            // Set WooCommerce Category Column Value
            add_filter('manage_product_cat_custom_column', array( $this, 'arm_wd_cat_column_value' ), 10, 3); // 3.1.1

            // Set WooCommerce Category Quick Edit Custom Box
            add_action('quick_edit_custom_box', array( $this, 'arm_wd_cat_quick_edit' ), 10, 3);  // 3.1.1

            // Save Quick Edited Category Data
            add_action('edited_product_cat', array( $this, 'arm_wd_cat_quick_save_term_meta' ), 10, 1); // 3.1.1
        }

        function arm_wd_cat_set_js() {
            global $arm_wd_version, $arm_wd, $pagenow ;
            if( $arm_wd->arm_wd_is_page( 'category' ) && ( $pagenow == 'edit-tags.php' || $pagenow == 'term.php' ) ) {
                // wp_register_script( 'arm_wd_cat_admin_js', ARM_WD_URL . '/js/arm_wd_cat_admin.js', array( 'jquery', 'inline-edit-post' ), $arm_wd_version);
                // wp_enqueue_script( 'arm_wd_cat_admin_js' );

                wp_enqueue_script( 'arm_wd_cat_admin_js', ARM_WD_URL . '/js/arm_wd_cat_admin.js', array( 'jquery', 'inline-edit-post' ), $arm_wd_version);                
            }
        }

        function arm_wd_get_category_content( $category_id = '', $quick_edit = false ) {
            global $arm_subscription_plans, $arm_wd, $arm_count_column;
            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('all');
            $arm_product_plans = array();
            if( isset( $category_id ) && !empty( $category_id ) ) {
                $arm_product_plans = $arm_wd->arm_wd_meta_get_cat_plans( $category_id );
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
                    $arm_plan_discount = $arm_wd->arm_wd_meta_get_cat_plan_discount($category_id, $plan_id);
                    $is_plan_selected = 'checked="checked"';
                    $is_plan_active = ' active';
                    $discount_opt_attr = '';
                    $discount_type = isset($arm_plan_discount['arm_discount_type']) ? $arm_plan_discount['arm_discount_type'] : '-';
                    $arm_amount = isset($arm_plan_discount['arm_amount']) ? $arm_plan_discount['arm_amount'] : '';
                }

                $arm_count_plan++;
                $arm_class_plan_lbl = ( $quick_edit == true ) ? 'arm_wd_plans_quick_'.$plan_id : 'arm_wd_plans_'.$plan_id;
                $plan_content = '<input type="checkbox" class="arm_wd_plans arm_wd_plans_'.$plan_id.'" id="'.$arm_class_plan_lbl.'" name="arm_wd_plans[]" value="'.$plan_id.'" '.$is_plan_selected.' >';
                $plan_content .= '<label for="'.$arm_class_plan_lbl.'" class="arm_wd_plans_lbl_'.$plan_id.$is_plan_active.'">'.$plan['arm_subscription_plan_name'].'</label>';

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

        function arm_wd_cat_add_new_meta_field() {
            global $arm_subscription_plans, $arm_wd;
            echo '<fieldset class="inline-edit-col-left">';
            echo '<div class="arm_main_wrapper arm_add_category_wrapper">';
            echo '<div class="arm_title_container"><div class="arm_title"><span>'. $arm_wd->arm_wd_tab_title .'<span></div></div>';
            $this->arm_wd_get_category_content();
            echo '</div>';
            echo '</fieldset>';
        }

        function arm_wd_cat_edit_new_meta_field($term) {
            global $arm_subscription_plans, $post, $arm_wd;
            $term_id = $term->term_id;
            echo '<tr class="form-field"><th colspan="2">';
            echo '<div class="arm_main_wrapper arm_edit_category_wrapper">';
            echo '<div class="arm_title_container"><div class="arm_title"><span>'. $arm_wd->arm_wd_tab_title .'<span></div></div>';
            $this->arm_wd_get_category_content( $term_id );
            echo '</div>';
            echo '</th><tr>';
        }

        function save_taxonomy_custom_meta( $term_id, $tt_id = '', $taxonomy = '' ) {
            $this->arm_wd_cat_quick_save_term_meta( $term_id );
        }

        function arm_wd_cat_column_header( $columns ) {
            global $arm_subscription_plans;
            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('all');
            foreach ($all_plans as $plan) {
                $columns['arm_wd_plan_'.$plan['arm_subscription_plan_id']] = $plan['arm_subscription_plan_name'];
                $columns['arm_wd_discount_type_'.$plan['arm_subscription_plan_id']] = $plan['arm_subscription_plan_name']." Discount Type";
                $columns['arm_wd_amount_'.$plan['arm_subscription_plan_id']] = $plan['arm_subscription_plan_name']." Amount";
            }
            return $columns;
        }
        
        function arm_wd_cat_column_value( $empty = '', $column_name, $term_id ) {
            global $arm_subscription_plans, $arm_wd;
            $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('all');
            $arm_product_plans = $arm_wd->arm_wd_meta_get_cat_plans( $term_id );
            $arm_plan_ids = array();
            foreach ($all_plans as $plan) {
                $arm_plan_ids[] = $plan['arm_subscription_plan_id'];
            }
            foreach ($all_plans as $plan) {
                $plan_id = $plan['arm_subscription_plan_id'];
                if( !empty( $arm_product_plans ) && in_array( $plan_id, $arm_product_plans ) ){
                    $arm_plan_discount = $arm_wd->arm_wd_meta_get_cat_plan_discount($term_id, $plan_id);
                    $arm_plan_is_discount = 'Yes';
                    $discount_type = isset($arm_plan_discount['arm_discount_type']) ? $arm_plan_discount['arm_discount_type'] : 'fix';
                    $arm_amount = isset($arm_plan_discount['arm_amount']) ? $arm_plan_discount['arm_amount'] : '';  
                } else {
                    $arm_plan_is_discount = 'No';
                    $discount_type = '-';
                    $arm_amount = '-';
                }

                if( $column_name == 'arm_wd_plan_'.$plan_id ){
                    echo '<input type="hidden" id="arm_plan_id_'.$term_id.'" name="arm_plan_id_'.$term_id.'" value="'.implode(',',$arm_plan_ids).'" />';
                    echo '<div id="arm_wd_plan_'.$plan_id.'_'.$term_id.'">'.$arm_plan_is_discount.'</div>';
                }

                if( $column_name == 'arm_wd_discount_type_'.$plan_id ){
                    echo '<div id="arm_wd_discount_type_'.$plan_id.'_'.$term_id.'">'.$discount_type.'</div>';
                }

                if( $column_name == 'arm_wd_amount_'.$plan_id ){
                    echo '<div id="arm_wd_amount_'.$plan_id.'_'.$term_id.'">'.$arm_amount.'</div>';
                }
            }
        }
        
        function arm_wd_cat_quick_edit( $column_name, $screen, $name ) {   
            if( $name == 'product_cat' ){
                global $arm_subscription_plans, $arm_wd, $arm_count_column1;
                $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('all');
                if($arm_count_column1 <= 0)
                {
                    foreach ($all_plans as $plan) {
                        $plan_id = $plan['arm_subscription_plan_id'];
                        if( $column_name == 'arm_wd_plan_'.$plan_id ){
                            $arm_count_column1++;
                            echo '<fieldset>';
                            echo '<div class="arm_main_wrapper arm_quick_edit_category_wrapper">';
                            echo '<div class="arm_title_container"><div class="arm_title"><span>' . $arm_wd->arm_wd_tab_title . '<span></div></div>';
                            $this->arm_wd_get_category_content( '', $quick_edit = true );
                            echo '</div>';
                            echo '</fieldset>';
                        }
                    }
                }
            }
        }

        function arm_wd_cat_quick_save_term_meta( $term_id ) {
            global $arm_wd;
            // Save ARMember Plans
            $plans = isset($_REQUEST['arm_wd_plans']) ? $_REQUEST['arm_wd_plans'] : array();
            $discount_type = isset($_REQUEST['arm_discount_type']) ? $_REQUEST['arm_discount_type'] : array();
            $arm_amount = isset($_REQUEST['arm_amount']) ? $_REQUEST['arm_amount'] : array();

            if (isset($plans) && !empty($plans)) {
                $arm_wd->arm_wd_meta_add_update_cat_plans( $term_id, $plans );
                foreach ($plans as $plan_id) {
                    $plan_discount_type = (isset($discount_type[$plan_id]) && !empty($discount_type) ) ? $discount_type[$plan_id] : '';
                    $plan_amount = (isset($arm_amount[$plan_id]) && !empty($arm_amount) ) ? $arm_amount[$plan_id] : '';
                    if( $plan_discount_type != '' && $plan_amount >= 0 && $plan_amount != '' ) {
                        $arm_wd->arm_wd_meta_add_update_cat_plan_discount( $term_id, $plan_id, $plan_discount_type, $plan_amount );
                    }
                }
            }
            else
            {
                $arm_wd->arm_wd_meta_delete_cat($term_id);
            }
        }        
    }
}

global $arm_wd_category;
$arm_wd_category = new arm_wd_category();



/*** quick edit category ****//*
function my_column_header($columns)
{
    $columns['start-date'] = __('Start Date', 'my_plugin');
    $columns['end-date'] = __('End Date', 'my_plugin');
    return $columns;
}
add_filter('manage_edit-product_cat_columns', 'my_column_header', 10, 1);

function my_column_value($empty = '', $custom_column, $term_id) 
{
    return esc_html(get_term_meta($term_id, $custom_column, true));     
}
add_filter('manage_product_cat_custom_column', 'my_column_value', 10, 3);

function my_quick_edit_custom_box($column_name, $screen, $name)
{   
    if($name != 'product_cat' && ($column_name != 'start-date' || $column_name != 'end-date')) return false;
?>
    <fieldset>
        <div id="my-custom-content" class="inline-edit-col">
            <label>
                <span class="title"><?php if($column_name == 'start-date') _e('Start Date', 'my_plugin'); else _e('End Date', 'my_plugin'); ?></span>
                <span class="input-text-wrap"><input type="text" name="<?php echo $column_name; ?>" class="ptitle" value=""></span>
            </label>
        </div>
    </fieldset>
<?php 
}
add_action('quick_edit_custom_box', 'my_quick_edit_custom_box', 10, 3); 


function my_save_term_meta($term_id)
{
    $allowed_html = array(
        'b' => array(),
        'em' => array (), 
        'i' => array (),
        'strike' => array(),
        'strong' => array(),
    );
    if(isset($_POST['start-date'])) 
        update_term_meta($term_id, 'start-date', wp_kses($_POST['start-date'], $allowed_html)); 
    if(isset($_POST['end-date'])) 
        update_term_meta($term_id, 'end-date', wp_kses($_POST['end-date'], $allowed_html));     
}

add_action('edited_product_cat', 'my_save_term_meta', 10, 1);

function my_add_admin_scripts()
{
    global $pagenow;
     
    if($pagenow == 'edit-tags.php' && (isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'product_cat') && !isset($_GET['action']))
    {
        wp_register_script(
            'quick-edit-js',
            plugins_url('quick_edit.js', __FILE__),
            array('jquery')
        );
        wp_enqueue_script('quick-edit-js');
    }
}
add_action('admin_enqueue_scripts', 'my_add_admin_scripts', 10, 1);
*/

?>