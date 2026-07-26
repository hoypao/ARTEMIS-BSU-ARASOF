# Trainer Role — Build Prompt for ARTEMIS

Paste everything below this line as-is into a new session/tool if you want an AI (or a developer) to build this. It's self-contained. Run it **inside the `artemis-laravel` project folder itself** (not a blank workspace) — it needs to read real files, not just this description of them.

---

## Step 0 — before writing any code, read these files in full

Do not design the Trainer dashboard, controllers, or schema from the descriptions below alone. Open and read these first, and match what they actually do:

- `resources/views/pages/admin_dashboard.blade.php` and `resources/views/pages/student_dashboard.blade.php` — copy their layout, Tailwind classes, and component structure exactly for the new `trainer_dashboard.blade.php`. Do not invent a new visual style.
- `app/Http/Controllers/StudentDashboardController.php` and `app/Http/Controllers/AdminOpsController.php` — copy their structure (PDO queries, `get_defined_vars()`, JSON action-handler pattern) for the new Trainer controllers.
- `app/Http/Middleware/EnsureRole.php`, `app/Http/Controllers/AuthController.php`, `routes/web.php` — read these before touching them; the two bugs described below are inside them.
- `app/Support/ui_helpers.php` — reuse `status_badge_html()` / progress-tracker helpers instead of writing new ones.

If anything below conflicts with what you find in the actual files, the actual files win — ask before proceeding rather than guessing.

---

## Context

ARTEMIS is a Laravel 13 port of a legacy plain-PHP system for the BatStateU ARASOF-Nasugbu Office of Culture and Arts (OCA). It manages 6 application types (Audition/Recruitment, Stipend, PATHFit Exemption, BANTOG Recognition, External Invitation, Appeal Admission) for Resident Performing Arts Group (RPAG) members, built against BatStateU's Culture and Arts Development Manual and its Work Instructions (WI-OCA-01 through WI-OCA-10).

**Conventions already established in the codebase — follow these exactly, don't introduce new patterns:**

- No Eloquent models or query builder — raw PDO via `getDB()` (defined in `app/Support/helpers.php`), prepared statements everywhere.
- Business logic lives in `app/Support/*.php` as plain function libraries (autoloaded via `composer.json`'s `files` array), not classes/services.
- Controllers are thin: fetch via PDO, call Support functions, pass everything to the Blade view via `get_defined_vars()`.
- Role gating via `Route::middleware('role:xxx')` groups in `routes/web.php`, enforced by `app/Http/Middleware/EnsureRole.php`.
- Every business rule cites its source in a comment: `Art. <Roman numeral> Sec. <n>` for the Manual, `WI-OCA-<nn>` for Work Instructions.
- Single-file Blade views under `resources/views/pages/`, Tailwind, matching the existing dashboards' visual language (red/gold BatStateU palette, card-based layout — copy the pattern from `admin_dashboard.blade.php` / `student_dashboard.blade.php`).
- JSON POST endpoints follow the `$input = $request->json()->all() ?: []` pattern used throughout `AdminOpsController`.

Existing `users.role` values: `student`, `admin`. This prompt adds a third: `trainer`.

## Goal

RPAG trainers currently have no system access — OCA admin staff enter trainer evaluation scores, BANTOG endorsements, and stipend hour verifications on their behalf, from paper. Give trainers their own login so they do these directly.

## MVP scope — build exactly these 4, nothing more without checking back

1. **My RPAG Roster** — trainer sees the members of their assigned discipline/group (from `performer_profiles` + `performer_talents` + `talent_categories`), read-only.
2. **Audition Rating** — for `audition_recruitment` applications in their discipline, trainer records a rating and pass/fail (WI-OCA-03: "Audition Criteria for the Selection of Culture and Arts Performers," BatStateU-FO-OCA-02). *We don't have the actual FO-OCA-02 form's fields on hand — use a simple numeric score (0–100) + remarks + pass/fail until the real rubric can be confirmed.*
3. **BANTOG Endorsement** — trainer digitally endorses a `bantog_recognition` application from their trainee, standing in for the paper "Certificate of Training" step (Manual Art. VIII Sec. 22-e.8, Sec. 25-A.3).
4. **Attendance / Hours Logging** — trainer logs per-session hours for their RPAG members. This becomes the verification source for `applications.hours_claimed` on stipend applications, which today is 100% student self-report (Art. VI Sec. 17-B.10 already ties trainer-certified hours to payment — this just brings that into ARTEMIS instead of leaving it on paper).

## Proposed schema changes

New table `trainer_profiles` (mirrors `performer_profiles`):
```sql
trainer_id      INT PK AUTO_INCREMENT
user_id         INT FK -> users.user_id, UNIQUE
category_id     INT FK -> talent_categories.category_id   -- primary discipline/RPAG group
bio             TEXT NULL
photo_path      VARCHAR(255) NULL
status          VARCHAR(20) DEFAULT 'active'
created_at      TIMESTAMP
```

New table `audition_ratings`:
```sql
rating_id       INT PK AUTO_INCREMENT
application_id  INT FK -> applications.application_id
trainer_id      INT FK -> users.user_id
score           DECIMAL(5,2) NULL
decision        ENUM('Pass','Fail') NULL
remarks         TEXT NULL
rated_at        TIMESTAMP
UNIQUE (application_id, trainer_id)
```

New table `trainer_attendance_logs`:
```sql
log_id             INT PK AUTO_INCREMENT
trainer_id         INT FK -> users.user_id
performer_user_id  INT FK -> users.user_id
session_date        DATE
hours               DECIMAL(4,2)
remarks             VARCHAR(255) NULL
logged_at           TIMESTAMP
```

Extend `applications` (for BANTOG endorsement):
```sql
ADD trainer_endorsed_by  INT NULL FK -> users.user_id
ADD trainer_endorsed_at  TIMESTAMP NULL
```

Extend `trainer_evaluations`:
```sql
ADD trainer_user_id INT NULL FK -> users.user_id
-- links to a real login when the trainer being evaluated has one;
-- keep the existing `trainer_name` text column for one-off/guest judges who don't.
```

## Required changes to EXISTING files (not just new ones)

- **`app/Http/Middleware/EnsureRole.php`** — the wrong-role fallback is currently a hardcoded `$user['role'] === 'admin' ? 'admin.dashboard' : 'student.dashboard'` ternary. With 3 roles this breaks: a trainer hitting a student-only route gets bounced to `student.dashboard`, which they also can't access → redirect loop. Change to a role→route map.
- **`app/Http/Controllers/AuthController.php::login()`** — same problem, the post-login redirect is `... ? 'admin.dashboard' : 'home'`. Needs a third branch for `trainer.dashboard`.
- **`routes/web.php`** — add:
  ```php
  Route::middleware('role:trainer')->group(function () {
      Route::get('/trainer/dashboard', [TrainerDashboardController::class, 'index'])->name('trainer.dashboard');
      Route::post('/trainer/audition/rate', [TrainerOpsController::class, 'rateAudition'])->name('trainer.audition.rate');
      Route::post('/trainer/bantog/endorse', [TrainerOpsController::class, 'endorseBantog'])->name('trainer.bantog.endorse');
      Route::post('/trainer/attendance/log', [TrainerOpsController::class, 'logAttendance'])->name('trainer.attendance.log');
  });
  ```
- **`app/Http/Controllers/HomeController.php`** — currently only has an `$isStudent` variant of the public landing page. Recommend NOT touching this for MVP — just send `trainer` role straight to `trainer.dashboard` on login instead of `home`.
- **`database/seeders/DatabaseSeeder.php`** and the demo-account table in **`README.md`** — add a demo trainer account alongside the existing admin/student ones.

## New files to create

- `app/Http/Controllers/TrainerDashboardController.php` — mirrors `StudentDashboardController`: loads the trainer's profile, roster, pending audition applications for their discipline, pending BANTOG endorsements, recent attendance logs.
- `app/Http/Controllers/TrainerOpsController.php` — mirrors `AdminOpsController`: the 3 POST actions (rate audition, endorse BANTOG, log attendance), PDO prepared statements, same validation style as the rest of that file.
- `resources/views/pages/trainer_dashboard.blade.php` — single-file Blade view, same visual system as the other two dashboards.

## Constraints

Keep using PDO/`getDB()` — do not introduce Eloquent models. Cite the Manual/WI section for every new business rule in a comment, same as the rest of the codebase. Additive only — don't touch the `role:student` / `role:admin` groups or any existing table's existing columns. If you extend `tests/smoke.cjs`, add trainer login/route checks following its existing pattern.

## Open product decisions — confirm these, don't just assume an answer

1. Should BANTOG endorsement **replace** the current "upload an endorsement document" requirement in `app/Support/document_requirements.php`, or **coexist** with it (trainer clicks Endorse AND a file can still be attached)?
2. Should a trainer be able to rate/endorse applicants **outside** their own discipline (e.g., covering for another trainer), or strictly locked to their assigned `category_id`?
3. Does `trainer_attendance_logs` data **block** a stipend submission when hours don't match, or just show OCA a "claimed vs. logged" comparison for staff to judge? (Recommend the comparison-only approach for MVP — blocking risks false rejections from incomplete logging.)
