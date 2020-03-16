<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

/**
 * 阵营
 * 种族
 */
class Occupation extends Controller
{
	use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {
      
  }
  
  protected function aftergetList(&$data){

  }
}
