<?php
namespace KPM;

/**
 *
 * Handles connection to Options Online POS accounts services API
 *
 *
 * @class API
 * @version	0.1
 * @since 0.1
 * @package	KPM
 * @author Conduct
 */
Class API {

    //Curl Connection object
    protected $ch = null;

    protected static $instance = null;

    //Override standard magic clone and constructor to ensure single instance exists
    protected function __construct() {}
    protected function __clone() { }

    /**
     * Accessible singleton method
     * get single instance of api
     *
     * @return API
     */
    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * Generic post data request, using raw XML don't build
     *
     * @param $xml
     * @param null $conditions
     * @param string $sortBy
     * @param bool $fieldBatch allow field batching, use only when there is unique sortBy Field
     */

    public function xmlRequest($xml) {
        try {
            $return_data = $this->postRequest($xml);
        } catch(APIException $e) {
            error_log($e->getMessage());
            return array();
        }
        return $this->xmlToArray($return_data);
    }


    /**
     * Convert SimpleXMLElement into an asociative array
     *
     * @param \SimpleXMLElement $xml
     */
    public function xmlToArray(\SimpleXMLElement $xml) {
        return json_decode(json_encode($xml), TRUE);
    }


    /**
     *
     * Take in an associatve array and return nested xml string
     * @param Array $arr associative array to unpack
     */
    public function unpackXml($arr, $level = 0) {
        $xml_str = '';
        foreach($arr as $key => $value) {
            $val = (is_array($value)) ? self::unpackXml($value, $level + 1) : $value;

            $indent = '';
            for ($i = 0; $i < $level; $i++) {
                $indent .= ' ';
            }

            $xml_str .= $indent . '<'.strtoupper($key).'>'.$val.'</'.strtoupper($key).'>'."\n";
        }
        return $xml_str;
    }

    /**
     * Extract from a SimpleXml object an attribute
     *
     * @param $field SimpleXMLElement Object
     * @param $attribute_name String index of attribute to retrieve
     */
    public function getXml($field,$attribute_name) {
        foreach($field->attributes() as $name => $value) {
            if($name == $attribute_name) {
                return (string)$value;
            }
        }
        return '';
    }

    /**
     * Extract from a SimpleXml object an attribute
     *
     * @param $field \SimpleXMLElement Object
     * @param $attribute_name String index of attribute to retrieve
     */
    public function getXmlAttribute(\SimpleXMLElement $field,$attribute_name) {
        foreach($field->attributes() as $name => $value) {
            if($name == $attribute_name) {
                return (string)$value;
            }
        }
        return '';
    }


    /**
     * Close the curl connection
     */
    public function closeConnection() {
        if(gettype($this->ch) == 'resource') {
            curl_close($this->ch);
        }
        $this->ch = null;
    }

    /**
     *
     * Make a request to the server
     * @param String $url destination url
     * @param String $xml the xml to post without header.
     * @param Bool $retry optional used for recursive calls, allow 1 retry	
     * @param bool $rawdata optional if we can jsut get the raw xml sent by the server. Normally used for testing using the ApiBridge.
     */
    public function postRequest($xml,$keepopen = false,$retry = false, $rawdata = FALSE) {
        $url = KEEKI_API_URL;

        if(empty($this->ch)) {
            $this->createConnection();
        }
        if(strpos($xml,'<?xml') !== 0) {
            $header = '<?xml version="1.0" encoding="utf-8" ?>';
            $post_xml = $header.$xml;
        } else {
            $post_xml = $xml;
        }

        // replace named HTML entities
        $post_xml = $this->xmlEntities($post_xml);

        //echo $post_xml;
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_xml);

        if(strpos($url,'https') === 0) {
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
        }
        curl_setopt($this->ch, CURLOPT_URL,$url);
        if(defined('KEEKI_API_PORT')) {
            curl_setopt($this->ch,CURLOPT_PORT,KEEKI_API_PORT);
        }
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


        if(curl_errno($this->ch))
        {
            error_log('CURL ERROR: '.curl_error($this->ch));
            curl_close($this->ch);
            throw new APIException('Could not connect, to update products.');
        }
        if(!$keepopen) {
            $this->closeConnection();
        }

        if (!$rawdata) {
            libxml_use_internal_errors(true);
            $result = simplexml_load_string($data);
            if($result === false) {
                $err_str = '';
                foreach(libxml_get_errors() as $error) {
                    $err_str .= $error->message;
                }
                //error_log('XML ERROR: '.$err_str);
                //error_log('XML PROVIDED: '.$data);
                $this->sendDebugEmail($post_xml,$data);
                //Allow 1 retry, as invalid XML is likely webservice markup of temporary error. TODO check headers?
                if(!$retry) {
                    usleep(500);
                    $result = $this->postRequest($xml,$keepopen,true);		
                } else {
                    throw new APIException('Product return invalid.');
                }
            } else {
                //error_log('XML SUCCESS: '.$post_xml);
            }
            return $result;
        } else {
            //used to create a bridge script on testing.
            return $data;
        }
    }

    /**
     * Open up a CURL connection
     */
    private function createConnection() {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 180);
    }

    /**
     * Send debug email with request/post data
     */
    private function sendDebugEmail($request_xml, $response_xml) {
        $to = defined('API_ERROR_EMAIL_TO') ? API_ERROR_EMAIL_TO : 'sunil.verma@webdesignmarket.com.au';
        $subject = defined('API_ERROR_EMAIL_SUBJECT') ? API_ERROR_EMAIL_SUBJECT : 'Keeki - API failure';
        $url = KEEKI_API_URL;
        $message = "URL: $url\nRequest data: \n$request_xml\n\nResponse data:\n$response_xml";
        wp_mail( $to, $subject, $message );
    }

    /**
     * Replace named HTML entities with numbered ones.
     * Example: &nbsp;
     */
    private function xmlEntities($string) {
        $translationTable = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);

        foreach ($translationTable as $char => $entity) {
            $from[] = $entity;
            $to[] = '&#'.ord($char).';';
        }
        return str_replace($from, $to, $string);
    }

}
Class APIException extends \Exception {}
