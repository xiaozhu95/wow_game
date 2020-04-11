<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use think\Response;

/**
 * 模拟tab产生空格
 * @param int $step
 * @param string $string
 * @param int $size
 * @return string
 */
function tab($step = 1, $string = ' ', $size = 4)
{
    return str_repeat($string, $size * $step);
}


/**
 * 框架内部默认ajax返回
 * @param string $msg      提示信息
 * @param string $redirect 重定向类型 current|parent|''
 * @param string $alert    父层弹框信息
 * @param bool $close      是否关闭当前层
 * @param string $url      重定向地址
 * @param string $data     附加数据
 * @param int $code        错误码
 * @param array $extend    扩展数据
 */
function ajax_return_adv($msg = '操作成功', $redirect = 'parent', $alert = '', $close = false, $url = '', $data = '', $code = 0, $extend = [])
{
    $extend['opt'] = [
        'alert'    => $alert,
        'close'    => $close,
        'redirect' => $redirect,
        'url'      => $url,
    ];

    return ajax_return($data, $msg, $code, $extend);
}

/**
 * 返回错误json信息
 */
function ajax_return_adv_error($msg = '', $code = 1, $redirect = '', $alert = '', $close = false, $url = '', $data = '', $extend = [])
{
    return ajax_return_adv($msg, $alert, $close, $redirect, $url, $data, $code, $extend);
}

/**
 * ajax数据返回，规范格式
 * @param array $data   返回的数据，默认空数组
 * @param string $msg   信息
 * @param int $code     错误码，0-未出现错误|其他出现错误
 * @param array $extend 扩展数据
 */
function ajax_return($data = [], $msg = "", $code = 0, $extend = [])
{
    $ret = ["code" => $code, "msg" => $msg, "data" => $data];
    $ret = array_merge($ret, $extend);

    return Response::create($ret, 'json');
}

/**
 * 返回标准错误json信息
 */
function ajax_return_error($msg = "出现错误", $code = 1, $data = [], $extend = [])
{
    return ajax_return($data, $msg, $code, $extend);
}

function front_ajax_return($msg,$status=0,$data=null){
	return json(['code'=>$status,'msg'=>$msg,'data'=>$data]);
}

/**
 * 生成随机数  (新增用户时需要用到)
 * @param int $length 随机数长度
 * @param int $numeric 是否只生成数字
 * @return string
 */
function random($length, $numeric = 0)
{
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	$hash = '';
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++)
	{
		$hash .= $seed{mt_rand(0, $max)};
	}
	return $hash;
}

//判断是手机登录还是电脑登录
function is_mobile() {
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;
    
    //此条摘自TPM智能切换模板引擎，适合TPM开发
    if(isset ($_SERVER['HTTP_CLIENT']) &&'PhoneClient'==$_SERVER['HTTP_CLIENT'])
        return true;
    //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA']))
        //找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
    //判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile'
        );
        //从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    //协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}


/**
 * 统一密码加密方式，如需变动直接修改此处
 * @param $password
 * @return string
 */
function password_hash_tp($password)
{
    return hash("md5", trim($password));
}


function curl_file_get_contents($durl){
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $durl);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
//   curl_setopt($ch, CURLOPT_USERAGENT, _USERAGENT_);
//   curl_setopt($ch, CURLOPT_REFERER,_REFERER_);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  $r = curl_exec($ch);
  curl_close($ch);
  return $r;
}

function remove_emoji($str)
{
 $str = preg_replace_callback(
   '/./u',
   function (array $match) {
    return strlen($match[0]) >= 4 ? '' : $match[0];
   },
   $str);

  return $str;
}

//app微信登录 小程序登录等需要用到
function array2object($array) {
    if (is_array($array)) {
        $obj = new StdClass();
        foreach ($array as $key => $val){
            $obj->$key = $val;
        }
    }
    else { $obj = $array; }
    return $obj;
}

function subtext($text, $length)
{
    if(mb_strlen($text, 'utf8') > $length)
        return mb_substr($text,0,$length,'utf8').'…';
    return $text;
}

    /**
	 * Get xml from array
	 */
	function getXMLFromArray($arr, $wrapper='xml', $real_key='')
	{
		$xml = $wrapper ? "<$wrapper>" : "";
		foreach ($arr as $key => $val) {
			if (is_numeric($val)) {
				$xml .= sprintf("<%s>%s</%s>", $key, $val, $key);
			} elseif (is_array($val)) {
        if($val['key'])
				  $xml .= sprintf("<%s>%s</%s>", $key, $this->getXMLFromArray($val['value'], '', $val['key']), $key);
        else if($real_key)
				  $xml .= sprintf("<%s>%s</%s>", $real_key, $this->getXMLFromArray($val,''), $real_key);
        else
				  $xml .= sprintf("<%s>%s</%s>", $key, $this->getXMLFromArray($val,''), $key);
			} else {
				$xml .= sprintf("<%s><![CDATA[%s]]></%s>", $key, $val, $key);
			}
		}
    	$xml .= $wrapper ? "</$wrapper>" : "";
		return $xml;
	}
    /**
	 * Get xml from array
	 */
	function arrTowXml($arr, $wrapper='xml', $real_key='')
	{
		$xml = $wrapper ? "<$wrapper>" : "";
		foreach ($arr as $key => $val) {
			if (is_numeric($val)) {
                                   $xml .= sprintf("<%s>%s</%s>", $key, $val, $key);
			} elseif (is_array($val)) {
        if($val['key'])
				  $xml .= sprintf("<%s>%s</%s>", $key, $this->getXMLFromArray($val['value'], '', $val['key']), $key);
        else if($real_key)
				  $xml .= sprintf("<%s>%s</%s>", $real_key, $this->getXMLFromArray($val,''), $real_key);
        else
				  $xml .= sprintf("<%s>%s</%s>", $key, $val, $key);
			} else {
				$xml .= sprintf("<%s>%s</%s>", $key, $val, $key);
			}
		}
    	$xml .= $wrapper ? "</$wrapper>" : "";
		return $xml;
	}
/*
 *
 *  返回数组中指定多列
 * @param  Array  $input       需要取出数组列的多维数组
 * @param  String $column_keys 要取出的列名，逗号分隔，如不传则返回所有列
 * @param  String $index_key   作为返回数组的索引的列
 * @return Array
 * */
function array_columns($input, $column_keys=null, $index_key=null){
    $result = array();
    $keys =isset($column_keys)? explode(',', $column_keys) : array();
    if($input){
        foreach($input as $k=>$v){
            // 指定返回列
            if($keys){
                $tmp = array();
                foreach($keys as $key){
                    $tmp[$key] = $v[$key];
                }
            }else{
                $tmp = $v;
            }
            // 指定索引列
            if(isset($index_key)){
                $result[$v[$index_key]] = $tmp;
            }else{
                $result[] = $tmp;
            }
 
        }
    }
    return $result;
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false)
{
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) return $ip[$type];
    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) unset($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}