<?php
namespace KPM;

require_once('model.php');

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\KPM\OrderModel' )):


    /**
     * Keeki Order Model,
     * Interface with API class and POST orders stored from WC
     *
     *
     * @class Order
     * @version	0.1
     * @since 0.1
     * @package	KPM
     * @author Conduct
     */
    class OrderModel extends Model {

        /**
         * Use the API to get product data
         * Where appropriate update the db product data
         *
         * @param Array $categories list of categories to use in save
         */
        public function placeOrder($meta,$customer,$payment,$products,$billingAddress,$deliveryAddress) {

            $xmlObj = $this->_getXmlObj($meta,$customer,$payment,$products,$billingAddress,$deliveryAddress);

           // echo $xmlObj->asXML();

           // die();

            error_log("<<<< ORDER XML DATA >>>>>");
            error_log($xmlObj->asXML());
            error_log("<<<< END ORDER XML DATA >>>>>");
            $order_response = API::getInstance()->xmlRequest($xmlObj->asXML());

            if(empty($order_response['success'])) {
                return array(false,empty($order_response['error']) ? 'Unknown error with POS callback' : $order_response['error']);
            }
            return array(true,$order_response['success']);
        }

        /**
         * Get the allowable attributes of an order query API request
         *
         * @return array
         */
        private function _orderParams( ) {
            $table = 'order';

            $meta = array(
                'accountCode',
                'requiredBy',
                'orderType',
                'customerReference',
                'gstMode',
                'priceCategory',
                'branch',
                'notes'
            );
            $customer = array(
                'title',
                'firstName',
                'lastName',
                'email',
                'phone',
                'mobile',
                'fax'
            );

            $billingAddress = array(
                'line1',
                'line2',
                'line3',
                'suburb',
                'state',
                'postCode'
            );
            $deliveryAddress = array(
                'line1',
                'line2',
                'line3',
                'suburb',
                'state',
                'postCode'
            );
            $payment = array(
                'date',
                'total',
                'cardType',
                'reference'
            );

            $product = array(
                'code',
                'description',
                'qty',
                'price',
                'notes'
                //'options'
            );

            return array($table,$meta,$customer,$payment,$product,$billingAddress,$deliveryAddress);
        }


        /**
         * Stack variables from a series of arrays onto a single simple xml object
         * Will create correct arbitrary nested structure for xml object.
         *
         *
         * @param $obj
         * @param $params
         */
        private function _getXmlObj($meta,$customer,$payment,$products,$billingAddress,$deliveryAddress) {

            //Defaults
            $meta['priceCategory'] = empty($meta['priceCategory']) ? 'R' : $meta['priceCategory'];
            $meta['gstMode'] = empty($meta['gstMode']) ? 'IN' : $meta['gstMode'];
            $meta['orderType'] = empty($meta['orderType']) ? 'ORDER' : $meta['orderType'];
            $meta['branch'] = empty($meta['branch']) ? '1' : $meta['branch'];

            //get data structures/valid fields
            list($table,$metaStrut,$customerStrut,$paymentStrut,$productStrut,$billingAddressStrut,$deliveryAddressStrut) = $this->_orderParams();

            //Create xml object, and attach variables and values
            $xmlRoot = new \SimpleXMLElement('<'.$table.'></'.$table.'>');
            $xmlRoot->addAttribute('clientKey',KEEKI_CLIENTKEY);
            $headerObj = $xmlRoot->addChild('header');
                $this->_stackVar($headerObj,$metaStrut,$meta);

                $contactObj = $headerObj->addChild('contact');
                $this->_stackVar($contactObj,$customerStrut,$customer);

                $billingObj = $headerObj->addChild('billingAddress');
                $this->_stackVar($billingObj,$billingAddressStrut,$billingAddress);

                $deliveryObj = $headerObj->addChild('deliveryAddress');
                $this->_stackVar($deliveryObj,$deliveryAddressStrut,$deliveryAddress);

                $paymentObj = $headerObj->addChild('payment');
                $this->_stackVar($paymentObj,$paymentStrut,$payment);

            $productsObj = $xmlRoot->addChild('products');
            foreach($products as $product) {
                $productObj = $productsObj->addChild('product');
                $this->_stackVar($productObj,$productStrut,$product);
            }
            return $xmlRoot;
        }


        /**
         * Helper function to add variables to xml object
         * Will only add values in associative array that match indexes from array strut
         *
         * @param \SimpleXMLElement $obj pass by reference
         * @param Array $strut
         * @param Array $values
         */
        private function _stackVar(\SimpleXMLElement &$obj,$strut,$values) {
            foreach($strut as $fieldIdx) {
                if(!empty($values[$fieldIdx])) {
                    $obj->addChild($fieldIdx,$values[$fieldIdx]);
                } else {
                    $obj->addChild($fieldIdx);
                }
            }
        }
    }

endif;

