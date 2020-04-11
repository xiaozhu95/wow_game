<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class JsapiPayLog extends Controller
{
	use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {

  }
  
  public function dd()
  {
      
  }

    public function pay()
  {
        
        $model = \think\Loader::model('PayLog');
        $subjects = model('Subject')->cache();
        $subject_id = $this->request->param('subject_id/d');
        $user_id = $this->request->param('user_id/d');
        $total_amount = $this->request->param('total_amount/d');
        $openId =  $this->request->param('open_id/s');
        if($subjects[$subject_id] && $user_id){
            
            $subject_name = $subjects[$subject_id]['name'];
            
            $pay_log = $model->where([
                    'subject_id' => $subject_id,
                    'subject_name' => $subject_name,
                    'user_id' => $user_id,
                    'total_amount' => $total_amount,
                    'third_party_payed' => 0,
                    // 'num' => $num,
                    'pc_id' => 0,
                    'type' => 5, //微信公众号支付
                    'status' => 0,
                    'wechat_openid' => $openId,
                    'create_time' => ['>',time()-3600]
                ])->find();

            if(!$pay_log)
                $pay_log = $model->create([
                        'subject_id' => $subject_id,
                        'subject_name' => $subject_name,
                        'user_id' => $user_id,
                        'total_amount' => $total_amount,
                        'wechat_openid' => $openId,
                        'third_party_payed' => 0,
                        'trade_no' =>"sdk".date("YmdHis"),
                        'pc_id' => 0,
                        'type' => 5, //微信公众号支付
                ]);

            return ajax_return($pay_log);
            
        }
       
        return ajax_return_adv_error('订单有误');
       
    }
    //余额充值
    public function rechargebalance()
    {
       
        $suc = Loader::model('PayLog')->save([
                    'wechat_openid' =>  $res['openid'],
                    'trade_no' => $res['transaction_id'],
                    'status'=>1
        ],['out_trade_no' => $res['out_trade_no'],'status'=>0]);
        
            
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
            $user = model('user')->where(['id' => $user_id])->find();
            $user->balance += $amount;
            $user->save();
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
  protected function aftergetList(&$data){
    
  }
}
