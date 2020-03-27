<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class Sms extends Controller
{
   use \app\api\traits\controller\Controller;

    protected function filter(&$map)
    {
      $model = $this->getModel();
      
    }
  
    /**
     * 发送登陆注册短信，type为1注册，为2登陆
     * @return array|mixed
     */
    public function sms()
    {
        $result = [
            'code' => 1,
            'data' => [],
            'msg' => '成功'
        ];
        $userModel = $this->getModel();
        if (!input("?param.mobile")) {
            $result['msg'] = '请输入手机号码';
            return $result;
        }
        //code的值可以为loign，reg，veri
        if (!input("?param.code")) {
            $result['msg'] = '缺少核心参数';
            return $result;
        }
        $code = input('param.code');
        $type = input('param.type');
        if ($type == 'bind') { //绑定会员，这个if迟早要拿掉，绑定的话，也发送login状态就行
            $code = 'login';
        }
       
        return $userModel->sms(input('param.mobile'), $code);
    }
    /**
     * 短信验证码登陆，手机短信验证注册账号
     * mobile       手机号码，必填
     * code         手机验证码，必填
     * invitecode   邀请码，推荐人的邀请码 选填
     * password     注册的时候，可以传密码 选填
     * user_wx_id   第三方登录，微信公众号里的登陆，微信小程序登陆等需要绑定账户的时候，要传这个参数，这是第一次的时候需要这样绑定，以后就不需要了  选填
     * @return array
     */
    public function smsLogin()
    {
        
        $platform = input('param.platform', 1);
        $userModel = $this->getModel();
        $data = input('param.');
       
        $user_id = input('user_id', 0);
        if(!$user_id){
            return ajax_return_adv_error('user_id不存在');
        }
        $data['user_id'] = $user_id;
        return json($userModel->smsVeri($data));
    }
    

    protected function aftergetList(&$data){

    }
}
