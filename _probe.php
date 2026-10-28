<?php
$p = new PDO('mysql:host=localhost;dbname=mokko;charset=utf8mb4', 'root', '');
foreach ($p->query('SELECT id,email,role FROM users') as $r) {
    echo $r['id'] . ' ' . $r['email'] . ' ' . $r['role'] . PHP_EOL;
}
echo 'PHP ' . PHP_VERSION . PHP_EOL;
echo 'zip=' . (class_exists('ZipArchive') ? 'yes' : 'no') . PHP_EOL;
echo 'exec=' . (function_exists('exec') ? 'yes' : 'no') . PHP_EOL;
echo 'proc_open=' . (function_exists('proc_open') ? 'yes' : 'no') . PHP_EOL;
echo 'sodium=' . (function_exists('sodium_crypto_sign_verify_detached') ? 'yes' : 'no') . PHP_EOL;
echo 'opcache=' . (function_exists('opcache_reset') ? 'yes' : 'no') . PHP_EOL;
