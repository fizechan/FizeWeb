<?php

namespace Fize\Web;

/**
 * Cookie 管理
 */
class Cookie
{

    /**
     * @var array 当前配置
     */
    protected static $config;

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
            'expire'   => 3600, //cookie有效时间，以秒为单位
            'path'     => '/', //Cookie路径
            'domain'   => '', //Cookie有效域名
            'secure'   => false, //是否只允许在HTTPS安全链接下生效
            'httponly' => true, //是否使用httponly，为安全性，全局默认开启
            'prefix'   => '', //Cookie键名前缀,如果发生冲突可以修改该值
        ];
        self::$config = array_merge($default_config, $config);
    }

    /**
     * 设置一个 cookie
     *
     * 参数 `$config` :
     *   类型为 int 表示有效时长，array 表示临时指定的配置
     * @param string    $key    键名
     * @param string    $value  键值
     * @param array|int $config 有效时长或临时指定的配置
     */
    public static function set(string $key, string $value, $config = [])
    {
        if (is_numeric($config)) {  // $config为数字时表示有效时长
            $config = [
                'expire' => $config
            ];
        }
        $config = array_merge(self::$config, $config);
        $key = $config['prefix'] . $key;
        setcookie($key, $value, time() + $config['expire'], $config['path'], $config['domain'], $config['secure'], $config['httponly']);
        // 使当前生效
        $_COOKIE[$key] = $value;
    }

    /**
     * 获取指定 cookie 值，未设置则返回 false
     *
     * 参数 `$config` :
     *   附加和设置 cookie 时相同的配置才能获取到
     * @param string $key    cookie 名
     * @param array  $config 附加设置
     * @return string
     */
    public static function get(string $key, array $config = [])
    {
        $config = array_merge(self::$config, $config);
        $key = $config['prefix'] . $key;
        if (!isset($_COOKIE[$key])) {
            return false;
        }
        return $_COOKIE[$key];
    }

    /**
     * 判断 Cookie 是否存在
     *
     * 参数 `$config` :
     *   附加和设置 cookie 时相同的配置才能获取到
     * @param string $key    cookie 名(加密前)
     * @param array  $config 附加设置
     * @return bool
     */
    public static function has(string $key, array $config = []): bool
    {
        return self::get($key, $config) !== false;
    }

    /**
     * 删除某个 Cookie 值
     *
     * 参数 `$config` :
     *   附加和设置 cookie 时相同的配置才能正确操作
     * @param string $key    cookie 键名
     * @param array  $config 附加设置
     */
    public static function delete(string $key, array $config = [])
    {
        $config = array_merge(self::$config, $config);
        $key = $config['prefix'] . $key;
        setcookie($key, '', -3600);

        // 下文马上失效
        unset($_COOKIE[$key]);
    }

    /**
     * 清空 Cookie 值
     */
    public static function clear()
    {
        foreach ($_COOKIE as $key => $value) {
            setcookie($key, '', -3600);
            unset($_COOKIE[$key]);  // 下文马上失效
        }
    }
}
