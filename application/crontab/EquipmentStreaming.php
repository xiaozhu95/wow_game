<?php

namespace app\crontab;
use app\common\model\AuctionEquipment;
use app\common\model\ConfirmPayment;


class EquipmentStreaming {
    
    /**定时任务 装备流拍*/
    public function worker()
    {
        $model = model('auction_equipment');
        
        $type = [AuctionEquipment::TYPE_IN_TRANSACTION,AuctionEquipment::TYPE_OF_CREATE];
        $list = $model->where('type','in',$type)->select();
        foreach ($list as $key => $value) {
           
            $time = time();
            $first_time = ($value['end_time'] + $value['pay_after_time'] * 60);
           
            if($value['end_time']<$time && $time<$first_time){  # pay_after_time 两次支付的时间间隔
               
                /**定时任务 拍卖结束后显示到我的交易  */
                $auction_log = $this->auctionMaxPrice($value['id']);
                
                if($auction_log && ($value['type'] == AuctionEquipment::TYPE_IN_TRANSACTION)){
                    $confirmModel = new ConfirmPayment();
                 
                    $confirmModel->where(['auction_equipment_id'=>$value['id']])->update(['status'=>ConfirmPayment::STATUS_OFF]);
                    $confirmModel->save([
                        'auction_equipment_id' => $auction_log->auction_equipment_id,
                        'auction_log_id' => $auction_log->id,
                        'status' => ConfirmPayment::STATUS_ON,
                        'user_id' => $auction_log->user_id,
                        'pay_end_time' => $first_time,
                    ]);
                    $equipment = $model->where(['id'=>$value['id']])->find();
                    $equipment->type = AuctionEquipment::TYPE_OF_CREATE; //我的交易
                    $equipment->user_id = $auction_log->user_id;
                    $equipment->save();
                    
                }
            }
            
            //推送给第二个人支付
            if($first_time<$time && $time < $value['pay_end_time'] && $value['type'] == AuctionEquipment::TYPE_OF_CREATE){
                
                
                $auction_log = $this->actionTwoPrice($value['id']);
                if($auction_log && ($value['type'] == AuctionEquipment::TYPE_OF_CREATE)){
                    $confirmModel =  model("confirm_payment");
                    $confirmModel->where(['auction_equipment_id'=>$value['id']])->update(['status'=>ConfirmPayment::STATUS_OFF]);
                    $confirmModel->save([
                        'auction_equipment_id' => $auction_log->auction_equipment_id,
                        'auction_log_id' => $auction_log->id,
                        'status' => ConfirmPayment::STATUS_ON,
                        'user_id' => $auction_log->user_id,
                        'pay_end_time' => $value['pay_end_time'],
                    ]);
                    $equipment = $model->where(['id'=>$value['id']])->find();
                    $equipment->user_id = $auction_log->user_id;
                    $equipment->save();
                }
            }
            //如果没有拍卖纪录立即流拍
            if($value['end_time']<$time  && $value['pay_end_time'] > $time){
                $equipment = $model->where(['id'=>$value['id']])->find();
                $auction_log = model('auction_log')->where(['auction_equipment_id'=>$value['id']])->find();
                if(!$auction_log){
                    $equipment->type = AuctionEquipment::TYPE_STREAM_SHOT; //流拍
                    $equipment->user_id = 0;
                    $equipment->save();
                }
            }
            //时间到期后没有支付算流拍
            if($value['pay_end_time']<$time){
                $equipment = $model->where(['id'=>$value['id']])->find();
                $equipment->type = AuctionEquipment::TYPE_STREAM_SHOT; //流拍
                $equipment->user_id = 0;
                $equipment->save();
            }
        }
    }
    
    private function auctionMaxPrice($auction_equipment_id)
    {
       
       
      //  $model = model('auction_log');
       $list = \think\Db::query('SELECT id,user_id,price,auction_equipment_id from wow_auction_log where auction_equipment_id = '.$auction_equipment_id.' ORDER by price desc LIMIT 0,1');
      	
        return isset($list[0]) ? $list[0] : []; 
      
    }
    private function actionTwoPrice($auction_equipment_id)
    {
        $model = model('auction_log');
        $list = $model->where(['auction_equipment_id'=>$auction_equipment_id])->order('price desc')->limit(2)->select(); 
        $list =  $list->toArray();
        $list = isset($list[1]) ? $list[1]:[];
     
        return $list;
    }
}
