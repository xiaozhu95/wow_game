<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class SysMsg extends Controller
{
    use \app\api\traits\controller\Controller;

    public function filter(&$map){
      $map['id'] = 0;
    }  
  
    public function count(){
      $count = 0;
      $uid = $this->request->param("user_id");
      $sign = $this->request->post("sign");
      if($uid && $sign && password_hash_tp($uid)==$sign){
        $res = model("SysMsg")->alias("m")->join([
          ['__SYS_MSG_USER_YES__ muy','muy.mid=m.id and muy.uid='.$uid,'LEFT'],
          ['__SYS_MSG_USER_NO__ mun','mun.mid=m.id and mun.uid='.$uid,'LEFT'],
          ['__SYS_MSG_USER_READ__ mur','mur.mid=m.id and mur.uid='.$uid,'LEFT']
        ])->field('m.id,m.status,mur.mid')->where("m.status in(1, 2) AND (m.end_time = 0 OR m.end_time >= ".time().") AND (mur.status is null or mur.status=0) AND (muy.uid=$uid or (muy.uid is null and mun.uid is null))")->order("m.id")->select();
        if($res){
          foreach($res as $v){
            if($v['status'] == 2) continue;
            $count++;
            
            if($v['mid']) continue; //有mid表示已经写入过read表了
            $list[] = [
              'mid' => $v['id'],
              'uid' => $uid
            ];
          }
          if(!empty($list))
            model("SysMsgUserRead")->saveAll($list,false);
        }
      }
      return json(['count'=>$count]);
    }
}
