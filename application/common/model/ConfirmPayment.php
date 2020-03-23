<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class ConfirmPayment extends Model
{
    // 指定表名,不含前缀
    protected $name = 'confirm_payment';
    
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    
    const STATUS_ON = 0;
    const STATUS_OFF = 1;
    
}
