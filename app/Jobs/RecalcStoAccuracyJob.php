<?php
// app/Jobs/RecalcStoAccuracyJob.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\StoAccuracyRecalcService;
use DB;

class RecalcStoAccuracyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 menit, sesuaikan skala data
    public $tries = 1;     // jangan retry otomatis — ini operasi yang mengubah data + log

    public function __construct(
        public int $configId,
        public bool $refreshQty,
        public bool $includeFinished,
        public string $requestedBy,   // username, bukan Auth::id() (job jalan di worker, beda proses)
        public string $jobToken       // uuid unik, dipakai untuk polling status
    ) {}

    public function handle(StoAccuracyRecalcService $service)
    {
        DB::table('sto_recalc_jobs')->where('job_token', $this->jobToken)->update([
            'status' => 'RUNNING', 'started_at' => now(),
        ]);

        try {
            $mappingIds = $service->resolveMappingIdsForConfig($this->configId);

            $result = $service->recalcMappingIds(
                $mappingIds,
                $this->refreshQty,
                $this->includeFinished,
                'web:' . $this->requestedBy,
                'web-ui'
            );

            DB::table('sto_recalc_jobs')->where('job_token', $this->jobToken)->update([
                'status'        => 'DONE',
                'finished_at'   => now(),
                'total_checked' => $result['total_checked'],
                'total_changed' => $result['total_changed'],
            ]);
        } catch (\Throwable $e) {
            DB::table('sto_recalc_jobs')->where('job_token', $this->jobToken)->update([
                'status' => 'FAILED', 'finished_at' => now(), 'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}