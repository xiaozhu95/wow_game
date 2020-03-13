<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
use think\Cache;

class Region extends Controller
{
    use \app\api\traits\controller\Controller;

    protected static $blacklist = ['getlist'];
		
    public function children(){
        $parent_id = $this->request->param("parent_id/d");
        if(!$parent_id) return;

        $model = $this->getModel();
        return json($model->children($parent_id));
    }
}
