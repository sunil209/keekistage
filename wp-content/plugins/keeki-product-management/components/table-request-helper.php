<?php
namespace KPM;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( '\KPM\BasicRequestHelper' )):

/**
 * Helper for making Basic Table Requests for stock and webgroups
 * Not always able to be used but provides structure for xml requests where follow arbitrary OptionsOnline request pattern
 */

Class RequestHelper {

    /**
     * Batch request to get all of table $table with sanity check
     *
     * @param $table
     * @param $fields
     * @param null $conditions
     * @param string $sortBy
     * @param bool $toFieldBatch allow field batching, use only when there is unique sortBy Field
     */

    public static function batchRequest($table,$fields,$conditions = null,$sortBy = '',$toFieldBatch = false, &$offset, &$count, &$field_batch = null) {
        $API = API::getInstance();

        $response_data = array(
            $table => array()
        );

        if ($toFieldBatch == false){
            $field_batch = null;
        }
        
        $limit = API_RECORD_LIMIT;        

            try {
                $xml = self::buildStandardRequestXml($table,$fields,$conditions,$sortBy,$limit,$offset,$field_batch);
                self::logXmlToFile($table,$xml,true); // log request
                //echo $xml;
                $return_data = $API->postRequest($xml,true);
            } catch(APIException $e) {
                error_log($e->getMessage());                
            }
            $return_array = self::standardRequestXmlToArray($return_data);
            self::logXmlToFile($table,$API->unpackXml($return_array),false); // log response
            //If offset is field then update the comparison value for loop
            if(!empty($field_batch)) {
                if(isset($return_array[$table])) {
                    $result_set = $return_array[$table];
                    $last_row = array_pop($result_set);
                    $field_batch['value'] = $last_row[$field_batch['field']];
                }
            }
            $response_data[$table] = array_merge($response_data[$table],(isset($return_array[$table]) ? $return_array[$table] : array()));
            $offset += $limit;
            //Sanity check
            if($offset > 25000) { //break;
                // do nothing
                 exit();
             }
            if(($count % 15) === 0) {
                //Ever 10 requests or 1500 items reset connection
                error_log('Wait');
                $API->closeConnection();
                usleep(250);
            }
            usleep(75);
            error_log('REQUEST '.$offset.', max '.$limit.' returned '.count($response_data[$table]).' for table '.$table);            

        //We kept it open should close to free up resources
        $API->closeConnection();
        return $response_data;
    }

    /**
     *
     * Make a request to the server
     * Build arbitrary predefined XML structure from parameters passed in
     *
     * @param String $table the table to request
     * @param Array $fields indexed array of field names to request
     * @param Array $conditions optional, conditions to match for return
     * @param String $sortBy optional the name of a field to sort results by
     * @param Int $limit optional numeric limit to number of results to be returned
     * @param Int $offset offset
     */
    public static function standardRequest($table,$fields,$conditions = null,$sortBy = '',$limit = 50,$offset = 10) {
        $xml = self::buildStandardRequestXml($table,$fields,$conditions,$sortBy,$limit,$offset);
        self::logXmlToFile($table,$xml,true); // log request

        $ret = API::getInstance()->postRequest($xml);
        
        $arr = self::standardRequestXmlToArray($ret);
        self::logXmlToFile($table,API::getInstance()->unpackXml($arr),false); // log response
        return $arr;
    }

    /*
     * Build arbitrary predefined XML structure from parameters passed in
     *
     * @param String $table the table to request
     * @param Array $fields indexed array of field names to request
     * @param Array $conditions optional, conditions to match for return
     * @param String $sortBy optional the name of a field to sort results by
     * @param Int $limit optional numeric limit to number of results to be returned
     * @param Int $offset offset
     * @param Array $field_batch contains 'field' and 'value', where sort by present can use this to batch loop through requests instead of inefficent offset
     */
    private static function buildStandardRequestXml($table,$fields,$conditions = array(),$sortBy = '',$limit = 50,$offset = 1,$field_batch = null) {
        $clientKey = KEEKI_CLIENTKEY;

        $xml = '
        <request clientKey="'.$clientKey.'">';

        if(empty($field_batch)) {
            $xml .= '
                <table name="'.strtoupper($table).'" sortBy="'.(!empty($sortBy) ? strtoupper($sortBy) : '').'" maxRecords="'.$limit.'" FirstRecord="'.$offset.'" >
                    <fields>';
        } else {
            $xml .= '
                <table name="'.strtoupper($table).'" sortBy="'.(!empty($sortBy) ? strtoupper($sortBy) : '').'" maxRecords="'.$limit.'" FirstRecord="1" >
                    <fields>';
        }


        foreach($fields as $field) {
            $xml .= '
                    <field>'.strtoupper($field).'</field>';
        }
        $xml .= '
                </fields>';

        if(!empty($conditions) || !empty($field_batch)) {
            $xml .= '
                <conditions>';
            //Un pack conditions including convert logic comparison to string description of comparotor
            foreach($conditions as $condition) {
                if(empty($condition['field']) || !isset($condition['value'])) { continue; }
                if(empty($condition['comparison'])) {
                    $type = 'equalTo';
                } else {
                    switch($condition['comparison']) {
                        case '<':
                            $type = 'lessThan';
                            break;
                        case '>':
                            $type = 'greaterThan';
                            break;
                        case '<=':
                            $type = 'lessOrEqualTo';
                            break;
                        case '>=':
                            $type = 'greaterOrEqualTo';
                            break;
                        case '!=':
                            $type = 'notEqual';
                            break;
                        case '=':
                        case '==':
                        default:
                            $type = 'equals';
                            break;
                    }
                }
                $xml .= '
                                <condition field="'.strtoupper($condition['field']).'" type="'.$type.'">'.strtoupper($condition['value']).'</condition>';
            }
            //TODO FIX OR DISABLE FOR NON-UNIQUE FIELDS
            if(!empty($field_batch) && !empty($field_batch['value'])) {
                $xml .= '
                                <condition field="'.$field_batch['field'].'" type="greaterThan">'.$field_batch['value'].'</condition>';
            }

            $xml .= '
                </conditions>';
        }//end if conditions
        $xml .= '
            </table>
        </request>';

        return $xml;
    }

    /**
     * Custom unpack the XML response specific for an Options online XML format
     * Example:
     * <table name="STOCK">
     *       <records count="1">
     *           <record item="2">
     *               <field name="STOCKCODE">001-032.16</field>
     *               <field name="BARCODE">0007651</field>
     *          </record>
     *           <record item="2">
     *               <field name="STOCKCODE">001-032.16</field>
     *               <field name="BARCODE">0007651</field>
     *          </record>
     *      </records>
     * </table>
     *
     *
     * @param $xml_return SimpleXMLElement Object, the root object of an Optional Online API return
     * @return Array clean associative array of results
     */
    private function standardRequestXmlToArray($xml_return) {
        $API = API::getInstance();

        if(!isset($xml_return->table)) { return array(); }

        $tablename = $API->getXmlAttribute($xml_return->table,'name');

        $return_array = array(
            $tablename => array()
        );

        if(!isset($xml_return->table->records)) { return $return_array; }

        foreach($xml_return->table->records->record as $record) {
            $return_item = array();
            foreach($record->field as $field) {
                $return_item[$API->getXmlAttribute($field,'name')] = (string)$field;
            }
            $return_array[$tablename][] = $return_item;
        }
        return $return_array;
    }

    /**
     * Log the specified XML to a file
     *
     * @param $table String name of table being queried. Used for file naming
     * @param $xml String XML data to be logged
     * @param $input Boolean request vs response data
     */
    private function logXmlToFile($table, $xml, $request = true) {
        $timestamp = date('YmdHis',strtotime('now'));
        $date = date('Ymd',strtotime('now'));
        $file_dir = dirname(__FILE__);
        $request_type = $request ? 'request' : 'response';
        $file_name = "api-log-$table-$request_type-$timestamp.xml";
        $file_path = "$file_dir/log/$date/$file_name";
        // create directories if required
        if(!file_exists(dirname($file_path)))
            mkdir(dirname($file_path), 0777, true);
        // store
        file_put_contents($file_path, $xml, FILE_APPEND);
    }
}
endif;

?>