# Frontend — Gestión de Tareas

SPA construida con Laravel 12, Inertia.js, Vue 3, TailwindCSS y Pinia. Consume exclusivamente el API del backend mediante Axios.

## Requisitos

- PHP 8.2 o superior
- Composer 2.x
- Node.js 18 o superior y npm
- El backend debe estar en ejecución en `http://localhost:8000`

**Nota para Windows:** si PowerShell rechaza `npm` con un error de política de ejecución (`npm.ps1 ... la ejecución de scripts está deshabilitada en este sistema`), habilitarla con:

```powershell
Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned
```

## Instalación

```bash
cd frontend
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Este proyecto no requiere base de datos ni migraciones.

## Variables de entorno

| Variable | Descripción | Valor |
|---|---|---|
| `VITE_API_URL` | URL base del API del backend | `http://localhost:8000/api` |
| `SESSION_DRIVER` | Almacenamiento de sesión | `file` |
| `CACHE_STORE` | Almacenamiento de caché | `file` |

El prefijo `VITE_` es obligatorio: Vite solo expone al navegador las variables que lo llevan, y lo hace en tiempo de compilación. Tras modificar `VITE_API_URL` hay que **reiniciar `npm run dev`**; recargar el navegador no basta.

Si `VITE_API_URL` no está definida, Axios resuelve las rutas como relativas al propio host y las peticiones de login llegan al frontend en lugar del backend, devolviendo un **405 Method Not Allowed**.

## Ejecución

Requiere dos procesos simultáneos:

```bash
# Terminal 1 — servidor de Inertia
php artisan serve --port=8001

# Terminal 2 — compilación de Vue y Tailwind
npm run dev
```

Aplicación disponible en `http://localhost:8001`.

Credenciales de prueba (creadas por el seeder del backend):

| Email | Contraseña |
|---|---|
| `diego@test.com` | `password123` |

## Pantallas

| Ruta | Descripción | Protegida |
|---|---|---|
| `/login` | Formulario de email y contraseña | No |
| `/dashboard` | Métricas: total, pendientes, en progreso, completadas | Sí |
| `/tasks` | Listado con filtros por estado, prioridad y búsqueda por título | Sí |

El navbar muestra el nombre del usuario autenticado (obtenido desde `GET /api/me`) e incluye el botón de cierre de sesión.

## Estructura

```
resources/js/
├── app.js                      Punto de entrada: Inertia + Pinia
├── lib/
│   └── api.js                  Cliente Axios e interceptores
├── stores/
│   ├── auth.js                 Sesión, token y usuario
│   └── tasks.js                Tareas y métricas
├── composables/
│   └── useAuthGuard.js         Protección de rutas en cliente
├── Layouts/
│   └── AppLayout.vue           Navbar y contenedor
└── Pages/
    ├── Login.vue
    ├── Dashboard.vue
    └── Tasks.vue
```

Los nombres de `Pages/` y `Layouts/` son sensibles a mayúsculas: Vite resuelve los componentes contra la cadena literal del glob `./Pages/**/*.vue`, aunque el sistema de archivos de Windows no distinga entre mayúsculas y minúsculas.

## Decisiones Técnicas

### 1. Inertia se emplea como capa de enrutamiento y layouts, no en su modo canónico

En un proyecto Inertia convencional, el controlador de Laravel consulta la base de datos y entrega los datos al componente Vue como props: no existe API, ni tokens en el cliente, ni CORS.

El enunciado exige, en cambio, que el frontend consuma **exclusivamente** el API del backend mediante Axios. En consecuencia, las rutas de `web.php` se limitan a renderizar componentes vacíos (`Inertia::render('Dashboard')`), sin consultas ni props, y la totalidad de los datos se solicita desde el navegador al API externo.

Se asume que el escenario simulado es aquel en el que el API ya existe y es independiente —consumible también por una aplicación móvil u otros clientes— y el frontend web es un consumidor más. Si el frontend fuera el único consumidor, lo idiomático sería que el controlador de Inertia accediera directamente a la base de datos y pasara los datos como props, evitando el API intermedio, el token en el cliente y la configuración de CORS.

### 2. El frontend no utiliza base de datos

No define modelos ni migraciones: la totalidad de los datos proviene del API. Por ello se configuró `SESSION_DRIVER=file` y `CACHE_STORE=file`, y se retiró la configuración de base de datos del `.env`.

Inertia requiere sesiones —forma parte del grupo de middleware `web`—, pero no impone dónde se almacenan. Mantener el driver `database` por defecto habría introducido una dependencia de una base de datos que este proyecto no necesita, y que provocaba un error 500 en cualquier instalación limpia donde el archivo SQLite aún no existiera.

### 3. La protección de rutas ocurre en el cliente, y es una consecuencia forzada de la arquitectura

Las rutas de `web.php` **no** llevan middleware de autenticación, porque el Laravel del frontend no tiene forma de saber quién es el usuario: no existe sesión de autenticación y el token JWT reside en el `localStorage` del navegador, fuera de su alcance.

La protección se implementa mediante el composable `useAuthGuard`, que al montar cada página verifica la existencia del token y redirige al login si no lo encuentra. Su alcance real es limitado y conviene ser explícito al respecto: un usuario sin token puede llegar a ver el esqueleto de la página durante un instante antes de ser redirigido, pero **no verá ningún dato**, porque todos los datos provienen del API, protegido por el middleware `auth:api`. La seguridad efectiva reside en el backend; el guard del cliente resuelve la experiencia de usuario, no la seguridad.

### 4. El token se maneja en dos capas: `localStorage` para persistir, Pinia para reaccionar

Ambos mecanismos cumplen funciones distintas y complementarias:

- **`localStorage`** aporta persistencia: el token sobrevive a una recarga de página. No es reactivo.
- **Pinia** aporta reactividad: cuando el usuario cambia, el navbar se actualiza solo. No sobrevive a una recarga.

El store se inicializa leyendo el token desde `localStorage` y escribe en ambos al modificarlo. Deliberadamente **no** se persiste el objeto `user`: se vuelve a solicitar a `GET /api/me` cuando la aplicación arranca. El API es la fuente de verdad de los datos del usuario; `localStorage` guarda únicamente la credencial.

Se conoce la limitación de seguridad de este enfoque: un token en `localStorage` es accesible desde JavaScript y, por tanto, vulnerable ante un XSS. La alternativa más robusta sería una cookie `httpOnly`, pero el enunciado solicita explícitamente `localStorage` o Pinia.

### 5. Los interceptores de Axios centralizan el token y la expiración de sesión

`lib/api.js` define una instancia de Axios con dos interceptores:

- **De petición:** inyecta la cabecera `Authorization: Bearer {token}` en cada llamada. Sin esto habría que recordar añadir el token manualmente en cada punto del código, y basta olvidarlo una vez para introducir un error.
- **De respuesta:** ante cualquier **401** —token expirado, inválido o revocado tras un logout— limpia el `localStorage` y redirige al login. Sin este mecanismo, al expirar el token (60 minutos por defecto) la aplicación quedaría mostrando errores sin explicación.

Este es además el punto exacto donde encajaría una estrategia de *refresh token*: interceptar el 401, renovar el token y reintentar la petición original.

### 6. El buscador aplica debounce de 300 ms

Sin debounce, cada pulsación de tecla dispararía una petición al API; escribir "laravel" generaría siete llamadas. Un único `watch` con `deep: true` observa los tres filtros y espera 300 ms de inactividad antes de consultar, enviando una sola petición.

Adicionalmente, el store descarta los filtros vacíos antes de construir la query string, de modo que no se envían parámetros como `?search=`.

### 7. Las fechas se formatean en UTC de forma explícita

El API devuelve `due_date` en formato ISO con zona UTC (`2026-07-20T00:00:00.000000Z`). Sin forzar `timeZone: 'UTC'` en el formateo, un navegador situado en Lima (UTC-5) interpretaría esa marca como las 19:00 del día anterior y mostraría la fecha equivocada. Es un error frecuente y silencioso.

### 8. No se implementó la vista de registro

El API expone `POST /api/register` conforme a la Parte 1 del enunciado, pero la Parte 2 especifica únicamente las pantallas de Login, Dashboard, Listado y Navbar. No se añadió una vista de registro para no exceder el alcance solicitado. El acceso para evaluación se resuelve mediante el seeder del backend.

## Notas

- `localhost` y `127.0.0.1` son orígenes distintos para el navegador, aunque apunten a la misma máquina. La configuración de CORS del backend admite ambos.
- El backend debe estar en ejecución antes de iniciar sesión; en caso contrario la petición falla y se muestra un mensaje de error de conexión.
