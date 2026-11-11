# Tatiana Skins and Beauty - Sistema de Información Web

## Descripción del Proyecto
**Tatiana Skins and Beauty** es una plataforma web integral diseñada para digitalizar y optimizar la gestión comercial y operativa del centro estético especializado en tratamientos faciales y cuidado de la piel (La Paz, Bolivia).

El sistema centraliza las operaciones clave del negocio, incluyendo la agenda de citas en tiempo real por sucursal, el registro del historial clínico-estético de la piel de cada cliente, la gestión de inventario de insumos y productos cosméticos en tiempo real, la administración de servicios y sucursales, el procesamiento de pagos con emisión de recibos y el control de accesos por roles (Administrador, Empleado/Especialista y Cliente).

---

## Arquitectura (MVC con PHP puro + PDO)

```text
tatiana_skins_and_beauty/
├── public/                  ← única carpeta que ve el navegador
│   ├── index.php            ← front controller (punto de entrada)
│   ├── css/  js/  uploads/
├── config/
│   ├── config.php           ← lee el .env y define las constantes
│   └── routes.php           ← mapa de rutas: vista → controlador, método, nivel
├── app/
│   ├── bootstrap.php        ← arranque: config, autoload, sesión, helpers
│   ├── core/                ← motor del MVC
│   │   ├── Router.php  Database.php (PDO)  Auth.php  Csrf.php
│   │   └── Flash.php  Validate.php  Upload.php
│   ├── models/              ← MODELO: todo el SQL (consultas preparadas con PDO)
│   │   ├── Model.php (base)  Ids.php
│   │   ├── Usuario  Producto  Servicio  Sucursal  MetodoPago
│   │   └── Reserva  Historial  Compra  Reporte
│   ├── controllers/         ← CONTROLADOR: reciben la petición, usan modelos, eligen vista
│   │   └── Home  Auth  Catalogo  Cliente  Reserva  Admin  AdminSistema  Errores (+ Controller base)
│   └── views/               ← VISTA: solo HTML y datos ya preparados
├── database/                ← script SQL de la base de datos
├── storage/logs/            ← registros de errores
├── bin/crear_admin.php      ← crea un administrador por consola
├── .env.example
└── README.md
```

**Flujo de una petición:** `index.php?v=catalogo` → `public/index.php` → `Router` (valida ruta, rol y CSRF) → `CatalogoController::index()` → `Producto::buscar()` (el modelo consulta con PDO) → `views/catalogo/index.php`.

**Reglas del proyecto**
* El SQL solo se escribe en `app/models/`. Los controladores y las vistas no tocan la base de datos.
* Los controladores no incluyen HTML; las vistas no contienen lógica de negocio.
* Para agregar una página nueva: 1) método en el controlador, 2) vista en `app/views/`, 3) una línea en `config/routes.php`.

---

## Requisitos
Solo **XAMPP** para Windows con **PHP 8.1 o superior** (probado con XAMPP 8.2: PHP 8.2 + MariaDB 10.4) y la extensión `pdo_mysql` (viene activada). No se necesita Composer, Node ni conexión a internet: Bootstrap, los íconos, Chart.js y las tipografías están incluidos en el repositorio.

---

## Instalación paso a paso (Windows + XAMPP)

### 1. Descargar el proyecto
* **Opción A — con Git:**
  ```bash
  git clone https://github.com/sofvaliba2/tatiana_skins_and_beauty.git
  ```
* **Opción B — sin Git:** En la página del repositorio presione **Code → Download ZIP** y descomprima el archivo.

### 2. Copiar la carpeta a `htdocs`
```text
C:\xampp\htdocs\tatiana_skins_and_beauty\
    ├─ app\
    ├─ config\
    ├─ database\
    ├─ public\
    └─ ...
```
El sistema calcula su dirección base automáticamente, así que también funciona en otra subcarpeta de `htdocs`.

### 3. Crear el archivo `.env`
Copie `.env.example` y renómbrelo a `.env`. Si MySQL tiene contraseña para `root`, escríbala en `DB_PASS`.

### 4. Iniciar Apache y MySQL
Abra el **XAMPP Control Panel** y presione **Start** en **Apache** y en **MySQL** (ambos deben quedar en verde).

### 5. Importar la base de datos
1. Abra [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
2. Sin seleccionar ninguna base de datos, haga clic en la pestaña **Importar**.
3. En *Archivo a importar* elija `database/tatiana_skins_and_beauty_stea.sql`.
4. Deje las demás opciones como están y presione **Importar**. Debe aparecer *"La importación se ejecutó exitosamente"*.

*Alternativa por consola:*
```bash
C:\xampp\mysql\bin\mysql.exe -u root < "C:\xampp\htdocs\tatiana_skins_and_beauty\database\tatiana_skins_and_beauty_stea.sql"
```

### 6. Abrir el sistema
[http://localhost/tatiana_skins_and_beauty/public/](http://localhost/tatiana_skins_and_beauty/public/) → lleva a la pantalla de inicio.

### Crear un administrador nuevo (opcional)
```bash
C:\xampp\php\php.exe bin\crear_admin.php correo@ejemplo.com "Nombre" "Apellido"
```

---

## Usuarios de prueba

| Rol | Usuario | Contraseña | Qué puede hacer |
|---|---|---|---|
<<<<<<< HEAD
| Administrador | `admin.test@test.bo` | `186c9075` | Acceso y control total del sistema, gestión de empleados, sucursales, horarios, servicios, inventario, reportes e historial clínico. |
| Cliente | `quispeliz143@gmail.com` | `12345678` | Exploración de catálogo de tratamientos/productos, reserva de citas en línea por sucursal y consulta de historial de citas. |
| Empleado | `empleado.test@test.bo` | `12345678` | Acceso al panel de empleado: agenda de citas por sucursal, clientes, historial de piel, ventas y pagos con comprobante, y edición de su perfil. No puede administrar usuarios, roles, sucursales ni ver reportes gerenciales. |

> **Empleado de prueba:** pertenece a la sucursal **SUC01 – SOPOCACHI**. Las cuentas de empleado (`ROL02`) solo pueden crearse o editarse desde **Administrador → Usuarios**; no existe registro público para empleados.
=======
| Administrador | `admin.test@test.bo` | `TatianaAdmin2026!` | Acceso y control total del sistema, gestión de empleados, sucursales, horarios, servicios, inventario, reportes e historial clínico. |
| Cliente | `quispeliz143@gmail.com` | `TatianaCliente2026!` | Exploración de catálogo de tratamientos/productos, reserva de citas en línea por sucursal y consulta de historial de citas. |

> Las contraseñas se guardan con `password_hash()` (bcrypt). Si necesitas restablecerlas
> de nuevo, ejecuta `C:\xampp\php\php.exe bin\reset_passwords.php`.
>>>>>>> origin/main
