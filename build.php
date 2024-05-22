<?php

/* $cmd = match (PHP_OS_FAMILY) {
    'Linux'   => "cat micro.sfx models.php > models.bin",
    'Darwin'  => 'cat micro.sfx models.php > models.exe',
    'Windows' => 'cmd /c copy /b micro.sfx + models.php models.exe',
    default   => throw new Exception("Os is not supported, Only Linux, MacOs and windows are only supported by default !"),
}; */

// 复制
function copyDir(string $source, string $destination)
{
    if (!is_dir($destination)) {
        mkdir($destination, 0777, true);
    }

    $dir = opendir($source);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($source . DIRECTORY_SEPARATOR . $file)) {
                copyDir($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
            } else {
                copy($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
            }
        }
    }
    closedir($dir);
}

$dirPhar = __DIR__ . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . "debug";
if (!is_dir($dirPhar)) {
    mkdir($dirPhar, 0777, true);
}
// webview.phar文件
$webviewPhar = $dirPhar . DIRECTORY_SEPARATOR . 'webview.phar';

// 检查文件是否存在  
if (file_exists($webviewPhar)) {
    // 文件存在，尝试删除  
    if (!unlink($webviewPhar)) {
        echo "执行删除文件 {$webviewPhar} 时出错。\n";
        exit;
    }
}

try {
    //产生一个webview.phar文件
    $phar = new Phar($webviewPhar, 0, 'webview.phar');
    // 添加src里面的所有文件到webview.phar归档文件
    $phar->buildFromDirectory(dirname(__FILE__) . '/src');
    //设置执行时的入口文件，第一个用于命令行，第二个用于浏览器访问，这里都设置为index.php
    $phar->setDefaultStub('index.php');
} catch (Exception $e) {
    echo new Exception("生成 Phar 文件时出错：" . $e->getMessage()) . "\n";
}

copyDir(__DIR__ . DIRECTORY_SEPARATOR . "os", $dirPhar . DIRECTORY_SEPARATOR . "os");


// windows 执行
$command = "copy /b " . __DIR__ . "\\php\\windows\\micro.sfx + {$dirPhar}\\webview.phar {$dirPhar}\\webview.exe";
echo $command . "\n";
exec($command, $output, $returnVar);

if ($returnVar === 0) {
    echo "命令执行成功！\n";
} else {
    echo "命令执行失败，返回码：" . $returnVar . PHP_EOL . "\n";
    if (!empty($output)) {
        echo "输出信息：" . PHP_EOL . "\n";
        print_r($output);
    }
    exit;
}
echo "打包成功！！！\n";
echo "打包路径:" . $dirPhar . "\n";
echo "二进制文件必须和os文件夹一起分发!";
