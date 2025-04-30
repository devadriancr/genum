<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ForecastSheetImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        PartNumbersImport::$forecastData = [];

        foreach ($collection as $row) {
            $partNumber = $row['part_number'];

            // Obtener y ordenar todas las fechas
            $dates = [];
            foreach ($row as $key => $value) {
                if ($key != 'part_number') {
                    $dates[] = [
                        'date' => Date::excelToDateTimeObject($key),
                        'key' => $key,
                        'value' => $value
                    ];
                }
            }

            usort($dates, function($a, $b) {
                return $a['date'] <=> $b['date'];
            });

            // Variables para controlar el retroceso
            $baseDaysToGoBack = 3; // 1 día hábil + 2 de fin de semana
            $additionalDays = 0;

            foreach ($dates as $item) {
                $currentDate = $item['date'];
                $weekDay = $currentDate->format('N');

                if ($item['value']) {
                    // Calcular retroceso total
                    $totalDaysToGoBack = $baseDaysToGoBack + $additionalDays;

                    // Aplicar retroceso
                    $adjustedDate = clone $currentDate;
                    $rewoundDays = 0;

                    while ($rewoundDays < $totalDaysToGoBack) {
                        $adjustedDate->modify('-1 day');
                        $adjustedWeekDay = $adjustedDate->format('N');

                        if ($adjustedWeekDay <= 5) { // Solo días hábiles
                            $rewoundDays++;
                        }
                    }

                    // Guardar resultado
                    PartNumbersImport::$forecastData[] = [
                        'part_number' => $partNumber,
                        'required_quantity' => $item['value'],
                        'required_date' => $adjustedDate->format('Y-m-d'),
                        'original_date' => $currentDate->format('Y-m-d'),
                    ];

                    // Reiniciar días adicionales después de usarlos
                    $additionalDays = 0;
                } else {
                    // Si es cero y día hábil, sumar 1 día adicional
                    if ($weekDay <= 5) {
                        $additionalDays++;
                    }
                }
            }
        }
    }
}
