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

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['custs'] = DB::table('third_party')
        ->where('third_party_type', '=', 'cust')
        ->orderBy('nama')
        ->get();

        $data['kolom'] = json_encode([
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false],
            ['data' => 'customer_id', 'name' => 'customer_id', 'title' => 'Customer Code'],
            ['data' => 'cust_name', 'name' => 'cust_name', 'title' => 'Customer Name'],
            ['data' => 'total_so', 'name' => 'total_so', 'title' => 'Total SO'],
            ['data' => 'scheduled_so', 'name' => 'scheduled_so', 'title' => 'Scheduled'],
        ]);

        $data['kolomDetail'] = json_encode([
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false],
            ['data' => 'so_code', 'name' => 'so_code', 'title' => 'SO Code'],
            ['data' => 'po_number', 'name' => 'po_number', 'title' => 'PO Number'],
            ['data' => 'so_date', 'name' => 'so_date', 'title' => 'SO Date'],
            ['data' => 'total_qty', 'name' => 'total_qty', 'title' => 'Order Qty'],
            ['data' => 'status', 'name' => 'status', 'title' => 'Schedule Status'],
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
            DB::raw("sum(case when exists (select 1 from delivery_schedule_det d where d.so_code = sales_order_hdr.so_code) then 1 else 0 end) as scheduled_so")
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
        ->addColumn('action', function ($row) {
            return '<a href="javascript:;" class="btn btn-sm btn-primary" onclick="detailCustomer(\''.$row->customer_id.'\',\''.$row->cust_name.'\')">
                        <i data-feather="list"></i> Detail
                    </a>';
        })
        ->addColumn('scheduled_so', function ($row) {
            return $row->scheduled_so.' / '.$row->total_so;
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        [$fromDate, $toDate] = $this->parseRange($request->periode);
        $customerId = $request->customerId;

        $data = DB::table('sales_order_hdr')
        ->select(
            'sales_order_hdr.so_code',
            'sales_order_hdr.po_number',
            'sales_order_hdr.so_date',
            DB::raw('(select coalesce(sum(qty),0) from sales_order_det where so_code = sales_order_hdr.so_code) as total_qty'),
            DB::raw("case when exists (select 1 from delivery_schedule_det d where d.so_code = sales_order_hdr.so_code) then 1 else 0 end as is_scheduled")
        )
        ->where('delivery_type', 'PO')
        ->where('customer_id', $customerId)
        ->where(function ($query) use ($fromDate, $toDate) {
            $fromDate ? $query->whereBetween(DB::raw("to_date(so_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->orderByRaw("to_date(so_date,'DD-MM-YYYY')")
        ->get();

        return Datatables::of($data)
        ->addColumn('status', function ($row) {
            return $row->is_scheduled
                ? '<div class="badge badge-success">Scheduled</div>'
                : '<div class="badge badge-secondary">Not Scheduled</div>';
        })
        ->addColumn('action', function ($row) {
            return '<a href="'.route('deliverySchedule.create', ['so_code' => $row->so_code]).'" class="btn btn-sm btn-info">
                        <i data-feather="calendar"></i> '.($row->is_scheduled ? 'Edit Schedule' : 'Create Schedule').'
                    </a>';
        })
        ->rawColumns(['status', 'action'])
        ->make(true);
    }

    public function create(Request $request)
    {
        $soCode = $request->so_code;

        $header = DB::table('sales_order_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'sales_order_hdr.customer_id')
        ->where('sales_order_hdr.so_code', $soCode)
        ->select('sales_order_hdr.*', 'third_party.nama as cust_name')
        ->first();

        if (!$header || $header->delivery_type != 'PO') {
            return redirect()->route('deliverySchedules.index')->with(['alert' => 'warning', 'message' => "SO $soCode is not eligible for a Delivery Schedule"]);
        }

        $data['title'] = $this->title;
        $data['header'] = $header;

        $data['articles'] = DB::table('sales_order_det')
        ->leftJoin('article', 'article.article_code', '=', 'sales_order_det.article_code')
        ->where('sales_order_det.so_code', $soCode)
        ->select('sales_order_det.article_code', 'sales_order_det.qty', 'sales_order_det.uom', 'article.article_desc', 'article.article_alternative_code')
        ->get();

        $data['existing'] = DB::table('delivery_schedule_det')
        ->where('so_code', $soCode)
        ->orderBy('schedule_date')
        ->get();

        return view('deliverySchedule.create', $data);
    }

    public function save(Request $request)
    {
        $username = Auth::user()->username;
        $soCode = $request->soCode;
        $rows = json_decode($request->rows);

        $validation = Validator::make($request->all(), [
            'soCode' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => ['SO Code is required'], 'alert' => 'error']);
        }

        $orderQty = DB::table('sales_order_det')
        ->where('so_code', $soCode)
        ->pluck('qty', 'article_code');

        $totalPerArticle = [];
        $dataSet = [];
        foreach ($rows as $val) {
            $qty = (float) $val->qty;
            if ($qty <= 0) {
                continue;
            }
            $totalPerArticle[$val->article_code] = ($totalPerArticle[$val->article_code] ?? 0) + $qty;
            $dataSet[] = [
                'so_code' => $soCode,
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
        foreach ($orderQty as $articleCode => $qty) {
            $scheduled = round($totalPerArticle[$articleCode] ?? 0, 2);
            if ($scheduled != round((float) $qty, 2)) {
                $errors[] = "$articleCode: scheduled qty ($scheduled) does not match order qty ($qty)";
            }
        }

        if (!empty($errors)) {
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => $errors, 'alert' => 'error']);
        }

        DB::beginTransaction();
        try {
            DB::table('delivery_schedule_det')->where('so_code', $soCode)->delete();
            if (!empty($dataSet)) {
                DB::table('delivery_schedule_det')->insert($dataSet);
            }
            DB::commit();
            $message = "Delivery Schedule for $soCode is successfully saved";
            \LogActivity::addToLog("Save $this->title", "username: $username Status $message");
            return response()->json(['status' => 1, 'title' => "Save $this->title", 'message' => $message, 'alert' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = "Delivery Schedule for $soCode is failed to save";
            \LogActivity::addToLog("Save $this->title", "username: $username Status $message");
            return response()->json(['status' => 0, 'title' => "Save $this->title", 'message' => [$message], 'alert' => 'error']);
        }
    }

    public function exportTemplate(Request $request)
    {
        $soCode = $request->so_code;

        $articles = DB::table('sales_order_det')
        ->where('so_code', $soCode)
        ->orderBy('article_code')
        ->get();

        return Excel::download(new DeliveryScheduleTemplateExport($articles), "delivery_schedule_template_$soCode.xlsx");
    }

    public function importExcel(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'file' => 'required|mimes:xls,xlsx|max:5120',
            'soCode' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => ['A valid SO Code and xls/xlsx file are required']]);
        }

        $soCode = $request->soCode;
        $validArticles = DB::table('sales_order_det')->where('so_code', $soCode)->pluck('article_code')->toArray();

        $import = new DeliveryScheduleImport();
        Excel::import($import, $request->file('file'));

        $rows = [];
        $errors = [];
        foreach ($import->rows as $row) {
            if (!in_array($row['article_code'], $validArticles)) {
                $errors[] = "{$row['article_code']} does not belong to SO $soCode";
                continue;
            }
            if (!$row['schedule_date']) {
                $errors[] = "{$row['article_code']}: invalid date, expected format DD-MM-YYYY";
                continue;
            }
            $rows[] = $row;
        }

        return response()->json(['status' => 1, 'rows' => $rows, 'errors' => $errors]);
    }
}
