<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class AdvType extends Model
{
    // 指定表名,不含前缀
    protected $name = 'adv_type';
    protected static function init() {
        AdvType::event( 'after_insert', function ( $data ) {
            AdvType::resetCache();
        } );

        AdvType::event( 'after_update', function ( $data ) {
            AdvType::resetCache();
        } );
    }

    public static function resetCache(){
        Cache::rm('adv_type');
        AdvType::cache();
    }

    public static function cache(){
        $data = Cache::remember('adv_type',function() {
			return AdvType::where('status',1)->order('id desc')->select();
        }, 0);
        
        return $data;
    }
}
