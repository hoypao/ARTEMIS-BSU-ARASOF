<?php

namespace App\Http\Controllers;

/**
 * Port of admin_dashboard.php — queries and pre-computes everything up front
 * (eligibility checks, trainer levels, RPAG placement, PATHFit matrix,
 * faculty escalation, QEO KPIs), which the Blade view embeds as JS globals.
 * Route middleware `role:admin` replaces require_role().
 */
class AdminDashboardController extends Controller
{
    public function index()
    {
        $admin = current_user();
        $pdo = getDB();

        $stmt = $pdo->query(
            "SELECT a.*, t.name AS type_name, t.code AS type_code, u.first_name, u.last_name, u.id_number, u.course
             FROM applications a
             JOIN application_types t ON t.type_id = a.type_id
             JOIN users u ON u.user_id = a.user_id
             ORDER BY a.submitted_at DESC"
        );
        $applications = $stmt->fetchAll();

        $documentsByApp = [];
        $benefitByApp = [];
        if ($applications) {
            $ids = array_column($applications, 'application_id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $stmt = $pdo->prepare("SELECT * FROM application_documents WHERE application_id IN ($placeholders) ORDER BY uploaded_at");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $doc) {
                $documentsByApp[$doc['application_id']][] = ['file_name' => $doc['file_name'], 'file_path' => $doc['file_path'], 'document_type' => $doc['document_type']];
            }

            $stmt = $pdo->prepare("SELECT * FROM benefit_records WHERE application_id IN ($placeholders)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $b) {
                $benefitByApp[$b['application_id']] = $b;
            }
        }

        // Performer profiles keyed by user_id, with a talent count — feeds the
        // Eligibility Inference Checker (app/Support/eligibility_engine.php).
        $profilesByUser = [];
        $stmt = $pdo->query(
            "SELECT p.*, COUNT(pt.id) AS talent_count
             FROM performer_profiles p
             LEFT JOIN performer_talents pt ON pt.profile_id = p.profile_id
             GROUP BY p.profile_id"
        );
        foreach ($stmt->fetchAll() as $p) {
            $profilesByUser[(int) $p['user_id']] = $p;
        }

        // Applications with a real digital trainer endorsement on file (application_endorsements)
        // — these satisfy the "Trainer's Endorsement / Recommendation Letter" requirement exactly
        // like an uploaded endorsement letter would, so the eligibility checker below doesn't flag
        // a genuinely-endorsed application as missing that document.
        $endorsedAppIds = array_flip($pdo->query('SELECT DISTINCT application_id FROM application_endorsements')->fetchAll(\PDO::FETCH_COLUMN));

        $eligibilityByApp = [];
        foreach ($applications as $a) {
            $docTypes = array_column($documentsByApp[$a['application_id']] ?? [], 'document_type');
            if (isset($endorsedAppIds[$a['application_id']])) {
                $docTypes[] = ARTEMIS_DOCUMENT_CATALOG['endorsement'];
            }
            $profile = $profilesByUser[(int) $a['user_id']] ?? null;
            $eligibilityByApp[$a['application_id']] = evaluate_application_eligibility($a['type_code'], $profile, $docTypes);
        }

        // Trainer Level Equivalency Engine data (Art. VI Sec. 17).
        $trainerEvaluations = $pdo->query('SELECT * FROM trainer_evaluations ORDER BY created_at DESC')->fetchAll();
        $talentCategoriesList = $pdo->query('SELECT category_id, name FROM talent_categories ORDER BY name')->fetchAll();

        // Honoraria for one-off Resource Persons, Facilitators, and Judges (Art. VI Sec. 18).
        $activityHonoraria = $pdo->query('SELECT * FROM activity_honoraria ORDER BY activity_date DESC')->fetchAll();

        // BANTOG Evaluator Panel (Art. VIII Sec. 24).
        $bantogEvaluators = $pdo->query('SELECT * FROM bantog_evaluators ORDER BY academic_year DESC, evaluator_id')->fetchAll();

        // Collaboration with Culture and Arts Agencies and Organizations (Art. XIII).
        $partnerOrganizations = $pdo->query('SELECT * FROM partner_organizations ORDER BY updated_at DESC')->fetchAll();

        // Equipment & Materials Inventory / Procurement (Art. VII; Property Custodian duties, Art. III Sec. 6.C).
        $equipmentItems = $pdo->query('SELECT * FROM equipment_items ORDER BY updated_at DESC')->fetchAll();
        $procurementRequests = $pdo->query('SELECT * FROM procurement_requests ORDER BY updated_at DESC')->fetchAll();
        $equipmentConditionCounts = summarize_equipment_conditions($equipmentItems);

        // Phase 2: cross-campus resource sharing + RLSDDP loss/damage reports (Art. VII Sec. 20-C), both need the item name for display.
        $resourceShareRequests = $pdo->query(
            "SELECT s.*, e.item_name FROM resource_share_requests s JOIN equipment_items e ON e.equipment_id = s.equipment_id ORDER BY s.updated_at DESC"
        )->fetchAll();
        $equipmentLossReports = $pdo->query(
            "SELECT r.*, e.item_name FROM equipment_loss_reports r JOIN equipment_items e ON e.equipment_id = r.equipment_id ORDER BY r.updated_at DESC"
        )->fetchAll();

        // Admission Appeals for prospective students (Art. IV Sec. 11).
        $admissionAppeals = $pdo->query('SELECT * FROM admission_appeals ORDER BY submitted_at DESC')->fetchAll();

        // The appeal card is rendered client-side, so the shared PHP tracker is
        // pre-rendered once per stage and handed to JS as a lookup rather than
        // reimplemented there — one component, one set of markup.
        $appealTrackerHtml = [];
        foreach (range(1, count(ARTEMIS_APPEAL_CHAIN) + 1) as $stage) {
            $appealTrackerHtml[$stage] = admin_progress_tracker_html($stage, 'appeal_admission');
        }

        // Academic Support: students on probation + active mentorships (Art. V Sec. 15).
        $probationStudents = $pdo->query(
            "SELECT p.user_id, p.probation_reason, p.probation_started_at, u.first_name, u.last_name, u.id_number, u.course
             FROM performer_profiles p JOIN users u ON u.user_id = p.user_id
             WHERE p.probation_status = 'Probation' ORDER BY p.probation_started_at DESC"
        )->fetchAll();
        $activeMentorships = $pdo->query(
            "SELECT m.*, u.first_name, u.last_name, u.id_number
             FROM student_mentorships m JOIN users u ON u.user_id = m.student_user_id
             WHERE m.status = 'Active' ORDER BY m.assigned_at DESC"
        )->fetchAll();
        $studentRoster = $pdo->query("SELECT user_id, first_name, last_name, id_number FROM users WHERE role = 'student' ORDER BY last_name, first_name")->fetchAll();

        $pathfitMatrixJs = [];
        foreach (ARTEMIS_PATHFIT_MATRIX as $standard => $rows) {
            $pathfitMatrixJs[] = ['standard' => $standard, 'cultureArts' => $rows['culture_arts']];
        }

        // RPAG/Discipline Recommender data (app/Support/talent_matcher.php, Art. IV Sec. 12).
        $talentsByProfile = [];
        $stmt = $pdo->query(
            "SELECT pt.profile_id, tc.name AS category_name, pt.proficiency_level
             FROM performer_talents pt JOIN talent_categories tc ON tc.category_id = pt.category_id"
        );
        foreach ($stmt->fetchAll() as $row) {
            $talentsByProfile[$row['profile_id']][] = ['category_name' => $row['category_name'], 'proficiency_level' => $row['proficiency_level']];
        }

        $performersWithPlacement = [];
        $placementByUser = [];
        $stmt = $pdo->query(
            "SELECT p.*, u.first_name, u.last_name, u.id_number, u.course
             FROM performer_profiles p JOIN users u ON u.user_id = p.user_id
             ORDER BY u.last_name, u.first_name"
        );
        foreach ($stmt->fetchAll() as $p) {
            $talents = $talentsByProfile[$p['profile_id']] ?? [];
            $placement = recommend_rpag_placement($talents, $p);
            $placementByUser[(int) $p['user_id']] = $placement;
            $performersWithPlacement[] = [
                'name' => $p['first_name'] . ' ' . $p['last_name'],
                'studentId' => $p['id_number'],
                'course' => $p['course'],
                'troupe' => $p['troupe_name'],
                'activeMember' => (bool) $p['active_member'],
                'yearsActive' => $p['years_active'] !== null ? (int) $p['years_active'] : null,
                'placement' => $placement,
            ];
        }

        // Faculty Non-Compliance Pattern Flag data (WI-OCA-09).
        $facultyComplaints = $pdo->query('SELECT * FROM faculty_noncompliance_complaints ORDER BY created_at DESC')->fetchAll();

        // QEO KPI Tracker data (BatStateU-QEO-OCA-03).
        $qeoKpis = compute_qeo_kpis($pdo);

        // Settings tab — real values from the DB, not the client-only localStorage
        // placeholder the page used to fall back to.
        $systemSettings = [
            'systemName' => get_setting($pdo, 'system_name', 'ARTEMIS'),
            'institution' => get_setting($pdo, 'institution', 'BatStateU ARASOF-Nasugbu'),
            'office' => get_setting($pdo, 'office', 'Culture and Arts Office'),
            'academicYear' => get_setting($pdo, 'academic_year', '2025 – 2026'),
        ];
        $adminRoleLabel = 'OCA Administrator';

        $modules = [
            ['code' => 'audition_recruitment', 'icon' => 'mic-2', 'label' => 'Audition'],
            ['code' => 'stipend', 'icon' => 'file-text', 'label' => 'Stipend'],
            ['code' => 'pathfit_exemption', 'icon' => 'book-open', 'label' => 'PATHFit'],
            ['code' => 'bantog_recognition', 'icon' => 'award', 'label' => 'Bantog'],
            ['code' => 'external_invitation', 'icon' => 'send', 'label' => 'Invitations'],
            ['code' => 'appeal_admission', 'icon' => 'message-square', 'label' => 'Appeals'],
        ];

        $statTotal = count($applications);
        $statPending = 0;
        $statApproved = 0;
        $statRejected = 0;
        $statusCounts = ['Approved' => 0, 'Pending' => 0, 'Under Review' => 0, 'Evaluation' => 0, 'Rejected' => 0];
        foreach ($applications as $app) {
            if (in_array($app['status'], ['Pending', 'Under Review', 'Evaluation'], true)) $statPending++;
            elseif ($app['status'] === 'Approved') $statApproved++;
            elseif ($app['status'] === 'Rejected') $statRejected++;
            if (isset($statusCounts[$app['status']])) $statusCounts[$app['status']]++;
        }

        // Monthly aggregates for charts (last 7 months, real data)
        $monthlyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i months"));
            $label = date('M', strtotime("-$i months"));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE DATE_FORMAT(submitted_at, '%Y-%m') = :ym");
            $stmt->execute(['ym' => $ym]);
            $submitted = (int) $stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status='Approved' AND DATE_FORMAT(decided_at, '%Y-%m') = :ym");
            $stmt->execute(['ym' => $ym]);
            $approved = (int) $stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status='Rejected' AND DATE_FORMAT(decided_at, '%Y-%m') = :ym");
            $stmt->execute(['ym' => $ym]);
            $rejected = (int) $stmt->fetchColumn();
            $monthlyData[] = ['month' => $label, 'submitted' => $submitted, 'approved' => $approved, 'rejected' => $rejected, 'rate' => ($approved + $rejected) > 0 ? round($approved / ($approved + $rejected) * 100) : 0];
        }

        $activeArtists = (int) $pdo->query('SELECT COUNT(*) FROM performer_profiles WHERE active_member = 1')->fetchColumn();
        $upcomingEventCount = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status IN ('Upcoming','Planning')")->fetchColumn();

        $events = $pdo->query(
            'SELECT e.*, t.code AS requires_type_code FROM events e
             LEFT JOIN application_types t ON t.type_id = e.requires_application_type_id
             ORDER BY e.event_date ASC'
        )->fetchAll();

        // Who RSVP'd to each event, so the admin can mark real Attended/Absent
        // outcomes after the fact instead of only seeing the expected headcount.
        $registrationsByEvent = [];
        $stmt = $pdo->query(
            'SELECT ea.attendance_id, ea.event_id, ea.status, u.first_name, u.last_name, u.id_number, u.course
             FROM event_attendance ea JOIN users u ON u.user_id = ea.user_id
             ORDER BY u.last_name, u.first_name'
        );
        foreach ($stmt->fetchAll() as $row) {
            $registrationsByEvent[$row['event_id']][] = $row;
        }

        $announcements = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC')->fetchAll();

        // Real notifications: a chronological activity feed built from actual status
        // changes (application_status_history), not one fixed slot per category —
        // so the list genuinely reflects what happened recently and varies with it,
        // instead of always surfacing the same handful of "is there at least one…?" checks.
        $notifItems = [];
        $stmt = $pdo->query(
            "SELECT h.stage, h.status, h.changed_at, a.application_code, t.name AS type_name, u.first_name, u.last_name
             FROM application_status_history h
             JOIN applications a ON a.application_id = h.application_id
             JOIN application_types t ON t.type_id = a.type_id
             JOIN users u ON u.user_id = a.user_id
             ORDER BY h.changed_at DESC LIMIT 8"
        );
        foreach ($stmt->fetchAll() as $h) {
            $who = $h['first_name'] . ' ' . $h['last_name'];
            if ($h['status'] === 'Approved') {
                $color = '#22C55E'; $icon = 'check-circle'; $msg = "{$h['application_code']} was approved";
            } elseif ($h['status'] === 'Rejected') {
                $color = '#EF4444'; $icon = 'x-circle'; $msg = "{$h['application_code']} was rejected";
            } elseif ((int) $h['stage'] === 1) {
                $color = '#3B82F6'; $icon = 'file-text'; $msg = "$who submitted {$h['type_name']}";
            } else {
                $color = '#7C3AED'; $icon = 'activity'; $msg = "{$h['application_code']} moved to {$h['status']}";
            }
            $notifItems[] = [
                'color' => $color, 'icon' => $icon, 'msg' => $msg, 'time' => time_ago($h['changed_at']),
                'section' => 'applications', 'filter' => '', 'module' => '', 'appCode' => $h['application_code'],
            ];
        }
        // A standing "needs attention" alert is genuinely real-time (true/false right now)
        // rather than historical, so it's pinned to the top instead of being one more row
        // in the chronological feed.
        if ($statPending > 0) {
            array_unshift($notifItems, ['color' => '#F59E0B', 'icon' => 'alert-circle', 'msg' => "$statPending application(s) pending review", 'time' => 'Now', 'section' => 'applications', 'filter' => 'Pending', 'module' => '', 'appCode' => '']);
        }
        $notifItems = array_slice($notifItems, 0, 8);

        $pageTitle = 'Admin Dashboard';

        return view('pages.admin_dashboard', get_defined_vars());
    }
}
