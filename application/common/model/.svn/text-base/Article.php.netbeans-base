<?php

namespace app\common\model;

use think\Model;
use think\Request;

class Article extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $type = [
        'create_time'  =>  'timestamp',
    ];
	
//	public function getCategoryIdTextAttr($value,$data)
//    {
//
//        $article_category = \think\Config::get('article_category');
//
//        return $article_category[$data['category_id']];
//    }	


    protected function getPicUrlAttr($value,$data)
    {
        $value = $data['pic'];
        return strpos($value,'/tmp') === 0 ? Request::instance()->domain().$value : $value;
    }

    protected function getContentAttr($value)
    {
        return htmlspecialchars_decode($value);
    }

    protected function getDescAttr($value, $data){
        return subtext(strip_tags(htmlspecialchars_decode($data['content'])), 30);
    }

    protected function getExtraArrAttr($value,$data)
    {
        return json_decode($data['extra'], true);
    }
    protected function setExtraAttr($value)
    {
        return json_encode($value);
    }
  
	public function articleCategory()
    {
        return $this->hasOne('ArticleCategory','id','category_id')->field('id,name');
    }

}