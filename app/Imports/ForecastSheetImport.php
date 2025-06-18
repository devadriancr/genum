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

    protected $stockDays;

    public function __construct($stockDays)
    {
        $this->stockDays = $stockDays;
    }

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        PartNumbersImport::$forecastData = [];

        // Obtener todas las fechas de los encabezados (excluyendo part_number)
        $dateColumns = [];
        $firstRow = $collection->first();

        foreach ($firstRow as $key => $value) {
            if ($key != 'part_number' && $value != '') {
                $dateObj = $this->parseDate($key);
                if ($dateObj) {
                    $dateColumns[] = [
                        'key' => $key,
                        'date' => $dateObj
                    ];
                }
            }
        }

        // Ordenar fechas cronológicamente
        usort($dateColumns, function($a, $b) {
            return $a['date'] <=> $b['date'];
        });

        $daysToMoveBack = 0;

        foreach ($dateColumns as $dateColumn) {
            $dateKey = $dateColumn['key'];
            $originalDate = $dateColumn['date'];

            // Verificar si TODA la columna está en cero
            $hasAnyData = false;
            foreach ($collection as $row) {
                if (isset($row[$dateKey]) && $row[$dateKey] > 0) {
                    $hasAnyData = true;
                    break;
                }
            }

            if (!$hasAnyData) {
                // Si toda la columna está en cero, incrementamos los días para retroceder
                // Solo si es día hábil
                if ($originalDate->format('N') <= 5) {
                    $daysToMoveBack++;
                }
                continue;
            }

            $totalDaysToGoBack = $this->stockDays + $daysToMoveBack;

            $adjustedDate = $this->subtractBusinessDays($originalDate, $totalDaysToGoBack);

            // Procesar todos los part numbers para esta fecha
            foreach ($collection as $row) {
                $partNumber = $row['part_number'];
                $quantity = $row[$dateKey] ?? 0;

                if ($quantity > 0) {
                    PartNumbersImport::$forecastData[] = [
                        'part_number' => $partNumber,
                        'required_quantity' => $quantity,
                        'required_date' => $adjustedDate->format('Y-m-d'),
                        'original_date' => $originalDate->format('Y-m-d'),
                    ];
                }
            }
        }
    }

    /**
     * Resta días hábiles (excluyendo fines de semana)
     */
    private function subtractBusinessDays(Carbon $date, int $days)
    {
        $adjustedDate = $date->copy();
        $businessDaysSubtracted = 0;

        while ($businessDaysSubtracted < $days) {
            $adjustedDate->subDay();

            // Si es día hábil (lunes=1 a viernes=5)
            if ($adjustedDate->format('N') <= 5) {
                $businessDaysSubtracted++;
            }
        }

        return $adjustedDate;
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
                    'd/m/Y',    // 19/06/2025
                    'd-M-Y',    // 19-jun-2025
                    'd-M',      // 19-jun
                    'd-m',      // 19-06
                    'd/m',      // 19/06
                    'Y-m-d',    // 2025-06-19
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
            Log::warning("No se pudo parsear la fecha: " . $dateInput . " - Error: " . $e->getMessage());
            return null;
        }
    }
}
