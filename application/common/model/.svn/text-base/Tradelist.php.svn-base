<?php
namespace app\common\model;

use think\Model;
use think\Config;

class Tradelist extends Model
{
    // 指定表名,不含前缀
    protected $name = 'tradelist';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
	
	public function user()
    {
        return $this->belongsTo('User')->field('tel,nickname');
    }
	
    protected function getSubjectNameAttr($value, $data)
    {
    		$subjects = Config::get('subjects');
        return $value ?: $subjects[$data['subject_id']]['name'];
    }	
	
    protected function getTypeTextAttr($value,$data)
    {
		$texts = Config::get('pay_types');
        return $texts[$data['type']];
    }	
}
