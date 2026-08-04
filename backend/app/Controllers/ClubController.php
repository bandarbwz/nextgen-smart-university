<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\ClubService;
use App\Validation\ActivityValidator;

class ClubController extends Controller
{
    public function __construct(
        private readonly ClubService $clubs = new ClubService(),
        private readonly ActivityValidator $validator = new ActivityValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticate();

        $clubs = $this->run(
            fn () => $this->clubs->list($user, $this->queryString('category'), $this->queryString('status'))
        );

        Response::success('Clubs retrieved.', ['clubs' => $clubs]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $club = $this->run(fn () => $this->clubs->get((int) $id, $user));

        Response::success('Club retrieved.', ['club' => $club]);
    }

    public function store(): void
    {
        $this->authenticateAs(['STAD Staff']);

        $data = Request::body();
        $errors = $this->validator->club($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $club = $this->run(fn () => $this->clubs->create($data));

        Response::success('Club created.', ['club' => $club], 201);
    }

    public function update(string $id): void
    {
        $this->authenticateAs(['STAD Staff']);

        $data = Request::body();
        $errors = $this->validator->club($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $club = $this->run(fn () => $this->clubs->update((int) $id, $data));

        Response::success('Club updated.', ['club' => $club]);
    }

    public function destroy(string $id): void
    {
        $this->authenticateAsAdministrator();

        $this->run(fn () => $this->clubs->delete((int) $id));

        Response::success('Club deleted.');
    }
}
