<?php
declare(strict_types=1);

namespace App\Services;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PositionstackApiService Implements GeoLocationApiInterface
{
    private string $apiKey = '';
    private string $baseUrl = '';

    public function __construct()
    {
        $this->apiKey = env('POSITION_STACK_API_KEY', '');
        $this->baseUrl = env('POSITION_STACK_API_BASE_URL', '');

        if (!$this->validateApiConfig()) {
            throw new Exception(
                'POSITION_STACK_API_KEY and POSITION_STACK_API_BASE_URL not set in .env file! Halting!!'
            );
        }
    }

    /**
     * @param string $address
     * @return array
     */
    public function getLocationInfo(string $address): array
    {
        if (!$this->validateApiConfig()) {

            return [
                'error' => true,
                'msg' => 'Error: POSITION_STACK_API_KEY and POSITION_STACK_API_BASE_URL not set in .env file! Halting!!',
            ];
        }

        $url = sprintf('%s/forward?access_key=%s&query=%s&output=json',
            $this->baseUrl, $this->apiKey, $address);

        try{
            $response = Http::get($url);

            if (!$response->successful()) {

                return [
                    'error' => true,
                    'msg' => sprintf('API response failed for %s', $address),
                ];
            }

            return $this->formatResult($response->json());
        } catch (Exception $exception) {
            Log::error(sprintf('Positionstack service error calling API for address: %s. Error: %s',
                $address,
                $exception->getMessage()
            ));

            return [
                'error' => true,
                'msg' => sprintf('Positionstack service error calling API for address: %s. Error: %s',
                    $address,
                    $exception->getMessage()
                ),
            ];
        }
    }

    /**
     * @param array $response
     * @return array
     */
    private function formatResult(array $response):array
    {
        return !empty($response['data']) ? current($response['data']) : [
            'error' => true,
            'msg' => 'No data for address',
            ];
    }

    /**
     * @return bool
     */
    private function validateApiConfig(): bool
    {
        return !empty($this->apiKey) && !empty($this->baseUrl);
    }
}
