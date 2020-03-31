<?php
namespace app\common\model;

use think\Model;
use think\Cache;
use app\common\model\AuctionEquipment;

class ConfirmPayment extends Model
{
    // 指定表名,不含前缀
    protected $name = 'confirm_payment';
    
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    
    const STATUS_ON = 0;
    const STATUS_OFF = 1;
    
    /**交易 显示 ,待审核显示*/
    public function confirm($data)
    {
        $team_info = model('team')->where(['id'=>$data['team_id']])->find();
        if(!$team_info){
             return [];
        }
        $confirm_payment_list = model('confirm_payment')->alias('payment')
            ->join('auction_equipment e','payment.auction_equipment_id = e.id','left')
            ->join('auction_log log','payment.auction_log_id = log.id','left')
            ->field('e.id,e.team_id,e.boss_id,e.type,e.user_id,e.equipment_id,e.equipment_name,e.currency_type,log.price,e.finsih_after_time,e.pay_after_time,e.end_time,e.pay_end_time,payment.id as confirm_payment_id,log.id as auction_log_id')
            ->where('payment.user_id = e.user_id')
            ->where('e.type','in',[AuctionEquipment::TYPE_OF_CREATE])
            ->where(['payment.status'=> self::STATUS_ON])
            ->where(['e.team_id'=>$data['team_id']]); 
            
       
    	
        //检验是否团长
        $team = model('team')->teamCheck(['id'=>$data['team_id'],'user_id'=>$data['user_id']]);
        $is_team = 1;
       
        if(!$team){
            $is_team = 0;
           $confirm_payment_list = $confirm_payment_list ->where(['e.user_id'=>$data['user_id']]);
         
          
        }
      	
       $confirm_payment_list = $confirm_payment_list->select();
       $confirm_payment_list_pay = model('confirm_payment')->alias('payment')
            ->join('auction_equipment e','payment.auction_equipment_id = e.id','left')
            ->join('auction_log log','payment.auction_log_id = log.id','left')
            ->join('auction_pay pay','payment.id = pay.confirm_payment_id','left')
            ->field('e.id,e.team_id,e.boss_id,e.type,e.user_id,e.equipment_id,e.equipment_name,e.currency_type,log.price,e.finsih_after_time,e.pay_after_time,e.end_time,e.pay_end_time,payment.id as confirm_payment_id,log.id as auction_log_id,pay.confirm_status')
            ->where('payment.user_id = e.user_id')
            ->where('pay.pay_type','in', AuctionPay::PAY_TYPE_YES)
            ->where(['e.team_id'=>$data['team_id']]);  
      
        if(!$team){
            $is_team = 0;
           $confirm_payment_list_pay = $confirm_payment_list_pay ->where(['e.user_id'=>$data['user_id']]);
        }
        if(!$confirm_payment_list) return $confirm_payment_list;
         $confirm_payment_list_pay = $confirm_payment_list_pay->select(); 

        $role = model('role');
        $role_info =  $role->where(['id'=>$team_info['role_id']])->find();
        $confirm_payment_list = $confirm_payment_list->toArray();
      	
        if($confirm_payment_list_pay){
            $confirm_payment_list_pay = $confirm_payment_list_pay->toArray();
            foreach ($confirm_payment_list_pay as $list_pay_key => $list_pay_value) {
               
                	array_unshift($confirm_payment_list, $list_pay_value);
             
            }
            
        }
        
      	$user_ids = array_column($confirm_payment_list, 'user_id');
      	$user_ids = array_unique($user_ids);
              //查询用户
        $user = model('user')->field('id,nickname,avatar')->where('id','in',$user_ids)->select();
        $user = array_columns($user, "nickname,avatar", "id");
      	$user_info = $role->arrayList(['service_id'=>$role_info['service_id'],'camp_id'=>$role_info['camp_id']],$user_ids);
        $equipment_ids = array_column($confirm_payment_list, "equipment_id");
        $equipment_ids = array_unique($equipment_ids);
        $equipment_result = model('boss_arms')->arrayList($equipment_ids);
      	$auction_equipment_ids = array_column($confirm_payment_list, "id");
  
		$auction_info = model('confirm_payment')->field('auction_equipment_id,count(auction_equipment_id) as number')->where('auction_equipment_id','in',$auction_equipment_ids)->group('auction_equipment_id')->select()->toArray();
      	$auction_info = array_column($auction_info, "number",'auction_equipment_id');
    	
        $confirm_payment_ids = array_column($confirm_payment_list, 'confirm_payment_id');
      	$confirm_payment_ids = array_unique($confirm_payment_ids);
    	$auction_pay = model('AuctionPay')->checkReview($user_ids,$confirm_payment_ids);

    	$times = time();
        foreach ($confirm_payment_list as $key => $value) {
          	
          	$total = $auction_info[$value['id']];
          	$total = 2 - $total; //两次支付时间
            if($total<0){
              	$total = 0;
            }
            //pay_end_time 是最后的支付时间（这个是两次支付的时间）
            $end_after_pay = $value['pay_end_time'] - ($total * 60 * $value['pay_after_time'] + $times) ;
          	if($end_after_pay<0){
              	$end_after_pay = 0;
            }
          	
            $confirm_payment_list[$key]['user'] = isset($user[$value['user_id']]) ? $user[$value['user_id']]   : [];
          	if( $confirm_payment_list[$key]['user']){
              	 $confirm_payment_list[$key]['user']['nickname'] = $user_info[$value['user_id']]['role_name'];
            }
          	$is_team_log = 0;
            if(isset($team['user_id']) && $team['user_id'] == $value['user_id'] ){
                 $is_team_log = 1;
            }
          
            $confirm_payment_list[$key]['order_id'] = isset($auction_pay[$value['confirm_payment_id']]['id']) ? $auction_pay[$value['confirm_payment_id']]['id']:0;
          	$confirm_payment_list[$key]['end_after_pay'] =$end_after_pay;
            $confirm_payment_list[$key]['is_team'] = $is_team_log;
            $confirm_payment_list[$key]['equipment_icon'] = isset($equipment_result[$value['equipment_id']]['icon']) ? $equipment_result[$value['equipment_id']]['icon'] : "" ;
            $confirm_payment_list[$key]['equipment_grade'] = isset($equipment_result[$value['equipment_id']]['grade']) ? $equipment_result[$value['equipment_id']]['grade'] : "" ;
            $confirm_payment_list[$key]['equipment_type'] = isset($equipment_result[$value['equipment_id']]['type']) ? $equipment_result[$value['equipment_id']]['type'] : "" ;
            if($is_team && $is_team_log == 0 && $value['currency_type'] == AuctionEquipment::CURRENCY_TYPE_MONEY){
                unset($confirm_payment_list[$key]);
            }
        }
  		$confirm_payment_list=array_values($confirm_payment_list);
        $data['is_team'] = $is_team;
      	$data['data'] = $confirm_payment_list;
        return $data;
          
    }
    
}
