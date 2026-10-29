<?php
declare(strict_types=1);

namespace Mokko\Api\Controllers;

/**
 * ini — настройки текущего аккаунта для интерфейса.
 */
final class InitController extends ApiController
{
    public function ini(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();

        $stmt = $pdo->prepare('SELECT name, lastname, email, status, wallet, phone FROM users WHERE id = ? LIMIT 1');
        $stmt->execute(array($uid));
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            \mokko_json(array('error' => 'user not found'));
        }

        if ($this->ctx->staffRow) {
            $user['name'] = $this->ctx->staffRow['name'];
            $user['lastname'] = $this->ctx->staffRow['lastname'];
            $user['email'] = $this->ctx->staffRow['email'];
            $user['phone'] = '';
            $user['wallet'] = 0;
        }

        \mokko_json(array('settings' => array(
            'name'     => $user['name'],
            'lastname' => $user['lastname'],
            'login'    => $user['email'],
            'currency' => 'RUB',
            'status'   => $user['status'],
            'wallet'   => $user['wallet'],
            'phone'    => $user['phone'],
        )));
    }
}
