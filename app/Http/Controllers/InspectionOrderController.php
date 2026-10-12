<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DataTables;
use DB;

/**
 * Inspection Order — transactional QC record (Incoming/Unloading/Buffing/
 * Touch Up/Final/Outgoing), mirrors "Daily Inspection" di modul QC abimanyu.
 * Pass Rate & Pass Through Rate dihitung dari total check/ok/ng, lalu
 * dibandingkan dengan target di Inspection Rule (article_code match, atau
 * default rule kalau tidak ada yang spesifik) untuk flag PASS/FAIL.
 * Tidak ada posting ke stock — ini murni dokumen QC.
 */
class InspectionOrderController extends Controller
{
    private const MODULE_CODE = 'QCO';

    private const ST_DRAFT    = '1';
    private const ST_POSTED   = '4';
    private const ST_CANCELED = '5';

    private const STATUS_LABEL = [
        self::ST_DRAFT    => 'DRAFT',
        self::ST_POSTED   => 'POSTED',
        self::ST_CANCELED => 'CANCELED',
    ];

    private const STATUS_BADGE = [
        self::ST_DRAFT    => 'badge-primary',
        self::ST_POSTED   => 'badge-success',
        self::ST_CANCELED => 'badge-danger',
    ];

    public function index(Request $request)
    {
        $data['title'] = "Inspection Order";
        return view("inspectionOrder.index", $data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Inspection Order";
        $data['posts'] = DB::table('inspection_posts')->where('status', '1')->orderBy('sequence')->get();
        $data['defects'] = DB::table('defects')->where('status', '1')->orderBy('code')->get();
        $data['suppliers'] = DB::table('third_party')->where('third_party_type', 'supp')->orderBy('nama')->get();
        $data['articles'] = DB::table('article')->orderBy('article_code')->get();
        return view("inspectionOrder.create", $data);
    }

    /**
     * Rule yang berlaku untuk article+post: article-spesifik kalau ada,
     * jatuh ke default (article_code NULL) kalau tidak.
     */
    private function findRule(string $articleCode, int $postId)
    {
        return DB::table('inspection_rules')
            ->where('inspection_post_id', $postId)
            ->where('status', '1')
            ->where(function ($q) use ($articleCode) {
                $q->where('article_code', $articleCode)->orWhereNull('article_code');
            })
            ->orderByRaw('article_code IS NULL') // article-spesifik duluan
            ->first();
    }

    /** AJAX: dipanggil dari form create/edit saat article/post dipilih, untuk menampilkan target. */
    public function getRule(Request $request)
    {
        $rule = $this->findRule(strtoupper((string) $request->articleCode), (int) $request->postId);
        return response()->json($rule ?: null);
    }

    private function computeRates(float $totalCheck, float $totalOk, float $totalOkRepair, float $totalNg): array
    {
        if ($totalCheck <= 0) {
            return [0, 0];
        }
        $passRate = round($totalOk / $totalCheck * 100, 2);
        $passThroughRate = round(($totalCheck - $totalNg - $totalOkRepair) / $totalCheck * 100, 2);
        return [$passRate, $passThroughRate];
    }

    private function getLastCode(string $key): string
    {
        DB::table('master_code')->where('code_key', $key)->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by'  => Auth::user()->username,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $newCode = DB::table('master_code')->where('code_key', $key)->value('code_number');
        $months  = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return sprintf(
            '%s-ASN-%s-%s-%s',
            $key, date('Y'), $months[date('n') - 1], str_pad($newCode, 4, '0', STR_PAD_LEFT)
        );
    }

    private function storeRules(): array
    {
        return [
            'inspection_date' => 'required|date',
            'inspection_post_id' => 'required|integer',
            'article_code' => 'required|string',
            'total_check' => 'required|numeric|min:0',
            'total_ok' => 'required|numeric|min:0',
            'total_ok_repair' => 'required|numeric|min:0',
            'total_ng' => 'required|numeric|min:0',
            'defect_id' => 'array',
            'category' => 'array',
            'defect_qty' => 'array',
            'defect_ok_repair' => 'array',
        ];
    }

    private function saveDetails(int $hdrId, Request $request): void
    {
        DB::table('inspection_order_det')->where('inspection_order_id', $hdrId)->delete();

        $defectIds = $request->input('defect_id', []);
        foreach ($defectIds as $i => $defectId) {
            if (!$defectId) {
                continue;
            }
            DB::table('inspection_order_det')->insert([
                'inspection_order_id' => $hdrId,
                'defect_id' => $defectId,
                'category' => $request->input('category')[$i] ?? null,
                'qty' => $request->input('defect_qty')[$i] ?? 0,
                'ok_repair' => $request->input('defect_ok_repair')[$i] ?? 0,
                'note' => $request->input('defect_note')[$i] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $this->validate($request, $this->storeRules());

        $articleCode = strtoupper($request->input('article_code'));
        $postId = (int) $request->input('inspection_post_id');
        [$passRate, $passThroughRate] = $this->computeRates(
            (float) $request->total_check, (float) $request->total_ok,
            (float) $request->total_ok_repair, (float) $request->total_ng
        );
        $matchedRule = $this->findRule($articleCode, $postId);
        $result = $matchedRule
            ? (($passRate >= $matchedRule->target_pass_rate && $passThroughRate >= $matchedRule->target_pass_through_rate) ? 'PASS' : 'FAIL')
            : null;

        DB::beginTransaction();
        try {
            $orderNo = $this->getLastCode(self::MODULE_CODE);
            $hdrId = DB::table('inspection_order_hdr')->insertGetId([
                'order_no' => $orderNo,
                'inspection_date' => $request->inspection_date,
                'inspection_post_id' => $postId,
                'supplier_code' => $request->input('supplier_code'),
                'article_code' => $articleCode,
                'shift' => $request->input('shift'),
                'check_method' => $request->input('check_method'),
                'qty_received' => $request->input('qty_received', 0),
                'total_check' => $request->total_check,
                'total_ok' => $request->total_ok,
                'total_ok_repair' => $request->total_ok_repair,
                'total_ng' => $request->total_ng,
                'pass_rate' => $passRate,
                'pass_through_rate' => $passThroughRate,
                'target_pass_rate' => $matchedRule->target_pass_rate ?? null,
                'target_pass_through_rate' => $matchedRule->target_pass_through_rate ?? null,
                'result' => $result,
                'note' => $request->input('note'),
                'status' => self::ST_DRAFT,
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->saveDetails($hdrId, $request);

            DB::commit();
            $alert = "alert-success";
            $message = "Inspection Order $orderNo is successfully saved";
            \LogActivity::addToLog('InspectionOrder save', "username: $username Status $message");
            return redirect()->route('inspectionOrder.index')->with(['alert' => $alert, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            $alert = "alert-warning";
            $message = "Inspection Order is failed to save: " . $e->getMessage();
            \LogActivity::addToLog('InspectionOrder save', "username: $username Status $message");
            return redirect()->back()->withInput()->with(['alert' => $alert, 'message' => $message]);
        }
    }

    public function edit(Request $request)
    {
        $hdr = DB::table('inspection_order_hdr')->where('id', $request->id)->first();
        if (!$hdr) {
            return redirect()->route('inspectionOrder.index')->with(['alert' => 'alert-warning', 'message' => 'Inspection Order not found']);
        }
        if ($hdr->status !== self::ST_DRAFT) {
            return redirect()->back()->with(['alert' => 'alert-warning', 'message' => 'Only DRAFT orders can be edited']);
        }

        $data['title'] = "Edit Inspection Order";
        $data['hdr'] = $hdr;
        $data['details'] = DB::table('inspection_order_det')->where('inspection_order_id', $hdr->id)->get();
        $data['posts'] = DB::table('inspection_posts')->where('status', '1')->orderBy('sequence')->get();
        $data['defects'] = DB::table('defects')->where('status', '1')->orderBy('code')->get();
        $data['suppliers'] = DB::table('third_party')->where('third_party_type', 'supp')->orderBy('nama')->get();
        $data['articles'] = DB::table('article')->orderBy('article_code')->get();
        return view('inspectionOrder.edit', $data);
    }

    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $id = $request->id;
        $hdr = DB::table('inspection_order_hdr')->where('id', $id)->first();
        if (!$hdr || $hdr->status !== self::ST_DRAFT) {
            return redirect()->back()->with(['alert' => 'alert-warning', 'message' => 'Only DRAFT orders can be updated']);
        }

        $this->validate($request, $this->storeRules());

        $articleCode = strtoupper($request->input('article_code'));
        $postId = (int) $request->input('inspection_post_id');
        [$passRate, $passThroughRate] = $this->computeRates(
            (float) $request->total_check, (float) $request->total_ok,
            (float) $request->total_ok_repair, (float) $request->total_ng
        );
        $matchedRule = $this->findRule($articleCode, $postId);
        $result = $matchedRule
            ? (($passRate >= $matchedRule->target_pass_rate && $passThroughRate >= $matchedRule->target_pass_through_rate) ? 'PASS' : 'FAIL')
            : null;

        DB::beginTransaction();
        try {
            DB::table('inspection_order_hdr')->where('id', $id)->update([
                'inspection_date' => $request->inspection_date,
                'inspection_post_id' => $postId,
                'supplier_code' => $request->input('supplier_code'),
                'article_code' => $articleCode,
                'shift' => $request->input('shift'),
                'check_method' => $request->input('check_method'),
                'qty_received' => $request->input('qty_received', 0),
                'total_check' => $request->total_check,
                'total_ok' => $request->total_ok,
                'total_ok_repair' => $request->total_ok_repair,
                'total_ng' => $request->total_ng,
                'pass_rate' => $passRate,
                'pass_through_rate' => $passThroughRate,
                'target_pass_rate' => $matchedRule->target_pass_rate ?? null,
                'target_pass_through_rate' => $matchedRule->target_pass_through_rate ?? null,
                'result' => $result,
                'note' => $request->input('note'),
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->saveDetails($id, $request);

            DB::commit();
            $alert = "alert-success";
            $message = "Successfully updated";
            \LogActivity::addToLog('InspectionOrder update', "username: $username Status $message");
            return redirect()->route('inspectionOrder.index')->with(['alert' => $alert, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            \LogActivity::addToLog('InspectionOrder update', "username: $username Status Failed");
            return redirect()->back()->withInput()->with(['alert' => 'alert-warning', 'message' => 'Failed to update: ' . $e->getMessage()]);
        }
    }

    public function show(Request $request)
    {
        $hdr = DB::table('inspection_order_hdr')
            ->leftJoin('inspection_posts', 'inspection_posts.id', '=', 'inspection_order_hdr.inspection_post_id')
            ->select('inspection_order_hdr.*', 'inspection_posts.name as post_name')
            ->where('inspection_order_hdr.id', $request->id)
            ->first();
        if (!$hdr) {
            return redirect()->route('inspectionOrder.index')->with(['alert' => 'alert-warning', 'message' => 'Inspection Order not found']);
        }

        $data['title'] = "Inspection Order Detail";
        $data['hdr'] = $hdr;
        $data['statusLabel'] = self::STATUS_LABEL[$hdr->status] ?? $hdr->status;
        $data['statusBadge'] = self::STATUS_BADGE[$hdr->status] ?? 'badge-secondary';
        $data['details'] = DB::table('inspection_order_det')
            ->leftJoin('defects', 'defects.id', '=', 'inspection_order_det.defect_id')
            ->select('inspection_order_det.*', 'defects.code as defect_code', 'defects.name as defect_name')
            ->where('inspection_order_det.inspection_order_id', $hdr->id)
            ->get();
        return view('inspectionOrder.show', $data);
    }

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $hdr = DB::table('inspection_order_hdr')->where('id', $request->id)->first();
        if (!$hdr || $hdr->status !== self::ST_DRAFT) {
            return redirect()->back()->with(['alert' => 'alert-warning', 'message' => 'Only DRAFT orders can be deleted']);
        }

        $rowAffected = DB::table('inspection_order_hdr')->where('id', $request->id)->delete();

        $alert = $rowAffected > 0 ? "alert-success" : "alert-warning";
        $message = $rowAffected > 0 ? "Successfully Deleted" : "Failed to Delete";
        \LogActivity::addToLog('InspectionOrder delete', "username: $username Status $message");
        return redirect()->back()->with(['alert' => $alert, 'message' => $message]);
    }

    public function posting(Request $request)
    {
        $username = Auth::user()->username;
        $hdr = DB::table('inspection_order_hdr')->where('id', $request->id)->first();
        if (!$hdr || $hdr->status !== self::ST_DRAFT) {
            return redirect()->back()->with(['alert' => 'alert-warning', 'message' => 'Only DRAFT orders can be posted']);
        }

        DB::table('inspection_order_hdr')->where('id', $request->id)->update([
            'status' => self::ST_POSTED,
            'updated_by' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        \LogActivity::addToLog('InspectionOrder posting', "username: $username order: $hdr->order_no");
        return redirect()->back()->with(['alert' => 'alert-success', 'message' => "$hdr->order_no is POSTED"]);
    }

    public function cancel(Request $request)
    {
        $username = Auth::user()->username;
        $hdr = DB::table('inspection_order_hdr')->where('id', $request->id)->first();
        if (!$hdr || $hdr->status === self::ST_CANCELED) {
            return redirect()->back()->with(['alert' => 'alert-warning', 'message' => 'Order already canceled or not found']);
        }
        $this->validate($request, ['reason' => 'required|string']);

        DB::table('inspection_order_hdr')->where('id', $request->id)->update([
            'status' => self::ST_CANCELED,
            'cancel_reason' => $request->input('reason'),
            'updated_by' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        \LogActivity::addToLog('InspectionOrder cancel', "username: $username order: $hdr->order_no");
        return redirect()->back()->with(['alert' => 'alert-success', 'message' => "$hdr->order_no is CANCELED"]);
    }

    public function list(Request $request)
    {
        $orderNo = strtolower($request->orderNo);
        $articleCode = strtolower($request->articleCode);

        $data = DB::table('inspection_order_hdr')
            ->leftJoin('inspection_posts', 'inspection_posts.id', '=', 'inspection_order_hdr.inspection_post_id')
            ->select('inspection_order_hdr.*', 'inspection_posts.name as post_name');
        $orderNo ? $data->where('inspection_order_hdr.order_no', 'ilike', '%' . $orderNo . '%') : '';
        $articleCode ? $data->where('inspection_order_hdr.article_code', 'ilike', '%' . $articleCode . '%') : '';
        $data->orderBy('inspection_order_hdr.id', 'desc');

        return Datatables::of($data)
            ->addColumn('action', function ($row) {
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';
                $buttons .= '<a href="' . route('inspectionOrder.show', ['id' => $row->id]) . '" class="dropdown-item">
                                <i data-feather="eye"></i>
                                Detail
                            </a>';
                if ($row->status == '1') {
                    if (Auth::user()->can('inspectionOrder-edit')) {
                        $buttons .= '<a href="' . route('inspectionOrder.edit', ['id' => $row->id]) . '" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                    }
                    if (Auth::user()->can('inspectionOrder-delete')) {
                        $buttons .= "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModal'
                                        data-href='" . route("inspectionOrder.destroy", ["id" => $row->id]) . "'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        Delete
                                    </a>";
                    }
                }
                $buttons .= '</div></div>';
                return $buttons;
            })
            ->editColumn('status', function ($row) {
                return self::STATUS_LABEL[$row->status] ?? $row->status;
            })
            ->editColumn('result', function ($row) {
                if (!$row->result) return '-';
                return $row->result == 'PASS'
                    ? '<span class="badge badge-success">PASS</span>'
                    : '<span class="badge badge-danger">FAIL</span>';
            })
            ->rawColumns(['action', 'result'])
            ->make(true);
    }
}
