<?php

namespace App\Http\Controllers;

/**
 * PATHFit Faculty dashboard — WI-OCA-04 assigns the PATHFit Faculty Member
 * (not OCA) the authority to review and grant/deny a student-artist's PATHFit
 * exemption request, and to withhold the grade otherwise. A faculty member
 * only sees the exemption applications students named them on
 * (applications.pathfit_faculty_id, set at submission time) and acts on them
 * through the same ApplicationController::review() endpoint admin uses,
 * scoped to approve/reject on just their own assigned applications.
 */
class PathfitFacultyDashboardController extends Controller
{
    public function index()
    {
        $faculty = current_user();
        $pdo = getDB();

        $stmt = $pdo->prepare(
            "SELECT a.*, u.first_name, u.last_name, u.id_number, u.course
             FROM applications a
             JOIN application_types t ON t.type_id = a.type_id
             JOIN users u ON u.user_id = a.user_id
             WHERE t.code = 'pathfit_exemption' AND a.pathfit_faculty_id = :fid
             ORDER BY a.submitted_at DESC"
        );
        $stmt->execute(['fid' => $faculty['user_id']]);
        $applications = $stmt->fetchAll();

        $documentsByApp = [];
        $profilesByUser = [];
        if ($applications) {
            $ids = array_column($applications, 'application_id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM application_documents WHERE application_id IN ($placeholders)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $doc) {
                $documentsByApp[$doc['application_id']][] = $doc['document_type'];
            }

            $userIds = array_unique(array_column($applications, 'user_id'));
            $uPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
            $stmt = $pdo->prepare(
                "SELECT p.*, COUNT(pt.id) AS talent_count
                 FROM performer_profiles p LEFT JOIN performer_talents pt ON pt.profile_id = p.profile_id
                 WHERE p.user_id IN ($uPlaceholders)
                 GROUP BY p.profile_id"
            );
            $stmt->execute($userIds);
            foreach ($stmt->fetchAll() as $p) {
                $profilesByUser[(int) $p['user_id']] = $p;
            }
        }

        foreach ($applications as &$app) {
            $profile = $profilesByUser[(int) $app['user_id']] ?? null;
            $docTypes = $documentsByApp[$app['application_id']] ?? [];
            $app['eligibility'] = evaluate_application_eligibility('pathfit_exemption', $profile, $docTypes);
            $app['justification'] = generate_pathfit_justification(
                trim($app['first_name'] . ' ' . $app['last_name']),
                $profile['troupe_name'] ?? null,
                $app['eligibility']
            );
        }
        unset($app);

        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        foreach ($applications as $app) {
            if (in_array($app['status'], ['Pending', 'Under Review', 'Evaluation'], true)) {
                $pendingCount++;
            } elseif ($app['status'] === 'Approved') {
                $approvedCount++;
            } elseif ($app['status'] === 'Rejected') {
                $rejectedCount++;
            }
        }

        $fullName = trim($faculty['first_name'] . ' ' . $faculty['last_name']);
        $initials = strtoupper(mb_substr($faculty['first_name'], 0, 1) . mb_substr($faculty['last_name'], 0, 1));

        $pageTitle = 'PATHFit Faculty Dashboard';

        return view('pages.pathfit_faculty_dashboard', get_defined_vars());
    }
}
