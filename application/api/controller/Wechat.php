<?php
namespace app\api\controller;

use think\Cache;
use think\Request;
					
class Wechat
{
	private $wechatKeys = [
		3 => [ //公众号
			'appId' => 'wxb76fe20a514815c7',
			'appSecret' => '24a0cd10946f515274291de5d39ff7e1',
		],
		4 => [ //小程序
			'appId' => 'wxbacf8be4001fc702',
			'appSecret' => 'fd1d5e1fc466c028b35ec066d876d557',
		], 
	];
	// private $siteUrl = "https://vote.fengsh.cn/";
	// private $db_field = "wechat_openid";
	
	private $token = "xJ4EPvdb7Pk8bm85y8JN";
	private $request;
	
	public function __construct(){
		$this->request = Request::instance();
	}
	
	
	public function checkToken(){ 
		$echoStr = $_GET["echostr"];
		if($this->checkSignature()){
			return $echoStr;
		}
	}
	public function index(){

	}
  
  private function msg($data){
    $this->execute("https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$this->getAccessToken(),$data,1);
  }
	
	private function userInfo($openid){
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->getAccessToken()."&openid=$openid&lang=zh_CN";
		return json_decode($this->execute($url));
	}

	private function reply($opt,$postObj){
		$opt['ToUserName'] = $postObj->FromUserName;
		$opt['FromUserName'] = $postObj->ToUserName;
		$opt['CreateTime'] = time();
		$xml = getXMLFromArray($opt);
		exit($xml);
	}
	
	private function checkSignature()
	{
		$signature = $this->request->param("signature");
		$timestamp = $this->request->param("timestamp");
		$nonce = $this->request->param("nonce");
		$token = $this->token;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
  
  public function getSnsAccessToken($code){
    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->wechatKeys[3]['appId']."&secret=".$this->wechatKeys[3]['appSecret']."&code=$code&grant_type=authorization_code";
    return json_decode($this->execute($url));
  }
	
	private function getAccessToken(){
		$key = $this->wechatKeys[3]['appId'].'_access_token';
		$accessToken = Cache::get($key);
		if($accessToken) return $accessToken;
		
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->wechatKeys[3]['appId']."&secret=".$this->wechatKeys[3]['appSecret'];
		$con = json_decode($this->execute($url));
		Cache::set($key,$con->access_token,$con->expires_in);
		return $con->access_token;
	}
  
  private function getJsTicket(){
		$key = $this->wechatKeys[3]['appId'].'_js_ticket';
		$ticket = Cache::get($key);
    if(!$ticket){
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$this->getAccessToken()."&type=jsapi";
      $data = json_decode($this->execute($url));
      $ticket = $data->ticket;
      Cache::set($key,$ticket,$data->expires_in);
    }
    return $ticket;
  }
	
  public function config(){
    $url = $this->request->post("url","","htmlspecialchars_decode");
    $config = [
      'timestamp' => time(),
      'noncestr' => md5(uniqid('', true)),
      'url' => $url,
      'jsapi_ticket' => $this->getJsTicket()
    ];
    $config['signature'] = $this->calculateSign($config);
    $config['appid'] = $this->wechatKeys[3]['appId'];
    $config['title'] = $this->request->post('title');
    $config['imgUrl'] = $this->request->post('pic');
    $config['desc'] = $this->request->post('message');
    
    return json($config);
  }
  
  private function calculateSign($arr)
  {
      ksort($arr);
      $buff = "";
      foreach ($arr as $k => $v) {
        if ($k != "sign" && $k != "key" && $v != "" && !is_array($v)){
          $buff .= $k . "=" . $v . "&";
        }
      }
      $buff = trim($buff, "&");
      return sha1($buff);
  }
	
	private function execute($url,$data='',$post=0,$cert=0){
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_POST, $post);
		if($post)
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		if($cert){
			curl_setopt($ch, CURLOPT_SSLCERT, ROOT_PATH.'sdks'.DS.'wechat_pay'.DS.'/apiclient_cert.pem');
			curl_setopt($ch, CURLOPT_SSLKEY, ROOT_PATH.'sdks'.DS.'wechat_pay'.DS.'/apiclient_key.pem');
		}
		
		curl_setopt($ch, CURLOPT_URL,$url);				
		return curl_exec($ch);
	}
	
	public function jscode2session(){
    $code = $this->request->post('code');
    $jscode2session = json_decode($this->execute("https://api.weixin.qq.com/sns/jscode2session?appid=".$this->wechatKeys[4]['appId']."&secret=".$this->wechatKeys[4]['appSecret']."&js_code=$code&grant_type=authorization_code"));
    return front_ajax_return("success",1,$jscode2session);
  }
}