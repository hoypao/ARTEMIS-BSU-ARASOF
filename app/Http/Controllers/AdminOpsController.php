<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Ports of the admin JSON action endpoints:
 * actions/events/event_action.php        -> events()
 * actions/events/mark_attendance_action.php -> attendance()
 * actions/misc/announcement_action.php   -> announcements()
 * actions/misc/faculty_complaint_action.php -> facultyComplaints()
 * actions/misc/trainer_evaluation_action.php -> trainerEvaluations()
 * All behind route middleware `role:admin`; CSRF via middleware.
 */
class AdminOpsController extends Controller
{
    /** event_action.php — create/update ("save", upsert on event_id) or delete. */
    public function events(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        if ($action === 'delete') {
            $eventId = (int) ($input['event_id'] ?? 0);
            if ($eventId <= 0) {
                return response()->json(['error' => 'Invalid event.'], 400);
            }
            $stmt = $pdo->prepare('DELETE FROM events WHERE event_id = :id');
            $stmt->execute(['id' => $eventId]);
            return response()->json(['success' => true]);
        }

        if ($action === 'save') {
            $eventId = (int) ($input['event_id'] ?? 0);
            $title = trim($input['title'] ?? '');
            $eventType = trim($input['event_type'] ?? '') !== '' ? trim($input['event_type']) : 'Cultural Night';
            $date = trim($input['date'] ?? '');
            $location = trim($input['location'] ?? '');
            $attendees = (int) ($input['attendees'] ?? 0);
            $status = $input['status'] ?? 'Upcoming';
            $requiresTravel = !empty($input['requires_travel']) ? 1 : 0;
            $requiresTypeCode = trim($input['requires_type_code'] ?? '');

            $allowedStatus = ['Planning', 'Upcoming', 'Ongoing', 'Completed', 'Cancelled'];
            if ($title === '' || $date === '' || !in_array($status, $allowedStatus, true)) {
                return response()->json(['error' => 'Please fill in the event title, date, and a valid status.'], 422);
            }
            $ts = strtotime($date);
            if (!$ts) {
                return response()->json(['error' => 'Could not understand that date.'], 422);
            }
            $eventDate = date('Y-m-d', $ts);
            $colors = ['#B11226', '#D4AF37']; // alternating brand colors for new events on the calendar view

            $requiresTypeId = null;
            if ($requiresTypeCode !== '') {
                $stmt = $pdo->prepare('SELECT type_id FROM application_types WHERE code = :code');
                $stmt->execute(['code' => $requiresTypeCode]);
                $requiresTypeId = $stmt->fetchColumn() ?: null;
            }

            if ($eventId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE events SET title = :title, event_type = :event_type, event_date = :event_date, location = :location,
                     expected_attendees = :attendees, status = :status, requires_travel = :requires_travel,
                     requires_application_type_id = :requires_type_id WHERE event_id = :id'
                );
                $stmt->execute([
                    'title' => $title, 'event_type' => $eventType, 'event_date' => $eventDate, 'location' => $location,
                    'attendees' => $attendees, 'status' => $status, 'requires_travel' => $requiresTravel,
                    'requires_type_id' => $requiresTypeId, 'id' => $eventId,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO events (title, event_type, event_date, location, expected_attendees, status, requires_travel, requires_application_type_id, color_hex, created_by)
                     VALUES (:title, :event_type, :event_date, :location, :attendees, :status, :requires_travel, :requires_type_id, :color, :created_by)'
                );
                $stmt->execute([
                    'title' => $title, 'event_type' => $eventType, 'event_date' => $eventDate, 'location' => $location,
                    'attendees' => $attendees, 'status' => $status, 'requires_travel' => $requiresTravel,
                    'requires_type_id' => $requiresTypeId,
                    'color' => $colors[array_rand($colors)], 'created_by' => $admin['user_id'],
                ]);
                $eventId = (int) $pdo->lastInsertId();
            }

            return response()->json(['success' => true, 'event_id' => $eventId]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }

    /** mark_attendance_action.php — mark a registered attendee Attended/Absent. */
    public function attendance(Request $request)
    {
        $input = $request->json()->all() ?: [];

        $attendanceId = (int) ($input['attendance_id'] ?? 0);
        $status = $input['status'] ?? '';

        if ($attendanceId <= 0 || !in_array($status, ['Attended', 'Absent'], true)) {
            return response()->json(['error' => 'Invalid request.'], 400);
        }

        $pdo = getDB();
        $stmt = $pdo->prepare(
            'UPDATE event_attendance SET status = :status, marked_at = NOW() WHERE attendance_id = :id'
        );
        $stmt->execute(['status' => $status, 'id' => $attendanceId]);

        if ($stmt->rowCount() === 0) {
            return response()->json(['error' => 'Attendance record not found.'], 404);
        }

        return response()->json(['success' => true]);
    }

    /** announcement_action.php — create/update ("save") or delete. */
    public function announcements(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        if ($action === 'delete') {
            $id = (int) ($input['announcement_id'] ?? 0);
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid announcement.'], 400);
            }
            $stmt = $pdo->prepare('DELETE FROM announcements WHERE announcement_id = :id');
            $stmt->execute(['id' => $id]);
            return response()->json(['success' => true]);
        }

        if ($action === 'save') {
            $id = (int) ($input['announcement_id'] ?? 0);
            $title = trim($input['title'] ?? '');
            $type = $input['type'] ?? 'General';
            $audience = trim($input['audience'] ?? 'All Students');
            $isUrgent = !empty($input['is_urgent']) ? 1 : 0;

            $allowedTypes = ['Audition', 'Stipend', 'Academic', 'General', 'Event'];
            if ($title === '' || !in_array($type, $allowedTypes, true)) {
                return response()->json(['error' => 'Please enter a title and choose a valid type.'], 422);
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE announcements SET title = :title, content = :content, tag = :tag, audience = :audience, is_urgent = :urgent WHERE announcement_id = :id'
                );
                $stmt->execute([
                    'title' => $title, 'content' => $title, 'tag' => $type, 'audience' => $audience, 'urgent' => $isUrgent, 'id' => $id,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO announcements (title, content, tag, audience, is_urgent, posted_by) VALUES (:title, :content, :tag, :audience, :urgent, :posted_by)'
                );
                $stmt->execute([
                    'title' => $title, 'content' => $title, 'tag' => $type, 'audience' => $audience, 'urgent' => $isUrgent, 'posted_by' => $admin['user_id'],
                ]);
                $id = (int) $pdo->lastInsertId();
            }

            return response()->json(['success' => true, 'announcement_id' => $id, 'audience' => $audience]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }

    /** faculty_complaint_action.php — "log" (auto-escalation) and "update_status". */
    public function facultyComplaints(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        if ($action === 'log') {
            $facultyName = trim($input['faculty_name'] ?? '');
            $college = trim($input['college'] ?? '');
            $rpagGroup = trim($input['rpag_group'] ?? '');
            $description = trim($input['description'] ?? '');

            if ($facultyName === '' || $description === '') {
                return response()->json(['error' => 'Faculty name and a description of the incident are required.'], 422);
            }

            $level = determine_escalation_level($pdo, $facultyName);

            $stmt = $pdo->prepare(
                'INSERT INTO faculty_noncompliance_complaints (faculty_name, college, rpag_group, description, escalation_level, filed_by)
                 VALUES (:name, :college, :rpag_group, :description, :level, :filer)'
            );
            $stmt->execute([
                'name' => $facultyName,
                'college' => $college !== '' ? $college : null,
                'rpag_group' => $rpagGroup !== '' ? $rpagGroup : null,
                'description' => $description,
                'level' => $level,
                'filer' => $admin['user_id'],
            ]);

            return response()->json([
                'success' => true,
                'complaint_id' => (int) $pdo->lastInsertId(),
                'escalation_level' => $level,
                'recommended_action' => escalation_recommended_action($level),
            ]);
        }

        if ($action === 'update_status') {
            $id = (int) ($input['complaint_id'] ?? 0);
            $status = $input['status'] ?? '';
            $allowed = ['Submitted', 'Dean Review', 'Written Warning Issued', 'Grievance Board', 'Resolved'];
            if ($id <= 0 || !in_array($status, $allowed, true)) {
                return response()->json(['error' => 'Invalid complaint or status.'], 422);
            }
            $stmt = $pdo->prepare('UPDATE faculty_noncompliance_complaints SET status = :status WHERE complaint_id = :id');
            $stmt->execute(['status' => $status, 'id' => $id]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }

    /** trainer_evaluation_action.php — "evaluate", "set_honorarium", "delete". */
    public function trainerEvaluations(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        if ($action === 'evaluate') {
            $trainerName = trim($input['trainer_name'] ?? '');
            $discipline = trim($input['discipline'] ?? '');
            $scores = [];
            foreach (array_keys(ARTEMIS_TRAINER_RUBRIC_CRITERIA) as $key) {
                $val = (int) ($input['score_' . $key] ?? 0);
                if ($val < 1 || $val > 5) {
                    return response()->json(['error' => 'Each rubric criterion must be scored 1-5.'], 422);
                }
                $scores[$key] = $val;
            }
            if ($trainerName === '') {
                return response()->json(['error' => 'Trainer name is required.'], 422);
            }

            $total = array_sum($scores);
            $level = compute_trainer_level($total);

            $stmt = $pdo->prepare(
                'INSERT INTO trainer_evaluations
                    (trainer_name, discipline, score_experience, score_recognition, score_contributions, score_skills, score_credentials,
                     total_score, recommended_level, recommended_salary_grade, evaluated_by)
                 VALUES (:name, :discipline, :experience, :recognition, :contributions, :skills, :credentials, :total, :level, :grade, :evaluator)'
            );
            $stmt->execute([
                'name' => $trainerName,
                'discipline' => $discipline !== '' ? $discipline : null,
                'experience' => $scores['experience'],
                'recognition' => $scores['recognition'],
                'contributions' => $scores['contributions'],
                'skills' => $scores['skills'],
                'credentials' => $scores['credentials'],
                'total' => $total,
                'level' => $level['name'],
                'grade' => $level['salary_grade'],
                'evaluator' => $admin['user_id'],
            ]);

            return response()->json([
                'success' => true,
                'evaluation_id' => (int) $pdo->lastInsertId(),
                'total_score' => $total,
                'level' => $level,
            ]);
        }

        if ($action === 'set_honorarium') {
            $id = (int) ($input['evaluation_id'] ?? 0);
            $hourlyRate = ($input['hourly_rate'] ?? '') !== '' ? round((float) $input['hourly_rate'], 2) : null;
            $hoursRendered = ($input['hours_rendered'] ?? '') !== '' ? round((float) $input['hours_rendered'], 2) : null;

            if ($id <= 0) {
                return response()->json(['error' => 'Invalid evaluation record.'], 400);
            }

            $computed = ($hourlyRate !== null && $hoursRendered !== null) ? round($hourlyRate * $hoursRendered, 2) : null;

            $stmt = $pdo->prepare(
                'UPDATE trainer_evaluations SET hourly_rate = :rate, hours_rendered = :hours, computed_honorarium = :computed WHERE evaluation_id = :id'
            );
            $stmt->execute(['rate' => $hourlyRate, 'hours' => $hoursRendered, 'computed' => $computed, 'id' => $id]);

            return response()->json(['success' => true, 'computed_honorarium' => $computed]);
        }

        if ($action === 'delete') {
            $id = (int) ($input['evaluation_id'] ?? 0);
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid evaluation record.'], 400);
            }
            $stmt = $pdo->prepare('DELETE FROM trainer_evaluations WHERE evaluation_id = :id');
            $stmt->execute(['id' => $id]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }
}
