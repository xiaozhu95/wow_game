<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class Team extends Model
{
    // 指定表名,不含前缀
    protected $name = 'team';
}
