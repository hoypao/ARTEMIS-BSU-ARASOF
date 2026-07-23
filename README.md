# ARTEMIS — Laravel Edition

**Artistic Resource and Talent Enterprise Management using Intelligent Systems**
Office of Culture and Arts, BatStateU ARASOF-Nasugbu

This is the **Laravel 13** port of the original plain-PHP ARTEMIS system. The UI is
pixel-identical to the original (same Tailwind CSS, same HTML, same JavaScript) —
verified with before/after full-page screenshot comparison on every page. What
changed is the architecture underneath: routing, controllers, middleware, and
Blade views now follow the Laravel MVC structure.

## Requirements

- **PHP 8.3** — installed at `C:\xampp\php83` (separate from XAMPP's PHP 8.0,
  which the legacy system keeps using)
- **XAMPP MySQL** running, with the existing `artemis_db` database
  (both systems share the same database — same accounts, same data)
- Composer (already used to install dependencies)

## Running the app

```powershell
cd C:\xampp\htdocs\artemis-laravel
C:\xampp\php83\php.exe artisan serve
```

Then open **http://localhost:8000**

> Use `localhost`, not `127.0.0.1` — the Google reCAPTCHA site key is registered
> for the `localhost` domain only. (Everything else works on either host.)

Demo accounts (same database as the legacy system):

| Role    | Email                        | Password   |
|---------|------------------------------|------------|
| Admin   | admin@batstate-u.edu.ph      | admin123   |
| Student | 23-75900@g.batstate-u.edu.ph | student123 |

*(Note: the login page's "Student Demo" quick-fill button still inserts the
original seed email `juan.delacruz@g.batstate-u.edu.ph`; that account's email
was changed in the live database to `23-75900@g.batstate-u.edu.ph`.)*

## How the legacy code maps to Laravel

| Legacy (plain PHP)                  | Laravel                                          |
|-------------------------------------|--------------------------------------------------|
| `index.php`, `login.php`, … (pages) | `app/Http/Controllers/*` + `resources/views/pages/*.blade.php` |
| `actions/**/*.php` (form/AJAX handlers) | Controller methods behind named POST routes  |
| `includes/*.php` (business logic)   | `app/Support/*.php` (autoloaded function libraries) |
| `includes/auth.php` require_role()  | `role:student` / `role:admin` route middleware (`app/Http/Middleware/EnsureRole.php`) |
| Custom CSRF token (`csrf_token` field) | Laravel CSRF, extended to accept the legacy field name (`app/Http/Middleware/VerifyCsrfToken.php`) |
| `config/database.php` getDB()       | Laravel DB connection (`getDB()` shim returns its PDO) |
| `assets/`, `manifest.json`, uploads | `public/assets/`, `public/manifest.json`, `public/uploads/documents/` |
| `.env` (custom parser)              | Laravel `.env` (same variable names for MAIL_* / RECAPTCHA_*) |

Route map (old URL → new URL):

- `index.php` → `/` · `login.php` → `/login` · `events.php` → `/events`
- `track_application.php` → `/track` · `ask_spartan.php` → `/ask-spartan`
- `reset_password.php` → `/reset-password`
- `student_dashboard.php` → `/student/dashboard`
- `admin_dashboard.php` → `/admin/dashboard`
- `actions/...` → matching POST routes (see `routes/web.php`)

Intelligent-systems modules ported verbatim into `app/Support/`: eligibility
engine, PATHFit equivalency matrix, RPAG talent matcher, trainer level
equivalency engine, faculty non-compliance escalation, QEO KPI tracker, and the
Ask Spartan assistant.

## Notes

- **Sessions** are Laravel file sessions (`SESSION_DRIVER=file`) — no extra
  tables were added to `artemis_db`; the schema is untouched.
- **MySQL strict mode is off** (`config/database.php` → `'strict' => false`)
  for parity with the legacy PDO connection: the dashboard's `GROUP BY`
  aggregate queries rely on the server-default sql_mode.
- **Email** still goes through PHPMailer + Gmail SMTP, exactly like the legacy
  `includes/mailer.php` (same `.env` variables).
- The legacy system remains untouched at `C:\xampp\htdocs\ARTEMIS (1)`
  (and its Apache-served clone at `http://localhost/ARTEMIS`).

## Tests

```powershell
cd C:\xampp\htdocs\artemis-laravel
node tests\smoke.cjs
```

Read-only smoke suite (ported from the legacy `tests/smoke.js`): lints all PHP,
loads every public page checking for console/page errors, verifies guest
guards, exercises the Ask Spartan endpoint, logs in with the demo accounts, and
cross-checks the six application types' document-requirement forms against
`app/Support/document_requirements.php`. Logged-in checks are skipped
automatically when reCAPTCHA keys are configured (scripted logins can't solve
the widget) — blank the two `RECAPTCHA_*` keys in `.env` and restart the server
to run the full suite. Latest full run: **26 passed, 0 failed**.
