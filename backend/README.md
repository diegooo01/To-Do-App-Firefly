# Backend — API de Gestión de Tareas

API REST en Laravel 12 con autenticación JWT (`tymon/jwt-auth`) y base de datos SQLite.

## Requisitos

- PHP 8.2 o superior
- Composer 2.x
- Extensiones de PHP: `sodium`, `zip`, `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `curl`, `fileinfo`

Verificar las extensiones activas:

```bash
php -m
```

Si alguna falta, habilitarla en el `php.ini` (localizable con `php --ini`) quitando el `;` de la línea `extension=...` correspondiente, y reiniciar la terminal.

Dos de ellas merecen mención explícita:

- **`sodium`** es obligatoria. `lcobucci/jwt`, dependencia de `tymon/jwt-auth`, la requiere para las operaciones criptográficas. Sin ella, `composer install` falla antes de instalar nada.
- **`zip`** evita que Composer clone cada dependencia con Git en lugar de descargar los paquetes comprimidos. Sin ella la instalación puede superar los veinte minutos y agotar el timeout.

## Instalación

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
```

La base de datos SQLite se crea automáticamente al migrar.

### Usuarios de prueba

El seeder crea dos usuarios:

| Email | Contraseña | Tareas |
|---|---|---|
| `diego@test.com` | `password123` | 8 tareas cubriendo los tres estados y las tres prioridades |
| `otro@test.com` | `password123` | 5 tareas generadas aleatoriamente |

El segundo usuario existe para verificar el aislamiento de datos: al iniciar sesión como `diego@test.com` deben visualizarse únicamente sus 8 tareas.

## Variables de entorno

| Variable | Descripción | Valor |
|---|---|---|
| `APP_KEY` | Clave de la aplicación. La genera `php artisan key:generate` | *(autogenerada)* |
| `JWT_SECRET` | Clave con la que se firman los tokens. La genera `php artisan jwt:secret` | *(autogenerada)* |
| `DB_CONNECTION` | Motor de base de datos | `sqlite` |
| `FRONTEND_URL` | Origen permitido por CORS | `http://localhost:8001` |

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

## Pruebas manuales del API

Con el servidor en ejecución y la base de datos poblada. En Windows debe usarse `curl.exe`, no `curl` (en PowerShell es un alias de `Invoke-WebRequest`, con otra sintaxis).

La cabecera `Accept: application/json` no es opcional: sin ella, ante un fallo de autenticación Laravel intenta redirigir a una ruta `login` inexistente en un API en lugar de devolver un 401.

### Autenticación

```bash
# Login — la respuesta incluye el token
curl.exe -X POST http://localhost:8000/api/login -H "Accept: application/json" -d "email=diego@test.com" -d "password=password123"
```

Guardar el token para no repetirlo:

```powershell
$t = "PEGAR_AQUI_EL_TOKEN"
```

```bash
# Usuario autenticado
curl.exe http://localhost:8000/api/me -H "Accept: application/json" -H "Authorization: Bearer $t"

# Sin token: 401
curl.exe -i http://localhost:8000/api/me -H "Accept: application/json"
```

### CRUD y filtros

```bash
# Listar
curl.exe "http://localhost:8000/api/tasks" -H "Accept: application/json" -H "Authorization: Bearer $t"

# Filtrar (individual y combinado)
curl.exe "http://localhost:8000/api/tasks?status=pending" -H "Accept: application/json" -H "Authorization: Bearer $t"
curl.exe "http://localhost:8000/api/tasks?priority=high" -H "Accept: application/json" -H "Authorization: Bearer $t"
curl.exe "http://localhost:8000/api/tasks?search=README" -H "Accept: application/json" -H "Authorization: Bearer $t"
curl.exe "http://localhost:8000/api/tasks?status=pending&priority=high" -H "Accept: application/json" -H "Authorization: Bearer $t"

# Crear (201)
curl.exe -X POST http://localhost:8000/api/tasks -H "Accept: application/json" -H "Authorization: Bearer $t" -d "title=Tarea de prueba" -d "priority=high" -d "due_date=2026-08-01"

# Editar
curl.exe -X PUT "http://localhost:8000/api/tasks/5" -H "Accept: application/json" -H "Authorization: Bearer $t" -d "title=Titulo actualizado"

# Cambiar estado
curl.exe -X PATCH "http://localhost:8000/api/tasks/5/status" -H "Accept: application/json" -H "Authorization: Bearer $t" -d "status=done"

# Eliminar (204, sin cuerpo)
curl.exe -i -X DELETE "http://localhost:8000/api/tasks/5" -H "Accept: application/json" -H "Authorization: Bearer $t"

# Dashboard
curl.exe "http://localhost:8000/api/dashboard" -H "Accept: application/json" -H "Authorization: Bearer $t"
```

El dashboard de `diego@test.com` devuelve `{"total":8,"pending":4,"in_progress":2,"done":2}`.

### Verificar la regla de negocio

Marcar una tarea como completada e intentar editarla:

```bash
curl.exe -X PATCH "http://localhost:8000/api/tasks/3/status" -H "Accept: application/json" -H "Authorization: Bearer $t" -d "status=done"

curl.exe -i -X PUT "http://localhost:8000/api/tasks/3" -H "Accept: application/json" -H "Authorization: Bearer $t" -d "title=Nuevo titulo"
```

La segunda llamada responde **422** con el mensaje "Una tarea completada no puede ser editada." Cambiar su estado sigue permitido, de modo que la tarea puede reabrirse.

### Verificar el aislamiento entre usuarios

Iniciar sesión con el segundo usuario y guardar su token en `$t2`:

```bash
curl.exe -X POST http://localhost:8000/api/login -H "Accept: application/json" -d "email=otro@test.com" -d "password=password123"
```

```bash
# Su listado no contiene ninguna tarea de diego@test.com
curl.exe "http://localhost:8000/api/tasks" -H "Accept: application/json" -H "Authorization: Bearer $t2"

# Acceder a una tarea ajena por ID: 403
curl.exe -i "http://localhost:8000/api/tasks/1" -H "Accept: application/json" -H "Authorization: Bearer $t2"

# Editarla o eliminarla: 403
curl.exe -i -X PUT "http://localhost:8000/api/tasks/1" -H "Accept: application/json" -H "Authorization: Bearer $t2" -d "title=Modificada"
curl.exe -i -X DELETE "http://localhost:8000/api/tasks/1" -H "Accept: application/json" -H "Authorization: Bearer $t2"
```

Son dos mecanismos protegiendo dos vectores distintos: el listado nunca incluye tareas ajenas porque la consulta parte de la relación del usuario; el acceso directo por identificador lo bloquea `TaskPolicy`.

### Verificar el cierre de sesión

```bash
curl.exe -X POST http://localhost:8000/api/logout -H "Accept: application/json" -H "Authorization: Bearer $t"

curl.exe -i http://localhost:8000/api/me -H "Accept: application/json" -H "Authorization: Bearer $t"
```

La segunda llamada responde **401**. El token no ha expirado ni ha dejado de ser criptográficamente válido: está en la *blacklist*, que el middleware consulta en cada petición.

> Los ejemplos envían los datos como `form-data`, que Laravel interpreta sin problema. Enviar JSON desde PowerShell exige escapar comillas y resulta propenso a errores; el API acepta ambos formatos y un cliente real (Axios, Postman) envía JSON sin dificultad.

## Estructura relevante

```
app/
├── Enums/
│   ├── TaskStatus.php          Valores válidos de estado
│   └── TaskPriority.php        Valores válidos de prioridad
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php
│   │   └── TaskController.php
│   └── Requests/
│       ├── Auth/               Validación de registro y login
│       └── Task/               Validación, autorización y regla de negocio
├── Models/
│   ├── Task.php                Casts a enum y query scopes de filtrado
│   └── User.php                Implementa JWTSubject
└── Policies/
    └── TaskPolicy.php          Verificación de propiedad
```

## Decisiones Técnicas

### 1. La regla "una tarea completada no puede editarse" vive en el Form Request

Se implementó en `UpdateTaskRequest::withValidator()` y no como un condicional en el controlador. Así el método `update()` queda reducido a una línea y toda la lógica que responde a "¿es válida esta petición?" se concentra en un único archivo.

Se devuelve **422** y no **403** de forma deliberada: un 403 comunica "no tienes permiso sobre este recurso", mientras que un 422 comunica "la operación no es válida en el estado actual del recurso". La tarea sí pertenece al usuario; lo que ocurre es que está cerrada.

Como consecuencia intencional, `PATCH /tasks/{id}/status` **no** aplica esta restricción. Bloquearlo también dejaría cualquier tarea `done` congelada de forma permanente, sin posibilidad de reabrirla. Se interpretó que "editar" se refiere al contenido de la tarea, no a su transición de estado.

### 2. El aislamiento por usuario se resuelve en dos capas

Son dos vectores de ataque distintos y cada uno se atiende de forma diferente:

- **Listado y creación:** las consultas parten siempre de `$request->user()->tasks()`, nunca de `Task::query()`, lo que garantiza el `WHERE user_id = ?` a nivel de SQL. Además, `user_id` **no** figura en el `$fillable` del modelo, por lo que un cliente no puede inyectarlo en el cuerpo de la petición para crear tareas a nombre de otro usuario (protección frente a mass assignment).
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

Los valores válidos residen en `App\Enums\TaskStatus` y `App\Enums\TaskPriority`; la columna en base de datos es un `string`. SQLite no soporta `ENUM` nativo y en MySQL modificarlo exige un `ALTER TABLE`.

Además, el mismo enum se emplea a la vez en el cast del modelo (`$task->status` devuelve un objeto tipado), en la validación (`Rule::enum(...)`) y en el factory (`randomElement(TaskStatus::cases())`), por lo que la lista de valores admitidos existe en un solo lugar: añadir un estado nuevo es una única línea.

### 6. SQLite como motor de base de datos

Permite clonar y ejecutar el proyecto sin levantar ningún servicio externo. El código no depende de particularidades de SQLite: basta cambiar `DB_CONNECTION` en el `.env` para migrar a MySQL o PostgreSQL.

### 7. CORS admite tanto `localhost` como `127.0.0.1`

Ambos apuntan a la misma máquina, pero el navegador los trata como orígenes distintos: la comparación del header `Origin` es una coincidencia exacta de cadena. Como no puede preverse por cuál de las dos URLs accederá quien ejecute el proyecto, se permiten ambas explícitamente en `config/cors.php`.

`supports_credentials` se mantiene en `false` porque la autenticación viaja en la cabecera `Authorization`, no en cookies.

### 8. Se eliminó Laravel Sanctum

`php artisan install:api` instala Sanctum por defecto. Al usar JWT como mecanismo de autenticación, se desinstaló el paquete para no mantener dos sistemas de autenticación coexistiendo en el proyecto.

## Notas

- El cierre de sesión con JWT se implementa mediante una *blacklist*: el token no puede invalidarse criptográficamente antes de su expiración, por lo que su identificador (`jti`) se almacena y el middleware lo rechaza en peticiones posteriores. Es un compromiso consciente entre el carácter *stateless* de JWT y la capacidad de revocar sesiones.
- En desarrollo, `APP_DEBUG=true` incluye la traza completa en las respuestas de error. En producción debe establecerse en `false`.
