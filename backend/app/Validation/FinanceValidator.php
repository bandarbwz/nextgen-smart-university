<?php

declare(strict_types=1);

namespace App\Validation;

class FinanceValidator
{
    private const FEE_TYPES = [
        'Tuition', 'Registration', 'Laboratory', 'Library', 'Examination', 'Other',
    ];

    private const PAYMENT_METHODS = [
        'Cash', 'Online Banking', 'Credit Card', 'Debit Card', 'E-Wallet',
    ];

    public function tuitionFee(array $data): array
    {
        return (new Validator())
            ->required($data, 'program_id', 'Program')
            ->integer($data, 'program_id', 'Program')
            ->required($data, 'semester_id', 'Semester')
            ->integer($data, 'semester_id', 'Semester')
            ->required($data, 'fee_type', 'Fee type')
            ->inList($data, 'fee_type', self::FEE_TYPES, 'Fee type')
            ->required($data, 'amount', 'Amount')
            ->numberBetween($data, 'amount', 0.01, 9999999, 'Amount')
            ->errors();
    }

    public function generateInvoice(array $data): array
    {
        return (new Validator())
            ->required($data, 'student_id', 'Student')
            ->integer($data, 'student_id', 'Student')
            ->required($data, 'semester_id', 'Semester')
            ->integer($data, 'semester_id', 'Semester')
            ->required($data, 'due_date', 'Due date')
            ->date($data, 'due_date', 'Due date')
            ->errors();
    }

    public function payment(array $data): array
    {
        return (new Validator())
            ->required($data, 'invoice_id', 'Invoice')
            ->integer($data, 'invoice_id', 'Invoice')
            ->required($data, 'payment_reference', 'Payment reference')
            ->maxLength($data, 'payment_reference', 100, 'Payment reference')
            ->required($data, 'payment_method', 'Payment method')
            ->inList($data, 'payment_method', self::PAYMENT_METHODS, 'Payment method')
            ->required($data, 'amount', 'Amount')
            ->numberBetween($data, 'amount', 0.01, 9999999, 'Amount')
            ->errors();
    }

    public function scholarship(array $data): array
    {
        return (new Validator())
            ->required($data, 'student_id', 'Student')
            ->integer($data, 'student_id', 'Student')
            ->required($data, 'scholarship_name', 'Scholarship name')
            ->maxLength($data, 'scholarship_name', 255, 'Scholarship name')
            ->required($data, 'amount', 'Amount')
            ->numberBetween($data, 'amount', 0.01, 9999999, 'Amount')
            ->required($data, 'start_date', 'Start date')
            ->date($data, 'start_date', 'Start date')
            ->required($data, 'end_date', 'End date')
            ->date($data, 'end_date', 'End date')
            ->errors();
    }

    public function hold(array $data): array
    {
        return (new Validator())
            ->required($data, 'student_id', 'Student')
            ->integer($data, 'student_id', 'Student')
            ->required($data, 'reason', 'Reason')
            ->maxLength($data, 'reason', 255, 'Reason')
            ->errors();
    }
}
