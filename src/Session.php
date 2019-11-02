<?php


namespace fize\web;

use fize\session\Session as FizeSession;

/**
 * Session管理类
 * @package fize\framework
 */
class Session extends FizeSession
{

    /**
     * 初始化
     * @param array $config 配置
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }
}