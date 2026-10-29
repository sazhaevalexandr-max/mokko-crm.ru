<?php
/**
 * Единый копирайт MOKKO CRM.
 * Одна точка правки для всех страниц: панель, касса и регистрация.
 */
if (!defined('MOKKO')) { die; }

if (!function_exists('mokko_copyright')) {
    function mokko_copyright() {
        return '© ' . date('Y') . ' MOKKO CRM &middot; '
            . '<a href="https://t.me/mokkocrm" target="_blank" rel="noopener noreferrer">Телеграм-канал</a>';
    }
}
