<?php

namespace App\Http\Controllers\Accounting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Response;
use App\Permission;
use DataTables;
use DB;
use App\Http\Controllers\AttributeController as Attributes;

class MasterPpnController extends Controller
{
    private $title;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "Master PPN";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'ppn_year','name'=>'ppn_year','title'=>'Year'],
            ['data'=>'ppn_value','name'=>'ppn_value','title'=>'PPN Value'],
            ['data'=>'ppn_start_date','name'=>'ppn_start_date','title'=>'Start Date'],
            ['data'=>'ppn_end_date','name'=>'ppn_end_date','title'=>'End Date'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated At']
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();

        $ppnDate = '30-12-2019';
        $ppnValue = Attributes::getLastPpn($ppnDate);
        
        return view("accounting.masterPpn.index",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $aYear = $request->aYear;
        $aStartDate = $request->aStartDate ? date('Y-m-d', strtotime($request->aStartDate)):null;
        $aEndDate = $request->aEndDate ? date('Y-m-d', strtotime($request->aEndDate)):null;
        $aPpnValue = is_null($request->aPpnValue) ? 0 : preg_replace('/[^0-9.]+/', '', $request->aPpnValue);
        $aIdKu = $request->aIdKu;
        $status = 1;
        $rowAffected = null;
               
        DB::beginTransaction();
        try {
            if ($aIdKu) {

                $rowAffected = DB::table('master_ppn')
                ->where('id', $aIdKu)
                ->update([
                    'ppn_year' => $aYear,
                    'ppn_value' => $aPpnValue,
                    'ppn_start_date' => $aStartDate,
                    'ppn_end_date' => $aEndDate,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
            }else{  
                $rowAffected = DB::table('master_ppn')->insert([
                    'ppn_year' => $aYear,
                    'ppn_value' => $aPpnValue,
                    'ppn_start_date' => $aStartDate,
                    'ppn_end_date' => $aEndDate,
                    'status' => $status,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            if($rowAffected){
                $title ='Save Master PPN';
                $alert  ="success";
                $message  = "$title is successfully saved";
                DB::commit(); 
            }else{
                DB::rollBack();
                $title ='Save Master PPN';
                $alert  ="warning";
                $message  = "$title is failed to save";
            }

            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert));

        } catch (Exception $e) {
            DB::rollBack();
            $title ='Save Master PPN';
            $alert  ="warning";
            $message  = "$title is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 0, 'message' => $message,'alert'=>$alert));
        }               
    }

    public function edit(Request $request)
    {       
        $storeCode = Auth::user()->initial_store;
        $id = $request->id;           

        $data= DB::table('master_ppn')
            ->where('id',$id)
            ->first();

        $aStartDate = $data->ppn_start_date ? date('d-m-Y', strtotime($data->ppn_start_date)):'';
        $aEndDate = $data->ppn_end_date ? date('d-m-Y', strtotime($data->ppn_end_date)):'';

        return response()->json(array('year' => $data->ppn_year, 'ppnValue' => $data->ppn_value, 'startDate' => $aStartDate, 'endDate' => $aEndDate,'id' => $id));
        
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;    
        $id=Crypt::decryptString($request->id);   
      
        $rowAffected = DB::table('master_ppn')->where('id',$id)->delete();
      
        if($rowAffected>0){
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
       
        $data = DB::table('master_ppn')
        ->select('master_ppn.*'
            ,DB::raw("to_char(ppn_start_date, 'DD-MM-YYYY') as ppn_start_date")
            ,DB::raw("to_char(ppn_end_date, 'DD-MM-YYYY') as ppn_end_date")
        )
        ->orderBy('ppn_year')->get();

        return DataTables::of($data)
        ->addColumn('action', function ($data) {            

            $buttons = '<div class="btn-group">
                            <a class="btn btn-icon btn-flat-primary dropdown-toggle hide-arrow" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu">';

            $buttons .=  '<a href="javascript:;" onclick="validasiEdit(\''.$data->id.'\')" class="dropdown-item">
                            <i data-feather="edit"></i>
                            <span>'. __("Edit") .'</span>
                         </a>';
            
            // $buttons .= '<a href="'. route('masterPpn.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
            //                 <i data-feather="edit"></i>
            //                 <span>'. __("Edit") .'</span>
            //             </a>';
                            
            $buttons .=     "<a href='javascript:;'
                                id='deleteButton'
                                class='dropdown-item'
                                data-toggle='modal'
                                data-target='#smallModal'
                                data-href='". route("masterPpn.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
                                <i data-feather='trash-2' class='feather-14-red mr-25'></i> 
                                <span>". __("Delete") ."</span>                   
                            </a>";

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        
        // ->addColumn('status', function ($data) {
        //     $badges=['badge-light-secondary','badge-light-primary','badge-light-secondary','badge-light-warning','badge-light-success','badge-light-dark','badge-light-dark','badge-light-danger','badge-light-danger'];
        //     //            0       1         2              3            4             5            6        7          8
        //     $status = ['DRAFT','NEW','WAITING APPROVE','APPROVED','PARTIAL PAID','FUll PAID','COMPLETE','REJECTED','CANCELED'];
        //     return "<div class='badge badge-pill ".$badges[$data->status]."'>".$status[$data->status]."</div>";
            
        // })
        
        ->rawColumns(['action','status'])
        ->make(true);
    }

}
