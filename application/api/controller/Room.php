<?php
namespace app\api\controller;

use app\api\Controller;

class Room extends Controller
{
    use \app\api\traits\controller\Controller;

    /**
     * @return mixed
     * ��������
     */
    public function createRoom ()
    {
        $params = input("post.");
        $model = $this->getModel();
        return $model->add($params);
    }

    /**
     * @return mixed
     * ���ɷ����
     */
    public function createRoomNumber ()
    {
        $params = input("post.");
        $model = $this->getModel();
        return $model->createRoomNumber($params);
    }

    /**
     * @return mixed
     * ���뷿��
     */
    public function joinRoom ()
    {
        $params = input("post.");
        $model = $this->getModel();
        return $model->joinRoom($params);
    }

    /**
     * @return \think\response\Json
     * �ж��û�����
     */
    public function checkUserRoomExit ()
    {
        $user_id = input("post.user_id");
        $model = $this->getModel();
        if (empty($user_id)) {
            $result = [
                'code' => 1,
                'msg' => 'userNotEmpty!'
            ];
            return json($result);
        }

        $roomExit = $model->checkUserRoomExit($user_id);
        if ($roomExit) {
            $result = [
                'code' => 2,
                'msg' => 'roomExit',
                'data' => $roomExit
            ];
        } else {
            $result = [
                'code' => 0,
                'msg' => 'success!'
            ];
        }
        return json($result);
    }

    /**
     * @return mixed
     * ��������
     */
    public function getlist ()
    {
        $roomId = input("get.room_id");
        $teamId = input("get.team_id");
        $model = $this->getModel();
        return $model->getList($roomId, $teamId);
    }

    /**
     * @return mixed
     * �����û����˵�Ǯ
     */
    public function calculationUserSubsidy ()
    {
        $params = input("post.");

        $model = $this->getModel();
        return $model->calculationUserSubsidy($params);
    }

    /**
     * @return mixed
     *  ȷ�Ϸ���
     */
    public function confirmDistribution ()
    {
        $params = input("post.");

        $model = $this->getModel();
        return $model->confirmDistribution($params);
    }
}