<?php
namespace app\api\controller;

use app\api\Controller;

class Image extends Controller
{
    static protected $blacklist = ['getlist'];

	public function thumb(){
		$width = $this->request->get('width');
        $height = $this->request->get('height');
		if(!$width || !$height) return;

        $path = $this->request->get('path');
		$domain = $this->request->domain();
		if(strpos($path,$domain) === 0){
			$path = str_replace($domain,'',$path);
		}elseif(strpos($path,'/') === 0){

		}else{
			return;
		}

    $path_prefix = ROOT_PATH.'public';
		if(file_exists($path_prefix.$path)){
			$dir = dirname($path);
			$ext = pathinfo($path,PATHINFO_EXTENSION);
			$filename = basename($path,'.'.$ext);
      $thumb_path = $dir.DS.'thumb'.DS;
      if (!file_exists ( $path_prefix.$thumb_path ))
      	mkdir ( $path_prefix.$thumb_path, 0777, true );
      $thumb_filename = $filename.'_'.$width.'x'.$height.'.'.$ext;
			if(!file_exists($path_prefix.$thumb_path.$thumb_filename)){
				$image = \think\Image::open($path_prefix.$path);
				$image->thumb($width, $height)->save($path_prefix.$thumb_path.$thumb_filename);
			}
			return redirect($domain.$thumb_path.$thumb_filename,302);
		}
	}
}