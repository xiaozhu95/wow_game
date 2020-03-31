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
        $distributionMode = $this->getModel("Distribution");

        $teamInfo = $teamMode->teamStatus($teamId);    // ����Ϣ
        $teamMemberInfo = $teamMemberMode->teamMemberStatus($teamId, $userId);    // ��Ա��Ϣ
        $auctionFloorInfo = $auctionFloorMode->getAuctionFloor($teamId);    // �ذ���Ϣ
        $distributionInfo = $distributionMode->distributionInfo($teamId);    // ������Ϣ



        $floor = [];
        $team_info = model('team')->where(['id'=>$teamId])->find();

        $result = model('TeamMember')->checkFloor(['team_id'=>$teamId,'is_floor'=>1]); //�ж��Ƿ��ǵذ�
        $floor['is_floor'] = 0; //���ǵذ�
        if($result){
            $floor['is_floor'] = 1; //�ǵذ�
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
            $floor['is_floor'] = 3; //�Ǵ��ų����
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
            $distributionInfo = 1;    // ��ʾ�Ѿ�����
        } else {
            $distributionInfo = 0;    // ��ʾδ����
        }
        $auctionFloorBuyInfo = [];    // �ذ幺����Ϣ

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
     * ��ȡ��ʽ��Ա��Ϣ
     */
    public function getTeamMemberInfo ()
    {
        $teamId = input("get.team_id");
        $teamMemberMode = $this->getModel("TeamMember");

        return $teamMemberMode->getTeamMemberInfo($teamId);    // ��ʽ��Ա��Ϣ
    }

    /**
     * @return mixed
     * ��ȡ���û��μӵ�����Ϣ
     */
    public function userTeamInfo ()
    {
        $params = input("post.");
        $teamMemberMode = $this->getModel("TeamMember");

        return $teamMemberMode->getUserTeamInfo($params);    // ��ʽ��Ա��Ϣ
    }
}