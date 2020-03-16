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
    protected $createTime  = "ctime";
    protected $updateTime = "utime";

  
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
            $user = $this->field("id,openid,avatar,nickname,mobile,balance")->where($condition)->find();

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
