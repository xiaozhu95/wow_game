<?php

namespace app\crontab;
use app\common\model\AuctionEquipment;

class EquipmentStreaming {
    
    /**定时任务 装备流拍*/
    public function worker()
    {
        $model = model('auction_equipment');
        $list = $model->where(['type'=> AuctionEquipment::TYPE_IN_TRANSACTION])->select();
        
        foreach ($list as $key => $value) {
            $time = time();
            if($value['pay_end_time'] < $time){
                $equipment = $model->where(['id'=>$value['id']])->find();
                $equipment->type = AuctionEquipment::TYPE_STREAM_SHOT; //流拍
                $equipment->save();
            }
        }
    }
    
}
