<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\ApiException;

class FileUpload
{
    public const PROFILE_DOCUMENT = 'document';

    public const PROFILE_COURSE_FILE = 'course_file';

    public const PROFILE_CHAT_FILE = 'chat_file';

    private const PROFILES = [
        self::PROFILE_DOCUMENT => [
            'max_bytes' => 5 * 1024 * 1024,
            'label' => 'PDF, JPG and PNG',
            'types' => [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            ],
        ],
        self::PROFILE_COURSE_FILE => [
            'max_bytes' => 25 * 1024 * 1024,
            'label' => 'PDF, Office documents, images, plain text and ZIP',
            'types' => [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'text/plain' => 'txt',
                'application/zip' => 'zip',
                'application/msword' => 'doc',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.ms-powerpoint' => 'ppt',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            ],
        ],
        self::PROFILE_CHAT_FILE => [
            'max_bytes' => 15 * 1024 * 1024,
            'label' => 'PDF, Office documents, images, ZIP, MP4 and MP3',
            'types' => [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'application/zip' => 'zip',
                'video/mp4' => 'mp4',
                'audio/mpeg' => 'mp3',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            ],
        ],
    ];

    public static function store(array $file, string $directory, string $profile = self::PROFILE_DOCUMENT): string
    {
        self::guardUploadStatus($file);

        $rules = self::PROFILES[$profile];

        if ($file['size'] > $rules['max_bytes']) {
            throw new ApiException(
                'The file must not be larger than ' . (int) ($rules['max_bytes'] / 1048576) . ' MB.',
                422
            );
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if (!isset($rules['types'][$mimeType])) {
            throw new ApiException('Only ' . $rules['label'] . ' files are accepted.', 422);
        }

        $target = dirname(__DIR__, 2) . '/storage/uploads/' . $directory;

        if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
            throw new ApiException('Unable to store the uploaded file.', 500);
        }

        $name = bin2hex(random_bytes(16)) . '.' . $rules['types'][$mimeType];

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
