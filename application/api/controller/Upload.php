<?php
namespace app\api\controller;

use app\api\Controller;
class Upload extends Controller
{

    public function index()
    {
      define('SKIP_AUTH',true);
      
			return action('admin/upload/upload');
    }
	
}
