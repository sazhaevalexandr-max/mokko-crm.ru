<?php
declare(strict_types=1);

namespace Mokko\Api;

/**
 * Общие проверки и хелперы CRM API.
 */
final class CrmSupport
{
    public static function shiftOwnerSql(int $staffId): string
    {
        return $staffId > 0 ? 'staff_id = ' . $staffId : 'staff_id IS NULL';
    }

    /**
     * Колонка shifts.staff_id добавляется при первом обращении.
     * Пока не заменяем это миграцией, чтобы не менять схему в этом этапе.
     */
    public static function shiftsReady(\PDO $pdo): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        try {
            $pdo->query('SELECT staff_id FROM shifts LIMIT 0');
        } catch (\Exception $e) {
            try {
                $pdo->exec('ALTER TABLE shifts ADD COLUMN staff_id INT NULL');
            } catch (\Exception $e2) {
                \mokko_log('shifts: add staff_id failed: ' . $e2->getMessage());
            }
        }

        $ready = true;
    }

    public static function placeOwned(\PDO $pdo, int $uid, int $place): bool
    {
        if ($place <= 0) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT id FROM places WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute(array($place, $uid));

        return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public static function date($value, string $fallback): string
    {
        $value = (string)$value;
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        if ($date && $date->format('Y-m-d') === $value) {
            return $value;
        }

        return $fallback;
    }

    public static function requisitesError(string $inn, string $kpp, string $phone): string
    {
        if ($inn !== '' && !preg_match('/^(\d{10}|\d{12})$/', $inn)) {
            return 'invalid inn';
        }
        if ($kpp !== '' && !preg_match('/^\d{9}$/', $kpp)) {
            return 'invalid kpp';
        }
        if ($phone !== '' && !preg_match('/^\+?\d{10,15}$/', $phone)) {
            return 'invalid phone';
        }

        return '';
    }

    public static function phoneClean($phone): string
    {
        $phone = trim((string)$phone);
        $plus = (strpos($phone, '+') === 0) ? '+' : '';

        return $plus . preg_replace('/\D+/', '', $phone);
    }

    public static function staffLoginError(\PDO $pdo, string $login, int $excludeId = 0): string
    {
        if ($login === '' || mb_strlen($login, 'UTF-8') > 60 || preg_match('/\s/u', $login)) {
            return 'invalid login';
        }

        $u = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $u->execute(array($login));
        if ($u->fetch(\PDO::FETCH_ASSOC)) {
            return 'login already used';
        }

        $s = $pdo->prepare('SELECT id FROM staff WHERE email = ? AND id <> ? LIMIT 1');
        $s->execute(array($login, $excludeId));
        if ($s->fetch(\PDO::FETCH_ASSOC)) {
            return 'login already used';
        }

        return '';
    }
}
