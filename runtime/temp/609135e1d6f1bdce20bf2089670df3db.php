<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:102:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\demo\index.html";i:1584354964;s:105:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\template\base.html";i:1584354964;s:116:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\template\javascript_vars.html";i:1584354964;}*/ ?>
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
    <p>主键不为ID的表，生成后需修改模板中的id为对应的主键，可参考user_profile的例子，编辑按钮带上pk参数并修改状态等切换也许带上对应参数 <?php echo show_status($vo['status'],$vo['user_id'],'user_id'); if (\Rbac::AccessCheck('edit')) : ?> <a title="编辑" href="javascript:;" onclick="layer_open('编辑','<?php echo \think\Url::build('edit', ['user_id' => $vo["user_id"], ]); ?>')" style="text-decoration:none" class="ml-5"><i class="Hui-iconfont">&#xe6df;</i></a><?php endif; ?></p>
    <div class="mt-20 markdown">
```

{$vo.status|show_status=$vo.user_id,'user_id'}
{tp:menu menu='sedit' pk='user_id' /}

```
    </div>
    <p>页面上其他字段使用状态切换方法如下 <?php echo get_status($vo['rec'],true,'rec'); ?> <?php echo show_status($vo['rec'],$vo['rec'],'id','rec'); ?></p>
    <div class="mt-20 markdown">
```

{$vo.rec|get_status=true,'rec'}
{$vo.rec|show_status=$vo.rec,'id','rec'}

```
    </div>
    <p>切换按钮的名称若不想叫做禁用/恢复，可如下设置 <?php echo show_status($vo['rec'],$vo['rec'],'id','rec','','开启,锁定'); ?></p>
    <div class="mt-20 markdown">
```

{$vo.rec|show_status=$vo.rec,'id','rec','','开启,锁定'}

```
    </div>
    <p>多个选项值的切换 <?php $value = "$vo[status]";?><div class="select-box">
                    <select name="status" class="select" onChange="ajax_req('/index.php/admin/demo/setfield',{field:'status',status:$(this).val(),id:'<?php echo $vo['id']; ?>'})"><option value="0"<?php echo $value == "0" ? " selected" : "";?>>待审</option><option value="1"<?php echo $value == "1" ? " selected" : "";?>>已审</option><option value="2"<?php echo $value == "2" ? " selected" : "";?>>结束</option> </select></div></p>
    <div class="mt-20 markdown">
```

{custom:select field="status" value="$vo[status]" values="0,1,2" texts="待审,已审,结束" /}

```
    </div>
</div>

<script type="text/javascript" src="__LIB__/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="__LIB__/layer/2.4/layer.js"></script>
<script type="text/javascript" src="__STATIC__/h-ui/js/H-ui.js"></script>
<script type="text/javascript" src="__STATIC__/h-ui.admin/js/H-ui.admin.js"></script>
<script type="text/javascript" src="__STATIC__/js/app.js"></script>
<script type="text/javascript" src="__LIB__/icheck/jquery.icheck.min.js"></script>

<script type="text/javascript" src="__LIB__/showdown/1.4.2/showdown.min.js"></script>
<script>
    $(function () {
        var converter = new showdown.Converter();
        $(".markdown").each(function () {
            $(this).html(converter.makeHtml($(this).html()))
        });
    })
</script>

</body>
</html>