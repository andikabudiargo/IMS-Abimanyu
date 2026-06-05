<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use DB;
use Carbon\Carbon;

class ApiController extends Controller
{
    public function __construct()
    {
        // Berikan pengecualian middleware auth untuk login dan register
        // $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function profile()
    {
        return response()->json(auth()->user());
    }

    public function getAllUser()
    {
        // Mengambil semua data user dari database
        $users = User::select('username', 'name', 'created_at')->get();
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Successfully retrieved the user list',
            'data' => $users
        ], 200);
    }

    public function getReportByPeriod(Request $request, $startDate, $endDate)
    {
        $validator = Validator::make([
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ], [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);


        if ($validator->fails()) { return response()->json($validator->errors(), 422); }

        // $startDate = $request->input('start_date');
        // $endDate = $request->input('end_date');

        // Hitung selisih hari antara start_date dan end_date
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->diffInDays($end) >= 180) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Rentang periode penarikan data maksimal adalah 180 hari untuk mencegah server overload'
            ], 400);
        }

        $endDate = $endDate ? $endDate : $startDate;

        // $status = ['DRAFT','VALIDATE','APPROVED','POSTED','CANCELED','PAID'];

        $invoices = DB::table('invoice_det')
        ->leftJoin('invoice_hdr','invoice_det.invoice_number','invoice_hdr.invoice_number')
        ->leftJoin('article','article.article_code','=','invoice_det.article_code')
        ->where(function ($query) use ($startDate,$endDate) {
            // $startDate ? $query->whereBetween(DB::raw("to_date(invoice_date,'DD-MM-YYYY')"), [$startDate, $endDate]) : '';
            $startDate ? $query->whereBetween('invoice_hdr.created_at', [$startDate, $endDate]) : '';
        })
        ->select(
            DB::RAW("max(invoice_hdr.invoice_number) as invoice_number")
            ,'invoice_hdr.invoice_date as invoice_date'
            ,DB::raw("CASE 
                WHEN invoice_hdr.status = '0' THEN 'DRAFT'
                WHEN invoice_hdr.status = '1' THEN 'VALIDATE'
                WHEN invoice_hdr.status = '2' THEN 'APPROVED'
                WHEN invoice_hdr.status = '3' THEN 'POSTED'
                WHEN invoice_hdr.status = '4' THEN 'CANCELED'
                WHEN invoice_hdr.status = '5' THEN 'PAID'
                ELSE 'UNKNOWN'
                END as status")
            ,'article.article_alternative_code as article_code'
            ,'article.article_desc'
            ,DB::raw("to_char(to_date(invoice_hdr.invoice_date, 'DD-MM-YYYY'), 'DD/MM/YYYY') as invoice_date")
            ,DB::raw("sum(qty) as qty")
            ,DB::RAW("max(invoice_det.uom) as uom")
            ,'price'
            ,'price_service'
            ,DB::raw("sum(qty*price) as total_price_material")
            ,DB::raw("sum(qty*price_service) as total_price_service")
            ,DB::raw("sum(qty*price) + sum(qty*price_service) as grand_total")
            ,'invoice_hdr.created_by as created_by'
            ,'invoice_hdr.created_at'
            ,'invoice_hdr.updated_by as updated_by'
            ,'invoice_hdr.updated_at'

        )
        ->groupBy('invoice_hdr.invoice_number')
        ->groupBy('invoice_hdr.invoice_date')
        ->groupBy('price')
        ->groupBy('price_service')
        ->groupBy('article.article_alternative_code')
        ->groupBy('article.article_desc')
        ->get(); 

        // $invoices = DB::table('invoice_det')->get();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Successfully retrieved the invoice list',
            'data' => $invoices
        ], 200);

    }
}