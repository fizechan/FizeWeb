<?php


namespace fize\web;

use fize\session\Session as FizeSession;

/**
 * Session管理类
 */
class Session extends FizeSession
{

    /**
     * 获取一个缓存
     * @param string $name 缓存名
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get($name, $default = null)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
    }

    /**
     * 查看指定缓存是否存在
     * @param string $name 缓存名
     * @return bool
     */
    public static function has($name)
    {
        return isset($_SESSION[$name]);
    }

    /**
     * 设置一个缓存
     * @param string $name 缓存名
     * @param mixed $value 缓存值
     */
    public static function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * 删除一个缓存
     * @param string $name 缓存名
     */
    public static function remove($name)
    {
        unset($_SESSION[$name]);
    }
}