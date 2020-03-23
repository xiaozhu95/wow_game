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

class Role extends Validate
{
    protected $rule = [
        "user_id"  => "require|integer",
        "service_id"  => "require|integer",
        "service_name" => 'require',
        'camp_id' => 'integer',
        'camp_name' => 'require',
        'occupation_id'=> 'integer',
        'occupation_name' =>'require',
        'role_name' => 'require|max:15',
        'grade' => 'integer',
        'talent' => 'require',
        'equipment_grade' => 'integer'
    ];
    protected $message = [
        'user_id.require' => '用户ID必填',
        'user_id.integer' => '用户ID数字',
        'service_id.require' => '服务器ID必填',
        'service_id.integer' => '服务器必须是数字',
        'service_name.require' => '服务器必须填',
        'camp_id.integer' => '阵营ID是数字',
        'camp_name.require' => '阵营必须',
        
        'race_id.require' => '种族',
        'race_id.integer' => '种族ID数字',
         'race_name.require' => '种族名称须填',
        
        'occupation_id.require' => '职业必填',
        'occupation_id.integer' => '职业ID数字',
        'occupation_name.require' => '职业必须填',
        
        'role_name.require' => '必填',
        'role_name.max' => '角色名称不能大于15',
        'grade.integer' => '角色等级',
        'talent.require' => '天赋必选',
        'equipment_grade.require' => '角色装备评分',
    ];
}