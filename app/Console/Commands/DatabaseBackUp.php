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
            // $command = "PGPASSWORD=".env('DB_PASSWORD')." pg_dump --no-owner -h 127.0.0.1  -p 5432 -U ".env('DB_USERNAME')." " . env('DB_DATABASE') . " > " . storage_path() . "/app/backup/" . $sourceFile;
            $command = "PGPASSWORD=".env('DB_PASSWORD')." pg_dump --no-owner -h 127.0.0.1  -p 5432 -U ".env('DB_USERNAME')." " . env('DB_DATABASE') . " > " . storage_path() . "/app/backup/" . $sourceFile;
        }

        $startedAt = Carbon::now();
        $returnVar = NULL;
        $output = NULL;
        // stderr ditangkap ke $output, stdout tetap ke file dump
        exec(str_replace(" > ", " 2>&1 > ", $command), $output, $returnVar);

        $error = null;
        if ($returnVar !== 0) {
            $error = trim(implode("\n", $output)) ?: "pg_dump exit code $returnVar";
            File::delete($storagePath . $sourceFile);
        } elseif (!File::exists($storagePath . $sourceFile) || File::size($storagePath . $sourceFile) === 0) {
            $error = "Hasil pg_dump kosong";
            File::delete($storagePath . $sourceFile);
        } elseif (!$this->converToZip($sourceFile, $zipFile)) {
            $error = "Convert to zip gagal";
        }

        File::append(storage_path('app/backup_log.jsonl'), json_encode([
            'file' => $zipFile,
            'started_at' => $startedAt->toDateTimeString(),
            'duration' => Carbon::now()->diffInSeconds($startedAt),
            'status' => $error ? 'failed' : 'success',
            'error' => $error,
        ]) . "\n");

        if ($error){
            Log::error('[PROCESS BACKUP DATA] failed: ' . $error);
        }else{
            Log::info('[PROCESS BACKUP DATA] ' . $sourceFile .' to '.$zipFile .' on '.$storagePath);
        }

        // retensi: harian 30 hari, backup tanggal 1 disimpan permanen
        foreach (File::files(storage_path('app/backup')) as $f) {
            $isMonthly = preg_match('/^backup-\d{4}-\d{2}-01-/', $f->getFilename());
            if (!$isMonthly && $f->getMTime() < Carbon::now()->subDays(30)->timestamp) {
                File::delete($f->getPathname());
            }
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
