<?php

namespace fize\web;

use fize\security\OpenSSL;

/**
 * Cookie 管理类
 */
class Cookie
{

    /**
     * @var array 当前配置
     */
    protected static $config;

    /**
     * @var OpenSSL 开启加密时使用到的 OpenSSL 对象
     */
    protected static $openssl;

    /**
     * cookie 被篡改时的事件回调函数收集器
     * @var array
     */
    private static $onTamperEvent = [];

    /**
     * 初始化
     *
     * 使用 Cookie 静态方法前请先执行初始化
     * 注意开启 httponly 后，前端 JS 是无法获取到 cookie 的。
     * 如果需要前端 js 获取 cookie ，可在设置 cookie 时禁用 httponly。
     * @param array $config 要更改的配置项
     */
    public function __construct(array $config = [])
    {
        $default_config = [
            'expire'        => 3600, //cookie有效时间，以秒为单位
            'path'          => "/", //Cookie路径
            'domain'        => "", //Cookie有效域名
            'secure'        => false, //是否只允许在HTTPS安全链接下生效
            'httponly'      => true, //是否使用httponly，为安全性，全局默认开启
            'prefix'        => "", //Cookie键名前缀,如果发生冲突可以修改该值
            'encode_key'    => false, //是否加密cookie键名，加密键名则需要对所有cookie进行遍历获取，不合适cookie过多的情况
            'encode_value'  => false, //是否加密cookie键值
            'secret_key'    => "", //加密密钥
        ];
        self::$config = array_merge($default_config, $config);
        
        if(self::$config['encode_key'] || self::$config['encode_value']) {
            self::$openssl = new OpenSSL();
            self::$openssl->setKey(self::$config['secret_key']);
        }
    }

    /**
     * 加密
     * @param string $value 待加密字符串
     * @return string
     */
    protected static function encode($value)
    {
        $iv64="AAECAwQFBgcICQoLDA0ODw==";  //@todo 不应存在强指定项
        $iv = base64_decode($iv64, true);
        return self::$openssl->encrypt($value, 'aes-256-cbc', 0, $iv);
    }

    /**
     * 解密
     * @param string $value 待解密字符串
     * @return string
     */
    protected static function decode($value)
    {
        $iv64="AAECAwQFBgcICQoLDA0ODw==";  //@todo 不应存在强指定项
        $iv = base64_decode($iv64, true);
        return self::$openssl->decrypt($value, 'aes-256-cbc', 0, $iv);
    }

    /**
     * 绑定 cookie 被篡改事件
     *
     * 参数 `$func` :
     *   该回调参数定义为 ($key, $value)
     * @param callable $func cookie 被篡改事件回调函数
     */
    public static function onTamper(callable $func)
    {
        self::$onTamperEvent[] = $func;
    }

    /**
     * 触发 cookie 被篡改事件
     *
     * 参数 `$key` :
     *   cookie 键名(解密后)
     * 参数 `$value` :
     *   键值(无法解密的原加密字符串)
     * @param string $key 获取到的 cookie 键名
     * @param string $value 获取到的 cookie 键值
     */
    private static function fireTamperEvent($key, $value)
    {
        foreach (self::$onTamperEvent as $func) {
            $func($key, $value);
        }
    }

    /**
     * 设置一个 cookie
     *
     * 参数 `$config` :
     *   类型为 int 表示有效时长，array 表示临时指定的配置
     * @param string $key 键名
     * @param string $value 键值
     * @param array|int $config 有效时长或临时指定的配置
     */
    public static function set($key, $value, $config = [])
    {
        if (is_numeric($config)) {
            $config = [
                'expire' => $config
            ];
        }
        $config = array_merge(self::$config, $config);
        $key = $config['prefix'] . $key;
        if ($config['encode_key']) {
            $no_find = true;
            foreach ($_COOKIE as $k => $v) {
                if (self::decode($k) == $key) {  //cookie会自动进行urldecode
                    $key = urlencode($k);  //手动urlencode防止传入非法字符
                    $no_find = false;
                    break;
                }
            }
            if ($no_find) {
                $key = urlencode(self::encode($key));  //手动urlencode防止传入非法字符
            }
        }
        if ($config['encode_value']) {
            $value = self::encode($value);
        }
        setcookie($key, $value, time() + $config['expire'], $config['path'], $config['domain'], $config['secure'], $config['httponly']);
        //使当前生效
        if($config['encode_key']) {
            $_COOKIE[urldecode($key)] = $value;
        } else {
            $_COOKIE[$key] = $value;
        }
    }

    /**
     * 获取指定 cookie 值，未设置则返回 false
     *
     * 参数 `$config` :
     *   附加和设置 cookie 时相同的配置才能获取到
     * @param string $key cookie 名(加密前)
     * @param array $config 附加设置
     * @return string
     */
    public static function get($key, array $config = [])
    {
        $value = '';
        $config = array_merge(self::$config, $config);
        $key = $config['prefix'] . $key;
        if ($config['encode_key']) {
            $no_find = true;
            foreach ($_COOKIE as $k => $v) {
                if (self::decode($k) == $key) {  //cookie会自动进行urldecode
                    $value = $v;
                    $no_find = false;
                    break;
                }
            }
            if ($no_find) {
                return false;
            }
        } else {
            if (!isset($_COOKIE[$key])) {
                return false;
            }
            $value = $_COOKIE[$key];
        }
        if ($config['encode_value']) {
            $decode_str = self::decode($value);
            if ($decode_str === false) {
                self::fireTamperEvent($key, $value);
                return false;
            }
            $value = $decode_str;
        }
        return $value;
    }

    /**
     * 判断 Cookie 是否存在
     *
     * 参数 `$config` :
     *   附加和设置 cookie 时相同的配置才能获取到
     * @param string $key cookie 名(加密前)
     * @param array $config 附加设置
     * @return bool
     */
    public static function has($key, array $config = [])
    {
        return self::get($key, $config) !== false;
    }

    /**
     * 删除某个 Cookie 值
     *
     * 参数 `$config` :
     *   附加和设置 cookie 时相同的配置才能正确操作
     * @param string $key cookie 键名
     * @param array $config 附加设置
     */
    public static function remove($key, array $config = [])
    {
        $config = array_merge(self::$config, $config);
        $key = $config['prefix'] . $key;

        if ($config['encode_key']) {
            $no_find = true;
            foreach ($_COOKIE as $k => $v) {
                if (self::decode($k) == $key) {  //cookie会自动进行urldecode
                    $key = urlencode($k);  //手动urlencode防止传入非法字符
                    $no_find = false;
                    break;
                }
            }
            if ($no_find) {
                $key = urlencode(self::encode($key));  //手动urlencode防止传入非法字符
            }
        }

        setcookie($key, '', -3600);

        //下文马上失效
        if(self::$config['encode_key']) {
            unset($_COOKIE[urldecode($key)]);
        } else {
            unset($_COOKIE[$key]);
        }
    }

    /**
     * 清空 Cookie 值
     */
    public static function clear()
    {
        foreach ($_COOKIE as $key => $value) {
            setcookie($key, '', -3600);
            unset($_COOKIE[$key]);  //下文马上失效
        }
    }
}
