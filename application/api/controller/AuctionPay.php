<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class AuctionPay extends Controller
{
	use \app\api\traits\controller\Controller;

  protected function filter(&$map)
  {
   
  }
  /**立即支付*/
  public function pay()
  {
      $data = $this->request->param();
      $model = $this->getModel();
      return $model->apy($data);
  }

    protected function aftergetList(&$data){
   
  }
}