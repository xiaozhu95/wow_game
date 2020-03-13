<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class UserProfile extends Controller
{
	use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {
			$map['user_id'] = 0;
  }


  public function edit()
  {
    $uid = $this->request->param("user_id");
		$sign = $this->request->param("sign");
		if($uid && $sign && password_hash_tp($uid)==$sign){
      define('SKIP_AUTH',true);
      $this->request->post(['_ajax'=>1]);
      return action('admin/user_profile/edit');
		}
  }
}
