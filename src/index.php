<?php

require_once "vendor/autoload.php";

use KingBes\PhpWebview\WebView;
use KingBes\PhpWebview\Dialog;

define('DS', DIRECTORY_SEPARATOR);

function shutdown(){
    $error = error_get_last();
    return $error && error($error['message']);
}
register_shutdown_function('shutdown');
function config($key='', $default = null){
    $conf = require 'config.php';
    return $key? ($conf[$key]??$default) : $conf;
}

function config_save($dir){
    $now = date("Y-m-d H:i:s");
    $configs = var_export([
        'dir'=>$dir
    ], true);
    $content = <<<Conf
<?php
// $now 更新
return $configs;
Conf;

    file_put_contents('config.php', $content);
}

function result($code, $msg, $data = []){
    return ["code" => $code, "msg" => $msg, "data" => $data];
}

function ok($msg, $data=[]){
    return result(0, $msg, $data);
}

function error($msg, $data=[]){
    return result(1, $msg, $data);
}

// 实例
$webview = new WebView('Eserver 工具箱', 800, 600, true, __DIR__);
$view = 'file:///'.realpath('home.html');
// 获取html
// $html = file_get_contents(__DIR__ . '/src/index.html');
$webview->navigate($view);
// 绑定
function parse_apps(string $software_file, $ret_mode = 'third_only')
{
    if(is_file($software_file)){
        $ret = json_decode(file_get_contents($software_file), true);
        if($ret){
            if($ret_mode !== 'all'){
                if($ret_mode === 'third_only'){
                    foreach($ret as $k=>$v){
                        if(isset($v['RemoteArchiveExt']) || stripos($v['Name'], 'composer') !== false) {
                            unset($ret[$k]);
                        }
                    }
                    $ret = array_values($ret);
                }else{
                    $official = [];
                    foreach($ret as $k=>$v){
                        if(isset($v['RemoteArchiveExt']) || stripos($v['Name'], 'composer') !== false) {
                            $official[] = $v;
                        }
                    }
                    return $official;
                }
            }
        }
        return $ret;
    }
    return [];
}

function parse_app_post($post){
    if(empty($post['Name'])){
        throw new Exception('应用名称必填');
    }
    if(empty($post['Desc'])){
        throw new Exception('应用描述必填');
    }
    if($post['Type'] == 'Tool'){
        if(empty($post['WinExePath'])){
            throw new Exception('工具主程序名称必填');
        }
        unset($post['ServerName']);
        unset($post['ServerPort']);
        unset($post['ServerProcessPath']);
        unset($post['StartServerArgs']);
    }else{
        $post['ShellServerProcess'] = true;
        unset($post['WinExePath']);
        $post['StartServerArgs'] = $post['StartServerArgs']? explode(',', $post['StartServerArgs']) : [];
        if(empty($post['ServerProcessPath'])){
            throw new Exception('服务进程的路径必填');
        }
    }
    $post['CanDelete'] = true;
    $post['DirName'] = strtolower($post['Name']);
    unset($post['Id']);
    return $post;
}

$webview->bind('apps', function ($seq, $req, $context) {
    $dir = config('dir');
    $software_file = $dir.DS.'core'.DS.'config'.DS.'software'.DS.'software.json';
//    var_dump($software_file);
    $is_file = file_exists($software_file);
    $apps = parse_apps($software_file);
    if($apps){
        // PHP 和 mysql 不让删除，走软件的卸载
        foreach ($apps as &$app){
            if(stripos($app['Name'], 'php') !== false || stripos($app['Name'], 'php') !== false){
                $app['CanDelete'] = false;
            }
        }
    }
    if($req){
        $apps = array_filter($apps, function ($app) use ($req) {
            var_dump($req[0]);
            return stripos($app['Name'], $req[0]) !== false || stripos($app['Desc'], $req[0]) !== false;
        });
    }
    return ok('', ['path'=>$dir, 'apps'=>$apps, 'is_file'=>$is_file]);
});

$webview->bind('app_add', function ($seq, $req, $context) {
    $post = [];
    parse_str($req[0], $post);
    var_dump($post);

    $dir = config('dir');
    $software_file = $dir.DS.'core'.DS.'config'.DS.'software'.DS.'software.json';
    $apps = parse_apps($software_file, 'all');
    try {
        $apps[] = parse_app_post($post);
        file_put_contents($software_file, json_encode($apps, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return ok('', ['apps'=>parse_apps($software_file)]);
    }catch (Exception $e){
        return error($e->getMessage());
    }
});


$webview->bind('app_edit', function ($seq, $req, $context) {
    var_dump($req[0]);
    $post = [];
    parse_str($req[0], $post);
    $post['Id'] = (int) $post['Id'];
    $dir = config('dir');
    $software_file = $dir.DS.'core'.DS.'config'.DS.'software'.DS.'software.json';
    $apps = parse_apps($software_file);
    try {
        $old = $apps[$post['Id']-1];
        $apps[$post['Id']-1] = array_merge($old, parse_app_post($post));
        $apps_official = parse_apps($software_file, 'official');
        foreach($apps as $app){
            $apps_official[] = $app;
        }
        file_put_contents($software_file, json_encode($apps_official, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return ok('', ['apps'=>parse_apps($software_file)]);
    }catch (Exception $e){
        return error($e->getMessage());
    }
});

$webview->bind('app_del', function ($seq, $req, $context) {
    $index = $req[0] - 1;
    $dir = config('dir');
    $software_file = $dir.DS.'core'.DS.'config'.DS.'software'.DS.'software.json';
    $apps = parse_apps($software_file);
    unset($apps[$index]);
    $apps_official = parse_apps($software_file, 'official');
    foreach($apps as $app){
        $apps_official[] = $app;
    }
    file_put_contents($software_file, json_encode($apps_official, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    return ok('', ['apps'=>array_values($apps)]);
});

// __DIR__ 入口位置
$dialog = new Dialog(__DIR__);
$webview->bind('dir', function ($seq, $req, $context) use($dialog){
    $conf = config();
    $ret = $dialog->dir(dirs:$conf['dir']);
    config_save($ret);
    return ok('', ['path'=>$ret]);
});

$webview->bind('icon', function ($seq, $req, $context) use($dialog){
    $path = $dialog->file();
    return ok('', ['icon'=>pathinfo($path, PATHINFO_BASENAME)]);
});
// 运行
$webview->run();
// 销毁
$webview->destroy();
