<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SelectedContainersExport implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $formattedData = [];

        // Primero, encontramos el máximo número de contenedores en cualquier fecha
        $maxContainers = max(array_map(function ($item) {
            return count($item['contenedores_seleccionados']);
        }, $this->data['resultados']));

        // Construimos las filas
        for ($i = 0; $i < $maxContainers; $i++) {
            $row = [];
            foreach ($this->data['resultados'] as $resultado) {
                $row[] = $resultado['contenedores_seleccionados'][$i] ?? '';
            }
            $formattedData[] = $row;
        }

        return $formattedData;
    }

    public function headings(): array
    {
        return array_map(function ($item) {
            return $item['fecha'];
        }, $this->data['resultados']);
    }

    public function title(): string
    {
        return 'Contenedores Seleccionados';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
