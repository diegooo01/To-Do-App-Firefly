# Prueba Técnica — Sistema de Gestión de Tareas

Aplicación fullstack compuesta por dos proyectos Laravel independientes: un API REST con autenticación JWT y un cliente SPA que lo consume.

## Estructura

```
to-do-app/
├── backend/     API REST — Laravel 12 + JWT (tymon/jwt-auth) + SQLite
└── frontend/    SPA — Laravel 12 + Inertia.js + Vue 3 + TailwindCSS + Pinia
```

Cada proyecto tiene su propio README con requisitos, instalación, variables de entorno y decisiones técnicas:

- [backend/README.md](backend/README.md)
- [frontend/README.md](frontend/README.md)

## Arquitectura

```
Navegador
   │
   ├── http://localhost:8001  →  frontend (Inertia sirve las páginas Vue)
   │
   └── http://localhost:8000  →  backend (API REST, datos vía Axios + JWT)
```

El frontend no accede a base de datos. Toda su información proviene del API mediante Axios, autenticándose con el token JWT que guarda en `localStorage`.

## Puesta en marcha

### 1. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve --port=8000
```

### 2. Frontend

En otra terminal:

```bash
cd frontend
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan serve --port=8001
```

Y en una tercera terminal:

```bash
cd frontend
npm run dev
```

### 3. Acceder

Abrir `http://localhost:8001` e iniciar sesión con:

| Email | Contraseña |
|---|---|
| `diego@test.com` | `password123` |

El seeder crea además un segundo usuario (`otro@test.com`, misma contraseña) con sus propias tareas, para poder verificar que cada usuario solo visualiza las suyas.

## Funcionalidad

**Backend**
- Registro, inicio y cierre de sesión con JWT
- CRUD de tareas con filtros por estado, prioridad y búsqueda por título
- Cambio de estado en endpoint dedicado
- Regla de negocio: una tarea completada no puede editarse
- Aislamiento por usuario mediante relación Eloquent y Policy
- Endpoint de métricas agregadas

**Frontend**
- Login con manejo diferenciado de errores de validación y credenciales
- Dashboard con las cuatro métricas
- Listado de tareas con filtros combinables y búsqueda con debounce
- Navbar con el usuario autenticado y cierre de sesión
- Redirección automática al login ante un token ausente o expirado
