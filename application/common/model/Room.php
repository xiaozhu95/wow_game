<?php
namespace app\common\model;

use think\Db;
use think\Model;
use think\Cache;

class Room extends Model
{
    const FLOOR_STATUS_OPEN = 1;    // ����floor
    const FLOOR_STATUS_CLOSE = 2;    // �ر�floor

    // �����Ƿ�ر� 1-������2-�ر�
    const ROOM_STATUS_OPEN = 1;    // ����
    const ROOM_STATUS_CLOSE = 2;    // �ر�
    private static $roomNum;    // �����

    // ָ������,����ǰ׺
    protected $name = 'room';

    public function getList($roomId, $teamId)
    {
        $result = $this->where(['id' => $roomId])->find();
        $auctionFloorMode = new AuctionFloor();
        $auctionFloorInfo = $auctionFloorMode->getAuctionFloor($teamId);    // �ذ���Ϣ
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
     * ��ӷ���
     */
    public function add ($params)
    {
        $floor_info = $params["floor_info"];
        $userInfo = $params["user_info"];
        $roomExit = $this->checkUserRoomExit($userInfo["user_id"]);
        if ($roomExit) {
            $result = [
                'code' => 1,
                'msg' => '���Ѿ��ڷ�����!'
            ];
            return json($result);
        }

        Db::startTrans();
        try {
            // ��������
            $otherInfo = [
                'name' => "ϵͳ������",
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
            var_dump(array_merge($params,$otherInfo));exit;
            $this->data(array_merge($params,$otherInfo))->save();

            // ������
            $teamModel = \model("Team");
            $teamId = $teamModel->add($userInfo, $this->id);

            // ����еװ壬���������װ�
            if ($params["floor_status"] == Room::FLOOR_STATUS_OPEN && !empty($floor_info)) {
                $auctionFloorModel = \model("AuctionFloor");
                $auctionFloorModel->add($floor_info, $teamId);
            }
            // ����Ա��Ϣ�������ų���Ϣ
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
            return $e;
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
     * �жϸ��û��Ƿ���ڷ�������Ѿ���������
     */
    public function checkUserRoomExit ($userId)
    {
        $teammember = TeamMember::where("user_id",$userId)
            ->where(["is_del" => TeamMember::IS_DEL_CREATE])
            ->where(["is_sign_out"=> TeamMember::NOT_SIGN_OUT])
            ->where("identity","<>", TeamMember::IDENTITY_TEAM_MEMBER_CONFIRM)
            ->select()->toArray();

        return Team::where("id","in",array_column($teammember, "team_id"))->find();
    }


    /**
     * @param $params
     * @return \think\response\Json
     * ���뷿��
     */
    public function joinRoom ($params)
    {
        $roomInfo = $this->where(['room_num' => $params['room_number']])->find();
        if (empty($roomInfo)) {
            $result = [
                'code' => 1,
                'msg' => "���䲻����!",
            ];
            return json($result);
        }

        if ($params['service_id'] != $roomInfo->service_id || $params['camp_id'] != $roomInfo->camp_id) {
            $result = [
                'code' => 1,
                'msg' => "��ɫ��������Ӫ��ͳһ!",
            ];
            return json($result);
        }

        // ���ݷ�����ҳ���id
        $team = new Team();
        $teamInfo = $team->where(['room_id' => $roomInfo->id])->find();
        // ������
        $teamMemberModel = \model("TeamMember");
        $teamId = $teamMemberModel->add($params["user_info"], $teamInfo->id, TeamMember::IDENTITY_TEAM_MEMBER_CONFIRM);
        if ($teamId) {
            $result = [
                'code' => 0,
                'msg' => "����ɹ�",
                'data' => $teamInfo->id
            ];
        } else {
            $result = [
                'code' => 1,
                'msg' => "�����쳣������������",
            ];
        }

        return json($result);
    }


    /**
     * @param $params
     * @return \think\response\Json
     * ���ɷ����
     */
    public function createRoomNumber ($params)
    {
        $existenceRoomNum = $this->field("room_num")->where(['status' => Room::ROOM_STATUS_OPEN])->select()->toArray();
        Room::$roomNum = array_column($existenceRoomNum, "room_num");
        $roomNumber = $this->createRoomNumberString();

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
     * ���ɷ����ַ���
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
        return $str;
    }

    // ���㲹����ʽ
    public function calculationUserSubsidy ($params)
    {
        $room = new Room();
        $roomInfo = $room->where(["id" => $params['room_id']])->find();
        $team = new Team();
        $teamInfo = $team->where(["id" => $params['team_id']])->find();
        $teamMember = new TeamMember();
        $teamMemberInfo = $teamMember->where(["team_id" => $params['team_id']])->select()->toArray();
        $teamMemberNum = count($teamMemberInfo);

        // currency_type 1-����ң�2-���,  status �������ͣ�1-�ٷֱȣ�2-�̶�����
        if ($params["currency_type"] == 1) {
            $balance = $teamInfo->amount - $roomInfo->expenditure;
            $userDistributionInfo = $this->calculationMoneySubsidy($balance, $params["status"], $params["subsidy"], $teamMemberNum, $teamInfo->gold_coin);
        } elseif ($params["currency_type"] == 2) {
            $balance = $teamInfo->gold_coin - $roomInfo->expenditure;
            $userDistributionInfo = $this->calculationGoldCoinSubsidy($balance, $params["status"], $params["subsidy"], $teamMemberNum);
        }
        $result = $this->userDistributionInfo($userDistributionInfo, $params['team_id']);
        $result = [
            'code' => 0,
            'msg' => "success",
            'data' => $result,
        ];
        return json($result);
    }

    private function calculationMoneySubsidy ($balance, $status, $subsidy, $teamMemberNum, $goldCoin)
    {
        $arrayUserSubsidy = array();
        $notMoneyUserNum = 0;
        $userDistributionInfo = array();
        $i = 0;

        if ($status == 1) {    // 1-�ٷֱ�
            foreach ($subsidy as $subsidyKey => $subsidyValue) {
                $subsidyRate = (int)$subsidyValue["value"] /100;
                foreach ($subsidyValue["user_id"] as $subsidyUserIdKey => $subsidyUserIdValue) {
                    if (array_key_exists($subsidyUserIdValue, $arrayUserSubsidy)) {
                        $arrayUserSubsidy[$subsidyUserIdValue] = $arrayUserSubsidy[$subsidyUserIdValue]  + bcmul($balance , $subsidyRate, 2);
                    } else {
                        $arrayUserSubsidy[$subsidyUserIdValue] = bcmul($balance , $subsidyRate, 2);
                    }
                }
                if ($subsidyValue["name"] == "����Ǯ") {
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
                } else {
                    $userDistributionInfo[$i]["userId"] = $arrayUserSubsidyKey;
                    $userDistributionInfo[$i]["money"] = $everyMoney + $arrayUserSubsidyValue;
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
            return $userDistributionInfo;
        }
        elseif ($status == 2) {    // 2-�̶�����
            foreach ($subsidy as $subsidyKey => $subsidyValue) {
                foreach ($subsidyValue["user_id"] as $subsidyUserIdKey => $subsidyUserIdValue) {
                    if (array_key_exists($subsidyUserIdValue, $arrayUserSubsidy)) {
                        $arrayUserSubsidy[$subsidyUserIdValue] = $arrayUserSubsidy[$subsidyUserIdValue] + (int)$subsidyValue["value"];
                    } else {
                        $arrayUserSubsidy[$subsidyUserIdValue] = $subsidyValue["value"];
                    }
                }
                if ($subsidyValue["name"] == "����Ǯ") {
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
                } else {
                    $userDistributionInfo[$i]["userId"] = $arrayUserSubsidyKey;
                    $userDistributionInfo[$i]["money"] = $everyMoney + $arrayUserSubsidyValue;
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
            return $userDistributionInfo;
        }
    }


    private function calculationGoldCoinSubsidy ($balance, $status, $subsidy)
    {
        if ($status == 1) {

        } elseif ($status == 2) {
            // �������
        }
    }

    public function calculationGoldCoin ($goldCoin, $haveMoneyUserNum)
    {
        return $surplusMoney = bcdiv($goldCoin, $haveMoneyUserNum, 2);
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
}
