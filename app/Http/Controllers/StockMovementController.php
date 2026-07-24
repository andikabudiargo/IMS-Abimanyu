<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use DataTables;
use DB;

class StockMovementController extends Controller
{
    private $title = 'Stock Movement';
    private $siteCode = 'HO';

    /** movement_type yang dianggap OUT (perspektif global) */
    private const TYPE_OUT = ['DELIVERY', 'REVISI DELIVERY', 'CANCEL DELIVERY', 'REPLACEMENT'];
    /** movement_type yang dianggap IN (perspektif global) */
    private const TYPE_IN  = ['RECEIVING', 'RETURN'];
    /** movement_type adjustment — arah ditentukan tanda qty, bukan jenis dokumen */
    private const TYPE_ADJ = ['ADJUSTMENT', 'CANCEL ADJUSTMENT'];
    /** movement_type yang murni perpindahan antar lokasi internal */
    private const TYPE_MOVE = ['TRANSFER', 'SUPPLY'];

    private const STATUS_MAP = [
        '1'  => ['label' => 'NEW',       'class' => 'badge-light-primary'],
        '2'  => ['label' => 'VALIDATED', 'class' => 'badge-light-info'],
        '3'  => ['label' => 'APPROVED',  'class' => 'badge-light-warning'],
        '4'  => ['label' => 'POSTED',    'class' => 'badge-light-success'],
        '5'  => ['label' => 'CANCELED',  'class' => 'badge-light-danger'],
        '7'  => ['label' => 'REVISED',   'class' => 'badge-light-warning'],
        '10' => ['label' => 'REVISED',   'class' => 'badge-light-warning'],
    ];

    /** label => class badge + icon feather */
    private const INOUT_BADGE = [
        'IN'         => ['badge-light-success',   'arrow-down-circle'],
        'OUT'        => ['badge-light-danger',    'arrow-up-circle'],
        'TRANSFER'   => ['badge-light-info',      'repeat'],
        'SUPPLY'     => ['badge-light-warning',   'send'],
        'ADJUSTMENT' => ['badge-light-secondary', 'sliders'],
        '-'          => ['badge-light-secondary', null],
    ];

    /** movement_type => [tabel, kolom nomor dokumen, nama route show] */
    private const REF_MAP = [
        'RECEIVING'         => ['receiving_hdr',        'rec_number',      'receiving.show'],
        'TRANSFER'          => ['transfer_stock_hdr',   'tr_number',       'transferStock.show'],
        'SUPPLY'            => ['transfer_stock_hdr',   'tr_number',       'transferStock.show'],
        'DELIVERY'          => ['delivery_hdr',         'delivery_number', 'delivery.show'],
        'REVISI DELIVERY'   => ['delivery_hdr',         'delivery_number', 'delivery.show'],
        'CANCEL DELIVERY'   => ['delivery_hdr',         'delivery_number', 'delivery.show'],
        'RETURN'            => ['dn_return_hdr',        'return_number',   'dnReturn.show'],
        'REPLACEMENT'       => ['dn_replace_hdr',       'replace_number',  'dnReplace.show'],
        'ADJUSTMENT'        => ['stock_adjustment_hdr', 'adj_code',        'stockAdjustment.show'],
        'CANCEL ADJUSTMENT' => ['stock_adjustment_hdr', 'adj_code',        'stockAdjustment.show'],
        'DN SEMENTARA'      => ['temporary_dn_hdr',     'tdn_number',      'suratJalanSementara.show'],
        'DN UMUM'           => ['dn_general_hdr',       'tdn_number',      'dnGeneral.show'],
    ];

    /* ================= View ================= */

    public function index(Request $request)
    {
        return view('warehouse.movement', [
            'title' => $this->title,
            'types' => DB::table('article_types')->where('status', 1)->orderBy('name')->get(),
            'supps' => DB::table('third_party')->orderBy('nama')->get(),
            'locs'  => DB::table('stock_location_master')->orderBy('location_name')->get(),
            'kolom' => $this->getTableColoumn(),
        ]);
    }

    public function getTableColoumn()
    {
        return json_encode([
            ['data' => 'movement_date',     'name' => 'movement_date',     'title' => 'Date'],
            ['data' => 'code',              'name' => 'code',              'title' => 'Article Code'],
            ['data' => 'artikel_desc',      'name' => 'artikel_desc',      'title' => 'Article'],
            ['data' => 'mv_from',           'name' => 'mv_from',           'title' => 'From'],
            ['data' => 'mv_to',             'name' => 'mv_to',             'title' => 'To'],
            ['data' => 'inout',             'name' => 'inout',             'title' => 'Transaction'],
            ['data' => 'movement_type',     'name' => 'movement_type',     'title' => 'Type'],
            ['data' => 'movement_transnno', 'name' => 'movement_transnno', 'title' => 'Ref'],
            ['data' => 'trx_status',        'name' => 'trx_status',        'title' => 'Status'],
            ['data' => 'qty_in',            'name' => 'qty_in',            'title' => 'Qty In'],
            ['data' => 'qty_out',           'name' => 'qty_out',           'title' => 'Qty Out'],
            ['data' => 'created_at',        'name' => 'created_at',        'title' => 'Created At'],
            ['data' => 'urutan',            'name' => 'urutan',            'title' => '#', 'searchable' => false, 'visible' => false],
        ], true);
    }

    /* ================= Query ================= */

    private function quote(array $values): string
    {
        return "'" . implode("','", $values) . "'";
    }

    /**
     * Bangun WHERE + bindings + konteks lokasi.
     * Return null jika tanggal belum dipilih (guard anti full-scan).
     */
    private function buildFilter(Request $request): ?array
    {
        if (!$request->fromDate || !$request->toDate) {
            return null;
        }

        $location = $request->location ?: null;

        $where = "WHERE m.site_code = ?
                  AND TO_DATE(m.movement_date,'dd-mm-yyyy')
                      BETWEEN TO_DATE(?, 'dd-mm-yyyy') AND TO_DATE(?, 'dd-mm-yyyy')";
        $bind = [$this->siteCode, $request->fromDate, $request->toDate];

       if ($location) {
    $where .= " AND m.location_number = ?";
    $bind[] = $location;
}

        if ($type = $request->type) {
            $where .= " AND a.article_type = ?";
            $bind[] = $type;
        }

        if ($supp = $request->supp) {
            $where .= " AND a.third_party = ?";
            $bind[] = $supp;
        }

        if ($article = strtolower(trim($request->article))) {
            $where .= " AND (lower(a.article_alternative_code) LIKE ?
                        OR lower(a.article_desc) LIKE ?
                        OR lower(m.artikel_code) LIKE ?)";
            $like = '%' . $article . '%';
            array_push($bind, $like, $like, $like);
        }

        [$clause, $clauseBind] = $this->inoutClause($request->inout, $location);
        $where .= $clause;
        $bind   = array_merge($bind, $clauseBind);

        return [
            'where'    => $where,
            'bindings' => $bind,
            'location' => $location,   // dipakai formatter untuk menentukan arah
        ];
    }

    /**
     * Filter transaksi.
     * - Tanpa lokasi: arah ditentukan jenis dokumen (perspektif global).
     * - Dengan lokasi: arah ditentukan movement_to/movement_from relatif lokasi itu.
     */
    private function inoutClause(?string $inout, ?string $location): array
    {
        if (!$inout) {
            return ['', []];
        }

        // ADJUSTMENT selalu berdiri sendiri, tidak terpengaruh konteks lokasi.
        if ($inout === 'adjustment') {
            return [" AND m.movement_type IN ({$this->quote(self::TYPE_ADJ)})", []];
        }

        /* ---- Mode lokasi: IN = barang masuk ke lokasi, OUT = keluar dari lokasi ---- */
       if ($location) {
    switch ($inout) {
        case 'in':
            return [' AND m.movement_plus > 0', []];
        case 'out':
            return [' AND m.movement_min > 0', []];
        case 'transfer':
            return [" AND m.movement_type = 'TRANSFER'", []];
        case 'supply':
            return [" AND m.movement_type = 'SUPPLY'", []];
        default:
            return ['', []];
    }
}

        /* ---- Mode global: arah dari jenis dokumen ---- */
        $excludeI = $this->quote(array_merge(self::TYPE_OUT, self::TYPE_ADJ, self::TYPE_MOVE));
        $excludeO = $this->quote(array_merge(self::TYPE_IN,  self::TYPE_ADJ, self::TYPE_MOVE));

        switch ($inout) {
            case 'in':
                return [" AND (m.movement_type IN ({$this->quote(self::TYPE_IN)})
                          OR (m.movement_type NOT IN ($excludeI) AND m.movement_plus > 0))", []];
            case 'out':
                return [" AND (m.movement_type IN ({$this->quote(self::TYPE_OUT)})
                          OR (m.movement_type NOT IN ($excludeO) AND m.movement_min > 0))", []];
            case 'transfer':
                return [" AND m.movement_type = 'TRANSFER'", []];
            case 'supply':
                return [" AND m.movement_type = 'SUPPLY'", []];
            default:
                return ['', []];
        }
    }

    private function baseSql(string $where, string $orderBy): string
{
    return "
        WITH filtered AS (
            SELECT
                m.movement_code,
                m.artikel_code,
                a.article_alternative_code AS code,
                m.artikel_desc,
                a.article_type,
                m.movement_plus - m.movement_min AS qty,
                m.movement_price,
                m.movement_date,
                m.movement_desc,
                m.movement_type,
                m.movement_min,
                m.movement_plus,
                m.movement_transnno,
                m.partner_type,
                m.location_number,
                m.movement_from,
                m.movement_to,
                m.site_code,
                m.created_at,
                CASE
                    WHEN m.movement_type IN ('DELIVERY','REVISI DELIVERY')
                    THEN ROW_NUMBER() OVER (
                        PARTITION BY m.movement_transnno, m.artikel_code
                        ORDER BY m.created_at DESC, m.movement_code DESC
                    )
                    ELSE 1
                END AS rn
            FROM warehouse_movement m
            LEFT JOIN article a
                ON a.article_code = m.artikel_code
            $where
            AND m.movement_type NOT LIKE 'CANCEL %'
        )
        SELECT
            f.movement_code,
            f.artikel_code,
            f.code,
            f.artikel_desc,
            f.article_type,
            f.qty,
            f.movement_price,
            f.movement_date,
            f.movement_desc,
            f.movement_type,
            f.movement_min,
            f.movement_plus,
            f.movement_transnno,
            f.partner_type,
            f.location_number,

            CASE WHEN f.partner_type = 'SUPP'
                THEN (SELECT nama FROM third_party WHERE kode = f.movement_from)
                ELSE (SELECT location_name FROM stock_location_master WHERE location_code = f.movement_from)
            END AS mv_from,

            CASE WHEN f.partner_type = 'CUST'
                THEN (SELECT nama FROM third_party WHERE kode = f.movement_to)
                ELSE (SELECT location_name FROM stock_location_master WHERE location_code = f.movement_to)
            END AS mv_to,

            COALESCE(rec.status, trf.status, del.status, ret.status,
                     rep.status, adj.status, tdn.status, dng.status) AS trx_status,

            f.site_code,
            f.created_at

        FROM filtered f
        LEFT JOIN receiving_hdr rec
            ON rec.rec_number = f.movement_transnno AND f.movement_type = 'RECEIVING'
        LEFT JOIN transfer_stock_hdr trf
            ON trf.tr_number = f.movement_transnno AND f.movement_type IN ('TRANSFER','SUPPLY')
        LEFT JOIN delivery_hdr del
            ON del.delivery_number = f.movement_transnno AND f.movement_type IN ('DELIVERY','REVISI DELIVERY','CANCEL DELIVERY')
        LEFT JOIN dn_return_hdr ret
            ON ret.return_number = f.movement_transnno AND f.movement_type = 'RETURN'
        LEFT JOIN dn_replace_hdr rep
            ON rep.replace_number = f.movement_transnno AND f.movement_type = 'REPLACEMENT'
        LEFT JOIN stock_adjustment_hdr adj
            ON adj.adj_code = f.movement_transnno AND f.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
        LEFT JOIN temporary_dn_hdr tdn
            ON tdn.tdn_number = f.movement_transnno AND f.movement_type = 'DN SEMENTARA'
        LEFT JOIN dn_general_hdr dng
            ON dng.tdn_number = f.movement_transnno AND f.movement_type = 'DN UMUM'
        WHERE f.rn = 1
        $orderBy
    ";
}

    private function fetch(array $filter, string $orderBy): array
    {
        return DB::select($this->baseSql($filter['where'], $orderBy), $filter['bindings']);
    }

    /* ================= Formatter ================= */

    /**
     * Tentukan arah transaksi.
     * $location != null -> perspektif lokasi (masuk/keluar gudang tsb).
     * $location == null -> perspektif global (jenis dokumen).
     */
    private function labelInout($row, ?string $location = null): string
{
    $type = strtoupper($row->movement_type);

    if (in_array($type, self::TYPE_ADJ, true)) return 'ADJUSTMENT';

    // Perspektif lokasi: location_number sudah pasti milik baris ini,
    // jadi arah cukup dilihat dari tanda qty.
    if ($location) {
        if ($row->movement_plus > 0) return 'IN';
        if ($row->movement_min > 0)  return 'OUT';
        return '-';
    }

    if (in_array($type, self::TYPE_OUT, true))  return 'OUT';
    if (in_array($type, self::TYPE_IN, true))   return 'IN';
    if (in_array($type, self::TYPE_MOVE, true)) return $type;
    if ($row->movement_plus > 0) return 'IN';
    if ($row->movement_min > 0)  return 'OUT';

    return '-';
}

    /** Pecah qty jadi in/out. Salah satu selalu 0.00. */
    private function splitQty($row, ?string $location = null): array
    {
        $qty   = (float) $row->qty;
        $label = $this->labelInout($row, $location);

        if ($label === 'IN')  return ['in' => abs($qty), 'out' => 0.0];
        if ($label === 'OUT') return ['in' => 0.0, 'out' => abs($qty)];

        // TRANSFER / SUPPLY / ADJUSTMENT / lainnya -> ikut tanda qty
        return $qty >= 0
            ? ['in' => $qty, 'out' => 0.0]
            : ['in' => 0.0, 'out' => abs($qty)];
    }

    private function labelStatus($status): string
    {
        if ($status === null || $status === '') return '-';
        return self::STATUS_MAP[$status]['label'] ?? strtoupper($status);
    }

    private function badge(string $label, string $class, ?string $icon = null): string
    {
        $i = $icon ? "<i data-feather='$icon' class='font-small-3'></i> " : '';
        return "<span class='badge badge-pill $class'>$i$label</span>";
    }

    /** Resolve link dokumen. Return null jika tidak ada / status CANCELED. */
    private function refLink($row): ?string
    {
        $ref = $row->movement_transnno;
        $cfg = self::REF_MAP[$row->movement_type] ?? null;
        if (!$ref || !$cfg) return null;

        [$table, $column, $route] = $cfg;

        $doc = DB::table($table)->where($column, $ref)->select('id', 'status')->first();
        if (!$doc || (string) $doc->status === '5') return null; // 5 = CANCELED

        return route($route, ['id' => Crypt::encryptString($doc->id)]);
    }

    /* ================= DataTable ================= */

    public function list(Request $request)
    {
        $filter = $this->buildFilter($request);
        if (!$filter) {
            return Datatables::of(collect([]))->make(true);
        }

        $loc  = $filter['location'];
        $rows = $this->fetch(
            $filter,
           "ORDER BY TO_DATE(f.movement_date,'dd-mm-yyyy') DESC, f.movement_code DESC"
        );

        $n = count($rows);
        foreach ($rows as $row) {
            $row->urutan = $n--;
        }

        return Datatables::of($rows)
           ->addColumn('qty_in', function ($row) use ($loc) {
    $val = number_format($this->splitQty($row, $loc)['in'], 2);
    $class = $val !== '0.00' ? 'text-hijau' : '';
    return "<div class='text-right {$class}'>$val</div>";
})
->addColumn('qty_out', function ($row) use ($loc) {
    $val = number_format($this->splitQty($row, $loc)['out'], 2);
    $class = $val !== '0.00' ? 'text-red' : '';
    return "<div class='text-right {$class}'>$val</div>";
})
            ->addColumn('inout', function ($row) use ($loc) {
                $label = $this->labelInout($row, $loc);
                [$class, $icon] = self::INOUT_BADGE[$label] ?? self::INOUT_BADGE['-'];
                return $this->badge($label, $class, $icon);
            })
            ->addColumn('movement_transnno', function ($row) {
                $ref = $row->movement_transnno;
                if (!$ref) return '-';

                $url = $this->refLink($row);
                return $url
                    ? '<a href="' . $url . '" target="_blank" class="text-primary">' . $ref . '</a>'
                    : '<span class="text-muted" title="Transaksi cancel / tidak bisa dibuka">' . $ref . '</span>';
            })
            ->addColumn('trx_status', function ($row) {
                $st = $row->trx_status;
                if ($st === null || $st === '') return $this->badge('-', 'badge-light-secondary');

                $cfg = self::STATUS_MAP[$st] ?? ['label' => strtoupper($st), 'class' => 'badge-light-secondary'];
                return $this->badge($cfg['label'], $cfg['class']);
            })
            ->rawColumns(['qty_in', 'qty_out', 'inout', 'movement_transnno', 'trx_status'])
            ->make(true);
    }

   

    /* ================= Export ================= */

    public function export(Request $request)
    {
        $filter = $this->buildFilter($request);
        if (!$filter) {
            return back()->with('error', 'Range Date wajib dipilih.');
        }

        return $request->mode === 'grouped'
            ? $this->exportGrouped($request, $filter)
            : $this->exportDetail($request, $filter);
    }

    /** Baris judul + periode + konteks lokasi. Return baris header kolom. */
    private function writeTitle($sheet, Request $request, array $filter, string $title, string $last): int
    {
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells("A1:{$last}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $info = 'Periode: ' . $request->fromDate . ' s/d ' . $request->toDate;
        if ($filter['location']) {
            $name = DB::table('stock_location_master')
                ->where('location_code', $filter['location'])
                ->value('location_name');
            $info .= '  |  Lokasi: ' . ($name ?: $filter['location'])
                   . '  (In/Out relatif terhadap lokasi ini)';
        }

        $sheet->setCellValue('A2', $info);
        $sheet->mergeCells("A2:{$last}2");

        return 4;
    }

    private function styleHeader($sheet, int $row, string $last, string $rgb = '4472C4'): void
    {
        $sheet->getStyle("A{$row}:{$last}{$row}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$row}:{$last}{$row}")->getFill()
              ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
    }

    private function autoSize($sheet, string $last): void
    {
        foreach (range('A', $last) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function streamXlsx(Spreadsheet $spreadsheet, string $prefix)
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $prefix . '_' . date('YmdHis') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

     private function applyQtyColor($sheet, string $cell, float $value, string $positiveRgb): void
{
    if ($value > 0) {
        $sheet->getStyle($cell)->getFont()->getColor()->setRGB($positiveRgb);
    }
    // 0.00 -> biarkan default (hitam), tidak perlu set warna
}

    /** Flat, kontinu, autofilter — urut tanggal terbaru dulu seperti tampilan layar. */
    private function exportDetail(Request $request, array $filter)
    {
        $loc  = $filter['location'];
        $rows = $this->fetch(
            $filter,
            "ORDER BY TO_DATE(f.movement_date,'dd-mm-yyyy') ASC, f.movement_code ASC"
        );

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail');

        $headers = ['Date', 'Article Code', 'Article Name', 'From', 'To',
                    'Transaction', 'Type', 'Ref', 'Status', 'Qty In', 'Qty Out', 'Created At'];
        $last = 'L';

        $headerRow = $this->writeTitle($sheet, $request, $filter, 'STOCK MOVEMENT — DETAIL REPORT', $last);
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $this->styleHeader($sheet, $headerRow, $last);

        $r        = $headerRow + 1;
        $firstRow = $r;

        foreach ($rows as $row) {
            $q = $this->splitQty($row, $loc);

            $sheet->setCellValue("A{$r}", $row->movement_date);
            $sheet->setCellValueExplicit("B{$r}", (string) $row->code, DataType::TYPE_STRING);
            $sheet->setCellValue("C{$r}", $row->artikel_desc);
            $sheet->setCellValue("D{$r}", $row->mv_from);
            $sheet->setCellValue("E{$r}", $row->mv_to);
            $sheet->setCellValue("F{$r}", $this->labelInout($row, $loc));
            $sheet->setCellValue("G{$r}", $row->movement_type);
            $sheet->setCellValueExplicit("H{$r}", (string) $row->movement_transnno, DataType::TYPE_STRING);
            $sheet->setCellValue("I{$r}", $this->labelStatus($row->trx_status));
           $sheet->setCellValue("J{$r}", round($q['in'], 2));
$sheet->setCellValue("K{$r}", round($q['out'], 2));
$this->applyQtyColor($sheet, "J{$r}", $q['in'],  '00B050'); // hijau
$this->applyQtyColor($sheet, "K{$r}", $q['out'], 'C00000'); // merah
            $sheet->setCellValue("L{$r}", $row->created_at);
            $r++;
        }

        $lastDataRow = $r - 1;

        if ($lastDataRow < $firstRow) {
            $sheet->setCellValue("A{$firstRow}", 'Tidak ada data untuk filter yang dipilih.');
        } else {
            $sheet->getStyle("J{$firstRow}:K{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setAutoFilter("A{$headerRow}:{$last}{$lastDataRow}");
            $sheet->freezePane('A' . ($headerRow + 1));
        }

        $this->autoSize($sheet, $last);

        return $this->streamXlsx($spreadsheet, 'stock_movement_detail');
    }

    /** Header article + detail + SUB TOTAL per article. Tanpa grand total. */
    private function exportGrouped(Request $request, array $filter)
{
    $loc  = $filter['location'];
    $rows = $this->fetch(
        $filter,
        "ORDER BY f.code ASC, TO_DATE(f.movement_date,'dd-mm-yyyy') ASC, f.movement_code ASC"
    );

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Summary');

    $headers = ['Date', 'From', 'To', 'Transaction', 'Type', 'Ref', 'Status', 'Qty In', 'Qty Out', 'Balance', 'Created At'];
    $last = 'K';

    $r = $this->writeTitle($sheet, $request, $filter, 'STOCK MOVEMENT — SUMMARY PER ARTICLE', $last);

    $current = null;
    $subIn   = 0.0;
    $subOut  = 0.0;
    $balance = 0.0;

    $writeClosing = function () use ($sheet, &$r, &$balance, $last) {
        $sheet->setCellValue("G{$r}", 'SALDO AKHIR');
        $sheet->setCellValue("J{$r}", round($balance, 2));
        $sheet->getStyle("A{$r}:{$last}{$r}")->getFont()->setBold(true)->setItalic(true);
        $sheet->getStyle("J{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
        $r++;
    };

    $writeSubtotal = function () use ($sheet, &$r, &$subIn, &$subOut, $last) {
        $sheet->setCellValue("G{$r}", 'SUB TOTAL');
        $sheet->setCellValue("H{$r}", round($subIn, 2));
        $sheet->setCellValue("I{$r}", round($subOut, 2));
        $sheet->getStyle("A{$r}:{$last}{$r}")->getFont()->setBold(true);
        $sheet->getStyle("H{$r}:I{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("A{$r}:{$last}{$r}")->getFill()
              ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');
        $r += 2;
        $subIn = $subOut = 0.0;
    };

    foreach ($rows as $row) {
        if ($current !== $row->artikel_code) {
            if ($current !== null) {
                $writeClosing();
                $writeSubtotal();
            }

            // Header article
            $sheet->setCellValue("A{$r}", trim($row->code . ' — ' . $row->artikel_desc));
            $sheet->mergeCells("A{$r}:{$last}{$r}");
            $this->styleHeader($sheet, $r, $last);
            $r++;

            // Header kolom
            $sheet->fromArray($headers, null, "A{$r}");
            $sheet->getStyle("A{$r}:{$last}{$r}")->getFont()->setBold(true);
            $sheet->getStyle("A{$r}:{$last}{$r}")->getFill()
                  ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
            $r++;

            // Saldo awal
            $opening = $this->resolveOpeningBalance($row->artikel_code, $loc, $request->fromDate);
            $balance = $opening['qty'];

            $sheet->setCellValue("G{$r}", 'SALDO AWAL');
            $sheet->setCellValue("J{$r}", round($balance, 2));
            $sheet->getStyle("A{$r}:{$last}{$r}")->getFont()->setItalic(true);
            $sheet->getStyle("J{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
            $r++;

            $current = $row->artikel_code;
        }

        $q = $this->splitQty($row, $loc);
        $balance += $q['in'] - $q['out'];

        $sheet->setCellValue("A{$r}", $row->movement_date);
        $sheet->setCellValue("B{$r}", $row->mv_from);
        $sheet->setCellValue("C{$r}", $row->mv_to);
        $sheet->setCellValue("D{$r}", $this->labelInout($row, $loc));
        $sheet->setCellValue("E{$r}", $row->movement_type);
        $sheet->setCellValueExplicit("F{$r}", (string) $row->movement_transnno, DataType::TYPE_STRING);
        $sheet->setCellValue("G{$r}", $this->labelStatus($row->trx_status));
        $sheet->setCellValue("H{$r}", round($q['in'], 2));
        $sheet->setCellValue("I{$r}", round($q['out'], 2));
        $sheet->setCellValue("J{$r}", round($balance, 2));
        $this->applyQtyColor($sheet, "H{$r}", $q['in'],  '00B050');
        $this->applyQtyColor($sheet, "I{$r}", $q['out'], 'C00000');
        $sheet->setCellValue("K{$r}", $row->created_at);

        $sheet->getStyle("H{$r}:J{$r}")->getNumberFormat()->setFormatCode('#,##0.00');

        $subIn  += $q['in'];
        $subOut += $q['out'];
        $r++;
    }

    if ($current !== null) {
        $writeClosing();
        $writeSubtotal();
    } else {
        $sheet->setCellValue("A{$r}", 'Tidak ada data untuk filter yang dipilih.');
    }

    $this->autoSize($sheet, $last);

    return $this->streamXlsx($spreadsheet, 'stock_movement_summary');
}

    private function resolveOpeningBalance(string $articleCode, ?string $location, string $fromDate): array
{
    $parts = explode('-', $fromDate);
    $bulan = isset($parts[1]) ? (int) $parts[1] : (int) date('m');
    $tahun = isset($parts[2]) ? (int) $parts[2] : (int) date('Y');

    $periode = $bulan - 1;
    $tahunOpening = $tahun;
    if ($periode < 1) { $periode = 12; $tahunOpening = $tahun - 1; }

    $out = ['qty' => 0.0];

    if (!$location) {
        $sql = "SELECT COALESCE(SUM(det.stock_after),0) AS saldo_awal
                FROM stock_adjustment_hdr hdr
                JOIN stock_adjustment_det det ON det.adj_code = hdr.adj_code
                WHERE hdr.adj_type = 'OPENING BALANCE'
                  AND hdr.status != '5'
                  AND hdr.periode = :periode
                  AND EXTRACT(YEAR FROM TO_DATE(hdr.adj_date,'dd-mm-yyyy')) = :tahun
                  AND det.article_code = :art";
        $r = DB::select($sql, ['periode' => $periode, 'tahun' => $tahunOpening, 'art' => $articleCode]);
        $out['qty'] = isset($r[0]) ? (float) $r[0]->saldo_awal : 0.0;
        return $out;
    }

    $sql = "SELECT det.stock_after AS saldo_awal
            FROM stock_adjustment_hdr hdr
            JOIN stock_adjustment_det det ON det.adj_code = hdr.adj_code
            WHERE hdr.adj_type = 'OPENING BALANCE'
              AND hdr.status != '5'
              AND hdr.periode = :periode
              AND EXTRACT(YEAR FROM TO_DATE(hdr.adj_date,'dd-mm-yyyy')) = :tahun
              AND det.article_code = :art
              AND hdr.location_code = :loc
            LIMIT 1";
    $r = DB::select($sql, ['periode' => $periode, 'tahun' => $tahunOpening, 'art' => $articleCode, 'loc' => $location]);
    $out['qty'] = isset($r[0]) ? (float) $r[0]->saldo_awal : 0.0;

    return $out;
}

}