<?php

namespace App\Http\Controllers;

use App\Exports\DataSelectionExport;
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
        Log::info('Inicio de la carga del archivo Excel.');

        // Obtener el archivo
        $path = $request->file('file_excel');
        Log::info('Archivo recibido: ' . $path->getClientOriginalName());

        try {
            // Realizar la importación
            Log::info('Iniciando la importación de datos desde el archivo Excel...');
            Excel::import(new PartNumbersImport, $path);
            Log::info('Importación completada.');

            // Obtener los datos
            Log::info('Obteniendo datos de la importación...');
            $getForecastData = PartNumbersImport::getForecastData();
            $getStockData = PartNumbersImport::getStockData();
            $getContainersData = PartNumbersImport::getContainersData();

            // Verificar si los datos de containers son un array
            if (!is_array($getContainersData)) {
                Log::info('Los datos de containers no son un array, convirtiendo...');
                $getContainersData = [$getContainersData];
            }

            // Combinar los datos
            $combinedData = [
                'forecast_data' => $getForecastData,
                'stock_data' => $getStockData,
                'containers_data' => $getContainersData
            ];
            // Log::info('Datos combinados listos para enviar a la API.', ['combined_data' => $combinedData]);

            // Calcular el tiempo transcurrido
            $endTime = Carbon::now();
            $diff = $startTime->diff($endTime);
            Log::alert("Diferencia: {$diff->h} horas, {$diff->i} minutos, {$diff->s} segundos, " . $diff->f * 1000 . " milisegundos.");

            // Enviar la petición POST a la API de FastAPI
            $client = new Client();
            Log::info('Enviando datos a la API de FastAPI...');

            try {
                $response = $client->post('http://192.168.130.49:9092/procesar_json/', [
                    'json' => $combinedData,
                    'timeout' => 900,
                    'connect_timeout' => 60,
                ]);

                $responseData = json_decode($response->getBody(), true);

                return Excel::download(
                    new DataSelectionExport($responseData),
                    'genum_' . now()->format('dmYHis') . '.xlsx'
                );
            } catch (\Exception $apiException) {
                Log::error('Error al hacer la petición a la API de FastAPI.', ['error' => $apiException->getMessage()]);
//                return response()->json([
//                    'success' => false,
//                    'error' => $apiException->getMessage()
//                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error en el proceso de carga y procesamiento de datos.', ['error' => $e->getMessage()]);
//            return response()->json([
//                'success' => false,
//                'error' => $e->getMessage()
//            ], 500);
        }
    }
}
