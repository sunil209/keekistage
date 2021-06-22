<?php
// Add custom Theme Functions here



add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tabs', 98 );

function woo_remove_product_tabs( $tabs ) {

    unset( $tabs['description'] );          // Remove the description tab

    return $tabs;

}

add_filter( 'woocommerce_product_tabs', 'woo_new_tab_warranty' );
function woo_new_tab_warranty( $tabs ) {
    
    // Adds the new tab
    
    $tabs['warranty'] = array(
        'title'     => __( 'Warranty', 'woocommerce' ),
        'priority'  => 40,
        'callback'  => 'woo_warranty_tab_content'
    );

    return $tabs;

}
function woo_warranty_tab_content() {

    // The new tab content

    echo '<a href="/warranty" target="_blank" style="text-transform:none;text-decoration:underline;font-size:13px;">Click here</a> to find out about our product warranty';
    
}

add_filter( 'woocommerce_product_tabs', 'woo_new_tab_delivery' );
function woo_new_tab_delivery( $tabs ) {
    
    // Adds the new tab
    
    $tabs['delivery'] = array(
        'title'     => __( 'Delivery', 'woocommerce' ),
        'priority'  => 50,
        'callback'  => 'woo_delivery_tab_content'
    );

    return $tabs;

}
function woo_delivery_tab_content() {

    // The new tab content

    echo '<p><a href="/faqs" target="_blank" style="text-transform:none;text-decoration:underline;font-size:16px;">Click here</a> to find out about delivery</p>';
    echo '<p><strong><em>When can I expect to receive my order? </em></strong></p>';
    echo '<p>Please allow the following times, as a general guide:</p>';
    echo '<p><strong>For stocked items:<br /> <br /> </strong>Metropolitan areas: Please allow between 10 and 15 working days depending on the size and complexity of your order.<br /> Remote locations: Please allow between 15 and 20 working days depending on the size and complexity of your order.</p>';
    echo '<p><strong>For furniture and made to order items:&nbsp;</strong>As a general guide, most Australian made furniture items on this site take at least 10 weeks to be custom made to your requirements.&nbsp;</p>';
    echo '<p><strong>Please note:</strong> <span style="text-decoration: underline;">We are unable to deliver to PO Box addresses.</span></p>';
    echo '<p>Please ensure that someone will be present to sign for your parcel upon delivery, alternatively you may provide authority for the parcel to be left without a signature in the notes when you place your order. We highly recommend you have the parcel sent to a work address or an address where there will be someone available to receive the delivery.</p>';
    
}


//Shortcode to place category image header into template
add_shortcode( 'wpv-post-wooimage', 'wpv_wooimage');
function wpv_wooimage(){
    // verify that this is a product category page
    if (is_product_category()){
        global $wp_query;
        // get the query object
        $cat = $wp_query->get_queried_object();
        // get the thumbnail id user the term_id
        $thumbnail_id = get_woocommerce_term_meta( $cat->term_id, 'thumbnail_id', true ); 
        // get the image URL
        $image = wp_get_attachment_url( $thumbnail_id ); 
        // print the IMG HTML
        echo '<img src="'.$image.'" alt="" width="100%" height="365" />';
    }
}


//Selects product thumbnails for category selection page
function woocommerce_subcategory_thumbnail( $category ) {
    global $wpdb;

    $small_thumbnail_size   = apply_filters( 'single_product_small_thumbnail_size', 'shop_catalog' );

    $children = get_terms( $category->taxonomy, array(
        'parent'    => $category->term_id,
        'hide_empty' => false
    ) );
    if(!$children) { // get_terms will return false if tax does not exist or term wasn't found.
        //Term has children

        //Query to get child products,
        $query = "SELECT ID FROM `".$wpdb->prefix."posts` AS p
                LEFT JOIN `".$wpdb->prefix."term_relationships` AS tr ON tr.object_id = p.ID
                LEFT JOIN ".$wpdb->prefix."term_taxonomy as tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'
                WHERE tt.term_id = %d AND p.post_type = 'product' AND ID IN ".selected_product_tag(). "
                ORDER BY p.post_title ASC
                LIMIT 0,1";
        //Could use get_posts, this is more direct, and faster. Optimisation required as called so often.
        $result = $wpdb->get_results(
            $wpdb->prepare($query,
                $category->term_id
            )
        );
    }

    if(!empty($result)) {
        $thumbnail_id = get_post_meta( $result[0]->ID, '_thumbnail_id', true );
    } else {
        //Fall back to category id if this is sub category
        $query = "SELECT ID FROM `".$wpdb->prefix."posts` AS p
        LEFT JOIN `".$wpdb->prefix."term_relationships` AS tr ON tr.object_id = p.ID
        LEFT JOIN ".$wpdb->prefix."term_taxonomy as tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'
        LEFT JOIN ".$wpdb->prefix."terms as t ON t.term_id = tt.term_id
        WHERE tt.parent = %d AND p.post_type = 'product' AND ID IN ".selected_product_tag(). "
        ORDER BY t.name ASC, p.post_title ASC
        LIMIT 0,1";

        $result = $wpdb->get_results(
            $wpdb->prepare($query,
                $category->term_id
            )
        );
        if(!empty($result)) {
            $thumbnail_id = get_post_meta( $result[0]->ID, '_thumbnail_id', true );
        } else {
                    $query = "SELECT ID FROM `".$wpdb->prefix."posts` AS p
                    LEFT JOIN `".$wpdb->prefix."term_relationships` AS tr ON tr.object_id = p.ID
                    LEFT JOIN ".$wpdb->prefix."term_taxonomy as tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'
                    WHERE tt.term_id = %d AND p.post_type = 'product'
                    ORDER BY p.post_title ASC
                    LIMIT 0,1";
            //Could use get_posts, this is more direct, and faster. Optimisation required as called so often.
            $result = $wpdb->get_results(
                $wpdb->prepare($query,
                    $category->term_id
                )
            );
            $thumbnail_id = get_post_meta( $result[0]->ID, '_thumbnail_id', true );
        }
    }
    if ( $thumbnail_id ) {
        //echo $thumbnail_id;
        $image = wp_get_attachment_image_src( $thumbnail_id, $small_thumbnail_size  );
        if($image !== false) {
            $image = $image[0];
        }
    }
    if(empty($image)) {
        $image = woocommerce_placeholder_img_src();
    }

    if ( $image )
        echo '<span class="resize-fit-center"><img src="' . $image . '" alt="' . $category->name . '" /></span>';
}



function selected_product_tag() {
    $query = new WP_Query( array('product_tag' => 'selected', 'post_type' => 'product', 'posts_per_page' => -1));
        while( $query->have_posts() ): $query->next_post();
        $selimg[] = $query->post->ID;
        endwhile; wp_reset_query(); wp_reset_postdata();
        return implodeAssoc(", ",$selimg);

}


function implodeAssoc($glue,$arr) 
{ 
   if(isset($arr)) { 
        $values=array_values($arr);
        return("(".implode($glue,$values)).")"; 
    }
}; 



function themeSetup()
{
    add_theme_support('post-thumbnails');
    add_image_size('gallery-custom-img', 300, 300, true);
   // add_image_size('small', 256, 158);
}
add_action('after_setup_theme', 'themeSetup');

// Addes custom sizes to Media Library.
function addMySizes($defaultSizes)
    {
        $mySizes = array
        (
            'gallery-custom-img' => 'Gallery'   
        );  

        return array_merge($defaultSizes, $mySizes);
    }  
add_filter('image_size_names_choose', 'addMySizes');

//change sort name
add_filter( 'gettext', 'theme_sort_change', 20, 3 );
function theme_sort_change( $translated_text, $text, $domain ) {

    if ( is_woocommerce() ) {

        switch ( $translated_text ) {

            case 'Sort by newness' :

                $translated_text = __( 'Sort by most recent', 'theme_text_domain' );
                break;
        }

    }

    return $translated_text;
}


// Modify the default WooCommerce orderby dropdown
//
// Options: menu_order, popularity, rating, date, price, price-desc
function my_woocommerce_catalog_orderby( $orderby ) {
    unset($orderby["popularity"]);
    unset($orderby["rating"]);
    return $orderby;
}
add_filter( "woocommerce_catalog_orderby", "my_woocommerce_catalog_orderby", 20 );


add_filter( 'woocommerce_cart_item_name', 'add_sku_in_cart', 20, 3);


function add_sku_in_cart( $title, $values, $cart_item_key ) {
    $sku = $values['data']->get_sku();
    return $sku ? $title . "<br>" . sprintf("%s", $sku) : $title;
}


// rename the "Have a Coupon?" message on the checkout page
function woocommerce_rename_coupon_message_on_checkout() {
    return 'Have a Promo Code?' . ' <a href="#" class="showcoupon">' . __( 'Click here to enter your code', 'woocommerce' ) . '</a>';
}
//add_filter( 'woocommerce_checkout_coupon_message', 'woocommerce_rename_coupon_message_on_checkout' );


// rename the coupon field on the checkout page
function woocommerce_rename_coupon_field_on_checkout( $translated_text, $text, $text_domain ) {
    // bail if not modifying frontend woocommerce text
    if ( is_admin() || 'woocommerce' !== $text_domain ) {
        return $translated_text;
    }
    if ( 'Coupon code' === $text ) {
        $translated_text = 'Enter Promo Code';
    
    } elseif ( 'Apply Coupon' === $text ) {
        $translated_text = 'Apply Promo Code';
    }  elseif ( 'Coupon' === $text ) {
        $translated_text = 'Promo Code';
    }
    return $translated_text;
}
add_filter( 'gettext', 'woocommerce_rename_coupon_field_on_checkout', 10, 3 );

add_action('woocommerce_after_wishlist_contents', 'flatsome_continue_shopping', 0);

if(isMobile()) {
        add_filter( 'the_title', 'shorten_my_title', 10, 2 );
}

function isMobile() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}
function shorten_my_title( $title, $id ) {
    if ( !is_product() && get_post_type( $id ) === 'product' && strlen( $title ) > 30 ) {
        return substr( $title, 0, 30 ) . '...'; // change 50 to the number of characters you want to show
    } else {
        return $title;
    }
}

/* Hook call just after coupons created save or updated on 24th OCT 2016 */

function keeki_tweake_while_save_coupon($post_id, $post, $update) {
    // If this is a revision, don't send the email.


  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }


    if($post_id != "" && $_POST['post_type'] == 'shop_coupon')
        {
                $prd_cats = array_filter( array_map( 'intval', $_POST['product_categories']));
                $prd_cats_ext = array_filter( array_map( 'intval', $_POST['exclude_product_categories']));
                $p_category = array();
                $p_e_category = array();
                $taxonomy = 'product_cat';
                if(!empty($prd_cats))
                    {
                        foreach ($prd_cats as $key => $value) {
                            $term = get_term($value, $taxonomy);
                            $p_category[] = $term->slug;
                        }
                    }
                if(!empty($prd_cats_ext))
                    {
                        foreach ($prd_cats_ext as $key => $value) {
                            $term = get_term($value, $taxonomy);
                            $p_e_category[] = $term->slug;
                        }
                    }
                update_post_meta( $post_id, 'product_categories_slug', $p_category);
                update_post_meta( $post_id, 'exclude_product_categories_slug', $p_e_category);
        } 

        if($post_id != "" && $_POST['post_type'] == 'product'){
            
            global $wpdb;
            $productmeta = new WC_Product($post_id);
            $sku = '';
            $yoast_title = '';
            $yoast_metadescripton = '';
            $sku = $productmeta->sku;
            $table_name = $wpdb->prefix. "yoastseo_custom";
            //$yoast_title = get_post_meta( $post_id, '_yoast_wpseo_title', true);
            //$yoast_metadescripton = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true);    
            $yoast_title = $_POST['yoast_wpseo_title'];
            $yoast_metadescripton = $_POST['yoast_wpseo_metadesc'];
            // Check if sku already exists in the table then update else insert
            
            $fetchResult = $wpdb->get_row( "SELECT * FROM $table_name WHERE yoastseo_sku_id = '$sku' " );
            if($fetchResult)
                {
                    $wpdb->query( 
                           " UPDATE $table_name SET 
                             yoastseo_meta_title = '$yoast_title',
                             yoastseo_meta_descriptions = '$yoast_metadescripton'
                             WHERE yoastseo_sku_id = '$sku'"
                         ); 
                }
            else
                {
                     $wpdb->insert($table_name , 
                              array(
                                      'yoastseo_sku_id' => $sku,
                                      'yoastseo_meta_title' => $yoast_title,
                                      'yoastseo_meta_descriptions' => $yoast_metadescripton
                                   )
                               );
                }
        }

     
        
}
// add_action( 'save_post', 'keeki_tweake_while_save_coupon', 10, 3 );



/* Create Table  to temporary store Yoast Title/ Description */

function yoastseo_create_table() {

    global $wpdb;
    $table_name = $wpdb->prefix. "yoastseo_custom";
    global $charset_collate;
    $charset_collate = $wpdb->get_charset_collate();
    global $db_version;
    
    if( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") !=  $table_name)
        {   
                $create_sql = "CREATE TABLE " . $table_name . " (
                yoastseo_uniq_id INT(11) NOT NULL auto_increment,
                yoastseo_sku_id VARCHAR(20) NOT NULL ,
                yoastseo_meta_title VARCHAR(300) NOT NULL ,
                yoastseo_meta_descriptions VARCHAR(400) NOT NULL,
                UNIQUE KEY yoastseo_sku_id (yoastseo_sku_id),
                PRIMARY KEY (yoastseo_uniq_id))$charset_collate;";

                require_once(ABSPATH . "wp-admin/includes/upgrade.php");
                dbDelta( $create_sql );
        }

    
    //register the new table with the wpdb object
    if (!isset($wpdb->yoastseo_custom))
        {
            $wpdb->yoastseo_custom = $table_name;
            //add the shortcut so you can use $wpdb->stats
            $wpdb->tables[] = str_replace($wpdb->prefix, '', $table_name);
        }

}
add_action( 'init', 'yoastseo_create_table');





/* Custom Price Filteration View */

function keeki_rrp_sale_price_html( $price, $product ) {
      if ( $product->is_on_sale() ) :
        $has_sale_text = array(
          '<del>' => '<span class="offerPrice">Was: </span><del> ',
          '<ins>' => '<br><span class="regularPrice">Now: </span><ins>'
        );
        $return_string = str_replace(array_keys( $has_sale_text ), array_values( $has_sale_text ), $price);
      else :
        $has_sale_text = array(
          '<ins>' => '<ins>'
        );
        $return_string = str_replace(array_keys( $has_sale_text ), array_values( $has_sale_text ), $price);
      endif;
      return $return_string;
}
add_filter( 'woocommerce_get_price_html', 'keeki_rrp_sale_price_html', 100, 2 );




add_action('wp_enqueue_scripts', 'override_woo_frontend_scripts');
function override_woo_frontend_scripts() {
    wp_deregister_script('wc-checkout');
    wp_enqueue_script('wc-checkout', get_stylesheet_directory_uri() . '/woocommerce/js/checkout.js', array('jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n'), null, true);
}



// hide coupon field on checkout page
/*
function hide_coupon_field_on_checkout( $enabled ) {
    if ( is_checkout() ) {
        $enabled = false;
    }
    else
    {
        $enabled = true;
    }
    return $enabled;
}
*/
// add_filter( 'woocommerce_coupons_enabled', 'hide_coupon_field_on_checkout' );




/* Online Exclusive Product */

/* Cluprite -- Need to change while moving data */

// add_action( 'woocommerce_product_options_general_product_data', 'woo_add_custom_general_fields' );

function woo_add_custom_general_fields()
        {
                global $woocommerce, $post;
                echo '<div class="options_group">';
                woocommerce_wp_checkbox(
                            array(
                                'id' => '_onlineExlusivecheckbox[' . $post->ID . ']',
                                'wrapper_class' => 'checkbox_class',
                                'label' => __('Online Exclusive', 'woocommerce' ),
                                'description' => __( 'Tick for make online exclusive ', 'woocommerce' ),
                                'value'       => get_post_meta( $post->ID, '_onlineExlusivecheckbox', true ),
                            )
                        );
                echo '';
      

        }

/* Disabled it Feb Cluprit */
// add_action('woocommerce_process_product_meta', 'woocommerce_product_custom_fields_save');
function woocommerce_product_custom_fields_save($post_id)
{
        // Checkbox
        $checkbox = isset( $_POST['_onlineExlusivecheckbox'][ $post_id ] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_onlineExlusivecheckbox', $checkbox );

                if($post_id != "")
                    {

                        global $wpdb;
                        global $woocommerce;
                        $productmeta = new WC_Product($post_id);
                        $sku = '';
                        $sku = $productmeta->sku;
                        $table_name = $wpdb->prefix. "onlineexclusive_custom";
                        $ifexclusive  = get_post_meta( $post_id, '_onlineExlusivecheckbox',true);



                        // Check if sku already exists in the table then update else insert
                        
                        $fetchResult = $wpdb->get_row( "SELECT * FROM $table_name WHERE onlineexlusive_uniq_id_sku_id = '$sku' " );
                        echo "SELECT * FROM $table_name WHERE onlineexlusive_uniq_id_sku_id = '$sku' ";
                        echo "UPDATE $table_name SET  onlineexlusive_if = ".$ifexclusive." WHERE onlineexlusive_uniq_id_sku_id = ".$sku."";
                        if($fetchResult)
                            {
                                $wpdb->query("UPDATE $table_name SET  onlineexlusive_if = '".$ifexclusive."' WHERE onlineexlusive_uniq_id_sku_id = '".$sku."'"); 
                            }
                        else
                            {
                                 $wpdb->insert($table_name , 
                                          array(
                                                  'onlineexlusive_uniq_id_sku_id' => $sku,
                                                  'onlineexlusive_if' => $ifexclusive
                                               )
                                           );
                            }
                    }

}


/* Create Table  to temporary store Yoast Title/ Description */

function onlineExclusive_create_table() {

    global $wpdb;
    $table_name = $wpdb->prefix. "onlineexclusive_custom";
    global $charset_collate;
    $charset_collate = $wpdb->get_charset_collate();
    global $db_version;
    
    if( $wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") !=  $table_name)
        {   
                $create_sql = "CREATE TABLE " . $table_name . " (
                onlineexlusive_uniq_id INT(11) NOT NULL auto_increment,
                onlineexlusive_uniq_id_sku_id VARCHAR(20) NOT NULL ,
                onlineexlusive_if VARCHAR(300) NOT NULL ,
                UNIQUE KEY onlineexlusive_uniq_id_sku_id (onlineexlusive_uniq_id_sku_id),
                PRIMARY KEY (onlineexlusive_uniq_id))$charset_collate;";

                require_once(ABSPATH . "wp-admin/includes/upgrade.php");
                dbDelta( $create_sql );
        }
    
    
    //register the new table with the wpdb object
    if (!isset($wpdb->yoastseo_custom))
        {
            $wpdb->yoastseo_custom = $table_name;
            //add the shortcut so you can use $wpdb->stats
            $wpdb->tables[] = str_replace($wpdb->prefix, '', $table_name);
        }

}
add_action( 'init', 'onlineExclusive_create_table');



add_filter( 'wc_product_sku_enabled', 'keeki_remove_sku' );
 
function keeki_remove_sku( $enabled ) {
    if ( is_page( 'cart' ) || is_cart() ) {
        return false;
    }
 
    return $enabled;
}


/* Show thumbnail on checkout page */

// Product thumbnail in checkout
add_filter( 'woocommerce_cart_item_name', 'product_thumbnail_in_checkout', 20, 3 );
function product_thumbnail_in_checkout( $product_name, $cart_item, $cart_item_key ){
    if ( is_checkout() )
    {
        $thumbnail   = $cart_item['data']->get_image(array( 56, 56));
        $image_html  = '<div class="product-item-thumbnail">'.$thumbnail.'</div> ';

        $product_name = $image_html . $product_name;
    }
    return $product_name;
}


add_filter( 'woocommerce_cart_item_price', 'keeki_change_cart_table_price_display', 30, 3 );
 
function keeki_change_cart_table_price_display( $price, $values, $cart_item_key ) {
$slashed_price = $values['data']->get_price_html();
$is_on_sale = $values['data']->is_on_sale();
if ( $is_on_sale ) {
 $price = $slashed_price;
}
return $price;
}

//https://www.terrytsang.com/tutorial/woocommerce/how-to-show-percentage-or-saved-amoun-for-woocommerce-product-sale-price/

function you_save_echo_product() {
    global $product;

    // works for Simple and Variable type
    $regular_price  = get_post_meta( $product->get_id(), '_regular_price', true ); 
    $sale_price     = get_post_meta( $product->get_id(), '_sale_price', true ); 
        
    if( !empty($sale_price) ) {
    
        $saved_amount       = $regular_price - $sale_price;
        $currency_symbol    = get_woocommerce_currency_symbol();

        $percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
        ?>
            <p class="you_save_price">Save <?php echo $currency_symbol .''. number_format($saved_amount, 2, '.', ''); ?></p>               
        <?php       
    } 
        
}
//add_action( 'woocommerce_single_product_summary', 'you_save_echo_product', 11 );



 
/**
 * Show sale prices at the checkout.
 */
function my_custom_show_sale_price_at_checkout( $subtotal, $cart_item, $cart_item_key ) {
    /** @var WC_Product $product */

    if(is_checkout()){
    $product = $cart_item['data'];
    $quantity = $cart_item['quantity'];
    if ( ! $product ) {
        return $subtotal;
    }
    $regular_price = $sale_price = $suffix = '';
    if ( $product->is_taxable() ) {
        if ( 'excl' === WC()->cart->tax_display_cart ) {
            $regular_price = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_regular_price(), 'qty' => $quantity ) );
            $sale_price    = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_sale_price(), 'qty' => $quantity ) );
            if ( WC()->cart->prices_include_tax && WC()->cart->tax_total > 0 ) {
                $suffix .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
            }
        } else {
            $regular_price = wc_get_price_including_tax( $product, array( 'price' => $product->get_regular_price(), 'qty' => $quantity ) );
            $sale_price = wc_get_price_including_tax( $product, array( 'price' => $product->get_sale_price(), 'qty' => $quantity ) );
            if ( ! WC()->cart->prices_include_tax && WC()->cart->tax_total > 0 ) {
                $suffix .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
            }
        }
    } else {
        $regular_price    = $product->get_price() * $quantity;
        $sale_price       = $product->get_sale_price() * $quantity;
    }
    if ( $product->is_on_sale() && ! empty( $sale_price ) ) {
        $price = wc_format_sale_price(
                     wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price(), 'qty' => $quantity ) ),
                     wc_get_price_to_display( $product, array( 'qty' => $quantity ) )
                 ) . $product->get_price_suffix();


          $regular_price    = $product->get_regular_price() * $quantity;
          $sale_price       = $product->get_sale_price() * $quantity;

          $saved_amount       = $regular_price - $sale_price;
          $currency_symbol    = get_woocommerce_currency_symbol();
         // $percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
          $total_saving = $currency_symbol . $saved_amount;


          $price  = 'Was '.$price ;
          $price = $price . $suffix .'<span class="saving">(Save '.$total_saving.' )</span>';
    } else {
        $price = wc_price( $regular_price ) . $product->get_price_suffix();
        $price = $price . $suffix;
    }
    // VAT suffix
   // $price = $price . $suffix .'<span class="saving">Save</span>';
    return $price;
   }
   else
   {

        return $subtotal;

   }
}
add_filter( 'woocommerce_cart_item_subtotal', 'my_custom_show_sale_price_at_checkout', 10, 3 );



/* This will add below total on cart / checkout that how much total amount saved */

/*

function bbloomer_wc_discount_total_30() {
 
    global $woocommerce;
      
    $discount_total = 0;
      
    foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values) {
          
    $_product = $values['data'];
  
        if ( $_product->is_on_sale() ) {
        $regular_price = $_product->get_regular_price();
        $sale_price = $_product->get_sale_price();
        $discount = ($regular_price - $sale_price) * $values['quantity'];
        $discount_total += $discount;
        }
  
    }
             
    if ( $discount_total > 0 ) {
    echo '<tr class="cart-discount">
    <th>'. __( 'Saved', 'woocommerce' ) .'</th>
    <td data-title=" '. __( 'Save', 'woocommerce' ) .' ">'
    . wc_price( $discount_total + $woocommerce->cart->discount_cart ) .'</td>
    </tr>';
    }
 
}
 
// Hook our values to the Basket and Checkout pages
 
add_action( 'woocommerce_cart_totals_after_order_total', 'bbloomer_wc_discount_total_30', 99);
add_action( 'woocommerce_review_order_after_order_total', 'bbloomer_wc_discount_total_30', 99);

*/





function keeki_update_order_of_fields( $address_fields ) {

$address_fields['billing_first_name']['priority'] = 3;
$address_fields['billing_last_name']['priority'] = 4;
$address_fields['billing_company']['priority'] = 5;
$address_fields['billing_email']['priority'] = 6;
$address_fields['billing_phone']['priority'] = 7;

//$address_fields['billing_state']['required'] = false;
//$address_fields['billing_city']['required'] = false;


return $address_fields;
}
add_filter( 'woocommerce_billing_fields', 'keeki_update_order_of_fields', 10, 1 );


add_filter( 'woocommerce_checkout_fields' , 'custom_rename_wc_checkout_fields', 99 );

// Change placeholder and label text
function custom_rename_wc_checkout_fields( $address_fields ) {



$address_fields['shipping']['shipping_address_2']['label'] = 'Apartment, suite etc';
$address_fields['shipping']['shipping_address_2']['placeholder'] = 'Apartment, suite etc';

return $address_fields;
}



/* Custom Update */
add_action( 'wp_ajax_nopriv_post_shipping_method', 'post_shipping_method_call' );
add_action( 'wp_ajax_post_shipping_method', 'post_shipping_method_call' );

function post_shipping_method_call() {
            $session = WC()->session;
            $session->set('chosen_shipping_methods', array( 'keeki_shipping'));  
}


add_filter( 'woocommerce_cart_totals_order_total_html', 'filter_function_name_9212' );
function filter_function_name_9212( $value ){
        global $woocommerce;
        $get_total = $woocommerce->cart->total;
        $x = (10 / 100) * $get_total;
        $value = $value."<span class='gstText'>(includes $". $x ." GST) </span>";

    return $value;
}




/* Prmotions and Discounts */






/* Add custom shipping cost if mirror available */





function my_hide_shipping_when_free_is_available( $rates , $package ) {


        global $woocommerce;
        $items = $woocommerce->cart->get_cart(); // Get all cart items

        /* set a flag to true if any furniture related category product */

        $taxonomyName = "product_cat";
        $value = 'furniture';
        $cat = get_term_by('slug',$value,$taxonomyName);
        $termID = $cat->term_id; 
        //echo $termID.'ddd';
        $termchildren = get_term_children( $termID, $taxonomyName );
        $termIdsArray = array();
        foreach ($termchildren as $value) { $termIdsArray[] = $value; }

        //print_r($termIdsArray);

        $isFurniture = '';




        foreach($items as $item => $values) 
            {       
                $terms = get_the_terms( $values['product_id'], 'product_cat' );
                foreach ($terms as $term) {     
                   // echo $term->slug;
                    /*floor-rugs */
                    /* mirrors */
                }

                if (has_term( $termIdsArray, 'product_cat', $values['product_id'] ) ) :
                    $price = get_post_meta($values['product_id'] , '_price', true); 
                    $price * $values['quantity'];
                    $PriceArray[] = $price * $values['quantity']; 
                    $Sum = array_sum($PriceArray);
                    $isFurniture = 'yes';
                endif;

            }

        $total = WC()->cart->subtotal;


       // echo $Sum.'Sum Val';


        if( $Sum <= 800 && $isFurniture == 'yes') {
           // echo "enter in loop";
            unset( $rates[951485]);
        }
        else
        {
            $rates = $rates;
        }


        return $rates;
}
/* add_filter( 'woocommerce_package_rates', 'my_hide_shipping_when_free_is_available1',  10, 2 ); */







/* Spend and Save 16-11-2017 */


function custom_wc_add_discount() {

        global $woocommerce;
        $number_products = $woocommerce->cart->cart_contents_count;

        $getSubtotal = $woocommerce->cart->subtotal;

        if($getSubtotal >= 100 && $getSubtotal < 250)
            {
                    $percentage = 10;
                    $discountPer = $getSubtotal*10;
                    $discountVal = ($percentage / 100) * $getSubtotal;
            }
        if($getSubtotal >= 250 && $getSubtotal < 850)
            {
                    $percentage = 15;
                    $discountPer = $getSubtotal*10;
                    $discountVal = ($percentage / 100) * $getSubtotal;
            }
        if($getSubtotal >= 850 && $getSubtotal < 2500)
            {
                    $percentage = 20;
                    $discountPer = $getSubtotal*10;
                    $discountVal = ($percentage / 100) * $getSubtotal;
            }
        if($getSubtotal >= 2500)
            {
                    $percentage = 25;
                    $discountPer = $getSubtotal*10;
                    $discountVal = ($percentage / 100) * $getSubtotal;
            }


        WC()->cart->add_fee( 'Saving '. $percentage.'%', - $discountVal );
}
//add_action( 'woocommerce_cart_calculate_fees','custom_wc_add_discount' );


/* End of spend and save */



//add_action('woocommerce_cart_calculate_fees', 'add_custom_discount_2nd_at_40', 10, 1 );
function add_custom_discount_2nd_at_40( $wc_cart ){
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    $discount = 0;
    if($wc_cart->cart_contents_count > 1 )
        {
            
                        $product_price = array();
                        global $woocommerce;
                        $items = $woocommerce->cart->get_cart();
                        foreach($items as $item => $values) { 
                                $_product =  wc_get_product( $values['data']->get_id()); 

                                $terms = get_the_terms( $_product->id, 'product_cat' );
                                        foreach ($terms as $term) 
                                            {
                                                $_categorySlug = $term->slug;
                                                if ($_categorySlug !== 'sale' ) 
                                                {
                                                    $product_in_cart_of_sale = 'true';
                                                }
                                                else
                                                {
                                                    $product_in_cart_of_sale = 'true';
                                                }
                                                
                                            }


                                $price = get_post_meta($values['product_id'] , '_price', true);
                                for($k = 1; $k <= $values['quantity']; $k++)
                                    {

                                       $product_price[] = $price;    
                                                     
                                    }

                            } 

                        if($product_in_cart_of_sale == 'true')
                            {

                                 $sum = 0;
                                 $totalProduct = floor($wc_cart->cart_contents_count / 2);
                                 sort($product_price);
                                 for($x = 0; $x < $totalProduct; $x++) 
                                    {
                                        $sum += $product_price[$x] * 0.4;
                                    }

                                 $discount = number_format($discount -= $sum, 2 );
                                 if( $discount != 0 )
                                    {
                                        // Displaying a custom notice (optional)
                                        wc_clear_notices();
                                        wc_add_notice( __("You get 40% of discount on the 2nd item"), 'notice');
                                       // $wc_cart->add_fee( 'Discount on 2nd item at 40%', $discount, true  );
                                        WC()->cart->add_fee('Discount on 2nd item at 40%', $discount);
                                    }
   
                            }
                        else
                            {

                            }

        }
}



add_action('woocommerce_cart_calculate_fees', 'add_custom_discount_2nd_at_20', 10, 1 );
function add_custom_discount_2nd_at_20( $wc_cart ){
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    $discount = 0;
    if($wc_cart->cart_contents_count > 1 )
        {
            
                        $product_price = array();
                        $product_Sku_Arrays = array(
                                                        'MF8311',
                                                        '01369E112',
                                                        '01369E112',
                                                        'A1856B.2S.LIN',
                                                        'A1856B.3S.LIN',
                                                        '1DT104/SQ150/GR',
                                                        '1DT104.SQ150.GUANWHT',
                                                        '1TT265/LT/GR',
                                                        '1TT265/CT/RECT/GR',
                                                        '1ST310.1600.SERP',
                                                        'D193.CT.BLK',
                                                        'B193.CT.BLK',
                                                        'A159',
                                                        'B159.CT.WH',
                                                        'D159.LT.WH',
                                                        'C159.CT.WH',
                                                        'B0909A.DT.WH',
                                                        'B0909C.CT.WH',
                                                        'B0909B.CT.WH',
                                                        'B0909D.LT.WH',
                                                        'BJ-105.CT.WH',
                                                        '1SM240.REC240.SNOW',
                                                        '1TT203/CT/RECT/SERP',
                                                        '1TT203/CT/RECT/CREA',
                                                        '1ST307/SERP',
                                                        '1ST307/CRM',
                                                        '1DT104/REC240/WBCS',
                                                        '1DT104/REC240/SERP',
                                                        '1DT104/REC240/BG'

                                                    );
                        $is_cat_exclued_cat = '';
                        global $woocommerce;
                        $items = $woocommerce->cart->get_cart();
                        foreach($items as $item => $values) { 
                                $_product =  wc_get_product( $values['data']->get_id()); 

                                $terms = get_the_terms( $_product->id, 'product_cat' );
                                        foreach ($terms as $term) 
                                            {
                                                $_categorySlug = $term->slug;
                                                if ($_categorySlug !== 'sale' ) 
                                                {
                                                    $product_in_cart_of_sale = 'false';
                                                }
                                                else
                                                {
                                                    $product_in_cart_of_sale = 'true';
                                                }
                                                
                                            }

                                if(in_array($_product->get_sku(), $product_Sku_Arrays)){
                                    $is_cat_exclued_cat = 'true';
                                }


                                $price = get_post_meta($values['product_id'] , '_price', true);
                                for($k = 1; $k <= $values['quantity']; $k++)
                                    {
                                       $product_price[] = $price;                   
                                    }

                            } 

                            
                        if($product_in_cart_of_sale != 'true' && $is_cat_exclued_cat == '')
                            {

                                 $sum = 0;
                                 $totalProduct = floor($wc_cart->cart_contents_count / 2);
                                 sort($product_price);
                                 for($x = 0; $x < $totalProduct; $x++) 
                                    {
                                        $sum += $product_price[$x] * 0.2;
                                    }

                                 $discount = number_format($discount -= $sum, 2 );
                                 if( $discount != 0 )
                                    {
                                        // Displaying a custom notice (optional)
                                        wc_clear_notices();
                                        wc_add_notice( __("You get 20% of discount on the 2nd item"), 'notice');
                                       // $wc_cart->add_fee( 'Discount on 2nd item at 40%', $discount, true  );
                                        WC()->cart->add_fee('Discount on 2nd item at 20%', $discount);
                                    }
   
                            }
                        else
                            {

                            }

        }
}



function my_hide_shipping_when_free_is_availableMirror( $rates , $package ) {

        global $woocommerce;
        $items = $woocommerce->cart->get_cart(); // Get all cart items
    
        $postcode = (isset($woocommerce->customer->shipping_postcode) && $woocommerce->customer->shipping_postcode!='')?$woocommerce->customer->shipping_postcode:$woocommerce->customer->postcode;
    
      //  echo $postcode;
    

        /* set a flag to true if any furniture related category product */

        $taxonomyName = "product_cat";
        $value = 'mirrors';
        $cat = get_term_by('slug',$value,$taxonomyName);
        $termID = $cat->term_id; 
        $termchildren = get_term_children( $termID, $taxonomyName );
        $termIdsArray = array($termID);
        foreach ($termchildren as $value) { $termIdsArray[] = $value; }

        $isMirror = '';
        $MirrorCount = 0;




        foreach($items as $item => $values) 
            {       
                $terms = get_the_terms( $values['product_id'], 'product_cat' );
                foreach ($terms as $term) {     
                     //echo $term->slug;
                }

                if (has_term( $termIdsArray, 'product_cat', $values['product_id'] ) ) :
                    
                    //echo "true";
                    $price = get_post_meta($values['product_id'] , '_price', true); 
                    $price * $values['quantity'];
                    $PriceArray[] = $price * $values['quantity']; 
                    $Sum = array_sum($PriceArray);
                    $isMirror = 'yes';
                    $MirrorCount += $values['quantity'];
                endif;

            }



        $total = WC()->cart->subtotal;

       if( $Sum <= 800 && $isFurniture == 'yes') {
            unset( $rates[893459]);
        }
        else
        {
                if($isMirror == 'yes'){


                        /*   echo '<div class="wnd-checkout-message"> <b>Please note:</b> Freight charges are calculated based on product, and mirrors are classed as bulky items, therefore extra charges apply.</h3>
                             </div>';

                    */
                }

                 /* overrride keeki shipping price for mirror case */
                if($isMirror == 'yes' && $MirrorCount >1)
                {
                        $customCost = 11*($MirrorCount-1);
                        if($rates['keeki_shipping'])
                            {

                                    $rates['keeki_shipping']->cost = $rates['keeki_shipping']->cost+$customCost;

                            }
                }
                else
                {
                        $rates = $rates;
                }
           


            
        }

        return $rates;


}
add_filter( 'woocommerce_package_rates', 'my_hide_shipping_when_free_is_availableMirror',  10, 2 );

/* End */








add_action('woocommerce_before_cart', 'keeki_check_category_in_cart');
 
function keeki_check_category_in_cart() {
 
$cat_in_cart = false;
 
// Loop through all products in the Cart        
foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
 
    // If Cart has category "mirrors", set $cat_in_cart to true
    if ( has_term( 'mirrors', 'product_cat', $cart_item['product_id'] ) ) {
        $cat_in_cart = true;
        break;
    }
}
   
// Do something if category "download" is in the Cart      
if ( $cat_in_cart ) {
 
// For example, print a notice
wc_print_notice( '<b>Please note:</b> Freight charges are calculated based on product, and mirrors are classed as bulky items, therefore extra charges apply', 'notice' );
 
 
}
 
}


/* Labour Day Discount March */


//add_action('woocommerce_cart_calculate_fees', 'add_labout_day_discount_on_second_item', 10, 1 );
function add_labout_day_discount_on_second_item( $wc_cart ){
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    $discount = 0;
    if($wc_cart->cart_contents_count > 1 )
        {
            
                        $product_price = array();
                        global $woocommerce;
                        $items = $woocommerce->cart->get_cart();
                        foreach($items as $item => $values) { 
                                $_product =  wc_get_product( $values['data']->get_id()); 

                                $terms = get_the_terms( $_product->id, 'product_cat' );
                                        foreach ($terms as $term) 
                                            {
                                                $_categorySlug = $term->slug;
                                                if ($_categorySlug !== 'sale' ) 
                                                {
                                                    $product_in_cart_of_sale = 'true';
                                                }
                                                else
                                                {
                                                    $product_in_cart_of_sale = 'false';
                                                }
                                                
                                            }


                                $price = get_post_meta($values['product_id'] , '_price', true);
                                for($k = 1; $k <= $values['quantity']; $k++)
                                    {

                                       $product_price[] = $price;    
                                                     
                                    }

                            } 

                        if($product_in_cart_of_sale == 'true')
                            {

                                 $sum = 0;
                                 $totalProduct = floor($wc_cart->cart_contents_count / 2);
                                 sort($product_price);
                                 for($x = 0; $x < $totalProduct; $x++) 
                                    {
                                        $sum += $product_price[$x] * 0.4;
                                    }

                                 $discount = number_format($discount -= $sum, 2 );
                                 if( $discount != 0 )
                                    {
                                        // Displaying a custom notice (optional)
                                        wc_clear_notices();
                                      //  wc_add_notice( __("You get 40% of discount on the 2nd item"), 'notice');
                                       // $wc_cart->add_fee( 'Discount on 2nd item at 40%', $discount, true  );
                                        WC()->cart->add_fee('Discount on 2nd item at 40%', $discount);
                                    }
   
                            }
                        else
                            {

                            }

        }
}







/* Update on 15th - Jan - 2019  Discount only on sales item */

// add_action('woocommerce_cart_calculate_fees', 'add_sale_cat_discount', 10, 1 );
function add_sale_cat_discount( $wc_cart ){
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    $discount = 0;
    if($wc_cart->cart_contents_count > 1 )
        {
            
            global $woocommerce;
            $discount = 0;
            $items_prices = array();
            $targeted_product_id = 40;
            foreach ( $wc_cart->get_cart() as $key => $cart_item ) {
                        $product_in_cart_of_sale = '';
                        $terms = get_the_terms( $cart_item['product_id'], 'product_cat' );
                        $product_in_cart_of_sale = '';
                        foreach ($terms as $term) 
                            {
                                $_categorySlug = $term->slug;
                                if ($_categorySlug !== 'sale' ) 
                                    {
                                                    
                                    }
                                else
                                    {
                                        $product_in_cart_of_sale = 'true';
                                    }                  
                            }

                if( $product_in_cart_of_sale  == 'true' ){
                    $qty = intval( $cart_item['quantity'] );
                    for( $i = 0; $i < $qty; $i++ )
                        $items_prices[] = floatval( $cart_item['data']->get_price());
                }
            }

            sort($items_prices);
            $count_items_prices = count($items_prices);
            $stop_count = floor( $count_items_prices / 2 );
            if( $count_items_prices > 1 ) 
                {
                    $counter = 0;
                    foreach( $items_prices as $key => $price )
                    {

                       // echo $price.'Inside Looop';
                        if($counter < $stop_count)
                            {
                                $discount -= number_format($price / 2, 2 );

                            }

                        $counter++;
                    }
                }
            if( $discount != 0 ){
                wc_clear_notices();
               //  wc_add_notice( __("You get 50% of discount on the 2nd item"), 'notice');
                $wc_cart->add_fee( '50% of Discount on the 2nd item', $discount, true  );
                # Note: Last argument in add_fee() method is related to applying the tax or not to the discount (true or false)
            }

        }
}




/* April 17 - 2019 */



// add_action('woocommerce_cart_calculate_fees', 'category_discount_daywise', 10, 1 );
function category_discount_daywise( $wc_cart ){
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    
    if($wc_cart->cart_contents_count > 0)
        {
            $Date = date( 'd', current_time( 'timestamp', 0 ));
            $k_cat = array();
            if($Date == 191)
                {
                    $k_cat = array('bedside-tables');
                    $k_discount_per = 15;
                    $check_parent = false;
                    calculate_day_and_categorie_discount_percentage($k_cat, $k_discount_per, $Date , $wc_cart , $check_parent);
                }
            if($Date == 201)
                {
                    $k_cat = array('bar-stools');
                    $k_discount_per = 15;
                    $check_parent = false;
                    calculate_day_and_categorie_discount_percentage($k_cat, $k_discount_per, $Date , $wc_cart , $check_parent);
                }
            if($Date == 211)
                {
                    $k_cat = array('dining-tables');
                    $k_discount_per = 15;
                    $check_parent = false;
                    calculate_day_and_categorie_discount_percentage($k_cat, $k_discount_per, $Date , $wc_cart , $check_parent );
                }
            if($Date == 221)
                {
                    $k_cat = array('furniture', 'homewares');
                    $k_discount_per = 25; 
                    $check_parent = true;
                    calculate_day_and_categorie_discount_percentage($k_cat, $k_discount_per, $Date , $wc_cart , $check_parent);
                }

        }
}

function get_term_top_most_parent( $term_id, $taxonomy ) {
    $parent  = get_term_by( 'id', $term_id, $taxonomy );
    while ( $parent->parent != 0 ){
        $parent  = get_term_by( 'id', $parent->parent, $taxonomy );
    }
    return $parent;
}



function calculate_day_and_categorie_discount_percentage(array $k_cat, $k_discount_per , $Date , $wc_cart , $check_parent)
    {
                $discount = 0;
                global $woocommerce;
                $items_prices = array();
                foreach ( $wc_cart->get_cart() as $key => $cart_item ) {
                            $product_in_cart_of_include_cat = '';
                            $terms = get_the_terms( $cart_item['product_id'], 'product_cat' );

                           
                            $arraySlugs = array();
                            foreach ($terms as $term_1) 
                                {
                                    $arraySlugs[] = $term_1->slug;
                                }
                            
                            if(!in_array('sale', $arraySlugs))
                                {
                                    // echo 'In loop';
                                    /* This check to skip all sales category */

                                    foreach ($terms as $term) 
                                        {
                                            $_categorySlug = $term->slug;

                                            if(in_array($_categorySlug, $k_cat))
                                            {

                                                $product_in_cart_of_include_cat = 'true';
                                                /* if($_categorySlug == 'bedside-tables')
                                                    {
                                                        $k_discount_per = 10;
                                                    }
                                                    else
                                                    {
                                                        $k_discount_per = $k_discount_per; 
                                                    }
                                                */
                                            }
                                            else
                                            {

                                                if($check_parent == true)
                                                    {

                                                        $theCatId = get_term_by( 'slug', $_categorySlug, 'product_cat' );
                                                        $theCatId = $theCatId->term_id;

                                                        $top_parent = get_term_top_most_parent( $theCatId, 'product_cat' );


                                                        //Check if you have it in your array to only add it once
                                                        if('furniture' == $top_parent->slug ) {
                                                                //echo 'furniture';
                                                                $product_in_cart_of_include_cat = 'true';
                                                                $k_discount_per = 10;
                                                        }
                                                       if('homewares' == $top_parent->slug ) {
                                                               // echo 'homewares';
                                                                $product_in_cart_of_include_cat = 'true';
                                                                $k_discount_per = 25;
                                                        }


                                                    }
                                                else
                                                    {
                                                        $product_in_cart_of_include_cat = '';


                                                    }

                                                
                                            }          
                                        }


                                        if( $product_in_cart_of_include_cat  == 'true' ){
                                            $qty = intval( $cart_item['quantity'] );
                                            for( $i = 0; $i < $qty; $i++ )
                                                $items_prices[] = array(
                                                                        'price' => $cart_item['data']->get_price(),
                                                                        'discount' => $k_discount_per
                                                 );

                                        }

                                }

                }

                asort($items_prices);

                $count_items_prices = count($items_prices);

                if( $count_items_prices > 0 ) 
                    {
                        
                        foreach( $items_prices as $price )
                            {

                                        $discount -= number_format(($price['price'] / 100) * $price['discount'], 2);
                            }
                    }

                   // echo $discount.'This is discount value';

                if( $discount != 0 ){
                    wc_clear_notices();
                   //  wc_add_notice( __("You get 50% of discount on the 2nd item"), 'notice');
                    // $wc_cart->add_fee( $k_discount_per.'% of Discount Applied', $discount, true  );
                    $wc_cart->add_fee('Discount Applied', $discount, true  );
                    # Note: Last argument in add_fee() method is related to applying the tax or not to the discount (true or false)
                }

    }





/* Custom APIs */


add_action( 'rest_api_init', 'get_coupon_info_by_name' );
function get_coupon_info_by_name() {
    register_rest_route( 'smart_coupons', 'coupon/(?P<slug>[a-zA-Z0-9-]+)', array(
                    'methods' => 'GET',
                    'callback' => 'get_smart_coupon',
                    'permission_callback'   => 'check_if_permission_allowed'
                )
            );
}
function get_smart_coupon($data) {

    global $woocommerce;

    $coupon_code = $data['slug'];
    $coupon_details = new WC_Coupon($coupon_code);


    $date_expires = '';
    $date_created = '';
    $date_modified = '';


    if( $coupon_details->get_date_expires() ) {
            $date_expires = $coupon_details->get_date_expires()->date('Y-m-d H:i:s');
        } else {
                    $date_expires = '';
        }

     if( $coupon_details->get_date_created() ) {
            $date_created = $coupon_details->get_date_created()->date('Y-m-d H:i:s');
        } else {
                    $date_created = '';
        }

     if( $coupon_details->get_date_modified() ) {
            $date_modified = $coupon_details->get_date_modified()->date('Y-m-d H:i:s');
        } else {
                    $date_modified = '';
        }



    

    $coupon_info = array();
    $coupon_info[ 'id' ] = $coupon_details->id;
    $coupon_info[ 'code'] = $coupon_code;
    $coupon_info[ 'amount' ] = $coupon_details->amount;
    $coupon_info[ 'date_created' ] =  $date_created;
    $coupon_info[ 'date_modified' ] = $date_modified;
    $coupon_info[ 'date_expires' ] =  $date_expires;
    $coupon_info[ 'discount_type' ] = $coupon_details->discount_type;
    $coupon_info[ 'description' ] = $coupon_details->description;
    $coupon_info[ 'usage_count' ] = $coupon_details->usage_count;

    //'Y F j, g:i a'
    wp_reset_postdata();
    return rest_ensure_response($coupon_info);
} 



/* Get rewards points by User id */

add_action( 'rest_api_init', 'get_rewards_points_by_userid' );
function get_rewards_points_by_userid() {
    register_rest_route( 'get_rewards_points', 'user/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => 'get_rewards_points_callback',
                    'permission_callback'   => 'check_if_permission_allowed'
                )
            );
}


function get_rewards_points_callback($data)
{

     global $woocommerce;
     global $wpdb,$table_prefix;

     $user_id = $data['id'];

     if ( ! user_can( $user_id, 'customer' ) ) return;



     $get_pts = $wpdb->get_var('SELECT points_balance FROM '.$table_prefix.'wc_points_rewards_user_points WHERE user_id = '.$user_id .' ORDER BY `id` DESC');


    // echo 'SELECT points_balance FROM '.$table_prefix.'wc_points_rewards_user_points WHERE user_id = '.$user_id;

     $points_info = array();
     $points_info[ $user_id ] = $get_pts;
     return rest_ensure_response($points_info);
     exit();
}



/* Update rewards points */

add_action( 'rest_api_init', 'update_rewards_points_by_userid' );
function update_rewards_points_by_userid() {
    register_rest_route( 'update_rewards_points', 'user/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => 'update_rewards_points_callback',
                    'permission_callback'   => 'check_if_permission_allowed'
                )
            );
}


function update_rewards_points_callback($data)
{

         global $woocommerce;
         global $wc_points_rewards;
         global $wpdb,$table_prefix;

         $user_id = $data['id'];
         $no_of_points = $data->get_param('no_of_points');


         if ( ! user_can( $user_id, 'customer' ) ) return;

            
        $get_last_record = $wpdb->get_var('SELECT id FROM '.$table_prefix.'wc_points_rewards_user_points WHERE user_id = '.$user_id .' ORDER BY `id` DESC');

            /* $wpdb->update( $table_prefix.'wc_points_rewards_user_points', array( 'points_balance' => $no_of_points), array( 
                        'user_id' => $user_id,
                        'id' => $get_last_record,
                    ), array( '%s' ) );

        */


        if($wpdb->update( $table_prefix.'wc_points_rewards_user_points', array( 'points_balance' => $no_of_points), array( 
                        'user_id' => $user_id,
                        'id' => $get_last_record,
                    ), array( '%s' ) ) === FALSE)
            $response = 'failure';
        else
            $response = 'sucesss';


             $points_info = array();
             $points_info[ 'status' ] = $response;
             return rest_ensure_response($points_info);
             exit();
}




/* Custom rewards colum */

add_action( 'rest_api_init', 'update_custom_rewards_points_by_userid' );
function update_custom_rewards_points_by_userid() {
    register_rest_route( 'rewards', 'user/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => 'update_custom_rewards_points_callback',
                    'permission_callback'   => 'check_if_permission_allowed'
                )
            );
}


add_action( 'rest_api_init', 'get_custom_rewards_points_by_userid' );
function get_custom_rewards_points_by_userid() {
    register_rest_route( 'getrewards', 'user/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => 'get_custom_rewards_points_callback',
                    'permission_callback'   => 'check_if_permission_allowed'
                )
            );
}


add_action( 'rest_api_init', 'get_custom_credit_points_by_userid' );
function get_custom_credit_points_by_userid() {
    register_rest_route( 'getcredits', 'user/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => 'get_custom_credit_points_callback',
                    'permission_callback'   => 'check_if_permission_allowed'
                )
            );
}

add_action( 'rest_api_init', 'update_custom_credit_points_by_userid' );
function update_custom_credit_points_by_userid() {
    register_rest_route( 'credits', 'user/(?P<id>\d+)', array(
                    'methods' => 'PUT',
                    'callback' => 'update_custom_credit_points_callback',
                    'permission_callback'   => 'check_if_permission_allowed'
                )
            );
}




function check_if_permission_allowed($request)
{


    if($request->get_param('consumer_key') && $request->get_param('consumer_secret'))
    {
            global $wpdb;
            $get_consumer_key = $request->get_param('consumer_key');
            $get_secret_key   = $request->get_param('consumer_secret');

            $consumer_key_array = array(
                                            'ck_82bc7479148e442624448bc4c9273a957da831e5',
                                            'ck_e64f57a8f818d0a9ac9068249179b68d1c6ebf86',
                                            'ck_63fc5d67fd18464c627ed7e34a209628db7015da'
                                      );
            $consumer_secret_array = array(
                                            'cs_235504d9e94351bfecbeda88e5116ff88186b749',
                                            'cs_ba2d74d2352ee808a659b41337140a80ddd161bb',
                                            'cs_c847b667dba01fedc817c2af88e713ef11da11fb'
                                        );


            if(in_array($get_consumer_key, $consumer_key_array) && in_array($get_secret_key, $consumer_secret_array))
                  {
                        return true;
                  }
                else
                  {
                        return new WP_Error( 'response', 'Wrong keys', array('status' => 404) );
                  }


            
    }
    else
    {
            return new WP_Error( 'response', 'You are not authorised to access', array('status' => 404) );
    }


}

function update_custom_rewards_points_callback($data)
{

     global $woocommerce;
     global $wc_points_rewards;
     global $wpdb,$table_prefix;

     $user_id = $data['id'];
     $no_of_points = $data->get_param('rewardspoints');

     if($no_of_points)
     {
             if ( ! user_can( $user_id, 'customer' ) ) return;

                $check_if_rewards_meta = get_user_meta($user_id, 'user_rewards_points', false);
                if($check_if_rewards_meta)
                {
                    update_user_meta($user_id, 'user_rewards_points', $no_of_points); 
                    $response = 'sucesss';
                }
                else
                {
                    add_user_meta( $user_id, 'user_rewards_points', $no_of_points);
                    $response = 'sucesss';
                }
    }
    else
    {

            $response = 'failure';

    }

     $points_info = array();
     $points_info[ 'status' ] = $response;
     return rest_ensure_response($points_info);
     exit();
}

function update_custom_credit_points_callback($request)
{

     global $woocommerce;
     global $wc_points_rewards;
     global $wpdb,$table_prefix;

     $response_error = '';
     $response = '';
     $points_info = array();

     $user_id = $request['id'];
     $no_of_points  = $request->get_param('creditpoints');
     $rewardspoints = $request->get_param('rewardspoints');



     if(is_numeric($no_of_points)  && is_numeric($rewardspoints))
     {

                //if($no_of_points % 25 == 0)
                if($no_of_points >= 0 && $rewardspoints >= 0)
                    {
                
                        update_credits_via_loop($user_id, $no_of_points, $rewardspoints);
                        $points_info[ 'status' ] = 'sucesss';
                        $points_info[ 'datetime' ] = date("Y-m-d H:i:s");
                   }
                else
                    {

                         if($no_of_points < 0){$no_of_points = 0;}else{$no_of_points = $no_of_points; }
                         if($rewardspoints < 0){$rewardspoints = 0;}else{$rewardspoints = $rewardspoints; }


                         update_credits_via_loop($user_id, $no_of_points, $rewardspoints);
                         //$response_error = 'Points must be in order 25';
                         $points_info[ 'status' ] = 'sucesss';
                        // $points_info[ 'error' ]  = $response_error;
                         $points_info[ 'datetime' ] = date("Y-m-d H:i:s");

                    }


    }
    else
    {
            $points_info[ 'error' ]  =  'Make sure all creditpoints and rewardspoints must be numberic';
            $points_info[ 'status' ] = 'failure';
            $points_info[ 'datetime' ] = date("Y-m-d H:i:s");

    }

     
     
     return rest_ensure_response($points_info);
     exit();
}



function update_credits_via_loop($user_id, $no_of_points, $rewardspoints)
{
     global $woocommerce;
     global $wc_points_rewards;
     global $wpdb,$table_prefix;
     $points_info = array();

                 if ( ! user_can( $user_id, 'customer' ) ) return;

                                $check_if_rewards_meta = get_user_meta($user_id, 'user_credit_points', false);
                                if($check_if_rewards_meta)
                                {
                                    update_user_meta($user_id, 'user_credit_points', $no_of_points); 
                                    // $response = 'sucesss';
                                }
                                else
                                {
                                    add_user_meta( $user_id, 'user_credit_points', $no_of_points);
                                    // $response = 'sucesss';
                                }


                                $check_if_user_rewards_meta = get_user_meta($user_id, 'user_rewards_points', false);
                                if($check_if_user_rewards_meta)
                                {
                                    update_user_meta($user_id, 'user_rewards_points', $rewardspoints); 
                                    //$response = 'sucesss';
                                }
                                else
                                {
                                    add_user_meta( $user_id, 'user_rewards_points', $rewardspoints);
                                    //$response = 'sucesss';
                                }


                                /* Update points column as well by converting */



                                         if ( ! user_can( $user_id, 'customer' ) ) return;

                                            
                                        $get_last_record = $wpdb->get_var('SELECT id FROM '.$table_prefix.'wc_points_rewards_user_points WHERE user_id = '.$user_id .' ORDER BY `id` DESC');


                                        $new_no_of_points = '';
                                        $new_no_of_points = ($no_of_points/25)*1000;

                                        if($get_last_record)
                                        {
                                            $response_2 = '';
                                            if($wpdb->update( $table_prefix.'wc_points_rewards_user_points', 
                                                array( 'points_balance' => $new_no_of_points,
                                                        'date' => date("Y-m-d H:i:s")
                                                      ), array( 
                                                                    'user_id' => $user_id,
                                                                    'id' => $get_last_record,
                                                                ), array( '%s' ) ) === FALSE)
                                                        $response_2 = 'failure';
                                                    else
                                                        $response_2 = 'sucesss';


                                            //$points_info[ 'status' ] = 'sucesss'; 
                                            //$points_info[ 'datetime' ] = date("Y-m-d H:i:s");
                                        }
                                        else
                                        {
                                                $wpdb->insert(
                                                        $table_prefix.'wc_points_rewards_user_points',
                                                        array(
                                                        'user_id' => $user_id,
                                                        'points' => $new_no_of_points,
                                                        'points_balance' => $new_no_of_points,
                                                        'date' => date("Y-m-d H:i:s"),
                                                        ),
                                                        array(
                                                        '%d',
                                                        '%d',
                                                        '%d',
                                                        '%s'
                                                        )
                                                    );

                                                    // $wpdb->show_errors();
                                                   // $points_info[ 'status' ] = 'sucesss'; 
                                                   // $points_info[ 'datetime' ] = date("Y-m-d H:i:s");
                                        }

                            return true;

}


function get_custom_rewards_points_callback($data)
    {

         global $woocommerce;
         global $wc_points_rewards;
         global $wpdb,$table_prefix;

         $user_id = $data['id'];
       
                 if ( ! user_can( $user_id, 'customer' ) ) return;

                    $get_rewards_meta = get_user_meta($user_id, 'user_rewards_points', false);
                    if($get_rewards_meta)
                    {
                        $response = 'sucesss';
                    }
                    else
                    {
                        $response = 'null';
                    }


         $points_info = array();
         $points_info[ 'rewards_pts' ] = $get_rewards_meta;
         $points_info[ 'status' ] = $response;
         return rest_ensure_response($points_info);
         exit();
    }


function get_custom_credit_points_callback($data)
    {

         global $woocommerce;
         global $wc_points_rewards;
         global $wpdb,$table_prefix;

         $user_id = $data['id'];
       
                 if ( ! user_can( $user_id, 'customer' ) ) return;

                    $get_rewards_meta = get_user_meta($user_id, 'user_credit_points', false);
                    if($get_rewards_meta)
                    {
                        $response = 'sucesss';
                    }
                    else
                    {
                        $response = 'null';
                    }


         $points_info = array();
         $points_info[ 'credit_pts' ] = $get_rewards_meta;
         $points_info[ 'status' ] = $response;
         return rest_ensure_response($points_info);
         exit();
    }


// wp-json/smart_coupons/coupon

/* End of Custom APIs */

/* Custom Fields for Variations Productions */


add_action( 'woocommerce_variation_options_pricing', 'bbloomer_add_custom_field_to_variations', 10, 3 );
 
function bbloomer_add_custom_field_to_variations( $loop, $variation_data, $variation ) {
    woocommerce_wp_text_input( array(
        'id' => 'custom_field[' . $loop . ']',
        'class' => 'short',
        'label' => __( 'Select', 'woocommerce' ),
        'value' => get_post_meta( $variation->ID, 'custom_field', true )
        )
    );
}


// Add New Variation Settings
add_filter( 'woocommerce_available_variation', 'load_variation_settings_fields' );

function load_variation_settings_fields( $variations ) {
  
  // duplicate the line for each field
    $variations['p_manufacture'] = get_post_meta( $variations[ 'variation_id' ], '_p_manufacture', true );
    $variations['p_design'] = get_post_meta( $variations[ 'variation_id' ], '_p_design', true );
    $variations['p_supplier'] = get_post_meta( $variations[ 'variation_id' ], '_p_supplier', true );
  
  return $variations;

}

/*

add_action( 'woocommerce_before_add_to_cart_button', 'vp_product_display_commodity_code' );
function vp_product_display_commodity_code() {
    global $product;

    if( $value = $product->get_meta( '_p_manufacture' ) ) {
        echo '<div class="vp-ccode-wrapper"><strong>' . __("Manufacture", "woocommerce") .
        ': </strong>'.esc_html( $value ).'</div>';
    }
}
*/

/* End of Custom fields variations */



add_action( 'wp_ajax_nopriv_get_variations_custom_options', 'get_variations_custom_options_method_call' );
add_action( 'wp_ajax_get_variations_custom_options', 'get_variations_custom_options_method_call' );

function get_variations_custom_options_method_call() {
        global $wpdb;
        global $woocommerce;

        $manufacture_options = '';
        $get_manufactures = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."keeki_manufacturing`");
        $colors_options = '';
        $get_colors = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."keeki_designs`");


        $manufacture_options .= '<option value="">Select Design</option>';
        $colors_options .= '<option value="">Select Fabric</option>';
        $variation_ID = $_POST['variation_id'];
        $desing = get_post_meta( $variation_ID , '_product_p_manufacture_ids', true );
        $desing = ! empty( $desing ) ? array_map( 'absint',  $desing ) : null;

        $color = get_post_meta( $variation_ID , '_product_p_color_ids', true );
        $color = ! empty( $color ) ? array_map( 'absint',  $color ) : null;

                $i = 0;
                foreach ($get_manufactures as $manufacture ) 
                    {
                            if(in_array($manufacture->id, $desing)){
                                $manufacture_options .= '<option value="'.$manufacture->id.'">'.$manufacture->manufacture_name.'</option>';
                            }

                            $i++;
                    }

                $j = 0;
                foreach ($get_colors as $get_color ) 
                    {
                            if(in_array($get_color->id, $color)){
                                $image_url = wp_get_attachment_image_url($get_color->design_color);
                                $colors_options .= '<option value="'.$get_color->id.'" data-src="'.$image_url.'">'.$get_color->design_name.'</option>';
                            }

                            $j++;
                    }



                $response = array( 'status' => true, 'desing' => $manufacture_options, 'color'=>$colors_options);
                echo json_encode($response);
                exit();
}

add_action( 'wp_ajax_nopriv_get_associated_fabric', 'get_associated_fabric_method_call' );
add_action( 'wp_ajax_get_associated_fabric', 'get_associated_fabric_method_call' );

function get_associated_fabric_method_call() {
        global $wpdb;
        global $woocommerce;

        $post_id = $_POST['p_id'];
        $get_mid = $_POST['mid'];
        $get_colors = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."keeki_designs` WHERE mid =  $get_mid ");
        $colors_options .= '<option value="">Select Fabric</option>';
        $color = get_post_meta( $post_id , '_m_product_p_color_ids', true );
        $color = ! empty( $color ) ? array_map( 'absint',  $color ) : null;

                $j = 0;
                foreach ($get_colors as $get_color ) 
                    {
                            if(in_array($get_color->id, $color)){
                                $image_url = wp_get_attachment_image_url($get_color->design_color);
                                $colors_options .= '<option value="'.$get_color->id.'" data-src="'.$image_url.'">'.$get_color->design_name.'</option>';
                            }

                            $j++;
                    }


                $response = array( 'status' => true, 'color'=>$colors_options);
                echo json_encode($response);
                exit();
}




// add_action( 'woocommerce_email_order_details', 'am_wdm_delivery_info_to_order_email', 5, 4 ); 
// add_action( 'woocommerce_email_order_meta', 'am_wdm_delivery_info_to_order_email', 5, 4 ); 

add_action( 'woocommerce_email_after_order_table', 'am_wdm_delivery_info_to_order_email', 5, 4 ); 


function am_wdm_delivery_info_to_order_email( $order, $sent_to_admin, $plain_text, $email ) {
    // Only customers need to know about the delivery times.
    if ( $sent_to_admin ) {
                $items = $order->get_items();
                foreach ( $items as $item_id => $item ) {
                         if ( wc_get_order_item_meta( $item_id, 'Supplier', true ) ){
                                echo '<p><strong> Supplier: </strong>'.wc_get_order_item_meta( $item_id, 'Supplier', true ); 
                                echo '</p>';

                            }
                }
    }
    else
    {
            return;

    }
}





add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'unset_specific_order_item_meta_data', 10, 2);
function unset_specific_order_item_meta_data($formatted_meta, $item){
    // Only on emails notifications
    if( is_admin() || is_wc_endpoint_url() )
    {
        return $formatted_meta;
    }
    else
    {
                foreach( $formatted_meta as $key => $meta ){
                        if( in_array( $meta->key, array('Supplier') ) )
                            unset($formatted_meta[$key]);
                }

        return $formatted_meta;

    }


}











/*
function kia_hide_mnm_meta_in_emails( $meta ) {

    if( ! is_admin() ) {
        $criteria = array(  'key' => 'Supplier' );
        $meta = wp_list_filter( $meta, $criteria, 'NOT' );
    }
    return $meta;

}
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'kia_hide_mnm_meta_in_emails' );
*/

/*
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'unset_specific_order_item_meta_data', 10, 2);
function unset_specific_order_item_meta_data($formatted_meta, $item){
    // Only on emails notifications
    if( is_admin() || is_wc_endpoint_url() )
    {
        return $formatted_meta;
    }
    else
    {
                foreach( $formatted_meta as $key => $meta ){
                        if( in_array( $meta->key, array('Supplier') ) )
                            unset($formatted_meta[$key]);
                }

                return $formatted_meta;

    }


}

function kia_hide_mnm_meta_in_emails( $meta ) {
    if( ! is_admin() ) {
        $criteria = array(  'key' => 'Part of' );
        $meta = wp_list_filter( $meta, $criteria, 'NOT' );
    }
    return $meta;
}
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'kia_hide_mnm_meta_in_emails' );

*/

/*
add_filter( 'woocommerce_email_recipient_customer_processing_order', 'conditional_email_notification', 10, 2 );
add_filter( 'woocommerce_email_recipient_customer_completed_order', 'conditional_email_notification', 10, 2 );
function conditional_email_notification( $recipient, $order ) {
    if( is_admin() ) return $recipient;





        $order = new WC_Order($order->get_id());
        foreach( $order->get_items() as $item ){

              $item_data = $item->get_data();
              $item_id     = $item->get_id();

              if ( wc_get_order_item_meta( $item_id, 'Supplier', true ) ){
                    return '';
                }


          }


    if ( get_post_meta( $order->get_id(), 'Supplier', true ) ){
        return '';
    }


    return $recipient;
}
*/

/* Disable auto update of plugin and theme */
add_filter( 'auto_update_plugin', '__return_false' );
add_filter( 'auto_update_theme', '__return_false' );



         
// add_action( 'woocommerce_save_product_variation', 'action_woocommerce_save_product_variation', 10, 2 ); 
// add_filter('woocommerce_rest_prepare_product_object', 'keeki_change_product_response', 20, 3);

add_filter('woocommerce_rest_prepare_product_variation_object', 'keeki_change_product_response', 20, 3);

function keeki_change_product_response($response, $object, $request) {
    global $wpdb;

    if (empty($response->data))
        return $response;

            $temp_response = $response;

        if($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['REQUEST_METHOD'] != 'PUT' && $_SERVER['REQUEST_METHOD'] != 'GET')
            {

                $id = $object->get_id(); 
                $variation_id = $id;

                $check_if_update = get_post_meta( $variation_id, 'is_variations_update',true);
                if($check_if_update){

                    $response = product_variation_update($variation_id , $response, $object, $request);

                }
                else
                {

                    if($variation_id){

                        $get_current_imgIds = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."netsuite_img_temp` WHERE product_id = $variation_id");
    
    
                        if($get_current_imgIds){
    
                            // Noting to do
    
                        }
                        else
                        {
    
                            $temp_array = array('0'=>'', '1'=>'', '2'=>'', '3'=>'', '4'=>'', '5'=>'', '6'=>'', '7'=>'', '8'=>'', '9'=>'' );
    
                            $created            = date("Y-m-d h:i:s");
                            $update             = '';
    
                            $image_ids_check = $wpdb->insert( 
                                                    $wpdb->prefix.'netsuite_img_temp', 
                                                    array( 
                                                        'product_id' => $variation_id,
                                                        'image_ids'  => serialize($temp_array),
                                                        'created'    => $created,
                                                        'update'     => $update
                                                    ), 
                                                    array( 
                                                        '%d',
                                                        '%s',
                                                        '%s',
                                                        '%s'
                                                    ) 
                                                );
                        }
    
                        if($image_ids_check){
                            if (!empty($request['images_netsuite']) && is_array($request['images_netsuite'])) {
                                    $uploadImageIds = array();
                                    $imageInfo      = array();
                                    $singleImageId  = '';
                                    $response->data['images_netsuite']   = array();
    
                                    foreach ($request['images_netsuite'] as $ref_img_url) {
                                         if($ref_img_url['src'] != ''){
                                             $singleImageId              = keeki_2021_saveFile($ref_img_url['src']);
                                             $imageInfo[$singleImageId]  = $ref_img_url['position'];
    
                                            $response->data['images_netsuite'][] = array(
                                                                                    'id' => $singleImageId,
                                                                                    'position' => $ref_img_url['position']
                                                                                );
    
                                            $temp_array[$ref_img_url['position']] = $singleImageId; 
                                         }
                                         
                                    }
    
    
    
    
                                                                   /* Update the Images and Gallery */
    
                                 if (is_array($temp_array)) {
                                         $gallery = array();
                                         $is_main_Img = '';
    
    
                                        $update    = date("Y-m-d h:i:s");
                                        $ifupdated = $wpdb->update( 
                                                     $wpdb->prefix.'netsuite_img_temp', 
                                                        array( 
                                                            'image_ids' => serialize($temp_array),
                                                            'update'    => $update
                                                        ), 
                                                        array( 'product_id' => $variation_id ), 
                                                        array( 
                                                            '%s',
                                                            '%s'
                                                        ) ,
                                                        array( 
                                                            '%d'
                                                        ) 
                                                );
    
                                        if ( false === $ifupdated ) {
                                                    $response->data['error'] = 'DB Error';
                                                } else {
                                                    $response->data['sucess'] = 'Updated Sucessfully';
                                                }
    
    
    
    
                                         foreach ($temp_array as $position => $image) {
                                            if($image != ''){
                                                if($is_main_Img == ''){
                                                     $attachment_id = isset($image) ? absint($image) : 0;
                                                     if($attachment_id != 0){
                                                        set_post_thumbnail($variation_id, $attachment_id);
                                                        $is_main_Img = true; 
                                                     }
                                                }
                                                else
                                                {
                                                      $attachment_id = isset($image) ? absint($image) : 0;
                                                      if (0 === $attachment_id) {
                                                                // Noting to do
                                                       } else {
                                                             $gallery[] = $attachment_id;
                                                        }
                                                    
                                                }
    
                                            }
    
                                         }
    
    
                                         if (!empty($gallery)) {
                                             update_post_meta($variation_id, 'rtwpvg_images', $gallery );
                                         }
                                         
                                     } 
                                  else 
                                     {
                                         //delete_post_thumbnail($id);
                                         // update_post_meta($id, '_product_image_gallery', '');
                                     }
    
                            }
                        }
    
    
    
                    }
                    
                }




            }
    
    
        if($_SERVER['REQUEST_METHOD'] == 'PUT' && $_SERVER['REQUEST_METHOD'] != 'POST' && $_SERVER['REQUEST_METHOD'] != 'GET')
             {

                    
                     $id = $object->get_id(); 
                     $variation_id = $id;
                     $response = product_variation_update($variation_id , $response, $object, $request);
                     
                     /*
                        if($variation_id){

                            $temp_put_array = array();
                            $get_current_imgIds = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."netsuite_img_temp` WHERE product_id = $variation_id");

                            if($get_current_imgIds){
                            $get_put_images_identifier =  unserialize($get_current_imgIds->image_ids);
                            }

                            $response->data['images_netsuite']   = array();
                            if (!empty($request['images_netsuite']) && is_array($request['images_netsuite'])) {
                                    $uploadImageIds = array();
                                    $imageInfo      = array();
                                    $singleImageId  = '';

                                    foreach ($request['images_netsuite'] as $ref_img_url) {
                                        if($ref_img_url['src'] != ''){
                                                $singleImageId              = keeki_2021_saveFile($ref_img_url['src']);
                                                $imageInfo[$singleImageId]  = $ref_img_url['position'];

                                            // $temp_positions[] = $ref_img_url['position'];

                                                $response->data['images_netsuite'][] = array(
                                                                                            'id' => $singleImageId,
                                                                                            'position' => $ref_img_url['position']
                                                                                        );

                                                $temp_put_array[$ref_img_url['position']] = $singleImageId; 
                                                if (is_array($get_put_images_identifier)) 
                                                {
                                                        $get_put_images_identifier[$ref_img_url['position']] = $singleImageId;
                                                } 
                
                                            }
                                        
                                    }

                                    if (is_array($get_put_images_identifier)) {

                                        $gallery = array();
                                        $is_main_Img = '';

                                        $update    = date("Y-m-d h:i:s");
                                        $ifupdated = $wpdb->update( 
                                                        $wpdb->prefix.'netsuite_img_temp', 
                                                                array( 
                                                                    'image_ids' => serialize($get_put_images_identifier),
                                                                    'update'    => $update
                                                                ), 
                                                                array( 'product_id' => $variation_id ), 
                                                                array( 
                                                                    '%s',
                                                                    '%s'
                                                                ) ,
                                                                array( 
                                                                    '%d'
                                                                ) 
                                                        );

                                        if ( false === $ifupdated ) {
                                                $response->data['error'] = 'DB Error';
                                            } 
                                    else {
                                                $response->data['sucess'] = 'Updated Sucessfully';
                                            }


                                        foreach ($get_put_images_identifier as $position => $image) {
                                            if($image != ''){
                                                if($is_main_Img == ''){
                                                    $attachment_id = isset($image) ? absint($image) : 0;
                                                    if($attachment_id != 0){
                                                        set_post_thumbnail($variation_id, $attachment_id);
                                                        $is_main_Img = true; 
                                                    }
                                                }
                                                else
                                                {
                                                    $attachment_id = isset($image) ? absint($image) : 0;
                                                    if (0 === $attachment_id) {
                                                                // Noting to do
                                                    } else {
                                                            $gallery[] = $attachment_id;
                                                        }
                                                    
                                                }

                                            }
                                        }


                                        if (!empty($gallery)) {
                                            update_post_meta($variation_id, 'rtwpvg_images', $gallery );
                                        }


                                    } 
                                else {
                                        //delete_post_thumbnail($id);
                                        // update_post_meta($id, '_product_image_gallery', '');
                                    }

                            }

                        }
                     */


             
             }


        if($_SERVER['REQUEST_METHOD'] == 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST' && $_SERVER['REQUEST_METHOD'] != 'PUT')
             {
                 
                	$id = $object->get_id(); 
	                $variation_id = $id;

                    if($variation_id){


                        $get_imgIds = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."netsuite_img_temp` WHERE product_id = $variation_id");

                        if($get_imgIds){
                           $get_identifiers =  unserialize($get_imgIds->image_ids);

                           if(is_array($get_identifiers)){
                                foreach ( $get_identifiers as $postion => $get_img_id ){
                                    if($get_img_id != ''){
                                        $response->data['images_netsuite'][] = array(
                                                                                    'id' => $get_img_id,
                                                                                    'position' => $postion
                                
                                                                            );
                                    }

                                }
                            }

                        }

                    }


             
             }
            
        return $response;
 
}



add_filter( 'woocommerce_rest_prepare_product_object', 'wc_app_add_custom_data_to_product', 20, 3 );

function wc_app_add_custom_data_to_product( $response, $object, $request ) {
    global $wpdb;

	if (empty($response->data))
        return $response;


    if($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['REQUEST_METHOD'] != 'PUT' && $_SERVER['REQUEST_METHOD'] != 'GET'){
                
             
        	 $create_product_id = $object->get_id(); 
        	 if($create_product_id){


                    $get_current_imgIds = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."netsuite_img_temp` WHERE product_id = $create_product_id");

                    if($get_current_imgIds){

                        // Noting to do
                    }
                    else
                    {
                        $temp_array = array('0'=>'', '1'=>'', '2'=>'', '3'=>'', '4'=>'', '5'=>'', '6'=>'', '7'=>'', '8'=>'', '9'=>'' );

                        $created            = date("Y-m-d h:i:s");
                        $update             = '';

                        $image_ids_check = $wpdb->insert( 
                                                $wpdb->prefix.'netsuite_img_temp', 
                                                array( 
                                                    'product_id' => $create_product_id,
                                                    'image_ids'  => serialize($temp_array),
                                                    'created'    => $created,
                                                    'update'     => $update
                                                ), 
                                                array( 
                                                    '%d',
                                                    '%s',
                                                    '%s',
                                                    '%s'
                                                ) 
                                            );
                    }


                    if($image_ids_check){
                        $response->data['images_netsuite']   = array();
                        if (!empty($request['images_netsuite']) && is_array($request['images_netsuite'])){
                                $uploadImageIds = array();
                                $imageInfo      = array();
                                $singleImageId  = '';

                                foreach ($request['images_netsuite'] as $ref_img_url) {
                                     if($ref_img_url['src'] != ''){
                                        $singleImageId              = keeki_2021_saveFile($ref_img_url['src']);
                                        $imageInfo[$singleImageId]  = $ref_img_url['position'];
                                        $response->data['images_netsuite'][] = array(
                                                                                'id' => $singleImageId,
                                                                                'position' => $ref_img_url['position']
                                                                            );

                                        $temp_array[$ref_img_url['position']] = $singleImageId;  

                                     }
                                     
                                }

                                /* Update the Images and Gallery */

                                 if (is_array($temp_array)) {
                                     $gallery = array();
                                     $is_main_Img = '';


                                    $update    = date("Y-m-d h:i:s");
                                    $ifupdated = $wpdb->update( 
                                                 $wpdb->prefix.'netsuite_img_temp', 
                                                    array( 
                                                        'image_ids' => serialize($temp_array),
                                                        'update'    => $update
                                                    ), 
                                                    array( 'product_id' => $create_product_id ), 
                                                    array( 
                                                        '%s',
                                                        '%s'
                                                    ) ,
                                                    array( 
                                                        '%d'
                                                    ) 
                                            );

                                    if ( false === $ifupdated ) {
                                                $response->data['error'] = 'DB Error';
                                            } else {
                                                $response->data['sucess'] = 'Updated Sucessfully';
                                            }




                                     foreach ($temp_array as $position => $image) {
                                        if($image != ''){
                                            if($is_main_Img == ''){
                                                 $attachment_id = isset($image) ? absint($image) : 0;
                                                 if($attachment_id != 0){
                                                    set_post_thumbnail($create_product_id, $attachment_id);
                                                    $is_main_Img = true; 
                                                 }
                                            }
                                            else
                                            {
                                                  $attachment_id = isset($image) ? absint($image) : 0;
                                                  if (0 === $attachment_id) {
                                                            // Noting to do
                                                   } else {
                                                         $gallery[] = $attachment_id;
                                                    }
                                                
                                            }

                                        }

                                     }



                                     if (!empty($gallery)) {
                                         update_post_meta($create_product_id, '_product_image_gallery', implode(',', $gallery));
                                     }
                                 } 
                              else 
                                 {
                                     //delete_post_thumbnail($id);
                                     // update_post_meta($id, '_product_image_gallery', '');
                                 }

                        }


                    }
                }
    }


    if($_SERVER['REQUEST_METHOD'] == 'PUT' && $_SERVER['REQUEST_METHOD'] != 'POST' && $_SERVER['REQUEST_METHOD'] != 'GET'){


             $create_product_id = $object->get_id(); 
             if($create_product_id){

                $temp_put_array = array();

                $get_current_imgIds = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."netsuite_img_temp` WHERE product_id = $create_product_id");

                if($get_current_imgIds){
                   $get_put_images_identifier =  unserialize($get_current_imgIds->image_ids);

                }

               



                $response->data['images_netsuite']   = array();
                if (!empty($request['images_netsuite']) && is_array($request['images_netsuite'])) {
                        $uploadImageIds = array();
                        $imageInfo      = array();
                        $singleImageId  = '';

                        foreach ($request['images_netsuite'] as $ref_img_url) {
                             if($ref_img_url['src'] != ''){
                                    $singleImageId              = keeki_2021_saveFile($ref_img_url['src']);
                                    $imageInfo[$singleImageId]  = $ref_img_url['position'];

                                   // $temp_positions[] = $ref_img_url['position'];

                                    $response->data['images_netsuite'][] = array(
                                                                                'id' => $singleImageId,
                                                                                'position' => $ref_img_url['position']
                                                                            );

                                    $temp_put_array[$ref_img_url['position']] = $singleImageId; 
                                     if (is_array($get_put_images_identifier)) 
                                     {
                                            $get_put_images_identifier[$ref_img_url['position']] = $singleImageId;
                                     } 
      
                                }
                             
                        }


                        if (is_array($get_put_images_identifier)) {

                             $gallery = array();
                             $is_main_Img = '';


                             /* Update query here */

                            update_post_meta($create_product_id, '_netsuite_images_identifier', $get_put_images_identifier[0] );


                            $update    = date("Y-m-d h:i:s");
                            $ifupdated = $wpdb->update( 
                                            $wpdb->prefix.'netsuite_img_temp', 
                                                    array( 
                                                        'image_ids' => serialize($get_put_images_identifier),
                                                        'update'    => $update
                                                    ), 
                                                    array( 'product_id' => $create_product_id ), 
                                                    array( 
                                                        '%s',
                                                        '%s'
                                                    ) ,
                                                    array( 
                                                        '%d'
                                                    ) 
                                            );

                            if ( false === $ifupdated ) {
                                    $response->data['error'] = 'DB Error';
                                } 
                           else {
                                    $response->data['sucess'] = 'Updated Sucessfully';
                                }



                             foreach ($get_put_images_identifier as $position => $image) {
                                if($image != ''){
                                    if($is_main_Img == ''){
                                         $attachment_id = isset($image) ? absint($image) : 0;
                                         if($attachment_id != 0){
                                            set_post_thumbnail($create_product_id, $attachment_id);
                                            $is_main_Img = true; 
                                         }
                                    }
                                    else
                                    {
                                          $attachment_id = isset($image) ? absint($image) : 0;
                                          if (0 === $attachment_id) {
                                                    // Noting to do
                                           } else {
                                                 $gallery[] = $attachment_id;
                                            }
                                        
                                    }

                                }
                             }


                            if (!empty($gallery)) {
                                 update_post_meta($create_product_id, '_product_image_gallery', implode(',', $gallery));
                             }


                         } 
                    else {
                             //delete_post_thumbnail($id);
                             // update_post_meta($id, '_product_image_gallery', '');
                         }

                }

             }

    }

    if($_SERVER['REQUEST_METHOD'] == 'GET' && $_SERVER['REQUEST_METHOD'] != 'POST' && $_SERVER['REQUEST_METHOD'] != 'PUT'){


     		$create_product_id = $object->get_id(); 
     		if($create_product_id){


                $get_imgIds = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."netsuite_img_temp` WHERE product_id = $create_product_id");

                if($get_imgIds){
                   $get_identifiers =  unserialize($get_imgIds->image_ids);

                   if(is_array($get_identifiers)){
                        foreach ( $get_identifiers as $postion => $get_img_id ){
                            if($get_img_id != ''){
                                $response->data['images_netsuite'][] = array(
                                                                            'id' => $get_img_id,
                                                                            'position' => $postion
                        
                                                                    );
                            }

                        }
                    }

                }

     		}

    }


  return $response;

}



function get_real_filename_2021($headers,$url){
                foreach($headers as $header)
                {
                    if (strpos(strtolower($header),'content-disposition') !== false)
                    {
                        $tmp_name = explode('=', $header);
                        if ($tmp_name[1]) return trim($tmp_name[1],'";\'');
                    }
                }

                $stripped_url = preg_replace('/\\?.*/', '', $url);
                return basename($stripped_url);
            
}


function keeki_2021_saveFile($imageurl){
    
        $location = ABSPATH.'temp/';
        $site_url = site_url();
    
        $fileextension = image_type_to_extension( exif_imagetype( $imageurl ) );
        $ext  = pathinfo( $imageurl, PATHINFO_EXTENSION );
        $name = pathinfo( $imageurl, PATHINFO_FILENAME )  . $fileextension;
    
        $myfile = file_get_contents($imageurl);    
    
        $filename = get_real_filename_2021($http_response_header,$imageurl);
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $imageurl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        $picture = curl_exec($ch);
        curl_close($ch);
    
        $file_location = $location.$filename;
    
        $fh = fopen($file_location, 'c');
        fwrite($fh, $picture);
        fclose($fh);
        
        $ImgURL = $site_url.'/temp/'.$filename;
    
        $getURL = upload_image_from_url_2021($ImgURL);
        unlink($file_location);
    
        return $getURL;
}


function upload_image_from_url_2021( $imageurl ){
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                require_once( ABSPATH . 'wp-admin/includes/media.php' );

                $fileextension = image_type_to_extension( exif_imagetype( $imageurl ) );

    
                $tmp = download_url( $imageurl );
                
                if ( is_wp_error( $tmp ) ) 
                {
                    @unlink( $file_array[ 'tmp_name' ] );
                    return $tmp;
                }



                $myfile = file_get_contents($imageurl);    
                $filename = get_real_filename_2021($http_response_header,$imageurl);

                // Image base name:
                $name = $filename;

                $path = pathinfo( $tmp );
                if( ! isset( $path['extension'] ) ):
                    $tmpnew = $tmp . '.tmp';
                    if( ! rename( $tmp, $tmpnew ) ):
                        return '';
                    else:
                        $ext  = pathinfo( $imageurl, PATHINFO_EXTENSION );
                        $name = pathinfo( $imageurl, PATHINFO_FILENAME )  . $fileextension;
                        $tmp = $tmpnew;
                    endif;
                endif;
    

                $file_array = array(
                    'name'     => $name,
                    'tmp_name' => $tmp
                );
    
    
                $attachmentId = media_handle_sideload( $file_array, 0 );

                        if ( is_wp_error($attachmentId) ) {
                            @unlink($file['tmp_name']);
                            var_dump( $attachmentId->get_error_messages( ) );
                        } else {                
                            $image = $attachmentId;
                        }


                // Check for handle sideload errors:
                if ( is_wp_error( $attachmentId ) )
                {
                    @unlink( $file_array['tmp_name'] );
                    return $attachmentId;
                }

                // Get the attachment url:
               //  $attachment_url = wp_get_attachment_url( $attachmentId );

    
                return $attachmentId;
} 


function product_variation_update($variation_id , $response, $object, $request){

        global $wpdb;
       // $id = $object->get_id(); 
       // $variation_id = $id;
        if($variation_id){

            $temp_put_array = array();

            $get_current_imgIds = $wpdb->get_row("SELECT * FROM `".$wpdb->prefix."netsuite_img_temp` WHERE product_id = $variation_id");

            if($get_current_imgIds){
                $get_put_images_identifier =  unserialize($get_current_imgIds->image_ids);
            }

            $response->data['images_netsuite']   = array();
            if (!empty($request['images_netsuite']) && is_array($request['images_netsuite'])) {
                    $uploadImageIds = array();
                    $imageInfo      = array();
                    $singleImageId  = '';

                    foreach ($request['images_netsuite'] as $ref_img_url) {
                            if($ref_img_url['src'] != ''){
                                $singleImageId              = keeki_2021_saveFile($ref_img_url['src']);
                                $imageInfo[$singleImageId]  = $ref_img_url['position'];

                                // $temp_positions[] = $ref_img_url['position'];

                                $response->data['images_netsuite'][] = array(
                                                                            'id' => $singleImageId,
                                                                            'position' => $ref_img_url['position']
                                                                        );

                                $temp_put_array[$ref_img_url['position']] = $singleImageId; 
                                    if (is_array($get_put_images_identifier)) 
                                    {
                                        $get_put_images_identifier[$ref_img_url['position']] = $singleImageId;
                                    } 

                            }
                            
                    }

                    if (is_array($get_put_images_identifier)) {

                            $gallery = array();
                            $is_main_Img = '';

                        $update    = date("Y-m-d h:i:s");
                        $ifupdated = $wpdb->update( 
                                        $wpdb->prefix.'netsuite_img_temp', 
                                                array( 
                                                    'image_ids' => serialize($get_put_images_identifier),
                                                    'update'    => $update
                                                ), 
                                                array( 'product_id' => $variation_id ), 
                                                array( 
                                                    '%s',
                                                    '%s'
                                                ) ,
                                                array( 
                                                    '%d'
                                                ) 
                                        );

                        if ( false === $ifupdated ) {
                                $response->data['error'] = 'DB Error';
                            } 
                        else {
                                $response->data['sucess'] = 'Updated Sucessfully';
                            }


                            foreach ($get_put_images_identifier as $position => $image) {
                            if($image != ''){
                                if($is_main_Img == ''){
                                        $attachment_id = isset($image) ? absint($image) : 0;
                                        if($attachment_id != 0){
                                        set_post_thumbnail($variation_id, $attachment_id);
                                        $is_main_Img = true; 
                                        }
                                }
                                else
                                {
                                        $attachment_id = isset($image) ? absint($image) : 0;
                                        if (0 === $attachment_id) {
                                                // Noting to do
                                        } else {
                                                $gallery[] = $attachment_id;
                                        }
                                    
                                }

                            }
                            }


                        if (!empty($gallery)) {
                                update_post_meta($variation_id, 'rtwpvg_images', $gallery );
                            }


                        } 
                else {
                            //delete_post_thumbnail($id);
                            // update_post_meta($id, '_product_image_gallery', '');
                        }

            }

        }

        return $response;

}


function action_woocommerce_update_product_variation( $product_get_id ) { 
    update_post_meta($product_get_id, 'is_variations_update', true );
}; 
         
// add the action 
add_action( 'woocommerce_update_product_variation', 'action_woocommerce_update_product_variation', 10, 1 );