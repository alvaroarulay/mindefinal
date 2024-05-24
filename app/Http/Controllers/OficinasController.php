<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Oficinas;
use App\Models\Actual;
use App\Models\Responsables;
use App\Models\Unidadadmin;
use XBase\TableCreator;
use XBase\TableEditor;
use XBase\TableReader;

class OficinasController extends Controller
{
    public function index(Request $request)
    {
        if (!Unidadadmin::where('estado','=','1')->count()) return redirect('/');
        $unidad = Unidadadmin::where('estado','=','1')->first();
        $buscar = $request->buscar;
        $criterio = $request->criterio;
        if ($buscar==''){
            $oficinas = Oficinas::select('oficina.id','oficina.codofic','oficina.nomofic','api_estado')
            ->where('oficina.unidad','=',$unidad->unidad)->paginate(10);
        }
        else{
            $oficinas = Oficinas::select('oficina.id','oficina.codofic','oficina.nomofic','api_estado')
            ->where('oficina.unidad','=',$unidad->unidad)
            ->where($criterio, 'like', '%'. $buscar . '%')->orderBy('id', 'desc')
            ->paginate(10);
        }
        return [
            'pagination' => [
                'total'        => $oficinas->total(),
                'current_page' => $oficinas->currentPage(),
                'per_page'     => $oficinas->perPage(),
                'last_page'    => $oficinas->lastPage(),
                'from'         => $oficinas->firstItem(),
                'to'           => $oficinas->lastItem(),
            ],
            'oficinas' => $oficinas
        ];
    }
    public function store(Request $request){
        $oficina = Oficina::findOrFail($request->id);
        $oficina->nomofic = $request->nomofic;
        $articulo->save();
        try {
        $table = new TableEditor(public_path('dbfs/OFICINA.DBF'),['encoding' => 'cp1252']);


        while ($record = $table->nextRecord()){
        if($record->get('codigo') == "$request->codofic"){
            $record->set('nomofic',"$request->nomofic");
            $table->writeRecord();
        }
        }
        $table->save()->close();
        } catch (Exception $e) {
        return response()->json(['message' => 'Excepción capturada: '+  $e->getMessage()]);
        }

        return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);
    }
    public function update(Request $request){

        $oficina = Oficina::findOrFail($request->id);
        $oficina->nomofic = $request->nomofic;
        $articulo->save();
        try {
        $table = new TableEditor(public_path('dbfs/OFICINA.DBF'),['encoding' => 'cp1252']);

        while ($record = $table->nextRecord()){
        if($record->get('codigo') == "$request->codofic"){
            $record->set('nomofic',"$request->nomofic");
            $table->writeRecord();
        }
        }
        $table->save()->close();
        } catch (Exception $e) {
        return response()->json(['message' => 'Excepción capturada: '+  $e->getMessage()]);
        }

        return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);
    }
    public function responsables(Request $request){
        if(isset($request->id)){
            $unidad = Unidadadmin::where('estado','=','1')->first();
            $responsables= Responsables::where([
                ['codofic', '=', $request->id],
                ['unidad', '=', $unidad->unidad],
            ])->get();
            $oficinas = Oficinas::where('codofic','=', $request->id)->first();
            return response()->json(
                [
                    'responsables'=>$responsables,
                    'oficinas'=>$oficinas,
                    'unidad' => $unidad,
                    'success' => true
                ]
                );
        }else
        {
            return response()->json(
                [
                   'success' => false,
                ]
                );
        }
    }
    public function responsableActuales(Request $request){
        if(isset($request->id)){
            $unidad = Unidadadmin::where('estado','=','1')->first();
            $actuales = Actual::where('unidad','=',$unidad->unidad)->get();
            $responsables= Responsables::where([
                ['codofic', '=', $request->id],
                ['unidad', '=', $unidad->unidad],
            ])->get();
            $oficinas = Oficinas::where('codofic','=', $request->id)->first();
            return response()->json(
                [
                    'responsables'=>$responsables,
                    'oficinas'=>$oficinas,
                    'unidad' => $unidad,
                    'success' => true
                ]
                );
        }else
        {
            return response()->json(
                [
                   'success' => false,
                ]
                );
        }
    }
    public function buscarOficina(Request $request){

        //if (!$request->ajax()) return redirect('/');

        $filtro = $request->filtro;
        $oficina = Oficinas::select('id','codofic','nomofic')->where('codofic','=', $filtro)->first();
        return response()->json(['oficina' => $oficina]);
    }
}
