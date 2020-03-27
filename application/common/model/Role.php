<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class Role extends Model
{
    // 指定表名,不含前缀
    protected $name = 'role';
    
    public function arrayList($where,$users)
    {
       $data  = $this->field('user_id,role_name')->where($where)->where('user_id','in',$users)->select();
       if(!$data) return $data;

       $data =  $data->toArray();
      
       $data = array_columns($data, "role_name", "user_id");
      
       return $data  ;
    }
}
