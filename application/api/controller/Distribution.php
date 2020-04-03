<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
use think\Config;

class Distribution extends Controller
{
   use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {
      $model = $this->getModel();
      
  }

    /**
     * @return mixed
     * 获取信息
     */
    public function getDistributionDetail ()
    {
        $teamId = input("post.team_id");
        $userId = input("post.user_id");
        $distributionMode = $this->getModel("Distribution");
        return $distributionMode->distributionDetail($teamId, $userId);    // 分配信息
    }

    /**
     * @return mixed
     * 进行投票
     */
    public function startVote ()
    {
        $params = input("post.");

        $model = $this->getModel();
        return $model->startVote($params);
    }

}
