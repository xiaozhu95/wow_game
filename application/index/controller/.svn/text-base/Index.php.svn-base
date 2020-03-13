<?php
namespace app\index\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\index\Controller;

class Index extends Controller {
	use \app\index\traits\controller\Controller;
  
    public function index(){
        // return redirect('http://www.delight-me.cn',302);
		// //判断 如果为手机 跳转到手机站
		// if(is_mobile())
		// 	return redirect('http://m.zhixingshang.com'.$_SERVER['REQUEST_URI'],302);
		
  	    return $this->view->fetch();
	}

}