#  Sistema de Ventas con Microservicios

**Taller de Software II — Taller Universitario**
**Estudiante:** Ricardo Calderon Garcia
**Fecha:** 15 de marzo de 2026

---

##  Descripción General

Sistema de ventas distribuido basado en arquitectura de microservicios. Utiliza un **API Gateway** central (Laravel) que autentica usuarios mediante JWT y enruta las solicitudes hacia microservicios especializados de Inventario (Flask/Python) y Ventas (Express/Node.js).

---

##  Diagrama de Arquitectura (ASCII)

```
┌─────────────────────────────────────────────────────────────────────┐
│                          CLIENTE / THUNDER CLIENT                   │
│                   (HTTP Requests + JWT Bearer Token)                │
└─────────────────────────────────┬───────────────────────────────────┘
                                  │  HTTP + JWT
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         API GATEWAY (Laravel 12)                    │
│                     PHP 8.3 — Puerto 8000                           │
│                     Base de datos: MySQL (Laragon)                  │
│                                                                     │
│   ┌─────────────────────────────────────────────────────────────┐   │
│   │  Autenticación JWT (tymon/jwt-auth)                         │   │
│   │  • Genera tokens en /api/login                              │   │
│   │  • Valida tokens en rutas protegidas                        │   │
│   │  • Invalida tokens en /api/logout                           │   │
│   └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│   ┌─────────────────────────────────────────────────────────────┐   │
│   │  Proxy / Router interno                                     │   │
│   │  • Agrega header: X-Internal-Key: {secret}                 │   │
│   │  • Redirige peticiones a microservicios                     │   │
│   └─────────────────────────────────────────────────────────────┘   │
└──────────────┬──────────────────────────────────┬────────────────────┘
               │  HTTP + X-Internal-Key            │  HTTP + X-Internal-Key
               ▼                                   ▼
┌──────────────────────────┐         ┌─────────────────────────────┐
│  MICROSERVICIO INVENTARIO│         │   MICROSERVICIO VENTAS      │
│  Flask (Python 3.11)     │         │   Express (Node.js 18)      │
│  Puerto: 5000            │         │   Puerto: 3001              │
│  DB: Firebase Realtime   │         │   DB: MongoDB Atlas         │
│                          │         │                             │
│  • POST /productos       │         │   • POST /ventas            │
│  • GET  /productos       │         │   • GET  /ventas            │
│  • GET  /productos/{id}  │         │   • GET  /ventas/usuario/   │
│        /stock            │         │         {usuarioId}         │
└──────────────────────────┘         └─────────────────────────────┘
           │                                       │
           ▼                                       ▼
┌──────────────────────────┐         ┌─────────────────────────────┐
│   Firebase Realtime DB   │         │       MongoDB Atlas          │
│   (Productos / Stock)    │         │       (Ventas / Pedidos)     │
└──────────────────────────┘         └─────────────────────────────┘
```

---

##  Seguridad

### Capa 1: Cliente → Gateway (JWT)

| Elemento         | Detalle                                          |
|------------------|--------------------------------------------------|
| Algoritmo        | HS256 (HMAC SHA-256)                             |
| Header           | `Authorization: Bearer <token>`                  |
| Expiración       | Configurable (p. ej., 60 minutos)                |
| Invalidación     | Blacklist en base de datos MySQL                 |
| Librería         | `tymon/jwt-auth` para Laravel                    |

**Rutas sin autenticación:**
- `POST /api/register`
- `POST /api/login`

**Rutas con autenticación JWT obligatoria:**
- Todas las demás rutas (`/api/me`, `/api/logout`, `/api/productos`, `/api/ventas`)

### Capa 2: Gateway → Microservicios (X-Internal-Key)

| Elemento         | Detalle                                               |
|------------------|-------------------------------------------------------|
| Mecanismo        | Header HTTP personalizado                             |
| Header           | `X-Internal-Key: <clave_secreta_compartida>`          |
| Configuración    | Variable de entorno en Gateway y en cada microservicio|
| Rechazo          | Microservicio devuelve `403 Forbidden` si falta/errada|

>  Los microservicios **nunca** son accesibles directamente desde el exterior. Solo el Gateway conoce su URL y clave interna.

---

##  Estructura del Proyecto

```
sistema-ventas-microservicios/
│
├── api-gateway/                    # Laravel 12 — Puerto 8000
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuthController.php       # register, login, logout, me
│   │   │   │   ├── ProductoController.php   # proxy a Flask
│   │   │   │   └── VentaController.php      # proxy a Express
│   │   │   └── Middleware/
│   │   │       └── JwtMiddleware.php        # Valida JWT
│   │   └── Services/
│   │       └── MicroserviceProxy.php        # Lógica de proxy HTTP
│   ├── config/
│   │   └── jwt.php
│   ├── routes/
│   │   └── api.php                          # Definición de rutas
│   ├── .env                                 # Variables de entorno
│   └── composer.json
│
├── microservicio-inventario/       # Flask — Puerto 5000
│   ├── app.py                      # Aplicación principal Flask
│   ├── routes/
│   │   └── productos.py            # Endpoints de productos
│   ├── middleware/
│   │   └── internal_key.py         # Validación X-Internal-Key
│   ├── services/
│   │   └── firebase_service.py     # Conexión Firebase
│   ├── requirements.txt
│   └── .env
│
├── microservicio-ventas/           # Express — Puerto 3001
│   ├── src/
│   │   ├── app.js                  # Aplicación principal Express
│   │   ├── routes/
│   │   │   └── ventas.js           # Endpoints de ventas
│   │   ├── middleware/
│   │   │   └── internalKey.js      # Validación X-Internal-Key
│   │   └── services/
│   │       └── mongoService.js     # Conexión MongoDB Atlas
│   ├── package.json
│   └── .env
│
├── .gitignore
└── README.md
```

---

##  Instalación y Configuración

### Prerequisitos Globales

- PHP 8.3 + Composer
- Laragon (MySQL)
- Python 3.11 + pip
- Node.js 18 + npm
- Cuenta Firebase (Realtime Database habilitada)
- Cuenta MongoDB Atlas

---

### 1 API Gateway — Laravel 12

```bash
# Clonar / ir al directorio
cd api-gateway

# Instalar dependencias PHP
composer install

# Instalar JWT
composer require tymon/jwt-auth

# Copiar y configurar variables de entorno
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

**Configurar `.env`:**
```env
APP_NAME=ApiGateway
APP_PORT=8000
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gateway_db
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=<generado_por_artisan_jwt:secret>
JWT_TTL=60

# Microservicios
INVENTARIO_URL=http://localhost:5000
VENTAS_URL=http://localhost:3001
INTERNAL_KEY=mi_clave_interna_secreta_2026
```

```bash
# Ejecutar migraciones (tabla users + token blacklist)
php artisan migrate

# Iniciar servidor
php artisan serve --port=8000
```

---

### 2 Microservicio Inventario — Flask

```bash
cd microservicio-inventario

# Crear entorno virtual
python -m venv venv
source venv/bin/activate        # Linux/Mac
venv\Scripts\activate           # Windows

# Instalar dependencias
pip install flask firebase-admin python-dotenv

# Guardar dependencias
pip freeze > requirements.txt
```

**Configurar `.env`:**
```env
PORT=5000
INTERNAL_KEY=mi_clave_interna_secreta_2026
FIREBASE_CREDENTIALS=serviceAccountKey.json
FIREBASE_DATABASE_URL=https://<tu-proyecto>.firebaseio.com
```

>  Coloca el archivo `serviceAccountKey.json` de Firebase en la raíz del microservicio.

```bash
# Iniciar microservicio
python app.py
```

---

### 3 Microservicio Ventas — Express

```bash
cd microservicio-ventas

# Instalar dependencias
npm install express mongoose dotenv

# Iniciar en desarrollo
npm run dev

# O en producción
npm start
```

**Configurar `.env`:**
```env
PORT=3001
INTERNAL_KEY=mi_clave_interna_secreta_2026
MONGO_URI=mongodb+srv://<usuario>:<password>@cluster.mongodb.net/ventas_db
```

---

##  Endpoints del API Gateway

###  Rutas Públicas (Sin autenticación)

#### `POST /api/register`
Registra un nuevo usuario en el sistema.

**Body (JSON):**
```json
{
  "name": "Ricardo Calderon",
  "email": "ricardo@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Respuesta exitosa (201):**
```json
{
  "message": "Usuario registrado exitosamente",
  "user": {
    "id": 1,
    "name": "Ricardo Calderon",
    "email": "ricardo@example.com"
  }
}
```

---

#### `POST /api/login`
Autentica al usuario y devuelve un token JWT.

**Body (JSON):**
```json
{
  "email": "ricardo@example.com",
  "password": "password123"
}
```

**Respuesta exitosa (200):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

---

###  Rutas Protegidas (Requieren JWT)

> **Header requerido:** `Authorization: Bearer <token>`

---

#### `POST /api/logout`
Invalida el token JWT actual (blacklist).

**Respuesta exitosa (200):**
```json
{
  "message": "Sesion cerrada exitosamente"
}
```

---

#### `GET /api/me`
Devuelve los datos del usuario autenticado.

**Respuesta exitosa (200):**
```json
{
  "id": 1,
  "name": "Ricardo Calderon",
  "email": "ricardo@example.com",
  "created_at": "2026-03-15T10:00:00.000Z"
}
```

---

#### `POST /api/productos` → Flask :5000
Crea un nuevo producto en el inventario.

**Body (JSON):**
```json
{
  "nombre": "Laptop Dell XPS",
  "precio": 1200.00,
  "stock": 50,
  "categoria": "Tecnologia"
}
```

**Respuesta exitosa (201):**
```json
{
  "id": "-N9xKkLmAbCdEfGh",
  "nombre": "Laptop Dell XPS",
  "precio": 1200.00,
  "stock": 50,
  "categoria": "Tecnologia",
  "creado_en": "2026-03-15T10:00:00Z"
}
```

---

#### `GET /api/productos` → Flask :5000
Lista todos los productos del inventario.

**Respuesta exitosa (200):**
```json
{
  "productos": [
    {
      "id": "-N9xKkLmAbCdEfGh",
      "nombre": "Laptop Dell XPS",
      "precio": 1200.00,
      "stock": 50,
      "categoria": "Tecnologia"
    }
  ]
}
```

---

#### `GET /api/productos/{id}/stock` → Flask :5000
Verifica el stock disponible de un producto.

**Ejemplo:** `GET /api/productos/-N9xKkLmAbCdEfGh/stock`

**Respuesta exitosa (200):**
```json
{
  "producto_id": "-N9xKkLmAbCdEfGh",
  "nombre": "Laptop Dell XPS",
  "stock_disponible": 50
}
```

---

#### `POST /api/ventas` → Express :3001
Registra una nueva venta en el sistema.

**Body (JSON):**
```json
{
  "usuario_id": 1,
  "productos": [
    {
      "producto_id": "-N9xKkLmAbCdEfGh",
      "nombre": "Laptop Dell XPS",
      "cantidad": 2,
      "precio_unitario": 1200.00
    }
  ]
}
```

**Respuesta exitosa (201):**
```json
{
  "venta_id": "65f3a2b1c4e5d6f7a8b9c0d1",
  "usuario_id": 1,
  "productos": [...],
  "total": 2400.00,
  "fecha": "2026-03-15T10:00:00Z",
  "estado": "completada"
}
```

---

#### `GET /api/ventas` → Express :3001
Lista todas las ventas registradas.

**Respuesta exitosa (200):**
```json
{
  "ventas": [
    {
      "venta_id": "65f3a2b1c4e5d6f7a8b9c0d1",
      "usuario_id": 1,
      "total": 2400.00,
      "fecha": "2026-03-15T10:00:00Z",
      "estado": "completada"
    }
  ]
}
```

---

#### `GET /api/ventas/usuario/{usuarioId}` → Express :3001
Obtiene todas las ventas de un usuario específico.

**Ejemplo:** `GET /api/ventas/usuario/1`

**Respuesta exitosa (200):**
```json
{
  "usuario_id": 1,
  "total_ventas": 3,
  "ventas": [...]
}
```

---

##  4 Flujo Completo de Registro de Venta

```
1. [CLIENTE]  POST /api/ventas  + Authorization: Bearer <JWT>
       │
       ▼
2. [GATEWAY]  Middleware JwtMiddleware valida el token
              ✓ Token válido → continuar
              ✗ Token inválido/expirado → 401 Unauthorized
       │
       ▼
3. [GATEWAY]  VentaController::store() recibe la solicitud
              • Extrae datos del body
              • Agrega header: X-Internal-Key: <clave_secreta>
       │
       ▼
4. [GATEWAY → EXPRESS]  HTTP POST http://localhost:3001/ventas
                         Headers: X-Internal-Key: <clave_secreta>
                         Body: { usuario_id, productos[] }
       │
       ▼
5. [EXPRESS]  Middleware internalKey.js valida X-Internal-Key
              ✓ Clave válida → continuar
              ✗ Clave inválida/ausente → 403 Forbidden
       │
       ▼
6. [EXPRESS]  ventasRouter POST /ventas
              • Calcula total de la venta
              • Guarda documento en MongoDB Atlas
              • Retorna venta creada con ID
       │
       ▼
7. [GATEWAY]  Recibe respuesta de Express (201 + datos venta)
              • Retorna la respuesta al cliente
       │
       ▼
8. [CLIENTE]  Recibe confirmación: venta_id, total, fecha, estado
```

---

##  Pruebas con Thunder Client

### Paso 1 — Registrar Usuario
```
Método: POST
URL:    http://localhost:8000/api/register
Headers: Content-Type: application/json
Body:
{
  "name": "Ricardo Calderon",
  "email": "ricardo@test.com",
  "password": "pass1234",
  "password_confirmation": "pass1234"
}
```

### Paso 2 — Login (Obtener Token)
```
Método: POST
URL:    http://localhost:8000/api/login
Headers: Content-Type: application/json
Body:
{
  "email": "ricardo@test.com",
  "password": "pass1234"
}
→ Copiar el campo "access_token" de la respuesta
```

### Paso 3 — Crear Producto
```
Método: POST
URL:    http://localhost:8000/api/productos
Headers:
  Authorization: Bearer <token_copiado>
  Content-Type: application/json
Body:
{
  "nombre": "Mouse Logitech MX",
  "precio": 75.00,
  "stock": 100,
  "categoria": "Periféricos"
}
```

### Paso 4 — Verificar Stock
```
Método: GET
URL:    http://localhost:8000/api/productos/<id_producto>/stock
Headers:
  Authorization: Bearer <token_copiado>
```

### Paso 5 — Registrar Venta
```
Método: POST
URL:    http://localhost:8000/api/ventas
Headers:
  Authorization: Bearer <token_copiado>
  Content-Type: application/json
Body:
{
  "usuario_id": 1,
  "productos": [
    {
      "producto_id": "<id_producto>",
      "nombre": "Mouse Logitech MX",
      "cantidad": 3,
      "precio_unitario": 75.00
    }
  ]
}
```

### Paso 6 — Listar Ventas del Usuario
```
Método: GET
URL:    http://localhost:8000/api/ventas/usuario/1
Headers:
  Authorization: Bearer <token_copiado>
```

### Paso 7 — Cerrar Sesión
```
Método: POST
URL:    http://localhost:8000/api/logout
Headers:
  Authorization: Bearer <token_copiado>
```

---

## Bases de Datos

| Componente             | Motor              | Uso                                    |
|------------------------|--------------------|----------------------------------------|
| API Gateway            | MySQL (Laragon)    | Usuarios, tokens JWT blacklist         |
| Microservicio Inventario | Firebase Realtime DB | Productos y stock en tiempo real     |
| Microservicio Ventas   | MongoDB Atlas      | Historial de ventas (documentos JSON)  |

---

## Variables de Entorno Resumen

| Variable        | Servicio    | Descripción                         |
|-----------------|-------------|-------------------------------------|
| `JWT_SECRET`    | Gateway     | Clave para firmar tokens JWT        |
| `JWT_TTL`       | Gateway     | Tiempo de vida del token (minutos)  |
| `INTERNAL_KEY`  | Todos       | Clave compartida gateway↔microservicios |
| `INVENTARIO_URL`| Gateway     | URL del microservicio Flask         |
| `VENTAS_URL`    | Gateway     | URL del microservicio Express       |
| `FIREBASE_DATABASE_URL` | Inventario | URL de Firebase Realtime DB  |
| `MONGO_URI`     | Ventas      | URI de conexión a MongoDB Atlas     |

---

## Tecnologías Utilizadas

| Componente       | Tecnología           | Versión  | Puerto |
|------------------|----------------------|----------|--------|
| API Gateway      | Laravel (PHP)        | 12 / 8.3 | 8000   |
| Microservicio 1  | Flask (Python)       | 3.11     | 5000   |
| Microservicio 2  | Express (Node.js)    | 18       | 3001   |
| DB Gateway       | MySQL                | -        | 3306   |
| DB Inventario    | Firebase Realtime DB | -        | -      |
| DB Ventas        | MongoDB Atlas        | -        | -      |
| Auth             | JWT (tymon/jwt-auth) | -        | -      |

---