<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
/**
 * 副本名称
 * 副本下的boss
 */
class TranscriptBoss extends Controller
{
	use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {
      
  }

  protected function aftergetList(&$data){

  }

    // 获取所有副本
    public function transcriptAndBoos ()
    {
        $parent_id = input("parent_id/d", 0);
        $model = $this->getModel();

        if ($parent_id) {
            $boss = $model->getChildren($parent_id);
            if (!empty($boss)) {
                foreach ($boss as $key => $value) {
                    $bossArms = new \app\common\model\BossArms();
                    $boss[$key]["equipment"] = $bossArms->getList($value->id);
                }
            }
            return json($boss);
        } else {
            return json($model->getChildren($parent_id));
        }

    }

}
