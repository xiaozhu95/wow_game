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

    // 添加房间
    public function add ($params)
    {
        $subsidy = '{"currency_type":1,"status":1,"subsidy":[{"name":"指挥","value":2},{"name":"MT","value":2},{"name":"FT","value":2},{"name":"DPS1st","value":2},{"name":"HPS1st","value":2}]}';
        $floor_info = json_decode('{
"currency_type":1,
"price":200,
"add_price":50,
"purple":1,
"blue":1,
"green":1,
"end_time":30,
"pay_end_time":5
}', true);
        $userInfo = json_decode('{
"user_id":1,
"room_id":2,
"user_role_name":"好游戏",
"role_id":1,
"attar":"https://wx.qlogo.cn/mmopen/vi_32/DASx3Yn7B8DNEqSJ16Nf8UBXoq1FJ2BO7wzhdwtrkscZtaZ36z2TKzMrAsp8iaI4LE6f3gVlSkwnsdHjIdDa51Q/132"
}', true);

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
            $this->data(array_merge($params,$otherInfo))->save();

            // 创建团
            $teamModel = \model("Team");
            $teamId = $teamModel->add($userInfo, $this->id, Team::IDENTITY_TEAM_LEADER);

            // 如果有底板，创建拍卖底板
            if ($params["floor_status"] == Room::FLOOR_STATUS_OPEN && !empty($floor_info)) {
                $floor_info = json_decode($floor_info, true);

                $auctionFloorModel = \model("AuctionFloor");
                $auctionFloorModel->add($floor_info, $teamId);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    // 加入房间
    public function joinRoom ()
    {
        $roomNumber = input("room_number", 3);    // 房间号
        $serviceId = input("service_id");    // 服务器id
        $campId = input("camp_id");    // 阵营id
        $roleId = input("role_id");    // 角色id

        $roomInfo = $this->where(['room_num' => $roomNumber])->find();
        if (empty($roomInfo)) {
            $result = [
                'code' => 1,
                'msg' => "房间不存在!",
            ];
            return json($result);
        }

        if ($serviceId != $roomInfo->service_id || $campId != $roomInfo->camp_id) {
            $result = [
                'code' => 1,
                'msg' => "角色服务器阵营不统一!",
            ];
            return json($result);
        }

        $userInfo = json_decode('{
"user_id":1,
"room_id":2,
"user_role_name":"好游戏",
"role_id":1,
"attar":"https://wx.qlogo.cn/mmopen/vi_32/DASx3Yn7B8DNEqSJ16Nf8UBXoq1FJ2BO7wzhdwtrkscZtaZ36z2TKzMrAsp8iaI4LE6f3gVlSkwnsdHjIdDa51Q/132"
}', true);

        // 进入团
        $teamModel = \model("Team");
        $teamId = $teamModel->add($userInfo, $roomInfo->id, Team::IDENTITY_TEAM_MEMBER);
        if ($teamId) {
            $result = [
                'code' => 0,
                'msg' => "加入成功",
            ];
        } else {
            $result = [
                'code' => 1,
                'msg' => "网络异常，请重新输入",
            ];
        }

        return json($result);
    }


    // 生成房间号
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
}
