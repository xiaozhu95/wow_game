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
        $param = $this->request->param('params');
        $param =  htmlspecialchars_decode($param);
        $param = json_decode($param,true);
        
        return $model->add($param);
    }
    
    protected function aftergetList(&$data){
      if($data){
          $data = $data->toArray();
          $auctionLog = new AuctionLog();
          $ids = array_column($data['data'],"id");
          $ids = array_unique($ids);
          
          //获取装每一次竞拍的最高价格
          $auctionLog = $auctionLog->auctionType($ids);
          
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
          }
      }
  }
}
