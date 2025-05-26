<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
                    $dateObj = $this->parseDate($key);

                    if ($dateObj) {
                        $dates[] = [
                            'date' => $dateObj,
                            'key' => $key,
                            'value' => $value
                        ];
                    }
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
                    $adjustedDate = $currentDate->copy();
                    $rewoundDays = 0;

                    while ($rewoundDays < $totalDaysToGoBack) {
                        $adjustedDate->subDay();
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

    /**
     * Convierte cualquier formato de fecha a Carbon
     */
    private function parseDate($dateInput)
    {
        try {
            // Si es un número (fecha serial de Excel)
            if (is_numeric($dateInput)) {
                return Carbon::instance(Date::excelToDateTimeObject($dateInput));
            }

            // Si es un string, intentar parsearlo
            if (is_string($dateInput)) {
                // Configurar Carbon para español
                Carbon::setLocale('es');

                // Intentar varios formatos comunes
                $formats = [
                    'd-M',      // 10-jun
                    'd-m',      // 10-06
                    'd/m',      // 10/06
                    'd-M-Y',    // 10-jun-2024
                    'd/m/Y',    // 10/06/2024
                    'Y-m-d',    // 2024-06-10
                ];

                foreach ($formats as $format) {
                    try {
                        $date = Carbon::createFromFormat($format, $dateInput);

                        // Si no se especifica año, usar el año actual
                        if (!strpos($format, 'Y')) {
                            $date->year(date('Y'));
                        }

                        return $date;
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                // Como último recurso, intentar parse automático
                return Carbon::parse($dateInput);
            }

            return null;
        } catch (\Exception $e) {
            // Log del error si es necesario
            Log::warning("No se pudo parsear la fecha: " . $dateInput . " - Error: " . $e->getMessage());
            return null;
        }
    }
}
