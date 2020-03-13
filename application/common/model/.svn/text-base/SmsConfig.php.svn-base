<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class SmsConfig extends Model
{
    // 指定表名,不含前缀
    protected $name = 'sms_config';
    protected function getTypeTextAttr($value,$data){
        return \think\Config::get('sms_types')[$data['type']];
    }

    protected static function init() {
        SmsConfig::event( 'after_insert', function ( $data ) {
            SmsConfig::resetCache();
        } );
        SmsConfig::event( 'after_update', function ( $data ) {
            SmsConfig::resetCache();
        } );
    }

    public static function resetCache(){
        Cache::rm('sms_config');
        SmsConfig::cache();
    }

    public static function cache(){
        $data = Cache::remember('sms_config',function() {
            $data = [];
            $arr = SmsConfig::where('status',1)->select();
            foreach($arr as $v){
                $data[] = $v;
            }
			return $data;
        }, 0);
        return $data;
    }
}
