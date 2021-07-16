<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Route;
use GuzzleHttp\Client;
use Response;


use App\Models\Permission;
use DataTables;
use DB;
use PDF;


class MenuController extends Controller
{

	/*
	Database menu--- example

	CREATE TABLE `menus` (
	  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	  `parent_id` INT(10) NOT NULL,
	  `ordering` INT(10) DEFAULT 0,
	  `title` VARCHAR(100) DEFAULT NULL,
	  `link` VARCHAR(100) DEFAULT NULL,
	  `permission` VARCHAR(100) DEFAULT NULL,
	  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	  PRIMARY KEY (`id`)  
	) 
	
	// Kalau parent_id nya 0 berarti itu menu, selain 0 berarti sub menu
	INSERT INTO menus (parent_id,ordering,title,link,permission) VALUES(0,1,'Dashboard','/home','dashboard');
	INSERT INTO menus (parent_id,ordering,title,link,permission) VALUES(0,2,'Master','','menu-master');
	INSERT INTO menus (parent_id,ordering,title,link,permission) VALUES(0,3,'Setting','','menu-setting');
	INSERT INTO menus (parent_id,ordering,title,link,permission) VALUES(0,4,'Logout','/logout','');
	INSERT INTO menus (parent_id,ordering,title,link,permission) VALUES(2,1,'Cabang','cabang.show','cabang-index');
	INSERT INTO menus (parent_id,ordering,title,link,permission) VALUES(3,1,'Users','users.index','user-index');
	INSERT INTO menus (parent_id,ordering,title,link,permission) VALUES(3,2,'User Cabang','showusercabang','user-index');
	INSERT INTO menus (parent_id,ordering,title,link,permission) VALUES(3,3,'Roles','roles.index','role-index');


	Ambil menu dari controller lain 
  	$menu = app(\App\Http\Controllers\MenuController::class)->getMenu();
    return view('home',array('menu' =>$menu));       

    kalo dari blade langsung panggil ini 
     {!! app(\App\Http\Controllers\MenuController::class)->getMenu() !!}

	*/

    public function getMenu()
    {
    	$userid=Auth::user()->id;
    	$menu ='<ul>';
    	$parentmenus = db::select("SELECT * FROM menus WHERE id in (SELECT id FROM menus WHERE permission IN (
								   SELECT name FROM permissions WHERE id IN (SELECT permission_id FROM permission_role WHERE role_id IN (SELECT role_id FROM roles WHERE id IN (SELECT role_id FROM role_user WHERE user_id =$userid) ))) OR permission ='' ) and  parent_id = 0 order by ordering");
        foreach ($parentmenus as $parentmenu) { 
        	$parentlink=$parentmenu->link;
        	$parentitle=$parentmenu->title;
        	$parenticon=$parentmenu->icon;

        	if ($parentlink ==''){
        		$iconnya='';
        		if ($parenticon != null || $parenticon != '' ){
        			$iconnya='<i class=" fa '.$parenticon.' fa-fw fa-1x"></i>';
        		}
        		
        		$menu.='<li>';
        		$menu.='<a href="javascript:void(0);">'.$iconnya.'<span class="title">'.$parentitle.'</span><span class="arrow <?php echo e(\Request::segment(1) == \''.$parentitle.'\' ? \'open\' : \'\'); ?>"></span></a>';
        		$menu.='<ul class="">';
        		$idparent =$parentmenu->id;
	        	$submenus = db::select("SELECT * FROM menus WHERE permission IN (SELECT name FROM permissions WHERE id IN (SELECT permission_id FROM permission_role WHERE role_id IN (SELECT role_id FROM roles WHERE id IN (SELECT role_id FROM role_user WHERE user_id =$userid) ))) and parent_id = $idparent order by ordering");
	        	foreach ($submenus as $submenu) { 
	        		$sublink=$submenu->link;
	        		$subtitle=$submenu->title;
	        		$subicon=$submenu->icon;
	        		$iconnyasub='';
	        		if ($subicon != null || $subicon != '' ){
	        			$iconnyasub='<i class="fa '.$subicon.' fa-fw fa-1x"></i>';
	        		}
	        		$menu.='<li style="white-space: nowrap;" class="<?php echo e(\Request::segment(2) == \''.$subtitle.'\' ? \'open\' : \'\'); ?>">'.$iconnyasub.'<span> <a href="'.route($sublink).'">'.$subtitle.'</a></span></li>';
	        	}
	        	$menu.='</ul>';
	        	$menu.='</li>';
        	}else{

        		$iconnya='';
        		
        		if ($parenticon != null || $parenticon != '' ){
        			$iconnya='<i class="fa '.$parenticon.' fa-fw fa-1x"></i>';
        		}
        		$menu.='<li><a href="'.url($parentlink).'" >'.$iconnya.$parentitle.'</a></li>';
        	}
       	}
       	$menu.='</ul>';
        return $menu;
	}
	

	public function showmenu(Request $request)
    {	
		$app = app();
		$data['routeCollection'] = $app->routes->getRoutes();
		$data['menus'] = DB::table('menus')->where ('parent_id','=',0)->get();
		$data['permissions'] = DB::table('permissions')->where ('display_name','like','%Menu%')->get();
		$data['icons'] = DB::table('fa_icons')->get();
		$data['menu_tree']=$this->menutree();
		   
		return view('menu.index', $data);
	}

	public function daftarmenu(Request $request)
    {	
		$daftarmenu = $this->menutree();
		return [$daftarmenu];
	}

	public function treemenu()
    {
			
    	$menu_tree ='<ul id="dragTreeData" class="hidden">';
    	$parentmenus = db::select("SELECT * FROM menus where parent_id = 0 order by ordering");
        foreach ($parentmenus as $parentmenu) { 
        	$parentlink=$parentmenu->link;
        	$parentitle=$parentmenu->title;
			$parenticon=$parentmenu->icon;
			
        	if ($parentlink ==''){
        		$iconnya='';
        		if ($parenticon != null || $parenticon != '' ){
        			$iconnya='<i class=" fa '.$parenticon.' fa-fw fa-1x"></i>';
        		}
        		
        		$menu_tree.='<li class="expanded" id="'.$parentitle.'">'.$parentitle;
        		$menu_tree.='<ul>';
        		$idparent =$parentmenu->id;
	        	$submenus = db::select("SELECT * FROM menus WHERE permission IN (SELECT name FROM permissions WHERE id IN (SELECT permission_id FROM permission_role WHERE role_id IN (SELECT role_id FROM roles WHERE id IN (SELECT role_id FROM role_user WHERE user_id =$userid) ))) and parent_id = $idparent order by ordering");
	        	foreach ($submenus as $submenu) { 
	        		$sublink=$submenu->link;
	        		$subtitle=$submenu->title;
					$subicon=$submenu->icon;
					$menu_tree.='<li id="'.$subtitle.'">'.$subtitle;
	        		// $iconnyasub='';
	        		// if ($subicon != null || $subicon != '' ){
	        		// 	$iconnyasub='<i class="fa '.$subicon.' fa-fw fa-1x"></i>';
	        		// }        		
	        	}
	        	$menu_tree.='</ul>';
	        	$menu_tree.='</li>';
        	}else{
				$iconnya='';
				$menu_tree.='<li id="'.$parentitle.'" title="'.$parentitle.'">'.$parentitle.'</li>';
        		
        		if ($parenticon != null || $parenticon != '' ){
        			$iconnya='<i class="fa '.$parenticon.' fa-fw fa-1x"></i>';
        		}
        		// $menu_tree.='<li><a href="'.url($parentlink).'" >'.$iconnya.$parentitle.'</a></li>';
        	}
       	}
       	$menu_tree.='</ul>';
        return  Response()->json($menu_tree);
	}

	public function deletemenu(Request $request)
    {
		$menu_id=$request['menu_id'];
        DB::beginTransaction();
            try {              
                DB::table('menus')->where('id',"=",$menu_id)->delete();
                DB::commit();
                return response()->json(array('status' => 1, 'message' => 'Data sudah di delete'));

            } catch (Exception $e) {
                DB::rollBack();
                return response()->json(array('status' => 0, 'message' => 'Delete data gagal'));
            }
	}
		

	public function menutree(){
		
		$menu_tree ='<ul id="dragTreeData" class="hidden">';
    	$parentmenus = db::select("SELECT * FROM menus where parent_id = 0 order by ordering");
        foreach ($parentmenus as $parentmenu) { 
        	$parentlink=$parentmenu->link;
        	$parentitle=$parentmenu->title;
			$parenticon=$parentmenu->icon;
			
        	if ($parentlink ==''){
        		$iconnya='';
        		if ($parenticon != null || $parenticon != '' ){
        			$iconnya='<i class=" fa '.$parenticon.' fa-fw fa-1x"></i>';
        		}
        		
        		$menu_tree.='<li id="'.$parentitle.'">'.$parentitle;
        		$menu_tree.='<ul>';
        		$idparent =$parentmenu->id;
	        	$submenus = db::select("SELECT * FROM menus where parent_id = $idparent order by ordering");
	        	foreach ($submenus as $submenu) { 
	        		$sublink=$submenu->link;
	        		$subtitle=$submenu->title;
					$subicon=$submenu->icon;
					$menu_tree.='<li id="'.$subtitle.'">'.$subtitle;
	        		// $iconnyasub='';
	        		// if ($subicon != null || $subicon != '' ){
	        		// 	$iconnyasub='<i class="fa '.$subicon.' fa-fw fa-1x"></i>';
	        		// }        		
	        	}
	        	$menu_tree.='</ul>';
	        	$menu_tree.='</li>';
        	}else{
				$iconnya='';
				$menu_tree.='<li id="'.$parentitle.'" title="'.$parentitle.'">'.$parentitle.'</li>';
        		
        		if ($parenticon != null || $parenticon != '' ){
        			$iconnya='<i class="fa '.$parenticon.' fa-fw fa-1x"></i>';
        		}
        		// $menu_tree.='<li><a href="'.url($parentlink).'" >'.$iconnya.$parentitle.'</a></li>';
        	}
       	}
		$menu_tree.='</ul>';
		return $menu_tree;
	}

	public function listmenu(Request $request)
    {      
        $sqlku=("SELECT * FROM menus order by ID");
        $menu = DB::table(DB::raw("($sqlku) as oki"));
        return Datatables::of($menu)
        ->addColumn('action', function ($menu) {
            $buttons = '<div class="text-center">
                            <div class="dropdown hidden-md-down">
                                <button class="btn btn-default" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" title="Menu">
                                <i class="fs-14 fa fa-bars"></i>
                            </button>
                                <ul class="dropdown-menu">';

            $buttons .= '<li><a href="javascript:;" onclick="mdlinfo(\''.$menu->id.'\')"><i class="fa fa-info fa-fw fa-1x"></i>Detail</a></li>';
            if (Auth::user()->can('karyawan-edit')) {
                 $buttons .= '<li><a href="javascript:;" onclick="validasiedit(\''.$menu->id.'\')"><i class="fa fa-edit fa-fw fa-1x"></i>Edit</a></li>';
            }
            if (Auth::user()->can('karyawan-delete')) {
                $buttons .= '<li class="divider"></li>';
                $buttons .= '<li><a href="javascript:;" onclick="validasidelete(\''.$menu->id.'\')" ><i class="fa fa-trash text-danger fa-fw fa-1x"></i>Delete</a></li>';
            }
            $buttons .= '</ul></div></div>';
            return $buttons;
			})
		->addColumn('iconnya', function ($menu) {
			$icon='<i class="fa '.$menu->icon.' fa-2x" aria-hidden="true"></i>';
			return $icon;
		})
        ->make(true);
	}

	public function showpermission(Request $request)
    {
        return view('menu.permission');
	}
	


	
}
