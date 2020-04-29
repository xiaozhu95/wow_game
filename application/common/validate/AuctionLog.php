<?php
/**
 * tpAdmin [a web admin based ThinkPHP5]
 *
 * @author yuan1994 <tianpian0805@gmail.com>
 * @link http://tpadmin.yuan1994.com/
 * @copyright 2016 yuan1994 all rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

//------------------------
// 分组管理验证器
//-------------------------

namespace app\common\validate;

use think\Validate;
use think\Db;

class AuctionLog extends Validate
{
    protected $rule = [
        "team_id"  => "require|integer",
        "equipment_id"  => "require|integer",
        "equipment_name"  => "require",
        "user_id"  => "require|integer",
        "price"  => "require|number|morePrice:0",
        "currency_type"  => "integer",
        "auction_equipment_id"  => "require|integer",
        "role_id"  => "require|integer",
        "role_name"  => "require",
    ];

    protected $message = [
        'team_id.require' => '团ID必填',
        'team_id.integer' => '团ID数字',
        'equipment_id.require' => '装备ID必填',
        'equipment_id.integer' => '装备ID数字',
        'equipment_name.require' => '装备名称必填',
        'user_id.require' => '用户id必填',
        'user_id.integer' => '用户id数字',
        'price.require' => '价格必填',
        'price.number' => '价格数字',
        'currency_type.integer' => '拍卖的币种',
        'auction_equipment_id.require' => '竞拍ID必填',
        'auction_equipment_id.integer' => '竞拍ID数字',
        'role_id.require' => '角色ID必填',
        'role_id.integer' => '角色ID数字',
        'role_name.require' => '角色ID必填',
    ];



    /**验证同一装备出价必须必上一个高*/
    protected function morePrice($price, $minPrice, $data)
    {
        $auction_equipment =  model('auction_equipment')->where(['id'=>$data['auction_equipment_id']])->find();
        if(!$auction_equipment){
            return '竞拍id不存在,无法拍卖';
        }
        if($auction_equipment['end_time']< time()){
            return '竞拍活动已结束';
        }
        $user_info = model('user')->where(['id'=>$data['user_id']])->find();
        if(!$user_info){
            return '用户不存在';
        }
        if ($auction_equipment->currency_type == \app\common\model\AuctionEquipment::CURRENCY_TYPE_MONEY) {
            //判断征信
            if($user_info->credit_num>=2 && $user_info->balance == 0){
                return '您的信用低,无法参与竞拍,请充值后在参与竞拍';
            }
            //相同的竞拍活动 找出冻结的余额
            $credit = model('credit')->field('value')->where(['auction_equipment_id'=>$data['auction_equipment_id'],'is_delete'=>0 ,'type'=>\app\common\model\Credit::TYPE_FREEZE])->find();
            //用户可用的总余额
            $user_price = $user_info->balance;
            if ($credit){
                $user_price += $credit->value;
            }
            if($user_info->credit_num>=2 && $user_price < $price){
                return '您的信用低,你充值的余额不足以支付你本轮竞价,请充值';
            }
            //同一件装备不允许再次竞拍
            $credit = model('credit')->field('id')->where(['equipment_id'=>$data['equipment_id'],'team_id'=>$data['team_id'],'is_delete'=>0,'type'=>\app\common\model\Credit::TYPE_AUCTION])->find();
            if ($user_info->credit_num>=2 && $credit){
                return '由于你在本次活动中该装备未支付，无法参加竞拍';
            }
        }
        $where["team_id"] = $data['team_id'];
        $where['equipment_id'] = $data['equipment_id'];
        $where['auction_equipment_id'] = $data['auction_equipment_id'];
        // $list = Db::name("AuctionLog")->field('max(price) as price')->where($where)->find();
        $list = Db::name("AuctionLog")->field('user_id,price')->where($where)->order('price desc')->find();

        $auction_price = 0;
        if(!$list){
            $list['price'] = 0;
            $list['user_id'] = 0;
        }
        if($list['user_id'] == $data['user_id']){
            return '你已是出价最高';
        }
        if(!$list['price']){
            $list['price'] = $auction_equipment['price']; //装起拍价格
            $auction_price = $list['price'];
        }else{
            $auction_price = $list['price'] + $auction_equipment['add_price']; //有人参加竞拍+加上步长
        }
        $baifenbi = 1;
        //每次最高出价不得高于100%
        if(($auction_price+$auction_price * $baifenbi)<$price)
        {
            return '每次出价范围('.$auction_price.'~'. ($auction_price+$auction_price * $baifenbi) .')';
        }



        if($auction_price>$price){
            $currency_type = "";
            if($auction_equipment['currency_type'] == 1){
                $currency_type = '金币';
            }
            if($auction_equipment['currency_type'] == 2){
                $currency_type = '元';
            }
            return '每次出价范围('.$auction_price.'~'. ($auction_price+$auction_price * $baifenbi) .')';
        }
        return true;
    }


}