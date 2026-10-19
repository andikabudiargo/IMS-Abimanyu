<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use DB;

class AccSubController extends Controller
{
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
