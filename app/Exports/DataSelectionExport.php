<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DataSelectionExport implements WithMultipleSheets
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new SelectedContainersExport($this->data),
            new FinalStockExport($this->data),
            new RemainingContainersExport($this->data)
        ];
    }
}
