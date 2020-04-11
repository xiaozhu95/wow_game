<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class Adv extends Controller
{
	use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {

    $time = time();
    $start_time = $this->request->param('start_time', $time);
    $end_time = $this->request->param('end_time', $time);
    if(!is_numeric($start_time))
      $start_time = strtotime($start_time);
    if(!is_numeric($end_time))
      $end_time = strtotime($end_time);

    $map['start_time'] = ["<",$start_time];
    $map['end_time'] = [">",$end_time];
    $map['status'] = 1;
    if(isset($map['type']) && strstr(',',$map['type']))
      $map['type'] = ['in', $map['type']];
    $map['_field'] = "link,pic,remark,end_time as end_time_timestamp,type";

		if(!$this->request->has("_order"))
      $map['_order_by'] = "sort asc,id desc";
      think::open();
    $map['_cache'] = true;
    // $map['_cache'] = md5(str_replace($time,'',json_encode($map)));
  }

  protected function aftergetList(&$data){
    if($this->request->param('classified')){
      $res = [];
      foreach($data as $v){
        $v['pic_url'] = $v['pic_url'];
        $v['link_arr'] = $v['link_arr'];
        $v['remark_arr'] = $v['remark_arr'];
        $res[$v['type']][] = $v;
      }
      $data = $res;
    }else
      foreach($data as $v){
        $v['pic_url'] = $v['pic_url'];
        $v['link_arr'] = $v['link_arr'];
        $v['remark_arr'] = $v['remark_arr'];
      }
  }
}
