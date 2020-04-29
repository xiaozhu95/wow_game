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
		if ($this->request->param("mobile")) {
            $map['user.mobile'] = ['like', $this->request->param("mobile").'%'];
        }
		if ($this->request->param("nickname")) {
            $map['user.nickname'] = $this->request->param("nickname");
        }
        if ($this->request->param("trade_no")) {
            $map['trade_no'] = $this->request->param("trade_no");
        }
        $map['_relation'] = "user";
        $map['_order_by'] = 'update_time desc';
        $time = strtotime(date('Y-m-d'));
        $start_time = date('Y-m-d',$time);
        $end_time = date('Y-m-d',$time + 86400);
        if ($today = $this->request->param("start_time")) {
            $start_time = $today;
        }
        if ($tomorrow = $this->request->param("end_time")) {
            $end_time = $tomorrow;
        }

        $this->view->assign('start_time',$start_time);
        $this->view->assign('end_time',$end_time);

        $model = $this->getModel();
        $type_value = 0;
        if ($type=$this->request->param("type")) {
            $type_value = $type_value;
        }
        $total_price = $model->countPayByTime($start_time,$end_time,$type);
        $this->view->assign('total_price',$total_price ? $total_price : '0');
    }

}
