# Gestión de encargados

Desde Estudiantes → expediente → «Buscar, crear o desvincular encargados», Dirección y Administración pueden:

- Buscar por código, nombre, correo o teléfono y vincular una cuenta existente.
- Crear una cuenta activa del rol Encargado si no existe y vincularla en la misma transacción.
- Desvincular únicamente al estudiante seleccionado, conservando la cuenta y los demás estudiantes.

Se reutiliza la relación muchos-a-muchos existente; no hay migraciones ni cambios a los datos locales. Vincular repetidamente no duplica relaciones ni sobrescribe datos de contacto. No se permite reemplazar la identidad de un contacto compartido desde su formulario de edición.

El código se normaliza a mayúsculas y debe ser único. También se rechazan coincidencias de correo, o de nombre y teléfono. La búsqueda previa sigue siendo importante: sin documento identificativo no se puede identificar automáticamente a una persona que proporcione datos distintos. No se fusionan ni eliminan cuentas existentes.

La contraseña inicial debe tener al menos 12 caracteres. Se almacena mediante Hash::make, no se registra en bitácora ni se vuelve a mostrar. Administración debe entregarla por un medio privado. La cuenta nueva queda con cambiar_password=true: al iniciar sesión solo puede cambiar su contraseña o cerrar sesión hasta completar el cambio. La protección se aplica también a cuentas existentes que ya tengan esa marca, sin restablecer sus contraseñas.

La bitácora conserva el usuario autor, fecha, acción y los identificadores de estudiante, encargado y cuenta. Desvincular revoca las consultas del estudiante en el servidor, no solo sus enlaces de navegación. Los permisos se comprueban en cada solicitud.

Pruebas: GuardianManagementTest usa la base de pruebas y cubre creación, duplicados, búsqueda, vínculos múltiples, conservación de datos, cambio obligatorio de contraseña, revocación de acceso y rechazo de gestión por docentes/familias mediante URLs directas. Las pruebas previas de permisos preparan usuarios que ya completaron el cambio inicial; no se deshabilita el middleware.
