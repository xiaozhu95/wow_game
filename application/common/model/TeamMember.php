<?php
namespace app\common\model;

use think\Model;
use think\Cache;

class TeamMember extends Model
{
    // 指定表名,不含前缀
    protected $name = 'team_member';
    
    const IDENTITY_TEAM = 1; //团长
    const IDENTITY_TEAM_ALLOW = 2; //正式团员
    const IDENTITY_TEAM_DENY = 3; //未审核团员
    const IDENTITY_TEAM_MEMBER = 4; //已成为地板
}
