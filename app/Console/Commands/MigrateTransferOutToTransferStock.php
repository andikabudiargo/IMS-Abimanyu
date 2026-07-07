<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TransferStockController;
use App\Helpers\AppHelpers;

class MigrateTransferOutToTransferStock extends Command
{
    protected $signature = 'transfer-out:migrate
                            {--from=01-07-2026 : Tanggal awal migrasi}
                            {--to= : Tanggal akhir migrasi (default hari ini)}
                            {--dry-run : Hanya tampilkan data tanpa eksekusi}';

    protected $description = 'Migrasi Transfer Out menjadi Transfer Stock dan langsung Posting';

    private function mapLocation($goodsLocationCode)
{
    $goods = DB::table('goods_location_master')
        ->where('location_code', $goodsLocationCode)
        ->first();

    if (!$goods) {
        throw new \Exception("Goods Location {$goodsLocationCode} tidak ditemukan.");
    }

    switch (strtolower(trim($goods->location_name))) {

        case 'spraybooth 1a':
            return '022';

        case 'spraybooth 1b':
            return '023';

        case 'spraybooth 1c':
            return '024';

        case 'spraybooth 2a':
            return '025';

        case 'spraybooth 2b':
            return '026';

        case 'spraybooth 2c':
            return '027';

        case 'spraybooth 3a':
            return '028';

        case 'spraybooth 3b':
            return '029';

        case 'spraybooth 3c':
            return '030';

        case 'spraybooth 4a':
            return '031';

        case 'spraybooth 4b':
            return '032';

        case 'spraybooth 4c':
            return '033';

        case 'spraybooth 5a':
            return '034';

        case 'spraybooth 5b':
            return '035';

        case 'spraybooth 5c':
            return '036';

        case 'sanding':
            return '039';

        case 'buffing plant 1':
        case 'buffing plant 2':
            return '038';

        case 'touch up plant 1':
        case 'touch up plant 2':
            return '040';

        case 'stripping':
            return '041';

        case 'return ng':
            return '037';
    }

    if (empty($goods->location_stock)) {
    throw new \Exception(
        "Goods Location '{$goods->location_name}' belum memiliki mapping."
    );
}

return $goods->location_stock;
}

    public function handle()
    {
       $from = \Carbon\Carbon::createFromFormat('d-m-Y', $this->option('from'))
            ->format('Y-m-d');

$to = $this->option('to')
    ? \Carbon\Carbon::createFromFormat('d-m-Y', $this->option('to'))->format('Y-m-d')
    : now()->format('Y-m-d');
        $dryRun = $this->option('dry-run');

        $this->info("Periode : {$from} s/d {$to}");

        /*
        |--------------------------------------------------------------------------
        | Ambil Transfer Out yang belum pernah dimigrasi
        |--------------------------------------------------------------------------
        */
       $transferOuts = DB::table('transfer_hdr as h')
    ->where('h.tr_type', 'TROUT')
    ->whereBetween(
        DB::raw("to_date(h.tr_date, 'DD-MM-YYYY')"),
        [$from, $to]
    )
    ->whereNotExists(function ($q) {
        $q->select(DB::raw(1))
          ->from('transfer_stock_hdr as s')
          ->whereColumn('s.ref_number', 'h.tr_number');
    })
    ->orderByRaw("to_date(h.tr_date,'DD-MM-YYYY')")
    ->orderBy('h.tr_number')
    ->get();

        if ($transferOuts->isEmpty()) {
            $this->info('Tidak ada data yang perlu dimigrasi.');
            return 0;
        }

        $this->info("Ditemukan {$transferOuts->count()} Transfer Out.");

        $this->table(
            [
                'Transfer Out',
                'Tanggal',
                'Location',
                'Status'
            ],
            $transferOuts->map(function ($r) {
                return [
                    $r->tr_number,
                    $r->tr_date,
                    $r->location_code,
                    $r->status
                ];
            })
        );

        if ($dryRun) {
            $this->warn('DRY RUN - tidak ada data yang diproses.');
            return 0;
        }

        if (!$this->confirm('Lanjutkan migrasi ?')) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Reflection processPosting()
        |--------------------------------------------------------------------------
        */
        $controller = app(TransferStockController::class);

        $reflection = new \ReflectionClass($controller);

        $processPosting = $reflection->getMethod('processPosting');
        $processPosting->setAccessible(true);

        $getLastCode = $reflection->getMethod('getLastCode');
        $getLastCode->setAccessible(true);

        $progress = $this->output->createProgressBar($transferOuts->count());

        $progress->start();

        $success = 0;
        $failed  = [];

        foreach ($transferOuts as $hdr) {

            DB::beginTransaction();

            try {

                /*
                ---------------------------------------------------------
                Ambil Detail Transfer Out
                ---------------------------------------------------------
                */

                $details = DB::table('transfer_det')
                    ->where('tr_number', $hdr->tr_number)
                    ->get();

                if ($details->isEmpty()) {
                    throw new \Exception("Detail kosong");
                }

                /*
                ---------------------------------------------------------
                Tentukan Location Tujuan
                (diasumsikan seluruh detail tujuan sama)
                ---------------------------------------------------------
                */

                $locationFrom = $this->mapLocation($hdr->location_code);

$locationTo = $this->mapLocation(
    $details->first()->location_to
);

                /*
                ---------------------------------------------------------
                Cari Dept Approver
                ---------------------------------------------------------
                */

               $approveDept = DB::table('stock_location_master')
    ->where('location_code', $locationTo)
    ->value('dept_code');



                /*
                ---------------------------------------------------------
                Cari Type Transfer
                ---------------------------------------------------------
                */

              $locationType = DB::table('stock_location_master')
    ->where('location_code', $locationTo)
    ->value('location_type');

                $trType = $locationType == 'booth'
                    ? 'SUPPLY'
                    : 'TRANSFER';

                /*
                ---------------------------------------------------------
                Generate Nomor TRF
                ---------------------------------------------------------
                */

             AppHelpers::resetCode('TRF');

$newTrNumber = $getLastCode->invoke(
    $controller,
    'TRF',
    $hdr->tr_date
);

                                /*
                ---------------------------------------------------------
                Insert Header Transfer Stock
                ---------------------------------------------------------
                */

               $note = trim(
    "Migrasi dari Transfer Out : {$hdr->tr_number}" .
    (!empty($hdr->note) ? PHP_EOL . $hdr->note : '')
);

DB::table('transfer_stock_hdr')->insert([

    'tr_number'     => $newTrNumber,
    'ref_number'    => $hdr->tr_number,
    'tr_date'       => $hdr->tr_date,
    'status'        => 1,
    'penerima'      => '',
    'note'          => $note,
    'tr_type'       => $trType,
   'location_from' => $locationFrom,
'location_to'   => $locationTo,
    'approve_dept'  => $approveDept,

    'created_by'    => 'system-migration',
    'updated_by'    => 'system-migration',

    'created_at' => $hdr->created_at,
    'updated_at'    => now(),

]);

                /*
                ---------------------------------------------------------
                Siapkan Detail
                ---------------------------------------------------------
                */

                $insertDetail = [];

                foreach ($details as $det) {

                    $insertDetail[] = [

                        'tr_number'    => $newTrNumber,

                        'article_code' => $det->article_code,

                        'qty'          => $det->qty,

                        'uom'          => $det->uom,

                        'note'         => $det->note,

                        'created_by'   => 'system-migration',

                        'updated_by'   => 'system-migration',

                        'created_at'   => $det->created_at,

                        'updated_at'   => now(),

                    ];

                }

                DB::table('transfer_stock_det')->insert($insertDetail);

                /*
                ---------------------------------------------------------
                Posting
                ---------------------------------------------------------
                */

                $result = $processPosting->invoke(
                    $controller,
                    $newTrNumber,
                    'system-migration'
                );

                if (!$result['success']) {

                    DB::rollBack();

                    $failed[] =
                        $hdr->tr_number .
                        ' => ' .
                        $newTrNumber .
                        ' : ' .
                        implode(', ', (array)$result['message']);

                    $progress->advance();

                    continue;

                }

                                /*
                ---------------------------------------------------------
                Posting berhasil
                ---------------------------------------------------------
                */

                DB::commit();

                $success++;

            } catch (\Exception $e) {

                DB::rollBack();

                $failed[] =
                    $hdr->tr_number .
                    ' : ' .
                    $e->getMessage();

            }

            $progress->advance();

        }

       $progress->finish();

$this->line('');
$this->line('');

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

$this->info("=========================================");
$this->info("MIGRASI SELESAI");
$this->info("=========================================");
$this->info("Berhasil : {$success}");
$this->info("Gagal    : ".count($failed));

if(count($failed) > 0){

    $this->line('');

    $this->warn("Daftar gagal :");

    foreach($failed as $row){

        $this->line(" - ".$row);

    }

}

        return 0;

    }

}