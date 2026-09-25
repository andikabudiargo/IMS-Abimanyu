<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use DataTables;
use DB;

// Dashboard gabungan Kas (KM+KK) dan Bank (BM+BK). Create/edit/show/print/delete
// tetap memakai controller lama per tipe supaya kode voucher & jurnal tidak berubah.
class CashBankController extends Controller
{
    // group => [title, [voucher_type => [label, route prefix]]]
    private $groups = [
        'kas'  => ['Kas',  ['KM' => ['Penerimaan', 'kasPenerimaan'], 'KK' => ['Pembayaran', 'kasKeluar']]],
        'bank' => ['Bank', ['BM' => ['Penerimaan', 'bankPenerimaan'], 'BK' => ['Pembayaran', 'bankKeluar']]],
    ];

    private function group($group)
    {
        abort_unless(isset($this->groups[$group]), 404);
        return $this->groups[$group];
    }

    // Tanggal awal yang masih boleh diedit/dihapus, sama dengan logika constructor controller lama.
    private function lockDateFor($code)
    {
        $lock = DB::table('application_lock')->where('code_key', $code)->where('status', '1')->value('lock_date') ?: '2023-01-01';
        $at = date('Y-m-d', strtotime('+1 day', strtotime($lock)));
        return date('Y-m-d') < $at
            ? date('Y-m-01', strtotime('-1 months', strtotime($lock)))
            : date('Y-m-01', strtotime($at));
    }

    public function index($group)
    {
        list($title, $types) = $this->group($group);
        $data['group'] = $group;
        $data['title'] = $title;
        $data['types'] = $types;
        $data['status'] = ['1' => 'NEW', '2' => 'VALIDATED', '3' => 'APPROVED'];
        $data['kolom'] = json_encode([
            ['data' => 'action', 'name' => 'action', 'title' => 'action', 'orderable' => false, 'searchable' => false],
            ['data' => 'tipe', 'name' => 'tipe', 'title' => 'Tipe'],
            ['data' => 'voucher_number', 'name' => 'voucher_number', 'title' => 'Voucher Number'],
            ['data' => 'statusku', 'name' => 'statusku', 'title' => 'Status'],
            ['data' => 'voucher_date', 'name' => 'voucher_date', 'title' => 'Date'],
            ['data' => 'voucher_date_2', 'name' => 'voucher_date_2', 'title' => 'Date', 'visible' => false],
            ['data' => 'party', 'name' => 'party', 'title' => 'Received From / Paid To'],
            ['data' => 'note', 'name' => 'note', 'title' => 'Note'],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'Amount'],
            ['data' => 'period', 'name' => 'period', 'title' => 'Period'],
            ['data' => 'approval_by', 'name' => 'approval_by', 'title' => 'Approved By'],
            ['data' => 'approval_at', 'name' => 'approval_at', 'title' => 'Approved At'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'Created By'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created At'],
        ]);
        return view('accounting.cashbook.index', $data);
    }

    public function list(Request $request, $group)
    {
        list(, $types) = $this->group($group);
        $search = strtolower($request->seachVc);
        $type = array_key_exists($request->searchType, $types) ? $request->searchType : null;

        $from = $to = '';
        if ($request->vcDate) {
            $date = explode('to', $request->vcDate);
            $from = implode('/', array_reverse(explode('-', trim($date[0]))));
            $to = count($date) > 1 ? implode('/', array_reverse(explode('-', trim($date[1])))) : $from;
        }

        $data = DB::table('kas_hdr')
            ->leftJoin('third_party', 'third_party.kode', 'kas_hdr.paid_to')
            ->whereIn('voucher_type', $type ? [$type] : array_keys($types))
            ->where('kas_hdr.status', '<>', '5')
            ->when($search, function ($q) use ($search) { $q->where('voucher_number', 'ilike', "%$search%"); })
            ->when($request->vcDate, function ($q) use ($from, $to) { $q->whereBetween(DB::raw("to_date(voucher_date,'DD-MM-YYYY')"), [$from, $to]); })
            ->when($request->period1, function ($q) use ($request) { $q->whereBetween(DB::raw('period::integer'), [$request->period1, $request->period2 ?: $request->period1]); })
            ->when($request->year, function ($q) use ($request) { $q->where('year', $request->year); })
            ->when($request->searchStatus, function ($q) use ($request) { $q->where('kas_hdr.status', $request->searchStatus); })
            ->select(
                'kas_hdr.*',
                DB::raw("to_char(to_date(voucher_date, 'DD-MM-YYYY'), 'DD/MM/YYYY') as voucher_date"),
                DB::raw("to_date(voucher_date, 'DD-MM-YYYY') as voucher_date_2"),
                'kas_hdr.status as statusku',
                DB::raw("case when voucher_type in ('KK','BK') and coalesce(paid_to,'other') <> 'other' then third_party.nama else kas_hdr.description end as party"),
                DB::raw("(select (select name from users where username = z.username) from approval_history z where module_number = kas_hdr.voucher_number order by approval_order desc limit 1) as approval_by"),
                DB::raw("(select to_char(approval_date::date, 'DD-MM-YYYY') from approval_history z where module_number = kas_hdr.voucher_number order by approval_order desc limit 1) as approval_at")
            )
            ->orderBy('id')
            ->get();

        $locks = [];
        foreach (array_keys($types) as $code) {
            $locks[$code] = $this->lockDateFor($code);
        }

        return Datatables::of($data)
            ->addColumn('tipe', function ($d) use ($types) {
                $cls = substr($d->voucher_type, 1) == 'M' ? 'badge-light-success' : 'badge-light-danger';
                return "<div class='badge $cls'>" . $types[$d->voucher_type][0] . '</div>';
            })
            ->addColumn('action', function ($d) use ($types, $locks) {
                $prefix = $types[$d->voucher_type][1];
                $id = Crypt::encryptString($d->id);
                $item = function ($icon, $label, $href, $attr = '') {
                    return "<a href='$href' $attr class='dropdown-item'><i data-feather='$icon'></i> $label</a>";
                };
                $html = '<div class="d-inline-flex"><a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown"><i data-feather="menu"></i></a><div class="dropdown-menu dropdown-menu-right">';
                $editable = date('Y-m-d', strtotime($d->voucher_date_2)) >= $locks[$d->voucher_type];
                if (in_array($d->statusku, ['1', '2'])) {
                    $html .= $item('check', 'Approve', route("$prefix.edit", ['id' => $id]));
                }
                if ($editable) {
                    $html .= $item('file-text', 'Edit', route("$prefix.edit", ['id' => $id]));
                }
                $html .= $item('list', 'Detail', route("$prefix.show", ['id' => $id]));
                $html .= $item('printer', 'Print', route("$prefix.print", ['id' => $id]), "target='_blank'");
                if ($editable) {
                    $html .= "<a href='javascript:;' id='deleteButton' class='dropdown-item' data-toggle='modal' data-target='#smallModal' data-href='" . route("$prefix.destroy", ['id' => $id]) . "'><i data-feather='trash-2' class='feather-14-red'></i> Delete</a>";
                }
                return $html . '</div></div>';
            })
            ->addColumn('statusku', function ($d) {
                $badges = ['badge-primary', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger', 'badge-dark'];
                $status = ['NEW', 'VALIDATED', 'APPROVED', '', 'DELETED', 'CLOSED'];
                return "<div class='badge " . $badges[$d->status - 1] . "'>" . $status[$d->status - 1] . '</div>';
            })
            ->addColumn('voucher_number', function ($d) use ($types) {
                $href = route($types[$d->voucher_type][1] . '.print', ['id' => Crypt::encryptString($d->id)]);
                return "<a href='$href' target='_blank' style='padding:0px'>$d->voucher_number</a>";
            })
            ->rawColumns(['action', 'tipe', 'statusku', 'voucher_number'])
            ->make(true);
    }
}
