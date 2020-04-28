<?php

namespace fize\web;

use fize\http\Response as HttpResponse;
use fize\http\Stream;
use fize\crypt\Json;

/**
 * Response 响应
 */
class Response extends HttpResponse
{
    /**
     * 发送响应
     */
    public function send()
    {
        header('HTTP/' . $this->getProtocolVersion() . ' ' . $this->getStatusCode() . ' ' . $this->getReasonPhrase());
        header('Status: ' . $this->getStatusCode() . ' ' . $this->getReasonPhrase());
        foreach ($this->getHeaders() as $key => $header) {
            header($key . ': ' . implode(', ', $header));
        }
        echo (string)$this->getBody();
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
     * JSON 响应
     * @param array|string $json    数组或者 JSON 字符串
     * @param string       $charset 输出编码
     * @return Response
     */
    public static function json($json, $charset = 'utf-8')
    {
        if (is_array($json)) {
            $json = Json::encode($json);
        }
        $response = new Response();
        $response = $response
            ->contentType('application/json', $charset)
            ->withBody(Stream::create($json));
        return $response;
    }

    /**
     * HTML 响应
     * @param string $html    HTML 内容
     * @param string $charset 输出编码
     * @return Response
     */
    public static function html($html, $charset = 'utf-8')
    {
        $response = new Response();
        $response = $response
            ->contentType('text/html', $charset)
            ->withBody(Stream::create($html));
        return $response;
    }

    /**
     * XML 响应
     * @param string $xml     XML 内容
     * @param string $charset 输出编码
     * @return Response
     */
    public static function xml($xml, $charset = 'utf-8')
    {
        $response = new Response();
        $response = $response
            ->contentType('text/xml', $charset)
            ->withBody(Stream::create($xml));
        return $response;
    }

    /**
     * 跳转
     * @param string $url   跳转 URL
     * @param int    $delay 延迟时间，以秒为单位
     * @return Response
     */
    public static function redirect($url, $delay = null)
    {
        $response = new Response();
        $response = $response
            ->withStatus(302);
        if (is_null($delay)) {
            $response = $response->withHeader('Location', $url);
        } else {
            $response = $response->withHeader("Refresh", "{$delay}; url={$url}");
        }
        return $response;
    }

    /**
     * 下载
     * @param string $file     要下载的文件路径
     * @param string $filename 下载文件名
     * @return Response
     */
    public static function download($file, $filename = null)
    {
        if (is_null($filename)) {
            $filename = basename($file);
        }

        $response = new Response();
        $response = $response
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withBody(new Stream($file));
        return $response;
    }

    /**
     * 页面输出类型
     * @param string $content_type 输出类型
     * @param string $charset      输出编码
     * @return Response
     */
    protected function contentType($content_type, $charset = 'utf-8')
    {
        return $this->withHeader('Content-Type', $content_type . '; charset=' . $charset);
    }
}
