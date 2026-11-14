# Documentación de APIs — LifeMedic Backend

Documentación oficial de los endpoints disponibles en el backend de **LifeMedic** (Laravel 13 + Sanctum).

- **URL base local:** `http://127.0.0.1:8000`
- **Headers recomendados:**
  - `Accept: application/json`
  - `Content-Type: application/json` (o `multipart/form-data` al subir imagen en `/api/v1/admin/medicines`)
  - `Authorization: Bearer <token>` en endpoints protegidos

---

## Convenciones Generales

El backend expone dos grupos de rutas bajo `/api`:

1. **API Móvil (`/api/*`):** Usa el formato estándar `App\Support\ApiResponse`:
   ```json
   {
     "success": true,
     "message": "Mensaje descriptivo.",
     "data": {},
     "meta": {}
   }
   ```
   En errores devuelve `success: false`, `message` y opcionalmente `errors`.
2. **API Versionada v1 (`/api/v1/*`):** Usa respuestas `{ "data": ... }` / `{ "message": ..., "data": ... }` y paginación estándar de Laravel en catálogos.

### Estados de Pedido

Todos los pedidos almacenan su clave canónica en BD y exponen su etiqueta en español (`status_label`):

| Clave (`status`) | Etiqueta (`status_label`) | Comportamiento de inventario |
|---|---|---|
| `pending` | `Pendiente` | Stock descontado atómicamente al confirmar la compra |
| `preparing` | `En preparación` | Stock reservado/descontado |
| `completed` | `Completado` | Pedido entregado o finalizado |
| `cancelled` | `Cancelado` | Reintegra automáticamente las unidades al inventario (`DB::transaction`) |

---

## 1. API Móvil REST (`/api/*`)

### 1.1 Autenticación y Perfil

#### `POST /api/auth/register` (Pública)
Registra una cuenta de cliente (`cliente`) activa y devuelve su token Bearer.

**Body:**
```json
{
  "name": "Sofía Mendoza",
  "email": "sofia@lifemedic.com",
  "phone": "+52 55 1234 5678",
  "address": "Av. Reforma 250, CDMX",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "device_name": "android-app"
}
```
- `name` (requerido, mín. 2, máx. 255)
- `email` (requerido, único)
- `phone`, `address`, `device_name` (opcionales)
- `password` + `password_confirmation` (requerido, mín. 8)

**Respuesta `201 Created`:** `data.user` + `data.token` + `data.token_type: "Bearer"`.

---

#### `POST /api/auth/login` (Pública)
Inicia sesión y emite un token Sanctum. Rechaza cuentas inactivas con `403`.

**Body:**
```json
{
  "email": "sofia@lifemedic.com",
  "password": "Password123!",
  "device_name": "ios-app"
}
```
**Respuestas:** `200 OK`, `401 Unauthorized` (credenciales inválidas), `403 Forbidden` (cuenta desactivada).

---

#### `GET /api/auth/profile` (Protegida: `auth:sanctum`, `active.user`)
Devuelve el perfil del usuario autenticado (`data.user` con `id`, `name`, `email`, `phone`, `address`, `role`, `role_label`, `is_active`).

---

#### `PUT /api/auth/profile` (Protegida: `auth:sanctum`, `active.user`)
Actualiza parcialmente `name`, `email`, `phone`, `address` o `password` (`password_confirmation`).

---

#### `POST /api/auth/logout` (Protegida: `auth:sanctum`, `active.user`)
Revoca el token Bearer actual. Devuelve `200 OK`.

---

### 1.2 Catálogo Público Móvil

#### `GET /api/categories` (Pública)
Lista las categorías disponibles ordenadas para la app móvil junto con `meta.total`.

---

#### `GET /api/medicines` y `GET /api/products` (Públicas)
Devuelve el catálogo completo filtrado opcionalmente por query string (`meta.total`, `meta.filters`).

**Query params soportados:**
- `category` / `categoria`: nombre o slug de categoría
- `type` / `catalog_type`: `medicine` o `product`
- `search` / `nombre` / `q`: búsqueda por nombre, principio activo, laboratorio o código
- `only_active` / `disponible`: filtra disponibilidad activa
- `sort`: criterio de ordenamiento

---

#### `GET /api/medicines/{identifier}` y `GET /api/products/{identifier}` (Públicas)
Consulta el detalle de un medicamento o producto por `id` numérico o código SKU (`codigo`, p. ej. `MED-00101`). Devuelve `404` si no existe.

---

### 1.3 Carrito de Compras Móvil (`auth:sanctum`, `active.user`)

#### `GET /api/cart`
Obtiene (o inicializa) el carrito activo del usuario autenticado con sus ítems, cantidades, `items_count`, `subtotal` y `total`.

---

#### `POST /api/cart/items`
Agrega un producto al carrito validando que esté activo y que `cantidad_acumulada <= stock`.

**Body:**
```json
{
  "medicine_id": 1,
  "quantity": 2
}
```
*(Alternativamente se puede enviar `"codigo": "MED-00101"` en lugar de `medicine_id`).*

**Respuestas:**
- `201 Created`: carrito actualizado
- `422 Unprocessable Entity`: medicamento inactivo o stock insuficiente

---

#### `PUT|PATCH /api/cart/items/{cartItem}`
Actualiza la cantidad de una línea del carrito (`quantity`: `0..999`). Enviar `quantity: 0` elimina el producto del carrito.

---

#### `DELETE /api/cart/items/{cartItem}`
Elimina el ítem del carrito activo del usuario autenticado (`404` si pertenece a otro usuario o no está activo).

---

### 1.4 Compras e Historial de Pedidos Móvil (`auth:sanctum`, `active.user`)

#### `POST /api/orders`
Procesa una compra enviada desde el carrito activo (o mediante `items[]` directos), valida disponibilidad y descuenta el inventario de forma atómica dentro de `DB::transaction` usando bloqueo pesimista `lockForUpdate()`. Si falla cualquier producto, revierte toda la operación (`422`) sin descontar inventario parcial.

**Body (checkout desde carrito activo):**
```json
{
  "delivery_method": "Domicilio",
  "delivery_address": "Av. Paseo de la Reforma 120",
  "shipping_cost": 45
}
```

**Body (checkout directo con ítems explícitos):**
```json
{
  "delivery_method": "Sucursal",
  "items": [
    { "medicine_id": 1, "quantity": 2 },
    { "medicine_id": 3, "quantity": 1 }
  ]
}
```
- `delivery_method`: `Domicilio | Sucursal | domicilio | sucursal | pickup | delivery` (por defecto `Domicilio`)
- `delivery_address`: opcional (toma la dirección del usuario si se omite)
- `shipping_cost`: numérico `>= 0` (por defecto `0.0`)
- `items`: opcional; si se omite se procesa el carrito activo y, tras confirmar el pedido, el carrito se limpia y marca como completado.

**Respuesta `201 Created`:**
```json
{
  "success": true,
  "message": "Pedido registrado correctamente.",
  "data": {
    "id": 1,
    "order_number": "LM-2026-AB12CD",
    "status": "pending",
    "status_label": "Pendiente",
    "delivery_method": "Domicilio",
    "delivery_method_label": "Envío a domicilio",
    "delivery_address": "Av. Paseo de la Reforma 120",
    "free_shipping": false,
    "shipping_cost": 45,
    "subtotal": 240,
    "total": 285,
    "customer_name": "Sofía Mendoza",
    "customer_email": "sofia@lifemedic.com",
    "phone": "+52 55 1234 5678",
    "admin_note": null,
    "placed_at": "2026-11-14T07:05:00+00:00",
    "status_updated_at": "2026-11-14T07:05:00+00:00",
    "items_count": 2,
    "items": [
      {
        "id": 1,
        "medicine_id": 1,
        "codigo": "MED-00101",
        "name": "Paracetamol 500mg",
        "quantity": 2,
        "unit_price": 120,
        "line_total": 240,
        "image_url": "http://127.0.0.1:8000/..."
      }
    ]
  }
}
```

---

#### `GET /api/orders`
Lista el historial de pedidos del usuario autenticado (ordenados del más reciente al más antiguo) con su estado actual (`status` y `status_label`) y líneas de detalle.

- Query param opcional: `?status=pending|preparing|completed|cancelled` (también acepta `pendiente`, `en preparación`, `completado`, `cancelado`).

---

#### `GET /api/orders/{order}`
Devuelve el detalle completo de un pedido perteneciente al usuario autenticado (`403 Forbidden` si pertenece a otro usuario).

---

## 2. API Versionada v1 (`/api/v1/*`)

Mantiene compatibilidad con clientes v1 existentes (todos los objetos `Order` incluyen también el atributo `status_label`).

### 2.1 Autenticación v1
| Método | Endpoint | Auth | Descripción |
|---|---|---|---|
| `POST` | `/api/v1/register` | Pública | Registro de cliente + emisión de token Sanctum |
| `POST` | `/api/v1/login` | Pública | Autenticación de usuario activo (`403` si está inactivo) |
| `GET` | `/api/v1/me` | `auth:sanctum`, `active.user` | Perfil del usuario autenticado con rol cargado |
| `PUT\|PATCH` | `/api/v1/me` | `auth:sanctum`, `active.user` | Actualiza perfil (`name`, `email`, `phone`, `address`, `password`) |
| `POST` | `/api/v1/logout` | `auth:sanctum`, `active.user` | Cierra sesión revocando el token actual |

### 2.2 Catálogo v1 (Público)
| Método | Endpoint | Parámetros / Notas |
|---|---|---|
| `GET` | `/api/v1/categories` | Lista categorías activas |
| `GET` | `/api/v1/medicines` | Filtros paginados: `search`, `category_id`, `category`, `catalog_type` (`medicine\|product\|all\|todos`), `min_price`, `max_price`, `sort` (`name\|price\|created_at\|id`), `direction` (`asc\|desc`), `per_page` (`1..100`) |
| `GET` | `/api/v1/medicines/{medicine}` | Detalle de medicamento activo (`404` si está inactivo) |
| `GET` | `/api/v1/products` | Igual que `/api/v1/medicines` con default `catalog_type=product` |
| `GET` | `/api/v1/products/{identifier}` | Detalle por `id` o `codigo` |

### 2.3 Carrito y Pedidos v1 (`auth:sanctum`, `active.user`)
| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/v1/cart` | Carrito activo del usuario autenticado |
| `POST` | `/api/v1/cart/items` | Agrega producto (`medicine_id` o `codigo` + `quantity`) validando stock |
| `PATCH` | `/api/v1/cart/items/{item}` | Actualiza cantidad (`0` elimina el ítem) |
| `DELETE` | `/api/v1/cart/items/{item}` | Elimina ítem del carrito |
| `GET` | `/api/v1/orders` | Historial de pedidos del usuario autenticado (`data[]` con `details.medicine` y `status_label`) |
| `GET` | `/api/v1/orders/{order}` | Detalle de un pedido del usuario (`403` si es de otro cliente) |
| `POST` | `/api/v1/orders` | Crea pedido en `DB::transaction` + `lockForUpdate()`, valida stock, descuenta inventario y vacía el carrito |

### 2.4 Administración de Inventario v1 (`auth:sanctum`, `active.user`, `role:admin,pharmacist`)
| Método | Endpoint | Descripción |
|---|---|---|
| `POST` | `/api/v1/admin/medicines` | Registra medicamento/producto (`category_id`, `codigo`, `name`, `price`, `stock`, `image`, campos farmacéuticos opcionales) |
| `PUT\|PATCH` | `/api/v1/admin/medicines/{medicine}` | Actualiza información, precio, stock o imagen |
| `DELETE` | `/api/v1/admin/medicines/{medicine}` | Desactiva medicamento (`is_active: false`) |

---

## 3. Referencia Rápida del Panel Web Administrativo

Rutas protegidas por sesión web + rol `administrador` (`/login`):

- **Listado de pedidos:** `GET /pedidos` — filtros por `estado` (`pendiente`, `en preparación`, `completado`, `cancelado`), fecha (`fecha`, `fecha_desde`, `fecha_hasta`) y búsqueda (`q`).
- **Detalle y actualización de pedido:** `GET /pedidos/{order}` y `PATCH /pedidos/{order}/estado` — datos del cliente, medicamentos, cantidades, subtotales, total y actualización de estado con nota interna.
- **Usuarios y roles:** `GET /usuarios`, `POST /usuarios`, `PUT|PATCH /usuarios/{user}`, `PATCH /usuarios/{user}/rol`, `PATCH /usuarios/{user}/estado`, `DELETE /usuarios/{user}` — listado, filtrado, alta/edición, activación/desactivación y asignación de roles (`administrador`, `farmaceutico`, `cliente`).
- **Credencial administrador sembrada (`DatabaseSeeder`):** `admin@lifemedic.com` (contraseña configurable por `LIFEMEDIC_ADMIN_PASSWORD`, por defecto `LifeMedic2026!`).
