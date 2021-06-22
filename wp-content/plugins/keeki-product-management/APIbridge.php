<?php

/**
 * Used as bridge to allow access to the interal API for testing purposes
 *
 *
 * @version 1.0
 * @author mlopez
 */

//$xml ='<request clientKey="KeekiAPIPassword123498765"><table name="STOCK" sortBy="STOCKCODE" maxRecords="150" FirstRecord="1" ><fields><field>LASTPRICE</field><field>STOCKCODE</field><field>DESC</field><field>QDESC</field><field>WEBDESC</field><field>WEBINFO</field><field>RECRETEX</field><field>RECRETINC</field><field>WIDTH</field><field>DEPTH</field><field>HEIGHT</field><field>FIELD1</field><field>SMALPIC</field><field>STKIND1</field><field>WEIGHT</field><field>QOH1</field><field>QTYALLOC</field></fields><conditions><condition field="STKIND1" type="equals">Y</condition><condition field="DEAD" type="notEqual">Y</condition><condition field="STOCKCODE" type="greaterThan">000</condition></conditions></table></request>';

$xml  = file_get_contents("php://input");

define('ABSPATH','');
require_once(dirname(__FILE__) . '/classes/class-api.php');
require_once(dirname(__FILE__) . '/config.php');

try{
	$API = KPM\API::getInstance();
	$response = $API->postRequest($xml, null, null, true);
} catch(Exception $e){
	echo $e->getMessage();
}



header("HTTP/1.1 200 OK");
header('Content-Type: text/xml; charset=utf-8');
echo $response;

