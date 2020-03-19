<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class ArticleCategory extends Model
{
    // 指定表名,不含前缀
    protected $name = 'article_category';


    protected function getIconUrlAttr($value,$data)
    {
        $value = $data['icon'];
        return strpos($value,'/tmp') === 0 ? Request::instance()->domain().$value : $value;
    }

    protected static function init() {
        ArticleCategory::event( 'after_insert', function ( $data ) {
            ArticleCategory::resetCache();
        } );
        ArticleCategory::event( 'after_update', function ( $data ) {
            ArticleCategory::resetCache();
        } );
    }

    public static function resetCache(){
        Cache::rm('article_category');
        ArticleCategory::cache();
    }

    public static function cache(){
        $data = Cache::remember('article_category',function() {
            $data = [];
            $arr = ArticleCategory::where('status',1)->select();
            foreach($arr as $v){
                $data[$v['id']] = $v;
            }
			return $data;
        }, 0);
        return $data;
    }
}
