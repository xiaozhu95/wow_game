<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class Region extends Model
{
    // 指定表名,不含前缀
    protected $name = 'region';

    public function children($parent_id){
		$parent_id = intval($parent_id);
		$children = Cache::remember('region_'.$parent_id,function() use ($parent_id){
			return $this->field("region_id,region_name,region_type")->where('parent_id',$parent_id)->select();
		},86400*30);
		return $children;
	}
}
