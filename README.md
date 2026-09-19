# Sistema de la Escuelita Parvularia Arévalo Barrios

Primera etapa del sistema de gestión escolar, desarrollada con Laravel 12, MySQL, Blade y Bootstrap 5. Esta entrega incluye autenticación mediante código identificador y contraseña, roles, control de acceso por rol y bitácora de inicio y cierre de sesión.

No incluye módulos de inscripciones ni pagos.

El módulo de expedientes estudiantiles incluye ciclos, grados, secciones, grupos docentes, historial escolar por ciclo, encargados, contactos de emergencia y personas autorizadas para recoger. La ubicación en un grupo actualiza el historial académico y no representa una inscripción.

El módulo de asistencia permite el registro diario completo por grupo, consulta por fecha, correcciones autorizadas con bitácora y resumen mensual. La restricción única por ubicación escolar y fecha evita duplicados.

Los permisos aplicados son:

- Dirección y personal administrativo pueden crear y actualizar expedientes y estructura escolar.
- Cada docente consulta únicamente estudiantes con una ubicación activa en sus grupos.
- Cada padre o encargado consulta únicamente estudiantes vinculados con su cuenta.

## Requisitos

- PHP 8.2 o posterior, con las extensiones habituales de Laravel y `pdo_mysql`.
- Composer 2.
- Node.js 20 o posterior y npm.
- MySQL 8 o una versión compatible de MariaDB.

## Instalación en Windows

Ejecuta estos comandos desde PowerShell en la raíz del proyecto:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
```

Inicia MySQL desde el panel de XAMPP y crea una base de datos vacía:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS escuelita_parvularia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Configura en `.env` las credenciales locales de MySQL. El archivo ya está excluido de Git. Después ejecuta:

```powershell
php artisan migrate --seed
php artisan serve
```

En otra consola, si deseas recompilación automática de los estilos y scripts:

```powershell
npm run dev
```

La aplicación quedará disponible normalmente en `http://127.0.0.1:8000`.

## Usuarios ficticios de desarrollo

El comando `php artisan migrate --seed` crea cuatro cuentas ficticias. Todas usan la contraseña temporal `Demo1234!`:

| Rol | Código |
| --- | --- |
| Dirección | `DIR-001` |
| Personal administrativo | `ADM-001` |
| Docente | `DOC-001` |
| Padre o encargado | `ENC-0001` |

Estas cuentas son solo para desarrollo y deben sustituirse antes de usar el sistema con información real.

## Pruebas

La suite usa SQLite en memoria para mantenerse aislada de la base de datos local:

```powershell
php artisan test
```

Las pruebas cubren el acceso por código, rechazo de contraseñas incorrectas y cuentas inactivas, cierre de sesión, bitácora y autorización de rutas por rol.
