<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Actual;
use App\Models\CodigoContable;
use App\Models\Auxiliares;
use App\Models\Oficinas;
use App\Models\Responsables;
use App\Models\Unidadadmin;
use App\Models\Logs;
use App\Models\Asignaciones;
use XBase\TableCreator;
use XBase\TableEditor;
use XBase\TableReader;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Jenssegers\Date\Date;

class ActualController extends Controller
{
    public function index(Request $request)
    {   
        //$actuales = $this->actuales->obtenerActuales();
        //if (!$request->ajax()) return redirect('/');
        if (\Auth::check()) {
            // El usuario está autenticado
            $unidad = Unidadadmin::where('estado','=','1')->first();
            $grupocontable = CodigoContable::All();
            $buscar = $request->buscar;
            $criterio = $request->criterio;

            if ($buscar==''){
            $actuales = Actual::join('codcont','actual.codcont','=','codcont.codcont')
            ->join('auxiliar',function ($join) {
                $join->on('actual.codaux', '=', 'auxiliar.codaux');
                    $join->on('actual.unidad', '=', 'auxiliar.unidad');
                    $join->on('actual.codcont', '=', 'auxiliar.codcont');
            })
            ->join('oficina',function ($join) {
                $join->on('actual.codofic', '=', 'oficina.codofic');
                    $join->on('actual.unidad', '=', 'oficina.unidad');
            })
            ->join('resp',function ($join) {
                $join->on('actual.codresp', '=', 'resp.codresp');
                    $join->on('actual.codofic', '=', 'resp.codofic');
                    $join->on('actual.unidad', '=', 'resp.unidad');
            })
            ->select('actual.id','actual.unidad','actual.codigo','codcont.nombre',
            'auxiliar.nomaux','actual.vidautil','oficina.nomofic','resp.nomresp',
            'actual.descripcion','actual.codestado','actual.estadoasignacion',
            'actual.codigosec','actual.observ','actual.codcont','actual.codaux')
            ->where('actual.unidad','=',$unidad->unidad)->paginate(5);
            }
            else{
            $actuales = Actual::join('codcont','actual.codcont','=','codcont.codcont')
            ->join('auxiliar',function ($join) {
                $join->on('actual.codaux', '=', 'auxiliar.codaux');
                        $join->on('actual.unidad', '=', 'auxiliar.unidad');
                        $join->on('actual.codcont', '=', 'auxiliar.codcont');
            })
            ->join('oficina',function ($join) {
                $join->on('actual.codofic', '=', 'oficina.codofic');
                        $join->on('actual.unidad', '=', 'oficina.unidad');
            })
            ->join('resp',function ($join) {
                $join->on('actual.codresp', '=', 'resp.codresp');
                        $join->on('actual.codofic', '=', 'resp.codofic');
                        $join->on('actual.unidad', '=', 'resp.unidad');
            })
            ->select('actual.id','actual.unidad','actual.codigo','codcont.nombre',
            'auxiliar.nomaux','actual.vidautil','oficina.nomofic','resp.nomresp',
            'actual.descripcion','actual.codestado','actual.estadoasignacion',
            'actual.codigosec','actual.observ','actual.codcont','actual.codaux')
            ->where('actual.unidad','=',$unidad->unidad)
            ->where('actual.'.$criterio, 'like', '%'. $buscar . '%')->paginate(5);           
            }
            //return view('actuales.lista', ['actuales' => $actuales,'unidad'=>$unidad]);
            return [
            'pagination' => [
                'total'        => $actuales->total(),
                'current_page' => $actuales->currentPage(),
                'per_page'     => $actuales->perPage(),
                'last_page'    => $actuales->lastPage(),
                'from'         => $actuales->firstItem(),
                'to'           => $actuales->lastItem(),
            ],
            'actuales'=>$actuales,
            'grupocontable'=>$grupocontable
            ];

            // Puedes realizar alguna acción con $user
        } else {
            $actuales = 0;
            return ['actuales'=>$actuales, 'pagination'=>0];
        }
        
    }
    public function show($id)
    {
        $actual = Actual::find($id);
        $responsable = Responsables::select('nomresp')->where('codresp','=',$actual->codresp)->where('codofic','=',$actual->codofic)->first();
        $codcont = CodigoContable::select('nombre')->where('codcont','=',$actual->codcont)->first();
        $auxiliar = Auxiliares::select('nomaux')->where('codaux','=',$actual->codaux)->first();
        $oficina = Oficinas::select('nomofic')->where('codofic','=',$actual->codofic)->first();
        /*$responsable = $this->responsable->obtenerResponsablePorId($actual->codresp);
        $codcont = $this->codconts->obtenerCodigoContablePorId($actual->codcont);
        $auxiliar = $this->auxiliar->obtenerAuxiliarPorId($actual->codaux);
        $oficina = $this->oficina->obtenerOficinaPorId($actual->codofic);*/
        return view('actuales.ver', ['actual' => $actual,'responsable'=>$responsable,'codcont'=>$codcont,'auxiliar'=>$auxiliar,'oficina'=>$oficina]);
    }
    public function update(Request $request)
    {
        //if (!$request->ajax()) return redirect('/');

        $articuloant = Actual::where('id','=',$request->id)->first();
        $codActualizar = $request->id;
        $contcodcont = $articuloant->codcont != $request->codcont ? true : false;
        $contcodaux = $articuloant->codaux != $request->codaux ? true : false;
        $contdescripcion = $articuloant->descripcion != $request->descripcion ? true : false;
        $contobserv = $articuloant->observ != $request->observacion ? true : false;
        $contcodestado = $articuloant->codestado != $request->estado ? true : false;
        $contcodsec = $articuloant->codigosec != $request->codsec ? true : false;

        $articulo = Actual::findOrFail($request->id);
        $articulo->codcont = $request->codcont;
        $articulo->codaux = $request->codaux;
        $articulo->descripcion = $request->descripcion;
        $articulo->observ = $request->observacion;
        $articulo->codestado = $request->estado;
        $articulo->codigosec = $request->codsec;
        $articulo->codimage= $request->id;
        $articulo->save();

        if ($contcodcont){
        $logs = new Logs();
        $logs->codactual = $request->id;
        $logs->descripcion = 'Se Modifico el Grupo Contable';
        $logs->user = auth()->user()->name;
        $logs->save();
        };
        if ($contcodaux){
        $logs = new Logs();
        $logs->codactual = $request->id;
        $logs->descripcion = 'Se Modifico el Auxliar';
        $logs->user = auth()->user()->name;
        $logs->save();
        };
        if ($contdescripcion){
        $logs = new Logs();
        $logs->codactual = $request->id;
        $logs->descripcion = 'Se Modifico la Descripción del Activo';
        $logs->user = auth()->user()->name;
        $logs->save();
        };
        if ($contobserv){
        $logs = new Logs();
        $logs->codactual = $request->id;
        $logs->descripcion = 'Se Modifico la observación del Activo';
        $logs->user = auth()->user()->name;
        $logs->save();
        };
        if ($contcodestado){
        $logs = new Logs();
        $logs->codactual = $request->id;
        $logs->descripcion = 'Se Modifico el Estado del Activo';
        $logs->user = auth()->user()->name;
        $logs->save();
        };
        if ($contcodsec){
        $logs = new Logs();
        $logs->codactual = $request->id;
        $logs->descripcion = 'Se Modifico el código Secundario';
        $logs->user = auth()->user()->name;
        $logs->save();
        };
        try {
            $table = new TableEditor(public_path('dbfs/ACTUAL.DBF'),['encoding' => 'cp1252']);

            while ($record = $table->nextRecord()){
                if($record->get('codigo') == "$request->codigo"){
                    $record->set('codcont',"$request->codcont");
                    $record->set('codaux',"$request->codaux");
                    $record->set('descrip',"$request->descripcion");
                    $record->set('codestado',"$request->estado");
                    $record->set('codigosec',"$request->codsec"); 
                    $table->writeRecord();
                }
            }
            $table->save()->close();
        } catch (Exception $e) {
        return response()->json(['message' => 'Excepción capturada: '+  $e->getMessage()]);
        }

        return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);

    }
    public function updateResponasable(Request $request){
        $data = $request->data;
        $codoficina = \Auth::user()->codofic;
        $codresponsable = \Auth::user()->codresp;
        try {
            for ($i=0; $i < count($data); $i++) {

                $id = $data[$i]['id'];

                $articuloant = Actual::where('id','=',$id)->first();

                $asignacion = New Asignaciones();
                $asignacion->codactual = $id;
                $asignacion->codresp = $articuloant->codresp ;
                $asignacion->codofic = $articuloant->codofic;
                $asignacion->usuario = \Auth::user()->name;
                $asignacion->save();
                        
                $articulo = Actual::findOrFail($id);
                $articulo->codresp = $codresponsable;
                $articulo->codofic = $codoficina;
                $articulo->estadoasignacion = 0;
                $articulo->save();
                
                $logs = new Logs();
                $logs->codactual = $id;
                $logs->descripcion = 'Se Modifico el Responsable y Oficina';
                $logs->user = \Auth::user()->name;
                $logs->save();
       
                $table = new TableEditor(public_path('dbfs/ACTUAL.DBF'));
                
                $codigo = $data[$i]['codigo'];

                while ($record = $table->nextRecord()){
                    if($record->get('codigo') == "$codigo"){
                        $record->set('codresp',"$codresponsable");
                        $record->set('codofic',"$codoficina"); 
                        $table->writeRecord();
                    }
                }
                $table->save()->close(); 
            }
            
            } catch (Exception $e) {
            return response()->json(['message' => 'Excepción capturada: '.$e->getMessage()]);
            }
            return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);
    }
    public function imprimir($id){
        $actual = Actual::find($id);
        $responsable = Responsables::select('nomresp')->where('codresp','=',$actual->codresp)->first();
        $codcont = CodigoContable::select('nombre')->where('codcont','=',$actual->codcont)->first();
        $auxiliar = Auxiliares::select('nomaux')->where('codaux','=',$actual->codaux)->first();
        $oficina = Oficinas::select('nomofic')->where('codofic','=',$actual->codofic)->first();
        $qr = QrCode::generate('http://dbfprueba.test/actuales/veractual/'.$actual->id);
        $pdf = \PDF::loadView('plantillapdf.pdf',compact('actual','responsable','codcont','auxiliar','oficina','qr'));
        $pdf->set_paper("A7", "landscape");
        return $pdf->download('ejemplo.pdf');
    }
    public function verinvitado($id){
        $actual = $this->actuales->obtenerActualPorId($id);
        $responsable = Responsables::select('nomresp')->where('codresp','=',$actual->codresp)->first();
        $codcont = CodigoContable::select('nombre')->where('codcont','=',$actual->codcont)->first();
        $auxiliar = Auxiliares::select('nomaux')->where('codaux','=',$actual->codaux)->first();
        $oficina = Oficinas::select('nomofic')->where('codofic','=',$actual->codofic)->first();
        return view('actuales.actualver', ['actual' => $actual,'responsable'=>$responsable,'codcont'=>$codcont,'auxiliar'=>$auxiliar,'oficina'=>$oficina]);
    }
    public function actualizarDatos(){
        $table = new TableReader(public_path('dbfs/ACTUAL.DBF'),['encoding' => 'cp1251']);
        $actuales=Actual::count();
        $contador = 0;

        while ($record = $table->nextRecord()) {
        $contador ++;
        if($actuales < $contador){
            DB::table('actual')->insert([
                'unidad' => $record->get('unidad'), 
                'entidad' => $record->get('entidad'),
                'codigo' => $record->get('codigo'),
                'codcont' => $record->get('codcont'),
                'codaux' => $record->get('codaux'),
                'vidautil' => $record->get('vidautil'),
                'descripcion' => $record->get('descrip'),
                'costo' => $record->get('costo'),
                'depacu' => $record->get('depacu'),
                'mes' => $record->get('mes'), 
                'año' => $record->get('ano'), 
                'b_rev' => $record->get('b_rev'),
                'dia' => $record->get('dia'), 
                'codofic' => $record->get('codofic'),
                'codresp' => $record->get('codresp'),
                'observ' => $record->get('observ'),
                'dia_ant' => $record->get('dia_ant'), 
                'mes_ant' => $record->get('mes_ant'), 
                'año_ant' => $record->get('ano_ant'),
                'vut_ant' => $record->get('vut_ant'),
                'costo_ant' => $record->get('costo_ant'),
                'band_ufv' => $record->get('band_ufv'), 
                'codestado' => $record->get('codestado'),
                'cod_rube' => $record->get('cod_rube'),
                'nro_conv' => $record->get('nro_conv'),
                'org_fin' => $record->get('org_fin'),
                'usuar' => $record->get('usuar'),
                'api_estado' => $record->get('api_estado'),
                'codigosec' => $record->get('codigosec'),
                'banderas' => $record->get('banderas'),
                'fec_mod' => $record->get('fec_mod'),
                'usu_mod' => $record->get('usu_mod'),
            ]);
            }
        }
        $table->close();

        if($actuales == $contador){
            return response()->json(['message' => 'No hay Registros Nuevos!!!']);
            } 
        else{
            return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);
            }
    }
    public function reporteActivos()
    {   
        //$actuales = $this->actuales->obtenerActuales();
        //if (!$request->ajax()) return redirect('/');
        
        $unidad = Unidadadmin::where('estado','=','1')->first();

        $actuales = Actual::join('codcont','actual.codcont','=','codcont.codcont')
        ->join('auxiliar',function ($join) {
        $join->on('actual.codaux', '=', 'auxiliar.codaux');
            $join->on('actual.unidad', '=', 'auxiliar.unidad');
            $join->on('actual.codcont', '=', 'auxiliar.codcont');
        })
        ->join('oficina',function ($join) {
        $join->on('actual.codofic', '=', 'oficina.codofic');
            $join->on('actual.unidad', '=', 'oficina.unidad');
        })
        ->join('resp',function ($join) {
        $join->on('actual.codresp', '=', 'resp.codresp');
            $join->on('actual.codofic', '=', 'resp.codofic');
            $join->on('actual.unidad', '=', 'resp.unidad');
        })
        ->join('estado','actual.codestado','=','estado.codestado')
        ->select('actual.id','actual.unidad','actual.codigo','codcont.nombre',
        'auxiliar.nomaux','actual.vidautil','oficina.nomofic','resp.nomresp',
        'actual.descripcion','estado.nomestado','actual.estadoasignacion',
        'actual.codigosec','actual.observ','actual.codcont','actual.codaux')
        ->where('actual.unidad','=',$unidad->unidad)->get();     
        //return view('actuales.lista', ['actuales' => $actuales,'unidad'=>$unidad]);
        return response()->json(['actuales'=>$actuales]);
    }
    public function buscarActivos(Request $request){
        $data = $request->filtro;
        $unidad = Unidadadmin::where('estado','=','1')->first();
        $actuales = Actual::where('codigo','=',$data)->first();
        return response()->json(['actuales'=>$actuales]);
    }
    public function buscarActivoResp(Request $request){
        $codresp = $request->codresp;
        $codofic = $request->codofic;
        $unidad = Unidadadmin::where('estado','=','1')->first();
        $actuales = Actual::join('oficina','oficina.codofic','=','actual.codofic')
                    ->join('auxiliar',function ($join) {
                        $join->on('actual.codaux', '=', 'auxiliar.codaux');
                            $join->on('actual.unidad', '=', 'auxiliar.unidad');
                            $join->on('actual.codcont', '=', 'auxiliar.codcont');
                    })
                    ->join('estado','actual.codestado','=','estado.id')
                    ->select('actual.id','actual.unidad','actual.codigo','actual.codresp','actual.codofic',
                    'actual.codaux','auxiliar.nomaux','estado.nomestado',
                    'actual.vidautil','oficina.nomofic',
                    'actual.descripcion','actual.codestado','actual.estadoasignacion',
                    'actual.codigosec','actual.observ')
                    ->where('actual.codresp','=',$codresp)
                    ->where('actual.codofic','=',$codofic)->get();
        return response()->json(['actuales'=>$actuales]);
    }
    public function updateAsignacion(Request $request){
        $data = $request->data;
        $codresp = $request->codresp2;
        $codofic = $request->codofic2;
        try {
            

            for ($i=0; $i < count($data); $i++) {
                
                $id = $data[$i]['id'];

                $articuloant = Actual::where('id','=',$id)->first();

                $asignacion = New Asignaciones();
                $asignacion->codactual = $id;
                $asignacion->codresp = $articuloant->codresp ;
                $asignacion->codofic = $articuloant->codofic;
                $asignacion->usuario = \Auth::user()->name;
                $asignacion->save();

                $articulo = Actual::findOrFail($id);
                $articulo->codresp = $request->codresp2;
                $articulo->codofic = $request->codofic2;
                $articulo->save();
                
                $logs = new Logs();
                $logs->codactual = $data[$i]['id'];
                $logs->descripcion = 'Se Modifico el Responsable y Oficina';
                $logs->user = \Auth::user()->name;
                $logs->save();
                
                $codigo = $data[$i]['codigo'];

                $table = new TableEditor(public_path('dbfs/ACTUAL.DBF'));
                while ($record = $table->nextRecord()){

                    if($record->get('codigo') == "$codigo"){
                        $record->set('codresp',"$codresp");
                        $record->set('codofic',"$codofic"); 
                        $table->writeRecord();
                    }
                }
                $table->save()->close();

                $codigo = "";
            }
            
        } catch (Exception $e) {
            return response()->json(['message' => 'Excepción capturada: '.$e->getMessage()]);
        }
            return response()->json(['message' => 'Datos Actualizados Correctamente!!!']);
    }
    public function repAsignaciones(Request $request){
        
        Date::setLocale('es');
        $fechaTitulo = Date::now()->format('l j F Y');
        $fechDerecha = Date::now()->format('d/M/Y');
        $datos = Actual::join('auxiliar',function ($join) {
                                        $join->on('actual.codaux', '=', 'auxiliar.codaux');
                                            $join->on('actual.unidad', '=', 'auxiliar.unidad');
                                            $join->on('actual.codcont', '=', 'auxiliar.codcont');
                                    })
                                    ->join('estado','actual.codestado','=','estado.id')
                                    ->select('actual.codigo','actual.codaux','auxiliar.nomaux','estado.nomestado', 'actual.descripcion',)
                                    ->where('actual.codresp','=',$request->codresp)
                                    ->where('actual.codofic','=',$request->codofic)->get();
        $responsable = Responsables::join('oficina','resp.codofic','=','oficina.codofic')
                                    ->join('cla_depts','resp.cod_exp','=','cla_depts.id')
                                    ->select('resp.nomresp','oficina.nomofic','resp.cargo','oficina.codofic','resp.ci','cla_depts.sigla')
                                    ->where('resp.codresp','=',$request->codresp)
                                    ->where('resp.codofic','=',$request->codofic)->first();
        $total = $datos->count();
        $pdf=Pdf::loadView('plantillapdf.repAsignacion',['datos'=>$datos,'responsable'=>$responsable,'fechaTitulo'=>$fechaTitulo,'fechaDerecha'=>$fechDerecha,'total'=>$total]);
        $pdf->set_paper(array(0,0,800,617));
        return $pdf->download();
        
    }
    public function repDevoluciones(Request $request){
        
        Date::setLocale('es');
        $fechaTitulo = Date::now()->format('l j F Y');
        $fechDerecha = Date::now()->format('d/M/Y');
        $datos = Asignaciones::join('actual','actual.id','=','asignacion.codactual')
                                    ->join('auxiliar',function ($join) {
                                        $join->on('actual.codaux', '=', 'auxiliar.codaux');
                                            $join->on('actual.unidad', '=', 'auxiliar.unidad');
                                            $join->on('actual.codcont', '=', 'auxiliar.codcont');
                                    })
                                    ->join('estado','actual.codestado','=','estado.id')
                                    ->select('actual.codigo','actual.codaux','auxiliar.nomaux','estado.nomestado', 'actual.descripcion',)
                                    ->where('asignacion.codresp','=',$request->codresp)
                                    ->where('asignacion.codofic','=',$request->codofic)->get();
        $responsable = Responsables::join('oficina','resp.codofic','=','oficina.codofic')
                                    ->join('cla_depts','resp.cod_exp','=','cla_depts.id')
                                    ->select('resp.nomresp','oficina.nomofic','resp.cargo','oficina.codofic','resp.ci','cla_depts.sigla')
                                    ->where('resp.codresp','=',$request->codresp)
                                    ->where('resp.codofic','=',$request->codofic)->first();
        $total = $datos->count();
        $pdf=Pdf::loadView('plantillapdf.repDevolucion',['datos'=>$datos,'responsable'=>$responsable,'fechaTitulo'=>$fechaTitulo,'fechaDerecha'=>$fechDerecha,'total'=>$total]);
        $pdf->set_paper(array(0,0,800,617));
        return $pdf->download();
        
    }
    public function buscarActivoEstado(Request $request){  
        $buscar = $request->buscar;
        $criterio = $request->criterio;

        if ($buscar==''){
            $actuales = Actual::select('id','codigo','codresp','codofic','descripcion','codestado')->where('estadoasignacion','=','0')->paginate(5);
        }
        else{
            $actuales = Actual::select('id','codigo','codresp','codofic','descripcion','codestado')->where('estadoasignacion','=','0')->where('actual.'.$criterio, 'like', '%'. $buscar . '%')->paginate(5);
        }
        return [
                'pagination' => [
                    'total'        => $actuales->total(),
                    'current_page' => $actuales->currentPage(),
                    'per_page'     => $actuales->perPage(),
                    'last_page'    => $actuales->lastPage(),
                    'from'         => $actuales->firstItem(),
                    'to'           => $actuales->lastItem(),
                ],
                'actuales'=>$actuales,
                ];
    }
}
