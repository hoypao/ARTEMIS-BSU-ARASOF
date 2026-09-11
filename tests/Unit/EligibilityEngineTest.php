<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for the OCA eligibility decision-support rules.
 *
 * ARTEMIS must distinguish verified eligibility from missing evidence. In
 * particular, an acceptable GWA does not prove that the student has no failed
 * course, and merely having a recorded talent does not prove PATHFit-equivalent
 * physical training.
 */
class EligibilityEngineTest extends TestCase
{
    public function test_gwa_alone_does_not_prove_no_failing_grades_for_stipend(): void
    {
        $profile = [
            'active_member' => 1,
            'years_active' => 2,
            'gwa' => '1.75',
            'talent_count' => 1,
        ];

        $documents = [
            ARTEMIS_DOCUMENT_CATALOG['performance_list'],
            ARTEMIS_DOCUMENT_CATALOG['consent'],
            ARTEMIS_DOCUMENT_CATALOG['grades'],
            ARTEMIS_DOCUMENT_CATALOG['membership'],
            ARTEMIS_DOCUMENT_CATALOG['profile'],
        ];

        $result = evaluate_application_eligibility('stipend', $profile, $documents);

        $this->assertSame('Needs Verification', $result['verdict']);
        // The old dashboard still consumes this binary flag. Unknown evidence
        // must therefore fail closed instead of being shown as green Eligible.
        $this->assertFalse($result['eligible']);
        $academicCheck = $this->findCheck($result['checks'], 'No failing grades in the credited semesters');
        $this->assertNull($academicCheck['pass']);
        $this->assertStringContainsString('GWA alone is not sufficient', $academicCheck['detail']);
    }

    public function test_verified_failing_course_produces_not_eligible_verdict(): void
    {
        $profile = [
            'active_member' => 1,
            'years_active' => 2,
            'talent_count' => 1,
            'has_failing_grades' => true,
        ];

        $documents = [
            ARTEMIS_DOCUMENT_CATALOG['performance_list'],
            ARTEMIS_DOCUMENT_CATALOG['consent'],
            ARTEMIS_DOCUMENT_CATALOG['grades'],
            ARTEMIS_DOCUMENT_CATALOG['membership'],
            ARTEMIS_DOCUMENT_CATALOG['profile'],
        ];

        $result = evaluate_application_eligibility('stipend', $profile, $documents);

        $this->assertSame('Not Eligible', $result['verdict']);
        $this->assertFalse($result['eligible']);
        $academicCheck = $this->findCheck($result['checks'], 'No failing grades in the credited semesters');
        $this->assertFalse($academicCheck['pass']);
    }

    public function test_pathfit_talent_record_is_not_used_as_training_equivalency_proof(): void
    {
        $profile = [
            'active_member' => 1,
            'talent_count' => 2,
        ];

        $documents = [
            ARTEMIS_DOCUMENT_CATALOG['membership'],
            ARTEMIS_DOCUMENT_CATALOG['performance_proof'],
        ];

        $result = evaluate_application_eligibility('pathfit_exemption', $profile, $documents);

        $this->assertSame('Needs Verification', $result['verdict']);
        $this->assertFalse($result['eligible']);
        $equivalencyCheck = $this->findCheck($result['checks'], 'Training/rehearsals meet PATHFit-equivalent physical demands');
        $this->assertNull($equivalencyCheck['pass']);
        $this->assertStringContainsString('must be verified', $equivalencyCheck['detail']);
    }

    public function test_pathfit_can_become_eligible_only_with_explicit_verified_signals(): void
    {
        $profile = [
            'active_member' => 1,
            'talent_count' => 1,
            'good_standing_verified' => true,
            'pathfit_equivalent_training_verified' => true,
        ];

        $documents = [
            ARTEMIS_DOCUMENT_CATALOG['membership'],
            ARTEMIS_DOCUMENT_CATALOG['performance_proof'],
        ];

        $result = evaluate_application_eligibility('pathfit_exemption', $profile, $documents);

        $this->assertSame('Eligible', $result['verdict']);
        $this->assertTrue($result['eligible']);
    }

    /**
     * @param array<int, array{label:string, pass:?bool, detail:string}> $checks
     * @return array{label:string, pass:?bool, detail:string}
     */
    private function findCheck(array $checks, string $labelPrefix): array
    {
        foreach ($checks as $check) {
            if (str_starts_with($check['label'], $labelPrefix)) {
                return $check;
            }
        }

        $this->fail("Eligibility check not found: {$labelPrefix}");
    }
}
