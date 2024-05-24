<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use App\Models\Responsables;
use App\Models\Unidadadmin;
use App\Models\Actual;
use XBase\TableCreator;
use XBase\TableEditor;
use XBase\TableReader;

class ResponsablesController extends Controller
{
    public function index(Request $request)
    {
        //if (!$request->ajax()) return redirect('/');
        $unidad = Unidadadmin::where('estado','=','1')->first();
        $buscar = $request->buscar;
        $criterio = $request->criterio;
        
        if ($buscar==''){
            $responsables = Responsables::join('oficina','resp.codofic','=','oficina.codofic')
            ->join('cla_depts','resp.cod_exp','=', 'cla_depts.id')
            ->select('resp.id','resp.codofic','resp.codresp','resp.nomresp','resp.cargo',
            'resp.ci','cla_depts.sigla','oficina.nomofic','resp.api_estado','resp.cod_exp')
            ->where('resp.unidad','=',$unidad->unidad)->paginate(5);
        }
        else{
            $responsables = Responsables::join('oficina','resp.codofic','=','oficina.codofic')
            ->join('cla_depts','resp.cod_exp','=', 'cla_depts.id')
            ->select('resp.id','resp.codofic','resp.codresp','resp.nomresp','resp.cargo',
            'resp.ci','cla_depts.sigla','oficina.nomofic','resp.api_estado','resp.cod_exp')
            ->where('resp.unidad','=',$unidad->unidad)
            ->where($criterio, 'like', '%'. $buscar . '%')->orderBy('id', 'desc')
            ->paginate(5);
        }
        

        return [
            'pagination' => [
                'total'        => $responsables->total(),
                'current_page' => $responsables->currentPage(),
                'per_page'     => $responsables->perPage(),
                'last_page'    => $responsables->lastPage(),
                'from'         => $responsables->firstItem(),
                'to'           => $responsables->lastItem(),
            ],
            'responsables' => $responsables
        ];
    }
    public function store(Request $request)
    {
        $unidad = Unidadadmin::select('unidad')->where('estado','=','1')->first();
        $codofic = Responsables::where('codofic','=',$request->codofic)->count();
        $fecha = Carbon::now()->format('Ymd');

        $responsable = new Responsables();
        $responsable->entidad='0020';
        $responsable->unidad=$unidad->unidad;
        $responsable->codofic = $request->codofic;
        $responsable->codresp = $codofic + 1;
        $responsable->nomresp = $request->nomresp;
        $responsable->cargo = $request->cargo;
        $responsable->observ = $request->observ;
        $responsable->ci = $request->ci;
        $responsable->feult = $fecha;
        $responsable->usuar = \Auth::user()->username;
        $responsable->cod_exp = $request->codexp;
        $responsable->api_estado = 1;
        $responsable->estado = 1;
        $responsable->custodio = 0;
        $responsable->save();

        try {
           
           $table = new TableEditor(
            public_path('dbfs/RESP.DBF'),
            [
                'editMode' => TableEditor::EDIT_MODE_CLONE, //default
            ]
            );
            $record = $table->appendRecord();
            $record->set('entidad', '0081');
            $record->set('unidad',$unidad->unidad);
            $record->set('codofic',$request->codofic);
            $record->set('codresp',$codofic + 1);
            $record->set('nomresp',$request->nomresp);
            $record->set('cargo',$request->cargo);
            $record->set('ci',$request->ci);
            $record->set('feult',$fecha);
            $record->set('usuar',\Auth::user()->username);
            $record->set('cod_exp',$request->codexp);
            $record->set('api_estado',1);
            
            $table
                ->writeRecord()
                ->save()
                ->close();
        } catch (Exception $e) {
            return response()->json(['message' => 'Excepción capturada: '+  $e->getMessage()]);
        }
        
        return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);
    }

    public function update(Request $request)
    {
        //if (!$request->ajax()) return redirect('/');
        $responsable = Responsables::findOrFail($request->id);
        $responsable->nomresp = $request->nomresp;
        $responsable->cargo = $request->cargo;
        $responsable->ci = $request->ci;
        $responsable->cod_exp = $request->cod_exp;
        $responsable->api_estado = $request->api_estado;
        $responsable->save();

        try {
           $table = new TableEditor(public_path('dbfs/RESP.DBF'),['encoding' => 'cp1252']);
        
        while ($record = $table->nextRecord()){
            if($record->get('codofic') == "$request->codofic" && $record->get('codresp') == "$request->codresp"){
                $record->set('nomresp',"$request->nomresp");
                $record->set('cargo',"$request->cargo");
                $record->set('ci',"$request->ci");
                $record->set('cod_exp',"$request->cod_exp");
                $record->set('api_estado',"$request->api_estado");
                $table->writeRecord();
            }
        }
        $table->save()->close();
        } catch (Exception $e) {
            return response()->json(['message' => 'Excepción capturada: '+  $e->getMessage()]);
        }
        
        return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);
    }
    public function actualizarDatos(){
        $table = new TableReader(public_path('dbfs/RESP.DBF'),['encoding' => 'cp1251']);
        $responsables=Responsables::count();
        $contador = 0;
      
        while ($record = $table->nextRecord()) {
            $contador ++;
            if($responsables < $contador){
                DB::table('resp')->insert([
                'entidad' =>$record->get('entidad'),
                'unidad' =>$record->get('unidad'),
                'codofic' =>$record->get('codofic'),
                'codresp' =>$record->get('codresp'),
                'nomresp' =>$record->get('nomresp'),
                'cargo' =>$record->get('cargo'),
                'observ' =>$record->get('observ'),
                'ci' =>$record->get('ci'),
                'feult' =>$record->get('feult'),
                'usuar' =>$record->get('usuar'),
                'cod_exp' =>$record->get('cod_exp'),
                'api_estado' =>$record->get('api_estado'),
                ]);
            }            
        }
        $table->close();
        if($responsables == $contador){
            return response()->json(['message' => 'No hay Registros Nuevos!!!']);
        } 
        else{
            return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);
        }
      }
    public function buscarResponsable(Request $request){

        //if (!$request->ajax()) return redirect('/');

        $filtro = $request->filtro;
        $responsable = Responsables::join('oficina','resp.codofic','=','oficina.codofic')
        ->where('resp.ci','=', $filtro)
        ->select('resp.id','resp.nomresp','resp.cargo','oficina.nomofic','resp.api_estado','resp.codresp','resp.codofic')->first();
        return response()->json(['responsable' => $responsable]);
    }
    public function listarResponsable(Request $request)
    {
        if (!$request->ajax()) return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;
        
        if ($buscar==''){
            $articulos = Articulo::join('categorias','articulos.idcategoria','=','categorias.id')
            ->select('articulos.id','articulos.idcategoria','articulos.codigo','articulos.nombre','categorias.nombre as nombre_categoria','articulos.precio_venta','articulos.stock','articulos.descripcion','articulos.condicion')
            ->where('articulos.stock','>','0')
            ->orderBy('articulos.id', 'desc')->paginate(10);
        }
        else{
            $articulos = Articulo::join('categorias','articulos.idcategoria','=','categorias.id')
            ->select('articulos.id','articulos.idcategoria','articulos.codigo','articulos.nombre','categorias.nombre as nombre_categoria','articulos.precio_venta','articulos.stock','articulos.descripcion','articulos.condicion')
            ->where('articulos.'.$criterio, 'like', '%'. $buscar . '%')
            ->where('articulos.stock','>','0')
            ->orderBy('articulos.id', 'desc')->paginate(10);
        }
        

        return ['articulos' => $articulos];
    }
    public function delete(Request $request){
        //echo (intval($request->codresp));
        $activo = Actual::where('codresp','=',$request->codresp)->where('codofic','=',$request->codofic)->get();
        $activo = $activo->count();
        if($activo==0){
            $res=Responsables::where('id',$request->id)->delete();

            $table = new TableEditor(public_path('dbfs/RESP.DBF'),['encoding' => 'cp1251']);

            while ($record = $table->nextRecord()) {
                if ($record->get('codofic')==$request->codofic && $record->get('codresp')==$request->codresp) {
                    $table->deleteRecord(); //mark record deleted
                }    
            }

            $table->pack()->save()->close();

            return response()->json(['message' => 'Responsable Eliminado Exitosamente !!!']);
        }else{
            return response()->json(['message' => 'El Usuario tiene '.$activo.' Activos asignados, no se puede Eliminar!!!']);
        }
        
    }
    public function repResponsables(){
        $responsable = Responsables::join('oficina','resp.codofic','=','oficina.codofic')
                                    ->select('resp.nomresp','resp.ci','oficina.nomofic','resp.cargo',
                                    'resp.observ',)->get();
        return response()->json(['responsable' => $responsable]);                      
    }

    
}
