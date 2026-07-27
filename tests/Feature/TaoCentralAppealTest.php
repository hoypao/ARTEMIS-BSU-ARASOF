<?php

namespace Tests\Feature;

use Database\Seeders\AppealPipelineSampleSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The TAO Central carve-out in AdminOpsController::admissionAppeals()
 * (Art. IV Sec. 11-C): OCA screens an appeal through the first two stages, TAO
 * Central owns the last two, and nobody reopens a decided appeal.
 *
 * Like SmokeTest, these run against the dev database and must not write to it —
 * so every case here asserts a *rejection*, which returns before any UPDATE.
 * The happy path (a TAO reviewer advancing 902 -> 903) would mutate shared
 * fixtures, so it is left to manual testing rather than silently reshaping the
 * seeded pipeline for the next run.
 *
 * Fixtures come from AppealPipelineSampleSeeder; run it first:
 *     php artisan db:seed --class=AppealPipelineSampleSeeder
 */
class TaoCentralAppealTest extends TestCase
{
    private const TAO_EMAIL = 'tao.central@batstate-u.edu.ph';
    private const TAO_PASSWORD = 'tao123';

    /** Owned solely by the round-trip test; sits above the seeder's 900-903. */
    private const SCRATCH_ID = 990;

    protected function tearDown(): void
    {
        DB::table('admission_appeals')->where('appeal_id', self::SCRATCH_ID)->delete();
        parent::tearDown();
    }

    private function seedScratchAppeal(): void
    {
        DB::table('admission_appeals')->where('appeal_id', self::SCRATCH_ID)->delete();
        DB::table('admission_appeals')->insert([
            'appeal_id' => self::SCRATCH_ID,
            'full_name' => 'Round Trip Fixture',
            'email' => 'round.trip.fixture@example.invalid',
            'secondary_school' => 'Test Senior High School',
            'campus' => 'ARASOF-Nasugbu',
            'discipline' => 'Music',
            'achievements_summary' => 'Fixture row for the evaluate-then-decide round trip.',
            'status' => 'Evaluation Stage',
            'remarks' => 'OCA screening complete.',
            'submitted_at' => '2026-07-01 09:00:00',
        ]);
    }

    private function loginAsTao(): void
    {
        $this->post('/login', ['email' => self::TAO_EMAIL, 'password' => self::TAO_PASSWORD])
            ->assertRedirect();
    }

    public function test_tao_central_lands_on_its_own_dashboard(): void
    {
        $this->loginAsTao();

        $this->get('/tao/dashboard')->assertOk();
    }

    public function test_tao_central_dashboard_shows_only_its_own_stages(): void
    {
        $this->loginAsTao();

        $html = $this->get('/tao/dashboard')->assertOk()->getContent();

        // The two appeals TAO Central owns are present...
        $this->assertStringContainsString('Patricia Mae Rivera', $html);
        $this->assertStringContainsString('Joshua Emmanuel Bautista', $html);
        // ...and the two still with OCA are filtered out of its payload.
        $this->assertStringNotContainsString('Kristine Joy Alcantara', $html);
        $this->assertStringNotContainsString('Miguel Antonio Ferrer', $html);
    }

    public function test_tao_central_cannot_reach_the_admin_dashboard(): void
    {
        $this->loginAsTao();

        // Wrong role bounces to this user's own dashboard, not to /login.
        $this->get('/admin/dashboard')->assertRedirect('/tao/dashboard');
    }

    public function test_tao_dashboard_carries_no_other_offices_data(): void
    {
        $this->loginAsTao();

        $html = $this->get('/tao/dashboard')->assertOk()->getContent();

        // The admin page ships every module's dataset in one payload; this page
        // must not, or separating the dashboards would be cosmetic only.
        foreach (['EQUIPMENT_ITEMS', 'PROCUREMENT', 'APPLICATIONS', 'PROBATION_STUDENTS', 'QEO'] as $dataset) {
            $this->assertStringNotContainsString($dataset, $html);
        }
    }

    public function test_dashboard_splits_into_overview_and_queue(): void
    {
        $this->loginAsTao();

        $html = $this->get('/tao/dashboard')->assertOk()->getContent();

        // Two nav items, two sections, and the Decided sub-view.
        $this->assertStringContainsString('data-section="dashboard"', $html);
        $this->assertStringContainsString('data-section="appeals"', $html);
        $this->assertStringContainsString('data-subtab="queue"', $html);
        $this->assertStringContainsString('data-subtab="decided"', $html);

        // Overview carries the three stat cards and a feed, no action buttons.
        $this->assertStringContainsString('In Evaluation Stage', $html);
        $this->assertStringContainsString("Awaiting President's Approval", $html);
        $this->assertStringContainsString('Decided This Year', $html);
        $this->assertStringContainsString('Recent Activity', $html);
    }

    public function test_queue_has_a_search_and_filter_bar(): void
    {
        $this->loginAsTao();

        $html = $this->get('/tao/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="appealSearch"', $html);
        $this->assertStringContainsString('id="disciplineFilter"', $html);
        $this->assertStringContainsString('id="stageFilter"', $html);
    }

    public function test_filter_bar_sits_above_the_queue_decided_tabs(): void
    {
        $this->loginAsTao();

        $html = $this->get('/tao/dashboard')->assertOk()->getContent();

        $filterBar = strpos($html, 'id="queueFilterBar"');
        $tabs = strpos($html, 'data-subtab="queue"');
        $list = strpos($html, 'id="appealsList"');

        $this->assertNotFalse($filterBar);
        $this->assertNotFalse($tabs);
        $this->assertLessThan($tabs, $filterBar, 'Search/filter row must render above the Queue/Decided toggle.');
        $this->assertLessThan($list, $tabs, 'The toggle must still sit between the filters and the cards.');
    }

    public function test_queue_count_lives_in_the_topbar_bell_not_the_sidebar(): void
    {
        $this->loginAsTao();

        $html = $this->get('/tao/dashboard')->assertOk()->getContent();

        // Bell + badge in the top bar, matching the OCA admin shell.
        $this->assertStringContainsString('id="notifBtn"', $html);
        $this->assertStringContainsString('id="notifBadge"', $html);
        $this->assertStringContainsString('id="notifDropdown"', $html);
        // ...and no count clinging to the sidebar item any more.
        $this->assertStringNotContainsString('navQueueCount', $html);
    }

    public function test_card_offers_attachments_oca_remarks_and_its_own_note_field(): void
    {
        $this->loginAsTao();

        $html = $this->get('/tao/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Certificates / Awards', $html);
        $this->assertStringContainsString('Recommendation Letter', $html);
        $this->assertStringContainsString('School Statement', $html);
        $this->assertStringContainsString('OCA screening remarks (read-only)', $html);
        $this->assertStringContainsString('Your evaluation remarks', $html);
        // Stage-appropriate actions.
        $this->assertStringContainsString('Evaluate &amp; Forward', $html);
        // The audit note is its own required field, not part of generic remarks.
        $this->assertStringContainsString('id="decisionNote"', $html);
        $this->assertStringContainsString('Decision on behalf of the University President', $html);
    }

    public function test_decision_at_for_approval_requires_the_presidential_note(): void
    {
        $this->loginAsTao();

        // 903 sits at For Approval; approving without the audit note is refused
        // before any write happens.
        $this->postJson('/tao/admission-appeals', [
            'appeal_id' => AppealPipelineSampleSeeder::ID_FOR_APPROVAL,
            'action' => 'approve',
            'remarks' => 'Looks good to me.',
        ])->assertStatus(422)
          ->assertJsonPath('error', 'A decision note on behalf of the University President is required to record this ruling.');

        $this->assertDatabaseHas('admission_appeals', [
            'appeal_id' => AppealPipelineSampleSeeder::ID_FOR_APPROVAL,
            'status' => 'For Approval (President via TAO)',
        ]);
    }

    public function test_reject_still_requires_a_reason(): void
    {
        $this->loginAsTao();

        $this->postJson('/tao/admission-appeals', [
            'appeal_id' => AppealPipelineSampleSeeder::ID_FOR_APPROVAL,
            'action' => 'reject',
        ])->assertStatus(422);
    }

    public function test_appeal_card_uses_the_shared_progress_tracker(): void
    {
        $this->loginAsTao();

        $html = $this->get('/tao/dashboard')->assertOk()->getContent();

        // The pre-rendered admin_progress_tracker_html() map, plus the stage
        // label only the 'appeal_admission' override produces.
        $this->assertStringContainsString('APPEAL_TRACKERS', $html);
        $this->assertStringContainsString('For Approval (President via TAO)', $html);
        $this->assertStringContainsString('Evaluation Stage', $html);
    }

    public function test_admin_dashboard_is_unchanged_by_the_new_role(): void
    {
        $this->post('/login', ['email' => 'admin@batstate-u.edu.ph', 'password' => 'admin123']);

        $html = $this->get('/admin/dashboard')->assertOk()->getContent();

        // Every appeal, at every stage — the admin view is not stage-filtered.
        $this->assertStringContainsString('Kristine Joy Alcantara', $html);
        $this->assertStringContainsString('Joshua Emmanuel Bautista', $html);
        // ...and every tab is still there.
        $this->assertStringContainsString('data-section="equipment"', $html);
        $this->assertStringContainsString('data-section="appeals"', $html);
    }

    public function test_tao_dashboard_rejects_anonymous_visitors(): void
    {
        $this->get('/tao/dashboard')->assertRedirect('/login');
    }

    /** @return array<string, array{int}> */
    public static function ocaOwnedAppeals(): array
    {
        return [
            'submitted'    => [AppealPipelineSampleSeeder::ID_SUBMITTED],
            'under review' => [AppealPipelineSampleSeeder::ID_UNDER_REVIEW],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ocaOwnedAppeals')]
    public function test_tao_central_cannot_act_before_an_appeal_reaches_it(int $appealId): void
    {
        $this->loginAsTao();

        $this->postJson('/tao/admission-appeals', ['appeal_id' => $appealId, 'action' => 'advance'])
            ->assertForbidden();
    }

    public function test_tao_central_cannot_reopen_a_decided_appeal(): void
    {
        $this->loginAsTao();

        // Appeal 1 is Approved in the dev database.
        $this->postJson('/tao/admission-appeals', ['appeal_id' => 1, 'action' => 'advance'])
            ->assertStatus(409);
    }

    public function test_tao_central_cannot_reach_admin_endpoints(): void
    {
        $this->loginAsTao();

        // Outside the role:tao_central group — the route guard bounces these
        // back to its own dashboard before the controller is ever reached.
        $this->post('/admin/equipment', ['action' => 'save', 'item_name' => 'x'])
            ->assertRedirect('/tao/dashboard');
        $this->post('/admin/admission-appeals', ['appeal_id' => 1, 'action' => 'advance'])
            ->assertRedirect('/tao/dashboard');
    }

    /**
     * The one test here that writes. It builds and tears down its own appeal
     * rather than moving a shared fixture, and phpunit.xml blanks MAIL_USERNAME
     * so notifyAppealStatus() short-circuits instead of mailing the address
     * below.
     */
    public function test_full_evaluate_then_decide_round_trip(): void
    {
        $id = self::SCRATCH_ID;
        $this->seedScratchAppeal();
        $this->loginAsTao();

        // 1. Evaluate & Forward, carrying the reviewer's own note.
        $this->postJson('/tao/admission-appeals', [
            'appeal_id' => $id,
            'action' => 'advance',
            'tao_remarks' => 'Portfolio verified against the issuing bodies; recommend approval.',
        ])->assertOk()->assertJsonPath('status', 'For Approval (President via TAO)');

        $row = DB::table('admission_appeals')->where('appeal_id', $id)->first();
        $this->assertSame('For Approval (President via TAO)', $row->status);
        $this->assertSame('Portfolio verified against the issuing bodies; recommend approval.', $row->tao_remarks);
        // OCA's screening note survives the reviewer writing their own.
        $this->assertSame('OCA screening complete.', $row->remarks);

        // 2. Record the ruling with the required presidential note.
        $this->postJson('/tao/admission-appeals', [
            'appeal_id' => $id,
            'action' => 'approve',
            'decision_note' => 'Approved by the University President per endorsement dated 27 July 2026.',
        ])->assertOk()->assertJsonPath('status', 'Approved');

        $row = DB::table('admission_appeals')->where('appeal_id', $id)->first();
        $this->assertSame('Approved', $row->status);
        $this->assertSame(
            'Approved by the University President per endorsement dated 27 July 2026.',
            $row->presidential_decision_note
        );
        $this->assertNotNull($row->decided_at);
        // The reviewer's evaluation note is not overwritten by the decision.
        $this->assertSame('Portfolio verified against the issuing bodies; recommend approval.', $row->tao_remarks);

        // 3. Decided means closed — no reopening.
        $this->postJson('/tao/admission-appeals', ['appeal_id' => $id, 'action' => 'advance'])
            ->assertStatus(409);
    }

    public function test_other_roles_still_cannot_touch_appeals(): void
    {
        $this->post('/login', ['email' => 'corazon.ibarra@batstate-u.edu.ph', 'password' => 'dean123']);

        $this->postJson('/tao/admission-appeals', [
            'appeal_id' => AppealPipelineSampleSeeder::ID_EVALUATION,
            'action' => 'advance',
        ])->assertRedirect('/dean/dashboard');

        $this->get('/tao/dashboard')->assertRedirect('/dean/dashboard');
    }
}
