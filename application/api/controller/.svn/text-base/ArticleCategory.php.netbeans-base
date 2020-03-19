<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class ArticleCategory extends Controller
{
    use \app\api\traits\controller\Controller;

    protected function filter(&$map)
    {
        if(!empty($this->request->param('parent_id')))
            $map['parent_id']=$this->request->param('parent_id');
        $map['status'] = 1;
        $map['_order_by'] = 'sort asc, id desc';
        $map['_cache'] = true;
    }

    protected function aftergetList(&$data){
        foreach($data as &$v){
            $v['icon_url'] = $v['icon_url'];
        }
    }

}
