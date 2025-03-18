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
    public function index()
    {
        $startTime = Carbon::now();

        // Tu arreglo de números de parte con la cantidad
        $parentPartNumbers = [
            [
                'part_number' => 'BDTS50260A',
                'required_quantity' => 100,
                'required_date' => '2025-03-14'
            ],
            [
                'part_number' => 'BDTS50260A',
                'required_quantity' => 100,
                'required_date' => '2025-03-14'
            ]
        ];


        // Crear una colección para almacenar los resultados de todos los números de parte
        $allChildren = collect();

        // Iterar sobre el arreglo de números de parte
        foreach ($parentPartNumbers as $parentPartNumber) {
            // Obtener los hijos para cada número de parte
            $children = YMCOM::getChildren($parentPartNumber['part_number'], $parentPartNumber['required_quantity']);

            // Combinar los resultados de todos los números de parte
            $allChildren = $allChildren->merge($children);
        }

        // Sumar la cantidad de partes si se repiten
        $groupedChildren = $allChildren->groupBy('MCCPRO')->map(function ($group) {
            // Si hay más de un hijo con el mismo número de parte, sumamos las cantidades
            $totalQuantity = $group->sum(function ($child) {
                return $child['MCQREQ']; // Sumamos la cantidad (MCQREQ)
            });

            // Devolvemos solo el primer elemento del grupo, pero con la cantidad total
            $child = $group->first();
            $child['MCQREQ'] = $totalQuantity; // Asignamos la cantidad total

            return $child;
        })->values(); // Usamos 'values()' para reindexar la colección

        $endTime = Carbon::now();
        $diff = $startTime->diff($endTime);

        dd($groupedChildren, "Diferencia: {$diff->h} horas, {$diff->i} minutos, {$diff->s} segundos, " . $diff->f * 1000 . " milisegundos.");

        return response()->json($groupedChildren);
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

        $path = $request->file('file')->getRealPath();

        Excel::import(new PartNumbersImport, $path);

        $parentPartNumbers = PartNumbersImport::getData();

        // Crear una colección para almacenar los resultados de todos los números de parte
        $allChildren = collect();

        // Iterar sobre el arreglo de números de parte
        foreach ($parentPartNumbers as $key => $parentPartNumber) {
            // Obtener los hijos para cada número de parte, incluyendo la fecha 'required_date'
            $children = YMCOM::getChildren(
                $parentPartNumber['part_number'],
                $parentPartNumber['required_quantity'],
                $parentPartNumber['required_date'] // Pasamos la fecha también
            );

            // Combinar los resultados de todos los números de parte
            $allChildren = $allChildren->merge($children);
        }

        // Agrupar primero por fecha (MCDATE)
        $groupedByDate = $allChildren->groupBy('MCDATE');

        // Ahora dentro de cada grupo de fecha, sumar las cantidades (MCQREQ)
        $finalResult = $groupedByDate->flatMap(function ($dateGroup) {
            return $dateGroup->map(function ($child) {
                return [
                    'part_number' => $child['MCCPRO'],
                    'required_quantity' => $child['MCQREQ'],
                    'required_date' => $child['required_date'],
                ];
            });
        });


        // Sumar las cantidades para las partes que se repiten
        $result = $finalResult->groupBy('part_number')->map(function ($group) {
            $totalQuantity = $group->sum('required_quantity');
            $child = $group->first();
            $child['required_quantity'] = $totalQuantity;
            return $child;
        })->values();


        $endTime = Carbon::now();
        $diff = $startTime->diff($endTime);

        Log::alert("Diferencia: {$diff->h} horas, {$diff->i} minutos, {$diff->s} segundos, " . $diff->f * 1000 . " milisegundos.");

        return response()->json($result);
    }
}
