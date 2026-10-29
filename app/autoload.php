<?php
/**
 * Минимальный автозагрузчик PSR-4 для пространства Mokko\.
 *
 * Composer пока не подключён: проект должен работать на обычном XAMPP.
 * Позже этот файл заменяется на vendor/autoload.php без изменения классов.
 */

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Mokko\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
