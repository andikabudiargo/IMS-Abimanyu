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
    private $moduleCode;
    // Akun COA PPN KELUARAN (CUSTOMER) -- satu-satunya akun yang dibandingkan di modul ini.
    const COA_ACCOUNT = '2000.14.1';
    // Sama dengan BankReconciliationController/BukuBesarV2Controller: tanggal mulai pembukuan
    // yang diakui, mutasi sebelum ini diabaikan.
    const GL_EPOCH = '2024-01-01';
    private $periodes = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct()
    {
        $this->title = 'Equalisasi PPN PK';
        $this->moduleCode = 'PK';
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
        return "PK-ASN-$year-$month-$padded";
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['subtitle'] = $this->title;
        $data['kolom'] = json_encode([
            ['data' => 'action', 'name' => 'action', 'title' => 'action', 'orderable' => false, 'searchable' => false],
            ['data' => 'equal_number', 'name' => 'equal_number', 'title' => 'Equal Number'],
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
        return view('accounting.ppnPkEqualisasi.create', $data);
    }

    // Tahap 1: upload Excel, parse + hitung ringkasan (read-only, belum ada yang disimpan)
    // supaya user bisa lihat dulu hasilnya sebelum memutuskan simpan -- sama dengan
    // BankReconciliationController::preview().
    public function preview(Request $request)
    {
        $validation = Validator::make($request->all(), [
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

        $totalDpp = round(array_sum(array_column($import->rows, 'dpp')), 2);
        $totalPpn = round(array_sum(array_column($import->rows, 'ppn')), 2);
        $gl = $this->glSummary($periode, $year);
        $selisih = round($totalPpn - $gl['mutasi_kredit'], 2);

        return response()->json([
            'status' => 1,
            'title' => "Preview $this->title",
            'message' => count($import->rows) . ' baris terbaca.',
            'alert' => 'success',
            'filePath' => $path,
            'rows' => $import->rows,
            'totalRows' => count($import->rows),
            'totalDpp' => $totalDpp,
            'totalPpn' => $totalPpn,
            'glMutasiKredit' => $gl['mutasi_kredit'],
            'selisih' => $selisih,
            'match' => abs($selisih) <= 0.01,
        ]);
    }

    // Tahap 2: user sudah lihat hasil preview dan klik Save sendiri -- baru di sini
    // ditulis ke DB. Parse diulang dari file yang sama supaya hasil final tetap
    // konsisten dengan data server (sama dengan BankReconciliationController::store()).
    public function store(Request $request)
    {
        $username = Auth::user()->username;

        $validation = Validator::make($request->all(), [
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

        $totalDpp = round(array_sum(array_column($import->rows, 'dpp')), 2);
        $totalPpn = round(array_sum(array_column($import->rows, 'ppn')), 2);

        AppHelpers::resetCode($this->moduleCode);
        $equalNumber = $this->getLastCode($this->moduleCode);

        DB::beginTransaction();
        try {
            DB::table('ppn_pk_equalisasi_hdr')->insert([
                'equal_number' => $equalNumber,
                'periode' => $periode,
                'year' => $year,
                'coa_account' => self::COA_ACCOUNT,
                'description' => $description,
                'total_dpp' => $totalDpp,
                'total_ppn' => $totalPpn,
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            foreach ($import->rows as $row) {
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
                    'created_by' => $username,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            DB::commit();
            $title = "Save $this->title";
            $message = "$equalNumber tersimpan. " . count($import->rows) . ' baris, total PPN ' . number_format($totalPpn, 2) . '.';
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

    // Mutasi KREDIT akun COA 2000.14.1 di ledger (kas_det/kas_hdr, lihat BukuBesarV2Controller)
    // untuk 1 periode -- dihitung on the fly, tidak disimpan, supaya selalu sinkron dengan
    // posting baru. Sama dengan BankReconciliationController::glSummary(), dipersempit cuma
    // ambil mutasi_kredit karena itu saja yang dipakai buat equalisasi PPN Keluaran.
    private function glSummary(int $periode, int $year): array
    {
        $from = sprintf('%04d-%02d-01', $year, $periode);
        $to = date('Y-m-t', strtotime($from));

        $period = DB::table('kas_det as d')
            ->join('kas_hdr as h', 'h.voucher_number', '=', 'd.voucher_number')
            ->where('d.account', self::COA_ACCOUNT)
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
            ->addColumn('periode', function ($d) {
                return ($this->periodes[(int) $d->periode] ?? $d->periode) . ' / ' . $d->year;
            })
            ->addColumn('total_ppn', function ($d) {
                return number_format($d->total_ppn, 2);
            })
            ->addColumn('match_status', function ($d) {
                $gl = $this->glSummary((int) $d->periode, (int) $d->year);
                $match = abs(round($d->total_ppn - $gl['mutasi_kredit'], 2)) <= 0.01;
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
        $data['gl'] = $this->glSummary((int) $data['header']->periode, (int) $data['header']->year);

        $data['kolomDetail'] = json_encode([
            ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'title' => 'No', 'orderable' => false, 'searchable' => false],
            ['data' => 'masa_pajak', 'name' => 'masa_pajak', 'title' => 'Masa Pajak'],
            ['data' => 'doc_number', 'name' => 'doc_number', 'title' => 'Nomor Dokumen'],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status'],
            ['data' => 'npwp', 'name' => 'npwp', 'title' => 'NPWP'],
            ['data' => 'nama', 'name' => 'nama', 'title' => 'Nama'],
            ['data' => 'jenis_pajak', 'name' => 'jenis_pajak', 'title' => 'Jenis Pajak'],
            ['data' => 'dpp', 'name' => 'dpp', 'title' => 'DPP'],
            ['data' => 'ppn', 'name' => 'ppn', 'title' => 'PPN'],
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
