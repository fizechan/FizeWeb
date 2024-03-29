<?php

namespace Fize\Web;

use Fize\Session\Session as FizeSession;

/**
 * Session 管理
 */
class Session
{

    /**
     * 构造
     *
     * 在构造方法中初始化 Session 底层管理
     * 使用 Session 静态方法前请先执行初始化
     * @param array $config 配置
     */
    public function __construct(array $config = [])
    {
        new FizeSession($config);
    }

    /**
     * 获取一个 Session 值
     * @param string $name    名称
     * @param mixed  $default 默认值
     * @return mixed
     */
    public static function get(string $name, $default = null)
    {
        return $_SESSION[$name] ?? $default;
    }

    /**
     * 查看指定 Session 值是否存在
     * @param string $name 名称
     * @return bool
     */
    public static function has(string $name): bool
    {
        return isset($_SESSION[$name]);
    }

    /**
     * 设置一个 Session 值
     * @param string $name  名称
     * @param mixed  $value 值
     */
    public static function set(string $name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * 删除一个 Session
     * @param string $name 名称
     */
    public static function delete(string $name)
    {
        unset($_SESSION[$name]);
    }

    /**
     * 清空 SESSION
     */
    public static function clear()
    {
        FizeSession::unset();
    }
}
