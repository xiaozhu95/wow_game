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


    /**
     * @param $userInfo
     * @param $room_id
     * @return mixed
     * 生成团信息
     */
    public function add ($userInfo, $room_id)
    {
        $otherInfo = [
            'create_time' => time(),
            'update_time' => time(),
            'room_id' => $room_id,
            'gold_coin' => 0,
            'amount' => 0,
            'isdel' => Team::IS_DEL_CREATE,
        ];

        $this->data(array_merge($userInfo, $otherInfo))->save();

        return $this->id;
    }

    /**
     * @param $teamId
     * @param $teamLeaderId
     * @param $teamLeaderRoleId
     * @return array|false|\PDOStatement|string|Model
     * 审核团长身份和返回该团的信息
     */
    public function checkTeamLeaderIdentity ($teamId, $teamLeaderId, $teamLeaderRoleId)
    {
        return $this->where(["id" => $teamId, 'user_id' => $teamLeaderId, 'role_id' => $teamLeaderRoleId])->find();
    }


    /**
     * @param $teamId
     * @return \think\response\Json
     * 开启副本
     */
    public function startTeam ($teamId)
    {
        $resultSave = $this->where(["team_id" => $teamId])
            ->update([
                'isdel' => Team::IS_DEL_OPEN
            ]);
        if ($resultSave) {
            $result = [
                'code' => 0,
                'msg' => '开启成功!'
            ];
        } else {
            $result = [
                'code' => 1,
                'msg' => '开启失败,请重试!'
            ];
        }
        return json($result);
    }

    /**
     * @param $teamId
     * @return array|false|\PDOStatement|string|Model
     * 获取团状态
     */
    public function teamStatus ($teamId)
    {
        return $this->field("id, room_id, isdel")->where(["id" => $teamId])->find();
    }

    public function teamCheck($data)
    {
        return $this->field('id,user_id')->where($data)->find();
    }
}
