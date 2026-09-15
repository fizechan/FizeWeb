# Request

请求环境只读封装。把 PHP 超全局变量、`php://input` 和若干常见判断收成静态方法。

- **类名**：`Fize\Web\Request`
- **源码**：[`src/Request.php`](../src/Request.php)
- **命名空间**：`Fize\Web`
- **依赖**：仅 PHP 标准能力；若存在 `apache_request_headers()` 则优先用它读请求头
- **相关**：[Cookie](Cookie.md) · [Session](Session.md) · [Response](Response.md)

---

## 1. 概述

`Request` 不创建 HTTP 客户端，也不实现 PSR-7 `ServerRequestInterface`。它始终读当前 SAPI 请求：

- 查询串、表单、上传、Cookie、Session、环境变量、`$_SERVER`
- 原始请求体、Content-Type、HTTP 方法
- 域名、URL、客户端 IP
- GET/POST/Ajax/Pjax/SSL/JSON/移动端等布尔判断

除构造函数外全部是 `public static`。可以不实例化直接调用；只有要改配置时才需要 `new Request($config)`。

同一 PHP 进程内，`method()`、`header()`、`input()` 的结果会缓存在静态属性上。CLI 单测或长驻进程若连续模拟多次请求，需要自行重置这些静态属性（当前类未提供重置方法）。

---

## 2. 初始化与配置

```php
public function __construct(array $config = [])
```

传入非空数组时，与默认配置 `array_merge`。空数组或未构造则一直使用默认值。

| 配置键 | 默认值 | 作用 |
|--------|--------|------|
| `var_method` | `_method` | POST 体里伪装 HTTP 方法的字段名，见 [`method()`](#method) |
| `var_ajax` | `_ajax` | 请求参数中伪装 Ajax 的字段名，见 [`isAjax()`](#isajax) |
| `var_pjax` | `_pjax` | 请求参数中伪装 Pjax 的字段名，见 [`isPjax()`](#ispjax) |
| `https_agent_name` | `''` | 自定义 HTTPS 代理头对应的 `$_SERVER` 键名，见 [`isSsl()`](#isssl) |
| `accept_type` | `''` | 额外视为“接受类型”的字符串；包含 `json` 时 [`isJson()`](#isjson) 恒为真 |

```php
use Fize\Web\Request;

new Request([
    'var_method'       => '_method',
    'https_agent_name' => 'HTTP_X_FORWARDED_SSL',
    'accept_type'      => 'application/json',
]);
```

配置存在静态属性 `Request` 内部，进程内全局生效。

---

## 3. 方法一览

| 方法 | 作用 | 默认值参数 |
|------|------|------------|
| `server($key, $default)` | `$_SERVER` | 有 |
| `get($key, $default)` | `$_GET` | 有 |
| `post($key, $default)` | `$_POST` | 有 |
| `files($key)` | `$_FILES` | 缺键返回 `null` |
| `request($key)` | `$_REQUEST` | 缺键返回 `null` |
| `session($key, $default)` | `$_SESSION` | 有 |
| `env($key, $default)` | `$_ENV` | 有 |
| `cookie($key, $default)` | `$_COOKIE`（不加前缀） | 有 |
| `input()` | `php://input`（缓存） | — |
| `header($key, $default)` | 请求头 | 有 |
| `contentType()` | `CONTENT_TYPE` 主类型 | — |
| `method()` | 实际/伪装后的 HTTP 方法 | — |
| `domain($protocol)` | 当前 Host，可选协议 | — |
| `url($host, $protocol)` | `REQUEST_URI`，可选域名 | — |
| `ip()` | 客户端 IP | — |
| `isGet()` / `isPost()` / `isPut()` / `isDelete()` / `isHead()` / `isPatch()` / `isOptions()` | 方法判断 | — |
| `isCli()` / `isCgi()` | SAPI 判断 | — |
| `isSsl()` | 是否 HTTPS | — |
| `isJson()` | Content-Type 或配置是否含 `json` | — |
| `isAjax($var_ajax)` | Ajax | — |
| `isPjax()` | Pjax | — |
| `isMobile()` | User-Agent 粗判移动端 | — |

未传 `$key`（即为 `null`）时，带超全局变量的方法返回**整个数组**。

---

## 4. 超全局变量

### `server`

```php
public static function server(string $key = null, string $default = null)
```

| 参数 | 说明 |
|------|------|
| `$key` | `$_SERVER` 键；`null` 返回整个 `$_SERVER` |
| `$default` | 键不存在时的返回值 |

```php
Request::server('REQUEST_METHOD');      // 如 "GET"
Request::server('NO_SUCH', 'fallback');
```

### `get` / `post`

```php
public static function get(string $key = null, string $default = null)
public static function post(string $key = null, string $default = null)
```

分别读 `$_GET`、`$_POST`。不存在的键返回 `$default`。

```php
$id = Request::get('id', '0');
$title = Request::post('title', '');
```

`method()` 若从 POST 伪装字段识别出方法，会 **`unset` 掉该 POST 字段**，之后 `post(var_method)` 不再能读到它。

### `files`

```php
public static function files(string $key = null)
```

读 `$_FILES`。指定键且不存在时返回 `null`（没有 `$default`）。返回值是 PHP 上传数组，本类不做校验或移动。

### `request`

```php
public static function request(string $key = null)
```

读 `$_REQUEST`。缺键返回 `null`。`variables_order` / `request_order` 决定 GET、POST、Cookie 的合并顺序，本类不改 php.ini。

### `session`

```php
public static function session(string $key = null, string $default = null)
```

直接读 `$_SESSION`。会话未启动时访问会触发 PHP 提示。需要本库管理会话时，先 `new \Fize\Web\Session()`，见 [Session](Session.md)。

这是只读视图；写入请用 `Session::set()`。

### `env`

```php
public static function env(string $key = null, string $default = null)
```

读 `$_ENV`。许多 SAPI 默认不填充 `$_ENV`，更常见的做法是 `server('某个键')` 或 `getenv()`。

### `cookie`

```php
public static function cookie(string $key = null, string $default = null)
```

原样读 `$_COOKIE`，**不会**加 [Cookie](Cookie.md) 的 `prefix`。若 Cookie 以 `app_token` 落地，应写：

```php
Request::cookie('app_token');
// 或
Cookie::get('token'); // 配置 prefix = 'app_'
```

---

## 5. 请求体与头

### `input`

```php
public static function input(): string
```

第一次调用时 `file_get_contents('php://input')` 并缓存到静态属性。后续调用返回同一份字符串。

注释写“失败时返回 false”，但返回类型声明为 `string`。读取失败时在 PHP 7+ 可能触发 `TypeError`。

适用于 `application/json`、`application/xml` 等非 `multipart/form-data` 正文。`multipart` 下 `php://input` 通常不可用。

```php
$raw = Request::input();
$data = json_decode($raw, true);
```

静态属性 `$body` 在源码中已声明但未使用。

### `header`

```php
public static function header(string $key = null, $default = null)
```

首次调用时组装并缓存全部请求头：

1. 若存在 `apache_request_headers()` 且返回非空，使用其结果。
2. 否则遍历 `$_SERVER`：`HTTP_*` 去掉前缀、下划线改连字符并转小写；另外并入 `CONTENT_TYPE`、`CONTENT_LENGTH`。
3. `array_change_key_case()` 把键统一成**小写**。

`$key === null` 时返回整份小写键名数组。

按键读取时，实现会先 `strtoupper($key)` 再去小写键数组里查找，因此 **`header('Content-Type')`、`header('content-type')` 通常都得不到值**，只会落到 `$default`。需要单个头时：

```php
$headers = Request::header();           // 小写键，如 content-type
$type = $headers['content-type'] ?? '';

// 或走 SERVER
Request::server('CONTENT_TYPE');
Request::server('HTTP_X_REQUESTED_WITH');
```

`composer.json` 的 `suggest.ext-apache` 即为此方法服务。

### `contentType`

```php
public static function contentType(): string
```

取 `$_SERVER['CONTENT_TYPE']`，若含 `;` 则只留第一部分并 `trim`。没有该键返回空字符串。

```php
// Content-Type: application/json; charset=utf-8
Request::contentType(); // "application/json"
```

---

## 6. 方法、URL、IP

### `method`

```php
public static function method(): string
```

按以下优先级决定，结果转大写后缓存：

1. `$_POST[$config['var_method']]` 存在，且值为 `GET` / `POST` / `PUT` / `PATCH` / `DELETE` 之一 → 用该值，并 `unset` 该 POST 字段；其它值则视为 `POST`。
2. 否则看 `HTTP_X_HTTP_METHOD_OVERRIDE`。
3. 否则 `REQUEST_METHOD`，再缺省为 `GET`。

HTML 表单只能发 GET/POST 时，可用隐藏域伪装 REST 方法：

```html
<input type="hidden" name="_method" value="PUT">
```

```php
Request::method();   // "PUT"
Request::isPut();    // true
```

### `domain`

```php
public static function domain(bool $protocol = true): string
```

主机名来自 `$_SERVER['HTTP_HOST']`。`$protocol === true` 时：

- `HTTPS` 非空且不等于 `off`，或 `SERVER_PORT == 443` → 前缀 `https://`
- 否则 `http://`

`$protocol === false` 时只返回 Host（可能含端口）。

反向代理终止 TLS 时，`HTTPS` / `SERVER_PORT` 可能仍是 HTTP，域名协议会不准；此时应同时参考 [`isSsl()`](#isssl)。

### `url`

```php
public static function url(bool $host = true, bool $protocol = true): string
```

基础是 `$_SERVER['REQUEST_URI']`（路径 + 查询串）。`$host === true` 时前面拼 `domain($protocol)`。

```php
Request::url();            // https://example.com/path?x=1
Request::url(true, false); // example.com/path?x=1
Request::url(false);       // /path?x=1
```

### `ip`

```php
public static function ip(): string
```

按顺序取第一个**通过 `FILTER_VALIDATE_IP`** 的地址：

1. `HTTP_CF_CONNECTING_IP`（Cloudflare）
2. `HTTP_X_FORWARDED_FOR`（只取逗号分隔的第一段，并 `trim`）
3. `HTTP_CLIENT_IP`
4. `REMOTE_ADDR`

都无效则返回字符串 `"unknown"`。

这些头可被客户端伪造。若应用不在受信任代理之后，应以 `REMOTE_ADDR` 为准，不要单独信任本方法做鉴权或限流。

---

## 7. 请求类型判断

### HTTP 方法

```php
public static function isGet(): bool
public static function isPost(): bool
public static function isPut(): bool
public static function isDelete(): bool
public static function isHead(): bool
public static function isPatch(): bool
public static function isOptions(): bool
```

全部基于已缓存的 `method()`。`isDelete` 的源码注释误写为 “DELTE”，行为仍是判断 `DELETE`。

`HEAD` / `OPTIONS` 不能靠 POST 伪装字段得到（伪装白名单不含它们），只能来自真实 `REQUEST_METHOD` 或 `X-HTTP-Method-Override`。

### `isCli` / `isCgi`

```php
public static function isCli(): bool  // PHP_SAPI === 'cli'
public static function isCgi(): bool  // PHP_SAPI 以 "cgi" 开头
```

### `isSsl`

```php
public static function isSsl(): bool
```

任一成立即为真：

| 条件 |
|------|
| `HTTPS` 为 `'1'` 或（不区分大小写）`'on'` |
| `REQUEST_SCHEME === 'https'` |
| `SERVER_PORT === '443'`（字符串比较） |
| `HTTP_X_FORWARDED_PROTO === 'https'` |
| 配置了非空 `https_agent_name`，且对应 `$_SERVER` 键为真 |

`HTTP_X_FORWARDED_PROTO` 同样可伪造，仅应在可信代理后使用。

### `isJson`

```php
public static function isJson(): bool
```

`contentType()` 包含子串 `json`，**或**配置 `accept_type` 包含 `json`。

注意：它看的是 **Content-Type**（及配置），不是 `Accept`。把 `accept_type` 设成含 `json` 的字符串会让该方法始终为 `true`。

### `isAjax`

```php
public static function isAjax(string $var_ajax = null): bool
```

1. `HTTP_X_REQUESTED_WITH` 存在且小写等于 `xmlhttprequest` → 视为 Ajax。
2. 若 `$_REQUEST` 中存在 `$var_ajax`（省略则用配置 `var_ajax`，默认 `_ajax`），**无论其值**，结果强制为 `true`。

```php
Request::isAjax();
Request::isAjax('_custom_ajax');
```

### `isPjax`

```php
public static function isPjax(): bool
```

`HTTP_X_PJAX` 存在（值任意），或 `$_REQUEST[var_pjax]` 为真。

### `isMobile`

```php
public static function isMobile(): bool
```

启发式检测，任一命中即真：

- `HTTP_VIA` 包含 `wap`（`stristr`）
- `HTTP_ACCEPT` 大写后包含 `VND.WAP.WML`
- 存在 `HTTP_X_WAP_PROFILE` 或 `HTTP_PROFILE`
- `HTTP_USER_AGENT` 匹配内置正则（含 android、iphone、iemobile、opera mini 等）

平板、桌面 Chrome 模拟器、新品牌 UA 都可能误判。不能替代响应式设计或客户端声明。

---

## 8. 静态状态与测试

| 静态属性 | 写入时机 | 影响 |
|----------|----------|------|
| `$config` | 构造 | 方法伪装、Ajax/Pjax、SSL、JSON |
| `$method` | 首次 `method()` | 所有 `isGet` 等 |
| `$header` | 首次 `header()` | 之后改 `$_SERVER` 不会反映到 `header()` |
| `$input` | 首次 `input()` | `php://input` 只能读一次，缓存是必要的 |
| `$body` | — | 未使用 |

PHPUnit 在同一进程跑多个用例时，后例会看到前例的 `$method` / `$header` / `$input`。当前类没有 `reset()`。

---

## 9. 完整示例

```php
use Fize\Web\Request;

new Request([
    'var_method' => '_method',
    'var_ajax'   => '_ajax',
]);

if (Request::isCli()) {
    fwrite(STDERR, "This endpoint is HTTP only\n");
    exit(1);
}

$page = (int) Request::get('page', 1);
$token = Request::server('HTTP_AUTHORIZATION', '');

if (Request::isJson()) {
    $payload = json_decode(Request::input(), true) ?: [];
} else {
    $payload = Request::post();
}

$meta = [
    'method' => Request::method(),
    'url'    => Request::url(),
    'ip'     => Request::ip(),
    'ssl'    => Request::isSsl(),
    'ajax'   => Request::isAjax(),
    'mobile' => Request::isMobile(),
];
```

与响应配合见 [Response](Response.md)。
