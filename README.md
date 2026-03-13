# Baltomore IP — Tasks REST API

A Laravel-based REST API for managing person records with fields for name, age, birthdate, and email.

---

## Requirements

- PHP >= 8.2
- Composer
- SQLite (default) or MySQL/PostgreSQL

---

## Installation

```bash
# 1. Install dependencies
composer install

# 2. Copy the environment file
cp .env.example .env   # Windows: copy .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Run database migrations
php artisan migrate
```

> By default the app uses **SQLite**. No extra database configuration is needed for local development.  
> To use MySQL or another driver, update `DB_CONNECTION` and related values in your `.env` file.

---

## Running the Server

```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`.

---

## API Endpoints

Base URL: `http://127.0.0.1:8000/api`

### Authentication (public)

| Method | Endpoint      | Description             |
|--------|---------------|-------------------------|
| `POST` | `/register`   | Register a new user     |
| `POST` | `/login`      | Login and get a token   |
| `POST` | `/logout`     | Revoke token (auth required) |

### Tasks (requires Bearer token)

| Method      | Endpoint        | Description         |
|-------------|-----------------|---------------------|
| `GET`       | `/tasks`        | List all records    |
| `POST`      | `/tasks`        | Create a record     |
| `GET`       | `/tasks/{id}`   | Get a single record |
| `PUT/PATCH` | `/tasks/{id}`   | Update a record     |
| `DELETE`    | `/tasks/{id}`   | Delete a record     |

---

## Request & Response Examples

### Register — `POST /api/register`

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**Response `201`:**
```json
{
  "user": { "id": 1, "name": "John Doe", "email": "john@example.com" },
  "token": "1|abc123..."
}
```

---

### Login — `POST /api/login`

```json
{
  "email": "john@example.com",
  "password": "secret123"
}
```

**Response `200`:**
```json
{
  "user": { "id": 1, "name": "John Doe", "email": "john@example.com" },
  "token": "2|xyz789..."
}
```

---

### Using the token

Include the token in the `Authorization` header for all protected routes:

```
Authorization: Bearer 2|xyz789...
```

---

### Logout — `POST /api/logout`

No body required. Just send the `Authorization` header. Revokes the current token.

**Response `200`:**
```json
{ "message": "Logged out successfully" }
```

---

### Create a record — `POST /api/tasks`

**Request body:**
```json
{
  "name": "John Doe",
  "age": 30,
  "birthdate": "1995-03-13",
  "email": "john@example.com"
}
```

**Response `201`:**
```json
{
  "id": 1,
  "name": "John Doe",
  "age": 30,
  "birthdate": "1995-03-13",
  "email": "john@example.com",
  "created_at": "2026-03-13T00:00:00.000000Z",
  "updated_at": "2026-03-13T00:00:00.000000Z"
}
```

---

### List all records — `GET /api/tasks`

**Response `200`:**
```json
[
  {
    "id": 1,
    "name": "John Doe",
    "age": 30,
    "birthdate": "1995-03-13",
    "email": "john@example.com",
    "created_at": "...",
    "updated_at": "..."
  }
]
```

---

### Get one record — `GET /api/tasks/1`

**Response `200`:** returns the matching record object, or `404` if not found.

---

### Update a record — `PATCH /api/tasks/1`

All fields are optional — only send what you want to change.

**Request body:**
```json
{
  "age": 31
}
```

**Response `200`:** returns the updated record object.

---

### Delete a record — `DELETE /api/tasks/1`

**Response `200`:**
```json
{
  "message": "Deleted"
}
```

---

## Validation Rules

| Field       | Create               | Update                    |
|-------------|----------------------|---------------------------|
| `name`      | required, string     | optional, string          |
| `age`       | required, integer    | optional, integer         |
| `birthdate` | required, date       | optional, date            |
| `email`     | required, valid, unique | optional, valid, unique (ignores own record) |

Validation failures return `422` with a JSON error body.

---

## Running Tests

```bash
php artisan test
```

---

## Project Structure

```
app/
  Http/
    Controllers/TaskController.php   # CRUD logic
    Requests/StoreTaskRequest.php    # Create validation
    Requests/UpdateTaskRequest.php   # Update validation
  Models/Task.php                    # Eloquent model
database/
  migrations/                        # DB schema
routes/
  api.php                            # API route definitions
```

---

## License

MIT
