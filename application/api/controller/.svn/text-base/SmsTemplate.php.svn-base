<?php
namespace app\api\controller;

use app\api\Controller;
class SmsTemplate extends Controller
{
    public function getlist(){
        $sms_config = model('SmsConfig')->cache()[0];
        $sms_tpls = [];
        if($sms_config){
            $sms_template = model('SmsTemplate')->cache();
            $sms_tpls = $sms_template[$sms_config['type']];

            foreach($sms_tpls as $k => $v){
                preg_match_all("/\\$\{(.*)\}/U", $v['content'], $out);
                $sms_tpls[$k]['fields'] = $out[1];
            }
        }
        array_unshift($sms_tpls, ['id'=>0,'name'=>'请选择','content'=>'']);
        return json($sms_tpls);
    }
	
}
