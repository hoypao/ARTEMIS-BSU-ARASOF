<?php
/**
 * Laravel ports of the legacy ARTEMIS helpers (config/database.php,
 * includes/functions.php, includes/auth.php). Same function names and
 * signatures, so the ported business logic and Blade views run unchanged.
 * e(), redirect(), csrf_token(), csrf_field() are NOT redefined here —
 * Laravel's own helpers cover them.
 */

use Illuminate\Support\Facades\DB;

/** Legacy getDB() — now hands back the PDO of Laravel's managed MySQL connection. */
function getDB(): PDO
{
    return DB::connection()->getPdo();
}

// ---------------------------------------------------------------------
// Session auth (legacy $_SESSION['user'] -> Laravel session store)
// ---------------------------------------------------------------------

function is_logged_in(): bool
{
    return !empty(session('user'));
}

function current_user(): ?array
{
    return session('user');
}

function login_user(array $userRow): void
{
    // New session ID on every login to prevent session fixation.
    session()->regenerate();
    session(['user' => [
        'user_id'    => (int) $userRow['user_id'],
        'role'       => $userRow['role'],
        'id_number'  => $userRow['id_number'],
        'email'      => $userRow['email'],
        'first_name' => $userRow['first_name'],
        'last_name'  => $userRow['last_name'],
        'course'     => $userRow['course'],
    ]]);
}

function logout_user(): void
{
    session()->invalidate();
    session()->regenerateToken();
}

// ---------------------------------------------------------------------
// Flash messages — read-and-clear semantics identical to the legacy app
// (a message survives until the next flash_get() for its type).
// ---------------------------------------------------------------------

function flash_set(string $type, string $message): void
{
    session(['flash.' . $type => $message]);
}

function flash_get(string $type): ?string
{
    return session()->pull('flash.' . $type);
}

// ---------------------------------------------------------------------
// Generic helpers (verbatim from includes/functions.php)
// ---------------------------------------------------------------------

function format_date(?string $datetime, string $format = 'M j, Y'): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '';
}

/** Relative "time ago" label for activity feeds (notifications) — falls back to a plain date past a week old. */
function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    if (!$ts) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 0) {
        return format_date($datetime);
    }
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return "{$m}m ago";
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return "{$h}h ago";
    }
    if ($diff < 604800) {
        $d = (int) floor($diff / 86400);
        return "{$d}d ago";
    }
    return format_date($datetime);
}

// e.g. APP-2026-001, APP-2026-002 ... — counts this year's applications so far and
// numbers the new one next; the year resets the sequence each January.
function generate_application_code(PDO $pdo): string
{
    $year = date('Y');
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM applications WHERE application_code LIKE :prefix"
    );
    $stmt->execute(['prefix' => "APP-{$year}-%"]);
    $next = ((int) $stmt->fetchColumn()) + 1;
    return sprintf('APP-%s-%03d', $year, $next);
}

/**
 * type_ids this user has at least one Approved application for — used to gate
 * RSVP on "By Invitation" events (events.requires_application_type_id).
 *
 * @return array<int, true> keyed by type_id for cheap isset() lookups
 */
function get_user_approved_application_type_ids(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        "SELECT DISTINCT type_id FROM applications WHERE user_id = :uid AND status = 'Approved'"
    );
    $stmt->execute(['uid' => $userId]);
    $ids = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $typeId) {
        $ids[(int) $typeId] = true;
    }
    return $ids;
}
