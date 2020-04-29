<?php
namespace app\admin\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\admin\Controller;
use app\common\model\User;

class Withdraw extends Controller
{
    use \app\admin\traits\controller\Controller;
    // 方法黑名单
    protected static $blacklist = [];

    /**
     * 首页
     * @return mixed
     */
    public function index()
    {
        $model = model('user_money_log');

        // 列表过滤器，生成查询Map对象
        $map = [];



        // 特殊过滤器，后缀是方法名的
        $actionFilter = 'filter' . $this->request->action();
        if (method_exists($this, $actionFilter)) {

            $this->$actionFilter($map);
        }

        // 自定义过滤器
        if (method_exists($this, 'filter')) {


            $this->filter($map);
        }
        $map['action'] = "withdraw";
        $this->datalist($model, $map);
        return $this->view->fetch('user_money_log/index');
    }

    protected function filter(&$map)
    {
        if ($this->request->param("tel")) {
            $map['user.mobile'] = ["like", $this->request->param("tel") . "%"];
            unset($map['tel']);
        }
        if ($this->request->param("tel")) {
            $map['user.nickname'] = ["like", "%" . $this->request->param("nickname") . "%"];
            unset($map['nickname']);
        }
        $map['_relation'] = "user";
    }

}
