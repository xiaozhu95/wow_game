<?php
namespace app\admin\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\admin\Controller;

class Adv extends Controller
{
    use \app\admin\traits\controller\Controller;
    // 方法黑名单
    protected static $blacklist = [];

    // public  function  type(){
    //     $list=model("AdvType")->where("status = 1")->field('id,name')->select();
    //     return json_encode($list);
    // }



    
}
