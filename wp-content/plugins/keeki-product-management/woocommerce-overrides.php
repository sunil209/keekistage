<?php
namespace KPM;
/*
 * Contains overrides for Woocommerce templates and functionality where we cannot avoid but stopping default behaviour.
 * This will need to be maintained with woocommerce version upgrades
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WCOverrides {

    /**
     *  Setup
     */
    public function __construct() {
        add_action('init',array($this,'init'));
    }

    /**
    * On WC init
    */
    public function init(){


        /** Inline quick edit */
        remove_action('quick_edit_custom_box', 'woocommerce_admin_product_quick_edit', 10,2);
        add_action( 'quick_edit_custom_box',  array($this,'keeki_admin_product_quick_edit'), 10, 2 );

    }

    /**
     * Custom quick edit - form
     *
     * @access public
     * @param mixed $column_name
     * @param mixed $post_type
     * @return void
     */
    public function keeki_admin_product_quick_edit( $column_name, $post_type ) {
        if ($column_name != 'price' || $post_type != 'product') return;
        ?>
        <fieldset class="inline-edit-col-left">
            <div id="woocommerce-fields" class="inline-edit-col">quick_edit_custom_box

                <h4><?php _e( 'Product Data', 'woocommerce' ); ?></h4>

                <?php if( get_option('woocommerce_enable_sku', true) !== 'no' ) : ?>

                    <label>
                        <span class="title"><?php _e( 'SKU', 'woocommerce' ); ?></span>
                        <span class="input-text-wrap">
                            <input type="text" name="_sku" class="text sku" value="" disabled>
                        </span>
                    </label>
                    <br class="clear" />

                <?php endif; ?>

                <div class="price_fields">
                    <label>
                        <span class="title"><?php _e( 'Price', 'woocommerce' ); ?></span>
                        <span class="input-text-wrap">
                            <input type="text" name="_regular_price" class="text regular_price" placeholder="<?php _e( 'Regular price', 'woocommerce' ); ?>" value="" disabled>
                        </span>
                    </label>
                    <br class="clear" />
                    <label>
                        <span class="title"><?php _e( 'Sale', 'woocommerce' ); ?></span>
                        <span class="input-text-wrap">
                            <input type="text" name="_sale_price" class="text sale_price" placeholder="<?php _e( 'Sale price', 'woocommerce' ); ?>" value="" disabled>
                        </span>
                    </label>
                    <br class="clear" />
                </div>

                <?php if ( get_option('woocommerce_enable_weight') == "yes" || get_option('woocommerce_enable_dimensions') == "yes" ) : ?>
                    <div class="dimension_fields">

                        <?php if ( get_option('woocommerce_enable_weight') == "yes" ) : ?>
                            <label>
                                <span class="title"><?php _e( 'Weight', 'woocommerce' ); ?></span>
                            <span class="input-text-wrap">
                                <input type="text" name="_weight" class="text weight" placeholder="0.00" value="" disabled>
                            </span>
                            </label>
                            <br class="clear" />
                        <?php endif; ?>

                        <?php if ( get_option('woocommerce_enable_dimensions') == "yes" ) : ?>
                            <div class="inline-edit-group dimensions">
                                <div>
                                    <span class="title"><?php _e( 'L/W/H', 'woocommerce' ); ?></span>
                                <span class="input-text-wrap">
                                    <input type="text" name="_length" class="text length" placeholder="<?php _e( 'Length', 'woocommerce' ); ?>" value="" disabled>
                                    <input type="text" name="_width" class="text width" placeholder="<?php _e( 'Width', 'woocommerce' ); ?>" value="" disabled>
                                    <input type="text" name="_height" class="text height" placeholder="<?php _e( 'Height', 'woocommerce' ); ?>" value="" disabled>
                                </span>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

                <label class="alignleft">
                    <span class="title"><?php _e( 'Visibility', 'woocommerce' ); ?></span>
                    <span class="input-text-wrap">
                        <select class="visibility" name="_visibility">
                            <?php
                            $options = array(
                                'visible' => __( 'Catalog &amp; search', 'woocommerce' ),
                                'catalog' => __( 'Catalog', 'woocommerce' ),
                                'search' => __( 'Search', 'woocommerce' ),
                                'hidden' => __( 'Hidden', 'woocommerce' )
                            );
                            foreach ($options as $key => $value) {
                                echo '<option value="'.$key.'">'. $value .'</option>';
                            }
                            ?>
                        </select>
                    </span>
                </label>
                <label class="alignleft featured">
                    <input type="checkbox" name="_featured" value="1">
                    <span class="checkbox-title"><?php _e( 'Featured', 'woocommerce' ); ?></span>
                </label>
                <br class="clear" />
                <label class="alignleft">
                    <span class="title"><?php _e( 'In stock?', 'woocommerce' ); ?></span>
                    <span class="input-text-wrap">
                        <select class="stock_status" name="_stock_status">
                            <?php
                            $options = array(
                                'instock' => __( 'In stock', 'woocommerce' ),
                                'outofstock' => __( 'Out of stock', 'woocommerce' )
                            );
                            foreach ($options as $key => $value) {
                                echo '<option value="'.$key.'">'. $value .'</option>';
                            }
                            ?>
                        </select>
                    </span>
                </label>

                <div class="stock_fields">

                    <?php if (get_option('woocommerce_manage_stock')=='yes') : ?>
                        <label class="alignleft manage_stock">
                            <input type="checkbox" name="_manage_stock" value="1">
                            <span class="checkbox-title"><?php _e( 'Manage stock?', 'woocommerce' ); ?></span>
                        </label>
                        <br class="clear" />
                        <label class="stock_qty_field">
                            <span class="title"><?php _e( 'Stock Qty', 'woocommerce' ); ?></span>
                            <span class="input-text-wrap">
                                <input type="text" name="_stock" class="text stock" value="" disabled>
                            </span>
                        </label>
                    <?php endif; ?>

                </div>

                <input type="hidden" name="woocommerce_quick_edit_nonce" value="<?php echo wp_create_nonce( 'woocommerce_quick_edit_nonce' ); ?>" />
            </div>
        </fieldset>
    <?php
    }
}
//Self initilize
new WCOverrides();
?>