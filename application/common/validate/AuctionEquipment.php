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

class AuctionEquipment extends Validate
{
    protected $rule = [
        "team_id"  => "require|integer",
        "boss_id"  => "require|integer",
        "equipment_id"  => "require|integer",
        "equipment_name"  => "require",
        "currency_type"  => "require|integer",
        "price"  => "require|number",
        "end_time"  => "require|number",
        "pay_end_time"  => "require|number",
    ];
    protected $message = [
        'team_id.require' => '房间ID必填',
        'team_id.integer' => '请选择房间ID',
        'boss_id.require' => 'Boss必填',
        'boss_id.integer' => 'bossID',
        'equipment_id.require' => '装备必填',
        'equipment_id.integer' => '装备数字',
        'currency_type.require' => '币种必填',
        'currency_type.integer' => '币种数字',
        'price.require' => '起拍价格必填',
        'price.number' => '起拍价格数字',
        'end_time.require' => '结束时间必填',
        'end_time.number' => '结束时间数字',
        'pay_end_time.require' => '支付时间必填',
        'pay_end_time.number' => '支付时间数字',
    ];
}