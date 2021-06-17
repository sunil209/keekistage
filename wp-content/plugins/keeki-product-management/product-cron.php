<?php
/**
 * Daily cron job, to be accessed directly, either change dirname(__FILE__) location or place in wp root
 */
ignore_user_abort(true);

if ( !empty($_POST) || defined('DOING_AJAX') || defined('DOING_CRON') )
    die();

//Basic check to stop stumbling over in address bar
//if(empty($argv[1]) || $argv[1] != 'run_update_cron') {
//    die();
//}

//Basic check to stop stumbling over in address bar
if(!empty($argv[2]) && $argv[2] == 'full') {
    define('COMPREHENSIVE_UPDATE', true);
} else {
    define('COMPREHENSIVE_UPDATE', false);
}

/**
 * Tell KPM we are doing the CRON task.
 *
 * @var bool
 */
define('DOING_CUSTOM_CRON', true);

ini_set('memory_limit','512M');
//set_time_limit (760);
ini_set('MAX_EXECUTION_TIME', -1);
set_time_limit (0);

if ( !defined('ABSPATH') ) {
    /** Set up WordPress environment */
    $file_dir = dirname(__FILE__);

//error_log('MARKER 1');
//error_log($file_dir.'/../../../wp-load.php');

    require_once($file_dir.'/../../../wp-load.php');

//error_log('MARKER2');

}

do_action('kpm_update_products');

delete_option('product_cat_children');

// error_log('Done');

$msg = "Eastcoast API Process End";
$notifyAlert = sendErrorhandlingAlrtEmail($msg);


                function sendErrorhandlingAlrtEmail($error){

                    $subject = 'Cludways: Eastcoast Sync Alert';
                    $to = 'sunil.verma@webdesignmarket.com.au';
                    $tocc = 'yuvraj@webdesignmarket.com.au';

                    // To send HTML mail, the Content-type header must be set
                    $headers[] = 'MIME-Version: 1.0';
                    $headers[] = 'Content-type: text/html; charset=iso-8859-1';

                    // Additional headers
                    $headers[] = 'To: Sunil <sunil.verma@webdesignmarket.com.au>';
                    $headers[] = 'From: Sync Alert <online@keeki.com.au>';
                    $headers[] = 'Cc: yuvraj@webdesignmarket.com.au';

                    // Mail it
                    mail($to, $subject, $error, implode("\r\n", $headers));
                    //mail($to, $subject, $error);
                return true;
            }


exit('DONE');
?>
