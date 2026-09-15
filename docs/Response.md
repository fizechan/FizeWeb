# Response

面向浏览器/客户端的 HTTP 响应构造与发送。本类继承 `Fize\Http\Response`（PSR-7 `ResponseInterface`），并加上 JSON / HTML / XML / 重定向 / 文件下载等工厂方法。

- **类名**：`Fize\Web\Response`
- **源码**：[`src/Response.php`](../src/Response.php)
- **命名空间**：`Fize\Web`
- **父类**：`Fize\Http\Response` → `Fize\Http\Message` → `Psr\Http\Message\ResponseInterface`
- **依赖**：`fize/http`（响应对象与 `StreamFactory`）、`fize/codec`（JSON 编码）
- **相关**：[Request](Request.md) · [index](index.md)

---

## 1. 概述

典型用法是：**工厂方法创建实例 → 可选再调 PSR-7 的 `with*` → `send()` 输出**。

```php
use Fize\Web\Response;

Response::json(['ok' => true])->send();
```

`send()` 会：

1. 发送状态行：`HTTP/{version} {code} {reason}`
2. 再发一条 `Status: {code} {reason}`（兼容部分旧 CGI/FastCGI）
3. 遍历 `getHeaders()`，每条头 `implode(', ', $values)` 后 `header()`
4. `echo` 响应体 `getContents()`

`send()` **不会** `exit`。调用后脚本继续执行；若后面还有输出，会拼在正文后面。

---

## 2. 不可变约定

父类 `withHeader()`、`withStatus()`、`withBody()` 等按 PSR-7 **克隆新实例**。本类中：

| 方法 | 是否返回新实例 |
|------|----------------|
| `json` / `html` / `xml` / `redirect` / `download` | 是（工厂，全新对象） |
| `noCache()` | 是（内部两次 `withHeader`） |
| `contentType()`（protected） | 是 |
| `send()` | 否，对**当前**实例发头并 echo |

必须接住返回值：

```php
// 错误：noCache() 的结果被丢弃，send() 的对象没有那两个头
$response = Response::html('<p>hi</p>');
$response->noCache();
$response->send();

// 正确
Response::html('<p>hi</p>')->noCache()->send();
```

---

## 3. 构造

本类未覆盖构造函数，使用父类：

```php
public function __construct(
    $body = null,
    int $status = 200,
    array $headers = [],
    string $protocol_version = '1.1',
    string $reason = null
)
```

| 参数 | 说明 |
|------|------|
| `$body` | `string`、`resource`、`StreamInterface` 或 `null` |
| `$status` | 100–599 的整数，默认 200 |
| `$headers` | 头数组 |
| `$protocol_version` | 默认 `1.1` |
| `$reason` | 空则按状态码填入内置短语表（如 200 → `OK`） |

日常更常用下面的静态工厂，而不是直接 `new Response()`。

---

## 4. 本类方法

### `send`

```php
public function send()
```

无返回值。依赖 PHP `header()`，必须在脚本产生实际输出之前调用，否则会 “headers already sent”。

不会设置 `Content-Length`。体来自 PSR-7 Stream；`getContents()` 从当前指针读到末尾。若流已被读过且不可 `seek`，可能输出空串。工厂方法新建的流一般没有这个问题。

### `noCache`

```php
public function noCache(): Response
```

等价于：

```http
Cache-Control: no-cache
Pragma: no-cache
```

返回带这两个头的新实例，便于链在工厂方法之后。

### `contentType`（protected）

```php
protected function contentType(string $content_type, string $charset = 'utf-8'): Response
```

设置：

```http
Content-Type: {content_type}; charset={charset}
```

供 `json()` / `html()` / `xml()` 使用，外部不能直接调用。

### `json`

```php
public static function json($json, string $charset = 'utf-8'): Response
```

| 参数 | 说明 |
|------|------|
| `$json` | `array` 时用 `Fize\Codec\Json::encode()`；`string` 时原样作为正文 |
| `$charset` | Content-Type 的 charset，默认 `utf-8` |

状态码默认 200。需要 4xx/5xx 时再 `withStatus()`：

```php
Response::json(['error' => 'not found'])
    ->withStatus(404)
    ->send();
```

传入已是 JSON 的字符串时**不会**再编码，也不会校验合法性。

### `html`

```php
public static function html(string $html, string $charset = 'utf-8'): Response
```

`Content-Type: text/html; charset=…`，正文为 `$html`。

```php
Response::html('<h1>Hello</h1>')->send();
```

### `xml`

```php
public static function xml(string $xml, string $charset = 'utf-8'): Response
```

`Content-Type: text/xml; charset=…`。参数必须是 XML **字符串**，本类不做数组转 XML。

```php
Response::xml('<?xml version="1.0"?><ok>1</ok>')->send();
```

### `redirect`

```php
public static function redirect(string $url, int $delay = null): Response
```

状态码固定 **302**。

| `$delay` | 行为 |
|----------|------|
| `null`（默认） | 头 `Location: {url}`，立即跳转 |
| 整数秒 | 头 `Refresh: {delay}; url={url}`，不设 `Location` |

```php
Response::redirect('/login')->send();
Response::redirect('https://example.com', 3)->send();
```

需要 301 / 303 / 307 时：

```php
Response::redirect('/new')->withStatus(301)->send();
```

注意：`withStatus(301)` 之后，若原先走的是 `Location`，头仍在；状态短语随父类短语表更新。

### `download`

```php
public static function download(string $file, string $filename = null): Response
```

| 参数 | 说明 |
|------|------|
| `$file` | 服务器本地路径，交给 `StreamFactory::createStreamFromFile()` |
| `$filename` | 下载显示名；`null` 时用 `basename($file)` |

设置的头：

```http
Content-Type: application/octet-stream
Content-Disposition: attachment; filename="{filename}"
Content-Transfer-Encoding: binary
```

文件必须存在且可读，否则底层建流会失败。`filename` 直接拼进头，含 `"` 或非 ASCII 时可能不符合 RFC 5987，需要的话应自行 `withHeader` 覆盖 `Content-Disposition`。

```php
Response::download('/var/data/report.csv', '报表.csv')->send();
```

---

## 5. 从父类继承的常用方法

完整 PSR-7 语义见 [psr/http-message](https://www.php-fig.org/psr/psr-7/) 与 `fize/http`。下列方法在拼响应时最常用。

| 方法 | 说明 |
|------|------|
| `getStatusCode(): int` | 状态码 |
| `getReasonPhrase(): string` | 原因短语 |
| `withStatus($code, $reasonPhrase = ''): static` | 新实例，指定状态 |
| `getProtocolVersion(): string` | 如 `1.1` |
| `withProtocolVersion($version): static` | 新实例 |
| `getHeaders(): array` | `[ name => string[] ]` |
| `hasHeader($name): bool` | 头名不区分大小写 |
| `getHeader($name): array` | 单头的值数组 |
| `getHeaderLine($name): string` | 逗号拼接 |
| `withHeader($name, $value): static` | 替换该头 |
| `withAddedHeader($name, $value): static` | 追加 |
| `withoutHeader($name): static` | 删除 |
| `getBody(): StreamInterface` | 响应体流 |
| `withBody(StreamInterface $body): static` | 替换正文 |

`send()` 输出头时使用 `getHeaders()` 的**原始键名**（工厂写入的是 `Content-Type`、`Location` 等）。

父类状态码必须在 100–599，否则 `InvalidArgumentException`。短语表覆盖常见 1xx–5xx（含 418、451 等）。

---

## 6. 组合示例

### JSON API

```php
use Fize\Web\Request;
use Fize\Web\Response;

if (!Request::isPost()) {
    Response::json(['errcode' => 405, 'errmsg' => 'method not allowed'])
        ->withStatus(405)
        ->send();
    return;
}

Response::json([
    'errcode' => 0,
    'data'    => ['id' => 1],
])->noCache()->send();
```

### HTML + 禁止缓存

```php
Response::html('<!DOCTYPE html><p>draft</p>', 'utf-8')
    ->noCache()
    ->send();
```

### 自定义头

```php
Response::json(['pong' => true])
    ->withHeader('X-Request-Id', 'abc-123')
    ->send();
```

### 直接 new

```php
use Fize\Web\Response;

$response = (new Response('plain text', 200, [
    'Content-Type' => ['text/plain; charset=utf-8'],
]))->withHeader('X-Powered-By', 'FizeWeb');

$response->send();
```

---

## 7. 注意点

1. **工厂方法默认 200**（`redirect` 除外，为 302）。错误码请显式 `withStatus`。
2. **`json(array)` 的编码行为**取决于 `Fize\Codec\Json::encode()`（Unicode、深度、错误处理以其版本为准），不是 `json_encode()` 的薄封装保证。
3. **没有统一的异常转响应**。未捕获异常仍走 PHP 默认错误页。
4. **下载大文件**时整文件进入 Stream 再 `getContents()`，等于读入再 echo，不适合超大文件或需要 `X-Sendfile` 的场景。
5. 单元测试 `tests/TestResponse.php` 目前只有空方法，没有断言示例。
