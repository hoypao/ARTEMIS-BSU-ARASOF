<?php
/** Shared render helpers for markup that recurs identically across pages (status badges, progress trackers). */

const ARTEMIS_PROGRESS_STAGES = ['Submitted', 'Under Review (OCA)', 'Evaluation Stage', 'For Approval (VPAA)', 'Approved / Rejected'];

/**
 * Per-type stage-label overrides (0-indexed stage => label) so the tracker names
 * the approver each Work Instruction actually designates, instead of the generic
 * VPAA wording: WI-OCA-03 (audition → Chancellor), WI-OCA-05/Art. IX Sec. 32
 * (stipend → Evaluation Committee, then Chancellor via VCAA), WI-OCA-04/Art. X
 * (PATHFit → PATHFit Faculty reviews and grants), WI-OCA-10 (BANTOG → first/second
 * screening then External Evaluators), WI-OCA-06 (external invitation → Chancellor
 * via VPAA), WI-OCA-02/Art. IV Sec. 11-C (appeal → University President via TAO).
 */
const ARTEMIS_STAGE_OVERRIDES = [
    'audition_recruitment' => [3 => 'For Approval (Chancellor)'],
    'stipend'              => [2 => 'Evaluation Committee', 3 => 'For Approval (Chancellor via VCAA)'],
    'pathfit_exemption'    => [1 => 'PATHFit Faculty Review', 3 => 'Grant by PATHFit Faculty'],
    'bantog_recognition'   => [1 => 'First Screening (OCA)', 2 => 'Second Screening (Director)', 3 => 'External Evaluation'],
    'external_invitation'  => [3 => 'For Approval (Chancellor via VPAA)'],
    'appeal_admission'     => [3 => 'For Approval (President via TAO)'],
];

/**
 * Admission Appeal statuses in pipeline order (Art. IV Sec. 11-C), excluding the
 * terminal Approved/Rejected pair. The wording is kept identical to
 * ARTEMIS_PROGRESS_STAGES as overridden for 'appeal_admission' above, so a
 * stored status maps straight onto the shared tracker with no lookup table —
 * index N of this array is stage N+1 of that tracker.
 */
const ARTEMIS_APPEAL_CHAIN = [
    'Submitted', 'Under Review (OCA)', 'Evaluation Stage', 'For Approval (President via TAO)',
];

/** Stages a TAO Central reviewer owns; OCA keeps everything before them. */
const ARTEMIS_APPEAL_TAO_STAGES = ['Evaluation Stage', 'For Approval (President via TAO)'];

/**
 * 1-indexed tracker stage for an appeal status. Approved/Rejected — and any
 * value not in the chain — land on the terminal circle.
 */
function appeal_status_stage(string $status): int
{
    $idx = array_search($status, ARTEMIS_APPEAL_CHAIN, true);
    return $idx === false ? count(ARTEMIS_APPEAL_CHAIN) + 1 : $idx + 1;
}

/** Stage labels for one application type (falls back to the generic pipeline). */
function application_progress_stages(?string $typeCode = null): array
{
    $stages = ARTEMIS_PROGRESS_STAGES;
    foreach (ARTEMIS_STAGE_OVERRIDES[$typeCode] ?? [] as $i => $label) {
        $stages[$i] = $label;
    }
    return $stages;
}

/** 'default' + every overridden type, for the pages that render trackers in JS. */
function application_progress_stages_by_type(): array
{
    $map = ['default' => ARTEMIS_PROGRESS_STAGES];
    foreach (array_keys(ARTEMIS_STAGE_OVERRIDES) as $code) {
        $map[$code] = application_progress_stages($code);
    }
    return $map;
}

function status_badge_html(string $status): string
{
    $map = [
        'Approved'      => ['bg' => '#DCFCE7', 'text' => '#15803D', 'dot' => '#22C55E'],
        'Rejected'      => ['bg' => '#FEE2E2', 'text' => '#B91C1C', 'dot' => '#EF4444'],
        'Pending'       => ['bg' => '#FEF9C3', 'text' => '#92400E', 'dot' => '#F59E0B'],
        'Under Review'  => ['bg' => '#DBEAFE', 'text' => '#1D4ED8', 'dot' => '#3B82F6'],
        'Evaluation'    => ['bg' => '#F3E8FF', 'text' => '#7C3AED', 'dot' => '#A855F7'],
    ];
    $s = $map[$status] ?? $map['Pending'];
    return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium" style="background:' . e($s['bg']) . ';color:' . e($s['text']) . ';">'
        . '<span class="w-1.5 h-1.5 rounded-full" style="background:' . e($s['dot']) . ';"></span>' . e($status) . '</span>';
}

/** Admin dashboard's wider variant: connecting bar + circles, labels below. */
function admin_progress_tracker_html(int $stage, ?string $typeCode = null): string
{
    $stages = application_progress_stages($typeCode);
    $total = count($stages);
    // The connecting line only spans center-of-first-circle to center-of-last-circle
    // (not the full width), so the fill % is scaled to that shorter span, not 0-100.
    $fillPct = (min($stage - 1, $total - 1) / ($total - 1)) * (100 - 100 / $total);
    $html = '<div class="w-full">';
    $html .= '<div class="relative flex items-center w-full" style="height:32px;">';
    $html .= '<div class="absolute h-0.5 bg-gray-200" style="left:' . (100 / ($total * 2)) . '%;right:' . (100 / ($total * 2)) . '%;"></div>';
    $html .= '<div class="absolute h-0.5" style="background:linear-gradient(90deg,#22C55E,#16a34a);left:' . (100 / ($total * 2)) . '%;width:' . $fillPct . '%;"></div>';
    foreach ($stages as $i => $label) {
        $n = $i + 1;
        $done = $n < $stage;
        $active = $n === $stage;
        $bg = $done ? '#22C55E' : ($active ? '#B11226' : '#fff');
        $color = ($done || $active) ? '#fff' : '#9CA3AF';
        $border = ($done || $active) ? 'none' : '2px solid #E5E7EB';
        $shadow = $active ? '0 0 0 3px rgba(177,18,38,0.15)' : 'none';
        $html .= '<div class="flex-1 flex justify-center relative z-10"><div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold" style="background:' . $bg . ';color:' . $color . ';border:' . $border . ';box-shadow:' . $shadow . ';">' . ($done ? '&#10003;' : $n) . '</div></div>';
    }
    $html .= '</div><div class="flex mt-1.5">';
    foreach ($stages as $i => $label) {
        $active = ($i + 1) === $stage;
        $style = $active ? 'color:#B11226;font-weight:600;' : 'color:#9CA3AF;font-weight:400;';
        $html .= '<div class="flex-1 flex justify-center px-0.5"><span class="text-[9px] sm:text-[10px] text-center leading-tight block" style="' . $style . '">' . e($label) . '</span></div>';
    }
    $html .= '</div></div>';
    return $html;
}

function progress_tracker_html(int $stage, ?string $typeCode = null): string
{
    $stages = application_progress_stages($typeCode);
    $html = '<div class="flex items-center gap-0 w-full">';
    foreach ($stages as $i => $label) {
        $n = $i + 1;
        $circleBg = $n < $stage ? '#22C55E' : ($n === $stage ? '#B11226' : '#E5E7EB');
        $circleColor = $n <= $stage ? '#fff' : '#9CA3AF';
        $labelStyle = $n === $stage ? 'color:#B11226;font-weight:600;' : '';
        $html .= '<div class="flex items-center flex-1">'
            . '<div class="flex flex-col items-center flex-1">'
            . '<div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold" style="background:' . e($circleBg) . ';color:' . e($circleColor) . ';">'
            . ($n < $stage ? '&#10003;' : $n) . '</div>'
            . '<span class="text-[9px] text-gray-500 mt-1 text-center leading-tight hidden sm:block" style="' . $labelStyle . '">' . e($label) . '</span>'
            . '</div>';
        if ($i < count($stages) - 1) {
            $lineBg = $n < $stage ? '#22C55E' : '#E5E7EB';
            $html .= '<div class="h-0.5 flex-1 -mt-4" style="background:' . e($lineBg) . ';"></div>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';
    return $html;
}
