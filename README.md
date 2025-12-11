Guide:

Run with Docker

Build and start both services (foreground):

```bash
docker compose up --build
```

Build and start both services (detached):

```bash
docker compose up --build -d
```

Stop and remove containers:

```bash
docker compose down
```

View logs:

```bash
docker compose logs -f backend
docker compose logs -f frontend
```

Access:
- Frontend: http://localhost:8001
- Backend API: http://localhost:8000

Manual run (no Docker)

- Start backend (PHP built-in server for quick testing):

```bash
php -S localhost:8000 -t backend/public
```

- Start frontend (static server):

```bash
php -S localhost:8001 -t frontend
```

If frontend and backend run on different origins, edit `frontend/js/app.js` and set `apiBase` to the backend origin, for example:

```js
const apiBase = 'http://localhost:8000';
```

API Endpoints (JSON)
- `GET /users` — list users
- `POST /users` — create user with `addresses` array
- `GET /users/{id}` — get user and addresses
- `PUT /users/{id}` — update user (provide `addresses` to replace)
- `DELETE /users/{id}` — delete user
- `GET /users/{id}/addresses` — list addresses for user
- `POST /users/{id}/addresses` — add address for user
- `GET /addresses/{id}` — get single address
- `PUT /addresses/{id}` — update address
- `DELETE /addresses/{id}` — delete address

Validation summary
- `email`: required, valid format, unique
- `first_name`, `last_name`: required, minimum 4 characters
- `mobile_number`: required, numeric only
- `birth_date`: required, `YYYY-MM-DD`, year >= 1950 and not in the future
- `addresses`: at least one on create; `barangay` and `city` required and min 3 chars; `user_id` must exist

Files of interest
- `backend/database.sql` — database schema
- `backend/src/Validator.php` — validation logic
- `backend/public/index.php` — API router and CORS handling
- `frontend/index.html`, `frontend/js/app.js` — UI and API calls
- `docker-compose.yml`, `backend/Dockerfile`, `frontend/Dockerfile`

Reset DB

Remove the SQLite file at `backend/data/db.sqlite` to reset the database (it will be recreated on next run).
