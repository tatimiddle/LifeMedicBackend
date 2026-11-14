# Documento de Diseño: Panel Administrativo LifeMedic (Laravel)

**Fecha:** 2026-11-12  
**Repositorio:** `lifemedicLarabel`  
**Idioma de la UI:** 100% Español  
**Enfoque elegido:** Laravel + Blade + Tailwind CSS (Vite) + Alpine.js + Gráficas SVG/JS a medida (Opción 1)

---

## 1. Propósito y Alcance

Construir desde cero un proyecto Laravel que sirva como base estructural y visual de **LifeMedic**, un panel administrativo para farmacia.

- **Objetivo de esta fase:** Maquetar con fidelidad visual el **Dashboard (`Panel`)** idéntico a la imagen de referencia y las **12 pantallas de módulos del menú lateral** (más la vista `Plan Pro`), todo en español y con interactividad de interfaz de usuario (filtros locales, acordeones de menú, modales de ver/crear/editar, checkboxes, notificaciones toast y diseño responsive).
- **Fuera de alcance en esta fase (No-objetivos):** Persistencia real en base de datos, migraciones/modelos Eloquent complejos, autenticación/roles, generación real de PDFs o pasarelas de pago. Toda esa lógica se conectará en fases posteriores sin tener que rehacer las vistas.
- **Anti-objetivos:** Romper la estética limpia de la imagen de referencia añadiendo barras superiores recargadas, sombras pesadas, colores genéricos o librerías de gráficos que deformen las visualizaciones originales.

---

## 2. Arquitectura en Laravel (Preparada para Lógica Real)

Para que las vistas queden desacopladas de los datos de prueba y puedan conectarse a Eloquent más adelante con cambios mínimos:

- **Estructura de rutas (`routes/web.php`):** Rutas nombradas y agrupadas semánticamente para los 12 módulos + `plan-pro`:
  - `dashboard` (`/`)
  - `compras.index` (`/compras`)
  - `ventas.index` (`/ventas`)
  - `productos.index` (`/productos`)
  - `proveedores.index` (`/proveedores`)
  - `clientes.index` (`/clientes`)
  - `medicamentos.index` (`/medicamentos`)
  - `facturas.index` (`/facturas`)
  - `pedidos.index` (`/pedidos`)
  - `reportes.ventas` (`/reportes/ventas`)
  - `ayuda.index` (`/ayuda`)
  - `configuracion.index` (`/configuracion`)
  - `plan-pro.index` (`/plan-pro`)
- **Controladores por módulo (`app/Http/Controllers/`):** Un controlador ligero por módulo (o agrupado limpiamente: `DashboardController`, `PurchaseController`, `SaleController`, `ProductController`, `SupplierController`, `CustomerController`, `MedicineController`, `InvoiceController`, `OrderController`, `ReportController`, `SupportController`, `SettingController`, `PlanController`) que recibe datos de `App\Support\PharmacyMockData` y los pasa tipados/estructurados a su vista Blade. Cuando llegue la fase real, solo se reemplaza la llamada al mock por consultas Eloquent.
- **Componentes Blade reutilizables (`resources/views/components/`):**
  - `layouts/app.blade.php`: Shell maestro con barra lateral fija en desktop (`lg:`) y drawer deslizable en móvil/tablet. En desktop (`lg+`), la vista `Panel` arranca directamente con la grilla de tarjetas sin barra superior (fiel a la imagen de referencia); las 11 pantallas secundarias utilizan `page-header.blade.php` para título, subtítulo, filtros y botón de acción primaria. En móvil/tablet (`< lg`) aparece una barra superior compacta con botón hamburguesa (`44px×44px`) y nombre de la pantalla actual.
  - `sidebar.blade.php`: Barra lateral sin cabecera de logotipo voluminosa arriba (inicia directo con margen superior limpio y el grupo `MENÚ`, igual que la referencia; el drawer móvil muestra el título `LifeMedic` y botón de cierre). Contiene 3 secciones colapsables que inician **todas expandidas por defecto**:
    1. `MENÚ`: `Panel` (primero), `Compras`, `Ventas`, `Productos`, `Proveedores`
    2. `OTROS`: `Clientes`, `Medicamentos`, `Facturas`, `Pedidos`
    3. `PREFERENCIAS`: `Reporte de Ventas`, `Ayuda y Soporte`, `Configuración`
    Más la tarjeta inferior `Actualiza a Pro`. El ítem activo usa gradiente oscuro tipo cápsula.
  - `card.blade.php`, `kpi-mini.blade.php`, `page-header.blade.php`, `status-badge.blade.php`, `action-buttons.blade.php`, `modal.blade.php`, `toast.blade.php`.
- **Frontend Stack (Vite):**
  - **Tailwind CSS v4** con tokens semánticos en `resources/css/app.css`.
  - **Alpine.js** (instalado vía npm e importado en `resources/js/app.js`, sin CDN externos frágiles) para menús colapsables, modales de ver/crear/editar, tabs, selección múltiple de filas, filtros en vivo sobre los datos maquetados y notificaciones toast.
  - **Gráficas SVG/JS a medida:** Implementadas como componentes Blade/Alpine interactivos (con tooltips al pasar el cursor y cambio de período `Este Mes` / `Último Trimestre` / `Este Año`) para reproducir exactamente la geometría de la imagen.

---

## 3. Sistema Visual y Tokens de Diseño

Basado exactamente en la imagen de referencia:

- **Superficies:**
  - Lienzo principal: `#F7F8FA`
  - Barra lateral: `#FAFAFC` con borde derecho `#ECEEF2` (`248px` en desktop)
  - Tarjetas: `#FFFFFF`, borde `1px solid #EEF0F5`, `rounded-2xl` (`16px`), sombra suave ambiental
  - Sub-tarjetas KPI internas: `#F6F8FB`, `rounded-xl`
- **Colores de Marca y Semánticos:**
  - **Negro activo (Sidebar):** Gradiente vertical `#27272A` → `#09090B` con sombra suave y texto/icono blanco
  - **Azul Primario:** `#165DFF` (segmento inferior de barras, línea de ingresos, barra Paracetamol, círculo central 80%, botón Ver)
  - **Naranja Acento:** `#F97316` / `#FF7A1A` (segmento superior de barras, línea de gastos, barra Amoxicilina, tendencia negativa, botón Eliminar)
  - **Verde Éxito:** `#22C55E` (tendencia positiva `↗`, barra Ibuprofeno, botón Editar)
  - **Verde Lima:** `#A3E635` (Efectivo Recibido en Reporte de Hoy)
  - **Violeta:** `#8B5CF6` (barra Metformina y bloque derecho de tics)
  - **Tarjeta Plan Pro:** Gradiente azul zafiro `#3B82F6` → `#1D4ED8` con ondas decorativas translúcidas, icono de candado esmerilado y botón blanco `Actualizar ahora`.
- **Tipografía:** `Plus Jakarta Sans` (con `tabular-nums` en todas las cifras, montos, porcentajes e IDs).
- **Responsive (Mobile-first):**
  - En `< lg`, la barra lateral se convierte en un drawer táctil activado desde una barra superior compacta móvil (`44px+` touch targets); en `lg+` desaparece la barra superior móvil para mantener la vista limpia igual a la referencia.
  - Grillas fluidas (`grid-cols-1` → `md:grid-cols-2` → `xl:grid-cols-12`) y tablas dentro de contenedores `overflow-x-auto`.

---

## 4. Diseño Detallado de Pantallas

### 4.1 Pantalla Principal: `Panel` (`/`)
Replica exacta del dashboard de la imagen de referencia, traducida al español:

1. **Fila Superior (12 columnas: `xl:col-span-7` + `xl:col-span-5`):**
   - **Tarjeta 1 — `Reporte Gráfico de Hoy` (`Este Mes ▾`):**
     - Lado izquierdo: Gráfico de 12 barras verticales (`Ene`–`Dic`) con puntas redondeadas, sin etiquetas numéricas ni líneas de grilla en el eje Y (igual a la referencia). Cada barra tiene un segmento inferior azul y un segmento superior naranja separados por una brecha blanca de `3px`; tooltip interactivo al pasar el cursor y actualización al cambiar el selector (`Este Mes` / `Último Trimestre` / `Este Año`).
     - *[Decisión de diseño]* Significado de series en tooltips y datos: segmento inferior azul = `Ventas`, segmento superior naranja = `Compras`.
     - Lado derecho (integrado dentro de la tarjeta): 3 mini tarjetas apiladas:
       - Icono camión | `Total Proveedores` / **`120`** | `↗ 32%` (verde)
       - Icono tendencia | `Sin Stock` / **`103`** | `↘ 09%` (naranja)
       - Icono frasco medicina | `Total Medicamentos` / **`150`** | `↗ 43%` (verde)
   - **Tarjeta 2 — `Medicamentos Más Vendidos` (`Este Mes ▾`):**
     - Tira superior horizontal de ~36 barras verticales delgadas redondeadas agrupadas por color (azul, verde, naranja, violeta) representando la proporción relativa de ventas por producto.
     - Lista de 4 filas con icono, nombre, barra de progreso horizontal redondeada y monto:
       - `Paracetamol` — Azul — `$54,647.00`
       - `Ibuprofeno` — Verde — `$12,345.00`
       - `Amoxicilina` — Naranja — `$16,567.00`
       - `Metformina` — Violeta — `$10,412.00`
     - *[Decisión de diseño]* Se conserva el orden visual exacto de los 4 medicamentos de la referencia (`Paracetamol`, `Ibuprofeno`, `Amoxicilina`, `Metformina`) y la escala visual de cada barra horizontal proporcional al valor respecto al máximo (`$54,647.00`), añadiendo unidades vendidas en el tooltip para justificar el ordenamiento.

2. **Fila Media (12 columnas: `xl:col-span-8` + `xl:col-span-4`):**
   - **Tarjeta 3 — `Ingresos vs Gastos Totales` (`Mensual ▾`):**
     - Leyenda con anillos huecos: `Ingresos` (azul) y `Gastos` (naranja).
     - Eje X (`Ene`–`Dic`). *[Decisión de diseño]* En el eje Y se usa escala lineal limpia `0`, `5K`, `10K`, `15K`, `20K` (corrigiendo el `1K` irregular de la captura de referencia para que los cálculos reales futuros escalen bien sin alterar la apariencia).
     - Gráfico SVG con ~36–48 puntos semanales distribuidos en los 12 meses: cada punto muestra un tallo vertical delgado con marcas horizontales superior/inferior tipo rango (`Ingresos` en azul y `Gastos` en naranja) superpuesto a un histograma de barras verticales gris muy tenue (`#F1F5F9`) en la base que representa volumen diario/semanal, con tooltip interactivo al hover.
   - **Tarjeta 4 — `Reporte de Hoy` (`...`):**
     - Subtítulo `Ganancia Total` → **`$536.00`** `↗ 43%`.
     - Lista izquierda con anillos huecos de colores y solo etiqueta (sin cifras impresas junto a la lista, fiel a la referencia): `Compra Total` (azul), `Efectivo Recibido` (verde lima), `Recibido en Banco` (verde), `Servicio Total` (naranja).
     - Derecha: Medidor circular completo (360°) con halo exterior gris suave, anillo segmentado de 4 colores con separación sutil y círculo interior azul sólido con `Ganancia Total` y **`80%`**, con tooltips informativos por segmento al pasar el cursor.

3. **Fila Inferior (12 columnas):**
   - **Tarjeta 5 — `Lista de Compras` (botón `Ver Todo` → `/compras`):**
     - Tabla con checkbox maestro y por fila: `ID Factura`, `Fecha`, `Proveedor`, `Monto a Pagar`, `Monto Pagado`, `Pendiente`, `Acción`.
     - *[Decisión de diseño]* Se mantiene el formato monetario uniforme y aritmética coherente (`Pendiente = Monto a Pagar - Monto Pagado`) preservando los proveedores, IDs y montos pagados/pendientes visibles de la referencia para que coincidan con los KPIs y modales de `/compras`:
       - `#P-00270` | `19 Feb, 2025` | `Square` | `$3,160.00` | `$2,500.00` | `$660.00`
       - `#P-00268` | `20 Feb, 2025` | `Beximco` | `$8,647.10` | `$5,000.00` | `$3,647.10`
       - `#P-00267` | `21 Feb, 2025` | `Renata` | `$10,754.95` | `$5,000.00` | `$5,754.95`
     - Botones de acción por fila (Ver azul, Editar verde, Eliminar naranja) que abren modales interactivos de detalle/edición/confirmación simulada.

### 4.2 Los 11 Módulos Restantes + `Plan Pro`
Todas las pantallas comparten el mismo lenguaje visual (tarjetas blancas `rounded-2xl`, mini KPIs `#F6F8FB`, badges de estado suaves y botones de acción azul/verde/naranja) con datos realistas de farmacia:

- **MENÚ:**
  - `Compras` (`/compras`): Resumen KPI (Total compras, Pagado, Saldo pendiente, Órdenes), buscador/filtros por estado (`Pagado`, `Parcial`, `Pendiente`), tabla extendida de compras a laboratorios/proveedores y modal `+ Nueva Compra`.
  - `Ventas` (`/ventas`): KPIs de ventas del día/mes, gráfico resumido de ventas por método de pago (`Efectivo`, `Tarjeta`, `Transferencia`), tabla de ventas (`#V-00412`, cliente, artículos, total, estado) y modal `+ Nueva Venta` tipo POS simplificado.
  - `Productos` (`/productos`): Catálogo general alineado con las categorías de LifeMedic (`Todos`, `Medicamentos`, `Dermocosmética`, `Vitaminas`, `Cuidado Personal`, `Bebé`, `Fragancias`), mostrando código (`MED-00101`, `DER-00205`), marca/laboratorio, precio unitario, stock y estado (`Activo`/`Agotado`), con cambio visual entre vista tabla y tarjetas y modal `+ Nuevo Producto`.
  - `Proveedores` (`/proveedores`): Directorio de laboratorios y distribuidores (`Square`, `Beximco`, `Renata`, `Laboratorios MK`, `Eucerin`, `Bayer Salud`), métricas de saldo y entregas, tabla/tarjetas con contacto y modal `+ Nuevo Proveedor`.
- **OTROS:**
  - `Clientes` (`/clientes`): Padrón de clientes registrados desde la app móvil (`name`, `email`, `phone`, estado `Activo`/`Inactivo`, dirección principal de despacho, cantidad de pedidos y total comprado), modal de historial de pedidos del cliente y modal `+ Nuevo Cliente`.
  - `Medicamentos` (`/medicamentos`): Control específico alineado al esquema `MEDICINES` de LifeMedic: `codigo` (`MED-00101`), nombre y concentración (`Paracetamol 500mg`, `Ibuprofeno 400mg`, `Amoxicilina 500mg`, `Metformina 850mg`, `Acetaminofén 500mg (Caja x 20)`), categoría, marca/laboratorio, lote/vencimiento (badges `Vigente`, `Por Vencer`, `Agotado`), precio unitario, stock disponible, indicador `is_active` y modal `+ Registrar Medicamento`.
  - `Facturas` (`/facturas`): Listado de comprobantes (`#FAC-0981`), filtros por estado (`Pagada`, `Pendiente`, `Vencida`), desglose de subtotal/envío/IVA/total y modal de **Vista Previa de Factura** lista para imprimir en el futuro.
  - `Pedidos` (`/pedidos`): Gestión de **pedidos emitidos por clientes desde la app móvil** (alineado a `ORDERS` y `ORDER_DETAILS`): pestañas por estado exacto (`Todos`, `Pendiente`, `Confirmado`, `Entregado`, `Cancelado`), columnas `# Pedido` (`LM-2026-00501`), `Cliente`, `Fecha` (`placed_at`), `Entrega` (`Domicilio` / `Retiro en Farmacia` + dirección), `Artículos`, `Envío Gratis` (badge cuando califica), `Total` y `Acción`. Incluye modal de detalle con líneas de productos (`quantity × unit_price`), cambio visual de estado y modal `+ Nuevo Pedido`.
- **PREFERENCIAS:**
  - `Reporte de Ventas` (`/reportes/ventas`): Selector de rango temporal, KPIs comparativos, gráfico SVG de tendencia de ventas vs utilidad, desglose por categoría (`Medicamentos`, `Dermocosmética`, `Vitaminas`, etc.) y tabla extendida de medicamentos más vendidos.
  - `Ayuda y Soporte` (`/ayuda`): Buscador de preguntas frecuentes con acordeón interactivo, bandeja maquetada de **Consultas Farmacéuticas / Soporte** entrantes desde la app móvil (RF-14), guías rápidas por módulo y formulario de ticket.
  - `Configuración` (`/configuracion`): Pestañas (`Farmacia`, `Perfil y Roles`, `Notificaciones`, `Envío Gratis e Impuestos`, `Apariencia`), incluyendo configuración del umbral de **Envío Gratuito** (RF-12), campos configurables con botón `Guardar cambios` y toast de confirmación simulado.
  - `Plan Pro` (`/plan-pro`, accesible desde `Actualizar ahora`): Comparativa clara entre Plan Básico y Plan Pro con toggle `Mensual / Anual` y tarjetas de beneficios analíticos.

---

## 5. Criterios de Verificación de la Maqueta

1. `php artisan route:list` lista las 13 rutas sin errores.
2. `npm run build` compila Tailwind CSS v4 y Alpine.js limpiamente sin warnings críticos.
3. Todas las páginas responden `HTTP 200` sin excepciones Blade/PHP.
4. Verificación visual en navegador (`375px` móvil, `768px` tablet y `1440px` desktop) asegurando fidelidad del `Panel` con la imagen de referencia, cero scroll horizontal no deseado, 100% de textos en español e interactividad funcional en menús, filtros y modales.

---

## 6. Decisiones Dejadas al Implementador

- Elección exacta de iconos SVG inline estilo Lucide/Heroicons para mantener coherencia visual sin dependencias pesadas de fuentes de iconos.
- Datos ficticios específicos de filas adicionales (fechas, lotes y nombres de clientes) siempre dentro del dominio realista de farmacia.
- Detalles internos de transición Alpine.js en modales, tooltips y toast notifications.
