<?php
/**
 * Аварийный откат обновления из командной строки.
 *
 * Нужен, если после установки пакета сломался index.php или панель и зайти
 * в /updates невозможно. Работает напрямую с резервными копиями
 * storage/updates/backups/<token>/.
 *
 * Запуск:
 *   php tools/rollback.php --list
 *   php tools/rollback.php <token>            # вернуть файлы
 *   php tools/rollback.php <token> --db       # вернуть файлы и БД
 *   php tools/rollback.php <token> --force    # игнорировать ручные правки
 *
 * Запускайте от пользователя, которому принадлежат файлы проекта
 * (на Linux — владелец сайта или www-data), иначе не будет прав на запись.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/lib/updater.php';

$args = array_slice($argv, 1);
$token = '';
$list = false;
$db = false;
$force = false;

foreach ($args as $arg) {
    if ($arg === '--list') {
        $list = true;
    } elseif ($arg === '--db') {
        $db = true;
    } elseif ($arg === '--force') {
        $force = true;
    } elseif ($arg !== '' && $arg[0] !== '-') {
        $token = $arg;
    }
}

function rollback_print_state($token, $state)
{
    $status = isset($state['status']) ? $state['status'] : '?';
    $title = isset($state['title']) ? $state['title'] : $token;
    $version = isset($state['version']) ? $state['version'] : '?';
    $time = isset($state['applied_at']) ? $state['applied_at'] : (isset($state['created_at']) ? $state['created_at'] : '');
    $files = isset($state['actions']) && is_array($state['actions']) ? count($state['actions']) : 0;
    $dbMark = !empty($state['db_dump']) ? ' + БД' : '';

    echo $token . '  ' . $status . '  ' . $title . ' ' . $version
        . '  файлов: ' . $files . $dbMark . '  ' . $time . PHP_EOL;
}

if ($list || $token === '') {
    $dir = mokko_upd_backups_dir();
    $items = glob($dir . '/*/state.json');
    if (!is_array($items) || empty($items)) {
        echo "Резервных копий нет.\n";
        exit(0);
    }

    echo "Доступные откаты:\n";
    foreach ($items as $file) {
        $state = mokko_upd_read_json($file);
        if (is_array($state) && isset($state['token'])) {
            rollback_print_state($state['token'], $state);
        }
    }
    exit(0);
}

try {
    $result = mokko_upd_rollback($token, $db, $force);
    echo "Готово.\n";
    echo '  ' . (isset($result['message']) ? $result['message'] : '') . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Ошибка: ' . $e->getMessage() . "\n");
    exit(1);
}
