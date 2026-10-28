# MOKKO CRM — отчёт по безопасности и список исправлений

Дата: 2026-10-26
Объект: `C:\xampp\htdocs\mokko-crm.ru` (http://localhost/mokko-crm.ru/login)
Стек: PHP 7.3 (mod_php), MySQL/MariaDB, jQuery-фронтенд.

## Кратко

Найдено и закрыто 18 проблем, из них 4 критических. Главная —
**полный обход авторизации через GET-параметры** `?access=...&uid=...&sid=...`:
любой посетитель мог подставить чужой `uid` и получить доступ к панели
управления без пароля. Второй по важности — **IDOR в `api/places.php`**:
список кофеен любого пользователя отдавался без авторизации по `?uid=`.

После патча:

* вход выполняется только через `POST /api/auth.php?action=login`;
* сессия создаётся только сервером, `uid` берётся из сессии, а роль — из БД;
* все изменяющие запросы требуют `POST` + `Content-Type: application/json` +
  совпадения `Origin`/`Referer`/`Sec-Fetch-Site` (CSRF);
* ошибки БД и PHP больше не показываются посетителю;
* служебные каталоги (`config/`, `lib/`, `pages/`, `setup/`) закрыты от
  прямого HTTP-доступа, добавлены security-заголовки.

## Найденные уязвимости

| № | Критичность | Уязвимость | Где было | Что сделано |
|---|---|---|---|---|
| 1 | Критическая | Обход авторизации: `?access=ANY&uid=ANY&sid=uid` записывал произвольные значения в `$_SESSION` и пускал в `/panel` | `index.php` (строки 121–134) | Блок удалён полностью |
| 2 | Критическая | Обход авторизации через `POST key` на `/panel` | `index.php` (view=panel), `pages/panel.php` | Удалён, вход только после `api/auth.php` |
| 3 | Критическая | IDOR: `api/places.php?action=load&uid=N` отдавал чужие кофейни без авторизации | `api/places.php` | Требуется сессия, `uid` только из сессии |
| 4 | Критическая | SQL-инъекция + хардкод чужих БД-креденшелов (`kdesign`/пароль) в `subscriber.php`, использование удалённого `mysql_*` | `subscriber.php` | PDO + prepared statement, креды удалены, валидация телефона/e-mail, rate limit |
| 5 | Высокая | Session fixation: сессия не перегенерировалась при входе | `api/auth.php` | `session_regenerate_id(true)` + очистка `$_SESSION`, `use_strict_mode`, `SameSite=Lax`, `HttpOnly` |
| 6 | Высокая | Нет ограничения попыток входа (brute force) | `api/auth.php` | Лимит 10 попыток / 5 минут на IP |
| 7 | Высокая | User enumeration: разные ответы «invalid login» / «invalid password» | `api/auth.php` | Единый ответ `invalid`, dummy-bcrypt для выравнивания времени |
| 8 | Высокая | Раскрытие ошибок БД клиенту (`db error: ...`) | `api/auth.php`, `api/crm.php`, `api/places.php` | Ошибки только в лог, клиенту — `server` |
| 9 | Высокая | Нет разграничения ролей: бариста мог вызывать админские модули (`places/new`, `staff/del`, `lager`, `stat`, `user/newpassword`…) | `api/crm.php` | Матрица прав: бариста — только `ini/guests/startshift/shift/buy`, `places/get`, `staff/newpassword`, `category/load`, `items/load` |
| 10 | Высокая | CSRF на всех API (сессионная cookie без SameSite, запросы без проверки источника) | `api/*.php`, `subscriber.php` | POST-only + `application/json` + проверка `Origin`/`Referer`/`Sec-Fetch-Site` + `SameSite=Lax` |
| 11 | Высокая | Подделка суммы чека: `total` приходил с клиента и писался в БД как есть | `api/crm.php` (`buy/check`) | Сумма пересчитывается по ценам `items` из БД |
| 12 | Высокая | Открытый генератор писем: `GET /?do=repeir` вызывал `mail()` на фиксированный адрес без авторизации, с неинициализированными переменными | `index.php`, `mail_get.php` | Триггер удалён, `mail_get.php` отдаёт 404 |
| 13 | Высокая | Публичный установщик: `setup/install.php` создавал БД и админа `admin@mokko.local` / `admin123` | `setup/install.php` | Только loopback, отказ при наличии пользователей, случайный пароль |
| 14 | Средняя | Reflected XSS: `?mail=` выводился без экранирования; введённый пароль отправлялся на `http://mokko.azurewebsites.net` | `pages/password.php` | Экранирование, внешний вызов удалён, страница переведена в информационный режим |
| 15 | Средняя | XSS в JS-контексте: значения сессии печатались в `<script>` без экранирования | `lib/crm.php`, `lib/panel.php`, `lib/enter.php`, `lib/select_place.php` | `json_encode` с `JSON_HEX_*` (хелпер `mokko_js()`) |
| 16 | Средняя | `addslashes()` в «очистке» ввода + `htmlspecialchars()` при записи (двойное кодирование, ложное чувство защиты) | `index.php`, `pages/panel.php` | `clean()` = trim + удаление управляющих символов; для SQL — только prepared statements |
| 17 | Средняя | Нет security-заголовков, включён `display_errors`, листинг каталогов | `.htaccess`, все точки входа | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, CSP (`frame-ancestors`, `base-uri`, `form-action`), `Options -Indexes`, `display_errors=Off` |
| 18 | Низкая | Прямой доступ к служебным/резервным файлам: `config/db.php`, `lib/login_app.php`, `index.html-`, `pages/*.php` | корень, `lib/`, `config/`, `pages/`, `setup/` | `.htaccess` с `Require all denied` / `Require local` |

Дополнительно: удалён сторонний `<script src="http://gc.kis.v2.scr.kaspersky-labs.com/...">`
из `pages/soon.php` (загрузка чужого JS по HTTP — потенциальный MITM/бэкдор),
пароль при регистрации теперь не короче 8 символов, а ответ `register`
не подтверждает существование e-mail.

## Изменённые файлы

Новые:

* `config/security.php` — сессия, заголовки, JSON, CSRF, rate limit, экранирование;
* `config/.htaccess`, `lib/.htaccess`, `pages/.htaccess`, `setup/.htaccess`;
* `lib/password.php` — безопасная страница восстановления пароля.

Переписаны:

* `index.php`, `api/auth.php`, `api/places.php`, `api/crm.php`, `config/db.php`,
  `subscriber.php`, `mail_get.php`, `setup/install.php`,
  `pages/login.php`, `pages/panel.php`, `pages/password.php`.

Точечно исправлены:

* `.htaccess`, `lib/crm.php`, `lib/panel.php`, `lib/enter.php`,
  `lib/select_place.php`, `lib/signup.php`, `pages/soon.php`, `js/login.js`.

В БД добавлена таблица `subscribers` (раньше код писал в чужую базу
`uborka-crm`, которой на этом сервере нет).

## Как проверить

```powershell
# 1. Обход авторизации закрыт: должно быть 302 на /login, панель не отдаётся
curl.exe -i "http://localhost/mokko-crm.ru/?access=x&uid=1&sid=uid"
curl.exe -i "http://localhost/mokko-crm.ru/panel"

# 2. IDOR закрыт: без сессии — ошибка
curl.exe "http://localhost/mokko-crm.ru/api/places.php?action=load&uid=1"

# 3. Вход работает
curl.exe -i -X POST "http://localhost/mokko-crm.ru/api/auth.php?action=login" `
  -H "Content-Type: application/json" -H "Origin: http://localhost" `
  -d '{\"login\":\"admin@mokko.local\",\"password\":\"admin123\"}'

# 4. CSRF с чужого origin — 403
curl.exe -i -X POST "http://localhost/mokko-crm.ru/api/crm.php?module=ini&action=get" `
  -H "Content-Type: application/json" -H "Origin: http://evil.example" -d '{}'

# 5. Служебное закрыто
curl.exe -i "http://localhost/mokko-crm.ru/config/db.php"
curl.exe -i "http://localhost/mokko-crm.ru/lib/panel.php"
```

## Что осталось (рекомендации)

1. **Пароль БД.** `config/db.php` по умолчанию использует `root` с пустым
   паролем (как в XAMPP). Для продакшена создайте отдельного пользователя
   с правами только на `mokko` и задайте `MOKKO_DB_USER` / `MOKKO_DB_PASS`
   (или впишите значения в `config/db.php`).
2. **HTTPS.** Cookie помечается `Secure` автоматически при HTTPS. На
   продакшене включите HTTPS и HSTS (`Header always set Strict-Transport-Security ...`).
3. **Восстановление пароля.** Полноценный сброс (токены в БД + письмо)
   сейчас отключён — страница просит написать в поддержку. Если нужен
   автоматический сброс, его надо делать через одноразовые хешированные
   токены с TTL, а не через `mail_get.php`.
4. **Логин бариста.** `api/auth.php` ищет пользователя только в `users`,
   поэтому сотрудники из таблицы `staff` войти не могут (это было и до
   патча). При добавлении такого входа пароль нужно проверять по
   `staff.password_hash`, а `sid` выставлять `staff`.
5. **XSS на клиенте.** Часть данных (`place.name`, имена гостей) вставляется
   в DOM через `.append('<option>'+name+'</option>')`. Это self-XSS внутри
   одного аккаунта, но правильнее использовать `.text()` / экранирование.
6. **Тестовый админ.** В БД остались `admin@mokko.local` / `admin123` и
   `Dnse22@ya.ru` — смените пароли и удалите демо-аккаунт.
7. **Резервные копии.** Оригиналы файлов до патча лежат в
   `C:\Users\HP\Documents\deepseek-harness\default-workspace\mokko-backup-20261026`.
