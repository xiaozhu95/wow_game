<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
use app\crontab\EquipmentStreaming;
use app\crontab\Distribution;

class Crontab extends Controller
{
    use \app\api\traits\controller\Controller;
  
    public function doWorker()
    {
        $steaming = new EquipmentStreaming();
        $steaming->worker();
    }

    public function doDistributionWorker ()
    {
        $steaming = new Distribution();
        $steaming->worker();
    }
}
