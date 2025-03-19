<?php

namespace App\Http\Controllers;

use App\Imports\PartNumbersImport;
use App\Models\YMCOM;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PartNumberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     *
     */
    public function import()
    {
        return view('part-numbers.import');
    }

    /**
     *
     */
    public function upload(Request $request)
    {
        $startTime = Carbon::now();

        $path = $request->file('file_excel');

        Excel::import(new PartNumbersImport, $path);

        $getForecastData = PartNumbersImport::getForecastData();
        $getStockData = PartNumbersImport::getStockData();
        $getContainersData = PartNumbersImport::getContainersData();

        $combinedData = [
            'forecast_data' => $getForecastData,
            'stock_data' => $getStockData,
            'containers_data' => $getContainersData,
        ];

        $endTime = Carbon::now();
        $diff = $startTime->diff($endTime);

        Log::alert("Diferencia: {$diff->h} horas, {$diff->i} minutos, {$diff->s} segundos, " . $diff->f * 1000 . " milisegundos.");

        return response()->json($combinedData);
    }
}
