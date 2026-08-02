<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\ApiException;

class FileUpload
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    private const ALLOWED = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    public static function store(array $file, string $directory): string
    {
        self::guardUploadStatus($file);

        if ($file['size'] > self::MAX_BYTES) {
            throw new ApiException('The file must not be larger than 5 MB.', 422);
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if (!isset(self::ALLOWED[$mimeType])) {
            throw new ApiException('Only PDF, JPG and PNG files are accepted.', 422);
        }

        $target = dirname(__DIR__, 2) . '/storage/uploads/' . $directory;

        if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
            throw new ApiException('Unable to store the uploaded file.', 500);
        }

        $name = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$mimeType];

        if (!move_uploaded_file($file['tmp_name'], $target . '/' . $name)) {
            Logger::error('Failed to move uploaded file', ['directory' => $directory]);

            throw new ApiException('Unable to store the uploaded file.', 500);
        }

        return $directory . '/' . $name;
    }

    public static function absolutePath(string $storedPath): string
    {
        return dirname(__DIR__, 2) . '/storage/uploads/' . $storedPath;
    }

    private static function guardUploadStatus(array $file): void
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'The file is larger than the server allows.',
            UPLOAD_ERR_FORM_SIZE => 'The file is larger than the form allows.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server is missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the file to disk.',
            UPLOAD_ERR_EXTENSION => 'The upload was stopped by a server extension.',
        ];

        $status = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($status !== UPLOAD_ERR_OK) {
            throw new ApiException($messages[$status] ?? 'The file could not be uploaded.', 422);
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new ApiException('The file could not be uploaded.', 422);
        }
    }
}
