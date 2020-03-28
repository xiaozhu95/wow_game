<?php
namespace app\api\controller;

use app\api\Controller;

class Team extends Controller
{
    use \app\api\traits\controller\Controller;

    /**
     * @return \think\response\Json
     * �����������ţ�
     */
    public function startTeam ()
    {
        $teamId = input("post.team_id");
        $userId = input("post.user_id");
        $teamMemberMode = $this->getModel("TeamMember");

        $teamLeader = $teamMemberMode->checkTeamLeaderIdentity($teamId, $userId);
        if ($teamLeader) {
            return $teamMemberMode->teamLeaderDissolutionTeam($teamId);
        } else {
            $result = [
                'code' => 1,
                'msg' => "�㲻�Ǳ����ų�����Ȩ����!"
            ];
            return json($result);
        }
    }


}