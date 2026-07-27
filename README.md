# ARTEMIS — Laravel Edition

**Artistic Resource and Talent Enterprise Management using Intelligent Systems**
Office of Culture and Arts, BatStateU ARASOF-Nasugbu

ARTEMIS digitizes the OCA's application, evaluation, and monitoring workflows for
student-artists under RPAG (Recognized Performing Arts Group) — from admission
appeal through audition, stipend, PATHFit exemption, BANTOG recognition, event
attendance, and faculty-compliance escalation — across five account types:
Student, OCA Admin, Trainer/Coach, PATHFit Faculty, College Dean, and TAO Central.

It began as a **Laravel 13** port of an earlier plain-PHP prototype (routing,
controllers, middleware, and Blade views now follow the Laravel MVC structure),
and has since grown original features on top of that port — QR-based event
check-in, trainer audition rating, and the public admission-appeal channel —
that did not exist in the original prototype. Every non-obvious business rule in
the code is commented with its source citation (`Art. <Roman numeral> Sec. <n>`
for the Culture and Arts Development Manual, `WI-OCA-<nn>` for the corresponding
Work Instruction), cross-checked against the official BatStateU-QEO-OCA-03
documents.

## Features

**Public (no login required)**
- Landing page and public Events listing
- Application status tracker (by reference code, no account needed)
- Ask Spartan — a rule-based assistant that answers questions about OCA policy
- Admission Appeal — a documentary-evidence appeal form for prospective
  student-artists who did not initially qualify for admission (WI-OCA-02)

**Student**
- Submit any of 6 application types with their required documents: Audition/
  Recruitment, Stipend, PATHFit Exemption, BANTOG Recognition, External
  Invitation, and Appeal Admission
- Track submitted applications through every review stage
- RSVP to events (off-campus events require an extra travel-acknowledgment step)
- Event Check-In QR code — a personal QR shown on the dashboard that OCA staff
  scan at the venue to mark attendance automatically
- Self-report academic standing (auto-triggers/clears RPAG probation) and
  benefit-completion reports
- Profile photo upload

**OCA Admin**
- Multi-stage application review/approval pipeline for all 6 application types
- Events management (create/edit), attendance tracking, and a camera-based QR
  scanner to check students in at the venue
- Announcements, faculty non-compliance complaint intake, trainer evaluations
  (Trainer Level Equivalency), activity honoraria, BANTOG evaluator records,
  partner organization / MOA tracking, admission appeal review, academic
  support / student mentorship tracking, QEO KPI reporting, and system settings

**Trainer / Coach**
- Review applications submitted by assigned RPAG members and provide the
  required endorsement, score, and Pass/Fail recommendation

**PATHFit Faculty**
- Review PATHFit exemption applications assigned to them and provide their
  recommendation based on the applicant's eligibility

**College Dean**
- Review applications and requests forwarded to their college and provide the
  dean's recommendation or decision

**TAO Central**
- Review admission appeal applications forwarded by OCA and provide the final
  TAO recommendation or decision

## Tech Stack

- **Backend**: Laravel 13 on PHP 8.3, routed/authorized through
  Laravel's router and custom role middleware, but with **all business logic
  in raw PDO** (`app/Support/*.php` function libraries) — no Eloquent models
  or query builder anywhere in the app
- **Database**: MySQL (XAMPP), schema tracked via Laravel migrations
- **Frontend**: server-rendered Blade views, Tailwind CSS, Chart.js, Lucide icons
- **Email**: PHPMailer over Gmail SMTP
- **Security**: Google reCAPTCHA on public forms, rate-limited login attempts,
  CSRF protection
- **QR check-in**: `qrcode-generator` (client-side QR generation) +
  `jsQR` (camera-based decoding) — no external QR service or API

## Requirements

- **PHP 8.3** — installed at `C:\xampp\php83` (separate from XAMPP's PHP 8.0,
  which the legacy system keeps using)
- **XAMPP MySQL** running
- Composer (already used to install dependencies)

## First-time setup (fresh database)

```powershell
cd C:\xampp\htdocs\artemis-laravel
C:\xampp\php83\php.exe artisan migrate
```

This recreates the full schema (25 tables) from
`database/migrations/2026_07_25_000000_create_artemis_schema.php`. If you're
instead pointing at the existing dev copy of `artemis_db` (same accounts, same
data as below), skip this — the schema is already there.

## Running the app

```powershell
cd C:\xampp\htdocs\artemis-laravel
C:\xampp\php83\php.exe artisan serve
```

Then open **http://localhost:8000**

> `php artisan serve` is single-threaded on Windows (no `pcntl` extension, so it
> can't fork worker processes) — fine for solo development, but requests queue
> up one at a time under concurrent load. For a live multi-user demo, serve
> through XAMPP's Apache (already configured for this project's `.htaccess`)
> instead.

> Use `localhost`, not `127.0.0.1` — the Google reCAPTCHA site key is registered
> for the `localhost` domain only. (Everything else works on either host.)

Demo accounts (same database as the legacy system):

| Role            | Email                             | Password    |
|-----------------|------------------------------------|-------------|
| Admin           | admin@batstate-u.edu.ph            | admin123    |
| Student         | 23-75900@g.batstate-u.edu.ph        | student123  |
| Student         | maria.santos@g.batstate-u.edu.ph    | student123  |
| Student         | carlo.reyes@g.batstate-u.edu.ph     | student123  |
| Student         | ana.lim@g.batstate-u.edu.ph         | student123  |
| Trainer         | trainer@batstate-u.edu.ph          | trainer123  |
| Trainer         | liza.fernandez@batstate-u.edu.ph    | trainer123  |
| Trainer         | jerico.mendoza@batstate-u.edu.ph    | trainer123  |
| PATHFit Faculty | andrea.bautista@batstate-u.edu.ph   | pathfit123  |
| College Dean    | corazon.ibarra@batstate-u.edu.ph    | dean123     |
| TAO Central     | tao.central@batstate-u.edu.ph       | tao123      |

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

Routes with no legacy equivalent (added after the port, not part of the
original prototype):

- `/appeal/apply` — public Admission Appeal form (`AdmissionAppealController`)
- `/trainer/dashboard` — Trainer/Coach dashboard
- `/pathfit-faculty/dashboard` — PATHFit Faculty dashboard
- `/dean/dashboard` — College Dean dashboard
- `/tao/dashboard` — TAO Central admission-appeal review (`TaoDashboardController`)
- `/admin/events/checkin` — QR check-in scan endpoint
- `/trainer/audition/rate` — Audition Rating submission

Intelligent-systems modules ported verbatim into `app/Support/`: eligibility
engine, PATHFit equivalency matrix, RPAG talent matcher, trainer level
equivalency engine, faculty non-compliance escalation, QEO KPI tracker, and the
Ask Spartan assistant.

## Notes

- **Schema is tracked via Laravel migrations**
  (`database/migrations/2026_07_25_000000_create_artemis_schema.php`) — before
  this, schema changes were applied by hand with raw SQL directly against the
  live database, which made the DB structure hard to reproduce on another
  machine. Run `php artisan migrate` on a fresh database to recreate it; the
  live dev copy of `artemis_db` already has the migration marked as applied,
  so re-running `migrate` against it is a no-op and won't touch existing data.
- **Sessions** are Laravel file sessions (`SESSION_DRIVER=file`) — no session
  table is needed in the database.
- **MySQL strict mode is off** (`config/database.php` → `'strict' => false`)
  for parity with the legacy PDO connection: the dashboard's `GROUP BY`
  aggregate queries rely on the server-default sql_mode.
- **Email** still goes through PHPMailer + Gmail SMTP, exactly like the legacy
  `includes/mailer.php` (same `.env` variables).
- **QR event check-in requires a secure context.** `getUserMedia()` (the
  camera API the admin scanner uses) only works over HTTPS, or over plain HTTP
  on `localhost`/`127.0.0.1` for local dev. On any other hostname over plain
  HTTP, the scanner shows a clear inline error instead of failing silently —
  serve over real HTTPS before demoing this off `localhost`.
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
to run the full suite. Latest full run: **26 passed, 0 failed** (re-verified
2026-07-25).

This suite only covers the Student, Admin, and public-page flows above — it
does not yet exercise the Trainer, PATHFit Faculty, or College Dean
dashboards, nor the Admission Appeal form or QR check-in flow. Those have been
verified manually (live browser testing against real data), not by this
automated suite.
