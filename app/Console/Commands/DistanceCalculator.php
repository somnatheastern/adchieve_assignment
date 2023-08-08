<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CSVGenerator;
use App\Services\DistanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

class DistanceCalculator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adchieve:distance-calculator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to calculate distance from Adchieve HQ for given addresses';

    protected ?CSVGenerator $csvGeneratorService = null;

    /**
     * Execute the console command.
     * @param DistanceService $distanceService
     * @param CSVGenerator $csvGenerator
     */
    public function handle(DistanceService $distanceService, CSVGenerator $csvGenerator)
    {
        try {
            $this->csvGeneratorService = $csvGenerator;

            $addresses = $distanceService->getAddresses();
            $this->printMessage(sprintf('Started processing %d addresses', count($addresses)));

            $results = [];

            foreach ($addresses as $addressInfo) {
                $result = $distanceService->process($addressInfo);

                if ($result['error']) {
                    $this->printMessage(
                        sprintf('Error processing address %s, error: %s', $addressInfo['address'], $result['msg']),
                        'error'
                    );

                    // This address has some issues, proceed with further address
                    continue;
                }

                $results[] = $result;
            }

            $results = $this->sortAddresses($results);
            $results = $this->formatResults($results);

            $this->printMessage(sprintf('Done processing %d addresses', count($results)));
            $this->printMessage(sprintf('================================='));
            $this->printResponse($results);
            $this->printMessage(sprintf('================================='));

            $this->store($results);
        }  catch (Exception $exception) {
            $this->printMessage($exception->getMessage(), 'error');
        }
    }

    /**
     * @param array $results
     * @return array
     */
    protected function formatResults(array $results): array
    {
        $formatted = [];
        array_walk($results, function($address, $index) use(&$formatted) {
            $formatted[] = [
                $index+1,
                sprintf('%.2f km', $address['distance'] ?? 0),
                $address['name'] ?? '',
                $address['address'] ?? '',
            ];
        });

        return $formatted;
    }

    /**
     * @param array $results
     */
    protected function printResponse(array $results)
    {
        $this->table(['Sortnumber', 'Distance', 'Name', 'Address'], $results);
    }

    /**
     * @param array $results
     */
    protected function store(array $results)
    {
        $csvHeaders = [['Sortnumber', 'Distance', 'Name', 'Address']];
        $data = array_merge($csvHeaders, $results);
        $filePath = storage_path('app/protected/distances.csv');

        $result = $this->csvGeneratorService->store($data, $filePath);

        if ($result['error']) {
            $this->printMessage(sprintf('Error storing CSV: %s', $result['msg']), 'error');
            return;
        }

        $this->printMessage(sprintf('CSV generated successfully on %s', $filePath));
    }

    /**
     * @param array $results
     * @return array
     */
    protected function sortAddresses(array $results): array
    {
        // Sort the results by distance
        usort($results, fn ($item1, $item2) => $item1['distance'] <=> $item2['distance']);

        return $results;
    }

    /**
     * @param string $msg
     * @param string $type
     */
    protected function printMessage(string $msg = '', string $type = 'info')
    {
        Log::info($msg);
        $this->$type($msg);
    }
}
