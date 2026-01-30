# 🏋️‍♂️ Gym Management API - Examen Symfony

API RESTful desarrollada con Symfony para la gestión de un gimnasio. El sistema permite administrar actividades, listas de reproducción de música, clientes y reservas, implementando lógica de negocio compleja para diferentes tipos de usuarios (Standard y Premium).

## 🚀 Tecnologías y Arquitectura

* **Framework:** Symfony 6/7 (WebApp)
* **Lenguaje:** PHP 8.2+
* **Base de Datos:** MySQL / MariaDB via Doctrine ORM
* **Arquitectura:** MVC con capa de **DTOs** (Data Transfer Objects) para desacoplar la API de la Base de Datos.
* **Buenas Prácticas:**
    * Uso de **Enums** para tipado estricto (`ClientType`, `ActivityType`).
    * **DataFixtures** para la carga inicial de datos.
    * Manejo de errores robusto con bloques `try-catch` y respuestas JSON estandarizadas.
    * Validaciones de negocio complejas (fechas, aforo, duplicados).

## ⚙️ Instalación y Configuración

Sigue estos pasos para desplegar el proyecto desde cero.

### 1. Requisitos previos
* PHP instalado (con extensiones pdo_mysql).
* Composer.
* Symfony CLI.
* Servidor MySQL/MariaDB corriendo (XAMPP, Docker, etc.).

### 2. Instalación de dependencias
```bash
composer install
```

### 3. Configuración de Base de Datos

Abre el archivo `.env` y configura tu conexión:

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/examen_gym_db?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
```

### 4. Crear Base de Datos y Esquema

Ejecuta los siguientes comandos en orden:

```bash
# Crear la BBDD
php bin/console doctrine:database:create

# Ejecutar migraciones (crear tablas)
php bin/console doctrine:migrations:migrate
```

### 5. Cargar Datos de Prueba (Fixtures) ⭐️

El proyecto incluye un sistema profesional de carga de datos falsos para pruebas.

```bash
php bin/console doctrine:fixtures:load
```

*(Escribe 'yes' cuando pregunte si quieres purgar la base de datos).*

---

## 📡 Documentación de la API

### 1. Listar Actividades

Obtiene el listado de actividades programadas, incluyendo sus *Playlists* asociadas.

* **Endpoint:** `GET /activities`
* **Parámetros (Query Params):**
  * `page`: Número de página (default: 1).
  * `page_size`: Elementos por página (default: 10).
  * `type`: Filtrar por tipo (ej: `BodyPump`, `Spinning`).
  * `sort`: Ordenar por `date` (default: date).
  * `order`: `asc` o `desc` (default: desc).
  * `onlyfree`: Si es `true`, oculta las actividades llenas (default: true).

**Ejemplo de Respuesta (DTO):**

```json
{
  "data": [
    {
      "id": 1,
      "type": "BodyPump",
      "max_participants": 20,
      "clients_signed": 5,
      "date_start": "2025-01-31 10:00:00",
      "date_end": "2025-01-31 11:00:00",
      "play_list": [
        { "id": 1, "name": "Eye of the Tiger", "duration_seconds": 240 }
      ]
    }
  ],
  "meta": { "page": 1, "limit": 10, "total-items": 1 }
}
```

---

### 2. Crear Reserva (Booking)

Permite a un cliente apuntarse a una actividad.

* **Endpoint:** `POST /bookings`
* **Body (JSON):**

```json
{
    "client_id": 1,
    "activity_id": 2
}
```

**Reglas de Negocio Implementadas:**

1. **Validación de Existencia:** Verifica que cliente y actividad existan.
2. **Control de Duplicados:** Un cliente no puede reservar la misma actividad dos veces.
3. **Control de Aforo:** No permite reservar si `bookings >= max_participants`.
4. **Regla Usuario STANDARD:** Solo pueden realizar **2 reservas por semana** (Lunes a Domingo).
5. **Regla Usuario PREMIUM:** Sin límite de reservas.

**Ejemplo de Respuesta:**

```json
{
  "id": 5,
  "activity": {
    "id": 2,
    "type": "Spinning",
    "max_participants": 15,
    "clients_signed": 8,
    "date_start": "2025-02-01 18:00:00",
    "date_end": "2025-02-01 19:00:00",
    "play_list": []
  },
  "client_id": 1
}
```

---

### 3. Detalle de Cliente y Estadísticas

Obtiene la información de un cliente, con opciones para ver su historial y estadísticas agregadas.

* **Endpoint:** `GET /clients/{id}`
* **Parámetros:**
  * `with_bookings=true`: Muestra la lista de reservas activas.
  * `with_statistics=true`: Muestra estadísticas agrupadas por Año y Tipo de actividad.

**Ejemplo de Respuesta Completa:**

```json
{
  "id": 1,
  "name": "Miguel Standard",
  "email": "miguel@gym.com",
  "type": "standard",
  "activities_booked": [
    {
      "id": 3,
      "activity": {
        "id": 1,
        "type": "BodyPump",
        "max_participants": 20,
        "clients_signed": 5,
        "date_start": "2025-01-31 10:00:00",
        "date_end": "2025-01-31 11:00:00",
        "play_list": []
      },
      "client_id": 1
    }
  ],
  "activity_statistics": [
    {
      "year": 2025,
      "statistics_by_type": [
        {
          "type": "BodyPump",
          "statistics": { "num_activities": 2, "num_minutes": 120 }
        }
      ]
    }
  ]
}
```

---

## 🧠 Decisiones de Diseño (Clean Code)

Para asegurar la escalabilidad y mantenibilidad del proyecto, se han tomado las siguientes decisiones:

### 1. **Patrón DTO (Data Transfer Object):**
* No se exponen las Entidades de Doctrine directamente en el JSON.
* Se han creado clases `ActivityDTO`, `PlaylistDTO`, `ClientDTO` y `BookingResponseDTO`.
* Esto permite transformar los datos (ej: calcular `clients_signed`) antes de enviarlos y evita referencias circulares.

### 2. **Enums de PHP 8.1:**
* Se usan `ClientType` y `ActivityType` para evitar "magic strings" y asegurar la integridad de los datos en la base de datos.

### 3. **Validación Manual en Controlador:**
* Debido a que las validaciones dependen de consultas complejas a la BBDD (fechas, conteos por semana), se ha optado por lógica explícita en el controlador dentro de un bloque `try-catch` para un control total del flujo.

### 4. **Manejo de Errores Global:**
* Todos los endpoints están protegidos contra fallos del servidor (Error 500), devolviendo siempre un JSON estructurado y no HTML por defecto.

---

## 🔧 Comandos Útiles

```bash
# Iniciar servidor de desarrollo
symfony serve

# Limpiar caché
php bin/console cache:clear

# Ver rutas disponibles
php bin/console debug:router

# Ejecutar consultas SQL directas
php bin/console doctrine:query:sql "SELECT * FROM client"
```

---

## 📋 Especificación OpenAPI

El proyecto incluye una especificación OpenAPI 3.0 completa en el archivo `openapi_4vGYM_Forclients.yaml` que documenta todos los endpoints, parámetros y esquemas de respuesta.

---

## 👨‍💻 Desarrollo

**Autor:** Jarol Rojas  
**Curso:** Desarrollo Web  
**Framework:** Symfony 7.x  
**Fecha:** Enero 2026