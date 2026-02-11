# Guia Tecnica de Proyecto - CursoIngles

Fecha de elaboracion: 2026-02-10  
Estado de pruebas al momento del analisis: `76 passed (386 assertions)` con `php artisan test`.

## 1. Resumen Ejecutivo
`CursoIngles` es una aplicacion Laravel para gestionar cursos de ingles con control de acceso por roles.

Roles operativos:
- `admin`: administra usuarios (profesores/estudiantes), asigna estudiantes a horarios confirmados y cierra horarios.
- `profesor`: crea y edita borradores de horario, luego confirma horarios.
- `estudiante`: consulta sus clases asignadas.

Reglas funcionales centrales:
- Lunes a sabado (`day_of_week` 1..6).
- Franja operativa `08:00` a `20:00`.
- Duracion maxima en escenarios semanales aleatorios: 3 horas.
- Maximo efectivo por aula/horario: 8 estudiantes.
- Sin cruces de horario para profesor o aula.
- Un curso no puede quedar asignado a dos profesores distintos (en slots activos).
- Asignacion de estudiantes solo en horarios `confirmed`.

## 2. Stack y Dependencias
Backend:
- PHP `^8.2`
- Laravel `^12`
- Spatie Laravel Permission `^6.24`

Frontend:
- Blade + Tailwind CSS + Alpine.js
- Vite

Dependencias clave:
- `composer.json`
- `package.json`

Configuracion base:
- `.env.example` usa MySQL por defecto.
- Locale de app: `es`.

## 3. Arquitectura General
Patron principal:
- MVC con Laravel.

Capas:
- `app/Models`: entidades de dominio.
- `app/Http/Controllers`: orquestacion de casos de uso web.
- `app/Http/Requests`: validaciones y autorizacion por request.
- `resources/views`: UI Blade (cards, tabs, formularios).
- `database/migrations`: esquema relacional.
- `database/seeders`: datos iniciales/demostracion/semana aleatoria.
- `tests`: pruebas unitarias y feature.

Control de acceso:
- Middleware de Spatie registrado en `bootstrap/app.php`:
  - `role`
  - `permission`
  - `role_or_permission`

## 4. Modelo de Dominio
Entidades principales:
- `User`
- `Course`
- `CourseTopic`
- `Classroom`
- `ScheduleSlot`
- `Enrollment`
- Tablas de Spatie (`roles`, `permissions`, pivotes)

Relaciones clave:
- `Course` 1:N `CourseTopic`
- `CourseTopic` 1:N `ScheduleSlot`
- `User` (profesor) 1:N `ScheduleSlot` via `teacher_id`
- `Classroom` 1:N `ScheduleSlot`
- `ScheduleSlot` 1:N `Enrollment`
- `User` (estudiante) 1:N `Enrollment` via `student_id`
- `User` N:M `ScheduleSlot` (estudiantes asignados) via `enrollments`

Estados de horario (`ScheduleSlot`):
- `draft`
- `confirmed`
- `closed`

Dias habilitados (`ScheduleSlot::DAY_LABELS`):
- 1 Lunes
- 2 Martes
- 3 Miercoles
- 4 Jueves
- 5 Viernes
- 6 Sabado

## 5. Esquema de Base de Datos
Migraciones de negocio:
- `database/migrations/2026_02_10_002030_create_courses_table.php`
- `database/migrations/2026_02_10_002031_create_classrooms_table.php`
- `database/migrations/2026_02_10_002040_create_course_topics_table.php`
- `database/migrations/2026_02_10_002050_create_schedule_slots_table.php`
- `database/migrations/2026_02_10_002060_create_enrollments_table.php`

Restricciones relevantes:
- `courses.code` unico.
- `classrooms.code` unico.
- `course_topics` unico por (`course_id`, `title`).
- `enrollments` unico por (`schedule_slot_id`, `student_id`).
- Indices de ventana para solapes en `schedule_slots`:
  - profesor + dia + hora inicio/fin
  - aula + dia + hora inicio/fin

## 6. Rutas y Modulos Funcionales
Archivo principal: `routes/web.php`.

Publicas:
- `GET /` (welcome)

Autenticadas:
- `GET /dashboard` (por rol, una sola vista con datos filtrados)
- Perfil: `GET/PATCH/DELETE /profile`

Profesor (`role:profesor`, prefijo `/profesor`):
- `GET /horarios` listar y formulario
- `POST /horarios` crear borrador
- `PUT /horarios/{scheduleSlot}` actualizar borrador
- `DELETE /horarios/{scheduleSlot}` eliminar borrador
- `PATCH /horarios/{scheduleSlot}/confirmar` confirmar horario

Admin (`role:admin`, prefijo `/admin`):
- `GET /horarios` gestion de asignaciones
- `POST /horarios/{scheduleSlot}/enrollments` asignar estudiante
- `DELETE /horarios/{scheduleSlot}/enrollments/{enrollment}` quitar asignacion
- `PATCH /horarios/{scheduleSlot}/close` cerrar horario
- `GET /usuarios` listado de usuarios gestionables
- `POST /usuarios` crear profesor/estudiante
- `PUT /usuarios/{user}` editar profesor/estudiante
- `DELETE /usuarios/{user}` eliminar profesor/estudiante

Estudiante (`role:estudiante`, prefijo `/estudiante`):
- `GET /horarios` ver clases asignadas

Auth Breeze:
- `routes/auth.php` (login, registro, reset password, verify email, logout)

## 7. Reglas de Negocio Implementadas
### 7.1 Horarios docentes
Origen:
- `app/Http/Requests/Teacher/StoreScheduleSlotRequest.php`
- `app/Http/Requests/Teacher/UpdateScheduleSlotRequest.php`
- `app/Http/Controllers/Teacher/ScheduleSlotController.php`

Reglas:
- Solo rol `profesor` puede crear/editar.
- `day_of_week` entre 1 y 6.
- `starts_at >= 08:00` y `< 20:00`.
- `ends_at > starts_at` y `<= 20:00`.
- Solo se editan/eliminan slots `draft` y del propio profesor.
- Confirmacion solo para slots propios en `draft`.
- El aula debe estar activa.
- El tema y su curso deben estar activos.
- Sin solape de horario para:
  - mismo profesor
  - misma aula
- Un mismo curso no puede quedar asignado a dos profesores distintos mientras existan slots activos (`!= closed`).

### 7.2 Asignacion de estudiantes
Origen:
- `app/Http/Controllers/Admin/ScheduleSlotController.php`
- `app/Http/Requests/Admin/StoreEnrollmentRequest.php`

Reglas:
- Solo `admin`.
- Solo slots `confirmed`.
- El usuario a asignar debe tener rol `estudiante`.
- No duplicar asignacion en el mismo slot.
- Capacidad maxima efectiva: `min(capacity_aula, 8)`.
- El estudiante no puede tener solape con otro slot `confirmed`.

### 7.3 Gestion de usuarios
Origen:
- `app/Http/Controllers/Admin/UserManagementController.php`
- `app/Http/Requests/Admin/StoreManagedUserRequest.php`
- `app/Http/Requests/Admin/UpdateManagedUserRequest.php`

Reglas:
- Solo `admin`.
- Solo se gestionan roles `profesor` y `estudiante`.
- No se permite modificar/eliminar usuarios admin desde este modulo.
- Password obligatorio al crear, opcional al editar.

## 8. Dashboard Actual (Estado Funcional)
Vista:
- `resources/views/dashboard.blade.php`

Controlador:
- `app/Http/Controllers/DashboardController.php`

Comportamiento:
- Muestra una unica seccion: `Vista rapida de horarios`.
- Presenta tabs por dia de semana actual (lunes a sabado).
- Al cambiar de tab, muestra solo cursos de ese dia.
- Cada card muestra:
  - curso
  - tema
  - estado
  - horario
  - aula
  - profesor (excepto vista de profesor en esa tarjeta, segun condicion actual)
  - cupos inscritos/capacidad

Notas tecnicas:
- El controlador aun calcula `stats` y `slots` aunque actualmente no se renderizan en la vista final.
- Esto no rompe funcionalidad, pero es un punto de limpieza futura.

## 9. UI/UX y Frontend
Layout base:
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/navigation.blade.php`

Caracteristicas:
- Diseno responsive con breakpoints (`sm`, `md`, `lg`).
- Tema oscuro soportado:
  - `resources/views/components/theme-init-script.blade.php`
  - `resources/views/components/theme-toggle.blade.php`
- Tarjetas de horario con efecto visual:
  - clase `schedule-card` en `resources/css/app.css`

Fuentes:
- Sora y Space Grotesk (Google Fonts).

## 10. Seeders y Datos Iniciales
Orden de ejecucion:
- `database/seeders/DatabaseSeeder.php`
  - `RolesAndPermissionsSeeder`
  - `DemoDataSeeder`
  - `WeeklyRandomScheduleSeeder`

### 10.1 RolesAndPermissionsSeeder
Define:
- Roles: `admin`, `profesor`, `estudiante`
- Permisos base de ejemplo y sincronizacion por rol

### 10.2 DemoDataSeeder
Objetivo:
- Datos demostrativos persistentes.

Incluye:
- admin, 6 profesores y 25 estudiantes aleatorios.
- cursos y temas base.
- maximo de 4 aulas activas (`A1`, `A2`, `B1`, `B2`).
- horarios confirmados/draft/closed.
- asignaciones de estudiantes.

### 10.3 WeeklyRandomScheduleSeeder
Objetivo:
- Generar dinamicamente una semana de cursos.

Reglas generadas:
- minimo 20 horarios semanales.
- lunes a sabado.
- 08:00-20:00.
- duraciones de 60, 90, 120, 150 o 180 min.
- sin cruces de aula/profesor.
- incluye estados `draft`, `confirmed` y `closed`.
- genera enrollments aleatorios.

Mecanismo:
- etiqueta slots con `notes` prefijo `Seeder semanal aleatorio:`.
- elimina slots previos de ese tipo antes de regenerar.

## 11. Pruebas y Cobertura
Suites:
- Unit: `tests/Unit`
- Feature: `tests/Feature`

Bloques cubiertos:
- Modelos y relaciones.
- FormRequests (autorizacion y reglas).
- Controladores custom (admin/teacher/student/dashboard).
- Seeders (incluye validaciones de reglas de generacion semanal).
- Flujos feature de auth, admin y profesor.

Ejecucion:
- `php artisan test`

Resultado del analisis:
- `76 passed (386 assertions)`.

## 12. Operacion y Comandos Utiles
Instalacion inicial:
1. `composer install`
2. `npm install`
3. `copy .env.example .env` (PowerShell) o copiar manualmente
4. `php artisan key:generate`
5. `php artisan migrate:fresh --seed`
6. `npm run build`
7. `php artisan serve`

Desarrollo:
- `npm run dev` (hot reload frontend)
- `php artisan serve`

Pruebas:
- `php artisan test`
- `php artisan test --testsuite=Unit`
- `php artisan test --testsuite=Feature`

Regenerar solo seeder semanal:
- `php artisan db:seed --class=WeeklyRandomScheduleSeeder`

## 13. Mapa de Archivos Clave
Dominio:
- `app/Models/ScheduleSlot.php`
- `app/Models/Enrollment.php`
- `app/Models/User.php`

Casos de uso:
- `app/Http/Controllers/Teacher/ScheduleSlotController.php`
- `app/Http/Controllers/Admin/ScheduleSlotController.php`
- `app/Http/Controllers/Admin/UserManagementController.php`
- `app/Http/Controllers/DashboardController.php`

Validacion:
- `app/Http/Requests/Teacher/StoreScheduleSlotRequest.php`
- `app/Http/Requests/Teacher/UpdateScheduleSlotRequest.php`
- `app/Http/Requests/Admin/StoreEnrollmentRequest.php`
- `app/Http/Requests/Admin/StoreManagedUserRequest.php`
- `app/Http/Requests/Admin/UpdateManagedUserRequest.php`

UI:
- `resources/views/dashboard.blade.php`
- `resources/views/teacher/schedule-slots/index.blade.php`
- `resources/views/admin/schedule-slots/index.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/student/schedule-slots/index.blade.php`

Datos:
- `database/migrations/*_create_*`
- `database/seeders/*.php`

## 14. Checklist para Cambios Futuros
Si agregas/modificas reglas de negocio:
1. Ajustar `FormRequest` correspondiente.
2. Ajustar validaciones de controlador si aplica (solapes, estados, etc.).
3. Actualizar o crear tests unitarios/feature.
4. Revisar seeders para coherencia de datos demo.
5. Revisar cards/tabs de dashboard si impacta visualizacion.

Si agregas un nuevo rol:
1. Actualizar `RolesAndPermissionsSeeder`.
2. Definir middleware/rutas en `routes/web.php`.
3. Ajustar navegacion en `resources/views/layouts/navigation.blade.php`.
4. Extender reglas de autorizacion en `FormRequest` y controladores.
5. Anadir tests de autorizacion/flujo.

Si cambias capacidad por aula:
1. Revisar `Classroom::effectiveCapacity()`.
2. Revisar capacidad en `ScheduleSlot::capacity`.
3. Revisar validacion de asignacion en `Admin/ScheduleSlotController`.
4. Actualizar pruebas de capacidad.

## 15. Riesgos y Deuda Tecnica Detectada
- El dashboard controller calcula `stats`/`slots` no usados en la vista actual.
- Reglas de permisos Spatie existen, pero la aplicacion se apoya principalmente en middleware de rol; conviene decidir estrategia unica (roles vs permisos granulares).
- Algunos textos UI y reglas de negocio viven en controladores; podria extraerse a servicios de dominio si crece complejidad.

## 16. Glosario Rapido
- `slot`: bloque de horario de un curso/tema con profesor y aula.
- `draft`: horario borrador, aun no asignable por admin.
- `confirmed`: horario confirmado, asignable a estudiantes.
- `closed`: horario cerrado, historico/no operativo para nuevas asignaciones.
- `enrollment`: asignacion de un estudiante a un slot.



