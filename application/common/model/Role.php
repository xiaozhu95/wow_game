<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class Role extends Model
{
    // 指定表名,不含前缀
    protected $name = 'role';
}
