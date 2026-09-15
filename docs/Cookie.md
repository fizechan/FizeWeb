# Cookie

对 `setcookie()` / `$_COOKIE` 的静态封装，支持全局默认过期时间、路径、域名、Secure、HttpOnly 和键名前缀。

- **类名**：`Fize\Web\Cookie`
- **源码**：[`src/Cookie.php`](../src/Cookie.php)
- **命名空间**：`Fize\Web`
- **依赖**：PHP `setcookie()`，不经过 `fize/http`
- **相关**：[Request](Request.md)（`Request::cookie()` 不加前缀）· [Session](Session.md)

---

## 1. 概述

使用前必须先构造一次，把配置写入静态属性 `$config`。之后全程用静态方法：

```php
use Fize\Web\Cookie;

new Cookie([
    'expire'   => 3600,
    'path'     => '/',
    'prefix'   => 'fz_',
    'httponly' => true,
]);

Cookie::set('token', 'abc');
Cookie::get('token');    // "abc"
Cookie::has('token');    // true
Cookie::delete('token');
Cookie::clear();
```

`set` / `delete` / `clear` 会同时改浏览器 Cookie（`Set-Cookie` 头）和**当前请求**的 `$_COOKIE`，因此同一次请求里立刻 `get()` 能看到写入或删除结果。

当前实现**没有**键名/键值加密，也没有篡改回调。`demo/` 与 `tests/TestCookie.php` 里的 `encrypt_key`、`encrypt_value`、`secret_key`、`onTamper` 是旧版残留，构造时传入会被 `array_merge` 进配置，但 `set`/`get` **不会读取这些键**。

---

## 2. 初始化与配置

```php
public function __construct(array $config = [])
```

默认配置与传入数组合并。未构造就调用静态方法时，`self::$config` 为 `null`，`array_merge` 会报错。

| 键 | 类型 | 默认 | 说明 |
|----|------|------|------|
| `expire` | int | `3600` | 有效时长（秒），从 `time()` 起算，不是 Unix 时间戳本身 |
| `path` | string | `/` | Cookie 路径 |
| `domain` | string | `''` | 有效域名；空字符串表示由浏览器按当前主机处理 |
| `secure` | bool | `false` | 为 true 时仅 HTTPS 下发送 |
| `httponly` | bool | `true` | 禁止 JavaScript 读取。前端需要读 Cookie 时，设置该项为 `false`，或在单次 `set` 里覆盖 |
| `prefix` | string | `''` | 写入/读取/删除时拼在键名前 |

构造函数文档特别说明：全局开启 HttpOnly 后，浏览器 JS 无法读取；需要 JS 访问时在该条 Cookie 上关闭 HttpOnly。

```php
new Cookie([
    'expire'   => 86400 * 7,
    'path'     => '/',
    'domain'   => '.example.com',
    'secure'   => true,
    'httponly' => true,
    'prefix'   => 'app_',
]);
```

同一进程里再次 `new Cookie($config)` 会**整表替换**静态配置（仍先与默认值合并，不是与上一次用户配置合并）。

---

## 3. 方法一览

| 方法 | 说明 |
|------|------|
| `__construct(array $config = [])` | 写入静态配置 |
| `set($key, $value, $config = [])` | 设置一条 |
| `get($key, array $config = [])` | 读取；不存在返回 `false` |
| `has($key, array $config = [])` | `get() !== false` |
| `delete($key, array $config = [])` | 删除一条 |
| `clear()` | 清空当前 `$_COOKIE` 中能遍历到的全部键 |

`set` / `get` / `has` / `delete` 的 `$config` 会与静态配置合并，用于覆盖 `expire`、`prefix` 等。**读取时真正用到的主要是 `prefix`**（必须与写入时一致才能对上键名）。

---

## 4. 方法说明

### `set`

```php
public static function set(string $key, string $value, $config = [])
```

| 参数 | 说明 |
|------|------|
| `$key` | 逻辑键名（不含前缀） |
| `$value` | 键值，类型为 `string` |
| `$config` | **int**：只覆盖 `expire`（秒）；**array**：与全局配置合并 |

实际键名：`{prefix}{key}`。调用：

```php
setcookie(
    $key,
    $value,
    time() + $config['expire'],
    $config['path'],
    $config['domain'],
    $config['secure'],
    $config['httponly']
);
$_COOKIE[$key] = $value;
```

过期时间是 **当前时间 + expire 秒**。`expire = 0` 表示立即过期（`time()+0`），不是“浏览器会话 Cookie”。需要会话级 Cookie 时，当前 API 没有单独开关，只能把 `expire` 设得很短或直接使用原生 `setcookie`。

```php
Cookie::set('uid', '42');
Cookie::set('flag', '1', 60);                    // 60 秒
Cookie::set('js_ok', '1', ['httponly' => false]);
Cookie::set('tmp', 'x', ['prefix' => 'x_', 'expire' => 300]);
```

键和值都是 `string`。数组/对象需自行序列化后再写入。

### `get`

```php
public static function get(string $key, array $config = [])
```

合并配置后拼前缀，读 `$_COOKIE`。未设置返回 **`false`**（不是 `null`）。

文档写返回类型 `@return string`，实际在缺失时返回 `false`，判断时不要当纯字符串。

```php
$val = Cookie::get('uid');
if ($val === false) {
    // 未设置
}
```

附加 `$config` 必须能还原写入时的 `prefix`：

```php
Cookie::set('a', '1', ['prefix' => 'p_']);
Cookie::get('a');                  // false（用的是全局 prefix）
Cookie::get('a', ['prefix' => 'p_']); // "1"
```

### `has`

```php
public static function has(string $key, array $config = []): bool
```

`get($key, $config) !== false`。值为空字符串 `''` 时仍为 `true`。

### `delete`

```php
public static function delete(string $key, array $config = [])
```

拼前缀后：

```php
setcookie($key, '', -3600);
unset($_COOKIE[$key]);
```

**只传了名称和过期的过去时间戳**，没有把 `path` / `domain` / `secure` / `httponly` 传给 `setcookie()`。浏览器匹配 Cookie 时路径、域名必须一致，用默认 `path=/` 写下的 Cookie，删除时若 PHP 默认 path 不是 `/`，浏览器可能删不掉。当前请求内的 `$_COOKIE` 仍会被 `unset`。

需要可靠删除时，应保证删除与设置使用相同的 path/domain，必要时用原生 `setcookie` 补齐参数。

`has()` / `get()` 的 `$config` 仍用于计算前缀，以便对上键名。

### `clear`

```php
public static function clear()
```

遍历**当前** `$_COOKIE` 的每一个键：

```php
setcookie($key, '', -3600);
unset($_COOKIE[$key]);
```

这里的 `$key` 已是超全局变量里的真实键（含前缀），不会再拼一次 `prefix`。与 `delete()` 一样不传 path/domain。

它会处理本请求能见到的**全部** Cookie，包括其它组件、分析脚本写入的键，而不是仅限本类 `prefix`。同域下其它 Cookie 也会被尝试过期。

---

## 5. 与 `Request::cookie()` 的关系

| | `Cookie::get('token')` | `Request::cookie('token')` |
|--|------------------------|----------------------------|
| 前缀 | 自动加配置 `prefix` | 不加 |
| 未设置 | `false` | `$default`（默认 `null`） |
| 写入 | 本类 `set` | 只读 |

`prefix` 为 `fz_` 时：

```php
Cookie::set('token', 'abc');     // 实际键 fz_token
Cookie::get('token');            // "abc"
Request::cookie('token');        // null
Request::cookie('fz_token');     // "abc"
```

---

## 6. 示例

### 登录态

```php
new Cookie([
    'expire'   => 86400,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'prefix'   => 'app_',
]);

Cookie::set('sid', $sessionId);

if (!Cookie::has('sid')) {
    header('Location: /login');
    exit;
}
```

### 单条覆盖 HttpOnly

```php
Cookie::set('theme', 'dark', [
    'httponly' => false,
    'expire'   => 86400 * 30,
]);
```

### 删除与清空

```php
Cookie::delete('sid');
Cookie::clear();
```

---

## 7. 注意点

1. **必须先 `new Cookie`**，即使全部使用默认值也要 `new Cookie()`。
2. **`get` 用 `false` 表示不存在**，与 `Request::cookie` 的 `null` 不同。
3. **`delete` / `clear` 的 `setcookie` 参数不完整**，跨 path/domain 时可能只清了当前请求的 `$_COOKIE`，浏览器里仍在。
4. **`clear()` 作用域是整份 `$_COOKIE`**，不是“本类写过的键”。
5. 未设置 `session.cookie_*` 时，本类与 PHP Session 的 Cookie 互相独立；Session 的会话 Cookie 由 [Session](Session.md) / `fize/session` 管理。
6. `tests/TestCookie.php` 依赖 Windows `start cmd` 启动 `php -S localhost:8123`，并请求已不存在的 `cookie_onTamper.php`；不能当作当前 API 的完整说明。
