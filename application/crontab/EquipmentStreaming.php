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
        //获取 0拍卖中  3我的交易 5 转第二次拍卖
        $type = [AuctionEquipment::TYPE_IN_TRANSACTION,AuctionEquipment::TYPE_OF_CREATE,AuctionEquipment::TYPE_OF_TOWAUCTION];
        $list = $model->where('type','in',$type)->select();
        $time = time();
        foreach ($list as $key => $value) {

            $data = [];


            $end_time = $value['end_time']; //获取拍卖结束时间
            /**
             * 第一次支付结束时间 = 竞拍结束  + 支付的间隔时间（分钟计算） * 60 （秒） * （推送的次数（默认零次）
             * 第二次支付结束时间 = 竞拍结束  + 支付的间隔时间（分钟计算） * 60 （秒） * （推送的次数（1）
             */
            $pay_end  = $end_time + $value->pay_after_time * 60 * ($value['push_num']) ;
            // 第一轮 推送三次
            if($pay_end < $time && $value->push_times == 3){
                //获取拍卖前三的记录
               $result = $this->actionThreePrice($value['id']);
               if(!$result){ //第一轮未有人参与竞拍
                   $data =[
                        'type' => AuctionEquipment::TYPE_STREAM_SHOT,
                        'user_id' => 0
                   ];
               }
               //果然push_num = 0 表示推送第一次 果然push_num = 1 表示推送第二次

               if (isset($result[$value['push_num']]) && $result[$value['push_num']]){
                   //筛选第二次和第三次
                   if ($value['push_num'] > 0){
                       //找到第上一次
                      $auction_log = $result[$value['push_num'] - 1];
                      $team_info = model('team')->field('room_id')->where(['id'=>$auction_log['team_id']])->find();
                      $room_info = model('room')->field('id,room_num')->where(['id'=>$team_info['room_id']])->find();

                      //添加征信 日志
                      $credit_result = model('Credit')->auctionadd($auction_log,$room_info);
                      //给用户征信加 +1
                      $user_info = model('user')->where(['id'=>$auction_log['user_id']])->find();
                      $user_info->credit_num +=1;
                      $user_info_result = $user_info->save();

                      //本次

                   }

               }

            }



//            if($value['end_time']<$time && $time<$first_time){  # pay_after_time 两次支付的时间间隔
//
//                /**定时任务 拍卖结束后显示到我的交易  */
//                $auction_log = $this->auctionMaxPrice($value['id']);
//
//                if($auction_log && ($value['type'] == AuctionEquipment::TYPE_IN_TRANSACTION)){
//                    $confirmModel = new ConfirmPayment();
//
//                    $confirmModel->where(['auction_equipment_id'=>$value['id']])->update(['status'=>ConfirmPayment::STATUS_OFF]);
//                    $confirmModel->save([
//                        'auction_equipment_id' => $auction_log->auction_equipment_id,
//                        'auction_log_id' => $auction_log->id,
//                        'status' => ConfirmPayment::STATUS_ON,
//                        'user_id' => $auction_log->user_id,
//                        'pay_end_time' => $first_time,
//                    ]);
//                    $equipment = $model->where(['id'=>$value['id']])->find();
//                    $equipment->type = AuctionEquipment::TYPE_OF_CREATE; //我的交易
//                    $equipment->user_id = $auction_log->user_id;
//                    $equipment->save();
//                }
//            }
//            //推送给第二个人支付
//            if($first_time<$time && $time < $value['pay_end_time'] && $value['type'] == AuctionEquipment::TYPE_OF_CREATE){
//                $auction_log = $this->actionTwoPrice($value['id']);
//                if($auction_log && ($value['type'] == AuctionEquipment::TYPE_OF_CREATE)){
//                    $confirmModel =  model("confirm_payment");
//                    $confirmModel->where(['auction_equipment_id'=>$value['id']])->update(['status'=>ConfirmPayment::STATUS_OFF]);
//                    $confirmModel->save([
//                        'auction_equipment_id' => $auction_log->auction_equipment_id,
//                        'auction_log_id' => $auction_log->id,
//                        'status' => ConfirmPayment::STATUS_ON,
//                        'user_id' => $auction_log->user_id,
//                        'pay_end_time' => $value['pay_end_time'],
//                    ]);
//                    $equipment = $model->where(['id'=>$value['id']])->find();
//                    $equipment->user_id = $auction_log->user_id;
//                    $equipment->save();
//                }
//            }
//            //如果没有拍卖纪录立即流拍
//            if($value['end_time']<$time  && $value['pay_end_time'] > $time){
//                $equipment = $model->where(['id'=>$value['id']])->find();
//                $auction_log = model('auction_log')->where(['auction_equipment_id'=>$value['id']])->find();
//                if(!$auction_log){
//                    $equipment->type = AuctionEquipment::TYPE_STREAM_SHOT; //流拍
//                    $equipment->user_id = 0;
//                    $equipment->save();
//                }
//            }
//            //时间到期后没有支付算流拍
//            if($value['pay_end_time']<$time){
//                $equipment = $model->where(['id'=>$value['id']])->find();
//                $equipment->type = AuctionEquipment::TYPE_STREAM_SHOT; //流拍
//                $equipment->user_id = 0;
//                $equipment->save();
//            }
        }
    }

    /**
     * @param int $action_equipment_id  活动流拍的ID;
     * @return int  返回是否流拍成功
     */
    private function actionLiuPai($action_equipment_id = 0)
    {
        $model = model('auction_equipment');
        $equipment = $model->where(['id'=>$action_equipment_id])->find();
        $equipment->type = AuctionEquipment::TYPE_STREAM_SHOT; //流拍
        $equipment->user_id = 0;
        $result = $equipment->save();
        return $result;
    }

    //获取该物品拍卖日志前三
    private function actionThreePrice($auction_equipment_id)
    {
        $model = model('auction_log');
        $list = $model->where(['auction_equipment_id'=>$auction_equipment_id])->order('price desc')->limit(3)->select();

        return $list;
    }

    //获取该物品拍卖日志前五
    private function actionFivePrice($auction_equipment_id)
    {
        $model = model('auction_log');
        $list = $model->where(['auction_equipment_id'=>$auction_equipment_id])->order('price desc')->limit(5)->select();

        return $list;
    }

}
