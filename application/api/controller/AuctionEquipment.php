<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
use app\api\controller\AuctionLog;

/**
 * 竞拍装备的开始
 */
class AuctionEquipment extends Controller
{
	use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {
      
  }
  
  protected function aftergetList(&$data){
      if($data){
          $data = $data->toArray();
          $auctionLog = new AuctionLog();
          $equipment_ids = array_column($data, "equipment_id");
          //获取装每一次竞拍的最高价格
          $auctionLog = $auctionLog->auctionType($equipment_ids);
          foreach ($data as $key => $value) {
              $value['price']  = isset($auctionLog[$value['equipment_id']]) ? $auctionLog[$value['equipment_id']] : 0;
          }
      }
  }
}
