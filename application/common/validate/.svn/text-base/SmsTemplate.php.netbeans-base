<?php
namespace app\common\validate;

use think\Validate;

class SmsTemplate extends Validate
{
    protected $rule = [
        "name|名字" => "require",
        "content|内容" => "require",
        "code|Code" => "require",
        "type|所属平台" => "require",
        "template_code|平台模板ID" => "require",
    ];
}
