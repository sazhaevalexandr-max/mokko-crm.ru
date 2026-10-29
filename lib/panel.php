<?php
/**
 * Панель управления: точка сборки layout и секций.
 * Сами секции лежат в lib/panel/sections/.
 */

if (!defined('MOKKO')) {
    die;
}

require_once dirname(__DIR__) . '/lib/modules.php';

if (is_file(dirname(__DIR__) . '/config/version.php')) {
    require_once dirname(__DIR__) . '/config/version.php';
}
if (!defined('MOKKO_VERSION')) {
    define('MOKKO_VERSION', '1.0.0');
}

mokko_modules_boot();

require __DIR__ . '/panel/layout.php';
require __DIR__ . '/panel/sections/home.php';
require __DIR__ . '/panel/sections/places.php';
require __DIR__ . '/panel/sections/staff.php';
require __DIR__ . '/panel/sections/options.php';
require __DIR__ . '/panel/sections/lager.php';
require __DIR__ . '/panel/sections/menu.php';
require __DIR__ . '/panel/sections/feedback.php';

mokko_hook('panel.layers');

require __DIR__ . '/panel/footer.php';