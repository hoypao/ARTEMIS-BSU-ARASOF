<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Ports of track_application.php (public tracker page) and
 * actions/applications/track_lookup_action.php (public JSON lookup that
 * requires BOTH the application code and the student number to match).
 *
 * Also handles Admission Appeal reference codes (APPEAL-00001, Art. IV Sec.
 * 11) the same way — the appeal form has no id_number to check against
 * (the appellant isn't an ARTEMIS student yet), so the second field is
 * matched against the appeal's email instead when the code has that prefix.
 */
class TrackController extends Controller
{
    /** track_application.php */
    public function page()
    {
        $user = current_user();
        $portalUrl = route('login');
        if ($user) {
            $portalUrl = route($user['role'] === 'admin' ? 'admin.dashboard' : 'student.dashboard');
        }

        $pageTitle = 'Track Application';

        return view('pages.track_application', compact('user', 'portalUrl', 'pageTitle'));
    }

    /** actions/applications/track_lookup_action.php (JSON) */
    public function lookup(Request $request)
    {
        // Public, unauthenticated, two-field lookup — throttle per IP so it
        // can't be used to enumerate application codes or appeal emails.
        // Keyed separately from AuthController's login lockout (that one is
        // per-email and DB-backed for user-facing "N attempts left"
        // messaging); this is a lower-stakes, IP-keyed limiter using
        // Laravel's built-in cache-backed RateLimiter — no schema needed.
        $rateLimitKey = 'track-lookup:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'error' => 'Too many lookup attempts. Please try again in ' . max(1, (int) ceil($seconds / 60)) . ' minute(s).',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        $input = $request->json()->all() ?: [];
        $code = trim($input['application_code'] ?? '');
        $idOrEmail = trim($input['id_number'] ?? '');

        if ($code === '' || $idOrEmail === '') {
            return response()->json(['error' => 'Please enter both the code and your ID number or email.'], 400);
        }

        if (stripos($code, 'APPEAL-') === 0) {
            return $this->lookupAppeal($code, $idOrEmail);
        }

        $pdo = getDB();
        $stmt = $pdo->prepare(
            'SELECT a.application_code, a.status, a.current_stage, a.remarks, a.submitted_at, t.name AS type_name, t.code AS type_code,
                    u.first_name, u.last_name, u.id_number, u.course
             FROM applications a
             JOIN application_types t ON t.type_id = a.type_id
             JOIN users u ON u.user_id = a.user_id
             WHERE a.application_code = :code AND u.id_number = :id_number
             LIMIT 1'
        );
        $stmt->execute(['code' => $code, 'id_number' => $idOrEmail]);
        $app = $stmt->fetch();

        if (!$app) {
            return response()->json(['error' => 'No application found matching that code and student number.'], 404);
        }

        return response()->json([
            'kind' => 'application',
            'application_code' => $app['application_code'],
            'type_name' => $app['type_name'],
            'type_code' => $app['type_code'],
            'status' => $app['status'],
            'stage' => (int) $app['current_stage'],
            'remarks' => $app['remarks'],
            'submitted_at' => format_date($app['submitted_at']),
            'name' => $app['first_name'] . ' ' . $app['last_name'],
            'student_id' => $app['id_number'],
            'course' => $app['course'],
        ]);
    }

    /** APPEAL-00001 lookup — second factor is email, since appellants aren't ARTEMIS users. */
    private function lookupAppeal(string $code, string $email)
    {
        $appealId = (int) ltrim(substr($code, 7), '0');
        if ($appealId <= 0) {
            return response()->json(['error' => 'No admission appeal found matching that reference and email.'], 404);
        }

        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM admission_appeals WHERE appeal_id = :id AND LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute(['id' => $appealId, 'email' => $email]);
        $appeal = $stmt->fetch();

        if (!$appeal) {
            return response()->json(['error' => 'No admission appeal found matching that reference and email.'], 404);
        }

        return response()->json([
            'kind' => 'appeal',
            'reference' => 'APPEAL-' . str_pad((string) $appeal['appeal_id'], 5, '0', STR_PAD_LEFT),
            'full_name' => $appeal['full_name'],
            'discipline' => $appeal['discipline'],
            'secondary_school' => $appeal['secondary_school'],
            'campus' => $appeal['campus'],
            'status' => $appeal['status'],
            'remarks' => $appeal['remarks'],
            'submitted_at' => format_date($appeal['submitted_at']),
        ]);
    }
}
