# Архитектура MOKKO CRM

Документ фиксирует границы слоёв после первого этапа рефакторинга.
Установщик и раздел обновлений намеренно оставлены самостоятельными.

## Точки входа

| Путь | Назначение | Зависит от `app/` |
|---|---|---|
| `index.php` | сайт и панель | да |
| `api/auth.php` | вход | нет |
| `api/crm.php` | JSON API CRM | нет |
| `api/updates.php` | API обновлений | нет |
| `setup/install.php` | установщик | нет |
| `lib/updates.php` | страница обновлений | нет |
| `tools/*.php` | CLI-сборка и откат | нет |

`index.php` — единственная точка входа для страниц. Он поднимает сессию,
заголовки, проверяет установку и режим обслуживания. Раздел `/updates`
обрабатывается отдельной веткой **до** подключения `app/autoload.php`, чтобы
модуль обновлений не зависел от нового слоя.

## Слои

```
app/
  Http/
    Request.php          — ?view=..., query, method
    Response.php         — html/redirect, отправка ответа
    Router.php           — таблица маршрутов + middleware
    Middleware/          — RequireAuth, RequireAdmin, RequireGuest
  Controllers/
    PageController.php   — сценарии страниц
    AuthController.php   — выход
  Security/
    Auth.php             — фасад над существующей сессией
  Repositories/
    PlaceRepository.php  — SQL принадлежности кофейни
  Api/
    ApiKernel.php        — bootstrap JSON API, роль, pv, права
    ApiRouter.php        — module => контроллер
    ApiContext.php       — $pdo, uid, action, input вместо global
    CrmSupport.php       — общие проверки (даты, телефон, склад, смены)
    Controllers/         — Init, Venue, Catalog, Staff, Shift, Stat
  Support/
    View.php             — рендер lib/*.php по белому списку
  autoload.php           — PSR-4 для Mokko\
```

Шаблоны:

```
lib/panel.php            — сборка панели
lib/panel/layout.php     — head, sidebar, header
lib/panel/sections/      — home, places, staff, options, lager, menu, feedback
lib/panel/footer.php     — скрипты, модалки, закрывающие теги

lib/crm.php              — сборка кассы
lib/crm/layout.php       — head, sidebar, header
lib/crm/sections/        — dashboard, guests, check
lib/crm/footer.php       — скрипты, лоадеры, закрывающие теги
```

Поток запроса:

```
index.php
  → bootstrap (security, session, install, maintenance, modules)
  → /updates? → отдельная ветка, exit
  → autoload
  → Router (config/routes.php)
  → middleware
  → Controller
  → View (lib/*.php) или Response::redirect
  → Response::send()
```

## Правила

- Контроллер не содержит SQL и не выводит HTML напрямую.
- Репозиторий не знает про HTTP.
- Шаблон не делает `require` с относительным путём — только `__DIR__`.
- Новый код — в namespace `Mokko\`, файл по PSR-4.
- `app/` и `tests/` закрыты от веба в `.htaccess`.
- `config/security.php`, `lib/modules.php`, `lib/updater.php`, `setup/`
  остаются совместимыми: их функции используют установщик и обновления.

## Фронтенд

- `js/api.js` — единый клиент CRM API (`MokkoApi.request`) и адаптер
  `api://module/action/{json}` для старых вызовов панели и кассы.
- Внешний `mokkoapi.azurewebsites.net` удалён из `panel.js`, `crm.js` и
  `interface.js`; все запросы идут на локальный `/api/crm.php` POST.
- `js/panel.js` и `js/crm.js` пока остаются крупными файлами: их полное
  разбиение на ES-модули требует перевода на strict mode и чистки неявных
  глобалов, поэтому вынесено в отдельный этап.

## Что дальше

1. `js/panel.js` — разбить на ES-модули после очистки неявных глобальных
   переменных; `js/crm.js` — то же.
2. `css/theme.css` — токены, компоненты, экраны; убрать дублирующий фреймворк.
3. `lib/updater.php` — сервисы `PackageValidator`, `Backup`, `Migrator`,
   `Installer`, `History` (отдельным этапом, не смешивая с `app/`).

## Проверки

```powershell
C:\xampp\php\php.exe tests\run.php
```

Сейчас тесты покрывают роутер, middleware, Request, белый список View и
общие проверки `CrmSupport` (12 проверок).
