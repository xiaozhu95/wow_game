<?php
namespace app\common\model;

use think\Model;
use think\Request;

class SysMsg extends Model
{
    // 指定表名,不含前缀
    protected $name = 'sys_msg';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $updateTime = false;
	protected $type = [
        'end_time'  =>  'timestamp',
        'create_time'  =>  'timestamp',
    ];
  
  
	protected function getMessageAttr($value)
    {
        return htmlspecialchars_decode($value);
    } 
  
  protected
  function getReceiveAttr( $value, $data ) {
    return model('SysMsgUserYes')->where(['mid'=>$data['id']])->value('group_concat(uid)');
  }
  
  protected
  function getShieldAttr( $value, $data ) {
    return model('SysMsgUserNo')->where(['mid'=>$data['id']])->value('group_concat(uid)');
  }
  
  protected static
  function init() {
    SysMsg::event( 'after_insert', function ( $data ) {
      $receive = Request::instance()->param("receive");
      if($receive){
        foreach(explode(',',$receive) as $v){
          $list[] = ['mid'=>$data->id,'uid'=>$v];
        }
        model('SysMsgUserYes')->saveAll($list,false);
      }
      $shield = Request::instance()->param("shield");
      if($shield){
        foreach(explode(',',$shield) as $v){
          $list[] = ['mid'=>$data->id,'uid'=>$v];
        }
        model('SysMsgUserNo')->saveAll($list,false);
      }
    } );
    SysMsg::event( 'after_update', function ( $data ) {
      model('SysMsgUserYes')->where(['mid' => $data->id])->delete();
      model('SysMsgUserNo')->where(['mid' => $data->id])->delete();
      $receive = Request::instance()->param("receive");
      if($receive){
        foreach(explode(',',$receive) as $v){
          $list[] = ['mid'=>$data->id,'uid'=>$v];
        }
        model('SysMsgUserYes')->saveAll($list,false);
      }
      $shield = Request::instance()->param("shield");
      if($shield){
        foreach(explode(',',$shield) as $v){
          $list[] = ['mid'=>$data->id,'uid'=>$v];
        }
        model('SysMsgUserNo')->saveAll($list,false);
      }
    } );
  }
}
