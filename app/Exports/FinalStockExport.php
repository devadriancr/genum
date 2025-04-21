<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinalStockExport implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $formattedData = [];

        foreach ($this->data['stock_final'] as $code => $quantity) {
            $formattedData[] = [
                'No. Parte' => $code,
                'Cantidad' => $quantity
            ];
        }

        return $formattedData;
    }

    public function headings(): array
    {
        return [
            'No. Parte',
            'Cantidad'
        ];
    }

    public function title(): string
    {
        return 'Stock Final';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
