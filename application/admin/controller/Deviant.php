<?php
namespace app\admin\controller;

\think\Loader::import('controller/Controller', \think\Config::get('traits_path') , EXT);

use app\admin\Controller;

class Deviant extends Controller
{
    use \app\admin\traits\controller\Controller;
    // 方法黑名单

    public function index()
    {
        $time = time();
        $deviant_model = model('Deviant');


        $min_create_time = $deviant_model->max('create_time');
        if(!$min_create_time) $min_create_time = 0;
        $max_create_time = $time;

        $user_money_logs = model('user_money_log')->field("user_id,sum(amount) as total_price")
                            ->where(['type'=>0])
                            ->where("create_time", "between", [$min_create_time, $max_create_time])
                            ->group('user_id')
                            ->select()
                            ->toArray();

        if($user_money_logs){



           $statistics = array_columns($user_money_logs,'total_price',"user_id");

           $statistics_user_ids = array_column($user_money_logs,"user_id");
           $user_model = model('user');
           foreach ($statistics_user_ids as $statistics_user_id){
               $user_model->whereOr("id",$statistics_user_id);
           }
           $user_resluts = $user_model->field('id,nickname,mobile,balance')->select();

           foreach ($user_resluts as $user_reslut){
               if ($statistics[$user_reslut['id']]['total_price'] != $user_reslut['balance']){
                   $deviant_result = $deviant_model->where(['user_id'=>$user_reslut['id']])->find();
                   if(!$deviant_result) $deviant_result = $deviant_model;
                   $deviant_model->user_id = $user_reslut['id'];
                   $deviant_result->nickname = $user_reslut['nickname'];
                   $deviant_result->mobile = $user_reslut['mobile'];
                   $deviant_result->msg = "检查到消费日志与余额不等，相差金额:".($user_reslut['balance'] - $statistics[$user_reslut['id']]['total_price'])."，暂时冻结账户,禁止提现";
                   $deviant_result->create_time = $time;
                   $deviant_result->save();

               }
           }
        }
        $map = [];
        $this->datalist($deviant_model, $map);
        return $this->view->fetch();

    }

}
