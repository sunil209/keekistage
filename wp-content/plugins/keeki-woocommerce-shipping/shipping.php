<?php
/*
	Plugin Name: WooCommerce Keeki Shipping
	Plugin URI:
	Description: Keeki Custom Shipping calculations
	Version: 1
	Author: Keeki
	Author URI:
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Check if WooCommerce is active
 */
if ( is_woocommerce_active() ) {

	/**
	 * woocommerce_init_shipping_table_rate function.
	 *
	 * @access public
	 * @return void
	 */
	function wc_keeki_shipping_init() {
		include_once( 'classes/wc-keeki-shipping.class.php' );
	}

	add_action( 'woocommerce_shipping_init', 'wc_keeki_shipping_init' );

	/**
	 * wc_australia_post_add_method function.
	 *
	 * @access public
	 * @param mixed $methods
	 * @return void
	 */
	function wc_keeki_shipping_methods( $methods ) {
		$methods[] = 'WC_Keeki_Shipping';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'wc_keeki_shipping_methods' );

	/**
	 * wc_australia_post_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	function wc_shipping_scripts() {
		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	add_action( 'admin_enqueue_scripts', 'wc_shipping_scripts' );
    if (is_admin() && !defined('DOING_AJAX')) {
        add_action('init', 'keeki_shipping_install');
    }

    /**
     * First time install/activation
     */
    function keeki_shipping_install() {
        $version = '1.0';
        require_once plugin_dir_path(__FILE__) .'install.php';
        require_once plugin_dir_path(__FILE__) .'classes/db.class.php';
        register_activation_hook( __FILE__, 'keekiShippingActivate' );
        if ( get_option('keeki_shipping_version') != $version ) {
            add_option('keeki_shipping_version', 0);
            add_action('admin_init', 'keekiShippingInstall', 0);
        }
    }

}
