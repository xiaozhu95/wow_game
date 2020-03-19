<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class TranscriptBoss extends Model
{
    // 指定表名,不含前缀
    protected $name = 'transcript_boss';

    public function getChildren ($parent_id)
    {
        return $this->field("id, parent_id, name, type")->where(["parent_id" => $parent_id])->select();
    }
}
