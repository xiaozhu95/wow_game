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
     * ����Ա����Ŷ�
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
     * ����Ա��˽����Ŷ�
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
     * �û��˳���
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
     * �ų���ɢ��
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
     * �ж��û�����Ϣ
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
     * ������ѯ�����Ƿ�رպ͸��û��Ƿ��߳���
     */
    public function ajaxPullTeamStatusAndUserStatus ()
    {
        $teamId = input("post.team_id");
        $userId = input("post.user_id");
        $teamMode = $this->getModel("Team");
        $teamMemberMode = $this->getModel("TeamMember");
        $auctionFloorMode = $this->getModel("AuctionFloor");

        $teamInfo = $teamMode->teamStatus($teamId);    // ����Ϣ
        $teamMemberInfo = $teamMemberMode->teamMemberStatus($teamId, $userId);    // ��Ա��Ϣ
        $auctionFloorInfo = $auctionFloorMode->getAuctionFloor($teamId);    // �ذ���Ϣ
        $auctionFloorBuyInfo = [];    // �ذ幺����Ϣ

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
     * ��ȡ��ʽ��Ա��Ϣ
     */
    public function getTeamMemberInfo ()
    {
        $teamId = input("get.team_id");
        $teamMemberMode = $this->getModel("TeamMember");

        return $teamMemberMode->getTeamMemberInfo($teamId);    // ��ʽ��Ա��Ϣ
    }
}