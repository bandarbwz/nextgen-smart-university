<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testRequiredRejectsMissingAndWhitespaceOnlyValues(): void
    {
        $errors = (new Validator())
            ->required([], 'name', 'Name')
            ->required(['name' => '   '], 'name', 'Name')
            ->errors();

        $this->assertArrayHasKey('name', $errors);
    }

    public function testRequiredAcceptsZeroAsAValue(): void
    {
        $errors = (new Validator())->required(['marks' => 0], 'marks', 'Marks')->errors();

        $this->assertSame([], $errors, 'Zero is a legitimate value and must not be treated as missing.');
    }

    #[DataProvider('invalidEmails')]
    public function testEmailRejectsMalformedAddresses(string $email): void
    {
        $errors = (new Validator())->email(['email' => $email], 'email', 'Email')->errors();

        $this->assertArrayHasKey('email', $errors);
    }

    public static function invalidEmails(): array
    {
        return [
            'no at sign' => ['student.nextgen.edu'],
            'no domain' => ['student@'],
            'no local part' => ['@nextgen.edu'],
            'contains space' => ['stu dent@nextgen.edu'],
        ];
    }

    public function testEmailAcceptsAValidAddress(): void
    {
        $errors = (new Validator())
            ->email(['email' => 'student@nextgen.edu'], 'email', 'Email')
            ->errors();

        $this->assertSame([], $errors);
    }

    #[DataProvider('weakPasswords')]
    public function testPasswordEnforcesEveryDocumentedRule(string $password, string $missing): void
    {
        $errors = (new Validator())->password(['password' => $password], 'password', 'Password')->errors();

        $this->assertArrayHasKey('password', $errors);
        $this->assertStringContainsString(
            $missing,
            implode(' ', $errors['password']),
            'The message should name the missing requirement.'
        );
    }

    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Ab1!x', 'at least 8 characters'],
            'no uppercase' => ['password1!', 'one uppercase letter'],
            'no lowercase' => ['PASSWORD1!', 'one lowercase letter'],
            'no number' => ['Password!', 'one number'],
            'no special character' => ['Password1', 'one special character'],
        ];
    }

    public function testPasswordAcceptsACompliantValue(): void
    {
        $errors = (new Validator())
            ->password(['password' => 'Password123!'], 'password', 'Password')
            ->errors();

        $this->assertSame([], $errors);
    }

    public function testPositiveIntegerRejectsZeroAndNegatives(): void
    {
        $errors = (new Validator())
            ->positiveInteger(['credits' => 0], 'credits', 'Credits')
            ->positiveInteger(['hours' => -3], 'hours', 'Hours')
            ->errors();

        $this->assertArrayHasKey('credits', $errors);
        $this->assertArrayHasKey('hours', $errors);
    }

    public function testInListRejectsValuesOutsideTheAllowedSet(): void
    {
        $errors = (new Validator())
            ->inList(['status' => 'Maybe'], 'status', ['Present', 'Absent'], 'Status')
            ->errors();

        $this->assertArrayHasKey('status', $errors);
    }

    public function testLatitudeAndLongitudeBoundsAreEnforced(): void
    {
        $errors = (new Validator())
            ->latitude(['lat' => 91], 'lat', 'Latitude')
            ->longitude(['lng' => -181], 'lng', 'Longitude')
            ->errors();

        $this->assertArrayHasKey('lat', $errors);
        $this->assertArrayHasKey('lng', $errors);
    }

    public function testMatchesDetectsMismatchedConfirmation(): void
    {
        $errors = (new Validator())
            ->matches(
                ['password' => 'Password123!', 'confirm' => 'Different1!'],
                'password',
                'confirm',
                'Password'
            )
            ->errors();

        $this->assertArrayHasKey('confirm', $errors);
    }

    public function testValidatorCollectsErrorsFromEveryRuleInTheChain(): void
    {
        $validator = (new Validator())
            ->required([], 'email', 'Email')
            ->required([], 'password', 'Password');

        $this->assertTrue($validator->fails());
        $this->assertCount(2, $validator->errors());
    }
}
