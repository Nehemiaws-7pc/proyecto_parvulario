# Auditoría de accesos demo — 22 de septiembre de 2026

## Método y protección de datos

Se enviaron solicitudes HTTP reales a http://127.0.0.1:8000: GET del formulario, extracción del token CSRF, POST de credenciales configuradas, cookies independientes y GET del panel y rutas protegidas. Los diez inicios de sesión respondieron 302 hacia /panel. No se imprimieron contraseñas ni hashes.

La base MySQL local no se borró, migró, sembró ni reinició; tampoco se restablecieron contraseñas. Los accesos pueden generar los registros normales de sesión y bitácora. Las operaciones de escritura académica y los escenarios negativos se ejecutaron exclusivamente con SQLite :memory: y RefreshDatabase. No se realizó una inspección visual con navegador ni una publicación académica sobre MySQL local.

## Matriz local

M = Estudiantes (/estudiantes), Asistencia (/asistencia), Actividades (/actividades), Evaluaciones (/evaluaciones), Justificaciones (/justificaciones), Avisos y avances (/avisos). Las rutas de módulos indicadas respondieron 200; los rechazos indicados respondieron 403. Tener acceso al índice no concede acceso a todos sus registros ni permiso de edición.

| Código | Rol/función | Login | Módulos visibles y rutas permitidas | Alcance local | Rutas rechazadas |
|---|---|---|---|---|---|
| DIR-001 | Dirección | OK | M + Estructura (/estructura) | Todos los grupos | Ninguna de las rutas de consulta auditadas |
| ADM-001 | Administración | OK | M + Estructura | Todos los grupos | /panel/direccion, /panel/docencia, /panel/familia |
| DOC-001 | Titular | OK | M | Preparatoria A y Preprimaria 5 años A (histórico) | /estructura; asistencia de grupos ajenos; paneles de otros roles |
| DOC-002 | Titular | OK | M | Preparatoria B | /estructura; asistencia de grupos ajenos; paneles de otros roles |
| DOC-003 | Titular | OK | M | Preparatoria C | /estructura; asistencia de grupos ajenos; paneles de otros roles |
| DOC-004 | Titular | OK | M | Párvulos A y Kinder A | /estructura; asistencia de los otros cinco grupos locales; paneles de otros roles |
| DOC-005 | Titular | OK | M | Párvulos B | /estructura; asistencia de grupos ajenos; paneles de otros roles |
| DOC-006 | Educación Física | OK | M | Los seis grupos demo; actividades/notas de su área | /estructura; asistencia del grupo histórico; detalle de actividad titular; paneles de otros roles |
| DOC-007 | Educación Especial | OK | Estudiantes (ficha básica) y Avisos | Seis grupos demo; publicación de avances | /asistencia, /actividades, /evaluaciones, /justificaciones, /estructura y detalle de actividad |
| ENC-0001 | Encargado | OK | M | Únicamente estudiantes vinculados y resultados publicados | /estructura, /panel/direccion, /panel/administracion, /panel/docencia |

Todos acceden a /panel. Las páginas antiguas /panel/{rol} conservan sus permisos: Dirección abre las cuatro; Administración solo administracion; docentes solo docencia (incluida DOC-007); encargado solo familia. No son permisos sobre los módulos académicos.

Los seis grupos demo son Preparatoria A/B/C, Párvulos A/B y Kinder A. La base local contiene además Preprimaria 5 años A y ENC-0001 tiene 25 vínculos explícitos, no 24. Se conservaron esos datos. La base de pruebas recién sembrada tiene seis grupos y 24 estudiantes; DOC-004 consulta ocho, DOC-006 y DOC-007 consultan 24 y el encargado consulta los 24 vinculados.

## Escenarios automatizados

- Diez sesiones independientes, enlaces del panel, índices y URLs directas de los cuatro paneles por rol.
- Asistencia por fecha 2026-09-17 y grupo/sección: cuatro registros por grupo demo, sin mezclar fecha ni sección. Rechazo de grupos ajenos para docentes.
- DOC-006 crea actividades, registra cuatro notas, publica resultados y registra asistencia en cada uno de los seis grupos. Las evaluaciones descriptivas se limitan al área Educación Física.
- DOC-007 no puede usar URLs de detalle, edición o POST para modificar asistencia, actividades o evaluaciones. Solo recibe la ficha básica del estudiante, sin datos médicos ni domicilio.
- Dirección y Administración disponen del formulario de actividades y pueden registrar/publicar evaluaciones.
- El encargado no modifica notas; al retirar un vínculo únicamente en la base de pruebas, el estudiante desaparece de listados, asistencia diaria/mensual, resultados y avisos individuales. Las notas pendientes y borradores conservan la protección de las pruebas existentes.
- Publicación de anuncios de Dirección y docente titular, consulta por el encargado vinculado, rechazo de publicación a estudiantes ajenos y de destinos mixtos estudiante/grupo. Sin matrícula activa en el grupo, el encargado no ve sus anuncios.
- Las pruebas de AvisoAvanceTest cubren avances de Educación Especial, anuncios docentes, grupos no asignados y consulta de Dirección de anuncios de todas las docentes.

## Fallos reproducidos y correcciones

1. DOC-007 podía abrir justificaciones y acceder a detalles académicos aunque sus índices estaban ocultos: se endurecieron políticas de consulta/escritura.
2. DOC-007 recibía el expediente privado del estudiante: se añadió una ficha básica separada.
3. DOC-006 podía operar sobre actividades titulares antiguas sin tipo y acceder a evaluaciones de otras áreas: se valida asignación activa y área en consultas, políticas y escrituras.
4. Dirección/Administración no disponían del formulario de actividades ni del flujo completo de registro/publicación de evaluaciones: se habilitaron sus permisos ya previstos.
5. La consulta auxiliar de asistencia para familias incluía asignaciones no vinculadas: se filtró también esa colección.
6. Los anuncios de grupo admitían matrículas históricas como vínculo y destinos simultáneos estudiante/grupo: se exige matrícula activa para anuncios de grupo y se rechazan destinos ambiguos.

## Verificaciones

- php artisan test: 67 pruebas correctas, 1401 aserciones.
- php vendor/bin/pint --test: correcto.
- git diff --check: correcto.
- Sin push ni despliegue. Sin cambios en seeders, credenciales ni estructura de la base local.
