<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Imports\UnifikasiBppuImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use DataTables;
use DB;
use Excel;
use AppHelpers;

// Equalisasi Unifikasi BPPU -- pola sama dengan PpnPkEqualisasiController, bedanya kunci
// matching = Nomor Pemotongan (Excel) vs bukti_potong di voucher (kas_hdr GJ/BK/KK,
// ap_invoice, debit_note_hdr) yang punya posting KREDIT ke COA hutang PPh tipe terpilih.
class UnifikasiBppuController extends Controller
{
    private $title;
    private $group = 'bppu';
    private $route = 'unifikasiBppu';
    private $periodes = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    const TYPES = [
        '23' => ['title' => 'Equalisasi Unifikasi PPh 23', 'coa_account' => '2000.14.3', 'code_key' => 'BP23', 'group' => 'bppu'],
        '42' => ['title' => 'Equalisasi Unifikasi PPh 4(2)', 'coa_account' => '2000.14.6', 'code_key' => 'BP42', 'group' => 'bppu'],
    ];

    public function __construct()
    {
        $this->title = 'Unifikasi BPPU';
    }

    private function groupTypes(): array
    {
        return array_filter(self::TYPES, fn ($t) => $t['group'] === $this->group);
    }

    // Hutang PPh -> posting kredit.
    private function glCol(string $coaAccount): string
    {
        foreach (self::TYPES as $t) {
            if ($t['coa_account'] === $coaAccount) {
                return empty($t['debit']) ? 'credit' : 'debit';
            }
        }
        return 'credit';
    }

    private function types(): array
    {
        $desc = DB::table('accounts')->whereIn('account', array_column(self::TYPES, 'coa_account'))->pluck('description', 'account');
        $types = $this->groupTypes();
        foreach ($types as &$t) {
            $t['coa_description'] = $desc[$t['coa_account']] ?? '';
        }
        return $types;
    }

    private function getLastCode($key)
    {
        DB::table('master_code')->where('code_key', $key)->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by' => Auth::user()->username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $newCode = DB::table('master_code')->where('code_key', $key)->value('code_number');
        $months = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        return "$key-ASN-" . date('Y') . '-' . $months[date('n') - 1] . '-' . str_pad($newCode, 4, '0', STR_PAD_LEFT);
    }

    private static function norm($s): string
    {
        return strtoupper(preg_replace('/[^0-9A-Za-z]/', '', (string) $s));
    }

    private function errorResponse($validation, $title)
    {
        $error_array = [];
        foreach ($validation->messages()->getMessages() as $messages) {
            $error_array[] = $messages;
        }
        return response()->json(['status' => 0, 'title' => $title, 'message' => $error_array, 'alert' => 'error']);
    }

    private function excelRule()
    {
        return ['file', function ($attr, $value, $fail) {
            if (!in_array(strtolower($value->getClientOriginalExtension()), ['xls', 'xlsx'])) {
                $fail('The statement must be a file of type: xls, xlsx.');
            }
        }];
    }

    private function readExcel(string $fullPath): array
    {
        $import = new UnifikasiBppuImport();
        Excel::import($import, $fullPath);
        return $import->rows;
    }

    // Voucher yang punya posting ke COA + bukti potongnya, query dasar untuk auto-match,
    // search Match Manual, dan refresh Nilai BB baris manual.
    private function glVoucherQuery(string $coaAccount)
    {
        return DB::table('kas_det as d')
            ->join('kas_hdr as h', 'h.voucher_number', '=', 'd.voucher_number')
            ->leftJoin('ap_invoice as ap', 'ap.ap_number', '=', 'd.voucher_number')
            ->leftJoin('debit_note_hdr as dn', 'dn.dn_number', '=', 'd.voucher_number')
            ->leftJoin('invoice_hdr as inv', 'inv.invoice_number', '=', 'd.voucher_number')
            ->where('d.account', $coaAccount)
            ->where('h.status', '<>', '5')
            ->groupBy('d.voucher_number', 'h.voucher_date', DB::raw("coalesce(nullif(h.bukti_potong, ''), nullif(ap.bukti_potong, ''), nullif(dn.bukti_potong, ''), inv.bukti_potong)"))
            ->select('d.voucher_number', 'h.voucher_date', DB::raw("coalesce(nullif(h.bukti_potong, ''), nullif(ap.bukti_potong, ''), nullif(dn.bukti_potong, ''), inv.bukti_potong) as bukti_potong"), DB::raw('coalesce(sum(d.' . $this->glCol($coaAccount) . '), 0) as gl_amount'));
    }

    // Link ke halaman edit modul asal voucher: AP Invoice / Invoice / Debit Note pakai id tabelnya
    // sendiri, BK/KK/BM/KM/GJ pakai kas_hdr.id. Voucher tipe lain tampil tanpa link.
    private function voucherLink(?string $voucherNumber): ?string
    {
        if (!$voucherNumber) {
            return null;
        }
        if ($id = DB::table('ap_invoice')->where('ap_number', $voucherNumber)->value('id')) {
            return route('accountPayable.edit', ['id' => Crypt::encryptString((string) $id)]);
        }
        if ($id = DB::table('invoice_hdr')->where('invoice_number', $voucherNumber)->value('id')) {
            return route('invoice.edit', ['id' => Crypt::encryptString((string) $id)]);
        }
        if ($id = DB::table('debit_note_hdr')->where('dn_number', $voucherNumber)->value('id')) {
            return route('debitNote.edit', ['id' => Crypt::encryptString((string) $id)]);
        }
        $kas = DB::table('kas_hdr')->where('voucher_number', $voucherNumber)->first(['id', 'voucher_type']);
        $route = ['BK' => 'bankKeluar.edit', 'KK' => 'kasKeluar.edit', 'GJ' => 'jurnalUmum.edit', 'BM' => 'bankPenerimaan.edit', 'KM' => 'kasPenerimaan.edit'][$kas->voucher_type ?? ''] ?? null;
        return $route ? route($route, ['id' => Crypt::encryptString((string) $kas->id)]) : null;
    }

    private function voucherCell(?string $voucherNumber): string
    {
        if (!$voucherNumber) {
            return '-';
        }
        $url = $this->voucherLink($voucherNumber);
        return $url ? "<a href='$url' target='_blank'>" . e($voucherNumber) . '</a>' : e($voucherNumber);
    }

    private function glAmount(string $coaAccount, string $voucherNumber): float
    {
        return (float) DB::table('kas_det as d')
            ->join('kas_hdr as h', 'h.voucher_number', '=', 'd.voucher_number')
            ->where('d.account', $coaAccount)
            ->where('h.status', '<>', '5')
            ->where('d.voucher_number', $voucherNumber)
            ->sum('d.' . $this->glCol($coaAccount));
    }

    // Baris dengan match_type MANUAL dipertahankan vouchernya (Nilai BB-nya saja yang
    // di-refresh dari ledger), baris lain dicocokkan otomatis lewat bukti potong.
    private function matchRows(array $rows, string $coaAccount): array
    {
        $byBupot = [];
        foreach ($this->glVoucherQuery($coaAccount)->get() as $v) {
            $key = self::norm($v->bukti_potong);
            if ($key !== '') {
                $byBupot[$key] = $v;
            }
        }

        foreach ($rows as &$row) {
            $voucherNumber = null;
            $gl = null;
            if (($row['match_type'] ?? null) === 'MANUAL') {
                $voucherNumber = $row['matched_voucher_number'] ?: null;
                $gl = $voucherNumber ? $this->glAmount($coaAccount, $voucherNumber) : null;
            } elseif ($v = $byBupot[self::norm($row['doc_number'])] ?? null) {
                $voucherNumber = $v->voucher_number;
                $gl = (float) $v->gl_amount;
                $row['match_type'] = 'AUTO';
            } else {
                $row['match_type'] = null;
            }
            $row['matched_voucher_number'] = $voucherNumber;
            $row['gl_amount'] = $gl;
            $row['match_status'] = $voucherNumber && abs(round($row['pph'] - $gl, 2)) <= 0.01 ? 'MATCHED' : 'UNMATCHED';
        }
        unset($row);

        return $rows;
    }

    private function glMutasi(int $periode, int $year, string $coaAccount): float
    {
        $from = sprintf('%04d-%02d-01', $year, $periode);
        return (float) DB::table('kas_det as d')
            ->join('kas_hdr as h', 'h.voucher_number', '=', 'd.voucher_number')
            ->where('d.account', $coaAccount)
            ->where('h.status', '<>', '5')
            ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') between ? and ?", [$from, date('Y-m-t', strtotime($from))])
            ->sum('d.' . $this->glCol($coaAccount));
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['subtitle'] = $this->title;
        $data['kolom'] = json_encode([
            ['data' => 'action', 'name' => 'action', 'title' => 'action', 'orderable' => false, 'searchable' => false],
            ['data' => 'equal_number', 'name' => 'equal_number', 'title' => 'Equal Number'],
            ['data' => 'type', 'name' => 'type', 'title' => 'Type'],
            ['data' => 'coa_account', 'name' => 'coa_account', 'title' => 'COA'],
            ['data' => 'periode', 'name' => 'periode', 'title' => 'Periode'],
            ['data' => 'description', 'name' => 'description', 'title' => 'Description'],
            ['data' => 'total_pph', 'name' => 'total_pph', 'title' => 'Total PPh (Excel)'],
            ['data' => 'match_status', 'name' => 'match_status', 'title' => 'Status'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'Created By'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created At'],
        ]);
        $data['types'] = $this->groupTypes();
        return view('accounting.unifikasiBppu.index', $data + ['route' => $this->route, 'isBps' => $this->group === 'bps']);
    }

    public function create()
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $data['periodes'] = $this->periodes;
        $data['types'] = $this->types();
        return view('accounting.unifikasiBppu.create', $data + ['route' => $this->route, 'isBps' => $this->group === 'bps']);
    }

    public function preview(Request $request)
    {
        $title = "Preview $this->title";
        $validation = Validator::make($request->all(), [
            'type' => 'required|in:' . implode(',', array_keys($this->groupTypes())),
            'periode' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'statement' => array_merge(['required'], $this->excelRule()),
        ]);
        if ($validation->fails()) {
            return $this->errorResponse($validation, $title);
        }

        $coaAccount = self::TYPES[$request->type]['coa_account'];
        $path = $request->file('statement')->store('unifikasi-bppu', 'local');
        try {
            $rows = $this->readExcel(storage_path('app/' . $path));
        } catch (\Exception $e) {
            return response()->json(['status' => -1, 'title' => $title, 'message' => 'Gagal membaca Excel: ' . $e->getMessage(), 'alert' => 'error']);
        }
        if (empty($rows)) {
            return response()->json(['status' => 2, 'title' => $title, 'message' => 'Tidak ada baris data yang terbaca dari Excel ini.', 'alert' => 'warning']);
        }

        $rows = $this->matchRows($rows, $coaAccount);
        foreach ($rows as &$r) {
            $r['voucher_link'] = $this->voucherLink($r['matched_voucher_number']);
        }
        unset($r);
        $totalPph = round(array_sum(array_column($rows, 'pph')), 2);
        $matchedCount = count(array_filter($rows, fn ($r) => $r['match_status'] === 'MATCHED'));
        $glMutasi = $this->glMutasi((int) $request->periode, (int) $request->year, $coaAccount);
        $selisih = round($totalPph - $glMutasi, 2);

        return response()->json([
            'status' => 1,
            'title' => $title,
            'message' => count($rows) . " baris terbaca, $matchedCount otomatis match.",
            'alert' => 'success',
            'filePath' => $path,
            'rows' => $rows,
            'totalRows' => count($rows),
            'matchedCount' => $matchedCount,
            'totalDpp' => round(array_sum(array_column($rows, 'dpp')), 2),
            'totalPph' => $totalPph,
            'glMutasi' => $glMutasi,
            'selisih' => $selisih,
            'match' => abs($selisih) <= 0.01,
        ]);
    }

    private function detRow(array $row): array
    {
        return [
            'masa_pajak' => $row['masa_pajak'],
            'doc_number' => $row['doc_number'],
            'status' => $row['status'],
            'jenis_pajak' => $row['jenis_pajak'],
            'kode_objek_pajak' => $row['kode_objek_pajak'],
            'nama' => $row['nama'],
            'dpp' => $row['dpp'],
            'pph' => $row['pph'],
            'match_status' => $row['match_status'],
            'match_type' => $row['match_type'],
            'matched_voucher_number' => $row['matched_voucher_number'],
            'gl_amount' => $row['gl_amount'],
        ];
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $title = "Save $this->title";
        $validation = Validator::make($request->all(), [
            'type' => 'required|in:' . implode(',', array_keys($this->groupTypes())),
            'periode' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'filePath' => 'required|string',
        ]);
        if ($validation->fails()) {
            return $this->errorResponse($validation, $title);
        }

        $typeInfo = self::TYPES[$request->type];
        $filePath = $request->filePath;
        // filePath dari response preview(), dibatasi ke folder sendiri.
        if (strpos($filePath, 'unifikasi-bppu/') !== 0 || strpos($filePath, '..') !== false || !is_file(storage_path('app/' . $filePath))) {
            return response()->json(['status' => -1, 'title' => $title, 'message' => 'File upload tidak valid / sudah tidak ada, silakan upload ulang.', 'alert' => 'error']);
        }

        try {
            $rows = $this->readExcel(storage_path('app/' . $filePath));
        } catch (\Exception $e) {
            return response()->json(['status' => -1, 'title' => $title, 'message' => 'Gagal membaca Excel: ' . $e->getMessage(), 'alert' => 'error']);
        }
        if (empty($rows)) {
            return response()->json(['status' => 2, 'title' => $title, 'message' => 'Tidak ada baris data yang terbaca dari Excel ini.', 'alert' => 'warning']);
        }
        $rows = $this->matchRows($rows, $typeInfo['coa_account']);

        AppHelpers::resetCode($typeInfo['code_key']);
        $equalNumber = $this->getLastCode($typeInfo['code_key']);
        $now = date('Y-m-d H:i:s');

        DB::beginTransaction();
        try {
            DB::table('unifikasi_bppu_hdr')->insert([
                'equal_number' => $equalNumber,
                'type' => $request->type,
                'periode' => (int) $request->periode,
                'year' => (int) $request->year,
                'coa_account' => $typeInfo['coa_account'],
                'description' => $request->description,
                'total_dpp' => round(array_sum(array_column($rows, 'dpp')), 2),
                'total_pph' => round(array_sum(array_column($rows, 'pph')), 2),
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach ($rows as $row) {
                DB::table('unifikasi_bppu_det')->insertOrIgnore($this->detRow($row) + ['equal_number' => $equalNumber, 'created_by' => $username, 'created_at' => $now]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => -1, 'title' => $title, 'message' => 'Gagal menyimpan: ' . $e->getMessage(), 'alert' => 'error']);
        }

        $message = "$equalNumber tersimpan. " . count($rows) . ' baris.';
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => 'success']);
    }

    public function list(Request $request)
    {
        $search = $request->searchEqualNumber;
        $data = DB::table('unifikasi_bppu_hdr')
            ->whereIn('type', array_keys($this->groupTypes()))
            ->when($search, fn ($q) => $q->where('equal_number', 'ilike', "%$search%"))
            ->when($request->searchType, fn ($q) => $q->where('type', $request->searchType))
            ->when($request->searchPeriode, fn ($q) => $q->where('periode', $request->searchPeriode))
            ->orderBy('id', 'desc')
            ->get();

        return Datatables::of($data)
            ->addColumn('action', function ($d) {
                $id = Crypt::encryptString($d->id);
                $buttons = '<div class="d-inline-flex"><a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a><div class="dropdown-menu dropdown-menu-right">';
                $buttons .= '<a href="' . route($this->route . '.show', ['id' => $id]) . '" class="dropdown-item"><i data-feather="list"></i> ' . __('Detail') . '</a>';
                $buttons .= "<a href='javascript:;' class='dropdown-item' data-size='sm' data-ajax-delete='true'
                    data-confirm='Are you sure want to Delete?|This action can not be undone. Do you want to continue?'
                    data-confirm-yes='document.getElementById(\"delete-form-{$d->id}\").submit();' data-modal-id='{$d->id}'
                    data-url='" . route($this->route . '.destroy', ['id' => $id]) . "'><i data-feather='trash-2' class='feather-14-red'></i> " . __('Delete') . '</a>';
                return $buttons . '</div></div>';
            })
            ->addColumn('equal_number', fn ($d) => '<a href="' . route($this->route . '.show', ['id' => Crypt::encryptString($d->id)]) . '">' . $d->equal_number . '</a>')
            ->addColumn('type', fn ($d) => self::TYPES[$d->type]['title'] ?? $d->type)
            ->addColumn('periode', fn ($d) => ($this->periodes[(int) $d->periode] ?? $d->periode) . ' / ' . $d->year)
            ->addColumn('total_pph', fn ($d) => number_format($d->total_pph, 2))
            ->addColumn('match_status', function ($d) {
                $match = abs(round($d->total_pph - $this->glMutasi((int) $d->periode, (int) $d->year, $d->coa_account), 2)) <= 0.01;
                return "<div class='badge badge-" . ($match ? 'success' : 'danger') . "'>" . ($match ? 'MATCH' : 'NOT MATCH') . '</div>';
            })
            ->rawColumns(['action', 'equal_number', 'match_status'])
            ->make(true);
    }

    public function show(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";
        $data['header'] = DB::table('unifikasi_bppu_hdr')->where('id', $id)->first();
        abort_unless($data['header'], 404);
        $data['id'] = $id;
        $data['periodes'] = $this->periodes;
        $data['typeInfo'] = $this->types()[$data['header']->type] ?? abort(404);
        $data['glMutasi'] = $this->glMutasi((int) $data['header']->periode, (int) $data['header']->year, $data['header']->coa_account);
        // Kolom mengikuti kolom kuning di masing-masing Excel (BPS tidak ada Status/Jenis Pajak).
        $excelCols = $this->group === 'bps'
            ? ['masa_pajak' => 'Masa Pajak', 'doc_number' => 'Nomor Pemotongan', 'nama' => 'Nama Pemotong', 'kode_objek_pajak' => 'Kode Objek Pajak']
            : ['masa_pajak' => 'Masa Pajak', 'doc_number' => 'Nomor Pemotongan', 'status' => 'Status', 'jenis_pajak' => 'Jenis Pajak', 'kode_objek_pajak' => 'Kode Objek Pajak', 'nama' => 'Nama'];
        $data['kolomDetail'] = json_encode(array_merge(
            [['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => 'No', 'orderable' => false, 'searchable' => false]],
            array_map(fn ($k, $t) => ['data' => $k, 'name' => $k, 'title' => $t], array_keys($excelCols), $excelCols),
            [
            ['data' => 'dpp', 'name' => 'dpp', 'title' => 'DPP (Rp)'],
            ['data' => 'pph', 'name' => 'pph', 'title' => 'PPh (Rp)'],
            ['data' => 'match_status', 'name' => 'match_status', 'title' => 'Match'],
            ['data' => 'matched_voucher_number', 'name' => 'matched_voucher_number', 'title' => 'Voucher BB'],
            ['data' => 'gl_amount', 'name' => 'gl_amount', 'title' => 'Nilai BB'],
            ['data' => 'action', 'name' => 'action', 'title' => 'action', 'orderable' => false, 'searchable' => false],
        ]));
        // Index kolom DPP pertama untuk JS (rata kanan, badge PENDING, dst).
        $data['dppCol'] = count($excelCols) + 1;
        return view('accounting.unifikasiBppu.show', $data + ['route' => $this->route, 'isBps' => $this->group === 'bps']);
    }

    public function listDetail(Request $request)
    {
        $header = DB::table('unifikasi_bppu_hdr')->where('id', $request->id)->first();
        abort_unless($header, 404);

        $data = DB::table('unifikasi_bppu_det')
            ->where('equal_number', $header->equal_number)
            ->when($request->status, fn ($q) => $q->where('match_status', $request->status))
            ->orderBy('id')
            ->get();

        return Datatables::of($data)
            ->addIndexColumn()
            ->addColumn('dpp', fn ($d) => number_format($d->dpp, 2))
            ->addColumn('pph', fn ($d) => number_format($d->pph, 2))
            ->addColumn('match_status', function ($d) {
                $ok = $d->match_status === 'MATCHED';
                $manual = $d->match_type === 'MANUAL' ? ' <small class="text-muted">(manual)</small>' : '';
                return "<div class='badge badge-" . ($ok ? 'success' : 'danger') . "'>" . ($ok ? 'MATCH' : 'NOT MATCH') . "</div>$manual";
            })
            ->addColumn('matched_voucher_number', fn ($d) => $this->voucherCell($d->matched_voucher_number))
            ->addColumn('gl_amount', fn ($d) => $d->gl_amount !== null ? number_format($d->gl_amount, 2) : '-')
            ->addColumn('action', function ($d) {
                $btn = '';
                if ($d->matched_voucher_number) {
                    $btn .= "<a href='javascript:;' data-det-id='{$d->id}' class='btn btn-sm btn-outline-warning mb-25' onclick='unmatchRow({$d->id})'>Unmatch</a> ";
                }
                if ($d->match_status !== 'MATCHED') {
                    $docNumber = e($d->doc_number);
                    $btn .= "<a href='javascript:;' data-det-id='{$d->id}' class='btn btn-sm btn-outline-primary' onclick='openManualMatch({$d->id}, \"{$docNumber}\", {$d->pph})'>Match Manual</a>";
                }
                return $btn;
            })
            ->rawColumns(['match_status', 'matched_voucher_number', 'action'])
            ->make(true);
    }

    public function searchVoucher(Request $request)
    {
        $header = DB::table('unifikasi_bppu_hdr')->where('equal_number', $request->equalNumber)->first();
        abort_unless($header, 404);
        $search = $request->search;

        $rows = DB::query()->fromSub($this->glVoucherQuery($header->coa_account), 'v')
            ->when($search, fn ($q) => $q->where(fn ($s) => $s->where('voucher_number', 'ilike', "%$search%")->orWhere('bukti_potong', 'ilike', "%$search%")))
            ->orderByRaw("to_date(voucher_date,'DD-MM-YYYY') desc")
            ->limit(20)
            ->get();

        return response()->json($rows);
    }

    public function matchManual(Request $request)
    {
        $username = Auth::user()->username;
        $det = DB::table('unifikasi_bppu_det')->where('id', $request->detId)->first();
        abort_unless($det, 404);
        $header = DB::table('unifikasi_bppu_hdr')->where('equal_number', $det->equal_number)->first();
        $voucherNumber = (string) $request->voucherNumber;
        abort_unless($voucherNumber !== '' && DB::table('kas_hdr')->where('voucher_number', $voucherNumber)->exists(), 422);

        $gl = $this->glAmount($header->coa_account, $voucherNumber);
        DB::table('unifikasi_bppu_det')->where('id', $det->id)->update([
            'match_status' => abs(round($det->pph - $gl, 2)) <= 0.01 ? 'MATCHED' : 'UNMATCHED',
            'match_type' => 'MANUAL',
            'matched_voucher_number' => $voucherNumber,
            'gl_amount' => $gl,
        ]);

        $title = "Match Manual $this->title";
        \LogActivity::addToLog($title, "username: $username detId: $det->id voucher: $voucherNumber");
        return response()->json(['status' => 1, 'title' => $title, 'message' => 'Baris berhasil di-match.', 'alert' => 'success']);
    }

    // Unmatch disimpan sebagai MANUAL tanpa voucher -- supaya Rekonsiliasi tidak
    // meng-auto-match ulang baris yang sengaja dilepas user.
    public function unmatch(Request $request)
    {
        DB::table('unifikasi_bppu_det')->where('id', $request->detId)->update([
            'match_status' => 'UNMATCHED',
            'match_type' => 'MANUAL',
            'matched_voucher_number' => null,
            'gl_amount' => null,
        ]);
        return response()->json(['status' => 1, 'title' => "Unmatch $this->title", 'message' => 'Match dibatalkan.', 'alert' => 'success']);
    }

    // Rekonsiliasi: ambil perubahan transaksi IMS (Nilai BB / auto-match ulang) dan,
    // kalau user upload Excel baru, perubahan Excel (baris baru ditambah, baris yang sudah
    // tidak ada dihapus, nilai di-update). Hasil Match Manual tetap dipertahankan.
    public function reconcile(Request $request)
    {
        $username = Auth::user()->username;
        $title = "Rekonsiliasi $this->title";
        $validation = Validator::make($request->all(), [
            'id' => 'required',
            'statement' => array_merge(['nullable'], $this->excelRule()),
        ]);
        if ($validation->fails()) {
            return $this->errorResponse($validation, $title);
        }
        $header = DB::table('unifikasi_bppu_hdr')->where('id', $request->id)->first();
        abort_unless($header, 404);

        $existing = DB::table('unifikasi_bppu_det')->where('equal_number', $header->equal_number)->get()->keyBy(fn ($d) => self::norm($d->doc_number));

        if ($request->hasFile('statement')) {
            $path = $request->file('statement')->store('unifikasi-bppu', 'local');
            try {
                $excelRows = $this->readExcel(storage_path('app/' . $path));
            } catch (\Exception $e) {
                return response()->json(['status' => -1, 'title' => $title, 'message' => 'Gagal membaca Excel: ' . $e->getMessage(), 'alert' => 'error']);
            }
            if (empty($excelRows)) {
                return response()->json(['status' => 2, 'title' => $title, 'message' => 'Tidak ada baris data yang terbaca dari Excel ini.', 'alert' => 'warning']);
            }
        } else {
            $excelRows = $existing->map(fn ($d) => (array) $d)->values()->all();
        }

        // Bawa hasil match manual dari baris lama ke baris Excel dengan nomor pemotongan sama.
        foreach ($excelRows as &$r) {
            $old = $existing[self::norm($r['doc_number'])] ?? null;
            $r['id'] = $old->id ?? null;
            $r['match_type'] = $old && $old->match_type === 'MANUAL' ? 'MANUAL' : null;
            $r['matched_voucher_number'] = $old && $old->match_type === 'MANUAL' ? $old->matched_voucher_number : null;
        }
        unset($r);
        $rows = $this->matchRows($excelRows, $header->coa_account);
        $now = date('Y-m-d H:i:s');

        DB::beginTransaction();
        try {
            $keepIds = [];
            foreach ($rows as $row) {
                if ($row['id']) {
                    DB::table('unifikasi_bppu_det')->where('id', $row['id'])->update($this->detRow($row));
                    $keepIds[] = $row['id'];
                } else {
                    $keepIds[] = DB::table('unifikasi_bppu_det')->insertGetId($this->detRow($row) + ['equal_number' => $header->equal_number, 'created_by' => $username, 'created_at' => $now]);
                }
            }
            DB::table('unifikasi_bppu_det')->where('equal_number', $header->equal_number)->whereNotIn('id', $keepIds)->delete();
            DB::table('unifikasi_bppu_hdr')->where('id', $header->id)->update([
                'total_dpp' => round(array_sum(array_column($rows, 'dpp')), 2),
                'total_pph' => round(array_sum(array_column($rows, 'pph')), 2),
                'updated_by' => $username,
                'updated_at' => $now,
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => -1, 'title' => $title, 'message' => 'Gagal rekonsiliasi: ' . $e->getMessage(), 'alert' => 'error']);
        }

        $matchedCount = count(array_filter($rows, fn ($r) => $r['match_status'] === 'MATCHED'));
        $message = count($rows) . " baris direkonsiliasi, $matchedCount match.";
        \LogActivity::addToLog($title, "username: $username equalNumber: $header->equal_number Status $message");
        return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => 'success']);
    }

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $header = DB::table('unifikasi_bppu_hdr')->where('id', $id)->first();
        $title = "Delete $this->title";
        if (!$header) {
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => 'Not found']);
        }
        DB::table('unifikasi_bppu_det')->where('equal_number', $header->equal_number)->delete();
        DB::table('unifikasi_bppu_hdr')->where('id', $id)->delete();
        $message = "$header->equal_number Successfully Deleted";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);
    }
}
