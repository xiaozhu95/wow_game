<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/22 0022
 * Time: 13:42
 */

namespace app\api\controller;

use app\api\Controller;

class Cron extends Controller
{
    public function index(){
        $time = time();
        $crons = model("Cron")->where(['start_time'=>['<=',$time],'next_time'=>["<=",$time],'status'=>1])->order('sort')->select();
        foreach($crons as $v){
            if($v['interval_time'])
                model("Cron")->save([
                    'next_time' => $time + $v['interval_time']
                ],['id'=>$v['id']]);
            else
                model("Cron")->where(['id'=>$v['id']])->delete();

            if(!defined('SKIP_AUTH')) define('SKIP_AUTH', true);
            $this->request->module($v['module']);
            $this->request->controller($v['controller']);
            $this->request->get(['cron'=>1]);
            $resp = action($v['module'].'/'.$v['controller'].'/'.$v['action'], $v['data_arr']);
            if($resp instanceof \think\response\Json){
                $resp = $resp->getData();
            }
            $data = $v->getData();
            $data['resp'] = $resp;
            $data['cron_id'] = $data['id'];
            unset($data['id']);
            model('CronLog')->allowField(true)->save($data);
        }

        //这里使用网页打开来自动更新的  如果采用crontab 可以不用加continue参数
        if($this->request->get('continue')){
            $next_time = model("Cron")->where(['start_time'=>['<=',$time],'status'=>1])->order('next_time')->value('next_time');
            return "<script>function redirect(){window.location.href='?continue=1&dt=".date('H:i:s')."';}setTimeout('redirect();',".(($next_time-time())*1000).");</script>";
        }
    }

    public function test(){
        return json(['status'=>1, 'msg'=>'done']);
    }
}