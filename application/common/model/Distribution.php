<?php
namespace app\common\model;

use think\Db;
use think\Model;
use think\Cache;

class Distribution extends Model
{
    // 指定表名,不含前缀
    protected $name = 'distribution';

    // 0-开始，1-开始投票，2-同意，3-失败
    const STATUS_START = 0;
    const STATUS_START_VOTE = 1;
    const STATUS_AGREE = 2;
    const STATUS_FAIL = 3;

    /**
     * @param $teamid
     * @return array|false|\PDOStatement|string|Model
     * 查询该团是否分配信息
     */
    public function distributionInfo ($teamid)
    {
        return $this->where(['team_id' => $teamid])->where(["status" => Distribution::STATUS_START])->find();
    }

    /**
     * @param $teamid
     * @return array|false|\PDOStatement|string|Model
     * 获取该团是否分配信息
     */
    public function distributionDetail ($teamid, $userId)
    {
        $findResult = $this->field("content, status, create_time")->where(['team_id' => $teamid])->find()->toArray();
        $content = json_decode($findResult["content"], true);
        $array = array();
        $agreeNum = 0;
        $disagreeNum = 0;
        foreach ($content as $key => $value) {
            // 统计支持和失败的人数
            if (isset($value["vote_status"]) && $value["vote_status"] == 1) {    // 1-支持，2-反对
                $agreeNum += 1;
            } elseif (isset($value["vote_status"]) && $value["vote_status"] == 2) {
                $disagreeNum += 1;
            }
            if ($value["userId"] == $userId) {
                array_unshift($array,$value);
            } else {
                array_push($array, $value);
            }
        }
        if ($findResult["status"] != Distribution::STATUS_AGREE || $findResult["status"] != Distribution::STATUS_FAIL) {
            if (time() > strtotime($findResult['create_time']) + 10 * 60 ) {
                $agreeRate = bcdiv($agreeNum, count($content));
                $result = $this->where(['team_id' => $teamid])->find();
                if ($agreeRate > 0.75) {
                    $result->status = Distribution::STATUS_AGREE;
                } else {
                    $result->status = Distribution::STATUS_FAIL;
                }
                Db::startTrans();
                try {
                    $result->save();
                    $team = Team::where("id", $teamid)->where("isdel","<>", 2)->find();
                    $team->isdel = Team::IS_DEL_CLOSE;
                    $room = Room::where(["id" => $team->room_id])->find();
                    $room->status = Room::ROOM_STATUS_CLOSE;
                    $team->save() ;
                    $room->save();

                    foreach ($content as $contentKey => $contentValue) {
                        $user = User::where(["id" => $contentValue["userId"]])->find();
                        $user->balance = $user->balance + $contentValue["money"];
                        $user->save();
                        //记录扣钱日志
                       model('UserMoneyLog')->data([
                            'user_id' => $contentValue["userId"],
                            'amount' => -$contentValue["money"],
                            'type' => 0,
                            'msg' => '账目分钱' ,
                            'controller' => 'distribution',
                            'action' => 'distributionDetail'
                        ])->save();
                    }
                    //Db::commit();
                } catch (\Exception $e) {
                    $this->rollback();
                }

            }
        }
        return json([
            'code' => 0,
            'msg' => 'success',
            'status' => $findResult["status"],
            'data' => $array,
            'voteDate' => [
                "agreeNum" => $agreeNum,
                "disagreeNum" => $disagreeNum,
            ],
        ]);
    }

    /**
     * @param $params
     * @return \think\response\Json
     * 进行投票
     */
    public function startVote ($params)
    {
        $allParams = $params["params"];
        $team_id = 0;

        // 获取团id
        if (is_array($allParams[0]["userInfo"])) {
            $team_id = $allParams[0]["userInfo"]["team_id"];
        }

        $distributionInfo = $this->where(["team_id" => $team_id])->where("status" ,"in", [Distribution::STATUS_START, Distribution::STATUS_START_VOTE])->find();
        if (empty($distributionInfo)) {
            $result = [
                'code' => 0,
                'msg' => "不存在分配方式"
            ];
            return json($result);
        }
        $distributionInfo->content = json_encode($allParams);
        $distributionInfo->status = Distribution::STATUS_START;
        if ($distributionInfo->save()) {
            $result = [
                'code' => 0,
                'msg' => "投票成功"
            ];
        } else {
            $result = [
                'code' => 1,
                'msg' => "投票失败!"
            ];
        }
        return json($result);
    }
}
