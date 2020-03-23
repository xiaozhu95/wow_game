<?php
namespace app\api\controller;

use app\api\Controller;
use think\Request;
use think\Config;
use think\Loader;
use think\cache\driver\Redis;

class PayLog extends Controller
{
	protected static $blacklist = ['getlist'];
	private $pay_types;
	private $wechatKeys = [
		1 => [ // app
			'app_id' => '', // APPID (开户邮件中可查看)
			'mch_id' => '', // 商户号 (开户邮件中可查看)
			'app_key' => '' // 商户支付密钥 (https://pay.weixin.qq.com/index.php/account/api_cert)
		],
		3 => [ // 公众号
			'app_id' => 'wxaf2657b223a45530', // APPID (开户邮件中可查看)
			'mch_id' => '1271649801', // 商户号 (开户邮件中可查看)
			'app_key' => '87da6600103d56eb0a27e2eaf48bdeb0' // 商户支付密钥 (https://pay.weixin.qq.com/index.php/account/api_cert)
		],
		4 => [ // 小程序
			'app_id' => 'wxaf2657b223a45530', // APPID (开户邮件中可查看)
			'mch_id' => '1502367711', // 商户号 (开户邮件中可查看)
			'app_key' => 'fbdbcda9e93b2c8f7275d8a792491bb5' // 商户支付密钥 (https://pay.weixin.qq.com/index.php/account/api_cert)
		]
	];
	private $alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmwTFRV01G5R2ErVM/RVvOHC6HlsvQ8en1KfLtQTOuP6VzXRBgCoUPZQMzco1oEaznvB9aEbGeHVXTvhuCV4QaEQ7LjYBYyyfmLKhoBOutBr/A57XYOMRBFMGT55EZXuViB+9kzvmmRzUtl+kUDb9Sa814o7H9+a+EgNhFmJ+UK7Klu4QI9bE/Bm1HBqz+TwfxsP66+M0GhSlgUC/NjCazrsEkD6AusMUoweMscKm9LoyUV60UHjgevepUFlyJdGLQWKyP9NTLwMZi+9Tg2weElBs1WY/eKLDE1EPxnVawWRgeiH1waQDYzSt1KtDzoIjAJ1eB99eEq2AmSEmklJn0wIDAQAB';
	
	public function __construct()
  {
		$this->pay_types = Config::get('pay_types');
    parent::__construct();
	}

	private function alipay($opt){
		require_once(ROOT_PATH.'sdks'.DS.'alipay'.DS.'AopClient.php');
		require_once(ROOT_PATH.'sdks'.DS.'alipay'.DS.'request'.DS.'AlipayTradeAppPayRequest.php');

		$aop = new \AopClient;
		$aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
		$aop->appId = "2015092000306274";
		$aop->rsaPrivateKey = 'MIIEpQIBAAKCAQEAr+as0vBllVP4mfCeNV5ZJOH2BLiArxbERN/zJqku5Tl3uTmbxc7IM2smw67DhfulBfvObAvPbz8rwGRgxLrbeQEtMKhIF0d0u4QrqOo5ardJ0UxtNWQ1LvkKRW4Bg2J3rcqKKhq6pOqLIu1eLeQNamHl5ZSIq9RKHWHuiNGudFi+XBnVjejTCjIY5ByMnJy2L5sF6iLUtQpEdm+hV3kJr+VMwhhX0k7QCVleitqb2ybfIsWW/VVoXbDwOxaGw0oeWMc1uqa9o+ZsWfgBoMepz6+n5Cv2Y8ObdcZavuG2m72S11SOpSIjDkjlNwixhyFM+C5rXdm9yVYRuyZoN0VuFQIDAQABAoIBAQCmH478sHjfrKxOhkaEVJSQFq9ICg/OTAwUmASFcKaPadS+I8AP8ph0py+3Ayg5M03I9uUeeZDwmZJyYtpZMbfw8cGCAIwFIEEIj2zEXGDZfjzC5BEHqZnowN6Ib7oSIT6x7WTLGu9GrRO2asLVSFm7LLX4Om1RCm7sjP7ATHcb/NnMuIhHwFehjcd1TqyxekJteD01sU/8xhhUiSWZLNbUnNiMazEKSSsHYVHURZ7dBSoDfSimaJ78O8hWGYnYPV0bYX9vED6lG9poD4mQ/+d7n3yeSpQ3euyVuXA8do8oszu+NvSBiCN6TcRes19jWqVNpBbg/AWiFR87ZCNcIO7JAoGBAOWDkDo2SgA5BrWmyxuFvBLDIRcddo2qsesj6KksGad63oXW6yeFbsde+UUOzPT/AsthXdCTtdSMQ1Yp8mc/ULowPCK1uNvx3LU8yNp3oC2RCTDZI5XX/IZ90WXXrYvsChQ7l/BQif0VpCEqLV6d9x1yCmoXNXu428Ka//zgUhFzAoGBAMQzP8otpyXcuEzu44doOw/wjk3wPcU5bAHkC7ijNdLziWgr/YbFlzEOc3wO0jP5DVt/ZYZBUN6vsN56hXIjpcloOF+or+ps4Vc01ayOYApYvrwTWYAlRaNdGd4OQwiRCNIPhzwlRk6k1S4lykye9GWju5uKNqxvrfJxECn4uIBXAoGAGvrUQYOQP2Z0u3XX+mxGJ454nVcBULX4JEQcXYapnV58Og+BpSuyUg2AD/YlccdodLAFbzdt8IZsg/x6Wli/DKQO7aWfDXvpDgUPN19InRKnme5smHjDXqv7qZUo+YHNzYMT4VQWZIHewWdL3guDuRpmzwHbb6fTbZT68qcL/rECgYEAm5TMM8XJ8uM5HoCc2qZTl4s5PSKRyRCEzmcIyGxb5SELyBiCHVoYT2VXPHwAQghviCvY1QJ8X2nQhkuAAIe2EqVbdresb+fRNTcHbaMlE29WKIvrgAuOUkIkngqPK37fELwRkCc6vmhFSCfdaK7vvJ1+ypNqUYjp1gPEPpQlslcCgYEAsisfVHTtBBhQYFtrsteCvjfVoAOZo16Qd3t9KYAix/6hDysbfrcf+FhhHrtnt8zZyZEOzpa8aEFSTddJqba6H1KDtG3kVyObcIbAl56H4/Q14tJHSKdO8D9UKsm3ap6BazXZAtK0EXXaltM83FbcFqbo5og0ARMUXB/WL1VPyQ4=';
		$aop->format = "json";
		$aop->charset = "UTF-8";
		$aop->signType = "RSA2";
		$aop->alipayrsaPublicKey = $this->alipayrsaPublicKey;

		$request = new \AlipayTradeAppPayRequest();
		//SDK已经封装掉了公共参数，这里只需要传入业务参数
		$bizcontent = "{\"body\":\"".$opt['body']."\","
						. "\"subject\": \"".$opt['subject']."\","
						. "\"seller\": \"\","
						. "\"out_trade_no\": \"".$opt['out_trade_no']."\","
						. "\"timeout_express\": \"60m\","
						. "\"total_amount\": \"".$opt['total_amount']."\","
						. "\"product_code\":\"QUICK_MSECURITY_PAY\""
						. "}";
		$request->setNotifyUrl($this->request->domain()."/api/pay_log/success/type/2/");
		$request->setBizContent($bizcontent);
		//这里和普通的接口调用不同，使用的是sdkExecute
		$response = $aop->sdkExecute($request);
		return $response; //直接返回 不然app支付功能不能正常使用
		//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
		return htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
	}
	
	public function check(){
		$id = $this->request->param('id/d');
		$status = 0;
		if($id){
			$status = Loader::model('PayLog')->where(['id'=>$id])->value("status");
		}
		return front_ajax_return($status);
	}
  
  public function suc($opt){
		// $opt = json_decode('{"body":"eyJ1c2VyX2lkIjozLCJzdWJqZWN0X2lkIjoxfQ;;;;","out_trade_no":"14","trade_no":"4200000056201804169701239007"}',true);
		// file_put_contents('a.txt',$opt);
		$body = json_decode(base64_decode(str_replace(';;','=',$opt['body'])),true);
    $subjects = model('Subject')->cache();
		$subject = $subjects[$body['subject_id']];
		// file_put_contents('a.txt',json_encode($subject));
    if(isset($body['money']) && $body['money'] > 0){
      model('UserMoneyLog')->data([
        'user_id' => $body['user_id'],
        'amount' => -$body['money'],
				'msg' => '支付'.$subject['name'],
				'controller' => 'pay_log',
				'action' => 'add'
      ])->save();
    }
    
    if(isset($body['pc_id']) && $body['pc_id']){
      model('PromoCodeLog')->save(['status'=>2, 'pc_id'=>$body['pc_id']], ['pc_id'=>$body['pc_id']]);
    }
		
		if($subject['callback']){
			$body['pay_log_id'] = $opt['out_trade_no'];
			action($subject['callback'],$body);
		}
  }
	
	public function success(){
		$type = $this->request->param('type/d', 0);
		if($this->pay_types[$type]){
			switch($type){
				case 1:
				case 3:
				case 4:
					require_once(ROOT_PATH.'sdks'.DS.'wechat_pay'.DS.'WechatPay.php');
					$pay = new \WechatPay($this->wechatKeys[$type]);
					$res = $pay->getNotify();
					if ($res) {
						$suc = Loader::model('PayLog')->save([
							'wechat_openid' =>  $res['openid'],
							'trade_no' => $res['transaction_id'],
							'status'=>1
						],['id' => $res['out_trade_no'],'status'=>0]);
            
            if($suc){
              $this->suc([
                'body' => $res['attach'],
                'out_trade_no' => $res['out_trade_no'],
                'trade_no' => $res['transaction_id']
              ]);
            }
						$reply = "<xml>
									<return_code><![CDATA[SUCCESS]]></return_code>
									<return_msg><![CDATA[OK]]></return_msg>
								</xml>";
						exit($reply);      // 向微信后台返回结果。
					}
					break;
				case 2:
					require_once(ROOT_PATH.'sdks'.DS.'alipay'.DS.'AopClient.php');
					$aop = new \AopClient;
					$aop->alipayrsaPublicKey = $this->alipayrsaPublicKey;
					$flag = $aop->rsaCheckV1($_POST, NULL, "RSA2");
					if($flag && $_POST['trade_status']=='TRADE_SUCCESS'){
						//支付宝会多次发送成功请求 判断下 不重复执行了
						if(!Loader::model('PayLog')->where(['id'=>$_POST['out_trade_no']])->value('trade_no')){
							Loader::model('PayLog')->save([
								'alipay_username' =>  $_POST['buyer_logon_id'],
								'trade_no' => $_POST['trade_no'],
								'status'=>1
							],['id' => $_POST['out_trade_no'],'status'=>0]);
              
              $this->suc([
                'body' => $_POST['body'],
                'out_trade_no' => $_POST['out_trade_no'],
                'trade_no' => $_POST['trade_no'],
              ]);
						}
					}
					break;
        default: // 余额/优惠码付款成功
          $_POST = $this->request->post();
          $suc = Loader::model('PayLog')->save([
            'status'=>1
          ],['id' => $_POST['out_trade_no'], 'status'=>0]);
          if($suc){
            $this->suc([
              'body' => $_POST['body'],
              'out_trade_no' => $_POST['out_trade_no'],
              'trade_no' => $_POST['trade_no'],
            ]);
          }
          break;
			}
		}
	}
	
  public function add()
  {
        $id = $this->request->param('id/d');
        $type = $this->request->param('type/d');
        $user_id = $this->request->param('user_id/d');
        $subjects = model('Subject')->cache();
        if($subjects[$id] && $this->pay_types[$type] && $user_id){
            $total_amount = $this->request->param('price/f');
            $subject_name = $subjects[$id]['name'];
            $attach['user_id'] = $user_id;
            $attach['subject_id'] = $id;      
            $pc_id = $this->request->post('promo_code_id/d', 0);
            $third_party_payed = $total_amount;
            $model = Loader::model('PayLog');
            $pay_log = $model->where([
                'subject_id' => $id,
                'subject_name' => $subject_name,
                'user_id' => $user_id,
                'total_amount' => $total_amount,
                'third_party_payed' => $third_party_payed,
                'money' => $total_amount,
                // 'num' => $num,
                'pc_id' => $pc_id,
                'type' => $type,
                'status' => 0,
                'create_time' => ['>',time()-3600]
            ])->find();
    
            if(!$pay_log)
                $pay_log = $model->create([
                        'subject_id' => $id,
                        'subject_name' => $subject_name,
                        'user_id' => $user_id,
                        'total_amount' => $total_amount,
                        'third_party_payed' => $third_party_payed,
                        'money' => $total_amount,
                        // 'num' => $num,
                        'pc_id' => $pc_id,
                        'type' => $type,
                ]);
              
                switch($type){
                        case 1:
                        case 3:
                        case 4:
                                $trade_type = $this->request->param('trade_type');
                                if($trade_type == 'NATIVE'){
                                        $data = [];
                                }else{
                                        require_once(ROOT_PATH.'sdks'.DS.'wechat_pay'.DS.'WechatPay.php');
                                        $pay = new \WechatPay($this->wechatKeys[$type]);

                                        $data = [
                                                'body' => $subject_name,
                                                'attach' => str_replace('=',';;',base64_encode(json_encode($attach))),
                                                'total_fee' => $third_party_payed * 100,
                                                'spbill_create_ip' => $this->request->ip(),
                                                'out_trade_no' => $pay_log->id < 10 ? "0".$pay_log->id : $pay_log->id,
                                                'notify_url' => $this->request->domain()."/api/pay_log/success/type/$type/",
                                                'trade_type' => in_array($type,[3,4]) ? 'JSAPI' : 'APP', //JSAPI--公众号支付、NATIVE--原生扫码支付、APP--app支付
                                        ];
                                        
                                        // print_r($data);
                                        if(in_array($type,[3,4])){
                                                $openid = $this->request->post('openid') ?? Loader::model('User')->where(['id'=>$user_id])->value('openid');
                                                if(!$openid){ 
                                                        return front_ajax_return('please bind wechat first'); 
                                                } 
                                                $data['openid'] = $openid;
                                        }
                                       
                                        $data = $pay->getInfo($data);
                                }
                                break;
                        case 2:
                                $data = $this->alipay([
                                        'body' => str_replace('=',';;',base64_encode(json_encode($attach))),
                                        'subject' => $subject_name,
                                        'total_amount' => $third_party_payed,
                                        'out_trade_no' => $pay_log->id
                                ]);
                                break;
                        default:
                                $data = [];
                                break;
                }
                return front_ajax_return($pay_log->id,1,$data);
            }
	}

	public function qrcode($pay_log_id,$type){
		require_once(ROOT_PATH.'sdks'.DS.'wechat_pay'.DS.'WechatPay.php');
		$pay = new \WechatPay($this->wechatKeys[$type]);

		$qrcode_url = $pay->getQrcodeUrl($pay_log_id);
		\QRcode::png( $qrcode_url, false, 'H', 2 );
		exit;
	}
	
	public function qrcodePay($product_id){
		if($this->request->controller() != 'WechatPay') return;

		$pay_log = model('PayLog')->where(['id'=>$product_id])->find();
		if($pay_log){
				$pay_log->status = 2;
				$pay_log->save();
				$type = $pay_log['type'];
				switch($type){
					case 1:
					case 3:
					case 4:
						$attach = [
							'user_id'=>$pay_log['user_id'],
							'subject_id'=>$pay_log['subject_id'],
							'money'=>$pay_log['money'],
							'pc_id'=>$pay_log['pc_id'],
						];
						
						$data = [
							'body' => $pay_log['subject_name'],
							'attach' => str_replace('=',';;',base64_encode(json_encode($attach))),
							'total_fee' => $pay_log['third_party_payed'] * 100,
							'spbill_create_ip' => $this->request->ip(),
							'out_trade_no' => $pay_log->id < 10 ? "0".$pay_log->id : $pay_log->id,
							'notify_url' => $this->request->domain()."/api/pay_log/success/type/$type/",
							'trade_type' => 'NATIVE', //JSAPI--公众号支付、NATIVE--原生扫码支付、APP--app支付
							'product_id' => $product_id,
						];

						require_once(ROOT_PATH.'sdks'.DS.'wechat_pay'.DS.'WechatPay.php');
						$pay = new \WechatPay($this->wechatKeys[$type]);
						$data = $pay->generatePrepayId($data, true);
						exit($data);
					break;
				}
		}
	}
  
  public function cron(){
    $time = time() - 3600;
    //因为cron设置了  每1分钟更新一次 所以时间为 between -60到0
		$pc_ids = model('PayLog')->where(['status'=>0,'create_time'=>['between', ($time - 60).','.$time],'pc_id'=>['>',0]])->value('group_concat(pc_id)');
		if($pc_ids)
    	model('PromoCodeLog')->destroy(['pc_id'=>['in',$pc_ids], 'status'=>1]);
    
    //设置完了之后将pay_log的状态改为失效 采用数据库形式更新 这样不会自动更新update_time
    model('PayLog')->where(['status'=>0,'create_time'=>['between', ($time - 60).','.$time]])->update(['status'=>2]);
	}
}
