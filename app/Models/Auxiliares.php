<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use XBase\TableReader;
use Illuminate\Support\Facades\DB;

class Auxiliares extends Model
{
    use HasFactory;
    protected $table = "auxiliar";
    protected $fillable = [
        'entidad',
        'unidad', 
        'codcont',
        'codaux',
        'nomaux',
        'observ',
        'feult',
        'usuar',
    ];
    protected $hidden = ['id'];
    public function obtenerAuxiliares()
    {
        return Auxiliares::all();
    }
    public function obtenerAuxiliarPorId($id)
    {
        return Auxiliares::find($id);
    }
    public function actualizarDatos(){
        $auxiliares=Auxiliares::all();
        foreach ($auxiliares as $auxiliar) {
            $auxiliar->delete();
        }
        $table = new TableReader('C:/vsiaf/dbfs/auxiliar.dbf',['encoding' => 'cp1251']);
        while ($record = $table->nextRecord()) {
            DB::table('auxiliar')->insert([
                'entidad' => $record->get('entidad'),
                'unidad' => $record->get('unidad'), 
                'codcont' => $record->get('codcont'),
                'codaux' => $record->get('codaux'),
                'nomaux' => $record->get('nomaux'),
                'observ' => $record->get('observ'),
                'feult' => $record->get('feult'),
                'usuar' => $record->get('usuar'),
          ]);
        }
        return Auxiliares::all(); 
      }
}
