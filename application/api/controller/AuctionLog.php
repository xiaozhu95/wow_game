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
  /**竞拍的装备的分类 装备竞拍的ID*/
  public function auctionType($equipment_ids)
  {
      $model = $this->getModel();
      $list = $model->where($equipment_ids)->field("equipment_id,max(price) as price")->where($model)->group("equipment_id")->select();
      if($list){
           $list = $list->toArray();
           $list = array_column($list, "price", "equipment_id");
      }
      return $list;
  }

  protected function aftergetList(&$data){

  }
}
