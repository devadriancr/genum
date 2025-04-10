<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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

                    $date = Date::excelToDateTimeObject($key);

                    $daysToGoBack = 2;
                    $rewoundDays = 0;

                    while ($rewoundDays < $daysToGoBack) {
                        $date->modify('-1 day');
                        $weekDay = $date->format('N');

                        if ($weekDay <= 5) {
                            $rewoundDays++;
                        }
                    }

                    $requiredDate = $date->format('Y-m-d');

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
