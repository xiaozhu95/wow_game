<?php
namespace app\common\model;

use think\Model;

class Cron extends Model
{
    // 指定表名,不含前缀
    protected $name = 'cron';
    protected $autoWriteTimestamp = 'int';
    protected $insert = ['start_time','next_time'];
    protected $type = [
        'start_time'  =>  'timestamp',
        'next_time'  =>  'timestamp',
    ];
  
    protected function setStartTimeAttr($value)
    {
        return $value ?: time();
    }
  
    protected function setNextTimeAttr($value)
    {
        return $value ?: time();
    }

    protected function getDataArrAttr($value,$data)
    {
        return json_decode($data['data'], true);
    }

    protected function setDataAttr($value)
    {
        return json_encode($value);
    }
}

