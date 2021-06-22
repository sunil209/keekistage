<?php
namespace KPM;

//error_log(KPM_DIR.'models/model-category.php');
//error_log($KPM_DIR.'models/model-category.php');

require_once KPM_DIR.'models/model-category.php';
require_once KPM_DIR.'models/model-product.php';


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\KPM\Products' ) ) :

    /**
     * Main Keeki Product Management Class
     *
     *
     * @class Products
     * @version	0.1
     * @since 0.1
     * @package	KPM
     * @author Conduct
     */
    class Products {
        /**
         * Private, wp_options referrence string
         * @var string
         */
        private $_option_last_update = 'KPM_products_last_update';

        /**
         * @var Object product model
         */
        protected $productModel = null;
        protected $categoryModel = null;

        //Override standard magic clone and constructor to ensure single instance exists
        public function __construct() {
            $this->productModel = ProductModel::getInstance();
            $this->categoryModel = CategoryModel::getInstance();
        }

        /**
         * If required get latest product data from
         */
        public function updateProductSet() {
            if (!defined('DOING_CUSTOM_CRON')) { return false; }            

            error_log('START SYNC');
            echo 'START SYNC';

            //Add the product categories
            echo "Srating Cateringy updates";
            error_log('START CATEGORY UPDATES');
            $start = microtime(true);
           // $this->categoryModel->updateCategories();
            $end = microtime(true);
            error_log('Time taken to update categories:'.($end - $start));            
            error_log('END CATEGORY UPDATES');  
            echo "End Cateringy updates";

            //Update the products and set their possibly new product categories
            echo "Start products";
            error_log('START PRODUCTS');
            $start = microtime(true);
            $this->productModel->updateProducts($this->categoryModel->webgroup_ids);
            $end = microtime(true);
            error_log('Time taken to update products:'.($end - $start));
            error_log('END PRODUCTS');
            echo "End of products";

            //Check which categories have been used by products and save as menu items
            echo "START MENU REBUILD";
            error_log('START MENU REBUILD');
            $start = microtime(true);
          //  $this->categoryModel->postProductUpdateCategories($this->productModel->used_webgroups);
            $end = microtime(true);            
            error_log('Time taken to update menu:'.($end - $start));
            error_log('END MENU REBUILD');
            echo "End MENU REBUILD"; 

             // Coupons updated categories 
            //Update the coupons and set their possibly new categories
            error_log('START PRODUCTS');
            $start = microtime(true);
          //  $this->productModel->Updatecopuons();
            $end = microtime(true);
            error_log('Time taken to update products:'.($end - $start));
            error_log('END PRODUCTS');
            // End of coupons updated 

          //  $this->_updateComplete();
            error_log('END SYNC'); 

            echo 'END SYNC';
            return false;
        }


        public function updateSinglProductSet()
        {


        }

        /**
         * Check option field for last update.
         * If more than 1 hour ago then return true otherwise false
         * @return bool
         */
        private function _requiresUpdate() {
            $sUpdated = get_option($this->_option_last_update);
            if(empty($sUpdated)) { return true; }

            $iExpiry = strtotime('-1 minutes');
            $iUpdated = strtotime($sUpdated);
            if($iUpdated < $iExpiry) {
                return true;
            }
            return false;
        }

        /**
         * Set the updated time in the options table
         */
        private function _updateComplete() {
             $last_updated = get_option($this->_option_last_update);
            if(!empty($last_updated)) {
                delete_option($this->_option_last_update);
            }
            $current_time = date('Y-m-d H:i:s',strtotime('now'));
            add_option($this->_option_last_update,$current_time); 
        }

    }

endif;
