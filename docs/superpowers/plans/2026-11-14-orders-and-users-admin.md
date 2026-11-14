# Orders, Mobile Checkout API & Users/Roles Admin Plan

> **For agentic workers:** Execute task-by-task using TDD (`php artisan test`) and Laravel Boost conventions (`AGENTS.md`, `vendor/bin/pint --dirty --format agent`).

**Goal:** Ensure complete mobile checkout + stock-safe transactions + order history endpoints alongside full Admin panel modules for Orders (`/pedidos`) and Users/Roles (`/usuarios`), update `docs/API.md`, run all tests, and leave `php artisan serve` running.

**Architecture:**
- Preserve all existing `/api/v1/*` endpoints and JSON shapes (`Api\OrderController`, `Api\CartController`).
- Extract shared order checkout & status-transition domain logic (including `DB::transaction`, `lockForUpdate()`, stock deduction, and stock restoration upon cancellation) into `App\Services\OrderService`.
- Expose `/api/cart*` and `/api/orders*` mobile endpoints (`Api\Mobile\CartController`, `Api\Mobile\OrderController`) using `App\Support\ApiResponse` alongside `/api/auth/*`, `/api/categories`, `/api/medicines`, and `/api/products`.
- Build `/pedidos`, `/pedidos/{order}`, and `/usuarios` MVC modules following `categorias.blade.php` / `medicamentos.blade.php` Tailwind v4 + Alpine.js patterns and `UserManagementService`.

---

## Task 1: Mobile Cart & Order API + Transactional `OrderService`

**Files:**
- Create: `app/Services/OrderService.php`
- Create: `app/Http/Controllers/Api/Mobile/CartController.php`
- Create: `app/Http/Controllers/Api/Mobile/OrderController.php`
- Create: `tests/Feature/Api/MobileCartAndOrderApiTest.php`
- Modify: `app/Models/Order.php`
- Modify: `app/Http/Controllers/Api/OrderController.php`
- Modify: `routes/api.php`

**Steps:**
1. Write failing feature tests in `tests/Feature/Api/MobileCartAndOrderApiTest.php` covering:
   - Adding/updating/removing items on `/api/cart` with `ApiResponse` format (`success: true`, `data`).
   - Creating order via `POST /api/orders` from cart and direct `items[]`, validating stock insufficiency returns `422` without partial stock deduction, and valid purchase decrements `medicines.stock` inside `DB::transaction` and clears cart.
   - Listing authenticated user's order history (`GET /api/orders`) and single order (`GET /api/orders/{order}`) including `status`, `status_label`, `subtotal`, `shipping_cost`, `total`, and `items`; preventing viewing another user's order (`403`/`404`).
2. Implement `Order::STATUS_LABELS`, `Order::normalizeStatus()`, `statusLabel()`, and `deliveryMethodLabel()`.
3. Implement `App\Services\OrderService` (`checkout`, `updateStatus`, `statusOptions`, `normalizeStatus`) with `DB::transaction` and `lockForUpdate()` ordered by medicine ID, restoring stock when cancelled and verifying stock when reopening from cancelled.
4. Wire `Api\Mobile\CartController`, `Api\Mobile\OrderController`, and add `status_label` cleanly in `Api\OrderController` without breaking `V1ApiTest`.
5. Run `php artisan test --compact tests/Feature/Api/MobileCartAndOrderApiTest.php tests/Feature/Api/V1ApiTest.php tests/Feature/Api/AuthApiTest.php tests/Feature/Api/CatalogApiTest.php`.

---

## Task 2: Admin Panel — Pedidos (`/pedidos` & `/pedidos/{order}`)

**Files:**
- Create: `app/Http/Controllers/OrderController.php`
- Create: `resources/views/modules/pedidos/index.blade.php`
- Create: `resources/views/modules/pedidos/show.blade.php`
- Modify: `routes/web.php`

**Steps:**
1. Write failing tests in `tests/Feature/OrderAndUserAdminTest.php` for:
   - `GET /pedidos` renders order list, KPIs, and filters accurately by status (`pendiente`, `en preparación`, `completado`, `cancelado`) and date (`fecha` / `fecha_desde` / `fecha_hasta`) and search query `q`.
   - `GET /pedidos/{order}` displays customer info, requested medicines, quantities, unit prices, subtotals, total, and status update control.
   - `PATCH /pedidos/{order}/estado` updates order status (`pending` -> `preparing` -> `completed` -> `cancelled`), restores medicine stock when cancelled, and persists `admin_note`.
2. Generate `OrderController` via `php artisan make:controller OrderController --no-interaction` and implement `index`, `show`, `updateStatus`.
3. Add `/pedidos`, `/pedidos/{order}`, and `/pedidos/{order}/estado` routes inside `['auth', 'admin']` middleware in `routes/web.php`.
4. Build `resources/views/modules/pedidos/index.blade.php` and `resources/views/modules/pedidos/show.blade.php` matching the `#095d52` corporate UI.
5. Run `php artisan test --compact tests/Feature/OrderAndUserAdminTest.php`.

---

## Task 3: Admin Panel — Usuarios y Roles (`/usuarios`)

**Files:**
- Create: `app/Http/Controllers/UserController.php`
- Create: `resources/views/modules/usuarios.blade.php`
- Modify: `routes/web.php`

**Steps:**
1. Add failing tests in `tests/Feature/OrderAndUserAdminTest.php` for:
   - `GET /usuarios` lists registered users, supports search `q`, role filter `rol`, and status filter `estado`.
   - `POST /usuarios` creates a user with assigned role (`administrador`, `farmaceutico`, `cliente`) and active status.
   - `PUT /usuarios/{user}` updates user profile/role/status.
   - `PATCH /usuarios/{user}/rol` assigns a new role (`UserManagementService::assignRole`).
   - `PATCH /usuarios/{user}/estado` toggles active/inactive status and invalidates Sanctum tokens when deactivated.
   - `DELETE /usuarios/{user}` deletes another user, while protecting the current admin from deleting/deactivating/demoting themselves.
2. Generate `UserController` via `php artisan make:controller UserController --no-interaction` backed by `UserManagementService`.
3. Add `/usuarios` routes in `routes/web.php` inside `['auth', 'admin']`.
4. Create `resources/views/modules/usuarios.blade.php` using `<x-modal>` and Alpine.js for create/edit modals + inline role and status forms.
5. Run `php artisan test --compact tests/Feature/OrderAndUserAdminTest.php`.

---

## Task 4: Navigation, Existing Tests Alignment, Build & Pint

**Files:**
- Modify: `app/Support/PharmacyMockData.php`
- Modify: `tests/Feature/MenuModulesTest.php`
- Modify: `tests/Feature/PreferenceModulesTest.php`
- Modify: `tests/Feature/DashboardPageTest.php`
- Modify: `tests/Unit/PharmacyMockDataTest.php`

**Steps:**
1. Add `Pedidos` and `Usuarios` to `PharmacyMockData::navigation()`.
2. Update removed-route assertions in `MenuModulesTest.php`, `PreferenceModulesTest.php`, `DashboardPageTest.php`, and `PharmacyMockDataTest.php` so `/pedidos` and `/usuarios` are verified active (and 9 nav items total), while `/compras`, `/proveedores`, `/configuracion`, `/notificaciones`, `/ayuda` remain 404.
3. Run `npm run build` to compile updated Tailwind utility classes into `public/build/`.
4. Run `vendor/bin/pint --dirty --format agent` on modified PHP files.
5. Run full `php artisan test --compact` suite and verify all tests pass cleanly.

---

## Task 5: API Documentation (`docs/API.md`) & Server Startup

**Files:**
- Modify: `docs/API.md`

**Steps:**
1. Run `php artisan route:list --path=api --json` to enumerate every live API endpoint.
2. Update `docs/API.md` with accurate Spanish documentation for both `/api/*` (Mobile REST) and `/api/v1/*` (Auth, Catalog, Cart, Orders, Admin Medicines), including auth headers, request schemas, response payloads, status values, and transactional stock behavior.
3. Verify database has seeded sample data (`php artisan db:seed --no-interaction` if safe / non-destructive or check counts) and start `php artisan serve --host=127.0.0.1 --port=8000` (`IsDaemon: true`).
4. Verify live HTTP responses on `http://127.0.0.1:8000/login` and `http://127.0.0.1:8000/api/medicines`.
