<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ContainersSheetImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $groupedData = [];

        foreach ($collection as $row) {
            $containerId = $row['container'];

            if (str_starts_with($containerId, 'AD')) {
                continue;
            }

            // Parsear la fecha correctamente (nuevo método)
            $availabilityDate = $this->parseDate($row['availability_date']);
            if ($availabilityDate === null) {
                continue; // Omitir esta fila si la fecha no es válida
            }
            $availabilityDate = $availabilityDate->format('Y-m-d');


            $partNumber = $row['part_number'];
            $quantity = $row['quantity'];

            $compositeKey = $containerId . '|' . $availabilityDate;

            // Inicializamos la estructura si no existe
            if (!isset($groupedData[$compositeKey])) {
                $groupedData[$compositeKey] = [
                    'container_id' => $containerId,
                    'availability_date' => $availabilityDate,
                    'parts' => []
                ];
            }

            $partFound = false;
            foreach ($groupedData[$compositeKey]['parts'] as &$part) {
                if ($part['part_number'] === $partNumber) {
                    $part['quantity'] += $quantity;
                    $partFound = true;
                    break;
                }
            }

            if (!$partFound) {
                $groupedData[$compositeKey]['parts'][] = [
                    'part_number' => $partNumber,
                    'quantity' => $quantity
                ];
            }
        }

        // Convertimos a array numérico
        $result = array_values($groupedData);

        // Ordenamos por fecha (ascendente)
        usort($result, function ($a, $b) {
            return strcmp($a['availability_date'], $b['availability_date']);
        });

        // Convertimos el array asociativo a numérico y lo asignamos a la propiedad estática
        PartNumbersImport::$containersData = $result;
    }

    /**
     * Parsea fechas que pueden venir como número de Excel o como string
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
