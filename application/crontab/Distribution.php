<?php

namespace app\crontab;
use think\Db;

class Distribution {
    
    /**定时任务 用户是否同意分配方式*/
    public function worker()
    {
        $distribution = model("distribution");
        $time = time()-10*60;
        $distributionInfo = $distribution->where('status','in',[0,1])->where('create_time','<',$time)->select();
        if ($distributionInfo) {
            foreach ($distributionInfo as $key =>$value) {
                $content = json_decode($value->create_time, true);
                $agreeNum = 0;
                $disagreeNum = 0;
                foreach ($content as $contentKey => $contentValue) {
                    // 统计支持和失败的人数
                    if (isset($contentValue["vote_status"]) && $contentValue["vote_status"] == 1) {    // 1-支持，2-反对
                        $agreeNum += 1;
                    } elseif (isset($contentValue["vote_status"]) && $contentValue["vote_status"] == 2) {
                        $disagreeNum += 1;
                    }
                }
                $allNum = count($content);
                if ($agreeNum/$allNum > 0.75) {
                    Db::startTrans();
                    try {
                        $value->status = 2;
                        $value->save();
                        $team = mode("team")->where(["id" => $value->team_id])->find();
                        $team->isdel = 2;
                        $team->save();
                        $room = mode("room")->where(["id" => $team->room_id])->find();
                        $room->isdel = 2;
                        $room->save();
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                    }


                } else {
                    $value->status = 3;
                    $value->save();
                }
            }
        }

    }
    

}
