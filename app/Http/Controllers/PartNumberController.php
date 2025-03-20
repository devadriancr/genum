<?php

namespace App\Http\Controllers;

use App\Imports\PartNumbersImport;
use App\Models\YMCOM;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PartNumberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
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

        // if (!is_array($getForecastData)) {
        //     $getForecastData = [$getForecastData];
        // }

        // if (!is_array($getStockData)) {
        //     $getStockData = [$getStockData];
        // }

        if (!is_array($getContainersData)) {
            $getContainersData = [$getContainersData];
        }

        $combinedData = [
            'forecast_data' => $getForecastData,
            'stock_data' => $getStockData,
            'containers_data' => $getContainersData,
        ];

        $endTime = Carbon::now();
        $diff = $startTime->diff($endTime);

        Log::alert("Diferencia: {$diff->h} horas, {$diff->i} minutos, {$diff->s} segundos, " . $diff->f * 1000 . " milisegundos.");

        $client = new Client();

        try {
            // Realizar la petición POST a la API de FastAPI (ajusta la URL y el endpoint según corresponda)
            $response = $client->post('http://10.1.51.200:8000/procesar_json/', [
                'json' => $combinedData,  // Se envía el JSON directamente
                'timeout' => 60,  // Tiempo máximo de espera en segundos
            ]);

            // Convertir la respuesta en JSON
            $responseData = json_decode($response->getBody(), true);

            // Retornar la respuesta de la API de FastAPI (opcionalmente puedes devolverla a tu frontend)
            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);
        } catch (\Exception $e) {
            // En caso de error, devolver el error
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
