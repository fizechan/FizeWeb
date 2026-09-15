# Session

在底层完成 `session_start()` 之后，对 `$_SESSION` 做 get / set / has / delete / clear。底层能力来自依赖包 `fize/session`。

- **类名**：`Fize\Web\Session`
- **源码**：[`src/Session.php`](../src/Session.php)
- **命名空间**：`Fize\Web`
- **依赖**：`Fize\Session\Session`（包 `fize/session`）
- **相关**：[Request](Request.md)（`Request::session()` 只读同一份 `$_SESSION`）· [Cookie](Cookie.md)

---

## 1. 概述

本类是**业务向门面**，不包装 `session_id()`、`session_regenerate_id()` 等。那些方法在 `Fize\Session\Session` 上。

```php
use Fize\Web\Session;

new Session();              // 启动底层 Session
Session::set('uid', 1);
Session::get('uid');        // 1
Session::has('uid');        // true
Session::delete('uid');
Session::clear();           // session_unset()
```

构造函数必须先执行：内部 `new \Fize\Session\Session($config)`。底层在状态为 `PHP_SESSION_NONE` 时调用 `session_start()`。未启动就 `get`/`set` 会碰到未定义的 `$_SESSION`。

`Request::session($key)` 读的是同一超全局变量，不会启动会话。

---

## 2. 初始化与配置

```php
public function __construct(array $config = [])
```

`$config` **原样**传给 `Fize\Session\Session`。`Fize\Web\Session` 自身不保存配置。

底层默认配置（摘自 `vendor/fize/session`，以已安装版本为准）：

| 键 | 默认 | 说明 |
|----|------|------|
| `cache_expire` | `null` | 非 null 时 `session_cache_expire()` |
| `cache_limiter` | `null` | 非 null 时 `session_cache_limiter()` |
| `module_name` | `null` | 非 null 时 `session_module_name()` |
| `name` | `null` | 非 null 时 `session_name()` |
| `register_shutdown` | `null` | 严格等于 `true` 时 `session_register_shutdown()` |
| `save_path` | `null` | 非 null 时 `session_save_path()` |
| `cookie_params` | `[]` | 意图调用 `session_set_cookie_params()`（见下方说明） |
| `save_handler` | 见下表 | 自定义存储 |
| `options` | `[]` | 传给 `session_start($options)` |

`save_handler` 默认：

```php
[
    'type'              => '',    // 空或 files：PHP 原生文件存储
    'config'            => [],
    'register_shutdown' => true,
]
```

`type` 非空且不等于 `files` 时，底层会实例化：

```text
Fize\Session\Handler\{Type}Handler
```

`fize/session` 自带的 Handler 包括：`File`、`Database`、`Memcache`、`Memcached`、`Redis`、`Mock`（类名形如 `RedisHandler`）。Database / Redis 等还需要对应扩展或 `fize/database`，详见 [FizeSession 手册](https://fizesession.readthedocs.io/zh_CN/latest/)。

```php
new Session([
    'name'      => 'APPSESSID',
    'save_path' => '/var/lib/php/sessions',
    'options'   => [
        'cookie_lifetime' => 86400,
        'cookie_httponly' => '1',
        'cookie_secure'   => '1',
    ],
]);
```

会话 Cookie 的生命周期、HttpOnly、Secure 更适合放在 `options`（即 `session_start` 的选项）或 php.ini 的 `session.cookie_*`。

关于 `cookie_params`：底层构造里使用的是未定义变量 `$cookie_params` 做 `empty()` 判断，而不是 `$config['cookie_params']`。因此**通过该键传入的数组当前不会生效**。不要依赖这一项。

底层只在 `session_status() === PHP_SESSION_NONE` 时 `start`。若应用或其它组件已经 `session_start()`，构造函数不会再次启动，但仍会先执行 name / save_path 等 setter——部分 setter 在会话已启动后会失败，需保证 **FizeWeb `Session` 最先初始化**。

---

## 3. 方法说明

### `get`

```php
public static function get(string $name, $default = null)
```

`$_SESSION[$name] ?? $default`。键不存在或值为 `null` 时都返回 `$default`（PHP 的 `??`）。

```php
Session::get('uid');           // 没有则 null
Session::get('role', 'guest');
```

没有“一次取出全部”的方法；需要整份数据时直接用 `$_SESSION` 或 `Request::session()`。

### `has`

```php
public static function has(string $name): bool
```

`isset($_SESSION[$name])`。值为 `null` 时 `isset` 为 false，故 `has` 为 false，与 `get` 落到默认值一致。

### `set`

```php
public static function set(string $name, $value)
```

`$_SESSION[$name] = $value`。`$value` 可为任意可序列化类型（数组、对象等取决于 Session 序列化器）。

无点号路径解析，不会把 `user.name` 写成嵌套数组，就是一个字面键。

### `delete`

```php
public static function delete(string $name)
```

`unset($_SESSION[$name])`。键不存在时无错误。

历史版本曾用 `remove`，2020-02 起统一为 `delete`，与 [Cookie](Cookie.md) 一致。

### `clear`

```php
public static function clear()
```

调用 `Fize\Session\Session::unset()`，即 `session_unset()`，去掉当前会话中的全部变量。

**不是** `session_destroy()`：会话 ID 与存储中的会话文件/记录通常仍在，只是 `$_SESSION` 被清空。要销毁会话需自行调用底层：

```php
\Fize\Session\Session::destroy();
```

---

## 4. 底层方法（按需）

`Fize\Web\Session` 未再导出下列方法。需要时直接使用 `Fize\Session\Session`（与本类构造时用的是同一个类）：

| 方法 | 对应 PHP |
|------|----------|
| `id()` | `session_id()` |
| `name($name = null)` | `session_name()` |
| `regenerateId($delete = false)` | `session_regenerate_id()` |
| `destroy()` | `session_destroy()` |
| `abort()` | `session_abort()` |
| `writeClose()` | `session_write_close()` |
| `encode()` / `decode($data)` | `session_encode()` / `session_decode()` |
| `status()` | `session_status()` |
| `gc()` | `session_gc()` |
| `createId($prefix = null)` | `session_create_id()` |
| `reset()` | `session_reset()` |
| `savePath($path = null)` | `session_save_path()` |
| `setCookieParams(...)` | `session_set_cookie_params()` |
| `setSaveHandler($handler, $register_shutdown = true)` | `session_set_save_handler()` |

登录成功后轮转会话 ID：

```php
use Fize\Web\Session;
use Fize\Session\Session as SessionEngine;

new Session();
SessionEngine::regenerateId(true);
Session::set('uid', $userId);
```

---

## 5. 示例

### 最小读写

```php
use Fize\Web\Session;

new Session();

Session::set('user', ['id' => 1, 'name' => 'Ada']);
$user = Session::get('user', []);

if (Session::has('user')) {
    Session::delete('flash');
}
```

### 与 Request 一起读

```php
use Fize\Web\Request;
use Fize\Web\Session;

new Session();

// 两处读的是同一数组
Session::get('uid');
Request::session('uid');
```

### Redis 存储（底层）

```php
new Session([
    'save_handler' => [
        'type'   => 'Redis',
        'config' => [
            // 字段以已安装的 Fize\Session\Handler\RedisHandler 为准
        ],
    ],
]);
```

Handler 的 `$config` 形状不属于 FizeWeb，请对照 `fize/session` 对应 Handler 源码。

### 退出登录

```php
Session::clear();
\Fize\Session\Session::destroy();
```

仅 `clear()` 时，下一次请求仍可能带着同一 Session ID 访问空数组。

---

## 6. 注意点

1. **先构造再静态调用**，并尽量在输出 HTML / 调用 `Response::send()` 之前完成，以免 Session Cookie 头发送失败。
2. **`clear()` ≠ `destroy()`**。
3. **`has` 使用 `isset`**，存了 `null` 会视为不存在。
4. 本类不处理并发锁、Flash 数据、CSRF。需要时在应用层实现。
5. `tests/TestSession.php` 只执行 `new Session()`，没有读写断言。
6. 更完整的处理器与 ini 项说明见 [FizeSession 参考手册](https://fizesession.readthedocs.io/zh_CN/latest/)。
