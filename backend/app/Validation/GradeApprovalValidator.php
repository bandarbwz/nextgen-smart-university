<?php

declare(strict_types=1);

namespace App\Validation;

class GradeApprovalValidator
{
    public function submission(array $data): array
    {
        return (new Validator())
            ->required($data, 'section_id', 'Section')
            ->integer($data, 'section_id', 'Section')
            ->errors();
    }

    /**
     * Rejecting or returning grades without saying why leaves the lecturer with
     * nothing to act on, so remarks are required for both.
     */
    public function decision(array $data): array
    {
        return (new Validator())
            ->required($data, 'remarks', 'Remarks')
            ->maxLength($data, 'remarks', 500, 'Remarks')
            ->errors();
    }
}
