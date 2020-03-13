<?php
namespace app\common\model;

use think\Model;

class SmsBatchLog extends Model
{
    // 指定表名,不含前缀
    protected $name = 'sms_batch_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    protected $updateTime = false;

    protected static function init() {
        SmsBatchLog::event( 'after_insert', function ( $data ) {
            model('Cron')->create([
                'module' => 'api',
                'controller' => 'sms_batch_log',
                'action' => 'cron',
                'data' => $data['extra']
            ]);
        } );
    }

    protected function getTypeTextAttr($value,$data)
    {
        $texts = ['系统','手动'];
        return $texts[$data['type']];
    }

    protected function getTelsAttr($value,$data)
    {
        $extra = json_decode($data['extra'], true);
        $tels = implode(',',$extra['tels']);
        return $tels;
    }

    protected function getExtraArrAttr($value,$data)
    {
        return json_decode($data['extra'], true);
    }
    protected function setExtraAttr($value,$data)
    {
        $value['template_id'] = $data['template_id'];
        if($value['fields']){
            $keys = array_keys($value['fields']);
            $values = array_values($value['fields']);
            foreach($keys as $k => $v){
                $keys[$k] = '${'.$v.'}';
            }

            $value['content'] = str_replace($keys,$values,$value['content']);
        }
        if(!is_array($value['tels'])){
            $tels = explode("\r\n", $value['tels']);
            foreach($tels as $k => $v){
                $tels[$k] = trim($v);
            }
            $value['tels'] = array_unique($tels);
        }
        return json_encode($value);
    }

    public function smsTemplate()
    {
        return $this->hasOne('SmsTemplate','id','template_id')->setEagerlyType(0);
    }
}
