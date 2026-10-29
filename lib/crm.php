<?php
/**
 * Касса: точка сборки layout и секций.
 * Сами секции лежат в lib/crm/sections/.
 */

if (!defined('MOKKO')) {
    die;
}

if (isset($_SESSION['sid']) && $_SESSION['sid'] === 'uid') {
    $access = 'admin';
    $type_ac = 'Администратор';
} else {
    $access = 'barista';
    $type_ac = 'Бариста';
}

require __DIR__ . '/crm/layout.php';
require __DIR__ . '/crm/sections/dashboard.php';
require __DIR__ . '/crm/sections/guests.php';
require __DIR__ . '/crm/sections/check.php';
require __DIR__ . '/crm/footer.php';