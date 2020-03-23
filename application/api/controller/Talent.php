<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class Talent extends Controller
{
	use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {
      
  }

  public function getChilds($occupation)
  {
      return  model('talent')->getChilds($occupation);
  }

  protected function aftergetList(&$data){

  }
}
