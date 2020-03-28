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
        $map['_relation']="User";
    }
    
    
    /**
     * 添加竞拍记录
     */
    public function addAuction()
    {
        define('SKIP_AUTH',true);
        $this->request->post(['_ajax'=>1]);
        return action('admin/auction_log/add');

    }

  protected function aftergetList(&$data){
      
      $auction_equipment_id = $this->request->param('auction_equipment_id');
      $team_id = $this->request->param('team_id');
      $team_info = model('team')->where(['id'=>$team_id])->find();
      if(!$team_info){
           $data = [];
           return ;
      }
      $auction_equipment = model('auction_equipment')->field('equipment_id,price,equipment_name,currency_type,add_price')->where(['id'=>$auction_equipment_id])->find();
      if(!$auction_equipment){
           $data = [];
         
           return ;
      }
      
       //获取装每一次竞拍的最高价格
      $auctionLog = model('auction_log')->auctionType([$auction_equipment_id]);
      
      $equipment_result = model('boss_arms')->arrayList([$auction_equipment['equipment_id']]);
      $data = $data->toArray();
      $is_visit = 0;
      $price = $auction_equipment['price'];
      if ( isset($auctionLog[$auction_equipment_id]['price']) ){
          $is_visit = 1;
          $price = $auctionLog[$auction_equipment_id]['price'];
      }
      $role = model('role');
      $role_info =  $role->where(['id'=>$team_info['role_id']])->find();
      $user_ids = array_column($data, 'user_id');
      $user_ids = array_unique($user_ids);
      $user_info = $role->arrayList(['service_id'=>$role_info['service_id'],'camp_id'=>$role_info['camp_id']],$user_ids);
      foreach ($data['data'] as $key => $value) {
          $data['data'][$key]['user']['nickname'] = isset($user_info[$value['user']['id']]['nickname']) ? $user_info[$value['user']['id']]['nickname'] :"";
      }
      
      $newData = [
            'auction_info' => [
              'equipment_id' => $auction_equipment['equipment_id'],
              'equipment_name' => $auction_equipment['equipment_name'],
              'currency_type' => $auction_equipment['currency_type'],
              'add_price' => $auction_equipment['add_price'],
              'is_visit' =>$is_visit,
              'equipment_price' => $price,
              'equipment_icon' => isset($equipment_result[$auction_equipment['equipment_id']]['icon']) ? $equipment_result[$auction_equipment['equipment_id']]['icon'] : "" ,
              'equipment_grade' => isset($equipment_result[$auction_equipment['equipment_id']]['grade']) ? $equipment_result[$auction_equipment['equipment_id']]['grade'] : "" ,
              'equipment_type' => isset($equipment_result[$auction_equipment['equipment_id']]['type']) ? $equipment_result[$auction_equipment['equipment_id']]['type'] : "" ,
           ],
          'record_log' => $data['data'],
      ];
    
      $data ['data'] = $newData;
  }
}
