<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Helpers\Config;
use App\Services\ApiException;
use App\Services\SettingsService;
use App\Services\SystemService;
use Tests\TestCase;

class SettingsSystemRulesTest extends TestCase
{
    private SettingsService $settings;

    private SystemService $system;

    private array $adminUser;

    private array $studentUser;

    private array $mailEnv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailEnv = [
            $_ENV['MAIL_HOST'] ?? '',
            $_ENV['MAIL_USERNAME'] ?? '',
            $_ENV['MAIL_PASSWORD'] ?? '',
        ];

        $this->settings = new SettingsService();
        $this->system = new SystemService();

        $adminId = $this->createUser('Administrator', 'admin@test.edu', 'Test Admin');
        $studentId = $this->createUser('Student', 'student@test.edu', 'Test Student');

        $this->adminUser = $this->actingAs($adminId, 'Administrator');
        $this->studentUser = $this->actingAs($studentId, 'Student');

        // Settings are seeded by the schema file, so reset the two this test moves.
        $this->db->exec("UPDATE SystemSetting SET setting_value = 'false' WHERE setting_key = 'maintenance_mode'");
        $this->db->exec("UPDATE SystemSetting SET setting_value = '21' WHERE setting_key = 'max_credit_hours'");
    }

    protected function tearDown(): void
    {
        $this->withMail(...$this->mailEnv);

        parent::tearDown();
    }

    public function testAUserWithNoSettingsRowGetsTheDefaults(): void
    {
        $settings = $this->settings->mine($this->studentUser);

        $this->assertSame('en', $settings['language']);
        $this->assertSame('system', $settings['theme']);
        $this->assertSame('UTC', $settings['timezone']);
    }

    public function testAUserCanChangeTheirOwnSettings(): void
    {
        $updated = $this->settings->updateMine($this->studentUser, [
            'language' => 'ar',
            'theme' => 'dark',
            'timezone' => 'Asia/Riyadh',
        ]);

        $this->assertSame('ar', $updated['language']);
        $this->assertSame('dark', $updated['theme']);

        $stored = $this->settings->mine($this->studentUser);

        $this->assertSame('ar', $stored['language']);
    }

    public function testUserSettingsAreScopedToTheUser(): void
    {
        $this->settings->updateMine($this->studentUser, ['language' => 'ar', 'theme' => 'dark']);

        $this->assertSame('en', $this->settings->mine($this->adminUser)['language']);
    }

    public function testAnUnknownLanguageOrThemeIsRefused(): void
    {
        try {
            $this->settings->updateMine($this->studentUser, ['language' => 'fr', 'theme' => 'dark']);

            $this->fail('An unsupported language must be refused.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->statusCode());
        }
    }

    public function testSystemSettingsAreGroupedByCategory(): void
    {
        $grouped = $this->settings->system();

        $this->assertArrayHasKey('Security', $grouped);
        $this->assertArrayHasKey('Maintenance', $grouped);
    }

    public function testAnIntegerSettingRejectsRubbish(): void
    {
        try {
            $this->settings->updateSystem($this->adminUser, ['max_credit_hours' => 'banana']);

            $this->fail('A non numeric value must be refused for an integer setting.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->statusCode());
        }
    }

    public function testAnIntegerSettingAcceptsANumber(): void
    {
        $this->settings->updateSystem($this->adminUser, ['max_credit_hours' => '18']);

        $value = $this->scalar(
            "SELECT setting_value FROM SystemSetting WHERE setting_key = 'max_credit_hours'"
        );

        $this->assertSame('18', $value);
    }

    public function testABooleanSettingOnlyAcceptsTrueOrFalse(): void
    {
        $this->expectException(ApiException::class);

        $this->settings->updateSystem($this->adminUser, ['maintenance_mode' => 'maybe']);
    }

    public function testAnEmptyValueIsRefused(): void
    {
        $this->expectException(ApiException::class);

        $this->settings->updateSystem($this->adminUser, ['university_name' => '']);
    }

    public function testAnUnknownSettingKeyIsRefused(): void
    {
        try {
            $this->settings->updateSystem($this->adminUser, ['secret_backdoor' => 'on']);

            $this->fail('An unknown setting key must be refused.');
        } catch (ApiException $exception) {
            $this->assertSame(422, $exception->statusCode());
        }
    }

    public function testEverySystemSettingChangeIsLogged(): void
    {
        $this->settings->updateSystem($this->adminUser, ['max_credit_hours' => '18']);

        $entries = $this->system->logs([])['log'];

        $this->assertNotEmpty($entries);
        $this->assertSame('Settings', $entries[0]['module']);
        $this->assertStringContainsString('max_credit_hours', $entries[0]['message']);
    }

    /**
     * Maintenance mode is only a label unless it actually stops people.
     */
    public function testMaintenanceModeBlocksOrdinaryUsers(): void
    {
        $this->assertFalse($this->system->shouldBlock('Student'));

        $this->system->setMaintenance($this->adminUser, true, 'Upgrading the database.');

        $this->assertTrue($this->system->shouldBlock('Student'));
        $this->assertTrue($this->system->shouldBlock('Lecturer'));
        $this->assertTrue($this->system->shouldBlock('Coordinator'));
    }

    /**
     * If maintenance locked out administrators too, nobody could turn it off.
     */
    public function testMaintenanceModeNeverBlocksAnAdministrator(): void
    {
        $this->system->setMaintenance($this->adminUser, true, null);

        $this->assertFalse($this->system->shouldBlock('Administrator'));
    }

    public function testMaintenanceCanBeTurnedBackOff(): void
    {
        $this->system->setMaintenance($this->adminUser, true, null);
        $this->system->setMaintenance($this->adminUser, false, null);

        $this->assertFalse($this->system->shouldBlock('Student'));
    }

    public function testTheMaintenanceMessageIsStoredAndReturned(): void
    {
        $this->system->setMaintenance($this->adminUser, true, 'Back at 14:00.');

        $this->assertSame('Back at 14:00.', $this->system->maintenanceMessage());
    }

    public function testTurningMaintenanceOnAndOffIsLogged(): void
    {
        $this->system->setMaintenance($this->adminUser, true, null);
        $this->system->setMaintenance($this->adminUser, false, null);

        $actions = array_column($this->system->logs([])['log'], 'action');

        $this->assertContains('Maintenance enabled', $actions);
        $this->assertContains('Maintenance disabled', $actions);
    }

    /**
     * The health check has to measure something. A dashboard that reports green
     * because it never looked is worse than no dashboard.
     */
    public function testTheHealthCheckReportsTheRealDatabaseState(): void
    {
        $health = $this->system->health();

        $database = $this->check($health, 'Database');

        $this->assertSame('up', $database['status']);
        $this->assertGreaterThan(50, $database['detail']['tables']);
    }

    public function testTheHealthCheckAdmitsTheAiServiceIsAbsent(): void
    {
        $ai = $this->check($this->system->health(), 'AI Service');

        $this->assertSame('not configured', $ai['status']);
    }

    /**
     * A mail host on its own sends nothing. Reporting "configured" because
     * MAIL_HOST is filled in is how a broken password reset stays hidden.
     */
    public function testTheHealthCheckCallsEmailUnconfiguredWhenTheCredentialsAreEmpty(): void
    {
        $this->withMail('smtp.example.com', 'someone', '');

        $this->assertSame('not configured', $this->check($this->system->health(), 'Email')['status']);

        $this->withMail('smtp.example.com', '', 'a-password');

        $this->assertSame('not configured', $this->check($this->system->health(), 'Email')['status']);

        $this->withMail('smtp.example.com', 'someone', 'a-password');

        $this->assertSame('configured', $this->check($this->system->health(), 'Email')['status']);
    }

    public function testTheHealthCheckReflectsMaintenanceMode(): void
    {
        $this->assertFalse($this->system->health()['maintenance_mode']);

        $this->system->setMaintenance($this->adminUser, true, null);

        $this->assertTrue($this->system->health()['maintenance_mode']);
    }

    public function testTheSystemLogCanBeFilteredBySeverity(): void
    {
        $this->system->setMaintenance($this->adminUser, true, null);

        $warnings = $this->system->logs(['severity' => 'warning'])['log'];

        $this->assertNotEmpty($warnings);

        foreach ($warnings as $entry) {
            $this->assertSame('warning', $entry['severity']);
        }
    }

    private function withMail(string $host, string $username, string $password): void
    {
        $_ENV['MAIL_HOST'] = $host;
        $_ENV['MAIL_USERNAME'] = $username;
        $_ENV['MAIL_PASSWORD'] = $password;

        Config::load(dirname(__DIR__, 2) . '/config/config.php');
    }

    private function check(array $health, string $name): array
    {
        foreach ($health['checks'] as $check) {
            if ($check['name'] === $name) {
                return $check;
            }
        }

        $this->fail('No health check named ' . $name);
    }
}
