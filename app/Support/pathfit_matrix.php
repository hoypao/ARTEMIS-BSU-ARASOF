<?php
/**
 * PATHFit Training Equivalency Program Matrix (Art. X, between Sec. 36 and
 * Sec. 37) — maps PATHFit standard outcomes to the outcomes produced by the
 * Culture and Arts Development Training Program. Used to auto-generate the
 * written equivalency justification instead of OCA staff drafting it from
 * scratch for every PATHFit exemption request.
 */

const ARTEMIS_PATHFIT_MATRIX = [
    'Active and Healthy Living' => [
        'pathfit' => 'Participate in moderate to vigorous physical activities (MVPAs); adapt movement competencies to independent, health-enhancing physical activity.',
        'culture_arts' => 'Enhanced physical fitness, endurance, and discipline through consistent regular and special RPAG training/rehearsals, with measurable improvement carried into competition performances.',
    ],
    'Advocacy and Action' => [
        'pathfit' => 'Devise and apply strategies to improve one\'s own and others\' physical activity performance, safety, and wellness.',
        'culture_arts' => 'Increased awareness of fitness, nutrition, and injury prevention/management sustained through the physical demands of regular rehearsal and performance participation.',
    ],
];

// Builds the plain-text justification staff paste into the application remarks;
// $eligibility (from evaluate_application_eligibility()) is optional so this can
// also be used to preview the matrix text on its own.
function generate_pathfit_justification(string $studentName, ?string $discipline, ?array $eligibility): string
{
    $lines = [];
    $lines[] = "PATHFit Equivalency Justification — {$studentName}" . ($discipline ? " ({$discipline})" : '');
    $lines[] = 'Per Art. X (Exemption from PATHFit Classes for Student Artists), Training Equivalency Program Matrix:';
    foreach (ARTEMIS_PATHFIT_MATRIX as $standard => $rows) {
        $lines[] = "- {$standard}: {$rows['culture_arts']}";
    }
    if ($eligibility !== null) {
        $lines[] = '';
        $lines[] = 'Eligibility check (Sec. 35):';
        foreach ($eligibility['checks'] as $check) {
            $mark = $check['pass'] === true ? 'PASS' : ($check['pass'] === false ? 'FAIL' : 'UNVERIFIED');
            $lines[] = "  [{$mark}] {$check['label']} — {$check['detail']}";
        }
    }
    $lines[] = '';
    $lines[] = 'Recommendation: rigorous RPAG training/rehearsal fulfills the physical education requirements outlined in the PATHFit syllabi (Sec. 35-c). Grade of 1.00 upon submission of all required documentation (Sec. 38-a).';
    return implode("\n", $lines);
}
