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
        "price"  => "number|morePrice:0",
        "currency_type"  => "integer",
        "auction_equipment_id"  => "require|integer",
    ];
    
    protected $message = [
        'team_id.require' => '团ID必填',
        'team_id.integer' => '团ID数字',
        'equipment_id.require' => '装备ID必填',
        'equipment_id.integer' => '装备ID数字',
        'equipment_name.require' => '装备名称必填',
        'user_id.require' => '用户id必填',
        'user_id.integer' => '用户id数字',
        'price.number' => '价格数字',
        'currency_type.integer' => '拍卖的币种',
        'auction_equipment_id.require' => '竞拍ID必填',
        'auction_equipment_id.integer' => '竞拍ID数字',
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
        $where["team_id"] = $data['team_id'];
        $where['equipment_id'] = $data['equipment_id'];
        $list = Db::name("AuctionLog")->field('max(price) as price')->where($where)->find();
        if(!$list['price']){
            $list['price'] = $minPrice;
        }
        if(($list['price'] + $auction_equipment['price'])>$price){
            $currency_type = "";
            if($auction_equipment['currency_type'] == 1){
                $currency_type = '金币';
            }
            if($auction_equipment['currency_type'] == 2){
                $currency_type = '元';
            }
            return '竞拍价格低于上次出价格'.$auction_equipment['price'].$currency_type;
        }
        return true;
    }
     
     
}