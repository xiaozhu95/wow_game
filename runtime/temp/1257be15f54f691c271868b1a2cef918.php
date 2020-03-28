<?php if (!defined('THINK_PATH')) exit(); /*a:6:{s:102:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\room\index.html";i:1584428327;s:105:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\template\base.html";i:1584354964;s:116:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\template\javascript_vars.html";i:1584354964;s:101:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\room\form.html";i:1584428327;s:99:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\room\th.html";i:1584431815;s:99:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/admin\view\room\td.html";i:1584431963;}*/ ?>
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
    
    <div class="cl pd-5 bg-1 bk-gray">
        <span class="l">
            <?php if (\Rbac::AccessCheck('add')) : ?><a class="btn btn-primary radius mr-5" href="javascript:;" onclick="layer_open('添加','<?php echo \think\Url::build('add', []); ?>')"><i class="Hui-iconfont">&#xe600;</i> 添加</a><?php endif; if (\Rbac::AccessCheck('forbid')) : ?><a href="javascript:;" onclick="forbid_all('<?php echo \think\Url::build('forbid', []); ?>')" class="btn btn-warning radius mr-5"><i class="Hui-iconfont">&#xe631;</i> 禁用</a><?php endif; if (\Rbac::AccessCheck('resume')) : ?><a href="javascript:;" onclick="resume_all('<?php echo \think\Url::build('resume', []); ?>')" class="btn btn-success radius mr-5"><i class="Hui-iconfont">&#xe615;</i> 恢复</a><?php endif; if (\Rbac::AccessCheck('delete')) : ?><a href="javascript:;" onclick="del_all('<?php echo \think\Url::build('delete', []); ?>')" class="btn btn-danger radius mr-5"><i class="Hui-iconfont">&#xe6e2;</i> 删除</a><?php endif; if (\Rbac::AccessCheck('recyclebin')) : ?><a href="javascript:;" onclick="open_window('回收站','<?php echo \think\Url::build('recyclebin', []); ?>')" class="btn btn-secondary radius mr-5"><i class="Hui-iconfont">&#xe6b9;</i> 回收站</a><?php endif; ?>
        </span>
        <span class="r pt-5 pr-5">
            共有数据 ：<strong><?php echo isset($count) ? $count :  '0'; ?></strong> 条
        </span>
    </div>
    <table class="table table-border table-bordered table-hover table-bg mt-20">
        <thead>
        <tr class="text-c">
            <th width="25"><input type="checkbox"></th>
<th width=""></th>
<th width="">房间名</th>
<!--<th width="">服务器id</th>-->
<th width="">服务器名称</th>
<!--<th width="">阵营id</th>-->
<th width="">阵营名称</th>
<!--<th width="">副本id</th>-->
<th width="">副本名称</th>
<!--<th width="">bossid</th>-->
<th width="">boss名称</th>
<th width="">团队名称</th>
<th width="">装备评分</th>
<th width="">YY房间号</th>
<th width="">支出</th>
<th width="">补贴方式</th>
<th width="">DPS需高于第一名的百分比</th>
<th width="">HPS需高于第一名的百分比</th>
<th width="">紫</th>
<th width="">蓝</th>
<th width="">绿</th>
<th width="">房间名称</th>
<th width="">房间状态</th>
<th width="">创建时间</th>
<th width="">结束时间</th>
            <th width="70">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): $i = 0; $__LIST__ = $list;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
        <tr class="text-c">
            <td><input type="checkbox" name="id[]" value="<?php echo $vo['id']; ?>"></td>
<td><?php echo $vo['id']; ?></td>
<td><?php echo $vo['name']; ?></td>
<td><?php echo $vo['service_id']; ?></td>
<td><?php echo $vo['service_name']; ?></td>
<td><?php echo $vo['camp_id']; ?></td>
<td><?php echo $vo['camp_name']; ?></td>
<td><?php echo $vo['transcript_id']; ?></td>
<td><?php echo $vo['transcript_name']; ?></td>
<td><?php echo $vo['boss_id']; ?></td>
<td><?php echo $vo['boss_name']; ?></td>
<td><?php echo $vo['team_type']; ?></td>
<td><?php echo $vo['equipment_score']; ?></td>
<td><?php echo $vo['subsidy']; ?></td>
<td><?php echo $vo['yy_room_number']; ?></td>
<td><?php echo $vo['expenditure']; ?></td>
<td><?php echo $vo['high_dps']; ?></td>
<td><?php echo $vo['high_hps']; ?></td>
<td><?php echo $vo['purple']; ?></td>
<td><?php echo $vo['blue']; ?></td>
<td><?php echo $vo['green']; ?></td>
<td><?php echo $vo['room_num']; ?></td>
<td><?php echo get_status($vo['status']); ?></td>
<td><?php echo $vo['create_time']; ?></td>
<td><?php echo $vo['update_time']; ?></td>
            <td class="f-14">
                <?php echo show_status($vo['status'],$vo['id']); if (\Rbac::AccessCheck('edit')) : ?> <a title="编辑" href="javascript:;" onclick="layer_open('编辑','<?php echo \think\Url::build('edit', ['id' => $vo["id"], ]); ?>')" style="text-decoration:none" class="ml-5"><i class="Hui-iconfont">&#xe6df;</i></a><?php endif; if (\Rbac::AccessCheck('delete')) : ?> <a title="删除" href="javascript:;" onclick="del(this,'<?php echo $vo['id']; ?>','<?php echo \think\Url::build('delete', []); ?>')" class="ml-5" style="text-decoration:none"><i class="Hui-iconfont">&#xe6e2;</i></a><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    <div class="page-bootstrap"><?php echo isset($page) ? $page :  ''; ?></div>
</div>

<script type="text/javascript" src="__LIB__/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="__LIB__/layer/2.4/layer.js"></script>
<script type="text/javascript" src="__STATIC__/h-ui/js/H-ui.js"></script>
<script type="text/javascript" src="__STATIC__/h-ui.admin/js/H-ui.admin.js"></script>
<script type="text/javascript" src="__STATIC__/js/app.js"></script>
<script type="text/javascript" src="__LIB__/icheck/jquery.icheck.min.js"></script>

</body>
</html>