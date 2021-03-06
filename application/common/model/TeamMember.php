<?php
namespace app\common\model;

use think\Db;
use think\Model;
use think\Config;


class TeamMember extends Model
{
    // 1-团长,2-正式团员,3-未审核团员
    const IDENTITY_TEAM_LEADER = 1;
    const IDENTITY_TEAM_MEMBER = 2;
    const IDENTITY_TEAM_MEMBER_CONFIRM = 3;

    // 1-未踢出,2-踢出
    const IS_DEL_CREATE = 1;
    const IS_DEL = 2;

    // 1-未退出,2-退出
    const NOT_SIGN_OUT = 1;
    const IS_SIGN_OUT = 2;

    // 指定表名,不含前缀
    protected $name = 'team_member';

    /**
     * @param $userInfo
     * @param $teamId
     * @param $identity
     * @return mixed
     * 加入进团
     */
    public function add ($userInfo, $teamId, $identity)
    {
        $otherInfo = [
            'create_time' => time(),
            'update_time' => time(),
            'team_id' => $teamId,
            'identity' => $identity,
            'is_sign_out' => TeamMember::NOT_SIGN_OUT,
            'is_del' => TeamMember::IS_DEL_CREATE,
        ];

        $this->data(array_merge($userInfo, $otherInfo))->save();

        return $this->id;
    }

    public function getList ($teamId)
    {
        $list = $this->alias("tm")
            ->field("tm.*, r.*")
            ->where(["tm.team_id" => $teamId])
            ->join("Role r", "tm.role_id=r.id", "right")
            ->where(["tm.is_sign_out" => TeamMember::IS_DEL_CREATE])
            ->where(["tm.is_del" => TeamMember::NOT_SIGN_OUT])
            ->order("tm.identity asc")
            ->select()
            ->toArray();

        // 将团长排位第一，自己排为第二，如果自己是团长则将第二个数据不是团长

        foreach ($list as $key => $value) {
            switch ($value["identity"]) {
                case TeamMember::IDENTITY_TEAM_LEADER:
                    $list[$key]["identityName"] = "团长";
                    break;
                case TeamMember::IDENTITY_TEAM_MEMBER:
                    $list[$key]["identityName"] = "团员";
                    break;
                case TeamMember::IDENTITY_TEAM_MEMBER_CONFIRM:
                    $list[$key]["identityName"] = "未审核";
                    break;
                default :
                    $list[$key]["identityName"] = "未审核团员";
                    break;
            }
        }
        $newData = [];
        $colors = Config::get('colors');
        foreach ($list as $list_key => $list_value) {
            $newData[$list_value['occupation_id']] ['name'] = $list_value['occupation_name'];
            $newData[$list_value['occupation_id']] ['color'] = isset($colors[$list_value['occupation_name']]) ? $colors[$list_value['occupation_name']] : '';
            $newData[$list_value['occupation_id']] ['list'][] = $list_value;
        }
        $result = [
            'code' => 0,
            'msg' => "success",
            "data" => $newData,
            "list" => $list,
        ];
        return json($result) ;
    }

    /**
     * @param $teamId
     * @param $userId    团长id
     * @return array|false|\PDOStatement|string|Model
     * 检查团长身份
     */
    public function checkTeamLeaderIdentity ($teamId, $userId)
    {
        return $this->where(["identity" => TeamMember::IDENTITY_TEAM_LEADER])->where(["team_id" => $teamId])->where(["user_id" => $userId])->find();
    }

    /**
     * @param $teamId
     * @param $teamUserId
     * @return \think\response\Json
     * 将成员踢出团队
     */
    public function removeTeamMember ($teamId, $teamUserId)
    {
        $resultSave = $this->where(["team_id" => $teamId])
            ->where(["user_id" => $teamUserId])
            ->update([
                'is_del' => TeamMember::IS_DEL
            ]);
        if ($resultSave) {
            $result = [
                'code' => 0,
                'msg' => '踢出成功!'
            ];
        } else {
            $result = [
                'code' => 1,
                'msg' => '踢出失败,请重试!'
            ];
        }
        return json($result);
    }

    /**
     * @param $teamId
     * @param $teamUserId
     * @return \think\response\Json
     * 审核成员
     */
    public function checkTeamMember ($teamId, $teamUserId, $checkStatus)
    {
        if ($checkStatus  == 1) {    // 同意加入
            $update = [
                'identity' => 2
            ];
        } elseif ($checkStatus  == 2) {    // 直接踢出
            $update = [
                'is_del' => TeamMember::IS_DEL
            ];
        }
        $resultSave = $this->where(["team_id" => $teamId])
            ->where(["user_id" => $teamUserId])
            ->update($update);
        if ($resultSave) {
            $result = [
                'code' => 0,
                'msg' => '操作成功!'
            ];
        } else {
            $result = [
                'code' => 1,
                'msg' => '操作失败,请重试!'
            ];
        }
        return json($result);
    }

    /**
     * @param $teamId
     * @param $teamUserId
     * @return \think\response\Json
     * 用户退出团
     */
    public function userQuitTeam ($teamId, $teamUserId)
    {
        $resultSave = $this->where(["team_id" => $teamId])
            ->where(["user_id" => $teamUserId])
            ->update([
                'is_sign_out' => TeamMember::IS_SIGN_OUT
            ]);
        if ($resultSave) {
            $result = [
                'code' => 0,
                'msg' => '退出成功!'
            ];
        } else {
            $result = [
                'code' => 1,
                'msg' => '退出失败,请重试!'
            ];
        }
        return json($result);
    }

    /**
     * @param $teamId
     * @param $teamUserId
     * @return \think\response\Json
     * 团长解散团
     */
    public function teamLeaderDissolutionTeam ($teamId, $teamUserId)
    {
        $team = Team::where("id", $teamId)->where("isdel","<>", 2)->where("user_id", $teamUserId)->find();
        if (empty($team)) {
            $result = [
                'code' => 1,
                'msg' => '团队不存在!',
            ];
            return json($result);
        }

        if ($team->gold_coin != 0 || $team->amount != 0) {
            $result = [
                'code' => 1,
                'msg' => '团队存在余额，不能解散!',
            ];
            return json($result);
        }

        Db::startTrans();
        try {
            $team->isdel = Team::IS_DEL_CLOSE;
            $room = new Room();
            $room->status = Room::ROOM_STATUS_CLOSE;
            if ($team->save() && $room->save())  {
                Db::commit();
                $result = [
                    'code' => 0,
                    'msg' => '解散成功!',
                ];
            }
        } catch (\Exception $exception) {
            $result = [
                'code' => 1,
                'msg' => '解散失败，请重试!',
            ];
        }
        return json($result);
    }

    /**
     * @param $teamId
     * @param $userId
     * @return \think\response\Json
     * 判断用户团信息
     */
    public function getUserIdentity ($teamId, $userId)
    {
        $identity =  $this->field("identity")->where(["team_id" => $teamId])
            ->where(["user_id" => $userId])
            ->find();

        $result = [
            'code' => 0,
            'msg' => "success",
            'data' => $identity
        ];

        return json($result);
    }

    // 获取团员状态
    public function teamMemberStatus ($teamId, $userId)
    {
        return $this->field("user_id, team_id, is_del, identity")->where(["team_id" => $teamId])->where(["user_id" => $userId])->find();
    }

    /**
     * @param $teamId
     * @return array|false|\PDOStatement|string|Model
     * 获取团正式用户信息
     */
    public function getTeamMemberInfo ($teamId)
    {
        $teamMemberInfo = $this
            ->where(["team_id" => $teamId])
            ->where("identity","<>", TeamMember::IDENTITY_TEAM_MEMBER_CONFIRM)
            ->where(["is_del"=> TeamMember::IS_DEL_CREATE])
            ->where(["is_sign_out"=> TeamMember::NOT_SIGN_OUT])
            ->select();

        $result = [
            'code' => 0,
            'msg' => "success",
            'data' => $teamMemberInfo
        ];

        return json($result);
    }

    // 获取该用户参加团的信息
    public function getUserTeamInfo ($params)
    {
        $teamRoomInfo = $this
            ->alias('tm')
            ->join("team t", "tm.team_id=t.id", "left")
            ->join("room r", "t.room_id=r.id", "left")
            ->where(["tm.user_id" => $params["user_id"]])
            ->where("tm.identity","<>", TeamMember::IDENTITY_TEAM_MEMBER_CONFIRM)
           ->order('id desc')
            ->select()->toArray();

        $distributionMode = new Distribution();
        foreach ($teamRoomInfo as $key => $value) {
            $teamRoomInfo[$key]["distributionInfo"] = empty($distributionMode->distributionInfo($value["team_id"])) ? 0 : 1 ;    // 分配信息;
        }
        $result = [
            'code' => 0,
            'msg' => "success",
            'data' => $teamRoomInfo
        ];

        return json($result);
    }
    //检查是否地板
    public function checkFloor($data)
    {
        return $this->field('id,user_id,is_floor')->where($data)->find();
    }
}
