<?php
/**
 * JSON API CRM — тонкая точка входа.
 *
 * Вся авторизация, права и маршрутизация — в app/Api/.
 */

require_once dirname(__DIR__) . '/app/autoload.php';

(new \Mokko\Api\ApiKernel())->run();
