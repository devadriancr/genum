<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PartNumbersSheetImport implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        PartNumbersImport::$partNumbersData = [];

        foreach ($collection as $row) {
            PartNumbersImport::$partNumbersData[] = $row['part_number'];
        }

    }
}
