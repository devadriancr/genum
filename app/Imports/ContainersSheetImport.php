<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ContainersSheetImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        PartNumbersImport::$containersData = [];

        foreach ($collection as $row) {
            PartNumbersImport::$containersData[] = [
                'part_number' => $row['part_number'],
                'stock_quantity' => $row['quantity'],
                'container' => $row['container'],
                'availability_date' => Date::excelToDateTimeObject($row['availability_date'])
                    ->format('Y-m-d'),
            ];
        }
    }
}
