<?php
namespace app\common\model;

use think\Model;
use think\Cache;
use app\common\validate\AuctionEquipment as AuctionEquipmentValidate;

class AuctionEquipment extends Model
{
    // 指定表名,不含前缀
    protected $name = 'auction_equipment';
    
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    
    const TYPE_IN_TRANSACTION = 0; //交易中
    const TYPE_SUCCESSFUL_TRANSACTION = 1; //交易成功
    const TYPE_STREAM_SHOT = 2; //流拍


    /**批量添加装备*/
    public function add($data)
    {
       
        $validate = new AuctionEquipmentValidate();
        if (!$data)
             return ajax_return_adv_error('参数不能为空');
        foreach ($data as $key => $value) {
            $time  = time() + (is_integer($value['finsih_after_time']) ? $value['finsih_after_time'] : 0) * 60;
            $data[$key]['end_time'] =  $time;
            $data[$key]['pay_end_time'] = $time +(is_integer($value['pay_after_time']) ? $value['pay_after_time'] : 0) * 60;
            unset($data[$key]['finsih_after_time']);
            unset($data[$key]['pay_after_time']);
           if (!$validate->check($data[$key])) {
                return ajax_return_adv_error($validate->getError());
            }
        }
        $result = $this->saveAll($data);
        if ($result){
           return ajax_return('保存成功');  
        }
        return ajax_return_adv_error('保存失败');
       
    }
}
