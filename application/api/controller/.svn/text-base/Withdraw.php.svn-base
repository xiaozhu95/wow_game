<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
use think\cache\driver\Redis;
use think\Loader;

class Withdraw extends Controller
{
    use \app\api\traits\controller\Controller;

    protected function filter(&$map)
    {
		$uid = $this->request->post("user_id");
		$sign = $this->request->post("sign");
		if($uid && $sign && password_hash_tp($uid)==$sign){
			$map['user_id'] = $uid;
			$map['_field'] = "amount,from_unixtime(create_time) as create_time,IF(isdelete=1,'已失效',IF(status=1,'已打款','待处理')) as status_text,status";
			if($this->request->has("status")){
				$status = $this->request->post("status/d");
				if(in_array($status,[0,1]))
					$map['status'] = $status;
				else
					$map['status'] = ''; //覆盖post中的status
			}
		}else
			$map['user_id'] = 0;
	}
	
	public function add(){
		$uid = $this->request->post("user_id");
		$sign = $this->request->post("sign");
		if($uid && $sign && password_hash_tp($uid)==$sign){
			$params = $this->request->post();
			if($params['amount']<=0) 
        return front_ajax_return("金额输入有误");
      
      // $redis = new Redis();
      // $redis = $redis->getHandler();
      // $key_exists = 'withdraw_'.$uid;
      // if($redis->decr($key_exists) === -1){
        $user = Loader::model('User')->where(["id"=>$uid])->find();
        if($user['money']>=$params['amount']){
          Loader::model('Withdraw')->save([
            'user_id' => $user['id'],
            'amount' => $params['amount']
          ]);
          // $redis->delete($key_exists);
          return front_ajax_return("提现申请已提交",1,['money'=>number_format($user['money']-$params['amount'], 2)]);
        }else{
          // $redis->delete($key_exists);
          return front_ajax_return("金额输入有误，账户余额不足",0,$user['money']);
        }
      // }else
      //   return front_ajax_return("当前账户正在其他设备上提现，请稍后再试");
		}else
			return front_ajax_return("提现失败");
	}
}
