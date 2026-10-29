<?php
declare(strict_types=1);

namespace Mokko\Http;

/**
 * Простой HTTP-ответ. Контроллеры возвращают Response, а index.php
 * отправляет его — вместо header()/exit в каждом обработчике.
 */
final class Response
{
    /** @var string */
    private $body;

    /** @var int */
    private $status;

    /** @var array */
    private $headers;

    public function __construct(string $body = '', int $status = 200, array $headers = array())
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, array(
            'Content-Type' => 'text/html; charset=utf-8',
        ));
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, array(
            'Location' => $url,
        ));
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        echo $this->body;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function status(): int
    {
        return $this->status;
    }
}
