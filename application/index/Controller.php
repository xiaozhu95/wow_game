<?php
namespace app\index;

use think\View;
use think\Request;
use think\Config;
use think\Cookie;
use think\exception\HttpResponseException;

class Controller
{
	/**
     * @var View 视图类实例
     */
    protected $view;
    /**
     * @var Request Request实例
     */
    protected $request;
	
	public function __construct()
    {
        if (null === $this->view) {
            $this->view = View::instance(Config::get('template'), Config::get('view_replace_str'));
        }
        if (null === $this->request) {
            $this->request = Request::instance();
        }
    }

    public function _empty()
    {
        $cfg = Config::get('template');
        if(file_exists(strtolower(APP_PATH.$this->request->module().DS.'view'.DS.$this->request->controller().DS.$this->request->action().$cfg['view_suffix'])))
            return $this->view->fetch();
    }
}
?>