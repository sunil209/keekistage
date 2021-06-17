<?php
namespace KPM;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\KPM\Model' )):

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    /**
     * Model framework for keeki product models
     * Enforces singleton behaviour
     */
    abstract class Model {

        /**
         * Hide magic constructor functions, force singleton
         */
        protected function __construct( ) {}
        protected function __clone( ) {}
        protected static $instance = null;

        /**
         * Get single instance
         * @return static
         */
        public static function getInstance() {
            if(self::$instance == null) {
                return new static;
            }
            return self::$instance;
        }

        /**
         * Convenience method for adding thumbnail data attributes,
         * Note: attachment must still be attached to the object (post or term) by caller.
         * This will not re-add images of existing filenames, new filename must be provided to re-upload as an optimisation
         *
         * Upload copy of image from url
         * Save to wp-uploads and attach to post
         *
         * @param Int $entity_id the id of the POST or Term
         * @param String $image_url
         *
         */
        protected function addThumbnailAttachment($entity_id,$image_url,$type = 'product') {
            global $wpdb;
            list($filename,$folder) = $this->_breakUpFilename($image_url);
            $file = $this->getAttachmentFilepath($image_url,$type);
            //Treat filenames as unique, only update where not found
            $attach_id = $wpdb->get_var(
                'SELECT ID FROM '.$wpdb->posts.'
                 WHERE guid = "'.$file.'"
                 AND post_type="attachment" LIMIT 1');

            $image_data = @file_get_contents(KEEKI_IMAGE_SOURCE_URL.'/'.str_replace(' ','%20',$folder).'/'.str_replace(' ','%20',$filename));
            if(!$image_data) {
                return false;
            }
            file_put_contents($file, $image_data);
        

            if(empty($attach_id)) {
                $wp_filetype = wp_check_filetype($filename, null );
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => sanitize_file_name($filename),
                    'post_content' => '',
                    'post_status' => 'inherit',
                    'guid' => $file
                );
                $attach_id = wp_insert_attachment( $attachment, $file, $entity_id );
                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
                wp_update_attachment_metadata( $attach_id, $attach_data );
            } else {
                    $metadata = wp_generate_attachment_metadata( $attach_id, $file );
                    wp_update_attachment_metadata( $attach_id, $metadata );
            }

            return $attach_id;
        }

        protected function imageIsNewOrModified($image_url, $stockcode = null) {


            // global $wpdb;
            // list($filename,$folder) = $this->_breakUpFilename($image_url);

            // $url = KEEKI_IMAGE_SOURCE_URL.'/'.str_replace(' ','%20',$folder).'/'.str_replace(' ','%20',$filename);

            // $h = get_headers($url, 1);

            // $dt = NULL;
            // if ($h && strstr($h[0], '200') == TRUE) {   
                
            //     try {
            //         date_default_timezone_set('Australia/ACT');
            //         $dt = new \DateTime($h['Last-Modified']);

            //         $today = new \DateTime(date('d-m-Y'));
            //         $datediff = date_diff($dt, $today);
                    
            //         //we only update the files modified in the last three days.
            //         if ($datediff->days >= 0 && $datediff->days <= SYNC_PRODUCTS_YOUNGER_THAN_DAYS){
            //             return true;
            //         } else {
            //             return false;
            //         }
            //     }
            //     catch (Exception $e){
            //         error_log($e->getMessage());
            //     }

            // }
            // else{
            //     //error_log("Error getting image on product STOCKCODE: " . $stockcode . " - " . $url. ' Response: ' . $h[0]);
            // }
            
            // //if we can't get the headers we don't update it.
            // return false;

            return true;
        }

        /*
         * Arbitrary function to take the local system url as returned by Options Online system and return a WP appropraite
         * media resource url
         *
         * @param String $filename the filename of the image
         */
        protected function getAttachmentFilepath($image_url,$type = 'product') {
            list($filename,$folder) = $this->_breakUpFilename($image_url);

            $dest_folder = ($type == 'category' ? 'categories' : 'products');

            $upload_dir = wp_upload_dir();
            $product_image_dir = $upload_dir['basedir'].'/'.$dest_folder;

            if(wp_mkdir_p($product_image_dir ))
                $file = $product_image_dir  . '/' . $filename;
            else
                $file = $upload_dir['basedir'] . '/' . $filename;

            return $file;
        }


        /**
         * Convience function to break up provided url into its useful components.
         * @param string $image_url
         * @return array
         */
        private function _breakUpFilename($image_url) {
            /*
            Example url replace pattern
            K:\Website\Large Image\1212RS.JPG
            API (Local):
            http://192.168.0.3/Pictures/Large%20Image/1212RS.JPG
            API (External):
            http://119.225.58.46:8080/Pictures/Large%20Image/1212RS.JPG
             */
            $url_pieces = explode('\\',$image_url);
            $drive = array_shift($url_pieces);
            $drive_folder =  array_shift($url_pieces);
            $filename = array_pop($url_pieces);
            
            $folder_struct = implode('/',$url_pieces);
            return array($filename,$folder_struct);
        }

       
    }

endif;
