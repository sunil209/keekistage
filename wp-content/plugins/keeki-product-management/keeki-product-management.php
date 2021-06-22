<?php

namespace KPM;

/**
 * Plugin Name: Keeki Product Management
 * Description: Middleware between Keeki POS Options Online account systems and woocommerce e-commerce plugin
 * Version: 0.1
 * Author: Conduct
 * Author URI: http://conducthq.com
 * Requires at least: 3.5
 * Tested up to: 3.5
 *
 * Text Domain: Keeki
 * Domain Path: /i18n/languages/
 *
 * @package KPM
 * @category Core
 * @author Conduct
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\KPM\main' ) ) :

    /**
     * Main Keeki Product Management Class
     *
     *
     * @class KPM
     * @version	0.1
     * @since 0.1
     * @package	KPM
     * @author Conduct
     */
    class Main {

        /**
         *  Setup
         */
        public function __construct() {
            //If no woocommerce can't proceed.
            $plugins = get_option( 'active_plugins' ) ? get_option( 'active_plugins' ) : array();
            if (function_exists('apply_filters') && !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', $plugins) ) ) {
                if(!function_exists('deactivate_plugins')) {
                    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                }
                deactivate_plugins('keeki-product-management/keeki-product-management.php');
                return false;
            }
            //Load required files
            $this->_getRequired();
            //Setup init hook
            add_action('init',array($this,'init'));

            //Always add hook but cron should be only caller
            add_action('kpm_update_products',array($this,'updateProductSet'));


            //Setup
            $orders= new Orders();
        }

        /**
         * On wp initialise
         */
        public function init() {}

        /**
         * Get required files
         */
        private function _getRequired() {
            define('KPM_DIR',plugin_dir_path(__FILE__));
            require_once('config.php');
            require_once('classes/class-api.php');
            require_once('classes/class-product.php');
            require_once('classes/class-order.php'); 
            require_once('woocommerce-overrides.php');
        }

        /**
         * Action reference, wait for update product call from cron
         */
        public function updateProductSet() {
            
            //Always call, doesn't always update
         
                    $xml ='<request clientKey="KeekiAPIPassword123498765"><table name="STOCK" sortBy="STOCKCODE" maxRecords="150" FirstRecord="1" ><fields><field>LASTPRICE</field><field>STOCKCODE</field><field>DESC</field><field>QDESC</field><field>WEBDESC</field><field>WEBINFO</field><field>RECRETEX</field><field>RECRETINC</field><field>WIDTH</field><field>DEPTH</field><field>HEIGHT</field><field>FIELD1</field><field>SMALPIC</field><field>STKIND1</field><field>WEIGHT</field><field>QOH1</field><field>QTYALLOC</field></fields><conditions><condition field="STKIND1" type="equals">Y</condition><condition field="DEAD" type="notEqual">Y</condition><condition field="STOCKCODE" type="greaterThan">000</condition></conditions></table></request>';
                       $url = KEEKI_API_URL;
                       $post_xml = '';

                        if(empty($this->ch)) {
                            $this->createConnection();
                        }
                        if(strpos($xml,'<?xml') !== 0) {
                            $header = '<?xml version="1.0" encoding="utf-8" ?>';
                            $post_xml = $header.$xml;
                        } else {
                            $post_xml = $xml;
                        }

                    $post_xml = $this->xmlEntities($post_xml);

                   // echo( htmlentities( $post_xml) );
                   // print_r($file);
                   // exit();
                    
                    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_xml);

                    if(strpos($url,'https') === 0) {
                        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
                    }
                    curl_setopt($this->ch, CURLOPT_URL,$url);
                    if(defined('KEEKI_API_PORT')) {
                        curl_setopt($this->ch,CURLOPT_PORT,KEEKI_API_PORT);
                    }
                    curl_setopt($this->ch,CURLOPT_FAILONERROR,true);
                    curl_setopt ($this->ch, CURLOPT_HTTPHEADER,array(
                            "POST HTTP/1.1",
                            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                            "Cache-Control: no-cache",
                            "Pragma: no-cache",
                            "Content-length: ".strlen($post_xml),
                            "Content-Type: text/html; charset=utf-8",
                            "Connection: Keep-Alive")
                    );

                    $data = curl_exec($this->ch);  
                    $httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);  
                    if ( $httpCode != 200 ){
                            echo "Return code is {$httpCode} \n" .curl_error($this->ch);
                            exit();
                        } else {
  
                                    $children_webgroup_data = array();
                                    list($table,$fields,$conditions,$sortBy,$limit,$offset) = $this->_webgroupsParamsCat();
                                    $field_batch = array(
                                        'field' =>  $sortBy,
                                        'value' => '000'
                                    );            

                                    $children_webgroup_data_temp = RequestHelper::batchRequest($table,$fields,$conditions,$sortBy,true, $offset, $count, $field_batch);
                                    $children_webgroup_data = \array_merge($children_webgroup_data, $children_webgroup_data_temp);
                                    list($table,$fields,$conditions,$sortBy,$limit,$offset) = $this->_parentwebgroupsParamsCat();

                                    $parent_webgroup_data_temp = RequestHelper::batchRequest($table,$fields,$conditions,$sortBy,false, $offset, $count);
                                    $parent_webgroup_data = \array_merge($parent_webgroup_data, $parent_webgroup_data_temp);

                                    $webgroup_data[$table] = \array_merge($parent_webgroup_data[$table], $children_webgroup_data[$table]);


                                    $debug = defined('API_DEBUG_ENABLED') ? API_DEBUG_ENABLED : false;

                                    list($table,$fields,$conditions,$sortBy,$limit,$offset) = $this->_standardProductParamsProducts();
                                    $field_batch = array(
                                        'field' =>  $sortBy,
                                        'value' => '000'
                                    );

                                    if ($debug) {
                                            $product_data =  RequestHelper::standardRequest($table,$fields,$conditions,$sortBy,$debug_limit,$debug_offset);
                                        } else {
                                            $product_data =  RequestHelper::batchRequest($table,$fields,$conditions,$sortBy,true, $offset, $count, $field_batch);                  
                                        }


                                        if(!empty($product_data[$table]))
                                            {

                                               $msg = "Eastcoast API Process Started";
                                               $notifyAlert = sendErrorhandlingAlrtEmail($msg);
                                               $products = new Products();
                                               $products->updateProductSet();

                                               
                                            }
                                            else{

                                                $msg = "Eastcoast API Process Not Started";
                                                $notifyAlert = sendErrorhandlingAlrtEmail($msg);
                                                echo "Invalid Response from server no products & categories founc.";
                                                exit();
                                            }


                        }
        }






         public function createConnection() {
                $this->ch = curl_init();
                curl_setopt($this->ch, CURLOPT_POST, 1);
                curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($this->ch, CURLOPT_TIMEOUT, 180);
            }


            private function xmlEntities($string) {
                $translationTable = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
                foreach ($translationTable as $char => $entity) {
                    $from[] = $entity;
                    $to[] = '&#'.ord($char).';';
                }
                return str_replace($from, $to, $string);
            }



                private function _webgroupsParamsCat( ) {
                    $table = 'DRWGRP';
                    $limit = '50';
                    $sortBy = 'PARGROUP1';
                    $offset = 1;

                    $fields = array(
                        'WEBGROUP',
                        'DESC',
                        'PICFILE',
                        'WGRPFLAG1'
                    );
                    for($i = 1; $i <= 10; $i++) {
                        $fields[] = 'PARGROUP'.$i;
                    }

                    $conditions = array(
                        array(
                            'field' => 'PARGROUP1',
                            'comparison' => '!=',
                            'value' => ''
                        ),
                        array(
                            'field' => 'WGRPFLAG1',
                            'comparison' => '!=',
                            'value' => 'Y'
                        )
                    );
                    return array($table,$fields,$conditions,$sortBy,$limit,$offset);
                }



                private function sendErrorhandlingAlrtEmail($error){

                    $subject = 'Eastcoast Alert Sync (Check 1)';
                    $to = 'sunil.verma@webdesignmarket.com.au';
                   
                    // To send HTML mail, the Content-type header must be set
                    $headers[] = 'MIME-Version: 1.0';
                    $headers[] = 'Content-type: text/html; charset=iso-8859-1';

                    // Additional headers
                    $headers[] = 'To: Sunil <sunil.verma@webdesignmarket.com.au>';
                    $headers[] = 'From: Error Alert <online@keeki.com.au>';
                    $headers[] = 'Cc: yuvraj@webdesignmarket.com.au';

                    // Mail it
                    mail($to, $subject, $error, implode("\r\n", $headers));
                    //mail($to, $subject, $error);
                return true;
            }

                    private function _parentwebgroupsParamsCat() {
                        //Get defaults
                        $table = 'DRWGRP';
                        $limit = '50';
                        $sortBy = 'PARGROUP1';
                        $offset = 1;

                        $fields = array(
                            'WEBGROUP',
                            'DESC',
                            'PICFILE',
                            'WGRPFLAG1'
                        );
                        for($i = 1; $i <= 10; $i++) {
                            $fields[] = 'PARGROUP'.$i;
                        }
                        $conditions = array(
                            array(
                                'field' => 'PARGROUP1',
                                'comparison' => 'equals',
                                'value' => ''
                            ),
                            array(
                                'field' => 'WGRPFLAG1',
                                'comparison' => '!=',
                                'value' => 'Y'
                            )
                        );
                        return array($table,$fields,$conditions,$sortBy,$limit,$offset);
                    }




                 private function _standardProductParamsProducts( ) {
                        $table = 'STOCK';
                        $limit = '500';
                        $sortBy = 'STOCKCODE';
                        $offset = 1;
                        $fields = array(
                            'LASTPRICE',
                            'STOCKCODE',
                            'DESC',
                            'QDESC',
                            'WEBDESC',
                            'WEBINFO',
                            'RECRETEX',
                            'RECRETINC',
                            'GOVRETEX',
                            'GOVRETINC',
                            'WIDTH',
                            'DEPTH',
                            'HEIGHT',
                            'FIELD1',
                            'SMALPIC',
                            'STKIND1',
                            'WEIGHT',
                            'QOH1',
                            'QTYALLOC',
                            'ENGREPBUY',
                            'ENGREPBUYI',
                            'DEAD'
                        );
                        for($i = 1; $i <= 15; $i++) {
                            $fields[] = 'WEBGROUP'.$i;
                        }

                        $conditions = array(
                            array(
                                'field' => 'STKIND1',
                                'comparison' => '==',
                                'value' => 'Y'
                            ),
                            array(
                                'field' => 'DEAD',
                                'comparison' => '!=',
                                'value' => 'Y'
                            )
                        );

                        return array($table,$fields,$conditions,$sortBy,$limit,$offset);
                    }




    }

    new \KPM\main();

endif;
