<?php
namespace app\common\model;

use think\Model;
use think\Cache;
// use think\Request;
					
class Wechat extends Model
{
	private $wechatKeys = [
		3 => [ //公众号
			'appId' => '',
			'appSecret' => '',
		],
		4 => [ //小程序
			'appId' => 'wxa07d866227c5c901',
			'appSecret' => 'dbe400b609953d04f5b43bfb16e0c280',
		], 
	];
	// private $siteUrl = "http://www.fengsh.com/";
	// private $db_field = "wechat_openid";
	
    private $token = "xJ4EPvdb7Pk8bm85y8JN";
    
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
    
    private function checkSignature($signature,$timestamp,$nonce)
    {
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

    public function jscode2session($code){
        $jscode2session = json_decode($this->execute("https://api.weixin.qq.com/sns/jscode2session?appid=".$this->wechatKeys[4]['appId']."&secret=".$this->wechatKeys[4]['appSecret']."&js_code=$code&grant_type=authorization_code"));
        return $jscode2session;
    }
    
    public function decryptData($sessionKey,$iv,$encryptedData){
        require_once(ROOT_PATH.'sdks'.DS.'wechat'.DS.'wxBizDataCrypt.php');
        $pc = new \WXBizDataCrypt($this->wechatKeys[4]['appId'], $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data );

        if ($errCode == 0) {
            return json_decode($data);
        } 
    }
}
