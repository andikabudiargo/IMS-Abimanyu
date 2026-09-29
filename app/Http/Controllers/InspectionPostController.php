<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DataTables;
use DB;

class InspectionPostController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Inspection Post";
        return view("inspectionPosts.index", $data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Inspection Post";
        return view("inspectionPosts.create", $data);
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;

        $rule = [
            'name' => 'required',
            'sequence' => 'required|integer',
        ];
        $this->validate($request, $rule);

        DB::beginTransaction();
        try {
            DB::table('inspection_posts')->insert([
                'name' => $request->input('name'),
                'sequence' => $request->input('sequence'),
                'status' => '1',
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::commit();
            $alert = "alert-success";
            $message = "Inspection Post is successfully saved";
            \LogActivity::addToLog('InspectionPost save', "username: $username Status $message");
            return redirect()->route('inspectionPost.index')->with(['alert' => $alert, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            $alert = "alert-warning";
            $message = "Inspection Post is failed to save";
            \LogActivity::addToLog('InspectionPost save', "username: $username Status $message");
            return redirect()->back()->with(['alert' => $alert, 'message' => $message]);
        }
    }

    public function edit(Request $request)
    {
        $data['title'] = "Edit Inspection Post";
        $data['post'] = DB::table('inspection_posts')->where('id', $request->id)->first();
        return view('inspectionPosts.edit', $data);
    }

    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $id = $request->id;

        $rule = [
            'name' => 'required',
            'sequence' => 'required|integer',
        ];
        $this->validate($request, $rule);

        DB::beginTransaction();
        try {
            $rowAffected = DB::table('inspection_posts')->where('id', $id)->update([
                'name' => $request->input('name'),
                'sequence' => $request->input('sequence'),
                'status' => $request->input('status', '1'),
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::commit();
            $alert = $rowAffected > 0 ? "alert-success" : "alert-warning";
            $message = $rowAffected > 0 ? "Successfully updated" : "Failed to update";
            \LogActivity::addToLog('InspectionPost update', "username: $username Status $message");
            return redirect()->route('inspectionPost.index')->with(['alert' => $alert, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            \LogActivity::addToLog('InspectionPost update', "username: $username Status Failed");
            return redirect()->back()->with(['alert' => 'alert-warning', 'message' => 'Failed to update']);
        }
    }

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $rowAffected = DB::table('inspection_posts')->where('id', $request->id)->delete();

        $alert = $rowAffected > 0 ? "alert-success" : "alert-warning";
        $message = $rowAffected > 0 ? "Successfully Deleted" : "Failed to Delete";
        \LogActivity::addToLog('InspectionPost delete', "username: $username Status $message");
        return redirect()->back()->with(['alert' => $alert, 'message' => $message]);
    }

    public function list(Request $request)
    {
        $name = strtolower($request->name);

        $data = DB::table('inspection_posts');
        $name ? $data->where('name', 'ilike', '%' . $name . '%') : '';
        $data->orderBy('sequence');

        return Datatables::of($data)
            ->addColumn('action', function ($row) {
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';
                if (Auth::user()->can('inspection-post-edit')) {
                    $buttons .= '<a href="' . route('inspectionPost.edit', ['id' => $row->id]) . '" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
                }
                if (Auth::user()->can('inspection-post-delete')) {
                    $buttons .= "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='" . route("inspectionPost.destroy", ["id" => $row->id]) . "'>
                                    <i data-feather='trash-2' class='feather-14-red'></i>
                                    Delete
                                </a>";
                }
                $buttons .= '</div></div>';
                return $buttons;
            })
            ->editColumn('status', function ($row) {
                return $row->status == '1' ? 'Active' : 'Inactive';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
