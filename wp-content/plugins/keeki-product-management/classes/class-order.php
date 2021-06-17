<?php
namespace KPM;

require_once KPM_DIR.'models/model-order.php';

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\KPM\Orders' ) ) :

    /**
     * Main Keeki Product Management Class
     *
     *
     * @class Orders
     * @version	0.1
     * @since 0.1
     * @package	KPM
     * @author Conduct
     */
    class Orders {

        /**
         * @var Object product model
         */
        protected $orderModel = null;

        //Override standard magic clone and constructor to ensure single instance exists
        public function __construct() {
            $this->orderModel = OrderModel::getInstance();
            //$this->testOrder();
            //Can't use woocommerce_order_status_completed as it shouldn't catch where order manually ticked over.
            //add_action('woocommerce_order_status_processing',array($this,'addOrderHook'),10,1); //Added to allow manual processing of orders - triggers xml request on manually proccessing payments.
         

            add_action('woocommerce_payment_complete', array($this, 'addOrderHook'),10,1);
            add_action('woocommerce_order_actions', array($this, 'add_order_meta_box_actions')); //we add manual processing to Order actions menu.
            add_action('woocommerce_order_action_keeki_push_to_eastcoast', array($this, 'process_order_meta_box_actions'));

        }

        /**
         * On complete order then add a hook to post this to the POS
         *
         * @param $order_id
         * @return bool
         */
        public function addOrderHook($order_id) {
            $Order = new \WC_Order($order_id);
            if(empty($Order->id)) {
                //No order nothing to update!
                return false;
            }


            $meta = array(
                'gstMode' => ($Order->prices_include_tax ? 'INC' : 'INC')
            );

            $customer = array(
                'title' => '',
                'firstName' => $Order->billing_first_name,
                'lastName' => $Order->billing_last_name,
                'email' => $Order->billing_email,
                'phone' => $Order->billing_phone,
                'mobile' => $Order->billing_phone,
                'fax' => ''
            );

            $billingAddress = array(
                'line1' => $Order->billing_address_1,
                'line2' => $Order->billing_address_2,
                'line3',
                'suburb' => $Order->billing_city,
                'state' => $Order->billing_state,
                'postCode' => $Order->billing_postcode
            );
            $deliveryAddress = array(
                'line1' => $Order->shipping_address_1,
                'line2' => $Order->shipping_address_2,
                'line3',
                'suburb' => $Order->shipping_city,
                'state' => $Order->shipping_state,
                'postCode' => $Order->shipping_postcode
            );
            //We have no other way of seeing if Credit Card other than identifying known CC Plugin Payment Method CBA!

            $fetchCardType = '';
            if($Order->payment_method =='paypal') 
                {   
                    $fetchCardType = 'PA'; 
                }
            elseif($Order->payment_method =='zipmoney') 
                {  
                    $fetchCardType = 'ZP'; 
                }
            else {  $fetchCardType = 'WP'; }
            
            /* Order info */



            /* Order info */


            $payment = array(
                'date' => date('d/m/Y',strtotime($Order->order_date)),
                'total' => $Order->order_total,
                'cardType' => $fetchCardType,
                'reference' => $Order->get_order_number()
            );

            $order_items = $Order->get_items();
            $products = array();
            foreach($order_items as $item) {
                $product = $item->get_product();
                $sku = $product->get_sku();

              /*  if(isset($item['item_meta']['_product_id']) && count($item['item_meta']['_product_id']) === 1) {
                    $product_meta = get_post_meta( $item['item_meta']['_product_id'][0]);
                }

            */
                $products[] = array(
                    'code' => $sku,
                    'description' => wc_clean($item['name']),
                    'qty' => $item['qty'],
                    'price' => $Order->get_item_subtotal($item, $Order->prices_include_tax),
                    'notes' => '' //@TODO Implement notes, from where?
                    //'options'
                );
            }


            list($success,$order_code) = $this->orderModel->placeOrder($meta,$customer,$payment,$products,$billingAddress,$deliveryAddress);
            if($success) {
                $Order->add_order_note('[POS FEEDBACK] Order Successfully Processed. Order REF#'.$order_code.". \r\n");

                
                $Order->status = 'completed';
                wp_set_object_terms( $Order->id, array('completed'), 'shop_order_status', false );


            } else {
                $note = '[POS FEEDBACK] Order could NOT be processed!. Error code: '.$order_code.". \r\n";
                $Order->add_order_note($note);

               
                $Order->status = 'on-hold';

                wp_set_object_terms( $Order->id, array('on-hold'), 'shop_order_status', false );
                $this->sendOrderSubmitFailureEmail( $Order->id, $note );  // send notification email
            }
        }

        //Manually push to Eastcoast

        function add_order_meta_box_actions( $actions ) {
            $actions['keeki_push_to_eastcoast'] = 'Push to Eastcoast';
            return $actions;
        }

        // run the code that should execute with this action is triggered
        function process_order_meta_box_actions( $order ) {
           /* $terms_array = wp_get_object_terms($order->id, 'shop_order_status');

            echo $terms_array;
            echo "here";
            die();
            $status = $terms_array[0]->name;
            if($status != 'completed') $this->addOrderHook( $order->id );
            */

            $status = $order->status;
            if($status != 'completed')
                $this->addOrderHook( $order->id );
        }

        /**
         * Send email with Order submit failure information
         *
         * @param $order_id
         * @param $error_message
         */
        private function sendOrderSubmitFailureEmail($order_id, $error_message) {
            $to = defined('ORDER_ERROR_EMAIL_TO') ? ORDER_ERROR_EMAIL_TO : 'sunil.verma@webdesignmarket.com.au';
            $subject = defined('ORDER_ERROR_EMAIL_SUBJECT') ? ORDER_ERROR_EMAIL_SUBJECT : 'Keeki - Order Submit Failure';
            $url = KEEKI_API_URL;
            $message = "URL: $url\n";
            $message .= "Order # $order_id has failed to submit to Eastcoast. Please review the following error message and resubmit manually.\n\n";
            $message .= "Error message:\n$error_message";
            wp_mail( $to, $subject, $message );
        }

    }

endif;