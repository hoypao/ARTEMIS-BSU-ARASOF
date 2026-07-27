<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Read-only smoke coverage for the routes that gate everything else: if a
 * public page 500s or a role guard stops redirecting, every other feature is
 * unreachable anyway.
 *
 * These run against the normal dev database (see phpunit.xml) and never write
 * to it — no RefreshDatabase, no factories. The account credentials below are
 * the demo accounts created by the schema migration.
 */
class SmokeTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function publicPages(): array
    {
        return [
            'home'           => ['/'],
            'events'         => ['/events'],
            'track'          => ['/track'],
            'admission appeal' => ['/appeal/apply'],
            'ask spartan'    => ['/ask-spartan'],
            'login'          => ['/login'],
            'reset password' => ['/reset-password'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_public_pages_render(string $path): void
    {
        $this->get($path)->assertOk();
    }

    /** @return array<string, array{string}> */
    public static function guardedDashboards(): array
    {
        return [
            'student' => ['/student/dashboard'],
            'trainer' => ['/trainer/dashboard'],
            'pathfit' => ['/pathfit-faculty/dashboard'],
            'dean'    => ['/dean/dashboard'],
            'tao'     => ['/tao/dashboard'],
            'admin'   => ['/admin/dashboard'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('guardedDashboards')]
    public function test_dashboards_reject_anonymous_visitors(string $path): void
    {
        $this->get($path)->assertRedirect('/login');
    }

    /** @return array<string, array{string, string, string}> */
    public static function accounts(): array
    {
        return [
            // email, password, own dashboard
            'admin'   => ['admin@batstate-u.edu.ph', 'admin123', '/admin/dashboard'],
            'student' => ['23-75900@g.batstate-u.edu.ph', 'student123', '/student/dashboard'],
            'trainer' => ['trainer@batstate-u.edu.ph', 'trainer123', '/trainer/dashboard'],
            'pathfit' => ['andrea.bautista@batstate-u.edu.ph', 'pathfit123', '/pathfit-faculty/dashboard'],
            'dean'    => ['corazon.ibarra@batstate-u.edu.ph', 'dean123', '/dean/dashboard'],
            'tao'     => ['tao.central@batstate-u.edu.ph', 'tao123', '/tao/dashboard'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('accounts')]
    public function test_each_role_can_log_in_and_load_its_dashboard(string $email, string $password, string $dashboard): void
    {
        $this->post('/login', ['email' => $email, 'password' => $password])
            ->assertRedirect();

        $this->get($dashboard)->assertOk();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post('/login', [
            'email' => 'admin@batstate-u.edu.ph',
            'password' => 'definitely-not-the-password',
        ])->assertRedirect('/login');

        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_a_role_cannot_reach_another_roles_dashboard(): void
    {
        $this->post('/login', [
            'email' => '23-75900@g.batstate-u.edu.ph',
            'password' => 'student123',
        ]);

        // Wrong role bounces to the user's own dashboard, not to /login.
        $this->get('/admin/dashboard')->assertRedirect('/student/dashboard');
        $this->get('/trainer/dashboard')->assertRedirect('/student/dashboard');
    }

    public function test_track_lookup_requires_both_fields_to_match(): void
    {
        $this->postJson('/track/lookup', [
            'application_code' => 'APP-2026-002',
            'id_number' => 'definitely-not-a-real-id',
        ])->assertNotFound();
    }

    public function test_ask_spartan_answers(): void
    {
        $this->postJson('/chat', ['message' => 'How do I apply for a stipend?'])
            ->assertOk()
            ->assertJsonStructure(['reply', 'time']);
    }
}
