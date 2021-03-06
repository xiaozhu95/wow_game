<?php
/**
 * tpAdmin [a web admin based ThinkPHP5]
 *
 * @author    yuan1994 <tianpian0805@gmail.com>
 * @link      http://tpadmin.yuan1994.com/
 * @copyright 2016 yuan1994 all rights reserved.
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

namespace app\index\traits\controller;

trait Controller
{
  /**
   * 首页
   * @return mixed
   */
	public function index()
  {
  	return $this->view->fetch();
  }
}
