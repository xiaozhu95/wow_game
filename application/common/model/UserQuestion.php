<?php
namespace app\common\model;

use think\Model;
use think\Request;
/**
 * 种族天赋
 */
class UserQuestion extends Model
{
    // 指定表名,不含前缀
    protected $name = 'user_question';

    public function addQusetion($content,$userId)
    {
        $userQusetion = new UserQuestion();
        $userQusetion->content = json_encode($content);
        $userQusetion->user_id = $userId;
        $userQusetion->create_time = time();
        if ($userQusetion->save()) {
            $result = [
                'code' => 0,
                'msg' => '提交成功!'
            ];
        } else {
            $result = [
                'code' => 1,
                'msg' => '网路异常，请重新提交!'
            ];
        }
        return json($result);
    }
    public function user()
    {
        return $this->belongsTo('user','user_id','id')->field('nickname')->setEagerlyType(0);
    }

    public function getContentTextAttr($value,$data)
    {
        return json_decode($data['content'],true);
    }

}
