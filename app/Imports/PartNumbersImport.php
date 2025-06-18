<?php

namespace App\Imports;

use App\Models\YMCOM;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PartNumbersImport implements WithMultipleSheets
{
    protected $stockDays;
    public static $forecastData = [];
    public static $stockData = [];
    public static $containersData = [];

    public function __construct($stockDays)
    {
        $this->stockDays = $stockDays;
    }

    /**
     *
     */
    public function sheets(): array
    {
        return [
            'Forecast' => new ForecastSheetImport($this->stockDays),
            'Stock' => new StockSheetImport(),
            'Containers' => new ContainersSheetImport(),
        ];
    }

    public static function getForecastData()
    {
        $parentPartNumbers = self::$forecastData;

        $allChildren = collect();

        foreach ($parentPartNumbers as $key => $parentPartNumber) {

            $children = YMCOM::getChildren(
                $parentPartNumber['part_number'],
                $parentPartNumber['required_quantity'],
                $parentPartNumber['required_date']
            );

            foreach ($children as $child) {
                $filteredChild = [
                    'part_number' => $child['MCCPRO'],
                    'required_quantity' => $child['MCQREQ'],
                    'required_date' => $child['required_date'],
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
            $totalQuantity = $group->sum('required_quantity');
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
}
