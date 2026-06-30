# AMS_UP — Evaluation Web App (PHP)

Server-rendered PHP app for the teacher-evaluation feature. Replaces the old
Flutter-web client. The browser talks **only to this PHP app** (same origin);
PHP calls the Go API server-side with cURL — so **there is no CORS to configure**.

## Pages
- `login.php` — sign in (`POST /auth/login`, then `/auth/me` for role + ids)
- `student.php` — teachers/subjects to evaluate (window gate → questions → semester → study-plans → submitted check)
- `evaluate.php` — 1–10 score form, submits one `/evaluation-results` row per question
- `admin.php` — live results grouped by teacher → subject → class, with evaluated/expected counts
- `report.php` — printable report → **browser print → Save as PDF**
- `questions.php` — question CRUD (`/evaluation-questions`)

## Structure (MVC)
The page URLs above **are** the controllers (PHP's page-per-route — no router
needed). Each one handles the request, then renders a view.
- **Controllers** — root `*.php` (`login.php`, `student.php`, …). Each does
  `require config.php`, processes the request, sets variables, `require`s a view.
- **Models** — `models/` (`api.php` transport, `auth.php` guards + `/auth/me`,
  `evaluation.php` semesters/study-plans/aggregation). All data access + domain
  logic. `config.php` wires session + API base + the model layer.
- **Views** — `views/` templates (one per page) + `views/layout/` shared
  header/footer. `helpers.php` holds `esc()`.

## Run (XAMPP)
Either drop this folder in `C:\xampp\htdocs\` and open
`http://localhost/webapp/`, or use PHP's built-in server:

```bash
C:\xampp\php\php.exe -S localhost:8000 -t webapp
```
Then open http://localhost:8000/

## Configure the API
Defaults to `https://api.phetsamone.xyz/api/v1`. Override with an env var:

```bash
set API_URL=http://localhost:3000/api/v1   # then start php
```

## Backend prerequisites (unchanged, server-side)
- An **admin account** exists (for `admin.php` / `report.php` / `questions.php`).
- An open `open_evalu` window (gates the student form).
- The gateway `LEGACY_*` env is set so `/study-plans` + `/semasters` return data.

No CORS setup needed — calls are server-to-server.
