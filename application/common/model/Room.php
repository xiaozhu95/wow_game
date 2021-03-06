<?php
namespace app\common\model;

use think\Db;
use think\Model;
use think\Cache;

class Room extends Model
{
    const FLOOR_STATUS_OPEN = 1;    // 开启floor
    const FLOOR_STATUS_CLOSE = 2;    // 关闭floor

    // 房间是否关闭 1-开启，2-关闭
    const ROOM_STATUS_OPEN = 1;    // 开启
    const ROOM_STATUS_CLOSE = 2;    // 关闭
    private static $roomNum;    // 房间号

    // 指定表名,不含前缀
    protected $name = 'room';

    public function getList($roomId, $teamId)
    {
        $result = $this->where(['id' => $roomId])->find();
        $auctionFloorMode = new AuctionFloor();
        $auctionFloorInfo = $auctionFloorMode->getAuctionFloor($teamId);    // 地板信息
        if ($result) {
            $result["high_dps"] = json_decode(json_decode($result["high_dps"]));
            $result["high_hps"] = json_decode($result["high_hps"]);
            $subsidy = json_decode($result["subsidy"], true);
            $newSubsidy = array();
            $tempArray = array();
            if ($subsidy["subsidy"]) {
                foreach ($subsidy["subsidy"] as $key =>$value) {
                    $tempArray = array_merge($tempArray, $value["list"]);
                }
                $newSubsidy["currency_type"] = $subsidy["currency_type"];
                $newSubsidy["status"] = $subsidy["status"];
                $newSubsidy["subsidy"] = $tempArray;
                $result["subsidy"] = $newSubsidy ;
            }

        }
        $result["floorInfo"] = $auctionFloorInfo;
        $resultData = [
            'code' => 0,
            'msg' => 'success!',
            'data' => $result
        ];
        return json($resultData);
    }
    /**
     * @param $params
     * @return \think\response\Json
     * 添加房间
     */
    public function add ($params)
    {
        $floor_info = $params["floor_info"];
        $userInfo = $params["user_info"];
        $roomExit = $this->checkUserRoomExit($userInfo["user_id"]);
        if ($roomExit) {
            $result = [
                'code' => 1,
                'msg' => '您已经在房间中!'
            ];
            return json($result);
        }

        Db::startTrans();
        try {
            // 创建房间
            $otherInfo = [
                'name' => "系统房间名",
                'create_time' => time(),
                'update_time' => time(),
            ];
            unset($params["floor_info"]);
            unset($params["user_info"]);
            unset($params["token"]);
            if ($params['high_hps']) {
                $params['high_hps'] = json_encode($params['high_hps']);
            }
            if ($params['high_hps']) {
                $params['high_dps'] = json_encode($params['high_hps']);
            }
            if ($params['subsidy']) {
                $params['subsidy'] = json_encode($params['subsidy']);
            }
            $this->data(array_merge($params,$otherInfo))->save();

            // 创建团
            $teamModel = \model("Team");
            $teamId = $teamModel->add($userInfo, $this->id);

            // 如果有底板，创建拍卖底板
            if ($params["floor_status"] == Room::FLOOR_STATUS_OPEN && !empty($floor_info)) {
                $auctionFloorModel = \model("AuctionFloor");
                $auctionFloorModel->add($floor_info, $teamId);
            }
            // 在团员信息中生成团长信息
            $teamMemberModel = \model("TeamMember");
            $teamMemberModel->add($userInfo, $teamId, TeamMember::IDENTITY_TEAM_LEADER);
            Db::commit();
            $result = [
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'team_id' => $teamId,
                ]
            ];
        } catch (\Exception $e) {
            Db::rollback();
            $result = [
                'code' => 1,
                'msg' => 'error'
            ];
        }
        return json($result);
    }

    /**
     * @param $userId
     * @return array|false|\PDOStatement|string|Model
     * 判断该用户是否存在房间里或已经创建房间
     */
    public function checkUserRoomExit ($userId)
    {
        $teammember = TeamMember::where("user_id",$userId)
            ->where(["is_del" => TeamMember::IS_DEL_CREATE])
            ->where(["is_sign_out"=> TeamMember::NOT_SIGN_OUT])
            ->select()->toArray();

        return Team::where("id","in",array_column($teammember, "team_id"))->where("isdel" , "<>", Team::IS_DEL_CLOSE)->find();
    }


    /**
     * @param $params
     * @return \think\response\Json
     * 加入房间
     */
    public function joinRoom ($params)
    {
        $roomInfo = $this->where(['room_num' => $params['room_number']])->find();
        if (empty($roomInfo)) {
            $result = [
                'code' => 1,
                'msg' => "房间不存在!",
            ];
            return json($result);
        }

        if ($params['service_id'] != $roomInfo->service_id || $params['camp_id'] != $roomInfo->camp_id) {
            $result = [
                'code' => 1,
                'msg' => "角色服务器阵营不统一!",
            ];
            return json($result);
        }

        // 根据房间号找出团id
        $team = new Team();
        $teamInfo = $team->where(['room_id' => $roomInfo->id])->find();
        // 进入团
        $teamMemberModel = \model("TeamMember");
        $teamMemberInfo = $teamMemberModel->where(["user_id" => $params["user_info"]["user_id"]])->where(["team_id" => $teamInfo->id])->find();
        if ($teamMemberInfo) {
            $teamMemberInfo->is_del = TeamMember::IS_DEL_CREATE;
            $teamMemberInfo->is_sign_out = TeamMember::NOT_SIGN_OUT;
            $teamMemberInfo->identity = TeamMember::IDENTITY_TEAM_MEMBER_CONFIRM;
            $teamSaveInfo = $teamMemberInfo->save();
        } else {
            $teamSaveInfo = $teamMemberModel->add($params["user_info"], $teamInfo->id, TeamMember::IDENTITY_TEAM_MEMBER_CONFIRM);
        }

        if ($teamSaveInfo) {
            $result = [
                'code' => 0,
                'msg' => "加入成功",
                'data' => $teamInfo->id
            ];
        } else {
            $result = [
                'code' => 1,
                'msg' => "网络异常，请重新输入",
            ];
        }

        return json($result);
    }


    /**
     * @param $params
     * @return \think\response\Json
     * 生成房间号
     */
    public function createRoomNumber ($params)
    {
        $existenceRoomNum = $this->field("room_num")->where(['status' => Room::ROOM_STATUS_OPEN])->select()->toArray();
        Room::$roomNum = array_column($existenceRoomNum, "room_num");
        $roomNumber = (string)$this->createRoomNumberString();

        $result = [
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'roomNumber' => $roomNumber,
                'userInfo' => $params['user_info']
            ]
        ];

        return json($result);
    }

    /**
     * @param int $min
     * @param int $max
     * @param int $num
     * @return string
     * 生成房间字符串
     */
    private function createRoomNumberString  ($min = 0, $max = 9, $num = 6)
    {
        $str = "";
        $i = 0;
        while ($i<$num) {
            $number = mt_rand($min, $max);
            $str .= $number;
            $i++;
        }
        if (in_array($str, Room::$roomNum)) {
            $this->createRoomNumberString();
        }
        return (string)$str;
    }

    // 计算补贴方式
    public function calculationUserSubsidy ($params)
    {
        $room = new Room();
        $roomInfo = $room->where(["id" => $params['room_id']])->find();
        $team = new Team();
        $teamInfo = $team->where(["id" => $params['team_id']])->find();
        $teamMember = new TeamMember();
        $teamMemberInfo = $teamMember->where(["team_id" => $params['team_id']])->select()->toArray();
        $teamMemberNum = count($teamMemberInfo);

        // currency_type 1-金币，2-人民币,  status 补贴类型，1-百分比，2-固定比例
        if (!empty($params["subsidy"])) {
            if ($params["currency_type"] == 1) {
                $balance = $teamInfo->gold_coin - $roomInfo->expenditure;
                $userDistributionInfo = $this->calculationGoldCoinSubsidy($balance, $params["status"], $params["subsidy"],$params["currency_type"], $teamMemberNum, $teamInfo->amount);
            } elseif ($params["currency_type"] == 2) {
                $balance = $teamInfo->amount - $roomInfo->expenditure;
                $userDistributionInfo = $this->calculationMoneySubsidy($balance, $params["status"], $params["subsidy"],$params["currency_type"],  $teamMemberNum, $teamInfo->gold_coin);

            }
        } else {
            if ($params["currency_type"] == 1) {
                $goldCoin = $teamInfo->gold_coin - $roomInfo->expenditure;
                $balance = $teamInfo->amount ;
            } elseif ($params["currency_type"] == 2) {
                $balance = $teamInfo->amount - $roomInfo->expenditure;
                $goldCoin = $teamInfo->gold_coin;
            }
            $userDistributionInfo = $this->averageDistribution($balance, $teamMemberNum, $goldCoin, $teamMemberInfo, $params["currency_type"]);
        }

        $result = $this->userDistributionInfo($userDistributionInfo, $params['team_id']);
        $result = [
            'code' => 0,
            'msg' => "success",
            'data' => $result,
        ];
        return json($result);
    }

    /**
     * @param $balance
     * @param $teamMemberNum
     * @param $goldCoin
     * @param $teamMemberInfo
     * @param $currencyType
     * @return array
     * 没有补贴则对团里的每个进行均分
     */
    private function averageDistribution ($balance, $teamMemberNum, $goldCoin, $teamMemberInfo, $currencyType)
    {
        $everyMoney = bcdiv($balance, $teamMemberNum, 2);
        $everyGold = bcdiv($goldCoin, $teamMemberNum, 2);
        $userDistributionInfo = array();

        foreach ($teamMemberInfo  as  $key =>  $value)  {
            $userDistributionInfo[$key]["userId"] = $value["user_id"];
            $userDistributionInfo[$key]["money"] = $everyMoney;
            $userDistributionInfo[$key]["currency_type"] = $currencyType;
            $userDistributionInfo[$key]["goldGoin"] = $everyGold;
        }

        return $userDistributionInfo;
    }

    private function calculationMoneySubsidy ($balance, $status, $subsidy,$currencyType, $teamMemberNum, $goldCoin)
    {
        $arrayUserSubsidy = array();
        $notMoneyUserNum = 0;
        $userDistributionInfo = array();
        $i = 0;

        if ($status == 1) {    // 1-百分比
            foreach ($subsidy as $subsidyKey => $subsidyValue) {
                $subsidyRate = (int)$subsidyValue["value"] /100;
                foreach ($subsidyValue["user_id"] as $subsidyUserIdKey => $subsidyUserIdValue) {
                    if (array_key_exists($subsidyUserIdValue, $arrayUserSubsidy)) {
                        $arrayUserSubsidy[$subsidyUserIdValue] = $arrayUserSubsidy[$subsidyUserIdValue]  + bcmul($balance , $subsidyRate, 2);
                    } else {
                        $arrayUserSubsidy[$subsidyUserIdValue] = bcmul($balance , $subsidyRate, 2);
                    }
                }
                if ($subsidyValue["name"] == "不分钱") {
                    $notMoneyUserNum = count($subsidyValue["user_id"]);
                }
            }

            $balance = $balance - array_sum($arrayUserSubsidy);
            $haveMoneyUserNum = $teamMemberNum - $notMoneyUserNum;
            $everyMoney = bcdiv($balance, $haveMoneyUserNum, 2);

            foreach ($arrayUserSubsidy as $arrayUserSubsidyKey => $arrayUserSubsidyValue) {
                if ($arrayUserSubsidyValue == 0) {
                    $userDistributionInfo[$i]["userId"] = $arrayUserSubsidyKey;
                    $userDistributionInfo[$i]["money"] = $arrayUserSubsidyValue;
                    $userDistributionInfo[$i]["currency_type"] = $currencyType;
                } else {
                    $userDistributionInfo[$i]["userId"] = $arrayUserSubsidyKey;
                    $userDistributionInfo[$i]["money"] = $everyMoney + $arrayUserSubsidyValue;
                    $userDistributionInfo[$i]["currency_type"] = $currencyType;
                }
                $i++;
            }

            $userEveryOneGoldCoin = $this->calculationGoldCoin($goldCoin, $haveMoneyUserNum);
            foreach ($userDistributionInfo as $userDistributionInfoKey=> $userDistributionInfoValue) {
                if ($userDistributionInfoValue["money"] == 0) {
                    $userDistributionInfo[$userDistributionInfoKey]['goldGoin'] = 0;
                } else {
                    $userDistributionInfo[$userDistributionInfoKey]['goldGoin'] = $userEveryOneGoldCoin;
                }
            }
        }
        elseif ($status == 2) {    // 2-固定比例
            foreach ($subsidy as $subsidyKey => $subsidyValue) {
                foreach ($subsidyValue["user_id"] as $subsidyUserIdKey => $subsidyUserIdValue) {
                    if (array_key_exists($subsidyUserIdValue, $arrayUserSubsidy)) {
                        $arrayUserSubsidy[$subsidyUserIdValue] = $arrayUserSubsidy[$subsidyUserIdValue] + (int)$subsidyValue["value"];
                    } else {
                        $arrayUserSubsidy[$subsidyUserIdValue] = $subsidyValue["value"];
                    }
                }
                if ($subsidyValue["name"] == "不分钱") {
                    $notMoneyUserNum = count($subsidyValue["user_id"]);
                }
            }
            $balance = $balance - array_sum($arrayUserSubsidy);
            $haveMoneyUserNum = $teamMemberNum - $notMoneyUserNum;
            $everyMoney = bcdiv($balance, $haveMoneyUserNum, 2);

            foreach ($arrayUserSubsidy as $arrayUserSubsidyKey => $arrayUserSubsidyValue) {
                if ($arrayUserSubsidyValue == 0) {
                    $userDistributionInfo[$i]["userId"] = $arrayUserSubsidyKey;
                    $userDistributionInfo[$i]["money"] = $arrayUserSubsidyValue;
                    $userDistributionInfo[$i]["currency_type"] = $currencyType;
                } else {
                    $userDistributionInfo[$i]["userId"] = $arrayUserSubsidyKey;
                    $userDistributionInfo[$i]["money"] = $everyMoney + $arrayUserSubsidyValue;
                    $userDistributionInfo[$i]["currency_type"] = $currencyType;
                }
                $i++;
            }

            $userEveryOneGoldCoin = $this->calculationGoldCoin($goldCoin, $haveMoneyUserNum);
            foreach ($userDistributionInfo as $userDistributionInfoKey=> $userDistributionInfoValue) {
                if ($userDistributionInfoValue["money"] == 0) {
                    $userDistributionInfo[$userDistributionInfoKey]['goldGoin'] = 0;
                } else {
                    $userDistributionInfo[$userDistributionInfoKey]['goldGoin'] = $userEveryOneGoldCoin;
                }
            }
        }
        return $userDistributionInfo;
    }

    private function calculationGoldCoinSubsidy ($balance, $status, $subsidy,$currencyType, $teamMemberNum, $goldCoin)
    {
        $arrayUserSubsidy = array();
        $notMoneyUserNum = 0;
        $userDistributionInfo = array();
        $i = 0;

        if ($status == 1) {    // 1-百分比
            foreach ($subsidy as $subsidyKey => $subsidyValue) {
                $subsidyRate = (int)$subsidyValue["value"] /100;
                foreach ($subsidyValue["user_id"] as $subsidyUserIdKey => $subsidyUserIdValue) {
                    if (array_key_exists($subsidyUserIdValue, $arrayUserSubsidy)) {
                        $arrayUserSubsidy[$subsidyUserIdValue] = $arrayUserSubsidy[$subsidyUserIdValue]  + bcmul($balance , $subsidyRate, 2);
                    } else {
                        $arrayUserSubsidy[$subsidyUserIdValue] = bcmul($balance , $subsidyRate, 2);
                    }
                }
                if ($subsidyValue["name"] == "不分钱") {
                    $notMoneyUserNum = count($subsidyValue["user_id"]);
                }
            }

            $balance = $balance - array_sum($arrayUserSubsidy);
            $haveMoneyUserNum = $teamMemberNum - $notMoneyUserNum;
            $everyMoney = bcdiv($balance, $haveMoneyUserNum, 2);

            foreach ($arrayUserSubsidy as $arrayUserSubsidyKey => $arrayUserSubsidyValue) {
                if ($arrayUserSubsidyValue == 0) {
                    $userDistributionInfo[$i]["userId"] = $arrayUserSubsidyKey;
                    $userDistributionInfo[$i]["money"] = $arrayUserSubsidyValue;
                    $userDistributionInfo[$i]["currency_type"] = $currencyType;
                } else {
                    $userDistributionInfo[$i]["userId"] = $arrayUserSubsidyKey;
                    $userDistributionInfo[$i]["money"] = $everyMoney + $arrayUserSubsidyValue;
                    $userDistributionInfo[$i]["currency_type"] = $currencyType;
                }
                $i++;
            }

            $userEveryOneGoldCoin = $this->calculationGoldCoin($goldCoin, $haveMoneyUserNum);
            foreach ($userDistributionInfo as $userDistributionInfoKey=> $userDistributionInfoValue) {
                if ($userDistributionInfoValue["money"] == 0) {
                    $userDistributionInfo[$userDistributionInfoKey]['goldGoin'] = 0;
                } else {
                    $userDistributionInfo[$userDistributionInfoKey]['goldGoin'] = $userEveryOneGoldCoin;
                }
            }
        }
        elseif ($status == 2) {    // 2-固定比例
            foreach ($subsidy as $subsidyKey => $subsidyValue) {
                foreach ($subsidyValue["user_id"] as $subsidyUserIdKey => $subsidyUserIdValue) {
                    if (array_key_exists($subsidyUserIdValue, $arrayUserSubsidy)) {
                        $arrayUserSubsidy[$subsidyUserIdValue] = $arrayUserSubsidy[$subsidyUserIdValue] + (int)$subsidyValue["value"];
                    } else {
                        $arrayUserSubsidy[$subsidyUserIdValue] = $subsidyValue["value"];
                    }
                }
                if ($subsidyValue["name"] == "不分钱") {
                    $notMoneyUserNum = count($subsidyValue["user_id"]);
                }
            }
            $balance = $balance - array_sum($arrayUserSubsidy);
            $haveMoneyUserNum = $teamMemberNum - $notMoneyUserNum;
            $everyMoney = bcdiv($balance, $haveMoneyUserNum, 2);

            foreach ($arrayUserSubsidy as $arrayUserSubsidyKey => $arrayUserSubsidyValue) {
                if ($arrayUserSubsidyValue == 0) {
                    $userDistributionInfo[$i]["userId"] = $arrayUserSubsidyKey;
                    $userDistributionInfo[$i]["money"] = $arrayUserSubsidyValue;
                    $userDistributionInfo[$i]["currency_type"] = $currencyType;
                } else {
                    $userDistributionInfo[$i]["userId"] = $arrayUserSubsidyKey;
                    $userDistributionInfo[$i]["money"] = $everyMoney + $arrayUserSubsidyValue;
                    $userDistributionInfo[$i]["currency_type"] = $currencyType;
                }
                $i++;
            }

            $userEveryOneGoldCoin = $this->calculationGoldCoin($goldCoin, $haveMoneyUserNum);
            foreach ($userDistributionInfo as $userDistributionInfoKey=> $userDistributionInfoValue) {
                if ($userDistributionInfoValue["money"] == 0) {
                    $userDistributionInfo[$userDistributionInfoKey]['goldGoin'] = 0;
                } else {
                    $userDistributionInfo[$userDistributionInfoKey]['goldGoin'] = $userEveryOneGoldCoin;
                }
            }
        }
        return $userDistributionInfo;
    }

    public function calculationGoldCoin ($goldCoin, $haveMoneyUserNum)
    {
        return $surplusMoney = bcdiv($goldCoin, $haveMoneyUserNum, 2);
    }

    public function calculationAmount ($amount, $haveMoneyUserNum)
    {
        return $surplusMoney = bcdiv(amount, $haveMoneyUserNum, 2);
    }

    public function userDistributionInfo ($userDistributionInfo, $teamId)
    {
        $teamMember = new TeamMember();
        foreach ($userDistributionInfo as $key => $value) {
            $teamMemberInfo = $teamMember->where(["user_id" => $value["userId"]])->where(["team_id" => $teamId])->find();
            $userDistributionInfo[$key]["userInfo"] = empty($teamMemberInfo) ? "" : $teamMemberInfo;
        }

        return $userDistributionInfo;
    }

    public function confirmDistribution ($params)
    {
        $allParams = $params["params"];
        $userId= array_column($allParams, "userId");
        $team_id = 0;

        // 获取团id
        if (is_array($allParams[0]["userInfo"])) {
            $team_id = $allParams[0]["userInfo"]["team_id"];
        }

        $distribution = new Distribution();
        $distribution->content = json_encode($allParams);
        $distribution->status = Distribution::STATUS_START;
        $distribution->team_id = $team_id;
        $distribution->create_time = time();
        $saveResult = $distribution->save();
        if ($saveResult) {
            $userModel = new User();
            $userMobile = $userModel->field("mobile")->where("id", "in", $userId)->select()->toArray();
            $userMobile = array_column($userMobile, "mobile");

            $sms = new Sms();
            foreach ($userMobile as $value) {
                //$sms->sendsms($value, "【杭州异构科技】提醒：请到魔兽团吧小程序-我的--团账单中确认分账方式，请在您在十分钟内确认，如未确认，默认同意该团长的分账方式", "", "");
            }
            $result = [
                'code' => 0,
                'msg' => "分配成功"
            ];
        } else {
            $result = [
                'code' => 1,
                'msg' => "分配失败!"
            ];
        }
        return json($result);
    }
}
