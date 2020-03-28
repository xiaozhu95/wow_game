<?php
/**
 * tpAdmin [a web admin based ThinkPHP5]
 *
 * @author    yuan1994 <tianpian0805@gmail.com>
 * @link      http://tpadmin.yuan1994.com/
 * @copyright 2016 yuan1994 all rights reserved.
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

namespace app\api\traits\controller;

use think\Db;

trait Controller
{
    public function getList()
    {
		$model = $this->getModel();

		// 列表过滤器，生成查询Map对象
		$map = $this->search($model, [$this->fieldIsDelete => $this::$isdelete]);

		// 自定义过滤器
		if (method_exists($this, 'filter')) {
			$this->filter($map);
		}
        $data = $this->datalist($model, $map);
        // 特殊过滤器，后缀是方法名的
		$actionFilter = 'after' . $this->request->action();
		if (method_exists($this, $actionFilter)) {
			$this->$actionFilter($data);
		}

        return ajax_return($data);
    }
}
