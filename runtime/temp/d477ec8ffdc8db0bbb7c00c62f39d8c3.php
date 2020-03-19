<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:103:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/index\view\index\index.html";i:1584354965;s:105:"D:\ruanjiananzhuang\phpstudy\PHPTutorial\WWW\wow_game\public/../application/index\view\template\base.html";i:1584354965;}*/ ?>
<html>
    <head>
        <title>abc</title>
        <link rel="stylesheet" href="__STATIC__/css/index.css" />
        
<style>
    ul li{ text-decoration: underline}
    </style>

    </head>
    <body>
        <header><nav>
            <ul>
                    <li>首页</li>
                    <li>文章也</li>
                </ul>
        </nav></header>
            
<a href="<?php echo \think\Url::build('index/article/index',['cid'=>5]); ?>" target="_blank">这是一个内容</a>

            
<script>
    let  cid = 5;

$.get("<?php echo \think\Url::build('api/article/getlist'); ?>"+"/cid/"+cid,{},function(data){

},'json');
</script>

        </body>
</html>