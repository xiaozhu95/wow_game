<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

/**
 * 竞拍的价格
 */
class AuctionLog extends Controller
{
	use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {
      
  }
  /**竞拍的装备的分类*/
  public function AuctionType()
  {
      $model = $this->getModel();
      var_dump($model);
  }

  protected function aftergetList(&$data){

  }
}
