<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class SysMsgUserRead extends Controller
{
    use \app\api\traits\controller\Controller;

    protected function filter(&$map)
    {
      $uid = $this->request->param("user_id");
      $sign = $this->request->post("sign");
      if($uid && $sign && password_hash_tp($uid)==$sign){
        $map['uid'] = $uid;
        $map['_field'] = 'sysmsg.title,sysmsg.message,sysmsg.create_time,sys_msg_user_read.status,sys_msg_user_read.id';
        $map['_order_by'] = "sys_msg_user_read.id desc";
        $map['_relation'] = "sys_msg";
        $map['_table'] = "sys_msg_user_read";
      }else
        $map['id'] = 0;
    }

  
    public function aftergetList(&$data)
    {
      $id = [];
      foreach($data as $v){
        $v['message'] = htmlspecialchars_decode($v['message']);
        if(!$v['status']){
          $id[] = $v['id'];
        }
      }
      if(!empty($id))
        model("SysMsgUserRead")->where("id","in",$id)->update(['status' => 1]);
    }

}
