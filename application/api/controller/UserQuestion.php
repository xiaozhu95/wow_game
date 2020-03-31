<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class UserQuestion extends Controller
{
    use \app\api\traits\controller\Controller;

    protected function filter(&$map)
    {

    }


    public function addQusetion ()
    {
        $params = input("post.");
        $model = $this->getModel();

        return $model->addQusetion( $params['content'],$params['user_id']);
    }
}
