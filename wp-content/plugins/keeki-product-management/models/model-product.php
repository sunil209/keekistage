<?php
namespace KPM;

require_once('model.php');
require_once KPM_DIR.'components/table-request-helper.php';

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\KPM\ProductModel' )):

    /**
     * Keeki Product Model,
     * Interface with API class and store product objects
     *
     *
     * @class Product
     * @version	0.1
     * @since 0.1
     * @package	KPM
     * @author Conduct
     */
    class ProductModel extends Model {

        //Populate with categories actually assigned to products
        public $used_webgroups = array();

        //Store the Titles used, so we don't get duplicates
        public $used_titles = array();

        /**
         * Use the API to get product data
         * Where appropriate update the db product data
         *
         * @param Array $categories list of categories to use in save
         */
        public function updateProducts($categories = array()) {
            // option to limit the Sync time by only downloading a subset of data
            $debug = defined('API_DEBUG_ENABLED') ? API_DEBUG_ENABLED : false;
            $debug_limit = defined('API_DEBUG_LIMIT') ? API_DEBUG_LIMIT : 300;
            $debug_offset = defined('API_DEBUG_OFFSET') ? API_DEBUG_OFFSET : 0;

            //reset
            $this->used_webgroups = array();
            $this->used_titles = array();    
            $added_posts = array();        
            $productImageData = array();
            $productImageDataByStockcode = array();            
            
            error_log('START RETRIEVING GALLERY IMAGES');
            //Grab all the products images, but DEAD products
            list($table,$fields,$conditions,$sortBy,$limit,$offset) = $this->_additionalProductImages();
            $field_batch = array(
                'field' =>  $sortBy,
                'value' => '000'
            );

            $count = 0;
            do{                
                if ($debug) {
                    $productImageData =  RequestHelper::standardRequest($table,$fields,$conditions,$sortBy,$debug_limit,$debug_offset, $field_batch);
                } else {

                    $productImageData =  RequestHelper::batchRequest($table,$fields,$conditions,$sortBy,true, $offset, $count, $field_batch);
                }
                $items_retrieved = count($productImageData[$table]);
            
                
                foreach ($productImageData[$table] as $key=>$images)
                {

                    //remove empty elements from real array...
                    $productImageData[$table][$key] = array_filter($productImageData[$table][$key]);
                    //...and from temporary one.
                    $images = array_filter($images);

                    foreach ($images as $row=>$url)
                    {    
                    //error_log($row);
                    //error_log($url);

                        if ($row != 'STOCKCODE' && $url != '' && !$this->imageIsNewOrModified($url, $images['STOCKCODE'])){
                        //if ($row != 'STOCKCODE' && $url != ''){
                            unset ($productImageData[$table][$key][$row]);
                        }
                    }
                    //if there is only one element left on the array and the key of it is STOCKCODE then the product doesn't have images and we delete it.
                    reset($productImageData[$table][$key]);
                    if (count($productImageData[$table][$key]) == 1 && key($productImageData[$table][$key]) == 'STOCKCODE') unset ($productImageData[$table][$key]);                                    	
                }
                
                $productImageDataByStockcode = $productImageDataByStockcode + $this->_organiseProductImagesByStockcode($productImageData[$table]);
                $count++;
            } while($items_retrieved >= API_RECORD_LIMIT);

            unset($productImageData);
            

            //print_r($productImageDataByStockcode);
            
            error_log('END RETRIEVING GALLERY IMAGES');

            error_log('START UPDATING PRODUCTS');
            //Grab all the products, but DEAD products
            list($table,$fields,$conditions,$sortBy,$limit,$offset) = $this->_standardProductParams();
            $field_batch = array(
                'field' =>  $sortBy,
                'value' => '000'
            );

            $count = 0;
            do{
                if ($debug) {
                    $product_data =  RequestHelper::standardRequest($table,$fields,$conditions,$sortBy,$debug_limit,$debug_offset);
                } else {
                    $product_data =  RequestHelper::batchRequest($table,$fields,$conditions,$sortBy,true, $offset, $count, $field_batch);                  
                }
                
                if(!empty($product_data[$table])) {
                    foreach($product_data[$table] as $product) {
                        $this->_addProductData($product,$categories,$productImageDataByStockcode[$product['STOCKCODE']],$added_posts);
                    }
                }
                $count++;
            } while(count($product_data[$table]) >= API_RECORD_LIMIT);
            unset ($product_data);

            error_log('END UPDATING PRODUCTS');

            error_log('START REMOVING OLD PRODUCTS');
            //Grab all DEAD products that have stock still
            list($table,$fields,$conditions,$sortBy,$limit,$offset) = $this->_leftoverProductParams();
            $field_batch = array(
                'field' =>  $sortBy,
                'value' => '000'
            );
            $count = 0; 
            do{
                if ($debug) {
                    $product_data =  RequestHelper::standardRequest($table,$fields,$conditions,$sortBy,$debug_limit,$debug_offset);
                } else {
                    $product_data =  RequestHelper::batchRequest($table,$fields,$conditions,$sortBy,true, $offset, $count, $field_batch);
                }

                if(!empty($product_data[$table])) {
                    foreach($product_data[$table] as $product) {
                        if (!$this->isDead($product)){ //yes, we are just getting from the API the already dead products but they won't dead on the site if they still have stock.
                            $this->_addProductData($product,$categories,$productImageDataByStockcode[$product['STOCKCODE']], $added_posts);
                        }
                    }
                }
                $count++;
            } while(count($product_data[$table]) >= API_RECORD_LIMIT);
            unset ($product_data);


            error_log('DELETEE OLD PRODUCTS');
            try{
                $this->_removeOldProducts($added_posts);
             } catch (Exception $e) {
                error_log("Remove old products failed");
            }


            error_log('END REMOVING OLD PRODUCTS');
            return true;
        }





        /**
         * Build a product query API request, set the required fields conditions and sort by
         *
         * @return array
         */
        private function _standardProductParams( ) {
            $table = 'STOCK';
            $limit = '50';
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
                // ,
                // array(
                //     'field' => 'STOCKCODE',
                //     'comparison' => '==',
                //     'value' => 'WRPTBW'
                // )
            );

            return array($table,$fields,$conditions,$sortBy,$limit,$offset);
        }

        /**
         * Build a specific product query API request, set the required fields conditions and sort by
         *
         * @return array
         */
        private function _leftoverProductParams() {
            //Get defaults
            list($table,$fields,$conditions,$sortBy,$limit,$offset) = $this->_standardProductParams();
            //Override specifics
            $limit = '100';
            $qohLimit=0;

            // Removed $qohLimit condition on call due to creating too much overhead and failure during api call
            /*if(defined('DISCONTINUED_STOCK_THRESHOLD') && DISCONTINUED_STOCK_THRESHOLD) {
            $qohLimit = DISCONTINUED_STOCK_THRESHOLD - 1;
            }*/


            $conditions = array(
                array(
                    'field' => 'STKIND1',
                    'comparison' => '==',
                    'value' => 'Y'
                ),
                array(
                    'field' => 'QOH1',
                    'comparison' => '>',
                    'value' => $qohLimit
                ),
                array(
                    'field' => 'DEAD',
                    'comparison' => '==',
                    'value' => 'Y'
                )
                 ,
                // array(
                //     'field' => 'STOCKCODE',
                //     'comparison' => '==',
                //     'value' => 'WRPTBW'
                // )
            );
            return array($table,$fields,$conditions,$sortBy,$limit,$offset);
        }

        /**
         * Organise array of product images arrays by stockcode
         * array('STOCKCODE'=>array('LINKIMG1'=>'k:/...'))
         *
         * @return array
         */
        private function _organiseProductImagesByStockcode($productImagesArray=array()) {
            //print_r($productImagesArray);
            if (empty($productImagesArray))
                return array();
            $organisedProductImages=array();
            foreach ($productImagesArray as $productImages) {
                $images = array();
                for ($i=1; $i<=10; $i++) {
                    $images['LINKIMG'.$i]=$productImages['LINKIMG'.$i];
                }
                $organisedProductImages[$productImages['STOCKCODE']] = $images;
            }
            return $organisedProductImages;
        }

        /**
         * Build the images query API request,
         * set the required fields conditions and sort by
         *
         * @return array
         */
        private function _additionalProductImages( ) {
            $table = 'DRMERC';
            $limit = '150';
            $sortBy = 'STOCKCODE';
            $offset = 1;

            $fields = array(

            );
            for($i = 1; $i <= 10; $i++) {
                $fields[] = 'LINKIMG'.$i;
            }

            $conditions = array(
                array(
                    'field' => 'LINKIMG1',
                    'comparison' => '!=',
                    'value' => ''
                )
            );
            return array($table,$fields,$conditions,$sortBy,$limit,$offset);
        }


        /**
         * Update WC custom product post type
         * @see woocommerce/admin/product/post-types/product.php for basis of this code
         *
         *
         * @param Array $data the data to update or insert the product with
         * @param Array $categories a list of categories to use when saving products
         * @param Array $added_posts an array to stack with every updated or added post id
         */
        private function _addProductData($data,$categories,$productImages, &$added_posts) {
            global $woocommerce;
            //get the admin user (use first found).
            error_log($data['STOCKCODE']);
               
            $SalePrice = '';
            if(isset($data['GOVRETINC']))
              {
                 $sp = $data['GOVRETINC'];
                 $SalePrice = (float)$sp;
                 if($SalePrice>0){$SalePrice = $SalePrice;}else{$SalePrice = '';}  
               }
             else
                {
                    $SalePrice = '';
                }

            
            $args = array(
                'role'         => 'Administrator'
            );

            $users = get_users($args);
            if(empty($users)) {
                return false;
            }


            if (!$this->isNewOrUpdated($data, $productImages) && !$this->imageIsNewOrModified($data['FIELD1'])) {
            //if (!$this->isNewOrUpdated($data, $productImages)) {
                //If product wasn't updated and it's not new and it does not have any new image in it we look for it on the DB
                $post_id = $this->_getBySku($data['STOCKCODE']);

                if (!empty($post_id)) {
                    $added_posts[] = $post_id; //we added to this variable so the product is not deleted at the end of the process.

                    //we have to add the product category again because the old one was deleted.
                    $index_prefix = 'WEBGROUP';
                    $taxonomy = 'product_cat';
                    if(!empty($categories)) {
                        //Clear any existing
                        $term_ids = array();
                        wp_set_object_terms( $post_id, null, $taxonomy);

                        $i = 1;
                        $index = $index_prefix.$i;
                        while(!empty($data[$index])) {
                            if(isset($categories[$data[$index]])) {
                                $term_ids[] = $categories[$data[$index]];
                                $this->used_webgroups[$data[$index]] = $categories[$data[$index]];
                            }
                            $i++;
                            $index = $index_prefix.$i;
                        }
                        if(!empty($term_ids)) {
                            wp_set_object_terms( $post_id, $term_ids, $taxonomy);
                        }
                    }
                    //Add tags
                    $taxonomy = 'product_tag';
                    if(!empty($data['WEBINFO'])) {
                        $tags = explode(',',$data['WEBINFO']);
                        if(!empty($tags)) {
                            wp_set_object_terms( $post_id, $tags, $taxonomy);
                        }
                    }
                    else
                     {
                        $tags = array();
                        wp_set_object_terms( $post_id, $tags, $taxonomy);
                     } 
                    return false;
                }
                //If we continue from here it's because the product is supposed to don't be new or updated but
                //for some reason we couldn't find it on the DB so we just add it. i.e.: Maybe a previous sync failed?
            }                        



            $desc = isset($data['DESC']) ? trim($data['DESC']) : '';
            $webdesc = isset($data['WEBDESC']) ? trim($data['WEBDESC']) : '';
            $qdesc = isset($data['QDESC']) ? trim($data['QDESC']) : '';



            // Attempt to read the <body> tag from the description, since it may have HTML in it
            $description = (!empty($data['WEBDESC']) ? $webdesc : $qdesc); // prefer WEBDESC over QDESC

            //echo $description;

            require_once("simple_html_dom.php");
            $doc = new \simple_html_dom();
            $doc->load($description, true, false);
            foreach ($doc->find("body") as $key) {
                    $body = $key->innertext;
            }


            //echo 'Body-'.$body;



            $title_text = !empty($body) ? $body : $description;

            // Replace BR tags with a special character, temporarily replace them.
            // if there are BR tags, take everything up to the first BR and remove new lines
            // otherwise, simply use up to the first NL and apply minimum length fallbacks if required
            $break_identifer = "|||";
            $title_text = str_replace("<br />", $break_identifer, $title_text);
            $title_text = str_replace("<br/>", $break_identifer, $title_text);
            $title_text = str_replace("<br >", $break_identifer, $title_text);
            $title_text = str_replace("<br>", $break_identifer, $title_text);
            // check if any BRs replaced
            $break_pos = strrpos($title_text, $break_identifer);
            if ($break_pos === FALSE) {
                // no BR tag found, take the first line
                $title = explode("\n", $title_text);
                $post_title = trim($title[0]);
            } else {
                // if there are BR tags, take everything up to the first BR and remove new lines
                $space = ' ';
                $dbl_space = '  ';
                // keep correct word separation while removing the NL
                $title_text = str_replace("\n", $space, $title_text);        // replace new lines with spaces
                // replace double spaces with spaces, incase there was a space at the end of previous line
                $title_text = str_replace($dbl_space, $space, $title_text);  
                // take everything up to the original BR tag
                $title = explode($break_identifer, $title_text);
                $post_title = trim($title[0]);
            }


            // check if this has fallen below the minimum length; i.e. they had the brand on a separate line of HTML and we used that
            $title_minimum = defined('PRODUCT_TITLE_EXPECTED_MINIMUM_LENGTH') ? PRODUCT_TITLE_EXPECTED_MINIMUM_LENGTH : 10;
            if (strlen($post_title) < $title_minimum) {

                if (!empty($qdesc)) {
                    $post_title = $desc;    // fall back to DESC field if it's not empty
                } elseif (!empty($desc)) {
                    $post_title = $qdesc;    // fall back to QDESC field if it's not empty
                } else {
                    $post_title = $title_text;  // fall back to the WEBDESC (body contents if possible)
                    // since WEBDESC will have the full contents, apply a limit on the length used for the title
                    $title_maximum = defined('PRODUCT_TITLE_EXPECTED_MAXIMUM_WEBDESC_LENGTH') ? PRODUCT_TITLE_EXPECTED_MAXIMUM_WEBDESC_LENGTH : 30;
                    if (strlen($post_title) > $title_maximum) {
                        substr($post_title, 0, $title_maximum);
                    }
                }
            }



        /*                
                if($desc != '' || !empty($desc))
                {
                  $post_title  = $desc;
                }
                else if($qdesc != '' || !empty($qdesc)) 
                {
                   $post_title = $qdesc;
                }
                else
                {
                    $post_title = $post_title;
                    $title_maximum = defined('PRODUCT_TITLE_EXPECTED_MAXIMUM_WEBDESC_LENGTH') ? PRODUCT_TITLE_EXPECTED_MAXIMUM_WEBDESC_LENGTH : 30;
                    if (strlen($post_title) > $title_maximum) {
                        substr($post_title, 0, $title_maximum);
                    }
                }
             
            */   
                $post_title =  $this->_ensureUniqueTitle($post_title);


                /* 
                $doc2 = new \simple_html_dom();
                $doc2->load($webdesc, true, false);
                foreach ($doc2->find("body") as $key) {
                        $webdesc = strip_tags($key->innertext);
                }
                $webdesc = strip_tags($webdesc);
                */


                $post_args = array(
                    'post_title'  => $post_title,
                    'post_status' => 'publish',
                    'post_type'   => 'product',
                    'post_content' 	=> (!empty($webdesc) ? $webdesc : (!empty($qdesc) ? $qdesc : '')),
                    'post_author' => $users[0]->ID
                );


            //if not targetted at existing WP post then see if SKU is already entered
            $post_id = $this->_getBySku($data['STOCKCODE']);


           /*
            echo $data['STOCKCODE'];
            if($data['STOCKCODE'] == 'MONMTQCS4'){
                wp_update_post($post_args);

                print_r($post_args);
            }

            exit();
            die();
            */
           


            if (isset($data['DEAD']) && $data['DEAD']=='Y' && empty($post_id)) {
                $post_args['post_date'] = date('Y-m-d H:i:s', strtotime("-90 days"));
            }

            if(!empty($post_id)) {                
                //Only necessary when a change in above args code,
                $post_args['ID'] = $post_id;
                wp_update_post($post_args);
            } else {
                //If still no post id, must be new create post.
                $post_id = wp_insert_post($post_args);                
            }     

            $added_posts[] = $post_id; 
            
            // Save fields
            if ( isset( $data['STOCKCODE'] ) ) update_post_meta( $post_id, '_sku', wc_clean( $data['STOCKCODE'] ) );
            if ( isset( $data['WEIGHT'] ) ) update_post_meta( $post_id, '_weight', wc_clean( $data['WEIGHT'] ) );
            if ( isset( $data['DEPTH'] ) ) update_post_meta( $post_id, '_length', wc_clean( $data['DEPTH'] ) );
            if ( isset( $data['WIDTH'] ) ) update_post_meta( $post_id, '_width', wc_clean( $data['WIDTH'] ) );
            if ( isset( $data['HEIGHT'] ) ) update_post_meta( $post_id, '_height', wc_clean( $data['HEIGHT'] ) );
            if ( isset( $data['_stock_status'] ) ) update_post_meta( $post_id, '_stock_status', wc_clean( $data['_stock_status'] ) );
            if ( isset( $data['_visibility'] ) ) update_post_meta( $post_id, '_visibility', wc_clean( $data['_visibility'] ) );
            if ( isset( $data['_featured'] ) ) update_post_meta( $post_id, '_featured', 'yes' ); else update_post_meta( $post_id, '_featured', 'no' );

            if ( isset( $data['RECRETINC'] ) ) update_post_meta( $post_id, '_regular_price', wc_clean( $data['RECRETINC'] ) );
     // if ( isset( $data['_sale_price'] ) ) update_post_meta( $post_id, '_sale_price', woocommerce_clean( $data['_sale_price'] ) );
            
            if ( isset( $data['GOVRETINC'] ) ) update_post_meta( $post_id, '_sale_price', wc_clean( $SalePrice ) );

            // Store API processing information
            update_post_meta( $post_id, '_api_processed_at', wc_clean( date('Y-m-d H:i:s') ) );
            update_post_meta( $post_id, '_api_DESC', $desc );
            update_post_meta( $post_id, '_api_WEBDESC', $webdesc );
            update_post_meta( $post_id, '_api_QDESC', $qdesc );
            update_post_meta( $post_id, '_api_description_body', $body );
            update_post_meta( $post_id, '_api_title_text', $title_text );
            update_post_meta( $post_id, '_api_post_title', $post_title );

            //Normally this would only happen on price change, but we won't to avoid looking up each product
            update_post_meta( $post_id, '_sale_price_dates_from', '' );
            update_post_meta( $post_id, '_sale_price_dates_to', '' );
            
            
            if ( isset( $data['GOVRETINC'] ) && $SalePrice != '') 
            {
                 update_post_meta( $post_id, '_price', wc_clean($SalePrice) );
             } 
            else
            {
                 update_post_meta( $post_id, '_price', wc_clean( $data['RECRETINC'] ) );
            }
            
            
         /*   if ( isset( $data['_sale_price'] ) && $data['_sale_price'] != '' ) {
                update_post_meta( $post_id, '_price', wc_clean( $data['_sale_price'] ) );
            } else {
                update_post_meta( $post_id, '_price', wc_clean( $data['RECRETINC'] ) );
            }
            */

            // Handle stock
            update_post_meta( $post_id, '_stock', (isset($data['QOH1']) ? (int) $data['QOH1'] : 0));
            update_post_meta( $post_id, '_visibility','visible');
            //Custom extensions/
            if(class_exists( 'SOD_WooCommerce_Pinterest_Button' )) {
                if(defined('PRODUCT_SOD_PININTEREST') && PRODUCT_SOD_PININTEREST) {
                    update_post_meta( $post_id, 'sod_pinterest','yes');
                }
            }
            if(defined('PRODUCT_DISABLE_STOCK') && PRODUCT_DISABLE_STOCK) {
                update_post_meta( $post_id, '_manage_stock', 'no' );
            } else {
                update_post_meta( $post_id, '_manage_stock', 'yes' );
            }

            error_log('1 - Image');
            //Add images
            if(empty($data['FIELD1'])) {
                delete_post_thumbnail($post_id);
            } else {                
                     try{                             
                        $attachment_id = $this->addThumbnailAttachment($post_id,$data['FIELD1'],'product');
                        error_log('thumbnail: '.$attachment_id);
                        if ($attachment_id != NULL) {                    
                            set_post_thumbnail( $post_id, $attachment_id );
                        }   
                        } catch (Exception $e) {                                 
                            error_log("Error updating product Image");
                        }
            }

            /** Add categories and tags **/

            //Add categories
            //If we have webgroup lookup stored in the category model use it
            $index_prefix = 'WEBGROUP';
            $taxonomy = 'product_cat';
            if(!empty($categories)) {
                //Clear any existing
                $term_ids = array();
                wp_set_object_terms( $post_id, null, $taxonomy);

                $i = 1;
                $index = $index_prefix.$i;
                while(!empty($data[$index])) {
                    if(isset($categories[$data[$index]])) {
                        $term_ids[] = $categories[$data[$index]];
                        $this->used_webgroups[$data[$index]] = $categories[$data[$index]];
                    }
                    $i++;
                    $index = $index_prefix.$i;
                }
                if(!empty($term_ids)) {
                    wp_set_object_terms( $post_id, $term_ids, $taxonomy);
                }
            }
            //Add tags
            $taxonomy = 'product_tag';
            if(!empty($data['WEBINFO'])) {
                $tags = explode(',',$data['WEBINFO']);
                if(!empty($tags)) {
                    wp_set_object_terms( $post_id, $tags, $taxonomy);
                }
            }
          else
             {
                $tags = array();
                wp_set_object_terms( $post_id, $tags, $taxonomy);
             } 

            // Clear transient
            //$woocommerce->clear_product_transients( $post_id );


            error_log('2 - Image Galleries');
            if ( isset( $data['STOCKCODE']) ) 
            {

                //if (isset($productImages[$data['STOCKCODE']]) && !empty($productImages[$data['STOCKCODE']])) {
                if (isset($productImages) && !empty($productImages)) 
                    {
                        $imageIDCSV='';
                            
                        //foreach ($productImages[$data['STOCKCODE']] as $key=>$image) {
                        foreach ($productImages as $key=>$image) 
                            {
                                 error_log("image: ".$image);
                                if ($image == '')
                                continue;
                                        $attachment_id = $this->addThumbnailAttachment($post_id,$image,'product');
                                        error_log("attachment: ".$attachment_id);
                                        if ($attachment_id !='') 
                                            {
                                                $imageIDCSV .= $attachment_id.',';
                                            }
                            }
                        error_log("Set update Gallery");
                        $imageIDCSV = rtrim($imageIDCSV,',');
                        update_post_meta( $post_id, '_product_image_gallery', $imageIDCSV );
                    } 
                    else 
                    {
                           error_log("Set Emtpy Gallery_1");
                         // set empty gallery
                             try{
                                update_post_meta( $post_id, '_product_image_gallery', '' );
                            } catch (Exception $e) {
                             error_log("Empty Product Images");
                             }
                     }
                } 
            else 
                {
                    // set empty gallery
                    error_log("Set Emtpy Gallery_2");
                     try{
                    update_post_meta( $post_id, '_product_image_gallery', '' );
                     } catch (Exception $e) {
                        error_log("Empty Product Images");
                    }
                }

                error_log("Return True");

                
            return true;
        }







        /**
         * Get product ID of product by SKU, can be used to check if product of SKU exists
         *
         * @param $sku
         * @return mixed
         */
        private function _getBySku($sku) {
            global $wpdb;
            $product_id = $wpdb->get_var(
                $wpdb->prepare('SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key="_sku" AND meta_value="%s" LIMIT 1', $sku )
            );
            return $product_id;
        }


        /**
         * Remove all posts not in an array of updated or added post items
         *
         * @param $added_posts
         */
        private function _removeOldProducts($added_posts) {
         $args = array(
                'posts_per_page'   => -1,
                'offset'           => 0,
                'exclude'          => $added_posts,
                'post_type'        => 'product',
                'fields'         => 'ids'
            );

            $posts = get_posts($args);

             error_log(print_r($posts));

            foreach($posts as $post) {
                error_log("Deleting".$post);
                wp_delete_post($post);
            }
        }


        /**
         * Ensure the Title is unique by comparing against previously used values. 
         * Will add a suffix if all of the possible entries are taken
         *
         * @param $post_title 
         * @param $duplicate_suffix optional 
         * @return string $post_title
         */
        private function _ensureUniqueTitle($post_title) {            

            $suffix = 1;           

            while(isset($this->used_titles[$post_title])){
                $post_title = $post_title . " - " . $suffix;
                $suffix++;
            }
            
            return $post_title;

        }

        private function isDead($product){
            if (isset($product['DEAD']) && $product['DEAD']=='Y') {
                //disable discontinued stock that has less than 4 items available
                $qoh = isset($product['QOH1']) ? (int) $product['QOH1'] : 0;
                $allocated = isset($product['QTYALLOC']) ? (int) $product['QTYALLOC'] : 0;
                $damaged = isset($product['ENGREPBUY']) ? (int) $product['ENGREPBUY'] : 0;
                $unassembled = isset($product['ENGREPBUYI']) ? (int) $product['ENGREPBUYI'] : 0;

                $onhand = $qoh - ($allocated + $damaged + $unassembled);

                if(defined('DISCONTINUED_STOCK_THRESHOLD') && DISCONTINUED_STOCK_THRESHOLD) {
                    if ($onhand < DISCONTINUED_STOCK_THRESHOLD) {
                        return true;
                    }
                } else {
                    if ($onhand <= 0) {
                        return true;
                    }
                }
            }

            return false;
        }

        private function isNewOrUpdated($product, $productImages){
            $mdate = date_parse(str_replace('/', '-', $product['LASTPRICE']));
            if ($mdate != FALSE && $mdate['error_count'] == 0){                
                $modified_date = new \DateTime($mdate['year'] . "-" . $mdate['month'] . "-" . $mdate['day']);                                
                $today = new \DateTime(date('d-m-Y'));         

                //if product details haven't been updated and there is not new or modified images we exit.
                if ((date_diff($modified_date, $today)->days > SYNC_PRODUCTS_YOUNGER_THAN_DAYS) && empty($productImages)){                         
                    return false;
                }
            }

            return true;
        }


        public function Updatecopuons()
                {

                        global $wpdb;
                        $type = 'shop_coupon';
                        $args=array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => -1);
                        $my_query = null;
                        $my_query = get_posts($args);
                        $taxonomy = 'product_cat';
                        if(!empty($my_query))
                            {
                                foreach ($my_query as $coupon) {
                                        $couponId =  $coupon->ID;
                                        $coupon_p_category = get_post_meta( $couponId, 'product_categories_slug', true);
                                        $coupon_p_category_exlude = get_post_meta( $couponId, 'exclude_product_categories_slug', true);
                                        $p_category_ids = array();
                                        $p_e_category_ids = array();
                                        if(!empty($coupon_p_category))
                                            {
                                                foreach ($coupon_p_category as $key => $slug) {
                                                    $term = get_term_by('slug', $slug, $taxonomy);
                                                    $p_category_ids[] = $term->term_id;
                                                }
                                            }
                                        if(!empty($coupon_p_category_exlude))
                                            {
                                                foreach ($coupon_p_category_exlude as $key => $slug) {
                                                    $term = get_term_by('slug', $slug, $taxonomy);
                                                    $p_e_category_ids[] = $term->term_id;
                                                }
                                            } 
                                        update_post_meta( $couponId, 'product_categories', array_filter( array_map( 'intval', $p_category_ids)));
                                        update_post_meta( $couponId, 'exclude_product_categories', array_filter( array_map( 'intval', $p_e_category_ids)));   
                                }
                            }




                            /* Update Title either accedenitallly deleted or not  */

                             $table_name = $wpdb->prefix. "yoastseo_custom";
                             $fetchResult = $wpdb->get_results( "SELECT * FROM $table_name" );
                             if($fetchResult && !(empty($fetchResult)))
                                 {
                                 
                                    foreach($fetchResult as $val)
                                        {
                                             $sku_value = $val->yoastseo_sku_id;
                                             $meta_title = $val->yoastseo_meta_title;
                                             $meta_description = $val->yoastseo_meta_descriptions;
                                        
                                             // step 1 Get current product id form $sku_value
                                             // step 2 Next update meta tile/ description of current product id
                                        
                                              $product_id = wc_get_product_id_by_sku($sku_value); 
                                              if( get_post_meta( $product_id, '_yoast_wpseo_metadesc', true ) ) {
                                                     update_post_meta( $product_id, '_yoast_wpseo_metadesc', $meta_description);   
                                              }else{
                                                    add_post_meta( $product_id, '_yoast_wpseo_metadesc', $meta_description);   
                                              }

                                             if( get_post_meta( $product_id, '_yoast_wpseo_title', true ) ) {
                                                     update_post_meta( $product_id, '_yoast_wpseo_title', $meta_title);   
                                              }else{
                                                    add_post_meta( $product_id, '_yoast_wpseo_title', $meta_title);   
                                              }                   
                                        }

                                 }



                                 /* online exclusive update */



                             $table_name = $wpdb->prefix. "onlineexclusive_custom";
                             $fetchResult = $wpdb->get_results( "SELECT * FROM $table_name" );
                             if($fetchResult && !(empty($fetchResult)))
                                 {
                                 
                                    foreach($fetchResult as $val)
                                        {
                                              $sku_value = $val->onlineexlusive_uniq_id_sku_id;
                                              $ifexclusive = $val->onlineexlusive_if;
                                              $product_id = wc_get_product_id_by_sku($sku_value); 
                                              if( get_post_meta( $product_id, '_onlineExlusivecheckbox', true ) ) {
                                                     update_post_meta( $product_id, '_onlineExlusivecheckbox', $ifexclusive);   
                                              }else{
                                                     add_post_meta( $product_id, '_onlineExlusivecheckbox', $ifexclusive);   
                                              }
                      
                                        }

                                 }

                            
                            return true;
                }

        }

endif;
