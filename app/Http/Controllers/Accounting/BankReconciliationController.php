<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\BcaStatementParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use DataTables;
use DB;
use AppHelpers;

class BankReconciliationController extends Controller
{
    private $title;
    private $moduleCode;
    // type (Kas/Bank) -> voucher_type kas_hdr yang relevan, sama seperti grouping di CashBankController.
    private $voucherTypes = ['KAS' => ['KM', 'KK'], 'BANK' => ['BM', 'BK']];

    public function __construct()
    {
        $this->title = 'Reconciliation Kas & Bank';
        $this->moduleCode = 'BANKREC';
    }

    public function getLastCode($key)
    {
        DB::table('master_code')->where('code_key', $key)->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by' => Auth::user()->username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $newCode = DB::table('master_code')->where('code_key', $key)->value('code_number');
        $months = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $month = $months[date('n') - 1];
        $year = date('Y');
        return "$key-$year-$month-$newCode";
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['subtitle'] = $this->title;
        $data['kolom'] = json_encode([
            ['data' => 'action', 'name' => 'action', 'title' => 'action', 'orderable' => false, 'searchable' => false],
            ['data' => 'recon_number', 'name' => 'recon_number', 'title' => 'Recon Number'],
            ['data' => 'periode', 'name' => 'periode', 'title' => 'Periode'],
            ['data' => 'type', 'name' => 'type', 'title' => 'Type'],
            ['data' => 'description', 'name' => 'description', 'title' => 'Description'],
            ['data' => 'match_summary', 'name' => 'match_summary', 'title' => 'Match'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'Created By'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created At'],
        ]);
        return view('accounting.bankReconciliation.index', $data);
    }

    public function create()
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        // Sama dengan select "Period" di accounting.bank.create: 1-12 tanpa zero-pad,
        // supaya langsung sama dengan kas_hdr.period dan tidak perlu konversi saat matching.
        $data['periodes'] = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return view('accounting.bankReconciliation.create', $data);
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;

        $validation = Validator::make($request->all(), [
            'periode' => 'required',
            'year' => 'required|integer',
            'type' => 'required|in:KAS,BANK',
            'statement' => 'required|file|mimes:csv,txt',
        ]);

        if ($validation->fails()) {
            $error_array = [];
            foreach ($validation->messages()->getMessages() as $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => $error_array, 'alert' => 'error']);
        }

        $periode = (int) $request->periode;
        $year = (int) $request->year;
        $type = $request->type;
        $description = $request->description;

        $file = $request->file('statement');
        $path = $file->store('bank-reconciliation', 'local');

        try {
            $parsed = (new BcaStatementParser())->parseFile(storage_path('app/' . $path));
        } catch (\Exception $e) {
            return response()->json(['status' => -1, 'title' => "Save $this->title", 'message' => 'Gagal membaca CSV: ' . $e->getMessage(), 'alert' => 'error']);
        }

        if (empty($parsed['rows'])) {
            return response()->json(['status' => 2, 'title' => "Save $this->title", 'message' => 'Tidak ada baris transaksi yang terbaca dari CSV ini.', 'alert' => 'warning']);
        }

        AppHelpers::resetCode($this->moduleCode);
        $reconNumber = $this->getLastCode($this->moduleCode);

        DB::beginTransaction();
        try {
            DB::table('bank_reconciliation_hdr')->insert([
                'recon_number' => $reconNumber,
                'periode' => $periode,
                'year' => $year,
                'description' => $description,
                'type' => $type,
                'status' => 'NEW',
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($parsed['rows'] as $row) {
                DB::table('bank_reconciliation_det')->insertOrIgnore([
                    'recon_number' => $reconNumber,
                    'stmt_date' => $row['stmt_date'],
                    'description' => $row['description'],
                    'amount' => $row['amount'],
                    'mutation_type' => $row['mutation_type'],
                    'saldo' => $row['saldo'],
                    'status' => 'UNMATCHED',
                    'created_by' => $username,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $matched = $this->autoMatch($reconNumber, $type, $periode, $year);

            DB::table('bank_reconciliation_hdr')->where('recon_number', $reconNumber)->update(['status' => 'DONE', 'updated_at' => date('Y-m-d H:i:s')]);

            DB::commit();
            $title = "Save $this->title";
            $message = "$reconNumber tersimpan. " . count($parsed['rows']) . " baris terbaca, $matched baris otomatis match.";
            $checksumWarning = $this->checksumWarning($parsed['rows'], $parsed['summary']);
            if ($checksumWarning) {
                $message .= ' PERINGATAN: ' . $checksumWarning;
            }
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => $checksumWarning ? 'warning' : 'success', 'reconNumber' => $reconNumber]);
        } catch (\Exception $e) {
            DB::rollBack();
            $title = "Save $this->title";
            $message = "Gagal menyimpan: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status' => -1, 'title' => $title, 'message' => $message, 'alert' => 'error']);
        }
    }

    // Bandingkan hasil parse baris transaksi terhadap ringkasan di akhir CSV
    // (Saldo Awal/Mutasi Debet/Mutasi Kredit/Saldo Akhir) sebagai checksum kewarasan parser.
    private function checksumWarning(array $rows, array $summary): ?string
    {
        if ($summary['count_cr'] === null && $summary['count_db'] === null) {
            return null; // ringkasan tidak ketemu di file, skip checksum
        }

        $cr = array_filter($rows, fn ($r) => $r['mutation_type'] === 'CR');
        $db = array_filter($rows, fn ($r) => $r['mutation_type'] === 'DB');
        $sumCr = round(array_sum(array_column($cr, 'amount')), 2);
        $sumDb = round(array_sum(array_column($db, 'amount')), 2);

        $issues = [];
        if ($summary['count_cr'] !== null && count($cr) !== $summary['count_cr']) {
            $issues[] = "jumlah baris CR terbaca " . count($cr) . ", file bilang {$summary['count_cr']}";
        }
        if ($summary['count_db'] !== null && count($db) !== $summary['count_db']) {
            $issues[] = "jumlah baris DB terbaca " . count($db) . ", file bilang {$summary['count_db']}";
        }
        if ($summary['mutasi_cr'] !== null && abs($sumCr - $summary['mutasi_cr']) > 0.01) {
            $issues[] = "total CR terbaca " . number_format($sumCr, 2) . ", file bilang " . number_format($summary['mutasi_cr'], 2);
        }
        if ($summary['mutasi_db'] !== null && abs($sumDb - $summary['mutasi_db']) > 0.01) {
            $issues[] = "total DB terbaca " . number_format($sumDb, 2) . ", file bilang " . number_format($summary['mutasi_db'], 2);
        }

        return $issues ? ('Hasil parse tidak cocok dengan ringkasan CSV (' . implode('; ', $issues) . '). Kemungkinan ada baris yang ke-skip, cek manual.') : null;
    }

    // Cocokkan tiap baris UNMATCHED ke kas_det (type + period/year + tanggal + nominal
    // sisi yang sesuai), kas_det yang sudah kepakai di-skip biar tidak dobel-match.
    // Tidak difilter per akun COA -- scope saat ini cuma 1 rekening per Type (Kas/Bank).
    private function autoMatch(string $reconNumber, string $type, int $periode, int $year): int
    {
        $voucherTypes = $this->voucherTypes[$type];
        $usedKasDetIds = [];
        $matchedCount = 0;

        $rows = DB::table('bank_reconciliation_det')->where('recon_number', $reconNumber)->where('status', 'UNMATCHED')->get();

        foreach ($rows as $row) {
            $amountColumn = $row->mutation_type === 'CR' ? 'debit' : 'credit';

            $candidate = DB::table('kas_det')
                ->join('kas_hdr', 'kas_hdr.voucher_number', '=', 'kas_det.voucher_number')
                ->whereIn('kas_hdr.voucher_type', $voucherTypes)
                ->where('kas_hdr.status', '<>', '5')
                ->whereRaw('kas_hdr.period::integer = ?', [$periode])
                ->where('kas_hdr.year', $year)
                ->where('kas_det.' . $amountColumn, $row->amount)
                ->whereRaw("to_date(kas_hdr.voucher_date,'DD-MM-YYYY') = ?", [$row->stmt_date])
                ->when(!empty($usedKasDetIds), function ($q) use ($usedKasDetIds) {
                    $q->whereNotIn('kas_det.id', $usedKasDetIds);
                })
                ->select('kas_det.id')
                ->first();

            if ($candidate) {
                $usedKasDetIds[] = $candidate->id;
                DB::table('bank_reconciliation_det')->where('id', $row->id)->update([
                    'status' => 'MATCHED',
                    'matched_kas_det_id' => $candidate->id,
                ]);
                $matchedCount++;
            }
        }

        return $matchedCount;
    }

    public function list(Request $request)
    {
        $search = strtolower($request->searchReconNumber);
        $searchType = $request->searchType;
        $searchPeriode = $request->searchPeriode;

        $data = DB::table('bank_reconciliation_hdr')
            ->when($search, function ($q) use ($search) { $q->where('recon_number', 'ilike', "%$search%"); })
            ->when($searchType, function ($q) use ($searchType) { $q->where('type', $searchType); })
            ->when($searchPeriode, function ($q) use ($searchPeriode) { $q->where('periode', $searchPeriode); })
            ->select(
                'bank_reconciliation_hdr.*',
                DB::raw("(select count(*) from bank_reconciliation_det where recon_number = bank_reconciliation_hdr.recon_number) as total_row"),
                DB::raw("(select count(*) from bank_reconciliation_det where recon_number = bank_reconciliation_hdr.recon_number and status = 'MATCHED') as matched_row")
            )
            ->orderBy('id', 'desc')
            ->get();

        return Datatables::of($data)
            ->addColumn('action', function ($d) {
                $id = Crypt::encryptString($d->id);
                $buttons = '<div class="d-inline-flex"><a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a><div class="dropdown-menu dropdown-menu-right">';
                $buttons .= '<a href="' . route('bankReconciliation.show', ['id' => $id]) . '" class="dropdown-item"><i data-feather="list"></i> ' . __('Detail') . '</a>';
                if (Auth::user()->can('bankReconciliation-delete')) {
                    $buttons .= "<a href='javascript:;' class='dropdown-item' data-size='sm' data-ajax-delete='true'
                        data-confirm='Are you sure want to Delete?|This action can not be undone. Do you want to continue?'
                        data-confirm-yes='document.getElementById(\"delete-form-{$d->id}\").submit();' data-modal-id='{$d->id}'
                        data-url='" . route('bankReconciliation.destroy', ['id' => $id]) . "'><i data-feather='trash-2' class='feather-14-red'></i> " . __('Delete') . '</a>';
                }
                return $buttons . '</div></div>';
            })
            ->addColumn('recon_number', function ($d) {
                return '<a href="' . route('bankReconciliation.show', ['id' => Crypt::encryptString($d->id)]) . '">' . $d->recon_number . '</a>';
            })
            ->addColumn('match_summary', function ($d) {
                $pct = $d->total_row > 0 ? round($d->matched_row / $d->total_row * 100, 1) : 0;
                $color = $pct == 100 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                return "<div class='badge badge-{$color}'>{$d->matched_row}/{$d->total_row} ({$pct}%)</div>";
            })
            ->rawColumns(['action', 'recon_number', 'match_summary'])
            ->make(true);
    }

    public function show(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";
        $data['header'] = DB::table('bank_reconciliation_hdr')->where('id', $id)->first();
        abort_unless($data['header'], 404);
        $data['id'] = $id;

        $data['kolomDetail'] = json_encode([
            ['data' => 'action', 'name' => 'action', 'title' => 'action', 'orderable' => false, 'searchable' => false],
            ['data' => 'stmt_date', 'name' => 'stmt_date', 'title' => 'Tanggal'],
            ['data' => 'description', 'name' => 'description', 'title' => 'Keterangan'],
            ['data' => 'mutation_type', 'name' => 'mutation_type', 'title' => 'DB/CR'],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'Mutasi'],
            ['data' => 'saldo', 'name' => 'saldo', 'title' => 'Saldo'],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status'],
            ['data' => 'voucher_number', 'name' => 'voucher_number', 'title' => 'Voucher GL'],
            ['data' => 'voucher_date', 'name' => 'voucher_date', 'title' => 'Tanggal GL'],
            ['data' => 'gl_amount', 'name' => 'gl_amount', 'title' => 'Nilai GL'],
        ]);

        return view('accounting.bankReconciliation.show', $data);
    }

    public function listDetail(Request $request)
    {
        // $id di sini sudah plain (bukan terenkripsi) -- show() mengirim id hasil
        // decrypt ke view (lihat $data['id']), lalu dipakai lagi buat endpoint AJAX ini.
        $id = $request->id;
        $header = DB::table('bank_reconciliation_hdr')->where('id', $id)->first();
        abort_unless($header, 404);

        $data = DB::table('bank_reconciliation_det')
            ->leftJoin('kas_det', 'kas_det.id', '=', 'bank_reconciliation_det.matched_kas_det_id')
            ->leftJoin('kas_hdr', 'kas_hdr.voucher_number', '=', 'kas_det.voucher_number')
            ->where('bank_reconciliation_det.recon_number', $header->recon_number)
            ->select(
                'bank_reconciliation_det.*',
                'kas_hdr.voucher_number',
                'kas_hdr.voucher_date',
                DB::raw('coalesce(kas_det.debit, kas_det.credit) as gl_amount')
            )
            ->orderBy('bank_reconciliation_det.stmt_date')
            ->orderBy('bank_reconciliation_det.id')
            ->get();

        return Datatables::of($data)
            ->addColumn('action', function ($d) {
                if ($d->status === 'MATCHED') {
                    return "<a href='javascript:;' class='btn btn-sm btn-outline-warning' onclick='unmatchRow({$d->id})'>Unmatch</a>";
                }
                return "<a href='javascript:;' class='btn btn-sm btn-outline-primary' onclick='openManualMatch({$d->id}, \"{$d->stmt_date}\", {$d->amount}, \"{$d->mutation_type}\")'>Match Manual</a>";
            })
            ->addColumn('stmt_date', function ($d) { return date('d-m-Y', strtotime($d->stmt_date)); })
            ->addColumn('amount', function ($d) { return number_format($d->amount, 2); })
            ->addColumn('saldo', function ($d) { return $d->saldo !== null ? number_format($d->saldo, 2) : '-'; })
            ->addColumn('status', function ($d) {
                $color = $d->status === 'MATCHED' ? 'success' : 'danger';
                $label = $d->status === 'MATCHED' ? 'MATCH' : 'NOT MATCH';
                return "<div class='badge badge-{$color}'>{$label}</div>";
            })
            ->addColumn('voucher_number', function ($d) { return $d->voucher_number ?: '-'; })
            ->addColumn('voucher_date', function ($d) { return $d->voucher_date ?: '-'; })
            ->addColumn('gl_amount', function ($d) { return $d->gl_amount !== null ? number_format($d->gl_amount, 2) : '-'; })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    // Pencarian voucher kandidat untuk match manual (dipakai modal di halaman show).
    public function searchVoucher(Request $request)
    {
        $header = DB::table('bank_reconciliation_hdr')->where('recon_number', $request->reconNumber)->first();
        abort_unless($header, 404);
        $voucherTypes = $this->voucherTypes[$header->type];

        $candidates = DB::table('kas_det')
            ->join('kas_hdr', 'kas_hdr.voucher_number', '=', 'kas_det.voucher_number')
            ->whereIn('kas_hdr.voucher_type', $voucherTypes)
            ->where('kas_hdr.status', '<>', '5')
            ->whereRaw('kas_hdr.period::integer = ?', [(int) $header->periode])
            ->where('kas_hdr.year', $header->year)
            ->whereNotIn('kas_det.id', function ($q) {
                $q->select('matched_kas_det_id')->from('bank_reconciliation_det')->whereNotNull('matched_kas_det_id');
            })
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($s) use ($request) {
                    $s->where('kas_det.voucher_number', 'ilike', '%' . $request->search . '%')
                      ->orWhere('kas_det.description', 'ilike', '%' . $request->search . '%');
                });
            })
            ->select('kas_det.id', 'kas_det.voucher_number', 'kas_hdr.voucher_date', 'kas_det.description', 'kas_det.debit', 'kas_det.credit')
            ->orderBy('kas_hdr.voucher_date', 'desc')
            ->limit(20)
            ->get();

        return response()->json($candidates);
    }

    public function matchManual(Request $request)
    {
        $username = Auth::user()->username;
        $det = DB::table('bank_reconciliation_det')->where('id', $request->detId)->first();
        abort_unless($det, 404);

        DB::table('bank_reconciliation_det')->where('id', $det->id)->update([
            'status' => 'MATCHED',
            'matched_kas_det_id' => $request->kasDetId,
        ]);

        $title = "Match Manual $this->title";
        \LogActivity::addToLog($title, "username: $username detId: $det->id kasDetId: $request->kasDetId");
        return response()->json(['status' => 1, 'title' => $title, 'message' => 'Baris berhasil di-match.', 'alert' => 'success']);
    }

    public function unmatch(Request $request)
    {
        DB::table('bank_reconciliation_det')->where('id', $request->detId)->update([
            'status' => 'UNMATCHED',
            'matched_kas_det_id' => null,
        ]);
        return response()->json(['status' => 1, 'title' => "Unmatch $this->title", 'message' => 'Match dibatalkan.', 'alert' => 'success']);
    }

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $header = DB::table('bank_reconciliation_hdr')->where('id', $id)->first();

        if ($header) {
            DB::table('bank_reconciliation_det')->where('recon_number', $header->recon_number)->delete();
            DB::table('bank_reconciliation_hdr')->where('id', $id)->delete();
            $title = "Delete $this->title";
            $message = "$header->recon_number Successfully Deleted";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);
        }

        return redirect()->back()->with(['title' => "Delete $this->title", 'alert' => 'warning', 'message' => 'Not found']);
    }
}
