# Objetos de base de datos: dónde están

Los **procedimientos, funciones y triggers viven en MySQL**, no en el frontend.
El frontend (`app/views/**/*.php` y `public/js/app.js`) **nunca** llama MySQL:
solo envía formularios a rutas PHP. El backend los invoca desde `app/models/*.php`
usando la conexión de `app/core/Database.php`.

## Archivos SQL (definición)

| Archivo | Contenido |
|---|---|
| `database/migration_seguridad_acceso.sql` | Estructura original de `seguridad_login` y `recuperacion_password` |
| `database/migration_smtp_recuperacion.sql` | Índice `idx_recuperacion_ip` para el límite de solicitudes |
| `database/migration_procedimientos_triggers.sql` | Objetos de **seguridad** + tabla `auditoria_seguridad` |
| `database/migration_objetos_negocio.sql` | Objetos de **negocio** + tablas `auditoria_inventario` y `auditoria_negocio` |

Para aplicarlos en otro entorno:

```bash
mysql -u root tatiana_skins_and_beauty_corregida < database/migration_smtp_recuperacion.sql
mysql -u root tatiana_skins_and_beauty_corregida < database/migration_procedimientos_triggers.sql
mysql -u root tatiana_skins_and_beauty_corregida < database/migration_objetos_negocio.sql
```

## Seguridad

| Objeto | Tipo | Se invoca desde |
|---|---|---|
| `sp_limpiar_recuperacion` | PROCEDURE | `app/models/RecuperacionPassword.php` → `limpiar()` |
| `fn_solicitudes_recientes` | FUNCTION | `RecuperacionPassword.php` → `solicitudesRecientes()` |
| `fn_segundos_espera_recuperacion` | FUNCTION | `RecuperacionPassword.php` → `segundosEspera()` |
| `trg_recuperacion_password_ai` | TRIGGER | MySQL (auditoría al crear token) |
| `trg_recuperacion_password_au` | TRIGGER | MySQL (auditoría al usar token) |
| `trg_cuenta_usuario_password_au` | TRIGGER | MySQL (auditoría al cambiar contraseña) |
| `trg_seguridad_login_bloqueo_au` | TRIGGER | MySQL (auditoría al bloquear login) |

## Negocio

| Objeto | Tipo | Se invoca desde |
|---|---|---|
| `sp_productos_stock_bajo` | PROCEDURE | `app/models/Producto.php` → `stockBajo()` |
| `sp_resumen_ventas` | PROCEDURE | `app/models/Compra.php` → `totales()` |
| `sp_agenda_dia` | PROCEDURE | `app/models/Reserva.php` → `citasDelDia()` y `citasDelDiaSucursal()` |
| `fn_total_ventas_dia` | FUNCTION | `app/models/Compra.php` → `totalDelDia()` |
| `fn_contar_stock_bajo` | FUNCTION | `app/models/Producto.php` → `contarStockBajo()` |
| `fn_citas_pendientes_sucursal` | FUNCTION | Disponible en MySQL para reportes |
| `trg_producto_au` | TRIGGER | MySQL (auditoría de cantidad, precio y activo) |
| `trg_promocion_bi` / `trg_promocion_bu` | TRIGGER | MySQL (valida fechas y precios) |
| `trg_promocion_ad` | TRIGGER | MySQL (auditoría al eliminar promoción) |
| `trg_compra_au` | TRIGGER | MySQL (auditoría al cambiar estado de pago) |
| `trg_reserva_au` | TRIGGER | MySQL (auditoría al cambiar estado o pago) |

**Nota de stock:** los triggers NO descuentan inventario. `Compra::crear()` ya lo
hace dentro de una transacción con `FOR UPDATE` y `UPDATE ... WHERE cantidad >= ?`.
Los triggers solo consultan, validan fechas de promociones y registran auditoría.

## Verificación en MySQL

```sql
SHOW PROCEDURE STATUS WHERE Db = 'tatiana_skins_and_beauty_corregida';
SHOW FUNCTION STATUS WHERE Db = 'tatiana_skins_and_beauty_corregida';
SHOW TRIGGERS;

SELECT * FROM auditoria_seguridad ORDER BY creado_en DESC LIMIT 20;
SELECT * FROM auditoria_inventario ORDER BY creado_en DESC LIMIT 20;
SELECT * FROM auditoria_negocio ORDER BY creado_en DESC LIMIT 20;
```

## Verificación en el código

```powershell
Select-String -Path app\models\*.php -Pattern 'sp_|fn_|llamar\('
```

## Frontend

No hay nada que agregar en vistas ni en JavaScript. Las pantallas que se benefician
de estos objetos son, entre otras:

- `app/views/admin/dashboard.php` → stock bajo y citas de hoy.
- `app/views/admin/notificaciones.php` → listado de stock bajo.
- `app/views/admin/ventas.php` → totales de ventas.
- `app/views/empleado/ventas.php` → agenda por sucursal.

Todas reciben los datos ya procesados desde los controladores; no ejecutan SQL.
