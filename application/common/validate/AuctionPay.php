<?php
namespace app\common\validate;

use think\Validate;
use app\common\model\ConfirmPayment;

class AuctionPay extends Validate
{
    protected $rule = [
        "team_id" => "require|integer",
        "equipment_id" => "require|integer",
        "confirm_payment_id" => "require|integer",
//        "confirm_status" => "require|integer",
        "equipment_name" => "require",
        "user_id" => "require|integer",
        "price" => "require|integer",
        "currency_type" => "require",
        "pay_type" => "require",
     
    ];
//    public function checkTeam($team_id, $default, $data)
//    {
//       $userInfo = model('team')->where(['id'=>$team_id])->find();
//       if (!$userInfo){
//            return '房间不存在';
//       }
//       return true;
//    }
//    
//    public function checkEquipment($equipment_id,$default,$data)
//    {
//        $equipment = model('auction_equipment')->where(['team_id'=>$data['team_id'],'equipment_id'=>$equipment_id])->find();
//        if (!$equipment){
//              return '该房间没这件拍卖的装备';
//        }
//        return true;
//    }
//    public function checkConfirmPayment($confirm_payment_id,$default,$data)
//    {
//       $result = model('confirm_payment')->where(['auction_log_id'=>$confirm_payment_id,'status'=> ConfirmPayment::STATUS_ON])->find();
//    }
}
