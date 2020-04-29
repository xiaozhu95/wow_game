<?php
namespace app\api;

use think\View;
use think\Request;
use think\Loader;
use think\Config;
use think\Db;
use think\exception\HttpException;

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
	/**
     * @var array 黑名单方法，禁止访问某些方法
     */
    protected static $blacklist = [];
    /**
     * @var array 白名单方法，如果设置会覆盖黑名单方法，只允许白名单方法能正常访问
     */
    protected static $allowList = [];

    protected static $isdelete = 0;
    protected $fieldIsDelete = 'isdelete';
    
  	public function __construct()
    {
  		  if (null === $this->view) {
            $this->view = View::instance(Config::get('template'), Config::get('view_replace_str'));
        }
        if (null === $this->request) {
            $this->request = Request::instance();
        }
      
        if($this->request->module() != 'admin'){
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        }
        // options 请求直接返回 如果tp什么都不返回 部分手机不能正常关闭链接  直到timeout
        if ($this->request->isOptions()){
            exit('ok');
        }

		    // 白名单/黑名单方法
        if ($this::$allowList && !in_array($this->request->action(), $this::$allowList)) {
            throw new HttpException(404, 'method not exists:' . $this->request->controller() . '->' . $this->request->action());
        } elseif ($this::$blacklist && in_array($this->request->action(), $this::$blacklist)) {
            throw new HttpException(404, 'method not exists:' . $this->request->controller() . '->' . $this->request->action());
        }
		
		    // 前置方法
        $beforeAction = "before" . $this->request->action();
        if (method_exists($this, $beforeAction)) {
            $this->$beforeAction();
        }


    }
	
	/**
     * 自动搜索查询字段,给模型字段过滤
     */
    protected function search($model, $param = [])
    {
        $map = [];
        $table_info = $model->getTableInfo();
        $param = array_merge($this->request->param(), $param);
        foreach ($param as $key => $val) {
            if ($val !== "" && in_array($key, $table_info['fields'])) {
                $map[$key] = $val;
            }
        }

        return $map;
    }
	
	/**
     * 获取模型
     *
     * @param string $controller
     * @param bool   $type 是否返回模型的类型
     *
     * @return \think\db\Query|\think\Model|array
     */
    protected function getModel($controller = '', $type = false)
    {
        $module = Config::get('app.model_path');
        if (!$controller) {
            $controller = $this->request->controller();
        }
        if (
            class_exists($modelClass = Loader::parseClass($module, 'model', $this->parseCamelCase($controller)))
            || class_exists($modelClass = Loader::parseClass($module, 'model', $controller))
        ) {
            $model = new $modelClass();
            $modelType = 'model';
        } else {
            $model = Db::name($this->parseTable($controller));
            $modelType = 'db';
        }

        return $type ? ['type' => $modelType, 'model' => $model] : $model;
    }
	
	/**
     * 根据表单生成查询条件
     * 进行列表过滤
     *
     * 过滤条件
     * $map['_table']       可强制设置表名前缀
     * $map['_relation']    可强制设置关联模型预载入(需在模型里定义)
     * $map['_field']       可强制设置字段
     * $map['_order_by']    可强制设置排序字段(field asc|desc[,filed2 asc|desc...]或者false)
     * $map['_paginate']    是否开启分页，传入false可以关闭分页
     * $map['_model']       可强制指定模型
     *
     * @param Model|Db $model    数据对象
     * @param array    $map      过滤条件
     * @param string   $field    查询的字段
     * @param string   $sortBy   排序
     * @param boolean  $asc      是否正序
     * @param boolean  $return   是否返回数据，返回数据时返回paginate对象，不返回时直接模板赋值
     * @param boolean  $paginate 是否开启分页
     */
    protected function datalist($model, $map, $field = null, $sortBy = '', $asc = false, $return = true, $paginate = true, $cache = false)
    {
        // 私有字段，指定特殊条件，查询时要删除
        $protectField = ['_table', '_relation', '_field', '_order_by', '_paginate', '_model', '_cache'];

        // 通过过滤器指定模型
        if (isset($map['_model'])) {
            $model = $map['_model'];
        }

        // 排序字段 默认为主键名
        $order = $this->request->param('_order') ?: (empty($sortBy) ? $model->getPk() : $sortBy);

        // 接受 sort参数 0 表示倒序 非0都 表示正序
        $sort = null !== $this->request->param('_sort')
            ? ($this->request->param('_sort') == 'asc' ? 'asc' : 'desc')
            : ($asc ? 'asc' : 'desc');

		// 设置关联预载入
        if (isset($map['_relation'])) {
            $model = $model::with($map['_relation']);
        }

      // 设置字段
        if (isset($map['_field'])) {
            $field = $map['_field'];
        }

        // 设置有$map['_relation']表示存在关联模型
        if (isset($map['_table'])) {
            // 给排序字段强制加上表名前缀
          
            if (strpos($order, ".") === false) {
                $order = $map['_table'] . "." . $order;
            }
            // 给字段强制加上表名前缀
            $_field = is_array($field) ? $field : explode(",", $field);
            foreach ($_field as &$v) {
                if (strpos($v, ".") === false) {
                    $v = preg_replace("/([^\s\(\)]*[a-z0-9\*])/", $map['_table'] . '.$1', $v, 1);
                }
            }
            $field = implode(",", $_field);
            // 给查询条件强制加上表名前缀
            foreach ($map as $k => $v) {
                if (!in_array($k, $protectField) && strpos($k, ".") === false) {
                    $map[$map['_table'] . '.' . $k] = $v;
                    unset($map[$k]);
                }
            }
        }

        // 设置排序字段 防止表无主键报错
        $order_by = $order ? "{$order} {$sort}" : false;
        if (isset($map['_order_by'])) {
            $order_by = $map['_order_by'];
        }

        // 是否开启分页
        $paginate = isset($map['_paginate']) ? boolval($map['_paginate']) : $paginate;
		
		//是否开启缓存
        $cache = isset($map['_cache']) ? (is_string($map['_cache']) ? $map['_cache'] : boolval($map['_cache'])) : $cache;

		// 删除设置属性的字段
        foreach ($protectField as $v) {
            unset($map[$v]);
        }
        if ($paginate) {
            // 分页查询

            // 每页数据数量
            $listRows = $this->request->param('numPerPage') ?: Config::get("paginate.list_rows");
			
			$list = $model
                ->field($field)
                ->where($map)
                ->order($order_by)
				->cache($cache)//->fetchSql()
                ->paginate($listRows, false, ['query' => $this->request->get(),'page'=>$this->request->param(Config::get("paginate.var_page"))]);

			if ($return) {
                // 返回值
                return $list;
            } else {
                // 模板赋值显示
                $this->view->assign('list', $list);
                $this->view->assign("count", $list->total());
                $this->view->assign("page", $list->render());
                $this->view->assign('numPerPage', $list->listRows());
            }
        } else {
            // 不开启分页查询
            $list = $model
                ->field($field)
                ->where($map)
                ->order($order_by)
                ->select();

            if ($return) {
                // 返回值
                return $list;
            } else {
                // 模板赋值显示
                $this->view->assign('list', $list);
                $this->view->assign("count", count($list));
                $this->view->assign("page", '');
                $this->view->assign('numPerPage', 0);
            }
        }
    }
	
	/**
     * 格式化表名，将 /. 转为 _ ，支持多级控制器
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function parseTable($name = '')
    {
        if (!$name) {
            $name = $this->request->controller();
        }

        return str_replace(['/', '.'], '_', $name);
    }
	
	/**
     * 将abc.def.Gh转为AbcDefGh
     *
     * @param $string
     *
     * @return mixed
     */
    protected function parseCamelCase($string)
    {
        return preg_replace_callback('/(\.|^)([a-zA-Z])/', function ($match) {
            return ucfirst($match[2]);
        }, $string);
    }
}
?>