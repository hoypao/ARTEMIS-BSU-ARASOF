<?php

namespace App\Http\Controllers;

/**
 * Port of index.php (public landing page + logged-in-student variant).
 * Query/aggregation logic is verbatim from the legacy preamble.
 */
class HomeController extends Controller
{
    public function index()
    {
        $pdo = getDB();

        // Auth state for the logged-in-student variant of this page — everything
        // below stays 100% unchanged for logged-out visitors and logged-in admins.
        $sessionUser = is_logged_in() ? current_user() : null;
        $isStudent = $sessionUser !== null && $sessionUser['role'] === 'student';
        $navFirstName = $isStudent ? $sessionUser['first_name'] : '';
        $navFullName = $isStudent ? trim($sessionUser['first_name'] . ' ' . $sessionUser['last_name']) : '';
        $navInitials = $isStudent
            ? strtoupper(mb_substr($sessionUser['first_name'], 0, 1) . mb_substr($sessionUser['last_name'], 0, 1))
            : '';
        $navPhotoUrl = null;
        if ($isStudent) {
            $stmt = $pdo->prepare('SELECT photo_path FROM performer_profiles WHERE user_id = :id');
            $stmt->execute(['id' => $sessionUser['user_id']]);
            $photoPath = $stmt->fetchColumn();
            $navPhotoUrl = $photoPath ? APP_URL . '/' . $photoPath : null;
        }

        $announcements = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3')->fetchAll();
        $upcomingEvents = $pdo->query(
            "SELECT e.*, t.name AS requires_type_name FROM events e
             LEFT JOIN application_types t ON t.type_id = e.requires_application_type_id
             WHERE e.status IN ('Upcoming','Planning') ORDER BY e.event_date ASC LIMIT 6"
        )->fetchAll();

        // Personal alerts (student only) — missing documents, decided outcomes,
        // and pending-review reminders pulled from the student's own applications.
        $personalAlerts = [];
        $myEventStatus = [];
        if ($isStudent) {
            $stmt = $pdo->prepare(
                'SELECT a.*, t.name AS type_name, t.code AS type_code
                 FROM applications a
                 JOIN application_types t ON t.type_id = a.type_id
                 WHERE a.user_id = :id
                 ORDER BY a.submitted_at DESC'
            );
            $stmt->execute(['id' => $sessionUser['user_id']]);
            $myApplications = $stmt->fetchAll();

            $myDocLabelsByApp = [];
            if ($myApplications) {
                $ids = array_column($myApplications, 'application_id');
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("SELECT application_id, document_type FROM application_documents WHERE application_id IN ($placeholders)");
                $stmt->execute($ids);
                foreach ($stmt->fetchAll() as $doc) {
                    $myDocLabelsByApp[$doc['application_id']][] = $doc['document_type'];
                }
            }

            $requirements = application_document_requirements();

            // 1) Missing required documents on still-active applications — most actionable.
            foreach ($myApplications as $app) {
                if (!in_array($app['status'], ['Pending', 'Under Review', 'Evaluation'], true)) {
                    continue;
                }
                $reqForType = $requirements[$app['type_code']] ?? [];
                $uploadedLabels = $myDocLabelsByApp[$app['application_id']] ?? [];
                $missingLabels = [];
                foreach ($reqForType as $docKey => $isRequired) {
                    if (!$isRequired) {
                        continue;
                    }
                    $label = ARTEMIS_DOCUMENT_CATALOG[$docKey] ?? $docKey;
                    if (!in_array($label, $uploadedLabels, true)) {
                        $missingLabels[] = $label;
                    }
                }
                if ($missingLabels) {
                    $personalAlerts[] = [
                        'urgent'  => true,
                        'icon'    => 'file-warning',
                        'color'   => '#B11226',
                        'badge'   => 'Action Required',
                        'title'   => 'Missing Document: ' . $app['application_code'],
                        'detail'  => 'Please upload: ' . implode(', ', $missingLabels) . ' for your ' . $app['type_name'] . ' application.',
                        'date'    => $app['submitted_at'],
                    ];
                }
            }

            // 2) Most recent decided application (Approved/Rejected).
            foreach ($myApplications as $app) {
                if (in_array($app['status'], ['Approved', 'Rejected'], true) && $app['decided_at']) {
                    $approved = $app['status'] === 'Approved';
                    $personalAlerts[] = [
                        'urgent'  => false,
                        'icon'    => $approved ? 'check-circle' : 'x-circle',
                        'color'   => $approved ? '#22C55E' : '#EF4444',
                        'badge'   => 'Update',
                        'title'   => $app['application_code'] . ' has been ' . $app['status'],
                        'detail'  => $approved
                            ? 'Congratulations! Your ' . $app['type_name'] . ' application was approved.'
                            : ($app['remarks'] ?: 'Your ' . $app['type_name'] . ' application was not approved this time.'),
                        'date'    => $app['decided_at'],
                    ];
                    break;
                }
            }

            // 3) Otherwise, a status reminder for an application still under review.
            if (count($personalAlerts) < 2) {
                foreach ($myApplications as $app) {
                    if (in_array($app['status'], ['Pending', 'Under Review', 'Evaluation'], true)) {
                        $personalAlerts[] = [
                            'urgent'  => false,
                            'icon'    => 'clock',
                            'color'   => '#3B82F6',
                            'badge'   => 'Status',
                            'title'   => $app['application_code'] . ' is ' . $app['status'],
                            'detail'  => 'Your ' . $app['type_name'] . ' application is currently being reviewed by OCA.',
                            'date'    => $app['submitted_at'],
                        ];
                        break;
                    }
                }
            }

            $personalAlerts = array_slice($personalAlerts, 0, 3);

            $stmt = $pdo->prepare('SELECT event_id, status FROM event_attendance WHERE user_id = :id');
            $stmt->execute(['id' => $sessionUser['user_id']]);
            foreach ($stmt->fetchAll() as $row) {
                $myEventStatus[$row['event_id']] = $row['status'];
            }

            // Registered events first, then the rest in date order.
            usort($upcomingEvents, function ($a, $b) use ($myEventStatus) {
                $aReg = in_array($myEventStatus[$a['event_id']] ?? null, ['Registered', 'Attended'], true) ? 0 : 1;
                $bReg = in_array($myEventStatus[$b['event_id']] ?? null, ['Registered', 'Attended'], true) ? 0 : 1;
                return $aReg <=> $bReg ?: strtotime($a['event_date']) <=> strtotime($b['event_date']);
            });
        }
        $upcomingEvents = array_slice($upcomingEvents, 0, 3);

        $services = [
            ['icon' => 'mic-2', 'title' => 'Audition & Recruitment', 'desc' => 'Online audition registration and talent recruitment system for cultural arts programs with document upload support.', 'color' => '#B11226'],
            ['icon' => 'file-text', 'title' => 'Application System', 'desc' => 'Unified multi-module application portal covering Stipend, PATHFit, Bantog, and External Invitation applications.', 'color' => '#D4AF37'],
            ['icon' => 'search', 'title' => 'Application Tracking', 'desc' => 'Real-time status tracking from submission through OCA review, evaluation, and Chancellor approval stages.', 'color' => '#B11226'],
            ['icon' => 'bell', 'title' => 'Announcements', 'desc' => 'Instant notifications for application updates, missing documents, interview schedules, and approval results.', 'color' => '#D4AF37'],
            ['icon' => 'calendar', 'title' => 'Events Management', 'desc' => 'Centralized calendar for cultural events, performances, competitions, and university arts programs.', 'color' => '#B11226'],
            ['icon' => 'award', 'title' => 'BANTOG Recognition', 'desc' => 'Digital processing of cultural excellence recognition programs and performance merit documentation.', 'color' => '#D4AF37'],
        ];

        $myApprovedTypeIds = $isStudent ? get_user_approved_application_type_ids($pdo, $sessionUser['user_id']) : [];

        $eventsForJs = array_map(function ($ev) use ($myEventStatus, $myApprovedTypeIds) {
            $ts = strtotime($ev['event_date']);
            $status = $myEventStatus[$ev['event_id']] ?? null;
            $requiresTypeId = $ev['requires_application_type_id'] !== null ? (int) $ev['requires_application_type_id'] : null;
            return [
                'id' => (int) $ev['event_id'],
                'title' => $ev['title'],
                'date' => date('F j, Y', $ts),
                'location' => $ev['location'] ?: '',
                'color' => $ev['color_hex'] ?: '#B11226',
                'desc' => $ev['description'] ?: '',
                'registered' => in_array($status, ['Registered', 'Attended'], true),
                'closingSoon' => $ts >= strtotime('today') && $ts <= strtotime('+3 days'),
                'requiresTravel' => (bool) $ev['requires_travel'],
                'requiresTypeName' => $ev['requires_type_name'],
                'eligible' => $requiresTypeId === null || isset($myApprovedTypeIds[$requiresTypeId]),
            ];
        }, $upcomingEvents);

        $pageTitle = 'Home';

        return view('pages.home', get_defined_vars());
    }
}
