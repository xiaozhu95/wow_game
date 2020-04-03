<?php
namespace app\api\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\api\Controller;
use think\Loader;
use think\Config;
use think\Cookie;
use think\Cache;
use think\cache\driver\Redis;

class User extends Controller
{
	use \app\api\traits\controller\Controller;
	
	protected static $blacklist = ['getlist'];
	private $db_field = "openid";
	private $db_field_allowed = ["openid", "wechat_openid_2", "wechat_openid_3"];

	public function getUserInfo ()
    {
        $data = $this->request->param();
        $model = $this->getModel();
        $userInfo =  $model->getUserInfo(['id'=>$data["user_id"]]);
        $result = [
            'code' => 0,
            'msg' => 'usccess',
            'data' => $userInfo,
        ];
        return json($result);
    }
	public function account(){
		$uid = $this->request->post("user_id");
		$sign = $this->request->post("sign");
    	$model = $this->getModel();
		if($uid && $sign && password_hash_tp($uid)==$sign && $user = $model->where(["id"=>$uid])->find()){
			$params = $this->request->post();
//			if(isset($user['weixin']) && $user['weixin']){
//				unset($params['weixin']);
//				unset($params['qq']);
//			}
			if(isset($user['wechat_unionid'])){ //防止被恶意修改
				unset($params['wechat_unionid']);
				unset($params['wechat_nickname']);
			}
			
			if(isset($params['pwd'])){
				if($params['pwd']!=$params['confirm_pwd'])
					return front_ajax_return("两次输入的密码不一致");
				else
					$params['password'] = $params['pwd'];
			}

			if(isset($params['nickname']) && $params['nickname']!=$user['nickname']){
				if($this->getNicknameExists($params['nickname']))
					return front_ajax_return("该昵称已被占用");
			}

			if(isset($params['tel'])){
				if(!is_numeric($params['tel']) || strlen($params['tel'])!=11)
					return front_ajax_return("手机号输入有误");

					$msg = model('Captcha')->check('bindtel', $params['tel'], $params['captcha']);
					if($msg)
						return front_ajax_return($msg);
        
				if($model::get(['tel'=>$params['tel']]))
				return front_ajax_return("该手机号已绑定在其他账号");
			}
		
//			if(isset($params['auth']) && !$user['parent_id']){
//				$params['auth'] = strtolower($params['auth']);
//				if($params['auth']=='sqb'){
//					$params['parent_id'] = 0;
//				}else{
//					$parent_id = base_convert($params['auth'],36,10);
//					if($parent_id==$uid){
//						return front_ajax_return("邀请人不能为自己咯");
//					}elseif($parent_id && $model::get(['id'=>$parent_id/*,'vip'=>['>',0],'vip_expire'=>['>',time()]*/]))
//						$params['parent_id'] = $parent_id;
//					else
//						return front_ajax_return("邀请码输入有误");
//				}
//			}
			
			unset($params['money']);
			if(false === $user->allowField(true)->save($params)){
				return front_ajax_return("设置失败");
			}else{
				return front_ajax_return("设置成功",1,$params);
			}
		}
	}

    /*一微信键获取手机号*/
    public function wxappInfo()
    {
        $userModel = $this->getModel();
        $params = input("post.");
        return $userModel->wxappInfo($params);
    }



    public function pushReg(){
		$uid = $this->request->post("user_id");
		$reg_id = $this->request->post("registrationID");
		$platform = $this->request->post("platform/d");
		if($uid && $reg_id && in_array($platform,[0,1])){
          $model = model('JpushReg');
          if($res = $model->where(['reg_id'=>$reg_id])->find()){
            if($res['user_id']!=$uid){
              $res->user_id = $uid;
              $res->status = 1;
              $res->save();
            }
          }else
            $model->save(['user_id'=>$uid,'reg_id'=>$reg_id,'platform'=>$platform]);
            }else
          return 'invalid request';
	}
	
	public function detail(){
		$uid = $this->request->post("user_id");
		$sign = $this->request->post("sign");
		if($uid && $sign && password_hash_tp($uid)==$sign && $user = Loader::model('User')->getUserInfo(['id'=>$uid])){
		}
    
		return front_ajax_return("success",1,$user);
	}


	
	public function wechatLogin(){
		$code = $this->request->param("code");
		if($code){
			$db_field = $this->request->param('field');
			if(in_array($db_field, $this->db_field_allowed))
				$this->db_field = $db_field;
				
			$this->request->get(['field'=>$db_field]);
			$token = model('wechat')->getSnsAccessToken($code);
			if(isset($token->scope) && $token->scope == 'snsapi_base' && isset($token->unionid)){ //token中含有unionid 也可以直接执行bindWechat方法了
				return $this->bindWechat($token);
			}elseif(isset($token->access_token)){
				$user_info = json_decode(curl_file_get_contents("https://api.weixin.qq.com/sns/userinfo?access_token=".$token->access_token."&openid=".$token->openid."&lang=zh_CN"));
				return $this->bindWechat($user_info);
			}else
				return front_ajax_return($token->errmsg);
		}
	}

	public function wechatLoginWeb(){
		$data = $this->wechatLogin();
		$data = $data->getData();
		if($data['status'] == 1){
			Cookie::set('user_id',$data['data']['id']/* ,86400*30 */);
			Cookie::set('sign',$data['data']['sign']/* ,86400*30 */);
		}
	}
	
	public function wechatLoginApp(){
		$user_info = array2object($this->request->post());
		return $this->bindWechat($user_info,1);
	}

	public function wechatLoginMiniProgram(){
		$user_info = $this->request->post('user_info');
     
       	$user_info = json_decode(htmlspecialchars_decode($user_info));
        $user_info->nickname = $user_info->nickName;
        $res = model('Wechat')->jscode2session($this->request->param('code'));
        if(isset($res->openid)){
			$user_info->openid = $res->openid;
			$sessionKey = $res->session_key;
            $user_info->session_key =  $res->session_key;
        }else
            return front_ajax_return('获取openid失败');
        
		$iv = $this->request->param('iv');
		$encryptedData = $this->request->param('encryptedData');
      	
		$data = model('Wechat')->decryptData($sessionKey,$iv,$encryptedData);
      
        if(!$data)
            return front_ajax_return('登录失败，请重试');
        if(isset($data->unionId))
        	$user_info->unionid = $data->unionId;
        return $this->bindWechat($user_info);
    }
	
	private function getNicknameExists($nickname){
    	if(!$nickname) return true;
		return Loader::model('User')->where('nickname',$nickname)->count() ? true : false;
	}
	
	private function getValidNickname($nickname){
		do
		{
			$exist = $this->getNicknameExists($nickname);
			if($exist)
				$nickname = $nickname.'_'.random(3);
		}while($exist);
		return $nickname;
	}
	
	public function bindWechat($user_info,$app=0){
		if(is_string($user_info))
			$user_info = unserialize($user_info);

		// 进入没有unionid的
		if($user_info->unionid ?? $user_info->openid){
			if(isset($user_info->nickname))
				$user_info->nickname = remove_emoji($user_info->nickname);
			//扫码关注公众号$user_info->auth才有值 
			if(isset($user_info->auth)){
				$arr = explode('_', $user_info->auth);
				$auth = $arr[0];
				$invite_id = $arr[1] ?? 0;
			}else
				$auth = $this->request->param("state");

			$model = Loader::model('User');
			if($auth && strstr($auth,"_")){ //绑定微信
				if(isset($user_info->unionid) && $model->where(['wechat_unionid'=>$user_info->unionid])->find())
					return front_ajax_return("该微信号已绑定在其他账户");

				$auth = explode("_",$auth);
				$uid = $auth[0];
				$sign = $auth[1];
				if($uid && $sign && password_hash_tp($uid)==$sign && $user = $model->where(['id'=>$uid])->find()){
					if(isset($user_info->sex) && !$user['gender'])
						$user['gender'] = $user_info->sex;

					if($app)
						$user['app'] = $app;
					else
						$user[$this->db_field] = $user_info->openid;
					
					$user['wechat_unionid'] = $user_info->unionid ?? '';
					if(isset($user_info->nickname)){
						$user['wechat_nickname'] = $user_info->nickname;
						if(!$user['nickname'])
							$user['nickname'] = $this->getValidNickname($user_info->nickname);
						$user['avatar'] = $user_info->avatarUrl;
					}
					$user->allowField(true)->save();
					return front_ajax_return("绑定成功",1,$user);
				}
			}elseif(isset($user_info->unionid) && $user = $model->getUserInfo(['wechat_unionid'=>$user_info->unionid])){ // unionid登录
				if($app)
				$user['app'] = $app;
				else
				  $user[$this->db_field] = $user_info->openid;
				if(isset($user_info->nickname)){
					$user['wechat_nickname'] = $user_info->nickname;
					if(!$user['nickname'])
						$user['nickname'] = $this->getValidNickname($user_info->nickname);
					$user['avatar'] = $user_info->avatarUrl;
				}
				$user['last_login_time'] = time();
				$user['last_login_ip'] = $this->request->ip();
				$user['subscribe_prev'] = $user['subscribe'];
				if(isset($user_info->subscribe))
					$user['subscribe'] = $user_info->subscribe;
        		$data = $user->getData();
				$user->allowField(true)->save();
				return front_ajax_return("登录成功",1,$data);
			}elseif($user = $model->getUserInfo([$this->db_field=>$user_info->openid])){ // openid登录
				if(isset($user_info->nickname)){
					if(!$user['nickname'])
						$user['nickname'] = $this->getValidNickname($user_info->nickname);
					$user['avatar'] = $user_info->avatarUrl;
				}
        		$data = $user->getData();
				$user->allowField(true)->save();
				return front_ajax_return("登录成功",1,$data);
			}else{ //注册
				
				if(isset($user_info->sex))
					$user['gender'] = $user_info->sex;
				
				if($app)
					$data['app'] = $app;
				else
				  $data[$this->db_field] = $user_info->openid;
				if(isset($user_info->nickname)){
					$data['nickname'] = $this->getValidNickname($user_info->nickname);
					$data['avatar'] = $user_info->avatarUrl;
				}
                $data['session_key'] = $user_info->session_key;
              	
				$model->allowField(true)->save($data);
				$user = $model->getUserInfo(['id'=>$model['id']]);
				

				return front_ajax_return("登录成功",1,$user);
			}
		}else
			return front_ajax_return($user_info->errmsg);
	}
	
	/**
     * 登录
     */
	public function login(){
		$tel = $this->request->post("tel");
		$pwd = $this->request->post("pwd");
		$captcha = $this->request->post("captcha");
		if($tel && $pwd || $captcha){
			$where = ['tel'=>$tel];
			if($captcha){
				$msg = model('Captcha')->check('login', $tel, $captcha);
				if($msg)
					return front_ajax_return($msg);
			}else
				$where['password']=password_hash_tp($pwd);
				
			$user = Loader::model('User')->getUserInfo($where);
			if($user){
				$user['last_login_time'] = time();
				$user['last_login_ip'] = $this->request->ip();
        		$data = $user->getData();
				$user->allowField(true)->save();
				return front_ajax_return("登录成功",1,$data);
			}else{
				return front_ajax_return("手机号/密码错误");
			}
		}else{
			return front_ajax_return("手机号/密码不能为空");
		}
	}
	
	/**
     * 注册
     */
	public function reg(){
		$data = $this->request->post();
		if($data['pwd']!=$data['confirm_pwd'])
			return front_ajax_return("两次输入的密码不一致");
		
		if(!is_numeric($data['tel']) || strlen($data['tel'])!=11)
			return front_ajax_return("手机号输入有误");
		
		$user = Loader::model('User');
		$data['auth'] = strtolower($data['auth']);
		if($data['auth']=='sqb' || !$data['auth']){
			$data['parent_id'] = 0;
		}else{
			$parent_id = base_convert($data['auth'],36,10);
			if($parent_id && $user::get(['id'=>$parent_id/*,'vip'=>['>',0],'vip_expire'=>['>',time()]*/]))
				$data['parent_id'] = $parent_id;
			else
				return front_ajax_return("邀请码输入有误");
		}
		
		
		if($user::get(['tel'=>$data['tel']]))
			return front_ajax_return("该手机号已注册");

			$msg = model('Captcha')->check('register', $data['tel'], $data['captcha']);
			if($msg)
				return front_ajax_return($msg);
		
		$data['password'] = $data['pwd'];
		$user->allowField(true)->save($data);		
		
		$user = $user->getUserInfo(['id'=>$user['id']]);
		return front_ajax_return("注册并登录成功",1,$user);
	}
	
	/**
     * 修改密码
     */
    public function mod()
    {
		$data = $this->request->param();
		
		if(isset($data['tel'])) $where['tel'] = $data['tel'];
		if(isset($data['user_id'])) $where['id'] = $data['user_id'];
		
		$user = Loader::model('User')->where($where)->find();
		if(!$user){
			if(isset($data['tel']))
				return front_ajax_return("该手机号未注册");
			else
				return front_ajax_return("invalid request");
		}
		
		if(isset($data['tel'])){
			if($user['tel'] && !isset($data['captcha']))
				return front_ajax_return("手机号验证通过",1);

				$msg = model('Captcha')->check('findpwd', $data['tel'], $data['captcha']);
				if($msg)
					return front_ajax_return($msg);
		}elseif(password_hash_tp($data['org_pwd']) != $user['password'])
			return front_ajax_return("原密码错误");
		
		if($data['pwd']!=$data['confirm_pwd'])
			return front_ajax_return("两次输入的密码不一致");
		
		if(false === $user->updatePassword($where,$data['pwd']))
			return front_ajax_return("密码修改失败");
		else{
			return front_ajax_return("修改成功，请重新登录",1);
		}
    }

    
}
