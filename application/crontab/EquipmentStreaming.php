<?php

namespace app\crontab;
use app\common\model\AuctionEquipment;
use app\common\model\ConfirmPayment;


class EquipmentStreaming {

    // 修改用户的装备支付状态
    public function worker ()
    {
        $model = model('auction_equipment');
        $type = [AuctionEquipment::TYPE_IN_TRANSACTION,AuctionEquipment::TYPE_OF_CREATE];
        $list = $model->where('type','in',$type)->order("create_time desc")->select();

        foreach ($list as $key => $value) {
            $time = time();
            // 查看该商品的购买纪录，如果没有拍卖纪录直接流拍
            $finishTime = strtotime($value->create_time) + $value->finsih_after_time * 60;
            if ($time > $finishTime)  {
                $first_time = ($value->end_time + $value->pay_after_time * 60);    // pay_after_time 两次支付的时间间隔
                $second_time = ($value->end_time + $value->pay_after_time * 2 * 60);    // pay_after_time 两次支付的时间间隔
                // 拍卖结束时间后的支付时间
                // 让价格最高的人支付
                $twoPrice = $this->actionTwoPrice($value->id);
                if($value['end_time']<$time && $time<$first_time){
                    if (!empty($twoPrice)) {
                        $confirmModel = new ConfirmPayment();
                        $confirmModel->where(['auction_equipment_id'=>$value['id']])->update(['status'=>ConfirmPayment::STATUS_OFF]);
                        $confirmModel->save([
                            'auction_equipment_id' => $twoPrice[0]['auction_equipment_id'],
                            'auction_log_id' => $twoPrice[0]['id'],
                            'status' => ConfirmPayment::STATUS_ON,
                            'user_id' => $twoPrice[0]['user_id'],
                            'pay_end_time' => $first_time,
                        ]);
                        $equipment = $model->where(['id'=>$value['id']])->find();
                        $equipment->type = AuctionEquipment::TYPE_OF_CREATE; //我的交易
                        $equipment->user_id = $twoPrice[0]['user_id'];
                        $equipment->save();
                    } else {    // 流拍
                        $equipment = $model->where(['id'=>$value->id])->find();
                        $equipment->type = AuctionEquipment::TYPE_STREAM_SHOT; //流拍
                        $equipment->user_id = 0;
                        $equipment->save();
                    }
                } elseif ($first_time<$time && $time < $second_time && $value['type'] == AuctionEquipment::TYPE_OF_CREATE) {    // 让价格第二的人支付
                    if (isset($twoPrice[1]) && !empty($twoPrice[1])) {
                        $confirmModel =  model("confirm_payment");
                        $confirmModel->where(['auction_equipment_id'=>$value['id']])->update(['status'=>ConfirmPayment::STATUS_OFF]);
                        $confirmModel->save([
                            'auction_equipment_id' => $twoPrice[1]['auction_equipment_id'],
                            'auction_log_id' => $twoPrice[1]['id'],
                            'status' => ConfirmPayment::STATUS_ON,
                            'user_id' => $twoPrice[1]['user_id'],
                            'pay_end_time' => $value['pay_end_time'],
                        ]);
                        $equipment = $model->where(['id'=>$value['id']])->find();
                        $equipment->user_id = $twoPrice[1]['user_id'];
                        $equipment->save();
                    } else {    // 流拍
                        $equipment = $model->where(['id'=>$value['id']])->find();
                        $equipment->type = AuctionEquipment::TYPE_STREAM_SHOT; //流拍
                        $equipment->user_id = 0;
                        $equipment->save();
                    }
                } else {
                    $equipment = $model->where(['id'=>$value['id']])->find();
                    $equipment->type = AuctionEquipment::TYPE_STREAM_SHOT; //流拍
                    $equipment->user_id = 0;
                    $equipment->save();
                }
            }
        }
    }









    /**定时任务 装备流拍*/
    public function worker1()
    {
        $model = model('auction_equipment');

        $type = [AuctionEquipment::TYPE_IN_TRANSACTION,AuctionEquipment::TYPE_OF_CREATE];
        $list = $model->where('type','in',$type)->select();
        $time = time();
        foreach ($list as $key => $value) {
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

    // 获取该物品最高和第二价格的拍卖纪录
    private function actionTwoPrice($auction_equipment_id)
    {
        $sqlStr = 'SELECT id,user_id,price,auction_equipment_id from wow_auction_log where auction_equipment_id = '.$auction_equipment_id.' ORDER by price desc LIMIT 0,2';
        $list = \think\Db::query($sqlStr);

        return $list;

    }

    // 获取该物品最高价格的拍卖纪录
    private function auctionMaxPrice($auction_equipment_id)
    {
        //  $model = model('auction_log');
        $list = \think\Db::query('SELECT id,user_id,price,auction_equipment_id from wow_auction_log where auction_equipment_id = '.$auction_equipment_id.' ORDER by price desc LIMIT 0,1');

        return isset($list[0]) ? $list[0] : [];

    }

    // 获取该物品第二高价格的拍卖纪录
    private function actionTwoPrice1($auction_equipment_id)
    {
        $model = model('auction_log');
        $list = $model->where(['auction_equipment_id'=>$auction_equipment_id])->order('price desc')->limit(2)->select();
        $list =  $list->toArray();
        $list = isset($list[1]) ? $list[1]:[];

        return $list;
    }
}
