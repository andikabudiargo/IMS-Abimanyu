<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DataTables;
use DB;

class InspectionRuleController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Inspection Rule";
        return view("inspectionRules.index", $data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Inspection Rule";
        $data['posts'] = DB::table('inspection_posts')->where('status', '1')->orderBy('sequence')->get();
        return view("inspectionRules.create", $data);
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $articleCode = $request->filled('article_code') ? strtoupper($request->input('article_code')) : null;

        $rule = [
            'inspection_post_id' => 'required|integer',
            'target_pass_rate' => 'required|numeric|min:0|max:100',
            'target_pass_through_rate' => 'required|numeric|min:0|max:100',
        ];
        $this->validate($request, $rule);

        $exists = DB::table('inspection_rules')
            ->where('inspection_post_id', $request->input('inspection_post_id'))
            ->where(function ($q) use ($articleCode) {
                $articleCode ? $q->where('article_code', $articleCode) : $q->whereNull('article_code');
            })
            ->exists();
        if ($exists) {
            $message = $articleCode
                ? "A rule for $articleCode on this Inspection Post already exists"
                : "A default rule for this Inspection Post already exists";
            return redirect()->back()->withInput()->with(['alert' => 'alert-warning', 'message' => $message]);
        }

        DB::beginTransaction();
        try {
            DB::table('inspection_rules')->insert([
                'article_code' => $articleCode,
                'inspection_post_id' => $request->input('inspection_post_id'),
                'target_pass_rate' => $request->input('target_pass_rate'),
                'target_pass_through_rate' => $request->input('target_pass_through_rate'),
                'note' => $request->input('note'),
                'status' => '1',
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::commit();
            $alert = "alert-success";
            $message = "Inspection Rule is successfully saved";
            \LogActivity::addToLog('InspectionRule save', "username: $username Status $message");
            return redirect()->route('inspectionRule.index')->with(['alert' => $alert, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            $alert = "alert-warning";
            $message = "Inspection Rule is failed to save";
            \LogActivity::addToLog('InspectionRule save', "username: $username Status $message");
            return redirect()->back()->with(['alert' => $alert, 'message' => $message]);
        }
    }

    public function edit(Request $request)
    {
        $data['title'] = "Edit Inspection Rule";
        $data['rule'] = DB::table('inspection_rules')->where('id', $request->id)->first();
        $data['posts'] = DB::table('inspection_posts')->where('status', '1')->orderBy('sequence')->get();
        return view('inspectionRules.edit', $data);
    }

    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $id = $request->id;
        $articleCode = $request->filled('article_code') ? strtoupper($request->input('article_code')) : null;

        $rule = [
            'inspection_post_id' => 'required|integer',
            'target_pass_rate' => 'required|numeric|min:0|max:100',
            'target_pass_through_rate' => 'required|numeric|min:0|max:100',
        ];
        $this->validate($request, $rule);

        $exists = DB::table('inspection_rules')
            ->where('id', '<>', $id)
            ->where('inspection_post_id', $request->input('inspection_post_id'))
            ->where(function ($q) use ($articleCode) {
                $articleCode ? $q->where('article_code', $articleCode) : $q->whereNull('article_code');
            })
            ->exists();
        if ($exists) {
            $message = $articleCode
                ? "A rule for $articleCode on this Inspection Post already exists"
                : "A default rule for this Inspection Post already exists";
            return redirect()->back()->withInput()->with(['alert' => 'alert-warning', 'message' => $message]);
        }

        DB::beginTransaction();
        try {
            $rowAffected = DB::table('inspection_rules')->where('id', $id)->update([
                'article_code' => $articleCode,
                'inspection_post_id' => $request->input('inspection_post_id'),
                'target_pass_rate' => $request->input('target_pass_rate'),
                'target_pass_through_rate' => $request->input('target_pass_through_rate'),
                'note' => $request->input('note'),
                'status' => $request->input('status', '1'),
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::commit();
            $alert = $rowAffected > 0 ? "alert-success" : "alert-warning";
            $message = $rowAffected > 0 ? "Successfully updated" : "Failed to update";
            \LogActivity::addToLog('InspectionRule update', "username: $username Status $message");
            return redirect()->route('inspectionRule.index')->with(['alert' => $alert, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            \LogActivity::addToLog('InspectionRule update', "username: $username Status Failed");
            return redirect()->back()->with(['alert' => 'alert-warning', 'message' => 'Failed to update']);
        }
    }

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $rowAffected = DB::table('inspection_rules')->where('id', $request->id)->delete();

        $alert = $rowAffected > 0 ? "alert-success" : "alert-warning";
        $message = $rowAffected > 0 ? "Successfully Deleted" : "Failed to Delete";
        \LogActivity::addToLog('InspectionRule delete', "username: $username Status $message");
        return redirect()->back()->with(['alert' => $alert, 'message' => $message]);
    }

    public function list(Request $request)
    {
        $articleCode = strtolower($request->articleCode);

        $data = DB::table('inspection_rules')
            ->join('inspection_posts', 'inspection_posts.id', '=', 'inspection_rules.inspection_post_id')
            ->select('inspection_rules.*', 'inspection_posts.name as post_name');
        $articleCode ? $data->where('inspection_rules.article_code', 'ilike', '%' . $articleCode . '%') : '';
        $data->orderBy('inspection_posts.sequence')->orderBy('inspection_rules.article_code');

        return Datatables::of($data)
            ->addColumn('action', function ($row) {
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';
                $buttons .= '<a href="' . route('inspectionRule.edit', ['id' => $row->id]) . '" class="dropdown-item">
                                <i data-feather="file-text"></i>
                                Edit
                            </a>';
                $buttons .= "<a href='javascript:;'
                                id='deleteButton'
                                class='dropdown-item'
                                data-toggle='modal'
                                data-target='#smallModal'
                                data-href='" . route("inspectionRule.destroy", ["id" => $row->id]) . "'>
                                <i data-feather='trash-2' class='feather-14-red'></i>
                                Delete
                            </a>";
                $buttons .= '</div></div>';
                return $buttons;
            })
            ->editColumn('article_code', function ($row) {
                return $row->article_code ?: '<i>Default (all articles)</i>';
            })
            ->editColumn('status', function ($row) {
                return $row->status == '1' ? 'Active' : 'Inactive';
            })
            ->rawColumns(['action', 'article_code'])
            ->make(true);
    }
}
