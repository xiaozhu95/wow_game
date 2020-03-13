<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class AuctionLog extends Model
{
    // 指定表名,不含前缀
    protected $name = 'auction_log';
}
