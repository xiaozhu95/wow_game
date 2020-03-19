<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

/**
 * 竞拍的价格
 */
class AuctionLog extends Controller
{
    
    use \app\api\traits\controller\Controller;
        

    protected function filter(&$map)
    {
        $map['_relation']="User,BossArms";
    }
    
    
    /**
     * 添加竞拍记录
     */
    public function addAuction()
    {
        define('SKIP_AUTH',true);
        $this->request->post(['_ajax'=>1]);
        return action('admin/auction_log/add');

    }

  protected function aftergetList(&$data){

  }
}
