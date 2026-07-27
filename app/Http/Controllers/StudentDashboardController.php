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

        // Check-in QR code (Art. XII event attendance) — a random, unguessable
        // token distinct from the sequential user_id, generated once and reused.
        // Staff scan this at the venue to mark real Attended status server-side.
        if (empty($user['qr_token'])) {
            $qrToken = bin2hex(random_bytes(24));
            $stmt = $pdo->prepare('UPDATE users SET qr_token = :token WHERE user_id = :id');
            $stmt->execute(['token' => $qrToken, 'id' => $sessionUser['user_id']]);
            $user['qr_token'] = $qrToken;
        }

        $stmt = $pdo->prepare('SELECT * FROM performer_profiles WHERE user_id = :id');
        $stmt->execute(['id' => $sessionUser['user_id']]);
        $profile = $stmt->fetch() ?: null;
        $profilePhotoUrl = ($profile && !empty($profile['photo_path'])) ? APP_URL . '/' . $profile['photo_path'] : null;

        // Academic Support: grade monitoring + probation status (Art. V Sec. 15).
        $stmt = $pdo->prepare('SELECT * FROM academic_monitoring_reports WHERE user_id = :id ORDER BY submitted_at DESC');
        $stmt->execute(['id' => $sessionUser['user_id']]);
        $academicReports = $stmt->fetchAll();
        $isOnProbation = $profile !== null && $profile['probation_status'] === 'Probation';

        $stmt = $pdo->prepare("SELECT * FROM student_mentorships WHERE student_user_id = :id AND status = 'Active' ORDER BY assigned_at DESC");
        $stmt->execute(['id' => $sessionUser['user_id']]);
        $activeMentorships = $stmt->fetchAll();

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
        $pathfitFaculty = $pdo->query("SELECT user_id, first_name, last_name FROM users WHERE role = 'pathfit_faculty' AND status = 'active' ORDER BY last_name, first_name")->fetchAll();

        // This student's own granted benefits (Stipend/PATHFit/BANTOG) — including
        // whether the Art. IX Sec. 34 end-of-semester completion report is still due.
        $stmt = $pdo->prepare('SELECT * FROM benefit_records WHERE user_id = :id ORDER BY granted_at DESC');
        $stmt->execute(['id' => $sessionUser['user_id']]);
        $benefits = $stmt->fetchAll();

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
        if (!upload_content_type_ok($_FILES['photo']['tmp_name'], $ext)) {
            flash_set('error', 'That file does not look like a valid image.');
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

    /**
     * End-of-semester Stipend completion report (Art. IX Sec. 34) — documentation
     * (written feedback, optional photo/video file) of the activities the stipend
     * supported and how it contributed to the student's artistic development.
     */
    public function submitBenefitReport(Request $request)
    {
        $user = current_user();
        $back = redirect()->route('student.dashboard', ['tab' => 'applications']);

        $benefitId = (int) $request->input('benefit_id', 0);
        $report = trim((string) $request->input('completion_report', ''));

        if ($benefitId <= 0 || $report === '') {
            flash_set('error', 'Please describe how the stipend contributed to your training or performances.');
            return $back;
        }

        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM benefit_records WHERE benefit_id = :id AND user_id = :uid AND benefit_type = 'Stipend'");
        $stmt->execute(['id' => $benefitId, 'uid' => $user['user_id']]);
        $benefit = $stmt->fetch();

        if (!$benefit) {
            flash_set('error', 'Benefit record not found.');
            return $back;
        }

        $filePath = null;
        if (!empty($_FILES['completion_file']) && $_FILES['completion_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['completion_file']['error'] !== UPLOAD_ERR_OK) {
                flash_set('error', 'Failed to upload the attached file. Please try again.');
                return $back;
            }
            if ($_FILES['completion_file']['size'] > 20 * 1024 * 1024) {
                flash_set('error', 'Attached file must be 20MB or smaller.');
                return $back;
            }
            $ext = strtolower(pathinfo($_FILES['completion_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'mp4', 'mov'], true)) {
                flash_set('error', 'Attachment must be a PDF, image (jpg, png), or video (mp4, mov).');
                return $back;
            }
            if (!upload_content_type_ok($_FILES['completion_file']['tmp_name'], $ext)) {
                flash_set('error', 'That attachment does not look like a valid PDF, image, or video file.');
                return $back;
            }
            $destDir = ARTEMIS_UPLOAD_PATH . '/' . $user['user_id'] . '/benefit-reports';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0775, true);
            }
            $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['completion_file']['tmp_name'], $destDir . '/' . $safeName)) {
                flash_set('error', 'Something went wrong saving your attachment. Please try again.');
                return $back;
            }
            $filePath = 'uploads/documents/' . $user['user_id'] . '/benefit-reports/' . $safeName;
        }

        $stmt = $pdo->prepare(
            'UPDATE benefit_records SET completion_report = :report, completion_file_path = COALESCE(:file, completion_file_path), completion_submitted_at = NOW() WHERE benefit_id = :id'
        );
        $stmt->execute(['report' => $report, 'file' => $filePath, 'id' => $benefitId]);

        flash_set('success', 'Completion report submitted. Thank you!');
        return $back;
    }

    /**
     * Twice-a-semester grade report to the Head of OCA (Art. V Sec. 15-C.1.a,
     * 15-C.3). A reported failing grade automatically places the student on
     * probation (Sec. 15-D) — restricting RPAG grant eligibility until a
     * passing grade is confirmed via a later report or admin override.
     */
    public function submitAcademicReport(Request $request)
    {
        $user = current_user();
        $back = redirect()->route('student.dashboard', ['tab' => 'profile']);

        $term = $request->input('term', '');
        $academicYear = trim($request->input('academic_year', ''));
        $semester = $request->input('semester', '');
        $hasFailingGrade = !empty($request->input('has_failing_grade'));
        $gwa = trim((string) $request->input('gwa', ''));
        $notes = trim($request->input('notes', ''));

        if (!in_array($term, ['Midterm', 'Final'], true) || $academicYear === '' || !in_array($semester, ['1st', '2nd', 'Summer'], true)) {
            flash_set('error', 'Please select the term, academic year, and semester.');
            return $back;
        }

        $pdo = getDB();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO academic_monitoring_reports (user_id, term, academic_year, semester, has_failing_grade, gwa, notes)
                 VALUES (:uid, :term, :year, :semester, :failing, :gwa, :notes)'
            );
            $stmt->execute([
                'uid' => $user['user_id'], 'term' => $term, 'year' => $academicYear, 'semester' => $semester,
                'failing' => $hasFailingGrade ? 1 : 0, 'gwa' => $gwa !== '' ? $gwa : null, 'notes' => $notes !== '' ? $notes : null,
            ]);

            $stmt = $pdo->prepare('SELECT profile_id FROM performer_profiles WHERE user_id = :uid');
            $stmt->execute(['uid' => $user['user_id']]);
            $profileId = $stmt->fetchColumn();
            if (!$profileId) {
                $stmt = $pdo->prepare('INSERT INTO performer_profiles (user_id) VALUES (:uid)');
                $stmt->execute(['uid' => $user['user_id']]);
                $profileId = (int) $pdo->lastInsertId();
            }

            if ($hasFailingGrade) {
                $stmt = $pdo->prepare(
                    "UPDATE performer_profiles SET probation_status = 'Probation',
                     probation_reason = :reason, probation_started_at = NOW() WHERE profile_id = :pid"
                );
                $stmt->execute(['reason' => "Failing grade reported for {$term} term, AY {$academicYear} ({$semester} sem).", 'pid' => $profileId]);
            } else {
                // A clean report clears an existing probation — Sec. 15-D lifts it
                // "only after the student has obtained a passing grade."
                $stmt = $pdo->prepare(
                    "UPDATE performer_profiles SET probation_status = 'None', probation_reason = NULL, probation_started_at = NULL
                     WHERE profile_id = :pid AND probation_status = 'Probation'"
                );
                $stmt->execute(['pid' => $profileId]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('submitAcademicReport: ' . $e->getMessage());
            flash_set('error', 'Something went wrong while submitting your report. Please try again.');
            return $back;
        }

        flash_set('success', $hasFailingGrade
            ? 'Report submitted. Since a failing grade was reported, your account has been placed under academic probation per Art. V Sec. 15-D.'
            : 'Academic report submitted. Thank you!');
        return $back;
    }
}
