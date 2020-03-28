<?php
namespace app\common\model;

use think\Model;
use think\Request;
/**
 * 种族天赋
 */
class Talent extends Model
{
    // 指定表名,不含前缀
    protected $name = 'talent';

    public function getChilds($occupation)
    {
       $list = $this->where(['name'=>$occupation,'type'=>0])->find();
       if(!$list)
           return ajax_return([]);
       $result = $this->where(['parent_id'=>$list['id']])->select();
       return ajax_return($result);
    }

  
}
