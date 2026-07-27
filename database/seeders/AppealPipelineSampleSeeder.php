<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One admission appeal parked at each pre-decision stage of the Art. IV Sec.
 * 11-C chain, so the TAO Central role has something to act on and the 5-circle
 * tracker can be seen at every position.
 *
 *     php artisan db:seed --class=AppealPipelineSampleSeeder
 *
 * Re-runnable: deletes only its own fixed IDs (900-903), which sit far above
 * the auto-increment range real appeals use, so nothing submitted through the
 * public form is touched.
 *
 * IDs are also referenced by tests/Feature/TaoCentralAppealTest.php, which
 * asserts the authority rules against known statuses — keep the status of each
 * ID stable if you edit this.
 */
class AppealPipelineSampleSeeder extends Seeder
{
    public const ID_SUBMITTED = 900;
    public const ID_UNDER_REVIEW = 901;
    public const ID_EVALUATION = 902;
    public const ID_FOR_APPROVAL = 903;

    private const IDS = [
        self::ID_SUBMITTED, self::ID_UNDER_REVIEW, self::ID_EVALUATION, self::ID_FOR_APPROVAL,
    ];

    public function run(): void
    {
        DB::table('admission_appeals')->whereIn('appeal_id', self::IDS)->delete();
        DB::table('admission_appeals')->insert($this->appeals());

        $this->command?->info('Seeded ' . count(self::IDS) . ' admission appeals, one per pre-decision stage.');
    }

    private function appeals(): array
    {
        $rows = [
            [
                'appeal_id' => self::ID_SUBMITTED,
                'full_name' => 'Kristine Joy Alcantara',
                'email' => 'kj.alcantara.appeal@example.com',
                'contact_number' => '09171234567',
                'secondary_school' => 'Nasugbu East Senior High School',
                'discipline' => 'Dance',
                'achievements_summary' => 'Champion, Regional Folk Dance Festival 2025 (Region IV-A). Lead dancer, provincial Sayaw Pilipinas delegation 2024 and 2025.',
                'academic_standing_note' => 'General average 88.4; no failing marks in Grades 11-12.',
                'status' => 'Submitted',
                'remarks' => null,
                'submitted_at' => '2026-07-21 09:14:00',
            ],
            [
                'appeal_id' => self::ID_UNDER_REVIEW,
                'full_name' => 'Miguel Antonio Ferrer',
                'email' => 'ma.ferrer.appeal@example.com',
                'contact_number' => '09285554412',
                'secondary_school' => 'Batangas National High School',
                'discipline' => 'Music',
                'achievements_summary' => 'First place, National Rondalla Competition 2025 (bandurria). Member, Batangas Provincial Youth Orchestra since 2023.',
                'academic_standing_note' => 'General average 90.1.',
                'status' => 'Under Review (OCA)',
                'remarks' => 'Certificates verified against the issuing organizations. Endorsement letter still to be confirmed with the school.',
                'submitted_at' => '2026-07-16 14:32:00',
            ],
            [
                'appeal_id' => self::ID_EVALUATION,
                'full_name' => 'Patricia Mae Rivera',
                'email' => 'pm.rivera.appeal@example.com',
                'contact_number' => '09063338891',
                'secondary_school' => 'Lipa City Science Integrated School',
                'discipline' => 'Theater Arts',
                'achievements_summary' => 'Best Actress, CALABARZON Regional Theater Festival 2025. Three years with a community theater guild, including two full-length productions.',
                'academic_standing_note' => 'General average 89.7; consistent honor student.',
                'status' => 'Evaluation Stage',
                'remarks' => 'OCA screening complete and endorsed to TAO Central for evaluation (Art. IV Sec. 11-C).',
                'submitted_at' => '2026-07-08 10:05:00',
            ],
            [
                'appeal_id' => self::ID_FOR_APPROVAL,
                'full_name' => 'Joshua Emmanuel Bautista',
                'email' => 'je.bautista.appeal@example.com',
                'contact_number' => '09452227734',
                'secondary_school' => 'San Juan Senior High School',
                'discipline' => 'Visual Arts',
                'achievements_summary' => 'National finalist, Shell National Students Art Competition 2025. Two solo exhibits mounted at the municipal cultural center.',
                'academic_standing_note' => 'General average 87.2.',
                'status' => 'For Approval (President via TAO)',
                'remarks' => 'TAO Central evaluation favorable; forwarded for the University President\'s approval.',
                'submitted_at' => '2026-06-29 11:48:00',
            ],
        ];

        return array_map(static fn (array $row): array => $row + [
            'campus' => 'ARASOF-Nasugbu',
        ], $rows);
    }
}
