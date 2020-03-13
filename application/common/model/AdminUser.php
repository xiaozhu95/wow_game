<?php
/**
 * tpAdmin [a web admin based ThinkPHP5]
 *
 * @author yuan1994 <tianpian0805@gmail.com>
 * @link http://tpadmin.yuan1994.com/
 * @copyright 2016 yuan1994 all rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

//------------------------
// 用户模型
//-------------------------

namespace app\common\model;

use think\Model;

class AdminUser extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    //自动完成
    protected $auto = ['password'];

    protected function filter(&$map)
    {
		if ($this->request->param("tel")) {
            $map['user.tel'] = ['like', $this->request->param("tel").'%'];
        }
		if ($this->request->param("nickname")) {
            $map['user.nickname'] = $this->request->param("nickname");
        }

        $map['_relation'] = "user";
    }

    protected function setPasswordAttr($value)
    {
        return password_hash_tp($value);
    }

    /**
     * 修改密码
     */
    public function updatePassword($uid, $password)
    {
        return $this->where("id", $uid)->update(['password' => password_hash_tp($password)]);
    }

	public function user()
    {
        return $this->belongsTo('user')->field('id,tel,nickname')->setEagerlyType(0);
    }
}