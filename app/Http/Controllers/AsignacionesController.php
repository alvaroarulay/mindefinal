<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asignaciones;

class AsignacionesController extends Controller
{
    public function repAsignaciones(){
        $asignaciones = Asignaciones::join('actual','asignacion.codactual','=','actual.id')
        ->join('resp',function ($join) {
            $join->on('asignacion.codresp', '=', 'resp.codresp');
                $join->on('asignacion.codofic', '=', 'resp.codofic');})
        ->join('oficina','oficina.codofic','=','resp.codofic')
        ->select('actual.codigo','resp.nomresp','oficina.nomofic','asignacion.usuario','asignacion.created_at')->get();
        return response()->json(['asignaciones' => $asignaciones]);      
    }
}
