<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
use app\common\model\AuctionLog;
use app\common\model\AuctionEquipment as AuctionEquipmentModel;

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
    /**订单记录*/
    public function auctionOrderList()
    {
        $user_id = $this->request->param('user_id',0);
        $data ['user_id'] = $user_id;
        $result = model('confirm_payment')->orderList($user_id);
        return ajax_return($result);
    }

    protected function aftergetList(&$data){
      $team_info = model('team')->where(['id'=>$data['team_id']])->find();
      if(!$team_info){
            return [];
      }
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
          //判断
          $type = $this->request->param('type');
          if($type == AuctionEquipmentModel::TYPE_STREAM_SHOT || $type == AuctionEquipmentModel::TYPE_SUCCESSFUL_TRANSACTION){
            $role = model('role');
            $role_info =  $role->where(['id'=>$team_info['role_id']])->find();
            $user_ids = array_column($data, 'user_id');
            $user_ids = array_unique($user_ids);
            $user_info = $role->arrayList(['service_id'=>$role_info['service_id'],'camp_id'=>$role_info['camp_id']],$user_ids);
            $user = model('user')->field('id,nickname,avatar')->where('id','in',$user_ids)->select();
            $user = array_columns($user, "nickname,avatar", "id");
          }
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
             // 流拍  或者 交易成功
             if($value['type'] == AuctionEquipmentModel::TYPE_STREAM_SHOT || $value['type'] == AuctionEquipmentModel::TYPE_SUCCESSFUL_TRANSACTION){
                $data['data'][$key]['user'] = [
                     'id' => $value['user_id'],
                     'nickname' => isset($user_info[$value['user_id']]['role_name']) ? $user_info[$value['user_id']]['role_name'] : '',
                     'avatar' => isset($user[$value['user_id']]['avatar']) ? $user[$value['user_id']]['avatar'] : "",
                 ];
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
