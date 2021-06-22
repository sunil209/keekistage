<?php
if ( !defined( 'ABSPATH' ) ) exit;
if ( !class_exists( 'TilPointsDB' ) ) :

    Class KeekiShippingDB {

        public function __construct() {
            //nothing
        }

        public function installPostcodeData() {
            global $wpdb;

            $table_name = $wpdb->prefix . "keeki_postcodes";
            $sql = "CREATE TABLE $table_name (
                postcode varchar(9) NOT NULL,
                locality VARCHAR(55) DEFAULT '',
                state VARCHAR(3) DEFAULT '',
                parcelzone varchar(3) DEFAULT '',
                bspname VARCHAR(20) DEFAULT '',
                UNIQUE KEY postcode (postcode, locality)
            );";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }

        public function installShippingPriceData() {
            global $wpdb;

            $table_name = $wpdb->prefix . "keeki_shipping_prices";
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                state VARCHAR(3) DEFAULT '',
                price_min decimal(7,2) NOT NULL,
                price_max decimal(7,2) NOT NULL,
                price_val decimal(7 ,2) NOT NULL,
                UNIQUE KEY id (id),
                UNIQUE KEY state_min_max (state,price_min,price_max)
            );";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

        }

        public function addData($table='', $data=array()) {
            global $wpdb;
            if ($table=='')
                return false;
            $table_name = $wpdb->prefix . $table;
            //get data from csv;

            if (!empty($data)) {
                foreach ($data as $key=>$values) {
                    $rows_affected = $wpdb->insert( $table_name, $values );
                }
            }
        }

        function constructPriceTable($filename='', $delimiter=','){
            if(!file_exists($filename) || !is_readable($filename))
                return FALSE;

            $data = array();
            if (($handle = fopen($filename, 'r')) !== FALSE)
            {
                while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
                {
                    $data[] = $row;
                }

                fclose($handle);
            }

            $headers = array(
                0=>'state',
                1=>'price_min',
                2=>'price_max',
                3=>'price_val',
            );

            $header = array_shift($data);

            foreach ($header as $colKey=>$col) {
                //echo $col;
                $cols = explode('-',$col);
                //print_r($cols);

                foreach ($data as $rowKey=>$row) {
                    if (!isset($cols[1]))
                        continue;
                    $price['state'] = $row[0];
                    $price['price_min'] = str_replace('$', '', $cols[0]);
                    $price['price_max'] = str_replace('$', '', $cols[1]);
                    $price['price_val'] = str_replace('$', '', $data[$rowKey][$colKey]);

                    $prices[] = $price;
                }
            }

            return $prices;
        }

        function csvToArray($filename='', $delimiter=','){
            if(!file_exists($filename) || !is_readable($filename))
                return FALSE;

            $header = NULL;
            $data = array();
            if (($handle = fopen($filename, 'r')) !== FALSE)
            {

                while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
                {
                    if(!$header)
                        $header = $row;
                    else
                        $data[] = array_combine($header, $row);
                }
                fclose($handle);
            }
            return $data;
        }


    }
endif;