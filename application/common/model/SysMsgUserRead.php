<?php
namespace app\common\model;

use think\Model;

class SysMsgUserRead extends Model
{
    // 指定表名,不含前缀
    protected $name = 'sys_msg_user_read';
  
    public function sysMsg()
    {
        return $this->belongsTo('SysMsg','mid','id')->field('id')->setEagerlyType(0);
    }  
  
}
