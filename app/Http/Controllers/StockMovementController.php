<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use DataTables;
use DB;

class StockMovementController extends Controller
{
    private $title;
    private $decimalPlaces;

    public function __construct()
    {
        $this->title = "Stock Movement";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumn()
    {
        $kolom =
        [
            ['data'=>'movement_date','name'=>'movement_date','title'=>'Date'],
            ['data'=>'code','name'=>'code','title'=>'Article Code'],
            ['data'=>'artikel_desc','name'=>'artikel_desc','title'=>'Article'],
            ['data'=>'mv_from','name'=>'mv_from','title'=>'From'],
            ['data'=>'mv_to','name'=>'mv_to','title'=>'To'],
            ['data'=>'inout','name'=>'inout','title'=>'Transaction'],
            ['data'=>'movement_type','name'=>'movement_type','title'=>'Type'],
            ['data'=>'movement_transnno','name'=>'movement_transnno','title'=>'Ref'],
            ['data'=>'trx_status','name'=>'trx_status','title'=>'Status'],
            ['data'=>'qty','name'=>'qty','title'=>'QTY'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Posted At'],
            ['data'=>'urutan','name'=>'urutan','title'=>'#','searchable'=>false,'visible'=>false],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['types'] = DB::table('article_types')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $data['supps'] = DB::table('third_party')
            ->orderBy('nama')
            ->get();

        $data['locs'] = DB::table('stock_location_master')
            ->orderBy('location_name')
            ->get();

        $data['kolom'] = $this->getTableColoumn();

        return view('warehouse.movement', $data);
    }

    public function list(Request $request)
    {
        $article  = strtolower(trim($request->article));   // cari by code / alt code / nama
        $type     = $request->type;                        // article_type
        $supp     = $request->supp;                        // third_party (supplier/customer)
        $location = $request->location;                    // location_code
        $fromDate = $request->fromDate;                    // dd-mm-yyyy
        $toDate   = $request->toDate;                      // dd-mm-yyyy
        $inout    = $request->inout;                       // '', in, out, transfer, supply
        $siteCode = 'HO';

        /*
         | GUARD:
         | Tanggal WAJIB dipilih. Kalau kosong, jangan query apa pun supaya
         | tidak menarik seluruh isi warehouse_movement (mencegah server exhaust).
        */
        if (!$fromDate || !$toDate) {
            return Datatables::of(collect([]))->make(true);
        }

        $where = "WHERE m.site_code = ?
                  AND TO_DATE(m.movement_date,'dd-mm-yyyy')
                      BETWEEN TO_DATE(?, 'dd-mm-yyyy') AND TO_DATE(?, 'dd-mm-yyyy')";
        $bindings = [$siteCode, $fromDate, $toDate];

        if ($location) {
            $where .= " AND (m.movement_from = ? OR m.movement_to = ? OR m.location_number = ?)";
            $bindings[] = $location;
            $bindings[] = $location;
            $bindings[] = $location;
        }

        if ($type) {
            $where .= " AND a.article_type = ?";
            $bindings[] = $type;
        }

        if ($supp) {
            $where .= " AND a.third_party = ?";
            $bindings[] = $supp;
        }

        if ($article) {
            $where .= " AND (lower(a.article_alternative_code) LIKE ?
                        OR lower(a.article_desc) LIKE ?
                        OR lower(m.artikel_code) LIKE ?)";
            $like = '%' . $article . '%';
            $bindings[] = $like;
            $bindings[] = $like;
            $bindings[] = $like;
        }

        /*
         | Filter transaksi (opsional).
         | IN     = RECEIVING, RETURN
         | OUT    = DELIVERY (+ revisi/cancel), REPLACEMENT
         | TRANSFER = TRANSFER (antar lokasi)
         | SUPPLY = SUPPLY (ke booth)
         | selain itu -> fallback ke movement_plus/movement_min
        */
        if ($inout === 'in') {
            $where .= " AND (m.movement_type IN ('RECEIVING','RETURN')
                        OR (m.movement_type NOT IN ('DELIVERY','REVISI DELIVERY','CANCEL DELIVERY','REPLACEMENT','TRANSFER','SUPPLY')
                            AND m.movement_plus > 0))";
        } elseif ($inout === 'out') {
            $where .= " AND (m.movement_type IN ('DELIVERY','REVISI DELIVERY','CANCEL DELIVERY','REPLACEMENT')
                        OR (m.movement_type NOT IN ('RECEIVING','RETURN','TRANSFER','SUPPLY')
                            AND m.movement_min > 0))";
        } elseif ($inout === 'transfer') {
            $where .= " AND m.movement_type = 'TRANSFER'";
        } elseif ($inout === 'supply') {
            $where .= " AND m.movement_type = 'SUPPLY'";
        }

        $sqlku = "
            SELECT
                m.movement_code,
                m.artikel_code,
                a.article_alternative_code as code,
                m.artikel_desc,
                a.article_type,
                m.movement_plus - m.movement_min as qty,
                m.movement_price,
                m.movement_date,
                m.movement_desc,
                m.movement_type,
                m.movement_min,
                m.movement_plus,
                m.movement_transnno,
                m.partner_type,
                m.location_number,

                CASE
                    WHEN m.partner_type = 'SUPP'
                        THEN (SELECT nama FROM third_party WHERE kode = m.movement_from)
                    ELSE (SELECT location_name FROM stock_location_master WHERE location_code = m.movement_from)
                END as mv_from,

                CASE
                    WHEN m.partner_type = 'CUST'
                        THEN (SELECT nama FROM third_party WHERE kode = m.movement_to)
                    ELSE (SELECT location_name FROM stock_location_master WHERE location_code = m.movement_to)
                END as mv_to,

                COALESCE(
                    rec.status,
                    trf.status,
                    del.status,
                    ret.status,
                    rep.status,
                    adj.status,
                    tdn.status,
                    dng.status
                ) as trx_status,

                m.site_code,
                m.created_at

            FROM warehouse_movement m
            LEFT JOIN article a
                ON a.article_code = m.artikel_code

            LEFT JOIN receiving_hdr rec
                ON rec.rec_number = m.movement_transnno
                AND m.movement_type = 'RECEIVING'

            LEFT JOIN transfer_stock_hdr trf
                ON trf.tr_number = m.movement_transnno
                AND m.movement_type IN ('TRANSFER','SUPPLY')

            LEFT JOIN delivery_hdr del
                ON del.delivery_number = m.movement_transnno
                AND m.movement_type IN ('DELIVERY','REVISI DELIVERY','CANCEL DELIVERY')

            LEFT JOIN dn_return_hdr ret
                ON ret.return_number = m.movement_transnno
                AND m.movement_type = 'RETURN'

            LEFT JOIN dn_replace_hdr rep
                ON rep.replace_number = m.movement_transnno
                AND m.movement_type = 'REPLACEMENT'

            LEFT JOIN stock_adjustment_hdr adj
                ON adj.adj_code = m.movement_transnno
                AND m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')

            LEFT JOIN temporary_dn_hdr tdn
                ON tdn.tdn_number = m.movement_transnno
                AND m.movement_type = 'DN SEMENTARA'

            LEFT JOIN dn_general_hdr dng
                ON dng.tdn_number = m.movement_transnno
                AND m.movement_type = 'DN UMUM'

            $where
            ORDER BY TO_DATE(m.movement_date,'dd-mm-yyyy') DESC, m.movement_code DESC
        ";

        $rows = DB::select($sqlku, $bindings);

        // nomor urut supaya DataTables tetap menampilkan terbaru di atas
        $n = count($rows);
        foreach ($rows as $row) {
            $row->urutan = $n--;
        }

        return Datatables::of($rows)
            ->addColumn('qty', function ($data) {
                $decimal = (fmod($data->qty, 1) !== 0.00) ? $this->decimalPlaces : 0;
                $qty = number_format($data->qty, $decimal);
                return $data->qty < 0
                    ? "<div class='text-right text-red'>$qty</div>"
                    : "<div class='text-right text-hijau'>$qty</div>";
            })
            ->addColumn('inout', function ($data) {
                $type = strtoupper($data->movement_type);

                // OUT
                if (in_array($type, ['DELIVERY','REVISI DELIVERY','CANCEL DELIVERY','REPLACEMENT'])) {
                    return "<span class='badge badge-pill badge-light-danger'>
                                <i data-feather='arrow-up-circle' class='font-small-3'></i> OUT
                            </span>";
                }
                // IN
                if (in_array($type, ['RECEIVING','RETURN'])) {
                    return "<span class='badge badge-pill badge-light-success'>
                                <i data-feather='arrow-down-circle' class='font-small-3'></i> IN
                            </span>";
                }
                // TRANSFER (antar lokasi)
                if ($type === 'TRANSFER') {
                    return "<span class='badge badge-pill badge-light-info'>
                                <i data-feather='repeat' class='font-small-3'></i> TRANSFER
                            </span>";
                }
                // SUPPLY (ke booth)
                if ($type === 'SUPPLY') {
                    return "<span class='badge badge-pill badge-light-warning'>
                                <i data-feather='send' class='font-small-3'></i> SUPPLY
                            </span>";
                }
                // fallback: pakai plus/min
                if ($data->movement_plus > 0) {
                    return "<span class='badge badge-pill badge-light-success'>
                                <i data-feather='arrow-down-circle' class='font-small-3'></i> IN
                            </span>";
                }
                if ($data->movement_min > 0) {
                    return "<span class='badge badge-pill badge-light-danger'>
                                <i data-feather='arrow-up-circle' class='font-small-3'></i> OUT
                            </span>";
                }
                return "<span class='badge badge-pill badge-light-secondary'>-</span>";
            })
            ->addColumn('movement_transnno', function ($data) {
                $ref = $data->movement_transnno;
                if (!$ref) return '-';

                $url    = null;
                $status = null;

                // Hanya status CANCEL yang mengunci link. REVISI tetap bisa diklik.
                $lockedStatus = ['5']; // 5 = CANCELED

                switch ($data->movement_type) {
                    case 'RECEIVING':
                        $row = DB::table('receiving_hdr')->where('rec_number', $ref)->select('id','status')->first();
                        if ($row) { $url = route('receiving.show', ['id' => Crypt::encryptString($row->id)]); $status = $row->status; }
                        break;

                    case 'TRANSFER':
                    case 'SUPPLY':
                        $row = DB::table('transfer_stock_hdr')->where('tr_number', $ref)->select('id','status')->first();
                        if ($row) { $url = route('transferStock.show', ['id' => Crypt::encryptString($row->id)]); $status = $row->status; }
                        break;

                    case 'DELIVERY':
                    case 'REVISI DELIVERY':
                    case 'CANCEL DELIVERY':
                        $row = DB::table('delivery_hdr')->where('delivery_number', $ref)->select('id','status')->first();
                        if ($row) { $url = route('delivery.show', ['id' => Crypt::encryptString($row->id)]); $status = $row->status; }
                        break;

                    case 'RETURN':
                        $row = DB::table('dn_return_hdr')->where('return_number', $ref)->select('id','status')->first();
                        if ($row) { $url = route('dnReturn.show', ['id' => Crypt::encryptString($row->id)]); $status = $row->status; }
                        break;

                    case 'REPLACEMENT':
                        $row = DB::table('dn_replace_hdr')->where('replace_number', $ref)->select('id','status')->first();
                        if ($row) { $url = route('dnReplace.show', ['id' => Crypt::encryptString($row->id)]); $status = $row->status; }
                        break;

                    case 'ADJUSTMENT':
                    case 'CANCEL ADJUSTMENT':
                        $row = DB::table('stock_adjustment_hdr')->where('adj_code', $ref)->select('id','status')->first();
                        if ($row) { $url = route('stockAdjustment.show', ['id' => Crypt::encryptString($row->id)]); $status = $row->status; }
                        break;

                    case 'DN SEMENTARA':
                        $row = DB::table('temporary_dn_hdr')->where('tdn_number', $ref)->select('id','status')->first();
                        if ($row) { $url = route('temporaryDn.show', ['id' => Crypt::encryptString($row->id)]); $status = $row->status; }
                        break;

                    case 'DN UMUM':
                        $row = DB::table('dn_general_hdr')->where('adj_code', $ref)->select('id','status')->first();
                        if ($row) { $url = route('dnGeneral.show', ['id' => Crypt::encryptString($row->id)]); $status = $row->status; }
                        break;
                }

                $isLocked = !$url || in_array((string) $status, $lockedStatus, true);

                if ($isLocked) {
                    return '<span class="text-muted" title="Transaksi cancel / tidak bisa dibuka">' . $ref . '</span>';
                }

                return '<a href="' . $url . '" target="_blank" class="text-primary">' . $ref . '</a>';
            })
            ->addColumn('trx_status', function ($data) {
                $st = $data->trx_status;
                if ($st === null || $st === '') {
                    return "<span class='badge badge-pill badge-light-secondary'>-</span>";
                }

                $map = [
                    '1'  => ['label' => 'NEW',     'class' => 'badge-light-primary'],
                    '2'  => ['label' => 'VALIDATED', 'class' => 'badge-light-info'],
                    '3'  => ['label' => 'APPROVED',  'class' => 'badge-light-warning'],
                    '4'  => ['label' => 'POSTED',    'class' => 'badge-light-success'],
                    '5'  => ['label' => 'CANCELED',  'class' => 'badge-light-danger'],
                    '7'  => ['label' => 'REVISED',   'class' => 'badge-light-warning'],
                    '10' => ['label' => 'REVISED',   'class' => 'badge-light-warning'],
                ];

                $cfg = $map[$st] ?? ['label' => strtoupper($st), 'class' => 'badge-light-secondary'];
                return "<span class='badge badge-pill {$cfg['class']}'>{$cfg['label']}</span>";
            })
            ->rawColumns(['qty', 'inout', 'movement_transnno', 'trx_status'])
            ->make(true);
    }
}