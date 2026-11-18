# Informe final de mejoras — Tatiana Skin and Beauty

Fecha: 18/11/2026
Base de datos: `tatiana_skins_and_beauty_corregida` (MariaDB 10.4.32)
Arquitectura: MVC propia en PHP 8 + MySQL (sin cambios de estructura, rutas ni diseño)

---

## 1. Resumen

Se corrigió el flujo completo de registro, login, reservas dinámicas, relación
servicio–producto, pagos QR/efectivo, carrito de compras y "Mis reservas", sin
alterar la arquitectura MVC, las carpetas, las rutas existentes ni el diseño
visual. Todo el cálculo de precios, anticipos y saldos se valida en el servidor.

Resultado de la verificación automatizada: **42/42 pruebas correctas**,
**75 archivos PHP sin errores de sintaxis** y **log de PHP vacío**.

---

## 2. Archivos modificados

### Núcleo y configuración
| Archivo | Cambio |
|---|---|
| `config/config.php` | Constantes `ANTICIPO_MONTO`, `PAGO_QR_IMAGEN`, `PAGO_QR_TITULAR`, `PAGO_QR_BANCO`, `PAGO_QR_CUENTA`, `PAGO_QR_PAYLOAD`; nombre de BD por defecto corregido. |
| `.env.example` | Se eliminaron marcadores de conflicto y texto dañado; se documentaron `APP_KEY`, anticipo y QR. |
| `app/core/Auth.php` | Verificación con `password_verify` + compatibilidad con hash antiguo HMAC y rehash transparente. |
| `app/core/Validate.php` | Helpers de nombre, teléfono boliviano, fecha de nacimiento (18+) y contraseña fuerte. |
| `app/views/layout/helpers.php` | Helpers `estado_pago()`, `url_archivo()`, `radios_si_no()`. |
| `app/views/layout/admin_header.php` | Corrección de texto dañado (Gestión, Configuración, Cerrar sesión, ·). |
| `public/css/style.css` | Estilos de las etiquetas de estado de pago. |

### Autenticación
| Archivo | Cambio |
|---|---|
| `app/controllers/AuthController.php` | Validación estricta de registro y login, correo único, edad mínima, contraseña fuerte, mensajes claros. |
| `app/views/auth/registro.php` | Campos obligatorios, `maxlength`, `autocomplete`, errores por campo. |
| `app/views/auth/login.php` | Mensajes de error claros y campos requeridos. |
| `bin/reset_passwords.php` | Script para restablecer contraseñas de admin y cliente de prueba. |

### Reservas
| Archivo | Cambio |
|---|---|
| `app/models/Reserva.php` | Nuevo esquema de reserva, bloqueo de horarios con `GET_LOCK`, respuestas JSON, ciclo de pago (`registrarComprobante`, `confirmarPago`, `rechazarPago`, `registrarPagoEfectivo`), totales y consultas por estado de pago. |
| `app/controllers/ReservaController.php` | Validación dinámica, acción `accion=horarios` sin crear reserva, ramas QR/efectivo, control de propiedad, subida de comprobante. |
| `app/views/reserva/nueva.php` | Preguntas faciales y por zona de depilación, mostrar/ocultar con JS, limpieza de campos ocultos, cálculo de horarios. |
| `app/views/reserva/pago.php` | QR real o aviso de configuración (nunca QR falso), datos del servicio, total, anticipo y saldo, subida de comprobante. |
| `app/views/reserva/recibo.php` | Detalle completo de la reserva y de los pagos. |
| `app/views/reserva/mis_reservas.php` | Solo reservas propias, detalle, saldo, estado y cancelación. |
| `app/views/reserva/_stepper.php` | Flujo reducido a 3 pasos coherentes. |

### Pagos y administración
| Archivo | Cambio |
|---|---|
| `app/models/MetodoPago.php` | Métodos dinámicos desde la BD (sin IDs fijos). |
| `app/controllers/AdminController.php` | Acciones `pago_confirmar`, `pago_rechazar`, `pago_efectivo`. |
| `app/views/admin/citas.php` | Verificación de comprobantes y registro de efectivo. |
| `app/controllers/AdminSistemaController.php` | Avisos de pagos por verificar. |
| `app/views/admin/notificaciones.php` | Listado de avisos actualizado. |

### Catálogo y carrito
| Archivo | Cambio |
|---|---|
| `app/models/Servicio.php` | Consultas de servicios y relación servicio–producto. |
| `app/models/Producto.php` | Validación de stock y precios en servidor. |
| `app/models/Compra.php` | Reconstrucción del carrito desde MySQL y guardado de la compra. |
| `app/controllers/CatalogoController.php` | Agregar, sumar, restar, quitar y finalizar compra con validación de stock/precio. |
| `app/views/catalogo/carrito.php` | Nombre, imagen, precio, cantidad, subtotal, total, métodos y sucursales reales. |
| `app/views/home/servicios.php` | Uso de `duracion_minutos` y categorías reales (sin warnings). |

### Base de datos
| Archivo | Cambio |
|---|---|
| `database/migracion_mejoras.sql` | Migración completa aplicada (ver sección 4). |
| `database/tatiana_skins_and_beauty_corregida.sql` | Esquema/datos base actualizados. |
| `README.md` | Credenciales y notas de configuración. |

### Eliminado
- `public/_debug_post.php` (archivo de depuración que exponía datos de POST).

---

## 3. Errores encontrados y soluciones

| # | Error encontrado | Solución aplicada |
|---|---|---|
| 1 | Contraseñas antiguas no verificaban con el login | `password_verify` con respaldo HMAC y rehash automático; contraseñas de prueba restablecidas. |
| 2 | Registro aceptaba números en nombres y campos vacíos | Validación PHP con regex de letras/acentos/ñ, `trim` y campos obligatorios. |
| 3 | Fecha de nacimiento permitía menores y fechas futuras | Validación de fecha pasada y edad mínima de 18 años. |
| 4 | Teléfono aceptaba `+591` y longitudes incorrectas | Exactamente 8 dígitos, sin prefijo. |
| 5 | Correo duplicado permitido | Índice único + validación y mensaje claro. |
| 6 | Contraseñas débiles aceptadas | Mínimo de longitud, mayúscula, minúscula, número y símbolo, con mensaje específico. |
| 7 | Formulario de reserva perdía las respuestas de piel al cambiar sucursal/fecha | Envío explícito con `accion=horarios` y conservación de datos. |
| 8 | Se creaban reservas al solo cambiar sucursal o fecha | Auto-envío eliminado; solo se recalculan horarios. |
| 9 | Preguntas de depilación mostraban todas las zonas | Primero se elige zona y luego se muestran solo sus preguntas/productos. |
| 10 | El servidor validaba campos ocultos | Validación solo de campos visibles/requeridos según zona. |
| 11 | Respuestas dinámicas no se guardaban | Columna `respuestas` JSON con todas las respuestas. |
| 12 | Anticipo fijo Bs 30 aunque el servicio costara menos | `anticipo = min(ANTICIPO_MONTO, total)`. |
| 13 | QR decorativo/falso en la página de pago | Solo se muestra el QR real configurado; si no existe, aviso de configuración. |
| 14 | La reserva se marcaba pagada automáticamente | Estado `por_verificar` hasta confirmación del administrador. |
| 15 | Pago en efectivo pedía QR | Efectivo va directo al recibo como `pendiente_efectivo`, sin QR. |
| 16 | Posible doble descuento del anticipo | El anticipo se registra una sola vez y el saldo se recalcula desde los pagos. |
| 17 | El carrito decía "está vacío" con productos | Se reconstruye desde MySQL en cada carga y se corrigió el nombre de la variable de sesión. |
| 18 | Carrito sin imágenes, cantidades ni totales | Vista reescrita con imagen, precio, cantidad, subtotal y total. |
| 19 | Precios y stock se confiaban al cliente | Validación de precio y stock en servidor antes de guardar. |
| 20 | "Mis reservas" mostraba reservas de otros usuarios | Filtro por `id_usuario` de la sesión y verificación de propiedad (403). |
| 21 | Reserva sin `id` al abrir el recibo | Se envía y valida el identificador; sin él no se muestra el recibo. |
| 22 | Estado del método de pago con mayúsculas inconsistentes | Normalización a minúsculas en modelo y controlador. |
| 23 | Subida de comprobante aceptaba extensiones peligrosas | Validación de MIME/extensión y nombre generado en servidor. |
| 24 | Admin solo cambiaba el estado sin registrar el pago | Confirmación/rechazo actualizan pago, saldo y auditoría en `pago_reserva`. |
| 25 | Texto dañado en el panel administrativo | Corrección de codificación UTF-8 en `admin_header.php`. |
| 26 | Warnings en la página de servicios | Uso de `duracion_minutos` y nombres de categoría reales. |

---

## 4. Cambios en MySQL

### Tabla `cuenta_usuario`
- `password` → `VARCHAR(255)` para `password_hash`.
- Índice único `uk_cuenta_correo (correo)`.

### Tabla `reserva` (nuevas columnas)
| Columna | Tipo | Uso |
|---|---|---|
| `tipo_tratamiento` | VARCHAR | `facial` / `depilacion` |
| `zona_tratamiento` | VARCHAR | Zona de depilación |
| `respuestas` | JSON/TEXT | Respuestas dinámicas |
| `precio_total` | DECIMAL(10,2) | Total del servicio |
| `anticipo` | DECIMAL(10,2) | Anticipo esperado |
| `monto_pagado` | DECIMAL(10,2) | Total pagado confirmado |
| `saldo_pendiente` | DECIMAL(10,2) | Saldo por pagar |
| `estado_pago` | VARCHAR | Estado del pago |
| `fecha_pago` | DATETIME | Fecha del último pago |
| `comprobante` | VARCHAR | Archivo del comprobante |
| `codigo_pago` | VARCHAR | Código de referencia |

### Tabla nueva `pago_reserva` (auditoría)
`id_pago`, `id_reserva`, `metodo`, `monto`, `tipo` (anticipo/saldo/efectivo),
`estado`, `comprobante`, `codigo_pago`, `observacion`, `fecha`, `verificado_por`.

### Tabla nueva `servicio_producto`
Relación real entre servicios y productos del catálogo existente
(`SERV01`/`SERV02` → `PROD01`, etc.). No se inventaron registros.

### Métodos de pago
- Se agregó `MP02` y se normalizó `MP01.estado = 'activo'`.

### Estados de pago usados
`sin_pago`, `pendiente_efectivo`, `por_verificar`, `anticipo_confirmado`,
`parcial`, `pagado`, `rechazado`.

---

## 5. Scripts SQL

1. **Migración de mejoras** (ejecutar una vez sobre la BD existente):

```bash
mysql -u root tatiana_skins_and_beauty_corregida < database/migracion_mejoras.sql
```

Contenido: contraseñas modernas, correo único, columnas de pago/respuestas,
tablas `pago_reserva` y `servicio_producto`, métodos de pago y backfill de
reservas existentes.

2. **Esquema completo** (instalación desde cero):

```bash
mysql -u root < database/tatiana_skins_and_beauty_corregida.sql
```

3. **Restablecer contraseñas de prueba**:

```bash
php bin/reset_passwords.php
```

---

## 6. Pago con QR real (Bolivia)

El sistema **no genera un QR falso**. Para habilitar el QR real configure en
`.env` una de estas opciones:

```env
# Opción A: imagen del QR oficial del banco/proveedor
PAGO_QR_IMAGEN=uploads/qr/qr_banco.png

# Opción B: datos de la cuenta (se muestran al cliente)
PAGO_QR_BANCO=Banco Unión
PAGO_QR_CUENTA=1234567890
PAGO_QR_TITULAR=Tatiana Skin and Beauty
PAGO_QR_PAYLOAD=
```

- `PAGO_QR_IMAGEN` acepta ruta pública o URL.
- Si queda vacío, la página muestra un aviso de configuración y el comprobante
  puede subirse igualmente; el administrador verifica el pago.
- Funciona con cualquier banco/QR Simple de Bolivia mientras se cargue la imagen
  o el payload real provisto por la entidad financiera.
- El anticipo se configura con `ANTICIPO_MONTO` (por defecto Bs 30, limitado al
  total del servicio).

---

## 7. Verificación realizada

Pruebas end-to-end automatizadas con PHP + cURL sobre el servidor local:

```
PASS=42  FAIL=0
```

Cobertura:
- Login de administrador y acceso al panel, citas y avisos.
- Rechazo de nombre con números, teléfono inválido, correo inválido, menor de
  edad, contraseña débil y contraseñas distintas.
- Registro válido + inicio de sesión automático.
- Login rechaza contraseña incorrecta.
- Horarios libres, formulario con preguntas faciales y de depilación.
- Recalcular horarios sin crear reserva.
- Reserva facial con QR, comprobante y estado `por_verificar`.
- Reserva de depilación en efectivo como `pendiente_efectivo`.
- Reserva con anticipo QR y saldo pendiente correcto.
- "Mis reservas" propia y bloqueo de reservas ajenas (403).
- Confirmación de pagos por el administrador.
- Carrito: agregar, sumar, restar, quitar y finalizar compra.
- Totales, JSON de respuestas y filas de pago correctos en MySQL.

Otras comprobaciones:
- `php -l` sobre **75 archivos**: 0 errores.
- `storage/logs/php_error.log` vacío tras las pruebas.
- Páginas públicas, login de cliente existente, panel admin y recibos con
  respuesta 200.
- Sin sesión, `admin_dashboard` redirige a login (302).

Datos de prueba eliminados y stock de `PROD01` restaurado a 5.

---

## 8. Credenciales de prueba

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin.test@test.bo` | `186c9075` |
| Cliente | `quispeliz143@gmail.com` | `12345678` |
| Empleado (SUC01) | `empleado@empleado.com` | `12345678` |

> Son las credenciales incluidas en el volcado
> `database/tatiana_skins_and_beauty_corregida.sql`. Cambiarlas antes de
> publicar en producción (especialmente la del cliente, que es débil).

---

## 9. Pendientes y recomendaciones

1. **QR real**: cargar la imagen/URL del QR del banco en `.env` para probar el
   flujo completo de anticipo (la estructura ya está lista).
2. **Producción**: definir `APP_ENV=production`, `APP_DEBUG=false`, una
   `APP_KEY` aleatoria y credenciales de BD distintas de `root`.
3. **HTTPS**: obligatorio para proteger sesiones, comprobantes y datos de pago.
4. **Respaldos**: programar copias de la BD y de `public/uploads/comprobantes`.
5. **Pasarela**: si se desea confirmación automática de pagos, integrar la API
   del proveedor QR; hoy la confirmación es manual y auditada.

---

## 10. Actualización: comprobantes en MySQL, categorías y sesiones

Fecha: 18/11/2026 · Base: `tatiana_skins_and_beauty_corregida`

### 10.1 Comprobantes guardados en la base de datos

- La tabla `pago_reserva` ahora guarda la imagen del comprobante dentro de MySQL:
  - `comprobante_nombre` (nombre original del archivo),
  - `comprobante_tipo` (MIME real, p. ej. `image/jpeg`),
  - `comprobante_datos` (`LONGBLOB` con la imagen).
- Se mantiene la copia en `public/uploads/comprobantes/` como respaldo, pero la
  vista ya no depende de que el archivo siga en disco.
- `Upload::imagenConDatos()` valida MIME/tamaño y devuelve ruta, nombre, tipo y
  datos binarios en una sola operación.
- `Reserva::registrarComprobante()` acepta los datos del archivo y guarda el
  BLOB junto con el registro de auditoría.
- Nueva ruta compartida `comprobante_pago` (`ReservaController::comprobantePago`)
  que sirve la imagen desde MySQL y, si no existe, recurre al archivo en disco:
  - **Administrador:** cualquier reserva.
  - **Empleado:** solo reservas de su sucursal.
  - **Cliente:** solo sus propias reservas.
- Migración: `database/migration_comprobantes_bd.sql`.

### 10.2 Avisos y visualización para el empleado

- `empleado/dashboard.php` incluye el panel **“Comprobantes de pago por
  verificar”** con clienta, anticipo, enlace al comprobante y acceso a la agenda.
- La campana de `empleado_header.php` muestra el número de comprobantes
  pendientes de su sucursal y enlaza al panel.
- `empleado/citas.php` agrega la columna **Pago** con estado y enlace al
  comprobante.
- `admin/citas.php`, `admin/notificaciones.php` y `reserva/recibo.php` usan la
  nueva ruta, de modo que el enlace funciona aunque el archivo se haya borrado.

### 10.3 Categorías de servicios al día

| ID | Categoría |
|---|---|
| CS01 | Depilaciones |
| CS02 | Faciales |
| CS03 | Corporales |

Asignación actual: `SERV01 Hidrafacial → CS02 Faciales`; `SERV02`, `SERV03` y
`SERV04` (depilaciones) `→ CS01 Depilaciones`.

### 10.4 Sesiones simultáneas por navegador

- La sesión usa la cookie propia `TATIANA_SESSID`, por lo que cada navegador
  (Chrome, Edge, Firefox) mantiene una sesión independiente.
- Verificado con tres sesiones simultáneas: administrador, empleado y cliente
  en paralelo, cada una con su propio panel y permisos.
- También se confirmó que un rol no puede abrir el panel de otro (403).

### 10.5 Verificación de esta actualización

- Subida real de un comprobante JPG (95 121 bytes) por el cliente.
- BLOB almacenado en `pago_reserva.comprobante_datos` con el mismo tamaño.
- `comprobante_pago` respondió `200 image/jpeg` para admin, empleado de la
  sucursal y cliente dueño; `403` para un empleado de otra sucursal.
- Paneles de admin y empleado muestran el enlace al comprobante.
- Smoke test de 30 páginas por rol: todas responden `200` (la única excepción
  es `empleado_disponibilidad`, endpoint AJAX que exige parámetros).
- `php -l` sin errores y `storage/logs/php_error.log` vacío.

### 10.6 Credenciales de prueba actualizadas

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin.test@test.bo` | `186c9075` |
| Cliente | `quispeliz143@gmail.com` | `12345678` |
| Empleado (SUC01) | `empleado@empleado.com` | `12345678` |

> El correo `empleado.test@test.bo` que figuraba en el README no existía en la
> base de datos; la cuenta real es `empleado@empleado.com` (usuario `EMP001`,
> sucursal `SUC01 SOPOCACHI`).
