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
        $validated = $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv',
            'stock_days' => 'required|integer|min:1|max:10',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ], [
            'file_excel.required' => 'Por favor selecciona un archivo Excel.',
            'file_excel.file' => 'El archivo subido no es válido.',
            'file_excel.mimes' => 'Solo se aceptan archivos con extensión .xlsx, .xls o .csv.',
            'stock_days.required' => 'Debes seleccionar los días de stock.',
            'stock_days.integer' => 'Los días de stock deben ser un número entero.',
            'stock_days.min' => 'Los días de stock deben ser al menos 1.',
            'stock_days.max' => 'Los días de stock no pueden ser más de 10.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
            'end_date.required' => 'La fecha de finalización es obligatoria.',
            'end_date.date' => 'La fecha de finalización debe ser una fecha válida.',
            'end_date.after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la fecha de inicio.',
        ]);

        $startTime = Carbon::now();

        // Obtener los datos del formulario
        $path = $validated['file_excel'];
        $stockDays = $validated['stock_days'];
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        try {
            // Realizar la importación con las fechas
            $import = new PartNumbersImport($stockDays, $startDate, $endDate);
            Excel::import($import, $path);

            // Obtener los datos
            $getForecastData = PartNumbersImport::getForecastData();
            $getStockData = PartNumbersImport::getStockData();
            $getContainersData = PartNumbersImport::getContainersData();

            // Verificar si los datos de containers son un array
            if (!is_array($getContainersData)) {
                $getContainersData = [$getContainersData];
            }

            // Crear un mapa de part_numbers para búsqueda rápida
            $stockPartNumbersMap = [];
            foreach ($getStockData as $stockItem) {
                $cleanPart = trim($stockItem['part_number']);
                $stockPartNumbersMap[$cleanPart] = true;
            }

            // Filtrar forecast_data
            $filteredForecastData = [];
            foreach ($getForecastData as $forecastItem) {
                $cleanPart = trim($forecastItem['part_number']);
                if (isset($stockPartNumbersMap[$cleanPart])) {
                    $filteredForecastData[] = $forecastItem;
                }
            }

            // Combinar los datos
            $combinedData = [
                'forecast_data' => $filteredForecastData,
                'stock_data' => $getStockData,
                'containers_data' => $getContainersData
            ];

            $prettyJson = json_encode($combinedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            Log::info("Datos combinados listos para enviar a la API:\n" . $prettyJson);

            // Calcular el tiempo transcurrido
            $endTime = Carbon::now();
            $diff = $startTime->diff($endTime);
            Log::alert("Diferencia: {$diff->h} horas, {$diff->i} minutos, {$diff->s} segundos, " . $diff->f * 1000 . " milisegundos.");

            // Enviar la petición POST a la API de FastAPI
            $client = new Client();

            try {
                $response = $client->post('http://192.168.130.49:9092/procesar_json/', [
                    'json' => $combinedData,
                    'timeout' => 900,
                    'connect_timeout' => 60,
                ]);

                $responseData = json_decode($response->getBody(), true);

                // Calcular el tiempo transcurrido
                $endTime = Carbon::now();
                $diff = $startTime->diff($endTime);
                Log::alert("Diferencia: {$diff->h} horas, {$diff->i} minutos, {$diff->s} segundos, " . $diff->f * 1000 . " milisegundos.");

                return Excel::download(
                    new DataSelectionExport($responseData),
                    'genum_' . now()->format('dmYHis') . '.xlsx'
                );
            } catch (\Exception $apiException) {
                Log::error('Error al hacer la petición a la API de FastAPI.', ['error' => $apiException->getMessage()]);
            }
        } catch (\Exception $e) {
            Log::error('Error en el proceso de carga y procesamiento de datos.', ['error' => $e->getMessage()]);
        }
    }
}
