<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Imports\DeliveryScheduleImport;
use App\Exports\DeliveryScheduleTemplateExport;
use DataTables;
use Excel;
use DB;

class DeliveryScheduleController extends Controller
{
    private $title = "Delivery Schedule";

    // Converts a "DD-MM-YYYY to DD-MM-YYYY" flatpickr range into [YYYY/MM/DD, YYYY/MM/DD],
    // matching SalesOrderController@list's convention for comparing against the
    // text-stored so_date via to_date(so_date,'DD-MM-YYYY').
    private function parseRange($raw)
    {
        $fromDate = "";
        $toDate = "";
        if ($raw) {
            $date = explode("to", $raw);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = count($date) > 1 ? implode("/", array_reverse(explode("-", trim($date[1])))) : $fromDate;
        }
        return [$fromDate, $toDate];
    }

    // Sorts "DD-MM-YYYY" strings chronologically (DeliveryMonitoringController@sortDates).
    private function sortDates(array $dates)
    {
        usort($dates, fn($a, $b) => strtotime(implode('-', array_reverse(explode('-', $a)))) <=> strtotime(implode('-', array_reverse(explode('-', $b)))));
        return $dates;
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['custs'] = DB::table('third_party')
        ->where('third_party_type', '=', 'cust')
        ->orderBy('nama')
        ->get();

        $data['kolom'] = json_encode([
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false],
            ['data' => 'cust', 'name' => 'cust_name', 'title' => 'Customer'],
            ['data' => 'total_so', 'name' => 'total_so', 'title' => 'Total SO'],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status', 'orderable' => false],
            ['data' => 'updated_by', 'name' => 'updated_by', 'title' => 'Updated By'],
            ['data' => 'updated_at', 'name' => 'updated_at', 'title' => 'Updated At'],
        ]);

        return view('deliverySchedule.index', $data);
    }

    public function list(Request $request)
    {
        [$fromDate, $toDate] = $this->parseRange($request->periode);
        $searchCustomer = $request->searchCustomer;

        $data = DB::table('sales_order_hdr')
        ->select(
            'sales_order_hdr.customer_id',
            'third_party.nama as cust_name',
            DB::raw('count(*) as total_so'),
            DB::raw("sum(case when exists (select 1 from delivery_schedule_det d where d.so_code = sales_order_hdr.so_code) then 1 else 0 end) as scheduled_so"),
            DB::raw("(select d2.updated_by from delivery_schedule_det d2
                        where d2.so_code in (select so_code from sales_order_hdr h2 where h2.customer_id = sales_order_hdr.customer_id and h2.delivery_type = 'PO')
                        order by d2.updated_at desc limit 1) as updated_by"),
            DB::raw("(select d2.updated_at from delivery_schedule_det d2
                        where d2.so_code in (select so_code from sales_order_hdr h2 where h2.customer_id = sales_order_hdr.customer_id and h2.delivery_type = 'PO')
                        order by d2.updated_at desc limit 1) as updated_at")
        )
        ->leftJoin('third_party', 'third_party.kode', '=', 'sales_order_hdr.customer_id')
        ->where('sales_order_hdr.delivery_type', 'PO')
        ->where(function ($query) use ($searchCustomer, $fromDate, $toDate) {
            $searchCustomer ? $query->where('customer_id', $searchCustomer) : '';
            $fromDate ? $query->whereBetween(DB::raw("to_date(so_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->groupBy('sales_order_hdr.customer_id', 'third_party.nama')
        ->orderBy('third_party.nama')
        ->get();

        return Datatables::of($data)
        ->addColumn('cust', function ($row) {
            return $row->customer_id.' - '.$row->cust_name;
        })
        ->addColumn('status', function ($row) {
            if ($row->scheduled_so == 0) {
                return '<div class="badge badge-secondary">Pending</div>';
            }
            if ($row->scheduled_so == $row->total_so) {
                return '<div class="badge badge-success">Scheduled</div>';
            }
            return '<div class="badge badge-warning">Partially Scheduled</div>';
        })
        ->addColumn('action', function ($row) {
            $isScheduled = $row->scheduled_so == $row->total_so;
            return '<a href="'.route('deliverySchedule.create', ['customer_id' => $row->customer_id]).'" class="btn btn-sm btn-primary">
                        <i data-feather="'.($isScheduled ? 'list' : 'calendar').'"></i> '.($isScheduled ? 'Detail' : 'Create Schedule').'
                    </a>';
        })
        ->editColumn('updated_at', function ($row) {
            return $row->updated_at ? date('d-m-Y H:i', strtotime($row->updated_at)) : '';
        })
        ->rawColumns(['action', 'status'])
        ->make(true);
    }

    // Detail toggle: grouped-by-customer article x schedule_date matrix, mirrors
    // DeliveryMonitoringController@groups so the UI matches Delivery Monitoring.
    public function matrix(Request $request)
    {
        [$fromDate, $toDate] = $this->parseRange($request->periode);
        $searchCustomer = $request->searchCustomer;

        $rows = DB::table('delivery_schedule_det')
        ->join('sales_order_hdr', 'sales_order_hdr.so_code', '=', 'delivery_schedule_det.so_code')
        ->leftJoin('third_party', 'third_party.kode', '=', 'sales_order_hdr.customer_id')
        ->leftJoin('article', 'article.article_code', '=', 'delivery_schedule_det.article_code')
        ->where('sales_order_hdr.delivery_type', 'PO')
        ->where(function ($query) use ($searchCustomer, $fromDate, $toDate) {
            $searchCustomer ? $query->where('sales_order_hdr.customer_id', $searchCustomer) : '';
            $fromDate ? $query->whereBetween(DB::raw("to_date(sales_order_hdr.so_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->select(
            'sales_order_hdr.customer_id',
            'third_party.nama as cust_name',
            'delivery_schedule_det.article_code',
            'article.article_alternative_code',
            'article.article_desc',
            'delivery_schedule_det.schedule_date',
            'delivery_schedule_det.qty'
        )
        ->get();

        $groups = [];
        foreach ($rows as $r) {
            $key = $r->customer_id;
            $date = date('d-m-Y', strtotime($r->schedule_date));
            $rowKey = $r->article_code;
            $groups[$key]['name'] = $r->cust_name ?? $r->customer_id;
            $groups[$key]['articles'][$rowKey] = trim(($r->article_alternative_code ?? $r->article_code).' - '.$r->article_desc, ' -');
            $groups[$key]['dates'][$date] = true;
            $groups[$key]['matrix'][$rowKey][$date] = ($groups[$key]['matrix'][$rowKey][$date] ?? 0) + (float) $r->qty;
        }
        foreach ($groups as &$g) {
            ksort($g['articles']);
            $g['dates'] = $this->sortDates(array_keys($g['dates']));
        }
        uasort($groups, fn($a, $b) => strcmp($a['name'], $b['name']));

        return response()->json(['groups' => array_values($groups)]);
    }

    public function create(Request $request)
    {
        $customerId = $request->customer_id;

        $customer = DB::table('third_party')->where('kode', $customerId)->first();

        $soCodes = DB::table('sales_order_hdr')
        ->where('customer_id', $customerId)
        ->where('delivery_type', 'PO')
        ->pluck('so_code');

        if (!$customer || $soCodes->isEmpty()) {
            return redirect()->route('deliverySchedules.index')->with(['alert' => 'warning', 'message' => "No PO-type Sales Order found for customer $customerId"]);
        }

        $data['title'] = $this->title;
        $data['customer'] = $customer;
        $data['soCodes'] = $soCodes;

        $data['articles'] = DB::table('sales_order_det')
        ->leftJoin('article', 'article.article_code', '=', 'sales_order_det.article_code')
        ->whereIn('sales_order_det.so_code', $soCodes)
        ->select('sales_order_det.so_code', 'sales_order_det.article_code', 'sales_order_det.qty', 'sales_order_det.uom', 'article.article_desc', 'article.article_alternative_code')
        ->orderBy('sales_order_det.so_code')
        ->get();

        $data['existing'] = DB::table('delivery_schedule_det')
        ->whereIn('so_code', $soCodes)
        ->orderBy('schedule_date')
        ->get();

        return view('deliverySchedule.create', $data);
    }

    public function save(Request $request)
    {
        $username = Auth::user()->username;
        $customerId = $request->customerId;
        $rows = json_decode($request->rows);

        $validation = Validator::make($request->all(), [
            'customerId' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => ['Customer is required'], 'alert' => 'error']);
        }

        $soCodes = DB::table('sales_order_hdr')
        ->where('customer_id', $customerId)
        ->where('delivery_type', 'PO')
        ->pluck('so_code')
        ->toArray();

        $orderQty = DB::table('sales_order_det')
        ->whereIn('so_code', $soCodes)
        ->get()
        ->keyBy(fn($r) => $r->so_code.'|'.$r->article_code);

        $totalPerKey = [];
        $dataSet = [];
        foreach ($rows as $val) {
            $qty = (float) $val->qty;
            if ($qty <= 0) {
                continue;
            }
            $key = $val->so_code.'|'.$val->article_code;
            $totalPerKey[$key] = ($totalPerKey[$key] ?? 0) + $qty;
            $dataSet[] = [
                'so_code' => $val->so_code,
                'article_code' => $val->article_code,
                // JS sends DD-MM-YYYY; store as a real date (Postgres won't parse DD-MM-YYYY by default)
                'schedule_date' => \Carbon\Carbon::createFromFormat('d-m-Y', $val->schedule_date)->format('Y-m-d'),
                'qty' => $qty,
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        $errors = [];
        foreach ($orderQty as $key => $row) {
            $scheduled = round($totalPerKey[$key] ?? 0, 2);
            if ($scheduled != round((float) $row->qty, 2)) {
                $errors[] = "{$row->so_code} / {$row->article_code}: scheduled qty ($scheduled) does not match order qty ({$row->qty})";
            }
        }

        if (!empty($errors)) {
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => $errors, 'alert' => 'error']);
        }

        DB::beginTransaction();
        try {
            DB::table('delivery_schedule_det')->whereIn('so_code', $soCodes)->delete();
            if (!empty($dataSet)) {
                DB::table('delivery_schedule_det')->insert($dataSet);
            }
            DB::commit();
            $message = "Delivery Schedule for customer $customerId is successfully saved";
            \LogActivity::addToLog("Save $this->title", "username: $username Status $message");
            return response()->json(['status' => 1, 'title' => "Save $this->title", 'message' => $message, 'alert' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = "Delivery Schedule for customer $customerId is failed to save";
            \LogActivity::addToLog("Save $this->title", "username: $username Status $message");
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => [$message], 'alert' => 'error']);
        }
    }

    // Builds the (so_code, article_code, article_alternative_code) pairs eligible
    // for a customer's Delivery Schedule - shared by exportTemplate/importExcel.
    private function articlePairs($customerId)
    {
        $soCodes = DB::table('sales_order_hdr')
        ->where('customer_id', $customerId)
        ->where('delivery_type', 'PO')
        ->pluck('so_code');

        return DB::table('sales_order_det')
        ->leftJoin('article', 'article.article_code', '=', 'sales_order_det.article_code')
        ->whereIn('sales_order_det.so_code', $soCodes)
        ->select('sales_order_det.so_code', 'sales_order_det.article_code', DB::raw('coalesce(article.article_alternative_code, sales_order_det.article_code) as alt_code'))
        ->orderBy('sales_order_det.so_code')
        ->orderBy('sales_order_det.article_code')
        ->get();
    }

    public function exportTemplate(Request $request)
    {
        $customerId = $request->customer_id;
        $periode = $request->periode;

        if (!$periode || strpos($periode, 'to') === false) {
            abort(422, 'Please pick the Delivery Date Range before downloading the template');
        }

        [$fromRaw, $toRaw] = array_map('trim', explode('to', $periode, 2));
        $from = \Carbon\Carbon::createFromFormat('d-m-Y', $fromRaw);
        $to = \Carbon\Carbon::createFromFormat('d-m-Y', $toRaw);

        $dates = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $dates[] = $d->format('d-m-Y');
        }

        $pairs = $this->articlePairs($customerId);

        $rows = [];
        foreach ($pairs as $p) {
            foreach ($dates as $d) {
                $rows[] = (object) ['so_code' => $p->so_code, 'article_code' => $p->alt_code, 'schedule_date' => $d];
            }
        }

        return Excel::download(new DeliveryScheduleTemplateExport($rows), "delivery_schedule_template_$customerId.xlsx");
    }

    public function importExcel(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'file' => 'required|mimes:xls,xlsx|max:5120',
            'customerId' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => ['A valid Customer and xls/xlsx file are required']]);
        }

        $customerId = $request->customerId;

        // Template shows article_alternative_code, so map it back to the real article_code.
        $lookup = [];
        foreach ($this->articlePairs($customerId) as $p) {
            $lookup[$p->so_code.'|'.$p->alt_code] = $p->article_code;
        }

        $import = new DeliveryScheduleImport();
        Excel::import($import, $request->file('file'));

        $rows = [];
        $errors = [];
        foreach ($import->rows as $row) {
            $key = $row['so_code'].'|'.$row['article_code'];
            if (!isset($lookup[$key])) {
                $errors[] = "{$row['so_code']} / {$row['article_code']} does not belong to this customer's PO Sales Orders";
                continue;
            }
            if (!$row['schedule_date']) {
                $errors[] = "{$row['so_code']} / {$row['article_code']}: invalid date, expected format DD-MM-YYYY";
                continue;
            }
            $row['article_code'] = $lookup[$key];
            $rows[] = $row;
        }

        return response()->json(['status' => 1, 'rows' => $rows, 'errors' => $errors]);
    }
}
