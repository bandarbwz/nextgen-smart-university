<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\SettingsService;
use App\Services\SystemService;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings = new SettingsService(),
        private readonly SystemService $system = new SystemService()
    ) {
        parent::__construct();
    }

    public function mine(): void
    {
        $user = $this->authenticate();

        $settings = $this->run(fn () => $this->settings->mine($user));

        Response::success('Settings retrieved.', ['settings' => $settings]);
    }

    public function updateMine(): void
    {
        $user = $this->authenticate();

        $settings = $this->run(fn () => $this->settings->updateMine($user, Request::body()));

        Response::success('Settings updated.', ['settings' => $settings]);
    }

    public function system(): void
    {
        $this->authenticateAsAdministrator();

        $settings = $this->run(fn () => $this->settings->system());

        Response::success('System settings retrieved.', ['settings' => $settings]);
    }

    public function updateSystem(): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $changes = is_array($data['settings'] ?? null) ? $data['settings'] : [];

        $settings = $this->run(fn () => $this->settings->updateSystem($user, $changes));

        Response::success('System settings updated.', ['settings' => $settings]);
    }

    public function health(): void
    {
        $this->authenticateAsAdministrator();

        $health = $this->run(fn () => $this->system->health());

        Response::success('Health checked.', $health);
    }

    public function logs(): void
    {
        $this->authenticateAsAdministrator();

        $filters = array_filter([
            'severity' => $this->queryString('severity'),
            'module' => $this->queryString('module'),
        ], static fn ($value): bool => $value !== null);

        $logs = $this->run(fn () => $this->system->logs($filters));

        Response::success('System log retrieved.', $logs);
    }

    public function maintenance(): void
    {
        $user = $this->authenticateAsAdministrator();

        $data = Request::body();
        $enabled = $data['enabled'] ?? null;

        if (!is_bool($enabled)) {
            Response::validationError(['enabled' => ['Enabled must be true or false.']]);
        }

        $result = $this->run(
            fn () => $this->system->setMaintenance($user, $enabled, $data['message'] ?? null)
        );

        Response::success('Maintenance mode updated.', $result);
    }

    /**
     * The System feature document lists database and file backup. Nothing in
     * this platform can take one; that is a scheduled operations task using
     * mysqldump, outside the application. Saying so is better than a button
     * that appears to work.
     */
    public function backup(): void
    {
        $this->authenticateAsAdministrator();

        Response::error(
            'Backups are not run from the application. They are an operations task, '
                . 'scheduled with mysqldump outside the platform.',
            501
        );
    }
}
