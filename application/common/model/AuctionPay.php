<?php
namespace app\common\model;

use think\Model;
use think\Cache;
use app\common\model\ConfirmPayment;
use app\common\model\AuctionEquipment;
use app\common\model\TeamMember;
use think\Db;
use app\common\validate\AuctionPay as AuctionPayValidate;
class AuctionPay extends Model
{
    // 指定表名,不含前缀
    protected $name = 'auction_pay';
    
    const CURRENCY_TYPE_GLOD = 1;
    const CURRENCY_TYPE_MONEY = 2;
    
    const CONFIRM_STATUS_MONEY = 0; //用钱支付无效确认
    const CONFIRM_STATUS_TEAM_MENBER = 1; //团员确认
    const CONFIRM_STATUS_TEAM = 2;  //团长确认
    
    const PAY_TYPE_YES = 1; //已支付
    const PAY_TYPE_NO = 2; //未支付

    /**
     * @param $data
     * @return \think\response\Json
     * 获取支付纪录
     */
    public function apyList ($data)
    {
        $team = new Team();
        $teamInfo = $team->field("	gold_coin, amount")->where(["id" => $data["team_id"]])->find();
        $params = $this->where(["pay_type" => AuctionPay::PAY_TYPE_YES])->where(["team_id" => $data["team_id"]]);
        $currencyCoin = $params->where(["currency_type" => AuctionPay::CURRENCY_TYPE_GLOD])->select()->toArray();
        $currencyMoney = $params->where(["currency_type" => AuctionPay::CURRENCY_TYPE_MONEY])->select()->toArray();

        $result = [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'currencyCoin' => $currencyCoin,
                'currencyMoney' => $currencyMoney,
                "teaminfo" => $teamInfo
            ]
        ];

        return json($result);
    }

    public function apy($data)
    {
      	$validate = new AuctionPayValidate();
        if (!$validate->check($data)) {
             return ajax_return_adv_error($validate->getError());
        }
        $user = model('user')->where(['id'=>$data['user_id']])->find();
        if(!$user) {
            return ajax_return_adv_error('用户不存在');
        }

        /**判断是装备拍卖,还是地板拍卖 如果equipment_id == 0是地板拍卖*/
        if($data['equipment_id'] == 0){ //拍卖地板
              Db::startTrans();
           $diban = model('auction_pay')->where(['team_id'=>$data['team_id'],'equipment_id'=>0])->find();
          
            if($diban) return ajax_return_adv_error('地板已拍卖');
            $auctionPayDaya['team_id'] = $data['team_id']; 
            $auctionPayDaya['equipment_id'] = 0; 
            $auctionPayDaya['confirm_payment_id'] = 0; 
            $auctionPayDaya['equipment_name'] = ""; 
            $auctionPayDaya['user_id'] = $data['user_id']; 
            $auctionPayDaya['price'] = $data['price']; 
            $auctionPayDaya['currency_type'] = $data['currency_type'];
            $auctionPayDaya['pay_type'] = self::PAY_TYPE_YES; 
            $auctionPayDaya['create_time'] = time();
            if($data['currency_type'] == self::CURRENCY_TYPE_GLOD) {
                $auctionPayDaya['confirm_status'] = self::CONFIRM_STATUS_TEAM_MENBER;
            }
            if($data['currency_type'] == self::CURRENCY_TYPE_MONEY) {
                $auctionPayDaya['confirm_status'] = self::CONFIRM_STATUS_MONEY;
            }
            $auction_pay  = model('auction_pay')->save($auctionPayDaya);
            //修改团的收支金额
            $team = model('team')->where(['id'=>$data['team_id']])->find();
          
            if($data['currency_type'] == self::CURRENCY_TYPE_MONEY){ //用钱购买
                if($user['balance']<$data['price']){
                    return ajax_return_adv_error('你户余额不足,请充值');
                }
                //扣钱
                $user->balance -= $data['price'];
                $user_result = $user->save();
              
                //记录扣钱日志
                $user_moneyLog = model('UserMoneyLog')->data([
                    'user_id' => $data['user_id'],
                    'amount' => -$data['price'],
                    'type' => 0,
                    'msg' => '购买地板' ,
                    'controller' => 'auction_pay',
                    'action' => 'buy'
                ])->save();
              
                $team->amount += $data['price'];
                $teamResult = $team->save();
                $team_member = model('team_member')->where(['team_id'=>$data['team_id'],'user_id'=>$data['user_id']])->where('is_floor','neq',1)->update(['is_floor'=> 1]);
               
                if(!$user_result || !$user_moneyLog || !$teamResult || !$team_member){
                    // 回滚事务
                    Db::rollback();
                    return ajax_return_adv_error('购买地板失败');
                }

            }
           // $team_member = model('team_member')->where(['team_id'=>$data['team_id'],'user_id'=>$data['user_id']])->where('identity','neq',TeamMember::IDENTITY_TEAM_MEMBER)->update();
            if ($auction_pay){
              
                 // 提交事务
                Db::commit();
                return ajax_return('地板购买成功');
                
            }
            // 回滚事务
            Db::rollback();
            return ajax_return('购买地板失败');
            
            
        }else{ //拍卖装备
            
            $payment = model('confirm_payment')->where(['id'=>$data['confirm_payment_id'],'status'=> ConfirmPayment::STATUS_ON,'user_id'=>$data['user_id']])->find();
            if (!$payment) {
                return ajax_return_adv_error('确认订单不存在无法支付');
            }
            $auction_log = model('auction_log')->where(['id'=>$data['auction_log_id'],'team_id'=>$data['team_id'],'user_id'=>$data['user_id']])->find();
            if (!$auction_log){
                return ajax_return_adv_error('竞拍记录不存在无法支付');
            }

            Db::startTrans();
            $auctionEquipmentModel=model('auction_equipment');
            $equipment = $auctionEquipmentModel->where(['id'=>$auction_log['auction_equipment_id'],'team_id'=>$data['team_id']])->find();
            if (!$equipment) {
               return ajax_return_adv_error('竞拍活动不存在');
            }
            if ($equipment['type'] == AuctionEquipment::TYPE_IN_TRANSACTION){
               return ajax_return_adv_error('该装备正在竞拍中无法支付');
            } 
            if ($equipment['type'] == AuctionEquipment::TYPE_SUCCESSFUL_TRANSACTION){
               return ajax_return_adv_error('该装备正已交易无法支付');
            }
            if ($equipment['type'] == AuctionEquipment::TYPE_STREAM_SHOT){
               return ajax_return_adv_error('该装备正已流拍');
            }
            if ($payment['pay_end_time']<time()){
                // return ajax_return_adv_error('已过最后的付款时间无法,进行支付');
            }
            $auctionPayDaya['team_id'] = $data['team_id']; 
            $auctionPayDaya['equipment_id'] = $data['equipment_id']; 
            $auctionPayDaya['confirm_payment_id'] = $data['confirm_payment_id']; 
            $auctionPayDaya['equipment_name'] = $data['equipment_name']; 
            $auctionPayDaya['user_id'] = $data['user_id']; 
            $auctionPayDaya['price'] = $data['price']; 
            if($data['currency_type'] == self::CURRENCY_TYPE_GLOD) {
                $auctionPayDaya['confirm_status'] = self::CONFIRM_STATUS_TEAM_MENBER;
                 $auctionPayDaya['pay_type'] = self::PAY_TYPE_YES; 
            }
            if($data['currency_type'] == self::CURRENCY_TYPE_MONEY) {
                $auctionPayDaya['confirm_status'] = self::CONFIRM_STATUS_MONEY;
                $auctionPayDaya['pay_type'] = self::PAY_TYPE_NO; 
            }
            $auctionPayDaya['currency_type'] = $data['currency_type'];
           
            $auctionPayDaya['create_time'] = time();

            $auction_pay  = model('auction_pay')->save($auctionPayDaya); //未支付

            /**进行支付的步数*/

            
            if($auction_pay){
                $time = time();
                //关闭显示确认
                $payment->status = ConfirmPayment::STATUS_OFF;
                $paymentResult = $payment->save();
                $auctionPayResult = model('auction_pay')->where($auctionPayDaya)->update(['pay_time'=> $time,'pay_type'=>self::PAY_TYPE_YES]);
                //修改团的收支金额
                $team = model('team')->where(['id'=>$data['team_id']])->find();
                if($equipment['currency_type'] == self::CURRENCY_TYPE_GLOD){
                    //$team->gold_coin += $data['price'];
                    $type = AuctionEquipment::TYPE_OF_CHECK;
                }
                if($equipment['currency_type'] == self::CURRENCY_TYPE_MONEY){
                    if($user['balance']<$data['price']){
                        return ajax_return_adv_error('你户余额不足,请充值');
                    }
                    //扣钱
                    $user->balance -= $data['price'];
                    $user_result = $user->save();
                    //记录扣钱日志
                    $user_moneyLog = model('UserMoneyLog')->data([
                        'user_id' => $data['user_id'],
                        'amount' => -$data['price'],
                        'type' => 0,
                        'msg' => '购买装备' . $auction_pay['equipment_name'],
                        'controller' => 'auction_pay',
                        'action' => 'buy'
                    ])->save();
                      $team->amount += $data['price'];
                      $teamResult = $team->save();
                  	
                 $type = AuctionEquipment::TYPE_SUCCESSFUL_TRANSACTION;
                    if(!$user_result && !$user_moneyLog && !$teamResult){
                        
                        // 回滚事务
                        Db::rollback();
                        return ajax_return_adv_error('购买失败');
                    }
                  
                }
                
                //修改准备表信息
                $actionResult =$auctionEquipmentModel->where(['id'=>$auction_log['auction_equipment_id'],'team_id'=>$data['team_id'],'type'=>AuctionEquipment::TYPE_OF_CREATE])
                        ->update(['type'=>$type,'user_id'=>$data['user_id']]);
                if($actionResult && $paymentResult && $auctionPayResult){

                    // 提交事务
                    Db::commit();
                   return ajax_return([],'操作成功');
                }


            }
            // 回滚事务
            Db::rollback();
            return ajax_return_adv_error('购买失败');
        }

    }
    
    /**团长确认*/
    public function review($order_id)
    {
       $auction_pay = model('auction_pay')->where(['id'=>$order_id,'pay_type'=> self::PAY_TYPE_YES,'currency_type'=> self::CURRENCY_TYPE_GLOD])->find();
       if ($auction_pay){
            Db::startTrans();
           $team = model('team')->where(['id'=>$auction_pay['team_id']])->find();
           $team->gold_coin += $auction_pay->price;
           $team_member = 1;
           $auction_equipment_result = 1;
           if ($auction_pay['equipment_id'] == 0){ //成为地板
               
               $team_member = model('team_member')->where(['team_id'=>$auction_pay['team_id'],'user_id'=>$auction_pay['user_id']])->where('is_floor','neq',1)->update(['is_floor'=> 1]);
               
           }else if($auction_pay['equipment_id'] > 0){
             $payment  = model('confirm_payment')->where(['id'=>$auction_pay['confirm_payment_id']])->find();
             $auction_equipment_result = model('auction_equipment')->where(['id'=>$payment['auction_equipment_id']])->update(['type'=> AuctionEquipment::TYPE_SUCCESSFUL_TRANSACTION]);
           }
           $result  = $team->save();
           $auction_pay->confirm_status = self::CONFIRM_STATUS_TEAM;
           $result = $auction_pay->save();
           if($result && $team_member && $auction_equipment_result && $result){
                   
                Db::commit();
               return ajax_return([],'操作成功');
           }
       }
        // 回滚事务
        Db::rollback();
        return ajax_return_adv_error('操作失败');
    }
    /**交易成功*/
    public function payOrder($confirm_payment_id){
        
    }
    
    /**检查是否审核已购买*/
    public function checkReview($user_ids,$confirm_payment_id)
    {
        $data['confirm_status'] = self::CONFIRM_STATUS_TEAM_MENBER;
        $data['currency_type'] = self::CURRENCY_TYPE_GLOD;
        $data['pay_type'] = self::PAY_TYPE_YES;
        $result = $this->field('id,confirm_payment_id')->where('user_id','in',$user_ids)->where('confirm_payment_id','in',$confirm_payment_id)->where($data)->order('id desc')->select();
        if(!$result) return $result;
        $result = $result->toArray();
        $result = array_columns($result, "id","confirm_payment_id");
        return $result;
    }
}
