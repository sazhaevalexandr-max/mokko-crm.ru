<?php
declare(strict_types=1);

namespace Mokko\Api;

/**
 * Данные одного запроса к CRM API.
 *
 * Заменяет набор глобальных переменных ($pdo, $uid, $action, $p, $staffId...),
 * которые раньше были доступны всем обработчикам через global.
 */
final class ApiContext
{
    /** @var \PDO */
    public $pdo;

    /** @var string */
    public $module;

    /** @var string */
    public $action;

    /** @var array */
    public $input;

    /** @var int */
    public $uid;

    /** @var array */
    public $currentUser;

    /** @var bool */
    public $isAdmin;

    /** @var int */
    public $staffId;

    /** @var array|null */
    public $staffRow;

    /** @var string */
    public $sessionHash;

    public function __construct(
        \PDO $pdo,
        string $module,
        string $action,
        array $input,
        int $uid,
        array $currentUser,
        bool $isAdmin,
        int $staffId,
        ?array $staffRow,
        string $sessionHash
    ) {
        $this->pdo = $pdo;
        $this->module = $module;
        $this->action = $action;
        $this->input = $input;
        $this->uid = $uid;
        $this->currentUser = $currentUser;
        $this->isAdmin = $isAdmin;
        $this->staffId = $staffId;
        $this->staffRow = $staffRow;
        $this->sessionHash = $sessionHash;
    }
}
