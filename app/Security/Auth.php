<?php
declare(strict_types=1);

namespace Mokko\Security;

/**
 * Фасад над существующей сессией MOKKO.
 *
 * Пока не переписываем config/security.php: установщик и раздел обновлений
 * зависят от его функций. Класс даёт новый код без дублирования проверок.
 */
final class Auth
{
    public static function check(): bool
    {
        return function_exists('mokko_is_authenticated') && mokko_is_authenticated();
    }

    public static function id(): int
    {
        return isset($_SESSION['uid']) ? (int)$_SESSION['uid'] : 0;
    }

    public static function isAdmin(): bool
    {
        return function_exists('mokko_is_admin_session') && mokko_is_admin_session();
    }

    public static function isStaff(): bool
    {
        return self::check() && !self::isAdmin();
    }

    public static function logout(): void
    {
        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                isset($params['path']) ? $params['path'] : '/',
                isset($params['domain']) ? $params['domain'] : '',
                !empty($params['secure']),
                !empty($params['httponly'])
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
