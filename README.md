# AMS_UP — Evaluation Web App (PHP)

Server-rendered PHP app for the teacher-evaluation feature. Replaces the old
Flutter-web client. The browser talks **only to this PHP app** (same origin);
PHP calls the Go API server-side with cURL — so **there is no CORS to configure**.

## Pages (clean URLs, no .php)
`index.php` is a front controller: role-prefixed extension-less paths map to
the page controllers. Apache: `.htaccess` rewrites non-files to `index.php`.
nginx: `try_files $uri $uri/ /index.php?$query_string;`.

| URL | Controller | |
|---|---|---|
| `/login` | `login.php` | sign in (`POST /auth/login`, then `/auth/me`) |
| `/student` | `student.php` | teachers/subjects to evaluate |
| `/student/eval` | `evaluate.php` | 1–10 score form |
| `/student/guide` | `guide.php` | score bands + walkthrough video |
| `/admin` | `admin.php` | live results by teacher → subject → class |
| `/admin/report` | `report.php` | printable report → browser print → PDF |
| `/admin/report-bulk` | `report_bulk.php` | multi-class PDF |
| `/admin/questions` | `questions.php` | question CRUD |
| `/admin/window` | `window.php` | open/close the evaluation window |
| `/logout` | `logout.php` | |

Links are built with `url('student/eval')` (see `helpers.php`), which prefixes
the folder the app is mounted at, so it works at site root and under a subdir.

## Structure (MVC)
Each controller handles the request, then renders a view.
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
C:\xampp\php\php.exe -S localhost:8000 -t webapp webapp\index.php
```
(the trailing `index.php` is the router script — required for the clean URLs)
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
