<?php
namespace app\admin\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\admin\Controller;
use app\common\model\User;

class UserMoneyLog extends Controller
{
    use \app\admin\traits\controller\Controller;
    // 方法黑名单
    protected static $blacklist = [];

    protected function filter(&$map)
    {
		if ($this->request->param("tel")) {
            $map['user.mobile'] = ['like', $this->request->param("tel").'%'];
        }
		if ($this->request->param("nickname")) {
            $map['user.nickname'] = $this->request->param("nickname");
        }

        $map['_relation'] = "user";
    }
	
}
