<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;

class Article extends Controller
{
    use \app\api\traits\controller\Controller;
	
    protected function filter(&$map)
    {
		$category_id = $this->request->param('category_id/d',1);
		$map['status'] = 1;
        $map['category_id'] = $category_id;
		$map['_field'] = "id,title,content,content as view,pic,create_time";
        $map['_order_by'] = 'sort asc, id desc';
        
		$map['_cache'] = true;
    }

    protected function aftergetList(&$data){
        foreach($data as &$v){
            $v['pic_url'] = $v['pic_url'];
            $v['view'] = $v['desc'];
        }
    }

	public function detail(){
		$id = $this->request->param('id/d');
		if($id){

			$model = $this->getModel();
			return json($model->field("content,title,pic")->where('id='.$id)->find());
		}else
      return 'invalid request';
	}




}
