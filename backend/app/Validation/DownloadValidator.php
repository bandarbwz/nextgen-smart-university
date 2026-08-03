<?php

declare(strict_types=1);

namespace App\Validation;

class DownloadValidator
{
    private const CATEGORIES = [
        'Academic Documents', 'Course Materials', 'Student Forms', 'Lecturer Forms',
        'Examination Documents', 'Reports', 'Certificates', 'Policies', 'Templates',
        'Financial Documents',
    ];

    private const VISIBILITIES = ['all', 'students', 'staff', 'administrators'];

    public function file(array $data): array
    {
        return (new Validator())
            ->required($data, 'title', 'Title')
            ->maxLength($data, 'title', 255, 'Title')
            ->required($data, 'category', 'Category')
            ->inList($data, 'category', self::CATEGORIES, 'Category')
            ->inList($data, 'visibility', self::VISIBILITIES, 'Visibility')
            ->errors();
    }
}
