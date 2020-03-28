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

        $teamInfo = $teamMode->teamStatus($teamId);    // 团信息
        $teamMemberInfo = $teamMemberMode->teamMemberStatus($teamId, $userId);    // 团员信息
        $auctionFloorInfo = $auctionFloorMode->getAuctionFloor($teamId);    // 地板信息
        $auctionFloorBuyInfo = [];    // 地板购买信息

        $result = [
            'code' => 0,
            'msg' => "success",
            'data' => [
                "teamInfo" => $teamInfo,
                "teamMemberInfo" => $teamMemberInfo,
                "auctionFloorInfo" => $auctionFloorInfo,
                "auctionFloorBuyInfo" => $auctionFloorBuyInfo,
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
}