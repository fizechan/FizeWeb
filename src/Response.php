<?php

namespace fize\web;

use fize\crypt\Json;

/**
 * 响应类
 */
class Response
{

    /**
     * @var string 当前contentType
     */
    protected $contentType = 'text/html';

    /**
     * @var string 字符集
     */
    protected $charset = 'utf-8';

    /**
     * @var int 状态码
     */
    protected $code = 200;

    /**
     * @var array header参数
     */
    protected $header = [];

    /**
     * @var string 响应主体内容
     */
    protected $content = null;

    /**
     * 设置或获取响应主体内容
     * @param string $content 主体内容
     * @return string
     */
    public function content($content = null)
    {
        if (!is_null($content)) {
            $this->content = $content;
        }
        return $this->content;
    }

    /**
     * HTTP状态
     * @param int $code 状态码
     * @return int
     */
    public function code($code = null)
    {
        if (!is_null($code)) {
            $this->code = $code;
        }
        return $this->code;
    }

    /**
     * 添加响应头获取返回响应头
     * @param mixed $header 要附加的响应头，数组则附加数组，字符串则附加单个，如果不指定该参数则返回当前响应头
     * @param null $value 如果指定该值则为key的值
     * @return array 返回响应头
     */
    public function header($header = null, $value = null)
    {
        if (is_null($header)) {
            return $this->header;
        }
        if (is_array($header)) {
            $this->header = array_merge($this->header, $header);
        }

        if (!is_null($value)) {
            $header = "{$header}: {$value}";
        }
        $this->header[] = $header;
        return $this->header;
    }

    /**
     * 页面输出类型
     * @param string $content_type 输出类型
     * @param string $charset 输出编码
     */
    public function contentType($content_type, $charset = 'utf-8')
    {
        $this->header('Content-Type', $content_type . '; charset=' . $charset);
    }

    /**
     * 发送响应
     */
    public function send()
    {
        $http_code = [
            '100' => 'Continue',
            '101' => 'Switching Protocols',
            '102' => 'Processing',
            '200' => 'OK',
            '201' => 'Created',
            '202' => 'Accepted',
            '203' => 'Non-Authoritative Information',
            '204' => 'No Content',
            '205' => 'Reset Content',
            '206' => 'Partial Content',
            '207' => 'Multi-Status',
            '300' => 'Multiple Choices',
            '301' => 'Moved Permanently',
            '302' => 'Move Temporarily',
            '303' => 'See Other',
            '304' => 'Not Modified',
            '305' => 'Use Proxy',
            '306' => 'Switch Proxy',
            '307' => 'Temporary Redirect',
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '402' => 'Payment Required',
            '403' => 'Forbidden',
            '404' => 'Not Found',
            '405' => 'Method Not Allowed',
            '406' => 'Not Acceptable',
            '407' => 'Proxy Authentication Required',
            '408' => 'Request Timeout',
            '409' => 'Conflict',
            '410' => 'Gone',
            '411' => 'Length Required',
            '412' => 'Precondition Failed',
            '413' => 'Request Entity Too Large',
            '414' => 'Request-URI Too Long',
            '415' => 'Unsupported Media Type',
            '416' => 'Requested Range Not Satisfiable',
            '417' => 'Expectation Failed',
            '418' => 'I\'m a teapot',
            '421' => 'Too Many Connections',
            '422' => 'Unprocessable Entity',
            '423' => 'Locked',
            '424' => 'Failed Dependency',
            '425' => 'Too Early',
            '426' => 'Upgrade Required',
            '449' => 'Retry With',
            '451' => 'Unavailable For Legal Reasons',
            '500' => 'Internal Server Error',
            '501' => 'Not Implemented',
            '502' => 'Bad Gateway',
            '503' => 'Service Unavailable',
            '504' => 'Gateway Timeout',
            '505' => 'HTTP Version Not Supported',
            '509' => 'Bandwidth Limit Exceeded',
            '510' => 'Not Extended',
            '600' => 'Unparseable Response Headers',
        ];

        if(is_numeric($this->code) && isset($http_code[$this->code])) {
            $this->code = $this->code . ' ' . $http_code[$this->code];
        }
        header('HTTP/1.1 ' . $this->code);
        header('Status: ' . $this->code);
        foreach ($this->header as $header) {
            header($header);
        }
        if($this->content) {
            echo $this->content;
        }
    }

    /**
     * 强制浏览器不进行缓存
     */
    public static function noCache()
    {
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
    }

    /**
     * JSON响应
     * @param array|string $json 数组或者JSON字符串
     * @param string $charset 输出编码
     * @return Response
     */
    public static function json($json, $charset = 'utf-8')
    {
        if (is_array($json)) {
            $json = Json::encode($json);
        }
        $response = new Response();
        $response->contentType('application/json', $charset);
        $response->content($json);
        return $response;
    }

    /**
     * HTML响应
     * @param string $html HTML内容
     * @param string $charset 输出编码
     * @return Response
     */
    public static function html($html, $charset = 'utf-8')
    {
        $response = new Response();
        $response->contentType('text/html', $charset);
        $response->content($html);
        return $response;
    }

    /**
     * XML响应
     * @param string $xml XML内容
     * @param string $charset 输出编码
     * @return Response
     */
    public static function xml($xml, $charset = 'utf-8')
    {
        $response = new Response();
        $response->contentType('text/xml', $charset);
        $response->content($xml);
        return $response;
    }

    /**
     * 跳转
     * @param string $url 跳转URL
     * @param int $delay 延迟时间，以秒为单位
     * @return Response
     */
    public static function redirect($url, $delay = null)
    {
        $response = new Response();
        $response->code(302);
        if (is_null($delay)) {
            $response->header('Location', $url);
        } else {
            $response->header("Refresh: {$delay}; url={$url}");
        }
        return $response;
    }

    /**
     * 下载
     * @param string $file 要下载的文件路径
     * @param string $filename 下载文件名
     * @return Response
     */
    public static function download($file, $filename = null)
    {
        if (is_null($filename)) {
            $filename = basename($file);
        }

        $response = new Response();
        $response->header('Content-Type', 'application/octet-stream');
        $response->header('Content-Disposition: attachment; filename="' . $filename . '"');
        $response->header('Content-Transfer-Encoding: binary');
        $response->content(file_get_contents($file));
        return $response;
    }
}