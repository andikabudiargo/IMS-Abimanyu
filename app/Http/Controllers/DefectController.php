<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use DataTables;
use DB;

class DefectController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Defect";
        return view("defects.index", $data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Defect";
        $data['posts'] = DB::table('inspection_posts')->where('status', '1')->orderBy('sequence')->get();
        return view("defects.create", $data);
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $code = strtoupper($request->input('code'));

        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $rule = [
            'code' => 'required|iunique:defects,code',
            'name' => 'required',
            'severity' => 'required',
            'disposition' => 'required',
            'posts' => 'required|array',
        ];
        $messages = [
            'iunique' => "The code $code has already been taken",
            'posts.required' => 'Select at least one Inspection Post',
        ];
        $this->validate($request, $rule, $messages);

        DB::beginTransaction();
        try {
            $defectId = DB::table('defects')->insertGetId([
                'code' => $code,
                'name' => $request->input('name'),
                'category' => $request->input('category'),
                'severity' => $request->input('severity'),
                'disposition' => $request->input('disposition'),
                'is_repairable' => $request->boolean('is_repairable'),
                'description' => $request->input('description'),
                'status' => '1',
                'created_by' => $username,
                'updated_by' => $username,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $pivot = collect($request->input('posts'))->map(function ($postId) use ($defectId) {
                return ['defect_id' => $defectId, 'inspection_post_id' => $postId];
            })->all();
            DB::table('defect_inspection_post')->insert($pivot);

            DB::commit();
            $alert = "alert-success";
            $message = "$code is successfully saved";
            \LogActivity::addToLog('Defect save', "username: $username Status $message");
            return redirect()->route('defect.index')->with(['alert' => $alert, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            $alert = "alert-warning";
            $message = "$code is failed to save";
            \LogActivity::addToLog('Defect save', "username: $username Status $message");
            return redirect()->back()->with(['alert' => $alert, 'message' => $message]);
        }
    }

    public function edit(Request $request)
    {
        $id = $request->id;
        $data['title'] = "Edit Defect";
        $data['defect'] = DB::table('defects')->where('id', $id)->first();
        $data['posts'] = DB::table('inspection_posts')->where('status', '1')->orderBy('sequence')->get();
        $data['selectedPosts'] = DB::table('defect_inspection_post')->where('defect_id', $id)->pluck('inspection_post_id')->all();
        return view('defects.edit', $data);
    }

    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $id = $request->id;

        $rule = [
            'name' => 'required',
            'severity' => 'required',
            'disposition' => 'required',
            'posts' => 'required|array',
        ];
        $messages = ['posts.required' => 'Select at least one Inspection Post'];
        $this->validate($request, $rule, $messages);

        DB::beginTransaction();
        try {
            $rowAffected = DB::table('defects')->where('id', $id)->update([
                'name' => $request->input('name'),
                'category' => $request->input('category'),
                'severity' => $request->input('severity'),
                'disposition' => $request->input('disposition'),
                'is_repairable' => $request->boolean('is_repairable'),
                'description' => $request->input('description'),
                'status' => $request->input('status', '1'),
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            DB::table('defect_inspection_post')->where('defect_id', $id)->delete();
            $pivot = collect($request->input('posts'))->map(function ($postId) use ($id) {
                return ['defect_id' => $id, 'inspection_post_id' => $postId];
            })->all();
            DB::table('defect_inspection_post')->insert($pivot);

            DB::commit();
            $alert = $rowAffected > 0 ? "alert-success" : "alert-warning";
            $message = $rowAffected > 0 ? "Successfully updated" : "Failed to update";
            \LogActivity::addToLog('Defect update', "username: $username Status $message");
            return redirect()->route('defect.index')->with(['alert' => $alert, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            \LogActivity::addToLog('Defect update', "username: $username Status Failed");
            return redirect()->back()->with(['alert' => 'alert-warning', 'message' => 'Failed to update']);
        }
    }

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $rowAffected = DB::table('defects')->where('id', $request->id)->delete();

        $alert = $rowAffected > 0 ? "alert-success" : "alert-warning";
        $message = $rowAffected > 0 ? "Successfully Deleted" : "Failed to Delete";
        \LogActivity::addToLog('Defect delete', "username: $username Status $message");
        return redirect()->back()->with(['alert' => $alert, 'message' => $message]);
    }

    public function list(Request $request)
    {
        $code = strtolower($request->code);
        $name = strtolower($request->name);

        $data = DB::table('defects');
        $code ? $data->where('code', 'ilike', '%' . $code . '%') : '';
        $name ? $data->where('name', 'ilike', '%' . $name . '%') : '';
        $data->orderBy('code');

        return Datatables::of($data)
            ->addColumn('action', function ($row) {
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';
                if (Auth::user()->can('defect-edit')) {
                    $buttons .= '<a href="' . route('defect.edit', ['id' => $row->id]) . '" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
                }
                if (Auth::user()->can('defect-delete')) {
                    $buttons .= "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='" . route("defect.destroy", ["id" => $row->id]) . "'>
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
            ->editColumn('is_repairable', function ($row) {
                return $row->is_repairable ? 'Yes' : 'No';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
}
