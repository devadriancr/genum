<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PartNumbersImport implements ToCollection, WithHeadingRow
{
    public static $data = [];

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        self::$data = [];

        foreach ($collection as $row) {
            self::$data[] = [
                'part_number' => $row['part_number'],
                'required_quantity' => $row['required_quantity'],
                'required_date' => Date::excelToDateTimeObject($row['required_date'])
                    ->modify('-2 days')
                    ->format('Y-m-d')
            ];
        }
    }

    public static function getData()
    {
        return self::$data;
    }
}
