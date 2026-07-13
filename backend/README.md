# Backend — API de Gestión de Tareas

API REST en Laravel 12 con autenticación JWT (`tymon/jwt-auth`).

## Requisitos

- PHP 8.2+
- Composer 2.x
- Extensiones PHP: `pdo_sqlite`, `mbstring`, `openssl`, `zip`, `curl`

## Instalación

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate
```

La base de datos SQLite se crea automáticamente al migrar.

## Variables de entorno

| Variable | Descripción |
|---|---|
| `APP_KEY` | Clave de la aplicación. La genera `key:generate` |
| `JWT_SECRET` | Clave de firma de los tokens. La genera `jwt:secret` |
| `DB_CONNECTION` | Motor de base de datos (`sqlite`) |
| `FRONTEND_URL` | Origen permitido por CORS (`http://localhost:8001`) |

## Ejecución

```bash
php artisan serve --port=8000
```

API disponible en `http://localhost:8000/api`.

## Endpoints

El token se envía en la cabecera `Authorization: Bearer {token}`.

### Autenticación

| Método | Ruta | Descripción | Token |
|---|---|---|---|
| POST | `/api/register` | Registro | No |
| POST | `/api/login` | Inicio de sesión | No |
| GET | `/api/me` | Usuario autenticado | Sí |
| POST | `/api/logout` | Cierre de sesión | Sí |

### Tareas

Todos requieren token. Cada usuario opera únicamente sobre sus propias tareas.

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/tasks` | Listar tareas |
| POST | `/api/tasks` | Crear tarea |
| GET | `/api/tasks/{id}` | Consultar una tarea |
| PUT | `/api/tasks/{id}` | Editar tarea |
| PATCH | `/api/tasks/{id}/status` | Cambiar estado |
| DELETE | `/api/tasks/{id}` | Eliminar tarea |
| GET | `/api/dashboard` | Métricas agregadas |

### Filtros

Combinables entre sí:

```
GET /api/tasks?status=pending
GET /api/tasks?priority=high
GET /api/tasks?search=laravel
GET /api/tasks?status=pending&priority=high
```

- `status`: `pending`, `in_progress`, `done`
- `priority`: `low`, `medium`, `high`
- `search`: coincidencia parcial sobre el título

### Códigos de respuesta

| Código | Situación |
|---|---|
| 200 | Operación exitosa |
| 201 | Recurso creado |
| 204 | Recurso eliminado |
| 401 | Token ausente, inválido, expirado o revocado |
| 403 | La tarea pertenece a otro usuario |
| 422 | Validación fallida o regla de negocio incumplida |

## Decisiones Técnicas

### 1. La regla "una tarea completada no puede editarse" vive en el Form Request

Se implementó en `UpdateTaskRequest::withValidator()` y no como un condicional en el controlador. Así el método `update()` queda reducido a una línea y toda la lógica que responde a "¿es válida esta petición?" se concentra en un único archivo.

Se devuelve **422** y no **403** de forma deliberada: un 403 comunica "no tienes permiso sobre este recurso", mientras que un 422 comunica "la operación no es válida en el estado actual del recurso". La tarea sí pertenece al usuario; lo que ocurre es que está cerrada.

Como consecuencia intencional, `PATCH /tasks/{id}/status` **no** aplica esta restricción. Bloquearlo también dejaría cualquier tarea `done` congelada de forma permanente, sin posibilidad de reabrirla. Se interpretó que "editar" se refiere al contenido de la tarea, no a su transición de estado.

### 2. El aislamiento por usuario se resuelve en dos capas

Son dos vectores distintos y cada uno se atiende de forma diferente:

- **Listado y creación:** las consultas parten siempre de `$request->user()->tasks()`, nunca de `Task::query()`, lo que garantiza el `WHERE user_id = ?` a nivel de SQL. Además, `user_id` **no** figura en el `$fillable` del modelo, por lo que un cliente no puede inyectarlo en el cuerpo de la petición para crear tareas a nombre de otro usuario (mass assignment).
- **Acceso por identificador** (`show`, `update`, `destroy`): el ID llega desde la URL, por lo que se aplica `TaskPolicy`, que verifica la propiedad del recurso y devuelve 403 si no coincide.

Repetir `where('user_id', auth()->id())` en cada método del controlador también funcionaría, pero es frágil: basta omitirlo una vez para abrir una fuga de datos entre usuarios.

### 3. Los filtros son query scopes, no condicionales en el controlador

Cada filtro es un scope del modelo (`scopeStatus`, `scopePriority`, `scopeSearch`) construido sobre `when()`, que se autoanula cuando el parámetro no está presente. Esto permite encadenarlos siempre, sin ramificaciones:

```php
$request->user()->tasks()
    ->status($request->query('status'))
    ->priority($request->query('priority'))
    ->search($request->query('search'))
    ->latest()
    ->get();
```

Incorporar un filtro nuevo consiste en añadir un scope y una línea.

### 4. El dashboard resuelve las cuatro métricas en una sola consulta

La implementación directa sería un `count()` por métrica: cuatro viajes a la base de datos. En su lugar se ejecuta un único `GROUP BY status` y la agregación final se compone en PHP.

### 5. `status` y `priority` como enums de PHP, no como `ENUM` de base de datos

Los valores válidos residen en `App\Enums\TaskStatus` y `App\Enums\TaskPriority`; la columna en base de datos es un `string`. SQLite no soporta `ENUM` nativo y en MySQL modificarlo exige un `ALTER TABLE`. Además, el mismo enum se emplea a la vez en el cast del modelo (`$task->status` devuelve un objeto tipado) y en la validación (`Rule::enum(...)`), por lo que la lista de valores admitidos existe en un solo lugar: añadir un estado nuevo es una única línea.

### 6. SQLite como motor de base de datos

Permite clonar y ejecutar el proyecto sin levantar ningún servicio externo. El código no depende de particularidades de SQLite: basta cambiar `DB_CONNECTION` en el `.env` para migrar a MySQL o PostgreSQL.
