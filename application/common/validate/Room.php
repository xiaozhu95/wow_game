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

class Room extends Validate
{
    protected $rule = [
        "room_number" => "require|integer",
        "service_id" => "require|integer",
        "camp_id" => "require|integer",
        "role_id" => "require|integer",
    ];

    protected $message = [
        "room_number.require" => "房间号不能为空!",
        "service_id.require" => "服务器id不能为空!",
        "camp_id.require" => "阵营id不能为空!",
        "role_id.require" => "角色id不能为空!",
    ];
}