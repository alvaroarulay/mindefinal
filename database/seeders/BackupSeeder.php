<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use XBase\TableReader;
use Illuminate\Support\Facades\DB;

class BackupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $table = new TableReader(public_path('dbfs/BACKUPS.DBF'),['encoding' => 'cp1251']);
        while ($record = $table->nextRecord()) {
            DB::table('backups')->insert([
                'dia' => $record->get('dia'),
                'hora' => $record->get('hora'), 
                'archivo' => $record->get('archivo'),
                'observ' => $record->get('observ'),
                'usuar' => $record->get('usuar'),
                'feult' => $record->get('feult'),
                'created_at'=>now(),
                'updated_at'=>now(),
          ]);
        }
    }
}
