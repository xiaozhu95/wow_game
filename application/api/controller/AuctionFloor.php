<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

/**
 * 竞拍底板
 */
class AuctionFloor extends Controller
{
    
    use \app\api\traits\controller\Controller;

}
