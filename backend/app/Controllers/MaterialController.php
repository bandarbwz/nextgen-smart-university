<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\MaterialService;
use App\Validation\LmsValidator;

class MaterialController extends Controller
{
    public function __construct(
        private readonly MaterialService $materials = new MaterialService(),
        private readonly LmsValidator $validator = new LmsValidator()
    ) {
        parent::__construct();
    }

    public function index(): void
    {
        $user = $this->authenticate();

        $materials = $this->run(
            fn () => $this->materials->list($user, $this->queryInt('section_id'))
        );

        Response::success('Materials retrieved.', ['materials' => $materials]);
    }

    public function show(string $id): void
    {
        $user = $this->authenticate();

        $material = $this->run(fn () => $this->materials->get((int) $id, $user));

        Response::success('Material retrieved.', ['material' => $material]);
    }

    public function store(): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::formOrBody();
        $errors = $this->validator->material($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $file = $_FILES['file'] ?? null;

        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            Response::validationError(['file' => ['A file is required.']]);
        }

        $material = $this->run(fn () => $this->materials->upload($user, [
            'section_id' => $data['section_id'],
            'title' => trim((string) $data['title']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'visibility' => $data['visibility'] ?? 'visible',
        ], $file));

        Response::success('Material uploaded.', ['material' => $material], 201);
    }

    public function update(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $data = Request::body();
        $errors = $this->validator->materialUpdate($data);

        if ($errors !== []) {
            Response::validationError($errors);
        }

        $material = $this->run(fn () => $this->materials->update((int) $id, $user, [
            'title' => trim((string) $data['title']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'visibility' => $data['visibility'] ?? null,
        ]));

        Response::success('Material updated.', ['material' => $material]);
    }

    public function destroy(string $id): void
    {
        $user = $this->authenticateAs(['Lecturer']);

        $this->run(fn () => $this->materials->delete((int) $id, $user));

        Response::success('Material deleted.');
    }

    public function download(string $id): void
    {
        $user = $this->authenticate();

        $file = $this->run(fn () => $this->materials->pathForDownload((int) $id, $user));

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
        header('Content-Length: ' . filesize($file['path']));
        header('X-Content-Type-Options: nosniff');

        readfile($file['path']);

        exit;
    }
}
