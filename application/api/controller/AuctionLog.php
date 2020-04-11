<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
use think\Db;

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
     * 如果后期加service层则加入
     */
    public function addAuction()
    {
        $user_id = $this->request->param('user_id');
        $data = $this->request->param();

        $model = $this->getModel();

        //获取用户信息
        $user = model('user');
        $userInfo = $user->userInfo($user_id);
        if (!$userInfo) {
            return ajax_return('用户信息有误');
        }

        //添加竞拍日志
        Db::startTrans();
        $aucction_log_result = $model->addLog($data);
        $flag = json_decode($aucction_log_result->getContent(),true);
        if($flag['code'] == 1){
            Db::rollback();
            return $aucction_log_result;
        }
        //检测用户是否失信(值征信次数大于等于2次)
        $crdit_add_result = 1; //默认添加成功
        $user_update_result = 1; //默认修改成功

        $auction_equipment_info = model('auction_equipment')->where(['id'=>$data['auction_equipment_id']])->find();
        if (!$auction_equipment_info) {
            return ajax_return('竞拍信息不存在');
        }
        if($userInfo->credit_num >= 2 && $auction_equipment_info->currency_type == \app\common\model\AuctionEquipment::CURRENCY_TYPE_MONEY){

            $team_info = model('team')->field('room_id')->where(['id'=>$data['team_id']])->find(); //团信息
            $room_info =  model('room')->where(['id'=>$team_info['room_id']])->find(); //房间信息

            if(!$room_info) {
                Db::rollback();
                return ajax_return_adv_error('房间信息不存在');
            }

            $credit = model('Credit');
            //添加冻结
            $crdit_add_result  = $credit->add($user_id, $data, $room_info);
            //统计冻结金额
            $total_price = $credit->total_price($user_id);
            //用户的总余额
            $user_price  = $userInfo->balance + $userInfo->freeze_money;
            $userInfo->balance = $user_price - $total_price;
            $userInfo->freeze_money = $total_price;
            $user_update_result = $userInfo->save();
        }
        if ($flag['code'] == 0 && $crdit_add_result && $user_update_result ){
            Db::commit();
            return ajax_return('竞拍成功');

        }
        Db::rollback();
        return ajax_return_adv_error('竞拍失败');


    }


    protected function aftergetList(&$data){

        $auction_equipment_id = $this->request->param('auction_equipment_id');
        $team_id = $this->request->param('team_id');
        $team_info = model('team')->where(['id'=>$team_id])->find();
        if(!$team_info){
            $data = [];
            return ;
        }
        $auction_equipment = model('auction_equipment')->field('equipment_id,price,equipment_name,currency_type,add_price,end_time')->where(['id'=>$auction_equipment_id])->find();
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
        $user_ids = array_column($data['data'], 'user_id');
        $user_ids	= array_unique($user_ids);

        $user_info = $role->arrayList(['service_id'=>$role_info['service_id'],'camp_id'=>$role_info['camp_id']],$user_ids);
        $time = $auction_equipment['end_time'] - time();
        if($time<0){
            $time = 0;
        }



        $newData = [
            'auction_info' => [
                'equipment_id' => $auction_equipment['equipment_id'],
                'equipment_name' => $auction_equipment['equipment_name'],
                'currency_type' => $auction_equipment['currency_type'],
                'add_price' => $auction_equipment['add_price'],
                'is_visit' =>$is_visit,
                'equipment_price' => $price,
                'end_time' => $time,
                'equipment_icon' => isset($equipment_result[$auction_equipment['equipment_id']]['icon']) ? $equipment_result[$auction_equipment['equipment_id']]['icon'] : "" ,
                'equipment_grade' => isset($equipment_result[$auction_equipment['equipment_id']]['grade']) ? $equipment_result[$auction_equipment['equipment_id']]['grade'] : "" ,
                'equipment_type' => isset($equipment_result[$auction_equipment['equipment_id']]['type']) ? $equipment_result[$auction_equipment['equipment_id']]['type'] : "" ,
            ],
            'record_log' => $data['data'],
        ];


        $data ['data'] = $newData;
    }
}
