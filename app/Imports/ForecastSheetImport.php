<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ForecastSheetImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        PartNumbersImport::$forecastData = [];

        foreach ($collection as $row) {
            $partNumber = $row['part_number'];

            foreach ($row as $key => $value) {
                if ($key != 'part_number' && $value) {
                    $requiredDate = Date::excelToDateTimeObject($key)->modify('-2 days')->format('Y-m-d');

                    PartNumbersImport::$forecastData[] = [
                        'part_number' => $partNumber,
                        'required_quantity' => $value,
                        'required_date' => $requiredDate,
                    ];
                }
            }
        }
    }
}
