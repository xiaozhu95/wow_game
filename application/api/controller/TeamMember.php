<?php
namespace app\api\controller;

use app\api\Controller;

class TeamMember extends Controller
{
    use \app\api\traits\controller\Controller;

    public function getList ()
    {
        $teamId = input("team_id");
        $userId = input("user_id");
        $teamMemberModel = $this->getModel("TeamMember");
        return $teamMemberModel->getList($teamId, $userId);
    }

    /**
     * @return \think\response\Json
     * 将成员提出团队
     */
    public function removeTeamMember ()
    {
        $teamId = input("post.team_id");
        $userId = input("post.user_id");
        $userLeaderId = input("post.user_leader_id");
        $teamMemberMode = $this->getModel("TeamMember");

        $teamLeader = $teamMemberMode->checkTeamLeaderIdentity($teamId, $userLeaderId);
        if ($teamLeader) {
            return $teamMemberMode->removeTeamMember($teamId, $userId);
        } else {
            $result = [
                'code' => 1,
                'msg' => "notPower"
            ];
            return json($result);
        }
    }

    /**
     * @return \think\response\Json
     * 将成员审核进入团队
     */
    public function checkTeamMember ()
    {
        $teamId = input("post.team_id");
        $userId = input("post.user_id");
        $userLeaderId = input("post.user_leader_id");
        $checkStatus = input("post.check_status");
        $teamMemberMode = $this->getModel("TeamMember");

        $teamLeader = $teamMemberMode->checkTeamLeaderIdentity($teamId, $userLeaderId);
        if ($teamLeader) {
            return $teamMemberMode->checkTeamMember($teamId, $userId, $checkStatus);
        } else {
            $result = [
                'code' => 1,
                'msg' => "notPower"
            ];
            return json($result);
        }
    }

    /**
     * @return mixed
     * 用户退出团
     */
    public function userQuitTeam ()
    {
        $teamId = input("post.team_id");
        $userId = input("post.user_id");
        $teamMemberMode = $this->getModel("TeamMember");

        return $teamMemberMode->userQuitTeam($teamId, $userId);
    }

    /**
     * @return \think\response\Json
     * 团长解散团
     */
    public function teamLeaderDissolutionTeam ()
    {
        $teamId = input("post.team_id");
        $userId = input("post.user_id");
        $teamMemberMode = $this->getModel("TeamMember");

        $teamLeader = $teamMemberMode->checkTeamLeaderIdentity($teamId, $userId);
        if ($teamLeader) {
            return $teamMemberMode->teamLeaderDissolutionTeam($teamId, $userId);
        } else {
            $result = [
                'code' => 1,
                'msg' => "notPower"
            ];
            return json($result);
        }
    }

    /**
     * @return mixed
     * 判断用户团信息
     */
    public function userTeamIdentity ()
    {
        $teamId = input("post.team_id");
        $userId = input("post.user_id");
        $teamMemberMode = $this->getModel("TeamMember");

        return $teamMemberMode->getUserIdentity($teamId, $userId);
    }

    /**
     * @return \think\response\Json
     * 用于轮询该团是否关闭和该用户是否被踢出团
     */
    public function ajaxPullTeamStatusAndUserStatus ()
    {
        $teamId = input("post.team_id");
        $userId = input("post.user_id");
        $teamMode = $this->getModel("Team");
        $teamMemberMode = $this->getModel("TeamMember");
        $auctionFloorMode = $this->getModel("AuctionFloor");
        $distributionMode = $this->getModel("Distribution");

        $teamInfo = $teamMode->teamStatus($teamId);    // 团信息
        $teamMemberInfo = $teamMemberMode->teamMemberStatus($teamId, $userId);    // 团员信息
        $auctionFloorInfo = $auctionFloorMode->getAuctionFloor($teamId);    // 地板信息
        $distributionInfo = $distributionMode->distributionInfo($teamId);    // 分配信息



        $floor = [];
        $team_info = model('team')->where(['id'=>$teamId])->find();

        $result = model('TeamMember')->checkFloor(['team_id'=>$teamId,'is_floor'=>1]); //判断是否是地板
        $floor['is_floor'] = 0; //不是地板
        if($result){
            $floor['is_floor'] = 1; //是地板
            $user = model('user')->field('id,nickname,avatar')->where('id','in',$result['user_id'])->find();

            $role = model('role');
            $role_info =  $role->where(['id'=>$team_info['role_id']])->find();

            $user_info = $role->arrayList(['service_id'=>$role_info['service_id'],'camp_id'=>$role_info['camp_id']],$result['user_id']);
            $floor['user']['id'] = $result['user_id'];
            $floor['user']['nickname'] = isset($user_info[$result['user_id']]['role_name']) ? $user_info[$result['user_id']]['role_name'] : '';
            $floor['user']['avatar'] = isset($user['avatar']) ? $user['avatar'] : "";
        }
        $pay = model('AuctionPay')->where(['team_id'=>$teamId,'confirm_status'=> \app\common\model\AuctionPay::CONFIRM_STATUS_TEAM_MENBER,'currency_type'=> \app\common\model\AuctionPay::CURRENCY_TYPE_GLOD,'pay_type'=> \app\common\model\AuctionPay::PAY_TYPE_YES])->find();
        if($pay){
            $floor['is_floor'] = 3; //是待团长审核
            $floor['order_id'] = $pay['id'];
            $user = model('user')->field('id,nickname,avatar')->where('id','in',$pay['user_id'])->find();
            $role = model('role');
            $role_info =  $role->where(['id'=>$team_info['role_id']])->find();
            $user_info = $role->arrayList(['service_id'=>$role_info['service_id'],'camp_id'=>$role_info['camp_id']],$pay['user_id']);
            $floor['user']['id'] = $result['user_id'];
            $floor['user']['nickname'] = isset($user_info[$pay['user_id']]['role_name']) ? $user_info[$pay['user_id']]['role_name'] : '';
            $floor['user']['avatar'] = isset($user['avatar']) ? $user['avatar'] : "";
        }



        if ($distributionInfo) {
            $distributionInfo = 1;    // 表示已经分配
        } else {
            $distributionInfo = 0;    // 表示未分配
        }
        $auctionFloorBuyInfo = [];    // 地板购买信息

        $result = [
            'code' => 0,
            'msg' => "success",
            'data' => [
                "teamInfo" => $teamInfo,
                "teamMemberInfo" => $teamMemberInfo,
                "auctionFloorInfo" => $auctionFloorInfo,
                "auctionFloorBuyInfo" => $auctionFloorBuyInfo,
                "distributionInfo" => $distributionInfo,
                'floor' => $floor,
            ]
        ];

        return json($result);
    }

    /**
     * @return mixed
     * 获取正式团员信息
     */
    public function getTeamMemberInfo ()
    {
        $teamId = input("get.team_id");
        $teamMemberMode = $this->getModel("TeamMember");

        return $teamMemberMode->getTeamMemberInfo($teamId);    // 正式团员信息
    }

    /**
     * @return mixed
     * 获取该用户参加的团信息
     */
    public function userTeamInfo ()
    {
        $params = input("post.");
        $teamMemberMode = $this->getModel("TeamMember");

        return $teamMemberMode->getUserTeamInfo($params);    // 正式团员信息
    }
}