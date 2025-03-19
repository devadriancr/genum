<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockSheetImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        PartNumbersImport::$stockData = [];

        foreach ($collection as $row) {
            PartNumbersImport::$stockData[] = [
                'part_number' => $row['part_number'],
                'quantity' => $row['quantity']
            ];
        }
    }
}
