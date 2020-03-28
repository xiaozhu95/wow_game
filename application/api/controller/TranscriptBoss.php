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
    // 杂项、设计图、锻造图、配方、垃圾、方程、消耗品、施法材料、图样、书籍
    // 获取所有副本
    public function transcriptAndBoos ()
    {
        $parent_id = input("parent_id/d", 0);
        $model = $this->getModel();

        if ($parent_id) {
            $boss = $model->getChildren($parent_id);
            $explodeStr = "英文：";
            if (!empty($boss)) {
                foreach ($boss as $key => $value) {
                    $bossArms = new \app\common\model\BossArms();
                    $equipment = $bossArms->getList($value->id);    // $boss[$key]["equipment"]

                    foreach ($equipment as $equipmentKey => $equipmentValue) {
                        $equipmentName = explode($explodeStr, $equipmentValue["name"]);
                        $equipment[$equipmentKey]["equipmentChineseName"] = substr($equipmentName[0], 9);
                        $equipment[$equipmentKey]["equipmentEnglishName"] = $equipmentName[1];
                    }
                    $boss[$key]["equipment"] = $equipment;
                }
            }
            return json($boss);
        } else {
            return json($model->getChildren($parent_id));
        }

    }

}
