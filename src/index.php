<?php

require_once "vendor/autoload.php";

use KingBes\PhpWebview\WebView;

// 实例
$webview = new WebView('Php WebView', 640, 480, true, __DIR__);
// 获取html
// $html = file_get_contents(__DIR__ . '/src/index.html');
// 设置HTML
$webview->setHTML("asd");
// 绑定
$webview->bind('btn', function ($seq, $req, $context) {
    return $req;
});
// 运行
$webview->run();
// 销毁
$webview->destroy();
