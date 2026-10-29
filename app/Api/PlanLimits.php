<?php
declare(strict_types=1);

namespace Mokko\Api;

/**
 * Лимиты тарифа — единый источник для сервера и интерфейса.
 */
final class PlanLimits
{
    public const PLACES = 2;
    public const CATEGORIES = 5;
    public const ITEMS = 40;
    public const STAFF = 4;

    /**
     * @return array{places:int,categories:int,items:int,staff:int}
     */
    public static function all(): array
    {
        return array(
            'places' => self::PLACES,
            'categories' => self::CATEGORIES,
            'items' => self::ITEMS,
            'staff' => self::STAFF,
        );
    }
}
