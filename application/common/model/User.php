<?php
namespace app\common\model;

use think\Model;
use think\Request;
use think\Config;

class User extends Model
{
    // 指定表名,不含前缀
    protected $name = 'user';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $createTime  = "ctime";
    protected $updateTime = "utime";


    protected function setCreateIpAttr()
    {
        return Request::instance()->ip();
    }

    protected function getIdTextAttr($value,$data)
    {
        return base_convert($data['id'],10,36);
    }
    protected function getTelTextAttr($value,$data)
    {
        return $data['tel'] ?: '-';
    }

    protected function getSignAttr($value,$data)
    {
        return password_hash_tp($data['id']);
    }

    protected function getAuthAttr($value,$data)
    {
        return $this->generateAuth($data['id']);
    }

    protected function getProvinceTextAttr($value,$data)
    {
        if($data['province'])
            return model("Region")->where(['region_id'=>$data['province']])->value('region_name');
    }
    protected function getCityTextAttr($value,$data)
    {
        if($data['city'])
            return model("Region")->where(['region_id'=>$data['city']])->value('region_name');
    }
    protected function getDistrictTextAttr($value,$data)
    {
        if($data['district'])
            return model("Region")->where(['region_id'=>$data['district']])->value('region_name');
    }

    protected function getTypeTextAttr($value, $data){
        $texts = Config::get('user_types');
        return $texts[$data['type']];
    }

    private function generateAuth($id){
        $auth = strtoupper(base_convert($id,10,36));
        for($i=strlen($auth);$i<4;$i++){
            $auth = "0".$auth;
        }
        return $auth;
    }

    protected function setPasswordAttr($value)
    {
        return password_hash_tp($value);
    }

    /**
     * 修改密码
     */
    public function updatePassword($where, $password)
    {
        if(!$where) return false;
        return $this->where($where)->update(['password' => password_hash_tp($password)]);
    }

    public function getUserInfo($condition){
        $user = $this->field("id,openid,avatar,nickname,mobile,balance")->where($condition)->find();

        return $user;
    }

    public function parentUser()
    {
        return $this->hasOne('User','id','parent_id','parent_user','left')->setEagerlyType(0);
    }

    public function userProfile()
    {
        return $this->hasOne('UserProfile')->setEagerlyType(0);
    }
    
    /**返回用户信息*/
    public function userInfo($user_id)
    {
        return $this->where(['id'=>$user_id])->find();
    }

    /*一微信键获取手机号*/
    public function wxappInfo($params)
    {
        $result = [
            'code' => 1,
            'data' => [],
            'msg' => ''
        ];
        $iv = $params['iv'];
        $encryptedData = $params['encryptedData'];
        $code = $params['code'];
        $userId = $params['user_id'];
        if (!input("?param.code")) {
            $result['msg'] = 'iv参数缺失';
            return $result;
        }
        if (!$iv) {
            //加密的encryptedData数据，这是个加密的字符串
            $result['msg'] = '加密参数缺失';
            return json($result);
        }
        if (!$encryptedData) {
            //加密的encryptedData数据，这是个加密的字符串
            $result['msg'] = '加密参数缺失';
            return json($result);
        }
        if (!$code) {
            //加密的encryptedData数据，这是个加密的字符串
            $result['msg'] = 'code参数缺失';
            return json($result);
        }
        if(!$userId){
            $result['msg'] = 'userId不能为空';
            return json($result);
        }

        $res = model('Wechat')->jscode2session($code);
        if(!isset($res->openid)){
            return front_ajax_return('获取openid失败', 1);
        }

        $data = model('Wechat')->decryptData($res->session_key,$iv,$encryptedData);
        if ($data) {
            $userInfo = $this->where(["id" => $userId])->find();
            $userInfo->mobile = $data->phoneNumber;
            $userInfoSaveResult = $userInfo->save();
            if ($userInfoSaveResult) {
                $result['code'] = 0;
                $result['msg'] = '保存成功!';
                $result['data'] = $userInfo;
            } else {
                $result['msg'] = '保存失败!';

            }
        } else {
            $result['msg'] = '获取失败!';
        }
        return json($result);
    }

    // 提现
    public function tixian($money){
        $appid = "################";//商户账号appid
        $secret = "##########";//api密码
        $mch_id = "#######";//商户号
        $mch_no = "#######";
        $openid="oz5SI5ORc4VZ5Xk61vWbkbhpozkg";//授权用户openid

        $arr = array();
        $arr['mch_appid'] = $appid;
        $arr['mchid'] = $mch_id;
        $arr['nonce_str'] = ugv::randomid(20);//随机字符串，不长于32位
        $arr['partner_trade_no'] = '1298016501' . date("Ymd") . rand(10000, 90000) . rand(10000, 90000);//商户订单号
        $arr['openid'] = $openid;
        $arr['check_name'] = 'NO_CHECK';//是否验证用户真实姓名，这里不验证
        $arr['amount'] = $money;//付款金额，单位为分
        $desc = "###提现";
        $arr['desc'] = $desc;//描述信息
        $arr['spbill_create_ip'] = '192.168.0.1';//获取服务器的ip
        //封装的关于签名的算法
        $notify = new Notify_pub();
        $notify->weixin_app_config = array();
        $notify->weixin_app_config['KEY'] = $mch_no;

        $arr['sign'] = $notify->getSign($arr, $secret);//签名

        $var = $notify->arrayToXml($arr);
        $xml = $this->curl_post_ssl('https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers', $var, 30, array(), 1);
        $rdata = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $return_code = (string)$rdata->return_code;
        $result_code = (string)$rdata->result_code;
        $return_code = trim(strtoupper($return_code));
        $result_code = trim(strtoupper($result_code));

        if ($return_code == 'SUCCESS' && $result_code == 'SUCCESS') {
            $isrr = array(
                'con'=>'ok',
                'error' => 0,
            );
        } else {
            $returnmsg = (string)$rdata->return_msg;
            $isrr = array(
                'error' => 1,
                'errmsg' => $returnmsg,
            );

        }
        return json_encode($isrr);
    }
    //上个方法中用到的curl_post_ssl()
    public function curl_post_ssl($url, $vars, $second = 30, $aHeader = array())
    {
        $isdir = "/cert/";//证书位置

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);//设置执行最长秒数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');//证书类型
        curl_setopt($ch, CURLOPT_SSLCERT, $isdir . 'apiclient_cert.pem');//证书位置
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');//CURLOPT_SSLKEY中规定的私钥的加密类型
        curl_setopt($ch, CURLOPT_SSLKEY, $isdir . 'apiclient_key.pem');//证书位置
        curl_setopt($ch, CURLOPT_CAINFO, 'PEM');
        curl_setopt($ch, CURLOPT_CAINFO, $isdir . 'rootca.pem');
        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);//设置头部
        }
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);//全部数据使用HTTP协议中的"POST"操作来发送

        $data = curl_exec($ch);//执行回话
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }
    public function getSign ($data, $secret)
    {
        //将要发送的数据整理为$data
        ksort($data);//排序
        //使用URL键值对的格式（即key1=value1&key2=value2…）拼接成字符串
        $str='';
        foreach($data as $k=>$v) {
            $str.=$k.'='.$v.'&';
        }
        //拼接API密钥
        $str.='key='.$secret;
        $data['sign']=md5($str);//加密
    }
    //遍历数组方法
    public function arraytoxml($data){
        $str='<xml>';
        foreach($data as $k=>$v) {
            $str.='<'.$k.'>'.$v.'</'.$k.'>';
        }
        $str.='</xml>';
        return $str;
    }

    public function xmltoarray($xml) {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring),true);
        return $val;
    }
}
