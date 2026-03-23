# Baltomore IP — Laravel Tasks API

A Laravel REST API for managing person records with:
- Sanctum token authentication
- Role and permission authorization (Spatie)
- Database notifications for task activity
- Eloquent ORM relationships and query filtering

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

# 5. Seed roles and permissions
php artisan db:seed
```

> By default the app uses **SQLite**. No extra database configuration is needed for local development.  
> To use MySQL or another driver, update `DB_CONNECTION` and related values in your `.env` file.

---

## Running the Server

```bash
php artisan serve
```

If port 8000 is busy:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

The API will be available at `http://127.0.0.1:8000`.

---

## API Endpoints

Base URL: `http://127.0.0.1:8000/api`

### Authentication

| Method | Endpoint       | Description                           |
|--------|----------------|---------------------------------------|
| `POST` | `/register`    | Register a new user (returns token)   |
| `POST` | `/login`       | Login and get a token                 |
| `POST` | `/logout`      | Revoke current token (auth required)  |
| `POST` | `/assign-role` | Assign role to current user (`admin` / `user`) |

### Tasks (requires Bearer token + permissions)

| Method      | Endpoint        | Permission Required |
|-------------|-----------------|---------------------|
| `GET`       | `/tasks`        | `view tasks`        |
| `POST`      | `/tasks`        | `create tasks`      |
| `GET`       | `/tasks/{id}`   | `view tasks`        |
| `PUT/PATCH` | `/tasks/{id}`   | `edit tasks`        |
| `DELETE`    | `/tasks/{id}`   | `delete tasks`      |

### Task Query Parameters (`GET /tasks`)

| Query Param | Type     | Description |
|------------|----------|-------------|
| `search`   | string   | Matches partial `name` or `email` |
| `min_age`  | integer  | Filter tasks with age >= value |
| `max_age`  | integer  | Filter tasks with age <= value |

### Notifications (requires Bearer token)

| Method | Endpoint                        | Description |
|--------|---------------------------------|-------------|
| `GET`  | `/notifications`                | List notifications + unread count |
| `POST` | `/notifications/{id}/read`      | Mark one notification as read |
| `POST` | `/notifications/read-all`       | Mark all unread notifications as read |

Task create/update/delete actions generate database notifications.

Created tasks are owned by the authenticated user via Eloquent (`user_id`).

---

## Request & Response Examples

### Required Headers for API Testing

```http
Accept: application/json
Content-Type: application/json
```

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

Set this header on protected routes:

```http
Authorization: Bearer YOUR_TOKEN
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
  "user_id": 1,
  "name": "John Doe",
  "age": 30,
  "birthdate": "1995-03-13",
  "email": "john@example.com",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
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
    "user_id": 1,
    "name": "John Doe",
    "age": 30,
    "birthdate": "1995-03-13",
    "email": "john@example.com",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "created_at": "...",
    "updated_at": "..."
  }
]
```

### Filtered list example — `GET /api/tasks?search=john&min_age=18&max_age=35`

Returns only tasks that match search and age conditions.

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

### Get notifications — `GET /api/notifications`

**Response `200`:**
```json
{
  "unread_count": 1,
  "notifications": [
    {
      "id": "uuid",
      "type": "App\\Notifications\\TaskActivityNotification",
      "data": {
        "task_id": 1,
        "task_name": "John Doe",
        "action": "created",
        "actor_name": "Regular User",
        "message": "Task \"John Doe\" was created by Regular User."
      },
      "read_at": null,
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

### Mark one notification as read — `POST /api/notifications/{id}/read`

**Response `200`:**
```json
{
  "message": "Notification marked as read",
  "notification": {
    "id": "uuid",
    "read_at": "2026-03-23T06:00:00.000000Z"
  }
}
```

### Mark all notifications as read — `POST /api/notifications/read-all`

**Response `200`:**
```json
{
  "message": "All notifications marked as read",
  "marked_count": 3
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

Permission failures return `403`.
Authentication failures return `401`.

---

## Running Tests

```bash
php artisan test
php artisan test --filter=NotificationTest
php artisan test --filter=EloquentOrmTest
```

---

## How to Use the API (Step by Step)

1. Start the app:
```bash
php artisan migrate
php artisan db:seed
php artisan serve
```
2. Register account: `POST /api/register`.
3. Login: `POST /api/login`, then copy `token`.
4. Add header: `Authorization: Bearer YOUR_TOKEN`.
5. (Optional) Set role: `POST /api/assign-role` with `{ "role": "admin" }`.
6. Create task: `POST /api/tasks`.
7. List tasks: `GET /api/tasks`.
8. Filter tasks: `GET /api/tasks?search=alice&min_age=18&max_age=30`.
9. Check notifications: `GET /api/notifications`.
10. Mark one notification: `POST /api/notifications/{id}/read`.
11. Mark all notifications: `POST /api/notifications/read-all`.

### Common Issues

- `401 Unauthorized`: Missing/invalid Bearer token.
- `403 Forbidden`: Account role has no permission for that action.
- `422 Unprocessable Content`: Request JSON/body fails validation.
- HTML response instead of JSON: Missing `Accept: application/json` header or wrong URL.

---

## Project Structure

```
app/
  Http/
    Controllers/AuthController.php          # Register/login/logout/assign role
    Controllers/TaskController.php          # Task CRUD + Eloquent query filtering + notification trigger
    Controllers/NotificationController.php  # Notification inbox endpoints
    Requests/StoreTaskRequest.php           # Create validation
    Requests/UpdateTaskRequest.php          # Update validation
  Notifications/TaskActivityNotification.php  # Database notification payload
  Models/Task.php                           # Eloquent model + scopes + belongsTo User
  Models/User.php                           # Auth model + hasMany Tasks
database/
  migrations/                        # users/tasks/sanctum/spatie/notifications tables
  migrations/2026_03_23_170100_add_user_id_to_tasks_table.php  # Task owner relationship
  seeders/RoleSeeder.php             # admin/user roles and task permissions
routes/
  api.php                            # API route definitions
```

---

## License

MIT
