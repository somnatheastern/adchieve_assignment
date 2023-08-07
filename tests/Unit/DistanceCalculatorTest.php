<?php
declare(strict_types=1);

namespace Tests\Unit;
use App\Services\DistanceService;
use App\Services\PositionstackApiService;
use Tests\TestCase;
use ReflectionClass;

class DistanceCalculatorTest extends TestCase
{
    /**
     * Test whether position stack API is working
     */
    public function test_check_position_stack_api_has_result()
    {

        $apiService = new PositionstackApiService();

        $address = $this->getTestAddress();
        $response = $apiService->getLocationInfo($address);

        $this->assertArrayHasKey('latitude', $response);
        $this->assertArrayHasKey('longitude', $response);
    }

    public function test_check_distance_calculation_for_two_addresses()
    {
        $addressLatLang = $this->getTestLatLong();
        $discountCalculatorService = new DistanceService(new PositionstackApiService());
        $reflection = new ReflectionClass(get_class($discountCalculatorService));
        $method = $reflection->getMethod('calculateDistance');
        $method->setAccessible(true);

        $calculatedDistance = $method->invokeArgs($discountCalculatorService, [$addressLatLang]);

        $this->assertIsFloat($calculatedDistance);
        $this->assertTrue(round($calculatedDistance) < 100);
    }

    private function getTestAddress():string
    {
        return 'Weena 505, 3013AL Rotterdam, The Netherlands';
    }

    private function getTestLatLong(): array
    {
        return [
            'latitude' => 51.92366,
            'longitude' => 4.471626,
        ];
    }
}
