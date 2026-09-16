<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Response;
use App\Permission;
use DataTables;
use DB;
use Approval;
use Exception;

class FormChangeRequestController extends Controller
{
    private $title;
    private $moduleCode;

    // 1 Pending, 2 Validated, 3 Approved, 4 Finished, 5 Canceled, 6 Rejected, 7 Revised
    private $statusLabel = [
        1 => 'Pending',
        2 => 'Validated',
        3 => 'Approved',
        4 => 'Finished',
        5 => 'Canceled',
        6 => 'Rejected',
        7 => 'Revised',
    ];

    private $modules = [
        'Article',
        'Article Request',
        'Article Type',
        'Group of Material',
        'Unit of Measure (UoM)',
        'UoM Conversion',
        'Sales Order',
        'Target Sales Order',
        'Conversion',
        'Sales Forecasting',
        'Customer',
        'Report SO',
        'Purchase Request',
        'Purchase Order',
        'Purchase Forecasting',
        'Supplier',
        'Delivery Instruction',
        'Report PO',
        'Work Order Sheet',
        'WOS Mixing',
        'Receiving',
        'Supplier Return',
        'Supplier Replace',
        'Delivery Note',
        'Delivery Return',
        'Delivery Replace',
        'Delivery Temporary',
        'Delivery General',
        'Delivery Received',
        'Delivery Report',
        'Stock',
        'Stock Movement',
        'Stock Transfer',
        'Stock Consumption',
        'Stock Adjustment',
        'Stock Location',
        'STO Count',
        'STO Configuration',
        'STO Adjustment',
        'STO Report',
        'Bill of Material (BOM)',
        'BOM Report',
        'Actual Loading',
        'Actual Finish Goods',
        'Inspection Order',
        'Inspection Defects',
        'Inspection Report',
        'Scrap Management',
        'Project Management',
        'Invoice Supplier',
        'Invoice Customer',
        'Debit Note',
        'Kas Pembayaran',
        'Kas Penerimaan',
        'Bank Pembayaran',
        'Bank Penerimaan',
        'Buku Besar',
        'Neraca',
        'Laba Rugi',
        'Trial Balance',
        'DN Report',
        'SO Report',
        'Receiving Report',
        'General Jouirnal',
        'Chart of Accounts (CoA)',
        'Assets',
        'Stock Valuation',
        'Department',
        'Form Change Request',
        'Master Data'
    ];

    private $types = [
        'Perubahan Data',
        'Penghapusan Data',
        'Perbaikan Fitur',
        'Penambahan Fitur',
    ];

    private $urgencies = [
        'Normal (<3 hari)',
        'Segera (<1 hari)',
        'Urgent (Hari Ini)',
    ];

    public function __construct()
    {
        $this->title = "Form Change Request";
        $this->moduleCode = "CR";
    }

    public function getLastCode($key)
    {
        DB::table('master_code')
        ->where('code_key', $key)
        ->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by' => Auth::user()->username,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $newCode = DB::table('master_code')
        ->where('code_key', $key)
        ->value('code_number');

        $months = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $month = $months[date('n') - 1];
        $year = date('Y');
        $number = str_pad($newCode, 4, '0', STR_PAD_LEFT);

        return "$key-ASN-$year-$month-$number";
    }

    public function getTableColoumn()
    {
        $kolom = [
            ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false],
            ['data' => 'cr_number', 'name' => 'cr_number', 'title' => 'Ticket Number'],
            ['data' => 'modul', 'name' => 'modul', 'title' => 'Modul'],
            ['data' => 'type', 'name' => 'type', 'title' => 'Type'],
            ['data' => 'urgency', 'name' => 'urgency', 'title' => 'Urgensi'],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status'],
            ['data' => 'created_by', 'name' => 'created_by', 'title' => 'Created By'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created At'],
        ];
        return json_encode($kolom, true);
    }

    private function statusBadge($status)
    {
        $badges = [
            1 => 'badge-light-warning',
            2 => 'badge-light-info',
            3 => 'badge-light-primary',
            4 => 'badge-light-success',
            5 => 'badge-light-secondary',
            6 => 'badge-light-danger',
            7 => 'badge-light-dark',
        ];
        $label = $this->statusLabel[$status] ?? $status;
        $class = $badges[$status] ?? 'badge-light-secondary';
        return "<div class='badge badge-pill $class'>$label</div>";
    }

    public function index()
    {
        $data['title'] = $this->title;
        $data['subtitle'] = $this->title;
        $data['kolom'] = $this->getTableColoumn();
        $data['modules'] = $this->modules;
        $data['types'] = $this->types;
        $data['statusList'] = $this->statusLabel;
        return view('formChangeRequest.index', $data);
    }

    public function list(Request $request)
    {
        $crNumber = strtolower($request->crNumber);
        $modul = $request->modul;
        $type = $request->type;
        $status = $request->status;
        $date = $request->date;

        $data = DB::table('form_change_request_hdr')
            ->select('*')
            ->where(function ($query) use ($crNumber, $modul, $type, $status, $date) {
                $crNumber ? $query->where('cr_number', 'ilike', '%' . $crNumber . '%') : '';
                $modul ? $query->where('modul', $modul) : '';
                $type ? $query->where('type', $type) : '';
                $status ? $query->where('status', $status) : '';
                if ($date) {
                    $range = explode(' to ', $date);
                    if (count($range) == 2) {
                        $query->whereBetween(DB::raw('created_at::date'), [$range[0], $range[1]]);
                    }
                }
            })
            ->orderByDesc('id')
            ->get();

        return Datatables::of($data)
            ->addColumn('action', function ($data) {
                $id = Crypt::encryptString($data->id);
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';
                $buttons .= "<a href='" . route('formChangeRequest.edit', ['id' => $id]) . "' class='dropdown-item'>
                                <i data-feather='eye'></i> " . __('Detail') . "</a>";
                if ($data->status == 1) {
                    $buttons .= "<a href='javascript:;' onclick='deleteCr(\"" . $id . "\",\"" . $data->cr_number . "\")' class='dropdown-item'>
                                    <i data-feather='trash-2' class='feather-14-red'></i> " . __('Delete') . "</a>";
                }
                $buttons .= '</div></div>';
                return $buttons;
            })
            ->addColumn('status', function ($data) {
                return $this->statusBadge($data->status);
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function create()
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $data['modules'] = $this->modules;
        $data['types'] = $this->types;
        $data['urgencies'] = $this->urgencies;
        return view('formChangeRequest.create', $data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modul' => 'required',
            'type' => 'required',
            'urgency' => 'required',
            'description' => 'required',
            'attachment.*' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->getMessageBag()->first());
        }

        $username = Auth::user()->username;
        $crNumber = $this->getLastCode($this->moduleCode);

        DB::beginTransaction();
        try {
            $hdrId = DB::table('form_change_request_hdr')->insertGetId([
                'cr_number' => $crNumber,
                'modul' => $request->modul,
                'type' => $request->type,
                'urgency' => $request->urgency,
                'description' => $request->description,
                'status' => 1,
                'rev_no' => 0,
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if ($request->type == 'Perubahan Data' && $request->has('detail')) {
                $this->saveDetail($hdrId, $request->detail);
            }

            if ($request->hasFile('attachment')) {
                $this->saveAttachments($hdrId, $request->file('attachment'), 'request');
            }

            DB::commit();
            \LogActivity::addToLog("Create $this->title", "username: $username created $crNumber");
            return redirect()->route('formChangeRequest.index')->with('success', "$crNumber successfully created.");
        } catch (Exception $e) {
            DB::rollBack();
            \LogActivity::addToLog("Create $this->title", "username: $username failed: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to create Form Change Request.');
        }
    }

    private function saveDetail($hdrId, $detail)
    {
        DB::table('form_change_request_det')->where('hdr_id', $hdrId)->delete();
        $no = 1;
        foreach ($detail as $row) {
            if (empty($row['ref_number']) && empty($row['field_name']) && empty($row['current_data']) && empty($row['proposed_data'])) {
                continue;
            }
            DB::table('form_change_request_det')->insert([
                'hdr_id' => $hdrId,
                'no' => $no,
                'ref_number' => $row['ref_number'] ?? null,
                'field_name' => $row['field_name'] ?? null,
                'current_data' => $row['current_data'] ?? null,
                'proposed_data' => $row['proposed_data'] ?? null,
                'note' => $row['note'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $no++;
        }
    }

    private function saveAttachments($hdrId, $files, $stage)
    {
        $username = Auth::user()->username;
        foreach ($files as $file) {
            if (!$file) continue;
            $path = $file->store('form-change-request', 'public');
            DB::table('form_change_request_attachment')->insert([
                'hdr_id' => $hdrId,
                'stage' => $stage,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'uploaded_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function edit(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $header = DB::table('form_change_request_hdr')->where('id', $id)->first();
        if (!$header) {
            return redirect()->route('formChangeRequest.index')->with('error', 'Data not found.');
        }

        $username = Auth::user()->username;

        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";
        $data['id'] = $request->id;
        $data['header'] = $header;
        $data['statusLabel'] = $this->statusLabel[$header->status] ?? $header->status;
        $data['modules'] = $this->modules;
        $data['types'] = $this->types;
        $data['urgencies'] = $this->urgencies;
        $data['detail'] = DB::table('form_change_request_det')->where('hdr_id', $id)->orderBy('no')->get();
        $data['attachments'] = DB::table('form_change_request_attachment')->where('hdr_id', $id)->orderBy('id')->get();
        $data['revisionLog'] = DB::table('form_change_request_revision_log')->where('hdr_id', $id)->orderByDesc('id')->get();
        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $header->cr_number, $username);

        $levelPosition = Approval::approvalLevelPosition($this->moduleCode, $header->cr_number, $username);
        $data['canApprove'] = count($levelPosition) > 0 && in_array($header->status, [1, 2, 3, 7]);
        $data['nextLevel'] = count($levelPosition) > 0 ? $levelPosition[0]->next_level : null;

        $data['isOwner'] = $header->created_by == $username;
        $data['canEdit'] = $data['isOwner'] && in_array($header->status, [1, 7]);
        $data['canRevise'] = $data['isOwner'] && $header->status == 2;
        $data['canCancel'] = $data['isOwner'] && $header->status == 1;
        $data['canDelete'] = $data['isOwner'] && $header->status == 1;

        return view('formChangeRequest.edit', $data);
    }

    public function update(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $header = DB::table('form_change_request_hdr')->where('id', $id)->first();
        if (!$header) {
            return redirect()->back()->with('error', 'Data not found.');
        }

        $username = Auth::user()->username;
        $isRevision = $header->status == 2;

        if (!in_array($header->status, [1, 2, 7]) || $header->created_by != $username) {
            return redirect()->back()->with('error', 'This request can no longer be edited.');
        }

        $rules = [
            'modul' => 'required',
            'type' => 'required',
            'urgency' => 'required',
            'description' => 'required',
            'attachment.*' => 'nullable|file|max:10240',
        ];
        if ($isRevision) {
            $rules['reason'] = 'required';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('error', $validator->getMessageBag()->first());
        }

        DB::beginTransaction();
        try {
            $update = [
                'modul' => $request->modul,
                'type' => $request->type,
                'urgency' => $request->urgency,
                'description' => $request->description,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $newRevNo = $header->rev_no;
            if ($isRevision) {
                $newRevNo = $header->rev_no + 1;
                $update['status'] = 7;
                $update['rev_no'] = $newRevNo;
                $update['revision_reason'] = $request->reason;

                DB::table('form_change_request_revision_log')->insert([
                    'hdr_id' => $id,
                    'rev_no' => $newRevNo,
                    'action' => '(C) Revised',
                    'reason' => $request->reason,
                    'created_by' => $username,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                // Restart the approval chain from level 1 since the data changed.
                DB::table('approval_history')
                    ->where('module_code', $this->moduleCode)
                    ->where('module_number', $header->cr_number)
                    ->delete();
            }

            DB::table('form_change_request_hdr')->where('id', $id)->update($update);

            if ($request->type == 'Perubahan Data' && $request->has('detail')) {
                $this->saveDetail($id, $request->detail);
            } else {
                DB::table('form_change_request_det')->where('hdr_id', $id)->delete();
            }

            if ($request->hasFile('attachment')) {
                $this->saveAttachments($id, $request->file('attachment'), 'request');
            }

            DB::commit();
            \LogActivity::addToLog("Update $this->title", "username: $username updated $header->cr_number");
            return redirect()->route('formChangeRequest.index')->with('success', "$header->cr_number successfully updated.");
        } catch (Exception $e) {
            DB::rollBack();
            \LogActivity::addToLog("Update $this->title", "username: $username failed: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Failed to update Form Change Request.');
        }
    }

    public function approve(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $header = DB::table('form_change_request_hdr')->where('id', $id)->first();

        if (!$header) {
            return response()->json(['status' => 0, 'message' => 'Data not found.']);
        }
        if (!in_array($header->status, [1, 2, 7])) {
            return response()->json(['status' => 0, 'message' => 'This request is not awaiting this approval level.']);
        }

        $levelPosition = Approval::approvalLevelPosition($this->moduleCode, $header->cr_number, $username);
        if (count($levelPosition) == 0) {
            return response()->json(['status' => 0, 'message' => 'You are not authorized to approve this request.']);
        }

        $nextLevel = $levelPosition[0]->next_level;
        $maxLevel = $levelPosition[0]->max_level;

        if ($nextLevel >= $maxLevel) {
            return response()->json(['status' => 0, 'message' => 'Final approval requires a completion note and attachment. Use the finish action.']);
        }

        $newStatus = $nextLevel == 1 ? 2 : 3;

        DB::beginTransaction();
        try {
            DB::table('form_change_request_hdr')->where('id', $id)->update([
                'status' => $newStatus,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('approval_history')->insert([
                'module_code' => $this->moduleCode,
                'module_number' => $header->cr_number,
                'username' => $username,
                'approval_order' => $nextLevel,
                'approval_date' => date('Y-m-d'),
                'status' => 1,
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::commit();
            \LogActivity::addToLog("Approve $this->title", "username: $username approved $header->cr_number level $nextLevel");
            return response()->json(['status' => 1, 'message' => "$header->cr_number approved.", 'newStatus' => $newStatus]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'message' => 'Failed to approve.']);
        }
    }

    public function approveFinal(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $header = DB::table('form_change_request_hdr')->where('id', $id)->first();

        if (!$header) {
            return redirect()->back()->with('error', 'Data not found.');
        }
        if ($header->status != 3) {
            return redirect()->back()->with('error', 'This request is not ready for the final approval.');
        }

        $levelPosition = Approval::approvalLevelPosition($this->moduleCode, $header->cr_number, $username);
        if (count($levelPosition) == 0 || $levelPosition[0]->next_level != $levelPosition[0]->max_level) {
            return redirect()->back()->with('error', 'You are not authorized for the final approval.');
        }

        $validator = Validator::make($request->all(), [
            'finish_note' => 'required',
            'attachment.*' => 'nullable|file|max:10240',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $nextLevel = $levelPosition[0]->next_level;

        DB::beginTransaction();
        try {
            DB::table('form_change_request_hdr')->where('id', $id)->update([
                'status' => 4,
                'finish_note' => $request->finish_note,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('approval_history')->insert([
                'module_code' => $this->moduleCode,
                'module_number' => $header->cr_number,
                'username' => $username,
                'approval_order' => $nextLevel,
                'approval_date' => date('Y-m-d'),
                'status' => 1,
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if ($request->hasFile('attachment')) {
                $this->saveAttachments($id, $request->file('attachment'), 'finish');
            }

            DB::commit();
            \LogActivity::addToLog("Finish $this->title", "username: $username finished $header->cr_number");
            return redirect()->route('formChangeRequest.index')->with('success', "$header->cr_number successfully finished.");
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to finish this request.');
        }
    }

    public function reject(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $header = DB::table('form_change_request_hdr')->where('id', $id)->first();

        if (!$header) {
            return redirect()->back()->with('error', 'Data not found.');
        }
        if (!in_array($header->status, [1, 2, 3, 7])) {
            return redirect()->back()->with('error', 'This request can no longer be rejected.');
        }

        $validator = Validator::make($request->all(), ['reason' => 'required']);
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        $levelPosition = Approval::approvalLevelPosition($this->moduleCode, $header->cr_number, $username);
        if (count($levelPosition) == 0) {
            return redirect()->back()->with('error', 'You are not authorized to reject this request.');
        }

        DB::beginTransaction();
        try {
            DB::table('form_change_request_hdr')->where('id', $id)->update([
                'status' => 6,
                'reject_reason' => $request->reason,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('approval_history')->insert([
                'module_code' => $this->moduleCode,
                'module_number' => $header->cr_number,
                'username' => $username,
                'approval_order' => $levelPosition[0]->next_level,
                'approval_date' => date('Y-m-d'),
                'status' => 0,
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::commit();
            \LogActivity::addToLog("Reject $this->title", "username: $username rejected $header->cr_number");
            return redirect()->route('formChangeRequest.index')->with('success', "$header->cr_number rejected.");
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to reject this request.');
        }
    }

    public function cancel(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $header = DB::table('form_change_request_hdr')->where('id', $id)->first();

        if (!$header) {
            return redirect()->back()->with('error', 'Data not found.');
        }
        if ($header->status != 1 || $header->created_by != $username) {
            return redirect()->back()->with('error', 'This request can no longer be canceled.');
        }

        $validator = Validator::make($request->all(), ['reason' => 'required']);
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        DB::table('form_change_request_hdr')->where('id', $id)->update([
            'status' => 5,
            'cancel_reason' => $request->reason,
            'updated_by' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        \LogActivity::addToLog("Cancel $this->title", "username: $username canceled $header->cr_number");
        return redirect()->route('formChangeRequest.index')->with('success', "$header->cr_number canceled.");
    }

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $header = DB::table('form_change_request_hdr')->where('id', $id)->first();

        if (!$header) {
            return redirect()->back()->with('error', 'Data not found.');
        }
        if ($header->status != 1 || $header->created_by != $username) {
            return redirect()->back()->with('error', 'Only pending requests created by you can be deleted.');
        }

        $attachments = DB::table('form_change_request_attachment')->where('hdr_id', $id)->get();
        foreach ($attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        DB::table('form_change_request_hdr')->where('id', $id)->delete();

        \LogActivity::addToLog("Delete $this->title", "username: $username deleted $header->cr_number");
        return redirect()->route('formChangeRequest.index')->with('success', "$header->cr_number successfully deleted.");
    }
}
