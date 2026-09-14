# MarckStock

Sistema privado de inventario para tienda escolar, hecho con **PHP + PostgreSQL**, preparado para Render y con API para una futura app Android con Retrofit.

## Módulos incluidos

- Login privado (sin registro público)
- Dashboard con métricas
- CRUD completo de productos
- Categorías reales
- Stock mínimo y alertas automáticas
- Buscador y filtros
- Reportes + exportación CSV
- Notificaciones de stock bajo
- Perfil y cambio de contraseña
- Configuración general (solo Admin)
- Gestión de usuarios (solo Admin)
- Roles: ADMIN y ENCARGADO
- API REST con Bearer Token para Android

## Permisos

**ADMIN**: todo el sistema, incluyendo categorías, configuración y usuarios.

**ENCARGADO**: dashboard, productos (crear/editar/eliminar), búsqueda, stock bajo, reportes, notificaciones y su cuenta.

## Base de datos

Tablas principales:

- `usuarios`
- `categorias`
- `productos`
- `configuracion`
- `api_tokens`

La base se inicializa automáticamente en la primera petición si está vacía.

## Credenciales demo

Si no defines variables de entorno, se crean:

- Admin: `admin` / `MarckStock2026!`
- Encargado: `encargado` / `Encargado2026!`

**Cámbialas en un despliegue real.** Puedes establecer `ADMIN_PASSWORD` y `ENCARGADO_PASSWORD` en Render antes del primer inicio.

## Ejecutar localmente

Necesitas PHP con `pdo_pgsql` y PostgreSQL.

1. Crea una base PostgreSQL vacía.
2. Configura `DATABASE_URL`.
3. Ejecuta:

```bash
php -S localhost:8000 -t public
```

4. Abre `http://localhost:8000`.

## Render

1. Sube esta carpeta a un repositorio GitHub.
2. Crea una PostgreSQL Database en Render.
3. Crea un Web Service usando el `Dockerfile`.
4. En variables de entorno del Web Service agrega `DATABASE_URL` con la Internal Database URL de PostgreSQL.
5. Opcionalmente define `ADMIN_USER`, `ADMIN_PASSWORD`, `ADMIN_NAME`, `ENCARGADO_USER`, `ENCARGADO_PASSWORD` y `ENCARGADO_NAME`.
6. Despliega. En el primer acceso, MarckStock crea las tablas y datos iniciales.

## API Android

Base de ejemplo:

```text
https://TU-SERVICIO.onrender.com/api/
```

### Login

`POST /api/auth/login.php`

JSON:

```json
{"usuario":"admin","password":"tu-clave"}
```

Devuelve un `token`. Luego envía:

```text
Authorization: Bearer TU_TOKEN
```

### Productos

- `GET /api/products/select.php`
- `POST /api/products/insert.php`
- `POST /api/products/update.php`
- `POST /api/products/delete.php`

### Categorías

- `GET /api/categories/select.php`

### Dashboard

- `GET /api/dashboard/summary.php`

## Estructura

```text
MarckStock/
├── app/
│   ├── config/
│   ├── helpers/
│   └── views/partials/
├── database/
├── public/
│   ├── api/
│   └── assets/
├── scripts/
├── Dockerfile
└── README.md
```
