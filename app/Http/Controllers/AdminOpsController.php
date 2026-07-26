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
     * Under Review -> Endorsed to TAO Central -> Approved/Rejected, mirroring the
     * Head of TAO -> Assistant Director of TAO-Central -> University President
     * chain described in the Manual, adapted to the statuses this system tracks.
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

        if ($action === 'remark') {
            $remarks = trim($input['remarks'] ?? '');
            if ($remarks === '') {
                return response()->json(['error' => 'Remark text is required.'], 422);
            }
            $stmt = $pdo->prepare('UPDATE admission_appeals SET remarks = :remarks WHERE appeal_id = :id');
            $stmt->execute(['remarks' => $remarks, 'id' => $id]);
            return response()->json(['success' => true]);
        }

        if (in_array($appeal['status'], ['Approved', 'Rejected'], true)) {
            return response()->json(['error' => 'This appeal already has a final decision.'], 409);
        }

        $chain = ['Submitted', 'Under Review', 'Endorsed to TAO Central'];

        if ($action === 'advance') {
            $currentIdx = array_search($appeal['status'], $chain, true);
            if ($currentIdx === false || $currentIdx >= count($chain) - 1) {
                return response()->json(['error' => 'This appeal is already at the final review stage. Use Approve or Reject.'], 409);
            }
            $newStatus = $chain[$currentIdx + 1];
            $stmt = $pdo->prepare('UPDATE admission_appeals SET status = :status, reviewed_by = :reviewer WHERE appeal_id = :id');
            $stmt->execute(['status' => $newStatus, 'reviewer' => $admin['user_id'], 'id' => $id]);
            return response()->json(['success' => true, 'status' => $newStatus]);
        }

        if (in_array($action, ['approve', 'reject'], true)) {
            $remarks = trim($input['remarks'] ?? '');
            if ($action === 'reject' && $remarks === '') {
                return response()->json(['error' => 'Remarks are required to reject an appeal.'], 422);
            }
            $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
            $stmt = $pdo->prepare(
                'UPDATE admission_appeals SET status = :status, reviewed_by = :reviewer, remarks = :remarks, decided_at = NOW() WHERE appeal_id = :id'
            );
            $stmt->execute([
                'status' => $newStatus, 'reviewer' => $admin['user_id'],
                'remarks' => $remarks !== '' ? $remarks : $appeal['remarks'], 'id' => $id,
            ]);
            return response()->json(['success' => true, 'status' => $newStatus]);
        }

        return response()->json(['error' => 'Invalid request.'], 400);
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
}
