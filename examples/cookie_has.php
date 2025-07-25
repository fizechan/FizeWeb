<?php
require_once "../vendor/autoload.php";

use Fize\Web\Cookie;

$config = [
    'prefix'        => "fz_",    //Cookie键名前缀,如果发生冲突可以修改该值
    'encrypt_key'   => true,     //是否加密cookie键名，加密键名则需要对所有cookie进行遍历获取，不合适cookie过多的情况
    'encrypt_value' => true,     //是否加密cookie键值
    'secret_key'    => "123456", //加密密钥
];

new Cookie($config);

Cookie::set('key1', 'value1');
$value10 = Cookie::has('key1');
Cookie::delete('key1');
$value11 = Cookie::has('key1');

Cookie::set('key2', 'value2', ['encrypt_value' => false]);
$value20 = Cookie::has('key2', ['encrypt_value' => false]);
Cookie::delete('key2', ['encrypt_value' => false]);
$value21 = Cookie::has('key2', ['encrypt_value' => false]);

Cookie::set('key3', 'value3', ['encrypt_key' => false, 'encrypt_value' => false]);
$value30 = Cookie::has('key3', ['encrypt_key' => false, 'encrypt_value' => false]);
Cookie::delete('key3', ['encrypt_key' => false, 'encrypt_value' => false]);
$value31 = Cookie::has('key3', ['encrypt_key' => false, 'encrypt_value' => false]);

$result = [
    'errcode' => 0,
    'errmsg'  => '',
    'data'    => [
        'value10' => $value10,
        'value11' => $value11,
        'value20' => $value20,
        'value21' => $value21,
        'value30' => $value30,
        'value31' => $value31
    ]
];

print_r($result);
