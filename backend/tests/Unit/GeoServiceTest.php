<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\GeoService;
use PHPUnit\Framework\TestCase;

class GeoServiceTest extends TestCase
{
    private GeoService $geo;

    protected function setUp(): void
    {
        $this->geo = new GeoService();
    }

    public function testDistanceBetweenIdenticalPointsIsZero(): void
    {
        $this->assertSame(0.0, $this->geo->distanceInMetres(3.0738, 101.5183, 3.0738, 101.5183));
    }

    public function testOneDegreeOfLatitudeIsRoughlyOneHundredAndElevenKilometres(): void
    {
        $distance = $this->geo->distanceInMetres(0.0, 0.0, 1.0, 0.0);

        $this->assertEqualsWithDelta(111_195, $distance, 500);
    }

    public function testShortDistanceMatchesTheExpectedMetreValue(): void
    {
        $distance = $this->geo->distanceInMetres(3.0738, 101.5183, 3.07425, 101.5183);

        $this->assertEqualsWithDelta(50, $distance, 2);
    }

    public function testPointInsideRadiusIsAccepted(): void
    {
        $this->assertTrue(
            $this->geo->isWithinRadius(3.07425, 101.5183, 3.0738, 101.5183, 150)
        );
    }

    public function testPointOutsideRadiusIsRejected(): void
    {
        $this->assertFalse(
            $this->geo->isWithinRadius(3.0918, 101.5183, 3.0738, 101.5183, 150)
        );
    }

    public function testDistanceIsSymmetric(): void
    {
        $there = $this->geo->distanceInMetres(3.0738, 101.5183, 3.0918, 101.6);
        $back = $this->geo->distanceInMetres(3.0918, 101.6, 3.0738, 101.5183);

        $this->assertEqualsWithDelta($there, $back, 0.001);
    }

    public function testDistanceWorksAcrossTheEquatorAndPrimeMeridian(): void
    {
        $distance = $this->geo->distanceInMetres(-0.5, -0.5, 0.5, 0.5);

        $this->assertGreaterThan(0, $distance, 'Negative coordinates must not break the calculation.');
        $this->assertEqualsWithDelta(157_000, $distance, 2_000);
    }
}
