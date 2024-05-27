<?php

require_once "vendor/autoload.php";

use KingBes\PhpWebview\WebView;
use KingBes\PhpWebview\Dialog;

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
$webview = new WebView('Php WebView', 640, 480, true, __DIR__);
$view = 'file:///'.realpath('home.html');
// 获取html
// $html = file_get_contents(__DIR__ . '/src/index.html');
$webview->navigate($view);
// 绑定
$webview->bind('btn', function ($seq, $req, $context) {
    return $req;
});
// __DIR__ 入口位置
$dialog = new Dialog(__DIR__);
$webview->bind('dir', function ($seq, $req, $context) use($dialog){
    $ret = $dialog->dir(dirs:"D:/");
    return ok('', ['path'=>$ret]);
});
// 运行
$webview->run();
// 销毁
$webview->destroy();
