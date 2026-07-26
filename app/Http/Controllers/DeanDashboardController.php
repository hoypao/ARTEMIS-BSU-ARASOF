<?php

namespace App\Http\Controllers;

/**
 * College Dean dashboard — WI-OCA-09 (Art. V Sec. 16-A.1) assigns the
 * College Dean (not OCA) the written-warning step for a faculty member's
 * first non-compliance violation against a student-artist. A Dean only sees
 * complaints filed against faculty in their own college
 * (faculty_noncompliance_complaints.college, matched against users.college)
 * and can act only on First Violation complaints — a Repeated Violation
 * auto-escalates to a Grievance Board chaired by HRMO instead (Art. V Sec.
 * 16-A.2), outside the Dean's authority, so those render read-only here.
 */
class DeanDashboardController extends Controller
{
    public function index()
    {
        $dean = current_user();
        $pdo = getDB();

        $stmt = $pdo->prepare(
            'SELECT * FROM faculty_noncompliance_complaints WHERE college = :college ORDER BY created_at DESC'
        );
        $stmt->execute(['college' => $dean['college'] ?? null]);
        $complaints = $stmt->fetchAll();

        foreach ($complaints as &$c) {
            $c['recommended_action'] = escalation_recommended_action($c['escalation_level']);
            $c['dean_actionable'] = $c['escalation_level'] === 'First Violation' && $c['status'] !== 'Resolved';
        }
        unset($c);

        $firstViolationCount = 0;
        $repeatedCount = 0;
        $resolvedCount = 0;
        foreach ($complaints as $c) {
            if ($c['escalation_level'] === 'First Violation') {
                $firstViolationCount++;
            } else {
                $repeatedCount++;
            }
            if ($c['status'] === 'Resolved') {
                $resolvedCount++;
            }
        }

        $fullName = trim($dean['first_name'] . ' ' . $dean['last_name']);
        $initials = strtoupper(mb_substr($dean['first_name'], 0, 1) . mb_substr($dean['last_name'], 0, 1));

        $pageTitle = 'College Dean Dashboard';

        return view('pages.dean_dashboard', get_defined_vars());
    }
}
