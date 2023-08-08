<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Exception;

class DistanceService
{
    private array $adchieveHqLocationConfig = [];

    public function __construct(protected GeoLocationApiInterface $apiService)
    {
        if (empty(env('ADCHIEVE_HQ_LOCATION_LAT', ''))
            || empty(env('ADCHIEVE_HQ_LOCATION_LAT', ''))
        ) {
            throw new Exception(
                'Please configure ADCHIEVE_HQ_LOCATION_LAT & ADCHIEVE_HQ_LOCATION_LNG in .env'
            );
        }

        $this->adchieveHqLocationConfig = [
            'lat' => env('ADCHIEVE_HQ_LOCATION_LAT', ''),
            'lng' => env('ADCHIEVE_HQ_LOCATION_LNG', ''),
        ];
    }

    /**
     * @param array $addressInfo
     * @return array|float[]|int[]
     */
    public function process(array $addressInfo = [])
    {
        $address = $addressInfo['address'];

        if (empty($address)) {
            return [
                'error' => true,
                'msg' => 'invalid address',
            ];
        }
            if (!Cache::has($address)) {
                $apiResponse = $this->apiService->getLocationInfo($address);

                // It seems the response is not valid, do not process this address
                if ($apiResponse['error'] ?? false) {
                    return [
                        'error' => true,
                        'msg' => $apiResponse['msg'],
                    ];
                }

                Cache::put($address,$apiResponse);
                // Intentionally halt next API call if any to prevent DDOS blocking
                sleep(2);
            }

            $apiResponse = Cache::get($address);

            $distance = $this->calculateDistance($apiResponse);

            return array_merge(['error' => false], $addressInfo, ['distance' => $distance]);
    }

    /**
     * @return array
     */
    public function getAddresses(): array
    {
        $jsonFile = file_get_contents(storage_path('app/protected/addresses.json'));

        return json_decode($jsonFile, true);
    }

    /**
     * Calculates distance from two geo coordinates
     * @param array $data
     * @return float
     */
    protected function calculateDistance(array $data): float
    {
        $latAddress = $data['latitude'] ?? '';
        $lonAddress = $data['longitude'] ?? '';

        if ('' === $latAddress || '' === $lonAddress) {
            return 0;
        }

        $lonHQ = (float)$this->adchieveHqLocationConfig['lng'];
        $latHQ = (float)$this->adchieveHqLocationConfig['lat'];

        $theta = $lonHQ - $lonAddress;

        // Formula to calculate distance from one point to other point
        // cos c = cos a cos b + sin a sin b cos C
        $dist = (sin(deg2rad($latHQ)) * sin(deg2rad($latAddress))) +
            (cos(deg2rad($latHQ)) * cos(deg2rad($latAddress)) * cos(deg2rad($theta)));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return  $miles * 1.609344; // Converted miles with kilometers
    }
}
