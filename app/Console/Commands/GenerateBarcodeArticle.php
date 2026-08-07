<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

class GenerateBarcodeArticle extends Command
{
    protected $signature = 'barcode:generate
                            {--type= : Filter by article_type, e.g. CM1}
                            {--force : Overwrite barcode yang sudah ada}';

    protected $description = 'Generate barcode PNG untuk artikel di database';

    public function handle()
    {
        $type  = $this->option('type');
        $force = $this->option('force');

        $query = DB::table('article')
            ->select('article_code', 'article_alternative_code', 'article_desc', 'barcode_path')
            ->whereNotNull('article_alternative_code')
            ->where('article_alternative_code', '!=', '');

        if ($type) {
            $query->where('article_type', $type);
        }

        if (!$force) {
            // Skip yang sudah punya barcode
            $query->whereNull('barcode_path');
        }

        $articles = $query->get();

        if ($articles->isEmpty()) {
            $this->info('Tidak ada artikel yang perlu di-generate.');
            return;
        }

        $this->info("Ditemukan {$articles->count()} artikel. Mulai generate...");

        $generator = new BarcodeGeneratorPNG();
        $success   = 0;
        $failed    = [];

        $bar = $this->output->createProgressBar($articles->count());
        $bar->start();

        foreach ($articles as $article) {
            try {
                $barcodeValue = trim($article->article_alternative_code);

                if (empty($barcodeValue)) {
                    $failed[] = "{$article->article_code} — alternative code kosong";
                    $bar->advance();
                    continue;
                }

                $imageData = $generator->getBarcode(
                    $barcodeValue,
                    $generator::TYPE_CODE_128,
                    2,   // width factor
                    60   // height px
                );

                $dir      = 'barcodes';
                $filename = $dir . '/' . $article->article_code . '.png';

                Storage::disk('public')->makeDirectory($dir);
                Storage::disk('public')->put($filename, $imageData);

                DB::table('article')
                    ->where('article_code', $article->article_code)
                    ->update(['barcode_path' => $filename]);

                $success++;
            } catch (\Exception $e) {
                $failed[] = "{$article->article_code} — " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Berhasil: {$success} artikel.");

        if (!empty($failed)) {
            $this->warn("❌ Gagal: " . count($failed) . " artikel:");
            foreach ($failed as $f) {
                $this->line("   - {$f}");
            }
        }
    }
}