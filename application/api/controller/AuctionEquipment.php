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

    private $is_team = 0; //判断是不是团长
    private $team_num = 0; //  待支付 待审核，审核
    private $team_menber_num = 0; // 待支付 待审核
    private $user_id = 0;

    protected function filter(&$map)
    {
        if(isset($map['user_id']) && $map['user_id']){
            unset($map['user_id']);
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

    public function aftergetList(&$data){

        $is_type = $this->request->param('is_type',0);
        $user_id = $this->request->param('user_id',0);
        $team_id = $this->request->param('team_id',0);
        //获取当前时间
        $time = time();
        if (!$data){
            return [];
        }
        //判断是否是团长查看，团员只能看到自己的
        if($user_id){
            $team_info = model('team')->teamCheck(['id'=>$team_id,'user_id'=>$user_id]);
            if($team_info && $team_info['user_id']==$user_id){
                $this->is_team = 1;
                $this->user_id = $team_info['user_id'];
            }
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
                        $role = isset($roleinfo[0]) ? ['id'=>$roleinfo[0]['id'],'user_id'=>$roleinfo[0]['user_id'],'role_id'=>$roleinfo[0]['role_id'],'role_name'=>$roleinfo[0]['role_name'],'price'=>$roleinfo[$value['push_num']]['price']] : [];
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
                    $data['data'][$key]['role'] = isset($roleinfo[$value['push_num']]) ? ['id'=>$roleinfo[$value['push_num']]['id'],'user_id'=>$roleinfo[$value['push_num']]['user_id'],'role_id'=>$roleinfo[$value['push_num']]['role_id'],'role_name'=>$roleinfo[$value['push_num']]['role_name'],'price'=>$roleinfo[$value['push_num']]['price']] : [];
                    if(!$this->is_team && isset($data['data'][$key]['role']['user_id']) && $data['data'][$key]['role']['user_id'] != $user_id){
                        unset($data['data'][$key]);
                        continue;
                    }

                    $this->team_num ++;
                    $this->team_menber_num ++;
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

                                //给用户征信加 +1
                                $user_info = model('user')->where(['id'=>$auction_log['user_id']])->find();

                                if($user_info->credit_num>=2 ){



                                    $auction_data['team_id'] = $value['team_id'];
                                    $auction_data['equipment_id'] = $value['equipment_id'];
                                    $auction_data['equipment_name'] = $value['equipment_name'];
                                    $auction_data['confirm_payment_id'] = $value['id'];
                                    $auction_data['user_id'] = $data['data'][$key]['role']['user_id'];
                                    $auction_data['price'] =   $data['data'][$key]['role']['price'];
                                    $auction_data['currency_type'] = $value['currency_type'];
                                    $auction_data['auction_log_id'] = $data['data'][$key]['role']['id'];
                                    $auction_data['auction_equipment_id'] = $value['id'];

                                    $auctiopay_result = model("AuctionPay")->apy($auction_data);

                                    $flag = json_decode($auctiopay_result->getContent(),true);

                                    if ($flag['code'] ==0){
                                        $user_info->credit_num-=1;
                                        $credit_result = model('Credit')->auctionjian($auction_log,$room_info);
                                        $data['data'][$key]['type'] = AuctionEquipmentModel::TYPE_SUCCESSFUL_TRANSACTION;
                                    }else{
                                        $user_info->credit_num +=1;
                                    }

                                }else{
                                    $user_info->credit_num +=1;
                                    //添加征信 日志
                                    $credit_result = model('Credit')->auctionadd($auction_log,$room_info);
                                }



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
                                    'finsih_after_time' => 10,
                                    'pay_after_time' => 5,
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
                if($data['data'][$key]['type'] == AuctionEquipmentModel::TYPE_OF_CHECK){
                    if(!$this->is_team && $data['data'][$key]['user_id'] != $user_id){
                        unset($data['data'][$key]);
                        continue;
                    }
                    $this->team_num ++;
                    $auction_pay_result = model('auction_pay')->field("id")->where(['confirm_payment_id'=>$value['id']])->find();
                    $order_id = isset($auction_pay_result['id']) ? $auction_pay_result['id'] : 0;
                    if(!$order_id){
                        $this->team_menber_num ++;
                    }
                    $data['data'][$key]['order_id'] = $order_id;
                }
                if($data['data'][$key]['type'] == AuctionEquipmentModel::TYPE_STREAM_SHOT){
                    //本次处理
                    $auctionEquipmentModel = new AuctionEquipmentModel();
                    $auctionEquipmentModel_find  = $auctionEquipmentModel->where(['id'=>$value['id']])->find();
                    if($auctionEquipmentModel_find->type != AuctionEquipmentModel::TYPE_STREAM_SHOT){
                        $auctionEquipmentModel_find->type = AuctionEquipmentModel::TYPE_STREAM_SHOT;
                        $auctionEquipmentModel_find->save();
                    }

                }
                $data['data'][$key]['countdown_time'] = $countdown_time;

                $data['data'][$key]['is_visit'] = 0;
                //判断是否参与过竞拍
                if($is_type == 1 && !$this->is_team){
                    $result = model("AuctionLog")->checkIsAuction($team_id,$data['data'][$key]['id'],$user_id);
                    if(!$result){
                        unset($data['data'][$key]);
                        continue;
                    }
                    $data['data'][$key]['is_visit'] = 1;
                    $data['data'][$key]['auction_fail'] = $result;
                }
            }

        }
        $data['team_num'] = $this->team_num;
        $data['team_member_num'] = $this->team_menber_num;

        $array = [];
        foreach ($data["data"] as $key => $value) {
            $array[] = $value;
        }

        $data["data"] = $array;
    }

}
