<?php

namespace Ekok\Web;

class Fw implements \ArrayAccess
{
    const HTTP_100 = "Continue";
    const HTTP_101 = "Switching Protocols";
    const HTTP_103 = "Early Hints";
    const HTTP_200 = "OK";
    const HTTP_201 = "Created";
    const HTTP_202 = "Accepted";
    const HTTP_203 = "Non-Authoritative Information";
    const HTTP_204 = "No Content";
    const HTTP_205 = "Reset Content";
    const HTTP_206 = "Partial Content";
    const HTTP_300 = "Multiple Choices";
    const HTTP_301 = "Moved Permanently";
    const HTTP_302 = "Found";
    const HTTP_303 = "See Other";
    const HTTP_304 = "Not Modified";
    const HTTP_307 = "Temporary Redirect";
    const HTTP_308 = "Permanent Redirect";
    const HTTP_400 = "Bad Request";
    const HTTP_401 = "Unauthorized";
    const HTTP_402 = "Payment Required";
    const HTTP_403 = "Forbidden";
    const HTTP_404 = "Not Found";
    const HTTP_405 = "Method Not Allowed";
    const HTTP_406 = "Not Acceptable";
    const HTTP_407 = "Proxy Authentication Required";
    const HTTP_408 = "Request Timeout";
    const HTTP_409 = "Conflict";
    const HTTP_410 = "Gone";
    const HTTP_411 = "Length Required";
    const HTTP_412 = "Precondition Failed";
    const HTTP_413 = "Payload Too Large";
    const HTTP_414 = "URI Too Long";
    const HTTP_415 = "Unsupported Media Type";
    const HTTP_416 = "Range Not Satisfiable";
    const HTTP_417 = "Expectation Failed";
    const HTTP_418 = "I'm a teapot";
    const HTTP_422 = "Unprocessable Entity";
    const HTTP_425 = "Too Early";
    const HTTP_426 = "Upgrade Required";
    const HTTP_428 = "Precondition Required";
    const HTTP_429 = "Too Many Requests";
    const HTTP_431 = "Request Header Fields Too Large";
    const HTTP_451 = "Unavailable For Legal Reasons";
    const HTTP_500 = "Internal Server Error";
    const HTTP_501 = "Not Implemented";
    const HTTP_502 = "Bad Gateway";
    const HTTP_503 = "Service Unavailable";
    const HTTP_504 = "Gateway Timeout";
    const HTTP_505 = "HTTP Version Not Supported";
    const HTTP_506 = "Variant Also Negotiates";
    const HTTP_507 = "Insufficient Storage";
    const HTTP_508 = "Loop Detected";
    const HTTP_510 = "Not Extended";
    const HTTP_511 = "Network Authentication Required";

    public static $mimes = array(
        'any' => '*/*',
        'html' => 'text/html',
        'json' => 'application/json',
    );

    protected $values = array();
    protected $keys = array();
    protected $routes = array();
    protected $aliases = array();

    public function __construct(
        array $post = null,
        array $get = null,
        array $files = null,
        array $cookie = null,
        array $server = null,
        array $env = null,
        $body = null
    ) {
        $headers = $server ? array_reduce(preg_grep('/^HTTP_/', array_keys($server)), function (array $headers, string $key) use ($server) {
            $headers[ucwords(strtr(strtolower(substr($key, 5)), '_', '-'), '-')] = $server[$key];

            return $headers;
        }, array()) : array();
        $method = $server['REQUEST_METHOD'] ?? 'GET';
        $contentMime = $headers['Content-Type'] ?? $server['CONTENT_TYPE'] ?? '*/*';
        $contentType = self::$mimes[$contentMime] ?? 'any';
        $protocol = $server['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $port = intval($server['SERVER_PORT'] ?? 80);
        $host = strstr(($headers['Host'] ?? $server['SERVER_NAME'] ?? 'localhost') . ':', ':', true);
        $secure = 'on' === ($server['HTTPS'] ?? $headers['X-Forwarded-Ssl'] ?? null) || 'https' === ($headers['X-Forwarded-Proto'] ?? null);
        $scheme = $secure ? 'https' : 'http';
        $baseUrl = $scheme . '://' . $host . ':' . $port;
        $basePath = '/';
        $entry = '';
        $sessionStarted = isset($session) && $session;
        $ajax = 'XMLHttpRequest' === ($headers['X-Requested-With'] ?? null);
        $ip = $headers['Client-Ip'] ?? $headers['X-Forwarded-For'] ?? $headers['X-Forwarded'] ?? $headers['X-Cluster-Client-Ip'] ?? $headers['Forwarded-For'] ?? $headers['Forwarded'] ?? $server['REMOTE_ADDR'] ?? '';

        if (false === strpos($ip, ',')) {
            $ip = filter_var($ip, FILTER_VALIDATE_IP);
        } else {
            $ip = array_reduce(explode(',', $ip), static function($found, $ip) {
                return $found ?: filter_var($ip, FILTER_VALIDATE_IP);
            });
        }

        if (in_array($port, array(80, 443))) {
            $baseUrl = substr($baseUrl, 0, strrpos($baseUrl, ':'));
        }

        if (isset($server['SCRIPT_NAME'])) {
            $basePath = strtr(dirname($server['SCRIPT_NAME']), '\\', '/');
            $entry = basename($server['SCRIPT_NAME']);
        }

        if (isset($server['PATH_INFO'])) {
            $path = $server['PATH_INFO'];
        } elseif ($entry && isset($server['REQUEST_URI'])) {
            $uri = strstr($server['REQUEST_URI'] . '?', '?', true);

            if (false === $pos = strpos($uri, $entry)) {
                $path = $uri;
            } else {
                $path = (substr($uri, $pos + strlen($entry))) ?: '/';
            }

            if ('/' !== $path && $basePath && 0 === strpos($path, $basePath)) {
                $path = (substr($path, strlen($basePath))) ?: '/';
            }
        } else {
            $path = '/';
        }

        $entryScript = !empty($entry);

        $this->values = array(
            'AJAX' => $ajax,
            'ALIAS' => null,
            'BASE_PATH' => $basePath,
            'BASE_URL' => $baseUrl,
            'CASELESS' => true,
            'CONTENT_MIME' => $contentMime,
            'CONTENT_TYPE' => $contentType,
            'CONTENT' => $body,
            'COOKIE' => $cookie,
            'ENTRY_SCRIPT' => $entryScript,
            'ENTRY' => $entry,
            'ENV' => $env,
            'FILES' => $files,
            'GET' => $get,
            'HEADER' => $headers,
            'HOST' => $host,
            'IP' => $ip,
            'METHOD_OVERRIDE' => false,
            'METHOD' => $method,
            'PARAMS' => null,
            'PATH' => $path,
            'PATTERN' => null,
            'PORT' => $port,
            'POST' => $post,
            'PROTOCOL' => $protocol,
            'SCHEME' => $scheme,
            'SECURE' => $secure,
            'SERVER' => $server,
            'SESSION_KEY' => 'web',
            'SESSION_STARTED' => $sessionStarted,
            'SESSION' => null,
        );
        $this->keys = array_fill_keys(array_keys($this->values), true);
    }

    public static function fromGlobals(): Fw
    {
        return new static($_POST, $_GET, $_FILES, $_COOKIE, $_SERVER, $_ENV);
    }

    public function offsetExists($offset)
    {
        $this->doRef($offset);

        return isset($this->keys[$offset]);
    }

    public function &offsetGet($offset)
    {
        $this->doRef($offset);

        if (!isset($this->keys[$offset])) {
            $this->offsetSet($offset, null);
        }

        return $this->values[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->doRef($offset);

        $this->values[$offset] = $value;
        $this->keys[$offset] = true;

        $this->doRef($offset, 'set');
    }

    public function offsetUnset($offset)
    {
        $this->doRef($offset);

        unset($this->values[$offset], $this->keys[$offset]);

        $this->doRef($offset, 'unset');
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function route(string $definition, $controller, array $options = null): Fw
    {
        if (!preg_match('/^(?:\h*)?([\w|]+)(?:\h+([\w+.]+))?\h+([^\h]+)(?:\h*)$/', $definition, $matches)) {
            throw new \LogicException("Invalid route: '{$definition}'.");
        }

        $methods = explode('|', strtoupper($matches[1]));
        $alias = $matches[2] ?? null;
        $path = $matches[3];

        $this->routes[$path][] = compact('methods', 'controller', 'options', 'alias');

        if ($alias) {
            $this->aliases[$alias] = $path;
        }

        return $this;
    }

    public function findRoute(string $path = null, string $method = null): ?array
    {
        $findPath = $path ?? $this->values['PATH'] ?? '/';
        $findMethod = $method ?? $this->values['METHOD'] ?? 'GET';
        $matchedRoutes = $this->findMatchedRoutes($findPath);
        $satisfied = array_filter($matchedRoutes, function(array $route) use ($findMethod) {
            $check = $route['options']['check'] ?? null;

            return (in_array($findMethod, $route['methods']) || in_array('ANY', $route['methods'])) && (!$check || $check($this));
        });

        usort($satisfied, static function(array $a, array $b) {
            return intval($b['options']['priority'] ?? 0) <=> intval($a['options']['priority'] ?? 0);
        });

        return $satisfied ? reset($satisfied) : null;
    }

    public function findMatchedRoutes(string $findPath): array
    {
        if (isset($this->routes[$findPath])) {
            return array_map(static function(array $route) use ($findPath) {
                return $route + array('path' => $findPath, 'parameters' => array());
            }, $this->routes[$findPath]);
        }

        foreach ($this->routes as $path => $routes) {
            if ($found = $this->findMatchedRoutesForPath($path, $routes, $findPath)) {
                return $found;
            }
        }

        return array();
    }

    public function routeMatch(string $findPath, string $path, array $requirements = null): ?array
    {
        $wild = $path;

        if (false !== strpos($path, '@')) {
            $wild = preg_replace_callback('~(?:@([\w]+)([*])?)~', static function($match) use ($requirements) {
                $name = $match[1];
                $modifier = $match[2] ?? null;
                $pattern = $requirements[$name] ?? ('*' === $modifier ? '.*' : '[^/]+');

                return "(?<{$name}>{$pattern})";
            }, $path);
        }

        $modifier = ($this->values['CASELESS'] ?? false) ? 'i' : null;
        $pattern = "~^{$wild}$~{$modifier}";

        if (preg_match($pattern, $findPath, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    protected function findMatchedRoutesForPath(string $path, array $routes, string $findPath): array
    {
        $found = array();

        foreach ($routes as $route) {
            if (null !== $parameters = $this->routeMatch($findPath, $path, $route['options']['requirements'] ?? null)) {
                $found[] = $route + compact('path', 'parameters');
            }
        }

        return $found;
    }

    protected function doRef($offset, ...$arguments): void
    {
        if (is_string($offset) && ctype_alpha($offset) && method_exists($this, $method = '_ref'.$offset)) {
            $this->$method(...$arguments);
        }
    }

    protected function _refSession(string $action = null): void
    {
        if ('set' === $action) {
            $_SESSION[$this->values['SESSION_KEY']] = $this->values['SESSION'];

            session_regenerate_id();
        } elseif ('unset' === $action) {
            $this->values['SESSION'] = null;

            unset($_SESSION[$this->values['SESSION_KEY']]);
        } elseif (!$this->values['SESSION_STARTED'] && PHP_SESSION_NONE === session_status()) {
            session_start();

            $this->values['SESSION_STARTED'] = true;
            $this->values['SESSION'] = &$_SESSION[$this->values['SESSION_KEY']];
        }
    }
}
