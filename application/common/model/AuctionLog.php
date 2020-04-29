<?php
namespace app\common\model;

use think\Db;
use think\Model;
use think\Cache;

class AuctionLog extends Model
{
    // 指定表名,不含前缀
    protected $name = 'auction_log';
    
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    
    /**竞拍的装备的分类 竞拍的ID*/
    public function auctionType($ids)
    {
       
            $sql = 'select user.id,a.auction_equipment_id,a.price,user.nickname,user.avatar from wow_auction_log a 
join 
(select auction_equipment_id,max(price) price from wow_auction_log group by auction_equipment_id) b
on

a.auction_equipment_id=b.auction_equipment_id and a.price=b.price

JOIN wow_user user

on user.id =a.user_id';
         $list = \think\Db::query($sql);

        if($list){
            
             $list = array_columns($list, "price,id,nickname,avatar", "auction_equipment_id");
             
        }
        return $list;
    }
    
    public function User()
    {
        return $this->hasOne('User','id','user_id')->field('id,nickname,avatar');
    }
    public function BossArms()
    {
        return $this->hasOne('BossArms','id','equipment_id')->field('id,name,icon');
    }
    /**添加竞拍日志*/
    public function addLog($data){
        
        $validate = new \app\common\validate\AuctionLog();
        if (!$validate->check($data)) { //
                return ajax_return_adv_error($validate->getError());
        }
        $list = Db::name("AuctionLog")->field('user_id,price')->where(['id'=>$data['id']])->order('price desc')->find();

        return ajax_return($this->save($data));
    }

    //获取该物品拍卖日志前三
    public function actionThreePrice($auction_equipment_id)
    {
        $model = model('auction_log');
        $list = $model->where(['auction_equipment_id'=>$auction_equipment_id])->order('price desc')->limit(3)->select();

        return $list;
    }

    //获取该物品拍卖日志前五
    public function actionFivePrice($auction_equipment_id)
    {
        $model = model('auction_log');
        $list = $model->where(['auction_equipment_id'=>$auction_equipment_id])->order('price desc')->limit(5)->select();

        return $list;
    }
    /** 检查是否参与过竞拍*/
    public function checkIsAuction($team_id,$auction_equipment_id,$user_id)
    {
       return $this->field("role_id,role_name,price")->where(['team_id'=>$team_id,'auction_equipment_id'=>$auction_equipment_id,'user_id'=>$user_id])->order("price desc")->find();
    }
}
