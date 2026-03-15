# Sistema de Ventas con Microservicios

Sistema para registrar ventas de una tienda utilizando arquitectura de microservicios.

> **Taller de Software II — Universidad Nacional de Colombia Sede Manizales**

---

## Arquitectura del sistema

```
Cliente (Thunder Client / Postman)
            │
            ▼
  [API Gateway - Laravel :8000]
       │  Valida JWT
       │  Agrega X-Internal-Key
       │
       ├──────────────────────────────────┐
       ▼                                  ▼
[Inventario - Flask :5000]     [Ventas - Express :3001]
  Firebase Realtime DB              MongoDB Atlas
```

---

## Tecnologías

| Servicio | Tecnología | Base de datos |
|---------|-----------|--------------|
| API Gateway | PHP 8.3 / Laravel 12 | MySQL (Laragon) |
| Microservicio Inventario | Python 3.11 / Flask | Firebase Realtime DB |
| Microservicio Ventas | Node.js 18 / Express | MongoDB Atlas |

---

## Requisitos previos

- PHP 8.3+ y Composer
- Python 3.11+ y pip
- Node.js 18+ y npm
- Laragon (MySQL)
- Cuenta en [Firebase Console](https://console.firebase.google.com)
- Cuenta en [MongoDB Atlas](https://cloud.mongodb.com)

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd ventas-microservicios
```

---

### 2. Gateway — Laravel

```bash
cd gateway
composer install
cp .env.example .env
php artisan key:generate
php artisan install:api
```

Instalar JWT:

```bash
composer require php-open-source-saver/jwt-auth
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
php artisan migrate
```

Configurar `.env`:

```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gateway_db
DB_USERNAME=root
DB_PASSWORD=

INVENTARIO_URL=http://127.0.0.1:5000
VENTAS_URL=http://127.0.0.1:3001
INTERNAL_KEY=clave_interna_segura_2026

JWT_SECRET=    ← generado automáticamente con jwt:secret
JWT_ALGO=HS256

CACHE_STORE=database
```

Correr el servidor:

```bash
php artisan serve
```

Disponible en `http://localhost:8000`

---

### 3. Inventario — Flask

```bash
cd inventario
python -m venv venv
source venv/Scripts/activate     # Windows Git Bash
# source venv/bin/activate       # Mac / Linux
pip install -r requirements.txt
```

Descarga el archivo `serviceAccountKey.json` desde:
> Firebase Console → Configuracion → Cuentas de servicio → Generar nueva clave privada

Colócalo en la carpeta `inventario/` y configura el `.env`:

```env
FLASK_APP=app.py
FLASK_ENV=development
DATABASE_URL=https://tu-proyecto.firebaseio.com/
INTERNAL_KEY=clave_interna_segura_2026
```

Correr el servidor:

```bash
python app.py
```

Disponible en `http://localhost:5000`

---

### 4. Ventas — Express

```bash
cd ventas
npm install
```

Configura el `.env`:

```env
PORT=3001
MONGO_URI=mongodb+srv://usuario:password@cluster.mongodb.net/ventas_db
INTERNAL_KEY=clave_interna_segura_2026
```

Correr el servidor:

```bash
node app.js
```

Disponible en `http://localhost:3001`

---

## Endpoints

> Todos los endpoints protegidos requieren el header:
> `Authorization: Bearer <token>`

### Autenticacion

| Metodo | Endpoint | Descripcion | Auth |
|--------|----------|-------------|------|
| POST | `/api/register` | Registrar usuario | No |
| POST | `/api/login` | Iniciar sesion y obtener token | No |
| POST | `/api/logout` | Cerrar sesion e invalidar token | Si |
| GET | `/api/me` | Ver usuario autenticado | Si |

### Inventario (Flask via Gateway)

| Metodo | Endpoint | Descripcion | Auth |
|--------|----------|-------------|------|
| POST | `/api/productos` | Registrar producto | Si |
| GET | `/api/productos` | Listar todos los productos | Si |
| GET | `/api/productos/{id}/stock` | Verificar stock de un producto | Si |

### Ventas (Express via Gateway)

| Metodo | Endpoint | Descripcion | Auth |
|--------|----------|-------------|------|
| POST | `/api/ventas` | Registrar una venta | Si |
| GET | `/api/ventas` | Listar todas las ventas | Si |
| GET | `/api/ventas?desde=&hasta=` | Filtrar ventas por fecha | Si |
| GET | `/api/ventas/usuario/{id}` | Ventas por usuario | Si |

---

## Flujo de registro de una venta

```
1. Cliente envia POST /api/ventas + JWT
2. Gateway valida el JWT
3. Gateway consulta stock a Flask (Firebase)
4. Si hay stock disponible:
   a. Gateway registra la venta en Express (MongoDB)
   b. Gateway descuenta el stock en Flask (Firebase)
5. Gateway retorna la venta registrada al cliente
```

---

## Seguridad

| Capa | Mecanismo | Descripcion |
|------|-----------|-------------|
| Cliente -> Gateway | JWT | Solo usuarios autenticados acceden a la API |
| Gateway -> Microservicios | X-Internal-Key | Flask y Express rechazan peticiones externas |

---

## Estructura del proyecto

```
ventas-microservicios/
│
├── gateway/                        <- Laravel (API Gateway)
│   ├── app/Http/Controllers/
│   │   ├── AuthController.php
│   │   ├── InventarioController.php
│   │   └── VentasController.php
│   ├── routes/
│   │   └── api.php
│   └── bootstrap/
│       └── app.php
│
├── inventario/                     <- Flask (Microservicio 1)
│   ├── app.py
│   ├── routes.py
│   ├── serviceAccountKey.json
│   ├── requirements.txt
│   └── .env
│
├── ventas/                         <- Express (Microservicio 2)
│   ├── app.js
│   ├── models/
│   │   └── Venta.js
│   ├── routes/
│   │   └── ventas.js
│   ├── middlewares/
│   │   └── verificarGateway.js
│   └── .env
│
└── README.md
```

---

## Pruebas con Thunder Client

```
1.  POST /api/register              -> 201 usuario creado
2.  POST /api/login                 -> 200 + token JWT
3.  GET  /api/productos             -> 401 sin token
4.  POST /api/productos             -> 201 producto creado (con token)
5.  GET  /api/productos             -> 200 lista de productos
6.  GET  /api/productos/{id}/stock  -> 200 stock disponible
7.  POST /api/ventas                -> 201 venta registrada
8.  GET  /api/productos/{id}/stock  -> 200 stock descontado
9.  POST /api/logout                -> 200 sesion cerrada
10. GET  /api/productos             -> 401 token invalido
```
