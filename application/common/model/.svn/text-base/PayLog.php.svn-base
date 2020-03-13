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
        return $this->belongsTo('User')->field('tel,nickname')->setEagerlyType(0);
    }
	
    // protected function getSubjectNameAttr($value, $data)
    // {
    // 	$subjects = Config::get('subjects');
    //     return $value ?: $subjects[$data['subject_id']]['name'];
    // }	
	
    protected function getTypeTextAttr($value,$data)
    {
		$texts = Config::get('pay_types');
        return $texts[$data['type']];
    }

}
