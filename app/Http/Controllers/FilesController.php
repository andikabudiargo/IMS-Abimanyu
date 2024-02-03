<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

use Response;
use App\Permission;
use DataTables;
use DB;

class FilesController extends Controller
{
    private $title;
    public function __construct()
    {
        $this->title = "Files Backup";
    }
    
    public function index(Request $request)
    {

        $data['title'] = $this->title;
        // $directory =  'app/backup';
        $directory =  storage_path() . "/app/backup/";
        $storage = File::allFiles($directory);
        // dd($storage);
        $files=array();
        $path = "";
        $file = "";
        foreach($storage as $index=>$val){
            // array_push($files,$storage[$index]->getPathName());
            // array_push($files,$storage[$index]->getFileName());
            array_push($files,$storage[$index]->getBaseName());
            $path = $storage[$index]->getPath();
            $file = $storage[$index]->getBaseName();
        }
        $data['files'] = $files;
        $data['path'] = $path;

        return view('files.index',$data);

    }

    public function download(Request $request){
        $filename = $request->file;
        $filePath = storage_path() . "/app/backup/".$filename;
        return response()->download($filePath);
    }

}
