# Panel Administrativo LifeMedic — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear el proyecto Laravel desde cero y maquetar en 100% español el Dashboard principal idéntico a la imagen de referencia junto con sus 12 módulos de pantallas laterales y `Plan Pro`, con interactividad UI local lista para conectar lógica real en la fase siguiente.

**Architecture:** Aplicación Laravel 12 con Blade, Tailwind CSS v4 compilado vía Vite, Alpine.js local vía npm y gráficas SVG/JS construidas a medida para igualar exactamente las visualizaciones de la referencia. Todos los datos maquetados residen en `App\Support\PharmacyMockData` e inyectan arreglos estructurados mediante un controlador dedicado por módulo, permitiendo sustituir mocks por consultas Eloquent sin reescribir las vistas Blade.

**Tech Stack:** PHP 8.4, Laravel 12, Tailwind CSS v4, Alpine.js 3, Vite, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-12-lifemedic-admin-design.md`

## Global Constraints

- Idioma de toda la UI: 100% español (menú, encabezados, meses `Ene`–`Dic`, leyendas, tablas, botones, modales y mensajes toast).
- Cero persistencia real, migraciones de negocio ni autenticación en esta fase; datos simulados en `App\Support\PharmacyMockData` alineados al dominio de `Funcionalidades - LifeMedic (1).md`.
- En desktop (`lg+`), `Panel` (`/`) abre directo con la grilla bento sin barra superior; la barra lateral inicia directa con el grupo `MENÚ` (sin cabecera voluminosa de logotipo) y todas las secciones (`MENÚ`, `OTROS`, `PREFERENCIAS`) arrancan expandidas.
- Las 11 pantallas secundarias + `Plan Pro` usan `x-page-header` manteniendo la paleta exacta (`#F7F8FA` lienzo, `#FAFAFC` sidebar, `#FFFFFF` tarjetas `rounded-2xl`, `#165DFF` azul, `#F97316` naranja, `#22C55E` verde, `#A3E635` lima, `#8B5CF6` violeta).
- `Lista de Compras` cumple `Pendiente = Monto a Pagar - Monto Pagado` en todas sus filas y totales.
- `Pedidos` (`/pedidos`) representa órdenes emitidas por clientes desde la app móvil con los estados `pendiente`, `confirmado`, `entregado`, `cancelado`.
- Sin CDN externos críticos para JS/CSS: Tailwind CSS v4 y Alpine.js se compilan con Vite (`npm run build`), usando solo SVG inline para iconografía y gráficas.

## Review Focus

- **Fuga de textos en inglés:** Que no queden etiquetas de la imagen original (`Graph Report Today`, `Top Selling Medicine`, `Over All Revenue`, `Today's Report`, `Purchase List`, `Upgrade Pro`, `Out of Stock`, `View All`) ni meses en inglés; cubierto por aserciones en `tests/Feature/DashboardPageTest.php` y `tests/Feature/PreferenceModulesTest.php`.
- **Inconsistencia aritmética en `Lista de Compras` y `/compras`:** Que todas las filas y tarjetas resumen de compras cuadren exactamente (`Pendiente = Monto a Pagar - Monto Pagado`); cubierto por `tests/Unit/PharmacyMockDataTest.php`.
- **Enlaces rotos en barra lateral, `Ver Todo` o `Actualizar ahora`:** Que cada URL renderizada en la navegación lateral y tarjetas del panel apunte a una ruta GET registrada con HTTP 200; cubierto por `tests/Feature/LayoutShellTest.php`.
- **Desbordamiento o navegación inaccesible en móvil (`375px`):** Que exista botón hamburguesa de mínimo `44px×44px`, drawer lateral táctil y `overflow-x-auto` en tablas; cubierto por `tests/Feature/LayoutShellTest.php` y auditoría visual en navegador en Task 7.
- **Estados vacíos al filtrar/buscar en módulos interactivos:** Que cada tabla/catálogo filtrable con Alpine muestre un estado vacío claro en español sin romper el layout; cubierto por `tests/Feature/MenuModulesTest.php` y `tests/Feature/OtherModulesTest.php`.

---

### Task 1: Scaffolding de Laravel 12, Git, Tailwind CSS v4 y Alpine.js

**Files:**
- Create: estructura base Laravel 12 en raíz (preservando `docs/`)
- Create: `resources/css/app.css`
- Create: `resources/js/app.js`
- Create: `tests/Feature/BootstrapHealthTest.php`
- Modify: `package.json`, `.env`, `resources/views/welcome.blade.php` (temporal hasta Task 3)

**Interfaces:**
- Consumes: PHP 8.4, Composer 2.8, Node 26, npm 12.
- Produces: Proyecto Laravel 12 ejecutable (`php artisan test`), tokens CSS `@theme` en `resources/css/app.css`, `window.Alpine` inicializado en `resources/js/app.js`, compilación Vite (`public/build/manifest.json`).

- [ ] **Step 1: Inicializar repositorio Git y crear proyecto Laravel 12 preservando `docs/`**

Crear Laravel en un directorio temporal `_laravel_tmp` con `composer create-project laravel/laravel _laravel_tmp --no-interaction`, mover todo su contenido (incluidos ocultos `.env`, `.gitignore`, `.editorconfig`) a la raíz actual sin sobrescribir `docs/`, eliminar `_laravel_tmp`, inicializar `git init` si no existe y configurar `APP_LOCALE=es` y `APP_NAME=LifeMedic` en `.env`.

- [ ] **Step 2: Instalar Alpine.js vía npm y configurar Vite + Tailwind CSS v4**

Ejecutar `npm install` y `npm install alpinejs --save-dev`. Definir en `resources/css/app.css` `@import "tailwindcss";` y el bloque `@theme` con `--font-sans` (`Plus Jakarta Sans`), colores semánticos (`--color-canvas: #f7f8fa`, `--color-sidebar: #fafafc`, `--color-brand-blue: #165dff`, `--color-brand-orange: #f97316`, `--color-brand-green: #22c55e`, `--color-brand-lime: #a3e635`, `--color-brand-purple: #8b5cf6`, `--color-card-border: #eef0f5`) y utilidades base (`tabular-nums`, suavizado tipográfico). Importar e iniciar `Alpine` en `resources/js/app.js`.

- [ ] **Step 3: Escribir el test base de arranque y verificar que falle antes de ajustar ruta raíz si aplica**

```php
// tests/Feature/BootstrapHealthTest.php
public function test_application_boots_and_vite_manifest_exists(): void
{
    $this->assertFileExists(public_path('build/manifest.json'));
    $this->assertSame('LifeMedic', config('app.name'));
}
```

- [ ] **Step 4: Compilar assets con `npm run build` y ejecutar `php artisan test`**

Run: `npm run build && php artisan test tests/Feature/BootstrapHealthTest.php`
Expected: `public/build/manifest.json` generado y `PASS` en `BootstrapHealthTest`.

- [ ] **Step 5: Commit inicial**

```bash
git add .
git commit -m "chore: scaffold laravel 12 with tailwind v4 and alpinejs"
```

---

### Task 2: Servicio `PharmacyMockData` y Componentes Compartidos del Shell

**Files:**
- Create: `app/Support/PharmacyMockData.php`
- Create: `resources/views/components/layouts/app.blade.php`
- Create: `resources/views/components/sidebar.blade.php`
- Create: `resources/views/components/sidebar-link.blade.php`
- Create: `resources/views/components/card.blade.php`
- Create: `resources/views/components/kpi-mini.blade.php`
- Create: `resources/views/components/page-header.blade.php`
- Create: `resources/views/components/status-badge.blade.php`
- Create: `resources/views/components/action-buttons.blade.php`
- Create: `resources/views/components/modal.blade.php`
- Create: `resources/views/components/toast.blade.php`
- Create: `resources/views/components/icon.blade.php`
- Test: `tests/Unit/PharmacyMockDataTest.php`
- Test: `tests/Feature/LayoutShellTest.php`

**Interfaces:**
- Consumes: Tokens Tailwind v4 y Alpine.js de Task 1.
- Produces:
  - `App\Support\PharmacyMockData::navigation(): array`
  - `App\Support\PharmacyMockData::dashboard(): array`
  - `App\Support\PharmacyMockData::purchases(): array`
  - `App\Support\PharmacyMockData::sales(): array`
  - `App\Support\PharmacyMockData::products(): array`
  - `App\Support\PharmacyMockData::suppliers(): array`
  - `App\Support\PharmacyMockData::customers(): array`
  - `App\Support\PharmacyMockData::medicines(): array`
  - `App\Support\PharmacyMockData::invoices(): array`
  - `App\Support\PharmacyMockData::orders(): array`
  - `App\Support\PharmacyMockData::salesReport(): array`
  - `App\Support\PharmacyMockData::support(): array`
  - `App\Support\PharmacyMockData::settings(): array`
  - `App\Support\PharmacyMockData::plans(): array`
  - Componentes Blade `<x-layouts.app :title="$title" :hideDesktopHeader="false">`, `<x-card>`, `<x-kpi-mini>`, `<x-page-header>`, `<x-status-badge>`, `<x-action-buttons>`, `<x-modal>`, `<x-toast>`, `<x-icon>`.

- [ ] **Step 1: Escribir tests unitarios y de shell antes de crear las clases**

En `tests/Unit/PharmacyMockDataTest.php`: verificar que `navigation()` devuelve exactamente 3 secciones (`MENÚ`, `OTROS`, `PREFERENCIAS`) con 12 ítems totales, que `dashboard()['purchases']` y `purchases()['items']` cumplen `round($row['payable'] - $row['paid'], 2) === round($row['due'], 2)`, que `orders()['items']` usa únicamente estados permitidos (`pendiente`, `confirmado`, `entregado`, `cancelado`), y que `products()['categories']` incluye `Medicamentos`, `Dermocosmética`, `Vitaminas`, `Cuidado Personal`, `Bebé`, `Fragancias`.
En `tests/Feature/LayoutShellTest.php`: renderizar un componente `<x-layouts.app>` de prueba verificando los 12 enlaces laterales en español, la tarjeta `Actualiza a Pro` (`Actualizar ahora`), el botón móvil con `min-h-[44px] min-w-[44px]` y la ausencia de barra superior extra cuando `hideDesktopHeader` es `true`.

- [ ] **Step 2: Ejecutar tests para confirmar fallo inicial**

Run: `php artisan test tests/Unit/PharmacyMockDataTest.php tests/Feature/LayoutShellTest.php`
Expected: FAIL (`Class "App\Support\PharmacyMockData" not found`).

- [ ] **Step 3: Implementar `App\Support\PharmacyMockData` con todos los datasets de farmacia en español**

Centralizar los datos de los 13 endpoints: métricas de `Reporte Gráfico de Hoy` (`Este Mes`, `Último Trimestre`, `Este Año`), `Total Proveedores: 120 (+32%)`, `Sin Stock: 103 (-09%)`, `Total Medicamentos: 150 (+43%)`, `Medicamentos Más Vendidos` (`Paracetamol $54,647.00`, `Ibuprofeno $12,345.00`, `Amoxicilina $16,567.00`, `Metformina $10,412.00`), series semanales de `Ingresos vs Gastos Totales`, `Reporte de Hoy` (`$536.00`, `80%`, 4 conceptos), `Lista de Compras` (`#P-00270`, `#P-00268`, `#P-00267` con aritmética coherente + filas extra en `/compras`) y datasets realistas de `ventas`, `productos` (`MED-00101`, `DER-00205`, etc.), `proveedores`, `clientes`, `medicamentos`, `facturas`, `pedidos` (`LM-2026-00501`...), `reportes`, `soporte` (FAQ + consultas farmacéuticas), `configuración` (umbral envío gratuito) y `plan pro`.

- [ ] **Step 4: Implementar los componentes Blade del shell y sistema UI**

Crear `<x-layouts.app>` (con Alpine state global para `sidebarOpen` en móvil y sistema `toast` global mediante eventos `window.dispatchEvent(new CustomEvent('notify', ...))`), `<x-sidebar>` (gradiente oscuro en ítem activo, 3 grupos colapsables iniciando abiertos, tarjeta azul `Actualiza a Pro` con candado y ondas SVG sutiles), `<x-icon>` (iconos vectoriales limpios estilo Lucide), `<x-card>`, `<x-kpi-mini>`, `<x-page-header>`, `<x-status-badge>`, `<x-action-buttons>` (botón azul Ver, verde Editar, naranja Eliminar) y `<x-modal>`.

- [ ] **Step 5: Ejecutar tests para verificar que pasen**

Run: `php artisan test tests/Unit/PharmacyMockDataTest.php tests/Feature/LayoutShellTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Support/PharmacyMockData.php resources/views/components tests/Unit/PharmacyMockDataTest.php tests/Feature/LayoutShellTest.php
git commit -m "feat: add PharmacyMockData service and reusable Blade layout components"
```

---

### Task 3: Dashboard Principal (`Panel` — `/`) y Gráficas SVG Interactivas

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Create: `resources/views/dashboard/index.blade.php`
- Create: `resources/views/components/charts/report-bars.blade.php`
- Create: `resources/views/components/charts/top-medicines.blade.php`
- Create: `resources/views/components/charts/revenue-expense.blade.php`
- Create: `resources/views/components/charts/today-donut.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/DashboardPageTest.php`

**Interfaces:**
- Consumes: `App\Support\PharmacyMockData::dashboard()`, `<x-layouts.app>`, `<x-card>`, `<x-kpi-mini>`, `<x-action-buttons>`, `<x-modal>`.
- Produces: Ruta GET `/` (`dashboard`), `DashboardController::__invoke()`, 4 componentes de gráficas SVG/Alpine interactivas y modales de `Ver` / `Editar` / `Eliminar` sobre `Lista de Compras`.

- [ ] **Step 1: Escribir `tests/Feature/DashboardPageTest.php`**

Aserciones obligatorias:
- `GET /` responde `200`.
- Contiene exactamente las etiquetas en español de las 5 tarjetas: `Reporte Gráfico de Hoy`, `Total Proveedores`, `120`, `Sin Stock`, `103`, `Total Medicamentos`, `150`, `Medicamentos Más Vendidos`, `Paracetamol`, `$54,647.00`, `Ibuprofeno`, `$12,345.00`, `Amoxicilina`, `$16,567.00`, `Metformina`, `$10,412.00`, `Ingresos vs Gastos Totales`, `Ingresos`, `Gastos`, `Reporte de Hoy`, `Ganancia Total`, `$536.00`, `80%`, `Compra Total`, `Efectivo Recibido`, `Recibido en Banco`, `Servicio Total`, `Lista de Compras`, `Ver Todo`, `#P-00270`, `#P-00268`, `#P-00267`.
- Eje Y contiene `0`, `5K`, `10K`, `15K`, `20K` y meses en español (`Ene`..`Dic`).
- NO contiene textos filtrados en inglés (`Graph Report Today`, `Top Selling Medicine`, `Over All Revenue`, `Today's Report`, `Purchase List`, `Upgrade Pro`, `Out of Stock`, `View All`).
- Contiene enlaces válidos hacia las 12 rutas del sidebar + `/plan-pro`.

- [ ] **Step 2: Ejecutar test para verificar fallo**

Run: `php artisan test tests/Feature/DashboardPageTest.php`
Expected: FAIL (ruta `/` aún devuelve vista de bienvenida por defecto).

- [ ] **Step 3: Implementar los 4 componentes de gráficas SVG/Alpine y `DashboardController` + `dashboard/index.blade.php`**

- `report-bars.blade.php`: 12 barras (`Ene`–`Dic`) con segmentos azul inferior y naranja superior con extremos redondeados y separación blanca `3px`, sin eje Y numérico ni líneas de grilla, tooltip en hover y selector (`Este Mes`, `Último Trimestre`, `Este Año`). Integrado dentro de la tarjeta con las 3 mini-KPI a la derecha.
- `top-medicines.blade.php`: tira de ~36 tics verticales redondeados agrupados en azul, verde, naranja y violeta + 4 filas de medicamentos con icono, barra horizontal redondeada y monto formateado + selector de período.
- `revenue-expense.blade.php`: gráfico SVG de velas/rangos (`Ingresos` azul, `Gastos` naranja) con ~36–48 puntos semanales, ticks horizontales y histograma base tenue `#F1F5F9`, ejes `0`–`20K` y `Ene`–`Dic`, tooltip al pasar el cursor y selector `Mensual` / `Trimestral` / `Anual`.
- `today-donut.blade.php`: aro exterior concéntrico suave, anillo segmentado de 4 colores (`#165DFF`, `#A3E635`, `#22C55E`, `#F97316`), disco interior azul sólido con `Ganancia Total` y `80%`, tooltips por segmento y menú `...` interactivo.
- `Lista de Compras`: checkboxes reactivos (seleccionar todo / fila), botones Ver/Editar/Eliminar con modales Alpine funcionales y notificación toast al simular edición o eliminación.
- Registrar `Route::get('/', DashboardController::class)->name('dashboard');` en `routes/web.php`.

- [ ] **Step 4: Ejecutar `DashboardPageTest` y confirmar pase**

Run: `php artisan test tests/Feature/DashboardPageTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add routes/web.php app/Http/Controllers/DashboardController.php resources/views/dashboard resources/views/components/charts tests/Feature/DashboardPageTest.php
git commit -m "feat: build spanish reference dashboard with custom interactive SVG charts"
```

---

### Task 4: Módulos de `MENÚ` (`Compras`, `Ventas`, `Productos`, `Proveedores`)

**Files:**
- Create: `app/Http/Controllers/PurchaseController.php`
- Create: `app/Http/Controllers/SaleController.php`
- Create: `app/Http/Controllers/ProductController.php`
- Create: `app/Http/Controllers/SupplierController.php`
- Create: `resources/views/modules/compras.blade.php`
- Create: `resources/views/modules/ventas.blade.php`
- Create: `resources/views/modules/productos.blade.php`
- Create: `resources/views/modules/proveedores.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/MenuModulesTest.php`

**Interfaces:**
- Consumes: `PharmacyMockData::purchases()`, `sales()`, `products()`, `suppliers()`, componentes del shell.
- Produces: Rutas nombradas `compras.index` (`/compras`), `ventas.index` (`/ventas`), `productos.index` (`/productos`), `proveedores.index` (`/proveedores`) y sus 4 vistas Blade interactivas.

- [ ] **Step 1: Escribir `tests/Feature/MenuModulesTest.php`**

Verificar HTTP 200 en `/compras`, `/ventas`, `/productos`, `/proveedores`, presencia de títulos en español, botones primarios (`Nueva Compra`, `Nueva Venta`, `Nuevo Producto`, `Nuevo Proveedor`), filtros/categorías de LifeMedic (`Medicamentos`, `Dermocosmética`, `Vitaminas`, `Cuidado Personal`, `Bebé`, `Fragancias`), estado vacío Alpine (`No se encontraron`) y ausencia de textos en inglés.

- [ ] **Step 2: Ejecutar test para verificar fallo**

Run: `php artisan test tests/Feature/MenuModulesTest.php`
Expected: FAIL (`404 Not Found`).

- [ ] **Step 3: Implementar controladores, rutas y las 4 vistas Blade**

- `compras.blade.php`: 4 KPI cards consistentes con los datos de compras, filtros por estado (`Todos`, `Pagado`, `Parcial`, `Pendiente`) + buscador en vivo Alpine, tabla completa con Ver/Editar/Eliminar modales y modal `+ Nueva Compra`.
- `ventas.blade.php`: KPIs diarios/mensuales, barra visual de distribución por método de pago (`Efectivo`, `Tarjeta`, `Transferencia`), tabla de ventas (`#V-00412`...) con filtro, modal de detalle de ticket y modal `+ Nueva Venta`.
- `productos.blade.php`: filtros por categoría oficial de LifeMedic (`Todos`, `Medicamentos`, `Dermocosmética`, `Vitaminas`, `Cuidado Personal`, `Bebé`, `Fragancias`), toggle vista `Tabla` / `Tarjetas`, códigos `MED-00101` / `DER-00205`, badges de stock y modal `+ Nuevo Producto`.
- `proveedores.blade.php`: KPIs de proveedores (`Square`, `Beximco`, `Renata`, `Laboratorios MK`, `Eucerin`, `Bayer Salud`), buscador, vista de tabla/tarjetas de contacto, saldo pendiente y modal `+ Nuevo Proveedor`.

- [ ] **Step 4: Ejecutar `MenuModulesTest` y confirmar pase**

Run: `php artisan test tests/Feature/MenuModulesTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add routes/web.php app/Http/Controllers/PurchaseController.php app/Http/Controllers/SaleController.php app/Http/Controllers/ProductController.php app/Http/Controllers/SupplierController.php resources/views/modules/compras.blade.php resources/views/modules/ventas.blade.php resources/views/modules/productos.blade.php resources/views/modules/proveedores.blade.php tests/Feature/MenuModulesTest.php
git commit -m "feat: add Compras, Ventas, Productos and Proveedores modules"
```

---

### Task 5: Módulos de `OTROS` (`Clientes`, `Medicamentos`, `Facturas`, `Pedidos`)

**Files:**
- Create: `app/Http/Controllers/CustomerController.php`
- Create: `app/Http/Controllers/MedicineController.php`
- Create: `app/Http/Controllers/InvoiceController.php`
- Create: `app/Http/Controllers/OrderController.php`
- Create: `resources/views/modules/clientes.blade.php`
- Create: `resources/views/modules/medicamentos.blade.php`
- Create: `resources/views/modules/facturas.blade.php`
- Create: `resources/views/modules/pedidos.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/OtherModulesTest.php`

**Interfaces:**
- Consumes: `PharmacyMockData::customers()`, `medicines()`, `invoices()`, `orders()`, componentes del shell.
- Produces: Rutas nombradas `clientes.index` (`/clientes`), `medicamentos.index` (`/medicamentos`), `facturas.index` (`/facturas`), `pedidos.index` (`/pedidos`) alineadas al esquema `USERS`, `MEDICINES`, `ORDERS`, `ORDER_DETAILS` de LifeMedic.

- [ ] **Step 1: Escribir `tests/Feature/OtherModulesTest.php`**

Verificar HTTP 200 en las 4 rutas, presencia de `MED-00101`, `LM-2026-00501`, pestañas de `Pedidos` (`Todos`, `Pendiente`, `Confirmado`, `Entregado`, `Cancelado`), badge `Envío Gratis`, vista previa de factura en `/facturas`, control de vencimiento/receta en `/medicamentos`, estado vacío en filtros y cero textos en inglés.

- [ ] **Step 2: Ejecutar test para verificar fallo**

Run: `php artisan test tests/Feature/OtherModulesTest.php`
Expected: FAIL (`404 Not Found`).

- [ ] **Step 3: Implementar controladores, rutas y las 4 vistas Blade**

- `clientes.blade.php`: listado de clientes de la app móvil (`name`, `email`, `phone`, dirección, pedidos realizados, total gastado, estado `Activo`/`Inactivo`), modal historial de pedidos y modal `+ Nuevo Cliente`.
- `medicamentos.blade.php`: tabla farmacéutica con `codigo`, nombre/concentración, categoría, laboratorio, lote/vencimiento (`Vigente`, `Por Vencer`, `Agotado`), `Con Receta`/`Venta Libre`, precio/stock y modal `+ Registrar Medicamento`.
- `facturas.blade.php`: listado `#FAC-0981` con filtro de estado (`Pagada`, `Pendiente`, `Vencida`), desglose subtotal/envío/IVA/total y modal de **Vista Previa de Factura** con botón simular impresión/descarga.
- `pedidos.blade.php`: pestañas interactivas (`Todos`, `Pendiente`, `Confirmado`, `Entregado`, `Cancelado`), columnas `# Pedido` (`LM-2026-00501`), `Cliente`, `Fecha`, `Entrega` (`Domicilio` / `Retiro en Farmacia`), `Artículos`, `Envío Gratis`, `Total`, modal de detalle con líneas `ORDER_DETAILS` y selector simulado de cambio de estado + modal `+ Nuevo Pedido`.

- [ ] **Step 4: Ejecutar `OtherModulesTest` y confirmar pase**

Run: `php artisan test tests/Feature/OtherModulesTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add routes/web.php app/Http/Controllers/CustomerController.php app/Http/Controllers/MedicineController.php app/Http/Controllers/InvoiceController.php app/Http/Controllers/OrderController.php resources/views/modules/clientes.blade.php resources/views/modules/medicamentos.blade.php resources/views/modules/facturas.blade.php resources/views/modules/pedidos.blade.php tests/Feature/OtherModulesTest.php
git commit -m "feat: add Clientes, Medicamentos, Facturas and Pedidos modules"
```

---

### Task 6: Módulos de `PREFERENCIAS` y `Plan Pro` (`Reporte de Ventas`, `Ayuda y Soporte`, `Configuración`, `Plan Pro`)

**Files:**
- Create: `app/Http/Controllers/ReportController.php`
- Create: `app/Http/Controllers/SupportController.php`
- Create: `app/Http/Controllers/SettingController.php`
- Create: `app/Http/Controllers/PlanController.php`
- Create: `resources/views/modules/reporte-ventas.blade.php`
- Create: `resources/views/modules/ayuda.blade.php`
- Create: `resources/views/modules/configuracion.blade.php`
- Create: `resources/views/modules/plan-pro.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PreferenceModulesTest.php`

**Interfaces:**
- Consumes: `PharmacyMockData::salesReport()`, `support()`, `settings()`, `plans()`, componentes del shell.
- Produces: Rutas nombradas `reportes.ventas` (`/reportes/ventas`), `ayuda.index` (`/ayuda`), `configuracion.index` (`/configuracion`), `plan-pro.index` (`/plan-pro`).

- [ ] **Step 1: Escribir `tests/Feature/PreferenceModulesTest.php`**

Verificar HTTP 200 en `/reportes/ventas`, `/ayuda`, `/configuracion`, `/plan-pro`, presencia de secciones clave (`Consulta Farmacéutica`, `Envío Gratuito`, `Mensual`, `Anual`), y validar en una prueba parametrizada que **todas las 13 rutas** responden 200 y no contienen ninguno de los strings filtrados en inglés (`Graph Report Today`, `Top Selling Medicine`, `Purchase List`, `Upgrade Pro`, `Out of Stock`, `View All`).

- [ ] **Step 2: Ejecutar test para verificar fallo**

Run: `php artisan test tests/Feature/PreferenceModulesTest.php`
Expected: FAIL (`404 Not Found`).

- [ ] **Step 3: Implementar controladores, rutas y las 4 vistas Blade**

- `reporte-ventas.blade.php`: selector de período (`Últimos 7 días`, `Este Mes`, `Este Trimestre`, `Este Año`), KPIs comparativos, gráfico SVG de tendencia de ventas vs utilidad, desglose por categoría de LifeMedic y tabla extendida de medicamentos top.
- `ayuda.blade.php`: buscador + acordeón de preguntas frecuentes, bandeja simulada de **Consultas Farmacéuticas** de clientes (RF-14) con modal de respuesta simulada y formulario de ticket de soporte técnico.
- `configuracion.blade.php`: pestañas Alpine (`Farmacia`, `Perfil y Roles`, `Notificaciones`, `Envío Gratis e Impuestos`, `Apariencia`), inputs y toggles para el umbral de envío gratuito (RF-12), botón `Guardar cambios` con toast de confirmación.
- `plan-pro.blade.php`: switch interactivo `Mensual / Anual`, tarjetas comparativas `Plan Básico` vs `Plan Pro LifeMedic`, y modal/toast de solicitud simulada.

- [ ] **Step 4: Ejecutar `PreferenceModulesTest` y confirmar pase**

Run: `php artisan test tests/Feature/PreferenceModulesTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add routes/web.php app/Http/Controllers/ReportController.php app/Http/Controllers/SupportController.php app/Http/Controllers/SettingController.php app/Http/Controllers/PlanController.php resources/views/modules/reporte-ventas.blade.php resources/views/modules/ayuda.blade.php resources/views/modules/configuracion.blade.php resources/views/modules/plan-pro.blade.php tests/Feature/PreferenceModulesTest.php
git commit -m "feat: add Reporte de Ventas, Ayuda, Configuracion and Plan Pro views"
```

---

### Task 7: Verificación Integral, Compilación Final y Revisión Visual Multi-Viewport

**Files:**
- Modify (solo si la inspección visual o los tests detectan ajustes menores de espaciado/alineación): vistas Blade o `resources/css/app.css`

**Interfaces:**
- Consumes: Aplicación completa (Tasks 1–6).
- Produces: Verificación completa de rutas, suite PHPUnit, build de producción Vite y capturas/inspección visual en navegador (`375px`, `768px`, `1440px`).

- [ ] **Step 1: Verificar rutas y compilación de producción**

Run: `php artisan route:list --except-vendor && npm run build`
Expected: Exactamente las 13 rutas de la aplicación listadas sin errores y build de Vite completado con `0` errores.

- [ ] **Step 2: Ejecutar toda la suite de tests**

Run: `php artisan test`
Expected: Todos los tests (`BootstrapHealthTest`, `PharmacyMockDataTest`, `LayoutShellTest`, `DashboardPageTest`, `MenuModulesTest`, `OtherModulesTest`, `PreferenceModulesTest`) en `PASS`.

- [ ] **Step 3: Levantamiento temporal del servidor local e inspección visual en navegador**

Levantar `php artisan serve` en segundo plano, inspeccionar `/` y módulos clave en viewports `1440px` (desktop), `768px` (tablet) y `375px` (móvil) usando el subagente/navegador:
- Verificar fidelidad visual de `Panel` (`/`) frente a la imagen de referencia (sidebar gradiente negro activo, 4 gráficas SVG, 3 mini KPIs, tabla `Lista de Compras`, tarjeta `Actualiza a Pro`).
- Probar interactividad: colapsar/expandir grupos del sidebar, cambiar filtro `Este Mes`, pasar cursor sobre gráficas, abrir modal `Ver`/`Editar` en `Lista de Compras`, navegar entre módulos y probar drawer móvil en `375px` sin scroll horizontal.
- Detener el servidor temporal al finalizar y corregir cualquier defecto visual encontrado re-ejecutando `npm run build` y `php artisan test`.

- [ ] **Step 4: Commit final (si hubo ajustes visuales)**

```bash
git status
git commit -m "style: refine visual alignment after multi-viewport verification"
```

---

## Self-Review

1. **Cobertura del spec:**
   - Sección 1–2 (Laravel, rutas, controladores, `PharmacyMockData`, Tailwind v4, Alpine, SVG a medida): Tasks 1, 2 y 3.
   - Sección 3 (Tokens visuales exactos, sidebar sin logotipo arriba, sin topbar desktop en `Panel`, drawer móvil `44px+`): Tasks 1 y 2.
   - Sección 4.1 (Las 5 tarjetas exactas del Dashboard en español, eje `0–20K`, aritmética coherente de `Lista de Compras`): Task 3.
   - Sección 4.2 (Los 11 módulos restantes + `Plan Pro` alineados a `Funcionalidades - LifeMedic (1).md`): Tasks 4, 5 y 6.
   - Sección 5 (Verificación `route:list`, `npm run build`, HTTP 200, multi-viewport): Task 7.
2. **Escaneo de pasos:** Cada tarea tiene archivos exactos, firmas de métodos en `Interfaces`, pasos accionables y comandos de verificación claros sin placeholders tipo `TBD`.
3. **Consistencia de tipos y nombres:** Los nombres de métodos en `PharmacyMockData` (`navigation`, `dashboard`, `purchases`, `sales`, `products`, `suppliers`, `customers`, `medicines`, `invoices`, `orders`, `salesReport`, `support`, `settings`, `plans`) y nombres de rutas (`dashboard`, `compras.index`, ..., `plan-pro.index`) coinciden exactamente entre todas las tareas.
4. **Review Focus:** Los 5 riesgos detectados (fuga de inglés, aritmética de compras, enlaces rotos, overflow/drawer móvil, estados vacíos) tienen tests dedicados en las tareas correspondientes.
5. **Proporción:** El plan es conciso y define interfaces/pruebas sin transcribir cuerpos extensos de Blade o CSS.
