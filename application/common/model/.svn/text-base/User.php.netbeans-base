<?php
namespace app\common\model;

use think\Model;
use think\Request;
use think\Config;

class User extends Model
{
    // 指定表名,不含前缀
    protected $name = 'user';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
	
	//自动完成
    protected $insert = ['create_ip','password'];  
    
    protected static function init() {
        User::event( 'after_insert', function ( $user ) {
            //注册时直接创建对应UserProfile
            model("UserProfile")->data([
                'user_id'=>$user->id
            ])->save();

            //注册送1000积分
            // model('UserMoneyLog')->data([
            //     'user_id'=>$user->id,
            //     'amount'=>1000,
            //     'msg'=>'注册送',
            //     'type'=>1,
                // 'controller' => 'user',
                // 'action' => 'register'
            // ])->save();
        } );
    }

	protected function setCreateIpAttr()
    {
        return Request::instance()->ip();
    }
	
    protected function getIdTextAttr($value,$data)
    {
        return base_convert($data['id'],10,36);
    }	
    protected function getTelTextAttr($value,$data)
    {
        return $data['tel'] ?: '-';
    }
  
    protected function getSignAttr($value,$data)
    {
      return password_hash_tp($data['id']);
    }
  
    protected function getAuthAttr($value,$data)
    {
      return $this->generateAuth($data['id']);
    }

    protected function getProvinceTextAttr($value,$data)
    {
        if($data['province'])
      return model("Region")->where(['region_id'=>$data['province']])->value('region_name');
    }
    protected function getCityTextAttr($value,$data)
    {
        if($data['city'])
      return model("Region")->where(['region_id'=>$data['city']])->value('region_name');
    }
    protected function getDistrictTextAttr($value,$data)
    {
        if($data['district'])
      return model("Region")->where(['region_id'=>$data['district']])->value('region_name');
    }

    protected function getTypeTextAttr($value, $data){
        $texts = Config::get('user_types');
        return $texts[$data['type']];
    }
  
    private function generateAuth($id){
      $auth = strtoupper(base_convert($id,10,36));
			for($i=strlen($auth);$i<4;$i++){
				$auth = "0".$auth;
			}
      return $auth;
    }
	
    protected function setPasswordAttr($value)
    {
        return password_hash_tp($value);
    }
  
    /**
     * 修改密码
     */
    public function updatePassword($where, $password)
    {
		if(!$where) return false;
		return $this->where($where)->update(['password' => password_hash_tp($password)]);
    }
  
	public function getUserInfo($condition){
		$user = $this->field("id,parent_id,tel,money,integral,nickname,avatar,wechat_nickname,wechat_unionid,gender,province,city,district,subscribe,type")->where($condition)->find();
        if($user['id']){
            $user['province_text'] = $user['province_text'];
            $user['city_text'] = $user['city_text'];
            $user['district_text'] = $user['district_text'];
            $user['sign'] = $user['sign'];
            $user['auth'] = $user['auth'];

            $user['userProfile'] = $user['userProfile'];
        }
		
		return $user;
	}

    public function parentUser()
    {
        return $this->hasOne('User','id','parent_id','parent_user','left')->setEagerlyType(0);
    }
    
    public function userProfile()
    {
        return $this->hasOne('UserProfile')->setEagerlyType(0);
    }
}
