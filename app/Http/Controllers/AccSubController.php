<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use DataTables;
use DB;

class AccSubController extends Controller
{
    private $title = 'Sub Account';

    public function getTableColoumn()
    {
        return json_encode([
            ['data'=> 'action', 'name'=>'action','title'=>'action', 'orderable'=> false, 'searchable'=> false ],
            ['data'=> 'sub_code', 'name'=>'sub_code','title'=>'Code' ],
            ['data'=> 'type_name', 'name'=>'type_name','title'=>'Account Type' ],
            ['data'=> 'description', 'name'=>'description','title'=>'Description' ],
        ], true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['kolom'] = $this->getTableColoumn();
        return view('accSub.index', $data);
    }

    public function list(Request $request)
    {
        $code = strtolower($request->code);
        $name = strtolower($request->name);

        $data = DB::table('acc_sub')
        ->leftJoin('acc_types', 'acc_types.code', '=', 'acc_sub.type_code')
        ->where('acc_sub.sub_code', 'ilike', '%'.$code.'%')
        ->where('acc_sub.description', 'ilike', '%'.$name.'%')
        ->select('acc_sub.*', 'acc_types.name as type_name')
        ->orderBy('acc_sub.sub_code')
        ->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                    <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                        <i data-feather="menu"></i>
                    </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            $buttons .=         '<a href="'. route('accSub.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            $buttons .=         "<a href='javascript:;'
                                id='deleteButton'
                                class='dropdown-item'
                                data-toggle='modal'
                                data-target='#smallModal'
                                data-href='". route("accSub.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
                                <i data-feather='trash-2' class='feather-14-red'></i>
                                Delete
                            </a>";
            $buttons .=     '</div>
                        </div>';
            return $buttons;
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function edit(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";
        $data['types'] = DB::table('acc_types')->where('status', 1)->orderBy('name')->get();
        $data['accSub'] = DB::table('acc_sub')->where('id', $id)->get()->first();
        return view('accSub.edit', $data);
    }

    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $typeCode = $request->input('typeCode');
        $desc = $request->input('desc');

        $this->validate($request, [
            'typeCode' => 'required',
            'desc' => 'required',
        ], [
            'required' => 'The field is required.',
        ]);

        $rowAffected = DB::table('acc_sub')->where('id', $id)->update([
            'type_code' => $typeCode,
            'description' => $desc,
            'updated_by' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        \LogActivity::addToLog('Update Sub Account', "username: $username id: $id");

        $message = $rowAffected > 0 ? 'Successfully updated' : 'Failed to update';
        return redirect()->back()->with(['status' => 1, 'alert' => $rowAffected > 0 ? 'success' : 'warning', 'message' => $message]);
    }

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $subCode = DB::table('acc_sub')->where('id', $id)->value('sub_code');

        if ($subCode && DB::table('accounts')->where('parent_id', $subCode)->exists()) {
            return redirect()->back()->with(['alert' => 'warning', 'message' => "Tidak bisa dihapus, masih dipakai oleh akun di bawah $subCode"]);
        }

        $rowAffected = DB::table('acc_sub')->where('id', $id)->delete();

        \LogActivity::addToLog('Delete Sub Account', "username: $username id: $id");

        $message = $rowAffected > 0 ? 'Successfully Deleted' : 'Failed to Delete';
        return redirect()->back()->with(['alert' => $rowAffected > 0 ? 'success' : 'warning', 'message' => $message]);
    }

    // Dipakai modal "Tambah Sub Account" di accounts/create: list + edit + delete
    // inline tanpa pindah halaman, id tidak di-encrypt karena cuma dipanggil dari
    // AJAX sesi yang sudah login (bukan link yang di-share).
    public function quickList(Request $request)
    {
        $typeCode = $request->input('typeCode');

        $data = DB::table('acc_sub')
        ->where('type_code', $typeCode)
        ->orderBy('sub_code')
        ->get(['id', 'sub_code', 'description']);

        return response()->json($data);
    }

    public function quickUpdate(Request $request)
    {
        $username = Auth::user()->username;
        $id = $request->input('id');
        $desc = $request->input('desc');

        $this->validate($request, [
            'id' => 'required',
            'desc' => 'required',
        ]);

        DB::table('acc_sub')->where('id', $id)->update([
            'description' => $desc,
            'updated_by' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        \LogActivity::addToLog('Update Sub Account', "username: $username id: $id");

        $row = DB::table('acc_sub')->where('id', $id)->first(['id', 'sub_code', 'description']);
        return response()->json($row);
    }

    public function quickDestroy(Request $request)
    {
        $username = Auth::user()->username;
        $id = $request->input('id');
        $subCode = DB::table('acc_sub')->where('id', $id)->value('sub_code');

        if ($subCode && DB::table('accounts')->where('parent_id', $subCode)->exists()) {
            return response()->json(['status' => 0, 'message' => "Tidak bisa dihapus, masih dipakai oleh akun di bawah $subCode"], 422);
        }

        DB::table('acc_sub')->where('id', $id)->delete();

        \LogActivity::addToLog('Delete Sub Account', "username: $username id: $id");

        return response()->json(['status' => 1]);
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $typeCode = $request->input('typeCode');
        $subCode = strtoupper($request->input('subCode'));
        $desc = $request->input('desc');

        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $this->validate($request, [
            'typeCode' => 'required',
            'subCode' => 'required|iunique:acc_sub,sub_code',
            'desc' => 'required',
        ], [
            'required' => 'The field is required.',
            'iunique' => 'The sub account code has already been taken',
        ]);

        DB::table('acc_sub')->insert([
            'type_code' => $typeCode,
            'sub_code' => $subCode,
            'description' => $desc,
            'created_by' => $username,
            'updated_by' => $username,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        \LogActivity::addToLog('Save Sub Account', "username: $username sub_code: $subCode");

        if ($request->wantsJson()) {
            return response()->json(['status' => 1, 'sub_code' => $subCode, 'description' => $desc]);
        }
        return redirect()->back()->with(['status' => 1, 'alert' => 'success', 'message' => "$subCode successfully saved"]);
    }
}
