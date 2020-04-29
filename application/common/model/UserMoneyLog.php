<?php
namespace app\common\model;

use think\Model;
use think\Config;

class UserMoneyLog extends Model
{
    // 指定表名,不含前缀
    protected $name = 'user_money_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
	protected $updateTime = false;

	public function user()
    {
        return $this->belongsTo('user','user_id','id')->field('id,mobile,nickname,avatar')->setEagerlyType(0);
    }

    protected function getTypeTextAttr($value, $data){
        $fields = Config::get('money_type_texts');
        return $fields[$data['type']];
    }

//	protected static function init()
//    {
//        UserMoneyLog::event('after_insert', function ($user_money_log) {
//            $fields = Config::get('money_types');
//			if(!isset($user_money_log['status']) || (isset($user_money_log['status']) && $user_money_log['status']>0)){
//                $user = new User();
//                $type = $user_money_log['type'] ?? 0;
//				$data = [
//					$fields[$type] => ['exp',$fields[$type].'+'.$user_money_log->amount]
//				];
//				$user->where("id",$user_money_log->user_id)->update($data);
//			}
//        });
//    }

}
