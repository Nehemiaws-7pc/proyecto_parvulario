# Recuperación de contraseña

El enlace «¿Olvidaste tu contraseña?» solicita código, nombre y teléfono. La respuesta pública es idéntica para datos coincidentes y no coincidentes. Las solicitudes se limitan por dirección IP y solo una coincidencia con una cuenta activa de docente o encargado crea una solicitud pendiente; la contraseña vigente nunca se modifica al solicitarla.

Dirección y Administración consultan las solicitudes pendientes. Tras verificar la identidad por el procedimiento de la escuela, pueden restablecer una solicitud individual. El sistema genera una contraseña temporal criptográficamente segura, guarda solo su hash, elimina sesiones persistentes de la cuenta, marca `cambiar_password` y muestra el valor únicamente en la respuesta inmediata para entrega privada. La solicitud queda resuelta y la bitácora registra usuario operador, cuenta, solicitud y fecha, sin secretos.

Al entrar con la contraseña temporal, el usuario solo puede cambiarla por una contraseña personal de al menos 12 caracteres. La contraseña anterior deja de ser válida. Docentes y encargados reciben 403 en la bandeja y en el restablecimiento, incluso mediante URL directa.
