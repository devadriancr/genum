<?php

namespace App\Imports;

use App\Models\YMCOM;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PartNumbersImport implements WithMultipleSheets
{
    public static $forecastData = [];
    public static $stockData = [];
    public static $containersData = [];
    public static  $partNumbersData = [];

    /**
     *
     */
    public function sheets(): array
    {
        return [
            'Forecast' => new ForecastSheetImport(),
            'Stock' => new StockSheetImport(),
            'Containers' => new ContainersSheetImport(),
            'Numbers' => new PartNumbersSheetImport(),
        ];
    }

    public static function getForecastData()
    {
        $parentPartNumbers = self::$forecastData;

        $allChildren = collect();

        // Recorremos cada número de parte
        foreach ($parentPartNumbers as $key => $parentPartNumber) {
            $children = YMCOM::getChildren(
                $parentPartNumber['part_number'],
                $parentPartNumber['required_quantity'],
                $parentPartNumber['required_date']
            );

            foreach ($children as $child) {
                // Filtramos solo los campos que necesitamos
                $filteredChild = [
                    'part_number' => $child['MCCPRO'],  // Guardamos 'MCCPRO' como 'part_number'
                    'required_quantity' => $child['MCQREQ'],  // Guardamos 'MCQREQ' como 'required_quantity'
                    'required_date' => $child['required_date'],  // Usamos el campo 'required_date' directamente
                ];

                // Solo agregamos el niño si tiene los campos requeridos
                if (isset($filteredChild['part_number']) && isset($filteredChild['required_quantity']) && isset($filteredChild['required_date'])) {
                    $allChildren->push($filteredChild);
                }
            }
        }

        $groupedByPartNumberAndDate = $allChildren->groupBy(function ($item) {
            return $item['part_number'] . '-' . $item['required_date'];
        });


        $finalResult = $groupedByPartNumberAndDate->map(function ($group) {
            $totalQuantity = $group->sum('required_quantity');  // Sumamos la cantidad de los hijos con la misma fecha
            $child = $group->first();
            $child['required_quantity'] = $totalQuantity;
            return $child;
        });

        $result = $finalResult->values();

        return $result;
    }

    /**
     *
     */
    public static function getStockData()
    {
        return self::$stockData;
    }

    /**
     *
     */
    public static function getContainersData()
    {
        return self::$containersData;
    }

    /**
     *
     */
    public static function getPartNumbersData(){
        return self::$partNumbersData;
    }
}
