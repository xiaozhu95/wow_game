<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
use app\common\model\AuctionLog;

/**
 * 竞拍装备的装备
 */
class AuctionEquipment extends Controller
{
    use \app\api\traits\controller\Controller;

    protected function filter(&$map)
    {

    }
    
    /**批量添加装备*/
    public function addEquipment()
    {
        $model = $this->getModel();
     
        $param = $this->request->param();
      
        return $model->add($param);
    }
    
    /**交易中*/
    public function transaction()
    {
       $data = $this->request->param();
       $result = model('confirm_payment')->confirm($data);
       return ajax_return($result);
    }

    protected function aftergetList(&$data){
      if($data){
          $data = $data->toArray();
          $auctionLog = new AuctionLog();
          $ids = array_column($data['data'],"id");
          $ids = array_unique($ids);
          $equipment_ids = array_column($data['data'],"equipment_id");
          $equipment_ids = array_unique($equipment_ids);
          
          //获取装每一次竞拍的最高价格
          $auctionLog = $auctionLog->auctionType($ids);
          $equipment_result = model('boss_arms')->arrayList($equipment_ids);
          foreach ($data['data'] as $key => $value) {
             $auctionMsg = isset($auctionLog[$value['id']]) ? $auctionLog[$value['id']] : 0;
             if($auctionMsg === 0){
                 $data['data'][$key]['is_visit'] = 0;
                 $data['data'][$key]['user'] = [];
             }else{
                 $data['data'][$key]['is_visit'] = 1;
                 $data['data'][$key]['user'] = [
                     'id' => $auctionMsg['id'],
                     'nickname' => $auctionMsg['nickname'],
                     'avatar' => $auctionMsg['avatar'],
                 ];
                 
                 $data['data'][$key]['price'] = $auctionMsg['price'];
             }
            $end_time = $value['end_time']- time();
            if($end_time<0){
                $end_time = 0;
            }
            $data['data'][$key]['end_time'] = $end_time;
            $data['data'][$key]['equipment_icon'] = isset($equipment_result[$value['equipment_id']]['icon']) ? $equipment_result[$value['equipment_id']]['icon'] : "" ;
            $data['data'][$key]['equipment_grade'] = isset($equipment_result[$value['equipment_id']]['grade']) ? $equipment_result[$value['equipment_id']]['grade'] : "" ;
            $data['data'][$key]['equipment_type'] = isset($equipment_result[$value['equipment_id']]['type']) ? $equipment_result[$value['equipment_id']]['type'] : "" ;
          }
      }
  }
}
