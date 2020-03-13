<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class Room extends Model
{
    // 指定表名,不含前缀
    protected $name = 'room';
}
