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

        // Detect zero-only business dates
        $zeroDates = [];
        $firstRow = $collection->first();
        $dateColumns = [];

        foreach ($firstRow as $key => $value) {
            if ($key === 'part_number' || $value === '') {
                continue;
            }
            $dateObj = $this->parseDate($key);
            if (! $dateObj) {
                continue;
            }
            // Check if entire column is zero
            $hasAny = false;
            foreach ($collection as $row) {
                if (($row[$key] ?? 0) > 0) {
                    $hasAny = true;
                    break;
                }
            }
            // If zero-column on a business day, record
            if (! $hasAny && $dateObj->isWeekday()) {
                $zeroDates[] = $dateObj->format('Y-m-d');
            }
            // Keep all date columns
            $dateColumns[] = ['key' => $key, 'date' => $dateObj, 'hasAny' => $hasAny];
        }

        // Sort date columns chronologically
        usort($dateColumns, fn($a, $b) => $a['date'] <=> $b['date']);

        // For each date with data, assign adjusted required_date
        foreach ($dateColumns as $col) {
            if (! $col['hasAny']) {
                continue; // skip zero columns
            }
            $orig  = $col['date'];
            $adj   = $this->subtractBusinessDaysSkippingZeros($orig, $this->stockDays, $zeroDates);
            // Collect entries
            foreach ($collection as $row) {
                $qty = $row[$col['key']] ?? 0;
                if ($qty > 0) {
                    PartNumbersImport::$forecastData[] = [
                        'part_number'       => $row['part_number'],
                        'required_quantity' => $qty,
                        'required_date'     => $adj->format('Y-m-d'),
                        'original_date'     => $orig->format('Y-m-d'),
                    ];
                }
            }
        }
    }

    /**
     * Subtracts a given number of business days from a date,
     * skipping weekends and any dates listed in zeroDates.
     */
    private function subtractBusinessDaysSkippingZeros(Carbon $date, int $days, array $zeroDates)
    {
        $d = $date->copy();
        $moved = 0;

        while ($moved < $days) {
            $d->subDay();
            // Skip weekends
            if (! $d->isWeekday()) {
                continue;
            }
            // Skip zero-data business days
            if (in_array($d->format('Y-m-d'), $zeroDates, true)) {
                continue;
            }
            $moved++;
        }

        return $d;
    }

    /**
     * Convierte cualquier formato de fecha a Carbon
     */
    private function parseDate($dateInput)
    {
        try {
            if (is_numeric($dateInput)) {
                return Carbon::instance(Date::excelToDateTimeObject($dateInput));
            }

            if (is_string($dateInput)) {
                Carbon::setLocale('es');
                $formats = [
                    'd/m/Y',
                    'd-M-Y',
                    'd-M',
                    'd-m',
                    'd/m',
                    'Y-m-d'
                ];
                foreach ($formats as $f) {
                    try {
                        $dt = Carbon::createFromFormat($f, $dateInput);
                        if (! str_contains($f, 'Y')) {
                            $dt->year(Carbon::now()->year);
                        }
                        return $dt;
                    } catch (\Exception $e) {
                        // continue;
                    }
                }
                return Carbon::parse($dateInput);
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("No se pudo parsear la fecha: {$dateInput}, Error: {$e->getMessage()}");
            return null;
        }
    }
}
