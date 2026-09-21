import {
    defineRailway,
    github,
    mysql,
    preserve,
    project,
    service,
} from "railway/iac";

export default defineRailway(() => {
    const database = mysql("MySQL");

    const web = service("web", {
        source: github("Nehemiaws-7pc/proyecto_parvulario", {
            branch: "feature/primera-etapa-autenticacion",
        }),
        build: {
            builder: "RAILPACK",
        },
        preDeploy:
            "php artisan config:clear && php artisan migrate --force && php artisan db:seed --force && php artisan config:cache && php artisan route:cache && php artisan view:cache",
        healthcheck: "/up",
        healthcheckTimeout: 300,
        env: {
            APP_KEY: preserve(),
            APP_URL: preserve(),
            APP_ENV: "production",
            APP_DEBUG: "false",
            APP_TIMEZONE: "America/Guatemala",
            DB_CONNECTION: "mysql",
            DB_URL: database.env.MYSQL_URL,
            LOG_CHANNEL: "stderr",
            LOG_STDERR_FORMATTER: "\\Monolog\\Formatter\\JsonFormatter",
            LOG_LEVEL: "info",
            SESSION_DRIVER: "database",
            CACHE_STORE: "database",
            QUEUE_CONNECTION: "sync",
            DEMO_DATA_ENABLED: preserve(),
            DEMO_RESET_PASSWORDS: preserve(),
            DEMO_USER_PASSWORD: preserve(),
            RAILPACK_NODE_NPM_INSTALL: "npm ci",
            RAILPACK_PHP_EXTENSIONS: "pdo_mysql",
            RAILPACK_SKIP_MIGRATIONS: "true",
        },
    });

    return project("escuelita-parvularia", {
        resources: [web, database],
    });
});
