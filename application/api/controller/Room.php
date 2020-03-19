<?php
namespace app\api\controller;

use app\api\Controller;

class Room extends Controller
{
    use \app\api\traits\controller\Controller;

    // 创建房间
    public function createRoom ()
    {
        $params = input("post.");
        $model = $this->getModel();
        return $model->add($params);
    }

    // 生成房间号
    public function createRoomNumber ()
    {
        $params = input("post.");
        $model = $this->getModel();
        return $model->createRoomNumber($params);
    }

}