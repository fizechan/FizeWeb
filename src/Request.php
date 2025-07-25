<?php

namespace Fize\Web;

/**
 * Request 请求
 */
class Request
{

    /**
     * @var string 请求方式
     */
    protected static $method;

    /**
     * @var array 当前 HEADER 参数
     */
    protected static $header = [];

    /**
     * @var string 当前请求内容
     */
    protected static $body;

    /**
     * @var string php://input 内容
     */
    protected static $input;

    /**
     * @var array 配置
     */
    protected static $config = [
        'var_method'       => '_method',  // 请求方式伪装字段
        'var_ajax'         => '_ajax',    // AJAX伪装字段
        'var_pjax'         => '_pjax',    // PJAX伪装字段
        'https_agent_name' => '',         // HTTPS代理标识
        'accept_type'      => '',         // 指定接受类型
    ];

    /**
     * 初始化静态属性
     * @param array $config 配置
     */
    public function __construct(array $config = [])
    {
        if ($config) {
            self::$config = array_merge(self::$config, $config);
        }
    }

    /**
     * 获取原生 SERVER
     * @param string|null $key     键名
     * @param string|null $default 默认值
     * @return mixed
     */
    public static function server(string $key = null, string $default = null)
    {
        if (is_null($key)) {
            return $_SERVER;
        }
        return $_SERVER[$key] ?? $default;
    }

    /**
     * 获取 GET 参数
     * @param string|null $key     键名
     * @param string|null $default 默认值
     * @return mixed
     */
    public static function get(string $key = null, string $default = null)
    {
        if (is_null($key)) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * 获取 POST 参数
     * @param string|null $key     键名
     * @param string|null $default 默认值
     * @return mixed
     */
    public static function post(string $key = null, string $default = null)
    {
        if (is_null($key)) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * 获取上传文件
     * @param string|null $key 键名
     * @return mixed
     */
    public static function files(string $key = null)
    {
        if (is_null($key)) {
            return $_FILES;
        }
        return $_FILES[$key] ?? null;
    }

    /**
     * 获取 REQUEST 参数
     * @param string|null $key 键名
     * @return mixed
     */
    public static function request(string $key = null)
    {
        if (is_null($key)) {
            return $_REQUEST;
        }
        return $_REQUEST[$key] ?? null;
    }

    /**
     * 获取 SESSION 参数
     * @param string|null $key     键名
     * @param string|null $default 默认值
     * @return mixed
     */
    public static function session(string $key = null, string $default = null)
    {
        if (is_null($key)) {
            return $_SESSION;
        }
        return $_SESSION[$key] ?? $default;
    }

    /**
     * 获取 ENV 参数
     * @param string|null $key     键名
     * @param string|null $default 默认值
     * @return mixed
     */
    public static function env(string $key = null, string $default = null)
    {
        if (is_null($key)) {
            return $_ENV;
        }
        return $_ENV[$key] ?? $default;
    }

    /**
     * 获取 COOKIE 参数
     * @param string|null $key     键名
     * @param string|null $default 默认值
     * @return mixed
     */
    public static function cookie(string $key = null, string $default = null)
    {
        if (is_null($key)) {
            return $_COOKIE;
        }
        return $_COOKIE[$key] ?? $default;
    }

    /**
     * 返回原始输入数据
     * @return string 失败时返回 false
     */
    public static function input(): string
    {
        if (!self::$input) {
            self::$input = file_get_contents('php://input');
        }
        return self::$input;
    }

    /**
     * 获取请求头
     * @param string|null $key     键名，不设置则返回请求头数组
     * @param mixed       $default 默认值
     * @return mixed
     */
    public static function header(string $key = null, $default = null)
    {
        if (!self::$header) {
            if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
                $header = $result;
            } else {
                $header = [];
                $server = $_SERVER;
                foreach ($server as $k => $val) {
                    if (0 === strpos($k, 'HTTP_')) {
                        $k = str_replace('_', '-', strtolower(substr($k, 5)));
                        $header[$k] = $val;
                    }
                }
                if (isset($server['CONTENT_TYPE'])) {
                    $header['content-type'] = $server['CONTENT_TYPE'];
                }
                if (isset($server['CONTENT_LENGTH'])) {
                    $header['content-length'] = $server['CONTENT_LENGTH'];
                }
            }
            self::$header = array_change_key_case($header);
        }
        if (is_null($key)) {
            return self::$header;
        }
        $key = strtoupper($key);
        return self::$header[$key] ?? $default;
    }

    /**
     * 当前请求 HTTP_CONTENT_TYPE
     * @return string
     */
    public static function contentType(): string
    {
        $content_type = self::server('CONTENT_TYPE');
        if ($content_type) {
            if (strpos($content_type, ';')) {
                list($type) = explode(';', $content_type);
            } else {
                $type = $content_type;
            }
            return trim($type);
        }
        return '';
    }

    /**
     * 当前的请求类型
     * @return string
     */
    public static function method(): string
    {
        if (!self::$method) {
            if (isset($_POST[self::$config['var_method']])) {
                $method = strtoupper($_POST[self::$config['var_method']]);
                if (in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
                    self::$method = $method;
                } else {
                    self::$method = 'POST';
                }
                unset($_POST[self::$config['var_method']]);
            } elseif (self::server('HTTP_X_HTTP_METHOD_OVERRIDE')) {
                self::$method = strtoupper(self::server('HTTP_X_HTTP_METHOD_OVERRIDE'));
            } else {
                self::$method = self::server('REQUEST_METHOD') ?: 'GET';
            }
        }
        return self::$method;
    }

    /**
     * 返回当前请求域名
     * @param bool $protocol 是否携带协议
     * @return string
     */
    public static function domain(bool $protocol = true): string
    {
        $domain = $_SERVER['HTTP_HOST'];
        if ($protocol) {
            $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $domain = $protocol . $domain;
        }
        return $domain;
    }

    /**
     * 返回当前请求 URL
     * @param bool $host     是否携带主机名
     * @param bool $protocol 是否携带协议
     * @return string
     */
    public static function url(bool $host = true, bool $protocol = true): string
    {
        $url = $_SERVER['REQUEST_URI'];
        if ($host) {
            $url = self::domain($protocol) . $url;
        }
        return $url;
    }

    /**
     * 客户端IP
     * @return string 无法识别返回“unknown”
     */
    public static function ip(): string
    {
        $sources = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        foreach ($sources as $source) {
            if (!empty($_SERVER[$source])) {
                $ip = trim(explode(',', $_SERVER[$source])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return 'unknown';
    }

    /**
     * 是否为 GET 请求
     * @return bool
     */
    public static function isGet(): bool
    {
        return self::method() == 'GET';
    }

    /**
     * 是否为 POST 请求
     * @return bool
     */
    public static function isPost(): bool
    {
        return self::method() == 'POST';
    }

    /**
     * 是否为 PUT 请求
     * @return bool
     */
    public static function isPut(): bool
    {
        return self::method() == 'PUT';
    }

    /**
     * 是否为 DELTE 请求
     * @return bool
     */
    public static function isDelete(): bool
    {
        return self::method() == 'DELETE';
    }

    /**
     * 是否为 HEAD 请求
     * @return bool
     */
    public static function isHead(): bool
    {
        return self::method() == 'HEAD';
    }

    /**
     * 是否为 PATCH 请求
     * @return bool
     */
    public static function isPatch(): bool
    {
        return self::method() == 'PATCH';
    }

    /**
     * 是否为 OPTIONS 请求
     * @return bool
     */
    public static function isOptions(): bool
    {
        return self::method() == 'OPTIONS';
    }

    /**
     * 是否为 cli
     * @return bool
     */
    public static function isCli(): bool
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * 是否为 cgi
     * @return bool
     */
    public static function isCgi(): bool
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }

    /**
     * 当前是否 ssl
     * @return bool
     */
    public static function isSsl(): bool
    {
        if (self::server('HTTPS') && ('1' == self::server('HTTPS') || 'on' == strtolower(self::server('HTTPS')))) {
            return true;
        } elseif ('https' == self::server('REQUEST_SCHEME')) {
            return true;
        } elseif ('443' == self::server('SERVER_PORT')) {
            return true;
        } elseif ('https' == self::server('HTTP_X_FORWARDED_PROTO')) {
            return true;
        } elseif (self::$config['https_agent_name'] && self::server(self::$config['https_agent_name'])) {
            return true;
        }

        return false;
    }

    /**
     * 当前是否 JSON 请求
     * @return bool
     */
    public static function isJson(): bool
    {
        return false !== strpos(self::contentType(), 'json') || false !== strpos(self::$config['accept_type'], 'json');
    }

    /**
     * 当前是否 Ajax 请求
     * @param string|null $var_ajax AJAX伪装字段
     * @return bool
     */
    public static function isAjax(string $var_ajax = null): bool
    {
        $value = self::server('HTTP_X_REQUESTED_WITH');
        $result = $value && 'xmlhttprequest' == strtolower($value);
        $var_ajax = $var_ajax ?: self::$config['var_ajax'];
        return self::request($var_ajax) ? true : $result;
    }

    /**
     * 当前是否 Pjax 请求
     * @return bool
     */
    public static function isPjax(): bool
    {
        $result = !is_null(self::server('HTTP_X_PJAX'));
        return self::request(self::$config['var_pjax']) ? true : $result;
    }

    /**
     * 检测是否使用手机访问
     * @return bool
     */
    public static function isMobile(): bool
    {
        if (self::server('HTTP_VIA') && stristr(self::server('HTTP_VIA'), "wap")) {
            return true;
        }
        if (self::server('HTTP_ACCEPT') && strpos(strtoupper(self::server('HTTP_ACCEPT')), "VND.WAP.WML")) {
            return true;
        }
        if (self::server('HTTP_X_WAP_PROFILE') || self::server('HTTP_PROFILE')) {
            return true;
        }
        if (self::server('HTTP_USER_AGENT') && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', self::server('HTTP_USER_AGENT'))) {
            return true;
        }

        return false;
    }
}
