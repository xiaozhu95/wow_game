<?php
namespace app\admin\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\admin\Controller;

class Adv extends Controller
{
    use \app\admin\traits\controller\Controller;
    // 方法黑名单
    protected static $blacklist = [];

    protected static $isdelete = false;

    protected function filter(&$map)
    {
        if ($this->request->param("start_time")) {
            $map['start_time'] = ["like", "%" . $this->request->param("start_time") . "%"];
        }
        if ($this->request->param("end_time")) {
            $map['end_time'] = ["like", "%" . $this->request->param("end_time") . "%"];
        }
    }
}
