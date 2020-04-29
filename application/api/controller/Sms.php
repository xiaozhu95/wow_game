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
            'msg' => '失败'
        ];
        $userModel = $this->getModel();
        if (!input("?param.mobile")) {
            $result['msg'] = '请输入手机号码';
            return json($result);
        }
        //code的值可以为loign，reg，veri
        if (!input("?param.code")) {
            $result['msg'] = '缺少核心参数';
            return json($result);
        }
        $code = input('param.code');
        $type = input('param.type');
        if ($type == 'bind') { //绑定会员，这个if迟早要拿掉，绑定的话，也发送login状态就行
            $code = 'login';
        }

        return $userModel->send(input('param.mobile'), $code, []);
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
        return $userModel->smsVeri($data);
    }

    /**
     * 公众号支付
     * 手机验证码
     *
     */
    public function smsTelLogin()
    {
        $mobile = $this->request->param('mobile');

        $code = "h5Pay";
        $model = $this->getModel();

        $result = $model->smsTelLogin($mobile,$code);
        return $result;
    }
    /**
     *
     *支付短信验证
     *
     */
    public function smsPay()
    {
        $user_id = $this->request->param('user_id');
        $mobile = $this->request->param('mobile');
        $token = $this->request->param('token');
        $vi = $this->request->param('vi');
        $user_id = decrypt($user_id,$vi);
        $mobile = decrypt($mobile,$vi);
        if(!$user_id || !$mobile) return ajax_return_adv_error('信息验证不通过');

        if (md5($user_id) == $token){
            $user = model("user")->where(['id'=>$user_id,'mobile'=>$mobile])->find();
            if(!$user_id){
                return ajax_return_adv_error('暂未绑定手机号请绑定后在体现');
            }
            $code = "pay";
            $model = $this->getModel();

            $result = $model->smsTelLogin($mobile,$code);
            return $result;
        }
        return ajax_return_adv_error('验证不通过');
    }

    /**
     * 公众号支付
     * 验证
     */
    public function smsVery($mobile,$code)
    {

        $result = array(
            'code' => 1,
            'data'   => '',
            'msg'    => ''
        );
        if (!isset($mobile)) {
            $result['msg'] = '请输入手机号码';
            return json($result);
        }
        if (!isset($code)) {
            $result['msg'] = '请输入验证码';
            return json($result);
        }
        $model = $this->getModel();

        //判断是否是用户名登陆
        $smsStatus = $model->check($mobile, $code, 'h5Pay');
        if($smsStatus == 1){
            $result['msg'] = '短信验证码错误';
            return json($result);
        }elseif($smsStatus==2){
            $result['msg'] = '短信验证码过期,请重新发送';
            return json($result);
        }
        $user = model('user');
        $userInfo = $user->where(['mobile'=>$mobile])->find();
        if(!$userInfo){
            $result['msg'] = '请到微信异构注册后在充值';
        }else{
            $result['code'] = 0;
            $result['data'] = ['user_id'=>$userInfo->id];
        }
        return json($result);
    }
    public function encrypt()
    {
        $data = $this->request->param();
        $result = encrypt($data);
        return ajax_return($result);
    }
    protected function aftergetList(&$data){

    }
}
