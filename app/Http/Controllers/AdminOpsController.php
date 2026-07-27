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
            // Post-Competition documentation (Art. XII Sec. 48) — only meaningful once decided/held.
            $competitionResult = trim($input['competition_result'] ?? '');

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
                     requires_application_type_id = :requires_type_id, competition_result = :competition_result WHERE event_id = :id'
                );
                $stmt->execute([
                    'title' => $title, 'event_type' => $eventType, 'event_date' => $eventDate, 'location' => $location,
                    'attendees' => $attendees, 'status' => $status, 'requires_travel' => $requiresTravel,
                    'requires_type_id' => $requiresTypeId, 'competition_result' => $competitionResult !== '' ? $competitionResult : null, 'id' => $eventId,
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

    /**
     * QR check-in — a staff member scans a student's Event Check-In QR (their
     * unguessable users.qr_token) at the venue; marks the attendee "Attended"
     * directly, including a walk-in who never RSVP'd (no existing row yet).
     */
    public function checkin(Request $request)
    {
        $input = $request->json()->all() ?: [];

        $eventId = (int) ($input['event_id'] ?? 0);
        $token = trim($input['token'] ?? '');

        if ($eventId <= 0 || $token === '') {
            return response()->json(['error' => 'Invalid request.'], 400);
        }

        $pdo = getDB();

        $stmt = $pdo->prepare('SELECT status FROM events WHERE event_id = :id');
        $stmt->execute(['id' => $eventId]);
        $event = $stmt->fetch();
        if (!$event) {
            return response()->json(['error' => 'Event not found.'], 404);
        }
        if (in_array($event['status'], ['Completed', 'Cancelled'], true)) {
            return response()->json(['error' => 'This event is no longer open for check-in.'], 409);
        }

        $stmt = $pdo->prepare(
            "SELECT user_id, first_name, last_name, id_number, course FROM users WHERE qr_token = :token AND role = 'student' AND status = 'active'"
        );
        $stmt->execute(['token' => $token]);
        $student = $stmt->fetch();
        if (!$student) {
            return response()->json(['error' => 'QR code not recognized.'], 404);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO event_attendance (event_id, user_id, status, marked_at)
             VALUES (:event_id, :user_id, "Attended", NOW())
             ON DUPLICATE KEY UPDATE status = "Attended", marked_at = NOW(), attendance_id = LAST_INSERT_ID(attendance_id)'
        );
        $stmt->execute(['event_id' => $eventId, 'user_id' => $student['user_id']]);
        $attendanceId = (int) $pdo->lastInsertId();

        return response()->json([
            'success' => true,
            'attendanceId' => $attendanceId,
            'userId' => (int) $student['user_id'],
            'name' => $student['first_name'] . ' ' . $student['last_name'],
            'idNumber' => $student['id_number'],
            'course' => $student['course'],
        ]);
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
            $content = trim($input['content'] ?? '');
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
                    'title' => $title, 'content' => $content, 'tag' => $type, 'audience' => $audience, 'urgent' => $isUrgent, 'id' => $id,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO announcements (title, content, tag, audience, is_urgent, posted_by) VALUES (:title, :content, :tag, :audience, :urgent, :posted_by)'
                );
                $stmt->execute([
                    'title' => $title, 'content' => $content, 'tag' => $type, 'audience' => $audience, 'urgent' => $isUrgent, 'posted_by' => $admin['user_id'],
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

        // Non-admin reviewers (currently: College Dean, WI-OCA-09 Art. V Sec. 16-A.1)
        // may only update_status on First Violation complaints against faculty in
        // their own college — filing a new complaint and handling repeat offenses
        // (which escalate to a Grievance Board chaired by HRMO, not the Dean) stay
        // admin-only.
        if ($admin['role'] !== 'admin') {
            if ($admin['role'] !== 'college_dean' || $action !== 'update_status') {
                return response()->json(['error' => 'Not authorized for this action.'], 403);
            }
            $id = (int) ($input['complaint_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT college, escalation_level FROM faculty_noncompliance_complaints WHERE complaint_id = :id');
            $stmt->execute(['id' => $id]);
            $complaint = $stmt->fetch();
            if (!$complaint || $complaint['college'] !== ($admin['college'] ?? null)) {
                return response()->json(['error' => 'This complaint is not in your college.'], 403);
            }
            if ($complaint['escalation_level'] !== 'First Violation') {
                return response()->json(['error' => 'Repeated violations are handled by the Grievance Board, not the College Dean.'], 403);
            }
            if (!in_array($input['status'] ?? '', ['Dean Review', 'Written Warning Issued', 'Resolved'], true)) {
                return response()->json(['error' => 'Invalid status for a Dean to set.'], 422);
            }
        }

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

    /**
     * Honoraria for one-off Resource Persons, Facilitators, and Judges in culture and
     * arts activities/competitions (Art. VI Sec. 18) — distinct from trainer_evaluations,
     * which covers the regular Training Specialist I-V contract-of-service trainers.
     */
    public function honoraria(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        if ($action === 'save') {
            $personName = trim($input['person_name'] ?? '');
            $role = $input['role'] ?? '';
            $activityName = trim($input['activity_name'] ?? '');
            $activityDate = trim($input['activity_date'] ?? '');
            $discipline = trim($input['discipline'] ?? '');
            $amount = ($input['amount'] ?? '') !== '' ? round((float) $input['amount'], 2) : null;

            $allowedRoles = ['Resource Person', 'Facilitator', 'Judge'];
            if ($personName === '' || !in_array($role, $allowedRoles, true) || $activityName === '') {
                return response()->json(['error' => 'Please provide the person\'s name, role, and activity name.'], 422);
            }
            $ts = strtotime($activityDate);
            if (!$ts) {
                return response()->json(['error' => 'Please provide a valid activity date.'], 422);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO activity_honoraria (person_name, role, activity_name, activity_date, discipline, amount, granted_by)
                 VALUES (:name, :role, :activity, :date, :discipline, :amount, :granted_by)'
            );
            $stmt->execute([
                'name' => $personName,
                'role' => $role,
                'activity' => $activityName,
                'date' => date('Y-m-d', $ts),
                'discipline' => $discipline !== '' ? $discipline : null,
                'amount' => $amount,
                'granted_by' => $admin['user_id'],
            ]);

            return response()->json(['success' => true, 'honorarium_id' => (int) $pdo->lastInsertId()]);
        }

        if ($action === 'delete') {
            $id = (int) ($input['honorarium_id'] ?? 0);
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid record.'], 400);
            }
            $stmt = $pdo->prepare('DELETE FROM activity_honoraria WHERE honorarium_id = :id');
            $stmt->execute(['id' => $id]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }

    /**
     * BANTOG Evaluator Panel (Art. VIII Sec. 24) — "Each year, the Director of the
     * Office of Culture and Arts shall submit the list of evaluators... which shall
     * be endorsed to the University President for approval." Recorded here so the
     * currently-serving committee is on file rather than only known informally.
     */
    public function bantogEvaluators(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $pdo = getDB();

        if ($action === 'save') {
            $name = trim($input['name'] ?? '');
            $position = trim($input['position'] ?? '');
            $academicYear = trim($input['academic_year'] ?? '');

            if ($name === '' || $position === '' || $academicYear === '') {
                return response()->json(['error' => 'Please provide the evaluator\'s name, position, and academic year.'], 422);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO bantog_evaluators (name, position, academic_year) VALUES (:name, :position, :year)'
            );
            $stmt->execute(['name' => $name, 'position' => $position, 'year' => $academicYear]);

            return response()->json(['success' => true, 'evaluator_id' => (int) $pdo->lastInsertId()]);
        }

        if ($action === 'delete') {
            $id = (int) ($input['evaluator_id'] ?? 0);
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid record.'], 400);
            }
            $stmt = $pdo->prepare('DELETE FROM bantog_evaluators WHERE evaluator_id = :id');
            $stmt->execute(['id' => $id]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }

    /**
     * Collaboration with Culture and Arts Agencies and Organizations (Art. XIII) —
     * tracks prospective/active partner organizations through survey, proposal,
     * approval, and MOA stages (Sec. 50-55).
     */
    public function partnerships(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        if ($action === 'save') {
            $id = (int) ($input['partner_id'] ?? 0);
            $name = trim($input['name'] ?? '');
            $orgType = $input['org_type'] ?? 'Other';
            $contactPerson = trim($input['contact_person'] ?? '');
            $contactEmail = trim($input['contact_email'] ?? '');
            $contactPhone = trim($input['contact_phone'] ?? '');
            $status = $input['status'] ?? 'Prospective';
            $proposalSummary = trim($input['proposal_summary'] ?? '');
            $moaSignedDate = trim($input['moa_signed_date'] ?? '');
            $moaExpiryDate = trim($input['moa_expiry_date'] ?? '');
            $notes = trim($input['notes'] ?? '');

            $allowedTypes = ['Government Cultural Agency', 'International Cultural Institution', 'NGO', 'Cultural Institution', 'Community/Indigenous Group', 'Other'];
            $allowedStatus = ['Prospective', 'Proposal Submitted', 'Approved', 'Active MOA', 'Completed', 'Declined'];
            if ($name === '' || !in_array($orgType, $allowedTypes, true) || !in_array($status, $allowedStatus, true)) {
                return response()->json(['error' => 'Please provide the organization name and a valid type/status.'], 422);
            }
            if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['error' => 'Please enter a valid contact email.'], 422);
            }

            $signedDate = $moaSignedDate !== '' ? (strtotime($moaSignedDate) ? date('Y-m-d', strtotime($moaSignedDate)) : null) : null;
            $expiryDate = $moaExpiryDate !== '' ? (strtotime($moaExpiryDate) ? date('Y-m-d', strtotime($moaExpiryDate)) : null) : null;

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE partner_organizations SET name = :name, org_type = :org_type, contact_person = :contact_person,
                     contact_email = :contact_email, contact_phone = :contact_phone, status = :status,
                     proposal_summary = :proposal_summary, moa_signed_date = :signed_date, moa_expiry_date = :expiry_date, notes = :notes
                     WHERE partner_id = :id'
                );
                $stmt->execute([
                    'name' => $name, 'org_type' => $orgType, 'contact_person' => $contactPerson !== '' ? $contactPerson : null,
                    'contact_email' => $contactEmail !== '' ? $contactEmail : null, 'contact_phone' => $contactPhone !== '' ? $contactPhone : null,
                    'status' => $status, 'proposal_summary' => $proposalSummary !== '' ? $proposalSummary : null,
                    'signed_date' => $signedDate, 'expiry_date' => $expiryDate, 'notes' => $notes !== '' ? $notes : null, 'id' => $id,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO partner_organizations (name, org_type, contact_person, contact_email, contact_phone, status, proposal_summary, moa_signed_date, moa_expiry_date, notes, created_by)
                     VALUES (:name, :org_type, :contact_person, :contact_email, :contact_phone, :status, :proposal_summary, :signed_date, :expiry_date, :notes, :created_by)'
                );
                $stmt->execute([
                    'name' => $name, 'org_type' => $orgType, 'contact_person' => $contactPerson !== '' ? $contactPerson : null,
                    'contact_email' => $contactEmail !== '' ? $contactEmail : null, 'contact_phone' => $contactPhone !== '' ? $contactPhone : null,
                    'status' => $status, 'proposal_summary' => $proposalSummary !== '' ? $proposalSummary : null,
                    'signed_date' => $signedDate, 'expiry_date' => $expiryDate, 'notes' => $notes !== '' ? $notes : null,
                    'created_by' => $admin['user_id'],
                ]);
                $id = (int) $pdo->lastInsertId();
            }

            return response()->json(['success' => true, 'partner_id' => $id]);
        }

        if ($action === 'delete') {
            $id = (int) ($input['partner_id'] ?? 0);
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid record.'], 400);
            }
            $stmt = $pdo->prepare('DELETE FROM partner_organizations WHERE partner_id = :id');
            $stmt->execute(['id' => $id]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }

    /**
     * Admission Appeal review (Art. IV Sec. 11-C) — Submitted (OCA screens) ->
     * Under Review (OCA) -> Evaluation Stage -> For Approval (President via TAO)
     * -> Approved/Rejected, mirroring the Head of TAO -> Assistant Director of
     * TAO-Central -> University President chain described in the Manual.
     *
     * The first two steps are OCA's own screening; the last two are TAO
     * Central's, which is where the tao_central role can act (see the scoping
     * block below). ARTEMIS_APPEAL_CHAIN in app/Support/ui_helpers.php is the
     * single source of order for both this controller and the tracker UI.
     */
    public function admissionAppeals(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        $id = (int) ($input['appeal_id'] ?? 0);
        if ($id <= 0) {
            return response()->json(['error' => 'Invalid request.'], 400);
        }
        $stmt = $pdo->prepare('SELECT * FROM admission_appeals WHERE appeal_id = :id');
        $stmt->execute(['id' => $id]);
        $appeal = $stmt->fetch();
        if (!$appeal) {
            return response()->json(['error' => 'Appeal not found.'], 404);
        }

        // Non-admin reviewers (currently: TAO Central, Art. IV Sec. 11-C) own only
        // the back half of the chain. Screening an appeal into Evaluation Stage
        // stays with OCA, and once the President's decision is recorded nobody
        // reopens it — same shape as the College Dean carve-out in
        // facultyComplaints() above. The check reads the appeal's *current*
        // status, so it also blocks advancing out of an OCA-owned step.
        if ($admin['role'] !== 'admin') {
            if ($admin['role'] !== 'tao_central') {
                return response()->json(['error' => 'Not authorized for this action.'], 403);
            }
            if (in_array($appeal['status'], ['Approved', 'Rejected'], true)) {
                return response()->json(['error' => 'This appeal already has a final decision.'], 409);
            }
            if (!in_array($appeal['status'], ARTEMIS_APPEAL_TAO_STAGES, true)) {
                return response()->json(['error' => 'This appeal is still with the OCA and has not reached TAO Central.'], 403);
            }
        }

        $isTao = $admin['role'] === 'tao_central';

        if ($action === 'remark') {
            $remarks = trim($input['remarks'] ?? '');
            if ($remarks === '') {
                return response()->json(['error' => 'Remark text is required.'], 422);
            }
            // A TAO reviewer's note goes in its own column so the OCA screening
            // remark it is being read against stays intact (and read-only on
            // the TAO dashboard) instead of being overwritten.
            $column = $isTao ? 'tao_remarks' : 'remarks';
            $stmt = $pdo->prepare("UPDATE admission_appeals SET {$column} = :remarks WHERE appeal_id = :id");
            $stmt->execute(['remarks' => $remarks, 'id' => $id]);
            return response()->json(['success' => true, 'field' => $column]);
        }

        if (in_array($appeal['status'], ['Approved', 'Rejected'], true)) {
            return response()->json(['error' => 'This appeal already has a final decision.'], 409);
        }

        $chain = ARTEMIS_APPEAL_CHAIN;

        if ($action === 'advance') {
            $currentIdx = array_search($appeal['status'], $chain, true);
            if ($currentIdx === false || $currentIdx >= count($chain) - 1) {
                return response()->json(['error' => 'This appeal is already at the final review stage. Use Approve or Reject.'], 409);
            }
            $newStatus = $chain[$currentIdx + 1];

            // "Evaluate & Forward" carries the reviewer's evaluation note with it,
            // so forwarding and recording why are one action rather than two.
            $taoNote = $isTao ? trim($input['tao_remarks'] ?? '') : '';
            if ($taoNote !== '') {
                $stmt = $pdo->prepare('UPDATE admission_appeals SET tao_remarks = :note WHERE appeal_id = :id');
                $stmt->execute(['note' => $taoNote, 'id' => $id]);
            }

            $stmt = $pdo->prepare('UPDATE admission_appeals SET status = :status, reviewed_by = :reviewer WHERE appeal_id = :id');
            $stmt->execute(['status' => $newStatus, 'reviewer' => $admin['user_id'], 'id' => $id]);
            $this->notifyAppealStatus($appeal, $newStatus, $appeal['remarks'] ?? '');
            return response()->json(['success' => true, 'status' => $newStatus, 'tao_remarks' => $taoNote !== '' ? $taoNote : ($appeal['tao_remarks'] ?? null)]);
        }

        if (in_array($action, ['approve', 'reject'], true)) {
            $remarks = trim($input['remarks'] ?? '');

            // Art. IV Sec. 11-C: the final ruling is the University President's,
            // recorded through TAO Central. When TAO enters it, the note saying
            // on whose authority is mandatory and is kept in its own column, so a
            // later edit to `remarks` cannot rewrite what was recorded at
            // decision time. OCA acting directly keeps the original behaviour.
            $decisionNote = trim($input['decision_note'] ?? '');
            $requiresDecisionNote = $isTao && $appeal['status'] === 'For Approval (President via TAO)';
            if ($requiresDecisionNote && $decisionNote === '') {
                return response()->json([
                    'error' => 'A decision note on behalf of the University President is required to record this ruling.',
                ], 422);
            }

            if ($action === 'reject' && $remarks === '' && $decisionNote === '') {
                return response()->json(['error' => 'Remarks are required to reject an appeal.'], 422);
            }

            $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
            // The decision note doubles as the appellant-facing reason when TAO
            // rules, matching what OCA's own remarks did before — which is what
            // keeps notifyAppealStatus() and /track working unchanged.
            $finalRemarks = $remarks !== '' ? $remarks : ($decisionNote !== '' ? $decisionNote : ($appeal['remarks'] ?? ''));

            $stmt = $pdo->prepare(
                'UPDATE admission_appeals
                 SET status = :status, reviewed_by = :reviewer, remarks = :remarks,
                     presidential_decision_note = :decision_note, decided_at = NOW()
                 WHERE appeal_id = :id'
            );
            $stmt->execute([
                'status' => $newStatus, 'reviewer' => $admin['user_id'],
                'remarks' => $finalRemarks !== '' ? $finalRemarks : null,
                'decision_note' => $decisionNote !== '' ? $decisionNote : ($appeal['presidential_decision_note'] ?? null),
                'id' => $id,
            ]);
            $this->notifyAppealStatus($appeal, $newStatus, $finalRemarks);
            return response()->json(['success' => true, 'status' => $newStatus]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }

    /** Best-effort status-update email to the appellant — same pattern as ApplicationController::review(). */
    private function notifyAppealStatus(array $appeal, string $newStatus, string $remarks): void
    {
        if (!mail_is_configured()) {
            return;
        }
        $reference = 'APPEAL-' . str_pad((string) $appeal['appeal_id'], 5, '0', STR_PAD_LEFT);
        $statusColor = $newStatus === 'Approved' ? '#15803D' : ($newStatus === 'Rejected' ? '#B91C1C' : '#B11226');
        $body = "<p>Your admission appeal <strong>{$reference}</strong> has a status update.</p>"
            . "<p style=\"text-align:center; margin: 20px 0;\"><span style=\"display:inline-block; background:{$statusColor}1a; color:{$statusColor}; padding:8px 20px; border-radius:20px; font-weight:600; font-size:13px;\">" . e($newStatus) . "</span></p>"
            . ($remarks !== '' ? "<p><strong>Remarks:</strong> " . nl2br(e($remarks)) . "</p>" : '')
            . "<p style=\"font-size:12px; color:#9CA3AF;\">Track this appeal any time at " . APP_URL . "/track using reference {$reference} and the email you applied with.</p>";
        send_email(
            $appeal['email'],
            $appeal['full_name'],
            "ARTEMIS: {$reference} is now {$newStatus}",
            email_layout('Admission Appeal Status Update', $body)
        );
    }

    /**
     * Academic Support tutoring/mentoring assignment and probation override
     * (Art. V Sec. 15-C, 15-D). The Manual assigns mentor selection to the
     * College Dean; recorded here by whichever OCA/Dean account is signed in,
     * since this deployment doesn't yet map students to a specific college.
     */
    public function academicSupport(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        if ($action === 'assign_mentor') {
            $studentUserId = (int) ($input['student_user_id'] ?? 0);
            $mentorName = trim($input['mentor_name'] ?? '');
            $reason = $input['reason'] ?? '';
            $allowedReasons = ['Voluntary Request', 'Failing Grade', 'Appeal Admission Recruit'];

            if ($studentUserId <= 0 || $mentorName === '' || !in_array($reason, $allowedReasons, true)) {
                return response()->json(['error' => 'Please select a student, mentor name, and reason.'], 422);
            }
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = :id AND role = 'student'");
            $stmt->execute(['id' => $studentUserId]);
            if (!$stmt->fetchColumn()) {
                return response()->json(['error' => 'Student not found.'], 404);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO student_mentorships (student_user_id, mentor_name, reason, assigned_by) VALUES (:uid, :mentor, :reason, :assigned_by)'
            );
            $stmt->execute(['uid' => $studentUserId, 'mentor' => $mentorName, 'reason' => $reason, 'assigned_by' => $admin['user_id']]);
            return response()->json(['success' => true, 'mentorship_id' => (int) $pdo->lastInsertId()]);
        }

        if ($action === 'complete_mentorship') {
            $id = (int) ($input['mentorship_id'] ?? 0);
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid record.'], 400);
            }
            $stmt = $pdo->prepare("UPDATE student_mentorships SET status = 'Completed', completed_at = NOW() WHERE mentorship_id = :id");
            $stmt->execute(['id' => $id]);
            return response()->json(['success' => true]);
        }

        if ($action === 'clear_probation') {
            $studentUserId = (int) ($input['student_user_id'] ?? 0);
            if ($studentUserId <= 0) {
                return response()->json(['error' => 'Invalid request.'], 400);
            }
            $stmt = $pdo->prepare(
                "UPDATE performer_profiles SET probation_status = 'None', probation_reason = NULL, probation_started_at = NULL WHERE user_id = :uid"
            );
            $stmt->execute(['uid' => $studentUserId]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }

    /** System Settings tab — persists to the real `settings` table + this admin's own users row, replacing the old client-only localStorage stub. */
    public function settings(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $admin = current_user();
        $pdo = getDB();

        $systemName = trim($input['systemName'] ?? '');
        $institution = trim($input['institution'] ?? '');
        $office = trim($input['office'] ?? '');
        $academicYear = trim($input['academicYear'] ?? '');
        $adminName = trim($input['adminName'] ?? '');
        $email = trim($input['email'] ?? '');

        if ($systemName === '' || $institution === '' || $adminName === '' || $email === '') {
            return response()->json(['error' => 'Please fill in all required fields.'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Please enter a valid email address.'], 422);
        }

        set_setting($pdo, 'system_name', $systemName);
        set_setting($pdo, 'institution', $institution);
        set_setting($pdo, 'office', $office);
        set_setting($pdo, 'academic_year', $academicYear);

        // "Administrator Name" is a single field in the UI but two columns in `users`.
        $parts = preg_split('/\s+/', $adminName, 2);
        $firstName = $parts[0];
        $lastName = $parts[1] ?? '';

        $stmt = $pdo->prepare('UPDATE users SET first_name = :first, last_name = :last, email = :email WHERE user_id = :id');
        $stmt->execute(['first' => $firstName, 'last' => $lastName, 'email' => $email, 'id' => $admin['user_id']]);

        // Keep the session copy in sync so the header/sidebar reflect the change immediately.
        session(['user' => array_merge($admin, ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email])]);

        return response()->json(['success' => true]);
    }

    /**
     * Property Custodian's inventory (Art. III Sec. 6.C.a-b) — the record of
     * every culture-and-arts equipment/material item: acquisition, condition,
     * and current custodian/location. New in this system; Article VII had no
     * prior implementation. Same save (upsert on equipment_id) / delete shape
     * as partnerships() above.
     */
    public function equipmentItems(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        if ($action === 'save') {
            $id = (int) ($input['equipment_id'] ?? 0);
            $itemName = trim($input['item_name'] ?? '');
            $category = trim($input['category'] ?? '');
            $campus = trim($input['campus'] ?? '') ?: 'ARASOF-Nasugbu';
            $condition = $input['condition_status'] ?? 'Good';
            $custodianName = trim($input['custodian_name'] ?? '');
            $location = trim($input['location'] ?? '');
            $acquisitionDateRaw = trim($input['acquisition_date'] ?? '');
            $acquisitionCost = ($input['acquisition_cost'] ?? '') !== '' ? round((float) $input['acquisition_cost'], 2) : null;
            $notes = trim($input['notes'] ?? '');

            if ($itemName === '' || !in_array($condition, ARTEMIS_EQUIPMENT_CONDITIONS, true)) {
                return response()->json(['error' => 'Please provide the item name and a valid condition.'], 422);
            }
            $acquisitionDate = $acquisitionDateRaw !== '' ? (strtotime($acquisitionDateRaw) ? date('Y-m-d', strtotime($acquisitionDateRaw)) : null) : null;

            $params = [
                'item_name' => $itemName, 'category' => $category !== '' ? $category : null, 'campus' => $campus,
                'condition_status' => $condition, 'custodian_name' => $custodianName !== '' ? $custodianName : null,
                'location' => $location !== '' ? $location : null, 'acquisition_date' => $acquisitionDate,
                'acquisition_cost' => $acquisitionCost, 'notes' => $notes !== '' ? $notes : null,
            ];

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE equipment_items SET item_name = :item_name, category = :category, campus = :campus,
                     condition_status = :condition_status, custodian_name = :custodian_name, location = :location,
                     acquisition_date = :acquisition_date, acquisition_cost = :acquisition_cost, notes = :notes
                     WHERE equipment_id = :id'
                );
                $stmt->execute($params + ['id' => $id]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO equipment_items (item_name, category, campus, condition_status, custodian_name, location, acquisition_date, acquisition_cost, notes, created_by)
                     VALUES (:item_name, :category, :campus, :condition_status, :custodian_name, :location, :acquisition_date, :acquisition_cost, :notes, :created_by)'
                );
                $stmt->execute($params + ['created_by' => $admin['user_id']]);
                $id = (int) $pdo->lastInsertId();
            }

            return response()->json(['success' => true, 'equipment_id' => $id]);
        }

        if ($action === 'delete') {
            $id = (int) ($input['equipment_id'] ?? 0);
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid record.'], 400);
            }
            $stmt = $pdo->prepare('DELETE FROM equipment_items WHERE equipment_id = :id');
            $stmt->execute(['id' => $id]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }

    /**
     * PPMP -> Purchase Request -> Approved -> Delivered pipeline (Art. VII
     * Sec. 20-A). Feeds QEO Objective 4 (equipment/materials acquisition,
     * BatStateU-QEO-OCA-03) via app/Support/qeo_kpi.php once requests exist.
     * `decided_at`/`approved_by` are stamped the first time a request reaches
     * a final status (Approved/Delivered/Cancelled) and cleared if it's ever
     * moved back to an in-progress status.
     */
    public function procurementRequests(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        if ($action === 'save') {
            $id = (int) ($input['request_id'] ?? 0);
            $itemName = trim($input['item_name'] ?? '');
            $category = trim($input['category'] ?? '');
            $quantity = max(1, (int) ($input['quantity'] ?? 1));
            $estimatedCost = ($input['estimated_cost'] ?? '') !== '' ? round((float) $input['estimated_cost'], 2) : null;
            $ppmpReference = trim($input['ppmp_reference'] ?? '');
            $justification = trim($input['justification'] ?? '');
            $status = $input['status'] ?? 'Draft';
            $notes = trim($input['notes'] ?? '');

            if ($itemName === '' || !in_array($status, ARTEMIS_PROCUREMENT_STATUSES, true)) {
                return response()->json(['error' => 'Please provide the item name and a valid status.'], 422);
            }

            $isFinal = in_array($status, ['Approved', 'Delivered', 'Cancelled'], true);
            $existingDecidedAt = null;
            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT decided_at FROM procurement_requests WHERE request_id = :id');
                $stmt->execute(['id' => $id]);
                $existingDecidedAt = $stmt->fetchColumn() ?: null;
            }
            $decidedAt = $isFinal ? ($existingDecidedAt ?? date('Y-m-d H:i:s')) : null;
            $approvedBy = $isFinal ? $admin['user_id'] : null;

            $params = [
                'item_name' => $itemName, 'category' => $category !== '' ? $category : null, 'quantity' => $quantity,
                'estimated_cost' => $estimatedCost, 'ppmp_reference' => $ppmpReference !== '' ? $ppmpReference : null,
                'justification' => $justification !== '' ? $justification : null, 'status' => $status,
                'notes' => $notes !== '' ? $notes : null, 'decided_at' => $decidedAt, 'approved_by' => $approvedBy,
            ];

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE procurement_requests SET item_name = :item_name, category = :category, quantity = :quantity,
                     estimated_cost = :estimated_cost, ppmp_reference = :ppmp_reference, justification = :justification,
                     status = :status, notes = :notes, decided_at = :decided_at, approved_by = :approved_by
                     WHERE request_id = :id'
                );
                $stmt->execute($params + ['id' => $id]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO procurement_requests (item_name, category, quantity, estimated_cost, ppmp_reference, justification, status, notes, decided_at, approved_by, requested_by)
                     VALUES (:item_name, :category, :quantity, :estimated_cost, :ppmp_reference, :justification, :status, :notes, :decided_at, :approved_by, :requested_by)'
                );
                $stmt->execute($params + ['requested_by' => $admin['user_id']]);
                $id = (int) $pdo->lastInsertId();
            }

            return response()->json(['success' => true, 'request_id' => $id]);
        }

        if ($action === 'delete') {
            $id = (int) ($input['request_id'] ?? 0);
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid record.'], 400);
            }
            $stmt = $pdo->prepare('DELETE FROM procurement_requests WHERE request_id = :id');
            $stmt->execute(['id' => $id]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }

    /**
     * Cross-campus resource sharing (Art. VII Sec. 20-C) — "a centralized
     * tracking system... to monitor the inventory, movement, and condition
     * of shared resources" between constituent campuses. When a loan comes
     * back (status -> Returned) with a condition_on_return recorded, that
     * condition is written back onto the equipment_items row so the
     * inventory reflects the item's real state post-loan without a second
     * manual edit.
     */
    public function resourceShareRequests(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        if ($action === 'save') {
            $id = (int) ($input['share_id'] ?? 0);
            $equipmentId = (int) ($input['equipment_id'] ?? 0);
            $fromCampus = trim($input['from_campus'] ?? '') ?: 'ARASOF-Nasugbu';
            $toCampus = trim($input['to_campus'] ?? '');
            $purpose = trim($input['purpose'] ?? '');
            $startDateRaw = trim($input['requested_start_date'] ?? '');
            $endDateRaw = trim($input['requested_end_date'] ?? '');
            $status = $input['status'] ?? 'Requested';
            $conditionOnReturn = $input['condition_on_return'] ?? '';
            $notes = trim($input['notes'] ?? '');

            if ($equipmentId <= 0 || $toCampus === '' || !in_array($status, ARTEMIS_SHARE_STATUSES, true)) {
                return response()->json(['error' => 'Please select the item, destination campus, and a valid status.'], 422);
            }
            if ($conditionOnReturn !== '' && !in_array($conditionOnReturn, ARTEMIS_EQUIPMENT_CONDITIONS, true)) {
                return response()->json(['error' => 'Invalid condition on return.'], 422);
            }
            $startDate = $startDateRaw !== '' ? (strtotime($startDateRaw) ? date('Y-m-d', strtotime($startDateRaw)) : null) : null;
            $endDate = $endDateRaw !== '' ? (strtotime($endDateRaw) ? date('Y-m-d', strtotime($endDateRaw)) : null) : null;
            $conditionOnReturn = $conditionOnReturn !== '' ? $conditionOnReturn : null;

            $isReturned = $status === 'Returned';
            $existingReturnedAt = null;
            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT returned_at FROM resource_share_requests WHERE share_id = :id');
                $stmt->execute(['id' => $id]);
                $existingReturnedAt = $stmt->fetchColumn() ?: null;
            }
            $returnedAt = $isReturned ? ($existingReturnedAt ?? date('Y-m-d H:i:s')) : null;

            $params = [
                'equipment_id' => $equipmentId, 'from_campus' => $fromCampus, 'to_campus' => $toCampus,
                'purpose' => $purpose !== '' ? $purpose : null, 'requested_start_date' => $startDate, 'requested_end_date' => $endDate,
                'status' => $status, 'condition_on_return' => $conditionOnReturn, 'notes' => $notes !== '' ? $notes : null,
                'returned_at' => $returnedAt,
            ];

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE resource_share_requests SET equipment_id = :equipment_id, from_campus = :from_campus, to_campus = :to_campus,
                     purpose = :purpose, requested_start_date = :requested_start_date, requested_end_date = :requested_end_date,
                     status = :status, condition_on_return = :condition_on_return, notes = :notes, returned_at = :returned_at
                     WHERE share_id = :id'
                );
                $stmt->execute($params + ['id' => $id]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO resource_share_requests (equipment_id, from_campus, to_campus, purpose, requested_start_date, requested_end_date, status, condition_on_return, notes, returned_at, requested_by)
                     VALUES (:equipment_id, :from_campus, :to_campus, :purpose, :requested_start_date, :requested_end_date, :status, :condition_on_return, :notes, :returned_at, :requested_by)'
                );
                $stmt->execute($params + ['requested_by' => $admin['user_id']]);
                $id = (int) $pdo->lastInsertId();
            }

            // Loan closed with a recorded condition -> sync it onto the item itself (Sec. 20-C's "condition of shared resources" tracking).
            if ($isReturned && $conditionOnReturn !== null) {
                $stmt = $pdo->prepare('UPDATE equipment_items SET condition_status = :cond WHERE equipment_id = :eid');
                $stmt->execute(['cond' => $conditionOnReturn, 'eid' => $equipmentId]);
            }

            return response()->json(['success' => true, 'share_id' => $id]);
        }

        if ($action === 'delete') {
            $id = (int) ($input['share_id'] ?? 0);
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid record.'], 400);
            }
            $stmt = $pdo->prepare('DELETE FROM resource_share_requests WHERE share_id = :id');
            $stmt->execute(['id' => $id]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }

    /**
     * RLSDDP-style Report of Lost, Stolen, Damaged or Destroyed Property
     * (Art. VII Sec. 20-C.2 — "any damage or malfunction must be reported
     * immediately"). Every save (create or edit) re-syncs the linked
     * equipment_item's condition via equipment_condition_for_loss_report()
     * so the inventory never drifts out of sync with the latest report.
     */
    public function equipmentLossReports(Request $request)
    {
        $input = $request->json()->all() ?: [];
        $action = $input['action'] ?? '';
        $admin = current_user();
        $pdo = getDB();

        if ($action === 'save') {
            $id = (int) ($input['report_id'] ?? 0);
            $equipmentId = (int) ($input['equipment_id'] ?? 0);
            $reportType = $input['report_type'] ?? '';
            $incidentDateRaw = trim($input['incident_date'] ?? '');
            $description = trim($input['description'] ?? '');
            $status = $input['status'] ?? 'Reported';
            $resolutionNotes = trim($input['resolution_notes'] ?? '');

            if ($equipmentId <= 0 || !in_array($reportType, ARTEMIS_LOSS_REPORT_TYPES, true) || $description === '' || !in_array($status, ARTEMIS_LOSS_REPORT_STATUSES, true)) {
                return response()->json(['error' => 'Please select the item, a valid report type, a description, and a valid status.'], 422);
            }
            $incidentDate = $incidentDateRaw !== '' ? (strtotime($incidentDateRaw) ? date('Y-m-d', strtotime($incidentDateRaw)) : null) : null;

            $params = [
                'equipment_id' => $equipmentId, 'report_type' => $reportType, 'incident_date' => $incidentDate,
                'description' => $description, 'status' => $status, 'resolution_notes' => $resolutionNotes !== '' ? $resolutionNotes : null,
            ];

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE equipment_loss_reports SET equipment_id = :equipment_id, report_type = :report_type, incident_date = :incident_date,
                     description = :description, status = :status, resolution_notes = :resolution_notes
                     WHERE report_id = :id'
                );
                $stmt->execute($params + ['id' => $id]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO equipment_loss_reports (equipment_id, report_type, incident_date, description, status, resolution_notes, reported_by)
                     VALUES (:equipment_id, :report_type, :incident_date, :description, :status, :resolution_notes, :reported_by)'
                );
                $stmt->execute($params + ['reported_by' => $admin['user_id']]);
                $id = (int) $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare('UPDATE equipment_items SET condition_status = :cond WHERE equipment_id = :eid');
            $stmt->execute(['cond' => equipment_condition_for_loss_report($reportType), 'eid' => $equipmentId]);

            return response()->json(['success' => true, 'report_id' => $id]);
        }

        if ($action === 'delete') {
            $id = (int) ($input['report_id'] ?? 0);
            if ($id <= 0) {
                return response()->json(['error' => 'Invalid record.'], 400);
            }
            $stmt = $pdo->prepare('DELETE FROM equipment_loss_reports WHERE report_id = :id');
            $stmt->execute(['id' => $id]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
    }
}
