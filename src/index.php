<?php

require_once "vendor/autoload.php";

use KingBes\PhpWebview\WebView;
use KingBes\PhpWebview\Dialog;

define('DS', DIRECTORY_SEPARATOR);
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
$webview = new WebView('Eserver 工具箱', 640, 480, true, __DIR__);
$view = 'file:///'.realpath('home.html');
// 获取html
// $html = file_get_contents(__DIR__ . '/src/index.html');
$webview->navigate($view);
// 绑定
function parse_apps(string $software_file)
{
    if(is_file($software_file)){
        return json_decode(file_get_contents($software_file), true);
    }
    return [];
}

$webview->bind('apps', function ($seq, $req, $context) {
    $dir = config('dir');
    $software_file = $dir.DS.'core'.DS.'config'.DS.'software'.DS.'software.json';
//    var_dump($software_file);
    $is_file = file_exists($software_file);
    $apps = parse_apps($software_file);
    if($req){
        $apps = array_filter($apps, function ($app) use ($req) {
            var_dump($req[0]);
            return stripos(strtolower($app['Name']), $req[0]) !== false || stripos(strtolower($app['Desc']), $req[0]) !== false;
        });
    }
    return ok('', ['path'=>$dir, 'apps'=>$apps, 'is_file'=>$is_file]);
});
// __DIR__ 入口位置
$dialog = new Dialog(__DIR__);
$webview->bind('dir', function ($seq, $req, $context) use($dialog){
    $conf = config();
    $ret = $dialog->dir(dirs:$conf['dir']);
    config_save($ret);
    return ok('', ['path'=>$ret]);
});
// 运行
$webview->run();
// 销毁
$webview->destroy();
