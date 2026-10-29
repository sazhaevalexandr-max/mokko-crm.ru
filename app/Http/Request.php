<?php
declare(strict_types=1);

namespace Mokko\Http;

/**
 * Нормализованный HTTP-запрос.
 *
 * Сейчас роут берётся из ?view=..., чтобы не ломать существующие
 * rewrite-правила .htaccess. Позже сюда можно добавить разбор REQUEST_URI.
 */
final class Request
{
    /** @var string */
    private $route;

    /** @var array */
    private $query;

    /** @var string */
    private $method;

    public function __construct(string $route, array $query = array(), string $method = 'GET')
    {
        $this->route = $route;
        $this->query = $query;
        $this->method = strtoupper($method);
    }

    public static function fromGlobals(): self
    {
        $route = isset($_GET['view']) && is_string($_GET['view']) ? $_GET['view'] : '';
        $method = isset($_SERVER['REQUEST_METHOD']) ? (string)$_SERVER['REQUEST_METHOD'] : 'GET';
        $query = is_array($_GET) ? $_GET : array();

        return new self($route, $query, $method);
    }

    public static function create(string $route, array $query = array()): self
    {
        return new self($route, $query, 'GET');
    }

    public function route(): string
    {
        return $this->route;
    }

    /**
     * @return mixed
     */
    public function query(string $key, $default = null)
    {
        return array_key_exists($key, $this->query) ? $this->query[$key] : $default;
    }

    public function hasQuery(string $key): bool
    {
        return array_key_exists($key, $this->query);
    }

    public function method(): string
    {
        return $this->method;
    }
}
