<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
use think\Config;
use think\Cache;

class UserMoneyLog extends Controller
{
    use \app\api\traits\controller\Controller;

    protected function filter(&$map)
    {
		$uid = $this->request->param("user_id");
		$sign = $this->request->param("sign");
		if($uid && $sign && password_hash_tp($uid)==$sign){
            // $map['_relation'] = "user";
            $map['_field'] = [
                "amount,msg,create_time"
            ];
            $map['type'] = $this->request->param('type',0);
            $map['user_id'] = $uid;
            $map['status'] = 1;
			if($this->request->has("tab")){
				if($this->request->post("tab")==1)
					$map['amount'] = ['>',0];
				elseif($this->request->post("tab")==2)
					$map['amount'] = ['<',0];
			}
		}else
			$map['id'] = 0;
    }

    public function integral($subject_id,$user_id,$pay_log_id){
        if($this->request->controller() != 'PayLog') return;

        $subjects = model('Subject')->cache();
        $subject = $subjects[$subject_id];
        if($subject['extra'] && $subject['extra']['integral']){
            $amount = $subject['extra']['integral'];
        }else{
            $amount = model('PayLog')->where(['id'=>$pay_log_id])->value('total_amount') * 100;
        }
        if($amount){
            $this->log($user_id, $amount, 1);
        }
    }

    public function money($subject_id,$user_id,$pay_log_id){
        if($this->request->controller() != 'PayLog') return;

        $subjects = model('Subject')->cache();
        $subject = $subjects[$subject_id];
        if($subject['extra'] && $subject['extra']['money']){
            $amount = $subject['extra']['money'];
        }else{
            $amount = model('PayLog')->where(['id'=>$pay_log_id])->value('total_amount');
        }
        if($amount){
            $this->log($user_id, $amount);
        }
    }

    private function log($user_id,$amount,$type=0){
        model('UserMoneyLog')->data([
            'user_id' => $user_id,
            'amount' => $amount,
            'type' => $type,
            'msg' => '购买',
            'controller' => 'pay_log',
            'action' => 'buy'
        ])->save();
    }
}
