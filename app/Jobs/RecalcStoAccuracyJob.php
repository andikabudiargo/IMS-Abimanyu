<?php

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

    public $timeout = 600;
    public $tries = 1;

    public function __construct(
        public array $mappingIds,
        public bool $refreshQty,
        public bool $includeFinished,
        public string $requestedBy,
        public string $jobToken
    ) {}

    public function handle(StoAccuracyRecalcService $service)
    {
        DB::table('sto_recalc_jobs')->where('job_token', $this->jobToken)->update([
            'status' => 'RUNNING', 'started_at' => now(),
        ]);

        try {
            $result = $service->recalcMappingIds(
                collect($this->mappingIds),
                $this->refreshQty,
                $this->includeFinished,
                'web:' . $this->requestedBy,
                'web-ui',
                function () {
                    DB::table('sto_recalc_jobs')->where('job_token', $this->jobToken)->increment('processed_mappings');
                }
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