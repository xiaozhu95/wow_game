<?php
namespace app\common\model;

use think\Model;
use think\Config;

class PayLog extends Model
{
    // 指定表名,不含前缀
    protected $name = 'pay_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
	
	public function user()
    {
        return $this->belongsTo('User')->field('mobile,nickname')->setEagerlyType(0);
    }
	
    // protected function getSubjectNameAttr($value, $data)
    // {
    // 	$subjects = Config::get('subjects');
    //     return $value ?: $subjects[$data['subject_id']]['name'];
    // }	
	
    protected function getTypeTextAttr($value,$data)
    {
		$texts = Config::get('pay_types');
        return $texts[$data['type']-1];
    }
    protected function getStatusTextAttr($value,$data)
    {
        $texts = ['未付款','已付款','失效'];
        return $texts[$data['status']];
    }
    /**
     * @param $startTime
     * @param $endTime
     * @return float|int
     * 计算时间段内的消费记录
     */
    public function countPayByTime ($startTime, $endTime, $type)
    {
        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        $where['status'] = 1;
        if($type>0){
            $where['type'] = $type;
        }

        $totalAmountCount = $this->where("create_time", "between", [$startTime, $endTime])->where($where)->sum("total_amount");

        return $totalAmountCount;
    }
}
