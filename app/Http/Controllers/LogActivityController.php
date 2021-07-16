<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use DataTables;

class LogActivityController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('log.logActivity');
    }

    public function myTestAddToLog()
    {

        \LogActivity::addToLog('My Testing Add To Log.');
        dd('log insert successfully.');
    }


    public function showLogLists()
    {

        $logs = \LogActivity::logActivityLists();
        return Datatables::of($logs)
        ->addColumn('action', function ($logs) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="more-vertical"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            $buttons .=         '<a href="'. route('users.edit', $logs->id) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Details
                                </a>';
            $buttons .=         '<a href="javascript:;" onclick="validasidelete(\''.$logs->id.'\')" class="dropdown-item">
                                    <i data-feather="x-square"></i>
                                    Delete
                                </a>';
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('date',function ($logs){
            return $logs->created_at;
        })
        ->rawColumns(['action'])
        ->make(true);

    }

}
