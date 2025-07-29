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
    protected $backtrackDays = 2;
    protected $stockDays;
    protected $startDate;
    protected $endDate;

    public function __construct($backtrackDays = null, $startDate = null, $endDate = null, $stockDays = null)
    {
        $this->backtrackDays = $backtrackDays ?? 2;
        $this->startDate = $startDate ? Carbon::parse($startDate) : null;
        $this->endDate = $endDate ? Carbon::parse($endDate) : null;
        $this->stockDays = $stockDays ?? 2;
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
            // Keep all date columns (we'll filter later)
            $dateColumns[] = ['key' => $key, 'date' => $dateObj, 'hasAny' => $hasAny];
        }

        // Sort date columns chronologically
        usort($dateColumns, fn($a, $b) => $a['date'] <=> $b['date']);

        // Calculate dynamic backtrack days based on start date availability
        $dynamicBacktrackDays = $this->calculateDynamicBacktrackDays($zeroDates);

        // Filter date columns by date range AFTER calculating backtrack days
        $filteredDateColumns = [];
        foreach ($dateColumns as $col) {
            // Filter by date range if specified
            if ($this->startDate && $col['date']->lt($this->startDate)) {
                continue;
            }
            if ($this->endDate && $col['date']->gt($this->endDate)) {
                continue;
            }
            $filteredDateColumns[] = $col;
        }

        // Create a map of all date columns for easy lookup
        $dateColumnsMap = [];
        foreach ($dateColumns as $col) {
            $dateColumnsMap[$col['date']->format('Y-m-d')] = $col;
        }

        // For each date with data, assign adjusted required_date and calculate stock days
        foreach ($filteredDateColumns as $col) {
            if (! $col['hasAny']) {
                continue; // skip zero columns
            }
            $orig = $col['date'];
            $adj = $this->subtractBusinessDaysSkippingZeros($orig, $dynamicBacktrackDays, $zeroDates);

            // Calculate quantities including stock days
            foreach ($collection as $row) {
                $partNumber = $row['part_number'];
                $baseQty = $row[$col['key']] ?? 0;

                if ($baseQty > 0) {
                    // Calculate total quantity including stock days
                    $totalQty = $this->calculateTotalQuantityWithStockDays(
                        $orig,
                        $baseQty,
                        $row,
                        $dateColumnsMap,
                        $zeroDates
                    );

                    PartNumbersImport::$forecastData[] = [
                        'part_number'       => $partNumber,
                        'required_quantity' => $totalQty,
                        'required_date'     => $adj->format('Y-m-d'),
                        'original_date'     => $orig->format('Y-m-d'),
                    ];
                }
            }
        }

        // Log information
        if ($this->startDate || $this->endDate) {
            $rangeInfo = sprintf(
                'Date range filter applied - Start: %s, End: %s, Dynamic backtrack days: %d',
                $this->startDate ? $this->startDate->format('Y-m-d') : 'Not specified',
                $this->endDate ? $this->endDate->format('Y-m-d') : 'Not specified',
                $dynamicBacktrackDays
            );
            Log::info($rangeInfo);
        }

        dd(PartNumbersImport::$forecastData);
    }

    /**
     * Calculate total quantity including stock days (forward-looking)
     */
    private function calculateTotalQuantityWithStockDays(Carbon $originalDate, $baseQuantity, $row, array $dateColumnsMap, array $zeroDates): int
    {
        $totalQuantity = $baseQuantity;
        $currentDate = $originalDate->copy();
        $stockDaysProcessed = 0;

        Log::info("Calculating stock days for date {$originalDate->format('Y-m-d')}, base quantity: {$baseQuantity}, stock days to add: {$this->stockDays}");

        // Add quantities from future dates based on stock days
        while ($stockDaysProcessed < $this->stockDays) {
            $currentDate->addDay();
            $currentDateStr = $currentDate->format('Y-m-d');

            // Skip weekends
            if (!$currentDate->isWeekday()) {
                continue;
            }

            // Skip zero-data business days
            if (in_array($currentDateStr, $zeroDates, true)) {
                continue;
            }

            // Check if this date exists in our columns map
            if (isset($dateColumnsMap[$currentDateStr])) {
                $dateColumn = $dateColumnsMap[$currentDateStr];
                $additionalQty = $row[$dateColumn['key']] ?? 0;
                $totalQuantity += $additionalQty;

                Log::info("Added quantity {$additionalQty} from date {$currentDateStr} (stock day " . ($stockDaysProcessed + 1) . ")");
            } else {
                // Date doesn't exist in Excel, add zero
                Log::info("Date {$currentDateStr} not found in Excel, adding 0 (stock day " . ($stockDaysProcessed + 1) . ")");
            }

            $stockDaysProcessed++;
        }

        Log::info("Final total quantity for {$originalDate->format('Y-m-d')}: {$totalQuantity}");
        return $totalQuantity;
    }

    /**
     * Calculate dynamic backtrack days based on available dates before start date
     */
    private function calculateDynamicBacktrackDays(array $zeroDates): int
    {
        if (!$this->startDate) {
            return $this->backtrackDays; // Default behavior if no start date
        }

        $availableDays = 0;
        $checkDate = $this->startDate->copy();
        $maxDaysToCheck = $this->backtrackDays;

        // Check how many business days are available before the start date
        for ($i = 1; $i <= $maxDaysToCheck; $i++) {
            $checkDate->subDay();

            // Skip weekends
            if (!$checkDate->isWeekday()) {
                $maxDaysToCheck++; // Extend search to account for weekend
                continue;
            }

            // If this date is not in zero dates, it's available
            if (!in_array($checkDate->format('Y-m-d'), $zeroDates, true)) {
                $availableDays++;
            }
        }

        Log::info("Dynamic backtrack calculation: {$availableDays} available days found before start date " . $this->startDate->format('Y-m-d'));

        return $availableDays;
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
