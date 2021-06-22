 <?php
/**
 * wc_keeki_shipping class.
 *
 * @extends WC_Shipping_Method
 */
class wc_keeki_shipping extends WC_Shipping_Method {

    public $version = '0.1';

	/**
	 * __constructor for Keeki Shipping class
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->id                 = 'keeki_shipping';
		$this->method_title       = __( 'Keeki Shipping', 'wc_keeki_shipping' );
		$this->method_description = __( 'Keeki shipping calculator.', 'wc_keeki_shipping' );

		$this->init();
	}

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    private function init() {
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        //print_r($this->settings);
        // Define user set variables
        $this->title           = isset( $this->settings['title'] ) ? $this->settings['title'] : $this->method_title;
        $this->availability    = isset( $this->settings['availability'] ) ? $this->settings['availability'] : 'all';
        $this->countries       = isset( $this->settings['countries'] ) ? $this->settings['countries'] : array();
        $this->origin          = isset( $this->settings['origin'] ) ? $this->settings['origin'] : '';

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        //add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'clear_transients' ) );
    }

	/**
	 * environment_check function.
	 *
	 * @access public
	 * @return void
	 */
	private function environment_check() {
		global $woocommerce;

		if ( get_woocommerce_currency() != "AUD" ) {
			echo '<div class="error">
				<p>' . sprintf( __( 'This shipping method requires that the <a href="%s">currency</a> is set to Australian Dollars.', 'wc_keeki_shipping' ), admin_url( 'admin.php?page=woocommerce_settings&tab=catalog' ) ) . '</p>
			</div>';
		}

		elseif ( $woocommerce->countries->get_base_country() != "AU" ) {
			echo '<div class="error">
				<p>' . sprintf( __( 'This shipping method requires that the <a href="%s">base country/region</a> is set to Australia.', 'wc_keeki_shipping' ), admin_url( 'admin.php?page=woocommerce_settings&tab=general' ) ) . '</p>
			</div>';
		}

		/*elseif ( ! $this->origin && $this->enabled == 'yes' ) {
			echo '<div class="error">
				<p>' . __( 'Keeki shipping is enabled, but the origin postcode has not been set.', 'wc_keeki_shipping' ) . '</p>
			</div>';
		}*/
	}

	/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
		// Check users environment supports this method
		$this->environment_check();

		// Show settings
		parent::admin_options();
	}


	/**
	 * clear_transients function.
	 *
	 * @access public
	 * @return void
	 */
	public function clear_transients() {
		delete_transient( 'wc_keeki_shipping_quotes' );
	}

    /**
     * init_form_fields function.
     *
     * @access public
     * @return void
     */
    public function init_form_fields() {
	    global $woocommerce;

    	$this->form_fields  = array(
			'enabled'          => array(
				'title'           => __( 'Enable/Disable', 'wc_keeki_shipping' ),
				'type'            => 'checkbox',
				'label'           => __( 'Enable this shipping method', 'wc_keeki_shipping' ),
				'default'         => 'no'
			),
			'title'            => array(
				'title'           => __( 'Method Title', 'wc_keeki_shipping' ),
				'type'            => 'text',
				'description'     => __( 'This controls the title which the user sees during checkout.', 'wc_keeki_shipping' ),
				'default'         => __( 'Keeki Shipping', 'wc_keeki_shipping' )
			),
			'origin'           => array(
				'title'           => __( 'Origin Postcode', 'wc_keeki_shipping' ),
				'type'            => 'text',
				'description'     => __( 'Enter the postcode for the <strong>sender</strong>.', 'wc_keeki_shipping' ),
				'default'         => ''
		    ),
		    'availability'  => array(
				'title'           => __( 'Method Availability', 'wc_keeki_shipping' ),
				'type'            => 'select',
				'default'         => 'all',
				'class'           => 'availability',
				'options'         => array(
					'all'            => __( 'All Countries', 'wc_keeki_shipping' ),
					'specific'       => __( 'Specific Countries', 'wc_keeki_shipping' ),
				),
			),
			'countries'        => array(
				'title'           => __( 'Specific Countries', 'wc_keeki_shipping' ),
				'type'            => 'multiselect',
				'class'           => 'chosen_select',
				'css'             => 'width: 450px;',
				'default'         => '',
				'options'         => $woocommerce->countries->get_allowed_countries(),
			)
		);
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package = array() ) {
    	global $woocommerce;

        //$this->rate_cache  = get_transient( 'wc_keeki_shipping_quotes' );

        $cart_ex_tax = $woocommerce->cart->cart_contents_total;
        $cart = $woocommerce->cart->subtotal;
        $postcode = (isset($woocommerce->customer->shipping_postcode) && $woocommerce->customer->shipping_postcode!='')?$woocommerce->customer->shipping_postcode:$woocommerce->customer->postcode;
        $state = (isset($woocommerce->customer->shipping_state) && $woocommerce->customer->shipping_state!='') ? $woocommerce->customer->shipping_state:$woocommerce->customer->state;

        $bulky_item = false;
        $cagetorySlug = false;
        $cartx = 0;
        foreach ($woocommerce->cart->cart_contents as $item) {
            $sku = $item['data']->get_sku();
            
            $customcart = $item['data']->get_sku();
            $customcartQuantity = $item['quantity'];
            $customprice = $item['data']->get_price();


            /* Check in cart if any items of category Mirror */
            $getproductId = wc_get_product_id_by_sku( $sku );
            $catSlug = array();
            
            $terms = get_the_terms ( $getproductId, 'product_cat' );
            foreach ( $terms as $term ) 
                {
                    $catSlug[] = $term->slug;  
                }
            
           // print_r($catSlug);
            
            if(in_array('mirrors', $catSlug))
                {
                   // echo "Enter in Loop";
                    $cagetorySlug = true;
                    $cartx += $customprice * $customcartQuantity;
                   // $cagetorySlug = '';
                }
                


            /* End */
            
            if ($sku=='CT-VERV-ASH') {
                $bulky_item=true;
            }
        }
        
     //  echo $cart.'+'.$cartx;
        
        $cart = $cart - $cartx;
        if($cart < 0)
        {
           $cart = abs($cart); 
        }
 
      //  echo $cart;
        
        /* Override total priceof cart */
        

        if (($cart > 1999) || ($bulky_item == true)) {
            $requestQuote = array(
                'id'       => $this->id,
                'label'    => "Request a quote",
                'cost'     => 0,
                'calc_tax' => 'per_item'
            );
            $this->add_rate( $requestQuote );
            //$woocommerce->add_message( 'For all purchases over $2000 dollars we will contact you with a delivery quote after we recieve your order.' );
            //$woocommerce->add_message( 'Goods on this order are considered a bulky item.  Freight charges will be calculated and advised within 24 hours after order has been placed.' );
        } else {


            if($cagetorySlug == true)
            {
                    
               // echo "in loop".'<br>';
              //   echo $cart.'<br>';
            //    echo $postcode.'<br>';
            //    echo $state.'<br>';
                
                    /* override shipping rates */
                    //$cart, $postcode, $state


                    if($postcode <= 2234 && $postcode >= 2000)
                    {

                        $shipping = $this->getShippingRate($cart, $postcode, $state);
                        if ($shipping) 
                            {
                                $increasedShipping = $shipping+90;
                                $shippingRate = array(
                                    'id'       => $this->id,
                                    'label'    => "Shipping total",
                                    'cost'     => $increasedShipping,
                                    'calc_tax' => 'per_item'
                                );
                                $woocommerce->customer->set_shipping_state($state);
                                $this->add_rate( $shippingRate );

                            }
                        else
                        {
                                $increasedShipping = 90;
                                $shippingRate = array(
                                    'id'       => $this->id,
                                    'label'    => "Shipping total",
                                    'cost'     => $increasedShipping,
                                    'calc_tax' => 'per_item'
                                );
                                $woocommerce->customer->set_shipping_state($state);
                                $this->add_rate( $shippingRate );
                        }

                    }
                    else if($postcode <= 3999 && $postcode >= 3000)
                    {

                            //3207
                        $shipping = $this->getShippingRate($cart, $postcode, $state);
                        if ($shipping) 
                            {
                                $increasedShipping = $shipping+80;
                                $shippingRate = array(
                                    'id'       => $this->id,
                                    'label'    => "Shipping total",
                                    'cost'     => $increasedShipping,
                                    'calc_tax' => 'per_item'
                                );
                                $woocommerce->customer->set_shipping_state($state);
                                $this->add_rate( $shippingRate );
                            }
                           else
                            {
                                    $increasedShipping = 80;
                                    $shippingRate = array(
                                        'id'       => $this->id,
                                        'label'    => "Shipping total",
                                        'cost'     => $increasedShipping,
                                        'calc_tax' => 'per_item'
                                    );
                                    $woocommerce->customer->set_shipping_state($state);
                                    $this->add_rate( $shippingRate );
                            }


                    }
                    else if($postcode <= 4207 && $postcode >= 4000)
                    {


                        $shipping = $this->getShippingRate($cart, $postcode, $state);
                        if ($shipping) 
                            {
                                $increasedShipping = $shipping+110;
                                $shippingRate = array(
                                    'id'       => $this->id,
                                    'label'    => "Shipping total",
                                    'cost'     => $increasedShipping,
                                    'calc_tax' => 'per_item'
                                );
                                $woocommerce->customer->set_shipping_state($state);
                                $this->add_rate( $shippingRate );
                            }
                            else
                            {
                                    $increasedShipping = 110;
                                    $shippingRate = array(
                                        'id'       => $this->id,
                                        'label'    => "Shipping total",
                                        'cost'     => $increasedShipping,
                                        'calc_tax' => 'per_item'
                                    );
                                    $woocommerce->customer->set_shipping_state($state);
                                    $this->add_rate( $shippingRate );
                            }


                    }
                    else if($postcode <= 5199 && $postcode >= 5000)
                    {


                        $shipping = $this->getShippingRate($cart, $postcode, $state);
                        if ($shipping) 
                            {
                                $increasedShipping = $shipping+95;
                                $shippingRate = array(
                                    'id'       => $this->id,
                                    'label'    => "Shipping total",
                                    'cost'     => $increasedShipping,
                                    'calc_tax' => 'per_item'
                                );
                                $woocommerce->customer->set_shipping_state($state);
                                $this->add_rate( $shippingRate );
                            }
                        else
                            {
                                    $increasedShipping = 95;
                                    $shippingRate = array(
                                        'id'       => $this->id,
                                        'label'    => "Shipping total",
                                        'cost'     => $increasedShipping,
                                        'calc_tax' => 'per_item'
                                    );
                                    $woocommerce->customer->set_shipping_state($state);
                                    $this->add_rate( $shippingRate );
                            }


                    }
                    else if($postcode <= 6199 && $postcode >= 6000)
                    {


                        $shipping = $this->getShippingRate($cart, $postcode, $state);
                        if ($shipping) 
                            {
                                $increasedShipping = $shipping+125;
                                $shippingRate = array(
                                    'id'       => $this->id,
                                    'label'    => "Shipping total",
                                    'cost'     => $increasedShipping,
                                    'calc_tax' => 'per_item'
                                );
                                $woocommerce->customer->set_shipping_state($state);
                                $this->add_rate( $shippingRate );
                            }
                         else
                            {
                                    $increasedShipping = 125;
                                    $shippingRate = array(
                                        'id'       => $this->id,
                                        'label'    => "Shipping total",
                                        'cost'     => $increasedShipping,
                                        'calc_tax' => 'per_item'
                                    );
                                    $woocommerce->customer->set_shipping_state($state);
                                    $this->add_rate( $shippingRate );
                            }


                    }
                     else if($postcode <= '0832' && $postcode >= '0800')
                    {


                        $shipping = $this->getShippingRate($cart, $postcode, $state);
                        if ($shipping) 
                            {
                                $increasedShipping = $shipping+200;
                                $shippingRate = array(
                                    'id'       => $this->id,
                                    'label'    => "Shipping total",
                                    'cost'     => $increasedShipping,
                                    'calc_tax' => 'per_item'
                                );
                                $woocommerce->customer->set_shipping_state($state);
                                $this->add_rate( $shippingRate );
                            }
                          else
                            {
                                    $increasedShipping = 200;
                                    $shippingRate = array(
                                        'id'       => $this->id,
                                        'label'    => "Shipping total",
                                        'cost'     => $increasedShipping,
                                        'calc_tax' => 'per_item'
                                    );
                                    $woocommerce->customer->set_shipping_state($state);
                                    $this->add_rate( $shippingRate );
                            }


                    }
                    else
                    {

                         $shipping = $this->getShippingRate($cart, $postcode, $state);
                            if ($shipping) 
                                {
                                    $increasedShipping = $shipping;
                                    $shippingRate = array(
                                        'id'       => $this->id,
                                        'label'    => "Shipping total",
                                        'cost'     => $increasedShipping,
                                        'calc_tax' => 'per_item'
                                    );
                                    $woocommerce->customer->set_shipping_state($state);
                                    $this->add_rate( $shippingRate );
                                }

                    }

            }
            else
            {


                    $shipping = $this->getShippingRate($cart, $postcode, $state);
                    if ($shipping) {
                        $shippingRate = array(
                            'id'       => $this->id,
                            'label'    => "Shipping total",
                            'cost'     => $shipping,
                            'calc_tax' => 'per_item'
                        );
                        $woocommerce->customer->set_shipping_state($state);
                        $this->add_rate( $shippingRate );
                    } else if ($postcode!="") {
                        wc_add_notice(__(sprintf( 'The postcode %s does not exist.', $postcode, $state). $response_array[3], 'wc-tech-authoaim'), 'error');
                        // $woocommerce->add_error( sprintf( 'The postcode %s does not exist.', $postcode, $state), $this->id );
                        return;
                    }


            }

        }

		// Set transient
		//set_transient( 'wc_keeki_shipping_quotes', $this->rate_cache );

    }

    /**
     * Request subtotal amount from db
     *
     * @access private
     * @param int $subtotal
     * @param int $postcode
     * @return void
     */
    private function getShippingRate($subtotal, $postcode, $state) {
        global $woocommerce;
        global $wpdb;

        $table_name = $wpdb->prefix . "keeki_postcodes";

        $result = $wpdb->get_results(
            $wpdb->prepare("SELECT state FROM $table_name WHERE postcode = %s LIMIT 1", $postcode)
        );

        //If postcode exists in the state selected
        /*if (empty($result) || (!empty($result) && $result[0]->state!=$state))
            return false;*/

        if (!empty($result)) {
            $state = $result[0]->state;
            $woocommerce->customer->set_shipping_state($state);
        } else {
            return;
        }

        $table_name = $wpdb->prefix . "keeki_shipping_prices";

        $result = $wpdb->get_results(
            $wpdb->prepare("SELECT price_val FROM $table_name WHERE state = %s AND price_min < %d AND price_max > %d", $state, $subtotal, $subtotal)
        );

        return $result[0]->price_val;
    }

}
