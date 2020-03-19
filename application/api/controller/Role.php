<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class Role extends Controller
{
   use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {
      $model = $this->getModel();
      
  }
  
  /**添加用户角色*/
  public function addRole()
  { 
    define('SKIP_AUTH',true);
    $this->request->post(['_ajax'=>1]);
    return action('admin/role/add');
  }

  protected function aftergetList(&$data){

  }
}
