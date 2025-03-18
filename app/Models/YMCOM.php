<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class YMCOM extends Model
{
    protected $connection = 'infor-live';
    protected $table = 'LX834FU01.YMCOM';

    protected $fillable = [
        'MCFPRO',
        'MCFCLS',
        'MCCPRO',
        'MCCCLS',
        'MCQREQ',
        'MCCCTM',
        'MCCUSR',
        'MCCCDT',
    ];

    public static function getChildren($parentPartNumber, $requiredQuantity, $requiredDate, $processed = [])
    {
        // Evitar ciclos comprobando si el número de parte ya fue procesado
        if (in_array($parentPartNumber, $processed)) {
            return collect();  // Retorna un conjunto vacío si ya se procesó este número de parte
        }

        // Agregar el número de parte actual a la lista de procesados
        $processed[] = $parentPartNumber;

        // Usar cache para almacenar los resultados de los hijos (15 minutos)
        $cacheKey = "children_{$parentPartNumber}";
        $children = cache()->remember($cacheKey, now()->addMinutes(15), function () use ($parentPartNumber) {
            return self::whereRaw('TRIM(MCFPRO) = ?', [$parentPartNumber])->get();
        });

        $allChildren = collect();

        foreach ($children as $child) {
            // Solo agregar los datos si el número de parte padre es diferente al hijo
            if ($child->MCFPRO != $child->MCCPRO && $child->MCCCLS === 'S1') {

                // Crear un array con toda la información del hijo
                $childData = [
                    'MCFPRO'  => $child->MCFPRO,
                    'MCFCLS'  => $child->MCFCLS,
                    'MCCPRO'  => $child->MCCPRO,
                    'MCCCLS'  => $child->MCCCLS,
                    'MCQREQ'  => $child->MCQREQ * $requiredQuantity,
                    'MCCCTM'  => $child->MCCCTM,
                    'MCCUSR'  => $child->MCCUSR,
                    'MCCCDT'  => $child->MCCCDT,
                    'required_date' => $requiredDate // Aquí asignamos la fecha del import
                ];

                // Agregar el hijo con la cantidad multiplicada y la fecha a la lista de todos los hijos
                $allChildren->push($childData);

                // Llamada recursiva para obtener sub-hijos, pasando también la cantidad y la fecha
                $subChildren = self::getChildren($child->MCCPRO, $requiredQuantity, $requiredDate, $processed);  // Pasamos también la fecha
                $allChildren = $allChildren->merge($subChildren);
            }
        }

        return $allChildren->unique('MCCPRO');
    }
}
