<?php
declare(strict_types=1);

namespace Mokko\Repositories;

/**
 * Доступ к кофейням. Первый репозиторий как пример: SQL уходит из контроллера.
 */
final class PlaceRepository
{
    public function belongsToUser(int $placeId, int $userId): bool
    {
        if ($placeId <= 0 || $userId <= 0) {
            return false;
        }

        require_once dirname(__DIR__, 2) . '/config/db.php';

        $stmt = getDB()->prepare('SELECT id FROM places WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute(array($placeId, $userId));

        return (bool)$stmt->fetchColumn();
    }
}
