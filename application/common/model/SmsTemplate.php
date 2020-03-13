<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class SmsTemplate extends Model
{
    // 指定表名,不含前缀
    protected $name = 'sms_template';
    protected function getTypeTextAttr($value,$data){
        return \think\Config::get('sms_types')[$data['type']];
    }

    protected static function init() {
        SmsTemplate::event( 'after_insert', function ( $data ) {
            SmsTemplate::resetCache();
        } );
        SmsTemplate::event( 'after_update', function ( $data ) {
            SmsTemplate::resetCache();
        } );
    }

    public static function resetCache(){
        Cache::rm('sms_template');
        SmsTemplate::cache();
    }

    public static function cache(){
        $data = Cache::remember('sms_template',function() {
            $data = [];
            $arr = SmsTemplate::where('status',1)->select();
            foreach($arr as $v){
                $data[$v['type']][$v['code']] = $v;
            }
			return $data;
        }, 0);
        return $data;
    }
}
