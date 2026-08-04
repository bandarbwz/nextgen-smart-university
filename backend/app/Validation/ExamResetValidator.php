<?php

declare(strict_types=1);

namespace App\Validation;

class ExamResetValidator
{
    public function request(array $data): array
    {
        return (new Validator())
            ->required($data, 'exam_id', 'Examination')
            ->integer($data, 'exam_id', 'Examination')
            ->required($data, 'request_reason', 'Reason')
            ->maxLength($data, 'request_reason', 1000, 'Reason')
            ->errors();
    }

    public function decision(array $data): array
    {
        return (new Validator())
            ->required($data, 'remarks', 'Remarks')
            ->maxLength($data, 'remarks', 500, 'Remarks')
            ->errors();
    }
}
