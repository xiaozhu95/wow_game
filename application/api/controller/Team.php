<?php
namespace app\api\controller;

use app\api\Controller;

class Team extends Controller
{
    use \app\api\traits\controller\Controller;

    /**
     * @return \think\response\Json
     * 开启副本（团）
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
                'msg' => "你不是本团团长，无权操作!"
            ];
            return json($result);
        }
    }


}