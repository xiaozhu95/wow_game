<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class Subject extends Model
{
    // 指定表名,不含前缀
    protected $name = 'subject';
    protected static function init() {
        Subject::event( 'after_insert', function ( $data ) {
            Subject::resetCache();
        } );

        Subject::event( 'after_update', function ( $data ) {
            Subject::resetCache();
        } );
    }

    public static function resetCache(){
        Cache::rm('subject');
        Subject::cache();
    }

    public static function cache(){
        $data = Cache::remember('subject',function() {
            $arr = Subject::where('status',1)->select();
            foreach($arr as $v){
                $data[$v['id']] = $v;
            }
			return $data;
        }, 0);
        
        return $data;
    }

    protected function getExtraAttr($value,$data)
    {
        return json_decode(htmlspecialchars_decode($value), true) ?: $value;
    }

    protected function getExtraTextAttr($value,$data) {
        return $data['extra'];
    }
}
