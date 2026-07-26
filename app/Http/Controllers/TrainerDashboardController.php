<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Trainer/Coach dashboard — the account type WI-OCA-03/04/05/10 and Art. VI
 * Sec. 17 of the Manual assign endorsement, certification, and monitoring
 * duties to, distinct from OCA (admin). A trainer sees the RPAG members
 * assigned to them (performer_profiles.trainer_id), their own Trainer Level
 * Equivalency evaluation/honorarium record, and can:
 *   - self-report training hours rendered (feeds their own honorarium), and
 *   - digitally endorse their members' applications where the type's
 *     document checklist requires a trainer endorsement (BANTOG Art. VIII
 *     Sec. 25-e / WI-OCA-10, External Invitation WI-OCA-06, Appeal WI-OCA-02)
 *     — recorded in application_endorsements and recognized by the admin's
 *     eligibility checker exactly like an uploaded endorsement letter.
 */
class TrainerDashboardController extends Controller
{
    public function index()
    {
        $trainer = current_user();
        $pdo = getDB();

        $stmt = $pdo->prepare(
            "SELECT p.*, u.first_name, u.last_name, u.id_number, u.course
             FROM performer_profiles p
             JOIN users u ON u.user_id = p.user_id
             WHERE p.trainer_id = :tid
             ORDER BY u.last_name, u.first_name"
        );
        $stmt->execute(['tid' => $trainer['user_id']]);
        $members = $stmt->fetchAll();

        $activeCount = 0;
        foreach ($members as $m) {
            if ($m['active_member']) {
                $activeCount++;
            }
        }

        $stmt = $pdo->prepare('SELECT * FROM trainer_evaluations WHERE trainer_user_id = :tid ORDER BY created_at DESC');
        $stmt->execute(['tid' => $trainer['user_id']]);
        $evaluations = $stmt->fetchAll();
        $latestEvaluation = $evaluations[0] ?? null;

        // What this trainer actually coaches right now — derived live from their
        // assigned members' own recorded talents/troupes, not a one-off evaluation
        // field, so it changes automatically as member assignments/talents change.
        $stmt = $pdo->prepare(
            "SELECT DISTINCT tc.name
             FROM performer_talents pt
             JOIN performer_profiles p ON p.profile_id = pt.profile_id
             JOIN talent_categories tc ON tc.category_id = pt.category_id
             WHERE p.trainer_id = :tid
             ORDER BY tc.name"
        );
        $stmt->execute(['tid' => $trainer['user_id']]);
        $memberDisciplines = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $disciplineLabel = $memberDisciplines ? implode(', ', $memberDisciplines) : 'No RPAG members assigned yet';

        // Applications belonging to this trainer's own RPAG members, with whether
        // this application's type requires a trainer endorsement and whether one
        // has already been recorded.
        $stmt = $pdo->prepare(
            "SELECT a.application_id, a.application_code, a.status, a.submitted_at, t.code AS type_code, t.name AS type_name,
                    u.first_name, u.last_name,
                    (SELECT COUNT(*) FROM application_endorsements e WHERE e.application_id = a.application_id AND e.trainer_user_id = :tid) AS endorsed_count,
                    (SELECT MAX(endorsed_at) FROM application_endorsements e WHERE e.application_id = a.application_id AND e.trainer_user_id = :tid2) AS endorsed_at
             FROM applications a
             JOIN application_types t ON t.type_id = a.type_id
             JOIN users u ON u.user_id = a.user_id
             JOIN performer_profiles p ON p.user_id = a.user_id
             WHERE p.trainer_id = :tid3
             ORDER BY a.submitted_at DESC"
        );
        $stmt->execute(['tid' => $trainer['user_id'], 'tid2' => $trainer['user_id'], 'tid3' => $trainer['user_id']]);
        $memberApplications = $stmt->fetchAll();

        $endorsementRequirement = application_document_requirements();
        foreach ($memberApplications as &$app) {
            $app['requires_endorsement'] = (bool) ($endorsementRequirement[$app['type_code']]['endorsement'] ?? false);
            $app['endorsed'] = (int) $app['endorsed_count'] > 0;
        }
        unset($app);

        // Real breakdowns for the dashboard charts — counted from the same
        // member-applications list the table below renders, never a separate
        // hardcoded figure.
        $memberStatusCounts = ['Approved' => 0, 'Pending' => 0, 'Under Review' => 0, 'Evaluation' => 0, 'Rejected' => 0];
        $memberTypeCounts = [];
        foreach ($memberApplications as $app) {
            if (isset($memberStatusCounts[$app['status']])) {
                $memberStatusCounts[$app['status']]++;
            }
            $memberTypeCounts[$app['type_name']] = ($memberTypeCounts[$app['type_name']] ?? 0) + 1;
        }

        // Audition/Recruitment applicants awaiting a rating — WI-OCA-03 has the
        // trainer (with RPAG officers) rate and deliberate on every audition
        // applicant, not just this trainer's own already-assigned members, so
        // this is intentionally NOT scoped to performer_profiles.trainer_id.
        // Each applicant's declared discipline(s) come from performer_talents,
        // the only link recorded at submission time (applications itself has
        // no talent_category_id column). This trainer's own rating (if any) is
        // left-joined in so the same list doubles as the "already rated" view.
        $stmt = $pdo->prepare(
            "SELECT a.application_id, a.application_code, a.status, a.submitted_at,
                    u.user_id, u.first_name, u.last_name, u.id_number, u.course,
                    GROUP_CONCAT(DISTINCT tc.name ORDER BY tc.name SEPARATOR ', ') AS talent_categories,
                    r.rating_id, r.score, r.decision, r.remarks, r.rated_at
             FROM applications a
             JOIN application_types t ON t.type_id = a.type_id
             JOIN users u ON u.user_id = a.user_id
             LEFT JOIN performer_profiles p ON p.user_id = a.user_id
             LEFT JOIN performer_talents pt ON pt.profile_id = p.profile_id
             LEFT JOIN talent_categories tc ON tc.category_id = pt.category_id
             LEFT JOIN audition_ratings r ON r.application_id = a.application_id AND r.trainer_id = :tid
             WHERE t.code = 'audition_recruitment'
             GROUP BY a.application_id
             ORDER BY (r.rating_id IS NULL) DESC, a.submitted_at DESC"
        );
        $stmt->execute(['tid' => $trainer['user_id']]);
        $auditionApplications = $stmt->fetchAll();
        $auditionPendingCount = 0;
        foreach ($auditionApplications as $aa) {
            if ($aa['rating_id'] === null) {
                $auditionPendingCount++;
            }
        }

        $fullName = trim($trainer['first_name'] . ' ' . $trainer['last_name']);
        $initials = strtoupper(mb_substr($trainer['first_name'], 0, 1) . mb_substr($trainer['last_name'], 0, 1));

        $pageTitle = 'Trainer Dashboard';

        return view('pages.trainer_dashboard', get_defined_vars());
    }

    /** Trainer self-reports their own hours rendered this period — recomputes honorarium if a rate is already on file. */
    public function updateHours(Request $request)
    {
        $trainer = current_user();
        $pdo = getDB();

        $hours = $request->input('hours_rendered', '');
        if ($hours === '' || !is_numeric($hours) || (float) $hours < 0) {
            flash_set('error', 'Please enter a valid number of hours.');
            return redirect()->route('trainer.dashboard', ['tab' => 'evaluation']);
        }
        $hours = round((float) $hours, 2);

        $stmt = $pdo->prepare('SELECT evaluation_id, hourly_rate FROM trainer_evaluations WHERE trainer_user_id = :tid ORDER BY created_at DESC LIMIT 1');
        $stmt->execute(['tid' => $trainer['user_id']]);
        $evaluation = $stmt->fetch();

        if (!$evaluation) {
            flash_set('error', 'OCA hasn\'t recorded a Trainer Level Equivalency evaluation for you yet, so hours can\'t be logged.');
            return redirect()->route('trainer.dashboard', ['tab' => 'evaluation']);
        }

        $computed = $evaluation['hourly_rate'] !== null ? round((float) $evaluation['hourly_rate'] * $hours, 2) : null;

        $stmt = $pdo->prepare('UPDATE trainer_evaluations SET hours_rendered = :hours, computed_honorarium = :computed WHERE evaluation_id = :id');
        $stmt->execute(['hours' => $hours, 'computed' => $computed, 'id' => $evaluation['evaluation_id']]);

        flash_set('success', 'Your training hours have been updated.');
        return redirect()->route('trainer.dashboard', ['tab' => 'evaluation']);
    }

    /** Digitally endorse one of this trainer's own members' applications (WI-OCA-10/06/02 endorsement requirement). */
    public function endorseApplication(Request $request)
    {
        $trainer = current_user();
        $pdo = getDB();

        $applicationId = (int) $request->input('application_id', 0);
        $note = trim((string) $request->input('note', ''));

        if ($applicationId <= 0) {
            flash_set('error', 'Invalid application.');
            return redirect()->route('trainer.dashboard', ['tab' => 'applications']);
        }

        // Authorization boundary: only allow endorsing applications from users
        // who are this trainer's own assigned RPAG members.
        $stmt = $pdo->prepare(
            "SELECT a.application_id, t.code AS type_code
             FROM applications a
             JOIN application_types t ON t.type_id = a.type_id
             JOIN performer_profiles p ON p.user_id = a.user_id
             WHERE a.application_id = :aid AND p.trainer_id = :tid"
        );
        $stmt->execute(['aid' => $applicationId, 'tid' => $trainer['user_id']]);
        $app = $stmt->fetch();

        if (!$app) {
            flash_set('error', 'That application is not assigned to you.');
            return redirect()->route('trainer.dashboard', ['tab' => 'applications']);
        }

        $requirements = application_document_requirements();
        if (empty($requirements[$app['type_code']]['endorsement'])) {
            flash_set('error', 'This application type does not require a trainer endorsement.');
            return redirect()->route('trainer.dashboard', ['tab' => 'applications']);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO application_endorsements (application_id, trainer_user_id, note) VALUES (:aid, :tid, :note)
             ON DUPLICATE KEY UPDATE note = VALUES(note), endorsed_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute(['aid' => $applicationId, 'tid' => $trainer['user_id'], 'note' => $note !== '' ? $note : null]);

        flash_set('success', 'Endorsement recorded.');
        return redirect()->route('trainer.dashboard', ['tab' => 'applications']);
    }

    /** Records/updates this trainer's score, Pass/Fail decision, and remarks for an audition/recruitment applicant (WI-OCA-03). */
    public function rateAudition(Request $request)
    {
        $trainer = current_user();
        $pdo = getDB();

        $applicationId = (int) $request->input('application_id', 0);
        $score = trim((string) $request->input('score', ''));
        $decision = $request->input('decision', '');
        $remarks = trim((string) $request->input('remarks', ''));

        if ($applicationId <= 0) {
            flash_set('error', 'Invalid application.');
            return redirect()->route('trainer.dashboard', ['tab' => 'auditions']);
        }
        if (!in_array($decision, ['Pass', 'Fail'], true)) {
            flash_set('error', 'Please select a Pass or Fail decision.');
            return redirect()->route('trainer.dashboard', ['tab' => 'auditions']);
        }
        if ($score !== '' && (!is_numeric($score) || (float) $score < 0 || (float) $score > 100)) {
            flash_set('error', 'Score must be a number between 0 and 100.');
            return redirect()->route('trainer.dashboard', ['tab' => 'auditions']);
        }
        $score = $score !== '' ? round((float) $score, 2) : null;

        $stmt = $pdo->prepare(
            "SELECT a.application_id FROM applications a
             JOIN application_types t ON t.type_id = a.type_id
             WHERE a.application_id = :aid AND t.code = 'audition_recruitment'"
        );
        $stmt->execute(['aid' => $applicationId]);
        if (!$stmt->fetch()) {
            flash_set('error', 'That application is not an Audition/Recruitment application.');
            return redirect()->route('trainer.dashboard', ['tab' => 'auditions']);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO audition_ratings (application_id, trainer_id, score, decision, remarks)
             VALUES (:aid, :tid, :score, :decision, :remarks)
             ON DUPLICATE KEY UPDATE score = VALUES(score), decision = VALUES(decision), remarks = VALUES(remarks), rated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'aid' => $applicationId,
            'tid' => $trainer['user_id'],
            'score' => $score,
            'decision' => $decision,
            'remarks' => $remarks !== '' ? $remarks : null,
        ]);

        flash_set('success', 'Audition rating saved.');
        return redirect()->route('trainer.dashboard', ['tab' => 'auditions']);
    }
}
