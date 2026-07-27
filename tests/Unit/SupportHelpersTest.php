<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The Support helpers that encode Culture and Arts Development Manual rules.
 * These are pure functions — no PDO, no request — so they are the one part of
 * ARTEMIS that can be pinned down exactly, and they are where a silent change
 * would quietly mis-grade a trainer or mis-state an inventory.
 *
 * They are loaded globally via composer.json's autoload "files" list, so no
 * Laravel bootstrap is needed here.
 */
class SupportHelpersTest extends TestCase
{
    /** @return array<string, array{string, int}> */
    public static function appealStatuses(): array
    {
        return [
            // status, expected 1-indexed tracker stage (Art. IV Sec. 11-C)
            'submitted'      => ['Submitted', 1],
            'oca review'     => ['Under Review (OCA)', 2],
            'evaluation'     => ['Evaluation Stage', 3],
            'for approval'   => ['For Approval (President via TAO)', 4],
            'approved'       => ['Approved', 5],
            'rejected'       => ['Rejected', 5],
            // Anything unrecognised lands on the terminal circle rather than
            // throwing or rendering an empty tracker.
            'unknown'        => ['Endorsed to TAO Central', 5],
        ];
    }

    #[DataProvider('appealStatuses')]
    public function test_appeal_status_maps_to_tracker_stage(string $status, int $expected): void
    {
        $this->assertSame($expected, appeal_status_stage($status));
    }

    public function test_appeal_chain_matches_the_tracker_labels(): void
    {
        // The chain doubles as the tracker's stage labels for 'appeal_admission';
        // if these ever drift, the appeal card would name a different step than
        // the one the controller advances to.
        $stages = application_progress_stages('appeal_admission');

        $this->assertSame(ARTEMIS_APPEAL_CHAIN, array_slice($stages, 0, count(ARTEMIS_APPEAL_CHAIN)));
        $this->assertCount(count(ARTEMIS_APPEAL_CHAIN) + 1, $stages);
    }

    public function test_tao_stages_are_the_back_half_of_the_chain(): void
    {
        foreach (ARTEMIS_APPEAL_TAO_STAGES as $stage) {
            $this->assertContains($stage, ARTEMIS_APPEAL_CHAIN);
            $this->assertGreaterThan(2, appeal_status_stage($stage), 'OCA owns stages 1-2.');
        }
    }

    /** @return array<string, array{int, string, int}> */
    public static function trainerScores(): array
    {
        return [
            // total, expected level, expected salary grade  (Art. VI Sec. 17-A)
            'floor of band I'   => [5, 'Training Specialist I', 11],
            'top of band I'     => [8, 'Training Specialist I', 11],
            'floor of band II'  => [9, 'Training Specialist II', 15],
            'mid band III'      => [14, 'Training Specialist III', 18],
            'top of band IV'    => [20, 'Training Specialist IV', 22],
            'floor of band V'   => [21, 'Training Specialist V', 24],
            'perfect score'     => [25, 'Training Specialist V', 24],
        ];
    }

    #[DataProvider('trainerScores')]
    public function test_trainer_level_bands(int $total, string $expectedName, int $expectedGrade): void
    {
        $level = compute_trainer_level($total);

        $this->assertSame($expectedName, $level['name']);
        $this->assertSame($expectedGrade, $level['salary_grade']);
    }

    public function test_trainer_level_clamps_out_of_range_scores(): void
    {
        // Below the 5-25 rubric range (e.g. not every criterion rated yet).
        $this->assertSame('Training Specialist I', compute_trainer_level(0)['name']);
        // Above it.
        $this->assertSame('Training Specialist V', compute_trainer_level(99)['name']);
    }

    public function test_every_trainer_band_is_reachable_and_contiguous(): void
    {
        // Guards against a future edit leaving a gap between bands, which would
        // silently fall through to the clamp and mis-grade a trainer.
        $seen = [];
        for ($score = 5; $score <= 25; $score++) {
            $seen[compute_trainer_level($score)['name']] = true;
        }

        $this->assertSame(
            array_keys(ARTEMIS_TRAINER_LEVELS),
            array_keys($seen),
            'Every Training Specialist level should be reachable from some score in 5-25.'
        );
    }

    public function test_loss_report_maps_to_an_inventory_condition(): void
    {
        // Sec. 20-C.2 — the inventory condition enum only has four states, so
        // Lost/Stolen/Destroyed all collapse to 'Lost'.
        $this->assertSame('Damaged', equipment_condition_for_loss_report('Damaged'));
        $this->assertSame('Lost', equipment_condition_for_loss_report('Lost'));
        $this->assertSame('Lost', equipment_condition_for_loss_report('Stolen'));
        $this->assertSame('Lost', equipment_condition_for_loss_report('Destroyed'));
    }

    public function test_loss_report_mapping_always_yields_a_valid_condition(): void
    {
        foreach (ARTEMIS_LOSS_REPORT_TYPES as $type) {
            $this->assertContains(
                equipment_condition_for_loss_report($type),
                ARTEMIS_EQUIPMENT_CONDITIONS,
                "Report type '{$type}' must map onto the equipment condition enum."
            );
        }
    }

    public function test_equipment_summary_counts_by_condition(): void
    {
        $counts = summarize_equipment_conditions([
            ['condition_status' => 'Good'],
            ['condition_status' => 'Good'],
            ['condition_status' => 'Needs Repair'],
            ['condition_status' => 'Lost'],
        ]);

        $this->assertSame(2, $counts['Good']);
        $this->assertSame(1, $counts['Needs Repair']);
        $this->assertSame(0, $counts['Damaged']);
        $this->assertSame(1, $counts['Lost']);
    }

    public function test_equipment_summary_defaults_and_ignores_unknown_conditions(): void
    {
        $counts = summarize_equipment_conditions([
            [],                                    // missing key defaults to Good
            ['condition_status' => 'Pulverised'],  // not in the enum — ignored
        ]);

        $this->assertSame(1, $counts['Good']);
        $this->assertSame(0, array_sum($counts) - $counts['Good']);
    }

    public function test_empty_equipment_summary_is_all_zeroes(): void
    {
        $counts = summarize_equipment_conditions([]);

        $this->assertSame(ARTEMIS_EQUIPMENT_CONDITIONS, array_keys($counts));
        $this->assertSame(0, array_sum($counts));
    }

    public function test_escalation_action_matches_the_work_instruction(): void
    {
        // WI-OCA-09: first violation -> Dean's written warning;
        // repeat -> Grievance Board chaired by the Head of HRMO.
        $this->assertStringContainsString(
            'College Dean',
            escalation_recommended_action('First Violation')
        );
        $this->assertStringContainsString(
            'Grievance Board',
            escalation_recommended_action('Repeated Violation')
        );
    }

    public function test_appeal_disciplines_cover_the_manual_and_bantog_fields(): void
    {
        // Art. IV Sec. 12 disciplines plus the two BANTOG-only fields
        // (Art. VIII Sec. 22-23) the admission appeal form must offer.
        foreach (['Music', 'Dance', 'Theater Arts', 'Film', 'Visual Arts', 'Architecture', 'Literature'] as $discipline) {
            $this->assertContains($discipline, ARTEMIS_APPEAL_DISCIPLINES);
        }
    }
}
