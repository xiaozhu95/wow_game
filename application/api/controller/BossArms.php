<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
/**
 * 副本下的装备
 */
class BossArms extends Controller
{
	use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {
      $map['type'] = "布甲";
  }

  protected function aftergetList(&$data){

  }
}
