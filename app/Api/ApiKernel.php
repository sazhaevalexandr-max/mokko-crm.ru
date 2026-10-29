<?php
declare(strict_types=1);

namespace Mokko\Api;

/**
 * Bootstrap JSON API CRM.
 *
 * Здесь остаются только аутентификация, роль, проверка pv и выбор контроллера.
 * Бизнес-логика — в app/Api/Controllers, проверки — в CrmSupport.
 */
final class ApiKernel
{
    /** @var array */
    private const STAFF_PERMISSIONS = array(
        'ini'        => '*',
        'guests'     => '*',
        'startshift' => '*',
        'shift'      => '*',
        'buy'        => '*',
        'places'     => array('get'),
        'staff'      => array('newpassword'),
        'category'   => array('load'),
        'items'      => array('load'),
        'user'       => array('unlock'),
    );

    public function run(): void
    {
        $context = $this->boot();
        $this->authorize($context);

        try {
            (new ApiRouter())->dispatch($context);
        } catch (\Throwable $e) {
            \mokko_log('crm: unhandled: ' . $e->getMessage());
            \mokko_json(array('error' => 'server'), 500);
        }
    }

    private function boot(): ApiContext
    {
        require_once dirname(__DIR__, 2) . '/config/security.php';
        \mokko_harden_errors();
        \mokko_session_start();
        \mokko_send_security_headers();

        if (\mokko_in_maintenance()) {
            header('Retry-After: 120');
            \mokko_json(array('error' => 'maintenance'), 503);
        }

        require_once dirname(__DIR__, 2) . '/config/db.php';

        $uid = \mokko_require_auth();

        \mokko_require_post();
        \mokko_require_same_origin();
        \mokko_require_json();

        $module = isset($_GET['module']) ? (string)$_GET['module'] : '';
        $action = isset($_GET['action']) ? (string)$_GET['action'] : '';
        $input = \mokko_json_input();

        try {
            $pdo = \getDB();
        } catch (\Exception $e) {
            \mokko_log('crm: db connect error: ' . $e->getMessage());
            \mokko_json(array('error' => 'server'));
        }

        $stmt = $pdo->prepare('SELECT id, role, status, password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute(array($uid));
        $currentUser = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$currentUser) {
            $_SESSION = array();
            \mokko_json(array('error' => 'invalid token'));
        }

        $isAdmin = ($currentUser['role'] === 'admin');

        $staffId = isset($_SESSION['staff_id']) ? (int)$_SESSION['staff_id'] : 0;
        $staffRow = null;

        if ($staffId > 0) {
            $stmt = $pdo->prepare('SELECT id, name, lastname, email, password_hash FROM staff WHERE id = ? AND uid = ? LIMIT 1');
            $stmt->execute(array($staffId, $uid));
            $staffRow = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$staffRow) {
                $_SESSION = array();
                \mokko_json(array('error' => 'invalid token'));
            }

            $isAdmin = false;
        }

        $sessionHash = $staffRow ? (string)$staffRow['password_hash'] : (string)$currentUser['password_hash'];
        if (empty($_SESSION['pv']) || !hash_equals(\mokko_password_fingerprint($sessionHash), (string)$_SESSION['pv'])) {
            $_SESSION = array();
            \mokko_json(array('error' => 'invalid token'), 401);
        }

        return new ApiContext(
            $pdo,
            $module,
            $action,
            is_array($input) ? $input : array(),
            (int)$uid,
            $currentUser,
            (bool)$isAdmin,
            $staffId,
            $staffRow ?: null,
            $sessionHash
        );
    }

    private function authorize(ApiContext $context): void
    {
        if ($context->isAdmin) {
            return;
        }

        if (!isset(self::STAFF_PERMISSIONS[$context->module])) {
            \mokko_json(array('error' => 'access denied'), 403);
        }

        $allowed = self::STAFF_PERMISSIONS[$context->module];
        if ($allowed !== '*' && !in_array($context->action, $allowed, true)) {
            \mokko_json(array('error' => 'access denied'), 403);
        }
    }
}
