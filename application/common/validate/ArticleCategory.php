<?php
namespace app\common\validate;

use think\Validate;

class ArticleCategory extends Validate
{
    protected $rule = [
        "name|名字" => "require",
        "sort|排序" => "require",
    ];
}
