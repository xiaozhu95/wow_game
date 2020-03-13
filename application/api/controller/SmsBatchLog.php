<?php
namespace app\api\controller;

use app\api\Controller;
class SmsBatchLog extends Controller
{
    protected static $blacklist = ['getlist'];

    public function cron($content,$tels,$fields,$template_id,$index=0)
    {

        $tels_arr = array_chunk($tels,100);
        $data = [
            'content' => $content,
            'tels' => $tels_arr[$index],
            'fields' => $fields,
            'template_id' => $template_id,
        ];
        model('Captcha')->sendBatch($data);
        if(count($tels_arr) > $index + 1){
            $data['index'] = ++$index;
            $data['tels'] = $tels;
            model('Cron')->create([
                'module' => 'api',
                'controller' => 'sms_batch_log',
                'action' => 'cron',
                'data' => $data
            ]);
        }
    }
	
}
