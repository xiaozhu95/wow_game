<?php
namespace app\common\model;

use think\Model;

class Withdraw extends Model
{
    // 指定表名,不含前缀
    protected $name = 'withdraw';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
	
	protected static function init()
    {
        Withdraw::event('after_insert', function ($withdraw) {
			UserMoneyLog::create([
				'user_id' => $withdraw['user_id'],
				'amount' => -$withdraw['amount'],
				'msg' => '提现',
                'controller' => 'withdraw',
                'action' => 'add'
			]);
        });
				
    }
	
	public function user()
    {
        return $this->belongsTo('User')->field('id,tel,nickname')->setEagerlyType(0);
    }				
}
