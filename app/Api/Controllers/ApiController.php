<?php
declare(strict_types=1);

namespace Mokko\Api\Controllers;

use Mokko\Api\ApiContext;

abstract class ApiController
{
    /** @var ApiContext */
    protected $ctx;

    public function __construct(ApiContext $ctx)
    {
        $this->ctx = $ctx;
    }

    protected function pdo(): \PDO
    {
        return $this->ctx->pdo;
    }

    protected function uid(): int
    {
        return $this->ctx->uid;
    }

    protected function action(): string
    {
        return $this->ctx->action;
    }

    protected function input(): array
    {
        return $this->ctx->input;
    }
}
