<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Auxiliares;
use App\Models\Unidadadmin;
use XBase\TableCreator;
use XBase\TableEditor;
use XBase\TableReader;

class AuxiliaresController extends Controller
{
   
    public function index(Request $request)
    {
        if (!Unidadadmin::where('estado','=','1')->count()) return redirect('/');
        $unidad = Unidadadmin::where('estado','=','1')->first();
       
        $buscar = $request->buscar;
        $criterio = $request->criterio;
        
        if ($buscar==''){
            $auxiliares = Auxiliares::join('codcont','auxiliar.codcont','=','codcont.codcont')
            ->select('auxiliar.id','auxiliar.nomaux','codcont.nombre')
            ->where('auxiliar.unidad','=',$unidad->unidad)->paginate(10);
        }
        else{
            $auxiliares = Auxiliares::join('codcont','auxiliar.codcont','=','codcont.codcont')
            ->select('auxiliar.id','auxiliar.nomaux','codcont.nombre')
            ->where('auxiliar.unidad','=',$unidad->unidad)
            ->where($criterio, 'like', '%'. $buscar . '%')->orderBy('id', 'desc')
            ->paginate(10);
        }
        

        return [
            'pagination' => [
                'total'        => $auxiliares->total(),
                'current_page' => $auxiliares->currentPage(),
                'per_page'     => $auxiliares->perPage(),
                'last_page'    => $auxiliares->lastPage(),
                'from'         => $auxiliares->firstItem(),
                'to'           => $auxiliares->lastItem(),
            ],
            'auxiliares' => $auxiliares
        ];
    }
    public function selectAuxiliar($id){
        if (!Unidadadmin::where('estado','=','1')->count()) return redirect('/');
        $unidad = Unidadadmin::where('estado','=','1')->first();

        $auxiliares = Auxiliares::select('id','nomaux','codaux')
            ->where('codcont','=',$id)
            ->get();
        return response()->json(['auxiliares'=>$auxiliares]);
    }
    public function update(Request $request){
        //if (!$request->ajax()) return redirect('/');
        $auxiliar = Auxiliares::find($request->id);
        $auxiliar->nomaux = $request->nomaux;
        $auxiliar->codcont = $request->codcont;
        $auxiliar->save();

        try {
           $table = new TableEditor(public_path('dbfs/AUXILIAR.DBF'),['encoding' => 'cp1252']);
        while ($record = $table->nextRecord()){
            if($record->get('codcont') == "$request->codcont" && $record->get('codaux') == "$request->codaux"){
                $record->set('nomaux',"$request->nomaux");
                $record->set('codcont',"$request->codcont");
                $table->writeRecord();
            }
        }
        $table->save()->close();
        } catch (Exception $e) {
            return response()->json(['message' => 'Excepción capturada: '+  $e->getMessage()]);
        }
        
        return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);
    }
}
