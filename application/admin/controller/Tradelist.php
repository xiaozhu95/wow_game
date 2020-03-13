<?php
namespace app\admin\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\admin\Controller;
use app\common\model\User;

class Tradelist extends Controller
{
    use \app\admin\traits\controller\Controller;
    // 方法黑名单
    protected static $blacklist = [];

    protected static $isdelete = false;

    protected function filter(&$map)
    {
      $map['_order_by'] = 'update_time desc';
		if ($this->request->param("tel")) {
            $map['user_id'] = User::where('tel',$this->request->param("tel"))->value('id');
        }
		if ($this->request->param("nickname")) {
            $map['user_id'] = User::where('nickname',$this->request->param("nickname"))->value('id');
        }
        if ($this->request->param("trade_no")) {
            $map['trade_no'] = $this->request->param("trade_no");
        }
    }

}
