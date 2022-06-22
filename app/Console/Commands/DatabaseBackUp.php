<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use ZipArchive;
use Log;
use File;

class DatabaseBackUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup database postgresql';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sourceFile = "backup-" . Carbon::now()->format('Y-m-d-h') . ".pgsql";
        $zipFile = "backup-" . Carbon::now()->format('Y-m-d-h') . ".zip";
        $storagePath = storage_path() . "/app/backup/";

        if (PHP_OS_FAMILY === "Windows") {
            //on Windows
            $command = "".env('DUMP_PATH')."pg_dump --no-owner --dbname=" . env('DB_USERNAME') . "://".env('DB_USERNAME').":".env('DB_PASSWORD')."@" . env('DB_HOST') . ":" . env('DB_PORT'). "/" . env('DB_DATABASE') . "  > " . storage_path() . "/app/backup/" . $sourceFile;
        } elseif (PHP_OS_FAMILY === "Linux") {
            //on linux
            $command = "PGPASSWORD=".env('DB_PASSWORD')." pg_dump --no-owner -h 127.0.0.1  -p 5432 -U ".env('DB_USERNAME')." " . env('DB_DATABASE') . " > " . storage_path() . "/app/backup/" . $sourceFile;
        }

        $returnVar = NULL;
        $output = NULL;
        exec($command, $output, $returnVar);

        //kasih waktu untuk mencari file setelah backup selesai
        for($i=0;$i<1000000;$i++){
            if (file_exists(storage_path() . "/app/backup/" . $sourceFile)) {
                $hasil  = $this->converToZip($sourceFile,$zipFile);
                break;
            }
        }

        if ($hasil){
            Log::info('[PROCESS BACKUP DATA] ' . $sourceFile .' to '.$zipFile .' on '.storage_path() . "/app/backup/");
        }else{
            Log::info('[PROCESS BACKUP DATA] convert to zip failed ...'   );
        }

    }

    public function converToZip($sourceFile,$zipFile)
    {
        $path = storage_path('/app/backup/');
        $filesInFolder = \File::allFiles($path);
        $fileZip = storage_path() . "/app/backup/$zipFile";
        $fileToZip = storage_path() . "/app/backup/$sourceFile";

        if (!file_exists($fileToZip)) {
            return false;
        }
        
        $zip = new ZipArchive;
        if (!$zip->open($fileZip, ZIPARCHIVE::CREATE)) {
            return false;
        }
      
        if (($zip->open($fileZip, ZipArchive::CREATE | ZipArchive::OVERWRITE))) {
            $zip->addFile($fileToZip, $sourceFile);
            $zip->close();
            for($i=0;$i<1000000;$i++){
                if(file_exists($fileToZip)){
                    unlink($fileToZip);
                    break;
                }
            }
            return true;  
        } 
    }
}
