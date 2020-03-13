<?php
namespace app\index\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\index\Controller;

class Article extends Controller {
	use \app\index\traits\controller\Controller;

	public function test(){
		print_r($this->request);
		// return $this->view->fetch();
	}

}