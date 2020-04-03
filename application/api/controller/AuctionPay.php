<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class AuctionPay extends Controller
{
	use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {
   
  }
  /**立即支付*/
  public function pay()
  {
      $data = $this->request->param();
      $model = $this->getModel();
      return $model->apy($data);
  }
  
  
    public function auctionList(){
        $user_id = $this->request->param('user_id',0);
        $map['user_id'] = $user_id; 
        $model = $this->getModel();
         $data = $this->datalist($model, $map);
        if($data){
            $data = $data->toArray();    
            //$data['pay_time'] =  $data['pay_time'] ? date('Y-m-d H:i:s',$data['pay_time']) : "";
            $equipment_ids = array_column($data['data'],"equipment_id");
            $team_ids = array_column($data['data'],"team_id");
            $team_ids = array_unique($team_ids);
            $team_members_info = model('team_member')->field('id,team_id,user_id,user_role_name')->where('team_id','in',$team_ids)->where(['is_del'=>1])->select()->toArray();
            $equipment_result = model('boss_arms')->arrayList($equipment_ids);
            foreach ($data['data'] as $key => $value) {
                $data['data'][$key]['pay_time'] =  $value['pay_time'] ? date('Y-m-d H:i:s',$value['pay_time']) : "";
                $data['data'][$key]['equipment_name'] =  $value['equipment_name'] ? $value['equipment_name'] : "购买地板";
                $data['data'][$key]['role_name'] = $this->filterRole($team_members_info, $value['user_id'], $value['team_id']);
                $data['data'][$key]['equipment_icon'] = isset($equipment_result[$value['equipment_id']]['icon']) ? $equipment_result[$value['equipment_id']]['icon'] : "" ;
                $data['data'][$key]['equipment_grade'] = isset($equipment_result[$value['equipment_id']]['grade']) ? $equipment_result[$value['equipment_id']]['grade'] : "" ;
                $data['data'][$key]['equipment_type'] = isset($equipment_result[$value['equipment_id']]['type']) ? $equipment_result[$value['equipment_id']]['type'] : "" ;
            }
        }
        return ajax_return($data);
    }
    
    //晒选用户角色
    private function filterRole($data,$user_id,$team_id){
        $role_name = "";
        foreach ($data as $key => $value) {
             if ($value['user_id'] == $user_id && $value['team_id'] == $team_id){
                 $role_name = $value['user_role_name'];
             }
        }
        return $role_name;
    }
}
