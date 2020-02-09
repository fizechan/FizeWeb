<?php

require_once "../vendor/autoload.php";

use fize\web\Cookie;
use fize\crypt\Json;

$config = [
    'prefix'        => "fz_", //Cookie键名前缀,如果发生冲突可以修改该值
    'encrypt_key'   => true, //是否加密cookie键名，加密键名则需要对所有cookie进行遍历获取，不合适cookie过多的情况
    'encrypt_value' => true, //是否加密cookie键值
    'secret_key'    => "123456", //加密密钥
];

new Cookie($config);

$status = [];

Cookie::onTamper(function ($key, $value) use (&$status) {
    $status[$key] = $value;
});

Cookie::set('key1', 'value1');

$value1 = Cookie::get('key1');

Cookie::set('key1', 'value2', ['encrypt_value' => false]);  //模拟篡改cookie

$value2 = Cookie::get('key1');

$result = [
    'errcode' => 0,
    'errmsg'  => '',
    'data'    => [
        'status' => $status,
        'value1' => $value1,
        'value2' => $value2
    ]
];

echo Json::encode($result);
