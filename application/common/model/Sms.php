<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class Sms extends Model
{
    // 指定表名,不含前缀
    protected $name = 'sms';


    const STATUS_UNUSED = 1;        //状态 未使用
    const STATUS_USED = 2;          //已使用

    //短信类型，这里的短信类型千万不要和message_center表里的类型冲突掉，哪里是总的类型，这里的是此模型特有的类型
    public $sms_tpl = [
        'reg' => [
            'name' => '用户注册',
            'check' => true
        ],
        'login' => [
            'name' => '用户登陆',
            'check' => true
        ],
        'veri' => [
            'name' => '短信校验',
            'check' => true
        ],


    ];

    /**
     * 登陆注册的时候，发送短信验证码
     */
    public function sms($mobile, $code)
    {
        $result = [
            'code' => 1,
            'data'   => '',
            'msg'    => '成功'
        ];

        $userInfo = model('user')->where(array('mobile' => $mobile))->find();
        if ($code == 'reg') {
            //注册
            if ($userInfo) {
                $result['msg'] = '此账号已经注册过';
                return json($result);
            }
        } elseif ($code == 'login') {
            //登陆
        } elseif ($code === 'veri') {
            // 找回密码
        } else {
            //其他业务逻辑
            $result['msg'] = '无此业务类型';
            return json($result);
        }

        //没问题了，就去发送短信验证码
        return $this->send($mobile, $code, []);
    }

    public function send($mobile,$code,$params)
    {
        if(!$mobile){
            return ajax_return_adv_error('电话号码不存在');
        }
        //如果是登陆注册等的短信，增加校验
        if($code == 'reg' || $code == 'login' || $code== 'veri'){
            $smsInfo = $this->where(['mobile'=>$mobile,'code'=>$code])->where('ctime', 'gt', time()-60*10)->where('status', 'eq', self::STATUS_UNUSED)->order('id desc')->find();
            if($smsInfo){
                if(time() - $smsInfo['ctime'] < 180){
                    return ajax_return_adv_error('两次发送时间间隔小于180秒');
                }
                $params = json_decode($smsInfo['params'],true);
            }else{
                $params = [
                    'code'=> rand(100000,999999)
                ];
            }
            $status = self::STATUS_UNUSED;
        }else{
            $status = self::STATUS_USED;
        }
        $str = $this->temp($code,$params);
        if($str == ''){
            return ajax_return_adv_error('类型不存在');
        }
        $data['mobile'] = $mobile;
        $data['code'] = $code;
        $data['params'] = json_encode($params);
        $data['content'] = $str;
        $data['ctime'] = time();
        $data['ip'] = get_client_ip(0,true);
        $data['status'] = $status;
        $this->save($data);

        $re = $this->sendsms($mobile,$str,$code,$params);
        return $re;
    }
    public function smsTelLogin($mobile,$code)
    {
        if(!$mobile){
            return ajax_return_adv_error('电话号码不存在');
        }
        $smsInfo = $this->where(['mobile'=>$mobile,'code'=>$code])->where('ctime', 'gt', time()-60*10)->where('status', 'eq', self::STATUS_UNUSED)->order('id desc')->find();

        if($code == 'h5Pay' || $code == 'pay'){

            if($smsInfo){

                if(time() - $smsInfo['ctime'] < 180){
                    return ajax_return_adv_error('两次发送时间间隔小于180秒');
                }
                $params = json_decode($smsInfo['params'],true);
            }else{
                $params = [
                    'code'=> rand(100000,999999)
                ];
            }
            $status = self::STATUS_UNUSED;
        }else{

            $status = self::STATUS_USED;
        }


        $str = "【杭州异构科技】验证码".$params['code']."，请勿告诉他人。";
        $data['mobile'] = $mobile;
        $data['code'] = $code;
        $data['params'] = json_encode($params);
        $data['content'] = $str;
        $data['ctime'] = time();
        $data['ip'] = get_client_ip(0,true);
        $data['status'] = $status;
        $this->save($data);
        $re = $this->sendsms($mobile,$str,$code,$params);
        return $re;
    }
    public function sendsms($mobile,$content,$code,$params)
    {
        $host = "http://47.98.130.42:7862/sms";
        $path = "";
        $method = "GET";
        // $appcode = "你自己的AppCode";
        //$headers = array();
        //array_push($headers, "Authorization:APPCODE " . $appcode);

        $content = urlencode($content);
        $querys = "action=send&account=940052&password=EchzeA&mobile={$mobile}&content={$content}&extno=1069016&rt=json";

        // \think\Log::info('Shenbosms', ['mobile'=>$mobile,'content'=>$content,'request'=>$querys]);
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        //   curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        //curl_setopt($curl, CURLOPT_HEADER, true); 如不输出json, 请打开这行代码，打印调试头部状态码。
        //状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $out_put = curl_exec($curl);
        $result = json_decode($out_put,true);
        if($result['status'] == 0){
            $result = [
                'code' => 0,
                'data' => '',
                'msg' => '发送成功'
            ];
        }else{
            $result = [
                'code' => 0,
                'data' => '',
                'msg' => '发送失败'
            ];
        }
        return json($result);
    }

    public function check($phone,$ver_code,$code){

        $where[] = ['mobile', 'eq', $phone];
        $where[] = ['code', 'eq', $code];
//        $where[] = ['ctime', 'gt', time()-60*10];

        //$where[] = ['ip', 'eq', get_client_ip()]; #先屏蔽ip检查，避免增加cdn或代理ip时出现问题

        $where[] = ['status', 'eq', self::STATUS_UNUSED];
        $sms_info = $this->where(['mobile'=>$phone,'code'=>$code,'status'=>self::STATUS_UNUSED])->order('id desc')->find();

        if($sms_info){

            if($sms_info['ctime'] + 60 * 5>time()){
                $params = json_decode($sms_info['params'],true);

                if($params['code'] == $ver_code){
                    $this->where(['mobile'=>$phone,'code'=>$code,'status'=>self::STATUS_UNUSED])->update(array('status'=>self::STATUS_USED));
                    return 0;//验证成功
                }else{
                    return 1;//验证码有误
                }
            }else{
                return 2;//验证码过期
            }
        }else{
            return 1;//验证码有误
        }
    }

    private function temp($code,$params){
        $msg = '';
        switch ($code)
        {
            case 'reg':
                // 账户注册
                // $params['code'] = 验证码

                $msg = "【杭州异构科技】验证码".$params['code']."，请勿告诉他人。";
                break;
            case 'login':
                // 账户登录
                // $params['code'] = 验证码
                $msg = "您正在登陆账号，验证码是".$params['code']."，请勿告诉他人。";
                break;
            case 'veri':
                // 验证验证码
                // $params['code'] = 验证码
                $msg = "您的验证码是".$params['code']."，请勿告诉他人。";
                break;
            case 'create_order':
                // 订单创建
                // $params['order_id'] = 订单号
                // $params['ship_addr'] = 收货详细地址包含省市区
                // $params['ship_name'] = 收货人姓名
                // $params['ship_mobile'] = 收货人手机号
                // $params['goods_amount'] = 商品总价
                // $params['cost_freight'] = 快递费
                // $params['order_amount'] = 订单总价 = 商品总价+快递费
                // $params['point'] = 使用抵扣积分单位个
                // $params['point_money'] = 积分抵扣金额单位元
                // $params['order_pmt'] = 订单优惠单位元
                // $params['goods_pmt'] = 商品优惠单位元
                // $params['coupon_pmt'] = 优惠券优惠单位元
                // $params['memo'] = 下单买家备注
                $msg = "恭喜您，订单创建成功,祝您购物愉快。";
                break;
            case 'order_payed':
                // 订单支付通知买家
                // $params['order_id'] = 订单号
                // $params['goods_amount'] = 商品总价
                // $params['cost_freight'] = 快递费
                // $params['order_amount'] = 订单总价
                // $params['money'] = 支付金额
                // $params['pay_time'] = 支付时间
                // $params['point'] = 使用抵扣积分单位个
                // $params['point_money'] = 积分抵扣金额单位元
                // $params['order_pmt'] = 订单优惠单位元
                // $params['goods_pmt'] = 商品优惠单位元
                // $params['coupon_pmt'] = 优惠券优惠单位元
                // $params['memo'] = 下单买家备注
                // $params['user_name'] = 买家昵称
                $msg = "恭喜您，订单支付成功,祝您购物愉快。";
                break;
            case 'remind_order_pay':
                // 未支付催单
                // $params['order_id'] = 订单号
                // $params['goods_amount'] = 商品总价
                // $params['cost_freight'] = 快递费
                // $params['order_amount'] = 订单总价
                // $params['money'] = 支付金额
                // $params['pay_time'] = 支付时间
                // $params['point'] = 使用抵扣积分单位个
                // $params['point_money'] = 积分抵扣金额单位元
                // $params['order_pmt'] = 订单优惠单位元
                // $params['goods_pmt'] = 商品优惠单位元
                // $params['coupon_pmt'] = 优惠券优惠单位元
                // $params['memo'] = 下单买家备注
                // $params['user_name'] = 买家昵称
                $msg = "您的订单还有1个小时就要取消了，请及时进行支付。";
                break;
            case 'delivery_notice':
                // 订单发货
                // $params['order_id'] = 订单号
                // $params['goods_amount'] = 商品总价
                // $params['cost_freight'] = 快递费
                // $params['order_amount'] = 订单总价
                // $params['money'] = 支付金额
                // $params['pay_time'] = 支付时间
                // $params['point'] = 使用抵扣积分单位个
                // $params['point_money'] = 积分抵扣金额单位元
                // $params['order_pmt'] = 订单优惠单位元
                // $params['goods_pmt'] = 商品优惠单位元
                // $params['coupon_pmt'] = 优惠券优惠单位元
                // $params['memo'] = 下单买家备注
                // $params['user_name'] = 买家昵称
                // $params['logistics_name'] = 快递公司
                // $params['ship_no'] = 快递编号
                // $params['ship_name'] = 收货人姓名
                // $params['ship_mobile'] = 收货人电话
                // $params['ship_addr'] = 收货详细地址
                $msg = "您好，您的订单已经发货。";
                break;
            case 'aftersales_pass':
                // 售后审核通过
                // $params['order_id'] = 订单号
                // $params['goods_amount'] = 商品总价
                // $params['cost_freight'] = 快递费
                // $params['order_amount'] = 订单总价
                // $params['money'] = 支付金额
                // $params['pay_time'] = 支付时间
                // $params['point'] = 使用抵扣积分单位个
                // $params['point_money'] = 积分抵扣金额单位元
                // $params['order_pmt'] = 订单优惠单位元
                // $params['goods_pmt'] = 商品优惠单位元
                // $params['coupon_pmt'] = 优惠券优惠单位元
                // $params['memo'] = 下单买家备注
                // $params['user_name'] = 买家昵称
                // $params['aftersales_id'] = 售后单号
                $msg = "您好，您的售后已经通过。";
                break;
            case 'refund_success':
                // 退款已处理
                // $params['refund_id'] = 退款单ID
                // $params['aftersales_id'] = 售后单id
                // $params['money'] = 退款金额
                // $params['type'] = 1=订单 2=充值单
                // $params['source_id'] = 订单或充值单ID
                $msg = "用户您好，您的退款已经处理，请确认。";
                break;
            case 'seller_order_notice':
                // 订单支付通知卖家
                // $params['order_id'] = 订单号
                // $params['goods_amount'] = 商品总价
                // $params['cost_freight'] = 快递费
                // $params['order_amount'] = 订单总价
                // $params['money'] = 支付金额
                // $params['pay_time'] = 支付时间
                // $params['point'] = 使用抵扣积分单位个
                // $params['point_money'] = 积分抵扣金额单位元
                // $params['order_pmt'] = 订单优惠单位元
                // $params['goods_pmt'] = 商品优惠单位元
                // $params['coupon_pmt'] = 优惠券优惠单位元
                // $params['memo'] = 下单买家备注
                // $params['user_name'] = 买家昵称
                $msg = "您有新的订单了，请及时处理。";
                break;
            case 'common':
                $msg = $params['tpl'];
                break;
        }
        return $msg;
    }

    protected function tableWhere($post)
    {
        $where = [];

        if(isset($post['id']) && $post['id'] != ""){
            $where[] = ['id', 'eq', $post['id']];
        }
        if(isset($post['mobile']) && $post['mobile'] != ""){
            $where[] = ['mobile', 'eq', $post['mobile']];
        }
        if(isset($post['code']) && $post['code'] != ""){
            $where[] = ['code', 'eq', $post['code']];
        }
        if(isset($post['ip']) && $post['ip'] != ""){
            $where[] = ['ip', 'eq', $post['ip']];
        }

        if(input('?param.date')){
            $theDate = explode(' 到 ',input('param.date'));
            if(count($theDate) == 2){
                $where[] = ['ctime', '<', strtotime($theDate[1])];
                $where[] = ['ctime', '>', strtotime($theDate[0])];
            }
        }


        if(isset($post['status']) && $post['status'] != ""){
            $where[] = ['status', 'eq', $post['status']];
        }

        $result['where'] = $where;
        $result['field'] = "*";
        $result['order'] = "ctime desc";
        return $result;
    }

    /**
     * 根据查询结果，格式化数据
     * @author sin
     * @param $list
     * @return mixed
     */
    protected function tableFormat($list)
    {
        foreach($list as $k => $v) {
            if($v['status']) {
                $list[$k]['status'] = config('params.sms')['status'][$v['status']];
            }

            if($v['ctime']) {
                $list[$k]['ctime'] = getTime($v['ctime']);
            }

        }
        return $list;
    }


    /** 手机号验证并绑定手机号*/
    public function smsVeri($data)
    {
        $result = array(
            'code' => 1,
            'data'   => '',
            'msg'    => ''
        );
        if (!isset($data['mobile'])) {
            $result['msg'] = '请输入手机号码';
            return json($result);
        }
        if (!isset($data['code'])) {
            $result['msg'] = '请输入验证码';
            return json($result);
        }

        //判断是否是用户名登陆
        $smsStatus = $this->check($data['mobile'], $data['code'], 'reg');
        if($smsStatus == 1){
            $result['msg'] = '短信验证码错误';
            return json($result);
        }elseif($smsStatus==2){
            $result['msg'] = '短信验证码过期,请重新发送';
            return json($result);
        }
        $user = new User();
        $userInfo = $user->where(['id'=>$data['user_id']])->find();

        if($userInfo){
            $userInfo->mobile = $data['mobile'];
            if($userInfo->save()){
                $result['code'] = 0;
                $result['data'] = ['mobile'=>$data['mobile']];
                $result['msg'] = "手机号保存成功";
            }else{
                $result['msg'] = "手机号保存失败";
            }
            return json($result);
        }else{
            $result['msg'] = "用户不存在";
        }
        return json($result);
    }
    public function getCtimeTextAttr($value,$data)
    {

       return date('Y-m-d H:i:s',$data['ctime']);
    }
    public function getStatusTextAttr($value,$data)
    {
        $status = [1=>'未使用',2=>'已使用'];
        return $status[$data['status']];
    }
}
