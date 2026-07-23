<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of student_dashboard.php — loads this student's profile, talents, and
 * applications; the Blade view embeds them as JSON into JS globals exactly
 * like the legacy page. Route middleware `role:student` replaces require_role().
 */
class StudentDashboardController extends Controller
{
    public function index()
    {
        $sessionUser = current_user();
        $pdo = getDB();

        $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id');
        $stmt->execute(['id' => $sessionUser['user_id']]);
        $user = $stmt->fetch();

        $stmt = $pdo->prepare('SELECT * FROM performer_profiles WHERE user_id = :id');
        $stmt->execute(['id' => $sessionUser['user_id']]);
        $profile = $stmt->fetch() ?: null;
        $profilePhotoUrl = ($profile && !empty($profile['photo_path'])) ? APP_URL . '/' . $profile['photo_path'] : null;

        $talents = [];
        if ($profile) {
            $stmt = $pdo->prepare(
                'SELECT tc.name FROM performer_talents pt JOIN talent_categories tc ON tc.category_id = pt.category_id WHERE pt.profile_id = :pid'
            );
            $stmt->execute(['pid' => $profile['profile_id']]);
            $talents = array_column($stmt->fetchAll(), 'name');
        }

        $stmt = $pdo->prepare(
            'SELECT a.*, t.name AS type_name, t.code AS type_code
             FROM applications a
             JOIN application_types t ON t.type_id = a.type_id
             WHERE a.user_id = :id
             ORDER BY a.submitted_at DESC'
        );
        $stmt->execute(['id' => $sessionUser['user_id']]);
        $applications = $stmt->fetchAll();

        $documentsByApp = [];
        if ($applications) {
            $ids = array_column($applications, 'application_id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM application_documents WHERE application_id IN ($placeholders) ORDER BY uploaded_at");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $doc) {
                $documentsByApp[$doc['application_id']][] = $doc;
            }
        }

        $applicationTypes = $pdo->query('SELECT * FROM application_types ORDER BY type_id')->fetchAll();
        $typeIcons = [
            'audition_recruitment'            => 'mic-2',
            'stipend'                         => 'file-text',
            'pathfit_exemption'               => 'book-open',
            'bantog_recognition'              => 'award',
            'external_invitation'             => 'send',
            'appeal_admission'                => 'user',
        ];
        $talentCategories = $pdo->query('SELECT * FROM talent_categories ORDER BY name')->fetchAll();

        $announcements = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC LIMIT 4')->fetchAll();
        $upcomingEvents = $pdo->query("SELECT * FROM events WHERE status IN ('Upcoming','Planning') ORDER BY event_date ASC LIMIT 3")->fetchAll();

        // Stat cards
        $statTotal = count($applications);
        $statPending = 0;
        $statApproved = 0;
        $statRejected = 0;
        $statusCounts = ['Approved' => 0, 'Pending' => 0, 'Under Review' => 0, 'Evaluation' => 0, 'Rejected' => 0];
        $typeCounts = [];
        foreach ($applications as $app) {
            if (in_array($app['status'], ['Pending', 'Under Review', 'Evaluation'], true)) {
                $statPending++;
            } elseif ($app['status'] === 'Approved') {
                $statApproved++;
            } elseif ($app['status'] === 'Rejected') {
                $statRejected++;
            }
            if (isset($statusCounts[$app['status']])) {
                $statusCounts[$app['status']]++;
            }
            $typeCounts[$app['type_name']] = ($typeCounts[$app['type_name']] ?? 0) + 1;
        }

        // Real notifications: this student's own application status changes merged
        // with announcements, sorted chronologically and capped — not fixed "latest
        // decided / latest urgent / latest announcement" slots that show up every
        // time regardless of whether anything new actually happened.
        $notifRaw = [];
        $stmt = $pdo->prepare(
            "SELECT h.stage, h.status, h.changed_at, a.application_code
             FROM application_status_history h
             JOIN applications a ON a.application_id = h.application_id
             WHERE a.user_id = :id AND h.stage > 1
             ORDER BY h.changed_at DESC LIMIT 8"
        );
        $stmt->execute(['id' => $sessionUser['user_id']]);
        foreach ($stmt->fetchAll() as $h) {
            if ($h['status'] === 'Approved') {
                $color = '#22C55E'; $icon = 'check-circle'; $msg = $h['application_code'] . ' has been Approved!';
            } elseif ($h['status'] === 'Rejected') {
                $color = '#EF4444'; $icon = 'x-circle'; $msg = $h['application_code'] . ' has been Rejected.';
            } else {
                $color = '#7C3AED'; $icon = 'activity'; $msg = $h['application_code'] . ' advanced to ' . $h['status'];
            }
            $notifRaw[] = ['ts' => strtotime($h['changed_at']), 'time' => time_ago($h['changed_at']), 'color' => $color, 'icon' => $icon, 'msg' => $msg, 'appCode' => $h['application_code'], 'announcementId' => null];
        }
        foreach ($announcements as $a) {
            $notifRaw[] = [
                'ts' => strtotime($a['created_at']),
                'time' => time_ago($a['created_at']),
                'color' => $a['is_urgent'] ? '#EF4444' : '#3B82F6',
                'icon' => $a['is_urgent'] ? 'alert-circle' : 'bell',
                'msg' => $a['title'],
                'appCode' => null,
                'announcementId' => (int) $a['announcement_id'],
            ];
        }
        usort($notifRaw, fn ($a, $b) => $b['ts'] <=> $a['ts']);
        $notifications = array_slice($notifRaw, 0, 5);

        $initials = strtoupper(mb_substr($user['first_name'], 0, 1) . mb_substr($user['last_name'], 0, 1));
        $fullName = $user['first_name'] . ' ' . $user['last_name'];

        $pageTitle = 'Student Dashboard';

        return view('pages.student_dashboard', get_defined_vars());
    }

    /** Profile photo upload (multipart POST) — shown on this dashboard, the Profile tab, and the public home page's nav avatar. */
    public function uploadPhoto(Request $request)
    {
        $user = current_user();
        $back = redirect()->route('student.dashboard', ['tab' => 'profile']);

        if (empty($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
            flash_set('error', 'Please choose a photo to upload.');
            return $back;
        }
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            flash_set('error', 'Failed to upload the photo. Please try again.');
            return $back;
        }
        if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            flash_set('error', 'Photo must be 5MB or smaller.');
            return $back;
        }
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            flash_set('error', 'Photo must be a JPG, PNG, or WEBP image.');
            return $back;
        }

        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT profile_id, photo_path FROM performer_profiles WHERE user_id = :uid');
        $stmt->execute(['uid' => $user['user_id']]);
        $existing = $stmt->fetch();

        $destDir = ARTEMIS_PROFILE_PHOTO_PATH . '/' . $user['user_id'];
        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }
        // Random filename on disk — avoids path traversal / collisions from user-supplied filenames.
        $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $destDir . '/' . $safeName;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
            flash_set('error', 'Something went wrong saving your photo. Please try again.');
            return $back;
        }
        $relativePath = 'uploads/profile_photos/' . $user['user_id'] . '/' . $safeName;

        if ($existing) {
            // Only clean up files we manage ourselves — never touch a path outside our upload folder.
            if ($existing['photo_path'] && str_starts_with($existing['photo_path'], 'uploads/profile_photos/')) {
                $oldFile = public_path($existing['photo_path']);
                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }
            $stmt = $pdo->prepare('UPDATE performer_profiles SET photo_path = :path WHERE profile_id = :id');
            $stmt->execute(['path' => $relativePath, 'id' => $existing['profile_id']]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO performer_profiles (user_id, photo_path) VALUES (:uid, :path)');
            $stmt->execute(['uid' => $user['user_id'], 'path' => $relativePath]);
        }

        flash_set('success', 'Profile photo updated successfully.');
        return $back;
    }
}
