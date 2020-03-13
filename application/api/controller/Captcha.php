<?php
namespace app\api\controller;


use app\api\Controller;
use think\Loader;


class Captcha extends Controller
{
	public function index(){
		$tel = $this->request->post('tel');
		if($tel){
			$data = [
				'code' => rand(1000, 9999),
				'type' => $this->request->post('type'),
				'tel'=>$tel,
				'expire_time'=>strtotime("900 seconds")
			];
			$user = Loader::model('User');
			
			if($data['type']=='register' && $user::get(['tel'=>$data['tel']]))
				return front_ajax_return("该手机号已注册");

			$model = $this->getModel();
			if($model::get(['type' => $data['type'],'tel'=>$data['tel'],'expire_time'=>['>',$data['expire_time']-60]])){
				return front_ajax_return("发送过于频繁");
			}elseif($model->where(['type' => $data['type'],'tel'=>$data['tel']])->count()>10){
				return front_ajax_return("发送已达上限，若一直收不到验证码，请联系客服");
			}else{
				$model::create($data);
				
				// 调用接口 发送短信
				$res = $model->send($data);
				if($res->Code == 'OK')
					return front_ajax_return("验证码已发送到您的手机",1);
				else
					return front_ajax_return("验证码发送失败，".$res->Message);
			}
		}
	}
}
