<?php
if(!class_exists('arm_wd_front_product')){
    
    class arm_wd_front_product{

        function __construct(){

            // For change Simple and External / Affiliate Product Price
            add_filter('woocommerce_product_get_price', array( $this, 'arm_wd_change_simple_prodcut_price' ), 10, 2);

            // For chagne Variable Product Price
            add_filter( 'woocommerce_product_variation_get_price', array( $this, 'arm_wd_change_variable_prodcut_price' ), 99, 2 );
            // add_filter( 'woocommerce_variable_sale_price_html', array( $this, 'wc_wc20_variation_price_format' ), 10, 2 );
            // add_filter( 'woocommerce_variable_price_html', array( $this, 'wc_wc20_variation_price_format' ), 10, 2 );

            // For Order place than display this is our membership user.
            // https://wisdmlabs.com/blog/add-custom-data-woocommerce-order/
            add_action( 'woocommerce_add_order_item_meta',array( $this, 'wdm_add_values_to_order_item_meta' ), 10, 2 );
            
        }

        function wdm_add_values_to_order_item_meta($item_id, $values)
        {
            global $woocommerce,$wpdb;
            if( !empty($item_id) && !empty($values) && !empty($values['product_id']) && !is_admin() && !current_user_can('administrator') && is_user_logged_in() )
            {
                $product = wc_get_product($values['product_id']);
                $arm_post_id = $product->get_id();
                $arm_product_type = $product->get_type();
                $sale_price = 0;
                $arm_discount_price = 0;
                if($arm_product_type == 'variable') {
                    $product_attr = wc_get_product($values['variation_id']);
                    $price = $product_attr->get_regular_price();
                    $sale_price = $product_attr->get_sale_price();
                    $arm_discount_price = $this->arm_wd_change_variable_prodcut_price( $sale_price, $product_attr );
                } else {
                    $price = $product->get_regular_price();
                    $sale_price = $product->get_sale_price();
                    $arm_discount_price = $this->arm_wd_change_simple_prodcut_price( $sale_price, $product );
                }

                if( $arm_discount_price <= 0 ){
                    $arm_discount_price = $sale_price;
                }

                //$arm_buy_price = isset($values['line_total']) ? $values['line_total'] : $sale_price;
                $arm_discount = $price - $arm_discount_price;
                if($arm_discount > 0 && $sale_price != $arm_discount_price) {
                    wc_add_order_item_meta($item_id,'ARM Regular Price', $price);
                    wc_add_order_item_meta($item_id,'ARM Discount', $arm_discount);  
                }
            }
        }

        function arm_wd_change_simple_prodcut_price( $saleprice, $product ) {
            global $post, $blog_id, $arm_wd;
            $price = $saleprice;
            if(!is_admin() && !current_user_can('administrator') && is_user_logged_in()) {
                if(is_object($post) ) {
                    $arm_post_id = $product->get_id();
                    $arm_product_type = $product->get_type();
                    $arm_user_id = get_current_user_id();
                    $arm_user_plan_id = get_user_meta( $arm_user_id, 'arm_user_plan_ids', true );
                    $arm_user_plan_id = ( !empty($arm_user_plan_id) && is_array($arm_user_plan_id) ) ? $arm_user_plan_id : array();
                    $arm_product_plan_id = $arm_wd->arm_wd_meta_get_product_plans( $arm_post_id );
                    $arm_discount_plan = array_intersect( $arm_user_plan_id, $arm_product_plan_id );
                    $discount_amount = array();
                    if( !empty( $arm_discount_plan ) )
                    {
                        $price = $product->get_regular_price();
                        foreach ( $arm_discount_plan as $plan_id ) {
                            $arm_plan_discount = $arm_wd->arm_wd_meta_get_plan_discount( $arm_post_id, $plan_id );
                            if( isset($arm_plan_discount['arm_discount_type']) && !empty($arm_plan_discount['arm_discount_type']) && isset($arm_plan_discount['arm_amount']) ){

                                switch ( $arm_plan_discount['arm_discount_type'] ) {
                                    case 'fix':
                                        $discount_amount[$plan_id] = $arm_plan_discount['arm_amount'];
                                        break;
                                    case 'per':
                                        $discount = ( $price * $arm_plan_discount['arm_amount'] ) / 100;
                                        $discount_amount[$plan_id] = $discount;
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }
                        if( !empty( $discount_amount ) ) {
                            $price = $price - max($discount_amount);
                            // $product->set_price($price);
                            // $product->set_regular_price($price);
                            $product->set_sale_price($price);
                        }
                    } else {
                        
                        // category discount code here...
                        $arm_category_ids = $product->get_category_ids();
                        $arm_category_plan_ids = array();
                        foreach ($arm_category_ids as $arm_cat_id) {
                            $arm_cat_plan_ids = $arm_wd->arm_wd_meta_get_cat_plans( $arm_cat_id );
                            $arm_discount_plan = array_intersect( $arm_user_plan_id, $arm_cat_plan_ids );
                            if( !empty( $arm_discount_plan ) )
                            {
                                $price = $product->get_regular_price();
                                foreach ( $arm_discount_plan as $plan_id ) {
                                    $arm_plan_discount = $arm_wd->arm_wd_meta_get_cat_plan_discount( $arm_cat_id, $plan_id );
                                    if( isset($arm_plan_discount['arm_discount_type']) && !empty($arm_plan_discount['arm_discount_type']) && isset($arm_plan_discount['arm_amount']) ){

                                        switch ( $arm_plan_discount['arm_discount_type'] ) {
                                            case 'fix':
                                                $discount_amount[$arm_cat_id."_".$plan_id] = $arm_plan_discount['arm_amount'];
                                                break;
                                            case 'per':
                                                $discount = ( $price * $arm_plan_discount['arm_amount'] ) / 100;
                                                $discount_amount[$arm_cat_id."_".$plan_id] = $discount;
                                                break;
                                            default:
                                                break;
                                        }
                                    }
                                }
                            }
                        }
                        if( !empty( $discount_amount ) ) {
                            $price = $price - end($discount_amount);
                            // $product->set_price($price);
                            // $product->set_regular_price($price);
                            $product->set_sale_price($price);
                        }
                    }
                }
                // echo "<br/>".$arm_post_id."===>".$price." type => ".$product->get_type();
                // $products = (array) $product;
                // echo "<pre>";
                // print_r($products);
                // echo "</pre>";
            } 
            return $price;
        }

        // for change variable product price change 
        function arm_wd_change_variable_prodcut_price( $saleprice, $variation ) {
            global $post, $arm_wd;
            $price = $saleprice;
            if(!is_admin() && !current_user_can('administrator') && is_user_logged_in()) {
                $arm_post_id = $variation->get_parent_id();
                $arm_variation_id = $variation->get_id();
                $product = wc_get_product($arm_variation_id);
                $arm_user_id = get_current_user_id();
                $arm_user_plan_id = get_user_meta( $arm_user_id, 'arm_user_plan_ids', true );
                $arm_product_plan_id = $arm_wd->arm_wd_meta_get_product_plans( $arm_post_id );
                $arm_discount_plan = array_intersect( $arm_user_plan_id, $arm_product_plan_id );
                $discount_amount = array();
                if( !empty( $arm_discount_plan ) )
                {
                    $price = $product->get_regular_price();
                    foreach ( $arm_discount_plan as $plan_id ) {
                        $arm_plan_discount = $arm_wd->arm_wd_meta_get_plan_discount( $arm_post_id, $plan_id );
                        if( isset($arm_plan_discount['arm_discount_type']) && !empty($arm_plan_discount['arm_discount_type']) && isset($arm_plan_discount['arm_amount']) ){

                            switch ( $arm_plan_discount['arm_discount_type'] ) {
                                case 'fix':
                                    $discount_amount[$plan_id] = $arm_plan_discount['arm_amount'];
                                    break;
                                case 'per':
                                    $discount = ( $price * $arm_plan_discount['arm_amount'] ) / 100;
                                    $discount_amount[$plan_id] = $discount;
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                    if( !empty( $discount_amount ) ) {
                        $price = $price - max($discount_amount);
                    }
                } else {
                    // category discount code here...
                    $product_cat = wc_get_product($arm_post_id);
                    $arm_category_ids = $product_cat->get_category_ids();
                    $arm_category_plan_ids = array();
                    foreach ($arm_category_ids as $arm_cat_id) {
                        $arm_cat_plan_ids = $arm_wd->arm_wd_meta_get_cat_plans( $arm_cat_id );
                        $arm_discount_plan = array_intersect( $arm_user_plan_id, $arm_cat_plan_ids );
                        if( !empty( $arm_discount_plan ) )
                        {
                            $price = $product->get_regular_price();
                            foreach ( $arm_discount_plan as $plan_id ) {
                                $arm_plan_discount = $arm_wd->arm_wd_meta_get_cat_plan_discount( $arm_cat_id, $plan_id );
                                if( isset($arm_plan_discount['arm_discount_type']) && !empty($arm_plan_discount['arm_discount_type']) && isset($arm_plan_discount['arm_amount']) ){

                                    switch ( $arm_plan_discount['arm_discount_type'] ) {
                                        case 'fix':
                                            $discount_amount[$arm_cat_id."_".$plan_id] = $arm_plan_discount['arm_amount'];
                                            break;
                                        case 'per':
                                            $discount = ( $price * $arm_plan_discount['arm_amount'] ) / 100;
                                            $discount_amount[$arm_cat_id."_".$plan_id] = $discount;
                                            break;
                                        default:
                                            break;
                                    }
                                }
                            }
                        }
                    }
                    if( !empty( $discount_amount ) ) {
                        $price = $price - end($discount_amount);
                    }
                }
            }
            return $price;
        }


    }
}

global $arm_wd_front_product;
$arm_wd_front_product = new arm_wd_front_product();

/************ woocommerce ************//*
// for change simple and external / affiliate product price change 
function return_custom_price1($price, $product) {
    global $post, $blog_id;
    if(!is_admin() && !current_user_can('administrator')) {
        if(is_object($post) ) { 
        //echo $post->ID."->".$price."<br/>";
        //$price = get_post_meta($post->ID, '_regular_price');
        // $post_id = $post->ID;
        // $price = ($price[0]*2.5);
            $price = $price / 10;
        }
    } 
    return $price;
}
add_filter('woocommerce_product_get_price', 'return_custom_price1', 10, 2);

// for change variable product price change 
function m_varient_price( $price, $variation ) {
    global $post;
    if(is_object($post)){
        //echo $post->ID."<br/>";
        $price = $price / 10;
    }
    return $price;
}
add_filter( 'woocommerce_product_variation_get_price', 'm_varient_price', 99, 2 );



/*
add_filter( 'woocommerce_available_variation', 'my_variation', 10, 3);
function my_variation( $data, $product, $variation ) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";

    $display_price = $data['display_price'] / 10;
    $data['display_price'] = $display_price;
    $data['price_html'] = wc_price($display_price);
    return $data;
}
*/

/*
// 1. Change the amount
function return_custom_price($price, $product) {
    global $post, $woocommerce;
    $post_id = $post->ID;
    // Prevent conflicts with order pages and products, peace of mind.
    if($post_id == '9' || $post_id == '10' || $post_id == '17' || $post_id == '53' || $post_id == ''){
        // cart, checkout, , order received, order now
        $post_id = $product->id;
    }
    $user_country = $_SESSION['user_location'];
    $get_user_currency = strtolower($user_country.'_price');
    // If the IP detection is enabled look for the correct price
    if($get_user_currency!=''){
        $new_price = get_post_meta($post_id, $get_user_currency, true);
        if($new_price==''){
            $new_price = $price;
        }
    }
    if( is_admin() && $_GET['post_type']=='product' ){
        return $price;
    } else {
        return $new_price;
    }
}
add_filter('woocommerce_product_get_price', 'return_custom_price', 10, 2);

// 2. Update the order meta with currency value and the method used to capture it
function update_meta_data_with_new_currency( $order_id ) {
    if($_SESSION['user_order_quantity']>=2){
        update_post_meta( $order_id, 'group_ticket_amount', $_SESSION['user_order_quantity'] );
        update_post_meta( $order_id, 'master_of', '' );

    }
    update_post_meta( $order_id, 'currency_used', $_SESSION['user_currency'] );
    update_post_meta( $order_id, 'currency_method', $_SESSION['currency_method'] );
}
add_action( 'woocommerce_checkout_update_order_meta', 'update_meta_data_with_new_currency' );*/

?>