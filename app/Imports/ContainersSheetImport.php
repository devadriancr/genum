<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ContainersSheetImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        // Array para agrupar por contenedor y fecha
        $groupedData = [];

        foreach ($collection as $row) {
            $containerId = $row['container'];
            $availabilityDate = Date::excelToDateTimeObject($row['availability_date'])->format('Y-m-d');
            $partNumber = $row['part_number'];
            $quantity = $row['quantity'];

            // Creamos una clave única combinando container_id y fecha
            $compositeKey = $containerId . '|' . $availabilityDate;

            // Inicializamos la estructura si no existe
            if (!isset($groupedData[$compositeKey])) {
                $groupedData[$compositeKey] = [
                    'container_id' => $containerId,
                    'availability_date' => $availabilityDate,
                    'parts' => []
                ];
            }

            // Buscamos si la parte ya existe en este grupo
            $partFound = false;
            foreach ($groupedData[$compositeKey]['parts'] as &$part) {
                if ($part['part_number'] === $partNumber) {
                    $part['quantity'] += $quantity;
                    $partFound = true;
                    break;
                }
            }

            // Si no encontramos la parte, la añadimos
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
        usort($result, function($a, $b) {
            return strcmp($a['availability_date'], $b['availability_date']);
        });

        // Convertimos el array asociativo a numérico y lo asignamos a la propiedad estática
        PartNumbersImport::$containersData = $result;
    }
}
