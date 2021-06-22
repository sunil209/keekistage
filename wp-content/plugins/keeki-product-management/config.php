<?php
namespace KPM;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


define('KEEKI_API_URL','http://remote.keeki.com.au:8080/OptionsAPI/Options.API');


define('KEEKI_CLIENTKEY','KeekiAPIPassword123498765');
define('KEEKI_IMAGE_SOURCE_URL','http://remote.keeki.com.au:8080/Pictures');

//Product Customisations
define('PRODUCT_SOD_PININTEREST',true);
define('PRODUCT_DISABLE_STOCK',true);
define('DISCONTINUED_STOCK_THRESHOLD',4);
// Product Title
DEFINE('PRODUCT_TITLE_EXPECTED_MINIMUM_LENGTH', 10);
DEFINE('PRODUCT_TITLE_EXPECTED_MAXIMUM_WEBDESC_LENGTH', 30);
// Sync debug options
define('API_DEBUG_ENABLED', false);
define('API_DEBUG_LIMIT', 500);
define('API_DEBUG_OFFSET', 0);
// error email when API receives error
//define('API_ERROR_EMAIL_TO', 'dev-alerts@astasolutions.com.au');
define('API_ERROR_EMAIL_TO', 'sunil.verma@webdesignmarket.com.au');
define('API_ERROR_EMAIL_SUBJECT', 'Keeki - API Failure - Development');
// error email when posting an order to EC
//define('ORDER_ERROR_EMAIL_TO', 'dev-alerts@astasolutions.com.au');
define('ORDER_ERROR_EMAIL_TO', 'sunil.verma@webdesignmarket.com.au');
define('ORDER_ERROR_EMAIL_SUBJECT', 'Keeki - Order Submit Failure - Development');

define('SYNC_PRODUCTS_YOUNGER_THAN_DAYS', 50);
define('API_RECORD_LIMIT', 150);