<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Imports\PpnPkEqualisasiImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use DataTables;
use DB;
use Excel;
use AppHelpers;

class PpnPkEqualisasiController extends Controller
{
    private $title;
    // Sama dengan BankReconciliationController/BukuBesarV2Controller: tanggal mulai pembukuan
    // yang diakui, mutasi sebelum ini diabaikan.
    const GL_EPOCH = '2024-01-01';
    private $periodes = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    // Satu modul, dua tipe equalisasi -- dibedakan dari akun COA & arah mutasi yang
    // dibandingkan (PK/Keluaran = kredit COA penjualan, PM/Masukan = debet COA pembelian).
    // Layout kolom Excel-nya sama persis (lihat PpnPkEqualisasiImport), jadi tidak perlu
    // import class terpisah.
    const TYPES = [
        'PK' => [
            'label' => 'PPN Keluaran',
            'title' => 'Equalisasi PPn-PK',
            'coa_account' => '2000.14.1',
            'mutasi_field' => 'mutasi_kredit',
            'mutasi_label' => 'Mutasi Kredit',
            'file_label' => 'Faktur Pajak Keluaran',
        ],
        'PM' => [
            'label' => 'PPN Masukan',
            'title' => 'Equalisasi PPn-PM',
            'coa_account' => '1100.73',
            'mutasi_field' => 'mutasi_debet',
            'mutasi_label' => 'Mutasi Debet',
            'file_label' => 'Faktur Pajak Masukan',
        ],
    ];

    public function __construct()
    {
        $this->title = 'Equalisasi PPN';
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
        $padded = str_pad($newCode, 4, '0', STR_PAD_LEFT);
        return "$key-ASN-$year-$month-$padded";
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['subtitle'] = $this->title;
        $data['kolom'] = json_encode([
            ['data' => 'action', 'name' => 'action', 'title' => 'action', 'orderable' => false, 'searchable' => false],
            ['data' => 'equal_number', 'name' => 'equal_number', 'title' => 'Equal Number'],
            ['data' => 'type', 'name' => 'type', 'title' => 'Type'],
            ['data' => 'periode', 'name' => 'periode', 'title' => 'Periode'],
            ['data' => 'description', 'name' => 'description', 'title' => 'Description'],
            ['data' => 'total_ppn', 'name' => 'total_ppn', 'title' => 'Total PPN (Excel)'],
            ['data' => 'match_status', 'name' => 'match_status', 'title' => 'Status'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'Created By'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created At'],
        ]);
        return view('accounting.ppnPkEqualisasi.index', $data);
    }

    public function create()
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $data['periodes'] = $this->periodes;
        $data['types'] = self::TYPES;
        return view('accounting.ppnPkEqualisasi.create', $data);
    }

    // Tahap 1: upload Excel, parse + hitung ringkasan (read-only, belum ada yang disimpan)
    // supaya user bisa lihat dulu hasilnya sebelum memutuskan simpan -- sama dengan
    // BankReconciliationController::preview().
    public function preview(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'type' => 'required|in:PK,PM',
            'periode' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            // mimes:xls,xlsx di-skip -- content-sniffed MIME untuk xlsx export dari portal
            // pajak kadang tidak kebaca sebagai office format (false reject), jadi dicek
            // dari ekstensi nama file saja, sama seperti yang sebenarnya dibutuhkan.
            'statement' => ['required', 'file', function ($attr, $value, $fail) {
                if (!in_array(strtolower($value->getClientOriginalExtension()), ['xls', 'xlsx'])) {
                    $fail('The statement must be a file of type: xls, xlsx.');
                }
            }],
        ]);

        if ($validation->fails()) {
            $error_array = [];
            foreach ($validation->messages()->getMessages() as $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'title' => "Preview $this->title", 'message' => $error_array, 'alert' => 'error']);
        }

        $type = $request->type;
        $typeInfo = self::TYPES[$type];
        $periode = (int) $request->periode;
        $year = (int) $request->year;

        $file = $request->file('statement');
        $path = $file->store('ppn-pk-equalisasi', 'local');

        try {
            $import = new PpnPkEqualisasiImport();
            Excel::import($import, storage_path('app/' . $path));
        } catch (\Exception $e) {
            return response()->json(['status' => -1, 'title' => "Preview $this->title", 'message' => 'Gagal membaca Excel: ' . $e->getMessage(), 'alert' => 'error']);
        }

        if (empty($import->rows)) {
            return response()->json(['status' => 2, 'title' => "Preview $this->title", 'message' => 'Tidak ada baris data yang terbaca dari Excel ini.', 'alert' => 'warning']);
        }

        $rows = $this->matchRows($import->rows, $type);
        $totalDpp = round(array_sum(array_column($rows, 'dpp')), 2);
        $totalPpn = round(array_sum(array_column($rows, 'ppn')), 2);
        $matchedCount = count(array_filter($rows, fn ($r) => $r['match_status'] === 'MATCHED'));
        $gl = $this->glSummary($periode, $year, $typeInfo['coa_account']);
        $glMutasi = $gl[$typeInfo['mutasi_field']];
        $selisih = round($totalPpn - $glMutasi, 2);

        return response()->json([
            'status' => 1,
            'title' => "Preview $this->title",
            'message' => count($rows) . " baris terbaca, $matchedCount otomatis match.",
            'alert' => 'success',
            'filePath' => $path,
            'rows' => $rows,
            'totalRows' => count($rows),
            'matchedCount' => $matchedCount,
            'totalDpp' => $totalDpp,
            'totalPpn' => $totalPpn,
            'glMutasiKredit' => $glMutasi,
            'selisih' => $selisih,
            'match' => abs($selisih) <= 0.01,
        ]);
    }

    // Nomor faktur yang disimpan di voucher (faktur_pajak/tax_inv_number) selalu lebih
    // pendek daripada nomor lengkap di Excel -- prefix Kode Transaksi+Status DJP-nya tidak
    // ikut tersimpan, dan panjangnya beda-beda antar periode (pernah 10 digit, pernah 13
    // digit). Jadi dicocokkan sebagai AKHIRAN dari nomor faktur lengkap di Excel (bukan
    // exact match). Guard panjang >= 8 biar suffix pendek tidak nyangkut ke banyak faktur.
    private function findBySuffix(string $table, string $numberColumn, string $norm)
    {
        if (strlen($norm) < 8) {
            return null;
        }

        return DB::table($table)
            ->whereRaw("$numberColumn is not null and $numberColumn <> '' and length(regexp_replace($numberColumn, '[^0-9A-Za-z]', '', 'g')) >= 8 and ? ilike '%' || regexp_replace($numberColumn, '[^0-9A-Za-z]', '', 'g')", [$norm])
            ->where('status', '<>', '5')
            ->first();
    }

    // Cocokkan tiap baris Excel ke voucher yang menerbitkannya, lalu ke posting COA PPN
    // (kredit untuk PK, debet untuk PM) punya voucher itu di kas_det/kas_hdr -- mirip
    // BankReconciliationController::matchRows(), tapi kuncinya nomor faktur (suffix match,
    // lihat findBySuffix()), bukan tanggal+nominal. Read-only (cuma SELECT), dipakai di
    // preview() maupun store() biar hasil yang ditampilkan ke user sama persis dengan yang
    // akhirnya disimpan.
    // PK -> invoice_hdr/debit_note_hdr.faktur_pajak, posting kredit. PM -> ap_invoice.
    // tax_inv_number, posting debet (lihat AccountPayableController, posting VAT AP invoice).
    private function matchRows(array $rows, string $type): array
    {
        $coaAccount = self::TYPES[$type]['coa_account'];
        $mutasiCol = $type === 'PM' ? 'd.debit' : 'd.credit';

        foreach ($rows as &$row) {
            $candidate = null;
            $voucherType = null;
            $norm = preg_replace('/[^0-9A-Za-z]/', '', (string) $row['doc_number']);

            if ($type === 'PK') {
                $candidate = $this->findBySuffix('invoice_hdr', 'faktur_pajak', $norm);
                $voucherType = 'INV';
                if ($candidate) {
                    $candidate->voucher_number = $candidate->invoice_number;
                } else {
                    $candidate = $this->findBySuffix('debit_note_hdr', 'faktur_pajak', $norm);
                    $voucherType = 'INV-DN';
                    if ($candidate) {
                        $candidate->voucher_number = $candidate->dn_number;
                    }
                }
            } elseif ($type === 'PM') {
                $candidate = $this->findBySuffix('ap_invoice', 'tax_inv_number', $norm);
                $voucherType = 'AP';
                if ($candidate) {
                    $candidate->voucher_number = $candidate->ap_number;
                }
            }

            if ($candidate) {
                $glAmount = (float) DB::table('kas_det as d')
                    ->join('kas_hdr as h', 'h.voucher_number', '=', 'd.voucher_number')
                    ->where('d.account', $coaAccount)
                    ->where('h.status', '<>', '5')
                    ->where('d.voucher_number', $candidate->voucher_number)
                    ->sum($mutasiCol);

                $route = ['INV' => 'invoice.edit', 'INV-DN' => 'debitNote.edit', 'AP' => 'accountPayable.edit'][$voucherType];
                $row['matched_voucher_type'] = $voucherType;
                $row['matched_voucher_number'] = $candidate->voucher_number;
                $row['matched_voucher_id'] = $candidate->id;
                $row['voucher_link'] = route($route, ['id' => Crypt::encryptString((string) $candidate->id)]);
                $row['gl_credit'] = $glAmount;
                $row['match_status'] = abs(round($row['ppn'] - $glAmount, 2)) <= 0.01 ? 'MATCHED' : 'UNMATCHED';
            } else {
                $row['matched_voucher_type'] = null;
                $row['matched_voucher_number'] = null;
                $row['matched_voucher_id'] = null;
                $row['voucher_link'] = null;
                $row['gl_credit'] = null;
                $row['match_status'] = 'UNMATCHED';
            }
        }
        unset($row);

        return $rows;
    }

    // Tahap 2: user sudah lihat hasil preview dan klik Save sendiri -- baru di sini
    // ditulis ke DB. Parse diulang dari file yang sama supaya hasil final tetap
    // konsisten dengan data server (sama dengan BankReconciliationController::store()).
    public function store(Request $request)
    {
        $username = Auth::user()->username;

        $validation = Validator::make($request->all(), [
            'type' => 'required|in:PK,PM',
            'periode' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'filePath' => 'required|string',
        ]);

        if ($validation->fails()) {
            $error_array = [];
            foreach ($validation->messages()->getMessages() as $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => $error_array, 'alert' => 'error']);
        }

        $type = $request->type;
        $typeInfo = self::TYPES[$type];
        $periode = (int) $request->periode;
        $year = (int) $request->year;
        $description = $request->description;
        $filePath = $request->filePath;

        // filePath datang dari response preview(), bukan input bebas dari client --
        // dibatasi ke folder sendiri biar tidak bisa dipakai buat baca file sembarangan.
        if (strpos($filePath, 'ppn-pk-equalisasi/') !== 0) {
            return response()->json(['status' => -1, 'title' => "Save $this->title", 'message' => 'File tidak valid.', 'alert' => 'error']);
        }
        $fullPath = storage_path('app/' . $filePath);
        if (!is_file($fullPath)) {
            return response()->json(['status' => -1, 'title' => "Save $this->title", 'message' => 'File upload sudah tidak ada, silakan upload ulang dari awal.', 'alert' => 'error']);
        }

        try {
            $import = new PpnPkEqualisasiImport();
            Excel::import($import, $fullPath);
        } catch (\Exception $e) {
            return response()->json(['status' => -1, 'title' => "Save $this->title", 'message' => 'Gagal membaca Excel: ' . $e->getMessage(), 'alert' => 'error']);
        }

        if (empty($import->rows)) {
            return response()->json(['status' => 2, 'title' => "Save $this->title", 'message' => 'Tidak ada baris data yang terbaca dari Excel ini.', 'alert' => 'warning']);
        }

        $rows = $this->matchRows($import->rows, $type);
        $totalDpp = round(array_sum(array_column($rows, 'dpp')), 2);
        $totalPpn = round(array_sum(array_column($rows, 'ppn')), 2);

        AppHelpers::resetCode($type);
        $equalNumber = $this->getLastCode($type);

        DB::beginTransaction();
        try {
            DB::table('ppn_pk_equalisasi_hdr')->insert([
                'equal_number' => $equalNumber,
                'type' => $type,
                'periode' => $periode,
                'year' => $year,
                'coa_account' => $typeInfo['coa_account'],
                'description' => $description,
                'total_dpp' => $totalDpp,
                'total_ppn' => $totalPpn,
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $matchedCount = 0;
            foreach ($rows as $row) {
                DB::table('ppn_pk_equalisasi_det')->insertOrIgnore([
                    'equal_number' => $equalNumber,
                    'masa_pajak' => $row['masa_pajak'],
                    'doc_number' => $row['doc_number'],
                    'status' => $row['status'],
                    'npwp' => $row['npwp'],
                    'nama' => $row['nama'],
                    'kode' => $row['kode'],
                    'jenis_pajak' => $row['jenis_pajak'],
                    'sifat' => $row['sifat'],
                    'dpp' => $row['dpp'],
                    'ppn' => $row['ppn'],
                    'match_status' => $row['match_status'],
                    'matched_voucher_type' => $row['matched_voucher_type'],
                    'matched_voucher_number' => $row['matched_voucher_number'],
                    'matched_voucher_id' => $row['matched_voucher_id'],
                    'gl_credit' => $row['gl_credit'],
                    'created_by' => $username,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                if ($row['match_status'] === 'MATCHED') {
                    $matchedCount++;
                }
            }

            DB::commit();
            $title = "Save $this->title";
            $message = "$equalNumber tersimpan. " . count($rows) . " baris, $matchedCount otomatis match, total PPN " . number_format($totalPpn, 2) . '.';
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => 'success', 'equalNumber' => $equalNumber]);
        } catch (\Exception $e) {
            DB::rollBack();
            $title = "Save $this->title";
            $message = 'Gagal menyimpan: ' . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status' => -1, 'title' => $title, 'message' => $message, 'alert' => 'error']);
        }
    }

    // Mutasi akun COA (PPN Keluaran/Masukan tergantung tipe) di ledger (kas_det/kas_hdr,
    // lihat BukuBesarV2Controller) untuk 1 periode -- dihitung on the fly, tidak disimpan,
    // supaya selalu sinkron dengan posting baru. Sama dengan
    // BankReconciliationController::glSummary(), tetap kembalikan debet & kredit, dipilih
    // salah satunya sesuai mutasi_field tipe yang bersangkutan.
    private function glSummary(int $periode, int $year, string $coaAccount): array
    {
        $from = sprintf('%04d-%02d-01', $year, $periode);
        $to = date('Y-m-t', strtotime($from));

        $period = DB::table('kas_det as d')
            ->join('kas_hdr as h', 'h.voucher_number', '=', 'd.voucher_number')
            ->where('d.account', $coaAccount)
            ->where('h.status', '<>', '5')
            ->whereRaw("to_date(h.voucher_date,'DD-MM-YYYY') between ? and ?", [$from, $to])
            ->selectRaw('coalesce(sum(d.debit), 0) as debet, coalesce(sum(d.credit), 0) as kredit')
            ->first();

        return [
            'mutasi_debet' => (float) $period->debet,
            'mutasi_kredit' => (float) $period->kredit,
        ];
    }

    public function list(Request $request)
    {
        $search = strtolower($request->searchEqualNumber);
        $searchPeriode = $request->searchPeriode;

        $data = DB::table('ppn_pk_equalisasi_hdr')
            ->when($search, function ($q) use ($search) { $q->where('equal_number', 'ilike', "%$search%"); })
            ->when($searchPeriode, function ($q) use ($searchPeriode) { $q->where('periode', $searchPeriode); })
            ->orderBy('id', 'desc')
            ->get();

        return Datatables::of($data)
            ->addColumn('action', function ($d) {
                $id = Crypt::encryptString($d->id);
                $buttons = '<div class="d-inline-flex"><a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a><div class="dropdown-menu dropdown-menu-right">';
                $buttons .= '<a href="' . route('ppnPkEqualisasi.show', ['id' => $id]) . '" class="dropdown-item"><i data-feather="list"></i> ' . __('Detail') . '</a>';
                $buttons .= "<a href='javascript:;' class='dropdown-item' data-size='sm' data-ajax-delete='true'
                    data-confirm='Are you sure want to Delete?|This action can not be undone. Do you want to continue?'
                    data-confirm-yes='document.getElementById(\"delete-form-{$d->id}\").submit();' data-modal-id='{$d->id}'
                    data-url='" . route('ppnPkEqualisasi.destroy', ['id' => $id]) . "'><i data-feather='trash-2' class='feather-14-red'></i> " . __('Delete') . '</a>';
                return $buttons . '</div></div>';
            })
            ->addColumn('equal_number', function ($d) {
                return '<a href="' . route('ppnPkEqualisasi.show', ['id' => Crypt::encryptString($d->id)]) . '">' . $d->equal_number . '</a>';
            })
            ->addColumn('type', function ($d) {
                return self::TYPES[$d->type]['label'] ?? $d->type;
            })
            ->addColumn('periode', function ($d) {
                return ($this->periodes[(int) $d->periode] ?? $d->periode) . ' / ' . $d->year;
            })
            ->addColumn('total_ppn', function ($d) {
                return number_format($d->total_ppn, 2);
            })
            ->addColumn('match_status', function ($d) {
                $typeInfo = self::TYPES[$d->type] ?? self::TYPES['PK'];
                $gl = $this->glSummary((int) $d->periode, (int) $d->year, $d->coa_account);
                $match = abs(round($d->total_ppn - $gl[$typeInfo['mutasi_field']], 2)) <= 0.01;
                $color = $match ? 'success' : 'danger';
                $label = $match ? 'MATCH' : 'NOT MATCH';
                return "<div class='badge badge-{$color}'>{$label}</div>";
            })
            ->rawColumns(['action', 'equal_number', 'match_status'])
            ->make(true);
    }

    public function show(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";
        $data['header'] = DB::table('ppn_pk_equalisasi_hdr')->where('id', $id)->first();
        abort_unless($data['header'], 404);
        $data['id'] = $id;
        $data['periodes'] = $this->periodes;
        $data['typeInfo'] = self::TYPES[$data['header']->type] ?? self::TYPES['PK'];
        $gl = $this->glSummary((int) $data['header']->periode, (int) $data['header']->year, $data['header']->coa_account);
        $data['gl'] = $gl;
        $data['glMutasi'] = $gl[$data['typeInfo']['mutasi_field']];

        $data['kolomDetail'] = json_encode([
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => 'No', 'orderable' => false, 'searchable' => false],
            ['data' => 'masa_pajak', 'name' => 'masa_pajak', 'title' => 'Masa Pajak'],
            ['data' => 'doc_number', 'name' => 'doc_number', 'title' => 'Nomor Faktur'],
            ['data' => 'npwp', 'name' => 'npwp', 'title' => 'NPWP'],
            ['data' => 'nama', 'name' => 'nama', 'title' => 'Nama'],
            ['data' => 'dpp', 'name' => 'dpp', 'title' => 'DPP'],
            ['data' => 'ppn', 'name' => 'ppn', 'title' => 'PPN (Excel)'],
            ['data' => 'match_status', 'name' => 'match_status', 'title' => 'Status'],
            ['data' => 'matched_voucher_number', 'name' => 'matched_voucher_number', 'title' => 'Voucher GL'],
            ['data' => 'gl_credit', 'name' => 'gl_credit', 'title' => 'Kredit GL'],
        ]);

        return view('accounting.ppnPkEqualisasi.show', $data);
    }

    public function listDetail(Request $request)
    {
        // $id di sini sudah plain (bukan terenkripsi) -- show() mengirim id hasil decrypt
        // ke view, lalu dipakai lagi buat endpoint AJAX ini (sama dengan BankReconciliationController).
        $id = $request->id;
        $header = DB::table('ppn_pk_equalisasi_hdr')->where('id', $id)->first();
        abort_unless($header, 404);

        $data = DB::table('ppn_pk_equalisasi_det')
            ->where('equal_number', $header->equal_number)
            ->orderBy('id')
            ->get();

        return Datatables::of($data)
            ->addIndexColumn()
            ->addColumn('dpp', function ($d) { return number_format($d->dpp, 2); })
            ->addColumn('ppn', function ($d) { return number_format($d->ppn, 2); })
            ->addColumn('match_status', function ($d) {
                $color = $d->match_status === 'MATCHED' ? 'success' : 'danger';
                $label = $d->match_status === 'MATCHED' ? 'MATCH' : 'NOT MATCH';
                return "<div class='badge badge-{$color}'>{$label}</div>";
            })
            ->addColumn('matched_voucher_number', function ($d) {
                if (!$d->matched_voucher_number) {
                    return '-';
                }
                $route = ['INV' => 'invoice.edit', 'INV-DN' => 'debitNote.edit', 'AP' => 'accountPayable.edit'][$d->matched_voucher_type] ?? 'invoice.edit';
                $url = route($route, ['id' => Crypt::encryptString((string) $d->matched_voucher_id)]);
                return "<a href='$url' target='_blank'>{$d->matched_voucher_number}</a> ({$d->matched_voucher_type})";
            })
            ->addColumn('gl_credit', function ($d) { return $d->gl_credit !== null ? number_format($d->gl_credit, 2) : '-'; })
            ->rawColumns(['match_status', 'matched_voucher_number'])
            ->make(true);
    }

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $header = DB::table('ppn_pk_equalisasi_hdr')->where('id', $id)->first();

        if ($header) {
            DB::table('ppn_pk_equalisasi_det')->where('equal_number', $header->equal_number)->delete();
            DB::table('ppn_pk_equalisasi_hdr')->where('id', $id)->delete();
            $title = "Delete $this->title";
            $message = "$header->equal_number Successfully Deleted";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);
        }

        return redirect()->back()->with(['title' => "Delete $this->title", 'alert' => 'warning', 'message' => 'Not found']);
    }
}
