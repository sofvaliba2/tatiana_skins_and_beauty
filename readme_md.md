# Tatiana Skins and Beauty - Sistema de Información Web

## Descripción del Proyecto
**Tatiana Skins and Beauty** es una plataforma web integral diseñada para digitalizar y optimizar la gestión comercial y operativa del centro estético especializado en tratamientos faciales y cuidado de la piel (La Paz, Bolivia).

El sistema centraliza las operaciones clave del negocio, incluyendo la agenda de citas en tiempo real por sucursal, el registro del historial clínico-estético de la piel de cada cliente, la gestión de inventario de insumos y productos cosméticos en tiempo real, la administración de servicios y sucursales, el procesamiento de pagos con emisión de recibos y el control de accesos por roles (Administrador, Empleado/Especialista y Cliente).

---

## Requisitos
Solo **XAMPP** para Windows con **PHP 8.0 o superior** (probado con XAMPP 8.2.12: PHP 8.2 + MariaDB 10.4). No se necesita Composer, Node ni conexión a internet: Bootstrap, los íconos, Chart.js y las tipografías están incluidos en el repositorio.

---

## Instalación paso a paso (Windows + XAMPP)

### 1. Descargar el proyecto
* **Opción A — con Git:**
  ```bash
  git clone https://github.com/sofvaliba2/tatiana_skins_and_beauty.git
  ```
* **Opción B — sin Git:** En la página del repositorio presione **Code → Download ZIP** y descomprima el archivo.

### 2. Copiar la carpeta a `htdocs`
La carpeta debe quedar exactamente así (si descargó el ZIP, 'tatiana_vistas')
```text
C:\xampp\htdocs\tatiana_vistas\
    ├─ app\
    ├─ public\
    ├─ database\
    ├─ index.php
    └─ ...
```
El sistema calcula su dirección base automáticamente, así que también funciona en otra subcarpeta de `htdocs`.

### 3. Iniciar Apache y MySQL
Abra el **XAMPP Control Panel** y presione **Start** en **Apache** y en **MySQL** (ambos deben quedar en verde).

### 4. Importar la base de datos
1. Abra [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
2. Sin seleccionar ninguna base de datos, haga clic en la pestaña **Importar**.
3. En *Archivo a importar* presione **Seleccionar archivo** y elija:
   `C:\xampp\htdocs\tatiana_vistas\tatiana_skins_and_beauty_stea (2).sql`
4. Deje las demás opciones como están y presione **Importar** (abajo). Debe aparecer el mensaje *"La importación se ejecutó exitosamente"* y la base de datos en el panel izquierdo.

*Alternativa por consola:*
```bash
C:\xampp\mysql\bin\mysql.exe -u root < "C:\xampp\htdocs\tatiana_vistas\tatiana_skins_and_beauty_stea (2).sql"
```

### 5. Abrir el sistema
[http://localhost/tatiana_vistas/tatiana/public//] → lleva a la pantalla de inicio.

---

## Usuarios de prueba

| Rol | Usuario | Contraseña | Qué puede hacer |
|---|---|---|---|
| Administrador | `admin.test@test.bo` | `186c9075` | Acceso y control total del sistema, gestión de empleados, sucursales, horarios, servicios, inventario, reportes e historial clínico. |
| Cliente | `quispeliz143@gmail.com` | `12345678` | Exploración de catálogo de tratamientos/productos, reserva de citas en línea por sucursal y consulta de historial de citas. |