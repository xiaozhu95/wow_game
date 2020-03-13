<?php
namespace app\admin\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\admin\Controller;
use think\Loader;



class User extends Controller
{
    use \app\admin\traits\controller\Controller;

    // 方法黑名单
    protected static $blacklist = [];

    protected function filter(&$map)
    {
        if (isset($map['tel'])) {
            $map['user.tel'] = ["like", $this->request->param("tel") . "%"];
            unset($map['tel']);
        }
        if (isset($map['nickname'])) {
            $map['user.nickname'] = ["like", "%" . $this->request->param("nickname") . "%"];
            unset($map['nickname']);
        }
        if(isset($map['parent_id'])){
            $map['user.parent_id'] = $map['parent_id'];
            unset($map['parent_id']);
        }
        if(isset($map['type'])){
            $map['user.type'] = $map['type'];
            unset($map['type']);
        }
        $map['_relation'] = "parentUser";
    }

	
	public function ajaxSearch(){
        $id = $this->request->param('id');
        if($id){
            return json($this->getModel()->where(['id'=>$id])->select());
        }
        $keyword = $this->request->param('keyword');
        if($keyword){
            return json($this->getModel()->field("id,nickname,tel")->where("nickname like '%".$keyword."%' or tel like '%".$keyword."%' or wechat_nickname like '%".$keyword."%'")->limit(10)->select());
        }
    }
	
	/**
     * 修改密码
     */
    public function password()
    {
        $id = $this->request->param('id/d');
        if ($id && $this->request->isPost()) {
            $password = $this->request->post('password');
            if (!$password) {
                return ajax_return_adv_error("密码不能为空");
            }
            if (false === Loader::model('User')->updatePassword(['id'=>$id], $password)) {
                return ajax_return_adv_error("密码修改失败");
            }
            return ajax_return_adv("密码已修改为{$password}", '');
        } else {
            return $this->view->fetch();
        }
    }
    
}
