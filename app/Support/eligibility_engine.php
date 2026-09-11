<?php
/**
 * Rule-based eligibility inference for OCA application types.
 *
 * Important design rule: ARTEMIS may assist reviewers, but it must not invent
 * institutional evidence. Criteria such as "no failing grades", "good standing",
 * or PATHFit-equivalent physical training are therefore treated as UNVERIFIED
 * unless the application/profile contains an explicit verified signal for them.
 * A GWA alone is not used to infer the absence of a failed course.
 *
 * A check's `pass` is tri-state:
 *   true  = verified evidence satisfies the criterion
 *   false = verified evidence does not satisfy the criterion
 *   null  = insufficient authoritative evidence; human review is required
 *
 * `verdict` is one of Eligible / Not Eligible / Needs Verification.
 * The legacy `eligible` boolean is retained for backwards compatibility with
 * existing UI code and is deliberately conservative: it is true only when every
 * required check is positively verified. New UI/workflow code should prefer
 * `verdict` so it can distinguish a verified failure from missing evidence.
 */

/**
 * Read an explicitly verified boolean-like value from an associative array.
 * Missing/null values remain null instead of being guessed from proxies.
 */
function eligibility_verified_bool(?array $record, string $key): ?bool
{
    if ($record === null || !array_key_exists($key, $record) || $record[$key] === null || $record[$key] === '') {
        return null;
    }

    $value = $record[$key];
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value) || is_float($value)) {
        return (bool) $value;
    }
    if (is_string($value)) {
        $normalized = strtolower(trim($value));
        if (in_array($normalized, ['1', 'true', 'yes', 'verified', 'pass', 'passing', 'good'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'failed', 'fail', 'not verified', 'bad'], true)) {
            return false;
        }
    }

    return null;
}

/**
 * @param array<int, string> $uploadedDocTypes Human-readable document labels
 * @return array{eligible: bool, verdict: string, checks: array<int, array{label:string, pass:?bool, detail:string}>}
 */
function evaluate_application_eligibility(string $typeCode, ?array $profile, array $uploadedDocTypes): array
{
    $checks = [];

    // Documentary completeness comes from the same canonical catalog used by
    // submission validation. Digital endorsements can be injected by callers as
    // their catalog label, so an electronic endorsement counts like an upload.
    $required = application_document_requirements()[$typeCode] ?? [];
    $requiredLabels = [];
    foreach ($required as $key => $isRequired) {
        if ($isRequired) {
            $requiredLabels[] = ARTEMIS_DOCUMENT_CATALOG[$key] ?? $key;
        }
    }
    $missingDocs = array_values(array_diff($requiredLabels, $uploadedDocTypes));
    $checks[] = [
        'label' => 'Required documents submitted',
        'pass' => empty($requiredLabels) ? null : empty($missingDocs),
        'detail' => empty($requiredLabels)
            ? 'This application type has no required-document checklist.'
            : (empty($missingDocs)
                ? 'All required supporting documents are on file.'
                : 'Missing: ' . implode(', ', $missingDocs)),
    ];

    $activeMember = $profile === null ? null : ((int) ($profile['active_member'] ?? 0) === 1);
    $yearsActive = $profile['years_active'] ?? null;
    $hasTalents = $profile !== null && (int) ($profile['talent_count'] ?? 0) > 0;

    // Do not infer "no failing grades" from GWA. The Manual/WIs state the
    // criterion in terms of failed courses/grades, not an average. Until the
    // system stores subject-level grades or a reviewer verifies the uploaded
    // grade record, the criterion must remain unverified.
    $hasFailingGrades = eligibility_verified_bool($profile, 'has_failing_grades');
    $noFailingGrades = $hasFailingGrades === null ? null : !$hasFailingGrades;
    $academicDetail = $noFailingGrades === null
        ? 'Needs verification: no verified course-level failing-grade status is on file. GWA alone is not sufficient to prove that no course was failed.'
        : ($noFailingGrades
            ? 'Verified record indicates no failing course.'
            : 'Verified record indicates at least one failing course.');

    switch ($typeCode) {
        case 'stipend':
            $checks[] = [
                'label' => 'Active RPAG member (Art. IX Sec. 30-b)',
                'pass' => $activeMember,
                'detail' => $profile === null
                    ? 'No performer profile on file.'
                    : ($activeMember ? 'Marked as an active RPAG member.' : 'Not currently marked as an active RPAG member.'),
            ];
            $checks[] = [
                'label' => 'Member for at least two consecutive semesters (Art. IX Sec. 30-b)',
                'pass' => $yearsActive === null ? null : ((float) $yearsActive >= 1),
                'detail' => $yearsActive === null
                    ? 'Needs verification: no membership-tenure evidence is on file.'
                    : ((float) $yearsActive >= 1
                        ? "{$yearsActive} year(s) of membership is recorded; reviewer should confirm the semesters are consecutive."
                        : 'Recorded tenure is less than one year.'),
            ];
            $checks[] = [
                'label' => 'No failing grades in the credited semesters (Art. IX Sec. 30-d)',
                'pass' => $noFailingGrades,
                'detail' => $academicDetail,
            ];
            break;

        case 'pathfit_exemption':
            $checks[] = [
                'label' => 'Active RPAG member (Art. X Sec. 35-a)',
                'pass' => $activeMember,
                'detail' => $profile === null
                    ? 'No performer profile on file.'
                    : ($activeMember ? 'Marked as an active RPAG member.' : 'Not currently marked as an active RPAG member.'),
            ];

            // "Good standing" covers more than grades. It must be explicitly
            // verified by the responsible office/faculty instead of inferred from GWA.
            $goodStanding = eligibility_verified_bool($profile, 'good_standing_verified');
            $checks[] = [
                'label' => 'Good standing with the organization and University (Art. X Sec. 35-b)',
                'pass' => $goodStanding,
                'detail' => $goodStanding === null
                    ? 'Needs verification: good standing has not yet been explicitly verified; attendance, academic, and behavioral standing require reviewer confirmation.'
                    : ($goodStanding ? 'Good standing has been verified.' : 'Good standing was not verified.'),
            ];

            $performanceProofLabel = ARTEMIS_DOCUMENT_CATALOG['performance_proof'];
            $hasPerformanceProof = in_array($performanceProofLabel, $uploadedDocTypes, true);
            $checks[] = [
                'label' => 'Documented training/rehearsal participation (Art. X Sec. 35-a, c; Sec. 37-b)',
                'pass' => $hasPerformanceProof ? true : null,
                'detail' => $hasPerformanceProof
                    ? 'Proof of involvement in performances/rehearsals is on file.'
                    : ($hasTalents
                        ? 'Needs verification: a talent/discipline is recorded, but that alone does not verify actual rehearsal/training participation.'
                        : 'Needs verification: no performance/rehearsal evidence is on file.'),
            ];

            $pathfitEquivalent = eligibility_verified_bool($profile, 'pathfit_equivalent_training_verified');
            $checks[] = [
                'label' => 'Training/rehearsals meet PATHFit-equivalent physical demands (Art. X Sec. 35-c)',
                'pass' => $pathfitEquivalent,
                'detail' => $pathfitEquivalent === null
                    ? 'Needs verification: physical-intensity equivalence must be verified from the training schedule/hours and supporting certification by the trainer/OCA and PATHFit faculty.'
                    : ($pathfitEquivalent ? 'PATHFit-equivalent physical demand has been verified.' : 'Verified training evidence does not meet the required physical-intensity equivalence.'),
            ];
            break;

        case 'bantog_recognition':
            $checks[] = [
                'label' => 'At least 2 years residency in the University (Art. VIII Sec. 22-b)',
                'pass' => $yearsActive === null ? null : ((float) $yearsActive >= 2),
                'detail' => $yearsActive === null
                    ? 'Needs verification: no verified University residency duration is on file.'
                    : "{$yearsActive} year(s) is currently recorded in the performer profile; reviewer should verify this represents University residency, not merely RPAG tenure.",
            ];
            $checks[] = [
                'label' => 'Active RPAG member within 2 years (Art. VIII Sec. 22-c)',
                'pass' => $activeMember,
                'detail' => $profile === null
                    ? 'No performer profile on file.'
                    : ($activeMember ? 'Marked as an active RPAG member.' : 'Not currently marked as an active RPAG member.'),
            ];
            $checks[] = [
                'label' => 'No failing grades (Art. VIII Sec. 22-d)',
                'pass' => $noFailingGrades,
                'detail' => $academicDetail,
            ];
            $checks[] = [
                'label' => 'Trained/participated in a recognized discipline (Art. VIII Sec. 22-e)',
                'pass' => $profile === null ? null : $hasTalents,
                'detail' => $hasTalents
                    ? 'At least one recorded talent/discipline is on file.'
                    : 'No recorded talent/discipline is on file.',
            ];
            break;

        case 'appeal_admission':
            $academicStanding = eligibility_verified_bool($profile, 'academic_standing_verified');
            $checks[] = [
                'label' => 'Satisfactory academic standing (Art. IV Sec. 11-A.2)',
                'pass' => $academicStanding,
                'detail' => $academicStanding === null
                    ? 'Needs verification: satisfactory academic standing has not yet been explicitly verified from the applicant\'s secondary-school record.'
                    : ($academicStanding ? 'Satisfactory academic standing has been verified.' : 'The submitted record does not verify satisfactory academic standing.'),
            ];
            break;

        default:
            break;
    }

    $hasFailure = false;
    $hasUnverified = false;
    foreach ($checks as $check) {
        if ($check['pass'] === false) {
            $hasFailure = true;
        } elseif ($check['pass'] === null) {
            $hasUnverified = true;
        }
    }

    $verdict = $hasFailure
        ? 'Not Eligible'
        : ($hasUnverified ? 'Needs Verification' : 'Eligible');

    return [
        // Existing binary UI can only show Eligible / Not Eligible. Be conservative
        // there: missing evidence is never rendered as a green Eligible result.
        'eligible' => $verdict === 'Eligible',
        'verdict' => $verdict,
        'checks' => $checks,
    ];
}
