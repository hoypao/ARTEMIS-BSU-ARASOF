<?php

namespace App\Http\Controllers;

/**
 * TAO Central reviewer dashboard — Art. IV Sec. 11-C splits an admission appeal
 * between two offices. OCA screens it (Submitted -> Under Review (OCA)), then
 * hands it to the Testing and Admission Office - Central, which evaluates it and
 * carries it to the University President for the final decision.
 *
 * This dashboard is the second half of that chain and nothing else: it lists
 * only appeals sitting at ARTEMIS_APPEAL_TAO_STAGES, the same two statuses
 * AdminOpsController::admissionAppeals() will let this role act on. The filter
 * here is presentation; the authority check there is what actually enforces it,
 * so a hand-crafted POST for an OCA-stage appeal still gets a 403.
 *
 * Kept as its own controller/view/route rather than a mode of the admin
 * dashboard — a TAO reviewer is not an OCA administrator, and shipping them the
 * admin page would mean shipping every other office's data in the same payload.
 */
class TaoDashboardController extends Controller
{
    public function index()
    {
        $reviewer = current_user();
        $pdo = getDB();

        $placeholders = implode(',', array_fill(0, count(ARTEMIS_APPEAL_TAO_STAGES), '?'));
        $stmt = $pdo->prepare(
            "SELECT * FROM admission_appeals WHERE status IN ($placeholders) ORDER BY submitted_at ASC"
        );
        $stmt->execute(ARTEMIS_APPEAL_TAO_STAGES);
        $appeals = $stmt->fetchAll();

        // Oldest first above: these are queues waiting on this office, so the
        // one that has been waiting longest is the one to act on.

        $evaluationCount = 0;
        $forApprovalCount = 0;
        foreach ($appeals as $a) {
            if ($a['status'] === 'Evaluation Stage') {
                $evaluationCount++;
            } else {
                $forApprovalCount++;
            }
        }

        // Decided appeals are read-only history, shown in their own sub-view
        // rather than only as a number. Not stage-filtered: once a ruling is
        // recorded there is no stage left to scope by, and this office needs to
        // be able to look up what it decided.
        $decidedAppeals = $pdo->query(
            "SELECT * FROM admission_appeals
             WHERE status IN ('Approved','Rejected')
             ORDER BY decided_at DESC, appeal_id DESC
             LIMIT 100"
        )->fetchAll();

        $decidedThisYear = 0;
        foreach ($decidedAppeals as $d) {
            if ($d['decided_at'] !== null && (int) date('Y', strtotime($d['decided_at'])) === (int) date('Y')) {
                $decidedThisYear++;
            }
        }

        // Recent activity for the overview: the newest movements this office
        // cares about, decided or waiting, newest first. COALESCE so an appeal
        // still in the queue sorts by when it arrived.
        $activity = $pdo->query(
            "SELECT appeal_id, full_name, discipline, status, submitted_at, decided_at,
                    COALESCE(decided_at, submitted_at) AS activity_at
             FROM admission_appeals
             WHERE status IN ('Evaluation Stage','For Approval (President via TAO)','Approved','Rejected')
             ORDER BY activity_at DESC, appeal_id DESC
             LIMIT 8"
        )->fetchAll();

        // The appeal cards render client-side, so the shared 5-circle tracker is
        // pre-rendered once per stage and handed to JS as a lookup rather than
        // reimplemented there — same approach the admin dashboard uses.
        $appealTrackerHtml = [];
        foreach (range(1, count(ARTEMIS_APPEAL_CHAIN) + 1) as $stage) {
            $appealTrackerHtml[$stage] = admin_progress_tracker_html($stage, 'appeal_admission');
        }

        $fullName = trim($reviewer['first_name'] . ' ' . $reviewer['last_name']);
        $initials = strtoupper(mb_substr($reviewer['first_name'], 0, 1) . mb_substr($reviewer['last_name'], 0, 1));

        $pageTitle = 'TAO Central Dashboard';

        return view('pages.tao_dashboard', get_defined_vars());
    }
}
