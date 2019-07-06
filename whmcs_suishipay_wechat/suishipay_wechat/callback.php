<?php
use WHMCS\Database\Capsule;
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
if(!function_exists("suishipay_wechat_sign_get")){
function suishipay_wechat_sign_get($para,$sk){
    ksort($para);
	$buff = "";
	foreach ($para as $k => $v)
	{
		if($k != "sign" && $v != "" && !is_array($v)){
			$buff .= $k . "=" . $v . "&";
		}
	}
	$buff = trim($buff, "&");
	$prestr = $buff . '&key='.$sk;
	return strtoupper(md5($prestr));
}
}
$gatewaymodule = "suishipay_wechat";
$GATEWAY       = getGatewayVariables($gatewaymodule);
if(!$GATEWAY["type"]){
	exit('fail');
}
$ReqData = json_decode(file_get_contents("php://input"),true);
$security['out_trade_no'] = @$ReqData['orderId'];
$security['total_fee'] = (@$ReqData['transAmount'])/100;
$security['trade_no'] = @$ReqData['transSeqId'];
$Sign = suishipay_wechat_sign_get(array("transSeqId"=>trim($ReqData['transSeqId']),"orderId"=>trim($ReqData['orderId']),"transAmount"=>trim($ReqData['transAmount']),"payTime"=>trim($ReqData['payTime'])),trim($GATEWAY['merchantSk']));
//额外手续费
$fee = 0;
if($Sign == @$ReqData['sign']){
    $invoiceid = checkCbInvoiceID($security['out_trade_no'], $GATEWAY["name"]);
    checkCbTransID($security['trade_no']);
    addInvoicePayment($invoiceid,$security['trade_no'],trim($security['total_fee']),$fee,$gatewaymodule);
    logTransaction($GATEWAY["name"], $ReqData, "Successful");
    echo json_encode(array('result'=>'0000','msg'=>'success'));
} else {
    echo 'fail';
}