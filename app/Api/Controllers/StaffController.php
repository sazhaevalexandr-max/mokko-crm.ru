<?php
declare(strict_types=1);

namespace Mokko\Api\Controllers;

use Mokko\Api\CrmSupport;
use Mokko\Api\PlanLimits;

/**
 * Сотрудники и настройки владельца.
 */
final class StaffController extends ApiController
{
    public function staff(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        switch ($this->action()) {
            case 'load':
                $stmt = $pdo->prepare('SELECT id, name, lastname, email AS login, phone, rate FROM staff WHERE uid = ? ORDER BY id');
                $stmt->execute(array($uid));
                \mokko_json($stmt->fetchAll(\PDO::FETCH_ASSOC));
                break;

            case 'new':
                $password = isset($p['pass']) ? (string)$p['pass'] : '';
                if (!\mokko_password_ok($password)) {
                    \mokko_json(array('error' => 'password must be 8-72 characters'));
                }

                $name = \mokko_str(isset($p['name']) ? $p['name'] : '', 100);
                $lastname = \mokko_str(isset($p['lastname']) ? $p['lastname'] : '', 100);
                $login = \mokko_str(isset($p['login']) ? $p['login'] : '', 60);
                $phone = \mokko_str(isset($p['phone']) ? $p['phone'] : '', 20);
                $rate = (float)(isset($p['rate']) ? $p['rate'] : 0);

                $loginError = CrmSupport::staffLoginError($pdo, $login);
                if ($loginError !== '') {
                    \mokko_json(array('error' => $loginError));
                }

                $countStmt = $pdo->prepare('SELECT COUNT(*) FROM staff WHERE uid = ?');
                $countStmt->execute(array($uid));
                if ((int)$countStmt->fetchColumn() >= PlanLimits::STAFF) {
                    \mokko_json(array('error' => 'Лимит тарифа: не более ' . PlanLimits::STAFF . ' сотрудников.'));
                }

                $stmt = $pdo->prepare('INSERT INTO staff (uid, name, lastname, email, phone, rate, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute(array($uid, $name, $lastname, $login, $phone, $rate, password_hash($password, PASSWORD_DEFAULT)));

                \mokko_json(array(
                    'Id' => (int)$pdo->lastInsertId(), 'name' => $name, 'lastname' => $lastname,
                    'login' => $login, 'phone' => $phone, 'rate' => $rate,
                ));
                break;

            case 'edit':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $name = \mokko_str(isset($p['name']) ? $p['name'] : '', 100);
                $lastname = \mokko_str(isset($p['lastname']) ? $p['lastname'] : '', 100);
                $login = \mokko_str(isset($p['login']) ? $p['login'] : '', 60);
                $phone = \mokko_str(isset($p['phone']) ? $p['phone'] : '', 20);
                $rate = (float)(isset($p['rate']) ? $p['rate'] : 0);

                $loginError = CrmSupport::staffLoginError($pdo, $login, $id);
                if ($loginError !== '') {
                    \mokko_json(array('error' => $loginError));
                }

                $stmt = $pdo->prepare('UPDATE staff SET name = ?, lastname = ?, email = ?, phone = ?, rate = ? WHERE id = ? AND uid = ?');
                $stmt->execute(array($name, $lastname, $login, $phone, $rate, $id, $uid));

                \mokko_json(array(
                    'Id' => $id, 'name' => $name, 'lastname' => $lastname,
                    'login' => $login, 'phone' => $phone, 'rate' => $rate,
                ));
                break;

            case 'del':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $pdo->prepare('DELETE FROM staff WHERE id = ? AND uid = ?')->execute(array($id, $uid));
                \mokko_json(array('Id' => $id));
                break;

            case 'newpass':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $password = isset($p['password']) ? (string)$p['password'] : '';
                if (!\mokko_password_ok($password)) {
                    \mokko_json(array('error' => 'password must be 8-72 characters'));
                }
                $pdo->prepare('UPDATE staff SET password_hash = ? WHERE id = ? AND uid = ?')
                    ->execute(array(password_hash($password, PASSWORD_DEFAULT), $id, $uid));
                \mokko_json(array('success' => true));
                break;

            case 'newpassword':
                \mokko_rate_limit_guard('pwchg_' . $uid, 5, 900);

                $old = isset($p['old_password']) ? (string)$p['old_password'] : '';
                $new = isset($p['password']) ? (string)$p['password'] : '';

                if ($this->ctx->staffId > 0) {
                    $stmt = $pdo->prepare('SELECT password_hash FROM staff WHERE id = ? AND uid = ? LIMIT 1');
                    $stmt->execute(array($this->ctx->staffId, $uid));
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

                    if (!$row || !password_verify($old, (string)$row['password_hash'])) {
                        \mokko_json(array('error' => 'wrong password'));
                    }
                    if (!\mokko_password_ok($new)) {
                        \mokko_json(array('error' => 'password must be 8-72 characters'));
                    }

                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    $pdo->prepare('UPDATE staff SET password_hash = ? WHERE id = ? AND uid = ?')
                        ->execute(array($newHash, $this->ctx->staffId, $uid));
                    $_SESSION['pv'] = \mokko_password_fingerprint($newHash);
                    \mokko_rate_limit_clear('pwchg_' . $uid);
                    \mokko_json(array('success' => true));
                }

                $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
                $stmt->execute(array($uid));
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$row || !password_verify($old, (string)$row['password_hash'])) {
                    \mokko_json(array('error' => 'wrong password'));
                }
                if (!\mokko_password_ok($new)) {
                    \mokko_json(array('error' => 'password must be 8-72 characters'));
                }

                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute(array($newHash, $uid));
                $_SESSION['pv'] = \mokko_password_fingerprint($newHash);
                \mokko_rate_limit_clear('pwchg_' . $uid);
                \mokko_json(array('success' => true));
                break;

            default:
                \mokko_json(array('error' => 'unknown action'));
        }
    }

    public function user(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        switch ($this->action()) {
            case 'unlock':
                \mokko_rate_limit_guard('pwchk_' . $uid, 10, 300);
                $password = isset($p['password']) ? (string)$p['password'] : '';
                $ok = ($this->ctx->sessionHash !== '' && password_verify($password, $this->ctx->sessionHash));

                \mokko_json(array('unlock' => $ok ? 'true' : 'false'));
                break;

            case 'newpassword':
                \mokko_rate_limit_guard('pwchg_' . $uid, 5, 900);
                $old = isset($p['old_password']) ? (string)$p['old_password'] : '';
                $new = isset($p['password']) ? (string)$p['password'] : '';

                $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
                $stmt->execute(array($uid));
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$row || !password_verify($old, (string)$row['password_hash'])) {
                    \mokko_json(array('error' => 'wrong password'));
                }
                if (!\mokko_password_ok($new)) {
                    \mokko_json(array('error' => 'password must be 8-72 characters'));
                }

                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute(array($newHash, $uid));
                $_SESSION['pv'] = \mokko_password_fingerprint($newHash);
                \mokko_rate_limit_clear('pwchg_' . $uid);
                \mokko_json(array('success' => true));
                break;

            case 'newphone':
                $phone = CrmSupport::phoneClean(\mokko_str(isset($p['phone']) ? $p['phone'] : '', 24));
                if ($phone !== '' && !preg_match('/^\+?\d{10,15}$/', $phone)) {
                    \mokko_json(array('error' => 'invalid phone'));
                }
                $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?')->execute(array($phone === '' ? null : $phone, $uid));
                \mokko_json(array('success' => true, 'phone' => $phone));
                break;

            case 'newname':
                $name = \mokko_str(isset($p['newname']) ? $p['newname'] : '', 100);
                $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute(array($name, $uid));
                \mokko_json(array('success' => true));
                break;

            default:
                \mokko_json(array('error' => 'unknown action'));
        }
    }
}
