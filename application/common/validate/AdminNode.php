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
// 节点验证器
//-------------------------

namespace app\common\validate;

use think\Validate;
use think\Db;

class Credit extends Validate
{
    protected $rule = [
        "equipment_id|装备ID"  => "require",
        "equipemnt_name|名称"   => "require",
        "user_id|用户ID"   => "require",
        "team_id|团ID" => "require",
        "room_id|房间ID" => "require",
        "room_name|房间名称" => "require",
        "des|详情说明" => "require",
    ];
}