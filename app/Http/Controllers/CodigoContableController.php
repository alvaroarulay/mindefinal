<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CodigoContable;
use App\Models\Auxiliares;
use App\Models\Unidadadmin;
use XBase\TableCreator;
use XBase\TableEditor;
use XBase\TableReader;
use Illuminate\Support\Facades\DB;

class CodigoContableController extends Controller
{
     public function index()
    {
        if (!Unidadadmin::where('estado','=','1')->count()) return redirect('/');
        $codigos = CodigoContable::All();
        return [
            'codigos' => $codigos
        ];
    }
    public function auxiliar(Request $request){
        if(isset($request->id)){
            $unidad = Unidadadmin::where('estado','=','1')->first();
            $auxiliares= Auxiliares::where([
                ['codcont', '=', $request->id],
                ['unidad', '=', $unidad->unidad],
            ])->get();
            $codconts = CodigoContable::where('codcont','=', $request->id)->first();
            return response()->json(
                [
                    'auxiliares'=>$auxiliares,
                    'codconts'=>$codconts,
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
    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $codconts = new CodigoContable($request->all());
        $codconts->save();

        $table = new TableEditor(
            public_path('dbfs/CODCONT.DBF'),
            [
                'editMode' => TableEditor::EDIT_MODE_CLONE, //default
            ]
        );
        $record = $table->appendRecord()
            ->set('codcont', $request->codcont)
            ->set('nombre', $request->nombre)
            ->set('vidautil', $request->vidautil)
            ->set('observ', $request->observ)
            ->set('depreciar', $request->depreciar)
            ->set('actualizar',$request->actualizar)
            ->set('feult', null)
            ->set('usuar', $request->usuar);
        $table->writeRecord($record)->save()->close();

        return redirect()->action([CodigoContableController::class, 'index']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $codcont = $this->codconts->obtenerCodigoContablePorId($id);
        return view('codconts.ver', ['codcont' => $codcont]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $codcont = $this->codconts->obtenerCodigoContablePorId($id);
        return view('codconts.editar', ['codcont' => $codcont]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $codcont = CodigoContable::findOrFail($request->codcont);
        $codcont->nombre=$request->nombre;
        $codcont->save();

        try {
            $table = new TableEditor(public_path('dbfs/CODCONT.DBF'),['encoding' => 'cp1252']);
         while ($record = $table->nextRecord()){
             if($record->get('codcont') == "$request->codcont" ){
                 $record->set('nombre',"$request->nombre");
                 $table->writeRecord();
             }
         }
         $table->save()->close();
         } catch (Exception $e) {
             return response()->json(['message' => 'Excepción capturada: '+  $e->getMessage()]);
         }
         
         return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $codcont = $this->codconts->obtenerCodigoContablePorId($id);
        $codcont->delete();
        return redirect()->action([CodigoContableController::class, 'index']);
    }
    /**
     * Volcar de nuevo una tabla.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function actualizar()
    {
        $codcont = $this->codconts->actualizarDatos();
        return redirect()->action([CodigoContableController::class, 'index']);
    }
    public function actualizarDatos(){
        $table = new TableReader(public_path('dbfs/CODCONT.DBF'),['encoding' => 'cp1252']);
        $contable=CodigoContable::count();
        $contador = 0;
      
        while ($record = $table->nextRecord()) {
            $contador ++;
            if($contable < $contador){
                DB::table('codcont')->insert([
                    'codcont' => $record->get('codcont'),
                    'nombre' => $record->get('nombre'),
                    'vidautil' => $record->get('vidautil'),
                    'observ' => $record->get('observ'), 
                    'depreciar' => $record->get('depreciar'), 
                    'actualizar' => $record->get('actualizar'), 
                    'feult' => $record->get('feult'),
                    'usuar' => $record->get('usuar'),
                  ]);
            }
            
        }
        if($actuales == $contador){
            return response()->json(['message' => 'No hay Registros Nuevos!!!']);
        } 
        else{
            return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);
        }
      }
}
