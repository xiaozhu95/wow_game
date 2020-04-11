<?php
class WechatPay
{
	private $app_id; // APPID (开户邮件中可查看)
	private $mch_id; // 商户号 (开户邮件中可查看)
	private $app_key; // 商户支付密钥 (https://pay.weixin.qq.com/index.php/account/api_cert)
  
	public function __construct($opt){
		$this->app_id = $opt['app_id'];
		$this->mch_id = $opt['mch_id'];
		$this->app_key = $opt['app_key'];
	}

	public function getQrcodeUrl($product_id){
		$response = array(
			'appid'     => $this->app_id,
			'mch_id'  => $this->mch_id,
			'nonce_str'  => $this->generateNonce(),
			'product_id'   => $product_id,
			'time_stamp' => time()
		);
		$sign = $this->calculateSign($response, $this->app_key);
		$url = "weixin://wxpay/bizpayurl?appid=".$this->app_id."&mch_id=".$this->mch_id."&nonce_str=".$response['nonce_str']."&product_id=$product_id&time_stamp=".$response['time_stamp']."&sign=$sign";
		return $url;
	}

	public function getInfo($data){
		// get prepay id
		$prepay_id = $this->generatePrepayId($data);
		// re-sign it
		if($data['trade_type']=='JSAPI'){
			$response = array(
				'appId'     => $this->app_id,
				'signType'  => 'MD5',
				'package'   => 'prepay_id='.$prepay_id,
				'nonceStr'  => $this->generateNonce(),
				'timeStamp' => ''.time(),  //坑爹的需要字符串
			);
			$response['paySign'] = $this->calculateSign($response, $this->app_key);
		// }elseif($data['trade_type']=='NATIVE'){
		// 	$response = array(
		// 		'appid'     => $this->app_id,
		// 		'mch_id'  => $this->mch_id,
		// 		'prepay_id'   => $prepay_id,
		// 		'nonce_str'  => $this->generateNonce()
		// 	);
				
		// 	$response['sign'] = $this->calculateSign($response, $this->app_key);
		}else{
			$response = array(
				'appid'     => $this->app_id,
				'partnerid' => $this->mch_id,
				'prepayid'  => $prepay_id,
				'package'   => 'Sign=WXPay',
				'noncestr'  => $this->generateNonce(),
				'timestamp' => time(),
			);
			$response['sign'] = $this->calculateSign($response, $this->app_key);
		}         
		// send it to APP
		return $response;
	}
	
	//公众号向用户付款（提现）
	public function payToUser($data){
		$response = [
			'mch_appid' => $this->app_id,
			'mchid' => $this->mch_id,
			'nonce_str' => $this->generateNonce(),
			'partner_trade_no' => $data['withdraw_ids'],
			'openid' => $data['openid'],
			'check_name' => "NO_CHECK",
			'amount' => $data['amount'],
			'desc' => $data['desc'],
			'spbill_create_ip' => $data['ip'],
		];
		$response['sign'] = $this->calculateSign($response, $this->app_key);
		return $response;
	}

	public function selfPayTouser($xml)
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers", //东南亚 apihk 其他 apius 国内 api
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array('Content-Type: text/xml'),
            CURLOPT_POSTFIELDS     => $xml,
            CURLOPT_VERBOSE => true
        ));
        $result = curl_exec($ch);
        curl_close($ch);
        // get the prepay id from response
        $xml = simplexml_load_string($result);
        return (string)$xml->prepay_id;
    }
        
        public function arr2xml($arr){
            $simxml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><xml></xml>');//创建simplexml对象  
            foreach($arr as $k=>$v){  
                $simxml->addChild($k,$v);  
            } 
            return $simxml->saveXML();  
        }
	/**
	 * Generate a nonce string
	 *
	 * @link https://pay.weixin.qq.com/wiki/doc/api/app.php?chapter=4_3
	 */
	private function generateNonce()
	{
		return md5(uniqid('', true));
	}
	/**
	 * Get a sign string from array using app key
	 *
	 * @link https://pay.weixin.qq.com/wiki/doc/api/app.php?chapter=4_3
	 */
	private function calculateSign($arr, $key)
	{
		ksort($arr);
		$buff = "";
		foreach ($arr as $k => $v) {
			if ($k != "sign" && $k != "key" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
		$buff = trim($buff, "&");
		return strtoupper(md5($buff . "&key=" . $key));
	}
	
	
	/**
	 * 解析xml文档，转化为对象
	 * @param  String $xmlStr xml文档
	 * @return Object         返回Obj对象
	 */
	public function xmlToArray($xmlStr) {
		$array_data = json_decode(json_encode(simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $array_data;
	}
	
	/**
	 * 接收支付结果通知参数
	 * @return Object 返回结果对象；
	 */
	public function getNotify() {
		$postXml = file_get_contents('php://input');   // 接受通知参数；
                cache('post',$postXml);
		if (empty($postXml)) {
			return false;
		}
		$postArr = $this->xmlToArray($postXml);
		if (empty($postArr)) {
			return false;
		}elseif (!empty($postArr['return_code']) && $postArr['return_code'] == 'FAIL') {
			return false;
		}

		$checkSign = $this->calculateSign($postArr, $this->app_key);

		if($checkSign!=$postArr['sign']){
			return false;
		}
		return $postArr;
	}
	
	/**
	 * Generate a prepay id
	 *
	 * @link https://pay.weixin.qq.com/wiki/doc/api/app.php?chapter=9_1
	 */
	public function generatePrepayId($data, $raw=false)
	{
		$params = array(
			'appid'            => $this->app_id,
			'mch_id'           => $this->mch_id,
			'nonce_str'        => $this->generateNonce(),
		);
                
		$params = array_merge($params,$data);
                
		// add sign
		$params['sign'] = $this->calculateSign($params, $this->app_key);
               
		// var_dump($params);
		// create xml
                
		$xml = getXMLFromArray($params);
               
		// send request
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL            => "https://apius.mch.weixin.qq.com/pay/unifiedorder", //东南亚 apihk 其他 apius 国内 api
			CURLOPT_POST           => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => array('Content-Type: text/xml'),
			CURLOPT_POSTFIELDS     => $xml,
			CURLOPT_VERBOSE => true
		));
		$result = curl_exec($ch);
                 
		curl_close($ch);
                
		if($raw)
			return $result;

		// get the prepay id from response
		$xml = simplexml_load_string($result);
		return (string)$xml->prepay_id;
	}
}