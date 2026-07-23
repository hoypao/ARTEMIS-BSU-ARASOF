<?php
/**
 * QEO KPI Tracker. Computes live progress against the Office of Culture and
 * Arts' 2026 Quality/Educational Organizations Objectives and Strategic/
 * Action Plans (BatStateU-QEO-OCA-03) for the metrics this system actually
 * has ground-truth data for (Stipend, PATHFit, BANTOG benefit grants, and
 * trainer engagements) — the QEO document's other objectives (equipment
 * procurement, customer satisfaction surveys, data-privacy incidents, etc.)
 * aren't tracked by ARTEMIS and are intentionally left out rather than
 * faked.
 *
 * "Projected year-end" is a simple pace-based linear projection: actual
 * count so far divided by the fraction of the calendar year elapsed. It is
 * a rough planning signal, not a statistical forecast.
 */

function compute_qeo_kpis(PDO $pdo): array
{
    $year = (int) date('Y');
    $dayOfYear = (int) date('z') + 1;
    $daysInYear = ((int) date('L', mktime(0, 0, 0, 1, 1, $year))) ? 366 : 365;
    $paceFraction = $dayOfYear / $daysInYear;

    $metricDefs = [
        'stipend' => [
            'label' => 'Stipend Recipients',
            'citation' => 'QEO Obj. 1 (Semi-Annual) / Art. IX Sec. 29',
            'target' => 20,
            'sql' => "SELECT COUNT(*) FROM benefit_records WHERE benefit_type = 'Stipend' AND YEAR(granted_at) = :year",
        ],
        'pathfit' => [
            'label' => 'PATHFit Exemptions Granted',
            'citation' => 'QEO Obj. 2 (Semi-Annual) / Art. X Sec. 38',
            'target' => 20,
            'sql' => "SELECT COUNT(*) FROM benefit_records WHERE benefit_type = 'PATHFit Exemption' AND YEAR(granted_at) = :year",
        ],
        'bantog' => [
            'label' => 'BANTOG Recognitions Granted',
            'citation' => 'QEO Obj. 3 (Annual) / Art. VIII Sec. 26',
            'target' => 30,
            'sql' => "SELECT COUNT(*) FROM benefit_records WHERE benefit_type = 'BANTOG Recognition' AND YEAR(granted_at) = :year",
        ],
        'trainers' => [
            'label' => 'Trainers / Resource Persons Engaged',
            'citation' => 'QEO Obj. 5 (Annual) / Art. VI Sec. 17',
            'target' => 15,
            'sql' => "SELECT COUNT(*) FROM trainer_evaluations WHERE YEAR(created_at) = :year",
        ],
        // Obj. 6 & 7 rely on the admin-facing "Event Type" field (added to the event
        // form specifically for this) rather than a dedicated boolean/category column —
        // events are counted once marked "Completed" (i.e. actually held/participated in).
        'competitions' => [
            'label' => 'Cultural Competitions Participated',
            'citation' => 'QEO Obj. 6 (Annual)',
            'target' => 10,
            'sql' => "SELECT COUNT(*) FROM events WHERE status = 'Completed' AND event_type LIKE '%Competition%' AND YEAR(event_date) = :year",
        ],
        'seminars' => [
            'label' => 'Culture & Arts Seminars/Workshops Conducted',
            'citation' => 'QEO Obj. 7 (Annual)',
            'target' => 2,
            'sql' => "SELECT COUNT(*) FROM events WHERE status = 'Completed' AND (event_type LIKE '%Seminar%' OR event_type LIKE '%Workshop%') AND YEAR(event_date) = :year",
        ],
    ];

    $metrics = [];
    foreach ($metricDefs as $key => $m) {
        $stmt = $pdo->prepare($m['sql']);
        $stmt->execute(['year' => $year]);
        $actual = (int) $stmt->fetchColumn();

        // "Expected by now" is the target scaled down to how far through the year
        // we are (e.g. 50% target if we're at day 182 of 365); actual vs. that
        // expectation — not vs. the full-year target — is what sets the status below.
        $projected = $paceFraction > 0 ? (int) round($actual / $paceFraction) : $actual;
        $expectedByNow = round($m['target'] * $paceFraction, 1);

        if ($actual >= $expectedByNow) {
            $status = 'On/Ahead of Pace';
        } elseif ($actual >= $expectedByNow * 0.75) {
            // Within 75% of pace: close enough to flag as a soft warning, not a hard miss.
            $status = 'Slightly Behind';
        } else {
            $status = 'Behind Pace';
        }

        $metrics[$key] = [
            'label' => $m['label'],
            'citation' => $m['citation'],
            'target' => $m['target'],
            'actual' => $actual,
            'pctOfTarget' => $m['target'] > 0 ? round($actual / $m['target'] * 100) : 0,
            'projectedYearEnd' => $projected,
            'expectedByNow' => $expectedByNow,
            'status' => $status,
        ];
    }

    return [
        'year' => $year,
        'paceFraction' => round($paceFraction * 100, 1),
        'metrics' => $metrics,
    ];
}
