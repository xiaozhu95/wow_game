<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:108:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\demo\imageupload.html";i:1584354964;s:105:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\template\base.html";i:1584354964;s:116:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\template\javascript_vars.html";i:1584354964;}*/ ?>
﻿<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"
          content="width=device-width,initial-scale=1,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
    <meta http-equiv="Cache-Control" content="no-siteapp"/>
    <title><?php echo \think\Config::get('site.title'); ?></title>
    <link rel="Bookmark" href="__ROOT__/favicon.ico" >
    <link rel="Shortcut Icon" href="__ROOT__/favicon.ico" />
    <!--[if lt IE 9]>
    <script type="text/javascript" src="__LIB__/html5.js"></script>
    <script type="text/javascript" src="__LIB__/respond.min.js"></script>
    <script type="text/javascript" src="__LIB__/PIE_IE678.js"></script>
    <![endif]-->
    <link rel="stylesheet" type="text/css" href="__STATIC__/h-ui/css/H-ui.min.css"/>
    <link rel="stylesheet" type="text/css" href="__STATIC__/h-ui.admin/css/H-ui.admin.css"/>
    <link rel="stylesheet" type="text/css" href="__LIB__/Hui-iconfont/1.0.7/iconfont.css"/>
    <link rel="stylesheet" type="text/css" href="__LIB__/icheck/icheck.css"/>
    <link rel="stylesheet" type="text/css" href="__STATIC__/h-ui.admin/skin/default/skin.css" id="skin"/>
    <link rel="stylesheet" type="text/css" href="__STATIC__/h-ui.admin/css/style.css"/>
    <link rel="stylesheet" type="text/css" href="__STATIC__/css/app.css"/>
    <link rel="stylesheet" type="text/css" href="__LIB__/icheck/icheck.css"/>
    
<link rel="stylesheet" href="__LIB__/lightbox2/css/lightbox.min.css">

    <!--[if IE 6]>
    <script type="text/javascript" src="__LIB__/DD_belatedPNG_0.0.8a-min.js"></script>
    <script>DD_belatedPNG.fix('*');</script>
    <![endif]-->
    <!--定义JavaScript常量-->
<script>
    window.THINK_ROOT = '<?php echo \think\Request::instance()->root(); ?>';
    window.THINK_MODULE = '<?php echo \think\Url::build("/" . \think\Request::instance()->module(), "", false); ?>';
    window.THINK_CONTROLLER = '<?php echo \think\Url::build("___", "", false); ?>'.replace('/___', '');
</script>
</head>
<body>

<nav class="breadcrumb">
    <div id="nav-title"></div>
    <a class="btn btn-success radius r btn-refresh" style="line-height:1.6em;margin-top:3px" href="javascript:;" title="刷新"><i class="Hui-iconfont"></i></a>
</nav>


<div class="page-container">
    <div class="form form-horizontal">
        <div class="row cl">
            <label class="form-label col-xs-3 col-sm-3"><span class="c-red">*</span>图片：</label>
            <div class="formControls col-xs-6 col-sm-6">
                <input type="text" class="input-text" id="upload" placeholder="请点击后面的上传按钮" datatype="*" nullmsg="请填写图片url" style="width: 70%">
                <button type="button" class="btn btn-primary radius" onclick="layer_open('文件上传','<?php echo \think\Url::build('Upload/index', ['id' => 'upload']); ?>')">上传</button>
                <a onclick="$(this).attr('href', $('#upload').val())" type="button" class="btn btn-success radius" data-lightbox="preview">预览</a>
            </div>
            <div class="col-xs-3 col-sm-3"></div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-3 col-sm-3">多图上传：</label>
            <div class="formControls col-xs-6 col-sm-6">
                <input type="text" class="input-text" placeholder="请上传图片" name="upload" style="width:70%" value="" >
                <button type="button" class="btn btn-primary radius" onclick="layer_open('文件上传','<?php echo \think\Url::build('Upload/index', ['id' => 'pics', 'preview' => 'pics_preview']); ?>')">上传</button>
            </div>
            <div class="col-xs-3 col-sm-3"></div>
        </div>
        <div class="row cl">
            <label class="form-label col-xs-3 col-sm-3"></label>
            <div class="col-xs-9 col-sm-9">
                <ul id="pics_preview" class="preview">
                    <?php if(isset($vo['pics_arr'])): if(is_array($vo['pics_arr']) || $vo['pics_arr'] instanceof \think\Collection || $vo['pics_arr'] instanceof \think\Paginator): $i = 0; $__LIST__ = $vo['pics_arr'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;?>
                    <li>
                        <i class="Hui-iconfont Hui-iconfont-close2 btn-primary" onclick="$(this).parent().remove()"></i>
                        <input type="hidden" name="pics[]" value="<?php echo $v; ?>" />
                        <a href="<?php echo $v; ?>" data-lightbox="pics">
                            <img src="<?php echo $v; ?>" class="radius">
                        </a>
                    </li>
                    <?php endforeach; endif; else: echo "" ;endif; endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div id="markdown" class="mt-20"></div>
</div>

<script type="text/javascript" src="__LIB__/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="__LIB__/layer/2.4/layer.js"></script>
<script type="text/javascript" src="__STATIC__/h-ui/js/H-ui.js"></script>
<script type="text/javascript" src="__STATIC__/h-ui.admin/js/H-ui.admin.js"></script>
<script type="text/javascript" src="__STATIC__/js/app.js"></script>
<script type="text/javascript" src="__LIB__/icheck/jquery.icheck.min.js"></script>

<script src="__LIB__/lightbox2/js/lightbox.min.js"></script>
<script src="__LIB__/sortable/jquery.sortable.min.js"></script>
<script type="text/javascript" src="__LIB__/showdown/1.4.2/showdown.min.js"></script>
<script>
$("#pics_preview").sortable({vertical: false, nested: false, delay: 50});

$(function () {
        var converter = new showdown.Converter(),
                text      = $("#markdown_tpl").html();
        $("#markdown").html(converter.makeHtml(text));
})
</script>

<script type="text/plain" id="markdown_tpl">
---
图片上传使用的是 H5 + iframe 兼容模式上传，不依赖 flash 实现无刷新上传，同时支持 H5 的拖拽上传和上传进度监听，支持表单直接提交上传文件


html 代码：
```
<div class="form form-horizontal">
    <div class="row cl">
        <label class="form-label col-xs-3 col-sm-3"><span class="c-red">*</span>图片：</label>
        <div class="formControls col-xs-6 col-sm-6">
            <input type="text" class="input-text" id="upload" placeholder="请点击后面的上传按钮" datatype="*" nullmsg="请填写图片url" style="width: 70%">
            <button type="button" class="btn btn-primary radius" onclick="layer_open('文件上传','{:\\think\\Url::build(\'Upload/index\', [\'id\' => \'upload\'])}')">上传</button>
            <a onclick="$(this).attr('href', $('#upload').val())" type="button" class="btn btn-success radius" data-lightbox="preview">预览</a>
        </div>
        <div class="col-xs-3 col-sm-3"></div>
    </div>
</div>
```


---
多图上传，留出一行进行图片上传后的预览，并通过sortable插件进行拖拽排序

html 代码：
```
<div class="row cl">
    <label class="form-label col-xs-3 col-sm-3">多图上传：</label>
    <div class="formControls col-xs-6 col-sm-6">
        <input type="text" class="input-text" placeholder="请上传图片" name="upload" style="width:70%" value="" >
        <button type="button" class="btn btn-primary radius" onclick="layer_open('文件上传','{:\\think\\Url::build(\'Upload/index\', [\'id\' => \'pics\', \'preview\' => \'pics_preview\'])}')">上传</button>
    </div>
    <div class="col-xs-3 col-sm-3"></div>
</div>
<div class="row cl">
    <label class="form-label col-xs-3 col-sm-3"></label>
    <div class="col-xs-9 col-sm-9">
        <ul id="pics_preview" class="preview">
            {present name="vo.pics_arr"}
            {volist name="vo.pics_arr" id="v"}
            <li>
                <i class="Hui-iconfont Hui-iconfont-close2 btn-primary" onclick="$(this).parent().remove()"></i>
                <input type="hidden" name="pics[]" value="{$v}" />
                <a href="{$v}" data-lightbox="pics">
                    <img src="{$v}" class="radius">
                </a>
            </li>
            {/volist}
            {/present}
        </ul>
    </div>
</div>
```
</script>


</body>
</html>