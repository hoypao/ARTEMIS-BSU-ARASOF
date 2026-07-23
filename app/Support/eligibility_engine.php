<?php
/**
 * Rule-based eligibility inference for OCA application types. Checks a
 * student's performer profile and submitted documents against the criteria
 * stated in the Manual (Art. IV Sec. 11, Art. VIII Sec. 22, Art. IX Sec. 30,
 * Art. X Sec. 35) so OCA staff see a pass/fail breakdown instead of having
 * to re-derive it by hand from each applicant's records.
 *
 * A check's `pass` is tri-state: true/false, or null when there isn't enough
 * data on file to verify it (this never fails the overall verdict on its own).
 */


function evaluate_application_eligibility(string $typeCode, ?array $profile, array $uploadedDocTypes): array
{
    $checks = [];

    // Check 1: does the applicant have every document this type requires (from the
    // same catalog apply_action.php validates against), regardless of the manual criteria below.
    $required = application_document_requirements()[$typeCode] ?? [];
    $requiredLabels = [];
    foreach ($required as $key => $isRequired) {
        if (!$isRequired) {
            continue;
        }
        $requiredLabels[] = ARTEMIS_DOCUMENT_CATALOG[$key] ?? $key;
    }
    $missingDocs = array_diff($requiredLabels, $uploadedDocTypes);
    $checks[] = [
        'label' => 'Required documents submitted',
        'pass' => empty($requiredLabels) ? null : empty($missingDocs),
        'detail' => empty($requiredLabels)
            ? 'This application type has no required-document checklist.'
            : (empty($missingDocs) ? 'All required supporting documents are on file.' : 'Missing: ' . implode(', ', $missingDocs)),
    ];

    // 5-point scale, higher = worse; anything above 3.00 counts as a failing mark.
    $gwa = $profile['gwa'] ?? null;
    $failingGrades = $gwa === null ? null : ((float) $gwa > 3.00);
    $gwaDetail = $gwa === null ? 'No GWA on file to verify.' : ($failingGrades ? "Recorded GWA ({$gwa}) indicates a failing mark." : "Recorded GWA ({$gwa}) is within passing range.");
    $activeMember = $profile !== null && (int) ($profile['active_member'] ?? 0) === 1;
    $yearsActive = $profile['years_active'] ?? null;
    $hasTalents = $profile !== null && (int) ($profile['talent_count'] ?? 0) > 0;

    switch ($typeCode) {
        case 'stipend':
            $checks[] = ['label' => 'Active RPAG member (Art. IX Sec. 30-b)', 'pass' => $profile === null ? null : $activeMember, 'detail' => $profile === null ? 'No performer profile on file.' : ($activeMember ? 'Marked as an active member in their performer profile.' : 'Not currently marked as an active RPAG member.')];
            // Sec. 30-b also requires membership "for at least two consecutive semesters";
            // years_active is the closest tenure signal on file (1 year ≈ two semesters).
            $checks[] = ['label' => 'Member for at least two consecutive semesters (Art. IX Sec. 30-b)', 'pass' => $yearsActive === null ? null : ($yearsActive >= 1), 'detail' => $yearsActive === null ? 'No membership tenure on file to verify.' : ($yearsActive >= 1 ? "{$yearsActive} year(s) of membership on file (≥ two consecutive semesters)." : 'Less than one year of membership on file — two consecutive semesters not yet reached.')];
            $checks[] = ['label' => 'No failing grades (Art. IX Sec. 30-d)', 'pass' => $failingGrades === null ? null : !$failingGrades, 'detail' => $gwaDetail];
            break;

        case 'pathfit_exemption':
            $checks[] = ['label' => 'Active RPAG member (Art. X Sec. 35-a)', 'pass' => $profile === null ? null : $activeMember, 'detail' => $profile === null ? 'No performer profile on file.' : ($activeMember ? 'Marked as an active member.' : 'Not currently marked as an active RPAG member.')];
            $checks[] = ['label' => 'Good standing (Art. X Sec. 35-b)', 'pass' => $failingGrades === null ? null : !$failingGrades, 'detail' => $gwaDetail];
            $checks[] = ['label' => 'Documented training/rehearsal participation (Art. X Sec. 35-c)', 'pass' => $profile === null ? null : $hasTalents, 'detail' => $hasTalents ? 'Has at least one recorded talent/discipline.' : 'No recorded talent/discipline on file.'];
            break;

        case 'bantog_recognition':
            $checks[] = ['label' => 'At least 2 years residency (Art. VIII Sec. 22-b)', 'pass' => $yearsActive === null ? null : ($yearsActive >= 2), 'detail' => $yearsActive !== null ? "{$yearsActive} year(s) on file." : 'No tenure on file.'];
            $checks[] = ['label' => 'Active RPAG member within 2 years (Art. VIII Sec. 22-c)', 'pass' => $profile === null ? null : $activeMember, 'detail' => $profile === null ? 'No performer profile on file.' : ($activeMember ? 'Marked as an active member.' : 'Not currently marked as an active RPAG member.')];
            $checks[] = ['label' => 'No failing grades (Art. VIII Sec. 22-d)', 'pass' => $failingGrades === null ? null : !$failingGrades, 'detail' => $gwaDetail];
            $checks[] = ['label' => 'Trained/participated in a discipline (Art. VIII Sec. 22-e)', 'pass' => $profile === null ? null : $hasTalents, 'detail' => $hasTalents ? 'Has at least one recorded talent/discipline.' : 'No recorded talent/discipline on file.'];
            break;

        case 'appeal_admission':
            $checks[] = ['label' => 'Satisfactory academic standing (Art. IV Sec. 11-A.2)', 'pass' => $failingGrades === null ? null : !$failingGrades, 'detail' => $gwaDetail];
            break;

        default:
            break;
    }

    // Only a hard "false" blocks eligibility; "null" (not enough data) never does,
    // so staff still get a verdict even when a profile field was never filled in.
    $eligible = true;
    foreach ($checks as $c) {
        if ($c['pass'] === false) {
            $eligible = false;
            break;
        }
    }

    return ['eligible' => $eligible, 'checks' => $checks];
}
