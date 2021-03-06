<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class AuctionFloor extends Model
{
    // 指定表名,不含前缀
    protected $name = 'auction_floor';

    public function add ($floor_info, $teamId)
    {
        $otherInfo = [
            'create_time' => time(),
            'update_time' => time(),
            'team_id' => $teamId,
        ];

        $this->data(array_merge($floor_info, $otherInfo))->save();
    }

    /**
     * @param $teamId
     * @return array|false|\PDOStatement|string|Model
     * 获取地板信息
     */
    public function getAuctionFloor ($teamId)
    {
        return $this->where(["team_id" => $teamId])->find();
    }

}
