<?php
namespace app\common\model;

use think\Model;

class CronLog extends Model
{
    // 指定表名,不含前缀
    protected $name = 'cron_log';
    protected $autoWriteTimestamp = 'int';
    protected $updateTime = false;

    protected function getRespArrAttr($value,$data)
    {
        return json_decode($value, true);
    }

    protected function setRespAttr($value)
    {
        return json_encode($value);
    }
}

