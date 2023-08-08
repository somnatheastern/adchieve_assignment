<?php
declare(strict_types=1);

namespace App\Services;

use Exception;

class CSVGenerator
{
    /**
     * @param array $data
     * @param string $filePath
     * @return array
     */
    public function store(array $data, string $filePath): array
    {
        try {
            $csvResource = fopen($filePath, 'w+');
            foreach ($data as $item) {
                fputcsv($csvResource, $item);
            }
            fclose($csvResource);

            return [
                'error' => false,
                'msg' => 'success',
            ];
        } catch (Exception $exception) {
            return [
                'error' => true,
                'msg' => $exception->getMessage(),
            ];
        }
    }
}
