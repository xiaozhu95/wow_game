<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class Team extends Model
{
    // 指定表名,不含前缀
    protected $name = 'team';
    const IS_DEL_CREATE = 0;    // 刚创建
    const IS_DEL_OPEN = 1;    // 开启
    const IS_DEL_CLOSE = 2;    // 解散

    // 身份1-团长，2-未确认团员，3-确认团员
    const IDENTITY_TEAM_LEADER = 1;
    const IDENTITY_TEAM_MEMBER = 2;
    const IDENTITY_TEAM_MEMBER_CONFIRM = 3;

    public function add ($userInfo, $room_id, $identity)
    {
        $otherInfo = [
            'create_time' => time(),
            'update_time' => time(),
            'room_id' => $room_id,
            'identity' => $identity,
            'gold_coin' => 0,
            'amount' => 0,
            'isdel' => Team::IS_DEL_CREATE,
        ];

        $this->data(array_merge($userInfo, $otherInfo))->save();

        return $this->id;
    }
}
