<?php
/**
 * tpAdmin [a web admin based ThinkPHP5]
 *
 * @author    yuan1994 <tianpian0805@gmail.com>
 * @link      http://tpadmin.yuan1994.com/
 * @copyright 2016 yuan1994 all rights reserved.
 * @license   http://www.apache.org/licenses/LICENSE-2.0
 */

//------------------------
// 自定义标签库
//-------------------------

namespace app\admin\taglib;

use think\template\TagLib;
use think\Request;
use think\Url;
use think\Loader;

class Custom extends Taglib
{

    // 标签定义
    protected $tags = [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'select'   => ['attr' => 'field,values,texts,value', 'close' => 0],
        'art_cat'   => ['close' => 0],
        'adv_type'   => ['close' => 0],
    ];
  
    public function tagAdv_type(){
        $data = model("AdvType")->cache();
        $parseStr = "";
        foreach($data as $k=>$v){
            $parseStr = '<option value="'.$k.'">'.$v['name'].'</option>'.$parseStr;
        }
        return $parseStr;
    }

    public function tagArt_cat(){
        $data = model("ArticleCategory")->cache();
        $parseStr = "";
        foreach($data as $k=>$v){
            $parseStr = '<option value="'.$k.'">'.$v['name'].'</option>'.$parseStr;
        }
        return $parseStr;
    }

    /**
     * 选项扩展
     * @param $tag
     * @return string
     */
    public function tagSelect($tag, $content)
    {
        $controller =  Request::instance()->controller();
        $field = $tag['field'];
        $id = $tag['id'] ?? 'id';
        $values=is_array($tag['values']) ? $tag['values'] : explode(',', $tag['values']);
        $texts=is_array($tag['texts']) ? $tag['texts'] : explode(',', $tag['texts']);
        $value = $tag['value'];

      
        $html='<?php $value = "'.$value.'";?><div class="select-box">
                    <select name="'.$field.'" class="select" onChange="ajax_req(\'' . Url::build($controller . '/setField') . '\',{field:\''.$field.'\','.$field.':$(this).val(),'.$id.':\'{$vo.'.$id.'}\'})">';
        foreach ($values as $row => $rows){
            $html.='<option value="'.$rows.'"<?php echo $value == "'.$rows.'" ? " selected" : "";?>>'.$texts[$row].'</option>';
        }
        $html.=' </select></div>';
        return $html;
    }


}