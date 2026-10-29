<?php
declare(strict_types=1);

namespace Mokko\Support;

use InvalidArgumentException;
use Mokko\Http\Response;
use RuntimeException;
use Throwable;

/**
 * Рендер существующих шаблонов lib/*.php.
 *
 * Белый список сохраняет прежнюю защиту: имя шаблона не приходит из запроса
 * напрямую. Шаблоны пока лежат в lib/ — их перенос в app/Views будет отдельным
 * этапом, чтобы не ломать установщик и обновления.
 */
final class View
{
    /** @var array */
    private const ALLOWED = array(
        'subscribe', 'panel', 'login', 'signup',
        'password', 'crm', 'enter', 'select_place', 'main',
    );

    public static function render(string $view): string
    {
        if (!in_array($view, self::ALLOWED, true)) {
            throw new InvalidArgumentException('View is not allowed: ' . $view);
        }

        $template = dirname(__DIR__, 2) . '/lib/' . $view . '.php';
        if (!is_file($template)) {
            throw new RuntimeException('View not found: ' . $view);
        }

        ob_start();
        try {
            require $template;
            return (string)ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    public static function response(string $view, int $status = 200): Response
    {
        return Response::html(self::render($view), $status);
    }
}
