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
        $logPath = storage_path('app/backup_log.jsonl');
        $logs = File::exists($logPath)
            ? array_filter(array_map('json_decode', file($logPath, FILE_IGNORE_NEW_LINES)))
            : [];

        $rows = [];
        // file yang ada di disk; backup lama (sebelum ada log) pakai waktu file
        foreach (File::files(storage_path('app/backup')) as $f) {
            $isMonthly = (bool) preg_match('/^backup-\d{4}-\d{2}-01-/', $f->getFilename());
            // zip dari dump kosong cuma ~150 byte; versi lama command tidak cek exit code pg_dump
            $isEmpty = $f->getSize() < 1024;
            // backup lama tanpa log: anggap mulai 02:00 sesuai jadwal di Kernel
            $assumedStart = strtotime(date('Y-m-d 02:00:00', $f->getMTime()));
            $hasAssumedStart = $f->getMTime() >= $assumedStart;
            $rows[$f->getFilename()] = (object) [
                'file' => $f->getFilename(),
                'started_at' => date('Y-m-d H:i:s', $hasAssumedStart ? $assumedStart : $f->getMTime()),
                'duration' => $hasAssumedStart ? $f->getMTime() - $assumedStart : null,
                'status' => $isEmpty ? 'failed' : 'success',
                'error' => $isEmpty ? 'File backup kosong, pg_dump kemungkinan gagal' : null,
                'size' => $f->getSize(),
                'monthly' => $isMonthly,
                // sama dengan retensi di DatabaseBackUp: mtime + 30 hari
                'expires_at' => $isMonthly ? null : $f->getMTime() + 30 * 86400,
            ];
        }
        foreach ($logs as $log) {
            if (isset($rows[$log->file])) {
                $rows[$log->file]->started_at = $log->started_at;
                $rows[$log->file]->duration = $log->duration;
            } elseif ($log->status === 'failed' && strtotime($log->started_at) > strtotime('-30 days')) {
                $rows[$log->file . '#' . $log->started_at] = (object) [
                    'file' => $log->file, 'started_at' => $log->started_at, 'duration' => $log->duration,
                    'status' => 'failed', 'error' => $log->error, 'size' => null,
                    'monthly' => false, 'expires_at' => null,
                ];
            }
        }
        usort($rows, function ($a, $b) { return strcmp($b->started_at, $a->started_at); });

        $data['rows'] = $rows;

        return view('files.index',$data);

    }

    public function download(Request $request){
        $filename = basename((string) $request->file);
        $filePath = storage_path() . "/app/backup/".$filename;
        return response()->download($filePath);
    }

}
