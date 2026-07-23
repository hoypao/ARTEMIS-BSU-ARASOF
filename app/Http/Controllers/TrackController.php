<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Ports of track_application.php (public tracker page) and
 * actions/applications/track_lookup_action.php (public JSON lookup that
 * requires BOTH the application code and the student number to match).
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
        $input = $request->json()->all() ?: [];
        $code = trim($input['application_code'] ?? '');
        $idNumber = trim($input['id_number'] ?? '');

        if ($code === '' || $idNumber === '') {
            return response()->json(['error' => 'Please enter both the application code and student number.'], 400);
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
        $stmt->execute(['code' => $code, 'id_number' => $idNumber]);
        $app = $stmt->fetch();

        if (!$app) {
            return response()->json(['error' => 'No application found matching that code and student number.'], 404);
        }

        return response()->json([
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
}
