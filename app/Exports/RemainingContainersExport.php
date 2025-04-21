<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RemainingContainersExport implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $formattedData = [];

        foreach ($this->data['contenedores_restantes'] as $container) {
            $formattedData[] = [
                'Contenedor' => $container
            ];
        }

        return $formattedData;
    }

    public function headings(): array
    {
        return [
            'Contenedor'
        ];
    }

    public function title(): string
    {
        return 'Contenedores Restantes';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
