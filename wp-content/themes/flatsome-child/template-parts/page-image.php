<?php
/*
Template name: Image Upload
*/

//get_header(); ?>

<?php 

   



function get_real_filename($headers,$url){
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


function SaveFile($imageurl){
    
        $location = ABSPATH.'temp/';
        $site_url = site_url();
    
        $fileextension = image_type_to_extension( exif_imagetype( $imageurl ) );
        $ext  = pathinfo( $imageurl, PATHINFO_EXTENSION );
        $name = pathinfo( $imageurl, PATHINFO_FILENAME )  . $fileextension;
    
        $myfile = file_get_contents($imageurl);    
       // $filename = 'temp_'.get_real_filename($http_response_header,$imageurl);
    
        $filename = get_real_filename($http_response_header,$imageurl);
    
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
    
        $getURL = upload_image_from_url($ImgURL);
        unlink($file_location);
    
        return $getURL;
}








function upload_image_from_url( $imageurl ){
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
                $filename = get_real_filename($http_response_header,$imageurl);

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

    
                return $attachmentId.'hhh';
} 





$uploadImage = SaveFile('https://5627755.app.netsuite.com/core/media/media.nl?id=28536&c=5627755&h=3c31c201f588519e4888');
echo $uploadImage;



















function uploadRemoteImageAndAttach($image_url, $parent_id){

        $image = $image_url;
        $get = wp_remote_get( $image );
        $type = wp_remote_retrieve_header( $get, 'content-type' );
        if (!$type)
            return false;

        $mirror = wp_upload_bits( basename( $image ), '', wp_remote_retrieve_body( $get ) );






        $attachment = array(
            'post_title'=> basename( $image ),
            'post_mime_type' => $type
        );

        $attach_id = wp_insert_attachment( $attachment, $mirror['file'], $parent_id );

        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attach_data = wp_generate_attachment_metadata( $attach_id, $mirror['file'] );

        wp_update_attachment_metadata( $attach_id, $attach_data );

        return $attach_id;

    }


function _uploadImageToMediaLibrary($url, $alt = "blabla") {

    
     require_once(ABSPATH . 'wp-load.php');
     require_once(ABSPATH . 'wp-admin/includes/image.php');
     require_once(ABSPATH . 'wp-admin/includes/file.php');
     require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    
    

    $tmp = download_url( $url );
    
    echo "<pre>";
    print_r($tmp);
     echo "</pre>";
    
    
    
    $desc = $alt;
    $file_array = array();
    
    
    
    
    die();

    // Set variables for storage
    // fix file filename for query strings
    preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
    $file_array['name'] = basename($matches[0]);
    $file_array['tmp_name'] = $tmp;

    // If error storing temporarily, unlink
    if ( is_wp_error( $tmp ) ) {
        @unlink($file_array['tmp_name']);
        $file_array['tmp_name'] = '';
    }

    // do the validation and storage stuff
    $id = media_handle_sideload( $file_array);

    // If error storing permanently, unlink
    if ( is_wp_error($id) ) {
        @unlink($file_array['tmp_name']);
        return $id;
    }

    return $id;
}



?>


<?php //get_footer(); ?>
