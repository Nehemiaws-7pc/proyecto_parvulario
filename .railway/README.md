# Railway

La infraestructura define un servicio web Laravel y una base de datos MySQL.
Railpack detecta `artisan`, instala Composer y Node, ejecuta `npm run build` para
generar los recursos de Vite y publica `public/` con FrankenPHP.

Antes de aplicar la infraestructura:

1. Instala e inicia sesión en Railway CLI.
2. Ejecuta `railway link` para seleccionar el proyecto y el entorno.
3. Revisa y aplica la definición inicial.
4. Define `APP_KEY` como secreto en el servicio web. Genera el valor localmente
   con `php artisan key:generate --show`; no lo guardes en Git.
5. Define `APP_URL` con el dominio HTTPS generado por Railway.
6. Define `DEMO_USER_PASSWORD` como secreto con un valor de al menos 8
   caracteres; no lo guardes en Git.
7. Cambia `DEMO_DATA_ENABLED` a `true` únicamente en el entorno de demostración
   y vuelve a desplegar para que el predeploy cargue los datos.
8. Mantén `DEMO_RESET_PASSWORDS=false`. Si necesitas recuperar las ocho cuentas
   ficticias, cámbialo temporalmente a `true`, despliega una vez y vuelve a
   establecerlo en `false`.

Con los datos de demostración habilitados se crean de forma idempotente
`DIR-001`, `DOC-001` a `DOC-006` y `ENC-0001`. La ejecución normal conserva sus
contraseñas y estados actuales. El restablecimiento explícito usa el valor
secreto de `DEMO_USER_PASSWORD`, activa las cuentas y deja
`cambiar_password=false`.

Revisa y aplica la definición con:

```sh
railway config plan
railway config apply
```

El predeploy aplica migraciones, ejecuta seeders idempotentes y valida las
cachés de configuración, rutas y vistas. Railway detiene el despliegue si el
comando termina con error. `RAILPACK_SKIP_MIGRATIONS=true` evita que Railpack
repita las migraciones y los seeders durante el arranque del contenedor. La
limpieza inicial de configuración permite que un cambio temporal de
`DEMO_RESET_PASSWORDS` se lea desde las variables actuales del servicio.
