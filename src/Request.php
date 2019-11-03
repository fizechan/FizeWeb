<?php
/** @noinspection PhpComposerExtensionStubsInspection */

namespace fize\web;

/**
 * 请求类
 */
class Request
{

    /**
     * @var string 请求方式
     */
    protected static $method;

    /**
     * @var array 当前HEADER参数
     */
    protected static $header = [];

    /**
     * @var string 当前请求内容
     */
    protected static $body;

    /**
     * @var array 资源类型定义
     */
    protected static $mimeType = [
        'xml'   => 'application/xml,text/xml,application/x-xml',
        'json'  => 'application/json,text/x-json,application/jsonrequest,text/json',
        'js'    => 'text/javascript,application/javascript,application/x-javascript',
        'css'   => 'text/css',
        'rss'   => 'application/rss+xml',
        'yaml'  => 'application/x-yaml,text/yaml',
        'atom'  => 'application/atom+xml',
        'pdf'   => 'application/pdf',
        'text'  => 'text/plain',
        'image' => 'image/png,image/jpg,image/jpeg,image/pjpeg,image/gif,image/webp,image/*',
        'csv'   => 'text/csv',
        'html'  => 'text/html,application/xhtml+xml,*/*',
    ];

    /**
     * @var string php://input内容
     */
    protected static $input;

    /**
     * @var array 配置
     */
    protected static $config;

    /**
     * 初始化静态属性
     * @param array $config 配置
     */
    public function __construct(array $config =[])
    {
        $default_config = [
            'var_method'       => '_method',  //请求方式伪装字段
            'var_ajax'         => '_ajax',  //AJAX伪装字段
            'var_pjax'         => '_pjax',  //PJAX伪装字段
            'https_agent_name' => '',  //HTTPS代理标识
            'accept_type'      => '',  //指定接受类型
        ];
        $config = array_merge($default_config, $config);
        self::$config = $config;
    }

    /**
     * 获取原生SERVER
     * @param string $key 键名
     * @param string $default 默认值
     * @return mixed
     */
    public static function server($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_SERVER;
        }
        return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
    }

    /**
     * 获取GET参数
     * @param string $key 键名
     * @param string $default 默认值
     * @return mixed
     */
    public static function get($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_GET;
        }
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    /**
     * 获取POST参数
     * @param string $key 键名
     * @param string $default 默认值
     * @return mixed
     */
    public static function post($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_POST;
        }
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    /**
     * 获取上传文件
     * @param string $key 键名
     * @return mixed
     */
    public static function files($key = null)
    {
        if (is_null($key)) {
            return $_FILES;
        }
        return isset($_FILES[$key]) ? $_FILES[$key] : null;
    }

    /**
     * 获取REQUEST参数
     * @param string $key 键名
     * @return mixed
     */
    public static function request($key = null)
    {
        if (is_null($key)) {
            return $_REQUEST;
        }
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
    }

    /**
     * 获取SESSION参数
     * @param string $key 键名
     * @param string $default 默认值
     * @return mixed
     */
    public static function session($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_SESSION;
        }
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    /**
     * 获取ENV参数
     * @param string $key 键名
     * @param string $default 默认值
     * @return mixed
     */
    public static function env($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_ENV;
        }
        return isset($_ENV[$key]) ? $_ENV[$key] : $default;
    }

    /**
     * 获取COOKIE参数
     * @param string $key 键名
     * @param string $default 默认值
     * @return mixed
     */
    public static function cookie($key = null, $default = null)
    {
        if (is_null($key)) {
            return $_COOKIE;
        }
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    /**
     * 返回原始输入数据
     * @return string 失败时返回false
     */
    public static function input()
    {
        if (!self::$input) {
            self::$input = file_get_contents('php://input');
        }
        return self::$input;
    }

    /**
     * 获取请求头
     * @param string $key 键名，不设置则返回请求头数组
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function header($key = null, $default = null)
    {
        if (!self::$header) {
            if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
                $header = $result;
            } else {
                $header = [];
                $server = $_SERVER;
                foreach ($server as $key => $val) {
                    if (0 === strpos($key, 'HTTP_')) {
                        $key = str_replace('_', '-', strtolower(substr($key, 5)));
                        $header[$key] = $val;
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
        return isset(self::$header[$key]) ? self::$header[$key] : $default;
    }

    /**
     * 当前请求 HTTP_CONTENT_TYPE
     * @return string
     */
    public static function contentType()
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
    public static function method()
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
     * 是否为GET请求
     * @return bool
     */
    public static function isGet()
    {
        return self::method() == 'GET';
    }

    /**
     * 是否为POST请求
     * @return bool
     */
    public static function isPost()
    {
        return self::method() == 'POST';
    }

    /**
     * 是否为PUT请求
     * @return bool
     */
    public static function isPut()
    {
        return self::method() == 'PUT';
    }

    /**
     * 是否为DELTE请求
     * @return bool
     */
    public static function isDelete()
    {
        return self::method() == 'DELETE';
    }

    /**
     * 是否为HEAD请求
     * @return bool
     */
    public static function isHead()
    {
        return self::method() == 'HEAD';
    }

    /**
     * 是否为PATCH请求
     * @return bool
     */
    public static function isPatch()
    {
        return self::method() == 'PATCH';
    }

    /**
     * 是否为OPTIONS请求
     * @return bool
     */
    public static function isOptions()
    {
        return self::method() == 'OPTIONS';
    }

    /**
     * 是否为cli
     * @return bool
     */
    public static function isCli()
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * 是否为cgi
     * @return bool
     */
    public static function isCgi()
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }

    /**
     * 当前是否ssl
     * @return bool
     */
    public static function isSsl()
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
     * 当前是否JSON请求
     * @return bool
     */
    public static function isJson()
    {
        return false !== strpos(self::contentType(), 'json') || false !== strpos(self::$config['accept_type'], 'json');
    }

    /**
     * 当前是否Ajax请求
     * @return bool
     */
    public static function isAjax()
    {
        $value = self::server('HTTP_X_REQUESTED_WITH');
        $result = $value && 'xmlhttprequest' == strtolower($value) ? true : false;
        return self::request(self::$config['var_ajax']) ? true : $result;
    }

    /**
     * 当前是否Pjax请求
     * @return bool
     */
    public static function isPjax()
    {
        $result = !is_null(self::server('HTTP_X_PJAX')) ? true : false;
        return self::request(self::$config['var_pjax']) ? true : $result;
    }

    /**
     * 检测是否使用手机访问
     * @return bool
     */
    public static function isMobile()
    {
        if (self::server('HTTP_VIA') && stristr(self::server('HTTP_VIA'), "wap")) {
            return true;
        } elseif (self::server('HTTP_ACCEPT') && strpos(strtoupper(self::server('HTTP_ACCEPT')), "VND.WAP.WML")) {
            return true;
        } elseif (self::server('HTTP_X_WAP_PROFILE') || self::server('HTTP_PROFILE')) {
            return true;
        } elseif (self::server('HTTP_USER_AGENT') && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', self::server('HTTP_USER_AGENT'))) {
            return true;
        }

        return false;
    }
}