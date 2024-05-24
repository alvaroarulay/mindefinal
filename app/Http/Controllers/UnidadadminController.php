<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unidadadmin;
use XBase\TableCreator;
use XBase\TableEditor;
use XBase\TableReader;

class UnidadadminController extends Controller
{
    protected $unidadadmin;

    public function __construct(Unidadadmin $unidadadmin)
    {
        $this->unidadadmin = $unidadadmin;
    }
    public function index()
    {
        if (!Unidadadmin::where('estado','=','1')->count()) return redirect('/');
        $unidad = Unidadadmin::All();
        return [
            'unidad' => $unidad
        ];
    }
    public function update(Request $request){
        //$data = $this->unidadadmin->obtenerUnidadPorId($request->id);
        if($request->estado == 1){
            $unidades=Unidadadmin::all();
            foreach ($unidades as $data) {
                $data->update(['estado'=>0]);
            };
            $data->where('Id',$request->id)->update(['estado'=>'1']);
        }

        $codcont = Unidadadmin::find($request->id);
        $codcont->unidad=$request->unidad;
        $codcont->descrip=$request->descripcion;
        $codcont->estado=$request->estado;
        $codcont->save();

        try {
            $table = new TableEditor(public_path('dbfs/unidadadmin.DBF'),['encoding' => 'cp1252']);
         
         while ($record = $table->nextRecord()){
             if($record->get('entidad') == "$request->entidad" ){
                 $record->set('unidad',"$request->unidad");
                 $record->set('descrip',"$request->descripcion");
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
