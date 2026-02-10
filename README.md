# CursoIngles

Sistema web para gestion de cursos de ingles con control por roles:
- `admin`: asigna estudiantes a horarios confirmados.
- `profesor`: crea/edita borradores y confirma horarios.
- `estudiante`: consulta clases asignadas.

## Stack

- Laravel 12
- Laravel Breeze (auth)
- Spatie Laravel Permission (roles/permisos)
- SQLite
- Tailwind CSS (UI cards moderna)

## Reglas implementadas

- Solo profesor confirma sus horarios.
- Solo admin asigna estudiantes.
- Asignacion permitida solo en horarios `confirmed`.
- Maximo de 8 estudiantes por horario/aula.
- Validacion de solapes:
  - profesor no puede tener cruces de horario.
  - aula no puede tener cruces de horario.
  - estudiante no puede ser asignado a dos clases cruzadas.

## Entidades

- `users` (persona + usuario simplificado)
- `roles`, `permissions` (Spatie)
- `courses`
- `course_topics`
- `classrooms`
- `schedule_slots`
- `enrollments`

## Arranque rapido

```bash
# Linux/macOS
cp .env.example .env

# Windows (PowerShell)
copy .env.example .env

php artisan key:generate
composer install
npm install
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

En otra terminal, para modo desarrollo frontend:

```bash
npm run dev
```

## Usuarios demo

Contrasena para todos: `password`

- Admin: `admin@cursoingles.test`
- Profesor 1: `profesor1@cursoingles.test`
- Profesor 2: `profesor2@cursoingles.test`
- Estudiante 1: `estudiante1@cursoingles.test`
- Estudiante 2: `estudiante2@cursoingles.test`

## Rutas principales

- Dashboard: `/dashboard`
- Profesor: `/profesor/horarios`
- Admin: `/admin/horarios`
- Admin usuarios: `/admin/usuarios`
- Estudiante: `/estudiante/horarios`
