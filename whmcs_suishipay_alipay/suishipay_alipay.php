<?php
use WHMCS\Database\Capsule;

function suishipay_alipay_MetaData() {
    return array(
        'DisplayName' => '随时付(支付宝)',
        'APIVersion' => '1.1',
    );
}

function suishipay_alipay_config() {
    $configarray = array(
        "FriendlyName"  => array(
            "Type"  => "System",
            "Value" => "随时付(支付宝)"
        ),
        "merchantNo"  => array(
            "FriendlyName" => "商户号",
            "Type"         => "text",
            "Size"         => "32"
        ),
        "merchantSk" => array(
            "FriendlyName" => "请求密钥",
            "Type"         => "text",
            "Size"         => "32"
        )
    );

    return $configarray;
}

function suishipay_alipay_link($params) {
	if(@$_REQUEST['getstatus'] == 'yes'){
		return '等待支付中';
	}
    if($_REQUEST['suishipaysub'] == 'yes'){
	   $PaySign = suishipay_alipay_sign_get(array("merchantNo"=>trim($params['merchantNo']),"orderId"=>$params['invoiceid'],"transAmount"=>($params['amount']*100),"payChannelCode"=>'ALIPAY',"notifyUrl"=>$params['systemurl'].'/modules/gateways/suishipay_alipay/callback.php'),trim($params['merchantSk']));
	   $GetInfo = json_decode(suishipay_alipay_curl_post('http://suishipay.com:30000/payment/trans/create',json_encode(array("merchantNo"=>trim($params['merchantNo']),"orderId"=>$params['invoiceid'],"transAmount"=>($params['amount']*100),"payChannelCode"=>'ALIPAY',"notifyUrl"=>$params['systemurl'].'/modules/gateways/suishipay_alipay/callback.php','sign'=>$PaySign)),true),true);
	   if(!$GetInfo){
		   exit('支付服务器未返回任何有效信息');
	   }
	   if($GetInfo['result'] != '0000'){
		   exit('支付订单添加错误：'.$GetInfo['msg']);
	   }
	   $userdata = array();
	   $userdata['qrcode'] = 'modules/gateways/suishipay_alipay/qrcode.php?data='.urlencode($GetInfo['data']['qrCode']);
	   $userdata['money'] = ($GetInfo['data']['realAmount'])/100;
	   $userdata['invoiceid'] = $params['invoiceid'];
	   //$userdata['make_time'] = date('Y-m-d H:i:s',$GetInfo['data']['maketime']);
	   //$userdata['end_time'] = date('Y-m-d H:i:s',$GetInfo['data']['stoptime']);
	   $userdata['order_id'] = $GetInfo['data']['transSeqId'];
	   $userdata['outTime'] = 3*60;
	   $userdata['logoShowTime'] = 1;
	   exit(suishipay_alipay_makehtml(json_encode($userdata)));
	}
    if(stristr($_SERVER['PHP_SELF'],'viewinvoice')){
		return '<form method="post" id=\'suishipaysub\'><input type="hidden" name="suishipaysub" value="yes"></form><button type="button" class="btn btn-danger btn-block" onclick="document.forms[\'suishipaysub\'].submit()">使用支付宝支付</button>';
    }else{
         return '<img style="width: 150px" src="'.$params['systemurl'].'/modules/gateways/suishipay_alipay/alipay.png" alt="支付宝支付" />';
    }

}

if(!function_exists("suishipay_alipay_makehtml")){
function suishipay_alipay_makehtml($userdata){
	$skin_raw = file_get_contents(__DIR__ . "/suishipay_alipay/themes.tpl");
    $skin_raw = str_replace('{$userdata}',$userdata,$skin_raw);
    return $skin_raw;
}
}

if(!function_exists("suishipay_alipay_curl_post")){
function suishipay_alipay_curl_post($url,$data,$json = false){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	if($json){
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data)));
	}
    $tmpInfo = curl_exec($curl);
    curl_close($curl);
    return $tmpInfo;
}
}
if(!function_exists("suishipay_alipay_sign_get")){
function suishipay_alipay_sign_get($para,$sk){
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