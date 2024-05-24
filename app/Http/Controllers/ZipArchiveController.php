<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use ZipArchive;

class ZipArchiveController extends Controller
{
    public function downloadZip()
    {                                
        $zip = new ZipArchive;
        $date = Carbon::now();
        $fileName = $date->format('Ymd') . '.' .'zip';

        if ($zip->open(public_path($fileName), ZipArchive::CREATE) === TRUE)
        {
            $files = \File::files(public_path('dbfs'));

            foreach ($files as $key => $value) {
                $file = basename($value);
                $zip->addFile($value, $file);
            }

            $zip->close();
        }

        return response()->download(public_path($fileName));
    }
}
