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
       
        $list = $this->alias('equipment')
                ->join('user u','u.id=equipment.user_id')
                ->where("auction_equipment_id",'in',$ids)->field('auction_equipment_id,max(price) as price,u.id,u.nickname,u.avatar')->group("auction_equipment_id")->select();

        if($list){
             $list = $list->toArray();
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
