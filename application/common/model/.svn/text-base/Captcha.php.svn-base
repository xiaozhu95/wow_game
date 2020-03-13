<?php
namespace app\common\model;

use think\Model;

class Captcha extends Model
{
    // 指定表名,不含前缀
    protected $name = 'captcha';
    protected $type = [
        'expire_time'  =>  'timestamp',
    ];

    public function check($type,$tel,$captcha){

        if(!$tel || !$captcha) return;
        $expire_time = $this->where(['tel'=>$tel,'type'=>$type,'code'=>$captcha])->order('id desc')->value('expire_time');
		if(!$expire_time)
			return "验证码输入有误";
		elseif($expire_time<time())
            return "验证码已过期，请重新获取";
        else
            $this->where(['type'=>$type,'tel'=>$tel])->delete();
    }

    public function send($data){
        $sms_config = model('SmsConfig')->cache()[0];
        if(!$sms_config)
            return '未设置短信接口参数';
        $sms_template = model('SmsTemplate')->cache();
        $sms_tpl = $sms_template[$sms_config['type']][$data['type']];
        if(!$sms_tpl)
            return 'invalid request';

        $data['sign_name'] = $sms_config['sign_name'];
        $data['app_key'] = $sms_config['app_key'];
        $data['app_secret'] = $sms_config['app_secret'];
        $data['template_code'] = $sms_tpl['template_code'];
        return model($sms_config['type'])->sendSms($data);
    }

    public function sendBatch($data){
        $sms_config = model('SmsConfig')->cache()[0];
        if(!$sms_config)
            return '未设置短信接口参数';
        $sms_template = model('SmsTemplate')->cache();
        foreach($sms_template[$sms_config['type']] as $v){
            if($v['id'] == $data['template_id'])
                $sms_tpl = $v;
        }
        if(!$sms_tpl)
            return 'invalid request';

        foreach($data['tels'] as $v){
            $data['sign_names'][] = $sms_config['sign_name'];
            $data['params'][] = $data['fields'];
        }
        $data['app_key'] = $sms_config['app_key'];
        $data['app_secret'] = $sms_config['app_secret'];
        $data['template_code'] = $sms_tpl['template_code'];
        return model($sms_config['type'])->sendBatchSms($data);
    }
}
