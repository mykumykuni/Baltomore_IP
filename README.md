# Baltomore IP - Laravel Tasks API

A Laravel REST API for managing task/person records with:

- Sanctum token authentication
- Role and permission authorization (Spatie)
- Database notifications for task activity
- Eloquent ORM relationships and query filtering
- File management per task (upload, list, download, delete)

## Tech Stack

| Item | Value |
|---|---|
| Framework | Laravel 12 |
| Auth | Laravel Sanctum |
| Authorization | spatie/laravel-permission |
| Database | SQLite by default (or MySQL/PostgreSQL) |
| Testing | PHPUnit/Pest via `php artisan test` |

## Requirements

| Requirement | Version |
|---|---|
| PHP | >= 8.2 |
| Composer | Latest stable |

## Quick Setup

| Step | Command |
|---|---|
| Install dependencies | `composer install` |
| Create env file | `copy .env.example .env` (Windows) |
| Generate app key | `php artisan key:generate` |
| Run migrations | `php artisan migrate` |
| Seed roles/permissions | `php artisan db:seed` |

## Run the API

| Scenario | Command |
|---|---|
| Default | `php artisan serve` |
| If port 8000 is busy | `php artisan serve --host=127.0.0.1 --port=8001` |

Base API URL:

- `http://127.0.0.1:8000/api`
- or `http://127.0.0.1:8001/api` if you use port 8001

## Required Headers

| Header | Value |
|---|---|
| Accept | `application/json` |
| Content-Type | `application/json` (JSON endpoints only) |
| Authorization | `Bearer YOUR_TOKEN` (protected endpoints) |

For file upload endpoint (`POST /tasks/{task}/files`), use `form-data` body and do not manually set `Content-Type`.

## Role and Permission Matrix

| Action | Permission |
|---|---|
| View tasks/files | `view tasks` |
| Create task/upload file | `create tasks` |
| Edit task | `edit tasks` |
| Delete task/delete file | `delete tasks` |

## Endpoint Reference

### Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | `/register` | Register user and return token |
| POST | `/login` | Login and return token |
| POST | `/logout` | Revoke current token |
| POST | `/assign-role` | Assign role to current user (`admin` or `user`) |

### Tasks

| Method | Endpoint | Description |
|---|---|---|
| GET | `/tasks` | List tasks |
| POST | `/tasks` | Create task |
| GET | `/tasks/{task}` | Get one task |
| PATCH/PUT | `/tasks/{task}` | Update task |
| DELETE | `/tasks/{task}` | Delete task |

### Task Query Parameters (`GET /tasks`)

| Query | Type | Description |
|---|---|---|
| `search` | string | Matches task `name` or `email` |
| `min_age` | integer | Filters `age >= min_age` |
| `max_age` | integer | Filters `age <= max_age` |

### Notifications

| Method | Endpoint | Description |
|---|---|---|
| GET | `/notifications` | List notifications + unread count |
| POST | `/notifications/{id}/read` | Mark one notification as read |
| POST | `/notifications/read-all` | Mark all unread notifications as read |

### File Management (Activity 7)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/tasks/{task}/files` | List files of a task |
| POST | `/tasks/{task}/files` | Upload a file to a task |
| GET | `/tasks/{task}/files/{taskFile}/download` | Download file |
| DELETE | `/tasks/{task}/files/{taskFile}` | Delete file |

## Request Body Reference

### Register (`POST /register`)

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### Login (`POST /login`)

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

### Assign role (`POST /assign-role`)

```json
{
  "role": "admin"
}
```

### Create task (`POST /tasks`)

```json
{
  "name": "Jane Smith",
  "age": 25,
  "birthdate": "2000-01-01",
  "email": "jane@example.com"
}
```

### Update task (`PATCH /tasks/{task}`)

```json
{
  "age": 26
}
```

### Upload file (`POST /tasks/{task}/files`)

Use `form-data`:

| Key | Type | Value |
|---|---|---|
| `file` | File | Choose local file |

## What Happens Internally

| Feature | Behavior |
|---|---|
| Eloquent ownership | Task is created using authenticated user relation (`user_id`) |
| Task listing | Includes eager-loaded `user` and `files` metadata |
| Notifications | Task create/update/delete generates database notifications |
| File storage | Uploaded files are stored on local disk under `storage/app/private/tasks/{task_id}` |
| File cleanup | Deleting a file record removes the physical file |

## End-to-End Testing Flow

| Step | Action | Endpoint/Command |
|---|---|---|
| 1 | Run app | `php artisan migrate`, `php artisan db:seed`, `php artisan serve --host=127.0.0.1 --port=8001` |
| 2 | Register | `POST /register` |
| 3 | Login and copy token | `POST /login` |
| 4 | Set Bearer token header | `Authorization: Bearer YOUR_TOKEN` |
| 5 | Create task | `POST /tasks` |
| 6 | Upload file | `POST /tasks/{task}/files` (form-data key `file`) |
| 7 | List files | `GET /tasks/{task}/files` |
| 8 | Download file | `GET /tasks/{task}/files/{taskFile}/download` |
| 9 | Delete file (admin) | `DELETE /tasks/{task}/files/{taskFile}` |
| 10 | Check notifications | `GET /notifications` |

## Automated Tests

| Scope | Command |
|---|---|
| Full test suite | `php artisan test` |
| Notifications only | `php artisan test --filter=NotificationTest` |
| Eloquent ORM only | `php artisan test --filter=EloquentOrmTest` |
| File management only | `php artisan test --filter=FileManagementTest` |

## Common Errors

| Status | Cause | Fix |
|---|---|---|
| 401 Unauthorized | Missing/invalid token | Re-login and send `Authorization: Bearer YOUR_TOKEN` |
| 403 Forbidden | Missing permission | Assign correct role/permissions |
| 404 Not Found | Wrong task/file id or wrong port | Create task first, use real IDs, confirm server/port |
| 422 Unprocessable Content | Invalid body/validation | Check required fields and formats |
| HTML response | Missing JSON header or wrong URL | Add `Accept: application/json`, use `/api/...` |

## Project Structure

```text
app/
  Http/
    Controllers/
      AuthController.php
      NotificationController.php
      TaskController.php
      TaskFileController.php
  Models/
    User.php
    Task.php
    TaskFile.php
  Notifications/
    TaskActivityNotification.php
database/
  migrations/
  seeders/
routes/
  api.php
```

## License

MIT
