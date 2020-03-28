<?php
namespace app\common\model;

use think\Model;
use think\Request;

class Adv extends Model
{
    // 指定表名,不含前缀
    protected $name = 'adv';
	protected $type = [
        'start_time'  =>  'timestamp',
        'end_time'  =>  'timestamp',
    ];
    
	protected function getTypeTextAttr($value,$data)
    {
        $adv_types = \think\Config::get('adv_types');
        return $adv_types[$data['type']];
    }

    protected function getPicUrlAttr($value,$data)
    {
        $value = $data['pic'];
        return html_entity_decode(strpos($value,'/tmp') === 0 ? Request::instance()->domain().$value : $value);
    }

    protected function setPicAttr($value)
    {
        return strpos($value,'/tmp') === 0 ? Request::instance()->domain().$value : $value;
    }

	protected function setRemarkAttr($value)
    {
        return json_encode($value);
    }

	protected function getRemarkArrAttr($value,$data)
    {
        return json_decode($data['remark'], true);
    }

    protected function setLinkAttr($value)
    {
        return json_encode($value);
    }

    protected function getLinkArrAttr($value,$data)
    {
        return json_decode($data['link'], true);
    }

    protected function getLinkTextAttr($value,$data)
    {
        return $data['link'];
    }
  
}
