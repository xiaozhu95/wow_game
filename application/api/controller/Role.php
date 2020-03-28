<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
use think\Config;

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
    $role_list = $data->toArray();
  
    $newData = [];
    $colors = Config::get('colors');
    foreach ($role_list['data'] as $key => $value) {
          $newData[$value['occupation_id']] ['name'] = $value['occupation_name'];
          $newData[$value['occupation_id']] ['color'] = isset($colors[$value['occupation_name']]) ? $colors[$value['occupation_name']] : '';
    	  $newData[$value['occupation_id']] ['list'][] = $value;
    }
    $role_list['data'] = $newData;
    $data = $role_list;
  }
}
