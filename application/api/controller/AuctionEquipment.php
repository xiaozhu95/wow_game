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
        //判断是否是团长查看，团员只能看到自己的
        if(isset($map['user_id']) && $map['user_id']){
            $team_info = model('team')->teamCheck(['user_id'=>$map['user_id']]);
            if($team_info && $team_info['user_id']==$map['user_id']){
                unset($map['user_id']);
            }
        }
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
        $result = model('confirm_payment')->orderList($data);
        return ajax_return($result);
    }

    protected function aftergetList(&$data){
        //获取当前时间
        $time = time();
        if (!$data){
            return [];
        }
        if ($data){
            $data = $data->toArray();
            $auction_log_model = new AuctionLog();
            $equipment_ids = array_column($data['data'],"equipment_id");
            $equipment_ids = array_unique($equipment_ids);
            $equipment_result = model('boss_arms')->arrayList($equipment_ids);
            foreach ($data['data'] as $key=>$value){
                $data['data'][$key]['equipment_icon'] = isset($equipment_result[$value['equipment_id']]['icon']) ? $equipment_result[$value['equipment_id']]['icon'] : "" ;
                $data['data'][$key]['equipment_grade'] = isset($equipment_result[$value['equipment_id']]['grade']) ? $equipment_result[$value['equipment_id']]['grade'] : "" ;
                $data['data'][$key]['equipment_type'] = isset($equipment_result[$value['equipment_id']]['type']) ? $equipment_result[$value['equipment_id']]['type'] : "" ;
                $countdown_time = 0;

                if($data['data'][$key]['type'] == AuctionEquipmentModel::TYPE_IN_TRANSACTION){
                    if($value['end_time']>=$time){
                        if(($value['end_time']-$time)>0){
                            $countdown_time = $value['end_time']-$time; //交易中的倒计时
                        }
                        //拍卖正在进行
                        $roleinfo = $auction_log_model->actionFivePrice($value['id']);
                        $role = isset($roleinfo[0]) ? ['id'=>$roleinfo[0]['id'],'role_id'=>$roleinfo[0]['role_id'],'role_name'=>$roleinfo[0]['role_name'],'price'=>$roleinfo[$value['push_num']]['price']] : [];
                        $data['data'][$key]['role'] = $role;
                        $data['data'][$key]['price_range'] = isset($role['price']) ? $role['price'].'~'.intval($role['price']+$role['price'] * 0.15) : $value['price'] .'~'. intval($value['price']+$value['price']  * 0.15).'.00';
                    }else{
                        //拍卖结束后 出现的状况 1.流拍 2.我的交易里面
                        $roleinfo = $auction_log_model->actionFivePrice($value['id']);
                        $roleinfo = $roleinfo->toArray();
                        if(!$roleinfo){ //未有人出价则流拍

                            $data['data'][$key]['type'] = AuctionEquipmentModel::TYPE_STREAM_SHOT;
                        }else{
                            $data['data'][$key]['type'] = AuctionEquipmentModel::TYPE_OF_CREATE;
                        }
                    }
                }

                if($data['data'][$key]['type'] == AuctionEquipmentModel::TYPE_OF_CREATE){
                    //2.推送给下一个人
                    $pay_start  = $value['end_time'] + $value['pay_after_time'] * 60 * ($value['push_num']); //推送给一个人的开始时间
                    $pay_end = $value['end_time'] + $value['pay_after_time'] * 60 * ($value['push_num']+1); //推送给一个人的结束时间

                    $roleinfo = $auction_log_model->actionFivePrice($value['id']);
                    $data['data'][$key]['role'] = isset($roleinfo[$value['push_num']]) ? ['id'=>$roleinfo[$value['push_num']]['id'],'role_id'=>$roleinfo[$value['push_num']]['role_id'],'role_name'=>$roleinfo[$value['push_num']]['role_name'],'price'=>$roleinfo[$value['push_num']]['price']] : [];
                    $auction_count = count($roleinfo); //竞拍的人生
                    if($auction_count>$value['push_times']){
                        $auction_count = $value['push_times'];
                    }

                    if(($pay_end-$time)>0 && isset($roleinfo[$value['push_num']])){

                        $countdown_time = $pay_end-$time; //立即支付的倒计时

                    }else{
                        //本次处理
                        $auctionEquipmentModel = new AuctionEquipmentModel();
                        $auctionEquipmentModel_find  = $auctionEquipmentModel->where(['id'=>$value['id']])->find();
                        $auctionEquipmentModel_find->push_num +=1;
                        $auction_update_result = $auctionEquipmentModel_find->save();
                        if ($pay_start<$time && $time >= $pay_end && $value['push_num']<$value['push_times']) {

                            //推送

                            if (isset($roleinfo[$value['push_num']]) && $roleinfo[$value['push_num']]){


                                //找到第上一次
                                $auction_log = $roleinfo[$value['push_num']];
                                $team_info = model('team')->field('room_id')->where(['id'=>$auction_log['team_id']])->find();
                                $room_info = model('room')->field('id,room_num')->where(['id'=>$team_info['room_id']])->find();

                                //添加征信 日志
                                $credit_result = model('Credit')->auctionadd($auction_log,$room_info);

                                //给用户征信加 +1
                                $user_info = model('user')->where(['id'=>$auction_log['user_id']])->find();
                                $user_info->credit_num +=1;
                                $user_info_result = $user_info->save();



                            }else{
                                if ($value['push_times'] == 3 && $value['push_num']>0) { //进行第二次拍卖
                                    $data['data'][$key]['type'] = AuctionEquipmentModel::TYPE_OF_TOWAUCTION;
                                }else{
                                    $data['data'][$key]['type'] = AuctionEquipmentModel::TYPE_STREAM_SHOT;
                                }
                            }


                        }else{

                            //流拍
                            $data['data'][$key]['type'] = AuctionEquipmentModel::TYPE_STREAM_SHOT;
                            if ($value['push_times'] == 3 && $value['push_num']>0) { //进行第二次拍卖
                                $data['data'][$key]['type'] = AuctionEquipmentModel::TYPE_OF_TOWAUCTION;
                            }

                        }
                    }
                }

                if($data['data'][$key]['type'] == AuctionEquipmentModel::TYPE_OF_TOWAUCTION &&  $value['parent_id'] == 0){

                    $auctionEquipmentModel = new AuctionEquipmentModel();
                    $result = $auctionEquipmentModel->where(['parent_id'=>$value['id']])->find();
                    if (!$result){
                        $auctionEquipmentModel_find  = $auctionEquipmentModel->where(['id'=>$value['id']])->find();
                        if($auctionEquipmentModel_find) {
                            $param = [];
                            $param = [
                                [
                                    'team_id' => $auctionEquipmentModel_find['team_id'],
                                    'boss_id' => $auctionEquipmentModel_find['boss_id'],
                                    'parent_id' => $auctionEquipmentModel_find['id'],
                                    'push_times' => 5,
                                    'finsih_after_time' => 2,
                                    'pay_after_time' => 3,
                                    'equipment_id' => $auctionEquipmentModel_find['equipment_id'],
                                    'equipment_name' => $auctionEquipmentModel_find['equipment_name'],
                                    'price' => $auctionEquipmentModel_find['price'],
                                    'add_price' => $auctionEquipmentModel_find['add_price'],
                                    'currency_type' => $auctionEquipmentModel_find['currency_type'],
                                ]
                            ];

                            $auctionEquipmentModel = new AuctionEquipmentModel();

                            $auctionEquipmentModel->add($param);
                            $auctionEquipmentModel_find->type = AuctionEquipmentModel::TYPE_OF_TOWAUCTION;
                            $auctionEquipmentModel_find->save();
                        }
                    }
                }
                $data['data'][$key]['countdown_time'] = $countdown_time;
            }

        }
    }

    protected function aftergetListCopy(&$data){
        $team_id = $this->request->param('team_id');
        $team_info = model('team')->where(['id'=>$team_id])->find();
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
                $user_ids = array_column($data['data'], 'user_id');
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
