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
    
    public function arrayList($ids)
    {
       $data  = $this->field('id,icon,grade,type')->where("id",'in',$ids)->select();
       if(!$data) return $data;

       $data =  $data->toArray();
      
       $data = array_columns($data, "icon,grade,type", "id");
      
       return $data  ;
    }
}
