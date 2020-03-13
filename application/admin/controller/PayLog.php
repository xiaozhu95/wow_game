<?php
namespace app\admin\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\admin\Controller;
use app\common\model\User;

class PayLog extends Controller
{
    use \app\admin\traits\controller\Controller;
    // 方法黑名单
    protected static $blacklist = [];

    protected static $isdelete = false;

    protected function filter(&$map)
    {
		if ($this->request->param("tel")) {
            $map['user.tel'] = ['like', $this->request->param("tel").'%'];
        }
		if ($this->request->param("nickname")) {
            $map['user.nickname'] = $this->request->param("nickname");
        }
        if ($this->request->param("trade_no")) {
            $map['trade_no'] = $this->request->param("trade_no");
        }
        $map['_relation'] = "user,subject";
        $map['_order_by'] = 'update_time desc';
    }

}
