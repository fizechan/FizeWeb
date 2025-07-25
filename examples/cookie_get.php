<?php

require_once "../vendor/autoload.php";

use Fize\Web\Cookie;

$config = [
    'prefix'        => "fz_", //Cookie键名前缀,如果发生冲突可以修改该值
    'encrypt_key'   => true, //是否加密cookie键名，加密键名则需要对所有cookie进行遍历获取，不合适cookie过多的情况
    'encrypt_value' => true, //是否加密cookie键值
    'secret_key'    => "123456", //加密密钥
];

new Cookie($config);

Cookie::set('key1', 'value1');
$value1 = Cookie::get('key1');

Cookie::set('key2', 'value2', ['encrypt_value' => false]);
$value2 = Cookie::get('key2', ['encrypt_value' => false]);

Cookie::set('key3', 'value3', ['encrypt_key' => false, 'encrypt_value' => false]);
$value3 = Cookie::get('key3', ['encrypt_key' => false, 'encrypt_value' => false]);

$result = [
    'errcode' => 0,
    'errmsg'  => '',
    'data'    => [
        'value1' => $value1,
        'value2' => $value2,
        'value3' => $value3
    ]
];

print_r($result);
