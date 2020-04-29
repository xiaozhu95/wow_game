<?php
namespace app\common\model;

use think\Model;
use think\Request;

class Credit extends Model
{
    // 指定表名,不含前缀
    protected $name = 'credit';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    const TYPE_AUCTION = 0; //竞拍失信
    const TYPE_FREEZE = 1; //冻结
    const TYPE_HEAD = 2; //团长失信

    /**添加冻结日志*/
    public function add($user_id,$data,$room_info)
    {

        //添加冻结日志
        $log = [
            // 'value' => $data['price'],
            'auction_equipment_id' => $data['auction_equipment_id'],
            'equipment_id' => $data['equipment_id'],
            'equipemnt_name' => $data['equipment_name'],
            'user_id' => $user_id,
            'team_id' => $data['team_id'],
            'room_id' => $room_info['id'],
            'room_name' => $room_info['room_num'],
            // 'des' => '',
            'type' => self::TYPE_FREEZE, //冻结金额
            'is_delete' => 0,
        ];

        $creat_resut = $this->where($log)->find();


        $credit_delete_result = 1;
        if ($creat_resut){
            $credit_delete_result = $creat_resut->save(['is_delete'=>1]); //删除原来相同的记录
        }

        $log['value'] = $data['price']; //冻结的金额

        $log['des'] = '由于你多次失信,参与竞拍装备:'.$data['equipment_name'] . '冻结金额：'.$data['price'] . '时间：' . date('Y-m-d H:i:s');

        //目的是记录最高的价格
        $credit_add_result = $this->save($log);

        $result = 0;

        //确认删除成功  确认添加成功
        if($credit_add_result && $credit_add_result){
            $result = 1;
        }

        return $result;

    }


    /**统计冻结金额*/
    public function total_price($user_id)
    {
        $result = $this->field('sum(value) as total_price')->where(['user_id'=>$user_id,'type'=> self::TYPE_FREEZE,'is_delete' =>0])->find();
        return $result['total_price'];
    }


    /**添加失信日志*/
    public function auctionadd($log_info,$room_info)
    {

        //添加冻结日志
        $log = [
            // 'value' => $data['price'],
            'auction_equipment_id' => $log_info['auction_equipment_id'],
            'equipment_id' => $log_info['equipment_id'],
            'equipemnt_name' => $log_info['equipment_name'],
            'user_id' => $log_info['user_id'],
            'team_id' => $log_info['team_id'],
            'room_id' => $room_info['id'],
            'room_name' => $room_info['room_num'],
            // 'des' => '',
            'type' => self::TYPE_AUCTION, //失信
            'is_delete' => 0,
        ];

        $creat_resut = $this->where($log)->find();



        $log['value'] = 1; //竞拍失信(失信值,越低越好)

        $log['des'] = '失信值+1，由于你竞拍装备:'.$log_info['equipment_name'] . '支付的金额：'.$log_info['price'] . '时间：' . date('Y-m-d H:i:s');

        //目的是记录最高的价格
        $credit_add_result = $this->save($log);
        return $credit_add_result;

    }

    /**添加失信日志*/
    public function auctionjian($log_info,$room_info)
    {

        //添加冻结日志
        $log = [
            // 'value' => $data['price'],
            'auction_equipment_id' => $log_info['auction_equipment_id'],
            'equipment_id' => $log_info['equipment_id'],
            'equipemnt_name' => $log_info['equipment_name'],
            'user_id' => $log_info['user_id'],
            'team_id' => $log_info['team_id'],
            'room_id' => $room_info['id'],
            'room_name' => $room_info['room_num'],
            // 'des' => '',
            'type' => self::TYPE_AUCTION, //失信
            'is_delete' => 0,
        ];

        $creat_resut = $this->where($log)->find();



        $log['value'] = -1; //竞拍失信(失信值,越低越好)

        $log['des'] = '失信值-1，由于你竞拍装备:'.$log_info['equipment_name'] . '支付的金额：'.$log_info['price'] . '时间：' . date('Y-m-d H:i:s');

        //目的是记录最高的价格
        $credit_add_result = $this->save($log);
        return $credit_add_result;

    }

}
