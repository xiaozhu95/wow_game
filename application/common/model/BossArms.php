<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class BossArms extends Model
{
    // 指定表名,不含前缀
    protected $name = 'boss_arms';

    public function getList ($transcript_boss_id)
    {
        return $this->where(["transcript_boss_id" => $transcript_boss_id])->select();
    }
}
