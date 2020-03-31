<?php
namespace app\common\model;

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
}
