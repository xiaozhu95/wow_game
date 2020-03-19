<?php
namespace app\api\controller;

use app\api\Controller;

class Room extends Controller
{
    use \app\api\traits\controller\Controller;

    // ��������
    public function createRoom ()
    {
        $params = input("post.");
        $model = $this->getModel();
        return $model->add($params);
    }

    // ���ɷ����
    public function createRoomNumber ()
    {
        $params = input("post.");
        $model = $this->getModel();
        return $model->createRoomNumber($params);
    }

}