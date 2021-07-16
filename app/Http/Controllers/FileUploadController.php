<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Image;
use App\Models\User;

class FileUploadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function fileUpload()
    {
        return view('fileUpload');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function fileUploadPost(Request $request)
    {
        $userid=Auth::user()->username;

        $request->validate([
            'imageUpload' => 'required|mimes:png,jpg,jepg|max:5000',
        ]);
        
        $path="app-assets/images/avatars";

        $fileName = $userid.".".$request->file('imageUpload')->extension();  
        $request->file('imageUpload')->move($path, $fileName);

        $result = User::updateOrCreate(
            [
                'username' => $userid,
            ],
            [
                'filename' => $path.'/'.$fileName
            ]
        );
        
        return back()
            ->with('success','You have successfully upload file.')
            ->with('file',$fileName);

    }

}