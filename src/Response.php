<?php

namespace Fize\Web;

use Fize\Codec\Json;
use Fize\Http\Response as HttpResponse;
use Fize\Http\StreamFactory;

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
        echo $this->getBody()->getContents();
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
    public static function json($json, string $charset = 'utf-8'): Response
    {
        if (is_array($json)) {
            $json = Json::encode($json);
        }
        $response = new Response();
        $response = $response
            ->contentType('application/json', $charset)
            ->withBody((new StreamFactory())->createStream($json));
        return $response;
    }

    /**
     * HTML 响应
     * @param string $html    HTML 内容
     * @param string $charset 输出编码
     * @return Response
     */
    public static function html(string $html, string $charset = 'utf-8'): Response
    {
        $response = new Response();
        $response = $response
            ->contentType('text/html', $charset)
            ->withBody((new StreamFactory())->createStream($html));
        return $response;
    }

    /**
     * XML 响应
     * @param string $xml     XML 内容
     * @param string $charset 输出编码
     * @return Response
     */
    public static function xml(string $xml, string $charset = 'utf-8'): Response
    {
        $response = new Response();
        $response = $response
            ->contentType('text/xml', $charset)
            ->withBody((new StreamFactory())->createStream($xml));
        return $response;
    }

    /**
     * 跳转
     * @param string   $url   跳转 URL
     * @param int|null $delay 延迟时间，以秒为单位
     * @return Response
     */
    public static function redirect(string $url, int $delay = null): Response
    {
        $response = new Response();
        $response = $response
            ->withStatus(302);
        if (is_null($delay)) {
            $response = $response->withHeader('Location', $url);
        } else {
            $response = $response->withHeader("Refresh", "$delay; url=$url");
        }
        return $response;
    }

    /**
     * 下载
     * @param string      $file     要下载的文件路径
     * @param string|null $filename 下载文件名
     * @return Response
     */
    public static function download(string $file, string $filename = null): Response
    {
        if (is_null($filename)) {
            $filename = basename($file);
        }

        $response = new Response();
        $response = $response
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withBody((new StreamFactory())->createStreamFromFile($file));
        return $response;
    }

    /**
     * 页面输出类型
     * @param string $content_type 输出类型
     * @param string $charset      输出编码
     * @return Response
     */
    protected function contentType(string $content_type, string $charset = 'utf-8'): Response
    {
        return $this->withHeader('Content-Type', $content_type . '; charset=' . $charset);
    }
}
